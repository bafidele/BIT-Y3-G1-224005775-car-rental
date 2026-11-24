-- Car Rental System schema for a fresh local database.
-- Keep staff accounts (users) separate from rental customers (customers).
CREATE DATABASE IF NOT EXISTS car_rental_demo
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE car_rental_demo;

CREATE TABLE IF NOT EXISTS users (
  user_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  full_name VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  must_change_password TINYINT(1) NOT NULL DEFAULT 0,
  role ENUM('admin', 'staff') NOT NULL DEFAULT 'staff',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id),
  UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS customers (
  customer_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  full_name VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL,
  phone VARCHAR(20) NOT NULL,
  district_city VARCHAR(50) NOT NULL,
  id_no VARCHAR(16) NOT NULL,
  password_hash VARCHAR(255) DEFAULT NULL,
  account_approved TINYINT(1) NOT NULL DEFAULT 1,
  profile_picture VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (customer_id),
  UNIQUE KEY uq_customers_email (email),
  UNIQUE KEY uq_customers_id_no (id_no)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- One-time, expiring customer password reset grants. Token values are stored only as SHA-256 hashes.
CREATE TABLE IF NOT EXISTS customer_password_resets (
  reset_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  customer_id BIGINT UNSIGNED NOT NULL,
  token_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  expires_at DATETIME NOT NULL,
  PRIMARY KEY (reset_id),
  UNIQUE KEY uq_customer_password_resets_token (token_hash),
  KEY idx_customer_password_resets_customer (customer_id, created_at),
  KEY idx_customer_password_resets_expiry (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS vehicles (
  vehicle_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  plate_no VARCHAR(20) NOT NULL,
  model VARCHAR(50) NOT NULL,
  type VARCHAR(50) NOT NULL,
  daily_rate DECIMAL(10,2) NOT NULL,
  photo VARCHAR(255) DEFAULT 'default_car.jpg',
  status ENUM('available', 'rented', 'maintenance') NOT NULL DEFAULT 'available',
  PRIMARY KEY (vehicle_id),
  UNIQUE KEY uq_vehicles_plate_no (plate_no),
  KEY idx_vehicles_status_model (status, model)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rentals (
  rental_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  vehicle_id INT UNSIGNED NOT NULL,
  customer_id INT UNSIGNED NOT NULL,
  start_date DATE NOT NULL,
  start_time TIME NOT NULL DEFAULT '09:00:00',
  end_date DATE NOT NULL,
  return_time TIME NOT NULL DEFAULT '09:00:00',
  total_cost DECIMAL(10,2) NOT NULL,
  discount_percent TINYINT UNSIGNED NOT NULL DEFAULT 0,
  discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  completed_at DATETIME DEFAULT NULL,
  status ENUM('payment_pending', 'payment_submitted', 'active', 'returned', 'completed', 'cancelled') NOT NULL DEFAULT 'payment_pending',
  payment_submitted_at DATETIME DEFAULT NULL,
  payment_approved_at DATETIME DEFAULT NULL,
  terms_accepted_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (rental_id),
  KEY idx_rentals_vehicle_status (vehicle_id, status),
  KEY idx_rentals_customer_status (customer_id, status),
  CONSTRAINT fk_rentals_vehicle FOREIGN KEY (vehicle_id)
    REFERENCES vehicles (vehicle_id) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_rentals_customer FOREIGN KEY (customer_id)
    REFERENCES customers (customer_id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
