<?php
require 'config/db.php';
checkAuth();

$isStaff = isStaffAccount();
$error = '';
$notice = (string)($_SESSION['password_notice'] ?? '');
unset($_SESSION['password_notice']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = (string)($_POST['current_password'] ?? '');
    $newPassword = (string)($_POST['new_password'] ?? '');
    $confirmPassword = (string)($_POST['confirm_password'] ?? '');

    if (!verifyCsrfToken()) {
        $error = 'Your session expired. Please submit the form again.';
    } elseif (strlen($newPassword) < 12) {
        $error = 'Choose a new password with at least 12 characters.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'The new password and confirmation do not match.';
    } else {
        if ($isStaff) {
            $accountStmt = $pdo->prepare('SELECT password_hash FROM users WHERE user_id = ? LIMIT 1');
            $accountStmt->execute([(int)$_SESSION['user_id']]);
        } else {
            $accountStmt = $pdo->prepare('SELECT password_hash FROM customers WHERE customer_id = ? LIMIT 1');
            $accountStmt->execute([(int)$_SESSION['customer_id']]);
        }
        $account = $accountStmt->fetch();

        if (!$account || !password_verify($currentPassword, (string)$account['password_hash'])) {
            $error = 'The current password is incorrect.';
        } else {
            $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            if ($isStaff) {
                $update = $pdo->prepare('UPDATE users SET password_hash = ?, must_change_password = 0 WHERE user_id = ?');
                $update->execute([$passwordHash, (int)$_SESSION['user_id']]);
            } else {
                $update = $pdo->prepare('UPDATE customers SET password_hash = ? WHERE customer_id = ?');
                $update->execute([$passwordHash, (int)$_SESSION['customer_id']]);
            }

            unset($_SESSION['must_change_password']);
            $_SESSION['password_notice'] = 'Your password has been changed.';
            session_regenerate_id(true);
            header('Location: index.php');
            exit;
        }
    }
}

include 'includes/header.php';
?>

<div class="page-heading">
    <div><p class="eyebrow">ACCOUNT SECURITY</p><h1>Change password</h1></div>
    <?php if (empty($_SESSION['must_change_password'])): ?><a class="button-link btn-blue" href="index.php">Back to dashboard</a><?php endif; ?>
</div>
<?php if ($notice !== ''): ?><div class="success"><?= e($notice) ?></div><?php endif; ?>
<?php if ($error !== ''): ?><div class="error"><?= e($error) ?></div><?php endif; ?>

<section class="auth-panel">
    <?php if (!empty($_SESSION['must_change_password'])): ?><p class="muted">For security, set a new password before continuing.</p><?php endif; ?>
    <form method="POST" class="form-vertical">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <label>Current password</label>
        <input type="password" name="current_password" autocomplete="current-password" required>
        <label>New password (at least 12 characters)</label>
        <input type="password" name="new_password" autocomplete="new-password" minlength="12" required>
        <label>Confirm new password</label>
        <input type="password" name="confirm_password" autocomplete="new-password" minlength="12" required>
        <button type="submit" class="btn-blue">Save new password</button>
    </form>
</section>

<?php include 'includes/footer.php'; ?>
