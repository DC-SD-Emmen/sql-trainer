<?php
// Databasekoppeling en hulpfuncties voor het uitvoeren van SQL van studenten.

declare(strict_types=1);

const MAX_ROWS = 2000;          // maximaal aantal rijen dat we per resultaat ophalen
const MAX_EXECUTION_MS = 5000;  // maximale looptijd van een SELECT

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function env(string $name, string $default = ''): string
{
    $value = $_ENV[$name] ?? getenv($name);
    return ($value === false || $value === null || $value === '') ? $default : (string) $value;
}

/**
 * Maakt verbinding met MySQL. Met $useDatabase = false wordt er geen database
 * geselecteerd; dat is nodig voor de reset (de database kan dan verwijderd zijn).
 */
function connect(bool $useDatabase = true): mysqli
{
    $db = new mysqli(
        env('DB_HOST', 'mysql'),
        env('DB_USER'),
        env('DB_PASSWORD'),
        $useDatabase ? env('DB_NAME') : null
    );
    $db->set_charset('utf8mb4');
    $db->query('SET SESSION max_execution_time = ' . MAX_EXECUTION_MS);
    return $db;
}

/**
 * Voert één of meer statements uit en verzamelt het resultaat van elk statement.
 * Bij een fout wordt gestopt; de resultaten tot dat moment blijven bewaard.
 *
 * @return array{results: list<array>, error: ?array}
 */
function runStatements(mysqli $db, string $sql): array
{
    $results = [];
    try {
        $db->multi_query($sql);
        do {
            $result = $db->store_result();
            if ($result instanceof mysqli_result) {
                $results[] = readResult($result);
                $result->free();
            } else {
                $results[] = ['type' => 'affected', 'affected' => $db->affected_rows];
            }
        } while ($db->more_results() && $db->next_result());
    } catch (mysqli_sql_exception $e) {
        // Overgebleven resultaten opruimen zodat de verbinding bruikbaar blijft.
        while ($db->more_results()) {
            try {
                $db->next_result();
            } catch (mysqli_sql_exception) {
                break;
            }
        }
        return ['results' => $results, 'error' => formatError($e, count($results) + 1)];
    }
    return ['results' => $results, 'error' => null];
}

function readResult(mysqli_result $result): array
{
    $numericTypes = [
        MYSQLI_TYPE_TINY, MYSQLI_TYPE_SHORT, MYSQLI_TYPE_LONG, MYSQLI_TYPE_INT24,
        MYSQLI_TYPE_LONGLONG, MYSQLI_TYPE_DECIMAL, MYSQLI_TYPE_NEWDECIMAL,
        MYSQLI_TYPE_FLOAT, MYSQLI_TYPE_DOUBLE, MYSQLI_TYPE_YEAR,
    ];
    $columns = [];
    foreach ($result->fetch_fields() as $field) {
        $columns[] = ['name' => $field->name, 'numeric' => in_array($field->type, $numericTypes, true)];
    }

    $rows = [];
    $truncated = false;
    while ($row = $result->fetch_row()) {
        if (count($rows) >= MAX_ROWS) {
            $truncated = true;
            break;
        }
        $rows[] = $row;
    }

    return [
        'type' => 'rows',
        'columns' => $columns,
        'rows' => $rows,
        'rowCount' => $truncated ? $result->num_rows : count($rows),
        'truncated' => $truncated,
    ];
}

/** Vertaalt veelvoorkomende MySQL-fouten naar een Nederlandse uitleg. */
function formatError(mysqli_sql_exception $e, int $statement): array
{
    $hints = [
        1044 => 'Je hebt geen rechten op deze database.',
        1049 => 'De oefen-database bestaat niet (meer). Klik op "Database resetten".',
        1054 => 'Een kolomnaam bestaat niet. Controleer de spelling in het databaseschema.',
        1062 => 'Deze waarde bestaat al en moet uniek zijn. Is de rij misschien al toegevoegd? Reset eventueel de database.',
        1064 => 'Er zit een fout in de schrijfwijze (syntax) van je query. Kijk vooral naar het stuk vlak na "near".',
        1065 => 'Je hebt geen query ingevoerd.',
        1136 => 'Het aantal kolommen komt niet overeen met het aantal waarden in VALUES.',
        1146 => 'Deze tabel bestaat niet. Controleer de tabelnaam in het databaseschema.',
        1222 => 'De SELECT-statements hebben een verschillend aantal kolommen.',
        1364 => 'Een verplichte kolom (NOT NULL) heeft geen waarde gekregen.',
        1366 => 'Een waarde past niet bij het type van de kolom.',
        1451 => 'Deze rij wordt nog gebruikt in een andere tabel (foreign key) en kan daarom niet worden verwijderd.',
        1452 => 'De waarde verwijst naar een rij in een andere tabel die niet bestaat (foreign key).',
        2013 => 'De verbinding met de database werd verbroken.',
        3024 => 'De query duurde te lang en is afgebroken.',
    ];
    return [
        'code' => $e->getCode(),
        'message' => $e->getMessage(),
        'hint' => $hints[$e->getCode()] ?? null,
        'statement' => $statement,
    ];
}

/** Haalt alle tabellen en kolommen op, inclusief het aantal rijen per tabel. */
function getSchema(mysqli $db): array
{
    $result = $db->query(
        "SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_KEY
           FROM information_schema.COLUMNS
          WHERE TABLE_SCHEMA = DATABASE()
          ORDER BY TABLE_NAME, ORDINAL_POSITION"
    );
    $tables = [];
    while ($row = $result->fetch_assoc()) {
        $tables[$row['TABLE_NAME']]['columns'][] = [
            'name' => $row['COLUMN_NAME'],
            'type' => $row['COLUMN_TYPE'],
            'nullable' => $row['IS_NULLABLE'] === 'YES',
            'key' => $row['COLUMN_KEY'],
        ];
    }
    foreach ($tables as $name => &$table) {
        $count = $db->query('SELECT COUNT(*) FROM `' . str_replace('`', '``', $name) . '`')->fetch_row()[0];
        $table = ['name' => $name, 'rows' => (int) $count, 'columns' => $table['columns']];
    }
    return array_values($tables);
}

/** Zet de oefen-database terug naar de oorspronkelijke staat. */
function resetDatabase(): void
{
    $file = env('DB_SEED_FILE', '/database/example-database.sql');
    if (!is_readable($file)) {
        throw new RuntimeException("Het bestand $file is niet gevonden. Controleer de volumes in docker-compose.yml.");
    }
    $db = connect(false);
    $outcome = runStatements($db, file_get_contents($file));
    $db->close();
    if ($outcome['error'] !== null) {
        throw new RuntimeException('Resetten mislukt: ' . $outcome['error']['message']);
    }
}
