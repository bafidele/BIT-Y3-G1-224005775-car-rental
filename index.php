<?php
require 'config/db.php';
checkAuth();

$isStaff = isStaffAccount();
$currentCustomer = null;
$completedRentals = 0;
$notice = $_SESSION['customer_notice'] ?? null;
unset($_SESSION['customer_notice']);

if (!$isStaff && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_payment'])) {
    if (!verifyCsrfToken()) {
        $_SESSION['customer_notice'] = ['error', 'Your session expired. Please try again.'];
    } else {
        $rentalId = (int)($_POST['rental_id'] ?? 0);
        $confirm = $pdo->prepare("UPDATE rentals SET status = 'payment_submitted', payment_submitted_at = NOW() WHERE rental_id = ? AND customer_id = ? AND status = 'payment_pending'");
        $confirm->execute([$rentalId, (int)$_SESSION['customer_id']]);
        $_SESSION['customer_notice'] = $confirm->rowCount() === 1
            ? ['success', 'Payment confirmation sent. An administrator will check your payment and approve the rental.']
            : ['error', 'This request could not be updated. It may already have been submitted or approved.'];
    }
    header('Location: index.php');
    exit;
}

if ($isStaff) {
    $sql = 'SELECT r.*, v.plate_no, v.model, c.full_name FROM rentals r JOIN vehicles v ON r.vehicle_id = v.vehicle_id JOIN customers c ON r.customer_id = c.customer_id ORDER BY r.rental_id DESC';
    $rentals = $pdo->query($sql)->fetchAll();
} else {
    $customerStmt = $pdo->prepare('SELECT customer_id, full_name, email, phone, district_city, profile_picture FROM customers WHERE customer_id = ? LIMIT 1');
    $customerStmt->execute([(int)$_SESSION['customer_id']]);
    $currentCustomer = $customerStmt->fetch();
    if (!$currentCustomer) {
        $_SESSION = [];
        session_destroy();
        header('Location: login.php');
        exit;
    }

    $rentalStmt = $pdo->prepare('SELECT r.*, v.plate_no, v.model, c.full_name FROM rentals r JOIN vehicles v ON r.vehicle_id = v.vehicle_id JOIN customers c ON r.customer_id = c.customer_id WHERE r.customer_id = ? ORDER BY r.rental_id DESC');
    $rentalStmt->execute([(int)$_SESSION['customer_id']]);
    $rentals = $rentalStmt->fetchAll();
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM rentals WHERE customer_id = ? AND status IN ('returned', 'completed')");
    $countStmt->execute([(int)$_SESSION['customer_id']]);
    $completedRentals = (int)$countStmt->fetchColumn();
}

$profilePicture = (string)($currentCustomer['profile_picture'] ?? '');
$validProfilePicture = preg_match('#^profiles/[a-f0-9]{32}\.(jpg|png|webp)$#', $profilePicture) === 1;
include 'includes/header.php';
?>

<?php if (!$isStaff): ?>
    <div class="page-heading">
        <div><p class="eyebrow">MY ACCOUNT</p><h1>My Account Dashboard</h1></div>
        <a class="button-link btn-blue" href="profile.php">Edit my profile</a>
    </div>
    <section class="account-overview">
        <div class="account-person">
            <?php if ($validProfilePicture): ?>
                <img class="profile-avatar" src="uploads/<?= e($profilePicture) ?>" alt="<?= e($currentCustomer['full_name']) ?> profile picture">
            <?php else: ?>
                <div class="profile-avatar profile-avatar-placeholder" aria-hidden="true"><?= e(strtoupper(substr((string)$currentCustomer['full_name'], 0, 1))) ?></div>
            <?php endif; ?>
            <div>
                <h2><?= e($currentCustomer['full_name']) ?></h2>
                <p><?= e($currentCustomer['email']) ?></p>
                <p><?= e($currentCustomer['phone']) ?> · <?= e($currentCustomer['district_city']) ?></p>
            </div>
        </div>
        <div class="reward-card">
            <strong><?= $completedRentals ?> completed rental<?= $completedRentals === 1 ? '' : 's' ?></strong>
            <?php if ($completedRentals >= 10): ?>
                <p>Your 5% loyalty discount is active for this and every future rental.</p>
            <?php else: ?>
                <p>Complete <?= 10 - $completedRentals ?> more rental<?= (10 - $completedRentals) === 1 ? '' : 's' ?> to unlock 5% off future rentals.</p>
            <?php endif; ?>
        </div>
    </section>
    <?php if ($notice): ?><div class="<?= e($notice[0]) ?>"><?= e($notice[1]) ?></div><?php endif; ?>
<?php else: ?>
    <div class="page-heading">
        <div><p class="eyebrow">ADMINISTRATION</p><h1>Rental dashboard</h1></div>
        <a class="button-link btn-blue" href="customers.php">Manage customers &amp; receipts</a>
    </div>
<?php endif; ?>

<h2><?= $isStaff ? 'All rentals' : 'My rental history' ?></h2>
<?php if (!$rentals): ?>
    <div class="empty-state">No rentals to show yet.</div>
<?php else: ?>
    <div class="table-scroll"><table>
        <thead><tr><th>Request</th><th>Vehicle</th><?php if ($isStaff): ?><th>Customer</th><?php endif; ?><th>Pick-up</th><th>Return</th><th>Cost</th><th>Status / completed at</th><th>Next step</th></tr></thead>
        <tbody>
        <?php foreach ($rentals as $rental): ?>
            <?php $isComplete = in_array((string)$rental['status'], ['returned', 'completed'], true); ?>
            <tr>
                <td>R-<?= str_pad((string)$rental['rental_id'], 6, '0', STR_PAD_LEFT) ?></td>
                <td><?= e($rental['model']) ?> (<?= e($rental['plate_no']) ?>)</td>
                <?php if ($isStaff): ?><td><?= e($rental['full_name']) ?></td><?php endif; ?>
                <td><?= e(date('d M Y', strtotime((string)$rental['start_date']))) ?> <?= e(substr((string)$rental['start_time'], 0, 5)) ?></td>
                <td><?= e(date('d M Y', strtotime((string)$rental['end_date']))) ?> <?= e(substr((string)$rental['return_time'], 0, 5)) ?></td>
                <td>$<?= number_format((float)$rental['total_cost'], 2) ?>
                    <?php if ((float)($rental['discount_amount'] ?? 0) > 0): ?><small class="discount-note">5% loyalty reward applied</small><?php endif; ?>
                </td>
                <td><?= $isComplete ? 'Completed' : match ((string)$rental['status']) {
                        'payment_pending' => 'Awaiting payment',
                        'payment_submitted' => 'Payment submitted · awaiting admin check',
                        'active' => 'Rental approved',
                        'cancelled' => 'Request cancelled',
                        default => e(ucfirst((string)$rental['status'])),
                    } ?>
                    <?php if ($isComplete && !empty($rental['completed_at'])): ?><small class="completed-time"><?= e(date('d M Y, H:i', strtotime((string)$rental['completed_at']))) ?></small><?php endif; ?>
                </td>
                <td>
                    <?php if (isCustomerAccount() && $rental['status'] === 'payment_pending'): ?>
                        <p class="muted">Pay using the details provided by the administrator, then confirm below. <a href="https://wa.me/250789339641" target="_blank" rel="noopener noreferrer">Contact us for payment details</a>.</p>
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
                            <input type="hidden" name="rental_id" value="<?= (int)$rental['rental_id'] ?>">
                            <button type="submit" name="confirm_payment" value="1" class="btn-blue">I have paid</button>
                        </form>
                    <?php elseif ($rental['status'] === 'payment_submitted'): ?>
                        <span class="badge pending">Waiting for payment check</span>
                    <?php elseif (in_array((string)$rental['status'], ['active', 'returned', 'completed'], true)): ?>
                        <a class="button-link btn-green" href="receipt.php?rental_id=<?= (int)$rental['rental_id'] ?>">Download PDF</a>
                    <?php elseif ($isStaff && in_array((string)$rental['status'], ['payment_pending', 'payment_submitted'], true)): ?>
                        <a class="button-link btn-blue" href="customers.php">Review request</a>
                    <?php else: ?>
                        <span class="muted">No receipt available</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
