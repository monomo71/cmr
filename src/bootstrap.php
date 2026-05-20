<?php

declare(strict_types=1);

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (strpos($class, $prefix) !== 0) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $file = __DIR__ . '/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});

use App\Database;

const ROOT_PATH = __DIR__ . '/..';
const PDF_TEMPLATE = ROOT_PATH . '/pdf/CMR.pdf';
const GENERATED_DIR = ROOT_PATH . '/storage/generated';

$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$baseUrl = rtrim(dirname($scriptName), '/\\');
define('BASE_URL', $baseUrl === '' ? '/' : $baseUrl . '/');

if (!is_dir(GENERATED_DIR)) {
    mkdir(GENERATED_DIR, 0777, true);
}

$configFile = ROOT_PATH . '/config.php';
$defaultConfig = [
    'driver' => 'sqlite',
    'path' => ROOT_PATH . '/data/cmr.sqlite',
];
$dbConfig = is_file($configFile) ? require $configFile : $defaultConfig;

$db = new Database($dbConfig);
$pdo = $db->pdo();

function h(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function cmrFieldLabels(): array
{
    return [
        'field1' => '1 Afzender (naam, adres, land)',
        'field2' => '2 Geadresseerde / Ontvanger (naam, adres, land)',
        'field3' => '3 Plaats van aflevering van de goederen',
        'field4' => '4 Plaats en datum van inontvangstneming',
        'field5' => '5 Bijgevoegde documenten',
        'field6' => '6 Merken en nummers',
        'field7' => '7 Aantal colli',
        'field8' => '8 Wijze van verpakking',
        'field9' => '9 Aard der goederen',
        'field10' => '10 Statistisch nummer',
        'field11' => '11 Bruto gewicht in kg',
        'field12' => '12 Volume in m3',
        'field13' => '13 Instructies afzender',
        'field14' => '14 Frankeringsvoorschrift',
        'field15' => '15 Terugbetaling',
        'field16' => '16 CMR-vrachtvoerder (naam, adres, land)',
        'field17' => '17 Opvolgende vervoerders',
        'field18' => '18 Voorbehoud en opmerkingen van de vervoerder',
        'field19' => '19 Speciale overeenkomsten',
        'field20' => '20 Te betalen kosten / vrachtgegevens',
        'field21' => '21 Opgemaakt te',
        'field22' => '22 Handtekening en stempel afzender',
        'field23' => '23 Handtekening en stempel vervoerder',
        'field24' => '24 Goederen ontvangen (plaats, datum, handtekening)',
    ];
}

function extractReceiverName(string $field2): string
{
    $lines = preg_split('/\r\n|\r|\n/', trim($field2)) ?: [];
    $name = trim((string)($lines[0] ?? 'Ontvanger'));
    return $name === '' ? 'Ontvanger' : $name;
}

function extractDateFromField24(string $field24): string
{
    if (preg_match('/\b(\d{1,2}[.\-\/]\d{1,2}[.\-\/]\d{2,4})\b/u', $field24, $m) === 1) {
        $raw = str_replace(['.', '/'], '-', $m[1]);
        $parts = explode('-', $raw);
        if (count($parts) === 3) {
            [$d, $mth, $y] = $parts;
            $y = strlen($y) === 2 ? ('20' . $y) : $y;
            if (checkdate((int)$mth, (int)$d, (int)$y)) {
                return sprintf('%02d-%02d-%04d', (int)$d, (int)$mth, (int)$y);
            }
        }
    }

    return (new DateTimeImmutable('now'))->format('d-m-Y');
}

function sanitizeFilenamePart(string $value): string
{
    $value = trim($value);
    if ($value === '') {
        return 'Onbekend';
    }

    $ascii = iconv('UTF-8', 'ASCII//TRANSLIT', $value);
    if ($ascii !== false) {
        $value = $ascii;
    }

    $value = preg_replace('/[^A-Za-z0-9]+/', '_', $value) ?? 'Onbekend';
    $value = trim($value, '_');

    return $value === '' ? 'Onbekend' : $value;
}

function normalizeItemsRows(array $items): array
{
    $fields = ['field6', 'field7', 'field8', 'field9', 'field10', 'field11', 'field12'];
    $fieldIndex = array_flip($fields);

    $cleaned = [];
    foreach ($items as $item) {
        if (!is_array($item)) {
            continue;
        }

        $row = [];
        $hasContent = false;
        foreach ($fields as $f) {
            $v = trim((string)($item[$f] ?? ''));
            $row[$f] = $v;
            if ($v !== '') {
                $hasContent = true;
            }
        }

        if ($hasContent) {
            $cleaned[] = $row;
        }
    }

    if ($cleaned === []) {
        return [];
    }

    $fragmented = true;
    foreach ($cleaned as $row) {
        $filled = 0;
        foreach ($fields as $f) {
            if ($row[$f] !== '') {
                $filled++;
            }
        }
        if ($filled > 1) {
            $fragmented = false;
            break;
        }
    }

    if (!$fragmented) {
        return $cleaned;
    }

    $rebuilt = [];
    $current = array_fill_keys($fields, '');
    $lastIdx = -1;

    foreach ($cleaned as $row) {
        $key = null;
        $val = '';
        foreach ($fields as $f) {
            if ($row[$f] !== '') {
                $key = $f;
                $val = $row[$f];
                break;
            }
        }

        if ($key === null) {
            continue;
        }

        $idx = $fieldIndex[$key];
        if ($idx <= $lastIdx) {
            $rebuilt[] = $current;
            $current = array_fill_keys($fields, '');
        }

        $current[$key] = $val;
        $lastIdx = $idx;
    }

    $hasCurrent = false;
    foreach ($fields as $f) {
        if ($current[$f] !== '') {
            $hasCurrent = true;
            break;
        }
    }
    if ($hasCurrent) {
        $rebuilt[] = $current;
    }

    return $rebuilt;
}
