<?php
require_once __DIR__ . '/includes/auth.php';
$user = require_role(['project_owner', 'funder', 'admin']);

if ($user['role'] === 'admin') {
    $scopeSql = '1=1';
} elseif ($user['role'] === 'funder') {
    $scopeSql = 'p.id IN (SELECT project_id FROM funding_transactions WHERE funder_id = :user_id)';
} else {
    $scopeSql = 'p.owner_id = :user_id';
}

$fundedStmt = db()->prepare("SELECT COALESCE(SUM(p.current_funding), 0) total FROM projects p WHERE $scopeSql");
if ($user['role'] !== 'admin') {
    $fundedStmt->bindValue(':user_id', (int) $user['id'], PDO::PARAM_INT);
}
$fundedStmt->execute();
$totalFunded = $fundedStmt->fetch();

$spentStmt = db()->prepare("SELECT COALESCE(SUM(st.amount), 0) total FROM spending_tracking st JOIN projects p ON p.id = st.project_id WHERE $scopeSql");
if ($user['role'] !== 'admin') {
    $spentStmt->bindValue(':user_id', (int) $user['id'], PDO::PARAM_INT);
}
$spentStmt->execute();
$totalSpent = $spentStmt->fetch();

$categoryStmt = db()->prepare("SELECT st.category, COALESCE(SUM(st.amount), 0) total FROM spending_tracking st JOIN projects p ON p.id = st.project_id WHERE $scopeSql GROUP BY st.category ORDER BY total DESC");
if ($user['role'] !== 'admin') {
    $categoryStmt->bindValue(':user_id', (int) $user['id'], PDO::PARAM_INT);
}
$categoryStmt->execute();
$categoryRows = $categoryStmt->fetchAll();

$paymentsStmt = db()->prepare(
    "SELECT st.*, p.title, s.business_name
     FROM spending_tracking st
     JOIN projects p ON p.id = st.project_id
     JOIN qr_payments qp ON qp.id = st.payment_id
     JOIN suppliers s ON s.id = qp.supplier_id
     WHERE $scopeSql
     ORDER BY st.created_at DESC
     LIMIT 20"
);
if ($user['role'] !== 'admin') {
    $paymentsStmt->bindValue(':user_id', (int) $user['id'], PDO::PARAM_INT);
}
$paymentsStmt->execute();
$payments = $paymentsStmt->fetchAll();

$maxCategory = max(array_map(static fn($row) => (float) $row['total'], $categoryRows ?: [['total' => 1]]));

$pageTitle = 'تتبع المصاريف';
require_once __DIR__ . '/includes/header.php';
?>

<section class="notice-panel">
    هذا التقرير يساعد الممولين على معرفة أين تم استخدام التمويل بشكل شفاف.
</section>

<section class="stats-grid">
    <article class="stat-card">
        <span>إجمالي التمويل</span>
        <strong><?= e(money($totalFunded['total'])) ?></strong>
        <small>تمويل متراكم للمشاريع</small>
    </article>
    <article class="stat-card">
        <span>إجمالي المصروف</span>
        <strong><?= e(money($totalSpent['total'])) ?></strong>
        <small>مدفوعات QR موثقة</small>
    </article>
    <article class="stat-card">
        <span>المبلغ المتبقي</span>
        <strong><?= e(money(($totalFunded['total'] ?? 0) - ($totalSpent['total'] ?? 0))) ?></strong>
        <small>فرق التمويل والصرف</small>
    </article>
</section>

<section class="dashboard-grid">
    <article class="panel">
        <div class="panel-header"><h2>الصرف حسب الفئة</h2></div>
        <div class="bar-list">
            <?php foreach ($categoryRows as $row): ?>
                <?php $width = $maxCategory > 0 ? round(((float) $row['total'] / $maxCategory) * 100) : 0; ?>
                <div class="bar-item">
                    <div class="card-row">
                        <span><?= e($row['category']) ?></span>
                        <strong><?= e(money($row['total'])) ?></strong>
                    </div>
                    <div class="progress"><span style="width:<?= $width ?>%"></span></div>
                </div>
            <?php endforeach; ?>
            <?php if (!$categoryRows): ?><p class="empty-state">لا توجد مصاريف بعد.</p><?php endif; ?>
        </div>
    </article>

    <article class="panel">
        <div class="panel-header"><h2>الخط الزمني للصرف</h2></div>
        <div class="timeline">
            <?php foreach (array_slice($payments, 0, 6) as $payment): ?>
                <div class="timeline-item">
                    <span></span>
                    <div>
                        <strong><?= e($payment['description']) ?></strong>
                        <p><?= e($payment['title']) ?> - <?= e(date('Y-m-d', strtotime($payment['created_at']))) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (!$payments): ?><p class="empty-state">لا توجد عمليات في الخط الزمني.</p><?php endif; ?>
        </div>
    </article>
</section>

<section class="panel">
    <div class="panel-header">
        <h2>المدفوعات إلى الموردين</h2>
        <span class="muted">عمليات QR مرتبطة بالمشاريع</span>
    </div>
    <div class="responsive-table">
        <table>
            <thead>
                <tr>
                    <th>المشروع</th>
                    <th>المورد</th>
                    <th>الفئة</th>
                    <th>المبلغ</th>
                    <th>التاريخ</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($payments as $payment): ?>
                    <tr>
                        <td><?= e($payment['title']) ?></td>
                        <td><?= e($payment['business_name']) ?></td>
                        <td><?= e($payment['category']) ?></td>
                        <td><?= e(money($payment['amount'])) ?></td>
                        <td><?= e(date('Y-m-d', strtotime($payment['created_at']))) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$payments): ?>
                    <tr><td colspan="5" class="empty-state">لا توجد مدفوعات مسجلة.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
