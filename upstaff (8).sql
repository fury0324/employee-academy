-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 09, 2026 at 09:15 AM
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
-- Database: `upstaff`
--

-- --------------------------------------------------------

--
-- Table structure for table `academy_news`
--

CREATE TABLE `academy_news` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `summary` text DEFAULT NULL,
  `content` text DEFAULT NULL,
  `category` varchar(100) DEFAULT 'Update',
  `image` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `academy_news`
--

INSERT INTO `academy_news` (`id`, `title`, `summary`, `content`, `category`, `image`, `created_at`, `updated_at`) VALUES
(1, 'Welcome to UpStaff Academy!', 'Your learning journey begins here. Complete courses to earn certifications.', NULL, 'Announcement', NULL, '2026-04-13 21:43:37', '2026-04-13 21:43:37'),
(2, 'New Courses Available', 'Check out our new general courses designed for beginners.', NULL, 'Update', NULL, '2026-04-13 21:43:37', '2026-04-13 21:43:37'),
(3, 'Welcome to UpStaff Academy!', 'Your learning journey begins here. Complete courses to earn certifications.', NULL, 'Announcement', NULL, '2026-04-13 21:43:54', '2026-04-13 21:43:54'),
(4, 'New Courses Available', 'Check out our new general courses designed for beginners.', NULL, 'Update', NULL, '2026-04-13 21:43:54', '2026-04-13 21:43:54');

-- --------------------------------------------------------

--
-- Table structure for table `certificates`
--

CREATE TABLE `certificates` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `course_type` enum('general','upskilling') NOT NULL DEFAULT 'general',
  `certificate_number` varchar(100) NOT NULL,
  `final_score` decimal(5,2) DEFAULT 0.00,
  `total_quizzes_passed` int(11) DEFAULT 0,
  `total_quizzes` int(11) DEFAULT 0,
  `certificate_data` text DEFAULT NULL,
  `download_count` int(11) DEFAULT 0,
  `issued_at` datetime DEFAULT current_timestamp(),
  `expiry_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `certificates`
--

INSERT INTO `certificates` (`id`, `user_id`, `course_id`, `course_type`, `certificate_number`, `final_score`, `total_quizzes_passed`, `total_quizzes`, `certificate_data`, `download_count`, `issued_at`, `expiry_date`) VALUES
(18, 11, 44, 'general', 'UPSTAFF-69FD928999BDE-20260508', 100.00, 10, 10, NULL, 1, '2026-05-08 15:36:41', '2027-05-08'),
(19, 11, 43, 'general', 'UPSTAFF-6A018C5C54DF2-20260511', 90.00, 9, 10, NULL, 0, '2026-05-11 15:59:24', '2027-05-11'),
(20, 11, 42, 'general', 'UPSTAFF-6A018CB993B64-20260511', 100.00, 10, 10, NULL, 1, '2026-05-11 16:00:57', '2027-05-11'),
(22, 11, 36, 'general', 'UPSTAFF-6A0211E4E419B-20260511', 100.00, 10, 10, NULL, 0, '2026-05-12 01:29:08', '2027-05-11'),
(23, 11, 39, 'general', 'UPSTAFF-6A0214964A1A3-20260511', 100.00, 10, 10, NULL, 0, '2026-05-12 01:40:38', '2027-05-11'),
(24, 15, 44, 'general', 'UPSTAFF-6A022AB23357E-20260511', 100.00, 10, 10, NULL, 0, '2026-05-12 03:14:58', '2027-05-11'),
(25, 15, 43, 'general', 'UPSTAFF-6A02728C54F6A-20260512', 90.00, 9, 10, NULL, 0, '2026-05-12 08:21:32', '2027-05-12'),
(26, 11, 38, 'general', 'UPSKILL-6A029145BBF6E-20260512', 100.00, 10, 10, NULL, 1, '2026-05-12 10:32:37', '2028-05-12'),
(27, 21, 42, 'general', 'UPSKILL-6A02AC769C787-20260512', 100.00, 10, 10, NULL, 0, '2026-05-12 12:28:38', '2028-05-12'),
(28, 21, 44, 'general', 'UPSKILL-6A02AD43E26A3-20260512', 100.00, 10, 10, NULL, 0, '2026-05-12 12:32:03', '2028-05-12'),
(29, 21, 43, 'general', 'UPSTAFF-6A02B20C0A1AB-20260512', 90.00, 9, 10, NULL, 0, '2026-05-12 12:52:28', '2027-05-12'),
(30, 21, 51, 'general', 'UPSKILL-6A27A74B2BB9E-20260609', 100.00, 2, 2, NULL, 0, '2026-06-09 13:40:27', '2028-06-09');

-- --------------------------------------------------------

--
-- Table structure for table `certificate_claims`
--

CREATE TABLE `certificate_claims` (
  `id` int(11) NOT NULL,
  `certificate_id` int(11) NOT NULL,
  `claimed_by_admin_id` int(11) NOT NULL,
  `claimed_at` datetime DEFAULT current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `certificate_claims`
--

INSERT INTO `certificate_claims` (`id`, `certificate_id`, `claimed_by_admin_id`, `claimed_at`, `ip_address`, `notes`, `created_at`) VALUES
(5, 18, 1, '2026-05-08 15:42:02', '::1', 'Claimed via validation portal', '2026-05-08 07:42:02'),
(6, 20, 1, '2026-05-12 12:12:49', '::1', 'Claimed via validation portal', '2026-05-12 04:12:49'),
(7, 26, 1, '2026-06-04 17:13:55', '::1', 'Claimed via validation portal', '2026-06-04 09:13:55');

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `type` enum('general','upskilling') DEFAULT 'general',
  `category` varchar(100) DEFAULT NULL,
  `difficulty` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `thumbnail_url` varchar(500) DEFAULT NULL,
  `status` enum('draft','published') DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`id`, `title`, `type`, `category`, `difficulty`, `description`, `thumbnail_url`, `status`, `created_at`, `updated_at`) VALUES
(36, 'XERO', 'upskilling', 'Accounting & Financial Software QuickBooks', 'Beginner', '<p class=\"ql-align-justify\"><span style=\"background-color: transparent; color: rgb(0, 0, 0);\">This guide introduces Xero, a cloud-based accounting platform tailored for managing the financial operations of small and medium-sized businesses. The source details three distinct subscription tiers—Early, Growing, and Established—designed to accommodate various stages of professional growth and complexity. Users can navigate a comprehensive dashboard to monitor real-time metrics, handle invoicing, track unpaid bills, and automate bank reconciliations. The text also highlights advanced functionalities, including AI-driven insights via the Jax tool and deep customization options within the settings and reporting menus. Ultimately, the material serves as a practical walkthrough for entrepreneurs looking to centralize their bookkeeping, payroll, and tax obligations within a single digital ecosystem.</span></p><p><br></p><p><br></p>', 'http://localhost:8080/upstaff/uploads/1778057339_maxresdefault.jpg', 'published', '2026-05-06 08:47:24', '2026-05-06 08:54:48'),
(37, 'MYOB', 'upskilling', 'Accounting & Financial Software QuickBooks', 'Beginner', '<p><span style=\"background-color: transparent; color: rgb(0, 0, 0);\">The provided text serves as a comprehensive guide to the features and benefits of the MYOB accounting software, specifically targeting small business owners and independent contractors. It highlights automated tools for expense tracking and invoice creation, while emphasizing the platform\'s ability to ensure Australian tax compliance and simplify bank reconciliations. The source also introduces MYOB Solo, a specialized mobile application tailored for diverse professionals such as tradies, consultants, and creative freelancers. By comparing the software to traditional spreadsheets, the guide illustrates how users can achieve better cash flow visibility and administrative freedom through automation. Finally, the overview notes that while the system is highly robust and scalable, potential users should consider its learning curve and cost relative to other market competitors.</span></p>', 'http://localhost:8080/upstaff/uploads/1778057749_maxresdefault1.jpg', 'published', '2026-05-06 08:55:51', '2026-05-06 09:02:11'),
(38, 'SAP', 'upskilling', 'Accounting & Financial Software QuickBooks', 'Beginner', '<p><span style=\"background-color: transparent; color: rgb(0, 0, 0);\">This source introduces SAP, a prominent German software corporation that leads the global market in Enterprise Resource Planning (ERP) solutions. The creator explains that the term refers to both the organization itself and its primary digital products used to manage business operations like finance, sales, and human resources. By comparing it to iconic brands like Microsoft and Coca-Cola, the text illustrates how the name functions as both a corporate identity and a household name for its software. The material emphasizes the massive scale of the company, noting its significant role in processing a majority of the world\'s transaction revenue. Ultimately, the overview highlights the high professional value of SAP proficiency due to its widespread adoption by the world\'s largest and most successful global enterprises.</span></p>', 'http://localhost:8080/upstaff/uploads/1778058251_maxresdefault2.jpg', 'published', '2026-05-06 09:04:13', '2026-05-06 09:09:30'),
(39, 'Netsuite', 'upskilling', 'Accounting & Financial Software QuickBooks', 'Beginner', '<p class=\"ql-align-justify\"><span style=\"background-color: transparent;\">This source features a video demonstration of NetSuite 2025, a long-standing cloud-based ERP software primarily known for its robust accounting and financial management capabilities. The presenter highlights how the platform uses role-based dashboards to provide users with immediate access to customizable KPIs, reminders, and reporting shortcuts. Beyond core finances, the software supports diverse business needs such as inventory management, CRM, and HR through internal modules and a vast third-party app ecosystem. Viewers are shown how to navigate automated reports, utilize advanced SuiteAnalytics for data visualization, and manage complex records or transactions. Finally, the guide offers strategic advice on purchasing the software and selecting an effective implementation team to ensure business success.</span></p><p><br></p>', 'http://localhost:8080/upstaff/uploads/1778058641_maxresdefault3.jpg', 'published', '2026-05-06 09:10:43', '2026-05-06 09:16:20'),
(40, 'Trello', 'upskilling', 'Project Management & Task Tracking', 'Beginner', '<p><span style=\"background-color: transparent; color: rgb(0, 0, 0);\">Trello is a versatile project management tool designed to help individuals and teams organize their workflows through a visual system of boards, lists, and cards. Users can initiate a project by creating a board and defining different stages of progress using customizable vertical columns. Within these columns, individual tasks are represented by cards that can be easily moved between lists to reflect their current status. These cards provide a high level of detail, allowing users to incorporate checklists, labels, deadlines, and file attachments to keep all relevant information in one place. To maintain an organized workspace, the system includes filtering options to find specific tasks and an archiving feature to clear out completed items. Additionally, the platform supports power-ups, which are specialized integrations that expand the software\'s basic functionality to meet more complex professional needs.</span></p>', 'http://localhost:8080/upstaff/uploads/1778059017_maxresdefault4.jpg', 'published', '2026-05-06 09:16:59', '2026-05-06 09:22:05'),
(41, 'ClickUp', 'upskilling', 'Project Management & Task Tracking', 'Beginner', '<p class=\"ql-align-justify\"><span style=\"background-color: transparent; color: rgb(0, 0, 0);\">The provided text serves as a comprehensive introductory guide to ClickUp, a project management tool marketed as an \"everything app.\" It highlights the platform\'s extensive accessibility across devices and outlines various pricing tiers, ranging from a restricted free plan to feature-rich premium options. The overview details core functionalities such as customizable tasks, diverse data views like Gantt charts and mind maps, and integrated tools for document hosting and team chatting. Additionally, the source explores advanced features like AI-assisted automation, real-time performance dashboards, and collaborative whiteboards designed to centralize a team\'s workflow. Ultimately, the text positions ClickUp as a versatile solution for users seeking to consolidate multiple productivity applications into a single, organized environment.</span></p>', 'http://localhost:8080/upstaff/uploads/1778059444_maxresdefault5.jpg', 'published', '2026-05-06 09:22:51', '2026-05-06 09:29:34'),
(42, 'Microsoft Office', 'general', 'Office Productivity & Collaboration', 'Beginner', '<p class=\"ql-align-justify\"><span style=\"background-color: transparent; color: rgb(0, 0, 0);\">This comprehensive guide serves as a foundational tutorial for individuals looking to master Microsoft Word, covering everything from initial setup to advanced document distribution. The source meticulously details the software interface, explaining how to navigate the ribbon, utilize dialogue launchers, and customize the quick access toolbar. Readers learn essential skills for text manipulation, including efficient selection techniques, formatting paragraphs, and using the find and replace tool to manage large documents. Beyond basic typing, the text provides instructions on inserting structured tables, adjusting page layouts, and adding professional elements like headers and cover pages. Furthermore, it highlights critical administrative features such as spell check, autocorrect, and various methods for printing or exporting files as PDFs. Prepared as a complete course, the material aims to transform beginners into proficient users capable of producing polished, professional work.</span></p><p><br></p><p><br></p>', 'http://localhost:8080/upstaff/uploads/1778218820_maxresdefault6.jpg', 'published', '2026-05-08 05:40:22', '2026-05-08 05:46:29'),
(43, 'Google workspace', 'general', 'Office Productivity & Collaboration', 'Beginner', '<p class=\"ql-align-justify\"><span style=\"background-color: transparent; color: rgb(0, 0, 0);\">The provided source serves as a comprehensive introduction to Google Workspace, emphasizing its role as a professional upgrade from standard consumer Gmail accounts. The text highlights that while the tools may look familiar, the business version allows for custom domain names and advanced administrative controls over security and user policies. A major focus is placed on the Business Standard plan, which offers essential features like shared drives for data ownership and the ability to record and transcribe Google Meet sessions. The guide also details collaborative benefits within Google Docs, such as real-time editing and an unlimited revision history for tracking changes. Additionally, it offers practical advice on the technical setup process, specifically mentioning the importance of DNS and SPF records to ensure email reliability. Ultimately, the source positions the platform as a powerful ecosystem designed to help small business owners streamline their productivity and secure their company data.</span></p><p><br></p><p><br></p>', 'http://localhost:8080/upstaff/uploads/1778219237_maxresdefault7.jpg', 'published', '2026-05-08 05:47:18', '2026-05-08 05:52:14'),
(44, 'SLACK', 'general', 'Office Productivity & Collaboration', 'Beginner', '<p><span style=\"background-color: transparent; color: rgb(0, 0, 0);\">This comprehensive tutorial for beginners details how to effectively navigate and utilize Slack as a central hub for team collaboration. The guide explains the setup process, whether creating a new workspace or joining an existing company group via email. It highlights the platform\'s core features, such as channels for organized group discussions and direct messages for private, one-on-one communication. The source also touches on the benefits of managing files and integrating tools like Zoom to streamline workflows and reduce email clutter. Additionally, it offers tips on messaging etiquette, such as using threads and reactions to maintain a tidy digital environment. Ultimately, the material emphasizes how the software fosters seamless communication for both remote and office-based teams.</span></p>', 'http://localhost:8080/upstaff/uploads/1778219574_maxresdefault8.jpg', 'published', '2026-05-08 05:52:55', '2026-05-08 05:57:08'),
(51, 'demo', 'general', 'Office Productivity & Collaboration', 'Beginner', '<p>demo lang for sir hahahahahahahahahahaha</p>', 'http://localhost:8080/upstaff/uploads/1780563082_Screenshot2026-06-03203548.png', 'published', '2026-06-04 08:50:47', '2026-06-04 08:56:56');

-- --------------------------------------------------------

--
-- Table structure for table `employee_logs`
--

CREATE TABLE `employee_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `timestamp` datetime DEFAULT current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employee_logs`
--

INSERT INTO `employee_logs` (`id`, `user_id`, `action`, `details`, `timestamp`, `ip_address`) VALUES
(1, 3, 'Login', NULL, '2026-04-12 19:08:55', '::1'),
(2, 3, 'Logout', NULL, '2026-04-12 19:09:37', '::1'),
(3, 3, 'Login', NULL, '2026-04-13 21:26:40', '::1'),
(4, 1, 'Login', NULL, '2026-04-14 00:18:48', '192.168.254.169'),
(5, 9, 'Login', NULL, '2026-04-14 00:22:30', '192.168.254.173'),
(6, 1, 'Login', NULL, '2026-04-14 01:01:33', '192.168.254.175'),
(7, 1, 'Login', NULL, '2026-04-14 01:09:02', '::1'),
(8, 1, 'Logout', NULL, '2026-04-14 01:23:58', '::1'),
(9, 1, 'Login', NULL, '2026-04-14 01:27:32', '::1'),
(10, 1, 'Logout', NULL, '2026-04-14 02:28:39', '192.168.254.175'),
(11, 8, 'Login', NULL, '2026-04-14 02:29:55', '192.168.254.175'),
(12, 1, 'Course Deleted', 'Deleted course ID: 1 - Title: DADW VAHJD - Deleted 1 videos, 2 quizzes, 2 questions, 4 options', '2026-04-14 02:31:15', '::1'),
(13, 1, 'Logout', NULL, '2026-04-14 02:45:53', '::1'),
(14, 1, 'Login', NULL, '2026-04-14 02:45:57', '::1'),
(15, 1, 'Course Deleted', 'Deleted course ID: 2 - Title: for beginner - Deleted 1 videos, 3 quizzes, 3 questions, 6 options', '2026-04-14 05:19:21', '::1'),
(16, 1, 'Course Deleted', 'Deleted course ID: 3 - Title: for everyone - Deleted 1 videos, 3 quizzes, 3 questions, 6 options', '2026-04-14 05:19:29', '::1'),
(17, 1, 'Logout', NULL, '2026-04-14 05:19:48', '::1'),
(18, 1, 'Login', NULL, '2026-04-14 05:19:53', '::1'),
(19, 1, 'Course Deleted', 'Deleted course ID: 5 - Title: DADAD - Deleted 1 videos, 2 quizzes, 2 questions, 4 options', '2026-04-14 05:25:58', '::1'),
(20, 1, 'Course Deleted', 'Deleted course ID: 4 - Title: para sa lahat - Deleted 1 videos, 3 quizzes, 3 questions, 6 options', '2026-04-14 05:26:04', '::1'),
(21, 8, 'Login', NULL, '2026-04-14 05:40:30', '::1'),
(22, 8, 'Logout', NULL, '2026-04-14 05:58:25', '::1'),
(23, 1, 'Login', NULL, '2026-04-14 21:35:15', '::1'),
(24, 8, 'Login', NULL, '2026-04-14 21:36:05', '::1'),
(25, 1, 'Login', NULL, '2026-04-14 22:01:18', '::1'),
(26, 8, 'Login', NULL, '2026-04-14 22:43:04', '::1'),
(27, 1, 'Login', NULL, '2026-04-14 22:56:17', '::1'),
(28, 8, 'Login', NULL, '2026-04-14 22:58:18', '::1'),
(29, 1, 'Login', NULL, '2026-04-14 23:04:13', '::1'),
(30, 8, 'Login', NULL, '2026-04-14 23:07:01', '::1'),
(31, 1, 'Login', NULL, '2026-04-15 01:45:00', '::1'),
(32, 8, 'Login', NULL, '2026-04-15 01:46:56', '::1'),
(33, 8, 'Logout', NULL, '2026-04-15 01:59:33', '::1'),
(34, 8, 'Login', NULL, '2026-04-15 01:59:35', '::1'),
(35, 8, 'Logout', NULL, '2026-04-15 02:08:48', '::1'),
(36, 1, 'Login', NULL, '2026-04-15 02:08:51', '::1'),
(37, 1, 'Logout', NULL, '2026-04-15 02:10:19', '::1'),
(38, 8, 'Login', NULL, '2026-04-15 02:10:21', '::1'),
(39, 8, 'Logout', NULL, '2026-04-15 03:35:25', '::1'),
(40, 1, 'Login', NULL, '2026-04-15 03:35:28', '::1'),
(41, 1, 'Logout', NULL, '2026-04-15 03:41:15', '::1'),
(42, 8, 'Login', NULL, '2026-04-15 03:41:19', '::1'),
(43, 8, 'Logout', NULL, '2026-04-15 03:56:57', '::1'),
(44, 1, 'Login', NULL, '2026-04-15 03:57:01', '::1'),
(45, 8, 'Login', NULL, '2026-04-15 03:57:21', '::1'),
(46, 8, 'Logout', NULL, '2026-04-15 05:01:42', '::1'),
(47, 1, 'Login', NULL, '2026-04-15 05:01:45', '::1'),
(48, 1, 'Logout', NULL, '2026-04-15 05:14:14', '::1'),
(49, 8, 'Login', NULL, '2026-04-15 05:14:17', '::1'),
(50, 8, 'Logout', NULL, '2026-04-15 05:15:06', '::1'),
(51, 1, 'Login', NULL, '2026-04-15 05:15:09', '::1'),
(52, 8, 'Login', NULL, '2026-04-15 05:16:32', '::1'),
(53, 8, 'Logout', NULL, '2026-04-15 05:19:13', '::1'),
(54, 8, 'Login', NULL, '2026-04-15 05:20:30', '192.168.254.169'),
(55, 8, 'Login', NULL, '2026-04-15 05:24:58', '::1'),
(56, 1, 'Login', NULL, '2026-04-15 05:25:25', '::1'),
(57, 1, 'Course Deleted', 'Deleted course ID: 7 - Title: for beginner - Deleted 1 videos, 2 quizzes, 2 questions, 4 options', '2026-04-15 05:58:21', '::1'),
(58, 1, 'Course Deleted', 'Deleted course ID: 8 - Title: for beginner - Deleted 1 videos, 4 quizzes, 4 questions, 8 options', '2026-04-15 05:58:26', '::1'),
(59, 1, 'Course Deleted', 'Deleted course ID: 9 - Title: for everyone - Deleted 1 videos, 3 quizzes, 3 questions, 6 options', '2026-04-15 05:58:31', '::1'),
(60, 8, 'Logout', NULL, '2026-04-15 05:59:37', '192.168.254.169'),
(61, 9, 'Login', NULL, '2026-04-15 06:00:56', '192.168.254.169'),
(62, 1, 'Login', NULL, '2026-04-15 21:59:58', '::1'),
(63, 1, 'Logout', NULL, '2026-04-15 22:02:24', '::1'),
(64, 8, 'Login', NULL, '2026-04-15 22:02:27', '::1'),
(65, 8, 'Logout', NULL, '2026-04-15 22:06:54', '::1'),
(66, 1, 'Login', NULL, '2026-04-15 22:06:57', '::1'),
(67, 1, 'Logout', NULL, '2026-04-15 22:07:19', '::1'),
(68, 8, 'Login', NULL, '2026-04-15 22:07:22', '::1'),
(69, 8, 'Logout', NULL, '2026-04-15 22:21:04', '::1'),
(70, 1, 'Login', NULL, '2026-04-15 22:21:07', '::1'),
(71, 1, 'Login', NULL, '2026-04-15 22:26:20', '192.168.254.169'),
(72, 1, 'Logout', NULL, '2026-04-15 22:26:47', '192.168.254.169'),
(73, 1, 'Login', NULL, '2026-04-15 22:26:54', '192.168.254.169'),
(74, 1, 'Logout', NULL, '2026-04-15 22:34:50', '::1'),
(75, 8, 'Login', NULL, '2026-04-15 22:34:53', '::1'),
(76, 8, 'Logout', NULL, '2026-04-15 22:38:19', '::1'),
(77, 1, 'Login', NULL, '2026-04-15 22:38:22', '::1'),
(78, 1, 'Login', NULL, '2026-04-15 23:02:25', '::1'),
(79, 1, 'Login', NULL, '2026-04-15 23:32:26', '192.168.254.169'),
(80, 1, 'Logout', NULL, '2026-04-15 23:33:14', '192.168.254.169'),
(87, 1, 'Login', NULL, '2026-04-16 00:07:22', '192.168.254.169'),
(88, 1, 'Logout', NULL, '2026-04-16 00:08:55', '192.168.254.169'),
(90, 1, 'Logout', NULL, '2026-04-16 01:06:56', '::1'),
(91, 8, 'Login', NULL, '2026-04-16 01:06:59', '::1'),
(92, 8, 'Logout', NULL, '2026-04-16 01:43:52', '::1'),
(93, 1, 'Login', NULL, '2026-04-16 01:43:55', '::1'),
(94, 1, 'Logout', NULL, '2026-04-16 01:47:29', '::1'),
(95, 8, 'Login', NULL, '2026-04-16 01:47:32', '::1'),
(96, 8, 'Logout', NULL, '2026-04-16 03:41:07', '::1'),
(97, 1, 'Login', NULL, '2026-04-16 03:41:11', '::1'),
(99, 1, 'Login', NULL, '2026-04-16 03:41:51', '192.168.254.169'),
(100, 1, 'Logout', NULL, '2026-04-16 03:42:51', '192.168.254.169'),
(102, 1, 'Logout', NULL, '2026-04-16 03:58:37', '::1'),
(103, 1, 'Login', NULL, '2026-04-16 03:58:41', '::1'),
(104, 1, 'Login', NULL, '2026-04-20 22:34:39', '::1'),
(105, 1, 'Logout', NULL, '2026-04-20 22:35:25', '::1'),
(106, 8, 'Login', NULL, '2026-04-20 22:35:29', '::1'),
(107, 1, 'Login', NULL, '2026-04-20 22:37:06', '::1'),
(108, 1, 'Login', NULL, '2026-04-20 22:39:04', '::1'),
(109, 1, 'Login', NULL, '2026-04-20 22:41:43', '192.168.254.169'),
(110, 1, 'Logout', NULL, '2026-04-20 22:43:23', '192.168.254.169'),
(111, 1, 'Login', NULL, '2026-04-20 22:44:04', '192.168.254.169'),
(112, 1, 'Logout', NULL, '2026-04-20 22:44:45', '192.168.254.169'),
(115, 1, 'Login', NULL, '2026-04-20 22:47:45', '192.168.254.169'),
(116, 1, 'Logout', NULL, '2026-04-20 22:48:03', '192.168.254.169'),
(118, 1, 'Logout', NULL, '2026-04-20 22:58:36', '::1'),
(119, 8, 'Login', NULL, '2026-04-20 22:58:41', '::1'),
(120, 8, 'Logout', NULL, '2026-04-20 22:58:45', '::1'),
(121, 8, 'Login', NULL, '2026-04-20 22:58:53', '::1'),
(122, 8, 'Logout', NULL, '2026-04-20 22:59:36', '::1'),
(123, 1, 'Login', NULL, '2026-04-20 22:59:41', '::1'),
(124, 1, 'Logout', NULL, '2026-04-20 23:42:38', '::1'),
(125, 3, 'Login', NULL, '2026-04-20 23:45:24', '::1'),
(126, 3, 'Logout', NULL, '2026-04-20 23:49:41', '::1'),
(127, 3, 'Login', NULL, '2026-04-20 23:57:15', '::1'),
(128, 3, 'Logout', NULL, '2026-04-21 00:03:22', '::1'),
(132, 3, 'Login', NULL, '2026-04-21 00:11:07', '::1'),
(136, 1, 'Login', NULL, '2026-04-21 00:21:46', '192.168.254.169'),
(137, 1, 'Logout', NULL, '2026-04-21 00:23:49', '192.168.254.169'),
(142, 1, 'Login', NULL, '2026-04-21 00:27:43', '192.168.254.169'),
(143, 3, 'Logout', NULL, '2026-04-21 00:28:08', '::1'),
(144, 1, 'Login', NULL, '2026-04-21 00:28:24', '::1'),
(145, 1, 'Logout', NULL, '2026-04-21 00:28:35', '::1'),
(146, 8, 'Login', NULL, '2026-04-21 00:28:39', '::1'),
(147, 1, 'Logout', NULL, '2026-04-21 00:29:55', '192.168.254.169'),
(150, 8, 'Logout', NULL, '2026-04-21 00:47:25', '::1'),
(151, 3, 'Login', NULL, '2026-04-21 00:47:30', '::1'),
(152, 3, 'Logout', NULL, '2026-04-21 01:18:14', '::1'),
(153, 1, 'Login', NULL, '2026-04-21 01:18:28', '::1'),
(154, 1, 'Logout', NULL, '2026-04-21 01:19:41', '::1'),
(155, 3, 'Login', NULL, '2026-04-21 01:19:44', '::1'),
(156, 3, 'Logout', NULL, '2026-04-21 01:33:02', '::1'),
(157, 1, 'Login', NULL, '2026-04-21 01:33:16', '::1'),
(159, 1, 'Logout', NULL, '2026-04-21 01:35:10', '::1'),
(160, 3, 'Login', NULL, '2026-04-21 01:35:15', '::1'),
(161, 3, 'Logout', NULL, '2026-04-21 01:36:15', '::1'),
(162, 1, 'Login', NULL, '2026-04-21 01:36:27', '::1'),
(163, 1, 'Logout', NULL, '2026-04-21 01:46:08', '::1'),
(164, 3, 'Login', NULL, '2026-04-21 01:46:12', '::1'),
(165, 1, 'Login', NULL, '2026-04-21 01:54:12', '192.168.254.169'),
(166, 3, 'Login', NULL, '2026-04-21 22:24:18', '::1'),
(167, 3, 'Logout', NULL, '2026-04-21 22:24:21', '::1'),
(168, 1, 'Login', NULL, '2026-04-21 22:24:34', '::1'),
(169, 1, 'Login', NULL, '2026-04-22 00:52:34', '::1'),
(170, 1, 'Login', NULL, '2026-04-22 00:52:59', '::1'),
(171, 1, 'Logout', NULL, '2026-04-22 00:53:06', '::1'),
(172, 8, 'Login', NULL, '2026-04-22 00:53:10', '::1'),
(173, 1, 'Logout', NULL, '2026-04-22 00:55:52', '::1'),
(174, 1, 'Login', NULL, '2026-04-22 00:56:41', '::1'),
(175, 1, 'Logout', NULL, '2026-04-22 00:59:36', '::1'),
(176, 8, 'Login', NULL, '2026-04-22 00:59:46', '::1'),
(177, 8, 'Logout', NULL, '2026-04-22 00:59:56', '::1'),
(178, 8, 'Logout', NULL, '2026-04-22 01:03:43', '::1'),
(179, 8, 'Login', NULL, '2026-04-22 01:06:27', '::1'),
(180, 8, 'Logout', NULL, '2026-04-22 01:06:31', '::1'),
(181, 8, 'Login', NULL, '2026-04-22 01:09:24', '::1'),
(182, 8, 'Password changed', '{\"changed\":\"password\"}', '2026-04-22 02:27:42', '::1'),
(183, 8, 'Logout', NULL, '2026-04-22 02:27:48', '::1'),
(184, 8, 'Login', NULL, '2026-04-22 02:27:55', '::1'),
(185, 8, 'Logout', NULL, '2026-04-22 02:28:10', '::1'),
(186, 1, 'Login', NULL, '2026-04-22 02:28:19', '::1'),
(187, 1, 'Logout', NULL, '2026-04-22 02:31:57', '::1'),
(188, 1, 'Login', NULL, '2026-04-22 02:32:23', '::1'),
(189, 1, 'Course Deleted', 'Deleted course ID: 14 - Title: adada - Deleted 1 videos, 3 quizzes, 3 questions, 6 options', '2026-04-22 04:32:32', '::1'),
(190, 1, 'Course Deleted', 'Deleted course ID: 12 - Title: dadad - Deleted 1 videos, 2 quizzes, 2 questions, 4 options', '2026-04-22 04:32:40', '::1'),
(191, 1, 'Logout', NULL, '2026-04-22 05:23:34', '::1'),
(192, 8, 'Login', NULL, '2026-04-22 05:23:37', '::1'),
(193, 8, 'Logout', NULL, '2026-04-22 05:24:02', '::1'),
(194, 1, 'Login', NULL, '2026-04-22 05:24:08', '::1'),
(195, 1, 'Logout', NULL, '2026-04-22 05:24:55', '::1'),
(196, 8, 'Login', NULL, '2026-04-22 05:25:50', '::1'),
(197, 8, 'Logout', NULL, '2026-04-22 05:26:09', '::1'),
(198, 8, 'Login', NULL, '2026-04-22 05:26:20', '::1'),
(199, 8, 'Logout', NULL, '2026-04-22 05:26:23', '::1'),
(200, 1, 'Login', NULL, '2026-04-22 05:26:26', '::1'),
(201, 1, 'Logout', NULL, '2026-04-22 05:27:44', '::1'),
(202, 8, 'Login', NULL, '2026-04-22 05:27:46', '::1'),
(203, 8, 'Login', NULL, '2026-04-22 05:30:37', '::1'),
(204, 8, 'Logout', NULL, '2026-04-22 05:32:25', '::1'),
(205, 1, 'Login', NULL, '2026-04-22 05:32:31', '::1'),
(206, 1, 'Login', NULL, '2026-04-22 21:20:47', '::1'),
(207, 1, 'Logout', NULL, '2026-04-22 21:22:53', '::1'),
(208, 8, 'Login', NULL, '2026-04-22 21:23:08', '::1'),
(209, 8, 'Logout', NULL, '2026-04-22 21:24:46', '::1'),
(210, 1, 'Login', NULL, '2026-04-22 21:24:56', '::1'),
(211, 1, 'Logout', NULL, '2026-04-22 21:34:53', '::1'),
(212, 8, 'Login', NULL, '2026-04-22 21:35:09', '::1'),
(213, 1, 'Login', NULL, '2026-04-22 21:35:50', '::1'),
(214, 1, 'Login', NULL, '2026-04-22 22:24:21', '::1'),
(215, 1, 'Logout', NULL, '2026-04-22 22:50:37', '::1'),
(218, 1, 'Login', NULL, '2026-04-22 23:18:54', '::1'),
(219, 1, 'Logout', NULL, '2026-04-22 23:19:33', '::1'),
(222, 1, 'Login', NULL, '2026-04-23 00:46:51', '::1'),
(223, 1, 'Logout', NULL, '2026-04-23 00:47:18', '::1'),
(225, 1, 'Login', NULL, '2026-04-23 01:30:41', '::1'),
(227, 1, 'Login', NULL, '2026-04-23 03:19:06', '::1'),
(232, 1, 'Login', NULL, '2026-04-23 05:11:25', '::1'),
(233, 1, 'Logout', NULL, '2026-04-23 05:13:41', '::1'),
(237, 1, 'Login', NULL, '2026-04-23 22:14:21', '::1'),
(238, 1, 'Logout', NULL, '2026-04-23 22:14:36', '::1'),
(241, 1, 'Login', NULL, '2026-04-23 22:15:31', '::1'),
(242, 1, 'Logout', NULL, '2026-04-23 22:17:07', '::1'),
(247, 1, 'Login', NULL, '2026-04-23 22:37:51', '::1'),
(248, 1, 'Logout', NULL, '2026-04-23 22:45:09', '::1'),
(250, 1, 'Login', NULL, '2026-04-23 22:51:49', '::1'),
(251, 1, 'Logout', NULL, '2026-04-23 22:54:08', '::1'),
(255, 1, 'Login', NULL, '2026-04-23 23:34:14', '::1'),
(258, 1, 'Login', NULL, '2026-04-23 23:38:52', '::1'),
(259, 1, 'Logout', NULL, '2026-04-23 23:40:20', '::1'),
(262, 1, 'Login', NULL, '2026-04-23 23:43:06', '::1'),
(263, 1, 'Course Deleted', 'Deleted course ID: 18 - Title: web dev intro - Deleted 1 videos, 3 quizzes, 3 questions, 6 options', '2026-04-23 23:43:25', '::1'),
(264, 1, 'Logout', NULL, '2026-04-23 23:44:53', '::1'),
(267, 1, 'Login', NULL, '2026-04-23 23:48:31', '::1'),
(268, 1, 'Logout', NULL, '2026-04-23 23:49:40', '::1'),
(271, 1, 'Login', NULL, '2026-04-24 00:27:59', '::1'),
(272, 1, 'Logout', NULL, '2026-04-24 00:29:21', '::1'),
(275, 1, 'Login', NULL, '2026-04-24 00:31:28', '::1'),
(276, 1, 'Logout', NULL, '2026-04-24 00:32:29', '::1'),
(279, 1, 'Login', NULL, '2026-04-24 00:38:48', '::1'),
(280, 1, 'Logout', NULL, '2026-04-24 00:40:06', '::1'),
(283, 8, 'Login', NULL, '2026-04-24 01:16:36', '::1'),
(284, 8, 'Logout', NULL, '2026-04-24 02:09:51', '::1'),
(285, 1, 'Login', NULL, '2026-04-24 02:09:54', '::1'),
(286, 1, 'Course Deleted', 'Deleted course ID: 25 - Title:  - Deleted 0 videos, 0 quizzes, 0 questions, 0 options', '2026-04-24 02:17:18', '::1'),
(287, 1, 'Login', NULL, '2026-04-24 02:30:48', '::1'),
(288, 1, 'Logout', NULL, '2026-04-24 03:57:54', '::1'),
(289, 8, 'Login', NULL, '2026-04-24 03:57:57', '::1'),
(290, 8, 'Logout', NULL, '2026-04-24 03:59:46', '::1'),
(291, 1, 'Login', NULL, '2026-04-24 03:59:48', '::1'),
(292, 1, 'Login', NULL, '2026-04-24 04:05:23', '::1'),
(293, 1, 'Logout', NULL, '2026-04-24 05:13:30', '::1'),
(294, 8, 'Login', NULL, '2026-04-24 05:13:33', '::1'),
(295, 8, 'Logout', NULL, '2026-04-24 05:23:18', '::1'),
(298, 8, 'Login', NULL, '2026-04-24 05:26:29', '::1'),
(299, 8, 'Logout', NULL, '2026-04-24 05:27:10', '::1'),
(300, 1, 'Login', NULL, '2026-04-24 05:27:19', '::1'),
(301, 1, 'Logout', NULL, '2026-04-24 05:27:39', '::1'),
(302, 8, 'Login', NULL, '2026-04-24 05:27:42', '::1'),
(303, 1, 'Login', NULL, '2026-04-24 05:39:57', '::1'),
(304, 1, 'Logout', NULL, '2026-04-24 05:42:30', '::1'),
(308, 1, 'Login', NULL, '2026-04-24 21:51:57', '::1'),
(309, 1, 'Logout', NULL, '2026-04-24 21:53:05', '::1'),
(312, 8, 'Login', NULL, '2026-04-24 21:54:13', '::1'),
(313, 8, 'Logout', NULL, '2026-04-24 22:12:56', '::1'),
(316, 1, 'Login', NULL, '2026-04-24 22:31:53', '::1'),
(317, 1, 'Logout', NULL, '2026-04-24 22:33:21', '::1'),
(318, 1, 'Login', NULL, '2026-04-24 22:33:36', '::1'),
(319, 1, 'Logout', NULL, '2026-04-24 23:11:56', '::1'),
(322, 1, 'Login', NULL, '2026-04-24 23:39:54', '::1'),
(323, 1, 'Logout', NULL, '2026-04-25 00:07:21', '::1'),
(324, 8, 'Login', NULL, '2026-04-25 00:07:25', '::1'),
(325, 8, 'Logout', NULL, '2026-04-25 00:07:46', '::1'),
(326, 8, 'Login', NULL, '2026-04-25 00:07:53', '::1'),
(327, 1, 'Login', NULL, '2026-04-25 00:08:13', '::1'),
(328, 1, 'Course Deleted', 'Deleted course ID: 26 - Title:  - Deleted 0 videos, 0 quizzes, 0 questions, 0 options', '2026-04-25 00:10:13', '::1'),
(329, 8, 'Login', NULL, '2026-04-25 00:42:24', '::1'),
(330, 8, 'Logout', NULL, '2026-04-25 00:42:40', '::1'),
(331, 1, 'Login', NULL, '2026-04-25 00:42:43', '::1'),
(332, 1, 'Logout', NULL, '2026-04-25 00:44:38', '::1'),
(336, 1, 'Login', NULL, '2026-04-25 00:47:43', '::1'),
(337, 8, 'Login', NULL, '2026-04-25 01:33:04', '::1'),
(338, 1, 'Login', NULL, '2026-04-25 01:42:31', '::1'),
(339, 1, 'Logout', NULL, '2026-04-25 04:07:43', '::1'),
(342, 1, 'Login', NULL, '2026-04-25 04:10:06', '::1'),
(343, 1, 'Logout', NULL, '2026-04-25 04:21:13', '::1'),
(344, 8, 'Login', NULL, '2026-04-25 04:21:18', '::1'),
(345, 8, 'Logout', NULL, '2026-04-25 04:21:25', '::1'),
(348, 1, 'Login', NULL, '2026-04-25 04:37:30', '::1'),
(349, 1, 'Login', NULL, '2026-04-25 04:38:06', '::1'),
(352, 1, 'Login', NULL, '2026-04-25 04:39:33', '::1'),
(354, 1, 'Login', NULL, '2026-04-25 04:42:47', '::1'),
(355, 1, 'Login', NULL, '2026-04-25 04:47:29', '::1'),
(356, 1, 'Logout', NULL, '2026-04-25 04:54:08', '::1'),
(357, 1, 'Login', NULL, '2026-04-25 04:55:46', '::1'),
(358, 1, 'Logout', NULL, '2026-04-25 04:55:55', '::1'),
(359, 1, 'Login', NULL, '2026-04-25 05:24:16', '::1'),
(360, 1, 'Logout', NULL, '2026-04-25 05:25:15', '::1'),
(361, 8, 'Login', NULL, '2026-04-25 05:25:19', '::1'),
(362, 1, 'Login', NULL, '2026-04-27 10:37:03', '::1'),
(363, 1, 'Logout', NULL, '2026-04-27 11:13:27', '::1'),
(366, 8, 'Login', NULL, '2026-04-27 11:30:30', '::1'),
(367, 8, 'Logout', NULL, '2026-04-27 11:31:41', '::1'),
(368, 1, 'Login', NULL, '2026-04-27 11:31:46', '::1'),
(369, 1, 'Logout', NULL, '2026-04-27 11:33:09', '::1'),
(370, 8, 'Login', NULL, '2026-04-27 11:33:19', '::1'),
(371, 8, 'Logout', NULL, '2026-04-27 11:38:00', '::1'),
(372, 1, 'Login', NULL, '2026-04-27 11:38:05', '::1'),
(373, 1, 'Logout', NULL, '2026-04-27 11:38:25', '::1'),
(374, 8, 'Login', NULL, '2026-04-27 11:38:32', '::1'),
(375, 8, 'Logout', NULL, '2026-04-27 11:51:27', '::1'),
(376, 1, 'Login', NULL, '2026-04-27 11:51:32', '::1'),
(377, 1, 'Logout', NULL, '2026-04-27 11:55:30', '::1'),
(378, 8, 'Login', NULL, '2026-04-27 11:55:40', '::1'),
(379, 8, 'Logout', NULL, '2026-04-27 12:07:25', '::1'),
(382, 1, 'Login', NULL, '2026-04-27 12:14:41', '::1'),
(383, 1, 'Logout', NULL, '2026-04-27 12:15:44', '::1'),
(384, 8, 'Login', NULL, '2026-04-27 12:15:55', '::1'),
(385, 8, 'Logout', NULL, '2026-04-27 12:16:19', '::1'),
(386, 1, 'Login', NULL, '2026-04-27 13:30:06', '::1'),
(387, 1, 'Course Deleted', 'Deleted course ID: 27 - Title: asdadaw - Deleted 1 videos, 2 quizzes, 2 questions, 4 options', '2026-04-27 13:30:55', '::1'),
(388, 1, 'Course Deleted', 'Deleted course ID: 20 - Title: adadawda - Deleted 1 videos, 3 quizzes, 3 questions, 6 options', '2026-04-27 13:31:15', '::1'),
(389, 1, 'Course Deleted', 'Deleted course ID: 19 - Title: dadad dawdaw - Deleted 1 videos, 3 quizzes, 3 questions, 6 options', '2026-04-27 13:31:22', '::1'),
(390, 1, 'Course Deleted', 'Deleted course ID: 22 - Title: up - Deleted 1 videos, 3 quizzes, 3 questions, 6 options', '2026-04-27 13:31:28', '::1'),
(391, 1, 'Logout', NULL, '2026-04-27 13:31:29', '::1'),
(392, 8, 'Login', NULL, '2026-04-27 13:31:44', '::1'),
(393, 8, 'Logout', NULL, '2026-04-27 13:31:52', '::1'),
(396, 1, 'Login', NULL, '2026-04-27 13:32:51', '::1'),
(397, 1, 'Logout', NULL, '2026-04-27 14:19:52', '::1'),
(398, 9, 'Login', NULL, '2026-04-27 14:51:15', '::1'),
(399, 9, 'Logout', NULL, '2026-04-27 14:59:04', '::1'),
(400, 1, 'Login', NULL, '2026-04-27 14:59:30', '::1'),
(401, 1, 'Logout', NULL, '2026-04-27 15:34:04', '::1'),
(402, 1, 'Login', NULL, '2026-04-27 15:36:10', '::1'),
(403, 1, 'Login', NULL, '2026-04-27 16:13:41', '192.168.254.123'),
(404, 1, 'Logout', NULL, '2026-04-27 16:16:03', '192.168.254.123'),
(405, 8, 'Login', NULL, '2026-04-27 16:17:10', '192.168.254.123'),
(406, 8, 'Logout', NULL, '2026-04-27 16:21:46', '192.168.254.123'),
(409, 1, 'Login', NULL, '2026-04-27 16:24:18', '192.168.254.123'),
(410, 1, 'Logout', NULL, '2026-04-27 16:27:30', '192.168.254.123'),
(411, 8, 'Login', NULL, '2026-04-27 16:27:41', '192.168.254.123'),
(412, 8, 'Logout', NULL, '2026-04-27 16:29:48', '192.168.254.123'),
(413, 1, 'Login', NULL, '2026-04-27 16:30:09', '192.168.254.123'),
(414, 1, 'Logout', NULL, '2026-04-27 16:30:54', '192.168.254.123'),
(415, 1, 'Login', NULL, '2026-04-27 16:33:44', '192.168.254.123'),
(416, 1, 'Logout', NULL, '2026-04-27 16:34:56', '192.168.254.123'),
(417, 11, 'Login', NULL, '2026-04-27 16:35:03', '192.168.254.123'),
(418, 11, 'Password changed', '{\"changed\":\"password\"}', '2026-04-27 16:36:27', '192.168.254.123'),
(419, 11, 'Logout', NULL, '2026-04-27 16:36:32', '192.168.254.123'),
(420, 1, 'Login', NULL, '2026-04-27 16:36:43', '192.168.254.123'),
(421, 1, 'Logout', NULL, '2026-04-27 16:37:32', '192.168.254.123'),
(422, 11, 'Login', NULL, '2026-04-27 16:37:40', '192.168.254.123'),
(423, 11, 'Logout', NULL, '2026-04-27 16:38:12', '192.168.254.123'),
(424, 1, 'Login', NULL, '2026-04-27 16:38:24', '192.168.254.123'),
(425, 8, 'Login', NULL, '2026-04-27 19:30:22', '192.168.254.123'),
(426, 8, 'Login', NULL, '2026-04-27 19:33:02', '::1'),
(427, 8, 'Logout', NULL, '2026-04-27 19:40:59', '192.168.254.123'),
(428, 1, 'Login', NULL, '2026-04-27 19:41:09', '192.168.254.123'),
(429, 1, 'Logout', NULL, '2026-04-27 19:58:28', '192.168.254.123'),
(430, 8, 'Login', NULL, '2026-04-27 19:58:45', '192.168.254.123'),
(431, 8, 'Logout', NULL, '2026-04-27 19:59:59', '192.168.254.123'),
(432, 1, 'Login', NULL, '2026-04-27 20:00:12', '192.168.254.123'),
(433, 1, 'Logout', NULL, '2026-04-27 20:17:38', '192.168.254.123'),
(434, 8, 'Login', NULL, '2026-04-27 20:17:54', '192.168.254.123'),
(435, 8, 'Logout', NULL, '2026-04-27 20:18:01', '192.168.254.123'),
(436, 1, 'Login', NULL, '2026-04-27 20:18:04', '192.168.254.123'),
(437, 1, 'Logout', NULL, '2026-04-27 20:29:50', '192.168.254.123'),
(438, 1, 'Login', NULL, '2026-04-27 20:32:57', '192.168.254.123'),
(439, 1, 'Logout', NULL, '2026-04-27 20:52:29', '192.168.254.123'),
(442, 1, 'Login', NULL, '2026-04-27 20:56:07', '192.168.254.123'),
(444, 1, 'Login', NULL, '2026-04-27 20:57:02', '192.168.254.123'),
(447, 1, 'Login', NULL, '2026-04-27 20:59:18', '192.168.254.123'),
(450, 1, 'Login', NULL, '2026-04-27 21:00:41', '192.168.254.123'),
(451, 1, 'Logout', NULL, '2026-04-27 21:01:37', '192.168.254.123'),
(454, 1, 'Login', NULL, '2026-04-27 21:02:39', '192.168.254.123'),
(455, 1, 'Logout', NULL, '2026-04-27 21:02:47', '192.168.254.123'),
(458, 11, 'Login', NULL, '2026-04-27 21:04:59', '192.168.254.123'),
(459, 11, 'Logout', NULL, '2026-04-27 21:07:16', '192.168.254.123'),
(460, 1, 'Login', NULL, '2026-04-27 21:07:20', '192.168.254.123'),
(461, 11, 'Login', NULL, '2026-04-27 21:08:11', '192.168.254.123'),
(462, 11, 'Logout', NULL, '2026-04-27 21:08:29', '192.168.254.123'),
(463, 1, 'Login', NULL, '2026-04-27 21:08:32', '192.168.254.123'),
(464, 1, 'Login', NULL, '2026-04-27 21:43:58', '192.168.254.123'),
(465, 1, 'Login', NULL, '2026-04-28 20:01:55', '::1'),
(466, 1, 'Logout', NULL, '2026-04-28 20:02:21', '::1'),
(467, 13, 'Login', NULL, '2026-04-28 20:02:30', '::1'),
(468, 13, 'Logout', NULL, '2026-04-28 20:05:29', '::1'),
(469, 1, 'Login', NULL, '2026-04-28 20:05:36', '::1'),
(470, 1, 'Logout', NULL, '2026-04-28 20:06:38', '::1'),
(471, 1, 'Login', NULL, '2026-04-28 20:08:13', '::1'),
(472, 1, 'Logout', NULL, '2026-04-28 20:09:03', '::1'),
(473, 13, 'Login', NULL, '2026-04-28 20:09:08', '::1'),
(474, 13, 'Logout', NULL, '2026-04-28 20:10:24', '::1'),
(475, 8, 'Login', NULL, '2026-04-28 20:10:30', '::1'),
(476, 8, 'Logout', NULL, '2026-04-28 20:10:46', '::1'),
(477, 1, 'Login', NULL, '2026-04-28 20:11:51', '::1'),
(478, 1, 'Logout', NULL, '2026-04-28 20:12:05', '::1'),
(479, 1, 'Login', NULL, '2026-04-28 20:12:22', '::1'),
(480, 1, 'Logout', NULL, '2026-04-28 20:15:53', '::1'),
(481, 11, 'Login', NULL, '2026-04-28 20:16:04', '::1'),
(482, 11, 'Logout', NULL, '2026-04-28 20:17:42', '::1'),
(483, 13, 'Login', NULL, '2026-04-28 20:17:59', '::1'),
(484, 13, 'Logout', NULL, '2026-04-28 20:19:13', '::1'),
(485, 1, 'Login', NULL, '2026-04-28 20:19:18', '::1'),
(486, 1, 'Logout', NULL, '2026-04-28 20:25:35', '::1'),
(487, 1, 'Login', NULL, '2026-04-29 13:45:17', '::1'),
(488, 1, 'Logout', NULL, '2026-04-29 13:46:21', '::1'),
(489, 1, 'Login', NULL, '2026-04-29 13:47:04', '::1'),
(490, 1, 'Logout', NULL, '2026-04-29 13:57:31', '::1'),
(491, 8, 'Login', NULL, '2026-04-29 13:57:35', '::1'),
(492, 8, 'Logout', NULL, '2026-04-29 14:01:09', '::1'),
(493, 1, 'Login', NULL, '2026-04-29 14:01:14', '::1'),
(494, 1, 'Logout', NULL, '2026-04-29 14:17:49', '::1'),
(495, 13, 'Login', NULL, '2026-04-29 14:17:54', '::1'),
(496, 13, 'Logout', NULL, '2026-04-29 14:18:15', '::1'),
(497, 1, 'Login', NULL, '2026-04-29 14:18:22', '::1'),
(498, 1, 'Logout', NULL, '2026-04-29 14:18:27', '::1'),
(499, 8, 'Login', NULL, '2026-04-29 14:18:33', '::1'),
(500, 8, 'Logout', NULL, '2026-04-29 14:19:08', '::1'),
(501, 1, 'Login', NULL, '2026-04-29 14:19:13', '::1'),
(502, 1, 'Login', NULL, '2026-04-29 14:54:28', '::1'),
(503, 1, 'Logout', NULL, '2026-04-29 14:55:03', '::1'),
(504, 13, 'Login', NULL, '2026-04-29 14:55:09', '::1'),
(505, 1, 'Login', NULL, '2026-04-29 14:56:19', '::1'),
(506, 1, 'Login', NULL, '2026-04-29 14:58:19', '::1'),
(507, 1, 'Logout', NULL, '2026-04-29 15:14:31', '::1'),
(508, 8, 'Login', NULL, '2026-04-29 15:14:47', '::1'),
(509, 8, 'Logout', NULL, '2026-04-29 15:15:03', '::1'),
(510, 1, 'Login', NULL, '2026-04-29 15:17:55', '::1'),
(511, 1, 'Logout', NULL, '2026-04-29 15:18:33', '::1'),
(512, 15, 'Login', NULL, '2026-04-29 15:18:51', '::1'),
(513, 15, 'Logout', NULL, '2026-04-29 15:23:28', '::1'),
(514, 15, 'Login', NULL, '2026-04-29 15:23:31', '::1'),
(515, 15, 'Logout', NULL, '2026-04-29 15:25:27', '::1'),
(516, 1, 'Login', NULL, '2026-04-29 15:25:35', '::1'),
(517, 1, 'Logout', NULL, '2026-04-29 15:25:58', '::1'),
(518, 15, 'Login', NULL, '2026-04-29 15:26:07', '::1'),
(519, 15, 'Logout', NULL, '2026-04-29 15:26:22', '::1'),
(520, 1, 'Login', NULL, '2026-04-29 15:26:32', '::1'),
(521, 1, 'Logout', NULL, '2026-04-29 15:27:16', '::1'),
(522, 15, 'Login', NULL, '2026-04-29 15:27:23', '::1'),
(523, 1, 'Login', NULL, '2026-04-29 18:21:59', '::1'),
(524, 1, 'Login', NULL, '2026-04-29 20:00:45', '::1'),
(525, 1, 'Logout', NULL, '2026-04-29 20:00:55', '::1'),
(526, 13, 'Login', NULL, '2026-04-29 20:01:02', '::1'),
(527, 13, 'Logout', NULL, '2026-04-29 20:01:13', '::1'),
(528, 8, 'Login', NULL, '2026-04-29 20:01:31', '::1'),
(529, 8, 'Logout', NULL, '2026-04-29 20:03:01', '::1'),
(530, 1, 'Login', NULL, '2026-04-29 20:05:25', '::1'),
(531, 1, 'Logout', NULL, '2026-04-29 20:05:34', '::1'),
(532, 1, 'Login', NULL, '2026-04-29 20:05:42', '::1'),
(533, 1, 'Login', NULL, '2026-04-30 21:17:11', '::1'),
(534, 1, 'Logout', NULL, '2026-04-30 21:20:08', '::1'),
(535, 8, 'Login', NULL, '2026-04-30 21:20:20', '::1'),
(536, 8, 'Logout', NULL, '2026-04-30 21:21:45', '::1'),
(537, 1, 'Login', NULL, '2026-04-30 21:45:34', '::1'),
(538, 1, 'Logout', NULL, '2026-04-30 22:18:00', '::1'),
(539, 1, 'Login', NULL, '2026-04-30 22:19:20', '::1'),
(540, 1, 'Logout', NULL, '2026-04-30 23:03:30', '::1'),
(541, 8, 'Login', NULL, '2026-04-30 23:03:39', '::1'),
(542, 8, 'Logout', NULL, '2026-05-01 00:11:34', '::1'),
(543, 1, 'Login', NULL, '2026-05-01 00:11:41', '::1'),
(544, 1, 'Logout', NULL, '2026-05-01 00:14:33', '::1'),
(545, 8, 'Login', NULL, '2026-05-01 00:14:48', '::1'),
(546, 8, 'Logout', NULL, '2026-05-01 00:15:15', '::1'),
(547, 1, 'Login', NULL, '2026-05-01 00:15:24', '::1'),
(548, 1, 'Logout', NULL, '2026-05-01 00:58:00', '::1'),
(549, 1, 'Login', NULL, '2026-05-01 01:00:45', '::1'),
(550, 1, 'Logout', NULL, '2026-05-01 01:08:43', '::1'),
(551, 1, 'Login', NULL, '2026-05-01 01:08:52', '::1'),
(552, 1, 'Logout', NULL, '2026-05-01 01:09:19', '::1'),
(553, 1, 'Login', NULL, '2026-05-01 01:09:27', '::1'),
(554, 1, 'Logout', NULL, '2026-05-01 01:09:40', '::1'),
(555, 1, 'Login', NULL, '2026-05-01 01:10:50', '::1'),
(556, 1, 'Logout', NULL, '2026-05-01 01:20:18', '::1'),
(557, 1, 'Login', NULL, '2026-05-01 01:21:29', '::1'),
(558, 1, 'Login', NULL, '2026-05-01 01:53:34', '::1'),
(559, 1, 'Login', NULL, '2026-05-04 14:13:46', '::1'),
(560, 1, 'Logout', NULL, '2026-05-04 14:18:49', '::1'),
(561, 8, 'Login', NULL, '2026-05-04 14:18:58', '::1'),
(562, 8, 'Logout', NULL, '2026-05-04 14:19:11', '::1'),
(563, 1, 'Login', NULL, '2026-05-04 14:19:15', '::1'),
(564, 1, 'Logout', NULL, '2026-05-04 14:19:22', '::1'),
(565, 8, 'Login', NULL, '2026-05-04 14:19:34', '::1'),
(566, 8, 'Logout', NULL, '2026-05-04 14:20:21', '::1'),
(567, 1, 'Login', NULL, '2026-05-04 14:20:29', '::1'),
(568, 1, 'Logout', NULL, '2026-05-04 14:49:59', '::1'),
(569, 8, 'Login', NULL, '2026-05-04 14:50:11', '::1'),
(570, 8, 'Logout', NULL, '2026-05-04 15:02:10', '::1'),
(571, 8, 'Login', NULL, '2026-05-04 15:23:24', '::1'),
(572, 8, 'Logout', NULL, '2026-05-04 17:16:31', '::1'),
(573, 1, 'Login', NULL, '2026-05-04 17:16:41', '::1'),
(574, 1, 'Login', NULL, '2026-05-04 17:20:45', '::1'),
(575, 1, 'Login', NULL, '2026-05-04 20:18:21', '::1'),
(576, 1, 'Logout', NULL, '2026-05-04 20:20:11', '::1'),
(577, 11, 'Login', NULL, '2026-05-04 20:20:22', '::1'),
(578, 11, 'Logout', NULL, '2026-05-04 20:23:18', '::1'),
(579, 1, 'Login', NULL, '2026-05-04 20:23:24', '::1'),
(580, 1, 'Logout', NULL, '2026-05-04 20:23:40', '::1'),
(581, 11, 'Login', NULL, '2026-05-04 20:23:47', '::1'),
(582, 11, 'Logout', NULL, '2026-05-04 20:24:42', '::1'),
(583, 1, 'Login', NULL, '2026-05-04 20:24:50', '::1'),
(584, 1, 'Logout', NULL, '2026-05-04 20:25:19', '::1'),
(585, 8, 'Login', NULL, '2026-05-04 20:25:24', '::1'),
(586, 8, 'Logout', NULL, '2026-05-04 20:25:35', '::1'),
(587, 1, 'Login', NULL, '2026-05-04 20:25:46', '::1'),
(588, 1, 'Logout', NULL, '2026-05-04 20:26:06', '::1'),
(589, 8, 'Login', NULL, '2026-05-04 20:26:16', '::1'),
(590, 8, 'Logout', NULL, '2026-05-04 20:26:44', '::1'),
(591, 1, 'Login', NULL, '2026-05-04 20:26:53', '::1'),
(592, 1, 'Logout', NULL, '2026-05-04 20:31:35', '::1'),
(593, 8, 'Login', NULL, '2026-05-04 20:31:45', '::1'),
(594, 8, 'Logout', NULL, '2026-05-04 20:33:26', '::1'),
(595, 1, 'Login', NULL, '2026-05-04 20:33:39', '::1'),
(596, 1, 'Logout', NULL, '2026-05-04 20:34:11', '::1'),
(597, 8, 'Login', NULL, '2026-05-04 20:34:21', '::1'),
(601, 1, 'Login', NULL, '2026-05-04 20:40:06', '::1'),
(602, 1, 'Login', NULL, '2026-05-05 16:08:36', '::1'),
(603, 1, 'Logout', NULL, '2026-05-05 16:12:49', '::1'),
(604, 1, 'Login', NULL, '2026-05-05 16:42:58', '::1'),
(605, 1, 'Logout', NULL, '2026-05-05 16:59:05', '::1'),
(606, 1, 'Login', NULL, '2026-05-05 17:01:42', '::1'),
(607, 1, 'Logout', NULL, '2026-05-05 17:02:43', '::1'),
(608, 11, 'Login', NULL, '2026-05-05 17:02:56', '::1'),
(609, 11, 'Logout', NULL, '2026-05-05 17:03:20', '::1'),
(610, 1, 'Login', NULL, '2026-05-05 17:03:23', '::1'),
(611, 1, 'Logout', NULL, '2026-05-05 17:04:17', '::1'),
(612, 11, 'Login', NULL, '2026-05-05 17:04:20', '::1'),
(613, 11, 'Logout', NULL, '2026-05-05 17:09:21', '::1'),
(614, 1, 'Login', NULL, '2026-05-05 17:09:28', '::1'),
(615, 1, 'Logout', NULL, '2026-05-05 17:14:09', '::1'),
(616, 11, 'Login', NULL, '2026-05-05 17:14:17', '::1'),
(617, 11, 'Logout', NULL, '2026-05-05 17:16:52', '::1'),
(618, 1, 'Login', NULL, '2026-05-05 17:16:56', '::1'),
(619, 1, 'Logout', NULL, '2026-05-05 17:23:05', '::1'),
(620, 11, 'Login', NULL, '2026-05-05 17:23:08', '::1'),
(621, 11, 'Logout', NULL, '2026-05-05 17:23:33', '::1'),
(622, 1, 'Login', NULL, '2026-05-05 17:23:37', '::1'),
(623, 1, 'Logout', NULL, '2026-05-05 17:24:42', '::1'),
(624, 11, 'Login', NULL, '2026-05-05 17:24:45', '::1'),
(625, 11, 'Logout', NULL, '2026-05-05 17:26:13', '::1'),
(626, 1, 'Login', NULL, '2026-05-06 14:58:59', '::1'),
(627, 1, 'Login', NULL, '2026-05-06 15:23:39', '::1'),
(628, 1, 'Logout', NULL, '2026-05-06 15:24:10', '::1'),
(629, 8, 'Login', NULL, '2026-05-06 15:24:28', '::1'),
(630, 8, 'Logout', NULL, '2026-05-06 15:24:36', '::1'),
(631, 11, 'Login', NULL, '2026-05-06 15:28:04', '::1'),
(632, 11, 'Logout', NULL, '2026-05-06 15:28:59', '::1'),
(633, 8, 'Login', NULL, '2026-05-06 15:32:13', '::1'),
(634, 8, 'Course Deleted', 'Deleted course ID: 29 - Title: How to send & reply to emails - Deleted 1 videos, 10 quizzes, 10 questions, 38 options', '2026-05-06 15:36:14', '::1'),
(635, 8, 'Course Deleted', 'Deleted course ID: 28 - Title: How to use QuickBooks Online - Deleted 1 videos, 10 quizzes, 10 questions, 40 options', '2026-05-06 15:36:23', '::1'),
(636, 8, 'Course Deleted', 'Deleted course ID: 33 - Title: canva basic - Deleted 1 videos, 2 quizzes, 2 questions, 4 options', '2026-05-06 15:36:31', '::1'),
(637, 8, 'Course Deleted', 'Deleted course ID: 34 - Title:  - Deleted 0 videos, 0 quizzes, 0 questions, 0 options', '2026-05-06 16:26:48', '::1'),
(638, 8, 'Course Deleted', 'Deleted course ID: 32 - Title: Canva basics - Deleted 1 videos, 1 quizzes, 1 questions, 2 options', '2026-05-06 16:26:52', '::1'),
(639, 8, 'Course Deleted', 'Deleted course ID: 31 - Title: 0xro - Deleted 1 videos, 3 quizzes, 3 questions, 6 options', '2026-05-06 16:27:06', '::1'),
(640, 8, 'Course Deleted', 'Deleted course ID: 30 - Title: dadawda - Deleted 1 videos, 3 quizzes, 3 questions, 6 options', '2026-05-06 16:27:11', '::1'),
(641, 1, 'Login', NULL, '2026-05-08 13:31:43', '::1'),
(642, 1, 'Logout', NULL, '2026-05-08 13:32:35', '::1'),
(643, 8, 'Login', NULL, '2026-05-08 13:32:50', '::1'),
(644, 8, 'Logout', NULL, '2026-05-08 13:32:55', '::1'),
(645, 11, 'Login', NULL, '2026-05-08 13:33:09', '::1'),
(646, 11, 'Logout', NULL, '2026-05-08 13:37:03', '::1'),
(647, 1, 'Login', NULL, '2026-05-08 13:37:08', '::1'),
(648, 1, 'Login', NULL, '2026-05-08 14:15:42', '::1'),
(649, 11, 'Login', NULL, '2026-05-08 14:45:50', '::1'),
(650, 11, 'Logout', NULL, '2026-05-08 14:54:21', '::1'),
(651, 1, 'Login', NULL, '2026-05-08 14:54:31', '::1'),
(652, 1, 'Logout', NULL, '2026-05-08 15:06:10', '::1'),
(653, 1, 'Login', NULL, '2026-05-08 15:26:26', '::1'),
(654, 1, 'Logout', NULL, '2026-05-08 15:29:00', '::1'),
(655, 11, 'Login', NULL, '2026-05-08 15:29:06', '::1'),
(656, 11, 'Logout', NULL, '2026-05-08 15:29:32', '::1'),
(657, 1, 'Login', NULL, '2026-05-08 15:29:36', '::1'),
(658, 1, 'Logout', NULL, '2026-05-08 15:30:24', '::1'),
(659, 11, 'Login', NULL, '2026-05-08 15:30:29', '::1'),
(660, 11, 'Logout', NULL, '2026-05-08 15:41:06', '::1'),
(661, 1, 'Login', NULL, '2026-05-08 15:41:11', '::1'),
(662, 1, 'Logout', NULL, '2026-05-08 15:42:48', '::1'),
(663, 11, 'Login', NULL, '2026-05-08 15:42:51', '::1'),
(664, 11, 'Logout', NULL, '2026-05-08 15:43:39', '::1'),
(665, 1, 'Login', NULL, '2026-05-08 15:43:45', '::1'),
(666, 1, 'Logout', NULL, '2026-05-08 15:44:01', '::1'),
(667, 11, 'Login', NULL, '2026-05-08 15:44:05', '::1'),
(668, 11, 'Logout', NULL, '2026-05-08 15:44:49', '::1'),
(669, 1, 'Login', NULL, '2026-05-08 15:44:52', '::1'),
(670, 1, 'Logout', NULL, '2026-05-08 15:46:27', '::1'),
(671, 11, 'Login', NULL, '2026-05-08 15:46:31', '::1'),
(672, 11, 'Logout', NULL, '2026-05-08 15:47:48', '::1'),
(673, 1, 'Login', NULL, '2026-05-08 15:47:51', '::1'),
(674, 1, 'Logout', NULL, '2026-05-08 15:56:07', '::1'),
(675, 1, 'Login', NULL, '2026-05-08 16:20:25', '::1'),
(676, 1, 'Logout', NULL, '2026-05-08 16:22:17', '::1'),
(677, 11, 'Login', NULL, '2026-05-08 16:22:21', '::1'),
(678, 11, 'Logout', NULL, '2026-05-08 16:27:06', '::1'),
(679, 1, 'Login', NULL, '2026-05-08 17:37:24', '::1'),
(680, 1, 'Course Deleted', 'Deleted course ID: 45 - Title: test course - Deleted 0 videos, 0 quizzes, 0 questions, 0 options', '2026-05-08 18:51:14', '::1'),
(681, 1, 'Logout', NULL, '2026-05-08 18:51:59', '::1'),
(682, 1, 'Login', NULL, '2026-05-08 18:52:03', '::1'),
(683, 1, 'Login', NULL, '2026-05-10 18:17:47', '::1'),
(684, 1, 'Logout', NULL, '2026-05-10 18:19:13', '::1'),
(685, 11, 'Login', NULL, '2026-05-10 18:19:24', '::1'),
(686, 1, 'Login', NULL, '2026-05-11 13:00:44', '::1'),
(687, 1, 'Logout', NULL, '2026-05-11 13:05:09', '::1'),
(688, 11, 'Login', NULL, '2026-05-11 13:05:15', '::1'),
(689, 11, 'Logout', NULL, '2026-05-11 13:05:40', '::1'),
(690, 1, 'Login', NULL, '2026-05-11 13:06:01', '::1'),
(691, 1, 'Logout', NULL, '2026-05-11 13:13:52', '::1'),
(692, 11, 'Login', NULL, '2026-05-11 13:14:06', '::1'),
(693, 11, 'Logout', NULL, '2026-05-11 13:16:13', '::1'),
(694, 1, 'Login', NULL, '2026-05-11 13:16:18', '::1'),
(695, 1, 'Logout', NULL, '2026-05-11 13:29:58', '::1'),
(696, 1, 'Login', NULL, '2026-05-11 13:42:04', '::1'),
(697, 1, 'Logout', NULL, '2026-05-11 14:14:04', '::1'),
(698, 11, 'Login', NULL, '2026-05-11 14:14:07', '::1'),
(699, 11, 'Logout', NULL, '2026-05-11 16:54:50', '::1'),
(700, 15, 'Login', NULL, '2026-05-11 16:54:59', '::1'),
(701, 15, 'Logout', NULL, '2026-05-11 16:55:10', '::1'),
(702, 11, 'Login', NULL, '2026-05-11 16:55:14', '::1'),
(703, 11, 'Logout', NULL, '2026-05-11 16:57:58', '::1'),
(704, 15, 'Login', NULL, '2026-05-11 16:58:00', '::1'),
(705, 15, 'Logout', NULL, '2026-05-11 16:58:10', '::1'),
(706, 1, 'Login', NULL, '2026-05-11 16:58:20', '::1'),
(707, 1, 'Logout', NULL, '2026-05-11 16:58:25', '::1'),
(708, 11, 'Login', NULL, '2026-05-11 16:58:29', '::1'),
(709, 11, 'Login', NULL, '2026-05-11 23:13:19', '::1'),
(710, 11, 'Logout', NULL, '2026-05-11 23:39:01', '::1'),
(711, 15, 'Login', NULL, '2026-05-11 23:39:04', '::1'),
(712, 15, 'Logout', NULL, '2026-05-11 23:39:12', '::1'),
(713, 11, 'Login', NULL, '2026-05-11 23:39:15', '::1'),
(714, 11, 'Logout', NULL, '2026-05-12 00:09:02', '::1'),
(715, 1, 'Login', NULL, '2026-05-12 00:09:05', '::1'),
(716, 1, 'Course Deleted', 'Deleted course ID: 35 - Title: QuickBooks - Deleted 1 videos, 10 quizzes, 10 questions, 40 options', '2026-05-12 00:09:18', '::1'),
(717, 11, 'Login', NULL, '2026-05-12 00:09:34', '::1'),
(718, 11, 'Logout', NULL, '2026-05-12 00:53:48', '::1'),
(719, 1, 'Login', NULL, '2026-05-12 00:53:52', '::1'),
(720, 1, 'Logout', NULL, '2026-05-12 00:54:31', '::1'),
(721, 11, 'Login', NULL, '2026-05-12 00:54:34', '::1'),
(722, 11, 'Logout', NULL, '2026-05-12 00:55:16', '::1'),
(723, 1, 'Login', NULL, '2026-05-12 00:55:19', '::1'),
(724, 1, 'Logout', NULL, '2026-05-12 00:55:31', '::1'),
(725, 11, 'Login', NULL, '2026-05-12 00:55:34', '::1'),
(726, 11, 'Logout', NULL, '2026-05-12 01:11:34', '::1'),
(727, 1, 'Login', NULL, '2026-05-12 01:11:37', '::1'),
(728, 1, 'Logout', NULL, '2026-05-12 01:12:11', '::1'),
(729, 1, 'Login', NULL, '2026-05-12 01:12:15', '::1'),
(730, 1, 'Logout', NULL, '2026-05-12 01:12:16', '::1'),
(731, 11, 'Login', NULL, '2026-05-12 01:12:19', '::1'),
(732, 11, 'Logout', NULL, '2026-05-12 01:18:19', '::1'),
(733, 15, 'Login', NULL, '2026-05-12 01:18:23', '::1'),
(734, 15, 'Logout', NULL, '2026-05-12 01:27:29', '::1'),
(735, 11, 'Login', NULL, '2026-05-12 01:27:32', '::1'),
(736, 11, 'Logout', NULL, '2026-05-12 01:36:46', '::1'),
(737, 1, 'Login', NULL, '2026-05-12 01:36:50', '::1'),
(738, 1, 'Logout', NULL, '2026-05-12 01:37:06', '::1'),
(739, 11, 'Login', NULL, '2026-05-12 01:37:09', '::1'),
(740, 11, 'Logout', NULL, '2026-05-12 01:41:49', '::1'),
(741, 15, 'Login', NULL, '2026-05-12 01:41:52', '::1'),
(742, 15, 'Logout', NULL, '2026-05-12 01:43:48', '::1'),
(743, 11, 'Login', NULL, '2026-05-12 01:43:53', '::1'),
(744, 11, 'Logout', NULL, '2026-05-12 02:24:40', '::1'),
(745, 1, 'Login', NULL, '2026-05-12 02:24:43', '::1'),
(746, 1, 'Logout', NULL, '2026-05-12 02:24:53', '::1'),
(747, 11, 'Login', NULL, '2026-05-12 02:24:59', '::1'),
(748, 11, 'Logout', NULL, '2026-05-12 02:35:40', '::1'),
(749, 1, 'Login', NULL, '2026-05-12 02:35:43', '::1'),
(750, 1, 'Logout', NULL, '2026-05-12 02:35:53', '::1'),
(751, 11, 'Login', NULL, '2026-05-12 02:35:56', '::1'),
(752, 11, 'Logout', NULL, '2026-05-12 02:41:47', '::1'),
(753, 1, 'Login', NULL, '2026-05-12 02:41:50', '::1'),
(754, 1, 'Logout', NULL, '2026-05-12 02:41:58', '::1'),
(755, 11, 'Login', NULL, '2026-05-12 02:42:01', '::1'),
(756, 11, 'Logout', NULL, '2026-05-12 02:50:16', '::1'),
(757, 1, 'Login', NULL, '2026-05-12 02:50:19', '::1'),
(758, 1, 'Logout', NULL, '2026-05-12 02:50:31', '::1'),
(759, 11, 'Login', NULL, '2026-05-12 02:50:33', '::1'),
(760, 11, 'Logout', NULL, '2026-05-12 03:00:25', '::1'),
(761, 1, 'Login', NULL, '2026-05-12 03:00:28', '::1'),
(762, 1, 'Logout', NULL, '2026-05-12 03:00:41', '::1'),
(763, 11, 'Login', NULL, '2026-05-12 03:00:44', '::1'),
(764, 11, 'Login', NULL, '2026-05-12 03:01:53', '::1'),
(765, 11, 'Logout', NULL, '2026-05-12 03:11:12', '::1'),
(766, 15, 'Login', NULL, '2026-05-12 03:11:15', '::1'),
(767, 15, 'Logout', NULL, '2026-05-12 03:12:39', '::1'),
(768, 1, 'Login', NULL, '2026-05-12 03:12:43', '::1'),
(769, 1, 'Logout', NULL, '2026-05-12 03:12:52', '::1'),
(770, 15, 'Login', NULL, '2026-05-12 03:12:54', '::1'),
(771, 15, 'Logout', NULL, '2026-05-12 03:16:49', '::1'),
(772, 11, 'Login', NULL, '2026-05-12 03:16:54', '::1'),
(773, 11, 'Logout', NULL, '2026-05-12 03:17:08', '::1'),
(774, 1, 'Login', NULL, '2026-05-12 03:17:11', '::1'),
(775, 1, 'Logout', NULL, '2026-05-12 03:17:20', '::1'),
(776, 11, 'Login', NULL, '2026-05-12 03:17:23', '::1'),
(777, 11, 'Logout', NULL, '2026-05-12 03:18:10', '::1'),
(778, 15, 'Login', NULL, '2026-05-12 03:18:13', '::1'),
(779, 15, 'Logout', NULL, '2026-05-12 03:24:48', '::1'),
(780, 11, 'Login', NULL, '2026-05-12 03:24:51', '::1'),
(781, 11, 'Logout', NULL, '2026-05-12 03:31:11', '::1'),
(782, 1, 'Login', NULL, '2026-05-12 03:31:14', '::1'),
(783, 1, 'Logout', NULL, '2026-05-12 03:31:32', '::1'),
(784, 11, 'Login', NULL, '2026-05-12 03:31:34', '::1'),
(785, 11, 'Logout', NULL, '2026-05-12 03:32:31', '::1'),
(786, 1, 'Login', NULL, '2026-05-12 03:32:33', '::1'),
(787, 1, 'Logout', NULL, '2026-05-12 03:34:42', '::1'),
(788, 11, 'Login', NULL, '2026-05-12 03:34:45', '::1'),
(789, 11, 'Logout', NULL, '2026-05-12 06:31:52', '::1'),
(790, 15, 'Login', NULL, '2026-05-12 06:31:59', '::1'),
(791, 15, 'Logout', NULL, '2026-05-12 06:33:23', '::1'),
(792, 11, 'Login', NULL, '2026-05-12 06:33:26', '::1'),
(793, 11, 'Logout', NULL, '2026-05-12 06:33:55', '::1'),
(794, 1, 'Login', NULL, '2026-05-12 06:34:04', '::1'),
(795, 1, 'Logout', NULL, '2026-05-12 06:34:14', '::1'),
(796, 11, 'Login', NULL, '2026-05-12 06:34:20', '::1'),
(797, 11, 'Logout', NULL, '2026-05-12 06:40:05', '::1'),
(798, 1, 'Login', NULL, '2026-05-12 06:40:08', '::1'),
(799, 1, 'Logout', NULL, '2026-05-12 06:40:17', '::1'),
(800, 11, 'Login', NULL, '2026-05-12 06:40:20', '::1'),
(801, 11, 'Logout', NULL, '2026-05-12 06:52:44', '::1'),
(802, 15, 'Login', NULL, '2026-05-12 06:52:47', '::1'),
(803, 15, 'Logout', NULL, '2026-05-12 06:53:12', '::1'),
(804, 1, 'Login', NULL, '2026-05-12 06:53:15', '::1'),
(805, 1, 'Logout', NULL, '2026-05-12 06:53:18', '::1'),
(806, 11, 'Login', NULL, '2026-05-12 06:53:23', '::1'),
(807, 11, 'Logout', NULL, '2026-05-12 07:07:10', '::1'),
(808, 1, 'Login', NULL, '2026-05-12 07:07:13', '::1'),
(809, 1, 'Logout', NULL, '2026-05-12 07:07:18', '::1'),
(810, 15, 'Login', NULL, '2026-05-12 07:07:21', '::1'),
(811, 15, 'Logout', NULL, '2026-05-12 07:08:07', '::1'),
(812, 1, 'Login', NULL, '2026-05-12 07:08:10', '::1'),
(813, 1, 'Logout', NULL, '2026-05-12 07:08:13', '::1'),
(814, 11, 'Login', NULL, '2026-05-12 07:08:17', '::1'),
(815, 11, 'Logout', NULL, '2026-05-12 07:19:38', '::1'),
(816, 15, 'Login', NULL, '2026-05-12 07:19:42', '::1'),
(817, 15, 'Logout', NULL, '2026-05-12 07:22:47', '::1'),
(818, 11, 'Login', NULL, '2026-05-12 07:22:51', '::1'),
(819, 11, 'Logout', NULL, '2026-05-12 08:15:21', '::1'),
(820, 15, 'Login', NULL, '2026-05-12 08:15:24', '::1'),
(821, 15, 'Logout', NULL, '2026-05-12 08:18:00', '::1'),
(822, 1, 'Login', NULL, '2026-05-12 08:18:04', '::1'),
(823, 1, 'Logout', NULL, '2026-05-12 08:18:22', '::1'),
(824, 15, 'Login', NULL, '2026-05-12 08:18:27', '::1'),
(825, 15, 'Logout', NULL, '2026-05-12 08:22:27', '::1'),
(826, 11, 'Login', NULL, '2026-05-12 08:22:33', '::1'),
(827, 11, 'Logout', NULL, '2026-05-12 08:23:40', '::1'),
(828, 15, 'Login', NULL, '2026-05-12 08:23:45', '::1'),
(829, 15, 'Logout', NULL, '2026-05-12 08:24:02', '::1'),
(830, 1, 'Login', NULL, '2026-05-12 08:24:05', '::1'),
(831, 1, 'Logout', NULL, '2026-05-12 09:06:05', '::1'),
(832, 15, 'Login', NULL, '2026-05-12 09:06:09', '::1'),
(833, 15, 'Logout', NULL, '2026-05-12 09:07:05', '::1'),
(834, 1, 'Login', NULL, '2026-05-12 09:07:08', '::1'),
(835, 1, 'Logout', NULL, '2026-05-12 09:11:18', '::1'),
(836, 15, 'Login', NULL, '2026-05-12 09:11:21', '::1'),
(837, 15, 'Logout', NULL, '2026-05-12 09:13:11', '::1'),
(838, 1, 'Login', NULL, '2026-05-12 09:13:14', '::1'),
(839, 15, 'Login', NULL, '2026-05-12 09:13:48', '::1'),
(840, 15, 'Logout', NULL, '2026-05-12 09:16:40', '::1'),
(841, 11, 'Login', NULL, '2026-05-12 09:16:49', '::1'),
(842, 1, 'Login', NULL, '2026-05-12 09:40:11', '::1'),
(843, 1, 'Logout', NULL, '2026-05-12 09:52:50', '::1'),
(844, 15, 'Login', NULL, '2026-05-12 09:52:54', '::1'),
(845, 15, 'Logout', NULL, '2026-05-12 09:52:59', '::1'),
(846, 15, 'Login', NULL, '2026-05-12 09:53:03', '::1'),
(847, 15, 'Logout', NULL, '2026-05-12 09:53:05', '::1'),
(848, 11, 'Login', NULL, '2026-05-12 09:53:08', '::1'),
(849, 11, 'Logout', NULL, '2026-05-12 09:53:10', '::1'),
(850, 11, 'Login', NULL, '2026-05-12 09:53:13', '::1'),
(851, 11, 'Logout', NULL, '2026-05-12 09:59:20', '::1'),
(852, 15, 'Login', NULL, '2026-05-12 09:59:23', '::1'),
(853, 15, 'Logout', NULL, '2026-05-12 10:00:12', '::1'),
(854, 11, 'Login', NULL, '2026-05-12 10:00:15', '::1'),
(855, 11, 'Logout', NULL, '2026-05-12 10:08:37', '::1'),
(856, 1, 'Login', NULL, '2026-05-12 10:08:43', '::1'),
(857, 1, 'Logout', NULL, '2026-05-12 10:09:35', '::1'),
(858, 11, 'Login', NULL, '2026-05-12 10:09:38', '::1'),
(859, 11, 'Logout', NULL, '2026-05-12 10:09:48', '::1'),
(860, 1, 'Login', NULL, '2026-05-12 10:09:50', '::1'),
(861, 1, 'Logout', NULL, '2026-05-12 10:14:32', '::1'),
(862, 11, 'Login', NULL, '2026-05-12 10:14:35', '::1'),
(863, 11, 'Logout', NULL, '2026-05-12 10:15:08', '::1'),
(864, 1, 'Login', NULL, '2026-05-12 10:15:11', '::1'),
(865, 1, 'Logout', NULL, '2026-05-12 10:27:19', '::1'),
(866, 11, 'Login', NULL, '2026-05-12 10:27:22', '::1'),
(867, 11, 'Logout', NULL, '2026-05-12 10:30:09', '::1'),
(868, 1, 'Login', NULL, '2026-05-12 10:30:12', '::1'),
(869, 1, 'Logout', NULL, '2026-05-12 10:30:28', '::1'),
(870, 11, 'Login', NULL, '2026-05-12 10:30:31', '::1'),
(871, 11, 'Logout', NULL, '2026-05-12 10:33:07', '::1'),
(872, 1, 'Login', NULL, '2026-05-12 10:33:10', '::1'),
(873, 1, 'Logout', NULL, '2026-05-12 10:34:20', '::1'),
(874, 11, 'Login', NULL, '2026-05-12 10:34:23', '::1'),
(875, 11, 'Logout', NULL, '2026-05-12 10:34:30', '::1'),
(876, 1, 'Login', NULL, '2026-05-12 10:34:34', '::1'),
(877, 1, 'Logout', NULL, '2026-05-12 12:01:48', '::1'),
(878, 1, 'Login', NULL, '2026-05-12 12:04:19', '::1'),
(879, 1, 'Logout', NULL, '2026-05-12 12:10:50', '::1'),
(880, 11, 'Login', NULL, '2026-05-12 12:10:57', '::1'),
(881, 11, 'Logout', NULL, '2026-05-12 12:11:44', '::1'),
(882, 1, 'Login', NULL, '2026-05-12 12:11:51', '::1'),
(883, 1, 'Logout', NULL, '2026-05-12 12:14:42', '::1'),
(884, 1, 'Login', NULL, '2026-05-12 12:17:40', '::1'),
(885, 1, 'Logout', NULL, '2026-05-12 12:19:04', '::1'),
(886, 21, 'Login', NULL, '2026-05-12 12:20:34', '::1'),
(887, 21, 'Logout', NULL, '2026-05-12 12:25:10', '::1'),
(888, 1, 'Login', NULL, '2026-05-12 12:25:37', '::1'),
(889, 1, 'Logout', NULL, '2026-05-12 12:26:59', '::1'),
(890, 21, 'Login', NULL, '2026-05-12 12:27:03', '::1'),
(891, 21, 'Logout', NULL, '2026-05-12 12:32:28', '::1'),
(892, 11, 'Login', NULL, '2026-05-12 12:32:32', '::1'),
(893, 11, 'Logout', NULL, '2026-05-12 12:37:03', '::1'),
(894, 21, 'Login', NULL, '2026-05-12 12:49:53', '::1'),
(895, 1, 'Login', NULL, '2026-05-12 23:08:33', '::1'),
(896, 1, 'Logout', NULL, '2026-05-12 23:09:35', '::1'),
(897, 21, 'Login', NULL, '2026-05-12 23:09:38', '::1'),
(898, 21, 'Logout', NULL, '2026-05-12 23:27:31', '::1'),
(899, 1, 'Login', NULL, '2026-05-12 23:27:42', '::1'),
(900, 1, 'Login', NULL, '2026-05-20 23:42:45', '::1'),
(901, 1, 'Course Deleted', 'Deleted course ID: 49 - Title: for beginner - Deleted 0 videos, 0 quizzes, 0 questions, 0 options', '2026-05-20 23:43:27', '::1'),
(902, 1, 'Logout', NULL, '2026-05-20 23:43:59', '::1'),
(903, 11, 'Login', NULL, '2026-05-20 23:44:03', '::1'),
(904, 11, 'Logout', NULL, '2026-05-20 23:46:15', '::1'),
(905, 1, 'Login', NULL, '2026-05-20 23:46:18', '::1'),
(906, 1, 'Login', NULL, '2026-05-24 13:20:23', '::1'),
(907, 1, 'Logout', NULL, '2026-05-24 13:20:59', '::1'),
(908, 11, 'Login', NULL, '2026-05-24 13:21:01', '::1'),
(909, 11, 'Logout', NULL, '2026-05-24 13:21:14', '::1'),
(910, 1, 'Login', NULL, '2026-05-24 13:21:17', '::1'),
(911, 1, 'Login', NULL, '2026-05-28 16:26:53', '::1'),
(912, 1, 'Logout', NULL, '2026-05-28 16:29:00', '::1'),
(913, 11, 'Login', NULL, '2026-05-28 16:29:07', '::1'),
(914, 1, 'Login', NULL, '2026-06-02 23:31:34', '::1'),
(915, 1, 'Logout', NULL, '2026-06-03 00:19:27', '::1'),
(916, 13, 'Login', NULL, '2026-06-03 00:19:32', '::1'),
(917, 13, 'Logout', NULL, '2026-06-03 00:21:19', '::1'),
(918, 1, 'Login', NULL, '2026-06-03 00:21:23', '::1'),
(919, 1, 'Logout', NULL, '2026-06-03 00:34:03', '::1'),
(920, 13, 'Login', NULL, '2026-06-03 00:39:57', '::1'),
(921, 13, 'Logout', NULL, '2026-06-03 00:44:41', '::1'),
(922, 1, 'Login', NULL, '2026-06-03 00:45:08', '::1'),
(923, 1, 'Logout', NULL, '2026-06-03 01:00:12', '::1'),
(924, 11, 'Login', NULL, '2026-06-03 01:00:19', '::1'),
(925, 11, 'Logout', NULL, '2026-06-03 01:00:41', '::1'),
(926, 1, 'Login', NULL, '2026-06-03 01:00:44', '::1'),
(927, 1, 'Course Deleted', 'Deleted course ID: 50 - Title: DRAWDADAD - Deleted 0 videos, 0 quizzes, 0 questions, 0 options', '2026-06-03 01:01:33', '::1'),
(928, 1, 'Course Deleted', 'Deleted course ID: 48 - Title: for beginner - Deleted 0 videos, 0 quizzes, 0 questions, 0 options', '2026-06-03 01:01:39', '::1'),
(929, 1, 'Course Deleted', 'Deleted course ID: 47 - Title: for beginner - Deleted 0 videos, 0 quizzes, 0 questions, 0 options', '2026-06-03 01:01:46', '::1'),
(930, 1, 'Course Deleted', 'Deleted course ID: 46 - Title: Professional Communication Skills [BUSINESS COMMUNICATION PRO] - Deleted 0 videos, 0 quizzes, 0 questions, 0 options', '2026-06-03 01:01:51', '::1'),
(931, 1, 'Login', NULL, '2026-06-03 13:32:44', '::1'),
(932, 1, 'Login', NULL, '2026-06-04 16:23:06', '::1'),
(933, 1, 'Logout', NULL, '2026-06-04 16:24:36', '::1'),
(934, 11, 'Login', NULL, '2026-06-04 16:24:41', '::1'),
(935, 11, 'Logout', NULL, '2026-06-04 16:24:54', '::1'),
(936, 1, 'Login', NULL, '2026-06-04 16:43:25', '::1'),
(937, 1, 'Logout', NULL, '2026-06-04 17:02:31', '::1'),
(938, 11, 'Login', NULL, '2026-06-04 17:02:35', '::1'),
(939, 11, 'Logout', NULL, '2026-06-04 17:02:39', '::1'),
(940, 11, 'Login', NULL, '2026-06-04 17:02:48', '::1'),
(941, 11, 'Logout', NULL, '2026-06-04 17:07:12', '::1'),
(942, 1, 'Login', NULL, '2026-06-04 17:07:17', '::1'),
(943, 1, 'Logout', NULL, '2026-06-04 17:08:52', '::1'),
(944, 11, 'Login', NULL, '2026-06-04 17:08:56', '::1'),
(945, 11, 'Logout', NULL, '2026-06-04 17:11:08', '::1'),
(946, 1, 'Login', NULL, '2026-06-04 17:11:12', '::1'),
(947, 1, 'Logout', NULL, '2026-06-04 17:12:40', '::1'),
(948, 11, 'Login', NULL, '2026-06-04 17:12:45', '::1'),
(949, 11, 'Logout', NULL, '2026-06-04 17:13:06', '::1'),
(950, 1, 'Login', NULL, '2026-06-04 17:13:11', '::1'),
(951, 1, 'Login', NULL, '2026-06-09 13:28:07', '::1'),
(952, 1, 'Logout', NULL, '2026-06-09 13:39:02', '::1'),
(953, 11, 'Login', NULL, '2026-06-09 13:39:05', '::1'),
(954, 11, 'Logout', NULL, '2026-06-09 13:39:12', '::1'),
(955, 21, 'Login', NULL, '2026-06-09 13:39:17', '::1'),
(956, 21, 'Logout', NULL, '2026-06-09 13:39:46', '::1'),
(957, 21, 'Login', NULL, '2026-06-09 13:39:49', '::1'),
(958, 21, 'Logout', NULL, '2026-06-09 13:40:33', '::1');
INSERT INTO `employee_logs` (`id`, `user_id`, `action`, `details`, `timestamp`, `ip_address`) VALUES
(959, 1, 'Login', NULL, '2026-06-09 13:40:39', '::1'),
(960, 1, 'Logout', NULL, '2026-06-09 13:47:36', '::1'),
(961, 21, 'Login', NULL, '2026-06-09 13:47:39', '::1'),
(962, 21, 'Logout', NULL, '2026-06-09 13:49:04', '::1'),
(963, 1, 'Login', NULL, '2026-06-09 13:49:07', '::1'),
(964, 1, 'Logout', NULL, '2026-06-09 14:04:55', '::1'),
(965, 21, 'Login', NULL, '2026-06-09 14:04:58', '::1'),
(966, 21, 'Logout', NULL, '2026-06-09 14:05:00', '::1'),
(967, 21, 'Login', NULL, '2026-06-09 14:05:05', '::1'),
(968, 21, 'Logout', NULL, '2026-06-09 14:07:07', '::1'),
(969, 1, 'Login', NULL, '2026-06-09 14:07:10', '::1'),
(970, 1, 'Logout', NULL, '2026-06-09 15:04:38', '::1'),
(971, 21, 'Login', NULL, '2026-06-09 15:04:42', '::1'),
(972, 21, 'Logout', NULL, '2026-06-09 15:04:43', '::1'),
(973, 21, 'Login', NULL, '2026-06-09 15:04:45', '::1'),
(974, 21, 'Logout', NULL, '2026-06-09 15:06:19', '::1'),
(975, 1, 'Login', NULL, '2026-06-09 15:06:21', '::1'),
(976, 1, 'Logout', NULL, '2026-06-09 15:12:12', '::1');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `status` varchar(50) DEFAULT 'pending',
  `score` varchar(10) DEFAULT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `type`, `title`, `message`, `status`, `score`, `link`, `is_read`, `created_at`) VALUES
(1, 1, 'retake_request', 'New Retake Request', 'karl antonio is requesting to retake: dadawda', 'pending', NULL, '../admin/retake_requests.php', 1, '2026-05-01 00:15:09'),
(2, 1, 'user_registration', 'New User Registration', 'jayson montenegro (@jayjay123.com) has registered and needs approval.', 'pending', NULL, 'admin_approval.php', 1, '2026-05-01 01:21:17'),
(3, 1, 'Request_retake', 'New Retake Request', 'karl antonio is requesting to retake: 0xro', 'pending', NULL, '../admin/Request_retake.php', 1, '2026-05-04 20:33:03'),
(4, 1, 'Request_retake', 'New Retake Request', 'Jane Corpin is requesting to retake: Microsoft Office', 'pending', NULL, '../admin/Request_retake.php', 1, '2026-05-08 15:43:18'),
(5, 8, 'Request_retake', 'New Retake Request', 'Jane Corpin is requesting to retake: Microsoft Office', 'pending', NULL, '../admin/Request_retake.php', 0, '2026-05-08 15:43:18'),
(6, 1, 'Request_retake', 'New Retake Request', 'Jane Corpin is requesting to retake: XERO', 'pending', NULL, '../admin/Request_retake.php', 1, '2026-05-12 00:53:40'),
(7, 8, 'Request_retake', 'New Retake Request', 'Jane Corpin is requesting to retake: XERO', 'pending', NULL, '../admin/Request_retake.php', 0, '2026-05-12 00:53:40'),
(8, 1, 'Request_retake', 'New Retake Request', 'Jane Corpin is requesting to retake: MYOB', 'pending', NULL, '../admin/Request_retake.php', 1, '2026-05-12 01:36:36'),
(9, 8, 'Request_retake', 'New Retake Request', 'Jane Corpin is requesting to retake: MYOB', 'pending', NULL, '../admin/Request_retake.php', 0, '2026-05-12 01:36:36'),
(10, 1, 'Request_retake', 'New Retake Request', 'Jane Corpin is requesting to retake: SAP', 'pending', NULL, '../admin/Request_retake.php', 1, '2026-05-12 02:23:37'),
(11, 8, 'Request_retake', 'New Retake Request', 'Jane Corpin is requesting to retake: SAP', 'pending', NULL, '../admin/Request_retake.php', 0, '2026-05-12 02:23:37'),
(12, 1, 'Request_retake', 'New Retake Request', 'Jane Corpin is requesting to retake: MYOB', 'pending', NULL, '../admin/Request_retake.php', 1, '2026-05-12 02:35:36'),
(13, 8, 'Request_retake', 'New Retake Request', 'Jane Corpin is requesting to retake: MYOB', 'pending', NULL, '../admin/Request_retake.php', 0, '2026-05-12 02:35:36'),
(14, 1, 'Request_retake', 'New Retake Request', 'Jane Corpin is requesting to retake: MYOB', 'pending', NULL, '../admin/Request_retake.php', 1, '2026-05-12 02:41:41'),
(15, 8, 'Request_retake', 'New Retake Request', 'Jane Corpin is requesting to retake: MYOB', 'pending', NULL, '../admin/Request_retake.php', 0, '2026-05-12 02:41:41'),
(16, 1, 'Request_retake', 'New Retake Request', 'Jane Corpin is requesting to retake: MYOB', 'pending', NULL, '../admin/Request_retake.php', 1, '2026-05-12 02:50:14'),
(17, 8, 'Request_retake', 'New Retake Request', 'Jane Corpin is requesting to retake: MYOB', 'pending', NULL, '../admin/Request_retake.php', 0, '2026-05-12 02:50:14'),
(18, 1, 'Request_retake', 'New Retake Request', 'Jane Corpin is requesting to retake: MYOB', 'pending', NULL, '../admin/Request_retake.php', 1, '2026-05-12 03:00:24'),
(19, 8, 'Request_retake', 'New Retake Request', 'Jane Corpin is requesting to retake: MYOB', 'pending', NULL, '../admin/Request_retake.php', 0, '2026-05-12 03:00:24'),
(20, 1, 'Request_retake', 'New Retake Request', 'romyl bieber is requesting to retake: SLACK', 'pending', NULL, '../admin/Request_retake.php', 1, '2026-05-12 03:12:36'),
(21, 8, 'Request_retake', 'New Retake Request', 'romyl bieber is requesting to retake: SLACK', 'pending', NULL, '../admin/Request_retake.php', 0, '2026-05-12 03:12:36'),
(22, 1, 'Request_retake', 'New Retake Request', 'Jane Corpin is requesting to retake: MYOB', 'pending', NULL, '../admin/Request_retake.php', 1, '2026-05-12 03:17:03'),
(23, 8, 'Request_retake', 'New Retake Request', 'Jane Corpin is requesting to retake: MYOB', 'pending', NULL, '../admin/Request_retake.php', 0, '2026-05-12 03:17:03'),
(24, 1, 'Request_retake', 'New Retake Request', 'Jane Corpin is requesting to retake: MYOB', 'pending', NULL, '../admin/Request_retake.php', 1, '2026-05-12 03:31:02'),
(25, 8, 'Request_retake', 'New Retake Request', 'Jane Corpin is requesting to retake: MYOB', 'pending', NULL, '../admin/Request_retake.php', 0, '2026-05-12 03:31:02'),
(26, 1, 'Request_retake', 'New Retake Request', 'Jane Corpin is requesting to retake: MYOB', 'pending', NULL, '../admin/Request_retake.php', 1, '2026-05-12 06:33:49'),
(27, 8, 'Request_retake', 'New Retake Request', 'Jane Corpin is requesting to retake: MYOB', 'pending', NULL, '../admin/Request_retake.php', 0, '2026-05-12 06:33:49'),
(28, 1, 'Request_retake', 'New Retake Request', 'Jane Corpin is requesting to retake: MYOB', 'pending', NULL, '../admin/Request_retake.php', 1, '2026-05-12 06:40:03'),
(29, 8, 'Request_retake', 'New Retake Request', 'Jane Corpin is requesting to retake: MYOB', 'pending', NULL, '../admin/Request_retake.php', 0, '2026-05-12 06:40:03'),
(30, 1, 'Request_retake', 'New Retake Request', 'romyl bieber is requesting to retake: Google workspace', 'pending', NULL, '../admin/Request_retake.php', 1, '2026-05-12 08:17:57'),
(31, 8, 'Request_retake', 'New Retake Request', 'romyl bieber is requesting to retake: Google workspace', 'pending', NULL, '../admin/Request_retake.php', 0, '2026-05-12 08:17:57'),
(32, 1, 'Request_retake', 'New Retake Request', 'Jane Corpin is requesting to retake: SAP', 'pending', NULL, '../admin/Request_retake.php', 1, '2026-05-12 10:30:02'),
(33, 8, 'Request_retake', 'New Retake Request', 'Jane Corpin is requesting to retake: SAP', 'pending', NULL, '../admin/Request_retake.php', 0, '2026-05-12 10:30:02'),
(34, 1, 'user_registration', 'New User Registration', 'ariel gorden (ariel@upstaff.com.au) has registered and needs approval.', 'pending', NULL, 'admin_approval.php', 1, '2026-05-12 12:17:11'),
(35, 8, 'user_registration', 'New User Registration', 'ariel gorden (ariel@upstaff.com.au) has registered and needs approval.', 'pending', NULL, 'admin_approval.php', 0, '2026-05-12 12:17:11'),
(36, 1, 'Request_retake', 'New Retake Request', 'ariel gorden is requesting to retake: Microsoft Office', 'pending', NULL, '../admin/Request_retake.php', 1, '2026-05-12 12:25:02'),
(37, 8, 'Request_retake', 'New Retake Request', 'ariel gorden is requesting to retake: Microsoft Office', 'pending', NULL, '../admin/Request_retake.php', 0, '2026-05-12 12:25:02'),
(38, 1, 'Request_retake', 'New Retake Request', 'Jane Corpin is requesting to retake: demo', 'pending', NULL, '../admin/Request_retake.php', 1, '2026-06-04 17:06:59'),
(39, 8, 'Request_retake', 'New Retake Request', 'Jane Corpin is requesting to retake: demo', 'pending', NULL, '../admin/Request_retake.php', 0, '2026-06-04 17:06:59'),
(40, 21, 'Request_retake', 'New Retake Request', 'Jane Corpin is requesting to retake: demo', 'pending', NULL, '../admin/Request_retake.php', 0, '2026-06-04 17:06:59');

-- --------------------------------------------------------

--
-- Table structure for table `options`
--

CREATE TABLE `options` (
  `id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `option_text` varchar(500) NOT NULL,
  `is_correct` tinyint(1) DEFAULT 0,
  `order_index` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `options`
--

INSERT INTO `options` (`id`, `question_id`, `option_text`, `is_correct`, `order_index`) VALUES
(428, 164, 'Established', 0, 0),
(429, 164, 'Growing', 0, 1),
(430, 164, 'Demo', 0, 2),
(431, 164, 'Early ', 1, 3),
(432, 165, 'An accountant or bookkeeper', 1, 0),
(433, 165, 'Any invited user with Standard access', 0, 1),
(434, 165, 'The automated Jax AI', 0, 2),
(435, 165, 'The business owner', 0, 3),
(436, 166, 'To automatically pay all outstanding bills using the automatic feed', 0, 0),
(437, 166, 'To calculate the annual tax liability for the business', 0, 1),
(438, 166, 'To apply for business loans directly through the bank feed', 0, 2),
(439, 166, 'To ensure cash records in Xero match the actual bank statement transactions ', 1, 3),
(440, 167, 'Flagged with a fiery red color ', 1, 0),
(441, 167, 'Surrounded by a yellow warning border', 0, 1),
(442, 167, 'Highlighted in electric blue', 0, 2),
(443, 167, 'Marked with a green checkmark', 0, 3),
(444, 168, 'Income Statement', 0, 0),
(445, 168, 'Short-term cash flow projection', 0, 1),
(446, 168, 'Balance Sheet ', 1, 2),
(447, 168, 'Business Snapshot', 0, 3),
(448, 169, 'To provide real-time strategic AI insights', 0, 0),
(449, 169, 'To automate bookkeeping tasks like entering bills and receipts', 1, 1),
(450, 169, 'To track mileage and expense claims for employees', 0, 2),
(451, 169, 'To manage employee payroll and tax filings', 0, 3),
(452, 170, 'Established ', 1, 0),
(453, 170, 'Standard', 0, 1),
(454, 170, 'Early', 0, 2),
(455, 170, 'Growing', 0, 3),
(456, 171, 'A brand new AI business companion ', 1, 0),
(457, 171, 'A new customer support ticketing system', 0, 1),
(458, 171, 'An external payroll integration for US employees', 0, 2),
(459, 171, 'A specialized tool for managing fixed asset registers', 0, 3),
(460, 172, 'Organization Details', 0, 0),
(461, 172, 'Account Watchlist', 0, 1),
(462, 172, 'Users ', 1, 2),
(463, 172, 'Connected Apps', 0, 3),
(464, 173, 'Bills are only used for cash payments, while purchase orders are for credit', 0, 0),
(465, 173, 'Purchase orders are for tracking orders with suppliers and don\'t impact accounts until converted ', 1, 1),
(466, 173, 'Purchase orders impact the income statement immediately upon creation', 0, 2),
(467, 173, 'Only bills can be used to track inventory items', 0, 3),
(468, 174, 'Store location, receipt number, and discount applied', 0, 0),
(469, 174, 'Vendor, date, and amount ', 1, 1),
(470, 174, 'Itemized list, tax rate, and payment method', 0, 2),
(471, 174, 'Employee name, category, and currency', 0, 3),
(472, 175, 'By offering a flat-rate tax reduction for all software subscribers', 0, 0),
(473, 175, 'By providing a direct legal representative for tax audits', 0, 1),
(474, 175, 'By automatically paying all tax liabilities from the linked bank account', 0, 2),
(475, 175, 'By automating GST calculations and business activity statement reporting', 1, 3),
(476, 176, 'It hides personal transactions from the business dashboard for privacy', 0, 0),
(477, 176, 'It eliminates the need for the user to ever check their bank balance', 0, 1),
(478, 176, 'It allows the software to take out short-term business loans automatically', 0, 2),
(479, 176, 'It automatically imports and matches transactions to reduce manual admin time', 1, 3),
(480, 177, 'It is only accessible via desktop computers to ensure high security', 1, 0),
(481, 177, 'It allows for unlimited users and multi-departmental tracking', 0, 1),
(482, 177, 'It is an all-in-one mobile app with automated receipt capturing and graphs', 0, 2),
(483, 177, 'It removes the need to track expenses or income entirely', 0, 3),
(484, 178, 'Managing complex manufacturing supply chains', 0, 0),
(485, 178, 'Budgeting and cash flow visibility for irregular income', 1, 1),
(486, 178, 'Calculating international import duties for physical goods', 0, 2),
(487, 178, 'Automating social media content creation for marketing', 0, 3),
(488, 179, 'On-site job tracking and managing materials or labor costs', 1, 0),
(489, 179, 'Managing high-volume retail storefront inventory', 0, 1),
(490, 179, 'Global currency exchange and stock market integration', 0, 2),
(491, 179, 'Automating customer hair-care history records', 0, 3),
(492, 180, 'Class pass tracking and superannuation reporting', 1, 0),
(493, 180, 'Calculating depreciation for high-rise commercial real estate', 0, 1),
(494, 180, 'Managing wholesale warehouse shipping logistics', 0, 2),
(495, 180, 'Real-time tracking of heavy machinery fuel consumption', 0, 3),
(496, 181, 'By including personal photos from the user\'s social media profile', 0, 0),
(497, 181, 'By using customizable templates that incorporate their own branding', 1, 1),
(498, 181, 'By sending invoices via traditional certified mail automatically', 0, 2),
(499, 181, 'By requiring clients to pay in person using physical currency', 0, 3),
(500, 182, 'It does not allow for any mobile access', 0, 0),
(501, 182, 'The software lacks any customer support options', 0, 1),
(502, 182, 'The software might feel a bit complex to navigate initially', 1, 2),
(503, 182, 'It is not compatible with Australian tax laws', 0, 3),
(504, 183, 'Raw material waste reduction statistics', 0, 0),
(505, 183, 'Daily physical inventory turnover rates', 0, 1),
(506, 183, 'Fuel efficiency for long-haul transport vehicles', 0, 2),
(507, 183, 'Profitability tracking by client or project', 1, 3),
(508, 184, 'English', 0, 0),
(509, 184, 'Dutch', 0, 1),
(510, 184, 'German', 1, 2),
(511, 184, 'French', 0, 3),
(512, 185, 'Executive Resource Program', 0, 0),
(513, 185, 'Essential Reporting Procedure', 0, 1),
(514, 185, 'Enterprise Resource Planning', 1, 2),
(515, 185, 'Electronic Revenue Processing', 0, 3),
(516, 186, 'IBM', 1, 0),
(517, 186, 'Intel', 0, 1),
(518, 186, 'Microsoft', 0, 2),
(519, 186, 'Oracle', 0, 3),
(520, 187, 'Apple iPhone', 0, 0),
(521, 187, 'BMW', 0, 1),
(522, 187, 'Coca-Cola', 1, 2),
(523, 187, 'Microsoft Windows', 0, 3),
(524, 188, '95%', 0, 0),
(525, 188, '60%', 0, 1),
(526, 188, '50%', 0, 2),
(527, 188, '77%', 1, 3),
(528, 189, 'Finance', 0, 0),
(529, 189, 'Human Resources', 0, 1),
(530, 189, 'Sales and Distribution', 0, 2),
(531, 189, 'Hardware Manufacturing', 1, 3),
(532, 190, 'Basic', 0, 0),
(533, 190, 'Berlin', 0, 1),
(534, 190, 'Business', 0, 2),
(535, 190, 'Bavarian', 1, 3),
(536, 191, 'SAP SE', 1, 0),
(537, 191, 'SAP Global Corp', 0, 1),
(538, 191, 'SAP Germany GMBH', 0, 2),
(539, 191, 'SAP ERP', 0, 3),
(540, 192, 'The name is long and complex, especially for non-German speakers.', 1, 0),
(541, 192, 'The acronym actually stands for different words in different countries.', 0, 1),
(542, 192, 'The company prefers to keep its origin a secret for branding reasons.', 0, 2),
(543, 192, 'The name was changed legally to just the abbreviation \'SAP\' years ago.', 0, 3),
(544, 193, 'SAP CRM', 1, 0),
(545, 193, 'SAP PLM', 0, 1),
(546, 193, 'SAP BI', 0, 2),
(547, 193, 'SAP HR', 0, 3),
(548, 194, 'Human Resources (HR)', 0, 0),
(549, 194, 'Customer Relationship Management (CRM)', 0, 1),
(550, 194, 'Accounting', 1, 2),
(551, 194, 'Inventory Management', 0, 3),
(552, 195, 'Hybrid-offline', 0, 0),
(553, 195, 'Cloud-based', 1, 1),
(554, 195, 'Open-source', 0, 2),
(555, 195, 'On-premise servers', 0, 3),
(556, 196, 'It displays specific tasks requiring action based on the user\'s role.', 1, 0),
(557, 196, 'It serves as a backup storage area for deleted files.', 0, 1),
(558, 196, 'It provides a chat interface for real-time communication.', 0, 2),
(559, 196, 'It tracks employee clock-in and clock-out times.', 0, 3),
(560, 197, 'The report is automatically exported to a CSV file.', 0, 0),
(561, 197, 'The software opens the \'Help\' documentation for that field.', 0, 1),
(562, 197, 'The user \'drills down\' into the individual transactions making up that total.', 1, 2),
(563, 197, 'The system prompts the user to delete the entry.', 0, 3),
(564, 198, 'Suite Analytics allows for pivot tables and advanced graphical representations.', 1, 0),
(565, 198, 'SuiteAnalytics can only be used for external data sources.', 0, 1),
(566, 198, 'Saved Searches require coding, while SuiteAnalytics is strictly drag-and-drop.', 0, 2),
(567, 198, 'Saved Searches are more visual, while SuiteAnalytics is purely text-based.', 0, 3),
(568, 199, 'Material Requirements Planning (MRP)', 1, 0),
(569, 199, 'Just-In-Time (JIT)', 0, 1),
(570, 199, 'Manual Spreadsheet Sync', 0, 2),
(571, 199, 'First-In, First-Out (FIFO)', 0, 3),
(572, 200, 'Contacting the software\'s original founders', 0, 0),
(573, 200, 'Waiting for the next yearly version update', 0, 1),
(574, 200, 'Developing a custom ERP from scratch', 0, 2),
(575, 200, 'Utilizing the third-party application ecosystem', 1, 3),
(576, 201, 'It restarts the software to clear the cache.', 0, 0),
(577, 201, 'It provides a list of shortcuts to generate new records or transactions.', 1, 1),
(578, 201, 'It creates a new user profile for an employee.', 0, 2),
(579, 201, 'It allows the user to build a new dashboard from scratch.', 0, 3),
(580, 202, 'The specific role of the user (e.g., Controller)', 1, 0),
(581, 202, 'The alphabetized list of company departments', 0, 1),
(582, 202, 'The date the employee was hired', 0, 2),
(583, 202, 'The geographic location of the office', 0, 3),
(584, 203, 'Hardware manufacturers and internet service providers', 0, 0),
(585, 203, 'NetSuite Professional Services and third-party solution providers', 1, 1),
(586, 203, 'The company\'s existing IT staff and temporary interns', 0, 2),
(587, 203, 'Social media influencers and retail consultants', 0, 3),
(588, 204, 'Set the privacy level', 0, 0),
(589, 204, 'Give the board a name ', 1, 1),
(590, 204, 'Invite team members', 0, 2),
(591, 205, 'From the top list to the bottom list.', 1, 0),
(592, 205, 'Randomly between lists', 0, 1),
(593, 205, 'From right to left', 0, 2),
(594, 205, 'From left to right', 0, 3),
(595, 206, 'The Checklist section', 0, 0),
(596, 206, 'The Description area', 1, 1),
(597, 206, 'The Label names', 0, 2),
(598, 206, 'The Activity feed', 0, 3),
(599, 207, 'Click directly on the color bar of the label', 1, 0),
(600, 207, 'Right-click the background of the board', 0, 1),
(601, 207, 'Hover over the label for three seconds', 0, 2),
(602, 207, 'Double-click the card title', 0, 3),
(603, 208, 'The card automatically moves to the \'Done\' list', 1, 0),
(604, 208, 'The card\'s background color changes to green', 0, 1),
(605, 208, 'The card is archived automatically', 0, 2),
(606, 208, 'A progress indicator shows the number of completed subtasks ', 0, 3),
(607, 209, 'It deletes the card permanently after 30 days', 0, 0),
(608, 209, 'The card is removed but remains searchable and can be reactivated', 1, 1),
(609, 209, 'It automatically moves the card to a different board', 0, 2),
(610, 209, 'It keeps the card visible but makes it uneditable', 0, 3),
(611, 210, 'LabelsB.', 0, 0),
(612, 210, 'Power-ups ', 1, 1),
(613, 210, 'The Search Menu', 0, 2),
(614, 210, 'Checklists', 0, 3),
(615, 211, 'True', 1, 0),
(616, 211, 'False', 0, 1),
(617, 212, 'Archive all cards in that list ', 1, 0),
(618, 212, 'Delete the entire \'Done\' list', 0, 1),
(619, 212, 'Change the board background to hide the list', 0, 2),
(620, 212, 'Move completed cards back to \'Ideas\'', 0, 3),
(621, 213, 'Automatically archiving cards when they expire', 0, 0),
(622, 213, 'Locking the card so no more changes can be made', 0, 1),
(623, 213, 'Acting as a notification as the date approaches', 1, 2),
(624, 213, 'Automatically assigning the card to a team member', 0, 3),
(625, 214, '1 gigabyte of storage', 1, 0),
(626, 214, 'Unlimited storage with a file size cap', 0, 1),
(627, 214, '500 megabytes of storage', 0, 2),
(628, 214, '100 megabytes of storage ', 0, 3),
(629, 215, 'Workload view', 1, 0),
(630, 215, 'Board view', 0, 1),
(631, 215, 'Gantt view', 0, 2),
(632, 215, 'Timeline view', 0, 3),
(633, 216, 'It focuses primarily on mobile-only features', 0, 0),
(634, 216, 'It removes the \'everything app\' philosophy', 0, 1),
(635, 216, 'It adds more complexity to satisfy power users', 0, 2),
(636, 216, 'It is more approachable and easier to manage', 1, 3),
(637, 217, 'It is strictly for personal use as a freelancer', 0, 0),
(638, 217, 'It mimics the interface of monday.com ', 1, 1),
(639, 217, 'It is the only view that allows for custom dates', 0, 2),
(640, 217, 'It uses a spreadsheet-only data entry system', 0, 3),
(641, 218, 'To manage long-term team knowledge for consistent access ', 1, 0),
(642, 218, 'To provide a direct replacement for Miro whiteboards', 0, 1),
(643, 218, 'To act as a spreadsheet for financial reporting', 0, 2),
(644, 218, 'To track real-time daily chat logs', 0, 3),
(645, 219, 'Mind Map view', 1, 0),
(646, 219, 'Table view', 0, 1),
(647, 219, 'Chat view', 0, 2),
(648, 219, 'Dashboard view', 0, 3),
(649, 220, 'Setting the priority level of the work', 0, 0),
(650, 220, 'Embedding the task in other areas of the application ', 1, 1),
(651, 220, 'Determining the order in which tasks must be finished', 0, 2),
(652, 220, 'Tracking the unique cost of the task', 0, 3),
(653, 221, 'It is used exclusively for automated AI updates', 0, 0),
(654, 221, 'It is only available on the most expensive Enterprise plan', 0, 1),
(655, 221, 'It gets a team about 80% of the way there and reduces costs ', 1, 2),
(656, 221, 'It is a full feature-for-feature replacement for Slack', 0, 3),
(657, 222, 'Gantt is for personal use while Timeline is for teams', 0, 0),
(658, 222, 'Timeline view is only for managing financial data', 0, 1),
(659, 222, 'Gantt view does not allow for subtasks', 0, 2),
(660, 222, 'Gantt includes task dependencies and is more intense ', 1, 3),
(661, 223, 'The white-labeling assistant', 0, 0),
(662, 223, 'The Gantt generator', 0, 1),
(663, 223, 'The standup feature', 1, 2),
(664, 223, 'The automatic time-tracker', 0, 3),
(665, 224, 'It collapses the ribbon to provide more space for the document text.', 0, 0),
(666, 224, 'It opens a window with additional features that could not fit in the ribbon space.', 1, 1),
(667, 224, 'It triggers a search for help topics related to that specific group.', 0, 2),
(668, 224, 'It automatically formats the selected text using recommended AI styles.', 0, 3),
(669, 225, 'Click once in the left margin next to the paragraph.', 0, 0),
(670, 225, 'Triple-click anywhere within the paragraph. ', 1, 1),
(671, 225, 'Double-click the first word of the paragraph.', 0, 2),
(672, 225, 'Right-click the paragraph and select \'Highlight All\'.', 0, 3),
(673, 226, 'Because \'Save\' is only used for documents stored in the cloud.', 0, 0),
(674, 226, 'Because the document must be converted to a template before it can be saved normally.', 0, 1),
(675, 226, 'Because the document has not yet been assigned a file name or a storage location. ', 1, 2),
(676, 226, 'Because the document is currently in \'Read Only\' mode until it is named.', 0, 3),
(677, 227, 'Escape ', 1, 0),
(678, 227, 'Backspace', 0, 1),
(679, 227, 'F5', 0, 2),
(680, 227, 'Enter', 0, 3),
(681, 228, 'Ctrl + Z', 0, 0),
(682, 228, 'Ctrl + R', 0, 1),
(683, 228, 'Ctrl + V', 0, 2),
(684, 228, 'Ctrl + Y', 1, 3),
(685, 229, 'It ensures the search only finds words with the exact same capitalization as the search term.', 1, 0),
(686, 229, 'It automatically changes the font of the replaced text to match the surrounding paragraph.', 0, 1),
(687, 229, 'It finds words that match the grammatical tense of the surrounding sentence.', 0, 2),
(688, 229, 'It searches for the word in every open Word document simultaneously.', 0, 3),
(689, 230, 'The cursor exits the table and moves to the next line of regular document text.', 0, 0),
(690, 230, 'Word automatically adds a new blank row to the bottom of the table. ', 1, 1),
(691, 230, 'The table is automatically converted into plain text separated by tabs.', 0, 2),
(692, 230, 'The cursor moves to the first cell at the top of the table.', 0, 3),
(693, 231, 'In the \'View\' tab under the \'Zoom\' options.', 0, 0),
(694, 231, 'Under the \'Font\' group in the \'Advanced Text Effects\' menu.', 0, 1),
(695, 231, 'In the \'Insert\' tab under \'Quick Parts\'.', 0, 2),
(696, 231, 'In the Paragraph settings dialogue box, accessible via the launch button on the Home tab.', 1, 3),
(697, 232, 'It can be configured to expand a short code (like \'T4TAS\') into a much longer predefined phrase. ', 1, 0),
(698, 232, 'It translates abbreviated slang into formal academic language automatically.', 0, 1),
(699, 232, 'It restricts the user from typing abbreviations that are not found in the official dictionary.', 0, 2),
(700, 232, 'It automatically deletes any word that is repeated twice in a row.', 0, 3),
(701, 233, 'It prevents the document from being edited by anyone else once the break is inserted.', 0, 0),
(702, 233, 'It ensures the new section always starts at the top of the next page, regardless of changes made to the previous text. ', 1, 1),
(703, 233, 'It automatically applies a border to the new page to signify a new chapter.', 0, 2),
(704, 233, 'It reduces the overall file size of the Word document significantly.', 0, 3),
(705, 234, 'The Workspace account allows for the use of a custom business domain name. ', 1, 0),
(706, 234, 'The consumer account provides access to more collaboration tools like Google Meet.', 0, 1),
(707, 234, 'Consumer accounts require a monthly subscription fee whereas Workspace is free.', 0, 2),
(708, 234, 'Workspace accounts use a completely different interface for Gmail and Calendar.', 0, 3),
(709, 235, 'Chrome policies', 1, 0),
(710, 235, 'Shared Drive permissions', 0, 1),
(711, 235, 'Gmail AI filters', 0, 2),
(712, 235, 'DNS records management', 0, 3),
(713, 236, 'It includes features like Shared Drives and the ability to record Google Meet sessions. ', 1, 0),
(714, 236, 'It removes the need to configure DNS records during the setup process.', 0, 1),
(715, 236, 'It provides the only way to access Google Docs and Sheets.', 0, 2),
(716, 236, 'It is the only plan that allows for a custom domain name.', 0, 3),
(717, 237, 'MX', 0, 0),
(718, 237, 'SPF ', 1, 1),
(719, 237, 'CNAME', 0, 2),
(720, 237, 'A Record', 0, 3),
(721, 238, 'It removes local copies of files that haven\'t been used in a while, keeping them in the cloud. ', 1, 0),
(722, 238, 'It only stores files on the local drive and deletes them from the cloud once synchronized.', 0, 1),
(723, 238, 'It compresses all local files into a ZIP format to save space.', 0, 2),
(724, 238, 'It duplicates every single file in the cloud onto the local hard drive.', 0, 3),
(725, 239, 'The contractor remains the owner of the files they create and share with you. ', 1, 0),
(726, 239, 'Google charges a fee for every file shared with a non-business user.', 0, 1),
(727, 239, 'Contractors cannot open files created in a consumer Gmail account.', 0, 2),
(728, 239, 'Contractors can automatically see your family photos.', 0, 3),
(729, 240, 'Revision history ', 1, 0),
(730, 240, 'Smart Compose', 0, 1),
(731, 240, 'Suggesting mode', 0, 2),
(732, 240, 'Activity Dashboard', 0, 3),
(733, 241, 'It is uploaded to a private YouTube playlist by default.', 0, 0),
(734, 241, 'It is deleted after 24 hours to save space unless manually downloaded.', 0, 1),
(735, 241, 'It is emailed to all participants as an attachment.', 0, 2),
(736, 241, 'It is automatically saved into a folder in your Google Drive. ', 1, 3),
(737, 242, 'Restricted mode', 1, 0),
(738, 242, 'Suggesting mode ', 0, 1),
(739, 242, 'Viewing mode', 0, 2),
(740, 242, 'Editing mode', 0, 3),
(741, 243, 'The business must pay a licensing fee for each file moved into the drive.', 0, 0),
(742, 243, 'The file remains the private property of the contractor.', 0, 1),
(743, 243, 'The file is automatically deleted after the contractor\'s contract expires.', 0, 2),
(744, 243, 'The contractor receives a prompt to transfer ownership to the business. ', 1, 3),
(745, 244, 'A specialized software exclusively for tracking employee clock-in times.', 0, 0),
(746, 244, 'A video editing suite for creative marketing teams.', 0, 1),
(747, 244, 'An external email hosting service that replaces Gmail or Outlook.', 0, 2),
(748, 244, 'A central hub for communication, files, and project organization.', 1, 3),
(749, 245, 'By following the prompts in an invitation email sent by the company.', 1, 0),
(750, 245, 'By contacting Slack support to verify your employment status.', 0, 1),
(751, 245, 'By creating a brand new workspace with the company\'s trademarked name.', 0, 2),
(752, 245, 'By searching for the company name on slack.com and clicking \'Join\'.', 0, 3),
(753, 246, 'Channels are limited to a maximum of three participants.', 0, 0),
(754, 246, 'Messages are lost or hidden after approximately 90 days.', 1, 1),
(755, 246, 'Direct messaging is completely disabled in the free version.', 0, 2),
(756, 246, 'Users are restricted to a maximum of five total messages per day.', 0, 3),
(757, 247, 'Channels are for company-wide announcements only, while Direct Messages are for social chat.', 0, 0),
(758, 247, 'Channels are public for everyone to see, while Direct Messages are always encrypted.', 0, 1),
(759, 247, 'Channels are used for file sharing, whereas Direct Messages are restricted to text only.', 0, 2),
(760, 247, 'Channels function as individual chat rooms for groups, while Direct Messages are one-on-one conversations.', 1, 3),
(761, 248, 'It keeps related messages organized and prevents the main channel from becoming cluttered.', 1, 0),
(762, 248, 'It automatically notifies everyone in the entire organization.', 0, 1),
(763, 248, 'It converts the message into an official email for external clients.', 0, 2),
(764, 248, 'It locks the channel so no other messages can be sent until the thread is closed.', 0, 3),
(765, 249, 'The Search bar at the top.', 0, 0),
(766, 249, 'The \'Social\' channel.', 0, 1),
(767, 249, 'The Profile settings menu.', 0, 2),
(768, 249, 'The Activity section on the sidebar.', 1, 3),
(769, 250, 'To bypass the need to invite any team members.', 0, 0),
(770, 250, 'To hide the channel from the company\'s IT administrators.', 0, 1),
(771, 250, 'To automatically upgrade the workspace to the Pro version.', 0, 2),
(772, 250, 'To provide a pre-laid out structure specifically tailored for a project\'s needs.', 1, 3),
(773, 251, 'By messaging yourself to jot down notes and private thoughts.', 1, 0),
(774, 251, 'By using it to test if your internet connection is working.', 0, 1),
(775, 251, 'By setting up an automated bot that replies to your own messages.', 0, 2),
(776, 251, 'By creating a backup of your entire computer\'s hard drive.', 0, 3),
(777, 252, 'Zoom', 1, 0),
(778, 252, 'Microsoft Teams', 0, 1),
(779, 252, 'Google Meet', 0, 2),
(780, 252, 'Skype', 0, 3),
(781, 253, 'Asking every teammate in a Direct Message if they remember the file.', 0, 0),
(782, 253, 'Checking the \'Social\' channel archives manually.', 0, 1),
(783, 253, 'Using the search bar to look for keywords or file names.', 1, 2),
(784, 253, 'Re-uploading the file so it appears at the bottom of the feed.', 0, 3),
(785, 254, 'Option 1', 1, 0),
(786, 254, 'Option 2', 0, 1),
(787, 254, 'New choice', 0, 2),
(788, 254, 'New choice', 0, 3),
(789, 255, 'True', 1, 0),
(790, 255, 'False', 0, 1);

-- --------------------------------------------------------

--
-- Table structure for table `questions`
--

CREATE TABLE `questions` (
  `id` int(11) NOT NULL,
  `quiz_id` int(11) NOT NULL,
  `type` enum('mc','tf') NOT NULL,
  `question_text` text NOT NULL,
  `order_index` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `questions`
--

INSERT INTO `questions` (`id`, `quiz_id`, `type`, `question_text`, `order_index`) VALUES
(164, 164, 'mc', 'Which Xero subscription plan is specifically limited to sending 20 quotes or invoices and receiving 5 bills per month?', 0),
(165, 165, 'mc', 'According to the guide, who is specifically recommended to create manual journal entries?', 0),
(166, 166, 'mc', 'What is the primary purpose of the \'Bank Reconciliation\' process in Xero?', 0),
(167, 167, 'mc', 'In the Xero Sales Overview, how are overdue amounts visually identified to the user?', 0),
(168, 168, 'mc', 'Which specific reporting tool provides a snapshot of assets, liabilities, and equity at a specific point in time?', 0),
(169, 169, 'mc', 'What is the function of the \'Hubdoc\' application mentioned in the \'Do More with Xero\' menu?', 0),
(170, 170, 'mc', 'If a business deals with multiple employees and needs to track specific projects and multi-currency transactions, which plan is required?', 0),
(171, 171, 'mc', 'What does the \'Jax\' feature in the navigation bar represent?', 0),
(172, 172, 'mc', 'In the Organization Settings, which section allows you to manage who has access to the Xero file and their permissions?', 0),
(173, 173, 'mc', 'What distinguishes \'Purchase Orders\' from \'Bills\' in Xero\'s Purchases menu?', 0),
(174, 174, 'mc', 'Which specific data points does the \'snap and track\' feature automatically extract from a receipt photo?', 0),
(175, 175, 'mc', 'How does MYOB assist users in meeting Australian Taxation Office (ATO) requirements?', 0),
(176, 176, 'mc', 'What is a primary benefit of linking a bank account directly to the MYOB software?', 0),
(177, 177, 'mc', 'According to the tutorial, what makes \'MYOB Solo\' distinct from standard spreadsheets for business management?', 0),
(178, 178, 'mc', 'For \'Creative Freelancers,\' which specific accounting challenge does MYOB address according to the demo?', 0),
(179, 179, 'mc', 'What is a key focus area for MYOB when supporting \'Trades\' professionals like builders and electricians?', 0),
(180, 180, 'mc', 'Which feature is highlighted as particularly useful for Health and Wellness professionals?', 0),
(181, 181, 'mc', 'How can users improve their professionalism when invoicing clients through MYOB?', 0),
(182, 182, 'mc', 'What is one mentioned disadvantage for absolute beginners using MYOB?', 0),
(183, 183, 'mc', 'For consultants and subject matter experts, MYOB emphasizes which financial metric?', 0),
(184, 184, 'mc', 'The acronym SAP originated from a phrase in which language?', 0),
(185, 185, 'mc', 'What does the \'ERP\' in SAP ERP stand for?', 0),
(186, 186, 'mc', 'Before founding SAP, the original five founders were employees of which technology giant?', 0),
(187, 187, 'mc', 'Which analogy does the author use to explain why both the company and its primary software product share the name \'SAP\'?', 0),
(188, 188, 'mc', 'According to the source material, approximately what percentage of the world\'s transaction revenue touches an SAP system?', 0),
(189, 189, 'mc', 'Which of the following is NOT listed as one of the primary business areas typically supported by ERP systems?', 0),
(190, 190, 'mc', 'In the context of the German brand BMW, used as an analogy for SAP, what does the \'B\' stand for?', 0),
(191, 191, 'mc', 'What is the official full legal name of the SAP company mentioned in the material?', 0),
(192, 192, 'mc', 'What is the primary reason the author suggests that even many SAP specialists do not remember the full German name of the company?', 0),
(193, 193, 'mc', 'Which of these is mentioned as a specific SAP product used for managing customer relations?', 0),
(194, 194, 'mc', 'According to the demo, which functional area is considered the \'core\' and most stable part of NetSuite?', 0),
(195, 195, 'mc', 'NetSuite is distinguished in the ERP market by being the very first software of its kind to be based in which environment?', 0),
(196, 196, 'mc', 'What is the primary benefit of the \'Reminders\' portlet on the home dashboard?', 0),
(197, 197, 'mc', 'When looking at a financial report like an Income Statement, what happens if a user clicks on a specific total revenue number?', 0),
(198, 198, 'mc', 'How does Suite Analytics differ from standard Saved Searches in NetSuite?', 0),
(199, 199, 'mc', 'Within the inventory management demo, which planning method was mentioned as an alternative to \'Reorder Point\'?', 0),
(200, 200, 'mc', 'If a user finds that NetSuite lacks a specific niche functionality, what does the demo suggest as a solution?', 0),
(201, 201, 'mc', 'What functionality does the \'Create New\' button at the top of the interface provide?', 0),
(202, 202, 'mc', 'The demo mentions that dashboard views in NetSuite are primarily organized by:', 0),
(203, 203, 'mc', 'According to the demo\'s conclusion, who are the two main types of entities that can assist with NetSuite implementation?', 0),
(204, 204, 'mc', 'When creating a new Trello board, what is the very first step you must take after selecting \'Create a board\'?', 0),
(205, 205, 'mc', 'In a standard Trello workflow, what is the typical progression for moving cards across lists?', 0),
(206, 206, 'mc', 'Where is the best place to add extensive notes or links within a Trello card to avoid cluttering the title?', 0),
(207, 207, 'mc', 'How can you view the text names of labels if only the colors are currently visible on the front of the cards?', 0),
(208, 208, 'mc', 'What happens to the front of a Trello card when you check off items in a checklist?', 0),
(209, 209, 'mc', 'Which of the following is an advantage of \'Archiving\' a card rather than deleting it?', 0),
(210, 210, 'mc', 'Which feature allows you to integrate Trello with external tools like Google Drive or add custom fields to cards?', 0),
(211, 211, 'tf', 'True or False: When searching for cards using a specific label filter, Trello will still show you the total number of cards in each list, even if they don\'t match the filter.', 0),
(212, 212, 'mc', 'If you want to keep your Trello board tidy without losing your \'Done\' list, what action does the tutorial recommend taking for completed tasks?', 0),
(213, 213, 'mc', 'Trello due dates serve two primary functions: tracking progress and which of the following?', 0),
(214, 214, 'mc', 'What is the primary storage limitation for users on ClickUp\'s free plan?', 0),
(215, 215, 'mc', 'Which ClickUp view is specifically highlighted as being the most effective for a manager to monitor team capacity and reallocate tasks?', 0),
(216, 216, 'mc', 'How does the tutorial characterize ClickUp version 3.0 compared to previous versions?', 0),
(217, 217, 'mc', 'According to the tutorial, what makes the \'List view\' particularly familiar to users migrating from other software?', 0),
(218, 218, 'mc', 'What is the primary function of the \'Internal Wiki\' within ClickUp Documents?', 0),
(219, 219, 'mc', 'Which feature allows users to visualize task breakdowns and hierarchy if they are \'visual thinkers\'?', 0),
(220, 220, 'mc', 'In the context of task management, what is the \'ID\' associated with each task used for?', 0),
(221, 221, 'mc', 'Which statement best describes ClickUp\'s \'Chat\' feature in relation to established tools like Slack?', 0),
(222, 222, 'mc', 'What distinguishes the \'Gantt view\' from the \'Timeline view\' in ClickUp?', 0),
(223, 223, 'mc', 'Which ClickUp AI feature is designed to help a user prepare for the day ahead within the Home area?', 0),
(224, 224, 'mc', 'What is the primary function of the \'dialogue launcher\' (also referred to as a launch button) found in some groups on the ribbon?', 0),
(225, 225, 'mc', 'In Microsoft Word, what is the fastest way to select an entire paragraph using only the mouse?', 0),
(226, 226, 'mc', 'When saving a document for the very first time, why does Word automatically default to \'Save As\' even if you click \'Save\'?', 0),
(227, 227, 'mc', 'If a user is in \'Focus Mode\' and wants to return to the standard \'Print Layout\' view, which keyboard key should they press?', 0),
(228, 228, 'mc', 'Which keyboard shortcut is used to \'Redo\' an action that was previously undone?', 0),
(229, 229, 'mc', ' What is the purpose of the \'Match Case\' option within the \'Find and Replace\' tool?', 0),
(230, 230, 'mc', 'When working in a table, what happens if you press the \'Tab\' key while your cursor is in the very last cell (bottom right)?', 0),
(231, 231, 'mc', 'Where can you find the options to change line spacing from \'Multiple\' to \'Double\' in a document?', 0),
(232, 232, 'mc', 'Which of the following describes the \'Autocorrect\' tool\'s ability to handle custom abbreviations?', 0),
(233, 233, 'mc', 'Why is using a \'Page Break\' (found in the Layout tab) preferable to pressing the \'Enter\' key multiple times to start a new page?', 0),
(234, 234, 'mc', 'What is the primary technical difference between a standard consumer Gmail account and a Google Workspace account?', 0),
(235, 235, 'mc', 'Which administrative feature allows a business owner to manage settings for staff members connecting to resources via a specific web browser?', 0),
(236, 236, 'mc', 'Why is the \'Business Standard\' plan recommended over the \'Basic\' plan for most businesses?', 0),
(237, 237, 'mc', 'During the technical setup of Google Workspace, which DNS setting is critical to prevent your emails from being flagged as spam?', 0),
(238, 238, 'mc', 'How does the modern Google Drive desktop application manage local storage space efficiently?', 0),
(239, 239, 'mc', 'What is a major risk of using a personal Gmail account when collaborating with external contractors?', 0),
(240, 240, 'mc', 'Which Google Docs feature allows you to view every single keystroke change made to a document since its creation?', 0),
(241, 241, 'mc', 'In Google Meet, what happens to the video recording and transcription of a meeting once it concludes?', 0),
(242, 242, 'mc', 'Which specific mode in Google Docs is best for collaborating with a partner whose changes you want to review before they become permanent?', 0),
(243, 243, 'mc', 'What happens if a contractor places a file into a business \'Shared Drive\' owned by your company?', 0),
(244, 244, 'mc', 'According to the tutorial, what is the primary role of Slack within a company or organization?', 0),
(245, 245, 'mc', 'If you are joining a company that already uses Slack, how do you typically gain access to their workspace?', 0),
(246, 246, 'mc', 'What is a specific limitation of the free version of Slack mentioned in the source material?', 0),
(247, 247, 'mc', 'How does the tutorial distinguish between \'Channels\' and \'Direct Messages\'?', 0),
(248, 248, 'mc', 'What is the benefit of using \'Threads\' when responding to a message in a channel?', 0),
(249, 249, 'mc', 'Where should a user look in the Slack interface to find mentions, reactions, or invitations to new groups?', 0),
(250, 250, 'mc', 'When creating a new channel, what is the purpose of using a \'template\' like the \'Project starter kit\'?', 0),
(251, 251, 'mc', 'According to the tutorial, how can you use the Direct Message feature to benefit your own personal workflow?', 0),
(252, 252, 'mc', 'Which specific plugin is mentioned as an example to facilitate virtual meetings directly through Slack commands?', 0),
(253, 253, 'mc', 'What is the recommended method for finding a specific file or past idea, such as \'new video ideas\', without scrolling through history?', 0),
(254, 254, 'mc', 'multiple choice option 1', 0),
(255, 255, 'tf', 'true ang sagot ', 0);

-- --------------------------------------------------------

--
-- Table structure for table `quizzes`
--

CREATE TABLE `quizzes` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `order_index` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quizzes`
--

INSERT INTO `quizzes` (`id`, `course_id`, `title`, `order_index`) VALUES
(164, 36, 'Quiz 1', 0),
(165, 36, 'Quiz 2', 1),
(166, 36, 'Quiz 3', 2),
(167, 36, 'Quiz 4', 3),
(168, 36, 'Quiz 5', 4),
(169, 36, 'Quiz 6', 5),
(170, 36, 'Quiz 7', 6),
(171, 36, 'Quiz 8', 7),
(172, 36, 'Quiz 9', 8),
(173, 36, 'Quiz 10', 9),
(174, 37, 'Quiz 1', 0),
(175, 37, 'Quiz 2', 1),
(176, 37, 'Quiz 3', 2),
(177, 37, 'Quiz 4', 3),
(178, 37, 'Quiz 5', 4),
(179, 37, 'Quiz 6', 5),
(180, 37, 'Quiz 7', 6),
(181, 37, 'Quiz 8', 7),
(182, 37, 'Quiz 9', 8),
(183, 37, 'Quiz 10', 9),
(184, 38, 'Quiz 1', 0),
(185, 38, 'Quiz 2', 1),
(186, 38, 'Quiz 3', 2),
(187, 38, 'Quiz 4', 3),
(188, 38, 'Quiz 5', 4),
(189, 38, 'Quiz 6', 5),
(190, 38, 'Quiz 7', 6),
(191, 38, 'Quiz 8', 7),
(192, 38, 'Quiz 9', 8),
(193, 38, 'Quiz 10', 9),
(194, 39, 'Quiz 1', 0),
(195, 39, 'Quiz 2', 1),
(196, 39, 'Quiz 3', 2),
(197, 39, 'Quiz 4', 3),
(198, 39, 'Quiz 5', 4),
(199, 39, 'Quiz 6', 5),
(200, 39, 'Quiz 7', 6),
(201, 39, 'Quiz 8', 7),
(202, 39, 'Quiz 9', 8),
(203, 39, 'Quiz 10', 9),
(204, 40, 'Quiz 1', 0),
(205, 40, 'Quiz 2', 1),
(206, 40, 'Quiz 3', 2),
(207, 40, 'Quiz 4', 3),
(208, 40, 'Quiz 5', 4),
(209, 40, 'Quiz 6', 5),
(210, 40, 'Quiz 7', 6),
(211, 40, 'Quiz 8', 7),
(212, 40, 'Quiz 9', 8),
(213, 40, 'Quiz 10', 9),
(214, 41, 'Quiz 1', 0),
(215, 41, 'Quiz 2', 1),
(216, 41, 'Quiz 3', 2),
(217, 41, 'Quiz 4', 3),
(218, 41, 'Quiz 5', 4),
(219, 41, 'Quiz 6', 5),
(220, 41, 'Quiz 7', 6),
(221, 41, 'Quiz 8', 7),
(222, 41, 'Quiz 9', 8),
(223, 41, 'Quiz 10', 9),
(224, 42, 'Quiz 1', 0),
(225, 42, 'Quiz 2', 1),
(226, 42, 'Quiz 3', 2),
(227, 42, 'Quiz 4', 3),
(228, 42, 'Quiz 5', 4),
(229, 42, 'Quiz 6', 5),
(230, 42, 'Quiz 7', 6),
(231, 42, 'Quiz 8', 7),
(232, 42, 'Quiz 9', 8),
(233, 42, 'Quiz 10', 9),
(234, 43, 'Quiz 1', 0),
(235, 43, 'Quiz 2', 1),
(236, 43, 'Quiz 3', 2),
(237, 43, 'Quiz 4', 3),
(238, 43, 'Quiz 5', 4),
(239, 43, 'Quiz 6', 5),
(240, 43, 'Quiz 7', 6),
(241, 43, 'Quiz 8', 7),
(242, 43, 'Quiz 9', 8),
(243, 43, 'Quiz 10', 9),
(244, 44, 'Quiz 1', 0),
(245, 44, 'Quiz 2', 1),
(246, 44, 'Quiz 3', 2),
(247, 44, 'Quiz 4', 3),
(248, 44, 'Quiz 5', 4),
(249, 44, 'Quiz 6', 5),
(250, 44, 'Quiz 7', 6),
(251, 44, 'Quiz 8', 7),
(252, 44, 'Quiz 9', 8),
(253, 44, 'Quiz 10', 9),
(254, 51, 'Quiz 1', 0),
(255, 51, 'Quiz 2', 1);

-- --------------------------------------------------------

--
-- Table structure for table `quiz_settings`
--

CREATE TABLE `quiz_settings` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `global_timer_minutes` int(11) DEFAULT 10,
  `global_timer_seconds` int(11) DEFAULT 0,
  `passing_threshold` int(11) DEFAULT 70,
  `randomize_questions` tinyint(1) NOT NULL DEFAULT 0,
  `randomize_options` tinyint(1) NOT NULL DEFAULT 0,
  `hide_correct_answers` tinyint(1) NOT NULL DEFAULT 0,
  `disable_copy` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quiz_settings`
--

INSERT INTO `quiz_settings` (`id`, `course_id`, `global_timer_minutes`, `global_timer_seconds`, `passing_threshold`, `randomize_questions`, `randomize_options`, `hide_correct_answers`, `disable_copy`) VALUES
(44, 36, 10, 0, 70, 0, 0, 1, 1),
(45, 37, 0, 10, 70, 0, 0, 1, 1),
(46, 38, 0, 10, 70, 0, 0, 1, 1),
(47, 39, 0, 10, 70, 0, 0, 1, 1),
(48, 40, 0, 0, 70, 0, 0, 1, 1),
(49, 41, 0, 10, 70, 0, 0, 1, 1),
(50, 42, 0, 10, 70, 0, 0, 1, 1),
(51, 43, 0, 10, 70, 0, 0, 1, 1),
(52, 44, 0, 10, 70, 0, 0, 1, 1),
(54, 51, 0, 10, 70, 1, 1, 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `retake_requests`
--

CREATE TABLE `retake_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `reason` text DEFAULT NULL,
  `requested_at` datetime DEFAULT current_timestamp(),
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `admin_notes` text DEFAULT NULL,
  `processed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `retake_requests`
--

INSERT INTO `retake_requests` (`id`, `user_id`, `course_id`, `reason`, `requested_at`, `status`, `admin_notes`, `processed_at`) VALUES
(2, 8, 7, NULL, '2026-04-15 05:25:08', 'approved', NULL, NULL),
(3, 8, 9, NULL, '2026-04-15 05:31:22', 'approved', NULL, NULL),
(4, 8, 10, NULL, '2026-04-15 05:49:26', 'approved', NULL, NULL),
(5, 8, 11, NULL, '2026-04-15 22:13:14', 'approved', NULL, NULL),
(6, 10, 11, NULL, '2026-04-16 01:29:44', 'approved', NULL, NULL),
(7, 8, 10, NULL, '2026-04-16 01:31:08', 'approved', NULL, NULL),
(8, 10, 11, NULL, '2026-04-16 01:31:47', 'pending', NULL, NULL),
(9, 8, 15, NULL, '2026-04-22 05:31:38', 'pending', NULL, NULL),
(10, 8, 13, NULL, '2026-04-22 05:31:45', 'pending', NULL, NULL),
(19, 8, 29, 'WALA INTERNET', '2026-05-01 00:11:30', '', '', '2026-05-04 20:26:26'),
(20, 8, 30, 'adwadkn adhauiwda dbuiawbd adbuwak', '2026-05-01 00:15:09', '', '', '2026-05-04 15:36:24'),
(21, 8, 31, 'sfsfesefse', '2026-05-04 20:33:03', 'pending', NULL, NULL),
(22, 11, 42, 'mahaba boi hahahha', '2026-05-08 15:43:18', '', 'pls do our best', '2026-05-08 15:47:24'),
(23, 11, 36, 'adadadawdaw', '2026-05-12 00:53:40', '', '', '2026-05-12 00:55:42'),
(24, 11, 37, 'i would like to retake it', '2026-05-12 01:36:36', '', '', '2026-05-12 01:37:27'),
(25, 11, 38, 'ilove yiuasda adwa', '2026-05-12 02:23:37', '', '', '2026-05-12 02:25:08'),
(26, 11, 37, 'sadadawdad adm awbdad adhbawd', '2026-05-12 02:35:36', '', '', '2026-05-12 02:36:05'),
(27, 11, 37, 'adawdadadax adwad', '2026-05-12 02:41:41', '', '', '2026-05-12 02:42:09'),
(28, 11, 37, 'adawdda djahwdvyawdkj adagdawda', '2026-05-12 02:50:14', '', '', '2026-05-12 02:50:45'),
(29, 11, 37, 'dadwadx adawdadawdawd', '2026-05-12 03:00:24', '', 'dadada', '2026-05-12 03:05:28'),
(30, 15, 44, 'dadawd adawdawdaw', '2026-05-12 03:12:36', '', 'dadawddaw', '2026-05-12 03:13:06'),
(31, 11, 37, 'adawdaw adawdawda adwad', '2026-05-12 03:17:03', '', 'dadadadadadadad', '2026-05-12 03:18:06'),
(32, 11, 37, 'sfsfa fawgfukahwkf afwafawf', '2026-05-12 03:31:02', '', 'okay', '2026-05-12 03:31:49'),
(33, 11, 37, 'dawadaugdyawd', '2026-05-12 06:33:49', '', 'dawdw', '2026-05-12 06:34:33'),
(34, 11, 37, 'asdasdadawd', '2026-05-12 06:40:03', '', 'adwad', '2026-05-12 06:40:28'),
(35, 15, 43, 'SVDV VESESFES BSGR', '2026-05-12 08:17:57', '', 'GDGD DGDR', '2026-05-12 08:18:46'),
(36, 11, 38, 'gsdggnfkslnef uens', '2026-05-12 10:30:02', '', '', '2026-05-12 10:30:43'),
(37, 21, 42, 'sorry sorry sorry', '2026-05-12 12:25:02', '', '', '2026-05-12 12:27:35'),
(38, 11, 51, 'please let me take again', '2026-06-04 17:06:59', 'rejected', '', '2026-06-04 17:14:19');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `firstname` varchar(100) NOT NULL,
  `lastname` varchar(100) DEFAULT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `employee_id` varchar(50) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `role` varchar(20) DEFAULT 'user',
  `otp_code` varchar(10) DEFAULT NULL,
  `otp_expire` datetime DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `firstname`, `lastname`, `username`, `email`, `password`, `phone`, `address`, `dob`, `position`, `employee_id`, `status`, `created_at`, `role`, `otp_code`, `otp_expire`, `profile_picture`) VALUES
(1, 'Admin', NULL, 'admin', 'admin@example.com', '$2y$10$YMiN11c9C9X8/l0E66uQpOknSz71mS/vPfSQO43peD0Lyr61qqrU6', '0000000000', 'System Admin', '2000-01-01', NULL, NULL, 'approved', '2026-03-05 21:01:40', 'admin', NULL, NULL, NULL),
(2, 'dexter', NULL, 'dondon', 'jerwingonzales1095@gmail.com', '$2y$10$IhxDxFWE6zA2hglG0x20EO8vJ0G56BbB/wL7UASiT.FTIcLQjp3wm', '0878496639', 'tetuan', '2026-03-06', NULL, NULL, 'approved', '2026-03-05 21:36:07', 'user', NULL, NULL, NULL),
(3, 'albert dela pena', NULL, 'albert', 'luchiloo10@gmail.com', '$2y$10$VFXFGWTmrbDf.cZj/PfpoOqCrqI22nccQkuYXnKYHp6m9gjSkiijW', '094 568 1385', 'san roque', '2008-03-18', 'Administrative Staff', NULL, 'approved', '2026-03-17 16:56:33', 'employee', NULL, NULL, NULL),
(4, 'shamir rasul', NULL, 'shamirrasul@gmail.com', 'shamirrasul@gmail.com', '$2y$10$zPPKmbkyU6ReW2RLwT4wDOJkj7Q4bGtpeTicfFNZZ9xc5BO9naTpO', '094 568 1385', 'baliwasan', '2008-03-18', 'HR Manager', NULL, 'approved', '2026-03-17 21:32:36', 'employee', NULL, NULL, NULL),
(5, 'joan', 'sucabo', 'sugabo@gmail.com', 'lightufury242001@gmail.com', '$2y$10$ckFfmLqyC2RRjy8.ISQSAO2KAjGDN0IO6UO0UVBOmNA7hopEcxdJO', '093 453 3213', 'manicahan', '2008-03-18', 'HR Manager', NULL, 'approved', '2026-03-18 13:59:08', 'employee', '311112', '2026-03-18 17:14:01', NULL),
(6, 'jose', 'antoa', 'antao@gmail.com', 'antao@gmail.com', '$2y$10$.J5FBoqnng7vk/w/gvggfuJiZq/Oc6HrXwaea0H6P3e15kfCUPoVC', '099 784 6469', 'guiwan', '2001-09-11', 'Quality Assurance', '', 'approved', '2026-03-18 18:08:29', 'employee', NULL, NULL, NULL),
(7, 'amier', 'jose', 'jose@gmail.com', 'jose@gmail.com', '$2y$10$Xg.fL5bVxfEZvYRY50D4v.NeXBRCZ3.2rjXTtpZvcf6lO0dggl1mO', '094 568 1385', 'tetuan', '2008-03-19', 'Quality Assurance', NULL, 'pending', '2026-03-18 21:37:56', 'employee', NULL, NULL, NULL),
(8, 'karl', 'antonio', 'antonio', 'antoniosteven664@gmail.com', '$2y$10$Mumo4Af43PKD9DqA/wKzOeOuGPQ0x9gh2cYNjPEhn6dvWHwcTPq2m', '093 453 3213', 'san roque', '2008-03-27', 'Software Developer', '76', 'approved', '2026-03-27 11:29:05', 'admin', NULL, NULL, 'user_8_1776864280.jpg'),
(9, 'Reizel Aimee', 'Fernando', 'aimeesssyouu', 'ms.maxpain@gmail.com', '$2y$10$yk1KOJq.dBESKi8RTsmune0.bQ/p4cE0itnkirWgu6c.7rJO.Z5Oy', '099 784 6469', 'barangay langit 123', '2004-06-26', 'IT Support', NULL, 'approved', '2026-04-13 16:20:54', 'employee', NULL, NULL, NULL),
(11, 'Jane', 'Corpin', 'jane', 'recruitment@upstaff.com.au', '$2y$10$03eWt2Vm1WjER85KvvvHVevd.ceVt/TpF7SZ6McWiLYUIXm7HmfYO', '123 456 789', 'Zamboanga City', '2008-04-21', 'HR Manager', NULL, 'approved', '2026-04-27 08:33:12', 'employee', NULL, NULL, 'user_11_1777278935.jpg'),
(13, 'ANNA', 'JOE', 'ANNA', 'anna@gmail.com', '$2y$10$zHusAC7zBPMXw6HP.jwmUOBU4orNVwZEhMNB6t/oLBAi8wD4j3SuC', '093 453 3213', 'manicahan', '2008-04-28', 'E-commerce Virtual Assistant', NULL, 'approved', '2026-04-28 12:00:39', 'employee', NULL, NULL, 'user_13_1777378747.jpg'),
(15, 'romyl', 'bieber', 'mr daks', 'romsofficial18@gmail.com', '$2y$10$JWwS6xacrYAuN/8zCtgufurByaSGzMpm3QQBfQit.2qg29o/P1ICW', '099 761 3769', 'san roque', '2008-04-29', 'Human Resources Associate', NULL, 'approved', '2026-04-29 07:17:21', 'employee', NULL, NULL, 'user_15_1777447149.jpg'),
(16, 'kyle', 'jays', 'jays', 'jays@gmail.com', '$2y$10$B7pTtyk3cmc2Ijowqp6dVuhUQqEFaePuCS8kZkUHpFtHuE8rdqn0a', '099 784 6469', 'guiwan', '2008-04-29', 'Recruitment Officer', NULL, 'pending', '2026-04-29 12:05:08', 'employee', NULL, NULL, NULL),
(17, 'mark', 'suksukbert', 'markie', 'markie@gmail.com', '$2y$10$eUexgdNQokouWt6LRu/NMuJAYYEJhR2Ojp8YSE0adWAj9o2ScgXmS', '099 784 6469', 'tetuan', '2008-04-29', 'Executive Assistant', NULL, 'pending', '2026-04-30 14:19:06', 'employee', NULL, NULL, NULL),
(18, 'night', 'light', 'lgiht', 'night@123gmail.com', '$2y$10$4Px1/Mc1VogMBEgpKopaEufq2b5Zb8GGnGNO6ACUc/2Xt3l9wM2ma', '099 784 6469', '7000 san roque', '2008-05-01', 'E-commerce Virtual Assistant', NULL, 'pending', '2026-04-30 16:59:14', 'employee', NULL, NULL, NULL),
(19, 'hermes', 'hipolito', 'hermes', 'hipolito@gmailcom', '$2y$10$3RJswaLmRt901PBHPCJ0WOdtVqOd6cgiB6.EuK0X3uT.WVI/HHvNi', '093 453 3213', 'baliwasan', '2008-05-01', 'Administrative Assistant – Skip Tracing', NULL, 'pending', '2026-04-30 17:10:36', 'employee', NULL, NULL, NULL),
(20, 'jayson', 'montenegro', 'montenegro', '@jayjay123.com', '$2y$10$/xJucbI5t6DyMxUUSOwz1ejJFRrgJNiOPaHihj.eaqdBK1uDjjz4i', '099 784 6469', 'manicahan', '2008-05-01', 'Administrative Assistant – Skip Tracing', NULL, 'pending', '2026-04-30 17:21:17', 'employee', NULL, NULL, NULL),
(21, 'ariel', 'gorden', 'ariel', 'ariel@upstaff.com.au', '$2y$10$Jn.XHMzMz9mFd12a3CI3IejlSWFG1YKCLBjaldId/KQFHTRdVHKPu', '000 000 0000', 'san roque', '1999-10-12', 'Virtual Assistant', NULL, 'approved', '2026-05-12 04:17:11', 'employee', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_courses`
--

CREATE TABLE `user_courses` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `video_id` int(11) DEFAULT NULL,
  `progress` int(11) DEFAULT 0,
  `final_score` decimal(5,2) DEFAULT NULL,
  `status` enum('not_started','in_progress','completed') DEFAULT 'not_started',
  `pass_status` enum('pending','passed','failed') DEFAULT 'pending',
  `started_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `last_accessed` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_courses`
--

INSERT INTO `user_courses` (`id`, `user_id`, `course_id`, `video_id`, `progress`, `final_score`, `status`, `pass_status`, `started_at`, `completed_at`, `last_accessed`) VALUES
(39, 11, 44, NULL, 100, NULL, 'completed', 'passed', '2026-05-08 15:35:09', NULL, '2026-05-08 15:36:41'),
(40, 11, 42, NULL, 100, NULL, 'completed', 'passed', '2026-05-08 15:38:23', NULL, '2026-05-11 16:00:57'),
(41, 11, 43, NULL, 100, NULL, 'completed', 'passed', '2026-05-11 15:57:00', NULL, '2026-05-11 15:59:08'),
(43, 11, 36, NULL, 100, NULL, 'completed', 'passed', '2026-05-12 00:49:40', NULL, '2026-05-12 01:29:08'),
(45, 11, 39, NULL, 100, NULL, 'completed', 'passed', '2026-05-12 01:39:16', NULL, '2026-05-12 01:40:38'),
(47, 15, 44, NULL, 100, NULL, 'completed', 'passed', '2026-05-12 03:11:28', NULL, '2026-05-12 03:14:58'),
(48, 15, 43, NULL, 100, NULL, 'completed', 'passed', '2026-05-12 08:15:57', NULL, '2026-05-12 08:21:28'),
(50, 11, 38, NULL, 100, NULL, 'completed', 'pending', '2026-05-12 10:28:26', NULL, '2026-05-12 10:32:37'),
(51, 21, 42, NULL, 100, NULL, 'completed', 'pending', '2026-05-12 12:22:02', NULL, '2026-05-12 12:28:38'),
(52, 21, 43, NULL, 100, NULL, 'completed', 'pending', '2026-05-12 12:29:27', NULL, '2026-05-12 12:30:27'),
(53, 21, 44, NULL, 100, NULL, 'completed', 'pending', '2026-05-12 12:30:42', NULL, '2026-05-12 12:32:03'),
(55, 13, 43, NULL, 100, 40.00, 'completed', 'failed', '2026-06-03 00:43:19', NULL, '2026-06-03 00:44:22'),
(56, 11, 51, NULL, 100, 50.00, 'completed', 'failed', '2026-06-04 17:05:52', NULL, '2026-06-04 17:06:20'),
(57, 21, 51, NULL, 100, 100.00, 'completed', 'passed', '2026-06-09 13:40:15', NULL, '2026-06-09 13:40:27'),
(58, 21, 36, NULL, 100, 30.00, 'completed', 'failed', '2026-06-09 13:47:55', NULL, '2026-06-09 13:48:55'),
(59, 21, 37, NULL, 100, 20.00, 'completed', 'failed', '2026-06-09 14:05:16', NULL, '2026-06-09 14:06:10'),
(60, 21, 38, NULL, 100, 10.00, 'completed', 'failed', '2026-06-09 15:05:02', NULL, '2026-06-09 15:06:12');

-- --------------------------------------------------------

--
-- Table structure for table `user_quiz_answers`
--

CREATE TABLE `user_quiz_answers` (
  `id` int(11) NOT NULL,
  `attempt_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `quiz_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `selected_option` int(11) DEFAULT NULL,
  `is_correct` tinyint(1) DEFAULT 0,
  `answered_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_quiz_answers`
--

INSERT INTO `user_quiz_answers` (`id`, `attempt_id`, `user_id`, `quiz_id`, `question_id`, `selected_option`, `is_correct`, `answered_at`) VALUES
(197, 197, 11, 244, 244, 3, 1, '2026-05-08 15:35:38'),
(198, 198, 11, 245, 245, 0, 1, '2026-05-08 15:35:44'),
(199, 199, 11, 246, 246, 1, 1, '2026-05-08 15:35:51'),
(200, 200, 11, 247, 247, 3, 1, '2026-05-08 15:36:04'),
(201, 201, 11, 248, 248, 0, 1, '2026-05-08 15:36:11'),
(202, 202, 11, 249, 249, 3, 1, '2026-05-08 15:36:17'),
(203, 203, 11, 250, 250, 3, 1, '2026-05-08 15:36:24'),
(204, 204, 11, 251, 251, 0, 1, '2026-05-08 15:36:30'),
(205, 205, 11, 252, 252, 0, 1, '2026-05-08 15:36:36'),
(206, 206, 11, 253, 253, 2, 1, '2026-05-08 15:36:41'),
(217, 217, 11, 234, 234, 0, 1, '2026-05-11 15:58:01'),
(218, 218, 11, 235, 235, 0, 1, '2026-05-11 15:58:07'),
(219, 219, 11, 236, 236, 0, 1, '2026-05-11 15:58:13'),
(220, 220, 11, 237, 237, 1, 1, '2026-05-11 15:58:19'),
(221, 221, 11, 238, 238, 0, 1, '2026-05-11 15:58:27'),
(222, 222, 11, 239, 239, 0, 1, '2026-05-11 15:58:38'),
(223, 223, 11, 240, 240, 0, 1, '2026-05-11 15:58:47'),
(224, 224, 11, 241, 241, 3, 1, '2026-05-11 15:58:53'),
(225, 225, 11, 242, 242, 1, 0, '2026-05-11 15:59:02'),
(226, 226, 11, 243, 243, 3, 1, '2026-05-11 15:59:08'),
(227, 227, 11, 224, 224, 1, 1, '2026-05-11 15:59:50'),
(228, 228, 11, 225, 225, 1, 1, '2026-05-11 15:59:58'),
(229, 229, 11, 226, 226, 2, 1, '2026-05-11 16:00:04'),
(230, 230, 11, 227, 227, 0, 1, '2026-05-11 16:00:11'),
(231, 231, 11, 228, 228, 3, 1, '2026-05-11 16:00:22'),
(232, 232, 11, 229, 229, 0, 1, '2026-05-11 16:00:28'),
(233, 233, 11, 230, 230, 1, 1, '2026-05-11 16:00:38'),
(234, 234, 11, 231, 231, 3, 1, '2026-05-11 16:00:44'),
(235, 235, 11, 232, 232, 0, 1, '2026-05-11 16:00:51'),
(236, 236, 11, 233, 233, 1, 1, '2026-05-11 16:00:57'),
(267, 267, 11, 164, 164, 3, 1, '2026-05-12 01:28:10'),
(268, 268, 11, 165, 165, 0, 1, '2026-05-12 01:28:18'),
(269, 269, 11, 166, 166, 3, 1, '2026-05-12 01:28:24'),
(270, 270, 11, 167, 167, 0, 1, '2026-05-12 01:28:29'),
(271, 271, 11, 168, 168, 2, 1, '2026-05-12 01:28:35'),
(272, 272, 11, 169, 169, 1, 1, '2026-05-12 01:28:41'),
(273, 273, 11, 170, 170, 0, 1, '2026-05-12 01:28:48'),
(274, 274, 11, 171, 171, 0, 1, '2026-05-12 01:28:55'),
(275, 275, 11, 172, 172, 2, 1, '2026-05-12 01:29:01'),
(276, 276, 11, 173, 173, 1, 1, '2026-05-12 01:29:08'),
(287, 287, 11, 194, 194, 2, 1, '2026-05-12 01:39:23'),
(288, 288, 11, 195, 195, 1, 1, '2026-05-12 01:39:29'),
(289, 289, 11, 196, 196, 0, 1, '2026-05-12 01:39:36'),
(290, 290, 11, 197, 197, 2, 1, '2026-05-12 01:39:41'),
(291, 291, 11, 198, 198, 0, 1, '2026-05-12 01:39:50'),
(292, 292, 11, 199, 199, 0, 1, '2026-05-12 01:40:01'),
(293, 293, 11, 200, 200, 3, 1, '2026-05-12 01:40:06'),
(294, 294, 11, 201, 201, 1, 1, '2026-05-12 01:40:15'),
(295, 295, 11, 202, 202, 0, 1, '2026-05-12 01:40:25'),
(296, 296, 11, 203, 203, 1, 1, '2026-05-12 01:40:38'),
(357, 357, 15, 244, 244, 3, 1, '2026-05-12 03:14:00'),
(358, 358, 15, 245, 245, 0, 1, '2026-05-12 03:14:05'),
(359, 359, 15, 246, 246, 1, 1, '2026-05-12 03:14:11'),
(360, 360, 15, 247, 247, 3, 1, '2026-05-12 03:14:18'),
(361, 361, 15, 248, 248, 0, 1, '2026-05-12 03:14:23'),
(362, 362, 15, 249, 249, 3, 1, '2026-05-12 03:14:30'),
(363, 363, 15, 250, 250, 3, 1, '2026-05-12 03:14:36'),
(364, 364, 15, 251, 251, 0, 1, '2026-05-12 03:14:47'),
(365, 365, 15, 252, 252, 0, 1, '2026-05-12 03:14:52'),
(366, 366, 15, 253, 253, 2, 1, '2026-05-12 03:14:58'),
(397, 397, 11, 174, 174, 1, 1, '2026-05-12 06:40:46'),
(398, 398, 11, 175, 175, 3, 1, '2026-05-12 06:40:55'),
(399, 399, 11, 176, 176, 3, 1, '2026-05-12 06:41:01'),
(400, 400, 11, 177, 177, 2, 0, '2026-05-12 06:41:08'),
(401, 401, 11, 178, 178, 1, 1, '2026-05-12 06:41:14'),
(402, 402, 11, 179, 179, 0, 1, '2026-05-12 06:41:20'),
(403, 403, 11, 180, 180, 0, 1, '2026-05-12 06:41:26'),
(404, 404, 11, 181, 181, 1, 1, '2026-05-12 06:41:37'),
(405, 405, 11, 182, 182, 2, 1, '2026-05-12 06:41:44'),
(406, 406, 11, 183, 183, 3, 1, '2026-05-12 06:41:50'),
(417, 417, 15, 234, 234, 0, 1, '2026-05-12 08:20:33'),
(418, 418, 15, 235, 235, 0, 1, '2026-05-12 08:20:39'),
(419, 419, 15, 236, 236, 0, 1, '2026-05-12 08:20:45'),
(420, 420, 15, 237, 237, 1, 1, '2026-05-12 08:20:51'),
(421, 421, 15, 238, 238, 0, 1, '2026-05-12 08:20:57'),
(422, 422, 15, 239, 239, 0, 1, '2026-05-12 08:21:03'),
(423, 423, 15, 240, 240, 0, 1, '2026-05-12 08:21:10'),
(424, 424, 15, 241, 241, 3, 1, '2026-05-12 08:21:16'),
(425, 425, 15, 242, 242, 3, 0, '2026-05-12 08:21:21'),
(426, 426, 15, 243, 243, 3, 1, '2026-05-12 08:21:28'),
(427, 427, 15, 224, 224, 0, 0, '2026-05-12 09:11:42'),
(428, 428, 15, 225, 225, 0, 0, '2026-05-12 09:11:48'),
(429, 429, 15, 226, 226, 0, 0, '2026-05-12 09:11:57'),
(430, 430, 15, 227, 227, 0, 1, '2026-05-12 09:12:05'),
(431, 431, 15, 228, 228, 0, 0, '2026-05-12 09:12:12'),
(432, 432, 15, 229, 229, 0, 1, '2026-05-12 09:12:22'),
(433, 433, 15, 230, 230, 0, 0, '2026-05-12 09:12:31'),
(434, 434, 15, 231, 231, 0, 0, '2026-05-12 09:12:39'),
(435, 435, 15, 232, 232, 0, 1, '2026-05-12 09:12:45'),
(436, 436, 15, 233, 233, 0, 0, '2026-05-12 09:12:52'),
(447, 447, 11, 184, 184, 2, 1, '2026-05-12 10:31:37'),
(448, 448, 11, 185, 185, 2, 1, '2026-05-12 10:31:43'),
(449, 449, 11, 186, 186, 0, 1, '2026-05-12 10:31:48'),
(450, 450, 11, 187, 187, 2, 1, '2026-05-12 10:31:54'),
(451, 451, 11, 188, 188, 3, 1, '2026-05-12 10:32:01'),
(452, 452, 11, 189, 189, 3, 1, '2026-05-12 10:32:07'),
(453, 453, 11, 190, 190, 3, 1, '2026-05-12 10:32:14'),
(454, 454, 11, 191, 191, 0, 1, '2026-05-12 10:32:20'),
(455, 455, 11, 192, 192, 0, 1, '2026-05-12 10:32:27'),
(456, 456, 11, 193, 193, 0, 1, '2026-05-12 10:32:37'),
(467, 467, 21, 224, 224, 1, 1, '2026-05-12 12:27:45'),
(468, 468, 21, 225, 225, 1, 1, '2026-05-12 12:27:51'),
(469, 469, 21, 226, 226, 2, 1, '2026-05-12 12:27:56'),
(470, 470, 21, 227, 227, 0, 1, '2026-05-12 12:28:02'),
(471, 471, 21, 228, 228, 3, 1, '2026-05-12 12:28:08'),
(472, 472, 21, 229, 229, 0, 1, '2026-05-12 12:28:13'),
(473, 473, 21, 230, 230, 1, 1, '2026-05-12 12:28:21'),
(474, 474, 21, 231, 231, 3, 1, '2026-05-12 12:28:27'),
(475, 475, 21, 232, 232, 0, 1, '2026-05-12 12:28:33'),
(476, 476, 21, 233, 233, 1, 1, '2026-05-12 12:28:38'),
(477, 477, 21, 234, 234, 0, 1, '2026-05-12 12:29:34'),
(478, 478, 21, 235, 235, 0, 1, '2026-05-12 12:29:40'),
(479, 479, 21, 236, 236, 0, 1, '2026-05-12 12:29:45'),
(480, 480, 21, 237, 237, 1, 1, '2026-05-12 12:29:51'),
(481, 481, 21, 238, 238, 0, 1, '2026-05-12 12:29:56'),
(482, 482, 21, 239, 239, 0, 1, '2026-05-12 12:30:02'),
(483, 483, 21, 240, 240, 0, 1, '2026-05-12 12:30:07'),
(484, 484, 21, 241, 241, 3, 1, '2026-05-12 12:30:13'),
(485, 485, 21, 242, 242, 1, 0, '2026-05-12 12:30:20'),
(486, 486, 21, 243, 243, 3, 1, '2026-05-12 12:30:27'),
(487, 487, 21, 244, 244, 3, 1, '2026-05-12 12:31:07'),
(488, 488, 21, 245, 245, 0, 1, '2026-05-12 12:31:13'),
(489, 489, 21, 246, 246, 1, 1, '2026-05-12 12:31:20'),
(490, 490, 21, 247, 247, 3, 1, '2026-05-12 12:31:27'),
(491, 491, 21, 248, 248, 0, 1, '2026-05-12 12:31:32'),
(492, 492, 21, 249, 249, 3, 1, '2026-05-12 12:31:38'),
(493, 493, 21, 250, 250, 3, 1, '2026-05-12 12:31:47'),
(494, 494, 21, 251, 251, 0, 1, '2026-05-12 12:31:53'),
(495, 495, 21, 252, 252, 0, 1, '2026-05-12 12:31:58'),
(496, 496, 21, 253, 253, 2, 1, '2026-05-12 12:32:03'),
(497, 497, 13, 244, 244, 1, 0, '2026-06-03 00:19:54'),
(498, 498, 13, 245, 245, 0, 1, '2026-06-03 00:20:00'),
(499, 499, 13, 246, 246, NULL, 0, '2026-06-03 00:20:13'),
(500, 500, 13, 247, 247, 0, 0, '2026-06-03 00:20:19'),
(501, 501, 13, 248, 248, 0, 1, '2026-06-03 00:20:25'),
(502, 502, 13, 249, 249, 0, 0, '2026-06-03 00:20:30'),
(503, 503, 13, 250, 250, 0, 0, '2026-06-03 00:20:38'),
(504, 504, 13, 251, 251, 0, 1, '2026-06-03 00:20:44'),
(505, 505, 13, 252, 252, NULL, 0, '2026-06-03 00:20:57'),
(506, 506, 13, 253, 253, 0, 0, '2026-06-03 00:21:03'),
(507, 507, 13, 234, 234, 1, 0, '2026-06-03 00:43:28'),
(508, 508, 13, 235, 235, 0, 1, '2026-06-03 00:43:33'),
(509, 509, 13, 236, 236, 1, 0, '2026-06-03 00:43:39'),
(510, 510, 13, 237, 237, 1, 1, '2026-06-03 00:43:45'),
(511, 511, 13, 238, 238, 0, 1, '2026-06-03 00:43:50'),
(512, 512, 13, 239, 239, 1, 0, '2026-06-03 00:43:56'),
(513, 513, 13, 240, 240, 1, 0, '2026-06-03 00:44:02'),
(514, 514, 13, 241, 241, 0, 0, '2026-06-03 00:44:11'),
(515, 515, 13, 242, 242, 0, 1, '2026-06-03 00:44:16'),
(516, 516, 13, 243, 243, 1, 0, '2026-06-03 00:44:22'),
(517, 517, 11, 254, 254, NULL, 0, '2026-06-04 17:06:13'),
(518, 518, 11, 255, 255, 0, 1, '2026-06-04 17:06:20'),
(519, 519, 21, 254, 254, 0, 1, '2026-06-09 13:40:20'),
(520, 520, 21, 255, 255, 0, 1, '2026-06-09 13:40:27'),
(521, 521, 21, 164, 164, 0, 0, '2026-06-09 13:48:01'),
(522, 522, 21, 165, 165, 0, 1, '2026-06-09 13:48:07'),
(523, 523, 21, 166, 166, 1, 0, '2026-06-09 13:48:14'),
(524, 524, 21, 167, 167, 1, 0, '2026-06-09 13:48:20'),
(525, 525, 21, 168, 168, 1, 0, '2026-06-09 13:48:25'),
(526, 526, 21, 169, 169, 1, 1, '2026-06-09 13:48:31'),
(527, 527, 21, 170, 170, 1, 0, '2026-06-09 13:48:38'),
(528, 528, 21, 171, 171, 1, 0, '2026-06-09 13:48:44'),
(529, 529, 21, 172, 172, 2, 1, '2026-06-09 13:48:49'),
(530, 530, 21, 173, 173, 2, 0, '2026-06-09 13:48:55'),
(531, 531, 21, 174, 174, 0, 0, '2026-06-09 14:05:21'),
(532, 532, 21, 175, 175, 2, 0, '2026-06-09 14:05:27'),
(533, 533, 21, 176, 176, 1, 0, '2026-06-09 14:05:32'),
(534, 534, 21, 177, 177, 1, 0, '2026-06-09 14:05:38'),
(535, 535, 21, 178, 178, 1, 1, '2026-06-09 14:05:43'),
(536, 536, 21, 179, 179, 1, 0, '2026-06-09 14:05:49'),
(537, 537, 21, 180, 180, 1, 0, '2026-06-09 14:05:54'),
(538, 538, 21, 181, 181, 1, 1, '2026-06-09 14:05:59'),
(539, 539, 21, 182, 182, 1, 0, '2026-06-09 14:06:05'),
(540, 540, 21, 183, 183, 1, 0, '2026-06-09 14:06:10'),
(541, 541, 21, 184, 184, 0, 0, '2026-06-09 15:05:08'),
(542, 542, 21, 185, 185, 0, 0, '2026-06-09 15:05:14'),
(543, 543, 21, 186, 186, 0, 1, '2026-06-09 15:05:22'),
(544, 544, 21, 187, 187, 0, 0, '2026-06-09 15:05:28'),
(545, 545, 21, 188, 188, 2, 0, '2026-06-09 15:05:34'),
(546, 546, 21, 189, 189, 1, 0, '2026-06-09 15:05:43'),
(547, 547, 21, 190, 190, 2, 0, '2026-06-09 15:05:51'),
(548, 548, 21, 191, 191, 2, 0, '2026-06-09 15:05:57'),
(549, 549, 21, 192, 192, 2, 0, '2026-06-09 15:06:06'),
(550, 550, 21, 193, 193, 2, 0, '2026-06-09 15:06:12');

-- --------------------------------------------------------

--
-- Table structure for table `user_quiz_attempts`
--

CREATE TABLE `user_quiz_attempts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `quiz_id` int(11) NOT NULL,
  `score` int(11) DEFAULT 0,
  `status` enum('pending','passed','failed') DEFAULT 'pending',
  `answers_data` text DEFAULT NULL,
  `time_taken` int(11) DEFAULT NULL,
  `attempted_at` datetime DEFAULT current_timestamp(),
  `completed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_quiz_attempts`
--

INSERT INTO `user_quiz_attempts` (`id`, `user_id`, `quiz_id`, `score`, `status`, `answers_data`, `time_taken`, `attempted_at`, `completed_at`) VALUES
(197, 11, 244, 100, 'passed', '[{\"question_id\":244,\"question_text\":\"According to the tutorial, what is the primary role of Slack within a company or organization?\",\"user_answer\":3,\"is_correct\":true,\"correct_answer\":\"A central hub for communication, files, and project organization.\"}]', 5, '2026-05-08 15:35:38', '2026-05-08 15:35:38'),
(198, 11, 245, 100, 'passed', '[{\"question_id\":245,\"question_text\":\"If you are joining a company that already uses Slack, how do you typically gain access to their workspace?\",\"user_answer\":0,\"is_correct\":true,\"correct_answer\":\"By following the prompts in an invitation email sent by the company.\"}]', 2, '2026-05-08 15:35:44', '2026-05-08 15:35:44'),
(199, 11, 246, 100, 'passed', '[{\"question_id\":246,\"question_text\":\"What is a specific limitation of the free version of Slack mentioned in the source material?\",\"user_answer\":1,\"is_correct\":true,\"correct_answer\":\"Messages are lost or hidden after approximately 90 days.\"}]', 4, '2026-05-08 15:35:51', '2026-05-08 15:35:51'),
(200, 11, 247, 100, 'passed', '[{\"question_id\":247,\"question_text\":\"How does the tutorial distinguish between \'Channels\' and \'Direct Messages\'?\",\"user_answer\":3,\"is_correct\":true,\"correct_answer\":\"Channels function as individual chat rooms for groups, while Direct Messages are one-on-one conversations.\"}]', 10, '2026-05-08 15:36:04', '2026-05-08 15:36:04'),
(201, 11, 248, 100, 'passed', '[{\"question_id\":248,\"question_text\":\"What is the benefit of using \'Threads\' when responding to a message in a channel?\",\"user_answer\":0,\"is_correct\":true,\"correct_answer\":\"It keeps related messages organized and prevents the main channel from becoming cluttered.\"}]', 3, '2026-05-08 15:36:11', '2026-05-08 15:36:11'),
(202, 11, 249, 100, 'passed', '[{\"question_id\":249,\"question_text\":\"Where should a user look in the Slack interface to find mentions, reactions, or invitations to new groups?\",\"user_answer\":3,\"is_correct\":true,\"correct_answer\":\"The Activity section on the sidebar.\"}]', 2, '2026-05-08 15:36:17', '2026-05-08 15:36:17'),
(203, 11, 250, 100, 'passed', '[{\"question_id\":250,\"question_text\":\"When creating a new channel, what is the purpose of using a \'template\' like the \'Project starter kit\'?\",\"user_answer\":3,\"is_correct\":true,\"correct_answer\":\"To provide a pre-laid out structure specifically tailored for a project\'s needs.\"}]', 3, '2026-05-08 15:36:24', '2026-05-08 15:36:24'),
(204, 11, 251, 100, 'passed', '[{\"question_id\":251,\"question_text\":\"According to the tutorial, how can you use the Direct Message feature to benefit your own personal workflow?\",\"user_answer\":0,\"is_correct\":true,\"correct_answer\":\"By messaging yourself to jot down notes and private thoughts.\"}]', 3, '2026-05-08 15:36:30', '2026-05-08 15:36:30'),
(205, 11, 252, 100, 'passed', '[{\"question_id\":252,\"question_text\":\"Which specific plugin is mentioned as an example to facilitate virtual meetings directly through Slack commands?\",\"user_answer\":0,\"is_correct\":true,\"correct_answer\":\"Zoom\"}]', 2, '2026-05-08 15:36:36', '2026-05-08 15:36:36'),
(206, 11, 253, 100, 'passed', '[{\"question_id\":253,\"question_text\":\"What is the recommended method for finding a specific file or past idea, such as \'new video ideas\', without scrolling through history?\",\"user_answer\":2,\"is_correct\":true,\"correct_answer\":\"Using the search bar to look for keywords or file names.\"}]', 2, '2026-05-08 15:36:41', '2026-05-08 15:36:41'),
(217, 11, 234, 100, 'passed', '[{\"question_id\":234,\"question_text\":\"What is the primary technical difference between a standard consumer Gmail account and a Google Workspace account?\",\"user_answer\":0,\"is_correct\":true,\"correct_answer\":\"The Workspace account allows for the use of a custom business domain name. \"}]', 2, '2026-05-11 15:58:01', '2026-05-11 15:58:01'),
(218, 11, 235, 100, 'passed', '[{\"question_id\":235,\"question_text\":\"Which administrative feature allows a business owner to manage settings for staff members connecting to resources via a specific web browser?\",\"user_answer\":0,\"is_correct\":true,\"correct_answer\":\"Chrome policies\"}]', 2, '2026-05-11 15:58:07', '2026-05-11 15:58:07'),
(219, 11, 236, 100, 'passed', '[{\"question_id\":236,\"question_text\":\"Why is the \'Business Standard\' plan recommended over the \'Basic\' plan for most businesses?\",\"user_answer\":0,\"is_correct\":true,\"correct_answer\":\"It includes features like Shared Drives and the ability to record Google Meet sessions. \"}]', 2, '2026-05-11 15:58:13', '2026-05-11 15:58:13'),
(220, 11, 237, 100, 'passed', '[{\"question_id\":237,\"question_text\":\"During the technical setup of Google Workspace, which DNS setting is critical to prevent your emails from being flagged as spam?\",\"user_answer\":1,\"is_correct\":true,\"correct_answer\":\"SPF \"}]', 3, '2026-05-11 15:58:19', '2026-05-11 15:58:19'),
(221, 11, 238, 100, 'passed', '[{\"question_id\":238,\"question_text\":\"How does the modern Google Drive desktop application manage local storage space efficiently?\",\"user_answer\":0,\"is_correct\":true,\"correct_answer\":\"It removes local copies of files that haven\'t been used in a while, keeping them in the cloud. \"}]', 4, '2026-05-11 15:58:27', '2026-05-11 15:58:27'),
(222, 11, 239, 100, 'passed', '[{\"question_id\":239,\"question_text\":\"What is a major risk of using a personal Gmail account when collaborating with external contractors?\",\"user_answer\":0,\"is_correct\":true,\"correct_answer\":\"The contractor remains the owner of the files they create and share with you. \"}]', 8, '2026-05-11 15:58:38', '2026-05-11 15:58:38'),
(223, 11, 240, 100, 'passed', '[{\"question_id\":240,\"question_text\":\"Which Google Docs feature allows you to view every single keystroke change made to a document since its creation?\",\"user_answer\":0,\"is_correct\":true,\"correct_answer\":\"Revision history \"}]', 5, '2026-05-11 15:58:47', '2026-05-11 15:58:47'),
(224, 11, 241, 100, 'passed', '[{\"question_id\":241,\"question_text\":\"In Google Meet, what happens to the video recording and transcription of a meeting once it concludes?\",\"user_answer\":3,\"is_correct\":true,\"correct_answer\":\"It is automatically saved into a folder in your Google Drive. \"}]', 3, '2026-05-11 15:58:53', '2026-05-11 15:58:53'),
(225, 11, 242, 0, 'failed', '[{\"question_id\":242,\"question_text\":\"Which specific mode in Google Docs is best for collaborating with a partner whose changes you want to review before they become permanent?\",\"user_answer\":1,\"is_correct\":false,\"correct_answer\":\"Restricted mode\"}]', 5, '2026-05-11 15:59:02', '2026-05-11 15:59:02'),
(226, 11, 243, 100, 'passed', '[{\"question_id\":243,\"question_text\":\"What happens if a contractor places a file into a business \'Shared Drive\' owned by your company?\",\"user_answer\":3,\"is_correct\":true,\"correct_answer\":\"The contractor receives a prompt to transfer ownership to the business. \"}]', 3, '2026-05-11 15:59:08', '2026-05-11 15:59:08'),
(227, 11, 224, 100, 'passed', '[{\"question_id\":224,\"question_text\":\"What is the primary function of the \'dialogue launcher\' (also referred to as a launch button) found in some groups on the ribbon?\",\"user_answer\":1,\"is_correct\":true,\"correct_answer\":\"It opens a window with additional features that could not fit in the ribbon space.\"}]', 5, '2026-05-11 15:59:50', '2026-05-11 15:59:50'),
(228, 11, 225, 100, 'passed', '[{\"question_id\":225,\"question_text\":\"In Microsoft Word, what is the fastest way to select an entire paragraph using only the mouse?\",\"user_answer\":1,\"is_correct\":true,\"correct_answer\":\"Triple-click anywhere within the paragraph. \"}]', 4, '2026-05-11 15:59:58', '2026-05-11 15:59:58'),
(229, 11, 226, 100, 'passed', '[{\"question_id\":226,\"question_text\":\"When saving a document for the very first time, why does Word automatically default to \'Save As\' even if you click \'Save\'?\",\"user_answer\":2,\"is_correct\":true,\"correct_answer\":\"Because the document has not yet been assigned a file name or a storage location. \"}]', 3, '2026-05-11 16:00:04', '2026-05-11 16:00:04'),
(230, 11, 227, 100, 'passed', '[{\"question_id\":227,\"question_text\":\"If a user is in \'Focus Mode\' and wants to return to the standard \'Print Layout\' view, which keyboard key should they press?\",\"user_answer\":0,\"is_correct\":true,\"correct_answer\":\"Escape \"}]', 4, '2026-05-11 16:00:11', '2026-05-11 16:00:11'),
(231, 11, 228, 100, 'passed', '[{\"question_id\":228,\"question_text\":\"Which keyboard shortcut is used to \'Redo\' an action that was previously undone?\",\"user_answer\":3,\"is_correct\":true,\"correct_answer\":\"Ctrl + Y\"}]', 7, '2026-05-11 16:00:22', '2026-05-11 16:00:22'),
(232, 11, 229, 100, 'passed', '[{\"question_id\":229,\"question_text\":\" What is the purpose of the \'Match Case\' option within the \'Find and Replace\' tool?\",\"user_answer\":0,\"is_correct\":true,\"correct_answer\":\"It ensures the search only finds words with the exact same capitalization as the search term.\"}]', 3, '2026-05-11 16:00:28', '2026-05-11 16:00:28'),
(233, 11, 230, 100, 'passed', '[{\"question_id\":230,\"question_text\":\"When working in a table, what happens if you press the \'Tab\' key while your cursor is in the very last cell (bottom right)?\",\"user_answer\":1,\"is_correct\":true,\"correct_answer\":\"Word automatically adds a new blank row to the bottom of the table. \"}]', 6, '2026-05-11 16:00:38', '2026-05-11 16:00:38'),
(234, 11, 231, 100, 'passed', '[{\"question_id\":231,\"question_text\":\"Where can you find the options to change line spacing from \'Multiple\' to \'Double\' in a document?\",\"user_answer\":3,\"is_correct\":true,\"correct_answer\":\"In the Paragraph settings dialogue box, accessible via the launch button on the Home tab.\"}]', 3, '2026-05-11 16:00:44', '2026-05-11 16:00:44'),
(235, 11, 232, 100, 'passed', '[{\"question_id\":232,\"question_text\":\"Which of the following describes the \'Autocorrect\' tool\'s ability to handle custom abbreviations?\",\"user_answer\":0,\"is_correct\":true,\"correct_answer\":\"It can be configured to expand a short code (like \'T4TAS\') into a much longer predefined phrase. \"}]', 3, '2026-05-11 16:00:51', '2026-05-11 16:00:51'),
(236, 11, 233, 100, 'passed', '[{\"question_id\":233,\"question_text\":\"Why is using a \'Page Break\' (found in the Layout tab) preferable to pressing the \'Enter\' key multiple times to start a new page?\",\"user_answer\":1,\"is_correct\":true,\"correct_answer\":\"It ensures the new section always starts at the top of the next page, regardless of changes made to the previous text. \"}]', 3, '2026-05-11 16:00:57', '2026-05-11 16:00:57'),
(267, 11, 164, 100, 'passed', '[{\"question_id\":164,\"question_text\":\"Which Xero subscription plan is specifically limited to sending 20 quotes or invoices and receiving 5 bills per month?\",\"user_answer\":3,\"is_correct\":true,\"correct_answer\":\"Early \"}]', 19, '2026-05-12 01:28:10', '2026-05-12 01:28:10'),
(268, 11, 165, 100, 'passed', '[{\"question_id\":165,\"question_text\":\"According to the guide, who is specifically recommended to create manual journal entries?\",\"user_answer\":0,\"is_correct\":true,\"correct_answer\":\"An accountant or bookkeeper\"}]', 4, '2026-05-12 01:28:18', '2026-05-12 01:28:18'),
(269, 11, 166, 100, 'passed', '[{\"question_id\":166,\"question_text\":\"What is the primary purpose of the \'Bank Reconciliation\' process in Xero?\",\"user_answer\":3,\"is_correct\":true,\"correct_answer\":\"To ensure cash records in Xero match the actual bank statement transactions \"}]', 2, '2026-05-12 01:28:24', '2026-05-12 01:28:24'),
(270, 11, 167, 100, 'passed', '[{\"question_id\":167,\"question_text\":\"In the Xero Sales Overview, how are overdue amounts visually identified to the user?\",\"user_answer\":0,\"is_correct\":true,\"correct_answer\":\"Flagged with a fiery red color \"}]', 2, '2026-05-12 01:28:29', '2026-05-12 01:28:29'),
(271, 11, 168, 100, 'passed', '[{\"question_id\":168,\"question_text\":\"Which specific reporting tool provides a snapshot of assets, liabilities, and equity at a specific point in time?\",\"user_answer\":2,\"is_correct\":true,\"correct_answer\":\"Balance Sheet \"}]', 3, '2026-05-12 01:28:35', '2026-05-12 01:28:35'),
(272, 11, 169, 100, 'passed', '[{\"question_id\":169,\"question_text\":\"What is the function of the \'Hubdoc\' application mentioned in the \'Do More with Xero\' menu?\",\"user_answer\":1,\"is_correct\":true,\"correct_answer\":\"To automate bookkeeping tasks like entering bills and receipts\"}]', 2, '2026-05-12 01:28:41', '2026-05-12 01:28:41'),
(273, 11, 170, 100, 'passed', '[{\"question_id\":170,\"question_text\":\"If a business deals with multiple employees and needs to track specific projects and multi-currency transactions, which plan is required?\",\"user_answer\":0,\"is_correct\":true,\"correct_answer\":\"Established \"}]', 4, '2026-05-12 01:28:48', '2026-05-12 01:28:48'),
(274, 11, 171, 100, 'passed', '[{\"question_id\":171,\"question_text\":\"What does the \'Jax\' feature in the navigation bar represent?\",\"user_answer\":0,\"is_correct\":true,\"correct_answer\":\"A brand new AI business companion \"}]', 3, '2026-05-12 01:28:55', '2026-05-12 01:28:55'),
(275, 11, 172, 100, 'passed', '[{\"question_id\":172,\"question_text\":\"In the Organization Settings, which section allows you to manage who has access to the Xero file and their permissions?\",\"user_answer\":2,\"is_correct\":true,\"correct_answer\":\"Users \"}]', 2, '2026-05-12 01:29:01', '2026-05-12 01:29:01'),
(276, 11, 173, 100, 'passed', '[{\"question_id\":173,\"question_text\":\"What distinguishes \'Purchase Orders\' from \'Bills\' in Xero\'s Purchases menu?\",\"user_answer\":1,\"is_correct\":true,\"correct_answer\":\"Purchase orders are for tracking orders with suppliers and don\'t impact accounts until converted \"}]', 3, '2026-05-12 01:29:08', '2026-05-12 01:29:08'),
(287, 11, 194, 100, 'passed', '[{\"question_id\":194,\"question_text\":\"According to the demo, which functional area is considered the \'core\' and most stable part of NetSuite?\",\"user_answer\":2,\"is_correct\":true,\"correct_answer\":\"Accounting\"}]', 3, '2026-05-12 01:39:23', '2026-05-12 01:39:23'),
(288, 11, 195, 100, 'passed', '[{\"question_id\":195,\"question_text\":\"NetSuite is distinguished in the ERP market by being the very first software of its kind to be based in which environment?\",\"user_answer\":1,\"is_correct\":true,\"correct_answer\":\"Cloud-based\"}]', 2, '2026-05-12 01:39:29', '2026-05-12 01:39:29'),
(289, 11, 196, 100, 'passed', '[{\"question_id\":196,\"question_text\":\"What is the primary benefit of the \'Reminders\' portlet on the home dashboard?\",\"user_answer\":0,\"is_correct\":true,\"correct_answer\":\"It displays specific tasks requiring action based on the user\'s role.\"}]', 3, '2026-05-12 01:39:36', '2026-05-12 01:39:36'),
(290, 11, 197, 100, 'passed', '[{\"question_id\":197,\"question_text\":\"When looking at a financial report like an Income Statement, what happens if a user clicks on a specific total revenue number?\",\"user_answer\":2,\"is_correct\":true,\"correct_answer\":\"The user \'drills down\' into the individual transactions making up that total.\"}]', 2, '2026-05-12 01:39:41', '2026-05-12 01:39:41'),
(291, 11, 198, 100, 'passed', '[{\"question_id\":198,\"question_text\":\"How does Suite Analytics differ from standard Saved Searches in NetSuite?\",\"user_answer\":0,\"is_correct\":true,\"correct_answer\":\"Suite Analytics allows for pivot tables and advanced graphical representations.\"}]', 5, '2026-05-12 01:39:50', '2026-05-12 01:39:50'),
(292, 11, 199, 100, 'passed', '[{\"question_id\":199,\"question_text\":\"Within the inventory management demo, which planning method was mentioned as an alternative to \'Reorder Point\'?\",\"user_answer\":0,\"is_correct\":true,\"correct_answer\":\"Material Requirements Planning (MRP)\"}]', 7, '2026-05-12 01:40:01', '2026-05-12 01:40:01'),
(293, 11, 200, 100, 'passed', '[{\"question_id\":200,\"question_text\":\"If a user finds that NetSuite lacks a specific niche functionality, what does the demo suggest as a solution?\",\"user_answer\":3,\"is_correct\":true,\"correct_answer\":\"Utilizing the third-party application ecosystem\"}]', 2, '2026-05-12 01:40:06', '2026-05-12 01:40:06'),
(294, 11, 201, 100, 'passed', '[{\"question_id\":201,\"question_text\":\"What functionality does the \'Create New\' button at the top of the interface provide?\",\"user_answer\":1,\"is_correct\":true,\"correct_answer\":\"It provides a list of shortcuts to generate new records or transactions.\"}]', 5, '2026-05-12 01:40:15', '2026-05-12 01:40:15'),
(295, 11, 202, 100, 'passed', '[{\"question_id\":202,\"question_text\":\"The demo mentions that dashboard views in NetSuite are primarily organized by:\",\"user_answer\":0,\"is_correct\":true,\"correct_answer\":\"The specific role of the user (e.g., Controller)\"}]', 7, '2026-05-12 01:40:25', '2026-05-12 01:40:25'),
(296, 11, 203, 100, 'passed', '[{\"question_id\":203,\"question_text\":\"According to the demo\'s conclusion, who are the two main types of entities that can assist with NetSuite implementation?\",\"user_answer\":1,\"is_correct\":true,\"correct_answer\":\"NetSuite Professional Services and third-party solution providers\"}]', 9, '2026-05-12 01:40:38', '2026-05-12 01:40:38'),
(357, 15, 244, 100, 'passed', '[{\"question_id\":244,\"question_text\":\"According to the tutorial, what is the primary role of Slack within a company or organization?\",\"user_answer\":3,\"is_correct\":true,\"correct_answer\":\"A central hub for communication, files, and project organization.\"}]', 3, '2026-05-12 03:14:00', '2026-05-12 03:14:00'),
(358, 15, 245, 100, 'passed', '[{\"question_id\":245,\"question_text\":\"If you are joining a company that already uses Slack, how do you typically gain access to their workspace?\",\"user_answer\":0,\"is_correct\":true,\"correct_answer\":\"By following the prompts in an invitation email sent by the company.\"}]', 2, '2026-05-12 03:14:05', '2026-05-12 03:14:05'),
(359, 15, 246, 100, 'passed', '[{\"question_id\":246,\"question_text\":\"What is a specific limitation of the free version of Slack mentioned in the source material?\",\"user_answer\":1,\"is_correct\":true,\"correct_answer\":\"Messages are lost or hidden after approximately 90 days.\"}]', 2, '2026-05-12 03:14:11', '2026-05-12 03:14:11'),
(360, 15, 247, 100, 'passed', '[{\"question_id\":247,\"question_text\":\"How does the tutorial distinguish between \'Channels\' and \'Direct Messages\'?\",\"user_answer\":3,\"is_correct\":true,\"correct_answer\":\"Channels function as individual chat rooms for groups, while Direct Messages are one-on-one conversations.\"}]', 4, '2026-05-12 03:14:18', '2026-05-12 03:14:18'),
(361, 15, 248, 100, 'passed', '[{\"question_id\":248,\"question_text\":\"What is the benefit of using \'Threads\' when responding to a message in a channel?\",\"user_answer\":0,\"is_correct\":true,\"correct_answer\":\"It keeps related messages organized and prevents the main channel from becoming cluttered.\"}]', 2, '2026-05-12 03:14:23', '2026-05-12 03:14:23'),
(362, 15, 249, 100, 'passed', '[{\"question_id\":249,\"question_text\":\"Where should a user look in the Slack interface to find mentions, reactions, or invitations to new groups?\",\"user_answer\":3,\"is_correct\":true,\"correct_answer\":\"The Activity section on the sidebar.\"}]', 3, '2026-05-12 03:14:30', '2026-05-12 03:14:30'),
(363, 15, 250, 100, 'passed', '[{\"question_id\":250,\"question_text\":\"When creating a new channel, what is the purpose of using a \'template\' like the \'Project starter kit\'?\",\"user_answer\":3,\"is_correct\":true,\"correct_answer\":\"To provide a pre-laid out structure specifically tailored for a project\'s needs.\"}]', 2, '2026-05-12 03:14:36', '2026-05-12 03:14:36'),
(364, 15, 251, 100, 'passed', '[{\"question_id\":251,\"question_text\":\"According to the tutorial, how can you use the Direct Message feature to benefit your own personal workflow?\",\"user_answer\":0,\"is_correct\":true,\"correct_answer\":\"By messaging yourself to jot down notes and private thoughts.\"}]', 7, '2026-05-12 03:14:47', '2026-05-12 03:14:47'),
(365, 15, 252, 100, 'passed', '[{\"question_id\":252,\"question_text\":\"Which specific plugin is mentioned as an example to facilitate virtual meetings directly through Slack commands?\",\"user_answer\":0,\"is_correct\":true,\"correct_answer\":\"Zoom\"}]', 2, '2026-05-12 03:14:52', '2026-05-12 03:14:52'),
(366, 15, 253, 100, 'passed', '[{\"question_id\":253,\"question_text\":\"What is the recommended method for finding a specific file or past idea, such as \'new video ideas\', without scrolling through history?\",\"user_answer\":2,\"is_correct\":true,\"correct_answer\":\"Using the search bar to look for keywords or file names.\"}]', 2, '2026-05-12 03:14:58', '2026-05-12 03:14:58'),
(397, 11, 174, 100, 'passed', '[{\"question_id\":174,\"question_text\":\"Which specific data points does the \'snap and track\' feature automatically extract from a receipt photo?\",\"user_answer\":1,\"is_correct\":true,\"correct_answer\":\"Vendor, date, and amount \"}]', 4, '2026-05-12 06:40:46', '2026-05-12 06:40:46'),
(398, 11, 175, 100, 'passed', '[{\"question_id\":175,\"question_text\":\"How does MYOB assist users in meeting Australian Taxation Office (ATO) requirements?\",\"user_answer\":3,\"is_correct\":true,\"correct_answer\":\"By automating GST calculations and business activity statement reporting\"}]', 5, '2026-05-12 06:40:55', '2026-05-12 06:40:55'),
(399, 11, 176, 100, 'passed', '[{\"question_id\":176,\"question_text\":\"What is a primary benefit of linking a bank account directly to the MYOB software?\",\"user_answer\":3,\"is_correct\":true,\"correct_answer\":\"It automatically imports and matches transactions to reduce manual admin time\"}]', 3, '2026-05-12 06:41:01', '2026-05-12 06:41:01'),
(400, 11, 177, 0, 'failed', '[{\"question_id\":177,\"question_text\":\"According to the tutorial, what makes \'MYOB Solo\' distinct from standard spreadsheets for business management?\",\"user_answer\":2,\"is_correct\":false,\"correct_answer\":\"It is only accessible via desktop computers to ensure high security\"}]', 3, '2026-05-12 06:41:08', '2026-05-12 06:41:08'),
(401, 11, 178, 100, 'passed', '[{\"question_id\":178,\"question_text\":\"For \'Creative Freelancers,\' which specific accounting challenge does MYOB address according to the demo?\",\"user_answer\":1,\"is_correct\":true,\"correct_answer\":\"Budgeting and cash flow visibility for irregular income\"}]', 2, '2026-05-12 06:41:14', '2026-05-12 06:41:14'),
(402, 11, 179, 100, 'passed', '[{\"question_id\":179,\"question_text\":\"What is a key focus area for MYOB when supporting \'Trades\' professionals like builders and electricians?\",\"user_answer\":0,\"is_correct\":true,\"correct_answer\":\"On-site job tracking and managing materials or labor costs\"}]', 2, '2026-05-12 06:41:20', '2026-05-12 06:41:20'),
(403, 11, 180, 100, 'passed', '[{\"question_id\":180,\"question_text\":\"Which feature is highlighted as particularly useful for Health and Wellness professionals?\",\"user_answer\":0,\"is_correct\":true,\"correct_answer\":\"Class pass tracking and superannuation reporting\"}]', 3, '2026-05-12 06:41:26', '2026-05-12 06:41:26'),
(404, 11, 181, 100, 'passed', '[{\"question_id\":181,\"question_text\":\"How can users improve their professionalism when invoicing clients through MYOB?\",\"user_answer\":1,\"is_correct\":true,\"correct_answer\":\"By using customizable templates that incorporate their own branding\"}]', 8, '2026-05-12 06:41:37', '2026-05-12 06:41:37'),
(405, 11, 182, 100, 'passed', '[{\"question_id\":182,\"question_text\":\"What is one mentioned disadvantage for absolute beginners using MYOB?\",\"user_answer\":2,\"is_correct\":true,\"correct_answer\":\"The software might feel a bit complex to navigate initially\"}]', 3, '2026-05-12 06:41:44', '2026-05-12 06:41:44'),
(406, 11, 183, 100, 'passed', '[{\"question_id\":183,\"question_text\":\"For consultants and subject matter experts, MYOB emphasizes which financial metric?\",\"user_answer\":3,\"is_correct\":true,\"correct_answer\":\"Profitability tracking by client or project\"}]', 3, '2026-05-12 06:41:50', '2026-05-12 06:41:50'),
(417, 15, 234, 100, 'passed', '[{\"question_id\":234,\"question_text\":\"What is the primary technical difference between a standard consumer Gmail account and a Google Workspace account?\",\"user_answer\":0,\"is_correct\":true,\"correct_answer\":\"The Workspace account allows for the use of a custom business domain name. \"}]', 4, '2026-05-12 08:20:33', '2026-05-12 08:20:33'),
(418, 15, 235, 100, 'passed', '[{\"question_id\":235,\"question_text\":\"Which administrative feature allows a business owner to manage settings for staff members connecting to resources via a specific web browser?\",\"user_answer\":0,\"is_correct\":true,\"correct_answer\":\"Chrome policies\"}]', 3, '2026-05-12 08:20:39', '2026-05-12 08:20:39'),
(419, 15, 236, 100, 'passed', '[{\"question_id\":236,\"question_text\":\"Why is the \'Business Standard\' plan recommended over the \'Basic\' plan for most businesses?\",\"user_answer\":0,\"is_correct\":true,\"correct_answer\":\"It includes features like Shared Drives and the ability to record Google Meet sessions. \"}]', 3, '2026-05-12 08:20:45', '2026-05-12 08:20:45'),
(420, 15, 237, 100, 'passed', '[{\"question_id\":237,\"question_text\":\"During the technical setup of Google Workspace, which DNS setting is critical to prevent your emails from being flagged as spam?\",\"user_answer\":1,\"is_correct\":true,\"correct_answer\":\"SPF \"}]', 2, '2026-05-12 08:20:51', '2026-05-12 08:20:51'),
(421, 15, 238, 100, 'passed', '[{\"question_id\":238,\"question_text\":\"How does the modern Google Drive desktop application manage local storage space efficiently?\",\"user_answer\":0,\"is_correct\":true,\"correct_answer\":\"It removes local copies of files that haven\'t been used in a while, keeping them in the cloud. \"}]', 3, '2026-05-12 08:20:57', '2026-05-12 08:20:57'),
(422, 15, 239, 100, 'passed', '[{\"question_id\":239,\"question_text\":\"What is a major risk of using a personal Gmail account when collaborating with external contractors?\",\"user_answer\":0,\"is_correct\":true,\"correct_answer\":\"The contractor remains the owner of the files they create and share with you. \"}]', 2, '2026-05-12 08:21:03', '2026-05-12 08:21:03'),
(423, 15, 240, 100, 'passed', '[{\"question_id\":240,\"question_text\":\"Which Google Docs feature allows you to view every single keystroke change made to a document since its creation?\",\"user_answer\":0,\"is_correct\":true,\"correct_answer\":\"Revision history \"}]', 4, '2026-05-12 08:21:10', '2026-05-12 08:21:10'),
(424, 15, 241, 100, 'passed', '[{\"question_id\":241,\"question_text\":\"In Google Meet, what happens to the video recording and transcription of a meeting once it concludes?\",\"user_answer\":3,\"is_correct\":true,\"correct_answer\":\"It is automatically saved into a folder in your Google Drive. \"}]', 2, '2026-05-12 08:21:16', '2026-05-12 08:21:16'),
(425, 15, 242, 0, 'failed', '[{\"question_id\":242,\"question_text\":\"Which specific mode in Google Docs is best for collaborating with a partner whose changes you want to review before they become permanent?\",\"user_answer\":3,\"is_correct\":false,\"correct_answer\":\"Restricted mode\"}]', 2, '2026-05-12 08:21:21', '2026-05-12 08:21:21'),
(426, 15, 243, 100, 'passed', '[{\"question_id\":243,\"question_text\":\"What happens if a contractor places a file into a business \'Shared Drive\' owned by your company?\",\"user_answer\":3,\"is_correct\":true,\"correct_answer\":\"The contractor receives a prompt to transfer ownership to the business. \"}]', 3, '2026-05-12 08:21:28', '2026-05-12 08:21:28'),
(427, 15, 224, 0, 'failed', '[{\"question_id\":224,\"question_text\":\"What is the primary function of the \'dialogue launcher\' (also referred to as a launch button) found in some groups on the ribbon?\",\"user_answer\":0,\"is_correct\":false,\"correct_answer\":\"It opens a window with additional features that could not fit in the ribbon space.\"}]', 3, '2026-05-12 09:11:42', '2026-05-12 09:11:42'),
(428, 15, 225, 0, 'failed', '[{\"question_id\":225,\"question_text\":\"In Microsoft Word, what is the fastest way to select an entire paragraph using only the mouse?\",\"user_answer\":0,\"is_correct\":false,\"correct_answer\":\"Triple-click anywhere within the paragraph. \"}]', 2, '2026-05-12 09:11:48', '2026-05-12 09:11:48'),
(429, 15, 226, 0, 'failed', '[{\"question_id\":226,\"question_text\":\"When saving a document for the very first time, why does Word automatically default to \'Save As\' even if you click \'Save\'?\",\"user_answer\":0,\"is_correct\":false,\"correct_answer\":\"Because the document has not yet been assigned a file name or a storage location. \"}]', 5, '2026-05-12 09:11:57', '2026-05-12 09:11:57'),
(430, 15, 227, 100, 'passed', '[{\"question_id\":227,\"question_text\":\"If a user is in \'Focus Mode\' and wants to return to the standard \'Print Layout\' view, which keyboard key should they press?\",\"user_answer\":0,\"is_correct\":true,\"correct_answer\":\"Escape \"}]', 5, '2026-05-12 09:12:05', '2026-05-12 09:12:05'),
(431, 15, 228, 0, 'failed', '[{\"question_id\":228,\"question_text\":\"Which keyboard shortcut is used to \'Redo\' an action that was previously undone?\",\"user_answer\":0,\"is_correct\":false,\"correct_answer\":\"Ctrl + Y\"}]', 4, '2026-05-12 09:12:12', '2026-05-12 09:12:12'),
(432, 15, 229, 100, 'passed', '[{\"question_id\":229,\"question_text\":\" What is the purpose of the \'Match Case\' option within the \'Find and Replace\' tool?\",\"user_answer\":0,\"is_correct\":true,\"correct_answer\":\"It ensures the search only finds words with the exact same capitalization as the search term.\"}]', 6, '2026-05-12 09:12:22', '2026-05-12 09:12:22'),
(433, 15, 230, 0, 'failed', '[{\"question_id\":230,\"question_text\":\"When working in a table, what happens if you press the \'Tab\' key while your cursor is in the very last cell (bottom right)?\",\"user_answer\":0,\"is_correct\":false,\"correct_answer\":\"Word automatically adds a new blank row to the bottom of the table. \"}]', 6, '2026-05-12 09:12:31', '2026-05-12 09:12:31'),
(434, 15, 231, 0, 'failed', '[{\"question_id\":231,\"question_text\":\"Where can you find the options to change line spacing from \'Multiple\' to \'Double\' in a document?\",\"user_answer\":0,\"is_correct\":false,\"correct_answer\":\"In the Paragraph settings dialogue box, accessible via the launch button on the Home tab.\"}]', 5, '2026-05-12 09:12:39', '2026-05-12 09:12:39'),
(435, 15, 232, 100, 'passed', '[{\"question_id\":232,\"question_text\":\"Which of the following describes the \'Autocorrect\' tool\'s ability to handle custom abbreviations?\",\"user_answer\":0,\"is_correct\":true,\"correct_answer\":\"It can be configured to expand a short code (like \'T4TAS\') into a much longer predefined phrase. \"}]', 2, '2026-05-12 09:12:45', '2026-05-12 09:12:45'),
(436, 15, 233, 0, 'failed', '[{\"question_id\":233,\"question_text\":\"Why is using a \'Page Break\' (found in the Layout tab) preferable to pressing the \'Enter\' key multiple times to start a new page?\",\"user_answer\":0,\"is_correct\":false,\"correct_answer\":\"It ensures the new section always starts at the top of the next page, regardless of changes made to the previous text. \"}]', 4, '2026-05-12 09:12:52', '2026-05-12 09:12:52'),
(447, 11, 184, 100, 'passed', '[{\"question_id\":184,\"question_text\":\"The acronym SAP originated from a phrase in which language?\",\"user_answer\":2,\"is_correct\":true,\"correct_answer\":\"German\"}]', 5, '2026-05-12 10:31:37', '2026-05-12 10:31:37'),
(448, 11, 185, 100, 'passed', '[{\"question_id\":185,\"question_text\":\"What does the \'ERP\' in SAP ERP stand for?\",\"user_answer\":2,\"is_correct\":true,\"correct_answer\":\"Enterprise Resource Planning\"}]', 2, '2026-05-12 10:31:43', '2026-05-12 10:31:43'),
(449, 11, 186, 100, 'passed', '[{\"question_id\":186,\"question_text\":\"Before founding SAP, the original five founders were employees of which technology giant?\",\"user_answer\":0,\"is_correct\":true,\"correct_answer\":\"IBM\"}]', 2, '2026-05-12 10:31:48', '2026-05-12 10:31:48'),
(450, 11, 187, 100, 'passed', '[{\"question_id\":187,\"question_text\":\"Which analogy does the author use to explain why both the company and its primary software product share the name \'SAP\'?\",\"user_answer\":2,\"is_correct\":true,\"correct_answer\":\"Coca-Cola\"}]', 2, '2026-05-12 10:31:54', '2026-05-12 10:31:54'),
(451, 11, 188, 100, 'passed', '[{\"question_id\":188,\"question_text\":\"According to the source material, approximately what percentage of the world\'s transaction revenue touches an SAP system?\",\"user_answer\":3,\"is_correct\":true,\"correct_answer\":\"77%\"}]', 3, '2026-05-12 10:32:01', '2026-05-12 10:32:01'),
(452, 11, 189, 100, 'passed', '[{\"question_id\":189,\"question_text\":\"Which of the following is NOT listed as one of the primary business areas typically supported by ERP systems?\",\"user_answer\":3,\"is_correct\":true,\"correct_answer\":\"Hardware Manufacturing\"}]', 2, '2026-05-12 10:32:07', '2026-05-12 10:32:07'),
(453, 11, 190, 100, 'passed', '[{\"question_id\":190,\"question_text\":\"In the context of the German brand BMW, used as an analogy for SAP, what does the \'B\' stand for?\",\"user_answer\":3,\"is_correct\":true,\"correct_answer\":\"Bavarian\"}]', 4, '2026-05-12 10:32:14', '2026-05-12 10:32:14'),
(454, 11, 191, 100, 'passed', '[{\"question_id\":191,\"question_text\":\"What is the official full legal name of the SAP company mentioned in the material?\",\"user_answer\":0,\"is_correct\":true,\"correct_answer\":\"SAP SE\"}]', 2, '2026-05-12 10:32:20', '2026-05-12 10:32:20'),
(455, 11, 192, 100, 'passed', '[{\"question_id\":192,\"question_text\":\"What is the primary reason the author suggests that even many SAP specialists do not remember the full German name of the company?\",\"user_answer\":0,\"is_correct\":true,\"correct_answer\":\"The name is long and complex, especially for non-German speakers.\"}]', 3, '2026-05-12 10:32:27', '2026-05-12 10:32:27'),
(456, 11, 193, 100, 'passed', '[{\"question_id\":193,\"question_text\":\"Which of these is mentioned as a specific SAP product used for managing customer relations?\",\"user_answer\":0,\"is_correct\":true,\"correct_answer\":\"SAP CRM\"}]', 7, '2026-05-12 10:32:37', '2026-05-12 10:32:37'),
(467, 21, 224, 100, 'passed', '[{\"question_id\":224,\"question_text\":\"What is the primary function of the \'dialogue launcher\' (also referred to as a launch button) found in some groups on the ribbon?\",\"user_answer\":1,\"is_correct\":true,\"correct_answer\":\"It opens a window with additional features that could not fit in the ribbon space.\"}]', 3, '2026-05-12 12:27:45', '2026-05-12 12:27:45'),
(468, 21, 225, 100, 'passed', '[{\"question_id\":225,\"question_text\":\"In Microsoft Word, what is the fastest way to select an entire paragraph using only the mouse?\",\"user_answer\":1,\"is_correct\":true,\"correct_answer\":\"Triple-click anywhere within the paragraph. \"}]', 2, '2026-05-12 12:27:51', '2026-05-12 12:27:51'),
(469, 21, 226, 100, 'passed', '[{\"question_id\":226,\"question_text\":\"When saving a document for the very first time, why does Word automatically default to \'Save As\' even if you click \'Save\'?\",\"user_answer\":2,\"is_correct\":true,\"correct_answer\":\"Because the document has not yet been assigned a file name or a storage location. \"}]', 2, '2026-05-12 12:27:56', '2026-05-12 12:27:56'),
(470, 21, 227, 100, 'passed', '[{\"question_id\":227,\"question_text\":\"If a user is in \'Focus Mode\' and wants to return to the standard \'Print Layout\' view, which keyboard key should they press?\",\"user_answer\":0,\"is_correct\":true,\"correct_answer\":\"Escape \"}]', 2, '2026-05-12 12:28:02', '2026-05-12 12:28:02'),
(471, 21, 228, 100, 'passed', '[{\"question_id\":228,\"question_text\":\"Which keyboard shortcut is used to \'Redo\' an action that was previously undone?\",\"user_answer\":3,\"is_correct\":true,\"correct_answer\":\"Ctrl + Y\"}]', 2, '2026-05-12 12:28:08', '2026-05-12 12:28:08'),
(472, 21, 229, 100, 'passed', '[{\"question_id\":229,\"question_text\":\" What is the purpose of the \'Match Case\' option within the \'Find and Replace\' tool?\",\"user_answer\":0,\"is_correct\":true,\"correct_answer\":\"It ensures the search only finds words with the exact same capitalization as the search term.\"}]', 2, '2026-05-12 12:28:13', '2026-05-12 12:28:13'),
(473, 21, 230, 100, 'passed', '[{\"question_id\":230,\"question_text\":\"When working in a table, what happens if you press the \'Tab\' key while your cursor is in the very last cell (bottom right)?\",\"user_answer\":1,\"is_correct\":true,\"correct_answer\":\"Word automatically adds a new blank row to the bottom of the table. \"}]', 4, '2026-05-12 12:28:21', '2026-05-12 12:28:21'),
(474, 21, 231, 100, 'passed', '[{\"question_id\":231,\"question_text\":\"Where can you find the options to change line spacing from \'Multiple\' to \'Double\' in a document?\",\"user_answer\":3,\"is_correct\":true,\"correct_answer\":\"In the Paragraph settings dialogue box, accessible via the launch button on the Home tab.\"}]', 2, '2026-05-12 12:28:27', '2026-05-12 12:28:27'),
(475, 21, 232, 100, 'passed', '[{\"question_id\":232,\"question_text\":\"Which of the following describes the \'Autocorrect\' tool\'s ability to handle custom abbreviations?\",\"user_answer\":0,\"is_correct\":true,\"correct_answer\":\"It can be configured to expand a short code (like \'T4TAS\') into a much longer predefined phrase. \"}]', 2, '2026-05-12 12:28:33', '2026-05-12 12:28:33'),
(476, 21, 233, 100, 'passed', '[{\"question_id\":233,\"question_text\":\"Why is using a \'Page Break\' (found in the Layout tab) preferable to pressing the \'Enter\' key multiple times to start a new page?\",\"user_answer\":1,\"is_correct\":true,\"correct_answer\":\"It ensures the new section always starts at the top of the next page, regardless of changes made to the previous text. \"}]', 2, '2026-05-12 12:28:38', '2026-05-12 12:28:38'),
(477, 21, 234, 100, 'passed', '[{\"question_id\":234,\"question_text\":\"What is the primary technical difference between a standard consumer Gmail account and a Google Workspace account?\",\"user_answer\":0,\"is_correct\":true,\"correct_answer\":\"The Workspace account allows for the use of a custom business domain name. \"}]', 2, '2026-05-12 12:29:34', '2026-05-12 12:29:34'),
(478, 21, 235, 100, 'passed', '[{\"question_id\":235,\"question_text\":\"Which administrative feature allows a business owner to manage settings for staff members connecting to resources via a specific web browser?\",\"user_answer\":0,\"is_correct\":true,\"correct_answer\":\"Chrome policies\"}]', 2, '2026-05-12 12:29:40', '2026-05-12 12:29:40'),
(479, 21, 236, 100, 'passed', '[{\"question_id\":236,\"question_text\":\"Why is the \'Business Standard\' plan recommended over the \'Basic\' plan for most businesses?\",\"user_answer\":0,\"is_correct\":true,\"correct_answer\":\"It includes features like Shared Drives and the ability to record Google Meet sessions. \"}]', 2, '2026-05-12 12:29:45', '2026-05-12 12:29:45'),
(480, 21, 237, 100, 'passed', '[{\"question_id\":237,\"question_text\":\"During the technical setup of Google Workspace, which DNS setting is critical to prevent your emails from being flagged as spam?\",\"user_answer\":1,\"is_correct\":true,\"correct_answer\":\"SPF \"}]', 2, '2026-05-12 12:29:51', '2026-05-12 12:29:51'),
(481, 21, 238, 100, 'passed', '[{\"question_id\":238,\"question_text\":\"How does the modern Google Drive desktop application manage local storage space efficiently?\",\"user_answer\":0,\"is_correct\":true,\"correct_answer\":\"It removes local copies of files that haven\'t been used in a while, keeping them in the cloud. \"}]', 2, '2026-05-12 12:29:56', '2026-05-12 12:29:56'),
(482, 21, 239, 100, 'passed', '[{\"question_id\":239,\"question_text\":\"What is a major risk of using a personal Gmail account when collaborating with external contractors?\",\"user_answer\":0,\"is_correct\":true,\"correct_answer\":\"The contractor remains the owner of the files they create and share with you. \"}]', 2, '2026-05-12 12:30:02', '2026-05-12 12:30:02'),
(483, 21, 240, 100, 'passed', '[{\"question_id\":240,\"question_text\":\"Which Google Docs feature allows you to view every single keystroke change made to a document since its creation?\",\"user_answer\":0,\"is_correct\":true,\"correct_answer\":\"Revision history \"}]', 2, '2026-05-12 12:30:07', '2026-05-12 12:30:07'),
(484, 21, 241, 100, 'passed', '[{\"question_id\":241,\"question_text\":\"In Google Meet, what happens to the video recording and transcription of a meeting once it concludes?\",\"user_answer\":3,\"is_correct\":true,\"correct_answer\":\"It is automatically saved into a folder in your Google Drive. \"}]', 2, '2026-05-12 12:30:13', '2026-05-12 12:30:13'),
(485, 21, 242, 0, 'failed', '[{\"question_id\":242,\"question_text\":\"Which specific mode in Google Docs is best for collaborating with a partner whose changes you want to review before they become permanent?\",\"user_answer\":1,\"is_correct\":false,\"correct_answer\":\"Restricted mode\"}]', 3, '2026-05-12 12:30:20', '2026-05-12 12:30:20'),
(486, 21, 243, 100, 'passed', '[{\"question_id\":243,\"question_text\":\"What happens if a contractor places a file into a business \'Shared Drive\' owned by your company?\",\"user_answer\":3,\"is_correct\":true,\"correct_answer\":\"The contractor receives a prompt to transfer ownership to the business. \"}]', 3, '2026-05-12 12:30:27', '2026-05-12 12:30:27'),
(487, 21, 244, 100, 'passed', '[{\"question_id\":244,\"question_text\":\"According to the tutorial, what is the primary role of Slack within a company or organization?\",\"user_answer\":3,\"is_correct\":true,\"correct_answer\":\"A central hub for communication, files, and project organization.\"}]', 5, '2026-05-12 12:31:07', '2026-05-12 12:31:07'),
(488, 21, 245, 100, 'passed', '[{\"question_id\":245,\"question_text\":\"If you are joining a company that already uses Slack, how do you typically gain access to their workspace?\",\"user_answer\":0,\"is_correct\":true,\"correct_answer\":\"By following the prompts in an invitation email sent by the company.\"}]', 2, '2026-05-12 12:31:13', '2026-05-12 12:31:13'),
(489, 21, 246, 100, 'passed', '[{\"question_id\":246,\"question_text\":\"What is a specific limitation of the free version of Slack mentioned in the source material?\",\"user_answer\":1,\"is_correct\":true,\"correct_answer\":\"Messages are lost or hidden after approximately 90 days.\"}]', 4, '2026-05-12 12:31:20', '2026-05-12 12:31:20'),
(490, 21, 247, 100, 'passed', '[{\"question_id\":247,\"question_text\":\"How does the tutorial distinguish between \'Channels\' and \'Direct Messages\'?\",\"user_answer\":3,\"is_correct\":true,\"correct_answer\":\"Channels function as individual chat rooms for groups, while Direct Messages are one-on-one conversations.\"}]', 3, '2026-05-12 12:31:27', '2026-05-12 12:31:27'),
(491, 21, 248, 100, 'passed', '[{\"question_id\":248,\"question_text\":\"What is the benefit of using \'Threads\' when responding to a message in a channel?\",\"user_answer\":0,\"is_correct\":true,\"correct_answer\":\"It keeps related messages organized and prevents the main channel from becoming cluttered.\"}]', 2, '2026-05-12 12:31:32', '2026-05-12 12:31:32'),
(492, 21, 249, 100, 'passed', '[{\"question_id\":249,\"question_text\":\"Where should a user look in the Slack interface to find mentions, reactions, or invitations to new groups?\",\"user_answer\":3,\"is_correct\":true,\"correct_answer\":\"The Activity section on the sidebar.\"}]', 2, '2026-05-12 12:31:38', '2026-05-12 12:31:38'),
(493, 21, 250, 100, 'passed', '[{\"question_id\":250,\"question_text\":\"When creating a new channel, what is the purpose of using a \'template\' like the \'Project starter kit\'?\",\"user_answer\":3,\"is_correct\":true,\"correct_answer\":\"To provide a pre-laid out structure specifically tailored for a project\'s needs.\"}]', 5, '2026-05-12 12:31:47', '2026-05-12 12:31:47'),
(494, 21, 251, 100, 'passed', '[{\"question_id\":251,\"question_text\":\"According to the tutorial, how can you use the Direct Message feature to benefit your own personal workflow?\",\"user_answer\":0,\"is_correct\":true,\"correct_answer\":\"By messaging yourself to jot down notes and private thoughts.\"}]', 2, '2026-05-12 12:31:53', '2026-05-12 12:31:53'),
(495, 21, 252, 100, 'passed', '[{\"question_id\":252,\"question_text\":\"Which specific plugin is mentioned as an example to facilitate virtual meetings directly through Slack commands?\",\"user_answer\":0,\"is_correct\":true,\"correct_answer\":\"Zoom\"}]', 2, '2026-05-12 12:31:58', '2026-05-12 12:31:58'),
(496, 21, 253, 100, 'passed', '[{\"question_id\":253,\"question_text\":\"What is the recommended method for finding a specific file or past idea, such as \'new video ideas\', without scrolling through history?\",\"user_answer\":2,\"is_correct\":true,\"correct_answer\":\"Using the search bar to look for keywords or file names.\"}]', 2, '2026-05-12 12:32:03', '2026-05-12 12:32:03'),
(497, 13, 244, 0, 'failed', '[{\"question_id\":244,\"question_text\":\"According to the tutorial, what is the primary role of Slack within a company or organization?\",\"user_answer\":1,\"is_correct\":false,\"correct_answer\":\"A central hub for communication, files, and project organization.\"}]', 2, '2026-06-03 00:19:54', '2026-06-03 00:19:54'),
(498, 13, 245, 100, 'passed', '[{\"question_id\":245,\"question_text\":\"If you are joining a company that already uses Slack, how do you typically gain access to their workspace?\",\"user_answer\":0,\"is_correct\":true,\"correct_answer\":\"By following the prompts in an invitation email sent by the company.\"}]', 3, '2026-06-03 00:20:00', '2026-06-03 00:20:00'),
(499, 13, 246, 0, 'failed', '[{\"question_id\":246,\"question_text\":\"What is a specific limitation of the free version of Slack mentioned in the source material?\",\"user_answer\":null,\"correct_answer\":\"Messages are lost or hidden after approximately 90 days.\"}]', 10, '2026-06-03 00:20:13', '2026-06-03 00:20:13'),
(500, 13, 247, 0, 'failed', '[{\"question_id\":247,\"question_text\":\"How does the tutorial distinguish between \'Channels\' and \'Direct Messages\'?\",\"user_answer\":0,\"is_correct\":false,\"correct_answer\":\"Channels function as individual chat rooms for groups, while Direct Messages are one-on-one conversations.\"}]', 2, '2026-06-03 00:20:19', '2026-06-03 00:20:19'),
(501, 13, 248, 100, 'passed', '[{\"question_id\":248,\"question_text\":\"What is the benefit of using \'Threads\' when responding to a message in a channel?\",\"user_answer\":0,\"is_correct\":true,\"correct_answer\":\"It keeps related messages organized and prevents the main channel from becoming cluttered.\"}]', 3, '2026-06-03 00:20:25', '2026-06-03 00:20:25'),
(502, 13, 249, 0, 'failed', '[{\"question_id\":249,\"question_text\":\"Where should a user look in the Slack interface to find mentions, reactions, or invitations to new groups?\",\"user_answer\":0,\"is_correct\":false,\"correct_answer\":\"The Activity section on the sidebar.\"}]', 2, '2026-06-03 00:20:30', '2026-06-03 00:20:30'),
(503, 13, 250, 0, 'failed', '[{\"question_id\":250,\"question_text\":\"When creating a new channel, what is the purpose of using a \'template\' like the \'Project starter kit\'?\",\"user_answer\":0,\"is_correct\":false,\"correct_answer\":\"To provide a pre-laid out structure specifically tailored for a project\'s needs.\"}]', 4, '2026-06-03 00:20:38', '2026-06-03 00:20:38'),
(504, 13, 251, 100, 'passed', '[{\"question_id\":251,\"question_text\":\"According to the tutorial, how can you use the Direct Message feature to benefit your own personal workflow?\",\"user_answer\":0,\"is_correct\":true,\"correct_answer\":\"By messaging yourself to jot down notes and private thoughts.\"}]', 3, '2026-06-03 00:20:44', '2026-06-03 00:20:44'),
(505, 13, 252, 0, 'failed', '[{\"question_id\":252,\"question_text\":\"Which specific plugin is mentioned as an example to facilitate virtual meetings directly through Slack commands?\",\"user_answer\":null,\"correct_answer\":\"Zoom\"}]', 10, '2026-06-03 00:20:57', '2026-06-03 00:20:57'),
(506, 13, 253, 0, 'failed', '[{\"question_id\":253,\"question_text\":\"What is the recommended method for finding a specific file or past idea, such as \'new video ideas\', without scrolling through history?\",\"user_answer\":0,\"is_correct\":false,\"correct_answer\":\"Using the search bar to look for keywords or file names.\"}]', 2, '2026-06-03 00:21:03', '2026-06-03 00:21:03'),
(507, 13, 234, 0, 'failed', '[{\"question_id\":234,\"question_text\":\"What is the primary technical difference between a standard consumer Gmail account and a Google Workspace account?\",\"user_answer\":1,\"is_correct\":false,\"correct_answer\":\"The Workspace account allows for the use of a custom business domain name. \"}]', 2, '2026-06-03 00:43:28', '2026-06-03 00:43:28'),
(508, 13, 235, 100, 'passed', '[{\"question_id\":235,\"question_text\":\"Which administrative feature allows a business owner to manage settings for staff members connecting to resources via a specific web browser?\",\"user_answer\":0,\"is_correct\":true,\"correct_answer\":\"Chrome policies\"}]', 2, '2026-06-03 00:43:33', '2026-06-03 00:43:33');
INSERT INTO `user_quiz_attempts` (`id`, `user_id`, `quiz_id`, `score`, `status`, `answers_data`, `time_taken`, `attempted_at`, `completed_at`) VALUES
(509, 13, 236, 0, 'failed', '[{\"question_id\":236,\"question_text\":\"Why is the \'Business Standard\' plan recommended over the \'Basic\' plan for most businesses?\",\"user_answer\":1,\"is_correct\":false,\"correct_answer\":\"It includes features like Shared Drives and the ability to record Google Meet sessions. \"}]', 2, '2026-06-03 00:43:39', '2026-06-03 00:43:39'),
(510, 13, 237, 100, 'passed', '[{\"question_id\":237,\"question_text\":\"During the technical setup of Google Workspace, which DNS setting is critical to prevent your emails from being flagged as spam?\",\"user_answer\":1,\"is_correct\":true,\"correct_answer\":\"SPF \"}]', 2, '2026-06-03 00:43:45', '2026-06-03 00:43:45'),
(511, 13, 238, 100, 'passed', '[{\"question_id\":238,\"question_text\":\"How does the modern Google Drive desktop application manage local storage space efficiently?\",\"user_answer\":0,\"is_correct\":true,\"correct_answer\":\"It removes local copies of files that haven\'t been used in a while, keeping them in the cloud. \"}]', 2, '2026-06-03 00:43:50', '2026-06-03 00:43:50'),
(512, 13, 239, 0, 'failed', '[{\"question_id\":239,\"question_text\":\"What is a major risk of using a personal Gmail account when collaborating with external contractors?\",\"user_answer\":1,\"is_correct\":false,\"correct_answer\":\"The contractor remains the owner of the files they create and share with you. \"}]', 2, '2026-06-03 00:43:56', '2026-06-03 00:43:56'),
(513, 13, 240, 0, 'failed', '[{\"question_id\":240,\"question_text\":\"Which Google Docs feature allows you to view every single keystroke change made to a document since its creation?\",\"user_answer\":1,\"is_correct\":false,\"correct_answer\":\"Revision history \"}]', 2, '2026-06-03 00:44:02', '2026-06-03 00:44:02'),
(514, 13, 241, 0, 'failed', '[{\"question_id\":241,\"question_text\":\"In Google Meet, what happens to the video recording and transcription of a meeting once it concludes?\",\"user_answer\":0,\"is_correct\":false,\"correct_answer\":\"It is automatically saved into a folder in your Google Drive. \"}]', 6, '2026-06-03 00:44:11', '2026-06-03 00:44:11'),
(515, 13, 242, 100, 'passed', '[{\"question_id\":242,\"question_text\":\"Which specific mode in Google Docs is best for collaborating with a partner whose changes you want to review before they become permanent?\",\"user_answer\":0,\"is_correct\":true,\"correct_answer\":\"Restricted mode\"}]', 2, '2026-06-03 00:44:16', '2026-06-03 00:44:16'),
(516, 13, 243, 0, 'failed', '[{\"question_id\":243,\"question_text\":\"What happens if a contractor places a file into a business \'Shared Drive\' owned by your company?\",\"user_answer\":1,\"is_correct\":false,\"correct_answer\":\"The contractor receives a prompt to transfer ownership to the business. \"}]', 2, '2026-06-03 00:44:22', '2026-06-03 00:44:22'),
(517, 11, 254, 0, 'failed', '[{\"question_id\":254,\"question_text\":\"multiple choice option 1\",\"user_answer\":null,\"correct_answer\":\"Option 1\"}]', 10, '2026-06-04 17:06:13', '2026-06-04 17:06:13'),
(518, 11, 255, 100, 'passed', '[{\"question_id\":255,\"question_text\":\"true ang sagot \",\"user_answer\":0,\"is_correct\":true,\"correct_answer\":\"True\"}]', 4, '2026-06-04 17:06:20', '2026-06-04 17:06:20'),
(519, 21, 254, 100, 'passed', '[{\"question_id\":254,\"question_text\":\"multiple choice option 1\",\"user_answer\":0,\"is_correct\":true,\"correct_answer\":\"Option 1\"}]', 2, '2026-06-09 13:40:20', '2026-06-09 13:40:20'),
(520, 21, 255, 100, 'passed', '[{\"question_id\":255,\"question_text\":\"true ang sagot \",\"user_answer\":0,\"is_correct\":true,\"correct_answer\":\"True\"}]', 3, '2026-06-09 13:40:27', '2026-06-09 13:40:27'),
(521, 21, 164, 0, 'failed', '[{\"question_id\":164,\"question_text\":\"Which Xero subscription plan is specifically limited to sending 20 quotes or invoices and receiving 5 bills per month?\",\"user_answer\":0,\"is_correct\":false,\"correct_answer\":\"Early \"}]', 2, '2026-06-09 13:48:01', '2026-06-09 13:48:01'),
(522, 21, 165, 100, 'passed', '[{\"question_id\":165,\"question_text\":\"According to the guide, who is specifically recommended to create manual journal entries?\",\"user_answer\":0,\"is_correct\":true,\"correct_answer\":\"An accountant or bookkeeper\"}]', 2, '2026-06-09 13:48:07', '2026-06-09 13:48:07'),
(523, 21, 166, 0, 'failed', '[{\"question_id\":166,\"question_text\":\"What is the primary purpose of the \'Bank Reconciliation\' process in Xero?\",\"user_answer\":1,\"is_correct\":false,\"correct_answer\":\"To ensure cash records in Xero match the actual bank statement transactions \"}]', 4, '2026-06-09 13:48:14', '2026-06-09 13:48:14'),
(524, 21, 167, 0, 'failed', '[{\"question_id\":167,\"question_text\":\"In the Xero Sales Overview, how are overdue amounts visually identified to the user?\",\"user_answer\":1,\"is_correct\":false,\"correct_answer\":\"Flagged with a fiery red color \"}]', 2, '2026-06-09 13:48:20', '2026-06-09 13:48:20'),
(525, 21, 168, 0, 'failed', '[{\"question_id\":168,\"question_text\":\"Which specific reporting tool provides a snapshot of assets, liabilities, and equity at a specific point in time?\",\"user_answer\":1,\"is_correct\":false,\"correct_answer\":\"Balance Sheet \"}]', 2, '2026-06-09 13:48:25', '2026-06-09 13:48:25'),
(526, 21, 169, 100, 'passed', '[{\"question_id\":169,\"question_text\":\"What is the function of the \'Hubdoc\' application mentioned in the \'Do More with Xero\' menu?\",\"user_answer\":1,\"is_correct\":true,\"correct_answer\":\"To automate bookkeeping tasks like entering bills and receipts\"}]', 2, '2026-06-09 13:48:31', '2026-06-09 13:48:31'),
(527, 21, 170, 0, 'failed', '[{\"question_id\":170,\"question_text\":\"If a business deals with multiple employees and needs to track specific projects and multi-currency transactions, which plan is required?\",\"user_answer\":1,\"is_correct\":false,\"correct_answer\":\"Established \"}]', 4, '2026-06-09 13:48:38', '2026-06-09 13:48:38'),
(528, 21, 171, 0, 'failed', '[{\"question_id\":171,\"question_text\":\"What does the \'Jax\' feature in the navigation bar represent?\",\"user_answer\":1,\"is_correct\":false,\"correct_answer\":\"A brand new AI business companion \"}]', 2, '2026-06-09 13:48:44', '2026-06-09 13:48:44'),
(529, 21, 172, 100, 'passed', '[{\"question_id\":172,\"question_text\":\"In the Organization Settings, which section allows you to manage who has access to the Xero file and their permissions?\",\"user_answer\":2,\"is_correct\":true,\"correct_answer\":\"Users \"}]', 2, '2026-06-09 13:48:49', '2026-06-09 13:48:49'),
(530, 21, 173, 0, 'failed', '[{\"question_id\":173,\"question_text\":\"What distinguishes \'Purchase Orders\' from \'Bills\' in Xero\'s Purchases menu?\",\"user_answer\":2,\"is_correct\":false,\"correct_answer\":\"Purchase orders are for tracking orders with suppliers and don\'t impact accounts until converted \"}]', 2, '2026-06-09 13:48:55', '2026-06-09 13:48:55'),
(531, 21, 174, 0, 'failed', '[{\"question_id\":174,\"question_text\":\"Which specific data points does the \'snap and track\' feature automatically extract from a receipt photo?\",\"user_answer\":0,\"is_correct\":false,\"correct_answer\":\"Vendor, date, and amount \"}]', 2, '2026-06-09 14:05:21', '2026-06-09 14:05:21'),
(532, 21, 175, 0, 'failed', '[{\"question_id\":175,\"question_text\":\"How does MYOB assist users in meeting Australian Taxation Office (ATO) requirements?\",\"user_answer\":2,\"is_correct\":false,\"correct_answer\":\"By automating GST calculations and business activity statement reporting\"}]', 3, '2026-06-09 14:05:27', '2026-06-09 14:05:27'),
(533, 21, 176, 0, 'failed', '[{\"question_id\":176,\"question_text\":\"What is a primary benefit of linking a bank account directly to the MYOB software?\",\"user_answer\":1,\"is_correct\":false,\"correct_answer\":\"It automatically imports and matches transactions to reduce manual admin time\"}]', 2, '2026-06-09 14:05:32', '2026-06-09 14:05:32'),
(534, 21, 177, 0, 'failed', '[{\"question_id\":177,\"question_text\":\"According to the tutorial, what makes \'MYOB Solo\' distinct from standard spreadsheets for business management?\",\"user_answer\":1,\"is_correct\":false,\"correct_answer\":\"It is only accessible via desktop computers to ensure high security\"}]', 2, '2026-06-09 14:05:38', '2026-06-09 14:05:38'),
(535, 21, 178, 100, 'passed', '[{\"question_id\":178,\"question_text\":\"For \'Creative Freelancers,\' which specific accounting challenge does MYOB address according to the demo?\",\"user_answer\":1,\"is_correct\":true,\"correct_answer\":\"Budgeting and cash flow visibility for irregular income\"}]', 2, '2026-06-09 14:05:43', '2026-06-09 14:05:43'),
(536, 21, 179, 0, 'failed', '[{\"question_id\":179,\"question_text\":\"What is a key focus area for MYOB when supporting \'Trades\' professionals like builders and electricians?\",\"user_answer\":1,\"is_correct\":false,\"correct_answer\":\"On-site job tracking and managing materials or labor costs\"}]', 2, '2026-06-09 14:05:49', '2026-06-09 14:05:49'),
(537, 21, 180, 0, 'failed', '[{\"question_id\":180,\"question_text\":\"Which feature is highlighted as particularly useful for Health and Wellness professionals?\",\"user_answer\":1,\"is_correct\":false,\"correct_answer\":\"Class pass tracking and superannuation reporting\"}]', 2, '2026-06-09 14:05:54', '2026-06-09 14:05:54'),
(538, 21, 181, 100, 'passed', '[{\"question_id\":181,\"question_text\":\"How can users improve their professionalism when invoicing clients through MYOB?\",\"user_answer\":1,\"is_correct\":true,\"correct_answer\":\"By using customizable templates that incorporate their own branding\"}]', 2, '2026-06-09 14:05:59', '2026-06-09 14:05:59'),
(539, 21, 182, 0, 'failed', '[{\"question_id\":182,\"question_text\":\"What is one mentioned disadvantage for absolute beginners using MYOB?\",\"user_answer\":1,\"is_correct\":false,\"correct_answer\":\"The software might feel a bit complex to navigate initially\"}]', 2, '2026-06-09 14:06:05', '2026-06-09 14:06:05'),
(540, 21, 183, 0, 'failed', '[{\"question_id\":183,\"question_text\":\"For consultants and subject matter experts, MYOB emphasizes which financial metric?\",\"user_answer\":1,\"is_correct\":false,\"correct_answer\":\"Profitability tracking by client or project\"}]', 2, '2026-06-09 14:06:10', '2026-06-09 14:06:10'),
(541, 21, 184, 0, 'failed', '[{\"question_id\":184,\"question_text\":\"The acronym SAP originated from a phrase in which language?\",\"user_answer\":0,\"is_correct\":false,\"correct_answer\":\"German\"}]', 3, '2026-06-09 15:05:08', '2026-06-09 15:05:08'),
(542, 21, 185, 0, 'failed', '[{\"question_id\":185,\"question_text\":\"What does the \'ERP\' in SAP ERP stand for?\",\"user_answer\":0,\"is_correct\":false,\"correct_answer\":\"Enterprise Resource Planning\"}]', 3, '2026-06-09 15:05:14', '2026-06-09 15:05:14'),
(543, 21, 186, 100, 'passed', '[{\"question_id\":186,\"question_text\":\"Before founding SAP, the original five founders were employees of which technology giant?\",\"user_answer\":0,\"is_correct\":true,\"correct_answer\":\"IBM\"}]', 4, '2026-06-09 15:05:22', '2026-06-09 15:05:22'),
(544, 21, 187, 0, 'failed', '[{\"question_id\":187,\"question_text\":\"Which analogy does the author use to explain why both the company and its primary software product share the name \'SAP\'?\",\"user_answer\":0,\"is_correct\":false,\"correct_answer\":\"Coca-Cola\"}]', 3, '2026-06-09 15:05:28', '2026-06-09 15:05:28'),
(545, 21, 188, 0, 'failed', '[{\"question_id\":188,\"question_text\":\"According to the source material, approximately what percentage of the world\'s transaction revenue touches an SAP system?\",\"user_answer\":2,\"is_correct\":false,\"correct_answer\":\"77%\"}]', 2, '2026-06-09 15:05:34', '2026-06-09 15:05:34'),
(546, 21, 189, 0, 'failed', '[{\"question_id\":189,\"question_text\":\"Which of the following is NOT listed as one of the primary business areas typically supported by ERP systems?\",\"user_answer\":1,\"is_correct\":false,\"correct_answer\":\"Hardware Manufacturing\"}]', 6, '2026-06-09 15:05:43', '2026-06-09 15:05:43'),
(547, 21, 190, 0, 'failed', '[{\"question_id\":190,\"question_text\":\"In the context of the German brand BMW, used as an analogy for SAP, what does the \'B\' stand for?\",\"user_answer\":2,\"is_correct\":false,\"correct_answer\":\"Bavarian\"}]', 4, '2026-06-09 15:05:51', '2026-06-09 15:05:51'),
(548, 21, 191, 0, 'failed', '[{\"question_id\":191,\"question_text\":\"What is the official full legal name of the SAP company mentioned in the material?\",\"user_answer\":2,\"is_correct\":false,\"correct_answer\":\"SAP SE\"}]', 3, '2026-06-09 15:05:57', '2026-06-09 15:05:57'),
(549, 21, 192, 0, 'failed', '[{\"question_id\":192,\"question_text\":\"What is the primary reason the author suggests that even many SAP specialists do not remember the full German name of the company?\",\"user_answer\":2,\"is_correct\":false,\"correct_answer\":\"The name is long and complex, especially for non-German speakers.\"}]', 5, '2026-06-09 15:06:06', '2026-06-09 15:06:06'),
(550, 21, 193, 0, 'failed', '[{\"question_id\":193,\"question_text\":\"Which of these is mentioned as a specific SAP product used for managing customer relations?\",\"user_answer\":2,\"is_correct\":false,\"correct_answer\":\"SAP CRM\"}]', 2, '2026-06-09 15:06:12', '2026-06-09 15:06:12');

-- --------------------------------------------------------

--
-- Table structure for table `user_video_watched`
--

CREATE TABLE `user_video_watched` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `video_id` int(11) NOT NULL,
  `watched_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_video_watched`
--

INSERT INTO `user_video_watched` (`id`, `user_id`, `course_id`, `video_id`, `watched_at`) VALUES
(44, 11, 44, 49, '2026-05-08 15:35:09'),
(46, 11, 43, 48, '2026-05-11 15:57:00'),
(47, 11, 42, 47, '2026-05-11 15:59:41'),
(50, 11, 36, 41, '2026-05-12 00:55:57'),
(53, 11, 39, 44, '2026-05-12 01:39:16'),
(60, 15, 44, 49, '2026-05-12 03:13:54'),
(64, 11, 37, 42, '2026-05-12 06:40:39'),
(66, 15, 43, 48, '2026-05-12 08:20:26'),
(69, 11, 38, 43, '2026-05-12 10:31:29'),
(71, 21, 42, 47, '2026-05-12 12:27:39'),
(72, 21, 43, 48, '2026-05-12 12:29:27'),
(73, 21, 44, 49, '2026-05-12 12:30:42'),
(74, 13, 44, 49, '2026-06-03 00:19:48'),
(75, 13, 43, 48, '2026-06-03 00:43:19'),
(76, 11, 51, 50, '2026-06-04 17:05:52'),
(77, 21, 51, 50, '2026-06-09 13:40:15'),
(78, 21, 36, 41, '2026-06-09 13:47:55'),
(79, 21, 37, 42, '2026-06-09 14:05:16'),
(80, 21, 38, 43, '2026-06-09 15:05:02');

-- --------------------------------------------------------

--
-- Table structure for table `videos`
--

CREATE TABLE `videos` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `video_url` varchar(500) DEFAULT NULL,
  `video_type` enum('upload','external') DEFAULT 'upload'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `videos`
--

INSERT INTO `videos` (`id`, `course_id`, `title`, `description`, `video_url`, `video_type`) VALUES
(41, 36, 'Essential Tutorial', '<p>Watch the video, then take the quizzes.</p>', 'https://youtu.be/S8DaoKc87sI?si=qqyAdobJpja0MowA', 'external'),
(42, 37, 'Mastering MYOB', '<p>Watch the video, then take the quizzes.</p>', 'https://youtu.be/uDjcObi5WfY?si=dPBp_neUF2l9wlKT', 'external'),
(43, 38, 'The Global Giant: An Introduction to SAP and ERP', '<p>Watch the video, then take the quizzes.</p>', 'https://youtu.be/JjrcxsniXvc?si=DRg8qc8FTP287sfa', 'external'),
(44, 39, 'Essential Tutorial', '<p>Watch the video, then take the quizzes.</p>', 'https://youtu.be/e0j3DE1lrmA?si=4m1R364V0xENByW0', 'external'),
(45, 40, 'The Beginner\'s Guide to Mastering Trello Workflow', '<p>Watch the video, then take the quizzes.</p>', 'https://youtu.be/geRKHFzTxNY?si=UKroP6zxUR_7YRKI', 'external'),
(46, 41, 'ClickUp: The Complete Beginner', '<p>Watch the video, then take the quizzes.</p>', 'https://youtu.be/0Q8aA0Lwuyc?si=3Z75FqgGKCS-C6fx', 'external'),
(47, 42, 'Mastering Microsoft Word: The Complete Beginner\'s Course', '<p>Watch the video, then take the quizzes.</p>', 'https://youtu.be/2MCmnr2L50o?si=kbnOHrn0UeWiIM1A', 'external'),
(48, 43, 'Mastering Google Workspace', '<p>Watch the video, then take the quizzes.</p>', 'https://youtu.be/FwT6_JFAk5Y?si=J1qRgaH34cEbltE2', 'external'),
(49, 44, 'The Beginner\'s Guide to Mastering Slack Workspace Fundamentals', '<p>Watch the video, then take the quizzes.</p>', 'https://youtu.be/2CGppw8cHyU?si=r0TUtfIj7-2XpYff', 'external'),
(50, 51, 'Essential Tutorial', '<p>Watch the video, then take the quizzes.</p>', 'https://youtu.be/5L35LcXrf2w?si=bOCJ_fZWTQPohS5L', 'external');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `academy_news`
--
ALTER TABLE `academy_news`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `certificates`
--
ALTER TABLE `certificates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `certificate_number` (`certificate_number`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_certificates_user_id` (`user_id`),
  ADD KEY `idx_certificates_course_id` (`course_id`),
  ADD KEY `idx_course_lookup` (`course_id`,`course_type`),
  ADD KEY `idx_user_course` (`user_id`,`course_id`,`course_type`);

--
-- Indexes for table `certificate_claims`
--
ALTER TABLE `certificate_claims`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_certificate_id` (`certificate_id`),
  ADD KEY `idx_claimed_by_admin` (`claimed_by_admin_id`),
  ADD KEY `idx_claimed_at` (`claimed_at`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `employee_logs`
--
ALTER TABLE `employee_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `options`
--
ALTER TABLE `options`
  ADD PRIMARY KEY (`id`),
  ADD KEY `question_id` (`question_id`);

--
-- Indexes for table `questions`
--
ALTER TABLE `questions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `quiz_id` (`quiz_id`);

--
-- Indexes for table `quizzes`
--
ALTER TABLE `quizzes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `quiz_settings`
--
ALTER TABLE `quiz_settings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `retake_requests`
--
ALTER TABLE `retake_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `user_courses`
--
ALTER TABLE `user_courses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `idx_user_courses_user_id` (`user_id`),
  ADD KEY `idx_user_courses_course_id` (`course_id`);

--
-- Indexes for table `user_quiz_answers`
--
ALTER TABLE `user_quiz_answers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `attempt_id` (`attempt_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `quiz_id` (`quiz_id`),
  ADD KEY `question_id` (`question_id`);

--
-- Indexes for table `user_quiz_attempts`
--
ALTER TABLE `user_quiz_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `quiz_id` (`quiz_id`),
  ADD KEY `idx_user_quiz_attempts_user_id` (`user_id`),
  ADD KEY `idx_user_quiz_attempts_quiz_id` (`quiz_id`);

--
-- Indexes for table `user_video_watched`
--
ALTER TABLE `user_video_watched`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `video_id` (`video_id`);

--
-- Indexes for table `videos`
--
ALTER TABLE `videos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `academy_news`
--
ALTER TABLE `academy_news`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `certificates`
--
ALTER TABLE `certificates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `certificate_claims`
--
ALTER TABLE `certificate_claims`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT for table `employee_logs`
--
ALTER TABLE `employee_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=977;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `options`
--
ALTER TABLE `options`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=791;

--
-- AUTO_INCREMENT for table `questions`
--
ALTER TABLE `questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=256;

--
-- AUTO_INCREMENT for table `quizzes`
--
ALTER TABLE `quizzes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=256;

--
-- AUTO_INCREMENT for table `quiz_settings`
--
ALTER TABLE `quiz_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `retake_requests`
--
ALTER TABLE `retake_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `user_courses`
--
ALTER TABLE `user_courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT for table `user_quiz_answers`
--
ALTER TABLE `user_quiz_answers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=551;

--
-- AUTO_INCREMENT for table `user_quiz_attempts`
--
ALTER TABLE `user_quiz_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=551;

--
-- AUTO_INCREMENT for table `user_video_watched`
--
ALTER TABLE `user_video_watched`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=81;

--
-- AUTO_INCREMENT for table `videos`
--
ALTER TABLE `videos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `certificates`
--
ALTER TABLE `certificates`
  ADD CONSTRAINT `certificates_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `certificate_claims`
--
ALTER TABLE `certificate_claims`
  ADD CONSTRAINT `certificate_claims_ibfk_1` FOREIGN KEY (`certificate_id`) REFERENCES `certificates` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `employee_logs`
--
ALTER TABLE `employee_logs`
  ADD CONSTRAINT `employee_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `options`
--
ALTER TABLE `options`
  ADD CONSTRAINT `options_ibfk_1` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `questions`
--
ALTER TABLE `questions`
  ADD CONSTRAINT `questions_ibfk_1` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `quizzes`
--
ALTER TABLE `quizzes`
  ADD CONSTRAINT `quizzes_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `quiz_settings`
--
ALTER TABLE `quiz_settings`
  ADD CONSTRAINT `quiz_settings_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_courses`
--
ALTER TABLE `user_courses`
  ADD CONSTRAINT `user_courses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_courses_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_quiz_answers`
--
ALTER TABLE `user_quiz_answers`
  ADD CONSTRAINT `user_quiz_answers_ibfk_1` FOREIGN KEY (`attempt_id`) REFERENCES `user_quiz_attempts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_quiz_answers_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_quiz_answers_ibfk_3` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_quiz_answers_ibfk_4` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_quiz_attempts`
--
ALTER TABLE `user_quiz_attempts`
  ADD CONSTRAINT `user_quiz_attempts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_quiz_attempts_ibfk_2` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_video_watched`
--
ALTER TABLE `user_video_watched`
  ADD CONSTRAINT `user_video_watched_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_video_watched_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_video_watched_ibfk_3` FOREIGN KEY (`video_id`) REFERENCES `videos` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `videos`
--
ALTER TABLE `videos`
  ADD CONSTRAINT `videos_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
