<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_role(['supplier']);

$supplierStmt = db()->prepare('SELECT * FROM suppliers WHERE user_id = ? LIMIT 1');
$supplierStmt->execute([(int) $user['id']]);
$supplier = $supplierStmt->fetch();

if (!$supplier) {
    $create = db()->prepare('INSERT INTO suppliers (user_id, business_name, category, qr_code) VALUES (?, ?, ?, ?)');
    $create->execute([(int) $user['id'], $user['full_name'], 'خدمات', 'QR-SUP-' . $user['id']]);
    $supplierStmt->execute([(int) $user['id']]);
    $supplier = $supplierStmt->fetch();
}

$paymentsStmt = db()->prepare(
    'SELECT qp.*, p.title, u.full_name owner_name
     FROM qr_payments qp
     JOIN projects p ON p.id = qp.project_id
     JOIN users u ON u.id = qp.owner_id
     WHERE qp.supplier_id = ?
     ORDER BY qp.created_at DESC'
);
$paymentsStmt->execute([(int) $supplier['id']]);
$payments = $paymentsStmt->fetchAll();
$total = array_sum(array_map(static fn($row) => (float) $row['amount'], $payments));

$pageTitle = 'لوحة المورد';
require_once __DIR__ . '/../includes/header.php';
?>

<section class="welcome-panel supplier-welcome">
    <div>
        <span class="section-badge">مسار الموردين</span>
        <h2>أهلاً <?= e($user['full_name']) ?>، هنا تظهر مدفوعات QR القادمة من المشاريع.</h2>
        <p>كل عملية مستلمة ترتبط بمشروع وفئة صرف، وهذا يجعل العلاقة بين المورد والممول وصاحب المشروع أوضح.</p>
    </div>
</section>

<section class="details-layout">
    <article class="panel qr-visual-panel">
        <div class="qr-visual supplier-qr">
            <div class="qr-grid">
                <?php for ($i = 0; $i < 24; $i++): ?><span></span><?php endfor; ?>
            </div>
            <strong><?= e($supplier['qr_code']) ?></strong>
        </div>
        <h2><?= e($supplier['business_name']) ?></h2>
        <p>الفئة: <?= e($supplier['category']) ?></p>
    </article>

    <section class="stats-grid compact-stats">
        <article class="stat-card featured"><span>إجمالي المدفوعات المستلمة</span><strong><?= e(money($total)) ?></strong><small>من عمليات QR</small></article>
        <article class="stat-card"><span>عدد العمليات</span><strong><?= e((string) count($payments)) ?></strong><small>عمليات موثقة</small></article>
        <article class="stat-card"><span>آخر عملية</span><strong><?= e($payments ? money($payments[0]['amount']) : '0 شيكل') ?></strong><small><?= e($payments ? date('Y-m-d', strtotime($payments[0]['created_at'])) : 'لا يوجد') ?></small></article>
    </section>
</section>

<section class="panel">
    <div class="panel-header"><h2>سجل عمليات QR</h2><span class="muted">كل العمليات مكتملة في النموذج التجريبي</span></div>
    <div class="responsive-table">
        <table>
            <thead><tr><th>المشروع</th><th>صاحب المشروع</th><th>الفئة</th><th>المبلغ</th><th>التاريخ</th><th>الحالة</th></tr></thead>
            <tbody>
                <?php foreach ($payments as $payment): ?>
                    <tr>
                        <td><?= e($payment['title']) ?></td>
                        <td><?= e($payment['owner_name']) ?></td>
                        <td><?= e($payment['category']) ?></td>
                        <td><?= e(money($payment['amount'])) ?></td>
                        <td><?= e(date('Y-m-d', strtotime($payment['created_at']))) ?></td>
                        <td><span class="badge status-approved">مكتملة</span></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$payments): ?><tr><td colspan="6" class="empty-state">لا توجد مدفوعات مستلمة بعد.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
