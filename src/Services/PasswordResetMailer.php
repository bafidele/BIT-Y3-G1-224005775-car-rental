<?php
declare(strict_types=1);

namespace CarRental\Services;

final class PasswordResetMailer
{
    public static function send(string $recipient, string $resetUrl): bool
    {
        $from = trim((string)(getenv('MAIL_FROM_EMAIL') ?: 'no-reply@example.test'));
        if (!filter_var($recipient, FILTER_VALIDATE_EMAIL) || !filter_var($from, FILTER_VALIDATE_EMAIL)) return false;

        $subject = 'Reset your car rental account password';
        $body = "We received a request to reset the password for your customer account.\r\n\r\n"
            . "Use this one-time link within 60 minutes:\r\n{$resetUrl}\r\n\r\n"
            . "If you did not request this change, you can ignore this email.";
        $headers = [
            'From' => $from,
            'Content-Type' => 'text/plain; charset=UTF-8',
            'X-Mailer' => 'Car Rental System',
        ];

        return @mail($recipient, $subject, $body, $headers);
    }
}
