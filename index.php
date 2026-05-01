<?php
$pageTitle = 'الرئيسية';
$layout = 'landing';
require_once __DIR__ . '/includes/header.php';
?>

<main id="home">
    <section class="hero-section">
        <div class="hero-copy">
            <span class="section-badge">منصة تمويل جماعي ذكية</span>
            <h1>تمويل ذكي لمشاريع تنمو بثقة</h1>
            <p>
                إثمار منصة تمويل جماعي تربط المشاريع الصغيرة والنساء في فلسطين بالمؤسسات الداعمة،
                مع تتبع شفاف واستخدام آمن للأموال عبر QR.
            </p>
            <div class="hero-actions">
                <a class="btn btn-primary" href="<?= e(url('marketplace.php')) ?>">استعرض المشاريع</a>
                <a class="btn btn-outline" href="<?= e(url('signup.php')) ?>">ابدأ مشروعك</a>
            </div>
            <div class="hero-trust">
            </div>
        </div>
        <div class="hero-visual" aria-label="عرض مرئي لمنصة إثمار">
            <div class="hero-logo-card">
                <?= logo_img('hero-logo') ?>
                <div>
                    <strong>إثمار - Ithmar</strong>
                    <span>نمول اليوم… لنثمر غداً</span>
                </div>
            </div>
            <div class="visual-card wallet-card">
                <span>رصيد المحفظة</span>
                <strong>18,450 شيكل</strong>
                <small>جاهز للاستخدام عبر الموردين المعتمدين</small>
            </div>
            <div class="visual-card qr-card">
                <span class="qr-box">QR</span>
                <div>
                    <strong>دفع موجه</strong>
                    <small>مواد خام - مورد محلي</small>
                </div>
            </div>
            <div class="visual-card progress-card">
                <div class="card-row">
                    <span>مشروع عسل جنين</span>
                    <strong>72%</strong>
                </div>
                <div class="progress"><span style="width:72%"></span></div>
                <small>تقدم التمويل يظهر مباشرة للممولين</small>
            </div>
        </div>
    </section>

    <section class="section" id="how">
        <div class="section-heading">
            <span class="section-badge">كيف يعمل</span>
            <h2>مسار واضح من الفكرة إلى الأثر</h2>
        </div>
        <div class="feature-grid four">
            <article class="feature-card">
                <span class="feature-icon">1</span>
                <h3>أنشئ مشروعك</h3>
                <p>صاحب المشروع يضيف الفكرة، الهدف، المدينة، والأثر المتوقع.</p>
            </article>
            <article class="feature-card">
                <span class="feature-icon">2</span>
                <h3>احصل على التمويل</h3>
                <p>الممولون يدعمون المشاريع مباشرة مع متابعة نسبة التقدم.</p>
            </article>
            <article class="feature-card">
                <span class="feature-icon">3</span>
                <h3>استخدم التمويل عبر QR</h3>
                <p>الأموال تُصرف للموردين ضمن فئات واضحة ومحددة مسبقاً.</p>
            </article>
            <article class="feature-card">
                <span class="feature-icon">4</span>
                <h3>تابع المصاريف بشفافية</h3>
                <p>كل عملية تظهر في لوحة تتبع تساعد على بناء الثقة.</p>
            </article>
        </div>
    </section>

    <section class="section muted-section">
        <div class="section-heading">
            <span class="section-badge">لماذا إثمار</span>
            <h2>ثقة أعلى للممولين وفرصة أفضل للمشاريع</h2>
        </div>
        <div class="feature-grid">
            <article class="feature-card">
                <h3>تمويل موجه</h3>
                <p>كل حملة ترتبط بهدف واضح مثل المواد، المعدات، التسويق أو الخدمات.</p>
            </article>
            <article class="feature-card">
                <h3>شفافية كاملة</h3>
                <p>تقارير الصرف تساعد الممول على معرفة أين تم استخدام التمويل.</p>
            </article>
            <article class="feature-card">
                <h3>دعم للنساء</h3>
                <p>المنصة مصممة لتمكين الفئات المهمشة وأصحاب المبادرات المحلية.</p>
            </article>
            <article class="feature-card">
                <h3>تقليل سوء استخدام التمويل</h3>
                <p>المدفوعات عبر QR تتحول تلقائياً إلى سجل شفاف قابل للمراجعة.</p>
            </article>
        </div>
    </section>

    <section class="section stats-showcase">
        <div class="section-heading">
            <span class="section-badge">أرقام تجريبية للعرض</span>
            <h2>المشكلة واضحة، والتأثير قابل للقياس</h2>
        </div>
        <div class="impact-grid">
            <article><strong>4</strong><span>مشاريع فلسطينية صغيرة</span></article>
            <article><strong>43,400</strong><span>شيكل تمويل تجريبي</span></article>
            <article><strong>27</strong><span>سيدة ومستفيد مباشر</span></article>
            <article><strong>4</strong><span>عمليات QR موثقة</span></article>
        </div>
    </section>

    <section class="cta-section">
        <h2>ابدأ رحلتك مع إثمار اليوم</h2>
        <p>أنشئ حملة، استقبل التمويل، وادفع للموردين بثقة ووضوح.</p>
        <a class="btn btn-primary" href="<?= e(url('signup.php')) ?>">ابدأ الآن</a>
    </section>

    <footer class="landing-footer">
        <div class="brand footer-brand">
            <?= logo_img('brand-logo') ?>
            <span class="brand-text"><strong>إثمار</strong><small>تمويل بثقة وشفافية</small></span>
        </div>
        <p>نموذج هاكاثون يعمل محلياً باستخدام PHP وMySQL، بدون بوابات دفع حقيقية.</p>
    </footer>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
