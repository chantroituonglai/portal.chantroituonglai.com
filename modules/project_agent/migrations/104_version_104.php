<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_104 extends App_module_migration
{
    public function up()
    {
        $CI = &get_instance();
        $CI->load->dbforge();

        $actionsTable = db_prefix() . 'project_agent_actions';
        if ($CI->db->table_exists($actionsTable)) {
            $fields = $CI->db->list_fields($actionsTable);
            if (!in_array('param_mapping', $fields)) {
                $CI->db->query('ALTER TABLE `' . $actionsTable . '` ADD COLUMN `param_mapping` TEXT NULL AFTER `prompt_override`');
            }
        }

        update_option('project_agent_db_version', 104);
    }
}
