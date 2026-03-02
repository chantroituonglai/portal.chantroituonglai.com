<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: OpenClaw Gateway
Description: Unified API gateway for OpenClaw orchestration over Portal APIs.
Version: 1.0.0
Author: Future Horizon
Requires at least: 2.3.*
*/

define('OPENCLAW_GATEWAY_MODULE_NAME', 'openclaw_gateway');
define('OPENCLAW_GATEWAY_DB_VERSION', 100);

register_language_files(OPENCLAW_GATEWAY_MODULE_NAME, [OPENCLAW_GATEWAY_MODULE_NAME]);

register_activation_hook(OPENCLAW_GATEWAY_MODULE_NAME, 'openclaw_gateway_activation_hook');
function openclaw_gateway_activation_hook()
{
    require_once __DIR__ . '/install.php';
}

register_deactivation_hook(OPENCLAW_GATEWAY_MODULE_NAME, 'openclaw_gateway_deactivation_hook');
function openclaw_gateway_deactivation_hook()
{
    // keep data on deactivation; only disable gateway write mode
    update_option('openclaw_gateway_enabled', 0);
}

hooks()->add_action('app_init', 'openclaw_gateway_init');
function openclaw_gateway_init()
{
    require_once __DIR__ . '/helpers/openclaw_gateway_helper.php';

    if (get_option('openclaw_gateway_enabled') === '') {
        add_option('openclaw_gateway_enabled', 1);
    }
    if (get_option('openclaw_gateway_auth_mode') === '') {
        add_option('openclaw_gateway_auth_mode', 'dual');
    }
    if (get_option('openclaw_gateway_read_only') === '') {
        add_option('openclaw_gateway_read_only', 0);
    }
    if (get_option('openclaw_gateway_request_timeout_ms') === '') {
        add_option('openclaw_gateway_request_timeout_ms', 12000);
    }
    if (get_option('openclaw_gateway_retry_max') === '') {
        add_option('openclaw_gateway_retry_max', 1);
    }
    if (get_option('openclaw_gateway_mask_sensitive') === '') {
        add_option('openclaw_gateway_mask_sensitive', 1);
    }
}
