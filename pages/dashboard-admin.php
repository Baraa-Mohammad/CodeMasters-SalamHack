<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_role(['admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $projectId = (int) ($_POST['project_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $status = $action === 'approve' ? 'approved' : ($action === 'reject' ? 'rejected' : '');

    if ($projectId > 0 && $status !== '') {
        $stmt = db()->prepare('UPDATE projects SET status = ? WHERE id = ?');
        $stmt->execute([$status, $projectId]);
        flash('success', 'تم تحديث حالة المشروع');
    }

    redirect('pages/dashboard-admin.php');
}

$stats = [
    'users' => db()->query('SELECT COUNT(*) total FROM users')->fetch()['total'],
    'projects' => db()->query('SELECT COUNT(*) total FROM projects')->fetch()['total'],
    'funding' => db()->query('SELECT COALESCE(SUM(amount), 0) total FROM funding_transactions')->fetch()['total'],
    'qr' => db()->query('SELECT COUNT(*) total FROM qr_payments')->fetch()['total'],
    'pending' => db()->query("SELECT COUNT(*) total FROM projects WHERE status = 'pending'")->fetch()['total'],
];

$projects = db()->query("SELECT p.*, u.full_name FROM projects p JOIN users u ON u.id = p.owner_id ORDER BY FIELD(p.status, 'pending', 'approved', 'funded', 'rejected'), p.created_at DESC")->fetchAll();
$users = db()->query('SELECT full_name, email, role, created_at FROM users ORDER BY created_at DESC LIMIT 8')->fetchAll();
$recent = db()->query(
    "SELECT 'تمويل' type, ft.amount, ft.created_at, p.title
     FROM funding_transactions ft JOIN projects p ON p.id = ft.project_id
     UNION ALL
     SELECT 'QR' type, qp.amount, qp.created_at, p.title
     FROM qr_payments qp JOIN projects p ON p.id = qp.project_id
     ORDER BY created_at DESC
     LIMIT 7"
)->fetchAll();

$pageTitle = 'لوحة إدارة المنصة';
require_once __DIR__ . '/../includes/header.php';
?>

<section class="welcome-panel admin-welcome">
    <div>
        <span class="section-badge">إدارة ومراجعة</span>
        <h2>لوحة مراقبة إثمار: مراجعة المشاريع، التمويل، ومدفوعات QR.</h2>
        <p>الهدف في العرض النهائي هو إظهار الثقة: مشروع واضح، تمويل واضح، وصرف قابل للتتبع.</p>
    </div>
</section>

<section class="stats-grid">
    <article class="stat-card"><span>المستخدمون</span><strong><?= e((string) $stats['users']) ?></strong><small>كل الأدوار</small></article>
    <article class="stat-card"><span>المشاريع</span><strong><?= e((string) $stats['projects']) ?></strong><small>كل الحالات</small></article>
    <article class="stat-card featured"><span>إجمالي التمويل</span><strong><?= e(money($stats['funding'])) ?></strong><small>عمليات مسجلة</small></article>
    <article class="stat-card"><span>عمليات QR</span><strong><?= e((string) $stats['qr']) ?></strong><small>مدفوعات موجهة</small></article>
    <article class="stat-card"><span>قيد المراجعة</span><strong><?= e((string) $stats['pending']) ?></strong><small>تحتاج قراراً</small></article>
</section>

<section class="dashboard-grid">
    <article class="panel">
        <div class="panel-header"><h2>إدارة المشاريع</h2><span class="muted">موافقة أو رفض سريع</span></div>
        <div class="responsive-table">
            <table>
                <thead><tr><th>المشروع</th><th>المالك</th><th>المبلغ المطلوب</th><th>الحالة</th><th>Risk Score</th><th>الإجراءات</th></tr></thead>
                <tbody>
                    <?php foreach ($projects as $project): ?>
                        <tr>
                            <td><?= e($project['title']) ?></td>
                            <td><?= e($project['full_name']) ?></td>
                            <td><?= e(money($project['funding_goal'])) ?></td>
                            <td><span class="badge <?= e(status_class($project['status'])) ?>"><?= e(status_label($project['status'])) ?></span></td>
                            <td><span class="badge <?= e(risk_class($project['risk_score'])) ?>"><?= e(risk_label($project['risk_score'])) ?></span></td>
                            <td class="table-actions">
                                <form method="post">
                                    <input type="hidden" name="project_id" value="<?= e((string) $project['id']) ?>">
                                    <button class="btn btn-small btn-success" name="action" value="approve" type="submit">موافقة</button>
                                </form>
                                <form method="post">
                                    <input type="hidden" name="project_id" value="<?= e((string) $project['id']) ?>">
                                    <button class="btn btn-small btn-danger" name="action" value="reject" type="submit">رفض</button>
                                </form>
                                <a class="btn btn-small btn-light" href="<?= e(url('project-details.php?id=' . $project['id'])) ?>">عرض</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </article>

    <article class="panel">
        <div class="panel-header"><h2>آخر نشاط في المنصة</h2></div>
        <div class="activity-list">
            <?php foreach ($recent as $row): ?>
                <div class="activity-item">
                    <span><?= e($row['type']) ?> - <?= e($row['title']) ?></span>
                    <strong><?= e(money($row['amount'])) ?></strong>
                </div>
            <?php endforeach; ?>
        </div>
    </article>
</section>

<section class="panel">
    <div class="panel-header"><h2>إدارة المستخدمين</h2><span class="muted">عرض سريع للحسابات التجريبية والجديدة</span></div>
    <div class="responsive-table">
        <table>
            <thead><tr><th>الاسم</th><th>البريد</th><th>الدور</th><th>تاريخ الإنشاء</th></tr></thead>
            <tbody>
                <?php foreach ($users as $account): ?>
                    <tr>
                        <td><?= e($account['full_name']) ?></td>
                        <td><?= e($account['email']) ?></td>
                        <td><?= e(role_label($account['role'])) ?></td>
                        <td><?= e(date('Y-m-d', strtotime($account['created_at']))) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
