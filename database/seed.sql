--Demonstration-only accounts and sample fleet for a fresh local database.
-- Admin must change the listed first-login password before accessing the site.
USE car_rental_demo;

INSERT INTO users (full_name, email, password_hash, must_change_password, role)
SELECT 'Car Rental Administrator', 'admin@carrental.local',
       '$2y$10$pnvhdp89sJq3n4PwnJA/Sum1sSbC.dgC6ITz9va41BwRdyz9dBUua', 1, 'admin'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'admin@carrental.local');

INSERT INTO customers (full_name, email, phone, district_city, id_no, password_hash, account_approved)
SELECT 'Demo Customer', 'customer@example.test', '+250700000001', 'Kigali', 'DEMO-1001',
       '$2y$10$D0vyMpEhb0INV4/DkfZFqO.yNjTCaLaDebG2zB4XINhMLws4gaLEi', 1
WHERE NOT EXISTS (SELECT 1 FROM customers WHERE email = 'customer@example.test');

INSERT INTO vehicles (plate_no, model, type, daily_rate, photo, status)
SELECT 'DEMO-RAV4', 'Toyota RAV4', 'SUV', 50.00, 'default_car.jpg', 'rented'
WHERE NOT EXISTS (SELECT 1 FROM vehicles WHERE plate_no = 'DEMO-RAV4');

INSERT INTO vehicles (plate_no, model, type, daily_rate, photo, status)
SELECT 'DEMO-CIVIC', 'Honda Civic', 'Sedan', 35.00, 'default_car.jpg', 'rented'
WHERE NOT EXISTS (SELECT 1 FROM vehicles WHERE plate_no = 'DEMO-CIVIC');

INSERT INTO vehicles (plate_no, model, type, daily_rate, photo, status)
SELECT 'DEMO-FOCUS', 'Ford Focus', 'Compact', 30.00, 'default_car.jpg', 'available'
WHERE NOT EXISTS (SELECT 1 FROM vehicles WHERE plate_no = 'DEMO-FOCUS');

INSERT INTO rentals (vehicle_id, customer_id, start_date, start_time, end_date, return_time,
                     total_cost, discount_percent, discount_amount, status,
                     payment_submitted_at, payment_approved_at, terms_accepted_at)
SELECT v.vehicle_id, c.customer_id, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '09:00:00',
       DATE_ADD(CURDATE(), INTERVAL 4 DAY), '09:00:00', 150.00, 0, 0.00,
       'payment_submitted', NOW(), NULL, NOW()
FROM vehicles v CROSS JOIN customers c
WHERE v.plate_no = 'DEMO-RAV4' AND c.email = 'customer@example.test'
  AND NOT EXISTS (
    SELECT 1 FROM rentals r WHERE r.vehicle_id = v.vehicle_id
      AND r.customer_id = c.customer_id AND r.status = 'payment_submitted'
  );

INSERT INTO rentals (vehicle_id, customer_id, start_date, start_time, end_date, return_time,
                     total_cost, discount_percent, discount_amount, status, terms_accepted_at)
SELECT v.vehicle_id, c.customer_id, DATE_ADD(CURDATE(), INTERVAL 5 DAY), '10:00:00',
       DATE_ADD(CURDATE(), INTERVAL 7 DAY), '10:00:00', 70.00, 0, 0.00,
       'payment_pending', NOW()
FROM vehicles v CROSS JOIN customers c
WHERE v.plate_no = 'DEMO-CIVIC' AND c.email = 'customer@example.test'
  AND NOT EXISTS (
    SELECT 1 FROM rentals r WHERE r.vehicle_id = v.vehicle_id
      AND r.customer_id = c.customer_id AND r.status = 'payment_pending'
  );

INSERT INTO rentals (vehicle_id, customer_id, start_date, start_time, end_date, return_time,
                     total_cost, discount_percent, discount_amount, completed_at, status)
SELECT v.vehicle_id, c.customer_id, DATE_SUB(CURDATE(), INTERVAL 35 DAY), '09:00:00',
       DATE_SUB(CURDATE(), INTERVAL 33 DAY), '09:00:00', 60.00, 0, 0.00,
       DATE_SUB(NOW(), INTERVAL 33 DAY), 'returned'
FROM vehicles v CROSS JOIN customers c
WHERE v.plate_no = 'DEMO-FOCUS' AND c.email = 'customer@example.test'
  AND NOT EXISTS (
    SELECT 1 FROM rentals r WHERE r.vehicle_id = v.vehicle_id
      AND r.customer_id = c.customer_id AND r.status IN ('returned', 'completed')
  );

UPDATE vehicles SET status = 'rented'
WHERE plate_no IN ('DEMO-RAV4', 'DEMO-CIVIC')
  AND EXISTS (SELECT 1 FROM rentals r WHERE r.vehicle_id = vehicles.vehicle_id
              AND r.status IN ('payment_pending', 'payment_submitted', 'active'));
