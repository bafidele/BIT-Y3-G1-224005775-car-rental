<?php
declare(strict_types=1);

namespace CarRental\Models;

use PDO;
use RuntimeException;
use Throwable;

final class Rental
{
    public function __construct(private PDO $pdo) {}

    public function allByCustomer(): array
    {
        $rows = $this->pdo->query(
            'SELECT r.*, v.plate_no, v.model, v.type, v.daily_rate
             FROM rentals r JOIN vehicles v ON v.vehicle_id = r.vehicle_id
             ORDER BY r.customer_id, r.rental_id DESC'
        )->fetchAll();
        $grouped = [];
        foreach ($rows as $row) $grouped[(int)$row['customer_id']][] = $row;
        return $grouped;
    }

    public function approvePayment(int $rentalId): void
    {
        $this->transition($rentalId, 'payment_submitted', 'active', 'Only requests with submitted payment can be approved.', true);
    }

    public function cancelRequest(int $rentalId): void
    {
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare('SELECT vehicle_id, status FROM rentals WHERE rental_id = ? FOR UPDATE');
            $stmt->execute([$rentalId]);
            $rental = $stmt->fetch();
            if (!$rental || !in_array((string)$rental['status'], ['payment_pending', 'payment_submitted'], true)) {
                throw new RuntimeException('Only a request awaiting payment or payment review can be cancelled.');
            }
            $update = $this->pdo->prepare("UPDATE rentals SET status = 'cancelled' WHERE rental_id = ? AND status IN ('payment_pending', 'payment_submitted')");
            $update->execute([$rentalId]);
            if ($update->rowCount() !== 1) throw new RuntimeException('This request was already updated.');
            $this->pdo->prepare("UPDATE vehicles SET status = 'available' WHERE vehicle_id = ? AND status = 'rented'")->execute([(int)$rental['vehicle_id']]);
            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $exception;
        }
    }

    public function markReturned(int $rentalId, string $completedAt): void
    {
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare('SELECT vehicle_id, status FROM rentals WHERE rental_id = ? FOR UPDATE');
            $stmt->execute([$rentalId]);
            $rental = $stmt->fetch();
            if (!$rental || $rental['status'] !== 'active') throw new RuntimeException('Only an active rental can be marked as returned.');
            $update = $this->pdo->prepare("UPDATE rentals SET status = 'returned', completed_at = ? WHERE rental_id = ? AND status = 'active'");
            $update->execute([$completedAt, $rentalId]);
            if ($update->rowCount() !== 1) throw new RuntimeException('This rental was already updated.');
            $this->pdo->prepare("UPDATE vehicles SET status = 'available' WHERE vehicle_id = ? AND status = 'rented'")->execute([(int)$rental['vehicle_id']]);
            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $exception;
        }
    }

    private function transition(int $rentalId, string $from, string $to, string $failure, bool $stampApproval = false): void
    {
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare('SELECT status FROM rentals WHERE rental_id = ? FOR UPDATE');
            $stmt->execute([$rentalId]);
            $rental = $stmt->fetch();
            if (!$rental || $rental['status'] !== $from) throw new RuntimeException($failure);
            $stamp = $stampApproval ? ', payment_approved_at = NOW()' : '';
            $update = $this->pdo->prepare("UPDATE rentals SET status = ?{$stamp} WHERE rental_id = ? AND status = ?");
            $update->execute([$to, $rentalId, $from]);
            if ($update->rowCount() !== 1) throw new RuntimeException('This rental was already updated.');
            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $exception;
        }
    }
}
