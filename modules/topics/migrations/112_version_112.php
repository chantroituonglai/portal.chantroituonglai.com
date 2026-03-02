<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_112 extends App_module_migration
{
    public function up()
    {
        $CI = &get_instance();

        // Create staff_online_status table
        if (!$CI->db->table_exists(db_prefix() . 'staff_online_status')) {
            $CI->db->query('CREATE TABLE `' . db_prefix() . 'staff_online_status` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `staff_id` int(11) NOT NULL,
                `topic_id` varchar(255) NOT NULL,
                `last_activity` datetime NOT NULL DEFAULT current_timestamp(),
                `datecreated` datetime DEFAULT current_timestamp(),
                `dateupdated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                PRIMARY KEY (`id`),
                KEY `staff_id` (`staff_id`),
                KEY `topic_id` (`topic_id`),
                KEY `last_activity_idx` (`last_activity`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;');
        }

        // Add module settings
        add_option('topics_online_tracking_enabled', 1);
        add_option('topics_online_timeout', 900); // 5 minutes in seconds
        add_option('topics_debug_panel_enabled', 0); // 0 = disabled by default
    }

    public function down()
    {
        $CI = &get_instance();

        // Remove settings
        delete_option('topics_online_tracking_enabled');
        delete_option('topics_online_timeout');

        // Drop table if exists
        if ($CI->db->table_exists(db_prefix() . 'staff_online_status')) {
            // $CI->db->query('DROP TABLE IF EXISTS `' . db_prefix() . 'staff_online_status`');
        }
    }
} 