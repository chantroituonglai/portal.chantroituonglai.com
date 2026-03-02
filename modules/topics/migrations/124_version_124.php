<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_124 extends App_module_migration
{
    public function up()
    {
        $CI = &get_instance();
        
        // Kiểm tra xem cột tags_last_sync đã tồn tại chưa
        $field_exists = false;
        $fields = $CI->db->field_data(db_prefix() . 'topic_controllers');
        foreach ($fields as $field) {
            if ($field->name == 'tags_last_sync') {
                $field_exists = true;
                break;
            }
        }
        
        // Thêm cột tags_last_sync và tags_state nếu chưa tồn tại
        if (!$field_exists) {
            $CI->db->query("ALTER TABLE `" . db_prefix() . "topic_controllers` 
                ADD COLUMN `tags_last_sync` DATETIME NULL DEFAULT NULL,
                ADD COLUMN `tags_state` TEXT NULL DEFAULT NULL");
            
            log_activity('Migration 124: Added tags_last_sync and tags_state columns to topic_controllers table');
        }

        // Kiểm tra xem cột categories_last_sync đã tồn tại chưa
        $field_exists = false;
        $fields = $CI->db->field_data(db_prefix() . 'topic_controllers');
        foreach ($fields as $field) {
            if ($field->name == 'categories_last_sync') {
                $field_exists = true;
                break;
            }
        }
        
        // Thêm cột categories_last_sync và categories_state nếu chưa tồn tại
        if (!$field_exists) {
            $CI->db->query("ALTER TABLE `" . db_prefix() . "topic_controllers` 
                ADD COLUMN `categories_last_sync` DATETIME NULL DEFAULT NULL,
                ADD COLUMN `categories_state` TEXT NULL DEFAULT NULL");
            
            log_activity('Migration 124: Added categories_last_sync and categories_state columns to topic_controllers table');
        }

        // Kiểm tra và tạo bảng topic_controller_tags nếu chưa tồn tại
        if (!$CI->db->table_exists(db_prefix() . 'topic_controller_tags')) {
            $CI->db->query("CREATE TABLE `" . db_prefix() . "topic_controller_tags` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `controller_id` INT(11) NOT NULL,
                `tag_id` VARCHAR(255) NOT NULL,
                `name` VARCHAR(255) NOT NULL,
                `slug` VARCHAR(255) NULL DEFAULT NULL,
                `parent_id` VARCHAR(255) NULL DEFAULT NULL,
                `count` INT(11) NOT NULL DEFAULT '0',
                `url` VARCHAR(255) NULL DEFAULT NULL,
                `datecreated` DATETIME NOT NULL,
                `dateupdated` DATETIME NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                INDEX `controller_id` (`controller_id`),
                INDEX `tag_id` (`tag_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ";");
            
            log_activity('Migration 124: Created topic_controller_tags table');
        }

        update_option('topics_db_version', '1.2.4');
    }

    public function down()
    {
        $CI = &get_instance();
        
        // Kiểm tra xem cột tags_last_sync có tồn tại không
        $field_exists = false;
        $fields = $CI->db->field_data(db_prefix() . 'topic_controllers');
        foreach ($fields as $field) {
            if ($field->name == 'tags_last_sync') {
                $field_exists = true;
                break;
            }
        }
        
        // Xóa cột tags_last_sync và tags_state nếu tồn tại
        if ($field_exists) {
            $CI->db->query("ALTER TABLE `" . db_prefix() . "topic_controllers` 
                DROP COLUMN `tags_last_sync`,
                DROP COLUMN `tags_state`");
            
            log_activity('Migration 124 Rollback: Removed tags_last_sync and tags_state columns from topic_controllers table');
        }

        // Kiểm tra xem cột categories_last_sync có tồn tại không
        $field_exists = false;
        $fields = $CI->db->field_data(db_prefix() . 'topic_controllers');
        foreach ($fields as $field) {
            if ($field->name == 'categories_last_sync') {
                $field_exists = true;
                break;
            }
        }
        
        // Xóa cột categories_last_sync và categories_state nếu tồn tại
        if ($field_exists) {
            $CI->db->query("ALTER TABLE `" . db_prefix() . "topic_controllers` 
                DROP COLUMN `categories_last_sync`,
                DROP COLUMN `categories_state`");
            
            log_activity('Migration 124 Rollback: Removed categories_last_sync and categories_state columns from topic_controllers table');
        }

        // Xóa bảng topic_controller_tags nếu tồn tại
        if ($CI->db->table_exists(db_prefix() . 'topic_controller_tags')) {
            $CI->db->query("DROP TABLE `" . db_prefix() . "topic_controller_tags`");
            
            log_activity('Migration 124 Rollback: Dropped topic_controller_tags table');
        }
    }
} 