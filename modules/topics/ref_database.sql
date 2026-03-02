-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Dec 24, 2024 at 04:29 PM
-- Server version: 10.11.11-MariaDB
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u310178187_portal`
--

-- --------------------------------------------------------

--
-- Table structure for table `tbltopics`
--

CREATE TABLE `tbltopics` (
  `id` int(11) NOT NULL,
  `topicid` varchar(255) NOT NULL,
  `topictitle` varchar(255) NOT NULL,
  `position` int(11) DEFAULT 0,
  `data` longtext DEFAULT NULL,
  `log` text NOT NULL,
  `action_type_code` varchar(50) DEFAULT NULL,
  `action_state_code` varchar(50) DEFAULT NULL,
  `target_id` int(11) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  `datecreated` datetime DEFAULT current_timestamp(),
  `dateupdated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `automation_id` varchar(250) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbltopic_action_buttons`
--

CREATE TABLE `tbltopic_action_buttons` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `button_type` varchar(50) NOT NULL DEFAULT 'primary',
  `workflow_id` varchar(255) NOT NULL,
  `trigger_type` enum('webhook','native') NOT NULL DEFAULT 'webhook',
  `target_action_type` varchar(255) DEFAULT NULL,
  `ignore_types` text DEFAULT NULL,
  `target_action_state` varchar(255) DEFAULT NULL,
  `ignore_states` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `settings` text DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `order` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbltopic_action_states`
--

CREATE TABLE `tbltopic_action_states` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `action_state_code` varchar(50) NOT NULL,
  `action_type_code` varchar(50) NOT NULL,
  `datecreated` datetime DEFAULT current_timestamp(),
  `dateupdated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `color` varchar(7) DEFAULT '#000000',
  `position` int(11) DEFAULT 0,
  `valid_data` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbltopic_action_states`
--

INSERT INTO `tbltopic_action_states` (`id`, `name`, `action_state_code`, `action_type_code`, `datecreated`, `dateupdated`, `color`, `position`, `valid_data`) VALUES
(1, 'Thành công', 'success', 'init', '2024-11-20 16:28:42', '2024-11-24 22:00:43', '#28a745', 2, 0),
(2, 'Thất bại', 'fail', 'init', '2024-11-20 16:28:42', '2024-11-24 14:10:48', '#dc3545', 1, 0),
(3, 'Có', 'Yes', 'GenerateSearchKeyword', '2024-11-20 19:51:50', '2024-11-28 06:31:46', '#1a73e8', 1, 0),
(4, 'Không', 'No', 'GenerateSearchKeyword', '2024-11-20 19:51:50', '2024-11-28 06:31:46', '#1a73e8', 2, 0),
(5, 'Hoàn thành', 'Completed', 'GenerateSearchKeyword', '2024-11-20 19:51:50', '2024-11-22 07:20:20', '#28a745', 5, 0),
(6, 'Lỗi', 'Failed', 'GenerateSearchKeyword', '2024-11-20 19:51:50', '2024-11-22 07:17:10', '#dc3545', 4, 0),
(7, 'Đang thực hiện', 'Processing', 'GenerateSearchKeyword', '2024-11-20 19:51:50', '2024-11-22 07:20:20', '#ffc107', 3, 0),
(8, 'Có', 'ImgYes', 'SearchImageToggle', '2024-11-20 19:54:07', '2024-11-28 06:31:46', '#ff9800', 1, 0),
(9, 'Không', 'ImgNo', 'SearchImageToggle', '2024-11-20 19:54:07', '2024-11-28 06:31:46', '#ff9800', 2, 0),
(10, 'Hoàn thành', 'ImgCompleted', 'SearchImageToggle', '2024-11-20 19:54:07', '2024-11-28 15:44:22', '#28a745', 5, 1),
(11, 'Lỗi', 'ImgFailed', 'SearchImageToggle', '2024-11-20 19:54:07', '2024-11-22 07:20:21', '#dc3545', 4, 0),
(12, 'Đang thực hiện', 'ImgProcessing', 'SearchImageToggle', '2024-11-20 19:54:07', '2024-11-22 07:20:21', '#ffc107', 3, 0),
(13, 'Có', 'GenImgYes', 'ImageGenerateToggle', '2024-11-20 19:55:22', '2024-11-28 06:31:46', '#9c27b0', 1, 0),
(14, 'Không', 'GenImgNo', 'ImageGenerateToggle', '2024-11-20 19:55:22', '2024-11-28 06:31:46', '#9c27b0', 2, 0),
(15, 'Hoàn thành', 'GenImgCompleted', 'ImageGenerateToggle', '2024-11-20 19:55:22', '2024-11-28 15:43:32', '#28a745', 5, 1),
(16, 'Lỗi', 'GenImgFailed', 'ImageGenerateToggle', '2024-11-20 19:55:22', '2024-11-22 07:20:21', '#dc3545', 4, 0),
(17, 'Đang thực hiện', 'GenImgProcessing', 'ImageGenerateToggle', '2024-11-20 19:55:22', '2024-11-22 07:20:21', '#ffc107', 3, 0),
(18, 'Không', 'ExecNo', 'ExecutionTag', '2024-11-20 19:57:02', '2024-11-22 07:20:21', '#6c757d', 1, 0),
(19, 'Đang viết bài', 'ExecWriting', 'ExecutionTag', '2024-11-20 19:57:02', '2024-11-22 13:36:30', '#ffc107', 4, 0),
(20, 'Viết nháp (Chưa Publish)', 'ExecDraft', 'ExecutionTag', '2024-11-20 19:57:02', '2024-11-23 00:51:33', '#ffc107', 5, 0),
(21, 'Hoàn thành', 'ExecCompleted', 'ExecutionTag', '2024-11-20 19:57:02', '2024-11-22 13:36:30', '#28a745', 15, 0),
(22, 'Audit', 'ExecAudit', 'ExecutionTag', '2024-11-20 19:57:02', '2024-11-22 13:36:30', '#ffc107', 6, 0),
(23, 'PostAudit', 'ExecPostAudit', 'ExecutionTag', '2024-11-20 19:57:02', '2024-11-22 13:36:30', '#ffc107', 7, 0),
(24, 'Viết nội dung Meta+Social', 'ExecSocialAudit', 'ExecutionTag', '2024-11-20 19:57:02', '2024-11-23 00:51:58', '#ffc107', 10, 0),
(25, 'Thực hiện Đăng Social', 'ExecSocialPost', 'ExecutionTag_ExecSocialMedia', '2024-11-20 19:57:02', '2024-11-30 11:47:42', '#17a2b8', 12, 0),
(26, 'Chuẩn bị lên lịch Social', 'ExecSocialScheduled', 'ExecutionTag_ExecSocialMedia', '2024-11-20 19:57:02', '2024-12-06 21:48:22', '#17a2b8', 13, 1),
(27, 'Lỗi', 'ExecFailed', 'ExecutionTag', '2024-11-20 19:57:02', '2024-11-22 13:36:30', '#dc3545', 16, 0),
(28, 'Đã đăng Social', 'ExecSocialPosted', 'ExecutionTag_ExecSocialMedia', '2024-11-20 19:57:02', '2024-11-30 12:21:57', '#28a745', 14, 1),
(29, 'Thêm hình ảnh minh họa (Google)', 'ExecPostAuditGallery', 'ExecutionTag', '2024-11-20 19:57:02', '2024-11-23 00:53:12', '#ffc107', 8, 0),
(30, 'TEST', 'ExecTest', 'ExecutionTag', '2024-11-20 19:57:02', '2024-11-22 13:36:30', '#6c757d', 17, 0),
(31, 'Đã thêm hình ảnh minh họa (Google)', 'ExecDonePostAuditGallery', 'ExecutionTag', '2024-11-20 19:57:02', '2024-11-23 00:58:39', '#28a745', 9, 0),
(32, 'Tạm ngưng', 'ExecPendingSocialPost', 'ExecutionTag_ExecSocialMedia', '2024-11-20 19:57:02', '2024-11-30 11:47:21', '#17a2b8', 11, 0),
(33, 'Bắt đầu thực thi', 'ExecStart', 'ExecutionTag', '2024-11-22 20:31:11', '2024-11-28 06:31:46', '#4caf50', 2, 0),
(34, 'Chọn phong cách viết bài', 'ExecChooseStyle', 'ExecutionTag_ExecWriting', '2024-11-22 20:36:22', '2024-12-03 09:40:26', '#4caf50', 1, 0),
(35, 'Bắt đầu viết nội dung', 'ExecutionTag_ExecWriting_Start', 'ExecutionTag_ExecWriting', '2024-11-22 20:44:47', '2024-12-03 09:40:26', '#f44336', 2, 0),
(36, 'Đã viết 1 phần', 'ExecutionTag_ExecWriting_Partial', 'ExecutionTag_ExecWriting', '2024-11-23 01:21:48', '2024-12-03 09:40:26', '#f44336', 3, 1),
(37, 'Đã viết nội dung (Chưa ảnh)', 'ExecutionTag_ExecWriting_Complete', 'ExecutionTag_ExecWriting', '2024-11-23 01:40:57', '2024-12-03 09:40:24', '#f44336', 5, 0),
(38, 'Đã tạo bài nháp (Wordpress)', 'ExecutionTag_ExecWriting_PostCreated', 'ExecutionTag_ExecWriting', '2024-11-23 12:04:26', '2024-12-03 09:40:24', '#f44336', 6, 1),
(39, 'Bắt đầu', 'ImageGeneration_MultiPurpose_Start', 'ImageGeneration_MultiPurpose', '2024-11-23 12:39:56', '2024-11-28 06:31:46', '#00bcd4', 1, 0),
(40, 'Kết thúc', 'ImageGeneration_MultiPurpose_Complete', 'ImageGeneration_MultiPurpose', '2024-11-23 12:40:36', '2024-11-28 15:43:51', '#00bcd4', 3, 1),
(41, 'Lỗi', 'ImageGeneration_MultiPurpose_Failed', 'ImageGeneration_MultiPurpose', '2024-11-23 12:41:15', '2024-11-28 06:31:46', '#00bcd4', 4, 0),
(42, 'Đang thực hiện', 'ImageGeneration_MultiPurpose_Processing', 'ImageGeneration_MultiPurpose', '2024-11-23 13:10:46', '2024-11-28 06:31:46', '#00bcd4', 2, 0),
(43, 'Tải file lên', 'ExecutionTag_ExecWriting_Upload', 'ExecutionTag_ExecWriting', '2024-11-24 00:04:40', '2024-12-03 16:40:47', '#28a745', 4, 1),
(44, 'Đã thêm', 'BuildPostStructure_Success', 'BuildPostStructure', '2024-11-24 20:47:17', '2024-11-28 16:07:58', '#28a745', 2, 1),
(45, 'Lỗi', 'BuildPostStructure_Error', 'BuildPostStructure', '2024-11-24 20:47:46', '2024-11-28 06:31:46', '#607d8b', 1, 0),
(46, 'Lỗi', 'ExecSocialPost_Error', 'ExecutionTag_ExecSocialMedia', '2024-12-01 14:22:53', '2024-12-01 14:37:40', '#ff0000', 21, 0),
(47, 'Đã tạo bài viết hoàn chỉnh', 'ExecutionTag_ExecWriting_PostCompleted', 'ExecutionTag_ExecWriting', '2024-12-03 16:39:55', '2024-12-03 09:40:24', '#000000', 7, 1),
(48, 'Bắt đầu Audit bài viết', 'ExecutionTag_ExecAudit_PostAuditStart', 'ExecutionTag_ExecAudit', '2024-12-03 21:41:21', '2024-12-03 18:49:40', '#000000', 1, 0),
(49, 'Lỗi', 'ExecutionTag_ExecAudit_PostAuditError', 'ExecutionTag_ExecAudit', '2024-12-03 21:44:32', '2024-12-03 18:49:40', '#000000', 2, 0),
(50, 'Đã audit', 'ExecutionTag_ExecAudit_PostAuditComplete', 'ExecutionTag_ExecAudit', '2024-12-04 01:35:22', '2024-12-03 18:49:40', '#000000', 3, 1),
(51, 'Bắt đầu Audit Social', 'ExecutionTag_ExecAudit_SocialAuditStart', 'ExecutionTag_ExecAudit', '2024-12-04 01:48:08', '2024-12-03 18:49:40', '#000000', 4, 0),
(52, 'Lỗi', 'ExecutionTag_ExecAudit_SocialAuditError', 'ExecutionTag_ExecAudit', '2024-12-04 01:48:48', '2024-12-03 18:49:42', '#000000', 5, 0),
(53, 'Hoàn thành Social Audit', 'ExecutionTag_ExecAudit_SocialAuditCompleted', 'ExecutionTag_ExecAudit', '2024-12-04 01:49:26', '2024-12-03 19:00:46', '#000000', 7, 1),
(54, 'Đã viết nội dung Social', 'ExecutionTag_ExecAudit_SocialAuditPartial', 'ExecutionTag_ExecAudit', '2024-12-04 02:00:39', '2024-12-04 02:00:47', '#000000', 6, 1);

-- --------------------------------------------------------

--
-- Table structure for table `tbltopic_action_types`
--

CREATE TABLE `tbltopic_action_types` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `action_type_code` varchar(50) NOT NULL,
  `datecreated` datetime DEFAULT current_timestamp(),
  `dateupdated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `position` int(11) DEFAULT 0,
  `parent_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbltopic_action_types`
--

INSERT INTO `tbltopic_action_types` (`id`, `name`, `action_type_code`, `datecreated`, `dateupdated`, `position`, `parent_id`) VALUES
(1, 'Nạp vào danh sách Google Sheet', 'init', '2024-11-20 16:28:42', '2024-12-12 18:25:14', 1, NULL),
(2, 'Tạo từ khóa tìm kiếm', 'GenerateSearchKeyword', '2024-11-20 19:51:50', '2024-11-24 13:29:37', 3, 1),
(3, 'Tìm hình minh họa', 'SearchImageToggle', '2024-11-20 19:54:07', '2024-11-24 13:29:37', 4, 1),
(4, 'Tạo ảnh bìa', 'ImageGenerateToggle', '2024-11-20 19:55:22', '2024-11-24 13:29:17', 6, 7),
(5, 'Công việc chính', 'ExecutionTag', '2024-11-20 19:57:02', '2024-12-04 02:30:16', 8, NULL),
(6, 'Viết bài', 'ExecutionTag_ExecWriting', '2024-11-22 20:44:19', '2024-12-03 08:56:33', 9, 5),
(7, 'Tác vụ hình ảnh', 'ImageGeneration', '2024-11-23 12:27:13', '2024-11-24 13:29:17', 5, NULL),
(8, 'Tạo ảnh khác', 'ImageGeneration_MultiPurpose', '2024-11-23 12:38:43', '2024-11-24 13:29:17', 7, 7),
(9, 'Xây dựng cấu trúc bài viết', 'BuildPostStructure', '2024-11-24 20:29:09', '2024-11-24 13:29:37', 2, 1),
(10, 'Social Media', 'ExecutionTag_ExecSocialMedia', '2024-11-30 11:12:22', '2024-12-03 08:56:24', 11, 5),
(11, 'Audit', 'ExecutionTag_ExecAudit', '2024-12-03 15:56:05', '2024-12-03 08:56:33', 10, 5);

-- --------------------------------------------------------

--
-- Table structure for table `tbltopic_automation_logs`
--

CREATE TABLE `tbltopic_automation_logs` (
  `id` int(11) NOT NULL,
  `topic_id` varchar(255) NOT NULL,
  `automation_id` varchar(250) NOT NULL,
  `workflow_id` varchar(250) NOT NULL,
  `status` varchar(50) DEFAULT 'pending',
  `response_data` text DEFAULT NULL,
  `datecreated` datetime DEFAULT current_timestamp(),
  `dateupdated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbltopic_controller`
--

CREATE TABLE `tbltopic_controller` (
  `id` int(11) NOT NULL,
  `controller_id` int(11) NOT NULL,
  `topic_id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `datecreated` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbltopic_controllers`
--

CREATE TABLE `tbltopic_controllers` (
  `id` int(11) NOT NULL,
  `status` tinyint(1) DEFAULT 1,
  `site` varchar(255) DEFAULT NULL,
  `platform` varchar(255) DEFAULT NULL,
  `blog_id` varchar(100) DEFAULT NULL,
  `logo_url` text DEFAULT NULL,
  `slogan` text DEFAULT NULL,
  `writing_style` text DEFAULT NULL,
  `emails` text DEFAULT NULL,
  `api_token` varchar(255) DEFAULT NULL,
  `project_id` varchar(100) DEFAULT NULL,
  `seo_task_sheet_id` varchar(100) DEFAULT NULL,
  `raw_data` varchar(100) DEFAULT NULL,
  `action_1` text DEFAULT NULL,
  `action_2` text DEFAULT NULL,
  `page_mapping` varchar(100) DEFAULT NULL,
  `datecreated` datetime DEFAULT current_timestamp(),
  `dateupdated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbltopic_master`
--

CREATE TABLE `tbltopic_master` (
  `id` int(11) NOT NULL,
  `topicid` varchar(255) NOT NULL,
  `topictitle` varchar(255) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  `datecreated` datetime DEFAULT current_timestamp(),
  `dateupdated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `controller_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbltopic_target`
--

CREATE TABLE `tbltopic_target` (
  `id` int(11) NOT NULL,
  `target_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `target_type` varchar(50) NOT NULL,
  `status` tinyint(1) DEFAULT 1,
  `datecreated` datetime DEFAULT current_timestamp(),
  `dateupdated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbltopic_target`
--

INSERT INTO `tbltopic_target` (`id`, `target_id`, `title`, `target_type`, `status`, `datecreated`, `dateupdated`) VALUES
(0, 1, 'No target', 'NOTARGET', 1, '2024-11-23 18:43:21', '2024-11-24 11:46:49'),
(2, 1, 'Topic', 'TOPIC', 1, '2024-11-24 17:51:56', '2024-11-24 17:51:56'),
(3, 2, 'Toplist - Single Item Raw', 'TOPLIST_SINGLE_ITEM_RAW', 1, '2024-11-24 18:37:50', '2024-11-24 20:40:32'),
(4, 3, 'Error', 'ERROR', 1, '2024-11-24 19:19:49', '2024-11-24 19:19:49'),
(5, 4, 'Toplist - Single Item Raw (Backup)', 'TOPLIST_SINGLE_ITEM_RAW_BACKUP', 1, '2024-12-02 01:30:56', '2024-12-02 01:30:56'),
(6, 5, 'Wordpress Article', 'WORDPRESS_POST', 1, '2024-12-02 11:54:10', '2024-12-02 11:54:28'),
(7, 6, 'Wordpress Image', 'WORDPRESS_MEDIA', 1, '2024-12-02 11:57:04', '2024-12-02 11:57:04'),
(8, 7, 'UNSPLASH MAGE GALLERY', 'UNSPLASH_IMAGE_GALLERY', 1, '2024-12-02 14:31:34', '2024-12-02 14:31:42'),
(9, 8, 'Google Sheet Raw Item', 'GOOGLESHEET_RAW_ITEM', 1, '2024-12-05 12:58:26', '2024-12-05 12:58:26');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tbltopics`
--
ALTER TABLE `tbltopics`
  ADD PRIMARY KEY (`id`),
  ADD KEY `topicid` (`topicid`),
  ADD KEY `idx_target_id` (`target_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `action_type_code` (`action_type_code`),
  ADD KEY `action_state_code` (`action_state_code`),
  ADD KEY `idx_automation_id` (`automation_id`);

--
-- Indexes for table `tbltopic_action_buttons`
--
ALTER TABLE `tbltopic_action_buttons`
  ADD PRIMARY KEY (`id`),
  ADD KEY `trigger_type` (`trigger_type`);

--
-- Indexes for table `tbltopic_action_states`
--
ALTER TABLE `tbltopic_action_states`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `action_state_code` (`action_state_code`),
  ADD KEY `states_action_type_fk` (`action_type_code`);

--
-- Indexes for table `tbltopic_action_types`
--
ALTER TABLE `tbltopic_action_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `action_type_code` (`action_type_code`),
  ADD KEY `idx_parent_id` (`parent_id`);

--
-- Indexes for table `tbltopic_automation_logs`
--
ALTER TABLE `tbltopic_automation_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `topic_id` (`topic_id`),
  ADD KEY `automation_id` (`automation_id`),
  ADD KEY `workflow_id` (`workflow_id`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `tbltopic_controller`
--
ALTER TABLE `tbltopic_controller`
  ADD PRIMARY KEY (`id`),
  ADD KEY `controller_id` (`controller_id`),
  ADD KEY `topic_id` (`topic_id`),
  ADD KEY `staff_id` (`staff_id`);

--
-- Indexes for table `tbltopic_controllers`
--
ALTER TABLE `tbltopic_controllers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tbltopic_master`
--
ALTER TABLE `tbltopic_master`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `topicid` (`topicid`),
  ADD KEY `fk_topic_master_controller` (`controller_id`);

--
-- Indexes for table `tbltopic_target`
--
ALTER TABLE `tbltopic_target`
  ADD PRIMARY KEY (`id`),
  ADD KEY `target_type` (`target_type`),
  ADD KEY `target_id` (`target_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tbltopics`
--
ALTER TABLE `tbltopics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbltopic_action_buttons`
--
ALTER TABLE `tbltopic_action_buttons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbltopic_action_states`
--
ALTER TABLE `tbltopic_action_states`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `tbltopic_action_types`
--
ALTER TABLE `tbltopic_action_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `tbltopic_automation_logs`
--
ALTER TABLE `tbltopic_automation_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbltopic_controller`
--
ALTER TABLE `tbltopic_controller`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbltopic_controllers`
--
ALTER TABLE `tbltopic_controllers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbltopic_master`
--
ALTER TABLE `tbltopic_master`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbltopic_target`
--
ALTER TABLE `tbltopic_target`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tbltopics`
--
ALTER TABLE `tbltopics`
  ADD CONSTRAINT `fk_topic_action_state` FOREIGN KEY (`action_state_code`) REFERENCES `tbltopic_action_states` (`action_state_code`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_topic_action_type` FOREIGN KEY (`action_type_code`) REFERENCES `tbltopic_action_types` (`action_type_code`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_topic_master` FOREIGN KEY (`topicid`) REFERENCES `tbltopic_master` (`topicid`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_topic_target` FOREIGN KEY (`target_id`) REFERENCES `tbltopic_target` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `tbltopic_action_states`
--
ALTER TABLE `tbltopic_action_states`
  ADD CONSTRAINT `states_action_type_fk` FOREIGN KEY (`action_type_code`) REFERENCES `tbltopic_action_types` (`action_type_code`) ON DELETE CASCADE,
  ADD CONSTRAINT `tbltopic_action_states_ibfk_1` FOREIGN KEY (`action_type_code`) REFERENCES `tbltopic_action_types` (`action_type_code`) ON DELETE CASCADE;

--
-- Constraints for table `tbltopic_action_types`
--
ALTER TABLE `tbltopic_action_types`
  ADD CONSTRAINT `fk_action_type_parent` FOREIGN KEY (`parent_id`) REFERENCES `tbltopic_action_types` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `tbltopic_automation_logs`
--
ALTER TABLE `tbltopic_automation_logs`
  ADD CONSTRAINT `fk_automation_topic` FOREIGN KEY (`topic_id`) REFERENCES `tbltopic_master` (`topicid`) ON DELETE CASCADE;

--
-- Constraints for table `tbltopic_controller`
--
ALTER TABLE `tbltopic_controller`
  ADD CONSTRAINT `fk_topic_controller_controller` FOREIGN KEY (`controller_id`) REFERENCES `tbltopic_controllers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_topic_controller_staff` FOREIGN KEY (`staff_id`) REFERENCES `tblstaff` (`staffid`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_topic_controller_topic` FOREIGN KEY (`topic_id`) REFERENCES `tbltopic_master` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tbltopic_master`
--
ALTER TABLE `tbltopic_master`
  ADD CONSTRAINT `fk_topic_master_controller` FOREIGN KEY (`controller_id`) REFERENCES `tbltopic_controllers` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
