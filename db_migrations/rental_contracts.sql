-- Migration: Create rental_contracts table
-- Run on: salesmanno04.mysql.domeneshop.no
-- Depends on: rental_objects, tenant_customers, tenant_companies

CREATE TABLE IF NOT EXISTS `rental_contracts` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `company_id` char(36) NOT NULL,
  `rental_object_id` char(36) NOT NULL,
  `customer_id` char(36) DEFAULT NULL,
  `customer_name` varchar(255) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `rental_days` int DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT 0.00,
  `deposit_amount` decimal(10,2) DEFAULT 0.00,
  `deposit_paid` tinyint(1) DEFAULT 0,
  `payment_status` enum('unpaid','partial','paid') DEFAULT 'unpaid',
  `payment_method` varchar(100) DEFAULT NULL,
  `status` enum('draft','active','completed','cancelled') DEFAULT 'draft',
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_company` (`company_id`),
  KEY `idx_object` (`rental_object_id`),
  KEY `idx_customer` (`customer_id`),
  KEY `idx_status` (`status`),
  KEY `idx_dates` (`start_date`, `end_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `rental_contracts`
  ADD CONSTRAINT `rental_contracts_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `rental_contracts_ibfk_2` FOREIGN KEY (`rental_object_id`) REFERENCES `rental_objects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `rental_contracts_ibfk_3` FOREIGN KEY (`customer_id`) REFERENCES `tenant_customers` (`id`) ON DELETE SET NULL;
