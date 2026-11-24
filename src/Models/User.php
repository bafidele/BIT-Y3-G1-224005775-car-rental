<?php
declare(strict_types=1);

namespace CarRental\Models;

use PDO;
use RuntimeException;

final class User
{
    public function __construct(private PDO $pdo) {}

    public function all(): array
    {
        return $this->pdo->query('SELECT user_id, full_name, email, role, must_change_password, created_at FROM users ORDER BY user_id')->fetchAll();
    }

    public function createStaff(array $data): void
    {
        $this->ensureEmailAvailable($data['email']);
        $stmt = $this->pdo->prepare("INSERT INTO users (full_name, email, password_hash, must_change_password, role) VALUES (?, ?, ?, 1, 'staff')");
        $stmt->execute([$data['full_name'], $data['email'], password_hash($data['password'], PASSWORD_DEFAULT)]);
    }

    public function update(int $id, array $data): void
    {
        $this->ensureEmailAvailable($data['email'], $id);
        if ($data['role'] === 'staff') $this->ensureAnotherAdminExists($id);
        $stmt = $this->pdo->prepare('UPDATE users SET full_name = ?, email = ?, role = ? WHERE user_id = ?');
        $stmt->execute([$data['full_name'], $data['email'], $data['role'], $id]);
        if ($stmt->rowCount() < 1 && !$this->find($id)) throw new RuntimeException('Staff account not found.');
    }

    public function delete(int $id, int $currentUserId): void
    {
        if ($id === $currentUserId) throw new RuntimeException('You cannot delete the account you are currently using.');
        $user = $this->find($id);
        if (!$user) throw new RuntimeException('Staff account not found.');
        if ($user['role'] === 'admin') $this->ensureAnotherAdminExists($id);
        $stmt = $this->pdo->prepare('DELETE FROM users WHERE user_id = ?');
        $stmt->execute([$id]);
    }

    private function ensureEmailAvailable(string $email, int $ignoreId = 0): void
    {
        $staff = $this->pdo->prepare('SELECT user_id FROM users WHERE email = ? AND user_id <> ? LIMIT 1');
        $staff->execute([$email, $ignoreId]);
        $customer = $this->pdo->prepare('SELECT customer_id FROM customers WHERE email = ? LIMIT 1');
        $customer->execute([$email]);
        if ($staff->fetch() || $customer->fetch()) throw new RuntimeException('That email is already used by another account.');
    }

    private function ensureAnotherAdminExists(int $ignoreId): void
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin' AND user_id <> ?");
        $stmt->execute([$ignoreId]);
        if ((int)$stmt->fetchColumn() < 1) throw new RuntimeException('Keep at least one administrator account.');
    }

    private function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT user_id, role FROM users WHERE user_id = ? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }
}
