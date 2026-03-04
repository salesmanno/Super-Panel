-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: salesmanno04.mysql.domeneshop.no
-- Generation Time: 04. Mar, 2026 09:59 AM
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

--
-- Indexes for dumped tables
--

--
-- Indexes for table `economy_invoices`
--
ALTER TABLE `economy_invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `company_id` (`company_id`,`invoice_number`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `idx_economy_invoices_customer_type` (`customer_entity_type`);

--
-- Begrensninger for dumpede tabeller
--

--
-- Begrensninger for tabell `economy_invoices`
--
ALTER TABLE `economy_invoices`
  ADD CONSTRAINT `economy_invoices_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `economy_invoices_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `tenant_customers` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
