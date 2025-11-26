-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 26, 2025 at 11:24 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `peer_tutoring`
--

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `id` int(11) NOT NULL,
  `session_id` int(11) DEFAULT NULL,
  `rater_id` int(11) DEFAULT NULL,
  `stars` int(11) DEFAULT NULL CHECK (`stars` between 1 and 5),
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `message`, `is_read`, `created_at`) VALUES
(11, 10, 'New session request for Development Studies', 1, '2025-10-06 09:29:23'),
(12, 10, 'New session request for Communication Skills', 1, '2025-10-06 09:48:03'),
(13, 14, 'New session request for Data Structures', 1, '2025-10-21 14:50:33'),
(14, 10, 'New session request for Communication Skills', 1, '2025-11-12 08:04:24'),
(15, 14, 'New session request for Data Structures', 1, '2025-11-12 08:08:24'),
(16, 10, 'New session request for Development Studies', 1, '2025-11-12 08:17:42');

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` int(11) NOT NULL,
  `learner_id` int(11) DEFAULT NULL,
  `tutor_id` int(11) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `start_time` datetime DEFAULT NULL,
  `end_time` datetime DEFAULT NULL,
  `capacity` int(11) DEFAULT 10,
  `is_closed` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('requested','assigned','accepted','rejected','cancelled','completed') DEFAULT 'requested'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `learner_id`, `tutor_id`, `title`, `description`, `start_time`, `end_time`, `capacity`, `is_closed`, `created_at`, `status`) VALUES
(11, 11, 10, 'Development Studies', NULL, '2025-10-24 16:33:00', '2025-10-24 17:33:00', 10, 0, '2025-10-06 09:29:23', ''),
(12, 11, 10, 'Communication Skills', NULL, '2025-10-23 17:48:00', '2025-10-23 18:48:00', 10, 0, '2025-10-06 09:48:03', 'accepted'),
(13, 11, 14, 'Data Structures', NULL, '2025-10-31 20:53:00', '2025-10-31 21:53:00', 10, 0, '2025-10-21 14:50:33', 'accepted'),
(14, 15, 10, 'Communication Skills', NULL, '2025-11-29 14:04:00', '2025-11-29 15:04:00', 10, 0, '2025-11-12 08:04:24', 'accepted'),
(15, 10, 14, 'Data Structures', NULL, '2025-11-22 23:11:00', '2025-11-23 00:11:00', 10, 0, '2025-11-12 08:08:24', 'accepted'),
(16, 16, 10, 'Development Studies', NULL, '2025-11-29 16:17:00', '2025-11-29 17:17:00', 10, 0, '2025-11-12 08:17:42', 'accepted');

-- --------------------------------------------------------

--
-- Table structure for table `session_registrations`
--

CREATE TABLE `session_registrations` (
  `id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `registered_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tutor_subjects`
--

CREATE TABLE `tutor_subjects` (
  `id` int(11) NOT NULL,
  `tutor_id` int(11) DEFAULT NULL,
  `subject` varchar(100) DEFAULT NULL,
  `year_of_study` int(11) NOT NULL,
  `degree_programme` varchar(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tutor_subjects`
--

INSERT INTO `tutor_subjects` (`id`, `tutor_id`, `subject`, `year_of_study`, `degree_programme`) VALUES
(12, 10, 'Enterpreneurship', 1, 'BSC in Computer Science'),
(13, 10, 'Development Studies', 1, 'BSC in Computer Science'),
(14, 10, 'Communication Skills', 1, 'BSC in Computer Science'),
(15, 14, 'Data Structures', 1, 'Bsc in computer science'),
(16, 14, 'web technologies', 1, 'Bsc in computer science');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `degree_programme` varchar(100) DEFAULT NULL,
  `year_of_study` int(11) DEFAULT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `status` enum('pending','active','suspended','deactivated') DEFAULT 'pending',
  `deactivation_reason` text DEFAULT NULL,
  `verification_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `profile_pic` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `email`, `phone`, `degree_programme`, `year_of_study`, `password_hash`, `status`, `deactivation_reason`, `verification_token`, `created_at`, `profile_pic`) VALUES
(10, 'kemmy', 'doe', 'kemmy@ifm.ac.tz', '0657898989', 'BSC in Computer Science', 1, '$2y$10$bW6KNZ.yOBs66mAz/oFPB.DsWl/221IGs1VnIfEzHbI93OKMEbTZu', 'active', NULL, NULL, '2025-10-04 17:06:01', NULL),
(11, 'dave', 'dave', 'dave@ifm.ac.tz', '0653103227', 'BSC in Computer Science', 1, '$2y$10$Y5M0Z2W/a77TCDNf.5nemuauL5t33c6Ju2hL27oaMIjHRhazj3Xm2', 'active', NULL, '54e050cf36fb635c09ec87bac2a22a37', '2025-10-04 17:23:38', NULL),
(12, 'datius', 'sinner', 'datius@ifm.ac.tz', NULL, NULL, NULL, '$2y$10$9oYyLf/RKVjVTIr5B8dlJO25TrvgaGW2dt3IdGfD66dekeBcLjh6W', 'active', NULL, NULL, '2025-10-06 16:06:18', NULL),
(13, 'admin', 'admin', 'admin@ifm.ac.tz', NULL, NULL, NULL, '$2y$10$Hhcqbrtt8fsTpl9gUNmFf.7bp9xzq7cfibmsQnpQ9ki0BNaFCLKLy', 'active', NULL, NULL, '2025-10-06 16:47:16', NULL),
(14, 'eugen', 'mamboya', 'eugen@ifm.ac.tz', '0787080792', 'Bsc in computer science', 1, '$2y$10$.yn8Wf3Htng5frrHuqORrOEV8nKRnslM7eJYaO6UyfW6B/UP4pkzS', 'active', NULL, NULL, '2025-10-21 14:46:56', 'tutor_14_1761058135.png'),
(15, 'kibwana', 'miruru', 'kibwana@ifm.ac.tz', '0780000000', 'Bsc in computer science', 1, '$2y$10$64jHA9FVc7nxyxZFVfcd.uu5GI3t.h46tg/HfFsF8L7Jhxm6YsuR2', 'active', NULL, NULL, '2025-11-12 08:02:09', 'student_15_1762934609.png'),
(16, 'dennis', 'denis', 'dennis@ifm.ac.tz', '0626540911', 'Bsc in computer science', 1, '$2y$10$/gzlcs4RdW1dyYi0WCG3dukkjpb75Kux66fQXtHnkTfedeciu59WO', 'active', NULL, NULL, '2025-11-12 08:16:05', 'student_16_1762935423.png');

-- --------------------------------------------------------

--
-- Table structure for table `user_roles`
--

CREATE TABLE `user_roles` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role` enum('student','tutor','admin') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_roles`
--

INSERT INTO `user_roles` (`id`, `user_id`, `role`) VALUES
(1, 10, 'student'),
(2, 10, 'tutor'),
(3, 11, 'student'),
(4, 12, 'student'),
(5, 13, 'admin'),
(6, 14, 'tutor'),
(7, 15, 'student'),
(8, 16, 'student');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`id`),
  ADD KEY `session_id` (`session_id`),
  ADD KEY `rater_id` (`rater_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `learner_id` (`learner_id`),
  ADD KEY `tutor_id` (`tutor_id`);

--
-- Indexes for table `session_registrations`
--
ALTER TABLE `session_registrations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_registration` (`session_id`,`student_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `tutor_subjects`
--
ALTER TABLE `tutor_subjects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tutor_id` (`tutor_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_role` (`user_id`,`role`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `sessions`
--
ALTER TABLE `sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `session_registrations`
--
ALTER TABLE `session_registrations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tutor_subjects`
--
ALTER TABLE `tutor_subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `user_roles`
--
ALTER TABLE `user_roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `feedback`
--
ALTER TABLE `feedback`
  ADD CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `feedback_ibfk_2` FOREIGN KEY (`rater_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sessions`
--
ALTER TABLE `sessions`
  ADD CONSTRAINT `sessions_ibfk_1` FOREIGN KEY (`learner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sessions_ibfk_2` FOREIGN KEY (`tutor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `session_registrations`
--
ALTER TABLE `session_registrations`
  ADD CONSTRAINT `session_registrations_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `session_registrations_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tutor_subjects`
--
ALTER TABLE `tutor_subjects`
  ADD CONSTRAINT `tutor_subjects_ibfk_1` FOREIGN KEY (`tutor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD CONSTRAINT `user_roles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
