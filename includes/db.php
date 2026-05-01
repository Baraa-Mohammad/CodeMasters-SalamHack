<?php
declare(strict_types=1);

const DB_HOST = 'localhost';
const DB_NAME = 'ithmar_db';
const DB_USER = 'root';
const DB_PASS = '';

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $server = new PDO('mysql:host=' . DB_HOST . ';charset=utf8mb4', DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $server->exec('CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

    $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    ensure_schema_is_current($pdo);

    return $pdo;
}

function ensure_schema_is_current(PDO $pdo): void
{
    create_core_tables_if_missing($pdo);
    normalize_core_column_types($pdo);

    $columns = [
        ['users', 'phone', "ALTER TABLE users ADD COLUMN phone VARCHAR(50) NULL AFTER email"],
        ['users', 'created_at', "ALTER TABLE users ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP"],
        ['projects', 'purpose', "ALTER TABLE projects ADD COLUMN purpose VARCHAR(100) NULL AFTER current_funding"],
        ['projects', 'category', "ALTER TABLE projects ADD COLUMN category VARCHAR(100) NULL AFTER purpose"],
        ['projects', 'city', "ALTER TABLE projects ADD COLUMN city VARCHAR(100) NULL AFTER category"],
        ['projects', 'impact_summary', "ALTER TABLE projects ADD COLUMN impact_summary TEXT NULL AFTER city"],
        ['projects', 'status', "ALTER TABLE projects ADD COLUMN status ENUM('pending','approved','rejected','funded') DEFAULT 'pending' AFTER impact_summary"],
        ['projects', 'risk_score', "ALTER TABLE projects ADD COLUMN risk_score ENUM('low','medium','high') DEFAULT 'low' AFTER status"],
        ['wallets', 'created_at', "ALTER TABLE wallets ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP"],
        ['suppliers', 'business_name', "ALTER TABLE suppliers ADD COLUMN business_name VARCHAR(255) NULL AFTER user_id"],
        ['suppliers', 'category', "ALTER TABLE suppliers ADD COLUMN category VARCHAR(100) NULL AFTER business_name"],
        ['suppliers', 'qr_code', "ALTER TABLE suppliers ADD COLUMN qr_code VARCHAR(255) NULL AFTER category"],
        ['qr_payments', 'category', "ALTER TABLE qr_payments ADD COLUMN category VARCHAR(100) NULL AFTER amount"],
        ['qr_payments', 'created_at', "ALTER TABLE qr_payments ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP"],
        ['spending_tracking', 'description', "ALTER TABLE spending_tracking ADD COLUMN description TEXT NULL AFTER payment_id"],
        ['spending_tracking', 'category', "ALTER TABLE spending_tracking ADD COLUMN category VARCHAR(100) NULL AFTER amount"],
        ['spending_tracking', 'created_at', "ALTER TABLE spending_tracking ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP"],
    ];

    foreach ($columns as [$table, $column, $sql]) {
        if (table_exists($pdo, $table) && !column_exists($pdo, $table, $column)) {
            $pdo->exec($sql);
        }
    }

    if (table_exists($pdo, 'spending_tracking') && column_exists($pdo, 'spending_tracking', 'category') && table_exists($pdo, 'qr_payments')) {
        $pdo->exec(
            "UPDATE spending_tracking st
             JOIN qr_payments qp ON qp.id = st.payment_id
             SET st.category = qp.category
             WHERE (st.category IS NULL OR st.category = '') AND qp.category IS NOT NULL"
        );
    }
}

function normalize_core_column_types(PDO $pdo): void
{
    if (table_exists($pdo, 'users') && column_exists($pdo, 'users', 'role')) {
        $pdo->exec("ALTER TABLE users MODIFY COLUMN role VARCHAR(50) NOT NULL DEFAULT 'project_owner'");
        $pdo->exec("UPDATE users SET role = 'project_owner' WHERE role IN ('owner', '') OR role IS NULL");
        $pdo->exec("UPDATE users SET role = 'funder' WHERE role NOT IN ('project_owner', 'funder', 'supplier', 'admin')");
        $pdo->exec("ALTER TABLE users MODIFY COLUMN role ENUM('project_owner','funder','supplier','admin') NOT NULL DEFAULT 'project_owner'");
    }

    if (table_exists($pdo, 'projects') && column_exists($pdo, 'projects', 'status')) {
        $pdo->exec("ALTER TABLE projects MODIFY COLUMN status ENUM('pending','approved','rejected','funded') NOT NULL DEFAULT 'pending'");
    }

    if (table_exists($pdo, 'projects') && column_exists($pdo, 'projects', 'risk_score')) {
        $pdo->exec("ALTER TABLE projects MODIFY COLUMN risk_score ENUM('low','medium','high') NOT NULL DEFAULT 'low'");
    }
}

function create_core_tables_if_missing(PDO $pdo): void
{
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            full_name VARCHAR(255),
            email VARCHAR(255) UNIQUE,
            phone VARCHAR(50),
            password VARCHAR(255),
            role ENUM('project_owner','funder','supplier','admin'),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS projects (
            id INT AUTO_INCREMENT PRIMARY KEY,
            owner_id INT,
            title VARCHAR(255),
            description TEXT,
            funding_goal DECIMAL(10,2),
            current_funding DECIMAL(10,2) DEFAULT 0,
            purpose VARCHAR(100),
            category VARCHAR(100),
            city VARCHAR(100),
            impact_summary TEXT,
            status ENUM('pending','approved','rejected','funded') DEFAULT 'pending',
            risk_score ENUM('low','medium','high') DEFAULT 'low',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS wallets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            balance DECIMAL(10,2) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS funding_transactions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            project_id INT,
            funder_id INT,
            amount DECIMAL(10,2),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS suppliers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            business_name VARCHAR(255),
            category VARCHAR(100),
            qr_code VARCHAR(255)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS qr_payments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            project_id INT,
            owner_id INT,
            supplier_id INT,
            amount DECIMAL(10,2),
            category VARCHAR(100),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS spending_tracking (
            id INT AUTO_INCREMENT PRIMARY KEY,
            project_id INT,
            payment_id INT,
            description TEXT,
            amount DECIMAL(10,2),
            category VARCHAR(100),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
}

function table_exists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?');
    $stmt->execute([$table]);
    return (int) $stmt->fetchColumn() > 0;
}

function column_exists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
    $stmt->execute([$table, $column]);
    return (int) $stmt->fetchColumn() > 0;
}

function ensure_wallet(int $userId): void
{
    $stmt = db()->prepare('SELECT id FROM wallets WHERE user_id = ? LIMIT 1');
    $stmt->execute([$userId]);

    if (!$stmt->fetch()) {
        $insert = db()->prepare('INSERT INTO wallets (user_id, balance) VALUES (?, 0)');
        $insert->execute([$userId]);
    }
}

function calculate_risk_score(float $amount, string $purpose): string
{
    if ($amount >= 30000) {
        return 'high';
    }

    $normalizedPurpose = trim($purpose);
    if ($amount >= 18000 || in_array($normalizedPurpose, ['معدات', 'equipment'], true)) {
        return 'medium';
    }

    return 'low';
}
