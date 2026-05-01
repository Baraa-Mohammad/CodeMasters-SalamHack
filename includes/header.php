<?php
require_once __DIR__ . '/auth.php';

$pageTitle = $pageTitle ?? 'إثمار';
$layout = $layout ?? 'dashboard';
$flash = get_flash();
$user = current_user();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#1F7A5C">
    <title><?= e($pageTitle) ?> | إثمار</title>
    <link rel="icon" type="image/png" href="<?= e(url('assets/images/logo.png')) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&family=Tajawal:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
    <link rel="stylesheet" href="<?= e(url('assets/css/dashboard.css')) ?>">
    <link rel="stylesheet" href="<?= e(url('assets/css/responsive.css')) ?>">
</head>
<body class="<?= e($layout) ?>-body">
<?php if ($layout === 'landing'): ?>
    <header class="landing-nav">
        <a class="brand" href="<?= e(url('index.php')) ?>">
            <?= logo_img('brand-logo') ?>
            <span class="brand-text">
                <strong>إثمار</strong>
                <small>نمول اليوم… لنُثمر غدًا</small>
            </span>
        </a>
        <nav class="nav-links">
            <a href="<?= e(url('index.php#home')) ?>">الرئيسية</a>
            <a href="<?= e(url('index.php#how')) ?>">كيف يعمل</a>
            <a href="<?= e(url('marketplace.php')) ?>">المشاريع</a>
            <a href="<?= e(url('about.php')) ?>">من نحن</a>
            <?php if ($user): ?>
                <a href="<?= e(url(dashboard_for_role($user['role']))) ?>">لوحة التحكم</a>
            <?php else: ?>
                <a href="<?= e(url('login.php')) ?>">تسجيل الدخول</a>
            <?php endif; ?>
        </nav>
        <?php if ($user): ?>
            <a class="btn btn-primary" href="<?= e(url('logout.php')) ?>">تسجيل الخروج</a>
        <?php else: ?>
            <a class="btn btn-primary" href="<?= e(url('signup.php')) ?>">ابدأ الآن</a>
        <?php endif; ?>
    </header>
<?php elseif ($layout === 'dashboard'): ?>
    <div class="app-shell">
        <?php include __DIR__ . '/sidebar.php'; ?>
        <main class="main-content">
            <div class="topbar">
                <button class="icon-btn sidebar-toggle" type="button" aria-label="فتح القائمة">☰</button>
                <a class="topbar-brand" href="<?= e(url(dashboard_for_role($user['role'] ?? ''))) ?>">
                    <?= logo_img('topbar-logo') ?>
                </a>
                <div class="page-heading">
                    <p class="eyebrow">إثمار | منصة تمويل ذكية</p>
                    <h1><?= e($pageTitle) ?></h1>
                </div>
                <div class="user-pill">
                    <span><?= e($user['full_name'] ?? 'زائر') ?></span>
                    <small><?= e(isset($user['role']) ? role_label($user['role']) : 'غير مسجل') ?></small>
                </div>
            </div>
<?php endif; ?>

<?php if ($flash): ?>
    <div class="flash flash-<?= e($flash['type']) ?>">
        <?= e($flash['message']) ?>
    </div>
<?php endif; ?>
