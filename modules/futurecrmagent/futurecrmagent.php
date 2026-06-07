<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: FutureCRM Agent
Description: Provider-neutral CRM AI agent for ticket triage and sales document draft creation.
Version: 1.0.3
Requires at least: 3.2.*
*/

define('FUTURECRMAGENT_DB_VERSION', 103);
// Runtime logs are stored in futurecrmagent_sales_doc_runs and futurecrmagent_tool_runs.

require_once __DIR__ . '/src/FuturecrmagentProvider.php';
require_once __DIR__ . '/libraries/Futurecrmagent_llm_client.php';
require_once __DIR__ . '/libraries/Futurecrmagent_tool_registry.php';

hooks()->add_action('admin_init', 'futurecrmagent_module_init');
hooks()->add_action('admin_init', 'futurecrmagent_module_activation_hook');
hooks()->add_action('admin_init', 'futurecrmagent_run_migrations', 2);
hooks()->add_action('app_admin_footer', 'futurecrmagent_inject_sales_doc_ai');
hooks()->add_action('app_admin_footer', 'futurecrmagent_inject_tickets_bulk_ui');
hooks()->add_action('app_admin_footer', 'futurecrmagent_inject_ticket_ai_ui');
hooks()->add_filter('module_futurecrmagent_action_links', 'module_futurecrmagent_action_links');
hooks()->add_filter('piped_ticket_data', 'futurecrmagent_classify_piped_ticket', 10, 1);
hooks()->add_action('ticket_created', 'futurecrmagent_link_log_with_ticket');

register_language_files('futurecrmagent', ['futurecrmagent']);

function futurecrmagent_module_init(): void
{
    if (class_exists('\app\services\ai\AiProviderRegistry')) {
        \app\services\ai\AiProviderRegistry::registerProvider(
            'futurecrmagent',
            new \Perfexcrm\Futurecrmagent\FuturecrmagentProvider()
        );

        hooks()->do_action('after_futurecrmagent_provider_registered');
    }

    $CI = &get_instance();
    $language = $CI->session->userdata('language') ?: 'english';
    $CI->lang->load('futurecrmagent/futurecrmagent', $language);
    if ($language !== 'english') {
        $CI->lang->load('futurecrmagent/futurecrmagent', 'english');
    }

    $CI->app->add_settings_section_child('ai', 'futurecrmagent', [
        'name'     => _l('FutureCRM Agent'),
        'view'     => 'futurecrmagent/settings',
        'position' => 16,
        'icon'     => 'fa-solid fa-robot',
    ]);
}

function futurecrmagent_module_activation_hook(): void
{
    add_option('futurecrmagent_ticket_classify_enabled', 0);
    add_option('futurecrmagent_db_version', 0);
    add_option('futurecrmagent_ticket_prompt', 'You are a ticket triage assistant. Classify the following email into: category (one of: Technical Issue, Billing, Sales, Account, Feedback, Other), priority (one of: Low, Medium, High, Urgent), and provide a confidence score between 0 and 1. Return ONLY a compact JSON object with exactly these keys: category, priority, score. Do not include explanations, markdown, or code fences. Subject: {$subject}\nBody:\n{$body}');
    add_option('futurecrmagent_sales_doc_system_prompt', 'You convert CRM sales document instructions into safe Perfex CRM draft fields. Return JSON only.');

    foreach ([
        'futurecrmagent_map_dept_technical_issue',
        'futurecrmagent_map_dept_billing',
        'futurecrmagent_map_dept_sales',
        'futurecrmagent_map_dept_account',
        'futurecrmagent_map_dept_feedback',
        'futurecrmagent_map_dept_other',
        'futurecrmagent_map_pri_low',
        'futurecrmagent_map_pri_medium',
        'futurecrmagent_map_pri_high',
        'futurecrmagent_map_pri_urgent',
        'futurecrmagent_map_status_technical_issue',
        'futurecrmagent_map_status_billing',
        'futurecrmagent_map_status_sales',
        'futurecrmagent_map_status_account',
        'futurecrmagent_map_status_feedback',
        'futurecrmagent_map_status_other',
    ] as $option) {
        add_option($option);
    }

    if (get_option('ai_reply_language') === null) {
        add_option('ai_reply_language', 'auto');
    }
}

function futurecrmagent_run_migrations(): void
{
    $CI = &get_instance();
    $installedVersion = (int) get_option('futurecrmagent_db_version');
    if ($installedVersion >= FUTURECRMAGENT_DB_VERSION) {
        return;
    }

    if (isset($CI->app_modules)) {
        $result = $CI->app_modules->upgrade_database('futurecrmagent');
        if ($result !== true) {
            log_message('error', 'FutureCRM Agent migration failed: ' . print_r($result, true));
        }
    }
}

function module_futurecrmagent_action_links($actions)
{
    $actions[] = '<a href="' . admin_url('settings?group=futurecrmagent') . '">' . _l('settings') . '</a>';
    $actions[] = '<a href="' . admin_url('settings?group=ai') . '">' . _l('settings_group_ai') . '</a>';

    return $actions;
}

function futurecrmagent_inject_sales_doc_ai(): void
{
    if (!is_staff_logged_in()) {
        return;
    }

    $uri = uri_string();
    $isEdit = preg_match('#^admin/(estimates/estimate|invoices/invoice|proposals/proposal)/[0-9]+#', $uri);
    $isManage = preg_match('#^admin/(estimates|invoices|proposals)(/list_(estimates|invoices|proposals).*)?/?$#', $uri);
    $isPreview = preg_match('#^admin/(estimates/list_estimates|invoices/list_invoices|proposals/list_proposals)/[0-9]+#', $uri);
    if (!$isEdit && !$isManage && !$isPreview) {
        return;
    }

    $src = module_dir_url('futurecrmagent', 'assets/js/futurecrmagent_sales_doc.js');
    echo '<script id="futurecrmagent-sales-doc-js" src="' . html_escape($src) . '?v=113"></script>';
}

function futurecrmagent_inject_ticket_ai_ui(): void
{
    if (!is_staff_logged_in()) {
        return;
    }

    $src = module_dir_url('futurecrmagent', 'assets/js/futurecrmagent_ticket_ai_ui.js');
    echo '<script id="futurecrmagent-ticket-ai-ui-js" src="' . html_escape($src) . '?v=102"></script>';
}

function futurecrmagent_inject_tickets_bulk_ui(): void
{
    if (!is_staff_logged_in() || (int) get_option('futurecrmagent_ticket_classify_enabled') !== 1) {
        return;
    }

    $src = module_dir_url('futurecrmagent', 'assets/js/futurecrmagent_tickets_bulk.js');
    echo '<script id="futurecrmagent-tickets-bulk-js" src="' . html_escape($src) . '?v=102"></script>';
    echo '<style id="futurecrmagent-modal-zfix">#futurecrmagent_classify_modal{z-index:20000;} .modal-backdrop.futurecrmagent-backdrop{z-index:19990;}</style>';
    echo '<div class="modal fade" id="futurecrmagent_classify_modal" tabindex="-1" role="dialog">'
        . '<div class="modal-dialog" role="document"><div class="modal-content">'
        . '<div class="modal-header"><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>'
        . '<h4 class="modal-title">FutureCRM Agent Classification</h4></div>'
        . '<div class="modal-body"><div class="progress"><div id="futurecrmagent-progress-bar" class="progress-bar" role="progressbar" style="width:0%">0%</div></div>'
        . '<p id="futurecrmagent-progress-text" class="text-muted"></p>'
        . '<div class="table-responsive" style="max-height:300px; overflow:auto;"><table class="table table-bordered">'
        . '<thead><tr><th>ID</th><th>Category</th><th>Priority</th><th>Score</th><th>Status</th></tr></thead><tbody id="futurecrmagent-results-body"></tbody></table></div></div>'
        . '<div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">' . _l('close') . '</button></div>'
        . '</div></div></div>';
}

function futurecrmagent_classify_piped_ticket(array $data): array
{
    if ((int) get_option('futurecrmagent_ticket_classify_enabled') !== 1) {
        return $data;
    }

    try {
        $subject = (string) ($data['subject'] ?? '');
        $body = (string) ($data['body'] ?? '');
        $from = (string) ($data['email'] ?? '');
        $prompt = futurecrmagent_build_prompt_for_ticket(null, $subject, $body);
        $provider = new \Perfexcrm\Futurecrmagent\FuturecrmagentProvider();
        $raw = $provider->chat($prompt);
        [$category, $priority, $score, $error] = futurecrmagent_parse_classification_json($raw);

        futurecrmagent_log_ticket_classification([
            'source'         => 'pipe',
            'email_from'     => $from,
            'subject'        => $subject,
            'preview'        => mb_substr(strip_tags($body), 0, 500),
            'classification' => ($category && $priority) ? ($category . ' / ' . $priority) : null,
            'score'          => $score,
            'raw'            => $raw,
            'error'          => $error,
        ]);
    } catch (Throwable $e) {
        log_message('error', '[FUTURECRMAGENT] piped ticket classify failed: ' . $e->getMessage());
    }

    return $data;
}

function futurecrmagent_build_prompt(string $subject, string $body): string
{
    $template = (string) get_option('futurecrmagent_ticket_prompt');
    if ($template === '') {
        $template = 'Return JSON with category, priority, score. Subject: {$subject}\nBody:\n{$body}';
    }

    return strtr($template, [
        '{$subject}' => $subject,
        '{$body}' => $body,
        '{$allowed_categories}' => implode(', ', futurecrmagent_get_allowed_categories()),
        '{$allowed_priorities}' => implode(', ', futurecrmagent_get_allowed_priorities()),
    ]);
}

function futurecrmagent_build_prompt_for_ticket($ticketId, string $subject, string $body): string
{
    $base = futurecrmagent_build_prompt($subject, $body);
    if (!$ticketId || strpos($base, '{ticket_') === false) {
        return $base;
    }

    try {
        $CI = &get_instance();
        $CI->load->library('merge_fields/ticket_merge_fields');
        $mergeFields = (new Ticket_merge_fields())->format('futurecrmagent', (int) $ticketId);
        if (is_array($mergeFields)) {
            return strtr($base, $mergeFields);
        }
    } catch (Throwable $e) {
        log_message('error', '[FUTURECRMAGENT] ticket merge field expansion failed: ' . $e->getMessage());
    }

    return $base;
}

function futurecrmagent_parse_classification_json($raw): array
{
    $decoded = json_decode((string) $raw, true);
    if (!is_array($decoded)) {
        $str = (string) $raw;
        $start = strpos($str, '{');
        $end = strrpos($str, '}');
        if ($start !== false && $end !== false && $end > $start) {
            $decoded = json_decode(substr($str, $start, $end - $start + 1), true);
        }
    }

    $category = futurecrmagent_normalize_category($decoded['category'] ?? null);
    $priority = futurecrmagent_normalize_priority($decoded['priority'] ?? null);
    $score = isset($decoded['score']) ? (float) $decoded['score'] : null;
    $error = null;

    if ($category === null || $priority === null) {
        $error = 'Invalid JSON response from provider';
    }

    return [$category, $priority, $score, $error];
}

function futurecrmagent_normalize_category($val)
{
    if (!$val) {
        return null;
    }
    $map = [
        'technical issue' => 'Technical Issue',
        'tech issue' => 'Technical Issue',
        'bug' => 'Technical Issue',
        'billing' => 'Billing',
        'payment' => 'Billing',
        'sales' => 'Sales',
        'account' => 'Account',
        'feedback' => 'Feedback',
        'other' => 'Other',
    ];
    $v = strtolower(trim((string) $val));
    return $map[$v] ?? null;
}

function futurecrmagent_normalize_priority($val)
{
    if (!$val) {
        return null;
    }
    $map = [
        'low' => 'Low',
        'medium' => 'Medium',
        'normal' => 'Medium',
        'high' => 'High',
        'urgent' => 'Urgent',
        'critical' => 'Urgent',
    ];
    $v = strtolower(trim((string) $val));
    return $map[$v] ?? null;
}

function futurecrmagent_get_allowed_categories(): array
{
    return ['Technical Issue', 'Billing', 'Sales', 'Account', 'Feedback', 'Other'];
}

function futurecrmagent_get_allowed_priorities(): array
{
    return ['Low', 'Medium', 'High', 'Urgent'];
}

function futurecrmagent_link_log_with_ticket(int $ticketId): void
{
    try {
        $CI = &get_instance();
        $CI->load->model('futurecrmagent/Futurecrmagent_model');
        $CI->Futurecrmagent_model->link_latest_log_to_ticket($ticketId);
    } catch (Throwable $e) {
        log_message('error', '[FUTURECRMAGENT] link log failed: ' . $e->getMessage());
    }
}

function futurecrmagent_log_ticket_classification(array $log): void
{
    $CI = &get_instance();
    $CI->load->model('futurecrmagent/Futurecrmagent_model');
    $CI->Futurecrmagent_model->insert_log($log);
}
