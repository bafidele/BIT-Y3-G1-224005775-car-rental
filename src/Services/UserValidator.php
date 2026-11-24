<?php
declare(strict_types=1);

namespace CarRental\Services;

final class UserValidator
{
    public static function validate(array $input, bool $creating): array
    {
        $data = [
            'full_name' => trim((string)($input['full_name'] ?? '')),
            'email' => strtolower(trim((string)($input['email'] ?? ''))),
            'role' => (string)($input['role'] ?? 'staff'),
            'password' => (string)($input['password'] ?? ''),
        ];
        $errors = [];
        if ($data['full_name'] === '' || strlen($data['full_name']) > 100) $errors['full_name'] = 'Enter a name of up to 100 characters.';
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL) || strlen($data['email']) > 100) $errors['email'] = 'Enter a valid email address.';
        if (!in_array($data['role'], ['admin', 'staff'], true)) $errors['role'] = 'Choose a valid staff role.';
        if ($creating && strlen($data['password']) < 12) $errors['password'] = 'A new staff password must have at least 12 characters.';
        if (!$creating) unset($data['password']);
        return [$data, $errors];
    }
}
