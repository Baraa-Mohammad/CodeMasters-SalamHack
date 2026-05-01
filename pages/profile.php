<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if (!$fullName || !$phone) {
        flash('error', 'الاسم والهاتف مطلوبان');
    } else {
        $stmt = db()->prepare('UPDATE users SET full_name = ?, phone = ? WHERE id = ?');
        $stmt->execute([$fullName, $phone, $user['id']]);
        flash('success', 'تم تحديث بيانات الملف الشخصي بنجاح');
        redirect('pages/profile.php');
    }
}

// جلب معلومات إضافية حسب نوع الحساب
$stats = [];
if ($user['role'] === 'project_owner') {
    $projectsStmt = db()->prepare('SELECT COUNT(*) cnt FROM projects WHERE owner_id = ?');
    $projectsStmt->execute([(int)$user['id']]);
    $stats['projects'] = $projectsStmt->fetch()['cnt'];

    $fundingStmt = db()->prepare('SELECT COALESCE(SUM(current_funding), 0) total FROM projects WHERE owner_id = ?');
    $fundingStmt->execute([(int)$user['id']]);
    $stats['funding'] = $fundingStmt->fetch()['total'];

    ensure_wallet((int)$user['id']);
    $walletStmt = db()->prepare('SELECT balance FROM wallets WHERE user_id = ? LIMIT 1');
    $walletStmt->execute([(int)$user['id']]);
    $stats['balance'] = $walletStmt->fetch()['balance'];
} elseif ($user['role'] === 'funder') {
    $fundingStmt = db()->prepare('SELECT COUNT(*) cnt FROM funding_transactions WHERE funder_id = ?');
    $fundingStmt->execute([(int)$user['id']]);
    $stats['funded_projects'] = $fundingStmt->fetch()['cnt'];

    $amountStmt = db()->prepare('SELECT COALESCE(SUM(amount), 0) total FROM funding_transactions WHERE funder_id = ?');
    $amountStmt->execute([(int)$user['id']]);
    $stats['funded_amount'] = $amountStmt->fetch()['total'];
} elseif ($user['role'] === 'supplier') {
    $paymentsStmt = db()->prepare('SELECT COUNT(*) cnt FROM qr_payments WHERE supplier_id = ?');
    $paymentsStmt->execute([(int)$user['id']]);
    $stats['transactions'] = $paymentsStmt->fetch()['cnt'];

    $incomeStmt = db()->prepare('SELECT COALESCE(SUM(amount), 0) total FROM qr_payments WHERE supplier_id = ?');
    $incomeStmt->execute([(int)$user['id']]);
    $stats['income'] = $incomeStmt->fetch()['total'];
}

$pageTitle = 'الملف الشخصي';
require_once __DIR__ . '/../includes/header.php';
?>

<section class="welcome-panel profile-welcome">
    <div>
        <span class="section-badge">إدارة الحساب</span>
        <h2>الملف الشخصي</h2>
        <p>تحديث البيانات الشخصية وعرض إحصائيات حسابك.</p>
    </div>
</section>

<div class="content-wrapper">
    <div class="profile-container">
        <div class="profile-main">
            <div class="profile-header card">
                <div class="profile-avatar">
                    <span class="avatar-placeholder"><?= e(substr($user['full_name'] ?? '', 0, 1)) ?></span>
                </div>
                <div class="profile-info">
                    <h3><?= e($user['full_name']) ?></h3>
                    <p class="role-badge"><?= e(role_label($user['role'])) ?></p>
                    <p class="email-text"><?= e($user['email']) ?></p>
                </div>
            </div>

            <div class="card">
                <h4>تحديث البيانات</h4>
                <form method="POST" class="profile-form">
                    <div class="form-group">
                        <label for="full_name">الاسم الكامل</label>
                        <input type="text" id="full_name" name="full_name" value="<?= e($user['full_name']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email">البريد الإلكتروني (لا يمكن تغييره)</label>
                        <input type="email" id="email" value="<?= e($user['email']) ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label for="phone">رقم الهاتف</label>
                        <input type="tel" id="phone" name="phone" value="<?= e($user['phone']) ?>" placeholder="0599000000">
                    </div>
                    <button type="submit" class="btn btn-primary">حفظ التغييرات</button>
                </form>
            </div>
        </div>

        <div class="profile-sidebar">
            <?php if (!empty($stats)): ?>
            <div class="card stats-card">
                <h4>إحصائياتك</h4>
                <div class="stats-list">
                    <?php if (isset($stats['projects'])): ?>
                        <div class="stat-item">
                            <span class="stat-label">المشاريع</span>
                            <strong class="stat-value"><?= e((string)$stats['projects']) ?></strong>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">إجمالي التمويل</span>
                            <strong class="stat-value"><?= e(money($stats['funding'])) ?></strong>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">رصيد المحفظة</span>
                            <strong class="stat-value" style="color: #1F7A5C;"><?= e(money($stats['balance'])) ?></strong>
                        </div>
                    <?php elseif (isset($stats['funded_projects'])): ?>
                        <div class="stat-item">
                            <span class="stat-label">المشاريع الممولة</span>
                            <strong class="stat-value"><?= e((string)$stats['funded_projects']) ?></strong>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">إجمالي التمويل المقدم</span>
                            <strong class="stat-value" style="color: #1F7A5C;"><?= e(money($stats['funded_amount'])) ?></strong>
                        </div>
                    <?php elseif (isset($stats['transactions'])): ?>
                        <div class="stat-item">
                            <span class="stat-label">المعاملات</span>
                            <strong class="stat-value"><?= e((string)$stats['transactions']) ?></strong>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">إجمالي الدخل</span>
                            <strong class="stat-value" style="color: #1F7A5C;"><?= e(money($stats['income'])) ?></strong>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="card">
                <h4>التنبيهات والإعدادات</h4>
                <div class="settings-list">
                    <div class="setting-item">
                        <label>
                            <input type="checkbox" checked disabled> استقبال إشعارات البريد
                        </label>
                    </div>
                    <div class="setting-item">
                        <label>
                            <input type="checkbox" checked disabled> استقبال تحديثات المشاريع
                        </label>
                    </div>
                    <div class="setting-item">
                        <label>
                            <input type="checkbox" checked disabled> استقبال نتائج التمويل
                        </label>
                    </div>
                </div>
            </div>

            <div class="card danger-zone">
                <h4>منطقة الخطر</h4>
                <p class="text-small">حذف الحساب سيؤدي إلى فقدان جميع البيانات المرتبطة به.</p>
                <a href="<?= e(url('logout.php')) ?>" class="btn btn-outline btn-small">تسجيل الخروج</a>
            </div>
        </div>
    </div>
</div>

<style>
.profile-container {
    display: grid;
    grid-template-columns: 1fr 320px;
    gap: 2rem;
    margin-top: 2rem;
}

.profile-header {
    display: flex;
    align-items: center;
    gap: 2rem;
    padding: 2rem;
    margin-bottom: 2rem;
}

.profile-avatar {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    background: linear-gradient(135deg, #1F7A5C, #8E7DBE);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.avatar-placeholder {
    color: white;
    font-size: 2.5rem;
    font-weight: 700;
    font-family: var(--font-bold);
}

.profile-info h3 {
    margin: 0;
    font-size: 1.5rem;
    color: var(--color-dark);
}

.role-badge {
    display: inline-block;
    background: var(--color-secondary);
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.85rem;
    margin: 0.5rem 0;
}

.email-text {
    color: var(--color-text-secondary);
    margin: 0.25rem 0 0 0;
}

.profile-form {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group label {
    font-weight: 500;
    margin-bottom: 0.5rem;
    color: var(--color-dark);
}

.form-group input:disabled {
    background: var(--color-bg-light);
    cursor: not-allowed;
}

.stats-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.stat-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid var(--color-border);
}

.stat-item:last-child {
    border-bottom: none;
}

.stat-label {
    font-size: 0.9rem;
    color: var(--color-text-secondary);
}

.stat-value {
    font-size: 1.25rem;
    color: var(--color-primary);
}

.settings-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.setting-item label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    font-size: 0.9rem;
}

.danger-zone {
    border: 1px solid #ff6b6b;
    background: #fff5f5;
}

.danger-zone h4 {
    color: #ff6b6b;
}

@media (max-width: 768px) {
    .profile-container {
        grid-template-columns: 1fr;
    }

    .profile-header {
        flex-direction: column;
        text-align: center;
    }
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
