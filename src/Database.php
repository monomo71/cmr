<?php

declare(strict_types=1);

namespace App;

use PDO;
use RuntimeException;

final class Database
{
    private PDO $pdo;

    public function __construct(array $config)
    {
        $this->pdo = self::connect($config);
        self::migrate($this->pdo, (string)($config['driver'] ?? 'sqlite'));
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    public static function connect(array $config): PDO
    {
        $driver = (string)($config['driver'] ?? 'sqlite');

        if ($driver === 'mysql') {
            $host = (string)($config['host'] ?? '127.0.0.1');
            $port = (string)($config['port'] ?? '3306');
            $dbName = (string)($config['database'] ?? '');
            $charset = (string)($config['charset'] ?? 'utf8mb4');

            if ($dbName === '') {
                throw new RuntimeException('MySQL database naam ontbreekt.');
            }

            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                $host,
                $port,
                $dbName,
                $charset
            );
            $pdo = new PDO(
                $dsn,
                (string)($config['username'] ?? ''),
                (string)($config['password'] ?? ''),
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
            return $pdo;
        }

        $sqlitePath = (string)($config['path'] ?? '');
        if ($sqlitePath === '') {
            throw new RuntimeException('SQLite pad ontbreekt.');
        }

        $dir = dirname($sqlitePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $pdo = new PDO('sqlite:' . $sqlitePath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec('PRAGMA foreign_keys = ON');
        return $pdo;
    }

    public static function migrate(PDO $pdo, ?string $driver = null): void
    {
        $driver = $driver ?: $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'mysql') {
            $pdo->exec(
                'CREATE TABLE IF NOT EXISTS companies (
                    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    role VARCHAR(20) NOT NULL DEFAULT "receiver",
                    cmr_text TEXT NOT NULL,
                    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
            );

            $pdo->exec(
                'CREATE TABLE IF NOT EXISTS cmr_documents (
                    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    field1 TEXT NULL,
                    field2 TEXT NULL,
                    field3 TEXT NULL,
                    field4 TEXT NULL,
                    field5 TEXT NULL,
                    field13 TEXT NULL,
                    field14 TEXT NULL,
                    field15 TEXT NULL,
                    field16 TEXT NULL,
                    field17 TEXT NULL,
                    field18 TEXT NULL,
                    field19 TEXT NULL,
                    field20 TEXT NULL,
                    field21 TEXT NULL,
                    field22 TEXT NULL,
                    field23 TEXT NULL,
                    field24 TEXT NULL,
                    items_json LONGTEXT NOT NULL,
                    pdf_path TEXT NULL,
                    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
            );

            return;
        }

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS companies (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                role TEXT NOT NULL DEFAULT "receiver",
                cmr_text TEXT NOT NULL,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
            )'
        );

        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS cmr_documents (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                field1 TEXT,
                field2 TEXT,
                field3 TEXT,
                field4 TEXT,
                field5 TEXT,
                field13 TEXT,
                field14 TEXT,
                field15 TEXT,
                field16 TEXT,
                field17 TEXT,
                field18 TEXT,
                field19 TEXT,
                field20 TEXT,
                field21 TEXT,
                field22 TEXT,
                field23 TEXT,
                field24 TEXT,
                items_json TEXT NOT NULL,
                pdf_path TEXT,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
            )'
        );

        $pdo->exec(
            'CREATE TRIGGER IF NOT EXISTS trg_companies_updated_at
            AFTER UPDATE ON companies
            BEGIN
              UPDATE companies SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
            END'
        );
    }
}
