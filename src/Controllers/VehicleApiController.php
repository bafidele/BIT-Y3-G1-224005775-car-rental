<?php
declare(strict_types=1);

namespace CarRental\Controllers;

use CarRental\Models\Vehicle;
use CarRental\Support\JsonResponse;
use PDO;
use Throwable;

final class VehicleApiController
{
    public function __construct(private PDO $pdo) {}

    public function index(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
            JsonResponse::send(['ok' => false, 'error' => 'GET is required.'], 405);
        }
        try {
            $query = trim((string)($_GET['q'] ?? ''));
            $query = mb_substr($query, 0, 80);
            $status = (string)($_GET['status'] ?? '');
            if (!in_array($status, ['', 'available', 'rented', 'maintenance'], true)) $status = '';
            $vehicles = (new Vehicle($this->pdo))->browse($query, $status);
            JsonResponse::send(['ok' => true, 'data' => ['vehicles' => $vehicles]]);
        } catch (Throwable $exception) {
            error_log('Vehicle API failed: ' . $exception->getMessage());
            JsonResponse::send(['ok' => false, 'error' => 'Cars could not be loaded. Please try again.'], 500);
        }
    }
}
