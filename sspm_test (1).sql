-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Mar 13, 2026 at 03:38 PM
-- Server version: 8.0.39-cll-lve
-- PHP Version: 8.4.16

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sspm_test`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int NOT NULL,
  `firstname` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `lastname` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `phone` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `username` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `role` enum('admin','super') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'admin',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admin_logs`
--

CREATE TABLE `admin_logs` (
  `id` bigint NOT NULL,
  `admin_id` int DEFAULT NULL,
  `admin_name` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `action` varchar(60) COLLATE utf8mb4_general_ci NOT NULL,
  `entity` varchar(60) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `entity_id` int DEFAULT NULL,
  `details` text COLLATE utf8mb4_general_ci,
  `ip` varchar(64) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_logs`
--

INSERT INTO `admin_logs` (`id`, `admin_id`, `admin_name`, `action`, `entity`, `entity_id`, `details`, `ip`, `user_agent`, `created_at`) VALUES
(1, 7, 'kakha', 'test_log', 'test', 1, '{\"hello\":\"world\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-30 15:02:33'),
(2, 7, 'kakha', 'slide_create', 'slides', 0, '{\"title\":null,\"link\":null,\"image\":null,\"order\":null}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-30 15:03:22'),
(3, 7, 'kakha', 'slide_create', 'slides', 6, '{\"title\":\"\",\"link\":\"\",\"image\":\"uploads/slides/slide_844875b01dc00dfd.jpg\",\"order\":0}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-30 15:04:23'),
(4, 7, 'kakha', 'login', 'admin_users', 7, '{\"username\":\"kakha\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-30 15:06:20'),
(5, 7, 'kakha', 'news_create', 'news', 1, '{\"title\":\"ბანაკი1\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-30 15:13:12'),
(6, 7, 'kakha', 'news_create', 'news', 2, '{\"title\":\"dfsd\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-30 15:26:52'),
(7, 7, 'kakha', 'news_create', 'news', 1, '{\"title\":\"main\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-30 15:45:20'),
(8, 7, 'kakha', 'news_delete', 'news', 1, '[]', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-30 15:45:24'),
(9, 7, 'kakha', 'news_create', 'news', 2, '{\"title\":\"main\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-30 15:45:40'),
(10, 7, 'kakha', 'news_create', 'news', 3, '{\"title\":\"s\",\"slug\":\"s\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-30 15:51:54'),
(11, 7, 'kakha', 'news_create', 'news', 4, '{\"title\":\"sad\",\"slug\":\"sad\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-30 16:03:27'),
(12, 7, 'kakha', 'camp_create', 'camps', 1, '{\"title\":\"ანაკლია\",\"slug\":\"ანაკლია\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-30 21:58:31'),
(13, 7, 'kakha', 'login', 'admin_users', 7, '{\"username\":\"kakha\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-31 10:35:21'),
(14, 7, 'kakha', 'login', 'admin_users', 7, '{\"username\":\"kakha\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2025-12-31 11:23:36'),
(15, 7, 'kakha', 'login', 'admin_users', 7, '{\"username\":\"kakha\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 18:36:20'),
(16, 7, 'kakha', 'login', 'admin_users', 7, '{\"username\":\"kakha\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-02 19:29:53'),
(17, 7, 'kakha', 'login', 'admin_users', 7, '{\"username\":\"kakha\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-03 11:47:54'),
(18, 7, 'kakha', 'login', 'admin_users', 7, '{\"username\":\"kakha\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-03 12:37:36'),
(19, 7, 'kakha', 'login', 'admin_users', 7, '{\"username\":\"kakha\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-04 07:51:41'),
(20, 7, 'kakha', 'login', 'admin_users', 7, '{\"username\":\"kakha\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-04 08:59:51'),
(21, 7, 'kakha', 'login', 'admin_users', 7, '{\"username\":\"kakha\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-04 12:52:23'),
(22, 7, 'kakha', 'login', 'admin_users', 7, '{\"username\":\"kakha\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-04 19:59:04'),
(23, 7, 'kakha', 'login', 'admin_users', 7, '{\"username\":\"kakha\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-05 14:16:09'),
(24, 7, 'kakha', 'login', 'admin_users', 7, '{\"username\":\"kakha\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 17:11:05'),
(25, 7, 'kakha', 'login', 'admin_users', 7, '{\"username\":\"kakha\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-11 20:11:06'),
(26, 7, 'kakha', 'slide_create', 'slides', 7, '{\"title\":\"\",\"link\":\"\",\"image\":\"uploads/slides/slide_8323a9360cddb820.jpg\",\"order\":0}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 13:22:53'),
(27, 7, 'kakha', 'login', 'admin_users', 7, '{\"username\":\"kakha\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-12 13:33:06'),
(28, 7, 'kakha', 'login', 'admin_users', 7, '{\"username\":\"kakha\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-13 09:03:11'),
(29, 7, 'kakha', 'slide_create', 'slides', 8, '{\"title\":\"\",\"link\":\"\",\"image\":\"uploads/slides/slide_b77334634d5636b6.jpg\",\"order\":0}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-13 09:04:05'),
(30, 7, 'kakha', 'login', 'admin_users', 7, '{\"username\":\"kakha\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-13 09:16:22'),
(31, 7, 'kakha', 'login', 'admin_users', 7, '{\"username\":\"kakha\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-13 09:41:20'),
(32, 7, 'kakha', 'slide_create', 'slides', 9, '{\"title\":\"\",\"link\":\"\",\"image\":\"uploads/slides/slide_e318121fa744ce0e.jpg\",\"order\":0}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '2026-01-13 09:47:02'),
(33, 7, 'kakha', 'login', 'admin_users', 7, '{\"username\":\"kakha\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-17 14:47:12'),
(34, 7, 'kakha', 'login', 'admin_users', 7, '{\"username\":\"kakha\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-21 14:57:55'),
(35, 7, 'kakha', 'login', 'admin_users', 7, '{\"username\":\"kakha\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-24 08:55:32'),
(36, 7, 'kakha', 'login', 'admin_users', 7, '{\"username\":\"kakha\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-24 10:28:57'),
(37, 7, 'kakha', 'login', 'admin_users', 7, '{\"username\":\"kakha\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-24 16:43:31'),
(38, 7, 'kakha', 'login', 'admin_users', 7, '{\"username\":\"kakha\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-24 19:47:01'),
(39, 7, 'kakha', 'login', 'admin_users', 7, '{\"username\":\"kakha\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-24 22:01:17'),
(40, 7, 'kakha', 'login', 'admin_users', 7, '{\"username\":\"kakha\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-25 10:57:24'),
(41, 7, 'kakha', 'login', 'admin_users', 7, '{\"username\":\"kakha\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-25 12:12:28'),
(42, 7, 'kakha', 'login', 'admin_users', 7, '{\"username\":\"kakha\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-25 12:48:24'),
(43, 7, 'kakha', 'login', 'admin_users', 7, '{\"username\":\"kakha\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-25 12:50:02'),
(44, 7, 'kakha', 'login', 'admin_users', 7, '{\"username\":\"kakha\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-25 13:50:43'),
(45, 7, 'kakha', 'login', 'admin_users', 7, '{\"username\":\"kakha\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-25 14:16:30'),
(46, 7, 'kakha', 'slide_create', 'slides', 10, '{\"title\":\"\",\"link\":\"\",\"image\":\"uploads/slides/slide_ff3d508eb6009212.webp\",\"order\":0}', '92.54.192.106', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-25 14:20:04'),
(47, 7, 'kakha', 'login', 'admin_users', 7, '{\"username\":\"kakha\"}', '213.200.31.57', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Mobile Safari/537.36', '2026-01-25 17:55:11'),
(48, 7, 'kakha', 'login', 'admin_users', 7, '{\"username\":\"kakha\"}', '213.200.15.44', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-25 20:06:59'),
(49, 7, 'kakha', 'login', 'admin_users', 7, '{\"username\":\"kakha\"}', '213.200.15.44', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-26 05:49:31'),
(50, 7, 'kakha', 'login', 'admin_users', 7, '{\"username\":\"kakha\"}', '92.54.192.106', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-26 09:34:02'),
(51, 7, 'kakha', 'login', 'admin_users', 7, '{\"username\":\"kakha\"}', '92.54.192.106', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-26 09:34:14'),
(52, 7, 'kakha', 'login', 'admin_users', 7, '{\"username\":\"kakha\"}', '188.169.6.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-31 17:29:36'),
(53, 7, 'kakha', 'slide_create', 'slides', 11, '{\"title\":\"\",\"link\":\"\",\"image\":\"uploads/slides/slide_fe9925a8b10e4f51.png\",\"order\":0}', '188.169.6.48', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-01-31 17:31:51'),
(54, 7, 'kakha', 'login', 'admin_users', 7, '{\"username\":\"kakha\"}', '92.54.192.110', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-19 13:13:01');

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int UNSIGNED NOT NULL,
  `username` varchar(60) COLLATE utf8mb4_general_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `role` enum('super','admin') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'admin',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_users`
--

INSERT INTO `admin_users` (`id`, `username`, `password_hash`, `role`, `is_active`, `created_at`, `last_login_at`) VALUES
(7, 'kakha', '$2y$10$uHVJkSm593d7.o2bI6bk0Oo0JzblANopwY4UcB6sF7lC.DAwx5v9i', 'super', 1, '2025-12-30 14:04:27', '2026-02-19 14:13:01');

-- --------------------------------------------------------

--
-- Table structure for table `applicants`
--

CREATE TABLE `applicants` (
  `id` int NOT NULL,
  `personal_id` varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
  `first_name` varchar(120) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `last_name` varchar(120) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `phone` varchar(64) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(160) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `camps`
--

CREATE TABLE `camps` (
  `id` int NOT NULL,
  `name` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `name_en` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `slug` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `card_text` varchar(255) COLLATE utf8mb4_general_ci DEFAULT '',
  `card_text_en` text COLLATE utf8mb4_general_ci,
  `cover` varchar(500) COLLATE utf8mb4_general_ci DEFAULT '',
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `closed` tinyint(1) NOT NULL DEFAULT '0',
  `window_days` int NOT NULL DEFAULT '365',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `camps`
--

INSERT INTO `camps` (`id`, `name`, `name_en`, `slug`, `card_text`, `card_text_en`, `cover`, `start_date`, `end_date`, `closed`, `window_days`, `created_at`, `updated_at`) VALUES
(1, 'ანაკლია', NULL, 'ანაკლია', '', NULL, '/youthagency/uploads/camps/up_1767281577_32e8ffb313f3.jpg', '2025-12-30', '2026-01-21', 0, 365, '2025-12-31 11:43:18', '2026-01-12 13:19:38'),
(3, 'შაორი', NULL, 'შაორი', 'sdf', NULL, '', '2026-01-12', '2026-01-31', 0, 365, '2026-01-25 11:03:53', '2026-01-26 09:34:59');

-- --------------------------------------------------------

--
-- Table structure for table `camps_attendance`
--

CREATE TABLE `camps_attendance` (
  `id` int NOT NULL,
  `unique_key` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `camp_id` int NOT NULL,
  `registration_id` int DEFAULT NULL,
  `camp_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `approved_at` datetime NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `camps_attendance`
--

INSERT INTO `camps_attendance` (`id`, `unique_key`, `camp_id`, `registration_id`, `camp_name`, `start_date`, `end_date`, `approved_at`, `created_at`) VALUES
(2, '339010856592132', 1, 10, 'ანაკლია', '2025-12-30', '2026-01-21', '2026-01-25 02:11:25', '2026-01-25 02:04:35'),
(6, '339010856592132323', 3, 12, 'შაორი', '2026-01-12', '2026-01-31', '2026-01-26 10:35:12', '2026-01-25 15:06:40'),
(10, '33901085659213', 1, 7, 'ანაკლია', '2025-12-30', '2026-01-21', '2026-01-25 15:16:07', '2026-01-25 15:16:07'),
(14, '339010856592132323', 1, 11, 'ანაკლია', '2025-12-30', '2026-01-21', '2026-01-25 16:52:41', '2026-01-25 16:52:41'),
(15, '23123', 1, 9, 'ანაკლია', '2025-12-30', '2026-01-21', '2026-01-25 21:17:57', '2026-01-25 21:17:57');

-- --------------------------------------------------------

--
-- Table structure for table `camps_fields`
--

CREATE TABLE `camps_fields` (
  `id` int NOT NULL,
  `camp_id` int NOT NULL,
  `sort_order` int NOT NULL DEFAULT '1',
  `label` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `label_en` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `type` varchar(30) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'text',
  `required` tinyint(1) NOT NULL DEFAULT '0',
  `options_json` text COLLATE utf8mb4_general_ci,
  `field_key` varchar(64) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `camps_fields`
--

INSERT INTO `camps_fields` (`id`, `camp_id`, `sort_order`, `label`, `label_en`, `type`, `required`, `options_json`, `field_key`) VALUES
(62, 1, 1, 'პირადი ნომერი', NULL, 'pid', 1, '{\"autofill\":\"pid\"}', 'pid'),
(63, 1, 2, 'სახელი', NULL, 'text', 1, '{\"autofill\":\"first_name\"}', 'first_name'),
(64, 1, 3, 'გვარი', NULL, 'text', 1, '', 'last_name'),
(65, 1, 4, 'ტელეფონის ნომერი', NULL, 'text', 0, '{\"autofill\":\"birth_date\"}', 'birth_date'),
(66, 1, 5, 'ასაკი', NULL, 'text', 0, '', 'age'),
(67, 1, 6, 'ქალაქი', NULL, 'text', 0, '{\"autofill\":\"phone\"}', 'phone'),
(68, 1, 7, 'ელფოსტა', NULL, 'email', 0, '', 'email'),
(69, 1, 8, 'საცხოვრებელი მისამართი', NULL, 'text', 0, '{\"autofill\":\"email\"}', 'email'),
(70, 1, 9, 'უნივერსიტეტი', NULL, 'text', 0, '', 'university'),
(71, 1, 10, 'ფაკულტეტი', NULL, 'text', 1, '', 'faculty'),
(72, 1, 11, 'კურსი', NULL, 'text', 0, '', 'course'),
(77, 3, 1, 'პირადი ნომერი', NULL, 'pid', 0, '', 'pid'),
(78, 3, 2, 'სახელი', NULL, 'text', 0, '', 'first_name'),
(79, 3, 3, 'გვარი', NULL, 'text', 0, '', 'last_name'),
(80, 3, 4, 'მეილი', NULL, 'email', 0, '', 'email');

-- --------------------------------------------------------

--
-- Table structure for table `camps_pid_blocklist`
--

CREATE TABLE `camps_pid_blocklist` (
  `id` int NOT NULL,
  `camp_id` int DEFAULT NULL,
  `pid` varchar(64) COLLATE utf8mb4_general_ci NOT NULL,
  `reason` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `camps_pid_blocklist`
--

INSERT INTO `camps_pid_blocklist` (`id`, `camp_id`, `pid`, `reason`, `created_at`) VALUES
(1, NULL, '33901085659', '', '2026-01-01 19:52:10'),
(2, 1, '33901085659213', 'მიზეზი', '2026-01-12 17:27:43'),
(3, 1, '3390108565921323', '', '2026-01-12 17:27:59');

-- --------------------------------------------------------

--
-- Table structure for table `camps_posts`
--

CREATE TABLE `camps_posts` (
  `id` int NOT NULL,
  `camp_id` int NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `title_en` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cover` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `body` mediumtext COLLATE utf8mb4_general_ci NOT NULL,
  `body_en` mediumtext COLLATE utf8mb4_general_ci,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `camps_posts`
--

INSERT INTO `camps_posts` (`id`, `camp_id`, `title`, `title_en`, `cover`, `body`, `body_en`, `created_at`) VALUES
(2, 1, 'ანაკლიის ბანაკი', NULL, '/youthagency/uploads/camps/posts/up_1767294942_9cb8e59c5986.jpg', 'Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.', NULL, '2026-01-01 19:32:14');

-- --------------------------------------------------------

--
-- Table structure for table `camps_post_media`
--

CREATE TABLE `camps_post_media` (
  `id` int NOT NULL,
  `post_id` int NOT NULL,
  `path` varchar(500) COLLATE utf8mb4_general_ci NOT NULL,
  `sort_order` int NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `camps_post_media`
--

INSERT INTO `camps_post_media` (`id`, `post_id`, `path`, `sort_order`, `created_at`) VALUES
(1, 2, '/youthagency/uploads/camps/posts/gal_1767281534_07f99fbbad3b.jpg', 1, '2026-01-01 19:32:14'),
(3, 2, '/youthagency/uploads/camps/posts/gal_1767281534_f1743acb7f18.jpg', 3, '2026-01-01 19:32:14'),
(4, 2, '/youthagency/uploads/camps/posts/gal_1767281534_d051b8638833.jpg', 4, '2026-01-01 19:32:14'),
(5, 2, '/youthagency/uploads/camps/posts/gal_1767281534_542bbfbf0e82.jpg', 5, '2026-01-01 19:32:14'),
(6, 2, '/youthagency/uploads/camps/posts/gal_1767281534_0acff7d4d06b.jpg', 6, '2026-01-01 19:32:14'),
(7, 2, '/youthagency/uploads/camps/posts/gal_1767281534_ff01c740cfe8.jpg', 7, '2026-01-01 19:32:14'),
(8, 2, '/youthagency/uploads/camps/posts/gal_1767281534_67a82f774624.jpg', 8, '2026-01-01 19:32:14'),
(9, 2, '/youthagency/uploads/camps/posts/gal_1767281534_5509d3d4cce2.jpg', 9, '2026-01-01 19:32:14');

-- --------------------------------------------------------

--
-- Table structure for table `camps_registrations`
--

CREATE TABLE `camps_registrations` (
  `id` int NOT NULL,
  `camp_id` int NOT NULL,
  `unique_key` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `ip` varchar(64) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `values_json` mediumtext COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` datetime NOT NULL,
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pending',
  `admin_note` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `values_map_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin
) ;

--
-- Dumping data for table `camps_registrations`
--

INSERT INTO `camps_registrations` (`id`, `camp_id`, `unique_key`, `ip`, `values_json`, `created_at`, `status`, `admin_note`, `updated_at`, `values_map_json`) VALUES
(3, 1, '339010856591', '::1', '[\"339010856591\",\"კახა\",\"ცხომელიძე\",\"571185852\",\"ოზურგეთი\",\"ოზურგეთი\",\"სტუ\"]', '2026-01-01 19:52:40', 'pending', '', '2026-01-13 13:17:57', NULL),
(6, 1, '01010101010', '::1', '[\"01010101010\",\"\",\"\",\"\",\"\",\"\",\"\"]', '2026-01-01 23:01:47', 'rejected', '', '2026-01-12 17:26:07', '{\"15\":\"01010101010\",\"16\":\"\",\"17\":\"\",\"18\":\"\",\"19\":\"\",\"20\":\"\",\"21\":\"\"}'),
(7, 1, '33901085659213', '::1', '{\"62\":\"33901085659213\",\"63\":\"saddsa\",\"64\":\"asasda\",\"65\":\"\",\"66\":\"\",\"67\":\"\",\"68\":\"\",\"69\":\"\",\"70\":\"\",\"71\":\"dsada\",\"72\":\"\"}', '2026-01-03 17:25:17', 'approved', '', '2026-01-25 15:16:07', NULL),
(8, 1, '2312', '::1', '{\"62\":\"2312\",\"63\":\"sad\",\"64\":\"asda\",\"65\":\"\",\"66\":\"\",\"67\":\"\",\"68\":\"\",\"69\":\"\",\"70\":\"\",\"71\":\"sa\",\"72\":\"\"}', '2026-01-04 01:38:15', 'rejected', '', '2026-01-25 21:18:02', NULL),
(9, 1, '23123', '::1', '{\"62\":\"23123\",\"63\":\"dasd\",\"64\":\"asda\",\"65\":\"\",\"66\":\"\",\"67\":\"\",\"68\":\"\",\"69\":\"\",\"70\":\"\",\"71\":\"sda\",\"72\":\"\"}', '2026-01-04 01:38:32', 'approved', '', '2026-01-25 21:17:57', NULL),
(10, 1, '339010856592132', '::1', '{\"62\":\"339010856592132\",\"63\":\"sada\",\"64\":\"sad\",\"65\":\"\",\"66\":\"\",\"67\":\"\",\"68\":\"\",\"69\":\"\",\"70\":\"\",\"71\":\"sda\",\"72\":\"\"}', '2026-01-04 01:51:55', 'approved', '', '2026-01-25 02:11:25', NULL),
(11, 1, '339010856592132323', '::1', '{\"62\":\"339010856592132323\",\"63\":\"სადა\",\"64\":\"სადა\",\"65\":\"\",\"66\":\"\",\"67\":\"\",\"68\":\"\",\"69\":\"\",\"70\":\"\",\"71\":\"ასდა\",\"72\":\"\"}', '2026-01-12 17:28:32', 'approved', '', '2026-01-25 16:52:41', NULL),
(12, 3, '339010856592132323', '::1', '{\"77\":\"339010856592132323\",\"78\":\"kaxa\",\"79\":\"cxome\",\"80\":\"cxome@gmail.com\"}', '2026-01-25 15:06:20', 'approved', '', '2026-01-26 10:35:12', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `camp_fields`
--

CREATE TABLE `camp_fields` (
  `id` int NOT NULL,
  `camp_id` int NOT NULL,
  `label` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `type` enum('text','pid','phone','email','date','select','file') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'text',
  `req` tinyint(1) NOT NULL DEFAULT '0',
  `options_text` varchar(500) COLLATE utf8mb4_general_ci DEFAULT '',
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `camp_posts`
--

CREATE TABLE `camp_posts` (
  `id` int NOT NULL,
  `camp_id` int NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `body` mediumtext COLLATE utf8mb4_general_ci NOT NULL,
  `cover` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `camp_registrations`
--

CREATE TABLE `camp_registrations` (
  `id` int NOT NULL,
  `camp_id` int NOT NULL,
  `unique_key` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `ip` varchar(64) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `camp_registration_values`
--

CREATE TABLE `camp_registration_values` (
  `id` int NOT NULL,
  `registration_id` int NOT NULL,
  `field_id` int NOT NULL,
  `value_text` mediumtext COLLATE utf8mb4_general_ci,
  `file_path` text COLLATE utf8mb4_general_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `grants`
--

CREATE TABLE `grants` (
  `id` int UNSIGNED NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `title_en` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `slug` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `description` text COLLATE utf8mb4_general_ci,
  `description_en` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `body` mediumtext COLLATE utf8mb4_general_ci,
  `body_en` mediumtext COLLATE utf8mb4_general_ci,
  `image_path` varchar(500) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `deadline` date DEFAULT NULL,
  `max_amount_person` decimal(12,2) DEFAULT NULL,
  `max_amount_org` decimal(12,2) DEFAULT NULL,
  `status` enum('current','closed') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'current',
  `apply_url` varchar(500) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `sort_order` int NOT NULL DEFAULT '100',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  `max_budget` decimal(12,2) NOT NULL DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `grants`
--

INSERT INTO `grants` (`id`, `title`, `title_en`, `slug`, `description`, `description_en`, `body`, `body_en`, `image_path`, `deadline`, `max_amount_person`, `max_amount_org`, `status`, `apply_url`, `sort_order`, `is_active`, `created_at`, `updated_at`, `max_budget`) VALUES
(1, 'ახალგაზრდული ინიციატივების საგრანტო პროგრამა', NULL, 'axalgazrduli-iniciativebi', 'ახალგაზრდების მიერ ინიცირებული იდეებისა და მცირე პროექტების დაფინანსება.', NULL, 'საგრანტო პროგრამის მიზანია ახალგაზრდების მიერ წამოწყებული ინიციატივების მხარდაჭერა.\n\nპროგრამის ამოცანებია:\n• ახალგაზრდების ჩართულობის გაზრდა\n• ინოვაციური იდეების მხარდაჭერა\n• სოციალური პასუხისმგებლობის გაძლიერება', NULL, '', '2026-01-20', NULL, NULL, 'current', '/youthagency/grants_apply.php', 10, 1, '2026-01-04 13:10:53', NULL, 5000.00),
(2, 'ინოვაციური და ტექნოლოგიური პროექტების გრანტი', NULL, 'inovaciuri-teqnologiuri-proeqtebi', 'ინოვაციაზე, ტექნოლოგიებზე და ციფრულ განვითარებაზე ორიენტირებული პროექტები.', NULL, 'პროგრამა განკუთვნილია ახალგაზრდებისთვის, რომლებიც მუშაობენ ტექნოლოგიურ ან ინოვაციურ მიმართულებებზე.\n\nმიზნები:\n• ციფრული უნარების განვითარება\n• სტარტაპ იდეების მხარდაჭერა\n• ტექნოლოგიური გადაწყვეტილებების ხელშეწყობა', NULL, '', '2026-02-05', NULL, NULL, 'current', '/youthagency/grants_apply.php', 20, 1, '2026-01-04 13:10:53', '2026-01-05 00:49:02', 0.00),
(3, 'საზოგადოებრივი ჩართულობის მინი-გრანტები', NULL, 'sazogadoebrivi-chartuloba', 'საზოგადოებისთვის სასარგებლო ახალგაზრდული აქტივობების მხარდაჭერა.', NULL, 'მინი-გრანტების პროგრამა მიზნად ისახავს ახალგაზრდების მიერ დაგეგმილი საზოგადოებრივი აქტივობების დაფინანსებას.\n\nმიზნები:\n• მოხალისეობა\n• ადგილობრივი ინიციატივები\n• სოციალური პროექტები', NULL, '', '2025-12-01', NULL, NULL, 'current', '/youthagency/grants_apply.php', 30, 1, '2026-01-04 13:10:53', '2026-01-04 21:13:35', 0.00),
(4, 'კულტურული და შემოქმედებითი პროექტების გრანტი', NULL, 'kulturuli-shemoqmedebiti-proeqtebi', 'კულტურის, ხელოვნებისა და შემოქმედებითი ინდუსტრიების მხარდაჭერა.', NULL, 'პროგრამა გათვლილია კულტურულ და შემოქმედებით პროექტებზე.\n\nმიზნები:\n• კულტურული მემკვიდრეობის პოპულარიზაცია\n• ახალგაზრდული შემოქმედების მხარდაჭერა\n• საზოგადოებაში კულტურული აქტივობის გაზრდა', NULL, '', '2026-03-10', NULL, NULL, 'current', '/youthagency/grants_apply.php', 40, 1, '2026-01-04 13:10:53', '2026-01-05 23:49:49', 0.00),
(5, 'sdasdas', NULL, 'assadas', 'sdadasda', NULL, 'dasda', NULL, '/uploads/grants/covers/grant_new_20260105_151519_2003bd.jpg', '2026-01-16', NULL, NULL, 'current', '/youthagency/grants/grants_apply.php?id=5', 0, 1, '2026-01-05 18:15:19', '2026-01-06 00:19:24', 0.00),
(6, 'dfs', NULL, 'dfs', 'dsfd', NULL, 'dsfs', NULL, '/uploads/grants/covers/grant_new_20260106_234101_4f5317.jpg', NULL, NULL, NULL, 'current', '/youthagency/grants/grants_apply.php?id=6', 0, 1, '2026-01-07 02:41:01', NULL, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `grant_applications`
--

CREATE TABLE `grant_applications` (
  `id` int UNSIGNED NOT NULL,
  `grant_id` int UNSIGNED NOT NULL,
  `applicant_name` varchar(190) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(190) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `phone` varchar(64) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` varchar(32) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'submitted',
  `rating` int NOT NULL DEFAULT '0',
  `admin_note` text COLLATE utf8mb4_general_ci,
  `form_data_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `deleted_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL
) ;

--
-- Dumping data for table `grant_applications`
--

INSERT INTO `grant_applications` (`id`, `grant_id`, `applicant_name`, `email`, `phone`, `status`, `rating`, `admin_note`, `form_data_json`, `deleted_at`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, NULL, NULL, 'submitted', 0, NULL, '{\"_meta\":{\"applicant_name\":\"\",\"email\":\"\",\"phone\":\"\"},\"grant_id\":1,\"steps\":{\"applicant\":[],\"project\":[],\"submit\":[]},\"_uploads\":[]}', NULL, '2026-01-05 14:59:34', '2026-01-05 14:59:35'),
(2, 5, 'dzsfxgcvm', 'dzsfxgcvm', NULL, 'need_clarification', 0, NULL, '{\"_meta\":{\"applicant_name\":\"dzsfxgcvm\",\"email\":\"dzsfxgcvm\",\"phone\":\"\"},\"grant_id\":5,\"steps\":{\"განმცხადებელი\":{\"field_1\":\"dzsfxgcvm\"},\"სდაკჯასდა\":[]},\"_uploads\":[]}', NULL, '2026-01-05 15:27:59', '2026-01-06 00:45:35'),
(3, 5, NULL, NULL, NULL, 'submitted', 0, NULL, '{\"applicant_type\":\"person\",\"form_json\":\"{\\\"field_85\\\":\\\"person\\\",\\\"field_86\\\":\\\"dasd\\\",\\\"field_87\\\":\\\"sad\\\",\\\"field_88\\\":\\\"sada\\\",\\\"field_89\\\":\\\"sad\\\",\\\"field_90\\\":\\\"asd\\\",\\\"field_91\\\":\\\"sad\\\",\\\"field_96\\\":\\\"sad\\\",\\\"field_95\\\":\\\"sad\\\",\\\"field_97\\\":\\\"asdsa\\\",\\\"field_98\\\":\\\"asdas\\\",\\\"field_99\\\":\\\"sda\\\"}\"}', NULL, '2026-01-07 18:54:42', NULL),
(4, 5, NULL, NULL, NULL, 'approved', 0, '', '{\"applicant_type\":\"person\",\"form_json\":\"{\\\"field_85\\\":\\\"person\\\",\\\"field_86\\\":\\\"სადას\\\",\\\"field_87\\\":\\\"ასდას\\\",\\\"field_88\\\":\\\"სადას\\\",\\\"field_89\\\":\\\"ასდ\\\",\\\"field_90\\\":\\\"სად\\\",\\\"field_91\\\":\\\"ასდ\\\",\\\"field_95\\\":\\\"სადა\\\",\\\"field_96\\\":\\\"სადას\\\",\\\"field_97\\\":\\\"სად\\\",\\\"field_98\\\":\\\"ასდ\\\",\\\"field_99\\\":\\\"სად\\\"}\"}', NULL, '2026-01-07 19:04:14', NULL),
(5, 5, 'sad', NULL, 'asd', 'in_review', 1, '', '{\"field_85\":\"person\",\"field_86\":\"sad\",\"field_87\":\"sad\",\"field_88\":\"sad\",\"field_89\":\"asd\",\"field_90\":\"asd\",\"field_91\":\"ads\",\"field_95\":\"sdsasdasa\",\"field_97\":\"sadsad\",\"field_98\":\"asdsad\",\"field_99\":\"adsa\"}', NULL, '2026-01-09 00:25:48', NULL),
(6, 5, 'dsfsd', NULL, 'dsfsd', 'submitted', 0, NULL, '{\"field_85\":\"person\",\"field_86\":\"dsfsd\",\"field_87\":\"dsfsd\",\"field_88\":\"sdffsd\",\"field_89\":\"dsfsd\",\"field_90\":\"sdfsdfs\",\"field_91\":\"dfsdsfsd\",\"field_95\":\"sdfsdfsd\",\"field_96\":\"sdfsdfs\",\"field_97\":\"dsdfsdf\",\"field_98\":\"sdfsdfsd\",\"field_99\":\"dsfdf\"}', NULL, '2026-01-12 01:17:41', NULL),
(7, 5, NULL, NULL, NULL, 'need_clarification', 0, '', '{\"field_85\":\"person\"}', NULL, '2026-01-12 17:22:08', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `grant_application_files`
--

CREATE TABLE `grant_application_files` (
  `id` int UNSIGNED NOT NULL,
  `application_id` int UNSIGNED NOT NULL,
  `grant_id` int UNSIGNED NOT NULL,
  `field_name` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `original_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `stored_path` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mime` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `size_bytes` bigint UNSIGNED NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `grant_app_actionplan`
--

CREATE TABLE `grant_app_actionplan` (
  `id` int UNSIGNED NOT NULL,
  `app_id` int UNSIGNED NOT NULL,
  `sort_order` int NOT NULL DEFAULT '1',
  `activity` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `grant_app_files`
--

CREATE TABLE `grant_app_files` (
  `id` int UNSIGNED NOT NULL,
  `app_id` int UNSIGNED NOT NULL,
  `kind` enum('req','other','field') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'other',
  `ref_id` int DEFAULT NULL,
  `grant_id` int UNSIGNED NOT NULL,
  `req_id` int UNSIGNED DEFAULT NULL,
  `orig_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `file_path` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `mime_type` varchar(120) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `file_size` int UNSIGNED NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `grant_app_messages`
--

CREATE TABLE `grant_app_messages` (
  `id` int UNSIGNED NOT NULL,
  `app_id` int UNSIGNED NOT NULL,
  `sender` varchar(16) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'admin',
  `message` text COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `grant_fields`
--

CREATE TABLE `grant_fields` (
  `id` int NOT NULL,
  `grant_id` int NOT NULL,
  `step_id` int NOT NULL,
  `field_key` varchar(64) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `label` varchar(190) COLLATE utf8mb4_general_ci NOT NULL,
  `type` varchar(32) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'text',
  `is_required` tinyint(1) NOT NULL DEFAULT '0',
  `show_for` varchar(16) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'all',
  `options_json` text COLLATE utf8mb4_general_ci,
  `placeholder` varchar(190) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `help_text` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `is_enabled` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `grant_fields`
--

INSERT INTO `grant_fields` (`id`, `grant_id`, `step_id`, `field_key`, `label`, `type`, `is_required`, `show_for`, `options_json`, `placeholder`, `help_text`, `sort_order`, `created_at`, `is_enabled`) VALUES
(2, 5, 1, '', 'გვარი', 'text', 1, 'all', NULL, NULL, NULL, 20, '2026-01-05 19:50:38', 1),
(3, 5, 1, '', 'სახელი', 'text', 0, 'all', NULL, NULL, NULL, 30, '2026-01-05 20:07:47', 1),
(4, 5, 5, '', 'სახელი', 'text', 1, 'all', NULL, NULL, NULL, 10, '2026-01-05 20:14:56', 1),
(5, 5, 5, '', 'გვარი', 'text', 1, 'all', NULL, NULL, NULL, 20, '2026-01-05 20:14:56', 1),
(6, 5, 5, '', 'ტელეფონი', 'phone', 1, 'all', NULL, NULL, NULL, 30, '2026-01-05 20:14:56', 1),
(7, 5, 5, '', 'ელფოსტა', 'email', 0, 'all', NULL, NULL, NULL, 40, '2026-01-05 20:14:56', 1),
(8, 5, 7, '', 'განმცხადებლის ტიპი', 'select', 1, 'all', '[\"ფიზიკური პირი\",\"ორგანიზაცია\"]', NULL, NULL, 10, '2026-01-06 10:43:55', 1),
(9, 5, 7, '', 'სახელი', 'text', 1, 'all', NULL, NULL, NULL, 20, '2026-01-06 10:43:55', 1),
(10, 5, 7, '', 'გვარი', 'text', 1, 'all', NULL, NULL, NULL, 30, '2026-01-06 10:43:55', 1),
(11, 5, 7, '', 'პირადი ნომერი (11 ციფრი)', 'text', 1, 'all', NULL, NULL, NULL, 40, '2026-01-06 10:43:55', 1),
(12, 5, 7, '', 'ტელეფონი (+995...)', 'phone', 1, 'all', NULL, NULL, NULL, 50, '2026-01-06 10:43:55', 1),
(13, 5, 7, '', 'ელ.ფოსტა', 'email', 1, 'all', NULL, NULL, NULL, 60, '2026-01-06 10:43:55', 1),
(14, 5, 7, '', 'მისამართი/რაიონი', 'text', 0, 'all', NULL, NULL, NULL, 70, '2026-01-06 10:43:55', 1),
(15, 5, 8, '', 'პროექტის დასახელება', 'text', 1, 'all', NULL, NULL, NULL, 10, '2026-01-06 10:43:55', 1),
(16, 5, 8, '', 'მოკლე აღწერა', 'textarea', 1, 'all', NULL, NULL, NULL, 20, '2026-01-06 10:43:55', 1),
(17, 5, 8, '', 'სრული აღწერა', 'textarea', 1, 'all', NULL, NULL, NULL, 30, '2026-01-06 10:43:55', 1),
(18, 5, 8, '', 'მიზნები', 'textarea', 1, 'all', NULL, NULL, NULL, 40, '2026-01-06 10:43:55', 1),
(19, 5, 8, '', 'მოსალოდნელი შედეგები', 'textarea', 1, 'all', NULL, NULL, NULL, 50, '2026-01-06 10:43:55', 1),
(20, 5, 8, '', 'დასაწყისი', 'date', 1, 'all', NULL, NULL, NULL, 60, '2026-01-06 10:43:55', 1),
(21, 5, 8, '', 'დასრულება', 'date', 1, 'all', NULL, NULL, NULL, 70, '2026-01-06 10:43:55', 1),
(22, 5, 9, '', 'ხარჯების ცხრილი (თითო ხაზზე: კატეგორია | აღწერა | თანხა ₾)', 'textarea', 1, 'all', NULL, NULL, NULL, 10, '2026-01-06 10:43:55', 1),
(23, 5, 9, '', 'ჯამი (₾)', 'number', 0, 'all', NULL, NULL, NULL, 20, '2026-01-06 10:43:55', 1),
(24, 5, 12, '', 'განმცხადებლის ტიპი', 'select', 1, 'all', '[\"ფიზიკური პირი\",\"ორგანიზაცია\"]', NULL, NULL, 10, '2026-01-06 10:50:02', 1),
(25, 5, 12, '', 'სახელი', 'text', 1, 'all', NULL, NULL, NULL, 20, '2026-01-06 10:50:02', 1),
(26, 5, 12, '', 'გვარი', 'text', 1, 'all', NULL, NULL, NULL, 30, '2026-01-06 10:50:02', 1),
(27, 5, 12, '', 'პირადი ნომერი (11 ციფრი)', 'text', 1, 'all', NULL, NULL, NULL, 40, '2026-01-06 10:50:02', 1),
(28, 5, 12, '', 'ტელეფონი (+995...)', 'phone', 1, 'all', NULL, NULL, NULL, 50, '2026-01-06 10:50:02', 1),
(29, 5, 12, '', 'ელ.ფოსტა', 'email', 1, 'all', NULL, NULL, NULL, 60, '2026-01-06 10:50:02', 1),
(30, 5, 12, '', 'მისამართი/რაიონი', 'text', 0, 'all', NULL, NULL, NULL, 70, '2026-01-06 10:50:02', 1),
(31, 5, 13, '', 'პროექტის დასახელება', 'text', 1, 'all', NULL, NULL, NULL, 10, '2026-01-06 10:50:02', 1),
(32, 5, 13, '', 'მოკლე აღწერა', 'textarea', 1, 'all', NULL, NULL, NULL, 20, '2026-01-06 10:50:02', 1),
(33, 5, 13, '', 'სრული აღწერა', 'textarea', 1, 'all', NULL, NULL, NULL, 30, '2026-01-06 10:50:02', 1),
(34, 5, 13, '', 'მიზნები', 'textarea', 1, 'all', NULL, NULL, NULL, 40, '2026-01-06 10:50:02', 1),
(35, 5, 13, '', 'მოსალოდნელი შედეგები', 'textarea', 1, 'all', NULL, NULL, NULL, 50, '2026-01-06 10:50:02', 1),
(36, 5, 13, '', 'დასაწყისი', 'date', 1, 'all', NULL, NULL, NULL, 60, '2026-01-06 10:50:02', 1),
(37, 5, 13, '', 'დასრულება', 'date', 1, 'all', NULL, NULL, NULL, 70, '2026-01-06 10:50:02', 1),
(38, 5, 14, '', 'ბიუჯეტის ცხრილი', 'text', 1, 'all', NULL, NULL, NULL, 10, '2026-01-06 10:50:02', 1),
(39, 5, 17, '', 'ორგანიზაციის დასახელება', 'text', 1, 'org', NULL, NULL, NULL, 10, '2026-01-06 10:52:09', 1),
(40, 5, 17, '', 'საიდენტიფიკაციო კოდი', 'text', 1, 'org', NULL, NULL, NULL, 20, '2026-01-06 10:52:09', 1),
(41, 5, 17, '', 'მისამართი', 'text', 0, 'org', NULL, NULL, NULL, 30, '2026-01-06 10:52:09', 1),
(42, 5, 17, '', 'საკონტაქტო პირი', 'text', 1, 'org', NULL, NULL, NULL, 40, '2026-01-06 10:52:09', 1),
(43, 5, 17, '', 'ტელეფონი', 'phone', 1, 'org', NULL, NULL, NULL, 50, '2026-01-06 10:52:09', 1),
(44, 5, 17, '', 'ელფოსტა', 'email', 0, 'org', NULL, NULL, NULL, 60, '2026-01-06 10:52:09', 1),
(45, 5, 18, '', 'პროექტის დასახელება', 'text', 1, 'all', NULL, NULL, NULL, 10, '2026-01-06 10:52:10', 1),
(46, 5, 18, '', 'მიზანი', 'textarea', 1, 'all', NULL, NULL, NULL, 20, '2026-01-06 10:52:10', 1),
(47, 5, 18, '', 'სრულად აღწერა', 'textarea', 1, 'all', NULL, NULL, NULL, 30, '2026-01-06 10:52:10', 1),
(48, 5, 21, '', 'სახელი', 'text', 1, 'all', NULL, NULL, NULL, 10, '2026-01-06 10:54:43', 1),
(49, 5, 21, '', 'გვარი', 'text', 1, 'all', NULL, NULL, NULL, 20, '2026-01-06 10:54:43', 1),
(50, 5, 21, '', 'ტელეფონი', 'phone', 1, 'all', NULL, NULL, NULL, 30, '2026-01-06 10:54:43', 1),
(51, 5, 21, '', 'ელფოსტა', 'email', 0, 'all', NULL, NULL, NULL, 40, '2026-01-06 10:54:43', 1),
(52, 5, 22, '', 'ღონისძიების დასახელება', 'text', 1, 'all', NULL, NULL, NULL, 10, '2026-01-06 10:54:43', 1),
(53, 5, 22, '', 'თარიღი', 'date', 1, 'all', NULL, NULL, NULL, 20, '2026-01-06 10:54:43', 1),
(54, 5, 22, '', 'ადგილი', 'text', 1, 'all', NULL, NULL, NULL, 30, '2026-01-06 10:54:43', 1),
(55, 5, 22, '', 'მონაწილეთა რაოდენობა', 'number', 0, 'all', NULL, NULL, NULL, 40, '2026-01-06 10:54:43', 1),
(56, 5, 22, '', 'აღწერა', 'textarea', 1, 'all', NULL, NULL, NULL, 50, '2026-01-06 10:54:43', 1),
(57, 5, 23, '', 'სრულად ბიუჯეტი (ლარი)', 'number', 1, 'all', NULL, NULL, NULL, 10, '2026-01-06 10:54:43', 1),
(58, 5, 23, '', 'ძირითადი ხარჯები', 'textarea', 1, 'all', NULL, NULL, NULL, 20, '2026-01-06 10:54:43', 1),
(59, 5, 26, '', 'განმცხადებლის ტიპი', 'select', 1, 'all', '[\"ფიზიკური პირი\",\"ორგანიზაცია\"]', NULL, NULL, 10, '2026-01-06 10:55:02', 1),
(60, 5, 26, '', 'სახელი', 'text', 0, 'org', NULL, NULL, NULL, 20, '2026-01-06 10:55:02', 1),
(61, 5, 26, '', 'გვარი', 'text', 0, 'person', NULL, NULL, NULL, 30, '2026-01-06 10:55:02', 1),
(62, 5, 26, '', 'პირადი ნომერი (11 ციფრი)', 'text', 1, 'all', NULL, NULL, NULL, 40, '2026-01-06 10:55:02', 1),
(63, 5, 26, '', 'ტელეფონი (+995...)', 'phone', 1, 'all', NULL, NULL, NULL, 50, '2026-01-06 10:55:02', 1),
(64, 5, 26, '', 'ელ.ფოსტა', 'email', 1, 'all', NULL, NULL, NULL, 60, '2026-01-06 10:55:02', 1),
(65, 5, 26, '', 'მისამართი/რაიონი', 'text', 0, 'all', NULL, NULL, NULL, 70, '2026-01-06 10:55:02', 1),
(66, 5, 26, '', 'ორგანიზაციის/აიპის დასახელება (ორგანიზაციისთვის)', 'text', 0, 'all', NULL, NULL, NULL, 80, '2026-01-06 10:55:02', 1),
(67, 5, 26, '', 'საიდენტიფიკაციო კოდი (ორგანიზაციისთვის)', 'text', 0, 'all', NULL, NULL, NULL, 90, '2026-01-06 10:55:02', 1),
(68, 5, 26, '', 'წარმომადგენლის სახელი/გვარი (ორგანიზაციისთვის)', 'text', 0, 'all', NULL, NULL, NULL, 100, '2026-01-06 10:55:02', 1),
(69, 5, 27, '', 'პროექტის დასახელება', 'text', 1, 'all', NULL, NULL, NULL, 10, '2026-01-06 10:55:02', 1),
(70, 5, 27, '', 'მოკლე აღწერა', 'textarea', 0, 'org', NULL, NULL, NULL, 20, '2026-01-06 10:55:02', 1),
(71, 5, 27, '', 'სრული აღწერა', 'textarea', 1, 'all', NULL, NULL, NULL, 30, '2026-01-06 10:55:02', 1),
(72, 5, 27, '', 'მიზნები', 'textarea', 1, 'all', NULL, NULL, NULL, 40, '2026-01-06 10:55:02', 1),
(73, 5, 27, '', 'მოსალოდნელი შედეგები', 'textarea', 1, 'all', NULL, NULL, NULL, 50, '2026-01-06 10:55:02', 1),
(74, 5, 27, '', 'დასაწყისი', 'date', 1, 'all', NULL, NULL, NULL, 60, '2026-01-06 10:55:02', 1),
(75, 5, 27, '', 'დასრულება', 'date', 1, 'all', NULL, NULL, NULL, 70, '2026-01-06 10:55:02', 1),
(76, 5, 28, '', 'ბიუჯეტის ცხრილი', 'text', 0, 'all', NULL, NULL, NULL, 10, '2026-01-06 10:55:03', 1),
(77, 5, 26, '', 'პირადი ნომერი', 'text', 0, 'person', NULL, NULL, NULL, 110, '2026-01-06 17:35:02', 1),
(78, 5, 26, '', 'ტელეფონი', 'phone', 0, 'all', NULL, NULL, NULL, 120, '2026-01-06 17:35:02', 1),
(79, 5, 26, '', 'მისამართი', 'text', 0, 'all', NULL, NULL, NULL, 130, '2026-01-06 17:35:02', 1),
(80, 5, 26, '', 'ორგანიზაციის დასახელება', 'text', 0, 'org', NULL, NULL, NULL, 140, '2026-01-06 17:35:02', 1),
(81, 5, 26, '', 'საიდენტიფიკაციო კოდი', 'text', 0, 'org', NULL, NULL, NULL, 150, '2026-01-06 17:35:02', 1),
(82, 5, 26, '', 'წარმომადგენელი (სახელი/გვარი)', 'text', 0, 'org', NULL, NULL, NULL, 160, '2026-01-06 17:35:02', 1),
(83, 5, 28, '', 'ბიუჯეტი', 'text', 0, 'all', NULL, NULL, NULL, 20, '2026-01-06 17:35:02', 1),
(84, 5, 29, '', 'ფაილების ატვირთვა', 'file', 0, 'all', NULL, NULL, NULL, 10, '2026-01-06 17:35:02', 1),
(85, 5, 31, '', 'განმცხადებლის ტიპი', 'select', 0, 'all', '[\"person\",\"org\"]', NULL, NULL, 10, '2026-01-06 17:35:53', 1),
(86, 5, 31, '', 'სახელი', 'text', 0, 'person', NULL, NULL, NULL, 20, '2026-01-06 17:35:53', 1),
(87, 5, 31, '', 'გვარი', 'text', 0, 'person', NULL, NULL, NULL, 30, '2026-01-06 17:35:53', 1),
(88, 5, 31, '', 'პირადი ნომერი', 'text', 0, 'person', NULL, NULL, NULL, 40, '2026-01-06 17:35:53', 1),
(89, 5, 31, '', 'ტელეფონი', 'phone', 0, 'all', NULL, NULL, NULL, 50, '2026-01-06 17:35:53', 1),
(90, 5, 31, '', 'ელ.ფოსტა', 'email', 0, 'all', NULL, NULL, NULL, 60, '2026-01-06 17:35:53', 1),
(91, 5, 31, '', 'მისამართი', 'text', 0, 'all', NULL, NULL, NULL, 70, '2026-01-06 17:35:53', 1),
(92, 5, 31, '', 'ორგანიზაციის დასახელება', 'text', 0, 'org', NULL, NULL, NULL, 80, '2026-01-06 17:35:53', 1),
(93, 5, 31, '', 'საიდენტიფიკაციო კოდი', 'text', 0, 'org', NULL, NULL, NULL, 90, '2026-01-06 17:35:53', 1),
(94, 5, 31, '', 'წარმომადგენელი (სახელი/გვარი)', 'text', 0, 'org', NULL, NULL, NULL, 100, '2026-01-06 17:35:53', 1),
(95, 5, 32, '', 'პროექტის დასახელება', 'text', 0, 'all', NULL, NULL, NULL, 10, '2026-01-06 17:35:53', 1),
(96, 5, 32, '', 'მოკლე აღწერა', 'text', 0, 'all', NULL, NULL, NULL, 20, '2026-01-06 17:35:53', 1),
(97, 5, 32, '', 'სრული აღწერა', 'textarea', 0, 'all', NULL, NULL, NULL, 30, '2026-01-06 17:35:53', 1),
(98, 5, 32, '', 'მიზნები', 'textarea', 0, 'all', NULL, NULL, NULL, 40, '2026-01-06 17:35:53', 1),
(99, 5, 32, '', 'მოსალოდნელი შედეგები', 'textarea', 0, 'all', NULL, NULL, NULL, 50, '2026-01-06 17:35:53', 1),
(100, 5, 32, '', 'დასაწყისი', 'date', 0, 'all', NULL, NULL, NULL, 60, '2026-01-06 17:35:53', 1),
(101, 5, 32, '', 'დასრულება', 'date', 0, 'all', NULL, NULL, NULL, 70, '2026-01-06 17:35:53', 1),
(102, 5, 33, '', 'ბიუჯეტი', 'text', 0, 'all', NULL, NULL, NULL, 10, '2026-01-06 17:35:53', 1),
(103, 5, 34, '', 'ფაილების ატვირთვა', 'file', 0, 'all', NULL, NULL, NULL, 10, '2026-01-06 17:35:54', 1),
(104, 6, 36, '', 'განმცხადებლის ტიპი', 'select', 0, 'all', '[\"person\",\"org\"]', NULL, NULL, 10, '2026-01-07 13:16:52', 1),
(105, 6, 36, '', 'სახელი', 'text', 0, 'person', NULL, NULL, NULL, 20, '2026-01-07 13:16:52', 1),
(106, 6, 36, '', 'გვარი', 'text', 0, 'person', NULL, NULL, NULL, 30, '2026-01-07 13:16:52', 1),
(107, 6, 36, '', 'პირადი ნომერი', 'text', 0, 'person', NULL, NULL, NULL, 40, '2026-01-07 13:16:52', 1),
(108, 6, 36, '', 'ტელეფონი', 'phone', 0, 'all', NULL, NULL, NULL, 50, '2026-01-07 13:16:52', 1),
(109, 6, 36, '', 'ელ.ფოსტა', 'email', 0, 'all', NULL, NULL, NULL, 60, '2026-01-07 13:16:52', 1),
(110, 6, 36, '', 'მისამართი', 'text', 0, 'all', NULL, NULL, NULL, 70, '2026-01-07 13:16:52', 1),
(111, 6, 36, '', 'ორგანიზაციის დასახელება', 'text', 0, 'org', NULL, NULL, NULL, 80, '2026-01-07 13:16:52', 1),
(112, 6, 36, '', 'საიდენტიფიკაციო კოდი', 'text', 0, 'org', NULL, NULL, NULL, 90, '2026-01-07 13:16:52', 1),
(113, 6, 36, '', 'წარმომადგენელი (სახელი/გვარი)', 'text', 0, 'org', NULL, NULL, NULL, 100, '2026-01-07 13:16:52', 1),
(114, 6, 37, '', 'პროექტის დასახელება', 'text', 0, 'all', NULL, NULL, NULL, 10, '2026-01-07 13:16:52', 1),
(115, 6, 37, '', 'მოკლე აღწერა', 'text', 0, 'all', NULL, NULL, NULL, 20, '2026-01-07 13:16:52', 1),
(116, 6, 37, '', 'სრული აღწერა', 'textarea', 0, 'all', NULL, NULL, NULL, 30, '2026-01-07 13:16:52', 1),
(117, 6, 37, '', 'მიზნები', 'textarea', 0, 'all', NULL, NULL, NULL, 40, '2026-01-07 13:16:52', 1),
(118, 6, 37, '', 'მოსალოდნელი შედეგები', 'textarea', 0, 'all', NULL, NULL, NULL, 50, '2026-01-07 13:16:52', 1),
(119, 6, 37, '', 'დასაწყისი', 'date', 0, 'all', NULL, NULL, NULL, 60, '2026-01-07 13:16:52', 1),
(120, 6, 37, '', 'დასრულება', 'date', 0, 'all', NULL, NULL, NULL, 70, '2026-01-07 13:16:52', 1),
(121, 6, 38, '', 'ბიუჯეტი', 'text', 0, 'all', NULL, NULL, NULL, 10, '2026-01-07 13:16:52', 1),
(122, 6, 39, '', 'ფაილების ატვირთვა', 'file', 0, 'all', NULL, NULL, NULL, 10, '2026-01-07 13:16:53', 1),
(124, 3, 41, '', 'განმცხადებლის ტიპი', 'select', 1, 'all', '[\"person\",\"org\"]', NULL, NULL, 1, '2026-01-12 13:30:41', 1),
(125, 3, 41, '', 'სახელი', 'text', 1, 'person', NULL, NULL, NULL, 2, '2026-01-12 13:30:41', 1),
(126, 3, 41, '', 'გვარი', 'text', 1, 'person', NULL, NULL, NULL, 3, '2026-01-12 13:30:41', 1),
(127, 3, 41, '', 'პირადი ნომერი', 'text', 1, 'person', NULL, NULL, NULL, 4, '2026-01-12 13:30:41', 1),
(128, 3, 41, '', 'ტელეფონი', 'phone', 1, 'all', NULL, NULL, NULL, 5, '2026-01-12 13:30:41', 1),
(129, 3, 41, '', 'ელ.ფოსტა', 'email', 1, 'all', NULL, NULL, NULL, 6, '2026-01-12 13:30:41', 1),
(130, 3, 41, '', 'მისამართი', 'text', 0, 'all', NULL, NULL, NULL, 7, '2026-01-12 13:30:41', 1),
(131, 3, 41, '', 'ორგანიზაციის დასახელება', 'text', 1, 'org', NULL, NULL, NULL, 8, '2026-01-12 13:30:41', 1),
(132, 3, 41, '', 'საიდენტიფიკაციო კოდი', 'text', 1, 'org', NULL, NULL, NULL, 9, '2026-01-12 13:30:42', 1),
(133, 3, 41, '', 'წარმომადგენელი (სახელი/გვარი)', 'text', 1, 'org', NULL, NULL, NULL, 10, '2026-01-12 13:30:42', 1),
(134, 3, 42, '', 'პროექტის დასახელება', 'text', 1, 'all', NULL, NULL, NULL, 1, '2026-01-12 13:30:42', 1),
(135, 3, 42, '', 'მოკლე აღწერა', 'text', 1, 'all', NULL, NULL, NULL, 2, '2026-01-12 13:30:42', 1),
(136, 3, 42, '', 'სრული აღწერა', 'textarea', 1, 'all', NULL, NULL, NULL, 3, '2026-01-12 13:30:42', 1),
(137, 3, 42, '', 'მიზნები', 'textarea', 1, 'all', NULL, NULL, NULL, 4, '2026-01-12 13:30:42', 1),
(138, 3, 42, '', 'მოსალოდნელი შედეგები', 'textarea', 1, 'all', NULL, NULL, NULL, 5, '2026-01-12 13:30:42', 1),
(139, 3, 42, '', 'დასაწყისი', 'date', 1, 'all', NULL, NULL, NULL, 6, '2026-01-12 13:30:42', 1),
(140, 3, 42, '', 'დასრულება', 'date', 1, 'all', NULL, NULL, NULL, 7, '2026-01-12 13:30:42', 1),
(141, 3, 43, '', 'ბიუჯეტი', 'budget_table', 1, 'all', '{\"currency\":\"₾\",\"min_rows\":1,\"columns\":[{\"key\":\"cat\",\"label\":\"კატეგორია\",\"type\":\"text\",\"required\":true,\"placeholder\":\"მაგ: აღჭურვილობა\"},{\"key\":\"desc\",\"label\":\"აღწერა\",\"type\":\"text\",\"required\":true,\"placeholder\":\"დანიშნულება\"},{\"key\":\"amount\",\"label\":\"თანხა (₾)\",\"type\":\"number\",\"required\":true,\"min\":0}]}', NULL, NULL, 1, '2026-01-12 13:30:42', 1),
(142, 3, 44, '', 'ფაილების ატვირთვა', 'file', 0, 'all', NULL, NULL, NULL, 1, '2026-01-12 13:30:42', 1);

-- --------------------------------------------------------

--
-- Table structure for table `grant_file_requirements`
--

CREATE TABLE `grant_file_requirements` (
  `id` int UNSIGNED NOT NULL,
  `grant_id` int UNSIGNED NOT NULL,
  `name` varchar(190) COLLATE utf8mb4_general_ci NOT NULL,
  `is_required` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int NOT NULL DEFAULT '1',
  `is_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `grant_form_fields`
--

CREATE TABLE `grant_form_fields` (
  `id` int UNSIGNED NOT NULL,
  `grant_id` int UNSIGNED NOT NULL,
  `step_id` int UNSIGNED NOT NULL,
  `field_key` varchar(64) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'field',
  `label` varchar(190) COLLATE utf8mb4_general_ci NOT NULL,
  `type` varchar(32) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'text',
  `options_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `is_required` tinyint(1) NOT NULL DEFAULT '0',
  `show_for` varchar(16) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'all',
  `sort_order` int NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ;

--
-- Dumping data for table `grant_form_fields`
--

INSERT INTO `grant_form_fields` (`id`, `grant_id`, `step_id`, `field_key`, `label`, `type`, `options_json`, `is_required`, `show_for`, `sort_order`, `created_at`) VALUES
(1, 4, 13, 'field', 'განმცხადებლის ტიპი', 'select', '[\"ფიზიკური პირი\",\"იურიდიული პირი\\/აიპი\"]', 1, 'all', 1, '2026-01-04 21:15:13'),
(2, 4, 13, 'field', 'სახელი', 'text', NULL, 1, 'person', 2, '2026-01-04 21:15:13'),
(3, 4, 13, 'field', 'გვარი', 'text', NULL, 1, 'person', 3, '2026-01-04 21:15:13'),
(4, 4, 13, 'field', 'პირადი ნომერი', 'text', NULL, 1, 'person', 4, '2026-01-04 21:15:13'),
(5, 4, 13, 'field', 'კომპანიის/აიპის დასახელება', 'text', NULL, 1, 'company', 5, '2026-01-04 21:15:13'),
(6, 4, 13, 'field', 'საიდენტიფიკაციო კოდი', 'text', NULL, 1, 'company', 6, '2026-01-04 21:15:13'),
(7, 4, 13, 'field', 'ელ.ფოსტა', 'email', NULL, 1, 'all', 7, '2026-01-04 21:15:13'),
(8, 4, 13, 'field', 'ტელეფონი', 'phone', NULL, 1, 'all', 8, '2026-01-04 21:15:13'),
(10, 2, 19, 'field', 'განმცხადებლის ტიპი', 'select', '[\"ფიზიკური პირი\",\"იურიდიული პირი\\/აიპი\"]', 1, 'all', 1, '2026-01-05 00:49:07'),
(11, 2, 19, 'field', 'სახელი', 'text', NULL, 1, 'person', 2, '2026-01-05 00:49:07'),
(12, 2, 19, 'field', 'გვარი', 'text', NULL, 1, 'person', 3, '2026-01-05 00:49:07'),
(13, 2, 19, 'field', 'პირადი ნომერი', 'text', NULL, 1, 'person', 4, '2026-01-05 00:49:07'),
(14, 2, 19, 'field', 'კომპანიის/აიპის დასახელება', 'text', NULL, 1, 'company', 5, '2026-01-05 00:49:07'),
(15, 2, 19, 'field', 'საიდენტიფიკაციო კოდი', 'text', NULL, 1, 'company', 6, '2026-01-05 00:49:07'),
(16, 2, 19, 'field', 'ელ.ფოსტა', 'email', NULL, 1, 'all', 7, '2026-01-05 00:49:07'),
(17, 2, 19, 'field', 'ტელეფონი', 'phone', NULL, 1, 'all', 8, '2026-01-05 00:49:07'),
(18, 2, 19, 'field', 'მისამართი', 'text', NULL, 0, 'all', 9, '2026-01-05 00:49:07');

-- --------------------------------------------------------

--
-- Table structure for table `grant_form_steps`
--

CREATE TABLE `grant_form_steps` (
  `id` int UNSIGNED NOT NULL,
  `grant_id` int UNSIGNED NOT NULL,
  `step_key` varchar(64) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'custom',
  `name` varchar(190) COLLATE utf8mb4_general_ci NOT NULL,
  `sort_order` int NOT NULL DEFAULT '1',
  `is_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `grant_form_steps`
--

INSERT INTO `grant_form_steps` (`id`, `grant_id`, `step_key`, `name`, `sort_order`, `is_enabled`, `created_at`) VALUES
(13, 4, 'applicant', 'განმცხადებელი', 1, 1, '2026-01-04 21:15:13'),
(14, 4, 'project', 'პროექტი', 2, 1, '2026-01-04 21:15:13'),
(15, 4, 'budget', 'ბიუჯეტი', 3, 1, '2026-01-04 21:15:13'),
(16, 4, 'plan', 'სამოქმედო გეგმა', 4, 1, '2026-01-04 21:15:13'),
(17, 4, 'files', 'ფაილები', 5, 1, '2026-01-04 21:15:13'),
(18, 4, 'clarification', 'დაზუსტება', 6, 1, '2026-01-04 21:15:13'),
(19, 2, 'applicant', 'განმცხადებელი', 1, 0, '2026-01-05 00:49:07'),
(20, 2, 'project', 'პროექტი', 2, 0, '2026-01-05 00:49:07'),
(21, 2, 'budget', 'ბიუჯეტი', 3, 0, '2026-01-05 00:49:07'),
(22, 2, 'plan', 'სამოქმედო გეგმა', 4, 0, '2026-01-05 00:49:07'),
(23, 2, 'files', 'ფაილები', 5, 0, '2026-01-05 00:49:07'),
(24, 2, 'clarification', 'დაზუსტება', 6, 0, '2026-01-05 00:49:07');

-- --------------------------------------------------------

--
-- Table structure for table `grant_requirements`
--

CREATE TABLE `grant_requirements` (
  `id` int NOT NULL,
  `grant_id` int NOT NULL,
  `name` varchar(190) COLLATE utf8mb4_general_ci NOT NULL,
  `is_required` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `grant_requirements`
--

INSERT INTO `grant_requirements` (`id`, `grant_id`, `name`, `is_required`, `sort_order`, `created_at`) VALUES
(12, 5, 'პროექტის აღწერა (PDF)', 1, 10, '2026-01-06 10:55:03'),
(13, 5, 'ბიუჯეტი (PDF ან XLSX)', 1, 20, '2026-01-06 10:55:03'),
(14, 5, 'განმცხადებლის დოკუმენტი (ID/რეგისტრაცია)', 1, 30, '2026-01-06 10:55:03');

-- --------------------------------------------------------

--
-- Table structure for table `grant_steps`
--

CREATE TABLE `grant_steps` (
  `id` int NOT NULL,
  `grant_id` int NOT NULL,
  `name` varchar(190) COLLATE utf8mb4_general_ci NOT NULL,
  `step_key` varchar(64) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `sort_order` int NOT NULL DEFAULT '0',
  `is_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `grant_steps`
--

INSERT INTO `grant_steps` (`id`, `grant_id`, `name`, `step_key`, `sort_order`, `is_enabled`, `created_at`) VALUES
(31, 5, 'განმცხადებელი', 'განმცხადებელი', 10, 1, '2026-01-06 17:35:53'),
(32, 5, 'პროექტი', 'პროექტი', 20, 1, '2026-01-06 17:35:53'),
(33, 5, 'ბიუჯეტი', 'ბიუჯეტი', 40, 1, '2026-01-06 17:35:53'),
(34, 5, 'ფაილები', 'ფაილები', 41, 1, '2026-01-06 17:35:53'),
(36, 6, 'განმცხადებელი', 'განმცხადებელი', 10, 1, '2026-01-07 13:16:52'),
(37, 6, 'პროექტი', 'პროექტი', 20, 1, '2026-01-07 13:16:52'),
(38, 6, 'ბიუჯეტი', 'ბიუჯეტი', 30, 1, '2026-01-07 13:16:52'),
(39, 6, 'ფაილები', 'ფაილები', 40, 1, '2026-01-07 13:16:52'),
(40, 5, 'მთავარი', 'მთავარი', 30, 1, '2026-01-12 13:29:41'),
(41, 3, 'განმცხადებელი', 'განმცხადებელი', 1, 1, '2026-01-12 13:30:41'),
(42, 3, 'პროექტი', 'პროექტი', 2, 1, '2026-01-12 13:30:41'),
(43, 3, 'ბიუჯეტი', 'ბიუჯეტი', 3, 1, '2026-01-12 13:30:41'),
(44, 3, 'ფაილები', 'ფაილები', 4, 1, '2026-01-12 13:30:41');

-- --------------------------------------------------------

--
-- Table structure for table `grant_uploads`
--

CREATE TABLE `grant_uploads` (
  `id` int UNSIGNED NOT NULL,
  `application_id` int UNSIGNED NOT NULL,
  `grant_id` int UNSIGNED NOT NULL,
  `requirement_id` int UNSIGNED DEFAULT NULL,
  `field_id` int UNSIGNED DEFAULT NULL,
  `field_name` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `original_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `stored_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_path` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `size_bytes` bigint UNSIGNED NOT NULL DEFAULT '0',
  `mime_type` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `grant_uploads`
--

INSERT INTO `grant_uploads` (`id`, `application_id`, `grant_id`, `requirement_id`, `field_id`, `field_name`, `original_name`, `stored_name`, `file_path`, `size_bytes`, `mime_type`, `deleted_at`, `created_at`) VALUES
(1, 6, 5, NULL, NULL, 'other_files', '612996704_907436831851807_455662120956458331_n.jpg', 'up_20260111_221741_9fdc127e8c0b8f78.jpg', 'uploads/grants/apps/6/up_20260111_221741_9fdc127e8c0b8f78.jpg', 232066, 'image/jpeg', NULL, '2026-01-12 01:17:41');

-- --------------------------------------------------------

--
-- Table structure for table `members`
--

CREATE TABLE `members` (
  `id` int NOT NULL,
  `pid` varchar(64) COLLATE utf8mb4_general_ci NOT NULL,
  `first_name` varchar(120) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `last_name` varchar(120) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `phone` varchar(64) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(190) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `address` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `extra_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  `age` int DEFAULT NULL,
  `university` varchar(190) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `faculty` varchar(190) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `course` varchar(64) COLLATE utf8mb4_general_ci DEFAULT NULL
) ;

--
-- Dumping data for table `members`
--

INSERT INTO `members` (`id`, `pid`, `first_name`, `last_name`, `birth_date`, `phone`, `email`, `address`, `extra_json`, `created_at`, `updated_at`, `age`, `university`, `faculty`, `course`) VALUES
(1, '01010101010', 'ნიკა', 'კალანდაძე', '2006-05-12', '+995555123456', 'nika@mail.com', 'Batumi', NULL, '2026-01-01 20:20:48', NULL, 19, 'ბათუმის სახელმწიფო უნივერსიტეტი', 'ტურიზმი', '2'),
(2, '010101010101', 'TestName', 'TestLast', '2006-05-12', '+995555111222', 'test@mail.com', 'Batumi', NULL, '2026-01-01 23:01:42', NULL, NULL, NULL, NULL, NULL),
(4, '0101010101012', 'ნიკა', 'კალანდაძე', NULL, '+995555123456', 'nika@mail.com', NULL, NULL, '2026-01-02 22:44:19', NULL, 19, 'ბათუმის სახელმწიფო უნივერსიტეტი', 'ტურიზმი', '2');

-- --------------------------------------------------------

--
-- Table structure for table `news`
--

CREATE TABLE `news` (
  `id` int NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `title_en` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `category` varchar(60) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `slug` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `body` longtext COLLATE utf8mb4_general_ci,
  `body_en` mediumtext COLLATE utf8mb4_general_ci,
  `link` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `image_path` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `published_at` datetime DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `news`
--

INSERT INTO `news` (`id`, `title`, `title_en`, `category`, `slug`, `body`, `body_en`, `link`, `image_path`, `published_at`, `sort_order`, `is_active`, `created_at`) VALUES
(2, 'ახალგაზრდული მრჩეველთა საბჭოების 2025 წლის ფორუმი გაიმართა', NULL, NULL, 'ახალგაზრდული-მრჩეველთა-საბჭოების-2025-წლის-ფორუმი-გაიმართა', '🔷 სსიპ ახალგაზრდობის სააგენტოს ორგანიზებითა და საქართველოს ახალგაზრდული მრჩეველთა საბჭოების ასოციაციასთან თანამშრომლობით, ახალგაზრდული მრჩეველთა საბჭოების 2025 წლის ფორუმი გაიმართა.\r\n🔷 ღონისძიების ფარგლებში, საქართველოს განათლების, მეცნიერებისა და ახალგაზრდობის მინისტრმა გივი მიქანაძემ სიტყვით გამოსვლისას ახალგაზრდების მხარდაჭერის კუთხით სამინისტროს მიერ განხორციელებულ პროექტებზე ისაუბრა. \r\n🔷 მინისტრმა, ახალგაზრდობის სააგენტოს ხელმძღვანელის მოვალეობის შემსრულებელ ვახტანგ ბააკაშვილთან და საქართველოს ახალგაზრდული მრჩეველთა საბჭოების ასოციაციის დამფუძნებლებთან - ეკატერინე გვარამიასა და ილია ფიფიასთან ერთად, გამარჯვებული მრჩეველთა საბჭოების წევრი ახალგაზრდები დააჯილდოვა.\r\n🏆  ღონისძიებაზე გამარჯვებულები ოთხ საკონკურსო კატეგორიაში გამოვლინდნენ:\r\n🔷  „ეფექტიანი პროექტების მქონე საბჭო“ - დმანისის ახალგაზრდული სათათბირო;\r\n🔷  „საუკეთესო თანამშრომლობის პრაქტიკის მქონე საბჭო“ - ხაშურის ახალგაზრდულ მრჩეველთა საბჭო;\r\n🔷  „კრეატიულობა“ - ჩხოროწყუს ახალგაზრდული მრჩეველთა საბჭო;\r\n🔷  2025 წლის საუკეთესო ახალგაზრდული მრჩეველთა საბჭო გახდა - ქუთაისის ახალგაზრდული მრჩეველთა საბჭო.\r\n🧍‍♀️🧍‍♂️ფორუმში 500-მდე ახალგაზრდამ მიიღო მონაწილეობა და მის მიზანს წარმოადგენდა - საქართველოს მუნიციპალიტეტებში არსებული ახალგაზრდული საბჭოების 2025 წლის საქმიანობების შეჯამება; გადაწყვეტილების მიღების პროცესში ახალგაზრდების ჩართულობის ხელშეწყობა; რეგიონებში მცხოვრები ახალგაზრდა ლიდერების შესაძლებლობების განვითარება; ახალგაზრდული მრჩეველთა საბჭოების, მუნიციპალიტეტების წარმომადგენლებისა და ახალგაზრდობის საკითხებზე მომუშავე ცენტრალური მთავრობის უწყებებს შორის თანამშრომლობის გაძლიერება; გრძელვადიანი პარტნიორობისა და ერთობლივი ინიციატივების შემუშავება.', NULL, NULL, 'uploads/news/news_f7592c3c30339842.jpg', NULL, 0, 1, '2025-12-30 15:45:40'),
(3, 'თურქეთის რესპუბლიკის ახალგაზრდობის და სპორტის სამინისტროს შორის თანამშრომლობის მემორანდუმი გაფორმდა', NULL, NULL, 'თურქეთის-რესპუბლიკის-ახალგაზრდობის-და-სპორტის-სამინისტროს-შორის-თანამშრომლობის-მემორანდუმი-გაფორმდა', '🔷 მიმდინარე წლის 4 სექტემბერს საქართველოს განათლების, მეცნიერებისა და ახალგაზრდობის სამინისტროსა და თურქეთის რესპუბლიკის ახალგაზრდობის და სპორტის სამინისტროს შორის თანამშრომლობის მემორანდუმი გაფორმდა, რომლის ფარგლებშიც, 9-12 დეკემბერს ახალგაზრდობის სააგენტომ თურქეთის რესპუბლიკის ახალგაზრდობისა და სპორტის სამინისტროს წარმომადგენლებს უმასპინძლა.\r\n\r\n🔷 ვიზიტის ფარგლებში შეხვედრა გაიმართა ახალგაზრდობის სააგენტოს ხელმძღვანელის მოვალეობის შემსრულებელ ვახტანგ ბააკაშვილთან და სააგენტოს დეპარტამენტების უფროსებთან, რომლებმაც დარგობრივი მიმართულებების მიხედვით პრეზენტაციები წარადგინეს.\r\n\r\n🔷 ასევე, შეხვედრა გაიმართა თბილისის საკრებულოს სპორტისა და ახალგაზრდულ საქმეთა კომისიის თავმჯდომარესთან და წევრებთან. გარდა ამისა, სტუმრებმა, ვიზიტის ფარგლებში დაათვალიერეს დიდი დიღმის მრავალფუნქციური საზოგადოებრივი ცენტრი და თბილისის მერიის კულტურის, განათლების, სპორტისა და ახალგაზრდულ საქმეთა საქალაქო სამსახურის წარმომადგენლებს შეხვდნენ. ასევე, კახეთში საგრანტო კონკურსების შემაჯამებელ ფორუმს დაესწრნენ, რომელსაც ახალგაზრდული ორგანიზაციებისა და საინიციატივო ჯგუფების 100-მდე წარმომადგენელი ესწრებოდა.\r\n\r\n✅ ვიზიტის ფარგლებში მიღწეული შედეგები, დადებითად აისახება და ხელს შეუწყობს ორ ქვეყანას შორის ახალგაზრდულ სფეროში არსებულ თანამშრომლობას', NULL, NULL, 'uploads/news/news_a65ada70b3be4dc0.jpg', NULL, 0, 1, '2025-12-30 15:51:54'),
(4, 'ახალგაზრდობის სააგენტოს ორგანიზებით, 13-14 დეკემბერს, თბილისში გაეროს მოდელირების კონფერენცია (Model United Nations) გაიმართა.', NULL, NULL, 'ახალგაზრდობის-სააგენტოს-ორგანიზებით-13-14-დეკემბერს-თბილისში-გაეროს-მოდელირების-კონფერენცია-model-united-nations-გაიმართა', '🔷 ახალგაზრდობის სააგენტოს ორგანიზებით, 13-14 დეკემბერს, თბილისში გაეროს მოდელირების კონფერენცია (Model United Nations) გაიმართა. \r\n🔷 შემაჯამებელ ღონისძიებას კონფერენციის მონაწილე 150-მდე ახალგაზრდასთან ერთად, ახალგაზრდობის სააგენტოს ხელმძღვანელის მოადგილეები - ანა ბუხრაშვილი და ანზორ მეგრელიშვილი და გაეროს მოსახლეობის ფონდის საქართველოს ოფისის (UNFPA) პროგრამის ანალიტიკოსი ნატალია ზაქარეიშვილი ესწრებოდნენ.\r\n✅ პროექტის ფარგლებში გამოვლინდა სამი გამარჯვებული დელეგატი, რომლებიც \r\nსრული დაფინანსებით ამერიკის შეერთებულ შტატებში გაემგზავრებიან და გაეროს მოდელირების კონფერენციაში მიიღებენ მონაწილეობას.\r\n✅ კონფერენცია დაფუძნებული იყო გაეროს სამუშაო შეხვედრების ფორმატზე, რომელიც წარმოადგენს საგანმანათლებლო სიმულაციას და მონაწილეებს, როგორც გაეროს წევრი ქვეყნების დელეგატების\r\nწარმომადგენლებს, შესაძლებლობა ჰქონდათ განეხილათ საერთაშორისო მნიშვნელობის საკითხები.\r\n🔷 ფორმატის ფარგლებში, წარმოდგენილი იყო სამი კომიტეტი: გაეროს გენერალური ასამბლეა, გაეროს უშიშროების საბჭო და გაეროს ქალთა ორგანიზაცია. ორი დღის განმავლობაში კომიტეტებმა იმსჯელეს და შეიმუშავეს რეზოლუციები, რომლებიც ღონისძიების შემაჯამებელ ნაწილში წარადგინეს.', NULL, NULL, 'uploads/news/news_fca4e1e6ae2319d5.jpg', NULL, 0, 1, '2025-12-30 16:03:27'),
(5, 'საქართველოს სტუდენტური პარლამენტი და მთავრობა', NULL, NULL, 'საქართველოს-სტუდენტური-პარლამენტი-და-მთავრობა', 'lorem ipsum lorem ipsum lorem ipsum lorem ipsum lorem ipsum', NULL, NULL, 'uploads/news/news_734a154f445ca6f6.jpg', NULL, 0, 1, '2025-12-30 18:29:29'),
(6, 'ახალგაზრდობის სააგენტო - სიახლეების', 'Youth Agency - NEWS', NULL, 'ახალგაზრდობის-სააგენტო-სიახლეების', '🔷 ახალგაზრდობის სააგენტოს ორგანიზებით, კახეთში საგრანტო კონკურსების შემაჯამებელი ფორუმი გაიმართა.\r\n🔷 ღონისძიების ფარგლებში, ფორუმის მონაწილეებს სიტყვით მიმართეს ახალგაზრდობის სააგენტოს ხელმძღვანელის მოვალეობის შემსრულებელმა ვახტანგ ბააკაშვილმა და  სახელმწიფო გრანტის მართვის სააგენტოს თავმჯდომარემ თამარ ზოდელავამ. მომხსენებლებმა ახალგაზრდობის სააგენტოს სამომავლო გეგმებზე და სახელმწიფო გრანტის მართვის სააგენტოს მიმდინარე და 2026 წელს დაგეგმილ შესაძლებლობებზე ისაუბრეს.\r\n✅ ფორუმს ახალგაზრდული ორგანიზაციებისა და საინიციატივო ჯგუფების 100-მდე წარმომადგენელი დაესწრო, რომელთა პროექტებიც ახალგაზრდობის სააგენტოს საგრანტო კონკურსის ფარგლებში დაფინანსდა. ღონისძიების მიზანს წარმოადგენდა ახალგაზრდული ორგანიზაციებისა და საინიციატივო ჯგუფების წარმომადგენლების გაძლიერება და გამოცდილების გაზიარების ხელშეწყობა.\r\n🔷 ასევე, ღონისძიების დასკვნით დღეს, ფორუმის მონაწილეებმა წარმოადგინეს პრეზენტაციები განხორციელებული პროექტების შესახებ და საუკეთესო პრაქტიკები ერთმანეთს გაუზიარეს.', '🔷 A summary forum of grant competitions was held in Kakheti, organized by the Youth Agency.\r\n\r\n🔷 As part of the event, the forum participants were addressed by Vakhtang Baakashvili, Acting Head of the Youth Agency, and Tamar Zodelava, Chairperson of the State Grants Management Agency. The speakers discussed the Youth Agency’s future plans as well as the current opportunities and those planned for 2026 by the State Grants Management Agency.\r\n\r\n✅ The forum was attended by up to 100 representatives of youth organizations and initiative groups whose projects were funded within the framework of the Youth Agency’s grant competition. The aim of the event was to strengthen representatives of youth organizations and initiative groups and to promote the sharing of experience.\r\n\r\n🔷 On the final day of the event, forum participants presented their implemented projects and shared best practices with one another.', NULL, 'uploads/news/news_cf2ef4c36710c72f.jpg', NULL, 0, 1, '2025-12-30 18:29:55');

-- --------------------------------------------------------

--
-- Table structure for table `news_images`
--

CREATE TABLE `news_images` (
  `id` int NOT NULL,
  `news_id` int NOT NULL,
  `image_path` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `news_images`
--

INSERT INTO `news_images` (`id`, `news_id`, `image_path`, `sort_order`, `created_at`) VALUES
(2, 4, 'uploads/news_gallery/gallery_35de8bf19a652069.jpg', 0, '2025-12-30 21:24:57'),
(3, 4, 'uploads/news_gallery/gallery_665d8331f347c161.jpg', 1, '2025-12-30 21:24:57'),
(4, 4, 'uploads/news_gallery/gallery_52e34eedd429d2c8.jpg', 2, '2025-12-30 21:24:57'),
(5, 4, 'uploads/news_gallery/gallery_0dd56b7476e422a4.jpg', 3, '2025-12-30 21:24:57'),
(6, 4, 'uploads/news_gallery/gallery_2a8f40fdde9afc44.jpg', 4, '2025-12-30 21:24:57'),
(7, 4, 'uploads/news_gallery/gallery_67a9cc44c28df8a0.jpg', 5, '2025-12-30 21:24:57'),
(8, 4, 'uploads/news_gallery/gallery_36bbe19b35e8c3a8.jpg', 6, '2025-12-30 21:24:57'),
(9, 4, 'uploads/news_gallery/gallery_5c0cbb8706766c88.jpg', 7, '2025-12-30 21:24:57'),
(10, 3, 'uploads/news_gallery/gallery_77c16583b685386f.jpg', 0, '2025-12-30 21:26:48'),
(11, 3, 'uploads/news_gallery/gallery_3896b8b651417019.jpg', 1, '2025-12-30 21:26:48'),
(12, 3, 'uploads/news_gallery/gallery_e3618069a430997e.jpg', 2, '2025-12-30 21:26:48'),
(13, 3, 'uploads/news_gallery/gallery_5c7b79dce71b2201.jpg', 3, '2025-12-30 21:26:48'),
(14, 3, 'uploads/news_gallery/gallery_9b0963c94a4956ca.jpg', 4, '2025-12-30 21:26:48'),
(15, 3, 'uploads/news_gallery/gallery_b2aeaa6209ae7ebe.jpg', 5, '2025-12-30 21:26:48');

-- --------------------------------------------------------

--
-- Table structure for table `persons`
--

CREATE TABLE `persons` (
  `personal_id` varchar(32) COLLATE utf8mb4_general_ci NOT NULL,
  `first_name` varchar(120) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `last_name` varchar(120) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `phone` varchar(60) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(120) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `birthdate` varchar(40) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `address` varchar(190) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `key` varchar(64) COLLATE utf8mb4_general_ci NOT NULL,
  `value` text COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`key`, `value`) VALUES
('autoplay_ms', '4500'),
('camp_repeat_lock_days', '30');

-- --------------------------------------------------------

--
-- Table structure for table `slides`
--

CREATE TABLE `slides` (
  `id` int UNSIGNED NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `link` varchar(1024) COLLATE utf8mb4_general_ci NOT NULL DEFAULT '',
  `image_path` varchar(1024) COLLATE utf8mb4_general_ci NOT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `slides`
--

INSERT INTO `slides` (`id`, `title`, `link`, `image_path`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'main', '', 'uploads/slides/slide_829a3e3c054c080b.jpg', 0, 0, '2025-12-30 13:18:52', '2025-12-30 13:19:01'),
(2, 'main', 'sads', 'uploads/slides/slide_5e32eee700f90e6b.jpg', 0, 0, '2025-12-30 13:19:43', '2025-12-30 21:20:30'),
(3, 'sa', '', 'uploads/slides/slide_66ba8a7c32bdb788.jpg', 0, 0, '2025-12-30 13:25:37', '2025-12-30 21:28:34'),
(4, 's', '', 'uploads/slides/slide_02032c5adb9c5357.jpg', 0, 0, '2025-12-30 13:40:14', '2025-12-30 21:28:37'),
(5, '', '', 'uploads/slides/slide_0dfc8766667bd269.jpg', 0, 0, '2025-12-30 13:52:48', '2026-01-13 09:41:28'),
(6, 'text', '#', 'uploads/slides/slide_169063b10e635b01.jpg', 0, 1, '2025-12-30 15:04:23', '2026-01-13 09:42:03'),
(7, '', '', 'uploads/slides/slide_8323a9360cddb820.jpg', 0, 0, '2026-01-12 13:22:53', '2026-01-12 13:23:02'),
(8, '', '', 'uploads/slides/slide_b77334634d5636b6.jpg', 0, 0, '2026-01-13 09:04:05', '2026-01-13 09:41:08'),
(9, '', '', 'uploads/slides/slide_e318121fa744ce0e.jpg', 0, 0, '2026-01-13 09:47:02', '2026-01-24 09:48:25'),
(10, '', '', 'uploads/slides/slide_ff3d508eb6009212.webp', 0, 0, '2026-01-25 14:20:04', '2026-01-25 14:20:10'),
(11, '', '', 'uploads/slides/slide_fe9925a8b10e4f51.png', 0, 0, '2026-01-31 17:31:51', '2026-01-31 17:32:46');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_id` (`admin_id`),
  ADD KEY `action` (`action`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `applicants`
--
ALTER TABLE `applicants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_id` (`personal_id`);

--
-- Indexes for table `camps`
--
ALTER TABLE `camps`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `camps_attendance`
--
ALTER TABLE `camps_attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_unique_camp` (`unique_key`,`camp_id`),
  ADD KEY `idx_unique_key` (`unique_key`),
  ADD KEY `idx_camp_id` (`camp_id`);

--
-- Indexes for table `camps_fields`
--
ALTER TABLE `camps_fields`
  ADD PRIMARY KEY (`id`),
  ADD KEY `camp_id` (`camp_id`),
  ADD KEY `idx_camp_field_key` (`camp_id`,`field_key`);

--
-- Indexes for table `camps_pid_blocklist`
--
ALTER TABLE `camps_pid_blocklist`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_camp_pid` (`camp_id`,`pid`),
  ADD KEY `idx_pid` (`pid`);

--
-- Indexes for table `camps_posts`
--
ALTER TABLE `camps_posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `camp_id` (`camp_id`);

--
-- Indexes for table `camps_post_media`
--
ALTER TABLE `camps_post_media`
  ADD PRIMARY KEY (`id`),
  ADD KEY `post_id` (`post_id`);

--
-- Indexes for table `camps_registrations`
--
ALTER TABLE `camps_registrations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `camp_id` (`camp_id`),
  ADD KEY `unique_key` (`unique_key`);

--
-- Indexes for table `camp_fields`
--
ALTER TABLE `camp_fields`
  ADD PRIMARY KEY (`id`),
  ADD KEY `camp_id` (`camp_id`);

--
-- Indexes for table `camp_posts`
--
ALTER TABLE `camp_posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `camp_id` (`camp_id`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `camp_registrations`
--
ALTER TABLE `camp_registrations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `camp_id` (`camp_id`),
  ADD KEY `unique_key` (`unique_key`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `camp_registration_values`
--
ALTER TABLE `camp_registration_values`
  ADD PRIMARY KEY (`id`),
  ADD KEY `registration_id` (`registration_id`),
  ADD KEY `field_id` (`field_id`);

--
-- Indexes for table `grants`
--
ALTER TABLE `grants`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_active_deadline` (`is_active`,`deadline`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_sort` (`sort_order`);

--
-- Indexes for table `grant_applications`
--
ALTER TABLE `grant_applications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `grant_id` (`grant_id`),
  ADD KEY `status` (`status`),
  ADD KEY `idx_apps_deleted` (`deleted_at`);

--
-- Indexes for table `grant_application_files`
--
ALTER TABLE `grant_application_files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_app_files_app` (`application_id`),
  ADD KEY `idx_app_files_grant` (`grant_id`);

--
-- Indexes for table `grant_app_actionplan`
--
ALTER TABLE `grant_app_actionplan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `app_id` (`app_id`);

--
-- Indexes for table `grant_app_files`
--
ALTER TABLE `grant_app_files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_app` (`app_id`),
  ADD KEY `idx_grant` (`grant_id`),
  ADD KEY `idx_req` (`req_id`),
  ADD KEY `idx_gaf_kind` (`kind`),
  ADD KEY `idx_gaf_ref_id` (`ref_id`);

--
-- Indexes for table `grant_app_messages`
--
ALTER TABLE `grant_app_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `app_id` (`app_id`);

--
-- Indexes for table `grant_fields`
--
ALTER TABLE `grant_fields`
  ADD PRIMARY KEY (`id`),
  ADD KEY `grant_id` (`grant_id`),
  ADD KEY `step_id` (`step_id`),
  ADD KEY `sort_order` (`sort_order`);

--
-- Indexes for table `grant_file_requirements`
--
ALTER TABLE `grant_file_requirements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `grant_id` (`grant_id`);

--
-- Indexes for table `grant_form_fields`
--
ALTER TABLE `grant_form_fields`
  ADD PRIMARY KEY (`id`),
  ADD KEY `grant_id` (`grant_id`),
  ADD KEY `step_id` (`step_id`);

--
-- Indexes for table `grant_form_steps`
--
ALTER TABLE `grant_form_steps`
  ADD PRIMARY KEY (`id`),
  ADD KEY `grant_id` (`grant_id`);

--
-- Indexes for table `grant_requirements`
--
ALTER TABLE `grant_requirements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `grant_id` (`grant_id`),
  ADD KEY `sort_order` (`sort_order`);

--
-- Indexes for table `grant_steps`
--
ALTER TABLE `grant_steps`
  ADD PRIMARY KEY (`id`),
  ADD KEY `grant_id` (`grant_id`),
  ADD KEY `sort_order` (`sort_order`);

--
-- Indexes for table `grant_uploads`
--
ALTER TABLE `grant_uploads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_up_app` (`application_id`),
  ADD KEY `idx_up_grant` (`grant_id`),
  ADD KEY `idx_up_req` (`requirement_id`),
  ADD KEY `idx_up_field` (`field_id`),
  ADD KEY `idx_up_deleted` (`deleted_at`);

--
-- Indexes for table `members`
--
ALTER TABLE `members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_pid` (`pid`);

--
-- Indexes for table `news`
--
ALTER TABLE `news`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `news_images`
--
ALTER TABLE `news_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `news_id` (`news_id`);

--
-- Indexes for table `persons`
--
ALTER TABLE `persons`
  ADD PRIMARY KEY (`personal_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `slides`
--
ALTER TABLE `slides`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order` (`sort_order`),
  ADD KEY `idx_active` (`is_active`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admin_logs`
--
ALTER TABLE `admin_logs`
  MODIFY `id` bigint NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `applicants`
--
ALTER TABLE `applicants`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `camps`
--
ALTER TABLE `camps`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `camps_attendance`
--
ALTER TABLE `camps_attendance`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `camps_fields`
--
ALTER TABLE `camps_fields`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=81;

--
-- AUTO_INCREMENT for table `camps_pid_blocklist`
--
ALTER TABLE `camps_pid_blocklist`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `camps_posts`
--
ALTER TABLE `camps_posts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `camps_post_media`
--
ALTER TABLE `camps_post_media`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `camps_registrations`
--
ALTER TABLE `camps_registrations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `camp_fields`
--
ALTER TABLE `camp_fields`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `camp_posts`
--
ALTER TABLE `camp_posts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `camp_registrations`
--
ALTER TABLE `camp_registrations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `camp_registration_values`
--
ALTER TABLE `camp_registration_values`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `grants`
--
ALTER TABLE `grants`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `grant_applications`
--
ALTER TABLE `grant_applications`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `grant_application_files`
--
ALTER TABLE `grant_application_files`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `grant_app_actionplan`
--
ALTER TABLE `grant_app_actionplan`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `grant_app_files`
--
ALTER TABLE `grant_app_files`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `grant_app_messages`
--
ALTER TABLE `grant_app_messages`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `grant_fields`
--
ALTER TABLE `grant_fields`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=143;

--
-- AUTO_INCREMENT for table `grant_file_requirements`
--
ALTER TABLE `grant_file_requirements`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `grant_form_fields`
--
ALTER TABLE `grant_form_fields`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `grant_form_steps`
--
ALTER TABLE `grant_form_steps`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `grant_requirements`
--
ALTER TABLE `grant_requirements`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `grant_steps`
--
ALTER TABLE `grant_steps`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `grant_uploads`
--
ALTER TABLE `grant_uploads`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `members`
--
ALTER TABLE `members`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `news`
--
ALTER TABLE `news`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `news_images`
--
ALTER TABLE `news_images`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `slides`
--
ALTER TABLE `slides`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `camps_attendance`
--
ALTER TABLE `camps_attendance`
  ADD CONSTRAINT `fk_att_camp` FOREIGN KEY (`camp_id`) REFERENCES `camps` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `camps_fields`
--
ALTER TABLE `camps_fields`
  ADD CONSTRAINT `fk_camps_fields_camp` FOREIGN KEY (`camp_id`) REFERENCES `camps` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `camps_posts`
--
ALTER TABLE `camps_posts`
  ADD CONSTRAINT `fk_camps_posts_camp` FOREIGN KEY (`camp_id`) REFERENCES `camps` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `camps_post_media`
--
ALTER TABLE `camps_post_media`
  ADD CONSTRAINT `fk_camps_post_media_post` FOREIGN KEY (`post_id`) REFERENCES `camps_posts` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `camps_registrations`
--
ALTER TABLE `camps_registrations`
  ADD CONSTRAINT `fk_camps_regs_camp` FOREIGN KEY (`camp_id`) REFERENCES `camps` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `camp_fields`
--
ALTER TABLE `camp_fields`
  ADD CONSTRAINT `camp_fields_ibfk_1` FOREIGN KEY (`camp_id`) REFERENCES `camps` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `camp_posts`
--
ALTER TABLE `camp_posts`
  ADD CONSTRAINT `camp_posts_ibfk_1` FOREIGN KEY (`camp_id`) REFERENCES `camps` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `camp_registrations`
--
ALTER TABLE `camp_registrations`
  ADD CONSTRAINT `camp_registrations_ibfk_1` FOREIGN KEY (`camp_id`) REFERENCES `camps` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `camp_registration_values`
--
ALTER TABLE `camp_registration_values`
  ADD CONSTRAINT `camp_registration_values_ibfk_1` FOREIGN KEY (`registration_id`) REFERENCES `camp_registrations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `camp_registration_values_ibfk_2` FOREIGN KEY (`field_id`) REFERENCES `camp_fields` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `grant_applications`
--
ALTER TABLE `grant_applications`
  ADD CONSTRAINT `fk_apps_grant` FOREIGN KEY (`grant_id`) REFERENCES `grants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `grant_application_files`
--
ALTER TABLE `grant_application_files`
  ADD CONSTRAINT `fk_app_files_app` FOREIGN KEY (`application_id`) REFERENCES `grant_applications` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_app_files_grant` FOREIGN KEY (`grant_id`) REFERENCES `grants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `grant_app_actionplan`
--
ALTER TABLE `grant_app_actionplan`
  ADD CONSTRAINT `fk_ap_app` FOREIGN KEY (`app_id`) REFERENCES `grant_applications` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `grant_app_files`
--
ALTER TABLE `grant_app_files`
  ADD CONSTRAINT `fk_files_app` FOREIGN KEY (`app_id`) REFERENCES `grant_applications` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_files_grant` FOREIGN KEY (`grant_id`) REFERENCES `grants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `grant_app_messages`
--
ALTER TABLE `grant_app_messages`
  ADD CONSTRAINT `fk_msgs_app` FOREIGN KEY (`app_id`) REFERENCES `grant_applications` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `grant_file_requirements`
--
ALTER TABLE `grant_file_requirements`
  ADD CONSTRAINT `fk_reqs_grant` FOREIGN KEY (`grant_id`) REFERENCES `grants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `grant_form_fields`
--
ALTER TABLE `grant_form_fields`
  ADD CONSTRAINT `fk_fields_grant` FOREIGN KEY (`grant_id`) REFERENCES `grants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_fields_step` FOREIGN KEY (`step_id`) REFERENCES `grant_form_steps` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `grant_form_steps`
--
ALTER TABLE `grant_form_steps`
  ADD CONSTRAINT `fk_steps_grant` FOREIGN KEY (`grant_id`) REFERENCES `grants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `grant_uploads`
--
ALTER TABLE `grant_uploads`
  ADD CONSTRAINT `fk_up_app` FOREIGN KEY (`application_id`) REFERENCES `grant_applications` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_up_grant` FOREIGN KEY (`grant_id`) REFERENCES `grants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `news_images`
--
ALTER TABLE `news_images`
  ADD CONSTRAINT `fk_news_images_news` FOREIGN KEY (`news_id`) REFERENCES `news` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
