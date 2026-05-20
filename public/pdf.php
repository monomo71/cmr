<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

if (!isset($_GET['id']) || !ctype_digit((string)$_GET['id'])) {
    http_response_code(400);
    echo 'Ongeldig document ID';
    exit;
}

$stmt = $pdo->prepare('SELECT id, pdf_path FROM cmr_documents WHERE id = :id');
$stmt->execute(['id' => (int)$_GET['id']]);
$record = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$record || empty($record['pdf_path'])) {
    http_response_code(404);
    echo 'PDF niet gevonden';
    exit;
}

$fullPath = ROOT_PATH . '/' . ltrim((string)$record['pdf_path'], '/');
if (!is_file($fullPath)) {
    http_response_code(404);
    echo 'Bestand niet gevonden op schijf';
    exit;
}

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="cmr-' . (int)$record['id'] . '.pdf"');
header('Content-Length: ' . (string)filesize($fullPath));
readfile($fullPath);
