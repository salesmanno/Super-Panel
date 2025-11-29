/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19  Distrib 10.6.22-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: salesmanno04.mysql.domeneshop.no    Database: salesmanno04
-- ------------------------------------------------------
-- Server version	11.4.7-MariaDB-ubu2004-log

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `activity_summaries`
--

DROP TABLE IF EXISTS `activity_summaries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `activity_summaries` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `company_id` char(36) DEFAULT NULL,
  `user_id` char(36) DEFAULT NULL,
  `user_type` varchar(20) DEFAULT NULL,
  `date_date` date NOT NULL,
  `total_actions` int(11) DEFAULT 0,
  `modules_used` text DEFAULT NULL,
  `first_action` datetime DEFAULT NULL,
  `last_action` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_id` (`company_id`,`user_id`,`date_date`),
  CONSTRAINT `activity_summaries_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_summaries`
--

LOCK TABLES `activity_summaries` WRITE;
set autocommit=0;
UNLOCK TABLES;
commit;

--
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_logs` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `company_id` char(36) DEFAULT NULL,
  `user_id` char(36) DEFAULT NULL,
  `user_type` varchar(20) DEFAULT NULL,
  `user_email` varchar(255) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `resource_type` varchar(100) DEFAULT NULL,
  `resource_id` char(36) DEFAULT NULL,
  `resource_name` varchar(255) DEFAULT NULL,
  `old_values` text DEFAULT NULL,
  `new_values` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `severity` varchar(20) DEFAULT 'info',
  `performed_at` datetime DEFAULT current_timestamp(),
  `module` varchar(100) DEFAULT NULL,
  `session_id` varchar(255) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `device_info` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_audit_logs_company_date` (`company_id`,`performed_at`),
  KEY `idx_audit_logs_user_module` (`user_id`,`module`),
  CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
set autocommit=0;
INSERT INTO `audit_logs` VALUES ('3c330e60-b1da-11f0-b9ad-b02628506e5c','453d3f87-b0e4-11f0-a1bb-b02628506e5c',NULL,NULL,NULL,'COMPANY_PASSWORD_CHANGED','tenant_company','453d3f87-b0e4-11f0-a1bb-b02628506e5c','Nordic Fitness AS','{\"password_changed_at\": \"2025-10-25 11:57:14\"}','{\"password_changed_at\": \"2025-10-25 11:57:14\"}',NULL,NULL,'info','2025-10-25 21:38:51',NULL,NULL,NULL,NULL),('3dbadfe0-b1da-11f0-b9ad-b02628506e5c','453d3f87-b0e4-11f0-a1bb-b02628506e5c',NULL,NULL,NULL,'COMPANY_PASSWORD_CHANGED','tenant_company','453d3f87-b0e4-11f0-a1bb-b02628506e5c','Nordic Fitness AS','{\"password_changed_at\": \"2025-10-25 11:57:14\"}','{\"password_changed_at\": \"2025-10-25 11:57:14\"}',NULL,NULL,'info','2025-10-25 21:38:54',NULL,NULL,NULL,NULL),('3f3a15c0-b1da-11f0-b9ad-b02628506e5c','453d3f87-b0e4-11f0-a1bb-b02628506e5c',NULL,NULL,NULL,'COMPANY_PASSWORD_CHANGED','tenant_company','453d3f87-b0e4-11f0-a1bb-b02628506e5c','Nordic Fitness AS','{\"password_changed_at\": \"2025-10-25 11:57:14\"}','{\"password_changed_at\": \"2025-10-25 11:57:14\"}',NULL,NULL,'info','2025-10-25 21:38:57',NULL,NULL,NULL,NULL),('49e63fae-b1da-11f0-b9ad-b02628506e5c','453d3f87-b0e4-11f0-a1bb-b02628506e5c',NULL,NULL,NULL,'COMPANY_PASSWORD_CHANGED','tenant_company','453d3f87-b0e4-11f0-a1bb-b02628506e5c','Nordic Fitness AS','{\"password_changed_at\": \"2025-10-25 11:57:14\"}','{\"password_changed_at\": \"2025-10-25 11:57:14\"}',NULL,NULL,'info','2025-10-25 21:39:14',NULL,NULL,NULL,NULL);
UNLOCK TABLES;
commit;

--
-- Table structure for table `core_sys_news`
--

DROP TABLE IF EXISTS `core_sys_news`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `core_sys_news` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `news_type` varchar(50) DEFAULT 'info',
  `severity` varchar(20) DEFAULT 'low',
  `is_published` tinyint(1) DEFAULT 0,
  `publish_at` datetime DEFAULT current_timestamp(),
  `expire_at` datetime DEFAULT NULL,
  `target_audience` varchar(50) DEFAULT 'all',
  `created_by` char(36) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `idx_core_sys_news_publish` (`publish_at`,`expire_at`,`is_published`),
  KEY `idx_core_sys_news_audience` (`target_audience`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `core_sys_news`
--

LOCK TABLES `core_sys_news` WRITE;
set autocommit=0;
INSERT INTO `core_sys_news` VALUES ('abf72214-b1ee-11f0-b9ad-b02628506e5c','Hei!','Velkommen til testversjon av bedriftsportalen vår.\r\n\r\nolanordmann\r\npassword','info','low',1,'2025-10-26 00:05:09',NULL,'all',NULL,'2025-10-26 00:05:09','2025-10-26 00:05:09');
UNLOCK TABLES;
commit;

--
-- Table structure for table `core_sys_updates`
--

DROP TABLE IF EXISTS `core_sys_updates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `core_sys_updates` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `version` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `update_type` varchar(50) DEFAULT 'feature',
  `release_date` date NOT NULL,
  `is_published` tinyint(1) DEFAULT 0,
  `documentation_url` varchar(500) DEFAULT NULL,
  `created_by` char(36) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `version` (`version`),
  KEY `created_by` (`created_by`),
  KEY `idx_core_sys_updates_published` (`is_published`,`release_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `core_sys_updates`
--

LOCK TABLES `core_sys_updates` WRITE;
set autocommit=0;
UNLOCK TABLES;
commit;

--
-- Table structure for table `economy_bank_accounts`
--

DROP TABLE IF EXISTS `economy_bank_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `economy_bank_accounts` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `company_id` char(36) NOT NULL,
  `account_name` varchar(255) NOT NULL,
  `account_number` varchar(100) DEFAULT NULL,
  `bank_name` varchar(255) DEFAULT NULL,
  `currency` varchar(3) DEFAULT 'NOK',
  `opening_balance` decimal(15,2) DEFAULT 0.00,
  `current_balance` decimal(15,2) DEFAULT 0.00,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `company_id` (`company_id`),
  CONSTRAINT `economy_bank_accounts_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `economy_bank_accounts`
--

LOCK TABLES `economy_bank_accounts` WRITE;
set autocommit=0;
UNLOCK TABLES;
commit;

--
-- Table structure for table `economy_categories`
--

DROP TABLE IF EXISTS `economy_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `economy_categories` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `company_id` char(36) NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` varchar(20) NOT NULL,
  `parent_category_id` char(36) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `color` varchar(7) DEFAULT '#3366CC',
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_id` (`company_id`,`name`,`type`),
  KEY `parent_category_id` (`parent_category_id`),
  CONSTRAINT `economy_categories_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `economy_categories_ibfk_2` FOREIGN KEY (`parent_category_id`) REFERENCES `economy_categories` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `economy_categories`
--

LOCK TABLES `economy_categories` WRITE;
set autocommit=0;
UNLOCK TABLES;
commit;

--
-- Table structure for table `economy_daily_summary`
--

DROP TABLE IF EXISTS `economy_daily_summary`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `economy_daily_summary` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `company_id` char(36) NOT NULL,
  `summary_date` date NOT NULL,
  `total_income` decimal(15,2) DEFAULT 0.00,
  `total_expenses` decimal(15,2) DEFAULT 0.00,
  `net_amount` decimal(15,2) DEFAULT 0.00,
  `transaction_count` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_id` (`company_id`,`summary_date`),
  CONSTRAINT `economy_daily_summary_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `economy_daily_summary`
--

LOCK TABLES `economy_daily_summary` WRITE;
set autocommit=0;
UNLOCK TABLES;
commit;

--
-- Table structure for table `economy_invoices`
--

DROP TABLE IF EXISTS `economy_invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `economy_invoices` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `company_id` char(36) NOT NULL,
  `customer_id` char(36) DEFAULT NULL,
  `invoice_number` varchar(100) NOT NULL,
  `invoice_date` date NOT NULL,
  `due_date` date NOT NULL,
  `customer_entity_type` varchar(20) DEFAULT 'individual',
  `subtotal` decimal(15,2) NOT NULL,
  `tax_rate` decimal(5,2) DEFAULT 0.00,
  `tax_amount` decimal(15,2) DEFAULT 0.00,
  `total_amount` decimal(15,2) NOT NULL,
  `currency` varchar(3) DEFAULT 'NOK',
  `status` varchar(20) DEFAULT 'draft',
  `payment_terms` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_id` (`company_id`,`invoice_number`),
  KEY `customer_id` (`customer_id`),
  KEY `idx_economy_invoices_customer_type` (`customer_entity_type`),
  CONSTRAINT `economy_invoices_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `economy_invoices_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `tenant_customers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `economy_invoices`
--

LOCK TABLES `economy_invoices` WRITE;
set autocommit=0;
UNLOCK TABLES;
commit;

--
-- Table structure for table `economy_payment_gateways`
--

DROP TABLE IF EXISTS `economy_payment_gateways`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `economy_payment_gateways` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `company_id` char(36) NOT NULL,
  `gateway_name` varchar(50) NOT NULL,
  `display_name` varchar(255) NOT NULL,
  `is_active` tinyint(1) DEFAULT 0,
  `test_mode` tinyint(1) DEFAULT 1,
  `vipps_client_id` varchar(255) DEFAULT NULL,
  `vipps_client_secret_encrypted` text DEFAULT NULL,
  `vipps_merchant_serial_number` varchar(255) DEFAULT NULL,
  `vipps_subscription_key` varchar(255) DEFAULT NULL,
  `paypal_client_id` varchar(255) DEFAULT NULL,
  `paypal_client_secret_encrypted` text DEFAULT NULL,
  `paypal_webhook_id` varchar(255) DEFAULT NULL,
  `stripe_public_key` varchar(255) DEFAULT NULL,
  `stripe_secret_key_encrypted` text DEFAULT NULL,
  `stripe_webhook_secret` varchar(255) DEFAULT NULL,
  `config_data` text DEFAULT NULL,
  `created_by` char(36) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_id` (`company_id`,`gateway_name`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `economy_payment_gateways_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `economy_payment_gateways_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `tenant_admins` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `economy_payment_gateways`
--

LOCK TABLES `economy_payment_gateways` WRITE;
set autocommit=0;
UNLOCK TABLES;
commit;

--
-- Table structure for table `economy_tax_rates`
--

DROP TABLE IF EXISTS `economy_tax_rates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `economy_tax_rates` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `company_id` char(36) NOT NULL,
  `name` varchar(100) NOT NULL,
  `rate` decimal(5,2) NOT NULL,
  `country_code` varchar(2) DEFAULT 'NO',
  `tax_type` varchar(20) DEFAULT 'vat',
  `is_compound` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `applies_to` varchar(20) DEFAULT 'all',
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_id` (`company_id`,`name`),
  CONSTRAINT `economy_tax_rates_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `economy_tax_rates`
--

LOCK TABLES `economy_tax_rates` WRITE;
set autocommit=0;
UNLOCK TABLES;
commit;

--
-- Table structure for table `economy_transactions`
--

DROP TABLE IF EXISTS `economy_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `economy_transactions` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `company_id` char(36) NOT NULL,
  `transaction_date` date NOT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `description` varchar(500) NOT NULL,
  `transaction_type` varchar(50) NOT NULL,
  `status` varchar(20) DEFAULT 'completed',
  `amount` decimal(15,2) NOT NULL,
  `currency` varchar(3) DEFAULT 'NOK',
  `invoice_id` char(36) DEFAULT NULL,
  `sale_order_id` char(36) DEFAULT NULL,
  `customer_id` char(36) DEFAULT NULL,
  `vendor_name` varchar(255) DEFAULT NULL,
  `bank_account_id` char(36) DEFAULT NULL,
  `payment_gateway_id` char(36) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT 'cash',
  `payment_reference` varchar(255) DEFAULT NULL,
  `payment_date` date DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `tags` text DEFAULT NULL,
  `created_by` char(36) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `customer_id` (`customer_id`),
  KEY `bank_account_id` (`bank_account_id`),
  KEY `payment_gateway_id` (`payment_gateway_id`),
  KEY `created_by` (`created_by`),
  KEY `idx_economy_transactions_date` (`company_id`,`transaction_date`),
  KEY `idx_economy_transactions_type` (`transaction_type`),
  CONSTRAINT `economy_transactions_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `economy_transactions_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `tenant_customers` (`id`),
  CONSTRAINT `economy_transactions_ibfk_3` FOREIGN KEY (`bank_account_id`) REFERENCES `economy_bank_accounts` (`id`),
  CONSTRAINT `economy_transactions_ibfk_4` FOREIGN KEY (`payment_gateway_id`) REFERENCES `economy_payment_gateways` (`id`),
  CONSTRAINT `economy_transactions_ibfk_5` FOREIGN KEY (`created_by`) REFERENCES `tenant_admins` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `economy_transactions`
--

LOCK TABLES `economy_transactions` WRITE;
set autocommit=0;
UNLOCK TABLES;
commit;

--
-- Table structure for table `email_accounts`
--

DROP TABLE IF EXISTS `email_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_accounts` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `company_id` char(36) NOT NULL,
  `email_address` varchar(255) NOT NULL,
  `display_name` varchar(255) NOT NULL,
  `imap_host` varchar(255) DEFAULT NULL,
  `imap_port` int(11) DEFAULT 993,
  `imap_username` varchar(255) DEFAULT NULL,
  `imap_password_encrypted` text DEFAULT NULL,
  `imap_ssl` tinyint(1) DEFAULT 1,
  `smtp_host` varchar(255) DEFAULT NULL,
  `smtp_port` int(11) DEFAULT 587,
  `smtp_username` varchar(255) DEFAULT NULL,
  `smtp_password_encrypted` text DEFAULT NULL,
  `smtp_ssl` tinyint(1) DEFAULT 1,
  `signature` text DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `last_sync` datetime DEFAULT NULL,
  `created_by` char(36) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_id` (`company_id`,`email_address`),
  KEY `created_by` (`created_by`),
  KEY `idx_email_accounts_company` (`company_id`),
  CONSTRAINT `email_accounts_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `email_accounts_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `tenant_admins` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `email_accounts`
--

LOCK TABLES `email_accounts` WRITE;
set autocommit=0;
UNLOCK TABLES;
commit;

--
-- Table structure for table `email_attachments`
--

DROP TABLE IF EXISTS `email_attachments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_attachments` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `message_id` char(36) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `content_id` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `message_id` (`message_id`),
  CONSTRAINT `email_attachments_ibfk_1` FOREIGN KEY (`message_id`) REFERENCES `email_messages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `email_attachments`
--

LOCK TABLES `email_attachments` WRITE;
set autocommit=0;
UNLOCK TABLES;
commit;

--
-- Table structure for table `email_configs`
--

DROP TABLE IF EXISTS `email_configs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_configs` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `company_id` char(36) NOT NULL,
  `email` varchar(255) NOT NULL,
  `display_name` varchar(255) NOT NULL,
  `smtp_host` varchar(255) DEFAULT NULL,
  `smtp_port` int(11) DEFAULT NULL,
  `smtp_username` varchar(255) DEFAULT NULL,
  `smtp_password_encrypted` text DEFAULT NULL,
  `signature` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_id` (`company_id`),
  CONSTRAINT `email_configs_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `email_configs`
--

LOCK TABLES `email_configs` WRITE;
set autocommit=0;
UNLOCK TABLES;
commit;

--
-- Table structure for table `email_drafts`
--

DROP TABLE IF EXISTS `email_drafts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_drafts` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `account_id` char(36) NOT NULL,
  `subject` varchar(1000) DEFAULT NULL,
  `body_text` text DEFAULT NULL,
  `body_html` text DEFAULT NULL,
  `to_addresses` text DEFAULT NULL,
  `cc_addresses` text DEFAULT NULL,
  `bcc_addresses` text DEFAULT NULL,
  `attachments` text DEFAULT NULL,
  `created_by` char(36) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `account_id` (`account_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `email_drafts_ibfk_1` FOREIGN KEY (`account_id`) REFERENCES `email_accounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `email_drafts_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `tenant_admins` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `email_drafts`
--

LOCK TABLES `email_drafts` WRITE;
set autocommit=0;
UNLOCK TABLES;
commit;

--
-- Table structure for table `email_folders`
--

DROP TABLE IF EXISTS `email_folders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_folders` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `account_id` char(36) NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` varchar(20) DEFAULT 'custom',
  `parent_folder_id` char(36) DEFAULT NULL,
  `sync_enabled` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `unread_count` int(11) DEFAULT 0,
  `total_count` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `account_id` (`account_id`,`name`),
  KEY `parent_folder_id` (`parent_folder_id`),
  CONSTRAINT `email_folders_ibfk_1` FOREIGN KEY (`account_id`) REFERENCES `email_accounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `email_folders_ibfk_2` FOREIGN KEY (`parent_folder_id`) REFERENCES `email_folders` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `email_folders`
--

LOCK TABLES `email_folders` WRITE;
set autocommit=0;
UNLOCK TABLES;
commit;

--
-- Table structure for table `email_logs`
--

DROP TABLE IF EXISTS `email_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_logs` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `company_id` char(36) NOT NULL,
  `sent_by` char(36) DEFAULT NULL,
  `from_email` varchar(255) NOT NULL,
  `from_name` varchar(255) NOT NULL,
  `to_email` varchar(255) NOT NULL,
  `to_name` varchar(255) DEFAULT NULL,
  `subject` varchar(500) NOT NULL,
  `body_text` text DEFAULT NULL,
  `body_html` text DEFAULT NULL,
  `template_used` varchar(100) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'sent',
  `error_message` text DEFAULT NULL,
  `sent_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `company_id` (`company_id`),
  KEY `sent_by` (`sent_by`),
  CONSTRAINT `email_logs_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `email_logs_ibfk_2` FOREIGN KEY (`sent_by`) REFERENCES `tenant_admins` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `email_logs`
--

LOCK TABLES `email_logs` WRITE;
set autocommit=0;
UNLOCK TABLES;
commit;

--
-- Table structure for table `email_messages`
--

DROP TABLE IF EXISTS `email_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_messages` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `account_id` char(36) NOT NULL,
  `folder_id` char(36) NOT NULL,
  `message_uid` varchar(500) DEFAULT NULL,
  `from_address` varchar(255) NOT NULL,
  `from_name` varchar(255) DEFAULT NULL,
  `to_addresses` text NOT NULL,
  `cc_addresses` text DEFAULT NULL,
  `bcc_addresses` text DEFAULT NULL,
  `subject` varchar(1000) DEFAULT NULL,
  `body_text` text DEFAULT NULL,
  `body_html` text DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `is_important` tinyint(1) DEFAULT 0,
  `is_deleted` tinyint(1) DEFAULT 0,
  `has_attachments` tinyint(1) DEFAULT 0,
  `sent_at` datetime DEFAULT NULL,
  `received_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `account_id` (`account_id`,`message_uid`),
  KEY `folder_id` (`folder_id`),
  KEY `idx_email_messages_account_folder` (`account_id`,`folder_id`),
  CONSTRAINT `email_messages_ibfk_1` FOREIGN KEY (`account_id`) REFERENCES `email_accounts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `email_messages_ibfk_2` FOREIGN KEY (`folder_id`) REFERENCES `email_folders` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `email_messages`
--

LOCK TABLES `email_messages` WRITE;
set autocommit=0;
UNLOCK TABLES;
commit;

--
-- Table structure for table `email_queue`
--

DROP TABLE IF EXISTS `email_queue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_queue` (
  `id` varchar(36) NOT NULL,
  `company_id` int(11) NOT NULL,
  `recipient_email` varchar(255) NOT NULL,
  `from_email` varchar(255) DEFAULT NULL,
  `subject` text NOT NULL,
  `body` text NOT NULL,
  `template_id` varchar(36) DEFAULT NULL,
  `module_source` varchar(50) DEFAULT NULL,
  `source_id` varchar(36) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'queued',
  `priority` int(11) DEFAULT 5,
  `scheduled_at` timestamp NULL DEFAULT current_timestamp(),
  `attempts` int(11) DEFAULT 0,
  `max_attempts` int(11) DEFAULT 3,
  `next_retry_at` timestamp NULL DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_email_queue_status` (`status`),
  KEY `idx_email_queue_company` (`company_id`),
  KEY `idx_email_queue_scheduled` (`scheduled_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `email_queue`
--

LOCK TABLES `email_queue` WRITE;
set autocommit=0;
UNLOCK TABLES;
commit;

--
-- Table structure for table `email_templates`
--

DROP TABLE IF EXISTS `email_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_templates` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `company_id` char(36) NOT NULL,
  `template_key` varchar(100) NOT NULL,
  `name` varchar(255) NOT NULL,
  `subject_template` varchar(500) NOT NULL,
  `body_template` text NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_template_per_company` (`company_id`,`template_key`),
  KEY `idx_template_company` (`company_id`),
  KEY `idx_template_key` (`template_key`),
  CONSTRAINT `email_templates_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `email_templates`
--

LOCK TABLES `email_templates` WRITE;
set autocommit=0;
UNLOCK TABLES;
commit;

--
-- Table structure for table `event_membership_plans`
--

DROP TABLE IF EXISTS `event_membership_plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `event_membership_plans` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `event_id` char(36) NOT NULL,
  `membership_plan_id` char(36) NOT NULL,
  `ticket_price` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `public_display` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_event_membership` (`event_id`,`membership_plan_id`),
  KEY `idx_event_id` (`event_id`),
  KEY `idx_membership_plan_id` (`membership_plan_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `event_membership_plans`
--

LOCK TABLES `event_membership_plans` WRITE;
set autocommit=0;
INSERT INTO `event_membership_plans` VALUES ('02db2b09-c795-11f0-bba6-b02628506e5c','7f71bfba77c3ab8df3cae50a19828367','c756c21b869ae63c37e015f8ac88cea2',0.00,'2025-11-22 11:18:46',0),('02db54bd-c795-11f0-bba6-b02628506e5c','7f71bfba77c3ab8df3cae50a19828367','fe60b4f364430a94a8ab5d60e2fb83ab',400.00,'2025-11-22 11:18:46',1),('15717e73-c58a-11f0-b807-b02628506e5c','249af8383aef5ef76d9a90650ad1223d','c756c21b869ae63c37e015f8ac88cea2',0.00,'2025-11-19 20:55:30',0),('15bd1ad0-c58a-11f0-b807-b02628506e5c','249af8383aef5ef76d9a90650ad1223d','fe60b4f364430a94a8ab5d60e2fb83ab',450.00,'2025-11-19 20:55:30',1),('472a3f15-c734-11f0-a7b4-b02628506e5c','8cb7e96db38209a1ecf708f865686c05','c756c21b869ae63c37e015f8ac88cea2',0.00,'2025-11-21 23:46:19',0),('472aabc6-c734-11f0-a7b4-b02628506e5c','8cb7e96db38209a1ecf708f865686c05','fe60b4f364430a94a8ab5d60e2fb83ab',400.00,'2025-11-21 23:46:19',1),('ac8ded69-c546-11f0-b807-b02628506e5c','ecc015c54165003b1752ef77581ca832','c756c21b869ae63c37e015f8ac88cea2',200.00,'2025-11-19 12:52:58',1),('acd63b1c-c546-11f0-b807-b02628506e5c','ecc015c54165003b1752ef77581ca832','fe60b4f364430a94a8ab5d60e2fb83ab',400.00,'2025-11-19 12:52:58',1),('fe86f256-c5f2-11f0-a3f5-b02628506e5c','7d7c7e6fbc6f79496e3fa273b89aec20','c756c21b869ae63c37e015f8ac88cea2',300.00,'2025-11-20 09:26:29',0),('fe872475-c5f2-11f0-a3f5-b02628506e5c','7d7c7e6fbc6f79496e3fa273b89aec20','fe60b4f364430a94a8ab5d60e2fb83ab',400.00,'2025-11-20 09:26:29',1);
UNLOCK TABLES;
commit;

--
-- Table structure for table `event_registration_fields`
--

DROP TABLE IF EXISTS `event_registration_fields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `event_registration_fields` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `event_id` char(36) NOT NULL,
  `field_name` varchar(50) NOT NULL,
  `field_label` varchar(100) NOT NULL,
  `field_type` varchar(20) NOT NULL DEFAULT 'text',
  `is_required` tinyint(1) DEFAULT 0,
  `is_visible` tinyint(1) DEFAULT 1,
  `display_order` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `event_id` (`event_id`,`field_name`),
  CONSTRAINT `event_registration_fields_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events_eventdata` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `event_registration_fields`
--

LOCK TABLES `event_registration_fields` WRITE;
set autocommit=0;
INSERT INTO `event_registration_fields` VALUES ('01b88718893db757ded1031815744840','d944100bca56b3fc2eaf6e9ed7f1363e','phone','Telefonnummer','tel',0,1,4,'2025-11-07 17:30:24'),('06042529c8691ef9e26d9bd3e78181fc','c4558dd2673bec6448e527eaac19a321','first_name','Fornavn','text',1,1,1,'2025-10-30 11:07:43'),('0648f5c1495fd2c280fec146a6947cf8','cdf48cd049b6e536c14c07baf54a59ef','city','Poststed','text',0,1,7,'2025-10-30 12:17:30'),('0740507ca04cad45a74a1ebab6189e2c','a5a7efae8089d169b64b47f22e34353b','email','E-postadresse','email',1,1,3,'2025-11-07 17:30:25'),('0b0862a0cb21b1fbb66361107471e425','cdf48cd049b6e536c14c07baf54a59ef','zip','Postnummer','text',0,1,6,'2025-10-30 12:17:30'),('0d5e67be9aed26180bd595e3317ecbfe','d944100bca56b3fc2eaf6e9ed7f1363e','last_name','Etternavn','text',1,1,2,'2025-11-07 17:30:24'),('0df575ab69f6e810c1e8e9352d228630','05b6f58ae4428d741059e081e31c94d3','email','E-postadresse','email',1,1,3,'2025-11-07 17:30:12'),('123f963714355e7802fbb4c3d4140f08','cc4305606c6895f399c0a790aaa75506','first_name','Fornavn','text',1,1,1,'2025-11-07 17:30:21'),('160376a733a5ea8ade3f7e75c57ef186','7c9554bacd712364efd7123e267c49a0','last_name','Etternavn','text',1,1,2,'2025-11-05 18:59:38'),('16cace5863140091fc385bbb68dda398','d944100bca56b3fc2eaf6e9ed7f1363e','email','E-postadresse','email',1,1,3,'2025-11-07 17:30:24'),('194586663ffc9411266f9cd159328ebf','9e39a1cec21321397934348723717b9d','phone','Telefonnummer','tel',0,1,4,'2025-10-30 12:13:52'),('1afa60f41af2d4b84413581ffbc58c56','a5a7efae8089d169b64b47f22e34353b','last_name','Etternavn','text',1,1,2,'2025-11-07 17:30:25'),('1b057de4ece279b3ae1e99a89e6a260e','9e39a1cec21321397934348723717b9d','city','Poststed','text',0,1,7,'2025-10-30 12:13:52'),('219dc887653436cea4b1ccec2906b15b','d944100bca56b3fc2eaf6e9ed7f1363e','first_name','Fornavn','text',1,1,1,'2025-11-07 17:30:24'),('2520ff40b4017e343fd05d83cea2aa97','c4558dd2673bec6448e527eaac19a321','street','Gateadresse','text',0,1,5,'2025-10-30 11:07:44'),('27a15a3bced555a399ce14edf7c88c69','cdf48cd049b6e536c14c07baf54a59ef','first_name','Fornavn','text',1,1,1,'2025-10-30 12:17:30'),('2f60c05cc102737205f1a3fd6be5d498','249af8383aef5ef76d9a90650ad1223d','first_name','Fornavn','text',1,1,1,'2025-11-19 21:55:28'),('32d561921ef3f4f141e7c56f9aba8016','7d7c7e6fbc6f79496e3fa273b89aec20','first_name','Fornavn','text',1,1,1,'2025-11-20 10:26:29'),('34f10868483ab50821644eef775dd3db','05b6f58ae4428d741059e081e31c94d3','first_name','Fornavn','text',1,1,1,'2025-11-07 17:30:12'),('393f64fa006fe007d3435efae89336fa','9e39a1cec21321397934348723717b9d','age','Alder','number',0,1,9,'2025-10-30 12:13:52'),('3f5cdb1a32eb0fb0e9de7ce9ca34e9b6','cc4305606c6895f399c0a790aaa75506','email','E-postadresse','email',1,1,3,'2025-11-07 17:30:21'),('499045d519bb12cceb62c951f1388fb0','f4fa9e3496bbe94c94d56ec257bc8d96','last_name','Etternavn','text',1,1,2,'2025-11-07 17:30:25'),('4a0396fa24d7a12ccf2f2268ff47d879','01e5c67cdb554af229977d2ae644a8de','email','E-postadresse','email',1,1,3,'2025-11-07 17:30:08'),('4d75e9a8c3cbb05cfdf46f66958b78e2','c4558dd2673bec6448e527eaac19a321','city','Poststed','text',0,1,7,'2025-10-30 11:07:45'),('4f9a778ec1b7c72466bb832285e09ee4','7c9554bacd712364efd7123e267c49a0','first_name','Fornavn','text',1,1,1,'2025-11-05 18:59:38'),('5076c7f5fe79ba811c982e6f4329e838','c4558dd2673bec6448e527eaac19a321','zip','Postnummer','text',0,1,6,'2025-10-30 11:07:44'),('50b010570f50cc0d4203e753b0acdf38','249af8383aef5ef76d9a90650ad1223d','phone','Telefonnummer','tel',0,1,4,'2025-11-19 21:55:29'),('55e34e00ce194845bd56e07d104c0327','cdf48cd049b6e536c14c07baf54a59ef','postal','Postboks','text',0,1,8,'2025-10-30 12:17:30'),('56750a635108c156473adc62bf25b2cd','9e39a1cec21321397934348723717b9d','street','Gateadresse','text',0,1,5,'2025-10-30 12:13:52'),('58b998be6b6cc8b114c6151d285d80b4','05b6f58ae4428d741059e081e31c94d3','phone','Telefonnummer','tel',0,1,4,'2025-11-07 17:30:12'),('5b3cf2e631f093c49c948e24e5ef6c82','9e39a1cec21321397934348723717b9d','postal','Postboks','text',0,1,8,'2025-10-30 12:13:52'),('5f9abe36fa1cd5f59f6521374d06378b','01e5c67cdb554af229977d2ae644a8de','phone','Telefonnummer','tel',0,1,4,'2025-11-07 17:30:08'),('618cdad39aff9637d7cb818f699d284e','c4558dd2673bec6448e527eaac19a321','email','E-postadresse','email',1,1,3,'2025-10-30 11:07:44'),('66a868672282b22fce12fceaf8b0aaba','0d0492c8d98a6d05a1715af1cd9f791a','first_name','Fornavn','text',1,1,1,'2025-11-03 17:49:06'),('7004ebded9fd065c8362b741bb8efbe8','cdf48cd049b6e536c14c07baf54a59ef','age','Alder','number',0,1,9,'2025-10-30 12:17:30'),('70bbb5b2d3083c5a928f9ed5c7240b72','97a8c174476ea1ac67c1562d4e3a970f','email','E-postadresse','email',1,1,3,'2025-11-09 22:21:24'),('745e1fc715b83abb1e85ec9b796f4b07','7d7c7e6fbc6f79496e3fa273b89aec20','phone','Telefonnummer','tel',0,1,4,'2025-11-20 10:26:29'),('750af7997d9b768e16d0f874260e2dc9','cc4305606c6895f399c0a790aaa75506','last_name','Etternavn','text',1,1,2,'2025-11-07 17:30:21'),('77cb1b76629e6289559a7036561ef02a','cdf48cd049b6e536c14c07baf54a59ef','phone','Telefonnummer','tel',0,1,4,'2025-10-30 12:17:30'),('78b8f62eccba44f678bb44cc380bb007','9e39a1cec21321397934348723717b9d','first_name','Fornavn','text',1,1,1,'2025-10-30 12:13:52'),('7b4bcecf123dc8f85a767b8fb3a4f540','c4558dd2673bec6448e527eaac19a321','last_name','Etternavn','text',1,1,2,'2025-10-30 11:07:43'),('7eb9c7212f6ffbe9e8a442f6a59f3820','ecc015c54165003b1752ef77581ca832','first_name','Fornavn','text',1,1,1,'2025-11-19 13:52:56'),('7f8c5ac6694b5d6f4d47acc5284a44fd','0d0492c8d98a6d05a1715af1cd9f791a','email','E-postadresse','email',1,1,3,'2025-11-03 17:49:06'),('7f9dffd4ff776903a6e2d9bb6a89f7a9','97a8c174476ea1ac67c1562d4e3a970f','first_name','Fornavn','text',1,1,1,'2025-11-09 22:21:24'),('7fd0cedeeb0a13ff612e29945fff9103','c4558dd2673bec6448e527eaac19a321','phone','Telefonnummer','tel',0,1,4,'2025-10-30 11:07:44'),('84aedb090f89b1f882cc09e7d93f467b','249af8383aef5ef76d9a90650ad1223d','last_name','Etternavn','text',1,1,2,'2025-11-19 21:55:29'),('84e2c299443681daf84e1a2538c8314c','cc4305606c6895f399c0a790aaa75506','phone','Telefonnummer','tel',0,1,4,'2025-11-07 17:30:21'),('8dfe786378503a5e9076e12d411d360e','01e5c67cdb554af229977d2ae644a8de','last_name','Etternavn','text',1,1,2,'2025-11-07 17:30:08'),('9e812372473056cfef4143df6a9b8caa','b8ddef108a02645587f1b8c3a2d15a80','first_name','Fornavn','text',1,1,1,'2025-11-02 16:57:00'),('a193d14f0e5e3c4bc2c85daca548325b','ecc015c54165003b1752ef77581ca832','phone','Telefonnummer','tel',0,1,3,'2025-11-19 13:52:57'),('a22e6f0d859b53b31b0eafeca9cb7f48','cdf48cd049b6e536c14c07baf54a59ef','email','E-postadresse','email',1,1,3,'2025-10-30 12:17:30'),('a54f7a375a678539c6c389633d57f9e1','7d7c7e6fbc6f79496e3fa273b89aec20','email','E-postadresse','email',0,1,3,'2025-11-20 10:26:29'),('a6937b8bb2517e04f9fe53d53468fe00','f4fa9e3496bbe94c94d56ec257bc8d96','email','E-postadresse','email',1,1,3,'2025-11-07 17:30:25'),('a7d36b99ddb5a79b0908a19fb801bf6e','9e39a1cec21321397934348723717b9d','last_name','Etternavn','text',1,1,2,'2025-10-30 12:13:52'),('aed01987ba79a82d47ff96350b456d15','97a8c174476ea1ac67c1562d4e3a970f','postal','Postboks','text',0,1,5,'2025-11-09 22:21:24'),('b402566e6417b980004b4999622de29a','01e5c67cdb554af229977d2ae644a8de','first_name','Fornavn','text',1,1,1,'2025-11-07 17:30:08'),('b7027580089abfdb8b8867c80dbe701d','cdf48cd049b6e536c14c07baf54a59ef','street','Gateadresse','text',0,1,5,'2025-10-30 12:17:30'),('c0506f541951f00cf5813e05a61e42f5','7d7c7e6fbc6f79496e3fa273b89aec20','last_name','Etternavn','text',1,1,2,'2025-11-20 10:26:29'),('c170df7987b6d38cea2428c21e60903a','b8ddef108a02645587f1b8c3a2d15a80','last_name','Etternavn','text',1,1,2,'2025-11-02 16:57:00'),('c213926f38de19a2f7f5b89b21b99164','9e39a1cec21321397934348723717b9d','email','E-postadresse','email',1,1,3,'2025-10-30 12:13:52'),('c2aa8a4a5ecd9682d40533bf5b0cb86f','7c9554bacd712364efd7123e267c49a0','email','E-postadresse','email',1,1,3,'2025-11-05 18:59:38'),('c895541a35d2c28e46790cffa8baaab4','b8ddef108a02645587f1b8c3a2d15a80','phone','Telefonnummer','tel',0,1,4,'2025-11-02 16:57:00'),('cbc6d1d4d6cbbf9823444678d62d4fb8','c4558dd2673bec6448e527eaac19a321','postal','Postboks','text',0,1,8,'2025-10-30 11:07:45'),('d1f900c5c04dc0f90a0d6f225fe66d30','97a8c174476ea1ac67c1562d4e3a970f','last_name','Etternavn','text',1,1,2,'2025-11-09 22:21:24'),('d4cbd2e814b5d11960792f6b0e0c1043','cdf48cd049b6e536c14c07baf54a59ef','last_name','Etternavn','text',1,1,2,'2025-10-30 12:17:30'),('d51d0f09872f29f6b90b342387f19d7f','f4fa9e3496bbe94c94d56ec257bc8d96','first_name','Fornavn','text',1,1,1,'2025-11-07 17:30:25'),('dcd46fe03378d2d9e373749f12c768a8','97a8c174476ea1ac67c1562d4e3a970f','city','Poststed','text',0,1,4,'2025-11-09 22:21:24'),('dfc98bd065def48d17d0d0481c7c0cd2','f4fa9e3496bbe94c94d56ec257bc8d96','phone','Telefonnummer','tel',0,1,4,'2025-11-07 17:30:25'),('e2f68a0b9252cee5713645e7f91c070c','0d0492c8d98a6d05a1715af1cd9f791a','last_name','Etternavn','text',1,1,2,'2025-11-03 17:49:06'),('e39fa0219b5ea67d806f34ab3f2fdb82','a5a7efae8089d169b64b47f22e34353b','phone','Telefonnummer','tel',0,1,4,'2025-11-07 17:30:25'),('e699c7b0e8a7f23dc0c8e6b97ac52aa3','b8ddef108a02645587f1b8c3a2d15a80','email','E-postadresse','email',1,1,3,'2025-11-02 16:57:00'),('e713aa0bc389ef1847060da2b2ca7b46','9e39a1cec21321397934348723717b9d','zip','Postnummer','text',0,1,6,'2025-10-30 12:13:52'),('e859b40baa43e0eb25f74f928cbf6653','a5a7efae8089d169b64b47f22e34353b','first_name','Fornavn','text',1,1,1,'2025-11-07 17:30:25'),('e8c63a999b15f5ce35c7410d0efebad5','ecc015c54165003b1752ef77581ca832','last_name','Etternavn','text',1,1,2,'2025-11-19 13:52:57'),('e8fed2d8fdb14d437367f2c6b3176f34','05b6f58ae4428d741059e081e31c94d3','last_name','Etternavn','text',1,1,2,'2025-11-07 17:30:12'),('ebf518f558505f1d9cc6eeb47faecc17','249af8383aef5ef76d9a90650ad1223d','email','E-postadresse','email',0,1,3,'2025-11-19 21:55:29'),('f1174525a12ed83937d608c65a0be839','0d0492c8d98a6d05a1715af1cd9f791a','phone','Telefonnummer','tel',0,1,4,'2025-11-03 17:49:06'),('fcd2d97092d55f145aea1d976403e6a6','b8ddef108a02645587f1b8c3a2d15a80','age','Alder','number',0,1,5,'2025-11-02 16:57:00');
UNLOCK TABLES;
commit;

--
-- Table structure for table `event_registrations`
--

DROP TABLE IF EXISTS `event_registrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `event_registrations` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `event_id` char(36) NOT NULL,
  `company_id` char(36) NOT NULL,
  `customer_id` char(36) NOT NULL,
  `approval_status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `approval_reason` text DEFAULT NULL,
  `approved_by` char(36) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `rejected_by` char(36) DEFAULT NULL,
  `rejected_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_event_customer` (`event_id`,`customer_id`),
  KEY `fk_event_reg_company` (`company_id`),
  KEY `fk_event_reg_customer` (`customer_id`),
  CONSTRAINT `fk_event_reg_company` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_event_reg_customer` FOREIGN KEY (`customer_id`) REFERENCES `tenant_customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_event_reg_event` FOREIGN KEY (`event_id`) REFERENCES `events_eventdata` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `event_registrations`
--

LOCK TABLES `event_registrations` WRITE;
set autocommit=0;
UNLOCK TABLES;
commit;

--
-- Table structure for table `events_checkin`
--

DROP TABLE IF EXISTS `events_checkin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `events_checkin` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `event_id` char(36) NOT NULL,
  `customer_id` char(36) DEFAULT NULL,
  `attendee_name` varchar(255) NOT NULL,
  `attendee_email` varchar(255) DEFAULT NULL,
  `attendee_phone` varchar(50) DEFAULT NULL,
  `ticket_type` varchar(50) DEFAULT 'standard',
  `ticket_price` decimal(10,2) NOT NULL,
  `payment_status` varchar(20) DEFAULT 'paid',
  `payment_method` varchar(50) DEFAULT 'cash',
  `checked_in` tinyint(1) DEFAULT 0,
  `check_in_time` datetime DEFAULT NULL,
  `checked_in_by` char(36) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `attendee_zip` varchar(20) DEFAULT NULL,
  `attendee_street` varchar(255) DEFAULT NULL,
  `attendee_city` varchar(100) DEFAULT NULL,
  `attendee_postal` varchar(20) DEFAULT NULL,
  `barcode` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_barcode_event` (`event_id`,`barcode`),
  KEY `idx_event` (`event_id`),
  KEY `idx_customer` (`customer_id`),
  KEY `idx_payment` (`payment_status`),
  KEY `idx_checkin` (`checked_in`,`check_in_time`),
  KEY `checked_in_by` (`checked_in_by`),
  KEY `idx_checkin_barcode` (`barcode`),
  CONSTRAINT `events_checkin_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events_eventdata` (`id`) ON DELETE CASCADE,
  CONSTRAINT `events_checkin_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `tenant_customers` (`id`),
  CONSTRAINT `events_checkin_ibfk_3` FOREIGN KEY (`checked_in_by`) REFERENCES `tenant_admins` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `events_checkin`
--

LOCK TABLES `events_checkin` WRITE;
set autocommit=0;
INSERT INTO `events_checkin` VALUES ('557ae857-2922-4408-b521-0add9d8ef82a','7f71bfba77c3ab8df3cae50a19828367','9a006b0bc0e211f08af7b02628506e5c','Atle Karl Kvalsvik','atlekarl@tussa.com','95918925','online',0.00,'pending',NULL,0,NULL,NULL,NULL,'2025-11-22 12:27:59','2025-11-22 12:27:59',NULL,NULL,NULL,NULL,NULL);
UNLOCK TABLES;
commit;

--
-- Table structure for table `events_eventdata`
--

DROP TABLE IF EXISTS `events_eventdata`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `events_eventdata` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `company_id` char(36) NOT NULL,
  `output_product_id` char(36) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `start_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_date` date DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `venue_name` varchar(255) DEFAULT NULL,
  `event_address` varchar(255) DEFAULT NULL,
  `event_city` varchar(255) DEFAULT NULL,
  `event_postal` varchar(50) DEFAULT NULL,
  `max_attendees` int(11) DEFAULT NULL,
  `registration_deadline` datetime DEFAULT NULL,
  `min_attendees` int(11) DEFAULT NULL,
  `age_limit` int(11) DEFAULT NULL,
  `is_member_exclusive` tinyint(1) DEFAULT 0,
  `ticket_price` decimal(10,2) DEFAULT 0.00,
  `member_ticket_price` decimal(10,2) DEFAULT NULL,
  `cost_price` decimal(10,2) DEFAULT 0.00,
  `event_image_url` varchar(500) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` char(36) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `default_payment_method` varchar(50) DEFAULT NULL,
  `payment_timing` enum('on_arrival','immediate','both') DEFAULT 'both',
  `registration_type` varchar(255) DEFAULT NULL,
  `payment_methods_json` text DEFAULT NULL COMMENT 'JSON array of allowed payment methods (cash, vipps, card, etc)',
  PRIMARY KEY (`id`),
  KEY `idx_company` (`company_id`),
  KEY `idx_output` (`output_product_id`),
  KEY `idx_date` (`start_date`,`end_date`),
  KEY `idx_active` (`is_active`),
  CONSTRAINT `events_eventdata_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `events_eventdata_ibfk_2` FOREIGN KEY (`output_product_id`) REFERENCES `output_products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `events_eventdata`
--

LOCK TABLES `events_eventdata` WRITE;
set autocommit=0;
INSERT INTO `events_eventdata` VALUES ('249af8383aef5ef76d9a90650ad1223d','7efbddea-b507-11f0-936b-b02628506e5c',NULL,'test','','2025-12-12','20:00:00','2025-12-12','22:00:00','Høgtun','Nesevegen 5','Ulsteinvik','6065',100,NULL,NULL,NULL,0,500.00,NULL,0.00,NULL,0,'7f0d55c4-b507-11f0-936b-b02628506e5c','2025-11-19 21:55:28','2025-11-20 11:55:04','cash','','signup','[\"cash\"]'),('3a2745d2bedaaa79f052826c2a3cf103','7efbddea-b507-11f0-936b-b02628506e5c',NULL,'Ooo','Lol','2025-12-19','23:40:00','2025-12-20','23:49:00','Heime','Vågå','Andeby','6969',NULL,NULL,NULL,NULL,0,0.00,NULL,0.00,NULL,0,'7f072d6a-b507-11f0-936b-b02628506e5c','2025-11-19 23:41:50','2025-11-20 11:55:12','cash','','none','[\"cash\"]'),('63535e7e926e77c74989c93c1efb4eab','7efbddea-b507-11f0-936b-b02628506e5c',NULL,'Julebord','','2025-12-12','20:00:00','2025-12-12','23:00:00','Høgtun','Vågsgata 61','Herøy','6098',100,'2025-12-10 00:00:00',20,0,1,0.00,NULL,0.00,NULL,0,'7f0d55c4-b507-11f0-936b-b02628506e5c','2025-11-21 23:39:46','2025-11-22 00:48:19','cash','','signup',NULL),('78519c1d48607be776164300edd1a474','7efbddea-b507-11f0-936b-b02628506e5c',NULL,'Julebingo','Jule','2025-11-27','12:48:00','2025-11-29','12:48:00','Høgtun Forsamlingshus','Vågsgata 61','Fosnavåg','6090',NULL,NULL,NULL,NULL,0,0.00,NULL,0.00,'https://tbib.dk/sites/default/files/styles/hero_wide/public/2025-07/12%20-%2001.12.2025%20Nissebingo.png?h=d4970467&itok=DP6w2Mok',1,'f996adb8d578c9f15859cc9ffb78b023792b','2025-11-22 12:49:33','2025-11-22 12:49:33','cash','','none',NULL),('7d7c7e6fbc6f79496e3fa273b89aec20','7efbddea-b507-11f0-936b-b02628506e5c',NULL,'Test','eee','2025-12-12','20:09:00','2025-12-12','20:20:00','heime','andeby','Helseby','606060',100,'2025-12-10 00:00:00',50,0,0,500.00,NULL,0.00,NULL,0,'7f0d55c4-b507-11f0-936b-b02628506e5c','2025-11-20 10:26:29','2025-11-20 11:55:01','cash','','signup',NULL),('7f71bfba77c3ab8df3cae50a19828367','7efbddea-b507-11f0-936b-b02628506e5c',NULL,'Julebord','Gøy på','2025-11-27','12:16:00','2025-11-28','12:16:00','Høgtun Forsamlingshus','Vågsgata 61','Herøy','6090',NULL,NULL,NULL,NULL,1,0.00,NULL,0.00,'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSCKmPqo7jyvml5ZOr32y9ZBa9KsMANGz9SFltU-PXLRiNIFHjWOFc-5tFUt8SMEFBqNHihyD2_KGdX0cbU_XXbVdD05AN0QDPjuQP9Nr_L&s=10',1,'f996adb8d578c9f15859cc9ffb78b023792b','2025-11-22 12:18:46','2025-11-22 12:18:46','cash','','signup',NULL),('8ae4ea0ffe48b187b9f67ddef84aabc2','7efbddea-b507-11f0-936b-b02628506e5c',NULL,'Julebingo','Julebingo for alle sammen!','2025-12-12','20:00:00','2025-12-12','22:00:00','Høgtun','vågsgata 61','Fosnavåg','6090',NULL,NULL,NULL,NULL,0,0.00,NULL,0.00,'https://play-lh.googleusercontent.com/AGR1f-ipIP44j8vdb7BZwkHUGFkEYAh9AE-ncEyFniYlY0153n_IFoCJ4SN4K5oHrw',0,'7f0d55c4-b507-11f0-936b-b02628506e5c','2025-11-19 13:38:37','2025-11-20 12:39:22','cash','','none',NULL),('8cb7e96db38209a1ecf708f865686c05','7efbddea-b507-11f0-936b-b02628506e5c',NULL,'Julebord','Kom på julebord','2025-12-12','20:00:00','2025-12-12','23:59:00','Høgtun','Vågsgata 61','Herøy','6098',100,NULL,NULL,0,1,0.00,NULL,0.00,'https://www.aktivioslo.no/wp-content/uploads/2011/08/julebord-2.jpg',1,'f996adb8d578c9f15859cc9ffb78b023792b','2025-11-22 00:46:19','2025-11-22 00:46:19','cash','','signup',NULL),('c39b93839693a60a1c0ad92687f34c33','7efbddea-b507-11f0-936b-b02628506e5c',NULL,'Test','Hehe','2025-11-22','12:39:00','2025-11-23','12:39:00','Hoi','Nesevegen 5','Ulsteinvik','6065',NULL,NULL,NULL,NULL,0,0.00,NULL,0.00,NULL,1,'7f0d55c4-b507-11f0-936b-b02628506e5c','2025-11-20 12:40:11','2025-11-20 12:40:11','cash','','none',NULL),('ecc015c54165003b1752ef77581ca832','7efbddea-b507-11f0-936b-b02628506e5c',NULL,'test','test','2025-12-14','20:00:00','2025-12-14','22:00:00','herøy kulturhus','nesevegen 5','ulsteinvik','6065',100,NULL,NULL,NULL,1,0.00,NULL,0.00,'https://www.lagerhaus.no/pub_docs/files/Juldukning-wheat-ribbed-ro%CC%88d-25-lagerhaus_7jpg-(1).jpg',0,'7f0d55c4-b507-11f0-936b-b02628506e5c','2025-11-19 13:52:56','2025-11-20 12:39:17','cash','','signup',NULL);
UNLOCK TABLES;
commit;

--
-- Table structure for table `events_results`
--

DROP TABLE IF EXISTS `events_results`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `events_results` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `event_id` char(36) NOT NULL,
  `company_id` char(36) NOT NULL,
  `date_start` datetime NOT NULL,
  `date_end` datetime NOT NULL,
  `ticketprice_member` decimal(10,2) DEFAULT 0.00,
  `ticketprice_normal` decimal(10,2) DEFAULT 0.00,
  `member_amount` decimal(10,2) DEFAULT 0.00,
  `guest_amount` decimal(10,2) DEFAULT 0.00,
  `total_ticket_sales` decimal(10,2) DEFAULT 0.00,
  `food_sales` decimal(10,2) DEFAULT 0.00,
  `drink_sales` decimal(10,2) DEFAULT 0.00,
  `other_sales` decimal(10,2) DEFAULT 0.00,
  `total_food_drink_sales` decimal(10,2) DEFAULT 0.00,
  `total_revenue` decimal(10,2) DEFAULT 0.00,
  `purchase_cost` decimal(10,2) DEFAULT 0.00,
  `food_drink_cost` decimal(10,2) DEFAULT 0.00,
  `total_costs` decimal(10,2) DEFAULT 0.00,
  `profit_loss` decimal(10,2) DEFAULT 0.00,
  `event_cancelled` tinyint(1) DEFAULT 0,
  `guest_checkins` int(11) DEFAULT 0,
  `member_checkins` int(11) DEFAULT 0,
  `total_checkins` int(11) DEFAULT 0,
  `amount_checkins` decimal(10,2) DEFAULT 0.00,
  `all_averageage` int(11) DEFAULT 0,
  `food_units_sold` int(11) DEFAULT 0,
  `drink_units_sold` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_event_result` (`event_id`),
  KEY `idx_events_results_company` (`company_id`),
  KEY `idx_events_results_dates` (`date_start`,`date_end`),
  CONSTRAINT `fk_events_results_company` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_events_results_event` FOREIGN KEY (`event_id`) REFERENCES `events_eventdata` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `events_results`
--

LOCK TABLES `events_results` WRITE;
set autocommit=0;
UNLOCK TABLES;
commit;

--
-- Table structure for table `market_marketplace`
--

DROP TABLE IF EXISTS `market_marketplace`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `market_marketplace` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `company_id` char(36) NOT NULL,
  `item_type` varchar(20) NOT NULL,
  `source_table` varchar(100) NOT NULL,
  `source_id` char(36) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `tags` text DEFAULT NULL,
  `image_urls` text DEFAULT NULL,
  `video_url` varchar(500) DEFAULT NULL,
  `price` decimal(15,2) DEFAULT NULL,
  `currency` varchar(3) DEFAULT 'NOK',
  `compare_at_price` decimal(15,2) DEFAULT NULL,
  `is_negotiable` tinyint(1) DEFAULT 0,
  `stock_quantity` int(11) DEFAULT 0,
  `min_quantity` int(11) DEFAULT 1,
  `max_quantity` int(11) DEFAULT 10,
  `location_address` varchar(255) DEFAULT NULL,
  `location_city` varchar(255) DEFAULT NULL,
  `location_state` varchar(255) DEFAULT NULL,
  `location_zip` varchar(50) DEFAULT NULL,
  `location_country` varchar(100) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'draft',
  `is_featured` tinyint(1) DEFAULT 0,
  `is_verified` tinyint(1) DEFAULT 0,
  `seo_title` varchar(255) DEFAULT NULL,
  `seo_description` text DEFAULT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `view_count` int(11) DEFAULT 0,
  `published_at` datetime DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `created_by` char(36) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_id` (`company_id`,`source_table`,`source_id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `created_by` (`created_by`),
  KEY `idx_marketplace_status` (`company_id`,`status`),
  KEY `idx_marketplace_category` (`company_id`,`category`),
  KEY `idx_marketplace_location` (`company_id`,`location_city`,`location_state`),
  KEY `idx_marketplace_type` (`item_type`,`source_table`),
  KEY `idx_marketplace_slug` (`slug`),
  CONSTRAINT `market_marketplace_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `market_marketplace_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `tenant_admins` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `market_marketplace`
--

LOCK TABLES `market_marketplace` WRITE;
set autocommit=0;
UNLOCK TABLES;
commit;

--
-- Table structure for table `marketplace_categories`
--

DROP TABLE IF EXISTS `marketplace_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `marketplace_categories` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `company_id` char(36) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `parent_category_id` char(36) DEFAULT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_id` (`company_id`,`name`),
  KEY `parent_category_id` (`parent_category_id`),
  CONSTRAINT `marketplace_categories_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `marketplace_categories_ibfk_2` FOREIGN KEY (`parent_category_id`) REFERENCES `marketplace_categories` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `marketplace_categories`
--

LOCK TABLES `marketplace_categories` WRITE;
set autocommit=0;
UNLOCK TABLES;
commit;

--
-- Table structure for table `marketplace_favorites`
--

DROP TABLE IF EXISTS `marketplace_favorites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `marketplace_favorites` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `marketplace_item_id` char(36) NOT NULL,
  `customer_id` char(36) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `marketplace_item_id` (`marketplace_item_id`,`customer_id`),
  KEY `customer_id` (`customer_id`),
  CONSTRAINT `marketplace_favorites_ibfk_1` FOREIGN KEY (`marketplace_item_id`) REFERENCES `market_marketplace` (`id`) ON DELETE CASCADE,
  CONSTRAINT `marketplace_favorites_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `tenant_customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `marketplace_favorites`
--

LOCK TABLES `marketplace_favorites` WRITE;
set autocommit=0;
UNLOCK TABLES;
commit;

--
-- Table structure for table `marketplace_inquiries`
--

DROP TABLE IF EXISTS `marketplace_inquiries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `marketplace_inquiries` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `marketplace_item_id` char(36) NOT NULL,
  `customer_id` char(36) DEFAULT NULL,
  `customer_name` varchar(255) NOT NULL,
  `customer_email` varchar(255) NOT NULL,
  `customer_phone` varchar(50) DEFAULT NULL,
  `message` text NOT NULL,
  `status` varchar(20) DEFAULT 'new',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `marketplace_item_id` (`marketplace_item_id`),
  KEY `customer_id` (`customer_id`),
  CONSTRAINT `marketplace_inquiries_ibfk_1` FOREIGN KEY (`marketplace_item_id`) REFERENCES `market_marketplace` (`id`) ON DELETE CASCADE,
  CONSTRAINT `marketplace_inquiries_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `tenant_customers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `marketplace_inquiries`
--

LOCK TABLES `marketplace_inquiries` WRITE;
set autocommit=0;
UNLOCK TABLES;
commit;

--
-- Table structure for table `membership_members`
--

DROP TABLE IF EXISTS `membership_members`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `membership_members` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `company_id` char(36) NOT NULL,
  `customer_id` char(36) NOT NULL,
  `membership_plan_id` char(36) DEFAULT NULL,
  `member_code` varchar(100) NOT NULL,
  `status` varchar(50) DEFAULT 'active',
  `join_date` date NOT NULL,
  `expiration_date` date DEFAULT NULL,
  `auto_renew` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_id` (`company_id`,`member_code`),
  UNIQUE KEY `company_id_2` (`company_id`,`customer_id`),
  KEY `customer_id` (`customer_id`),
  KEY `membership_plan_id` (`membership_plan_id`),
  CONSTRAINT `membership_members_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `membership_members_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `tenant_customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `membership_members_ibfk_3` FOREIGN KEY (`membership_plan_id`) REFERENCES `membership_plans` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `membership_members`
--

LOCK TABLES `membership_members` WRITE;
set autocommit=0;
INSERT INTO `membership_members` VALUES ('99ff943bc0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','99ff86c4c0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-99FF943B','active','2025-07-21','2026-07-21',1,'2025-11-13 23:46:32'),('9a001b3fc0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a00105dc0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A001B3F','active','2025-07-21','2026-07-21',1,'2025-11-13 23:46:32'),('9a007840c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a006b0bc0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A007840','active','2025-07-21','2026-07-21',1,'2025-11-13 23:46:32'),('9a00cff7c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a00c1c0c0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A00CFF7','active','2025-07-21','2026-07-21',1,'2025-11-13 23:46:32'),('9a012b8ec0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a011f2ec0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A012B8E','active','2025-07-21','2026-07-21',1,'2025-11-13 23:46:32'),('9a0189e3c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a017d83c0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A0189E3','active','2025-07-21','2026-07-21',1,'2025-11-13 23:46:32'),('9a01e2ecc0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a01d78dc0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A01E2EC','active','2025-07-21','2026-07-21',1,'2025-11-13 23:46:32'),('9a023fe9c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a0232bdc0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A023FE9','active','2025-07-23','2026-07-23',1,'2025-11-13 23:46:32'),('9a029fd1c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a0293c1c0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A029FD1','active','2025-07-23','2026-07-23',1,'2025-11-13 23:46:32'),('9a03006ac0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a02f472c0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A03006A','active','2025-07-23','2026-07-23',1,'2025-11-13 23:46:32'),('9a038e05c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a03735ac0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A038E05','active','2025-07-23','2026-07-23',1,'2025-11-13 23:46:32'),('9a041b1fc0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a040014c0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A041B1F','active','2025-07-23','2026-07-23',1,'2025-11-13 23:46:32'),('9a04a2b1c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a048c74c0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A04A2B1','active','2025-07-23','2026-07-23',1,'2025-11-13 23:46:32'),('9a05318ec0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a051722c0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A05318E','active','2025-07-23','2026-07-23',1,'2025-11-13 23:46:32'),('9a05c935c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a05addcc0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A05C935','active','2025-07-23','2026-07-23',1,'2025-11-13 23:46:32'),('9a066abfc0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a0653e5c0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A066ABF','active','2025-07-23','2026-07-23',1,'2025-11-13 23:46:32'),('9a06fad6c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a06e5c8c0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A06FAD6','active','2025-07-23','2026-07-23',1,'2025-11-13 23:46:32'),('9a0793cbc0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a077e16c0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A0793CB','active','2025-07-23','2026-07-23',1,'2025-11-13 23:46:32'),('9a0830dcc0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a0813e5c0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A0830DC','active','2025-07-23','2026-07-23',1,'2025-11-13 23:46:32'),('9a08da6bc0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a08bd3ac0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A08DA6B','active','2025-07-23','2026-07-23',1,'2025-11-13 23:46:32'),('9a097051c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a0958c6c0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A097051','active','2025-07-23','2026-07-23',1,'2025-11-13 23:46:32'),('9a09fc16c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a09e5f7c0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A09FC16','active','2025-07-23','2026-07-23',1,'2025-11-13 23:46:32'),('9a0a99edc0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a0a805ac0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A0A99ED','active','2025-07-23','2026-07-23',1,'2025-11-13 23:46:32'),('9a0b2923c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a0b115bc0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A0B2923','active','2025-07-23','2026-07-23',1,'2025-11-13 23:46:32'),('9a0bc261c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a0ba712c0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A0BC261','active','2025-07-23','2026-07-23',1,'2025-11-13 23:46:32'),('9a0c5fa6c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a0c4a57c0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A0C5FA6','active','2025-07-23','2026-07-23',1,'2025-11-13 23:46:32'),('9a0d3d03c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a0d2398c0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A0D3D03','active','2025-07-23','2026-07-23',1,'2025-11-13 23:46:32'),('9a0dd288c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a0dbb48c0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A0DD288','active','2025-07-23','2026-07-23',1,'2025-11-13 23:46:32'),('9a0e6befc0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a0e53b8c0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A0E6BEF','active','2025-07-23','2026-07-23',1,'2025-11-13 23:46:32'),('9a0efe01c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a0eea1ec0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A0EFE01','active','2025-07-23','2026-07-23',1,'2025-11-13 23:46:32'),('9a0f8aaec0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a0f7707c0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A0F8AAE','active','2025-07-23','2026-07-23',1,'2025-11-13 23:46:32'),('9a101cb8c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a100893c0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A101CB8','active','2025-07-23','2026-07-23',1,'2025-11-13 23:46:32'),('9a10aa1dc0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a108facc0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A10AA1D','active','2025-07-23','2026-07-23',1,'2025-11-13 23:46:32'),('9a11321dc0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a111b45c0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A11321D','active','2025-07-23','2026-07-23',1,'2025-11-13 23:46:32'),('9a11bf35c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a11a871c0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A11BF35','active','2025-07-23','2026-07-23',1,'2025-11-13 23:46:32'),('9a1255bfc0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a123e90c0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A1255BF','active','2025-07-23','2026-07-23',1,'2025-11-13 23:46:32'),('9a12edb5c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a12d8aec0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A12EDB5','active','2025-07-23','2026-07-23',1,'2025-11-13 23:46:32'),('9a1382d3c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a1367abc0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A1382D3','active','2025-07-23','2026-07-23',1,'2025-11-13 23:46:32'),('9a141007c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a13f823c0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A141007','active','2025-07-23','2026-07-23',1,'2025-11-13 23:46:32'),('9a14b7fdc0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a14a053c0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A14B7FD','active','2025-07-23','2026-07-23',1,'2025-11-13 23:46:32'),('9a155998c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a1542dfc0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A155998','active','2025-07-23','2026-07-23',1,'2025-11-13 23:46:32'),('9a15eb89c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a15d50cc0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A15EB89','active','2025-07-23','2026-07-23',1,'2025-11-13 23:46:32'),('9a167b4cc0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a1664cac0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A167B4C','active','2025-07-23','2026-07-23',1,'2025-11-13 23:46:32'),('9a1713e2c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a16f795c0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A1713E2','active','2025-07-23','2026-07-23',1,'2025-11-13 23:46:32'),('9a17b715c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a179ac8c0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A17B715','active','2025-07-23','2026-07-23',1,'2025-11-13 23:46:32'),('9a18553fc0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a183e6ac0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A18553F','active','2025-07-23','2026-07-23',1,'2025-11-13 23:46:32'),('9a1903e1c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a18e777c0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A1903E1','active','2025-07-23','2026-07-23',1,'2025-11-13 23:46:32'),('9a19a3b4c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a198dc0c0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A19A3B4','active','2025-07-23','2026-07-23',1,'2025-11-13 23:46:32'),('9a1a42fcc0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a1a2c7ec0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A1A42FC','active','2025-07-23','2026-07-23',1,'2025-11-13 23:46:32'),('9a1ada95c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a1ac58ec0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A1ADA95','active','2025-07-23','2026-07-23',1,'2025-11-13 23:46:32'),('9a1b6c08c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a1b568bc0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A1B6C08','active','2025-07-23','2026-07-23',1,'2025-11-13 23:46:32'),('9a1c0299c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a1be6f7c0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A1C0299','active','2025-07-23','2026-07-23',1,'2025-11-13 23:46:32'),('9a1ca08ac0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a1c8974c0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A1CA08A','active','2025-07-23','2026-07-23',1,'2025-11-13 23:46:32'),('9a1d3764c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a1d20b6c0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A1D3764','active','2025-07-23','2026-07-23',1,'2025-11-13 23:46:32'),('9a1e0f76c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a1df526c0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A1E0F76','active','2025-07-23','2026-07-23',1,'2025-11-13 23:46:32'),('9a1eac3ac0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a1e906bc0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A1EAC3A','active','2025-07-23','2026-07-23',1,'2025-11-13 23:46:32'),('9a1f4780c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a1f32ccc0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A1F4780','active','2025-07-23','2026-07-23',1,'2025-11-13 23:46:32'),('9a1ff44dc0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a1fd941c0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A1FF44D','active','2025-07-23','2026-07-23',1,'2025-11-13 23:46:32'),('9a20925fc0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a20774cc0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A20925F','active','2025-07-23','2026-07-23',1,'2025-11-13 23:46:32'),('9a213979c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a211c68c0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A213979','active','2025-07-23','2026-07-23',1,'2025-11-13 23:46:32'),('9a21dac0c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a21c3f3c0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A21DAC0','active','2025-07-23','2026-07-23',1,'2025-11-13 23:46:32'),('9a227fddc0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a226339c0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A227FDD','active','2025-07-23','2026-07-23',1,'2025-11-13 23:46:32'),('9a232390c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a230c15c0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A232390','active','2025-07-23','2026-07-23',1,'2025-11-13 23:46:32'),('9a23cb3bc0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a23b030c0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A23CB3B','active','2025-07-23','2026-07-23',1,'2025-11-13 23:46:32'),('9a246fddc0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a245933c0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A246FDD','active','2025-07-23','2026-07-23',1,'2025-11-13 23:46:32'),('9a251f8cc0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a250463c0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A251F8C','active','2025-07-23','2026-07-23',1,'2025-11-13 23:46:32'),('9a25de65c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a25c234c0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A25DE65','active','2025-07-23','2026-07-23',1,'2025-11-13 23:46:32'),('9a269621c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a267a75c0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A269621','active','2025-07-23','2026-07-23',1,'2025-11-13 23:46:32'),('9a27397dc0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a2723acc0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A27397D','active','2025-07-23','2026-07-23',1,'2025-11-13 23:46:32'),('9a27eaabc0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a27cde1c0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A27EAAB','active','2025-07-23','2026-07-23',1,'2025-11-13 23:46:32'),('9a2887b9c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a286a86c0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A2887B9','active','2025-11-14','2026-11-14',1,'2025-11-13 23:46:33'),('9a29425bc0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a292bf5c0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A29425B','active','2025-07-23','2026-07-23',1,'2025-11-13 23:46:33'),('9a29f395c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a29db2fc0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A29F395','active','2025-07-23','2026-07-23',1,'2025-11-13 23:46:33'),('9a2a9514c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a2a78dfc0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A2A9514','active','2025-07-23','2026-07-23',1,'2025-11-13 23:46:33'),('9a2b4a08c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a2b2b41c0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A2B4A08','active','2025-07-23','2026-07-23',1,'2025-11-13 23:46:33'),('9a2c8b2fc0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a2c7295c0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A2C8B2F','active','2025-07-23','2026-07-23',1,'2025-11-13 23:46:33'),('9a2d2b79c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a2d1552c0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A2D2B79','active','2025-07-23','2026-07-23',1,'2025-11-13 23:46:33'),('9a2dfa8ec0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a2ddd8cc0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A2DFA8E','active','2025-07-23','2026-07-23',1,'2025-11-13 23:46:33'),('9a2eb0a8c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a2e94c5c0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A2EB0A8','active','2025-07-23','2026-07-23',1,'2025-11-13 23:46:33'),('9a2f5af7c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a2f44ffc0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A2F5AF7','active','2025-07-23','2026-07-23',1,'2025-11-13 23:46:33'),('9a300a07c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a2fee2bc0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A300A07','active','2025-07-23','2026-07-23',1,'2025-11-13 23:46:33'),('9a30e622c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a30d22ec0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A30E622','active','2025-07-23','2026-07-23',1,'2025-11-13 23:46:33'),('9a3184e8c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a316f71c0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A3184E8','active','2025-07-23','2026-07-23',1,'2025-11-13 23:46:33'),('9a322381c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a320c7ac0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A322381','active','2025-07-23','2026-07-23',1,'2025-11-13 23:46:33'),('9a32e392c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a32c698c0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A32E392','active','2025-07-23','2026-07-23',1,'2025-11-13 23:46:33'),('9a33a5fec0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a33895fc0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A33A5FE','active','2025-07-23','2026-07-23',1,'2025-11-13 23:46:33'),('9a344575c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a342a3dc0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A344575','active','2025-07-23','2026-07-23',1,'2025-11-13 23:46:33'),('9a34f2d2c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a34dbb7c0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A34F2D2','active','2025-07-23','2026-07-23',1,'2025-11-13 23:46:33'),('9a35a39dc0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a358981c0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A35A39D','active','2025-07-23','2026-07-23',1,'2025-11-13 23:46:33'),('9a365672c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a363e23c0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A365672','active','2025-07-23','2026-07-23',1,'2025-11-13 23:46:33'),('9a37aed9c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a379289c0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A37AED9','active','2025-07-28','2026-07-28',1,'2025-11-13 23:46:33'),('9a38550dc0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a383f4ac0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A38550D','active','2025-08-22','2026-08-22',1,'2025-11-13 23:46:33'),('9a38f8b9c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a38e3a4c0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A38F8B9','active','2025-08-22','2026-08-22',1,'2025-11-13 23:46:33'),('9a39b576c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a3998ebc0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A39B576','active','2025-08-22','2026-10-17',1,'2025-11-13 23:46:33'),('9a3b2700c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a3b0bb9c0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A3B2700','active','2025-08-22','2026-08-22',1,'2025-11-13 23:46:33'),('9a3bd008c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a3bb950c0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A3BD008','active','2025-09-02','2026-09-02',1,'2025-11-13 23:46:33'),('9a3c7c41c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a3c65a6c0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A3C7C41','active','2025-09-07','2026-09-07',1,'2025-11-13 23:46:33'),('9a3d257bc0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a3d1005c0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A3D257B','active','2025-09-10','2026-09-10',1,'2025-11-13 23:46:33'),('9a3dcc64c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a3db617c0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A3DCC64','active','2025-09-10','2026-09-10',1,'2025-11-13 23:46:33'),('9a3e7c51c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a3e64b4c0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A3E7C51','active','2025-09-11','2026-09-11',1,'2025-11-13 23:46:33'),('9a3f3256c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a3f1a62c0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A3F3256','active','2025-09-17','2026-09-17',1,'2025-11-13 23:46:33'),('9a3fe0a3c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a3fc8b7c0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A3FE0A3','active','2025-09-17','2026-09-17',1,'2025-11-13 23:46:33'),('9a408babc0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a407409c0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A408BAB','active','2025-09-17','2026-09-17',1,'2025-11-13 23:46:33'),('9a41257ec0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a410ec1c0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A41257E','active','2025-09-19','2026-09-19',1,'2025-11-13 23:46:33'),('9a41d3b0c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a41bd38c0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A41D3B0','active','2025-09-21','2026-09-21',1,'2025-11-13 23:46:33'),('9a42801dc0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a426898c0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A42801D','active','2025-09-21','2026-09-21',1,'2025-11-13 23:46:33'),('9a437093c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a43542ec0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A437093','active','2025-09-23','2026-09-23',1,'2025-11-13 23:46:33'),('9a443c1bc0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a4423f2c0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A443C1B','active','2025-09-24','2026-09-24',1,'2025-11-13 23:46:33'),('9a44e99ac0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a44d32cc0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A44E99A','active','2025-09-24','2026-09-24',1,'2025-11-13 23:46:33'),('9a459dd7c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a4585ffc0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A459DD7','active','2025-09-25','2026-09-25',1,'2025-11-13 23:46:33'),('9a464144c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a462ad4c0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A464144','active','2025-09-25','2026-09-25',1,'2025-11-13 23:46:33'),('9a46f551c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a46d709c0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A46F551','active','2025-09-26','2026-09-26',1,'2025-11-13 23:46:33'),('9a47ac37c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a4793d0c0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A47AC37','active','2025-09-26','2026-09-26',1,'2025-11-13 23:46:33'),('9a48669ec0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a484deec0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A48669E','active','2025-09-26','2026-09-26',1,'2025-11-13 23:46:33'),('9a491a07c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a4901b4c0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A491A07','active','2025-09-26','2026-09-26',1,'2025-11-13 23:46:33'),('9a49d39bc0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a49bc79c0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A49D39B','active','2025-09-26','2026-09-26',1,'2025-11-13 23:46:33'),('9a4a8510c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a4a6f57c0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A4A8510','active','2025-09-29','2026-09-29',1,'2025-11-13 23:46:33'),('9a4b1ff6c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a4b06f5c0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A4B1FF6','active','2025-09-29','2026-09-29',1,'2025-11-13 23:46:33'),('9a4bd65fc0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a4bbec5c0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A4BD65F','active','2025-09-29','2026-09-29',1,'2025-11-13 23:46:33'),('9a4c9bdbc0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a4c8009c0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A4C9BDB','active','2025-09-29','2026-09-29',1,'2025-11-13 23:46:33'),('9a4d4a67c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a4d3490c0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A4D4A67','active','2025-09-29','2026-09-29',1,'2025-11-13 23:46:33'),('9a4dfd15c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a4de4e5c0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A4DFD15','active','2025-09-29','2026-09-29',1,'2025-11-13 23:46:33'),('9a4ec8e7c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a4eaf39c0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A4EC8E7','active','2025-09-29','2026-09-29',1,'2025-11-13 23:46:33'),('9a4f7099c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a4f54b3c0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A4F7099','active','2025-09-29','2026-09-29',1,'2025-11-13 23:46:33'),('9a502681c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a500ae4c0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A502681','active','2025-09-29','2026-09-29',1,'2025-11-13 23:46:33'),('9a50e274c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a50cc39c0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A50E274','active','2025-09-29','2026-09-29',1,'2025-11-13 23:46:33'),('9a519224c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a517c49c0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A519224','active','2025-09-29','2026-09-29',1,'2025-11-13 23:46:33'),('9a524392c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a522722c0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A524392','active','2025-09-30','2026-09-30',1,'2025-11-13 23:46:33'),('9a52f582c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a52dd61c0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A52F582','active','2025-09-30','2026-09-30',1,'2025-11-13 23:46:33'),('9a53ae00c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a53948cc0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A53AE00','active','2025-09-30','2026-09-30',1,'2025-11-13 23:46:33'),('9a546017c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','9a544a60c0e211f08af7b02628506e5c','fe60b4f364430a94a8ab5d60e2fb83ab','MBR-9A546017','active','2025-10-17','2026-10-17',1,'2025-11-13 23:46:33');
UNLOCK TABLES;
commit;

--
-- Table structure for table `membership_plans`
--

DROP TABLE IF EXISTS `membership_plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `membership_plans` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `company_id` char(36) NOT NULL,
  `output_product_id` char(36) DEFAULT NULL,
  `plan_code` varchar(100) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `currency` varchar(3) DEFAULT 'NOK',
  `billing_cycle` varchar(20) DEFAULT 'monthly',
  `benefits` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `image_url` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_id` (`company_id`,`plan_code`),
  KEY `idx_output_product` (`output_product_id`),
  CONSTRAINT `membership_plans_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `membership_plans_ibfk_2` FOREIGN KEY (`output_product_id`) REFERENCES `output_products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `membership_plans`
--

LOCK TABLES `membership_plans` WRITE;
set autocommit=0;
INSERT INTO `membership_plans` VALUES ('169e9e52b0736f776946e9c719d0ad74','7efbddea-b507-11f0-936b-b02628506e5c',NULL,'PLAN-169E9E52','222','',222.00,'NOK','monthly','',0,'2025-11-07 21:58:08',NULL),('6601fc2e4de5a93ea092b22aa21abed5','a9cdf14b6790d0247b55534d68065eb695f9',NULL,'PLAN-6601FC2E','Jojo','Hehe',200.00,'NOK','monthly','Jippi',1,'2025-11-13 16:36:39','https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRprwIbFRCeE5fFR-Sw1E0WajXoiUfNQN08IPsEfoEM5hBXUqxkBAFrDNE&s=10'),('824e62807f611850b782c90483a2f40d','7efbddea-b507-11f0-936b-b02628506e5c',NULL,'PLAN-824E6280','333','33',3.00,'NOK','monthly','',0,'2025-11-07 21:58:42',NULL),('c756c21b869ae63c37e015f8ac88cea2','7efbddea-b507-11f0-936b-b02628506e5c',NULL,'PLAN-C756C21B','Bergsøy medlem styre','Hei',200.00,'NOK','yearly','',1,'2025-11-14 16:59:40',NULL),('ef57db8d66a47a2f7c8e88eed304c971','7efbddea-b507-11f0-936b-b02628506e5c',NULL,'PLAN-EF57DB8D','Gjerrigknark','',150.00,'NOK','yearly','',0,'2025-10-29 21:57:58',NULL),('f0e64c71dbe8215e8fd8dfe733fc2d61','7efbddea-b507-11f0-936b-b02628506e5c',NULL,'PLAN-F0E64C71','Josse','Heihei',500.00,'NOK','quarterly','Tilgang til alt',0,'2025-11-14 17:19:31',NULL),('fe60b4f364430a94a8ab5d60e2fb83ab','7efbddea-b507-11f0-936b-b02628506e5c',NULL,'PLAN-FE60B4F3','Bergsøy Medlem','Vær med å støtt oss!:-)',150.00,'NOK','yearly','Medlemspriser ved alle bergsøy arrangement!\nNyhetsbrev.\nRask tilgang til alt av våre ressurser.',1,'2025-11-13 08:23:46','https://www.temashop.no/media/catalog/product/cache/cat_resized/1200/0/b/l/blaa_foliestjerne_festartikler_blaa_fargetema.jpg');
UNLOCK TABLES;
commit;

--
-- Table structure for table `output_products`
--

DROP TABLE IF EXISTS `output_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `output_products` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `company_id` char(36) NOT NULL,
  `module_id` char(36) NOT NULL,
  `product_type` varchar(50) NOT NULL,
  `product_id` char(36) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(15,2) NOT NULL,
  `cost_price` decimal(15,2) DEFAULT 0.00,
  `currency` varchar(3) DEFAULT 'NOK',
  `is_visible` tinyint(1) DEFAULT 1,
  `is_public` tinyint(1) DEFAULT 1,
  `is_marketplace_featured` tinyint(1) DEFAULT 0,
  `referred_by` varchar(100) DEFAULT NULL,
  `marketplace_priority` int(11) DEFAULT 0,
  `created_by` char(36) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_type` (`product_type`,`product_id`),
  KEY `idx_company` (`company_id`),
  KEY `idx_module` (`module_id`),
  KEY `idx_type` (`product_type`),
  KEY `idx_visibility` (`is_visible`,`is_public`,`is_marketplace_featured`),
  CONSTRAINT `output_products_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `output_products_ibfk_2` FOREIGN KEY (`module_id`) REFERENCES `system_modules` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `output_products`
--

LOCK TABLES `output_products` WRITE;
set autocommit=0;
INSERT INTO `output_products` VALUES ('0066f9392d98a322b3b6055cfb0d61ba','7efbddea-b507-11f0-936b-b02628506e5c','7f3c499a-af1a-11f0-a914-b02628506e5c','product','dedbc54d4e7cf46b1feb77e7a55d1f1b4c4a','',NULL,0.00,0.00,'NOK',1,1,0,NULL,0,'7f0d55c4-b507-11f0-936b-b02628506e5c','2025-11-08 15:51:10','2025-11-08 15:51:10'),('1b342073f71a1aae245ac91e339a3105','a9cdf14b6790d0247b55534d68065eb695f9','15a3c857-b57f-11f0-a6d0-b02628506e5c','service','af620ca225ebb8e242104a8807fc3c8f','Herreklipp','Klipp hos oss',500.00,0.00,'NOK',1,1,0,NULL,0,'580850091a22fb4dbe9023b759222b305282','2025-11-13 16:38:23','2025-11-13 16:38:23'),('235bd345a970748591d6cdd4d592430a','e63933d7ed368ed5a117c9c6e9a7f434fbaf','7f3c4d5f-af1a-11f0-a914-b02628506e5c','membership','7cba9d36313d7e5b2dfcbb25f2a11baa','Hhh',NULL,200.00,0.00,'NOK',1,1,0,NULL,0,'2cb1eef4c42d797384ff208a9f0b407a8a53','2025-11-08 06:39:30','2025-11-09 17:25:57'),('272e909c697feb6e4ecf26b79a8fe96b','0a2f314a1b2f7d21ae3de741810ef5e44506','7f3c499a-af1a-11f0-a914-b02628506e5c','product','38d8f306c01c4c5f530d530474c932c7448e','Mår m/brød','',50.00,0.00,'NOK',1,1,0,NULL,0,'e722f72f4db3114c487c2a267de58fb2eb82','2025-11-08 09:24:02','2025-11-08 15:42:55'),('29cfc7de1268fd5d04f188c3fcb26acd','7efbddea-b507-11f0-936b-b02628506e5c','7f3c4c76-af1a-11f0-a914-b02628506e5c','event','dafc483c4d333496c57d5dc5440e3862','',NULL,0.00,0.00,'NOK',1,1,0,NULL,0,'7f0d55c4-b507-11f0-936b-b02628506e5c','2025-11-01 10:43:10','2025-11-01 10:43:10'),('2af5685b00390cc43601f77b11ad2083','453d3f87-b0e4-11f0-a1bb-b02628506e5c','7f3c4c76-af1a-11f0-a914-b02628506e5c','event','9e39a1cec21321397934348723717b9d','',NULL,0.00,0.00,'NOK',0,0,0,NULL,0,'4541881c-b0e4-11f0-a1bb-b02628506e5c','2025-10-30 12:13:52','2025-11-02 15:17:20'),('33646f4bbf4fac9b5316f9e04ed679cd','7efbddea-b507-11f0-936b-b02628506e5c','7f3c4c76-af1a-11f0-a914-b02628506e5c','event','249af8383aef5ef76d9a90650ad1223d','',NULL,0.00,0.00,'NOK',0,0,0,NULL,0,'7f0d55c4-b507-11f0-936b-b02628506e5c','2025-11-19 21:55:31','2025-11-20 11:55:04'),('3742ffe14152829d819b821c9424f4b4','e63933d7ed368ed5a117c9c6e9a7f434fbaf','7f3c4c76-af1a-11f0-a914-b02628506e5c','event','cc4305606c6895f399c0a790aaa75506','',NULL,0.00,0.00,'NOK',1,1,0,NULL,0,'2cb1eef4c42d797384ff208a9f0b407a8a53','2025-11-07 17:30:21','2025-11-07 17:30:21'),('38bb573427bd7afc4d14aeafb4013b9a','fa2fe5cc78d85c1eec44e3980fb01e8d75b0','7f3c499a-af1a-11f0-a914-b02628506e5c','product','096f422d361ed35726f2a99cf2c855f1bf84','',NULL,0.00,0.00,'NOK',1,1,0,NULL,0,'59bf1e0758e971cb388952b87cfcf072ef13','2025-11-05 19:06:49','2025-11-05 19:06:49'),('3a3a3d469390e2cd9d5302f541c0785e','fa2fe5cc78d85c1eec44e3980fb01e8d75b0','15a3c857-b57f-11f0-a6d0-b02628506e5c','service','1352b2d595070faaf86b6e93387a81bb','Bolleklipp','Kom å få fin bolleklipp',500.00,0.00,'NOK',1,1,0,NULL,0,'59bf1e0758e971cb388952b87cfcf072ef13','2025-11-05 19:01:37','2025-11-05 19:01:37'),('41796324978caaf4aaa238222e9ce875','7efbddea-b507-11f0-936b-b02628506e5c','7f3c4c76-af1a-11f0-a914-b02628506e5c','event','7f71bfba77c3ab8df3cae50a19828367','',NULL,0.00,0.00,'NOK',1,1,0,NULL,0,'f996adb8d578c9f15859cc9ffb78b023792b','2025-11-22 12:18:46','2025-11-22 12:18:46'),('4505a58b22e1cf09c57d40d8d48f0aef','7efbddea-b507-11f0-936b-b02628506e5c','7f3c4c76-af1a-11f0-a914-b02628506e5c','event','78519c1d48607be776164300edd1a474','',NULL,0.00,0.00,'NOK',1,1,0,NULL,0,'f996adb8d578c9f15859cc9ffb78b023792b','2025-11-22 12:49:33','2025-11-22 12:49:33'),('4525bdcda36dc0c7a7a3c9f07a4cf8ea','453d3f87-b0e4-11f0-a1bb-b02628506e5c','7f3c499a-af1a-11f0-a914-b02628506e5c','product','cbe046cd560776839174530c8510ad048e8b','Pepsi','',40.00,0.00,'NOK',1,1,0,NULL,0,'4541881c-b0e4-11f0-a1bb-b02628506e5c','2025-10-25 22:48:16','2025-10-28 16:29:11'),('4e9544b949d4ef972a5ed647ddff20ed','7efbddea-b507-11f0-936b-b02628506e5c','7f3c4c76-af1a-11f0-a914-b02628506e5c','event','3a2745d2bedaaa79f052826c2a3cf103','',NULL,0.00,0.00,'NOK',0,0,0,NULL,0,'7f072d6a-b507-11f0-936b-b02628506e5c','2025-11-19 23:41:51','2025-11-20 11:55:12'),('52b2f65a444370d2326a687208e5135b','e63933d7ed368ed5a117c9c6e9a7f434fbaf','7f3c4c76-af1a-11f0-a914-b02628506e5c','event','f4fa9e3496bbe94c94d56ec257bc8d96','',NULL,0.00,0.00,'NOK',1,1,0,NULL,0,'2cb1eef4c42d797384ff208a9f0b407a8a53','2025-11-07 17:30:25','2025-11-07 17:30:25'),('564058d4e43d9c4ab0b9faa9b902326b','7efbddea-b507-11f0-936b-b02628506e5c','7f3c4c76-af1a-11f0-a914-b02628506e5c','event','8cb7e96db38209a1ecf708f865686c05','',NULL,0.00,0.00,'NOK',1,1,0,NULL,0,'f996adb8d578c9f15859cc9ffb78b023792b','2025-11-22 00:46:19','2025-11-22 00:46:19'),('58743316224ffb2537faad7a7b763705','7efbddea-b507-11f0-936b-b02628506e5c','7f3c4c76-af1a-11f0-a914-b02628506e5c','event','7d7c7e6fbc6f79496e3fa273b89aec20','',NULL,0.00,0.00,'NOK',0,0,0,NULL,0,'7f0d55c4-b507-11f0-936b-b02628506e5c','2025-11-20 10:26:29','2025-11-20 11:55:01'),('7a8cce56b077e4090279c0ac2e3dc5b5','7efbddea-b507-11f0-936b-b02628506e5c','7f3c499a-af1a-11f0-a914-b02628506e5c','product','2f5d1e6bdd2ba279943b2d880e6c246bdfce','Cola','',40.00,0.00,'NOK',1,1,0,NULL,0,'7f0d55c4-b507-11f0-936b-b02628506e5c','2025-10-29 21:52:19','2025-10-29 21:52:54'),('7eb177ae35f295added103e686049167','a9cdf14b6790d0247b55534d68065eb695f9','7f3c4d5f-af1a-11f0-a914-b02628506e5c','membership','6601fc2e4de5a93ea092b22aa21abed5','Jojo',NULL,200.00,0.00,'NOK',1,1,0,NULL,0,'580850091a22fb4dbe9023b759222b305282','2025-11-13 16:36:39','2025-11-13 16:36:39'),('805efa3ef0bf681e0c16b5e35cec5146','7efbddea-b507-11f0-936b-b02628506e5c','7f3c4d5f-af1a-11f0-a914-b02628506e5c','membership','fe60b4f364430a94a8ab5d60e2fb83ab','Bergsøy Medlem',NULL,150.00,0.00,'NOK',1,1,0,NULL,0,'7f0d55c4-b507-11f0-936b-b02628506e5c','2025-11-13 08:23:46','2025-11-14 17:19:31'),('83ed74872d170b12e7b9b92626569c58','e63933d7ed368ed5a117c9c6e9a7f434fbaf','7f3c4c76-af1a-11f0-a914-b02628506e5c','event','d944100bca56b3fc2eaf6e9ed7f1363e','',NULL,0.00,0.00,'NOK',1,1,0,NULL,0,'2cb1eef4c42d797384ff208a9f0b407a8a53','2025-11-07 17:30:24','2025-11-07 17:30:24'),('86c5bb3c5c1a5bf9625bc2c255b7bd83','7efbddea-b507-11f0-936b-b02628506e5c','7f3c4c76-af1a-11f0-a914-b02628506e5c','event','b53bd9a0d795f7f5114b1826d08e2e41','',NULL,0.00,0.00,'NOK',0,0,0,NULL,0,'7f0d55c4-b507-11f0-936b-b02628506e5c','2025-10-29 21:55:37','2025-11-02 00:20:53'),('882f9b2a1b7c99265a2e5433320b0f6e','453d3f87-b0e4-11f0-a1bb-b02628506e5c','7f3c499a-af1a-11f0-a914-b02628506e5c','product','7cf46b250bdd12f195a9ff01154b3be54871','Pølse med brød','',40.00,0.00,'NOK',1,1,0,NULL,0,'4541881c-b0e4-11f0-a1bb-b02628506e5c','2025-10-28 11:27:02','2025-10-28 11:27:56'),('8967bf21bc9615b78c3afb4bc019e193','7efbddea-b507-11f0-936b-b02628506e5c','7f3c499a-af1a-11f0-a914-b02628506e5c','product','16ad682f97547c48b3a2d78cb9aea61d5251','Pølse med brød','Gode pølse',40.00,0.00,'NOK',1,1,0,NULL,0,'7f0d55c4-b507-11f0-936b-b02628506e5c','2025-10-29 22:13:17','2025-10-29 22:16:56'),('99abccb9e2d0e55f19fa2a7e9ec72150','e090c403-b7f1-11f0-a3af-b02628506e5c','7f3c4c76-af1a-11f0-a914-b02628506e5c','event','b8ddef108a02645587f1b8c3a2d15a80','',NULL,0.00,0.00,'NOK',1,1,0,NULL,0,'48f41e2f-b7f2-11f0-a3af-b02628506e5c','2025-11-02 16:57:00','2025-11-02 16:57:00'),('99c9f7a23ead3b565b51374179904432','7efbddea-b507-11f0-936b-b02628506e5c','7f3c4d5f-af1a-11f0-a914-b02628506e5c','membership','169e9e52b0736f776946e9c719d0ad74','222',NULL,222.00,0.00,'NOK',0,0,0,NULL,0,'7f0d55c4-b507-11f0-936b-b02628506e5c','2025-11-07 21:58:08','2025-11-14 17:19:31'),('9a49db3753554d99e5be428a4e8f88d5','453d3f87-b0e4-11f0-a1bb-b02628506e5c','7f3c4c76-af1a-11f0-a914-b02628506e5c','event','604f3c5fe642013db561392fdaf56c45','',NULL,0.00,0.00,'NOK',0,1,0,NULL,0,'4541881c-b0e4-11f0-a1bb-b02628506e5c','2025-10-25 18:02:29','2025-11-02 09:12:15'),('9da14215e9f4998b9d6bfa9c434b6373','0a2f314a1b2f7d21ae3de741810ef5e44506','7f3c499a-af1a-11f0-a914-b02628506e5c','product','4f4a3f911d972221dfe940b1062c91387f63','Snacks','',40.00,0.00,'NOK',1,1,0,NULL,0,'e722f72f4db3114c487c2a267de58fb2eb82','2025-11-08 09:24:32','2025-11-08 15:42:39'),('a6d39317993389b3a0cefce4f83d0b60','0a2f314a1b2f7d21ae3de741810ef5e44506','7f3c499a-af1a-11f0-a914-b02628506e5c','product','22bb46e76daf17407bd9d18a1035180fa63d','',NULL,0.00,0.00,'NOK',1,1,0,NULL,0,'e722f72f4db3114c487c2a267de58fb2eb82','2025-11-08 15:41:19','2025-11-08 15:41:19'),('af0a91e6fd1efbbf17ee1ef011831ca3','e63933d7ed368ed5a117c9c6e9a7f434fbaf','7f3c4c76-af1a-11f0-a914-b02628506e5c','event','01e5c67cdb554af229977d2ae644a8de','',NULL,0.00,0.00,'NOK',0,0,0,NULL,0,'2cb1eef4c42d797384ff208a9f0b407a8a53','2025-11-07 17:30:08','2025-11-08 06:40:13'),('b98576f7de541d6f204c4715e2bdebcc','e63933d7ed368ed5a117c9c6e9a7f434fbaf','7f3c4d5f-af1a-11f0-a914-b02628506e5c','membership','eb882b1f7720e88dcc2d4ad996c5d52a','Heisann',NULL,400.00,0.00,'NOK',1,1,0,NULL,0,'2cb1eef4c42d797384ff208a9f0b407a8a53','2025-11-09 17:25:57','2025-11-09 17:25:57'),('bbff23051b4846abcc22d34abcab57a1','7efbddea-b507-11f0-936b-b02628506e5c','7f3c4c76-af1a-11f0-a914-b02628506e5c','event','79bc8391e7e0b8725fd3397c110bd5cc','',NULL,0.00,0.00,'NOK',0,0,0,NULL,0,'7f0d55c4-b507-11f0-936b-b02628506e5c','2025-11-07 21:56:39','2025-11-07 21:56:46'),('bc1da798273586ab819f29f37782f47f','7efbddea-b507-11f0-936b-b02628506e5c','7f3c4c76-af1a-11f0-a914-b02628506e5c','event','63535e7e926e77c74989c93c1efb4eab','',NULL,0.00,0.00,'NOK',0,0,0,NULL,0,'7f0d55c4-b507-11f0-936b-b02628506e5c','2025-11-21 23:39:47','2025-11-22 00:48:19'),('c3437cbe187ef8db8991468471ad8c78','7efbddea-b507-11f0-936b-b02628506e5c','7f3c4c76-af1a-11f0-a914-b02628506e5c','event','8ae4ea0ffe48b187b9f67ddef84aabc2','',NULL,0.00,0.00,'NOK',0,0,0,NULL,0,'7f0d55c4-b507-11f0-936b-b02628506e5c','2025-11-19 13:38:38','2025-11-20 12:39:22'),('c3b0a67c4303738886ff7a8c45a221d2','7efbddea-b507-11f0-936b-b02628506e5c','7f3c499a-af1a-11f0-a914-b02628506e5c','product','24692482c3a89b75763a4408c338696369b4','',NULL,0.00,0.00,'NOK',1,1,0,NULL,0,'7f0d55c4-b507-11f0-936b-b02628506e5c','2025-11-08 15:51:12','2025-11-08 15:51:12'),('c8e935f61a043441ad5e0913df26bc48','453d3f87-b0e4-11f0-a1bb-b02628506e5c','7f3c4d5f-af1a-11f0-a914-b02628506e5c','membership','858090db8cb491a89752b0bca87ec7b3','Medlem',NULL,100.00,0.00,'NOK',1,1,0,NULL,0,'1','2025-10-29 20:06:59','2025-10-29 20:57:28'),('caf85ec69cfa99b93792c26b5fd021cc','453d3f87-b0e4-11f0-a1bb-b02628506e5c','7f3c4d5f-af1a-11f0-a914-b02628506e5c','membership','2bbf6cd74c09e24e05b12b4576409029','medlem','h',150.00,0.00,'NOK',1,1,0,NULL,0,'4541881c-b0e4-11f0-a1bb-b02628506e5c','2025-10-28 16:29:39','2025-10-29 20:57:27'),('ced5f4310501ea776132b6aa198a6fb1','453d3f87-b0e4-11f0-a1bb-b02628506e5c','7f3c4c76-af1a-11f0-a914-b02628506e5c','event','c4558dd2673bec6448e527eaac19a321','',NULL,0.00,0.00,'NOK',0,1,0,NULL,0,'4541881c-b0e4-11f0-a1bb-b02628506e5c','2025-10-30 11:07:46','2025-11-02 09:12:26'),('cf427a43b9371ebff930ad3c7a5455e9','453d3f87-b0e4-11f0-a1bb-b02628506e5c','7f3c4c76-af1a-11f0-a914-b02628506e5c','event','cdf48cd049b6e536c14c07baf54a59ef','',NULL,0.00,0.00,'NOK',0,1,0,NULL,0,'4541881c-b0e4-11f0-a1bb-b02628506e5c','2025-10-30 12:17:30','2025-11-02 09:12:30'),('d407d9c71ee78719dbf6872b8eade825','fa2fe5cc78d85c1eec44e3980fb01e8d75b0','7f3c4c76-af1a-11f0-a914-b02628506e5c','event','7c9554bacd712364efd7123e267c49a0','',NULL,0.00,0.00,'NOK',1,1,0,NULL,0,'59bf1e0758e971cb388952b87cfcf072ef13','2025-11-05 18:59:38','2025-11-05 18:59:38'),('d5be61d1a6ddbc902e26ce99e82683d9','0a2f314a1b2f7d21ae3de741810ef5e44506','7f3c499a-af1a-11f0-a914-b02628506e5c','product','16bbeac3470f834df80f0b63d456b9c39fa2','Pølse med brød','',40.00,0.00,'NOK',1,1,0,NULL,0,'e722f72f4db3114c487c2a267de58fb2eb82','2025-11-08 09:22:07','2025-11-08 15:43:10'),('d9d5affd1b8714950eb09cb3a5d8d75f','7efbddea-b507-11f0-936b-b02628506e5c','7f3c4c76-af1a-11f0-a914-b02628506e5c','event','ecc015c54165003b1752ef77581ca832','',NULL,0.00,0.00,'NOK',0,0,0,NULL,0,'7f0d55c4-b507-11f0-936b-b02628506e5c','2025-11-19 13:52:59','2025-11-20 12:39:17'),('e54e1da83a4a6567a3e75586d7df7a4a','e63933d7ed368ed5a117c9c6e9a7f434fbaf','7f3c4c76-af1a-11f0-a914-b02628506e5c','event','05b6f58ae4428d741059e081e31c94d3','',NULL,0.00,0.00,'NOK',1,1,0,NULL,0,'2cb1eef4c42d797384ff208a9f0b407a8a53','2025-11-07 17:30:12','2025-11-07 17:30:12'),('e6d218485f15aefc02ccbbb3df3b1fe4','7efbddea-b507-11f0-936b-b02628506e5c','7f3c4d5f-af1a-11f0-a914-b02628506e5c','membership','c756c21b869ae63c37e015f8ac88cea2','Bergsøy medlem styre',NULL,200.00,0.00,'NOK',1,1,0,NULL,0,'7f0d55c4-b507-11f0-936b-b02628506e5c','2025-11-14 16:59:40','2025-11-14 17:19:31'),('e71e78dafd755f51dbaba45d6e5f57fd','0a2f314a1b2f7d21ae3de741810ef5e44506','7f3c499a-af1a-11f0-a914-b02628506e5c','product','09f274670a3f2401001d2d68de1ba9822e27','Brus Cola','',40.00,0.00,'NOK',1,1,0,NULL,0,'e722f72f4db3114c487c2a267de58fb2eb82','2025-11-08 14:10:29','2025-11-08 15:42:03'),('f15d5252e6e0e6be047fa7da480dc71f','7efbddea-b507-11f0-936b-b02628506e5c','7f3c4c76-af1a-11f0-a914-b02628506e5c','event','c39b93839693a60a1c0ad92687f34c33','',NULL,0.00,0.00,'NOK',1,1,0,NULL,0,'7f0d55c4-b507-11f0-936b-b02628506e5c','2025-11-20 12:40:11','2025-11-20 12:40:11'),('f1dac2a3466789d281fef56c0bbd2a06','e63933d7ed368ed5a117c9c6e9a7f434fbaf','7f3c499a-af1a-11f0-a914-b02628506e5c','product','dfe54e03c045f883a280ecab8c27eef1f8e5','',NULL,0.00,0.00,'NOK',1,1,0,NULL,0,'2cb1eef4c42d797384ff208a9f0b407a8a53','2025-11-08 06:42:59','2025-11-08 06:42:59'),('f766e648d443a2a46a6c82eae8800832','e090c403-b7f1-11f0-a3af-b02628506e5c','7f3c4c76-af1a-11f0-a914-b02628506e5c','event','0d0492c8d98a6d05a1715af1cd9f791a','',NULL,0.00,0.00,'NOK',1,1,0,NULL,0,'48f41e2f-b7f2-11f0-a3af-b02628506e5c','2025-11-03 17:49:06','2025-11-03 17:49:06'),('f8e434e1a66ff2b4806394a11139a1fd','7efbddea-b507-11f0-936b-b02628506e5c','7f3c4c76-af1a-11f0-a914-b02628506e5c','event','c4f196578fa691e9d98a4e8f4418be64','',NULL,0.00,0.00,'NOK',1,1,0,NULL,0,'7f0d55c4-b507-11f0-936b-b02628506e5c','2025-11-13 23:10:13','2025-11-13 23:10:13');
UNLOCK TABLES;
commit;

--
-- Table structure for table `rental_cabins`
--

DROP TABLE IF EXISTS `rental_cabins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `rental_cabins` (
  `object_id` char(36) NOT NULL,
  `sqm` int(11) DEFAULT NULL,
  `bedrooms` int(11) DEFAULT NULL,
  `beds` int(11) DEFAULT NULL,
  `bathrooms` int(11) DEFAULT NULL,
  `season_type` enum('Helår','Vinter','Sommer') DEFAULT NULL,
  `min_nights` int(11) DEFAULT 1,
  `cleaning_fee` decimal(10,2) DEFAULT 0.00,
  `distance_to_ski` varchar(100) DEFAULT NULL,
  `distance_to_store` varchar(100) DEFAULT NULL,
  `water_supply` varchar(100) DEFAULT NULL,
  `sewage` varchar(100) DEFAULT NULL,
  `heating` varchar(100) DEFAULT NULL,
  `facilities` text DEFAULT NULL,
  `max_guests` int(11) DEFAULT NULL,
  `pet_fee` decimal(10,2) DEFAULT 0.00,
  `linen_included` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`object_id`),
  CONSTRAINT `rental_cabins_ibfk_1` FOREIGN KEY (`object_id`) REFERENCES `rental_objects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rental_cabins`
--

LOCK TABLES `rental_cabins` WRITE;
set autocommit=0;
UNLOCK TABLES;
commit;

--
-- Table structure for table `rental_containers`
--

DROP TABLE IF EXISTS `rental_containers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `rental_containers` (
  `object_id` char(36) NOT NULL,
  `container_type` enum('Åpen','Lukket','Kombi') DEFAULT NULL,
  `cubic_meters` decimal(8,2) DEFAULT NULL,
  `sqm_floor` decimal(8,2) DEFAULT NULL,
  `length_m` decimal(6,2) DEFAULT NULL,
  `width_m` decimal(6,2) DEFAULT NULL,
  `height_m` decimal(6,2) DEFAULT NULL,
  `container_condition` enum('Ny','God','Slitt') DEFAULT NULL,
  `weatherproof` tinyint(1) DEFAULT 1,
  `lockable` tinyint(1) DEFAULT 1,
  `delivery_available` tinyint(1) DEFAULT 0,
  `delivery_cost` decimal(10,2) DEFAULT 0.00,
  `access_type` varchar(100) DEFAULT NULL,
  `climate_controlled` tinyint(1) DEFAULT 0,
  `shelving_included` tinyint(1) DEFAULT 0,
  `street` varchar(100) DEFAULT NULL,
  `zipcode` varchar(20) DEFAULT NULL,
  `postal_city` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`object_id`),
  CONSTRAINT `rental_containers_ibfk_1` FOREIGN KEY (`object_id`) REFERENCES `rental_objects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rental_containers`
--

LOCK TABLES `rental_containers` WRITE;
set autocommit=0;
UNLOCK TABLES;
commit;

--
-- Table structure for table `rental_event_items`
--

DROP TABLE IF EXISTS `rental_event_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `rental_event_items` (
  `object_id` char(36) NOT NULL,
  `item_type` varchar(100) DEFAULT NULL,
  `dimensions` varchar(100) DEFAULT NULL,
  `capacity` int(11) DEFAULT NULL,
  `color` varchar(50) DEFAULT NULL,
  `material` varchar(100) DEFAULT NULL,
  `weatherproof` tinyint(1) DEFAULT 0,
  `sides_included` int(11) DEFAULT NULL,
  `windows` tinyint(1) DEFAULT 0,
  `setup_included` tinyint(1) DEFAULT 0,
  `setup_cost` decimal(10,2) DEFAULT 0.00,
  `takedown_included` tinyint(1) DEFAULT 0,
  `flooring_available` tinyint(1) DEFAULT 0,
  `lighting_available` tinyint(1) DEFAULT 0,
  `heating_available` tinyint(1) DEFAULT 0,
  `min_rental_days` int(11) DEFAULT 1,
  PRIMARY KEY (`object_id`),
  CONSTRAINT `rental_event_items_ibfk_1` FOREIGN KEY (`object_id`) REFERENCES `rental_objects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rental_event_items`
--

LOCK TABLES `rental_event_items` WRITE;
set autocommit=0;
UNLOCK TABLES;
commit;

--
-- Table structure for table `rental_object_fields`
--

DROP TABLE IF EXISTS `rental_object_fields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `rental_object_fields` (
  `id` char(36) NOT NULL,
  `subdomain` varchar(100) NOT NULL,
  `field_name` varchar(50) NOT NULL,
  `is_required` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_field` (`subdomain`,`field_name`),
  KEY `idx_subdomain` (`subdomain`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rental_object_fields`
--

LOCK TABLES `rental_object_fields` WRITE;
set autocommit=0;
UNLOCK TABLES;
commit;

--
-- Table structure for table `rental_objects`
--

DROP TABLE IF EXISTS `rental_objects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `rental_objects` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `company_id` char(36) NOT NULL,
  `output_product_id` char(36) DEFAULT NULL,
  `object_name` varchar(255) NOT NULL,
  `serial_number` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `category` enum('residence','cabin','venue','container','tool','vehicle','office','sports_equipment','parking','rv','event_item','trailer') DEFAULT NULL,
  `rental_price` decimal(10,2) DEFAULT 0.00,
  `deposit_amount` decimal(10,2) DEFAULT 0.00,
  `condition_notes` text DEFAULT NULL,
  `contract_url` varchar(500) DEFAULT NULL,
  `contract_filename` varchar(255) DEFAULT NULL,
  `is_available` tinyint(1) DEFAULT 1,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` char(36) NOT NULL,
  `image_url` text DEFAULT NULL,
  `market_price` decimal(10,2) DEFAULT NULL,
  `rental_periods` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_company` (`company_id`),
  KEY `idx_output_product` (`output_product_id`),
  KEY `idx_available` (`is_available`,`is_active`),
  KEY `idx_serial` (`serial_number`),
  CONSTRAINT `rental_objects_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `rental_objects_ibfk_2` FOREIGN KEY (`output_product_id`) REFERENCES `output_products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rental_objects`
--

LOCK TABLES `rental_objects` WRITE;
set autocommit=0;
UNLOCK TABLES;
commit;

--
-- Table structure for table `rental_offices`
--

DROP TABLE IF EXISTS `rental_offices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `rental_offices` (
  `object_id` char(36) NOT NULL,
  `sqm` int(11) DEFAULT NULL,
  `office_rooms` int(11) DEFAULT NULL,
  `meeting_rooms` int(11) DEFAULT NULL,
  `reception_area` tinyint(1) DEFAULT 0,
  `floor` int(11) DEFAULT NULL,
  `elevator` tinyint(1) DEFAULT 0,
  `parking_spaces` int(11) DEFAULT 0,
  `kitchenette` tinyint(1) DEFAULT 0,
  `internet_speed_mbps` int(11) DEFAULT NULL,
  `air_conditioning` tinyint(1) DEFAULT 0,
  `security_system` tinyint(1) DEFAULT 0,
  `accessibility` varchar(255) DEFAULT NULL,
  `lease_type` enum('Langtidsleie','Korttidsleie') DEFAULT NULL,
  `min_lease_months` int(11) DEFAULT NULL,
  `energy_rating` varchar(10) DEFAULT NULL,
  `operating_costs_included` tinyint(1) DEFAULT 0,
  `facilities` text DEFAULT NULL,
  PRIMARY KEY (`object_id`),
  CONSTRAINT `rental_offices_ibfk_1` FOREIGN KEY (`object_id`) REFERENCES `rental_objects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rental_offices`
--

LOCK TABLES `rental_offices` WRITE;
set autocommit=0;
UNLOCK TABLES;
commit;

--
-- Table structure for table `rental_parking`
--

DROP TABLE IF EXISTS `rental_parking`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `rental_parking` (
  `object_id` char(36) NOT NULL,
  `parking_type` enum('Utendørs','Innendørs garasje','Carport') DEFAULT NULL,
  `sqm` int(11) DEFAULT NULL,
  `height_clearance_m` decimal(4,2) DEFAULT NULL,
  `vehicle_types` text DEFAULT NULL,
  `electric_charging` tinyint(1) DEFAULT 0,
  `charging_type` varchar(100) DEFAULT NULL,
  `security` varchar(255) DEFAULT NULL,
  `access_247` tinyint(1) DEFAULT 1,
  `covered` tinyint(1) DEFAULT 0,
  `heated` tinyint(1) DEFAULT 0,
  `distance_to_address` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`object_id`),
  CONSTRAINT `rental_parking_ibfk_1` FOREIGN KEY (`object_id`) REFERENCES `rental_objects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rental_parking`
--

LOCK TABLES `rental_parking` WRITE;
set autocommit=0;
UNLOCK TABLES;
commit;

--
-- Table structure for table `rental_residences`
--

DROP TABLE IF EXISTS `rental_residences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `rental_residences` (
  `object_id` char(36) NOT NULL,
  `sqm` int(11) DEFAULT NULL,
  `bedrooms` int(11) DEFAULT NULL,
  `bathrooms` int(11) DEFAULT NULL,
  `floor` int(11) DEFAULT NULL,
  `elevator` tinyint(1) DEFAULT 0,
  `parking_spaces` int(11) DEFAULT 0,
  `balcony` tinyint(1) DEFAULT 0,
  `furnished` enum('Møblert','Umøblert','Delvis møblert') DEFAULT NULL,
  `lease_type` enum('Langtidsleie','Korttidsleie','Begge') DEFAULT NULL,
  `min_lease_months` int(11) DEFAULT NULL,
  `pets_allowed` tinyint(1) DEFAULT 0,
  `energy_rating` varchar(10) DEFAULT NULL,
  `internet_included` tinyint(1) DEFAULT 0,
  `heating_type` varchar(100) DEFAULT NULL,
  `year_built` int(11) DEFAULT NULL,
  `last_renovated` int(11) DEFAULT NULL,
  PRIMARY KEY (`object_id`),
  CONSTRAINT `rental_residences_ibfk_1` FOREIGN KEY (`object_id`) REFERENCES `rental_objects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rental_residences`
--

LOCK TABLES `rental_residences` WRITE;
set autocommit=0;
UNLOCK TABLES;
commit;

--
-- Table structure for table `rental_rvs`
--

DROP TABLE IF EXISTS `rental_rvs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `rental_rvs` (
  `object_id` char(36) NOT NULL,
  `vehicle_type` enum('Bobil','Campingvogn','Leiebil med henger') DEFAULT NULL,
  `make` varchar(100) DEFAULT NULL,
  `model` varchar(100) DEFAULT NULL,
  `year` int(11) DEFAULT NULL,
  `license_plate` varchar(20) DEFAULT NULL,
  `length_m` decimal(4,2) DEFAULT NULL,
  `sleeping_capacity` int(11) DEFAULT NULL,
  `fixed_beds` int(11) DEFAULT NULL,
  `toilet` varchar(50) DEFAULT NULL,
  `shower` tinyint(1) DEFAULT 0,
  `kitchen` enum('Ingen','Enkel','Fullstendig') DEFAULT NULL,
  `heating` varchar(100) DEFAULT NULL,
  `air_conditioning` tinyint(1) DEFAULT 0,
  `awning` tinyint(1) DEFAULT 0,
  `bike_rack` tinyint(1) DEFAULT 0,
  `solar_panels` tinyint(1) DEFAULT 0,
  `km_included_per_day` int(11) DEFAULT NULL,
  `fuel_type` varchar(50) DEFAULT NULL,
  `transmission` enum('Manuell','Automat') DEFAULT NULL,
  `min_driver_age` int(11) DEFAULT 18,
  `driver_license_required` varchar(50) DEFAULT NULL,
  `license_years_required` int(11) DEFAULT 0,
  `additional_certificates` text DEFAULT NULL,
  PRIMARY KEY (`object_id`),
  CONSTRAINT `rental_rvs_ibfk_1` FOREIGN KEY (`object_id`) REFERENCES `rental_objects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rental_rvs`
--

LOCK TABLES `rental_rvs` WRITE;
set autocommit=0;
UNLOCK TABLES;
commit;

--
-- Table structure for table `rental_schedule`
--

DROP TABLE IF EXISTS `rental_schedule`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `rental_schedule` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `company_id` char(36) NOT NULL,
  `rental_object_id` char(36) NOT NULL,
  `customer_id` char(36) DEFAULT NULL,
  `customer_name` varchar(255) DEFAULT NULL,
  `customer_email` varchar(255) DEFAULT NULL,
  `customer_phone` varchar(50) DEFAULT NULL,
  `start_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_date` date NOT NULL,
  `end_time` time NOT NULL,
  `logistics` varchar(50) DEFAULT 'pick_up',
  `delivery_address` text DEFAULT NULL,
  `rental_fee` decimal(10,2) NOT NULL,
  `deposit_paid` decimal(10,2) DEFAULT 0.00,
  `status` varchar(50) DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` char(36) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_company` (`company_id`),
  KEY `idx_object` (`rental_object_id`),
  KEY `idx_customer` (`customer_id`),
  KEY `idx_dates` (`start_date`,`end_date`),
  KEY `idx_status` (`status`),
  CONSTRAINT `rental_schedule_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `rental_schedule_ibfk_2` FOREIGN KEY (`rental_object_id`) REFERENCES `rental_objects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `rental_schedule_ibfk_3` FOREIGN KEY (`customer_id`) REFERENCES `tenant_customers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rental_schedule`
--

LOCK TABLES `rental_schedule` WRITE;
set autocommit=0;
UNLOCK TABLES;
commit;

--
-- Table structure for table `rental_sports_equipment`
--

DROP TABLE IF EXISTS `rental_sports_equipment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `rental_sports_equipment` (
  `object_id` char(36) NOT NULL,
  `sport_type` varchar(100) DEFAULT NULL,
  `equipment_type` varchar(100) DEFAULT NULL,
  `brand` varchar(100) DEFAULT NULL,
  `model` varchar(100) DEFAULT NULL,
  `size` varchar(50) DEFAULT NULL,
  `skill_level` enum('Nybegynner','Viderekommen','Ekspert') DEFAULT NULL,
  `equipment_condition` enum('Ny','God','Slitt') DEFAULT NULL,
  `year` int(11) DEFAULT NULL,
  `includes` text DEFAULT NULL,
  `boots_available` tinyint(1) DEFAULT 0,
  `helmet_available` tinyint(1) DEFAULT 0,
  `insurance_recommended` tinyint(1) DEFAULT 0,
  `age_range` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`object_id`),
  CONSTRAINT `rental_sports_equipment_ibfk_1` FOREIGN KEY (`object_id`) REFERENCES `rental_objects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rental_sports_equipment`
--

LOCK TABLES `rental_sports_equipment` WRITE;
set autocommit=0;
UNLOCK TABLES;
commit;

--
-- Table structure for table `rental_tools`
--

DROP TABLE IF EXISTS `rental_tools`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `rental_tools` (
  `object_id` char(36) NOT NULL,
  `brand` varchar(100) DEFAULT NULL,
  `model` varchar(100) DEFAULT NULL,
  `year` int(11) DEFAULT NULL,
  `tool_condition` enum('Ny','Som ny','God','Slitt') DEFAULT NULL,
  `power_type` varchar(100) DEFAULT NULL,
  `weight_kg` decimal(6,2) DEFAULT NULL,
  `included_accessories` text DEFAULT NULL,
  `requires_training` tinyint(1) DEFAULT 0,
  `insurance_required` tinyint(1) DEFAULT 0,
  `fuel_type` varchar(50) DEFAULT NULL,
  `maintenance_status` varchar(255) DEFAULT NULL,
  `safety_equipment_included` tinyint(1) DEFAULT 0,
  `manual_url` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`object_id`),
  CONSTRAINT `rental_tools_ibfk_1` FOREIGN KEY (`object_id`) REFERENCES `rental_objects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rental_tools`
--

LOCK TABLES `rental_tools` WRITE;
set autocommit=0;
UNLOCK TABLES;
commit;

--
-- Table structure for table `rental_trailers`
--

DROP TABLE IF EXISTS `rental_trailers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `rental_trailers` (
  `object_id` char(36) NOT NULL,
  `trailer_type` enum('Åpen henger','Skaphenger','Båthenger','Maskinhenger','Tilhenger med presenning') DEFAULT NULL,
  `load_capacity_kg` int(11) DEFAULT NULL,
  `length_m` decimal(4,2) DEFAULT NULL,
  `width_m` decimal(4,2) DEFAULT NULL,
  `height_m` decimal(4,2) DEFAULT NULL,
  `coupling_type` varchar(100) DEFAULT NULL,
  `brakes` tinyint(1) DEFAULT 0,
  `license_plate` varchar(20) DEFAULT NULL,
  `year` int(11) DEFAULT NULL,
  `ramp_available` tinyint(1) DEFAULT 0,
  `lashing_points` int(11) DEFAULT NULL,
  `cover_included` tinyint(1) DEFAULT 0,
  `sides_foldable` tinyint(1) DEFAULT 0,
  `registration_required` tinyint(1) DEFAULT 1,
  `trailer_condition` enum('Ny','God','Slitt') DEFAULT NULL,
  `driver_license_class` varchar(50) DEFAULT NULL,
  `license_years_required` int(11) DEFAULT 0,
  `min_driver_age` int(11) DEFAULT 18,
  PRIMARY KEY (`object_id`),
  CONSTRAINT `rental_trailers_ibfk_1` FOREIGN KEY (`object_id`) REFERENCES `rental_objects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rental_trailers`
--

LOCK TABLES `rental_trailers` WRITE;
set autocommit=0;
UNLOCK TABLES;
commit;

--
-- Table structure for table `rental_vehicles`
--

DROP TABLE IF EXISTS `rental_vehicles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `rental_vehicles` (
  `object_id` char(36) NOT NULL,
  `make` varchar(100) DEFAULT NULL,
  `model` varchar(100) DEFAULT NULL,
  `year` int(11) DEFAULT NULL,
  `license_plate` varchar(20) DEFAULT NULL,
  `fuel_type` enum('Bensin','Diesel','Elektrisk','Hybrid') DEFAULT NULL,
  `transmission` enum('Manuell','Automat') DEFAULT NULL,
  `seats` int(11) DEFAULT NULL,
  `cargo_space_m3` decimal(6,2) DEFAULT NULL,
  `towing_capacity_kg` int(11) DEFAULT NULL,
  `mileage_km` int(11) DEFAULT NULL,
  `fuel_policy` varchar(100) DEFAULT NULL,
  `insurance_included` tinyint(1) DEFAULT 1,
  `km_included_per_day` int(11) DEFAULT NULL,
  `extra_km_cost` decimal(6,2) DEFAULT NULL,
  `min_driver_age` int(11) DEFAULT 18,
  `driver_license_class` varchar(50) DEFAULT NULL,
  `license_years_required` int(11) DEFAULT 0,
  `additional_certificates` text DEFAULT NULL,
  `winter_tires` tinyint(1) DEFAULT 0,
  `equipment` text DEFAULT NULL,
  PRIMARY KEY (`object_id`),
  CONSTRAINT `rental_vehicles_ibfk_1` FOREIGN KEY (`object_id`) REFERENCES `rental_objects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rental_vehicles`
--

LOCK TABLES `rental_vehicles` WRITE;
set autocommit=0;
UNLOCK TABLES;
commit;

--
-- Table structure for table `rental_venues`
--

DROP TABLE IF EXISTS `rental_venues`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `rental_venues` (
  `object_id` char(36) NOT NULL,
  `sqm` int(11) DEFAULT NULL,
  `capacity_seated` int(11) DEFAULT NULL,
  `capacity_standing` int(11) DEFAULT NULL,
  `kitchen_type` enum('Ingen','Tekjøkken','Fullstendig') DEFAULT NULL,
  `video_equipment` text DEFAULT NULL,
  `sound_systems` text DEFAULT NULL,
  `tables_chairs_included` tinyint(1) DEFAULT 0,
  `parking_spaces` int(11) DEFAULT 0,
  `accessibility` varchar(255) DEFAULT NULL,
  `alcohol_license` tinyint(1) DEFAULT 0,
  `catering_required` tinyint(1) DEFAULT 0,
  `price_per_hour` decimal(10,2) DEFAULT NULL,
  `min_hours` int(11) DEFAULT 0,
  `min_days` int(11) DEFAULT 1,
  `cleaning_included` tinyint(1) DEFAULT 0,
  `facilities` text DEFAULT NULL,
  PRIMARY KEY (`object_id`),
  CONSTRAINT `rental_venues_ibfk_1` FOREIGN KEY (`object_id`) REFERENCES `rental_objects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `rental_venues`
--

LOCK TABLES `rental_venues` WRITE;
set autocommit=0;
UNLOCK TABLES;
commit;

--
-- Table structure for table `reservation_service_bookings`
--

DROP TABLE IF EXISTS `reservation_service_bookings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `reservation_service_bookings` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `reservation_id` char(36) NOT NULL,
  `service_id` char(36) NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `reservation_id` (`reservation_id`,`service_id`),
  KEY `service_id` (`service_id`),
  CONSTRAINT `reservation_service_bookings_ibfk_1` FOREIGN KEY (`reservation_id`) REFERENCES `reservations_reservations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reservation_service_bookings_ibfk_2` FOREIGN KEY (`service_id`) REFERENCES `reservation_services` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reservation_service_bookings`
--

LOCK TABLES `reservation_service_bookings` WRITE;
set autocommit=0;
UNLOCK TABLES;
commit;

--
-- Table structure for table `reservation_services`
--

DROP TABLE IF EXISTS `reservation_services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `reservation_services` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `company_id` char(36) NOT NULL,
  `output_product_id` char(36) DEFAULT NULL,
  `service_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) DEFAULT 0.00,
  `duration_minutes` int(11) NOT NULL,
  `service_type` varchar(50) DEFAULT 'other',
  `available_from` time DEFAULT '09:00:00',
  `available_to` time DEFAULT '17:00:00',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `assigned_staff_mode` enum('single','multiple','all') DEFAULT NULL,
  `assigned_staff_ids` text DEFAULT NULL,
  `capacity` int(11) DEFAULT 1,
  `img_url` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `company_id` (`company_id`),
  KEY `reservation_services_ibfk_2` (`output_product_id`),
  CONSTRAINT `reservation_services_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reservation_services_ibfk_2` FOREIGN KEY (`output_product_id`) REFERENCES `output_products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reservation_services`
--

LOCK TABLES `reservation_services` WRITE;
set autocommit=0;
UNLOCK TABLES;
commit;

--
-- Table structure for table `reservations_confirmed`
--

DROP TABLE IF EXISTS `reservations_confirmed`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `reservations_confirmed` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `reservation_id` char(36) NOT NULL,
  `confirmed_by` char(36) NOT NULL,
  `confirmed_at` datetime DEFAULT current_timestamp(),
  `confirmation_method` varchar(20) DEFAULT 'online',
  `payment_status` varchar(20) DEFAULT 'pending',
  `amount_paid` decimal(10,2) DEFAULT 0.00,
  `payment_date` datetime DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_reference` varchar(255) DEFAULT NULL,
  `event_type` varchar(100) DEFAULT NULL,
  `schedule_notes` text DEFAULT NULL,
  `special_requirements` text DEFAULT NULL,
  `actual_check_in` datetime DEFAULT NULL,
  `actual_check_out` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `reservation_id` (`reservation_id`),
  KEY `confirmed_by` (`confirmed_by`),
  KEY `idx_reservations_confirmed_payment` (`payment_status`),
  CONSTRAINT `reservations_confirmed_ibfk_1` FOREIGN KEY (`reservation_id`) REFERENCES `reservations_reservations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reservations_confirmed_ibfk_2` FOREIGN KEY (`confirmed_by`) REFERENCES `tenant_admins` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reservations_confirmed`
--

LOCK TABLES `reservations_confirmed` WRITE;
set autocommit=0;
UNLOCK TABLES;
commit;

--
-- Table structure for table `reservations_reservations`
--

DROP TABLE IF EXISTS `reservations_reservations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `reservations_reservations` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `company_id` char(36) NOT NULL,
  `resource_id` char(36) NOT NULL,
  `customer_id` char(36) DEFAULT NULL,
  `staff_id` char(36) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `start_datetime` datetime NOT NULL,
  `end_datetime` datetime NOT NULL,
  `total_amount` decimal(10,2) DEFAULT 0.00,
  `deposit_amount` decimal(10,2) DEFAULT 0.00,
  `currency` varchar(3) DEFAULT 'NOK',
  `status` varchar(20) DEFAULT 'pending',
  `guest_count` int(11) DEFAULT 1,
  `is_recurring` tinyint(1) DEFAULT 0,
  `recurrence_pattern` varchar(20) DEFAULT NULL,
  `recurrence_end_date` date DEFAULT NULL,
  `created_by` char(36) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `resource_id` (`resource_id`),
  KEY `customer_id` (`customer_id`),
  KEY `staff_id` (`staff_id`),
  KEY `created_by` (`created_by`),
  KEY `idx_reservations_dates` (`company_id`,`start_datetime`,`end_datetime`),
  KEY `idx_reservations_status` (`status`),
  CONSTRAINT `reservations_reservations_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reservations_reservations_ibfk_2` FOREIGN KEY (`resource_id`) REFERENCES `reservations_resources` (`id`),
  CONSTRAINT `reservations_reservations_ibfk_3` FOREIGN KEY (`customer_id`) REFERENCES `tenant_customers` (`id`),
  CONSTRAINT `reservations_reservations_ibfk_4` FOREIGN KEY (`staff_id`) REFERENCES `tenant_staff` (`id`),
  CONSTRAINT `reservations_reservations_ibfk_5` FOREIGN KEY (`created_by`) REFERENCES `tenant_admins` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reservations_reservations`
--

LOCK TABLES `reservations_reservations` WRITE;
set autocommit=0;
UNLOCK TABLES;
commit;

--
-- Table structure for table `reservations_resources`
--

DROP TABLE IF EXISTS `reservations_resources`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `reservations_resources` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `company_id` char(36) NOT NULL,
  `resource_name` varchar(255) NOT NULL,
  `resource_type` varchar(20) DEFAULT 'room',
  `description` text DEFAULT NULL,
  `capacity` int(11) DEFAULT 1,
  `location` varchar(255) DEFAULT NULL,
  `hourly_rate` decimal(10,2) DEFAULT 0.00,
  `daily_rate` decimal(10,2) DEFAULT 0.00,
  `is_available` tinyint(1) DEFAULT 1,
  `features` text DEFAULT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `created_by` char(36) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `company_id` (`company_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `reservations_resources_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reservations_resources_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `tenant_admins` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reservations_resources`
--

LOCK TABLES `reservations_resources` WRITE;
set autocommit=0;
UNLOCK TABLES;
commit;

--
-- Table structure for table `saas_admins`
--

DROP TABLE IF EXISTS `saas_admins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `saas_admins` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `saas_staff_id` char(36) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` varchar(50) DEFAULT 'admin',
  `permissions` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `mfa_enabled` tinyint(1) DEFAULT 0,
  `mfa_secret` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `username` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `saas_staff_id` (`saas_staff_id`),
  CONSTRAINT `saas_admins_ibfk_1` FOREIGN KEY (`saas_staff_id`) REFERENCES `saas_staff` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `saas_admins`
--

LOCK TABLES `saas_admins` WRITE;
set autocommit=0;
INSERT INTO `saas_admins` VALUES ('4538cad6-b0e4-11f0-a1bb-b02628506e5c','453689f8-b0e4-11f0-a1bb-b02628506e5c','system.admin@salesmanno04.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','super_admin',NULL,1,NULL,0,NULL,'2025-10-24 16:18:10','2025-10-24 16:18:10',NULL),('ba464206-b8d8-11f0-ad30-b02628506e5c','d1522625-b8d7-11f0-ad30-b02628506e5c','magnekvalsvique@gmail.com','$2y$12$c3IAZ9yaJMz/RhgZDHlo6OLAEjdZGWzCrGE1g.D/My7BR0nqqb8ie','Ultra user','ALL',1,'2025-11-22 00:42:48',0,NULL,'2025-11-03 18:15:42','2025-11-22 00:42:48','salesMan1');
UNLOCK TABLES;
commit;

--
-- Table structure for table `saas_staff`
--

DROP TABLE IF EXISTS `saas_staff`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `saas_staff` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `job_title` varchar(100) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `employment_type` varchar(50) DEFAULT 'full_time',
  `hire_date` date DEFAULT NULL,
  `address_street` varchar(255) DEFAULT NULL,
  `address_city` varchar(255) DEFAULT NULL,
  `address_state` varchar(255) DEFAULT NULL,
  `address_zip` varchar(50) DEFAULT NULL,
  `address_country` varchar(100) DEFAULT NULL,
  `emergency_contact_name` varchar(255) DEFAULT NULL,
  `emergency_contact_phone` varchar(50) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `saas_staff`
--

LOCK TABLES `saas_staff` WRITE;
set autocommit=0;
INSERT INTO `saas_staff` VALUES ('453689f8-b0e4-11f0-a1bb-b02628506e5c','System','Administrator','system.admin@salesmanno04.com','+4712345678','System Admin','IT','full_time','2025-10-24',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'2025-10-24 16:18:10','2025-10-24 16:18:10'),('d1522625-b8d7-11f0-ad30-b02628506e5c','Magne','Kvalsvik','magne.k@salesman.no','40469364','SUPPORT','Salesman.no','full_time',NULL,'Anholen 13B','Ulsteinvik','Møre og Romsdal','6065','Norway','Sølvi Hjelvik','48361707',1,'2025-11-03 18:09:11','2025-11-03 18:09:11');
UNLOCK TABLES;
commit;

--
-- Table structure for table `saas_subscription_plans`
--

DROP TABLE IF EXISTS `saas_subscription_plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `saas_subscription_plans` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price_monthly` decimal(10,2) DEFAULT NULL,
  `price_annual` decimal(10,2) DEFAULT NULL,
  `max_users` int(11) DEFAULT NULL,
  `max_storage_mb` int(11) DEFAULT NULL,
  `features` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` char(36) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `saas_subscription_plans_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `saas_admins` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `saas_subscription_plans`
--

LOCK TABLES `saas_subscription_plans` WRITE;
set autocommit=0;
UNLOCK TABLES;
commit;

--
-- Table structure for table `saas_system_config`
--

DROP TABLE IF EXISTS `saas_system_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `saas_system_config` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `config_key` varchar(100) NOT NULL,
  `config_value` text DEFAULT NULL,
  `config_type` varchar(20) DEFAULT 'string',
  `description` text DEFAULT NULL,
  `is_sensitive` tinyint(1) DEFAULT 0,
  `updated_by` char(36) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `config_key` (`config_key`),
  KEY `updated_by` (`updated_by`),
  CONSTRAINT `saas_system_config_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `saas_admins` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `saas_system_config`
--

LOCK TABLES `saas_system_config` WRITE;
set autocommit=0;
INSERT INTO `saas_system_config` VALUES ('2ec70b77-b9a9-11f0-ad30-b02628506e5c','domeneshop_api_token','n3MOuieSzXOmPXmi','encrypted','Domeneshop API Token for DNS management',1,NULL,'2025-11-04 19:07:53','2025-11-04 19:08:33'),('2ec72547-b9a9-11f0-ad30-b02628506e5c','domeneshop_api_secret','OWsVSKWgNlSbyaceXgTYu6P73kXwmMCwT1ef5g7JyaOEQqch8t1heeGwZm73XRNu','encrypted','Domeneshop API Secret for DNS management',1,NULL,'2025-11-04 19:07:53','2025-11-04 19:08:33'),('2ec72715-b9a9-11f0-ad30-b02628506e5c','domeneshop_domain_id','2167680','string','Domeneshop Domain ID for salesman.no',0,NULL,'2025-11-04 19:07:53','2025-11-04 20:03:24'),('731f1d25-b9e5-11f0-ad30-b02628506e5c','production_server_ip','194.63.249.219','string','IP-adresse for www.salesman.no serveren',0,NULL,'2025-11-05 02:19:17','2025-11-05 02:19:17');
UNLOCK TABLES;
commit;

--
-- Table structure for table `sales_cart`
--

DROP TABLE IF EXISTS `sales_cart`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sales_cart` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_id` char(36) NOT NULL,
  `admin_id` char(36) NOT NULL,
  `output_product_id` char(36) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `added_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_company_admin` (`company_id`,`admin_id`),
  KEY `idx_output_product` (`output_product_id`),
  KEY `admin_id` (`admin_id`),
  CONSTRAINT `sales_cart_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`),
  CONSTRAINT `sales_cart_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `tenant_admins` (`id`),
  CONSTRAINT `sales_cart_ibfk_3` FOREIGN KEY (`output_product_id`) REFERENCES `output_products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=58 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales_cart`
--

LOCK TABLES `sales_cart` WRITE;
set autocommit=0;
INSERT INTO `sales_cart` VALUES (43,'7efbddea-b507-11f0-936b-b02628506e5c','7f072d6a-b507-11f0-936b-b02628506e5c','7a8cce56b077e4090279c0ac2e3dc5b5',3,'2025-11-01 08:36:36'),(44,'7efbddea-b507-11f0-936b-b02628506e5c','7f072d6a-b507-11f0-936b-b02628506e5c','8967bf21bc9615b78c3afb4bc019e193',4,'2025-11-01 15:07:25');
UNLOCK TABLES;
commit;

--
-- Table structure for table `sales_categories`
--

DROP TABLE IF EXISTS `sales_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sales_categories` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `company_id` char(36) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `parent_category_id` char(36) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_id` (`company_id`,`name`),
  KEY `parent_category_id` (`parent_category_id`),
  CONSTRAINT `sales_categories_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sales_categories_ibfk_2` FOREIGN KEY (`parent_category_id`) REFERENCES `sales_categories` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales_categories`
--

LOCK TABLES `sales_categories` WRITE;
set autocommit=0;
INSERT INTO `sales_categories` VALUES ('492d07e1-b7f2-11f0-a3af-b02628506e5c','e090c403-b7f1-11f0-a3af-b02628506e5c','Kaffe og varme drikker','Spesialkaffe, te og varme drikker',NULL,1,1,'2025-11-02 14:46:08'),('492d1503-b7f2-11f0-a3af-b02628506e5c','e090c403-b7f1-11f0-a3af-b02628506e5c','Kalde drikker','Juice, brus, vann og kalde drikker',NULL,2,1,'2025-11-02 14:46:08'),('492d1629-b7f2-11f0-a3af-b02628506e5c','e090c403-b7f1-11f0-a3af-b02628506e5c','Bakverk','Kaker, boller, croissanter og bakverk',NULL,3,1,'2025-11-02 14:46:08'),('492d16e5-b7f2-11f0-a3af-b02628506e5c','e090c403-b7f1-11f0-a3af-b02628506e5c','Lunsj','Smørbrød, salater og lunsjretter',NULL,4,1,'2025-11-02 14:46:08');
UNLOCK TABLES;
commit;

--
-- Table structure for table `sales_orders`
--

DROP TABLE IF EXISTS `sales_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sales_orders` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `company_id` char(36) NOT NULL,
  `customer_id` char(36) DEFAULT NULL,
  `member_id` char(36) DEFAULT NULL,
  `order_number` varchar(100) NOT NULL,
  `order_date` date NOT NULL,
  `status` varchar(50) DEFAULT 'draft',
  `payment_status` varchar(50) DEFAULT 'pending',
  `subtotal` decimal(15,2) NOT NULL,
  `tax_amount` decimal(15,2) DEFAULT 0.00,
  `discount_amount` decimal(15,2) DEFAULT 0.00,
  `membership_discount` decimal(15,2) DEFAULT 0.00,
  `total_amount` decimal(15,2) NOT NULL,
  `currency` varchar(3) DEFAULT 'NOK',
  `notes` text DEFAULT NULL,
  `shipping_address_street` varchar(255) DEFAULT NULL,
  `shipping_address_city` varchar(255) DEFAULT NULL,
  `shipping_address_state` varchar(255) DEFAULT NULL,
  `shipping_address_zip` varchar(50) DEFAULT NULL,
  `shipping_address_country` varchar(100) DEFAULT NULL,
  `created_by` char(36) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_id` (`company_id`,`order_number`),
  KEY `customer_id` (`customer_id`),
  KEY `member_id` (`member_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `sales_orders_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sales_orders_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `tenant_customers` (`id`),
  CONSTRAINT `sales_orders_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `tenant_admins` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales_orders`
--

LOCK TABLES `sales_orders` WRITE;
set autocommit=0;
UNLOCK TABLES;
commit;

--
-- Table structure for table `sales_orders_items`
--

DROP TABLE IF EXISTS `sales_orders_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sales_orders_items` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `order_id` char(36) NOT NULL,
  `product_id` char(36) DEFAULT NULL,
  `product_name` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(15,2) NOT NULL,
  `member_unit_price` decimal(15,2) DEFAULT NULL,
  `tax_rate` decimal(5,2) DEFAULT 0.00,
  `line_total` decimal(15,2) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `sales_orders_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `sales_orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sales_orders_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `sales_products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales_orders_items`
--

LOCK TABLES `sales_orders_items` WRITE;
set autocommit=0;
UNLOCK TABLES;
commit;

--
-- Table structure for table `sales_products`
--

DROP TABLE IF EXISTS `sales_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sales_products` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `company_id` char(36) NOT NULL,
  `category_id` char(36) DEFAULT NULL,
  `sku` varchar(100) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `barcode` varchar(100) DEFAULT NULL,
  `cost_price` decimal(15,2) DEFAULT 0.00,
  `selling_price` decimal(15,2) NOT NULL,
  `img_product` varchar(500) DEFAULT NULL,
  `currency` varchar(3) DEFAULT 'NOK',
  `tax_rate` decimal(5,2) DEFAULT 0.00,
  `item_taxable` tinyint(1) DEFAULT 1,
  `unit` varchar(50) DEFAULT 'pcs',
  `reorder_level` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `output_product_id` char(36) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `stock_quantity` int(11) DEFAULT 0,
  `low_stock_threeshold` int(11) DEFAULT 5,
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_id` (`company_id`,`sku`),
  KEY `category_id` (`category_id`),
  KEY `idx_output_product` (`output_product_id`),
  CONSTRAINT `fk_sales_products_output_product` FOREIGN KEY (`output_product_id`) REFERENCES `output_products` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `sales_products_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sales_products_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `sales_categories` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales_products`
--

LOCK TABLES `sales_products` WRITE;
set autocommit=0;
INSERT INTO `sales_products` VALUES ('16ad682f97547c48b3a2d78cb9aea61d5251','7efbddea-b507-11f0-936b-b02628506e5c',NULL,'PRD-1761772397-1747','Pølse med brød','Gode pølse','',5.00,40.00,'https://external-content.duckduckgo.com/iu/?u=https%3A%2F%2Ftse1.explicit.bing.net%2Fth%2Fid%2FOIP.OQ2IrOezxXtIgRDXKX6URQHaHa%3Fpid%3DApi&f=1&ipt=8c4a0a5c2500e2114cfbb551f011648cfd9a36fcc69fcf3a519ad7ddc545085c&ipo=images','NOK',25.00,1,'stk',1,1,'8967bf21bc9615b78c3afb4bc019e193','2025-10-29 22:13:17','2025-10-31 09:39:07',165,5),('24692482c3a89b75763a4408c338696369b4','7efbddea-b507-11f0-936b-b02628506e5c',NULL,'PRD-1762613472-8687','Kake','','',8.00,45.00,'','NOK',25.00,1,'stk',0,1,'c3b0a67c4303738886ff7a8c45a221d2','2025-11-08 15:51:12','2025-11-13 23:21:22',-1,5),('2f5d1e6bdd2ba279943b2d880e6c246bdfce','7efbddea-b507-11f0-936b-b02628506e5c',NULL,'PRD-1761771139-4583','Cola','','',15.00,40.00,'https://cdn.pji.nu/product/standard/280/499819.jpg','NOK',25.00,1,'stk',0,1,'7a8cce56b077e4090279c0ac2e3dc5b5','2025-10-29 21:52:19','2025-11-13 23:21:02',-3,5),('dedbc54d4e7cf46b1feb77e7a55d1f1b4c4a','7efbddea-b507-11f0-936b-b02628506e5c',NULL,'PRD-1762613469-3267','Kake','','',8.00,45.00,'','NOK',25.00,1,'stk',0,1,'0066f9392d98a322b3b6055cfb0d61ba','2025-11-08 15:51:10','2025-11-13 23:21:22',-1,5);
UNLOCK TABLES;
commit;

--
-- Table structure for table `sales_products_stock`
--

DROP TABLE IF EXISTS `sales_products_stock`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sales_products_stock` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `product_id` char(36) NOT NULL,
  `location` varchar(100) DEFAULT 'main',
  `quantity` int(11) NOT NULL DEFAULT 0,
  `reserved_quantity` int(11) DEFAULT 0,
  `last_restocked` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_id` (`product_id`,`location`),
  CONSTRAINT `sales_products_stock_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `sales_products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales_products_stock`
--

LOCK TABLES `sales_products_stock` WRITE;
set autocommit=0;
UNLOCK TABLES;
commit;

--
-- Table structure for table `sales_purchase_history`
--

DROP TABLE IF EXISTS `sales_purchase_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sales_purchase_history` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `company_id` char(36) NOT NULL,
  `product_id` char(36) DEFAULT NULL,
  `product_name` varchar(255) NOT NULL,
  `supplier` varchar(255) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `unit_cost` decimal(10,2) NOT NULL,
  `total_cost` decimal(10,2) NOT NULL,
  `purchase_date` datetime NOT NULL DEFAULT current_timestamp(),
  `created_by` char(36) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_company_id` (`company_id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales_purchase_history`
--

LOCK TABLES `sales_purchase_history` WRITE;
set autocommit=0;
UNLOCK TABLES;
commit;

--
-- Table structure for table `sales_transactions`
--

DROP TABLE IF EXISTS `sales_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sales_transactions` (
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
  `created_by` char(36) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_company` (`company_id`),
  KEY `idx_product` (`sales_product_id`),
  CONSTRAINT `sales_transactions_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`),
  CONSTRAINT `sales_transactions_ibfk_2` FOREIGN KEY (`sales_product_id`) REFERENCES `sales_products` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales_transactions`
--

LOCK TABLES `sales_transactions` WRITE;
set autocommit=0;
INSERT INTO `sales_transactions` VALUES (4,'7efbddea-b507-11f0-936b-b02628506e5c','2f5d1e6bdd2ba279943b2d880e6c246bdfce',1,40.00,'cash','Walk-in','atlekarl',0,'','7f0d55c4-b507-11f0-936b-b02628506e5c','2025-10-29 21:53:05'),(5,'7efbddea-b507-11f0-936b-b02628506e5c','16ad682f97547c48b3a2d78cb9aea61d5251',1,40.00,'cash','Walk-in',NULL,0,'','7f072d6a-b507-11f0-936b-b02628506e5c','2025-10-29 22:17:34'),(6,'7efbddea-b507-11f0-936b-b02628506e5c','16ad682f97547c48b3a2d78cb9aea61d5251',2,80.00,'cash','Walk-in',NULL,0,'','7f0d55c4-b507-11f0-936b-b02628506e5c','2025-10-29 22:17:59'),(7,'7efbddea-b507-11f0-936b-b02628506e5c','2f5d1e6bdd2ba279943b2d880e6c246bdfce',2,80.00,'cash','Walk-in',NULL,0,'','7f0d55c4-b507-11f0-936b-b02628506e5c','2025-10-29 22:17:59'),(8,'7efbddea-b507-11f0-936b-b02628506e5c','2f5d1e6bdd2ba279943b2d880e6c246bdfce',2,80.00,'card','Walk-in',NULL,0,'','7f0d55c4-b507-11f0-936b-b02628506e5c','2025-10-29 22:18:45'),(9,'7efbddea-b507-11f0-936b-b02628506e5c','16ad682f97547c48b3a2d78cb9aea61d5251',3,120.00,'card','Walk-in',NULL,0,'','7f0d55c4-b507-11f0-936b-b02628506e5c','2025-10-29 22:18:45'),(10,'7efbddea-b507-11f0-936b-b02628506e5c','16ad682f97547c48b3a2d78cb9aea61d5251',1,40.00,'cash','Walk-in',NULL,0,'','7f0d55c4-b507-11f0-936b-b02628506e5c','2025-10-29 22:20:13'),(11,'7efbddea-b507-11f0-936b-b02628506e5c','2f5d1e6bdd2ba279943b2d880e6c246bdfce',1,40.00,'cash','Walk-in',NULL,0,'','7f0d55c4-b507-11f0-936b-b02628506e5c','2025-10-29 22:20:13'),(12,'7efbddea-b507-11f0-936b-b02628506e5c','16ad682f97547c48b3a2d78cb9aea61d5251',3,120.00,'cash','Walk-in',NULL,0,'','7f0d55c4-b507-11f0-936b-b02628506e5c','2025-10-30 06:58:02'),(13,'7efbddea-b507-11f0-936b-b02628506e5c','16ad682f97547c48b3a2d78cb9aea61d5251',3,120.00,'cash','Walk-in',NULL,0,'','7f0d55c4-b507-11f0-936b-b02628506e5c','2025-10-30 09:38:23'),(14,'7efbddea-b507-11f0-936b-b02628506e5c','16ad682f97547c48b3a2d78cb9aea61d5251',1,40.00,'cash','Walk-in',NULL,0,'','7f0d55c4-b507-11f0-936b-b02628506e5c','2025-10-30 11:40:36'),(15,'7efbddea-b507-11f0-936b-b02628506e5c','16ad682f97547c48b3a2d78cb9aea61d5251',1,40.00,'cash','Walk-in',NULL,0,'','7f0d55c4-b507-11f0-936b-b02628506e5c','2025-10-31 09:39:07'),(24,'7efbddea-b507-11f0-936b-b02628506e5c','2f5d1e6bdd2ba279943b2d880e6c246bdfce',3,120.00,'cash','Walk-in',NULL,0,'','7f0d55c4-b507-11f0-936b-b02628506e5c','2025-11-13 23:21:02'),(25,'7efbddea-b507-11f0-936b-b02628506e5c','dedbc54d4e7cf46b1feb77e7a55d1f1b4c4a',1,45.00,'cash','Walk-in',NULL,0,'','7f0d55c4-b507-11f0-936b-b02628506e5c','2025-11-13 23:21:22'),(26,'7efbddea-b507-11f0-936b-b02628506e5c','24692482c3a89b75763a4408c338696369b4',1,45.00,'cash','Walk-in',NULL,0,'','7f0d55c4-b507-11f0-936b-b02628506e5c','2025-11-13 23:21:22');
UNLOCK TABLES;
commit;

--
-- Table structure for table `self_company`
--

DROP TABLE IF EXISTS `self_company`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `self_company` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `company_name` varchar(255) NOT NULL,
  `legal_name` varchar(255) DEFAULT NULL,
  `organization_no` varchar(100) DEFAULT NULL,
  `tax_id` varchar(100) DEFAULT NULL,
  `company_number` varchar(100) DEFAULT NULL,
  `contact_email` varchar(255) DEFAULT NULL,
  `contact_phone` varchar(50) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `logo_url` varchar(500) DEFAULT NULL,
  `legal_address_street` varchar(255) DEFAULT NULL,
  `legal_address_city` varchar(255) DEFAULT NULL,
  `legal_address_state` varchar(255) DEFAULT NULL,
  `legal_address_zip` varchar(50) DEFAULT NULL,
  `legal_address_country` varchar(100) DEFAULT 'Norway',
  `bank_name` varchar(255) DEFAULT NULL,
  `bank_account_number` varchar(100) DEFAULT NULL,
  `bank_swift_code` varchar(100) DEFAULT NULL,
  `invoice_prefix` varchar(50) DEFAULT 'INV',
  `invoice_start_number` int(11) DEFAULT 1000,
  `invoice_terms` text DEFAULT NULL,
  `invoice_due_days` int(11) DEFAULT 30,
  `default_tax_rate` decimal(5,2) DEFAULT 0.00,
  `tax_enabled` tinyint(1) DEFAULT 0,
  `default_currency` varchar(3) DEFAULT 'NOK',
  `is_active` tinyint(1) DEFAULT 1,
  `setup_completed` tinyint(1) DEFAULT 0,
  `created_by` char(36) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `self_company_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `saas_admins` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `self_company`
--

LOCK TABLES `self_company` WRITE;
set autocommit=0;
INSERT INTO `self_company` VALUES ('453afc7e-b0e4-11f0-a1bb-b02628506e5c','Salesman','Salesman','123456789',NULL,'1','admin@salesmanno04.com','+4712345678','https://salesmanno04.com','https://8upload.com/display/690aa0b5321ac/salesman-removebg-preview.png.php','Anholen 13B','Ulsteinvik','Møre og Romsdal','6065','Norway',NULL,NULL,NULL,'INV',1000,NULL,30,25.00,1,'NOK',1,1,'4538cad6-b0e4-11f0-a1bb-b02628506e5c','2025-10-24 16:18:10','2025-11-05 01:57:37'),('f9bb758a-b9e1-11f0-ad30-b02628506e5c','Din SAAS Bedrift AS','Din SAAS Bedrift AS',NULL,NULL,NULL,'admin@saas.no',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'Norway',NULL,NULL,NULL,'INV',1000,NULL,30,0.00,0,'NOK',1,0,'ba464206-b8d8-11f0-ad30-b02628506e5c','2025-11-05 01:54:25','2025-11-05 01:54:25');
UNLOCK TABLES;
commit;

--
-- Table structure for table `service_bookings`
--

DROP TABLE IF EXISTS `service_bookings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `service_bookings` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `company_id` char(36) NOT NULL,
  `service_id` int(11) DEFAULT NULL,
  `admin_id` char(36) NOT NULL,
  `service_name` varchar(255) NOT NULL,
  `service_description` text DEFAULT NULL,
  `selling_price` decimal(10,2) NOT NULL,
  `cost_price` decimal(10,2) DEFAULT NULL,
  `duration` int(11) NOT NULL,
  `date_from` date NOT NULL,
  `time_from` time NOT NULL,
  `date_to` date NOT NULL,
  `time_to` time NOT NULL,
  `assigned_staff_id` char(36) DEFAULT NULL,
  `customer_name` varchar(255) DEFAULT NULL,
  `customer_email` varchar(255) DEFAULT NULL,
  `customer_phone` varchar(50) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_status` varchar(50) DEFAULT 'pending',
  `status` varchar(50) DEFAULT 'available',
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `admin_id` (`admin_id`),
  KEY `idx_service_bookings_company` (`company_id`),
  KEY `idx_service_bookings_date` (`date_from`,`date_to`),
  KEY `idx_service_bookings_status` (`status`),
  KEY `idx_service_bookings_staff` (`assigned_staff_id`),
  CONSTRAINT `service_bookings_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `service_bookings_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `tenant_admins` (`id`) ON DELETE CASCADE,
  CONSTRAINT `service_bookings_ibfk_3` FOREIGN KEY (`assigned_staff_id`) REFERENCES `tenant_staff` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `service_bookings`
--

LOCK TABLES `service_bookings` WRITE;
set autocommit=0;
UNLOCK TABLES;
commit;

--
-- Table structure for table `system_modules`
--

DROP TABLE IF EXISTS `system_modules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `system_modules` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `module_key` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(50) DEFAULT 'business',
  `icon` varchar(100) DEFAULT NULL,
  `version` varchar(20) DEFAULT '1.0.0',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `module_key` (`module_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_modules`
--

LOCK TABLES `system_modules` WRITE;
set autocommit=0;
INSERT INTO `system_modules` VALUES ('15a3c857-b57f-11f0-a6d0-b02628506e5c','reservations','Reservasjoner','Reservasjonssystem','business','calendar-check','1.0.0',1,'2025-10-30 11:56:27','2025-10-30 11:56:27'),('7f3c499a-af1a-11f0-a914-b02628506e5c','sales','Sales','Point of sale and product management','business','shopping-cart','1.0.0',1,'2025-10-22 07:41:18','2025-10-25 13:46:20'),('7f3c4bb2-af1a-11f0-a914-b02628506e5c','economy','Economy','Financial management and accounting','business','dollar-sign','1.0.0',1,'2025-10-22 07:41:18','2025-10-25 13:46:20'),('7f3c4c76-af1a-11f0-a914-b02628506e5c','events','Events','Event management and ticketing','business','calendar','1.0.0',1,'2025-10-22 07:41:18','2025-10-25 13:46:20'),('7f3c4ceb-af1a-11f0-a914-b02628506e5c','reservation','Reservation','Service bookings and appointments','business','clock','1.0.0',1,'2025-10-22 07:41:18','2025-10-25 13:46:20'),('7f3c4d5f-af1a-11f0-a914-b02628506e5c','membership','Membership','Membership management','business','users','1.0.0',1,'2025-10-22 07:41:18','2025-10-25 13:46:21'),('7f3c4dcc-af1a-11f0-a914-b02628506e5c','rental','Rental','Equipment and object rentals','business','package','1.0.0',1,'2025-10-22 07:41:18','2025-10-25 13:46:21'),('7f3c4e39-af1a-11f0-a914-b02628506e5c','email','Email','Email campaigns and templates','communication','mail','1.0.0',1,'2025-10-22 07:41:18','2025-10-25 13:46:21'),('7f3c4e9e-af1a-11f0-a914-b02628506e5c','marketplace','Marketplace','Online marketplace listings','business','shopping-bag','1.0.0',1,'2025-10-22 07:41:18','2025-10-25 13:46:21'),('7f3c4f0b-af1a-11f0-a914-b02628506e5c','website','Website','Company website management','marketing','globe','1.0.0',1,'2025-10-22 07:41:18','2025-10-25 13:46:21'),('f9d42479-b182-11f0-b9ad-b02628506e5c','ditt_nettsted','Ditt Nettsted','Administrer firmaets nettside og velg mal','business','bi-globe','1.0.0',1,'2025-10-25 11:14:14','2025-10-25 11:14:14');
UNLOCK TABLES;
commit;

--
-- Table structure for table `tenant_admin_modules`
--

DROP TABLE IF EXISTS `tenant_admin_modules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tenant_admin_modules` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `tenant_admin_id` char(36) NOT NULL,
  `module_id` char(36) NOT NULL,
  `granted_by` char(36) NOT NULL,
  `granted_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `tenant_admin_id` (`tenant_admin_id`,`module_id`),
  KEY `module_id` (`module_id`),
  KEY `idx_tenant_admin_modules_granted_by` (`granted_by`),
  CONSTRAINT `tenant_admin_modules_ibfk_1` FOREIGN KEY (`tenant_admin_id`) REFERENCES `tenant_admins` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tenant_admin_modules_ibfk_2` FOREIGN KEY (`module_id`) REFERENCES `system_modules` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tenant_admin_modules_ibfk_3` FOREIGN KEY (`granted_by`) REFERENCES `saas_admins` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tenant_admin_modules`
--

LOCK TABLES `tenant_admin_modules` WRITE;
set autocommit=0;
INSERT INTO `tenant_admin_modules` VALUES ('14a83530-c0a6-11f0-8af7-b02628506e5c','580850091a22fb4dbe9023b759222b305282','7f3c499a-af1a-11f0-a914-b02628506e5c','ba464206-b8d8-11f0-ad30-b02628506e5c','2025-11-13 16:33:19'),('14a83dcc-c0a6-11f0-8af7-b02628506e5c','580850091a22fb4dbe9023b759222b305282','7f3c4bb2-af1a-11f0-a914-b02628506e5c','ba464206-b8d8-11f0-ad30-b02628506e5c','2025-11-13 16:33:19'),('14a845c3-c0a6-11f0-8af7-b02628506e5c','580850091a22fb4dbe9023b759222b305282','7f3c4c76-af1a-11f0-a914-b02628506e5c','ba464206-b8d8-11f0-ad30-b02628506e5c','2025-11-13 16:33:19'),('14a84cf1-c0a6-11f0-8af7-b02628506e5c','580850091a22fb4dbe9023b759222b305282','7f3c4ceb-af1a-11f0-a914-b02628506e5c','ba464206-b8d8-11f0-ad30-b02628506e5c','2025-11-13 16:33:19'),('14a8535f-c0a6-11f0-8af7-b02628506e5c','580850091a22fb4dbe9023b759222b305282','7f3c4d5f-af1a-11f0-a914-b02628506e5c','ba464206-b8d8-11f0-ad30-b02628506e5c','2025-11-13 16:33:19'),('14a87190-c0a6-11f0-8af7-b02628506e5c','580850091a22fb4dbe9023b759222b305282','7f3c4dcc-af1a-11f0-a914-b02628506e5c','ba464206-b8d8-11f0-ad30-b02628506e5c','2025-11-13 16:33:19'),('14a87cdc-c0a6-11f0-8af7-b02628506e5c','580850091a22fb4dbe9023b759222b305282','7f3c4e39-af1a-11f0-a914-b02628506e5c','ba464206-b8d8-11f0-ad30-b02628506e5c','2025-11-13 16:33:19'),('14a884a0-c0a6-11f0-8af7-b02628506e5c','580850091a22fb4dbe9023b759222b305282','7f3c4e9e-af1a-11f0-a914-b02628506e5c','ba464206-b8d8-11f0-ad30-b02628506e5c','2025-11-13 16:33:19'),('14a88f70-c0a6-11f0-8af7-b02628506e5c','580850091a22fb4dbe9023b759222b305282','f9d42479-b182-11f0-b9ad-b02628506e5c','ba464206-b8d8-11f0-ad30-b02628506e5c','2025-11-13 16:33:19'),('95424986-c705-11f0-a7b4-b02628506e5c','7f0d55c4-b507-11f0-936b-b02628506e5c','7f3c4bb2-af1a-11f0-a914-b02628506e5c','ba464206-b8d8-11f0-ad30-b02628506e5c','2025-11-21 19:12:04'),('9542561b-c705-11f0-a7b4-b02628506e5c','7f0d55c4-b507-11f0-936b-b02628506e5c','7f3c4c76-af1a-11f0-a914-b02628506e5c','ba464206-b8d8-11f0-ad30-b02628506e5c','2025-11-21 19:12:04'),('9542603b-c705-11f0-a7b4-b02628506e5c','7f0d55c4-b507-11f0-936b-b02628506e5c','7f3c4d5f-af1a-11f0-a914-b02628506e5c','ba464206-b8d8-11f0-ad30-b02628506e5c','2025-11-21 19:12:04'),('ae77d337-c705-11f0-a7b4-b02628506e5c','7f072d6a-b507-11f0-936b-b02628506e5c','7f3c4bb2-af1a-11f0-a914-b02628506e5c','ba464206-b8d8-11f0-ad30-b02628506e5c','2025-11-21 19:12:46'),('ae77e2a7-c705-11f0-a7b4-b02628506e5c','7f072d6a-b507-11f0-936b-b02628506e5c','7f3c4c76-af1a-11f0-a914-b02628506e5c','ba464206-b8d8-11f0-ad30-b02628506e5c','2025-11-21 19:12:46'),('ae77ed22-c705-11f0-a7b4-b02628506e5c','7f072d6a-b507-11f0-936b-b02628506e5c','7f3c4d5f-af1a-11f0-a914-b02628506e5c','ba464206-b8d8-11f0-ad30-b02628506e5c','2025-11-21 19:12:46'),('e1245123-c733-11f0-a7b4-b02628506e5c','f996adb8d578c9f15859cc9ffb78b023792b','7f3c4bb2-af1a-11f0-a914-b02628506e5c','ba464206-b8d8-11f0-ad30-b02628506e5c','2025-11-22 00:43:28'),('e1245908-c733-11f0-a7b4-b02628506e5c','f996adb8d578c9f15859cc9ffb78b023792b','7f3c4c76-af1a-11f0-a914-b02628506e5c','ba464206-b8d8-11f0-ad30-b02628506e5c','2025-11-22 00:43:28'),('e1245f53-c733-11f0-a7b4-b02628506e5c','f996adb8d578c9f15859cc9ffb78b023792b','7f3c4d5f-af1a-11f0-a914-b02628506e5c','ba464206-b8d8-11f0-ad30-b02628506e5c','2025-11-22 00:43:28');
UNLOCK TABLES;
commit;

--
-- Table structure for table `tenant_admin_sessions`
--

DROP TABLE IF EXISTS `tenant_admin_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tenant_admin_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_admin_id` char(36) NOT NULL,
  `session_id` varchar(100) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `login_at` datetime DEFAULT current_timestamp(),
  `logout_at` datetime DEFAULT NULL,
  `last_activity` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `session_id` (`session_id`),
  KEY `tenant_admin_id` (`tenant_admin_id`),
  CONSTRAINT `tenant_admin_sessions_ibfk_1` FOREIGN KEY (`tenant_admin_id`) REFERENCES `tenant_admins` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=238 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tenant_admin_sessions`
--

LOCK TABLES `tenant_admin_sessions` WRITE;
set autocommit=0;
INSERT INTO `tenant_admin_sessions` VALUES (42,'7f072d6a-b507-11f0-936b-b02628506e5c','89cae0c5db68a4c0c7aa77a16339191f','172.31.72.226','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Replit/1.0.14 Chrome/124.0.6367.119 Electron/30.0.3 Safari/537.36','2025-10-29 21:42:30','2025-10-29 21:42:59','2025-10-29 21:42:59',0,'2025-10-29 21:42:30'),(43,'7f0d55c4-b507-11f0-936b-b02628506e5c','9d411ff5a63aa211a27327c2e65dc746','172.31.72.226','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Replit/1.0.14 Chrome/124.0.6367.119 Electron/30.0.3 Safari/537.36','2025-10-29 21:44:04','2025-10-29 21:44:56','2025-10-29 21:44:56',0,'2025-10-29 21:44:04'),(44,'7f0d55c4-b507-11f0-936b-b02628506e5c','3bb2fd7e89fb56ea46d3ed4373487262','172.31.72.226','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Replit/1.0.14 Chrome/124.0.6367.119 Electron/30.0.3 Safari/537.36','2025-10-29 21:46:01','2025-10-29 21:49:02','2025-10-29 21:49:02',0,'2025-10-29 21:46:01'),(45,'7f0d55c4-b507-11f0-936b-b02628506e5c','64cbc142155264383e43c476dfd93494','147.12.38.160','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36','2025-10-29 21:49:02','2025-10-29 21:58:12','2025-10-29 21:58:12',0,'2025-10-29 21:49:02'),(46,'7f072d6a-b507-11f0-936b-b02628506e5c','9509193371ca6cdfc21f9519c5f5d786','147.12.38.160','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36','2025-10-29 21:59:12','2025-10-29 22:00:03','2025-10-29 22:00:03',0,'2025-10-29 21:59:12'),(47,'7f0d55c4-b507-11f0-936b-b02628506e5c','dbc1de22b3dce112b94103ee30bccf2c','147.12.38.160','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36','2025-10-29 22:00:08','2025-10-29 22:00:10','2025-10-29 22:00:10',0,'2025-10-29 22:00:08'),(48,'7f0d55c4-b507-11f0-936b-b02628506e5c','ba0b53d22c059bba310f8b02c72dad66','147.12.38.160','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36','2025-10-29 22:00:30','2025-10-29 22:00:36','2025-10-29 22:00:36',0,'2025-10-29 22:00:30'),(49,'7f0d55c4-b507-11f0-936b-b02628506e5c','399eee371cc860487995d5b2f94ea214','147.12.38.160','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36','2025-10-29 22:00:58','2025-10-29 22:01:00','2025-10-29 22:01:00',0,'2025-10-29 22:00:58'),(50,'7f0d55c4-b507-11f0-936b-b02628506e5c','4441a6d084965546083d11d7df47fc96','45.14.193.84','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0','2025-10-29 22:01:33','2025-10-29 22:01:33','2025-10-29 22:01:33',0,'2025-10-29 22:01:33'),(51,'7f0d55c4-b507-11f0-936b-b02628506e5c','e160cff552bb1218cd783ee674b5dbe5','45.14.193.84','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0','2025-10-29 22:01:33','2025-10-29 22:01:34','2025-10-29 22:01:34',0,'2025-10-29 22:01:33'),(52,'7f072d6a-b507-11f0-936b-b02628506e5c','c732c6f711ba1f1bbfacd869b5dd3696','147.12.38.160','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36','2025-10-29 22:01:33','2025-10-29 22:01:33','2025-10-29 22:01:33',0,'2025-10-29 22:01:33'),(53,'7f072d6a-b507-11f0-936b-b02628506e5c','2b8200ca3c2d9906ec018bf5f2a90fec','147.12.38.160','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36','2025-10-29 22:01:33','2025-10-29 22:01:34','2025-10-29 22:01:34',0,'2025-10-29 22:01:33'),(54,'7f0d55c4-b507-11f0-936b-b02628506e5c','1a9dd86bae99dd479764b11140a69d72','45.14.193.84','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0','2025-10-29 22:01:34','2025-10-29 22:01:34','2025-10-29 22:01:34',0,'2025-10-29 22:01:34'),(55,'7f0d55c4-b507-11f0-936b-b02628506e5c','565a54dec87ff889bf87d9cccad54ca7','45.14.193.84','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0','2025-10-29 22:01:34','2025-10-29 22:43:22','2025-10-29 22:43:22',0,'2025-10-29 22:01:34'),(56,'7f072d6a-b507-11f0-936b-b02628506e5c','eb008bedb3d8a7db9621f8b426855d0a','147.12.38.160','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36','2025-10-29 22:01:34','2025-10-29 22:50:39','2025-10-29 22:50:39',0,'2025-10-29 22:01:34'),(58,'7f0d55c4-b507-11f0-936b-b02628506e5c','ffef0d78d38fad4ddc130756dd50c91b','147.12.38.160','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1','2025-10-29 22:46:44','2025-10-29 22:47:15','2025-10-29 22:47:15',0,'2025-10-29 22:46:44'),(60,'7f0d55c4-b507-11f0-936b-b02628506e5c','f699412ae14533d235755c2d62bab8fb','172.31.69.226','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 Replit-Bonsai/2.155.0 (iOS 18.5)','2025-10-30 00:15:06','2025-10-30 07:41:02','2025-10-30 07:41:02',0,'2025-10-30 00:15:06'),(61,'7f0d55c4-b507-11f0-936b-b02628506e5c','4e8736ccaa98b3eb6074715fffb245f1','172.31.90.194','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 Replit-Bonsai/2.155.0 (iOS 18.5)','2025-10-30 07:41:02','2025-10-30 08:26:33','2025-10-30 08:26:33',0,'2025-10-30 07:41:02'),(62,'7f0d55c4-b507-11f0-936b-b02628506e5c','cc4dabf33cf73a3c27d7d9ae7a568690','172.31.91.194','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Replit/1.0.14 Chrome/124.0.6367.119 Electron/30.0.3 Safari/537.36','2025-10-30 08:26:33','2025-10-30 08:26:35','2025-10-30 08:26:35',0,'2025-10-30 08:26:33'),(63,'7f0d55c4-b507-11f0-936b-b02628506e5c','887d54e9fd767cf71a28c68d486c98c9','172.31.91.194','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Replit/1.0.14 Chrome/124.0.6367.119 Electron/30.0.3 Safari/537.36','2025-10-30 08:26:35','2025-10-30 10:26:58','2025-10-30 10:26:58',0,'2025-10-30 08:26:35'),(64,'7f0d55c4-b507-11f0-936b-b02628506e5c','1d4219200cec676871c2c20851c00b88','172.31.91.194','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Replit/1.0.14 Chrome/124.0.6367.119 Electron/30.0.3 Safari/537.36','2025-10-30 10:26:59','2025-10-30 10:58:30','2025-10-30 10:58:30',0,'2025-10-30 10:26:59'),(68,'7f0d55c4-b507-11f0-936b-b02628506e5c','32b88ccac13f8113cfece3c93f54e466','172.31.91.194','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Replit/1.0.14 Chrome/124.0.6367.119 Electron/30.0.3 Safari/537.36','2025-10-30 11:18:47','2025-10-30 11:39:32','2025-10-30 11:39:32',0,'2025-10-30 11:18:47'),(69,'7f0d55c4-b507-11f0-936b-b02628506e5c','abd3a5e4bceca9bb3f267fc734d44c4a','147.12.38.160','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-10-30 11:39:32','2025-10-30 11:50:16','2025-10-30 11:50:16',0,'2025-10-30 11:39:32'),(70,'7f0d55c4-b507-11f0-936b-b02628506e5c','6334c75576aab543ebcaad71d4b3f499','172.31.91.194','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Replit/1.0.14 Chrome/124.0.6367.119 Electron/30.0.3 Safari/537.36','2025-10-30 11:50:17','2025-10-30 12:32:44','2025-10-30 12:32:44',0,'2025-10-30 11:50:17'),(73,'7f0d55c4-b507-11f0-936b-b02628506e5c','fcdeed363da5260a36a613170bc2ee9e','89.8.4.242','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1','2025-10-30 12:32:44','2025-10-30 15:52:12','2025-10-30 15:52:12',0,'2025-10-30 12:32:44'),(74,'7f0d55c4-b507-11f0-936b-b02628506e5c','6250376629fd34ad35c0c3a53e39b277','89.8.4.242','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1','2025-10-30 15:52:12','2025-10-30 18:01:39','2025-10-30 18:01:39',0,'2025-10-30 15:52:12'),(76,'7f0d55c4-b507-11f0-936b-b02628506e5c','1d2d4277485edf836f77597079bdbd83','147.12.38.160','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36','2025-10-31 09:35:10','2025-10-31 10:24:35','2025-10-31 10:24:35',0,'2025-10-31 09:35:10'),(78,'7f0d55c4-b507-11f0-936b-b02628506e5c','d12ba9f7c0b5675fd7c0bbbe4a1f8a8e','89.8.4.107','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1','2025-10-31 10:24:35','2025-10-31 12:40:33','2025-10-31 12:40:33',0,'2025-10-31 10:24:35'),(80,'7f0d55c4-b507-11f0-936b-b02628506e5c','8ba2807d2aa4153a00301d20ab7d56dd','172.31.115.162','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 Replit-Bonsai/2.155.0 (iOS 18.5)','2025-11-01 06:39:17','2025-11-01 07:36:44','2025-11-01 07:36:44',0,'2025-11-01 06:39:17'),(83,'7f0d55c4-b507-11f0-936b-b02628506e5c','3a52266f7a3e70a05bb32b0b305e41c1','147.12.38.160','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1','2025-11-01 07:58:58','2025-11-01 08:39:45','2025-11-01 08:39:45',0,'2025-11-01 07:58:58'),(84,'7f0d55c4-b507-11f0-936b-b02628506e5c','98e388c34b5bd0713303d5d69482c543','147.12.38.160','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1','2025-11-01 08:39:45','2025-11-01 09:18:34','2025-11-01 09:18:34',0,'2025-11-01 08:39:45'),(85,'7f0d55c4-b507-11f0-936b-b02628506e5c','d4c50d4489af1860d8b6fb880f27f673','147.12.38.160','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1','2025-11-01 09:18:34','2025-11-01 09:54:53','2025-11-01 09:54:53',0,'2025-11-01 09:18:34'),(86,'7f072d6a-b507-11f0-936b-b02628506e5c','56b1eda3965af4e4b4f866a622fe77a2','172.31.68.162','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 Replit-Bonsai/2.155.0 (iOS 18.5)','2025-11-01 09:27:05','2025-11-01 10:17:41','2025-11-01 10:17:41',0,'2025-11-01 09:27:05'),(88,'7f0d55c4-b507-11f0-936b-b02628506e5c','257b5804f1aa8acaa91094ea28a37236','147.12.38.160','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1','2025-11-01 09:54:53','2025-11-01 10:39:57','2025-11-01 10:39:57',0,'2025-11-01 09:54:53'),(89,'7f072d6a-b507-11f0-936b-b02628506e5c','5cec30d623640388d369770c295daa05','89.8.4.107','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1','2025-11-01 10:17:41','2025-11-01 11:07:55','2025-11-01 11:07:55',0,'2025-11-01 10:17:41'),(90,'7f0d55c4-b507-11f0-936b-b02628506e5c','5ce31cece794f8c681c53c73c460134c','89.8.4.107','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1','2025-11-01 10:39:57','2025-11-01 13:26:11','2025-11-01 13:26:11',0,'2025-11-01 10:39:57'),(92,'7f0d55c4-b507-11f0-936b-b02628506e5c','13064b1a7268964b2e6876e36424eed2','89.8.4.107','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1','2025-11-01 13:26:11','2025-11-01 14:17:53','2025-11-01 14:17:53',0,'2025-11-01 13:26:11'),(93,'7f0d55c4-b507-11f0-936b-b02628506e5c','d2647326b3703d3522cc97e451f5ea25','89.8.4.107','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1','2025-11-01 14:17:53','2025-11-01 15:56:35','2025-11-01 15:56:35',0,'2025-11-01 14:17:53'),(95,'7f072d6a-b507-11f0-936b-b02628506e5c','13f852e7316c1d5263e1bb2d158c9ca1','45.88.221.253','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0','2025-11-01 15:56:35','2025-11-01 16:08:56','2025-11-01 16:08:56',0,'2025-11-01 15:56:35'),(96,'7f0d55c4-b507-11f0-936b-b02628506e5c','80c4685f4a6dae223aed457709637798','89.8.4.107','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1','2025-11-01 16:06:58','2025-11-01 19:01:02','2025-11-01 19:01:02',0,'2025-11-01 16:06:58'),(97,'7f0d55c4-b507-11f0-936b-b02628506e5c','7ddc39baa09c5c25d770a09de04ba733','147.12.38.160','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1','2025-11-01 19:01:02','2025-11-01 22:48:49','2025-11-01 22:48:49',0,'2025-11-01 19:01:02'),(98,'7f072d6a-b507-11f0-936b-b02628506e5c','bdff4cb10dc635c532b7d13d67375040','147.12.38.160','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1','2025-11-01 20:15:32','2025-11-01 22:48:49','2025-11-01 22:48:49',0,'2025-11-01 20:15:32'),(99,'7f0d55c4-b507-11f0-936b-b02628506e5c','d1fb67ec39c90415659d82378d4a6f62','147.12.38.160','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36','2025-11-01 22:49:14','2025-11-02 00:20:32','2025-11-02 00:20:32',0,'2025-11-01 22:49:14'),(100,'7f0d55c4-b507-11f0-936b-b02628506e5c','16301118a5ef6cfbb0541b7d425efb32','147.12.38.160','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1','2025-11-02 00:20:32','2025-11-02 00:27:46','2025-11-02 00:27:46',0,'2025-11-02 00:20:32'),(102,'7f0d55c4-b507-11f0-936b-b02628506e5c','fb058870e94dcb071910721cc4708d32','147.12.38.160','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1','2025-11-02 00:30:35','2025-11-02 08:05:54','2025-11-02 08:05:54',0,'2025-11-02 00:30:35'),(103,'7f0d55c4-b507-11f0-936b-b02628506e5c','c65764e76504d7758ccaa484f2de6d02','147.12.38.160','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1','2025-11-02 08:05:54','2025-11-02 09:14:41','2025-11-02 09:14:41',0,'2025-11-02 08:05:54'),(106,'7f0d55c4-b507-11f0-936b-b02628506e5c','23bc00b0725dd2863e062634ade41250','147.12.38.160','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1','2025-11-02 09:29:52','2025-11-02 09:31:27','2025-11-02 09:31:27',0,'2025-11-02 09:29:52'),(107,'7f0d55c4-b507-11f0-936b-b02628506e5c','1c3c7448c371ee15f4fb53768f1f18aa','147.12.38.160','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36','2025-11-02 14:10:42','2025-11-02 15:16:47','2025-11-02 15:16:47',0,'2025-11-02 14:10:42'),(112,'7f0d55c4-b507-11f0-936b-b02628506e5c','ba0485ac5198877ddab03155186ae03e','147.12.38.160','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1','2025-11-02 22:52:23','2025-11-02 23:39:47','2025-11-02 23:39:47',0,'2025-11-02 22:52:23'),(121,'7f0d55c4-b507-11f0-936b-b02628506e5c','f4e499179d1e7fa9c701ef7b4f083710','89.8.5.185','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1','2025-11-03 15:35:06','2025-11-03 17:47:10','2025-11-03 17:47:10',0,'2025-11-03 15:35:06'),(126,'7f0d55c4-b507-11f0-936b-b02628506e5c','c9b5d56ed026669d56faa36223420f9c','147.12.38.160','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1','2025-11-04 05:36:25','2025-11-04 07:54:13','2025-11-04 07:54:13',0,'2025-11-04 05:36:25'),(127,'7f072d6a-b507-11f0-936b-b02628506e5c','c710e82c19263ba43e1b3389865841cf','147.12.38.160','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1','2025-11-04 05:53:15','2025-11-04 07:54:13','2025-11-04 07:54:13',0,'2025-11-04 05:53:15'),(128,'7f0d55c4-b507-11f0-936b-b02628506e5c','bef8814251fa85b505ae3b468594fa2c','147.12.38.160','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1','2025-11-04 07:54:13','2025-11-04 08:27:31','2025-11-04 08:27:31',0,'2025-11-04 07:54:13'),(129,'7f072d6a-b507-11f0-936b-b02628506e5c','bb7a2d46f833dabcb6dc046e81fbeeca','147.12.38.160','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36','2025-11-04 08:24:58','2025-11-04 08:27:45','2025-11-04 08:27:45',0,'2025-11-04 08:24:58'),(130,'7f0d55c4-b507-11f0-936b-b02628506e5c','115417dfe4042d0ced561d584c7c57b8','147.12.38.160','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36','2025-11-04 08:27:49','2025-11-04 08:32:03','2025-11-04 08:32:03',0,'2025-11-04 08:27:49'),(131,'7f0d55c4-b507-11f0-936b-b02628506e5c','bdb2c9204d215abe3e7eccdbb8b2efdd','147.12.38.160','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36','2025-11-04 08:32:06','2025-11-04 09:09:31','2025-11-04 09:09:31',0,'2025-11-04 08:32:06'),(132,'7f072d6a-b507-11f0-936b-b02628506e5c','49abebbbc5fba4c9e0bb73d84916b17a','147.12.38.160','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36','2025-11-04 09:04:23','2025-11-04 10:06:45','2025-11-04 10:06:45',0,'2025-11-04 09:04:23'),(133,'7f0d55c4-b507-11f0-936b-b02628506e5c','207b1ca36f07c3e9014dd8a34a10755e','147.12.38.160','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36','2025-11-04 09:09:35','2025-11-04 09:12:24','2025-11-04 09:12:24',0,'2025-11-04 09:09:35'),(134,'7f0d55c4-b507-11f0-936b-b02628506e5c','f7f25dc31efcb3da69c3b89f527ebcbc','147.12.38.160','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36','2025-11-04 09:12:27','2025-11-04 10:06:45','2025-11-04 10:06:45',0,'2025-11-04 09:12:27'),(135,'7f0d55c4-b507-11f0-936b-b02628506e5c','1124a38204db4da9fa7e1b02dc1b9384','62.92.137.147','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1','2025-11-04 10:06:45','2025-11-04 12:35:37','2025-11-04 12:35:37',0,'2025-11-04 10:06:45'),(136,'7f0d55c4-b507-11f0-936b-b02628506e5c','87f1db32b72773a5bb8fb37fece4fe50','62.92.137.147','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1','2025-11-04 12:35:37','2025-11-04 14:01:45','2025-11-04 14:01:45',0,'2025-11-04 12:35:37'),(138,'7f0d55c4-b507-11f0-936b-b02628506e5c','08e4737137582a0f7bebb99fdd682ffd','147.12.38.160','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36','2025-11-04 16:48:52','2025-11-04 19:42:09','2025-11-04 19:42:09',0,'2025-11-04 16:48:52'),(139,'7f0d55c4-b507-11f0-936b-b02628506e5c','ef5b5cb09aecee842a9ace79569c2b76','147.12.38.160','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36','2025-11-04 19:42:09','2025-11-05 18:57:32','2025-11-05 18:57:32',0,'2025-11-04 19:42:09'),(141,'7f0d55c4-b507-11f0-936b-b02628506e5c','5530efafdf47288df82df7804758ad3a','147.12.38.160','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36','2025-11-05 22:20:40','2025-11-05 22:32:28','2025-11-05 22:32:28',0,'2025-11-05 22:20:40'),(144,'7f0d55c4-b507-11f0-936b-b02628506e5c','89614ecb76537e4b34a78e5c0803ca48','147.12.38.160','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36','2025-11-05 22:47:52','2025-11-05 22:48:33','2025-11-05 22:48:33',0,'2025-11-05 22:47:52'),(145,'7f0d55c4-b507-11f0-936b-b02628506e5c','3d9c3e8325dfe2e8e9a737778e7ba570','172.31.106.194','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Replit/1.0.14 Chrome/124.0.6367.119 Electron/30.0.3 Safari/537.36','2025-11-05 22:48:42','2025-11-06 10:38:21','2025-11-06 10:38:21',0,'2025-11-05 22:48:42'),(146,'7f0d55c4-b507-11f0-936b-b02628506e5c','3a8dd009449af5276d2577773211fc22','147.12.38.160','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36','2025-11-06 10:38:21','2025-11-07 09:56:21','2025-11-07 09:56:21',0,'2025-11-06 10:38:21'),(151,'7f0d55c4-b507-11f0-936b-b02628506e5c','6153542bee92e256b2e64231bf3d6a74','147.12.38.160','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1','2025-11-07 18:18:50','2025-11-07 19:02:42','2025-11-07 19:02:42',0,'2025-11-07 18:18:50'),(152,'7f0d55c4-b507-11f0-936b-b02628506e5c','3a02d0d40f04af1c4bf7a7c62bc02353','147.12.38.160','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36','2025-11-07 19:02:42','2025-11-07 19:37:55','2025-11-07 19:37:55',0,'2025-11-07 19:02:42'),(153,'7f0d55c4-b507-11f0-936b-b02628506e5c','b4e66499ced3e3a05f327ac454945470','147.12.38.160','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1','2025-11-07 19:37:55','2025-11-07 20:39:56','2025-11-07 20:39:56',0,'2025-11-07 19:37:55'),(154,'7f0d55c4-b507-11f0-936b-b02628506e5c','e0be00f4f9f7a51f19a277f436bc065a','147.12.38.160','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1','2025-11-07 20:39:56','2025-11-07 21:55:12','2025-11-07 21:55:12',0,'2025-11-07 20:39:56'),(155,'7f0d55c4-b507-11f0-936b-b02628506e5c','e9fd5967c796e3275dc590da623be39c','147.12.38.160','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36','2025-11-07 21:55:12','2025-11-08 00:20:33','2025-11-08 00:20:33',0,'2025-11-07 21:55:12'),(156,'7f0d55c4-b507-11f0-936b-b02628506e5c','273d61d28edff6be45e98006e8a88b3e','147.12.38.160','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1','2025-11-08 00:20:33','2025-11-08 00:51:48','2025-11-08 00:51:48',0,'2025-11-08 00:20:33'),(157,'7f0d55c4-b507-11f0-936b-b02628506e5c','ccef81466be65456a75c0009dac6e944','172.31.93.98','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 Replit-Bonsai/2.155.0 (iOS 18.5)','2025-11-08 00:51:49','2025-11-08 06:23:20','2025-11-08 06:23:20',0,'2025-11-08 00:51:49'),(158,'7f0d55c4-b507-11f0-936b-b02628506e5c','038d635f89c08bf1f5c6cc84cf745358','172.31.93.98','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 Replit-Bonsai/2.155.0 (iOS 18.5)','2025-11-08 06:23:21','2025-11-08 08:39:04','2025-11-08 08:39:04',0,'2025-11-08 06:23:21'),(160,'7f0d55c4-b507-11f0-936b-b02628506e5c','59d904ee8fc09e94b162030bcda8b0d4','172.31.93.98','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36','2025-11-08 08:39:05','2025-11-08 14:09:05','2025-11-08 14:09:05',0,'2025-11-08 08:39:05'),(165,'7f0d55c4-b507-11f0-936b-b02628506e5c','36f5ec553d31042b3355fc427f61b0f4','172.31.83.130','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 Replit-Bonsai/2.155.0 (iOS 18.5)','2025-11-08 15:50:02','2025-11-08 16:29:16','2025-11-08 16:29:16',0,'2025-11-08 15:50:02'),(166,'7f0d55c4-b507-11f0-936b-b02628506e5c','48d2ed93939e8f32747405640c033d50','172.31.72.2','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 Replit-Bonsai/2.155.0 (iOS 18.5)','2025-11-08 16:29:17','2025-11-08 17:01:01','2025-11-08 17:01:01',0,'2025-11-08 16:29:17'),(168,'7f0d55c4-b507-11f0-936b-b02628506e5c','0f3bf0a1a0f316b3be6d3b20a9a9feef','172.31.93.98','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 Replit-Bonsai/2.155.0 (iOS 18.5)','2025-11-08 17:33:13','2025-11-08 19:25:26','2025-11-08 19:25:26',0,'2025-11-08 17:33:13'),(173,'7f0d55c4-b507-11f0-936b-b02628506e5c','1ab6db04b17e97578aceb1d6d1338091','89.8.222.211','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1','2025-11-09 17:23:40','2025-11-09 17:25:34','2025-11-09 17:25:34',0,'2025-11-09 17:23:40'),(179,'7f0d55c4-b507-11f0-936b-b02628506e5c','e8e3e8480dbf409c151908aca1ae70fb','45.88.221.253','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-11-09 22:12:22','2025-11-09 23:15:20','2025-11-09 23:15:20',0,'2025-11-09 22:12:22'),(180,'7f0d55c4-b507-11f0-936b-b02628506e5c','e05838e23a2fbf04a3c2aba04ace179b','45.88.221.253','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-11-09 23:15:20','2025-11-13 08:19:40','2025-11-13 08:19:40',0,'2025-11-09 23:15:20'),(181,'7f0d55c4-b507-11f0-936b-b02628506e5c','c98e0e8545dcc67f12feedd7cfeca570','147.12.38.160','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-13 08:19:40','2025-11-13 16:32:31','2025-11-13 16:32:31',0,'2025-11-13 08:19:40'),(182,'580850091a22fb4dbe9023b759222b305282','138b3ab79fbd3aee0110b74fd9b132bf','62.92.137.147','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1','2025-11-13 16:32:31','2025-11-13 18:00:27','2025-11-13 18:00:27',0,'2025-11-13 16:32:31'),(183,'7f0d55c4-b507-11f0-936b-b02628506e5c','0a679b162dcc7adb4a1d02fdc45efcba','172.31.124.194','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 Replit-Bonsai/2.163.0 (iOS 18.5)','2025-11-13 18:00:26','2025-11-13 21:17:53','2025-11-13 21:17:53',0,'2025-11-13 18:00:26'),(184,'580850091a22fb4dbe9023b759222b305282','fbe1a7398f093597949d441694e68a4d','172.31.76.98','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 Replit-Bonsai/2.163.0 (iOS 18.5)','2025-11-13 18:50:32','2025-11-13 21:17:55','2025-11-13 21:17:55',0,'2025-11-13 18:50:32'),(185,'7f0d55c4-b507-11f0-936b-b02628506e5c','346f7ac277701b2805eaef0b98c07a8c','172.31.78.162','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Replit/1.0.14 Chrome/124.0.6367.119 Electron/30.0.3 Safari/537.36','2025-11-13 21:17:54','2025-11-13 22:54:53','2025-11-13 22:54:53',0,'2025-11-13 21:17:54'),(186,'580850091a22fb4dbe9023b759222b305282','a4ff152bb7bc9cecf85184191603a23d','172.31.78.162','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Replit/1.0.14 Chrome/124.0.6367.119 Electron/30.0.3 Safari/537.36','2025-11-13 21:57:28','2025-11-13 22:54:53','2025-11-13 22:54:53',0,'2025-11-13 21:57:28'),(187,'7f0d55c4-b507-11f0-936b-b02628506e5c','461cbbac22dd216284a68cccd685d8e2','147.12.38.160','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-13 23:08:02','2025-11-13 23:23:52','2025-11-13 23:23:52',0,'2025-11-13 23:08:02'),(188,'7f0d55c4-b507-11f0-936b-b02628506e5c','c36f5f0d05e7f6a28e4c5f48c3086bed','172.31.87.130','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Replit/1.0.14 Chrome/124.0.6367.119 Electron/30.0.3 Safari/537.36','2025-11-13 23:23:58','2025-11-14 00:03:38','2025-11-14 00:03:38',0,'2025-11-13 23:23:58'),(189,'7f072d6a-b507-11f0-936b-b02628506e5c','19bb60085ea6834b4f353f7bdb279b1b','147.12.38.160','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1','2025-11-13 23:53:36','2025-11-14 07:20:33','2025-11-14 07:20:33',0,'2025-11-13 23:53:36'),(190,'7f0d55c4-b507-11f0-936b-b02628506e5c','34ddc1ce8f709c60528588c39e31161e','172.31.70.194','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 Replit-Bonsai/2.163.0 (iOS 18.5)','2025-11-14 07:20:31','2025-11-14 07:22:01','2025-11-14 07:22:01',0,'2025-11-14 07:20:31'),(191,'7f0d55c4-b507-11f0-936b-b02628506e5c','1b9d322cb023dde88057ba4748c74623','172.31.70.194','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 Replit-Bonsai/2.163.0 (iOS 18.5)','2025-11-14 07:27:32','2025-11-14 07:31:57','2025-11-14 07:31:57',0,'2025-11-14 07:27:32'),(192,'7f0d55c4-b507-11f0-936b-b02628506e5c','027ceb6f766ab46702db529aff1c8552','172.31.70.194','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 Replit-Bonsai/2.163.0 (iOS 18.5)','2025-11-14 07:32:16','2025-11-14 07:33:04','2025-11-14 07:33:04',0,'2025-11-14 07:32:16'),(193,'7f0d55c4-b507-11f0-936b-b02628506e5c','744cc838c7d3a5d80570e1251a562086','172.31.105.66','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 Replit-Bonsai/2.163.0 (iOS 18.5)','2025-11-14 07:48:02','2025-11-14 08:44:53','2025-11-14 08:44:53',0,'2025-11-14 07:48:02'),(194,'7f0d55c4-b507-11f0-936b-b02628506e5c','8e51fae938c77b486c8f78a8f848734e','147.12.38.160','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-14 08:44:53','2025-11-14 10:10:27','2025-11-14 10:10:27',0,'2025-11-14 08:44:53'),(195,'7f072d6a-b507-11f0-936b-b02628506e5c','924b217ce36bffcb5fe9c296bb3a8ec3','172.31.109.34','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Replit/1.0.14 Chrome/124.0.6367.119 Electron/30.0.3 Safari/537.36','2025-11-14 09:02:55','2025-11-14 11:28:26','2025-11-14 11:28:26',0,'2025-11-14 09:02:55'),(196,'7f0d55c4-b507-11f0-936b-b02628506e5c','ca44aec54ec0e7754b9b9f104b98e10d','172.31.109.34','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 Replit-Bonsai/2.163.0 (iOS 18.5)','2025-11-14 10:23:16','2025-11-14 10:25:21','2025-11-14 10:25:21',0,'2025-11-14 10:23:16'),(197,'7f0d55c4-b507-11f0-936b-b02628506e5c','2e86191d0f5506372566e1a46188b297','172.31.109.34','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 Replit-Bonsai/2.163.0 (iOS 18.5)','2025-11-14 10:52:05','2025-11-14 10:52:48','2025-11-14 10:52:48',0,'2025-11-14 10:52:05'),(198,'7f0d55c4-b507-11f0-936b-b02628506e5c','a9442221e90b8831ac4335687ed7e414','172.31.109.34','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 Replit-Bonsai/2.163.0 (iOS 18.5)','2025-11-14 11:00:56','2025-11-14 16:59:04','2025-11-14 16:59:04',0,'2025-11-14 11:00:56'),(199,'7f0d55c4-b507-11f0-936b-b02628506e5c','be4b38830da2397387ac4eb0a7cee460','147.12.38.160','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1','2025-11-14 16:59:04','2025-11-14 21:18:10','2025-11-14 21:18:10',0,'2025-11-14 16:59:04'),(200,'7f0d55c4-b507-11f0-936b-b02628506e5c','eea5fe21ca074a5e2d8259a47043e609','172.31.69.34','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Replit/1.0.14 Chrome/124.0.6367.119 Electron/30.0.3 Safari/537.36','2025-11-19 11:35:00','2025-11-19 11:42:02','2025-11-19 11:42:02',0,'2025-11-19 11:35:00'),(202,'7f0d55c4-b507-11f0-936b-b02628506e5c','555b46f03774479658304e60521f7b8f','172.31.69.34','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Replit/1.0.14 Chrome/124.0.6367.119 Electron/30.0.3 Safari/537.36','2025-11-19 12:04:46','2025-11-19 12:14:09','2025-11-19 12:14:09',0,'2025-11-19 12:04:46'),(203,'7f0d55c4-b507-11f0-936b-b02628506e5c','261fc8f310764e6bdee67346d927cbea','172.31.69.34','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Replit/1.0.14 Chrome/124.0.6367.119 Electron/30.0.3 Safari/537.36','2025-11-19 12:14:33','2025-11-19 12:16:11','2025-11-19 12:16:11',0,'2025-11-19 12:14:33'),(205,'7f0d55c4-b507-11f0-936b-b02628506e5c','c5569256ef37cb863d0ba34911e53543','147.12.38.160','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-19 12:46:51','2025-11-19 13:17:13','2025-11-19 13:17:13',0,'2025-11-19 12:46:51'),(207,'7f0d55c4-b507-11f0-936b-b02628506e5c','030f00e602a308900b5a07086f7f4d36','172.31.69.34','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Replit/1.0.14 Chrome/124.0.6367.119 Electron/30.0.3 Safari/537.36','2025-11-19 22:06:06','2025-11-19 22:09:30','2025-11-19 22:09:30',0,'2025-11-19 22:06:06'),(208,'7f0d55c4-b507-11f0-936b-b02628506e5c','5db2231111389ad96ced2c25f64f6692','172.31.69.34','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Replit/1.0.14 Chrome/124.0.6367.119 Electron/30.0.3 Safari/537.36','2025-11-19 22:09:38','2025-11-19 22:32:24','2025-11-19 22:32:24',0,'2025-11-19 22:09:38'),(209,'7f0d55c4-b507-11f0-936b-b02628506e5c','c1572f7ffe5c5799a02b8b0943c8fd0e','172.31.69.34','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Replit/1.0.14 Chrome/124.0.6367.119 Electron/30.0.3 Safari/537.36','2025-11-19 22:55:07','2025-11-19 23:32:18','2025-11-19 23:32:18',0,'2025-11-19 22:55:07'),(210,'7f072d6a-b507-11f0-936b-b02628506e5c','a76de957acc543c67f536a3df10db7bd','172.31.69.34','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 Replit-Bonsai/2.163.0 (iOS 18.5)','2025-11-19 23:00:31','2025-11-20 06:49:34','2025-11-20 06:49:34',0,'2025-11-19 23:00:31'),(211,'7f0d55c4-b507-11f0-936b-b02628506e5c','95072e30a71b51f8bfdd43590c7ed691','172.31.74.2','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 Replit-Bonsai/2.163.0 (iOS 18.5)','2025-11-20 06:49:33','2025-11-20 10:25:07','2025-11-20 10:25:07',0,'2025-11-20 06:49:33'),(212,'7f0d55c4-b507-11f0-936b-b02628506e5c','a0a3ab6beaf3ae4f8b74cbc5a547a009','147.12.38.160','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-20 10:25:07','2025-11-20 11:22:29','2025-11-20 11:22:29',0,'2025-11-20 10:25:07'),(213,'7f0d55c4-b507-11f0-936b-b02628506e5c','d42156fde92fcb409ae14db9a02ca03a','172.31.83.34','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Replit/1.0.14 Chrome/124.0.6367.119 Electron/30.0.3 Safari/537.36','2025-11-20 11:22:30','2025-11-20 11:54:50','2025-11-20 11:54:50',0,'2025-11-20 11:22:30'),(214,'7f0d55c4-b507-11f0-936b-b02628506e5c','fc3e219e40919d4f09c9360adfa76ebe','147.12.38.160','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1','2025-11-20 11:54:50','2025-11-20 15:40:55','2025-11-20 15:40:55',0,'2025-11-20 11:54:50'),(229,'7f072d6a-b507-11f0-936b-b02628506e5c','f43c26ad100a2a67781fbd5e1d013e53','172.31.76.226','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Replit/1.0.14 Chrome/124.0.6367.119 Electron/30.0.3 Safari/537.36','2025-11-22 00:18:43','2025-11-22 00:51:49','2025-11-22 00:51:49',0,'2025-11-22 00:18:43'),(230,'7f0d55c4-b507-11f0-936b-b02628506e5c','7f4b88d61dac4ce6651cebaa428bee48','45.14.193.99','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0','2025-11-22 00:40:03','2025-11-22 12:15:29','2025-11-22 12:15:29',0,'2025-11-22 00:40:03'),(231,'f996adb8d578c9f15859cc9ffb78b023792b','4669859b7667a904700d3c1d4edf4773','147.12.38.160','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','2025-11-22 00:43:13','2025-11-22 12:15:29','2025-11-22 12:15:29',0,'2025-11-22 00:43:13'),(232,'7f072d6a-b507-11f0-936b-b02628506e5c','4b29d1ce0065a22aa989471ccfbd348b','172.31.76.226','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Replit/1.0.14 Chrome/124.0.6367.119 Electron/30.0.3 Safari/537.36','2025-11-22 00:53:35','2025-11-22 12:15:29','2025-11-22 12:15:29',0,'2025-11-22 00:53:35'),(233,'f996adb8d578c9f15859cc9ffb78b023792b','45a9207ae640ea8d0c06cfbfdc793fc7','147.12.38.160','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1','2025-11-22 12:15:29','2025-11-22 13:42:48','2025-11-22 13:42:48',0,'2025-11-22 12:15:29'),(234,'7f0d55c4-b507-11f0-936b-b02628506e5c','7af09a07c4d3cca6b9a60f5201a9c8cf','172.31.107.2','Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 Replit-Bonsai/2.163.0 (iOS 18.5)','2025-11-22 13:42:46','2025-11-22 15:08:59','2025-11-22 15:08:59',0,'2025-11-22 13:42:46'),(235,'7f0d55c4-b507-11f0-936b-b02628506e5c','bca9c1592e9834b12e8f96becd1cbe1b','172.31.70.194','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Replit/1.0.14 Chrome/124.0.6367.119 Electron/30.0.3 Safari/537.36','2025-11-22 15:09:00','2025-11-22 20:58:10','2025-11-22 20:58:10',0,'2025-11-22 15:09:00'),(236,'7f0d55c4-b507-11f0-936b-b02628506e5c','ba619b95002fbfdb0de7060253067dd5','172.31.77.130','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Replit/1.0.14 Chrome/124.0.6367.119 Electron/30.0.3 Safari/537.36','2025-11-22 20:58:11','2025-11-22 22:36:09','2025-11-22 22:36:09',0,'2025-11-22 20:58:11'),(237,'7f0d55c4-b507-11f0-936b-b02628506e5c','dc5f7739eec7bedbf9ebd9f6304ea996','172.31.77.130','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Replit/1.0.14 Chrome/124.0.6367.119 Electron/30.0.3 Safari/537.36','2025-11-22 22:36:10',NULL,'2025-11-22 22:36:11',1,'2025-11-22 22:36:10');
UNLOCK TABLES;
commit;

--
-- Table structure for table `tenant_admins`
--

DROP TABLE IF EXISTS `tenant_admins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tenant_admins` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tenant_admins`
--

LOCK TABLES `tenant_admins` WRITE;
set autocommit=0;
INSERT INTO `tenant_admins` VALUES ('580850091a22fb4dbe9023b759222b305282','a9cdf14b6790d0247b55534d68065eb695f9','','$2y$10$.Pat/emm80aCNmSQoNsQBetINFkYYBpAjXNe8pFPNwcnYoPcLfzgy','Kb','admin',1,'2025-11-13 21:57:27',0,NULL,'ba464206-b8d8-11f0-ad30-b02628506e5c','2025-11-13 16:28:41','2025-11-13 21:57:27','a355a634b40eb972506264982f4448a4d296'),('7f072d6a-b507-11f0-936b-b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','maritahr@gmail.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','maritahr','admin',1,'2025-11-22 00:53:35',0,NULL,'','2025-10-29 21:40:24','2025-11-22 00:53:35','7efffc84-b507-11f0-936b-b02628506e5c'),('7f0d55c4-b507-11f0-936b-b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','atlekarl@tussa.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','atlekarl','super_admin',1,'2025-11-22 22:36:10',0,NULL,'','2025-10-29 21:40:24','2025-11-22 22:36:10','7f0980cf-b507-11f0-936b-b02628506e5c'),('98c4e6f482c623c4e38e672c72b5ac7415d9','a7eb3dbc5543932549d02a0bfd2b79720ad5','','$2y$10$6lw/xOgL6uMSSpFCZLOLpeAoJxLcC2ao31yEDcgncsg3jqD2eI/Xm','Mussa','admin',1,NULL,0,NULL,'ba464206-b8d8-11f0-ad30-b02628506e5c','2025-11-20 15:46:48','2025-11-20 15:46:48',NULL),('f996adb8d578c9f15859cc9ffb78b023792b','7efbddea-b507-11f0-936b-b02628506e5c','','$2y$10$vMsg4HXtPkyVLBHthiO7J.DsuK0sHfbkyZizxNfv/jE6ET80tTg5y','magnek','admin',1,'2025-11-22 12:15:29',0,NULL,'ba464206-b8d8-11f0-ad30-b02628506e5c','2025-11-22 00:43:03','2025-11-22 12:15:29',NULL);
UNLOCK TABLES;
commit;

--
-- Table structure for table `tenant_announcements`
--

DROP TABLE IF EXISTS `tenant_announcements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tenant_announcements` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `company_id` char(36) NOT NULL,
  `author_id` char(36) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `announcement_type` varchar(50) DEFAULT 'info',
  `priority` varchar(20) DEFAULT 'normal',
  `target_audience` varchar(50) DEFAULT 'all',
  `is_published` tinyint(1) DEFAULT 0,
  `publish_at` datetime DEFAULT current_timestamp(),
  `expire_at` datetime DEFAULT NULL,
  `is_pinned` tinyint(1) DEFAULT 0,
  `related_event_id` char(36) DEFAULT NULL,
  `related_module` varchar(50) DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `image_url` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_company_published` (`company_id`,`is_published`,`publish_at`),
  KEY `idx_author` (`author_id`),
  KEY `idx_pinned` (`is_pinned`),
  KEY `idx_expiry` (`expire_at`),
  KEY `idx_related_event` (`related_event_id`),
  CONSTRAINT `fk_tenant_announcements_author` FOREIGN KEY (`author_id`) REFERENCES `tenant_admins` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tenant_announcements_company` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tenant_announcements_event` FOREIGN KEY (`related_event_id`) REFERENCES `events_eventdata` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tenant_announcements`
--

LOCK TABLES `tenant_announcements` WRITE;
set autocommit=0;
INSERT INTO `tenant_announcements` VALUES ('0a0544b3-c61f-11f0-a3f5-b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','7f0d55c4-b507-11f0-936b-b02628506e5c','Test','Testinnlegg','info','normal','all',1,'2025-11-20 15:41:00',NULL,0,NULL,NULL,NULL,'2025-11-20 15:41:46','2025-11-20 15:41:46',NULL),('0f96a234-bc31-11f0-a9ad-b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','7f0d55c4-b507-11f0-936b-b02628506e5c','Hei','Hallo','info','normal','all',1,'2025-11-08 00:25:00',NULL,0,NULL,NULL,NULL,'2025-11-08 00:25:34','2025-11-08 00:25:34',NULL),('9573c554-b564-11f0-a6d0-b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','7f0d55c4-b507-11f0-936b-b02628506e5c','eee','eeeeee','info','normal','all',0,'2025-10-30 08:46:00',NULL,0,NULL,NULL,NULL,'2025-10-30 08:46:46','2025-10-30 08:46:46',NULL),('c77de67c-b76c-11f0-8f3c-b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','7f0d55c4-b507-11f0-936b-b02628506e5c','Da kjører vi test!','Velkommen til vår nye webside hos salesman!:-)\ndra til høyre for Om oss, dra til venstre for tjenester(booking, o.l)','info','normal','all',1,'2025-11-01 22:49:00',NULL,0,NULL,NULL,NULL,'2025-11-01 22:50:27','2025-11-01 22:50:27','https://image.forskning.no/2128003.webp?imageId=2128003&x=0.00&y=0.00&cropw=100.00&croph=100.00&width=964&height=700&format=jpg');
UNLOCK TABLES;
commit;

--
-- Table structure for table `tenant_companies`
--

DROP TABLE IF EXISTS `tenant_companies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tenant_companies` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `name` varchar(255) NOT NULL,
  `legal_name` varchar(255) DEFAULT NULL,
  `org_number` varchar(20) DEFAULT NULL,
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
  `admin_password_hash` varchar(255) DEFAULT NULL,
  `password_last_changed` datetime DEFAULT current_timestamp(),
  `password_reset_required` tinyint(1) DEFAULT 0,
  `subdomain_active` tinyint(1) DEFAULT 0,
  `subdomain_dns_created` datetime DEFAULT NULL,
  `subdomain_last_check` datetime DEFAULT NULL,
  `subdomain_status` varchar(20) DEFAULT 'pending',
  PRIMARY KEY (`id`),
  UNIQUE KEY `subdomain` (`subdomain`),
  UNIQUE KEY `domain` (`domain`),
  KEY `onboarded_by` (`onboarded_by`),
  KEY `idx_tenant_companies_subdomain` (`subdomain`),
  KEY `idx_tenant_companies_entity_type` (`entity_type`),
  KEY `idx_tenant_subdomain_status` (`subdomain_active`,`subdomain_status`),
  CONSTRAINT `tenant_companies_ibfk_1` FOREIGN KEY (`onboarded_by`) REFERENCES `saas_admins` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Tenant companies with individual admin passwords for data modification';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tenant_companies`
--

LOCK TABLES `tenant_companies` WRITE;
set autocommit=0;
INSERT INTO `tenant_companies` VALUES ('7efbddea-b507-11f0-936b-b02628506e5c','Bergsøy Danseklubb','Bergsøy Danseklubb',' 996 171 159','','bergsoey','','','Vi er en gjeng kjekke folk som kjem ifrå Herøy i Møre og Romsdal. Vi arrangerar dans, bingo og kinovisningar','atlekarl@tussa.com','95918925','bergsoy.salesman.no','https://8upload.com/image/69027f81ae243/bergsoy_logo_black.png','Mulevikvegen 42','Nerlandsøy','Møre og Romsdal','6098','Norway','',1,1,NULL,NULL,'2025-10-29 21:40:24','2025-11-14 16:36:08',1,'$2y$12$c3IAZ9yaJMz/RhgZDHlo6OLAEjdZGWzCrGE1g.D/My7BR0nqqb8ie','2025-10-29 21:40:24',0,0,NULL,'2025-11-09 22:34:42','pending'),('a7eb3dbc5543932549d02a0bfd2b79720ad5','VERONA ULSTEINVIK AS','VERONA ULSTEINVIK AS','921283873','company','verona',NULL,'Drift av restauranter',NULL,'veronaulsteinvik@salesman.no','40404040',NULL,NULL,'Kyrkjegata 14A','ULSTEINVIK',NULL,'6065','Norway',NULL,0,1,NULL,'ba464206-b8d8-11f0-ad30-b02628506e5c','2025-11-20 15:45:54','2025-11-20 15:47:33',1,NULL,'2025-11-20 15:45:54',0,0,NULL,'2025-11-20 15:47:33','active'),('a9cdf14b6790d0247b55534d68065eb695f9','KAFFEBAREN ULSTEINVIK AS','KAFFEBAREN ULSTEINVIK AS','988210102','company','kaffebaren',NULL,'Drift av barer','','kaffebarenulsteinvik@salesman.no','40404040','',NULL,'Sjøgata 55','ULSTEINVIK',NULL,'6065','magnekvalsvique@gmail.com',NULL,0,1,NULL,'ba464206-b8d8-11f0-ad30-b02628506e5c','2025-11-13 16:27:41','2025-11-13 17:12:16',1,'$2y$10$/wftPstrPMjYMkGGWXjBYeMl.uoyC4I/4pWQu2esBvLe.2Li5/vk2','2025-11-13 17:12:16',0,0,NULL,NULL,'pending');
UNLOCK TABLES;
commit;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`salesmanno04`@`2a01:5b40:0:%`*/ /*!50003 TRIGGER audit_company_password_change
AFTER UPDATE ON tenant_companies
FOR EACH ROW
BEGIN
    IF OLD.admin_password_hash != NEW.admin_password_hash THEN
        INSERT INTO audit_logs (
            id, company_id, action, resource_type, resource_id, resource_name,
            old_values, new_values, performed_at
        ) VALUES (
            UUID(),
            NEW.id,
            'COMPANY_PASSWORD_CHANGED',
            'tenant_company',
            NEW.id,
            NEW.name,
            JSON_OBJECT('password_changed_at', OLD.password_last_changed),
            JSON_OBJECT('password_changed_at', NEW.password_last_changed),
            CURRENT_TIMESTAMP()
        );
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `tenant_company_modules`
--

DROP TABLE IF EXISTS `tenant_company_modules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tenant_company_modules` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `company_id` char(36) NOT NULL,
  `module_id` char(36) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `config` text DEFAULT NULL,
  `activated_at` datetime DEFAULT current_timestamp(),
  `deactivated_at` datetime DEFAULT NULL,
  `assigned_by` char(36) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_id` (`company_id`,`module_id`),
  KEY `module_id` (`module_id`),
  KEY `assigned_by` (`assigned_by`),
  CONSTRAINT `tenant_company_modules_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tenant_company_modules_ibfk_2` FOREIGN KEY (`module_id`) REFERENCES `system_modules` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tenant_company_modules_ibfk_3` FOREIGN KEY (`assigned_by`) REFERENCES `saas_admins` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tenant_company_modules`
--

LOCK TABLES `tenant_company_modules` WRITE;
set autocommit=0;
INSERT INTO `tenant_company_modules` VALUES ('4b38e25c-c0a5-11f0-8af7-b02628506e5c','a9cdf14b6790d0247b55534d68065eb695f9','15a3c857-b57f-11f0-a6d0-b02628506e5c',1,NULL,'2025-11-13 16:27:41',NULL,'ba464206-b8d8-11f0-ad30-b02628506e5c','2025-11-13 16:27:41'),('4b38f231-c0a5-11f0-8af7-b02628506e5c','a9cdf14b6790d0247b55534d68065eb695f9','7f3c499a-af1a-11f0-a914-b02628506e5c',1,NULL,'2025-11-13 16:27:41',NULL,'ba464206-b8d8-11f0-ad30-b02628506e5c','2025-11-13 16:27:41'),('4b390007-c0a5-11f0-8af7-b02628506e5c','a9cdf14b6790d0247b55534d68065eb695f9','7f3c4bb2-af1a-11f0-a914-b02628506e5c',1,NULL,'2025-11-13 16:27:41',NULL,'ba464206-b8d8-11f0-ad30-b02628506e5c','2025-11-13 16:27:41'),('4b390d89-c0a5-11f0-8af7-b02628506e5c','a9cdf14b6790d0247b55534d68065eb695f9','7f3c4c76-af1a-11f0-a914-b02628506e5c',1,NULL,'2025-11-13 16:27:41',NULL,'ba464206-b8d8-11f0-ad30-b02628506e5c','2025-11-13 16:27:41'),('4b391f94-c0a5-11f0-8af7-b02628506e5c','a9cdf14b6790d0247b55534d68065eb695f9','7f3c4ceb-af1a-11f0-a914-b02628506e5c',1,NULL,'2025-11-13 16:27:41',NULL,'ba464206-b8d8-11f0-ad30-b02628506e5c','2025-11-13 16:27:41'),('4b392c26-c0a5-11f0-8af7-b02628506e5c','a9cdf14b6790d0247b55534d68065eb695f9','7f3c4d5f-af1a-11f0-a914-b02628506e5c',1,NULL,'2025-11-13 16:27:41',NULL,'ba464206-b8d8-11f0-ad30-b02628506e5c','2025-11-13 16:27:41'),('4b3936c3-c0a5-11f0-8af7-b02628506e5c','a9cdf14b6790d0247b55534d68065eb695f9','7f3c4dcc-af1a-11f0-a914-b02628506e5c',1,NULL,'2025-11-13 16:27:41',NULL,'ba464206-b8d8-11f0-ad30-b02628506e5c','2025-11-13 16:27:41'),('4b39409b-c0a5-11f0-8af7-b02628506e5c','a9cdf14b6790d0247b55534d68065eb695f9','7f3c4e39-af1a-11f0-a914-b02628506e5c',1,NULL,'2025-11-13 16:27:41',NULL,'ba464206-b8d8-11f0-ad30-b02628506e5c','2025-11-13 16:27:41'),('4b394bcb-c0a5-11f0-8af7-b02628506e5c','a9cdf14b6790d0247b55534d68065eb695f9','7f3c4e9e-af1a-11f0-a914-b02628506e5c',1,NULL,'2025-11-13 16:27:41',NULL,'ba464206-b8d8-11f0-ad30-b02628506e5c','2025-11-13 16:27:41'),('4b39572c-c0a5-11f0-8af7-b02628506e5c','a9cdf14b6790d0247b55534d68065eb695f9','7f3c4f0b-af1a-11f0-a914-b02628506e5c',1,NULL,'2025-11-13 16:27:41',NULL,'ba464206-b8d8-11f0-ad30-b02628506e5c','2025-11-13 16:27:41'),('4b39627c-c0a5-11f0-8af7-b02628506e5c','a9cdf14b6790d0247b55534d68065eb695f9','f9d42479-b182-11f0-b9ad-b02628506e5c',1,NULL,'2025-11-13 16:27:41',NULL,'ba464206-b8d8-11f0-ad30-b02628506e5c','2025-11-13 16:27:41'),('9e36f67c-c61f-11f0-a3f5-b02628506e5c','a7eb3dbc5543932549d02a0bfd2b79720ad5','15a3c857-b57f-11f0-a6d0-b02628506e5c',1,NULL,'2025-11-20 15:45:54',NULL,'ba464206-b8d8-11f0-ad30-b02628506e5c','2025-11-20 15:45:54'),('9e37124f-c61f-11f0-a3f5-b02628506e5c','a7eb3dbc5543932549d02a0bfd2b79720ad5','7f3c499a-af1a-11f0-a914-b02628506e5c',1,NULL,'2025-11-20 15:45:54',NULL,'ba464206-b8d8-11f0-ad30-b02628506e5c','2025-11-20 15:45:54'),('9e37225f-c61f-11f0-a3f5-b02628506e5c','a7eb3dbc5543932549d02a0bfd2b79720ad5','7f3c4bb2-af1a-11f0-a914-b02628506e5c',1,NULL,'2025-11-20 15:45:54',NULL,'ba464206-b8d8-11f0-ad30-b02628506e5c','2025-11-20 15:45:54'),('9e373341-c61f-11f0-a3f5-b02628506e5c','a7eb3dbc5543932549d02a0bfd2b79720ad5','7f3c4c76-af1a-11f0-a914-b02628506e5c',1,NULL,'2025-11-20 15:45:54',NULL,'ba464206-b8d8-11f0-ad30-b02628506e5c','2025-11-20 15:45:54'),('9e375ce1-c61f-11f0-a3f5-b02628506e5c','a7eb3dbc5543932549d02a0bfd2b79720ad5','7f3c4ceb-af1a-11f0-a914-b02628506e5c',1,NULL,'2025-11-20 15:45:54',NULL,'ba464206-b8d8-11f0-ad30-b02628506e5c','2025-11-20 15:45:54'),('9e376413-c61f-11f0-a3f5-b02628506e5c','a7eb3dbc5543932549d02a0bfd2b79720ad5','7f3c4d5f-af1a-11f0-a914-b02628506e5c',1,NULL,'2025-11-20 15:45:54',NULL,'ba464206-b8d8-11f0-ad30-b02628506e5c','2025-11-20 15:45:54'),('9e376ef4-c61f-11f0-a3f5-b02628506e5c','a7eb3dbc5543932549d02a0bfd2b79720ad5','7f3c4dcc-af1a-11f0-a914-b02628506e5c',1,NULL,'2025-11-20 15:45:54',NULL,'ba464206-b8d8-11f0-ad30-b02628506e5c','2025-11-20 15:45:54'),('9e377701-c61f-11f0-a3f5-b02628506e5c','a7eb3dbc5543932549d02a0bfd2b79720ad5','7f3c4e39-af1a-11f0-a914-b02628506e5c',1,NULL,'2025-11-20 15:45:54',NULL,'ba464206-b8d8-11f0-ad30-b02628506e5c','2025-11-20 15:45:54'),('9e3780cf-c61f-11f0-a3f5-b02628506e5c','a7eb3dbc5543932549d02a0bfd2b79720ad5','7f3c4e9e-af1a-11f0-a914-b02628506e5c',1,NULL,'2025-11-20 15:45:54',NULL,'ba464206-b8d8-11f0-ad30-b02628506e5c','2025-11-20 15:45:54'),('9e378aa1-c61f-11f0-a3f5-b02628506e5c','a7eb3dbc5543932549d02a0bfd2b79720ad5','7f3c4f0b-af1a-11f0-a914-b02628506e5c',1,NULL,'2025-11-20 15:45:54',NULL,'ba464206-b8d8-11f0-ad30-b02628506e5c','2025-11-20 15:45:54'),('9e379495-c61f-11f0-a3f5-b02628506e5c','a7eb3dbc5543932549d02a0bfd2b79720ad5','f9d42479-b182-11f0-b9ad-b02628506e5c',1,NULL,'2025-11-20 15:45:54',NULL,'ba464206-b8d8-11f0-ad30-b02628506e5c','2025-11-20 15:45:54'),('9f05919d-b508-11f0-936b-b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','7f3c499a-af1a-11f0-a914-b02628506e5c',1,NULL,'2025-10-29 21:48:28',NULL,'4538cad6-b0e4-11f0-a1bb-b02628506e5c','2025-10-29 21:48:28'),('9f05a218-b508-11f0-936b-b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','7f3c4bb2-af1a-11f0-a914-b02628506e5c',1,NULL,'2025-10-29 21:48:28',NULL,'4538cad6-b0e4-11f0-a1bb-b02628506e5c','2025-10-29 21:48:28'),('9f05a568-b508-11f0-936b-b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','7f3c4c76-af1a-11f0-a914-b02628506e5c',1,NULL,'2025-10-29 21:48:28',NULL,'4538cad6-b0e4-11f0-a1bb-b02628506e5c','2025-10-29 21:48:28'),('9f05a748-b508-11f0-936b-b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','7f3c4ceb-af1a-11f0-a914-b02628506e5c',1,NULL,'2025-10-29 21:48:28',NULL,'4538cad6-b0e4-11f0-a1bb-b02628506e5c','2025-10-29 21:48:28'),('9f05a90f-b508-11f0-936b-b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','7f3c4d5f-af1a-11f0-a914-b02628506e5c',1,NULL,'2025-10-29 21:48:28',NULL,'4538cad6-b0e4-11f0-a1bb-b02628506e5c','2025-10-29 21:48:28'),('9f05aaca-b508-11f0-936b-b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','7f3c4dcc-af1a-11f0-a914-b02628506e5c',1,NULL,'2025-10-29 21:48:28',NULL,'4538cad6-b0e4-11f0-a1bb-b02628506e5c','2025-10-29 21:48:28'),('9f05ac89-b508-11f0-936b-b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','7f3c4e39-af1a-11f0-a914-b02628506e5c',1,NULL,'2025-10-29 21:48:28',NULL,'4538cad6-b0e4-11f0-a1bb-b02628506e5c','2025-10-29 21:48:28'),('9f05ae3e-b508-11f0-936b-b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','7f3c4e9e-af1a-11f0-a914-b02628506e5c',1,NULL,'2025-10-29 21:48:28',NULL,'4538cad6-b0e4-11f0-a1bb-b02628506e5c','2025-10-29 21:48:28'),('9f05aff4-b508-11f0-936b-b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','7f3c4f0b-af1a-11f0-a914-b02628506e5c',1,NULL,'2025-10-29 21:48:28',NULL,'4538cad6-b0e4-11f0-a1bb-b02628506e5c','2025-10-29 21:48:28'),('9f05b1ac-b508-11f0-936b-b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','f9d42479-b182-11f0-b9ad-b02628506e5c',1,NULL,'2025-10-29 21:48:28',NULL,'4538cad6-b0e4-11f0-a1bb-b02628506e5c','2025-10-29 21:48:28');
UNLOCK TABLES;
commit;

--
-- Table structure for table `tenant_customers`
--

DROP TABLE IF EXISTS `tenant_customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tenant_customers` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `company_id` char(36) NOT NULL,
  `customer_code` varchar(100) NOT NULL,
  `first_name` varchar(255) DEFAULT NULL,
  `last_name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `job_title` varchar(100) DEFAULT NULL,
  `address_street` varchar(255) DEFAULT NULL,
  `address_city` varchar(255) DEFAULT NULL,
  `address_state` varchar(255) DEFAULT NULL,
  `address_zip` varchar(50) DEFAULT NULL,
  `address_country` varchar(100) DEFAULT NULL,
  `customer_type` varchar(20) DEFAULT 'individual',
  `customer_tier` varchar(50) DEFAULT 'regular',
  `source` varchar(100) DEFAULT NULL,
  `is_member` tinyint(1) DEFAULT 0,
  `status` varchar(50) DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_id` (`company_id`,`customer_code`),
  UNIQUE KEY `company_id_2` (`company_id`,`email`),
  CONSTRAINT `tenant_customers_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tenant_customers`
--

LOCK TABLES `tenant_customers` WRITE;
set autocommit=0;
INSERT INTO `tenant_customers` VALUES ('959d575ac3d579b6ce4fdc2fa8ba2709df5e','a7eb3dbc5543932549d02a0bfd2b79720ad5','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'individual','regular',NULL,0,'active',NULL,'2025-11-20 15:45:54','2025-11-20 15:45:54'),('99ff86c4c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-99FF86C4','Egil','Rasmineson Frantsen','e-fran@online.no','90689856',NULL,NULL,NULL,'Gamlemyklebustveg 9 6092 Fosnavåg',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:32','2025-11-13 23:46:32'),('9a00105dc0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A00105D','Britt','Inger Gusdal Frantsen','brittingerbig@gmail.com','93226717',NULL,NULL,NULL,'GamleMyklebustveg 9 6092 Fosnavåg',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:32','2025-11-13 23:46:32'),('9a006b0bc0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A006B0B','Atle','Karl Kvalsvik','atlekarl@tussa.com','95918925','1956-04-08',NULL,NULL,'Mulevikvegen,42 6098 Nerlandsøy',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:32','2025-11-22 12:27:59'),('9a00c1c0c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A00C1C0','Bente','Kvalsvik Notøy','bente02@live.no','48158704',NULL,NULL,NULL,'Breidanesvegen 8, 6092 Fosnavåg',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:32','2025-11-13 23:46:32'),('9a011f2ec0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A011F2E','Marion','Nilsen','marion.nilsen@outlook.com','97988677','1955-07-17',NULL,NULL,'Mulevikvegen,42 6098 Nerlandsøy',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:32','2025-11-13 23:46:32'),('9a017d83c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A017D83','Kristin','Leine','kristinleine@hotmail.no','99649880',NULL,NULL,NULL,'Jordmor Helgas veg  14, 6090 Fosnavåg',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:32','2025-11-13 23:46:32'),('9a01d78dc0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A01D78D','Marita','Higraf Remøy','maritahr2@gmail.com','90981991','1966-04-14',NULL,NULL,'Nedre plassgjerde 6098 Nerlandsøy',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:32','2025-11-13 23:46:32'),('9a0232bdc0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A0232BD','Oddveig','Arnesen',NULL,'45235239',NULL,NULL,NULL,'Nordgardsvegen 4 6090 Fosnavåg',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:32','2025-11-13 23:46:32'),('9a0293c1c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A0293C1','Asbjørn','Bendal','abendal@online.no','95766662',NULL,NULL,NULL,'Kviehaugen 8 B 6091 Fosnavåg',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:32','2025-11-13 23:46:32'),('9a02f472c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A02F472','Jan','Bugge',NULL,'95125587',NULL,NULL,NULL,'Nedre Hjelmeseth 20 6091 Fosnavåg',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:32','2025-11-13 23:46:32'),('9a03735ac0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A03735A','Jan','Bø',NULL,'48051150',NULL,NULL,NULL,'Sandevegen 6 6095 Bølandet',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:32','2025-11-13 23:46:32'),('9a040014c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A040014','Reidun','Baade','reidun23@hotmail.no','97077208','1958-03-23',NULL,NULL,'Ospenesvegen 7 6092 Fosnavåg',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:32','2025-11-13 23:46:32'),('9a048c74c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A048C74','Annlaug','Eikrem','annlaugeikrem@hotmail.com','46430527',NULL,NULL,NULL,'Voldsundvegen 48, 6094 Leinøy',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:32','2025-11-13 23:46:32'),('9a051722c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A051722','Myrthel','Emblem','myrtelemblem@gmail.com','91849589',NULL,NULL,NULL,'Postboks 25 6099 Fosnavåg',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:32','2025-11-13 23:46:32'),('9a05addcc0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A05ADDC','Arild','Flusund','arild.flusund@gmail.com','91637514','1958-10-12',NULL,NULL,'Sandevegen 8 9095 Bølandet',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:32','2025-11-13 23:46:32'),('9a0653e5c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A0653E5','Arnhild','Flusund',NULL,'41235051','1962-04-29',NULL,NULL,'Sandevegen 8 6095 Bølandet',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:32','2025-11-13 23:46:32'),('9a06e5c8c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A06E5C8','Idar','Frantzen','frantsenidar@gmail.com','40613610',NULL,NULL,NULL,'Ospenesveien 21 6092 Fosnavåg',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:32','2025-11-13 23:46:32'),('9a077e16c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A077E16','Tone','Foss Frantzen','tone.frantzen@hotmail.com','95338070',NULL,NULL,NULL,'Holmsildgata 16 6090 Fosnavåg',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:32','2025-11-13 23:46:32'),('9a0813e5c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A0813E5','Mathias','Haugen','stottemur@hotmail.com','45500147',NULL,NULL,NULL,'Myklebustvegen 115 6488 Myklebust',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:32','2025-11-13 23:46:32'),('9a08bd3ac0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A08BD3A','Robert','Bjarne Helde','robert.heldeb@gmail.com','97624748',NULL,NULL,NULL,'Demingane 27 6092 Fosnavåg',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:32','2025-11-13 23:46:32'),('9a0958c6c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A0958C6','Fritjof','Husøy',NULL,'95223457',NULL,NULL,NULL,'Torvik 6095 Bølandet',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:32','2025-11-13 23:46:32'),('9a09e5f7c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A09E5F7','Erik','Iversen','erik.iversen@kcadeutag.com','90838258',NULL,NULL,NULL,'Kopperstadvegen 140 6098 Nerlandsøy',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:32','2025-11-13 23:46:32'),('9a0a805ac0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A0A805A','Margunn','Kalland',NULL,'91329484',NULL,NULL,NULL,'Massavegen 14, 6096 Runde',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:32','2025-11-13 23:46:32'),('9a0b115bc0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A0B115B','Marit','Knutsen',NULL,'91894371',NULL,NULL,NULL,'Borgarhaugvegen 4 6091 Fosnavåg',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:32','2025-11-13 23:46:32'),('9a0ba712c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A0BA712','Perly','Christin Vikebak Kopperstad','perly.kopperstad@outlook.com','90117868',NULL,NULL,NULL,'6099 Fosnavåg',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:32','2025-11-13 23:46:32'),('9a0c4a57c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A0C4A57','Idar','Ove Kragset','idarovekr@hotmail.com','90112149',NULL,NULL,NULL,'Breidanesvegen 8, 6092 Fosnavåg',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:32','2025-11-13 23:46:32'),('9a0d2398c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A0D2398','Myrthel','Kvalsund',NULL,'90588968',NULL,NULL,NULL,'Teigelia 38 6098 Nerlandsøy',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:32','2025-11-13 23:46:32'),('9a0dbb48c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A0DBB48','Per','Ove Kvalsund',NULL,'90688968',NULL,NULL,NULL,'Teigelia 38 6098 Nerlandsøy',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:32','2025-11-13 23:46:32'),('9a0e53b8c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A0E53B8','Geir','Vangen Kvalsund','geirkv@gmail.com','48248499',NULL,NULL,NULL,'Øvre Plassegjerde 5 6098 Nerlandsøy',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:32','2025-11-13 23:46:32'),('9a0eea1ec0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A0EEA1E','aksel.kvalsvik@tussa.com','','aksel.kvalsvik@tussa.com','97553520',NULL,NULL,NULL,'Øvre Plassgjerdet 61 6098 Nerlandsøy',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:32','2025-11-13 23:46:32'),('9a0f7707c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A0F7707','Evy','Helen Ryssevik Kvalsvik','evy.kvals@gmail.com','95995513',NULL,NULL,NULL,'Bendalsvegen 8C 6091 Fosnavåg',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:32','2025-11-13 23:46:32'),('9a100893c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A100893','Åse','Kvalsvik',NULL,'92451835',NULL,NULL,NULL,'Øvre Plassegjerde 5 6098 Nerlandsøy',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:32','2025-11-13 23:46:32'),('9a108facc0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A108FAC','Mariann','Leinebø',NULL,'48294481',NULL,NULL,NULL,'Støylavegne 13 6094 Leinøy',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:32','2025-11-13 23:46:32'),('9a111b45c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A111B45','Gunnar','Leinebø',NULL,'91561632',NULL,NULL,NULL,'Støylavegen 13 6094 Leinøy',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:32','2025-11-13 23:46:32'),('9a11a871c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A11A871','Marit','Jordan Lyster',NULL,'95156176',NULL,NULL,NULL,'Leikongvegen 422 6080 Gurskøy',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:32','2025-11-13 23:46:32'),('9a123e90c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A123E90','Mariann','Løvold','mariann.lovold@hotmail.com','98482604',NULL,NULL,NULL,'Verket 14 6091 Fosnavåg',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:32','2025-11-13 23:46:32'),('9a12d8aec0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A12D8AE','Tore','Løvøy','tore.lovoy@gmail.com','41307657',NULL,NULL,NULL,'Sandvikvegen 71 6070 Tjørvåg',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:32','2025-11-13 23:46:32'),('9a1367abc0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A1367AB','Irene','Petra Madsen',NULL,'91737567',NULL,NULL,NULL,'Geila 5, 6098 Nerlandsøy',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:32','2025-11-13 23:46:32'),('9a13f823c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A13F823','Johan','Moltu','johan.moltu@gmail.com','91568576',NULL,NULL,NULL,'Røyravegen 10 6095 Bølandet',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:32','2025-11-13 23:46:32'),('9a14a053c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A14A053','Sølvi','Moltu','solvi.moltu@tussa.com','93641550',NULL,NULL,NULL,'Leinøy 6094 Leinøy',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:32','2025-11-13 23:46:32'),('9a1542dfc0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A1542DF','Anne-Jorunn','Moltumyr',NULL,'90940208',NULL,NULL,NULL,'Remøy 6094 Leinøy',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:32','2025-11-13 23:46:32'),('9a15d50cc0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A15D50C','Henning','Moltumyr','henningmoltumyr@tussa.com','94143636',NULL,NULL,NULL,'Remøy 6094 Leinøy',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:32','2025-11-13 23:46:32'),('9a1664cac0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A1664CA','Oddvar','Myklebust',NULL,'93600832',NULL,NULL,NULL,'Eggesbøvegen 6 A 6092 Fosnavåg',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:32','2025-11-13 23:46:32'),('9a16f795c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A16F795','Jan','Arild Myklebusthaug','jamyklebusthaug@gmail.com','47830680',NULL,NULL,NULL,'Brunholmgata 22 6004 Ålesund',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:32','2025-11-13 23:46:32'),('9a179ac8c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A179AC8','Geir','Per Nerland','geir.per.nerland@gmail.com','99308210',NULL,NULL,NULL,'Landsvegen 12 6098 Nerlandsøy',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:32','2025-11-13 23:46:32'),('9a183e6ac0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A183E6A','Jeanette','Nerland','jeanette@hotmail.com','97191203',NULL,NULL,NULL,'Landsvegen 12 6098 Nerlandsøy',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:32','2025-11-13 23:46:32'),('9a18e777c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A18E777','Oddbjørn','Nerland','oddbjorn@nerland.io','47238763',NULL,NULL,NULL,'Svingen 8 6092 Fosnavåg',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:32','2025-11-13 23:46:32'),('9a198dc0c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A198DC0','Alf','Nerland','alfnerland@tussa.com','41660225',NULL,NULL,NULL,'Holmsildgata 16 6090 Fosnavåg',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:32','2025-11-13 23:46:32'),('9a1a2c7ec0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A1A2C7E','Marit','Nerlid',NULL,'97643583',NULL,NULL,NULL,'Kalvegjerde 2 6076 Moltustranda',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:32','2025-11-13 23:46:32'),('9a1ac58ec0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A1AC58E','Anita','Nilsen','anita-nilsen@live.no','97646387',NULL,NULL,NULL,'6092 Fosnavåg',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:32','2025-11-13 23:46:32'),('9a1b568bc0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A1B568B','Gerd','Jorunn Nilsen',NULL,'41302631',NULL,NULL,NULL,'Kyrkjevegen 93 6230 Sykkulven',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:32','2025-11-13 23:46:32'),('9a1be6f7c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A1BE6F7','Gunn','Nilsen','gunn-n@hotmail.no','93412336',NULL,NULL,NULL,'Bendalsvegen 15B 6091 Fosnavåg',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:32','2025-11-13 23:46:32'),('9a1c8974c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A1C8974','Bård','Nordnes',NULL,'41734804',NULL,NULL,NULL,'Voldsundvegen 32 6094 Leinøy',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:32','2025-11-13 23:46:32'),('9a1d20b6c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A1D20B6','Ingunn','Nordnes','ingnordnes@hotmail.com','95752952',NULL,NULL,NULL,'Voldsundvegen 32 6094 Leinøy',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:32','2025-11-13 23:46:32'),('9a1df526c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A1DF526','Alis','Notøy',NULL,'90857103',NULL,NULL,NULL,'Stornesvegen 8 6070 Tjørvåg',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:32','2025-11-13 23:46:32'),('9a1e906bc0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A1E906B','Mai-Brit','Nygarden',NULL,'95000339',NULL,NULL,NULL,'K.L.Vegen 9, 6060 Hareid',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:32','2025-11-13 23:46:32'),('9a1f32ccc0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A1F32CC','Sigmund','Nymark','sigmund.nymark@gmail.com','41932875',NULL,NULL,NULL,'Leione 3A 6094 Leinøy',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:32','2025-11-13 23:46:32'),('9a1fd941c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A1FD941','Eldar','Hoan Ose',NULL,'41506612',NULL,NULL,NULL,'Hoslekervegen 120 6065 Ulsteinvik',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:32','2025-11-13 23:46:32'),('9a20774cc0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A20774C','Endre','Johan Ose','endrejohan@hotmail.com','91101955',NULL,NULL,NULL,'Holkestadvegen 6 6091 Fosnavåg',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:32','2025-11-13 23:46:32'),('9a211c68c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A211C68','Eva','Lena Ose','trolle-40@hotmail.com','48172229',NULL,NULL,NULL,'Hoslekervegen 120 6065 Ulsteinvik',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:32','2025-11-13 23:46:32'),('9a21c3f3c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A21C3F3','Bjørn','Prytz','bjornprytz@gmail.com','47393108',NULL,NULL,NULL,'Buråsa 1 6090 Fosnavåg',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:32','2025-11-13 23:46:32'),('9a226339c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A226339','Kristin','Rabben','kri-rabb@online.no','90069947',NULL,NULL,NULL,'Brunholmgata 22, 6004 Ålesund',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:32','2025-11-13 23:46:32'),('9a230c15c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A230C15','Kirsten','Reite','k.reite@hotmail.com','95791864',NULL,NULL,NULL,'Kvianesvegen 49, 6094 Leinøy',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:32','2025-11-13 23:46:32'),('9a23b030c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A23B030','Ronald','Reite','rona-re@tussa.com','41438157',NULL,NULL,NULL,'Kvianesvegen 49, 6094 Leinøy',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:32','2025-11-13 23:46:32'),('9a245933c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A245933','Aud','Remøy','audremoy@gmail.com','98684879','1961-06-04',NULL,NULL,'Myrane 20 6094 Leinøy',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:32','2025-11-13 23:46:32'),('9a250463c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A250463','Kurt','Remøy','kurt.remoy@tussa.com','95839656','1957-11-15',NULL,NULL,'Myrane 20 6094 Leinøy',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:32','2025-11-13 23:46:32'),('9a25c234c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A25C234','Paul','Gustav Remøy','gustav.jarle@example.com','87654321',NULL,NULL,NULL,'Remøya 22 6094 Leinøy',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:32','2025-11-13 23:46:32'),('9a267a75c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A267A75','Sveinung','Remøy',NULL,'48195217',NULL,NULL,NULL,'Nedre Plassgjerde 7 6098 Nerlandsøy',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:32','2025-11-13 23:46:32'),('9a2723acc0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A2723AC','Ellinor','Rinde','hallgeir66@hotmail.com','41257965',NULL,NULL,NULL,'Ædane 9 6080 Gurskøy',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:32','2025-11-13 23:46:32'),('9a27cde1c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A27CDE1','Liv','Runde',NULL,'92260396',NULL,NULL,NULL,'Rundevegen 10 6096 Runde',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:32','2025-11-13 23:46:32'),('9a286a86c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A286A86','Frode','Ryssevik',NULL,'97521996',NULL,NULL,NULL,'Bendalsvegen 6C 6091 Fosnavåg',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:32','2025-11-13 23:46:32'),('9a292bf5c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A292BF5','Palmar','Rønnestad','palmar.ronnestad@gmail.com','99011940','1954-01-18',NULL,NULL,'Nedre Røyra 1',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:33','2025-11-13 23:46:33'),('9a29db2fc0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A29DB2F','Sølvi','Røren',NULL,'97966560',NULL,NULL,NULL,'Vågsgata 45, 6090 Fosnavåg',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:33','2025-11-13 23:46:33'),('9a2a78dfc0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A2A78DF','Hans','Sande','hanssande@tussa.com','92459392',NULL,NULL,NULL,'Sandevegen  47 6095 Bølandet',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:33','2025-11-13 23:46:33'),('9a2b2b41c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A2B2B41','Petra','Sandvik',NULL,'45035591',NULL,NULL,NULL,'Tjørvågvegen 2 6070 Tjørvåg',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:33','2025-11-13 23:46:33'),('9a2c7295c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A2C7295','Oddvar','Skogen',NULL,'47857099',NULL,NULL,NULL,'Vågsgata 45, 6090 Fosnavåg',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:33','2025-11-13 23:46:33'),('9a2d1552c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A2D1552','Roar','Skoglund','rokriskog@gmail.com','91369350',NULL,NULL,NULL,'Svingen 8 6092 Fosnavåg',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:33','2025-11-13 23:46:33'),('9a2ddd8cc0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A2DDD8C','Arild','Slettestøl','arild_j@live.com','91688928',NULL,NULL,NULL,'Knotten 130 6082 Gursken',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:33','2025-11-13 23:46:33'),('9a2e94c5c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A2E94C5','Aslak','Solbjørg','asolborg@aolnorge.no','46891090',NULL,NULL,NULL,'Nautøya 125 6070 Tjørvåg',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:33','2025-11-13 23:46:33'),('9a2f44ffc0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A2F44FF','Oddveig','Stanghelle','oddveig_sta@hotmail.com','99649879',NULL,NULL,NULL,'Leinevegen 272 6094 Leinøy',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:33','2025-11-13 23:46:33'),('9a2fee2bc0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A2FEE2B','Liv','Bessie Stave','livstave@online.no','95768617',NULL,NULL,NULL,'Osebergvegen 5 6065 Ulsteinvik',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:33','2025-11-13 23:46:33'),('9a30d22ec0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A30D22E','Per','Egil Sørheller','peregilsorheller@gmail.com','45906393',NULL,NULL,NULL,'Brauta 3 6037 Eidsnes',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:33','2025-11-13 23:46:33'),('9a316f71c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A316F71','Kirsten','Teige',NULL,'40086106',NULL,NULL,NULL,'Teigeveien 47, 6098 Nerlandsøy',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:33','2025-11-13 23:46:33'),('9a320c7ac0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A320C7A','Oluf','Teige','oluf.teige@tussa.com','97525423','2025-09-15',NULL,NULL,'Teigeveien 47, 6098 Nerlandsøy',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:33','2025-11-13 23:46:33'),('9a32c698c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A32C698','Anne-Johanne','Tellefsen','anne.johanne.tellefsen@gmail.com','90846683',NULL,NULL,NULL,'Teigelia 6 6098 Nerlandsøy',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:33','2025-11-13 23:46:33'),('9a33895fc0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A33895F','Solfrid','Thorset',NULL,'95766727',NULL,NULL,NULL,'Leikongbakken 4, 6080 Gurskøy',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:33','2025-11-13 23:46:33'),('9a342a3dc0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A342A3D','Kjellmar','Vike','kjellmar50@gmail.com','90863096','2025-08-25',NULL,NULL,'Grønhaugvegen 34, 6092 Fosnavåg',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:33','2025-11-13 23:46:33'),('9a34dbb7c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A34DBB7','Laila','Vike','vennen53@hotmail.com','99162331','1963-06-15',NULL,NULL,'Grønhaugvegen 34, 6092 Fosnavåg',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:33','2025-11-13 23:46:33'),('9a358981c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A358981','Idar','Voldsund','idarvoldsund@tussa.com','99308221',NULL,NULL,NULL,'Arhaugvegen 14, 6095 Bølandet',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:33','2025-11-13 23:46:33'),('9a363e23c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A363E23','Anne-Marie','Westbø','annemawest4268@gmail.com','91804691',NULL,NULL,NULL,'Torvikvegen 43 6095 Bølandet',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:33','2025-11-13 23:46:33'),('9a379289c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A379289','Unni','Nerland Nerland','unni@nerland.io','45474106',NULL,NULL,NULL,'Svingen 8',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:33','2025-11-13 23:46:33'),('9a383f4ac0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A383F4A','Laila','Nedreberg Remøy Remøy','laila@remoy.org','40873948','1962-08-21',NULL,NULL,'Nedre plassgjerde 7',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:33','2025-11-13 23:46:33'),('9a38e3a4c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A38E3A4','Halvard','Leine','halvard.leine@tussa.com','95247887','1956-11-01',NULL,NULL,'Leine 41',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:33','2025-11-13 23:46:33'),('9a3998ebc0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A3998EB','Svein','Arne Leine','tull@one.no','97069105','1953-07-25',NULL,NULL,'bergslia 3',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:33','2025-11-13 23:46:33'),('9a3b0bb9c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A3B0BB9','Hildegunn','Øye','hildegunnoddrun@gmail.com','48030489','1955-03-14',NULL,NULL,'Øvre',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:33','2025-11-13 23:46:33'),('9a3bb950c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A3BB950','Kari','Iren Sandvik','kariharisan@hotmail.com','47863533','1953-04-15',NULL,NULL,'Eggesbøgrenda 6 A',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:33','2025-11-13 23:46:33'),('9a3c65a6c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A3C65A6','Alf','Inge Leine','alf.inge.leine@gmail.com','90636950',NULL,NULL,NULL,'Nedre Plassegjerdet 15',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:33','2025-11-13 23:46:33'),('9a3d1005c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A3D1005','Gry','Myrseth Myrseth','gry.myrseth@gmail.com','95793923','1961-12-11',NULL,NULL,'Hulldalsbana 4 A',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:33','2025-11-13 23:46:33'),('9a3db617c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A3DB617','Lise','Hauge Kvalsund','lise.kvalsund@hotmail.com','95058708','1959-08-29',NULL,NULL,'Bakkevn 9',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:33','2025-11-13 23:46:33'),('9a3e64b4c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A3E64B4','Oddgeir','Madsen Madsen','oddgeir.madsen@tussa.com','91681611','1955-05-21',NULL,NULL,'Teigelia 4',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:33','2025-11-13 23:46:33'),('9a3f1a62c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A3F1A62','Karen','Myrseth Moltumyr','ksmoltumyr@gmail.com','95966318',NULL,NULL,NULL,'Markveien 9',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:33','2025-11-13 23:46:33'),('9a3fc8b7c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A3FC8B7','Reidun','Frantzen','reidun.frantzen@hotmail.com','47044251','1965-11-18',NULL,NULL,'Toftestøvegen 24',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:33','2025-11-13 23:46:33'),('9a407409c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A407409','Vilfred','Pettersen',NULL,NULL,NULL,NULL,NULL,'INNHENT',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:33','2025-11-13 23:46:33'),('9a410ec1c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A410EC1','Karl','-Sverre Moltumyr','ksmolrumyr@gmail.com','48281188',NULL,NULL,NULL,'Merkveien 9',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:33','2025-11-13 23:46:33'),('9a41bd38c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A41BD38','Paul','Sandanger','paul.sandanger@tussa.com','908 95 585','1950-05-04',NULL,NULL,'Gjerdevegen 10',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:33','2025-11-13 23:46:33'),('9a426898c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A426898','Jorunn','Berge','lola58@live.no','91894452','1958-04-14',NULL,NULL,'Bergsvegen 39',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:33','2025-11-13 23:46:33'),('9a43542ec0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A43542E','Marina','Hoff Myking','marinamyking@hotmail.com','91586793',NULL,NULL,NULL,'Støylavegen 11A, 6094 Leinøy',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:33','2025-11-13 23:46:33'),('9a4423f2c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A4423F2','Susann','Nerland','susannno@yahoo.no','99393147','2025-04-15',NULL,NULL,'Landsvegen 18, 6098 Nerlandsøy',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:33','2025-11-13 23:46:33'),('9a44d32cc0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A44D32C','Toril','Zahl Remøy','toril.zahlr@hotmail.no','41646461','1965-08-22',NULL,NULL,'Myrane 6, 6094 Leinøy',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:33','2025-11-13 23:46:33'),('9a4585ffc0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A4585FF','Norunn','Kragseth','magne.kragseth@tussa.com','95433899',NULL,NULL,NULL,'Kopperstadvegen 47',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:33','2025-11-13 23:46:33'),('9a462ad4c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A462AD4','Bård','Remøy Remøy','baard_remoy@hotmail.com','95724601',NULL,NULL,NULL,'Kvanesveien 45, 6094 Leinøy',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:33','2025-11-13 23:46:33'),('9a46d709c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A46D709','Audhild','Kvalsvik','audhild.kvalsvik@outlook.com','99570874','1957-03-12',NULL,NULL,'Valvegen 1D, 6092 Fosnavåg',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:33','2025-11-13 23:46:33'),('9a4793d0c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A4793D0','Ole','Jonny Konningen','konningen@icloud.com','90996351','1959-02-09',NULL,NULL,'Nedre Plassegjerdet 11, 6098 Nerlandsøy',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:33','2025-11-13 23:46:33'),('9a484deec0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A484DEE','Marit','Kvalsund Konningen','marit.konningen@hotmail.com','92461475','1960-02-07',NULL,NULL,'Nedre Plassegjerdet 11, 6098 Vatne',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:33','2025-11-13 23:46:33'),('9a4901b4c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A4901B4','Eivind','Longva','elongva@gmail.com','93027251','1959-10-23',NULL,NULL,'Vågsholmvegen 19, 6090 FosnavÅg',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:33','2025-11-13 23:46:33'),('9a49bc79c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A49BC79','Mona','Zahl Longva','monazl@hotmail.com','995 01 291',NULL,NULL,NULL,'Vågsholmvegen 19, 6090 Fosnavåg',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:33','2025-11-13 23:46:33'),('9a4a6f57c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A4A6F57','Per','Jarle Skar',NULL,'95813437',NULL,NULL,NULL,'Demingane 14, 6092 Fosnavåg',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:33','2025-11-13 23:46:33'),('9a4b06f5c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A4B06F5','Venche','Lannerholm','wenchelann@hotmail.com','95498886',NULL,NULL,NULL,'Knotten 130, 6082 Gursken',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:33','2025-11-13 23:46:33'),('9a4bbec5c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A4BBEC5','Karin','Bø','karin.bo15@gmail.com','41224947',NULL,NULL,NULL,'Sandevegen 6, 6095 Bølandet',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:33','2025-11-13 23:46:33'),('9a4c8009c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A4C8009','Sigurd','Notøy','sigmarnotoy@gmail.com','90645690',NULL,NULL,NULL,'Stornesvegen 8, 6070 Tjørvåg',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:33','2025-11-13 23:46:33'),('9a4d3490c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A4D3490','Bergljot','Myrseth',NULL,'90025016',NULL,NULL,NULL,'Mikkelhaugen 9,, 6092 Fosnavåg',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:33','2025-11-13 23:46:33'),('9a4de4e5c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A4DE4E5','Jostein','Mo','jo-i-mo@hotmail.com','90683835',NULL,NULL,NULL,'Mosgeila, 6154 Ørsta',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:33','2025-11-13 23:46:33'),('9a4eaf39c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A4EAF39','Anne','Kristensen',NULL,'94891113',NULL,NULL,NULL,'Storbakken 2, 6092 Fosnavåg',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:33','2025-11-13 23:46:33'),('9a4f54b3c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A4F54B3','Oddveig','GARVIK','Oddveig.petrin.garvik@outlook.com','99611182',NULL,NULL,NULL,'Mikkelhaugen 6, 6092 Fosnavåg',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:33','2025-11-13 23:46:33'),('9a500ae4c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A500AE4','Laila','Berge','berge_laila35@hotmail.com','41498722','1959-11-16',NULL,NULL,'Elsebøveien 17, 6091 Fosnavåg',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:33','2025-11-13 23:46:33'),('9a50cc39c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A50CC39','Guttorm','Berge','gube53@hotmail.com','97777840','1953-05-11',NULL,NULL,'Flusundvegen 53, 6091 Fosnavåg',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:33','2025-11-13 23:46:33'),('9a517c49c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A517C49','Line','Nygard','linenyga@hotmail.com','957 25 121','1969-02-18',NULL,NULL,'Flusundvegen 53, 6091 Fosnavåg',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:33','2025-11-13 23:46:33'),('9a522722c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A522722','Anita','H Kvalsund','anitah.kvalsund@hotmail.com','91107083','1959-07-17',NULL,NULL,'Koperstadvegen 43, 6098 NERLANDSØY',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:33','2025-11-13 23:46:33'),('9a52dd61c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A52DD61','Runar','Kvalsund','runar.kvalsund@tussa.com','47015195','1958-02-21',NULL,NULL,'Koperstadvegen 43, 6098 Nerlandsøy',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:33','2025-11-13 23:46:33'),('9a53948cc0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A53948C','Perny','Hilde Kvalsvik','pernyh58@gmail.com','92842285','1958-09-19',NULL,NULL,'Nesevegen 5, 6065 Ulstein',NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:33','2025-11-13 23:46:33'),('9a544a60c0e211f08af7b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c','CUST-9A544A60','Trond','Tellefsen','trond.tellefsen@oneco.no','91729150',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'individual','regular',NULL,1,'active',NULL,'2025-11-13 23:46:33','2025-11-13 23:46:33');
UNLOCK TABLES;
commit;

--
-- Table structure for table `tenant_email_config`
--

DROP TABLE IF EXISTS `tenant_email_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tenant_email_config` (
  `id` char(36) NOT NULL,
  `company_id` char(36) NOT NULL,
  `smtp_host` varchar(255) DEFAULT NULL,
  `smtp_port` int(11) DEFAULT NULL,
  `smtp_encryption` varchar(10) DEFAULT NULL,
  `smtp_username` varchar(255) DEFAULT NULL,
  `smtp_password` varchar(255) DEFAULT NULL,
  `smtp_from_email` varchar(255) DEFAULT NULL,
  `smtp_from_name` varchar(255) DEFAULT NULL,
  `imap_host` varchar(255) DEFAULT NULL,
  `imap_port` int(11) DEFAULT NULL,
  `imap_encryption` varchar(10) DEFAULT NULL,
  `imap_username` varchar(255) DEFAULT NULL,
  `imap_password` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_id` (`company_id`),
  CONSTRAINT `tenant_email_config_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tenant_email_config`
--

LOCK TABLES `tenant_email_config` WRITE;
set autocommit=0;
INSERT INTO `tenant_email_config` VALUES ('','7efbddea-b507-11f0-936b-b02628506e5c','smtp.domeneshop.no',587,'tls','salesman4','ibis-vevre-Nop-bulka-1700','bergsoy.d-k@salesman.no','Bergsøy Danseklubb','imap.domeneshop.no',993,'ssl','salesman4','ibis-vevre-Nop-bulka-1700','2025-11-04 05:32:07','2025-11-04 07:56:36');
UNLOCK TABLES;
commit;

--
-- Table structure for table `tenant_settings`
--

DROP TABLE IF EXISTS `tenant_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tenant_settings` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `company_id` char(36) NOT NULL,
  `setting_key` varchar(255) NOT NULL,
  `setting_value` text NOT NULL,
  `setting_type` varchar(20) DEFAULT 'string',
  `category` varchar(100) DEFAULT 'general',
  `description` text DEFAULT NULL,
  `is_public` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_id` (`company_id`,`setting_key`),
  KEY `idx_tenant_settings_company_key` (`company_id`,`setting_key`),
  CONSTRAINT `tenant_settings_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tenant_settings`
--

LOCK TABLES `tenant_settings` WRITE;
set autocommit=0;
INSERT INTO `tenant_settings` VALUES ('7750680be9f6b83abfeb7521e896b03ac67a','a7eb3dbc5543932549d02a0bfd2b79720ad5','','','string','general',NULL,0,'2025-11-20 15:45:54','2025-11-20 15:45:54'),('ed7681168a69481c9752956bc6aefd05f1fa','a9cdf14b6790d0247b55534d68065eb695f9','','','string','general',NULL,0,'2025-11-13 16:27:41','2025-11-13 16:27:41');
UNLOCK TABLES;
commit;

--
-- Table structure for table `tenant_staff`
--

DROP TABLE IF EXISTS `tenant_staff`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tenant_staff` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tenant_staff`
--

LOCK TABLES `tenant_staff` WRITE;
set autocommit=0;
INSERT INTO `tenant_staff` VALUES ('7efffc84-b507-11f0-936b-b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c',NULL,NULL,'Marita Higraf','Remøy','maritahr@gmail.com','90981991','1966-04-14',NULL,NULL,'full_time',NULL,NULL,'Nedre plassgjerde','Nerlandsøy',NULL,'6098',NULL,NULL,NULL,1,NULL,'2025-10-29 21:40:24','2025-10-29 21:40:24'),('7f0980cf-b507-11f0-936b-b02628506e5c','7efbddea-b507-11f0-936b-b02628506e5c',NULL,NULL,'Atle Karl','Kvalsvik','atlekarl@tussa.com','95918925','1956-04-08',NULL,NULL,'full_time',NULL,NULL,'Mulevikvegen 42','Nerlandsøy',NULL,'6098',NULL,NULL,NULL,1,NULL,'2025-10-29 21:40:24','2025-10-29 21:40:24'),('a355a634b40eb972506264982f4448a4d296','a9cdf14b6790d0247b55534d68065eb695f9',NULL,NULL,'Gjert','Fjert','keke@leke.no','40404040',NULL,'Klovn',NULL,'full_time',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,'2025-11-13 16:28:13','2025-11-13 16:28:13'),('c7323d96765fdd747611d47c286171a03504','7efbddea-b507-11f0-936b-b02628506e5c',NULL,NULL,'Jossi','Kakskiv','solvihje@gmail.com','48361707',NULL,'Führer',NULL,'full_time',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,'2025-11-04 07:57:51','2025-11-04 07:57:51');
UNLOCK TABLES;
commit;

--
-- Table structure for table `tenant_staff_modules`
--

DROP TABLE IF EXISTS `tenant_staff_modules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tenant_staff_modules` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `staff_id` char(36) NOT NULL,
  `module_id` char(36) NOT NULL,
  `granted_by` char(36) NOT NULL,
  `granted_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `staff_id` (`staff_id`,`module_id`),
  KEY `module_id` (`module_id`),
  KEY `idx_tenant_staff_modules_granted_by` (`granted_by`),
  CONSTRAINT `tenant_staff_modules_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `tenant_staff` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tenant_staff_modules_ibfk_2` FOREIGN KEY (`module_id`) REFERENCES `system_modules` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tenant_staff_modules_ibfk_3` FOREIGN KEY (`granted_by`) REFERENCES `saas_admins` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tenant_staff_modules`
--

LOCK TABLES `tenant_staff_modules` WRITE;
set autocommit=0;
UNLOCK TABLES;
commit;

--
-- Table structure for table `tenant_subscriptions`
--

DROP TABLE IF EXISTS `tenant_subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tenant_subscriptions` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `company_id` char(36) NOT NULL,
  `self_company_id` char(36) NOT NULL,
  `plan_id` char(36) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'active',
  `billing_cycle` varchar(20) DEFAULT 'monthly',
  `current_period_start` datetime DEFAULT NULL,
  `current_period_end` datetime DEFAULT NULL,
  `stripe_subscription_id` varchar(255) DEFAULT NULL,
  `stripe_customer_id` varchar(255) DEFAULT NULL,
  `canceled_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `company_id` (`company_id`),
  KEY `self_company_id` (`self_company_id`),
  KEY `plan_id` (`plan_id`),
  CONSTRAINT `tenant_subscriptions_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tenant_subscriptions_ibfk_3` FOREIGN KEY (`plan_id`) REFERENCES `saas_subscription_plans` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tenant_subscriptions`
--

LOCK TABLES `tenant_subscriptions` WRITE;
set autocommit=0;
INSERT INTO `tenant_subscriptions` VALUES ('11f975759a5eec4c79173d9de35620992947','a9cdf14b6790d0247b55534d68065eb695f9','',NULL,'active','monthly',NULL,NULL,NULL,NULL,NULL,'2025-11-13 16:27:41','2025-11-13 16:27:41'),('6451fcd36a8a3481ec6f4a30e12d44d504e3','a7eb3dbc5543932549d02a0bfd2b79720ad5','',NULL,'active','monthly',NULL,NULL,NULL,NULL,NULL,'2025-11-20 15:45:54','2025-11-20 15:45:54');
UNLOCK TABLES;
commit;

--
-- Table structure for table `tenant_terms_acceptances`
--

DROP TABLE IF EXISTS `tenant_terms_acceptances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tenant_terms_acceptances` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `terms_conditions_id` char(36) NOT NULL,
  `customer_id` char(36) DEFAULT NULL,
  `user_type` varchar(20) NOT NULL DEFAULT 'customer',
  `user_id` char(36) DEFAULT NULL,
  `user_email` varchar(255) NOT NULL,
  `user_name` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `accepted_at` datetime DEFAULT current_timestamp(),
  `module_context` varchar(50) DEFAULT NULL COMMENT 'Which module triggered acceptance',
  `transaction_id` char(36) DEFAULT NULL COMMENT 'Related transaction/sale/booking',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_acceptance` (`terms_conditions_id`,`customer_id`,`module_context`,`transaction_id`),
  KEY `idx_terms_acceptances_customer` (`customer_id`),
  KEY `idx_terms_acceptances_terms` (`terms_conditions_id`),
  KEY `idx_terms_acceptances_module` (`module_context`),
  KEY `idx_terms_acceptances_date` (`accepted_at`),
  CONSTRAINT `tenant_terms_acceptances_ibfk_1` FOREIGN KEY (`terms_conditions_id`) REFERENCES `tenant_terms_conditions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tenant_terms_acceptances_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `tenant_customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tenant_terms_acceptances`
--

LOCK TABLES `tenant_terms_acceptances` WRITE;
set autocommit=0;
UNLOCK TABLES;
commit;

--
-- Table structure for table `tenant_terms_conditions`
--

DROP TABLE IF EXISTS `tenant_terms_conditions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tenant_terms_conditions` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `company_id` char(36) NOT NULL,
  `title` varchar(255) NOT NULL DEFAULT 'Kjøpsbetingelser',
  `content` text NOT NULL,
  `version` varchar(20) NOT NULL DEFAULT '1.0',
  `effective_date` date NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `applies_to_modules` text DEFAULT NULL COMMENT 'JSON array of module IDs or "all"',
  `requires_customer_acceptance` tinyint(1) DEFAULT 1,
  `acceptance_type` enum('checkbox','signature','implicit') DEFAULT 'checkbox',
  `created_by` char(36) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_company_version` (`company_id`,`version`),
  KEY `idx_tenant_terms_company` (`company_id`),
  KEY `idx_tenant_terms_active` (`is_active`,`effective_date`),
  KEY `tenant_terms_conditions_ibfk_2` (`created_by`),
  CONSTRAINT `tenant_terms_conditions_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tenant_terms_conditions_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `tenant_admins` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tenant_terms_conditions`
--

LOCK TABLES `tenant_terms_conditions` WRITE;
set autocommit=0;
INSERT INTO `tenant_terms_conditions` VALUES ('309c4654-562b-4c6d-ac44-3e9c7b0d4649','7efbddea-b507-11f0-936b-b02628506e5c','Kjøpsbetingelser','KJØPSBETINGELSER FOR BERGSØY DANSEKLUBB\n\n§ 1 ALMINNELIGE BESTEMMELSER\nDisse kjøpsbetingelsene gjelder for alle billetter, varer og tjenester kjøpt gjennom Bergsøy danseklubb. Ved kjøp godtar kunden disse betingelsene.\n\n§ 2 BILLETTER TIL ARRANGEMENTER\n- Billetter kan kjøpes online, i døren eller på forhånd etter avtale\n- Billetter refunderes ikke ved avlysning fra kundens side\n- Ved avlysning fra arrangørens side refunderes billetten fullt ut\n- Billetten er personlig og kan ikke videreselges til en høyere pris enn opprinnelig kjøpspris\n\n§ 3 KJØP AV VARER I KIOSKEN\n- Alle priser er i NOK inkl. mva\n- Varer kan returneres kun ved produksjonsfeil\n- Forbruksvarer (mat/drikke) refunderes ikke\n- Kjøp gjort i kiosken kan ikke refunderes etter at varene er forlatt lokalet\n\n§ 4 MEDLEMSKAP\n- Medlemskap løper i 12 måneder fra kjøpsdato\n- Medlemsavgift refunderes ikke ved oppsigelse\n- Medlemsfordeler gjelder kun for inneværende medlemskap\n\n§ 5 ANSVAR\nBergsøy danseklubb er ikke ansvarlig for:\n- Tapte eller stjålne billetter\n- Personskader under arrangementer\n- Tap av eller skade på personlige eiendeler\n- Endringer i program eller artister\n\n§ 6 PERSONVERN\nVi behandler personopplysninger i henhold til personvermloven. Opplysninger brukes kun til administrasjon av kjøp og medlemskap.\n\n§ 7 TVANG OG KLAGER\nKjøp omfattes av forbrukerkjøpsloven. Klager rettes til Bergsøy danseklubb innen 14 dager.\n\n§ 8 ENDRINGER\nBergsøy danseklubb forbeholder seg retten til å endre disse betingelsene. Gjeldende versjon finnes alltid på vår hjemmeside.\n\nKontakt: bergsoy.danseklubb@example.org | Tlf: 123 45 678\n','1.0','2025-11-21',1,NULL,1,'checkbox','7f072d6a-b507-11f0-936b-b02628506e5c','2025-11-21 22:08:05','2025-11-21 22:08:05');
UNLOCK TABLES;
commit;

--
-- Table structure for table `tenant_website_config`
--

DROP TABLE IF EXISTS `tenant_website_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tenant_website_config` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `company_id` char(36) NOT NULL,
  `module_id` char(36) NOT NULL,
  `is_enabled` tinyint(1) DEFAULT 1,
  `is_public` tinyint(1) DEFAULT 1,
  `show_in_navigation` tinyint(1) DEFAULT 1,
  `display_name` varchar(255) DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `navigation_icon` varchar(100) DEFAULT NULL,
  `custom_content` text DEFAULT NULL,
  `about_us_content` text DEFAULT NULL,
  `template_data` text DEFAULT NULL,
  `style_config` text DEFAULT NULL,
  `layout_config` text DEFAULT NULL,
  `color_scheme` varchar(50) DEFAULT 'default',
  `seo_title` varchar(255) DEFAULT NULL,
  `seo_description` text DEFAULT NULL,
  `seo_keywords` text DEFAULT NULL,
  `canonical_url` varchar(500) DEFAULT NULL,
  `allowed_roles` text DEFAULT NULL,
  `access_password` varchar(255) DEFAULT NULL,
  `is_published` tinyint(1) DEFAULT 1,
  `published_at` datetime DEFAULT NULL,
  `unpublished_at` datetime DEFAULT NULL,
  `created_by` char(36) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `updated_by` char(36) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_id` (`company_id`,`module_id`),
  KEY `module_id` (`module_id`),
  KEY `created_by` (`created_by`),
  KEY `updated_by` (`updated_by`),
  KEY `idx_website_config_company` (`company_id`),
  KEY `idx_website_config_public` (`is_public`,`is_enabled`,`is_published`),
  KEY `idx_website_config_navigation` (`show_in_navigation`,`display_order`),
  CONSTRAINT `tenant_website_config_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tenant_website_config_ibfk_2` FOREIGN KEY (`module_id`) REFERENCES `system_modules` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tenant_website_config_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `tenant_admins` (`id`),
  CONSTRAINT `tenant_website_config_ibfk_4` FOREIGN KEY (`updated_by`) REFERENCES `tenant_admins` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tenant_website_config`
--

LOCK TABLES `tenant_website_config` WRITE;
set autocommit=0;
UNLOCK TABLES;
commit;

--
-- Table structure for table `tenant_website_general`
--

DROP TABLE IF EXISTS `tenant_website_general`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tenant_website_general` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `company_id` char(36) NOT NULL,
  `site_title` varchar(255) DEFAULT NULL,
  `site_description` text DEFAULT NULL,
  `logo_url` varchar(500) DEFAULT NULL,
  `favicon_url` varchar(500) DEFAULT NULL,
  `primary_color` varchar(7) DEFAULT '#3366CC',
  `secondary_color` varchar(7) DEFAULT '#666666',
  `header_layout` varchar(50) DEFAULT 'standard',
  `footer_layout` varchar(50) DEFAULT 'standard',
  `navigation_style` varchar(50) DEFAULT 'top',
  `homepage_module` char(36) DEFAULT NULL,
  `hero_enabled` tinyint(1) DEFAULT 1,
  `hero_title` varchar(255) DEFAULT NULL,
  `hero_description` text DEFAULT NULL,
  `hero_image_url` varchar(500) DEFAULT NULL,
  `hero_button_text` varchar(100) DEFAULT NULL,
  `hero_button_url` varchar(500) DEFAULT NULL,
  `contact_email` varchar(255) DEFAULT NULL,
  `contact_phone` varchar(50) DEFAULT NULL,
  `contact_address` text DEFAULT NULL,
  `social_media_links` text DEFAULT NULL,
  `footer_text` text DEFAULT NULL,
  `footer_links` text DEFAULT NULL,
  `custom_css` text DEFAULT NULL,
  `custom_js` text DEFAULT NULL,
  `google_analytics_id` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `maintenance_mode` tinyint(1) DEFAULT 0,
  `maintenance_message` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `header_bg_color` varchar(20) DEFAULT '#ffffff',
  `body_bg_color` varchar(20) DEFAULT '#f8f9fa',
  `logo_height` varchar(10) DEFAULT '50px',
  `header_height` varchar(10) DEFAULT '80px',
  `logo_position` varchar(20) DEFAULT 'left',
  `nav_position` varchar(20) DEFAULT 'center',
  `font_family` varchar(100) DEFAULT 'Inter, sans-serif',
  `button_style` varchar(20) DEFAULT 'rounded',
  `border_radius` varchar(10) DEFAULT '16px',
  `spacing_unit` varchar(10) DEFAULT '1rem',
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_id` (`company_id`),
  KEY `homepage_module` (`homepage_module`),
  CONSTRAINT `tenant_website_general_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tenant_website_general_ibfk_2` FOREIGN KEY (`homepage_module`) REFERENCES `system_modules` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tenant_website_general`
--

LOCK TABLES `tenant_website_general` WRITE;
set autocommit=0;
INSERT INTO `tenant_website_general` VALUES ('0af6c4341feb32903a98b863c64570b81d03','a7eb3dbc5543932549d02a0bfd2b79720ad5',NULL,NULL,NULL,NULL,'#3366CC','#666666','standard','standard','top',NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL,'2025-11-20 15:45:54','2025-11-20 15:45:54','#ffffff','#f8f9fa','50px','80px','left','center','Inter, sans-serif','rounded','16px','1rem'),('5199685f7757432ed30a4e60bd77b9a10cea','a9cdf14b6790d0247b55534d68065eb695f9',NULL,NULL,NULL,NULL,'#3366CC','#666666','standard','standard','top',NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,0,NULL,'2025-11-13 16:27:41','2025-11-13 16:27:41','#ffffff','#f8f9fa','50px','80px','left','center','Inter, sans-serif','rounded','16px','1rem');
UNLOCK TABLES;
commit;

--
-- Table structure for table `unified_ledger`
--

DROP TABLE IF EXISTS `unified_ledger`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `unified_ledger` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `order_id` varchar(100) NOT NULL,
  `company_id` char(36) NOT NULL,
  `module_source` varchar(50) NOT NULL,
  `item_type` varchar(50) NOT NULL,
  `source_id` char(36) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `selling_price` decimal(10,2) NOT NULL,
  `cost_price` decimal(10,2) DEFAULT NULL,
  `customer_name` varchar(255) DEFAULT NULL,
  `customer_email` varchar(255) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_status` varchar(50) DEFAULT 'completed',
  `status` varchar(50) DEFAULT 'active',
  `transaction_date` datetime NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_transaction` (`module_source`,`source_id`),
  KEY `idx_unified_ledger_company` (`company_id`),
  KEY `idx_unified_ledger_module` (`module_source`,`item_type`),
  KEY `idx_unified_ledger_order` (`order_id`),
  KEY `idx_unified_ledger_date` (`transaction_date`),
  KEY `idx_unified_ledger_customer` (`customer_email`),
  KEY `idx_unified_ledger_status` (`status`),
  CONSTRAINT `unified_ledger_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `unified_ledger`
--

LOCK TABLES `unified_ledger` WRITE;
set autocommit=0;
INSERT INTO `unified_ledger` VALUES ('0b786da9-eea0-4eac-a892-e0af6110a9c4','ORD-20251030-78346B','7efbddea-b507-11f0-936b-b02628506e5c','sales','product','12','Pølse med brød',3,120.00,15.00,'Walk-in',NULL,'cash','completed','active','2025-10-30 06:58:02','2025-10-30 07:58:03','{\"product_id\":\"16ad682f97547c48b3a2d78cb9aea61d5251\",\"unit_price\":\"40.00\",\"notes\":\"\",\"event_id\":\"b53bd9a0d795f7f5114b1826d08e2e41\"}'),('5c0a1fa4-24b8-4e38-83e5-37da7085ab42','ORD-20251029-867076','7efbddea-b507-11f0-936b-b02628506e5c','sales','product','10','Pølse med brød',1,40.00,5.00,'Walk-in',NULL,'cash','completed','active','2025-10-29 22:20:13','2025-10-29 22:20:13','{\"product_id\":\"16ad682f97547c48b3a2d78cb9aea61d5251\",\"unit_price\":\"40.00\",\"notes\":\"\"}'),('746adc55-94ea-444f-ac21-85697a6bcb84','ORD-20251029-3987D5','7efbddea-b507-11f0-936b-b02628506e5c','sales','product','5','Pølse med brød',1,40.00,5.00,'Walk-in',NULL,'cash','completed','active','2025-10-29 22:17:34','2025-10-29 22:17:34','{\"product_id\":\"16ad682f97547c48b3a2d78cb9aea61d5251\",\"unit_price\":\"40.00\",\"notes\":\"\"}'),('c2ca0602-d9a6-4ceb-a8c5-0266f2a15d64','ORD-20251029-867076','7efbddea-b507-11f0-936b-b02628506e5c','sales','product','11','Cola',1,40.00,15.00,'Walk-in',NULL,'cash','completed','active','2025-10-29 22:20:13','2025-10-29 22:20:13','{\"product_id\":\"2f5d1e6bdd2ba279943b2d880e6c246bdfce\",\"unit_price\":\"40.00\",\"notes\":\"\"}'),('c678e10e-64da-49f3-9af0-a4ea1e72c1aa','ORD-20251029-23D855','7efbddea-b507-11f0-936b-b02628506e5c','sales','product','8','Cola',2,80.00,30.00,'Walk-in',NULL,'card','completed','active','2025-10-29 22:18:45','2025-10-29 22:18:45','{\"product_id\":\"2f5d1e6bdd2ba279943b2d880e6c246bdfce\",\"unit_price\":\"40.00\",\"notes\":\"\"}'),('cbaed707-c23a-46c2-bff7-f2f82ae87184','ORD-20251029-23D855','7efbddea-b507-11f0-936b-b02628506e5c','sales','product','9','Pølse med brød',3,120.00,15.00,'Walk-in',NULL,'card','completed','active','2025-10-29 22:18:45','2025-10-29 22:18:45','{\"product_id\":\"16ad682f97547c48b3a2d78cb9aea61d5251\",\"unit_price\":\"40.00\",\"notes\":\"\"}'),('d0371d25-ee68-46e3-857b-ba33a302d588','ORD-20251029-46F48B','7efbddea-b507-11f0-936b-b02628506e5c','sales','product','4','Cola',1,40.00,15.00,'Walk-in','atlekarl','cash','completed','active','2025-10-29 21:53:05','2025-10-29 21:53:05','{\"product_id\":\"2f5d1e6bdd2ba279943b2d880e6c246bdfce\",\"unit_price\":\"40.00\",\"notes\":\"\"}'),('da949209-89cf-4376-90c8-90a5860f443b','EVENT-20251029-0B5559','7efbddea-b507-11f0-936b-b02628506e5c','events','event','e27e8c6a0e45a6a57802b376b81184a2','dynamitt testing (Medlemspris)',1,500.00,0.00,'Magne Kvalsvik','magnekvalsvique@gmail.com','cash','completed','active','2025-10-29 22:03:59','2025-10-29 22:03:59','{\"event_id\":\"b53bd9a0d795f7f5114b1826d08e2e41\",\"is_member_price\":true}'),('dc91b7c5-621f-493b-9abb-9619b0947d8b','MEMBERSHIP-20251030-DF5BEE','7efbddea-b507-11f0-936b-b02628506e5c','membership','membership','0','Gjerrigknark',1,150.00,NULL,'Makkus Kulesen','maku@lesen.no','cash','completed','active','2025-10-30 06:46:00','2025-10-30 07:46:00','{\"plan_id\":\"ef57db8d66a47a2f7c8e88eed304c971\"}'),('f4fe2bac-8b3e-402d-a43a-7402493608d8','ORD-20251029-9430BD','7efbddea-b507-11f0-936b-b02628506e5c','sales','product','6','Pølse med brød',2,80.00,10.00,'Walk-in',NULL,'cash','completed','active','2025-10-29 22:17:59','2025-10-29 22:17:59','{\"product_id\":\"16ad682f97547c48b3a2d78cb9aea61d5251\",\"unit_price\":\"40.00\",\"notes\":\"\"}'),('fde89f60-7404-4ee3-ac30-bdffcce3c879','ORD-20251029-9430BD','7efbddea-b507-11f0-936b-b02628506e5c','sales','product','7','Cola',2,80.00,30.00,'Walk-in',NULL,'cash','completed','active','2025-10-29 22:17:59','2025-10-29 22:17:59','{\"product_id\":\"2f5d1e6bdd2ba279943b2d880e6c246bdfce\",\"unit_price\":\"40.00\",\"notes\":\"\"}');
UNLOCK TABLES;
commit;

--
-- Dumping routines for database 'salesmanno04'
--
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
/*!50003 DROP FUNCTION IF EXISTS `VerifyCompanyPassword` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
DELIMITER ;;
CREATE DEFINER=`salesmanno04`@`2a01:5b40:0:%` FUNCTION `VerifyCompanyPassword`(p_company_id CHAR(36),
    p_password_hash VARCHAR(255)
) RETURNS tinyint(4)
    READS SQL DATA
    DETERMINISTIC
BEGIN
    DECLARE v_stored_hash VARCHAR(255);
    DECLARE v_result TINYINT DEFAULT 0;
    
    SELECT admin_password_hash INTO v_stored_hash 
    FROM tenant_companies 
    WHERE id = p_company_id AND is_active = 1;
    
    IF v_stored_hash IS NOT NULL AND v_stored_hash = p_password_hash THEN
        SET v_result = 1;
    END IF;
    
    RETURN v_result;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `ChangeCompanyPassword` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
DELIMITER ;;
CREATE DEFINER=`salesmanno04`@`2a01:5b40:0:%` PROCEDURE `ChangeCompanyPassword`(
    IN p_company_id CHAR(36),
    IN p_current_password VARCHAR(255),
    IN p_new_password_hash VARCHAR(255),
    OUT p_success TINYINT,
    OUT p_message VARCHAR(255)
)
BEGIN
    DECLARE v_current_hash VARCHAR(255);
    DECLARE v_company_exists INT DEFAULT 0;
    
    -- Check if company exists
    SELECT COUNT(*) INTO v_company_exists 
    FROM tenant_companies 
    WHERE id = p_company_id AND is_active = 1;
    
    IF v_company_exists = 0 THEN
        SET p_success = 0;
        SET p_message = 'Company not found or inactive';
    ELSE
        -- Get current password hash
        SELECT admin_password_hash INTO v_current_hash 
        FROM tenant_companies 
        WHERE id = p_company_id;
        
        -- Verify current password (in real implementation, you'd use proper password verification)
        IF v_current_hash IS NULL OR v_current_hash = p_current_password THEN
            -- Update password
            UPDATE tenant_companies 
            SET 
                admin_password_hash = p_new_password_hash,
                password_last_changed = CURRENT_TIMESTAMP(),
                password_reset_required = 0
            WHERE id = p_company_id;
            
            SET p_success = 1;
            SET p_message = 'Password updated successfully';
        ELSE
            SET p_success = 0;
            SET p_message = 'Current password is incorrect';
        END IF;
    END IF;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `LinkEventResultToLedger` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
DELIMITER ;;
CREATE DEFINER=`salesmanno04`@`2a01:5b40:0:%` PROCEDURE `LinkEventResultToLedger`(
IN p_event_result_id CHAR(36)
)
BEGIN
DECLARE v_event_id CHAR(36);
DECLARE v_company_id CHAR(36);
DECLARE v_total_revenue DECIMAL(10,2);
DECLARE v_ledger_id CHAR(36);

-- Get event result data
SELECT er.event_id, er.company_id, er.total_revenue
INTO v_event_id, v_company_id, v_total_revenue
FROM events_results er
WHERE er.id = p_event_result_id;
-- Find matching ledger entry
SELECT id INTO v_ledger_id
FROM unified_ledger 
WHERE company_id = v_company_id 
AND module_source = 'events'
AND source_id = v_event_id
LIMIT 1;
-- Update events_results with ledger ID
UPDATE events_results 
SET unified_ledger_id = v_ledger_id
WHERE id = p_event_result_id;
SELECT v_ledger_id as ledger_id;

END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `SafeDeleteCompany` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
DELIMITER ;;
CREATE DEFINER=`salesmanno04`@`%` PROCEDURE `SafeDeleteCompany`(IN p_company_id CHAR(36))
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- Deaktiver FK checks midlertidig for å tillate sletting i ønsket rekkefølge
    SET FOREIGN_KEY_CHECKS = 0;
    
    -- FASE 1: Slett barn-tabeller som ikke har direkte company_id
    -- Må slettes før parent-tabellene
    DELETE FROM events_checkin 
    WHERE event_id IN (SELECT id FROM events_eventdata WHERE company_id = p_company_id);
    
    DELETE FROM sales_transactions 
    WHERE company_id = p_company_id;
    
    DELETE FROM sales_orders_items 
    WHERE order_id IN (SELECT id FROM sales_orders WHERE company_id = p_company_id);
    
    DELETE FROM reservation_service_bookings 
    WHERE service_id IN (SELECT id FROM reservation_services WHERE company_id = p_company_id);
    
    DELETE FROM tenant_admin_sessions 
    WHERE tenant_admin_id IN (SELECT id FROM tenant_admins WHERE company_id = p_company_id);
    
    DELETE FROM tenant_admin_modules 
    WHERE tenant_admin_id IN (SELECT id FROM tenant_admins WHERE company_id = p_company_id);
    
    DELETE FROM tenant_staff_modules 
    WHERE staff_id IN (SELECT id FROM tenant_staff WHERE company_id = p_company_id);
    
    -- FASE 2: Slett hovedtabeller med company_id i riktig rekkefølge
    -- Rekkefølge: modul-data først, deretter konfigurasjoner, til slutt brukere
    DELETE FROM tenant_company_modules WHERE company_id = p_company_id;
    DELETE FROM tenant_announcements WHERE company_id = p_company_id;
    DELETE FROM tenant_website_config WHERE company_id = p_company_id;
    DELETE FROM tenant_website_general WHERE company_id = p_company_id;
    DELETE FROM tenant_settings WHERE company_id = p_company_id;
    DELETE FROM tenant_subscriptions WHERE company_id = p_company_id;
    
    -- Events modul
    DELETE FROM events_results WHERE company_id = p_company_id;
    DELETE FROM events_eventdata WHERE company_id = p_company_id;
    
    -- Membership modul
    DELETE FROM membership_members WHERE company_id = p_company_id;
    DELETE FROM membership_plans WHERE company_id = p_company_id;
    
    -- Reservation modul
    DELETE FROM reservation_services WHERE company_id = p_company_id;
    
    -- Sales modul
    DELETE FROM sales_orders WHERE company_id = p_company_id;
    DELETE FROM sales_products WHERE company_id = p_company_id;
    DELETE FROM sales_cart WHERE company_id = p_company_id;
    
    -- Rental modul
    DELETE FROM rental_objects WHERE company_id = p_company_id;
    DELETE FROM rental_schedule WHERE company_id = p_company_id;
    
    -- Service og økonomi
    DELETE FROM service_bookings WHERE company_id = p_company_id;
    DELETE FROM unified_ledger WHERE company_id = p_company_id;
    
    -- Kunder, ansatte og admins
    DELETE FROM tenant_customers WHERE company_id = p_company_id;
    DELETE FROM tenant_admins WHERE company_id = p_company_id;
    DELETE FROM tenant_staff WHERE company_id = p_company_id;
    
    -- FASE 3: Slett selve bedriften (sist!)
    DELETE FROM tenant_companies WHERE id = p_company_id;
    
    -- Re-aktiver FK checks
    SET FOREIGN_KEY_CHECKS = 1;
    
    COMMIT;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `UpsertCustomer` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
DELIMITER ;;
CREATE DEFINER=`salesmanno04`@`2a01:5b40:0:%` PROCEDURE `UpsertCustomer`(
    IN p_company_id CHAR(36),
    IN p_first_name VARCHAR(255),
    IN p_last_name VARCHAR(255),
    IN p_email VARCHAR(255),
    IN p_phone VARCHAR(255),
    IN p_customer_type VARCHAR(20)
)
BEGIN
    DECLARE v_customer_id CHAR(36);
    DECLARE v_customer_code VARCHAR(100);
    
    -- Søk etter eksisterende kunde (e-post har prioritet)
    SELECT id INTO v_customer_id 
    FROM tenant_customers 
    WHERE company_id = p_company_id 
    AND (email = p_email OR phone = p_phone)
    LIMIT 1;
    
    IF v_customer_id IS NOT NULL THEN
        -- Oppdater eksisterende kunde
        UPDATE tenant_customers 
        SET 
            first_name = COALESCE(p_first_name, first_name),
            last_name = COALESCE(p_last_name, last_name),
            phone = COALESCE(p_phone, phone),
            customer_type = COALESCE(p_customer_type, customer_type),
            updated_at = NOW()
        WHERE id = v_customer_id;
    ELSE
        -- Opprett ny kunde
        SET v_customer_id = UUID();
        SET v_customer_code = CONCAT('CUST', DATE_FORMAT(NOW(), '%Y%m%d'), '_', LEFT(UUID(), 8));
        
        INSERT INTO tenant_customers (
            id, company_id, customer_code, first_name, last_name, 
            email, phone, customer_type, status, created_at, updated_at
        ) VALUES (
            v_customer_id, p_company_id, v_customer_code,
            p_first_name, p_last_name, p_email, p_phone, 
            p_customer_type, 'active', NOW(), NOW()
        );
    END IF;
    
    SELECT v_customer_id as customer_id;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-11-22 23:09:03
