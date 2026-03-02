<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_113 extends App_module_migration
{
    public function up()
    {
        $CI = &get_instance();

        // 1. Tạo bảng topic_automation_logs
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

        // 2. Thêm column automation_id vào bảng topics để reference
        $CI->db->query("ALTER TABLE `" . db_prefix() . "topics` 
            ADD COLUMN IF NOT EXISTS `automation_id` varchar(250) NULL,
            ADD INDEX `idx_automation_id` (`automation_id`)");
    }

    public function down()
    {
        $CI = &get_instance();

        // Drop column from topics
        $CI->db->query("ALTER TABLE `" . db_prefix() . "topics` 
            DROP COLUMN IF EXISTS `automation_id`,
            DROP INDEX IF EXISTS `idx_automation_id`");

        // Drop automation logs table
        if ($CI->db->table_exists(db_prefix() . 'topic_automation_logs')) {
            $CI->db->query('DROP TABLE IF EXISTS `' . db_prefix() . 'topic_automation_logs`');
        }
    }
} 