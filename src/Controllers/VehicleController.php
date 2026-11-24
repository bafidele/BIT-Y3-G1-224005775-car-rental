<?php
declare(strict_types=1);

namespace CarRental\Controllers;

use CarRental\Models\Vehicle;
use CarRental\Services\VehicleValidator;
use CarRental\Support\View;
use PDO;
use PDOException;
use RuntimeException;
use Throwable;

final class VehicleController
{
    private Vehicle $vehicles;

    public function __construct(PDO $pdo)
    {
        $this->vehicles = new Vehicle($pdo);
    }

    public function browse(): void
    {
        $query = trim((string)($_GET['q'] ?? ''));
        $query = mb_substr($query, 0, 80);
        $status = (string)($_GET['status'] ?? '');
        if (!in_array($status, ['', 'available', 'rented', 'maintenance'], true)) $status = '';

        View::render('vehicles/browse', [
            'title' => 'Browse Cars',
            'cars' => $this->vehicles->browse($query, $status),
            'query' => $query,
            'statusFilter' => $status,
        ]);
    }

    public function manage(): void
    {
        \requireStaff();
        $notice = $_SESSION['vehicle_notice'] ?? null;
        unset($_SESSION['vehicle_notice']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $message = ['error', 'The vehicle action could not be completed.'];
            try {
                if (!\verifyCsrfToken()) throw new RuntimeException('Your session expired. Please reload and try again.');
                $action = (string)($_POST['action'] ?? '');
                if ($action === 'add' || $action === 'update') {
                    [$data, $errors] = VehicleValidator::validate($_POST);
                    if ($errors) throw new RuntimeException(implode(' ', $errors));
                    if ($action === 'add') {
                        $this->vehicles->create($data);
                        $message = ['success', 'Vehicle added to the fleet.'];
                    } else {
                        $this->vehicles->update((int)($_POST['vehicle_id'] ?? 0), $data);
                        $message = ['success', 'Vehicle details updated.'];
                    }
                } elseif ($action === 'maintenance') {
                    $this->vehicles->setMaintenance((int)($_POST['vehicle_id'] ?? 0), (string)($_POST['status'] ?? ''));
                    $message = ['success', 'Vehicle status updated.'];
                } elseif ($action === 'delete') {
                    $this->vehicles->delete((int)($_POST['vehicle_id'] ?? 0));
                    $message = ['success', 'Vehicle removed from the fleet.'];
                } else {
                    throw new RuntimeException('Choose a valid vehicle action.');
                }
            } catch (RuntimeException $exception) {
                $message = ['error', $exception->getMessage()];
            } catch (PDOException $exception) {
                error_log('Vehicle management database error: ' . $exception->getMessage());
                $message = ['error', $exception->getCode() === '23000'
                    ? 'That registration plate is already in use or the vehicle is linked to a rental.'
                    : 'The vehicle could not be saved. Please try again.'];
            } catch (Throwable $exception) {
                error_log('Vehicle management failed: ' . $exception->getMessage());
                $message = ['error', 'The vehicle could not be saved. Please try again.'];
            }
            $_SESSION['vehicle_notice'] = $message;
            header('Location: vehicles.php', true, 303);
            exit;
        }

        View::render('vehicles/manage', [
            'title' => 'Vehicle management',
            'vehicles' => $this->vehicles->all(),
            'notice' => $notice,
        ]);
    }
}
