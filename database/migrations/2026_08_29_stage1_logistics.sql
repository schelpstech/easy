CREATE TABLE IF NOT EXISTS staff_users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(40) NOT NULL DEFAULT 'dispatcher',
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    last_login_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_staff_users_email (email),
    KEY idx_staff_users_role_status (role, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS contact_messages (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    reference VARCHAR(30) NOT NULL,
    full_name VARCHAR(120) NOT NULL,
    company_name VARCHAR(160) NULL,
    email VARCHAR(190) NOT NULL,
    phone VARCHAR(30) NULL,
    subject VARCHAR(160) NOT NULL,
    message TEXT NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'new',
    assigned_to BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_contact_messages_reference (reference),
    KEY idx_contact_messages_status_created (status, created_at),
    CONSTRAINT fk_contact_messages_assignee FOREIGN KEY (assigned_to) REFERENCES staff_users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS quote_requests (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    reference VARCHAR(30) NOT NULL,
    shipment_type VARCHAR(30) NOT NULL,
    from_location VARCHAR(190) NOT NULL,
    to_location VARCHAR(190) NOT NULL,
    weight_range VARCHAR(40) NOT NULL,
    quantity INT UNSIGNED NOT NULL DEFAULT 1,
    delivery_type VARCHAR(60) NOT NULL,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL,
    phone VARCHAR(30) NOT NULL,
    notes TEXT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'new',
    quoted_amount DECIMAL(14,2) NULL,
    currency CHAR(3) NOT NULL DEFAULT 'NGN',
    assigned_to BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_quote_requests_reference (reference),
    KEY idx_quote_requests_status_created (status, created_at),
    CONSTRAINT fk_quote_requests_assignee FOREIGN KEY (assigned_to) REFERENCES staff_users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS shipments (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    tracking_number VARCHAR(24) NOT NULL,
    customer_name VARCHAR(120) NOT NULL,
    customer_email VARCHAR(190) NULL,
    customer_phone VARCHAR(30) NOT NULL,
    origin VARCHAR(190) NOT NULL,
    destination VARCHAR(190) NOT NULL,
    service_type VARCHAR(60) NOT NULL,
    package_description VARCHAR(500) NOT NULL,
    weight_kg DECIMAL(10,2) NULL,
    status VARCHAR(40) NOT NULL DEFAULT 'booked',
    expected_delivery_at DATETIME NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_shipments_tracking_number (tracking_number),
    KEY idx_shipments_status_updated (status, updated_at),
    KEY idx_shipments_customer_phone (customer_phone),
    CONSTRAINT fk_shipments_created_by FOREIGN KEY (created_by) REFERENCES staff_users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS shipment_events (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    shipment_id BIGINT UNSIGNED NOT NULL,
    status VARCHAR(40) NOT NULL,
    title VARCHAR(160) NOT NULL,
    description TEXT NULL,
    location VARCHAR(190) NULL,
    event_time DATETIME NOT NULL,
    is_public TINYINT(1) NOT NULL DEFAULT 1,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_shipment_events_timeline (shipment_id, event_time, id),
    CONSTRAINT fk_shipment_events_shipment FOREIGN KEY (shipment_id) REFERENCES shipments (id) ON DELETE CASCADE,
    CONSTRAINT fk_shipment_events_created_by FOREIGN KEY (created_by) REFERENCES staff_users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS login_attempts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    email VARCHAR(190) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    was_successful TINYINT(1) NOT NULL DEFAULT 0,
    attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_login_attempts_window (email, ip_address, was_successful, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    staff_user_id BIGINT UNSIGNED NULL,
    action VARCHAR(120) NOT NULL,
    entity_type VARCHAR(80) NULL,
    entity_id BIGINT UNSIGNED NULL,
    context_json JSON NULL,
    ip_address VARCHAR(45) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_audit_logs_entity (entity_type, entity_id, created_at),
    KEY idx_audit_logs_staff (staff_user_id, created_at),
    CONSTRAINT fk_audit_logs_staff FOREIGN KEY (staff_user_id) REFERENCES staff_users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

