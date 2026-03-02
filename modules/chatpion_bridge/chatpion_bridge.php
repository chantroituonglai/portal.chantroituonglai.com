<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Chatpion Bridge
Description: Perfex CRM ↔ Chatpion integration layer providing task ↔ campaign links.
Version: 0.1.0
Requires at least: 3.0.*
*/

define('CHATPION_BRIDGE_MODULE_NAME', 'chatpion_bridge');
define('CHATPION_BRIDGE_DB_VERSION', 101);

require_once __DIR__ . '/helpers/chatpion_bridge_helper.php';

register_activation_hook(CHATPION_BRIDGE_MODULE_NAME, 'chatpion_bridge_module_activation_hook');
register_deactivation_hook(CHATPION_BRIDGE_MODULE_NAME, 'chatpion_bridge_module_deactivation_hook');
register_language_files(CHATPION_BRIDGE_MODULE_NAME, [CHATPION_BRIDGE_MODULE_NAME]);

hooks()->add_action('admin_init', 'chatpion_bridge_init_menu');
hooks()->add_action('admin_init', 'chatpion_bridge_run_migrations', 5);
hooks()->add_action('app_admin_footer', 'chatpion_bridge_admin_footer_assets');
hooks()->add_filter('get_task_by_id', 'chatpion_bridge_inject_task_data');
hooks()->add_action('task_deleted', 'chatpion_bridge_task_deleted');

/**
 * Module activation callback
 */
function chatpion_bridge_module_activation_hook(): void
{
    $CI = &get_instance();
    require_once __DIR__ . '/install.php';
}

function chatpion_bridge_is_tasks_screen(): bool
{
    $CI = &get_instance();

    return $CI->router->fetch_class() === 'tasks';
}

function chatpion_bridge_admin_footer_assets(): void
{
    $lang = [
        'choose'          => _l('chatpion_bridge_task_choose_button'),
        'clear'           => _l('chatpion_bridge_task_unlink_button'),
        'link'            => _l('chatpion_bridge_task_link_button'),
        'refresh'         => _l('chatpion_bridge_task_refresh_button'),
        'unlink_confirm'  => _l('chatpion_bridge_confirm_unlink'),
        'no_results'      => _l('chatpion_bridge_campaign_empty'),
        'loading'         => _l('chatpion_bridge_campaign_loading'),
        'status_pending'  => _l('chatpion_bridge_status_pending'),
        'status_processing' => _l('chatpion_bridge_status_processing'),
        'status_completed'=> _l('chatpion_bridge_status_completed'),
        'workspace_saved' => _l('chatpion_bridge_workspace_saved'),
        'workspace_error' => _l('chatpion_bridge_workspace_save_error'),
        'unexpected_error'=> _l('chatpion_bridge_unexpected_error'),
        'not_synced'      => _l('chatpion_bridge_not_synced'),
        'not_available'   => _l('chatpion_bridge_not_available'),
        'campaign_display'=> _l('chatpion_bridge_campaign_display'),
        'owner'           => _l('chatpion_bridge_owner'),
        'media_type_label'=> _l('chatpion_bridge_media_type_label'),
        'media_type_facebook'   => _l('chatpion_bridge_media_type_facebook'),
        'media_type_instagram'  => _l('chatpion_bridge_media_type_instagram'),
        'preview_heading'       => _l('chatpion_bridge_preview_heading'),
        'metrics_unavailable'   => _l('chatpion_bridge_metrics_unavailable'),
        'all_accounts'          => _l('chatpion_bridge_campaign_filter_account_all'),
        'linked'                => _l('chatpion_bridge_linked'),
        'reset_filters'         => _l('chatpion_bridge_reset_filters'),
    ];

    echo '<script>window.ChatpionBridge = window.ChatpionBridge || {}; window.ChatpionBridge.lang = ' . json_encode($lang) . ';</script>';
    echo '<script src="' . module_dir_url(CHATPION_BRIDGE_MODULE_NAME, 'assets/js/chatpion-bridge-task.js') . '?v=' . time() . '"></script>';

    // Ensure the Campaign Modal markup exists globally in admin so JS can open it from any page
    echo '<div class="modal fade" id="chatpionCampaignModal" tabindex="-1" role="dialog" aria-hidden="true">'
        . '<div class="modal-dialog modal-lg" role="document">'
        . '<div class="modal-content">'
        . '<div class="modal-header">'
        . '<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>'
        . '<h4 class="modal-title">' . _l('chatpion_bridge_campaign_modal_title') . '</h4>'
        . '</div>'
        . '<div class="modal-body">'
        . '<div class="row m-bot15 align-items-center chatpion-modal-filters">'
        . '<div class="col-md-4 col-sm-12">'
        . '<input type="text" class="form-control" id="chatpion-campaign-search" placeholder="' . _l('chatpion_bridge_campaign_search_placeholder') . '">'
        . '</div>'
        . '<div class="col-md-3 col-sm-6 mtop10-xs">'
        . '<select id="chatpion-campaign-status" class="selectpicker" data-width="100%">'
        . '<option value="">' . _l('chatpion_bridge_campaign_filter_status') . '</option>'
        . '<option value="0">' . _l('chatpion_bridge_status_pending') . '</option>'
        . '<option value="1">' . _l('chatpion_bridge_status_processing') . '</option>'
        . '<option value="2">' . _l('chatpion_bridge_status_completed') . '</option>'
        . '</select>'
        . '</div>'
        . '<div class="col-md-3 col-sm-6 mtop10-xs">'
        . '<select id="chatpion-campaign-account" class="selectpicker" data-width="100%" data-live-search="true">'
        . '<option value="">' . _l('chatpion_bridge_campaign_filter_account_all') . '</option>'
        . '</select>'
        . '</div>'
        . '<div class="col-md-2 col-sm-12 mtop10-xs text-right">'
        . '<button type="button" class="btn btn-default btn-block" id="chatpion-campaign-reset">' . _l('chatpion_bridge_reset_filters') . '</button>'
        . '</div>'
        . '</div>'
        . '<div class="table-responsive">'
        . '<table class="table table-striped chatpion-campaign-table">'
        . '<thead>'
        . '<tr>'
        . '<th>' . _l('chatpion_bridge_campaign_name') . '</th>'
        . '<th>' . _l('chatpion_bridge_campaign_account') . '</th>'
        . '<th>' . _l('chatpion_bridge_campaign_status') . '</th>'
        . '<th>' . _l('chatpion_bridge_campaign_schedule') . '</th>'
        . '<th></th>'
        . '</tr>'
        . '</thead>'
        . '<tbody id="chatpion-campaign-results">'
        . '<tr>'
        . '<td colspan="5" class="text-center text-muted">' . _l('chatpion_bridge_campaign_loading') . '</td>'
        . '</tr>'
        . '</tbody>'
        . '</table>'
        . '</div>'
        . '<div class="tw-flex tw-justify-between tw-items-center tw-mt-3" id="chatpion-campaign-pagination">'
        . '<button type="button" class="btn btn-default btn-sm" data-page="prev">&laquo;</button>'
        . '<span class="tw-text-sm tw-text-neutral-600" data-role="pagination-info"></span>'
        . '<button type="button" class="btn btn-default btn-sm" data-page="next">&raquo;</button>'
        . '</div>'
        . '</div>'
        . '</div>'
        . '</div>'
        . '</div>';
}

function chatpion_bridge_inject_task_data($task)
{
    $CI = & get_instance();
    $CI->load->library('app_modules');

    if (! $CI->app_modules->is_active('chatpion_bridge')) {
        return $task;
    }

    $CI->load->model('chatpion_bridge/Chatpion_bridge_task_model', 'chatpionBridgeTaskModel');
    $CI->load->model('chatpion_bridge/Chatpion_bridge_model', 'chatpionBridgeModel');

    $link = $CI->chatpionBridgeTaskModel->get_task_link((int) $task->id);
    $formatted = $link ? $CI->chatpionBridgeModel->format_task_link($link) : null;
    $task->chatpion_campaign = $formatted ? (object) $formatted : null;

    return $task;
}

/**
 * Module deactivation callback
 */
function chatpion_bridge_module_deactivation_hook(): void
{
    // Keep data by default; placeholder in case we need cleanup hooks later.
}

/**
 * Register setup menu item
 */
function chatpion_bridge_init_menu(): void
{
    if (!is_admin()) {
        return;
    }

    $CI = &get_instance();
    $CI->app_menu->add_setup_menu_item('chatpion_bridge', [
        'href'     => admin_url('chatpion_bridge'),
        'name'     => _l('chatpion_bridge'),
        'position' => 76,
    ]);
}

/**
 * Ensure database schema is up to date
 */
function chatpion_bridge_run_migrations(): void
{
    $CI = &get_instance();
    $installed_version = (int) get_option('chatpion_bridge_db_version');

    if ($installed_version >= CHATPION_BRIDGE_DB_VERSION) {
        return;
    }

    if (isset($CI->app_modules)) {
        $result = $CI->app_modules->upgrade_database(CHATPION_BRIDGE_MODULE_NAME);
        if ($result !== true) {
            log_activity('[Chatpion Bridge] Migration failed: ' . print_r($result, true));
        }
    }
}

/**
 * Hook: Called after a task is deleted
 * Notifies ChatPion to remove the corresponding link
 * 
 * @param int $task_id The ID of the deleted task
 */
function chatpion_bridge_task_deleted($task_id): void
{
    $CI = &get_instance();
    
    log_message('error', '[ChatPion Bridge] ========== TASK DELETED HOOK ==========');
    log_message('error', '[ChatPion Bridge] Task ID: ' . $task_id);
    
    // Check if ChatPion Bridge link exists for this task
    $CI->db->select('*');
    $CI->db->where('task_id', $task_id);
    $link = $CI->db->get(db_prefix() . 'chatpion_bridge_task_links')->row();
    
    if (!$link) {
        log_message('error', '[ChatPion Bridge] No ChatPion link found for task: ' . $task_id);
        log_message('error', '[ChatPion Bridge] ========== HOOK END (NO LINK) ==========');
        return;
    }
    
    log_message('error', '[ChatPion Bridge] Found link:');
    log_message('error', '[ChatPion Bridge]   - Campaign ID: ' . $link->campaign_id);
    log_message('error', '[ChatPion Bridge]   - Project ID: ' . ($link->project_id ?? 'N/A'));
    log_message('error', '[ChatPion Bridge]   - User ID: ' . ($link->user_id ?? 'N/A'));
    log_message('error', '[ChatPion Bridge]   - Link ID: ' . ($link->id ?? 'N/A'));
    
    // Get ChatPion settings from module config
    $chatpion_base_url = get_option('chatpion_bridge_base_url');
    $chatpion_api_key = get_option('chatpion_bridge_api_key');
    
    log_message('error', '[ChatPion Bridge] Config check:');
    log_message('error', '[ChatPion Bridge]   - Base URL: ' . ($chatpion_base_url ?: '(empty)'));
    log_message('error', '[ChatPion Bridge]   - API Key: ' . ($chatpion_api_key ? substr($chatpion_api_key, 0, 10) . '...' : '(empty)'));
    
    if (empty($chatpion_base_url)) {
        log_message('error', '[ChatPion Bridge] ERROR: ChatPion base URL not configured in settings');
        log_message('error', '[ChatPion Bridge] ========== HOOK END (NO CONFIG) ==========');
        return;
    }
    
    if (empty($chatpion_api_key)) {
        log_message('error', '[ChatPion Bridge] ERROR: ChatPion API key not configured in settings');
        log_message('error', '[ChatPion Bridge] ========== HOOK END (NO API KEY) ==========');
        return;
    }
    
    // Prepare webhook URL using configured base URL
    $webhook_url = rtrim($chatpion_base_url, '/') . '/perfex_task_deleted';
    
    // Get project ID if available
    $project_id = !empty($link->project_id) ? $link->project_id : null;
    
    // Prepare POST data
    $post_data = [
        'api_key' => $chatpion_api_key,
        'task_id' => $task_id,
        'project_id' => $project_id
    ];
    
    log_message('error', '[ChatPion Bridge] Webhook call preparation:');
    log_message('error', '[ChatPion Bridge]   - URL: ' . $webhook_url);
    log_message('error', '[ChatPion Bridge]   - Method: POST');
    log_message('error', '[ChatPion Bridge]   - Data: ' . json_encode($post_data));
    
    // Call ChatPion webhook
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $webhook_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10); // 10 second timeout
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded'
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    $curl_info = curl_getinfo($ch);
    curl_close($ch);
    
    log_message('error', '[ChatPion Bridge] cURL execution completed:');
    log_message('error', '[ChatPion Bridge]   - HTTP Code: ' . $http_code);
    log_message('error', '[ChatPion Bridge]   - Total Time: ' . $curl_info['total_time'] . 's');
    log_message('error', '[ChatPion Bridge]   - Connect Time: ' . $curl_info['connect_time'] . 's');
    
    if ($error) {
        log_message('error', '[ChatPion Bridge] ERROR: cURL error - ' . $error);
        log_message('error', '[ChatPion Bridge] ========== HOOK END (CURL ERROR) ==========');
        return;
    }
    
    log_message('error', '[ChatPion Bridge] Response body: ' . $response);
    
    $result = json_decode($response, true);
    
    if (!$result) {
        log_message('error', '[ChatPion Bridge] ERROR: Failed to decode JSON response');
        log_message('error', '[ChatPion Bridge] Raw response: ' . substr($response, 0, 500));
        log_message('error', '[ChatPion Bridge] ========== HOOK END (JSON ERROR) ==========');
        return;
    }
    
    log_message('error', '[ChatPion Bridge] Parsed response: ' . json_encode($result));
    
    if ($result && isset($result['status']) && $result['status'] === 'success') {
        log_message('error', '[ChatPion Bridge] SUCCESS: ChatPion notified successfully');
        
        if (isset($result['data'])) {
            log_message('error', '[ChatPion Bridge] Response data: ' . json_encode($result['data']));
        }
        
        // Delete the local bridge link
        $CI->db->where('task_id', $task_id);
        $deleted = $CI->db->delete(db_prefix() . 'chatpion_bridge_task_links');
        
        if ($deleted) {
            log_message('error', '[ChatPion Bridge] Local bridge link deleted for task: ' . $task_id);
            log_message('error', '[ChatPion Bridge] Affected rows: ' . $CI->db->affected_rows());
        } else {
            log_message('error', '[ChatPion Bridge] WARNING: Failed to delete local bridge link');
        }
        
        log_message('error', '[ChatPion Bridge] ========== HOOK END (SUCCESS) ==========');
    } else {
        $error_msg = isset($result['message']) ? $result['message'] : 'Unknown error';
        log_message('error', '[ChatPion Bridge] ERROR: Webhook failed - ' . $error_msg);
        log_message('error', '[ChatPion Bridge] Full response: ' . json_encode($result));
        log_message('error', '[ChatPion Bridge] ========== HOOK END (WEBHOOK FAILED) ==========');
        
        // Optionally: Still delete local link even if webhook failed
        // $CI->db->where('task_id', $task_id);
        // $CI->db->delete(db_prefix() . 'chatpion_bridge_task_links');
    }
}
