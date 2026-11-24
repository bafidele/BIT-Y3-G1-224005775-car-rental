<?php
declare(strict_types=1);

namespace CarRental\Models;

use PDO;
use RuntimeException;

final class Vehicle
{
    public function __construct(private PDO $pdo) {}

    public function browse(string $query = '', string $status = ''): array
    {
        $sql = "SELECT v.*,
                    (SELECT r.end_date FROM rentals r
                     WHERE r.vehicle_id = v.vehicle_id
                       AND r.status IN ('active', 'payment_pending', 'payment_submitted')
                     ORDER BY r.rental_id DESC LIMIT 1) AS expected_return_date,
                    (SELECT r.return_time FROM rentals r
                     WHERE r.vehicle_id = v.vehicle_id
                       AND r.status IN ('active', 'payment_pending', 'payment_submitted')
                     ORDER BY r.rental_id DESC LIMIT 1) AS expected_return_time
                FROM vehicles v WHERE 1=1";
        $params = [];
        if ($query !== '') {
            $sql .= ' AND (v.model LIKE :model OR v.type LIKE :type OR v.plate_no LIKE :plate)';
            $term = '%' . $query . '%';
            $params['model'] = $term;
            $params['type'] = $term;
            $params['plate'] = $term;
        }
        if ($status !== '') {
            $sql .= ' AND v.status = :status';
            $params['status'] = $status;
        }
        $sql .= " ORDER BY CASE WHEN v.status = 'available' THEN 0 ELSE 1 END, v.model";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function all(): array
    {
        return $this->pdo->query('SELECT * FROM vehicles ORDER BY vehicle_id DESC')->fetchAll();
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO vehicles (plate_no, model, type, daily_rate) VALUES (:plate_no, :model, :type, :daily_rate)');
        $stmt->execute($data);
        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->pdo->prepare('UPDATE vehicles SET plate_no = :plate_no, model = :model, type = :type, daily_rate = :daily_rate WHERE vehicle_id = :vehicle_id');
        $stmt->execute($data + ['vehicle_id' => $id]);
        if ($stmt->rowCount() < 1 && !$this->find($id)) {
            throw new RuntimeException('Vehicle not found.');
        }
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM vehicles WHERE vehicle_id = ? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function setMaintenance(int $id, string $status): void
    {
        if (!in_array($status, ['available', 'maintenance'], true)) {
            throw new RuntimeException('Choose available or maintenance status.');
        }
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM rentals WHERE vehicle_id = ? AND status IN ('active', 'payment_pending', 'payment_submitted')");
        $stmt->execute([$id]);
        if ((int)$stmt->fetchColumn() > 0) {
            throw new RuntimeException('A car with an open rental request cannot be moved to maintenance or available.');
        }
        $update = $this->pdo->prepare('UPDATE vehicles SET status = ? WHERE vehicle_id = ?');
        $update->execute([$status, $id]);
        if ($update->rowCount() < 1 && !$this->find($id)) {
            throw new RuntimeException('Vehicle not found.');
        }
    }

    public function delete(int $id): void
    {
        $check = $this->pdo->prepare('SELECT COUNT(*) FROM rentals WHERE vehicle_id = ?');
        $check->execute([$id]);
        if ((int)$check->fetchColumn() > 0) {
            throw new RuntimeException('This vehicle has rental history and must be kept for receipts and records.');
        }
        $delete = $this->pdo->prepare('DELETE FROM vehicles WHERE vehicle_id = ?');
        $delete->execute([$id]);
        if ($delete->rowCount() !== 1) throw new RuntimeException('Vehicle not found.');
    }
}
