<?php
require 'config/db.php';
if (isAuthenticated()) { header('Location: index.php'); exit; }

$error = '';
$notice = (string)($_SESSION['auth_notice'] ?? '');
unset($_SESSION['auth_notice']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken()) {
        $error = 'Your session expired. Please refresh the page and try again.';
    } else {
        $login = strtolower(trim((string)($_POST['email'] ?? '')));
        $password = (string)($_POST['password'] ?? '');

    if (strcasecmp($login, 'admin') === 0) {
        $staffStmt = $pdo->query("SELECT user_id, full_name, email, password_hash, role, must_change_password FROM users WHERE role = 'admin' ORDER BY user_id LIMIT 1");
    } else {
        $staffStmt = $pdo->prepare("SELECT user_id, full_name, email, password_hash, role, must_change_password FROM users WHERE email = ? LIMIT 1");
        $staffStmt->execute([$login]);
    }
    $staff = $staffStmt->fetch();

    if ($staff && password_verify($password, (string)$staff['password_hash'])) {
        session_regenerate_id(true);
        unset($_SESSION['customer_id']);
        $_SESSION['user_id'] = (int)$staff['user_id'];
        $_SESSION['full_name'] = $staff['full_name'];
        $_SESSION['role'] = $staff['role'];
        $_SESSION['must_change_password'] = (int)$staff['must_change_password'] === 1;
        header('Location: ' . ($_SESSION['must_change_password'] ? 'change_password.php' : 'index.php'));
        exit;
    }

    $customerStmt = $pdo->prepare('SELECT customer_id, full_name, email, password_hash FROM customers WHERE email = ? LIMIT 1');
    $customerStmt->execute([$login]);
    $customer = $customerStmt->fetch();

    if ($customer && !empty($customer['password_hash']) && password_verify($password, (string)$customer['password_hash'])) {
        session_regenerate_id(true);
        unset($_SESSION['user_id']);
        $_SESSION['customer_id'] = (int)$customer['customer_id'];
        $_SESSION['full_name'] = $customer['full_name'];
        $_SESSION['role'] = 'customer';
        header('Location: index.php');
        exit;
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
include 'includes/header.php';
?>

<section class="auth-panel">
    <h2>Login</h2>
    <?php if ($notice !== ''): ?><div class="success"><?= e($notice) ?></div><?php endif; ?>
    <?php if ($error !== ''): ?><div class="error"><?= e($error) ?></div><?php endif; ?>
    <form method="POST" class="form-vertical">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <label>Email or admin username:</label>
        <input type="text" name="email" autocomplete="username" placeholder="you@example.com or admin" required>
        <label>Password:</label>
        <input type="password" name="password" autocomplete="current-password" required>
        <button type="submit" class="btn-blue">Login</button>
    </form>
    <p class="auth-register-prompt"><a href="forgot_password.php">Forgot your customer password?</a></p>
    <p class="auth-register-prompt">New to our platform? <a href="register.php">Create an account</a></p>
</section>
<?php include 'includes/footer.php'; ?>
