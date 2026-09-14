-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: salesmanno04.mysql.domeneshop.no
-- Generation Time: 14. Sep, 2026 20:54 PM
-- Tjener-versjon: 11.4.10-MariaDB-deb12-log
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
('246bcdb9b3404c4a2337e322b426b64e', '7efbddea-b507-11f0-936b-b02628506e5c', 'ae01f242e28fa5d3c916ac23238e9690', 'Bingo', 'Ny Bingo på Høgtun', '2026-03-25', '19:00:00', '2026-03-26', '21:00:00', '', 'Vågsgata 61 Høgtun', 'Fosnavåg', '6090', NULL, NULL, NULL, NULL, 0, 0.00, 0.00, 'https://external-content.duckduckgo.com/iu/?u=https%3A%2F%2Ftse2.mm.bing.net%2Fth%2Fid%2FOIP.Y-X130sw_xnC2p8MrQvOcQHaFk%3Fpid%3DApi&f=1&ipt=1d6b29240be816390494bf8764c0d87d2267aa44ae995f2ccb364417f32b6862&ipo=images', 0, '7f0d55c4-b507-11f0-936b-b02628506e5c', '2026-03-11 18:03:36', '2026-03-11 18:13:53', 'cash', 'both', 'signup', '[\"cash\",\"vipps\"]', 0, 'exgh3'),
('55473ffa9d1f35874de5acb7a811663f', '7efbddea-b507-11f0-936b-b02628506e5c', 'd03a0a29be280202890be14ca0bd1472', 'Fredagsdans', '?? Fredagsdans på Høgtun – 23.01.26 \n\nVelkomen til ein kjekk og sosial dansekveld på Høgtun!\n\n? Trekkspel kameratane leverer flott dansemusikk\n? Dørene åpner kl. 19 - gratis inngang\nSalg av enkel mat og snacks\n?? -Ta med det du ønsker\n-Ingen påmelding\n\nTa med venner, godt humør og danselyst – så lagar vi ein triveleg fredagskveld saman!', '2026-01-23', '19:00:00', NULL, NULL, '', 'Vågsgata 61', 'Fosnavåg', '6090', NULL, NULL, NULL, NULL, 0, 0.00, 0.00, 'https://8upload.com//image/935ede7e9fc39248/Skjermbilde_2026-01-10_kl._15.14.49.png', 0, '7f072d6a-b507-11f0-936b-b02628506e5c', '2026-01-10 15:13:29', '2026-01-24 09:58:43', '', 'both', 'none', NULL, 0, 'b28764'),
('58da89f2617ba42b7d739fa2b91b34a5', '7efbddea-b507-11f0-936b-b02628506e5c', '9aa2b0c9f31f68cd3b8129d501b31763', 'Februar bingo', 'Bingo på Høgtun. Vi åpner dørene klokken 19:00, for en kveld med flotte premier og spennende bingo!', '2026-02-25', '19:00:00', '2026-02-25', '21:00:00', 'Høgtun', 'Vågsgata 61', 'Fosnavåg', '6092', NULL, NULL, NULL, NULL, 0, 0.00, 0.00, 'https://external-content.duckduckgo.com/iu/?u=https%3A%2F%2Ftse2.mm.bing.net%2Fth%2Fid%2FOIP.Y-X130sw_xnC2p8MrQvOcQHaFk%3Fpid%3DApi&f=1&ipt=1d6b29240be816390494bf8764c0d87d2267aa44ae995f2ccb364417f32b6862&ipo=images', 0, '7f0d55c4-b507-11f0-936b-b02628506e5c', '2026-01-05 19:41:43', '2026-02-16 20:58:24', '', 'both', 'signup', NULL, 0, '81b247'),
('61ae0db46f57be604382f93166307d5c', '7efbddea-b507-11f0-936b-b02628506e5c', 'de05131b4ba15eedf17ae6168826d626', 'Swingkurs ', 'Dette ble ei minnerik helg!\nNo er det kun å øve, å øve. :-)  \n', '2026-03-06', '19:00:00', '2026-03-07', '16:00:00', 'Høgtun', 'Vågsgata 61', 'Fosnavåg', '6090', 34, NULL, NULL, NULL, 0, 50.00, 0.00, 'https://8upload.com/image/83b1346d8c7199ae/image.jpg.jpg', 1, '7f072d6a-b507-11f0-936b-b02628506e5c', '2026-01-24 09:24:12', '2026-05-17 21:55:41', '', 'both', 'signup', NULL, 0, 'f7qh0'),
('71620c349769113ccbe065572b75ca7a', '7efbddea-b507-11f0-936b-b02628506e5c', '5ea8da71e300952edfb34947c231be34', 'NY BINGO PÅ HØGTUN', 'ingen på melding,  Flotte premier. ', '2026-03-25', '19:00:00', '2026-03-26', '15:30:00', '', 'Vågsgata 61', 'Fosnavåg', '6090', NULL, NULL, NULL, NULL, 0, 0.00, 0.00, 'https://external-content.duckduckgo.com/iu/?u=https%3A%2F%2Ftse2.mm.bing.net%2Fth%2Fid%2FOIP.Y-X130sw_xnC2p8MrQvOcQHaFk%3Fpid%3DApi&f=1&ipt=1d6b29240be816390494bf8764c0d87d2267aa44ae995f2ccb364417f32b6862&ipo=images', 1, '7f0d55c4-b507-11f0-936b-b02628506e5c', '2026-03-12 14:30:09', '2026-03-12 14:30:09', 'cash', 'both', 'none', '[\"cash\",\"vipps\"]', 0, 'wo9rr'),
('75ed3c1fee1d71147a8e4cff732c7880', '7efbddea-b507-11f0-936b-b02628506e5c', '6d40546a1614036c986eeaca8912903e', 'Mars Bingo på Høgtun', 'No kjører vi på igjen med ny Bingo på Høgtun.', '2026-03-25', '19:00:00', '2026-03-26', '12:50:00', '', 'Vågsgata 61  Høgtun', 'Fosnavåg', '6090', NULL, NULL, NULL, NULL, 0, 0.00, 0.00, 'https://external-content.duckduckgo.com/iu/?u=https%3A%2F%2Ftse2.mm.bing.net%2Fth%2Fid%2FOIP.Y-X130sw_xnC2p8MrQvOcQHaFk%3Fpid%3DApi&f=1&ipt=1d6b29240be816390494bf8764c0d87d2267aa44ae995f2ccb364417f32b6862&ipo=images', 0, '7f0d55c4-b507-11f0-936b-b02628506e5c', '2026-03-11 17:48:04', '2026-03-11 18:06:11', 'cash', 'both', 'signup', '[\"cash\",\"vipps\"]', 0, 'z0hf5'),
('7b6e48ca5e24a281f209721a3d21c176', '7efbddea-b507-11f0-936b-b02628506e5c', 'ea3e74ba7faece03f9c7bdf64463327d', 'Romjulsdans', 'Dansetreff 28 Desember..\nMer info kommer ...', '2026-03-02', '11:36:00', '2026-08-15', '11:37:00', '', 'Kulturhuset', 'Fosnavåg', '6090', NULL, NULL, NULL, NULL, 0, 0.00, 0.00, 'https://external-content.duckduckgo.com/iu/?u=https%3A%2F%2Ftse3.mm.bing.net%2Fth%2Fid%2FOIP.7YdLDhnIfmWand1SUr0hBwHaHa%3Fpid%3DApi&f=1&ipt=2fd064ed7d258cb406f18f99b961d4d108da329351de46981ec23c2e8ddbbb56&ipo=images', 1, '7f0d55c4-b507-11f0-936b-b02628506e5c', '2026-03-02 11:38:14', '2026-03-02 11:51:41', 'cash', 'both', 'none', '[\"cash\",\"vipps\"]', 0, '9sp8a'),
('868511f10f7a2501da5f7d12ed32d92e', '7efbddea-b507-11f0-936b-b02628506e5c', '2c4d2bb3b6fd723283e365f5bd92425a', 'Novemberdansen', 'Sissel Lunde Sundby spiller opp til dans den 7 november, vi inviterer inn alle til en flott dansekveld. Ta med partnere og bekjente', '2026-11-07', '20:00:00', '2026-11-08', '01:00:00', 'Herøy Kulturhus', 'Lisjebøvegen 10A', 'Fosnavåg', '6090', NULL, NULL, NULL, NULL, 0, 400.00, 0.00, 'https://i.8upload.com/image/5d69daf4f708f31a/799487160-10162522548937126-4024492985214528845-n.jpg', 1, '7f0d55c4-b507-11f0-936b-b02628506e5c', '2026-09-09 12:42:50', '2026-09-09 21:36:29', 'cash', 'both', 'signup', '[\"cash\"]', 1, 's46q9'),
('94856373198f53e10d050c7864eaba67', '7efbddea-b507-11f0-936b-b02628506e5c', '9e2ed92ecf5779938510af0b26cc30cb', 'Danse, -øvingskveld med fokus på swingdans.', 'Det blir igjen ny dansekveld torsdag 16. April kl 19.00\nArr. er åpent for alle og ,med fri inngang.\nHåper mange tar turen.. :-)', '2026-04-16', '19:00:00', '2026-04-17', '21:00:00', '', 'Vågsgata 61 Høgtun', 'Fosnavåg', '6090', NULL, NULL, NULL, NULL, 0, 0.00, 0.00, 'https://external-content.duckduckgo.com/iu/?u=https%3A%2F%2Ftse4.mm.bing.net%2Fth%2Fid%2FOIP.2TAkkqmvocm22A98tRXlHQHaE8%3Fpid%3DApi&f=1&ipt=2d315d8d2742a4f5c812322972ef28f5bd23019cf0e1ee3e3733a25dcfb80215&ipo=images', 1, 'f996adb8d578c9f15859cc9ffb78b023792b', '2026-04-13 12:36:50', '2026-04-13 12:40:45', 'cash', 'both', 'none', '[\"cash\",\"vipps\"]', 0, 'ko3uf'),
('9affd35b4ba567bea6b7f1cc033a5788', '7efbddea-b507-11f0-936b-b02628506e5c', 'eb8d10e228a5fd9a60eca4c8fb806bc5', 'Bingo', 'Bingo på Høgtun Onsdag 7.10 kl 19.00', '2026-07-10', '19:00:00', NULL, NULL, 'Høgtun', '', 'Fosnavåg', '6090', NULL, NULL, NULL, NULL, 0, 0.00, 0.00, 'https://external-content.duckduckgo.com/iu/?u=https%3A%2F%2Ftse3.mm.bing.net%2Fth%2Fid%2FOIP.oPaV3-xIFaNjYdXhqlsOmwHaE8%3Fr%3D0%26pid%3DApi&f=1&ipt=9b0555436db20eb83c573e5749a6e2d69cf4aa5a83e56a72748cf3aaf4513e15&ipo=images', 1, '7f0d55c4-b507-11f0-936b-b02628506e5c', '2026-09-14 10:13:01', '2026-09-14 10:13:01', 'cash', 'both', 'none', '[\"cash\",\"vipps\"]', 0, '8mv4y'),
('b306ef9c815bd0528b29839777f005e9', '7efbddea-b507-11f0-936b-b02628506e5c', '1b0b34e5c81c63511aea8a6e1a85662e', 'Mars Bingo', 'Ny spennende bingo kveld, med mange flotte premier, Ingen påmelding, \nher er det \"første mann til mølla\" som gjelder.. :-) ', '2026-03-25', '19:00:00', '2026-03-26', '09:00:00', '', 'Vågsgata', 'Fosnavåg', '61', NULL, NULL, NULL, NULL, 0, 0.00, 0.00, 'https://external-content.duckduckgo.com/iu/?u=https%3A%2F%2Ftse2.mm.bing.net%2Fth%2Fid%2FOIP.Y-X130sw_xnC2p8MrQvOcQHaFk%3Fpid%3DApi&f=1&ipt=1d6b29240be816390494bf8764c0d87d2267aa44ae995f2ccb364417f32b6862&ipo=images', 0, '7f0d55c4-b507-11f0-936b-b02628506e5c', '2026-02-16 20:57:27', '2026-03-12 14:30:34', '', 'both', 'signup', NULL, 0, 'tyj3s'),
('fc3a942857d96a5645560c86888d056d', '7efbddea-b507-11f0-936b-b02628506e5c', '772a5dbcb5c3dca32a9873f94b9c2487', 'TESTARRANGEMENT', 'TEST', '2026-04-03', '09:30:00', '2026-04-09', '09:30:00', 'TESTSTED', 'Anholen 13b', 'Ulsteinvik', '6065', 300, NULL, NULL, NULL, 0, 1.00, 0.00, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcR5nsH2XsYOzsKwv_t4vEiA9OBqrKnosviItibi29VfGe0jHbtR1RCzvFpT&s=10', 0, '7f072d6a-b507-11f0-936b-b02628506e5c', '2026-02-27 09:26:48', '2026-02-27 22:02:16', 'vipps', 'both', 'signup', '[\"cash\",\"vipps\"]', 1, 'yeqoz'),
('fc8b18485aae1b5d3890656c5ad46c39', '7efbddea-b507-11f0-936b-b02628506e5c', '12a8901486c06b666ba289d8206da1e1', 'Dansetreff med Steinar Engelbrektson ', 'Dansetreff på Kulturhuset ved badeland i Fosnavåg.\nDette blir en kjempefin dansekveld... :-)\n', '2026-02-07', '19:30:00', '2026-02-08', '01:00:00', '', 'Lisjebøvegen 10a', 'Fosnavåg', '6092', 300, NULL, NULL, NULL, 0, 400.00, 0.00, 'https://external-content.duckduckgo.com/iu/?u=https%3A%2F%2Ftse1.mm.bing.net%2Fth%2Fid%2FOIP.4r9eMreo4EJBPfwNJ1tRHAHaC2%3Fpid%3DApi&f=1&ipt=d5be2278bb03a1b34e6dff24b61284e98f435cdfb41cec1d3f1cee3c091e1835&ipo=images', 1, '7f0d55c4-b507-11f0-936b-b02628506e5c', '2026-01-08 22:02:09', '2026-05-16 23:58:55', 'cash', 'both', 'binding', '[\'vipps\']', 1, 'a7504b'),
('fe44f5ed5c09b739fefd15ed58e253f7', '7efbddea-b507-11f0-936b-b02628506e5c', 'b110764b7f142ac966e317decd23da7c', 'Senior Dansekvelder på Høgtun', 'Torsdager, fra Kl. 19-22.\nDato: 12.02 - 26-02 - 12.03 - 26-03 - 09.04 - 23.04', '2026-02-02', '19:00:00', '2026-04-28', NULL, '', 'Høgtun, Vågsgt. 61', 'Fosnavåg', '6095', 0, NULL, 0, NULL, 0, 0.00, 0.00, NULL, 0, '7f0d55c4-b507-11f0-936b-b02628506e5c', '2026-02-08 11:53:13', '2026-02-08 11:56:47', 'cash', 'both', 'signup', '[\"cash\",\"vipps\"]', 0, '248sx');

--
-- Indexes for dumped tables
--

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
-- Begrensninger for dumpede tabeller
--

--
-- Begrensninger for tabell `events_eventdata`
--
ALTER TABLE `events_eventdata`
  ADD CONSTRAINT `events_eventdata_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `tenant_companies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `events_eventdata_ibfk_2` FOREIGN KEY (`output_product_id`) REFERENCES `output_products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
