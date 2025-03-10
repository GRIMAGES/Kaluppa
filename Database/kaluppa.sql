-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 10, 2025 at 03:11 PM
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
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` enum('published','draft','archived') DEFAULT 'published'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`id`, `title`, `content`, `image`, `created_at`, `updated_at`, `status`) VALUES
(4, '123', '21312', 'uploads/RobloxScreenShot20240728_212833872.png', '2025-02-26 17:18:20', '2025-02-26 17:18:20', 'published');

-- --------------------------------------------------------

--
-- Table structure for table `applications`
--

CREATE TABLE `applications` (
  `id` varchar(20) NOT NULL,
  `user_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `middle_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `house_number` varchar(255) NOT NULL,
  `street` varchar(255) NOT NULL,
  `barangay` varchar(255) NOT NULL,
  `district` varchar(255) NOT NULL,
  `city` varchar(255) NOT NULL,
  `region` varchar(255) NOT NULL,
  `postal_code` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `document` text NOT NULL,
  `status` enum('pending','approved','rejected','under review','enrolled') DEFAULT 'pending',
  `applied_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `applications`
--

INSERT INTO `applications` (`id`, `user_id`, `course_id`, `first_name`, `middle_name`, `last_name`, `house_number`, `street`, `barangay`, `district`, `city`, `region`, `postal_code`, `email`, `document`, `status`, `applied_at`) VALUES
('APP-00001', 34, 36, 'WILLIAM DUNCAN', 'OLLERO', 'GONZALES', '350', 'Chico', 'Cembo', '2', 'Makati', 'NCR', '1214', 'botchok.gonzales@gmail.com', '/Backend/Documents/Scholarship/1740735573_5.-Recommendation-Letter.docx.pdf,/Backend/Documents/Scholarship/1740735573_CLP-QF-06_Recommendation_Letter.docx,/Backend/Documents/Scholarship/1740735573_MOA.docx', 'pending', '2025-02-28 09:39:33'),
('APP-00002', 34, 38, 'WILLIAM DUNCAN', 'OLLERO', 'GONZALES', '350', 'Chico', 'Cembo', '2', 'Makati', 'NCR', '1214', 'botchok.gonzales@gmail.com', '/Backend/Documents/Scholarship/1740848079_5._Recommendation_Letter.docx,/Backend/Documents/Scholarship/1740848079_5.-Recommendation-Letter.docx.pdf,/Backend/Documents/Scholarship/1740848079_1740507175_5._Recommendation_Letter__1_.docx', 'pending', '2025-03-01 16:54:39'),
('APP-00003', 43, 36, 'WILLIAM DUNCAN', 'OLLERO', 'GONZALES', '350', 'Chico', 'Cembo', '2', 'Makati', 'NCR', '1214', 'gonzales.williamduncan7@gmail.com', '/Backend/Documents/Scholarship/1741613315_generated_certificates__1_.pdf', 'pending', '2025-03-10 13:28:35'),
('APP-00004', 43, 37, 'WILLIAM DUNCAN', 'OLLERO', 'GONZALES', '350', 'Chico', 'Cembo', '2', 'Makati', 'NCR', '1214', 'gonzales.williamduncan7@gmail.com', '/Backend/Documents/Scholarship/1741613571_generated_certificates__1_.pdf', 'pending', '2025-03-10 13:32:51');

-- --------------------------------------------------------

--
-- Table structure for table `certificate_templates`
--

CREATE TABLE `certificate_templates` (
  `id` int(11) NOT NULL,
  `template_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `uploaded_by` varchar(255) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `certificate_type` varchar(50) NOT NULL,
  `font_full_name` varchar(50) NOT NULL,
  `font_course_name` varchar(50) NOT NULL,
  `font_certificate_no` varchar(50) NOT NULL,
  `font_date` varchar(50) NOT NULL,
  `pos_full_name_x` int(11) NOT NULL,
  `pos_full_name_y` int(11) NOT NULL,
  `pos_course_name_x` int(11) NOT NULL,
  `pos_course_name_y` int(11) NOT NULL,
  `pos_certificate_no_x` int(11) NOT NULL,
  `pos_certificate_no_y` int(11) NOT NULL,
  `pos_date_x` int(11) NOT NULL,
  `pos_date_y` int(11) NOT NULL,
  `size_full_name` int(11) NOT NULL,
  `size_course_name` int(11) NOT NULL,
  `size_certificate_no` int(11) NOT NULL,
  `size_date` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `certificate_templates`
--

INSERT INTO `certificate_templates` (`id`, `template_name`, `file_path`, `uploaded_by`, `uploaded_at`, `certificate_type`, `font_full_name`, `font_course_name`, `font_certificate_no`, `font_date`, `pos_full_name_x`, `pos_full_name_y`, `pos_course_name_x`, `pos_course_name_y`, `pos_certificate_no_x`, `pos_certificate_no_y`, `pos_date_x`, `pos_date_y`, `size_full_name`, `size_course_name`, `size_certificate_no`, `size_date`) VALUES
(1, 'Simple modern Appreciation Certificate.png', '../../certificate_templates/Simple modern Appreciation Certificate.png', 'botchok.gonzales@gmail.com', '2025-03-07 21:08:11', 'volunteer', 'PinyonScript', 'Times', 'Times', 'Times', 0, 0, 0, 0, 0, 0, 0, 0, 36, 16, 12, 12),
(2, 'test1.png', '../../certificate_templates/Simple modern Appreciation Certificate.png', 'botchok.gonzales@gmail.com', '2025-03-07 21:18:20', 'volunteer', 'PinyonScript', 'Times', 'Times', 'Times', 0, 110, 0, 135, 0, 180, 0, 205, 36, 16, 12, 12),
(3, 'test2.png', '../../certificate_templates/Simple modern Appreciation Certificate.png', 'botchok.gonzales@gmail.com', '2025-03-07 21:22:10', 'volunteer', 'PinyonScript', 'Times', 'Times', 'Times', 0, 110, 0, 135, 0, 180, 0, 205, 30, 14, 12, 12),
(4, 'Simple modern Appreciation Certificate.png', '../../certificate_templates/Simple modern Appreciation Certificate.png', 'botchok.gonzales@gmail.com', '2025-03-07 21:26:00', 'volunteer', 'PinyonScript', 'Times', 'Times', 'Times', 50, 110, 50, 135, 50, 180, 50, 205, 30, 16, 12, 12),
(5, 'test3', '../../certificate_templates/Simple modern Appreciation Certificate.png', 'botchok.gonzales@gmail.com', '2025-03-07 21:38:29', 'volunteer', 'PinyonScript', 'Times', 'Times', 'Times', 50, 110, 50, 135, 50, 180, 50, 205, 36, 16, 12, 12),
(6, 'test3', '../../certificate_templates/Simple modern Appreciation Certificate.png', 'botchok.gonzales@gmail.com', '2025-03-07 21:40:58', 'volunteer', 'PinyonScript', 'Times', 'Times', 'Times', 50, 110, 50, 135, 50, 180, 50, 205, 36, 16, 12, 12),
(7, 'test3', '../../certificate_templates/Simple modern Appreciation Certificate.png', 'botchok.gonzales@gmail.com', '2025-03-07 21:42:47', 'volunteer', 'PinyonScript', 'Times', 'Times', 'Times', 50, 110, 50, 135, 50, 180, 50, 205, 30, 16, 12, 12);

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
  `status` enum('upcoming','ongoing','completed') NOT NULL DEFAULT 'upcoming',
  `duration` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`id`, `image`, `name`, `description`, `instructor`, `requisites`, `capacity`, `enrolled_students`, `status`, `duration`) VALUES
(36, 'barista.jpeg', 'Barista NCII', 'The Barista NC II Qualification consists of competencies that a person must achieve in the deliverance of good quality coffee in commercially-operated cafes or specialty coffee shops. This qualification is specific to a person who specializes in making coffee beverages. A person who has achieved this qualification is competent to be a barista.', 'John Paul Santos', 'Coffee Making', 5, 1, 'ongoing', '178 hours, 23 days'),
(37, 'IT.jpeg', 'Information Technology', 'information technology', 'William Duncan Gonzales', 'Software and Hardware', 21, 1, 'ongoing', '178 hours, 23 days'),
(38, 'IT.jpeg', '1', 'i', '21', '21', 21, 0, 'ongoing', '178 hours, 23 days');

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
(10, 'Thanksgiving', 'THANKS.jpeg', '2025-01-30 09:40:00', 'William Duncan Gonzales', 'Thanksgiving', '2024-11-29 11:40:59');

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
(47, 'redmercy.ros@gmail.com', 'accepted_volunteers', '2024-12-13 21:23:25', 'Accepted volunteer', 'WILLIAM DUNCAN OLLERO GONZALES', 'excel'),
(48, 'redmercy.ros@gmail.com', 'enrolled_scholars', '2025-02-28 17:22:48', 'scholar', 'WILLIAM DUNCAN OLLERO GONZALES', 'excel'),
(49, 'redmercy.ros@gmail.com', 'enrolled_scholars', '2025-02-28 17:40:05', 'scholar', 'WILLIAM DUNCAN OLLERO GONZALES', 'excel'),
(50, 'redmercy.ros@gmail.com', 'enrolled_scholars', '2025-02-28 17:40:27', 'scholar', 'WILLIAM DUNCAN OLLERO GONZALES', 'excel'),
(51, 'redmercy.ros@gmail.com', 'enrolled_scholars', '2025-02-28 17:43:40', 'qweq', 'WILLIAM DUNCAN OLLERO GONZALES', 'pdf');

-- --------------------------------------------------------

--
-- Table structure for table `featured_cards`
--

CREATE TABLE `featured_cards` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `details` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `featured_cards`
--

INSERT INTO `featured_cards` (`id`, `title`, `description`, `image`, `details`) VALUES
(1, 'Featured Course', '☕ Barista Training Course: Master the Art of Coffee Making\r\n', 'barista.jpeg', 'Unleash your passion for coffee with our comprehensive Barista Training Course! Whether you\'re a coffee enthusiast or aspiring to start a career in the café industry, this course equips you with the essential skills and techniques to become a professional barista. Learn the fundamentals of coffee brewing, espresso extraction, milk frothing, latte art, customer service, and machine maintenance—all guided by industry experts.\r\n\r\nWhat You\'ll Learn:\r\nCoffee origins, types, and flavor profiles\r\nEspresso preparation and advanced brewing methods\r\nMilk texturing and latte art techniques\r\nProper use and maintenance of coffee equipment\r\nHygiene, safety, and customer service best practices\r\n\r\nWho Should Enroll:\r\nAspiring baristas and café staff\r\nCoffee lovers who want to deepen their skills\r\nEntrepreneurs planning to start a coffee business\r\nDuration:\r\n2–4 weeks (flexible schedules available)\r\n\r\nCertificate:\r\nReceive a Certificate of Completion and boost your credentials in the café and hospitality industry.'),
(2, 'Exciting Work', '🌱 Volunteer Work: Tree Planting for a Greener Tomorrow', 'tree planting.jpeg', 'Join us in making a lasting impact on the environment through our Tree Planting Volunteer Program. This initiative is a hands-on opportunity to contribute to reforestation, combat climate change, and create healthier communities. Whether you\'re a nature lover, a student, or someone who wants to give back, your helping hands can make a difference—one tree at a time.\r\n\r\nWhat You\'ll Do:\r\nPlant native trees in selected areas\r\nPrepare soil and maintain planting zones\r\nLearn about biodiversity and sustainable ecosystems\r\nCollaborate with fellow volunteers and environmental experts\r\nWhy Join?\r\nHelp restore natural habitats\r\nPromote cleaner air and reduce carbon footprint\r\nEarn volunteer hours and experience\r\nBe part of a community-driven environmental movement\r\nWho Can Join:\r\nStudents, professionals, organizations, and anyone passionate about nature\r\nNo prior experience required—just your willingness to help\r\nDuration & Schedule:\r\n1-day or weekend activities (dates may vary)\r\n\r\nCertificate:\r\nVolunteers will receive a Certificate of Participation and a sense of fulfillment for contributing to a greener planet.'),
(3, 'Upcoming Events', '🦃 Upcoming Event: Thanksgiving Celebration', 'THANKS.jpeg', 'Join us for a heartwarming Thanksgiving Celebration filled with gratitude, togetherness, and joy! Let’s gather as a community to celebrate the spirit of thankfulness with meaningful activities, good food, and memorable moments.\r\n\r\nEvent Highlights:\r\nThanksgiving Dinner Feast 🍽️\r\nCommunity Sharing & Reflections 🧡\r\nLive Music & Performances 🎶\r\nFun Games & Group Activities 🎯\r\nVolunteer Appreciation & Recognition 🏅\r\n\r\nWho Can Attend:\r\nEveryone is welcome—students, volunteers, families, and community members!\r\n\r\nWhy You Should Join:\r\nCelebrate the season of gratitude with a warm community\r\nConnect with others and share stories of kindness and hope\r\nEnjoy a joyful evening filled with food, fun, and fellowship\r\nSpecial Note:\r\nFeel free to bring donations, small gifts, or letters of appreciation to share with our volunteers and community partners.\r\n\r\n');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` varchar(255) NOT NULL,
  `status` enum('unread','read') DEFAULT 'unread',
  `category` enum('application','event','system','reminder') NOT NULL DEFAULT 'application',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `email` varchar(255) DEFAULT NULL
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
  `house_number` varchar(255) NOT NULL,
  `street` varchar(255) NOT NULL,
  `barangay` varchar(255) NOT NULL,
  `district` varchar(255) NOT NULL,
  `city` varchar(255) NOT NULL,
  `region` varchar(255) NOT NULL,
  `postal_code` varchar(255) NOT NULL,
  `country` varchar(255) DEFAULT 'Philippines',
  `password` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `otp` varchar(6) NOT NULL,
  `is_verified` tinyint(1) NOT NULL,
  `otp_expiry` datetime NOT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_expiry` datetime DEFAULT NULL,
  `role` varchar(255) NOT NULL DEFAULT 'user',
  `profile_picture` varchar(255) DEFAULT NULL,
  `phone` varchar(12) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id`, `first_name`, `middle_name`, `last_name`, `birthday`, `gender`, `email`, `house_number`, `street`, `barangay`, `district`, `city`, `region`, `postal_code`, `country`, `password`, `created_at`, `otp`, `is_verified`, `otp_expiry`, `reset_token`, `reset_expiry`, `role`, `profile_picture`, `phone`) VALUES
(32, 'WILLIAM DUNCAN', 'OLLERO', 'GONZALES', '2024-11-19', 'male', 'redmercy.ros@gmail.com', '', '', '', '', '', '', '', 'Philippines', '$2y$10$WhdZiwXhNGs7rFvsg7mA4uG.rHxCflo83IJgoF7sxqLeEzK.QWgE2', '2025-03-04 01:23:55', '', 1, '0000-00-00 00:00:00', '6a22aa902217d26aee5ab02723e6c5430b29bf3d7b2285d3541bf0daa88288f8', NULL, 'admin', NULL, '0'),
(34, 'WILLIAM DUNCAN', 'OLLERO', 'GONZALES', '2024-11-11', 'male', 'botchok.gonzales@gmail.com', '350', 'Chico', 'Cembo', '2', 'Makati', 'NCR', '1214', 'Philippines', '$2y$10$BXgk6NZGy2KzveFwVH0tZuMPpLt4lZbz1GMMs/Scitnvrpb9pobf.', '2025-03-06 15:51:57', '', 1, '0000-00-00 00:00:00', NULL, NULL, 'admin', 'man.jpeg', '0'),
(43, 'WILLIAM DUNCAN', 'OLLERO', 'GONZALES', '2025-02-27', 'male', 'gonzales.williamduncan7@gmail.com', '350', 'Chico', 'Cembo', '2', 'Makati', 'NCR', '1214', 'Philippines', '$2y$10$6UHe8.xnFTf3kWRYCVeyvuNMq/aiPXKHWKBL1r/wMSx/2VqnfvM4C', '2025-03-06 14:13:13', '', 1, '0000-00-00 00:00:00', NULL, NULL, 'user', 'man.jpeg', '09506785100'),
(44, 'WILLIAM DUNCAN', 'OLLERO', 'GONZALES', '2025-03-28', 'male', 'gonzales.williamdumcam7@gmail.com', '350', 'Chico', 'cembo', '2', 'Makati', 'NCR', '1214', 'Philippines', '$2y$10$Tp85Hw6ZNI5AH1zizVVkh.vCIZlTwtdwVgrvipOk58teRpwapdtNK', '2025-03-02 18:42:10', '962511', 0, '0000-00-00 00:00:00', NULL, NULL, 'user', NULL, '09506785100'),
(45, 'WILLIAM DUNCAN', 'OLLERO', 'GONZALES', '2025-03-05', 'male', 'velcro.mir4@gmail.com', '350', 'Chico', 'Cembo', '2', 'Makati', 'NCR', '1214', 'Philippines', '$2y$10$cjxMbsQMxLpPtNOBJQibFuA7Z2Qr9BrqiXQLqeZBG/zjvvhYKUmge', '2025-03-04 01:24:51', '', 1, '0000-00-00 00:00:00', NULL, NULL, 'admin', NULL, '09506785100');

-- --------------------------------------------------------

--
-- Table structure for table `volunteer_application`
--

CREATE TABLE `volunteer_application` (
  `id` varchar(11) NOT NULL,
  `work_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `application_date` datetime NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `remarks` text DEFAULT NULL,
  `resume_path` varchar(255) DEFAULT NULL,
  `phone` varchar(12) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `house_number` varchar(50) NOT NULL,
  `street` varchar(255) NOT NULL,
  `barangay` varchar(255) NOT NULL,
  `district` varchar(255) NOT NULL,
  `city` varchar(255) NOT NULL,
  `region` varchar(255) NOT NULL,
  `postal_code` varchar(20) NOT NULL,
  `applied_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `volunteer_application`
--

INSERT INTO `volunteer_application` (`id`, `work_id`, `user_id`, `email`, `application_date`, `status`, `remarks`, `resume_path`, `phone`, `first_name`, `middle_name`, `last_name`, `house_number`, `street`, `barangay`, `district`, `city`, `region`, `postal_code`, `applied_at`) VALUES
('VOL-00001', 1, 34, 'botchok.gonzales@gmail.com', '2025-03-02 01:03:19', 'pending', NULL, 'C:\\xampp\\php\\Kaluppa\\Backend\\Documents\\Volunteer\\5. Recommendation Letter.docx', '09506785100', 'WILLIAM DUNCAN', 'OLLERO', 'GONZALES', '350', 'Chico', 'Cembo', '2', 'Makati', 'NCR', '1214', '2025-03-01 17:03:19'),
('VOL-00002', 2, 34, 'botchok.gonzales@gmail.com', '2025-03-02 15:06:57', 'pending', NULL, 'C:\\xampp\\php\\Kaluppa\\Backend\\Documents\\Volunteer\\5. Recommendation Letter.docx', '09506785100', 'WILLIAM DUNCAN', 'OLLERO', 'GONZALES', '350', 'Chico', 'Cembo', '2', 'Makati', 'NCR', '1214', '2025-03-02 07:06:57'),
('VOL-00003', 3, 34, 'botchok.gonzales@gmail.com', '2025-03-02 15:08:36', 'pending', NULL, 'C:\\xampp\\php\\Kaluppa\\Backend\\Documents\\Volunteer\\5. Recommendation Letter.docx', '09506785100', 'WILLIAM DUNCAN', 'OLLERO', 'GONZALES', '350', 'Chico', 'Cembo', '2', 'Makati', 'NCR', '1214', '2025-03-02 07:08:36'),
('VOL-00004', 1, 43, 'gonzales.williamduncan7@gmail.com', '2025-03-10 21:08:09', 'pending', NULL, 'C:\\xampp\\php\\Kaluppa\\Backend\\Documents\\Volunteer\\generated_certificates (1).pdf', '09954280476', 'WILLIAM DUNCAN', 'OLLERO', 'GONZALES', '350', 'Chico', 'Cembo', '2', 'Makati', 'NCR', '1214', '2025-03-10 13:08:09'),
('VOL-00005', 2, 43, 'gonzales.williamduncan7@gmail.com', '2025-03-10 21:33:03', 'pending', NULL, 'C:\\xampp\\php\\Kaluppa\\Backend\\Documents\\Volunteer\\generated_certificates (1).pdf', '09506785100', 'WILLIAM DUNCAN', 'OLLERO', 'GONZALES', '350', 'Chico', 'Cembo', '2', 'Makati', 'NCR', '1214', '2025-03-10 13:33:03'),
('VOL-00006', 3, 43, 'gonzales.williamduncan7@gmail.com', '2025-03-10 21:34:55', 'pending', NULL, 'C:\\xampp\\php\\Kaluppa\\Backend\\Documents\\Volunteer\\generated_certificates (4).pdf', '09506785100', 'WILLIAM DUNCAN', 'OLLERO', 'GONZALES', '350', 'Chico', 'Cembo', '2', 'Makati', 'NCR', '1214', '2025-03-10 13:34:55');

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
(1, 'Tree Planting', 'Planting trees', '2024-12-25 08:40:00', '../images/tree planting.jpeg', 'MAKATI', 'masipag'),
(2, '213', '21', '2024-11-06 02:19:00', '../images/tree planting.jpeg', 'MAKATI', 'masa'),
(3, 'weq', 'qweq', '2025-03-19 17:09:00', '../images/tree planting.jpeg', 'MAKATI', 'wtrtrwr');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `applications`
--
ALTER TABLE `applications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `certificate_templates`
--
ALTER TABLE `certificate_templates`
  ADD PRIMARY KEY (`id`);

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
-- Indexes for table `featured_cards`
--
ALTER TABLE `featured_cards`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_notifications_users` (`user_id`);

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
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `certificate_templates`
--
ALTER TABLE `certificate_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT for table `featured_cards`
--
ALTER TABLE `featured_cards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `works`
--
ALTER TABLE `works`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
  ADD CONSTRAINT `fk_notifications_users` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
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
