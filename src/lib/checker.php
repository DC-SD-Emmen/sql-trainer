<?php
// Nakijken: vergelijkt het resultaat van de student met dat van het modelantwoord.

declare(strict_types=1);

require_once __DIR__ . '/db.php';

/**
 * Voert SQL uit binnen een transactie die altijd wordt teruggedraaid.
 * Bij een DML-opdracht wordt daarna de controlequery uitgevoerd om te zien
 * hoe de tabel er na de wijziging uitziet.
 *
 * Let op: DDL zoals DROP TABLE voert in MySQL een impliciete COMMIT uit en
 * kan dus niet worden teruggedraaid. Daarvoor is de resetknop.
 */
function runInRollback(mysqli $db, string $sql, ?string $checkQuery): array
{
    $db->begin_transaction();
    try {
        $outcome = runStatements($db, $sql);
        $outcome['state'] = null;
        if ($outcome['error'] === null && $checkQuery !== null) {
            $state = runStatements($db, $checkQuery);
            if ($state['error'] !== null) {
                $outcome['error'] = $state['error'];
            } else {
                $outcome['state'] = end($state['results']);
            }
        }
        return $outcome;
    } finally {
        $db->rollback();
    }
}

function checkExercise(mysqli $db, array $exercise, string $sql): array
{
    $isDml = $exercise['type'] === 'dml';
    $checkQuery = $isDml ? $exercise['check'] : null;

    $student = runInRollback($db, $sql, $checkQuery);
    if ($student['error'] !== null) {
        return ['correct' => false, 'student' => $student, 'message' => 'Je query geeft een foutmelding.'];
    }

    $expected = runInRollback($db, $exercise['solution'], $checkQuery);
    if ($expected['error'] !== null) {
        return [
            'correct' => false,
            'student' => $student,
            'message' => 'Het modelantwoord kan niet worden uitgevoerd. De database is waarschijnlijk gewijzigd; '
                . 'klik op "Database resetten" en probeer het opnieuw.',
        ];
    }

    if ($isDml) {
        [$correct, $message] = compareDml($student, $expected);
    } else {
        $studentResult = lastResultSet($student['results']);
        if ($studentResult === null) {
            return [
                'correct' => false,
                'student' => $student,
                'message' => 'Je query leverde geen tabel met resultaten op. Gebruik een SELECT-statement.',
            ];
        }
        [$correct, $message] = compareResults($studentResult, lastResultSet($expected['results']), $exercise['order'] ?? null);
    }

    return ['correct' => $correct, 'student' => $student, 'message' => $message];
}

function lastResultSet(array $results): ?array
{
    foreach (array_reverse($results) as $result) {
        if ($result['type'] === 'rows') {
            return $result;
        }
    }
    return null;
}

function compareDml(array $student, array $expected): array
{
    $studentAffected = totalAffected($student['results']);
    $expectedAffected = totalAffected($expected['results']);

    [$sameState] = compareResults($student['state'], $expected['state'], true);
    if ($sameState) {
        return [true, 'Goed zo! De tabel ziet er precies zo uit als verwacht.'];
    }

    $message = 'De tabel ziet er na jouw query anders uit dan verwacht.';
    if ($studentAffected !== $expectedAffected) {
        $message .= sprintf(
            ' Jouw query heeft %d %s aangepast, verwacht was %d.',
            $studentAffected,
            $studentAffected === 1 ? 'rij' : 'rijen',
            $expectedAffected
        );
        if ($studentAffected > $expectedAffected) {
            $message .= ' Ben je de WHERE-voorwaarde vergeten of is die te ruim?';
        }
    } else {
        $message .= ' Het aantal aangepaste rijen klopt wel; controleer de waarden die je invult.';
    }
    return [false, $message];
}

function totalAffected(array $results): int
{
    $total = 0;
    foreach ($results as $result) {
        if ($result['type'] === 'affected' && $result['affected'] > 0) {
            $total += $result['affected'];
        }
    }
    return $total;
}

/**
 * Vergelijkt twee resultaattabellen op waarden (kolomnamen/aliassen tellen niet mee).
 *
 * $order: null = volgorde maakt niet uit, true = volgorde van alle kolommen telt,
 * list<int> = alleen de volgorde van deze kolommen (index) telt. Zo wordt een student
 * niet afgekeurd als rijen met dezelfde sorteerwaarde in een andere volgorde staan.
 */
function compareResults(array $actual, array $expected, array|bool|null $order): array
{
    $actualCols = count($actual['columns']);
    $expectedCols = count($expected['columns']);
    if ($actualCols !== $expectedCols) {
        return [false, sprintf(
            'Je resultaat heeft %d %s, maar er worden er %d verwacht. Controleer welke kolommen je selecteert.',
            $actualCols,
            $actualCols === 1 ? 'kolom' : 'kolommen',
            $expectedCols
        )];
    }

    if ($actual['rowCount'] !== $expected['rowCount']) {
        return [false, sprintf(
            'Je resultaat heeft %d %s, maar er worden er %d verwacht. Controleer je WHERE-voorwaarde en/of LIMIT.',
            $actual['rowCount'],
            $actual['rowCount'] === 1 ? 'rij' : 'rijen',
            $expected['rowCount']
        )];
    }

    $actualRows = array_map('normalizeRow', $actual['rows']);
    $expectedRows = array_map('normalizeRow', $expected['rows']);

    if (sortedKeys($actualRows) !== sortedKeys($expectedRows)) {
        if (sortedKeys(array_map('sortRow', $actualRows)) === sortedKeys(array_map('sortRow', $expectedRows))) {
            return [false, 'De juiste gegevens, maar de kolommen staan in een andere volgorde dan gevraagd.'];
        }
        return [false, 'Het aantal rijen klopt, maar de inhoud niet. Controleer je kolommen en je WHERE-voorwaarde.'];
    }

    if ($order !== null) {
        $indexes = $order === true ? range(0, $expectedCols - 1) : $order;
        $project = static fn (array $row) => json_encode(array_map(static fn ($i) => $row[$i], $indexes));
        if (array_map($project, $actualRows) !== array_map($project, $expectedRows)) {
            return [false, 'De juiste rijen, maar in de verkeerde volgorde. Controleer je ORDER BY (ASC of DESC?).'];
        }
    }

    return [true, 'Goed zo! Je resultaat is precies wat er gevraagd werd.'];
}

/** Zorgt dat bijv. 189.00 en 189 als gelijk worden gezien. */
function normalizeRow(array $row): array
{
    return array_map(static function ($value) {
        if ($value === null) {
            return null;
        }
        $value = (string) $value;
        if (is_numeric($value) && str_contains($value, '.') && !str_contains(strtolower($value), 'e')) {
            $value = rtrim(rtrim($value, '0'), '.');
            if ($value === '' || $value === '-' || $value === '-0') {
                $value = '0';
            }
        }
        return $value;
    }, $row);
}

function sortRow(array $row): array
{
    sort($row, SORT_STRING);
    return $row;
}

function sortedKeys(array $rows): array
{
    $keys = array_map('json_encode', $rows);
    sort($keys);
    return $keys;
}
