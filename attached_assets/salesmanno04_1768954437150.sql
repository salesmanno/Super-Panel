-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: salesmanno04.mysql.domeneshop.no
-- Generation Time: 21. Jan, 2026 00:13 AM
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

DELIMITER $$
--
-- Prosedyrer
--
CREATE DEFINER=`salesmanno04`@`2a01:5b40:0:%` PROCEDURE `ChangeCompanyPassword` (IN `p_company_id` CHAR(36), IN `p_current_password` VARCHAR(255), IN `p_new_password_hash` VARCHAR(255), OUT `p_success` TINYINT, OUT `p_message` VARCHAR(255))   BEGIN
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
END$$

CREATE DEFINER=`salesmanno04`@`2a01:5b40:0:%` PROCEDURE `LinkEventResultToLedger` (IN `p_event_result_id` CHAR(36))   BEGIN
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

END$$

CREATE DEFINER=`salesmanno04`@`%` PROCEDURE `SafeDeleteCompany` (IN `p_company_id` CHAR(36))   BEGIN
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
END$$

CREATE DEFINER=`salesmanno04`@`2a01:5b40:0:%` PROCEDURE `UpsertCustomer` (IN `p_company_id` CHAR(36), IN `p_first_name` VARCHAR(255), IN `p_last_name` VARCHAR(255), IN `p_email` VARCHAR(255), IN `p_phone` VARCHAR(255), IN `p_customer_type` VARCHAR(20))   BEGIN
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
END$$

--
-- Funsjoner
--
CREATE DEFINER=`salesmanno04`@`2a01:5b40:0:%` FUNCTION `VerifyCompanyPassword` (`p_company_id` CHAR(36), `p_password_hash` VARCHAR(255)) RETURNS TINYINT(4) DETERMINISTIC READS SQL DATA BEGIN
    DECLARE v_stored_hash VARCHAR(255);
    DECLARE v_result TINYINT DEFAULT 0;
    
    SELECT admin_password_hash INTO v_stored_hash 
    FROM tenant_companies 
    WHERE id = p_company_id AND is_active = 1;
    
    IF v_stored_hash IS NOT NULL AND v_stored_hash = p_password_hash THEN
        SET v_result = 1;
    END IF;
    
    RETURN v_result;
END$$

DELIMITER ;

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
-- Tabellstruktur for tabell `announcement_products`
--

CREATE TABLE `announcement_products` (
  `id` int(11) NOT NULL,
  `company_id` char(36) NOT NULL,
  `announcement_id` char(36) NOT NULL,
  `output_product_id` char(36) NOT NULL,
  `discount_percent` decimal(5,2) DEFAULT 0.00,
  `max_quantity` int(11) DEFAULT NULL,
  `amount_sold` int(11) DEFAULT 0,
  `expiry_date` date DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dataark for tabell `announcement_products`
--

INSERT INTO `announcement_products` (`id`, `company_id`, `announcement_id`, `output_product_id`, `discount_percent`, `max_quantity`, `amount_sold`, `expiry_date`, `created_at`) VALUES
(7, '7efbddea-b507-11f0-936b-b02628506e5c', 'ce605bc8-e75a-11f0-ada2-b02628506e5c', '12a8901486c06b666ba289d8206da1e1', 0.00, NULL, 0, '2026-02-08', '2026-01-16 17:49:15');

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

--
-- Dataark for tabell `audit_logs`
--

INSERT INTO `audit_logs` (`id`, `company_id`, `user_id`, `user_type`, `user_email`, `action`, `resource_type`, `resource_id`, `resource_name`, `old_values`, `new_values`, `ip_address`, `user_agent`, `severity`, `performed_at`, `module`, `session_id`, `location`, `device_info`) VALUES
('15d01eaadc0fbe220c96b6c242503ece', '7efbddea-b507-11f0-936b-b02628506e5c', '7f0d55c4-b507-11f0-936b-b02628506e5c', 'tenant_admin', 'atlekarl@tussa.com', 'NO_SHOW_MARKED', 'events_checkin', 'f75c5d85e810dd85eb50005be4e3ea53', 'Stordans på Gjerde - Jose Kull', '{\"checked_in\":0,\"payment_status\":\"pending\"}', '{\"checked_in\":1,\"payment_status\":\"no_show\",\"payment_method\":null,\"ticket_price\":350,\"admin_name\":\"Atle Karl Kvalsvik\",\"customer_name\":\"Jose Kull\",\"action_message\":\"Atle Karl Kvalsvik markerte Jose Kull som no-show for arrangement \'Stordans p\\u00e5 Gjerde\'.\"}', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', 'info', '2025-12-04 10:45:37', 'events', '227959d47ae18cb81032c78e46d65b4a', NULL, NULL),
('2d6f684e5b604fcbc016952bfb13740a', '7efbddea-b507-11f0-936b-b02628506e5c', '7f0d55c4-b507-11f0-936b-b02628506e5c', 'tenant_admin', 'atlekarl@tussa.com', 'EVENT_CHECKIN', 'events_checkin', 'bf8beeb7ce94a93503814f7e09501d97', 'Stordans på Gjerde - Markus Nilsen', '{\"checked_in\":0,\"payment_status\":\"pending\"}', '{\"checked_in\":1,\"payment_status\":\"paid\",\"payment_method\":\"cash\",\"ticket_price\":350,\"admin_name\":\"Atle Karl Kvalsvik\",\"customer_name\":\"Markus Nilsen\",\"action_message\":\"Atle Karl Kvalsvik sjekket inn Markus Nilsen mot betaling p\\u00e5 350 kr gjennom kontant.\"}', '195.181.132.108', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', 'info', '2025-12-06 12:55:41', 'events', 'fbb9036485671041fa803f05c2878828', NULL, NULL),
('3c330e60-b1da-11f0-b9ad-b02628506e5c', '453d3f87-b0e4-11f0-a1bb-b02628506e5c', NULL, NULL, NULL, 'COMPANY_PASSWORD_CHANGED', 'tenant_company', '453d3f87-b0e4-11f0-a1bb-b02628506e5c', 'Nordic Fitness AS', '{\"password_changed_at\": \"2025-10-25 11:57:14\"}', '{\"password_changed_at\": \"2025-10-25 11:57:14\"}', NULL, NULL, 'info', '2025-10-25 21:38:51', NULL, NULL, NULL, NULL),
('3dbadfe0-b1da-11f0-b9ad-b02628506e5c', '453d3f87-b0e4-11f0-a1bb-b02628506e5c', NULL, NULL, NULL, 'COMPANY_PASSWORD_CHANGED', 'tenant_company', '453d3f87-b0e4-11f0-a1bb-b02628506e5c', 'Nordic Fitness AS', '{\"password_changed_at\": \"2025-10-25 11:57:14\"}', '{\"password_changed_at\": \"2025-10-25 11:57:14\"}', NULL, NULL, 'info', '2025-10-25 21:38:54', NULL, NULL, NULL, NULL),
('3eb73c079acfc0c8a47e26c7a23265de', '7efbddea-b507-11f0-936b-b02628506e5c', '7f0d55c4-b507-11f0-936b-b02628506e5c', 'tenant_admin', 'atlekarl@tussa.com', 'EVENT_CHECKIN', 'events_checkin', '535fb8610539d4bf9b0995f862185e2bf806', 'Stordans på Gjerde - Atle Karl Kvalsvik', '{\"checked_in\":0,\"payment_status\":\"paid\"}', '{\"checked_in\":1,\"payment_status\":\"paid\",\"payment_method\":\"cash\",\"ticket_price\":350,\"admin_name\":\"Atle Karl Kvalsvik\",\"customer_name\":\"Atle Karl Kvalsvik\",\"action_message\":\"Atle Karl Kvalsvik sjekket inn Atle Karl Kvalsvik mot betaling p\\u00e5 350 kr gjennom kontant.\"}', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', 'info', '2025-12-04 10:45:03', 'events', '227959d47ae18cb81032c78e46d65b4a', NULL, NULL),
('3f3a15c0-b1da-11f0-b9ad-b02628506e5c', '453d3f87-b0e4-11f0-a1bb-b02628506e5c', NULL, NULL, NULL, 'COMPANY_PASSWORD_CHANGED', 'tenant_company', '453d3f87-b0e4-11f0-a1bb-b02628506e5c', 'Nordic Fitness AS', '{\"password_changed_at\": \"2025-10-25 11:57:14\"}', '{\"password_changed_at\": \"2025-10-25 11:57:14\"}', NULL, NULL, 'info', '2025-10-25 21:38:57', NULL, NULL, NULL, NULL),
('49e63fae-b1da-11f0-b9ad-b02628506e5c', '453d3f87-b0e4-11f0-a1bb-b02628506e5c', NULL, NULL, NULL, 'COMPANY_PASSWORD_CHANGED', 'tenant_company', '453d3f87-b0e4-11f0-a1bb-b02628506e5c', 'Nordic Fitness AS', '{\"password_changed_at\": \"2025-10-25 11:57:14\"}', '{\"password_changed_at\": \"2025-10-25 11:57:14\"}', NULL, NULL, 'info', '2025-10-25 21:39:14', NULL, NULL, NULL, NULL),
('6bcc54f7389aa7599a6e498e02fe3534', '7efbddea-b507-11f0-936b-b02628506e5c', '7f0d55c4-b507-11f0-936b-b02628506e5c', 'tenant_admin', 'atlekarl@tussa.com', 'EVENT_CHECKIN', 'events_checkin', '8fcb3ea0a01ea6e11cb99f3a9cce1e9305e3', 'Hei - Magne Kvalsvik', '{\"checked_in\":0,\"payment_status\":\"paid\"}', '{\"checked_in\":1,\"payment_status\":\"paid\",\"payment_method\":\"cash\",\"ticket_price\":2,\"admin_name\":\"Atle Karl Kvalsvik\",\"customer_name\":\"Magne Kvalsvik\",\"action_message\":\"Atle Karl Kvalsvik sjekket inn Magne Kvalsvik mot betaling p\\u00e5 2 kr gjennom kontant.\"}', '45.14.192.138', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', 'info', '2025-12-15 21:21:48', 'events', '591d88e97c1207bf69b7fef36904dc7d', NULL, NULL),
('a7c29ae3cd4053ced09cbc18f0f044dd', '7efbddea-b507-11f0-936b-b02628506e5c', '7f0d55c4-b507-11f0-936b-b02628506e5c', 'tenant_admin', 'atlekarl@tussa.com', 'EVENT_CHECKIN', 'events_checkin', 'f9e1c0c77a35651e22fee4b37d6e6371', 'Januardansen - Magis Lagis', '{\"checked_in\":0,\"payment_status\":\"pending\"}', '{\"checked_in\":1,\"payment_status\":\"paid\",\"payment_method\":\"vipps\",\"ticket_price\":500,\"admin_name\":\"Atle Karl Kvalsvik\",\"customer_name\":\"Magis Lagis\",\"action_message\":\"Atle Karl Kvalsvik sjekket inn Magis Lagis mot betaling p\\u00e5 500 kr gjennom Vipps.\"}', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', 'info', '2025-12-23 20:51:28', 'events', '6d101b44109c1587bbe50cd464131e48', NULL, NULL),
('c2da24cfa73ab07a77cd9f20b90b2ed7', '7efbddea-b507-11f0-936b-b02628506e5c', 'f996adb8d578c9f15859cc9ffb78b023792b', 'tenant_admin', '', 'EVENT_CHECKIN', 'events_checkin', 'a822e5739efbc6d24de45453b99ce29d', 'Stordans på Gjerde - Magne Kvalsvik', '{\"checked_in\":0,\"payment_status\":\"pending\"}', '{\"checked_in\":1,\"payment_status\":\"paid\",\"payment_method\":\"cash\",\"ticket_price\":350,\"admin_name\":\"\",\"customer_name\":\"Magne Kvalsvik\",\"action_message\":\" sjekket inn Magne Kvalsvik mot betaling p\\u00e5 350 kr gjennom kontant.\"}', '89.8.5.177', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', 'info', '2025-12-06 14:13:42', 'events', '3b306538dc88a9499d6781cc5f62745e', NULL, NULL),
('c3c46e7c8ea005aad9bfb8c6d113c59f', '7efbddea-b507-11f0-936b-b02628506e5c', '7f0d55c4-b507-11f0-936b-b02628506e5c', 'tenant_admin', 'atlekarl@tussa.com', 'EVENT_CHECKIN', 'events_checkin', '2082ce2f79a70f700cb147a538ab50ea', 'Hei - Gustav Lisjebø', '{\"checked_in\":0,\"payment_status\":\"pending\"}', '{\"checked_in\":1,\"payment_status\":\"paid\",\"payment_method\":\"cash\",\"ticket_price\":4,\"admin_name\":\"Atle Karl Kvalsvik\",\"customer_name\":\"Gustav Lisjeb\\u00f8\",\"action_message\":\"Atle Karl Kvalsvik sjekket inn Gustav Lisjeb\\u00f8 mot betaling p\\u00e5 4 kr gjennom kontant.\"}', '45.14.192.138', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', 'info', '2025-12-15 21:23:53', 'events', '591d88e97c1207bf69b7fef36904dc7d', NULL, NULL),
('d2ccf9245a6065934ed656c179b8f48e', '7efbddea-b507-11f0-936b-b02628506e5c', '7f072d6a-b507-11f0-936b-b02628506e5c', 'tenant_admin', 'maritahr@gmail.com', 'EVENT_CHECKIN', 'events_checkin', 'dbd66343b37103db5387b165240d9b5e8b2e', 'HYTT Å PINE  - Atle Karl Kvalsvik', '{\"checked_in\":0,\"payment_status\":\"paid\"}', '{\"checked_in\":1,\"payment_status\":\"paid\",\"payment_method\":\"vipps\",\"ticket_price\":2,\"admin_name\":\"Marita Higraf Rem\\u00f8y\",\"customer_name\":\"Atle Karl Kvalsvik\",\"action_message\":\"Marita Higraf Rem\\u00f8y sjekket inn Atle Karl Kvalsvik mot betaling p\\u00e5 2 kr gjennom Vipps.\"}', '45.14.192.221', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', 'info', '2025-12-16 22:26:42', 'events', '34c3a9327048d86fdca41d8a70d19d25', NULL, NULL);

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

--
-- Dataark for tabell `core_sys_news`
--

INSERT INTO `core_sys_news` (`id`, `title`, `content`, `news_type`, `severity`, `is_published`, `publish_at`, `expire_at`, `target_audience`, `created_by`, `created_at`, `updated_at`) VALUES
('abf72214-b1ee-11f0-b9ad-b02628506e5c', 'Velkommen', 'testversjon av bedriftsportalen vår.\n\nmanglende eller glemt passord, kontakt 404 69 364', 'info', 'low', 1, '2025-10-26 00:05:09', NULL, 'all', NULL, '2025-10-26 00:05:09', '2025-11-29 08:16:05');

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
-- Tabellstruktur for tabell `economy_configuration_data`
--

CREATE TABLE `economy_configuration_data` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `company_id` char(36) NOT NULL COMMENT 'Hvilken bedrift dette gjelder for',
  `config_name` varchar(100) NOT NULL COMMENT 'Navn på betalingsløsning (Vipps, Stripe, PayPal)',
  `gateway_type` varchar(50) NOT NULL COMMENT 'vipps, stripe, paypal, bank, custom',
  `client_id` varchar(255) DEFAULT NULL,
  `client_secret_encrypted` text DEFAULT NULL COMMENT 'Kryptert secret',
  `subscription_key` varchar(255) DEFAULT NULL,
  `merchant_serial_number` varchar(255) DEFAULT NULL,
  `public_key` varchar(255) DEFAULT NULL,
  `private_key_encrypted` text DEFAULT NULL,
  `webhook_secret` varchar(255) DEFAULT NULL,
  `webhook_id` varchar(255) DEFAULT NULL,
  `config_data` text DEFAULT NULL COMMENT 'JSON med ekstra konfigurasjon',
  `is_active` tinyint(1) DEFAULT 0 COMMENT 'Er denne konfigurasjonen aktiv?',
  `test_mode` tinyint(1) DEFAULT 1 COMMENT 'Testmodus (sandbox)',
  `configurated_by` char(36) NOT NULL COMMENT 'SAAS admin som konfigurerte',
  `last_used` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dataark for tabell `economy_configuration_data`
--

INSERT INTO `economy_configuration_data` (`id`, `company_id`, `config_name`, `gateway_type`, `client_id`, `client_secret_encrypted`, `subscription_key`, `merchant_serial_number`, `public_key`, `private_key_encrypted`, `webhook_secret`, `webhook_id`, `config_data`, `is_active`, `test_mode`, `configurated_by`, `last_used`, `created_at`, `updated_at`) VALUES
('af1cfae7-dbe5-11f0-bffa-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'vipps', 'vipps', 'b6b386ba-1719-4cda-9ea9-043597fff764', 'TiP8Q~tdV9CTAFL2xKySzcOGKILIUPUsvjWLOamD', 'cea291f95463f5f2a1f547ea6fa2552b', '1049459', NULL, NULL, NULL, NULL, NULL, 1, 0, '', NULL, '2025-12-18 08:46:38', '2026-01-18 00:34:05');

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
  `configuration_id` char(36) DEFAULT NULL,
  `gateway_name` varchar(50) NOT NULL,
  `is_active` tinyint(1) DEFAULT 0,
  `test_mode` tinyint(1) DEFAULT 1,
  `created_by` char(36) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dataark for tabell `economy_payment_gateways`
--

INSERT INTO `economy_payment_gateways` (`id`, `company_id`, `configuration_id`, `gateway_name`, `is_active`, `test_mode`, `created_by`, `created_at`, `updated_at`) VALUES
('c6c2ef2a-d47c-11f0-9f08-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'af1cfae7-dbe5-11f0-bffa-b02628506e5c', 'vipps', 1, 0, NULL, '2025-12-08 22:28:02', '2025-12-18 08:46:38');

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
  `imap_password_encrypted` text DEFAULT NULL,
  `signature` text DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `last_sync` datetime DEFAULT NULL,
  `created_by` char(36) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `sync_status` enum('idle','syncing','error') DEFAULT 'idle',
  `error_message` varchar(500) DEFAULT NULL COMMENT 'Siste feilmelding',
  `alertNew` tinyint(4) DEFAULT 0,
  `last_sync_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dataark for tabell `email_accounts`
--

INSERT INTO `email_accounts` (`id`, `company_id`, `email_address`, `display_name`, `imap_password_encrypted`, `signature`, `is_default`, `is_active`, `last_sync`, `created_by`, `created_at`, `updated_at`, `sync_status`, `error_message`, `alertNew`, `last_sync_at`) VALUES
('6bcefb5e-f4b1-11f0-abb4-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'bergsoy.d-k@salesman.no', 'Bergsøy Danseklubb', 'ibis-vevre-Nop-bulka-1700', 'Vennlig hilsen', 1, 1, NULL, NULL, '2026-01-18 22:05:30', '2026-01-18 22:05:30', 'idle', NULL, 0, NULL);

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
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `imap_host` varchar(255) DEFAULT NULL,
  `imap_port` int(11) DEFAULT 993,
  `imap_username` varchar(255) DEFAULT NULL,
  `imap_password_encrypted` varchar(255) DEFAULT NULL,
  `automail_events_confirm` tinyint(1) DEFAULT NULL COMMENT 'Bekreftelse ved påmelding',
  `automail_events_reminder_3d` tinyint(1) DEFAULT NULL COMMENT 'Påminnelse 3 dager før arrangement',
  `automail_events_thankyou` tinyint(1) DEFAULT NULL COMMENT 'Takkebrev etter arrangement',
  `automail_sales_order` tinyint(1) DEFAULT NULL COMMENT 'Ordrebekreftelse ved kjøp',
  `automail_membership_welcome` tinyint(1) DEFAULT NULL COMMENT 'Velkomst-e-post ved registrering',
  `automail_membership_renewal` tinyint(1) DEFAULT NULL COMMENT 'Fornyelsespåminnelse',
  `automail_reservation_confirm` tinyint(1) DEFAULT NULL COMMENT 'Reservasjonsbekreftelse',
  `automail_reservation_reminder` tinyint(1) DEFAULT NULL COMMENT 'Påminnelse før reservasjon',
  `automail_rental_confirm` tinyint(1) DEFAULT NULL COMMENT 'Utleiebekreftelse',
  `automail_rental_return` tinyint(1) DEFAULT NULL COMMENT 'Returpåminnelse'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dataark for tabell `email_configs`
--

INSERT INTO `email_configs` (`id`, `company_id`, `email`, `display_name`, `smtp_host`, `smtp_port`, `smtp_username`, `smtp_password_encrypted`, `is_active`, `created_at`, `updated_at`, `imap_host`, `imap_port`, `imap_username`, `imap_password_encrypted`, `automail_events_confirm`, `automail_events_reminder_3d`, `automail_events_thankyou`, `automail_sales_order`, `automail_membership_welcome`, `automail_membership_renewal`, `automail_reservation_confirm`, `automail_reservation_reminder`, `automail_rental_confirm`, `automail_rental_return`) VALUES
('d7652502-f4b0-11f0-abb4-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'bergsoy.d-k@salesman.no', 'Bergsøy Danseklubb', 'smtp.domeneshop.no', 587, 'salesman4', 'ibis-vevre-Nop-bulka-1700', 1, '2026-01-18 22:01:21', '2026-01-20 00:05:08', 'imap.domeneshop.no', 993, 'salesman4', 'ibis-vevre-Nop-bulka-1700', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

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
  `qrcode` longblob DEFAULT NULL,
  `group_id` char(36) DEFAULT NULL,
  `is_primary_payer` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dataark for tabell `events_checkin`
--

INSERT INTO `events_checkin` (`id`, `registration_id`, `event_id`, `customer_id`, `membership_plan_id`, `ticket_price`, `payment_status`, `payment_method`, `checked_in`, `check_in_time`, `checked_in_by`, `notes`, `created_at`, `updated_at`, `barcode`, `qrcode`, `group_id`, `is_primary_payer`) VALUES
('0d279c3df308bbf1dac3cafd620f5406', '3d53b0d6-1e60-4ebd-9c4d-66097fdd4157', 'fc8b18485aae1b5d3890656c5ad46c39', '126ad5cce3731f36e5e4fcc35fe7071bf427', NULL, 400.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-18 12:55:47', '2026-01-18 12:55:47', '000050754267', NULL, NULL, 0),
('110992b16d178f63d83d0d90095a3e1ee402', '7c4f7c6f-0a1b-4218-a645-886b7f67f07e', 'fc8b18485aae1b5d3890656c5ad46c39', '69f68bbe-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 350.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-13 23:22:23', '2026-01-13 23:22:23', NULL, NULL, NULL, 1),
('1865cbffb8020f1b27935320d1a9654a', '46efb8e9-5195-4e9a-9f86-d8eb096dc06e', 'fc8b18485aae1b5d3890656c5ad46c39', '6464d76a21886c2e71638c9e85a9ff7889a5', NULL, 400.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-19 11:24:27', '2026-01-19 11:24:27', '000000899949', NULL, NULL, 0),
('22eda70a0bea4f03b04650aad37aa906', '55ad7edf-79a9-43ab-b9d8-06169c38a317', 'fc8b18485aae1b5d3890656c5ad46c39', 'bf9f4835cf86fc6ddf91f87955bb0c4c7551', NULL, 400.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-18 19:24:03', '2026-01-18 19:24:03', '000083682674', NULL, NULL, 0),
('24988c5ab4fbb6c92937cda7a364d8832ac8', '82fe5a54-87dc-4a64-8af9-3006b818b68c', 'fc8b18485aae1b5d3890656c5ad46c39', '69f6bc66-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 350.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-14 11:01:22', '2026-01-14 11:01:22', NULL, NULL, NULL, 1),
('2ffebd165e0fa87a1817e8b5183e7050a6cd', 'fe54301a-121f-4579-98d4-47058d64aa3c', 'fc8b18485aae1b5d3890656c5ad46c39', '69f67dab-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 350.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-15 10:00:06', '2026-01-15 10:00:06', NULL, NULL, NULL, 1),
('3cc1aeee0f8778c12e681433f71e1b8d94f1', 'e2b5ec1f-a737-4196-9fca-d94f9017e70c', 'fc8b18485aae1b5d3890656c5ad46c39', '69f68433-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 350.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-12 14:51:34', '2026-01-12 14:51:34', NULL, NULL, NULL, 1),
('4a4dce36fa5f7017af3b9ac76c3dd3c8', 'c9600f91-9edd-4547-9238-c7d7792042ea', 'fc8b18485aae1b5d3890656c5ad46c39', '15944770a5255ab2256b1540c674690f7ab0', NULL, 400.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-18 12:56:09', '2026-01-18 12:56:09', '000004072676', NULL, NULL, 0),
('4a75a5083a794ad21362b63327c8f98999d4', 'e76a2423-5ebc-4035-885e-5b5b5188ce9c', 'fc8b18485aae1b5d3890656c5ad46c39', '25cc3f4b5c68868c67f159018d1c8a74f86c', NULL, 350.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-20 19:55:49', '2026-01-20 19:55:49', NULL, NULL, NULL, 1),
('4c075708d4de46ef7bc5e624233cd7509e13', '8b22136f-d044-4c6a-9859-de3205e0e8c8', 'fc8b18485aae1b5d3890656c5ad46c39', '69f699af-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 350.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-14 11:01:22', '2026-01-14 11:01:22', NULL, NULL, NULL, 1),
('4cd129e96119a08515f8a4df26d8c718aa2c', '971f458f-8d80-495e-95d8-7e68ef780a9b', 'fc8b18485aae1b5d3890656c5ad46c39', 'f5910bfa5f03f754a6cb2bd11b856cf744dc', NULL, 350.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-21 01:11:57', '2026-01-21 01:11:57', NULL, NULL, NULL, 1),
('4e9003e244246c67672efddd4f1cae99', '219962e1-b43e-483b-9fb5-def99114ce91', 'fc8b18485aae1b5d3890656c5ad46c39', 'a467996f28c150ab799bb9e5d247b42c46a3', NULL, 400.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-19 11:25:15', '2026-01-19 11:25:15', '036601533463', NULL, NULL, 0),
('579f21f14d55d3ccef8c23b36be6efaa', '26b736d6-9841-48a3-af14-6b493d7ca7f6', 'fc8b18485aae1b5d3890656c5ad46c39', 'd9ea2f0ab8930466f89a72ab2891ddfc15aa', NULL, 400.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-20 09:15:34', '2026-01-20 09:15:34', '000041745281', NULL, NULL, 0),
('5843e5e41c48cb1cd80146df6641bc2c', 'c5611fe6-fdac-469a-8426-9a75ac4a946b', 'fc8b18485aae1b5d3890656c5ad46c39', 'aa9d757529e17a7420904344cfd7f705bedf', NULL, 400.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-18 12:56:02', '2026-01-18 12:56:02', '000272430994', NULL, NULL, 0),
('5b95dabbc6f4b68f8e50a9b8feb4218e', '5b94c5bc-9a80-425b-9dfd-18bfe72a4b2a', 'fc8b18485aae1b5d3890656c5ad46c39', '9d4522d0bb354d2e8dc8976571b46ac6cb0c', NULL, 400.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-18 12:55:55', '2026-01-18 12:55:55', '000008585714', NULL, NULL, 0),
('5cab63356bfaa9c9e46cbf767c9be959ed77', '23b5ad1d-c7b9-4dd4-8ba8-46ccf879f814', 'fc8b18485aae1b5d3890656c5ad46c39', '69f6a493-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 350.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-15 10:26:25', '2026-01-15 10:26:25', NULL, NULL, NULL, 1),
('65b79be68d76a10c1c4406a20cdedaaa6356', '9344192b-fb4c-40a8-bec3-db3a18ec31d3', 'fc8b18485aae1b5d3890656c5ad46c39', '69f6c081-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 350.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-16 17:09:00', '2026-01-16 17:09:00', NULL, NULL, NULL, 1),
('68c572a237fa85cec7ce1c0094c94051', 'd6877ac9-b9c4-4414-91a2-1a215ce1e627', 'fc8b18485aae1b5d3890656c5ad46c39', '1d3d03015dfb1b6e7e23eeac23ae28fde5f3', NULL, 400.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-19 11:25:02', '2026-01-19 11:25:02', '000046957665', NULL, NULL, 0),
('71342cffc5bd7f498bb13daeaa1abdaf', '16ca518c-5ebf-427d-bc15-87f1e9fc6aa7', 'fc8b18485aae1b5d3890656c5ad46c39', '1e0d309b046171d91bffb32bf1a570856777', NULL, 400.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-19 11:24:55', '2026-01-19 11:24:55', '000487123346', NULL, NULL, 0),
('71fe6dd74275770f8b1c89318c8af5f0', '44b74740-5981-4fb7-88ba-db347d80f8b4', 'fc8b18485aae1b5d3890656c5ad46c39', 'ccc35a6aaebde1957a9ffdb10c87899fa0e0', NULL, 400.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-16 05:48:34', '2026-01-16 05:48:34', '000077832269', NULL, NULL, 0),
('79d9779fb8f21ed6c294b59af4a579103cfe', '38b609b9-4cd9-4c56-ba6a-4632e71c412c', 'fc8b18485aae1b5d3890656c5ad46c39', 'da1e7e8cfeed4c04a6097df30dc77eeed0c4', NULL, 350.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-20 09:54:53', '2026-01-20 09:54:53', NULL, NULL, NULL, 1),
('7a6de12b0fae025d87eb7c736fbe54139f9d', 'd29c3f0e-e33a-4146-8c03-97857302b0da', 'fc8b18485aae1b5d3890656c5ad46c39', '69f6c282-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 350.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-15 17:34:52', '2026-01-15 17:34:52', NULL, NULL, NULL, 1),
('7e494d360252fe5a977972c47244659b', '1018f7b3-a52f-4421-a671-9573d7965638', 'fc8b18485aae1b5d3890656c5ad46c39', 'c671dc6594f96ab7ebf201d00cb6b6e3de1d', NULL, 400.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-18 19:23:58', '2026-01-18 19:23:58', '000008328748', NULL, NULL, 0),
('8232ab97c3f3c6bff269fdc41b187374', 'b164a78f-106d-41be-90b9-36cb489bfe54', 'fc8b18485aae1b5d3890656c5ad46c39', 'b5578a551dfa2b0a2a2325802764ff5641cd', NULL, 400.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-20 19:57:09', '2026-01-20 19:57:09', '005895550685', NULL, NULL, 0),
('877772d70213e6b58e441a362f7a10bf', '33f55092-88b0-45f2-bf23-192726514b8b', 'fc8b18485aae1b5d3890656c5ad46c39', 'f5fa7c635ec8f00b60f79e6b8323489109b1', NULL, 400.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-20 00:40:24', '2026-01-20 00:40:24', '006801469046', NULL, NULL, 0),
('8b896dd4aa82bb4c740e8009a5eb9c0b', '213c8cf7-6499-4578-b2bb-714f79c9406c', 'fc8b18485aae1b5d3890656c5ad46c39', '77b10bd993e868df39f717b0d64bd9ad065f', NULL, 400.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-19 11:25:06', '2026-01-19 11:25:06', '000035514640', NULL, NULL, 0),
('8e36a2e21eed57d71019cf84ae10129b', '667293d7-c334-4923-9d8e-74d17ae20062', 'fc8b18485aae1b5d3890656c5ad46c39', '148176b84267e1fbfa9c5fd08e21285dde07', NULL, 400.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-20 00:40:30', '2026-01-20 00:40:30', '000002186382', NULL, NULL, 0),
('8fc07508fdb5dad1749e7104619cbe77', 'b1787d59-5ba2-40aa-96ff-96989cc3087b', 'fc8b18485aae1b5d3890656c5ad46c39', 'ede0496b4118a5e712cd933e3314ffaa892f', NULL, 400.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-19 11:24:31', '2026-01-19 11:24:31', '000038620775', NULL, NULL, 0),
('9071233123afb63b8e3e61e694d861ace539', 'a4f84251-403e-4a31-8ef8-4f23c6a88e5c', 'fc8b18485aae1b5d3890656c5ad46c39', '69f684d1-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 350.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-15 10:30:44', '2026-01-15 10:30:44', NULL, NULL, NULL, 1),
('9089ac4773469d1fab2aa22574bc444ea6fa', '30c97fbe-f78e-4d02-8acb-385964e2b28f', 'fc8b18485aae1b5d3890656c5ad46c39', '69f6a3f6-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 350.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-14 17:28:12', '2026-01-14 17:28:12', NULL, NULL, NULL, 1),
('92d15522a96156f6f010f0d672915dc4ec6d', '25513396-0a62-4f5a-9c1b-e826fc28c61d', 'fc8b18485aae1b5d3890656c5ad46c39', 'fead9ff82716fb6586dec237c8211a073b8d', NULL, 350.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-16 17:06:21', '2026-01-16 17:06:21', NULL, NULL, NULL, 1),
('93e34a1e8394e9aaa0c536bdf19c218d1b9e', '10455600-3ac7-4925-a145-75e0b016ea99', 'fc8b18485aae1b5d3890656c5ad46c39', '69f67f15-e752-11f0-ada2-b02628506e5c', 'c756c21b869ae63c37e015f8ac88cea2', 400.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-09 07:29:12', '2026-01-09 07:29:12', NULL, NULL, NULL, 1),
('9c88858e7179f922016a8f9b6c1d78c324e3', 'cbe0ee8a-ab68-4df5-9ac4-c6c20ce96274', 'fc8b18485aae1b5d3890656c5ad46c39', '69f6d5fb-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 350.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-10 21:53:14', '2026-01-10 21:53:14', NULL, NULL, NULL, 1),
('9d7644678ac34a20ab5c68ec781c2c2c', '9d37e3e0-1431-40ec-a4c8-558f6f74023e', 'fc8b18485aae1b5d3890656c5ad46c39', 'c86c82006d19f8cf1c5a7ba4dbf5e4653443', NULL, 400.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-16 05:48:37', '2026-01-16 05:48:37', '000711222876', NULL, NULL, 0),
('9e35f41ae080b0d70dff7bac2e764bfd3b7e', 'c077d4f8-e6d2-4020-aef3-f8edcfe62276', 'fc8b18485aae1b5d3890656c5ad46c39', '2102c860dd03066d60d6066e514506f34d5a', NULL, 350.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-20 18:33:32', '2026-01-20 18:33:32', NULL, NULL, NULL, 1),
('a117682325d85565caa58d5ce017066c', 'ce81c5aa-e4c6-4943-bd39-c77fbabbe197', 'fc8b18485aae1b5d3890656c5ad46c39', '2306806ac608bafdfc50672144b14c11ee4f', NULL, 400.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-19 11:24:58', '2026-01-19 11:24:58', '000001933031', NULL, NULL, 0),
('a67f144bc3b2f27e1e8b788fa9004e86e303', '17085e8b-9a33-4937-a6ba-cf90fadcafa2', 'fc8b18485aae1b5d3890656c5ad46c39', '69f67ba4-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 350.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-15 10:37:58', '2026-01-15 10:37:58', NULL, NULL, NULL, 1),
('a717802d299f9054f13b8f6f0cede3ab', '874da850-9056-42ea-b62a-c24fe537b47e', 'fc8b18485aae1b5d3890656c5ad46c39', '5013b784781c70d78dc17e80ef099d33f34e', NULL, 400.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-19 11:25:10', '2026-01-19 11:25:10', '000003664407', NULL, NULL, 0),
('aca8a6f2e5ccd5a3586dd92ee9ba23d4', '4c21ffaa-f8e4-4784-9295-e6ae1a930318', 'fc8b18485aae1b5d3890656c5ad46c39', '18f9e21b335888b6e9db97243ac05466a29c', NULL, 400.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-18 12:55:51', '2026-01-18 12:55:51', '000000045537', NULL, NULL, 0),
('b0e5ca9bf68963188284ca01744890a620bf', '9aab02c4-30ed-4313-b0d5-f5d4100dfab0', 'fc8b18485aae1b5d3890656c5ad46c39', '857ed927a50e36a8f77043d921965c28b3c9', NULL, 350.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-20 18:33:32', '2026-01-20 18:33:32', NULL, NULL, NULL, 1),
('ba65e652e46386e25da524427bb93c06', '3530209e-2556-4c3d-9dce-e8f5c16fb7e9', 'fc8b18485aae1b5d3890656c5ad46c39', '100408a04c8725be625ef9ea5d9cd7668ab6', NULL, 400.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-21 01:03:09', '2026-01-21 01:03:09', '000070559439', NULL, NULL, 0),
('bc6591ccca2a1f970df961894add51d2', 'fead74d9-41a1-4a7a-af67-77f06af0d80e', 'fc8b18485aae1b5d3890656c5ad46c39', '7d6b1cb415df4945be13ebb8ea584412044c', NULL, 400.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-19 11:24:43', '2026-01-19 11:24:43', '000000094986', NULL, NULL, 0),
('c35ae3ad7cab2ad27dfeda12d7e9aa08', 'b112429f-6d9e-43df-85b7-049474e338e9', 'fc8b18485aae1b5d3890656c5ad46c39', '98a34d2143378e70aa3a7cebe7a2da4c5453', NULL, 400.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-18 12:55:58', '2026-01-18 12:55:58', '000000622097', NULL, NULL, 0),
('c4be984488b63eda99a107f0f29134ff', 'a7f5c515-6e8a-4699-b4cf-575955b2639d', 'fc8b18485aae1b5d3890656c5ad46c39', 'd04009ac77230a920d84006a5e801065e1a9', NULL, 400.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-18 00:20:36', '2026-01-18 00:20:36', '000006643249', NULL, NULL, 0),
('ce7b9c72c3618ebccc5ad980adf4cdc4', 'd9d9f1c7-3319-4494-9798-d51d024a98fb', 'fc8b18485aae1b5d3890656c5ad46c39', '0451523f085a24ddf332298b2957eb2de3f1', NULL, 400.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-19 11:24:47', '2026-01-19 11:24:47', '000004982425', NULL, NULL, 0),
('d1f761abf4d4aadaef2390f4570278e668b0', '7851c584-d3ca-4d82-add8-c262b2abd227', 'fc8b18485aae1b5d3890656c5ad46c39', '69f6d558-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 350.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-10 21:53:40', '2026-01-10 21:53:40', NULL, NULL, NULL, 1),
('d76320e58d917b490b345f27fb1df3fa', 'dc8a2e3a-df77-465b-bb90-c4a38fd52952', 'fc8b18485aae1b5d3890656c5ad46c39', 'ec95b65b2bf2d3d05431f3bd180c08681636', NULL, 400.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-19 08:42:43', '2026-01-19 08:42:43', '000886115935', NULL, NULL, 0),
('f3ca3a98426c644af1c5256e9536fcca359e', '98ca48cf-17e1-4c65-8631-7a06bee07e6b', 'fc8b18485aae1b5d3890656c5ad46c39', '69f6c1d5-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 350.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-09 07:29:40', '2026-01-09 07:29:40', NULL, NULL, NULL, 1),
('fcbca817c79d9b7c7c7a906e7e36c6b5e37c', 'e5a795e6-65f4-4f79-82ea-9bf749e81c91', 'fc8b18485aae1b5d3890656c5ad46c39', '69f67c62-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 350.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-15 13:43:09', '2026-01-15 13:43:09', NULL, NULL, NULL, 1);

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `events_checkin_groups`
--

CREATE TABLE `events_checkin_groups` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `event_id` char(36) NOT NULL,
  `group_name` varchar(255) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_status` varchar(20) DEFAULT 'pending',
  `payment_method` varchar(50) DEFAULT NULL,
  `paid_by_customer_id` char(36) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` char(36) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
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
  `memberverification` tinyint(1) DEFAULT 0,
  `share_code` varchar(6) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dataark for tabell `events_eventdata`
--

INSERT INTO `events_eventdata` (`id`, `company_id`, `output_product_id`, `title`, `description`, `start_date`, `start_time`, `end_date`, `end_time`, `venue_name`, `event_address`, `event_city`, `event_postal`, `max_attendees`, `registration_deadline`, `min_attendees`, `age_limit`, `is_member_exclusive`, `ticket_price`, `cost_price`, `event_image_url`, `is_active`, `created_by`, `created_at`, `updated_at`, `default_payment_method`, `payment_timing`, `registration_type`, `payment_methods_json`, `memberverification`, `share_code`) VALUES
('55473ffa9d1f35874de5acb7a811663f', '7efbddea-b507-11f0-936b-b02628506e5c', 'd03a0a29be280202890be14ca0bd1472', 'Fredagsdans', '💃🕺 Fredagsdans på Høgtun – 23.01.26 \n\nVelkomen til ein kjekk og sosial dansekveld på Høgtun!\n\n🎶 Trekkspel kameratane leverer flott dansemusikk\n🕢 Dørene åpner kl. 19 - gratis inngang\nSalg av enkel mat og snacks\n🥂🍾 -Ta med det du ønsker\n-Ingen påmelding\n\nTa med venner, godt humør og danselyst – så lagar vi ein triveleg fredagskveld saman!', '2026-01-23', '19:00:00', NULL, NULL, '', 'Vågsgata 61', 'Fosnavåg', '6090', NULL, NULL, NULL, NULL, 0, 0.00, 0.00, 'https://8upload.com//image/935ede7e9fc39248/Skjermbilde_2026-01-10_kl._15.14.49.png', 1, '7f072d6a-b507-11f0-936b-b02628506e5c', '2026-01-10 15:13:29', '2026-01-13 23:04:25', '', 'both', 'none', NULL, 0, 'b28764'),
('58da89f2617ba42b7d739fa2b91b34a5', '7efbddea-b507-11f0-936b-b02628506e5c', '9aa2b0c9f31f68cd3b8129d501b31763', 'Januarbingo', 'Bingo på Høgtun. Vi åpner dørene klokken 19:00, for en kveld med flotte premier og spennende bingo!', '2026-01-28', '19:00:00', '2026-01-28', '21:00:00', 'Høgtun', 'Vågsgata 61', 'Fosnavåg', '6092', NULL, NULL, NULL, NULL, 0, 0.00, 0.00, 'https://external-content.duckduckgo.com/iu/?u=https%3A%2F%2Ftse2.mm.bing.net%2Fth%2Fid%2FOIP.Y-X130sw_xnC2p8MrQvOcQHaFk%3Fpid%3DApi&f=1&ipt=1d6b29240be816390494bf8764c0d87d2267aa44ae995f2ccb364417f32b6862&ipo=images', 1, '7f0d55c4-b507-11f0-936b-b02628506e5c', '2026-01-05 19:41:43', '2026-01-13 23:04:25', '', 'both', 'none', NULL, 0, '81b247'),
('fc8b18485aae1b5d3890656c5ad46c39', '7efbddea-b507-11f0-936b-b02628506e5c', '12a8901486c06b666ba289d8206da1e1', 'Dansetreff med Steinar Engelbrektson ', 'Dansetreff på Kulturhuset ved badeland i Fosnavåg.\nDette blir en kjempefin dansekveld... :-)\n', '2026-02-07', '19:30:00', '2026-02-08', '01:00:00', '', 'Lisjebøvegen 10a', 'Fosnavåg', '6092', 170, NULL, NULL, NULL, 0, 400.00, 0.00, 'https://external-content.duckduckgo.com/iu/?u=https%3A%2F%2Ftse1.mm.bing.net%2Fth%2Fid%2FOIP.4r9eMreo4EJBPfwNJ1tRHAHaC2%3Fpid%3DApi&f=1&ipt=d5be2278bb03a1b34e6dff24b61284e98f435cdfb41cec1d3f1cee3c091e1835&ipo=images', 1, '7f0d55c4-b507-11f0-936b-b02628506e5c', '2026-01-01 22:02:09', '2026-01-21 01:11:38', 'cash', 'both', 'signup', '[\"cash\"]', 1, 'a7504b');

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

--
-- Dataark for tabell `event_membership_plans`
--

INSERT INTO `event_membership_plans` (`id`, `event_id`, `membership_plan_id`, `ticket_price`, `created_at`, `public_display`) VALUES
('02db2b09-c795-11f0-bba6-b02628506e5c', '7f71bfba77c3ab8df3cae50a19828367', 'c756c21b869ae63c37e015f8ac88cea2', 0.00, '2025-11-22 11:18:46', 0),
('02db54bd-c795-11f0-bba6-b02628506e5c', '7f71bfba77c3ab8df3cae50a19828367', 'fe60b4f364430a94a8ab5d60e2fb83ab', 400.00, '2025-11-22 11:18:46', 1),
('0d1cea08-de24-11f0-bb40-b02628506e5c', 'cf5e1f98f54ed2642c117ffd7e173999', 'c756c21b869ae63c37e015f8ac88cea2', 50.00, '2025-12-21 04:18:06', 0),
('0d1d105b-de24-11f0-bb40-b02628506e5c', 'cf5e1f98f54ed2642c117ffd7e173999', 'fe60b4f364430a94a8ab5d60e2fb83ab', 450.00, '2025-12-21 04:18:06', 1),
('10a2b45b-e754-11f0-ada2-b02628506e5c', '9daf97965b477cf963c8adb3818b353d', 'ec90a19c3209fc47d42524ef9b95ff44', 250.00, '2026-01-01 20:54:29', 1),
('10a2e4c0-e754-11f0-ada2-b02628506e5c', '9daf97965b477cf963c8adb3818b353d', 'c756c21b869ae63c37e015f8ac88cea2', 250.00, '2026-01-01 20:54:29', 0),
('15717e73-c58a-11f0-b807-b02628506e5c', '249af8383aef5ef76d9a90650ad1223d', 'c756c21b869ae63c37e015f8ac88cea2', 0.00, '2025-11-19 20:55:30', 0),
('1b1a329f-c857-11f0-a370-b02628506e5c', 'd5a5aa67cb78961bb05768192b706477', 'c756c21b869ae63c37e015f8ac88cea2', 250.00, '2025-11-23 10:28:09', 0),
('1b1a8acf-c857-11f0-a370-b02628506e5c', 'd5a5aa67cb78961bb05768192b706477', 'fe60b4f364430a94a8ab5d60e2fb83ab', 350.00, '2025-11-23 10:28:09', 1),
('1df37081-cb15-11f0-b7b7-b02628506e5c', 'c411364df1d17f36addf50cde5a1595a', 'c756c21b869ae63c37e015f8ac88cea2', 200.00, '2025-11-26 22:13:20', 0),
('1df3902a-cb15-11f0-b7b7-b02628506e5c', 'c411364df1d17f36addf50cde5a1595a', 'fe60b4f364430a94a8ab5d60e2fb83ab', 450.00, '2025-11-26 22:13:20', 1),
('22d78b58-e755-11f0-ada2-b02628506e5c', 'fc8b18485aae1b5d3890656c5ad46c39', 'ec90a19c3209fc47d42524ef9b95ff44', 350.00, '2026-01-01 21:02:09', 1),
('2e433ff3-ea66-11f0-9895-b02628506e5c', '58da89f2617ba42b7d739fa2b91b34a5', 'ec90a19c3209fc47d42524ef9b95ff44', 0.00, '2026-01-05 18:41:43', 0),
('2e436ef6-ea66-11f0-9895-b02628506e5c', '58da89f2617ba42b7d739fa2b91b34a5', 'c756c21b869ae63c37e015f8ac88cea2', 0.00, '2026-01-05 18:41:43', 0),
('30edcd74-e2a5-11f0-b773-b02628506e5c', 'c3b349cce7ee1222a33d6a39972f6f51', 'c756c21b869ae63c37e015f8ac88cea2', 1.00, '2025-12-26 21:52:36', 0),
('30ee0c5a-e2a5-11f0-b773-b02628506e5c', 'c3b349cce7ee1222a33d6a39972f6f51', 'fe60b4f364430a94a8ab5d60e2fb83ab', 2.00, '2025-12-26 21:52:36', 0),
('472a3f15-c734-11f0-a7b4-b02628506e5c', '8cb7e96db38209a1ecf708f865686c05', 'c756c21b869ae63c37e015f8ac88cea2', 0.00, '2025-11-21 23:46:19', 0),
('472aabc6-c734-11f0-a7b4-b02628506e5c', '8cb7e96db38209a1ecf708f865686c05', 'fe60b4f364430a94a8ab5d60e2fb83ab', 400.00, '2025-11-21 23:46:19', 1),
('4a355d06-c96c-11f0-aadf-b02628506e5c', 'aaf3a2ceca8b26c2250072fb8530971f', 'c756c21b869ae63c37e015f8ac88cea2', 100.00, '2025-11-24 19:32:18', 1),
('4a358971-c96c-11f0-aadf-b02628506e5c', 'aaf3a2ceca8b26c2250072fb8530971f', 'fe60b4f364430a94a8ab5d60e2fb83ab', 450.00, '2025-11-24 19:32:18', 1),
('8b0b6ebb-e37e-11f0-a9ff-b02628506e5c', '3ce434d90e9f025a6901c67148c9368f', 'c756c21b869ae63c37e015f8ac88cea2', 2.00, '2025-12-27 23:48:28', 1),
('8b0b906d-e37e-11f0-a9ff-b02628506e5c', '3ce434d90e9f025a6901c67148c9368f', 'fe60b4f364430a94a8ab5d60e2fb83ab', 1.00, '2025-12-27 23:48:28', 1),
('8c8eba03-c857-11f0-a370-b02628506e5c', 'e56cf02efeb1f7d76489b5844afea130', 'c756c21b869ae63c37e015f8ac88cea2', 0.00, '2025-11-23 10:31:19', 0),
('8c8ecfdb-c857-11f0-a370-b02628506e5c', 'e56cf02efeb1f7d76489b5844afea130', 'fe60b4f364430a94a8ab5d60e2fb83ab', 150.00, '2025-11-23 10:31:19', 1),
('8d462991-d9e2-11f0-aec6-b02628506e5c', '5dd50982890153b39e28047fc1515056', 'c756c21b869ae63c37e015f8ac88cea2', 3.00, '2025-12-15 18:19:10', 1),
('8d464190-d9e2-11f0-aec6-b02628506e5c', '5dd50982890153b39e28047fc1515056', 'fe60b4f364430a94a8ab5d60e2fb83ab', 2.00, '2025-12-15 18:19:10', 1),
('9c3a4722-e379-11f0-a9ff-b02628506e5c', '798bbd266e4487f394ab5f795e2574ad', 'c756c21b869ae63c37e015f8ac88cea2', 2.00, '2025-12-27 23:13:10', 0),
('9c3a7338-e379-11f0-a9ff-b02628506e5c', '798bbd266e4487f394ab5f795e2574ad', 'fe60b4f364430a94a8ab5d60e2fb83ab', 1.00, '2025-12-27 23:13:10', 0),
('a241e0d5-c844-11f0-a370-b02628506e5c', 'eedb45a0c403a32dbb97573c05bcd61b', 'c756c21b869ae63c37e015f8ac88cea2', 150.00, '2025-11-23 08:15:55', 0),
('a2422333-c844-11f0-a370-b02628506e5c', 'eedb45a0c403a32dbb97573c05bcd61b', 'fe60b4f364430a94a8ab5d60e2fb83ab', 400.00, '2025-11-23 08:15:55', 1),
('a25c3277-cb76-11f0-b21f-b02628506e5c', '4aed37c0693899eaf979c73ba41fb5eb', 'c756c21b869ae63c37e015f8ac88cea2', 0.00, '2025-11-27 09:51:23', 0),
('a25c5f28-cb76-11f0-b21f-b02628506e5c', '4aed37c0693899eaf979c73ba41fb5eb', 'fe60b4f364430a94a8ab5d60e2fb83ab', 250.00, '2025-11-27 09:51:23', 1),
('a7198c30-d9f7-11f0-aec6-b02628506e5c', '6caf417437111fb1e704d7051d5e1885', 'c756c21b869ae63c37e015f8ac88cea2', 0.00, '2025-12-15 20:50:13', 0),
('a719a497-d9f7-11f0-aec6-b02628506e5c', '6caf417437111fb1e704d7051d5e1885', 'fe60b4f364430a94a8ab5d60e2fb83ab', 2.00, '2025-12-15 20:50:13', 1),
('ac8ded69-c546-11f0-b807-b02628506e5c', 'ecc015c54165003b1752ef77581ca832', 'c756c21b869ae63c37e015f8ac88cea2', 200.00, '2025-11-19 12:52:58', 0),
('acd63b1c-c546-11f0-b807-b02628506e5c', 'ecc015c54165003b1752ef77581ca832', 'fe60b4f364430a94a8ab5d60e2fb83ab', 400.00, '2025-11-19 12:52:58', 1),
('bdb394f5-d2a7-11f0-8ff2-b02628506e5c', '71aa4c7ac7a37c03a2a06b490d0e50f8', 'c756c21b869ae63c37e015f8ac88cea2', 100.00, '2025-12-06 13:30:33', 0),
('bdb3ba01-d2a7-11f0-8ff2-b02628506e5c', '71aa4c7ac7a37c03a2a06b490d0e50f8', 'fe60b4f364430a94a8ab5d60e2fb83ab', 200.00, '2025-12-06 13:30:33', 1),
('d18b5f6d-d9f6-11f0-aec6-b02628506e5c', '88389c6dd955f957d59bfb8942a87420', 'c756c21b869ae63c37e015f8ac88cea2', 0.00, '2025-12-15 20:44:14', 1),
('d18b80a8-d9f6-11f0-aec6-b02628506e5c', '88389c6dd955f957d59bfb8942a87420', 'fe60b4f364430a94a8ab5d60e2fb83ab', 2.00, '2025-12-15 20:44:14', 1),
('d765dccf-cad7-11f0-b7b7-b02628506e5c', 'b834eaa17c1434de89cb9455a44c89bb', 'c756c21b869ae63c37e015f8ac88cea2', 0.00, '2025-11-26 14:54:42', 0),
('d7660f78-cad7-11f0-b7b7-b02628506e5c', 'b834eaa17c1434de89cb9455a44c89bb', 'fe60b4f364430a94a8ab5d60e2fb83ab', 400.00, '2025-11-26 14:54:42', 1),
('f60fb666-c85a-11f0-a370-b02628506e5c', '1f3c2bdb3b07990d295113ec4a3c2b9f', 'c756c21b869ae63c37e015f8ac88cea2', 50.00, '2025-11-23 10:55:44', 1),
('f60fd147-c85a-11f0-a370-b02628506e5c', '1f3c2bdb3b07990d295113ec4a3c2b9f', 'fe60b4f364430a94a8ab5d60e2fb83ab', 400.00, '2025-11-23 10:55:44', 1),
('fe1ba049-cbe7-11f0-b21f-b02628506e5c', 'b7e6071f358d41ea21b90e5e39b751d4', 'c756c21b869ae63c37e015f8ac88cea2', 200.00, '2025-11-27 23:22:50', 0),
('fe1bc289-cbe7-11f0-b21f-b02628506e5c', 'b7e6071f358d41ea21b90e5e39b751d4', 'fe60b4f364430a94a8ab5d60e2fb83ab', 250.00, '2025-11-27 23:22:50', 1),
('fe86f256-c5f2-11f0-a3f5-b02628506e5c', '7d7c7e6fbc6f79496e3fa273b89aec20', 'c756c21b869ae63c37e015f8ac88cea2', 300.00, '2025-11-20 09:26:29', 0),
('fe872475-c5f2-11f0-a3f5-b02628506e5c', '7d7c7e6fbc6f79496e3fa273b89aec20', 'fe60b4f364430a94a8ab5d60e2fb83ab', 400.00, '2025-11-20 09:26:29', 1);

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

--
-- Dataark for tabell `event_registrations`
--

INSERT INTO `event_registrations` (`id`, `event_id`, `company_id`, `customer_id`, `approval_status`, `approval_reason`, `rejection_reason`, `approved_by`, `approved_at`, `rejected_by`, `rejected_at`, `created_at`, `updated_at`, `number_of_attendees`) VALUES
('1018f7b3-a52f-4421-a671-9573d7965638', 'fc8b18485aae1b5d3890656c5ad46c39', '7efbddea-b507-11f0-936b-b02628506e5c', 'c671dc6594f96ab7ebf201d00cb6b6e3de1d', 'approved', 'Manuell godkjenning', NULL, '7f0d55c4-b507-11f0-936b-b02628506e5c', '2026-01-18 19:23:58', NULL, NULL, '2026-01-18 16:23:14', '2026-01-18 19:23:58', 1),
('10455600-3ac7-4925-a145-75e0b016ea99', 'fc8b18485aae1b5d3890656c5ad46c39', '7efbddea-b507-11f0-936b-b02628506e5c', '69f67f15-e752-11f0-ada2-b02628506e5c', 'approved', 'Auto-godkjent medlem', NULL, NULL, '2026-01-09 07:29:12', NULL, NULL, '2026-01-09 07:29:12', '2026-01-09 07:29:12', 1),
('16ca518c-5ebf-427d-bc15-87f1e9fc6aa7', 'fc8b18485aae1b5d3890656c5ad46c39', '7efbddea-b507-11f0-936b-b02628506e5c', '1e0d309b046171d91bffb32bf1a570856777', 'approved', 'Manuell godkjenning', NULL, '7f072d6a-b507-11f0-936b-b02628506e5c', '2026-01-19 11:24:55', NULL, NULL, '2026-01-19 10:21:45', '2026-01-19 11:24:55', 1),
('17085e8b-9a33-4937-a6ba-cf90fadcafa2', 'fc8b18485aae1b5d3890656c5ad46c39', '7efbddea-b507-11f0-936b-b02628506e5c', '69f67ba4-e752-11f0-ada2-b02628506e5c', 'approved', 'Auto-godkjent medlem', NULL, NULL, '2026-01-15 10:37:58', NULL, NULL, '2026-01-15 10:37:58', '2026-01-15 10:37:58', 1),
('213c8cf7-6499-4578-b2bb-714f79c9406c', 'fc8b18485aae1b5d3890656c5ad46c39', '7efbddea-b507-11f0-936b-b02628506e5c', '77b10bd993e868df39f717b0d64bd9ad065f', 'approved', 'Manuell godkjenning', NULL, '7f072d6a-b507-11f0-936b-b02628506e5c', '2026-01-19 11:25:06', NULL, NULL, '2026-01-19 10:18:49', '2026-01-19 11:25:06', 1),
('219962e1-b43e-483b-9fb5-def99114ce91', 'fc8b18485aae1b5d3890656c5ad46c39', '7efbddea-b507-11f0-936b-b02628506e5c', 'a467996f28c150ab799bb9e5d247b42c46a3', 'approved', 'Manuell godkjenning', NULL, '7f072d6a-b507-11f0-936b-b02628506e5c', '2026-01-19 11:25:15', NULL, NULL, '2026-01-19 10:16:53', '2026-01-19 11:25:15', 1),
('23b5ad1d-c7b9-4dd4-8ba8-46ccf879f814', 'fc8b18485aae1b5d3890656c5ad46c39', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6a493-e752-11f0-ada2-b02628506e5c', 'approved', 'Auto-godkjent medlem', NULL, NULL, '2026-01-15 10:26:25', NULL, NULL, '2026-01-15 10:26:25', '2026-01-15 10:26:25', 1),
('25513396-0a62-4f5a-9c1b-e826fc28c61d', 'fc8b18485aae1b5d3890656c5ad46c39', '7efbddea-b507-11f0-936b-b02628506e5c', 'fead9ff82716fb6586dec237c8211a073b8d', 'approved', 'Auto-godkjent medlem', NULL, NULL, '2026-01-16 17:06:21', NULL, NULL, '2026-01-16 17:06:21', '2026-01-16 17:06:21', 1),
('26b736d6-9841-48a3-af14-6b493d7ca7f6', 'fc8b18485aae1b5d3890656c5ad46c39', '7efbddea-b507-11f0-936b-b02628506e5c', 'd9ea2f0ab8930466f89a72ab2891ddfc15aa', 'approved', 'ok', NULL, NULL, '2026-01-20 09:15:34', NULL, NULL, '2026-01-20 09:14:51', '2026-01-20 20:31:23', 1),
('30c97fbe-f78e-4d02-8acb-385964e2b28f', 'fc8b18485aae1b5d3890656c5ad46c39', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6a3f6-e752-11f0-ada2-b02628506e5c', 'approved', 'Auto-godkjent medlem', NULL, '22d78b58-e755-11f0-ada2-b02628506e5c', '2026-01-14 17:28:12', NULL, NULL, '2026-01-14 17:28:12', '2026-01-20 20:31:12', 1),
('33f55092-88b0-45f2-bf23-192726514b8b', 'fc8b18485aae1b5d3890656c5ad46c39', '7efbddea-b507-11f0-936b-b02628506e5c', 'f5fa7c635ec8f00b60f79e6b8323489109b1', 'approved', 'Manuell godkjenning', NULL, '7f0d55c4-b507-11f0-936b-b02628506e5c', '2026-01-20 00:40:24', NULL, NULL, '2026-01-19 19:01:52', '2026-01-20 00:40:24', 1),
('3530209e-2556-4c3d-9dce-e8f5c16fb7e9', 'fc8b18485aae1b5d3890656c5ad46c39', '7efbddea-b507-11f0-936b-b02628506e5c', '100408a04c8725be625ef9ea5d9cd7668ab6', 'approved', 'ok', NULL, 'f996adb8d578c9f15859cc9ffb78b023792b', '2026-01-21 01:03:09', NULL, NULL, '2026-01-21 01:02:46', '2026-01-21 01:03:09', 1),
('38b609b9-4cd9-4c56-ba6a-4632e71c412c', 'fc8b18485aae1b5d3890656c5ad46c39', '7efbddea-b507-11f0-936b-b02628506e5c', 'da1e7e8cfeed4c04a6097df30dc77eeed0c4', 'approved', 'Auto-godkjent medlem', NULL, NULL, '2026-01-20 09:54:53', NULL, NULL, '2026-01-20 09:54:53', '2026-01-20 09:54:53', 1),
('3d53b0d6-1e60-4ebd-9c4d-66097fdd4157', 'fc8b18485aae1b5d3890656c5ad46c39', '7efbddea-b507-11f0-936b-b02628506e5c', '126ad5cce3731f36e5e4fcc35fe7071bf427', 'approved', 'Manuell godkjenning', NULL, '7f0d55c4-b507-11f0-936b-b02628506e5c', '2026-01-18 12:55:47', NULL, NULL, '2026-01-18 10:35:18', '2026-01-18 12:55:47', 1),
('44b74740-5981-4fb7-88ba-db347d80f8b4', 'fc8b18485aae1b5d3890656c5ad46c39', '7efbddea-b507-11f0-936b-b02628506e5c', 'ccc35a6aaebde1957a9ffdb10c87899fa0e0', 'approved', 'Manuell godkjenning', NULL, '7f0d55c4-b507-11f0-936b-b02628506e5c', '2026-01-16 05:48:34', NULL, NULL, '2026-01-16 05:47:36', '2026-01-16 05:48:34', 1),
('46efb8e9-5195-4e9a-9f86-d8eb096dc06e', 'fc8b18485aae1b5d3890656c5ad46c39', '7efbddea-b507-11f0-936b-b02628506e5c', '6464d76a21886c2e71638c9e85a9ff7889a5', 'approved', 'Manuell godkjenning', NULL, '7f072d6a-b507-11f0-936b-b02628506e5c', '2026-01-19 11:24:27', NULL, NULL, '2026-01-19 10:24:50', '2026-01-19 11:24:27', 1),
('4c21ffaa-f8e4-4784-9295-e6ae1a930318', 'fc8b18485aae1b5d3890656c5ad46c39', '7efbddea-b507-11f0-936b-b02628506e5c', '18f9e21b335888b6e9db97243ac05466a29c', 'approved', 'Manuell godkjenning', NULL, '7f0d55c4-b507-11f0-936b-b02628506e5c', '2026-01-18 12:55:51', NULL, NULL, '2026-01-18 10:35:18', '2026-01-18 12:55:51', 1),
('55ad7edf-79a9-43ab-b9d8-06169c38a317', 'fc8b18485aae1b5d3890656c5ad46c39', '7efbddea-b507-11f0-936b-b02628506e5c', 'bf9f4835cf86fc6ddf91f87955bb0c4c7551', 'approved', 'Manuell godkjenning', NULL, '7f0d55c4-b507-11f0-936b-b02628506e5c', '2026-01-18 19:24:03', NULL, NULL, '2026-01-18 16:22:39', '2026-01-18 19:24:03', 1),
('5b94c5bc-9a80-425b-9dfd-18bfe72a4b2a', 'fc8b18485aae1b5d3890656c5ad46c39', '7efbddea-b507-11f0-936b-b02628506e5c', '9d4522d0bb354d2e8dc8976571b46ac6cb0c', 'approved', 'Manuell godkjenning', NULL, '7f0d55c4-b507-11f0-936b-b02628506e5c', '2026-01-18 12:55:55', NULL, NULL, '2026-01-18 10:35:18', '2026-01-18 12:55:55', 1),
('667293d7-c334-4923-9d8e-74d17ae20062', 'fc8b18485aae1b5d3890656c5ad46c39', '7efbddea-b507-11f0-936b-b02628506e5c', '148176b84267e1fbfa9c5fd08e21285dde07', 'approved', 'Manuell godkjenning', NULL, '7f0d55c4-b507-11f0-936b-b02628506e5c', '2026-01-20 00:40:30', NULL, NULL, '2026-01-19 19:00:58', '2026-01-20 00:40:30', 1),
('7851c584-d3ca-4d82-add8-c262b2abd227', 'fc8b18485aae1b5d3890656c5ad46c39', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6d558-e752-11f0-ada2-b02628506e5c', 'approved', 'Auto-godkjent medlem', NULL, NULL, '2026-01-10 21:53:40', NULL, NULL, '2026-01-10 21:53:40', '2026-01-10 21:53:40', 1),
('7c4f7c6f-0a1b-4218-a645-886b7f67f07e', 'fc8b18485aae1b5d3890656c5ad46c39', '7efbddea-b507-11f0-936b-b02628506e5c', '69f68bbe-e752-11f0-ada2-b02628506e5c', 'approved', 'Auto-godkjent medlem', NULL, NULL, '2026-01-13 23:22:23', NULL, NULL, '2026-01-13 23:22:23', '2026-01-13 23:22:23', 1),
('82fe5a54-87dc-4a64-8af9-3006b818b68c', 'fc8b18485aae1b5d3890656c5ad46c39', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6bc66-e752-11f0-ada2-b02628506e5c', 'approved', 'Auto-godkjent medlem', NULL, NULL, '2026-01-14 11:01:22', NULL, NULL, '2026-01-14 11:01:22', '2026-01-14 11:01:22', 1),
('874da850-9056-42ea-b62a-c24fe537b47e', 'fc8b18485aae1b5d3890656c5ad46c39', '7efbddea-b507-11f0-936b-b02628506e5c', '5013b784781c70d78dc17e80ef099d33f34e', 'approved', 'Manuell godkjenning', NULL, '7f072d6a-b507-11f0-936b-b02628506e5c', '2026-01-19 11:25:10', NULL, NULL, '2026-01-19 10:17:54', '2026-01-19 11:25:10', 1),
('8b22136f-d044-4c6a-9859-de3205e0e8c8', 'fc8b18485aae1b5d3890656c5ad46c39', '7efbddea-b507-11f0-936b-b02628506e5c', '69f699af-e752-11f0-ada2-b02628506e5c', 'approved', 'Auto-godkjent medlem', NULL, NULL, '2026-01-14 11:01:22', NULL, NULL, '2026-01-14 11:01:22', '2026-01-14 11:01:22', 1),
('9344192b-fb4c-40a8-bec3-db3a18ec31d3', 'fc8b18485aae1b5d3890656c5ad46c39', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6c081-e752-11f0-ada2-b02628506e5c', 'approved', 'Auto-godkjent medlem', NULL, NULL, '2026-01-16 17:09:00', NULL, NULL, '2026-01-16 17:09:00', '2026-01-16 17:09:00', 1),
('971f458f-8d80-495e-95d8-7e68ef780a9b', 'fc8b18485aae1b5d3890656c5ad46c39', '7efbddea-b507-11f0-936b-b02628506e5c', 'f5910bfa5f03f754a6cb2bd11b856cf744dc', 'approved', 'Auto-godkjent medlem', NULL, NULL, '2026-01-21 01:11:57', NULL, NULL, '2026-01-21 01:11:57', '2026-01-21 01:11:57', 1),
('98ca48cf-17e1-4c65-8631-7a06bee07e6b', 'fc8b18485aae1b5d3890656c5ad46c39', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6c1d5-e752-11f0-ada2-b02628506e5c', 'approved', 'Auto-godkjent medlem', NULL, NULL, '2026-01-09 07:29:40', NULL, NULL, '2026-01-09 07:29:40', '2026-01-09 07:29:40', 1),
('9aab02c4-30ed-4313-b0d5-f5d4100dfab0', 'fc8b18485aae1b5d3890656c5ad46c39', '7efbddea-b507-11f0-936b-b02628506e5c', '857ed927a50e36a8f77043d921965c28b3c9', 'approved', 'Auto-godkjent medlem', NULL, NULL, '2026-01-20 18:33:32', NULL, NULL, '2026-01-20 18:33:32', '2026-01-20 18:33:32', 1),
('9d37e3e0-1431-40ec-a4c8-558f6f74023e', 'fc8b18485aae1b5d3890656c5ad46c39', '7efbddea-b507-11f0-936b-b02628506e5c', 'c86c82006d19f8cf1c5a7ba4dbf5e4653443', 'approved', 'Manuell godkjenning', NULL, '7f0d55c4-b507-11f0-936b-b02628506e5c', '2026-01-16 05:48:37', NULL, NULL, '2026-01-16 05:47:36', '2026-01-16 05:48:37', 1),
('a4f84251-403e-4a31-8ef8-4f23c6a88e5c', 'fc8b18485aae1b5d3890656c5ad46c39', '7efbddea-b507-11f0-936b-b02628506e5c', '69f684d1-e752-11f0-ada2-b02628506e5c', 'approved', 'Auto-godkjent medlem', NULL, NULL, '2026-01-15 10:30:44', NULL, NULL, '2026-01-15 10:30:44', '2026-01-15 10:30:44', 1),
('a7f5c515-6e8a-4699-b4cf-575955b2639d', 'fc8b18485aae1b5d3890656c5ad46c39', '7efbddea-b507-11f0-936b-b02628506e5c', 'd04009ac77230a920d84006a5e801065e1a9', 'approved', 'Manuell godkjenning', NULL, '7f0d55c4-b507-11f0-936b-b02628506e5c', '2026-01-18 00:20:36', NULL, NULL, '2026-01-18 00:05:24', '2026-01-18 00:20:36', 1),
('b112429f-6d9e-43df-85b7-049474e338e9', 'fc8b18485aae1b5d3890656c5ad46c39', '7efbddea-b507-11f0-936b-b02628506e5c', '98a34d2143378e70aa3a7cebe7a2da4c5453', 'approved', 'Manuell godkjenning', NULL, '7f0d55c4-b507-11f0-936b-b02628506e5c', '2026-01-18 12:55:58', NULL, NULL, '2026-01-18 10:35:18', '2026-01-18 12:55:58', 1),
('b164a78f-106d-41be-90b9-36cb489bfe54', 'fc8b18485aae1b5d3890656c5ad46c39', '7efbddea-b507-11f0-936b-b02628506e5c', 'b5578a551dfa2b0a2a2325802764ff5641cd', 'approved', 'Manuell godkjenning', NULL, '7f0d55c4-b507-11f0-936b-b02628506e5c', '2026-01-20 19:57:09', NULL, NULL, '2026-01-20 19:56:27', '2026-01-20 19:57:09', 1),
('b1787d59-5ba2-40aa-96ff-96989cc3087b', 'fc8b18485aae1b5d3890656c5ad46c39', '7efbddea-b507-11f0-936b-b02628506e5c', 'ede0496b4118a5e712cd933e3314ffaa892f', 'approved', 'Manuell godkjenning', NULL, '7f072d6a-b507-11f0-936b-b02628506e5c', '2026-01-19 11:24:31', NULL, NULL, '2026-01-19 10:24:01', '2026-01-19 11:24:31', 1),
('c077d4f8-e6d2-4020-aef3-f8edcfe62276', 'fc8b18485aae1b5d3890656c5ad46c39', '7efbddea-b507-11f0-936b-b02628506e5c', '2102c860dd03066d60d6066e514506f34d5a', 'approved', 'Auto-godkjent medlem', NULL, NULL, '2026-01-20 18:33:32', NULL, NULL, '2026-01-20 18:33:32', '2026-01-20 18:33:32', 1),
('c5611fe6-fdac-469a-8426-9a75ac4a946b', 'fc8b18485aae1b5d3890656c5ad46c39', '7efbddea-b507-11f0-936b-b02628506e5c', 'aa9d757529e17a7420904344cfd7f705bedf', 'approved', 'Manuell godkjenning', NULL, '7f0d55c4-b507-11f0-936b-b02628506e5c', '2026-01-18 12:56:02', NULL, NULL, '2026-01-18 10:35:18', '2026-01-18 12:56:02', 1),
('c9600f91-9edd-4547-9238-c7d7792042ea', 'fc8b18485aae1b5d3890656c5ad46c39', '7efbddea-b507-11f0-936b-b02628506e5c', '15944770a5255ab2256b1540c674690f7ab0', 'approved', 'Manuell godkjenning', NULL, '7f0d55c4-b507-11f0-936b-b02628506e5c', '2026-01-18 12:56:09', NULL, NULL, '2026-01-18 10:35:18', '2026-01-18 12:56:09', 1),
('cbe0ee8a-ab68-4df5-9ac4-c6c20ce96274', 'fc8b18485aae1b5d3890656c5ad46c39', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6d5fb-e752-11f0-ada2-b02628506e5c', 'approved', 'Auto-godkjent medlem', NULL, NULL, '2026-01-10 21:53:14', NULL, NULL, '2026-01-10 21:53:14', '2026-01-10 21:53:14', 1),
('ce81c5aa-e4c6-4943-bd39-c77fbabbe197', 'fc8b18485aae1b5d3890656c5ad46c39', '7efbddea-b507-11f0-936b-b02628506e5c', '2306806ac608bafdfc50672144b14c11ee4f', 'approved', 'Manuell godkjenning', NULL, '7f072d6a-b507-11f0-936b-b02628506e5c', '2026-01-19 11:24:58', NULL, NULL, '2026-01-19 10:20:56', '2026-01-19 11:24:58', 1),
('d29c3f0e-e33a-4146-8c03-97857302b0da', 'fc8b18485aae1b5d3890656c5ad46c39', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6c282-e752-11f0-ada2-b02628506e5c', 'approved', 'Auto-godkjent medlem', NULL, NULL, '2026-01-15 17:34:52', NULL, NULL, '2026-01-15 17:34:52', '2026-01-15 17:34:52', 1),
('d6877ac9-b9c4-4414-91a2-1a215ce1e627', 'fc8b18485aae1b5d3890656c5ad46c39', '7efbddea-b507-11f0-936b-b02628506e5c', '1d3d03015dfb1b6e7e23eeac23ae28fde5f3', 'approved', 'Manuell godkjenning', NULL, '7f072d6a-b507-11f0-936b-b02628506e5c', '2026-01-19 11:25:02', NULL, NULL, '2026-01-19 10:19:46', '2026-01-19 11:25:02', 1),
('d9d9f1c7-3319-4494-9798-d51d024a98fb', 'fc8b18485aae1b5d3890656c5ad46c39', '7efbddea-b507-11f0-936b-b02628506e5c', '0451523f085a24ddf332298b2957eb2de3f1', 'approved', 'Manuell godkjenning', NULL, '7f072d6a-b507-11f0-936b-b02628506e5c', '2026-01-19 11:24:47', NULL, NULL, '2026-01-19 10:22:35', '2026-01-19 11:24:47', 1),
('dc8a2e3a-df77-465b-bb90-c4a38fd52952', 'fc8b18485aae1b5d3890656c5ad46c39', '7efbddea-b507-11f0-936b-b02628506e5c', 'ec95b65b2bf2d3d05431f3bd180c08681636', 'approved', 'Manuell godkjenning', NULL, '7f0d55c4-b507-11f0-936b-b02628506e5c', '2026-01-19 08:42:43', NULL, NULL, '2026-01-18 20:09:51', '2026-01-19 08:42:43', 1),
('e2b5ec1f-a737-4196-9fca-d94f9017e70c', 'fc8b18485aae1b5d3890656c5ad46c39', '7efbddea-b507-11f0-936b-b02628506e5c', '69f68433-e752-11f0-ada2-b02628506e5c', 'approved', 'Auto-godkjent medlem', NULL, NULL, '2026-01-12 14:51:34', NULL, NULL, '2026-01-12 14:51:34', '2026-01-12 14:51:34', 1),
('e5a795e6-65f4-4f79-82ea-9bf749e81c91', 'fc8b18485aae1b5d3890656c5ad46c39', '7efbddea-b507-11f0-936b-b02628506e5c', '69f67c62-e752-11f0-ada2-b02628506e5c', 'approved', 'Auto-godkjent medlem', NULL, NULL, '2026-01-15 13:43:09', NULL, NULL, '2026-01-15 13:43:09', '2026-01-15 13:43:09', 1),
('e76a2423-5ebc-4035-885e-5b5b5188ce9c', 'fc8b18485aae1b5d3890656c5ad46c39', '7efbddea-b507-11f0-936b-b02628506e5c', '25cc3f4b5c68868c67f159018d1c8a74f86c', 'approved', 'Auto-godkjent medlem', NULL, NULL, '2026-01-20 19:55:49', NULL, NULL, '2026-01-20 19:55:49', '2026-01-20 19:55:49', 1),
('fe54301a-121f-4579-98d4-47058d64aa3c', 'fc8b18485aae1b5d3890656c5ad46c39', '7efbddea-b507-11f0-936b-b02628506e5c', '69f67dab-e752-11f0-ada2-b02628506e5c', 'approved', 'Auto-godkjent medlem', NULL, NULL, '2026-01-15 10:00:06', NULL, NULL, '2026-01-15 10:00:06', '2026-01-15 10:00:06', 1),
('fead74d9-41a1-4a7a-af67-77f06af0d80e', 'fc8b18485aae1b5d3890656c5ad46c39', '7efbddea-b507-11f0-936b-b02628506e5c', '7d6b1cb415df4945be13ebb8ea584412044c', 'approved', 'Manuell godkjenning', NULL, '7f072d6a-b507-11f0-936b-b02628506e5c', '2026-01-19 11:24:43', NULL, NULL, '2026-01-19 10:23:16', '2026-01-19 11:24:43', 1);

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

--
-- Dataark for tabell `event_registration_fields`
--

INSERT INTO `event_registration_fields` (`id`, `event_id`, `field_name`, `field_label`, `field_type`, `is_required`, `is_visible`, `display_order`, `created_at`, `member_compatability`) VALUES
('030b4ee5f9392184dde834ecb71e5ddd', 'fc8b18485aae1b5d3890656c5ad46c39', 'last_name', 'Etternavn', 'text', 1, 1, 2, '2026-01-01 22:02:09', 1),
('14ca1d1f5095847508824bfeff8ba107', 'fc8b18485aae1b5d3890656c5ad46c39', 'first_name', 'Fornavn', 'text', 1, 1, 1, '2026-01-01 22:02:09', 1),
('9bf23a3171962fb76f67fe4076481529', 'fc8b18485aae1b5d3890656c5ad46c39', 'phone', 'Telefonnummer', 'tel', 0, 1, 3, '2026-01-20 22:29:21', 1),
('b820feba273813f7479fc4a864e96e83', '55473ffa9d1f35874de5acb7a811663f', 'phone', 'Telefonnummer', 'tel', 0, 1, 4, '2026-01-13 21:42:34', 1),
('c52acf6d9a617595087717f647986ccd', '58da89f2617ba42b7d739fa2b91b34a5', 'last_name', 'Etternavn', 'text', 1, 1, 2, '2026-01-05 19:41:43', 1),
('ca9bf0eed4971dae71160ed68eaaa4b4', '58da89f2617ba42b7d739fa2b91b34a5', 'first_name', 'Fornavn', 'text', 1, 1, 1, '2026-01-05 19:41:43', 1),
('d2bd897111e2a8311630313397794338', '58da89f2617ba42b7d739fa2b91b34a5', 'email', 'E-postadresse', 'email', 0, 1, 3, '2026-01-05 19:58:12', 1),
('f9645f00ed25d80cb57699986653e663', '55473ffa9d1f35874de5acb7a811663f', 'email', 'E-postadresse', 'email', 0, 1, 3, '2026-01-13 21:42:34', 1);

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
  `created_at` datetime DEFAULT current_timestamp(),
  `paymentMethod` varchar(255) DEFAULT NULL,
  `payment_status` varchar(20) DEFAULT 'unpaid'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dataark for tabell `membership_members`
--

INSERT INTO `membership_members` (`id`, `company_id`, `customer_id`, `membership_plan_id`, `member_code`, `status`, `join_date`, `expiration_date`, `auto_renew`, `created_at`, `paymentMethod`, `payment_status`) VALUES
('31c6ddcd-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f676b6-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-3955A3-2601-0001', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c6e147-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f67a98-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-47B3DA-2601-0002', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c6e2ac-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f67ba4-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-648FDA-2601-0003', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c6e40d-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f67c62-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-C9AFDD-2601-0004', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c6e57a-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f67dab-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-D4DF8C-2601-0005', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c6e703-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f67e68-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-C02F44-2601-0006', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c6e8e4-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f67f15-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-F556FF-2601-0007', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c6eaa2-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f67fc2-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-AAE6E7-2601-0008', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c6ec79-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6806e-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-E960B5-2601-0009', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c6eefd-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f68111-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-0E473F-2601-0010', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c6f113-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f681a7-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-F763D8-2601-0011', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c6f3db-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f68244-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-FC1DC1-2601-0012', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c6f620-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f682f0-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-0B2D3E-2601-0013', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c6f87e-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6838a-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-05B279-2601-0014', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c6faf6-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f68433-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-85DF12-2601-0015', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c6fd8a-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f684d1-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-04A84B-2601-0016', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c700d6-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f68574-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-8A2DE8-2601-0017', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c703d7-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6861b-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-A514A6-2601-0018', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c706be-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f686c2-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-E14875-2601-0019', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c709bd-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6877f-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-1E47C8-2601-0020', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c70cd4-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f68825-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-EB07B0-2601-0021', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c71006-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f688c8-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-406829-2601-0022', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c713e2-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f68963-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-5997D7-2601-0023', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c71761-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f68a3b-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-B59E65-2601-0024', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c71ae3-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f68b10-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-8DA334-2601-0025', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c71f3c-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f68bbe-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-C2C841-2601-0026', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c72379-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f68c5d-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-5B0C6D-2601-0027', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c72750-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f68cfe-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-ED3AB5-2601-0028', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c72b3e-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f68da1-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-DC8C58-2601-0029', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c72f77-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f68e45-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-38650F-2601-0030', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c7339e-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f68ee8-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-47F01E-2601-0031', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c737dc-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f68f8a-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-4B7ECB-2601-0032', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c73c35-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f69027-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-BF8821-2601-0033', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c740da-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f690c7-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-006A53-2601-0034', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c74569-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6916b-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-379F75-2601-0035', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c74a10-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6920a-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-86D2B8-2601-0036', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c74f5b-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f692ad-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-F08D89-2601-0037', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c7544d-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f69360-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-77E1C0-2601-0038', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c759c9-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6941b-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-C14830-2601-0039', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c75ee3-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f694ce-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-71550F-2601-0040', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c76480-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f695cd-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-16D241-2601-0041', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c76a24-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6967a-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-3FB0DC-2601-0042', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c76f87-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6971f-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-CFA790-2601-0043', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c77504-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f697bf-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-0D829B-2601-0044', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c77a99-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f69862-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-28EA4C-2601-0045', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c7804a-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6990c-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-CBFAF7-2601-0046', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c78619-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f699af-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-8256CF-2601-0047', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c78bfe-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f69a67-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-8A0735-2601-0048', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c79357-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f69b0b-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-B001D3-2601-0049', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c7997e-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f69bb2-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-FC82F2-2601-0050', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c79da5-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f69c55-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-A4D666-2601-0051', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c7a0b2-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f69cfa-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-212695-2601-0052', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c7a3cb-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f69da2-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-1DC05C-2601-0053', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c7a717-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f69e41-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-FFB3DA-2601-0054', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c7aa4b-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f69edc-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-908BD3-2601-0055', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c7ad8a-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f69f83-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-12164F-2601-0056', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c7b0d6-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6a021-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-AA2E91-2601-0057', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c7b42f-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6a0cf-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-1D5598-2601-0058', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c7b793-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6a16d-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-B7998D-2601-0059', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c7bb05-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6a20e-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-A12276-2601-0060', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c7bed4-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6a2bc-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-5B6474-2601-0061', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c7c264-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6a35b-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-B22144-2601-0062', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c7c5fd-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6a3f6-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-31052F-2601-0063', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c7c9bf-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6a493-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-7038BF-2601-0064', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c7cd72-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6a53e-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-DC0ED0-2601-0065', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c7d132-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6a5e9-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-9BA572-2601-0066', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c7d51f-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6a695-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-8D5B13-2601-0067', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c7d93c-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6a737-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-59B0DB-2601-0068', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c7dd2c-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6a809-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-5D4345-2601-0069', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c7e11e-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6a8af-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-77178C-2601-0070', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c7e51f-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6a948-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-27946C-2601-0071', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c7e92c-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6a9e9-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-455AAA-2601-0072', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c7ed47-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6aa94-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-549B57-2601-0073', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c7f16e-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6aeb9-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-CD894D-2601-0074', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c7f5a2-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6af79-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-56DF24-2601-0075', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c7fa26-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6b030-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-F8D188-2601-0076', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c7fe7d-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6b0ed-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-7CED67-2601-0077', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c802d6-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6b19b-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-3CFDD3-2601-0078', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c8077d-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6b254-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-F1BC5B-2601-0079', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c80c29-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6b2f6-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-507CF9-2601-0080', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c81128-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6b39b-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-9E3A90-2601-0081', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c81617-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6b467-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-B07493-2601-0082', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c81ac0-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6b516-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-C099F1-2601-0083', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c81d6b-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6b5b8-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-6D17F0-2601-0084', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c82005-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6b668-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-2CA6D1-2601-0085', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c8229f-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6b706-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-974558-2601-0086', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c82544-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6b7b6-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-B165EF-2601-0087', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c827f6-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6b862-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-37D8A8-2601-0088', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c82ad4-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6b90a-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-371168-2601-0089', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c82d8a-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6b9ae-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-9CB8AA-2601-0090', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c8304a-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6ba63-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-D85891-2601-0091', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c8330a-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6bb04-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-D4A14E-2601-0092', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c83631-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6bbab-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-00F686-2601-0093', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c838fd-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6bc66-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-4493AE-2601-0094', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c83bd6-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6bd19-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-944CAE-2601-0095', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c83eaf-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6bdcd-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-A34BF4-2601-0096', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c84195-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6be79-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-971867-2601-0097', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c84488-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6bf19-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-DF8C02-2601-0098', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c84787-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6bfce-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-B9DC6B-2601-0099', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c84ac3-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6c081-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-575304-2601-0100', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c84dad-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6c12a-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-5BF9AE-2601-0101', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c850c7-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6c1d5-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-C9D204-2601-0102', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c853ee-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6c282-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-F94B7B-2601-0103', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c856e1-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6c326-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-8F4788-2601-0104', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c85a14-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6c3ce-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-D87D34-2601-0105', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c85d13-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6c472-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-2ED9FB-2601-0106', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c86054-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6c514-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-9B5009-2601-0107', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c863bc-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6c5b8-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-5710D9-2601-0108', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c866ca-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6c667-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-5D10B3-2601-0109', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c86a23-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6c70d-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-88B45A-2601-0110', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c86d8a-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6c83b-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-C7E98B-2601-0111', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c870fd-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6c8e2-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-3D375D-2601-0112', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c874dc-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6c98f-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-F8CB44-2601-0113', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c877fb-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6ca39-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-BF7039-2601-0114', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c87b8a-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6cae8-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-3A8AF3-2601-0115', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c87ecd-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6cb8b-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-6B94C5-2601-0116', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c8821e-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6cc34-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-3FA3CA-2601-0117', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c8855f-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6ccde-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-0A1C93-2601-0118', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c888fa-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6cd80-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-FA58BD-2601-0119', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c88c48-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6ce23-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-1CEAA6-2601-0120', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c88fa2-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6cec9-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-F9ECE0-2601-0121', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c89349-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6cf6c-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-521925-2601-0122', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c8974b-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6d012-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-D71E58-2601-0123', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c89ab8-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6d0c4-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-8B6FA2-2601-0124', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c89e78-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6d169-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-44AF63-2601-0125', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c8a246-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6d211-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-DA7486-2601-0126', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c8a5bb-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6d2b4-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-B4FDCF-2601-0127', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c8a93c-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6d35b-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-27D17B-2601-0128', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c8acca-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6d409-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-7AA585-2601-0129', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c8b064-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6d4ae-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-2F555D-2601-0130', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c8b490-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6d558-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-C66862-2601-0131', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c8b8bb-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6d5fb-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-17D192-2601-0132', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c8bc87-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6d6a3-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-F63404-2601-0133', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c8c0b4-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6d752-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-487253-2601-0134', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c8c488-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6d7f7-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-297867-2601-0135', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c8c8a0-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6d89d-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-F12630-2601-0136', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c8cca3-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6d946-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-5279A2-2601-0137', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c8d072-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6d9ea-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-120D7B-2601-0138', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c8d4c5-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6da8f-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-87BEA4-2601-0139', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c8d8e5-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6db36-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-94C0AB-2601-0140', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c8dd0d-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6dbdc-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-7CBCD4-2601-0141', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c8e143-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6dc81-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-059F22-2601-0142', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c8e51f-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6dd2a-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-368D6B-2601-0143', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c8e966-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6de09-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-8DB958-2601-0144', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c8ed4d-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6dee5-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-44BA6A-2601-0145', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c8f1b7-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6dfb5-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-48980D-2601-0146', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c8f613-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6e060-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-990481-2601-0147', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c8fa75-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6e111-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-E84238-2601-0148', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c8fcb6-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6e1bf-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-3E419F-2601-0149', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c8ff21-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6e264-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-58F54A-2601-0150', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c90162-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6e30e-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-8E4849-2601-0151', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'unpaid'),
('31c903ca-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6e3af-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-2BBEDF-2601-0152', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c90833-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6e453-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-70E551-2601-0153', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c90a8e-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6e4ff-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-7E6094-2601-0154', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c90f03-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6e5a4-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-304F67-2601-0155', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c91169-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6e646-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-836B38-2601-0156', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c913dc-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f6e6ee-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-BD391F-2601-0157', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c9165c-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f703b0-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-00B834-2601-0158', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c918e9-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f70470-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-602263-2601-0159', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c91d6c-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f705ec-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-533B34-2601-0160', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c9204d-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f7069a-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-682FE7-2601-0161', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c922c7-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f7074e-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-BE337B-2601-0162', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c9279d-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f7080e-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-E9A97A-2601-0163', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c92a0a-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f708e9-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-81E6C0-2601-0164', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid'),
('31c92ccf-e8f9-11f0-8287-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '69f709bb-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 'MEM-56A046-2601-0165', 'active', '2026-01-04', '2026-03-01', 1, '2026-01-04 00:09:02', NULL, 'paid');

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
  `for_sale` tinyint(1) DEFAULT 1,
  `payment_methods_json` text DEFAULT NULL COMMENT 'JSON array of allowed payment methods (cash, vipps, card, etc)',
  `default_payment_method` varchar(50) DEFAULT 'Vipps',
  `auto_renew_enabled` tinyint(1) DEFAULT 1,
  `require_terms_acceptance` tinyint(1) DEFAULT 1,
  `future_price_change_date` date DEFAULT NULL COMMENT 'Dato for planlagt permanent prisendring',
  `future_price_new` decimal(10,2) DEFAULT NULL COMMENT 'Ny permanent pris fra change_date',
  `share_code` varchar(6) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dataark for tabell `membership_plans`
--

INSERT INTO `membership_plans` (`id`, `company_id`, `output_product_id`, `plan_code`, `name`, `description`, `price`, `currency`, `billing_cycle`, `benefits`, `is_active`, `created_at`, `image_url`, `for_sale`, `payment_methods_json`, `default_payment_method`, `auto_renew_enabled`, `require_terms_acceptance`, `future_price_change_date`, `future_price_new`, `share_code`) VALUES
('c756c21b869ae63c37e015f8ac88cea2', '7efbddea-b507-11f0-936b-b02628506e5c', NULL, 'PLAN-C756C21B', 'Bergsøy Medlem Styre', 'Hei', 3.00, 'NOK', 'yearly', '', 1, '2025-11-14 16:59:40', 'https://www.temashop.no/media/catalog/product/cache/cat_resized/1200/0/b/l/blaa_foliestjerne_festartikler_blaa_fargetema.jpg', 0, 'vipps', 'vipps', 1, 1, NULL, NULL, '39986c'),
('ec90a19c3209fc47d42524ef9b95ff44', '7efbddea-b507-11f0-936b-b02628506e5c', NULL, 'PLAN-EC90A19C', 'Bergsøy Medlem', 'Denne medlemsavgifta gjelder no kun fram til 28 Feb. 2026, \nDå forfell betalinga til nytt år.  med ny kontigent. ', 1.00, 'NOK', 'monthly', 'Autogodkjenning for alle arrangementreservasjoner', 1, '2026-01-01 21:46:33', 'https://8upload.com/image/dcda4daeaedb0a61/bdcd32dc-25e2-4f2e-a815-b4b287931fd4__1_.png', 1, 'vipps', 'Vipps', 0, 1, '2026-02-28', 250.00, '5a9fa2'),
('ef57db8d66a47a2f7c8e88eed304c971', '7efbddea-b507-11f0-936b-b02628506e5c', NULL, 'PLAN-EF57DB8D', 'Gjerrigknark', '', 69.00, 'NOK', 'yearly', '', 0, '2025-10-29 21:57:58', NULL, 1, NULL, 'Vipps', 1, 1, NULL, NULL, 'a648d7');

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
  `currency` varchar(3) DEFAULT 'NOK',
  `is_visible` tinyint(1) DEFAULT 1,
  `is_public` tinyint(1) DEFAULT 1,
  `is_marketplace_featured` tinyint(1) DEFAULT 0,
  `referred_by` varchar(100) DEFAULT NULL,
  `marketplace_priority` int(11) DEFAULT 0,
  `created_by` char(36) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `discount_percent` int(11) DEFAULT 0 COMMENT 'Rabatt i prosent (0-100)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dataark for tabell `output_products`
--

INSERT INTO `output_products` (`id`, `company_id`, `module_id`, `product_type`, `product_id`, `name`, `description`, `currency`, `is_visible`, `is_public`, `is_marketplace_featured`, `referred_by`, `marketplace_priority`, `created_by`, `created_at`, `updated_at`, `discount_percent`) VALUES
('0c6cb6f34c7aeb57ceaab677c39c8ef3', '7efbddea-b507-11f0-936b-b02628506e5c', '7f3c4d5f-af1a-11f0-a914-b02628506e5c', 'membership', 'ec90a19c3209fc47d42524ef9b95ff44', 'Bergsøy Medlem', NULL, 'NOK', 1, 1, 0, NULL, 0, '7f0d55c4-b507-11f0-936b-b02628506e5c', '2026-01-13 19:06:56', '2026-01-14 15:30:51', 0),
('12a8901486c06b666ba289d8206da1e1', '7efbddea-b507-11f0-936b-b02628506e5c', '7f3c4c76-af1a-11f0-a914-b02628506e5c', 'events', 'fc8b18485aae1b5d3890656c5ad46c39', 'Dansetreff med Steinar Engebretsen ', NULL, 'NOK', 1, 1, 0, NULL, 0, '7f0d55c4-b507-11f0-936b-b02628506e5c', '2026-01-01 22:02:09', '2026-01-20 22:29:21', 0),
('7462f56e-db1b-11f0-a4b6-b02628506e5c', '136c7dba2f23fd91db56b342dd535c225a80', '15a3c857-b57f-11f0-a6d0-b02628506e5c', 'service', 'c767681b-db1c-11f0-a4b6-b02628506e5c', 'Herreklipp', 'En profesjonell klipp tilpasset ditt hår, hodeform og stilpreferanser. Vi tar oss tid til en grundig rådgivning, presis utforming og en skjeggjustering inkludert. Ferdig med vask, styling og et tips til hjemmepleien. Perfekt for den moderne mannen som setter pris pa kvalitet og et ryddig resultat.', 'NOK', 1, 1, 0, NULL, 0, '', '2025-12-17 08:39:01', '2025-12-17 11:43:33', 0),
('794e1dfc442dae49910f82d6798b7cfe', '54da99402ab5beb095223ce68587761f2dc7', '15a3c857-b57f-11f0-a6d0-b02628506e5c', 'service', '7f8ad25f1f787f9e4d3af5a57c5b2ba8', 'Manikyrtime enkel', 'Manikyrtime', 'NOK', 1, 1, 0, NULL, 0, '738e6ff077a74d602e68b898b6dfe805312e', '2025-12-21 17:45:36', '2025-12-21 17:45:36', 0),
('9517bc1107b365524b7e9a933408f6c4', '54da99402ab5beb095223ce68587761f2dc7', '15a3c857-b57f-11f0-a6d0-b02628506e5c', 'service', '509bde56078d7143f055b1dd85e564ac', 'Manikyr', 'Time', 'NOK', 1, 1, 0, NULL, 0, '738e6ff077a74d602e68b898b6dfe805312e', '2025-12-21 11:26:38', '2025-12-21 11:26:38', 0),
('9aa2b0c9f31f68cd3b8129d501b31763', '7efbddea-b507-11f0-936b-b02628506e5c', '7f3c4c76-af1a-11f0-a914-b02628506e5c', 'events', '58da89f2617ba42b7d739fa2b91b34a5', 'Bingo 28.01.2026 kl 19.00', NULL, 'NOK', 1, 1, 0, NULL, 0, '7f0d55c4-b507-11f0-936b-b02628506e5c', '2026-01-05 19:41:43', '2026-01-20 22:29:21', 0),
('d03a0a29be280202890be14ca0bd1472', '7efbddea-b507-11f0-936b-b02628506e5c', '7f3c4c76-af1a-11f0-a914-b02628506e5c', 'events', '55473ffa9d1f35874de5acb7a811663f', 'Fredagsdans', NULL, 'NOK', 1, 1, 0, NULL, 0, '7f072d6a-b507-11f0-936b-b02628506e5c', '2026-01-10 15:13:29', '2026-01-20 22:29:21', 0),
('d5be61d1a6ddbc902e26ce99e82683d9', '0a2f314a1b2f7d21ae3de741810ef5e44506', '7f3c499a-af1a-11f0-a914-b02628506e5c', 'product', '16bbeac3470f834df80f0b63d456b9c39fa2', 'Pølse med brød', '', 'NOK', 1, 1, 0, NULL, 0, 'e722f72f4db3114c487c2a267de58fb2eb82', '2025-11-08 09:22:07', '2025-11-08 15:43:10', 0),
('e71e78dafd755f51dbaba45d6e5f57fd', '0a2f314a1b2f7d21ae3de741810ef5e44506', '7f3c499a-af1a-11f0-a914-b02628506e5c', 'product', '09f274670a3f2401001d2d68de1ba9822e27', 'Brus Cola', '', 'NOK', 1, 1, 0, NULL, 0, 'e722f72f4db3114c487c2a267de58fb2eb82', '2025-11-08 14:10:29', '2025-11-08 15:42:03', 0),
('f1dac2a3466789d281fef56c0bbd2a06', 'e63933d7ed368ed5a117c9c6e9a7f434fbaf', '7f3c499a-af1a-11f0-a914-b02628506e5c', 'product', 'dfe54e03c045f883a280ecab8c27eef1f8e5', '', NULL, 'NOK', 1, 1, 0, NULL, 0, '2cb1eef4c42d797384ff208a9f0b407a8a53', '2025-11-08 06:42:59', '2025-11-08 06:42:59', 0);

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
  `img_url` varchar(255) DEFAULT NULL,
  `payment_methods_json` text DEFAULT NULL COMMENT 'JSON array of allowed payment methods',
  `default_payment_method` varchar(50) DEFAULT NULL,
  `created_by` char(36) DEFAULT NULL,
  `tenant_staff_ids` text DEFAULT NULL COMMENT 'JSON array of staff IDs who can perform this service',
  `required_duration_minutes` int(11) DEFAULT NULL,
  `buffer_before_minutes` int(11) DEFAULT 0,
  `buffer_after_minutes` int(11) DEFAULT 0,
  `max_daily_bookings` int(11) DEFAULT NULL,
  `service_capacity` int(11) DEFAULT 1,
  `share_code` varchar(6) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dataark for tabell `reservation_services`
--

INSERT INTO `reservation_services` (`id`, `company_id`, `output_product_id`, `service_name`, `description`, `price`, `duration_minutes`, `service_type`, `available_from`, `available_to`, `is_active`, `created_at`, `updated_at`, `assigned_staff_mode`, `assigned_staff_ids`, `capacity`, `img_url`, `payment_methods_json`, `default_payment_method`, `created_by`, `tenant_staff_ids`, `required_duration_minutes`, `buffer_before_minutes`, `buffer_after_minutes`, `max_daily_bookings`, `service_capacity`, `share_code`) VALUES
('509bde56078d7143f055b1dd85e564ac', '54da99402ab5beb095223ce68587761f2dc7', '9517bc1107b365524b7e9a933408f6c4', 'Manikyr', 'Time', 700.00, 120, 'Negletime', '09:00:00', '17:00:00', 1, '2025-12-21 11:26:38', '2026-01-16 07:50:48', NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, 1, 'f72206'),
('7f8ad25f1f787f9e4d3af5a57c5b2ba8', '54da99402ab5beb095223ce68587761f2dc7', '794e1dfc442dae49910f82d6798b7cfe', 'Manikyrtime enkel', 'Manikyrtime', 500.00, 90, 'Negletime', '09:00:00', '17:00:00', 1, '2025-12-21 17:45:36', '2026-01-16 07:50:33', NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, 1, 'd90362'),
('c767681b-db1c-11f0-a4b6-b02628506e5c', '136c7dba2f23fd91db56b342dd535c225a80', NULL, 'Hår og skjeggtrim', NULL, 550.00, 10, 'service', '09:00:00', '17:00:00', 1, '2025-12-17 08:48:29', '2026-01-13 23:04:25', NULL, NULL, 1, 'https://scaled-images.timma.fi/timma/image/upload/w_1080,c_limit,q_auto/v1692818311/c/babylon-fris-r-d80fe5d0-41e9-11ee-b56a-67315532b89f.jpg', NULL, NULL, NULL, NULL, NULL, 0, 0, NULL, 1, '5bd89f');

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

--
-- Dataark for tabell `saas_admins`
--

INSERT INTO `saas_admins` (`id`, `saas_staff_id`, `email`, `password_hash`, `role`, `permissions`, `is_active`, `last_login`, `mfa_enabled`, `mfa_secret`, `created_at`, `updated_at`, `username`) VALUES
('4538cad6-b0e4-11f0-a1bb-b02628506e5c', '453689f8-b0e4-11f0-a1bb-b02628506e5c', 'system.admin@salesmanno04.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin', NULL, 1, NULL, 0, NULL, '2025-10-24 16:18:10', '2025-10-24 16:18:10', NULL),
('ba464206-b8d8-11f0-ad30-b02628506e5c', 'd1522625-b8d7-11f0-ad30-b02628506e5c', 'magnekvalsvique@gmail.com', '$2y$12$c3IAZ9yaJMz/RhgZDHlo6OLAEjdZGWzCrGE1g.D/My7BR0nqqb8ie', 'Ultra user', 'ALL', 1, '2026-01-20 08:10:44', 0, NULL, '2025-11-03 18:15:42', '2026-01-20 08:10:44', 'salesMan1');

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

--
-- Dataark for tabell `saas_staff`
--

INSERT INTO `saas_staff` (`id`, `first_name`, `last_name`, `email`, `phone`, `job_title`, `department`, `employment_type`, `hire_date`, `address_street`, `address_city`, `address_state`, `address_zip`, `address_country`, `emergency_contact_name`, `emergency_contact_phone`, `is_active`, `created_at`, `updated_at`) VALUES
('453689f8-b0e4-11f0-a1bb-b02628506e5c', 'System', 'Administrator', 'system.admin@salesmanno04.com', '+4712345678', 'System Admin', 'IT', 'full_time', '2025-10-24', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2025-10-24 16:18:10', '2025-10-24 16:18:10'),
('d1522625-b8d7-11f0-ad30-b02628506e5c', 'Magne', 'Kvalsvik', 'magne.k@salesman.no', '40469364', 'SUPPORT', 'Salesman.no', 'full_time', NULL, 'Anholen 13B', 'Ulsteinvik', 'Møre og Romsdal', '6065', 'Norway', 'Sølvi Hjelvik', '48361707', 1, '2025-11-03 18:09:11', '2025-11-03 18:09:11');

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

--
-- Dataark for tabell `saas_system_config`
--

INSERT INTO `saas_system_config` (`id`, `config_key`, `config_value`, `config_type`, `description`, `is_sensitive`, `updated_by`, `created_at`, `updated_at`) VALUES
('2ec70b77-b9a9-11f0-ad30-b02628506e5c', 'domeneshop_api_token', 'n3MOuieSzXOmPXmi', 'encrypted', 'Domeneshop API Token for DNS management', 1, NULL, '2025-11-04 19:07:53', '2025-11-04 19:08:33'),
('2ec72547-b9a9-11f0-ad30-b02628506e5c', 'domeneshop_api_secret', 'OWsVSKWgNlSbyaceXgTYu6P73kXwmMCwT1ef5g7JyaOEQqch8t1heeGwZm73XRNu', 'encrypted', 'Domeneshop API Secret for DNS management', 1, NULL, '2025-11-04 19:07:53', '2025-11-04 19:08:33'),
('2ec72715-b9a9-11f0-ad30-b02628506e5c', 'domeneshop_domain_id', '2167680', 'string', 'Domeneshop Domain ID for salesman.no', 0, NULL, '2025-11-04 19:07:53', '2025-11-04 20:03:24'),
('731f1d25-b9e5-11f0-ad30-b02628506e5c', 'production_server_ip', '194.63.249.219', 'string', 'IP-adresse for www.salesman.no serveren', 0, NULL, '2025-11-05 02:19:17', '2025-11-05 02:19:17');

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

--
-- Dataark for tabell `sales_categories`
--

INSERT INTO `sales_categories` (`id`, `company_id`, `name`, `description`, `parent_category_id`, `sort_order`, `is_active`, `created_at`) VALUES
('492d07e1-b7f2-11f0-a3af-b02628506e5c', 'e090c403-b7f1-11f0-a3af-b02628506e5c', 'Kaffe og varme drikker', 'Spesialkaffe, te og varme drikker', NULL, 1, 1, '2025-11-02 14:46:08'),
('492d1503-b7f2-11f0-a3af-b02628506e5c', 'e090c403-b7f1-11f0-a3af-b02628506e5c', 'Kalde drikker', 'Juice, brus, vann og kalde drikker', NULL, 2, 1, '2025-11-02 14:46:08'),
('492d1629-b7f2-11f0-a3af-b02628506e5c', 'e090c403-b7f1-11f0-a3af-b02628506e5c', 'Bakverk', 'Kaker, boller, croissanter og bakverk', NULL, 3, 1, '2025-11-02 14:46:08'),
('492d16e5-b7f2-11f0-a3af-b02628506e5c', 'e090c403-b7f1-11f0-a3af-b02628506e5c', 'Lunsj', 'Smørbrød, salater og lunsjretter', NULL, 4, 1, '2025-11-02 14:46:08');

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

--
-- Dataark for tabell `sales_products`
--

INSERT INTO `sales_products` (`id`, `company_id`, `category_id`, `sku`, `name`, `description`, `barcode`, `cost_price`, `selling_price`, `img_product`, `currency`, `tax_rate`, `item_taxable`, `unit`, `reorder_level`, `is_active`, `output_product_id`, `created_at`, `updated_at`, `stock_quantity`, `low_stock_threeshold`) VALUES
('16ad682f97547c48b3a2d78cb9aea61d5251', '7efbddea-b507-11f0-936b-b02628506e5c', NULL, 'PRD-1761772397-1747', 'Pølse med brød', 'Gode pølse', '', 5.00, 40.00, 'https://external-content.duckduckgo.com/iu/?u=https%3A%2F%2Ftse1.explicit.bing.net%2Fth%2Fid%2FOIP.OQ2IrOezxXtIgRDXKX6URQHaHa%3Fpid%3DApi&f=1&ipt=8c4a0a5c2500e2114cfbb551f011648cfd9a36fcc69fcf3a519ad7ddc545085c&ipo=images', 'NOK', 25.00, 1, 'stk', 1, 1, NULL, '2025-10-29 22:13:17', '2025-10-31 09:39:07', 165, 5),
('24692482c3a89b75763a4408c338696369b4', '7efbddea-b507-11f0-936b-b02628506e5c', NULL, 'PRD-1762613472-8687', 'Kake', '', '', 8.00, 45.00, '', 'NOK', 25.00, 1, 'stk', 0, 1, NULL, '2025-11-08 15:51:12', '2025-11-13 23:21:22', -1, 5),
('2f5d1e6bdd2ba279943b2d880e6c246bdfce', '7efbddea-b507-11f0-936b-b02628506e5c', NULL, 'PRD-1761771139-4583', 'Cola', '', '', 15.00, 40.00, 'https://cdn.pji.nu/product/standard/280/499819.jpg', 'NOK', 25.00, 1, 'stk', 0, 1, NULL, '2025-10-29 21:52:19', '2025-11-13 23:21:02', -3, 5),
('dedbc54d4e7cf46b1feb77e7a55d1f1b4c4a', '7efbddea-b507-11f0-936b-b02628506e5c', NULL, 'PRD-1762613469-3267', 'Kake', '', '', 8.00, 45.00, '', 'NOK', 25.00, 1, 'stk', 0, 1, NULL, '2025-11-08 15:51:10', '2025-11-13 23:21:22', -1, 5);

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

--
-- Dataark for tabell `sales_transactions`
--

INSERT INTO `sales_transactions` (`id`, `company_id`, `sales_product_id`, `quantity`, `total_price`, `payment_method`, `customer_name`, `customer_email`, `send_receipt`, `notes`, `created_by`, `created_at`) VALUES
(4, '7efbddea-b507-11f0-936b-b02628506e5c', '2f5d1e6bdd2ba279943b2d880e6c246bdfce', 1, 40.00, 'cash', 'Walk-in', 'atlekarl', 0, '', '7f0d55c4-b507-11f0-936b-b02628506e5c', '2025-10-29 21:53:05'),
(5, '7efbddea-b507-11f0-936b-b02628506e5c', '16ad682f97547c48b3a2d78cb9aea61d5251', 1, 40.00, 'cash', 'Walk-in', NULL, 0, '', '7f072d6a-b507-11f0-936b-b02628506e5c', '2025-10-29 22:17:34'),
(6, '7efbddea-b507-11f0-936b-b02628506e5c', '16ad682f97547c48b3a2d78cb9aea61d5251', 2, 80.00, 'cash', 'Walk-in', NULL, 0, '', '7f0d55c4-b507-11f0-936b-b02628506e5c', '2025-10-29 22:17:59'),
(7, '7efbddea-b507-11f0-936b-b02628506e5c', '2f5d1e6bdd2ba279943b2d880e6c246bdfce', 2, 80.00, 'cash', 'Walk-in', NULL, 0, '', '7f0d55c4-b507-11f0-936b-b02628506e5c', '2025-10-29 22:17:59'),
(8, '7efbddea-b507-11f0-936b-b02628506e5c', '2f5d1e6bdd2ba279943b2d880e6c246bdfce', 2, 80.00, 'card', 'Walk-in', NULL, 0, '', '7f0d55c4-b507-11f0-936b-b02628506e5c', '2025-10-29 22:18:45'),
(9, '7efbddea-b507-11f0-936b-b02628506e5c', '16ad682f97547c48b3a2d78cb9aea61d5251', 3, 120.00, 'card', 'Walk-in', NULL, 0, '', '7f0d55c4-b507-11f0-936b-b02628506e5c', '2025-10-29 22:18:45'),
(10, '7efbddea-b507-11f0-936b-b02628506e5c', '16ad682f97547c48b3a2d78cb9aea61d5251', 1, 40.00, 'cash', 'Walk-in', NULL, 0, '', '7f0d55c4-b507-11f0-936b-b02628506e5c', '2025-10-29 22:20:13'),
(11, '7efbddea-b507-11f0-936b-b02628506e5c', '2f5d1e6bdd2ba279943b2d880e6c246bdfce', 1, 40.00, 'cash', 'Walk-in', NULL, 0, '', '7f0d55c4-b507-11f0-936b-b02628506e5c', '2025-10-29 22:20:13'),
(12, '7efbddea-b507-11f0-936b-b02628506e5c', '16ad682f97547c48b3a2d78cb9aea61d5251', 3, 120.00, 'cash', 'Walk-in', NULL, 0, '', '7f0d55c4-b507-11f0-936b-b02628506e5c', '2025-10-30 06:58:02'),
(13, '7efbddea-b507-11f0-936b-b02628506e5c', '16ad682f97547c48b3a2d78cb9aea61d5251', 3, 120.00, 'cash', 'Walk-in', NULL, 0, '', '7f0d55c4-b507-11f0-936b-b02628506e5c', '2025-10-30 09:38:23'),
(14, '7efbddea-b507-11f0-936b-b02628506e5c', '16ad682f97547c48b3a2d78cb9aea61d5251', 1, 40.00, 'cash', 'Walk-in', NULL, 0, '', '7f0d55c4-b507-11f0-936b-b02628506e5c', '2025-10-30 11:40:36'),
(15, '7efbddea-b507-11f0-936b-b02628506e5c', '16ad682f97547c48b3a2d78cb9aea61d5251', 1, 40.00, 'cash', 'Walk-in', NULL, 0, '', '7f0d55c4-b507-11f0-936b-b02628506e5c', '2025-10-31 09:39:07'),
(24, '7efbddea-b507-11f0-936b-b02628506e5c', '2f5d1e6bdd2ba279943b2d880e6c246bdfce', 3, 120.00, 'cash', 'Walk-in', NULL, 0, '', '7f0d55c4-b507-11f0-936b-b02628506e5c', '2025-11-13 23:21:02'),
(25, '7efbddea-b507-11f0-936b-b02628506e5c', 'dedbc54d4e7cf46b1feb77e7a55d1f1b4c4a', 1, 45.00, 'cash', 'Walk-in', NULL, 0, '', '7f0d55c4-b507-11f0-936b-b02628506e5c', '2025-11-13 23:21:22'),
(26, '7efbddea-b507-11f0-936b-b02628506e5c', '24692482c3a89b75763a4408c338696369b4', 1, 45.00, 'cash', 'Walk-in', NULL, 0, '', '7f0d55c4-b507-11f0-936b-b02628506e5c', '2025-11-13 23:21:22');

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

--
-- Dataark for tabell `self_company`
--

INSERT INTO `self_company` (`id`, `company_name`, `legal_name`, `organization_no`, `tax_id`, `company_number`, `contact_email`, `contact_phone`, `website`, `logo_url`, `legal_address_street`, `legal_address_city`, `legal_address_state`, `legal_address_zip`, `legal_address_country`, `bank_name`, `bank_account_number`, `bank_swift_code`, `invoice_prefix`, `invoice_start_number`, `invoice_terms`, `invoice_due_days`, `default_tax_rate`, `tax_enabled`, `default_currency`, `is_active`, `setup_completed`, `created_by`, `created_at`, `updated_at`) VALUES
('453afc7e-b0e4-11f0-a1bb-b02628506e5c', 'Salesman', 'Salesman', '123456789', NULL, '1', 'admin@salesmanno04.com', '+4712345678', 'https://salesmanno04.com', 'https://8upload.com/display/690aa0b5321ac/salesman-removebg-preview.png.php', 'Anholen 13B', 'Ulsteinvik', 'Møre og Romsdal', '6065', 'Norway', NULL, NULL, NULL, 'INV', 1000, NULL, 30, 25.00, 1, 'NOK', 1, 1, '4538cad6-b0e4-11f0-a1bb-b02628506e5c', '2025-10-24 16:18:10', '2025-11-05 01:57:37'),
('f9bb758a-b9e1-11f0-ad30-b02628506e5c', 'Din SAAS Bedrift AS', 'Din SAAS Bedrift AS', NULL, NULL, NULL, 'admin@saas.no', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Norway', NULL, NULL, NULL, 'INV', 1000, NULL, 30, 0.00, 0, 'NOK', 1, 0, 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-11-05 01:54:25', '2025-11-05 01:54:25');

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `service_bookings`
--

CREATE TABLE `service_bookings` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `company_id` char(36) NOT NULL,
  `service_id` char(36) NOT NULL,
  `customer_id` char(36) NOT NULL,
  `staff_id` char(36) DEFAULT NULL,
  `booking_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `status` varchar(20) DEFAULT 'confirmed',
  `payment_status` varchar(20) DEFAULT 'pending',
  `payment_method` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `noshow_booking` smallint(6) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `staff_availability`
--

CREATE TABLE `staff_availability` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `company_id` char(36) NOT NULL,
  `staff_id` char(36) NOT NULL,
  `date` date NOT NULL,
  `day_of_week` tinyint(1) DEFAULT NULL COMMENT '0=Sunday, 1=Monday, etc.',
  `start_time` time DEFAULT '09:00:00',
  `end_time` time DEFAULT '17:00:00',
  `is_working_day` tinyint(1) DEFAULT 1,
  `booked_slots` text DEFAULT NULL,
  `breaks` text DEFAULT NULL,
  `max_bookings_per_day` int(11) DEFAULT NULL,
  `slot_duration_minutes` int(11) DEFAULT 60,
  `buffer_between_minutes` int(11) DEFAULT 0,
  `availability_type` varchar(20) DEFAULT 'standard',
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dataark for tabell `staff_availability`
--

INSERT INTO `staff_availability` (`id`, `company_id`, `staff_id`, `date`, `day_of_week`, `start_time`, `end_time`, `is_working_day`, `booked_slots`, `breaks`, `max_bookings_per_day`, `slot_duration_minutes`, `buffer_between_minutes`, `availability_type`, `notes`, `created_at`, `updated_at`) VALUES
('081cbb76-dbd4-11f0-bffa-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '7efffc84-b507-11f0-936b-b02628506e5c', '2025-12-19', 5, '09:00:00', '17:00:00', 0, NULL, NULL, NULL, 60, 0, 'standard', NULL, '2025-12-18 06:40:16', '2025-12-18 06:40:17'),
('119868b1-dea9-11f0-bb40-b02628506e5c', '54da99402ab5beb095223ce68587761f2dc7', '76b943ed854e4fd8ea8ac017f02666f48b4d', '2025-12-21', 0, '09:00:00', '17:00:00', 1, NULL, NULL, NULL, 60, 0, 'standard', NULL, '2025-12-21 21:10:17', '2025-12-21 21:10:19'),
('11988729-dea9-11f0-bb40-b02628506e5c', '54da99402ab5beb095223ce68587761f2dc7', '76b943ed854e4fd8ea8ac017f02666f48b4d', '2025-12-22', 1, '09:00:00', '17:00:00', 1, NULL, NULL, NULL, 60, 0, 'standard', NULL, '2025-12-21 21:10:17', '2025-12-21 21:10:20'),
('1198afbc-dea9-11f0-bb40-b02628506e5c', '54da99402ab5beb095223ce68587761f2dc7', '76b943ed854e4fd8ea8ac017f02666f48b4d', '2025-12-23', 2, '09:00:00', '17:00:00', 1, NULL, NULL, NULL, 60, 0, 'standard', NULL, '2025-12-21 21:10:17', '2025-12-21 21:10:20'),
('1198d39e-dea9-11f0-bb40-b02628506e5c', '54da99402ab5beb095223ce68587761f2dc7', '76b943ed854e4fd8ea8ac017f02666f48b4d', '2025-12-24', 3, '09:00:00', '17:00:00', 1, NULL, NULL, NULL, 60, 0, 'standard', NULL, '2025-12-21 21:10:17', '2025-12-21 21:10:21'),
('1198f3fb-dea9-11f0-bb40-b02628506e5c', '54da99402ab5beb095223ce68587761f2dc7', '76b943ed854e4fd8ea8ac017f02666f48b4d', '2025-12-25', 4, '09:00:00', '17:00:00', 1, NULL, NULL, NULL, 60, 0, 'standard', NULL, '2025-12-21 21:10:17', '2025-12-21 21:10:22'),
('11991762-dea9-11f0-bb40-b02628506e5c', '54da99402ab5beb095223ce68587761f2dc7', '76b943ed854e4fd8ea8ac017f02666f48b4d', '2025-12-26', 5, '09:00:00', '17:00:00', 0, NULL, NULL, NULL, 60, 0, 'standard', NULL, '2025-12-21 21:10:17', '2025-12-21 21:10:17'),
('119937bf-dea9-11f0-bb40-b02628506e5c', '54da99402ab5beb095223ce68587761f2dc7', '76b943ed854e4fd8ea8ac017f02666f48b4d', '2025-12-27', 6, '09:00:00', '17:00:00', 0, NULL, NULL, NULL, 60, 0, 'standard', NULL, '2025-12-21 21:10:17', '2025-12-21 21:10:17'),
('2847f668-dd00-11f0-956e-b02628506e5c', '54da99402ab5beb095223ce68587761f2dc7', '76b943ed854e4fd8ea8ac017f02666f48b4d', '2025-12-19', 5, '09:00:00', '17:00:00', 0, NULL, NULL, NULL, 60, 0, 'standard', NULL, '2025-12-19 18:28:39', '2025-12-23 01:28:18'),
('28ad7639-dd00-11f0-956e-b02628506e5c', '54da99402ab5beb095223ce68587761f2dc7', '76b943ed854e4fd8ea8ac017f02666f48b4d', '2025-12-17', 3, '09:00:00', '17:00:00', 1, NULL, NULL, NULL, 60, 0, 'standard', NULL, '2025-12-19 18:28:40', '2025-12-20 15:43:43'),
('2999a05d-dd00-11f0-956e-b02628506e5c', '54da99402ab5beb095223ce68587761f2dc7', '76b943ed854e4fd8ea8ac017f02666f48b4d', '2025-12-16', 2, '09:00:00', '17:00:00', 1, NULL, NULL, NULL, 60, 0, 'standard', NULL, '2025-12-19 18:28:41', '2025-12-20 15:43:44'),
('2b0b7c0b-dc05-11f0-bffa-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '0822cd1cbe90d9e4afa5bf5011deb42ef7d8', '2025-11-30', 0, '09:00:00', '17:00:00', 1, NULL, NULL, NULL, 60, 0, 'standard', NULL, '2025-12-18 12:32:00', '2025-12-18 12:32:01'),
('49f5a4ab-df96-11f0-8c95-b02628506e5c', '54da99402ab5beb095223ce68587761f2dc7', '76b943ed854e4fd8ea8ac017f02666f48b4d', '2025-12-15', 1, '09:00:00', '17:00:00', 1, NULL, NULL, NULL, 60, 0, 'standard', NULL, '2025-12-23 01:28:22', '2025-12-23 01:28:23'),
('4e313bd9-ddb2-11f0-a864-b02628506e5c', '54da99402ab5beb095223ce68587761f2dc7', '76b943ed854e4fd8ea8ac017f02666f48b4d', '2025-12-12', 5, '09:00:00', '17:00:00', 0, NULL, NULL, NULL, 60, 0, 'standard', NULL, '2025-12-20 15:43:53', '2025-12-20 15:43:54'),
('4f53a2b1-ddb2-11f0-a864-b02628506e5c', '54da99402ab5beb095223ce68587761f2dc7', '76b943ed854e4fd8ea8ac017f02666f48b4d', '2025-12-20', 6, '09:00:00', '17:00:00', 0, NULL, NULL, NULL, 60, 0, 'standard', NULL, '2025-12-20 15:43:55', '2025-12-20 15:43:59'),
('aca96307-dc63-11f0-bffa-b02628506e5c', '54da99402ab5beb095223ce68587761f2dc7', '76b943ed854e4fd8ea8ac017f02666f48b4d', '2025-11-06', 4, '09:00:00', '17:00:00', 0, NULL, NULL, NULL, 60, 0, 'standard', NULL, '2025-12-18 23:48:30', '2025-12-18 23:48:52'),
('adb541c5-dc63-11f0-bffa-b02628506e5c', '54da99402ab5beb095223ce68587761f2dc7', '76b943ed854e4fd8ea8ac017f02666f48b4d', '2025-11-05', 3, '09:00:00', '17:00:00', 0, NULL, NULL, NULL, 60, 0, 'standard', NULL, '2025-12-18 23:48:32', '2025-12-18 23:48:52'),
('ae087910-dc63-11f0-bffa-b02628506e5c', '54da99402ab5beb095223ce68587761f2dc7', '76b943ed854e4fd8ea8ac017f02666f48b4d', '2025-11-04', 2, '09:00:00', '17:00:00', 0, NULL, NULL, NULL, 60, 0, 'standard', NULL, '2025-12-18 23:48:32', '2025-12-18 23:48:51'),
('ae704436-dc63-11f0-bffa-b02628506e5c', '54da99402ab5beb095223ce68587761f2dc7', '76b943ed854e4fd8ea8ac017f02666f48b4d', '2025-11-03', 1, '09:00:00', '17:00:00', 0, NULL, NULL, NULL, 60, 0, 'standard', NULL, '2025-12-18 23:48:33', '2025-12-18 23:48:51'),
('aec09af9-dc63-11f0-bffa-b02628506e5c', '54da99402ab5beb095223ce68587761f2dc7', '76b943ed854e4fd8ea8ac017f02666f48b4d', '2025-11-02', 0, '09:00:00', '17:00:00', 0, NULL, NULL, NULL, 60, 0, 'standard', NULL, '2025-12-18 23:48:34', '2025-12-18 23:48:51'),
('b3da5205-dbf6-11f0-bffa-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '7f0980cf-b507-11f0-936b-b02628506e5c', '2025-12-19', 5, '09:00:00', '17:00:00', 1, NULL, NULL, NULL, 60, 0, 'standard', NULL, '2025-12-18 10:48:27', '2025-12-18 10:48:30'),
('b453c7c1-dc63-11f0-bffa-b02628506e5c', '54da99402ab5beb095223ce68587761f2dc7', '76b943ed854e4fd8ea8ac017f02666f48b4d', '2025-11-07', 5, '09:00:00', '17:00:00', 0, NULL, NULL, NULL, 60, 0, 'standard', NULL, '2025-12-18 23:48:43', '2025-12-18 23:48:52'),
('b71fe63b-dc63-11f0-bffa-b02628506e5c', '54da99402ab5beb095223ce68587761f2dc7', '76b943ed854e4fd8ea8ac017f02666f48b4d', '2025-11-08', 6, '09:00:00', '17:00:00', 0, NULL, NULL, NULL, 60, 0, 'standard', NULL, '2025-12-18 23:48:48', '2025-12-18 23:48:53'),
('bb683923-dc63-11f0-bffa-b02628506e5c', '54da99402ab5beb095223ce68587761f2dc7', '76b943ed854e4fd8ea8ac017f02666f48b4d', '2025-12-18', 4, '09:00:00', '17:00:00', 1, NULL, NULL, NULL, 60, 0, 'standard', NULL, '2025-12-18 23:48:55', '2025-12-20 15:43:40');

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `staff_services`
--

CREATE TABLE `staff_services` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `company_id` char(36) NOT NULL,
  `staff_id` char(36) NOT NULL,
  `service_id` char(36) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `max_daily_bookings` int(11) DEFAULT 5,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `worker_primary` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dataark for tabell `staff_services`
--

INSERT INTO `staff_services` (`id`, `company_id`, `staff_id`, `service_id`, `is_active`, `max_daily_bookings`, `created_at`, `updated_at`, `worker_primary`) VALUES
('79cc605f-de8c-11f0-bb40-b02628506e5c', '54da99402ab5beb095223ce68587761f2dc7', '76b943ed854e4fd8ea8ac017f02666f48b4d', '7f8ad25f1f787f9e4d3af5a57c5b2ba8', 1, 5, '2025-12-21 17:45:36', '2026-01-16 07:50:33', 1),
('886c1e44-de57-11f0-bb40-b02628506e5c', '54da99402ab5beb095223ce68587761f2dc7', '76b943ed854e4fd8ea8ac017f02666f48b4d', '509bde56078d7143f055b1dd85e564ac', 1, 5, '2025-12-21 11:26:38', '2026-01-16 07:50:48', 1);

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

--
-- Dataark for tabell `system_modules`
--

INSERT INTO `system_modules` (`id`, `module_key`, `name`, `description`, `category`, `icon`, `version`, `is_active`, `created_at`, `updated_at`) VALUES
('15a3c857-b57f-11f0-a6d0-b02628506e5c', 'reservations', 'Reservasjoner', 'Reservasjonssystem', 'business', 'calendar-check', '1.0.0', 1, '2025-10-30 11:56:27', '2025-10-30 11:56:27'),
('7f3c499a-af1a-11f0-a914-b02628506e5c', 'sales', 'Sales', 'Point of sale and product management', 'business', 'shopping-cart', '1.0.0', 1, '2025-10-22 07:41:18', '2025-10-25 13:46:20'),
('7f3c4bb2-af1a-11f0-a914-b02628506e5c', 'economy', 'Economy', 'Financial management and accounting', 'business', 'dollar-sign', '1.0.0', 1, '2025-10-22 07:41:18', '2025-10-25 13:46:20'),
('7f3c4c76-af1a-11f0-a914-b02628506e5c', 'events', 'Events', 'Event management and ticketing', 'business', 'calendar', '1.0.0', 1, '2025-10-22 07:41:18', '2025-10-25 13:46:20'),
('7f3c4ceb-af1a-11f0-a914-b02628506e5c', 'reservation', 'Reservation', 'Service bookings and appointments', 'business', 'clock', '1.0.0', 1, '2025-10-22 07:41:18', '2025-10-25 13:46:20'),
('7f3c4d5f-af1a-11f0-a914-b02628506e5c', 'membership', 'Membership', 'Membership management', 'business', 'users', '1.0.0', 1, '2025-10-22 07:41:18', '2025-10-25 13:46:21'),
('7f3c4dcc-af1a-11f0-a914-b02628506e5c', 'rental', 'Rental', 'Equipment and object rentals', 'business', 'package', '1.0.0', 1, '2025-10-22 07:41:18', '2025-10-25 13:46:21'),
('7f3c4e39-af1a-11f0-a914-b02628506e5c', 'email', 'Email', 'Email campaigns and templates', 'communication', 'mail', '1.0.0', 1, '2025-10-22 07:41:18', '2025-10-25 13:46:21'),
('7f3c4e9e-af1a-11f0-a914-b02628506e5c', 'marketplace', 'Marketplace', 'Online marketplace listings', 'business', 'shopping-bag', '1.0.0', 1, '2025-10-22 07:41:18', '2025-10-25 13:46:21'),
('7f3c4f0b-af1a-11f0-a914-b02628506e5c', 'website', 'Website', 'Company website management', 'marketing', 'globe', '1.0.0', 1, '2025-10-22 07:41:18', '2025-10-25 13:46:21'),
('f9d42479-b182-11f0-b9ad-b02628506e5c', 'ditt_nettsted', 'Ditt Nettsted', 'Administrer firmaets nettside og velg mal', 'business', 'bi-globe', '1.0.0', 1, '2025-10-25 11:14:14', '2025-10-25 11:14:14');

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

--
-- Dataark for tabell `tenant_admins`
--

INSERT INTO `tenant_admins` (`id`, `company_id`, `email`, `password_hash`, `username`, `role`, `is_active`, `last_login`, `mfa_enabled`, `mfa_secret`, `created_by`, `created_at`, `updated_at`, `tenant_staff_id`) VALUES
('30ffdd6978f7086948c110585ae54954af7f', '136c7dba2f23fd91db56b342dd535c225a80', '', '$2y$10$/JK.sDzm8.KQDHmsVf7Hau.CQUje9orU6Lu4uqrCwFvLJwX0KKZyS', 'babylon1', 'admin', 1, '2025-12-17 15:15:48', 0, NULL, 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-12-17 02:42:19', '2025-12-17 15:15:48', '65c9d59ab53ecdcaf4f80de9a25821033906'),
('580850091a22fb4dbe9023b759222b305282', 'a9cdf14b6790d0247b55534d68065eb695f9', '', '$2y$10$.Pat/emm80aCNmSQoNsQBetINFkYYBpAjXNe8pFPNwcnYoPcLfzgy', 'Kb', 'admin', 1, '2025-11-13 21:57:27', 0, NULL, 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-11-13 16:28:41', '2025-11-13 21:57:27', 'a355a634b40eb972506264982f4448a4d296'),
('738e6ff077a74d602e68b898b6dfe805312e', '54da99402ab5beb095223ce68587761f2dc7', '', '$2y$10$TLLq5d/l9bhxXe.xzq1LM.BAdmfDCSH4p5VOweFgPmauG0e3wYGBi', 'jn1', 'admin', 1, '2026-01-16 07:49:26', 0, NULL, 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-12-18 15:36:57', '2026-01-16 07:49:26', '76b943ed854e4fd8ea8ac017f02666f48b4d'),
('7f072d6a-b507-11f0-936b-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'maritahr@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'maritahr', 'admin', 1, '2026-01-20 17:39:17', 0, NULL, '', '2025-10-29 21:40:24', '2026-01-20 17:39:17', '7efffc84-b507-11f0-936b-b02628506e5c'),
('7f0d55c4-b507-11f0-936b-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'atlekarl@tussa.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'atlekarl', 'super_admin', 1, '2026-01-20 23:05:27', 0, NULL, '', '2025-10-29 21:40:24', '2026-01-20 23:05:27', '7f0980cf-b507-11f0-936b-b02628506e5c'),
('98c4e6f482c623c4e38e672c72b5ac7415d9', 'a7eb3dbc5543932549d02a0bfd2b79720ad5', '', '$2y$10$6lw/xOgL6uMSSpFCZLOLpeAoJxLcC2ao31yEDcgncsg3jqD2eI/Xm', 'Mussa', 'admin', 1, NULL, 0, NULL, 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-11-20 15:46:48', '2025-11-20 15:46:48', NULL),
('f996adb8d578c9f15859cc9ffb78b023792b', '7efbddea-b507-11f0-936b-b02628506e5c', '', '$2y$10$vMsg4HXtPkyVLBHthiO7J.DsuK0sHfbkyZizxNfv/jE6ET80tTg5y', 'magnek', 'admin', 1, '2026-01-21 00:37:06', 0, NULL, 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-11-22 00:43:03', '2026-01-21 00:37:06', NULL);

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

--
-- Dataark for tabell `tenant_admin_modules`
--

INSERT INTO `tenant_admin_modules` (`id`, `tenant_admin_id`, `module_id`, `granted_by`, `granted_at`) VALUES
('14a83530-c0a6-11f0-8af7-b02628506e5c', '580850091a22fb4dbe9023b759222b305282', '7f3c499a-af1a-11f0-a914-b02628506e5c', 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-11-13 16:33:19'),
('14a83dcc-c0a6-11f0-8af7-b02628506e5c', '580850091a22fb4dbe9023b759222b305282', '7f3c4bb2-af1a-11f0-a914-b02628506e5c', 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-11-13 16:33:19'),
('14a845c3-c0a6-11f0-8af7-b02628506e5c', '580850091a22fb4dbe9023b759222b305282', '7f3c4c76-af1a-11f0-a914-b02628506e5c', 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-11-13 16:33:19'),
('14a84cf1-c0a6-11f0-8af7-b02628506e5c', '580850091a22fb4dbe9023b759222b305282', '7f3c4ceb-af1a-11f0-a914-b02628506e5c', 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-11-13 16:33:19'),
('14a8535f-c0a6-11f0-8af7-b02628506e5c', '580850091a22fb4dbe9023b759222b305282', '7f3c4d5f-af1a-11f0-a914-b02628506e5c', 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-11-13 16:33:19'),
('14a87190-c0a6-11f0-8af7-b02628506e5c', '580850091a22fb4dbe9023b759222b305282', '7f3c4dcc-af1a-11f0-a914-b02628506e5c', 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-11-13 16:33:19'),
('14a87cdc-c0a6-11f0-8af7-b02628506e5c', '580850091a22fb4dbe9023b759222b305282', '7f3c4e39-af1a-11f0-a914-b02628506e5c', 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-11-13 16:33:19'),
('14a884a0-c0a6-11f0-8af7-b02628506e5c', '580850091a22fb4dbe9023b759222b305282', '7f3c4e9e-af1a-11f0-a914-b02628506e5c', 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-11-13 16:33:19'),
('14a88f70-c0a6-11f0-8af7-b02628506e5c', '580850091a22fb4dbe9023b759222b305282', 'f9d42479-b182-11f0-b9ad-b02628506e5c', 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-11-13 16:33:19'),
('2f52f76d-db95-11f0-a4b6-b02628506e5c', 'f996adb8d578c9f15859cc9ffb78b023792b', '7f3c499a-af1a-11f0-a914-b02628506e5c', 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-12-17 23:10:23'),
('2f52ff1c-db95-11f0-a4b6-b02628506e5c', 'f996adb8d578c9f15859cc9ffb78b023792b', '7f3c4bb2-af1a-11f0-a914-b02628506e5c', 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-12-17 23:10:23'),
('2f530689-db95-11f0-a4b6-b02628506e5c', 'f996adb8d578c9f15859cc9ffb78b023792b', '7f3c4c76-af1a-11f0-a914-b02628506e5c', 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-12-17 23:10:23'),
('2f530daa-db95-11f0-a4b6-b02628506e5c', 'f996adb8d578c9f15859cc9ffb78b023792b', '7f3c4ceb-af1a-11f0-a914-b02628506e5c', 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-12-17 23:10:23'),
('2f531433-db95-11f0-a4b6-b02628506e5c', 'f996adb8d578c9f15859cc9ffb78b023792b', '7f3c4d5f-af1a-11f0-a914-b02628506e5c', 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-12-17 23:10:23'),
('2f531b8e-db95-11f0-a4b6-b02628506e5c', 'f996adb8d578c9f15859cc9ffb78b023792b', '7f3c4dcc-af1a-11f0-a914-b02628506e5c', 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-12-17 23:10:23'),
('2f5322e0-db95-11f0-a4b6-b02628506e5c', 'f996adb8d578c9f15859cc9ffb78b023792b', '7f3c4e39-af1a-11f0-a914-b02628506e5c', 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-12-17 23:10:23'),
('343068cb-e679-11f0-ada2-b02628506e5c', '7f072d6a-b507-11f0-936b-b02628506e5c', '7f3c4c76-af1a-11f0-a914-b02628506e5c', 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-12-31 19:47:48'),
('343070ee-e679-11f0-ada2-b02628506e5c', '7f072d6a-b507-11f0-936b-b02628506e5c', '7f3c4d5f-af1a-11f0-a914-b02628506e5c', 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-12-31 19:47:48'),
('45f96dcf-db95-11f0-a4b6-b02628506e5c', '7f0d55c4-b507-11f0-936b-b02628506e5c', '7f3c4bb2-af1a-11f0-a914-b02628506e5c', 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-12-17 23:11:01'),
('45f978c8-db95-11f0-a4b6-b02628506e5c', '7f0d55c4-b507-11f0-936b-b02628506e5c', '7f3c4c76-af1a-11f0-a914-b02628506e5c', 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-12-17 23:11:01'),
('45f97f28-db95-11f0-a4b6-b02628506e5c', '7f0d55c4-b507-11f0-936b-b02628506e5c', '7f3c4d5f-af1a-11f0-a914-b02628506e5c', 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-12-17 23:11:01'),
('45f98710-db95-11f0-a4b6-b02628506e5c', '7f0d55c4-b507-11f0-936b-b02628506e5c', 'f9d42479-b182-11f0-b9ad-b02628506e5c', 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-12-17 23:11:01'),
('d4c82597-db18-11f0-a4b6-b02628506e5c', '30ffdd6978f7086948c110585ae54954af7f', '7f3c4bb2-af1a-11f0-a914-b02628506e5c', 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-12-17 08:20:14'),
('d4c832f0-db18-11f0-a4b6-b02628506e5c', '30ffdd6978f7086948c110585ae54954af7f', '7f3c4ceb-af1a-11f0-a914-b02628506e5c', 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-12-17 08:20:14'),
('d4c83e74-db18-11f0-a4b6-b02628506e5c', '30ffdd6978f7086948c110585ae54954af7f', '7f3c4d5f-af1a-11f0-a914-b02628506e5c', 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-12-17 08:20:14'),
('fd6030ed-dddb-11f0-a864-b02628506e5c', '738e6ff077a74d602e68b898b6dfe805312e', '7f3c4bb2-af1a-11f0-a914-b02628506e5c', 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-12-20 20:42:16'),
('fd603cbd-dddb-11f0-a864-b02628506e5c', '738e6ff077a74d602e68b898b6dfe805312e', '7f3c4ceb-af1a-11f0-a914-b02628506e5c', 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-12-20 20:42:16');

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

--
-- Dataark for tabell `tenant_admin_sessions`
--

INSERT INTO `tenant_admin_sessions` (`id`, `tenant_admin_id`, `session_id`, `ip_address`, `user_agent`, `login_at`, `logout_at`, `last_activity`, `is_active`, `created_at`) VALUES
(42, '7f072d6a-b507-11f0-936b-b02628506e5c', '89cae0c5db68a4c0c7aa77a16339191f', '172.31.72.226', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Replit/1.0.14 Chrome/124.0.6367.119 Electron/30.0.3 Safari/537.36', '2025-10-29 21:42:30', '2025-12-02 18:28:44', '2025-12-02 18:28:44', 0, '2025-10-29 21:42:30'),
(43, '7f0d55c4-b507-11f0-936b-b02628506e5c', '9d411ff5a63aa211a27327c2e65dc746', '172.31.72.226', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Replit/1.0.14 Chrome/124.0.6367.119 Electron/30.0.3 Safari/537.36', '2025-10-29 21:44:04', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-10-29 21:44:04'),
(44, '7f0d55c4-b507-11f0-936b-b02628506e5c', '3bb2fd7e89fb56ea46d3ed4373487262', '172.31.72.226', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Replit/1.0.14 Chrome/124.0.6367.119 Electron/30.0.3 Safari/537.36', '2025-10-29 21:46:01', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-10-29 21:46:01'),
(45, '7f0d55c4-b507-11f0-936b-b02628506e5c', '64cbc142155264383e43c476dfd93494', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-29 21:49:02', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-10-29 21:49:02'),
(46, '7f072d6a-b507-11f0-936b-b02628506e5c', '9509193371ca6cdfc21f9519c5f5d786', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-29 21:59:12', '2025-12-02 18:28:44', '2025-12-02 18:28:44', 0, '2025-10-29 21:59:12'),
(47, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'dbc1de22b3dce112b94103ee30bccf2c', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-29 22:00:08', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-10-29 22:00:08'),
(48, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'ba0b53d22c059bba310f8b02c72dad66', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-29 22:00:30', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-10-29 22:00:30'),
(49, '7f0d55c4-b507-11f0-936b-b02628506e5c', '399eee371cc860487995d5b2f94ea214', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-29 22:00:58', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-10-29 22:00:58'),
(50, '7f0d55c4-b507-11f0-936b-b02628506e5c', '4441a6d084965546083d11d7df47fc96', '45.14.193.84', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-10-29 22:01:33', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-10-29 22:01:33'),
(51, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'e160cff552bb1218cd783ee674b5dbe5', '45.14.193.84', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-10-29 22:01:33', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-10-29 22:01:33'),
(52, '7f072d6a-b507-11f0-936b-b02628506e5c', 'c732c6f711ba1f1bbfacd869b5dd3696', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-29 22:01:33', '2025-12-02 18:28:44', '2025-12-02 18:28:44', 0, '2025-10-29 22:01:33'),
(53, '7f072d6a-b507-11f0-936b-b02628506e5c', '2b8200ca3c2d9906ec018bf5f2a90fec', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-29 22:01:33', '2025-12-02 18:28:44', '2025-12-02 18:28:44', 0, '2025-10-29 22:01:33'),
(54, '7f0d55c4-b507-11f0-936b-b02628506e5c', '1a9dd86bae99dd479764b11140a69d72', '45.14.193.84', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-10-29 22:01:34', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-10-29 22:01:34'),
(55, '7f0d55c4-b507-11f0-936b-b02628506e5c', '565a54dec87ff889bf87d9cccad54ca7', '45.14.193.84', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-10-29 22:01:34', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-10-29 22:01:34'),
(56, '7f072d6a-b507-11f0-936b-b02628506e5c', 'eb008bedb3d8a7db9621f8b426855d0a', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-29 22:01:34', '2025-12-02 18:28:44', '2025-12-02 18:28:44', 0, '2025-10-29 22:01:34'),
(58, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'ffef0d78d38fad4ddc130756dd50c91b', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-10-29 22:46:44', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-10-29 22:46:44'),
(60, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'f699412ae14533d235755c2d62bab8fb', '172.31.69.226', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 Replit-Bonsai/2.155.0 (iOS 18.5)', '2025-10-30 00:15:06', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-10-30 00:15:06'),
(61, '7f0d55c4-b507-11f0-936b-b02628506e5c', '4e8736ccaa98b3eb6074715fffb245f1', '172.31.90.194', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 Replit-Bonsai/2.155.0 (iOS 18.5)', '2025-10-30 07:41:02', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-10-30 07:41:02'),
(62, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'cc4dabf33cf73a3c27d7d9ae7a568690', '172.31.91.194', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Replit/1.0.14 Chrome/124.0.6367.119 Electron/30.0.3 Safari/537.36', '2025-10-30 08:26:33', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-10-30 08:26:33'),
(63, '7f0d55c4-b507-11f0-936b-b02628506e5c', '887d54e9fd767cf71a28c68d486c98c9', '172.31.91.194', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Replit/1.0.14 Chrome/124.0.6367.119 Electron/30.0.3 Safari/537.36', '2025-10-30 08:26:35', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-10-30 08:26:35'),
(64, '7f0d55c4-b507-11f0-936b-b02628506e5c', '1d4219200cec676871c2c20851c00b88', '172.31.91.194', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Replit/1.0.14 Chrome/124.0.6367.119 Electron/30.0.3 Safari/537.36', '2025-10-30 10:26:59', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-10-30 10:26:59'),
(68, '7f0d55c4-b507-11f0-936b-b02628506e5c', '32b88ccac13f8113cfece3c93f54e466', '172.31.91.194', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Replit/1.0.14 Chrome/124.0.6367.119 Electron/30.0.3 Safari/537.36', '2025-10-30 11:18:47', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-10-30 11:18:47'),
(69, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'abd3a5e4bceca9bb3f267fc734d44c4a', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-10-30 11:39:32', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-10-30 11:39:32'),
(70, '7f0d55c4-b507-11f0-936b-b02628506e5c', '6334c75576aab543ebcaad71d4b3f499', '172.31.91.194', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Replit/1.0.14 Chrome/124.0.6367.119 Electron/30.0.3 Safari/537.36', '2025-10-30 11:50:17', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-10-30 11:50:17'),
(73, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'fcdeed363da5260a36a613170bc2ee9e', '89.8.4.242', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-10-30 12:32:44', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-10-30 12:32:44'),
(74, '7f0d55c4-b507-11f0-936b-b02628506e5c', '6250376629fd34ad35c0c3a53e39b277', '89.8.4.242', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-10-30 15:52:12', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-10-30 15:52:12'),
(76, '7f0d55c4-b507-11f0-936b-b02628506e5c', '1d2d4277485edf836f77597079bdbd83', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-10-31 09:35:10', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-10-31 09:35:10'),
(78, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'd12ba9f7c0b5675fd7c0bbbe4a1f8a8e', '89.8.4.107', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-10-31 10:24:35', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-10-31 10:24:35'),
(80, '7f0d55c4-b507-11f0-936b-b02628506e5c', '8ba2807d2aa4153a00301d20ab7d56dd', '172.31.115.162', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 Replit-Bonsai/2.155.0 (iOS 18.5)', '2025-11-01 06:39:17', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-01 06:39:17'),
(83, '7f0d55c4-b507-11f0-936b-b02628506e5c', '3a52266f7a3e70a05bb32b0b305e41c1', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-11-01 07:58:58', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-01 07:58:58'),
(84, '7f0d55c4-b507-11f0-936b-b02628506e5c', '98e388c34b5bd0713303d5d69482c543', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-11-01 08:39:45', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-01 08:39:45'),
(85, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'd4c50d4489af1860d8b6fb880f27f673', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-11-01 09:18:34', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-01 09:18:34'),
(86, '7f072d6a-b507-11f0-936b-b02628506e5c', '56b1eda3965af4e4b4f866a622fe77a2', '172.31.68.162', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 Replit-Bonsai/2.155.0 (iOS 18.5)', '2025-11-01 09:27:05', '2025-12-02 18:28:44', '2025-12-02 18:28:44', 0, '2025-11-01 09:27:05'),
(88, '7f0d55c4-b507-11f0-936b-b02628506e5c', '257b5804f1aa8acaa91094ea28a37236', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-11-01 09:54:53', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-01 09:54:53'),
(89, '7f072d6a-b507-11f0-936b-b02628506e5c', '5cec30d623640388d369770c295daa05', '89.8.4.107', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-11-01 10:17:41', '2025-12-02 18:28:44', '2025-12-02 18:28:44', 0, '2025-11-01 10:17:41'),
(90, '7f0d55c4-b507-11f0-936b-b02628506e5c', '5ce31cece794f8c681c53c73c460134c', '89.8.4.107', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-11-01 10:39:57', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-01 10:39:57'),
(92, '7f0d55c4-b507-11f0-936b-b02628506e5c', '13064b1a7268964b2e6876e36424eed2', '89.8.4.107', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-11-01 13:26:11', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-01 13:26:11'),
(93, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'd2647326b3703d3522cc97e451f5ea25', '89.8.4.107', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-11-01 14:17:53', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-01 14:17:53'),
(95, '7f072d6a-b507-11f0-936b-b02628506e5c', '13f852e7316c1d5263e1bb2d158c9ca1', '45.88.221.253', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-11-01 15:56:35', '2025-12-02 18:28:44', '2025-12-02 18:28:44', 0, '2025-11-01 15:56:35'),
(96, '7f0d55c4-b507-11f0-936b-b02628506e5c', '80c4685f4a6dae223aed457709637798', '89.8.4.107', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-11-01 16:06:58', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-01 16:06:58'),
(97, '7f0d55c4-b507-11f0-936b-b02628506e5c', '7ddc39baa09c5c25d770a09de04ba733', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-11-01 19:01:02', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-01 19:01:02'),
(98, '7f072d6a-b507-11f0-936b-b02628506e5c', 'bdff4cb10dc635c532b7d13d67375040', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-11-01 20:15:32', '2025-12-02 18:28:44', '2025-12-02 18:28:44', 0, '2025-11-01 20:15:32'),
(99, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'd1fb67ec39c90415659d82378d4a6f62', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-01 22:49:14', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-01 22:49:14'),
(100, '7f0d55c4-b507-11f0-936b-b02628506e5c', '16301118a5ef6cfbb0541b7d425efb32', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-11-02 00:20:32', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-02 00:20:32'),
(102, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'fb058870e94dcb071910721cc4708d32', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-11-02 00:30:35', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-02 00:30:35'),
(103, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'c65764e76504d7758ccaa484f2de6d02', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-11-02 08:05:54', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-02 08:05:54'),
(106, '7f0d55c4-b507-11f0-936b-b02628506e5c', '23bc00b0725dd2863e062634ade41250', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-11-02 09:29:52', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-02 09:29:52'),
(107, '7f0d55c4-b507-11f0-936b-b02628506e5c', '1c3c7448c371ee15f4fb53768f1f18aa', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-02 14:10:42', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-02 14:10:42'),
(112, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'ba0485ac5198877ddab03155186ae03e', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-11-02 22:52:23', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-02 22:52:23'),
(121, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'f4e499179d1e7fa9c701ef7b4f083710', '89.8.5.185', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-11-03 15:35:06', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-03 15:35:06'),
(126, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'c9b5d56ed026669d56faa36223420f9c', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-11-04 05:36:25', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-04 05:36:25'),
(127, '7f072d6a-b507-11f0-936b-b02628506e5c', 'c710e82c19263ba43e1b3389865841cf', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-11-04 05:53:15', '2025-12-02 18:28:44', '2025-12-02 18:28:44', 0, '2025-11-04 05:53:15'),
(128, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'bef8814251fa85b505ae3b468594fa2c', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-11-04 07:54:13', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-04 07:54:13'),
(129, '7f072d6a-b507-11f0-936b-b02628506e5c', 'bb7a2d46f833dabcb6dc046e81fbeeca', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-04 08:24:58', '2025-12-02 18:28:44', '2025-12-02 18:28:44', 0, '2025-11-04 08:24:58'),
(130, '7f0d55c4-b507-11f0-936b-b02628506e5c', '115417dfe4042d0ced561d584c7c57b8', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-04 08:27:49', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-04 08:27:49'),
(131, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'bdb2c9204d215abe3e7eccdbb8b2efdd', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-04 08:32:06', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-04 08:32:06'),
(132, '7f072d6a-b507-11f0-936b-b02628506e5c', '49abebbbc5fba4c9e0bb73d84916b17a', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-04 09:04:23', '2025-12-02 18:28:44', '2025-12-02 18:28:44', 0, '2025-11-04 09:04:23'),
(133, '7f0d55c4-b507-11f0-936b-b02628506e5c', '207b1ca36f07c3e9014dd8a34a10755e', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-04 09:09:35', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-04 09:09:35'),
(134, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'f7f25dc31efcb3da69c3b89f527ebcbc', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-04 09:12:27', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-04 09:12:27'),
(135, '7f0d55c4-b507-11f0-936b-b02628506e5c', '1124a38204db4da9fa7e1b02dc1b9384', '62.92.137.147', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-11-04 10:06:45', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-04 10:06:45'),
(136, '7f0d55c4-b507-11f0-936b-b02628506e5c', '87f1db32b72773a5bb8fb37fece4fe50', '62.92.137.147', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-11-04 12:35:37', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-04 12:35:37'),
(138, '7f0d55c4-b507-11f0-936b-b02628506e5c', '08e4737137582a0f7bebb99fdd682ffd', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-04 16:48:52', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-04 16:48:52'),
(139, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'ef5b5cb09aecee842a9ace79569c2b76', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-04 19:42:09', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-04 19:42:09'),
(141, '7f0d55c4-b507-11f0-936b-b02628506e5c', '5530efafdf47288df82df7804758ad3a', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-05 22:20:40', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-05 22:20:40'),
(144, '7f0d55c4-b507-11f0-936b-b02628506e5c', '89614ecb76537e4b34a78e5c0803ca48', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-05 22:47:52', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-05 22:47:52'),
(145, '7f0d55c4-b507-11f0-936b-b02628506e5c', '3d9c3e8325dfe2e8e9a737778e7ba570', '172.31.106.194', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Replit/1.0.14 Chrome/124.0.6367.119 Electron/30.0.3 Safari/537.36', '2025-11-05 22:48:42', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-05 22:48:42'),
(146, '7f0d55c4-b507-11f0-936b-b02628506e5c', '3a8dd009449af5276d2577773211fc22', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-06 10:38:21', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-06 10:38:21'),
(151, '7f0d55c4-b507-11f0-936b-b02628506e5c', '6153542bee92e256b2e64231bf3d6a74', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-11-07 18:18:50', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-07 18:18:50'),
(152, '7f0d55c4-b507-11f0-936b-b02628506e5c', '3a02d0d40f04af1c4bf7a7c62bc02353', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-07 19:02:42', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-07 19:02:42'),
(153, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'b4e66499ced3e3a05f327ac454945470', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-11-07 19:37:55', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-07 19:37:55'),
(154, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'e0be00f4f9f7a51f19a277f436bc065a', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-11-07 20:39:56', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-07 20:39:56'),
(155, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'e9fd5967c796e3275dc590da623be39c', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-07 21:55:12', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-07 21:55:12'),
(156, '7f0d55c4-b507-11f0-936b-b02628506e5c', '273d61d28edff6be45e98006e8a88b3e', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-11-08 00:20:33', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-08 00:20:33'),
(157, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'ccef81466be65456a75c0009dac6e944', '172.31.93.98', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 Replit-Bonsai/2.155.0 (iOS 18.5)', '2025-11-08 00:51:49', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-08 00:51:49'),
(158, '7f0d55c4-b507-11f0-936b-b02628506e5c', '038d635f89c08bf1f5c6cc84cf745358', '172.31.93.98', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 Replit-Bonsai/2.155.0 (iOS 18.5)', '2025-11-08 06:23:21', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-08 06:23:21'),
(160, '7f0d55c4-b507-11f0-936b-b02628506e5c', '59d904ee8fc09e94b162030bcda8b0d4', '172.31.93.98', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36', '2025-11-08 08:39:05', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-08 08:39:05'),
(165, '7f0d55c4-b507-11f0-936b-b02628506e5c', '36f5ec553d31042b3355fc427f61b0f4', '172.31.83.130', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 Replit-Bonsai/2.155.0 (iOS 18.5)', '2025-11-08 15:50:02', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-08 15:50:02'),
(166, '7f0d55c4-b507-11f0-936b-b02628506e5c', '48d2ed93939e8f32747405640c033d50', '172.31.72.2', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 Replit-Bonsai/2.155.0 (iOS 18.5)', '2025-11-08 16:29:17', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-08 16:29:17'),
(168, '7f0d55c4-b507-11f0-936b-b02628506e5c', '0f3bf0a1a0f316b3be6d3b20a9a9feef', '172.31.93.98', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 Replit-Bonsai/2.155.0 (iOS 18.5)', '2025-11-08 17:33:13', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-08 17:33:13'),
(173, '7f0d55c4-b507-11f0-936b-b02628506e5c', '1ab6db04b17e97578aceb1d6d1338091', '89.8.222.211', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-11-09 17:23:40', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-09 17:23:40'),
(179, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'e8e3e8480dbf409c151908aca1ae70fb', '45.88.221.253', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-11-09 22:12:22', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-09 22:12:22'),
(180, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'e05838e23a2fbf04a3c2aba04ace179b', '45.88.221.253', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-11-09 23:15:20', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-09 23:15:20'),
(181, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'c98e0e8545dcc67f12feedd7cfeca570', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-13 08:19:40', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-13 08:19:40'),
(182, '580850091a22fb4dbe9023b759222b305282', '138b3ab79fbd3aee0110b74fd9b132bf', '62.92.137.147', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-11-13 16:32:31', '2025-11-13 18:00:27', '2025-11-13 18:00:27', 0, '2025-11-13 16:32:31'),
(183, '7f0d55c4-b507-11f0-936b-b02628506e5c', '0a679b162dcc7adb4a1d02fdc45efcba', '172.31.124.194', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 Replit-Bonsai/2.163.0 (iOS 18.5)', '2025-11-13 18:00:26', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-13 18:00:26'),
(184, '580850091a22fb4dbe9023b759222b305282', 'fbe1a7398f093597949d441694e68a4d', '172.31.76.98', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 Replit-Bonsai/2.163.0 (iOS 18.5)', '2025-11-13 18:50:32', '2025-11-13 21:17:55', '2025-11-13 21:17:55', 0, '2025-11-13 18:50:32'),
(185, '7f0d55c4-b507-11f0-936b-b02628506e5c', '346f7ac277701b2805eaef0b98c07a8c', '172.31.78.162', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Replit/1.0.14 Chrome/124.0.6367.119 Electron/30.0.3 Safari/537.36', '2025-11-13 21:17:54', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-13 21:17:54'),
(186, '580850091a22fb4dbe9023b759222b305282', 'a4ff152bb7bc9cecf85184191603a23d', '172.31.78.162', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Replit/1.0.14 Chrome/124.0.6367.119 Electron/30.0.3 Safari/537.36', '2025-11-13 21:57:28', '2025-11-13 22:54:53', '2025-11-13 22:54:53', 0, '2025-11-13 21:57:28'),
(187, '7f0d55c4-b507-11f0-936b-b02628506e5c', '461cbbac22dd216284a68cccd685d8e2', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-13 23:08:02', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-13 23:08:02'),
(188, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'c36f5f0d05e7f6a28e4c5f48c3086bed', '172.31.87.130', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Replit/1.0.14 Chrome/124.0.6367.119 Electron/30.0.3 Safari/537.36', '2025-11-13 23:23:58', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-13 23:23:58'),
(189, '7f072d6a-b507-11f0-936b-b02628506e5c', '19bb60085ea6834b4f353f7bdb279b1b', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-11-13 23:53:36', '2025-12-02 18:28:44', '2025-12-02 18:28:44', 0, '2025-11-13 23:53:36'),
(190, '7f0d55c4-b507-11f0-936b-b02628506e5c', '34ddc1ce8f709c60528588c39e31161e', '172.31.70.194', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 Replit-Bonsai/2.163.0 (iOS 18.5)', '2025-11-14 07:20:31', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-14 07:20:31'),
(191, '7f0d55c4-b507-11f0-936b-b02628506e5c', '1b9d322cb023dde88057ba4748c74623', '172.31.70.194', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 Replit-Bonsai/2.163.0 (iOS 18.5)', '2025-11-14 07:27:32', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-14 07:27:32'),
(192, '7f0d55c4-b507-11f0-936b-b02628506e5c', '027ceb6f766ab46702db529aff1c8552', '172.31.70.194', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 Replit-Bonsai/2.163.0 (iOS 18.5)', '2025-11-14 07:32:16', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-14 07:32:16'),
(193, '7f0d55c4-b507-11f0-936b-b02628506e5c', '744cc838c7d3a5d80570e1251a562086', '172.31.105.66', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 Replit-Bonsai/2.163.0 (iOS 18.5)', '2025-11-14 07:48:02', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-14 07:48:02'),
(194, '7f0d55c4-b507-11f0-936b-b02628506e5c', '8e51fae938c77b486c8f78a8f848734e', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-14 08:44:53', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-14 08:44:53'),
(195, '7f072d6a-b507-11f0-936b-b02628506e5c', '924b217ce36bffcb5fe9c296bb3a8ec3', '172.31.109.34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Replit/1.0.14 Chrome/124.0.6367.119 Electron/30.0.3 Safari/537.36', '2025-11-14 09:02:55', '2025-12-02 18:28:44', '2025-12-02 18:28:44', 0, '2025-11-14 09:02:55'),
(196, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'ca44aec54ec0e7754b9b9f104b98e10d', '172.31.109.34', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 Replit-Bonsai/2.163.0 (iOS 18.5)', '2025-11-14 10:23:16', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-14 10:23:16'),
(197, '7f0d55c4-b507-11f0-936b-b02628506e5c', '2e86191d0f5506372566e1a46188b297', '172.31.109.34', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 Replit-Bonsai/2.163.0 (iOS 18.5)', '2025-11-14 10:52:05', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-14 10:52:05'),
(198, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'a9442221e90b8831ac4335687ed7e414', '172.31.109.34', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 Replit-Bonsai/2.163.0 (iOS 18.5)', '2025-11-14 11:00:56', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-14 11:00:56'),
(199, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'be4b38830da2397387ac4eb0a7cee460', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-11-14 16:59:04', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-14 16:59:04'),
(200, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'eea5fe21ca074a5e2d8259a47043e609', '172.31.69.34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Replit/1.0.14 Chrome/124.0.6367.119 Electron/30.0.3 Safari/537.36', '2025-11-19 11:35:00', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-19 11:35:00'),
(202, '7f0d55c4-b507-11f0-936b-b02628506e5c', '555b46f03774479658304e60521f7b8f', '172.31.69.34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Replit/1.0.14 Chrome/124.0.6367.119 Electron/30.0.3 Safari/537.36', '2025-11-19 12:04:46', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-19 12:04:46'),
(203, '7f0d55c4-b507-11f0-936b-b02628506e5c', '261fc8f310764e6bdee67346d927cbea', '172.31.69.34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Replit/1.0.14 Chrome/124.0.6367.119 Electron/30.0.3 Safari/537.36', '2025-11-19 12:14:33', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-19 12:14:33'),
(205, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'c5569256ef37cb863d0ba34911e53543', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-19 12:46:51', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-19 12:46:51'),
(207, '7f0d55c4-b507-11f0-936b-b02628506e5c', '030f00e602a308900b5a07086f7f4d36', '172.31.69.34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Replit/1.0.14 Chrome/124.0.6367.119 Electron/30.0.3 Safari/537.36', '2025-11-19 22:06:06', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-19 22:06:06'),
(208, '7f0d55c4-b507-11f0-936b-b02628506e5c', '5db2231111389ad96ced2c25f64f6692', '172.31.69.34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Replit/1.0.14 Chrome/124.0.6367.119 Electron/30.0.3 Safari/537.36', '2025-11-19 22:09:38', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-19 22:09:38'),
(209, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'c1572f7ffe5c5799a02b8b0943c8fd0e', '172.31.69.34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Replit/1.0.14 Chrome/124.0.6367.119 Electron/30.0.3 Safari/537.36', '2025-11-19 22:55:07', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-19 22:55:07'),
(210, '7f072d6a-b507-11f0-936b-b02628506e5c', 'a76de957acc543c67f536a3df10db7bd', '172.31.69.34', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 Replit-Bonsai/2.163.0 (iOS 18.5)', '2025-11-19 23:00:31', '2025-12-02 18:28:44', '2025-12-02 18:28:44', 0, '2025-11-19 23:00:31'),
(211, '7f0d55c4-b507-11f0-936b-b02628506e5c', '95072e30a71b51f8bfdd43590c7ed691', '172.31.74.2', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 Replit-Bonsai/2.163.0 (iOS 18.5)', '2025-11-20 06:49:33', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-20 06:49:33'),
(212, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'a0a3ab6beaf3ae4f8b74cbc5a547a009', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-20 10:25:07', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-20 10:25:07'),
(213, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'd42156fde92fcb409ae14db9a02ca03a', '172.31.83.34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Replit/1.0.14 Chrome/124.0.6367.119 Electron/30.0.3 Safari/537.36', '2025-11-20 11:22:30', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-20 11:22:30'),
(214, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'fc3e219e40919d4f09c9360adfa76ebe', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-11-20 11:54:50', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-20 11:54:50'),
(229, '7f072d6a-b507-11f0-936b-b02628506e5c', 'f43c26ad100a2a67781fbd5e1d013e53', '172.31.76.226', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Replit/1.0.14 Chrome/124.0.6367.119 Electron/30.0.3 Safari/537.36', '2025-11-22 00:18:43', '2025-12-02 18:28:44', '2025-12-02 18:28:44', 0, '2025-11-22 00:18:43'),
(230, '7f0d55c4-b507-11f0-936b-b02628506e5c', '7f4b88d61dac4ce6651cebaa428bee48', '45.14.193.99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-11-22 00:40:03', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-22 00:40:03'),
(231, 'f996adb8d578c9f15859cc9ffb78b023792b', '4669859b7667a904700d3c1d4edf4773', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-22 00:43:13', '2026-01-20 00:06:13', '2026-01-20 00:06:13', 0, '2025-11-22 00:43:13'),
(232, '7f072d6a-b507-11f0-936b-b02628506e5c', '4b29d1ce0065a22aa989471ccfbd348b', '172.31.76.226', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Replit/1.0.14 Chrome/124.0.6367.119 Electron/30.0.3 Safari/537.36', '2025-11-22 00:53:35', '2025-12-02 18:28:44', '2025-12-02 18:28:44', 0, '2025-11-22 00:53:35'),
(233, 'f996adb8d578c9f15859cc9ffb78b023792b', '45a9207ae640ea8d0c06cfbfdc793fc7', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-11-22 12:15:29', '2026-01-20 00:06:13', '2026-01-20 00:06:13', 0, '2025-11-22 12:15:29'),
(234, '7f0d55c4-b507-11f0-936b-b02628506e5c', '7af09a07c4d3cca6b9a60f5201a9c8cf', '172.31.107.2', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 Replit-Bonsai/2.163.0 (iOS 18.5)', '2025-11-22 13:42:46', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-22 13:42:46'),
(235, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'bca9c1592e9834b12e8f96becd1cbe1b', '172.31.70.194', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Replit/1.0.14 Chrome/124.0.6367.119 Electron/30.0.3 Safari/537.36', '2025-11-22 15:09:00', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-22 15:09:00'),
(236, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'ba619b95002fbfdb0de7060253067dd5', '172.31.77.130', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Replit/1.0.14 Chrome/124.0.6367.119 Electron/30.0.3 Safari/537.36', '2025-11-22 20:58:11', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-22 20:58:11'),
(237, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'dc5f7739eec7bedbf9ebd9f6304ea996', '172.31.77.130', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Replit/1.0.14 Chrome/124.0.6367.119 Electron/30.0.3 Safari/537.36', '2025-11-22 22:36:10', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-22 22:36:10'),
(238, 'f996adb8d578c9f15859cc9ffb78b023792b', 'f14a6683a5fff2e89da57760935fc36c', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-11-22 23:34:46', '2026-01-20 00:06:13', '2026-01-20 00:06:13', 0, '2025-11-22 23:34:46'),
(239, 'f996adb8d578c9f15859cc9ffb78b023792b', '717a586c52a5dcf414e2057230cd961b', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-11-23 00:35:12', '2026-01-20 00:06:13', '2026-01-20 00:06:13', 0, '2025-11-23 00:35:12'),
(240, 'f996adb8d578c9f15859cc9ffb78b023792b', 'f2272f1492bf4e475252d07e8610d13f', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-23 01:12:52', '2026-01-20 00:06:13', '2026-01-20 00:06:13', 0, '2025-11-23 01:12:52'),
(241, '7f0d55c4-b507-11f0-936b-b02628506e5c', '8c48febe6d3585c3f8b0a0abce8bc74a', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-23 01:13:23', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-23 01:13:23'),
(242, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'f451ecd1a57c18652bd194abd01e7430', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-11-23 06:26:53', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-23 06:26:53'),
(243, 'f996adb8d578c9f15859cc9ffb78b023792b', '1771f7000e3b60d9c5d9119480979413', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-11-23 06:52:52', '2026-01-20 00:06:13', '2026-01-20 00:06:13', 0, '2025-11-23 06:52:52'),
(244, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'e6806ca7c599214ef0840c16151d12f4', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-11-23 07:16:42', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-23 07:16:42'),
(245, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'f6c17a339bbec68b4186fed7d4b0676f', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-11-23 08:07:59', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-23 08:07:59'),
(246, 'f996adb8d578c9f15859cc9ffb78b023792b', '751ba2f7a3663856e820a7cf338529fb', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-11-23 08:25:16', '2026-01-20 00:06:13', '2026-01-20 00:06:13', 0, '2025-11-23 08:25:16'),
(247, '7f0d55c4-b507-11f0-936b-b02628506e5c', '12089e6f9be272c5101b787e46f8ed6a', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-11-23 08:54:29', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-23 08:54:29'),
(248, '7f0d55c4-b507-11f0-936b-b02628506e5c', '8a6ca34dbc62f93d8ea38155b3d2e457', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-11-23 09:11:56', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-23 09:11:56'),
(249, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'aa4ce0713bda243224cad5281778cc21', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-11-23 11:18:15', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-23 11:18:15'),
(250, 'f996adb8d578c9f15859cc9ffb78b023792b', '9f89dfe43eb63195bef810abcbd9b365', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-23 11:26:18', '2026-01-20 00:06:13', '2026-01-20 00:06:13', 0, '2025-11-23 11:26:18'),
(251, '7f0d55c4-b507-11f0-936b-b02628506e5c', '850d0a97cb260c9040cc088235b63d8c', '172.31.72.66', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 Replit-Bonsai/2.163.0 (iOS 18.5)', '2025-11-23 12:24:52', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-23 12:24:52'),
(252, '7f0d55c4-b507-11f0-936b-b02628506e5c', '8052a50871b7fdcfd0a7b733c3d0cc7e', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-11-23 18:11:58', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-23 18:11:58'),
(253, '7f0d55c4-b507-11f0-936b-b02628506e5c', '09fd510cfb04900c33ea1a0044bfef68', '172.31.86.226', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 Replit-Bonsai/2.163.0 (iOS 18.5)', '2025-11-24 01:25:41', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-24 01:25:41'),
(254, 'f996adb8d578c9f15859cc9ffb78b023792b', '158319101752f87ba09269b202a27ed7', '172.31.80.130', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 Replit-Bonsai/2.163.0 (iOS 18.5)', '2025-11-24 01:48:27', '2026-01-20 00:06:13', '2026-01-20 00:06:13', 0, '2025-11-24 01:48:27'),
(255, '7f0d55c4-b507-11f0-936b-b02628506e5c', '9c1ad1a7c8469a2605a88c1a5d0845f0', '172.31.75.66', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 Replit-Bonsai/2.163.0 (iOS 18.5)', '2025-11-24 02:22:26', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-24 02:22:26'),
(256, '7f0d55c4-b507-11f0-936b-b02628506e5c', '414b15027fbf2b3e0cbc6cea4a0f30a9', '172.31.90.2', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 Replit-Bonsai/2.163.0 (iOS 18.5)', '2025-11-24 04:37:18', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-24 04:37:18'),
(257, '7f0d55c4-b507-11f0-936b-b02628506e5c', '23553128fe5d32883fe320f1f1b24963', '172.31.91.130', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 Replit-Bonsai/2.163.0 (iOS 18.5)', '2025-11-24 06:16:26', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-24 06:16:26'),
(258, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'c182a00f4f80a47bed8ef1afa2fa4722', '172.31.81.130', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 Replit-Bonsai/2.163.0 (iOS 18.5)', '2025-11-24 06:58:48', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-24 06:58:48'),
(259, '7f0d55c4-b507-11f0-936b-b02628506e5c', '1d2c09869bd4b102f51f7a833bf0bda8', '172.31.74.194', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 Replit-Bonsai/2.163.0 (iOS 18.5)', '2025-11-24 08:42:46', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-24 08:42:46'),
(260, '7f0d55c4-b507-11f0-936b-b02628506e5c', '1dc9ef8f2a2492771e04fa995ac73270', '172.31.70.130', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 Replit-Bonsai/2.163.0 (iOS 18.5)', '2025-11-24 16:30:49', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-24 16:30:49'),
(261, 'f996adb8d578c9f15859cc9ffb78b023792b', 'e70ac3a049a19812e7332657b93c9b8b', '172.31.70.34', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 Replit-Bonsai/2.163.0 (iOS 18.5)', '2025-11-24 17:31:17', '2026-01-20 00:06:13', '2026-01-20 00:06:13', 0, '2025-11-24 17:31:17'),
(262, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'e9ad89d28d41e43fe441b6d230c04690', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-11-24 20:26:20', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-24 20:26:20'),
(263, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'd34dd8dca6d8a237b04e913dcd385d25', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-24 21:30:30', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-24 21:30:30'),
(264, 'f996adb8d578c9f15859cc9ffb78b023792b', '7e7ad07b26603489f4e49d90a0e2aa13', '172.31.80.34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Replit/1.0.14 Chrome/124.0.6367.119 Electron/30.0.3 Safari/537.36', '2025-11-24 21:33:42', '2026-01-20 00:06:13', '2026-01-20 00:06:13', 0, '2025-11-24 21:33:42'),
(265, '7f072d6a-b507-11f0-936b-b02628506e5c', '205621eb8f1d87e4fd625aa2fd89e98a', '172.31.80.34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Replit/1.0.14 Chrome/124.0.6367.119 Electron/30.0.3 Safari/537.36', '2025-11-24 22:05:44', '2025-12-02 18:28:44', '2025-12-02 18:28:44', 0, '2025-11-24 22:05:44');
INSERT INTO `tenant_admin_sessions` (`id`, `tenant_admin_id`, `session_id`, `ip_address`, `user_agent`, `login_at`, `logout_at`, `last_activity`, `is_active`, `created_at`) VALUES
(266, '7f0d55c4-b507-11f0-936b-b02628506e5c', '32c168003c58c441eaabd9c0332e0cbe', '172.31.67.2', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Replit/1.0.14 Chrome/124.0.6367.119 Electron/30.0.3 Safari/537.36', '2025-11-26 10:38:22', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-26 10:38:22'),
(267, '7f072d6a-b507-11f0-936b-b02628506e5c', 'a731c23f2c3a54c75c507fb31437fe64', '172.31.67.2', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 Replit-Bonsai/2.165.3 (iOS 18.5)', '2025-11-26 13:45:48', '2025-12-02 18:28:44', '2025-12-02 18:28:44', 0, '2025-11-26 13:45:48'),
(268, 'f996adb8d578c9f15859cc9ffb78b023792b', '4aae9fb04c1fe2cb7bd514cc600be5af', '172.31.67.2', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 Replit-Bonsai/2.165.3 (iOS 18.5)', '2025-11-26 15:36:29', '2026-01-20 00:06:13', '2026-01-20 00:06:13', 0, '2025-11-26 15:36:29'),
(269, '7f0d55c4-b507-11f0-936b-b02628506e5c', '28c440a5c0c1540358c100128f957d04', '89.8.239.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-11-26 15:47:02', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-26 15:47:02'),
(270, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'c9fe8e817270df1637927351cbdeed35', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-11-26 17:42:21', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-26 17:42:21'),
(271, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'f16e5032555a3446547c7000181e8d0c', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-26 19:40:30', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-26 19:40:30'),
(272, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'f9ad859908521074f35cc3a01619e428', '172.31.66.98', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Replit/1.0.14 Chrome/124.0.6367.119 Electron/30.0.3 Safari/537.36', '2025-11-26 20:47:42', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-26 20:47:42'),
(273, '7f0d55c4-b507-11f0-936b-b02628506e5c', '63838359af1ce4087ace6c439218a2b9', '172.31.69.2', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Replit/1.0.14 Chrome/124.0.6367.119 Electron/30.0.3 Safari/537.36', '2025-11-26 21:37:39', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-26 21:37:39'),
(274, '7f0d55c4-b507-11f0-936b-b02628506e5c', '28408341335a159beefa4fdd7384eb57', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-11-26 23:08:18', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-26 23:08:18'),
(275, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'db212f5ccffd06611becfa2448f2385f', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-11-27 07:03:13', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-27 07:03:13'),
(276, '7f0d55c4-b507-11f0-936b-b02628506e5c', '80dd39429223ae9fc47c31d6514a9704', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-27 09:17:36', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-27 09:17:36'),
(277, '7f072d6a-b507-11f0-936b-b02628506e5c', '085235c727ff9df889e0c1aa56ae47b0', '172.31.69.162', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Replit/1.0.14 Chrome/124.0.6367.119 Electron/30.0.3 Safari/537.36', '2025-11-27 10:32:48', '2025-12-02 18:28:44', '2025-12-02 18:28:44', 0, '2025-11-27 10:32:48'),
(278, '7f0d55c4-b507-11f0-936b-b02628506e5c', '8158fab9463fa5915931403b031e6f77', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-27 11:29:01', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-27 11:29:01'),
(279, '7f0d55c4-b507-11f0-936b-b02628506e5c', '0d9b0d33b7ca3bb1ab3f5bfa120f87c4', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-27 12:36:05', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-27 12:36:05'),
(280, '7f072d6a-b507-11f0-936b-b02628506e5c', '3da03b0f7d099d0bf0b1469d5d945c30', '62.92.137.147', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-11-27 13:35:31', '2025-12-02 18:28:44', '2025-12-02 18:28:44', 0, '2025-11-27 13:35:31'),
(281, '7f0d55c4-b507-11f0-936b-b02628506e5c', '220b3c2ddf0d1a242910e92c76748771', '172.31.69.162', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 Replit-Bonsai/2.165.3 (iOS 18.5)', '2025-11-27 15:35:57', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-27 15:35:57'),
(282, '7f072d6a-b507-11f0-936b-b02628506e5c', 'd2f031e98743f5298ed4ccdc9565a7b8', '45.14.193.99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-27 15:47:45', '2025-12-02 18:28:44', '2025-12-02 18:28:44', 0, '2025-11-27 15:47:45'),
(283, '7f072d6a-b507-11f0-936b-b02628506e5c', '53001df6ac65851ba19612efe9d80620', '45.14.193.99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-27 19:44:27', '2025-12-02 18:28:44', '2025-12-02 18:28:44', 0, '2025-11-27 19:44:27'),
(284, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'd623f2b6d13d5391bd7f4f6f06b98881', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-11-27 20:53:02', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-27 20:53:02'),
(285, '7f072d6a-b507-11f0-936b-b02628506e5c', 'cd452db20bec482fb0b3a49e66b4488b', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-27 23:41:59', '2025-12-02 18:28:44', '2025-12-02 18:28:44', 0, '2025-11-27 23:41:59'),
(286, '7f072d6a-b507-11f0-936b-b02628506e5c', 'a82fe4f0d187d8dea6f4bd7451c0fce9', '45.14.193.99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-27 23:42:21', '2025-12-02 18:28:44', '2025-12-02 18:28:44', 0, '2025-11-27 23:42:21'),
(287, 'f996adb8d578c9f15859cc9ffb78b023792b', '935eae73d47598dd0ed231d41f8b05a3', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-27 23:42:26', '2026-01-20 00:06:13', '2026-01-20 00:06:13', 0, '2025-11-27 23:42:26'),
(288, '7f0d55c4-b507-11f0-936b-b02628506e5c', '59ef1b41175ee15cb3c786709a722899', '45.14.193.99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-27 23:51:02', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-27 23:51:02'),
(289, '7f072d6a-b507-11f0-936b-b02628506e5c', '6aa4e8be3e70769751b1f4a6fbf9a6c6', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-11-28 00:20:59', '2025-12-02 18:28:44', '2025-12-02 18:28:44', 0, '2025-11-28 00:20:59'),
(290, '7f0d55c4-b507-11f0-936b-b02628506e5c', '6e0da6e319899c76dcc29654100bf99d', '172.31.69.162', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Replit/1.0.14 Chrome/124.0.6367.119 Electron/30.0.3 Safari/537.36', '2025-11-28 00:33:59', '2025-11-28 00:51:27', '2025-11-28 00:51:27', 0, '2025-11-28 00:33:59'),
(291, '7f0d55c4-b507-11f0-936b-b02628506e5c', '949d50882bf05da157d065f0f80be2d5', '45.14.193.99', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-28 00:51:29', '2025-11-28 09:44:19', '2025-11-28 09:44:19', 0, '2025-11-28 00:51:29'),
(292, 'f996adb8d578c9f15859cc9ffb78b023792b', '4b659057ef7bc3649f0fa3dc1fc8a380', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-11-28 00:53:51', '2026-01-20 00:06:13', '2026-01-20 00:06:13', 0, '2025-11-28 00:53:51'),
(293, '7f072d6a-b507-11f0-936b-b02628506e5c', '035e7c64b54965df2ddeeebfb9de45d7', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-28 01:07:11', '2025-12-02 18:28:44', '2025-12-02 18:28:44', 0, '2025-11-28 01:07:11'),
(294, 'f996adb8d578c9f15859cc9ffb78b023792b', '46852177fcbc98a1fa118727f162938b', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-28 16:51:58', '2026-01-20 00:06:13', '2026-01-20 00:06:13', 0, '2025-11-28 16:51:58'),
(295, 'f996adb8d578c9f15859cc9ffb78b023792b', 'cfe898ae3f8b1aa9f5dd08f47fe2fc63', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 OPR/123.0.0.0', '2025-11-28 18:41:04', '2026-01-20 00:06:13', '2026-01-20 00:06:13', 0, '2025-11-28 18:41:04'),
(296, '7f0d55c4-b507-11f0-936b-b02628506e5c', '2a639295b79a0408f2231ea7155d2399', '172.31.98.98', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 Replit-Bonsai/2.165.3 (iOS 18.5)', '2025-11-29 07:28:56', '2025-11-29 09:02:27', '2025-11-29 09:02:27', 0, '2025-11-29 07:28:56'),
(297, '7f072d6a-b507-11f0-936b-b02628506e5c', '0450d96157bcf48ccc916af6b875b585', '172.31.98.98', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Replit/1.0.14 Chrome/124.0.6367.119 Electron/30.0.3 Safari/537.36', '2025-11-29 08:09:08', '2025-12-02 18:28:44', '2025-12-02 18:28:44', 0, '2025-11-29 08:09:08'),
(298, 'f996adb8d578c9f15859cc9ffb78b023792b', '8a292be9e211fde2cc3a02823fab8cb4', '89.8.238.40', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-11-29 09:02:27', '2026-01-20 00:06:13', '2026-01-20 00:06:13', 0, '2025-11-29 09:02:27'),
(299, '7f0d55c4-b507-11f0-936b-b02628506e5c', '7e57ab08a4257f5961fa07a976138fe2', '172.31.67.162', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 Replit-Bonsai/2.165.3 (iOS 18.5)', '2025-11-29 09:29:20', '2025-11-30 16:39:46', '2025-11-30 16:39:46', 0, '2025-11-29 09:29:20'),
(300, 'f996adb8d578c9f15859cc9ffb78b023792b', '234933ab562bb8d005efbee67ef2e2f5', '89.8.238.159', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-11-30 16:39:46', '2026-01-20 00:06:13', '2026-01-20 00:06:13', 0, '2025-11-30 16:39:46'),
(301, '7f0d55c4-b507-11f0-936b-b02628506e5c', '1086c576fd82bc3d024de1c77f9f14b5', '172.31.99.98', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 Replit-Bonsai/2.165.3 (iOS 18.5)', '2025-12-01 19:18:23', '2025-12-01 20:38:25', '2025-12-01 20:38:25', 0, '2025-12-01 19:18:23'),
(302, '7f0d55c4-b507-11f0-936b-b02628506e5c', '720e16370c888694024af575bcade503', '172.31.84.194', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 Replit-Bonsai/2.165.3 (iOS 18.5)', '2025-12-01 20:38:26', '2025-12-01 22:42:19', '2025-12-01 22:42:19', 0, '2025-12-01 20:38:26'),
(303, '7f0d55c4-b507-11f0-936b-b02628506e5c', '7edf5cfe499fbb984f17a3f724924ce3', '172.31.91.98', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-01 22:42:20', '2025-12-02 00:56:28', '2025-12-02 00:56:28', 0, '2025-12-01 22:42:20'),
(304, 'f996adb8d578c9f15859cc9ffb78b023792b', '4574204c59cc0b7025bdbc495e605511', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-12-02 00:56:28', '2026-01-20 00:06:13', '2026-01-20 00:06:13', 0, '2025-12-02 00:56:28'),
(305, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'a2d5f1fb7e9f79173d319a80877895c3', '172.31.93.226', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Replit/1.0.14 Chrome/124.0.6367.119 Electron/30.0.3 Safari/537.36', '2025-12-02 01:17:32', '2025-12-02 01:24:40', '2025-12-02 01:24:40', 0, '2025-12-02 01:17:32'),
(306, 'f996adb8d578c9f15859cc9ffb78b023792b', '5a01d93fed0237badf2aff3dae5f25de', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-12-02 06:58:51', '2026-01-20 00:06:13', '2026-01-20 00:06:13', 0, '2025-12-02 06:58:51'),
(307, '7f0d55c4-b507-11f0-936b-b02628506e5c', '0da8eec3685d5e63d01f9dcc9a11bcd8', '172.31.67.34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-02 17:37:50', '2025-12-02 18:37:51', '2025-12-02 18:37:51', 0, '2025-12-02 17:37:50'),
(308, 'f996adb8d578c9f15859cc9ffb78b023792b', '8f41f505d62f792c2f92d401ebbff6c9', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-02 18:01:55', '2026-01-20 00:06:13', '2026-01-20 00:06:13', 0, '2025-12-02 18:01:55'),
(309, '7f072d6a-b507-11f0-936b-b02628506e5c', '2eee6a749eb7d4b5fe4e6e87faef8492', '172.31.76.130', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-02 18:17:12', '2025-12-02 18:28:44', '2025-12-02 18:28:44', 0, '2025-12-02 18:17:12'),
(310, '7f072d6a-b507-11f0-936b-b02628506e5c', '05309c35b0b1b33baab64304eb30414b', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-02 18:29:03', '2025-12-02 18:33:06', '2025-12-02 18:33:06', 0, '2025-12-02 18:29:03'),
(311, 'f996adb8d578c9f15859cc9ffb78b023792b', '3b61ca034c2f3d78fbc44e4b9a4ea180', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-02 18:33:26', '2026-01-20 00:06:13', '2026-01-20 00:06:13', 0, '2025-12-02 18:33:26'),
(312, '7f072d6a-b507-11f0-936b-b02628506e5c', '63dd5012e608307211454888e39dccd9', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-02 18:35:03', '2025-12-03 07:08:36', '2025-12-03 07:08:36', 0, '2025-12-02 18:35:03'),
(313, '7f0d55c4-b507-11f0-936b-b02628506e5c', '327d69bcfb55654ae722bcdf950e0df9', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-02 18:59:27', '2025-12-03 07:08:36', '2025-12-03 07:08:36', 0, '2025-12-02 18:59:27'),
(314, '7f0d55c4-b507-11f0-936b-b02628506e5c', '8450ffe02063b93fd67ba9387cb2fc6e', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-03 07:08:36', '2025-12-03 07:45:09', '2025-12-03 07:45:09', 0, '2025-12-03 07:08:36'),
(315, '7f072d6a-b507-11f0-936b-b02628506e5c', 'c1f80a621c733c5dbb657754f00aa72a', '172.31.99.66', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Replit/1.0.14 Chrome/124.0.6367.119 Electron/30.0.3 Safari/537.36', '2025-12-03 07:09:39', '2025-12-03 07:33:46', '2025-12-03 07:33:46', 0, '2025-12-03 07:09:39'),
(316, 'f996adb8d578c9f15859cc9ffb78b023792b', '7dcb8f84092ad115f10428303884f83e', '172.31.99.66', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Replit/1.0.14 Chrome/124.0.6367.119 Electron/30.0.3 Safari/537.36', '2025-12-03 07:34:03', '2026-01-20 00:06:13', '2026-01-20 00:06:13', 0, '2025-12-03 07:34:03'),
(317, 'f996adb8d578c9f15859cc9ffb78b023792b', '8a0645e1c6ec0fb51bffc63e621a32b5', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-12-03 09:01:22', '2026-01-20 00:06:13', '2026-01-20 00:06:13', 0, '2025-12-03 09:01:22'),
(318, 'f996adb8d578c9f15859cc9ffb78b023792b', '6df6788915f18c768ffd7c18f2fae9c3', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-03 09:02:52', '2026-01-20 00:06:13', '2026-01-20 00:06:13', 0, '2025-12-03 09:02:52'),
(319, '7f0d55c4-b507-11f0-936b-b02628506e5c', '9884e2c67c69e0c808c44702f1050960', '45.14.193.157', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-12-03 09:04:06', '2025-12-03 09:36:28', '2025-12-03 09:36:28', 0, '2025-12-03 09:04:06'),
(320, 'f996adb8d578c9f15859cc9ffb78b023792b', '3c95c70cd6beacad8e827ff11e27c3a6', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-03 09:23:37', '2026-01-20 00:06:13', '2026-01-20 00:06:13', 0, '2025-12-03 09:23:37'),
(321, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'beb9f440ba826d2e60d2f943e1a81626', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-12-03 09:49:15', '2025-12-03 11:23:17', '2025-12-03 11:23:17', 0, '2025-12-03 09:49:15'),
(322, '7f0d55c4-b507-11f0-936b-b02628506e5c', '6b1b956a10e9fd9bfbb44165a735df9d', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-03 11:23:17', '2025-12-03 12:51:13', '2025-12-03 12:51:13', 0, '2025-12-03 11:23:17'),
(323, 'f996adb8d578c9f15859cc9ffb78b023792b', 'c78a55bcc15c3bbf532994f88fff510a', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-12-03 11:36:45', '2026-01-20 00:06:13', '2026-01-20 00:06:13', 0, '2025-12-03 11:36:45'),
(324, '7f0d55c4-b507-11f0-936b-b02628506e5c', '56665c0825c6531a30120419cf363c5d', '46.46.232.50', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-12-03 12:51:13', '2025-12-03 12:56:10', '2025-12-03 12:56:10', 0, '2025-12-03 12:51:13'),
(325, 'f996adb8d578c9f15859cc9ffb78b023792b', '5816b8aba9688af63274dbf3b724d92f', '89.8.233.53', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-12-03 13:57:32', '2026-01-20 00:06:13', '2026-01-20 00:06:13', 0, '2025-12-03 13:57:32'),
(326, 'f996adb8d578c9f15859cc9ffb78b023792b', '312bc5195b0865ff9c16a6c3f0fc0881', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-12-03 18:56:29', '2026-01-20 00:06:13', '2026-01-20 00:06:13', 0, '2025-12-03 18:56:29'),
(327, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'c7e48b344493348a25d8e2d6b505427a', '172.31.68.2', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 Replit-Bonsai/2.165.3 (iOS 18.5)', '2025-12-03 19:11:45', '2025-12-03 20:21:04', '2025-12-03 20:21:04', 0, '2025-12-03 19:11:45'),
(328, 'f996adb8d578c9f15859cc9ffb78b023792b', 'd0331727834d6724e1d5d4455691482a', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-12-03 20:21:04', '2026-01-20 00:06:13', '2026-01-20 00:06:13', 0, '2025-12-03 20:21:04'),
(329, 'f996adb8d578c9f15859cc9ffb78b023792b', 'cc7a1ddcda0ab90df964ad3270fa6243', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-12-03 21:01:36', '2026-01-20 00:06:13', '2026-01-20 00:06:13', 0, '2025-12-03 21:01:36'),
(330, 'f996adb8d578c9f15859cc9ffb78b023792b', 'def6e5f487a2f4dad5936829aab1f71f', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-03 22:47:06', '2026-01-20 00:06:13', '2026-01-20 00:06:13', 0, '2025-12-03 22:47:06'),
(331, '7f0d55c4-b507-11f0-936b-b02628506e5c', '2f0e15a67fb1454d2a661b40408493fc', '45.84.39.51', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-12-03 23:02:34', '2025-12-04 00:05:48', '2025-12-04 00:05:48', 0, '2025-12-03 23:02:34'),
(332, 'f996adb8d578c9f15859cc9ffb78b023792b', '00ac368fc06faa7845ba7e204029cb3f', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-12-04 00:05:48', '2026-01-20 00:06:13', '2026-01-20 00:06:13', 0, '2025-12-04 00:05:48'),
(333, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'c651e6a5bc41d495e8facccc18b49b8e', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-12-04 00:20:43', '2025-12-04 06:43:44', '2025-12-04 06:43:44', 0, '2025-12-04 00:20:43'),
(334, 'f996adb8d578c9f15859cc9ffb78b023792b', '55ecbcc4df865708553487ee38e20a8e', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-12-04 00:41:13', '2026-01-20 00:06:13', '2026-01-20 00:06:13', 0, '2025-12-04 00:41:13'),
(335, 'f996adb8d578c9f15859cc9ffb78b023792b', '0ca301dbe5c0c11a7963bceff09843f0', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-04 06:43:44', '2026-01-20 00:06:13', '2026-01-20 00:06:13', 0, '2025-12-04 06:43:44'),
(336, '7f0d55c4-b507-11f0-936b-b02628506e5c', '785d430b0d4d6342b0bea667d0437026', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-12-04 07:23:28', '2025-12-04 08:16:18', '2025-12-04 08:16:18', 0, '2025-12-04 07:23:28'),
(337, 'f996adb8d578c9f15859cc9ffb78b023792b', '06dda295fc85397b340c46ce5d7f988e', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-12-04 07:40:36', '2026-01-20 00:06:13', '2026-01-20 00:06:13', 0, '2025-12-04 07:40:36'),
(338, 'f996adb8d578c9f15859cc9ffb78b023792b', 'a91bc7417601d5cad8db6b65c358783b', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-04 08:16:18', '2026-01-20 00:06:13', '2026-01-20 00:06:13', 0, '2025-12-04 08:16:18'),
(339, 'f996adb8d578c9f15859cc9ffb78b023792b', '7cd8ace2852b80d7fb66d1cacfafbb1e', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-04 08:41:47', '2026-01-20 00:06:13', '2026-01-20 00:06:13', 0, '2025-12-04 08:41:47'),
(340, '7f0d55c4-b507-11f0-936b-b02628506e5c', '227959d47ae18cb81032c78e46d65b4a', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-12-04 10:44:39', '2025-12-04 12:16:34', '2025-12-04 12:16:34', 0, '2025-12-04 10:44:39'),
(341, 'f996adb8d578c9f15859cc9ffb78b023792b', '75f356c0dcfd24465f7e3fa863f2b748', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-12-04 12:16:34', '2026-01-20 00:06:13', '2026-01-20 00:06:13', 0, '2025-12-04 12:16:34'),
(342, 'f996adb8d578c9f15859cc9ffb78b023792b', '992aff5d34ec6cd515facf984ba53e9c', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-05 08:49:31', '2026-01-20 00:06:13', '2026-01-20 00:06:13', 0, '2025-12-05 08:49:31'),
(343, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'a96defdbd95b7879ac79145bcd3cb36b', '45.84.39.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-12-06 10:17:26', '2025-12-06 10:18:10', '2025-12-06 10:18:10', 0, '2025-12-06 10:17:26'),
(344, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'c722038aff914b6a80cb94fc7d691cc9', '89.8.252.193', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-12-06 12:25:55', '2025-12-06 12:53:53', '2025-12-06 12:53:53', 0, '2025-12-06 12:25:55'),
(345, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'fbb9036485671041fa803f05c2878828', '195.181.132.108', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-12-06 12:54:45', '2025-12-06 13:59:43', '2025-12-06 13:59:43', 0, '2025-12-06 12:54:45'),
(346, '7f0d55c4-b507-11f0-936b-b02628506e5c', '6dc19f25647c08ae645246928f29b7d5', '45.84.39.51', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-12-06 13:59:43', '2025-12-06 14:34:28', '2025-12-06 14:34:28', 0, '2025-12-06 13:59:43'),
(347, 'f996adb8d578c9f15859cc9ffb78b023792b', '3b306538dc88a9499d6781cc5f62745e', '89.8.5.177', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-12-06 14:11:24', '2026-01-20 00:06:13', '2026-01-20 00:06:13', 0, '2025-12-06 14:11:24'),
(348, 'f996adb8d578c9f15859cc9ffb78b023792b', '07f26b00199ebcba603fff0c2f697838', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-12-08 23:57:12', '2026-01-20 00:06:13', '2026-01-20 00:06:13', 0, '2025-12-08 23:57:12'),
(349, 'f996adb8d578c9f15859cc9ffb78b023792b', 'da4c35ee483ab8a556fb4e7c025232a0', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-12-11 21:29:07', '2026-01-20 00:06:13', '2026-01-20 00:06:13', 0, '2025-12-11 21:29:07'),
(350, 'f996adb8d578c9f15859cc9ffb78b023792b', '29760e894535593d5846bb67d27d3049', '172.31.115.66', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Replit/1.0.14 Chrome/124.0.6367.119 Electron/30.0.3 Safari/537.36', '2025-12-11 21:59:42', '2026-01-20 00:06:13', '2026-01-20 00:06:13', 0, '2025-12-11 21:59:42'),
(351, '7f0d55c4-b507-11f0-936b-b02628506e5c', '0f4b7c75f46a9f7b121ff4c3d197cba0', '45.14.192.168', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-12-11 22:01:04', '2025-12-11 22:38:16', '2025-12-11 22:38:16', 0, '2025-12-11 22:01:04'),
(352, '7f072d6a-b507-11f0-936b-b02628506e5c', '34eae69a635f3077d327b73ab32a349b', '45.14.192.168', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-12-11 22:20:30', '2025-12-11 22:21:23', '2025-12-11 22:21:23', 0, '2025-12-11 22:20:30'),
(353, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'd13e54133a0e3cee3e297f98b660222a', '45.14.192.168', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-12-11 22:38:16', '2025-12-11 22:38:21', '2025-12-11 22:38:21', 0, '2025-12-11 22:38:16'),
(354, 'f996adb8d578c9f15859cc9ffb78b023792b', '390bbb6049518ccf5910745c77b3cde1', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-11 22:46:50', '2026-01-20 00:06:13', '2026-01-20 00:06:13', 0, '2025-12-11 22:46:50'),
(355, 'f996adb8d578c9f15859cc9ffb78b023792b', '5e0848f422c7bd29266c4b99344df287', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-12 21:41:52', '2026-01-20 00:06:13', '2026-01-20 00:06:13', 0, '2025-12-12 21:41:52'),
(356, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'a6813930c4634a282de89567bead65cb', '45.14.192.167', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-12-12 21:45:19', '2025-12-12 23:30:01', '2025-12-12 23:30:01', 0, '2025-12-12 21:45:19'),
(357, 'f996adb8d578c9f15859cc9ffb78b023792b', '9287628edcd428a70ec52bfde3f21a58', '172.31.94.66', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Replit/1.0.14 Chrome/124.0.6367.119 Electron/30.0.3 Safari/537.36', '2025-12-12 23:27:24', '2026-01-20 00:06:13', '2026-01-20 00:06:13', 0, '2025-12-12 23:27:24'),
(358, '7f0d55c4-b507-11f0-936b-b02628506e5c', '06d6acb7c0096d88ee39e81342742d63', '172.31.103.226', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 Replit-Bonsai/2.165.3 (iOS 18.5)', '2025-12-15 01:15:41', '2025-12-15 08:35:01', '2025-12-15 08:35:01', 0, '2025-12-15 01:15:41'),
(359, 'f996adb8d578c9f15859cc9ffb78b023792b', '3b6d7adf3beedde9688834e8fde570cc', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-12-15 01:59:18', '2026-01-20 00:06:13', '2026-01-20 00:06:13', 0, '2025-12-15 01:59:18'),
(360, '7f0d55c4-b507-11f0-936b-b02628506e5c', '3a226b946fc29600dde124cc449199fd', '94.156.150.109', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-12-15 08:35:02', '2025-12-15 09:20:57', '2025-12-15 09:20:57', 0, '2025-12-15 08:35:02'),
(361, 'f996adb8d578c9f15859cc9ffb78b023792b', '05843d93237b993dc6a60fff5d9f8c49', '172.31.102.226', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 Replit-Bonsai/2.165.3 (iOS 18.5)', '2025-12-15 09:20:56', '2026-01-20 00:06:13', '2026-01-20 00:06:13', 0, '2025-12-15 09:20:56'),
(362, 'f996adb8d578c9f15859cc9ffb78b023792b', '1c3208a552ba119c4ac51250949f3a4c', '172.31.95.98', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 Replit-Bonsai/2.165.3 (iOS 18.5)', '2025-12-15 10:43:29', '2026-01-20 00:06:13', '2026-01-20 00:06:13', 0, '2025-12-15 10:43:29'),
(363, 'f996adb8d578c9f15859cc9ffb78b023792b', '37a238cec5b0c70ec7a9a4d3d09841fa', '172.31.72.226', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 Replit-Bonsai/2.165.3 (iOS 18.5)', '2025-12-15 11:20:07', '2026-01-20 00:06:13', '2026-01-20 00:06:13', 0, '2025-12-15 11:20:07'),
(364, '7f0d55c4-b507-11f0-936b-b02628506e5c', '62f4eeef1cc950e178483dca69ea4086', '172.31.72.226', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 Replit-Bonsai/2.165.3 (iOS 18.5)', '2025-12-15 11:43:24', '2025-12-15 12:15:22', '2025-12-15 12:15:22', 0, '2025-12-15 11:43:24'),
(365, 'f996adb8d578c9f15859cc9ffb78b023792b', 'fc1a84915f417c1184cf8206ff6949f3', '172.31.81.66', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 Replit-Bonsai/2.165.3 (iOS 18.5)', '2025-12-15 12:15:21', '2026-01-20 00:06:13', '2026-01-20 00:06:13', 0, '2025-12-15 12:15:21'),
(366, 'f996adb8d578c9f15859cc9ffb78b023792b', 'b5d93d4feae108ba4b900d5e8d64711c', '172.31.67.34', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 Replit-Bonsai/2.165.3 (iOS 18.5)', '2025-12-15 15:04:25', '2026-01-20 00:06:13', '2026-01-20 00:06:13', 0, '2025-12-15 15:04:25'),
(367, 'f996adb8d578c9f15859cc9ffb78b023792b', '9a29f0617a08fca4a7c2a49532cd9edc', '172.31.69.66', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Replit/1.0.14 Chrome/124.0.6367.119 Electron/30.0.3 Safari/537.36', '2025-12-15 18:19:04', '2026-01-20 00:06:13', '2026-01-20 00:06:13', 0, '2025-12-15 18:19:04'),
(368, 'f996adb8d578c9f15859cc9ffb78b023792b', 'c0d725fbea74765178b8c9d813408273', '172.31.69.66', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Replit/1.0.14 Chrome/124.0.6367.119 Electron/30.0.3 Safari/537.36', '2025-12-15 18:27:43', '2026-01-20 00:06:13', '2026-01-20 00:06:13', 0, '2025-12-15 18:27:43'),
(369, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'f00b78505305b7a8724e7341690192cf', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-15 18:56:13', '2025-12-15 18:59:07', '2025-12-15 18:59:07', 0, '2025-12-15 18:56:13'),
(370, '7f0d55c4-b507-11f0-936b-b02628506e5c', '5405418c3b9858b495b25bcfa3ee49e9', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-15 18:59:08', '2025-12-15 21:17:03', '2025-12-15 21:17:03', 0, '2025-12-15 18:59:08'),
(371, 'f996adb8d578c9f15859cc9ffb78b023792b', 'ddfb0ffcc347c0ea61592888ea4c85d8', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-15 18:59:15', '2026-01-20 00:06:13', '2026-01-20 00:06:13', 0, '2025-12-15 18:59:15'),
(372, 'f996adb8d578c9f15859cc9ffb78b023792b', 'b1667b743def05602bf729c7ef35062e', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-15 19:05:46', '2026-01-20 00:06:13', '2026-01-20 00:06:13', 0, '2025-12-15 19:05:46'),
(373, 'f996adb8d578c9f15859cc9ffb78b023792b', '354689a304578e54f53e8e9de273fc0e', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-15 19:17:40', '2026-01-20 00:06:13', '2026-01-20 00:06:13', 0, '2025-12-15 19:17:40'),
(374, '7f0d55c4-b507-11f0-936b-b02628506e5c', '591d88e97c1207bf69b7fef36904dc7d', '45.14.192.138', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-12-15 21:17:03', '2025-12-15 22:28:10', '2025-12-15 22:28:10', 0, '2025-12-15 21:17:03'),
(375, 'f996adb8d578c9f15859cc9ffb78b023792b', '4bd8381e670572498d1cd36b774affdd', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-12-15 21:21:29', '2026-01-20 00:06:13', '2026-01-20 00:06:13', 0, '2025-12-15 21:21:29'),
(376, '7f072d6a-b507-11f0-936b-b02628506e5c', '295fa3089b2655014c80d2a646554605', '172.31.86.2', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 Replit-Bonsai/2.165.3 (iOS 18.5)', '2025-12-15 22:10:02', '2025-12-16 06:54:35', '2025-12-16 06:54:35', 0, '2025-12-15 22:10:02'),
(377, 'f996adb8d578c9f15859cc9ffb78b023792b', '88bea38b1c0ff7d65c1f3b99c41b7eac', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-12-16 06:54:32', '2026-01-20 00:06:13', '2026-01-20 00:06:13', 0, '2025-12-16 06:54:32'),
(378, 'f996adb8d578c9f15859cc9ffb78b023792b', '0763f025b144e65b71ac99154e951666', '172.31.78.130', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 Replit-Bonsai/2.165.3 (iOS 18.5)', '2025-12-16 09:21:04', '2026-01-20 00:06:13', '2026-01-20 00:06:13', 0, '2025-12-16 09:21:04'),
(379, '7f0d55c4-b507-11f0-936b-b02628506e5c', '3b46c5da5ff76fc40865554dee84b2e6', '172.31.78.130', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Replit/1.0.14 Chrome/124.0.6367.119 Electron/30.0.3 Safari/537.36', '2025-12-16 09:36:23', '2025-12-16 21:35:19', '2025-12-16 21:35:19', 0, '2025-12-16 09:36:23'),
(380, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'ec6aecf3014c7f4bb670b25f9b107fe7', '45.14.192.221', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-16 21:50:20', '2025-12-16 22:21:02', '2025-12-16 22:21:02', 0, '2025-12-16 21:50:20'),
(381, 'f996adb8d578c9f15859cc9ffb78b023792b', 'e1d1a5d2acb291f0ca98bc4c5bee3ae0', '172.31.68.34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Replit/1.0.14 Chrome/124.0.6367.119 Electron/30.0.3 Safari/537.36', '2025-12-16 22:03:12', '2026-01-20 00:06:13', '2026-01-20 00:06:13', 0, '2025-12-16 22:03:12'),
(382, '7f072d6a-b507-11f0-936b-b02628506e5c', '34c3a9327048d86fdca41d8a70d19d25', '45.14.192.221', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-16 22:09:45', '2025-12-16 22:29:30', '2025-12-16 22:29:30', 0, '2025-12-16 22:09:45'),
(383, 'f996adb8d578c9f15859cc9ffb78b023792b', '3e22f49765ceb316a1f5f7a7fc82dd22', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-16 22:59:59', '2026-01-20 00:06:13', '2026-01-20 00:06:13', 0, '2025-12-16 22:59:59'),
(384, 'f996adb8d578c9f15859cc9ffb78b023792b', '7bc8a7d8487d71d6fcc444677a74cb71', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-12-16 23:28:40', '2026-01-20 00:06:13', '2026-01-20 00:06:13', 0, '2025-12-16 23:28:40'),
(385, 'f996adb8d578c9f15859cc9ffb78b023792b', '6b590cfd37b980a67b96b29eec5957f4', '172.31.112.130', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 Replit-Bonsai/2.165.3 (iOS 18.5)', '2025-12-17 14:52:32', '2026-01-20 00:06:13', '2026-01-20 00:06:13', 0, '2025-12-17 14:52:32'),
(386, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'c6630d475104f4e41e779d0839146e74', '89.8.251.25', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-12-17 14:53:24', '2025-12-17 15:15:28', '2025-12-17 15:15:28', 0, '2025-12-17 14:53:24'),
(387, '30ffdd6978f7086948c110585ae54954af7f', '675cb83e65fc7f7e75fe52539f2011b8', '89.8.251.25', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-12-17 15:15:48', '2025-12-17 23:05:49', '2025-12-17 23:05:49', 0, '2025-12-17 15:15:48'),
(388, 'f996adb8d578c9f15859cc9ffb78b023792b', '7d5fbff88658af141e3f261b101fcefa', '172.31.118.194', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Replit/1.0.14 Chrome/124.0.6367.119 Electron/30.0.3 Safari/537.36', '2025-12-17 22:57:17', '2026-01-20 00:06:13', '2026-01-20 00:06:13', 0, '2025-12-17 22:57:17'),
(389, '7f072d6a-b507-11f0-936b-b02628506e5c', '37bddc23c9156a15997e1be9fbfa3f4c', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-17 23:25:00', '2025-12-18 07:10:45', '2025-12-18 07:10:45', 0, '2025-12-17 23:25:00'),
(390, 'f996adb8d578c9f15859cc9ffb78b023792b', '5103ea8d7cf772105aaa205c05d6ed80', '172.31.118.194', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 Replit-Bonsai/2.165.3 (iOS 18.5)', '2025-12-18 06:26:27', '2026-01-20 00:06:13', '2026-01-20 00:06:13', 0, '2025-12-18 06:26:27'),
(391, '7f072d6a-b507-11f0-936b-b02628506e5c', '9824afaa94bf202a4dc30d0df5b40d04', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-12-18 07:10:45', '2025-12-18 08:03:37', '2025-12-18 08:03:37', 0, '2025-12-18 07:10:45'),
(392, 'f996adb8d578c9f15859cc9ffb78b023792b', '89706f6f5a5a7ee6b97bf390da540c7d', '172.31.83.2', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Replit/1.0.14 Chrome/124.0.6367.119 Electron/30.0.3 Safari/537.36', '2025-12-18 09:18:30', '2026-01-20 00:06:13', '2026-01-20 00:06:13', 0, '2025-12-18 09:18:30'),
(393, '7f0d55c4-b507-11f0-936b-b02628506e5c', '5572f9f5ac2fa8efaf4dd9b0dcd22262', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-12-18 10:43:56', '2025-12-18 10:44:11', '2025-12-18 10:44:11', 0, '2025-12-18 10:43:56'),
(394, 'f996adb8d578c9f15859cc9ffb78b023792b', 'befabcb30d06523e8f9e2576a60594eb', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-12-18 10:44:14', '2026-01-20 00:06:13', '2026-01-20 00:06:13', 0, '2025-12-18 10:44:14'),
(395, 'f996adb8d578c9f15859cc9ffb78b023792b', 'b0239e51c575fe322050980969e699bd', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-18 12:01:22', '2026-01-20 00:06:13', '2026-01-20 00:06:13', 0, '2025-12-18 12:01:22'),
(396, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'd9f5a56c3fd22e76a32b8d22b8c03344', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-12-18 12:36:28', '2025-12-18 15:18:27', '2025-12-18 15:18:27', 0, '2025-12-18 12:36:28'),
(397, 'f996adb8d578c9f15859cc9ffb78b023792b', 'afeef7c8cdfc81f8779d79526623b7ed', '89.8.251.13', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-12-18 15:18:25', '2026-01-20 00:06:13', '2026-01-20 00:06:13', 0, '2025-12-18 15:18:25'),
(398, '738e6ff077a74d602e68b898b6dfe805312e', 'cd928cb692b341edb334631dcca8fc59', '89.8.251.13', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-12-18 15:38:26', '2025-12-22 06:54:20', '2025-12-22 06:54:20', 0, '2025-12-18 15:38:26'),
(399, '738e6ff077a74d602e68b898b6dfe805312e', 'bd8e74ff70c298df3abf1a243f70c9e5', '89.8.251.13', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-12-18 15:41:10', '2025-12-22 06:54:20', '2025-12-22 06:54:20', 0, '2025-12-18 15:41:10'),
(400, '738e6ff077a74d602e68b898b6dfe805312e', 'afe4d2dcfbf9fe5974ad7e5394720a12', '62.92.137.147', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-12-18 16:15:14', '2025-12-22 06:54:20', '2025-12-22 06:54:20', 0, '2025-12-18 16:15:14'),
(401, '738e6ff077a74d602e68b898b6dfe805312e', '7ca085f26f4c9693f5571836506b8a42', '62.92.137.147', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-12-18 17:43:23', '2025-12-22 06:54:20', '2025-12-22 06:54:20', 0, '2025-12-18 17:43:23'),
(402, '738e6ff077a74d602e68b898b6dfe805312e', '2af4e0b22ca7493bd274e20f548cd03a', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-18 23:46:13', '2025-12-22 06:54:20', '2025-12-22 06:54:20', 0, '2025-12-18 23:46:13'),
(403, '738e6ff077a74d602e68b898b6dfe805312e', '7910d19c3ba82bb2baeadd18b685487c', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-18 23:53:04', '2025-12-22 06:54:20', '2025-12-22 06:54:20', 0, '2025-12-18 23:53:04'),
(404, '738e6ff077a74d602e68b898b6dfe805312e', '9c7f77cad3b1644fa26350e20c637d76', '62.92.137.147', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-12-19 11:37:46', '2025-12-22 06:54:20', '2025-12-22 06:54:20', 0, '2025-12-19 11:37:46'),
(405, '738e6ff077a74d602e68b898b6dfe805312e', '77b6c797b85fab11791b75bae4bc5e1d', '89.8.251.146', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-12-19 16:14:48', '2025-12-22 06:54:20', '2025-12-22 06:54:20', 0, '2025-12-19 16:14:48'),
(406, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'ad56b7558c03d78b593655d1d0627785', '89.8.251.146', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-12-19 16:16:14', '2025-12-19 18:28:14', '2025-12-19 18:28:14', 0, '2025-12-19 16:16:14'),
(407, '738e6ff077a74d602e68b898b6dfe805312e', 'f571f7de0ced540ae1ef21bae637c830', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-12-19 18:28:11', '2025-12-22 06:54:20', '2025-12-22 06:54:20', 0, '2025-12-19 18:28:11'),
(408, '738e6ff077a74d602e68b898b6dfe805312e', 'b9638dba5556308a01841f125f071d44', '172.31.73.162', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 Replit-Bonsai/2.165.3 (iOS 18.5)', '2025-12-20 08:12:03', '2025-12-22 06:54:20', '2025-12-22 06:54:20', 0, '2025-12-20 08:12:03'),
(409, '7f0d55c4-b507-11f0-936b-b02628506e5c', '69cd58d4bdaad254f92b410d5ae8a903', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 14:18:36', '2025-12-20 15:43:22', '2025-12-20 15:43:22', 0, '2025-12-20 14:18:36'),
(410, '738e6ff077a74d602e68b898b6dfe805312e', '5c026387578d2780681dbdcb6bac9948', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-12-20 15:43:19', '2025-12-22 06:54:20', '2025-12-22 06:54:20', 0, '2025-12-20 15:43:19'),
(411, '7f0d55c4-b507-11f0-936b-b02628506e5c', '47cc3fc3ac33a127622318dba31e7560', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-12-20 15:44:23', '2025-12-20 16:18:01', '2025-12-20 16:18:01', 0, '2025-12-20 15:44:23'),
(412, '738e6ff077a74d602e68b898b6dfe805312e', 'b2a17b727eb8539dd62708ff7bc038b7', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-12-20 15:54:10', '2025-12-22 06:54:20', '2025-12-22 06:54:20', 0, '2025-12-20 15:54:10'),
(413, 'f996adb8d578c9f15859cc9ffb78b023792b', '5159c980bcf25a73469f20d0d0452d98', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-12-20 15:56:46', '2026-01-20 00:06:13', '2026-01-20 00:06:13', 0, '2025-12-20 15:56:46'),
(414, '7f0d55c4-b507-11f0-936b-b02628506e5c', '70bcdceee21a49a7faf722f3cf0c7700', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-12-20 16:18:01', '2025-12-20 20:41:00', '2025-12-20 20:41:00', 0, '2025-12-20 16:18:01'),
(415, '738e6ff077a74d602e68b898b6dfe805312e', '419e9a8827dafa09eef6d6f6fd1c2dc2', '147.12.42.159', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.1 Mobile/15E148 Safari/604.1', '2025-12-20 17:07:17', '2025-12-22 06:54:20', '2025-12-22 06:54:20', 0, '2025-12-20 17:07:17');
INSERT INTO `tenant_admin_sessions` (`id`, `tenant_admin_id`, `session_id`, `ip_address`, `user_agent`, `login_at`, `logout_at`, `last_activity`, `is_active`, `created_at`) VALUES
(416, '738e6ff077a74d602e68b898b6dfe805312e', '3531eacddeb1eb376fe10aa3855e343b', '147.12.42.159', 'Mozilla/5.0 (Linux; Android 16; Pixel 10 Pro XL Build/BP4A.251205.006.A4; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/143.0.7499.34 Mobile Safari/537.36 [FB_IAB/FB4A;FBAV/536.0.0.46.216;]', '2025-12-20 20:40:57', '2025-12-22 06:54:20', '2025-12-22 06:54:20', 0, '2025-12-20 20:40:57'),
(417, '738e6ff077a74d602e68b898b6dfe805312e', '524b295c32a4ded5e4c825d1e240946c', '147.12.42.159', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2025-12-20 20:41:39', '2025-12-22 06:54:20', '2025-12-22 06:54:20', 0, '2025-12-20 20:41:39'),
(418, '738e6ff077a74d602e68b898b6dfe805312e', '14181b01036e5eee4fa657d310d3656b', '147.12.42.159', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2025-12-20 20:42:44', '2025-12-22 06:54:20', '2025-12-22 06:54:20', 0, '2025-12-20 20:42:44'),
(419, '738e6ff077a74d602e68b898b6dfe805312e', 'a43aca4015a228cddcb1210f275a6077', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 22:21:35', '2025-12-22 06:54:20', '2025-12-22 06:54:20', 0, '2025-12-20 22:21:35'),
(420, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'ee8b73a0630e81c2077bc8d2aaec207d', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 22:22:52', '2025-12-20 22:53:48', '2025-12-20 22:53:48', 0, '2025-12-20 22:22:52'),
(421, '7f0d55c4-b507-11f0-936b-b02628506e5c', '4948cbe6e84eff3368755751f7000ad0', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 23:18:41', '2025-12-20 23:20:34', '2025-12-20 23:20:34', 0, '2025-12-20 23:18:41'),
(422, '738e6ff077a74d602e68b898b6dfe805312e', '47754f461a294d90fdab6010ff93498c', '147.12.42.159', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2025-12-20 23:19:23', '2025-12-22 06:54:20', '2025-12-22 06:54:20', 0, '2025-12-20 23:19:23'),
(423, '7f0d55c4-b507-11f0-936b-b02628506e5c', '7810dae3925280aa35600ed0bcb60419', '172.31.100.98', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Replit/1.0.14 Chrome/124.0.6367.119 Electron/30.0.3 Safari/537.36', '2025-12-20 23:24:30', '2025-12-20 23:49:38', '2025-12-20 23:49:38', 0, '2025-12-20 23:24:30'),
(424, '7f072d6a-b507-11f0-936b-b02628506e5c', 'cd75ee6c1b872c532b251bdfb4f51936', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 23:48:57', '2025-12-20 23:49:41', '2025-12-20 23:49:41', 0, '2025-12-20 23:48:57'),
(425, '7f0d55c4-b507-11f0-936b-b02628506e5c', '9ec940722904a714119eb6fde539ddb1', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-20 23:49:43', '2025-12-21 00:21:58', '2025-12-21 00:21:58', 0, '2025-12-20 23:49:43'),
(426, '738e6ff077a74d602e68b898b6dfe805312e', '9d1bbe7618e4bba0a64c84921ef24212', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2025-12-21 00:21:55', '2025-12-22 06:54:20', '2025-12-22 06:54:20', 0, '2025-12-21 00:21:55'),
(427, '7f0d55c4-b507-11f0-936b-b02628506e5c', '969a80985e415cf6603052ee4d9c25ae', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2025-12-21 05:06:47', '2025-12-21 06:10:55', '2025-12-21 06:10:55', 0, '2025-12-21 05:06:47'),
(428, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'c7f73d858471e6975cf07b2259aa3846', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2025-12-21 06:10:55', '2025-12-21 06:44:27', '2025-12-21 06:44:27', 0, '2025-12-21 06:10:55'),
(429, '7f0d55c4-b507-11f0-936b-b02628506e5c', '61cda7abd257cb5439a44d091aa8b22c', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2025-12-21 06:44:27', '2025-12-21 07:47:10', '2025-12-21 07:47:10', 0, '2025-12-21 06:44:27'),
(430, '738e6ff077a74d602e68b898b6dfe805312e', 'e87830f702bbda1b5bc71592b549c599', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-12-21 07:47:07', '2025-12-22 06:54:20', '2025-12-22 06:54:20', 0, '2025-12-21 07:47:07'),
(431, '738e6ff077a74d602e68b898b6dfe805312e', '69311ea22f485372a7892c3b84db6532', '172.31.94.2', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 Replit-Bonsai/2.165.3 (iOS 18.5)', '2025-12-21 08:40:58', '2025-12-22 06:54:20', '2025-12-22 06:54:20', 0, '2025-12-21 08:40:58'),
(432, '738e6ff077a74d602e68b898b6dfe805312e', '009dbae2369e43412d4e0a54b6f8ab0e', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-12-21 08:46:09', '2025-12-22 06:54:20', '2025-12-22 06:54:20', 0, '2025-12-21 08:46:09'),
(433, '738e6ff077a74d602e68b898b6dfe805312e', '7a6336a25c7a0bffb98802adeb4e26ca', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2025-12-21 14:35:08', '2025-12-22 06:54:20', '2025-12-22 06:54:20', 0, '2025-12-21 14:35:08'),
(434, '738e6ff077a74d602e68b898b6dfe805312e', '4f62166d774e7392b7fc6bc0502e4fc1', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2025-12-21 17:36:33', '2025-12-22 06:54:20', '2025-12-22 06:54:20', 0, '2025-12-21 17:36:33'),
(435, '738e6ff077a74d602e68b898b6dfe805312e', 'eb5cbafe3b3cdb3552ecde56f5e9d233', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-12-21 17:43:30', '2025-12-22 06:54:20', '2025-12-22 06:54:20', 0, '2025-12-21 17:43:30'),
(436, '738e6ff077a74d602e68b898b6dfe805312e', '1a4958fad293b47496b924c752446ed1', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-12-21 18:42:25', '2025-12-22 06:54:20', '2025-12-22 06:54:20', 0, '2025-12-21 18:42:25'),
(437, '738e6ff077a74d602e68b898b6dfe805312e', '6b33fd91fa789617069574508b17670f', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-12-21 21:05:43', '2025-12-22 06:54:20', '2025-12-22 06:54:20', 0, '2025-12-21 21:05:43'),
(438, '738e6ff077a74d602e68b898b6dfe805312e', 'd16b6486e64ddde005eb91fd90010b78', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2025-12-21 21:08:13', '2025-12-22 06:54:20', '2025-12-22 06:54:20', 0, '2025-12-21 21:08:13'),
(439, '738e6ff077a74d602e68b898b6dfe805312e', '512f496307ad449d4f42d19cd585be46', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-12-22 00:24:51', '2025-12-22 06:54:20', '2025-12-22 06:54:20', 0, '2025-12-22 00:24:51'),
(440, '738e6ff077a74d602e68b898b6dfe805312e', '4c8b158e97a09dc77af06abe584e56a3', '172.31.67.2', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Replit/1.0.14 Chrome/124.0.6367.119 Electron/30.0.3 Safari/537.36', '2025-12-22 06:34:49', '2025-12-22 06:54:20', '2025-12-22 06:54:20', 0, '2025-12-22 06:34:49'),
(441, '738e6ff077a74d602e68b898b6dfe805312e', 'e7f3b3a90f7b9e92be026969a2996516', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-12-22 06:54:26', '2025-12-22 15:39:14', '2025-12-22 15:39:14', 0, '2025-12-22 06:54:26'),
(442, '738e6ff077a74d602e68b898b6dfe805312e', 'be63a6261d857c453e592755b6bab2b1', '62.92.137.147', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-12-22 15:39:14', '2025-12-22 15:47:01', '2025-12-22 15:47:01', 0, '2025-12-22 15:39:14'),
(443, '738e6ff077a74d602e68b898b6dfe805312e', '08d16d394efbf3e232b65d1e1947c34b', '89.8.251.212', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-12-22 15:47:42', '2025-12-22 15:47:48', '2025-12-22 15:47:48', 0, '2025-12-22 15:47:42'),
(444, '738e6ff077a74d602e68b898b6dfe805312e', '0ff3d89e1fe6876df856b2221b0bf057', '89.8.251.212', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2025-12-22 16:34:05', '2025-12-23 01:27:28', '2025-12-23 01:27:28', 0, '2025-12-22 16:34:05'),
(445, '738e6ff077a74d602e68b898b6dfe805312e', '3135132af9425ad4eaad3f30d5122520', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-12-23 01:27:28', '2025-12-23 08:00:28', '2025-12-23 08:00:28', 0, '2025-12-23 01:27:28'),
(446, '7f072d6a-b507-11f0-936b-b02628506e5c', '8b998b9c6d35e52d33f03cd9206e771b', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2025-12-23 08:00:25', '2025-12-23 15:52:31', '2025-12-23 15:52:31', 0, '2025-12-23 08:00:25'),
(447, '7f0d55c4-b507-11f0-936b-b02628506e5c', '73f26726043ae3ec28eb7d50ec5c0282', '89.8.251.190', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2025-12-23 15:52:28', '2025-12-23 17:08:33', '2025-12-23 17:08:33', 0, '2025-12-23 15:52:28'),
(448, '738e6ff077a74d602e68b898b6dfe805312e', '386e7a22a22d451093770b4e56823d44', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2025-12-23 17:08:45', '2025-12-23 17:12:37', '2025-12-23 17:12:37', 0, '2025-12-23 17:08:45'),
(449, '7f0d55c4-b507-11f0-936b-b02628506e5c', '6d101b44109c1587bbe50cd464131e48', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2025-12-23 20:48:29', '2025-12-24 04:50:29', '2025-12-24 04:50:29', 0, '2025-12-23 20:48:29'),
(450, '7f0d55c4-b507-11f0-936b-b02628506e5c', '54ba6380c5571eb63e22c0e7b022a809', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-12-24 04:50:29', '2025-12-24 12:30:58', '2025-12-24 12:30:58', 0, '2025-12-24 04:50:29'),
(451, '7f0d55c4-b507-11f0-936b-b02628506e5c', '7187159949b05faf779d9258ed698861', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-24 12:30:58', '2025-12-24 12:44:59', '2025-12-24 12:44:59', 0, '2025-12-24 12:30:58'),
(452, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'd8578271e9d56f397dcd7bffed7db5fc', '172.31.122.194', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Replit/1.0.14 Chrome/124.0.6367.119 Electron/30.0.3 Safari/537.36', '2025-12-24 12:45:06', '2025-12-24 14:48:24', '2025-12-24 14:48:24', 0, '2025-12-24 12:45:06'),
(453, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'ade257c50d10ff4e99f42fa1f77d5dae', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-12-24 14:48:24', '2025-12-25 12:32:27', '2025-12-25 12:32:27', 0, '2025-12-24 14:48:24'),
(454, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'c6150a6d805ce7b7a493f08fee8c14bd', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-25 12:32:27', '2025-12-25 22:35:46', '2025-12-25 22:35:46', 0, '2025-12-25 12:32:27'),
(455, '7f072d6a-b507-11f0-936b-b02628506e5c', 'a6c126fd478bd5c419db26b040735058', '45.14.192.128', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-25 22:35:43', '2025-12-26 01:18:09', '2025-12-26 01:18:09', 0, '2025-12-25 22:35:43'),
(456, '7f0d55c4-b507-11f0-936b-b02628506e5c', '5cc78a12e064fb03ab21c08a593936ed', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-12-26 01:18:07', '2025-12-26 01:53:34', '2025-12-26 01:53:34', 0, '2025-12-26 01:18:07'),
(457, '7f0d55c4-b507-11f0-936b-b02628506e5c', '1cb435814c6e4dbd9cfe59ebbe9cac26', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-12-26 01:53:34', '2025-12-26 09:29:09', '2025-12-26 09:29:09', 0, '2025-12-26 01:53:34'),
(458, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'fa14fde16f2c0f25a9e607daaa12963a', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-26 09:29:09', '2025-12-26 10:16:00', '2025-12-26 10:16:00', 0, '2025-12-26 09:29:09'),
(459, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'de9043bde146310a4dbbfeac43c612fc', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-12-26 10:16:00', '2025-12-26 14:58:33', '2025-12-26 14:58:33', 0, '2025-12-26 10:16:00'),
(460, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'a91b0dd9ab50c633368a55b4a8bfa9a4', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-12-26 14:58:33', '2025-12-26 17:39:51', '2025-12-26 17:39:51', 0, '2025-12-26 14:58:33'),
(461, '7f0d55c4-b507-11f0-936b-b02628506e5c', '1c4fccc09a1641a90e5846e621872631', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-12-26 17:39:51', '2025-12-26 18:01:13', '2025-12-26 18:01:13', 0, '2025-12-26 17:39:51'),
(462, '7f0d55c4-b507-11f0-936b-b02628506e5c', '7b0ff540f1a3f971c2899bf83729748d', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-12-26 18:21:25', '2025-12-26 19:51:38', '2025-12-26 19:51:38', 0, '2025-12-26 18:21:25'),
(463, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'db09db1aa7154894fb00d16a98e0b950', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-12-26 19:51:38', '2025-12-26 20:29:46', '2025-12-26 20:29:46', 0, '2025-12-26 19:51:38'),
(464, '7f072d6a-b507-11f0-936b-b02628506e5c', '5c9cbae3830e81176f4457634d802844', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-26 20:05:47', '2025-12-26 21:04:26', '2025-12-26 21:04:26', 0, '2025-12-26 20:05:47'),
(465, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'c8cd1807ced00c04495f0cde14957c1d', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-12-26 20:29:46', '2025-12-26 21:04:24', '2025-12-26 21:04:24', 0, '2025-12-26 20:29:46'),
(466, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'dae4cec1e2de8aa9116fb1abc70f95d1', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-12-26 21:04:24', '2025-12-26 22:49:46', '2025-12-26 22:49:46', 0, '2025-12-26 21:04:24'),
(467, '7f0d55c4-b507-11f0-936b-b02628506e5c', '569f80ba067695ceb82372ff99b15d77', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-12-26 22:49:46', '2025-12-27 08:02:26', '2025-12-27 08:02:26', 0, '2025-12-26 22:49:46'),
(468, '7f0d55c4-b507-11f0-936b-b02628506e5c', '5e9128c06b93ab3b290d4d41e923b65b', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-12-27 08:02:26', '2025-12-27 16:57:44', '2025-12-27 16:57:44', 0, '2025-12-27 08:02:26'),
(469, '738e6ff077a74d602e68b898b6dfe805312e', '18b0e68099170f3348f407e35264c2d8', '147.12.42.159', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2025-12-27 16:57:41', '2025-12-28 00:06:23', '2025-12-28 00:06:23', 0, '2025-12-27 16:57:41'),
(470, '7f0d55c4-b507-11f0-936b-b02628506e5c', '708bd7c5fc89ed95e2d5f1bfc83d49f0', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 00:06:20', '2025-12-28 07:41:37', '2025-12-28 07:41:37', 0, '2025-12-28 00:06:20'),
(471, '7f072d6a-b507-11f0-936b-b02628506e5c', '66855b34aef4184c6fca8f039a1ebe9e', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-12-28 00:44:49', '2025-12-28 07:41:34', '2025-12-28 07:41:34', 0, '2025-12-28 00:44:49'),
(472, '7f072d6a-b507-11f0-936b-b02628506e5c', '1c54b96118fea0e6316fcb0225caf0e0', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-12-28 07:41:34', '2025-12-28 23:21:18', '2025-12-28 23:21:18', 0, '2025-12-28 07:41:34'),
(473, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'd48e8616ac6b382481a86870bcd89ec3', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-28 23:21:16', '2025-12-29 07:24:30', '2025-12-29 07:24:30', 0, '2025-12-28 23:21:16'),
(474, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'f150f201081398aa593520437c84f306', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2025-12-29 07:24:30', '2025-12-31 19:44:27', '2025-12-31 19:44:27', 0, '2025-12-29 07:24:30'),
(475, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'fd7700e15d06373d7cae06f68c2f499b', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-31 19:44:27', '2025-12-31 22:56:44', '2025-12-31 22:56:44', 0, '2025-12-31 19:44:27'),
(476, '7f0d55c4-b507-11f0-936b-b02628506e5c', '2ddda804e981b2ddc9c2f6b5e95d0cea', '45.88.221.124', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-01 18:00:52', '2026-01-01 21:43:04', '2026-01-01 21:43:04', 0, '2026-01-01 18:00:52'),
(477, '7f072d6a-b507-11f0-936b-b02628506e5c', '6e6632c87ee7d6230af21d93c91a6dd9', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-01 21:43:01', '2026-01-01 22:37:23', '2026-01-01 22:37:23', 0, '2026-01-01 21:43:01'),
(478, '7f0d55c4-b507-11f0-936b-b02628506e5c', '1e25b4ab6331abd51e6c993dadfcd80c', '45.88.221.125', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-01 21:47:10', '2026-01-02 23:22:48', '2026-01-02 23:22:48', 0, '2026-01-01 21:47:10'),
(479, 'f996adb8d578c9f15859cc9ffb78b023792b', '2adfc95d630fd9b114a054ad34df8b1f', '172.31.94.194', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Replit/1.0.14 Chrome/124.0.6367.119 Electron/30.0.3 Safari/537.36', '2026-01-01 22:37:15', '2026-01-20 00:06:13', '2026-01-20 00:06:13', 0, '2026-01-01 22:37:15'),
(480, '7f072d6a-b507-11f0-936b-b02628506e5c', '95262c3767c197cc280098b00ea7c81b', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-01 22:43:20', '2026-01-02 23:13:00', '2026-01-02 23:13:00', 0, '2026-01-01 22:43:20'),
(481, '7f072d6a-b507-11f0-936b-b02628506e5c', '483f8ac2b81a306979f3d8e6491150d8', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 23:22:45', '2026-01-03 00:19:06', '2026-01-03 00:19:06', 0, '2026-01-02 23:22:45'),
(482, '7f0d55c4-b507-11f0-936b-b02628506e5c', '85117721d125048ca3c738128aba6bd7', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2026-01-03 00:19:03', '2026-01-03 10:08:38', '2026-01-03 10:08:38', 0, '2026-01-03 00:19:03'),
(483, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'e8b1e44e0a2366654b565b5e38fb9d65', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-03 10:08:38', '2026-01-05 19:17:06', '2026-01-05 19:17:06', 0, '2026-01-03 10:08:38'),
(484, '7f0d55c4-b507-11f0-936b-b02628506e5c', '3998cd717b539444abdd55edca9dbe6c', '45.88.221.121', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-05 19:17:06', '2026-01-05 22:34:15', '2026-01-05 22:34:15', 0, '2026-01-05 19:17:06'),
(485, '7f072d6a-b507-11f0-936b-b02628506e5c', '61f00228ae6f77d7d2832a0fcea38be5', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-05 19:28:48', '2026-01-06 23:54:33', '2026-01-06 23:54:33', 0, '2026-01-05 19:28:48'),
(486, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'cd0f9c60b2441ae533ba20c9e89c91b8', '45.88.221.125', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-06 23:54:31', '2026-01-06 23:57:42', '2026-01-06 23:57:42', 0, '2026-01-06 23:54:31'),
(487, '7f072d6a-b507-11f0-936b-b02628506e5c', '09e3e33f7a35d53e61af16933927b374', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2026-01-07 07:40:26', '2026-01-07 19:58:03', '2026-01-07 19:58:03', 0, '2026-01-07 07:40:26'),
(488, 'f996adb8d578c9f15859cc9ffb78b023792b', '50594a3b0afe1f5793e36c2c1f8035db', '89.8.239.98', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.5 Mobile/15E148 Safari/604.1', '2026-01-07 19:58:01', '2026-01-20 00:06:13', '2026-01-20 00:06:13', 0, '2026-01-07 19:58:01'),
(489, '7f0d55c4-b507-11f0-936b-b02628506e5c', '8bcdc8a4d6d388dccaefc5a54f2d0f0d', '45.88.221.122', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-07 23:53:01', '2026-01-07 23:53:49', '2026-01-07 23:53:49', 0, '2026-01-07 23:53:01'),
(490, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'dacafc2ddb43a8d1908e30d14ba4ec02', '45.88.221.122', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-08 02:26:44', '2026-01-08 02:28:37', '2026-01-08 02:28:37', 0, '2026-01-08 02:26:44'),
(491, 'f996adb8d578c9f15859cc9ffb78b023792b', '9cb32378d376d47046bb55c329de68d2', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2026-01-08 23:02:50', '2026-01-20 00:06:13', '2026-01-20 00:06:13', 0, '2026-01-08 23:02:50'),
(492, '7f0d55c4-b507-11f0-936b-b02628506e5c', '6504017534602ada69cda367a6263e24', '45.88.221.122', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-09 23:30:08', '2026-01-10 09:45:45', '2026-01-10 09:45:45', 0, '2026-01-09 23:30:08'),
(493, '7f072d6a-b507-11f0-936b-b02628506e5c', '9a979053e221634e8d291cce768b4d90', '213.128.180.74', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.6 Safari/605.1.15', '2026-01-10 09:45:45', '2026-01-10 15:00:31', '2026-01-10 15:00:31', 0, '2026-01-10 09:45:45'),
(494, '7f072d6a-b507-11f0-936b-b02628506e5c', '75decb8489edba906e7d823424eb1bf2', '213.128.180.74', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.1 Mobile/15E148 Safari/604.1', '2026-01-10 15:00:31', '2026-01-10 15:32:03', '2026-01-10 15:32:03', 0, '2026-01-10 15:00:31'),
(495, '7f072d6a-b507-11f0-936b-b02628506e5c', '3dc5daec089c4fe4547fe944f3a77323', '213.128.180.74', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.6 Safari/605.1.15', '2026-01-10 15:32:03', '2026-01-10 15:58:11', '2026-01-10 15:58:11', 0, '2026-01-10 15:32:03'),
(496, '7f072d6a-b507-11f0-936b-b02628506e5c', '5c1f7b66378df7be07b2dcb0e26d9dad', '213.128.180.74', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.1 Mobile/15E148 Safari/604.1', '2026-01-10 15:59:02', '2026-01-10 16:09:37', '2026-01-10 16:09:37', 0, '2026-01-10 15:59:02'),
(497, '7f072d6a-b507-11f0-936b-b02628506e5c', '3a535880d8d942eb5f9d7ce2873b53f3', '213.128.180.74', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.6 Safari/605.1.15', '2026-01-10 16:10:00', '2026-01-10 16:14:27', '2026-01-10 16:14:27', 0, '2026-01-10 16:10:00'),
(498, '7f072d6a-b507-11f0-936b-b02628506e5c', '376f0b52b63f7bd8cbc35d2061fc2ac8', '213.128.180.74', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.1 Mobile/15E148 Safari/604.1', '2026-01-10 16:14:39', '2026-01-10 16:15:44', '2026-01-10 16:15:44', 0, '2026-01-10 16:14:39'),
(499, '7f072d6a-b507-11f0-936b-b02628506e5c', 'adcf786b342d748a7a6d38d24f471838', '213.128.180.74', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.6 Safari/605.1.15', '2026-01-10 16:17:42', '2026-01-10 19:50:25', '2026-01-10 19:50:25', 0, '2026-01-10 16:17:42'),
(500, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'b477e464c1e61bdb2118c011102fcb8b', '45.14.192.158', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-10 19:50:22', '2026-01-10 20:38:37', '2026-01-10 20:38:37', 0, '2026-01-10 19:50:22'),
(501, '7f0d55c4-b507-11f0-936b-b02628506e5c', '5e45d68c537a4b9983eba1a9e2a22cc1', '45.14.192.157', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-11 01:05:09', '2026-01-11 01:17:26', '2026-01-11 01:17:26', 0, '2026-01-11 01:05:09'),
(502, '7f072d6a-b507-11f0-936b-b02628506e5c', '77bf34d0e209cc53e51bcfb23d60a185', '2a02:2121:819:c242:8c1e:c3e0:96a2:3c46', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.1 Mobile/15E148 Safari/604.1', '2026-01-11 13:13:06', '2026-01-12 10:45:31', '2026-01-12 10:45:31', 0, '2026-01-11 13:13:06'),
(503, '7f072d6a-b507-11f0-936b-b02628506e5c', '7b97d06b1909ca09442dc0b6cd8b7a7f', '88.94.198.172', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.6 Safari/605.1.15', '2026-01-12 10:45:31', '2026-01-12 10:48:29', '2026-01-12 10:48:29', 0, '2026-01-12 10:45:31'),
(504, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'e2a2aab5f9ef139781268c8354f1b47f', '45.14.192.159', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-12 18:36:55', '2026-01-12 18:38:27', '2026-01-12 18:38:27', 0, '2026-01-12 18:36:55'),
(505, '7f0d55c4-b507-11f0-936b-b02628506e5c', '3c7e7c8780d5ffc1aa2685ca3792eb91', '45.14.192.148', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-13 02:08:22', '2026-01-13 02:10:56', '2026-01-13 02:10:56', 0, '2026-01-13 02:08:22'),
(506, '7f0d55c4-b507-11f0-936b-b02628506e5c', '0c49501b8d37943ac6c52a9164c55134', '45.14.192.146', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-13 19:05:58', '2026-01-13 19:36:26', '2026-01-13 19:36:26', 0, '2026-01-13 19:05:58'),
(507, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'f7e427061c6a1c4fa379f28345258866', '45.14.192.154', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-13 20:58:30', '2026-01-13 21:28:24', '2026-01-13 21:28:24', 0, '2026-01-13 20:58:30'),
(508, 'f996adb8d578c9f15859cc9ffb78b023792b', '1707ca4b927fecd1f856f3c0ff926499', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2026-01-13 21:38:55', '2026-01-20 00:06:13', '2026-01-20 00:06:13', 0, '2026-01-13 21:38:55'),
(509, '7f0d55c4-b507-11f0-936b-b02628506e5c', '0fb5936a9b64a29ceb2e45e7ea5dc619', '45.14.192.154', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-13 21:42:29', '2026-01-13 22:09:23', '2026-01-13 22:09:23', 0, '2026-01-13 21:42:29'),
(510, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'd72499caec251ba5931ed29cade9a008', '45.14.192.154', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-13 22:40:56', '2026-01-13 22:41:23', '2026-01-13 22:41:23', 0, '2026-01-13 22:40:56'),
(511, 'f996adb8d578c9f15859cc9ffb78b023792b', '0aaf7b867401aa9d1e5389b2a2f1de93', '172.31.94.34', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Replit/1.0.14 Chrome/124.0.6367.119 Electron/30.0.3 Safari/537.36', '2026-01-13 23:42:10', '2026-01-20 00:06:13', '2026-01-20 00:06:13', 0, '2026-01-13 23:42:10'),
(512, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'e3578acbaf0fe3fbb4eaa296bc4c8841', '45.88.221.199', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-14 10:54:00', '2026-01-14 10:59:07', '2026-01-14 10:59:07', 0, '2026-01-14 10:54:00'),
(513, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'bbc94695572b83af7ed0579361da0982', '45.88.221.199', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-14 11:00:09', '2026-01-14 11:02:16', '2026-01-14 11:02:16', 0, '2026-01-14 11:00:09'),
(514, 'f996adb8d578c9f15859cc9ffb78b023792b', 'd156845888a78aeee2f500e5ce3847e5', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-14 15:30:03', '2026-01-20 00:06:13', '2026-01-20 00:06:13', 0, '2026-01-14 15:30:03'),
(515, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'cf1f1d6e681a6170f4b1cfa6a1d8eb2c', '45.85.248.246', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-15 09:41:10', '2026-01-15 10:23:47', '2026-01-15 10:23:47', 0, '2026-01-15 09:41:10'),
(516, 'f996adb8d578c9f15859cc9ffb78b023792b', '44ccf88f886c2308e607bfceb2eda57c', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-15 09:59:22', '2026-01-20 00:06:13', '2026-01-20 00:06:13', 0, '2026-01-15 09:59:22'),
(517, '7f072d6a-b507-11f0-936b-b02628506e5c', '8c1e1e83582f8dced491db5588162bc8', '45.85.248.246', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-15 10:00:37', '2026-01-15 10:30:50', '2026-01-15 10:30:50', 0, '2026-01-15 10:00:37'),
(518, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'fcb14bc7e0d31e296d7395d8fbf07e02', '45.85.248.246', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-15 10:23:47', '2026-01-15 10:45:15', '2026-01-15 10:45:15', 0, '2026-01-15 10:23:47'),
(519, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'e15d4dc8a973e9dca92c6ff371528a7d', '45.85.248.244', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-15 11:07:08', '2026-01-15 11:10:37', '2026-01-15 11:10:37', 0, '2026-01-15 11:07:08'),
(520, 'f996adb8d578c9f15859cc9ffb78b023792b', 'afa5a77c78429276dc0983fb0f360596', '62.92.137.147', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2026-01-15 15:37:15', '2026-01-20 00:06:13', '2026-01-20 00:06:13', 0, '2026-01-15 15:37:15'),
(521, '7f0d55c4-b507-11f0-936b-b02628506e5c', '448bbcfc44aa3c95ec4570172007ab30', '45.85.248.249', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-16 05:48:17', '2026-01-16 05:48:51', '2026-01-16 05:48:51', 0, '2026-01-16 05:48:17'),
(522, '738e6ff077a74d602e68b898b6dfe805312e', '1af809995b8f67917faaf8a99527bb1e', '195.181.152.28', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-16 07:49:26', '2026-01-16 10:40:29', '2026-01-16 10:40:29', 0, '2026-01-16 07:49:26'),
(523, '7f0d55c4-b507-11f0-936b-b02628506e5c', '13dcde71a5e1b27cbe2a02dccfd015ee', '45.85.248.243', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-16 10:40:26', '2026-01-16 10:41:03', '2026-01-16 10:41:03', 0, '2026-01-16 10:40:26'),
(524, '7f0d55c4-b507-11f0-936b-b02628506e5c', '8cb5443e5d3b876223e961d4eeb899bc', '45.85.248.246', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-16 17:05:37', '2026-01-16 17:06:34', '2026-01-16 17:06:34', 0, '2026-01-16 17:05:37'),
(525, '7f0d55c4-b507-11f0-936b-b02628506e5c', '887872572bcabeee0e03cf508cb7f5fe', '45.85.248.247', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-16 17:48:02', '2026-01-16 17:49:30', '2026-01-16 17:49:30', 0, '2026-01-16 17:48:02'),
(526, '7f0d55c4-b507-11f0-936b-b02628506e5c', '23764bbafa7af0c6cef775c07b079c45', '45.85.248.247', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-16 17:55:52', '2026-01-16 20:55:35', '2026-01-16 20:55:35', 0, '2026-01-16 17:55:52'),
(527, 'f996adb8d578c9f15859cc9ffb78b023792b', '8f1f4c2171a275e1ea8f849224be6a9a', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2026-01-16 20:55:32', '2026-01-20 00:06:13', '2026-01-20 00:06:13', 0, '2026-01-16 20:55:32'),
(528, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'bb605e4591916842efbead89b63cf872', '45.85.248.245', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-16 22:37:02', '2026-01-16 22:37:26', '2026-01-16 22:37:26', 0, '2026-01-16 22:37:02'),
(529, '7f072d6a-b507-11f0-936b-b02628506e5c', '495a6bc281daa6b4cb31879931babe13', '213.128.180.74', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.1 Mobile/15E148 Safari/604.1', '2026-01-17 08:15:45', '2026-01-17 11:24:11', '2026-01-17 11:24:11', 0, '2026-01-17 08:15:45'),
(530, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'd111c8e5f54d6731f69acc740d9506f7', '45.85.248.252', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-17 11:24:08', '2026-01-17 11:24:32', '2026-01-17 11:24:32', 0, '2026-01-17 11:24:08'),
(531, '7f072d6a-b507-11f0-936b-b02628506e5c', 'e996f1751c03c42789a7e3960b12ebe7', '213.128.180.74', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.6 Safari/605.1.15', '2026-01-17 21:29:41', '2026-01-17 21:41:22', '2026-01-17 21:41:22', 0, '2026-01-17 21:29:41'),
(532, 'f996adb8d578c9f15859cc9ffb78b023792b', '5abd31aae694c071ddc807acd81d6654', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-17 23:56:48', '2026-01-20 00:06:13', '2026-01-20 00:06:13', 0, '2026-01-17 23:56:48'),
(533, '7f0d55c4-b507-11f0-936b-b02628506e5c', '2897998b8ac51ade91fa998cdd3a44a4', '45.85.248.244', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-18 00:07:30', '2026-01-18 00:46:53', '2026-01-18 00:46:53', 0, '2026-01-18 00:07:30'),
(534, '7f0d55c4-b507-11f0-936b-b02628506e5c', '5d5a6ea570e4838babfe785370145256', '45.85.248.244', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-18 00:46:53', '2026-01-18 00:49:45', '2026-01-18 00:49:45', 0, '2026-01-18 00:46:53'),
(535, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'e9b462a72b33450088dfe0137388821e', '45.85.248.244', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-18 00:51:43', '2026-01-18 00:51:50', '2026-01-18 00:51:50', 0, '2026-01-18 00:51:43'),
(536, '7f0d55c4-b507-11f0-936b-b02628506e5c', '666222155fbc563cf37be95fef3693e5', '45.85.248.244', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-18 02:17:19', '2026-01-18 02:20:43', '2026-01-18 02:20:43', 0, '2026-01-18 02:17:19'),
(537, '7f0d55c4-b507-11f0-936b-b02628506e5c', '8f92406b907a98f85161458a4324865c', '45.85.248.248', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-18 10:20:53', '2026-01-18 10:21:24', '2026-01-18 10:21:24', 0, '2026-01-18 10:20:53'),
(538, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'e5f343dd523e4b72e04257e7eb33af75', '45.85.248.251', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-18 12:55:15', '2026-01-18 12:56:27', '2026-01-18 12:56:27', 0, '2026-01-18 12:55:15'),
(539, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'd30404b0f9e055fede59fcf9217cce77', '45.85.248.254', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-18 19:23:28', '2026-01-18 19:24:16', '2026-01-18 19:24:16', 0, '2026-01-18 19:23:28'),
(540, 'f996adb8d578c9f15859cc9ffb78b023792b', '755d05076ba186f323c63264b2cfa8b1', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-18 21:43:46', '2026-01-20 00:06:13', '2026-01-20 00:06:13', 0, '2026-01-18 21:43:46'),
(541, 'f996adb8d578c9f15859cc9ffb78b023792b', 'ff6797402b6451d368bfee97913c5fcc', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-18 22:06:51', '2026-01-20 00:06:13', '2026-01-20 00:06:13', 0, '2026-01-18 22:06:51'),
(542, 'f996adb8d578c9f15859cc9ffb78b023792b', 'd5680351353eb376397a610178fcdcdd', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2026-01-19 00:27:13', '2026-01-20 00:06:13', '2026-01-20 00:06:13', 0, '2026-01-19 00:27:13'),
(543, '7f0d55c4-b507-11f0-936b-b02628506e5c', '639841aa3330b474c73c2f6879d70889', '45.85.248.254', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-19 08:42:27', '2026-01-19 08:43:48', '2026-01-19 08:43:48', 0, '2026-01-19 08:42:27'),
(544, '7f072d6a-b507-11f0-936b-b02628506e5c', 'c464384093a8a6bc00b568ed283bb022', '213.128.180.74', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.6 Safari/605.1.15', '2026-01-19 11:19:48', '2026-01-19 16:09:33', '2026-01-19 16:09:33', 0, '2026-01-19 11:19:48'),
(545, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'a5045c30cfe2520781268ab69f10983b', '45.85.251.126', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '2026-01-19 16:09:30', '2026-01-19 16:10:22', '2026-01-19 16:10:22', 0, '2026-01-19 16:09:30'),
(546, 'f996adb8d578c9f15859cc9ffb78b023792b', '3a2e6ee3aa45907bfc28b66fc68ed09c', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-19 23:39:37', '2026-01-20 00:06:13', '2026-01-20 00:06:13', 0, '2026-01-19 23:39:37'),
(547, 'f996adb8d578c9f15859cc9ffb78b023792b', '035e4a1c6a2cb1180d9216409b9f299f', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2026-01-20 00:06:22', '2026-01-20 00:40:06', '2026-01-20 00:40:06', 0, '2026-01-20 00:06:22'),
(548, '7f0d55c4-b507-11f0-936b-b02628506e5c', '7d134f002a4989011e81e44d784fb75f', '45.85.248.246', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-20 00:40:04', '2026-01-20 00:48:26', '2026-01-20 00:48:26', 0, '2026-01-20 00:40:04'),
(549, 'f996adb8d578c9f15859cc9ffb78b023792b', 'f707a0e6f85d6c544a6831a418e20a17', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-20 07:30:59', '2026-01-20 08:11:08', '2026-01-20 08:11:08', 0, '2026-01-20 07:30:59'),
(550, 'f996adb8d578c9f15859cc9ffb78b023792b', 'b334d94aa53d77cf80200ae4b73eaaf8', '89.8.238.174', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2026-01-20 08:11:08', '2026-01-20 09:04:13', '2026-01-20 09:04:13', 0, '2026-01-20 08:11:08'),
(551, 'f996adb8d578c9f15859cc9ffb78b023792b', '997e222af8771d2e599819b9187e96bd', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-20 09:04:13', '2026-01-20 11:22:09', '2026-01-20 11:22:09', 0, '2026-01-20 09:04:13'),
(552, '7f072d6a-b507-11f0-936b-b02628506e5c', '78da8fd94e52ed36bb7385b32927a162', '213.128.180.74', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.6 Safari/605.1.15', '2026-01-20 10:20:21', '2026-01-20 10:23:42', '2026-01-20 10:23:42', 0, '2026-01-20 10:20:21'),
(553, '7f0d55c4-b507-11f0-936b-b02628506e5c', '40e3cb6b3a7145ff80c6c2698eb13e2d', '45.85.248.244', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-20 11:22:07', '2026-01-20 11:23:13', '2026-01-20 11:23:13', 0, '2026-01-20 11:22:07'),
(554, '7f0d55c4-b507-11f0-936b-b02628506e5c', '5689bbdf47eecb724799dba46bb84b6d', '2a05:9cc2:7:2622:15ec:9b30:ed45:9836', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2026-01-20 14:38:46', '2026-01-20 14:41:44', '2026-01-20 14:41:44', 0, '2026-01-20 14:38:46'),
(555, '7f072d6a-b507-11f0-936b-b02628506e5c', '26ae9397baa1f9f4a70ad8e53efef546', '2a02:2121:852:5648:4861:f474:b769:7b81', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.2 Mobile/15E148 Safari/604.1', '2026-01-20 17:39:17', '2026-01-20 18:27:22', '2026-01-20 18:27:22', 0, '2026-01-20 17:39:17'),
(556, '7f0d55c4-b507-11f0-936b-b02628506e5c', '84f6ed2318fbe931b5f7928e5c902c7a', '45.85.248.254', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-20 18:27:19', '2026-01-20 18:35:29', '2026-01-20 18:35:29', 0, '2026-01-20 18:27:19'),
(557, '7f0d55c4-b507-11f0-936b-b02628506e5c', 'e5f1d1ab7218847eb4ba8289fc331386', '45.85.248.254', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-20 18:35:48', '2026-01-20 19:56:55', '2026-01-20 19:56:55', 0, '2026-01-20 18:35:48'),
(558, '7f0d55c4-b507-11f0-936b-b02628506e5c', '4c4ac531e32c1a4e1c861dcbd466818a', '45.85.248.251', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-20 19:56:55', '2026-01-20 19:57:49', '2026-01-20 19:57:49', 0, '2026-01-20 19:56:55'),
(559, 'f996adb8d578c9f15859cc9ffb78b023792b', '9685e8ca4cf47314a72a47d9bdbbf27d', '147.12.38.160', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/139.0.7258.76 Mobile/15E148 Safari/604.1', '2026-01-20 21:59:05', '2026-01-20 23:05:30', '2026-01-20 23:05:30', 0, '2026-01-20 21:59:05'),
(560, '7f0d55c4-b507-11f0-936b-b02628506e5c', '2e6f637ce80455a77cf18e5d19e1fa39', '45.85.248.251', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2026-01-20 23:05:27', '2026-01-20 23:05:46', '2026-01-20 23:05:46', 0, '2026-01-20 23:05:27'),
(561, 'f996adb8d578c9f15859cc9ffb78b023792b', '18aae8120c72e648e4791698bae6c4d1', '147.12.38.160', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-21 00:37:06', NULL, '2026-01-21 01:12:11', 1, '2026-01-21 00:37:06');

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

--
-- Dataark for tabell `tenant_announcements`
--

INSERT INTO `tenant_announcements` (`id`, `company_id`, `author_id`, `title`, `content`, `announcement_type`, `priority`, `target_audience`, `is_published`, `publish_at`, `expire_at`, `is_pinned`, `related_event_id`, `related_module`, `metadata`, `created_at`, `updated_at`, `image_url`) VALUES
('', '136c7dba2f23fd91db56b342dd535c225a80', '30ffdd6978f7086948c110585ae54954af7f', 'Demo', 'Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.', 'info', 'normal', 'all', 1, '2025-12-17 02:28:46', NULL, 0, NULL, NULL, NULL, '2025-12-17 02:28:46', '2025-12-17 11:45:50', 'https://8upload.com/image/dfa09122320acd9b/Skjermbilde_2025-12-17_114457.jpg'),
('ab8973d3-ddde-11f0-a864-b02628506e5c', '54da99402ab5beb095223ce68587761f2dc7', '738e6ff077a74d602e68b898b6dfe805312e', 'Åpning', 'Vi åpner dørene 01.01.2026', 'info', 'normal', 'all', 1, '2025-12-20 21:01:27', NULL, 0, NULL, NULL, NULL, '2025-12-20 21:01:27', '2025-12-20 21:01:27', 'https://8upload.com/image/dd52a8851b8445b0/513_58532e5c-150d-4526-a40c-02da08ed7dd8__1_.jpg'),
('c5406e5a-ddb8-11f0-a864-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '7f0d55c4-b507-11f0-936b-b02628506e5c', 'Informasjon: Ny Webside.', 'Her kan du sveipe til tjenester for å finne alle våre arrangementer og medlemskap.\n\nVed påmelding/u medlemskap, må du manuelt godkjennes.\nVed påmelding/m medlemskap, blir du automatisk godkjent og tildelt innsjekk\nMedlemskap kjøpes også her\nNB; Vi bruker oppgitt e-post adresse til tilbakemelding, så pass på at den blir rett utfylt.', 'info', 'normal', 'all', 1, '2025-12-20 16:30:00', NULL, 0, NULL, NULL, NULL, '2025-12-20 16:30:10', '2026-01-05 19:54:13', 'https://upload.wikimedia.org/wikipedia/commons/0/08/Fosnav%C3%A5g_Heid_tursti.jpg'),
('ce605bc8-e75a-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '7f0d55c4-b507-11f0-936b-b02628506e5c', 'Nytt Dansetreff med Steinar Engelbrektson 7. Feb.', 'Dette er nyårets største satsing i klubben, Vi har booket Steinar Engelbrektson og lager stordans på Kulturhuset i fosnavåg.', 'info', 'normal', 'all', 1, '2026-01-12 22:42:00', NULL, 0, NULL, NULL, NULL, '2026-01-01 22:42:44', '2026-01-18 00:03:04', 'https://external-content.duckduckgo.com/iu/?u=https%3A%2F%2Ftse3.mm.bing.net%2Fth%2Fid%2FOIP.dHnnny-GT36votxWsz-t0wHaFp%3Fpid%3DApi&f=1&ipt=16916d9ad446659129b767cb1eda1607e659eeb838534a8be31ec45dda074966&ipo=images'),
('d7491bd7-ea66-11f0-9895-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '7f0d55c4-b507-11f0-936b-b02628506e5c', 'BINGO PÅ HØGTUN', '28.01.2026 kjører vi på med årets første Bingo.\n\"Ingen påmelding\", her er det \"første mann til mølla\" som gjelder.. :-)', 'info', 'normal', 'all', 1, '2026-01-05 19:46:00', NULL, 0, NULL, NULL, NULL, '2026-01-05 19:46:26', '2026-01-10 20:38:29', 'https://external-content.duckduckgo.com/iu/?u=https%3A%2F%2Ftse2.mm.bing.net%2Fth%2Fid%2FOIP.Y-X130sw_xnC2p8MrQvOcQHaFk%3Fpid%3DApi&f=1&ipt=1d6b29240be816390494bf8764c0d87d2267aa44ae995f2ccb364417f32b6862&ipo=images'),
('fa3abb30-ee55-11f0-afe9-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '7f0d55c4-b507-11f0-936b-b02628506e5c', 'Fredagsdans på Høgtun 23.01.26 kl 19.00 Gratis inngang.!!!', 'Velkommen til en kjekk og sosial kveld på Høgtun !\nTrekkspel kameratane leverer flott dansemusikk 🕢 Dørene åpner kl. 19 - gratis inngang Salg av enkel mat og snacks 🥂🍾 -Ta med det du ønsker -Ingen påmelding Ta med venner, godt humør og danselyst – så lagar vi ein triveleg fredagskveld saman!', 'info', 'normal', 'all', 1, '2026-01-10 20:00:00', NULL, 0, NULL, NULL, NULL, '2026-01-10 19:55:48', '2026-01-12 18:38:01', 'https://8upload.com//image/935ede7e9fc39248/Skjermbilde_2026-01-10_kl._15.14.49.png');

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
  `subdomain_status` varchar(20) DEFAULT 'pending',
  `celebratoryHeader` tinyint(1) DEFAULT 0,
  `trial_demo` int(11) DEFAULT 0,
  `refresh_polling` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Tenant companies with individual admin passwords for data modification';

--
-- Dataark for tabell `tenant_companies`
--

INSERT INTO `tenant_companies` (`id`, `name`, `legal_name`, `org_number`, `entity_type`, `subdomain`, `domain`, `industry`, `about_us`, `contact_email`, `contact_phone`, `website`, `logo_url`, `address_street`, `address_city`, `address_state`, `address_zip`, `address_country`, `tax_id`, `tax_registered`, `is_active`, `trial_ends_at`, `onboarded_by`, `created_at`, `updated_at`, `Cash_payment`, `admin_password_hash`, `password_last_changed`, `password_reset_required`, `subdomain_active`, `subdomain_dns_created`, `subdomain_last_check`, `subdomain_status`, `celebratoryHeader`, `trial_demo`, `refresh_polling`) VALUES
('136c7dba2f23fd91db56b342dd535c225a80', 'BABYLON FRISØR', 'BABYLON FRISØR MOSHREQ RAZZAQ ATIA ALHILALY', '919163577', 'company', 'babylon', NULL, 'Frisering og barbering', '', 'babylon@salesman.no', '', '', 'https://8upload.com/image/57f45c717c753bb0/A430C261-44C3-41B0-A8B5-66B6BF299688.png', 'Bjørndalvegen 25', 'ULSTEINVIK', NULL, '6065', 'Norway', NULL, 0, 1, NULL, 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-12-17 01:29:18', '2025-12-19 00:24:09', 1, NULL, '2025-12-17 01:29:18', 0, 0, NULL, '2025-12-17 08:19:24', 'active', 0, 0, 0),
('2abf8cb74c20b1a64941e0351630f3ae39d7', 'PRO ULSTEINVIK AS', 'PRO ULSTEINVIK AS', '917582793', 'company', 'proulsteinvik', NULL, 'Drift av restauranter', NULL, 'proulsteinvik@salesman.no', '', NULL, NULL, 'Sjøgata 45', 'ULSTEINVIK', NULL, '6065', 'Norway', NULL, 0, 1, NULL, 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-12-04 18:10:11', '2025-12-04 18:10:11', 1, NULL, '2025-12-04 18:10:11', 0, 0, NULL, NULL, 'pending', 0, 0, 0),
('54da99402ab5beb095223ce68587761f2dc7', 'JustNails', 'JustNails', '', 'company', 'justnails', NULL, 'Neglesalong', NULL, 'justnails@salesman.no', '94280190', NULL, 'https://8upload.com/image/2406089619fdbd55/IMG_5681.png', 'Ovrageila 4', 'Hjørungavåg', NULL, '6063', 'Norway', NULL, 0, 1, NULL, 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-12-18 15:22:46', '2025-12-21 21:54:52', 1, NULL, '2025-12-18 15:22:46', 0, 0, NULL, NULL, 'pending', 1, 0, 0),
('5c98d1c6daaed60c59a906dcaa139ee3adb1', 'DIMNA GRENDAHUS', 'DIMNA GRENDAHUS SA', '911905906', 'company', 'dimna', NULL, 'Utleie av egen eller leid fast eiendom', NULL, 'dimnagrendahus@salesman.no', '', NULL, NULL, 'Hansvika 29', 'ULSTEINVIK', NULL, '6065', 'Norway', NULL, 0, 1, NULL, 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-12-21 21:32:29', '2025-12-22 00:06:03', 1, NULL, '2025-12-21 21:32:29', 0, 0, NULL, '2025-12-21 22:52:03', 'active', 0, 1, 0),
('7efbddea-b507-11f0-936b-b02628506e5c', 'Bergsøy Danseklubb', 'Bergsøy Danseklubb', ' 996 171 159', '', 'bergsoy', '', 'Forening', 'Bergsøy Danseklubb er en engasjert og inkluderende forening som arbeider for å løfte dansen og det sosiale fellesskapet i Herøy og omegn. Vi ønsker å ta vare på våre dansetradisjoner, samtidig som vi har døren åpen for alle som ønsker å delta i et aktivt og sosialt miljø.\n\nKlubben er et møtested for både unge og eldre, og kjennetegnes av et svært godt miljø der dans og samhold står i sentrum. Hos oss er det høyt under taket og rom for alle.\n\nVi arrangerer større dansekvelder på Kulturhuset med levende musikk. Disse arrangementene har blitt svært populære og samler mange til dans og hyggelig sosialt samvær. På vårt forsamlingshus Høgtun arrangerer vi også enklere dansekvelder, kosekvelder og bingokvelder.\n\nFølg gjerne med på vår nettside for oppdatert informasjon om kommende arrangementer.\nHøgtun kan også leies ut – ta kontakt på telefon 481 45 008 for reservasjon.\n\nVi oppfordrer alle til å melde seg inn i klubben og bli en del av fellesskapet. Her er det gode muligheter for å bidra med nye idéer og være med på å skape arrangementer som gir flere muligheten til å oppleve det gode samværet vi har i Bergsøy Danseklubb.\n\nAlle er hjertelig velkommen!', 'atlekarl@tussa.com', '95918925', 'bergsoy.salesman.no', 'https://8upload.com/image/5c1dc056d859233e/Skjermbilde_2025-12-16_211926.jpg', 'Vågsgata 61', 'Fosnavåg', 'Møre og Romsdal', '6090', 'Norway', '', 1, 1, NULL, NULL, '2025-10-29 21:40:24', '2026-01-17 23:57:01', 1, '$2y$12$c3IAZ9yaJMz/RhgZDHlo6OLAEjdZGWzCrGE1g.D/My7BR0nqqb8ie', '2025-10-29 21:40:24', 0, 1, NULL, '2025-12-06 14:17:46', 'active', 0, 0, 0),
('a7eb3dbc5543932549d02a0bfd2b79720ad5', 'VERONA ULSTEINVIK AS', 'VERONA ULSTEINVIK AS', '921283873', 'company', 'verona', NULL, 'Drift av restauranter', '', 'veronaulsteinvik@salesman.no', '40404040', '', NULL, 'Kyrkjegata 14A', 'ULSTEINVIK', NULL, '6065', 'Norway', NULL, 0, 1, NULL, 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-11-20 15:45:54', '2025-11-24 01:20:45', 1, NULL, '2025-11-20 15:45:54', 0, 0, NULL, '2025-11-20 15:47:33', 'active', 0, 0, 0),
('a9cdf14b6790d0247b55534d68065eb695f9', 'KAFFEBAREN ULSTEINVIK AS', 'KAFFEBAREN ULSTEINVIK AS', '988210102', 'company', 'kaffebaren', NULL, 'Drift av barer', '', 'kaffebarenulsteinvik@salesman.no', '40404040', '', NULL, 'Sjøgata 55', 'ULSTEINVIK', NULL, '6065', 'magnekvalsvique@gmail.com', NULL, 0, 1, NULL, 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-11-13 16:27:41', '2025-11-13 17:12:16', 1, '$2y$10$/wftPstrPMjYMkGGWXjBYeMl.uoyC4I/4pWQu2esBvLe.2Li5/vk2', '2025-11-13 17:12:16', 0, 0, NULL, NULL, 'pending', 0, 0, 0);

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

--
-- Dataark for tabell `tenant_company_modules`
--

INSERT INTO `tenant_company_modules` (`id`, `company_id`, `module_id`, `is_active`, `config`, `activated_at`, `deactivated_at`, `assigned_by`, `created_at`) VALUES
('0640b87f-dc1d-11f0-bffa-b02628506e5c', '54da99402ab5beb095223ce68587761f2dc7', '15a3c857-b57f-11f0-a6d0-b02628506e5c', 1, NULL, '2025-12-18 15:22:46', NULL, 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-12-18 15:22:46'),
('0640cea5-dc1d-11f0-bffa-b02628506e5c', '54da99402ab5beb095223ce68587761f2dc7', '7f3c499a-af1a-11f0-a914-b02628506e5c', 1, NULL, '2025-12-18 15:22:46', NULL, 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-12-18 15:22:46'),
('0640d667-dc1d-11f0-bffa-b02628506e5c', '54da99402ab5beb095223ce68587761f2dc7', '7f3c4bb2-af1a-11f0-a914-b02628506e5c', 1, NULL, '2025-12-18 15:22:46', NULL, 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-12-18 15:22:46'),
('0640de5d-dc1d-11f0-bffa-b02628506e5c', '54da99402ab5beb095223ce68587761f2dc7', '7f3c4c76-af1a-11f0-a914-b02628506e5c', 1, NULL, '2025-12-18 15:22:46', NULL, 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-12-18 15:22:46'),
('0640e53f-dc1d-11f0-bffa-b02628506e5c', '54da99402ab5beb095223ce68587761f2dc7', '7f3c4ceb-af1a-11f0-a914-b02628506e5c', 1, NULL, '2025-12-18 15:22:46', NULL, 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-12-18 15:22:46'),
('0640ebf6-dc1d-11f0-bffa-b02628506e5c', '54da99402ab5beb095223ce68587761f2dc7', '7f3c4d5f-af1a-11f0-a914-b02628506e5c', 1, NULL, '2025-12-18 15:22:46', NULL, 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-12-18 15:22:46'),
('0640f229-dc1d-11f0-bffa-b02628506e5c', '54da99402ab5beb095223ce68587761f2dc7', '7f3c4dcc-af1a-11f0-a914-b02628506e5c', 1, NULL, '2025-12-18 15:22:46', NULL, 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-12-18 15:22:46'),
('0640f900-dc1d-11f0-bffa-b02628506e5c', '54da99402ab5beb095223ce68587761f2dc7', '7f3c4e39-af1a-11f0-a914-b02628506e5c', 1, NULL, '2025-12-18 15:22:46', NULL, 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-12-18 15:22:46'),
('0641000e-dc1d-11f0-bffa-b02628506e5c', '54da99402ab5beb095223ce68587761f2dc7', '7f3c4e9e-af1a-11f0-a914-b02628506e5c', 1, NULL, '2025-12-18 15:22:46', NULL, 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-12-18 15:22:46'),
('0641265d-dc1d-11f0-bffa-b02628506e5c', '54da99402ab5beb095223ce68587761f2dc7', '7f3c4f0b-af1a-11f0-a914-b02628506e5c', 1, NULL, '2025-12-18 15:22:46', NULL, 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-12-18 15:22:46'),
('06413010-dc1d-11f0-bffa-b02628506e5c', '54da99402ab5beb095223ce68587761f2dc7', 'f9d42479-b182-11f0-b9ad-b02628506e5c', 1, NULL, '2025-12-18 15:22:46', NULL, 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-12-18 15:22:46'),
('17f11c31-d134-11f0-93be-b02628506e5c', '2abf8cb74c20b1a64941e0351630f3ae39d7', '15a3c857-b57f-11f0-a6d0-b02628506e5c', 1, NULL, '2025-12-04 18:10:11', NULL, 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-12-04 18:10:11'),
('17f13258-d134-11f0-93be-b02628506e5c', '2abf8cb74c20b1a64941e0351630f3ae39d7', '7f3c499a-af1a-11f0-a914-b02628506e5c', 1, NULL, '2025-12-04 18:10:11', NULL, 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-12-04 18:10:11'),
('17f13efa-d134-11f0-93be-b02628506e5c', '2abf8cb74c20b1a64941e0351630f3ae39d7', '7f3c4bb2-af1a-11f0-a914-b02628506e5c', 1, NULL, '2025-12-04 18:10:11', NULL, 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-12-04 18:10:11'),
('17f146e0-d134-11f0-93be-b02628506e5c', '2abf8cb74c20b1a64941e0351630f3ae39d7', '7f3c4c76-af1a-11f0-a914-b02628506e5c', 1, NULL, '2025-12-04 18:10:11', NULL, 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-12-04 18:10:11'),
('17f14e91-d134-11f0-93be-b02628506e5c', '2abf8cb74c20b1a64941e0351630f3ae39d7', '7f3c4ceb-af1a-11f0-a914-b02628506e5c', 1, NULL, '2025-12-04 18:10:11', NULL, 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-12-04 18:10:11'),
('17f1569d-d134-11f0-93be-b02628506e5c', '2abf8cb74c20b1a64941e0351630f3ae39d7', '7f3c4d5f-af1a-11f0-a914-b02628506e5c', 1, NULL, '2025-12-04 18:10:11', NULL, 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-12-04 18:10:11'),
('17f15ff4-d134-11f0-93be-b02628506e5c', '2abf8cb74c20b1a64941e0351630f3ae39d7', '7f3c4dcc-af1a-11f0-a914-b02628506e5c', 1, NULL, '2025-12-04 18:10:11', NULL, 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-12-04 18:10:11'),
('17f1689b-d134-11f0-93be-b02628506e5c', '2abf8cb74c20b1a64941e0351630f3ae39d7', '7f3c4e39-af1a-11f0-a914-b02628506e5c', 1, NULL, '2025-12-04 18:10:11', NULL, 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-12-04 18:10:11'),
('17f17217-d134-11f0-93be-b02628506e5c', '2abf8cb74c20b1a64941e0351630f3ae39d7', '7f3c4e9e-af1a-11f0-a914-b02628506e5c', 1, NULL, '2025-12-04 18:10:11', NULL, 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-12-04 18:10:11'),
('17f17a52-d134-11f0-93be-b02628506e5c', '2abf8cb74c20b1a64941e0351630f3ae39d7', '7f3c4f0b-af1a-11f0-a914-b02628506e5c', 1, NULL, '2025-12-04 18:10:11', NULL, 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-12-04 18:10:11'),
('17f1837e-d134-11f0-93be-b02628506e5c', '2abf8cb74c20b1a64941e0351630f3ae39d7', 'f9d42479-b182-11f0-b9ad-b02628506e5c', 1, NULL, '2025-12-04 18:10:11', NULL, 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-12-04 18:10:11'),
('2b9e7b6a-deac-11f0-bb40-b02628506e5c', '5c98d1c6daaed60c59a906dcaa139ee3adb1', '15a3c857-b57f-11f0-a6d0-b02628506e5c', 1, NULL, '2025-12-21 21:32:29', NULL, 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-12-21 21:32:29'),
('2b9e9744-deac-11f0-bb40-b02628506e5c', '5c98d1c6daaed60c59a906dcaa139ee3adb1', '7f3c499a-af1a-11f0-a914-b02628506e5c', 1, NULL, '2025-12-21 21:32:29', NULL, 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-12-21 21:32:29'),
('2b9ea29f-deac-11f0-bb40-b02628506e5c', '5c98d1c6daaed60c59a906dcaa139ee3adb1', '7f3c4bb2-af1a-11f0-a914-b02628506e5c', 1, NULL, '2025-12-21 21:32:29', NULL, 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-12-21 21:32:29'),
('2b9eab9e-deac-11f0-bb40-b02628506e5c', '5c98d1c6daaed60c59a906dcaa139ee3adb1', '7f3c4c76-af1a-11f0-a914-b02628506e5c', 1, NULL, '2025-12-21 21:32:29', NULL, 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-12-21 21:32:29'),
('2b9eb586-deac-11f0-bb40-b02628506e5c', '5c98d1c6daaed60c59a906dcaa139ee3adb1', '7f3c4ceb-af1a-11f0-a914-b02628506e5c', 1, NULL, '2025-12-21 21:32:29', NULL, 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-12-21 21:32:29'),
('2b9ec11a-deac-11f0-bb40-b02628506e5c', '5c98d1c6daaed60c59a906dcaa139ee3adb1', '7f3c4d5f-af1a-11f0-a914-b02628506e5c', 1, NULL, '2025-12-21 21:32:29', NULL, 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-12-21 21:32:29'),
('2b9ec9b1-deac-11f0-bb40-b02628506e5c', '5c98d1c6daaed60c59a906dcaa139ee3adb1', '7f3c4dcc-af1a-11f0-a914-b02628506e5c', 1, NULL, '2025-12-21 21:32:29', NULL, 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-12-21 21:32:29'),
('2b9ed4cc-deac-11f0-bb40-b02628506e5c', '5c98d1c6daaed60c59a906dcaa139ee3adb1', '7f3c4e39-af1a-11f0-a914-b02628506e5c', 1, NULL, '2025-12-21 21:32:29', NULL, 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-12-21 21:32:29'),
('2b9ee13c-deac-11f0-bb40-b02628506e5c', '5c98d1c6daaed60c59a906dcaa139ee3adb1', '7f3c4e9e-af1a-11f0-a914-b02628506e5c', 1, NULL, '2025-12-21 21:32:29', NULL, 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-12-21 21:32:29'),
('2b9f0f17-deac-11f0-bb40-b02628506e5c', '5c98d1c6daaed60c59a906dcaa139ee3adb1', '7f3c4f0b-af1a-11f0-a914-b02628506e5c', 1, NULL, '2025-12-21 21:32:29', NULL, 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-12-21 21:32:29'),
('2b9f19c7-deac-11f0-bb40-b02628506e5c', '5c98d1c6daaed60c59a906dcaa139ee3adb1', 'f9d42479-b182-11f0-b9ad-b02628506e5c', 1, NULL, '2025-12-21 21:32:29', NULL, 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-12-21 21:32:29'),
('4b38e25c-c0a5-11f0-8af7-b02628506e5c', 'a9cdf14b6790d0247b55534d68065eb695f9', '15a3c857-b57f-11f0-a6d0-b02628506e5c', 1, NULL, '2025-11-13 16:27:41', NULL, 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-11-13 16:27:41'),
('4b38f231-c0a5-11f0-8af7-b02628506e5c', 'a9cdf14b6790d0247b55534d68065eb695f9', '7f3c499a-af1a-11f0-a914-b02628506e5c', 1, NULL, '2025-11-13 16:27:41', NULL, 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-11-13 16:27:41'),
('4b390007-c0a5-11f0-8af7-b02628506e5c', 'a9cdf14b6790d0247b55534d68065eb695f9', '7f3c4bb2-af1a-11f0-a914-b02628506e5c', 1, NULL, '2025-11-13 16:27:41', NULL, 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-11-13 16:27:41'),
('4b390d89-c0a5-11f0-8af7-b02628506e5c', 'a9cdf14b6790d0247b55534d68065eb695f9', '7f3c4c76-af1a-11f0-a914-b02628506e5c', 1, NULL, '2025-11-13 16:27:41', NULL, 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-11-13 16:27:41'),
('4b391f94-c0a5-11f0-8af7-b02628506e5c', 'a9cdf14b6790d0247b55534d68065eb695f9', '7f3c4ceb-af1a-11f0-a914-b02628506e5c', 1, NULL, '2025-11-13 16:27:41', NULL, 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-11-13 16:27:41'),
('4b392c26-c0a5-11f0-8af7-b02628506e5c', 'a9cdf14b6790d0247b55534d68065eb695f9', '7f3c4d5f-af1a-11f0-a914-b02628506e5c', 1, NULL, '2025-11-13 16:27:41', NULL, 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-11-13 16:27:41'),
('4b3936c3-c0a5-11f0-8af7-b02628506e5c', 'a9cdf14b6790d0247b55534d68065eb695f9', '7f3c4dcc-af1a-11f0-a914-b02628506e5c', 1, NULL, '2025-11-13 16:27:41', NULL, 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-11-13 16:27:41'),
('4b39409b-c0a5-11f0-8af7-b02628506e5c', 'a9cdf14b6790d0247b55534d68065eb695f9', '7f3c4e39-af1a-11f0-a914-b02628506e5c', 1, NULL, '2025-11-13 16:27:41', NULL, 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-11-13 16:27:41'),
('4b394bcb-c0a5-11f0-8af7-b02628506e5c', 'a9cdf14b6790d0247b55534d68065eb695f9', '7f3c4e9e-af1a-11f0-a914-b02628506e5c', 1, NULL, '2025-11-13 16:27:41', NULL, 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-11-13 16:27:41'),
('4b39572c-c0a5-11f0-8af7-b02628506e5c', 'a9cdf14b6790d0247b55534d68065eb695f9', '7f3c4f0b-af1a-11f0-a914-b02628506e5c', 1, NULL, '2025-11-13 16:27:41', NULL, 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-11-13 16:27:41'),
('4b39627c-c0a5-11f0-8af7-b02628506e5c', 'a9cdf14b6790d0247b55534d68065eb695f9', 'f9d42479-b182-11f0-b9ad-b02628506e5c', 1, NULL, '2025-11-13 16:27:41', NULL, 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-11-13 16:27:41'),
('6cbe1066-dadf-11f0-bd81-b02628506e5c', '136c7dba2f23fd91db56b342dd535c225a80', '15a3c857-b57f-11f0-a6d0-b02628506e5c', 1, NULL, '2025-12-17 01:29:18', NULL, 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-12-17 01:29:18'),
('6cbe479a-dadf-11f0-bd81-b02628506e5c', '136c7dba2f23fd91db56b342dd535c225a80', '7f3c499a-af1a-11f0-a914-b02628506e5c', 1, NULL, '2025-12-17 01:29:18', NULL, 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-12-17 01:29:18'),
('6cbe6bad-dadf-11f0-bd81-b02628506e5c', '136c7dba2f23fd91db56b342dd535c225a80', '7f3c4bb2-af1a-11f0-a914-b02628506e5c', 1, NULL, '2025-12-17 01:29:18', NULL, 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-12-17 01:29:18'),
('6cbe8646-dadf-11f0-bd81-b02628506e5c', '136c7dba2f23fd91db56b342dd535c225a80', '7f3c4c76-af1a-11f0-a914-b02628506e5c', 1, NULL, '2025-12-17 01:29:18', NULL, 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-12-17 01:29:18'),
('6cbeb9d6-dadf-11f0-bd81-b02628506e5c', '136c7dba2f23fd91db56b342dd535c225a80', '7f3c4ceb-af1a-11f0-a914-b02628506e5c', 1, NULL, '2025-12-17 01:29:18', NULL, 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-12-17 01:29:18'),
('6cbede68-dadf-11f0-bd81-b02628506e5c', '136c7dba2f23fd91db56b342dd535c225a80', '7f3c4d5f-af1a-11f0-a914-b02628506e5c', 1, NULL, '2025-12-17 01:29:18', NULL, 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-12-17 01:29:18'),
('6cbf096f-dadf-11f0-bd81-b02628506e5c', '136c7dba2f23fd91db56b342dd535c225a80', '7f3c4dcc-af1a-11f0-a914-b02628506e5c', 1, NULL, '2025-12-17 01:29:18', NULL, 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-12-17 01:29:18'),
('6cbf3612-dadf-11f0-bd81-b02628506e5c', '136c7dba2f23fd91db56b342dd535c225a80', '7f3c4e39-af1a-11f0-a914-b02628506e5c', 1, NULL, '2025-12-17 01:29:18', NULL, 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-12-17 01:29:18'),
('6cbf4cdf-dadf-11f0-bd81-b02628506e5c', '136c7dba2f23fd91db56b342dd535c225a80', '7f3c4e9e-af1a-11f0-a914-b02628506e5c', 1, NULL, '2025-12-17 01:29:18', NULL, 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-12-17 01:29:18'),
('6cbf74e6-dadf-11f0-bd81-b02628506e5c', '136c7dba2f23fd91db56b342dd535c225a80', '7f3c4f0b-af1a-11f0-a914-b02628506e5c', 1, NULL, '2025-12-17 01:29:18', NULL, 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-12-17 01:29:18'),
('6cbfa124-dadf-11f0-bd81-b02628506e5c', '136c7dba2f23fd91db56b342dd535c225a80', 'f9d42479-b182-11f0-b9ad-b02628506e5c', 1, NULL, '2025-12-17 01:29:18', NULL, 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-12-17 01:29:18'),
('9e36f67c-c61f-11f0-a3f5-b02628506e5c', 'a7eb3dbc5543932549d02a0bfd2b79720ad5', '15a3c857-b57f-11f0-a6d0-b02628506e5c', 1, NULL, '2025-11-20 15:45:54', NULL, 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-11-20 15:45:54'),
('9e37124f-c61f-11f0-a3f5-b02628506e5c', 'a7eb3dbc5543932549d02a0bfd2b79720ad5', '7f3c499a-af1a-11f0-a914-b02628506e5c', 1, NULL, '2025-11-20 15:45:54', NULL, 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-11-20 15:45:54'),
('9e37225f-c61f-11f0-a3f5-b02628506e5c', 'a7eb3dbc5543932549d02a0bfd2b79720ad5', '7f3c4bb2-af1a-11f0-a914-b02628506e5c', 1, NULL, '2025-11-20 15:45:54', NULL, 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-11-20 15:45:54'),
('9e373341-c61f-11f0-a3f5-b02628506e5c', 'a7eb3dbc5543932549d02a0bfd2b79720ad5', '7f3c4c76-af1a-11f0-a914-b02628506e5c', 1, NULL, '2025-11-20 15:45:54', NULL, 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-11-20 15:45:54'),
('9e375ce1-c61f-11f0-a3f5-b02628506e5c', 'a7eb3dbc5543932549d02a0bfd2b79720ad5', '7f3c4ceb-af1a-11f0-a914-b02628506e5c', 1, NULL, '2025-11-20 15:45:54', NULL, 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-11-20 15:45:54'),
('9e376413-c61f-11f0-a3f5-b02628506e5c', 'a7eb3dbc5543932549d02a0bfd2b79720ad5', '7f3c4d5f-af1a-11f0-a914-b02628506e5c', 1, NULL, '2025-11-20 15:45:54', NULL, 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-11-20 15:45:54'),
('9e376ef4-c61f-11f0-a3f5-b02628506e5c', 'a7eb3dbc5543932549d02a0bfd2b79720ad5', '7f3c4dcc-af1a-11f0-a914-b02628506e5c', 1, NULL, '2025-11-20 15:45:54', NULL, 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-11-20 15:45:54'),
('9e377701-c61f-11f0-a3f5-b02628506e5c', 'a7eb3dbc5543932549d02a0bfd2b79720ad5', '7f3c4e39-af1a-11f0-a914-b02628506e5c', 1, NULL, '2025-11-20 15:45:54', NULL, 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-11-20 15:45:54'),
('9e3780cf-c61f-11f0-a3f5-b02628506e5c', 'a7eb3dbc5543932549d02a0bfd2b79720ad5', '7f3c4e9e-af1a-11f0-a914-b02628506e5c', 1, NULL, '2025-11-20 15:45:54', NULL, 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-11-20 15:45:54'),
('9e378aa1-c61f-11f0-a3f5-b02628506e5c', 'a7eb3dbc5543932549d02a0bfd2b79720ad5', '7f3c4f0b-af1a-11f0-a914-b02628506e5c', 1, NULL, '2025-11-20 15:45:54', NULL, 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-11-20 15:45:54'),
('9e379495-c61f-11f0-a3f5-b02628506e5c', 'a7eb3dbc5543932549d02a0bfd2b79720ad5', 'f9d42479-b182-11f0-b9ad-b02628506e5c', 1, NULL, '2025-11-20 15:45:54', NULL, 'ba464206-b8d8-11f0-ad30-b02628506e5c', '2025-11-20 15:45:54'),
('9f05919d-b508-11f0-936b-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '7f3c499a-af1a-11f0-a914-b02628506e5c', 1, NULL, '2025-10-29 21:48:28', NULL, '4538cad6-b0e4-11f0-a1bb-b02628506e5c', '2025-10-29 21:48:28'),
('9f05a218-b508-11f0-936b-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '7f3c4bb2-af1a-11f0-a914-b02628506e5c', 1, NULL, '2025-10-29 21:48:28', NULL, '4538cad6-b0e4-11f0-a1bb-b02628506e5c', '2025-10-29 21:48:28'),
('9f05a568-b508-11f0-936b-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '7f3c4c76-af1a-11f0-a914-b02628506e5c', 1, NULL, '2025-10-29 21:48:28', NULL, '4538cad6-b0e4-11f0-a1bb-b02628506e5c', '2025-10-29 21:48:28'),
('9f05a748-b508-11f0-936b-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '7f3c4ceb-af1a-11f0-a914-b02628506e5c', 1, NULL, '2025-10-29 21:48:28', NULL, '4538cad6-b0e4-11f0-a1bb-b02628506e5c', '2025-10-29 21:48:28'),
('9f05a90f-b508-11f0-936b-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '7f3c4d5f-af1a-11f0-a914-b02628506e5c', 1, NULL, '2025-10-29 21:48:28', NULL, '4538cad6-b0e4-11f0-a1bb-b02628506e5c', '2025-10-29 21:48:28'),
('9f05aaca-b508-11f0-936b-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '7f3c4dcc-af1a-11f0-a914-b02628506e5c', 1, NULL, '2025-10-29 21:48:28', NULL, '4538cad6-b0e4-11f0-a1bb-b02628506e5c', '2025-10-29 21:48:28'),
('9f05ac89-b508-11f0-936b-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '7f3c4e39-af1a-11f0-a914-b02628506e5c', 1, NULL, '2025-10-29 21:48:28', NULL, '4538cad6-b0e4-11f0-a1bb-b02628506e5c', '2025-10-29 21:48:28'),
('9f05ae3e-b508-11f0-936b-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '7f3c4e9e-af1a-11f0-a914-b02628506e5c', 1, NULL, '2025-10-29 21:48:28', NULL, '4538cad6-b0e4-11f0-a1bb-b02628506e5c', '2025-10-29 21:48:28'),
('9f05aff4-b508-11f0-936b-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', '7f3c4f0b-af1a-11f0-a914-b02628506e5c', 1, NULL, '2025-10-29 21:48:28', NULL, '4538cad6-b0e4-11f0-a1bb-b02628506e5c', '2025-10-29 21:48:28'),
('9f05b1ac-b508-11f0-936b-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'f9d42479-b182-11f0-b9ad-b02628506e5c', 1, NULL, '2025-10-29 21:48:28', NULL, '4538cad6-b0e4-11f0-a1bb-b02628506e5c', '2025-10-29 21:48:28');

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
  `address_street` varchar(255) DEFAULT NULL,
  `address_city` varchar(255) DEFAULT NULL,
  `address_zip` varchar(50) DEFAULT NULL,
  `address_country` varchar(100) DEFAULT NULL,
  `customer_type` varchar(20) DEFAULT 'individual',
  `source` varchar(100) DEFAULT NULL,
  `payment_status` tinyint(1) DEFAULT 0,
  `status` varchar(50) DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_audit_log_id` char(36) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dataark for tabell `tenant_customers`
--

INSERT INTO `tenant_customers` (`id`, `company_id`, `customer_code`, `first_name`, `last_name`, `email`, `phone`, `date_of_birth`, `company_name`, `address_street`, `address_city`, `address_zip`, `address_country`, `customer_type`, `source`, `payment_status`, `status`, `notes`, `created_at`, `updated_at`, `last_audit_log_id`) VALUES
('0451523f085a24ddf332298b2957eb2de3f1', '7efbddea-b507-11f0-936b-b02628506e5c', 'CUST-1768814555-7751', 'Ann', 'Sandanger', NULL, '95789630', NULL, NULL, 'Nedre Hjelmeseth 20', 'Fosnavåg', '6091', NULL, 'individual', 'event_registration', 0, 'active', NULL, '2026-01-19 10:22:35', '2026-01-21 01:06:04', NULL),
('100408a04c8725be625ef9ea5d9cd7668ab6', '7efbddea-b507-11f0-936b-b02628506e5c', 'CUST-1768953766-2506', 'Sigrunn', 'Skoge', NULL, '91747050', NULL, NULL, NULL, NULL, NULL, NULL, 'individual', 'event_registration', 0, 'active', NULL, '2026-01-21 01:02:46', '2026-01-21 01:02:46', NULL),
('126ad5cce3731f36e5e4fcc35fe7071bf427', '7efbddea-b507-11f0-936b-b02628506e5c', 'CUST-1768728918-3394', 'Aud', 'Bendal', NULL, '99299405', NULL, NULL, NULL, NULL, NULL, NULL, 'individual', 'event_registration', 0, 'active', NULL, '2026-01-18 10:35:18', '2026-01-18 10:35:18', NULL),
('148176b84267e1fbfa9c5fd08e21285dde07', '7efbddea-b507-11f0-936b-b02628506e5c', 'CUST-1768845658-3083', 'Johnny', 'Ingebrigtsen', NULL, '97077206', NULL, NULL, NULL, NULL, NULL, NULL, 'individual', 'event_registration', 0, 'active', NULL, '2026-01-19 19:00:58', '2026-01-19 19:00:58', NULL),
('15944770a5255ab2256b1540c674690f7ab0', '7efbddea-b507-11f0-936b-b02628506e5c', 'CUST-1768728918-9292', 'Jarle', 'Berge', NULL, '90817338', NULL, NULL, NULL, NULL, NULL, NULL, 'individual', 'event_registration', 0, 'active', NULL, '2026-01-18 10:35:18', '2026-01-18 10:35:18', NULL),
('18f9e21b335888b6e9db97243ac05466a29c', '7efbddea-b507-11f0-936b-b02628506e5c', 'CUST-1768728918-8309', 'Aud', 'Karlsen', NULL, '97762582', NULL, NULL, NULL, NULL, NULL, NULL, 'individual', 'event_registration', 0, 'active', NULL, '2026-01-18 10:35:18', '2026-01-18 10:35:18', NULL),
('1d3d03015dfb1b6e7e23eeac23ae28fde5f3', '7efbddea-b507-11f0-936b-b02628506e5c', 'CUST-1768814386-1559', 'Kjell', 'Ove Moltumyr', NULL, '90118422', NULL, NULL, NULL, NULL, NULL, NULL, 'individual', 'event_registration', 0, 'active', NULL, '2026-01-19 10:19:46', '2026-01-19 10:19:46', NULL),
('1e0d309b046171d91bffb32bf1a570856777', '7efbddea-b507-11f0-936b-b02628506e5c', 'CUST-1768814505-9579', 'John', 'Eugen Tellefsen', NULL, '41515117', NULL, NULL, NULL, NULL, NULL, NULL, 'individual', 'event_registration', 0, 'active', NULL, '2026-01-19 10:21:45', '2026-01-19 10:21:45', NULL),
('2102c860dd03066d60d6066e514506f34d5a', '7efbddea-b507-11f0-936b-b02628506e5c', 'CUST-1768930412-3728', 'Halvard', 'Leine', NULL, '95247887', NULL, NULL, NULL, NULL, NULL, NULL, 'individual', 'event_registration', 0, 'active', NULL, '2026-01-20 18:33:32', '2026-01-20 18:33:32', NULL),
('2306806ac608bafdfc50672144b14c11ee4f', '7efbddea-b507-11f0-936b-b02628506e5c', 'CUST-1768814456-3720', 'Lillian', 'Tellefsen', NULL, '92038171', NULL, NULL, NULL, NULL, NULL, NULL, 'individual', 'event_registration', 0, 'active', NULL, '2026-01-19 10:20:56', '2026-01-19 10:20:56', NULL),
('25cc3f4b5c68868c67f159018d1c8a74f86c', '7efbddea-b507-11f0-936b-b02628506e5c', 'CUST-1768935349-1691', 'Tore', 'Løvøy', NULL, '41307657', NULL, NULL, NULL, NULL, NULL, NULL, 'individual', 'event_registration', 0, 'active', NULL, '2026-01-20 19:55:49', '2026-01-20 19:55:49', NULL),
('5013b784781c70d78dc17e80ef099d33f34e', '7efbddea-b507-11f0-936b-b02628506e5c', 'CUST-1768814274-8850', 'Liv', 'Henny G Sydhagen', NULL, '97754374', NULL, NULL, NULL, NULL, NULL, NULL, 'individual', 'event_registration', 0, 'active', NULL, '2026-01-19 10:17:54', '2026-01-19 10:17:54', NULL),
('6464d76a21886c2e71638c9e85a9ff7889a5', '7efbddea-b507-11f0-936b-b02628506e5c', 'CUST-1768814690-3533', 'Paul', 'Sandanger', NULL, '90895585', NULL, NULL, NULL, NULL, NULL, NULL, 'individual', 'event_registration', 0, 'active', NULL, '2026-01-19 10:24:50', '2026-01-19 10:24:50', NULL),
('69f676b6-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0120', 'Egil', 'Rasmineson Frantsen', 'e-fran@online.no', '90689856', NULL, NULL, 'Gamlemyklebustveg 9', 'Fosnavåg', '6092', NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-21 21:23:20', '2026-01-01 21:42:39', NULL),
('69f67a98-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0121', 'Britt Inger', 'Gusdal Frantsen', 'brittingerbig@gmail.com', '93226717', NULL, NULL, 'GamleMyklebustveg 9', 'Fosnavåg', '6092', NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-21 21:23:21', '2026-01-01 21:42:39', NULL),
('69f67ba4-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0123', 'Atle', 'Kvalsvik', 'atlekarl@tussa.com', '95918925', '1956-04-08', NULL, 'Mulevikvegen 42', 'Nerlandsøy', '6098', NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-21 21:23:21', '2026-01-15 10:37:58', NULL),
('69f67c62-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0124', 'Bente', 'Kvalsvik Notøy', 'bente02@live.no', '48158704', NULL, NULL, 'Breidanesvegen 8', 'Fosnavåg', '6092', NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-21 21:23:22', '2026-01-15 13:43:09', NULL),
('69f67dab-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0125', 'Marion', 'Nilsen', 'marion.nilsen@outlook.com', '97988677', '1955-07-17', NULL, 'Mulevikvegen 42', 'Nerlandsøy', '6098', NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-21 21:23:22', '2026-01-15 10:00:06', NULL),
('69f67e68-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0126', 'Kristin', 'Leine', 'kristinleine@hotmail.no', '99649880', NULL, NULL, 'Jordmor Helgas veg 14', 'Fosnavåg', '6090', NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-21 21:23:22', '2026-01-01 21:42:39', NULL),
('69f67f15-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0127', 'Marita', 'Higraf Remøy', 'maritahr2@gmail.com', '90981991', '1966-04-14', NULL, 'Nedre plassgjerde', 'Nerlandsøy', '6098', NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-21 21:23:23', '2026-01-09 07:29:12', NULL),
('69f67fc2-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0131', 'Oddveig', 'Arnesen', 'oddveig.arnesen@example.com', '45235239', NULL, NULL, 'Nordgardsvegen 4', 'Fosnavåg', '6090', NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-23 18:03:14', '2026-01-01 21:42:39', NULL),
('69f6806e-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0132', 'Asbjørn', 'Bendal', 'abendal@online.no', '95766662', NULL, NULL, 'Kviehaugen 8 B', 'Fosnavåg', '6091', NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-23 18:03:15', '2026-01-01 21:42:39', NULL),
('69f68111-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0133', 'Jan', 'Bugge', 'jan.bugge@example.com', '95125587', NULL, NULL, 'Nedre Hjelmeseth 20', 'Fosnavåg', '6091', NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-23 18:03:15', '2026-01-01 21:42:39', NULL),
('69f681a7-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0134', 'Jan', 'Bø', 'jan.bo@example.com', '48051150', NULL, NULL, 'Sandevegen 6', 'Bølandet', '6095', NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-23 18:03:15', '2026-01-01 21:42:39', NULL),
('69f68244-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0135', 'Reidun', 'Baade', 'reidun23@hotmail.no', '97077208', '1958-03-23', NULL, 'Ospenesvegen 7', 'Fosnavåg', '6092', NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-23 18:03:16', '2026-01-01 21:42:39', NULL),
('69f682f0-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0136', 'Annlaug', 'Eikrem', 'annlaugeikrem@hotmail.com', '46430527', NULL, NULL, 'Voldsundvegen 48', 'Leinøy', '6094', NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-23 18:03:16', '2026-01-01 21:42:39', NULL),
('69f6838a-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0137', 'Myrthel', 'Emblem', 'myrtelemblem@gmail.com', '91849589', NULL, NULL, 'Postboks 25', 'Fosnavåg', '6099', NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-23 18:03:16', '2026-01-01 21:42:39', NULL),
('69f68433-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0138', 'Arild', 'Flusund', 'arild.flusund@gmail.com', '91637514', '1958-10-12', NULL, 'Sandevegen 8', 'Bølandet', '9095', NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-23 18:03:16', '2026-01-12 14:51:34', NULL),
('69f684d1-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0139', 'Arnhild', 'Flusund', 'af@tussa.com', '41235051', '1962-04-29', NULL, 'Sandevegen 8', 'Bølandet', '6095', NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-23 18:03:17', '2026-01-15 10:33:49', NULL),
('69f68574-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0140', 'Idar', 'Frantzen', 'frantsenidar@gmail.com', '40613610', NULL, NULL, 'Ospenesveien 21', 'Fosnavåg', '6092', NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-23 18:03:17', '2026-01-01 21:42:39', NULL),
('69f6861b-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0141', 'Tone Foss', 'Frantzen', 'tone.frantzen@hotmail.com', '95338070', NULL, NULL, 'Holmsildgata 16', 'Fosnavåg', '6090', NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-23 18:03:18', '2026-01-01 21:42:39', NULL),
('69f686c2-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0142', 'Mathias', 'Haugen', 'stottemur@hotmail.com', '45500147', NULL, NULL, 'Myklebustvegen 115', 'Myklebust', '6488', NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-23 18:03:18', '2026-01-01 21:42:39', NULL),
('69f6877f-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0143', 'Robert Bjarne', 'Helde', 'robert.heldeb@gmail.com', '97624748', NULL, NULL, 'Demingane 27', 'Fosnavåg', '6092', NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-23 18:03:18', '2026-01-01 21:42:39', NULL),
('69f68825-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0144', 'Fritjof', 'Husøy', 'husfri@gmail.com', '95223457', NULL, NULL, 'Torvik', 'Bølandet', '6095', NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-23 18:03:19', '2026-01-01 21:42:39', NULL),
('69f688c8-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0145', 'Erik', 'Iversen', 'erik.iversen@kcadeutag.com', '90838258', NULL, NULL, 'Kopperstadvegen 140', 'Nerlandsøy', '6098', NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-23 18:03:19', '2026-01-01 21:42:39', NULL),
('69f68963-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0146', 'Margunn', 'Kalland', 'margunn.kalland@example.com', '91329484', NULL, NULL, 'Massavegen 14', 'Runde', '6096', NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-23 18:03:19', '2026-01-01 21:42:39', NULL),
('69f68a3b-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0147', 'Marit', 'Knutsen', 'marit.knutsen@example.com', '91894371', NULL, NULL, 'Borgarhaugvegen 4', 'Fosnavåg', '6091', NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-23 18:03:19', '2026-01-01 21:42:39', NULL),
('69f68b10-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0149', 'Perly Christin Vikebak', 'Kopperstad', 'perly.kopperstad@outlook.com', '90117868', NULL, NULL, 'Postboks', 'Fosnavåg', '6099', NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-23 18:03:20', '2026-01-01 21:42:39', NULL),
('69f68bbe-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0150', 'Idar', 'Ove Kragset', 'idarovekr@hotmail.com', '90112149', NULL, NULL, 'Breidanesvegen 8', 'Fosnavåg', '6092', NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-23 18:03:20', '2026-01-13 23:22:23', NULL),
('69f68c5d-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0151', 'Myrthel', 'Kvalsund', 'myrthel.kvalsund@example.com', '90588968', NULL, NULL, 'Teigelia 38', 'Nerlandsøy', '6098', NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-23 18:03:20', '2026-01-01 21:42:39', NULL),
('69f68cfe-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0152', 'Per Ove', 'Kvalsund', 'per.ove.kvalsund@example.com', '90688968', NULL, NULL, 'Teigelia 38', 'Nerlandsøy', '6098', NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-23 18:03:21', '2026-01-01 21:42:39', NULL),
('69f68da1-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0153', 'Geir Vangen', 'Kvalsund', 'geirkv@gmail.com', '48248499', NULL, NULL, 'Øvre Plassegjerde 5', 'Nerlandsøy', '6098', NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-23 18:03:21', '2026-01-01 21:42:39', NULL),
('69f68e45-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0154', 'Aksel', 'Kvalsvik', 'aksel.kvalsvik@tussa.com', '97553520', NULL, NULL, 'Øvre Plassgjerdet 61', 'Nerlandsøy', '6098', NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-23 18:03:21', '2026-01-01 21:42:39', NULL),
('69f68ee8-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0155', 'Evy Helen Ryssevik', 'Kvalsvik', 'evy.kvals@gmail.com', '95995513', NULL, NULL, 'Bendalsvegen 8C', 'Fosnavåg', '6091', NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-23 18:03:22', '2026-01-01 21:42:39', NULL),
('69f68f8a-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0156', 'Åse', 'Kvalsvik', 'ase.kvalsvik@example.com', '92451835', NULL, NULL, 'Øvre Plassegjerde 5', 'Nerlandsøy', '6098', NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-23 18:03:22', '2026-01-01 21:42:39', NULL),
('69f69027-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0157', 'Mariann', 'Leinebø', 'mariann.leinebo@example.com', '48294481', NULL, NULL, 'Støylavegne 13', 'Leinøy', '6094', NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-23 18:03:22', '2026-01-01 21:42:39', NULL),
('69f690c7-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0158', 'Gunnar', 'Leinebø', 'gunnar.leinebo@example.com', '91561632', NULL, NULL, 'Støylavegen 13', 'Leinøy', '6094', NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-23 18:03:23', '2026-01-01 21:42:39', NULL),
('69f6916b-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0159', 'Marit Jordan', 'Lyster', 'marit.jordan.lyster@example.com', '95156176', NULL, NULL, 'Leikongvegen 422', 'Gurskøy', '6080', NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-23 18:03:23', '2026-01-01 21:42:39', NULL),
('69f6920a-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0160', 'Mariann', 'Løvold', 'mariann.lovold@hotmail.com', '98482604', NULL, NULL, 'Verket 14', 'Fosnavåg', '6091', NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-23 18:03:23', '2026-01-01 21:42:39', NULL),
('69f692ad-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0161', 'Tore', 'Løvøy', 'tore.lovoy@gmail.com', '41307657', NULL, NULL, 'Sandvikvegen 71', 'Tjørvåg', '6070', NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-23 18:03:24', '2026-01-01 21:42:39', NULL),
('69f69360-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0162', 'Irene Petra', 'Madsen', 'irene.petra.madsen@example.com', '91737567', NULL, NULL, 'Geila 5', 'Nerlandsøy', '6098', NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-23 18:03:24', '2026-01-01 21:42:39', NULL),
('69f6941b-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0163', 'Johan', 'Moltu', 'johan.moltu@gmail.com', '91568576', NULL, NULL, 'Røyravegen 10', 'Bølandet', '6095', NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-23 18:03:24', '2026-01-01 21:42:39', NULL),
('69f694ce-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0164', 'Sølvi', 'Moltu', 'solvi.moltu@tussa.com', '93641550', NULL, NULL, 'Leinøy', 'Leinøy', '6094', NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-23 18:03:24', '2026-01-01 21:42:39', NULL),
('69f695cd-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0165', 'Anne-Jorunn', 'Moltumyr', 'anne.jorunn.moltumyr@example.com', '90940208', NULL, NULL, 'Remøy', 'Leinøy', '6094', NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-23 18:03:25', '2026-01-01 21:42:39', NULL),
('69f6967a-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0166', 'Henning', 'Moltumyr', 'henningmoltumyr@tussa.com', '94143636', NULL, NULL, 'Remøy', 'Leinøy', '6094', NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-23 18:03:25', '2026-01-01 21:42:39', NULL),
('69f6971f-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0167', 'Oddvar', 'Myklebust', 'oddvar.myklebust@example.com', '93600832', NULL, NULL, 'Eggesbøvegen 6 A', 'Fosnavåg', '6092', NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-23 18:03:25', '2026-01-01 21:42:39', NULL),
('69f697bf-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0168', 'Jan Arild', 'Myklebusthaug', 'jamyklebusthaug@gmail.com', '47830680', NULL, NULL, 'Brunholmgata 22', 'Ålesund', '6004', NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-23 18:03:25', '2026-01-01 21:42:39', NULL),
('69f69862-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0170', 'Geir Per', 'Nerland', 'geir.per.nerland@gmail.com', '99308210', NULL, NULL, 'Landsvegen 12', 'Nerlandsøy', '6098', NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-23 18:03:26', '2026-01-01 21:42:39', NULL),
('69f6990c-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0171', 'Jeanette', 'Nerland', 'jeanette@hotmail.com', '97191203', NULL, NULL, 'Landsvegen 12', 'Nerlandsøy', '6098', NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-23 18:03:26', '2026-01-01 21:42:39', NULL),
('69f699af-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0172', 'Oddbjørn', 'Nerland', 'oddbjorn@nerland.io', '47238763', NULL, NULL, 'Svingen 8', 'Fosnavåg', '6092', NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-23 18:03:26', '2026-01-14 11:01:22', NULL),
('69f69a67-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0173', 'Alf', 'Nerland', 'alfnerland@tussa.com', '41660225', NULL, NULL, 'Holmsildgata 16', 'Fosnavåg', '6090', NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-23 18:03:27', '2026-01-01 21:42:39', NULL),
('69f69b0b-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0174', 'Marit', 'Nerlid', 'marit.nerlid@example.com', '97643583', NULL, NULL, 'Kalvegjerde 2', 'Moltustranda', '6076', NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-23 18:03:27', '2026-01-01 21:42:39', NULL),
('69f69bb2-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0175', 'Anita', 'Nilsen', 'anita-nilsen@live.no', '97646387', NULL, NULL, 'Postboks', 'Fosnavåg', '6092', NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-23 18:03:27', '2026-01-01 21:42:39', NULL),
('69f69c55-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0176', 'Gerd Jorunn', 'Nilsen', 'gerd.jorunn.nilsen@example.com', '41302631', NULL, NULL, 'Kyrkjevegen 93', 'Sykkulven', '6230', NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-23 18:03:27', '2026-01-01 21:42:39', NULL),
('69f69cfa-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0177', 'Gunn', 'Nilsen', 'gunn-n@hotmail.com', '93412336', NULL, NULL, 'Bendalsvegen 15B', 'Fosnavåg', '6091', NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-23 18:03:28', '2026-01-01 21:42:39', NULL),
('69f69da2-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0178', 'Bård', 'Nordnes', 'bard.nordnes@example.com', '41734804', NULL, NULL, 'Voldsundvegen 32', 'Leinøy', '6094', NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-23 18:03:28', '2026-01-01 21:42:39', NULL),
('69f69e41-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0179', 'Ingunn', 'Nordnes', 'ingnordnes@hotmail.com', '95752952', NULL, NULL, 'Voldsundvegen 32', 'Leinøy', '6094', NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-23 18:03:28', '2026-01-01 21:42:39', NULL),
('69f69edc-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0180', 'Alis', 'Notøy', 'alis.notoy@example.com', '90857103', NULL, NULL, 'Stornesvegen 8', 'Tjørvåg', '6070', NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-23 18:03:29', '2026-01-01 21:42:39', NULL),
('69f69f83-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0181', 'Mai-Brit', 'Nygarden', 'mai.brit.nygarden@example.com', '95000339', NULL, NULL, 'K.L.Vegen 9', 'Hareid', '6060', NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-23 18:03:29', '2026-01-01 21:42:39', NULL),
('69f6a021-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0182', 'Sigmund', 'Nymark', 'sigmund.nymark@gmail.com', '41932875', NULL, NULL, 'Leione 3A', 'Leinøy', '6094', NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-23 18:03:30', '2026-01-01 21:42:39', NULL),
('69f6a0cf-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0183', 'Eldar Hoan', 'Ose', 'eldar.hoan.ose@example.com', '41506612', NULL, NULL, 'Hoslekervegen 120', 'Ulsteinvik', '6065', NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-23 18:03:30', '2026-01-01 21:42:39', NULL),
('69f6a16d-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0184', 'Endre Johan', 'Ose', 'endrejohan@hotmail.com', '91101955', NULL, NULL, 'Holkestadvegen 6', 'Fosnavåg', '6091', NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-23 18:03:30', '2026-01-01 21:42:39', NULL),
('69f6a20e-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0185', 'Eva Lena', 'Ose', 'trolle-40@hotmail.com', '48172229', NULL, NULL, 'Hoslekervegen 120', 'Ulsteinvik', '6065', NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-23 18:03:30', '2026-01-01 21:42:39', NULL),
('69f6a2bc-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0186', 'Bjørn', 'Prytz', 'bjornprytz@gmail.com', '47393108', NULL, NULL, 'Buråsa 1', 'Fosnavåg', '6090', NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-23 18:03:31', '2026-01-01 21:42:39', NULL),
('69f6a35b-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0187', 'Kristin', 'Rabben', 'kri-rabb@online.no', '90069947', NULL, NULL, 'Brunholmgata 22', 'Ålesund', '6004', NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-23 18:03:31', '2026-01-01 21:42:39', NULL),
('69f6a3f6-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0188', 'Kirsten', 'Reite', 'k.reite@hotmail.com', '95791864', NULL, NULL, 'Kvianesvegen 49', 'Leinøy', '6094', NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-23 18:03:31', '2026-01-14 17:28:12', NULL),
('69f6a493-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0189', 'Ronald', 'Reite', 'rona-re@tussa.com', '41438157', NULL, NULL, 'Kvianesvegen 49', 'Leinøy', '6094', NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-23 18:03:31', '2026-01-15 10:26:25', NULL),
('69f6a53e-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0190', 'Aud', 'Remøy', 'audremoy@gmail.com', '98684879', '1961-06-04', NULL, 'Myrane 20', 'Leinøy', '6094', NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-23 18:03:32', '2026-01-01 21:42:39', NULL),
('69f6a5e9-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0191', 'Kurt', 'Remøy', 'kurt.remoy@tussa.com', '95839656', '1957-11-15', NULL, 'Myrane 20', 'Leinøy', '6094', NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-23 18:03:32', '2026-01-01 21:42:39', NULL),
('69f6a695-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0192', 'Paul Gustav', 'Remøy', 'gustav.jarle@example.com', '87654321', NULL, NULL, 'Remøya 22', 'Leinøy', '6094', NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-23 18:03:32', '2026-01-01 21:42:39', NULL),
('69f6a737-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0193', 'Sveinung', 'Remøy', 'sveinung.remoy@example.com', '48195217', NULL, NULL, 'Nedre Plassgjerde 7', 'Nerlandsøy', '6098', NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-23 18:03:33', '2026-01-01 21:42:39', NULL),
('69f6a809-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0194', 'Ellinor', 'Rinde', 'ellinor.rinde@hotmail.com', '41257965', NULL, NULL, 'Ædane 9', 'Gurskøy', '6080', NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-23 18:03:33', '2026-01-01 21:42:39', NULL),
('69f6a8af-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0195', 'Liv', 'Runde', 'liv.runde@example.com', '92260396', NULL, NULL, 'Rundevegen 10', 'Runde', '6096', NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-23 18:03:33', '2026-01-01 21:42:39', NULL),
('69f6a948-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0196', 'Frode', 'Ryssevik', 'frode.ryssevik@example.com', '97521996', NULL, NULL, 'Bendalsvegen 6C', 'Fosnavåg', '6091', NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-23 18:03:34', '2026-01-01 21:42:39', NULL),
('69f6a9e9-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0197', 'Palmar', 'Rønnestad', 'palmar.ronnestad@gmail.com', '99011940', '1954-01-18', NULL, 'Nedre Røyra 1', NULL, NULL, NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-23 18:03:34', '2026-01-01 21:42:39', NULL),
('69f6aa94-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0198', 'Sølvi', 'Røren', 'solvi.roren@example.com', '97966560', NULL, NULL, 'Vågsgata 45', 'Fosnavåg', '6090', NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-23 18:03:34', '2026-01-01 21:42:39', NULL),
('69f6aeb9-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0199', 'Ann', 'Sandanger', 'annhsandanger@gmail.com', '95789630', NULL, NULL, 'Nedre Hjelmeseth 20', 'Fosnavåg', '6091', NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-23 18:03:34', '2026-01-01 21:42:39', NULL),
('69f6af79-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0200', 'Hans', 'Sande', 'hanssande@tussa.com', '92459392', NULL, NULL, 'Sandevegen 47', 'Bølandet', '6095', NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-23 18:03:35', '2026-01-01 21:42:39', NULL),
('69f6b030-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0201', 'Petra', 'Sandvik', 'petra.sandvik@example.com', '45035591', NULL, NULL, 'Tjørvågvegen 2', 'Tjørvåg', '6070', NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-23 18:03:35', '2026-01-01 21:42:39', NULL),
('69f6b0ed-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0202', 'Roar', 'Sandvik', 'solvi.moltu2@tussa.com', '90116870', NULL, NULL, 'Nautøya 90', 'Tjørvåg', '6070', NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-23 18:03:35', '2026-01-01 21:42:39', NULL),
('69f6b19b-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0203', 'Oddvar', 'Skogen', 'oddvar.skogen@example.com', '47857099', NULL, NULL, 'Vågsgata 45', 'Fosnavåg', '6090', NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-23 18:03:35', '2026-01-01 21:42:39', NULL),
('69f6b254-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0204', 'Roar', 'Skoglund', 'rokriskog@gmail.com', '91369350', NULL, NULL, 'Svingen 8', 'Fosnavåg', '6092', NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-23 18:03:36', '2026-01-01 21:42:39', NULL),
('69f6b2f6-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0205', 'Arild', 'Slettestøl', 'arild-j@live.com', '91688928', NULL, NULL, 'Knotten 130', 'Gursken', '6082', NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-23 18:03:36', '2026-01-01 21:42:39', NULL),
('69f6b39b-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0206', 'Aslak', 'Solbjørg', 'asolborg@aolnorge.no', '46891090', NULL, NULL, 'Nautøya 125', 'Tjørvåg', '6070', NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-23 18:03:36', '2026-01-01 21:42:39', NULL),
('69f6b467-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0207', 'Oddveig', 'Stanghelle', 'oddveig_sta@hotmail.com', '99649879', NULL, NULL, 'Leinevegen 272', 'Leinøy', '6094', NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-23 18:03:37', '2026-01-01 21:42:39', NULL),
('69f6b516-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0208', 'Liv Bessie', 'Stave', 'livstave@online.no', '95768617', NULL, NULL, 'Osebergvegen 5', 'Ulsteinvik', '6065', NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-23 18:03:37', '2026-01-01 21:42:39', NULL),
('69f6b5b8-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0209', 'Per Egil', 'Sørheller', 'peregilsorheller@gmail.com', '45906393', NULL, NULL, 'Brauta 3', 'Eidsnes', '6037', NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-23 18:03:37', '2026-01-01 21:42:39', NULL),
('69f6b668-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0210', 'Kirsten', 'Teige', 'kirsten.teige@example.com', '40086106', NULL, NULL, 'Teigeveien 47', 'Nerlandsøy', '6098', NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-23 18:03:38', '2026-01-01 21:42:39', NULL),
('69f6b706-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0211', 'Oluf', 'Teige', 'oluf.teige@tussa.com', '97525423', '2025-09-15', NULL, 'Teigeveien 47', 'Nerlandsøy', '6098', NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-23 18:03:38', '2026-01-01 21:42:39', NULL),
('69f6b7b6-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0212', 'Anne-Johanne', 'Tellefsen', 'anne.johanne.tellefsen@gmail.com', '90846683', NULL, NULL, 'Teigelia 6', 'Nerlandsøy', '6098', NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-23 18:03:38', '2026-01-01 21:42:39', NULL),
('69f6b862-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0213', 'Solfrid', 'Thorset', 'solfrid.thorset@example.com', '95766727', NULL, NULL, 'Leikongbakken 4', 'Gurskøy', '6080', NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-23 18:03:39', '2026-01-01 21:42:39', NULL),
('69f6b90a-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0214', 'Kjellmar', 'Vike', 'kjellmar50@gmail.com', '90863096', '2025-08-25', NULL, 'Grønhaugvegen 34', 'Fosnavåg', '6092', NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-23 18:03:39', '2026-01-01 21:42:39', NULL),
('69f6b9ae-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0215', 'Laila', 'Vike', 'tull@111.no', '99162331', '1963-06-15', NULL, 'Grønhaugvegen 34', 'Fosnavåg', '6092', NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-23 18:03:39', '2026-01-01 21:42:39', NULL),
('69f6ba63-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0216', 'Idar', 'Voldsund', 'idarvoldsund@tussa.com', '99308221', NULL, NULL, 'Arhaugvegen 14', 'Bølandet', '6095', NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-23 18:03:40', '2026-01-01 21:42:39', NULL),
('69f6bb04-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0217', 'Anne-Marie', 'Westbø', 'annemawest4268@gmail.com', '91804691', NULL, NULL, 'Torvikvegen 43', 'Bølandet', '6095', NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-23 18:03:40', '2026-01-01 21:42:39', NULL),
('69f6bbab-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0218', 'Hallgeir', 'Åsebø', 'hallgeir66@hotmail.com', '95753664', NULL, NULL, 'Ædane 9', 'Gurskøy', '6080', NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-23 18:03:40', '2026-01-01 21:42:39', NULL),
('69f6bc66-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0224', 'Unni', 'Nerland Nerland', 'unni@nerland.io', '45474106', NULL, NULL, 'Svingen 8', NULL, NULL, NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-28 13:33:45', '2026-01-14 11:01:22', NULL),
('69f6bd19-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0225', 'Linda Myrseth', 'Kvalsund', 'linda43@online.no', '93058908', NULL, NULL, 'Øvre plassgjerdet 5', 'Nerlandsøy', '6098', NULL, 'individual', NULL, 0, 'active', NULL, '2025-07-30 22:09:05', '2026-01-01 21:42:39', NULL),
('69f6bdcd-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0227', 'Laila Nedreberg Remøy', 'Remøy', 'laila@remoy.org', '40873948', '1962-08-21', NULL, 'Nedre plassgjerde 7', NULL, NULL, NULL, 'individual', NULL, 0, 'active', NULL, '2025-08-22 11:06:47', '2026-01-01 21:42:39', NULL),
('69f6be79-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0228', 'Halvard', 'Leine', 'halvard.leine@tussa.com', '95247887', '1956-11-01', NULL, 'Leine 41', NULL, NULL, NULL, 'individual', NULL, 0, 'active', NULL, '2025-08-22 23:11:51', '2026-01-01 21:42:39', NULL),
('69f6bf19-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0229', 'Svein Arne', 'Leine', 'tull@one.no', '97069105', '1953-07-25', NULL, 'Bergslia 3', NULL, NULL, NULL, 'individual', NULL, 0, 'active', NULL, '2025-08-22 23:12:03', '2026-01-01 21:42:39', NULL),
('69f6bfce-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0230', 'Kristine Bratli', 'Leine', NULL, '97500786', '1960-09-30', NULL, 'Leine 41', NULL, NULL, NULL, 'individual', NULL, 0, 'active', NULL, '2025-08-22 23:12:07', '2026-01-21 01:09:30', NULL),
('69f6c081-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0231', 'Hildegunn', 'Øye', 'hildegunnoddrun@gmail.com', '47030489', '1955-03-14', NULL, 'Øvre', NULL, NULL, NULL, 'individual', NULL, 0, 'active', NULL, '2025-08-22 23:12:11', '2026-01-16 17:09:00', NULL),
('69f6c12a-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0232', 'Kari Iren', 'Sandvik', 'kariharisan@hotmail.com', '47863533', '1953-04-15', NULL, 'Eggesbøgrenda 6 A', NULL, NULL, NULL, 'individual', NULL, 0, 'active', NULL, '2025-09-02 13:46:28', '2026-01-01 21:42:39', NULL),
('69f6c1d5-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0233', 'Alf', 'Inge Leine', 'alf.inge.leine@gmail.com', '90636950', NULL, NULL, 'Nedre Plassegjerdet 15', NULL, NULL, NULL, 'individual', NULL, 0, 'active', NULL, '2025-09-07 14:39:01', '2026-01-09 07:29:40', NULL),
('69f6c282-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0234', 'Gry', 'Myrseth Myrseth', 'gry.myrseth@gmail.com', '95793923', '1961-12-11', NULL, 'Hulldalsbana 4 A', NULL, NULL, NULL, 'individual', NULL, 0, 'active', NULL, '2025-09-10 12:39:36', '2026-01-15 17:34:52', NULL),
('69f6c326-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0235', 'Lise Hauge', 'Kvalsund', 'lise.kvalsund@hotmail.com', '95058708', '1959-08-29', NULL, 'Bakkevn 9', NULL, NULL, NULL, 'individual', NULL, 0, 'active', NULL, '2025-09-10 12:42:02', '2026-01-01 21:42:39', NULL),
('69f6c3ce-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0236', 'Oddgeir Madsen', 'Madsen', 'oddgeir.madsen@tussa.com', '91681611', '1955-05-21', NULL, 'Teigelia 4', NULL, NULL, NULL, 'individual', NULL, 0, 'active', NULL, '2025-09-11 07:09:22', '2026-01-01 21:42:39', NULL),
('69f6c472-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0237', 'Karen Myrseth', 'Moltumyr', 'ksmoltumyr@gmail.com', '95966318', NULL, NULL, 'Markveien 9', NULL, NULL, NULL, 'individual', NULL, 0, 'active', NULL, '2025-09-17 09:04:43', '2026-01-01 21:42:39', NULL),
('69f6c514-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0238', 'Reidun', 'Frantzen', 'reidun.frantzen@hotmail.com', '47044251', '1965-11-18', NULL, 'Toftestøvegen 24', NULL, NULL, NULL, 'individual', NULL, 0, 'active', NULL, '2025-09-17 22:06:48', '2026-01-01 21:42:39', NULL),
('69f6c5b8-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0239', 'Vilfred', 'Pettersen', 'vilfred.pettersen@example.com', NULL, NULL, NULL, 'INNHENT', NULL, NULL, NULL, 'individual', NULL, 0, 'active', NULL, '2025-09-17 22:09:37', '2026-01-01 21:42:39', NULL),
('69f6c667-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0240', 'Karl Sverre', 'Moltumyr', 'ksmolrumyr@gmail.com', '48281188', NULL, NULL, 'Merkveien 9', NULL, NULL, NULL, 'individual', NULL, 0, 'active', NULL, '2025-09-19 08:25:07', '2026-01-01 21:42:39', NULL),
('69f6c70d-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0241', 'Paul', 'Sandanger', 'paul.sandanger@tussa.com', '90895585', '1950-05-04', NULL, 'Gjerdevegen 10', NULL, NULL, NULL, 'individual', NULL, 0, 'active', NULL, '2025-09-21 20:40:23', '2026-01-01 21:42:39', NULL),
('69f6c83b-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0242', 'Jorunn', 'Berge', 'lola58@live.no', '91894452', '1958-04-14', NULL, 'Bergsvegen 39', NULL, NULL, NULL, 'individual', NULL, 0, 'active', NULL, '2025-09-21 20:40:29', '2026-01-01 21:42:39', NULL),
('69f6c8e2-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0243', 'Marina Hoff', 'Myking', 'marinamyking@hotmail.com', '91586793', NULL, NULL, 'Støylavegen 11A', 'Leinøy', '6094', NULL, 'individual', NULL, 0, 'active', NULL, '2025-09-23 17:12:47', '2026-01-01 21:42:39', NULL),
('69f6c98f-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0244', 'Susann', 'Nerland', 'susannno@yahoo.no', '99393147', '2025-04-15', NULL, 'Landsvegen 18', 'Nerlandsøy', '6098', NULL, 'individual', NULL, 0, 'active', NULL, '2025-09-24 20:26:13', '2026-01-01 21:42:39', NULL),
('69f6ca39-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0245', 'Toril Zahl', 'Remøy', 'toril.zahlr@hotmail.no', '41646461', '1965-08-22', NULL, 'Myrane 6', 'Leinøy', '6094', NULL, 'individual', NULL, 0, 'active', NULL, '2025-09-24 22:18:04', '2026-01-01 21:42:39', NULL),
('69f6cae8-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0246', 'Norunn', 'Kragseth', 'magne.kragseth@tussa.com', '95433899', NULL, NULL, 'Kopperstadvegen 47', NULL, NULL, NULL, 'individual', NULL, 0, 'active', NULL, '2025-09-25 11:40:56', '2026-01-01 21:42:39', NULL),
('69f6cb8b-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0247', 'Bård Remøy', 'Remøy', 'baard_remoy@hotmail.com', '95724601', NULL, NULL, 'Kvanesveien 45', 'Leinøy', '6094', NULL, 'individual', NULL, 0, 'active', NULL, '2025-09-25 20:38:29', '2026-01-01 21:42:39', NULL),
('69f6cc34-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0248', 'Audhild', 'Kvalsvik', 'audhild.kvalsvik@outlook.com', '99570874', '1957-03-12', NULL, 'Valvegen 1D', 'Fosnavåg', '6092', NULL, 'individual', NULL, 0, 'active', NULL, '2025-09-26 21:35:28', '2026-01-01 21:42:39', NULL),
('69f6ccde-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0249', 'Ole Jonny', 'Konningen', 'konningen@icloud.com', '90996351', '1959-02-09', NULL, 'Nedre Plassegjerdet 11', 'Nerlandsøy', '6098', NULL, 'individual', NULL, 0, 'active', NULL, '2025-09-26 21:37:00', '2026-01-01 21:42:39', NULL),
('69f6cd80-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0250', 'Marit Kvalsund', 'Konningen', 'marit.konningen@hotmail.com', '92461475', '1960-02-07', NULL, 'Nedre Plassegjerdet 11', 'Vatne', '6098', NULL, 'individual', NULL, 0, 'active', NULL, '2025-09-26 21:37:12', '2026-01-01 21:42:39', NULL),
('69f6ce23-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0251', 'Eivind', 'Longva', 'elongva@gmail.com', '93027251', '1959-10-23', NULL, 'Vågsholmvegen 19', 'FosnavÅg', '6090', NULL, 'individual', NULL, 0, 'active', NULL, '2025-09-26 21:38:07', '2026-01-01 21:42:39', NULL),
('69f6cec9-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0252', 'Mona Zahl', 'Longva', 'monazl@hotmail.com', '99501291', NULL, NULL, 'Vågsholmvegen 19', 'Fosnavåg', '6090', NULL, 'individual', NULL, 0, 'active', NULL, '2025-09-26 21:38:14', '2026-01-01 21:42:39', NULL),
('69f6cf6c-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0253', 'Per Jarle', 'Skar', 'per.jarle.skar@example.com', '95813437', NULL, NULL, 'Demingane 14', 'Fosnavåg', '6092', NULL, 'individual', NULL, 0, 'active', NULL, '2025-09-29 15:18:33', '2026-01-01 21:42:39', NULL),
('69f6d012-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0254', 'Venche', 'Lannerholm', 'venchelann@hotmail.com', '95498886', NULL, NULL, 'Knotten 130', 'Gursken', '6082', NULL, 'individual', NULL, 0, 'active', NULL, '2025-09-29 18:42:28', '2026-01-01 21:42:39', NULL),
('69f6d0c4-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0255', 'Karin', 'Bø', 'karin.bo15@gmail.com', '41224947', NULL, NULL, 'Sandevegen 6', 'Bølandet', '6095', NULL, 'individual', NULL, 0, 'active', NULL, '2025-09-29 18:52:07', '2026-01-01 21:42:39', NULL),
('69f6d169-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0256', 'Sigurd', 'Notøy', 'sigmarnotoy@gmail.com', '90645690', NULL, NULL, 'Stornesvegen 8', 'Tjørvåg', '6070', NULL, 'individual', NULL, 0, 'active', NULL, '2025-09-29 18:53:45', '2026-01-01 21:42:39', NULL),
('69f6d211-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0257', 'Bergljot', 'Myrseth', 'bergljot.myrseth@example.com', '90025016', NULL, NULL, 'Mikkelhaugen 9', 'Fosnavåg', '6092', NULL, 'individual', NULL, 0, 'active', NULL, '2025-09-29 18:56:17', '2026-01-01 21:42:39', NULL),
('69f6d2b4-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0258', 'Jostein', 'Mo', 'jo-i-mo@hotmail.com', '90683835', NULL, NULL, 'Mosgeila', 'Ørsta', '6154', NULL, 'individual', NULL, 0, 'active', NULL, '2025-09-29 18:57:55', '2026-01-01 21:42:39', NULL),
('69f6d35b-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0259', 'Anne', 'Kristensen', 'tull2@111.no', '94891113', NULL, NULL, 'Storbakken 2', 'Fosnavåg', '6092', NULL, 'individual', NULL, 0, 'active', NULL, '2025-09-29 19:00:58', '2026-01-01 21:42:39', NULL),
('69f6d409-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0260', 'Oddveig', 'Garvik', 'Oddveig.petrin.garvik@outlook.com', '99611182', NULL, NULL, 'Mikkelhaugen 6', 'Fosnavåg', '6092', NULL, 'individual', NULL, 0, 'active', NULL, '2025-09-29 20:05:44', '2026-01-01 21:42:39', NULL),
('69f6d4ae-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0261', 'Laila', 'Berge', 'berge_laila35@hotmail.com', '41498722', '1959-11-16', NULL, 'Elsebøveien 17', 'Fosnavåg', '6091', NULL, 'individual', NULL, 0, 'active', NULL, '2025-09-29 20:05:56', '2026-01-01 21:42:39', NULL),
('69f6d558-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0262', 'Guttorm', 'Berge', 'gube53@hotmail.com', '97777840', '1953-05-11', NULL, 'Flusundvegen 53', 'Fosnavåg', '6091', NULL, 'individual', NULL, 0, 'active', NULL, '2025-09-29 20:06:07', '2026-01-10 21:53:40', NULL),
('69f6d5fb-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0263', 'Line', 'Nygard', 'linenyga@hotmail.com', '95725121', '1969-02-18', NULL, 'Flusundvegen 53', 'Fosnavåg', '6091', NULL, 'individual', NULL, 0, 'active', NULL, '2025-09-29 20:06:15', '2026-01-13 22:08:41', NULL),
('69f6d6a3-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0264', 'Anita H', 'Kvalsund', 'anitah.kvalsund@hotmail.com', '91107083', '1959-07-17', NULL, 'Koperstadvegen 43', 'NERLANDSØY', '6098', NULL, 'individual', NULL, 0, 'active', NULL, '2025-09-30 19:17:30', '2026-01-01 21:42:39', NULL),
('69f6d752-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0265', 'Runar', 'Kvalsund', 'runar.kvalsund@tussa.com', '47015195', '1958-02-21', NULL, 'Koperstadvegen 43', 'Nerlandsøy', '6098', NULL, 'individual', NULL, 0, 'active', NULL, '2025-09-30 19:17:38', '2026-01-01 21:42:39', NULL),
('69f6d7f7-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0266', 'Perny Hilde', 'Kvalsvik', 'pernyh58@gmail.com', '92842285', '1958-09-19', NULL, 'Nesevegen 5', 'Ulstein', '6065', NULL, 'individual', NULL, 0, 'active', NULL, '2025-09-30 20:54:49', '2026-01-01 21:42:39', NULL),
('69f6d89d-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0267', 'Borghild', 'Hjelmeseth', 'hjelmrsethborghild@gmail.com', '95270207', NULL, NULL, 'Vareleitebakkane 7b', 'Ulstein', '6065', NULL, 'individual', NULL, 0, 'active', NULL, '2025-10-01 14:22:06', '2026-01-01 21:42:39', NULL),
('69f6d946-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0268', 'Rune', 'Hjelmeseth', 'ruhjelmeseth@gmail.com', '48275916', NULL, NULL, 'Vareleitebakkane 7b', 'Ulstein', '6065', NULL, 'individual', NULL, 0, 'active', NULL, '2025-10-01 14:22:11', '2026-01-01 21:42:39', NULL),
('69f6d9ea-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0269', 'Ove Knut', 'Hansen', 'ove.k.hansen@hotmail.no', '41927870', NULL, NULL, 'Smålegane 30', 'Fosnavåg', '6091', NULL, 'individual', NULL, 0, 'active', NULL, '2025-10-01 14:23:55', '2026-01-01 21:42:39', NULL),
('69f6da8f-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0270', 'Berit Oline', 'Hansen', 'berit.oline.hansen@gmail.com', '97616902', NULL, NULL, 'Smålegane 30', 'Fosnavåg', '6091', NULL, 'individual', NULL, 0, 'active', NULL, '2025-10-01 14:24:00', '2026-01-01 21:42:39', NULL),
('69f6db36-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0271', 'Sigrunn', 'Skoge', 'sigrunnskoge@live.no', '91747050', '1953-04-12', NULL, 'Gjerdevegen 10', 'Hareid', '6060', NULL, 'individual', NULL, 0, 'active', NULL, '2025-10-01 14:29:30', '2026-01-01 21:42:39', NULL),
('69f6dbdc-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0273', 'Ester Johanne', 'Higraff', 'esterhigraff@hotmail.com', '91133307', NULL, NULL, 'Øvre Plassegjerdet 15', 'Nerlandsøy', '6098', NULL, 'individual', NULL, 0, 'active', NULL, '2025-10-05 21:33:33', '2026-01-01 21:42:39', NULL),
('69f6dc81-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0274', 'Julianne', 'Higraff', 'julianne.higraff@gmail.com', '99362199', '1963-04-05', NULL, 'Mjelthaugvegen 28', 'Nerlandsøy', '6098', NULL, 'individual', NULL, 0, 'active', NULL, '2025-10-14 21:20:56', '2026-01-01 21:42:39', NULL),
('69f6dd2a-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0275', 'Tore Olav', 'Relling', 'rellingtoreolav@gmail.com', '96613458', '1957-05-09', NULL, 'Mjelthaugvegen 28', 'Nerlandsøy', '6098', NULL, 'individual', NULL, 0, 'active', NULL, '2025-10-14 21:21:08', '2026-01-01 21:42:39', NULL),
('69f6de09-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0276', 'Gunlaug', 'Frantsen', 'gunlaugfrantsen@gmail.com', '97478971', '2025-09-23', NULL, 'Ospenesvegen 7', 'Fosnavåg', '6092', NULL, 'individual', NULL, 0, 'active', NULL, '2025-10-16 23:27:53', '2026-01-01 21:42:39', NULL),
('69f6dee5-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0277', 'Per Martin', 'Hjelmeseth', 'per.hjelmeseth@hotmail.com', '90578620', '1951-02-10', NULL, 'Øvre Bergslia4', 'Fosnavåg', '6091', NULL, 'individual', NULL, 0, 'active', NULL, '2025-10-16 23:28:13', '2026-01-01 21:42:39', NULL),
('69f6dfb5-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0278', 'Asveig', 'Kvalsund', 'pak@tussa.com', '97062584', '1954-12-06', NULL, 'Myravegen 20', 'Nerlandsøy', '6098', NULL, 'individual', NULL, 0, 'active', NULL, '2025-10-16 23:28:20', '2026-01-01 21:42:39', NULL),
('69f6e060-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0279', 'Trond', 'Tellefsen', 'trond.tellefsen@oneco.no', '91729150', NULL, NULL, NULL, NULL, NULL, NULL, 'individual', NULL, 0, 'active', NULL, '2025-10-17 20:01:10', '2026-01-01 21:42:39', NULL),
('69f6e111-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0280', 'Leidulf', 'Helde', 'tull3@one.no', '41646426', NULL, NULL, 'x', NULL, NULL, NULL, 'individual', NULL, 0, 'active', NULL, '2025-10-17 20:02:42', '2026-01-01 21:42:39', NULL),
('69f6e1bf-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0281', 'Elisabet Larsen', 'Voldnes', 'Elvoldnes@gmail.com', '48180039', '1956-08-03', NULL, 'Eggesbøvegen 57', 'Fosnavåg', '6092', NULL, 'individual', NULL, 0, 'active', NULL, '2025-10-23 14:04:40', '2026-01-01 21:42:39', NULL),
('69f6e264-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0282', 'Signhild', 'Skorpen', 'fruskorpen@hotmail.com', '4790984170', NULL, NULL, 'Holmsildgata 16', 'FosnavÅg', '6090', NULL, 'individual', NULL, 0, 'active', NULL, '2025-10-23 14:04:46', '2026-01-01 21:42:39', NULL),
('69f6e30e-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0283', 'Hege N.', 'Vestavik', 'hegenv@tussa.com', '+4791802900', NULL, NULL, 'Nedre Sørlia 26', 'Fosnavåg', '6092', NULL, 'individual', NULL, 0, 'active', NULL, '2025-10-23 14:06:17', '2026-01-01 21:42:39', NULL),
('69f6e3af-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0284', 'Leni', 'Stenersen', 'leni.stenersen@gmail.com', '90014296', NULL, NULL, 'Kleivaneset 14b', 'Fosnavåg', '6090', NULL, 'individual', NULL, 0, 'active', NULL, '2025-10-23 14:06:37', '2026-01-01 21:42:39', NULL),
('69f6e453-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0286', 'Arne', 'Voldnes', 'arne.voldnes@gmail.com', '48180040', '1946-08-13', NULL, 'Eggesbøvegen 57', 'Fosnavåg', '6092', NULL, 'individual', NULL, 0, 'active', NULL, '2025-10-26 09:37:08', '2026-01-01 21:42:39', NULL),
('69f6e4ff-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0287', 'Jan', 'Leine', 'jan.leine57@gmail.com', '91689870', NULL, NULL, 'Mikkelhaugen6', 'Fosnavåg', '6092', NULL, 'individual', NULL, 0, 'active', NULL, '2025-10-29 15:47:21', '2026-01-01 21:42:39', NULL);
INSERT INTO `tenant_customers` (`id`, `company_id`, `customer_code`, `first_name`, `last_name`, `email`, `phone`, `date_of_birth`, `company_name`, `address_street`, `address_city`, `address_zip`, `address_country`, `customer_type`, `source`, `payment_status`, `status`, `notes`, `created_at`, `updated_at`, `last_audit_log_id`) VALUES
('69f6e5a4-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0288', 'Marit Bøe', 'Rygh', 'matritboe@gmail.com', '95140474', NULL, NULL, 'Bøvegen 48', 'Bølandet', '6095', NULL, 'individual', NULL, 0, 'active', NULL, '2025-10-30 11:40:41', '2026-01-01 21:42:39', NULL),
('69f6e646-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0289', 'Tom', 'Rygh', 'tomrygh@gmail.com', '91135955', NULL, NULL, 'Bøvegen 48', 'Bølandet', '6095', NULL, 'individual', NULL, 0, 'active', NULL, '2025-10-30 11:40:44', '2026-01-01 21:42:39', NULL),
('69f6e6ee-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0290', 'Idar', 'Warholm', 'warholm59@gmail.com', '91771088', NULL, NULL, 'Leitet 22', 'Leinøy', '6094', NULL, 'individual', NULL, 0, 'active', NULL, '2025-11-20 12:20:46', '2026-01-01 21:42:39', NULL),
('69f703b0-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0291', 'Nina Sundnes', 'Warholm', 'ninawarholm@gmail.com', '90823533', NULL, NULL, 'Leitet 22', 'Leinøy', '6094', NULL, 'individual', NULL, 0, 'active', NULL, '2025-11-20 12:20:52', '2026-01-01 21:42:39', NULL),
('69f70470-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0292', 'Bente', 'Jacobsen', 'bentehvaa@hotmail.com', '93040823', NULL, NULL, 'Monsplassen 14', 'Moltustranda', '6076', NULL, 'individual', NULL, 0, 'active', NULL, '2025-11-22 21:30:09', '2026-01-01 21:42:39', NULL),
('69f705ec-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0293', 'Oddvar', 'Nykrem', 'onykrem@icloud.com', '99737033', NULL, NULL, 'Elsebøvegen 23', 'Fosnavåg', '6091', NULL, 'individual', NULL, 0, 'active', NULL, '2025-11-30 19:05:16', '2026-01-01 21:42:39', NULL),
('69f7069a-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0294', 'Turid', 'Tunheim', 'turidjt@gmail.com', '91132191', NULL, NULL, 'Elsebøvegen 23', 'Fosnavåg', '6091', NULL, 'individual', NULL, 0, 'active', NULL, '2025-11-30 19:05:22', '2026-01-01 21:42:39', NULL),
('69f7074e-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0295', 'Torill', 'Slenes', 'torilslene@gmail.com', '977906673', NULL, NULL, 'Breidenesvegen 2', 'Fosnavåg', '6092', NULL, 'individual', NULL, 0, 'active', NULL, '2025-12-04 21:27:45', '2026-01-01 21:42:39', NULL),
('69f7080e-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0296', 'Atle', 'Leine', 'atlelein@online.no', '90658232', NULL, NULL, 'Breidenesvegen 2', 'Fosnavåg', '6092', NULL, 'individual', NULL, 0, 'active', NULL, '2025-12-04 21:27:50', '2026-01-01 21:42:39', NULL),
('69f708e9-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0297', 'Tor', 'Myrhol', 'tmyrhol@gmail.com', '92085470', NULL, NULL, 'Nørvågvegen 58K', 'Fosnavåg', '6090', NULL, 'individual', NULL, 0, 'active', NULL, '2025-12-06 10:13:53', '2026-01-01 21:42:39', NULL),
('69f709bb-e752-11f0-ada2-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'M0298', 'Eli', 'Myrhol', 'eli.myrhol@gmail.com', '92085470', NULL, NULL, 'Nørvågvegen 58 K', 'Fosnavåg', '6090', NULL, 'individual', NULL, 0, 'active', NULL, '2025-12-06 10:29:21', '2026-01-01 21:42:39', NULL),
('77b10bd993e868df39f717b0d64bd9ad065f', '7efbddea-b507-11f0-936b-b02628506e5c', 'CUST-1768814329-8081', 'Ann', 'Hilde Tellefsen', NULL, '92260189', NULL, NULL, NULL, NULL, NULL, NULL, 'individual', 'event_registration', 0, 'active', NULL, '2026-01-19 10:18:49', '2026-01-19 10:18:49', NULL),
('7d6b1cb415df4945be13ebb8ea584412044c', '7efbddea-b507-11f0-936b-b02628506e5c', 'CUST-1768814596-9259', 'Jan', 'Bugge', NULL, '95125587', NULL, NULL, NULL, NULL, NULL, NULL, 'individual', 'event_registration', 0, 'active', NULL, '2026-01-19 10:23:16', '2026-01-19 10:23:16', NULL),
('857ed927a50e36a8f77043d921965c28b3c9', '7efbddea-b507-11f0-936b-b02628506e5c', 'CUST-1768930412-5570', 'Kristine', 'Bratli Leine', NULL, '97500786', NULL, NULL, NULL, NULL, NULL, NULL, 'individual', 'event_registration', 0, 'active', NULL, '2026-01-20 18:33:32', '2026-01-20 18:33:32', NULL),
('9862de48792bdf9248ba5fe1017fa503d69b', '7efbddea-b507-11f0-936b-b02628506e5c', 'CUST-1768898987-5791', 'Sigrunn', 'Skoge', NULL, '91747050', NULL, NULL, NULL, NULL, NULL, NULL, 'individual', 'event_registration', 0, 'active', NULL, '2026-01-20 09:49:47', '2026-01-20 09:49:47', NULL),
('98a34d2143378e70aa3a7cebe7a2da4c5453', '7efbddea-b507-11f0-936b-b02628506e5c', 'CUST-1768728918-2311', 'Karsten', 'Bendal', NULL, '90889939', NULL, NULL, NULL, NULL, NULL, NULL, 'individual', 'event_registration', 0, 'active', NULL, '2026-01-18 10:35:18', '2026-01-18 10:35:18', NULL),
('9aeaa4a51b0b8451adec03ce6bbe0898a859', '7efbddea-b507-11f0-936b-b02628506e5c', 'CUST-1768896822-4630', 'Kristin', 'Leine', NULL, '99649880', NULL, NULL, NULL, NULL, NULL, NULL, 'individual', 'event_registration', 0, 'active', NULL, '2026-01-20 09:13:42', '2026-01-20 09:13:42', NULL),
('9d4522d0bb354d2e8dc8976571b46ac6cb0c', '7efbddea-b507-11f0-936b-b02628506e5c', 'CUST-1768728918-1781', 'Laila', 'Berge', NULL, '41498722', NULL, NULL, NULL, NULL, NULL, NULL, 'individual', 'event_registration', 0, 'active', NULL, '2026-01-18 10:35:18', '2026-01-18 10:35:18', NULL),
('a467996f28c150ab799bb9e5d247b42c46a3', '7efbddea-b507-11f0-936b-b02628506e5c', 'CUST-1768814213-8576', 'Gunnar', 'Sydhagen', NULL, '95127003', NULL, NULL, NULL, NULL, NULL, NULL, 'individual', 'event_registration', 0, 'active', NULL, '2026-01-19 10:16:53', '2026-01-19 10:16:53', NULL),
('aa9d757529e17a7420904344cfd7f705bedf', '7efbddea-b507-11f0-936b-b02628506e5c', 'CUST-1768728918-8913', 'Ronald', 'Karlsen', NULL, '95881531', NULL, NULL, NULL, NULL, NULL, NULL, 'individual', 'event_registration', 0, 'active', NULL, '2026-01-18 10:35:18', '2026-01-18 10:35:18', NULL),
('b3c2402d19798ea78105f89a547a45605e36', '7efbddea-b507-11f0-936b-b02628506e5c', 'CUST-1768690813-5221', 'Andor', 'Kvalsund', NULL, '41226405', NULL, NULL, NULL, NULL, NULL, NULL, 'individual', 'event_registration', 0, 'active', NULL, '2026-01-18 00:00:13', '2026-01-18 00:00:13', NULL),
('b5494d56f18d3438a2c8a4af9d76817356ab', '7efbddea-b507-11f0-936b-b02628506e5c', 'CUST-1768814731-5126', 'Sigrunn', 'Skoge', NULL, '91747050', NULL, NULL, NULL, NULL, NULL, NULL, 'individual', 'event_registration', 0, 'active', NULL, '2026-01-19 10:25:31', '2026-01-19 10:25:31', NULL),
('b5578a551dfa2b0a2a2325802764ff5641cd', '7efbddea-b507-11f0-936b-b02628506e5c', 'CUST-1768935387-5190', 'Solveig', 'Irene Johansen', NULL, '91246172', NULL, NULL, NULL, NULL, NULL, NULL, 'individual', 'event_registration', 0, 'active', NULL, '2026-01-20 19:56:27', '2026-01-20 19:56:27', NULL),
('b8e8e8e19498728ff4b239e48f7cde83ab5a', '7efbddea-b507-11f0-936b-b02628506e5c', 'CUST-1768896923-6832', 'Sigrunn', 'Skoge', NULL, '91747050', NULL, NULL, NULL, NULL, NULL, NULL, 'individual', 'event_registration', 0, 'active', NULL, '2026-01-20 09:15:23', '2026-01-20 09:15:23', NULL),
('bf9f4835cf86fc6ddf91f87955bb0c4c7551', '7efbddea-b507-11f0-936b-b02628506e5c', 'CUST-1768749759-4274', 'Anita', 'Kvalsund', NULL, '91107083', NULL, NULL, NULL, NULL, NULL, NULL, 'individual', 'event_registration', 0, 'active', NULL, '2026-01-18 16:22:39', '2026-01-18 16:22:39', NULL),
('c671dc6594f96ab7ebf201d00cb6b6e3de1d', '7efbddea-b507-11f0-936b-b02628506e5c', 'CUST-1768749794-3749', 'Runar', 'Kvalsund', NULL, '47015195', NULL, NULL, NULL, NULL, NULL, NULL, 'individual', 'event_registration', 0, 'active', NULL, '2026-01-18 16:23:14', '2026-01-18 16:23:14', NULL),
('c86c82006d19f8cf1c5a7ba4dbf5e4653443', '7efbddea-b507-11f0-936b-b02628506e5c', 'CUST-1768538856-8078', 'Laila', 'Ødegård', 'laila@tussa.com', '90088114', NULL, NULL, NULL, NULL, NULL, NULL, 'individual', 'event_registration', 0, 'active', NULL, '2026-01-16 05:47:36', '2026-01-16 05:47:36', NULL),
('ccc35a6aaebde1957a9ffdb10c87899fa0e0', '7efbddea-b507-11f0-936b-b02628506e5c', 'CUST-1768538856-8669', 'Per', 'Ødegård', 'per@tussa.com', '90065130', NULL, NULL, NULL, NULL, NULL, NULL, 'individual', 'event_registration', 0, 'active', NULL, '2026-01-16 05:47:36', '2026-01-16 05:47:36', NULL),
('d04009ac77230a920d84006a5e801065e1a9', '7efbddea-b507-11f0-936b-b02628506e5c', 'CUST-1768691124-7246', 'Anita', 'Johanne Kvalsund', NULL, '41226404', NULL, NULL, NULL, NULL, NULL, NULL, 'individual', 'event_registration', 0, 'active', NULL, '2026-01-18 00:05:24', '2026-01-18 00:05:24', NULL),
('d0ffa5ccdc8a57f684782a6fe24c741b45df', '7efbddea-b507-11f0-936b-b02628506e5c', 'CUST-1767768008-9410', 'Magne', 'Kvalsvik', 'os.core.agility@gmail.com', '01020304', NULL, NULL, NULL, NULL, NULL, NULL, 'individual', 'event_registration', 0, 'active', NULL, '2026-01-07 07:40:08', '2026-01-07 07:40:08', NULL),
('d9ea2f0ab8930466f89a72ab2891ddfc15aa', '7efbddea-b507-11f0-936b-b02628506e5c', 'CUST-1768896891-1859', 'Andor', 'Kvalsund', NULL, '41226405', NULL, NULL, NULL, NULL, NULL, NULL, 'individual', 'event_registration', 0, 'active', NULL, '2026-01-20 09:14:51', '2026-01-20 09:14:51', NULL),
('da1e7e8cfeed4c04a6097df30dc77eeed0c4', '7efbddea-b507-11f0-936b-b02628506e5c', 'CUST-1768899293-9540', 'Sigrunn', 'Skoge', NULL, '91747050', NULL, NULL, NULL, NULL, NULL, NULL, 'individual', 'event_registration', 0, 'active', NULL, '2026-01-20 09:54:53', '2026-01-20 09:54:53', NULL),
('dc1d3186d342d936fbd9a05db00b5d7fe78a', '7efbddea-b507-11f0-936b-b02628506e5c', 'CUST-1768942937-8251', 'Sigrunn', 'Skoge', NULL, '91747050', NULL, NULL, NULL, NULL, NULL, NULL, 'individual', 'event_registration', 0, 'active', NULL, '2026-01-20 22:02:17', '2026-01-20 22:02:17', NULL),
('ec95b65b2bf2d3d05431f3bd180c08681636', '7efbddea-b507-11f0-936b-b02628506e5c', 'CUST-1768763391-8786', 'Hans', 'Sande', NULL, '92459392', NULL, NULL, NULL, NULL, NULL, NULL, 'individual', 'event_registration', 0, 'active', NULL, '2026-01-18 20:09:51', '2026-01-18 20:09:51', NULL),
('ede0496b4118a5e712cd933e3314ffaa892f', '7efbddea-b507-11f0-936b-b02628506e5c', 'CUST-1768814641-7878', 'Ellinor', 'Gjerde', NULL, '48093797', NULL, NULL, NULL, NULL, NULL, NULL, 'individual', 'event_registration', 0, 'active', NULL, '2026-01-19 10:24:01', '2026-01-19 10:24:01', NULL),
('ee5349ae13b1a0192fbab286e5626e658f64', '7efbddea-b507-11f0-936b-b02628506e5c', 'CUST-1768408040-6876', 'Ronald', 'Reite', 'dlanorrar@hotmail.com', '4741438157', NULL, NULL, NULL, NULL, NULL, NULL, 'individual', 'event_registration', 0, 'active', NULL, '2026-01-14 17:27:20', '2026-01-14 17:27:20', NULL),
('f5910bfa5f03f754a6cb2bd11b856cf744dc', '7efbddea-b507-11f0-936b-b02628506e5c', 'CUST-1768954317-5554', 'Kristin', 'Leine', NULL, '99649880', NULL, NULL, NULL, NULL, NULL, NULL, 'individual', 'event_registration', 0, 'active', NULL, '2026-01-21 01:11:57', '2026-01-21 01:11:57', NULL),
('f5fa7c635ec8f00b60f79e6b8323489109b1', '7efbddea-b507-11f0-936b-b02628506e5c', 'CUST-1768845712-1524', 'Anne', 'Lise Ingebrigtsen', NULL, '97019058', NULL, NULL, NULL, NULL, NULL, NULL, 'individual', 'event_registration', 0, 'active', NULL, '2026-01-19 19:01:52', '2026-01-19 19:01:52', NULL),
('fead9ff82716fb6586dec237c8211a073b8d', '7efbddea-b507-11f0-936b-b02628506e5c', 'CUST-1768579581-6216', 'Svein', 'Arne Leine', 'svein.arne.leine@tussa.com', '97069105', NULL, NULL, NULL, NULL, NULL, NULL, 'individual', 'event_registration', 0, 'active', NULL, '2026-01-16 17:06:21', '2026-01-16 17:06:21', NULL);

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `tenant_polling_tracker`
--

CREATE TABLE `tenant_polling_tracker` (
  `id` char(36) NOT NULL DEFAULT uuid(),
  `company_id` char(36) NOT NULL,
  `module_source` varchar(50) NOT NULL COMMENT 'Modul: sales, events, membership, reservations, rental',
  `action_type` varchar(50) DEFAULT NULL COMMENT 'Aktivitetstype: new_booking, payment_received, cancellation, etc.',
  `polling_source` varchar(100) NOT NULL COMMENT 'Kilde for polling (f.eks. "email_imap", "payment_gateway", "external_api")',
  `polling_type` varchar(50) NOT NULL COMMENT 'Type polling (f.eks. "incremental", "full", "delta")',
  `last_polled_at` datetime DEFAULT NULL COMMENT 'Sist gang polling ble utført',
  `next_poll_at` datetime DEFAULT NULL COMMENT 'Neste planlagte polling',
  `polling_status` varchar(20) DEFAULT 'idle' COMMENT 'Status: idle, running, failed, paused',
  `polling_interval_seconds` int(11) DEFAULT 300 COMMENT 'Interval i sekunder mellom polling',
  `last_successful_poll` datetime DEFAULT NULL COMMENT 'Sist vellykket polling',
  `last_error` text DEFAULT NULL COMMENT 'Siste feilmelding hvis polling feilet',
  `error_count` int(11) DEFAULT 0 COMMENT 'Antall påfølgende feil',
  `config_data` text DEFAULT NULL COMMENT 'JSON med modulspesifikk konfigurasjon',
  `items_processed_last_poll` int(11) DEFAULT 0 COMMENT 'Antall elementer prosessert sist',
  `total_items_processed` int(11) DEFAULT 0 COMMENT 'Totalt antall prosesserte elementer',
  `metadata` text DEFAULT NULL COMMENT 'Ekstra metadata (JSON)',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dataark for tabell `tenant_polling_tracker`
--

INSERT INTO `tenant_polling_tracker` (`id`, `company_id`, `module_source`, `action_type`, `polling_source`, `polling_type`, `last_polled_at`, `next_poll_at`, `polling_status`, `polling_interval_seconds`, `last_successful_poll`, `last_error`, `error_count`, `config_data`, `items_processed_last_poll`, `total_items_processed`, `metadata`, `created_at`, `updated_at`) VALUES
('a0500675-c7fe-4de0-8b1d-eeca742ece53', '54da99402ab5beb095223ce68587761f2dc7', 'reservations', 'new_booking', 'service_booking', 'incremental', NULL, '2025-12-23 17:10:37', 'pending', 30, NULL, NULL, 0, NULL, 2, 2, '{\"booking_id\":\"775d7fde09110de685370b965f3bf945\",\"service_name\":\"Manikyr\",\"customer_email\":\"ola@gmail.com\",\"booking_date\":\"2025-12-24\",\"amount\":\"250.00\"}', '2025-12-22 15:40:46', '2025-12-26 02:01:40');

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

--
-- Dataark for tabell `tenant_settings`
--

INSERT INTO `tenant_settings` (`id`, `company_id`, `setting_key`, `setting_value`, `setting_type`, `category`, `description`, `is_public`, `created_at`, `updated_at`) VALUES
('407426779e9749b624c70be767f86dfe5571', '2abf8cb74c20b1a64941e0351630f3ae39d7', '', '', 'string', 'general', NULL, 0, '2025-12-04 18:10:11', '2025-12-04 18:10:11'),
('7750680be9f6b83abfeb7521e896b03ac67a', 'a7eb3dbc5543932549d02a0bfd2b79720ad5', '', '', 'string', 'general', NULL, 0, '2025-11-20 15:45:54', '2025-11-20 15:45:54'),
('b114341953e5ac5888e617946bc138050bcb', '5c98d1c6daaed60c59a906dcaa139ee3adb1', '', '', 'string', 'general', NULL, 0, '2025-12-21 21:32:29', '2025-12-21 21:32:29'),
('e9f3ecaeb0ee410be3bf20ab8368d41aa8d8', '54da99402ab5beb095223ce68587761f2dc7', '', '', 'string', 'general', NULL, 0, '2025-12-18 15:22:46', '2025-12-18 15:22:46'),
('ed7681168a69481c9752956bc6aefd05f1fa', 'a9cdf14b6790d0247b55534d68065eb695f9', '', '', 'string', 'general', NULL, 0, '2025-11-13 16:27:41', '2025-11-13 16:27:41'),
('fb6456cc82b4ad4f0363e49daa2398ddc1db', '136c7dba2f23fd91db56b342dd535c225a80', '', '', 'string', 'general', NULL, 0, '2025-12-17 01:29:18', '2025-12-17 01:29:18');

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

--
-- Dataark for tabell `tenant_staff`
--

INSERT INTO `tenant_staff` (`id`, `company_id`, `tenant_admin_id`, `employee_code`, `first_name`, `last_name`, `email`, `phone`, `date_of_birth`, `job_title`, `department`, `employment_type`, `hire_date`, `salary`, `address_street`, `address_city`, `address_state`, `address_zip`, `address_country`, `emergency_contact_name`, `emergency_contact_phone`, `is_active`, `last_login`, `created_at`, `updated_at`) VALUES
('0822cd1cbe90d9e4afa5bf5011deb42ef7d8', '7efbddea-b507-11f0-936b-b02628506e5c', NULL, NULL, 'Kristin', 'Leine', '', '99649880', NULL, 'Styremedlem', NULL, 'full_time', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, '2025-12-15 01:08:11', '2025-12-15 01:08:11'),
('0b476abbe0e6fd587bc39bffc17f8014babe', '5c98d1c6daaed60c59a906dcaa139ee3adb1', NULL, NULL, 'Per Knut', 'Dimmen', '', '91694567', NULL, 'Styremedlem', NULL, 'full_time', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, '2025-12-21 21:38:08', '2025-12-21 21:38:08'),
('41cc985f2d4bfda8dc8e06994198de2088b5', '5c98d1c6daaed60c59a906dcaa139ee3adb1', NULL, NULL, 'Ove Inge', 'Dimmen', '', '95006330', NULL, 'Styremedlem', NULL, 'full_time', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, '2025-12-21 21:37:36', '2025-12-21 21:37:36'),
('42b1d611d7577e56088f2aa3f134a9cf9897', '7efbddea-b507-11f0-936b-b02628506e5c', NULL, NULL, 'Marion', 'Nilsen', '', '97988677', NULL, 'Kasserer', NULL, 'full_time', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, '2025-12-15 01:05:55', '2025-12-15 01:05:55'),
('65c9d59ab53ecdcaf4f80de9a25821033906', '136c7dba2f23fd91db56b342dd535c225a80', NULL, NULL, 'Moshreq Razzaq', 'Atia Alhilaly', '', '477 43 987', NULL, 'Foretakseier / Driver', NULL, 'full_time', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, '2025-12-17 01:37:05', '2025-12-17 01:37:05'),
('76b943ed854e4fd8ea8ac017f02666f48b4d', '54da99402ab5beb095223ce68587761f2dc7', NULL, NULL, 'Sara', 'Eriksson', '', '94280190', NULL, 'Driver', NULL, 'full_time', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, '2025-12-18 15:25:16', '2025-12-18 15:25:16'),
('796ffb831f229b41dbca82ba9e87d39db914', '7efbddea-b507-11f0-936b-b02628506e5c', NULL, NULL, 'Robert', 'Helde', '', '97624748', NULL, 'Styremedlem', NULL, 'full_time', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, '2025-12-15 01:06:53', '2025-12-15 01:06:53'),
('7ab4d9347c845513aaf9e4383c9433142169', '5c98d1c6daaed60c59a906dcaa139ee3adb1', NULL, NULL, 'Svein Børre', 'Dimmen', '', '48087724', NULL, 'Styremedlem', NULL, 'full_time', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, '2025-12-21 21:36:24', '2025-12-21 21:36:24'),
('7bf15a9f46b142ffd72469d73a9d529edd28', '5c98d1c6daaed60c59a906dcaa139ee3adb1', NULL, NULL, 'Werner', 'Røyseth', '', '40243608', NULL, 'Styremedlem', NULL, 'full_time', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, '2025-12-21 21:35:39', '2025-12-21 21:35:39'),
('7efffc84-b507-11f0-936b-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', NULL, NULL, 'Marita Higraf', 'Remøy', 'maritahr@gmail.com', '90981991', '1966-04-14', 'Sekretær', NULL, 'full_time', NULL, NULL, 'Nedre plassgjerde', 'Nerlandsøy', NULL, '6098', NULL, NULL, NULL, 1, NULL, '2025-10-29 21:40:24', '2025-12-14 23:46:44'),
('7f0980cf-b507-11f0-936b-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', NULL, NULL, 'Atle Karl', 'Kvalsvik', 'atlekarl@tussa.com', '95918925', '1956-04-08', 'Styreleder', NULL, 'full_time', NULL, NULL, 'Mulevikvegen 42', 'Nerlandsøy', NULL, '6098', NULL, NULL, NULL, 1, NULL, '2025-10-29 21:40:24', '2025-12-14 23:46:51'),
('8f972e2967080c9cb1f18ec01faf9e4c8700', '5c98d1c6daaed60c59a906dcaa139ee3adb1', NULL, NULL, 'Mette Christine Bachke', 'Holstad', '', '47487276', NULL, 'Styreleder', NULL, 'full_time', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, '2025-12-21 21:34:03', '2025-12-21 21:34:03'),
('98619915a7e5c1303a4bc74173d7d12e0d30', '5c98d1c6daaed60c59a906dcaa139ee3adb1', NULL, NULL, 'Barbro Teigene', 'Eikrem', '', '40639067', NULL, 'Styremedlem', NULL, 'full_time', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, '2025-12-21 21:37:05', '2025-12-21 21:37:05'),
('aed925c2da53321cfb4e35ff5909b7cf4702', '5c98d1c6daaed60c59a906dcaa139ee3adb1', NULL, NULL, 'Stian Slyngstad', 'Korsan', '', '41235325', NULL, 'Kontaktperson', NULL, 'full_time', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, '2025-12-21 21:34:55', '2025-12-21 21:34:55'),
('b15baf660fcf225443dd537ed88d625cb6b3', '7efbddea-b507-11f0-936b-b02628506e5c', NULL, NULL, 'Linda Myrseth', 'Kvalsund', '', '93058908', NULL, 'Styremedlem', NULL, 'full_time', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, '2025-12-15 01:09:36', '2025-12-15 01:09:36'),
('bc359ecc392c1e9199d8ced7ce8f63f9c592', '7efbddea-b507-11f0-936b-b02628506e5c', NULL, NULL, 'Egil Raminesson', 'Frantsen', '', '90689856', NULL, 'Styremedlem', NULL, 'full_time', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, '2025-12-15 01:11:09', '2025-12-15 01:11:09'),
('d737e3b71bbb87f6184b65529deade5e8fb8', '7efbddea-b507-11f0-936b-b02628506e5c', NULL, NULL, 'Bente Notø', 'Kvalsvik', '', '48158704', NULL, 'Nestleder', NULL, 'full_time', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, NULL, '2025-12-15 00:56:29', '2025-12-15 00:56:29');

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

--
-- Dataark for tabell `tenant_subscriptions`
--

INSERT INTO `tenant_subscriptions` (`id`, `company_id`, `self_company_id`, `plan_id`, `status`, `billing_cycle`, `current_period_start`, `current_period_end`, `stripe_subscription_id`, `stripe_customer_id`, `canceled_at`, `created_at`, `updated_at`) VALUES
('11f975759a5eec4c79173d9de35620992947', 'a9cdf14b6790d0247b55534d68065eb695f9', '', NULL, 'active', 'monthly', NULL, NULL, NULL, NULL, NULL, '2025-11-13 16:27:41', '2025-11-13 16:27:41'),
('208c78ae75768acbfcbd82e87dd86f3bd8e3', '136c7dba2f23fd91db56b342dd535c225a80', '', NULL, 'active', 'monthly', NULL, NULL, NULL, NULL, NULL, '2025-12-17 01:29:18', '2025-12-17 01:29:18'),
('6451fcd36a8a3481ec6f4a30e12d44d504e3', 'a7eb3dbc5543932549d02a0bfd2b79720ad5', '', NULL, 'active', 'monthly', NULL, NULL, NULL, NULL, NULL, '2025-11-20 15:45:54', '2025-11-20 15:45:54'),
('a9d3f0e0749d26694583357a186f2192f59e', '54da99402ab5beb095223ce68587761f2dc7', '', NULL, 'active', 'monthly', NULL, NULL, NULL, NULL, NULL, '2025-12-18 15:22:46', '2025-12-18 15:22:46'),
('aa24685d09e97b488206fd50416738b8e79c', '5c98d1c6daaed60c59a906dcaa139ee3adb1', '', NULL, 'active', 'monthly', NULL, NULL, NULL, NULL, NULL, '2025-12-21 21:32:29', '2025-12-21 21:32:29'),
('accd49ebb14e0204e21ad500583a579ea427', '2abf8cb74c20b1a64941e0351630f3ae39d7', '', NULL, 'active', 'monthly', NULL, NULL, NULL, NULL, NULL, '2025-12-04 18:10:11', '2025-12-04 18:10:11');

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

--
-- Dataark for tabell `tenant_terms_conditions`
--

INSERT INTO `tenant_terms_conditions` (`id`, `company_id`, `title`, `content`, `version`, `effective_date`, `is_active`, `applies_to_modules`, `requires_customer_acceptance`, `acceptance_type`, `created_by`, `created_at`, `updated_at`) VALUES
('309c4654-562b-4c6d-ac44-3e9c7b0d4649', '7efbddea-b507-11f0-936b-b02628506e5c', 'Kjøpsbetingelser', 'KJØPSBETINGELSER FOR BERGSØY DANSEKLUBB\n\n§ 1 ALMINNELIGE BESTEMMELSER\nDisse kjøpsbetingelsene gjelder for alle billetter, varer og tjenester kjøpt gjennom Bergsøy danseklubb. Ved kjøp godtar kunden disse betingelsene.\n\n§ 2 BILLETTER TIL ARRANGEMENTER\n- Billetter kan kjøpes online, i døren eller på forhånd etter avtale\n- Billetter refunderes ikke ved avlysning fra kundens side\n- Ved avlysning fra arrangørens side refunderes billetten fullt ut\n- Billetten er personlig og kan ikke videreselges til en høyere pris enn opprinnelig kjøpspris\n\n§ 3 KJØP AV VARER I KIOSKEN\n- Alle priser er i NOK inkl. mva\n- Varer kan returneres kun ved produksjonsfeil\n- Forbruksvarer (mat/drikke) refunderes ikke\n- Kjøp gjort i kiosken kan ikke refunderes etter at varene er forlatt lokalet\n\n§ 4 MEDLEMSKAP\n- Medlemskap løper i 12 måneder fra kjøpsdato\n- Medlemsavgift refunderes ikke ved oppsigelse\n- Medlemsfordeler gjelder kun for inneværende medlemskap\n\n§ 5 ANSVAR\nBergsøy danseklubb er ikke ansvarlig for:\n- Tapte eller stjålne billetter\n- Personskader under arrangementer\n- Tap av eller skade på personlige eiendeler\n- Endringer i program eller artister\n\n§ 6 PERSONVERN\nVi behandler personopplysninger i henhold til personvermloven. Opplysninger brukes kun til administrasjon av kjøp og medlemskap.\n\n§ 7 TVANG OG KLAGER\nKjøp omfattes av forbrukerkjøpsloven. Klager rettes til Bergsøy danseklubb innen 14 dager.\n\n§ 8 ENDRINGER\nBergsøy danseklubb forbeholder seg retten til å endre disse betingelsene. Gjeldende versjon finnes alltid på vår hjemmeside.\n\nKontakt: bergsoy.d-k.salesman.no | Tlf: 959 18 925\n', '1.0', '2025-11-21', 1, NULL, 1, 'checkbox', '7f072d6a-b507-11f0-936b-b02628506e5c', '2025-11-21 22:08:05', '2025-12-15 03:34:27');

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
  `spacing_unit` varchar(10) DEFAULT '1rem',
  `header_text_color` varchar(7) DEFAULT '#000000',
  `hero_text_color` varchar(7) DEFAULT '#000000',
  `reservation_form_bg_color` varchar(20) DEFAULT '#667eea'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dataark for tabell `tenant_website_general`
--

INSERT INTO `tenant_website_general` (`id`, `company_id`, `site_title`, `site_description`, `logo_url`, `favicon_url`, `primary_color`, `secondary_color`, `header_layout`, `footer_layout`, `navigation_style`, `homepage_module`, `hero_enabled`, `hero_title`, `hero_description`, `hero_image_url`, `hero_button_text`, `hero_button_url`, `contact_email`, `contact_phone`, `contact_address`, `social_media_links`, `footer_text`, `footer_links`, `custom_css`, `custom_js`, `google_analytics_id`, `is_active`, `maintenance_mode`, `maintenance_message`, `created_at`, `updated_at`, `header_bg_color`, `body_bg_color`, `logo_height`, `header_height`, `logo_position`, `nav_position`, `font_family`, `button_style`, `border_radius`, `spacing_unit`, `header_text_color`, `hero_text_color`, `reservation_form_bg_color`) VALUES
('0af6c4341feb32903a98b863c64570b81d03', 'a7eb3dbc5543932549d02a0bfd2b79720ad5', NULL, NULL, NULL, NULL, '#3366CC', '#666666', 'standard', 'standard', 'top', NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, NULL, '2025-11-20 15:45:54', '2025-11-20 15:45:54', '#ffffff', '#f8f9fa', '50px', '80px', 'left', 'center', 'Inter, sans-serif', 'rounded', '16px', '1rem', '#000000', '#000000', '#667eea'),
('3f731a766be8ce8b5bcdddcfc1792d2c73f1', '2abf8cb74c20b1a64941e0351630f3ae39d7', NULL, NULL, NULL, NULL, '#3366CC', '#666666', 'standard', 'standard', 'top', NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, NULL, '2025-12-04 18:10:11', '2025-12-21 22:35:33', '#144CFF', '#f8f9fa', '50px', '80px', 'left', 'center', 'Inter, sans-serif', 'rounded', '16px', '1rem', '#000000', '#000000', '#f8f9fa'),
('43e524df8768d998ff295e0b3a33f83fe3a6', '5c98d1c6daaed60c59a906dcaa139ee3adb1', NULL, 'Dimna Grendahus', NULL, NULL, '#3366CC', '#666666', 'standard', 'standard', 'top', NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, NULL, '2025-12-21 21:32:29', '2025-12-22 06:49:05', '#50D99C', '#F0F9F6', '50px', '100px', 'left', 'center', 'Inter, sans-serif', 'rounded', '16px', '1rem', '#ffffff', '#000000', '#50D99C'),
('5199685f7757432ed30a4e60bd77b9a10cea', 'a9cdf14b6790d0247b55534d68065eb695f9', NULL, NULL, NULL, NULL, '#3366CC', '#666666', 'standard', 'standard', 'top', NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, NULL, '2025-11-13 16:27:41', '2025-11-13 16:27:41', '#ffffff', '#f8f9fa', '50px', '80px', 'left', 'center', 'Inter, sans-serif', 'rounded', '16px', '1rem', '#000000', '#000000', '#667eea'),
('663344d32d27ecad19081df85b9259240150', '136c7dba2f23fd91db56b342dd535c225a80', NULL, 'Babylon', NULL, NULL, '#3366CC', '#666666', 'standard', 'standard', 'top', NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, NULL, '2025-12-17 01:29:18', '2025-12-19 05:59:46', '#0C2D48', '#faf9f6', '50px', '80px', 'left', 'center', 'Inter, sans-serif', 'rounded', '16px', '1rem', '#ffffff', '#ffffff', '#667eea'),
('84ce4265852a8b59dacec181189a22c4818a', '54da99402ab5beb095223ce68587761f2dc7', NULL, 'JustNails', NULL, NULL, '#3366CC', '#666666', 'standard', 'standard', 'top', NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, NULL, '2025-12-18 15:22:46', '2025-12-19 18:16:53', '#6096C2', '#DCA7BB', '50px', '100px', 'left', 'center', 'Inter, sans-serif', 'rounded', '16px', '1rem', '#ffffff', '#ffffff', '#6096C2'),
('8b51cb47-d960-11f0-aec6-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', NULL, 'Bergsøy Danseklubb', NULL, NULL, '#3366CC', '#666666', 'standard', 'standard', 'top', NULL, 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, NULL, '2025-12-15 03:48:32', '2025-12-19 09:42:57', '#274d8c', '#e0f0ff', '90px', '100px', 'left', 'center', 'Inter, sans-serif', 'rounded', '16px', '1rem', '#ffffff', '#000000', '#667eea');

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
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_status` varchar(50) DEFAULT 'completed',
  `status` varchar(50) DEFAULT 'active',
  `transaction_type` varchar(20) DEFAULT NULL COMMENT 'income, expense, or null',
  `category` varchar(100) DEFAULT NULL COMMENT 'Salg, Arrangement, Medlemskap, etc',
  `transaction_date` datetime NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `customer_id` char(36) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dataark for tabell `unified_ledger`
--

INSERT INTO `unified_ledger` (`id`, `order_id`, `company_id`, `module_source`, `item_type`, `source_id`, `item_name`, `quantity`, `selling_price`, `cost_price`, `payment_method`, `payment_status`, `status`, `transaction_type`, `category`, `transaction_date`, `created_at`, `metadata`, `customer_id`) VALUES
('01df5949-c3b5-4d34-ae24-1cfe4852e167', 'EVT-20251223-F9E1C0', '7efbddea-b507-11f0-936b-b02628506e5c', 'events', 'event', 'f9e1c0c77a35651e22fee4b37d6e6371', 'Januardansen', 1, 500.00, 0.00, 'vipps', 'completed', 'active', 'income', 'Arrangement', '2025-12-23 20:51:28', '2025-12-23 20:51:28', '{\"event_id\":\"cf5e1f98f54ed2642c117ffd7e173999\",\"ticket_type\":\"regular\",\"checkin_id\":\"f9e1c0c77a35651e22fee4b37d6e6371\"}', NULL),
('0618ad06-4ff8-4c09-b530-1db21ab7a697', 'EVT-874da850-9056-42ea-b62a-c24fe537b47e', '7efbddea-b507-11f0-936b-b02628506e5c', 'events', 'event_ticket', '874da850-9056-42ea-b62a-c24fe537b47e', 'Dansetreff med Steinar Engelbrektson ', 1, 400.00, NULL, 'cash', 'pending', 'active', NULL, NULL, '2026-01-19 10:17:54', '2026-01-19 10:17:54', '{\"event_id\":\"fc8b18485aae1b5d3890656c5ad46c39\",\"is_member\":false,\"membership_plan_id\":null}', '5013b784781c70d78dc17e80ef099d33f34e'),
('0bb08120-07cc-4da2-9785-eb0b652afa86', 'EVT-dc8a2e3a-df77-465b-bb90-c4a38fd52952', '7efbddea-b507-11f0-936b-b02628506e5c', 'events', 'event_ticket', 'dc8a2e3a-df77-465b-bb90-c4a38fd52952', 'Dansetreff med Steinar Engelbrektson ', 1, 400.00, NULL, 'cash', 'pending', 'active', NULL, NULL, '2026-01-18 20:09:51', '2026-01-18 20:09:51', '{\"event_id\":\"fc8b18485aae1b5d3890656c5ad46c39\",\"is_member\":false,\"membership_plan_id\":null}', 'ec95b65b2bf2d3d05431f3bd180c08681636'),
('0cdd0f49-5068-4bfd-97ba-fedefe5763fb', 'EVT-ce81c5aa-e4c6-4943-bd39-c77fbabbe197', '7efbddea-b507-11f0-936b-b02628506e5c', 'events', 'event_ticket', 'ce81c5aa-e4c6-4943-bd39-c77fbabbe197', 'Dansetreff med Steinar Engelbrektson ', 1, 400.00, NULL, 'cash', 'pending', 'active', NULL, NULL, '2026-01-19 10:20:56', '2026-01-19 10:20:56', '{\"event_id\":\"fc8b18485aae1b5d3890656c5ad46c39\",\"is_member\":false,\"membership_plan_id\":null}', '2306806ac608bafdfc50672144b14c11ee4f'),
('0f7fb75f-20f1-41eb-887f-2e13d3c4fbc3', 'EVT-b112429f-6d9e-43df-85b7-049474e338e9', '7efbddea-b507-11f0-936b-b02628506e5c', 'events', 'event_ticket', 'b112429f-6d9e-43df-85b7-049474e338e9', 'Dansetreff med Steinar Engelbrektson ', 1, 400.00, NULL, 'cash', 'pending', 'active', NULL, NULL, '2026-01-18 10:35:18', '2026-01-18 10:35:18', '{\"event_id\":\"fc8b18485aae1b5d3890656c5ad46c39\",\"is_member\":false,\"membership_plan_id\":null}', '98a34d2143378e70aa3a7cebe7a2da4c5453'),
('10d42774-5734-4100-b297-4abab846a068', 'EVT-38b609b9-4cd9-4c56-ba6a-4632e71c412c', '7efbddea-b507-11f0-936b-b02628506e5c', 'events', 'event_ticket', '38b609b9-4cd9-4c56-ba6a-4632e71c412c', 'Dansetreff med Steinar Engelbrektson ', 1, 350.00, NULL, 'cash', 'pending', 'active', NULL, NULL, '2026-01-20 09:54:53', '2026-01-20 09:54:53', '{\"event_id\":\"fc8b18485aae1b5d3890656c5ad46c39\",\"is_member\":true,\"membership_plan_id\":null}', 'da1e7e8cfeed4c04a6097df30dc77eeed0c4'),
('16f0c6dc-21ff-4b4c-8db9-70e7d020edb1', 'EVT-82fe5a54-87dc-4a64-8af9-3006b818b68c', '7efbddea-b507-11f0-936b-b02628506e5c', 'events', 'event_ticket', '82fe5a54-87dc-4a64-8af9-3006b818b68c', 'Dansetreff med Steinar Engebrektson ', 1, 350.00, NULL, 'cash', 'pending', 'active', NULL, NULL, '2026-01-14 11:01:22', '2026-01-14 11:01:22', '{\"event_id\":\"fc8b18485aae1b5d3890656c5ad46c39\",\"is_member\":true,\"membership_plan_id\":\"ec90a19c3209fc47d42524ef9b95ff44\"}', '69f6bc66-e752-11f0-ada2-b02628506e5c'),
('1d7d5c81-bac1-422e-b782-5a74b7e9c290', 'EVT-9da10678-9a82-4d8b-8a6e-22ea8ecf2d7e', '7efbddea-b507-11f0-936b-b02628506e5c', 'events', 'event_ticket', '9da10678-9a82-4d8b-8a6e-22ea8ecf2d7e', 'Dansetreff med Steinar Engelbrektson ', 1, 350.00, NULL, 'cash', 'pending', 'active', NULL, NULL, '2026-01-20 21:02:18', '2026-01-20 22:02:19', '{\"event_id\":\"fc8b18485aae1b5d3890656c5ad46c39\",\"is_member\":true,\"membership_plan_id\":\"ec90a19c3209fc47d42524ef9b95ff44\"}', 'dc1d3186d342d936fbd9a05db00b5d7fe78a'),
('1ff1cad8-7167-4fbe-8ffb-49bb8b22f638', 'EVT-55ad7edf-79a9-43ab-b9d8-06169c38a317', '7efbddea-b507-11f0-936b-b02628506e5c', 'events', 'event_ticket', '55ad7edf-79a9-43ab-b9d8-06169c38a317', 'Dansetreff med Steinar Engelbrektson ', 1, 400.00, NULL, 'cash', 'pending', 'active', NULL, NULL, '2026-01-18 16:22:39', '2026-01-18 16:22:39', '{\"event_id\":\"fc8b18485aae1b5d3890656c5ad46c39\",\"is_member\":false,\"membership_plan_id\":null}', 'bf9f4835cf86fc6ddf91f87955bb0c4c7551'),
('2262f874-44b1-4419-aa23-1073483e6ce9', 'EVT-1414dd36-1492-48de-9a09-ca6bbde4528a', '7efbddea-b507-11f0-936b-b02628506e5c', 'events', 'event_ticket', '1414dd36-1492-48de-9a09-ca6bbde4528a', 'Dansetreff med Steinar Engelbrektson ', 1, 350.00, NULL, 'cash', 'pending', 'active', NULL, NULL, '2026-01-20 08:45:14', '2026-01-20 09:45:15', '{\"event_id\":\"fc8b18485aae1b5d3890656c5ad46c39\",\"is_member\":true,\"membership_plan_id\":null}', NULL),
('2b884f05-19c6-4f59-b2b8-07c0e6753393', 'EVT-bd7e973e-70a3-4fdf-a551-6a252f2388a2', '7efbddea-b507-11f0-936b-b02628506e5c', 'events', 'event_ticket', 'bd7e973e-70a3-4fdf-a551-6a252f2388a2', 'Dansetreff med Steinar Engebrektson ', 1, 400.00, NULL, 'cash', 'pending', 'active', NULL, NULL, '2026-01-07 07:40:08', '2026-01-07 07:40:08', '{\"event_id\":\"fc8b18485aae1b5d3890656c5ad46c39\",\"is_member\":false,\"membership_plan_id\":null}', 'd0ffa5ccdc8a57f684782a6fe24c741b45df'),
('350c564e-cfb4-4bc1-bcb6-fd0c5db06384', 'EVT-30c97fbe-f78e-4d02-8acb-385964e2b28f', '7efbddea-b507-11f0-936b-b02628506e5c', 'events', 'event_ticket', '30c97fbe-f78e-4d02-8acb-385964e2b28f', 'Dansetreff med Steinar Engebrektson ', 1, 350.00, NULL, 'cash', 'pending', 'active', NULL, NULL, '2026-01-14 17:28:12', '2026-01-14 17:28:12', '{\"event_id\":\"fc8b18485aae1b5d3890656c5ad46c39\",\"is_member\":true,\"membership_plan_id\":\"ec90a19c3209fc47d42524ef9b95ff44\"}', '69f6a3f6-e752-11f0-ada2-b02628506e5c'),
('353b2f59-0486-4dfd-83d4-f3b53d100934', 'EVT-a255d01e-08b9-4308-86ac-f32f80515bc0', '7efbddea-b507-11f0-936b-b02628506e5c', 'events', 'event_ticket', 'a255d01e-08b9-4308-86ac-f32f80515bc0', 'Hei', 1, 300.00, NULL, 'vipps', 'pending', 'active', NULL, NULL, '2025-12-30 22:35:20', '2025-12-30 22:35:20', '{\"event_id\":\"3198abb7e34216e5bc23bd953e27ee9d\",\"is_member\":false,\"membership_plan_id\":null}', NULL),
('3d1a0269-96a7-47cd-be23-8dc2fce0193c', 'EVT-bb3e0258-ef4e-4d7d-b64b-11707cfad3f7', '7efbddea-b507-11f0-936b-b02628506e5c', 'events', 'event_ticket', 'bb3e0258-ef4e-4d7d-b64b-11707cfad3f7', 'Dansetreff med Steinar Engelbrektson ', 1, 350.00, NULL, 'cash', 'pending', 'active', NULL, NULL, '2026-01-20 09:15:23', '2026-01-20 09:15:23', '{\"event_id\":\"fc8b18485aae1b5d3890656c5ad46c39\",\"is_member\":true,\"membership_plan_id\":null}', 'b8e8e8e19498728ff4b239e48f7cde83ab5a'),
('3e561cb4-dc4a-415b-b2b2-9dc944b33ca2', 'EVT-20251216-DBD663', '7efbddea-b507-11f0-936b-b02628506e5c', 'events', 'event', 'dbd66343b37103db5387b165240d9b5e8b2e', 'HYTT Å PINE ', 1, 2.00, 0.00, 'vipps', 'completed', 'active', 'income', 'Arrangement', '2025-12-16 22:26:42', '2025-12-16 22:26:42', '{\"event_id\":\"6caf417437111fb1e704d7051d5e1885\",\"ticket_type\":\"standard\",\"checkin_id\":\"dbd66343b37103db5387b165240d9b5e8b2e\"}', NULL),
('3e702879-7cea-4e1e-a425-a87e88d77b1a', 'EVT-26b736d6-9841-48a3-af14-6b493d7ca7f6', '7efbddea-b507-11f0-936b-b02628506e5c', 'events', 'event_ticket', '26b736d6-9841-48a3-af14-6b493d7ca7f6', 'Dansetreff med Steinar Engelbrektson ', 1, 400.00, NULL, 'cash', 'pending', 'active', NULL, NULL, '2026-01-20 09:14:51', '2026-01-20 09:14:51', '{\"event_id\":\"fc8b18485aae1b5d3890656c5ad46c39\",\"is_member\":false,\"membership_plan_id\":null}', 'd9ea2f0ab8930466f89a72ab2891ddfc15aa'),
('4143a159-708f-44a9-bb4f-7f89157bb972', 'EVT-e0648c3c-8ff8-46e1-b8a1-0a79f8c80fb3', '7efbddea-b507-11f0-936b-b02628506e5c', 'events', 'event_ticket', 'e0648c3c-8ff8-46e1-b8a1-0a79f8c80fb3', 'Dansetreff med Steinar Engebrektson ', 1, 400.00, NULL, 'cash', 'pending', 'active', NULL, NULL, '2026-01-15 10:15:09', '2026-01-15 10:15:09', '{\"event_id\":\"fc8b18485aae1b5d3890656c5ad46c39\",\"is_member\":false,\"membership_plan_id\":null}', NULL),
('45bd9f42-5a62-4b3a-9e64-dd8e1a21ad0c', 'EVT-9aab02c4-30ed-4313-b0d5-f5d4100dfab0', '7efbddea-b507-11f0-936b-b02628506e5c', 'events', 'event_ticket', '9aab02c4-30ed-4313-b0d5-f5d4100dfab0', 'Dansetreff med Steinar Engelbrektson ', 1, 350.00, NULL, 'cash', 'pending', 'active', NULL, NULL, '2026-01-20 18:33:32', '2026-01-20 18:33:32', '{\"event_id\":\"fc8b18485aae1b5d3890656c5ad46c39\",\"is_member\":true,\"membership_plan_id\":null}', '857ed927a50e36a8f77043d921965c28b3c9'),
('50198272-8683-4c11-a0e8-0a211f5592db', 'EVT-667293d7-c334-4923-9d8e-74d17ae20062', '7efbddea-b507-11f0-936b-b02628506e5c', 'events', 'event_ticket', '667293d7-c334-4923-9d8e-74d17ae20062', 'Dansetreff med Steinar Engelbrektson ', 1, 400.00, NULL, 'cash', 'pending', 'active', NULL, NULL, '2026-01-19 19:00:58', '2026-01-19 19:00:58', '{\"event_id\":\"fc8b18485aae1b5d3890656c5ad46c39\",\"is_member\":false,\"membership_plan_id\":null}', '148176b84267e1fbfa9c5fd08e21285dde07'),
('5073903a-76ed-4580-97c3-6932d2236852', 'EVT-c5611fe6-fdac-469a-8426-9a75ac4a946b', '7efbddea-b507-11f0-936b-b02628506e5c', 'events', 'event_ticket', 'c5611fe6-fdac-469a-8426-9a75ac4a946b', 'Dansetreff med Steinar Engelbrektson ', 1, 400.00, NULL, 'cash', 'pending', 'active', NULL, NULL, '2026-01-18 10:35:18', '2026-01-18 10:35:18', '{\"event_id\":\"fc8b18485aae1b5d3890656c5ad46c39\",\"is_member\":false,\"membership_plan_id\":null}', 'aa9d757529e17a7420904344cfd7f705bedf'),
('51b65941-f1c4-4fac-805c-bc00546c815b', 'EVT-23b5ad1d-c7b9-4dd4-8ba8-46ccf879f814', '7efbddea-b507-11f0-936b-b02628506e5c', 'events', 'event_ticket', '23b5ad1d-c7b9-4dd4-8ba8-46ccf879f814', 'Dansetreff med Steinar Engebrektson ', 1, 350.00, NULL, 'cash', 'pending', 'active', NULL, NULL, '2026-01-15 10:26:25', '2026-01-15 10:26:25', '{\"event_id\":\"fc8b18485aae1b5d3890656c5ad46c39\",\"is_member\":true,\"membership_plan_id\":\"ec90a19c3209fc47d42524ef9b95ff44\"}', '69f6a493-e752-11f0-ada2-b02628506e5c'),
('5437a692-2dce-4876-974a-7f93df199228', 'EVT-8b22136f-d044-4c6a-9859-de3205e0e8c8', '7efbddea-b507-11f0-936b-b02628506e5c', 'events', 'event_ticket', '8b22136f-d044-4c6a-9859-de3205e0e8c8', 'Dansetreff med Steinar Engebrektson ', 1, 350.00, NULL, 'cash', 'pending', 'active', NULL, NULL, '2026-01-14 11:01:22', '2026-01-14 11:01:22', '{\"event_id\":\"fc8b18485aae1b5d3890656c5ad46c39\",\"is_member\":true,\"membership_plan_id\":\"ec90a19c3209fc47d42524ef9b95ff44\"}', '69f699af-e752-11f0-ada2-b02628506e5c'),
('55bf2571-46c8-4d01-a131-6e58c3f0210a', 'EVT-219962e1-b43e-483b-9fb5-def99114ce91', '7efbddea-b507-11f0-936b-b02628506e5c', 'events', 'event_ticket', '219962e1-b43e-483b-9fb5-def99114ce91', 'Dansetreff med Steinar Engelbrektson ', 1, 400.00, NULL, 'cash', 'pending', 'active', NULL, NULL, '2026-01-19 10:16:53', '2026-01-19 10:16:53', '{\"event_id\":\"fc8b18485aae1b5d3890656c5ad46c39\",\"is_member\":false,\"membership_plan_id\":null}', 'a467996f28c150ab799bb9e5d247b42c46a3'),
('5e43add1-527c-4a89-bd22-760fc788f159', 'EVT-c077d4f8-e6d2-4020-aef3-f8edcfe62276', '7efbddea-b507-11f0-936b-b02628506e5c', 'events', 'event_ticket', 'c077d4f8-e6d2-4020-aef3-f8edcfe62276', 'Dansetreff med Steinar Engelbrektson ', 1, 350.00, NULL, 'cash', 'pending', 'active', NULL, NULL, '2026-01-20 18:33:32', '2026-01-20 18:33:32', '{\"event_id\":\"fc8b18485aae1b5d3890656c5ad46c39\",\"is_member\":true,\"membership_plan_id\":null}', '2102c860dd03066d60d6066e514506f34d5a'),
('5eb922f3-64b0-4fa6-8938-53c52d02031c', 'EVT-9344192b-fb4c-40a8-bec3-db3a18ec31d3', '7efbddea-b507-11f0-936b-b02628506e5c', 'events', 'event_ticket', '9344192b-fb4c-40a8-bec3-db3a18ec31d3', 'Dansetreff med Steinar Engebrektson ', 1, 350.00, NULL, 'cash', 'pending', 'active', NULL, NULL, '2026-01-16 17:09:00', '2026-01-16 17:09:00', '{\"event_id\":\"fc8b18485aae1b5d3890656c5ad46c39\",\"is_member\":true,\"membership_plan_id\":\"ec90a19c3209fc47d42524ef9b95ff44\"}', '69f6c081-e752-11f0-ada2-b02628506e5c'),
('5fc330d1-308c-4e51-8702-79535f9cf99c', 'EVT-f7b13acb-27fe-4796-af4c-6f7dad0d6c40', '7efbddea-b507-11f0-936b-b02628506e5c', 'events', 'event_ticket', 'f7b13acb-27fe-4796-af4c-6f7dad0d6c40', 'Dansetreff med Steinar Engebrektson ', 1, 400.00, NULL, 'cash', 'pending', 'active', NULL, NULL, '2026-01-11 01:16:30', '2026-01-11 01:16:30', '{\"event_id\":\"fc8b18485aae1b5d3890656c5ad46c39\",\"is_member\":true,\"membership_plan_id\":\"c756c21b869ae63c37e015f8ac88cea2\"}', '69f67ba4-e752-11f0-ada2-b02628506e5c'),
('61e4e176-9611-4dd5-96d1-fe3bd56cb4a7', 'EVT-0aa03912-cd0a-4f19-9ace-1d0f935bd8c2', '7efbddea-b507-11f0-936b-b02628506e5c', 'events', 'event_ticket', '0aa03912-cd0a-4f19-9ace-1d0f935bd8c2', 'Hei', 1, 3.00, NULL, 'vipps', 'pending', 'active', NULL, NULL, '2025-12-29 10:07:34', '2025-12-29 11:07:34', '{\"event_id\":\"3ce434d90e9f025a6901c67148c9368f\",\"is_member\":false,\"membership_plan_id\":null}', NULL),
('63b2cf04-ef59-4b74-b95e-d29f513896bc', 'EVT-5b94c5bc-9a80-425b-9dfd-18bfe72a4b2a', '7efbddea-b507-11f0-936b-b02628506e5c', 'events', 'event_ticket', '5b94c5bc-9a80-425b-9dfd-18bfe72a4b2a', 'Dansetreff med Steinar Engelbrektson ', 1, 400.00, NULL, 'cash', 'pending', 'active', NULL, NULL, '2026-01-18 10:35:18', '2026-01-18 10:35:18', '{\"event_id\":\"fc8b18485aae1b5d3890656c5ad46c39\",\"is_member\":false,\"membership_plan_id\":null}', '9d4522d0bb354d2e8dc8976571b46ac6cb0c'),
('68f817b1-9fa6-4287-b9f7-00f0832ac42b', 'EVT-16ca518c-5ebf-427d-bc15-87f1e9fc6aa7', '7efbddea-b507-11f0-936b-b02628506e5c', 'events', 'event_ticket', '16ca518c-5ebf-427d-bc15-87f1e9fc6aa7', 'Dansetreff med Steinar Engelbrektson ', 1, 400.00, NULL, 'cash', 'pending', 'active', NULL, NULL, '2026-01-19 10:21:45', '2026-01-19 10:21:45', '{\"event_id\":\"fc8b18485aae1b5d3890656c5ad46c39\",\"is_member\":false,\"membership_plan_id\":null}', '1e0d309b046171d91bffb32bf1a570856777'),
('6a8ae118-9464-47f2-a8d5-c0b1e399a166', 'EVT-a7f5c515-6e8a-4699-b4cf-575955b2639d', '7efbddea-b507-11f0-936b-b02628506e5c', 'events', 'event_ticket', 'a7f5c515-6e8a-4699-b4cf-575955b2639d', 'Dansetreff med Steinar Engelbrektson ', 1, 400.00, NULL, 'cash', 'pending', 'active', NULL, NULL, '2026-01-18 00:05:24', '2026-01-18 00:05:24', '{\"event_id\":\"fc8b18485aae1b5d3890656c5ad46c39\",\"is_member\":false,\"membership_plan_id\":null}', 'd04009ac77230a920d84006a5e801065e1a9'),
('6c0512b3-1b5e-4f59-89b8-16f1b8b5a49a', 'EVT-17085e8b-9a33-4937-a6ba-cf90fadcafa2', '7efbddea-b507-11f0-936b-b02628506e5c', 'events', 'event_ticket', '17085e8b-9a33-4937-a6ba-cf90fadcafa2', 'Dansetreff med Steinar Engebrektson ', 1, 350.00, NULL, 'cash', 'pending', 'active', NULL, NULL, '2026-01-15 10:37:58', '2026-01-15 10:37:58', '{\"event_id\":\"fc8b18485aae1b5d3890656c5ad46c39\",\"is_member\":true,\"membership_plan_id\":\"ec90a19c3209fc47d42524ef9b95ff44\"}', '69f67ba4-e752-11f0-ada2-b02628506e5c'),
('6c42c8f9-a65a-4a87-9ca4-1613915eb62b', 'EVT-5eac0281-a1f0-4a74-954a-818cf61855f9', '7efbddea-b507-11f0-936b-b02628506e5c', 'events', 'event_ticket', '5eac0281-a1f0-4a74-954a-818cf61855f9', 'Dansetreff med Steinar Engebrektson ', 1, 350.00, NULL, 'cash', 'pending', 'active', NULL, NULL, '2026-01-14 17:27:20', '2026-01-14 17:27:20', '{\"event_id\":\"fc8b18485aae1b5d3890656c5ad46c39\",\"is_member\":true,\"membership_plan_id\":null}', 'ee5349ae13b1a0192fbab286e5626e658f64'),
('6ef6c6a3-3106-47bb-8c93-a37387d5259f', 'EVENT-6998FAFD', '7efbddea-b507-11f0-936b-b02628506e5c', 'events', 'expense', 'cf5e1f98f54ed2642c117ffd7e173999', 'Lokale', 1, -10000.00, NULL, 'cash', 'completed', 'active', 'expense', 'Arrangement', '2025-12-21 05:24:14', '2025-12-21 05:24:14', '{\"event_id\":\"cf5e1f98f54ed2642c117ffd7e173999\",\"event_title\":\"Januardansen\",\"manual_entry\":true,\"added_by\":\"7f0d55c4-b507-11f0-936b-b02628506e5c\"}', NULL),
('73984e43-06d1-47c5-a0a3-984effc057bb', 'EVT-1018f7b3-a52f-4421-a671-9573d7965638', '7efbddea-b507-11f0-936b-b02628506e5c', 'events', 'event_ticket', '1018f7b3-a52f-4421-a671-9573d7965638', 'Dansetreff med Steinar Engelbrektson ', 1, 400.00, NULL, 'cash', 'pending', 'active', NULL, NULL, '2026-01-18 16:23:14', '2026-01-18 16:23:14', '{\"event_id\":\"fc8b18485aae1b5d3890656c5ad46c39\",\"is_member\":false,\"membership_plan_id\":null}', 'c671dc6594f96ab7ebf201d00cb6b6e3de1d'),
('793ee453-a580-4f2e-96db-99c3878f4fd4', 'EVT-65e99fc7-cc09-42ed-a3c1-6129b72b5fbc', '7efbddea-b507-11f0-936b-b02628506e5c', 'events', 'event_ticket', '65e99fc7-cc09-42ed-a3c1-6129b72b5fbc', 'Dansetreff med Steinar Engelbrektson ', 1, 350.00, NULL, 'cash', 'pending', 'active', NULL, NULL, '2026-01-20 08:49:48', '2026-01-20 09:49:49', '{\"event_id\":\"fc8b18485aae1b5d3890656c5ad46c39\",\"is_member\":true,\"membership_plan_id\":null}', '9862de48792bdf9248ba5fe1017fa503d69b'),
('7f1f768d-345f-4996-a388-c1fc0e5fbdcf', 'EVT-c74a3dde-fcdd-4157-b744-930d19da265e', '7efbddea-b507-11f0-936b-b02628506e5c', 'events', 'event_ticket', 'c74a3dde-fcdd-4157-b744-930d19da265e', 'Dansetreff med Steinar Engelbrektson ', 1, 400.00, NULL, 'cash', 'pending', 'active', NULL, NULL, '2026-01-20 00:44:20', '2026-01-20 00:44:20', '{\"event_id\":\"fc8b18485aae1b5d3890656c5ad46c39\",\"is_member\":false,\"membership_plan_id\":null}', NULL),
('897b3856-f034-4f4e-8d6b-bfaf32ec602a', 'EVT-7851c584-d3ca-4d82-add8-c262b2abd227', '7efbddea-b507-11f0-936b-b02628506e5c', 'events', 'event_ticket', '7851c584-d3ca-4d82-add8-c262b2abd227', 'Dansetreff med Steinar Engebrektson ', 1, 350.00, NULL, 'cash', 'pending', 'active', NULL, NULL, '2026-01-10 21:53:40', '2026-01-10 21:53:40', '{\"event_id\":\"fc8b18485aae1b5d3890656c5ad46c39\",\"is_member\":true,\"membership_plan_id\":\"ec90a19c3209fc47d42524ef9b95ff44\"}', '69f6d558-e752-11f0-ada2-b02628506e5c'),
('950427f0-fa4c-4798-a715-af0e9b2d80f0', 'EVT-a4f84251-403e-4a31-8ef8-4f23c6a88e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'events', 'event_ticket', 'a4f84251-403e-4a31-8ef8-4f23c6a88e5c', 'Dansetreff med Steinar Engebrektson ', 1, 350.00, NULL, 'cash', 'pending', 'active', NULL, NULL, '2026-01-15 10:30:44', '2026-01-15 10:30:44', '{\"event_id\":\"fc8b18485aae1b5d3890656c5ad46c39\",\"is_member\":true,\"membership_plan_id\":\"ec90a19c3209fc47d42524ef9b95ff44\"}', '69f684d1-e752-11f0-ada2-b02628506e5c'),
('96784b46-ff25-4098-8f9b-81b37ca58817', 'EVT-3530209e-2556-4c3d-9dce-e8f5c16fb7e9', '7efbddea-b507-11f0-936b-b02628506e5c', 'events', 'event_ticket', '3530209e-2556-4c3d-9dce-e8f5c16fb7e9', 'Dansetreff med Steinar Engelbrektson ', 1, 400.00, NULL, 'cash', 'pending', 'active', NULL, NULL, '2026-01-21 01:02:46', '2026-01-21 01:02:46', '{\"event_id\":\"fc8b18485aae1b5d3890656c5ad46c39\",\"is_member\":false,\"membership_plan_id\":null}', '100408a04c8725be625ef9ea5d9cd7668ab6'),
('9e2198e0-4043-4460-8928-a7a1788a7afa', 'EVT-32151b56-4547-4659-9f0b-819d7e9ffa4a', '7efbddea-b507-11f0-936b-b02628506e5c', 'events', 'event_ticket', '32151b56-4547-4659-9f0b-819d7e9ffa4a', 'Dansetreff med Steinar Engelbrektson ', 1, 400.00, NULL, 'cash', 'pending', 'active', NULL, NULL, '2026-01-18 00:00:13', '2026-01-18 00:00:13', '{\"event_id\":\"fc8b18485aae1b5d3890656c5ad46c39\",\"is_member\":false,\"membership_plan_id\":null}', 'b3c2402d19798ea78105f89a547a45605e36'),
('9e8ded26-41b7-4417-9b28-acd6f013bd7e', 'EVT-213c8cf7-6499-4578-b2bb-714f79c9406c', '7efbddea-b507-11f0-936b-b02628506e5c', 'events', 'event_ticket', '213c8cf7-6499-4578-b2bb-714f79c9406c', 'Dansetreff med Steinar Engelbrektson ', 1, 400.00, NULL, 'cash', 'pending', 'active', NULL, NULL, '2026-01-19 10:18:49', '2026-01-19 10:18:49', '{\"event_id\":\"fc8b18485aae1b5d3890656c5ad46c39\",\"is_member\":false,\"membership_plan_id\":null}', '77b10bd993e868df39f717b0d64bd9ad065f'),
('a3fde350-b28a-4316-aeb9-84c074243c94', 'EVT-fead74d9-41a1-4a7a-af67-77f06af0d80e', '7efbddea-b507-11f0-936b-b02628506e5c', 'events', 'event_ticket', 'fead74d9-41a1-4a7a-af67-77f06af0d80e', 'Dansetreff med Steinar Engelbrektson ', 1, 400.00, NULL, 'cash', 'pending', 'active', NULL, NULL, '2026-01-19 10:23:16', '2026-01-19 10:23:16', '{\"event_id\":\"fc8b18485aae1b5d3890656c5ad46c39\",\"is_member\":false,\"membership_plan_id\":null}', '7d6b1cb415df4945be13ebb8ea584412044c'),
('a4ac0c54-917d-4261-94f2-076d6849348d', 'EVT-e5a795e6-65f4-4f79-82ea-9bf749e81c91', '7efbddea-b507-11f0-936b-b02628506e5c', 'events', 'event_ticket', 'e5a795e6-65f4-4f79-82ea-9bf749e81c91', 'Dansetreff med Steinar Engebrektson ', 1, 350.00, NULL, 'cash', 'pending', 'active', NULL, NULL, '2026-01-15 13:43:09', '2026-01-15 13:43:09', '{\"event_id\":\"fc8b18485aae1b5d3890656c5ad46c39\",\"is_member\":true,\"membership_plan_id\":\"ec90a19c3209fc47d42524ef9b95ff44\"}', '69f67c62-e752-11f0-ada2-b02628506e5c'),
('a5668ce5-b973-42a9-a905-5e00e2e0ca66', 'EVT-b164a78f-106d-41be-90b9-36cb489bfe54', '7efbddea-b507-11f0-936b-b02628506e5c', 'events', 'event_ticket', 'b164a78f-106d-41be-90b9-36cb489bfe54', 'Dansetreff med Steinar Engelbrektson ', 1, 400.00, NULL, 'cash', 'pending', 'active', NULL, NULL, '2026-01-20 19:56:27', '2026-01-20 19:56:27', '{\"event_id\":\"fc8b18485aae1b5d3890656c5ad46c39\",\"is_member\":false,\"membership_plan_id\":null}', 'b5578a551dfa2b0a2a2325802764ff5641cd'),
('a9a0ab3e-2227-4bc7-bc6a-1224084b6b26', 'EVT-44b74740-5981-4fb7-88ba-db347d80f8b4', '7efbddea-b507-11f0-936b-b02628506e5c', 'events', 'event_ticket', '44b74740-5981-4fb7-88ba-db347d80f8b4', 'Dansetreff med Steinar Engebrektson ', 1, 400.00, NULL, 'cash', 'pending', 'active', NULL, NULL, '2026-01-16 05:47:36', '2026-01-16 05:47:36', '{\"event_id\":\"fc8b18485aae1b5d3890656c5ad46c39\",\"is_member\":false,\"membership_plan_id\":null}', 'ccc35a6aaebde1957a9ffdb10c87899fa0e0'),
('b014cf81-bc76-44ec-9f43-d8c96d71a0e7', 'EVT-e2b5ec1f-a737-4196-9fca-d94f9017e70c', '7efbddea-b507-11f0-936b-b02628506e5c', 'events', 'event_ticket', 'e2b5ec1f-a737-4196-9fca-d94f9017e70c', 'Dansetreff med Steinar Engebrektson ', 1, 350.00, NULL, 'cash', 'pending', 'active', NULL, NULL, '2026-01-12 14:51:34', '2026-01-12 14:51:34', '{\"event_id\":\"fc8b18485aae1b5d3890656c5ad46c39\",\"is_member\":true,\"membership_plan_id\":\"ec90a19c3209fc47d42524ef9b95ff44\"}', '69f68433-e752-11f0-ada2-b02628506e5c'),
('b019687c-5ebc-477b-9809-77465cfe2f99', 'EVT-d29c3f0e-e33a-4146-8c03-97857302b0da', '7efbddea-b507-11f0-936b-b02628506e5c', 'events', 'event_ticket', 'd29c3f0e-e33a-4146-8c03-97857302b0da', 'Dansetreff med Steinar Engebrektson ', 1, 350.00, NULL, 'cash', 'pending', 'active', NULL, NULL, '2026-01-15 17:34:52', '2026-01-15 17:34:52', '{\"event_id\":\"fc8b18485aae1b5d3890656c5ad46c39\",\"is_member\":true,\"membership_plan_id\":\"ec90a19c3209fc47d42524ef9b95ff44\"}', '69f6c282-e752-11f0-ada2-b02628506e5c'),
('b130bfe3-691a-4f06-8161-b5e705480320', 'EVT-cbe0ee8a-ab68-4df5-9ac4-c6c20ce96274', '7efbddea-b507-11f0-936b-b02628506e5c', 'events', 'event_ticket', 'cbe0ee8a-ab68-4df5-9ac4-c6c20ce96274', 'Dansetreff med Steinar Engebrektson ', 1, 350.00, NULL, 'cash', 'pending', 'active', NULL, NULL, '2026-01-10 21:53:14', '2026-01-10 21:53:14', '{\"event_id\":\"fc8b18485aae1b5d3890656c5ad46c39\",\"is_member\":true,\"membership_plan_id\":\"ec90a19c3209fc47d42524ef9b95ff44\"}', '69f6d5fb-e752-11f0-ada2-b02628506e5c'),
('b3875b4f-ffb9-4a74-9e9f-f21246d9b3e7', 'EVT-7b310eb3-1647-4d63-ad5c-4601d49d1875', '7efbddea-b507-11f0-936b-b02628506e5c', 'events', 'event_ticket', '7b310eb3-1647-4d63-ad5c-4601d49d1875', 'Dansetreff med Steinar Engelbrektson ', 1, 400.00, NULL, 'cash', 'pending', 'active', NULL, NULL, '2026-01-18 20:14:55', '2026-01-18 20:14:55', '{\"event_id\":\"fc8b18485aae1b5d3890656c5ad46c39\",\"is_member\":false,\"membership_plan_id\":null}', NULL),
('ba3b8e55-a95e-4628-ab3d-7b61003d0955', 'EVT-6c733020-a602-46bd-a6fc-cbb994ae6243', '7efbddea-b507-11f0-936b-b02628506e5c', 'events', 'event_ticket', '6c733020-a602-46bd-a6fc-cbb994ae6243', 'Dansetreff med Steinar Engelbrektson ', 1, 400.00, NULL, 'cash', 'pending', 'active', NULL, NULL, '2026-01-19 10:25:31', '2026-01-19 10:25:31', '{\"event_id\":\"fc8b18485aae1b5d3890656c5ad46c39\",\"is_member\":false,\"membership_plan_id\":null}', 'b5494d56f18d3438a2c8a4af9d76817356ab'),
('c32f2273-7b66-43e7-87e3-39c3db2f8ee3', 'EVT-25513396-0a62-4f5a-9c1b-e826fc28c61d', '7efbddea-b507-11f0-936b-b02628506e5c', 'events', 'event_ticket', '25513396-0a62-4f5a-9c1b-e826fc28c61d', 'Dansetreff med Steinar Engebrektson ', 1, 350.00, NULL, 'cash', 'pending', 'active', NULL, NULL, '2026-01-16 17:06:21', '2026-01-16 17:06:21', '{\"event_id\":\"fc8b18485aae1b5d3890656c5ad46c39\",\"is_member\":true,\"membership_plan_id\":null}', 'fead9ff82716fb6586dec237c8211a073b8d'),
('ca6804b4-f6aa-4c25-9a19-25d0ee421cf0', 'EVT-46efb8e9-5195-4e9a-9f86-d8eb096dc06e', '7efbddea-b507-11f0-936b-b02628506e5c', 'events', 'event_ticket', '46efb8e9-5195-4e9a-9f86-d8eb096dc06e', 'Dansetreff med Steinar Engelbrektson ', 1, 400.00, NULL, 'cash', 'pending', 'active', NULL, NULL, '2026-01-19 10:24:50', '2026-01-19 10:24:50', '{\"event_id\":\"fc8b18485aae1b5d3890656c5ad46c39\",\"is_member\":false,\"membership_plan_id\":null}', '6464d76a21886c2e71638c9e85a9ff7889a5'),
('cab54a4a-dae5-4d4e-bcb2-6306a661629e', 'EVT-d9d9f1c7-3319-4494-9798-d51d024a98fb', '7efbddea-b507-11f0-936b-b02628506e5c', 'events', 'event_ticket', 'd9d9f1c7-3319-4494-9798-d51d024a98fb', 'Dansetreff med Steinar Engelbrektson ', 1, 400.00, NULL, 'cash', 'pending', 'active', NULL, NULL, '2026-01-19 10:22:35', '2026-01-19 10:22:35', '{\"event_id\":\"fc8b18485aae1b5d3890656c5ad46c39\",\"is_member\":false,\"membership_plan_id\":null}', '0451523f085a24ddf332298b2957eb2de3f1'),
('cec8fff0-a7ac-4a3a-8f3d-c48e2c0ea131', 'EVT-12d4b095-2ec1-45a7-9d8e-3d90d3f7dff4', '7efbddea-b507-11f0-936b-b02628506e5c', 'events', 'event_ticket', '12d4b095-2ec1-45a7-9d8e-3d90d3f7dff4', 'Dansetreff med Steinar Engelbrektson ', 1, 350.00, NULL, 'cash', 'pending', 'active', NULL, NULL, '2026-01-20 09:39:10', '2026-01-20 09:39:10', '{\"event_id\":\"fc8b18485aae1b5d3890656c5ad46c39\",\"is_member\":true,\"membership_plan_id\":null}', NULL),
('cfc4d64f-813b-4f72-b307-032602e6f8a9', 'EVT-971f458f-8d80-495e-95d8-7e68ef780a9b', '7efbddea-b507-11f0-936b-b02628506e5c', 'events', 'event_ticket', '971f458f-8d80-495e-95d8-7e68ef780a9b', 'Dansetreff med Steinar Engelbrektson ', 1, 350.00, NULL, 'cash', 'pending', 'active', NULL, NULL, '2026-01-21 01:11:57', '2026-01-21 01:11:57', '{\"event_id\":\"fc8b18485aae1b5d3890656c5ad46c39\",\"is_member\":true,\"membership_plan_id\":null}', 'f5910bfa5f03f754a6cb2bd11b856cf744dc'),
('d0f505a1-5bf4-4d20-86ed-080473161439', 'EVT-fe54301a-121f-4579-98d4-47058d64aa3c', '7efbddea-b507-11f0-936b-b02628506e5c', 'events', 'event_ticket', 'fe54301a-121f-4579-98d4-47058d64aa3c', 'Dansetreff med Steinar Engebrektson ', 1, 350.00, NULL, 'cash', 'pending', 'active', NULL, NULL, '2026-01-15 10:00:06', '2026-01-15 10:00:06', '{\"event_id\":\"fc8b18485aae1b5d3890656c5ad46c39\",\"is_member\":true,\"membership_plan_id\":\"ec90a19c3209fc47d42524ef9b95ff44\"}', '69f67dab-e752-11f0-ada2-b02628506e5c'),
('d14e7920-e22c-411a-a43a-009607307575', 'EVT-98ca48cf-17e1-4c65-8631-7a06bee07e6b', '7efbddea-b507-11f0-936b-b02628506e5c', 'events', 'event_ticket', '98ca48cf-17e1-4c65-8631-7a06bee07e6b', 'Dansetreff med Steinar Engebrektson ', 1, 350.00, NULL, 'cash', 'pending', 'active', NULL, NULL, '2026-01-09 07:29:40', '2026-01-09 07:29:40', '{\"event_id\":\"fc8b18485aae1b5d3890656c5ad46c39\",\"is_member\":true,\"membership_plan_id\":\"ec90a19c3209fc47d42524ef9b95ff44\"}', '69f6c1d5-e752-11f0-ada2-b02628506e5c'),
('d1569802-90b8-4f6f-99d6-e2e65cbc1b49', 'EVT-299407cc-ff24-45b4-b332-080ff035d909', '7efbddea-b507-11f0-936b-b02628506e5c', 'events', 'event_ticket', '299407cc-ff24-45b4-b332-080ff035d909', 'Dansetreff med Steinar Engebrektson ', 1, 400.00, NULL, 'cash', 'pending', 'active', NULL, NULL, '2026-01-15 10:32:58', '2026-01-15 10:32:58', '{\"event_id\":\"fc8b18485aae1b5d3890656c5ad46c39\",\"is_member\":false,\"membership_plan_id\":null}', NULL),
('d3156b12-3f21-4d59-a912-28beefcb5a30', 'EVT-9923e7c4-db5b-4506-a766-ec4bf47993f8', '7efbddea-b507-11f0-936b-b02628506e5c', 'events', 'event_ticket', '9923e7c4-db5b-4506-a766-ec4bf47993f8', 'Dansetreff med Steinar Engebrektson ', 1, 400.00, NULL, 'cash', 'pending', 'active', NULL, NULL, '2026-01-07 23:52:36', '2026-01-07 23:52:36', '{\"event_id\":\"fc8b18485aae1b5d3890656c5ad46c39\",\"is_member\":true,\"membership_plan_id\":\"c756c21b869ae63c37e015f8ac88cea2\"}', '69f67ba4-e752-11f0-ada2-b02628506e5c'),
('d4305d39-1797-4ecc-86bd-33dc5fee8f08', 'EVT-10455600-3ac7-4925-a145-75e0b016ea99', '7efbddea-b507-11f0-936b-b02628506e5c', 'events', 'event_ticket', '10455600-3ac7-4925-a145-75e0b016ea99', 'Dansetreff med Steinar Engebrektson ', 1, 400.00, NULL, 'cash', 'pending', 'active', NULL, NULL, '2026-01-09 07:29:12', '2026-01-09 07:29:12', '{\"event_id\":\"fc8b18485aae1b5d3890656c5ad46c39\",\"is_member\":true,\"membership_plan_id\":\"c756c21b869ae63c37e015f8ac88cea2\"}', '69f67f15-e752-11f0-ada2-b02628506e5c'),
('d9ded112-5dbd-444b-97f6-13d86773daa9', 'EVT-b1787d59-5ba2-40aa-96ff-96989cc3087b', '7efbddea-b507-11f0-936b-b02628506e5c', 'events', 'event_ticket', 'b1787d59-5ba2-40aa-96ff-96989cc3087b', 'Dansetreff med Steinar Engelbrektson ', 1, 400.00, NULL, 'cash', 'pending', 'active', NULL, NULL, '2026-01-19 10:24:01', '2026-01-19 10:24:01', '{\"event_id\":\"fc8b18485aae1b5d3890656c5ad46c39\",\"is_member\":false,\"membership_plan_id\":null}', 'ede0496b4118a5e712cd933e3314ffaa892f'),
('dcf2cae1-c4a7-434c-bdef-1330c74bb4e2', 'EVT-8f982747-f71e-419c-89cf-c10b08fb4d81', '7efbddea-b507-11f0-936b-b02628506e5c', 'events', 'event_ticket', '8f982747-f71e-419c-89cf-c10b08fb4d81', 'Dansetreff med Steinar Engelbrektson ', 1, 350.00, NULL, 'cash', 'pending', 'active', NULL, NULL, '2026-01-20 09:13:42', '2026-01-20 09:13:42', '{\"event_id\":\"fc8b18485aae1b5d3890656c5ad46c39\",\"is_member\":true,\"membership_plan_id\":null}', '9aeaa4a51b0b8451adec03ce6bbe0898a859'),
('ddff5c88-bca7-498e-ba6b-06207663f12f', 'EVT-3d53b0d6-1e60-4ebd-9c4d-66097fdd4157', '7efbddea-b507-11f0-936b-b02628506e5c', 'events', 'event_ticket', '3d53b0d6-1e60-4ebd-9c4d-66097fdd4157', 'Dansetreff med Steinar Engelbrektson ', 1, 400.00, NULL, 'cash', 'pending', 'active', NULL, NULL, '2026-01-18 10:35:18', '2026-01-18 10:35:18', '{\"event_id\":\"fc8b18485aae1b5d3890656c5ad46c39\",\"is_member\":false,\"membership_plan_id\":null}', '126ad5cce3731f36e5e4fcc35fe7071bf427'),
('e44a80ec-d2e0-4e3f-a619-ac93463a3df2', 'EVT-e76a2423-5ebc-4035-885e-5b5b5188ce9c', '7efbddea-b507-11f0-936b-b02628506e5c', 'events', 'event_ticket', 'e76a2423-5ebc-4035-885e-5b5b5188ce9c', 'Dansetreff med Steinar Engelbrektson ', 1, 350.00, NULL, 'cash', 'pending', 'active', NULL, NULL, '2026-01-20 19:55:49', '2026-01-20 19:55:49', '{\"event_id\":\"fc8b18485aae1b5d3890656c5ad46c39\",\"is_member\":true,\"membership_plan_id\":null}', '25cc3f4b5c68868c67f159018d1c8a74f86c'),
('e692d5fb-8432-496b-82fd-543e0f8b2845', 'EVT-7c4f7c6f-0a1b-4218-a645-886b7f67f07e', '7efbddea-b507-11f0-936b-b02628506e5c', 'events', 'event_ticket', '7c4f7c6f-0a1b-4218-a645-886b7f67f07e', 'Dansetreff med Steinar Engebrektson ', 1, 350.00, NULL, 'cash', 'pending', 'active', NULL, NULL, '2026-01-13 23:22:23', '2026-01-13 23:22:23', '{\"event_id\":\"fc8b18485aae1b5d3890656c5ad46c39\",\"is_member\":true,\"membership_plan_id\":\"ec90a19c3209fc47d42524ef9b95ff44\"}', '69f68bbe-e752-11f0-ada2-b02628506e5c'),
('e7323df6-2950-4626-9497-954e32e7038b', 'EVT-c9600f91-9edd-4547-9238-c7d7792042ea', '7efbddea-b507-11f0-936b-b02628506e5c', 'events', 'event_ticket', 'c9600f91-9edd-4547-9238-c7d7792042ea', 'Dansetreff med Steinar Engelbrektson ', 1, 400.00, NULL, 'cash', 'pending', 'active', NULL, NULL, '2026-01-18 10:35:18', '2026-01-18 10:35:18', '{\"event_id\":\"fc8b18485aae1b5d3890656c5ad46c39\",\"is_member\":false,\"membership_plan_id\":null}', '15944770a5255ab2256b1540c674690f7ab0'),
('e75a5c42-105b-4efd-9157-4891a62c2120', 'EVT-33f55092-88b0-45f2-bf23-192726514b8b', '7efbddea-b507-11f0-936b-b02628506e5c', 'events', 'event_ticket', '33f55092-88b0-45f2-bf23-192726514b8b', 'Dansetreff med Steinar Engelbrektson ', 1, 400.00, NULL, 'cash', 'pending', 'active', NULL, NULL, '2026-01-19 19:01:52', '2026-01-19 19:01:52', '{\"event_id\":\"fc8b18485aae1b5d3890656c5ad46c39\",\"is_member\":false,\"membership_plan_id\":null}', 'f5fa7c635ec8f00b60f79e6b8323489109b1'),
('e7fa5781-a661-43e6-af31-fcb7f7226fd4', 'EVT-9d37e3e0-1431-40ec-a4c8-558f6f74023e', '7efbddea-b507-11f0-936b-b02628506e5c', 'events', 'event_ticket', '9d37e3e0-1431-40ec-a4c8-558f6f74023e', 'Dansetreff med Steinar Engebrektson ', 1, 400.00, NULL, 'cash', 'pending', 'active', NULL, NULL, '2026-01-16 05:47:36', '2026-01-16 05:47:36', '{\"event_id\":\"fc8b18485aae1b5d3890656c5ad46c39\",\"is_member\":false,\"membership_plan_id\":null}', 'c86c82006d19f8cf1c5a7ba4dbf5e4653443'),
('f3187e01-5131-4fcf-bcae-39f6eda752b3', 'EVT-d6877ac9-b9c4-4414-91a2-1a215ce1e627', '7efbddea-b507-11f0-936b-b02628506e5c', 'events', 'event_ticket', 'd6877ac9-b9c4-4414-91a2-1a215ce1e627', 'Dansetreff med Steinar Engelbrektson ', 1, 400.00, NULL, 'cash', 'pending', 'active', NULL, NULL, '2026-01-19 10:19:46', '2026-01-19 10:19:46', '{\"event_id\":\"fc8b18485aae1b5d3890656c5ad46c39\",\"is_member\":false,\"membership_plan_id\":null}', '1d3d03015dfb1b6e7e23eeac23ae28fde5f3'),
('f4f72b14-ce75-4e1a-88b8-0636d153866f', 'EVT-be3083b9-be06-4e2f-a4cc-ba6a0b1da367', '7efbddea-b507-11f0-936b-b02628506e5c', 'events', 'event_ticket', 'be3083b9-be06-4e2f-a4cc-ba6a0b1da367', 'Hei', 1, 2.00, NULL, 'vipps', 'pending', 'active', NULL, NULL, '2025-12-29 11:33:00', '2025-12-29 12:33:01', '{\"event_id\":\"3ce434d90e9f025a6901c67148c9368f\",\"is_member\":true,\"membership_plan_id\":null}', NULL),
('f891b747-4d9a-448b-9506-3b63bbcbf80a', 'EVT-4c21ffaa-f8e4-4784-9295-e6ae1a930318', '7efbddea-b507-11f0-936b-b02628506e5c', 'events', 'event_ticket', '4c21ffaa-f8e4-4784-9295-e6ae1a930318', 'Dansetreff med Steinar Engelbrektson ', 1, 400.00, NULL, 'cash', 'pending', 'active', NULL, NULL, '2026-01-18 10:35:18', '2026-01-18 10:35:18', '{\"event_id\":\"fc8b18485aae1b5d3890656c5ad46c39\",\"is_member\":false,\"membership_plan_id\":null}', '18f9e21b335888b6e9db97243ac05466a29c'),
('f9e7d0e1-8f7e-4527-91aa-26cb08e5fd8c', 'EVT-88ec4f41-7577-4d1c-bed0-07aa8ac9c3fc', '7efbddea-b507-11f0-936b-b02628506e5c', 'events', 'event_ticket', '88ec4f41-7577-4d1c-bed0-07aa8ac9c3fc', 'Dansetreff med Steinar Engebrektson ', 1, 350.00, NULL, 'cash', 'pending', 'active', NULL, NULL, '2026-01-10 21:39:22', '2026-01-10 21:39:22', '{\"event_id\":\"fc8b18485aae1b5d3890656c5ad46c39\",\"is_member\":true,\"membership_plan_id\":null}', NULL);

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
-- Indexes for table `announcement_products`
--
ALTER TABLE `announcement_products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_announcement_product` (`announcement_id`,`output_product_id`),
  ADD KEY `idx_company` (`company_id`),
  ADD KEY `idx_announcement` (`announcement_id`),
  ADD KEY `idx_output_product` (`output_product_id`);

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
-- Indexes for table `economy_configuration_data`
--
ALTER TABLE `economy_configuration_data`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_company_gateway` (`company_id`,`gateway_type`),
  ADD KEY `idx_economy_config_company` (`company_id`),
  ADD KEY `idx_economy_config_gateway` (`gateway_type`),
  ADD KEY `idx_economy_config_active` (`is_active`),
  ADD KEY `fk_economy_config_saas_admin` (`configurated_by`);

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
  ADD KEY `created_by` (`created_by`),
  ADD KEY `configuration_id` (`configuration_id`);

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
  ADD KEY `fk_events_checkin_registration` (`registration_id`),
  ADD KEY `idx_checkin_group` (`group_id`);

--
-- Indexes for table `events_checkin_groups`
--
ALTER TABLE `events_checkin_groups`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_group_event` (`event_id`),
  ADD KEY `idx_group_status` (`payment_status`);

--
-- Indexes for table `events_eventdata`
--
ALTER TABLE `events_eventdata`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_share_code` (`share_code`),
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
  ADD UNIQUE KEY `idx_share_code` (`share_code`),
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
-- Indexes for table `reservation_services`
--
ALTER TABLE `reservation_services`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_share_code` (`share_code`),
  ADD KEY `company_id` (`company_id`),
  ADD KEY `reservation_services_ibfk_2` (`output_product_id`),
  ADD KEY `fk_reservation_services_created_by` (`created_by`);

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
  ADD UNIQUE KEY `unique_booking_time` (`company_id`,`booking_date`,`start_time`,`staff_id`),
  ADD KEY `idx_service_bookings_staff_date` (`staff_id`,`booking_date`),
  ADD KEY `idx_service_bookings_company` (`company_id`),
  ADD KEY `idx_service_bookings_service` (`service_id`),
  ADD KEY `idx_service_bookings_customer` (`customer_id`),
  ADD KEY `idx_service_bookings_status` (`status`);

--
-- Indexes for table `staff_availability`
--
ALTER TABLE `staff_availability`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_staff_date` (`company_id`,`staff_id`,`date`),
  ADD KEY `idx_staff_availability_staff` (`staff_id`),
  ADD KEY `idx_staff_availability_date_range` (`date`,`start_time`,`end_time`),
  ADD KEY `idx_staff_availability_type` (`availability_type`);

--
-- Indexes for table `staff_services`
--
ALTER TABLE `staff_services`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_staff_service` (`company_id`,`staff_id`,`service_id`),
  ADD KEY `staff_id` (`staff_id`),
  ADD KEY `service_id` (`service_id`);

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
  ADD UNIQUE KEY `company_id_2` (`company_id`,`email`),
  ADD KEY `idx_last_audit_log` (`last_audit_log_id`);

--
-- Indexes for table `tenant_polling_tracker`
--
ALTER TABLE `tenant_polling_tracker`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_company_module_source` (`company_id`,`module_source`,`polling_source`),
  ADD KEY `idx_company_polling` (`company_id`,`polling_status`),
  ADD KEY `idx_next_poll` (`next_poll_at`,`polling_status`),
  ADD KEY `idx_module_source` (`module_source`,`polling_source`);

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
  ADD KEY `idx_unified_ledger_status` (`status`),
  ADD KEY `idx_unified_ledger_customer_id` (`customer_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `announcement_products`
--
ALTER TABLE `announcement_products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `sales_cart`
--
ALTER TABLE `sales_cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT for table `sales_transactions`
--
ALTER TABLE `sales_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `tenant_admin_sessions`
--
ALTER TABLE `tenant_admin_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=562;

--
-- Begrensninger for dumpede tabeller
--

--
-- Begrensninger for tabell `activity_summaries`
--
ALTER TABLE `activity_summaries`
  ADD CONSTRAINT `activity_summaries_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE;

--
-- Begrensninger for tabell `announcement_products`
--
ALTER TABLE `announcement_products`
  ADD CONSTRAINT `fk_ann_prod_announcement` FOREIGN KEY (`announcement_id`) REFERENCES `tenant_announcements` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ann_prod_company` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`);

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
-- Begrensninger for tabell `economy_configuration_data`
--
ALTER TABLE `economy_configuration_data`
  ADD CONSTRAINT `economy_configuration_data_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `economy_configuration_data_ibfk_2` FOREIGN KEY (`configurated_by`) REFERENCES `saas_admins` (`id`) ON DELETE CASCADE;

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
  ADD CONSTRAINT `economy_payment_gateways_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `tenant_admins` (`id`),
  ADD CONSTRAINT `economy_payment_gateways_ibfk_3` FOREIGN KEY (`configuration_id`) REFERENCES `economy_configuration_data` (`id`) ON DELETE SET NULL;

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
-- Begrensninger for tabell `reservation_services`
--
ALTER TABLE `reservation_services`
  ADD CONSTRAINT `fk_reservation_services_created_by` FOREIGN KEY (`created_by`) REFERENCES `tenant_admins` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `reservation_services_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reservation_services_ibfk_2` FOREIGN KEY (`output_product_id`) REFERENCES `output_products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `reservation_services_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `tenant_admins` (`id`) ON DELETE SET NULL;

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
  ADD CONSTRAINT `service_bookings_ibfk_2` FOREIGN KEY (`service_id`) REFERENCES `reservation_services` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `service_bookings_ibfk_3` FOREIGN KEY (`customer_id`) REFERENCES `tenant_customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `service_bookings_ibfk_4` FOREIGN KEY (`staff_id`) REFERENCES `tenant_staff` (`id`) ON DELETE SET NULL;

--
-- Begrensninger for tabell `staff_availability`
--
ALTER TABLE `staff_availability`
  ADD CONSTRAINT `staff_availability_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `staff_availability_ibfk_2` FOREIGN KEY (`staff_id`) REFERENCES `tenant_staff` (`id`) ON DELETE CASCADE;

--
-- Begrensninger for tabell `staff_services`
--
ALTER TABLE `staff_services`
  ADD CONSTRAINT `staff_services_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `staff_services_ibfk_2` FOREIGN KEY (`staff_id`) REFERENCES `tenant_staff` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `staff_services_ibfk_3` FOREIGN KEY (`service_id`) REFERENCES `reservation_services` (`id`) ON DELETE CASCADE;

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
  ADD CONSTRAINT `fk_tenant_customers_last_audit_log` FOREIGN KEY (`last_audit_log_id`) REFERENCES `audit_logs` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `tenant_customers_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE;

--
-- Begrensninger for tabell `tenant_polling_tracker`
--
ALTER TABLE `tenant_polling_tracker`
  ADD CONSTRAINT `fk_tenant_polling_tracker_company` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE;

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
  ADD CONSTRAINT `fk_unified_ledger_customer` FOREIGN KEY (`customer_id`) REFERENCES `tenant_customers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `unified_ledger_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
