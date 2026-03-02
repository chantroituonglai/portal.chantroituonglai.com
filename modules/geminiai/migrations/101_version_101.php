<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_101 extends App_module_migration
{
    public function up()
    {
        $CI = &get_instance();

        if (get_option('geminiai_ticket_classify_enabled') === '') {
            add_option('geminiai_ticket_classify_enabled', 0);
        }

        $table = db_prefix() . 'geminiai_ticket_logs';
        if (!$CI->db->table_exists($table)) {
            $sql = 'CREATE TABLE ' . $table . ' (
                id INT(11) NOT NULL AUTO_INCREMENT,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                source VARCHAR(20) DEFAULT NULL,
                email_from VARCHAR(191) DEFAULT NULL,
                subject VARCHAR(255) DEFAULT NULL,
                preview TEXT DEFAULT NULL,
                classification VARCHAR(191) DEFAULT NULL,
                score DECIMAL(5,2) DEFAULT NULL,
                ticket_id INT(11) DEFAULT NULL,
                raw LONGTEXT DEFAULT NULL,
                error TEXT DEFAULT NULL,
                PRIMARY KEY (id),
                KEY idx_ticket_id (ticket_id),
                KEY idx_created_at (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set;

            $CI->db->query($sql);
        }

        update_option('geminiai_db_version', 101);
    }
}
