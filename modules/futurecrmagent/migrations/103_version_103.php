<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_103 extends App_module_migration
{
    public function up()
    {
        $CI = &get_instance();
        $this->createToolRuns($CI);
        update_option('futurecrmagent_db_version', 103);
    }

    private function createToolRuns($CI): void
    {
        $table = db_prefix() . 'futurecrmagent_tool_runs';
        if ($CI->db->table_exists($table)) {
            return;
        }

        $CI->db->query('CREATE TABLE `' . $table . '` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `staffid` INT(11) DEFAULT NULL,
            `session_id` VARCHAR(64) DEFAULT NULL,
            `context_type` VARCHAR(50) DEFAULT NULL,
            `context_id` INT(11) DEFAULT NULL,
            `tool_name` VARCHAR(120) NOT NULL,
            `args_json` LONGTEXT DEFAULT NULL,
            `result_summary_json` LONGTEXT DEFAULT NULL,
            `status` VARCHAR(40) NOT NULL DEFAULT "ok",
            `duration_ms` INT(11) DEFAULT 0,
            `error` TEXT DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `idx_context` (`context_type`, `context_id`),
            KEY `idx_staff` (`staffid`),
            KEY `idx_tool` (`tool_name`),
            KEY `idx_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set);
    }
}
