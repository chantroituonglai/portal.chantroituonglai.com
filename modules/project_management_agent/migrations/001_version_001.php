<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_001 extends App_module_migration
{
    public function up()
    {
        $CI = &get_instance();
        $CI->load->dbforge();

        $compositionsTable = db_prefix() . 'project_management_agent_compositions';
        if (!$CI->db->table_exists($compositionsTable)) {
            $CI->dbforge->add_field([
                'composition_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
                'source_project_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => false],
                'user_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => false],
                'composition_data' => ['type' => 'LONGTEXT', 'null' => false],
                'ai_breakdown' => ['type' => 'LONGTEXT', 'null' => true],
                'input_data' => ['type' => 'LONGTEXT', 'null' => true],
                'status' => ['type' => "ENUM('collecting','analyzing','ready','cloning','completed','failed')", 'default' => 'collecting', 'null' => false],
                'status_message' => ['type' => 'TEXT', 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => false],
                'updated_at' => ['type' => 'DATETIME', 'null' => false],
            ]);
            $CI->dbforge->add_key('composition_id', true);
            $CI->dbforge->add_key('source_project_id');
            $CI->dbforge->add_key('user_id');
            $CI->dbforge->add_key('status');
            $CI->dbforge->create_table($compositionsTable, true);
        }

        $clonesTable = db_prefix() . 'project_management_agent_clones';
        if (!$CI->db->table_exists($clonesTable)) {
            $CI->dbforge->add_field([
                'clone_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
                'composition_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => false],
                'source_project_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => false],
                'new_project_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'null' => true],
                'clone_config' => ['type' => 'LONGTEXT', 'null' => false],
                'timeline_adjustments' => ['type' => 'LONGTEXT', 'null' => true],
                'status' => ['type' => "ENUM('pending','processing','completed','failed')", 'default' => 'pending', 'null' => false],
                'progress' => ['type' => 'INT', 'constraint' => 3, 'default' => 0, 'null' => false],
                'error_message' => ['type' => 'TEXT', 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => false],
                'completed_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $CI->dbforge->add_key('clone_id', true);
            $CI->dbforge->add_key('composition_id');
            $CI->dbforge->add_key('source_project_id');
            $CI->dbforge->add_key('new_project_id');
            $CI->dbforge->add_key('status');
            $CI->dbforge->create_table($clonesTable, true);
        }

        update_option('project_management_agent_db_version', 1);
    }
}
