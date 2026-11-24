<?php
declare(strict_types=1);
require_once __DIR__ . '/src/bootstrap.php';

(new \CarRental\Controllers\UserController($pdo))->manage();
