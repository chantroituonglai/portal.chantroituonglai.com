<?php
defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();

// 1. Create topic_action_types table FIRST (because other tables reference it)
if (!$CI->db->table_exists(db_prefix() . 'topic_action_types')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . 'topic_action_types` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(100) NOT NULL,
        `action_type_code` varchar(50) NOT NULL,
        `datecreated` datetime DEFAULT current_timestamp(),
        `dateupdated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        `position` int(11) DEFAULT 0,
        `parent_id` int(11) DEFAULT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `action_type_code` (`action_type_code`),
        KEY `idx_parent_id` (`parent_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;');

    // Insert default action types
    $CI->db->query("INSERT INTO `" . db_prefix() . "topic_action_types` 
        (`name`, `action_type_code`, `position`) VALUES
        ('Viết bài', 'WRITING', 1),
        ('Kiểm duyệt nội dung', 'CONTENT_AUDIT', 2),
        ('Đăng bài', 'PUBLISHING', 3),
        ('Kiểm tra SEO', 'SEO_CHECK', 4),
        ('Kiểm duyệt hình ảnh', 'IMAGE_AUDIT', 5),
        ('Lên lịch đăng', 'SCHEDULE', 6)");
}

// 2. Create topic_action_states table
if (!$CI->db->table_exists(db_prefix() . 'topic_action_states')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . 'topic_action_states` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(100) NOT NULL,
        `action_state_code` varchar(50) NOT NULL,
        `action_type_code` varchar(50) NOT NULL,
        `datecreated` datetime DEFAULT current_timestamp(),
        `dateupdated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        `color` varchar(7) DEFAULT "#000000",
        `position` int(11) DEFAULT 0,
        PRIMARY KEY (`id`),
        UNIQUE KEY `action_state_code` (`action_state_code`),
        KEY `states_action_type_fk` (`action_type_code`),
        CONSTRAINT `states_action_type_fk` FOREIGN KEY (`action_type_code`) 
        REFERENCES `' . db_prefix() . 'topic_action_types` (`action_type_code`) 
        ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;');

    // Insert default states
    $CI->db->query("INSERT INTO `" . db_prefix() . "topic_action_states` 
        (`name`, `action_state_code`, `action_type_code`, `color`, `position`) VALUES
        ('Đang viết', 'WRITING_IN_PROGRESS', 'WRITING', '#FFA500', 1),
        ('Hoàn thành', 'WRITING_COMPLETED', 'WRITING', '#28a745', 2),
        ('Cần sửa', 'WRITING_NEEDS_REVISION', 'WRITING', '#DC3545', 3),
        ('Chờ duyệt', 'CONTENT_PENDING', 'CONTENT_AUDIT', '#17A2B8', 4),
        ('Đã duyệt', 'CONTENT_APPROVED', 'CONTENT_AUDIT', '#28a745', 5),
        ('Từ chối', 'CONTENT_REJECTED', 'CONTENT_AUDIT', '#DC3545', 6)");
}

// 3. Create topic_master table
if (!$CI->db->table_exists(db_prefix() . 'topic_master')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . 'topic_master` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `topicid` varchar(255) NOT NULL,
        `topictitle` varchar(255) DEFAULT NULL,
        `status` tinyint(1) DEFAULT 1,
        `datecreated` datetime DEFAULT current_timestamp(),
        `dateupdated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`),
        UNIQUE KEY `topicid` (`topicid`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;');
}

// 4. Create topic_target table
if (!$CI->db->table_exists(db_prefix() . 'topic_target')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . 'topic_target` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `target_id` int(11) NOT NULL,
        `name` varchar(100) NOT NULL,
        `target_type` varchar(50) NOT NULL,
        `status` tinyint(1) DEFAULT 1,
        `datecreated` datetime DEFAULT current_timestamp(),
        `dateupdated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `target_type` (`target_type`),
        KEY `target_id` (`target_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;');
}

// 5. Create topics table
if (!$CI->db->table_exists(db_prefix() . 'topics')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . 'topics` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `topicid` varchar(255) NOT NULL,
        `topictitle` varchar(255) NOT NULL,
        `log` text NOT NULL,
        `action_type_code` varchar(50) DEFAULT NULL,
        `action_state_code` varchar(50) DEFAULT NULL,
        `target_id` int(11) DEFAULT NULL,
        `status` tinyint(1) DEFAULT 1,
        `datecreated` datetime DEFAULT current_timestamp(),
        `dateupdated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `topicid` (`topicid`),
        KEY `idx_target_id` (`target_id`),
        KEY `idx_status` (`status`),
        KEY `action_type_code` (`action_type_code`),
        KEY `action_state_code` (`action_state_code`),
        CONSTRAINT `fk_topic_master` FOREIGN KEY (`topicid`) 
        REFERENCES `' . db_prefix() . 'topic_master` (`topicid`) ON DELETE CASCADE,
        CONSTRAINT `fk_topic_target` FOREIGN KEY (`target_id`) 
        REFERENCES `' . db_prefix() . 'topic_target` (`id`) ON DELETE SET NULL,
        CONSTRAINT `fk_topic_action_type` FOREIGN KEY (`action_type_code`) 
        REFERENCES `' . db_prefix() . 'topic_action_types` (`action_type_code`) ON DELETE SET NULL,
        CONSTRAINT `fk_topic_action_state` FOREIGN KEY (`action_state_code`) 
        REFERENCES `' . db_prefix() . 'topic_action_states` (`action_state_code`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;');
}

// 6. Add parent_id foreign key to topic_action_types
$CI->db->query('ALTER TABLE `' . db_prefix() . 'topic_action_types`
    ADD CONSTRAINT `fk_action_type_parent` 
    FOREIGN KEY (`parent_id`) 
    REFERENCES `' . db_prefix() . 'topic_action_types` (`id`) 
    ON DELETE SET NULL');

// 7. Add permissions
$capabilities = [];
$capabilities['capabilities'] = [
    'view'   => _l('permission_view') . '(' . _l('permission_global') . ')',
    'create' => _l('permission_create'),
    'edit'   => _l('permission_edit'),
    'delete' => _l('permission_delete'),
];

register_staff_capabilities('topics', $capabilities, _l('topics'));

// Create topic_online_status table
if (!$CI->db->table_exists(db_prefix() . 'topic_online_status')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . 'topic_online_status` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `staff_id` int(11) NOT NULL,
        `topic_id` varchar(255) NOT NULL,
        `last_activity` datetime NOT NULL DEFAULT current_timestamp(),
        `datecreated` datetime DEFAULT current_timestamp(),
        `dateupdated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `staff_id` (`staff_id`),
        KEY `topic_id` (`topic_id`),
        KEY `last_activity_idx` (`last_activity`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;');
}

// Add module settings
add_option('topics_online_tracking_enabled', 1);
add_option('topics_online_timeout', 900); // 5 minutes in seconds
add_option('topics_debug_panel_enabled', 0); // 0 = disabled by default

// 1. Create topic_automation_logs table
if (!$CI->db->table_exists(db_prefix() . 'topic_automation_logs')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . 'topic_automation_logs` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `topic_id` varchar(255) NOT NULL,
        `automation_id` varchar(250) NOT NULL,
        `workflow_id` varchar(250) NOT NULL,
        `status` varchar(50) DEFAULT "pending",
        `response_data` text NULL,
        `datecreated` datetime DEFAULT current_timestamp(),
        `dateupdated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `topic_id` (`topic_id`),
        KEY `automation_id` (`automation_id`),
        KEY `workflow_id` (`workflow_id`),
        KEY `status` (`status`),
        CONSTRAINT `fk_automation_topic` FOREIGN KEY (`topic_id`) 
        REFERENCES `' . db_prefix() . 'topic_master` (`topicid`) 
        ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;');
}

// 2. Add automation_id column to topics table
$CI->db->query("ALTER TABLE `" . db_prefix() . "topics` 
    ADD COLUMN IF NOT EXISTS `automation_id` varchar(250) NULL,
    ADD INDEX `idx_automation_id` (`automation_id`)");

// 3. Add module settings
add_option('topics_n8n_webhook_url', ''); // URL webhook của n8n
add_option('topics_n8n_api_key', ''); // API key của n8n nếu cần

// 4. Add permissions for automation
$capabilities = [];
$capabilities['capabilities'] = [
    'view'   => _l('permission_view') . '(' . _l('permission_global') . ')',
    'create' => _l('permission_create'),
    'edit'   => _l('permission_edit'),
    'delete' => _l('permission_delete'),
];

if (!$CI->db->table_exists(db_prefix() . 'topic_automation_settings')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . 'topic_automation_settings` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `workflow_id` varchar(250) NOT NULL,
        `name` varchar(255) NOT NULL,
        `description` text NULL,
        `settings` text NULL,
        `is_active` tinyint(1) DEFAULT 1,
        `datecreated` datetime DEFAULT current_timestamp(),
        `dateupdated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`),
        UNIQUE KEY `workflow_id` (`workflow_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;');

    // Insert default workflow settings
    $CI->db->query("INSERT INTO `" . db_prefix() . "topic_automation_settings` 
        (`workflow_id`, `name`, `description`, `settings`, `is_active`) VALUES
        ('default-content-workflow', 'Content Workflow', 'Default content creation and approval workflow', 
        '{\"webhook_url\":\"\",\"timeout\":300,\"retry_count\":3}', 1)");
}

// Register staff capabilities
register_staff_capabilities('topics_automation', $capabilities, _l('topics_automation'));

// Initialize default settings
add_option('topics_n8n_host', '');
add_option('topics_n8n_webhook_url', '');
add_option('topics_n8n_api_key', '');

// Create topic_action_buttons table
if (!$CI->db->table_exists(db_prefix() . 'topic_action_buttons')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . 'topic_action_buttons` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(255) NOT NULL,
        `button_type` varchar(50) NOT NULL DEFAULT "primary",
        `workflow_id` varchar(255) NOT NULL,
        `trigger_type` ENUM("webhook", "native") NOT NULL DEFAULT "webhook",
        `target_action_type` varchar(50) DEFAULT NULL,
        `target_action_state` varchar(50) DEFAULT NULL,
        `ignore_types` TEXT NULL,
        `ignore_states` TEXT NULL,
        `description` text DEFAULT NULL,
        `settings` text DEFAULT NULL,
        `status` tinyint(1) DEFAULT 1,
        `order` int(11) DEFAULT 0,
        `datecreated` datetime DEFAULT current_timestamp(),
        `dateupdated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `workflow_id` (`workflow_id`),
        KEY `target_action_type` (`target_action_type`),
        KEY `target_action_state` (`target_action_state`),
        KEY `trigger_type` (`trigger_type`),
        CONSTRAINT `fk_button_action_type` 
            FOREIGN KEY (`target_action_type`) 
            REFERENCES `' . db_prefix() . 'topic_action_types` (`action_type_code`) 
            ON DELETE SET NULL,
        CONSTRAINT `fk_button_action_state` 
            FOREIGN KEY (`target_action_state`) 
            REFERENCES `' . db_prefix() . 'topic_action_states` (`action_state_code`) 
            ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;');

    // Insert default action buttons with ignore rules
    $CI->db->query("INSERT INTO `" . db_prefix() . "topic_action_buttons` 
        (`name`, `button_type`, `workflow_id`, `trigger_type`, `target_action_type`, 
         `target_action_state`, `description`, `ignore_types`, `ignore_states`, `order`) VALUES
        ('Kích hoạt Topic', 'primary', 'activate-topic-workflow', 'webhook', 
         'WRITING', 'WRITING_IN_PROGRESS', 'Kích hoạt topic và bắt đầu quy trình viết bài',
         '[\"PUBLISHING\",\"SEO_CHECK\"]', '[\"CONTENT_APPROVED\",\"CONTENT_REJECTED\"]', 1),
        ('Chạy lại quy trình', 'warning', 'rerun-process-workflow', 'native', 
         NULL, NULL, 'Chạy lại quy trình xử lý cho topic',
         NULL, NULL, 2)");
}

// Add module settings for action buttons
add_option('topics_enable_action_buttons', 1);
add_option('topics_default_workflow_id', 'activate-topic-workflow');

// Add permissions for action buttons
$capabilities = [];
$capabilities['capabilities'] = [
    'view'   => _l('permission_view') . '(' . _l('permission_global') . ')',
    'create' => _l('permission_create'),
    'edit'   => _l('permission_edit'),
    'delete' => _l('permission_delete'),
];

register_staff_capabilities('topic_action_buttons', $capabilities, _l('topic_action_buttons'));

// Create topic_controllers table
if (!$CI->db->table_exists(db_prefix() . 'topic_controllers')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . 'topic_controllers` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
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
        `raw_data` varchar(255) DEFAULT NULL,
        `action_1` text DEFAULT NULL,
        `action_2` text DEFAULT NULL,
        `page_mapping` varchar(255) DEFAULT NULL,
        `datecreated` datetime DEFAULT current_timestamp(),
        `dateupdated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `idx_status` (`status`),
        KEY `idx_site` (`site`),
        KEY `idx_blog_id` (`blog_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;');
}

// Add controller_id column to topic_master table
$CI->db->query("ALTER TABLE `" . db_prefix() . "topic_master` 
    ADD COLUMN IF NOT EXISTS `controller_id` int(11) DEFAULT NULL,
    ADD CONSTRAINT `fk_topic_master_controller` 
    FOREIGN KEY (`controller_id`) 
    REFERENCES `" . db_prefix() . "topic_controllers`(`id`) 
    ON DELETE SET NULL");

// Add permissions for controllers
$capabilities = [];
$capabilities['capabilities'] = [
    'view'   => _l('permission_view') . '(' . _l('permission_global') . ')',
    'create' => _l('permission_create'),
    'edit'   => _l('permission_edit'),
    'delete' => _l('permission_delete'),
];

register_staff_capabilities('topic_controllers', $capabilities, _l('topic_controllers'));

// Add module settings for controllers
add_option('topics_enable_controllers', 1);

// Create topic_controller table for many-to-many relationship
if (!$CI->db->table_exists(db_prefix() . 'topic_controller')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . 'topic_controller` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `controller_id` int(11) NOT NULL,
        `topic_id` int(11) NOT NULL,
        `staff_id` int(11) NOT NULL,
        `datecreated` datetime DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `controller_id` (`controller_id`),
        KEY `topic_id` (`topic_id`),
        KEY `staff_id` (`staff_id`),
        CONSTRAINT `fk_topic_controller_controller` 
            FOREIGN KEY (`controller_id`) 
            REFERENCES `' . db_prefix() . 'topic_controllers` (`id`) 
            ON DELETE CASCADE,
        CONSTRAINT `fk_topic_controller_topic` 
            FOREIGN KEY (`topic_id`) 
            REFERENCES `' . db_prefix() . 'topic_master` (`id`) 
            ON DELETE CASCADE,
        CONSTRAINT `fk_topic_controller_staff` 
            FOREIGN KEY (`staff_id`) 
            REFERENCES `' . db_prefix() . 'staff` (`staffid`) 
            ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;');
}
