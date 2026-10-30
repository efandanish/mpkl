-- Clean installation schema. Import into an empty database.
-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Oct 07, 2026 at 09:33 PM
-- Server version: 10.4.27-MariaDB
-- PHP Version: 7.4.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `caj_pemajuan`
--

-- --------------------------------------------------------

--
-- Table structure for table `development_charges`
--

CREATE TABLE `development_charges` (
  `id` int(11) NOT NULL,
  `developer_name` varchar(200) NOT NULL,
  `project_name` varchar(255) NOT NULL,
  `osc_id` varchar(100) DEFAULT NULL,
  `lot_number` varchar(100) DEFAULT NULL,
  `mukim` varchar(150) DEFAULT NULL,
  `file_number` varchar(150) DEFAULT NULL,
  `charge_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `osc_decision` enum('Lulus','Tangguh','Ditolak') DEFAULT 'Tangguh',
  `payment_plan` enum('Bayaran Penuh','3 Ansuran','4 Ansuran') NOT NULL DEFAULT 'Bayaran Penuh',
  `record_year` smallint unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `development_charges`
--


-- --------------------------------------------------------

--
-- Table structure for table `development_charge_payments`
--

CREATE TABLE `development_charge_payments` (
  `id` int(11) NOT NULL,
  `development_charge_id` int(11) NOT NULL,
  `installment_no` int(11) NOT NULL DEFAULT 1,
  `due_date` date DEFAULT NULL,
  `payment_date` date DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` enum('Menunggu','Dibayar') NOT NULL DEFAULT 'Menunggu',
  `receipt_file` varchar(255) DEFAULT NULL,
  `receipt_number` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `development_charge_payments`
--


-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `audience` varchar(20) NOT NULL DEFAULT 'legacy',
  `department` varchar(150) DEFAULT NULL,
  `reminder_key` varchar(150) DEFAULT NULL,
  `id` int(11) NOT NULL,
  `category` enum('project','charge','payment','variation','system') NOT NULL,
  `action` varchar(50) NOT NULL,
  `title` varchar(150) NOT NULL,
  `message` varchar(255) NOT NULL,
  `entity_type` varchar(50) DEFAULT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--


-- --------------------------------------------------------

--
-- Table structure for table `notification_reads`
--

CREATE TABLE `notification_reads` (
  `id` int(11) NOT NULL,
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `read_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notification_reads`
--


-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

CREATE TABLE `projects` (
  `id` int(11) NOT NULL,
  `project_id` varchar(50) NOT NULL,
  `department` varchar(150) NOT NULL,
  `project_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `approval_status` enum('Diluluskan','Tangguh','Ditolak') DEFAULT 'Tangguh',
  `meeting_count` int(11) DEFAULT 0,
  `procurement_type` varchar(100) DEFAULT NULL,
  `sst_value` decimal(12,2) DEFAULT 0.00,
  `meeting_approval_value` decimal(12,2) DEFAULT 0.00,
  `payment_method` enum('One-off','Ansuran','Pajakan/Sewa') DEFAULT 'One-off',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `project_status` enum('Belum Mula','Dalam Proses','Selesai','Dibatalkan') DEFAULT 'Belum Mula',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `projects`
--


-- --------------------------------------------------------

--
-- Table structure for table `project_expenses`
--

CREATE TABLE `project_expenses` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `schedule_id` int(11) DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `payment_date` date NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `project_payment_schedules`
--

CREATE TABLE `project_payment_schedules` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `claim_date` date NOT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `type` enum('One-off','Ansuran','Pajakan/Sewa') NOT NULL,
  `status` enum('Menunggu','Dibayar') NOT NULL DEFAULT 'Menunggu',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `project_payment_schedules`
--


-- --------------------------------------------------------

--
-- Table structure for table `project_variations`
--

CREATE TABLE `project_variations` (
  `id` int(11) NOT NULL,
  `project_id` int(11) NOT NULL,
  `description` varchar(255) NOT NULL,
  `variation_type` enum('Tambah','Tolak') NOT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `variation_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','user') NOT NULL DEFAULT 'user',
  `department` varchar(150) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--


--
-- Indexes for dumped tables
--

--
-- Indexes for table `development_charges`
--
ALTER TABLE `development_charges`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_charge_record_year` (`record_year`),
  ADD CONSTRAINT `chk_charge_record_year` CHECK (`record_year` IS NULL OR `record_year` BETWEEN 1000 AND 9999);

--
-- Indexes for table `development_charge_payments`
--
ALTER TABLE `development_charge_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_charge_payment_charge` (`development_charge_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_project_reminder` (`reminder_key`),
  ADD KEY `notification_entity_action` (`entity_type`, `entity_id`, `action`);

--
-- Indexes for table `notification_reads`
--
ALTER TABLE `notification_reads`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_notification_user` (`notification_id`,`user_id`),
  ADD KEY `fk_notification_read_user` (`user_id`);

--
-- Indexes for table `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `project_id` (`project_id`);

--
-- Indexes for table `project_expenses`
--
ALTER TABLE `project_expenses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_expense_project` (`project_id`),
  ADD KEY `fk_expense_schedule` (`schedule_id`);

--
-- Indexes for table `project_payment_schedules`
--
ALTER TABLE `project_payment_schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_schedule_project` (`project_id`);

--
-- Indexes for table `project_variations`
--
ALTER TABLE `project_variations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_variation_project` (`project_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `development_charges`
--
ALTER TABLE `development_charges`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `development_charge_payments`
--
ALTER TABLE `development_charge_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notification_reads`
--
ALTER TABLE `notification_reads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `project_expenses`
--
ALTER TABLE `project_expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `project_payment_schedules`
--
ALTER TABLE `project_payment_schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `project_variations`
--
ALTER TABLE `project_variations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `development_charge_payments`
--
ALTER TABLE `development_charge_payments`
  ADD CONSTRAINT `fk_charge_payment_charge` FOREIGN KEY (`development_charge_id`) REFERENCES `development_charges` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notification_reads`
--
ALTER TABLE `notification_reads`
  ADD CONSTRAINT `fk_notification_read_notification` FOREIGN KEY (`notification_id`) REFERENCES `notifications` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_notification_read_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `project_expenses`
--
ALTER TABLE `project_expenses`
  ADD CONSTRAINT `fk_expense_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_expense_schedule` FOREIGN KEY (`schedule_id`) REFERENCES `project_payment_schedules` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `project_payment_schedules`
--
ALTER TABLE `project_payment_schedules`
  ADD CONSTRAINT `fk_schedule_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `project_variations`
--
ALTER TABLE `project_variations`
  ADD CONSTRAINT `fk_variation_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
