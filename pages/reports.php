<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_role(['admin']);

// إحصائيات عامة
$generalStats = [
    'users' => db()->query('SELECT COUNT(*) total FROM users')->fetch()['total'],
    'projects' => db()->query('SELECT COUNT(*) total FROM projects')->fetch()['total'],
    'funding' => db()->query('SELECT COALESCE(SUM(amount), 0) total FROM funding_transactions')->fetch()['total'],
    'qr_payments' => db()->query('SELECT COUNT(*) total FROM qr_payments')->fetch()['total'],
];

// إحصائيات المشاريع
$projectStats = db()->query('
    SELECT 
        status,
        COUNT(*) count,
        COALESCE(SUM(current_funding), 0) funding,
        COALESCE(SUM(funding_goal), 0) goal
    FROM projects
    GROUP BY status
')->fetchAll();

// إحصائيات الأدوار
$roleStats = db()->query('
    SELECT 
        role,
        COUNT(*) count
    FROM users
    GROUP BY role
')->fetchAll();

// أكثر المشاريع تمويلاً
$topProjects = db()->query('
    SELECT p.id, p.title, p.current_funding, p.funding_goal, p.status, u.full_name,
           COUNT(ft.id) fundersCount
    FROM projects p
    JOIN users u ON u.id = p.owner_id
    LEFT JOIN funding_transactions ft ON ft.project_id = p.id
    GROUP BY p.id
    ORDER BY p.current_funding DESC
    LIMIT 10
')->fetchAll();

// أكثر الفئات تمويلاً
$categoryStats = db()->query('
    SELECT 
        category,
        COUNT(*) projectCount,
        COALESCE(SUM(current_funding), 0) totalFunding
    FROM projects
    GROUP BY category
    ORDER BY totalFunding DESC
')->fetchAll();

// آخر الأنشطة
$recentActivity = db()->query("
    SELECT 'تمويل' type, ft.amount, ft.created_at, p.title FROM funding_transactions ft
    JOIN projects p ON p.id = ft.project_id
    UNION ALL
    SELECT 'QR' type, qp.amount, qp.created_at, p.title FROM qr_payments qp
    JOIN projects p ON p.id = qp.project_id
    ORDER BY created_at DESC
    LIMIT 15
")->fetchAll();

// توزيع المخاطر
$riskStats = db()->query('
    SELECT 
        risk_score,
        COUNT(*) count
    FROM projects
    GROUP BY risk_score
')->fetchAll();

$pageTitle = 'التقارير والإحصائيات';
require_once __DIR__ . '/../includes/header.php';
?>

<section class="welcome-panel admin-reports">
    <div>
        <span class="section-badge">إدارة ومراقبة</span>
        <h2>التقارير والإحصائيات الشاملة</h2>
        <p>مراقبة شاملة لأداء المنصة والمشاريع والتمويلات.</p>
    </div>
</section>

<div class="content-wrapper">
    <!-- الإحصائيات العامة -->
    <section class="reports-section">
        <h3>الإحصائيات العامة</h3>
        <div class="stats-grid">
            <div class="stat-card featured">
                <span class="stat-icon">👥</span>
                <span class="stat-label">إجمالي المستخدمين</span>
                <strong><?= e((string)$generalStats['users']) ?></strong>
            </div>
            <div class="stat-card">
                <span class="stat-icon">💼</span>
                <span class="stat-label">إجمالي المشاريع</span>
                <strong><?= e((string)$generalStats['projects']) ?></strong>
            </div>
            <div class="stat-card">
                <span class="stat-icon">💰</span>
                <span class="stat-label">إجمالي التمويلات</span>
                <strong><?= e(money($generalStats['funding'])) ?></strong>
            </div>
            <div class="stat-card">
                <span class="stat-icon">✅</span>
                <span class="stat-label">عمليات QR</span>
                <strong><?= e((string)$generalStats['qr_payments']) ?></strong>
            </div>
        </div>
    </section>

    <div class="reports-grid">
        <!-- حالة المشاريع -->
        <div class="card">
            <h3>توزيع حالة المشاريع</h3>
            <div class="status-breakdown">
                <?php foreach ($projectStats as $stat): ?>
                <div class="status-item">
                    <div class="status-header">
                        <span class="status-name"><?= e(status_label($stat['status'])) ?></span>
                        <span class="status-count"><?= e((string)$stat['count']) ?></span>
                    </div>
                    <div class="status-progress">
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?= e((string)($stat['count'] / max(1, array_reduce($projectStats, fn($c, $s) => $c + $s['count'], 0)) * 100)) ?>%"></div>
                        </div>
                        <span class="status-funding">
                            <?= e(money($stat['funding'])) ?> / <?= e(money($stat['goal'])) ?>
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- توزيع الأدوار -->
        <div class="card">
            <h3>توزيع المستخدمين حسب النوع</h3>
            <div class="roles-breakdown">
                <?php foreach ($roleStats as $role): ?>
                <div class="role-item">
                    <span class="role-name"><?= e(role_label($role['role'])) ?></span>
                    <div class="role-bar">
                        <div class="role-count"><?= e((string)$role['count']) ?></div>
                    </div>
                    <span class="role-percent">
                        <?= e((string)round(($role['count'] / $generalStats['users']) * 100)) ?>%
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- توزيع المخاطر -->
        <div class="card">
            <h3>تقييم المخاطر</h3>
            <div class="risk-breakdown">
                <?php 
                    $risks = ['low' => 0, 'medium' => 0, 'high' => 0];
                    foreach ($riskStats as $risk) {
                        $risks[$risk['risk_score']] = $risk['count'];
                    }
                ?>
                <div class="risk-item risk-low">
                    <span class="risk-label">
                        <strong><?= e((string)$risks['low']) ?></strong>
                        مشروع منخفض المخاطر
                    </span>
                </div>
                <div class="risk-item risk-medium">
                    <span class="risk-label">
                        <strong><?= e((string)$risks['medium']) ?></strong>
                        مشروع متوسط المخاطر
                    </span>
                </div>
                <div class="risk-item risk-high">
                    <span class="risk-label">
                        <strong><?= e((string)$risks['high']) ?></strong>
                        مشروع مرتفع المخاطر
                    </span>
                </div>
            </div>
        </div>

        <!-- الفئات الأكثر نشاطاً -->
        <div class="card">
            <h3>الفئات الأكثر تمويلاً</h3>
            <div class="categories-table">
                <div class="table-header">
                    <span>الفئة</span>
                    <span>المشاريع</span>
                    <span>التمويل</span>
                </div>
                <?php foreach ($categoryStats as $cat): ?>
                <div class="table-row">
                    <span class="category-name"><?= e($cat['category']) ?></span>
                    <span class="project-count"><?= e((string)$cat['projectCount']) ?></span>
                    <span class="category-funding"><?= e(money($cat['totalFunding'])) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- أكثر المشاريع نشاطاً -->
    <div class="card">
        <h3>أكثر 10 مشاريع تمويلاً</h3>
        <div class="projects-table">
            <div class="table-header">
                <span>المشروع</span>
                <span>المالك</span>
                <span>التمويل</span>
                <span>الهدف</span>
                <span>النسبة</span>
                <span>الممولون</span>
                <span>الحالة</span>
            </div>
            <?php foreach ($topProjects as $proj): ?>
            <div class="table-row">
                <span class="project-title"><?= e($proj['title']) ?></span>
                <span class="owner"><?= e($proj['full_name']) ?></span>
                <span class="funding"><?= e(money($proj['current_funding'])) ?></span>
                <span class="goal"><?= e(money($proj['funding_goal'])) ?></span>
                <span class="percent">
                    <?= e((string)progress_percent($proj['current_funding'], $proj['funding_goal'])) ?>%
                </span>
                <span class="funders"><?= e((string)$proj['fundersCount']) ?></span>
                <span class="status <?= e(status_class($proj['status'])) ?>">
                    <?= e(status_label($proj['status'])) ?>
                </span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- سجل الأنشطة الأخيرة -->
    <div class="card">
        <h3>آخر الأنشطة على المنصة</h3>
        <div class="activity-feed">
            <?php foreach ($recentActivity as $activity): ?>
            <div class="activity-item">
                <span class="activity-type <?= e(strtolower($activity['type'])) ?>">
                    <?= e($activity['type']) ?>
                </span>
                <div class="activity-content">
                    <span class="activity-text">
                        <?= e($activity['type']) ?> بقيمة <?= e(money($activity['amount'])) ?>
                        على مشروع "<?= e($activity['title']) ?>"
                    </span>
                    <span class="activity-time">
                        <?= e(date('d/m/Y H:i', strtotime($activity['created_at']))) ?>
                    </span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<style>
.reports-section {
    margin-bottom: 3rem;
}

.reports-section h3 {
    margin-bottom: 1.5rem;
    color: var(--color-dark);
}

.reports-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 2rem;
    margin: 2rem 0;
}

.status-breakdown {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.status-item {
    padding: 1rem;
    background: var(--color-bg-light);
    border-radius: 8px;
}

.status-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.75rem;
}

.status-name {
    font-weight: 600;
    color: var(--color-dark);
}

.status-count {
    background: var(--color-primary);
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.85rem;
}

.status-progress {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.progress-bar {
    width: 100%;
    height: 8px;
    background: white;
    border-radius: 4px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #1F7A5C, #8E7DBE);
}

.status-funding {
    font-size: 0.85rem;
    color: var(--color-text-secondary);
}

.roles-breakdown {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.role-item {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.role-name {
    min-width: 150px;
    color: var(--color-dark);
    font-weight: 500;
}

.role-bar {
    flex: 1;
    height: 30px;
    background: linear-gradient(90deg, #1F7A5C, #8E7DBE);
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.role-count {
    color: white;
    font-weight: 600;
    font-size: 0.85rem;
}

.role-percent {
    min-width: 50px;
    text-align: right;
    color: var(--color-text-secondary);
    font-weight: 500;
}

.risk-breakdown {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
}

.risk-item {
    padding: 1.5rem;
    border-radius: 8px;
    text-align: center;
}

.risk-item.risk-low {
    background: #e8f5e9;
    color: #2e7d32;
}

.risk-item.risk-medium {
    background: #fff3e0;
    color: #f57c00;
}

.risk-item.risk-high {
    background: #ffebee;
    color: #c62828;
}

.risk-label {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.risk-label strong {
    font-size: 1.5rem;
}

.categories-table,
.projects-table {
    display: flex;
    flex-direction: column;
}

.table-header {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
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
    grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
    gap: 1rem;
    padding: 1rem;
    border-bottom: 1px solid var(--color-border);
    align-items: center;
    font-size: 0.9rem;
}

.table-row:last-child {
    border-bottom: none;
}

.projects-table .table-header {
    grid-template-columns: 2fr 1.5fr 1fr 1fr 1fr 1fr 1fr;
}

.projects-table .table-row {
    grid-template-columns: 2fr 1.5fr 1fr 1fr 1fr 1fr 1fr;
}

.project-title {
    font-weight: 500;
    color: var(--color-dark);
}

.owner {
    color: var(--color-text-secondary);
}

.funding {
    color: var(--color-primary);
    font-weight: 600;
}

.goal {
    color: var(--color-text-secondary);
}

.percent {
    font-weight: 600;
    color: var(--color-primary);
}

.funders {
    background: var(--color-primary);
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    text-align: center;
    font-weight: 600;
}

.status {
    padding: 0.3rem 0.6rem;
    border-radius: 4px;
    font-size: 0.8rem;
    font-weight: 600;
}

.status.status-approved {
    background: #e8f5e9;
    color: #2e7d32;
}

.status.status-pending {
    background: #fff3e0;
    color: #f57c00;
}

.status.status-funded {
    background: #e1f5fe;
    color: #0277bd;
}

.activity-feed {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.activity-item {
    display: flex;
    gap: 1rem;
    padding: 1rem;
    background: var(--color-bg-light);
    border-radius: 8px;
    align-items: flex-start;
}

.activity-type {
    padding: 0.3rem 0.6rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 700;
    white-space: nowrap;
    text-transform: uppercase;
}

.activity-type.funding {
    background: #e8f5e9;
    color: #2e7d32;
}

.activity-type.qr {
    background: #e1f5fe;
    color: #0277bd;
}

.activity-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.activity-text {
    color: var(--color-dark);
    font-size: 0.9rem;
}

.activity-time {
    font-size: 0.8rem;
    color: var(--color-text-secondary);
}

@media (max-width: 1024px) {
    .reports-grid {
        grid-template-columns: 1fr;
    }

    .risk-breakdown {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .table-header,
    .table-row {
        grid-template-columns: 1fr !important;
    }

    .projects-table .table-header,
    .projects-table .table-row {
        grid-template-columns: 1fr !important;
    }

    .activity-item {
        flex-direction: column;
    }
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
