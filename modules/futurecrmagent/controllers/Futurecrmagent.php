<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Futurecrmagent extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('futurecrmagent/Futurecrmagent_model');
    }

    public function sales_doc_context()
    {
        $this->require_sales_doc_ajax();

        $docType = (string) $this->input->post('doc_type');
        $draftId = (int) $this->input->post('draft_id');
        $message = (string) $this->input->post('message');
        $this->require_sales_doc_permission($docType, $draftId > 0 ? 'edit' : 'create');

        $this->json_response([
            'success' => true,
            'context' => $this->Futurecrmagent_model->build_sales_doc_harness($docType, $draftId, $message),
        ]);
    }

    public function sales_doc_assist()
    {
        $this->require_sales_doc_ajax();

        $docType = (string) $this->input->post('doc_type');
        $draftId = (int) $this->input->post('draft_id');
        $message = trim((string) $this->input->post('message'));
        $this->require_sales_doc_permission($docType, $draftId > 0 ? 'edit' : 'create');

        if ($message === '') {
            $this->json_response(['success' => false, 'message' => 'Instruction is required.']);
        }

        try {
            $harness = $this->Futurecrmagent_model->build_sales_doc_harness($docType, $draftId, $message);
            $messages = [
                [
                    'role' => 'system',
                    'content' => (string) get_option('futurecrmagent_sales_doc_system_prompt') . "\nReturn JSON only with keys: doc_type, header, items, missing_fields, warnings, confidence.",
                ],
                [
                    'role' => 'user',
                    'content' => json_encode($harness),
                ],
            ];

            $this->load->library('futurecrmagent/Futurecrmagent_llm_client');
            $llm = $this->futurecrmagent_llm_client->chat_json($messages, 0.2);
            if (empty($llm['ok'])) {
                $this->Futurecrmagent_model->log_sales_doc_run($docType, $draftId, $message, $messages, $llm, null, 'failed', $llm['error'] ?? null);
                $this->json_response(['success' => false, 'message' => $llm['error'] ?? 'LLM request failed.']);
            }

            $plan = $this->Futurecrmagent_model->normalize_sales_doc_plan($docType, $llm['json']);
            $validation = $this->Futurecrmagent_model->validate_sales_doc_plan($docType, $draftId, $plan, $draftId > 0);
            $status = $validation['valid'] ? 'planned' : 'rejected';
            $this->Futurecrmagent_model->log_sales_doc_run($docType, $draftId, $message, $messages, $llm, $plan, $status, implode('; ', $validation['errors']));

            $this->json_response([
                'success' => $validation['valid'],
                'message' => $validation['valid'] ? 'Plan generated.' : implode("\n", $validation['errors']),
                'plan' => $plan,
                'validation' => $validation,
            ]);
        } catch (Throwable $e) {
            log_message('error', '[FUTURECRMAGENT] sales_doc_assist failed: ' . $e->getMessage());
            $this->json_response(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function apply_sales_doc_draft()
    {
        $this->require_sales_doc_ajax();

        $docType = (string) $this->input->post('doc_type');
        $draftId = (int) $this->input->post('draft_id');
        $plan = json_decode((string) $this->input->post('plan'), true);
        $this->require_sales_doc_permission($docType, 'edit');
        if (!is_array($plan)) {
            $plan = [];
        }

        $plan = $this->Futurecrmagent_model->normalize_sales_doc_plan($docType, $plan);
        $validation = $this->Futurecrmagent_model->validate_sales_doc_plan($docType, $draftId, $plan, true);
        if (!$validation['valid']) {
            $this->json_response([
                'success' => false,
                'message' => implode("\n", $validation['errors']),
                'validation' => $validation,
            ]);
        }

        try {
            $this->Futurecrmagent_model->apply_sales_doc_plan($docType, $draftId, $plan, 'draft');
        } catch (Throwable $e) {
            $this->Futurecrmagent_model->log_sales_doc_run($docType, $draftId, '', [], [], $plan, 'failed', $e->getMessage());
            $this->json_response(['success' => false, 'message' => $e->getMessage()]);
        }

        $this->Futurecrmagent_model->log_sales_doc_run($docType, $draftId, '', [], [], $plan, 'applied', null);
        $this->json_response([
            'success' => true,
            'message' => 'Draft updated.',
            'plan' => $plan,
        ]);
    }

    public function create_sales_doc_from_plan()
    {
        $this->require_sales_doc_ajax();

        $docType = (string) $this->input->post('doc_type');
        $targetStatus = (string) $this->input->post('target_status');
        $sourceText = (string) $this->input->post('message');
        $plan = json_decode((string) $this->input->post('plan'), true);
        $this->require_sales_doc_permission($docType, 'create');
        if (!is_array($plan)) {
            $plan = [];
        }

        $plan = $this->Futurecrmagent_model->normalize_sales_doc_plan($docType, $plan);
        $validation = $this->Futurecrmagent_model->validate_sales_doc_plan($docType, 0, $plan, false);
        if (!$validation['valid']) {
            $this->Futurecrmagent_model->log_sales_doc_run($docType, 0, $sourceText, [], [], $plan, 'rejected', implode('; ', $validation['errors']));
            $this->json_response([
                'success' => false,
                'message' => implode("\n", $validation['errors']),
                'validation' => $validation,
            ]);
        }

        try {
            $created = $this->Futurecrmagent_model->create_sales_doc_from_plan($docType, $plan, $targetStatus);
        } catch (Throwable $e) {
            $this->Futurecrmagent_model->log_sales_doc_run($docType, 0, $sourceText, [], [], $plan, 'failed', $e->getMessage());
            $this->json_response(['success' => false, 'message' => $e->getMessage()]);
        }

        $mode = $targetStatus === 'open' ? 'created_open' : 'created_draft';
        $this->Futurecrmagent_model->log_sales_doc_run($docType, (int) $created['id'], $sourceText, [], [], $plan, $mode, null);
        $this->json_response([
            'success' => true,
            'message' => $targetStatus === 'open' ? 'Open document created.' : 'Draft created.',
            'id' => (int) $created['id'],
            'edit_url' => $created['edit_url'],
        ]);
    }

    public function preview_assist()
    {
        $this->require_sales_doc_ajax();

        $docType = (string) $this->input->post('doc_type');
        $docId = (int) $this->input->post('doc_id');
        $mode = (string) $this->input->post('mode');
        $message = trim((string) $this->input->post('message'));
        $this->require_preview_doc_access($docType, $docId);

        if ($message === '') {
            $this->json_response(['success' => false, 'message' => 'Question is required.']);
        }

        try {
            $this->json_response($this->preview_assist_payload($docType, $docId, $message, $mode));
        } catch (Throwable $e) {
            log_message('error', '[FUTURECRMAGENT] preview_assist failed: ' . $e->getMessage());
            $this->json_response(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function tool_catalog()
    {
        $this->require_sales_doc_ajax();

        $docType = (string) $this->input->post('doc_type');
        $docId = (int) $this->input->post('doc_id');
        if ($docId > 0) {
            $this->require_preview_doc_access($docType, $docId);
        } else {
            $this->require_sales_doc_permission($docType, 'create');
        }

        $this->load->library('futurecrmagent/Futurecrmagent_tool_registry');
        $this->json_response([
            'success' => true,
            'catalog' => $this->futurecrmagent_tool_registry->get_catalog($docType, $docId),
            'context' => $this->Futurecrmagent_model->tool_context($docType, $docId, ''),
        ]);
    }

    public function tool_assist()
    {
        $this->require_sales_doc_ajax();

        $docType = (string) $this->input->post('doc_type');
        $docId = (int) $this->input->post('doc_id');
        $mode = (string) $this->input->post('mode');
        $message = trim((string) $this->input->post('message'));
        $this->require_preview_doc_access($docType, $docId);

        if ($message === '') {
            $this->json_response(['success' => false, 'message' => 'Question is required.']);
        }

        try {
            $payload = $this->preview_assist_payload($docType, $docId, $message, $mode);
            $payload['message'] = 'Compatibility tool endpoint routed through CRM tools and LLM analysis.';
            $this->json_response($payload);
        } catch (Throwable $e) {
            log_message('error', '[FUTURECRMAGENT] tool_assist failed: ' . $e->getMessage());
            $this->json_response(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function submit_tool_action_approval()
    {
        $this->require_sales_doc_ajax();

        $docType = (string) $this->input->post('doc_type');
        $docId = (int) $this->input->post('doc_id');
        $message = trim((string) $this->input->post('message'));
        $plan = json_decode((string) $this->input->post('action_plan'), true);
        $this->require_preview_doc_access($docType, $docId);
        if (!is_array($plan)) {
            $plan = [];
        }

        $validation = $this->Futurecrmagent_model->validate_preview_action_plan($docType, $docId, $plan);
        if (!$validation['valid']) {
            $this->Futurecrmagent_model->log_tool_run([
                'context_type' => $docType,
                'context_id' => $docId,
                'tool_name' => (string) ($plan['action_key'] ?? ''),
                'args' => $plan,
                'status' => 'rejected',
                'error' => implode('; ', $validation['errors']),
            ]);
            $this->json_response([
                'success' => false,
                'message' => implode("\n", $validation['errors']),
                'validation' => $validation,
            ]);
        }

        try {
            $approval = $this->Futurecrmagent_model->create_preview_action_approval($docType, $docId, $plan, $message);
            $this->Futurecrmagent_model->log_tool_run([
                'context_type' => $docType,
                'context_id' => $docId,
                'tool_name' => (string) ($plan['action_key'] ?? ''),
                'args' => $plan,
                'result_summary' => $approval,
                'status' => 'approval_created',
            ]);
            $this->json_response([
                'success' => true,
                'message' => 'Approval request created. No CRM write action has been executed.',
                'approval' => $approval,
                'approval_url' => admin_url('futureagent/approvals'),
            ]);
        } catch (Throwable $e) {
            log_message('error', '[FUTURECRMAGENT] submit_tool_action_approval failed: ' . $e->getMessage());
            $this->json_response(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function preview_action_plan()
    {
        $this->require_sales_doc_ajax();

        $docType = (string) $this->input->post('doc_type');
        $docId = (int) $this->input->post('doc_id');
        $message = trim((string) $this->input->post('message'));
        $this->require_preview_doc_access($docType, $docId);

        if ($message === '') {
            $this->json_response(['success' => false, 'message' => 'Action request is required.']);
        }

        try {
            $harness = $this->Futurecrmagent_model->build_preview_harness($docType, $docId, $message, 'action');
            $messages = $this->preview_messages($harness, true);

            $this->load->library('futurecrmagent/Futurecrmagent_llm_client');
            $llm = $this->futurecrmagent_llm_client->chat_json($messages, 0.2);
            if (empty($llm['ok'])) {
                $this->Futurecrmagent_model->log_sales_doc_run($docType, $docId, $message, $messages, $llm, null, 'preview_action_failed', $llm['error'] ?? null);
                $this->json_response(['success' => false, 'message' => $llm['error'] ?? 'LLM request failed.']);
            }

            $preview = $this->Futurecrmagent_model->normalize_preview_response($docType, $llm['json']);
            $validation = $this->Futurecrmagent_model->validate_preview_action_plan($docType, $docId, $preview['action_plan'] ?? []);
            $status = $validation['valid'] ? 'preview_action_planned' : 'preview_action_rejected';
            $this->Futurecrmagent_model->log_sales_doc_run($docType, $docId, $message, $messages, $llm, $preview, $status, implode('; ', $validation['errors']));

            $this->json_response([
                'success' => $validation['valid'],
                'message' => $validation['valid'] ? 'Action plan generated.' : implode("\n", $validation['errors']),
                'preview' => $preview,
                'validation' => $validation,
            ]);
        } catch (Throwable $e) {
            log_message('error', '[FUTURECRMAGENT] preview_action_plan failed: ' . $e->getMessage());
            $this->json_response(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function submit_preview_action_approval()
    {
        $this->require_sales_doc_ajax();

        $docType = (string) $this->input->post('doc_type');
        $docId = (int) $this->input->post('doc_id');
        $message = trim((string) $this->input->post('message'));
        $plan = json_decode((string) $this->input->post('action_plan'), true);
        $this->require_preview_doc_access($docType, $docId);
        if (!is_array($plan)) {
            $plan = [];
        }

        $validation = $this->Futurecrmagent_model->validate_preview_action_plan($docType, $docId, $plan);
        if (!$validation['valid']) {
            $this->Futurecrmagent_model->log_sales_doc_run($docType, $docId, $message, [], [], ['action_plan' => $plan], 'preview_approval_rejected', implode('; ', $validation['errors']));
            $this->json_response([
                'success' => false,
                'message' => implode("\n", $validation['errors']),
                'validation' => $validation,
            ]);
        }

        try {
            $approval = $this->Futurecrmagent_model->create_preview_action_approval($docType, $docId, $plan, $message);
            $this->Futurecrmagent_model->log_sales_doc_run($docType, $docId, $message, [], [], ['action_plan' => $plan, 'approval' => $approval], 'approval_created', null);
            $this->json_response([
                'success' => true,
                'message' => 'Approval request created. No CRM write action has been executed.',
                'approval' => $approval,
                'approval_url' => admin_url('futureagent/approvals'),
            ]);
        } catch (Throwable $e) {
            log_message('error', '[FUTURECRMAGENT] submit_preview_action_approval failed: ' . $e->getMessage());
            $this->json_response(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function bulk_classify()
    {
        if (!is_staff_logged_in()) {
            ajax_access_denied();
        }
        if (!is_admin() && !has_permission('tickets', '', 'view')) {
            ajax_access_denied();
        }
        if (!$this->input->is_ajax_request()) {
            show_error('Bad Request', 400);
        }
        if ((int) get_option('futurecrmagent_ticket_classify_enabled') !== 1) {
            $this->json_response(['success' => false, 'message' => 'FutureCRM Agent classification is disabled.']);
        }

        $ids = $this->input->post('ids');
        $ids = is_array($ids) ? $ids : [$ids];
        $provider = new \Perfexcrm\Futurecrmagent\FuturecrmagentProvider();
        $results = [];

        foreach ($ids as $id) {
            $id = (int) $id;
            $ticket = $this->db->where('ticketid', $id)->get(db_prefix() . 'tickets')->row();
            if (!$ticket) {
                $results[] = ['id' => $id, 'error' => 'Ticket not found'];
                continue;
            }

            try {
                $raw = $provider->chat(futurecrmagent_build_prompt_for_ticket($id, (string) $ticket->subject, (string) $ticket->message));
                [$category, $priority, $score, $error] = futurecrmagent_parse_classification_json($raw);
                futurecrmagent_log_ticket_classification([
                    'source' => 'bulk',
                    'email_from' => $ticket->email,
                    'subject' => $ticket->subject,
                    'preview' => mb_substr(strip_tags((string) $ticket->message), 0, 500),
                    'classification' => ($category && $priority) ? ($category . ' / ' . $priority) : null,
                    'score' => $score,
                    'ticket_id' => $id,
                    'raw' => $raw,
                    'error' => $error,
                ]);
                $this->Futurecrmagent_model->apply_mapping($id, $category, $priority);
                $results[] = ['id' => $id, 'category' => $category, 'priority' => $priority, 'score' => $score, 'error' => $error];
            } catch (Throwable $e) {
                $results[] = ['id' => $id, 'error' => $e->getMessage()];
            }
        }

        $this->json_response(['success' => true, 'results' => $results]);
    }

    private function require_sales_doc_ajax(): void
    {
        if (!is_staff_logged_in()) {
            ajax_access_denied();
        }
        if (!$this->input->is_ajax_request()) {
            show_error('Bad Request', 400);
        }
    }

    private function require_sales_doc_permission(string $docType, string $action = 'edit'): void
    {
        $capabilities = [
            'estimate' => 'estimates',
            'invoice' => 'invoices',
            'proposal' => 'proposals',
        ];

        $action = $action === 'create' ? 'create' : 'edit';
        if (!isset($capabilities[$docType]) || staff_cant($action, $capabilities[$docType])) {
            ajax_access_denied();
        }
    }

    private function require_preview_doc_access(string $docType, int $docId): void
    {
        $helpers = [
            'estimate' => 'user_can_view_estimate',
            'invoice' => 'user_can_view_invoice',
            'proposal' => 'user_can_view_proposal',
        ];

        if ($docId <= 0 || empty($helpers[$docType]) || !function_exists($helpers[$docType]) || !$helpers[$docType]($docId)) {
            ajax_access_denied();
        }
    }

    private function preview_messages(array $harness, bool $forceAction): array
    {
        $instruction = $forceAction
            ? 'Create one approval-gated CRM action plan from the approved action catalog. Never execute actions. Return JSON only.'
            : 'Answer read-only CRM questions from the provided document context and trusted PHP tool results. Add business judgment, risks, and recommendations based only on supplied data. If the user asks for a write action, include an approval-gated action_plan only. Return JSON only.';

        return [
            [
                'role' => 'system',
                'content' => $instruction . ' Required JSON keys: answer, citations, context_used, action_plan, risks, missing_info. action_plan must use only action_key values present in action_catalog. Write in Vietnamese unless the user asks otherwise. Interpret common Vietnamese typos from context, for example "khách hàng này là ao" usually means "khách hàng này là ai". Do not reveal chain-of-thought, hidden reasoning, raw prompts, raw tool payloads, or internal JSON. For summaries, mention what the document is, notable commercial points, possible missing/risk items, and concrete next steps.',
            ],
            [
                'role' => 'user',
                'content' => json_encode($harness),
            ],
        ];
    }

    private function preview_assist_payload(string $docType, int $docId, string $message, string $mode): array
    {
        $harness = $this->Futurecrmagent_model->build_preview_harness($docType, $docId, $message, $mode);
        $toolPreview = $this->run_llm_selected_tool_preview($docType, $docId, $message, $mode, $harness);
        if (empty($toolPreview['selected'])) {
            $toolPreview = $this->run_tool_gateway_preview($docType, $docId, $message, $mode);
        }
        $harness['tool_gateway'] = [
            'trace' => $toolPreview['trace'],
            'local_context_answer' => $toolPreview['preview']['answer'] ?? '',
            'local_missing_info' => $toolPreview['preview']['missing_info'] ?? [],
            'local_risks' => $toolPreview['preview']['risks'] ?? [],
            'catalog_summary' => $toolPreview['catalog']['tools'] ?? [],
            'selection_mode' => !empty($toolPreview['selected']) ? 'llm_tool_requests' : 'php_heuristic_fallback',
            'instruction' => 'Use these trusted PHP tool results as source data. Add business judgment and concise recommendations. Do not expose raw tool JSON unless asked in Technical mode.',
        ];
        $messages = $this->preview_messages($harness, $mode === 'action');

        $this->load->library('futurecrmagent/Futurecrmagent_llm_client');
        $llm = $this->futurecrmagent_llm_client->chat_json($messages, 0.2);
        if (empty($llm['ok'])) {
            $preview = !empty($toolPreview['preview'])
                ? $toolPreview['preview']
                : $this->Futurecrmagent_model->fallback_preview_answer($docType, $docId, $message, $mode, $llm['error'] ?? 'LLM request failed.');
            $this->Futurecrmagent_model->log_sales_doc_run($docType, $docId, $message, $messages, $llm, $preview, 'preview_fallback_answered', $llm['error'] ?? null);

            return [
                'success' => true,
                'message' => 'Preview answered from local CRM context because the LLM proxy was unavailable.',
                'preview' => $preview,
            ];
        }

        $preview = $this->Futurecrmagent_model->normalize_preview_response($docType, $llm['json']);
        if (trim((string) ($preview['answer'] ?? '')) === '' && !empty($toolPreview['preview'])) {
            $preview = $toolPreview['preview'];
        } else {
            $preview['tool_trace'] = $toolPreview['trace'];
            $preview['analysis_summary'] = !empty($toolPreview['selected'])
                ? 'LLM đã chọn tool từ catalog được phép; PHP validate/chạy tool và LLM chỉ phân tích trên kết quả đã chuẩn hóa. Raw reasoning không hiển thị.'
                : 'PHP đã fallback chọn tool nội bộ theo ngữ cảnh; LLM phân tích trên kết quả đã chuẩn hóa. Raw reasoning không hiển thị.';
            if (empty($preview['context_used'])) {
                $preview['context_used'] = $toolPreview['preview']['context_used'] ?? ['document header', 'document items', 'document totals', 'tool trace'];
            }
            if (empty($preview['citations'])) {
                $preview['citations'] = $toolPreview['preview']['citations'] ?? ['Perfex ' . $docType . ' #' . $docId, 'items table'];
            }
        }

        $this->Futurecrmagent_model->log_sales_doc_run($docType, $docId, $message, $messages, $llm, $preview, 'preview_tool_llm_answered', null);

        return [
            'success' => true,
            'message' => 'Preview answer generated from CRM tools and LLM analysis.',
            'preview' => $preview,
        ];
    }

    private function run_llm_selected_tool_preview(string $docType, int $docId, string $message, string $mode, array $harness): array
    {
        $mode = in_array($mode, ['qa', 'summary', 'action'], true) ? $mode : 'qa';
        $this->load->library('futurecrmagent/Futurecrmagent_tool_registry');
        $this->load->library('futurecrmagent/Futurecrmagent_llm_client');

        $context = $this->Futurecrmagent_model->tool_context($docType, $docId, $message);
        $catalog = $this->futurecrmagent_tool_registry->get_catalog($docType, $docId);
        $toolsForLlm = $this->futurecrmagent_tool_registry->tools_for_llm($docType, $docId);

        $selectionHarness = [
            'task' => 'Select CRM tools needed to answer the user question. Return JSON only.',
            'doc_type' => $docType,
            'doc_id' => $docId,
            'mode' => $mode,
            'user_message' => $message,
            'current_document' => $harness['document']['header'] ?? [],
            'current_customer' => $harness['customer'] ?? null,
            'available_tool_names' => array_keys($catalog['tools'] ?? []),
            'rules' => [
                'For any question about this document, request sales_doc.get_summary first.',
                'For customer identity, history, or commercial relationship, also request crm.customer.get_history.',
                'For missing data, request sales_doc.find_missing_fields.',
                'For totals/pricing/tax, request sales_doc.explain_totals.',
                'For related CRM activity, request sales_doc.list_related_records.',
                'Write tools only prepare approval plans and must not execute writes.',
                'Return no more than 6 tool_requests.',
            ],
        ];

        $messages = [[
            'role' => 'system',
            'content' => 'You are a CRM tool selector. Return JSON with tool_requests only when tools are needed. Do not answer the user yet. Use only available tools.',
        ], [
            'role' => 'user',
            'content' => json_encode($selectionHarness),
        ]];

        $llm = $this->futurecrmagent_llm_client->chat_tool_json($messages, $toolsForLlm, 0.1);
        if (empty($llm['ok'])) {
            return [
                'selected' => false,
                'preview' => [],
                'trace' => [],
                'catalog' => $catalog,
                'error' => $llm['error'] ?? 'LLM tool selection failed.',
            ];
        }

        $requests = $llm['json']['tool_requests'] ?? [];
        if (!is_array($requests) || !$requests) {
            return [
                'selected' => false,
                'preview' => [],
                'trace' => [],
                'catalog' => $catalog,
            ];
        }

        $trace = [];
        foreach (array_slice($requests, 0, 6) as $request) {
            if (!is_array($request)) {
                continue;
            }
            $toolName = (string) ($request['name'] ?? $request['tool'] ?? '');
            if ($toolName === '') {
                continue;
            }
            $args = is_array($request['arguments'] ?? null) ? $request['arguments'] : [];
            $args = array_merge([
                'doc_type' => $docType,
                'doc_id' => $docId,
                'customer_id' => (int) ($context['customer_id'] ?? 0),
                'query' => $message,
                'note' => $message,
                'summary' => $message,
            ], $args);

            try {
                $entry = $this->futurecrmagent_tool_registry->execute_tool($toolName, $args, $context);
            } catch (Throwable $e) {
                $entry = [
                    'tool' => str_replace('__', '.', $toolName),
                    'mode' => 'read',
                    'duration_ms' => 0,
                    'result' => ['error' => $e->getMessage()],
                ];
            }
            $trace[] = $entry;
            $this->Futurecrmagent_model->log_tool_run([
                'context_type' => $docType,
                'context_id' => $docId,
                'tool_name' => $entry['tool'],
                'args' => $args,
                'result_summary' => $this->Futurecrmagent_model->tool_trace_summary([$entry]),
                'status' => empty($entry['result']['error']) ? 'ok' : 'failed',
                'error' => $entry['result']['error'] ?? null,
                'duration_ms' => (int) ($entry['duration_ms'] ?? 0),
            ]);
        }

        if (!$trace) {
            return [
                'selected' => false,
                'preview' => [],
                'trace' => [],
                'catalog' => $catalog,
            ];
        }

        return [
            'selected' => true,
            'preview' => $this->Futurecrmagent_model->normalize_tool_preview($docType, $docId, $message, $mode, $trace, $catalog),
            'trace' => $this->Futurecrmagent_model->tool_trace_summary($trace),
            'catalog' => $catalog,
        ];
    }

    private function run_tool_gateway_preview(string $docType, int $docId, string $message, string $mode): array
    {
        $mode = in_array($mode, ['qa', 'summary', 'action'], true) ? $mode : 'qa';
        $this->load->library('futurecrmagent/Futurecrmagent_tool_registry');

        $context = $this->Futurecrmagent_model->tool_context($docType, $docId, $message);
        $catalog = $this->futurecrmagent_tool_registry->get_catalog($docType, $docId);
        $toolNames = $this->futurecrmagent_tool_registry->select_tools($message, $mode);
        $toolNames = array_slice($toolNames, 0, 6);
        $trace = [];

        foreach ($toolNames as $toolName) {
            $args = [
                'doc_type' => $docType,
                'doc_id' => $docId,
                'customer_id' => (int) ($context['customer_id'] ?? 0),
                'query' => $message,
                'note' => $message,
                'summary' => $message,
            ];

            $entry = $this->futurecrmagent_tool_registry->execute_tool($toolName, $args, $context);
            $trace[] = $entry;
            $this->Futurecrmagent_model->log_tool_run([
                'context_type' => $docType,
                'context_id' => $docId,
                'tool_name' => $entry['tool'],
                'args' => $args,
                'result_summary' => $this->Futurecrmagent_model->tool_trace_summary([$entry]),
                'status' => 'ok',
                'duration_ms' => (int) ($entry['duration_ms'] ?? 0),
            ]);
        }

        return [
            'preview' => $this->Futurecrmagent_model->normalize_tool_preview($docType, $docId, $message, $mode, $trace, $catalog),
            'trace' => $this->Futurecrmagent_model->tool_trace_summary($trace),
            'catalog' => $catalog,
        ];
    }

    private function json_response(array $payload): void
    {
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($payload));
        $this->output->_display();
        exit;
    }
}
