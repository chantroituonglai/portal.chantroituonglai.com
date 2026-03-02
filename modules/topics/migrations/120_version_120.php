<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_120 extends App_module_migration
{
    public function up()
    {
        $CI = &get_instance();

        // Create topic_external_data table
        if (!$CI->db->table_exists(db_prefix() . 'topic_external_data')) {
            $CI->db->query('CREATE TABLE `' . db_prefix() . 'topic_external_data` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `topic_master_id` int(11) NOT NULL,
                `rel_type` varchar(100) NOT NULL,
                `rel_id` varchar(255) NOT NULL,
                `rel_data` text DEFAULT NULL,
                `rel_data_raw` longtext DEFAULT NULL,
                `datecreated` datetime DEFAULT current_timestamp(),
                `dateupdated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                PRIMARY KEY (`id`),
                UNIQUE KEY `unique_topic_rel` (`topic_master_id`, `rel_type`, `rel_id`),
                KEY `topic_master_id` (`topic_master_id`),
                KEY `rel_type` (`rel_type`),
                KEY `rel_id` (`rel_id`),
                CONSTRAINT `fk_external_data_topic_master` 
                    FOREIGN KEY (`topic_master_id`) 
                    REFERENCES `' . db_prefix() . 'topic_master` (`id`) 
                    ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;');
        }
    }

    public function down()
    {
        $CI = &get_instance();
        
        if ($CI->db->table_exists(db_prefix() . 'topic_external_data')) {
            $CI->db->query('DROP TABLE IF EXISTS `' . db_prefix() . 'topic_external_data`');
        }
    }
} 