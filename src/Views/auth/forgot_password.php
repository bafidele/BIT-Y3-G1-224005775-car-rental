<section class="auth-panel">
    <h2>Forgot password?</h2>
    <p class="muted">Enter the email address on your customer account. If it matches an account, we’ll send a secure reset link that expires in 60 minutes.</p>
    <?php if ($notice !== ''): ?><div class="success" role="status"><?= e($notice) ?></div><?php endif; ?>
    <?php if ($error !== ''): ?><div class="error" role="alert"><?= e($error) ?></div><?php endif; ?>
    <?php if ($devLink !== ''): ?>
        <div class="success local-reset-link" role="status">
            Local XAMPP mail is not configured. Use this development-only link:
            <a href="<?= e($devLink) ?>">Reset customer password</a>
        </div>
    <?php endif; ?>
    <form method="POST" class="form-vertical">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <label for="reset-email">Customer email</label>
        <input id="reset-email" type="email" name="email" maxlength="100" autocomplete="email" required>
        <button type="submit" class="btn-blue">Send reset link</button>
    </form>
    <p class="auth-register-prompt"><a href="login.php">Back to login</a></p>
</section>
