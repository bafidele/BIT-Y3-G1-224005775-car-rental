# 2–3 minute live demonstration script

Use a local database imported from `database/schema.sql` and `database/seed.sql`. These seeded rental rows simulate payment states; do not present them as real transactions.

1. **0:00–0:20 — HTML and PHP connection:** Open `cars.html`. Point out that it is a separate HTML page and that its car list is loaded from the PHP JSON endpoint.
2. **0:20–0:40 — Async search:** Search by model or plate and change availability. Show the results updating without a reload, then open `browse.php` to show the full booking page and fallback.
3. **0:40–1:00 — Customer access:** Register a free customer account or sign in to the demo customer. Show the customer dashboard and rental history.
4. **1:00–1:25 — Rental request:** Select an available car, choose pick-up/return times, open and accept the rental terms, then submit the request. Explain that payment is handled using staff instructions, not collected by the website.
5. **1:25–1:55 — Staff approval:** Sign in as administrator, open Customers & Receipts, verify a submitted payment, and approve the rental. Show the customer receipt download becomes available.
6. **1:55–2:20 — MVC and CRUD:** Open Vehicle Management, edit a vehicle, then show the deletion guard for a vehicle with rental history. Open customer management and show edit/delete protection.
7. **2:20–2:40 — Security and data:** Show that staff account creation is admin-only, new passwords require a reset, and the database uses separate users/customers tables with rental foreign keys.
8. **2:40–3:00 — Close:** Point to the README, schema/seed files, and test plan/evidence.
