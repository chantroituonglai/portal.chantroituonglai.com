<?php

defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();

add_option('chatpion_bridge_base_url', '');
add_option('chatpion_bridge_api_key', '');
add_option('chatpion_bridge_api_timeout', 30);
add_option('chatpion_bridge_enable_logging', 1);
add_option('chatpion_bridge_default_workspace_template', '');
add_option('chatpion_bridge_db_version', 0);

// Run initial migration immediately on activation
if (function_exists('chatpion_bridge_run_migrations')) {
    chatpion_bridge_run_migrations();
}

