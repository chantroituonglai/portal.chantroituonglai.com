<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_126 extends App_module_migration
{
    public function up()
    {
        $CI = &get_instance();
        
        // Kiểm tra xem cột sync_session_id đã tồn tại trong bảng tbltopic_controller_tags chưa
        $field_exists = false;
        $fields = $CI->db->field_data(db_prefix() . 'topic_controller_tags');
        foreach ($fields as $field) {
            if ($field->name == 'sync_session_id') {
                $field_exists = true;
                break;
            }
        }
        
        // Thêm cột sync_session_id nếu chưa tồn tại
        if (!$field_exists) {
            $CI->db->query("ALTER TABLE `" . db_prefix() . "topic_controller_tags` 
                ADD COLUMN `sync_session_id` varchar(100) DEFAULT NULL COMMENT 'ID phiên đồng bộ khi tag được thêm hoặc cập nhật' AFTER `raw_data`;");
            
            log_activity('Migration 126: Added sync_session_id column to topic_controller_tags table');
        }
        
        // Kiểm tra xem cột sync_count đã tồn tại trong bảng tbltopic_sync_logs chưa
        $field_exists = false;
        $fields = $CI->db->field_data(db_prefix() . 'topic_sync_logs');
        foreach ($fields as $field) {
            if ($field->name == 'processed_count') {
                $field_exists = true;
                break;
            }
        }
        
        // Thêm cột processed_count để theo dõi số lượng chính xác tag đã xử lý
        if (!$field_exists) {
            $CI->db->query("ALTER TABLE `" . db_prefix() . "topic_sync_logs` 
                ADD COLUMN `processed_count` int(11) DEFAULT 0 COMMENT 'Số lượng tags thực tế đã xử lý' AFTER `summary_data`;");
            
            log_activity('Migration 126: Added processed_count column to topic_sync_logs table');
        }
        
        // Cập nhật phiên bản module
        update_option('topics_db_version', '1.2.6');
    }

    public function down()
    {
        $CI = &get_instance();
        
        // Kiểm tra xem cột sync_session_id có tồn tại không
        $field_exists = false;
        $fields = $CI->db->field_data(db_prefix() . 'topic_controller_tags');
        foreach ($fields as $field) {
            if ($field->name == 'sync_session_id') {
                $field_exists = true;
                break;
            }
        }
        
        // Xóa cột sync_session_id nếu tồn tại
        if ($field_exists) {
            $CI->db->query("ALTER TABLE `" . db_prefix() . "topic_controller_tags` 
                DROP COLUMN `sync_session_id`");
            
            log_activity('Migration 126 Rollback: Removed sync_session_id column from topic_controller_tags table');
        }
        
        // Kiểm tra xem cột processed_count có tồn tại không
        $field_exists = false;
        $fields = $CI->db->field_data(db_prefix() . 'topic_sync_logs');
        foreach ($fields as $field) {
            if ($field->name == 'processed_count') {
                $field_exists = true;
                break;
            }
        }
        
        // Xóa cột processed_count nếu tồn tại
        if ($field_exists) {
            $CI->db->query("ALTER TABLE `" . db_prefix() . "topic_sync_logs` 
                DROP COLUMN `processed_count`");
            
            log_activity('Migration 126 Rollback: Removed processed_count column from topic_sync_logs table');
        }

        // Cập nhật phiên bản module về phiên bản trước
        update_option('topics_db_version', '1.2.5');
    }
} 