-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: salesmanno04.mysql.domeneshop.no
-- Generation Time: 03. Des, 2025 08:26 AM
-- Tjener-versjon: 11.4.7-MariaDB-ubu2004-log
-- PHP Version: 8.3.27

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `salesmanno04`
--

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `activity_summaries`
--

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
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `audit_logs`
--

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
  `device_info` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `core_sys_news`
--

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
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `core_sys_updates`
--

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
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `economy_bank_accounts`
--

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
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `economy_categories`
--

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
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `economy_daily_summary`
--

CREATE TABLE `economy_daily_summary` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `company_id` char(36) NOT NULL,
  `summary_date` date NOT NULL,
  `total_income` decimal(15,2) DEFAULT 0.00,
  `total_expenses` decimal(15,2) DEFAULT 0.00,
  `net_amount` decimal(15,2) DEFAULT 0.00,
  `transaction_count` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `economy_invoices`
--

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
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `economy_payment_gateways`
--

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
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `economy_tax_rates`
--

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
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `economy_transactions`
--

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
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `email_accounts`
--

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
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `email_attachments`
--

CREATE TABLE `email_attachments` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `message_id` char(36) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `content_id` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `email_configs`
--

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
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `email_drafts`
--

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
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `email_folders`
--

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
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `email_logs`
--

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
  `sent_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `email_messages`
--

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
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `email_queue`
--

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
  `updated_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `email_templates`
--

CREATE TABLE `email_templates` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `company_id` char(36) NOT NULL,
  `template_key` varchar(100) NOT NULL,
  `name` varchar(255) NOT NULL,
  `subject_template` varchar(500) NOT NULL,
  `body_template` text NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `events_checkin`
--

CREATE TABLE `events_checkin` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `registration_id` char(36) DEFAULT NULL,
  `event_id` char(36) NOT NULL,
  `customer_id` char(36) DEFAULT NULL,
  `ticket_type` varchar(50) DEFAULT 'standard',
  `membership_plan_id` char(36) DEFAULT NULL,
  `ticket_price` decimal(10,2) NOT NULL,
  `payment_status` varchar(20) DEFAULT 'paid',
  `payment_method` varchar(50) DEFAULT 'cash',
  `checked_in` tinyint(1) DEFAULT 0,
  `check_in_time` datetime DEFAULT NULL,
  `checked_in_by` char(36) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `barcode` varchar(255) DEFAULT NULL,
  `qrcode` longblob DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `events_eventdata`
--

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
  `memberverification` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `events_results`
--

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
  `revenue_tickets_cash` decimal(10,2) DEFAULT 0.00 COMMENT 'Billettinntekter - Kontant',
  `revenue_tickets_card` decimal(10,2) DEFAULT 0.00 COMMENT 'Billettinntekter - Kort',
  `revenue_tickets_vipps` decimal(10,2) DEFAULT 0.00 COMMENT 'Billettinntekter - Vipps',
  `revenue_tickets_invoice` decimal(10,2) DEFAULT 0.00 COMMENT 'Billettinntekter - Faktura',
  `revenue_merchandise` decimal(10,2) DEFAULT 0.00 COMMENT 'Salg av varer/merchandise',
  `revenue_donations` decimal(10,2) DEFAULT 0.00 COMMENT 'Donasjoner',
  `food_sales` decimal(10,2) DEFAULT 0.00,
  `drink_sales` decimal(10,2) DEFAULT 0.00,
  `other_sales` decimal(10,2) DEFAULT 0.00,
  `total_food_drink_sales` decimal(10,2) DEFAULT 0.00,
  `total_revenue` decimal(10,2) DEFAULT 0.00,
  `purchase_cost` decimal(10,2) DEFAULT 0.00,
  `food_drink_cost` decimal(10,2) DEFAULT 0.00,
  `total_costs` decimal(10,2) DEFAULT 0.00,
  `expense_staff` decimal(10,2) DEFAULT 0.00 COMMENT 'Personalkostnader',
  `expense_venue` decimal(10,2) DEFAULT 0.00 COMMENT 'Venueleie og lokalekostand',
  `expense_equipment` decimal(10,2) DEFAULT 0.00 COMMENT 'Utleie av utstyr',
  `expense_materials` decimal(10,2) DEFAULT 0.00 COMMENT 'Materialer og forbruk',
  `expense_other` decimal(10,2) DEFAULT 0.00 COMMENT 'Andre utgifter',
  `profit_loss` decimal(10,2) DEFAULT 0.00,
  `event_cancelled` tinyint(1) DEFAULT 0,
  `status` varchar(50) DEFAULT 'planning' COMMENT 'Status: planning, confirmed, in_progress, completed, cancelled',
  `notes` longtext DEFAULT NULL COMMENT 'Notater om arrangementet',
  `organizer_notes` longtext DEFAULT NULL COMMENT 'Interne notater',
  `guest_checkins` int(11) DEFAULT 0,
  `member_checkins` int(11) DEFAULT 0,
  `total_checkins` int(11) DEFAULT 0,
  `no_shows` int(11) DEFAULT 0 COMMENT 'Antall som ikke møtte opp',
  `attendance_rate` decimal(5,2) DEFAULT 0.00 COMMENT 'Oppmøte % (0-100)',
  `average_age` int(11) DEFAULT 0 COMMENT 'Gjennomsnittlig alder',
  `amount_checkins` decimal(10,2) DEFAULT 0.00,
  `all_averageage` int(11) DEFAULT 0,
  `food_units_sold` int(11) DEFAULT 0,
  `drink_units_sold` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `finalized_at` datetime DEFAULT NULL COMMENT 'Når resultatene ble avsluttet'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `event_membership_plans`
--

CREATE TABLE `event_membership_plans` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `event_id` char(36) NOT NULL,
  `membership_plan_id` char(36) NOT NULL,
  `ticket_price` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `public_display` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `event_registrations`
--

CREATE TABLE `event_registrations` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `event_id` char(36) NOT NULL,
  `company_id` char(36) NOT NULL,
  `customer_id` char(36) NOT NULL,
  `approval_status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `approval_reason` text DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `approved_by` char(36) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `rejected_by` char(36) DEFAULT NULL,
  `rejected_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `number_of_attendees` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `event_registration_fields`
--

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
  `member_compatability` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `marketplace_categories`
--

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
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `marketplace_favorites`
--

CREATE TABLE `marketplace_favorites` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `marketplace_item_id` char(36) NOT NULL,
  `customer_id` char(36) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `marketplace_inquiries`
--

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
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `market_marketplace`
--

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
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `membership_members`
--

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
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `membership_plans`
--

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
  `for_sale` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `output_products`
--

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
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `rental_cabins`
--

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
  `linen_included` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `rental_containers`
--

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
  `postal_city` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `rental_event_items`
--

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
  `min_rental_days` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `rental_objects`
--

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
  `rental_periods` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `rental_object_fields`
--

CREATE TABLE `rental_object_fields` (
  `id` char(36) NOT NULL,
  `subdomain` varchar(100) NOT NULL,
  `field_name` varchar(50) NOT NULL,
  `is_required` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `rental_offices`
--

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
  `facilities` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `rental_parking`
--

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
  `distance_to_address` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `rental_residences`
--

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
  `last_renovated` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `rental_rvs`
--

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
  `additional_certificates` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `rental_schedule`
--

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
  `created_by` char(36) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `rental_sports_equipment`
--

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
  `age_range` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `rental_tools`
--

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
  `manual_url` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `rental_trailers`
--

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
  `min_driver_age` int(11) DEFAULT 18
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `rental_vehicles`
--

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
  `equipment` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `rental_venues`
--

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
  `facilities` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `reservations_confirmed`
--

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
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `reservations_reservations`
--

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
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `reservations_resources`
--

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
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `reservation_services`
--

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
  `img_url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `reservation_service_bookings`
--

CREATE TABLE `reservation_service_bookings` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `reservation_id` char(36) NOT NULL,
  `service_id` char(36) NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `saas_admins`
--

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
  `username` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `saas_staff`
--

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
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `saas_subscription_plans`
--

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
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `saas_system_config`
--

CREATE TABLE `saas_system_config` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `config_key` varchar(100) NOT NULL,
  `config_value` text DEFAULT NULL,
  `config_type` varchar(20) DEFAULT 'string',
  `description` text DEFAULT NULL,
  `is_sensitive` tinyint(1) DEFAULT 0,
  `updated_by` char(36) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `sales_cart`
--

CREATE TABLE `sales_cart` (
  `id` int(11) NOT NULL,
  `company_id` char(36) NOT NULL,
  `admin_id` char(36) NOT NULL,
  `output_product_id` char(36) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `added_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `sales_categories`
--

CREATE TABLE `sales_categories` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `company_id` char(36) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `parent_category_id` char(36) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `sales_orders`
--

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
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `sales_orders_items`
--

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
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `sales_products`
--

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
  `low_stock_threeshold` int(11) DEFAULT 5
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `sales_products_stock`
--

CREATE TABLE `sales_products_stock` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `product_id` char(36) NOT NULL,
  `location` varchar(100) DEFAULT 'main',
  `quantity` int(11) NOT NULL DEFAULT 0,
  `reserved_quantity` int(11) DEFAULT 0,
  `last_restocked` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `sales_purchase_history`
--

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
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `sales_transactions`
--

CREATE TABLE `sales_transactions` (
  `id` int(11) NOT NULL,
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
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `self_company`
--

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
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `service_bookings`
--

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
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `system_modules`
--

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
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `tenant_admins`
--

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
  `tenant_staff_id` char(36) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `tenant_admin_modules`
--

CREATE TABLE `tenant_admin_modules` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `tenant_admin_id` char(36) NOT NULL,
  `module_id` char(36) NOT NULL,
  `granted_by` char(36) NOT NULL,
  `granted_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `tenant_admin_sessions`
--

CREATE TABLE `tenant_admin_sessions` (
  `id` int(11) NOT NULL,
  `tenant_admin_id` char(36) NOT NULL,
  `session_id` varchar(100) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `login_at` datetime DEFAULT current_timestamp(),
  `logout_at` datetime DEFAULT NULL,
  `last_activity` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `tenant_announcements`
--

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
  `image_url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `tenant_companies`
--

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
  `subdomain_status` varchar(20) DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Tenant companies with individual admin passwords for data modification';

--
-- Triggere `tenant_companies`
--
DELIMITER $$
CREATE TRIGGER `audit_company_password_change` AFTER UPDATE ON `tenant_companies` FOR EACH ROW BEGIN
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
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `tenant_company_modules`
--

CREATE TABLE `tenant_company_modules` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `company_id` char(36) NOT NULL,
  `module_id` char(36) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `config` text DEFAULT NULL,
  `activated_at` datetime DEFAULT current_timestamp(),
  `deactivated_at` datetime DEFAULT NULL,
  `assigned_by` char(36) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `tenant_customers`
--

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
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `tenant_email_config`
--

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
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `tenant_settings`
--

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
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `tenant_staff`
--

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
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `tenant_staff_modules`
--

CREATE TABLE `tenant_staff_modules` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `staff_id` char(36) NOT NULL,
  `module_id` char(36) NOT NULL,
  `granted_by` char(36) NOT NULL,
  `granted_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `tenant_subscriptions`
--

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
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `tenant_terms_acceptances`
--

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
  `transaction_id` char(36) DEFAULT NULL COMMENT 'Related transaction/sale/booking'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `tenant_terms_conditions`
--

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
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `tenant_website_config`
--

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
  `updated_by` char(36) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `tenant_website_general`
--

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
  `spacing_unit` varchar(10) DEFAULT '1rem'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `unified_ledger`
--

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
  `transaction_type` varchar(20) DEFAULT NULL COMMENT 'income, expense, or null',
  `category` varchar(100) DEFAULT NULL COMMENT 'Salg, Arrangement, Medlemskap, etc',
  `transaction_date` datetime NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_summaries`
--
ALTER TABLE `activity_summaries`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `company_id` (`company_id`,`user_id`,`date_date`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_audit_logs_company_date` (`company_id`,`performed_at`),
  ADD KEY `idx_audit_logs_user_module` (`user_id`,`module`);

--
-- Indexes for table `core_sys_news`
--
ALTER TABLE `core_sys_news`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_core_sys_news_publish` (`publish_at`,`expire_at`,`is_published`),
  ADD KEY `idx_core_sys_news_audience` (`target_audience`);

--
-- Indexes for table `core_sys_updates`
--
ALTER TABLE `core_sys_updates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `version` (`version`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_core_sys_updates_published` (`is_published`,`release_date`);

--
-- Indexes for table `economy_bank_accounts`
--
ALTER TABLE `economy_bank_accounts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `company_id` (`company_id`);

--
-- Indexes for table `economy_categories`
--
ALTER TABLE `economy_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `company_id` (`company_id`,`name`,`type`),
  ADD KEY `parent_category_id` (`parent_category_id`);

--
-- Indexes for table `economy_daily_summary`
--
ALTER TABLE `economy_daily_summary`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `company_id` (`company_id`,`summary_date`);

--
-- Indexes for table `economy_invoices`
--
ALTER TABLE `economy_invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `company_id` (`company_id`,`invoice_number`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `idx_economy_invoices_customer_type` (`customer_entity_type`);

--
-- Indexes for table `economy_payment_gateways`
--
ALTER TABLE `economy_payment_gateways`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `company_id` (`company_id`,`gateway_name`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `economy_tax_rates`
--
ALTER TABLE `economy_tax_rates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `company_id` (`company_id`,`name`);

--
-- Indexes for table `economy_transactions`
--
ALTER TABLE `economy_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `bank_account_id` (`bank_account_id`),
  ADD KEY `payment_gateway_id` (`payment_gateway_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_economy_transactions_date` (`company_id`,`transaction_date`),
  ADD KEY `idx_economy_transactions_type` (`transaction_type`);

--
-- Indexes for table `email_accounts`
--
ALTER TABLE `email_accounts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `company_id` (`company_id`,`email_address`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_email_accounts_company` (`company_id`);

--
-- Indexes for table `email_attachments`
--
ALTER TABLE `email_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `message_id` (`message_id`);

--
-- Indexes for table `email_configs`
--
ALTER TABLE `email_configs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `company_id` (`company_id`);

--
-- Indexes for table `email_drafts`
--
ALTER TABLE `email_drafts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `account_id` (`account_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `email_folders`
--
ALTER TABLE `email_folders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `account_id` (`account_id`,`name`),
  ADD KEY `parent_folder_id` (`parent_folder_id`);

--
-- Indexes for table `email_logs`
--
ALTER TABLE `email_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `company_id` (`company_id`),
  ADD KEY `sent_by` (`sent_by`);

--
-- Indexes for table `email_messages`
--
ALTER TABLE `email_messages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `account_id` (`account_id`,`message_uid`),
  ADD KEY `folder_id` (`folder_id`),
  ADD KEY `idx_email_messages_account_folder` (`account_id`,`folder_id`);

--
-- Indexes for table `email_queue`
--
ALTER TABLE `email_queue`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email_queue_status` (`status`),
  ADD KEY `idx_email_queue_company` (`company_id`),
  ADD KEY `idx_email_queue_scheduled` (`scheduled_at`);

--
-- Indexes for table `email_templates`
--
ALTER TABLE `email_templates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_template_per_company` (`company_id`,`template_key`),
  ADD KEY `idx_template_company` (`company_id`),
  ADD KEY `idx_template_key` (`template_key`);

--
-- Indexes for table `events_checkin`
--
ALTER TABLE `events_checkin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_barcode_event` (`event_id`,`barcode`),
  ADD KEY `idx_event` (`event_id`),
  ADD KEY `idx_customer` (`customer_id`),
  ADD KEY `idx_payment` (`payment_status`),
  ADD KEY `idx_checkin` (`checked_in`,`check_in_time`),
  ADD KEY `checked_in_by` (`checked_in_by`),
  ADD KEY `idx_checkin_barcode` (`barcode`),
  ADD KEY `idx_membership_plan_id` (`membership_plan_id`),
  ADD KEY `fk_events_checkin_registration` (`registration_id`);

--
-- Indexes for table `events_eventdata`
--
ALTER TABLE `events_eventdata`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_company` (`company_id`),
  ADD KEY `idx_output` (`output_product_id`),
  ADD KEY `idx_date` (`start_date`,`end_date`),
  ADD KEY `idx_active` (`is_active`);

--
-- Indexes for table `events_results`
--
ALTER TABLE `events_results`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_event_result` (`event_id`),
  ADD KEY `idx_events_results_company` (`company_id`),
  ADD KEY `idx_events_results_dates` (`date_start`,`date_end`);

--
-- Indexes for table `event_membership_plans`
--
ALTER TABLE `event_membership_plans`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_event_membership` (`event_id`,`membership_plan_id`),
  ADD KEY `idx_event_id` (`event_id`),
  ADD KEY `idx_membership_plan_id` (`membership_plan_id`);

--
-- Indexes for table `event_registrations`
--
ALTER TABLE `event_registrations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_event_customer` (`event_id`,`customer_id`),
  ADD KEY `fk_event_reg_company` (`company_id`),
  ADD KEY `fk_event_reg_customer` (`customer_id`);

--
-- Indexes for table `event_registration_fields`
--
ALTER TABLE `event_registration_fields`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `event_id` (`event_id`,`field_name`);

--
-- Indexes for table `marketplace_categories`
--
ALTER TABLE `marketplace_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `company_id` (`company_id`,`name`),
  ADD KEY `parent_category_id` (`parent_category_id`);

--
-- Indexes for table `marketplace_favorites`
--
ALTER TABLE `marketplace_favorites`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `marketplace_item_id` (`marketplace_item_id`,`customer_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `marketplace_inquiries`
--
ALTER TABLE `marketplace_inquiries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `marketplace_item_id` (`marketplace_item_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `market_marketplace`
--
ALTER TABLE `market_marketplace`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `company_id` (`company_id`,`source_table`,`source_id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_marketplace_status` (`company_id`,`status`),
  ADD KEY `idx_marketplace_category` (`company_id`,`category`),
  ADD KEY `idx_marketplace_location` (`company_id`,`location_city`,`location_state`),
  ADD KEY `idx_marketplace_type` (`item_type`,`source_table`),
  ADD KEY `idx_marketplace_slug` (`slug`);

--
-- Indexes for table `membership_members`
--
ALTER TABLE `membership_members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `company_id` (`company_id`,`member_code`),
  ADD UNIQUE KEY `company_id_2` (`company_id`,`customer_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `membership_plan_id` (`membership_plan_id`);

--
-- Indexes for table `membership_plans`
--
ALTER TABLE `membership_plans`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `company_id` (`company_id`,`plan_code`),
  ADD KEY `idx_output_product` (`output_product_id`);

--
-- Indexes for table `output_products`
--
ALTER TABLE `output_products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `product_type` (`product_type`,`product_id`),
  ADD KEY `idx_company` (`company_id`),
  ADD KEY `idx_module` (`module_id`),
  ADD KEY `idx_type` (`product_type`),
  ADD KEY `idx_visibility` (`is_visible`,`is_public`,`is_marketplace_featured`);

--
-- Indexes for table `rental_cabins`
--
ALTER TABLE `rental_cabins`
  ADD PRIMARY KEY (`object_id`);

--
-- Indexes for table `rental_containers`
--
ALTER TABLE `rental_containers`
  ADD PRIMARY KEY (`object_id`);

--
-- Indexes for table `rental_event_items`
--
ALTER TABLE `rental_event_items`
  ADD PRIMARY KEY (`object_id`);

--
-- Indexes for table `rental_objects`
--
ALTER TABLE `rental_objects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_company` (`company_id`),
  ADD KEY `idx_output_product` (`output_product_id`),
  ADD KEY `idx_available` (`is_available`,`is_active`),
  ADD KEY `idx_serial` (`serial_number`);

--
-- Indexes for table `rental_object_fields`
--
ALTER TABLE `rental_object_fields`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_field` (`subdomain`,`field_name`),
  ADD KEY `idx_subdomain` (`subdomain`);

--
-- Indexes for table `rental_offices`
--
ALTER TABLE `rental_offices`
  ADD PRIMARY KEY (`object_id`);

--
-- Indexes for table `rental_parking`
--
ALTER TABLE `rental_parking`
  ADD PRIMARY KEY (`object_id`);

--
-- Indexes for table `rental_residences`
--
ALTER TABLE `rental_residences`
  ADD PRIMARY KEY (`object_id`);

--
-- Indexes for table `rental_rvs`
--
ALTER TABLE `rental_rvs`
  ADD PRIMARY KEY (`object_id`);

--
-- Indexes for table `rental_schedule`
--
ALTER TABLE `rental_schedule`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_company` (`company_id`),
  ADD KEY `idx_object` (`rental_object_id`),
  ADD KEY `idx_customer` (`customer_id`),
  ADD KEY `idx_dates` (`start_date`,`end_date`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `rental_sports_equipment`
--
ALTER TABLE `rental_sports_equipment`
  ADD PRIMARY KEY (`object_id`);

--
-- Indexes for table `rental_tools`
--
ALTER TABLE `rental_tools`
  ADD PRIMARY KEY (`object_id`);

--
-- Indexes for table `rental_trailers`
--
ALTER TABLE `rental_trailers`
  ADD PRIMARY KEY (`object_id`);

--
-- Indexes for table `rental_vehicles`
--
ALTER TABLE `rental_vehicles`
  ADD PRIMARY KEY (`object_id`);

--
-- Indexes for table `rental_venues`
--
ALTER TABLE `rental_venues`
  ADD PRIMARY KEY (`object_id`);

--
-- Indexes for table `reservations_confirmed`
--
ALTER TABLE `reservations_confirmed`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `reservation_id` (`reservation_id`),
  ADD KEY `confirmed_by` (`confirmed_by`),
  ADD KEY `idx_reservations_confirmed_payment` (`payment_status`);

--
-- Indexes for table `reservations_reservations`
--
ALTER TABLE `reservations_reservations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `resource_id` (`resource_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `staff_id` (`staff_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_reservations_dates` (`company_id`,`start_datetime`,`end_datetime`),
  ADD KEY `idx_reservations_status` (`status`);

--
-- Indexes for table `reservations_resources`
--
ALTER TABLE `reservations_resources`
  ADD PRIMARY KEY (`id`),
  ADD KEY `company_id` (`company_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `reservation_services`
--
ALTER TABLE `reservation_services`
  ADD PRIMARY KEY (`id`),
  ADD KEY `company_id` (`company_id`),
  ADD KEY `reservation_services_ibfk_2` (`output_product_id`);

--
-- Indexes for table `reservation_service_bookings`
--
ALTER TABLE `reservation_service_bookings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `reservation_id` (`reservation_id`,`service_id`),
  ADD KEY `service_id` (`service_id`);

--
-- Indexes for table `saas_admins`
--
ALTER TABLE `saas_admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `saas_staff_id` (`saas_staff_id`);

--
-- Indexes for table `saas_staff`
--
ALTER TABLE `saas_staff`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `saas_subscription_plans`
--
ALTER TABLE `saas_subscription_plans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `saas_system_config`
--
ALTER TABLE `saas_system_config`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `config_key` (`config_key`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Indexes for table `sales_cart`
--
ALTER TABLE `sales_cart`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_company_admin` (`company_id`,`admin_id`),
  ADD KEY `idx_output_product` (`output_product_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `sales_categories`
--
ALTER TABLE `sales_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `company_id` (`company_id`,`name`),
  ADD KEY `parent_category_id` (`parent_category_id`);

--
-- Indexes for table `sales_orders`
--
ALTER TABLE `sales_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `company_id` (`company_id`,`order_number`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `member_id` (`member_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `sales_orders_items`
--
ALTER TABLE `sales_orders_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `sales_products`
--
ALTER TABLE `sales_products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `company_id` (`company_id`,`sku`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `idx_output_product` (`output_product_id`);

--
-- Indexes for table `sales_products_stock`
--
ALTER TABLE `sales_products_stock`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `product_id` (`product_id`,`location`);

--
-- Indexes for table `sales_purchase_history`
--
ALTER TABLE `sales_purchase_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_company_id` (`company_id`),
  ADD KEY `idx_product_id` (`product_id`),
  ADD KEY `idx_created_by` (`created_by`);

--
-- Indexes for table `sales_transactions`
--
ALTER TABLE `sales_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_company` (`company_id`),
  ADD KEY `idx_product` (`sales_product_id`);

--
-- Indexes for table `self_company`
--
ALTER TABLE `self_company`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `service_bookings`
--
ALTER TABLE `service_bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_id` (`admin_id`),
  ADD KEY `idx_service_bookings_company` (`company_id`),
  ADD KEY `idx_service_bookings_date` (`date_from`,`date_to`),
  ADD KEY `idx_service_bookings_status` (`status`),
  ADD KEY `idx_service_bookings_staff` (`assigned_staff_id`);

--
-- Indexes for table `system_modules`
--
ALTER TABLE `system_modules`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `module_key` (`module_key`);

--
-- Indexes for table `tenant_admins`
--
ALTER TABLE `tenant_admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `company_id` (`company_id`,`email`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `tenant_admin_modules`
--
ALTER TABLE `tenant_admin_modules`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tenant_admin_id` (`tenant_admin_id`,`module_id`),
  ADD KEY `module_id` (`module_id`),
  ADD KEY `idx_tenant_admin_modules_granted_by` (`granted_by`);

--
-- Indexes for table `tenant_admin_sessions`
--
ALTER TABLE `tenant_admin_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_id` (`session_id`),
  ADD KEY `tenant_admin_id` (`tenant_admin_id`);

--
-- Indexes for table `tenant_announcements`
--
ALTER TABLE `tenant_announcements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_company_published` (`company_id`,`is_published`,`publish_at`),
  ADD KEY `idx_author` (`author_id`),
  ADD KEY `idx_pinned` (`is_pinned`),
  ADD KEY `idx_expiry` (`expire_at`),
  ADD KEY `idx_related_event` (`related_event_id`);

--
-- Indexes for table `tenant_companies`
--
ALTER TABLE `tenant_companies`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `subdomain` (`subdomain`),
  ADD UNIQUE KEY `domain` (`domain`),
  ADD KEY `onboarded_by` (`onboarded_by`),
  ADD KEY `idx_tenant_companies_subdomain` (`subdomain`),
  ADD KEY `idx_tenant_companies_entity_type` (`entity_type`),
  ADD KEY `idx_tenant_subdomain_status` (`subdomain_active`,`subdomain_status`);

--
-- Indexes for table `tenant_company_modules`
--
ALTER TABLE `tenant_company_modules`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `company_id` (`company_id`,`module_id`),
  ADD KEY `module_id` (`module_id`),
  ADD KEY `assigned_by` (`assigned_by`);

--
-- Indexes for table `tenant_customers`
--
ALTER TABLE `tenant_customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `company_id` (`company_id`,`customer_code`),
  ADD UNIQUE KEY `company_id_2` (`company_id`,`email`);

--
-- Indexes for table `tenant_email_config`
--
ALTER TABLE `tenant_email_config`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `company_id` (`company_id`);

--
-- Indexes for table `tenant_settings`
--
ALTER TABLE `tenant_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `company_id` (`company_id`,`setting_key`),
  ADD KEY `idx_tenant_settings_company_key` (`company_id`,`setting_key`);

--
-- Indexes for table `tenant_staff`
--
ALTER TABLE `tenant_staff`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `company_id` (`company_id`,`email`),
  ADD UNIQUE KEY `company_id_2` (`company_id`,`employee_code`),
  ADD KEY `tenant_admin_id` (`tenant_admin_id`);

--
-- Indexes for table `tenant_staff_modules`
--
ALTER TABLE `tenant_staff_modules`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `staff_id` (`staff_id`,`module_id`),
  ADD KEY `module_id` (`module_id`),
  ADD KEY `idx_tenant_staff_modules_granted_by` (`granted_by`);

--
-- Indexes for table `tenant_subscriptions`
--
ALTER TABLE `tenant_subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `company_id` (`company_id`),
  ADD KEY `self_company_id` (`self_company_id`),
  ADD KEY `plan_id` (`plan_id`);

--
-- Indexes for table `tenant_terms_acceptances`
--
ALTER TABLE `tenant_terms_acceptances`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_acceptance` (`terms_conditions_id`,`customer_id`,`module_context`,`transaction_id`),
  ADD KEY `idx_terms_acceptances_customer` (`customer_id`),
  ADD KEY `idx_terms_acceptances_terms` (`terms_conditions_id`),
  ADD KEY `idx_terms_acceptances_module` (`module_context`),
  ADD KEY `idx_terms_acceptances_date` (`accepted_at`);

--
-- Indexes for table `tenant_terms_conditions`
--
ALTER TABLE `tenant_terms_conditions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_company_version` (`company_id`,`version`),
  ADD KEY `idx_tenant_terms_company` (`company_id`),
  ADD KEY `idx_tenant_terms_active` (`is_active`,`effective_date`),
  ADD KEY `tenant_terms_conditions_ibfk_2` (`created_by`);

--
-- Indexes for table `tenant_website_config`
--
ALTER TABLE `tenant_website_config`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `company_id` (`company_id`,`module_id`),
  ADD KEY `module_id` (`module_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `updated_by` (`updated_by`),
  ADD KEY `idx_website_config_company` (`company_id`),
  ADD KEY `idx_website_config_public` (`is_public`,`is_enabled`,`is_published`),
  ADD KEY `idx_website_config_navigation` (`show_in_navigation`,`display_order`);

--
-- Indexes for table `tenant_website_general`
--
ALTER TABLE `tenant_website_general`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `company_id` (`company_id`),
  ADD KEY `homepage_module` (`homepage_module`);

--
-- Indexes for table `unified_ledger`
--
ALTER TABLE `unified_ledger`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_unified_ledger_company` (`company_id`),
  ADD KEY `idx_unified_ledger_module` (`module_source`,`item_type`),
  ADD KEY `idx_unified_ledger_order` (`order_id`),
  ADD KEY `idx_unified_ledger_date` (`transaction_date`),
  ADD KEY `idx_unified_ledger_customer` (`customer_email`),
  ADD KEY `idx_unified_ledger_status` (`status`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `sales_cart`
--
ALTER TABLE `sales_cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sales_transactions`
--
ALTER TABLE `sales_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tenant_admin_sessions`
--
ALTER TABLE `tenant_admin_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Begrensninger for dumpede tabeller
--

--
-- Begrensninger for tabell `activity_summaries`
--
ALTER TABLE `activity_summaries`
  ADD CONSTRAINT `activity_summaries_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE;

--
-- Begrensninger for tabell `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE;

--
-- Begrensninger for tabell `economy_bank_accounts`
--
ALTER TABLE `economy_bank_accounts`
  ADD CONSTRAINT `economy_bank_accounts_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE;

--
-- Begrensninger for tabell `economy_categories`
--
ALTER TABLE `economy_categories`
  ADD CONSTRAINT `economy_categories_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `economy_categories_ibfk_2` FOREIGN KEY (`parent_category_id`) REFERENCES `economy_categories` (`id`);

--
-- Begrensninger for tabell `economy_daily_summary`
--
ALTER TABLE `economy_daily_summary`
  ADD CONSTRAINT `economy_daily_summary_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE;

--
-- Begrensninger for tabell `economy_invoices`
--
ALTER TABLE `economy_invoices`
  ADD CONSTRAINT `economy_invoices_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `economy_invoices_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `tenant_customers` (`id`);

--
-- Begrensninger for tabell `economy_payment_gateways`
--
ALTER TABLE `economy_payment_gateways`
  ADD CONSTRAINT `economy_payment_gateways_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `economy_payment_gateways_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `tenant_admins` (`id`);

--
-- Begrensninger for tabell `economy_tax_rates`
--
ALTER TABLE `economy_tax_rates`
  ADD CONSTRAINT `economy_tax_rates_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE;

--
-- Begrensninger for tabell `economy_transactions`
--
ALTER TABLE `economy_transactions`
  ADD CONSTRAINT `economy_transactions_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `economy_transactions_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `tenant_customers` (`id`),
  ADD CONSTRAINT `economy_transactions_ibfk_3` FOREIGN KEY (`bank_account_id`) REFERENCES `economy_bank_accounts` (`id`),
  ADD CONSTRAINT `economy_transactions_ibfk_4` FOREIGN KEY (`payment_gateway_id`) REFERENCES `economy_payment_gateways` (`id`),
  ADD CONSTRAINT `economy_transactions_ibfk_5` FOREIGN KEY (`created_by`) REFERENCES `tenant_admins` (`id`);

--
-- Begrensninger for tabell `email_accounts`
--
ALTER TABLE `email_accounts`
  ADD CONSTRAINT `email_accounts_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `email_accounts_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `tenant_admins` (`id`);

--
-- Begrensninger for tabell `email_attachments`
--
ALTER TABLE `email_attachments`
  ADD CONSTRAINT `email_attachments_ibfk_1` FOREIGN KEY (`message_id`) REFERENCES `email_messages` (`id`) ON DELETE CASCADE;

--
-- Begrensninger for tabell `email_configs`
--
ALTER TABLE `email_configs`
  ADD CONSTRAINT `email_configs_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE;

--
-- Begrensninger for tabell `email_drafts`
--
ALTER TABLE `email_drafts`
  ADD CONSTRAINT `email_drafts_ibfk_1` FOREIGN KEY (`account_id`) REFERENCES `email_accounts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `email_drafts_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `tenant_admins` (`id`);

--
-- Begrensninger for tabell `email_folders`
--
ALTER TABLE `email_folders`
  ADD CONSTRAINT `email_folders_ibfk_1` FOREIGN KEY (`account_id`) REFERENCES `email_accounts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `email_folders_ibfk_2` FOREIGN KEY (`parent_folder_id`) REFERENCES `email_folders` (`id`);

--
-- Begrensninger for tabell `email_logs`
--
ALTER TABLE `email_logs`
  ADD CONSTRAINT `email_logs_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `email_logs_ibfk_2` FOREIGN KEY (`sent_by`) REFERENCES `tenant_admins` (`id`);

--
-- Begrensninger for tabell `email_messages`
--
ALTER TABLE `email_messages`
  ADD CONSTRAINT `email_messages_ibfk_1` FOREIGN KEY (`account_id`) REFERENCES `email_accounts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `email_messages_ibfk_2` FOREIGN KEY (`folder_id`) REFERENCES `email_folders` (`id`);

--
-- Begrensninger for tabell `email_templates`
--
ALTER TABLE `email_templates`
  ADD CONSTRAINT `email_templates_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE;

--
-- Begrensninger for tabell `events_checkin`
--
ALTER TABLE `events_checkin`
  ADD CONSTRAINT `events_checkin_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events_eventdata` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `events_checkin_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `tenant_customers` (`id`),
  ADD CONSTRAINT `events_checkin_ibfk_3` FOREIGN KEY (`checked_in_by`) REFERENCES `tenant_admins` (`id`),
  ADD CONSTRAINT `fk_checkin_registration` FOREIGN KEY (`registration_id`) REFERENCES `event_registrations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_events_checkin_registration` FOREIGN KEY (`registration_id`) REFERENCES `event_registrations` (`id`) ON DELETE CASCADE;

--
-- Begrensninger for tabell `events_eventdata`
--
ALTER TABLE `events_eventdata`
  ADD CONSTRAINT `events_eventdata_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `events_eventdata_ibfk_2` FOREIGN KEY (`output_product_id`) REFERENCES `output_products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Begrensninger for tabell `events_results`
--
ALTER TABLE `events_results`
  ADD CONSTRAINT `fk_events_results_company` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_events_results_event` FOREIGN KEY (`event_id`) REFERENCES `events_eventdata` (`id`) ON DELETE CASCADE;

--
-- Begrensninger for tabell `event_registrations`
--
ALTER TABLE `event_registrations`
  ADD CONSTRAINT `fk_event_reg_company` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_event_reg_customer` FOREIGN KEY (`customer_id`) REFERENCES `tenant_customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_event_reg_event` FOREIGN KEY (`event_id`) REFERENCES `events_eventdata` (`id`) ON DELETE CASCADE;

--
-- Begrensninger for tabell `event_registration_fields`
--
ALTER TABLE `event_registration_fields`
  ADD CONSTRAINT `event_registration_fields_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events_eventdata` (`id`) ON DELETE CASCADE;

--
-- Begrensninger for tabell `marketplace_categories`
--
ALTER TABLE `marketplace_categories`
  ADD CONSTRAINT `marketplace_categories_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `marketplace_categories_ibfk_2` FOREIGN KEY (`parent_category_id`) REFERENCES `marketplace_categories` (`id`);

--
-- Begrensninger for tabell `marketplace_favorites`
--
ALTER TABLE `marketplace_favorites`
  ADD CONSTRAINT `marketplace_favorites_ibfk_1` FOREIGN KEY (`marketplace_item_id`) REFERENCES `market_marketplace` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `marketplace_favorites_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `tenant_customers` (`id`) ON DELETE CASCADE;

--
-- Begrensninger for tabell `marketplace_inquiries`
--
ALTER TABLE `marketplace_inquiries`
  ADD CONSTRAINT `marketplace_inquiries_ibfk_1` FOREIGN KEY (`marketplace_item_id`) REFERENCES `market_marketplace` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `marketplace_inquiries_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `tenant_customers` (`id`);

--
-- Begrensninger for tabell `market_marketplace`
--
ALTER TABLE `market_marketplace`
  ADD CONSTRAINT `market_marketplace_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `market_marketplace_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `tenant_admins` (`id`);

--
-- Begrensninger for tabell `membership_members`
--
ALTER TABLE `membership_members`
  ADD CONSTRAINT `membership_members_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `membership_members_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `tenant_customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `membership_members_ibfk_3` FOREIGN KEY (`membership_plan_id`) REFERENCES `membership_plans` (`id`);

--
-- Begrensninger for tabell `membership_plans`
--
ALTER TABLE `membership_plans`
  ADD CONSTRAINT `membership_plans_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `membership_plans_ibfk_2` FOREIGN KEY (`output_product_id`) REFERENCES `output_products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Begrensninger for tabell `output_products`
--
ALTER TABLE `output_products`
  ADD CONSTRAINT `output_products_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `output_products_ibfk_2` FOREIGN KEY (`module_id`) REFERENCES `system_modules` (`id`);

--
-- Begrensninger for tabell `rental_cabins`
--
ALTER TABLE `rental_cabins`
  ADD CONSTRAINT `rental_cabins_ibfk_1` FOREIGN KEY (`object_id`) REFERENCES `rental_objects` (`id`) ON DELETE CASCADE;

--
-- Begrensninger for tabell `rental_containers`
--
ALTER TABLE `rental_containers`
  ADD CONSTRAINT `rental_containers_ibfk_1` FOREIGN KEY (`object_id`) REFERENCES `rental_objects` (`id`) ON DELETE CASCADE;

--
-- Begrensninger for tabell `rental_event_items`
--
ALTER TABLE `rental_event_items`
  ADD CONSTRAINT `rental_event_items_ibfk_1` FOREIGN KEY (`object_id`) REFERENCES `rental_objects` (`id`) ON DELETE CASCADE;

--
-- Begrensninger for tabell `rental_objects`
--
ALTER TABLE `rental_objects`
  ADD CONSTRAINT `rental_objects_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `rental_objects_ibfk_2` FOREIGN KEY (`output_product_id`) REFERENCES `output_products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Begrensninger for tabell `rental_offices`
--
ALTER TABLE `rental_offices`
  ADD CONSTRAINT `rental_offices_ibfk_1` FOREIGN KEY (`object_id`) REFERENCES `rental_objects` (`id`) ON DELETE CASCADE;

--
-- Begrensninger for tabell `rental_parking`
--
ALTER TABLE `rental_parking`
  ADD CONSTRAINT `rental_parking_ibfk_1` FOREIGN KEY (`object_id`) REFERENCES `rental_objects` (`id`) ON DELETE CASCADE;

--
-- Begrensninger for tabell `rental_residences`
--
ALTER TABLE `rental_residences`
  ADD CONSTRAINT `rental_residences_ibfk_1` FOREIGN KEY (`object_id`) REFERENCES `rental_objects` (`id`) ON DELETE CASCADE;

--
-- Begrensninger for tabell `rental_rvs`
--
ALTER TABLE `rental_rvs`
  ADD CONSTRAINT `rental_rvs_ibfk_1` FOREIGN KEY (`object_id`) REFERENCES `rental_objects` (`id`) ON DELETE CASCADE;

--
-- Begrensninger for tabell `rental_schedule`
--
ALTER TABLE `rental_schedule`
  ADD CONSTRAINT `rental_schedule_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `rental_schedule_ibfk_2` FOREIGN KEY (`rental_object_id`) REFERENCES `rental_objects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `rental_schedule_ibfk_3` FOREIGN KEY (`customer_id`) REFERENCES `tenant_customers` (`id`);

--
-- Begrensninger for tabell `rental_sports_equipment`
--
ALTER TABLE `rental_sports_equipment`
  ADD CONSTRAINT `rental_sports_equipment_ibfk_1` FOREIGN KEY (`object_id`) REFERENCES `rental_objects` (`id`) ON DELETE CASCADE;

--
-- Begrensninger for tabell `rental_tools`
--
ALTER TABLE `rental_tools`
  ADD CONSTRAINT `rental_tools_ibfk_1` FOREIGN KEY (`object_id`) REFERENCES `rental_objects` (`id`) ON DELETE CASCADE;

--
-- Begrensninger for tabell `rental_trailers`
--
ALTER TABLE `rental_trailers`
  ADD CONSTRAINT `rental_trailers_ibfk_1` FOREIGN KEY (`object_id`) REFERENCES `rental_objects` (`id`) ON DELETE CASCADE;

--
-- Begrensninger for tabell `rental_vehicles`
--
ALTER TABLE `rental_vehicles`
  ADD CONSTRAINT `rental_vehicles_ibfk_1` FOREIGN KEY (`object_id`) REFERENCES `rental_objects` (`id`) ON DELETE CASCADE;

--
-- Begrensninger for tabell `rental_venues`
--
ALTER TABLE `rental_venues`
  ADD CONSTRAINT `rental_venues_ibfk_1` FOREIGN KEY (`object_id`) REFERENCES `rental_objects` (`id`) ON DELETE CASCADE;

--
-- Begrensninger for tabell `reservations_confirmed`
--
ALTER TABLE `reservations_confirmed`
  ADD CONSTRAINT `reservations_confirmed_ibfk_1` FOREIGN KEY (`reservation_id`) REFERENCES `reservations_reservations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reservations_confirmed_ibfk_2` FOREIGN KEY (`confirmed_by`) REFERENCES `tenant_admins` (`id`);

--
-- Begrensninger for tabell `reservations_reservations`
--
ALTER TABLE `reservations_reservations`
  ADD CONSTRAINT `reservations_reservations_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reservations_reservations_ibfk_2` FOREIGN KEY (`resource_id`) REFERENCES `reservations_resources` (`id`),
  ADD CONSTRAINT `reservations_reservations_ibfk_3` FOREIGN KEY (`customer_id`) REFERENCES `tenant_customers` (`id`),
  ADD CONSTRAINT `reservations_reservations_ibfk_4` FOREIGN KEY (`staff_id`) REFERENCES `tenant_staff` (`id`),
  ADD CONSTRAINT `reservations_reservations_ibfk_5` FOREIGN KEY (`created_by`) REFERENCES `tenant_admins` (`id`);

--
-- Begrensninger for tabell `reservations_resources`
--
ALTER TABLE `reservations_resources`
  ADD CONSTRAINT `reservations_resources_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reservations_resources_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `tenant_admins` (`id`);

--
-- Begrensninger for tabell `reservation_services`
--
ALTER TABLE `reservation_services`
  ADD CONSTRAINT `reservation_services_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reservation_services_ibfk_2` FOREIGN KEY (`output_product_id`) REFERENCES `output_products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Begrensninger for tabell `reservation_service_bookings`
--
ALTER TABLE `reservation_service_bookings`
  ADD CONSTRAINT `reservation_service_bookings_ibfk_1` FOREIGN KEY (`reservation_id`) REFERENCES `reservations_reservations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reservation_service_bookings_ibfk_2` FOREIGN KEY (`service_id`) REFERENCES `reservation_services` (`id`);

--
-- Begrensninger for tabell `saas_admins`
--
ALTER TABLE `saas_admins`
  ADD CONSTRAINT `saas_admins_ibfk_1` FOREIGN KEY (`saas_staff_id`) REFERENCES `saas_staff` (`id`) ON DELETE CASCADE;

--
-- Begrensninger for tabell `saas_subscription_plans`
--
ALTER TABLE `saas_subscription_plans`
  ADD CONSTRAINT `saas_subscription_plans_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `saas_admins` (`id`);

--
-- Begrensninger for tabell `saas_system_config`
--
ALTER TABLE `saas_system_config`
  ADD CONSTRAINT `saas_system_config_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `saas_admins` (`id`);

--
-- Begrensninger for tabell `sales_cart`
--
ALTER TABLE `sales_cart`
  ADD CONSTRAINT `sales_cart_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`),
  ADD CONSTRAINT `sales_cart_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `tenant_admins` (`id`),
  ADD CONSTRAINT `sales_cart_ibfk_3` FOREIGN KEY (`output_product_id`) REFERENCES `output_products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Begrensninger for tabell `sales_categories`
--
ALTER TABLE `sales_categories`
  ADD CONSTRAINT `sales_categories_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sales_categories_ibfk_2` FOREIGN KEY (`parent_category_id`) REFERENCES `sales_categories` (`id`);

--
-- Begrensninger for tabell `sales_orders`
--
ALTER TABLE `sales_orders`
  ADD CONSTRAINT `sales_orders_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sales_orders_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `tenant_customers` (`id`),
  ADD CONSTRAINT `sales_orders_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `tenant_admins` (`id`);

--
-- Begrensninger for tabell `sales_orders_items`
--
ALTER TABLE `sales_orders_items`
  ADD CONSTRAINT `sales_orders_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `sales_orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sales_orders_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `sales_products` (`id`);

--
-- Begrensninger for tabell `sales_products`
--
ALTER TABLE `sales_products`
  ADD CONSTRAINT `fk_sales_products_output_product` FOREIGN KEY (`output_product_id`) REFERENCES `output_products` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `sales_products_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sales_products_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `sales_categories` (`id`);

--
-- Begrensninger for tabell `sales_products_stock`
--
ALTER TABLE `sales_products_stock`
  ADD CONSTRAINT `sales_products_stock_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `sales_products` (`id`) ON DELETE CASCADE;

--
-- Begrensninger for tabell `sales_transactions`
--
ALTER TABLE `sales_transactions`
  ADD CONSTRAINT `sales_transactions_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`),
  ADD CONSTRAINT `sales_transactions_ibfk_2` FOREIGN KEY (`sales_product_id`) REFERENCES `sales_products` (`id`);

--
-- Begrensninger for tabell `self_company`
--
ALTER TABLE `self_company`
  ADD CONSTRAINT `self_company_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `saas_admins` (`id`);

--
-- Begrensninger for tabell `service_bookings`
--
ALTER TABLE `service_bookings`
  ADD CONSTRAINT `service_bookings_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `service_bookings_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `tenant_admins` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `service_bookings_ibfk_3` FOREIGN KEY (`assigned_staff_id`) REFERENCES `tenant_staff` (`id`) ON DELETE SET NULL;

--
-- Begrensninger for tabell `tenant_admins`
--
ALTER TABLE `tenant_admins`
  ADD CONSTRAINT `tenant_admins_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tenant_admins_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `saas_admins` (`id`);

--
-- Begrensninger for tabell `tenant_admin_modules`
--
ALTER TABLE `tenant_admin_modules`
  ADD CONSTRAINT `tenant_admin_modules_ibfk_1` FOREIGN KEY (`tenant_admin_id`) REFERENCES `tenant_admins` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tenant_admin_modules_ibfk_2` FOREIGN KEY (`module_id`) REFERENCES `system_modules` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tenant_admin_modules_ibfk_3` FOREIGN KEY (`granted_by`) REFERENCES `saas_admins` (`id`);

--
-- Begrensninger for tabell `tenant_admin_sessions`
--
ALTER TABLE `tenant_admin_sessions`
  ADD CONSTRAINT `tenant_admin_sessions_ibfk_1` FOREIGN KEY (`tenant_admin_id`) REFERENCES `tenant_admins` (`id`) ON DELETE CASCADE;

--
-- Begrensninger for tabell `tenant_announcements`
--
ALTER TABLE `tenant_announcements`
  ADD CONSTRAINT `fk_tenant_announcements_author` FOREIGN KEY (`author_id`) REFERENCES `tenant_admins` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_tenant_announcements_company` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_tenant_announcements_event` FOREIGN KEY (`related_event_id`) REFERENCES `events_eventdata` (`id`) ON DELETE SET NULL;

--
-- Begrensninger for tabell `tenant_companies`
--
ALTER TABLE `tenant_companies`
  ADD CONSTRAINT `tenant_companies_ibfk_1` FOREIGN KEY (`onboarded_by`) REFERENCES `saas_admins` (`id`);

--
-- Begrensninger for tabell `tenant_company_modules`
--
ALTER TABLE `tenant_company_modules`
  ADD CONSTRAINT `tenant_company_modules_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tenant_company_modules_ibfk_2` FOREIGN KEY (`module_id`) REFERENCES `system_modules` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tenant_company_modules_ibfk_3` FOREIGN KEY (`assigned_by`) REFERENCES `saas_admins` (`id`);

--
-- Begrensninger for tabell `tenant_customers`
--
ALTER TABLE `tenant_customers`
  ADD CONSTRAINT `tenant_customers_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE;

--
-- Begrensninger for tabell `tenant_email_config`
--
ALTER TABLE `tenant_email_config`
  ADD CONSTRAINT `tenant_email_config_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE;

--
-- Begrensninger for tabell `tenant_settings`
--
ALTER TABLE `tenant_settings`
  ADD CONSTRAINT `tenant_settings_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE;

--
-- Begrensninger for tabell `tenant_staff`
--
ALTER TABLE `tenant_staff`
  ADD CONSTRAINT `tenant_staff_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tenant_staff_ibfk_2` FOREIGN KEY (`tenant_admin_id`) REFERENCES `tenant_admins` (`id`) ON DELETE SET NULL;

--
-- Begrensninger for tabell `tenant_staff_modules`
--
ALTER TABLE `tenant_staff_modules`
  ADD CONSTRAINT `tenant_staff_modules_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `tenant_staff` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tenant_staff_modules_ibfk_2` FOREIGN KEY (`module_id`) REFERENCES `system_modules` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tenant_staff_modules_ibfk_3` FOREIGN KEY (`granted_by`) REFERENCES `saas_admins` (`id`);

--
-- Begrensninger for tabell `tenant_subscriptions`
--
ALTER TABLE `tenant_subscriptions`
  ADD CONSTRAINT `tenant_subscriptions_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tenant_subscriptions_ibfk_3` FOREIGN KEY (`plan_id`) REFERENCES `saas_subscription_plans` (`id`);

--
-- Begrensninger for tabell `tenant_terms_acceptances`
--
ALTER TABLE `tenant_terms_acceptances`
  ADD CONSTRAINT `tenant_terms_acceptances_ibfk_1` FOREIGN KEY (`terms_conditions_id`) REFERENCES `tenant_terms_conditions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tenant_terms_acceptances_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `tenant_customers` (`id`) ON DELETE CASCADE;

--
-- Begrensninger for tabell `tenant_terms_conditions`
--
ALTER TABLE `tenant_terms_conditions`
  ADD CONSTRAINT `tenant_terms_conditions_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tenant_terms_conditions_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `tenant_admins` (`id`);

--
-- Begrensninger for tabell `tenant_website_config`
--
ALTER TABLE `tenant_website_config`
  ADD CONSTRAINT `tenant_website_config_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tenant_website_config_ibfk_2` FOREIGN KEY (`module_id`) REFERENCES `system_modules` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tenant_website_config_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `tenant_admins` (`id`),
  ADD CONSTRAINT `tenant_website_config_ibfk_4` FOREIGN KEY (`updated_by`) REFERENCES `tenant_admins` (`id`);

--
-- Begrensninger for tabell `tenant_website_general`
--
ALTER TABLE `tenant_website_general`
  ADD CONSTRAINT `tenant_website_general_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tenant_website_general_ibfk_2` FOREIGN KEY (`homepage_module`) REFERENCES `system_modules` (`id`);

--
-- Begrensninger for tabell `unified_ledger`
--
ALTER TABLE `unified_ledger`
  ADD CONSTRAINT `unified_ledger_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
