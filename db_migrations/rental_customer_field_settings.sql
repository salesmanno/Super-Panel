-- Selectable customer fields per rental object (tenant scoped).
-- Runtime code remains compatible with installations before this migration.
CREATE TABLE IF NOT EXISTS `rental_customer_field_settings` (
  `id` char(36) NOT NULL,
  `company_id` char(36) NOT NULL,
  `rental_object_id` char(36) NOT NULL,
  `field_key` varchar(64) NOT NULL,
  `selected` tinyint(1) NOT NULL DEFAULT 0,
  `required` tinyint(1) NOT NULL DEFAULT 0,
  `display_order` int NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_rental_customer_field` (`company_id`, `rental_object_id`, `field_key`),
  KEY `idx_rental_customer_object` (`company_id`, `rental_object_id`),
  CONSTRAINT `rental_customer_fields_company_fk`
    FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `rental_customer_fields_object_fk`
    FOREIGN KEY (`rental_object_id`) REFERENCES `rental_objects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;