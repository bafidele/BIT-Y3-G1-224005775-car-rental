## PRIME CAR LEASE CAR RENTALS

## About my project

This is my car rental website project. Customers can create an account, look through the cars, request a rental, report that they have paid, and download a receipt after the administrator approves the payment. Staff manage the cars, customers, rental requests, and returns.

The staff login accounts are stored in `users`. Customer accounts are stored separately in `customers`.

## What I used

- HTML and responsive CSS
- Vanilla JavaScript, including Fetch for car search
- PHP 8 with sessions, shared includes, and PDO prepared statements
- MySQL / MariaDB

The main models, controllers, services, and page views are in `src/`. The car search JSON endpoint is `api/vehicles.php`.

## VIDEO DEMO 3MINUTES LINK
[https://drive.google.com/drive/folders/1LrYfjRaM1YAlRUkbJVt0TK9PQBgEq9MZ?usp=drive_link
# Car Rental Management System



The main models, controllers, services, and page views are in `src/`. The car search JSON endpoint is `api/vehicles.php`.

## How the HTML page connects to the other files

1. `cars.html` contains the page structure, text, navigation, car search form, and car list area.
2. The `<link>` in `cars.html` loads `assets/style.css`, which controls the colors, layout, and mobile styling.
3. The `<script>` in `cars.html` loads `assets/js/html-browse.js`. This file handles search input and builds the car cards safely in the browser.
4. When the page opens or someone searches, the JavaScript uses Fetch to request `api/vehicles.php` with the search text and availability filter.
5. `api/vehicles.php` calls `src/Controllers/VehicleApiController.php`. The controller gets car data through `src/Models/Vehicle.php`, which reads the MySQL database with PDO.
6. The PHP endpoint sends the results back as JSON. The JavaScript uses those results to update the HTML page. The “Continue to booking page” button opens `browse.php` for the full rental flow.

To view it in XAMPP, open `http://localhost/car_rental/cars.html`. This is a real HTML page connected to the same CSS, JavaScript, PHP API, and MySQL data used by the main application.

## Run it with XAMPP

1. Start Apache and MySQL in XAMPP.
2. Copy `.env.example` to `.env` in this folder. The example uses database name `car_rental_demo`, MySQL user `root`, and a blank password. Change these values if your MySQL setup is different.
3. Open phpMyAdmin and import `database/schema.sql`, then import `database/seed.sql`.
4. Open `http://localhost/car_rental/`.

The schema file creates the demo database and its tables. If you are adding password recovery to an existing database instead, import `database/migrations/001_customer_password_resets.sql` into that database. This adds the reset-token table.

### Demo accounts

- Admin username: `admin`
- Admin password: `ChangeMeFirst!2026` (the site asks you to change it at first login)
- Customer email: `customer@example.test`
- Customer password: `CustomerDemo!2026`

These accounts are only for a fresh local demo database. Do not use them on a live website.

## Database tables

- `users`  administrator and staff login accounts
- `customers` : customer profiles and login accounts
- `vehicles` : car details and availability
- `rentals` : customer bookings, dates, fees, discounts, and payment status
- `customer_password_resets` : hashed, one-use reset tokens with an expiry time

## Main pages

| Page | What it does |
|---|---|
| `index.php` | Customer or staff dashboard |
| `browse.php` | View cars and search by model or plate |
| `cars.html` | Standalone HTML car page connected to the PHP JSON API |
| `api/vehicles.php?q=&status=` | Returns car search results as JSON |
| `register.php` and `login.php` | Create a customer account and sign in |
| `logout.php` | Sign out securely |
| `forgot_password.php` and `reset_password.php` | Request and use a customer password reset link |
| `change_password.php` | Change a password after signing in |
| `site_logo.php` | Admin-only website logo upload handler |
| `customers.php` | Manage customers and review rental payments |
| `vehicles.php` | Add, edit, and remove cars |
| `staff.php` | Admin-only staff account management |
| `profile.php` | Edit a customer profile |
| `add_rental.php` | Request or create a rental |
| `receipt.php?rental_id=…` | Download an approved rental receipt |

## Security and payments

Passwords use PHP `password_hash()` and `password_verify()`. Forms that change data use CSRF tokens, and database values are handled with prepared statements. Customer and staff pages check the account role on the server.

The website does not collect online payments. A customer follows the payment instructions, selects **I have paid**, and waits for staff to check the payment. The receipt becomes available after approval. Cars and customers with rental history cannot be deleted so old receipts remain available.

An administrator can upload a JPG, PNG, or WEBP website logo from the left side of the header. Click the logo or **Upload photo**, choose an image, and press **Save**. The server checks the image type, dimensions, and file size before saving it.

Password reset links are valid for 60 minutes and can only be used once. XAMPP does not send email until a mail transport is configured in PHP. When using the site on `localhost`, the page can show a development reset link instead. For a hosted website, set up SMTP mail, use an HTTPS `APP_BASE_URL`, and set `MAIL_FROM_EMAIL` in `.env`.

## Tests and demo

The test plan has 29 cases in [docs/TEST_PLAN.md](docs/TEST_PLAN.md). The results are in [docs/TEST_EVIDENCE.md](docs/TEST_EVIDENCE.md). So far, 23 database smoke checks have passed on the separate demo database. Some browser-form and email-delivery checks are still marked as not run in the evidence file.

To run the smoke checks, use the demo database only:



