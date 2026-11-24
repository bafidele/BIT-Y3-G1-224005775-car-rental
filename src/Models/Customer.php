<?php
declare(strict_types=1);

namespace CarRental\Models;

use PDO;
use RuntimeException;

final class Customer
{
    public function __construct(private PDO $pdo) {}

    public function allWithRentalCounts(): array
    {
        return $this->pdo->query(
            "SELECT c.customer_id, c.full_name, c.email, c.phone, c.district_city, c.id_no,
                    c.profile_picture, c.created_at,
                    COALESCE(r.total_count, 0) AS total_rentals,
                    COALESCE(r.completed_count, 0) AS completed_rentals
             FROM customers c
             LEFT JOIN (
                 SELECT customer_id, COUNT(*) AS total_count,
                        SUM(status IN ('returned', 'completed')) AS completed_count
                 FROM rentals GROUP BY customer_id
             ) r ON r.customer_id = c.customer_id
             ORDER BY c.created_at DESC, c.customer_id DESC"
        )->fetchAll();
    }

    public function update(int $id, array $data): void
    {
        $duplicate = $this->pdo->prepare('SELECT customer_id FROM customers WHERE (email = :email OR id_no = :id_no) AND customer_id <> :customer_id LIMIT 1');
        $duplicate->execute([
            'email' => $data['email'],
            'id_no' => $data['id_no'],
            'customer_id' => $id,
        ]);
        if ($duplicate->fetch()) throw new RuntimeException('That email or National ID is already used by another customer.');

        $staffDuplicate = $this->pdo->prepare('SELECT user_id FROM users WHERE email = ? LIMIT 1');
        $staffDuplicate->execute([$data['email']]);
        if ($staffDuplicate->fetch()) throw new RuntimeException('That email belongs to a staff account.');

        $update = $this->pdo->prepare('UPDATE customers SET full_name = :full_name, email = :email, phone = :phone, district_city = :district_city, id_no = :id_no WHERE customer_id = :customer_id');
        $update->execute($data + ['customer_id' => $id]);
        if ($update->rowCount() < 1 && !$this->find($id)) throw new RuntimeException('Customer not found.');
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT customer_id, full_name, email, phone, district_city, id_no FROM customers WHERE customer_id = ? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function delete(int $id): void
    {
        $delete = $this->pdo->prepare('DELETE FROM customers WHERE customer_id = ? AND NOT EXISTS (SELECT 1 FROM rentals WHERE rentals.customer_id = customers.customer_id)');
        $delete->execute([$id]);
        if ($delete->rowCount() === 1) return;
        if (!$this->find($id)) throw new RuntimeException('Customer not found.');
        throw new RuntimeException('This customer has rental records and must be kept for receipts and history.');
    }
}
