<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_117 extends App_module_migration
{
    public function up()
    {
        $CI = &get_instance();

        // Create topic_controller table for many-to-many relationship
        if (!$CI->db->table_exists(db_prefix() . 'topic_controller')) {
            $CI->db->query('CREATE TABLE `' . db_prefix() . 'topic_controller` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `controller_id` int(11) NOT NULL,
                `topic_id` int(11) NOT NULL,
                `staff_id` int(11) NOT NULL,
                `datecreated` datetime DEFAULT current_timestamp(),
                PRIMARY KEY (`id`),
                KEY `controller_id` (`controller_id`),
                KEY `topic_id` (`topic_id`),
                KEY `staff_id` (`staff_id`),
                CONSTRAINT `fk_topic_controller_controller` 
                    FOREIGN KEY (`controller_id`) 
                    REFERENCES `' . db_prefix() . 'topic_controllers` (`id`) 
                    ON DELETE CASCADE,
                CONSTRAINT `fk_topic_controller_topic` 
                    FOREIGN KEY (`topic_id`) 
                    REFERENCES `' . db_prefix() . 'topic_master` (`id`) 
                    ON DELETE CASCADE,
                CONSTRAINT `fk_topic_controller_staff` 
                    FOREIGN KEY (`staff_id`) 
                    REFERENCES `' . db_prefix() . 'staff` (`staffid`) 
                    ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;');
        }
    }

    public function down()
    {
        $CI = &get_instance();
        
        // Drop table if exists
        if ($CI->db->table_exists(db_prefix() . 'topic_controller')) {
            $CI->db->query('DROP TABLE IF EXISTS `' . db_prefix() . 'topic_controller`');
        }
    }
} 