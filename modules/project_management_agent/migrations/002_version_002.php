<?php
defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();
$CI->load->dbforge();

$tblCompositions = db_prefix() . 'project_management_agent_compositions';
if ($CI->db->table_exists($tblCompositions)) {
    if (!$CI->db->field_exists('input_data', $tblCompositions)) {
        $CI->dbforge->add_column($tblCompositions, [
            'input_data' => [
                'type' => 'LONGTEXT',
                'null' => TRUE,
                'after' => 'ai_breakdown',
            ],
        ]);
    }
}

// bump explicit version for visibility in some setups
update_option('project_management_agent_db_version', 2);

