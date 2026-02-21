-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: salesmanno04.mysql.domeneshop.no
-- Generation Time: 21. Feb, 2026 10:44 AM
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

--
-- Indexes for dumped tables
--

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
-- Begrensninger for dumpede tabeller
--

--
-- Begrensninger for tabell `rental_objects`
--
ALTER TABLE `rental_objects`
  ADD CONSTRAINT `rental_objects_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `rental_objects_ibfk_2` FOREIGN KEY (`output_product_id`) REFERENCES `output_products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
