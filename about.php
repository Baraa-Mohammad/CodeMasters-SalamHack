<?php
$layout = 'landing';
$pageTitle = 'من نحن';
require_once __DIR__ . '/includes/header.php';
?>

<section class="about-hero container">
    <div>
        <span class="section-badge">فريق العمل</span>
        <h1>من نحن</h1>
        <p>نحن فريق <strong>Code Masters</strong>، نعمل على بناء حلول مالية رقمية عملية تخدم المجتمع المحلي وتدعم نمو المشاريع الصغيرة بثقة وشفافية.</p>
    </div>
    <div class="about-logo-wrap">
        <?= logo_img('about-logo') ?>
    </div>
</section>

<section class="about-team container">
    <article class="team-card">
        <h3>براءه محمد</h3>
        <p>Software Full Stack Developer</p>
    </article>
    <article class="team-card">
        <h3>جنى قاسم</h3>
        <p>BackEnd Developer</p>
    </article>
    <article class="team-card">
        <h3>ماسه مجاهد</h3>
        <p>UI Developer</p>
    </article>
</section>

<section class="about-project container">
    <h2>عن مشروع إثمار</h2>
    <p>إثمار منصة تمويل جماعي ذكية تمكّن النساء وأصحاب المشاريع الصغيرة من الحصول على تمويل وإدارته بشفافية عبر محفظة رقمية ودفع موجه باستخدام QR.</p>
    <a class="btn btn-primary" href="<?= e(url('marketplace.php')) ?>">استعراض المشاريع</a>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
