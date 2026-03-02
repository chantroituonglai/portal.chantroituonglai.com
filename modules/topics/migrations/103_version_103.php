<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_103 extends App_module_migration {
    public function up() {
        $CI = &get_instance();

        // Add default settings
        add_option('topics_api_enabled', 1);
        add_option('topics_notification_enabled', 1);

        // Add action_type_code column to topic_action_types table
        if (!$CI->db->field_exists('action_type_code', db_prefix() . 'topic_action_types')) {
            $CI->db->query("ALTER TABLE `" . db_prefix() . "topic_action_types` ADD `action_type_code` VARCHAR(50) NOT NULL AFTER `name`;");
        }
    }

    public function down() {
        // Remove settings
        delete_option('topics_api_enabled');
        delete_option('topics_notification_enabled');
    }
}
