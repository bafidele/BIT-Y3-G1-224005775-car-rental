<section class="auth-panel">
    <h2>Reset customer password</h2>
    <?php if ($notice !== ''): ?><div class="success" role="status"><?= e($notice) ?></div><?php endif; ?>
    <?php if ($error !== ''): ?><div class="error" role="alert"><?= e($error) ?></div><?php endif; ?>
    <?php if ($tokenValid): ?>
        <form method="POST" class="form-vertical">
            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
            <input type="hidden" name="token" value="<?= e($token) ?>">
            <label for="new-password">New password (at least 12 characters)</label>
            <input id="new-password" type="password" name="password" minlength="12" autocomplete="new-password" required>
            <label for="confirm-password">Confirm new password</label>
            <input id="confirm-password" type="password" name="confirm_password" minlength="12" autocomplete="new-password" required>
            <button type="submit" class="btn-blue">Save new password</button>
        </form>
    <?php elseif ($notice !== ''): ?>
        <p class="auth-register-prompt"><a href="login.php">Go to login</a></p>
    <?php else: ?>
        <p class="auth-register-prompt"><a href="forgot_password.php">Request a new reset link</a></p>
    <?php endif; ?>
</section>
