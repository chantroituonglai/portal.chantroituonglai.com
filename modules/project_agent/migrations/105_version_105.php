<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_105 extends App_module_migration
{
    public function up()
    {
        $CI = &get_instance();
        $CI->load->dbforge();

        $actionsTable = db_prefix() . 'project_agent_actions';
        if ($CI->db->table_exists($actionsTable)) {
            $fields = $CI->db->list_fields($actionsTable);
            if (!in_array('related_tables', $fields)) {
                $CI->db->query('ALTER TABLE `' . $actionsTable . '` ADD COLUMN `related_tables` TEXT NULL AFTER `param_mapping`');
            }
            if (!in_array('entity_type', $fields)) {
                $CI->db->query('ALTER TABLE `' . $actionsTable . '` ADD COLUMN `entity_type` VARCHAR(64) NULL AFTER `related_tables`');
            }
            if (!in_array('context_queries', $fields)) {
                $CI->db->query('ALTER TABLE `' . $actionsTable . '` ADD COLUMN `context_queries` TEXT NULL AFTER `entity_type`');
            }
        }

        update_option('project_agent_db_version', 105);
    }
}
