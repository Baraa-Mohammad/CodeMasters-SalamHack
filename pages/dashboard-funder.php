<?php
require_once __DIR__ . '/../includes/auth.php';
$user = require_role(['funder']);
ensure_wallet((int) $user['id']);

$statsStmt = db()->prepare('SELECT COUNT(*) funded_count, COALESCE(SUM(amount), 0) total_amount FROM funding_transactions WHERE funder_id = ?');
$statsStmt->execute([(int) $user['id']]);
$stats = $statsStmt->fetch() ?: ['funded_count' => 0, 'total_amount' => 0];

$projectsStmt = db()->prepare(
    "SELECT p.id, p.title, p.city, p.category, p.status, p.risk_score, p.funding_goal, p.current_funding, u.full_name owner_name,
            COALESCE(SUM(ft.amount), 0) my_funding
     FROM projects p
     JOIN users u ON u.id = p.owner_id
     LEFT JOIN funding_transactions ft ON ft.project_id = p.id AND ft.funder_id = ?
     WHERE EXISTS (SELECT 1 FROM funding_transactions x WHERE x.project_id = p.id AND x.funder_id = ?)
     GROUP BY p.id, p.title, p.city, p.category, p.status, p.risk_score, p.funding_goal, p.current_funding, u.full_name
     ORDER BY p.created_at DESC"
);
$projectsStmt->execute([(int) $user['id'], (int) $user['id']]);
$projects = $projectsStmt->fetchAll() ?: [];

$transactionsStmt = db()->prepare(
    "SELECT ft.amount, ft.created_at, p.title
     FROM funding_transactions ft
     JOIN projects p ON p.id = ft.project_id
     WHERE ft.funder_id = ?
     ORDER BY ft.created_at DESC
     LIMIT 8"
);
$transactionsStmt->execute([(int) $user['id']]);
$transactions = $transactionsStmt->fetchAll() ?: [];

$categoriesStmt = db()->prepare(
    "SELECT COALESCE(NULLIF(p.category,''), 'غير مصنف') category, COALESCE(SUM(ft.amount), 0) total_amount
     FROM funding_transactions ft
     JOIN projects p ON p.id = ft.project_id
     WHERE ft.funder_id = ?
     GROUP BY COALESCE(NULLIF(p.category,''), 'غير مصنف')
     ORDER BY total_amount DESC"
);
$categoriesStmt->execute([(int) $user['id']]);
$categories = $categoriesStmt->fetchAll() ?: [];

$suggested = db()->query(
    "SELECT p.id, p.title, p.city, p.category, p.risk_score, p.funding_goal, p.current_funding, p.description
     FROM projects p
     WHERE p.status = 'approved'
     ORDER BY FIELD(p.risk_score, 'low', 'medium', 'high'), p.created_at DESC
     LIMIT 4"
)->fetchAll() ?: [];

$pageTitle = 'لوحة الممول';
require_once __DIR__ . '/../includes/header.php';
?>

<section class="welcome-panel funder-welcome">
    <div>
        <span class="section-badge">مسار الممول</span>
        <h2>أهلاً <?= e($user['full_name']) ?>، متابعة التمويل أصبحت أوضح.</h2>
        <p>اختر المشاريع المناسبة، راقب التقدم، وتابع أثر مساهماتك بشكل شفاف.</p>
    </div>
    <div class="quick-actions">
        <a class="btn btn-primary" href="<?= e(url('marketplace.php')) ?>">استعراض المشاريع</a>
        <a class="btn btn-light" href="<?= e(url('tracking.php')) ?>">عرض تقارير الشفافية</a>
    </div>
</section>

<section class="stats-grid">
    <article class="stat-card featured"><span>إجمالي التمويل</span><strong><?= e(money((float) $stats['total_amount'])) ?></strong></article>
    <article class="stat-card"><span>عدد التمويلات</span><strong><?= e((string) $stats['funded_count']) ?></strong></article>
    <article class="stat-card"><span>المشاريع المدعومة</span><strong><?= e((string) count($projects)) ?></strong></article>
    <article class="stat-card"><span>متوسط التمويل</span><strong><?= e(money(count($projects) ? ((float) $stats['total_amount'] / count($projects)) : 0)) ?></strong></article>
</section>

<section class="two-col">
    <article class="panel">
        <div class="panel-head"><h3>المشاريع التي دعمتها</h3></div>
        <?php if (!$projects): ?>
            <p class="empty-state">لم تقم بتمويل أي مشروع بعد.</p>
        <?php else: ?>
            <div class="list">
                <?php foreach ($projects as $p): ?>
                    <div class="item">
                        <div class="item-top">
                            <div>
                                <strong><?= e($p['title']) ?></strong>
                                <small><?= e($p['owner_name']) ?> • <?= e($p['city']) ?></small>
                            </div>
                            <span class="badge <?= e(risk_class((string) $p['risk_score'])) ?>"><?= e(risk_label((string) $p['risk_score'])) ?></span>
                        </div>
                        <div class="progress"><span style="width:<?= e((string) progress_percent((float) $p['current_funding'], (float) $p['funding_goal'])) ?>%"></span></div>
                        <div class="item-bottom">
                            <span>تمويلك: <?= e(money((float) $p['my_funding'])) ?></span>
                            <a href="<?= e(url('project-details.php?id=' . $p['id'])) ?>">عرض التفاصيل</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </article>

    <aside class="panel">
        <div class="panel-head"><h3>توزيع تمويلك</h3></div>
        <?php if (!$categories): ?>
            <p class="empty-state">لا يوجد بيانات بعد.</p>
        <?php else: ?>
            <div class="cats">
                <?php foreach ($categories as $c): ?>
                    <?php $pct = (float) $stats['total_amount'] > 0 ? ((float) $c['total_amount'] / (float) $stats['total_amount']) * 100 : 0; ?>
                    <div class="cat">
                        <div><strong><?= e($c['category']) ?></strong><span><?= e(money((float) $c['total_amount'])) ?></span></div>
                        <div class="progress thin"><span style="width:<?= e((string) round($pct, 1)) ?>%"></span></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </aside>
</section>

<section class="panel">
    <div class="panel-head"><h3>آخر التمويلات</h3></div>
    <?php if (!$transactions): ?>
        <p class="empty-state">لا توجد عمليات حتى الآن.</p>
    <?php else: ?>
        <div class="table">
            <div class="row head"><span>المشروع</span><span>المبلغ</span><span>التاريخ</span></div>
            <?php foreach ($transactions as $t): ?>
                <div class="row"><span><?= e($t['title']) ?></span><span><?= e(money((float) $t['amount'])) ?></span><span><?= e(date('d/m/Y', strtotime((string) $t['created_at']))) ?></span></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php if ($suggested): ?>
<section class="panel">
    <div class="panel-head"><h3>مشاريع مقترحة</h3><a href="<?= e(url('marketplace.php')) ?>">كل المشاريع</a></div>
    <div class="suggested">
        <?php foreach ($suggested as $p): ?>
            <a class="s-card" href="<?= e(url('project-details.php?id=' . $p['id'])) ?>">
                <strong><?= e($p['title']) ?></strong>
                <small><?= e($p['city']) ?> • <?= e($p['category']) ?></small>
                <p><?= e(excerpt((string) $p['description'], 90)) ?></p>
            </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<style>
.quick-actions{display:flex;gap:.75rem;flex-wrap:wrap}
.two-col{display:grid;grid-template-columns:2fr 1fr;gap:1rem;margin-top:1rem}
.panel{background:#fff;border:1px solid #e8e4d8;border-radius:18px;padding:1rem;margin-top:1rem}
.panel-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:.75rem}
.list,.cats{display:flex;flex-direction:column;gap:.75rem}
.item{border:1px solid #ece8dc;border-radius:14px;padding:.75rem}
.item-top,.item-bottom{display:flex;justify-content:space-between;align-items:center;gap:.75rem}
.item-top small{display:block;color:#65706c}
.progress{height:10px;background:#eef1ec;border-radius:99px;overflow:hidden;margin:.6rem 0}
.progress span{display:block;height:100%;background:linear-gradient(90deg,#1F7A5C,#D4AF37)}
.progress.thin{height:7px}
.cat>div:first-child{display:flex;justify-content:space-between;align-items:center}
.table .row{display:grid;grid-template-columns:1.6fr 1fr 1fr;gap:.5rem;padding:.55rem 0;border-bottom:1px solid #f0ede4}
.table .row.head{font-weight:700;color:#2b4a41}
.suggested{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.75rem}
.s-card{border:1px solid #ece8dc;border-radius:12px;padding:.75rem;text-decoration:none;color:inherit;transition:.2s}
.s-card:hover{transform:translateY(-2px);box-shadow:0 10px 18px rgba(24,53,43,.08)}
.s-card small{display:block;color:#68736f;margin:.3rem 0}
.s-card p{margin:0;color:#33423f}
@media (max-width: 992px){.two-col{grid-template-columns:1fr}.suggested{grid-template-columns:1fr}}
@media (max-width: 768px){.table .row{grid-template-columns:1fr}.item-top,.item-bottom{flex-direction:column;align-items:flex-start}}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
