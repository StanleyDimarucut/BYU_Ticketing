-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 12, 2026 at 09:20 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.4.13

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ticketing-system`
--

-- --------------------------------------------------------

--
-- Table structure for table `kb_articles`
--

CREATE TABLE `kb_articles` (
  `id` int(11) UNSIGNED NOT NULL,
  `author_id` int(11) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `kb_articles`
--

INSERT INTO `kb_articles` (`id`, `author_id`, `title`, `content`, `created_at`, `updated_at`) VALUES
(1, 2, 'Broken Mouse scrollwheel', 'ASid aifj iqwjn asufwia jdina wifi', '2026-02-12 10:50:19', '2026-02-12 10:50:19'),
(2, 3, 'Broken Monitor', 'aefqwefqwfqwef wef qwe fweq fwqef qwe fqwefqwef we', '2026-02-12 12:49:39', '2026-02-12 12:49:39'),
(3, 3, 'No Internet', 'asd aw asd wajns jdwnja njsdn jwajsn jdwaj jqf hsdfn jf qw', '2026-02-12 12:50:17', '2026-02-12 12:50:17');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) UNSIGNED NOT NULL,
  `message` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `message`, `link`, `is_read`, `created_at`) VALUES
(1, 2, 'New Ticket #2: Broken Monitor', 'tickets.php?id=2', 1, '2026-02-11 11:17:32'),
(2, 3, 'New Ticket #2: Broken Monitor', 'tickets.php?id=2', 1, '2026-02-11 11:17:32'),
(3, 4, 'New Ticket #2: Broken Monitor', 'tickets.php?id=2', 1, '2026-02-11 11:17:32'),
(4, 2, 'New Ticket #3: Missing keycaps keyboard', 'tickets.php?id=3', 1, '2026-02-11 11:18:37'),
(5, 3, 'New Ticket #3: Missing keycaps keyboard', 'tickets.php?id=3', 1, '2026-02-11 11:18:37'),
(6, 4, 'New Ticket #3: Missing keycaps keyboard', 'tickets.php?id=3', 1, '2026-02-11 11:18:37'),
(7, 1, 'New response on Ticket #3: Replaced Keyboard...', 'tickets.php?id=3', 1, '2026-02-11 11:19:05'),
(8, 1, 'Ticket #3 status updated to Closed', 'tickets.php?id=3', 1, '2026-02-11 11:19:05'),
(9, 1, 'New response on Ticket #1: Be careful of water...', 'tickets.php?id=1', 0, '2026-02-11 11:22:29'),
(10, 1, 'Ticket #1 status updated to Closed', 'tickets.php?id=1', 0, '2026-02-11 11:22:29'),
(11, 1, 'Ticket #3 (Missing keycaps keyboard) status updated to \"Open\"', 'tickets.php?id=3', 0, '2026-02-11 13:36:08'),
(12, 1, 'Ticket #3 (Missing keycaps keyboard) status updated to \"Closed\"', 'tickets.php?id=3', 0, '2026-02-11 13:36:52'),
(13, 1, 'Ticket #3 (Missing keycaps keyboard) status updated to \"Closed\"', 'tickets.php?id=3', 0, '2026-02-12 10:38:09'),
(14, 1, 'Ticket #3 (Missing keycaps keyboard) status updated to \"Closed\"', 'tickets.php?id=3', 0, '2026-02-12 13:48:18'),
(15, 1, 'Ticket #3 (Missing keycaps keyboard) status updated to \"In Progress\"', 'tickets.php?id=3', 0, '2026-02-12 13:50:27'),
(16, 1, 'Ticket #3 (Missing keycaps keyboard) status updated to \"Closed\"', 'tickets.php?id=3', 0, '2026-02-12 13:50:34');

-- --------------------------------------------------------

--
-- Table structure for table `tickets`
--

CREATE TABLE `tickets` (
  `id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) UNSIGNED NOT NULL,
  `subject` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `category` varchar(50) NOT NULL DEFAULT 'Other',
  `ticket_type` enum('Incident','Request') NOT NULL DEFAULT 'Incident',
  `status` varchar(50) NOT NULL DEFAULT 'Open',
  `priority` varchar(50) NOT NULL DEFAULT 'normal',
  `technician_response` text DEFAULT NULL,
  `hours_worked` decimal(10,2) DEFAULT NULL,
  `importance` varchar(50) DEFAULT NULL,
  `resolution_status` varchar(50) DEFAULT NULL,
  `additional_comments` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tickets`
--

INSERT INTO `tickets` (`id`, `user_id`, `subject`, `description`, `category`, `status`, `priority`, `technician_response`, `hours_worked`, `importance`, `resolution_status`, `additional_comments`, `created_at`) VALUES
(1, 1, 'Broken Mouse', 'Broken Sensor', 'Hardware', 'Closed', 'low', 'Replace Mouse', 1.00, 'Slowing User Down', 'Resolved', '', '2026-02-11 10:39:46'),
(2, 3, 'Broken Monitor', 'Broken screen', 'Hardware', 'Open', 'high', NULL, NULL, 'Mission Critical', NULL, NULL, '2026-02-11 11:17:32'),
(3, 1, 'Missing keycaps keyboard', 'No letter E', 'Hardware', 'Closed', 'high', NULL, 1.00, 'Slowing User Down', 'Resolved', '', '2026-02-11 11:18:37');

-- --------------------------------------------------------

--
-- Table structure for table `ticket_attachments`
--

CREATE TABLE `ticket_attachments` (
  `id` int(11) UNSIGNED NOT NULL,
  `ticket_id` int(11) UNSIGNED NOT NULL,
  `filename` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ticket_logs`
--

CREATE TABLE `ticket_logs` (
  `id` int(11) UNSIGNED NOT NULL,
  `ticket_id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) UNSIGNED NOT NULL,
  `action` varchar(50) NOT NULL,
  `details` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ticket_logs`
--

INSERT INTO `ticket_logs` (`id`, `ticket_id`, `user_id`, `action`, `details`, `created_at`) VALUES
(1, 1, 3, 'Response Added', 'Technician response updated', '2026-02-11 10:51:31'),
(2, 1, 3, 'Response Added', 'Technician added a new response', '2026-02-11 11:00:33'),
(3, 2, 3, 'Created', 'Ticket created', '2026-02-11 11:17:32'),
(4, 3, 1, 'Created', 'Ticket created', '2026-02-11 11:18:37'),
(5, 3, 3, 'Response Added', 'Technician added a new response', '2026-02-11 11:19:05'),
(6, 1, 4, 'Response Added', 'Technician added a new response', '2026-02-11 11:22:29'),
(7, 3, 3, 'Status Update', 'Status changed to \"Open\"', '2026-02-11 13:36:08'),
(8, 3, 3, 'Status Update', 'Status changed to \"Closed\"', '2026-02-11 13:36:52'),
(9, 3, 2, 'Status Update', 'Status changed to \"Closed\"', '2026-02-12 10:38:09'),
(10, 3, 3, 'Status Update', 'Status changed to \"Closed\"', '2026-02-12 13:48:18'),
(11, 3, 3, 'Status Update', 'Status changed to \"In Progress\"', '2026-02-12 13:50:27'),
(12, 3, 3, 'Status Update', 'Status changed to \"Closed\"', '2026-02-12 13:50:34');

-- --------------------------------------------------------

--
-- Table structure for table `ticket_responses`
--

CREATE TABLE `ticket_responses` (
  `id` int(11) UNSIGNED NOT NULL,
  `ticket_id` int(11) UNSIGNED NOT NULL,
  `user_id` int(11) UNSIGNED NOT NULL,
  `response` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ticket_responses`
--

INSERT INTO `ticket_responses` (`id`, `ticket_id`, `user_id`, `response`, `created_at`) VALUES
(1, 1, 2, 'Replace Mouse', '2026-02-11 03:56:02'),
(2, 1, 3, 'Mouse has been replaced', '2026-02-11 11:00:33'),
(3, 3, 3, 'Replaced Keyboard', '2026-02-11 11:19:05'),
(4, 1, 4, 'Be careful of water', '2026-02-11 11:22:29');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('help_desk','admin','technician') NOT NULL DEFAULT 'help_desk',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `username`, `password`, `role`, `created_at`) VALUES
(1, 'James Stanley Dimarucut', 'James', '$2y$12$pAE1fQlcSA5njYKrFpftCeJ2C3GGsNnZto/cghlLzgxH7901cesOu', 'help_desk', '2026-02-11 10:35:29'),
(2, 'Jarom Bustillo', 'Jarom', '$2y$12$YFH3vJmVB7xwOqg6v2oFnOjVFCqp.NjNUsvynVlS69hYkCRZ/E7PG', 'admin', '2026-02-11 10:38:19'),
(3, 'Damien Caumeran', 'Damien', '$2y$12$iNoSMgpWls.W2ZlnzUVL2OOEAD/4Al3U7goDaiJA3Ie7qfD56FVc2', 'technician', '2026-02-11 10:38:57'),
(4, 'Aldrian Bahan', 'Bahan', '$2y$12$7D9jIvbciYo/C6er9Q5vA.s8cT1BZrrpSYi9wDJ2CfwAMaGTgDrva', 'technician', '2026-02-11 10:52:27');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `kb_articles`
--
ALTER TABLE `kb_articles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `author_id` (`author_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `tickets`
--
ALTER TABLE `tickets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `ticket_attachments`
--
ALTER TABLE `ticket_attachments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ticket_id` (`ticket_id`);

--
-- Indexes for table `ticket_logs`
--
ALTER TABLE `ticket_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ticket_id` (`ticket_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `ticket_responses`
--
ALTER TABLE `ticket_responses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ticket_id` (`ticket_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `kb_articles`
--
ALTER TABLE `kb_articles`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `tickets`
--
ALTER TABLE `tickets`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `ticket_attachments`
--
ALTER TABLE `ticket_attachments`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ticket_logs`
--
ALTER TABLE `ticket_logs`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `ticket_responses`
--
ALTER TABLE `ticket_responses`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `kb_articles`
--
ALTER TABLE `kb_articles`
  ADD CONSTRAINT `kb_author_fk` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notif_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tickets`
--
ALTER TABLE `tickets`
  ADD CONSTRAINT `tickets_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ticket_attachments`
--
ALTER TABLE `ticket_attachments`
  ADD CONSTRAINT `attachments_ticket_fk` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ticket_logs`
--
ALTER TABLE `ticket_logs`
  ADD CONSTRAINT `logs_ticket_fk` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `logs_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ticket_responses`
--
ALTER TABLE `ticket_responses`
  ADD CONSTRAINT `responses_ticket_fk` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `responses_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
