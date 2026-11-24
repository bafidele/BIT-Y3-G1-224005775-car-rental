<?php
declare(strict_types=1);

namespace CarRental\Controllers;

use CarRental\Models\Customer;
use CarRental\Models\Rental;
use CarRental\Services\CustomerValidator;
use CarRental\Support\View;
use PDO;
use PDOException;
use RuntimeException;
use Throwable;

final class CustomerController
{
    private Customer $customers;
    private Rental $rentals;

    public function __construct(PDO $pdo)
    {
        $this->customers = new Customer($pdo);
        $this->rentals = new Rental($pdo);
    }

    public function manage(): void
    {
        \requireStaff();
        $notice = $_SESSION['admin_notice'] ?? null;
        unset($_SESSION['admin_notice']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $message = ['error', 'The requested action could not be completed.'];
            try {
                if (!\verifyCsrfToken()) throw new RuntimeException('Your session expired. Please reload and try again.');
                $action = (string)($_POST['action'] ?? '');
                $id = (int)($_POST['customer_id'] ?? $_POST['rental_id'] ?? 0);
                if ($action === 'update_customer') {
                    [$data, $errors] = CustomerValidator::validate($_POST);
                    if ($errors) throw new RuntimeException(implode(' ', $errors));
                    $this->customers->update($id, $data);
                    $message = ['success', 'Customer details updated.'];
                } elseif ($action === 'delete_customer') {
                    $this->customers->delete($id);
                    $message = ['success', 'Customer account deleted.'];
                } elseif ($action === 'approve_rental') {
                    $this->rentals->approvePayment($id);
                    $message = ['success', 'Payment approved and rental confirmed. The customer can now download the PDF receipt.'];
                } elseif ($action === 'cancel_rental') {
                    $this->rentals->cancelRequest($id);
                    $message = ['success', 'Rental request cancelled and the car is available again.'];
                } elseif ($action === 'complete_rental') {
                    $this->rentals->markReturned($id, date('Y-m-d H:i:s'));
                    $message = ['success', 'Rental marked as returned and completed.'];
                } else {
                    throw new RuntimeException('Choose a valid customer or rental action.');
                }
            } catch (RuntimeException $exception) {
                $message = ['error', $exception->getMessage()];
            } catch (PDOException $exception) {
                error_log('Customer management database error: ' . $exception->getMessage());
                $message = ['error', $exception->getCode() === '23000'
                    ? 'The email or National ID is already in use, or this record is linked to rental history.'
                    : 'The requested action could not be saved. Please try again.'];
            } catch (Throwable $exception) {
                error_log('Customer management failed: ' . $exception->getMessage());
                $message = ['error', 'The requested action could not be saved. Please try again.'];
            }
            $_SESSION['admin_notice'] = $message;
            header('Location: customers.php', true, 303);
            exit;
        }

        View::render('customers/manage', [
            'title' => 'Customer management',
            'customers' => $this->customers->allWithRentalCounts(),
            'rentalsByCustomer' => $this->rentals->allByCustomer(),
            'notice' => $notice,
        ]);
    }
}
