<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Database.php';

use App\Database;

const ROOT_PATH = __DIR__ . '/..';

$configPath = ROOT_PATH . '/config.php';
$existingConfig = is_file($configPath) ? require $configPath : null;

$values = [
    'driver' => (string)($existingConfig['driver'] ?? 'sqlite'),
    'sqlite_path' => (string)($existingConfig['path'] ?? ROOT_PATH . '/data/cmr.sqlite'),
    'mysql_host' => (string)($existingConfig['host'] ?? '127.0.0.1'),
    'mysql_port' => (string)($existingConfig['port'] ?? '3306'),
    'mysql_database' => (string)($existingConfig['database'] ?? ''),
    'mysql_username' => (string)($existingConfig['username'] ?? ''),
    'mysql_password' => (string)($existingConfig['password'] ?? ''),
    'mysql_charset' => (string)($existingConfig['charset'] ?? 'utf8mb4'),
];

$error = '';
$success = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $values['driver'] = (string)($_POST['driver'] ?? 'sqlite');
    $values['sqlite_path'] = trim((string)($_POST['sqlite_path'] ?? ROOT_PATH . '/data/cmr.sqlite'));
    $values['mysql_host'] = trim((string)($_POST['mysql_host'] ?? '127.0.0.1'));
    $values['mysql_port'] = trim((string)($_POST['mysql_port'] ?? '3306'));
    $values['mysql_database'] = trim((string)($_POST['mysql_database'] ?? ''));
    $values['mysql_username'] = trim((string)($_POST['mysql_username'] ?? ''));
    $values['mysql_password'] = (string)($_POST['mysql_password'] ?? '');
    $values['mysql_charset'] = trim((string)($_POST['mysql_charset'] ?? 'utf8mb4'));

    try {
        if ($values['driver'] === 'mysql') {
            if ($values['mysql_database'] === '') {
                throw new RuntimeException('MySQL database naam is verplicht.');
            }

            $config = [
                'driver' => 'mysql',
                'host' => $values['mysql_host'],
                'port' => $values['mysql_port'],
                'database' => $values['mysql_database'],
                'username' => $values['mysql_username'],
                'password' => $values['mysql_password'],
                'charset' => $values['mysql_charset'] ?: 'utf8mb4',
            ];
        } else {
            if ($values['sqlite_path'] === '') {
                throw new RuntimeException('SQLite pad is verplicht.');
            }

            $config = [
                'driver' => 'sqlite',
                'path' => $values['sqlite_path'],
            ];
        }

        $pdo = Database::connect($config);
        Database::migrate($pdo, $config['driver']);

        $configContent = "<?php\n\nreturn " . var_export($config, true) . ";\n";
        if (@file_put_contents($configPath, $configContent) === false) {
            throw new RuntimeException('Kon config.php niet schrijven. Controleer schrijfrechten op de projectmap.');
        }

        $success = 'Installatie geslaagd. Config opgeslagen en tabellen aangemaakt.';
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

function h(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="nl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>CMR Setup</title>
  <link rel="stylesheet" href="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>assets/app.css">
</head>
<body>
<div class="container">
  <div class="topbar">
    <div class="brand">CMR Setup</div>
    <div class="menu">
      <a class="btn" href="index.php">Invullen</a>
      <a class="btn" href="admin.php">Admin</a>
      <a class="btn" href="history.php">Archief</a>
    </div>
  </div>

  <div class="card">
    <p style="margin-top:0; color:#475569;">Vul hieronder je databasegegevens in. Na opslaan wordt <code>config.php</code> aangemaakt en worden de tabellen automatisch geinstalleerd.</p>
    <div class="notice">PDF generatie gebruikt nu alleen PHP (geen Python/pip nodig).</div>

    <?php if ($error !== ''): ?>
      <div class="notice" style="border-color:#fecaca;background:#fef2f2;color:#991b1b;">
        <?= h($error) ?>
      </div>
    <?php endif; ?>

    <?php if ($success !== ''): ?>
      <div class="notice">
        <?= h($success) ?>
        <div style="margin-top:8px;"><a class="btn primary" href="index.php">Naar invultool</a></div>
      </div>
    <?php endif; ?>

    <form method="post">
      <div class="row">
        <div>
          <label>Database type</label>
          <select name="driver" id="driver">
            <option value="sqlite" <?= $values['driver'] === 'sqlite' ? 'selected' : '' ?>>SQLite (lokaal bestand)</option>
            <option value="mysql" <?= $values['driver'] === 'mysql' ? 'selected' : '' ?>>MySQL / MariaDB</option>
          </select>
        </div>
      </div>

      <div id="sqlite-fields" style="margin-top:12px; <?= $values['driver'] === 'sqlite' ? '' : 'display:none;' ?>">
        <label>SQLite pad</label>
        <input name="sqlite_path" value="<?= h($values['sqlite_path']) ?>" placeholder="/var/www/project/data/cmr.sqlite">
      </div>

      <div id="mysql-fields" style="margin-top:12px; <?= $values['driver'] === 'mysql' ? '' : 'display:none;' ?>">
        <div class="row">
          <div>
            <label>MySQL host</label>
            <input name="mysql_host" value="<?= h($values['mysql_host']) ?>" placeholder="127.0.0.1">
          </div>
          <div>
            <label>MySQL poort</label>
            <input name="mysql_port" value="<?= h($values['mysql_port']) ?>" placeholder="3306">
          </div>
          <div>
            <label>Database naam</label>
            <input name="mysql_database" value="<?= h($values['mysql_database']) ?>">
          </div>
          <div>
            <label>Gebruiker</label>
            <input name="mysql_username" value="<?= h($values['mysql_username']) ?>">
          </div>
          <div>
            <label>Wachtwoord</label>
            <input type="password" name="mysql_password" value="<?= h($values['mysql_password']) ?>">
          </div>
          <div>
            <label>Charset</label>
            <input name="mysql_charset" value="<?= h($values['mysql_charset']) ?>" placeholder="utf8mb4">
          </div>
        </div>
      </div>

      <div class="actions">
        <button class="btn primary" type="submit">Installeren / Bijwerken</button>
      </div>
    </form>
  </div>
</div>

<script>
  const driverSelect = document.getElementById('driver');
  const sqliteFields = document.getElementById('sqlite-fields');
  const mysqlFields = document.getElementById('mysql-fields');

  function toggleDbFields() {
    const mode = driverSelect.value;
    sqliteFields.style.display = mode === 'sqlite' ? '' : 'none';
    mysqlFields.style.display = mode === 'mysql' ? '' : 'none';
  }

  driverSelect.addEventListener('change', toggleDbFields);
  toggleDbFields();
</script>
</body>
</html>
