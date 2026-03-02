<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_125 extends App_module_migration
{
    public function up()
    {
        $CI = &get_instance();
        
        // Kiểm tra và tạo bảng topic_sync_logs nếu chưa tồn tại
        if (!$CI->db->table_exists(db_prefix() . 'topic_sync_logs')) {
            $CI->db->query("CREATE TABLE `" . db_prefix() . "topic_sync_logs` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `controller_id` int(11) NOT NULL,
                `session_id` varchar(100) NOT NULL,
                `rel_type` varchar(50) NOT NULL,
                `status` varchar(20) NOT NULL DEFAULT 'in_progress',
                `summary_data` longtext DEFAULT NULL,
                `log_data` longtext DEFAULT NULL,
                `start_time` datetime DEFAULT NULL,
                `end_time` datetime DEFAULT NULL,
                `datecreated` datetime NOT NULL,
                `dateupdated` datetime NOT NULL,
                PRIMARY KEY (`id`),
                KEY `controller_id` (`controller_id`),
                KEY `session_id` (`session_id`),
                KEY `rel_type` (`rel_type`),
                KEY `status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ";");
            
            log_activity('Migration 125: Created topic_sync_logs table');
        }

        // Kiểm tra xem cột tags_sync_session_id đã tồn tại chưa
        $field_exists = false;
        $fields = $CI->db->field_data(db_prefix() . 'topic_controllers');
        foreach ($fields as $field) {
            if ($field->name == 'tags_sync_session_id') {
                $field_exists = true;
                break;
            }
        }
        
        // Thêm cột tags_sync_session_id nếu chưa tồn tại
        if (!$field_exists) {
            $CI->db->query("ALTER TABLE `" . db_prefix() . "topic_controllers` 
                ADD COLUMN `tags_sync_session_id` varchar(100) DEFAULT NULL AFTER `tags_last_sync`;");
            
            log_activity('Migration 125: Added tags_sync_session_id column to topic_controllers table');
        }

        update_option('topics_db_version', '1.2.5');
    }

    public function down()
    {
        $CI = &get_instance();
        
        // Xóa bảng topic_sync_logs nếu tồn tại
        if ($CI->db->table_exists(db_prefix() . 'topic_sync_logs')) {
            $CI->db->query("DROP TABLE `" . db_prefix() . "topic_sync_logs`");
            
            log_activity('Migration 125 Rollback: Dropped topic_sync_logs table');
        }

        // Kiểm tra xem cột tags_sync_session_id có tồn tại không
        $field_exists = false;
        $fields = $CI->db->field_data(db_prefix() . 'topic_controllers');
        foreach ($fields as $field) {
            if ($field->name == 'tags_sync_session_id') {
                $field_exists = true;
                break;
            }
        }
        
        // Xóa cột tags_sync_session_id nếu tồn tại
        if ($field_exists) {
            $CI->db->query("ALTER TABLE `" . db_prefix() . "topic_controllers` 
                DROP COLUMN `tags_sync_session_id`");
            
            log_activity('Migration 125 Rollback: Removed tags_sync_session_id column from topic_controllers table');
        }

        // Cập nhật phiên bản module về phiên bản trước
        update_option('topics_db_version', '1.2.4');
    }
} 