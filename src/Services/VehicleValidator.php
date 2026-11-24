<?php
declare(strict_types=1);

namespace CarRental\Services;

final class VehicleValidator
{
    public static function validate(array $input): array
    {
        $data = [
            'plate_no' => strtoupper(trim((string)($input['plate_no'] ?? ''))),
            'model' => trim((string)($input['model'] ?? '')),
            'type' => trim((string)($input['type'] ?? '')),
            'daily_rate' => filter_var($input['daily_rate'] ?? null, FILTER_VALIDATE_FLOAT),
        ];
        $errors = [];
        if ($data['plate_no'] === '' || strlen($data['plate_no']) > 20) $errors['plate_no'] = 'Enter a registration plate of up to 20 characters.';
        if ($data['model'] === '' || strlen($data['model']) > 50) $errors['model'] = 'Enter a vehicle model of up to 50 characters.';
        if ($data['type'] === '' || strlen($data['type']) > 50) $errors['type'] = 'Enter a vehicle type of up to 50 characters.';
        if ($data['daily_rate'] === false || $data['daily_rate'] <= 0 || $data['daily_rate'] > 99999999.99) $errors['daily_rate'] = 'Enter a daily rate greater than zero.';
        return [$data, $errors];
    }
}
