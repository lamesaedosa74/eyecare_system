-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Mar 27, 2025 at 08:59 PM
-- Server version: 8.4.3
-- PHP Version: 8.3.16

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `eyecare`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `admin_id` int NOT NULL,
  `user_id` int NOT NULL,
  `first_name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `last_name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `sex` enum('Male','Female','Other') COLLATE utf8mb4_general_ci NOT NULL,
  `age` int DEFAULT NULL,
  `phone_number` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_general_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`admin_id`, `user_id`, `first_name`, `last_name`, `sex`, `age`, `phone_number`, `address`) VALUES
(1, 1, 'lamesa', 'edosa', 'Male', 23, '0917093815', 'nole'),
(2, 18, 'akkaka', 'llKKKK', 'Male', 24, '091797736', 'KAHSJSH');

-- --------------------------------------------------------

--
-- Table structure for table `all_users`
--

CREATE TABLE `all_users` (
  `user_id` int DEFAULT NULL,
  `role` varchar(50) DEFAULT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `sex` enum('Male','Female','Other') DEFAULT NULL,
  `age` int DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `address` text,
  `email` varchar(100) DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `data_clerk`
--

CREATE TABLE `data_clerk` (
  `clerk_id` int NOT NULL,
  `user_id` int NOT NULL,
  `first_name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `last_name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `sex` enum('Male','Female','Other') COLLATE utf8mb4_general_ci NOT NULL,
  `age` int DEFAULT NULL,
  `phone_number` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_general_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `data_clerk`
--

INSERT INTO `data_clerk` (`clerk_id`, `user_id`, `first_name`, `last_name`, `sex`, `age`, `phone_number`, `address`) VALUES
(1, 3, 'la', 'w', 'Male', 34, '091709562627', 'nole'),
(2, 12, 'data', 'q', 'Male', 23, '09167873', 'badiya'),
(3, 17, 'kefalo', 'fishia', 'Male', 24, '0916789903', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `drug_inventory`
--

CREATE TABLE `drug_inventory` (
  `drug_id` int NOT NULL,
  `drug_name` varchar(255) NOT NULL,
  `generic_name` varchar(255) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `dosage` varchar(100) NOT NULL,
  `form` varchar(50) DEFAULT NULL COMMENT 'Tablet, Capsule, Drops, etc.',
  `quantity` int NOT NULL DEFAULT '0',
  `unit` varchar(20) DEFAULT NULL COMMENT 'mg, ml, etc.',
  `expiry_date` date DEFAULT NULL,
  `supplier` varchar(255) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `drug_inventory`
--

INSERT INTO `drug_inventory` (`drug_id`, `drug_name`, `generic_name`, `category`, `dosage`, `form`, `quantity`, `unit`, `expiry_date`, `supplier`, `price`, `created_at`, `updated_at`) VALUES
(1, 'Latanoprost', 'Latanoprost', 'Glaucoma', '0.005%', 'Eye Drops', 50, 'ml', '2026-12-02', NULL, NULL, '2025-03-25 19:07:10', '2025-03-26 11:20:38'),
(2, 'Timolol Maleate', 'Timolol', 'Glaucoma', '0.5%', 'Eye Drops', 35, 'ml', '2026-10-07', NULL, NULL, '2025-03-25 19:07:10', '2025-03-26 11:21:27'),
(3, 'Brimonidine', 'Brimonidine Tartrate', 'Glaucoma', '0.2%', 'Eye Drops', 42, 'ml', '2025-03-30', NULL, NULL, '2025-03-25 19:07:10', '2025-03-25 19:07:10'),
(4, 'Dorzolamide', 'Dorzolamide HCl', 'Glaucoma', '2%', 'Eye Drops', 27, 'ml', '2026-11-18', NULL, NULL, '2025-03-25 19:07:10', '2025-03-26 20:23:58'),
(5, 'Cyclopentolate', 'Cyclopentolate HCl', 'Mydriatic', '1%', 'Eye Drops', 57, 'ml', '2025-05-15', NULL, NULL, '2025-03-25 19:07:10', '2025-03-27 08:37:23'),
(6, 'Tropicamide', 'Tropicamide', 'Mydriatic', '0.5%', 'Eye Drops', 55, 'ml', '2025-02-28', NULL, NULL, '2025-03-25 19:07:10', '2025-03-25 19:07:10'),
(7, 'Artificial Tears', 'Carboxymethylcellulose', 'Lubricant', '0.5%', 'Eye Drops', 92, 'ml', '2025-12-31', NULL, NULL, '2025-03-25 19:07:10', '2025-03-26 19:51:25'),
(8, 'Fluorescein', 'Fluorescein Sodium', 'Diagnostic', '1%', 'Eye Drops', 29, 'ml', '2026-09-29', NULL, NULL, '2025-03-25 19:07:10', '2025-03-27 19:32:32');

-- --------------------------------------------------------

--
-- Table structure for table `eye_conditions`
--

CREATE TABLE `eye_conditions` (
  `condition_id` int NOT NULL,
  `condition_name` varchar(100) NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `description` text,
  `treatment_guidelines` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `eye_conditions`
--

INSERT INTO `eye_conditions` (`condition_id`, `condition_name`, `category`, `description`, `treatment_guidelines`) VALUES
(1, 'Cataracts', 'Lens', 'Clouding of the eye\'s natural lens', 'Surgical removal with IOL implantation'),
(2, 'Glaucoma', 'Optic Nerve', 'Increased intraocular pressure damaging optic nerve', 'Medication, laser treatment, or surgery'),
(3, 'AMD (Age-related Macular Degeneration)', 'Retina', 'Deterioration of the macula', 'Anti-VEGF injections, laser therapy'),
(4, 'Diabetic Retinopathy', 'Retina', 'Diabetes-induced retinal damage', 'Laser treatment, vitrectomy, medication'),
(5, 'Dry Eye Syndrome', 'Surface', 'Insufficient tear production', 'Artificial tears, punctal plugs, medications'),
(6, 'Conjunctivitis', 'Surface', 'Inflammation of the conjunctiva', 'Antibiotics, antihistamines, or anti-inflammatory drops'),
(7, 'Refractive Errors', 'General', 'Myopia, hyperopia, astigmatism, presbyopia', 'Corrective lenses, refractive surgery');

-- --------------------------------------------------------

--
-- Table structure for table `eye_examinations`
--

CREATE TABLE `eye_examinations` (
  `exam_id` int NOT NULL,
  `patient_id` int NOT NULL,
  `nurse_id` int NOT NULL,
  `visual_acuity_left` varchar(10) NOT NULL,
  `visual_acuity_right` varchar(10) NOT NULL,
  `intraocular_pressure_left` decimal(5,1) NOT NULL,
  `intraocular_pressure_right` decimal(5,1) NOT NULL,
  `notes` text,
  `exam_date` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `eye_examinations`
--

INSERT INTO `eye_examinations` (`exam_id`, `patient_id`, `nurse_id`, `visual_acuity_left`, `visual_acuity_right`, `intraocular_pressure_left`, `intraocular_pressure_right`, `notes`, `exam_date`) VALUES
(1, 1, 1, '20/20', '20/20', 20.0, 45.0, 'hhhhhh', '2025-03-26 10:43:06'),
(2, 1, 1, '20/20', '20/20', 20.0, 45.0, 'hhhhhh', '2025-03-26 10:45:27'),
(3, 1, 1, '20/20', '20/20', 20.0, 45.0, 'hhhhhh', '2025-03-26 10:46:15'),
(4, 1, 1, '20/20', '20/20', 20.0, 45.0, 'hhhhhh', '2025-03-26 10:47:44'),
(5, 1, 1, '20/20', '20/20', 20.0, 45.0, 'hhhhhh', '2025-03-26 10:55:34');

-- --------------------------------------------------------

--
-- Table structure for table `lab_requests`
--

CREATE TABLE `lab_requests` (
  `request_id` int NOT NULL,
  `request_no` varchar(20) NOT NULL,
  `test_id` int NOT NULL,
  `request_date` date NOT NULL,
  `completion_date` date DEFAULT NULL,
  `urgency` enum('routine','urgent','stat') DEFAULT 'routine',
  `clinical_notes` text,
  `results` text,
  `status` enum('pending','completed','cancelled') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `patient_id` int NOT NULL,
  `ophthalmologist_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `lab_requests`
--

INSERT INTO `lab_requests` (`request_id`, `request_no`, `test_id`, `request_date`, `completion_date`, `urgency`, `clinical_notes`, `results`, `status`, `created_at`, `patient_id`, `ophthalmologist_id`) VALUES
(1, 'LAB-20250325-4AB1FF', 9, '2025-03-25', NULL, 'urgent', 'kjjahhah', NULL, 'pending', '2025-03-25 19:34:44', 1, 1),
(2, 'LAB-20250326-2A102C', 4, '2025-03-26', NULL, 'urgent', 'mmmmmm', NULL, 'pending', '2025-03-26 06:27:30', 1, 1),
(3, 'LAB-20250327-8E261A', 4, '2025-03-27', NULL, 'urgent', 'LAJJHHJSHHSBBBBSVVSCV', NULL, 'pending', '2025-03-27 19:33:44', 2, 1);

-- --------------------------------------------------------

--
-- Table structure for table `lab_tests`
--

CREATE TABLE `lab_tests` (
  `test_id` int NOT NULL,
  `test_name` varchar(100) NOT NULL,
  `description` text,
  `category` varchar(50) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `lab_tests`
--

INSERT INTO `lab_tests` (`test_id`, `test_name`, `description`, `category`, `is_active`, `created_at`) VALUES
(1, 'Visual Acuity Test', 'Measurement of the ability of the eye to distinguish shapes and details', 'Basic', 1, '2025-03-25 19:31:06'),
(2, 'Tonometry', 'Measurement of intraocular pressure', 'Glaucoma', 1, '2025-03-25 19:31:06'),
(3, 'Slit Lamp Exam', 'Microscopic examination of the eye structures', 'Comprehensive', 1, '2025-03-25 19:31:06'),
(4, 'Retinal Imaging', 'Digital imaging of the retina', 'Diagnostic', 1, '2025-03-25 19:31:06'),
(5, 'Optical Coherence Tomography', 'OCT scan of retinal layers', 'Advanced', 1, '2025-03-25 19:31:06'),
(6, 'Visual Field Test', 'Measurement of peripheral vision', 'Glaucoma', 1, '2025-03-25 19:31:06'),
(7, 'Corneal Topography', 'Mapping the surface curvature of the cornea', 'Refractive', 1, '2025-03-25 19:31:06'),
(8, 'Fluorescein Angiography', 'Imaging of retinal blood vessels', 'Diagnostic', 1, '2025-03-25 19:31:06'),
(9, 'A-Scan Ultrasound', 'Measurement of eye length for IOL calculation', 'Pre-surgical', 1, '2025-03-25 19:31:06');

-- --------------------------------------------------------

--
-- Table structure for table `medical_staff`
--

CREATE TABLE `medical_staff` (
  `staff_id` int NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `role` varchar(50) NOT NULL,
  `specialization` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `medical_staff`
--

INSERT INTO `medical_staff` (`staff_id`, `first_name`, `last_name`, `role`, `specialization`) VALUES
(1, 'jems', 'Ahmed', 'anesthesiologist', 'Ophthalmic Anesthesia'),
(2, 'kali', 'mohamed', 'anesthesiologist', 'General Anesthesia'),
(3, 'Michael', 'lami', 'anesthesiologist', 'Pediatric Anesthesia');

-- --------------------------------------------------------

--
-- Table structure for table `operating_rooms`
--

CREATE TABLE `operating_rooms` (
  `room_id` int NOT NULL,
  `room_name` varchar(50) NOT NULL,
  `equipment` text,
  `is_active` tinyint(1) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `operating_rooms`
--

INSERT INTO `operating_rooms` (`room_id`, `room_name`, `equipment`, `is_active`) VALUES
(1, 'OR-1', 'Phaco machine, Microscope, Laser', 1),
(2, 'OR-2', 'Vitrectomy machine, Cryo unit', 1),
(3, 'OR-3', 'LASIK laser, Topography system', 1),
(4, 'OR-4', 'General ophthalmic equipment', 1);

-- --------------------------------------------------------

--
-- Table structure for table `ophthalmic_nurse`
--

CREATE TABLE `ophthalmic_nurse` (
  `nurse_id` int NOT NULL,
  `user_id` int NOT NULL,
  `first_name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `last_name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `sex` enum('Male','Female','Other') COLLATE utf8mb4_general_ci NOT NULL,
  `age` int DEFAULT NULL,
  `phone_number` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_general_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ophthalmic_nurse`
--

INSERT INTO `ophthalmic_nurse` (`nurse_id`, `user_id`, `first_name`, `last_name`, `sex`, `age`, `phone_number`, `address`) VALUES
(1, 15, 'nurse', 'vb', 'Male', 34, '091769876', 'ff');

-- --------------------------------------------------------

--
-- Table structure for table `ophthalmologist`
--

CREATE TABLE `ophthalmologist` (
  `ophthalmologist_id` int NOT NULL,
  `user_id` int NOT NULL,
  `first_name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `last_name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `sex` enum('Male','Female','Other') COLLATE utf8mb4_general_ci NOT NULL,
  `age` int DEFAULT NULL,
  `phone_number` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_general_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ophthalmologist`
--

INSERT INTO `ophthalmologist` (`ophthalmologist_id`, `user_id`, `first_name`, `last_name`, `sex`, `age`, `phone_number`, `address`) VALUES
(1, 14, 'doctor', 'x', 'Male', 23, '0916456767', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `optometrist`
--

CREATE TABLE `optometrist` (
  `optometrist_id` int NOT NULL,
  `user_id` int NOT NULL,
  `first_name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `last_name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `sex` enum('Male','Female','Other') COLLATE utf8mb4_general_ci NOT NULL,
  `age` int DEFAULT NULL,
  `phone_number` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_general_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `optometrist`
--

INSERT INTO `optometrist` (`optometrist_id`, `user_id`, `first_name`, `last_name`, `sex`, `age`, `phone_number`, `address`) VALUES
(1, 16, 'opt', 'metrist', 'Male', 34, '0914366778', 'wee');

-- --------------------------------------------------------

--
-- Table structure for table `patients`
--

CREATE TABLE `patients` (
  `patient_id` int NOT NULL,
  `mrn` varchar(50) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `date_of_birth` date NOT NULL,
  `age` int DEFAULT NULL,
  `gender` enum('Male','Female','Other') NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `zone` varchar(100) NOT NULL,
  `woreda` varchar(100) NOT NULL,
  `kebele` varchar(100) NOT NULL,
  `medical_history` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `patients`
--

INSERT INTO `patients` (`patient_id`, `mrn`, `first_name`, `last_name`, `date_of_birth`, `age`, `gender`, `phone_number`, `email`, `zone`, `woreda`, `kebele`, `medical_history`, `created_at`) VALUES
(1, 'MRN-20250325-G48DIP', 'mifta', 'ibraim', '2025-03-04', 0, 'Male', '0928776635', 'mifta@gmail.com', 'ararage', 'dd', '08', NULL, '2025-03-25 08:45:56'),
(2, 'MRN-20250326-07DCPA', 'jemal', 'denge', '2025-03-04', 0, 'Male', '0912653467', 'jemal@gmail.cm', 'aa', 'kaliti', '09', 'jhhuwyywwg', '2025-03-26 20:27:36'),
(3, 'MRN-20250327-JLNXW6', 'krubel', 'mengisu', '1994-07-24', 30, 'Male', '0988776655', 'kiraz@gmail.com', 'keffa', 'bonga', '03', 'rtjdjdnnf', '2025-03-27 08:35:37'),
(4, 'MRN-20250327-1DWGSE', 'gabbavv', 'jjahhzbb', '2025-03-03', 0, 'Male', '0972655336', 'gaakkj@gmail.com', 'hararge', 'badesa', '07', NULL, '2025-03-27 19:42:35');

--
-- Triggers `patients`
--
DELIMITER $$
CREATE TRIGGER `update_age_before_insert` BEFORE INSERT ON `patients` FOR EACH ROW BEGIN
   SET NEW.age = TIMESTAMPDIFF(YEAR, NEW.date_of_birth, CURDATE());
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `update_age_before_update` BEFORE UPDATE ON `patients` FOR EACH ROW BEGIN
   SET NEW.age = TIMESTAMPDIFF(YEAR, NEW.date_of_birth, CURDATE());
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `patient_diagnoses`
--

CREATE TABLE `patient_diagnoses` (
  `diagnosis_id` int NOT NULL,
  `patient_id` int NOT NULL,
  `ophthalmologist_id` int NOT NULL,
  `condition_id` int NOT NULL,
  `diagnosis_date` date NOT NULL,
  `eye_affected` enum('left','right','both') NOT NULL,
  `severity` enum('mild','moderate','severe') DEFAULT NULL,
  `findings` text,
  `treatment_plan` text,
  `follow_up_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `patient_diagnoses`
--

INSERT INTO `patient_diagnoses` (`diagnosis_id`, `patient_id`, `ophthalmologist_id`, `condition_id`, `diagnosis_date`, `eye_affected`, `severity`, `findings`, `treatment_plan`, `follow_up_date`, `created_at`) VALUES
(1, 1, 1, 6, '2025-03-25', 'right', 'mild', 'kjjahahasygqkqK', 'KKjjqq', '2025-03-26', '2025-03-25 20:15:09'),
(2, 1, 1, 3, '2025-03-25', 'right', 'mild', 'kjjahahasygqkqK', 'KKjjqq', '2025-03-29', '2025-03-25 20:16:39'),
(3, 1, 1, 3, '2025-03-27', 'both', 'moderate', 'DDDDDDXZAAASdasX', 'ssasscdcv', '2025-03-29', '2025-03-27 19:38:07');

-- --------------------------------------------------------

--
-- Table structure for table `prescriptions`
--

CREATE TABLE `prescriptions` (
  `prescription_id` int NOT NULL,
  `pr_no_id` varchar(50) NOT NULL,
  `date_prescription` date NOT NULL,
  `prescribed_by` varchar(100) NOT NULL,
  `drug_name` varchar(255) NOT NULL,
  `nurse_id` int DEFAULT NULL,
  `ophthalmologist_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `patient_id` int NOT NULL,
  `dosage` varchar(50) NOT NULL,
  `frequency` varchar(50) NOT NULL,
  `duration` varchar(50) NOT NULL,
  `instructions` text,
  `optometrist_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `prescriptions`
--

INSERT INTO `prescriptions` (`prescription_id`, `pr_no_id`, `date_prescription`, `prescribed_by`, `drug_name`, `nurse_id`, `ophthalmologist_id`, `created_at`, `patient_id`, `dosage`, `frequency`, `duration`, `instructions`, `optometrist_id`) VALUES
(2, 'PR-20250325-4EDDB8', '2025-03-25', 'doctor x', 'Artificial Tears', 1, 1, '2025-03-25 20:51:00', 1, '0.5%', 'Once daily', '2', 'kjjhhh', NULL),
(4, 'PR-20250326-073FEA', '2025-03-26', 'doctor x', 'Cyclopentolate', 1, 1, '2025-03-26 06:25:52', 1, '1%', 'Twice daily', '2', '', NULL),
(5, 'PR-20250326-617050', '2025-03-26', 'opt metrist', 'Artificial Tears', 1, NULL, '2025-03-26 19:15:50', 1, '0.5%', 'Every 2 hours', '3', 'sss', NULL),
(6, 'PR-20250326-6338B2', '2025-03-26', 'opt metrist', 'Artificial Tears', 1, NULL, '2025-03-26 19:42:46', 1, '0.5%', 'Once daily', '7', 'ss', 1),
(7, 'PR-20250326-168818', '2025-03-26', 'opt metrist', 'Artificial Tears', 1, NULL, '2025-03-26 19:44:01', 1, '0.5%', 'Twice daily', '7days', 'kJJAHAGAGAGABABAA', 1),
(8, 'PR-20250326-38994B', '2025-03-26', 'opt metrist', 'Artificial Tears', 1, NULL, '2025-03-26 19:50:59', 1, '0.5%', 'Twice daily', '7days', 'kJJAHAGAGAGABABAA', 1),
(9, 'PR-20250326-DB8AB6', '2025-03-26', 'opt metrist', 'Artificial Tears', 1, NULL, '2025-03-26 19:51:25', 1, '0.5%', 'Once daily', '5', 'FFF', 1),
(11, 'PR-20250327-B04131', '2025-03-27', 'doctor x', 'Cyclopentolate', 1, 1, '2025-03-27 08:25:47', 2, '1%', 'Once daily', '3week', '', NULL),
(12, 'PR-20250327-30EFD3', '2025-03-27', 'doctor x', 'Cyclopentolate', 1, 1, '2025-03-27 08:37:23', 3, '1%', 'Twice daily', '66', 'ffffffff', NULL),
(13, 'PR-20250327-08A85B', '2025-03-27', 'doctor x', 'Fluorescein', 1, 1, '2025-03-27 19:32:32', 1, '1%', 'Twice daily', '7days', '', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `referrals`
--

CREATE TABLE `referrals` (
  `referral_id` int NOT NULL,
  `patient_id` int NOT NULL,
  `optometrist_id` int NOT NULL,
  `ophthalmologist_id` int NOT NULL,
  `referral_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `reason` varchar(255) NOT NULL,
  `notes` text,
  `status` enum('Pending','Accepted','Completed','Cancelled') DEFAULT 'Pending',
  `appointment_date` datetime DEFAULT NULL,
  `follow_up_notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `referrals`
--

INSERT INTO `referrals` (`referral_id`, `patient_id`, `optometrist_id`, `ophthalmologist_id`, `referral_date`, `reason`, `notes`, `status`, `appointment_date`, `follow_up_notes`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, '2025-03-26 23:50:06', 'Surgical Consultation', 'nnn', 'Accepted', NULL, NULL, '2025-03-26 20:50:06', '2025-03-26 21:10:57'),
(2, 2, 1, 1, '2025-03-27 00:13:23', 'Surgical Consultation', 'zz', 'Pending', NULL, NULL, '2025-03-26 21:13:23', '2025-03-26 21:13:23'),
(3, 1, 1, 1, '2025-03-27 22:40:24', 'Surgical Consultation', 'kajjhshsgssg', 'Pending', NULL, NULL, '2025-03-27 19:40:24', '2025-03-27 19:40:24');

-- --------------------------------------------------------

--
-- Table structure for table `referral_documents`
--

CREATE TABLE `referral_documents` (
  `document_id` int NOT NULL,
  `referral_id` int NOT NULL,
  `document_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_type` varchar(50) NOT NULL,
  `file_size` int NOT NULL,
  `uploaded_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `role_id` int NOT NULL,
  `role_name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`role_id`, `role_name`, `description`) VALUES
(1, 'admin', 'System administrator with full access'),
(2, 'data_clerk', 'Responsible for data entry'),
(3, 'ophthalmologist', 'Medical doctor specializing in eye care'),
(4, 'ophthalmic_nurse', 'Nurse specialized in eye care'),
(5, 'optometrist', 'Eye care professional for vision tests and corrections');

-- --------------------------------------------------------

--
-- Table structure for table `surgeries`
--

CREATE TABLE `surgeries` (
  `surgery_id` int NOT NULL,
  `case_number` varchar(20) NOT NULL,
  `patient_id` int NOT NULL,
  `ophthalmologist_id` int NOT NULL,
  `surgery_type_id` int NOT NULL,
  `room_id` int NOT NULL,
  `anesthesiologist_id` int DEFAULT NULL,
  `nurse_id` int DEFAULT NULL,
  `scheduled_datetime` datetime NOT NULL,
  `actual_start` datetime DEFAULT NULL,
  `actual_end` datetime DEFAULT NULL,
  `notes` text,
  `status` enum('scheduled','in_progress','completed','cancelled') DEFAULT 'scheduled',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `surgeries`
--

INSERT INTO `surgeries` (`surgery_id`, `case_number`, `patient_id`, `ophthalmologist_id`, `surgery_type_id`, `room_id`, `anesthesiologist_id`, `nurse_id`, `scheduled_datetime`, `actual_start`, `actual_end`, `notes`, `status`, `created_at`) VALUES
(1, 'SUR-20250326-4953A0', 1, 1, 1, 1, 3, 1, '2025-03-29 09:00:00', '2025-03-26 10:01:08', NULL, 'hhhHHhJH', 'in_progress', '2025-03-26 06:36:04'),
(2, 'SUR-20250326-A4C05A', 1, 1, 1, 1, 3, 1, '2025-03-26 08:00:00', '2025-03-26 23:31:46', NULL, 'aaa', 'in_progress', '2025-03-26 07:00:10'),
(3, 'SUR-20250327-423B4D', 1, 1, 2, 3, 3, 1, '2025-03-28 08:00:00', NULL, NULL, 'LLAKJSBHNSBNJXSHHBJNMKL;LUVGB NMBHVM', 'scheduled', '2025-03-27 19:35:48');

-- --------------------------------------------------------

--
-- Table structure for table `surgery_types`
--

CREATE TABLE `surgery_types` (
  `surgery_type_id` int NOT NULL,
  `type_name` varchar(100) NOT NULL,
  `description` text,
  `avg_duration` int DEFAULT NULL COMMENT 'in minutes',
  `is_active` tinyint(1) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `surgery_types`
--

INSERT INTO `surgery_types` (`surgery_type_id`, `type_name`, `description`, `avg_duration`, `is_active`) VALUES
(1, 'Cataract Extraction', 'Removal of cloudy lens and replacement with IOL', 30, 1),
(2, 'Phacoemulsification', 'Cataract removal using ultrasound', 20, 1),
(3, 'Trabeculectomy', 'Glaucoma drainage surgery', 45, 1),
(4, 'Vitrectomy', 'Removal of vitreous gel from eye', 90, 1),
(5, 'LASIK', 'Laser vision correction', 15, 1),
(6, 'PRK', 'Photorefractive keratectomy', 20, 1),
(7, 'Corneal Transplant', 'Replacement of damaged cornea', 120, 1);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int NOT NULL,
  `role_id` int NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `role_id`, `username`, `email`, `password`, `created_at`) VALUES
(1, 1, 'lami', 'lami@gmail.com', '123456', '2025-03-24 20:31:42'),
(3, 2, 'la', 'nn@gmail.com', '1122', '2025-03-25 07:11:46'),
(12, 2, 'data12', 'data12@gmail.com', '1122', '2025-03-25 08:06:09'),
(14, 3, 'doctor', 'doctor@gmail.com', '1122', '2025-03-25 18:11:10'),
(15, 4, 'nurse', 'nurse@gmail.com', '1122', '2025-03-25 20:47:44'),
(16, 5, 'opto', 'optometrist@gmail.com', '1122', '2025-03-26 11:43:44'),
(17, 2, 'kefalo', 'kefalo@gmail.com', '123456', '2025-03-27 14:40:57'),
(18, 1, 'MIFTA', 'KKKK@GMAIL.COM', '123456', '2025-03-27 19:27:56');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `data_clerk`
--
ALTER TABLE `data_clerk`
  ADD PRIMARY KEY (`clerk_id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `drug_inventory`
--
ALTER TABLE `drug_inventory`
  ADD PRIMARY KEY (`drug_id`),
  ADD UNIQUE KEY `drug_name` (`drug_name`);

--
-- Indexes for table `eye_conditions`
--
ALTER TABLE `eye_conditions`
  ADD PRIMARY KEY (`condition_id`);

--
-- Indexes for table `eye_examinations`
--
ALTER TABLE `eye_examinations`
  ADD PRIMARY KEY (`exam_id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `nurse_id` (`nurse_id`);

--
-- Indexes for table `lab_requests`
--
ALTER TABLE `lab_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD UNIQUE KEY `request_no` (`request_no`),
  ADD KEY `fk_lab_request_patient` (`patient_id`),
  ADD KEY `fk_lab_request_ophthalmologist` (`ophthalmologist_id`);

--
-- Indexes for table `lab_tests`
--
ALTER TABLE `lab_tests`
  ADD PRIMARY KEY (`test_id`);

--
-- Indexes for table `medical_staff`
--
ALTER TABLE `medical_staff`
  ADD PRIMARY KEY (`staff_id`);

--
-- Indexes for table `operating_rooms`
--
ALTER TABLE `operating_rooms`
  ADD PRIMARY KEY (`room_id`);

--
-- Indexes for table `ophthalmic_nurse`
--
ALTER TABLE `ophthalmic_nurse`
  ADD PRIMARY KEY (`nurse_id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `ophthalmologist`
--
ALTER TABLE `ophthalmologist`
  ADD PRIMARY KEY (`ophthalmologist_id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `optometrist`
--
ALTER TABLE `optometrist`
  ADD PRIMARY KEY (`optometrist_id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`patient_id`),
  ADD UNIQUE KEY `mrn` (`mrn`),
  ADD UNIQUE KEY `phone_number` (`phone_number`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `patient_diagnoses`
--
ALTER TABLE `patient_diagnoses`
  ADD PRIMARY KEY (`diagnosis_id`),
  ADD KEY `fk_diagnosis_patient` (`patient_id`),
  ADD KEY `fk_diagnosis_ophthalmologist` (`ophthalmologist_id`),
  ADD KEY `fk_diagnosis_condition` (`condition_id`);

--
-- Indexes for table `prescriptions`
--
ALTER TABLE `prescriptions`
  ADD PRIMARY KEY (`prescription_id`),
  ADD KEY `fk_nurse` (`nurse_id`),
  ADD KEY `fk_ophthalmologist` (`ophthalmologist_id`),
  ADD KEY `fk_patient` (`patient_id`),
  ADD KEY `fk_optometrist_id` (`optometrist_id`);

--
-- Indexes for table `referrals`
--
ALTER TABLE `referrals`
  ADD PRIMARY KEY (`referral_id`),
  ADD KEY `patient_id` (`patient_id`),
  ADD KEY `optometrist_id` (`optometrist_id`),
  ADD KEY `ophthalmologist_id` (`ophthalmologist_id`);

--
-- Indexes for table `referral_documents`
--
ALTER TABLE `referral_documents`
  ADD PRIMARY KEY (`document_id`),
  ADD KEY `referral_id` (`referral_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`role_id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Indexes for table `surgeries`
--
ALTER TABLE `surgeries`
  ADD PRIMARY KEY (`surgery_id`),
  ADD UNIQUE KEY `case_number` (`case_number`),
  ADD KEY `fk_surgery_patient` (`patient_id`),
  ADD KEY `fk_surgery_ophthalmologist` (`ophthalmologist_id`),
  ADD KEY `fk_surgery_type` (`surgery_type_id`),
  ADD KEY `fk_surgery_room` (`room_id`);

--
-- Indexes for table `surgery_types`
--
ALTER TABLE `surgery_types`
  ADD PRIMARY KEY (`surgery_type_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `role_id` (`role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `admin_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `data_clerk`
--
ALTER TABLE `data_clerk`
  MODIFY `clerk_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `drug_inventory`
--
ALTER TABLE `drug_inventory`
  MODIFY `drug_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `eye_conditions`
--
ALTER TABLE `eye_conditions`
  MODIFY `condition_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `eye_examinations`
--
ALTER TABLE `eye_examinations`
  MODIFY `exam_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `lab_requests`
--
ALTER TABLE `lab_requests`
  MODIFY `request_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `lab_tests`
--
ALTER TABLE `lab_tests`
  MODIFY `test_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `medical_staff`
--
ALTER TABLE `medical_staff`
  MODIFY `staff_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `operating_rooms`
--
ALTER TABLE `operating_rooms`
  MODIFY `room_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `ophthalmic_nurse`
--
ALTER TABLE `ophthalmic_nurse`
  MODIFY `nurse_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `ophthalmologist`
--
ALTER TABLE `ophthalmologist`
  MODIFY `ophthalmologist_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `optometrist`
--
ALTER TABLE `optometrist`
  MODIFY `optometrist_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `patients`
--
ALTER TABLE `patients`
  MODIFY `patient_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `patient_diagnoses`
--
ALTER TABLE `patient_diagnoses`
  MODIFY `diagnosis_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `prescriptions`
--
ALTER TABLE `prescriptions`
  MODIFY `prescription_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `referrals`
--
ALTER TABLE `referrals`
  MODIFY `referral_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `referral_documents`
--
ALTER TABLE `referral_documents`
  MODIFY `document_id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `role_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `surgeries`
--
ALTER TABLE `surgeries`
  MODIFY `surgery_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `surgery_types`
--
ALTER TABLE `surgery_types`
  MODIFY `surgery_type_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin`
--
ALTER TABLE `admin`
  ADD CONSTRAINT `admin_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `data_clerk`
--
ALTER TABLE `data_clerk`
  ADD CONSTRAINT `data_clerk_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `eye_examinations`
--
ALTER TABLE `eye_examinations`
  ADD CONSTRAINT `eye_examinations_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`),
  ADD CONSTRAINT `eye_examinations_ibfk_2` FOREIGN KEY (`nurse_id`) REFERENCES `ophthalmic_nurse` (`nurse_id`);

--
-- Constraints for table `lab_requests`
--
ALTER TABLE `lab_requests`
  ADD CONSTRAINT `fk_lab_request_ophthalmologist` FOREIGN KEY (`ophthalmologist_id`) REFERENCES `ophthalmologist` (`ophthalmologist_id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `fk_lab_request_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE RESTRICT;

--
-- Constraints for table `ophthalmic_nurse`
--
ALTER TABLE `ophthalmic_nurse`
  ADD CONSTRAINT `ophthalmic_nurse_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `ophthalmologist`
--
ALTER TABLE `ophthalmologist`
  ADD CONSTRAINT `ophthalmologist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `optometrist`
--
ALTER TABLE `optometrist`
  ADD CONSTRAINT `optometrist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `patient_diagnoses`
--
ALTER TABLE `patient_diagnoses`
  ADD CONSTRAINT `fk_diagnosis_condition` FOREIGN KEY (`condition_id`) REFERENCES `eye_conditions` (`condition_id`),
  ADD CONSTRAINT `fk_diagnosis_ophthalmologist` FOREIGN KEY (`ophthalmologist_id`) REFERENCES `ophthalmologist` (`ophthalmologist_id`),
  ADD CONSTRAINT `fk_diagnosis_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`);

--
-- Constraints for table `prescriptions`
--
ALTER TABLE `prescriptions`
  ADD CONSTRAINT `fk_nurse` FOREIGN KEY (`nurse_id`) REFERENCES `ophthalmic_nurse` (`nurse_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_ophthalmologist` FOREIGN KEY (`ophthalmologist_id`) REFERENCES `ophthalmologist` (`ophthalmologist_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_optometrist_id` FOREIGN KEY (`optometrist_id`) REFERENCES `optometrist` (`optometrist_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE CASCADE;

--
-- Constraints for table `referrals`
--
ALTER TABLE `referrals`
  ADD CONSTRAINT `referrals_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `referrals_ibfk_2` FOREIGN KEY (`optometrist_id`) REFERENCES `optometrist` (`optometrist_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `referrals_ibfk_3` FOREIGN KEY (`ophthalmologist_id`) REFERENCES `ophthalmologist` (`ophthalmologist_id`) ON DELETE CASCADE;

--
-- Constraints for table `referral_documents`
--
ALTER TABLE `referral_documents`
  ADD CONSTRAINT `referral_documents_ibfk_1` FOREIGN KEY (`referral_id`) REFERENCES `referrals` (`referral_id`) ON DELETE CASCADE;

--
-- Constraints for table `surgeries`
--
ALTER TABLE `surgeries`
  ADD CONSTRAINT `fk_surgery_ophthalmologist` FOREIGN KEY (`ophthalmologist_id`) REFERENCES `ophthalmologist` (`ophthalmologist_id`),
  ADD CONSTRAINT `fk_surgery_patient` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`),
  ADD CONSTRAINT `fk_surgery_room` FOREIGN KEY (`room_id`) REFERENCES `operating_rooms` (`room_id`),
  ADD CONSTRAINT `fk_surgery_type` FOREIGN KEY (`surgery_type_id`) REFERENCES `surgery_types` (`surgery_type_id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
