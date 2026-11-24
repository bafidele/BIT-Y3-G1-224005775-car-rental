<?php
require 'config/db.php';
checkAuth();
if (!isCustomerAccount()) {
    http_response_code(403);
    exit('Only customer accounts have an editable customer profile.');
}

$customerId = (int)$_SESSION['customer_id'];
$stmt = $pdo->prepare('SELECT customer_id, full_name, email, phone, district_city, id_no, profile_picture FROM customers WHERE customer_id = ? LIMIT 1');
$stmt->execute([$customerId]);
$customer = $stmt->fetch();
if (!$customer) {
    http_response_code(404);
    exit('Account not found.');
}

$values = [
    'full_name' => (string)$customer['full_name'],
    'email' => (string)$customer['email'],
    'phone' => (string)$customer['phone'],
    'district_city' => (string)$customer['district_city'],
    'id_no' => (string)$customer['id_no'],
];
$error = '';
$notice = (string)($_SESSION['profile_notice'] ?? '');
unset($_SESSION['profile_notice']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($values as $key => $_) $values[$key] = trim((string)($_POST[$key] ?? ''));
    $values['email'] = strtolower($values['email']);

    if (!verifyCsrfToken()) {
        $error = 'Your session expired. Please submit the form again.';
    } elseif ($values['full_name'] === '' || strlen($values['full_name']) > 100 || !filter_var($values['email'], FILTER_VALIDATE_EMAIL) || strlen($values['email']) > 100) {
        $error = 'Enter a valid name and email address.';
    } elseif ($values['phone'] === '' || strlen($values['phone']) > 20 || $values['district_city'] === '' || strlen($values['district_city']) > 50 || $values['id_no'] === '' || strlen($values['id_no']) > 16) {
        $error = 'Check the length of your phone, district/city, or ID number.';
    } else {
        $duplicate = $pdo->prepare('SELECT customer_id FROM customers WHERE (email = ? OR id_no = ?) AND customer_id <> ? LIMIT 1');
        $duplicate->execute([$values['email'], $values['id_no'], $customerId]);
        $staffDuplicate = $pdo->prepare('SELECT user_id FROM users WHERE email = ? LIMIT 1');
        $staffDuplicate->execute([$values['email']]);
        if ($duplicate->fetch() || $staffDuplicate->fetch()) {
            $error = 'That email address or ID number is already used by another account.';
        } else {
            $newImagePath = null;
            $upload = $_FILES['profile_picture'] ?? null;
            if (is_array($upload) && (int)$upload['error'] !== UPLOAD_ERR_NO_FILE) {
                if ((int)$upload['error'] !== UPLOAD_ERR_OK || (int)$upload['size'] > 3 * 1024 * 1024) {
                    $error = 'Choose an image smaller than 3 MB and try again.';
                } else {
                    $imageInfo = @getimagesize((string)$upload['tmp_name']);
                    $fileInfo = new finfo(FILEINFO_MIME_TYPE);
                    $mime = $fileInfo->file((string)$upload['tmp_name']);
                    $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
                    if (!$imageInfo || !isset($allowedTypes[$mime]) || $imageInfo['mime'] !== $mime || $imageInfo[0] > 6000 || $imageInfo[1] > 6000 || ($imageInfo[0] * $imageInfo[1]) > 20000000) {
                        $error = 'Upload a valid JPG, PNG, or WEBP image.';
                    } else {
                        $directory = __DIR__ . '/uploads/profiles';
                        if (!is_dir($directory) && !@mkdir($directory, 0755, true) && !is_dir($directory)) {
                            $error = 'The profile picture folder could not be created.';
                        } else {
                            $fileName = bin2hex(random_bytes(16)) . '.' . $allowedTypes[$mime];
                            if (!move_uploaded_file((string)$upload['tmp_name'], $directory . '/' . $fileName)) {
                                $error = 'The profile picture could not be saved. Please try again.';
                            } else {
                                $newImagePath = 'profiles/' . $fileName;
                            }
                        }
                    }
                }
            }

            if ($error === '') {
                $picturePath = $newImagePath ?? (string)($customer['profile_picture'] ?? '');
                try {
                    $update = $pdo->prepare('UPDATE customers SET full_name = ?, email = ?, phone = ?, district_city = ?, id_no = ?, profile_picture = ? WHERE customer_id = ?');
                    $update->execute([
                        $values['full_name'],
                        $values['email'],
                        $values['phone'],
                        $values['district_city'],
                        $values['id_no'],
                        $picturePath !== '' ? $picturePath : null,
                        $customerId,
                    ]);
                    $_SESSION['full_name'] = $values['full_name'];
                    if ($newImagePath !== null) {
                        $oldPath = (string)($customer['profile_picture'] ?? '');
                        if (preg_match('#^profiles/[a-f0-9]{32}\.(jpg|png|webp)$#', $oldPath)) {
                            $oldAbsolutePath = __DIR__ . '/uploads/' . $oldPath;
                            if (is_file($oldAbsolutePath)) @unlink($oldAbsolutePath);
                        }
                    }
                    $_SESSION['profile_notice'] = 'Your profile has been updated.';
                    header('Location: profile.php');
                    exit;
                } catch (Throwable $exception) {
                    if ($newImagePath !== null) @unlink(__DIR__ . '/uploads/' . $newImagePath);
                    error_log('Profile update failed: ' . $exception->getMessage());
                    $error = 'Your profile could not be saved. Please try again.';
                }
            } elseif ($newImagePath !== null) {
                @unlink(__DIR__ . '/uploads/' . $newImagePath);
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt->execute([$customerId]);
    $customer = $stmt->fetch();
}
$picture = (string)($customer['profile_picture'] ?? '');
$validPicture = preg_match('#^profiles/[a-f0-9]{32}\.(jpg|png|webp)$#', $picture) === 1;
include 'includes/header.php';
?>

<div class="page-heading">
    <div><p class="eyebrow">MY ACCOUNT</p><h1>Edit my profile</h1></div>
    <a class="button-link btn-blue" href="index.php">Back to My Account</a>
</div>
<?php if ($notice !== ''): ?><div class="success"><?= e($notice) ?></div><?php endif; ?>
<?php if ($error !== ''): ?><div class="error"><?= e($error) ?></div><?php endif; ?>

<section class="customer-card profile-editor">
    <div class="profile-edit-picture">
        <?php if ($validPicture): ?>
            <img class="profile-avatar" src="uploads/<?= e($picture) ?>" alt="Current profile picture">
        <?php else: ?>
            <div class="profile-avatar profile-avatar-placeholder" aria-hidden="true"><?= e(strtoupper(substr($values['full_name'], 0, 1))) ?></div>
        <?php endif; ?>
        <p class="muted">JPG, PNG, or WEBP up to 3 MB</p>
    </div>
    <form method="POST" enctype="multipart/form-data" class="form-vertical profile-form">
        <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
        <input type="hidden" name="MAX_FILE_SIZE" value="3145728">
        <label>Profile picture
            <input type="file" name="profile_picture" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
        </label>
        <label>Full name
            <input type="text" name="full_name" maxlength="100" value="<?= e($values['full_name']) ?>" required>
        </label>
        <label>Email address
            <input type="email" name="email" maxlength="100" value="<?= e($values['email']) ?>" autocomplete="email" required>
        </label>
        <label>Phone number
            <input type="tel" name="phone" maxlength="20" value="<?= e($values['phone']) ?>" required>
        </label>
        <label>District / city
            <input type="text" name="district_city" maxlength="50" value="<?= e($values['district_city']) ?>" required>
        </label>
        <label>National ID
            <input type="text" name="id_no" maxlength="16" value="<?= e($values['id_no']) ?>" required>
        </label>
        <button type="submit" class="btn-blue">Save profile</button>
    </form>
</section>

<?php include 'includes/footer.php'; ?>
