<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_101 extends App_module_migration
{
    public function up()
    {
        $CI = &get_instance();
        $CI->load->dbforge();

        $entriesTable = db_prefix() . 'project_agent_memory_entries';
        $chainsTable  = db_prefix() . 'project_agent_memory_chains';

        $entryFields = $CI->db->list_fields($entriesTable);
        if (!in_array('is_chain_selected', $entryFields)) {
            $CI->db->query('ALTER TABLE `' . $entriesTable . '` ADD COLUMN `is_chain_selected` TINYINT(1) NOT NULL DEFAULT 0 AFTER `entity_refs`');
        }
        if (!in_array('chain_priority', $entryFields)) {
            $CI->db->query('ALTER TABLE `' . $entriesTable . '` ADD COLUMN `chain_priority` INT NOT NULL DEFAULT 0 AFTER `is_chain_selected`');
        }
        if (!in_array('related_question_id', $entryFields)) {
            $CI->db->query('ALTER TABLE `' . $entriesTable . '` ADD COLUMN `related_question_id` VARCHAR(100) NULL AFTER `chain_priority`');
        }

        if (!$CI->db->table_exists($chainsTable)) {
            $CI->dbforge->add_field([
                'id' => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
                'session_id' => ['type' => 'INT', 'constraint' => 11, 'null' => false],
                'question_id' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false],
                'memory_ids' => ['type' => 'TEXT', 'null' => false],
                'created_at' => ['type' => 'DATETIME', 'null' => false],
            ]);
            $CI->dbforge->add_key('id', true);
            $CI->dbforge->add_key('session_id');
            $CI->dbforge->create_table($chainsTable, true);
            try {
                $CI->db->query('ALTER TABLE `' . $chainsTable . '` ALTER `created_at` SET DEFAULT CURRENT_TIMESTAMP');
            } catch (\Throwable $e) {
            }
        }

        update_option('project_agent_db_version', 101);
    }
}
