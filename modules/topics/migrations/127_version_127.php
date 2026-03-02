<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_127 extends App_module_migration
{
    public function up()
    {
        $CI = &get_instance();
        
        // Kiểm tra xem bảng topic_editor_drafts đã tồn tại chưa
        if (!$CI->db->table_exists(db_prefix() . 'topic_editor_drafts')) {
            $CI->db->query("CREATE TABLE `" . db_prefix() . "topic_editor_drafts` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `topic_id` int(11) NOT NULL,
                `draft_title` varchar(191) NOT NULL,
                `draft_content` longtext NOT NULL,
                `draft_sections` longtext DEFAULT NULL COMMENT 'JSON structure of content sections',
                `draft_metadata` longtext DEFAULT NULL COMMENT 'JSON structure for SEO metadata',
                `status` varchar(50) NOT NULL DEFAULT 'draft',
                `version` int(11) NOT NULL DEFAULT 1,
                `created_by` int(11) DEFAULT NULL,
                `last_edited_by` int(11) DEFAULT NULL,
                `created_at` datetime DEFAULT current_timestamp(),
                `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                PRIMARY KEY (`id`),
                KEY `topic_id` (`topic_id`),
                KEY `status` (`status`),
                CONSTRAINT `fk_topic_editor_drafts_topic` FOREIGN KEY (`topic_id`) REFERENCES `" . db_prefix() . "topics` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
            
            log_activity('Migration 127: Created topic_editor_drafts table');
        }

        // Kiểm tra xem trường editor_settings đã tồn tại trong topics chưa
        $field_exists = false;
        $fields = $CI->db->field_data(db_prefix() . 'topics');
        foreach ($fields as $field) {
            if ($field->name == 'editor_settings') {
                $field_exists = true;
                break;
            }
        }
        
        // Thêm trường editor_settings vào bảng topics
        if (!$field_exists) {
            $CI->db->query("ALTER TABLE `" . db_prefix() . "topics` 
                ADD COLUMN `editor_settings` longtext DEFAULT NULL COMMENT 'JSON structure for topic editor configuration'");
            
            log_activity('Migration 127: Added editor_settings column to topics table');
        }

        // Kiểm tra xem trường editor_active_draft_id đã tồn tại trong topics chưa
        $field_exists = false;
        $fields = $CI->db->field_data(db_prefix() . 'topics');
        foreach ($fields as $field) {
            if ($field->name == 'active_draft_id') {
                $field_exists = true;
                break;
            }
        }
        
        // Thêm trường active_draft_id vào bảng topics
        if (!$field_exists) {
            $CI->db->query("ALTER TABLE `" . db_prefix() . "topics` 
                ADD COLUMN `active_draft_id` int(11) DEFAULT NULL COMMENT 'ID of the currently active draft'");
            
            log_activity('Migration 127: Added active_draft_id column to topics table');
        }
        
        // Cập nhật phiên bản module
        update_option('topics_version', '1.2.7');
    }

    public function down()
    {
        $CI = &get_instance();
        
        // Kiểm tra và xóa trường active_draft_id từ bảng topics
        $field_exists = false;
        $fields = $CI->db->field_data(db_prefix() . 'topics');
        foreach ($fields as $field) {
            if ($field->name == 'active_draft_id') {
                $field_exists = true;
                break;
            }
        }
        
        if ($field_exists) {
            $CI->db->query("ALTER TABLE `" . db_prefix() . "topics` 
                DROP COLUMN `active_draft_id`");
            
            log_activity('Migration 127 Rollback: Removed active_draft_id column from topics table');
        }

        // Kiểm tra và xóa trường editor_settings từ bảng topics
        $field_exists = false;
        $fields = $CI->db->field_data(db_prefix() . 'topics');
        foreach ($fields as $field) {
            if ($field->name == 'editor_settings') {
                $field_exists = true;
                break;
            }
        }
        
        if ($field_exists) {
            $CI->db->query("ALTER TABLE `" . db_prefix() . "topics` 
                DROP COLUMN `editor_settings`");
            
            log_activity('Migration 127 Rollback: Removed editor_settings column from topics table');
        }

        // Kiểm tra và xóa bảng topic_editor_drafts
        if ($CI->db->table_exists(db_prefix() . 'topic_editor_drafts')) {
            $CI->db->query("DROP TABLE `" . db_prefix() . "topic_editor_drafts`");
            
            log_activity('Migration 127 Rollback: Dropped topic_editor_drafts table');
        }

        // Cập nhật phiên bản module về phiên bản trước
        update_option('topics_version', '1.2.6');
    }
} 