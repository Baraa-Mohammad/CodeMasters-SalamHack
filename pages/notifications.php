<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_login();

$notifications = [];

if ($user['role'] === 'project_owner') {
    $pendingStmt = db()->prepare('SELECT id, title, created_at FROM projects WHERE owner_id = ? AND status = "pending" ORDER BY created_at DESC LIMIT 5');
    $pendingStmt->execute([(int) $user['id']]);
    foreach ($pendingStmt->fetchAll() as $proj) {
        $notifications[] = [
            'type' => 'pending',
            'title' => 'المشروع قيد المراجعة',
            'message' => 'مشروعك "' . $proj['title'] . '" بانتظار مراجعة الإدارة.',
            'date' => $proj['created_at'],
            'action_url' => 'project-details.php?id=' . $proj['id'],
        ];
    }

    $fundingStmt = db()->prepare(
        'SELECT ft.project_id, ft.amount, ft.created_at, p.title
         FROM funding_transactions ft
         JOIN projects p ON p.id = ft.project_id
         WHERE p.owner_id = ?
         ORDER BY ft.created_at DESC
         LIMIT 5'
    );
    $fundingStmt->execute([(int) $user['id']]);
    foreach ($fundingStmt->fetchAll() as $fund) {
        $notifications[] = [
            'type' => 'funding',
            'title' => 'تمويل جديد',
            'message' => 'تم دعم مشروع "' . $fund['title'] . '" بمبلغ ' . money($fund['amount']) . '.',
            'date' => $fund['created_at'],
            'action_url' => 'project-details.php?id=' . $fund['project_id'],
        ];
    }

    $paymentsStmt = db()->prepare(
        'SELECT qp.project_id, qp.amount, qp.category, qp.created_at, p.title
         FROM qr_payments qp
         JOIN projects p ON p.id = qp.project_id
         WHERE qp.owner_id = ?
         ORDER BY qp.created_at DESC
         LIMIT 5'
    );
    $paymentsStmt->execute([(int) $user['id']]);
    foreach ($paymentsStmt->fetchAll() as $pay) {
        $notifications[] = [
            'type' => 'payment',
            'title' => 'دفع عبر QR',
            'message' => 'تم دفع ' . money($pay['amount']) . ' لفئة "' . $pay['category'] . '" من مشروع "' . $pay['title'] . '".',
            'date' => $pay['created_at'],
            'action_url' => 'tracking.php',
        ];
    }
}

if ($user['role'] === 'funder') {
    $newProjectsStmt = db()->prepare(
        'SELECT p.id, p.title, p.created_at, u.full_name
         FROM projects p
         JOIN users u ON u.id = p.owner_id
         WHERE p.status = "approved"
         ORDER BY p.created_at DESC
         LIMIT 5'
    );
    $newProjectsStmt->execute();
    foreach ($newProjectsStmt->fetchAll() as $proj) {
        $notifications[] = [
            'type' => 'new_project',
            'title' => 'مشروع جديد متاح',
            'message' => 'مشروع "' . $proj['title'] . '" لصاحبه/صاحبته ' . $proj['full_name'] . ' متاح للتمويل.',
            'date' => $proj['created_at'],
            'action_url' => 'project-details.php?id=' . $proj['id'],
        ];
    }

    $supportedStmt = db()->prepare(
        'SELECT p.id, p.title, p.current_funding, p.funding_goal, MAX(ft.created_at) AS last_funded_at
         FROM projects p
         JOIN funding_transactions ft ON ft.project_id = p.id
         WHERE ft.funder_id = ?
         GROUP BY p.id, p.title, p.current_funding, p.funding_goal
         ORDER BY last_funded_at DESC
         LIMIT 5'
    );
    $supportedStmt->execute([(int) $user['id']]);
    foreach ($supportedStmt->fetchAll() as $proj) {
        $notifications[] = [
            'type' => 'project_update',
            'title' => 'تحديث مشروع ممول',
            'message' => 'المشروع "' . $proj['title'] . '" وصل إلى ' . progress_percent($proj['current_funding'], $proj['funding_goal']) . '% من الهدف.',
            'date' => $proj['last_funded_at'],
            'action_url' => 'project-details.php?id=' . $proj['id'],
        ];
    }
}

if ($user['role'] === 'supplier') {
    $supplierStmt = db()->prepare('SELECT id FROM suppliers WHERE user_id = ? LIMIT 1');
    $supplierStmt->execute([(int) $user['id']]);
    $supplierId = (int) ($supplierStmt->fetchColumn() ?: 0);

    if ($supplierId > 0) {
        $transStmt = db()->prepare(
            'SELECT qp.amount, qp.category, qp.created_at, p.title
             FROM qr_payments qp
             JOIN projects p ON p.id = qp.project_id
             WHERE qp.supplier_id = ?
             ORDER BY qp.created_at DESC
             LIMIT 7'
        );
        $transStmt->execute([$supplierId]);
        foreach ($transStmt->fetchAll() as $row) {
            $notifications[] = [
                'type' => 'transaction',
                'title' => 'دفعة جديدة من QR',
                'message' => 'تم استلام ' . money($row['amount']) . ' لفئة "' . $row['category'] . '" من مشروع "' . $row['title'] . '".',
                'date' => $row['created_at'],
                'action_url' => 'pages/dashboard-supplier.php',
            ];
        }
    }
}

if ($user['role'] === 'admin') {
    $reviewStmt = db()->prepare(
        'SELECT p.id, p.title, p.created_at, u.full_name
         FROM projects p
         JOIN users u ON u.id = p.owner_id
         WHERE p.status = "pending"
         ORDER BY p.created_at DESC
         LIMIT 7'
    );
    $reviewStmt->execute();
    foreach ($reviewStmt->fetchAll() as $rev) {
        $notifications[] = [
            'type' => 'review_needed',
            'title' => 'مشروع بانتظار المراجعة',
            'message' => 'المشروع "' . $rev['title'] . '" (صاحب/صاحبة المشروع: ' . $rev['full_name'] . ') يحتاج قرار مراجعة.',
            'date' => $rev['created_at'],
            'action_url' => 'pages/dashboard-admin.php',
        ];
    }
}

usort($notifications, static function (array $a, array $b): int {
    return strtotime($b['date']) <=> strtotime($a['date']);
});

function relative_time_ar(string $datetime): string
{
    $date = strtotime($datetime);
    $diff = time() - $date;

    if ($diff < 60) {
        return 'الآن';
    }
    if ($diff < 3600) {
        return floor($diff / 60) . ' دقيقة';
    }
    if ($diff < 86400) {
        return floor($diff / 3600) . ' ساعة';
    }
    return date('d/m/Y H:i', $date);
}

$pageTitle = 'الإشعارات';
require_once __DIR__ . '/../includes/header.php';
?>

<section class="welcome-panel notifications-welcome">
    <div>
        <span class="section-badge">مركز التحديثات</span>
        <h2>الإشعارات والتنبيهات</h2>
        <p>تابع آخر الأحداث على حسابك: تمويلات جديدة، تحديثات مشاريع، عمليات QR، ومهام المراجعة.</p>
    </div>
</section>

<div class="content-wrapper notifications-container">
    <aside class="notifications-sidebar">
        <div class="filter-card">
            <h4>الفلاتر</h4>
            <div class="filter-group">
                <label><span>الكل</span><input type="radio" name="filter" value="all" checked></label>
                <label><span>غير مقروء</span><input type="radio" name="filter" value="unread"></label>
                <label><span>اليوم</span><input type="radio" name="filter" value="today"></label>
                <label><span>هذا الأسبوع</span><input type="radio" name="filter" value="week"></label>
            </div>
        </div>

        <div class="stats-card">
            <h4>الملخص</h4>
            <div class="stats">
                <div class="stat">
                    <strong><?= e((string) count($notifications)) ?></strong>
                    <span>إشعار</span>
                </div>
            </div>
        </div>
    </aside>

    <section class="notifications-list">
        <?php if (!$notifications): ?>
            <div class="empty-state">
                <h3>لا توجد إشعارات حالياً</h3>
                <p>ستظهر هنا التحديثات الجديدة عند حدوث أي نشاط على حسابك.</p>
            </div>
        <?php else: ?>
            <?php foreach ($notifications as $notif): ?>
                <a href="<?= e(url($notif['action_url'])) ?>" class="notification-item notification-<?= e($notif['type']) ?>">
                    <div class="notification-content">
                        <h4><?= e($notif['title']) ?></h4>
                        <p><?= e($notif['message']) ?></p>
                        <span class="notification-date"><?= e(relative_time_ar($notif['date'])) ?></span>
                    </div>
                    <span class="notification-arrow">←</span>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>
</div>

<style>
.notifications-container {
    display: grid;
    grid-template-columns: 280px 1fr;
    gap: 1.25rem;
    margin-top: 1rem;
}

.notifications-sidebar {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.filter-card,
.stats-card {
    background: #fff;
    border: 1px solid #e7e2d6;
    border-radius: 14px;
    padding: 1rem;
}

.filter-card h4,
.stats-card h4 {
    margin: 0 0 .8rem 0;
    color: #1f7a5c;
}

.filter-group {
    display: grid;
    gap: .55rem;
}

.filter-group label {
    display: grid;
    grid-template-columns: 1fr auto;
    align-items: center;
    gap: .8rem;
    background: #f7faf8;
    border-radius: 10px;
    padding: .55rem .65rem;
    font-weight: 700;
    color: #51625c;
}

.filter-group label span {
    min-width: 0;
    text-align: right;
    overflow-wrap: anywhere;
}

.filter-group input[type="radio"] {
    margin: 0;
    flex-shrink: 0;
}

.stats .stat {
    text-align: center;
    background: #f7faf8;
    border-radius: 10px;
    padding: .8rem;
}

.stats .stat strong {
    display: block;
    color: #1f7a5c;
    font-size: 1.35rem;
}

.notifications-list {
    display: grid;
    gap: .8rem;
}

.notification-item {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: .8rem;
    background: #fff;
    border: 1px solid #e7e2d6;
    border-right: 4px solid #1f7a5c;
    border-radius: 12px;
    padding: 1rem;
    text-decoration: none;
}

.notification-item:hover {
    box-shadow: 0 8px 24px rgba(31,122,92,.08);
}

.notification-content h4 {
    margin: 0 0 .25rem 0;
    color: #1f2e28;
    font-size: 1rem;
}

.notification-content p {
    margin: 0 0 .5rem 0;
    color: #5c6763;
    line-height: 1.6;
}

.notification-date {
    color: #8c9692;
    font-size: .83rem;
}

.notification-arrow {
    color: #1f7a5c;
    font-size: 1.1rem;
    align-self: center;
}

.empty-state {
    border-radius: 12px;
    background: #fff;
    border: 1px dashed #cfded5;
    text-align: center;
    padding: 2rem 1rem;
}

@media (max-width: 900px) {
    .notifications-container {
        grid-template-columns: 1fr;
    }
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
