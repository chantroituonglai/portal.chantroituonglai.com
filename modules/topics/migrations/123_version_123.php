<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_123 extends App_module_migration
{
    public function up()
    {
        $CI = &get_instance();
        
        // Tạo bảng topic_controller_blogs nếu chưa tồn tại
        if (!$CI->db->table_exists(db_prefix() . 'topic_controller_blogs')) {
            $CI->db->query("CREATE TABLE `" . db_prefix() . "topic_controller_blogs` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `controller_id` int(11) NOT NULL,
                `blog_id` varchar(100) NOT NULL COMMENT 'ID của bài viết trên nền tảng gốc',
                `title` varchar(255) NOT NULL,
                `slug` varchar(255) DEFAULT NULL,
                `excerpt` text DEFAULT NULL COMMENT 'Tóm tắt bài viết',
                `status` varchar(20) DEFAULT 'publish' COMMENT 'Trạng thái bài viết (publish, draft, v.v.)',
                `author` varchar(255) DEFAULT NULL COMMENT 'Tác giả bài viết',
                `featured_image` text DEFAULT NULL COMMENT 'URL hình ảnh đại diện',
                `url` varchar(255) DEFAULT NULL COMMENT 'URL của bài viết trên website',
                `date_published` datetime DEFAULT NULL,
                `date_modified` datetime DEFAULT NULL,
                `comment_count` int(11) DEFAULT 0,
                `view_count` int(11) DEFAULT 0 COMMENT 'Số lượt xem (nếu có)',
                `raw_data` longtext DEFAULT NULL COMMENT 'Dữ liệu gốc dạng JSON',
                `last_sync` datetime DEFAULT NULL COMMENT 'Thời gian đồng bộ cuối cùng',
                `datecreated` datetime DEFAULT current_timestamp(),
                `dateupdated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                PRIMARY KEY (`id`),
                KEY `controller_id` (`controller_id`),
                KEY `blog_id` (`blog_id`),
                KEY `status` (`status`),
                CONSTRAINT `tbltopic_controller_blogs_ibfk_1` FOREIGN KEY (`controller_id`) REFERENCES `" . db_prefix() . "topic_controllers` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;");
            
            log_activity('Migration 123: Created topic_controller_blogs table');
        }
        
        // Tạo bảng topic_controller_blog_relationships nếu chưa tồn tại
        if (!$CI->db->table_exists(db_prefix() . 'topic_controller_blog_relationships')) {
            $CI->db->query("CREATE TABLE `" . db_prefix() . "topic_controller_blog_relationships` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `controller_id` int(11) NOT NULL,
                `blog_id` varchar(100) NOT NULL COMMENT 'ID của bài viết trên nền tảng gốc',
                `type` enum('category','tag') NOT NULL COMMENT 'Loại quan hệ',
                `term_id` varchar(100) NOT NULL COMMENT 'ID của danh mục hoặc thẻ',
                `datecreated` datetime DEFAULT current_timestamp(),
                PRIMARY KEY (`id`),
                UNIQUE KEY `unique_relationship` (`controller_id`,`blog_id`,`type`,`term_id`),
                KEY `controller_id` (`controller_id`),
                KEY `blog_id` (`blog_id`),
                KEY `term_id` (`term_id`),
                CONSTRAINT `tbltopic_controller_blog_relationships_ibfk_1` FOREIGN KEY (`controller_id`) REFERENCES `" . db_prefix() . "topic_controllers` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;");
            
            log_activity('Migration 123: Created topic_controller_blog_relationships table');
        }
        
        // Tạo bảng topic_controller_tags nếu chưa tồn tại (chuẩn bị cho tính năng tags)
        if (!$CI->db->table_exists(db_prefix() . 'topic_controller_tags')) {
            $CI->db->query("CREATE TABLE `" . db_prefix() . "topic_controller_tags` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `controller_id` int(11) NOT NULL,
                `tag_id` varchar(100) NOT NULL COMMENT 'ID của thẻ trên nền tảng gốc',
                `name` varchar(255) NOT NULL,
                `slug` varchar(255) DEFAULT NULL,
                `description` text DEFAULT NULL,
                `count` int(11) DEFAULT 0 COMMENT 'Số lượng bài viết có thẻ này',
                `url` varchar(255) DEFAULT NULL COMMENT 'URL của thẻ trên website',
                `raw_data` longtext DEFAULT NULL COMMENT 'Dữ liệu gốc dạng JSON',
                `last_sync` datetime DEFAULT NULL COMMENT 'Thời gian đồng bộ cuối cùng',
                `datecreated` datetime DEFAULT current_timestamp(),
                `dateupdated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                PRIMARY KEY (`id`),
                KEY `controller_id` (`controller_id`),
                KEY `tag_id` (`tag_id`),
                CONSTRAINT `tbltopic_controller_tags_ibfk_1` FOREIGN KEY (`controller_id`) REFERENCES `" . db_prefix() . "topic_controllers` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;");
            
            log_activity('Migration 123: Created topic_controller_tags table');
        }
        
        update_option('topics_version', '1.2.3');
    }

    public function down()
    {
        $CI = &get_instance();
        
        // Xóa bảng topic_controller_blog_relationships nếu tồn tại
        if ($CI->db->table_exists(db_prefix() . 'topic_controller_blog_relationships')) {
            $CI->db->query("DROP TABLE IF EXISTS `" . db_prefix() . "topic_controller_blog_relationships`");
            log_activity('Migration 123 Rollback: Dropped topic_controller_blog_relationships table');
        }
        
        // Xóa bảng topic_controller_blogs nếu tồn tại
        if ($CI->db->table_exists(db_prefix() . 'topic_controller_blogs')) {
            $CI->db->query("DROP TABLE IF EXISTS `" . db_prefix() . "topic_controller_blogs`");
            log_activity('Migration 123 Rollback: Dropped topic_controller_blogs table');
        }
        
        // Xóa bảng topic_controller_tags nếu tồn tại
        if ($CI->db->table_exists(db_prefix() . 'topic_controller_tags')) {
            $CI->db->query("DROP TABLE IF EXISTS `" . db_prefix() . "topic_controller_tags`");
            log_activity('Migration 123 Rollback: Dropped topic_controller_tags table');
        }
        
        update_option('topics_version', '1.2.2');
    }
}