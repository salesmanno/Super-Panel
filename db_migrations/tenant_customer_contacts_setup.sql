-- Manual schema prerequisite for the customer-contact migration.
-- Select the application's database before executing.
-- Existing tables and data are not replaced. No application request runs this SQL.
CREATE TABLE IF NOT EXISTS tenant_customer_contacts (
    id CHAR(36) NOT NULL,
    customer_id CHAR(36) NOT NULL,
    contact_type ENUM('email', 'phone') NOT NULL,
    contact_value VARCHAR(255) NOT NULL,
    normalized_value VARCHAR(255) NOT NULL,
    is_primary TINYINT(1) NOT NULL DEFAULT 0,
    verified_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_contacts_customer (customer_id),
    KEY idx_contacts_lookup (contact_type, normalized_value(150)),
    CONSTRAINT fk_contacts_customer
        FOREIGN KEY (customer_id) REFERENCES tenant_customers(id)
        ON DELETE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_general_ci;
