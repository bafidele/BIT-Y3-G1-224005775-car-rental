<?php
require 'config/db.php';
if (isAuthenticated()) { header('Location: index.php'); exit; }

$error = '';
$values = ['full_name' => '', 'email' => '', 'phone' => '', 'district_city' => '', 'id_no' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($values as $key => $_) $values[$key] = trim((string)($_POST[$key] ?? ''));
    $values['email'] = strtolower($values['email']);
    $password = (string)($_POST['password'] ?? '');

    if (!verifyCsrfToken()) {
        $error = 'Your session expired. Please submit the form again.';
    } elseif ($values['full_name'] === '' || strlen($values['full_name']) > 100 || !filter_var($values['email'], FILTER_VALIDATE_EMAIL) || strlen($values['email']) > 100) {
        $error = 'Enter your name and a valid email address.';
    } elseif ($values['phone'] === '' || strlen($values['phone']) > 20 || $values['district_city'] === '' || strlen($values['district_city']) > 50 || $values['id_no'] === '' || strlen($values['id_no']) > 16) {
        $error = 'Check your phone number, district/city, and ID number.';
    } elseif (strlen($password) < 4) {
        $error = 'Password must be at least 4 characters long.';
    } else {
        $staffEmail = $pdo->prepare('SELECT user_id FROM users WHERE email = ? LIMIT 1');
        $staffEmail->execute([$values['email']]);
        if ($staffEmail->fetch()) {
            $error = 'That email belongs to an administrator or staff account. Contact the administrator for help.';
        } else {
            $existingStmt = $pdo->prepare('SELECT customer_id, email, id_no, password_hash FROM customers WHERE email = ? OR id_no = ? LIMIT 1');
            $existingStmt->execute([$values['email'], $values['id_no']]);
            $existing = $existingStmt->fetch();

            if ($existing) {
                $sameDetails = strcasecmp((string)$existing['email'], $values['email']) === 0 && (string)$existing['id_no'] === $values['id_no'];
                if (!$sameDetails) {
                    $error = 'The email or ID number is already attached to a different customer record. Check the details or contact the administrator.';
                } elseif (!empty($existing['password_hash'])) {
                    $error = 'An account already exists for this email. Sign in or contact the administrator if you need help.';
                } else {
                    $claim = $pdo->prepare('UPDATE customers SET full_name = ?, phone = ?, district_city = ?, password_hash = ?, account_approved = 1 WHERE customer_id = ? AND password_hash IS NULL');
                    $claim->execute([
                        $values['full_name'],
                        $values['phone'],
                        $values['district_city'],
                        password_hash($password, PASSWORD_DEFAULT),
                        (int)$existing['customer_id'],
                    ]);
                    if ($claim->rowCount() === 1) {
                        $_SESSION['auth_notice'] = 'Your free customer account is ready. Sign in to request a car.';
                        header('Location: login.php');
                        exit;
                    }
                    $error = 'That customer record was updated already. Try signing in or contact the administrator.';
                }
            } else {
                $insert = $pdo->prepare('INSERT INTO customers (full_name, email, phone, district_city, id_no, password_hash, account_approved) VALUES (?, ?, ?, ?, ?, ?, 1)');
                $insert->execute([
                    $values['full_name'],
                    $values['email'],
                    $values['phone'],
                    $values['district_city'],
                    $values['id_no'],
                    password_hash($password, PASSWORD_DEFAULT),
                ]);
                $_SESSION['auth_notice'] = 'Your free customer account is ready. Sign in to request a car.';
                header('Location: login.php');
                exit;
            }
        }
    }
}
include 'includes/header.php';
?>

<section class="auth-panel">
    <h2>Create Customer Account</h2>
    <p class="muted">Creating an account is free. You can request a car after signing in; rental requests are confirmed after payment is checked by an administrator.</p>
    <?php if ($error !== ''): ?><div class="error"><?= e($error) ?></div><?php endif; ?>
    <form method="POST" class="form-vertical">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <label>Full name:</label>
        <input type="text" name="full_name" maxlength="100" autocomplete="name" value="<?= e($values['full_name']) ?>" required>
        <label>Email:</label>
        <input type="email" name="email" maxlength="100" autocomplete="email" value="<?= e($values['email']) ?>" required>
        <label>Phone number:</label>
        <input type="tel" name="phone" maxlength="20" autocomplete="tel" value="<?= e($values['phone']) ?>" required>
        <label>District / city:</label>
        <input type="text" name="district_city" maxlength="50" value="<?= e($values['district_city']) ?>" required>
        <label>National ID (maximum 16 characters):</label>
        <input type="text" name="id_no" maxlength="16" value="<?= e($values['id_no']) ?>" required>
        <label>Password (at least 4 characters):</label>
        <input type="password" name="password" autocomplete="new-password" required minlength="4">
        <button type="submit" class="btn-green">Register account</button>
    </form>
</section>

<?php include 'includes/footer.php'; ?>
