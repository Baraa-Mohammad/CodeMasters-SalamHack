<?php
require_once __DIR__ . '/includes/auth.php';
$user = require_role(['project_owner']);
ensure_wallet((int) $user['id']);

$walletStmt = db()->prepare('SELECT balance FROM wallets WHERE user_id = ? LIMIT 1');
$walletStmt->execute([(int) $user['id']]);
$wallet = $walletStmt->fetch();

$fundingStmt = db()->prepare('SELECT COALESCE(SUM(ft.amount), 0) total FROM funding_transactions ft JOIN projects p ON p.id = ft.project_id WHERE p.owner_id = ?');
$fundingStmt->execute([(int) $user['id']]);
$totalFunding = $fundingStmt->fetch();

$spentStmt = db()->prepare('SELECT COALESCE(SUM(amount), 0) total FROM qr_payments WHERE owner_id = ?');
$spentStmt->execute([(int) $user['id']]);
$totalSpent = $spentStmt->fetch();

$transactionsStmt = db()->prepare(
    "SELECT * FROM (
        SELECT 'تمويل وارد' AS type, p.title AS project_title, ft.amount AS amount, ft.created_at AS created_at, 'مكتمل' AS status
        FROM funding_transactions ft
        JOIN projects p ON p.id = ft.project_id
        WHERE p.owner_id = ?
        UNION ALL
        SELECT 'دفع QR' AS type, p.title AS project_title, -qp.amount AS amount, qp.created_at AS created_at, 'مكتمل' AS status
        FROM qr_payments qp
        JOIN projects p ON p.id = qp.project_id
        WHERE qp.owner_id = ?
    ) wallet_ops
    ORDER BY created_at DESC
    LIMIT 12"
);
$transactionsStmt->execute([(int) $user['id'], (int) $user['id']]);
$transactions = $transactionsStmt->fetchAll();

$pageTitle = 'المحفظة';
require_once __DIR__ . '/includes/header.php';
?>

<section class="stats-grid">
    <article class="stat-card featured">
        <span>الرصيد الحالي</span>
        <strong><?= e(money($wallet['balance'] ?? 0)) ?></strong>
        <small>متاح للدفع للموردين عبر QR</small>
    </article>
    <article class="stat-card">
        <span>إجمالي التمويل المستلم</span>
        <strong><?= e(money($totalFunding['total'])) ?></strong>
        <small>من حملات التمويل</small>
    </article>
    <article class="stat-card">
        <span>إجمالي المصروف</span>
        <strong><?= e(money($totalSpent['total'])) ?></strong>
        <small>مدفوعات QR موثقة</small>
    </article>
    <article class="stat-card">
        <span>المتبقي</span>
        <strong><?= e(money(($totalFunding['total'] ?? 0) - ($totalSpent['total'] ?? 0))) ?></strong>
        <small>بعد المصاريف المسجلة</small>
    </article>
</section>

<section class="quick-actions">
    <a class="btn btn-primary" href="<?= e(url('qr-payment.php')) ?>">الدفع عبر QR</a>
    <a class="btn btn-light" href="<?= e(url('tracking.php')) ?>">تتبع المصاريف</a>
</section>

<section class="panel">
    <div class="panel-header">
        <h2>آخر عمليات المحفظة</h2>
        <span class="muted">تمويل وارد ومدفوعات موجهة</span>
    </div>
    <div class="responsive-table">
        <table>
            <thead>
                <tr>
                    <th>نوع العملية</th>
                    <th>المشروع</th>
                    <th>المبلغ</th>
                    <th>التاريخ</th>
                    <th>الحالة</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transactions as $transaction): ?>
                    <tr>
                        <td><?= e($transaction['type']) ?></td>
                        <td><?= e($transaction['project_title']) ?></td>
                        <td class="<?= (float) $transaction['amount'] < 0 ? 'negative' : 'positive' ?>"><?= e(money($transaction['amount'])) ?></td>
                        <td><?= e(date('Y-m-d', strtotime($transaction['created_at']))) ?></td>
                        <td><span class="badge status-approved"><?= e($transaction['status']) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$transactions): ?>
                    <tr><td colspan="5" class="empty-state">لا توجد عمليات بعد.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
