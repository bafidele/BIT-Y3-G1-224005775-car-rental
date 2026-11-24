<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/src/bootstrap.php';

use CarRental\Models\Customer;
use CarRental\Models\CustomerPasswordReset;
use CarRental\Models\User;
use CarRental\Models\Vehicle;
use CarRental\Services\CustomerValidator;
use CarRental\Services\UserValidator;
use CarRental\Services\VehicleValidator;

function expect(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
    echo "PASS: {$message}\n";
}

$vehicleModel = new Vehicle($pdo);
$customerModel = new Customer($pdo);
$passwordResets = new CustomerPasswordReset($pdo);
$userModel = new User($pdo);
$pdo->beginTransaction();

try {
    [$invalidVehicle, $vehicleErrors] = VehicleValidator::validate(['plate_no' => '', 'model' => '', 'type' => '', 'daily_rate' => 0]);
    expect(count($vehicleErrors) === 4, 'vehicle validation rejects incomplete and non-positive data');

    [$customerData, $customerErrors] = CustomerValidator::validate([
        'full_name' => 'Test Customer', 'email' => 'test.customer@example.test',
        'phone' => '+250700000111', 'district_city' => 'Kigali', 'id_no' => 'TEST-C-001',
    ]);
    expect($customerErrors === [], 'customer validation accepts valid details');

    $insertCustomer = $pdo->prepare('INSERT INTO customers (full_name, email, phone, district_city, id_no, password_hash) VALUES (?, ?, ?, ?, ?, ?)');
    $insertCustomer->execute([
        $customerData['full_name'], $customerData['email'], $customerData['phone'],
        $customerData['district_city'], $customerData['id_no'], password_hash('SmokeCustomer!2026', PASSWORD_DEFAULT),
    ]);
    $customerId = (int)$pdo->lastInsertId();
    $checkCustomer = $pdo->prepare('SELECT password_hash FROM customers WHERE customer_id = ?');
    $checkCustomer->execute([$customerId]);
    expect(password_verify('SmokeCustomer!2026', (string)$checkCustomer->fetchColumn()), 'customer password is stored as a verifiable hash');
    $customerModel->update($customerId, array_replace($customerData, ['full_name' => 'Updated Test Customer']));
    expect(($customerModel->find($customerId)['full_name'] ?? '') === 'Updated Test Customer', 'customer update/read works');
    $customerModel->delete($customerId);
    expect($customerModel->find($customerId) === null, 'customer delete works when no rentals exist');

    $vehicleData = ['plate_no' => 'TEST-CRUD-001', 'model' => 'Test Sedan', 'type' => 'Sedan', 'daily_rate' => 22.50];
    $vehicleId = $vehicleModel->create($vehicleData);
    expect($vehicleModel->find($vehicleId) !== null, 'vehicle create/read works');
    $vehicleModel->update($vehicleId, ['plate_no' => 'TEST-CRUD-002', 'model' => 'Updated Sedan', 'type' => 'Sedan', 'daily_rate' => 25.00]);
    $vehicleModel->setMaintenance($vehicleId, 'maintenance');
    $vehicleModel->setMaintenance($vehicleId, 'available');
    expect($vehicleModel->find($vehicleId)['model'] === 'Updated Sedan', 'vehicle update and status changes work');
    $vehicleModel->delete($vehicleId);
    expect($vehicleModel->find($vehicleId) === null, 'vehicle delete works when no rentals exist');

    $seededVehicle = $pdo->query("SELECT vehicle_id FROM vehicles WHERE plate_no = 'DEMO-RAV4'")->fetchColumn();
    expect($seededVehicle !== false, 'seeded rental vehicle exists for integrity checks');
    $deleteBlocked = false;
    try { $vehicleModel->delete((int)$seededVehicle); } catch (RuntimeException) { $deleteBlocked = true; }
    expect($deleteBlocked, 'vehicle deletion is blocked when rental history exists');

    $seededCustomer = $pdo->query("SELECT customer_id FROM customers WHERE email = 'customer@example.test'")->fetchColumn();
    expect($seededCustomer !== false, 'seeded customer exists for integrity checks');
    $customerDeleteBlocked = false;
    try { $customerModel->delete((int)$seededCustomer); } catch (RuntimeException) { $customerDeleteBlocked = true; }
    expect($customerDeleteBlocked, 'customer deletion is blocked when rental history exists');

    [$staffData, $staffErrors] = UserValidator::validate([
        'full_name' => 'Test Staff', 'email' => 'test.staff@example.test',
        'role' => 'staff', 'password' => 'SmokeStaff!2026',
    ], true);
    expect($staffErrors === [], 'staff registration validation accepts a valid account');
    $userModel->createStaff($staffData);
    $staffId = (int)$pdo->lastInsertId();
    $staffHash = $pdo->prepare('SELECT password_hash FROM users WHERE user_id = ?');
    $staffHash->execute([$staffId]);
    expect(password_verify($staffData['password'], (string)$staffHash->fetchColumn()), 'staff password is stored as a verifiable hash');
    $updateStaff = ['full_name' => 'Updated Test Staff', 'email' => 'test.staff.updated@example.test', 'role' => 'staff'];
    $userModel->update($staffId, $updateStaff);
    expect(count(array_filter($userModel->all(), static fn(array $row): bool => (int)$row['user_id'] === $staffId && $row['full_name'] === 'Updated Test Staff')) === 1, 'staff update/read works');
    $userModel->delete($staffId, (int)($_SESSION['user_id'] ?? 0));
    expect(count(array_filter($userModel->all(), static fn(array $row): bool => (int)$row['user_id'] === $staffId)) === 0, 'staff delete works without removing the administrator');

    [$shortPassword, $passwordErrors] = UserValidator::validate([
        'full_name' => 'Test Staff', 'email' => 'short@example.test', 'role' => 'staff', 'password' => 'short',
    ], true);
    expect(isset($passwordErrors['password']), 'staff registration rejects a short password');

    $matches = $vehicleModel->browse('Toyota', '');
    expect(count($matches) > 0, 'vehicle search returns matching records');
    $customers = $customerModel->allWithRentalCounts();
    expect(count($customers) > 0, 'customer list and rental counts load');

    $resetCustomerId = (int)$pdo->query("SELECT customer_id FROM customers WHERE email = 'customer@example.test'")->fetchColumn();
    $resetToken = bin2hex(random_bytes(32));
    $resetHash = hash('sha256', $resetToken);
    $expiresAt = gmdate('Y-m-d H:i:s', time() + 3600);
    $passwordResets->create($resetCustomerId, $resetHash, $expiresAt);
    expect($passwordResets->hasRecentRequest($resetCustomerId), 'password reset request is rate-limited for one minute');
    $newResetPassword = 'ChangedCustomer!2026';
    expect($passwordResets->consume($resetHash, password_hash($newResetPassword, PASSWORD_DEFAULT)), 'valid reset token changes the customer password');
    $resetPasswordCheck = $pdo->prepare('SELECT password_hash FROM customers WHERE customer_id = ?');
    $resetPasswordCheck->execute([$resetCustomerId]);
    expect(password_verify($newResetPassword, (string)$resetPasswordCheck->fetchColumn()), 'reset password is saved as a hash');
    expect(!$passwordResets->consume($resetHash, password_hash('AnotherPassword!2026', PASSWORD_DEFAULT)), 'reset token cannot be used a second time');
} finally {
    if ($pdo->inTransaction()) $pdo->rollBack();
}

echo "All database smoke checks passed; transaction rolled back.\n";
