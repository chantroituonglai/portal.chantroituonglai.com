<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_128 extends App_module_migration
{
    public function up()
    {
        $CI = &get_instance();
        
        // Tạo bảng mới để lưu trữ mối quan hệ giữa controller và action buttons
        if (!$CI->db->table_exists(db_prefix() . 'topic_controller_action_buttons')) {
            $CI->db->query("CREATE TABLE `" . db_prefix() . "topic_controller_action_buttons` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `controller_id` int(11) NOT NULL,
                `action_button_id` int(11) NOT NULL,
                `status` tinyint(1) DEFAULT 1,
                `order` int(11) DEFAULT 0,
                `datecreated` datetime DEFAULT current_timestamp(),
                `dateupdated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                PRIMARY KEY (`id`),
                KEY `controller_id` (`controller_id`),
                KEY `action_button_id` (`action_button_id`),
                CONSTRAINT `fk_controller_action_button_controller` 
                    FOREIGN KEY (`controller_id`) 
                    REFERENCES `" . db_prefix() . "topic_controllers` (`id`) 
                    ON DELETE CASCADE,
                CONSTRAINT `fk_controller_action_button_button` 
                    FOREIGN KEY (`action_button_id`) 
                    REFERENCES `" . db_prefix() . "topic_action_buttons` (`id`) 
                    ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
            
            log_activity('Migration 128: Created topic_controller_action_buttons table');
        }
        
        // Kiểm tra xem cột action_command đã tồn tại trong topic_action_buttons chưa
        $field_exists = false;
        $fields = $CI->db->field_data(db_prefix() . 'topic_action_buttons');
        foreach ($fields as $field) {
            if ($field->name == 'action_command') {
                $field_exists = true;
                break;
            }
        }
        
        // Thêm cột action_command vào bảng topic_action_buttons nếu chưa tồn tại
        if (!$field_exists) {
            $CI->db->query("ALTER TABLE `" . db_prefix() . "topic_action_buttons` 
                ADD COLUMN `action_command` varchar(50) DEFAULT NULL COMMENT 'Command code for special actions'");
            
            log_activity('Migration 128: Added action_command column to topic_action_buttons table');
        }
        
        // Cập nhật phiên bản module
        update_option('topics_version', '1.2.8');
    }

    public function down()
    {
        $CI = &get_instance();
        
        // Kiểm tra và xóa cột action_command từ bảng topic_action_buttons
        $field_exists = false;
        $fields = $CI->db->field_data(db_prefix() . 'topic_action_buttons');
        foreach ($fields as $field) {
            if ($field->name == 'action_command') {
                $field_exists = true;
                break;
            }
        }
        
        if ($field_exists) {
            $CI->db->query("ALTER TABLE `" . db_prefix() . "topic_action_buttons` 
                DROP COLUMN `action_command`");
            
            log_activity('Migration 128 Rollback: Removed action_command column from topic_action_buttons table');
        }
        
        // Kiểm tra và xóa bảng topic_controller_action_buttons
        if ($CI->db->table_exists(db_prefix() . 'topic_controller_action_buttons')) {
            $CI->db->query("DROP TABLE `" . db_prefix() . "topic_controller_action_buttons`");
            
            log_activity('Migration 128 Rollback: Dropped topic_controller_action_buttons table');
        }

        // Cập nhật phiên bản module về phiên bản trước
        update_option('topics_version', '1.2.7');
    }
} 