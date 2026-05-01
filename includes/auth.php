<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

function current_user(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    static $user = null;
    if ($user !== null && (int) $user['id'] === (int) $_SESSION['user_id']) {
        return $user;
    }

    $stmt = db()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([(int) $_SESSION['user_id']]);
    $user = $stmt->fetch() ?: null;

    return $user;
}

function require_login(): array
{
    $user = current_user();
    if (!$user) {
        flash('error', 'يرجى تسجيل الدخول أولاً للمتابعة.');
        redirect('login.php');
    }

    return $user;
}

function require_role(array $roles): array
{
    $user = require_login();
    if (!in_array($user['role'], $roles, true)) {
        flash('error', 'هذه الصفحة مخصصة لنوع حساب مختلف.');
        redirect(dashboard_for_role($user['role']));
    }

    return $user;
}
