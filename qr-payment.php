<?php
require_once __DIR__ . '/includes/auth.php';
$user = require_role(['project_owner']);
ensure_wallet((int) $user['id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $projectId = (int) ($_POST['project_id'] ?? 0);
    $supplierId = (int) ($_POST['supplier_id'] ?? 0);
    $amount = (float) ($_POST['amount'] ?? 0);
    $category = trim($_POST['category'] ?? '');

    try {
        db()->beginTransaction();

        $walletStmt = db()->prepare('SELECT balance FROM wallets WHERE user_id = ? FOR UPDATE');
        $walletStmt->execute([(int) $user['id']]);
        $wallet = $walletStmt->fetch();

        $projectStmt = db()->prepare('SELECT id, title FROM projects WHERE id = ? AND owner_id = ? LIMIT 1');
        $projectStmt->execute([$projectId, (int) $user['id']]);
        $project = $projectStmt->fetch();

        $supplierStmt = db()->prepare('SELECT id, business_name FROM suppliers WHERE id = ? LIMIT 1');
        $supplierStmt->execute([$supplierId]);
        $supplier = $supplierStmt->fetch();

        if (!$project || !$supplier || $amount <= 0 || $category === '') {
            throw new RuntimeException('invalid_payment');
        }

        if ((float) ($wallet['balance'] ?? 0) < $amount) {
            throw new RuntimeException('insufficient_balance');
        }

        $deduct = db()->prepare('UPDATE wallets SET balance = balance - ? WHERE user_id = ?');
        $deduct->execute([$amount, (int) $user['id']]);

        $payment = db()->prepare('INSERT INTO qr_payments (project_id, owner_id, supplier_id, amount, category) VALUES (?, ?, ?, ?, ?)');
        $payment->execute([$projectId, (int) $user['id'], $supplierId, $amount, $category]);
        $paymentId = (int) db()->lastInsertId();

        $tracking = db()->prepare('INSERT INTO spending_tracking (project_id, payment_id, description, amount, category) VALUES (?, ?, ?, ?, ?)');
        $tracking->execute([$projectId, $paymentId, 'دفع عبر QR إلى ' . $supplier['business_name'], $amount, $category]);

        db()->commit();
        flash('success', 'تم الدفع بنجاح وتتبع العملية ضمن تقرير الشفافية');
        redirect('tracking.php');
    } catch (Throwable $exception) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }
        $message = $exception->getMessage() === 'insufficient_balance' ? 'الرصيد غير كاف لإتمام الدفع' : 'تعذر تنفيذ الدفع عبر QR';
        flash('error', $message);
        redirect('qr-payment.php');
    }
}

$suppliers = db()->query('SELECT s.*, u.full_name FROM suppliers s JOIN users u ON u.id = s.user_id ORDER BY s.business_name')->fetchAll();
$projectsStmt = db()->prepare("SELECT id, title FROM projects WHERE owner_id = ? AND status IN ('approved','funded') ORDER BY created_at DESC");
$projectsStmt->execute([(int) $user['id']]);
$projects = $projectsStmt->fetchAll();
$walletStmt = db()->prepare('SELECT balance FROM wallets WHERE user_id = ? LIMIT 1');
$walletStmt->execute([(int) $user['id']]);
$wallet = $walletStmt->fetch();

$pageTitle = 'الدفع عبر QR';
require_once __DIR__ . '/includes/header.php';
?>

<section class="details-layout">
    <article class="panel qr-visual-panel">
        <div class="qr-visual">
            <div class="qr-grid">
                <span></span><span></span><span></span><span></span><span></span><span></span>
                <span></span><span></span><span></span><span></span><span></span><span></span>
                <span></span><span></span><span></span><span></span><span></span><span></span>
                <span></span><span></span><span></span><span></span><span></span><span></span>
            </div>
            <strong>QR</strong>
        </div>
        <h2>دفع موجه وآمن</h2>
        <p>هذه الصفحة تحاكي صرف التمويل للموردين فقط، ثم تسجل العملية تلقائياً في تقرير الشفافية.</p>
        <div class="mini-stat">
            <span>رصيدك الحالي</span>
            <strong><?= e(money($wallet['balance'] ?? 0)) ?></strong>
        </div>
    </article>

    <article class="panel form-panel">
        <div class="panel-header">
            <h2>تفاصيل الدفع</h2>
        </div>
        <form method="post" class="form-grid">
            <label>
                المورد
                <select name="supplier_id" required>
                    <option value="">اختر المورد</option>
                    <?php foreach ($suppliers as $supplier): ?>
                        <option value="<?= e((string) $supplier['id']) ?>"><?= e($supplier['business_name']) ?> - <?= e($supplier['category']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                المشروع
                <select name="project_id" required>
                    <option value="">اختر المشروع</option>
                    <?php foreach ($projects as $project): ?>
                        <option value="<?= e((string) $project['id']) ?>"><?= e($project['title']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                المبلغ
                <input type="number" name="amount" min="10" step="10" required placeholder="250">
            </label>
            <label>
                فئة الصرف
                <select name="category" required>
                    <option value="مواد">مواد</option>
                    <option value="معدات">معدات</option>
                    <option value="تسويق">تسويق</option>
                    <option value="خدمات">خدمات</option>
                </select>
            </label>
            <button class="btn btn-primary full-field" type="submit">تأكيد الدفع</button>
        </form>
    </article>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
