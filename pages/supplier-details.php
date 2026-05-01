<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_login();

$supplierId = (int)($_GET['id'] ?? 0);
if ($supplierId <= 0) {
    flash('error', 'معرف المورد غير صحيح');
    redirect('marketplace.php');
}

// جلب معلومات المورد
$supplierStmt = db()->prepare('SELECT s.*, u.full_name, u.phone, u.email FROM suppliers s JOIN users u ON u.id = s.user_id WHERE s.id = ? LIMIT 1');
$supplierStmt->execute([$supplierId]);
$supplier = $supplierStmt->fetch();

if (!$supplier) {
    flash('error', 'المورد غير موجود');
    redirect('marketplace.php');
}

// إحصائيات المورد
$statsStmt = db()->prepare('
    SELECT 
        COUNT(*) total_transactions,
        COALESCE(SUM(amount), 0) total_amount,
        COUNT(DISTINCT project_id) projects_served
    FROM qr_payments
    WHERE supplier_id = ?
');
$statsStmt->execute([$supplierId]);
$stats = $statsStmt->fetch();

// آخر المعاملات
$transactionsStmt = db()->prepare('
    SELECT qp.*, p.title, u.full_name owner_name
    FROM qr_payments qp
    JOIN projects p ON p.id = qp.project_id
    JOIN users u ON u.id = qp.owner_id
    WHERE qp.supplier_id = ?
    ORDER BY qp.created_at DESC
    LIMIT 10
');
$transactionsStmt->execute([$supplierId]);
$transactions = $transactionsStmt->fetchAll();

// المشاريع المخدومة
$projectsStmt = db()->prepare('
    SELECT DISTINCT p.id, p.title, p.owner_id, p.category, u.full_name
    FROM qr_payments qp
    JOIN projects p ON p.id = qp.project_id
    JOIN users u ON u.id = p.owner_id
    WHERE qp.supplier_id = ?
    ORDER BY qp.created_at DESC
');
$projectsStmt->execute([$supplierId]);
$projects = $projectsStmt->fetchAll();

// تحليل الفئات
$categoriesStmt = db()->prepare('
    SELECT qp.category, COUNT(*) transaction_count, COALESCE(SUM(qp.amount), 0) total_amount
    FROM qr_payments qp
    WHERE qp.supplier_id = ?
    GROUP BY qp.category
    ORDER BY total_amount DESC
');
$categoriesStmt->execute([$supplierId]);
$categories = $categoriesStmt->fetchAll();

$pageTitle = 'تفاصيل المورد';
require_once __DIR__ . '/../includes/header.php';
?>

<section class="breadcrumb-nav">
    <a href="<?= e(url('marketplace.php')) ?>">المتجر</a>
    <span>/</span>
    <span><?= e($supplier['business_name']) ?></span>
</section>

<div class="content-wrapper supplier-details">
    <div class="supplier-header-card">
        <div class="supplier-avatar">
            <span><?= e(substr($supplier['business_name'], 0, 1)) ?></span>
        </div>
        <div class="supplier-info">
            <h1><?= e($supplier['business_name']) ?></h1>
            <p class="supplier-category"><?= e(category_icon($supplier['category'])) ?> <?= e($supplier['category']) ?></p>
            <div class="supplier-contact">
                <span>📞 <?= e($supplier['phone']) ?></span>
                <span>📧 <?= e($supplier['email']) ?></span>
            </div>
        </div>
    </div>

    <div class="two-column-layout">
        <div class="main-column">
            <!-- إحصائيات المورد -->
            <div class="card stats-section">
                <h3>إحصائيات الأداء</h3>
                <div class="stats-grid-small">
                    <div class="stat-box">
                        <span class="stat-label">إجمالي المعاملات</span>
                        <strong class="stat-value"><?= e((string)$stats['total_transactions']) ?></strong>
                    </div>
                    <div class="stat-box">
                        <span class="stat-label">إجمالي المبلغ المحول</span>
                        <strong class="stat-value"><?= e(money($stats['total_amount'])) ?></strong>
                    </div>
                    <div class="stat-box">
                        <span class="stat-label">المشاريع المخدومة</span>
                        <strong class="stat-value"><?= e((string)$stats['projects_served']) ?></strong>
                    </div>
                    <div class="stat-box">
                        <span class="stat-label">متوسط المعاملة</span>
                        <strong class="stat-value">
                            <?= e(money($stats['total_transactions'] > 0 ? $stats['total_amount'] / $stats['total_transactions'] : 0)) ?>
                        </strong>
                    </div>
                </div>
            </div>

            <!-- آخر المعاملات -->
            <div class="card">
                <h3>آخر المعاملات</h3>
                <?php if (empty($transactions)): ?>
                    <div class="empty-state">
                        <p>لا توجد معاملات بعد</p>
                    </div>
                <?php else: ?>
                    <div class="transactions-table">
                        <div class="table-header">
                            <span>المشروع</span>
                            <span>المالك</span>
                            <span>الفئة</span>
                            <span>المبلغ</span>
                            <span>التاريخ</span>
                        </div>
                        <?php foreach ($transactions as $trans): ?>
                        <div class="table-row">
                            <span class="project-name"><?= e($trans['title']) ?></span>
                            <span class="owner-name"><?= e($trans['owner_name']) ?></span>
                            <span class="category-tag"><?= e($trans['category']) ?></span>
                            <span class="amount"><?= e(money($trans['amount'])) ?></span>
                            <span class="date"><?= e(date('d/m/Y', strtotime($trans['created_at']))) ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- المشاريع المخدومة -->
            <?php if (!empty($projects)): ?>
            <div class="card">
                <h3>المشاريع المدعومة</h3>
                <div class="projects-grid">
                    <?php foreach ($projects as $proj): ?>
                    <div class="project-card">
                        <h4><?= e($proj['title']) ?></h4>
                        <p class="owner"><?= e($proj['full_name']) ?></p>
                        <p class="category"><?= e($proj['category']) ?></p>
                        <a href="<?= e(url('project-details.php?id=' . $proj['id'])) ?>" class="link">
                            عرض المشروع →
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- الشريط الجانبي -->
        <div class="sidebar">
            <!-- معلومات الاتصال -->
            <div class="card contact-card">
                <h3>معلومات الاتصال</h3>
                <div class="contact-info">
                    <div class="contact-item">
                        <span class="label">الاسم:</span>
                        <span class="value"><?= e($supplier['full_name']) ?></span>
                    </div>
                    <div class="contact-item">
                        <span class="label">البريد الإلكتروني:</span>
                        <a href="mailto:<?= e($supplier['email']) ?>"><?= e($supplier['email']) ?></a>
                    </div>
                    <div class="contact-item">
                        <span class="label">الهاتف:</span>
                        <a href="tel:<?= e($supplier['phone']) ?>"><?= e($supplier['phone']) ?></a>
                    </div>
                    <div class="contact-item">
                        <span class="label">رمز QR:</span>
                        <span class="qr-code"><?= e($supplier['qr_code']) ?></span>
                    </div>
                </div>
            </div>

            <!-- توزيع الفئات -->
            <?php if (!empty($categories)): ?>
            <div class="card">
                <h3>توزيع الفئات</h3>
                <div class="categories-list">
                    <?php foreach ($categories as $cat): ?>
                    <div class="category-item">
                        <span class="name"><?= e($cat['category']) ?></span>
                        <span class="count"><?= e((string)$cat['transaction_count']) ?></span>
                        <span class="amount"><?= e(money($cat['total_amount'])) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- الوضع -->
            <div class="card status-card">
                <h3>الحالة</h3>
                <div class="status-badge status-active">نشط وموثوق</div>
                <p class="status-description">
                    هذا المورد موثوق به ويتعامل مع إثمار. تم التحقق من بيانات الاتصال والمعاملات.
                </p>
            </div>
        </div>
    </div>
</div>

<style>
.breadcrumb-nav {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 2rem;
    font-size: 0.9rem;
    color: var(--color-text-secondary);
}

.breadcrumb-nav a {
    color: var(--color-primary);
    text-decoration: none;
}

.breadcrumb-nav a:hover {
    text-decoration: underline;
}

.supplier-details {
    margin-top: 2rem;
}

.supplier-header-card {
    display: flex;
    gap: 2rem;
    padding: 2rem;
    background: linear-gradient(135deg, #f0f7f4, #f9f5fe);
    border-radius: 12px;
    margin-bottom: 2rem;
    align-items: center;
}

.supplier-avatar {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    background: linear-gradient(135deg, #1F7A5C, #8E7DBE);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 2.5rem;
    font-weight: 700;
    flex-shrink: 0;
}

.supplier-info h1 {
    margin: 0 0 0.5rem 0;
    color: var(--color-dark);
}

.supplier-category {
    display: inline-block;
    background: var(--color-primary);
    color: white;
    padding: 0.4rem 0.8rem;
    border-radius: 20px;
    font-size: 0.85rem;
    margin-bottom: 0.75rem;
}

.supplier-contact {
    display: flex;
    gap: 1.5rem;
    font-size: 0.9rem;
    color: var(--color-text-secondary);
}

.two-column-layout {
    display: grid;
    grid-template-columns: 1fr 320px;
    gap: 2rem;
}

.stats-grid-small {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
}

.stat-box {
    padding: 1rem;
    background: var(--color-bg-light);
    border-radius: 8px;
    text-align: center;
}

.stat-label {
    display: block;
    font-size: 0.85rem;
    color: var(--color-text-secondary);
    margin-bottom: 0.25rem;
}

.stat-value {
    display: block;
    font-size: 1.5rem;
    color: var(--color-primary);
    font-weight: 700;
}

.transactions-table {
    display: flex;
    flex-direction: column;
}

.table-header {
    display: grid;
    grid-template-columns: 1.5fr 1fr 1fr 1fr 0.8fr;
    gap: 1rem;
    padding: 1rem;
    background: var(--color-bg-light);
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.85rem;
    margin-bottom: 1rem;
}

.table-row {
    display: grid;
    grid-template-columns: 1.5fr 1fr 1fr 1fr 0.8fr;
    gap: 1rem;
    padding: 1rem;
    border-bottom: 1px solid var(--color-border);
    align-items: center;
}

.table-row:last-child {
    border-bottom: none;
}

.project-name {
    color: var(--color-dark);
    font-weight: 500;
}

.owner-name {
    color: var(--color-text-secondary);
    font-size: 0.9rem;
}

.category-tag {
    background: #e8f5e9;
    color: #2e7d32;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.8rem;
}

.amount {
    color: var(--color-primary);
    font-weight: 600;
}

.date {
    color: var(--color-text-secondary);
    font-size: 0.85rem;
}

.projects-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 1rem;
}

.project-card {
    border: 1px solid var(--color-border);
    border-radius: 8px;
    padding: 1rem;
    text-align: center;
}

.project-card h4 {
    margin: 0 0 0.5rem 0;
    color: var(--color-dark);
}

.project-card .owner {
    font-size: 0.85rem;
    color: var(--color-text-secondary);
    margin: 0.25rem 0;
}

.project-card .category {
    font-size: 0.8rem;
    color: var(--color-primary);
    margin: 0;
}

.project-card .link {
    display: inline-block;
    color: var(--color-primary);
    text-decoration: none;
    font-weight: 500;
    margin-top: 0.75rem;
}

.contact-info {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.contact-item {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.contact-item .label {
    font-size: 0.8rem;
    color: var(--color-text-secondary);
    font-weight: 500;
}

.contact-item .value,
.contact-item a {
    color: var(--color-dark);
    text-decoration: none;
}

.contact-item a:hover {
    color: var(--color-primary);
}

.qr-code {
    background: var(--color-bg-light);
    padding: 0.5rem;
    border-radius: 4px;
    font-family: monospace;
    font-size: 0.85rem;
}

.categories-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.category-item {
    padding: 0.75rem;
    background: var(--color-bg-light);
    border-radius: 6px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.category-item .name {
    flex: 1;
    color: var(--color-dark);
    font-weight: 500;
}

.category-item .count {
    background: var(--color-primary);
    color: white;
    padding: 0.2rem 0.4rem;
    border-radius: 4px;
    font-size: 0.75rem;
    margin: 0 0.5rem;
}

.category-item .amount {
    color: var(--color-primary);
    font-weight: 600;
    font-size: 0.9rem;
}

.status-badge {
    display: inline-block;
    padding: 0.4rem 0.8rem;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
    margin-bottom: 1rem;
}

.status-badge.status-active {
    background: #e8f5e9;
    color: #2e7d32;
}

.status-description {
    font-size: 0.9rem;
    color: var(--color-text-secondary);
    line-height: 1.5;
}

.empty-state {
    text-align: center;
    padding: 2rem 1rem;
    color: var(--color-text-secondary);
}

@media (max-width: 1024px) {
    .two-column-layout {
        grid-template-columns: 1fr;
    }

    .supplier-header-card {
        flex-direction: column;
        text-align: center;
    }
}

@media (max-width: 768px) {
    .stats-grid-small {
        grid-template-columns: 1fr;
    }

    .table-header,
    .table-row {
        grid-template-columns: 1fr;
    }

    .supplier-contact {
        flex-direction: column;
        gap: 0.5rem;
    }

    .projects-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
