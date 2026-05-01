<?php
require_once __DIR__ . '/includes/auth.php';
$user = require_role(['project_owner']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $purpose = trim($_POST['purpose'] ?? '');
    $fundingGoal = (float) ($_POST['funding_goal'] ?? 0);
    $city = trim($_POST['city'] ?? '');
    $beneficiary = trim($_POST['beneficiary'] ?? '');
    $impactSummary = trim($_POST['impact_summary'] ?? '');

    if ($title === '' || $description === '' || $category === '' || $purpose === '' || $city === '' || $impactSummary === '' || $fundingGoal <= 0) {
        flash('error', 'يرجى تعبئة جميع الحقول الأساسية للمشروع');
        redirect('create-project.php');
    }

    if ($beneficiary !== '') {
        $impactSummary = "الفئة المستفيدة: {$beneficiary}\n{$impactSummary}";
    }

    $riskScore = calculate_risk_score($fundingGoal, $purpose);
    $status = 'pending';

    $stmt = db()->prepare(
        'INSERT INTO projects (owner_id, title, description, funding_goal, current_funding, purpose, category, city, impact_summary, status, risk_score)
         VALUES (?, ?, ?, ?, 0, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([(int) $user['id'], $title, $description, $fundingGoal, $purpose, $category, $city, $impactSummary, $status, $riskScore]);

    flash('success', 'تم إنشاء المشروع بنجاح وهو الآن بانتظار مراجعة الإدارة');
    redirect(dashboard_for_role($user['role']));
}

$pageTitle = 'إنشاء مشروع';
require_once __DIR__ . '/includes/header.php';
?>

<section class="panel form-panel">
    <div class="panel-header">
        <div>
            <h2>بيانات المشروع</h2>
            <p>أضف معلومات واضحة تساعد الممولين على فهم الفكرة والحاجة والأثر المتوقع.</p>
        </div>
    </div>

    <form method="post" class="form-grid">
        <label>
            اسم المشروع
            <input type="text" name="title" required placeholder="مثال: مطبخ منزلي لإنتاج المأكولات الصحية">
        </label>
        <label>
            التصنيف
            <input type="text" name="category" required placeholder="غذاء، زراعة، حرف، تقنية">
        </label>

        <label class="full-field">
            وصف المشروع
            <textarea name="description" rows="5" required placeholder="اشرح الفكرة، المشكلة، وكيف سيُستخدم التمويل"></textarea>
        </label>

        <label>
            الهدف من التمويل
            <select name="purpose" required>
                <option value="مواد">مواد</option>
                <option value="معدات">معدات</option>
                <option value="تسويق">تسويق</option>
                <option value="خدمات">خدمات</option>
            </select>
        </label>
        <label>
            المبلغ المطلوب
            <input type="number" name="funding_goal" min="100" step="50" required placeholder="12000">
        </label>
        <label>
            المدينة
            <input type="text" name="city" required placeholder="رام الله">
        </label>
        <label>
            الفئة المستفيدة
            <input type="text" name="beneficiary" placeholder="نساء، عائلات، شباب">
        </label>

        <label class="full-field">
            وصف الأثر المتوقع
            <textarea name="impact_summary" rows="4" required placeholder="فرص عمل، زيادة دخل، دعم إنتاج محلي"></textarea>
        </label>

        <button class="btn btn-primary full-field" type="submit">نشر المشروع</button>
    </form>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
