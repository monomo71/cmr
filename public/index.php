<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

$companies = $pdo->query('SELECT id, name, role, cmr_text FROM companies ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
$senders = array_values(array_filter($companies, static fn(array $c): bool => in_array($c['role'], ['sender', 'both'], true)));
$receivers = array_values(array_filter($companies, static fn(array $c): bool => in_array($c['role'], ['receiver', 'both'], true)));
$labels = cmrFieldLabels();

$record = null;
$editRecord = null;
$mode = (string)($_GET['mode'] ?? '');

if (isset($_GET['id']) && ctype_digit((string)$_GET['id'])) {
    $stmt = $pdo->prepare('SELECT * FROM cmr_documents WHERE id = :id');
    $stmt->execute(['id' => (int)$_GET['id']]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

if (isset($_GET['edit']) && ctype_digit((string)$_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM cmr_documents WHERE id = :id');
    $stmt->execute(['id' => (int)$_GET['edit']]);
    $editRecord = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

$activeRecord = $record ?: $editRecord;
$activeRecordId = $activeRecord ? (int)$activeRecord['id'] : null;

$default = [
    'field1' => '', 'field2' => '', 'field3' => '', 'field4' => '', 'field5' => '',
    'field13' => '', 'field14' => '', 'field15' => '', 'field16' => '', 'field17' => '', 'field18' => '',
    'field19' => '', 'field20' => '', 'field21' => '', 'field22' => '', 'field23' => '', 'field24' => '',
];

$formData = $default;
$initialItems = [];
if ($editRecord) {
    foreach ($default as $key => $value) {
        $formData[$key] = (string)($editRecord[$key] ?? '');
    }
    $decoded = json_decode((string)($editRecord['items_json'] ?? '[]'), true);
    $initialItems = is_array($decoded) ? normalizeItemsRows($decoded) : [];
}
?>
<!doctype html>
<html lang="nl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>CMR Invultool</title>
  <link rel="stylesheet" href="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>assets/app.css">
</head>
<body>
<div class="container">
  <div class="topbar">
    <div class="brand">CMR Invultool</div>
    <div class="menu">
      <a class="btn" href="admin.php">Admin</a>
      <?php if ($activeRecordId): ?>
        <a class="btn" href="pdf.php?id=<?= $activeRecordId ?>" target="_blank">PDF openen</a>
        <a class="btn" href="index.php?edit=<?= $activeRecordId ?>">Opnieuw bewerken</a>
      <?php else: ?>
        <button class="btn" type="button" disabled>PDF openen</button>
        <button class="btn" type="button" disabled>Opnieuw bewerken</button>
      <?php endif; ?>
      <a class="btn" href="history.php">Archief</a>
    </div>
  </div>

  <div class="card">
    <?php if ($record): ?>
      <div class="notice">
        <?= $mode === 'updated' ? 'CMR bijgewerkt' : 'CMR opgeslagen' ?> als document #<?= (int)$record['id'] ?>.
      </div>
    <?php endif; ?>

    <?php if ($editRecord): ?>
      <div class="notice">
        Je bewerkt nu document #<?= (int)$editRecord['id'] ?>.
      </div>
    <?php endif; ?>

    <form method="post" action="save_cmr.php">
      <?php if ($editRecord): ?>
        <input type="hidden" name="edit_id" value="<?= (int)$editRecord['id'] ?>">
      <?php endif; ?>

      <div class="row">
        <div>
          <label for="sender_select">Afzender kiezen (veld 1)</label>
          <select id="sender_select">
            <option value="">Handmatig invullen</option>
            <?php foreach ($senders as $company): ?>
              <option value="<?= (int)$company['id'] ?>" data-cmr="<?= h($company['cmr_text']) ?>"><?= h($company['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label for="receiver_select">Ontvanger kiezen (veld 2)</label>
          <select id="receiver_select">
            <option value="">Handmatig invullen</option>
            <?php foreach ($receivers as $company): ?>
              <option value="<?= (int)$company['id'] ?>" data-cmr="<?= h($company['cmr_text']) ?>"><?= h($company['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="row">
        <div class="single"><label><?= h($labels['field1']) ?></label><textarea name="field1" id="field1" required><?= h($formData['field1']) ?></textarea></div>
        <div class="single"><label><?= h($labels['field2']) ?></label><textarea name="field2" id="field2" required><?= h($formData['field2']) ?></textarea></div>
        <div class="single"><label><?= h($labels['field3']) ?></label><textarea name="field3"><?= h($formData['field3']) ?></textarea></div>
        <div class="single"><label><?= h($labels['field4']) ?></label><textarea name="field4"><?= h($formData['field4']) ?></textarea></div>
        <div class="single"><label><?= h($labels['field5']) ?></label><textarea name="field5"><?= h($formData['field5']) ?></textarea></div>
        <div class="single"><label><?= h($labels['field16']) ?></label><textarea name="field16"><?= h($formData['field16']) ?></textarea></div>
        <div class="single"><label><?= h($labels['field17']) ?></label><textarea name="field17"><?= h($formData['field17']) ?></textarea></div>
        <div class="single"><label><?= h($labels['field18']) ?></label><textarea name="field18"><?= h($formData['field18']) ?></textarea></div>
      </div>

      <label>Velden 6 t/m 12 (meerdere regels mogelijk)</label>
      <div class="table-wrap">
        <table id="items-table">
          <thead>
          <tr>
            <th><?= h($labels['field6']) ?></th>
            <th><?= h($labels['field7']) ?></th>
            <th><?= h($labels['field8']) ?></th>
            <th><?= h($labels['field9']) ?></th>
            <th><?= h($labels['field10']) ?></th>
            <th><?= h($labels['field11']) ?></th>
            <th><?= h($labels['field12']) ?></th>
            <th></th>
          </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
      <div class="actions">
        <button type="button" class="btn" id="add-row">Regel toevoegen</button>
      </div>

      <div class="row">
        <div class="single"><label><?= h($labels['field13']) ?></label><textarea name="field13"><?= h($formData['field13']) ?></textarea></div>
        <div class="single"><label><?= h($labels['field19']) ?></label><textarea name="field19"><?= h($formData['field19']) ?></textarea></div>
        <div class="single"><label><?= h($labels['field14']) ?></label><textarea name="field14"><?= h($formData['field14']) ?></textarea></div>
        <div class="single"><label><?= h($labels['field20']) ?></label><textarea name="field20"><?= h($formData['field20']) ?></textarea></div>
        <div class="single"><label><?= h($labels['field21']) ?></label><textarea name="field21"><?= h($formData['field21']) ?></textarea></div>
        <div class="single"><label><?= h($labels['field15']) ?></label><textarea name="field15"><?= h($formData['field15']) ?></textarea></div>
        <div class="single"><label><?= h($labels['field22']) ?></label><textarea name="field22"><?= h($formData['field22']) ?></textarea></div>
        <div class="single"><label><?= h($labels['field23']) ?></label><textarea name="field23"><?= h($formData['field23']) ?></textarea></div>
        <div class="single"><label><?= h($labels['field24']) ?></label><textarea name="field24"><?= h($formData['field24']) ?></textarea></div>
      </div>

      <div class="actions">
        <button class="btn primary" type="submit"><?= $editRecord ? 'Bijwerken en PDF vernieuwen' : 'Opslaan en PDF maken' ?></button>
      </div>
    </form>
  </div>
</div>

<script>
  const senderSelect = document.getElementById('sender_select');
  const receiverSelect = document.getElementById('receiver_select');
  const field1 = document.getElementById('field1');
  const field2 = document.getElementById('field2');
  const initialItems = <?= json_encode($initialItems, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

  senderSelect.addEventListener('change', () => {
    const option = senderSelect.options[senderSelect.selectedIndex];
    if (option?.dataset?.cmr) field1.value = option.dataset.cmr;
  });

  receiverSelect.addEventListener('change', () => {
    const option = receiverSelect.options[receiverSelect.selectedIndex];
    if (option?.dataset?.cmr) field2.value = option.dataset.cmr;
  });

  const tbody = document.querySelector('#items-table tbody');

  function renumberRows() {
    const rows = Array.from(tbody.querySelectorAll('tr'));
    rows.forEach((tr, rowIndex) => {
      const inputs = tr.querySelectorAll('input[data-field]');
      inputs.forEach((input) => {
        const field = input.getAttribute('data-field');
        input.name = `items[${rowIndex}][${field}]`;
      });
    });
  }

  function addRow(initial = {}) {
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td><input data-field="field6" value="${initial.field6 || ''}"></td>
      <td><input data-field="field7" value="${initial.field7 || ''}"></td>
      <td><input data-field="field8" value="${initial.field8 || ''}"></td>
      <td><input data-field="field9" value="${initial.field9 || ''}"></td>
      <td><input data-field="field10" value="${initial.field10 || ''}"></td>
      <td><input data-field="field11" value="${initial.field11 || ''}"></td>
      <td><input data-field="field12" value="${initial.field12 || ''}"></td>
      <td><button type="button" class="btn icon" title="Verwijderen">X</button></td>
    `;
    tr.querySelector('button').addEventListener('click', () => {
      tr.remove();
      renumberRows();
    });
    tbody.appendChild(tr);
    renumberRows();
  }

  document.getElementById('add-row').addEventListener('click', () => addRow());
  if (Array.isArray(initialItems) && initialItems.length > 0) {
    initialItems.forEach((item) => addRow(item || {}));
  } else {
    addRow();
  }
</script>
</body>
</html>
