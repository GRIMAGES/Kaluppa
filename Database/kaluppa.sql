-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 19, 2025 at 02:22 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `kaluppa`
--

-- --------------------------------------------------------

--
-- Table structure for table `applications`
--

CREATE TABLE `applications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `dob` date NOT NULL,
  `address` varchar(255) NOT NULL,
  `document` varchar(255) NOT NULL,
  `status` enum('pending','approved','rejected','under review','enrolled') DEFAULT 'pending',
  `applied_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `applications`
--

INSERT INTO `applications` (`id`, `user_id`, `course_id`, `full_name`, `email`, `dob`, `address`, `document`, `status`, `applied_at`) VALUES
(78, 34, 36, 'WILLIAM DUNCAN  GONZALES', 'botchok.gonzales@gmail.com', '2025-01-01', '350 - A Chico St Cembo', '/Backend/Documents/Scholarship/1737221163_Gonzales__William_Duncan__Resume.pdf', 'under review', '2025-01-18 17:26:03'),
(79, 36, 36, ' botchok  gonzales', 'velcro.mir4@gmail.com', '2025-01-14', '350 - A Chico St Cembo', '/Backend/Documents/Scholarship/1738218091_Gonzales_WilliamDuncan_Resume.pdf', 'under review', '2025-01-30 06:21:31'),
(81, 34, 37, 'WILLIAM DUNCAN OLLERO GONZALES', 'botchok.gonzales@gmail.com', '2025-02-20', '350 - A Chico St Cembo', '/Backend/Documents/Scholarship/1739863969_Gonzales__William_Duncan_Resume.pdf', 'pending', '2025-02-18 07:32:49');

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` int(255) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `instructor` varchar(255) NOT NULL,
  `requisites` text NOT NULL,
  `capacity` int(11) NOT NULL,
  `enrolled_students` int(11) NOT NULL,
  `status` varchar(255) NOT NULL,
  `duration` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`id`, `image`, `name`, `description`, `instructor`, `requisites`, `capacity`, `enrolled_students`, `status`, `duration`) VALUES
(36, 'tan.jpg', 'Barista NCII', 'The Barista NC II Qualification consists of competencies that a person must achieve in the deliverance of good quality coffee in commercially-operated cafes or specialty coffee shops. This qualification is specific to a person who specializes in making coffee beverages. A person who has achieved this qualification is competent to be a barista.', 'John Paul Santos', 'Coffee Making', 5, 2, '', '178 hours, 23 days'),
(37, 'Screenshot 2024-08-13 121056.png', 'Information Technology', 'information technology', 'William Duncan Gonzales', 'Software and Hardware', 21, 1, '', '178 hours, 23 days'),
(38, 'Screenshot 2024-08-27 201743.png', '1', 'i', '21', '21', 21, 0, '', '178 hours, 23 days');

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `image` varchar(255) NOT NULL,
  `event_time` datetime NOT NULL,
  `organizer` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`id`, `title`, `image`, `event_time`, `organizer`, `description`, `created_at`) VALUES
(10, 'Thanksgiving', 'tan.jpg', '2025-01-30 09:40:00', 'William Duncan Gonzales', 'Thanksgiving', '2024-11-29 11:40:59');

-- --------------------------------------------------------

--
-- Table structure for table `export_logs`
--

CREATE TABLE `export_logs` (
  `id` int(11) NOT NULL,
  `admin_email` varchar(255) NOT NULL,
  `report_type` varchar(50) NOT NULL,
  `export_date` datetime DEFAULT current_timestamp(),
  `file_name` varchar(255) NOT NULL,
  `admin_name` varchar(255) NOT NULL,
  `file_type` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `export_logs`
--

INSERT INTO `export_logs` (`id`, `admin_email`, `report_type`, `export_date`, `file_name`, `admin_name`, `file_type`) VALUES
(41, 'redmercy.ros@gmail.com', 'accepted_scholars', '2024-12-13 19:40:53', 'scholar', 'WILLIAM DUNCAN OLLERO GONZALES', 'excel'),
(42, 'redmercy.ros@gmail.com', 'accepted_volunteers', '2024-12-13 19:41:34', 'volunteer', 'WILLIAM DUNCAN OLLERO GONZALES', 'excel'),
(43, 'redmercy.ros@gmail.com', 'accepted_volunteers', '2024-12-13 19:42:03', 'volunteer', 'WILLIAM DUNCAN OLLERO GONZALES', 'pdf'),
(44, 'redmercy.ros@gmail.com', 'accepted_scholars', '2024-12-13 19:42:17', 'volunteer', 'WILLIAM DUNCAN OLLERO GONZALES', 'pdf'),
(45, 'redmercy.ros@gmail.com', 'accepted_scholars', '2024-12-13 19:42:25', 'scholar', 'WILLIAM DUNCAN OLLERO GONZALES', 'pdf'),
(46, 'redmercy.ros@gmail.com', 'accepted_scholars', '2024-12-13 21:22:28', 'Accepted scholar', 'WILLIAM DUNCAN OLLERO GONZALES', 'excel'),
(47, 'redmercy.ros@gmail.com', 'accepted_volunteers', '2024-12-13 21:23:25', 'Accepted volunteer', 'WILLIAM DUNCAN OLLERO GONZALES', 'excel');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` varchar(255) NOT NULL,
  `status` enum('unread','read') DEFAULT 'unread',
  `category` enum('form_submission','admin_approval','event','other') DEFAULT 'other',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id` int(255) NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `middle_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `birthday` date NOT NULL,
  `gender` varchar(20) NOT NULL,
  `email` varchar(255) NOT NULL,
  `country` varchar(255) DEFAULT 'Philippines',
  `password` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `otp` varchar(6) NOT NULL,
  `is_verified` tinyint(1) NOT NULL,
  `otp_expiry` datetime NOT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_expiry` datetime DEFAULT NULL,
  `role` varchar(255) NOT NULL DEFAULT 'user',
  `profile_picture` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id`, `first_name`, `middle_name`, `last_name`, `birthday`, `gender`, `email`, `country`, `password`, `created_at`, `otp`, `is_verified`, `otp_expiry`, `reset_token`, `reset_expiry`, `role`, `profile_picture`) VALUES
(32, 'WILLIAM DUNCAN', 'OLLERO', 'GONZALES', '2024-11-19', 'male', 'redmercy.ros@gmail.com', 'Philippines', '$2y$10$WhdZiwXhNGs7rFvsg7mA4uG.rHxCflo83IJgoF7sxqLeEzK.QWgE2', '2024-12-01 13:56:15', '', 1, '0000-00-00 00:00:00', 'a6e690f74ab72e0078cd2780faec4df20acced5e63c9075689c2bf6d24edd663', NULL, 'admin', NULL),
(34, 'WILLIAM DUNCAN', 'OLLERO', 'GONZALES', '2024-11-11', 'male', 'botchok.gonzales@gmail.com', 'Philippines', '$2y$10$BXgk6NZGy2KzveFwVH0tZuMPpLt4lZbz1GMMs/Scitnvrpb9pobf.', '2025-02-18 08:01:57', '', 1, '0000-00-00 00:00:00', NULL, NULL, 'user', 'profile.jpeg'),
(36, ' botchok', '', 'gonzales', '2025-01-28', 'male', 'velcro.mir4@gmail.com', 'Philippines', '$2y$10$sLbYFJIxLYNY27HNqaJECeh4slEQtOByVgV7jWaMNLjAZ3KnOBRMO', '2025-01-30 06:20:52', '', 1, '0000-00-00 00:00:00', NULL, NULL, 'user', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `volunteer_application`
--

CREATE TABLE `volunteer_application` (
  `id` int(11) NOT NULL,
  `work_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `application_date` datetime NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `remarks` text DEFAULT NULL,
  `resume_path` varchar(255) DEFAULT NULL,
  `phone` varchar(12) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `volunteer_application`
--

INSERT INTO `volunteer_application` (`id`, `work_id`, `user_id`, `name`, `email`, `application_date`, `status`, `remarks`, `resume_path`, `phone`) VALUES
(9, 1, 34, 'WILLIAM DUNCAN  GONZALES', 'botchok.gonzales@gmail.com', '2025-01-18 23:58:07', 'approved', NULL, '/../Documents/Volunteer/Gonzales, William Duncan, Resume.pdf', '09506785100');

-- --------------------------------------------------------

--
-- Table structure for table `works`
--

CREATE TABLE `works` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `work_datetime` datetime NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `location` varchar(255) NOT NULL,
  `requirements` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `works`
--

INSERT INTO `works` (`id`, `title`, `description`, `work_datetime`, `image_path`, `location`, `requirements`) VALUES
(1, 'Tree Planting', 'Planting trees', '2024-12-25 08:40:00', '../images/tan.jpg', 'MAKATI', 'Pogi'),
(2, '213', '21', '2024-11-06 02:19:00', '../images/tan.jpg', '', '');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `applications`
--
ALTER TABLE `applications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `export_logs`
--
ALTER TABLE `export_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_email` (`admin_email`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `volunteer_application`
--
ALTER TABLE `volunteer_application`
  ADD PRIMARY KEY (`id`),
  ADD KEY `work_id` (`work_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `works`
--
ALTER TABLE `works`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `applications`
--
ALTER TABLE `applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=82;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `export_logs`
--
ALTER TABLE `export_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `volunteer_application`
--
ALTER TABLE `volunteer_application`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `works`
--
ALTER TABLE `works`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `applications`
--
ALTER TABLE `applications`
  ADD CONSTRAINT `applications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `applications_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `export_logs`
--
ALTER TABLE `export_logs`
  ADD CONSTRAINT `export_logs_ibfk_1` FOREIGN KEY (`admin_email`) REFERENCES `user` (`email`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`);

--
-- Constraints for table `volunteer_application`
--
ALTER TABLE `volunteer_application`
  ADD CONSTRAINT `volunteer_application_ibfk_1` FOREIGN KEY (`work_id`) REFERENCES `works` (`id`),
  ADD CONSTRAINT `volunteer_application_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
