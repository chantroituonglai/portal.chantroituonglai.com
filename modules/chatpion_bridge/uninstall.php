<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Keep tables for potential re-activation; only remove module-specific options.
 */
delete_option('chatpion_bridge_base_url');
delete_option('chatpion_bridge_api_key');
delete_option('chatpion_bridge_api_timeout');
delete_option('chatpion_bridge_enable_logging');
delete_option('chatpion_bridge_default_workspace_template');
delete_option('chatpion_bridge_db_version');

