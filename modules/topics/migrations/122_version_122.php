<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_122 extends App_module_migration
{
    public function up()
    {
        $CI = &get_instance();
        
        // Thêm cột expanded_categories vào bảng topic_controllers
        if (!$CI->db->field_exists('expanded_categories', db_prefix() . 'topic_controllers')) {
            $CI->db->query("ALTER TABLE `" . db_prefix() . "topic_controllers` 
                ADD COLUMN `expanded_categories` TEXT NULL 
                COMMENT 'Trạng thái mở rộng của các danh mục (JSON)' AFTER `seo_task_sheet_id`");
            
            log_activity('Migration 122: Added expanded_categories field to topic_controllers table');
        }
        
        // Tạo bảng topic_controller_categories nếu chưa tồn tại
        if (!$CI->db->table_exists(db_prefix() . 'topic_controller_categories')) {
            $CI->db->query("CREATE TABLE `" . db_prefix() . "topic_controller_categories` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `controller_id` int(11) NOT NULL,
                `category_id` varchar(100) NOT NULL COMMENT 'ID của danh mục trên nền tảng gốc',
                `parent_id` varchar(100) DEFAULT NULL COMMENT 'ID của danh mục cha trên nền tảng gốc',
                `name` varchar(255) NOT NULL,
                `slug` varchar(255) DEFAULT NULL,
                `description` text DEFAULT NULL,
                `count` int(11) DEFAULT 0 COMMENT 'Số lượng bài viết trong danh mục',
                `url` varchar(255) DEFAULT NULL COMMENT 'URL của danh mục trên website',
                `image_url` text DEFAULT NULL COMMENT 'URL hình ảnh đại diện (nếu có)',
                `raw_data` longtext DEFAULT NULL COMMENT 'Dữ liệu gốc dạng JSON',
                `last_sync` datetime DEFAULT NULL COMMENT 'Thời gian đồng bộ cuối cùng',
                `datecreated` datetime DEFAULT current_timestamp(),
                `dateupdated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                PRIMARY KEY (`id`),
                KEY `controller_id` (`controller_id`),
                KEY `category_id` (`category_id`),
                KEY `parent_id` (`parent_id`),
                CONSTRAINT `tbltopic_controller_categories_ibfk_1` FOREIGN KEY (`controller_id`) REFERENCES `" . db_prefix() . "topic_controllers` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;");
            
            log_activity('Migration 122: Created topic_controller_categories table');
        }
        
        update_option('topics_version', '1.2.2');
    }

    public function down()
    {
        $CI = &get_instance();
        
        // Xóa cột expanded_categories khỏi bảng topic_controllers nếu tồn tại
        if ($CI->db->field_exists('expanded_categories', db_prefix() . 'topic_controllers')) {
            $CI->db->query("ALTER TABLE `" . db_prefix() . "topic_controllers` 
                DROP COLUMN `expanded_categories`");
            
            log_activity('Migration 122 Rollback: Removed expanded_categories field from topic_controllers table');
        }
        
        // Xóa bảng topic_controller_categories nếu tồn tại
        if ($CI->db->table_exists(db_prefix() . 'topic_controller_categories')) {
            $CI->db->query("DROP TABLE IF EXISTS `" . db_prefix() . "topic_controller_categories`");
            
            log_activity('Migration 122 Rollback: Dropped topic_controller_categories table');
        }
        
        update_option('topics_version', '1.2.1');
    }
} 