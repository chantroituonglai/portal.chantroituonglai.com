<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_201 extends App_module_migration
{
    public function up()
    {
        $CI = &get_instance();

        add_option('external_products_lotte_api_token', '');

        if (!$CI->db->table_exists(db_prefix() . 'lotte_crawl_log')) {
            $CI->db->query('CREATE TABLE `' . db_prefix() . "lotte_crawl_log` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `triggered_at` datetime NOT NULL,
                `trigger_source` varchar(100) NOT NULL DEFAULT 'external',
                `from_date` date DEFAULT NULL,
                `to_date` date DEFAULT NULL,
                `orders_found` int(11) NOT NULL DEFAULT 0,
                `orders_saved` int(11) NOT NULL DEFAULT 0,
                `errors` text DEFAULT NULL,
                `status` varchar(50) NOT NULL DEFAULT 'pending',
                PRIMARY KEY (`id`),
                KEY `triggered_at` (`triggered_at`),
                KEY `status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');
        }

        log_activity('External Products module migrated to version 2.0.1');
    }
}
