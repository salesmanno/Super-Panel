CREATE TABLE `tenant_companies` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `name` varchar(255) NOT NULL,
  `legal_name` varchar(255) DEFAULT NULL,
  `entity_type` varchar(20) DEFAULT 'company',
  `subdomain` varchar(100) NOT NULL,
  `domain` varchar(255) DEFAULT NULL,
  `industry` varchar(100) DEFAULT NULL,
  `about_us` text DEFAULT NULL,
  `contact_email` varchar(255) DEFAULT NULL,
  `contact_phone` varchar(50) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `logo_url` varchar(500) DEFAULT NULL,
  `address_street` varchar(255) DEFAULT NULL,
  `address_city` varchar(255) DEFAULT NULL,
  `address_state` varchar(255) DEFAULT NULL,
  `address_zip` varchar(50) DEFAULT NULL,
  `address_country` varchar(100) DEFAULT 'Norway',
  `tax_id` varchar(100) DEFAULT NULL,
  `tax_registered` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `trial_ends_at` datetime DEFAULT NULL,
  `onboarded_by` char(36) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `Cash_payment` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `subdomain` (`subdomain`),
  UNIQUE KEY `domain` (`domain`),
  KEY `onboarded_by` (`onboarded_by`),
  KEY `idx_tenant_companies_subdomain` (`subdomain`),
  KEY `idx_tenant_companies_entity_type` (`entity_type`),
  CONSTRAINT `tenant_companies_ibfk_1` FOREIGN KEY (`onboarded_by`) REFERENCES `saas_admins` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
-- ============================================
-- Table: tenant_admins
-- ============================================
  `id` char(36) NOT NULL DEFAULT uuid(),
  `company_id` char(36) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `username` varchar(255) NOT NULL,
  `role` varchar(50) DEFAULT 'admin',
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `mfa_enabled` tinyint(1) DEFAULT 0,
  `mfa_secret` varchar(100) DEFAULT NULL,
  `created_by` char(36) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `tenant_staff_id` char(36) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_id` (`company_id`,`email`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `tenant_admins_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tenant_admins_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `saas_admins` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci

-- ============================================
-- Table: tenant_staff
-- ============================================
  `id` char(36) NOT NULL DEFAULT uuid(),
  `company_id` char(36) NOT NULL,
  `tenant_admin_id` char(36) DEFAULT NULL,
  `employee_code` varchar(100) DEFAULT NULL,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `job_title` varchar(100) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `employment_type` varchar(50) DEFAULT 'full_time',
  `hire_date` date DEFAULT NULL,
  `salary` decimal(12,2) DEFAULT NULL,
  `address_street` varchar(255) DEFAULT NULL,
  `address_city` varchar(255) DEFAULT NULL,
  `address_state` varchar(255) DEFAULT NULL,
  `address_zip` varchar(50) DEFAULT NULL,
  `address_country` varchar(100) DEFAULT NULL,
  `emergency_contact_name` varchar(255) DEFAULT NULL,
  `emergency_contact_phone` varchar(50) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_id` (`company_id`,`email`),
  UNIQUE KEY `company_id_2` (`company_id`,`employee_code`),
  KEY `tenant_admin_id` (`tenant_admin_id`),
  CONSTRAINT `tenant_staff_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tenant_staff_ibfk_2` FOREIGN KEY (`tenant_admin_id`) REFERENCES `tenant_admins` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci

-- ============================================
-- Table: sales_products
-- ============================================
  `id` char(36) NOT NULL DEFAULT uuid(),
  `company_id` char(36) NOT NULL,
  `category_id` char(36) DEFAULT NULL,
  `sku` varchar(100) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `barcode` varchar(100) DEFAULT NULL,
  `cost_price` decimal(15,2) DEFAULT 0.00,
  `selling_price` decimal(15,2) NOT NULL,
  `member_price` decimal(15,2) DEFAULT NULL,
  `currency` varchar(3) DEFAULT 'USD',
  `tax_rate` decimal(5,2) DEFAULT 0.00,
  `unit` varchar(50) DEFAULT 'pcs',
  `reorder_level` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `output_product_id` char(36) DEFAULT NULL,
  `profit_margin` decimal(10,2) GENERATED ALWAYS AS (`selling_price` - ifnull(`cost_price`,0)) STORED,
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_id` (`company_id`,`sku`),
  KEY `category_id` (`category_id`),
  KEY `idx_output_product` (`output_product_id`),
  CONSTRAINT `sales_products_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sales_products_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `sales_categories` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci

-- ============================================
-- Table: sales_transactions
-- ============================================
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` char(36) NOT NULL,
  `sales_product_id` char(36) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `total_price` decimal(15,2) NOT NULL,
  `payment_method` varchar(50) DEFAULT 'cash',
  `customer_name` varchar(255) DEFAULT NULL,
  `customer_email` varchar(255) DEFAULT NULL,
  `send_receipt` tinyint(1) DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_company` (`company_id`),
  KEY `idx_product` (`sales_product_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci

-- ============================================
-- Table: events_eventdata
-- ============================================

-- ============================================
-- Table: membership_plans
-- ============================================

-- ============================================
-- Table: rental_objects
-- ============================================

-- ============================================
-- Table: output_products
-- ============================================

-- ============================================
-- Table: email_templates
-- ============================================

-- ============================================
-- Table: email_logs
-- ============================================

-- ============================================
-- Table: service_bookings
-- ============================================

-- ============================================
-- Table: unified_ledger
-- ============================================

