<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_101 extends App_module_migration
{
    public function up()
    {
        $CI = &get_instance();

        $settingsTable = db_prefix() . 'chatpion_bridge_settings';
        if (!$CI->db->table_exists($settingsTable)) {
            $sql = 'CREATE TABLE `' . $settingsTable . '` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `staff_id` INT(11) DEFAULT NULL,
                `chatpion_user_id` INT(11) DEFAULT NULL,
                `api_base_url` VARCHAR(255) NOT NULL,
                `api_key` TEXT DEFAULT NULL,
                `settings_payload` LONGTEXT DEFAULT NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_staff` (`staff_id`),
                KEY `idx_chatpion_user` (`chatpion_user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ';';

            $CI->db->query($sql);
        }

        $linksTable = db_prefix() . 'chatpion_bridge_task_links';
        if (!$CI->db->table_exists($linksTable)) {
            $sql = 'CREATE TABLE `' . $linksTable . '` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `task_id` INT(11) NOT NULL,
                `campaign_id` VARCHAR(100) NOT NULL,
                `account_id` VARCHAR(100) DEFAULT NULL,
                `workspace_json` LONGTEXT DEFAULT NULL,
                `last_status` VARCHAR(50) DEFAULT NULL,
                `post_url` TEXT DEFAULT NULL,
                `media_type` VARCHAR(50) DEFAULT NULL,
                `last_synced_at` DATETIME DEFAULT NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                `created_by` INT(11) DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `idx_task_unique` (`task_id`),
                KEY `idx_campaign` (`campaign_id`),
                KEY `idx_last_status` (`last_status`),
                KEY `idx_media_type` (`media_type`)
            ) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ';';

            $CI->db->query($sql);
        }

        $logsTable = db_prefix() . 'chatpion_bridge_logs';
        if (!$CI->db->table_exists($logsTable)) {
            $sql = 'CREATE TABLE `' . $logsTable . '` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `task_id` INT(11) DEFAULT NULL,
                `campaign_id` VARCHAR(100) DEFAULT NULL,
                `level` VARCHAR(20) NOT NULL DEFAULT "info",
                `message` TEXT DEFAULT NULL,
                `context` LONGTEXT DEFAULT NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_task` (`task_id`),
                KEY `idx_campaign` (`campaign_id`),
                KEY `idx_created_at` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ';';

            $CI->db->query($sql);
        }

        if (get_option('chatpion_bridge_db_version') < 101) {
            update_option('chatpion_bridge_db_version', 101);
        }
    }
}

