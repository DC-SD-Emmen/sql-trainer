<?php
// JSON-API voor de SQL-trainer.
//   GET  ?action=init               opdrachten + databaseschema
//   GET  ?action=schema             databaseschema
//   POST ?action=check     {id, sql} opdracht nakijken (wijzigingen worden teruggedraaid)
//   POST ?action=run       {sql}     vrij oefenen (wijzigingen blijven bewaard)
//   GET  ?action=solution&id=...     modelantwoord + verwacht resultaat
//   POST ?action=reset               database terugzetten naar de oorspronkelijke staat

declare(strict_types=1);

// PHP-waarschuwingen mogen niet als HTML in het JSON-antwoord terechtkomen;
// fouten worden hieronder als nette JSON-melding teruggegeven.
ini_set('display_errors', '0');

require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/checker.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function respond(array $data, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function findExercise(array $exercises, string $id): array
{
    foreach ($exercises as $exercise) {
        if ($exercise['id'] === $id) {
            return $exercise;
        }
    }
    respond(['error' => "Opdracht '$id' bestaat niet."], 404);
}

function requireSql(array $input): string
{
    $sql = trim((string) ($input['sql'] ?? ''));
    if ($sql === '') {
        respond(['error' => 'Typ eerst een query.'], 422);
    }
    return $sql;
}

$exercises = require __DIR__ . '/lib/exercises.php';
$action = $_GET['action'] ?? '';
$input = json_decode(file_get_contents('php://input') ?: '[]', true) ?: [];

if (in_array($action, ['check', 'run', 'reset'], true) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(['error' => 'Gebruik POST voor deze actie.'], 405);
}

try {
    switch ($action) {
        case 'init':
            $public = array_map(static fn (array $e) => [
                'id' => $e['id'],
                'topic' => $e['topic'],
                'title' => $e['title'],
                'from' => $e['from'],
                'story' => $e['story'],
                'text' => $e['text'],
                'hint' => $e['hint'],
                'type' => $e['type'],
            ], $exercises);
            $schema = null;
            $schemaError = null;
            try {
                $schema = getSchema(connect());
            } catch (mysqli_sql_exception $e) {
                $schemaError = formatError($e, 1);
            }
            respond(['exercises' => $public, 'schema' => $schema, 'schemaError' => $schemaError]);

        case 'schema':
            respond(['schema' => getSchema(connect())]);

        case 'check':
            $exercise = findExercise($exercises, (string) ($input['id'] ?? ''));
            respond(checkExercise(connect(), $exercise, requireSql($input)));

        case 'run':
            respond(runStatements(connect(), requireSql($input)));

        case 'solution':
            $exercise = findExercise($exercises, (string) ($_GET['id'] ?? ''));
            $checkQuery = $exercise['type'] === 'dml' ? $exercise['check'] : null;
            respond(['solution' => $exercise['solution'], 'expected' => runInRollback(connect(), $exercise['solution'], $checkQuery)]);

        case 'reset':
            resetDatabase();
            respond(['ok' => true, 'schema' => getSchema(connect())]);

        default:
            respond(['error' => 'Onbekende actie.'], 400);
    }
} catch (mysqli_sql_exception $e) {
    $error = formatError($e, 1);
    if ($e->getCode() === 2002) {
        $error['hint'] = 'Kan geen verbinding maken met de database. Start MySQL nog op? Wacht even en probeer het opnieuw.';
    }
    respond(['error' => $error['hint'] ?? $error['message'], 'details' => $error], 500);
} catch (Throwable $e) {
    respond(['error' => $e->getMessage()], 500);
}
