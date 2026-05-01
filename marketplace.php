<?php
require_once __DIR__ . '/includes/auth.php';

$search = trim($_GET['search'] ?? '');
$category = trim($_GET['category'] ?? '');
$risk = trim($_GET['risk'] ?? '');

$where = ["p.status IN ('approved', 'funded')"];
$params = [];

if ($search !== '') {
    $where[] = '(p.title LIKE ? OR p.description LIKE ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

if ($category !== '') {
    $where[] = 'p.category = ?';
    $params[] = $category;
}

if ($risk !== '') {
    $where[] = 'p.risk_score = ?';
    $params[] = $risk;
}

$sql = 'SELECT p.*, u.full_name FROM projects p JOIN users u ON u.id = p.owner_id WHERE ' . implode(' AND ', $where) . ' ORDER BY p.created_at DESC';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$projects = $stmt->fetchAll();

$categories = db()->query("SELECT DISTINCT category FROM projects WHERE category IS NOT NULL AND category <> '' ORDER BY category")->fetchAll();

$pageTitle = 'استعراض المشاريع';
$layout = current_user() ? 'dashboard' : 'landing';
require_once __DIR__ . '/includes/header.php';
?>

<section class="panel">
    <form method="get" class="filter-bar">
        <input type="search" name="search" value="<?= e($search) ?>" placeholder="ابحث باسم المشروع">
        <select name="category">
            <option value="">كل التصنيفات</option>
            <?php foreach ($categories as $row): ?>
                <option value="<?= e($row['category']) ?>" <?= $category === $row['category'] ? 'selected' : '' ?>><?= e($row['category']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="risk">
            <option value="">كل درجات المخاطر</option>
            <option value="low" <?= $risk === 'low' ? 'selected' : '' ?>>منخفض</option>
            <option value="medium" <?= $risk === 'medium' ? 'selected' : '' ?>>متوسط</option>
            <option value="high" <?= $risk === 'high' ? 'selected' : '' ?>>مرتفع</option>
        </select>
        <button class="btn btn-primary" type="submit">تصفية</button>
    </form>
</section>

<section class="projects-grid">
    <?php foreach ($projects as $project): ?>
        <?php $percent = progress_percent($project['current_funding'], $project['funding_goal']); ?>
        <article class="project-card">
            <div class="project-thumb">
                <img src="<?= e(url('assets/images/placeholder-project.jpg')) ?>" alt="">
                <span><?= e($project['city']) ?></span>
            </div>
            <div class="project-card-head">
                <span class="badge risk-<?= e($project['risk_score']) ?>"><?= e(risk_label($project['risk_score'])) ?></span>
                <span class="badge status-<?= e($project['status']) ?>"><?= e(status_label($project['status'])) ?></span>
            </div>
            <h2><?= e($project['title']) ?></h2>
            <p class="muted">بواسطة <?= e($project['full_name']) ?> - <?= e($project['city']) ?></p>
            <p><?= e(excerpt($project['description'])) ?></p>
            <div class="card-row">
                <span><?= e($project['category']) ?></span>
                <strong><?= e(money($project['funding_goal'])) ?></strong>
            </div>
            <div class="progress"><span style="width:<?= $percent ?>%"></span></div>
            <div class="card-row muted">
                <span>تم جمع <?= e(money($project['current_funding'])) ?></span>
                <span><?= $percent ?>%</span>
            </div>
            <div class="card-actions">
                <a class="btn btn-light" href="<?= e(url('project-details.php?id=' . $project['id'])) ?>">عرض التفاصيل</a>
                <a class="btn btn-primary" href="<?= e(url('project-details.php?id=' . $project['id'] . '#fund')) ?>">تمويل</a>
            </div>
        </article>
    <?php endforeach; ?>
    <?php if (!$projects): ?>
        <div class="empty-state large">لا توجد مشاريع مطابقة للبحث الحالي.</div>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
