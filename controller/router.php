<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Auth;
use App\AddressService;
use App\BookingService;
use App\BulkShipmentService;
use App\CargoService;
use App\CorporateService;
use App\CustomerAuth;
use App\Csrf;
use App\Flash;
use App\InquiryService;
use App\PaymentService;
use App\PricingService;
use App\ProofOfDeliveryService;
use App\RiderService;
use App\ShipmentService;
use App\Validator;
use App\StaffAccountService;
use App\NotificationSettings;
use App\NotificationService;

$action = (string) ($_GET['action'] ?? '');

try {
    switch ($action) {
        case 'contact.submit':
            require_post();
            require_csrf();
            $returnTo = ($_POST['_return'] ?? '') === 'index.php' ? 'index.php#contact' : 'contact.php';

            if (!empty($_POST['website'])) {
                Flash::set('success', 'Thank you. Your message has been received.');
                redirect($returnTo);
            }
            if (!submission_allowed('contact')) {
                Flash::set('warning', 'Please wait a moment before sending another message.');
                redirect($returnTo);
            }

            [$data, $errors] = Validator::contact($_POST);
            if ($errors !== []) {
                store_form_state('contact', $data, $errors);
                Flash::set('danger', 'Please check the highlighted contact details.');
                redirect($returnTo);
            }

            $reference = InquiryService::createContact($data);
            Flash::set('success', 'Thank you. Your message reference is ' . $reference . '. Our team will contact you shortly.');
            redirect($returnTo);

        case 'quote.submit':
            require_post();
            require_csrf();
            $returnTo = ($_POST['_return'] ?? '') === 'index.php' ? 'index.php#quote' : 'quote.php';

            if (!empty($_POST['website'])) {
                Flash::set('success', 'Thank you. Your quote request has been received.');
                redirect($returnTo);
            }
            if (!submission_allowed('quote')) {
                Flash::set('warning', 'Please wait a moment before sending another quote request.');
                redirect($returnTo);
            }

            [$data, $errors] = Validator::quote($_POST);
            if ($errors !== []) {
                store_form_state('quote', $data, $errors);
                Flash::set('danger', 'Please check the highlighted shipment details.');
                redirect($returnTo);
            }

            $reference = InquiryService::createQuote($data);
            Flash::set('success', 'Your quote request ' . $reference . ' has been received. We will confirm availability and pricing with you.');
            redirect($returnTo);

        case 'staff.login':
            require_post();
            require_csrf();
            $email = Validator::email($_POST['email'] ?? '');
            $password = (string) ($_POST['password'] ?? '');
            if ($email !== '' && $password !== '' && Auth::attempt($email, $password)) {
                Flash::set('success', 'Welcome back.');
                redirect(staff_home_path());
            }
            Flash::set('danger', 'The sign-in details are incorrect or temporarily locked.');
            redirect('staff/login.php');

        case 'staff.account.create':
            require_post(); require_csrf(); Auth::requireRole(['admin']);
            store_form_state('staff_account', [
                'full_name' => Validator::text($_POST['full_name'] ?? '', 120),
                'email' => Validator::email($_POST['email'] ?? ''),
                'role' => Validator::choice($_POST['role'] ?? '', ['admin', 'dispatcher']),
            ], []);
            StaffAccountService::create($_POST);
            pull_form_state('staff_account');
            Flash::set('success', 'Staff account created. Share the initial password securely and ask the staff member to change it after signing in.');
            redirect('staff/accounts.php');

        case 'staff.password.change':
            require_post(); require_csrf(); Auth::requireStaff();
            StaffAccountService::changePassword((string) ($_POST['current_password'] ?? ''), (string) ($_POST['password'] ?? ''), (string) ($_POST['password_confirmation'] ?? ''));
            Flash::set('success', 'Password changed. All staff sessions for your account have been signed out. Sign in with your new password.');
            redirect('staff/login.php');

        case 'staff.notification_settings.save':
            require_post(); require_csrf(); Auth::requireRole(['admin']);
            $channel = Validator::choice($_POST['channel'] ?? '', NotificationSettings::CHANNELS);
            NotificationSettings::save($channel, $_POST);
            Flash::set('success', 'Delivery settings saved. Use Send test to check the saved configuration.');
            redirect('staff/settings.php?channel=' . $channel);

        case 'staff.notification_settings.test':
            require_post(); require_csrf(); Auth::requireRole(['admin']);
            if (!submission_allowed('notification_test', 30)) { throw new RuntimeException('Wait 30 seconds before sending another test.'); }
            $channel = Validator::choice($_POST['channel'] ?? '', NotificationSettings::CHANNELS);
            NotificationService::sendTest($channel, (string) ($_POST['recipient'] ?? ''), (string) ($_POST['current_password'] ?? ''));
            Flash::set('success', 'The saved transport accepted the test. Check the recipient inbox or phone; acceptance does not confirm final delivery.');
            redirect('staff/settings.php?channel=' . $channel);

        case 'customer.register':
            require_post();
            require_csrf();
            if (!submission_allowed('customer_register', 5)) {
                Flash::set('warning', 'Please wait a moment before trying to create another account.');
                redirect('customer/register.php');
            }
            $data = [
                'full_name' => Validator::text($_POST['full_name'] ?? '', 120),
                'email' => Validator::email($_POST['email'] ?? ''),
                'phone' => Validator::phone($_POST['phone'] ?? ''),
            ];
            $password = (string) ($_POST['password'] ?? '');
            $errors = [];
            if (mb_strlen($data['full_name']) < 2) { $errors['full_name'] = 'Enter your full name.'; }
            if ($data['email'] === '') { $errors['email'] = 'Enter a valid email address.'; }
            if ($data['phone'] === '') { $errors['phone'] = 'Enter a valid phone number.'; }
            if (mb_strlen($password) < 12) { $errors['password'] = 'Use at least 12 characters.'; }
            if (!hash_equals($password, (string) ($_POST['password_confirmation'] ?? ''))) { $errors['password_confirmation'] = 'The passwords do not match.'; }
            if ($errors !== []) {
                store_form_state('customer_register', $data, $errors);
                Flash::set('danger', 'Please check the highlighted account details.');
                redirect('customer/register.php');
            }
            CustomerAuth::register($data['full_name'], $data['email'], $data['phone'], $password);
            Flash::set('success', 'Welcome to Easyway. Add your pickup and delivery addresses to book online.');
            redirect('customer/addresses.php');

        case 'customer.login':
            require_post();
            require_csrf();
            $email = Validator::email($_POST['email'] ?? '');
            if ($email !== '' && CustomerAuth::attempt($email, (string) ($_POST['password'] ?? ''))) {
                Flash::set('success', 'Welcome back.');
                redirect('customer/index.php');
            }
            Flash::set('danger', 'The sign-in details are incorrect or temporarily locked.');
            redirect('customer/login.php');

        case 'customer.logout':
            require_post();
            require_csrf();
            CustomerAuth::logout();
            Flash::set('success', 'You have been signed out.');
            redirect('customer/login.php');

        case 'customer.address.create':
            require_post();
            require_csrf();
            CustomerAuth::requireCustomer();
            $country = strtoupper(Validator::text($_POST['country_code'] ?? 'NG', 2));
            $data = [
                'label' => Validator::text($_POST['label'] ?? '', 80),
                'recipient_name' => Validator::text($_POST['recipient_name'] ?? '', 120),
                'phone' => Validator::phone($_POST['phone'] ?? ''),
                'address_line' => Validator::text($_POST['address_line'] ?? '', 255),
                'city' => Validator::text($_POST['city'] ?? '', 100),
                'state_name' => Validator::text($_POST['state_name'] ?? '', 100),
                'country_code' => preg_match('/^[A-Z]{2}$/', $country) ? $country : '',
                'directions' => Validator::text($_POST['directions'] ?? '', 500),
                'is_default' => isset($_POST['is_default']),
            ];
            $errors = [];
            foreach (['label','recipient_name','phone','address_line','city','state_name','country_code'] as $field) {
                if ($data[$field] === '') { $errors[$field] = 'This field is required.'; }
            }
            if ($errors !== []) {
                store_form_state('customer_address', $data, $errors);
                Flash::set('danger', 'Please check the highlighted address details.');
                redirect('customer/addresses.php#new-address');
            }
            AddressService::create((int) CustomerAuth::id(), $data);
            Flash::set('success', 'Address saved to your account.');
            redirect('customer/addresses.php');

        case 'booking.create':
            require_post();
            require_csrf();
            CustomerAuth::requireCustomer();
            if (!submission_allowed('booking_create', 3)) {
                Flash::set('warning', 'Please wait a moment before creating another booking.');
                redirect('customer/book.php');
            }
            $number = static function (mixed $value, float $min, float $max): float {
                $valid = filter_var($value, FILTER_VALIDATE_FLOAT);
                return $valid === false || $valid < $min || $valid > $max ? 0.0 : round((float) $valid, 2);
            };
            $data = [
                'pickup_address_id' => (int) ($_POST['pickup_address_id'] ?? 0),
                'delivery_address_id' => (int) ($_POST['delivery_address_id'] ?? 0),
                'origin_zone_id' => (int) ($_POST['origin_zone_id'] ?? 0),
                'destination_zone_id' => (int) ($_POST['destination_zone_id'] ?? 0),
                'service_code' => Validator::choice($_POST['service_code'] ?? '', array_keys(PricingService::services())),
                'package_description' => Validator::text($_POST['package_description'] ?? '', 500),
                'weight_kg' => $number($_POST['weight_kg'] ?? null, 0.01, 100000),
                'length_cm' => $number($_POST['length_cm'] ?? 0, 0, 100000),
                'width_cm' => $number($_POST['width_cm'] ?? 0, 0, 100000),
                'height_cm' => $number($_POST['height_cm'] ?? 0, 0, 100000),
                'declared_value' => $number($_POST['declared_value'] ?? 0, 0, 1000000000),
                'is_fragile' => isset($_POST['is_fragile']),
                'packaging_required' => isset($_POST['packaging_required']),
            ];
            $errors = [];
            foreach (['pickup_address_id','delivery_address_id','origin_zone_id','destination_zone_id'] as $field) {
                if ((int) $data[$field] < 1) { $errors[$field] = 'Choose an option.'; }
            }
            if ($data['service_code'] === '') { $errors['service_code'] = 'Choose a service.'; }
            if (mb_strlen($data['package_description']) < 3) { $errors['package_description'] = 'Describe the package.'; }
            if ($data['weight_kg'] <= 0) { $errors['weight_kg'] = 'Enter a valid weight.'; }
            if ($errors !== []) {
                store_form_state('booking', $data, $errors);
                Flash::set('danger', 'Please check the highlighted booking details.');
                redirect('customer/book.php');
            }
            try {
                $bookingId = BookingService::create($data, (int) CustomerAuth::id());
            } catch (RuntimeException $exception) {
                store_form_state('booking', $data, []);
                Flash::set('danger', $exception->getMessage());
                redirect('customer/book.php');
            }
            Flash::set('success', 'Booking created. Review the invoice and complete payment when ready.');
            redirect('customer/booking.php?id=' . $bookingId);

        case 'payment.initialize':
            require_post();
            require_csrf();
            CustomerAuth::requireCustomer();
            $bookingId = filter_var($_POST['booking_id'] ?? null, FILTER_VALIDATE_INT);
            if ($bookingId === false || $bookingId < 1) { throw new RuntimeException('Booking not found.'); }
            try {
                $checkoutUrl = PaymentService::initialize((int) $bookingId, (int) CustomerAuth::id());
            } catch (RuntimeException $exception) {
                Flash::set('danger', $exception->getMessage());
                redirect('customer/booking.php?id=' . (int) $bookingId);
            }
            header('Location: ' . $checkoutUrl, true, 303);
            exit;

        case 'staff.logout':
            require_post();
            require_csrf();
            Auth::logout();
            Flash::set('success', 'You have been signed out.');
            redirect('staff/login.php');

        case 'staff.shipment.create':
            require_post();
            require_csrf();
            Auth::requireRole(['admin', 'dispatcher']);

            $weight = filter_var($_POST['weight_kg'] ?? null, FILTER_VALIDATE_FLOAT);
            $expectedDate = Validator::text($_POST['expected_delivery_at'] ?? '', 10);
            $data = [
                'customer_name' => Validator::text($_POST['customer_name'] ?? '', 120),
                'customer_email' => Validator::email($_POST['customer_email'] ?? ''),
                'customer_phone' => Validator::phone($_POST['customer_phone'] ?? ''),
                'origin' => Validator::text($_POST['origin'] ?? '', 190),
                'destination' => Validator::text($_POST['destination'] ?? '', 190),
                'service_type' => Validator::choice($_POST['service_type'] ?? '', [
                    'Standard Delivery', 'Express Delivery', 'Same-Day Delivery', 'International Delivery', 'Cargo / Freight',
                ]),
                'package_description' => Validator::text($_POST['package_description'] ?? '', 500),
                'weight_kg' => $weight === false || $weight <= 0 ? null : round((float) $weight, 2),
                'expected_delivery_at' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $expectedDate) ? $expectedDate . ' 17:00:00' : null,
            ];

            $errors = [];
            foreach (['customer_name', 'customer_phone', 'origin', 'destination', 'service_type', 'package_description'] as $field) {
                if ($data[$field] === '') {
                    $errors[$field] = 'This field is required.';
                }
            }
            if (($_POST['customer_email'] ?? '') !== '' && $data['customer_email'] === '') {
                $errors['customer_email'] = 'Enter a valid email address.';
            }

            if ($errors !== []) {
                store_form_state('shipment', $data, $errors);
                Flash::set('danger', 'Please check the highlighted shipment details.');
                redirect('staff/shipments.php#new-shipment');
            }

            $trackingNumber = ShipmentService::create($data, (int) Auth::id());
            Flash::set('success', 'Shipment created. Tracking number: ' . $trackingNumber);
            redirect('staff/shipments.php');

        case 'staff.shipment.event':
            require_post();
            require_csrf();
            Auth::requireRole(['admin', 'dispatcher']);

            $shipmentId = filter_var($_POST['shipment_id'] ?? null, FILTER_VALIDATE_INT);
            if ($shipmentId === false || $shipmentId < 1) {
                throw new RuntimeException('Shipment not found.');
            }
            $status = Validator::choice($_POST['status'] ?? '', array_keys(ShipmentService::statuses()));
            $eventTimeInput = Validator::text($_POST['event_time'] ?? '', 20);
            $eventTime = DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $eventTimeInput);
            $data = [
                'status' => $status,
                'title' => Validator::text($_POST['title'] ?? '', 160),
                'description' => Validator::text($_POST['description'] ?? '', 3000),
                'location' => Validator::text($_POST['location'] ?? '', 190),
                'event_time' => $eventTime ? $eventTime->format('Y-m-d H:i:s') : date('Y-m-d H:i:s'),
                'is_public' => isset($_POST['is_public']),
            ];
            ShipmentService::addEvent((int) $shipmentId, $data, (int) Auth::id());
            Flash::set('success', 'Shipment status updated.');
            redirect('staff/shipment.php?id=' . (int) $shipmentId);

        case 'staff.rate.save':
            require_post();
            require_csrf();
            Auth::requireRole(['admin']);
            $decimal = static function (mixed $value, float $min, float $max): float {
                $valid = filter_var($value, FILTER_VALIDATE_FLOAT);
                if ($valid === false || $valid < $min || $valid > $max) { throw new RuntimeException('One or more rate values are outside the allowed range.'); }
                return round((float) $valid, 3);
            };
            $daysMin = filter_var($_POST['estimated_days_min'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 365]]);
            $daysMax = filter_var($_POST['estimated_days_max'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 365]]);
            $data = [
                'origin_zone_id' => (int) ($_POST['origin_zone_id'] ?? 0), 'destination_zone_id' => (int) ($_POST['destination_zone_id'] ?? 0),
                'service_code' => Validator::choice($_POST['service_code'] ?? '', array_keys(PricingService::services())),
                'currency' => Validator::choice(strtoupper((string) ($_POST['currency'] ?? 'NGN')), ['NGN','USD','GBP','EUR']),
                'base_fee' => $decimal($_POST['base_fee'] ?? null, 0, 1000000000),
                'base_weight_kg' => $decimal($_POST['base_weight_kg'] ?? null, 0.01, 100000),
                'extra_kg_fee' => $decimal($_POST['extra_kg_fee'] ?? null, 0, 1000000000),
                'minimum_fee' => $decimal($_POST['minimum_fee'] ?? 0, 0, 1000000000),
                'fuel_percent' => $decimal($_POST['fuel_percent'] ?? 0, 0, 100),
                'insurance_percent' => $decimal($_POST['insurance_percent'] ?? 0, 0, 100),
                'packaging_fee' => $decimal($_POST['packaging_fee'] ?? 0, 0, 1000000000),
                'tax_percent' => $decimal($_POST['tax_percent'] ?? 0, 0, 100),
                'volumetric_divisor' => $decimal($_POST['volumetric_divisor'] ?? 5000, 1, 100000),
                'estimated_days_min' => $daysMin === false ? null : $daysMin,
                'estimated_days_max' => $daysMax === false ? null : $daysMax,
                'status' => Validator::choice($_POST['status'] ?? 'active', ['active','inactive']),
            ];
            if ($data['origin_zone_id'] < 1 || $data['destination_zone_id'] < 1 || $data['service_code'] === '' || $data['currency'] === '') {
                throw new RuntimeException('Choose a valid route, service and currency.');
            }
            if ($data['estimated_days_min'] !== null && $data['estimated_days_max'] !== null && $data['estimated_days_min'] > $data['estimated_days_max']) {
                throw new RuntimeException('The minimum delivery time cannot be greater than the maximum.');
            }
            PricingService::saveRate($data, (int) Auth::id());
            Flash::set('success', 'Rate card saved. The calculator now uses this route and service.');
            redirect('staff/rates.php');

        case 'staff.booking.convert':
            require_post();
            require_csrf();
            Auth::requireRole(['admin','dispatcher']);
            $bookingId = filter_var($_POST['booking_id'] ?? null, FILTER_VALIDATE_INT);
            if ($bookingId === false || $bookingId < 1) { throw new RuntimeException('Booking not found.'); }
            $tracking = BookingService::convertToShipment((int) $bookingId, (int) Auth::id());
            Flash::set('success', 'Shipment created from the paid booking: ' . $tracking);
            redirect('staff/bookings.php');

        case 'staff.pod.create':
            require_post();
            require_csrf();
            Auth::requireRole(['admin','dispatcher']);
            $shipmentId = filter_var($_POST['shipment_id'] ?? null, FILTER_VALIDATE_INT);
            $recipient = Validator::text($_POST['recipient_name'] ?? '', 120);
            $deliveredInput = Validator::text($_POST['delivered_at'] ?? '', 20);
            $delivered = DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $deliveredInput);
            if ($shipmentId === false || $shipmentId < 1 || mb_strlen($recipient) < 2 || !$delivered) {
                throw new RuntimeException('Enter the recipient and a valid delivery date and time.');
            }
            $lat = filter_var($_POST['latitude'] ?? null, FILTER_VALIDATE_FLOAT);
            $lng = filter_var($_POST['longitude'] ?? null, FILTER_VALIDATE_FLOAT);
            ProofOfDeliveryService::capture((int) $shipmentId, [
                'recipient_name' => $recipient,
                'delivery_note' => Validator::text($_POST['delivery_note'] ?? '', 1000),
                'latitude' => $lat === false || $lat < -90 || $lat > 90 ? null : $lat,
                'longitude' => $lng === false || $lng < -180 || $lng > 180 ? null : $lng,
                'delivered_at' => $delivered->format('Y-m-d H:i:s'),
            ], $_FILES['delivery_photo'] ?? null, (int) Auth::id());
            Flash::set('success', 'Proof of delivery saved and the shipment was marked delivered.');
            redirect('staff/shipment.php?id=' . (int) $shipmentId);

        case 'staff.rider.create':
            require_post(); require_csrf(); Auth::requireRole(['admin']);
            $rider = [
                'full_name' => Validator::text($_POST['full_name'] ?? '', 120),
                'email' => Validator::email($_POST['email'] ?? ''),
                'phone' => Validator::phone($_POST['phone'] ?? ''),
                'vehicle_type' => Validator::choice($_POST['vehicle_type'] ?? '', ['motorcycle','car','van','truck','bicycle']),
                'vehicle_registration' => Validator::text($_POST['vehicle_registration'] ?? '', 40),
                'licence_number' => Validator::text($_POST['licence_number'] ?? '', 80),
                'emergency_contact' => Validator::text($_POST['emergency_contact'] ?? '', 120),
                'password' => (string) ($_POST['password'] ?? ''),
            ];
            if (mb_strlen($rider['full_name']) < 2 || $rider['email'] === '' || $rider['phone'] === '' || $rider['vehicle_type'] === '' || mb_strlen($rider['password']) < 12) {
                throw new RuntimeException('Enter the rider name, valid contact details, vehicle and a password of at least 12 characters.');
            }
            RiderService::create($rider, (int) Auth::id());
            Flash::set('success', 'Rider account created and ready for assignment.');
            redirect('staff/riders.php');

        case 'staff.rider.assign':
            require_post(); require_csrf(); Auth::requireRole(['admin','dispatcher']);
            $shipmentId = filter_var($_POST['shipment_id'] ?? null, FILTER_VALIDATE_INT);
            $riderId = filter_var($_POST['rider_id'] ?? null, FILTER_VALIDATE_INT);
            if ($shipmentId === false || $riderId === false) { throw new RuntimeException('Choose a shipment and rider.'); }
            RiderService::assign((int) $shipmentId, (int) $riderId, Validator::text($_POST['assignment_note'] ?? '', 500), (int) Auth::id());
            Flash::set('success', 'Rider assigned to this shipment.');
            redirect('staff/shipment.php?id=' . (int) $shipmentId);

        case 'staff.rider.status':
            require_post(); require_csrf(); Auth::requireRole(['admin']);
            $riderId = filter_var($_POST['rider_id'] ?? null, FILTER_VALIDATE_INT);
            if ($riderId === false) { throw new RuntimeException('Rider not found.'); }
            RiderService::setActive((int) $riderId, ($_POST['active'] ?? '') === '1', (int) Auth::id());
            Flash::set('success', ($_POST['active'] ?? '') === '1' ? 'Rider account activated.' : 'Rider account deactivated.');
            redirect('staff/riders.php');

        case 'staff.rider.unassign':
            require_post(); require_csrf(); Auth::requireRole(['admin','dispatcher']);
            $shipmentId = filter_var($_POST['shipment_id'] ?? null, FILTER_VALIDATE_INT);
            if ($shipmentId === false) { throw new RuntimeException('Shipment not found.'); }
            RiderService::unassign((int) $shipmentId, (int) Auth::id());
            Flash::set('success', 'Rider unassigned and location sharing stopped.');
            redirect('staff/shipment.php?id=' . (int) $shipmentId);

        case 'rider.shipment.event':
            require_post(); require_csrf(); Auth::requireRole(['rider']);
            $shipmentId = filter_var($_POST['shipment_id'] ?? null, FILTER_VALIDATE_INT);
            if ($shipmentId === false || !RiderService::canAccessShipment((int) Auth::id(), (int) $shipmentId)) { throw new RuntimeException('This shipment is not assigned to you.'); }
            $status = Validator::choice($_POST['status'] ?? '', array_keys(ShipmentService::statuses()));
            $eventTime = DateTimeImmutable::createFromFormat('Y-m-d\TH:i', Validator::text($_POST['event_time'] ?? '', 20));
            ShipmentService::addEvent((int) $shipmentId, [
                'status' => $status, 'title' => '', 'description' => Validator::text($_POST['description'] ?? '', 1000),
                'location' => Validator::text($_POST['location'] ?? '', 190),
                'event_time' => $eventTime ? $eventTime->format('Y-m-d H:i:s') : date('Y-m-d H:i:s'), 'is_public' => true,
            ], (int) Auth::id());
            Flash::set('success', 'Shipment milestone updated.');
            redirect('rider/index.php?shipment=' . (int) $shipmentId);

        case 'rider.pod.create':
            require_post(); require_csrf(); Auth::requireRole(['rider']);
            $shipmentId = filter_var($_POST['shipment_id'] ?? null, FILTER_VALIDATE_INT);
            $delivered = DateTimeImmutable::createFromFormat('Y-m-d\TH:i', Validator::text($_POST['delivered_at'] ?? '', 20));
            $recipient = Validator::text($_POST['recipient_name'] ?? '', 120);
            if ($shipmentId === false || !$delivered || mb_strlen($recipient) < 2 || !RiderService::canAccessShipment((int) Auth::id(), (int) $shipmentId)) { throw new RuntimeException('Enter valid delivery details for your assigned shipment.'); }
            $lat = filter_var($_POST['latitude'] ?? null, FILTER_VALIDATE_FLOAT); $lng = filter_var($_POST['longitude'] ?? null, FILTER_VALIDATE_FLOAT);
            ProofOfDeliveryService::capture((int) $shipmentId, [
                'recipient_name' => $recipient, 'delivery_note' => Validator::text($_POST['delivery_note'] ?? '', 1000),
                'latitude' => $lat === false ? null : $lat, 'longitude' => $lng === false ? null : $lng,
                'delivered_at' => $delivered->format('Y-m-d H:i:s'),
            ], $_FILES['delivery_photo'] ?? null, (int) Auth::id());
            Flash::set('success', 'Proof of delivery saved. The assignment is complete.');
            redirect('rider/index.php');

        case 'staff.corporate.create':
            require_post(); require_csrf(); Auth::requireRole(['admin']);
            $limit = filter_var($_POST['credit_limit'] ?? null, FILTER_VALIDATE_FLOAT);
            $terms = filter_var($_POST['payment_terms_days'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 365]]);
            $data = [
                'company_name' => Validator::text($_POST['company_name'] ?? '', 190), 'billing_email' => Validator::email($_POST['billing_email'] ?? ''),
                'billing_phone' => Validator::phone($_POST['billing_phone'] ?? ''), 'billing_address' => Validator::text($_POST['billing_address'] ?? '', 500),
                'tax_id' => Validator::text($_POST['tax_id'] ?? '', 80), 'credit_limit' => $limit === false ? -1 : round((float) $limit, 2),
                'payment_terms_days' => $terms === false ? -1 : (int) $terms, 'currency' => Validator::choice(strtoupper((string) ($_POST['currency'] ?? 'NGN')), ['NGN','USD','GBP','EUR']),
                'account_manager_id' => (int) ($_POST['account_manager_id'] ?? 0),
            ];
            if (mb_strlen($data['company_name']) < 2 || $data['billing_email'] === '' || $data['billing_phone'] === '' || $data['credit_limit'] < 0 || $data['payment_terms_days'] < 0 || $data['currency'] === '') { throw new RuntimeException('Enter valid corporate account and credit terms.'); }
            $accountId = CorporateService::create($data, (int) Auth::id());
            Flash::set('success', 'Corporate account created. Add an existing customer as a member.');
            redirect('staff/corporate.php?id=' . $accountId);

        case 'staff.corporate.member':
            require_post(); require_csrf(); Auth::requireRole(['admin']);
            $accountId = filter_var($_POST['account_id'] ?? null, FILTER_VALIDATE_INT);
            $email = Validator::email($_POST['email'] ?? ''); $role = Validator::choice($_POST['member_role'] ?? '', ['owner','manager','member']);
            if ($accountId === false || $email === '' || $role === '') { throw new RuntimeException('Choose an account, customer email and member role.'); }
            CorporateService::addMemberByEmail((int) $accountId, $email, $role, (int) Auth::id());
            Flash::set('success', 'Corporate member added.'); redirect('staff/corporate.php?id=' . (int) $accountId);

        case 'staff.corporate.payment':
            require_post(); require_csrf(); Auth::requireRole(['admin']);
            $accountId = filter_var($_POST['account_id'] ?? null, FILTER_VALIDATE_INT); $amount = filter_var($_POST['amount'] ?? null, FILTER_VALIDATE_FLOAT);
            if ($accountId === false || $amount === false) { throw new RuntimeException('Enter a valid account payment.'); }
            CorporateService::recordPayment((int) $accountId, (float) $amount, Validator::text($_POST['reference'] ?? '', 50), Validator::text($_POST['description'] ?? '', 255), (int) Auth::id());
            Flash::set('success', 'Corporate payment recorded.'); redirect('staff/corporate.php?id=' . (int) $accountId);

        case 'customer.corporate.charge':
            require_post(); require_csrf(); CustomerAuth::requireCustomer();
            $accountId = filter_var($_POST['account_id'] ?? null, FILTER_VALIDATE_INT); $bookingId = filter_var($_POST['booking_id'] ?? null, FILTER_VALIDATE_INT);
            if ($accountId === false || $bookingId === false) { throw new RuntimeException('Choose a corporate account and booking.'); }
            CorporateService::allocateBookingCredit((int) $accountId, (int) $bookingId, (int) CustomerAuth::id());
            Flash::set('success', 'Booking approved against corporate credit.'); redirect('customer/booking.php?id=' . (int) $bookingId);

        case 'customer.bulk.import':
            require_post(); require_csrf(); CustomerAuth::requireCustomer();
            $accountId = filter_var($_POST['account_id'] ?? null, FILTER_VALIDATE_INT);
            if ($accountId === false) { throw new RuntimeException('Choose a corporate account.'); }
            $batchId = BulkShipmentService::importUpload((int) $accountId, (int) CustomerAuth::id(), $_FILES['bulk_csv'] ?? []);
            Flash::set('success', 'Bulk batch processed. Review any rejected rows before resubmitting them.'); redirect('customer/bulk-batch.php?id=' . $batchId);

        case 'staff.bulk.convert':
            require_post(); require_csrf(); Auth::requireRole(['admin','dispatcher']);
            $batchId = filter_var($_POST['batch_id'] ?? null, FILTER_VALIDATE_INT);
            if ($batchId === false) { throw new RuntimeException('Bulk batch not found.'); }
            $conversion = BulkShipmentService::convertBatch((int) $batchId, (int) Auth::id());
            Flash::set($conversion['failed'] > 0 ? 'warning' : 'success', $conversion['created'] . ' shipments created; ' . $conversion['failed'] . ' failed; ' . $conversion['skipped'] . ' already complete or rejected.');
            redirect('staff/bulk.php?id=' . (int) $batchId);

        case 'staff.cargo.create':
            require_post(); require_csrf(); Auth::requireRole(['admin','dispatcher']);
            $pieces = filter_var($_POST['pieces'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 1000000]]);
            $weight = filter_var($_POST['gross_weight_kg'] ?? null, FILTER_VALIDATE_FLOAT); $volume = filter_var($_POST['volume_cbm'] ?? null, FILTER_VALIDATE_FLOAT);
            $dateValue = static function (string $key): ?string { $v = Validator::text($_POST[$key] ?? '', 20); if ($v === '') { return null; } $d = DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $v); return $d ? $d->format('Y-m-d H:i:s') : null; };
            $data = [
                'shipment_id' => (int) ($_POST['shipment_id'] ?? 0), 'corporate_account_id' => (int) ($_POST['corporate_account_id'] ?? 0),
                'transport_mode' => Validator::choice($_POST['transport_mode'] ?? '', ['air','sea','road']), 'cargo_type' => Validator::text($_POST['cargo_type'] ?? '', 80),
                'incoterm' => Validator::text(strtoupper((string) ($_POST['incoterm'] ?? '')), 10), 'origin_terminal' => Validator::text($_POST['origin_terminal'] ?? '', 190),
                'destination_terminal' => Validator::text($_POST['destination_terminal'] ?? '', 190), 'carrier_name' => Validator::text($_POST['carrier_name'] ?? '', 160),
                'vessel_or_flight' => Validator::text($_POST['vessel_or_flight'] ?? '', 120), 'airway_or_bill_number' => Validator::text($_POST['airway_or_bill_number'] ?? '', 120),
                'container_number' => Validator::text($_POST['container_number'] ?? '', 40), 'pieces' => $pieces === false ? 0 : (int) $pieces,
                'gross_weight_kg' => $weight === false ? 0 : max(0, (float) $weight), 'volume_cbm' => $volume === false ? 0 : max(0, (float) $volume),
                'estimated_departure_at' => $dateValue('estimated_departure_at'), 'estimated_arrival_at' => $dateValue('estimated_arrival_at'),
            ];
            if ($data['transport_mode'] === '' || mb_strlen($data['cargo_type']) < 2 || mb_strlen($data['origin_terminal']) < 2 || mb_strlen($data['destination_terminal']) < 2 || $data['pieces'] < 1) { throw new RuntimeException('Enter the cargo mode, type, route and piece count.'); }
            $cargoId = CargoService::create($data, (int) Auth::id()); Flash::set('success', 'Cargo record created.'); redirect('staff/cargo.php?id=' . $cargoId);

        case 'staff.cargo.milestone':
            require_post(); require_csrf(); Auth::requireRole(['admin','dispatcher']);
            $cargoId = filter_var($_POST['cargo_id'] ?? null, FILTER_VALIDATE_INT); $event = DateTimeImmutable::createFromFormat('Y-m-d\TH:i', Validator::text($_POST['event_time'] ?? '', 20));
            if ($cargoId === false || !$event) { throw new RuntimeException('Cargo record or event time is invalid.'); }
            CargoService::addMilestone((int) $cargoId, [
                'status' => Validator::choice($_POST['status'] ?? '', array_keys(CargoService::statuses())),
                'customs_status' => Validator::choice($_POST['customs_status'] ?? '', array_keys(CargoService::customsStatuses())),
                'title' => Validator::text($_POST['title'] ?? '', 160), 'description' => Validator::text($_POST['description'] ?? '', 1000),
                'location' => Validator::text($_POST['location'] ?? '', 190), 'event_time' => $event->format('Y-m-d H:i:s'), 'is_public' => isset($_POST['is_public']),
            ], (int) Auth::id());
            Flash::set('success', 'Cargo milestone recorded.'); redirect('staff/cargo.php?id=' . (int) $cargoId);

        default:
            http_response_code(404);
            exit('Route not found.');
    }
} catch (Throwable $exception) {
    error_log('Easyway route failure [' . $action . ']: ' . $exception->getMessage());
    Flash::set('danger', $exception instanceof RuntimeException ? $exception->getMessage() : 'We could not complete that request. Please try again.');
    if (str_starts_with($action, 'staff.')) {
        if (Auth::check()) {
            if ($action === 'staff.account.create') { redirect('staff/accounts.php'); }
            if ($action === 'staff.password.change') { redirect('staff/password.php'); }
            if (str_starts_with($action, 'staff.notification_settings.')) {
                redirect('staff/settings.php?channel=' . Validator::choice($_POST['channel'] ?? '', NotificationSettings::CHANNELS, 'email'));
            }
        }
        redirect(Auth::check() ? staff_home_path() : 'staff/login.php');
    }
    if (str_starts_with($action, 'rider.')) { redirect(Auth::check() ? 'rider/index.php' : 'staff/login.php'); }
    if (str_starts_with($action, 'customer.') || str_starts_with($action, 'booking.') || str_starts_with($action, 'payment.')) {
        redirect(CustomerAuth::check() ? 'customer/index.php' : 'customer/login.php');
    }
    redirect('index.php');
}
