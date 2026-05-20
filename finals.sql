-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 19, 2026 at 02:17 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `finals`
--

-- ============================================================
-- DROP ALL TABLES FIRST (safe re-import on InfinityFree / localhost)
-- ============================================================
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `stock_movements`;
DROP TABLE IF EXISTS `shopping_cart`;
DROP TABLE IF EXISTS `order_status_history`;
DROP TABLE IF EXISTS `order_items`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `settings`;
DROP TABLE IF EXISTS `inventory_items`;
DROP TABLE IF EXISTS `customers`;
DROP TABLE IF EXISTS `admins`;

SET FOREIGN_KEY_CHECKS = 1;

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `fullname` varchar(255) NOT NULL,
  `username` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','manager','staff') DEFAULT 'admin',
  `status` enum('active','inactive') DEFAULT 'active',
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `reset_token` varchar(255) DEFAULT NULL,
  `token_expiry` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `fullname`, `username`, `email`, `password`, `role`, `status`, `last_login`, `created_at`, `updated_at`, `reset_token`, `token_expiry`) VALUES
(2, 'Girlly Fernandez', 'admin', 'girllyfernandez359@gmail.com', '$2y$10$JTc5oQj47vBeVjPJEch1supEC1Kuj9uWB6FbVdTjhG/LPmNbWldKy', 'admin', 'active', NULL, '2026-05-17 08:03:51', '2026-05-18 19:05:06', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `fullname` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(50) NOT NULL,
  `address` text NOT NULL,
  `password` varchar(255) NOT NULL,
  `profile_image` varchar(500) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `verification_token` varchar(255) DEFAULT NULL,
  `email_verified` tinyint(1) DEFAULT 0,
  `last_login` timestamp NULL DEFAULT NULL,
  `status` enum('active','inactive','pending') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `fullname`, `email`, `phone`, `address`, `password`, `profile_image`, `date_of_birth`, `gender`, `verification_token`, `email_verified`, `last_login`, `status`, `created_at`, `updated_at`) VALUES
(3, 'Karen Montes', 'monteskaren1205@gmail.com', '09123456789', 'San Matias Sta.Rita Pampanga', '$2y$10$op2y8DMLxB.9qxltagIzyufVyUaShbbYbjCK.E0OL95r3tAhgGxDm', NULL, NULL, NULL, NULL, 0, NULL, 'active', '2026-05-17 10:19:51', '2026-05-18 18:21:48'),
(4, 'Ruffa Calilung', 'ruffacalilung.1627@gmail.com', '09090909090', 'San Vicente Ebus Guagua Pampanga', '$2y$10$LAWz2KHhi8ko6lHPvcHcpee2pSKMaQLFCW6nGtq6DyYZv9N3M9vra', NULL, NULL, NULL, NULL, 0, NULL, 'active', '2026-05-17 15:30:30', '2026-05-17 15:30:30'),
(5, 'renren david', 'monteskaren1218@gmail.com', '09123456789', 'san matias stta rita pampanga', '$2y$10$oYcHRIOC5ypZlNibtpz4eu6PO/ORlwEV.oAdtoIWkOxS2GN5xM16G', NULL, NULL, NULL, NULL, 0, NULL, 'active', '2026-05-18 19:12:04', '2026-05-18 19:12:04');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_items`
--

CREATE TABLE `inventory_items` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(100) DEFAULT 'General',
  `current_quantity` int(11) DEFAULT 0,
  `min_stock` int(11) DEFAULT 10,
  `selling_price` decimal(10,2) DEFAULT 0.00,
  `unit` varchar(50) DEFAULT 'pcs',
  `expiry_date` date DEFAULT NULL,
  `supplier` varchar(255) DEFAULT NULL,
  `barcode` varchar(100) DEFAULT NULL,
  `image_path` varchar(500) DEFAULT NULL,
  `status` enum('active','inactive','discontinued') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `inventory_items`
--

INSERT INTO `inventory_items` (`id`, `name`, `description`, `category`, `current_quantity`, `min_stock`, `selling_price`, `unit`, `expiry_date`, `supplier`, `barcode`, `image_path`, `status`, `created_at`, `updated_at`) VALUES
(11, 'B‑MEG Integra 1000 – Chick Booster Crumble (Immuno‑Boosters)', 'For day‑old to 4‑weeks; probiotics + enzymes, boosts immunity & digestion.\\r\\n', 'Feeds', 99, 10, 2050.00, '50', '2026-10-16', '', '4800016110012', 'uploads/inventory/6a0988b66903a.png', 'active', '2026-05-16 06:14:31', '2026-05-18 03:28:49'),
(12, 'B‑MEG Integra 2000 – Starter Crumble (Immuno‑Growth Boosters)', 'High protein, easy digest, builds strong body resistance & fast growth.', 'Feeds', 100, 10, 2350.00, '50', '2026-10-16', '', '4800016110029', 'uploads/inventory/6a0988de81dc3.png', 'active', '2026-05-16 06:18:56', '2026-05-17 11:42:14'),
(13, 'B-MEG Integra 2500 – Transition Pellet (Anti-Stress + Immuno-Boosters)', 'Smooth shift from starter to grower; reduces stress during feed change, supports consistent growth and strong defense.', 'Feeds', 100, 10, 2100.00, '50', '2026-10-17', '', '4800016110036', 'uploads/inventory/6a098d79c1389.png', 'active', '2026-05-17 09:42:17', '2026-05-17 09:42:17'),
(14, ' B-MEG Integra 3000 – Maintenance / Free-Range Finisher Pellet', 'Balanced nutrition; keeps birds lean and healthy, good for backyard/free-range, improves muscle development & disease resistance', 'Feeds', 100, 10, 1950.00, '50', '0026-10-17', '', '4800016110043', 'uploads/inventory/6a098e10e9f7a.png', 'active', '2026-05-17 09:44:48', '2026-05-17 09:44:48'),
(15, ' B-MEG Integra 3000 PLUS+ – Mixed Pellet & Grain', 'Added whole grains; extra energy & fiber, better digestion, supports ideal body weight & form, enhanced gut health.', 'Feeds', 100, 10, 2000.00, '50', '2026-10-17', NULL, '4800016110104', 'uploads/inventory/6a098fca7daa0.png', 'active', '2026-05-17 09:48:39', '2026-05-17 11:42:14'),
(17, 'B-MEG Integra 4000 – Breeder / Layer Pellet (Embryo Developer)', 'High calcium & phosphorus; boosts egg production, stronger shells, better hatchability & chick quality, superior immunity.', 'Feeds', 60, 10, 1900.00, '50', '2026-10-17', NULL, '4800016110050', 'uploads/inventory/6a09910e3030c.png', 'active', '2026-05-17 09:57:34', '2026-05-18 04:45:12'),
(18, 'B-MEG Integra 5000 – High Protein Pellet', ' Fortified with electrolytes & B-complex; fast muscle growth, high energy, easy digestion, excellent for meat-type chickens.', 'Feeds', 59, 10, 1850.00, '50', '2026-10-17', NULL, '4800016110067', 'uploads/inventory/6a099156173f0.png', 'active', '2026-05-17 09:58:46', '2026-05-18 03:27:55'),
(28, 'VitMinPRO Water Soluble Powder – Health Enhancer (Multivitamins + Minerals + Electrolytes + Amino Acids + Probiotics)', 'Boosts immunity, energy, digestion & growth; anti‑stress; corrects nutrient deficiencies', 'Supplements/Medicines', 50, 10, 300.00, '20 sachet per box', '2028-05-17', NULL, '4809012340015', 'uploads/inventory/6a09d4d6bc43e.png', 'active', '2026-05-17 14:46:46', '2026-05-17 14:46:46'),
(31, 'VitMinPRO Multivitamins + Minerals + Electrolytes + Amino Acids + Probiotics', 'Easy-to-feed, complete nutrition per tablet', 'Supplements/Medicines', 40, 10, 320.00, '100 Tablets', '2029-05-17', NULL, '4809012340022', 'uploads/inventory/6a09d573c40c2.png', 'active', '2026-05-17 14:49:23', '2026-05-17 14:49:23'),
(32, 'VitMinPRO Water Soluble Powder – Health Enhancer (Multivitamins + Minerals + Electrolytes + Amino Acids + Probiotics)', 'Water-soluble, all poultry & livestock', 'Supplements/Medicines', 56, 10, 640.00, '1 kg', '2028-05-17', NULL, '4809012340039', 'uploads/inventory/6a09d623ab042.png', 'active', '2026-05-17 14:52:19', '2026-05-18 22:27:24'),
(34, 'Thunderbird Red Cell Vitamin‑Iron‑Mineral Liquid Supplement', 'Liquid iron + vitamins; prevents anemia, boosts blood health & vitality', 'Supplements/Medicines', 99, 10, 220.00, '100ml', '2028-05-17', NULL, ' 4808876210048', 'uploads/inventory/6a09d7e5afdbf.png', 'active', '2026-05-17 14:59:49', '2026-05-18 20:59:02'),
(35, 'Thunderbird Red Cell Vitamin‑Iron‑Mineral Liquid Supplement', 'Liquid iron + vitamins; prevents anemia, boosts blood health & vitality', 'Supplements/Medicines', 88, 10, 1400.00, '50ml', '2028-05-17', 'ThunderBird', ' 4808876210048', 'uploads/inventory/6a09d82c2ef5f.png', 'active', '2026-05-17 15:01:00', '2026-05-18 22:27:23'),
(36, 'Hammer Tablet – Super Strength & Energy Booster', 'Maximum power, stamina, recovery, anti-stress; for gamefowl & layers', 'Supplements/Medicines', 49, 10, 350.00, '100 Tablets', '2028-11-17', NULL, '4809012340107', 'uploads/inventory/6a09d8aea144b.png', 'active', '2026-05-17 15:03:10', '2026-05-18 03:27:55'),
(39, 'Water Feeder (Archived)', 'Compact poultry drinker designed for chicks and small flocks. Easy to refill, spill-resistant.', 'Equipment/Feeding Tools', 0, 10, 45.00, '1.5 L', NULL, NULL, '100001500001', 'uploads/inventory/6a0a78d011c27.png', 'active', '2026-05-17 15:25:58', '2026-05-18 00:33:32'),
(40, 'Water Feeder ', 'Compact poultry drinker designed for chicks and small flocks. Easy to refill, spill-resistant, and ideal for starter chicks in cages or brooders.', 'Equipment/Feeding Tools', 110, 10, 45.00, '1.5 L', NULL, NULL, '100001500001', 'uploads/inventory/6a0a78d011c27.png', 'active', '2026-05-18 02:26:24', '2026-05-18 05:41:29'),
(41, 'Water Feeder ', 'Medium-capacity poultry water feeder suitable for small backyard chickens. Provides clean water supply with minimal contamination.', 'Equipment/Feeding Tools', 60, 10, 70.00, '3 L', NULL, NULL, '100003000002', 'uploads/inventory/6a0a79182c2a4.png', 'active', '2026-05-18 02:27:36', '2026-05-18 02:27:36'),
(42, 'Water Feeder ', 'Standard-size chicken drinker for growing flocks. Durable plastic design with stable base to reduce tipping.', 'Equipment/Feeding Tools', 82, 10, 120.00, '6 L', NULL, NULL, '100006000003', 'uploads/inventory/6a0a796f674e8.png', 'active', '2026-05-18 02:29:03', '2026-05-18 22:27:23'),
(43, 'Water Feeder ', 'Large-capacity poultry waterer ideal for medium to large backyard farms. Reduces frequent refilling and keeps water clean longer.', 'Equipment/Feeding Tools', 64, 10, 180.00, '9 L', NULL, NULL, '100009000004', 'uploads/inventory/6a0a79c99cc16.png', 'active', '2026-05-18 02:30:33', '2026-05-18 22:44:39'),
(44, 'Water Feeder ', 'Heavy-duty water feeder designed for large flocks. Best for free-range or semi-commercial poultry setups with high water demand.', 'Equipment/Feeding Tools', 0, 10, 250.00, '14 L', NULL, NULL, '100014000005', 'uploads/inventory/6a0a7a19a2797.png', 'active', '2026-05-18 02:31:53', '2026-05-18 22:33:20'),
(45, ' Metal Chicken Feeder ', 'Long trough-style feeder designed for multiple chickens at once. Prevents crowding and allows even feed distribution.', 'Equipment/Feeding Tools', 96, 10, 150.00, '10', NULL, NULL, '1000015000013', 'uploads/inventory/6a0b9133e73dc.jpeg', 'active', '2026-05-18 22:22:43', '2026-05-18 22:37:03');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `order_number` varchar(100) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('Draft','Pending Payment','Pending Approval','Approved','Processed','Shipped','Delivered','Cancelled') DEFAULT 'Draft',
  `payment_method` enum('cod','gcash','card','bank_transfer') DEFAULT 'cod',
  `payment_reference` varchar(255) DEFAULT NULL,
  `payment_amount` decimal(10,2) DEFAULT NULL,
  `payment_date` timestamp NULL DEFAULT NULL,
  `tracking_number` varchar(255) DEFAULT NULL,
  `shipping_date` timestamp NULL DEFAULT NULL,
  `actual_delivery` timestamp NULL DEFAULT NULL,
  `delivery_address` text NOT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `customer_id`, `order_number`, `total_amount`, `status`, `payment_method`, `payment_reference`, `payment_amount`, `payment_date`, `tracking_number`, `shipping_date`, `actual_delivery`, `delivery_address`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(4, 3, 'ORD-20260518-6A0A6BE3A2C08', 2245.00, 'Delivered', 'cod', NULL, NULL, NULL, 'BP20260518112331', '2026-05-18 03:27:13', '2026-05-18 03:27:55', 'San Matias Sta.Rita Pampanga', '', 2, '2026-05-18 01:31:15', '2026-05-18 03:27:55'),
(5, 3, 'ORD-20260518-6A0A86055825B', 2050.00, 'Delivered', 'gcash', 'BP20260518112331', 2050.00, '2026-05-18 03:23:55', 'BP20260518112331', '2026-05-18 03:28:46', '2026-05-18 03:28:49', 'San Matias Sta.Rita Pampanga', '', 2, '2026-05-18 03:22:45', '2026-05-18 03:28:49'),
(6, 4, 'ORD-20260518-6A0A936A00AD7', 38250.00, 'Delivered', 'gcash', 'BP20260518112331', 38250.00, '2026-05-18 04:44:15', '2026051811264', '2026-05-18 04:45:10', '2026-05-18 04:45:12', 'San Vicente Ebus Guagua Pampanga', 'atut', 2, '2026-05-18 04:19:54', '2026-05-18 04:45:12'),
(7, 4, 'ORD-20260518-6A0A98ABF0AD2', 250.00, 'Delivered', 'gcash', 'BP20260518112331', 250.00, '2026-05-18 04:44:11', '2026051811264', '2026-05-18 04:45:04', '2026-05-18 04:45:11', 'San Vicente Ebus Guagua Pampanga', '', 2, '2026-05-18 04:42:19', '2026-05-18 04:45:11'),
(8, 4, 'ORD-20260518-6A0A9A1FC45B3', 250.00, 'Delivered', 'cod', '', 250.00, '2026-05-18 04:48:46', '2026051811264', '2026-05-18 04:49:17', '2026-05-18 04:49:24', 'San Vicente Ebus Guagua Pampanga', '', 2, '2026-05-18 04:48:31', '2026-05-18 04:49:24'),
(9, 3, 'ORD-20260518-6A0AA53FB8D67', 250.00, 'Processed', 'cod', 'cod', 250.00, '2026-05-18 16:08:04', NULL, NULL, NULL, 'San Matias Sta.Rita Pampanga', '', 2, '2026-05-18 05:35:59', '2026-05-18 20:49:57'),
(10, 3, 'ORD-20260518-6A0B389F589D5', 1250.00, 'Processed', 'cod', '', 1250.00, '2026-05-18 16:05:32', NULL, NULL, NULL, 'San Matias Sta.Rita Pampanga', 'bala kana', 2, '2026-05-18 16:04:47', '2026-05-18 20:49:52'),
(11, 3, 'ORD-20260518-6A0B42483F40A', 465.00, 'Processed', 'cod', '', 465.00, '2026-05-18 16:46:13', NULL, NULL, NULL, 'San Matias Sta.Rita Pampanga', '', 2, '2026-05-18 16:46:00', '2026-05-18 18:14:35'),
(12, 5, 'ORD-20260518-6A0B6515C5747', 12500.00, 'Delivered', 'cod', '', 12500.00, '2026-05-18 19:14:55', '6A0B89D10561F', '2026-05-18 22:27:16', '2026-05-18 22:27:18', 'san matias stta rita pampanga', 'ppwede na', 2, '2026-05-18 19:14:29', '2026-05-18 22:27:18'),
(13, 5, 'ORD-20260518-6A0B66BD46B4A', 520.00, 'Delivered', 'cod', '', 520.00, '2026-05-18 19:21:56', '6A0B66BD46B4A', '2026-05-18 20:56:50', '2026-05-18 20:59:02', 'san matias stta rita pampanga', 'gfhtdjytud', 2, '2026-05-18 19:21:33', '2026-05-18 20:59:02'),
(14, 5, 'ORD-20260518-6A0B67B9A2FF1', 640.00, 'Delivered', 'gcash', 'dgrer', 640.00, '2026-05-18 19:28:54', 'JT201567890234', '2026-05-18 22:27:09', '2026-05-18 22:27:24', 'san matias stta rita pampanga', 'fyh', 2, '2026-05-18 19:25:45', '2026-05-18 22:27:24'),
(15, 5, 'ORD-20260518-6A0B893137A15', 250.00, 'Delivered', 'gcash', NULL, NULL, NULL, 'JT100456789012', '2026-05-18 22:27:05', '2026-05-18 22:27:24', 'san matias stta rita pampanga', '', 2, '2026-05-18 21:48:33', '2026-05-18 22:27:24'),
(16, 5, 'ORD-20260518-6A0B89525FBA4', 550.00, 'Delivered', 'gcash', NULL, NULL, NULL, 'LB987654321012', '2026-05-18 22:27:00', '2026-05-18 22:27:23', 'san matias stta rita pampanga', '', 2, '2026-05-18 21:49:06', '2026-05-18 22:27:23'),
(17, 5, 'ORD-20260518-6A0B89D10561F', 250.00, 'Delivered', 'cod', '', 250.00, '2026-05-18 21:51:22', '6A0B89D10561F', '2026-05-18 21:54:58', '2026-05-18 21:55:05', 'san matias stta rita pampanga', '', 2, '2026-05-18 21:51:13', '2026-05-18 21:55:05'),
(18, 5, 'ORD-20260519-6A0B8BEA0315B', 1400.00, 'Delivered', 'cod', NULL, NULL, NULL, 'JT403789012456', '2026-05-18 22:26:47', '2026-05-18 22:27:23', 'san matias stta rita pampanga', '', 2, '2026-05-18 22:00:10', '2026-05-18 22:27:23'),
(19, 5, 'ORD-20260519-6A0B8D3B73688', 250.00, 'Delivered', 'cod', NULL, NULL, NULL, 'JT302678901345', '2026-05-18 22:26:12', '2026-05-18 22:27:22', 'san matias stta rita pampanga', '', 2, '2026-05-18 22:05:47', '2026-05-18 22:27:22'),
(20, 5, 'ORD-20260519-6A0B8D4D4ECD1', 250.00, 'Delivered', 'gcash', NULL, NULL, NULL, 'JT201567890234', '2026-05-18 22:26:01', '2026-05-18 22:27:21', 'san matias stta rita pampanga', '', 2, '2026-05-18 22:06:05', '2026-05-18 22:27:21'),
(21, 5, 'ORD-20260519-6A0B8F3C5ACF4', 640.00, 'Delivered', 'cod', 'COD', 640.00, '2026-05-18 22:15:11', 'JT100456789012', '2026-05-18 22:25:46', '2026-05-18 22:27:21', 'san matias stta rita pampanga', '\nAdmin Note: Reference Confirm', 2, '2026-05-18 22:14:20', '2026-05-18 22:27:21'),
(22, 4, 'ORD-20260519-6A0B93B07AF2F', 550.00, 'Draft', 'cod', NULL, NULL, NULL, NULL, NULL, NULL, 'San Vicente Ebus Guagua Pampanga', '', NULL, '2026-05-18 22:33:20', '2026-05-18 22:33:20'),
(23, 4, 'ORD-20260519-6A0B93CED7664', 150.00, 'Draft', 'cod', NULL, NULL, NULL, NULL, NULL, NULL, 'San Vicente Ebus Guagua Pampanga', '', NULL, '2026-05-18 22:33:50', '2026-05-18 22:33:50'),
(24, 4, 'ORD-20260519-6A0B948FA283A', 150.00, 'Pending Payment', 'cod', '', 0.00, '2026-05-18 22:37:22', NULL, NULL, NULL, 'San Vicente Ebus Guagua Pampanga', '', NULL, '2026-05-18 22:37:03', '2026-05-18 22:37:22'),
(25, 4, 'ORD-20260519-6A0B9656C4301', 180.00, 'Pending Payment', 'cod', '', 0.00, '2026-05-18 22:44:48', NULL, NULL, NULL, 'San Vicente Ebus Guagua Pampanga', '', NULL, '2026-05-18 22:44:38', '2026-05-18 22:44:48');

-- NOTE: Triggers removed for InfinityFree shared hosting compatibility.
-- Order status logging and inventory deduction are handled in PHP code.

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `quantity`, `unit_price`, `total_price`, `created_at`) VALUES
(4, 4, 18, 0, 1, 1850.00, 1850.00, '2026-05-18 01:31:15'),
(5, 4, 39, 0, 1, 45.00, 45.00, '2026-05-18 01:31:15'),
(6, 4, 36, 0, 1, 350.00, 350.00, '2026-05-18 01:31:15'),
(7, 5, 11, 0, 1, 2050.00, 2050.00, '2026-05-18 03:22:45'),
(8, 6, 17, 0, 20, 1900.00, 38000.00, '2026-05-18 04:19:54'),
(9, 6, 44, 0, 1, 250.00, 250.00, '2026-05-18 04:19:54'),
(10, 7, 44, 0, 1, 250.00, 250.00, '2026-05-18 04:42:19'),
(11, 8, 44, 0, 1, 250.00, 250.00, '2026-05-18 04:48:31'),
(12, 9, 44, 0, 1, 250.00, 250.00, '2026-05-18 05:35:59'),
(13, 10, 44, 0, 5, 250.00, 1250.00, '2026-05-18 16:04:47'),
(14, 11, 36, 0, 1, 350.00, 350.00, '2026-05-18 16:46:00'),
(15, 11, 35, 0, 1, 115.00, 115.00, '2026-05-18 16:46:00'),
(16, 12, 44, 0, 50, 250.00, 12500.00, '2026-05-18 19:14:29'),
(17, 13, 43, 0, 1, 180.00, 180.00, '2026-05-18 19:21:33'),
(18, 13, 42, 0, 1, 120.00, 120.00, '2026-05-18 19:21:33'),
(19, 13, 34, 0, 1, 220.00, 220.00, '2026-05-18 19:21:33'),
(20, 14, 32, 0, 1, 640.00, 640.00, '2026-05-18 19:25:45'),
(21, 15, 44, 0, 1, 250.00, 250.00, '2026-05-18 21:48:33'),
(22, 16, 44, 0, 1, 250.00, 250.00, '2026-05-18 21:49:06'),
(23, 16, 43, 0, 1, 180.00, 180.00, '2026-05-18 21:49:06'),
(24, 16, 42, 0, 1, 120.00, 120.00, '2026-05-18 21:49:06'),
(25, 17, 44, 0, 1, 250.00, 250.00, '2026-05-18 21:51:13'),
(26, 18, 35, 0, 1, 1400.00, 1400.00, '2026-05-18 22:00:10'),
(27, 19, 44, 0, 1, 250.00, 250.00, '2026-05-18 22:05:47'),
(28, 20, 44, 0, 1, 250.00, 250.00, '2026-05-18 22:06:05'),
(29, 21, 32, 0, 1, 640.00, 640.00, '2026-05-18 22:14:20'),
(30, 22, 44, 0, 1, 250.00, 250.00, '2026-05-18 22:33:20'),
(31, 22, 45, 0, 2, 150.00, 300.00, '2026-05-18 22:33:20'),
(32, 23, 45, 0, 1, 150.00, 150.00, '2026-05-18 22:33:50'),
(33, 24, 45, 0, 1, 150.00, 150.00, '2026-05-18 22:37:03'),
(34, 25, 43, 0, 1, 180.00, 180.00, '2026-05-18 22:44:39');

-- NOTE: order_items triggers removed for InfinityFree compatibility.
-- Total recalculation is handled in PHP code (place_order.php).

-- --------------------------------------------------------

--
-- Table structure for table `order_status_history`
--

CREATE TABLE `order_status_history` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `status` varchar(50) NOT NULL,
  `notes` text DEFAULT NULL,
  `changed_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_status_history`
--

INSERT INTO `order_status_history` (`id`, `order_id`, `status`, `notes`, `changed_by`, `created_at`) VALUES
(10, 4, 'Approved', 'Status changed from Draft to Approved', 2, '2026-05-18 03:00:09'),
(11, 5, 'Pending Payment', 'Status changed from Draft to Pending Payment', NULL, '2026-05-18 03:23:55'),
(12, 4, 'Processed', 'Status changed from Approved to Processed', 2, '2026-05-18 03:25:48'),
(13, 4, 'Shipped', 'Status changed from Processed to Shipped', 2, '2026-05-18 03:27:13'),
(14, 4, 'Delivered', 'Status changed from Shipped to Delivered', 2, '2026-05-18 03:27:55'),
(15, 5, 'Approved', 'Status changed from Pending Payment to Approved', 2, '2026-05-18 03:28:28'),
(16, 5, 'Processed', 'Status changed from Approved to Processed', 2, '2026-05-18 03:28:38'),
(17, 5, 'Shipped', 'Status changed from Processed to Shipped', 2, '2026-05-18 03:28:46'),
(18, 5, 'Delivered', 'Status changed from Shipped to Delivered', 2, '2026-05-18 03:28:49'),
(19, 6, 'Pending Payment', 'Status changed from Draft to Pending Payment', NULL, '2026-05-18 04:28:21'),
(20, 7, 'Pending Payment', 'Status changed from Draft to Pending Payment', NULL, '2026-05-18 04:42:33'),
(21, 7, 'Approved', 'Status changed from Pending Payment to Approved', 2, '2026-05-18 04:44:11'),
(22, 6, 'Approved', 'Status changed from Pending Payment to Approved', 2, '2026-05-18 04:44:15'),
(23, 7, 'Processed', 'Status changed from Approved to Processed', 2, '2026-05-18 04:44:22'),
(24, 6, 'Processed', 'Status changed from Approved to Processed', 2, '2026-05-18 04:44:23'),
(25, 7, 'Shipped', 'Status changed from Processed to Shipped', 2, '2026-05-18 04:45:04'),
(26, 6, 'Shipped', 'Status changed from Processed to Shipped', 2, '2026-05-18 04:45:10'),
(27, 7, 'Delivered', 'Status changed from Shipped to Delivered', 2, '2026-05-18 04:45:11'),
(28, 6, 'Delivered', 'Status changed from Shipped to Delivered', 2, '2026-05-18 04:45:12'),
(29, 8, 'Pending Payment', 'Status changed from Draft to Pending Payment', NULL, '2026-05-18 04:48:40'),
(30, 8, 'Approved', 'Status changed from Pending Payment to Approved', 2, '2026-05-18 04:49:03'),
(31, 8, 'Processed', 'Status changed from Approved to Processed', 2, '2026-05-18 04:49:11'),
(32, 8, 'Shipped', 'Status changed from Processed to Shipped', 2, '2026-05-18 04:49:17'),
(33, 8, 'Delivered', 'Status changed from Shipped to Delivered', 2, '2026-05-18 04:49:24'),
(34, 9, 'Pending Payment', 'Status changed from Draft to Pending Payment', NULL, '2026-05-18 05:36:07'),
(35, 10, 'Pending Payment', 'Status changed from Draft to Pending Payment', NULL, '2026-05-18 16:05:32'),
(36, 10, 'Approved', 'Status changed from Pending Payment to Approved', 2, '2026-05-18 16:07:37'),
(37, 9, 'Approved', 'Status changed from Pending Payment to Approved', 2, '2026-05-18 16:08:04'),
(38, 11, 'Pending Payment', 'Status changed from Draft to Pending Payment', NULL, '2026-05-18 16:46:13'),
(39, 11, 'Approved', 'Status changed from Pending Payment to Approved', 2, '2026-05-18 18:14:08'),
(40, 11, 'Processed', 'Status changed from Approved to Processed', 2, '2026-05-18 18:14:35'),
(41, 12, 'Pending Payment', 'Status changed from Draft to Pending Payment', NULL, '2026-05-18 19:14:55'),
(42, 12, 'Approved', 'Status changed from Pending Payment to Approved', 2, '2026-05-18 19:16:40'),
(43, 12, 'Processed', 'Status changed from Approved to Processed', 2, '2026-05-18 19:17:04'),
(44, 13, 'Pending Payment', 'Status changed from Draft to Pending Payment', NULL, '2026-05-18 19:21:56'),
(45, 13, 'Approved', 'Status changed from Pending Payment to Approved', 2, '2026-05-18 19:23:06'),
(46, 13, 'Processed', 'Status changed from Approved to Processed', 2, '2026-05-18 19:25:01'),
(47, 14, 'Pending Payment', 'Status changed from Draft to Pending Payment', NULL, '2026-05-18 19:26:25'),
(48, 10, 'Processed', 'Status changed from Approved to Processed', 2, '2026-05-18 20:49:52'),
(49, 9, 'Processed', 'Status changed from Approved to Processed', 2, '2026-05-18 20:49:57'),
(50, 13, 'Shipped', 'Status changed from Processed to Shipped', 2, '2026-05-18 20:56:50'),
(51, 13, 'Delivered', 'Status changed from Shipped to Delivered', 2, '2026-05-18 20:59:02'),
(52, 17, 'Pending Payment', 'Status changed from Draft to Pending Payment', NULL, '2026-05-18 21:51:22'),
(53, 17, 'Approved', 'Status changed from Pending Payment to Approved', 2, '2026-05-18 21:54:10'),
(54, 16, 'Approved', 'Status changed from Draft to Approved', 2, '2026-05-18 21:54:14'),
(55, 15, 'Approved', 'Status changed from Draft to Approved', 2, '2026-05-18 21:54:17'),
(56, 14, 'Approved', 'Status changed from Pending Payment to Approved', 2, '2026-05-18 21:54:20'),
(57, 17, 'Processed', 'Status changed from Approved to Processed', 2, '2026-05-18 21:54:27'),
(58, 16, 'Processed', 'Status changed from Approved to Processed', 2, '2026-05-18 21:54:28'),
(59, 15, 'Processed', 'Status changed from Approved to Processed', 2, '2026-05-18 21:54:29'),
(60, 14, 'Processed', 'Status changed from Approved to Processed', 2, '2026-05-18 21:54:30'),
(61, 17, 'Shipped', 'Status changed from Processed to Shipped', 2, '2026-05-18 21:54:58'),
(62, 17, 'Delivered', 'Status changed from Shipped to Delivered', 2, '2026-05-18 21:55:05'),
(63, 20, 'Approved', 'Status changed from Draft to Approved', 2, '2026-05-18 22:08:46'),
(64, 20, 'Processed', 'Status changed from Approved to Processed', 2, '2026-05-18 22:08:57'),
(65, 19, 'Approved', 'Status changed from Draft to Approved', 2, '2026-05-18 22:12:49'),
(66, 18, 'Approved', 'Status changed from Draft to Approved', 2, '2026-05-18 22:13:19'),
(67, 21, 'Pending Payment', 'Status changed from Draft to Pending Payment', NULL, '2026-05-18 22:14:22'),
(68, 21, 'Approved', 'Status changed from Pending Payment to Approved', 2, '2026-05-18 22:15:11'),
(69, 21, 'Processed', 'Status changed from Approved to Processed', 2, '2026-05-18 22:15:17'),
(70, 19, 'Processed', 'Status changed from Approved to Processed', 2, '2026-05-18 22:24:49'),
(71, 18, 'Processed', 'Status changed from Approved to Processed', 2, '2026-05-18 22:24:50'),
(72, 21, 'Shipped', 'Status changed from Processed to Shipped', 2, '2026-05-18 22:25:46'),
(73, 20, 'Shipped', 'Status changed from Processed to Shipped', 2, '2026-05-18 22:26:01'),
(74, 19, 'Shipped', 'Status changed from Processed to Shipped', 2, '2026-05-18 22:26:12'),
(75, 18, 'Shipped', 'Status changed from Processed to Shipped', 2, '2026-05-18 22:26:47'),
(76, 16, 'Shipped', 'Status changed from Processed to Shipped', 2, '2026-05-18 22:27:00'),
(77, 15, 'Shipped', 'Status changed from Processed to Shipped', 2, '2026-05-18 22:27:05'),
(78, 14, 'Shipped', 'Status changed from Processed to Shipped', 2, '2026-05-18 22:27:09'),
(79, 12, 'Shipped', 'Status changed from Processed to Shipped', 2, '2026-05-18 22:27:16'),
(80, 12, 'Delivered', 'Status changed from Shipped to Delivered', 2, '2026-05-18 22:27:18'),
(81, 21, 'Delivered', 'Status changed from Shipped to Delivered', 2, '2026-05-18 22:27:21'),
(82, 20, 'Delivered', 'Status changed from Shipped to Delivered', 2, '2026-05-18 22:27:21'),
(83, 19, 'Delivered', 'Status changed from Shipped to Delivered', 2, '2026-05-18 22:27:22'),
(84, 18, 'Delivered', 'Status changed from Shipped to Delivered', 2, '2026-05-18 22:27:23'),
(85, 16, 'Delivered', 'Status changed from Shipped to Delivered', 2, '2026-05-18 22:27:23'),
(86, 15, 'Delivered', 'Status changed from Shipped to Delivered', 2, '2026-05-18 22:27:24'),
(87, 14, 'Delivered', 'Status changed from Shipped to Delivered', 2, '2026-05-18 22:27:24'),
(88, 24, 'Pending Payment', 'Status changed from Draft to Pending Payment', NULL, '2026-05-18 22:37:22'),
(89, 25, 'Pending Payment', 'Status changed from Draft to Pending Payment', NULL, '2026-05-18 22:44:48');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `key` varchar(100) NOT NULL,
  `value` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `key`, `value`, `description`, `created_at`, `updated_at`) VALUES
(1, 'site_name', 'Poultry Shop', 'Website name', '2026-05-16 04:29:55', '2026-05-16 04:29:55'),
(2, 'site_email', 'orders@poultryshop.com', 'Contact email', '2026-05-16 04:29:55', '2026-05-16 04:29:55'),
(3, 'currency', 'PHP', 'Default currency', '2026-05-16 04:29:55', '2026-05-16 04:29:55'),
(4, 'tax_rate', '0.12', 'Tax rate as decimal', '2026-05-16 04:29:55', '2026-05-16 04:29:55'),
(5, 'shipping_fee', '50.00', 'Default shipping fee', '2026-05-16 04:29:55', '2026-05-16 04:29:55'),
(6, 'min_order_amount', '100.00', 'Minimum order amount', '2026-05-16 04:29:55', '2026-05-16 04:29:55'),
(7, 'enable_cod', '1', 'Enable cash on delivery', '2026-05-16 04:29:55', '2026-05-16 04:29:55'),
(8, 'enable_gcash', '1', 'Enable GCash payments', '2026-05-16 04:29:55', '2026-05-16 04:29:55'),
(9, 'enable_card', '1', 'Enable card payments', '2026-05-16 04:29:55', '2026-05-16 04:29:55'),
(10, 'low_stock_threshold', '10', 'Alert when stock below this level', '2026-05-16 04:29:55', '2026-05-16 04:29:55'),
(11, 'session_timeout', '1800', 'Session timeout in seconds', '2026-05-16 04:29:55', '2026-05-16 04:29:55'),
(12, 'max_cart_items', '20', 'Maximum items allowed in cart', '2026-05-16 04:29:55', '2026-05-16 04:29:55'),
(13, 'enable_reviews', '1', 'Enable product reviews', '2026-05-16 04:29:55', '2026-05-16 04:29:55'),
(14, 'require_email_verification', '0', 'Require email verification for registration', '2026-05-16 04:29:55', '2026-05-16 04:29:55'),
(15, 'maintenance_mode', '0', 'Website maintenance mode', '2026-05-16 04:29:55', '2026-05-16 04:29:55');

-- --------------------------------------------------------

--
-- Table structure for table `shopping_cart`
--

CREATE TABLE `shopping_cart` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stock_movements`
--

CREATE TABLE `stock_movements` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `movement_type` enum('in','out','adjustment') NOT NULL,
  `quantity` int(11) NOT NULL,
  `reference` varchar(255) DEFAULT NULL,
  `user_name` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `stock_movements`
--

INSERT INTO `stock_movements` (`id`, `product_id`, `product_name`, `movement_type`, `quantity`, `reference`, `user_name`, `created_at`) VALUES
(1, 11, 'B-MEG Integra 1000 – Chick Booster Crumble', 'in', 100, 'Initial Stock', 'Girlly Fernandez', '2026-05-16 06:14:31'),
(2, 12, 'B-MEG Integra 2000 – Starter Crumble (Immuno-Growth Boosters)', 'in', 100, 'Initial Stock', 'Girlly Fernandez', '2026-05-16 06:18:56'),
(3, 13, 'B-MEG Integra 2500 – Transition Pellet (Anti-Stress + Immuno-Boosters)', 'in', 100, 'Initial Stock', NULL, '2026-05-17 09:42:17'),
(4, 14, ' B-MEG Integra 3000 – Maintenance / Free-Range Finisher Pellet', 'in', 100, 'Initial Stock', NULL, '2026-05-17 09:44:48'),
(5, 15, ' B-MEG Integra 3000 PLUS+ – Mixed Pellet & Grain', 'in', 100, 'Initial Stock', NULL, '2026-05-17 09:48:39'),
(7, 17, 'B-MEG Integra 4000 – Breeder / Layer Pellet (Embryo Developer)', 'in', 80, 'Initial Stock', NULL, '2026-05-17 09:57:34'),
(8, 18, 'B-MEG Integra 5000 – High Protein Pellet', 'in', 60, 'Initial Stock', NULL, '2026-05-17 09:58:46'),
(18, 28, 'VitMinPRO Water Soluble Powder – Health Enhancer (Multivitamins + Minerals + Electrolytes + Amino Acids + Probiotics)', 'in', 50, 'Initial Stock', NULL, '2026-05-17 14:46:46'),
(21, 31, 'VitMinPRO Multivitamins + Minerals + Electrolytes + Amino Acids + Probiotics', 'in', 40, 'Initial Stock', NULL, '2026-05-17 14:49:23'),
(22, 32, 'VitMinPRO Water Soluble Powder – Health Enhancer (Multivitamins + Minerals + Electrolytes + Amino Acids + Probiotics)', 'in', 60, 'Initial Stock', NULL, '2026-05-17 14:52:19'),
(24, 34, 'Thunderbird Red Cell Vitamin‑Iron‑Mineral Liquid Supplement', 'in', 60, 'Initial Stock', NULL, '2026-05-17 14:59:49'),
(25, 35, 'Thunderbird Red Cell Vitamin‑Iron‑Mineral Liquid Supplement', 'in', 70, 'Initial Stock', NULL, '2026-05-17 15:01:00'),
(26, 36, 'Hammer Tablet – Super Strength & Energy Booster', 'in', 50, 'Initial Stock', NULL, '2026-05-17 15:03:10'),
(29, 39, 'Water Feeder ', 'in', 70, 'Initial Stock', NULL, '2026-05-17 15:25:58'),
(30, 39, 'Water Feeder ', 'out', 2, 'Order Delivered #3', 'Ruffa Calilung', '2026-05-18 00:33:32'),
(31, 32, 'VitMinPRO Water Soluble Powder – Health Enhancer (Multivitamins + Minerals + Electrolytes + Amino Acids + Probiotics)', 'out', 1, 'Order Delivered #3', 'Ruffa Calilung', '2026-05-18 00:33:32'),
(33, 40, 'Water Feeder ', 'in', 100, 'Initial Stock', NULL, '2026-05-18 02:26:24'),
(34, 41, 'Water Feeder ', 'in', 60, 'Initial Stock', NULL, '2026-05-18 02:27:36'),
(35, 42, 'Water Feeder ', 'in', 85, 'Initial Stock', NULL, '2026-05-18 02:29:03'),
(36, 43, 'Water Feeder ', 'in', 68, 'Initial Stock', NULL, '2026-05-18 02:30:33'),
(37, 44, 'Water Feeder ', 'in', 57, 'Initial Stock', NULL, '2026-05-18 02:31:53'),
(38, 18, 'B-MEG Integra 5000 – High Protein Pellet', 'out', 1, 'Order Delivered #4', 'Karen Montes', '2026-05-18 03:27:55'),
(39, 36, 'Hammer Tablet – Super Strength & Energy Booster', 'out', 1, 'Order Delivered #4', 'Karen Montes', '2026-05-18 03:27:55'),
(41, 11, 'B‑MEG Integra 1000 – Chick Booster Crumble (Immuno‑Boosters)', 'out', 1, 'Order Delivered #5', 'Karen Montes', '2026-05-18 03:28:49'),
(42, 44, 'Water Feeder ', 'out', 1, 'Order Delivered #7', 'Ruffa Calilung', '2026-05-18 04:45:11'),
(43, 17, 'B-MEG Integra 4000 – Breeder / Layer Pellet (Embryo Developer)', 'out', 20, 'Order Delivered #6', 'Ruffa Calilung', '2026-05-18 04:45:12'),
(44, 44, 'Water Feeder ', 'out', 1, 'Order Delivered #6', 'Ruffa Calilung', '2026-05-18 04:45:12'),
(46, 44, 'Water Feeder ', 'out', 1, 'Order Delivered #8', 'Ruffa Calilung', '2026-05-18 04:49:24'),
(47, 40, 'Water Feeder ', 'in', 10, 'Restock from  - atut', NULL, '2026-05-18 05:41:29'),
(48, 34, 'Thunderbird Red Cell Vitamin‑Iron‑Mineral Liquid Supplement', 'in', 40, 'Restock from  - tut', NULL, '2026-05-18 05:42:56'),
(49, 35, 'Thunderbird Red Cell Vitamin‑Iron‑Mineral Liquid Supplement', 'in', 20, 'Restock from ThunderBird - hahahhaha', NULL, '2026-05-18 18:10:54'),
(50, 43, 'Water Feeder ', 'out', 1, 'Order Delivered #13', 'renren david', '2026-05-18 20:59:02'),
(51, 42, 'Water Feeder ', 'out', 1, 'Order Delivered #13', 'renren david', '2026-05-18 20:59:02'),
(52, 34, 'Thunderbird Red Cell Vitamin‑Iron‑Mineral Liquid Supplement', 'out', 1, 'Order Delivered #13', 'renren david', '2026-05-18 20:59:02'),
(53, 44, 'Water Feeder ', 'out', 1, '0', 'System (Customer)', '2026-05-18 21:49:06'),
(54, 43, 'Water Feeder ', 'out', 1, '0', 'System (Customer)', '2026-05-18 21:49:06'),
(55, 42, 'Water Feeder ', 'out', 1, '0', 'System (Customer)', '2026-05-18 21:49:06'),
(56, 44, 'Water Feeder ', 'out', 1, '0', 'System (Customer)', '2026-05-18 21:51:13'),
(57, 44, 'Water Feeder ', 'out', 1, 'Order Delivered #17', 'renren david', '2026-05-18 21:55:05'),
(58, 35, 'Thunderbird Red Cell Vitamin‑Iron‑Mineral Liquid Supplement', 'out', 1, 'Order Placed #ORD-20260519-6A0B8BEA0315B', 'System (Customer)', '2026-05-18 22:00:10'),
(59, 44, 'Water Feeder ', 'out', 1, 'Order Placed #ORD-20260519-6A0B8D3B73688', 'System (Customer)', '2026-05-18 22:05:47'),
(60, 44, 'Water Feeder ', 'out', 1, 'Order Placed #ORD-20260519-6A0B8D4D4ECD1', 'System (Customer)', '2026-05-18 22:06:05'),
(61, 32, 'VitMinPRO Water Soluble Powder – Health Enhancer (Multivitamins + Minerals + Electrolytes + Amino Acids + Probiotics)', 'out', 1, 'Order Placed #ORD-20260519-6A0B8F3C5ACF4', 'System (Customer)', '2026-05-18 22:14:20'),
(62, 45, ' Metal Chicken Feeder ', 'in', 100, 'Initial Stock', NULL, '2026-05-18 22:22:43'),
(63, 44, 'Water Feeder ', 'out', 50, 'Order Delivered #12', 'renren david', '2026-05-18 22:27:18'),
(64, 32, 'VitMinPRO Water Soluble Powder – Health Enhancer (Multivitamins + Minerals + Electrolytes + Amino Acids + Probiotics)', 'out', 1, 'Order Delivered #21', 'renren david', '2026-05-18 22:27:21'),
(65, 44, 'Water Feeder ', 'out', 1, 'Order Delivered #20', 'renren david', '2026-05-18 22:27:21'),
(66, 44, 'Water Feeder ', 'out', 1, 'Order Delivered #19', 'renren david', '2026-05-18 22:27:22'),
(67, 35, 'Thunderbird Red Cell Vitamin‑Iron‑Mineral Liquid Supplement', 'out', 1, 'Order Delivered #18', 'renren david', '2026-05-18 22:27:23'),
(68, 44, 'Water Feeder ', 'out', 1, 'Order Delivered #16', 'renren david', '2026-05-18 22:27:23'),
(69, 43, 'Water Feeder ', 'out', 1, 'Order Delivered #16', 'renren david', '2026-05-18 22:27:23'),
(70, 42, 'Water Feeder ', 'out', 1, 'Order Delivered #16', 'renren david', '2026-05-18 22:27:23'),
(71, 44, 'Water Feeder ', 'out', 1, 'Order Delivered #15', 'renren david', '2026-05-18 22:27:24'),
(72, 32, 'VitMinPRO Water Soluble Powder – Health Enhancer (Multivitamins + Minerals + Electrolytes + Amino Acids + Probiotics)', 'out', 1, 'Order Delivered #14', 'renren david', '2026-05-18 22:27:24'),
(73, 44, 'Water Feeder ', 'out', 1, '0', 'System (Customer)', '2026-05-18 22:33:20'),
(74, 45, ' Metal Chicken Feeder ', 'out', 2, '0', 'System (Customer)', '2026-05-18 22:33:20'),
(75, 45, ' Metal Chicken Feeder ', 'out', 1, '0', 'System (Customer)', '2026-05-18 22:33:50'),
(76, 45, ' Metal Chicken Feeder ', 'out', 1, '0', 'System (Customer)', '2026-05-18 22:37:03'),
(77, 43, 'Water Feeder ', 'out', 1, '0', 'System (Customer)', '2026-05-18 22:44:39');

-- --------------------------------------------------------

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_role` (`role`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `inventory_items`
--
ALTER TABLE `inventory_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_name` (`name`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_quantity` (`current_quantity`),
  ADD KEY `idx_barcode` (`barcode`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_customer_id` (`customer_id`),
  ADD KEY `idx_order_number` (`order_number`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_payment_method` (`payment_method`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_order_id` (`order_id`),
  ADD KEY `idx_product_id` (`product_id`);

--
-- Indexes for table `order_status_history`
--
ALTER TABLE `order_status_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `changed_by` (`changed_by`),
  ADD KEY `idx_order_id` (`order_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `key` (`key`),
  ADD KEY `idx_key` (`key`);

--
-- Indexes for table `shopping_cart`
--
ALTER TABLE `shopping_cart`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_cart_item` (`customer_id`,`product_id`),
  ADD KEY `idx_customer_id` (`customer_id`),
  ADD KEY `idx_product_id` (`product_id`);

--
-- Indexes for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_product_id` (`product_id`),
  ADD KEY `idx_movement_type` (`movement_type`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `inventory_items`
--
ALTER TABLE `inventory_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `order_status_history`
--
ALTER TABLE `order_status_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=90;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `shopping_cart`
--
ALTER TABLE `shopping_cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `stock_movements`
--
ALTER TABLE `stock_movements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=78;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `inventory_items` (`id`);

--
-- Constraints for table `order_status_history`
--
ALTER TABLE `order_status_history`
  ADD CONSTRAINT `order_status_history_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_status_history_ibfk_2` FOREIGN KEY (`changed_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `shopping_cart`
--
ALTER TABLE `shopping_cart`
  ADD CONSTRAINT `shopping_cart_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `shopping_cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `inventory_items` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD CONSTRAINT `stock_movements_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `inventory_items` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
