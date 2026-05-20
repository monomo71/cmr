<?php

declare(strict_types=1);

use App\PdfRenderer;

require_once __DIR__ . '/../src/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$rawItems = $_POST['items'] ?? [];
$items = [];
if (is_array($rawItems)) {
    foreach ($rawItems as $rawItem) {
        if (!is_array($rawItem)) {
            continue;
        }

        $item = [
            'field6' => trim((string)($rawItem['field6'] ?? '')),
            'field7' => trim((string)($rawItem['field7'] ?? '')),
            'field8' => trim((string)($rawItem['field8'] ?? '')),
            'field9' => trim((string)($rawItem['field9'] ?? '')),
            'field10' => trim((string)($rawItem['field10'] ?? '')),
            'field11' => trim((string)($rawItem['field11'] ?? '')),
            'field12' => trim((string)($rawItem['field12'] ?? '')),
        ];

        $hasContent = false;
        foreach ($item as $value) {
            if ($value !== '') {
                $hasContent = true;
                break;
            }
        }

        if ($hasContent) {
            $items[] = $item;
        }
    }
}
$items = normalizeItemsRows($items);

$data = [
    'field1' => trim((string)($_POST['field1'] ?? '')),
    'field2' => trim((string)($_POST['field2'] ?? '')),
    'field3' => trim((string)($_POST['field3'] ?? '')),
    'field4' => trim((string)($_POST['field4'] ?? '')),
    'field5' => trim((string)($_POST['field5'] ?? '')),
    'field13' => trim((string)($_POST['field13'] ?? '')),
    'field14' => trim((string)($_POST['field14'] ?? '')),
    'field15' => trim((string)($_POST['field15'] ?? '')),
    'field16' => trim((string)($_POST['field16'] ?? '')),
    'field17' => trim((string)($_POST['field17'] ?? '')),
    'field18' => trim((string)($_POST['field18'] ?? '')),
    'field19' => trim((string)($_POST['field19'] ?? '')),
    'field20' => trim((string)($_POST['field20'] ?? '')),
    'field21' => trim((string)($_POST['field21'] ?? '')),
    'field22' => trim((string)($_POST['field22'] ?? '')),
    'field23' => trim((string)($_POST['field23'] ?? '')),
    'field24' => trim((string)($_POST['field24'] ?? '')),
    'items' => $items,
];

$params = [
    'field1' => $data['field1'],
    'field2' => $data['field2'],
    'field3' => $data['field3'],
    'field4' => $data['field4'],
    'field5' => $data['field5'],
    'field13' => $data['field13'],
    'field14' => $data['field14'],
    'field15' => $data['field15'],
    'field16' => $data['field16'],
    'field17' => $data['field17'],
    'field18' => $data['field18'],
    'field19' => $data['field19'],
    'field20' => $data['field20'],
    'field21' => $data['field21'],
    'field22' => $data['field22'],
    'field23' => $data['field23'],
    'field24' => $data['field24'],
    'items_json' => json_encode($items, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
];

$editId = isset($_POST['edit_id']) && ctype_digit((string)$_POST['edit_id']) ? (int)$_POST['edit_id'] : null;
$oldPdfPath = null;
if ($editId) {
    $existing = $pdo->prepare('SELECT pdf_path FROM cmr_documents WHERE id = :id');
    $existing->execute(['id' => $editId]);
    $oldRecord = $existing->fetch(PDO::FETCH_ASSOC) ?: null;
    $oldPdfPath = $oldRecord['pdf_path'] ?? null;

    $stmt = $pdo->prepare(
        'UPDATE cmr_documents
        SET field1 = :field1, field2 = :field2, field3 = :field3, field4 = :field4, field5 = :field5,
            field13 = :field13, field14 = :field14, field15 = :field15, field16 = :field16, field17 = :field17, field18 = :field18,
            field19 = :field19, field20 = :field20, field21 = :field21, field22 = :field22, field23 = :field23, field24 = :field24,
            items_json = :items_json
        WHERE id = :id'
    );
    $stmt->execute($params + ['id' => $editId]);
    $id = $editId;
} else {
    $stmt = $pdo->prepare(
        'INSERT INTO cmr_documents (
            field1, field2, field3, field4, field5,
            field13, field14, field15, field16, field17, field18,
            field19, field20, field21, field22, field23, field24,
            items_json
        ) VALUES (
            :field1, :field2, :field3, :field4, :field5,
            :field13, :field14, :field15, :field16, :field17, :field18,
            :field19, :field20, :field21, :field22, :field23, :field24,
            :items_json
        )'
    );
    $stmt->execute($params);
    $id = (int)$pdo->lastInsertId();
}

$receiver = sanitizeFilenamePart(extractReceiverName($data['field2']));
$date = extractDateFromField24($data['field24']);
$filename = sprintf('CMR_%s_%s.pdf', $receiver, $date);
$outputFile = GENERATED_DIR . '/' . $id . '-' . $filename;
$relativeOutput = 'storage/generated/' . $id . '-' . $filename;

$renderer = new PdfRenderer();
try {
    $renderer->render(PDF_TEMPLATE, $data, $outputFile);
} catch (Throwable $e) {
    http_response_code(500);
    echo '<h1>PDF generatie mislukt</h1>';
    echo '<pre>' . h($e->getMessage()) . '</pre>';
    exit;
}

$update = $pdo->prepare('UPDATE cmr_documents SET pdf_path = :pdf WHERE id = :id');
$update->execute(['pdf' => $relativeOutput, 'id' => $id]);

if ($oldPdfPath && $oldPdfPath !== $relativeOutput) {
    $oldFull = ROOT_PATH . '/' . ltrim((string)$oldPdfPath, '/');
    if (is_file($oldFull)) {
        @unlink($oldFull);
    }
}

header('Location: index.php?id=' . $id . '&mode=' . ($editId ? 'updated' : 'created'));
exit;
