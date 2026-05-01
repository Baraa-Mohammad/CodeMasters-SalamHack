<?php
declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function app_base(): string
{
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $marker = '/ithmar';
    $position = strpos($script, $marker);

    if ($position !== false) {
        return substr($script, 0, $position + strlen($marker));
    }

    $base = rtrim(str_replace('\\', '/', dirname($script)), '/');
    return ($base === '/' || $base === '.') ? '' : $base;
}

function url(string $path = ''): string
{
    return app_base() . '/' . ltrim($path, '/');
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash(): ?array
{
    if (empty($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $flash;
}

function dashboard_for_role(string $role): string
{
    return [
        'project_owner' => 'pages/dashboard-owner.php',
        'funder' => 'pages/dashboard-funder.php',
        'supplier' => 'pages/dashboard-supplier.php',
        'admin' => 'pages/dashboard-admin.php',
    ][$role] ?? 'login.php';
}

function role_label(string $role): string
{
    return [
        'project_owner' => 'صاحب/صاحبة مشروع',
        'funder' => 'ممول / مؤسسة',
        'supplier' => 'مورد',
        'admin' => 'إدارة المنصة',
    ][$role] ?? $role;
}

function status_label(string $status): string
{
    return [
        'pending' => 'قيد المراجعة',
        'approved' => 'مقبول',
        'rejected' => 'مرفوض',
        'funded' => 'ممول',
    ][$status] ?? $status;
}

function risk_label(string $risk): string
{
    return [
        'low' => 'منخفض',
        'medium' => 'متوسط',
        'high' => 'مرتفع',
    ][$risk] ?? $risk;
}

function money(float|string|null $amount): string
{
    return number_format((float) $amount, 0) . ' شيكل';
}

function progress_percent(float|string $current, float|string $goal): int
{
    $goal = max((float) $goal, 1);
    return (int) min(100, round(((float) $current / $goal) * 100));
}

function excerpt(string $text, int $limit = 135): string
{
    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        return mb_strlen($text, 'UTF-8') > $limit ? mb_substr($text, 0, $limit, 'UTF-8') . '…' : $text;
    }

    return strlen($text) > $limit ? substr($text, 0, $limit) . '…' : $text;
}

function status_class(string $status): string
{
    return 'status-' . preg_replace('/[^a-z_]/', '', $status);
}

function risk_class(string $risk): string
{
    return 'risk-' . preg_replace('/[^a-z_]/', '', $risk);
}

function logo_img(string $class = 'brand-logo'): string
{
    return '<img class="' . e($class) . '" src="' . e(url('assets/images/logo.png')) . '" alt="شعار إثمار">';
}

function can_view_project(array $user, array $project): bool
{
    if ($user['role'] === 'admin') return true;
    if ($project['status'] === 'approved' || $project['status'] === 'funded') return true;
    if ($user['role'] === 'project_owner' && (int)$project['owner_id'] === (int)$user['id']) return true;
    return false;
}

function get_user_projects_count(int $userId, string $role): int
{
    $status = $role === 'project_owner' ? "WHERE owner_id = $userId" : '';
    $stmt = db()->prepare("SELECT COUNT(*) cnt FROM projects {$status}");
    $stmt->execute();
    return (int)$stmt->fetch()['cnt'];
}

function get_funding_status(array $project): string
{
    $percent = progress_percent($project['current_funding'], $project['funding_goal']);
    if ($percent >= 100) return 'مكتمل';
    if ($percent >= 75) return 'قريب الإنجاز';
    if ($percent >= 50) return 'منتصف الطريق';
    if ($percent >= 25) return 'بدأ التمويل';
    return 'جديد';
}

function category_icon(string $category): string
{
    $icons = [
        'غذاء' => '🍽️',
        'حرف يدوية' => '🎨',
        'منتجات طبيعية' => '🌿',
        'خياطة' => '✂️',
        'تكنولوجيا' => '💻',
        'خدمات' => '🛠️',
        'تجارة' => '🏪',
        'تعليم' => '📚',
    ];
    return $icons[$category] ?? '💼';
}
