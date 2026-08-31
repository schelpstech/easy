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

## Public social links

The supplied TikTok, Instagram and Facebook links appear in the homepage footer, the shared public/customer footer and the Contact page. URLs are centralized in `social_media_links()` in `app/functions.php`; optional `TIKTOK_URL`, `INSTAGRAM_URL` and `FACEBOOK_URL` environment settings override them. Leave a setting blank to hide that platform. Only valid HTTPS links are displayed. Links use the existing local icon assets, accessible labels and safe new-tab attributes; no third-party widgets or tracking scripts are loaded. The existing WhatsApp contact links are unchanged. Verify with `php tools/qa/social_links_smoke.php`.

## Stage 3 operating notes

- Only the currently assigned rider can send a shipment location. The rider explicitly starts sharing and may keep the trip dispatch-only.
- Public tracking returns only a recent, explicitly public location for an active shipment. Sharing stops on completion or unassignment.
- Schedule `php tools/prune_location_history.php` daily; `RIDER_LOCATION_RETENTION_DAYS` defaults to 30 days and accepts 1 to 365.
- Bulk template columns are: `pickup_address_id,delivery_address_id,origin_zone_id,destination_zone_id,service_code,package_description,weight_kg,length_cm,width_cm,height_cm,declared_value,packaging_required,is_fragile`.
- Corporate credit approval rechecks membership, currency, outstanding balance and credit limit inside a database transaction.

## Production integrations

### Staff accounts, passwords and delivery settings

- Run `php tools/install_staff_settings.php` once on each deployment. It adds only `notification_settings` and a private encryption key; it does not alter staff records or enable delivery.
- If the settings table already exists, the first administrator save containing a credential can securely create a missing key in `storage/private`. This is allowed only when there are no encrypted credentials already saved. Existing or invalid keys are never replaced automatically. PHP needs write access for initial setup and read access afterward.
- The Delivery Settings page reports missing keys, invalid environment keys, file-read permissions and unavailable OpenSSL separately. For a hosted deployment, the key and PHP extensions must exist on that host, not only on your local AMPPS machine. Restore the original key whenever encrypted credentials already exist.
- Administrators can open **Staff Accounts** to create administrator or dispatcher logins. Rider accounts remain in **Riders**. Account creation requires the administrator's current password and a confirmed initial password. Share that initial password privately.
- **Change Password** is available to staff and riders. It requires the current password, checks confirmation, and signs out all sessions of that account. Existing sessions created before this upgrade must sign in once again.
- Open **Delivery Settings** (administrator only) for Email, SMS and WhatsApp. Save drafts with delivery disabled, then use **Send test** to send one fixed message to an inbox/phone you control. Test acceptance is not a delivery receipt. Tests do not drain the outbox.
- Email supports hosting-managed server mail or authenticated SMTP with STARTTLS/implicit TLS (ports 587, 465 or 2525). PHP cURL with SMTP/SMTPS and OpenSSL support is required. Certificate verification is never disabled. SMTP support uses the [PHP cURL mail options](https://www.php.net/manual/en/function.curl-setopt.php).
- `Call to undefined function App\mail()` means the Server mail path was selected and PHP `mail()` was unavailable in that runtime; it is not an SMTP authentication failure. Select **Authenticated SMTP**, save the provider details while automatic delivery is disabled, send a test, then enable delivery after confirming receipt. Server mail ignores all SMTP fields; SMTP does not depend on `mail()`. PHP 8 removes definitions of [disabled functions](https://www.php.net/manual/en/ini.core.php#ini.disable-functions).
- Run `php tools/process_notifications.php --check` with the exact PHP executable and absolute script path used by cron. This read-only check shows the effective transport/configuration source and PHP capability problems without connecting to providers, displaying credentials or changing the outbox. It does not prove provider delivery. The regular command without `--check` sends pending notifications. Pending failed attempts retry when due (up to five attempts); terminal `failed` records are not automatically requeued by a settings change. Do not blanket-reset or resend old shipment updates.
- SMS and WhatsApp retain the provider-neutral adapter contract: HTTPS POST JSON `{ "to": "+234...", "message": "...", "reference": "EWN-123" }`, optionally with a Bearer token. The adapter must translate to the chosen provider, manage sender IDs/approved WhatsApp templates, reject provider failures with non-2xx responses, and deduplicate `reference` on retries. Do not enter a raw provider API unless it accepts this contract.
- Only public provider hosts are supported; local/private destinations and redirects are blocked. Webhook URLs cannot contain credentials, query parameters or fragments.
- Saved database settings override that channel's environment defaults for both the web portal and the CLI worker. Credentials are blank in forms, retained on an empty submission, and removed only with the explicit removal checkbox. Database credentials use [authenticated AES-256-GCM encryption](https://www.php.net/manual/en/function.openssl-encrypt.php).
- Securely back up `storage/private/notification-key.php` (or the `NOTIFICATION_SETTINGS_KEY` environment value) together with the database. Losing/changing the key makes saved credentials unreadable. Never commit it. Keep `storage` denied by the web server; non-Apache hosts need equivalent access rules.
- Enabling a channel allows the existing scheduled worker to send its pending backlog. Saving/testing does not schedule a job. Review the outbox before enabling. To stop sending, disable the channel and stop any already-running worker; an in-flight request may already have been accepted.
- Verify with `php tools/qa/staff_settings_smoke.php`. It uses temporary QA accounts and disabled settings, rolls back its changes and sends no messages.
- For HTTP workflow checks, start a local PHP development server and run `php tools/qa/staff_settings_http.php http://127.0.0.1:8099`. This checks real login, account creation, password changes, role restrictions and CSRF, then removes its own QA accounts without changing delivery settings.

### Actionable quotes and messages

- Run `php tools/install_inquiry_inbox.php` once per deployment. It adds only `inquiry_activities`; existing quote/contact records and delivery configuration remain intact. The inbox stays readable until installation, with actions disabled.
- Administrators and dispatchers open **Quotes & Messages**, choose Quote requests or Contact messages, filter by name/email/reference and status, then select **Open inquiry**. Riders and customer sessions cannot read or change this workspace.
- **Reply by email** queues a message to the inquiry's stored customer address. **Send quotation** queues the confirmed total, currency, original route/service and staff-entered terms. Delivery adds the shared branded HTML layout and a plain-text alternative; the saved message/quotation snapshot remains unchanged. It also updates the existing quote's latest amount/currency and retains each quotation snapshot in history. Neither action creates an invoice, booking or payment.
- Inquiry emails require enabled, configured **Authenticated SMTP** and use the existing `tools/process_notifications.php` schedule. Saving an action means queued, not delivered. History shows pending/retry/failed or mail-server acceptance. Acceptance is not proof of final delivery or reading. Disabling email pauses delivery; changing transport to Server mail does not silently reroute inquiry replies.
- **Staff notes** stay internal and are never added to customer emails. Statuses are staff-managed (New, In progress, Replied, Closed; quotes also have Quoted, Accepted and Declined). The first queued email moves New to In progress, not to Replied/Quoted. Use a status update to record subsequent follow-up or a customer's decision.
- Call and WhatsApp shortcuts open the staff member's external app; nothing is sent automatically. Nigerian mobile numbers in local format are normalized to +234. Record call/WhatsApp outcomes as internal notes. Customer email replies arrive in the configured sender mailbox; incoming email synchronization is not included.
- Activities record staff identity, time and exact outgoing content. Duplicate form submissions are idempotent; stale forms must be reviewed before resubmission. Notes and outgoing messages are retained as history rather than editable sent messages. The detail screen displays the latest 100 activities.
- The notification worker filters enabled channels before selecting its batch, so disabled-channel backlog cannot block newer inquiry emails. A database advisory lock prevents overlapping workers from sending the same selected batch. A process crash after provider acceptance but before the database update can still cause a retry; do not blanket-requeue old messages.
- Verify with `php tools/qa/inquiry_inbox_smoke.php`: temporary data/settings are rolled back, transport calls are mocked, and existing outbox rows remain unchanged. `tools/qa/inquiry_inbox_http.php` tests real POST/CSRF/role flows against a dedicated loopback PHP server with synthetic SMTP environment settings; it requires the ordinary local worker configuration to remain disabled, and removes its own pending emails and fixtures afterward. Never use QA scripts against production.

### New inquiry and quote alerts for staff

- Every successfully saved quote request or contact inquiry (homepage or dedicated form) now queues one branded email to **info@easyway.ng**. The optional `INQUIRY_ALERT_EMAIL` environment setting changes that single recipient; it must be a valid email address and is never taken from customer form fields. No database migration is required on the existing Stage 2/3 installation.
- Alerts contain the reference, submission time, customer contact details, complete message or shipment requirements/notes, and a button to the relevant **Quotes & Messages** inbox. The button uses the public HTTPS `APP_URL`; set `APP_URL=https://easyway.ng` on the live root deployment. Staff sign-in and existing permissions still apply. Find the reference in that inbox to respond and retain reply history. An alert is not a customer reply and does not change the inquiry's New status.
- Inquiry and outbox entry are saved in one transaction, so a queue-write failure cannot leave a successful request without its alert. Submission does not contact SMTP. Existing validation, CSRF, honeypot and short duplicate-submission cooldown continue to apply.
- The existing notification cron delivers the alert on its next run using the configured email transport (including your saved SMTP). Email must be enabled in **Delivery Settings**. Disabled email leaves alerts pending; delivery errors use the existing backoff/retry policy and appear in **Notification outbox**. No extra cron job is needed. For near-immediate alerts, schedule the existing worker every minute; do not run overlapping extra jobs. Already-created inquiries are not backfilled or resent by this change.
- `php tools/qa/inquiry_alerts_smoke.php` tests atomic queueing, content/branding, disabled delivery and mocked SMTP retries, rolling back all fixtures. The local-only `tools/qa/inquiry_alerts_http.php` covers both public entry points, validation, CSRF, spam and immediate duplicate submissions; it requires local automatic email delivery to be disabled and a synthetic alert recipient on its dedicated test server. Neither suite sends real email. Never run QA against production.

### Branded email layout

- All email delivery passes through `app/EmailTemplate.php` and `app/views/emails/notification.php`: booking/corporate confirmations, payment confirmations, shipment/cargo updates, proof-of-delivery notices, staff replies, quotations and Delivery Settings tests. Existing pending messages receive the design when next delivered; no outbox rewrite, resend, database migration or new mail service is needed. SMS/WhatsApp payloads remain unchanged.
- The navy/orange layout includes the actual `assets/img/easyway/logo.jpg` as an inline CID image, readable message paragraphs, a context-appropriate action, office address, both telephone numbers, support email, WhatsApp and the configured social links. It uses inline styles, presentation tables and system fonts. An unreadable/missing logo falls back to the Easyway name without blocking delivery; deploy the logo and both template files together.
- Contact details use the same `COMPANY_ADDRESS`, `SUPPORT_EMAIL`, `SUPPORT_PHONE`, `SUPPORT_PHONE_SECONDARY`, `WHATSAPP_NUMBER` and optional social URL settings as the website. `Reply-To` stays the configured email sender mailbox; use a monitored sender for staff inquiries. Internal notes and delivery credentials are never passed to the template.
- Set `APP_URL` to the final **public HTTPS** website base (including its subdirectory, if any) so account/booking/tracking buttons work from cron. Without a suitable public HTTPS URL, emails retain WhatsApp/contact actions and omit website links; they never use a request Host header or localhost. Account buttons still require customer sign-in; no private proof-of-delivery files are attached or made public.
- Both SMTP and native mail receive [multipart alternatives](https://www.rfc-editor.org/rfc/rfc2046#section-5.1.4) with UTF-8 text and HTML, and a [related inline logo](https://www.rfc-editor.org/rfc/rfc2387). Customer/staff text is HTML-escaped and preserved in the plain-text alternative. No remote fonts, external image requests or tracking pixels are added.
- Run `php tools/qa/email_branding_smoke.php` for offline transport/MIME/content checks (no database changes or real email). Add `--previews` to generate synthetic HTML and `.eml` fixtures under `storage/cache`; keep that directory protected and remove fixtures after review. Browser previews do not prove rendering in every email client. After deployment, use **Delivery Settings → Email → Send test** to an inbox you control and check the logo, contacts and layout in your actual Gmail/Outlook/mobile client. Testing does not enable automatic delivery or drain the customer outbox.

### Editable rates and pricing catalogues

- Run `php tools/install_rate_management.php` once after deploying. The repeatable installer adds only `rate_services`, seeds the original service codes and preserves any legacy custom codes. It does not change existing zones, rate prices or bookings. Older deployments continue using the original built-in services until installation.
- Administrators open **Rates → Rate cards** and select **Edit** to load an existing rate. All route, price, weight, surcharge, currency, delivery-time and availability fields are editable. **Add rate** creates a separate route; an existing origin/destination/service combination must be edited explicitly, never silently overwritten by a new form.
- **Origins & destinations** manages one shared location list. An active location can be used at either end of a route; there is no automatic reverse-direction price. **Services** manages the service names/codes used by the calculator, customer booking and corporate bulk validation. Adding a location or service does not invent a price: configure an active rate for the exact route/service before accepting online estimates.
- Catalogue names and statuses are editable. Codes stay stable after creation so integrations and bulk CSV references remain valid. Inactive entries are hidden from new customer selections and their routes cannot be priced, including direct requests. Reactivating an entry restores its otherwise-active rates. Records are not deleted, and existing booking price/service snapshots are not rewritten.
- Saves use existing staff authentication, admin permissions, CSRF protection, database validation and audit logging. Prefilled edit forms carry a content version; stale edits must be reloaded rather than overwriting a colleague's work. Validation failures preserve entered values. The internal `PricingService::saveRate` API retains legacy route-upsert compatibility, while staff form calls explicitly use strict create/edit mode.
- Verify locally with `php tools/qa/rate_management_smoke.php` (transaction rollback, no deliveries) and `php tools/qa/rate_management_http.php http://127.0.0.1:8099` against a local PHP server. HTTP QA creates and removes only its own fixtures and verifies that existing rates/bookings/outbox data are unchanged. Never run QA against production.

### Payments and scheduled delivery

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
