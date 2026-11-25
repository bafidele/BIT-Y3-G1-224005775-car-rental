# Test evidence

Execution date: 2026-11-22. Environment: XAMPP PHP 8.2.12, MariaDB 10.4.32, isolated `car_rental_demo` database. Password reset verification ran only against `car_rental_demo`.

The additive password-reset table migration was also applied to the active `car_rental_db` so the current local app can use the feature. It created one empty table and did not alter customer, staff, vehicle, or rental rows.

| Check | Result | Evidence / notes |
|---|---|---|
| PHP syntax lint | PASS | `php -l` passed for all 45 application and test PHP files. |
| JavaScript syntax | PASS | Node syntax check passed for `assets/js/browse.js`. |
| Database schema and seed import | PASS | Fresh isolated schema imported; seeded counts: 1 staff user, 1 customer, 3 vehicles, 3 rentals. |
| Vehicle model CRUD and validation | PASS | Smoke checks covered valid/invalid input, create, read, update, status change, delete, search, and blocking deletion with rental history. |
| Customer model CRUD and validation | PASS | Smoke checks covered validation, hashed password, update, delete without rentals, customer list/counts, and blocking deletion with rental history. |
| Staff model CRUD and password handling | PASS | Smoke checks covered short-password rejection, hashed staff password, create, read, update, and delete while retaining the seeded administrator. |
| Customer password reset lifecycle | PASS | Four isolated database assertions covered request throttling, token consumption, hashed password update, and rejection of token reuse; the outer test transaction rolled all changes back. |
| JSON vehicle search | PASS | Endpoint returned valid JSON with one matching Toyota result from the isolated database. |
| HTTP page/API smoke | PASS | Loopback test served Browse Cars and the JSON API; an unauthenticated request to customer management redirected to login. |
| Login and logout security | PASS | Missing login CSRF token was rejected; seeded admin was required to change its password; POST logout with CSRF ended both admin and customer sessions. |
| Role authorization | PASS | A signed-in customer received HTTP 403 on Vehicle Management. |
| Customer request through receipt/return | PASS | Over HTTP, the customer submitted a request, confirmed payment, staff approved it, customer downloaded a PDF, and staff marked it returned. |
| Test cleanup | PASS | The temporary rental was removed, the test car was restored to available, and the demo admin's initial password/reset requirement was restored. |
| Customer self-registration UI and duplicate/invalid form cases | NOT RUN | Model validation and password hashing were smoke-checked; interactive registration failure cases remain in `TEST_PLAN.md`. |
| Vehicle/customer/staff CRUD through browser forms | NOT RUN | MVC models and operations were smoke-checked; interactive admin form cases remain in `TEST_PLAN.md`. |
| Rental date/terms negative cases and cross-customer receipt denial | NOT RUN | Manual security/validation cases remain in `TEST_PLAN.md`. |
| Password reset email delivery and browser flow | NOT RUN | PHP `mail()` has no configured transport in this XAMPP setup. The localhost-only link fallback and reset pages are implemented; email delivery requires PHP mail/SMTP configuration. |
| Standalone HTML page and PHP API in browser | NOT RUN | `cars.html` now links the shared CSS and a JavaScript Fetch client to `api/vehicles.php`; run test case 27 in a browser and record the result. |

The 23 database smoke assertions passed. The customer/admin HTTP rental flow used only the isolated demo database; test data was cleaned up and the sample administrator reset was restored. Remaining manual cases are listed explicitly rather than marked as passed.
