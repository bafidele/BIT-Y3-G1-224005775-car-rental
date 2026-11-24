# Car Rental System test plan

Run the workflow against a disposable database imported from `database/schema.sql` and `database/seed.sql`. Do not use real customer records for test mutations. The seeded payment examples are simulations; no money is collected by this application.

| # | Area | Test | Expected result |
|---:|---|---|---|
| 1 | Login | Sign in with the seeded administrator and initial password | First-login password change is required; a new session ID is issued after authentication |
| 2 | Login | Submit login without a CSRF token | Request is rejected and no account is signed in |
| 3 | Registration | Register a customer with valid details and password | Customer row is created with a password hash; sign-in works with the new account |
| 4 | Registration | Submit invalid email, duplicate email/ID, or too-short password | Server rejects the request and no duplicate account is created |
| 5 | Browse | Open Browse Cars with JavaScript enabled and search by model/plate | Fetch calls `api/vehicles.php`; matching cards and result count update without reloading |
| 6 | Browse fallback | Disable JavaScript and filter the fleet | GET form reloads the page and shows the filtered vehicles |
| 7 | Vehicle CRUD | Administrator adds a valid vehicle | Vehicle appears in the fleet and browse list as available |
| 8 | Vehicle validation | Submit a missing plate/model/type or non-positive rate | Server rejects the input; no vehicle row is inserted |
| 9 | Vehicle CRUD | Edit a vehicle's plate, model, type, and rate | Updated values appear in management and browse pages |
| 10 | Vehicle CRUD | Delete a vehicle with no rentals | Vehicle is removed |
| 11 | Referential integrity | Try to delete a vehicle with rental history | Deletion is blocked and history remains intact |
| 12 | Customer CRUD | Administrator edits a customer's contact details | The profile values are updated; duplicate email/ID is rejected |
| 13 | Customer CRUD | Delete a customer without rental history | Customer record is removed |
| 14 | Referential integrity | Try to delete a customer with rental history | Deletion is blocked to preserve receipts and history |
| 15 | Staff registration | Administrator creates a staff account | Password is hashed and first login requires a password change |
| 16 | Authorization | Customer submits a staff-only vehicle/customer/staff action | Server returns a forbidden response and does not change data |
| 17 | Rental flow | Customer requests an available vehicle with valid dates and accepted terms | Pending request is saved, car becomes rented, and total is calculated server-side |
| 18 | Rental validation | Customer submits return time before pick-up or omits terms acceptance | Request is rejected and no rental is created |
| 19 | Payment review | Customer selects I have paid, then staff approves the submitted request | Status moves through payment submitted to active; receipt becomes downloadable |
| 20 | Return | Staff marks an active rental returned | Rental is completed, vehicle becomes available, and completed count increases |
| 21 | Receipt access | Customer requests another customer's receipt ID | Server returns not found/forbidden and does not disclose the receipt |
| 22 | Logout | Submit logout without a CSRF token or use GET | Request is rejected; valid footer POST ends the session |
| 23 | Password reset | Request a reset for an existing customer account | A one-hour, one-use reset link is sent; the page gives the same response for unknown emails |
| 24 | Password reset | Submit an expired, altered, or already-used reset link | Password remains unchanged and the customer is asked to request a new link |
| 25 | Password reset | Submit a short password, mismatched confirmation, or missing CSRF token | Server rejects the change; the existing password remains valid |
| 26 | Password reset | Complete reset with a valid link and sign in using the new password | Password is stored as a hash, prior reset links are invalidated, and new sign-in succeeds |
| 27 | HTML integration | Open `cars.html`, search for a car, and change the availability filter | HTML loads the shared CSS and JavaScript; Fetch gets JSON from the PHP API and updates the car list |

## Execution notes

Record the date, database used, PHP/MySQL versions, tester, and pass/fail result for each case in `docs/TEST_EVIDENCE.md`. Include a screenshot of the live-search result and one approved receipt with personal information removed if the instructor needs visual evidence.
