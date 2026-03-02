<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Project Agent Module Uninstallation
 */

$CI = &get_instance();

// Drop database tables
$CI->load->dbforge();

// Drop tables in reverse order (due to foreign key constraints)
$tables = [
    'project_agent_action_logs',
    'project_agent_response_actions',
    'project_agent_memory_chains',
    'project_agent_memory_entries', 
    'project_agent_actions',
    'project_agent_sessions'
];

foreach ($tables as $table) {
    $CI->dbforge->drop_table(db_prefix() . $table, TRUE);
}

// Remove module options
delete_option('project_agent_ai_room_enabled');
delete_option('project_agent_auto_confirm_threshold');
delete_option('project_agent_memory_retention_days');
delete_option('project_agent_max_concurrent_sessions');
delete_option('project_agent_default_risk_level');
delete_option('project_agent_debug_enabled');

// Log uninstallation
log_message('info', 'Project Agent Module: Uninstallation completed successfully');
