-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 15, 2026 at 12:58 PM
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
-- Database: `tesda`
--

-- --------------------------------------------------------

--
-- Table structure for table `auditlogs`
--

CREATE TABLE `auditlogs` (
  `audit_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` text DEFAULT NULL,
  `timestamp` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chatbot`
--

CREATE TABLE `chatbot` (
  `chatbot_id` int(11) NOT NULL,
  `category` varchar(255) DEFAULT NULL,
  `template_name` varchar(255) DEFAULT NULL,
  `input_label` varchar(255) DEFAULT NULL,
  `input_type` varchar(255) DEFAULT NULL,
  `sql_query` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chatbot`
--

INSERT INTO `chatbot` (`chatbot_id`, `category`, `template_name`, `input_label`, `input_type`, `sql_query`, `created_at`, `updated_at`) VALUES
(1, 'INVENTORY', 'What items are available in the inventory?', NULL, NULL, 'SELECT item_name AS Item, COUNT(*) AS Available\r\nFROM items\r\nWHERE status = \'Available\'\r\nGROUP BY item_name\r\nORDER BY item_name;', '2025-11-24 07:53:24', '2025-11-26 05:04:51'),
(2, 'INVENTORY', 'Is this item is currently Available?', 'Please input the Serial number\r\n', 'text', 'SELECT CASE \r\n    WHEN status = \'Available\' THEN CONCAT(item_name, \" with Serial No: \", serial_no, \" is available.\")\r\n    ELSE CONCAT(item_name, \" (Serial No: \", serial_no, \") is not available. Current status: \", status)\r\nEND AS answer\r\nFROM items\r\nWHERE serial_no = :value', '2025-11-24 07:53:24', '2025-11-26 05:09:23'),
(3, 'INVENTORY', 'How many units of this item are left?', 'Item Name', 'text', 'SELECT CONCAT(items.item_name, \' has \', COUNT(*) , \' units left\') AS Answer\r\nFROM items\r\nWHERE items.item_name = :value\r\nGROUP BY items.item_name', '2025-11-24 07:53:24', '2025-11-26 05:35:10'),
(5, 'INVENTORY', 'Who is the custodian of this item?', 'Please Input the Serial number of the item', 'text', 'SELECT \r\n    CONCAT(\'The custodian of \', i.serial_no, \' is \', f.issued_by) AS answer\r\nFROM issuedlog i\r\nJOIN formrecords f \r\n    ON i.reference_no = f.reference_no\r\nWHERE i.serial_no = :value\r\nORDER BY i.issue_id DESC\r\nLIMIT 1;\r\n', '2025-11-24 07:53:24', '2025-11-26 06:08:18'),
(6, 'INVENTORY', 'Can I request an item from the inventory?', NULL, NULL, 'SELECT \"You can request an item by submitting a request form in the Forms section.\" AS answer', '2025-11-24 07:53:24', '2025-11-24 07:53:24'),
(7, 'INVENTORY', 'What items are low on stock?', NULL, NULL, 'SELECT \r\n    IF(\r\n        COUNT(*) = 0,\r\n        \'There are no items that are in low stock.\',\r\n        GROUP_CONCAT(CONCAT(item_name, \' has only \', quantity, \' stocks left\') SEPARATOR \'\\n\')\r\n    ) AS `Chat Bot`\r\nFROM propertyinventory\r\nWHERE quantity < 10;', '2025-11-24 07:53:24', '2025-11-26 10:09:30'),
(8, 'MAINTENANCE', 'How do I report a damaged tool/equipment?', NULL, NULL, 'SELECT \"You can report damaged items by submitting a maintenance request in the Maintenance section.\" AS answer', '2025-11-24 07:53:24', '2025-11-24 07:53:24'),
(9, 'MAINTENANCE', 'What items are under maintenance right now?', NULL, NULL, 'SELECT GROUP_CONCAT(item_name) AS answer FROM maintenance WHERE status = \"in progress\"', '2025-11-24 07:53:24', '2025-11-24 07:53:24'),
(10, 'MAINTENANCE', 'How long is the repair duration for [item]?', 'Item Name', 'text', 'SELECT CONCAT(\"The estimated repair duration for \", item_name, \" is \", repair_days, \" days.\") AS answer FROM maintenance WHERE item_name = :value', '2025-11-24 07:53:24', '2025-11-24 07:53:24'),
(11, 'MAINTENANCE', 'Who is assigned to the maintenance request?', 'Item Name', 'text', 'SELECT CONCAT(\"The person assigned to \", item_name, \" is \", assigned_to) AS answer FROM maintenance WHERE item_name = :value', '2025-11-24 07:53:24', '2025-11-24 07:53:24'),
(12, 'MAINTENANCE', 'What is the status of my maintenance ticket?', 'Ticket ID', 'text', 'SELECT CONCAT(\"The status of ticket \", ticket_id, \" is \", status) AS answer FROM maintenance WHERE ticket_id = :value', '2025-11-24 07:53:24', '2025-11-24 07:53:24'),
(13, 'MAINTENANCE', 'Can I schedule maintenance for [item]?', 'Item Name', 'text', 'SELECT \"Yes, you can schedule maintenance by creating a new request in the Maintenance section.\" AS answer', '2025-11-24 07:53:24', '2025-11-24 07:53:24'),
(14, 'CHATBOT', 'What can you do?', NULL, NULL, 'SELECT \"I can answer questions about inventory, maintenance, and issued items.\" AS answer', '2025-11-24 07:53:24', '2025-11-24 07:53:24'),
(15, 'CHATBOT', 'How do I use this system?', NULL, NULL, 'SELECT \"You can use the system by navigating through the dashboard and forms.\" AS answer', '2025-11-24 07:53:24', '2025-11-24 07:53:24'),
(16, 'CHATBOT', 'How do I search for an item?', NULL, NULL, 'SELECT \"You can search for items in the inventory search bar.\" AS answer', '2025-11-24 07:53:24', '2025-11-24 07:53:24'),
(17, 'CHATBOT', 'How do I submit a report or request?', NULL, NULL, 'SELECT \"Reports and requests can be submitted via the Forms section.\" AS answer', '2025-11-24 07:53:24', '2025-11-24 07:53:24'),
(18, 'CHATBOT', 'Who can I contact for system issues?', NULL, NULL, 'SELECT \"Please contact the system administrator or IT support for assistance.\" AS answer', '2025-11-24 07:53:24', '2025-11-24 07:53:24'),
(20, 'ISSUED ITEMS', 'Who borrowed the Item?', 'Serial Number of the item', 'text', 'SELECT\r\n    CASE \r\n        WHEN i.status = \'Available\' THEN CONCAT(i.item_name, \' (Serial No: \', i.serial_no, \') is currently available. No one has borrowed it.\')\r\n        WHEN i.status = \'Issued\' THEN CONCAT(s.student_name, \' borrowed this item.\')\r\n    END AS answer\r\nFROM items i\r\nLEFT JOIN issuedlog il \r\n    ON i.serial_no = il.serial_no \r\n    AND il.actual_return_date IS NULL  -- only currently borrowed items\r\nLEFT JOIN student s \r\n    ON il.student_id = s.student_id\r\nWHERE i.serial_no = ?\r\nLIMIT 1;', '2025-11-24 07:53:24', '2025-12-03 06:27:06'),
(21, 'ISSUED ITEMS', 'When is the Expected return date for the item?', 'Please input the Serial Number', 'text', 'SELECT CONCAT(\"The return date for \", serial_no, \" is \", DATE_FORMAT(return_date, \'%M %d, %Y\')) AS answer FROM issuedlog WHERE serial_no = :value', '2025-11-24 07:53:24', '2025-11-26 10:00:09'),
(22, 'ISSUED ITEMS', 'How do I request to borrow an item?', NULL, NULL, 'SELECT \"You can request to borrow an item by submitting a request form.\" AS answer', '2025-11-24 07:53:24', '2025-11-24 07:53:24'),
(24, 'ISSUED ITEMS', 'What items were issued today?', NULL, NULL, 'SELECT CONCAT(\r\n    \'Item - \', i.item_name, \' | \', l.serial_no\r\n) AS ChatBot\r\nFROM issuedlog l\r\nJOIN items i \r\n    ON l.serial_no = i.serial_no\r\nWHERE DATE(l.issued_date) = CURDATE();\r\n', '2025-11-24 07:53:24', '2025-11-26 10:07:05'),
(25, 'FORM / RECORDS', 'Where can I fill out a request form?', NULL, NULL, 'SELECT \"You can fill out request forms in the Forms section.\" AS answer', '2025-11-24 07:53:24', '2025-11-24 07:53:24');

-- --------------------------------------------------------

--
-- Table structure for table `chatbotqueries`
--

CREATE TABLE `chatbotqueries` (
  `query_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `category` varchar(255) DEFAULT NULL,
  `question` text DEFAULT NULL,
  `response` text DEFAULT NULL,
  `timestamp` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chatbotqueries`
--

INSERT INTO `chatbotqueries` (`query_id`, `user_id`, `category`, `question`, `response`, `timestamp`) VALUES
(1, NULL, 'Inventory', 'What items are available in the inventory?', 'You can check the `items` table. For example, items with status = \"Available\" are ready to use.', '2025-11-24 08:13:16'),
(2, NULL, 'Inventory', 'Is [item name] in stock?', 'Query the `items` table: SELECT * FROM items WHERE item_name = \"[item name]\" AND status = \"Available\";', '2025-11-24 08:13:16'),
(3, NULL, 'Inventory', 'How many units of [item] are left?', 'Check the `items` table: SELECT stock FROM items WHERE item_name = \"[item]\";', '2025-11-24 08:13:16'),
(4, NULL, 'Inventory', 'When was the inventory last updated?', 'Check the `updated_at` column in the `items` table for the most recent update.', '2025-11-24 08:13:16'),
(5, NULL, 'Inventory', 'Who is the custodian of [item]?', 'You can check the `issuedlog` table joined with `student` table: SELECT s.student_name FROM issuedlog i JOIN student s ON i.student_id = s.student_id WHERE i.serial_no = (SELECT serial_no FROM items WHERE item_name = \"[item]\");', '2025-11-24 08:13:16'),
(6, NULL, 'Inventory', 'Can I request an item from the inventory?', 'Yes, submit a request in the `requests` table or contact the Property Custodian.', '2025-11-24 08:13:16'),
(7, NULL, 'Inventory', 'What items are low on stock?', 'Query items table: SELECT item_name, stock FROM items WHERE stock <= 5;', '2025-11-24 08:13:16'),
(8, NULL, 'Maintenance', 'How do I report a damaged tool/equipment?', 'Insert a record into `damagereports` table with item_id, reported_by, and description.', '2025-11-24 08:13:16'),
(9, NULL, 'Maintenance', 'What items are under maintenance right now?', 'Query the `maintenancelog` table for items not yet completed: SELECT serial_no, issue_type FROM maintenancelog WHERE date_completed IS NULL;', '2025-11-24 08:13:16'),
(10, NULL, 'Maintenance', 'How long is the repair duration for [item]?', 'Check `maintenancelog` table: SELECT DATEDIFF(date_completed, date_reported) FROM maintenancelog WHERE serial_no = (SELECT serial_no FROM items WHERE item_name = \"[item]\");', '2025-11-24 08:13:16'),
(11, NULL, 'Maintenance', 'Who is assigned to the maintenance request?', 'Check `maintenancerecords` table joined with `users`: SELECT u.full_name FROM maintenancerecords m JOIN users u ON m.performed_by = u.user_id WHERE m.item_id = (SELECT item_id FROM items WHERE item_name = \"[item]\");', '2025-11-24 08:13:16'),
(12, NULL, 'Maintenance', 'What is the status of my maintenance ticket?', 'Check the `maintenancerecords` or `maintenancelog` table depending on tracking method.', '2025-11-24 08:13:16'),
(13, NULL, 'Maintenance', 'Can I schedule maintenance for [item]?', 'Yes, insert a new record into `maintenancerecords` with item_id, type, date, description, and performed_by.', '2025-11-24 08:13:16'),
(14, NULL, 'Issued Items', 'What items are currently issued to me?', 'Check the `issuedlog` table for your student_id or user_id with no return_date.', '2025-11-24 08:13:16'),
(15, NULL, 'Issued Items', 'Who borrowed [item]?', 'Query `issuedlog` joined with `student` table: SELECT s.student_name FROM issuedlog i JOIN student s ON i.student_id = s.student_id WHERE i.serial_no = (SELECT serial_no FROM items WHERE item_name = \"[item]\");', '2025-11-24 08:13:16'),
(16, NULL, 'Issued Items', 'When is the return date for [issued item]?', 'Check the `issuedlog` table: SELECT return_date FROM issuedlog WHERE serial_no = (SELECT serial_no FROM items WHERE item_name = \"[issued item]\");', '2025-11-24 08:13:16'),
(17, NULL, 'Issued Items', 'How do I request to borrow an item?', 'Insert a new record into `requests` table or contact the Property Custodian.', '2025-11-24 08:13:16'),
(18, NULL, 'Issued Items', 'Can I extend the borrowing period?', 'Yes, update the `return_date` in `issuedlog` table with approval from the custodian.', '2025-11-24 08:13:16'),
(19, NULL, 'Issued Items', 'What items were issued today?', 'Query `issuedlog` table: SELECT serial_no, property_no FROM issuedlog WHERE issued_date = CURDATE();', '2025-11-24 08:13:16'),
(20, NULL, 'Form / Records', 'Where can I fill out a request form?', 'Forms are recorded in the `formrecords` table. Use the Add Form function.', '2025-11-24 08:13:16'),
(21, NULL, 'Form / Records', 'How do I check my submitted forms?', 'Query `formrecords` table: SELECT * FROM formrecords WHERE student_name = \"[your name]\";', '2025-11-24 08:13:16'),
(22, NULL, 'Form / Records', 'How do I check the status of my form?', 'Check the `status` column in `formrecords` table.', '2025-11-24 08:13:16'),
(23, NULL, 'Chatbot', 'What can you do?', 'I can answer inventory, maintenance, issued items, and form-related questions.', '2025-11-24 08:13:16'),
(24, NULL, 'Chatbot', 'How do I use this system?', 'Use the dashboard menus to navigate inventory, issued items, maintenance, and forms.', '2025-11-24 08:13:16'),
(25, NULL, 'Chatbot', 'How do I search for an item?', 'Use the search function in the inventory section or query the `items` table.', '2025-11-24 08:13:16'),
(26, NULL, 'Chatbot', 'How do I submit a report or request?', 'Submit via `damagereports` or `requests` table depending on the need.', '2025-11-24 08:13:16'),
(27, NULL, 'Chatbot', 'Who can I contact for system issues?', 'Contact the Property Custodian or system administrator.', '2025-11-24 08:13:16');

-- --------------------------------------------------------

--
-- Table structure for table `damagefixes`
--

CREATE TABLE `damagefixes` (
  `id` int(10) UNSIGNED NOT NULL,
  `damage_type` varchar(100) NOT NULL,
  `recommended_action` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `damagereports`
--

CREATE TABLE `damagereports` (
  `id` int(11) NOT NULL,
  `serial_no` varchar(255) NOT NULL,
  `reported_at` date NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `formrecords`
--

CREATE TABLE `formrecords` (
  `form_id` bigint(20) NOT NULL,
  `item_count` int(11) NOT NULL DEFAULT 1,
  `student_name` varchar(100) NOT NULL,
  `issued_by` varchar(100) NOT NULL,
  `form_type` varchar(10) NOT NULL,
  `reference_no` varchar(50) DEFAULT NULL,
  `status` enum('Active','Archived') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `formrecords`
--

INSERT INTO `formrecords` (`form_id`, `item_count`, `student_name`, `issued_by`, `form_type`, `reference_no`, `status`, `created_at`, `updated_at`) VALUES
(25, 2, 'Jes', 'Cedric Sadsad', 'ICS', 'RFN-00019', 'Active', '2026-01-06 01:39:27', '2026-01-06 01:39:27'),
(26, 2, 'Cedric John P. Sadsad', 'Cedric Sadsad', 'ICS', 'RFN-00020', 'Active', '2026-01-06 01:40:10', '2026-01-06 01:40:10'),
(27, 2, 'Cedric John P. Sadsad', 'Cedric Sadsad', 'ICS', 'RFN-00021', 'Active', '2026-01-06 01:44:54', '2026-01-06 01:44:54');

-- --------------------------------------------------------

--
-- Table structure for table `issuedlog`
--

CREATE TABLE `issuedlog` (
  `issue_id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `serial_no` varchar(255) DEFAULT NULL,
  `property_no` varchar(255) DEFAULT NULL,
  `form_type` enum('ICS','PAR') DEFAULT NULL,
  `issued_date` date NOT NULL,
  `return_date` date DEFAULT NULL,
  `actual_return_date` date DEFAULT NULL,
  `reference_no` varchar(50) DEFAULT NULL,
  `usage_hours` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `issuedlog`
--

INSERT INTO `issuedlog` (`issue_id`, `student_id`, `serial_no`, `property_no`, `form_type`, `issued_date`, `return_date`, `actual_return_date`, `reference_no`, `usage_hours`, `created_at`, `updated_at`) VALUES
(25, 3, 'SN0001', '00001', 'ICS', '2025-11-26', '2025-11-30', '2025-12-02', 'RFN-00001', 0, '2025-11-26 01:52:23', '2025-12-02 08:07:00'),
(26, 3, 'SN0002', '00001', 'ICS', '2025-11-26', '2025-11-30', '2025-12-02', 'RFN-00001', 0, '2025-11-26 01:52:23', '2025-12-02 08:07:08'),
(27, 3, 'SN0001', '00001', 'ICS', '2025-12-03', '2025-12-21', '2025-12-02', 'RFN-00002', 0, '2025-12-02 08:05:21', '2025-12-02 08:05:49'),
(28, 3, 'SN0002', '00001', 'ICS', '2025-12-03', '2025-12-21', '2025-12-02', 'RFN-00002', 0, '2025-12-02 08:05:21', '2025-12-02 08:06:03'),
(29, 3, 'SN0003', '00001', 'ICS', '2025-12-03', '2025-12-21', '2025-12-02', 'RFN-00002', 0, '2025-12-02 08:05:21', '2025-12-02 08:06:12'),
(30, 3, 'SN0001', '00001', 'ICS', '2025-12-03', '2025-12-10', '2025-12-02', 'RFN-00003', 0, '2025-12-02 10:54:49', '2025-12-02 11:11:37'),
(31, 3, 'SN0002', '00001', 'ICS', '2025-12-03', '2025-12-10', '2025-12-02', 'RFN-00003', 0, '2025-12-02 10:54:49', '2025-12-02 11:11:52'),
(32, 3, 'SN0003', '00001', 'ICS', '2025-12-03', '2025-12-10', '2025-12-02', 'RFN-00003', 0, '2025-12-02 10:54:49', '2025-12-02 11:11:59'),
(33, 3, 'SN0011', '00002', 'PAR', '2025-12-03', '2025-12-17', '2025-12-02', 'RFN-00004', 0, '2025-12-02 11:21:16', '2025-12-02 11:21:32'),
(34, 3, 'SN0012', '00002', 'PAR', '2025-12-03', '2025-12-17', '2025-12-02', 'RFN-00004', 0, '2025-12-02 11:21:16', '2025-12-02 11:21:40'),
(35, 3, 'SN0013', '00002', 'PAR', '2025-12-03', '2025-12-17', '2025-12-02', 'RFN-00004', 0, '2025-12-02 11:21:16', '2025-12-02 11:21:45'),
(36, 3, 'SN0011', '00002', 'PAR', '2025-12-03', '2025-12-10', '2025-12-02', 'RFN-00005', 0, '2025-12-02 11:30:17', '2025-12-02 11:30:40'),
(37, 3, 'SN0012', '00002', 'PAR', '2025-12-03', '2025-12-10', '2025-12-02', 'RFN-00005', 0, '2025-12-02 11:30:17', '2025-12-02 11:30:52'),
(38, 3, 'SN0013', '00002', 'PAR', '2025-12-03', '2025-12-10', '2025-12-02', 'RFN-00005', 0, '2025-12-02 11:30:17', '2025-12-02 11:30:59'),
(39, 3, 'SN0001', '00001', 'ICS', '2025-12-03', '2025-12-10', '2025-12-02', 'RFN-00006', 0, '2025-12-02 11:54:57', '2025-12-02 11:55:48'),
(40, 3, 'SN0002', '00001', 'ICS', '2025-12-03', '2025-12-10', '2025-12-02', 'RFN-00006', 0, '2025-12-02 11:54:57', '2025-12-02 11:55:56'),
(41, 3, 'SN0003', '00001', 'ICS', '2025-12-03', '2025-12-10', '2025-12-02', 'RFN-00006', 0, '2025-12-02 11:54:57', '2025-12-02 11:56:02'),
(42, 3, 'SN0011', '00002', 'PAR', '2025-12-03', '2025-12-10', '2025-12-02', 'RFN-00007', 0, '2025-12-02 11:55:32', '2025-12-02 11:56:16'),
(43, 3, 'SN0012', '00002', 'PAR', '2025-12-03', '2025-12-10', '2025-12-02', 'RFN-00007', 0, '2025-12-02 11:55:32', '2025-12-02 11:56:21'),
(44, 3, 'SN0013', '00002', 'PAR', '2025-12-03', '2025-12-10', '2025-12-02', 'RFN-00007', 0, '2025-12-02 11:55:32', '2025-12-02 11:56:27'),
(45, 3, 'SN0001', '00001', 'ICS', '2025-12-03', '2025-12-10', '2025-12-02', 'RFN-00008', 0, '2025-12-02 11:57:38', '2025-12-02 11:57:46'),
(46, 3, 'SN0002', '00001', 'ICS', '2025-12-03', '2025-12-10', '2025-12-02', 'RFN-00008', 0, '2025-12-02 11:57:38', '2025-12-02 11:57:51'),
(47, 3, 'SN0001', '00001', 'ICS', '2025-12-03', '2025-12-10', '2025-12-03', 'RFN-00009', 0, '2025-12-02 21:43:43', '2025-12-02 21:44:45'),
(48, 3, 'SN0002', '00001', 'ICS', '2025-12-03', '2025-12-10', '2025-12-03', 'RFN-00009', 0, '2025-12-02 21:43:43', '2025-12-02 21:45:07'),
(49, 3, 'SN0003', '00001', 'ICS', '2025-12-03', '2025-12-10', '2025-12-03', 'RFN-00009', 0, '2025-12-02 21:43:43', '2025-12-02 21:45:14'),
(50, 3, 'SN0011', '00002', 'PAR', '2025-12-03', '2025-12-04', '2025-12-03', 'RFN-000010', 0, '2025-12-02 21:53:23', '2025-12-02 21:53:42'),
(51, 3, 'SN0012', '00002', 'PAR', '2025-12-03', '2025-12-04', '2025-12-03', 'RFN-000010', 0, '2025-12-02 21:53:24', '2025-12-02 21:53:47'),
(52, 3, 'SN0013', '00002', 'PAR', '2025-12-03', '2025-12-04', '2025-12-03', 'RFN-000010', 0, '2025-12-02 21:53:24', '2025-12-02 21:53:51'),
(53, 3, 'SN0011', '00002', 'PAR', '2025-12-03', '2025-12-10', '2025-12-03', 'RFN-00011', 0, '2025-12-02 21:57:24', '2025-12-02 21:57:36'),
(54, 3, 'SN0012', '00002', 'PAR', '2025-12-03', '2025-12-10', '2025-12-03', 'RFN-00012', 0, '2025-12-02 22:03:27', '2025-12-02 22:03:40'),
(55, 3, 'SN0013', '00002', 'PAR', '2025-12-03', '2025-12-10', '2025-12-03', 'RFN-00012', 0, '2025-12-02 22:03:27', '2025-12-02 22:03:47'),
(56, 3, 'SN0012', '00002', 'PAR', '2025-12-03', '2025-12-10', '2025-12-03', 'RFN-00013', 0, '2025-12-02 22:05:42', '2025-12-02 22:05:51'),
(57, 3, 'SN0013', '00002', 'PAR', '2025-12-03', '2025-12-10', '2025-12-03', 'RFN-00013', 0, '2025-12-02 22:05:42', '2025-12-02 22:05:57'),
(58, 3, 'SN0012', '00002', 'PAR', '2025-12-03', '2025-12-10', '2025-12-03', 'RFN-00014', 0, '2025-12-02 22:08:17', '2025-12-02 22:08:25'),
(59, 3, 'SN0013', '00002', 'PAR', '2025-12-03', '2025-12-10', '2025-12-03', 'RFN-00014', 0, '2025-12-02 22:08:17', '2025-12-02 22:08:28'),
(60, 3, 'SN0001', '00001', 'ICS', '2025-12-03', '2025-12-10', '2025-12-03', 'RFN-00015', 0, '2025-12-02 22:10:49', '2025-12-02 22:11:06'),
(61, 3, 'SN0002', '00001', 'ICS', '2025-12-03', '2025-12-10', '2025-12-03', 'RFN-00015', 0, '2025-12-02 22:10:49', '2025-12-02 22:11:10'),
(62, 4, 'SN0001', '00001', 'ICS', '2025-12-03', '2025-12-10', '2025-12-03', 'RFN-00016', 0, '2025-12-02 22:20:17', '2025-12-02 22:20:47'),
(63, 4, 'SN0001', '00001', 'ICS', '2025-12-03', '2025-12-10', '2025-12-07', 'RFN-00017', 0, '2025-12-02 22:21:57', '2025-12-07 10:50:01'),
(64, 4, 'SN0002', '00001', 'ICS', '2025-12-03', '2025-12-10', '2025-12-07', 'RFN-00018', 0, '2025-12-03 00:13:03', '2025-12-06 19:41:17'),
(65, 4, 'SN0003', '00001', 'ICS', '2025-12-03', '2025-12-10', '2025-12-07', 'RFN-00018', 0, '2025-12-03 00:13:03', '2025-12-06 19:44:33');

-- --------------------------------------------------------

--
-- Table structure for table `items`
--

CREATE TABLE `items` (
  `item_id` int(11) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `classification` varchar(255) NOT NULL,
  `source_of_fund` varchar(255) NOT NULL,
  `date_acquired` date NOT NULL,
  `property_no` varchar(255) NOT NULL,
  `serial_no` varchar(255) NOT NULL,
  `stock` int(11) DEFAULT 1,
  `usage_count` int(11) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `status` enum('Available','Borrowed','Issued','For Repair','Damaged','Lost','Unserviceable') DEFAULT 'Available',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `last_maintenance_date` date DEFAULT NULL,
  `maintenance_interval_days` int(11) DEFAULT NULL,
  `maintenance_threshold_usage` int(11) DEFAULT NULL,
  `expected_life_hours` int(11) DEFAULT NULL,
  `total_usage_hours` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `items`
--

INSERT INTO `items` (`item_id`, `item_name`, `classification`, `source_of_fund`, `date_acquired`, `property_no`, `serial_no`, `stock`, `usage_count`, `remarks`, `status`, `created_at`, `updated_at`, `last_maintenance_date`, `maintenance_interval_days`, `maintenance_threshold_usage`, `expected_life_hours`, `total_usage_hours`) VALUES
(11, 'Printer', 'IT EQUIPMENT', 'TESDA', '2025-11-26', '00001', 'SN0001', 1, 9, 'TESDA QC', 'Available', '2025-11-26 01:50:39', '2026-02-07 00:11:22', '2025-11-11', 30, 20, 1000, 200),
(12, 'Printer', 'IT EQUIPMENT', 'TESDA', '2025-11-26', '00001', 'SN0002', 1, 8, 'TESDA QC', 'Available', '2025-11-26 01:50:39', '2026-02-08 06:47:23', NULL, NULL, NULL, NULL, 0),
(13, 'Printer', 'IT EQUIPMENT', 'TESDA', '2025-11-26', '00001', 'SN0003', 1, 5, 'TESDA QC', 'Available', '2025-11-26 01:50:39', '2026-01-05 00:47:37', NULL, NULL, NULL, NULL, 0),
(14, 'Printer', 'IT EQUIPMENT', 'TESDA', '2025-11-26', '00001', 'SN0004', 1, NULL, 'TESDA QC', 'Available', '2025-11-26 01:50:39', '2025-11-26 01:50:39', NULL, NULL, NULL, NULL, 0),
(15, 'Printer', 'IT EQUIPMENT', 'TESDA', '2025-11-26', '00001', 'SN0005', 1, NULL, 'TESDA QC', 'Available', '2025-11-26 01:50:39', '2025-11-26 01:50:39', NULL, NULL, NULL, NULL, 0),
(16, 'Printer', 'IT EQUIPMENT', 'TESDA', '2025-11-25', '00001', 'SN0006', 1, NULL, 'TESDA QC', 'Available', '2025-11-26 01:51:17', '2025-11-26 01:51:17', NULL, NULL, NULL, NULL, 0),
(17, 'Printer', 'IT EQUIPMENT', 'TESDA', '2025-11-25', '00001', 'SN0007', 1, NULL, 'TESDA QC', 'Available', '2025-11-26 01:51:17', '2025-11-26 01:51:17', NULL, NULL, NULL, NULL, 0),
(18, 'Printer', 'IT EQUIPMENT', 'TESDA', '2025-11-25', '00001', 'SN0008', 1, NULL, 'TESDA QC', 'Available', '2025-11-26 01:51:17', '2025-11-26 01:51:17', NULL, NULL, NULL, NULL, 0),
(19, 'Printer', 'IT EQUIPMENT', 'TESDA', '2025-11-25', '00001', 'SN0009', 1, NULL, 'TESDA QC', 'Available', '2025-11-26 01:51:17', '2025-11-26 01:51:17', NULL, NULL, NULL, NULL, 0),
(20, 'Printer', 'IT EQUIPMENT', 'TESDA', '2025-11-25', '00001', 'SN0010', 1, NULL, 'TESDA QC', 'Available', '2025-11-26 01:51:17', '2025-11-26 01:51:17', NULL, NULL, NULL, NULL, 0),
(21, 'Computer', 'IT EQUIPMENT', 'CHED', '2025-11-26', '00002', 'SN0011', 1, 5, 'TESDA QC', 'Available', '2025-11-26 01:51:50', '2025-12-02 21:57:38', NULL, NULL, NULL, NULL, 0),
(22, 'Computer', 'IT EQUIPMENT', 'CHED', '2025-11-26', '00002', 'SN0012', 1, 7, 'TESDA QC', 'Available', '2025-11-26 01:51:50', '2025-12-02 22:08:25', NULL, NULL, NULL, NULL, 0),
(23, 'Computer', 'IT EQUIPMENT', 'CHED', '2025-11-26', '00002', 'SN0013', 1, 7, 'TESDA QC', 'Available', '2025-11-26 01:51:50', '2025-12-02 22:08:28', NULL, NULL, NULL, NULL, 0),
(24, 'Computer', 'IT EQUIPMENT', 'CHED', '2025-11-26', '00002', 'SN0014', 1, NULL, 'TESDA QC', 'Available', '2025-11-26 01:51:50', '2025-11-26 01:51:50', NULL, NULL, NULL, NULL, 0),
(25, 'Computer', 'IT EQUIPMENT', 'CHED', '2025-11-26', '00002', 'SN0015', 1, NULL, 'TESDA QC', 'Available', '2025-11-26 01:51:50', '2025-11-26 01:51:50', NULL, NULL, NULL, NULL, 0),
(26, 'Computer', 'IT EQUIPMENT', 'CHED', '2025-11-26', '00002', 'SN0016', 1, NULL, 'TESDA QC', 'Available', '2025-11-26 02:10:14', '2025-11-26 02:10:14', NULL, NULL, NULL, NULL, 0),
(27, 'Computer', 'IT EQUIPMENT', 'CHED', '2025-11-26', '00002', 'SN0017', 1, NULL, 'TESDA QC', 'Available', '2025-11-26 02:10:14', '2025-11-26 02:10:14', NULL, NULL, NULL, NULL, 0),
(28, 'Computer', 'IT EQUIPMENT', 'CHED', '2025-11-26', '00002', 'SN0018', 1, NULL, 'TESDA QC', 'Available', '2025-11-26 02:10:14', '2025-11-26 02:10:14', NULL, NULL, NULL, NULL, 0),
(29, 'Computer', 'IT EQUIPMENT', 'CHED', '2025-11-26', '00002', 'SN0019', 1, NULL, 'TESDA QC', 'Available', '2025-11-26 02:10:14', '2025-11-26 02:10:14', NULL, NULL, NULL, NULL, 0),
(30, 'Computer', 'IT EQUIPMENT', 'CHED', '2025-11-26', '00002', 'SN0020', 1, NULL, 'TESDA QC', 'Available', '2025-11-26 02:10:14', '2025-11-26 02:10:14', NULL, NULL, NULL, NULL, 0),
(31, 'Computer', 'IT EQUIPMENT', 'CHED', '2025-11-26', '00002', 'SN0021', 1, NULL, 'TESDA QC', 'Available', '2025-11-26 02:10:14', '2025-11-26 02:10:14', NULL, NULL, NULL, NULL, 0),
(32, 'Computer', 'IT EQUIPMENT', 'CHED', '2025-11-26', '00002', 'SN0022', 1, NULL, 'TESDA QC', 'Available', '2025-11-26 02:10:14', '2025-11-26 02:10:14', NULL, NULL, NULL, NULL, 0),
(33, 'Computer', 'IT EQUIPMENT', 'CHED', '2025-11-26', '00002', 'SN0023', 1, NULL, 'TESDA QC', 'Available', '2025-11-26 02:10:14', '2025-11-26 02:10:14', NULL, NULL, NULL, NULL, 0),
(34, 'Computer', 'IT EQUIPMENT', 'CHED', '2025-11-26', '00002', 'SN0024', 1, NULL, 'TESDA QC', 'Available', '2025-11-26 02:10:14', '2025-11-26 02:10:14', NULL, NULL, NULL, NULL, 0),
(35, 'Computer', 'IT EQUIPMENT', 'CHED', '2025-11-26', '00002', 'SN0025', 1, NULL, 'TESDA QC', 'Available', '2025-11-26 02:10:14', '2025-11-26 02:10:14', NULL, NULL, NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `item_approval_requests`
--

CREATE TABLE `item_approval_requests` (
  `request_id` int(10) UNSIGNED NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `serial_number` varchar(100) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `request_type` enum('qr','barcode') NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `requested_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `item_approval_requests`
--

INSERT INTO `item_approval_requests` (`request_id`, `item_name`, `serial_number`, `quantity`, `request_type`, `status`, `requested_at`, `created_at`, `updated_at`) VALUES
(6, 'printer', 'SN0026', 10, 'qr', 'pending', '2026-02-10 09:31:19', '2026-02-10 01:31:19', '2026-02-10 01:31:19'),
(7, 'laptop', 'SN0036', 5, 'barcode', 'pending', '2026-02-10 09:31:19', '2026-02-10 01:31:19', '2026-02-10 01:31:19');

-- --------------------------------------------------------

--
-- Table structure for table `maintenance`
--

CREATE TABLE `maintenance` (
  `maintenance_id` int(11) NOT NULL,
  `serial_no` varchar(255) NOT NULL,
  `issue_type` varchar(255) NOT NULL,
  `repair_cost` decimal(10,2) NOT NULL,
  `date_reported` date NOT NULL,
  `expected_completion` date DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `damage_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `maintenance`
--

INSERT INTO `maintenance` (`maintenance_id`, `serial_no`, `issue_type`, `repair_cost`, `date_reported`, `expected_completion`, `remarks`, `damage_id`) VALUES
(7, 'SN0002', 'Test', 200.00, '2025-12-10', '2026-01-29', 'Done', NULL),
(8, 'SN0001', 'Reported Issue', 0.00, '2025-12-08', '2025-12-13', 'Item is now available', NULL),
(9, 'SN0003', 'Reported Issue', 0.00, '2025-12-10', '2025-12-13', 'Item is now available', NULL),
(10, 'SN0001', 'Reported Issue', 0.00, '2025-12-07', '2026-01-08', 'Item is now available', NULL),
(11, 'SN0001', 'Reported Issue', 0.00, '2026-01-05', '2026-01-08', 'Item is now available', NULL),
(12, 'SN0002', 'Reported Issue', 0.00, '2026-01-05', '2026-01-08', 'Auto-transferred from damage report', NULL),
(13, 'SN0003', 'Reported Issue', 0.00, '2026-01-05', '2026-01-08', 'Auto-transferred from damage report', NULL),
(14, 'SN0001', 'Reported Issue', 0.00, '2026-02-07', '2026-02-10', 'Item is now available', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '2025_11_29_202250_create_cache_table', 1);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` varchar(50) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `item_id`, `title`, `message`, `type`, `created_at`, `updated_at`) VALUES
(1, 11, 'Item nearing maintenance schedule', 'Printer (SN: SN0001) requires maintenance in 14 days.', 'maintenance', '2025-11-26 18:42:17', '2025-11-26 18:42:17'),
(2, 11, 'High Usage Warning', 'Printer (SN: SN0001) has high usage (20).', 'inventory', '2025-11-26 18:42:17', '2025-11-26 18:42:17'),
(3, 11, 'Item Returned', 'Serial No. SN0001 has been returned.', 'inventory', '2025-12-02 08:03:37', '2025-12-02 16:23:17'),
(4, 12, 'Item Returned', 'Serial No. SN0002 has been returned.', 'return', '2025-12-02 08:04:03', '2025-12-02 08:04:03'),
(5, 11, 'Item Returned', 'Serial No. SN0001 has been returned.', 'return', '2025-12-02 08:05:49', '2025-12-02 08:05:49'),
(6, 12, 'Item Returned', 'Serial No. SN0002 has been returned.', 'return', '2025-12-02 08:06:03', '2025-12-02 08:06:03'),
(7, 13, 'Item Returned', 'Serial No. SN0003 has been returned.', 'return', '2025-12-02 08:06:12', '2025-12-02 08:06:12'),
(8, 11, 'Item Returned', 'Serial No. SN0001 has been returned.', 'return', '2025-12-02 08:07:00', '2025-12-02 08:07:00'),
(9, 12, 'Item Returned', 'Serial No. SN0002 has been returned.', 'return', '2025-12-02 08:07:08', '2025-12-02 08:07:08'),
(10, 11, 'Item Returned', 'Serial No. SN0001 has been returned.', 'inventory', '2025-12-02 11:11:38', '2025-12-02 11:11:38'),
(11, 12, 'Item Returned', 'Serial No. SN0002 has been returned.', 'inventory', '2025-12-02 11:11:52', '2025-12-02 11:11:52'),
(12, 13, 'Item Returned', 'Serial No. SN0003 has been returned.', 'inventory', '2025-12-02 11:11:59', '2025-12-02 11:11:59'),
(13, 21, 'Item Returned', 'Serial No. SN0011 has been returned.', 'inventory', '2025-12-02 11:21:32', '2025-12-02 11:21:32'),
(14, 22, 'Item Returned', 'Serial No. SN0012 has been returned.', 'inventory', '2025-12-02 11:21:40', '2025-12-02 11:21:40'),
(15, 23, 'Item Returned', 'Serial No. SN0013 has been returned.', 'inventory', '2025-12-02 11:21:45', '2025-12-02 11:21:45'),
(16, 21, 'Item Returned', 'Serial No. SN0011 has been returned.', 'inventory', '2025-12-02 11:30:41', '2025-12-02 11:30:41'),
(17, 22, 'Item Returned', 'Serial No. SN0012 has been returned.', 'inventory', '2025-12-02 11:30:53', '2025-12-02 11:30:53'),
(18, 23, 'Item Returned', 'Serial No. SN0013 has been returned.', 'inventory', '2025-12-02 11:30:59', '2025-12-02 11:30:59'),
(19, 11, 'Item Returned', 'Serial No. SN0001 has been returned.', 'inventory', '2025-12-02 11:55:48', '2025-12-02 11:55:48'),
(20, 12, 'Item Returned', 'Serial No. SN0002 has been returned.', 'inventory', '2025-12-02 11:55:56', '2025-12-02 11:55:56'),
(21, 13, 'Item Returned', 'Serial No. SN0003 has been returned.', 'inventory', '2025-12-02 11:56:02', '2025-12-02 11:56:02'),
(22, 21, 'Item Returned', 'Serial No. SN0011 has been returned.', 'inventory', '2025-12-02 11:56:16', '2025-12-02 11:56:16'),
(23, 22, 'Item Returned', 'Serial No. SN0012 has been returned.', 'inventory', '2025-12-02 11:56:21', '2025-12-02 11:56:21'),
(24, 23, 'Item Returned', 'Serial No. SN0013 has been returned.', 'inventory', '2025-12-02 11:56:27', '2025-12-02 11:56:27'),
(25, 11, 'Item Returned', 'Serial No. SN0001 has been returned.', 'inventory', '2025-12-02 11:57:46', '2025-12-02 11:57:46'),
(26, 12, 'Item Returned', 'Serial No. SN0002 has been returned.', 'inventory', '2025-12-02 11:57:51', '2025-12-02 11:57:51'),
(27, 11, 'Item Returned', 'Serial No. SN0001 has been returned.', 'inventory', '2025-12-02 21:44:46', '2025-12-02 21:44:46'),
(28, 12, 'Item Returned', 'Serial No. SN0002 has been returned.', 'inventory', '2025-12-02 21:45:07', '2025-12-02 21:45:07'),
(29, 12, 'Item Returned', 'Serial No. SN0002 has been returned.', 'inventory', '2025-12-02 21:45:07', '2025-12-02 21:45:07'),
(30, 13, 'Item Returned', 'Serial No. SN0003 has been returned.', 'inventory', '2025-12-02 21:45:14', '2025-12-02 21:45:14'),
(31, 21, 'Item Returned', 'Serial No. SN0011 has been returned.', 'inventory', '2025-12-02 21:53:43', '2025-12-02 21:53:43'),
(32, 22, 'Item Returned', 'Serial No. SN0012 has been returned.', 'inventory', '2025-12-02 21:53:47', '2025-12-02 21:53:47'),
(33, 23, 'Item Returned', 'Serial No. SN0013 has been returned.', 'inventory', '2025-12-02 21:53:51', '2025-12-02 21:53:51'),
(34, 21, 'Item Returned', 'Serial No. SN0011 has been returned.', 'inventory', '2025-12-02 21:57:38', '2025-12-02 21:57:38'),
(35, 22, 'Item Returned', 'Serial No. SN0012 has been returned.', 'inventory', '2025-12-02 22:03:40', '2025-12-02 22:03:40'),
(36, 23, 'Item Returned', 'Serial No. SN0013 has been returned.', 'inventory', '2025-12-02 22:03:47', '2025-12-02 22:03:47'),
(37, 22, 'Item Returned', 'Serial No. SN0012 has been returned.', 'inventory', '2025-12-02 22:05:52', '2025-12-02 22:05:52'),
(38, 23, 'Item Returned', 'Serial No. SN0013 has been returned.', 'inventory', '2025-12-02 22:05:57', '2025-12-02 22:05:57'),
(39, 22, 'Item Returned', 'Serial No. SN0012 has been returned.', 'inventory', '2025-12-02 22:08:25', '2025-12-02 22:08:25'),
(40, 23, 'Item Returned', 'Serial No. SN0013 has been returned.', 'inventory', '2025-12-02 22:08:28', '2025-12-02 22:08:28'),
(41, 11, 'Item Returned', 'Serial No. SN0001 has been returned.', 'inventory', '2025-12-02 22:11:06', '2025-12-02 22:11:06'),
(42, 12, 'Item Returned', 'Serial No. SN0002 has been returned.', 'inventory', '2025-12-02 22:11:10', '2025-12-02 22:11:10'),
(43, 11, 'Item Returned', 'Serial No. SN0001 has been returned.', 'inventory', '2025-12-02 22:20:47', '2025-12-02 22:20:47'),
(44, 11, 'Item marked as Unserviceable', 'Item \'Printer\' (Serial No: SN0001) has been marked as unserviceable.', 'unserviceable', '2025-12-03 00:06:13', '2025-12-03 00:06:13'),
(45, 12, 'Item Returned', 'Serial No. SN0002 has been returned.', 'inventory', '2025-12-03 00:13:13', '2025-12-03 00:13:13'),
(46, 13, 'Item marked as Unserviceable', 'Item \'Printer\' (Serial No: SN0003) has been marked as unserviceable.', 'unserviceable', '2025-12-03 00:13:27', '2025-12-03 00:13:27'),
(47, 12, 'Item Returned', 'Serial No. SN0002 has been returned.', 'inventory', '2025-12-06 19:41:17', '2025-12-06 19:41:17'),
(48, 13, 'Item Returned', 'Serial No. SN0003 has been returned.', 'inventory', '2025-12-06 19:44:33', '2025-12-06 19:44:33'),
(49, 11, 'Item Returned', 'Serial No. SN0001 has been returned.', 'inventory', '2025-12-07 10:50:01', '2025-12-07 10:50:01');

-- --------------------------------------------------------

--
-- Table structure for table `passwordresets`
--

CREATE TABLE `passwordresets` (
  `reset_id` int(11) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `token` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `propertyinventory`
--

CREATE TABLE `propertyinventory` (
  `inventory_id` int(11) NOT NULL,
  `property_no` varchar(255) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `unit_cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `sources_of_fund` varchar(255) DEFAULT NULL,
  `classification` varchar(255) DEFAULT NULL,
  `date_acquired` date DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Available',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `propertyinventory`
--

INSERT INTO `propertyinventory` (`inventory_id`, `property_no`, `item_name`, `quantity`, `unit_cost`, `sources_of_fund`, `classification`, `date_acquired`, `status`, `created_at`, `updated_at`) VALUES
(3, '00001', 'Printer', 8, 15000.00, 'TESDA', 'IT EQUIPMENT', '2025-11-26', 'Available', '2025-11-26 01:50:39', '2025-12-03 08:13:27'),
(4, '00002', 'Computer', 15, 50000.00, 'CHED', 'IT EQUIPMENT', '2025-11-26', 'Available', '2025-11-26 01:51:50', '2025-11-26 02:10:14');

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `report_id` int(11) NOT NULL,
  `type` varchar(255) DEFAULT NULL,
  `generated_by` int(11) DEFAULT NULL,
  `date_generated` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `requests`
--

CREATE TABLE `requests` (
  `request_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `request_date` timestamp NULL DEFAULT current_timestamp(),
  `status` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `session_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `ip_address` varchar(255) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` text DEFAULT NULL,
  `last_activity` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student`
--

CREATE TABLE `student` (
  `student_id` int(11) NOT NULL,
  `student_name` varchar(255) NOT NULL,
  `student_number` varchar(255) NOT NULL,
  `batch` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student`
--

INSERT INTO `student` (`student_id`, `student_name`, `student_number`, `batch`, `created_at`, `updated_at`) VALUES
(3, 'Cedric John P. Sadsad', '21-1900', 'IT2025', NULL, NULL),
(4, 'Jes', '21-1902', '2025', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `first_name` varchar(255) DEFAULT NULL,
  `last_name` varchar(255) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `contact_no` varchar(50) DEFAULT NULL,
  `role` enum('Admin','User','Property Custodian','Regular Employee') NOT NULL DEFAULT 'User',
  `password` varchar(255) NOT NULL,
  `verification_code` varchar(10) DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `is_approved` tinyint(1) DEFAULT 0,
  `code_expires_at` datetime DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `first_name`, `last_name`, `email`, `contact_no`, `role`, `password`, `verification_code`, `is_verified`, `is_approved`, `code_expires_at`, `remember_token`, `created_at`, `updated_at`) VALUES
(4, 'Cedric', 'Sadsad', 'sadsadcedric62@gmail.com', '09958705846', 'Admin', '$2y$12$b68x29a1Up7YwzEkXK7HFuoig3HKHMdARLpBhHcL0UkWwFvPAJPJC', NULL, 1, 1, '2025-11-24 03:05:28', NULL, '2025-11-23 18:55:30', '2025-11-23 18:56:09'),
(5, 'CJ', 'Sadsad', 'sadsad.cedricjohn.06182003@gmail.com', '09958705846', 'Property Custodian', '$2y$12$LvvvhacCksFcKOxshCbPE.xkLUBtYWTGzz9h5Wd10DyDzqn8k1lCq', NULL, 1, 1, '2025-12-01 17:37:21', NULL, '2025-12-01 09:27:21', '2025-12-01 09:27:55'),
(8, 'Julie', 'San Jose', 'hoyalua@gmail.com', '09958705846', 'Regular Employee', '$2y$12$n0u2IdHSa1FeQ1tUpE9deeUs9cDFrz0UCfGH2zbo5QG6MasgpgR3O', NULL, 1, 1, '2025-12-01 18:26:34', NULL, '2025-12-01 10:16:34', '2025-12-02 06:20:43');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `auditlogs`
--
ALTER TABLE `auditlogs`
  ADD PRIMARY KEY (`audit_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`);

--
-- Indexes for table `chatbot`
--
ALTER TABLE `chatbot`
  ADD PRIMARY KEY (`chatbot_id`);

--
-- Indexes for table `chatbotqueries`
--
ALTER TABLE `chatbotqueries`
  ADD PRIMARY KEY (`query_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `damagefixes`
--
ALTER TABLE `damagefixes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `damage_type` (`damage_type`),
  ADD KEY `damage_fixes_damage_type_index` (`damage_type`);

--
-- Indexes for table `damagereports`
--
ALTER TABLE `damagereports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_damagereports_item` (`serial_no`);

--
-- Indexes for table `formrecords`
--
ALTER TABLE `formrecords`
  ADD PRIMARY KEY (`form_id`),
  ADD UNIQUE KEY `reference_no` (`reference_no`);

--
-- Indexes for table `issuedlog`
--
ALTER TABLE `issuedlog`
  ADD PRIMARY KEY (`issue_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `serial_no` (`serial_no`),
  ADD KEY `property_no` (`property_no`),
  ADD KEY `reference_no` (`reference_no`);

--
-- Indexes for table `items`
--
ALTER TABLE `items`
  ADD PRIMARY KEY (`item_id`),
  ADD UNIQUE KEY `serial_no` (`serial_no`),
  ADD UNIQUE KEY `unique_serial_no` (`serial_no`),
  ADD KEY `property_no` (`property_no`);

--
-- Indexes for table `item_approval_requests`
--
ALTER TABLE `item_approval_requests`
  ADD PRIMARY KEY (`request_id`);

--
-- Indexes for table `maintenance`
--
ALTER TABLE `maintenance`
  ADD PRIMARY KEY (`maintenance_id`),
  ADD KEY `serial_no` (`serial_no`),
  ADD KEY `fk_maintenance_damage` (`damage_id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `damagereports`
--
ALTER TABLE `damagereports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `item_approval_requests`
--
ALTER TABLE `item_approval_requests`
  MODIFY `request_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
