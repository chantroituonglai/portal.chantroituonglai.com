<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: OpenClaw Gateway
Description: Unified API gateway for OpenClaw orchestration over Portal APIs.
Version: 1.1.0
Author: Future Horizon
Requires at least: 2.3.*
*/

define('OPENCLAW_GATEWAY_MODULE_NAME', 'openclaw_gateway');
define('OPENCLAW_GATEWAY_DB_VERSION', 101);

register_language_files(OPENCLAW_GATEWAY_MODULE_NAME, [OPENCLAW_GATEWAY_MODULE_NAME]);

register_activation_hook(OPENCLAW_GATEWAY_MODULE_NAME, 'openclaw_gateway_activation_hook');
function openclaw_gateway_activation_hook()
{
    require_once __DIR__ . '/install.php';
}

register_deactivation_hook(OPENCLAW_GATEWAY_MODULE_NAME, 'openclaw_gateway_deactivation_hook');
function openclaw_gateway_deactivation_hook()
{
    // Keep data on deactivation.
    update_option('openclaw_gateway_enabled', 0);
}

hooks()->add_action('app_init', 'openclaw_gateway_init');
function openclaw_gateway_init()
{
    require_once __DIR__ . '/helpers/openclaw_gateway_helper.php';
    require_once __DIR__ . '/helpers/openclaw_gateway_bridge_helper.php';

    ocg_bridge_ensure_schema();

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

    // Bridge options (module-only, no core edits)
    if (get_option('openclaw_bridge_enabled') === '') {
        add_option('openclaw_bridge_enabled', 0);
    }
    if (get_option('openclaw_bridge_endpoint') === '') {
        add_option('openclaw_bridge_endpoint', '');
    }
    if (get_option('openclaw_bridge_auth_token') === '') {
        add_option('openclaw_bridge_auth_token', '');
    }
    if (get_option('openclaw_bridge_hmac_secret') === '') {
        add_option('openclaw_bridge_hmac_secret', '');
    }
    if (get_option('openclaw_bridge_timeout_ms') === '') {
        add_option('openclaw_bridge_timeout_ms', 12000);
    }
    if (get_option('openclaw_bridge_retry_max') === '') {
        add_option('openclaw_bridge_retry_max', 3);
    }
    if (get_option('openclaw_bridge_retry_delay_sec') === '') {
        add_option('openclaw_bridge_retry_delay_sec', 60);
    }
    if (get_option('openclaw_bridge_agent') === '') {
        add_option('openclaw_bridge_agent', 'crm-agent');
    }
    if (get_option('openclaw_bridge_notify_agent') === '') {
        add_option('openclaw_bridge_notify_agent', 'main');
    }
    if (get_option('openclaw_bridge_include_payload') === '') {
        add_option('openclaw_bridge_include_payload', 1);
    }
    if (get_option('openclaw_bridge_group_id') === '') {
        add_option('openclaw_bridge_group_id', '');
    }
    if (get_option('openclaw_bridge_payload_policy') === '') {
        add_option('openclaw_bridge_payload_policy', 'full');
    }
    if (get_option('openclaw_bridge_event_allow_patterns') === '') {
        add_option('openclaw_bridge_event_allow_patterns', '');
    }
    if (get_option('openclaw_bridge_event_deny_patterns') === '') {
        add_option('openclaw_bridge_event_deny_patterns', '');
    }
    if (get_option('openclaw_bridge_payload_fields_json') === '') {
        add_option('openclaw_bridge_payload_fields_json', '');
    }

    foreach (['sales', 'projects', 'tasks', 'tickets', 'clients', 'leads', 'staff', 'calendar', 'cron'] as $scopeKey) {
        if (get_option('openclaw_bridge_scope_' . $scopeKey) === '') {
            add_option('openclaw_bridge_scope_' . $scopeKey, 1);
        }
    }
}



hooks()->add_action('admin_init', 'openclaw_gateway_admin_menu');
function openclaw_gateway_admin_menu()
{
    $CI = &get_instance();
    if (!is_admin()) {
        return;
    }

    $CI->app_menu->add_sidebar_menu_item('openclaw_gateway_pipeline', [
        'slug' => 'openclaw_gateway_pipeline',
        'name' => 'Agent Pipeline',
        'icon' => 'fa fa-exchange menu-icon',
        'href' => admin_url('openclaw_gateway/openclaw_gateway_admin'),
        'position' => 32,
    ]);

    $CI->app_menu->add_sidebar_children_item('openclaw_gateway_pipeline', [
        'slug' => 'openclaw_gateway_logs',
        'name' => 'Overview',
        'icon' => 'fa fa-list',
        'href' => admin_url('openclaw_gateway/openclaw_gateway_admin'),
        'position' => 1,
    ]);

    $CI->app_menu->add_sidebar_children_item('openclaw_gateway_pipeline', [
        'slug' => 'openclaw_gateway_bridge_settings',
        'name' => 'Bridge Settings',
        'icon' => 'fa fa-sliders',
        'href' => admin_url('openclaw_gateway/openclaw_gateway_admin/bridge_settings'),
        'position' => 2,
    ]);

    $CI->app_menu->add_sidebar_children_item('openclaw_gateway_pipeline', [
        'slug' => 'openclaw_gateway_scopes',
        'name' => 'Scopes',
        'icon' => 'fa fa-sitemap',
        'href' => admin_url('openclaw_gateway/openclaw_gateway_admin/scope_settings'),
        'position' => 3,
    ]);

    $CI->app_menu->add_sidebar_children_item('openclaw_gateway_pipeline', [
        'slug' => 'openclaw_gateway_pipeline_logs',
        'name' => 'Pipeline Logs',
        'icon' => 'fa fa-random',
        'href' => admin_url('openclaw_gateway/openclaw_gateway_admin/pipeline_logs'),
        'position' => 4,
    ]);

    $CI->app_menu->add_sidebar_children_item('openclaw_gateway_pipeline', [
        'slug' => 'openclaw_gateway_bridge_queue',
        'name' => 'Bridge Queue',
        'icon' => 'fa fa-exchange',
        'href' => admin_url('openclaw_gateway/openclaw_gateway_admin/bridge_queue'),
        'position' => 5,
    ]);

    $CI->app_menu->add_sidebar_children_item('openclaw_gateway_pipeline', [
        'slug' => 'openclaw_gateway_gateway_logs',
        'name' => 'Gateway Logs',
        'icon' => 'fa fa-terminal',
        'href' => admin_url('openclaw_gateway/openclaw_gateway_admin/gateway_logs'),
        'position' => 6,
    ]);
}

// Sales + project events
hooks()->add_action('after_estimate_added', 'openclaw_gateway_on_after_estimate_added');
function openclaw_gateway_on_after_estimate_added($estimateId)
{
    ocg_bridge_emit_from_table('sales.estimate.created', 'estimates', 'id', $estimateId, 'create');
}

hooks()->add_action('after_estimate_updated', 'openclaw_gateway_on_after_estimate_updated');
function openclaw_gateway_on_after_estimate_updated($estimateId)
{
    ocg_bridge_emit_from_table('sales.estimate.updated', 'estimates', 'id', $estimateId, 'update');
}

hooks()->add_action('after_invoice_added', 'openclaw_gateway_on_after_invoice_added');
function openclaw_gateway_on_after_invoice_added($invoiceId)
{
    ocg_bridge_emit_from_table('sales.invoice.created', 'invoices', 'id', $invoiceId, 'create');
}

hooks()->add_action('invoice_updated', 'openclaw_gateway_on_invoice_updated');
function openclaw_gateway_on_invoice_updated($hookData)
{
    $invoiceId = is_array($hookData) && isset($hookData['id']) ? $hookData['id'] : null;
    if ($invoiceId) {
        ocg_bridge_emit_from_table('sales.invoice.updated', 'invoices', 'id', $invoiceId, 'update', ['hook' => 'invoice_updated']);
    }
}

hooks()->add_action('proposal_created', 'openclaw_gateway_on_proposal_created');
function openclaw_gateway_on_proposal_created($proposalId)
{
    ocg_bridge_emit_from_table('sales.proposal.created', 'proposals', 'id', $proposalId, 'create');
}

hooks()->add_action('after_proposal_updated', 'openclaw_gateway_on_after_proposal_updated');
function openclaw_gateway_on_after_proposal_updated($proposalId)
{
    ocg_bridge_emit_from_table('sales.proposal.updated', 'proposals', 'id', $proposalId, 'update');
}

// Customer document views (client portal)
hooks()->add_action('invoice_html_viewed', 'openclaw_gateway_on_invoice_html_viewed');
function openclaw_gateway_on_invoice_html_viewed($invoiceId)
{
    ocg_bridge_emit_from_table('sales.invoice.viewed', 'invoices', 'id', $invoiceId, 'view');
}

hooks()->add_action('estimate_html_viewed', 'openclaw_gateway_on_estimate_html_viewed');
function openclaw_gateway_on_estimate_html_viewed($estimateId)
{
    ocg_bridge_emit_from_table('sales.estimate.viewed', 'estimates', 'id', $estimateId, 'view');
}

hooks()->add_action('proposal_html_viewed', 'openclaw_gateway_on_proposal_html_viewed');
function openclaw_gateway_on_proposal_html_viewed($proposalId)
{
    ocg_bridge_emit_from_table('sales.proposal.viewed', 'proposals', 'id', $proposalId, 'view');
}

hooks()->add_action('contract_html_viewed', 'openclaw_gateway_on_contract_html_viewed');
function openclaw_gateway_on_contract_html_viewed($contractId)
{
    ocg_bridge_emit_from_table('contract.viewed', 'contracts', 'id', $contractId, 'view');
}

hooks()->add_action('delivery_note_html_viewed', 'openclaw_gateway_on_delivery_note_html_viewed');
function openclaw_gateway_on_delivery_note_html_viewed($deliveryNoteId)
{
    ocg_bridge_emit_from_table('delivery_note.viewed', 'delivery_notes', 'id', $deliveryNoteId, 'view');
}

hooks()->add_action('after_payment_added', 'openclaw_gateway_on_after_payment_added');
function openclaw_gateway_on_after_payment_added($paymentId)
{
    ocg_bridge_emit_from_table('sales.payment.created', 'invoicepaymentrecords', 'id', $paymentId, 'create');
}

hooks()->add_action('after_payment_updated', 'openclaw_gateway_on_after_payment_updated');
function openclaw_gateway_on_after_payment_updated($payload)
{
    $paymentId = is_array($payload) && isset($payload['paymentid']) ? $payload['paymentid'] : null;
    if ($paymentId) {
        ocg_bridge_emit_from_table('sales.payment.updated', 'invoicepaymentrecords', 'id', $paymentId, 'update', ['hook' => 'after_payment_updated']);
    }
}

hooks()->add_action('after_add_project', 'openclaw_gateway_on_after_add_project');
function openclaw_gateway_on_after_add_project($projectId)
{
    ocg_bridge_emit_from_table('project.created', 'projects', 'id', $projectId, 'create');
}

hooks()->add_action('after_update_project', 'openclaw_gateway_on_after_update_project');
function openclaw_gateway_on_after_update_project($projectId)
{
    ocg_bridge_emit_from_table('project.updated', 'projects', 'id', $projectId, 'update');
}

hooks()->add_action('project_status_changed', 'openclaw_gateway_on_project_status_changed');
function openclaw_gateway_on_project_status_changed($payload)
{
    $projectId = is_array($payload) && isset($payload['project_id']) ? $payload['project_id'] : null;
    if ($projectId) {
        ocg_bridge_emit_from_table('project.status_changed', 'projects', 'id', $projectId, 'update', ['hook_payload' => $payload]);
    }
}

// Calendar events (filter hooks available in core)
hooks()->add_filter('event_create_data', 'openclaw_gateway_on_event_create_data', 10, 1);
function openclaw_gateway_on_event_create_data($eventData)
{
    ocg_bridge_emit_event('calendar.event.create', 'events', null, is_array($eventData) ? $eventData : [], 'create', ['hook' => 'event_create_data']);
    return $eventData;
}

hooks()->add_filter('event_update_data', 'openclaw_gateway_on_event_update_data', 10, 2);
function openclaw_gateway_on_event_update_data($eventData, $eventId)
{
    ocg_bridge_emit_event('calendar.event.update', 'events', $eventId, is_array($eventData) ? $eventData : [], 'update', ['hook' => 'event_update_data']);
    return $eventData;
}

// Cron cycle heartbeat + retry queue
hooks()->add_action('after_cron_run', 'openclaw_gateway_after_cron_run', 10, 1);
function openclaw_gateway_after_cron_run($manually)
{
    ocg_bridge_emit_event('portal.cron.completed', 'cron', null, ['manually' => (bool) $manually], 'cycle');
    ocg_bridge_retry_failed();
}


function openclaw_gateway_pick_id($payload, $keys)
{
    if (!is_array($payload)) {
        return null;
    }
    foreach ($keys as $k) {
        if (isset($payload[$k]) && $payload[$k] !== '' && $payload[$k] !== null) {
            return $payload[$k];
        }
    }
    return null;
}

// Task flows
hooks()->add_action('after_add_task', 'openclaw_gateway_on_after_add_task');
function openclaw_gateway_on_after_add_task($taskId)
{
    ocg_bridge_emit_from_table('task.created', 'tasks', 'id', $taskId, 'create');
}

hooks()->add_action('after_update_task', 'openclaw_gateway_on_after_update_task');
function openclaw_gateway_on_after_update_task($taskId)
{
    ocg_bridge_emit_from_table('task.updated', 'tasks', 'id', $taskId, 'update');
}

hooks()->add_action('task_status_changed', 'openclaw_gateway_on_task_status_changed');
function openclaw_gateway_on_task_status_changed($payload)
{
    $taskId = openclaw_gateway_pick_id($payload, ['task_id', 'id']);
    if ($taskId) {
        ocg_bridge_emit_from_table('task.status_changed', 'tasks', 'id', $taskId, 'update', ['hook_payload' => $payload]);
    }
}

hooks()->add_action('task_deleted', 'openclaw_gateway_on_task_deleted');
function openclaw_gateway_on_task_deleted($taskId)
{
    ocg_bridge_emit_event('task.deleted', 'tasks', $taskId, ['task_id' => $taskId], 'delete');
}

// Ticket flows
hooks()->add_action('ticket_created', 'openclaw_gateway_on_ticket_created');
function openclaw_gateway_on_ticket_created($ticketId)
{
    ocg_bridge_emit_from_table('ticket.created', 'tickets', 'ticketid', $ticketId, 'create');
}

hooks()->add_action('after_ticket_status_changed', 'openclaw_gateway_on_after_ticket_status_changed');
function openclaw_gateway_on_after_ticket_status_changed($payload)
{
    $ticketId = openclaw_gateway_pick_id($payload, ['ticket_id', 'ticketid', 'id']);
    if ($ticketId) {
        ocg_bridge_emit_from_table('ticket.status_changed', 'tickets', 'ticketid', $ticketId, 'update', ['hook_payload' => $payload]);
    }
}

hooks()->add_action('after_ticket_deleted', 'openclaw_gateway_on_after_ticket_deleted');
function openclaw_gateway_on_after_ticket_deleted($ticketId)
{
    ocg_bridge_emit_event('ticket.deleted', 'tickets', $ticketId, ['ticket_id' => $ticketId], 'delete');
}

// Customer / clients / contacts
hooks()->add_action('after_client_created', 'openclaw_gateway_on_after_client_created');
function openclaw_gateway_on_after_client_created($payload)
{
    $clientId = openclaw_gateway_pick_id($payload, ['client_id', 'customer_id', 'userid', 'id']);
    if ($clientId) {
        ocg_bridge_emit_from_table('customer.created', 'clients', 'userid', $clientId, 'create', ['hook_payload' => $payload]);
    }
}

hooks()->add_action('client_updated', 'openclaw_gateway_on_client_updated');
function openclaw_gateway_on_client_updated($payload)
{
    $clientId = openclaw_gateway_pick_id($payload, ['client_id', 'customer_id', 'userid', 'id']);
    if ($clientId) {
        ocg_bridge_emit_from_table('customer.updated', 'clients', 'userid', $clientId, 'update', ['hook_payload' => $payload]);
    }
}

hooks()->add_action('client_status_changed', 'openclaw_gateway_on_client_status_changed');
function openclaw_gateway_on_client_status_changed($payload)
{
    $clientId = openclaw_gateway_pick_id($payload, ['client_id', 'customer_id', 'userid', 'id']);
    if ($clientId) {
        ocg_bridge_emit_from_table('customer.status_changed', 'clients', 'userid', $clientId, 'update', ['hook_payload' => $payload]);
    }
}

hooks()->add_action('after_client_deleted', 'openclaw_gateway_on_after_client_deleted');
function openclaw_gateway_on_after_client_deleted($clientId)
{
    ocg_bridge_emit_event('customer.deleted', 'clients', $clientId, ['client_id' => $clientId], 'delete');
}

hooks()->add_action('contact_created', 'openclaw_gateway_on_contact_created');
function openclaw_gateway_on_contact_created($contactId)
{
    ocg_bridge_emit_from_table('contact.created', 'contacts', 'id', $contactId, 'create');
}

hooks()->add_action('contact_updated', 'openclaw_gateway_on_contact_updated');
function openclaw_gateway_on_contact_updated($contactId, $data = [])
{
    ocg_bridge_emit_from_table('contact.updated', 'contacts', 'id', $contactId, 'update', ['hook_payload' => $data]);
}

hooks()->add_action('contact_status_changed', 'openclaw_gateway_on_contact_status_changed');
function openclaw_gateway_on_contact_status_changed($payload)
{
    $contactId = openclaw_gateway_pick_id($payload, ['contact_id', 'id']);
    if ($contactId) {
        ocg_bridge_emit_from_table('contact.status_changed', 'contacts', 'id', $contactId, 'update', ['hook_payload' => $payload]);
    }
}

hooks()->add_action('contact_deleted', 'openclaw_gateway_on_contact_deleted');
function openclaw_gateway_on_contact_deleted($contactId, $result = null)
{
    ocg_bridge_emit_event('contact.deleted', 'contacts', $contactId, ['contact_id' => $contactId, 'result' => $result], 'delete');
}

// Lead flows
hooks()->add_action('lead_created', 'openclaw_gateway_on_lead_created');
function openclaw_gateway_on_lead_created($leadId)
{
    if (is_array($leadId)) {
        $leadId = openclaw_gateway_pick_id($leadId, ['lead_id', 'id']);
    }
    if ($leadId) {
        ocg_bridge_emit_from_table('lead.created', 'leads', 'id', $leadId, 'create');
    }
}

hooks()->add_action('after_lead_updated', 'openclaw_gateway_on_after_lead_updated');
function openclaw_gateway_on_after_lead_updated($leadId)
{
    ocg_bridge_emit_from_table('lead.updated', 'leads', 'id', $leadId, 'update');
}

hooks()->add_action('lead_status_changed', 'openclaw_gateway_on_lead_status_changed');
function openclaw_gateway_on_lead_status_changed($payload)
{
    $leadId = openclaw_gateway_pick_id($payload, ['lead_id', 'id']);
    if ($leadId) {
        ocg_bridge_emit_from_table('lead.status_changed', 'leads', 'id', $leadId, 'update', ['hook_payload' => $payload]);
    }
}

hooks()->add_action('after_lead_deleted', 'openclaw_gateway_on_after_lead_deleted');
function openclaw_gateway_on_after_lead_deleted($leadId)
{
    ocg_bridge_emit_event('lead.deleted', 'leads', $leadId, ['lead_id' => $leadId], 'delete');
}

// Staff flows
hooks()->add_action('staff_member_created', 'openclaw_gateway_on_staff_member_created');
function openclaw_gateway_on_staff_member_created($staffId)
{
    ocg_bridge_emit_from_table('staff.created', 'staff', 'staffid', $staffId, 'create');
}

hooks()->add_action('staff_member_updated', 'openclaw_gateway_on_staff_member_updated');
function openclaw_gateway_on_staff_member_updated($staffId)
{
    ocg_bridge_emit_from_table('staff.updated', 'staff', 'staffid', $staffId, 'update');
}

hooks()->add_action('after_staff_status_change', 'openclaw_gateway_on_after_staff_status_change');
function openclaw_gateway_on_after_staff_status_change($staffId)
{
    ocg_bridge_emit_from_table('staff.status_changed', 'staff', 'staffid', $staffId, 'update');
}

hooks()->add_action('staff_member_deleted', 'openclaw_gateway_on_staff_member_deleted');
function openclaw_gateway_on_staff_member_deleted($payload)
{
    $staffId = openclaw_gateway_pick_id($payload, ['staff_id', 'id']);
    ocg_bridge_emit_event('staff.deleted', 'staff', $staffId, is_array($payload) ? $payload : ['staff_id' => $staffId], 'delete');
}
