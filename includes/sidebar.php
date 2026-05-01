<?php
$activePage = basename($_SERVER['PHP_SELF'] ?? '');
$role = $user['role'] ?? '';
$dashboardPath = dashboard_for_role($role);

$navItems = [
    ['href' => $dashboardPath, 'label' => 'لوحة التحكم', 'icon' => 'grid', 'roles' => ['project_owner', 'funder', 'supplier', 'admin']],
    ['href' => 'pages/profile.php', 'label' => 'الملف الشخصي', 'icon' => 'user', 'roles' => ['project_owner', 'funder', 'supplier', 'admin']],
    ['href' => 'create-project.php', 'label' => 'إنشاء مشروع', 'icon' => 'plus', 'roles' => ['project_owner']],
    ['href' => 'marketplace.php', 'label' => 'استعراض المشاريع', 'icon' => 'market', 'roles' => ['project_owner', 'funder', 'admin']],
    ['href' => 'wallet.php', 'label' => 'المحفظة', 'icon' => 'wallet', 'roles' => ['project_owner']],
    ['href' => 'qr-payment.php', 'label' => 'الدفع عبر QR', 'icon' => 'qr', 'roles' => ['project_owner']],
    ['href' => 'tracking.php', 'label' => 'تقارير الشفافية', 'icon' => 'chart', 'roles' => ['project_owner', 'funder', 'admin']],
    ['href' => 'pages/notifications.php', 'label' => 'الإشعارات', 'icon' => 'bell', 'roles' => ['project_owner', 'funder', 'supplier', 'admin']],
    ['href' => 'about.php', 'label' => 'من نحن', 'icon' => 'user', 'roles' => ['project_owner', 'funder', 'supplier', 'admin']],
    ['href' => 'pages/reports.php', 'label' => 'التقارير', 'icon' => 'chart-line', 'roles' => ['admin']],
    ['href' => 'pages/dashboard-admin.php', 'label' => 'إدارة المنصة', 'icon' => 'shield', 'roles' => ['admin']],
];
?>

<aside class="sidebar" id="sidebar">
    <a class="brand sidebar-brand" href="<?= e(url($dashboardPath)) ?>">
        <?= logo_img('sidebar-logo') ?>
        <span class="brand-text">
            <strong>إثمار</strong>
            <small>منصة تمويل موثوقة</small>
        </span>
    </a>

    <div class="sidebar-slogan">نمول اليوم… لنُثمر غدًا</div>

    <nav class="sidebar-nav">
        <?php foreach ($navItems as $item): ?>
            <?php if (!in_array($role, $item['roles'], true)) {
                continue;
            } ?>
            <?php
            $itemBase = basename($item['href']);
            $isActive = ($activePage === $itemBase) || (strpos($item['href'], $activePage) !== false);
            ?>
            <a class="<?= $isActive ? 'active' : '' ?>" href="<?= e(url($item['href'])) ?>">
                <span class="nav-icon icon-<?= e($item['icon']) ?>"></span>
                <?= e($item['label']) ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="sidebar-note">
        <strong>Demo Flow</strong>
        <span>مشروع → مراجعة → تمويل → محفظة → QR → شفافية</span>
    </div>

    <a class="logout-link" href="<?= e(url('logout.php')) ?>">تسجيل الخروج</a>
</aside>
