-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: salesmanno04.mysql.domeneshop.no
-- Generation Time: 19. Jan, 2026 23:01 PM
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
  `imap_password_encrypted` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dataark for tabell `email_configs`
--

INSERT INTO `email_configs` (`id`, `company_id`, `email`, `display_name`, `smtp_host`, `smtp_port`, `smtp_username`, `smtp_password_encrypted`, `is_active`, `created_at`, `updated_at`, `imap_host`, `imap_port`, `imap_username`, `imap_password_encrypted`) VALUES
('d7652502-f4b0-11f0-abb4-b02628506e5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'bergsoy.d-k@salesman.no', 'Bergsøy Danseklubb', 'bergsoy.d-k@salesman.no', 587, 'salesman4', 'ibis-vevre-Nop-bulka-1700', 1, '2026-01-18 22:01:21', '2026-01-19 23:59:36', 'imap.domeneshop.no', 993, 'salesman4', 'ibis-vevre-Nop-bulka-1700');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `email_configs`
--
ALTER TABLE `email_configs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `company_id` (`company_id`);

--
-- Begrensninger for dumpede tabeller
--

--
-- Begrensninger for tabell `email_configs`
--
ALTER TABLE `email_configs`
  ADD CONSTRAINT `email_configs_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
