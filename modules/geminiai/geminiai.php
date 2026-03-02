<?php

use app\services\ai\AiProviderRegistry;
use Perfexcrm\Geminiai\GeminiProvider;

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Gemini AI Integration
Description: Alternate AI provider module using Google Gemini API
Version: 1.0.1
Requires at least: 3.2.*
*/

define('GEMINIAI_DB_VERSION', 101);

require_once __DIR__ . '/src/GeminiProvider.php';

hooks()->add_action('admin_init', 'geminiai_module_init');
hooks()->add_action('admin_init', 'geminiai_module_activation_hook');
hooks()->add_action('admin_init', 'geminiai_run_migrations', 2);
// Inject UI helpers
hooks()->add_action('app_admin_footer', 'geminiai_inject_tickets_bulk_ui');
hooks()->add_action('app_admin_footer', 'geminiai_inject_ticket_ai_ui');

hooks()->add_filter('module_geminiai_action_links', 'module_geminiai_action_links');

// Load module language files
register_language_files('geminiai', ['geminiai']);

// Inject extra controls into core AI settings without editing core file
hooks()->add_action('settings_ai', function () {
    echo '<hr />';
    echo '<div class="com-md-12">'
        . '<i class="fa-regular fa-circle-question pull-left tw-mt-0.5 tw-mr-1" data-toggle="tooltip"'
        . ' data-title="' . _l('settings_ai_reply_language_help') . '"></i>'
        . render_select('settings[ai_reply_language]', [
            ['id' => 'auto', 'name' => 'Auto-detect (match customer)'],
            ['id' => 'vi', 'name' => 'Vietnamese'],
            ['id' => 'en', 'name' => 'English'],
            ['id' => 'ja', 'name' => 'Japanese'],
            ['id' => 'ko', 'name' => 'Korean'],
            ['id' => 'zh', 'name' => 'Chinese'],
            ['id' => 'fr', 'name' => 'French'],
            ['id' => 'de', 'name' => 'German'],
            ['id' => 'es', 'name' => 'Spanish'],
        ], ['id','name'], 'AI Reply Language', get_option('ai_reply_language') ?: 'auto')
        . '</div>';
});

// Add filter to prevent replying to ads/auto-responses without editing core
hooks()->add_filter('before_ai_tickets_suggest_ticket_reply', function ($prompt, $ticket) {
    try {
        $subject = strtolower(strip_tags($ticket->subject ?? ''));
        $message = strtolower(strip_tags($ticket->message ?? ''));
        $text = $subject . "\n" . $message;
        $keywords = [
            'do not reply','no-reply','noreply','auto-response','autoresponse',
            'thank you for your email','thank you for contacting','newsletter','promotion','unsubscribe','marketing','advertisement','advertising','faq page'
        ];
        foreach ($keywords as $kw) {
            if (strpos($text, $kw) !== false) {
                return "Return only this exact HTML snippet: <p>This message appears to be an automated notification or promotional content. No reply is necessary.</p>";
            }
        }
    } catch (\Throwable $e) {}
    return $prompt;
}, 10, 2);

// Add filter to force summary language via ?lang= param or default option
hooks()->add_filter('before_ai_tickets_summarize_ticket', function ($prompt, $ticket) {
    $CI = &get_instance();
    $lang = (string) $CI->input->get('lang');
    if ($lang === '' || $lang === 'auto') {
        $lang = (string) get_option('ai_reply_language');
    }
    if ($lang && $lang !== 'auto') {
        $prompt = "Always reply strictly in language code '" . $lang . "'.\n" . $prompt;
    }
    return $prompt;
}, 10, 2);

function geminiai_inject_ticket_ai_ui(): void
{
    if (!is_staff_logged_in()) { return; }
    $src = module_dir_url('geminiai', 'assets/js/geminiai_ticket_ai_ui.js');
    echo '<script id="geminiai-ticket-ai-ui-js" src="' . html_escape($src) . '"></script>';
}

// Ticket pipeline hooks
hooks()->add_filter('piped_ticket_data', 'geminiai_classify_piped_ticket', 10, 1);
hooks()->add_action('ticket_created', 'geminiai_link_log_with_ticket');

function geminiai_run_migrations(): void
{
    $CI = &get_instance();
    $installed_version = (int) get_option('geminiai_db_version');
    if ($installed_version >= GEMINIAI_DB_VERSION) {
        return;
    }
    if (isset($CI->app_modules)) {
        $result = $CI->app_modules->upgrade_database('geminiai');
        if ($result !== true) {
            log_message('error', 'Gemini AI migration failed: ' . print_r($result, true));
        }
    }
}

/**
 * Add additional settings links for this module in the module list area
 *
 * @param array $actions current actions
 * @return array
 */
function module_geminiai_action_links($actions)
{
    if (get_instance()->app_modules->is_active('geminiai')) {
        $actions[] = '<a href="' . admin_url('settings?group=geminiai') . '">' . _l('settings') . '</a>';
    }

    $actions[] = '<a href="' . admin_url('settings?group=ai') . '">' . _l('settings_group_ai') . '</a>';

    return $actions;
}

function geminiai_module_init(): void
{
    AiProviderRegistry::registerProvider('geminiai', new GeminiProvider());

    $CI = &get_instance();
    // Ensure module language is loaded before using _l()
    $language = $CI->session->userdata('language');
    if (!$language) { $language = 'english'; }
    $CI->lang->load('geminiai/geminiai', $language);
    if ($language !== 'english') {
        $CI->lang->load('geminiai/geminiai', 'english');
    }
    $CI->app->add_settings_section_child(
        'ai',
        'geminiai',
        [
            'name'     => _l('Gemini AI'),
            'view'     => 'geminiai/settings',
            'position' => 16,
            'icon'     => 'fa-solid fa-stars',
        ]
    );
}

function geminiai_module_activation_hook(): void
{
    add_option('geminiai_api_key');
    // Multi-key rotation support
    add_option('geminiai_api_keys');
    add_option('geminiai_active_key_index', 0);
    add_option('geminiai_model', 'gemini-1.5-flash');
    add_option('geminiai_max_token', 1024);
    add_option('geminiai_ticket_classify_enabled', 0);
    add_option('geminiai_db_version', 0);
    add_option('geminiai_ticket_prompt', 'You are a ticket triage assistant. Classify the following email into: category (one of: Technical Issue, Billing, Sales, Account, Feedback, Other), priority (one of: Low, Medium, High, Urgent), and provide a confidence score between 0 and 1. Return ONLY a compact JSON object with exactly these keys: category, priority, score. Do not include any explanations, markdown, or code fences. The output MUST be valid JSON.\nSubject: {$subject}\nBody:\n{$body}');
    // AI reply language stored globally (used by core views); default auto
    if (get_option('ai_reply_language') === null) {
        add_option('ai_reply_language', 'auto');
    }

    // Mapping options (category -> department)
    add_option('geminiai_map_dept_technical_issue');
    add_option('geminiai_map_dept_billing');
    add_option('geminiai_map_dept_sales');
    add_option('geminiai_map_dept_account');
    add_option('geminiai_map_dept_feedback');
    add_option('geminiai_map_dept_other');
    // Mapping options (priority -> priorityid)
    add_option('geminiai_map_pri_low');
    add_option('geminiai_map_pri_medium');
    add_option('geminiai_map_pri_high');
    add_option('geminiai_map_pri_urgent');
    // Mapping options (category -> status)
    add_option('geminiai_map_status_technical_issue');
    add_option('geminiai_map_status_billing');
    add_option('geminiai_map_status_sales');
    add_option('geminiai_map_status_account');
    add_option('geminiai_map_status_feedback');
    add_option('geminiai_map_status_other');
}

function geminiai_inject_tickets_bulk_ui(): void
{
    // Inject only for staff area; JS will no-op if not on tickets list
    if (!is_staff_logged_in()) {
        return;
    }
    // Show bulk classify UI only when enabled in settings
    if ((int) get_option('geminiai_ticket_classify_enabled') !== 1) {
        return;
    }
    $src = module_dir_url('geminiai', 'assets/js/geminiai_tickets_bulk.js');
    echo '<script id="geminiai-tickets-bulk-js" src="' . html_escape($src) . '"></script>';
    // Z-index override to keep the progress modal above others
    echo '<style id="geminiai-modal-zfix">#geminiai_classify_modal{z-index:20000;} .modal-backdrop.geminiai-backdrop{z-index:19990;}</style>';
    // Progress & results modal
    echo '<div class="modal fade" id="geminiai_classify_modal" tabindex="-1" role="dialog">'
        . '<div class="modal-dialog" role="document">'
        . '<div class="modal-content">'
        . '<div class="modal-header">'
        . '<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>'
        . '<h4 class="modal-title">Gemini Classification</h4>'
        . '</div>'
        . '<div class="modal-body">'
        . '<div class="progress">'
        . '<div id="geminiai-progress-bar" class="progress-bar" role="progressbar" style="width:0%">0%</div>'
        . '</div>'
        . '<p id="geminiai-progress-text" class="text-muted"></p>'
        . '<div class="table-responsive" style="max-height:300px; overflow:auto;">'
        . '<table class="table table-bordered"><thead><tr><th>ID</th><th>Category</th><th>Priority</th><th>Score</th><th>Status</th></tr></thead><tbody id="geminiai-results-body"></tbody></table>'
        . '</div>'
        . '</div>'
        . '<div class="modal-footer">'
        . '<button type="button" class="btn btn-default" data-dismiss="modal">' . _l('close') . '</button>'
        . '</div>'
        . '</div>'
        . '</div>'
        . '</div>';
}

/**
 * Filter: classify piped ticket content and log the read/decision
 */
function geminiai_classify_piped_ticket(array $data): array
{
    try {
        if ((int) get_option('geminiai_ticket_classify_enabled') !== 1) {
            return $data;
        }
        log_message('error', '[GEMINIAI] piped filter start');
        $provider = new GeminiProvider();
        $subject  = (string) ($data['subject'] ?? '');
        $body     = (string) ($data['body'] ?? '');
        $from     = (string) ($data['email'] ?? '');

        $prompt = geminiai_build_prompt_for_ticket(null, $subject, $body);

        log_message('error', '[GEMINIAI] piped calling provider');
        $raw = $provider->chat($prompt);
        $rawStr = (string) $raw;
        log_message('error', '[GEMINIAI] piped provider raw len=' . strlen($rawStr));
        log_message('error', '[GEMINIAI] piped provider raw body=' . substr($rawStr, 0, 2000));
        [$cat, $pri, $sco, $err] = geminiai_parse_classification_json($raw);

        // Log to history
        $CI = &get_instance();
        $CI->load->model('geminiai/Geminiai_model');
        $CI->Geminiai_model->insert_log([
            'source'         => 'pipe',
            'email_from'     => $from,
            'subject'        => $subject,
            'preview'        => mb_substr(strip_tags($body), 0, 500),
            'classification' => ($cat && $pri) ? ($cat . ' / ' . $pri) : null,
            'score'          => $sco,
            'raw'            => $raw,
            'error'          => $err,
        ]);

        // Optionally, we could add classification markers into the body for staff visibility
        // but for now we keep it in logs only
        return $data;
    } catch (\Throwable $e) {
        log_message('error', '[GEMINIAI] piped error: ' . $e->getMessage());
        $CI = &get_instance();
        $CI->load->model('geminiai/Geminiai_model');
        $CI->Geminiai_model->insert_log([
            'source'         => 'pipe',
            'email_from'     => $data['email'] ?? '',
            'subject'        => $data['subject'] ?? '',
            'preview'        => mb_substr(strip_tags($data['body'] ?? ''), 0, 500),
            'classification' => null,
            'score'          => null,
            'raw'            => null,
            'error'          => $e->getMessage(),
        ]);
        return $data;
    }
}

/**
 * Build strict prompt that enforces JSON-only output
 */
function geminiai_build_prompt(string $subject, string $body): string
{
    $template = (string) get_option('geminiai_ticket_prompt');
    if ($template === '') {
        $template = 'You are a ticket triage assistant. You must choose category only from: {$allowed_categories} and priority only from: {$allowed_priorities}. Provide a confidence score between 0 and 1. Return ONLY a compact JSON object with exactly these keys: category, priority, score. Do not include any explanations, markdown, or code fences. The output MUST be valid JSON.\nSubject: {$subject}\nBody:\n{$body}';
    }
    $allowedCategories = implode(', ', geminiai_get_allowed_categories());
    $allowedPriorities = implode(', ', geminiai_get_allowed_priorities());
    $replacements = [
        '{$subject}' => (string) $subject,
        '{$body}'    => (string) $body,
        '{$allowed_categories}' => $allowedCategories,
        '{$allowed_priorities}' => $allowedPriorities,
    ];
    return strtr($template, $replacements);
}

/**
 * Build prompt and expand ticket merge fields if ticketId provided.
 */
function geminiai_build_prompt_for_ticket($ticketId, string $subject, string $body): string
{
    $base = geminiai_build_prompt($subject, $body);
    // Replace ticket merge fields if present in template
    $hasBraces = strpos($base, '{') !== false && strpos($base, '}') !== false;
    if (!$hasBraces) {
        return $base;
    }
    $CI = &get_instance();
    $repl = [];
    if ($ticketId) {
        // Load merge fields for this ticket
        $CI->load->library('merge_fields/ticket_merge_fields');
        $mf = new Ticket_merge_fields();
        $fields = $mf->format('geminiai', (int) $ticketId);
        if (is_array($fields)) {
            $repl = $fields;
        }
    }
    // Fallbacks for piped (no ticket yet)
    if (!isset($repl['{ticket_subject}'])) {
        $repl['{ticket_subject}'] = (string) $subject;
    }
    if (!isset($repl['{ticket_message}'])) {
        $repl['{ticket_message}'] = (string) $body;
    }
    // Apply replacements
    return strtr($base, $repl);
}

/**
 * Try to parse AI response into [category, priority, score, error]
 */
function geminiai_parse_classification_json($raw): array
{
    $category = null; $priority = null; $score = null; $err = null;
    // 1) Try direct JSON decode
    $decoded = json_decode((string)$raw, true);
    if (is_array($decoded)) {
        $category = $decoded['category'] ?? null;
        $priority = $decoded['priority'] ?? null;
        $score    = isset($decoded['score']) ? (float)$decoded['score'] : null;
    }
    // 2) Try code-fence extraction ```...```
    if ($category === null && $priority === null) {
        if (preg_match('/```[a-zA-Z]*\n(.+?)```/s', (string)$raw, $m)) {
            $decoded2 = json_decode(trim($m[1]), true);
            if (is_array($decoded2)) {
                $category = $decoded2['category'] ?? $category;
                $priority = $decoded2['priority'] ?? $priority;
                $score    = isset($decoded2['score']) ? (float)$decoded2['score'] : $score;
            }
        }
    }
    // 3) Try curly braces slice
    if ($category === null && $priority === null) {
        $str = (string)$raw;
        $start = strpos($str, '{');
        $end   = strrpos($str, '}');
        if ($start !== false && $end !== false && $end > $start) {
            $json = substr($str, $start, $end - $start + 1);
            $decoded3 = json_decode($json, true);
            if (is_array($decoded3)) {
                $category = $decoded3['category'] ?? $category;
                $priority = $decoded3['priority'] ?? $priority;
                $score    = isset($decoded3['score']) ? (float)$decoded3['score'] : $score;
            }
        }
    }
    // 4) Heuristic key-value lines e.g. "category: X\npriority: Y\nscore: 0.8"
    if ($category === null && $priority === null) {
        $str = strtolower((string)$raw);
        if (preg_match('/category\s*[:\-]\s*([a-z ]+)/i', $str, $m1)) {
            $category = trim($m1[1]);
        }
        if (preg_match('/priority\s*[:\-]\s*([a-z ]+)/i', $str, $m2)) {
            $priority = trim($m2[1]);
        }
        if (preg_match('/score\s*[:\-]\s*([0-9]*\.?[0-9]+)/i', (string)$raw, $m3)) {
            $score = (float)$m3[1];
        }
    }
    // Normalize values
    $category = geminiai_normalize_category($category);
    $priority = geminiai_normalize_priority($priority);
    // Enforce allowed sets from settings
    $allowedCats = geminiai_get_allowed_categories();
    $allowedPris = geminiai_get_allowed_priorities();
    if ($category !== null && !in_array($category, $allowedCats, true)) {
        $category = null; $err = 'Category not permitted by settings';
    }
    if ($priority !== null && !in_array($priority, $allowedPris, true)) {
        $priority = null; $err = $err ?: 'Priority not permitted by settings';
    }
    if ($category === null || $priority === null) {
        $err = $err ?: 'Invalid JSON response from provider';
    }
    return [$category, $priority, $score, $err];
}

function geminiai_normalize_category($val)
{
    if (!$val) { return null; }
    $v = strtolower(trim($val));
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
    if (isset($map[$v])) { return $map[$v]; }
    // try first-letter match
    foreach ($map as $k => $canon) {
        if (strpos($v, $k) !== false) { return $canon; }
    }
    return null;
}

function geminiai_normalize_priority($val)
{
    if (!$val) { return null; }
    $v = strtolower(trim($val));
    $map = [
        'low' => 'Low',
        'medium' => 'Medium',
        'normal' => 'Medium',
        'high' => 'High',
        'urgent' => 'Urgent',
        'critical' => 'Urgent',
    ];
    return $map[$v] ?? null;
}

/**
 * Read allowed categories from mapping settings. If none configured, return all defaults.
 */
function geminiai_get_allowed_categories(): array
{
    $map = [
        'Technical Issue' => 'geminiai_map_dept_technical_issue',
        'Billing'         => 'geminiai_map_dept_billing',
        'Sales'           => 'geminiai_map_dept_sales',
        'Account'         => 'geminiai_map_dept_account',
        'Feedback'        => 'geminiai_map_dept_feedback',
        'Other'           => 'geminiai_map_dept_other',
    ];
    $allowed = [];
    foreach ($map as $label => $opt) {
        $val = (int) get_option($opt);
        if ($val > 0) { $allowed[] = $label; }
    }
    if (empty($allowed)) {
        $allowed = array_keys($map);
    }
    return $allowed;
}

/**
 * Read allowed priorities from mapping settings. If none configured, return defaults.
 */
function geminiai_get_allowed_priorities(): array
{
    $map = [
        'Low'    => 'geminiai_map_pri_low',
        'Medium' => 'geminiai_map_pri_medium',
        'High'   => 'geminiai_map_pri_high',
        'Urgent' => 'geminiai_map_pri_urgent',
    ];
    $allowed = [];
    foreach ($map as $label => $opt) {
        $val = (int) get_option($opt);
        if ($val > 0) { $allowed[] = $label; }
    }
    if (empty($allowed)) {
        $allowed = array_keys($map);
    }
    return $allowed;
}

/**
 * Apply department/priority (and status) mapping to a ticket
 */
function geminiai_apply_ticket_mapping(int $ticketId, $category, $priority): void
{
    $CI = &get_instance();
    $normCat = geminiai_normalize_category($category);
    $normPri = geminiai_normalize_priority($priority);

    $updates = [];
    // Department mapping
    if ($normCat) {
        $optKey = null;
        switch ($normCat) {
            case 'Technical Issue': $optKey = 'geminiai_map_dept_technical_issue'; break;
            case 'Billing': $optKey = 'geminiai_map_dept_billing'; break;
            case 'Sales': $optKey = 'geminiai_map_dept_sales'; break;
            case 'Account': $optKey = 'geminiai_map_dept_account'; break;
            case 'Feedback': $optKey = 'geminiai_map_dept_feedback'; break;
            case 'Other': $optKey = 'geminiai_map_dept_other'; break;
        }
        $deptId = $optKey ? (int) get_option($optKey) : 0;
        if ($deptId > 0) {
            $updates['department'] = $deptId;
        }
        // Status mapping by category
        $statusOpt = null;
        switch ($normCat) {
            case 'Technical Issue': $statusOpt = 'geminiai_map_status_technical_issue'; break;
            case 'Billing': $statusOpt = 'geminiai_map_status_billing'; break;
            case 'Sales': $statusOpt = 'geminiai_map_status_sales'; break;
            case 'Account': $statusOpt = 'geminiai_map_status_account'; break;
            case 'Feedback': $statusOpt = 'geminiai_map_status_feedback'; break;
            case 'Other': $statusOpt = 'geminiai_map_status_other'; break;
        }
        $statusId = $statusOpt ? (int) get_option($statusOpt) : 0;
        if ($statusId > 0) {
            $updates['status'] = $statusId;
        }
    }
    // Priority mapping
    if ($normPri) {
        $optKey = null;
        switch ($normPri) {
            case 'Low': $optKey = 'geminiai_map_pri_low'; break;
            case 'Medium': $optKey = 'geminiai_map_pri_medium'; break;
            case 'High': $optKey = 'geminiai_map_pri_high'; break;
            case 'Urgent': $optKey = 'geminiai_map_pri_urgent'; break;
        }
        $priId = $optKey ? (int) get_option($optKey) : 0;
        if ($priId > 0) {
            $updates['priority'] = $priId;
        }
    }
    if (!empty($updates)) {
        $CI->db->where('ticketid', $ticketId);
        $CI->db->update(db_prefix() . 'tickets', $updates);
    }
}

/**
 * After ticket created, try to attach ticket_id to the latest matching log (same from+subject)
 */
function geminiai_link_log_with_ticket(int $ticketId): void
{
    try {
        if ((int) get_option('geminiai_ticket_classify_enabled') !== 1) {
            return;
        }
        $CI = &get_instance();
        $CI->load->model('geminiai/Geminiai_model');
        $CI->Geminiai_model->link_latest_log_to_ticket($ticketId);
    } catch (\Throwable $e) {
        // ignore
    }
}

// Keep legacy helper for back-compat if used elsewhere
function geminiai_log_ticket_classification(array $log): void
{
    $CI = &get_instance();
    $CI->load->model('geminiai/Geminiai_model');
    $CI->Geminiai_model->insert_log($log);
}
