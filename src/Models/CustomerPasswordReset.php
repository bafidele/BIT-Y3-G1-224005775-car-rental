<?php
declare(strict_types=1);

namespace CarRental\Models;

use PDO;

final class CustomerPasswordReset
{
    public function __construct(private PDO $pdo) {}

    public function findCustomerIdByEmail(string $email): ?int
    {
        $stmt = $this->pdo->prepare('SELECT customer_id FROM customers WHERE email = ? AND password_hash IS NOT NULL LIMIT 1');
        $stmt->execute([$email]);
        $customerId = $stmt->fetchColumn();
        return $customerId === false ? null : (int)$customerId;
    }

    public function hasRecentRequest(int $customerId): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM customer_password_resets
             WHERE customer_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 60 SECOND)
             LIMIT 1'
        );
        $stmt->execute([$customerId]);
        return (bool)$stmt->fetchColumn();
    }

    public function create(int $customerId, string $tokenHash, string $expiresAtUtc): void
    {
        $ownsTransaction = !$this->pdo->inTransaction();
        if ($ownsTransaction) $this->pdo->beginTransaction();
        try {
            $delete = $this->pdo->prepare('DELETE FROM customer_password_resets WHERE customer_id = ? OR expires_at <= UTC_TIMESTAMP()');
            $delete->execute([$customerId]);

            $insert = $this->pdo->prepare(
                'INSERT INTO customer_password_resets (customer_id, token_hash, expires_at) VALUES (?, ?, ?)'
            );
            $insert->execute([$customerId, $tokenHash, $expiresAtUtc]);
            if ($ownsTransaction) $this->pdo->commit();
        } catch (\Throwable $exception) {
            if ($ownsTransaction && $this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $exception;
        }
    }

    public function removeToken(string $tokenHash): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM customer_password_resets WHERE token_hash = ?');
        $stmt->execute([$tokenHash]);
    }

    public function consume(string $tokenHash, string $passwordHash): bool
    {
        $ownsTransaction = !$this->pdo->inTransaction();
        if ($ownsTransaction) $this->pdo->beginTransaction();
        try {
            $find = $this->pdo->prepare(
                'SELECT customer_id FROM customer_password_resets
                 WHERE token_hash = ? AND expires_at > UTC_TIMESTAMP()
                 LIMIT 1 FOR UPDATE'
            );
            $find->execute([$tokenHash]);
            $customerId = $find->fetchColumn();
            if ($customerId === false) {
                if ($ownsTransaction) $this->pdo->rollBack();
                return false;
            }

            $update = $this->pdo->prepare('UPDATE customers SET password_hash = ? WHERE customer_id = ?');
            $update->execute([$passwordHash, (int)$customerId]);
            if ($update->rowCount() !== 1) {
                if ($ownsTransaction) $this->pdo->rollBack();
                return false;
            }

            $delete = $this->pdo->prepare('DELETE FROM customer_password_resets WHERE customer_id = ?');
            $delete->execute([(int)$customerId]);
            if ($ownsTransaction) $this->pdo->commit();
            return true;
        } catch (\Throwable $exception) {
            if ($ownsTransaction && $this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $exception;
        }
    }
}
