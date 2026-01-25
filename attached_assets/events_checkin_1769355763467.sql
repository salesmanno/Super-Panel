-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: salesmanno04.mysql.domeneshop.no
-- Generation Time: 25. Jan, 2026 15:42 PM
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
('1865cbffb8020f1b27935320d1a9654a', '46efb8e9-5195-4e9a-9f86-d8eb096dc06e', 'fc8b18485aae1b5d3890656c5ad46c39', '69f6c70d-e752-11f0-ada2-b02628506e5c', NULL, 400.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-19 11:24:27', '2026-01-21 01:27:35', '000000899949', NULL, NULL, 0),
('220a91bd3a27adde548398907995527c7c9d', '9da7929f-9377-43fd-90c4-5d8360df9ab3', 'fc8b18485aae1b5d3890656c5ad46c39', '69f68f8a-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 350.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-24 11:11:08', '2026-01-24 11:11:08', NULL, NULL, NULL, 1),
('22eda70a0bea4f03b04650aad37aa906', '55ad7edf-79a9-43ab-b9d8-06169c38a317', 'fc8b18485aae1b5d3890656c5ad46c39', '69f6d6a3-e752-11f0-ada2-b02628506e5c', NULL, 400.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-18 19:24:03', '2026-01-21 01:28:37', '000083682674', NULL, NULL, 0),
('240da0436c8233dbf9f75e77cb8584d2829f', '72785e99-dc75-4000-9079-7a63f123c5a4', 'fc8b18485aae1b5d3890656c5ad46c39', '69f68a3b-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 350.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-22 17:34:20', '2026-01-22 17:34:20', NULL, NULL, NULL, 1),
('24988c5ab4fbb6c92937cda7a364d8832ac8', '82fe5a54-87dc-4a64-8af9-3006b818b68c', 'fc8b18485aae1b5d3890656c5ad46c39', '69f6bc66-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 350.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-14 11:01:22', '2026-01-14 11:01:22', NULL, NULL, NULL, 1),
('2ffebd165e0fa87a1817e8b5183e7050a6cd', 'fe54301a-121f-4579-98d4-47058d64aa3c', 'fc8b18485aae1b5d3890656c5ad46c39', '69f67dab-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 350.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-15 10:00:06', '2026-01-15 10:00:06', NULL, NULL, NULL, 1),
('3cc1aeee0f8778c12e681433f71e1b8d94f1', 'e2b5ec1f-a737-4196-9fca-d94f9017e70c', 'fc8b18485aae1b5d3890656c5ad46c39', '69f68433-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 350.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-12 14:51:34', '2026-01-12 14:51:34', NULL, NULL, NULL, 1),
('46930cabcac57e758becc8ce9a6bdf67e422', 'e9e68d0c-2579-47cd-9f08-85a4a45940b4', 'fc8b18485aae1b5d3890656c5ad46c39', '69f69cfa-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 350.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-22 17:34:20', '2026-01-22 17:34:20', NULL, NULL, NULL, 1),
('4a4dce36fa5f7017af3b9ac76c3dd3c8', 'c9600f91-9edd-4547-9238-c7d7792042ea', 'fc8b18485aae1b5d3890656c5ad46c39', '15944770a5255ab2256b1540c674690f7ab0', NULL, 400.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-18 12:56:09', '2026-01-18 12:56:09', '000004072676', NULL, NULL, 0),
('4a75a5083a794ad21362b63327c8f98999d4', 'e76a2423-5ebc-4035-885e-5b5b5188ce9c', 'fc8b18485aae1b5d3890656c5ad46c39', '69f692ad-e752-11f0-ada2-b02628506e5c', NULL, 350.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-20 19:55:49', '2026-01-21 01:25:55', NULL, NULL, NULL, 1),
('4c075708d4de46ef7bc5e624233cd7509e13', '8b22136f-d044-4c6a-9859-de3205e0e8c8', 'fc8b18485aae1b5d3890656c5ad46c39', '69f699af-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 350.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-14 11:01:22', '2026-01-14 11:01:22', NULL, NULL, NULL, 1),
('4cd129e96119a08515f8a4df26d8c718aa2c', '971f458f-8d80-495e-95d8-7e68ef780a9b', 'fc8b18485aae1b5d3890656c5ad46c39', '69f67e68-e752-11f0-ada2-b02628506e5c', NULL, 350.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-21 01:11:57', '2026-01-21 01:25:55', NULL, NULL, NULL, 1),
('4e9003e244246c67672efddd4f1cae99', '219962e1-b43e-483b-9fb5-def99114ce91', 'fc8b18485aae1b5d3890656c5ad46c39', 'a467996f28c150ab799bb9e5d247b42c46a3', NULL, 400.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-19 11:25:15', '2026-01-19 11:25:15', '036601533463', NULL, NULL, 0),
('579f21f14d55d3ccef8c23b36be6efaa', '26b736d6-9841-48a3-af14-6b493d7ca7f6', 'fc8b18485aae1b5d3890656c5ad46c39', 'd9ea2f0ab8930466f89a72ab2891ddfc15aa', NULL, 400.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-20 09:15:34', '2026-01-20 09:15:34', '000041745281', NULL, NULL, 0),
('5843e5e41c48cb1cd80146df6641bc2c', 'c5611fe6-fdac-469a-8426-9a75ac4a946b', 'fc8b18485aae1b5d3890656c5ad46c39', 'aa9d757529e17a7420904344cfd7f705bedf', NULL, 400.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-18 12:56:02', '2026-01-18 12:56:02', '000272430994', NULL, NULL, 0),
('5b95dabbc6f4b68f8e50a9b8feb4218e', '5b94c5bc-9a80-425b-9dfd-18bfe72a4b2a', 'fc8b18485aae1b5d3890656c5ad46c39', '69f6d4ae-e752-11f0-ada2-b02628506e5c', NULL, 400.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-18 12:55:55', '2026-01-21 01:27:35', '000008585714', NULL, NULL, 0),
('5cab63356bfaa9c9e46cbf767c9be959ed77', '23b5ad1d-c7b9-4dd4-8ba8-46ccf879f814', 'fc8b18485aae1b5d3890656c5ad46c39', '69f6a493-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 350.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-15 10:26:25', '2026-01-15 10:26:25', NULL, NULL, NULL, 1),
('65b79be68d76a10c1c4406a20cdedaaa6356', '9344192b-fb4c-40a8-bec3-db3a18ec31d3', 'fc8b18485aae1b5d3890656c5ad46c39', '69f6c081-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 350.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-16 17:09:00', '2026-01-16 17:09:00', NULL, NULL, NULL, 1),
('68c572a237fa85cec7ce1c0094c94051', 'd6877ac9-b9c4-4414-91a2-1a215ce1e627', 'fc8b18485aae1b5d3890656c5ad46c39', '1d3d03015dfb1b6e7e23eeac23ae28fde5f3', NULL, 400.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-19 11:25:02', '2026-01-19 11:25:02', '000046957665', NULL, NULL, 0),
('70084f8a13bcb86b876102104f42c6f35300', '7d99357f-6bc2-4981-9bca-8bfbdf22b222', 'fc8b18485aae1b5d3890656c5ad46c39', '69f68e45-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 350.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-24 11:09:25', '2026-01-24 11:09:25', NULL, NULL, NULL, 1),
('71342cffc5bd7f498bb13daeaa1abdaf', '16ca518c-5ebf-427d-bc15-87f1e9fc6aa7', 'fc8b18485aae1b5d3890656c5ad46c39', '1e0d309b046171d91bffb32bf1a570856777', NULL, 400.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-19 11:24:55', '2026-01-19 11:24:55', '000487123346', NULL, NULL, 0),
('71fe6dd74275770f8b1c89318c8af5f0', '44b74740-5981-4fb7-88ba-db347d80f8b4', 'fc8b18485aae1b5d3890656c5ad46c39', 'ccc35a6aaebde1957a9ffdb10c87899fa0e0', NULL, 400.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-16 05:48:34', '2026-01-16 05:48:34', '000077832269', NULL, NULL, 0),
('7a6de12b0fae025d87eb7c736fbe54139f9d', 'd29c3f0e-e33a-4146-8c03-97857302b0da', 'fc8b18485aae1b5d3890656c5ad46c39', '69f6c282-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 350.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-15 17:34:52', '2026-01-15 17:34:52', NULL, NULL, NULL, 1),
('7d3d74c68d4726243521f224bcf35a8e583d', 'ed85ab0b-a6a4-428a-8da6-7d4ee2eff268', 'fc8b18485aae1b5d3890656c5ad46c39', '69f6dd2a-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 350.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-25 14:42:20', '2026-01-25 14:42:20', NULL, NULL, NULL, 1),
('7e494d360252fe5a977972c47244659b', '1018f7b3-a52f-4421-a671-9573d7965638', 'fc8b18485aae1b5d3890656c5ad46c39', '69f6d752-e752-11f0-ada2-b02628506e5c', NULL, 400.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-18 19:23:58', '2026-01-21 01:28:37', '000008328748', NULL, NULL, 0),
('8232ab97c3f3c6bff269fdc41b187374', 'b164a78f-106d-41be-90b9-36cb489bfe54', 'fc8b18485aae1b5d3890656c5ad46c39', 'b5578a551dfa2b0a2a2325802764ff5641cd', NULL, 400.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-20 19:57:09', '2026-01-20 19:57:09', '005895550685', NULL, NULL, 0),
('877772d70213e6b58e441a362f7a10bf', '33f55092-88b0-45f2-bf23-192726514b8b', 'fc8b18485aae1b5d3890656c5ad46c39', 'f5fa7c635ec8f00b60f79e6b8323489109b1', NULL, 400.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-20 00:40:24', '2026-01-20 00:40:24', '006801469046', NULL, NULL, 0),
('8b896dd4aa82bb4c740e8009a5eb9c0b', '213c8cf7-6499-4578-b2bb-714f79c9406c', 'fc8b18485aae1b5d3890656c5ad46c39', '77b10bd993e868df39f717b0d64bd9ad065f', NULL, 400.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-19 11:25:06', '2026-01-19 11:25:06', '000035514640', NULL, NULL, 0),
('8ba39d2557534e76a47b969bdda6f6c8', '6323741e-7833-44d6-bbac-b17be71e08b9', 'fc8b18485aae1b5d3890656c5ad46c39', 'e1f419b5c70dd2db0198259fc35af19a468a', NULL, 400.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-22 19:34:10', '2026-01-22 19:34:10', '000000895293', NULL, NULL, 0),
('8e36a2e21eed57d71019cf84ae10129b', '667293d7-c334-4923-9d8e-74d17ae20062', 'fc8b18485aae1b5d3890656c5ad46c39', '148176b84267e1fbfa9c5fd08e21285dde07', NULL, 400.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-20 00:40:30', '2026-01-20 00:40:30', '000002186382', NULL, NULL, 0),
('8fc07508fdb5dad1749e7104619cbe77', 'b1787d59-5ba2-40aa-96ff-96989cc3087b', 'fc8b18485aae1b5d3890656c5ad46c39', 'ede0496b4118a5e712cd933e3314ffaa892f', NULL, 400.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-19 11:24:31', '2026-01-19 11:24:31', '000038620775', NULL, NULL, 0),
('9071233123afb63b8e3e61e694d861ace539', 'a4f84251-403e-4a31-8ef8-4f23c6a88e5c', 'fc8b18485aae1b5d3890656c5ad46c39', '69f684d1-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 350.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-15 10:30:44', '2026-01-15 10:30:44', NULL, NULL, NULL, 1),
('9089ac4773469d1fab2aa22574bc444ea6fa', '30c97fbe-f78e-4d02-8acb-385964e2b28f', 'fc8b18485aae1b5d3890656c5ad46c39', '69f6a3f6-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 350.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-14 17:28:12', '2026-01-14 17:28:12', NULL, NULL, NULL, 1),
('92d15522a96156f6f010f0d672915dc4ec6d', '25513396-0a62-4f5a-9c1b-e826fc28c61d', 'fc8b18485aae1b5d3890656c5ad46c39', '69f6bf19-e752-11f0-ada2-b02628506e5c', NULL, 350.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-16 17:06:21', '2026-01-21 01:27:35', NULL, NULL, NULL, 1),
('93e34a1e8394e9aaa0c536bdf19c218d1b9e', '10455600-3ac7-4925-a145-75e0b016ea99', 'fc8b18485aae1b5d3890656c5ad46c39', '69f67f15-e752-11f0-ada2-b02628506e5c', 'c756c21b869ae63c37e015f8ac88cea2', 400.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-09 07:29:12', '2026-01-09 07:29:12', NULL, NULL, NULL, 1),
('97e965a3607b8151dd1ff0100857f38510ab', '757a826f-6167-4713-a9af-9ee4b1ac1308', 'fc8b18485aae1b5d3890656c5ad46c39', '69f6dc81-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 350.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-25 14:42:20', '2026-01-25 14:42:20', NULL, NULL, NULL, 1),
('9c88858e7179f922016a8f9b6c1d78c324e3', 'cbe0ee8a-ab68-4df5-9ac4-c6c20ce96274', 'fc8b18485aae1b5d3890656c5ad46c39', '69f6d5fb-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 350.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-10 21:53:14', '2026-01-10 21:53:14', NULL, NULL, NULL, 1),
('9d7644678ac34a20ab5c68ec781c2c2c', '9d37e3e0-1431-40ec-a4c8-558f6f74023e', 'fc8b18485aae1b5d3890656c5ad46c39', 'c86c82006d19f8cf1c5a7ba4dbf5e4653443', NULL, 400.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-16 05:48:37', '2026-01-16 05:48:37', '000711222876', NULL, NULL, 0),
('9e35f41ae080b0d70dff7bac2e764bfd3b7e', 'c077d4f8-e6d2-4020-aef3-f8edcfe62276', 'fc8b18485aae1b5d3890656c5ad46c39', '69f6be79-e752-11f0-ada2-b02628506e5c', NULL, 350.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-20 18:33:32', '2026-01-21 01:25:55', NULL, NULL, NULL, 1),
('a117682325d85565caa58d5ce017066c', 'ce81c5aa-e4c6-4943-bd39-c77fbabbe197', 'fc8b18485aae1b5d3890656c5ad46c39', '2306806ac608bafdfc50672144b14c11ee4f', NULL, 400.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-19 11:24:58', '2026-01-19 11:24:58', '000001933031', NULL, NULL, 0),
('a67f144bc3b2f27e1e8b788fa9004e86e303', '17085e8b-9a33-4937-a6ba-cf90fadcafa2', 'fc8b18485aae1b5d3890656c5ad46c39', '69f67ba4-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 350.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-15 10:37:58', '2026-01-15 10:37:58', NULL, NULL, NULL, 1),
('a717802d299f9054f13b8f6f0cede3ab', '874da850-9056-42ea-b62a-c24fe537b47e', 'fc8b18485aae1b5d3890656c5ad46c39', '5013b784781c70d78dc17e80ef099d33f34e', NULL, 400.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-19 11:25:10', '2026-01-19 11:25:10', '000003664407', NULL, NULL, 0),
('aca8a6f2e5ccd5a3586dd92ee9ba23d4', '4c21ffaa-f8e4-4784-9295-e6ae1a930318', 'fc8b18485aae1b5d3890656c5ad46c39', '18f9e21b335888b6e9db97243ac05466a29c', NULL, 400.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-18 12:55:51', '2026-01-18 12:55:51', '000000045537', NULL, NULL, 0),
('b0e5ca9bf68963188284ca01744890a620bf', '9aab02c4-30ed-4313-b0d5-f5d4100dfab0', 'fc8b18485aae1b5d3890656c5ad46c39', '69f6bfce-e752-11f0-ada2-b02628506e5c', NULL, 350.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-20 18:33:32', '2026-01-21 01:27:35', NULL, NULL, NULL, 1),
('bc6591ccca2a1f970df961894add51d2', 'fead74d9-41a1-4a7a-af67-77f06af0d80e', 'fc8b18485aae1b5d3890656c5ad46c39', '69f68111-e752-11f0-ada2-b02628506e5c', NULL, 400.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-19 11:24:43', '2026-01-21 01:25:55', '000000094986', NULL, NULL, 0),
('c35ae3ad7cab2ad27dfeda12d7e9aa08', 'b112429f-6d9e-43df-85b7-049474e338e9', 'fc8b18485aae1b5d3890656c5ad46c39', '98a34d2143378e70aa3a7cebe7a2da4c5453', NULL, 400.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-18 12:55:58', '2026-01-18 12:55:58', '000000622097', NULL, NULL, 0),
('c4be984488b63eda99a107f0f29134ff', 'a7f5c515-6e8a-4699-b4cf-575955b2639d', 'fc8b18485aae1b5d3890656c5ad46c39', 'd04009ac77230a920d84006a5e801065e1a9', NULL, 400.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-18 00:20:36', '2026-01-18 00:20:36', '000006643249', NULL, NULL, 0),
('ce7b9c72c3618ebccc5ad980adf4cdc4', 'd9d9f1c7-3319-4494-9798-d51d024a98fb', 'fc8b18485aae1b5d3890656c5ad46c39', '69f6aeb9-e752-11f0-ada2-b02628506e5c', NULL, 400.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-19 11:24:47', '2026-01-21 01:21:42', '000004982425', NULL, NULL, 0),
('d1f761abf4d4aadaef2390f4570278e668b0', '7851c584-d3ca-4d82-add8-c262b2abd227', 'fc8b18485aae1b5d3890656c5ad46c39', '69f6d558-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 350.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-10 21:53:40', '2026-01-10 21:53:40', NULL, NULL, NULL, 1),
('d76320e58d917b490b345f27fb1df3fa', 'dc8a2e3a-df77-465b-bb90-c4a38fd52952', 'fc8b18485aae1b5d3890656c5ad46c39', '69f6af79-e752-11f0-ada2-b02628506e5c', NULL, 400.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-19 08:42:43', '2026-01-21 01:25:55', '000886115935', NULL, NULL, 0),
('f3ca3a98426c644af1c5256e9536fcca359e', '98ca48cf-17e1-4c65-8631-7a06bee07e6b', 'fc8b18485aae1b5d3890656c5ad46c39', '69f6c1d5-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 350.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-09 07:29:40', '2026-01-09 07:29:40', NULL, NULL, NULL, 1),
('fcbca817c79d9b7c7c7a906e7e36c6b5e37c', 'e5a795e6-65f4-4f79-82ea-9bf749e81c91', 'fc8b18485aae1b5d3890656c5ad46c39', '69f67c62-e752-11f0-ada2-b02628506e5c', 'ec90a19c3209fc47d42524ef9b95ff44', 350.00, 'pending', 'cash', 0, NULL, NULL, NULL, '2026-01-15 13:43:09', '2026-01-15 13:43:09', NULL, NULL, NULL, 1);

--
-- Indexes for dumped tables
--

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
-- Begrensninger for dumpede tabeller
--

--
-- Begrensninger for tabell `events_checkin`
--
ALTER TABLE `events_checkin`
  ADD CONSTRAINT `events_checkin_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `events_eventdata` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `events_checkin_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `tenant_customers` (`id`),
  ADD CONSTRAINT `events_checkin_ibfk_3` FOREIGN KEY (`checked_in_by`) REFERENCES `tenant_admins` (`id`),
  ADD CONSTRAINT `fk_checkin_registration` FOREIGN KEY (`registration_id`) REFERENCES `event_registrations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_events_checkin_registration` FOREIGN KEY (`registration_id`) REFERENCES `event_registrations` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
