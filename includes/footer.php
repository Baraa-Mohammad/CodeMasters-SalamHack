<?php $layout = $layout ?? 'dashboard'; ?>
<?php if ($layout === 'dashboard'): ?>
            <footer class="site-footer app-footer">
                <div class="footer-col">
                    <h3>إثمار</h3>
                    <p>إثمار منصة تمويل جماعي ذكية تمكّن النساء وأصحاب المشاريع الصغيرة من الحصول على تمويل وإدارته بشفافية عبر محفظة رقمية ودفع موجه باستخدام QR.</p>
                </div>
                <div class="footer-col">
                    <h4>Code Masters</h4>
                    <ul>
                        <li>براءه محمد — Software Full Stack Developer</li>
                        <li>جنى قاسم — BackEnd Developer</li>
                        <li>ماسه مجاهد — UI Developer</li>
                    </ul>
                </div>
            </footer>
        </main>
    </div>
<?php else: ?>
    <footer class="site-footer landing-footer">
        <div class="footer-col">
            <h3>إثمار</h3>
            <p>إثمار منصة تمويل جماعي ذكية تمكّن النساء وأصحاب المشاريع الصغيرة من الحصول على تمويل وإدارته بشفافية عبر محفظة رقمية ودفع موجه باستخدام QR.</p>
        </div>
        <div class="footer-col">
            <h4>Code Masters</h4>
            <ul>
                <li>براءه محمد — Software Full Stack Developer</li>
                <li>جنى قاسم — BackEnd Developer</li>
                <li>ماسه مجاهد — UI Developer</li>
            </ul>
        </div>
    </footer>
<?php endif; ?>
<script src="<?= e(url('assets/js/main.js')) ?>"></script>
<script src="<?= e(url('assets/js/dashboard.js')) ?>"></script>
</body>
</html>
