-- Exact customer values submitted for a rental request.
-- Values are request-scoped because not every requested field maps to a
-- tenant_customers column (for example age).
CREATE TABLE IF NOT EXISTS `rental_request_field_values` (
  `id` char(36) NOT NULL,
  `company_id` char(36) NOT NULL,
  `rental_object_id` char(36) NOT NULL,
  `rental_request_id` char(36) NOT NULL,
  `field_key` varchar(64) NOT NULL,
  `field_value` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_rental_request_field_value`
    (`company_id`, `rental_request_id`, `field_key`),
  KEY `idx_rental_request_fields_object`
    (`company_id`, `rental_object_id`, `rental_request_id`),
  CONSTRAINT `rental_request_fields_company_fk`
    FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `rental_request_fields_object_fk`
    FOREIGN KEY (`rental_object_id`) REFERENCES `rental_objects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `rental_request_fields_request_fk`
    FOREIGN KEY (`rental_request_id`) REFERENCES `rental_schedule` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;