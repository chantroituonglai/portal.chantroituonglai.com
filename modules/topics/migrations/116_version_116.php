<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_116 extends App_module_migration
{
    public function up()
    {
        $CI = &get_instance();

        // Tạo bảng controllers
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
                `raw_data` varchar(100) DEFAULT NULL,
                `action_1` text DEFAULT NULL,
                `action_2` text DEFAULT NULL,
                `page_mapping` varchar(100) DEFAULT NULL,
                `datecreated` datetime DEFAULT current_timestamp(),
                `dateupdated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;');
        }

        // Thêm cột controller_id vào bảng topic_master
        $CI->db->query("ALTER TABLE `" . db_prefix() . "topic_master` 
            ADD COLUMN IF NOT EXISTS `controller_id` int(11) DEFAULT NULL,
            ADD CONSTRAINT `fk_topic_master_controller` 
            FOREIGN KEY (`controller_id`) REFERENCES `" . db_prefix() . "topic_controllers`(`id`) 
            ON DELETE SET NULL");
    }

    public function down()
    {
        $CI = &get_instance();
        
        // Drop foreign key
        $CI->db->query("ALTER TABLE `" . db_prefix() . "topic_master` 
            DROP FOREIGN KEY IF EXISTS `fk_topic_master_controller`,
            DROP COLUMN IF EXISTS `controller_id`");

        // Drop table
        if ($CI->db->table_exists(db_prefix() . 'topic_controllers')) {
            $CI->db->query('DROP TABLE IF EXISTS `' . db_prefix() . 'topic_controllers`');
        }
    }
}