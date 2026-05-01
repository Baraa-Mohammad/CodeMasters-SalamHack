<?php
require_once __DIR__ . '/includes/auth.php';

$projectId = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT p.*, u.full_name, u.email, u.phone FROM projects p JOIN users u ON u.id = p.owner_id WHERE p.id = ? LIMIT 1');
$stmt->execute([$projectId]);
$project = $stmt->fetch();

if (!$project) {
    flash('error', 'المشروع غير موجود');
    redirect('marketplace.php');
}

$fundingLog = db()->prepare('SELECT ft.amount, ft.created_at, u.full_name FROM funding_transactions ft JOIN users u ON u.id = ft.funder_id WHERE ft.project_id = ? ORDER BY ft.created_at DESC LIMIT 8');
$fundingLog->execute([$projectId]);
$fundingRows = $fundingLog->fetchAll();

$spendingLog = db()->prepare('SELECT st.description, st.amount, st.category, st.created_at FROM spending_tracking st WHERE st.project_id = ? ORDER BY st.created_at DESC LIMIT 8');
$spendingLog->execute([$projectId]);
$spendingRows = $spendingLog->fetchAll();

$user = current_user();
$pageTitle = 'تفاصيل المشروع';
$layout = $user ? 'dashboard' : 'landing';
require_once __DIR__ . '/includes/header.php';
?>

<section class="details-layout">
    <article class="panel project-details">
        <div class="project-card-head">
            <span class="badge risk-<?= e($project['risk_score']) ?>"><?= e(risk_label($project['risk_score'])) ?></span>
            <span class="badge status-<?= e($project['status']) ?>"><?= e(status_label($project['status'])) ?></span>
        </div>
        <h2><?= e($project['title']) ?></h2>
        <p class="lead"><?= e($project['description']) ?></p>

        <div class="info-grid">
            <div><span>صاحب المشروع</span><strong><?= e($project['full_name']) ?></strong></div>
            <div><span>المدينة</span><strong><?= e($project['city']) ?></strong></div>
            <div><span>هدف التمويل</span><strong><?= e($project['purpose']) ?></strong></div>
            <div><span>التصنيف</span><strong><?= e($project['category']) ?></strong></div>
        </div>

        <div class="funding-block">
            <div class="card-row">
                <span>التقدم</span>
                <strong><?= progress_percent($project['current_funding'], $project['funding_goal']) ?>%</strong>
            </div>
            <div class="progress tall">
                <span style="width:<?= progress_percent($project['current_funding'], $project['funding_goal']) ?>%"></span>
            </div>
            <div class="card-row muted">
                <span><?= e(money($project['current_funding'])) ?> ممول</span>
                <span>الهدف <?= e(money($project['funding_goal'])) ?></span>
            </div>
        </div>

        <h3>ملخص الأثر</h3>
        <p><?= e($project['impact_summary']) ?></p>
    </article>

    <aside class="panel fund-panel" id="fund">
        <h2>موّل المشروع</h2>
        <p>سيتم تحديث التمويل ورصيد محفظة صاحب المشروع فوراً.</p>
        <?php if (($user['role'] ?? '') === 'funder' && in_array($project['status'], ['approved', 'funded'], true)): ?>
            <form method="post" action="<?= e(url('fund-project.php')) ?>" class="form-card">
                <input type="hidden" name="project_id" value="<?= e((string) $project['id']) ?>">
                <label>
                    المبلغ
                    <input type="number" name="amount" min="50" step="50" required placeholder="500">
                </label>
                <button class="btn btn-primary full" type="submit">موّل المشروع</button>
            </form>
        <?php elseif (!$user): ?>
            <a class="btn btn-primary full" href="<?= e(url('login.php')) ?>">تسجيل الدخول للتمويل</a>
        <?php else: ?>
            <div class="empty-state">التمويل متاح فقط لحسابات الممولين وللمشاريع المقبولة.</div>
        <?php endif; ?>
    </aside>
</section>

<section class="dashboard-grid">
    <article class="panel">
        <div class="panel-header"><h2>سجل التمويل</h2></div>
        <div class="activity-list">
            <?php foreach ($fundingRows as $row): ?>
                <div class="activity-item">
                    <span><?= e($row['full_name']) ?> موّل المشروع</span>
                    <strong><?= e(money($row['amount'])) ?></strong>
                </div>
            <?php endforeach; ?>
            <?php if (!$fundingRows): ?><p class="empty-state">لا يوجد تمويل بعد.</p><?php endif; ?>
        </div>
    </article>
    <article class="panel">
        <div class="panel-header"><h2>سجل النشاط والصرف</h2></div>
        <div class="activity-list">
            <?php foreach ($spendingRows as $row): ?>
                <div class="activity-item">
                    <span><?= e($row['description']) ?> - <?= e($row['category']) ?></span>
                    <strong><?= e(money($row['amount'])) ?></strong>
                </div>
            <?php endforeach; ?>
            <?php if (!$spendingRows): ?><p class="empty-state">لا توجد مصاريف مسجلة بعد.</p><?php endif; ?>
        </div>
    </article>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
