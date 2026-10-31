<?php
/** Shared test schema for suites that resolve customer profile contacts. */
function createCustomerContactFixtureTable(PDO $db): void
{
    $db->exec("CREATE TABLE tenant_customer_contacts (
        id VARCHAR(36) PRIMARY KEY, customer_id VARCHAR(36) NOT NULL,
        contact_type ENUM('email','phone') NOT NULL, contact_value VARCHAR(255) NOT NULL,
        normalized_value VARCHAR(255) NOT NULL, is_primary TINYINT NOT NULL DEFAULT 0,
        verified_at DATETIME NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        KEY idx_customer(customer_id), KEY idx_lookup(contact_type,normalized_value(150))
    ) ENGINE=InnoDB");
}
