<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'save') {
        $id = isset($_POST['id']) && ctype_digit((string)$_POST['id']) ? (int)$_POST['id'] : null;
        $name = trim((string)($_POST['name'] ?? ''));
        $role = (string)($_POST['role'] ?? 'receiver');
        $cmrText = trim((string)($_POST['cmr_text'] ?? ''));

        if (!in_array($role, ['sender', 'receiver', 'both'], true)) {
            $role = 'receiver';
        }

        if ($name !== '' && $cmrText !== '') {
            if ($id) {
                $stmt = $pdo->prepare('UPDATE companies SET name = :name, role = :role, cmr_text = :cmr_text WHERE id = :id');
                $stmt->execute(['id' => $id, 'name' => $name, 'role' => $role, 'cmr_text' => $cmrText]);
            } else {
                $stmt = $pdo->prepare('INSERT INTO companies (name, role, cmr_text) VALUES (:name, :role, :cmr_text)');
                $stmt->execute(['name' => $name, 'role' => $role, 'cmr_text' => $cmrText]);
            }
        }
    }

    if ($action === 'delete' && isset($_POST['id']) && ctype_digit((string)$_POST['id'])) {
        $stmt = $pdo->prepare('DELETE FROM companies WHERE id = :id');
        $stmt->execute(['id' => (int)$_POST['id']]);
    }

    header('Location: admin.php');
    exit;
}

$companies = $pdo->query('SELECT * FROM companies ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="nl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>CMR Administratie</title>
  <link rel="stylesheet" href="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>assets/app.css">
</head>
<body>
<div class="container">
  <div class="topbar">
    <div class="brand">CMR Administratie</div>
    <div class="menu">
      <a class="btn" href="index.php">Invullen</a>
      <a class="btn" href="history.php">Archief</a>
      <a class="btn" href="admin.php">Admin</a>
    </div>
  </div>

  <div class="card" style="margin-bottom:14px;">
    <h2 style="margin:0 0 10px 0; font-size:18px;">Nieuw bedrijf</h2>
    <form method="post" class="row">
      <input type="hidden" name="action" value="save">
      <div>
        <label>Bedrijfsnaam</label>
        <input name="name" required>
      </div>
      <div>
        <label>Rol</label>
        <select name="role">
          <option value="sender">Afzender</option>
          <option value="receiver" selected>Ontvanger</option>
          <option value="both">Beide</option>
        </select>
      </div>
      <div style="grid-column: 1 / -1;">
        <label>CMR tekst</label>
        <textarea name="cmr_text" placeholder="Naam&#10;Straat + nummer&#10;Postcode Plaats&#10;Land" required></textarea>
      </div>
      <div>
        <button class="btn primary" type="submit">Opslaan</button>
      </div>
    </form>
  </div>

  <div class="card">
    <h2 style="margin:0 0 10px 0; font-size:18px;">Ontvangers en verzenders</h2>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Naam</th>
            <th>Rol</th>
            <th>CMR tekst</th>
            <th style="width:90px;">Acties</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($companies as $company): ?>
            <tr>
              <td>
                <input form="save-<?= (int)$company['id'] ?>" name="name" value="<?= h($company['name']) ?>" required>
              </td>
              <td>
                <select form="save-<?= (int)$company['id'] ?>" name="role">
                  <option value="sender" <?= $company['role'] === 'sender' ? 'selected' : '' ?>>Afzender</option>
                  <option value="receiver" <?= $company['role'] === 'receiver' ? 'selected' : '' ?>>Ontvanger</option>
                  <option value="both" <?= $company['role'] === 'both' ? 'selected' : '' ?>>Beide</option>
                </select>
              </td>
              <td>
                <textarea form="save-<?= (int)$company['id'] ?>" name="cmr_text" required><?= h($company['cmr_text']) ?></textarea>
              </td>
              <td>
                <div class="actions" style="margin:0;">
                  <form id="save-<?= (int)$company['id'] ?>" method="post" style="display:inline;">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="id" value="<?= (int)$company['id'] ?>">
                    <button class="btn icon" type="submit" title="Bewerken / opslaan" aria-label="Bewerken / opslaan">
                      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                    </button>
                  </form>
                  <form method="post" style="display:inline;" onsubmit="return confirm('Weet je het zeker?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= (int)$company['id'] ?>">
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
