-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 16, 2025 at 11:33 PM
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
-- Database: `rfid`
--

-- --------------------------------------------------------

--
-- Table structure for table `daily_records`
--

CREATE TABLE `daily_records` (
  `record_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `work_date` date NOT NULL,
  `first_entry` datetime DEFAULT NULL,
  `last_exit` datetime DEFAULT NULL,
  `work_hours` decimal(5,2) DEFAULT 0.00,
  `status` enum('present','absent','late','half-day') DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `daily_records`
--

INSERT INTO `daily_records` (`record_id`, `employee_id`, `work_date`, `first_entry`, `last_exit`, `work_hours`, `status`, `notes`) VALUES
(3, 12, '2025-05-08', '2025-05-08 01:19:21', '2025-05-08 01:29:49', 0.13, 'present', NULL),
(4, 13, '2025-05-08', '2025-05-08 01:21:29', '2025-05-08 01:33:41', 0.20, 'present', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `department_id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `manager_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `employee_id` int(11) NOT NULL,
  `rfid_uid` varchar(100) NOT NULL,
  `name` varchar(100) NOT NULL,
  `gender` varchar(20) NOT NULL,
  `email` varchar(100) NOT NULL,
  `mobile` varchar(20) NOT NULL,
  `department` varchar(50) DEFAULT NULL,
  `position` varchar(50) DEFAULT NULL,
  `hire_date` date DEFAULT NULL,
  `current_status` enum('in','out') DEFAULT 'out',
  `employment_status` enum('active','inactive') DEFAULT 'active',
  `profile_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`employee_id`, `rfid_uid`, `name`, `gender`, `email`, `mobile`, `department`, `position`, `hire_date`, `current_status`, `employment_status`, `profile_image`, `created_at`, `updated_at`) VALUES
(12, '43CB9DAA', 'Anass Nabil', 'Male', 'anassnbbnnb2@gmail.com', '0653470405', 'Informatique', 'Dev', '2025-05-08', 'out', 'active', NULL, '2025-05-07 23:19:03', '2025-05-07 23:29:49'),
(13, '43C0639A', 'Ilyass', 'Male', 'ilyass@gmail.com', '05345435', 'Informatique', 'dev', '2025-05-08', 'in', 'active', NULL, '2025-05-07 23:21:00', '2025-05-07 23:33:55');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `setting_id` int(11) NOT NULL,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text NOT NULL,
  `description` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`setting_id`, `setting_key`, `setting_value`, `description`, `updated_at`) VALUES
(1, 'workday_start', '09:00:00', 'Default work day start time', '2025-05-07 23:09:11'),
(2, 'workday_end', '17:00:00', 'Default work day end time', '2025-05-07 23:09:11'),
(3, 'late_threshold', '00:15:00', 'Minutes after workday start to mark as late', '2025-05-07 23:09:11'),
(4, 'company_name', 'Your Company', 'Company name for reports and display', '2025-05-07 23:09:11'),
(5, 'weekend_days', '0,6', 'Weekend days (0=Sunday, 6=Saturday)', '2025-05-07 23:09:11');

-- --------------------------------------------------------

--
-- Table structure for table `table_the_iot_projects`
--

CREATE TABLE `table_the_iot_projects` (
  `name` varchar(100) NOT NULL,
  `id` varchar(100) NOT NULL,
  `gender` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `mobile` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `table_the_iot_projects`
--

INSERT INTO `table_the_iot_projects` (`name`, `id`, `gender`, `email`, `mobile`) VALUES
('Alsan', '39EAB06D', 'Male', 'mydigitalnepal@gmail.com', '9800998787'),
('Anass Nabil', '43C0639A', 'Male', 'anassnbbnnb2@gmail.com', '0653470405'),
('Anass', '43CB9DAA', 'Male', 'anassnbbnnb2@gmail.com', '0653470405'),
('John', '769174F8', 'Male', 'john@email.com', '23456789'),
('Thvhm,b', '81A3DC79', 'Female', 'jgkhkkmanjil@gmail.com', '45768767564'),
('The IoT Projects', '866080F8', 'Male', 'ask.theiotprojects@gmail.com', '9800988978');

-- --------------------------------------------------------

--
-- Table structure for table `time_logs`
--

CREATE TABLE `time_logs` (
  `log_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `rfid_uid` varchar(100) NOT NULL,
  `log_type` enum('entry','exit') NOT NULL,
  `timestamp` datetime DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `time_logs`
--

INSERT INTO `time_logs` (`log_id`, `employee_id`, `rfid_uid`, `log_type`, `timestamp`, `notes`) VALUES
(8, 12, '43CB9DAA', 'entry', '2025-05-08 00:19:21', ''),
(9, 12, '43CB9DAA', 'exit', '2025-05-08 00:19:24', ''),
(10, 12, '43CB9DAA', 'entry', '2025-05-08 00:19:27', ''),
(11, 13, '43C0639A', 'entry', '2025-05-08 00:21:29', ''),
(12, 12, '43CB9DAA', 'exit', '2025-05-08 00:23:02', ''),
(13, 12, '43CB9DAA', 'entry', '2025-05-08 00:23:40', ''),
(14, 12, '43CB9DAA', 'exit', '2025-05-08 00:29:32', ''),
(15, 12, '43CB9DAA', 'entry', '2025-05-08 00:29:44', ''),
(16, 12, '43CB9DAA', 'exit', '2025-05-08 00:29:49', ''),
(17, 13, '43C0639A', 'exit', '2025-05-08 00:33:41', ''),
(18, 13, '43C0639A', 'entry', '2025-05-08 00:33:55', '');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` enum('admin','manager','viewer') DEFAULT 'viewer',
  `employee_id` int(11) DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `email`, `role`, `employee_id`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'admin', '$2y$10$GtQSp.P8OU50YTXMOZsYJe00CGFQsyPwqmrXjBZ.CU5vABAKRJGOi', 'admin@example.com', 'admin', NULL, NULL, '2025-05-07 23:09:12', '2025-05-07 23:09:12');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `daily_records`
--
ALTER TABLE `daily_records`
  ADD PRIMARY KEY (`record_id`),
  ADD UNIQUE KEY `employee_date` (`employee_id`,`work_date`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`department_id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD KEY `manager_id` (`manager_id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`employee_id`),
  ADD UNIQUE KEY `rfid_uid` (`rfid_uid`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`setting_id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `table_the_iot_projects`
--
ALTER TABLE `table_the_iot_projects`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `time_logs`
--
ALTER TABLE `time_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `rfid_uid` (`rfid_uid`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `employee_id` (`employee_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `daily_records`
--
ALTER TABLE `daily_records`
  MODIFY `record_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `department_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `employee_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `setting_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `time_logs`
--
ALTER TABLE `time_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `daily_records`
--
ALTER TABLE `daily_records`
  ADD CONSTRAINT `daily_records_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE;

--
-- Constraints for table `departments`
--
ALTER TABLE `departments`
  ADD CONSTRAINT `departments_ibfk_1` FOREIGN KEY (`manager_id`) REFERENCES `employees` (`employee_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `time_logs`
--
ALTER TABLE `time_logs`
  ADD CONSTRAINT `time_logs_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `time_logs_ibfk_2` FOREIGN KEY (`rfid_uid`) REFERENCES `employees` (`rfid_uid`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`employee_id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
