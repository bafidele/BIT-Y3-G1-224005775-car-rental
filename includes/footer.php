    </div>
    <footer class="site-footer">
        <div class="footer-inner">
            <?php $siteLinks = require __DIR__ . '/../config/site_links.php'; ?>
            <div class="footer-newsletter-row">
                <section class="footer-newsletter" aria-labelledby="newsletter-heading">
                    <h2 id="newsletter-heading">Subscribe to our newsletter</h2>
                    <p>Get car rental news and service updates by email. The button opens an email request for you to send.</p>
                    <?php if (!empty($siteLinks['newsletter'])): ?>
                        <a class="footer-action-button" href="<?= e($siteLinks['newsletter']) ?>">Subscribe by email</a>
                    <?php else: ?>
                        <span class="footer-action-button is-disabled" aria-disabled="true">Signup link needed</span>
                    <?php endif; ?>
                </section>
                <?php if (isAuthenticated()): ?>
                    <form class="footer-logout" method="post" action="logout.php">
                        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                        <button class="footer-logout-link" type="submit">Logout</button>
                    </form>
                <?php endif; ?>
            </div>
            <section class="footer-social" aria-labelledby="social-heading">
                <h2 id="social-heading">Follow us</h2>
                <div class="footer-social-links">
                    <?php foreach (($siteLinks['social'] ?? []) as $platform => $url): ?>
                        <?php if (is_string($url) && filter_var($url, FILTER_VALIDATE_URL) && strtolower((string)parse_url($url, PHP_URL_SCHEME)) === 'https'): ?>
                            <a class="footer-social-button" href="<?= e($url) ?>" target="_blank" rel="noopener noreferrer"><?= e($platform) ?></a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <?php if (empty($siteLinks['social']['LinkedIn'])): ?><span class="footer-social-button is-disabled" aria-disabled="true">LinkedIn · link needed</span><?php endif; ?>
                </div>
            </section>
            <div class="footer-support">
                <h2>Support</h2>
                <a href="mailto:bafidele2024@gmail.com">bafidele2024@gmail.com</a>
                <div class="footer-phone-links">
                    <a href="tel:+250789339641">0789 339 641</a>
                    <a href="tel:+250790175697">0790 175 697</a>
                </div>
            </div>
            <p class="footer-credit">Developed by BAZIMAZIKI FIDELE · Copyright © 2026. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
