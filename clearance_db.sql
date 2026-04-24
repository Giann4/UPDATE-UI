-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 24, 2026 at 06:30 AM
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
-- Database: `clearance_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` varchar(20) DEFAULT NULL,
  `profile_photo` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `name`, `email`, `password`, `role`, `profile_photo`) VALUES
(1, 'Admin', 'admin@gmail.com', 'c93ccd78b2076528346216b3b2f701e6', 'admin', 'admin_1_1776331551.png');

-- --------------------------------------------------------

--
-- Table structure for table `class_members`
--

CREATE TABLE `class_members` (
  `id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `class_requests`
--

CREATE TABLE `class_requests` (
  `id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `subject` varchar(100) NOT NULL,
  `status` varchar(50) DEFAULT 'Requesting',
  `result` varchar(50) DEFAULT '',
  `comment` text DEFAULT NULL,
  `date_signed` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `class_requests`
--

INSERT INTO `class_requests` (`id`, `class_id`, `student_id`, `subject`, `status`, `result`, `comment`, `date_signed`, `created_at`) VALUES
(1, 1, 3, 'S A A D', 'Reviewed', 'Passed', 'GOOD', '2026-04-12', '2026-04-12 06:29:04'),
(3, 3, 3, 'NETWORKING', 'Reviewed', 'Passed', 'CONGRATS', '2026-04-12', '2026-04-12 06:45:06'),
(4, 4, 3, 'IAS', 'Reviewed', 'Passed', 'CONGRATS', '2026-04-12', '2026-04-12 06:46:59'),
(5, 5, 3, 'ARDUINO', 'Reviewed', 'Passed', 'CONGRATS', '2026-04-15', '2026-04-12 06:48:47'),
(6, 6, 3, 'ENGLISH', 'Reviewed', 'Passed', 'CONGRATS', '2026-04-12', '2026-04-12 06:50:58'),
(7, 7, 3, 'CODE', 'Reviewed', 'Passed', 'CONGRATS', '2026-04-12', '2026-04-12 07:00:10'),
(8, 8, 3, 'CALCULUS', 'Reviewed', 'Passed', 'CONGRATS', '2026-04-12', '2026-04-12 15:58:55'),
(9, 9, 3, 'PE', 'Reviewed', 'Passed', '1234', '2026-04-12', '2026-04-12 16:06:33'),
(10, 10, 12, 'SCIENCE', 'Reviewed', 'Passed', 'CONGRATS', '2026-04-12', '2026-04-12 17:53:36'),
(11, 11, 14, 'AP', 'Reviewed', 'Passed', 'CONGRATS', '2026-04-12', '2026-04-12 19:21:23'),
(12, 14, 19, 'S A A D', 'Reviewed', 'Failed', 'SEE YOU NEXT YEAR', '2026-04-18', '2026-04-18 07:20:48'),
(13, 14, 20, 'S A A D', 'Reviewed', 'Passed', 'CONGRATS', '2026-04-18', '2026-04-18 07:22:17'),
(14, 15, 19, 'CODING', 'Reviewed', 'Passed', 'CONGRATS', '2026-04-18', '2026-04-18 07:27:02');

-- --------------------------------------------------------

--
-- Table structure for table `teacher_album`
--

CREATE TABLE `teacher_album` (
  `id` int(11) NOT NULL,
  `teacher_name` varchar(150) NOT NULL,
  `teacher_photo` varchar(255) DEFAULT NULL,
  `teacher_email` varchar(150) DEFAULT NULL,
  `teacher_contact` varchar(50) DEFAULT NULL,
  `teacher_department` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teacher_album`
--

INSERT INTO `teacher_album` (`id`, `teacher_name`, `teacher_photo`, `teacher_email`, `teacher_contact`, `teacher_department`, `created_at`) VALUES
(1, 'ALLETE MANZANERO', '1776320441_4380.jpg', 'alette@gmail.com', '09851642711', 'IT DEPARTMENT', '2026-04-16 06:11:44'),
(3, 'ANGELO VILLIANUEVA', '1776320962_7238.jpg', 'angelo@gmail.com', '09851642711', 'IT DEPARTMENT', '2026-04-16 06:29:22'),
(4, 'KENJI UY', '1776321024_9877.jpg', 'kenji@gmail.com', '09851642711', 'IT DEPARTMENT', '2026-04-16 06:30:24'),
(5, 'DENNIS SANTOS', '1776321083_1276.jpg', 'denis@gmail.com', '09851642711', 'IT DEPARTMENT', '2026-04-16 06:31:13'),
(6, 'DONALENE TOCMO', '1776321149_2576.jpg', 'donalene@gmail.com', '09851642711', 'IT DEPARTMENT', '2026-04-16 06:32:29'),
(7, 'MARIA ANGEL BERNASE', '1776321473_6911.jpg', 'angel@gmail.com', '09851642711', 'IT DEPARTMENT', '2026-04-16 06:37:53');

-- --------------------------------------------------------

--
-- Table structure for table `teacher_classes`
--

CREATE TABLE `teacher_classes` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `subject` varchar(100) NOT NULL,
  `course` varchar(50) NOT NULL,
  `class_code` varchar(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teacher_classes`
--

INSERT INTO `teacher_classes` (`id`, `teacher_id`, `subject`, `course`, `class_code`, `created_at`) VALUES
(1, 2, 'S A A D', 'BSIT 3', 'mc46nfw', '2026-04-12 05:00:39'),
(3, 5, 'NETWORKING', 'BSIT 3', '2puqa93', '2026-04-12 06:44:27'),
(4, 6, 'IAS', 'BSIT 3', '4ti31ze', '2026-04-12 06:46:39'),
(5, 7, 'ARDUINO', 'BSIT 3', 'es0jo1c', '2026-04-12 06:48:26'),
(6, 8, 'ENGLISH', 'BSIT 3', 'hu8pikn', '2026-04-12 06:50:34'),
(7, 9, 'CODE', 'BSIT 3', 'dvlccmp', '2026-04-12 06:59:46'),
(8, 10, 'CALCULUS', 'BSIT 3', '16ccs8k', '2026-04-12 15:58:31'),
(9, 11, 'PE', 'BSIT 3', '1d9l5jy', '2026-04-12 16:05:56'),
(10, 2, 'SCIENCE', 'BSIT 1', '2DWKS27J', '2026-04-12 17:07:13'),
(11, 13, 'AP', 'BSIT 2', 'SVUA4CFL', '2026-04-12 19:20:08'),
(13, 4, 'HUMAN COMPUTER INTERACTION', 'BSIT 3', 'VXZPLNYK', '2026-04-15 14:00:24'),
(14, 18, 'S A A D', 'BSIT 3', 'EJCTZS83', '2026-04-18 07:18:32'),
(15, 21, 'CODING', 'BSIT 3', 'JL5LCANK', '2026-04-18 07:26:25');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `firstname` varchar(100) DEFAULT NULL,
  `lastname` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` enum('student','teacher') DEFAULT NULL,
  `course` varchar(50) DEFAULT NULL,
  `profile_photo` varchar(255) DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `firstname`, `lastname`, `email`, `contact_number`, `password`, `role`, `course`, `profile_photo`, `is_deleted`, `deleted_at`) VALUES
(1, 'Mark', 'Paredes', 'markparedes54321@gmail.com', '09602097975', '81dc9bdb52d04dc20036dbd8313ed055', 'student', 'BSIT 3', 'user_1_1775969467.jpg', 0, NULL),
(4, 'alette', 'manzanero', 'alette@gmail.com', '09602097975', '81dc9bdb52d04dc20036dbd8313ed055', 'teacher', NULL, NULL, 0, NULL),
(5, 'Kenji', 'Uy', 'kenji@gmail.com', '09602097975', '81dc9bdb52d04dc20036dbd8313ed055', 'teacher', NULL, NULL, 0, NULL),
(6, 'Angelo', 'Villianueva', 'Angelo@gmail.com', '09602097975', '81dc9bdb52d04dc20036dbd8313ed055', 'teacher', NULL, NULL, 0, NULL),
(7, 'Angel', 'Bernase', 'Angel@gmail.com', '09602097975', '81dc9bdb52d04dc20036dbd8313ed055', 'teacher', NULL, 'teacher_7_1776276983.png', 0, NULL),
(9, 'Dennis', 'Santos', 'dennis@gmail.com', '09602097975', '81dc9bdb52d04dc20036dbd8313ed055', 'teacher', NULL, NULL, 0, NULL),
(10, 'Daryl', 'Esio', 'daryl@gmail.com', '09602097975', '81dc9bdb52d04dc20036dbd8313ed055', 'teacher', NULL, NULL, 1, '2026-04-23 14:24:38'),
(12, 'natasha', 'Esio', 'natasha@gmail.com', '09602097975', '81dc9bdb52d04dc20036dbd8313ed055', 'student', 'BSIT 1', NULL, 0, NULL),
(14, 'JECK', 'DETERA', 'jeck@gmail.com', '09602097975', '81dc9bdb52d04dc20036dbd8313ed055', 'student', 'BSIT 2', 'student_14_1776021794.png', 0, NULL),
(15, 'Harlie', 'Poras', 'harlie@gmail.com', '09602097975', '81dc9bdb52d04dc20036dbd8313ed055', 'student', 'BSIT 3', NULL, 0, NULL),
(16, 'Martin', 'Angeles', 'martin@gmail.com', '09602097975', '81dc9bdb52d04dc20036dbd8313ed055', 'student', 'BSIT 3', NULL, 1, '2026-04-23 13:47:21'),
(18, 'DONALENE', 'TOCMO', 'donalene@gmail.com', '09851642711', '81dc9bdb52d04dc20036dbd8313ed055', 'teacher', NULL, NULL, 1, '2026-04-23 12:58:53'),
(19, 'GIAN', 'ESIO', 'gian@gmail.com', '09851642711', '81dc9bdb52d04dc20036dbd8313ed055', 'student', 'BSIT 3', NULL, 1, '2026-04-23 14:24:45'),
(22, 'PATRICIO EZEKIEL', 'MAGDAME', 'patricio@gmail.com', '09602097975', '70f83b32b430dd05e2c39ff69c08c375', 'student', 'BSIT 1', 'student_22_1776927318.png', 0, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `class_members`
--
ALTER TABLE `class_members`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `class_requests`
--
ALTER TABLE `class_requests`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `teacher_album`
--
ALTER TABLE `teacher_album`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `teacher_classes`
--
ALTER TABLE `teacher_classes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `class_code` (`class_code`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `class_members`
--
ALTER TABLE `class_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `class_requests`
--
ALTER TABLE `class_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `teacher_album`
--
ALTER TABLE `teacher_album`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `teacher_classes`
--
ALTER TABLE `teacher_classes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
