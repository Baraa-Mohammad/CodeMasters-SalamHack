<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_role(['project_owner']);
ensure_wallet((int) $user['id']);

$walletStmt = db()->prepare('SELECT balance FROM wallets WHERE user_id = ? LIMIT 1');
$walletStmt->execute([(int) $user['id']]);
$wallet = $walletStmt->fetch();

$projectStats = db()->prepare('SELECT COUNT(*) projects_count, COALESCE(SUM(current_funding), 0) funded_total FROM projects WHERE owner_id = ?');
$projectStats->execute([(int) $user['id']]);
$stats = $projectStats->fetch();

$spentStmt = db()->prepare('SELECT COALESCE(SUM(amount), 0) spent_total FROM qr_payments WHERE owner_id = ?');
$spentStmt->execute([(int) $user['id']]);
$spent = $spentStmt->fetch();

$projectsStmt = db()->prepare('SELECT * FROM projects WHERE owner_id = ? ORDER BY created_at DESC');
$projectsStmt->execute([(int) $user['id']]);
$projects = $projectsStmt->fetchAll();

$paymentsStmt = db()->prepare(
    'SELECT qp.*, p.title, s.business_name
     FROM qr_payments qp
     JOIN projects p ON p.id = qp.project_id
     JOIN suppliers s ON s.id = qp.supplier_id
     WHERE qp.owner_id = ?
     ORDER BY qp.created_at DESC
     LIMIT 6'
);
$paymentsStmt->execute([(int) $user['id']]);
$payments = $paymentsStmt->fetchAll();

$pageTitle = 'لوحة صاحب المشروع';
require_once __DIR__ . '/../includes/header.php';
?>

<section class="welcome-panel owner-welcome">
    <div>
        <span class="section-badge">مسار أصحاب المشاريع</span>
        <h2>أهلاً <?= e($user['full_name']) ?>، تابعي تمويلك وصرفك من مكان واحد.</h2>
        <p>كل مشروع يبدأ بطلب واضح، ثم مراجعة، ثم تمويل، وبعدها صرف مضبوط عبر QR يظهر للممولين بشفافية.</p>
    </div>
    <a class="btn btn-primary" href="<?= e(url('create-project.php')) ?>">إنشاء مشروع جديد</a>
</section>

<section class="stats-grid">
    <article class="stat-card featured"><span>رصيد المحفظة</span><strong><?= e(money($wallet['balance'] ?? 0)) ?></strong><small>جاهز للدفع للموردين</small></article>
    <article class="stat-card"><span>مشاريعي</span><strong><?= e((string) $stats['projects_count']) ?></strong><small>منشورة أو قيد المراجعة</small></article>
    <article class="stat-card"><span>إجمالي التمويل المستلم</span><strong><?= e(money($stats['funded_total'])) ?></strong><small>من الممولين والمؤسسات</small></article>
    <article class="stat-card"><span>إجمالي المصاريف</span><strong><?= e(money($spent['spent_total'])) ?></strong><small>مدفوعات QR موثقة</small></article>
</section>

<section class="quick-actions">
    <a class="btn btn-primary" href="<?= e(url('create-project.php')) ?>">إنشاء مشروع جديد</a>
    <a class="btn btn-light" href="<?= e(url('wallet.php')) ?>">عرض محفظتي</a>
    <a class="btn btn-light" href="<?= e(url('qr-payment.php')) ?>">الدفع عبر QR</a>
    <a class="btn btn-light" href="<?= e(url('tracking.php')) ?>">تتبع المصاريف</a>
</section>

<section class="dashboard-grid">
    <article class="panel">
        <div class="panel-header"><h2>المشاريع الخاصة بي</h2><a href="<?= e(url('create-project.php')) ?>">إضافة مشروع</a></div>
        <div class="project-stack">
            <?php foreach ($projects as $project): ?>
                <div class="project-row-card">
                    <div>
                        <h3><?= e($project['title']) ?></h3>
                        <span class="muted"><?= e($project['city']) ?> - <?= e($project['category']) ?></span>
                    </div>
                    <span class="badge <?= e(status_class($project['status'])) ?>"><?= e(status_label($project['status'])) ?></span>
                    <div class="progress-wrap">
                        <div class="card-row muted"><span><?= e(money($project['current_funding'])) ?></span><span><?= progress_percent($project['current_funding'], $project['funding_goal']) ?>%</span></div>
                        <div class="progress"><span style="width:<?= progress_percent($project['current_funding'], $project['funding_goal']) ?>%"></span></div>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (!$projects): ?>
                <div class="empty-state">لا يوجد مشاريع بعد، ابدئي بإنشاء مشروعك الأول.</div>
            <?php endif; ?>
        </div>
    </article>

    <article class="panel">
        <div class="panel-header"><h2>آخر عمليات QR</h2><a href="<?= e(url('tracking.php')) ?>">تقرير الشفافية</a></div>
        <div class="activity-list">
            <?php foreach ($payments as $payment): ?>
                <div class="activity-item">
                    <span><?= e($payment['business_name']) ?> - <?= e($payment['category']) ?></span>
                    <strong><?= e(money($payment['amount'])) ?></strong>
                </div>
            <?php endforeach; ?>
            <?php if (!$payments): ?>
                <p class="empty-state">لم يتم تسجيل أي دفع QR بعد.</p>
            <?php endif; ?>
        </div>
    </article>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
