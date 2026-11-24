<?php
declare(strict_types=1);

namespace CarRental\Services;

final class CustomerValidator
{
    public static function validate(array $input): array
    {
        $data = [
            'full_name' => trim((string)($input['full_name'] ?? '')),
            'email' => strtolower(trim((string)($input['email'] ?? ''))),
            'phone' => trim((string)($input['phone'] ?? '')),
            'district_city' => trim((string)($input['district_city'] ?? '')),
            'id_no' => trim((string)($input['id_no'] ?? '')),
        ];
        $errors = [];
        if ($data['full_name'] === '' || strlen($data['full_name']) > 100) $errors['full_name'] = 'Enter a name of up to 100 characters.';
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL) || strlen($data['email']) > 100) $errors['email'] = 'Enter a valid email address.';
        if ($data['phone'] === '' || strlen($data['phone']) > 20) $errors['phone'] = 'Enter a phone number of up to 20 characters.';
        if ($data['district_city'] === '' || strlen($data['district_city']) > 50) $errors['district_city'] = 'Enter a city or district of up to 50 characters.';
        if ($data['id_no'] === '' || strlen($data['id_no']) > 16) $errors['id_no'] = 'Enter a National ID of up to 16 characters.';
        return [$data, $errors];
    }
}
