<?php
require_once __DIR__ . '/includes/auth.php';

if (current_user()) {
    redirect(dashboard_for_role(current_user()['role']));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = (string) ($_POST['password'] ?? '');

    $stmt = db()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    $validPassword = $user && (
        password_verify($password, $user['password']) ||
        hash_equals((string) $user['password'], $password)
    );

    if ($validPassword) {
        $_SESSION['user_id'] = (int) $user['id'];
        flash('success', 'تم تسجيل الدخول بنجاح');
        redirect(dashboard_for_role($user['role']));
    }

    flash('error', 'بيانات الدخول غير صحيحة');
    redirect('login.php');
}

$pageTitle = 'تسجيل الدخول';
$layout = 'auth';
require_once __DIR__ . '/includes/header.php';
?>

<main class="auth-page">
    <section class="auth-panel">
        <a class="brand auth-brand" href="<?= e(url('index.php')) ?>">
            <?= logo_img('brand-logo auth-logo') ?>
            <span class="brand-text"><strong>إثمار</strong><small>دخول آمن للتجربة</small></span>
        </a>
        <h1>تسجيل الدخول</h1>
        <p>ادخل إلى لوحة التحكم المناسبة لدورك: صاحب مشروع، ممول، مورد، أو إدارة المنصة.</p>

        <form method="post" class="form-card">
            <label>
                البريد الإلكتروني
                <input type="email" name="email" required value="owner@ithmar.ps">
            </label>
            <label>
                كلمة المرور
                <input type="password" name="password" required value="123456">
            </label>
            <button class="btn btn-primary full" type="submit">تسجيل الدخول</button>
        </form>

        <a class="text-link" href="<?= e(url('signup.php')) ?>">إنشاء حساب جديد</a>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
