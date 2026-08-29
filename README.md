# Easyway Logistics Stages 1, 2 and 3

Stage 1 extends the original static Easyway homepage with a small custom PHP 8.2/MySQL logistics application.

## Included

- Public About, Services, International Destinations, Cargo, Packaging, Contact, Quote and Tracking pages.
- CSRF-protected contact and quote request storage.
- Staff authentication with login throttling.
- Staff dashboard, quote/message inbox and shipment register.
- Unique, non-sequential public tracking numbers.
- Validated shipment status transitions and public/internal event visibility.
- Audit records for important application actions.

## Stage 2 included

- Customer registration, secure sign-in throttling and saved pickup/delivery addresses.
- Staff-managed pricing zones and rate cards; no sample prices are enabled by default.
- Public quote calculator with actual versus volumetric chargeable weight.
- Customer online booking with server-side price recalculation and 48-hour price snapshots.
- Paystack server initialization, callback verification, signed and idempotent webhooks, and amount/currency matching.
- Automatic printable invoices and receipts.
- Email, SMS and WhatsApp notification outbox with retry support and opt-in provider delivery.
- Paid-booking conversion into tracked shipments.
- Proof of delivery with recipient, timestamp, optional coordinates and a privately stored photo.

## Stage 3 included

- Decision-focused operations dashboard and date-filtered reports with per-currency financial totals.
- Restricted rider accounts, one-active-assignment enforcement and assignment history.
- Rider-controlled live GPS sharing with public expiry and automatic shutoff on completion/unassignment.
- Corporate memberships, credit limits, payment terms, statements and staff-posted payments.
- Bulk CSV bookings (up to 500 rows / 2 MB) using saved addresses and the existing server-side rate engine.
- Air, sea and road cargo records with documentation, customs and movement milestones.
- CSV operations report export with visible metric definitions and freshness time.

## Local setup

1. Copy `.env.example` to `.env` and configure the local database values. A local `.env` is already present in this checkout and is ignored by Git.
2. Install or update all additive schemas:

   `php tools/install_stage3.php`

3. Set a strong password in the process environment and create the first administrator:

   PowerShell:

   `$env:EASYWAY_NEW_STAFF_PASSWORD='use-a-unique-12-plus-character-password'`

   `php tools/create_staff.php --email=you@example.com --name="Your Name" --role=admin`

   `Remove-Item Env:EASYWAY_NEW_STAFF_PASSWORD`

4. Sign in at `/easy/staff/`, open **Rates**, and configure approved business rates. The calculator intentionally sends unsupported routes to manual quoting until a rate is active.

5. Open `/easy/` for the public website, `/easy/customer/` for customer accounts, `/easy/staff/` for operations and `/easy/rider/` for assigned rider work.

## Stage 3 operating notes

- Only the currently assigned rider can send a shipment location. The rider explicitly starts sharing and may keep the trip dispatch-only.
- Public tracking returns only a recent, explicitly public location for an active shipment. Sharing stops on completion or unassignment.
- Schedule `php tools/prune_location_history.php` daily; `RIDER_LOCATION_RETENTION_DAYS` defaults to 30 days and accepts 1 to 365.
- Bulk template columns are: `pickup_address_id,delivery_address_id,origin_zone_id,destination_zone_id,service_code,package_description,weight_kg,length_cm,width_cm,height_cm,declared_value,packaging_required,is_fragile`.
- Corporate credit approval rechecks membership, currency, outstanding balance and credit limit inside a database transaction.

## Production integrations

- Keep `PAYSTACK_ENABLED=false` until `APP_URL` is the final HTTPS origin, `PAYSTACK_SECRET_KEY` is configured, and the Paystack dashboard webhook points to `/webhooks/paystack.php`.
- Paystack amounts are initialized on the server in currency subunits. Callback and webhook handling both verify status, exact amount and currency before confirming a booking.
- Keep email, SMS and WhatsApp flags disabled until their transports are configured. SMS/WhatsApp adapters POST JSON containing `to`, `message` and `reference` to the configured HTTPS webhook with an optional bearer token.
- Schedule `php tools/process_notifications.php` with cron or Task Scheduler after provider testing.
- Proof images are stored below `storage/proof-of-delivery` and are served only through an authenticated authorization check.

## Verification

- `php tools/qa/stage1_smoke.php`
- `php tools/qa/stage2_smoke.php`
- `php tools/qa/stage3_smoke.php`
- Lint all application PHP files and run `node --check` for every application JavaScript file.
- Verify registration, address creation, booking, invoice rendering, rate administration and proof of delivery in a browser.

The `.env`, `app`, `database` and `tools` paths are protected from direct Apache access. Production database credentials must be different from the local root configuration.
