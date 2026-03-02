<?php
defined('BASEPATH') or exit('No direct script access allowed');
$CI = &get_instance();


// check if column exists in table
if (!$CI->db->field_exists('deal_comment_id', db_prefix() . 'files')) {
    $CI->db->query("ALTER TABLE " . db_prefix() . "files ADD `deal_comment_id` INT NULL DEFAULT NULL AFTER `task_comment_id`;");
}

$CI->db->query("CREATE TABLE IF NOT EXISTS `tbl_deals` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` varchar(191) DEFAULT NULL,
  `deal_value` decimal(10,2) NOT NULL DEFAULT '0.00',
  `source_id` int DEFAULT NULL,
  `status` varchar(100) DEFAULT 'open',
  `notes` text,
  `pipeline_id` int DEFAULT NULL,
  `currency` varchar(64) NOT NULL DEFAULT 'USD',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `days_to_close` date DEFAULT NULL,
  `user_id` text,
  `project_id` int DEFAULT NULL,
  `invoice_id` int DEFAULT NULL,
  `client_id` text,
  `stage_id` int NOT NULL,
  `default_deal_owner` int NOT NULL,
  `convert_to_project` varchar(100) DEFAULT NULL,
  `lost_reason` text,
  `tax` decimal(18,2) DEFAULT NULL,
  `total_tax` text,
   `dealorder` INT NULL DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;");

$CI->db->query("CREATE TABLE IF NOT EXISTS `tbl_deals_comments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `content` text NOT NULL,
  `deal_id` int NOT NULL,
  `staffid` int NOT NULL,
  `contact_id` int NOT NULL DEFAULT '0',
  `file_id` int NOT NULL DEFAULT '0',
  `dateadded` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `file_id` (`file_id`),
  KEY `deal_id` (`deal_id`)
) ENGINE=InnoDB;");
$CI->db->query("CREATE TABLE IF NOT EXISTS `tbl_deals_email` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email_to` text,
  `email_cc` varchar(100) DEFAULT NULL,
  `deals_id` int NOT NULL,
  `subject` varchar(230) DEFAULT NULL,
  `message_body` varchar(512) DEFAULT NULL,
  `uploads` varchar(512) DEFAULT NULL,
  `user_id` int NOT NULL,
  `files` text NOT NULL,
  `uploaded_path` text NOT NULL,
  `file_name` text NOT NULL,
  `size` int NOT NULL,
  `ext` varchar(100) NOT NULL,
  `is_image` int NOT NULL,
  `message_time` datetime NOT NULL,
  `attach_file` text,
  `email_from` varchar(120) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;");
$CI->db->query("CREATE TABLE IF NOT EXISTS `tbl_deals_items` (
  `items_id` int NOT NULL AUTO_INCREMENT,
  `deals_id` int NOT NULL,
  `tax_rates_id` text,
  `item_tax_rate` decimal(18,2) NOT NULL DEFAULT '0.00',
  `item_tax_name` text,
  `item_tax_total` decimal(18,2) NOT NULL DEFAULT '0.00',
  `quantity` decimal(18,2) DEFAULT '0.00',
  `total_cost` decimal(18,2) DEFAULT '0.00',
  `item_name` varchar(255) DEFAULT 'Item Name',
  `item_desc` longtext,
  `unit_cost` decimal(18,2) DEFAULT '0.00',
  `order` int DEFAULT '0',
  `date_saved` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `unit` varchar(200) DEFAULT NULL,
  `hsn_code` text,
  `item_id` int DEFAULT '0',
  PRIMARY KEY (`items_id`)
) ENGINE=InnoDB;");
$CI->db->query("CREATE TABLE IF NOT EXISTS `tbl_deals_mettings` (
  `mettings_id` int NOT NULL AUTO_INCREMENT,
  `leads_id` int DEFAULT NULL,
  `opportunities_id` int DEFAULT NULL,
  `meeting_subject` varchar(200) NOT NULL,
  `attendees` varchar(300) NOT NULL,
  `user_id` int NOT NULL,
  `module` varchar(50) DEFAULT NULL,
  `module_field_id` int DEFAULT NULL,
  `start_date` varchar(100) NOT NULL,
  `end_date` varchar(100) NOT NULL,
  `location` varchar(100) NOT NULL,
  `description` mediumtext NOT NULL,
  PRIMARY KEY (`mettings_id`)
) ENGINE=InnoDB;");

$CI->db->query("DROP TABLE IF EXISTS `tbl_deals_pipelines`;");
$CI->db->query("CREATE TABLE IF NOT EXISTS `tbl_deals_pipelines` (
  `pipeline_id` int NOT NULL AUTO_INCREMENT,
  `pipeline_name` varchar(100) NOT NULL,
  `description` varchar(512) DEFAULT NULL,
  `order` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`pipeline_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4;");

$CI->db->query("INSERT INTO `tbl_deals_pipelines` (`pipeline_id`, `pipeline_name`, `description`, `order`) VALUES
(1, 'Sales', NULL, 0),
(2, 'Interview', NULL, 0),
(3, 'Store', NULL, 0);");
$CI->db->query("DROP TABLE IF EXISTS `tbl_deals_source`;");
$CI->db->query("CREATE TABLE IF NOT EXISTS `tbl_deals_source` (
  `source_id` int NOT NULL AUTO_INCREMENT,
  `source_name` varchar(100) NOT NULL,
  PRIMARY KEY (`source_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11;");

$CI->db->query("INSERT INTO `tbl_deals_source` (`source_id`, `source_name`) VALUES
(1, 'Facebook'),
(2, 'Google Organic'),
(3, 'Web'),
(4, 'Twitter'),
(6, 'Youtube'),
(7, 'Mailchimp'),
(8, 'Previous Client'),
(9, 'Email List'),
(10, 'Google Ads');");

$CI->db->query("DROP TABLE IF EXISTS `tbl_deals_stages`;");
$CI->db->query("CREATE TABLE IF NOT EXISTS `tbl_deals_stages` (
  `stage_id` int NOT NULL AUTO_INCREMENT,
  `stage_name` varchar(512) DEFAULT NULL,
  `pipeline_id` int NOT NULL,
  `stage_order` int NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`stage_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8;");
$CI->db->query("INSERT INTO `tbl_deals_stages` (`stage_id`, `stage_name`, `pipeline_id`, `stage_order`, `date`) VALUES
(1, 'Qualified To Buy', 1, 1, '2023-08-23 09:22:38'),
(2, 'Contact Made', 1, 2, '2023-08-23 09:41:24'),
(3, 'Presentation Scheduled', 1, 3, '2023-08-23 09:41:47'),
(4, 'Proposal Made', 1, 4, '2023-08-23 09:41:55'),
(5, 'Appointment Scheduled', 1, 5, '2023-08-23 09:42:13');");

$CI->db->query("CREATE TABLE IF NOT EXISTS `tbl_deal_activity_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `deal_id` int NOT NULL,
  `description` mediumtext NOT NULL,
  `additional_data` text,
  `date` datetime NOT NULL,
  `staffid` int NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `custom_activity` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;");

$CI->db->query("CREATE TABLE IF NOT EXISTS `tbl_deal_calls` (
  `calls_id` int NOT NULL AUTO_INCREMENT,
  `leads_id` int DEFAULT NULL,
  `opportunities_id` int DEFAULT NULL,
  `client_id` int DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `module` varchar(50) DEFAULT NULL,
  `module_field_id` int DEFAULT NULL,
  `date` varchar(20) DEFAULT NULL,
  `call_summary` varchar(200) NOT NULL,
  `call_type` varchar(50) DEFAULT NULL,
  `outcome` varchar(50) DEFAULT NULL,
  `duration` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`calls_id`)
) ENGINE=InnoDB;");

if (!$CI->db->field_exists('rel_type', 'tbl_deals')) {
    $CI->db->query("ALTER TABLE `tbl_deals` ADD `rel_type` VARCHAR(30) NULL DEFAULT NULL AFTER `client_id`, ADD `rel_id` INT(11) NULL DEFAULT NULL AFTER `rel_type`;");
}
$deal_send_email = [
    'type' => 'deal',
    'slug' => 'deal_send_email',
    'name' => 'Deal Send Email',
    'subject' => '{subject}',
    'message' => '{message}'
];
create_email_template($deal_send_email['subject'], $deal_send_email['message'], $deal_send_email['type'], $deal_send_email['name'], $deal_send_email['slug']);