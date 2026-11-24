<?php
require 'config/db.php';
checkAuth();
require_once 'includes/receipt_pdf.php';

$rentalId = filter_input(INPUT_GET, 'rental_id', FILTER_VALIDATE_INT);
if (!$rentalId) {
    http_response_code(400);
    exit('Invalid receipt request.');
}

$sql = 'SELECT r.*, v.plate_no, v.model, v.type, v.daily_rate, c.full_name, c.email, c.phone FROM rentals r JOIN vehicles v ON v.vehicle_id = r.vehicle_id JOIN customers c ON c.customer_id = r.customer_id WHERE r.rental_id = ?';
$params = [$rentalId];
if (isCustomerAccount()) {
    $sql .= ' AND r.customer_id = ?';
    $params[] = (int)$_SESSION['customer_id'];
}
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rental = $stmt->fetch();
if (!$rental) {
    http_response_code(404);
    exit('Receipt not found.');
}

if (!in_array((string)$rental['status'], ['active', 'returned', 'completed'], true)) {
    http_response_code(403);
    exit('The receipt will be available after an administrator approves the payment and rental.');
}

$pdf = rental_make_receipt_pdf($rental);
$filename = 'SAFELY DRIVING  CAR RENTALS-Receipt-R' . (int)$rental['rental_id'] . '.pdf';
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($pdf));
header('Cache-Control: private, no-store, max-age=0');
echo $pdf;
exit;
