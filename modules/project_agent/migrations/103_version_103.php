<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_103 extends App_module_migration
{
    public function up()
    {
        $CI = &get_instance();
        $CI->load->dbforge();

        $logsTable = db_prefix() . 'project_agent_action_logs';
        $responseTable = db_prefix() . 'project_agent_response_actions';

        $logFields = $CI->db->list_fields($logsTable);
        if (!in_array('response_id', $logFields)) {
            $CI->db->query('ALTER TABLE `' . $logsTable . '` ADD COLUMN `response_id` VARCHAR(100) NULL AFTER `client_token`');
        }
        if (!in_array('action_status', $logFields)) {
            $CI->db->query("ALTER TABLE `{$logsTable}` ADD COLUMN `action_status` ENUM('pending','executing','completed','failed') NOT NULL DEFAULT 'pending' AFTER `response_id`");
        }
        if (!in_array('execution_order', $logFields)) {
            $CI->db->query('ALTER TABLE `' . $logsTable . '` ADD COLUMN `execution_order` INT NOT NULL DEFAULT 0 AFTER `action_status`');
        }
        if (!in_array('result_summary', $logFields)) {
            $CI->db->query('ALTER TABLE `' . $logsTable . '` ADD COLUMN `result_summary` TEXT NULL AFTER `result_json`');
        }

        if (!$CI->db->table_exists($responseTable)) {
            $CI->dbforge->add_field([
                'id' => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
                'response_id' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false],
                'session_id' => ['type' => 'INT', 'constraint' => 11, 'null' => false],
                'action_id' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false],
                'action_name' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
                'parameters' => ['type' => 'TEXT', 'null' => false],
                'status' => ['type' => "ENUM('pending','executing','completed','failed')", 'null' => false, 'default' => 'pending'],
                'execution_order' => ['type' => 'INT', 'null' => false, 'default' => 0],
                'created_at' => ['type' => 'DATETIME', 'null' => false],
                'updated_at' => ['type' => 'DATETIME', 'null' => false],
            ]);
            $CI->dbforge->add_key('id', true);
            $CI->dbforge->add_key(['response_id', 'session_id']);
            $CI->dbforge->create_table($responseTable, true);
        }

        update_option('project_agent_db_version', 103);
    }
}
