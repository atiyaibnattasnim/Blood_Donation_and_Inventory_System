-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 18, 2025 at 03:43 AM
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
-- Database: `blood_donation_and_inventory_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`user_id`) VALUES
(17);

-- --------------------------------------------------------

--
-- Table structure for table `blood_unit`
--

CREATE TABLE `blood_unit` (
  `blood_unit_id` int(11) NOT NULL,
  `blood_group` enum('A','B','AB','O') NOT NULL,
  `rh_factor` enum('+','-') NOT NULL,
  `collection_date` date NOT NULL,
  `expiry_date` date NOT NULL,
  `status` enum('available','reserved','used','expired') DEFAULT 'available',
  `storage_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `blood_unit`
--

INSERT INTO `blood_unit` (`blood_unit_id`, `blood_group`, `rh_factor`, `collection_date`, `expiry_date`, `status`, `storage_id`) VALUES
(1, 'A', '+', '2025-08-01', '2025-09-12', 'available', 1),
(2, 'O', '-', '2025-08-05', '2025-09-16', 'available', 2),
(3, 'B', '+', '2025-08-10', '2025-09-21', 'available', 3),
(4, 'AB', '+', '2025-08-15', '2025-09-26', 'available', 4),
(5, 'A', '-', '2025-08-20', '2025-10-01', 'reserved', 5),
(6, 'AB', '+', '2025-08-17', '2025-09-28', 'available', 1),
(7, 'AB', '+', '2025-08-19', '2025-09-30', 'available', 1),
(8, 'AB', '+', '2025-08-15', '2025-09-26', 'available', 7),
(9, 'AB', '+', '2025-08-07', '2025-09-18', 'available', 1);

-- --------------------------------------------------------

--
-- Table structure for table `donation`
--

CREATE TABLE `donation` (
  `user_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `blood_unit_id` int(11) NOT NULL,
  `donation_date` date NOT NULL,
  `quantity_ml` int(11) NOT NULL,
  `remarks` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `donation`
--

INSERT INTO `donation` (`user_id`, `event_id`, `blood_unit_id`, `donation_date`, `quantity_ml`, `remarks`) VALUES
(1, 1, 1, '2025-08-01', 450, 'First donation at Dhaka camp'),
(4, 2, 2, '2025-08-05', 450, 'Chattogram drive donation'),
(7, 1, 6, '2025-08-17', 2, ''),
(7, 2, 7, '2025-08-19', 3, ''),
(7, 3, 3, '2025-08-10', 450, 'Healthy donor'),
(7, 3, 9, '2025-08-07', 2, ''),
(9, 4, 4, '2025-08-15', 450, NULL),
(18, 5, 5, '2025-08-20', 450, 'Urgent donation');

-- --------------------------------------------------------

--
-- Table structure for table `donation_appointment`
--

CREATE TABLE `donation_appointment` (
  `appointment_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `scheduled_date` date NOT NULL,
  `location` varchar(100) NOT NULL,
  `appointment_status` enum('scheduled','completed','cancelled') DEFAULT 'scheduled'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `donation_appointment`
--

INSERT INTO `donation_appointment` (`appointment_id`, `user_id`, `scheduled_date`, `location`, `appointment_status`) VALUES
(1, 1, '2025-09-05', 'Dhaka Medical College Hospital', 'scheduled'),
(2, 4, '2025-09-10', 'Chattogram Medical College Hospital', 'scheduled'),
(3, 7, '2025-09-12', 'Sylhet MAG Osmani Medical College', 'completed'),
(4, 9, '2025-09-15', 'Rajshahi Medical College Hospital', 'scheduled'),
(5, 18, '2025-09-20', 'Evercare Hospital Dhaka', 'cancelled'),
(6, 7, '2025-08-18', 'ABC', 'scheduled');

-- --------------------------------------------------------

--
-- Table structure for table `donor`
--

CREATE TABLE `donor` (
  `user_id` int(11) NOT NULL,
  `blood_group` enum('A','B','AB','O') NOT NULL,
  `rh_factor` enum('+','-') NOT NULL,
  `eligibility_status` enum('eligible','ineligible','pending') DEFAULT 'pending',
  `donation_count` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `donor`
--

INSERT INTO `donor` (`user_id`, `blood_group`, `rh_factor`, `eligibility_status`, `donation_count`) VALUES
(1, 'A', '+', 'eligible', 1),
(4, 'A', '+', 'eligible', 1),
(7, 'AB', '+', 'eligible', 4),
(9, 'AB', '+', 'eligible', 1),
(18, 'A', '+', 'eligible', 1),
(20, 'AB', '-', 'pending', 0);

-- --------------------------------------------------------

--
-- Table structure for table `donor_health_record`
--

CREATE TABLE `donor_health_record` (
  `donor_id` int(11) NOT NULL,
  `checkup_date` date NOT NULL,
  `blood_pressure` varchar(20) DEFAULT NULL,
  `hemoglobin_level` decimal(4,1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `donor_health_record`
--

INSERT INTO `donor_health_record` (`donor_id`, `checkup_date`, `blood_pressure`, `hemoglobin_level`) VALUES
(1, '2025-07-25', '120/80', 13.5),
(4, '2025-07-30', '118/76', 14.0),
(7, '2025-08-01', '125/82', 13.8),
(9, '2025-08-05', '130/85', 12.9),
(18, '2025-08-10', '122/78', 13.2);

-- --------------------------------------------------------

--
-- Table structure for table `event_`
--

CREATE TABLE `event_` (
  `event_id` int(11) NOT NULL,
  `event_name` varchar(100) NOT NULL,
  `location` varchar(100) NOT NULL,
  `event_date` date NOT NULL,
  `organizer_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `event_`
--

INSERT INTO `event_` (`event_id`, `event_name`, `location`, `event_date`, `organizer_id`) VALUES
(1, 'Dhaka Blood Drive 2025', 'Dhaka Medical College Hospital, Dhaka', '2025-09-01', 1),
(2, 'Chattogram Donation Camp', 'Chattogram Medical College Hospital, Chattogram', '2025-09-10', 3),
(3, 'Sylhet Community Drive', 'Sylhet MAG Osmani Medical College Hospital, Sylhet', '2025-09-15', 2),
(4, 'Rajshahi Health Fair', 'Rajshahi Medical College Hospital, Rajshahi', '2025-09-20', 4),
(5, 'Quantum Blood Camp', 'Bashundhara Convention Center, Dhaka', '2025-10-01', 5);

-- --------------------------------------------------------

--
-- Table structure for table `hospital`
--

CREATE TABLE `hospital` (
  `hospital_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `city` varchar(50) NOT NULL,
  `street` varchar(100) DEFAULT NULL,
  `postal_code` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hospital`
--

INSERT INTO `hospital` (`hospital_id`, `name`, `city`, `street`, `postal_code`) VALUES
(1, 'Bangabandhu Sheikh Mujib Medical University', 'Dhaka', 'Shahbagh', '1000'),
(2, 'Dhaka Medical College Hospital', 'Dhaka', 'Secretariat Road', '1000'),
(3, 'Square Hospitals Ltd.', 'Dhaka', '18/F Bir Uttam Qazi Nuruzzaman Sarak', '1205'),
(4, 'United Hospital Ltd.', 'Dhaka', 'Plot 15, Road 71, Gulshan', '1212'),
(5, 'Evercare Hospital Dhaka', 'Dhaka', 'Plot 81, Block E, Bashundhara R/A', '1229'),
(6, 'Chattogram Medical College Hospital', 'Chattogram', 'Probartok Circle', '4002'),
(7, 'Imperial Hospital Limited', 'Chattogram', 'Zakir Hossain Road', '4215'),
(8, 'Sylhet MAG Osmani Medical College Hospital', 'Sylhet', 'Medical Road', '3100'),
(9, 'Rajshahi Medical College Hospital', 'Rajshahi', 'Laxmipur', '6000'),
(10, 'Khulna Medical College Hospital', 'Khulna', 'Boyra', '9000');

-- --------------------------------------------------------

--
-- Table structure for table `hospital_phone_no`
--

CREATE TABLE `hospital_phone_no` (
  `hospital_id` int(11) NOT NULL,
  `phone_number` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hospital_phone_no`
--

INSERT INTO `hospital_phone_no` (`hospital_id`, `phone_number`) VALUES
(1, '+880-2-55165600'),
(2, '+880-2-9660015'),
(3, '+880-2-8836000'),
(4, '+880-2-9852466'),
(5, '+880-2-55037242'),
(6, '+880-31-656565'),
(7, '+880-31-627913'),
(8, '+880-821-721151'),
(9, '+880-721-774432'),
(10, '+880-41-760350');

-- --------------------------------------------------------

--
-- Table structure for table `hospital_representative`
--

CREATE TABLE `hospital_representative` (
  `user_id` int(11) NOT NULL,
  `hospital_id` int(11) NOT NULL,
  `department` varchar(50) DEFAULT NULL,
  `designation` varchar(50) DEFAULT NULL,
  `license_id` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hospital_representative`
--

INSERT INTO `hospital_representative` (`user_id`, `hospital_id`, `department`, `designation`, `license_id`) VALUES
(11, 3, 'Cardiology', 'Supervisor', '223'),
(16, 1, 'ab', 'abc', '22');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_manager`
--

CREATE TABLE `inventory_manager` (
  `manager_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_manager`
--

INSERT INTO `inventory_manager` (`manager_id`, `user_id`, `start_date`, `end_date`) VALUES
(1, 11, '2025-08-01', NULL),
(2, 16, '2025-08-05', NULL),
(3, 16, '2025-08-17', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `login_credentials`
--

CREATE TABLE `login_credentials` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `account_status` enum('active','inactive','suspended') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `login_credentials`
--

INSERT INTO `login_credentials` (`user_id`, `username`, `password`, `account_status`) VALUES
(1, 'atiya.tasnim@northsouth.edu', '$2y$10$EjznLqM5eWpK/63kdNa/oObgXmN.VNpIPsQKSvKKM5YB8AZ9uI76W', 'active'),
(4, 'akdjfdhgu@45gmail.vom', '$2y$10$Vh24AZ1MWbouuMKTo.N5OeUUacUPhfQ8mbX0nqAzv2QLC59.hq.2i', 'active'),
(5, 'a.b@gmail.com', '$2y$10$RaGZRZpUtT3XxEBzkpDTy.MyucIz.VjTzoA5l5Do5YRLQbijzgS3y', 'active'),
(6, 'ab.cd@gmail.com', '$2y$10$FMH8wkTcfxpG4438FuTfG.okvXRiYIeh7vbnymRDBk4Fn7Z7aLK6C', 'active'),
(7, 'selina.islam@gmail.com', '$2y$10$8y7yiQQ6rdMXEqK68S6lGOKXqv02xDXbohva8T4pxunqXty8gDSNy', 'active'),
(9, 'anika.shormily@gmail.com', '$2y$10$h..7tsemeRhGIF0KCNJc7Ol7MXUZKzRMElrdAzOILCEPbQRiJJ/Xy', 'active'),
(11, 'abcd@gmail.com', '$2y$10$wheDlbLxY.gvbU.m33VlVO8qojOsz1YVLYC7yPO36oitOQn/.k756', 'active'),
(13, 'atiya.ibnat@gmail.com', '$2y$10$hxLVJ/GyGlA4aB.iTvGZw.3J.Q2ukcCUDXgV9zoOI4QLDekDKMYbK', 'active'),
(14, 'antara.labiba@gmail.com', '$2y$10$gXI.aGzSPe5NACqDuM.N1ulyHtAS7E8vlYuSay1pDiHVKmo7EbIxe', 'active'),
(16, 'antara.tamoha@gmail.com', '$2y$10$FN7tMDyf.iY0t74IPBPchuCb57yx83r2eFfawuZ9hLTCcUEvQY8NG', 'active'),
(17, 'admin@gmail.com', '$2y$10$f0kw7xZM5u95Z37DRZkuR.ex9sfXwAM.QOEx8LuglpeZEiXCWA7Tm', 'active'),
(18, 'atiyaibnattasnim@gmail.com', '$2y$10$rssJrW5m08qyGn2AiN4iB.eUTNSD5bOB6vxsXO041vCNC/vsTVAMO', 'active'),
(19, 'meena.akter@gmail.com', '$2y$10$FI2W3OdkLN43aS/8TgliquN67.EVtgTW38pflj7zujSlvwpD.u.cC', 'active'),
(20, 'raju.islam@gmail.com', '$2y$10$/SzF9jrCnY3tr6AYUdDDoOCGBZBYCMiWWezr4tAwB0dyvwmWIuTNa', 'active'),
(21, 'saiful.islam@gmail.com', '$2y$10$JExdLmSZGE0Q.UPQb9kk0u7VgcEqEZkjvky3GcsG4M.oVlCW9j8ca', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `organizer`
--

CREATE TABLE `organizer` (
  `organizer_id` int(11) NOT NULL,
  `organizer_name` varchar(100) NOT NULL,
  `organizer_type` enum('hospital','ngo','community_group','other') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `organizer`
--

INSERT INTO `organizer` (`organizer_id`, `organizer_name`, `organizer_type`) VALUES
(1, 'Bangladesh Red Crescent Society', 'ngo'),
(2, 'BRAC', 'ngo'),
(3, 'Sandhani', 'community_group'),
(4, 'Dhaka Medical College Hospital', 'hospital'),
(5, 'Quantum Foundation', 'other');

-- --------------------------------------------------------

--
-- Table structure for table `recipient`
--

CREATE TABLE `recipient` (
  `user_id` int(11) NOT NULL,
  `medical_condition` varchar(255) DEFAULT NULL,
  `blood_group` enum('A','B','AB','O') NOT NULL,
  `rh_factor` enum('+','-') NOT NULL,
  `urgency_level` enum('Low','Medium','High') DEFAULT NULL,
  `hospital_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `recipient`
--

INSERT INTO `recipient` (`user_id`, `medical_condition`, `blood_group`, `rh_factor`, `urgency_level`, `hospital_id`) VALUES
(5, '', 'A', '+', 'Medium', 3),
(19, '', 'AB', '+', 'High', 1),
(21, '', 'AB', '+', 'Medium', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `request`
--

CREATE TABLE `request` (
  `request_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `blood_group` enum('A','B','AB','O') NOT NULL,
  `rh_factor` enum('+','-') NOT NULL,
  `quantity_ml` int(11) NOT NULL,
  `request_date` date NOT NULL,
  `status` enum('pending','approved','rejected','fulfilled') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `request`
--

INSERT INTO `request` (`request_id`, `user_id`, `blood_group`, `rh_factor`, `quantity_ml`, `request_date`, `status`) VALUES
(1, 5, 'A', '-', 3, '2025-08-12', 'approved'),
(2, 5, 'AB', '+', 2, '2025-08-12', 'pending'),
(3, 5, 'AB', '+', 2, '2025-08-12', 'pending'),
(4, 5, 'B', '+', 10, '2025-08-12', 'pending'),
(5, 5, 'B', '+', 10, '2025-08-12', 'pending'),
(6, 11, 'AB', '-', 5, '2025-08-12', 'pending'),
(7, 11, 'AB', '-', 5, '2025-08-12', 'pending'),
(8, 5, 'AB', '+', 4, '2025-08-12', 'pending'),
(9, 5, 'AB', '+', 4, '2025-08-12', 'pending'),
(10, 16, 'AB', '+', 4, '2025-08-16', 'pending'),
(11, 16, 'O', '-', 1, '2025-08-16', 'pending'),
(12, 16, 'B', '+', 2, '2025-08-16', 'pending'),
(13, 19, 'AB', '+', 4, '2025-08-16', 'pending'),
(14, 21, 'AB', '+', 3, '2025-08-17', 'pending'),
(15, 21, 'AB', '+', 3, '2025-08-17', 'pending'),
(16, 21, 'AB', '+', 4, '2025-08-17', 'pending');

-- --------------------------------------------------------

--
-- Table structure for table `storage`
--

CREATE TABLE `storage` (
  `storage_id` int(11) NOT NULL,
  `hospital_id` int(11) NOT NULL,
  `location_name` varchar(100) NOT NULL,
  `fridge_number` varchar(20) NOT NULL,
  `shelf_number` varchar(20) DEFAULT NULL,
  `capacity` int(11) NOT NULL,
  `temperature` decimal(4,1) DEFAULT NULL,
  `blood_group` enum('A','B','AB','O') NOT NULL,
  `rh_factor` enum('+','-') NOT NULL,
  `quantity_ml` int(11) DEFAULT 0,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `storage`
--

INSERT INTO `storage` (`storage_id`, `hospital_id`, `location_name`, `fridge_number`, `shelf_number`, `capacity`, `temperature`, `blood_group`, `rh_factor`, `quantity_ml`, `last_updated`) VALUES
(1, 1, 'Main Blood Bank', 'FR01', 'S01', 10000, 4.0, 'A', '+', 450, '2025-08-17 14:22:53'),
(2, 1, 'Emergency Ward', 'FR02', 'S02', 5000, 4.0, 'O', '-', 450, '2025-08-17 14:22:53'),
(3, 2, 'Central Storage', 'FR03', 'S01', 8000, 4.0, 'B', '+', 450, '2025-08-17 14:22:53'),
(4, 3, 'Lab Storage', 'FR04', 'S03', 6000, 4.0, 'AB', '+', 450, '2025-08-17 14:22:53'),
(5, 4, 'Blood Bank A', 'FR05', 'S01', 7000, 4.0, 'A', '-', 450, '2025-08-17 14:22:53'),
(6, 6, 'Chattogram Main', 'FR06', 'S02', 9000, 4.0, 'O', '+', 0, '2025-08-17 14:21:17'),
(7, 1, 'Abd', '3', '2', 2, 4.0, 'B', '+', 450, '2025-08-17 14:38:01'),
(8, 1, 'Abd', '3', '2', 2, 4.0, 'B', '+', 0, '2025-08-17 14:38:23');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `user_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `city` varchar(50) DEFAULT NULL,
  `street` varchar(100) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`user_id`, `first_name`, `last_name`, `email`, `city`, `street`, `postal_code`, `date_of_birth`) VALUES
(1, 'Atiya Ibnat', 'Tasnim', 'atiya.tasnim@northsouth.edu', NULL, NULL, NULL, NULL),
(4, 'Atiya Ibnat', 'Tasneem', 'akdjfdhgu@45gmail.vom', 'Dhaka', '5th', '12112', '2002-03-02'),
(5, 'A', 'B', 'a.b@gmail.com', 'Dhaka', '123', '1209', '2002-04-12'),
(6, 'AB', 'CD', 'ab.cd@gmail.com', 'Dhaka', '123', '1209', '2002-04-12'),
(7, 'Selina', 'Islam', 'selina.islam@gmail.com', 'Dhaka', '111', '1207', '1967-01-01'),
(9, 'Anika', 'Shormily', 'anika.shormily@gmail.com', 'Dhaka', '113', '1207', '1997-05-02'),
(11, 'abcd', 'abcd', 'abcd@gmail.com', '', '', '', NULL),
(13, 'Atiya', 'Ibnat', 'atiya.ibnat@gmail.com', 'Dhaka', '143', '1207', '2002-04-03'),
(14, 'Antara', 'Labiba', 'antara.labiba@gmail.com', '', '', '', NULL),
(16, 'Antara', 'Tamoha', 'antara.tamoha@gmail.com', 'Dhaka', '1st', '1216', '2025-07-27'),
(17, 'Admin', 'User', 'admin@gmail.com', 'Admin City', '123 Admin St', '12345', '1980-01-01'),
(18, 'Atiya Ibnat', 'Tasnim', 'atiyaibnattasnim@gmail.com', '', '', '', '0000-00-00'),
(19, 'Meena', 'Akter', 'meena.akter@gmail.com', 'Chattogram', 'Probartok Circle', '1217', '2025-08-07'),
(20, 'Raju', 'Islam', 'raju.islam@gmail.com', 'Dhaka', 'abc', '1234', '2025-08-04'),
(21, 'Saiful', 'Islam', 'saiful.islam@gmail.com', 'Dhaka', '6th', '1217', '2025-07-29');

-- --------------------------------------------------------

--
-- Table structure for table `user_phone_no`
--

CREATE TABLE `user_phone_no` (
  `user_id` int(11) NOT NULL,
  `phone_number` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_phone_no`
--

INSERT INTO `user_phone_no` (`user_id`, `phone_number`) VALUES
(16, '01611396659');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `blood_unit`
--
ALTER TABLE `blood_unit`
  ADD PRIMARY KEY (`blood_unit_id`),
  ADD KEY `storage_id` (`storage_id`);

--
-- Indexes for table `donation`
--
ALTER TABLE `donation`
  ADD PRIMARY KEY (`user_id`,`event_id`,`blood_unit_id`),
  ADD KEY `event_id` (`event_id`),
  ADD KEY `blood_unit_id` (`blood_unit_id`);

--
-- Indexes for table `donation_appointment`
--
ALTER TABLE `donation_appointment`
  ADD PRIMARY KEY (`appointment_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `donor`
--
ALTER TABLE `donor`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `donor_health_record`
--
ALTER TABLE `donor_health_record`
  ADD PRIMARY KEY (`donor_id`,`checkup_date`);

--
-- Indexes for table `event_`
--
ALTER TABLE `event_`
  ADD PRIMARY KEY (`event_id`),
  ADD KEY `organizer_id` (`organizer_id`);

--
-- Indexes for table `hospital`
--
ALTER TABLE `hospital`
  ADD PRIMARY KEY (`hospital_id`);

--
-- Indexes for table `hospital_phone_no`
--
ALTER TABLE `hospital_phone_no`
  ADD PRIMARY KEY (`hospital_id`,`phone_number`);

--
-- Indexes for table `hospital_representative`
--
ALTER TABLE `hospital_representative`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `license_id` (`license_id`),
  ADD KEY `hospital_id` (`hospital_id`);

--
-- Indexes for table `inventory_manager`
--
ALTER TABLE `inventory_manager`
  ADD PRIMARY KEY (`manager_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `login_credentials`
--
ALTER TABLE `login_credentials`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `organizer`
--
ALTER TABLE `organizer`
  ADD PRIMARY KEY (`organizer_id`);

--
-- Indexes for table `recipient`
--
ALTER TABLE `recipient`
  ADD PRIMARY KEY (`user_id`),
  ADD KEY `fk_recipient_hospital` (`hospital_id`);

--
-- Indexes for table `request`
--
ALTER TABLE `request`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `storage`
--
ALTER TABLE `storage`
  ADD PRIMARY KEY (`storage_id`),
  ADD KEY `hospital_id` (`hospital_id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_phone_no`
--
ALTER TABLE `user_phone_no`
  ADD PRIMARY KEY (`user_id`,`phone_number`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `blood_unit`
--
ALTER TABLE `blood_unit`
  MODIFY `blood_unit_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `donation_appointment`
--
ALTER TABLE `donation_appointment`
  MODIFY `appointment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `event_`
--
ALTER TABLE `event_`
  MODIFY `event_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `hospital`
--
ALTER TABLE `hospital`
  MODIFY `hospital_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `inventory_manager`
--
ALTER TABLE `inventory_manager`
  MODIFY `manager_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `organizer`
--
ALTER TABLE `organizer`
  MODIFY `organizer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `request`
--
ALTER TABLE `request`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `storage`
--
ALTER TABLE `storage`
  MODIFY `storage_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin`
--
ALTER TABLE `admin`
  ADD CONSTRAINT `admin_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `blood_unit`
--
ALTER TABLE `blood_unit`
  ADD CONSTRAINT `blood_unit_ibfk_1` FOREIGN KEY (`storage_id`) REFERENCES `storage` (`storage_id`) ON DELETE CASCADE;

--
-- Constraints for table `donation`
--
ALTER TABLE `donation`
  ADD CONSTRAINT `donation_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `donation_ibfk_2` FOREIGN KEY (`event_id`) REFERENCES `event_` (`event_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `donation_ibfk_3` FOREIGN KEY (`blood_unit_id`) REFERENCES `blood_unit` (`blood_unit_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `donation_appointment`
--
ALTER TABLE `donation_appointment`
  ADD CONSTRAINT `donation_appointment_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `donor` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `donor`
--
ALTER TABLE `donor`
  ADD CONSTRAINT `donor_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `donor_health_record`
--
ALTER TABLE `donor_health_record`
  ADD CONSTRAINT `donor_health_record_ibfk_1` FOREIGN KEY (`donor_id`) REFERENCES `donor` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `event_`
--
ALTER TABLE `event_`
  ADD CONSTRAINT `event__ibfk_1` FOREIGN KEY (`organizer_id`) REFERENCES `organizer` (`organizer_id`) ON DELETE CASCADE;

--
-- Constraints for table `hospital_phone_no`
--
ALTER TABLE `hospital_phone_no`
  ADD CONSTRAINT `hospital_phone_no_ibfk_1` FOREIGN KEY (`hospital_id`) REFERENCES `hospital` (`hospital_id`) ON DELETE CASCADE;

--
-- Constraints for table `hospital_representative`
--
ALTER TABLE `hospital_representative`
  ADD CONSTRAINT `hospital_representative_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `hospital_representative_ibfk_2` FOREIGN KEY (`hospital_id`) REFERENCES `hospital` (`hospital_id`) ON DELETE CASCADE;

--
-- Constraints for table `inventory_manager`
--
ALTER TABLE `inventory_manager`
  ADD CONSTRAINT `inventory_manager_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `hospital_representative` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `login_credentials`
--
ALTER TABLE `login_credentials`
  ADD CONSTRAINT `login_credentials_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `recipient`
--
ALTER TABLE `recipient`
  ADD CONSTRAINT `fk_recipient_hospital` FOREIGN KEY (`hospital_id`) REFERENCES `hospital` (`hospital_id`),
  ADD CONSTRAINT `recipient_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `request`
--
ALTER TABLE `request`
  ADD CONSTRAINT `request_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `storage`
--
ALTER TABLE `storage`
  ADD CONSTRAINT `storage_ibfk_1` FOREIGN KEY (`hospital_id`) REFERENCES `hospital` (`hospital_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_phone_no`
--
ALTER TABLE `user_phone_no`
  ADD CONSTRAINT `user_phone_no_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
