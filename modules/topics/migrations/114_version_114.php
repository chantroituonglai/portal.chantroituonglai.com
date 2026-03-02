<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_114 extends App_module_migration
{
    public function up()
    {
        $CI = &get_instance();
        
        // Tạo bảng topic_action_buttons để lưu cấu hình nút
        if (!$CI->db->table_exists(db_prefix() . 'topic_action_buttons')) {
            $CI->db->query('CREATE TABLE `' . db_prefix() . 'topic_action_buttons` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `name` varchar(255) NOT NULL,
                `button_type` varchar(50) NOT NULL DEFAULT "primary",
                `workflow_id` varchar(255) NOT NULL,
                `target_action_type` varchar(255) DEFAULT NULL,
                `target_action_state` varchar(255) DEFAULT NULL,
                `description` text,
                `status` tinyint(1) NOT NULL DEFAULT 1,
                `order` int(11) NOT NULL DEFAULT 0,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ';');
        }
    }
} 