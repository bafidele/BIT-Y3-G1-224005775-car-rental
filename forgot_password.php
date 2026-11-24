<?php
declare(strict_types=1);

require __DIR__ . '/src/bootstrap.php';

if (isAuthenticated()) {
    header('Location: index.php');
    exit;
}

(new \CarRental\Controllers\PasswordResetController($pdo))->request();
