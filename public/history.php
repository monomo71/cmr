<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = (string)($_POST['action'] ?? '');
    if ($action === 'delete' && isset($_POST['id']) && ctype_digit((string)$_POST['id'])) {
        $id = (int)$_POST['id'];
        $stmt = $pdo->prepare('SELECT pdf_path FROM cmr_documents WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

        $delete = $pdo->prepare('DELETE FROM cmr_documents WHERE id = :id');
        $delete->execute(['id' => $id]);

        if ($row && !empty($row['pdf_path'])) {
            $fullPath = ROOT_PATH . '/' . ltrim((string)$row['pdf_path'], '/');
            if (is_file($fullPath)) {
                @unlink($fullPath);
            }
        }
    }

    header('Location: history.php');
    exit;
}

$q = trim((string)($_GET['q'] ?? ''));
$params = [];
$sql = 'SELECT id, field2, field24, pdf_path, created_at FROM cmr_documents';
if ($q !== '') {
    $sql .= ' WHERE field2 LIKE :q OR field24 LIKE :q';
    $params['q'] = '%' . $q . '%';
}
$sql .= ' ORDER BY id DESC LIMIT 500';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="nl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>CMR Archief</title>
  <link rel="stylesheet" href="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>assets/app.css">
</head>
<body>
<div class="container">
  <div class="topbar">
    <div class="brand">CMR Archief</div>
    <div class="menu">
      <a class="btn" href="index.php">Invullen</a>
      <a class="btn" href="admin.php">Admin</a>
      <a class="btn" href="history.php">Archief</a>
    </div>
  </div>

  <div class="card">
    <form method="get" class="actions">
      <input name="q" value="<?= h($q) ?>" placeholder="Zoek op ontvanger of datum" style="max-width: 360px;">
      <button class="btn" type="submit">Zoeken</button>
    </form>

    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Ontvanger</th>
            <th>Datum veld 24</th>
            <th>Aangemaakt</th>
            <th>Bestandsnaam</th>
            <th style="width:130px;">Acties</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
          <tr>
            <td><?= (int)$row['id'] ?></td>
            <td><?= h(extractReceiverName((string)$row['field2'])) ?></td>
            <td><?= h(extractDateFromField24((string)$row['field24'])) ?></td>
            <td><?= h((string)$row['created_at']) ?></td>
            <td><?= h(basename((string)$row['pdf_path'])) ?></td>
            <td>
              <div class="actions" style="margin:0;">
                <a class="btn icon" href="pdf.php?id=<?= (int)$row['id'] ?>" target="_blank" title="PDF openen" aria-label="PDF openen">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
                </a>
                <a class="btn icon" href="index.php?edit=<?= (int)$row['id'] ?>" title="Opnieuw bewerken" aria-label="Opnieuw bewerken">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                </a>
                <form method="post" style="display:inline;" onsubmit="return confirm('Document echt verwijderen?');">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                  <button class="btn icon danger" type="submit" title="Verwijderen" aria-label="Verwijderen">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
                  </button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
</body>
</html>
