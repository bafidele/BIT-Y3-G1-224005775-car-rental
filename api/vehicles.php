<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/src/bootstrap.php';

(new \CarRental\Controllers\VehicleApiController($pdo))->index();
