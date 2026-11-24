<?php
require 'config/db.php';
checkAuth();
require_once 'includes/receipt_pdf.php';

$isStaff = isStaffAccount();
$error = '';
$success = $_SESSION['rental_notice'] ?? null;
unset($_SESSION['rental_notice']);
$completedRentalsBefore = 0;
$selectedVehicleId = (int)($_POST['vehicle_id'] ?? $_GET['vehicle_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vehicleId = (int)($_POST['vehicle_id'] ?? 0);
    $customerId = $isStaff ? (int)($_POST['customer_id'] ?? 0) : (int)$_SESSION['customer_id'];
    $startDate = trim((string)($_POST['start_date'] ?? ''));
    $endDate = trim((string)($_POST['end_date'] ?? ''));
    $startTime = trim((string)($_POST['start_time'] ?? '09:00'));
    $returnTime = trim((string)($_POST['return_time'] ?? '09:00'));
    $start = DateTime::createFromFormat('!Y-m-d H:i', $startDate . ' ' . $startTime);
    $end = DateTime::createFromFormat('!Y-m-d H:i', $endDate . ' ' . $returnTime);

    if (!verifyCsrfToken()) {
        $error = 'Your session expired. Please reload and submit the form again.';
    } elseif (!$isStaff && (string)($_POST['terms_accepted'] ?? '') !== '1') {
        $error = 'Please review and accept the rental terms before sending your request.';
    } elseif (!$start || !$end || $start->format('Y-m-d H:i') !== $startDate . ' ' . $startTime || $end->format('Y-m-d H:i') !== $endDate . ' ' . $returnTime || $end <= $start) {
        $error = 'Enter valid pick-up and return times. The return time must be after pick-up.';
    } elseif ($vehicleId < 1 || $customerId < 1) {
        $error = 'Choose a customer and an available car.';
    } else {
        try {
            $pdo->beginTransaction();
            $vehicleStmt = $pdo->prepare("SELECT * FROM vehicles WHERE vehicle_id = ? AND status = 'available' FOR UPDATE");
            $vehicleStmt->execute([$vehicleId]);
            $vehicle = $vehicleStmt->fetch();
            $customerStmt = $pdo->prepare('SELECT * FROM customers WHERE customer_id = ? FOR UPDATE');
            $customerStmt->execute([$customerId]);
            $customer = $customerStmt->fetch();
            if (!$vehicle || !$customer) {
                throw new RuntimeException('That car is no longer available, or the customer account could not be found.');
            }

            $completedStmt = $pdo->prepare("SELECT COUNT(*) FROM rentals WHERE customer_id = ? AND status IN ('returned', 'completed')");
            $completedStmt->execute([$customerId]);
            $completedRentalsBefore = (int)$completedStmt->fetchColumn();
            $discountPercent = $completedRentalsBefore >= 10 ? 5 : 0;
            $billableDays = max(1, (int)ceil(($end->getTimestamp() - $start->getTimestamp()) / 86400));
            $rentalSubtotal = round($billableDays * (float)$vehicle['daily_rate'], 2);
            $discountAmount = round($rentalSubtotal * $discountPercent / 100, 2);
            $totalCost = round($rentalSubtotal - $discountAmount, 2);

            $rentalStatus = $isStaff ? 'active' : 'payment_pending';
            $termsAcceptedAt = $isStaff ? null : date('Y-m-d H:i:s');
            $insert = $pdo->prepare('INSERT INTO rentals (vehicle_id, customer_id, start_date, start_time, end_date, return_time, total_cost, discount_percent, discount_amount, status, terms_accepted_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $insert->execute([$vehicleId, $customerId, $startDate, $startTime . ':00', $endDate, $returnTime . ':00', $totalCost, $discountPercent, $discountAmount, $rentalStatus, $termsAcceptedAt]);
            $rentalId = (int)$pdo->lastInsertId();
            $pdo->prepare("UPDATE vehicles SET status = 'rented' WHERE vehicle_id = ? AND status = 'available'")->execute([$vehicleId]);
            $pdo->commit();

            $message = $isStaff
                ? 'Rental created for ' . $customer['full_name'] . '. Total: $' . number_format($totalCost, 2)
                : 'Car request sent. Total due: $' . number_format($totalCost, 2) . '. After paying, press “I have paid” on My Account. The administrator will check the payment before confirming the rental.';
            if ($discountPercent > 0) $message .= ' The 5% loyalty reward saved $' . number_format($discountAmount, 2) . '.';
            if ($isStaff) $message .= ' The PDF receipt is ready to download.';
            $_SESSION['rental_notice'] = ['success', $message, $rentalId];
            header('Location: add_rental.php');
            exit;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $error = $exception instanceof RuntimeException ? $exception->getMessage() : 'The rental could not be saved. Please try again.';
            if (!$exception instanceof RuntimeException) error_log('Rental creation failed: ' . $exception->getMessage());
        }
    }
}

$vehicles = $pdo->query("SELECT * FROM vehicles WHERE status = 'available' ORDER BY model")->fetchAll();
$customers = $isStaff ? $pdo->query('SELECT customer_id, full_name, id_no, email FROM customers ORDER BY full_name')->fetchAll() : [];
if (!$isStaff) {
    $completedStmt = $pdo->prepare("SELECT COUNT(*) FROM rentals WHERE customer_id = ? AND status IN ('returned', 'completed')");
    $completedStmt->execute([(int)$_SESSION['customer_id']]);
    $completedRentalsBefore = (int)$completedStmt->fetchColumn();
}
include 'includes/header.php';
?>

<div class="page-heading"><div><p class="eyebrow">RESERVATIONS</p><h1><?= $isStaff ? 'Create a rental' : 'Request a car' ?></h1></div><p>Set the exact pick-up and return time. Customer requests are confirmed after payment is checked by an administrator.</p></div>
<?php if ($error !== ''): ?><div class="error"><?= e($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="success"><?= e($success[1]) ?><?php if ($isStaff && !empty($success[2])): ?> <a href="receipt.php?rental_id=<?= (int)$success[2] ?>">Download receipt PDF</a><?php endif; ?></div><?php endif; ?>
<?php if (!$isStaff): ?>
    <div class="reward-notice">
        <strong>Completed rentals: <?= (int)$completedRentalsBefore ?></strong>
        <?php if ($completedRentalsBefore >= 10): ?>You receive 5% off this and future rentals.<?php else: ?>Complete <?= 10 - $completedRentalsBefore ?> more completed rental<?= ($completedRentalsBefore === 9) ? '' : 's' ?> to unlock 5% off future rentals.<?php endif; ?>
    </div>
<?php endif; ?>

<form method="POST" action="add_rental.php" class="form-vertical rental-form" id="rental-request-form">
    <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">
    <?php if (!$isStaff): ?><input type="hidden" name="terms_accepted" id="terms-accepted" value="<?= (string)($_POST['terms_accepted'] ?? '') === '1' ? '1' : '0' ?>"><?php endif; ?>
    <?php if ($isStaff): ?>
        <label>Customer:</label>
        <select name="customer_id" required><option value="">Choose a customer</option>
            <?php foreach ($customers as $customer): ?><option value="<?= (int)$customer['customer_id'] ?>"><?= e($customer['full_name']) ?> · <?= e($customer['email']) ?> · ID <?= e($customer['id_no']) ?></option><?php endforeach; ?>
        </select>
    <?php else: ?>
        <p class="muted">The rental will be created for your account.</p>
    <?php endif; ?>
    <label>Available car:</label>
    <select name="vehicle_id" required><option value="">Choose a car</option>
        <?php foreach ($vehicles as $vehicle): ?><option value="<?= (int)$vehicle['vehicle_id'] ?>" <?= $selectedVehicleId === (int)$vehicle['vehicle_id'] ? 'selected' : '' ?>><?= e($vehicle['model']) ?> (<?= e($vehicle['plate_no']) ?>) · $<?= number_format((float)$vehicle['daily_rate'], 2) ?>/day</option><?php endforeach; ?>
    </select>
    <div class="time-fields">
        <div><label>Pick-up date</label><input type="date" name="start_date" required></div>
        <div><label>Pick-up time</label><input type="time" name="start_time" value="09:00" required></div>
        <div><label>Return date</label><input type="date" name="end_date" required></div>
        <div><label>Return time</label><input type="time" name="return_time" value="09:00" required></div>
    </div>
    <?php if (!$isStaff): ?><button type="button" id="open-rental-terms" class="btn-blue terms-review-button">Read rental terms</button><?php endif; ?>
    <button type="submit" class="btn-blue"><?= $isStaff ? 'Create rental' : 'Send car request' ?></button>
    <?php if (!$isStaff): ?>
        <div class="terms-modal" id="rental-terms-modal" role="dialog" aria-modal="true" aria-labelledby="rental-terms-title" aria-hidden="true">
            <section class="terms-dialog">
                <h2 id="rental-terms-title">Rental Terms: Safe Use, Care &amp; Accidents</h2>
                <div class="terms-copy">
                    <p>By requesting this vehicle, you agree to use it lawfully and carefully, and to allow only the driver or drivers authorized in your rental agreement to operate it. Do not use the car for racing, towing, illegal activity, or any purpose that could damage it. Before departure, inspect the bodywork, tyres, lights, windows, fuel level, and interior, and promptly report any existing damage or warning lights. Keep the vehicle clean, secure, and locked when unattended. If a warning light appears, the vehicle overheats, or it becomes unsafe, stop in a safe place and contact the rental company before continuing. Do not arrange repairs or towing without approval unless needed to protect people from immediate danger.</p>
                    <p>If an accident occurs, first move to safety where possible and contact emergency services or police when needed. Notify the rental company as soon as possible. Exchange names, contact, vehicle, and insurance details with other parties; take photographs of the scene and damage; record witness details and any police or incident reference; and cooperate with the rental company and insurer. Do not leave the scene when it is unsafe or unlawful to do so. Return the car, keys, and documents at the agreed time and report new damage. You remain responsible for fines and costs arising from misuse or negligence as set out in the rental agreement and applicable law.</p>
                </div>
                <label class="terms-accept-label"><input type="checkbox" id="rental-terms-checkbox" <?= (string)($_POST['terms_accepted'] ?? '') === '1' ? 'checked' : '' ?>> I have read and agree to these rental terms.</label>
                <div class="terms-buttons">
                    <button type="button" id="accept-rental-terms" class="btn-green" disabled>I agree &amp; continue</button>
                    <button type="button" id="close-rental-terms" class="btn-red">Close</button>
                </div>
            </section>
        </div>
    <?php endif; ?>
</form>

<?php if (!$isStaff): ?>
<script>
(() => {
    const form = document.getElementById('rental-request-form');
    const modal = document.getElementById('rental-terms-modal');
    const checkbox = document.getElementById('rental-terms-checkbox');
    const accepted = document.getElementById('terms-accepted');
    const acceptButton = document.getElementById('accept-rental-terms');
    const openButton = document.getElementById('open-rental-terms');
    const closeButton = document.getElementById('close-rental-terms');
    if (accepted.value === '1') {
        checkbox.checked = true;
        openButton.textContent = 'Rental terms accepted';
    }
    acceptButton.disabled = !checkbox.checked;

    const openTerms = () => {
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        checkbox.focus();
    };
    const closeTerms = () => {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
    };

    openButton.addEventListener('click', openTerms);
    closeButton.addEventListener('click', closeTerms);
    checkbox.addEventListener('change', () => { acceptButton.disabled = !checkbox.checked; });
    acceptButton.addEventListener('click', () => {
        if (!checkbox.checked) return;
        accepted.value = '1';
        closeTerms();
        openButton.textContent = 'Rental terms accepted';
    });
    form.addEventListener('submit', (event) => {
        if (accepted.value !== '1') {
            event.preventDefault();
            openTerms();
        }
    });
    if (new URLSearchParams(window.location.search).get('show_terms') === '1') openTerms();
})();
</script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
