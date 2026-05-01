<?php
require_once __DIR__ . '/includes/auth.php';

if (current_user()) {
    redirect(dashboard_for_role(current_user()['role']));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    $role = $_POST['role'] ?? 'project_owner';
    $allowedRoles = ['project_owner', 'funder', 'supplier'];

    if ($fullName === '' || $email === '' || $password === '' || !in_array($role, $allowedRoles, true)) {
        flash('error', 'يرجى تعبئة البيانات المطلوبة');
        redirect('signup.php');
    }

    try {
        db()->beginTransaction();
        $stmt = db()->prepare('INSERT INTO users (full_name, email, phone, password, role) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$fullName, $email, $phone, password_hash($password, PASSWORD_DEFAULT), $role]);
        $userId = (int) db()->lastInsertId();
        ensure_wallet($userId);

        if ($role === 'supplier') {
            $supplier = db()->prepare('INSERT INTO suppliers (user_id, business_name, category, qr_code) VALUES (?, ?, ?, ?)');
            $supplier->execute([$userId, $fullName, 'مواد', 'QR-SUP-' . $userId]);
        }

        db()->commit();
        $_SESSION['user_id'] = $userId;
        flash('success', 'تم إنشاء الحساب بنجاح');
        redirect(dashboard_for_role($role));
    } catch (Throwable $exception) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }
        flash('error', 'تعذر إنشاء الحساب. تأكد من أن البريد غير مستخدم');
        redirect('signup.php');
    }
}

$pageTitle = 'إنشاء حساب';
$layout = 'auth';
require_once __DIR__ . '/includes/header.php';
?>

<main class="auth-page">
    <section class="auth-panel wide">
        <a class="brand auth-brand" href="<?= e(url('index.php')) ?>">
            <?= logo_img('brand-logo auth-logo') ?>
            <span class="brand-text"><strong>إثمار</strong><small>حساب جديد</small></span>
        </a>
        <h1>إنشاء حساب</h1>

        <form method="post" class="form-card two-cols">
            <label>
                الاسم الكامل
                <input type="text" name="full_name" required>
            </label>
            <label>
                البريد الإلكتروني
                <input type="email" name="email" required>
            </label>
            <label>
                رقم الهاتف
                <input type="text" name="phone" required>
            </label>
            <label>
                كلمة المرور
                <input type="password" name="password" required>
            </label>
            <label class="full-field">
                نوع الحساب
                <select name="role" required>
                    <option value="project_owner">صاحب مشروع</option>
                    <option value="funder">ممول / مؤسسة</option>
                    <option value="supplier">مورد</option>
                </select>
            </label>
            <button class="btn btn-primary full-field" type="submit">إنشاء حساب</button>
        </form>
        <a class="text-link" href="<?= e(url('login.php')) ?>">لدي حساب بالفعل</a>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
