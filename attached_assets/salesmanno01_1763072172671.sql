-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: salesmanno01.mysql.domeneshop.no
-- Generation Time: 13. Nov, 2025 22:14 PM
-- Tjener-versjon: 11.4.7-MariaDB-ubu2004-log
-- PHP Version: 8.2.8

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `salesmanno01`
--

-- --------------------------------------------------------

--
-- Tabellstruktur for tabell `members`
--

CREATE TABLE `members` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `join_date` date NOT NULL DEFAULT curdate(),
  `membership_fee` int(11) DEFAULT 500,
  `membership_expires_at` date DEFAULT NULL,
  `memberstate` tinyint(4) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `membership_type` varchar(50) DEFAULT 'regular',
  `member_since` date DEFAULT curdate(),
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dataark for tabell `members`
--

INSERT INTO `members` (`id`, `name`, `email`, `phone`, `address`, `birth_date`, `join_date`, `membership_fee`, `membership_expires_at`, `memberstate`, `created_at`, `updated_at`, `membership_type`, `member_since`, `is_active`) VALUES
(120, 'Egil Rasmineson Frantsen', 'e-fran@online.no', '90689856', 'Gamlemyklebustveg 9 6092 Fosnavåg', NULL, '2025-07-21', 500, '2026-07-21', 1, '2025-07-21 21:23:20', '2025-09-30 22:32:22', 'regular', '2025-07-21', 1),
(121, 'Britt Inger Gusdal Frantsen', 'brittingerbig@gmail.com', '93226717', 'GamleMyklebustveg 9 6092 Fosnavåg', NULL, '2025-07-21', 500, '2026-07-21', 1, '2025-07-21 21:23:21', '2025-09-30 22:32:22', 'regular', '2025-07-21', 1),
(123, 'Atle Kvalsvik', 'atlekarl@tussa.com', '95918925', 'Mulevikvegen,42 6098 Nerlandsøy', '1956-04-08', '2025-07-21', 500, '2026-07-21', 1, '2025-07-21 21:23:21', '2025-10-14 09:10:11', 'regular', '2025-07-21', 1),
(124, 'Bente Kvalsvik Notøy', 'bente02@live.no', '48158704', 'Breidanesvegen 8, 6092 Fosnavåg', NULL, '2025-07-21', 500, '2026-07-21', 1, '2025-07-21 21:23:22', '2025-10-14 09:12:13', 'regular', '2025-07-21', 1),
(125, 'Marion Nilsen', 'marion.nilsen@outlook.com', '97988677', 'Mulevikvegen,42 6098 Nerlandsøy', '1955-07-17', '2025-07-21', 500, '2026-07-21', 1, '2025-07-21 21:23:22', '2025-10-26 10:13:35', 'regular', '2025-07-21', 1),
(126, 'Kristin Leine', 'kristinleine@hotmail.no', '99649880', 'Jordmor Helgas veg  14, 6090 Fosnavåg', NULL, '2025-07-21', 500, '2026-07-21', 1, '2025-07-21 21:23:22', '2025-10-14 20:30:27', 'regular', '2025-07-21', 1),
(127, 'Marita Higraf Remøy', 'maritahr2@gmail.com', '90981991', 'Nedre plassgjerde 6098 Nerlandsøy', '1966-04-14', '2025-07-21', 500, '2026-07-21', 1, '2025-07-21 21:23:23', '2025-10-14 20:33:23', 'regular', '2025-07-21', 1),
(131, 'Oddveig Arnesen', NULL, '45235239', 'Nordgardsvegen 4 6090 Fosnavåg', NULL, '2025-07-23', 500, '2026-07-23', 1, '2025-07-23 18:03:14', '2025-09-30 22:32:22', 'regular', '2025-07-23', 1),
(132, 'Asbjørn Bendal', 'abendal@online.no', '95766662', 'Kviehaugen 8 B 6091 Fosnavåg', NULL, '2025-07-23', 500, '2026-07-23', 1, '2025-07-23 18:03:15', '2025-10-14 09:07:21', 'regular', '2025-07-23', 1),
(133, 'Jan Bugge', NULL, '95125587', 'Nedre Hjelmeseth 20 6091 Fosnavåg', NULL, '2025-07-23', 500, '2026-07-23', 1, '2025-07-23 18:03:15', '2025-09-30 22:32:22', 'regular', '2025-07-23', 1),
(134, 'Jan Bø', NULL, '48051150', 'Sandevegen 6 6095 Bølandet', NULL, '2025-07-23', 500, '2026-07-23', 1, '2025-07-23 18:03:15', '2025-11-05 22:56:53', 'regular', '2025-07-23', 1),
(135, 'Reidun Baade', 'reidun23@hotmail.no', '97077208', 'Ospenesvegen 7 6092 Fosnavåg', '1958-03-23', '2025-07-23', 500, '2026-07-23', 1, '2025-07-23 18:03:16', '2025-09-30 22:32:22', 'regular', '2025-07-23', 1),
(136, 'Annlaug Eikrem', 'annlaugeikrem@hotmail.com', '46430527', 'Voldsundvegen 48, 6094 Leinøy', NULL, '2025-07-23', 500, '2026-07-23', 1, '2025-07-23 18:03:16', '2025-10-14 09:06:10', 'regular', '2025-07-23', 1),
(137, 'Myrthel Emblem', 'myrtelemblem@gmail.com', '91849589', 'Postboks 25 6099 Fosnavåg', NULL, '2025-07-23', 500, '2026-07-23', 1, '2025-07-23 18:03:16', '2025-10-14 20:34:08', 'regular', '2025-07-23', 1),
(138, 'Arild Flusund', 'arild.flusund@gmail.com', '91637514', 'Sandevegen 8 9095 Bølandet', '1958-10-12', '2025-07-23', 500, '2026-07-23', 1, '2025-07-23 18:03:16', '2025-09-30 22:32:22', 'regular', '2025-07-23', 1),
(139, 'Arnhild Flusund', NULL, '41235051', 'Sandevegen 8 6095 Bølandet', '1962-04-29', '2025-07-23', 500, '2026-07-23', 1, '2025-07-23 18:03:17', '2025-10-14 09:07:07', 'regular', '2025-07-23', 1),
(140, 'Idar Frantzen', 'frantsenidar@gmail.com', '40613610', 'Ospenesveien 21 6092 Fosnavåg', NULL, '2025-07-23', 500, '2026-07-23', 1, '2025-07-23 18:03:17', '2025-11-13 20:17:48', 'regular', '2025-07-23', 1),
(141, 'Tone Foss Frantzen', 'tone.frantzen@hotmail.com', '95338070', 'Holmsildgata 16 6090 Fosnavåg', NULL, '2025-07-23', 500, '2026-07-23', 1, '2025-07-23 18:03:18', '2025-10-14 20:39:48', 'regular', '2025-07-23', 1),
(142, 'Mathias Haugen', 'stottemur@hotmail.com', '45500147', 'Myklebustvegen 115 6488 Myklebust', NULL, '2025-07-23', 500, '2026-07-23', 1, '2025-07-23 18:03:18', '2025-10-14 20:33:34', 'regular', '2025-07-23', 1),
(143, 'Robert Bjarne Helde', 'robert.heldeb@gmail.com', '97624748', 'Demingane 27 6092 Fosnavåg', NULL, '2025-07-23', 500, '2026-07-23', 1, '2025-07-23 18:03:18', '2025-10-14 20:38:03', 'regular', '2025-07-23', 1),
(144, 'Fritjof Husøy', NULL, '95223457', 'Torvik 6095 Bølandet', NULL, '2025-07-23', 500, '2026-07-23', 1, '2025-07-23 18:03:19', '2025-09-30 22:32:22', 'regular', '2025-07-23', 1),
(145, 'Erik Iversen', 'erik.iversen@kcadeutag.com', '90838258', 'Kopperstadvegen 140 6098 Nerlandsøy', NULL, '2025-07-23', 500, '2026-07-23', 1, '2025-07-23 18:03:19', '2025-10-14 09:20:13', 'regular', '2025-07-23', 1),
(146, 'Margunn Kalland', NULL, '91329484', 'Massavegen 14, 6096 Runde', NULL, '2025-07-23', 500, '2026-07-23', 1, '2025-07-23 18:03:19', '2025-09-30 22:32:22', 'regular', '2025-07-23', 1),
(147, 'Marit Knutsen', NULL, '91894371', 'Borgarhaugvegen 4 6091 Fosnavåg', NULL, '2025-07-23', 500, '2026-07-23', 1, '2025-07-23 18:03:19', '2025-09-30 22:32:22', 'regular', '2025-07-23', 1),
(149, 'Perly Christin Vikebak Kopperstad', 'perly.kopperstad@outlook.com', '90117868', '6099 Fosnavåg', NULL, '2025-07-23', 500, '2026-07-23', 1, '2025-07-23 18:03:20', '2025-10-14 20:36:52', 'regular', '2025-07-23', 1),
(150, 'Idar Ove Kragset', 'idarovekr@hotmail.com', '90112149', 'Breidanesvegen 8, 6092 Fosnavåg', NULL, '2025-07-23', 500, '2026-07-23', 1, '2025-07-23 18:03:20', '2025-10-14 20:24:33', 'regular', '2025-07-23', 1),
(151, 'Myrthel Kvalsund', NULL, '90588968', 'Teigelia 38 6098 Nerlandsøy', NULL, '2025-07-23', 500, '2026-07-23', 1, '2025-07-23 18:03:20', '2025-09-30 22:32:22', 'regular', '2025-07-23', 1),
(152, 'Per Ove Kvalsund', NULL, '90688968', 'Teigelia 38 6098 Nerlandsøy', NULL, '2025-07-23', 500, '2026-07-23', 1, '2025-07-23 18:03:21', '2025-09-30 22:32:22', 'regular', '2025-07-23', 1),
(153, 'Geir Vangen Kvalsund', 'geirkv@gmail.com', '48248499', 'Øvre Plassegjerde 5 6098 Nerlandsøy', NULL, '2025-07-23', 500, '2026-07-23', 1, '2025-07-23 18:03:21', '2025-09-30 22:32:22', 'regular', '2025-07-23', 1),
(154, 'aksel.kvalsvik@tussa.com', 'aksel.kvalsvik@tussa.com', '97553520', 'Øvre Plassgjerdet 61 6098 Nerlandsøy', NULL, '2025-07-23', 500, '2026-07-23', 1, '2025-07-23 18:03:21', '2025-10-14 09:03:34', 'regular', '2025-07-23', 1),
(155, 'Evy Helen Ryssevik Kvalsvik', 'evy.kvals@gmail.com', '95995513', 'Bendalsvegen 8C 6091 Fosnavåg', NULL, '2025-07-23', 500, '2026-07-23', 1, '2025-07-23 18:03:22', '2025-10-14 09:21:04', 'regular', '2025-07-23', 1),
(156, 'Åse Kvalsvik', NULL, '92451835', 'Øvre Plassegjerde 5 6098 Nerlandsøy', NULL, '2025-07-23', 500, '2026-07-23', 1, '2025-07-23 18:03:22', '2025-09-30 22:32:22', 'regular', '2025-07-23', 1),
(157, 'Mariann Leinebø', NULL, '48294481', 'Støylavegne 13 6094 Leinøy', NULL, '2025-07-23', 500, '2026-07-23', 1, '2025-07-23 18:03:22', '2025-09-30 22:32:22', 'regular', '2025-07-23', 1),
(158, 'Gunnar Leinebø', NULL, '91561632', 'Støylavegen 13 6094 Leinøy', NULL, '2025-07-23', 500, '2026-07-23', 1, '2025-07-23 18:03:23', '2025-09-30 22:32:22', 'regular', '2025-07-23', 1),
(159, 'Marit Jordan Lyster', NULL, '95156176', 'Leikongvegen 422 6080 Gurskøy', NULL, '2025-07-23', 500, '2026-07-23', 1, '2025-07-23 18:03:23', '2025-09-30 22:32:22', 'regular', '2025-07-23', 1),
(160, 'Mariann Løvold', 'mariann.lovold@hotmail.com', '98482604', 'Verket 14 6091 Fosnavåg', NULL, '2025-07-23', 500, '2026-07-23', 1, '2025-07-23 18:03:23', '2025-10-14 20:32:36', 'regular', '2025-07-23', 1),
(161, 'Tore Løvøy', 'tore.lovoy@gmail.com', '41307657', 'Sandvikvegen 71 6070 Tjørvåg', NULL, '2025-07-23', 500, '2026-07-23', 1, '2025-07-23 18:03:24', '2025-10-14 20:39:59', 'regular', '2025-07-23', 1),
(162, 'Irene Petra Madsen', NULL, '91737567', 'Geila 5, 6098 Nerlandsøy', NULL, '2025-07-23', 500, '2026-07-23', 1, '2025-07-23 18:03:24', '2025-09-30 22:32:22', 'regular', '2025-07-23', 1),
(163, 'Johan Moltu', 'johan.moltu@gmail.com', '91568576', 'Røyravegen 10 6095 Bølandet', NULL, '2025-07-23', 500, '2026-07-23', 1, '2025-07-23 18:03:24', '2025-10-14 20:28:29', 'regular', '2025-07-23', 1),
(164, 'Sølvi Moltu', 'solvi.moltu@tussa.com', '93641550', 'Leinøy 6094 Leinøy', NULL, '2025-07-23', 500, '2026-07-23', 1, '2025-07-23 18:03:24', '2025-11-02 22:22:06', 'regular', '2025-07-23', 1),
(165, 'Anne-Jorunn Moltumyr', NULL, '90940208', 'Remøy 6094 Leinøy', NULL, '2025-07-23', 500, '2026-07-23', 1, '2025-07-23 18:03:25', '2025-09-30 22:32:22', 'regular', '2025-07-23', 1),
(166, 'Henning Moltumyr', 'henningmoltumyr@tussa.com', '94143636', 'Remøy 6094 Leinøy', NULL, '2025-07-23', 500, '2026-07-23', 1, '2025-07-23 18:03:25', '2025-10-14 09:27:01', 'regular', '2025-07-23', 1),
(167, 'Oddvar Myklebust', NULL, '93600832', 'Eggesbøvegen 6 A 6092 Fosnavåg', NULL, '2025-07-23', 500, '2026-07-23', 1, '2025-07-23 18:03:25', '2025-09-30 22:32:22', 'regular', '2025-07-23', 1),
(168, 'Jan Arild Myklebusthaug', 'jamyklebusthaug@gmail.com', '47830680', 'Brunholmgata 22 6004 Ålesund', NULL, '2025-07-23', 500, '2026-07-23', 1, '2025-07-23 18:03:25', '2025-10-14 20:27:27', 'regular', '2025-07-23', 1),
(170, 'Geir Per Nerland', 'geir.per.nerland@gmail.com', '99308210', 'Landsvegen 12 6098 Nerlandsøy', NULL, '2025-07-23', 500, '2026-07-23', 1, '2025-07-23 18:03:26', '2025-10-14 09:21:19', 'regular', '2025-07-23', 1),
(171, 'Jeanette Nerland', 'jeanette@hotmail.com', '97191203', 'Landsvegen 12 6098 Nerlandsøy', NULL, '2025-07-23', 500, '2026-07-23', 1, '2025-07-23 18:03:26', '2025-10-14 20:28:04', 'regular', '2025-07-23', 1),
(172, 'Oddbjørn Nerland', 'oddbjorn@nerland.io', '47238763', 'Svingen 8 6092 Fosnavåg', NULL, '2025-07-23', 500, '2026-07-23', 1, '2025-07-23 18:03:26', '2025-10-14 20:34:34', 'regular', '2025-07-23', 1),
(173, 'Alf Nerland', 'alfnerland@tussa.com', '41660225', 'Holmsildgata 16 6090 Fosnavåg', NULL, '2025-07-23', 500, '2026-07-23', 1, '2025-07-23 18:03:27', '2025-09-30 22:32:22', 'regular', '2025-07-23', 1),
(174, 'Marit Nerlid', NULL, '97643583', 'Kalvegjerde 2 6076 Moltustranda', NULL, '2025-07-23', 500, '2026-07-23', 1, '2025-07-23 18:03:27', '2025-09-30 22:32:22', 'regular', '2025-07-23', 1),
(175, 'Anita Nilsen', 'anita-nilsen@live.no', '97646387', '6092 Fosnavåg', NULL, '2025-07-23', 500, '2026-07-23', 1, '2025-07-23 18:03:27', '2025-09-30 22:32:22', 'regular', '2025-07-23', 1),
(176, 'Gerd Jorunn Nilsen', NULL, '41302631', 'Kyrkjevegen 93 6230 Sykkulven', NULL, '2025-07-23', 500, '2026-07-23', 1, '2025-07-23 18:03:27', '2025-11-05 22:56:27', 'regular', '2025-07-23', 1),
(177, 'Gunn Nilsen', 'gunn-n@hotmail.no', '93412336', 'Bendalsvegen 15B 6091 Fosnavåg', NULL, '2025-07-23', 500, '2026-07-23', 1, '2025-07-23 18:03:28', '2025-09-30 22:32:22', 'regular', '2025-07-23', 1),
(178, 'Bård Nordnes', NULL, '41734804', 'Voldsundvegen 32 6094 Leinøy', NULL, '2025-07-23', 500, '2026-07-23', 1, '2025-07-23 18:03:28', '2025-09-30 22:32:22', 'regular', '2025-07-23', 1),
(179, 'Ingunn Nordnes', 'ingnordnes@hotmail.com', '95752952', 'Voldsundvegen 32 6094 Leinøy', NULL, '2025-07-23', 500, '2026-07-23', 1, '2025-07-23 18:03:28', '2025-10-14 20:26:58', 'regular', '2025-07-23', 1),
(180, 'Alis Notøy', NULL, '90857103', 'Stornesvegen 8 6070 Tjørvåg', NULL, '2025-07-23', 500, '2026-07-23', 1, '2025-07-23 18:03:29', '2025-11-05 22:56:09', 'regular', '2025-07-23', 1),
(181, 'Mai-Brit Nygarden', NULL, '95000339', 'K.L.Vegen 9, 6060 Hareid', NULL, '2025-07-23', 500, '2026-07-23', 1, '2025-07-23 18:03:29', '2025-09-30 22:32:22', 'regular', '2025-07-23', 1),
(182, 'Sigmund Nymark', 'sigmund.nymark@gmail.com', '41932875', 'Leione 3A 6094 Leinøy', NULL, '2025-07-23', 500, '2026-07-23', 1, '2025-07-23 18:03:30', '2025-10-14 20:38:54', 'regular', '2025-07-23', 1),
(183, 'Eldar Hoan Ose', NULL, '41506612', 'Hoslekervegen 120 6065 Ulsteinvik', NULL, '2025-07-23', 500, '2026-07-23', 1, '2025-07-23 18:03:30', '2025-09-30 22:32:22', 'regular', '2025-07-23', 1),
(184, 'Endre Johan Ose', 'endrejohan@hotmail.com', '91101955', 'Holkestadvegen 6 6091 Fosnavåg', NULL, '2025-07-23', 500, '2026-07-23', 1, '2025-07-23 18:03:30', '2025-10-14 09:19:49', 'regular', '2025-07-23', 1),
(185, 'Eva Lena Ose', 'trolle-40@hotmail.com', '48172229', 'Hoslekervegen 120 6065 Ulsteinvik', NULL, '2025-07-23', 500, '2026-07-23', 1, '2025-07-23 18:03:30', '2025-10-14 09:20:49', 'regular', '2025-07-23', 1),
(186, 'Bjørn Prytz', 'bjornprytz@gmail.com', '47393108', 'Buråsa 1 6090 Fosnavåg', NULL, '2025-07-23', 500, '2026-07-23', 1, '2025-07-23 18:03:31', '2025-09-30 22:32:22', 'regular', '2025-07-23', 1),
(187, 'Kristin Rabben', 'kri-rabb@online.no', '90069947', 'Brunholmgata 22, 6004 Ålesund', NULL, '2025-07-23', 500, '2026-07-23', 1, '2025-07-23 18:03:31', '2025-10-14 20:30:41', 'regular', '2025-07-23', 1),
(188, 'Kirsten Reite', 'k.reite@hotmail.com', '95791864', 'Kvianesvegen 49, 6094 Leinøy', NULL, '2025-07-23', 500, '2026-07-23', 1, '2025-07-23 18:03:31', '2025-10-14 20:30:02', 'regular', '2025-07-23', 1),
(189, 'Ronald Reite', 'rona-re@tussa.com', '41438157', 'Kvianesvegen 49, 6094 Leinøy', NULL, '2025-07-23', 500, '2026-07-23', 1, '2025-07-23 18:03:31', '2025-10-14 20:38:17', 'regular', '2025-07-23', 1),
(190, 'Aud Remøy', 'audremoy@gmail.com', '98684879', 'Myrane 20 6094 Leinøy', '1961-06-04', '2025-07-23', 500, '2026-07-23', 1, '2025-07-23 18:03:32', '2025-09-30 22:32:22', 'regular', '2025-07-23', 1),
(191, 'Kurt Remøy', 'kurt.remoy@tussa.com', '95839656', 'Myrane 20 6094 Leinøy', '1957-11-15', '2025-07-23', 500, '2026-07-23', 1, '2025-07-23 18:03:32', '2025-10-14 20:31:08', 'regular', '2025-07-23', 1),
(192, 'Paul Gustav Remøy', 'gustav.jarle@example.com', '87654321', 'Remøya 22 6094 Leinøy', NULL, '2025-07-23', 500, '2026-07-23', 1, '2025-07-23 18:03:32', '2025-10-14 20:36:05', 'regular', '2025-07-23', 1),
(193, 'Sveinung Remøy', NULL, '48195217', 'Nedre Plassgjerde 7 6098 Nerlandsøy', NULL, '2025-07-23', 500, '2026-07-23', 1, '2025-07-23 18:03:33', '2025-09-30 22:32:22', 'regular', '2025-07-23', 1),
(194, 'Ellinor Rinde', 'hallgeir66@hotmail.com', '41257965', 'Ædane 9 6080 Gurskøy', NULL, '2025-07-23', 500, '2026-07-23', 1, '2025-07-23 18:03:33', '2025-09-30 22:32:22', 'regular', '2025-07-23', 1),
(195, 'Liv Runde', NULL, '92260396', 'Rundevegen 10 6096 Runde', NULL, '2025-07-23', 500, '2026-07-23', 1, '2025-07-23 18:03:33', '2025-09-30 22:32:22', 'regular', '2025-07-23', 1),
(196, 'Frode Ryssevik', NULL, '97521996', 'Bendalsvegen 6C 6091 Fosnavåg', NULL, '2025-07-23', 500, '2026-07-23', 1, '2025-07-23 18:03:34', '2025-09-30 22:32:22', 'regular', '2025-07-23', 1),
(197, 'Palmar Rønnestad', 'palmar.ronnestad@gmail.com', '99011940', 'Nedre Røyra 1', '1954-01-18', '2025-07-23', 500, '2026-07-23', 1, '2025-07-23 18:03:34', '2025-10-15 14:23:51', 'regular', '2025-07-23', 1),
(198, 'Sølvi Røren', NULL, '97966560', 'Vågsgata 45, 6090 Fosnavåg', NULL, '2025-07-23', 500, '2026-07-23', 1, '2025-07-23 18:03:34', '2025-09-30 22:32:22', 'regular', '2025-07-23', 1),
(199, 'Ann Sandanger', 'annhsandanger@gmail.com', '95789630', 'Nedre Hjelmeseth 20 6091 Fosnavåg', NULL, '0000-00-00', 500, NULL, 1, '2025-07-23 18:03:34', '2025-09-24 19:44:03', 'regular', '2025-07-23', 1),
(200, 'Hans Sande', 'hanssande@tussa.com', '92459392', 'Sandevegen  47 6095 Bølandet', NULL, '2025-07-23', 500, '2026-07-23', 1, '2025-07-23 18:03:35', '2025-10-14 09:26:51', 'regular', '2025-07-23', 1),
(201, 'Petra Sandvik', NULL, '45035591', 'Tjørvågvegen 2 6070 Tjørvåg', NULL, '2025-07-23', 500, '2026-07-23', 1, '2025-07-23 18:03:35', '2025-09-30 22:32:22', 'regular', '2025-07-23', 1),
(202, 'Roar Sandvik', 'solvi.moltu@tussa.com', '90116870', 'Nautøya 90 6070 Tjørvåg', NULL, '2025-07-23', 500, '2026-07-23', 1, '2025-07-23 18:03:35', '2025-11-02 22:24:23', 'regular', '2025-07-23', 1),
(203, 'Oddvar Skogen', NULL, '47857099', 'Vågsgata 45, 6090 Fosnavåg', NULL, '2025-07-23', 500, '2026-07-23', 1, '2025-07-23 18:03:35', '2025-09-30 22:32:22', 'regular', '2025-07-23', 1),
(204, 'Roar Skoglund', 'rokriskog@gmail.com', '91369350', 'Svingen 8 6092 Fosnavåg', NULL, '2025-07-23', 500, '2026-07-23', 1, '2025-07-23 18:03:36', '2025-10-14 20:37:49', 'regular', '2025-07-23', 1),
(205, 'Arild Slettestøl', 'arild_j@live.com', '91688928', 'Knotten 130 6082 Gursken', NULL, '2025-07-23', 500, '2026-07-23', 1, '2025-07-23 18:03:36', '2025-10-14 09:06:36', 'regular', '2025-07-23', 1),
(206, 'Aslak Solbjørg', 'asolborg@aolnorge.no', '46891090', 'Nautøya 125 6070 Tjørvåg', NULL, '2025-07-23', 500, '2026-07-23', 1, '2025-07-23 18:03:36', '2025-10-14 09:07:37', 'regular', '2025-07-23', 1),
(207, 'Oddveig Stanghelle', 'oddveig_sta@hotmail.com', '99649879', 'Leinevegen 272 6094 Leinøy', NULL, '2025-07-23', 500, '2026-07-23', 1, '2025-07-23 18:03:37', '2025-09-30 22:32:22', 'regular', '2025-07-23', 1),
(208, 'Liv Bessie Stave', 'livstave@online.no', '95768617', 'Osebergvegen 5 6065 Ulsteinvik', NULL, '2025-07-23', 500, '2026-07-23', 1, '2025-07-23 18:03:37', '2025-10-14 20:32:20', 'regular', '2025-07-23', 1),
(209, 'Per Egil Sørheller', 'peregilsorheller@gmail.com', '45906393', 'Brauta 3 6037 Eidsnes', NULL, '2025-07-23', 500, '2026-07-23', 1, '2025-07-23 18:03:37', '2025-10-14 20:36:25', 'regular', '2025-07-23', 1),
(210, 'Kirsten Teige', NULL, '40086106', 'Teigeveien 47, 6098 Nerlandsøy', NULL, '2025-07-23', 500, '2026-07-23', 1, '2025-07-23 18:03:38', '2025-09-30 22:32:22', 'regular', '2025-07-23', 1),
(211, 'Oluf Teige', 'oluf.teige@tussa.com', '97525423', 'Teigeveien 47, 6098 Nerlandsøy', '2025-09-15', '2025-07-23', 500, '2026-07-23', 1, '2025-07-23 18:03:38', '2025-10-14 20:35:26', 'regular', '2025-07-23', 1),
(212, 'Anne-Johanne Tellefsen', 'anne.johanne.tellefsen@gmail.com', '90846683', 'Teigelia 6 6098 Nerlandsøy', NULL, '2025-07-23', 500, '2026-07-23', 1, '2025-07-23 18:03:38', '2025-09-30 22:32:22', 'regular', '2025-07-23', 1),
(213, 'Solfrid Thorset', NULL, '95766727', 'Leikongbakken 4, 6080 Gurskøy', NULL, '2025-07-23', 500, '2026-07-23', 1, '2025-07-23 18:03:39', '2025-09-30 22:32:22', 'regular', '2025-07-23', 1),
(214, 'Kjellmar Vike', 'kjellmar50@gmail.com', '90863096', 'Grønhaugvegen 34, 6092 Fosnavåg', '2025-08-25', '2025-07-23', 500, '2026-07-23', 1, '2025-07-23 18:03:39', '2025-10-14 20:30:16', 'regular', '2025-07-23', 1),
(215, 'Laila Vike', 'vennen53@hotmail.com', '99162331', 'Grønhaugvegen 34, 6092 Fosnavåg', '1963-06-15', '2025-07-23', 500, '2026-07-23', 1, '2025-07-23 18:03:39', '2025-10-14 20:31:38', 'regular', '2025-07-23', 1),
(216, 'Idar Voldsund', 'idarvoldsund@tussa.com', '99308221', 'Arhaugvegen 14, 6095 Bølandet', NULL, '2025-07-23', 500, '2026-07-23', 1, '2025-07-23 18:03:40', '2025-10-14 20:26:32', 'regular', '2025-07-23', 1),
(217, 'Anne-Marie Westbø', 'annemawest4268@gmail.com', '91804691', 'Torvikvegen 43 6095 Bølandet', NULL, '2025-07-23', 500, '2026-07-23', 1, '2025-07-23 18:03:40', '2025-09-30 22:32:22', 'regular', '2025-07-23', 1),
(218, 'Hallgeir Åsebø', 'hallgeir66@hotmail.com', '95753664', 'Ædane 9 6080 Gurskøy', NULL, '2025-07-23', 500, '2026-07-23', 1, '2025-07-23 18:03:40', '2025-09-30 22:32:22', 'regular', '2025-07-23', 1),
(224, 'Unni Nerland Nerland', 'unni@nerland.io', '45474106', 'Svingen 8', NULL, '2025-07-28', 500, '2026-07-28', 1, '2025-07-28 13:33:45', '2025-10-14 20:40:17', 'regular', '2025-07-28', 1),
(225, 'Linda Myrseth Kvalsund', 'linda43@online.no', '93058908', 'Øvre plassgjerdet, 5, 6098 Nerlandsøy', NULL, '0000-00-00', 500, NULL, 1, '2025-07-30 22:09:05', '2025-10-14 20:31:47', 'regular', '0000-00-00', 1),
(227, 'Laila Nedreberg Remøy Remøy', 'laila@remoy.org', '40873948', 'Nedre plassgjerde 7', '1962-08-21', '2025-08-22', 500, '2026-08-22', 1, '2025-08-22 11:06:47', '2025-10-14 20:31:27', 'regular', '2025-08-22', 1),
(228, 'Halvard Leine', 'halvard.leine@tussa.com', '95247887', 'Leine 41', '1956-11-01', '2025-08-22', 500, '2026-08-22', 1, '2025-08-22 23:11:51', '2025-10-14 09:26:34', 'regular', '2025-08-22', 1),
(229, 'Svein Arne Leine', 'tull@one.no', '97069105', 'bergslia 3', '1953-07-25', '2025-08-22', 500, '2026-08-22', 1, '2025-08-22 23:12:03', '2025-11-07 19:03:16', 'regular', '2025-08-22', 1),
(230, 'Kristine Bratli Leine', 'tull@one.no', '97500786', 'Leine 41', '1960-09-30', '2025-08-22', 500, '2026-08-22', 1, '2025-08-22 23:12:07', '2025-11-08 05:58:30', 'regular', '2025-08-22', 1),
(231, 'Hildegunn Øye', 'hildegunnoddrun@gmail.com', '48030489', 'Øvre', '1955-03-14', '2025-08-22', 500, '2026-08-22', 1, '2025-08-22 23:12:11', '2025-10-14 09:27:14', 'regular', '2025-08-22', 1),
(232, 'Kari Iren Sandvik', 'kariharisan@hotmail.com', '47863533', 'Eggesbøgrenda 6 A', '1953-04-15', '2025-09-02', 500, '2026-09-02', 1, '2025-09-02 13:46:28', '2025-10-14 20:29:22', 'regular', '2025-09-02', 1),
(233, 'Alf Inge Leine', 'alf.inge.leine@gmail.com', '90636950', 'Nedre Plassegjerdet 15', NULL, '2025-09-07', 150, '2026-09-07', 1, '2025-09-07 14:39:01', '2025-09-30 22:32:22', 'regular', '2025-09-07', 1),
(234, 'Gry Myrseth Myrseth', 'gry.myrseth@gmail.com', '95793923', 'Hulldalsbana 4 A', '1961-12-11', '2025-09-10', 500, '2026-09-10', 1, '2025-09-10 12:39:36', '2025-09-30 22:32:22', 'regular', '2025-09-10', 1),
(235, 'Lise Hauge Kvalsund', 'lise.kvalsund@hotmail.com', '95058708', 'Bakkevn 9', '1959-08-29', '2025-09-10', 500, '2026-09-10', 1, '2025-09-10 12:42:02', '2025-10-14 20:32:09', 'regular', '2025-09-10', 1),
(236, 'Oddgeir Madsen Madsen', 'oddgeir.madsen@tussa.com', '91681611', 'Teigelia 4', '1955-05-21', '2025-09-11', 500, '2026-09-11', 1, '2025-09-11 07:09:22', '2025-10-14 20:34:44', 'regular', '2025-09-11', 1),
(237, 'Karen Myrseth Moltumyr', 'ksmoltumyr@gmail.com', '95966318', 'Markveien 9', NULL, '2025-09-17', 500, '2026-09-17', 1, '2025-09-17 09:04:43', '2025-11-12 13:11:32', 'regular', '2025-09-17', 1),
(238, 'Reidun Frantzen', 'reidun.frantzen@hotmail.com', '47044251', 'Toftestøvegen 24', '1965-11-18', '2025-09-17', 500, '2026-09-17', 1, '2025-09-17 22:06:48', '2025-10-14 20:37:34', 'regular', '2025-09-17', 1),
(239, 'Vilfred Pettersen', NULL, NULL, 'INNHENT', NULL, '2025-09-17', 500, '2026-09-17', 1, '2025-09-17 22:09:37', '2025-11-05 22:57:43', 'regular', '2025-09-17', 1),
(240, 'Karl -Sverre Moltumyr', 'ksmolrumyr@gmail.com', '48281188', 'Merkveien 9', NULL, '2025-09-19', 500, '2026-09-19', 1, '2025-09-19 08:25:07', '2025-11-12 13:09:46', 'regular', '2025-09-19', 1),
(241, 'Paul Sandanger', 'paul.sandanger@tussa.com', '908 95 585', 'Gjerdevegen 10', '1950-05-04', '2025-09-21', 500, '2026-09-21', 1, '2025-09-21 20:40:23', '2025-10-14 20:36:15', 'regular', '2025-09-21', 1),
(242, 'Jorunn Berge', 'lola58@live.no', '91894452', 'Bergsvegen 39', '1958-04-14', '2025-09-21', 500, '2026-09-21', 1, '2025-09-21 20:40:29', '2025-10-14 20:28:47', 'regular', '2025-09-21', 1),
(243, 'Marina Hoff Myking', 'marinamyking@hotmail.com', '91586793', 'Støylavegen 11A, 6094 Leinøy', NULL, '2025-09-23', 500, '2026-09-23', 1, '2025-09-23 17:12:47', '2025-10-14 20:32:46', 'regular', '2025-09-23', 1),
(244, 'Susann Nerland', 'susannno@yahoo.no', '99393147', 'Landsvegen 18, 6098 Nerlandsøy', '2025-04-15', '2025-09-24', 500, '2026-09-24', 1, '2025-09-24 20:26:13', '2025-10-14 20:39:24', 'regular', '2025-09-24', 1),
(245, 'Toril Zahl Remøy', 'toril.zahlr@hotmail.no', '41646461', 'Myrane 6, 6094 Leinøy', '1965-08-22', '2025-09-24', 500, '2026-09-24', 1, '2025-09-24 22:18:04', '2025-10-14 20:40:08', 'regular', '2025-09-24', 1),
(246, 'Norunn Kragseth', 'magne.kragseth@tussa.com', '95433899', 'Kopperstadvegen 47', NULL, '2025-09-25', 150, '2026-09-25', 1, '2025-09-25 11:40:56', '2025-10-14 20:34:23', 'regular', '2025-09-25', 1),
(247, 'Bård Remøy Remøy', 'baard_remoy@hotmail.com', '95724601', 'Kvanesveien 45, 6094 Leinøy', NULL, '2025-09-25', 500, '2026-09-25', 1, '2025-09-25 20:38:29', '2025-10-14 09:11:12', 'regular', '2025-09-25', 1),
(248, 'Audhild Kvalsvik', 'audhild.kvalsvik@outlook.com', '99570874', 'Valvegen 1D, 6092 Fosnavåg', '1957-03-12', '2025-09-26', 500, '2026-09-26', 1, '2025-09-26 21:35:28', '2025-10-14 09:10:50', 'regular', '2025-09-26', 1),
(249, 'Ole Jonny Konningen', 'konningen@icloud.com', '90996351', 'Nedre Plassegjerdet 11, 6098 Nerlandsøy', '1959-02-09', '2025-09-26', 500, '2026-09-26', 1, '2025-09-26 21:37:00', '2025-10-14 20:35:16', 'regular', '2025-09-26', 1),
(250, 'Marit Kvalsund Konningen', 'marit.konningen@hotmail.com', '92461475', 'Nedre Plassegjerdet 11, 6098 Vatne', '1960-02-07', '2025-09-26', 500, '2026-09-26', 1, '2025-09-26 21:37:12', '2025-10-14 20:33:09', 'regular', '2025-09-26', 1),
(251, 'Eivind Longva', 'elongva@gmail.com', '93027251', 'Vågsholmvegen 19, 6090 FosnavÅg', '1959-10-23', '2025-09-26', 500, '2026-09-26', 1, '2025-09-26 21:38:07', '2025-09-30 22:32:22', 'regular', '2025-09-26', 1),
(252, 'Mona Zahl Longva', 'monazl@hotmail.com', '995 01 291', 'Vågsholmvegen 19, 6090 Fosnavåg', NULL, '2025-09-26', 500, '2026-09-26', 1, '2025-09-26 21:38:14', '2025-10-14 20:33:57', 'regular', '2025-09-26', 1),
(253, 'Per Jarle Skar', NULL, '95813437', 'Demingane 14, 6092 Fosnavåg', NULL, '2025-09-29', 500, '2026-09-29', 1, '2025-09-29 15:18:33', '2025-11-05 22:57:14', 'regular', '2025-09-29', 1),
(254, 'Venche Lannerholm', 'wenchelann@hotmail.com', '95498886', 'Knotten 130, 6082 Gursken', NULL, '2025-09-29', 500, '2026-09-29', 1, '2025-09-29 18:42:28', '2025-10-14 20:40:26', 'regular', '2025-09-29', 1),
(255, 'Karin Bø', 'karin.bo15@gmail.com', '41224947', 'Sandevegen 6, 6095 Bølandet', NULL, '2025-09-29', 500, '2026-09-29', 1, '2025-09-29 18:52:07', '2025-10-14 20:29:32', 'regular', '2025-09-29', 1),
(256, 'Sigurd Notøy', 'sigmarnotoy@gmail.com', '90645690', 'Stornesvegen 8, 6070 Tjørvåg', NULL, '2025-09-29', 500, '2026-09-29', 1, '2025-09-29 18:53:45', '2025-10-14 20:39:12', 'regular', '2025-09-29', 1),
(257, 'Bergljot Myrseth', NULL, '90025016', 'Mikkelhaugen 9,, 6092 Fosnavåg', NULL, '2025-09-29', 500, '2026-09-29', 1, '2025-09-29 18:56:17', '2025-10-19 08:21:52', 'regular', '2025-09-29', 1),
(258, 'Jostein Mo', 'jo-i-mo@hotmail.com', '90683835', 'Mosgeila, 6154 Ørsta', NULL, '2025-09-29', 500, '2026-09-29', 1, '2025-09-29 18:57:55', '2025-10-14 20:28:58', 'regular', '2025-09-29', 1),
(259, 'Anne Kristensen', NULL, '94891113', 'Storbakken 2, 6092 Fosnavåg', NULL, '2025-09-29', 500, '2026-09-29', 1, '2025-09-29 19:00:58', '2025-10-19 08:22:14', 'regular', '2025-09-29', 1),
(260, 'Oddveig GARVIK', 'Oddveig.petrin.garvik@outlook.com', '99611182', 'Mikkelhaugen 6, 6092 Fosnavåg', NULL, '2025-09-29', 500, '2026-09-29', 1, '2025-09-29 20:05:44', '2025-11-10 11:42:55', 'regular', '2025-09-29', 1),
(261, 'Laila Berge', 'berge_laila35@hotmail.com', '41498722', 'Elsebøveien 17, 6091 Fosnavåg', '1959-11-16', '2025-09-29', 500, '2026-09-29', 1, '2025-09-29 20:05:56', '2025-10-14 20:31:18', 'regular', '2025-09-29', 1),
(262, 'Guttorm Berge', 'gube53@hotmail.com', '97777840', 'Flusundvegen 53, 6091 Fosnavåg', '1953-05-11', '2025-09-29', 500, '2026-09-29', 1, '2025-09-29 20:06:07', '2025-10-14 09:26:10', 'regular', '2025-09-29', 1),
(263, 'Line Nygard', 'linenyga@hotmail.com', '957 25 121', 'Flusundvegen 53, 6091 Fosnavåg', '1969-02-18', '2025-09-29', 500, '2026-09-29', 1, '2025-09-29 20:06:15', '2025-10-14 20:31:56', 'regular', '2025-09-29', 1),
(264, 'Anita H Kvalsund', 'anitah.kvalsund@hotmail.com', '91107083', 'Koperstadvegen 43, 6098 NERLANDSØY', '1959-07-17', '2025-09-30', 500, '2026-09-30', 1, '2025-09-30 19:17:30', '2025-09-30 22:32:22', 'regular', '2025-09-30', 1),
(265, 'Runar Kvalsund', 'runar.kvalsund@tussa.com', '47015195', 'Koperstadvegen 43, 6098 Nerlandsøy', '1958-02-21', '2025-09-30', 500, '2026-09-30', 1, '2025-09-30 19:17:38', '2025-10-14 20:38:26', 'regular', '2025-09-30', 1),
(266, 'Perny Hilde Kvalsvik', 'pernyh58@gmail.com', '92842285', 'Nesevegen 5, 6065 Ulstein', '1958-09-19', '2025-09-30', 500, '2026-09-30', 1, '2025-09-30 20:54:49', '2025-09-30 22:32:22', 'regular', '2025-09-30', 1),
(267, 'Borghild Hjelmeseth', 'hjelmrsethborghild@gmail.com', '95270207', 'Vareleitebakkane 7b, 6065 Ulstein', NULL, '2025-10-01', 500, NULL, 1, '2025-10-01 14:22:06', '2025-10-01 17:43:34', 'regular', '2025-10-01', 1),
(268, 'Rune Hjelmeseth', 'ruhjelmeseth@gmail.com', '48275916', 'Vareleitebakkane 7b, 6065 Ulstein', NULL, '2025-10-01', 500, NULL, 1, '2025-10-01 14:22:11', '2025-10-14 20:38:36', 'regular', '2025-10-01', 1),
(269, 'Ove Knut Hansen', 'ove.k.hansen@hotmail.no', '41927870', 'Smålegane 30, 6091 Fosnavåg', NULL, '2025-10-01', 500, NULL, 1, '2025-10-01 14:23:55', '2025-10-19 08:09:40', 'regular', '2025-10-01', 1),
(270, 'Berit Oline Hansen', 'berit.oline.hansen@gmail.com', '97616902', 'Smålegane 30, 6091 Fosnavåg', NULL, '2025-10-01', 500, NULL, 1, '2025-10-01 14:24:00', NULL, 'regular', '2025-10-01', 1),
(271, 'Sigrunn Skoge', 'sigrunnskoge@live.no', '91747050', 'Gjerdevegen 10, 6060 Hareid', '1953-04-12', '2025-10-01', 500, NULL, 1, '2025-10-01 14:29:30', '2025-10-14 20:39:02', 'regular', '2025-10-01', 1),
(273, 'Ester Johanne Higraff', 'esterhigraff@hotmail.com', '91133307', 'Øvre Plassegjerdet 15, 6098 Nerlandsøy', NULL, '2025-10-05', 500, NULL, 1, '2025-10-05 21:33:33', '2025-11-11 08:25:26', 'regular', '2025-10-05', 1),
(274, 'Julianne Higraff', 'julianne.higraff@gmail.com', '99362199', 'Mjelthaugvegen 28, 6098 Nerlandsøy', '1963-04-05', '2025-10-14', 500, NULL, 1, '2025-10-14 21:20:56', '2025-10-19 08:13:52', 'regular', '2025-10-14', 1),
(275, 'Tore Olav Relling', 'torerelling@hotmail.com', '96613458', 'Mjelthaugvegen 28, 6098 Nerlandsøy', '1957-05-09', '2025-10-14', 500, NULL, 1, '2025-10-14 21:21:08', '2025-11-12 13:07:31', 'regular', '2025-10-14', 1),
(276, 'Gunlaug Frantsen', 'gunlaugfrantsen@gmail.com', '97478971', 'Ospenesvegen 7, 6092 Fosnavåg', '2025-09-23', '2025-10-16', 500, NULL, 1, '2025-10-16 23:27:53', '2025-10-19 08:08:19', 'regular', '2025-10-16', 1),
(277, 'Per Martin Hjelmeseth', 'per.hjelmeseth@hotmail.com', '90578620', 'Øvre Bergslia4, 6091 Fosnavåg', '1951-02-10', '2025-10-16', 500, NULL, 1, '2025-10-16 23:28:13', NULL, 'regular', '2025-10-16', 1),
(278, 'Asveig Kvalsund', 'pak@tussa.com', '97062584', 'Myravegen 20, 6098 Nerlandsøy', '1954-12-06', '2025-10-16', 500, NULL, 1, '2025-10-16 23:28:20', NULL, 'regular', '2025-10-16', 1),
(279, 'Trond Tellefsen', 'trond.tellefsen@oneco.no', '91729150', NULL, NULL, '2025-10-17', 500, '2026-10-17', 1, '2025-10-17 20:01:10', '2025-10-19 08:19:35', 'regular', '2025-10-17', 1),
(280, 'Leidulf Helde', 'tull@one.no', '41646426', 'x', NULL, '2025-10-17', 500, '2026-10-17', 1, '2025-10-17 20:02:42', '2025-11-07 19:05:42', 'regular', '2025-10-17', 1),
(281, 'Elisabet Larsen Voldnes', 'Elvoldnes@gmail.com', '48180039', 'Eggesbøvegen 57, 6092 Fosnavåg', '1956-08-03', '2025-10-23', 500, NULL, 1, '2025-10-23 14:04:40', NULL, 'regular', '2025-10-23', 1),
(282, 'Signhild Skorpen', 'fruskorpen@hotmail.com', '4790984170', 'Holmsildgata 16, 6090 FosnavÅg', NULL, '2025-10-23', 500, NULL, 1, '2025-10-23 14:04:46', NULL, 'regular', '2025-10-23', 1),
(283, 'Hege N. Vestavik', 'hegenv@tussa.com', '+4791802900', 'Nedre Sørlia 26, 6092 Fosnavåg', NULL, '2025-10-23', 500, NULL, 1, '2025-10-23 14:06:17', '2025-10-23 15:09:42', 'regular', '2025-10-23', 1),
(284, 'Leni Stenersen', 'leni.stenersen@gmail.com', '900 14 296', 'Kleivaneset 14b, 6090 Fosnavåg', NULL, '2025-10-23', 500, NULL, 1, '2025-10-23 14:06:37', NULL, 'regular', '2025-10-23', 1),
(286, 'Arne Voldnes', 'arne.voldnes@gmail.com', '48180040', 'Eggesbøvegen 57, 6092 Fosnavåg', '1946-08-13', '2025-10-26', 500, NULL, 1, '2025-10-26 09:37:08', NULL, 'regular', '2025-10-26', 1),
(287, 'Jan Leine', 'jan.leine57@gmail.com', '91689870', 'Mikkelhaugen6, 6092 Fosnavåg', NULL, '2025-10-29', 500, NULL, 1, '2025-10-29 15:47:21', NULL, 'regular', '2025-10-29', 1),
(288, 'Marit Bøe Rygh', 'maritboe@gmail.com', '95140474', 'Bøvegen 48, 6095 Bølandet', NULL, '2025-10-30', 500, NULL, 1, '2025-10-30 11:40:41', NULL, 'regular', '2025-10-30', 1),
(289, 'Tom Rygh', 'tomrygh@gmail.com', '91135955', 'Bøvegen 48, 6095 Bølandet', NULL, '2025-10-30', 500, NULL, 1, '2025-10-30 11:40:44', NULL, 'regular', '2025-10-30', 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `members`
--
ALTER TABLE `members`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `members`
--
ALTER TABLE `members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=290;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
