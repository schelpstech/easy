CREATE TABLE IF NOT EXISTS rider_profiles (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    staff_user_id BIGINT UNSIGNED NOT NULL,
    rider_code VARCHAR(30) NOT NULL,
    phone VARCHAR(30) NOT NULL,
    vehicle_type VARCHAR(40) NOT NULL,
    vehicle_registration VARCHAR(40) NULL,
    licence_number VARCHAR(80) NULL,
    emergency_contact VARCHAR(120) NULL,
    availability_status VARCHAR(30) NOT NULL DEFAULT 'available',
    location_sharing_enabled TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_rider_profiles_staff (staff_user_id),
    UNIQUE KEY uq_rider_profiles_code (rider_code),
    KEY idx_rider_profiles_availability (availability_status),
    CONSTRAINT fk_rider_profiles_staff FOREIGN KEY (staff_user_id) REFERENCES staff_users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS shipment_assignments (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    shipment_id BIGINT UNSIGNED NOT NULL,
    rider_id BIGINT UNSIGNED NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    assignment_note VARCHAR(500) NULL,
    assigned_by BIGINT UNSIGNED NOT NULL,
    assigned_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME NULL,
    cancelled_at DATETIME NULL,
    PRIMARY KEY (id),
    KEY idx_assignments_shipment_status (shipment_id, status, assigned_at),
    KEY idx_assignments_rider_status (rider_id, status, assigned_at),
    CONSTRAINT fk_assignments_shipment FOREIGN KEY (shipment_id) REFERENCES shipments (id) ON DELETE CASCADE,
    CONSTRAINT fk_assignments_rider FOREIGN KEY (rider_id) REFERENCES rider_profiles (id),
    CONSTRAINT fk_assignments_staff FOREIGN KEY (assigned_by) REFERENCES staff_users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rider_location_pings (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    rider_id BIGINT UNSIGNED NOT NULL,
    shipment_id BIGINT UNSIGNED NOT NULL,
    latitude DECIMAL(10,7) NOT NULL,
    longitude DECIMAL(10,7) NOT NULL,
    accuracy_m DECIMAL(10,2) NULL,
    speed_mps DECIMAL(10,2) NULL,
    heading_degrees DECIMAL(7,2) NULL,
    share_public TINYINT(1) NOT NULL DEFAULT 0,
    recorded_at DATETIME NOT NULL,
    received_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_rider_pings_shipment_time (shipment_id, recorded_at, id),
    KEY idx_rider_pings_rider_time (rider_id, recorded_at, id),
    CONSTRAINT fk_rider_pings_rider FOREIGN KEY (rider_id) REFERENCES rider_profiles (id) ON DELETE CASCADE,
    CONSTRAINT fk_rider_pings_shipment FOREIGN KEY (shipment_id) REFERENCES shipments (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS corporate_accounts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    account_number VARCHAR(30) NOT NULL,
    company_name VARCHAR(190) NOT NULL,
    billing_email VARCHAR(190) NOT NULL,
    billing_phone VARCHAR(30) NOT NULL,
    billing_address VARCHAR(500) NULL,
    tax_id VARCHAR(80) NULL,
    credit_limit DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    payment_terms_days SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    currency CHAR(3) NOT NULL DEFAULT 'NGN',
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    account_manager_id BIGINT UNSIGNED NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_corporate_accounts_number (account_number),
    KEY idx_corporate_accounts_status_name (status, company_name),
    CONSTRAINT fk_corporate_manager FOREIGN KEY (account_manager_id) REFERENCES staff_users (id) ON DELETE SET NULL,
    CONSTRAINT fk_corporate_created_by FOREIGN KEY (created_by) REFERENCES staff_users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS corporate_members (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    corporate_account_id BIGINT UNSIGNED NOT NULL,
    customer_id BIGINT UNSIGNED NOT NULL,
    member_role VARCHAR(20) NOT NULL DEFAULT 'member',
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_corporate_member (corporate_account_id, customer_id),
    KEY idx_corporate_member_customer (customer_id, status),
    CONSTRAINT fk_corporate_members_account FOREIGN KEY (corporate_account_id) REFERENCES corporate_accounts (id) ON DELETE CASCADE,
    CONSTRAINT fk_corporate_members_customer FOREIGN KEY (customer_id) REFERENCES customer_users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS bulk_shipment_batches (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    batch_number VARCHAR(30) NOT NULL,
    corporate_account_id BIGINT UNSIGNED NOT NULL,
    uploaded_by BIGINT UNSIGNED NOT NULL,
    source_filename VARCHAR(190) NOT NULL,
    row_count INT UNSIGNED NOT NULL DEFAULT 0,
    successful_count INT UNSIGNED NOT NULL DEFAULT 0,
    failed_count INT UNSIGNED NOT NULL DEFAULT 0,
    currency CHAR(3) NOT NULL DEFAULT 'NGN',
    total_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    status VARCHAR(30) NOT NULL DEFAULT 'processing',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_bulk_batches_number (batch_number),
    KEY idx_bulk_batches_account_created (corporate_account_id, created_at),
    CONSTRAINT fk_bulk_batches_account FOREIGN KEY (corporate_account_id) REFERENCES corporate_accounts (id),
    CONSTRAINT fk_bulk_batches_customer FOREIGN KEY (uploaded_by) REFERENCES customer_users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS bulk_shipment_items (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    batch_id BIGINT UNSIGNED NOT NULL,
    source_line INT UNSIGNED NOT NULL,
    booking_id BIGINT UNSIGNED NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    error_message VARCHAR(500) NULL,
    amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    source_json JSON NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_bulk_items_row (batch_id, source_line),
    KEY idx_bulk_items_booking (booking_id),
    CONSTRAINT fk_bulk_items_batch FOREIGN KEY (batch_id) REFERENCES bulk_shipment_batches (id) ON DELETE CASCADE,
    CONSTRAINT fk_bulk_items_booking FOREIGN KEY (booking_id) REFERENCES bookings (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS corporate_booking_links (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    corporate_account_id BIGINT UNSIGNED NOT NULL,
    booking_id BIGINT UNSIGNED NOT NULL,
    batch_id BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_corporate_booking (booking_id),
    KEY idx_corporate_links_account_created (corporate_account_id, created_at),
    CONSTRAINT fk_corporate_links_account FOREIGN KEY (corporate_account_id) REFERENCES corporate_accounts (id),
    CONSTRAINT fk_corporate_links_booking FOREIGN KEY (booking_id) REFERENCES bookings (id) ON DELETE CASCADE,
    CONSTRAINT fk_corporate_links_batch FOREIGN KEY (batch_id) REFERENCES bulk_shipment_batches (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS corporate_ledger (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    corporate_account_id BIGINT UNSIGNED NOT NULL,
    booking_id BIGINT UNSIGNED NULL,
    entry_type VARCHAR(20) NOT NULL,
    reference VARCHAR(50) NOT NULL,
    description VARCHAR(255) NOT NULL,
    debit_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    credit_amount DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    currency CHAR(3) NOT NULL DEFAULT 'NGN',
    due_at DATETIME NULL,
    posted_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_corporate_ledger_reference (reference),
    KEY idx_corporate_ledger_account_created (corporate_account_id, created_at),
    KEY idx_corporate_ledger_booking (booking_id),
    CONSTRAINT fk_corporate_ledger_account FOREIGN KEY (corporate_account_id) REFERENCES corporate_accounts (id),
    CONSTRAINT fk_corporate_ledger_booking FOREIGN KEY (booking_id) REFERENCES bookings (id) ON DELETE SET NULL,
    CONSTRAINT fk_corporate_ledger_staff FOREIGN KEY (posted_by) REFERENCES staff_users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cargo_shipments (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    cargo_reference VARCHAR(30) NOT NULL,
    shipment_id BIGINT UNSIGNED NULL,
    corporate_account_id BIGINT UNSIGNED NULL,
    transport_mode VARCHAR(20) NOT NULL,
    cargo_type VARCHAR(80) NOT NULL,
    incoterm VARCHAR(10) NULL,
    origin_terminal VARCHAR(190) NOT NULL,
    destination_terminal VARCHAR(190) NOT NULL,
    carrier_name VARCHAR(160) NULL,
    vessel_or_flight VARCHAR(120) NULL,
    airway_or_bill_number VARCHAR(120) NULL,
    container_number VARCHAR(40) NULL,
    pieces INT UNSIGNED NOT NULL DEFAULT 1,
    gross_weight_kg DECIMAL(12,2) NULL,
    volume_cbm DECIMAL(12,3) NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'booked',
    customs_status VARCHAR(40) NOT NULL DEFAULT 'not_started',
    estimated_departure_at DATETIME NULL,
    estimated_arrival_at DATETIME NULL,
    actual_arrival_at DATETIME NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_cargo_reference (cargo_reference),
    UNIQUE KEY uq_cargo_shipment (shipment_id),
    KEY idx_cargo_status_eta (status, estimated_arrival_at),
    KEY idx_cargo_corporate (corporate_account_id, created_at),
    CONSTRAINT fk_cargo_shipment FOREIGN KEY (shipment_id) REFERENCES shipments (id) ON DELETE SET NULL,
    CONSTRAINT fk_cargo_corporate FOREIGN KEY (corporate_account_id) REFERENCES corporate_accounts (id) ON DELETE SET NULL,
    CONSTRAINT fk_cargo_staff FOREIGN KEY (created_by) REFERENCES staff_users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS cargo_milestones (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    cargo_id BIGINT UNSIGNED NOT NULL,
    status VARCHAR(30) NOT NULL,
    title VARCHAR(160) NOT NULL,
    description VARCHAR(1000) NULL,
    location VARCHAR(190) NULL,
    event_time DATETIME NOT NULL,
    is_public TINYINT(1) NOT NULL DEFAULT 1,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_cargo_milestones_timeline (cargo_id, event_time, id),
    CONSTRAINT fk_cargo_milestones_cargo FOREIGN KEY (cargo_id) REFERENCES cargo_shipments (id) ON DELETE CASCADE,
    CONSTRAINT fk_cargo_milestones_staff FOREIGN KEY (created_by) REFERENCES staff_users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
