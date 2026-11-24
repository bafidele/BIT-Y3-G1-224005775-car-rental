<?php
declare(strict_types=1);

namespace CarRental\Controllers;

use CarRental\Models\CustomerPasswordReset;
use CarRental\Services\PasswordResetMailer;
use CarRental\Support\View;
use DateTimeImmutable;
use DateTimeZone;
use PDO;
use Throwable;

final class PasswordResetController
{
    private CustomerPasswordReset $resets;

    public function __construct(private PDO $pdo)
    {
        $this->resets = new CustomerPasswordReset($pdo);
    }

    public function request(): void
    {
        $error = '';
        $notice = (string)($_SESSION['password_reset_notice'] ?? '');
        $flashError = (string)($_SESSION['password_reset_error'] ?? '');
        $devLink = (string)($_SESSION['password_reset_dev_link'] ?? '');
        unset($_SESSION['password_reset_notice'], $_SESSION['password_reset_error'], $_SESSION['password_reset_dev_link']);
        if ($flashError !== '') $error = $flashError;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!\verifyCsrfToken()) {
                $error = 'Your session expired. Please refresh the page and try again.';
            } else {
                $email = strtolower(trim((string)($_POST['email'] ?? '')));
                if (strlen($email) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $error = 'Enter a valid email address.';
                } elseif (!$this->allowSessionAttempt()) {
                    $notice = 'If an eligible customer account exists for that address, password reset instructions will be sent.';
                } else {
                    try {
                        $customerId = $this->resets->findCustomerIdByEmail($email);
                        if ($customerId !== null && !$this->resets->hasRecentRequest($customerId)) {
                            $token = bin2hex(random_bytes(32));
                            $tokenHash = hash('sha256', $token);
                            $expires = (new DateTimeImmutable('+60 minutes', new DateTimeZone('UTC')))->format('Y-m-d H:i:s');
                            $url = $this->resetUrl($token);
                            $this->resets->create($customerId, $tokenHash, $expires);

                            if (!PasswordResetMailer::send($email, $url)) {
                                if ($this->isLocalRequest()) {
                                    // Local XAMPP often has no mail transport configured. Keep this fallback
                                    // restricted to localhost so reset links are never exposed on a live site.
                                    $devLink = $url;
                                    $_SESSION['password_reset_dev_link'] = $devLink;
                                } else {
                                    $this->resets->removeToken($tokenHash);
                                    error_log('Customer password reset email could not be delivered.');
                                }
                            }
                        }
                        $notice = 'If an eligible customer account exists for that address, password reset instructions will be sent.';
                    } catch (Throwable $exception) {
                        error_log('Customer password reset request failed: ' . $exception->getMessage());
                        $notice = 'If an eligible customer account exists for that address, password reset instructions will be sent.';
                    }
                }
            }

            $_SESSION['password_reset_notice'] = $notice;
            $_SESSION['password_reset_error'] = $error;
            header('Location: forgot_password.php', true, 303);
            exit;
        }

        View::render('auth/forgot_password', [
            'title' => 'Forgot password',
            'notice' => $notice,
            'error' => $error,
            'devLink' => $devLink,
        ]);
    }

    public function reset(): void
    {
        header('Referrer-Policy: no-referrer');
        header('Cache-Control: no-store, max-age=0');
        header('Pragma: no-cache');
        $token = trim((string)($_GET['token'] ?? $_POST['token'] ?? ''));
        $tokenValid = preg_match('/^[a-f0-9]{64}$/D', $token) === 1;
        $tokenHash = $tokenValid ? hash('sha256', $token) : '';
        $error = '';
        $notice = (string)($_SESSION['password_reset_complete'] ?? '');
        unset($_SESSION['password_reset_complete']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $password = (string)($_POST['password'] ?? '');
            $confirmation = (string)($_POST['confirm_password'] ?? '');

            if (!\verifyCsrfToken()) {
                $error = 'Your session expired. Reload this page from the reset email and try again.';
            } elseif (!$tokenValid) {
                $error = 'This password reset link is invalid or has expired. Request a new link.';
            } elseif (strlen($password) < 12) {
                $error = 'Choose a password with at least 12 characters.';
            } elseif ($password !== $confirmation) {
                $error = 'The new password and confirmation do not match.';
            } else {
                try {
                    if ($this->resets->consume($tokenHash, password_hash($password, PASSWORD_DEFAULT))) {
                        $_SESSION['password_reset_complete'] = 'Your password has been reset. Sign in with your new password.';
                        session_regenerate_id(true);
                        header('Location: reset_password.php', true, 303);
                        exit;
                    }
                    $error = 'This password reset link is invalid or has expired. Request a new link.';
                    $tokenValid = false;
                } catch (Throwable $exception) {
                    error_log('Customer password reset completion failed: ' . $exception->getMessage());
                    $error = 'The password could not be changed. Request a new reset link and try again.';
                }
            }
        } elseif ($tokenValid) {
            try {
                $stmt = $this->pdo->prepare(
                    'SELECT 1 FROM customer_password_resets WHERE token_hash = ? AND expires_at > UTC_TIMESTAMP() LIMIT 1'
                );
                $stmt->execute([$tokenHash]);
                $tokenValid = (bool)$stmt->fetchColumn();
            } catch (Throwable $exception) {
                error_log('Customer password reset token lookup failed: ' . $exception->getMessage());
                $tokenValid = false;
            }
            if (!$tokenValid) $error = 'This password reset link is invalid or has expired. Request a new link.';
        } else {
            $error = 'This password reset link is invalid or has expired. Request a new link.';
        }

        View::render('auth/reset_password', [
            'title' => 'Reset password',
            'notice' => $notice,
            'error' => $error,
            'token' => $token,
            'tokenValid' => $tokenValid,
        ]);
    }

    private function allowSessionAttempt(): bool
    {
        $now = time();
        $attempts = array_values(array_filter(
            is_array($_SESSION['password_reset_attempts'] ?? null) ? $_SESSION['password_reset_attempts'] : [],
            static fn($time): bool => is_int($time) && $time > $now - 3600
        ));
        if (count($attempts) >= 5) {
            $_SESSION['password_reset_attempts'] = $attempts;
            return false;
        }
        $attempts[] = $now;
        $_SESSION['password_reset_attempts'] = $attempts;
        return true;
    }

    private function resetUrl(string $token): string
    {
        $baseUrl = rtrim((string)(getenv('APP_BASE_URL') ?: 'http://localhost/car_rental'), '/');
        if (!filter_var($baseUrl, FILTER_VALIDATE_URL)
            || !in_array(strtolower((string)parse_url($baseUrl, PHP_URL_SCHEME)), ['http', 'https'], true)
            || parse_url($baseUrl, PHP_URL_USER) !== null
            || parse_url($baseUrl, PHP_URL_PASS) !== null
            || parse_url($baseUrl, PHP_URL_QUERY) !== null
            || parse_url($baseUrl, PHP_URL_FRAGMENT) !== null) {
            throw new \RuntimeException('APP_BASE_URL must be an absolute HTTP or HTTPS URL.');
        }
        return $baseUrl . '/reset_password.php?token=' . rawurlencode($token);
    }

    private function isLocalRequest(): bool
    {
        $host = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
        $host = preg_replace('/:\d+$/', '', $host) ?? '';
        return in_array($host, ['localhost', '127.0.0.1', '[::1]'], true);
    }
}
