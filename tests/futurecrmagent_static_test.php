<?php

$root = dirname(__DIR__);

function fca_read($path)
{
    global $root;
    $full = $root . '/' . $path;
    if (!is_file($full)) {
        throw new RuntimeException('Missing file: ' . $path);
    }

    return file_get_contents($full);
}

function fca_assert($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

fca_assert(!is_dir($root . '/modules/geminiai'), 'Legacy geminiai module directory must be renamed.');

$module = fca_read('modules/futurecrmagent/futurecrmagent.php');
$controller = fca_read('modules/futurecrmagent/controllers/Futurecrmagent.php');
$model = fca_read('modules/futurecrmagent/models/Futurecrmagent_model.php');
$client = fca_read('modules/futurecrmagent/libraries/Futurecrmagent_llm_client.php');
$registry = fca_read('modules/futurecrmagent/libraries/Futurecrmagent_tool_registry.php');
$js = fca_read('modules/futurecrmagent/assets/js/futurecrmagent_sales_doc.js');
$migration = fca_read('modules/futurecrmagent/migrations/102_version_102.php');
$migration103 = fca_read('modules/futurecrmagent/migrations/103_version_103.php');

fca_assert(strpos($module, 'Module Name: FutureCRM Agent') !== false, 'FutureCRM Agent module header is required.');
fca_assert(strpos($module, 'futurecrmagent_inject_sales_doc_ai') !== false, 'Sales document AI asset injection hook is required.');
fca_assert(strpos($module, 'futurecrmagent_sales_doc_runs') !== false, 'Sales document run log table must be managed by the module.');

foreach (['sales_doc_assist', 'apply_sales_doc_draft', 'create_sales_doc_from_plan', 'sales_doc_context'] as $method) {
    fca_assert(strpos($controller, 'function ' . $method . '(') !== false, 'Missing controller endpoint: ' . $method);
}
foreach (['preview_assist', 'preview_action_plan', 'submit_preview_action_approval'] as $method) {
    fca_assert(strpos($controller, 'function ' . $method . '(') !== false, 'Missing preview gateway endpoint: ' . $method);
}
fca_assert(strpos($controller, "['tool_gateway']") !== false, 'Preview assistant must send tool context into the LLM harness before answering.');
fca_assert(strpos($controller, 'preview_tool_llm_answered') !== false, 'Preview assistant must distinguish LLM-enhanced tool answers from local fallback answers.');
fca_assert(strpos($controller, 'function preview_assist_payload(') !== false, 'Preview and compatibility tool endpoints must share the LLM-enhanced preview flow.');
fca_assert(strpos($controller, 'function run_llm_selected_tool_preview(') !== false, 'Preview assistant must allow LLM to select tools from the approved catalog.');
fca_assert(strpos($controller, 'chat_tool_json') !== false && strpos($controller, 'tool_requests') !== false, 'Preview assistant must execute LLM-selected tool requests before final analysis.');
fca_assert(strpos($controller, 'llm_tool_requests') !== false, 'Preview assistant must expose whether tools were selected by the LLM.');
fca_assert(strpos($controller, 'Compatibility tool endpoint routed through CRM tools and LLM analysis.') !== false, 'Compatibility tool endpoint must not remain local-only.');
foreach (['tool_catalog', 'tool_assist', 'submit_tool_action_approval'] as $method) {
    fca_assert(strpos($controller, 'function ' . $method . '(') !== false, 'Missing tool gateway endpoint: ' . $method);
}
fca_assert(strpos($controller, 'private function json_response') !== false && strpos($controller, 'exit;') !== false, 'AJAX JSON responses must stop execution after output.');

fca_assert(strpos($client, 'futureagent_proxy_base_url') !== false, 'LLM client must use FutureAgent proxy base URL.');
fca_assert(strpos($client, 'futureagent_proxy_api_key') !== false, 'LLM client must use FutureAgent proxy API key.');
fca_assert(strpos($client, '/chat/completions') !== false, 'LLM client must call OpenAI-compatible chat completions.');
fca_assert(strpos($client, 'chat_tool_json') !== false, 'LLM client must expose tool-call JSON fallback support.');
fca_assert(strpos($client, 'generativelanguage.googleapis.com') === false, 'LLM client must not call Gemini directly.');
fca_assert(strpos($client, 'gemini-') === false, 'LLM client must not hardcode Gemini models.');

foreach (['Futurecrmagent_tool_registry', 'get_catalog', 'execute_tool', 'approval_plan_from_tool', 'sales_doc.get_summary', 'crm.customer.get_history', 'sales_doc.note.add'] as $needle) {
    fca_assert(strpos($registry, $needle) !== false, 'Tool registry missing required capability: ' . $needle);
}
foreach (['customers', 'contacts', 'leads', 'estimates', 'proposals', 'invoices', 'projects', 'tasks', 'tickets', 'notes', 'reminders', 'contracts', 'expenses', 'staff', 'taxes', 'currencies'] as $domain) {
    fca_assert(strpos($registry, "'" . $domain . "'") !== false || strpos($registry, '"' . $domain . '"') !== false, 'Tool registry missing core CRM domain: ' . $domain);
}
foreach (['call_user_func', 'shell_exec', 'proc_open', 'passthru', 'raw_sql', 'webhook'] as $forbidden) {
    fca_assert(strpos($registry, $forbidden) === false, 'Tool registry must not expose arbitrary execution: ' . $forbidden);
}
foreach (['payment', 'refund', 'credit', 'delete'] as $blocked) {
    fca_assert(strpos($registry, "'blocked_actions'") !== false && strpos($registry, $blocked) !== false, 'Tool registry must explicitly block action: ' . $blocked);
}
fca_assert(strpos($registry, "'approval_required' => true") !== false, 'Write tools must require approval.');
fca_assert(strpos($registry, "'max_tool_turns' => 6") !== false, 'Tool loop must be bounded to 6 turns.');

foreach (['estimate', 'proposal', 'invoice'] as $docType) {
    fca_assert(strpos($model, "'" . $docType . "'") !== false, 'Sales document harness missing doc type: ' . $docType);
}
foreach (['build_sales_doc_harness', 'normalize_sales_doc_plan', 'validate_sales_doc_plan', 'apply_sales_doc_plan', 'create_sales_doc_from_plan', 'log_sales_doc_run'] as $method) {
    fca_assert(strpos($model, 'function ' . $method . '(') !== false, 'Missing model method: ' . $method);
}
foreach (['build_preview_harness', 'preview_action_catalog', 'normalize_preview_response', 'validate_preview_action_plan', 'create_preview_action_approval'] as $method) {
    fca_assert(strpos($model, 'function ' . $method . '(') !== false, 'Missing preview gateway model method: ' . $method);
}
fca_assert(strpos($model, 'normalize_preview_list') !== false, 'Preview normalization must sanitize citations/context lists.');
fca_assert(strpos($model, 'preview_scalar_label') !== false, 'Preview normalization must convert object citations into readable labels.');
foreach (['log_tool_run', 'tool_context', 'tool_trace_summary'] as $method) {
    fca_assert(strpos($model, 'function ' . $method . '(') !== false, 'Missing tool audit/model method: ' . $method);
}
foreach (['preview_status_label', 'preview_item_lines', 'preview_missing_summary'] as $method) {
    fca_assert(strpos($model, 'function ' . $method . '(') !== false, 'Tool preview must return business-readable summaries: ' . $method);
}
fca_assert(strpos($model, 'function fallback_preview_answer(') !== false, 'Preview read-only assistant needs a local fallback when LLM proxy is unavailable.');
foreach (['sales_doc.note.add', 'sales_doc.task.create', 'sales_doc.email.prepare_send', 'sales_doc.status.update', 'sales_doc.attachment.summary'] as $actionKey) {
    fca_assert(strpos($model, $actionKey) !== false, 'Preview action catalog missing write action: ' . $actionKey);
}
foreach (['shell_exec', 'proc_open', 'n8n', 'webhook'] as $forbidden) {
    fca_assert(strpos($model, $forbidden) === false, 'Preview gateway must not expose arbitrary executor: ' . $forbidden);
}
fca_assert(strpos($model, 'futureagent_approvals') !== false, 'Preview write actions must create FutureAgent approvals.');

fca_assert(strpos($js, 'fa-wand-magic-sparkles') !== false || strpos($js, 'fa-magic') !== false, 'Sales document AI must use a sparkle icon trigger.');
fca_assert(strpos($js, 'futurecrmagent-sales-doc-fab') !== false, 'Sales document AI trigger must render as a floating action button.');
fca_assert(strpos($js, 'futurecrmagent-sales-doc-panel') !== false, 'Sales document AI must render a floating chat panel.');
fca_assert(strpos($js, 'futurecrmagent-sales-doc-overlay') !== false, 'Sales document AI must render a modal overlay.');
fca_assert(strpos($js, 'futurecrmagent-sales-doc-context') !== false, 'Sales document AI modal must include a right context panel.');
foreach (['Balanced', 'Technical', '/summary', '/missing_fields', '/action_note', 'Export Chat', 'Pin Summary'] as $uiText) {
    fca_assert(strpos($js, $uiText) !== false, 'Sales document AI modal missing UI capability: ' . $uiText);
}
foreach (['/tools', 'Tool trace', 'futurecrmagent/tool_catalog', 'futurecrmagent/tool_assist', 'futurecrmagent/submit_tool_action_approval'] as $toolUi) {
    fca_assert(strpos($js, $toolUi) !== false, 'Sales document AI modal missing tool gateway UI: ' . $toolUi);
}
fca_assert(strpos($js, 'formatListValue') !== false, 'Sales document AI list renderer must avoid [object Object]/boolean output.');
fca_assert(strpos($js, 'typeof value === "boolean"') !== false, 'Sales document AI list renderer must skip boolean list values.');
fca_assert(strpos($js, 'function renderMarkdown(') !== false, 'Sales document AI must render safe Markdown instead of showing raw markdown text.');
fca_assert(strpos($js, 'futurecrmagent-sales-doc-markdown') !== false, 'Sales document AI must style rendered Markdown content.');
fca_assert(strpos($js, 'function renderInsightList(') !== false, 'Sales document AI must render missing info and risks as enriched callouts.');
fca_assert(strpos($js, 'isTechnicalMode') !== false, 'Technical traces must be hidden unless Technical mode is selected.');
fca_assert(strpos($js, 'Nguồn dữ liệu đã dùng') !== false && strpos($js, 'isTechnicalMode()') !== false, 'Sources/citations must not render as default chat noise.');
fca_assert(strpos($js, 'Connected') !== false, 'Sales document AI chat panel must show connection state.');
fca_assert(strpos($js, 'mode: "manage"') !== false, 'Sales document AI must support manage/list mode.');
fca_assert(strpos($js, 'mode: "preview"') !== false, 'Sales document AI must support preview mode.');
fca_assert(strpos($js, 'window.location.hash') !== false && strpos($js, 'hashchange') !== false, 'Sales document AI must detect preview records opened from manage-page hash routes.');
foreach (['Hỏi/Tra cứu', 'Tổng hợp', 'Đề xuất hành động'] as $label) {
    fca_assert(strpos($js, $label) !== false, 'Preview assistant missing mode label: ' . $label);
}
fca_assert(strpos($js, 'futurecrmagent/sales_doc_assist') !== false, 'Sales document AI assist endpoint must be used by JS.');
fca_assert(strpos($js, 'futurecrmagent/apply_sales_doc_draft') !== false, 'Sales document AI apply endpoint must be used by JS.');
fca_assert(strpos($js, 'futurecrmagent/create_sales_doc_from_plan') !== false, 'Sales document AI create endpoint must be used by JS.');
fca_assert(strpos($js, 'futurecrmagent/preview_assist') !== false, 'Preview assistant endpoint must be used by JS.');
fca_assert(strpos($js, 'var endpoint = endpoints.previewAssist') !== false, 'Preview chat must call LLM-enhanced preview_assist, not local-only tool_assist.');
fca_assert(strpos($js, 'futurecrmagent/preview_action_plan') !== false, 'Preview action-plan endpoint must be used by JS.');
fca_assert(strpos($js, 'futurecrmagent/submit_preview_action_approval') !== false, 'Preview approval endpoint must be used by JS.');
fca_assert(strpos($js, 'add_item_to_table') !== false, 'Sales document AI must apply items through Perfex item table helpers.');
fca_assert(strpos($js, 'data-autosave-url') !== false, 'Sales document AI must recognize autosave-enabled drafts.');
fca_assert(strpos($js, 'target_status') !== false, 'Sales document AI must send draft/open target status.');

fca_assert(strpos($migration, 'geminiai_ticket_logs') !== false, 'Migration must account for legacy ticket logs.');
fca_assert(strpos($migration, 'futurecrmagent_ticket_logs') !== false, 'Migration must create/rename FutureCRM ticket logs.');
fca_assert(strpos($migration, 'futurecrmagent_sales_doc_runs') !== false, 'Migration must create sales document run logs.');
fca_assert(strpos($migration103, 'futurecrmagent_tool_runs') !== false, 'Migration must create tool run audit logs.');

foreach (['list_estimates', 'list_invoices', 'list_proposals'] as $previewRoute) {
    fca_assert(strpos($module, $previewRoute) !== false, 'Sales document preview route must inject assistant asset: ' . $previewRoute);
}

echo "FutureCRM Agent static test passed.\n";
