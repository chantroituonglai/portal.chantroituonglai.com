<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Futurecrmagent Model
 * - Encapsulates DB operations for logs and ticket mappings.
 */
class Futurecrmagent_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Insert a classification log row.
     */
    public function insert_log(array $log): void
    {
        $table = db_prefix() . 'futurecrmagent_ticket_logs';
        $insert = [
            'created_at'    => date('Y-m-d H:i:s'),
            'source'        => $log['source'] ?? null,
            'email_from'    => $log['email_from'] ?? null,
            'subject'       => $log['subject'] ?? null,
            'preview'       => $log['preview'] ?? null,
            'classification'=> $log['classification'] ?? null,
            'score'         => $log['score'] ?? null,
            'ticket_id'     => $log['ticket_id'] ?? null,
            'raw'           => isset($log['raw']) ? (is_string($log['raw']) ? $log['raw'] : json_encode($log['raw'])) : null,
            'error'         => $log['error'] ?? null,
        ];
        $this->db->insert($table, $insert);
    }

    /**
     * Get latest history items (latest per ticket + recent unlinked), newest first.
     */
    public function get_history_latest(int $limit = 50): array
    {
        $table = db_prefix() . 'futurecrmagent_ticket_logs';
        $sql = "SELECT * FROM ("
             . " SELECT l.* FROM {$table} l"
             . " INNER JOIN (SELECT MAX(id) AS id FROM {$table} WHERE ticket_id IS NOT NULL GROUP BY ticket_id) t ON t.id = l.id"
             . " UNION ALL"
             . " SELECT l.* FROM {$table} l WHERE l.ticket_id IS NULL"
             . ") x ORDER BY x.id DESC LIMIT " . (int) $limit;
        return $this->db->query($sql)->result();
    }

    /**
     * Link the most recent unmatched log to a ticket and apply mapping.
     */
    public function link_latest_log_to_ticket(int $ticketId): void
    {
        $this->db->where('ticketid', $ticketId);
        $t = $this->db->get(db_prefix() . 'tickets')->row();
        if (!$t) { return; }

        $from    = (string) ($t->email ?? '');
        $subject = (string) ($t->subject ?? '');

        $table = db_prefix() . 'futurecrmagent_ticket_logs';
        $this->db->where('ticket_id IS NULL', null, false);
        $this->db->where('email_from', $from);
        $this->db->where('subject', $subject);
        $this->db->where('created_at >=', date('Y-m-d H:i:s', time() - 600));
        $this->db->order_by('id', 'DESC');
        $this->db->limit(1);
        $row = $this->db->get($table)->row();
        if ($row) {
            $this->db->where('id', $row->id);
            $this->db->update($table, ['ticket_id' => $ticketId]);
            log_message('error', '[FUTURECRMAGENT] link_latest_log_to_ticket id=' . $ticketId . ' log_id=' . $row->id);

            $cat = null; $pri = null;
            if (!empty($row->classification) && strpos($row->classification, '/') !== false) {
                $parts = array_map('trim', explode('/', $row->classification, 2));
                $cat = $parts[0] ?? null;
                $pri = $parts[1] ?? null;
            }
            if ($cat || $pri) {
                $this->apply_mapping($ticketId, $cat, $pri);
            }
        } else {
            log_message('error', '[FUTURECRMAGENT] link_latest_log_to_ticket no log found for id=' . $ticketId);
        }
    }

    /**
     * Apply department/priority (and status) mapping to a ticket
     */
    public function apply_mapping(int $ticketId, $category, $priority): void
    {
        $normCat = function_exists('futurecrmagent_normalize_category') ? futurecrmagent_normalize_category($category) : $category;
        $normPri = function_exists('futurecrmagent_normalize_priority') ? futurecrmagent_normalize_priority($priority) : $priority;

        $updates = [];
        $deptId = 0; $statusId = 0; $priId = 0;
        if ($normCat) {
            $optKey = null;
            switch ($normCat) {
                case 'Technical Issue': $optKey = 'futurecrmagent_map_dept_technical_issue'; break;
                case 'Billing': $optKey = 'futurecrmagent_map_dept_billing'; break;
                case 'Sales': $optKey = 'futurecrmagent_map_dept_sales'; break;
                case 'Account': $optKey = 'futurecrmagent_map_dept_account'; break;
                case 'Feedback': $optKey = 'futurecrmagent_map_dept_feedback'; break;
                case 'Other': $optKey = 'futurecrmagent_map_dept_other'; break;
            }
            $deptId = $optKey ? (int) get_option($optKey) : 0;
            if ($deptId > 0) { $updates['department'] = $deptId; }

            $statusOpt = null;
            switch ($normCat) {
                case 'Technical Issue': $statusOpt = 'futurecrmagent_map_status_technical_issue'; break;
                case 'Billing': $statusOpt = 'futurecrmagent_map_status_billing'; break;
                case 'Sales': $statusOpt = 'futurecrmagent_map_status_sales'; break;
                case 'Account': $statusOpt = 'futurecrmagent_map_status_account'; break;
                case 'Feedback': $statusOpt = 'futurecrmagent_map_status_feedback'; break;
                case 'Other': $statusOpt = 'futurecrmagent_map_status_other'; break;
            }
            $statusId = $statusOpt ? (int) get_option($statusOpt) : 0;
            if ($statusId > 0) { $updates['status'] = $statusId; }
        }

        if ($normPri) {
            $optKey = null;
            switch ($normPri) {
                case 'Low': $optKey = 'futurecrmagent_map_pri_low'; break;
                case 'Medium': $optKey = 'futurecrmagent_map_pri_medium'; break;
                case 'High': $optKey = 'futurecrmagent_map_pri_high'; break;
                case 'Urgent': $optKey = 'futurecrmagent_map_pri_urgent'; break;
            }
            $priId = $optKey ? (int) get_option($optKey) : 0;
            if ($priId > 0) { $updates['priority'] = $priId; }
        }

        log_message('error', '[FUTURECRMAGENT] apply_mapping id=' . $ticketId . ' cat=' . $normCat . ' pri=' . $normPri . ' deptId=' . $deptId . ' priId=' . $priId . ' statusId=' . $statusId);

        if (!empty($updates)) {
            $this->db->where('ticketid', $ticketId);
            $this->db->update(db_prefix() . 'tickets', $updates);
            log_message('error', '[FUTURECRMAGENT] apply_mapping updated rows=' . $this->db->affected_rows());
        } else {
            log_message('error', '[FUTURECRMAGENT] apply_mapping no updates for id=' . $ticketId);
        }
    }

    public function build_sales_doc_harness(string $docType, int $draftId, string $message): array
    {
        $docType = $this->normalize_doc_type($docType);
        if ($docType === '') {
            throw new InvalidArgumentException('Unsupported sales document type.');
        }

        return [
            'task' => 'Create or update a Perfex CRM sales document draft from the user instruction. Return JSON only.',
            'doc_type' => $docType,
            'draft_id' => $draftId,
            'user_instruction' => $message,
            'draft' => $this->get_sales_doc_summary($docType, $draftId),
            'schema' => $this->sales_doc_schema($docType),
            'context' => [
                'customers' => $this->search_customers($message),
                'projects' => $this->search_projects($message),
                'items' => $this->search_items($message),
                'taxes' => $this->list_taxes(),
                'currencies' => $this->list_currencies(),
                'staff' => $this->list_staff(),
            ],
            'rules' => [
                'Use only ids present in context when setting customer, project, currency, tax, or staff.',
                'Do not send, approve, or mark the document as accepted/paid.',
                'Items must include description, qty, rate, unit, long_description, and taxname.',
                'Use document-level discount_percent, discount_total, and discount_type for discounts; do not invent unsupported item-level discount fields.',
                'Totals are calculated by Perfex CRM; do not invent authoritative totals.',
            ],
        ];
    }

    public function normalize_sales_doc_plan(string $docType, array $plan): array
    {
        $docType = $this->normalize_doc_type($docType ?: (string) ($plan['doc_type'] ?? ''));
        $header = is_array($plan['header'] ?? null) ? $plan['header'] : [];
        $items = is_array($plan['items'] ?? null) ? $plan['items'] : [];

        $normalizedItems = [];
        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $taxNames = $item['taxname'] ?? [];
            if (is_string($taxNames) && $taxNames !== '') {
                $taxNames = [$taxNames];
            }
            if (!is_array($taxNames)) {
                $taxNames = [];
            }

            $normalizedItems[] = [
                'description' => trim((string) ($item['description'] ?? '')),
                'long_description' => trim((string) ($item['long_description'] ?? '')),
                'qty' => max(0, (float) ($item['qty'] ?? 1)),
                'unit' => trim((string) ($item['unit'] ?? '')),
                'rate' => max(0, (float) ($item['rate'] ?? 0)),
                'taxname' => array_values(array_filter(array_map('strval', $taxNames))),
                'order' => $index + 1,
            ];
        }

        return [
            'doc_type' => $docType,
            'header' => $this->normalize_sales_doc_header($docType, $header),
            'items' => $normalizedItems,
            'missing_fields' => array_values(array_filter((array) ($plan['missing_fields'] ?? []))),
            'warnings' => array_values(array_filter((array) ($plan['warnings'] ?? []))),
            'confidence' => isset($plan['confidence']) ? (float) $plan['confidence'] : null,
        ];
    }

    public function validate_sales_doc_plan(string $docType, int $draftId, array $plan, bool $requireDraft = true): array
    {
        $errors = [];
        $docType = $this->normalize_doc_type($docType);
        if ($docType === '') {
            $errors[] = 'Unsupported sales document type.';
        }
        if ($requireDraft && ($draftId <= 0 || !$this->sales_doc_is_draft($docType, $draftId))) {
            $errors[] = 'Target document must be an editable draft.';
        }
        if (empty($plan['items'])) {
            $errors[] = 'At least one item is required.';
        }

        foreach ($plan['items'] ?? [] as $index => $item) {
            if (($item['description'] ?? '') === '') {
                $errors[] = 'Item ' . ($index + 1) . ' description is required.';
            }
            if (($item['qty'] ?? 0) <= 0) {
                $errors[] = 'Item ' . ($index + 1) . ' quantity must be greater than zero.';
            }
            if (($item['rate'] ?? 0) < 0) {
                $errors[] = 'Item ' . ($index + 1) . ' rate cannot be negative.';
            }
            foreach ($item['taxname'] ?? [] as $taxName) {
                if (!$this->tax_name_exists($taxName)) {
                    $errors[] = 'Unknown tax "' . $taxName . '" on item ' . ($index + 1) . '.';
                }
            }
        }

        $header = $plan['header'] ?? [];
        if (!empty($header['clientid']) && !$this->row_exists('clients', 'userid', (int) $header['clientid'])) {
            $errors[] = 'Customer does not exist.';
        }
        if (!empty($header['project_id']) && !$this->row_exists('projects', 'id', (int) $header['project_id'])) {
            $errors[] = 'Project does not exist.';
        }
        if (!empty($header['currency']) && !$this->row_exists('currencies', 'id', (int) $header['currency'])) {
            $errors[] = 'Currency does not exist.';
        }
        if (isset($header['discount_percent']) && $header['discount_percent'] > 100) {
            $errors[] = 'Discount percent cannot be greater than 100.';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    public function create_sales_doc_from_plan(string $docType, array $plan, string $targetStatus = 'draft'): array
    {
        $docType = $this->normalize_doc_type($docType);
        if ($docType === '') {
            throw new InvalidArgumentException('Unsupported sales document type.');
        }

        $this->load_sales_doc_model($docType);
        $model = $this->sales_doc_model_name($docType);
        $context = [];
        $header = $plan['header'] ?? [];
        if (!empty($header['clientid'])) {
            $context['customer_id'] = (int) $header['clientid'];
        }
        if (!empty($header['project_id'])) {
            $context['project_id'] = (int) $header['project_id'];
        }

        $id = (int) $this->{$model}->create_empty_draft($context);
        if ($id <= 0) {
            throw new RuntimeException('Could not create sales document draft.');
        }

        $this->apply_sales_doc_plan($docType, $id, $plan, $targetStatus);

        return [
            'id' => $id,
            'edit_url' => $this->sales_doc_edit_url($docType, $id),
        ];
    }

    public function apply_sales_doc_plan(string $docType, int $id, array $plan, string $targetStatus = 'draft'): bool
    {
        $docType = $this->normalize_doc_type($docType);
        if ($docType === '' || $id <= 0) {
            throw new InvalidArgumentException('Invalid sales document target.');
        }

        $row = $this->get_sales_doc_summary($docType, $id);
        if (!$row) {
            throw new RuntimeException('Target sales document was not found.');
        }
        if (!$this->sales_doc_is_draft($docType, $id)) {
            throw new RuntimeException('Only editable drafts can be updated by FutureCRM Agent.');
        }

        $targetStatus = $targetStatus === 'open' ? 'open' : 'draft';
        $this->load_sales_doc_model($docType);
        $model = $this->sales_doc_model_name($docType);

        if ($docType === 'invoice' && $targetStatus === 'open' && (int) ($row['status'] ?? 0) === $this->sales_doc_draft_status($docType)) {
            $this->{$model}->change_invoice_number_when_status_draft($id);
            $row = $this->get_sales_doc_summary($docType, $id);
        }

        $payload = $this->sales_doc_update_payload($docType, $id, $row, $plan, $targetStatus);
        $result = $this->{$model}->update($payload, $id);

        return (bool) $result;
    }

    public function log_sales_doc_run(string $docType, int $draftId, string $sourceText, array $request, array $response, ?array $payload, string $status, ?string $error): void
    {
        $table = db_prefix() . 'futurecrmagent_sales_doc_runs';
        if (!$this->db->table_exists($table)) {
            return;
        }

        $this->db->insert($table, [
            'created_at' => date('Y-m-d H:i:s'),
            'staffid' => get_staff_user_id(),
            'doc_type' => $this->normalize_doc_type($docType),
            'doc_id' => $draftId > 0 ? $draftId : null,
            'source_text' => $sourceText,
            'llm_request_json' => $request ? json_encode($request) : null,
            'llm_response_json' => $response ? json_encode($response) : null,
            'normalized_payload_json' => $payload ? json_encode($payload) : null,
            'status' => $status,
            'error' => $error,
        ]);
    }

    public function log_tool_run(array $row): void
    {
        $table = db_prefix() . 'futurecrmagent_tool_runs';
        if (!$this->db->table_exists($table)) {
            return;
        }

        $this->db->insert($table, [
            'created_at' => date('Y-m-d H:i:s'),
            'staffid' => get_staff_user_id(),
            'session_id' => (string) ($row['session_id'] ?? ''),
            'context_type' => (string) ($row['context_type'] ?? ''),
            'context_id' => (int) ($row['context_id'] ?? 0),
            'tool_name' => (string) ($row['tool_name'] ?? ''),
            'args_json' => isset($row['args']) ? json_encode($row['args']) : null,
            'result_summary_json' => isset($row['result_summary']) ? json_encode($row['result_summary']) : null,
            'status' => (string) ($row['status'] ?? 'ok'),
            'duration_ms' => (int) ($row['duration_ms'] ?? 0),
            'error' => $row['error'] ?? null,
        ]);
    }

    public function tool_context(string $docType, int $docId, string $message = ''): array
    {
        $docType = $this->normalize_doc_type($docType);
        $document = $docType !== '' && $docId > 0 ? $this->get_sales_doc_summary($docType, $docId) : [];
        $clientId = $document ? $this->preview_client_id($docType, $document) : 0;

        return [
            'doc_type' => $docType,
            'doc_id' => $docId,
            'message' => $message,
            'customer_id' => $clientId,
            'staffid' => get_staff_user_id(),
            'permission_snapshot' => $docType !== '' ? $this->preview_permission_snapshot($docType) : [],
            'document' => $document,
        ];
    }

    public function tool_trace_summary(array $trace): array
    {
        $summary = [];
        foreach ($trace as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $result = is_array($entry['result'] ?? null) ? $entry['result'] : [];
            $summary[] = [
                'tool' => (string) ($entry['tool'] ?? ''),
                'mode' => (string) ($entry['mode'] ?? 'read'),
                'duration_ms' => (int) ($entry['duration_ms'] ?? 0),
                'summary' => $this->summarize_tool_result($result),
            ];
        }

        return $summary;
    }

    public function build_preview_harness(string $docType, int $docId, string $message, string $mode): array
    {
        $docType = $this->normalize_doc_type($docType);
        if ($docType === '' || $docId <= 0) {
            throw new InvalidArgumentException('Unsupported sales document preview target.');
        }

        $document = $this->get_sales_doc_summary($docType, $docId);
        if (!$document) {
            throw new RuntimeException('Sales document was not found.');
        }

        $clientId = $this->preview_client_id($docType, $document);
        $projectId = (int) ($document['project_id'] ?? 0);

        return [
            'task' => 'Assist on an existing Perfex CRM sales document preview. Read-only answers can be returned directly. Write actions must be converted into approval-gated action plans.',
            'mode' => in_array($mode, ['qa', 'summary', 'action'], true) ? $mode : 'qa',
            'doc_type' => $docType,
            'doc_id' => $docId,
            'user_message' => $message,
            'document' => [
                'header' => $document,
                'items' => get_items_by_type($docType, $docId),
                'totals' => $this->preview_totals($document),
                'preview_url' => $this->sales_doc_preview_url($docType, $docId),
            ],
            'customer' => $clientId > 0 ? $this->db->where('userid', $clientId)->get(db_prefix() . 'clients')->row_array() : null,
            'project' => $projectId > 0 ? $this->db->where('id', $projectId)->get(db_prefix() . 'projects')->row_array() : null,
            'related_context' => [
                'notes' => $this->preview_related_rows('notes', $docType, $docId),
                'tasks' => $this->preview_related_rows('tasks', $docType, $docId),
                'reminders' => $this->preview_related_rows('reminders', $docType, $docId),
                'activity' => $this->preview_activity_rows($docType, $docId),
            ],
            'permission_snapshot' => $this->preview_permission_snapshot($docType),
            'action_catalog' => $this->preview_action_catalog($docType),
            'rules' => [
                'Do not call arbitrary PHP models, shell commands, external automations, or dynamic executors.',
                'For read-only questions, answer with citations/context_used from the harness.',
                'For any action that writes CRM data, return an action_plan using one action_key from action_catalog.',
                'All write actions require human approval and must not be described as already executed.',
                'Invoice payment, refund, and credit actions are out of scope for v1.',
            ],
        ];
    }

    public function normalize_tool_preview(string $docType, int $docId, string $message, string $mode, array $trace, array $catalog): array
    {
        $docType = $this->normalize_doc_type($docType);
        $document = $this->get_sales_doc_summary($docType, $docId);
        $items = $docType !== '' && $docId > 0 ? get_items_by_type($docType, $docId) : [];
        $label = [
            'estimate' => 'báo giá',
            'invoice' => 'hóa đơn',
            'proposal' => 'đề xuất',
        ][$docType] ?? 'phiếu';

        $answer = '';
        $missing = [];
        $risks = [];
        $actionPlan = [];
        $hasMissingTool = false;
        foreach ($trace as $entry) {
            $tool = (string) ($entry['tool'] ?? '');
            $result = is_array($entry['result'] ?? null) ? $entry['result'] : [];
            if ($tool === 'sales_doc.get_summary') {
                $number = (string) ($document['number'] ?? $docId);
                $total = isset($document['total']) ? app_format_money((float) $document['total'], $this->preview_currency($document)) : '-';
                $status = $this->preview_status_label($docType, (int) ($document['status'] ?? 0));
                $answer = 'Đây là ' . $label . ' #' . $number . ' trong Perfex CRM.';
                $answer .= "\n- ID: " . $docId;
                $answer .= "\n- Trạng thái: " . $status;
                if (!empty($document['date'])) {
                    $answer .= "\n- Ngày: " . (string) $document['date'];
                }
                $answer .= "\n- Số dòng sản phẩm/dịch vụ: " . count($items);
                $answer .= "\n- Tổng tiền hiện tại: " . $total;
                $itemLines = $this->preview_item_lines($items, $document);
                if ($itemLines) {
                    $answer .= "\n\nCác dòng chính:";
                    foreach ($itemLines as $line) {
                        $answer .= "\n- " . $line;
                    }
                }
            } elseif ($tool === 'sales_doc.explain_totals') {
                $answer .= "\n\nDiễn giải tổng tiền: " . (string) ($result['explanation'] ?? '');
            } elseif ($tool === 'sales_doc.find_missing_fields') {
                $hasMissingTool = true;
                $missing = array_merge($missing, (array) ($result['missing_fields'] ?? []));
                $risks = array_merge($risks, (array) ($result['warnings'] ?? []));
            } elseif ($tool === 'sales_doc.list_related_records') {
                $answer .= "\n\nNgữ cảnh liên quan: " . count($result['notes'] ?? []) . ' notes, ' . count($result['tasks'] ?? []) . ' tasks, ' . count($result['reminders'] ?? []) . ' reminders.';
            } elseif ($tool === 'crm.customer.get_history') {
                $answer .= "\n\nLịch sử khách hàng: " . count($result['estimates'] ?? []) . ' estimates, ' . count($result['proposals'] ?? []) . ' proposals, ' . count($result['invoices'] ?? []) . ' invoices gần nhất.';
            } elseif (!empty($result['action_plan'])) {
                $actionPlan = $result['action_plan'];
                $answer .= "\n\nTôi đã chuẩn bị action plan để gửi phê duyệt. Không có dữ liệu CRM nào được ghi ngay.";
            } elseif ($tool === 'crm.reference.lookup') {
                $answer = 'Các tool đang được phép trong ngữ cảnh này:';
                foreach ($catalog['tools'] ?? [] as $toolName => $toolData) {
                    $answer .= "\n- " . $toolName . ': ' . (string) ($toolData['description'] ?? '');
                }
            }
        }

        if ($answer === '') {
            $answer = 'Đã đọc ngữ cảnh Perfex CRM hiện tại, nhưng chưa có kết quả phù hợp để hiển thị.';
        }
        if ($hasMissingTool) {
            $answer .= "\n\n" . $this->preview_missing_summary($missing, $risks);
        }

        return [
            'doc_type' => $docType,
            'answer' => $answer,
            'citations' => ['Perfex ' . $docType . ' #' . $docId, 'tool catalog', 'items table'],
            'context_used' => ['document header', 'document items', 'document totals', 'tool trace'],
            'risks' => array_values(array_filter($risks)),
            'missing_info' => array_values(array_filter($missing)),
            'tool_trace' => $this->tool_trace_summary($trace),
            'analysis_summary' => 'Đã dùng tool catalog nội bộ để phân tích ngữ cảnh; không hiển thị raw reasoning.',
            'action_plan' => $actionPlan ?: [
                'action_key' => '',
                'title' => '',
                'description' => '',
                'payload' => [],
                'requires_approval' => true,
            ],
        ];
    }

    public function preview_action_catalog(string $docType = ''): array
    {
        $docType = $this->normalize_doc_type($docType);
        $catalog = [
            'sales_doc.summarize' => [
                'label' => 'Summarize document',
                'write' => false,
                'requires_approval' => false,
            ],
            'sales_doc.explain_totals' => [
                'label' => 'Explain totals',
                'write' => false,
                'requires_approval' => false,
            ],
            'sales_doc.find_missing_fields' => [
                'label' => 'Find missing fields',
                'write' => false,
                'requires_approval' => false,
            ],
            'sales_doc.list_related_records' => [
                'label' => 'List related notes, tasks, reminders, and activity',
                'write' => false,
                'requires_approval' => false,
            ],
            'sales_doc.draft_email' => [
                'label' => 'Draft customer email text',
                'write' => false,
                'requires_approval' => false,
            ],
            'sales_doc.note.add' => [
                'label' => 'Add internal note',
                'write' => true,
                'requires_approval' => true,
                'required_permission' => 'edit',
                'payload_schema' => ['note' => 'string'],
            ],
            'sales_doc.task.create' => [
                'label' => 'Create related task',
                'write' => true,
                'requires_approval' => true,
                'required_permission' => 'create_task',
                'payload_schema' => ['name' => 'string', 'description' => 'string', 'duedate' => 'YYYY-MM-DD'],
            ],
            'sales_doc.email.prepare_send' => [
                'label' => 'Prepare customer email',
                'write' => true,
                'requires_approval' => true,
                'required_permission' => 'edit',
                'payload_schema' => ['subject' => 'string', 'body' => 'string'],
            ],
            'sales_doc.status.update' => [
                'label' => 'Update document status',
                'write' => true,
                'requires_approval' => true,
                'required_permission' => 'edit',
                'payload_schema' => ['status' => 'int|string', 'reason' => 'string'],
            ],
            'sales_doc.attachment.summary' => [
                'label' => 'Attach generated summary',
                'write' => true,
                'requires_approval' => true,
                'required_permission' => 'edit',
                'payload_schema' => ['summary' => 'string', 'filename' => 'string'],
            ],
        ];

        if ($docType === 'estimate') {
            $catalog['estimate.convert.project'] = [
                'label' => 'Convert estimate to project',
                'write' => true,
                'requires_approval' => true,
                'required_permission' => 'create_project',
                'payload_schema' => ['project_name' => 'string', 'reason' => 'string'],
            ];
            $catalog['estimate.convert.invoice'] = [
                'label' => 'Convert estimate to invoice',
                'write' => true,
                'requires_approval' => true,
                'required_permission' => 'create_invoice',
                'payload_schema' => ['reason' => 'string'],
            ];
        }

        return $catalog;
    }

    public function normalize_preview_response(string $docType, array $response): array
    {
        $actionPlan = is_array($response['action_plan'] ?? null) ? $response['action_plan'] : [];
        $payload = is_array($actionPlan['payload'] ?? null) ? $actionPlan['payload'] : [];

        return [
            'doc_type' => $this->normalize_doc_type($docType),
            'answer' => trim((string) ($response['answer'] ?? '')),
            'citations' => $this->normalize_preview_list($response['citations'] ?? []),
            'context_used' => $this->normalize_preview_list($response['context_used'] ?? []),
            'risks' => $this->normalize_preview_list($response['risks'] ?? []),
            'missing_info' => $this->normalize_preview_list($response['missing_info'] ?? []),
            'action_plan' => [
                'action_key' => trim((string) ($actionPlan['action_key'] ?? '')),
                'title' => trim((string) ($actionPlan['title'] ?? '')),
                'description' => trim((string) ($actionPlan['description'] ?? '')),
                'payload' => $payload,
                'requires_approval' => true,
            ],
        ];
    }

    public function fallback_preview_answer(string $docType, int $docId, string $message, string $mode, string $reason = ''): array
    {
        $docType = $this->normalize_doc_type($docType);
        $document = $this->get_sales_doc_summary($docType, $docId);
        $items = $docType !== '' && $docId > 0 ? get_items_by_type($docType, $docId) : [];
        $label = [
            'estimate' => 'báo giá',
            'invoice' => 'hóa đơn',
            'proposal' => 'đề xuất',
        ][$docType] ?? 'phiếu';

        $number = (string) ($document['number'] ?? $docId);
        $status = (string) ($document['status'] ?? '-');
        $total = isset($document['total']) ? app_format_money((float) $document['total'], $this->preview_currency($document)) : '-';
        $date = (string) ($document['date'] ?? '');
        $itemCount = count($items);

        $answer = 'Đây là ' . $label . ' #' . $number . ' trong Perfex CRM.';
        $answer .= "\n- ID: " . $docId;
        $answer .= "\n- Trạng thái: " . $status;
        if ($date !== '') {
            $answer .= "\n- Ngày: " . $date;
        }
        $answer .= "\n- Số dòng sản phẩm/dịch vụ: " . $itemCount;
        $answer .= "\n- Tổng tiền hiện tại: " . $total;
        if ($mode === 'summary' && $items) {
            $answer .= "\n\nCác dòng chính:";
            foreach (array_slice($items, 0, 5) as $item) {
                $answer .= "\n- " . (string) ($item['description'] ?? '-') . ' x ' . (string) ($item['qty'] ?? '1') . ' @ ' . (string) ($item['rate'] ?? '0');
            }
        }

        return [
            'doc_type' => $docType,
            'answer' => $answer,
            'citations' => ['Perfex ' . $docType . ' #' . $docId, 'items table'],
            'context_used' => ['document header', 'document items', 'document totals'],
            'risks' => $reason !== '' ? ['LLM proxy unavailable: ' . $reason] : [],
            'missing_info' => [],
            'action_plan' => [
                'action_key' => '',
                'title' => '',
                'description' => '',
                'payload' => [],
                'requires_approval' => true,
            ],
        ];
    }

    public function validate_preview_action_plan(string $docType, int $docId, array $plan): array
    {
        $errors = [];
        $docType = $this->normalize_doc_type($docType);
        $document = $docType !== '' && $docId > 0 ? $this->get_sales_doc_summary($docType, $docId) : [];
        if ($docType === '' || !$document) {
            $errors[] = 'Sales document was not found.';
        }

        $catalog = $this->preview_action_catalog($docType);
        $actionKey = trim((string) ($plan['action_key'] ?? ''));
        if ($actionKey === '' || empty($catalog[$actionKey])) {
            $errors[] = 'Action is not in the approved FutureCRM Agent catalog.';
        } elseif (empty($catalog[$actionKey]['write']) || empty($catalog[$actionKey]['requires_approval'])) {
            $errors[] = 'Only approval-gated write actions can be submitted from preview.';
        } else {
            $permissionError = $this->preview_permission_error($docType, (string) ($catalog[$actionKey]['required_permission'] ?? 'edit'));
            if ($permissionError !== '') {
                $errors[] = $permissionError;
            }
        }

        if (strpos($actionKey, 'payment') !== false || strpos($actionKey, 'refund') !== false || strpos($actionKey, 'credit') !== false) {
            $errors[] = 'Invoice payment, refund, and credit actions are not available in v1.';
        }

        $payload = is_array($plan['payload'] ?? null) ? $plan['payload'] : [];
        if (!$payload) {
            $errors[] = 'Action payload is required.';
        }

        if ($actionKey === 'sales_doc.note.add' && trim((string) ($payload['note'] ?? '')) === '') {
            $errors[] = 'Internal note content is required.';
        }
        if ($actionKey === 'sales_doc.task.create' && trim((string) ($payload['name'] ?? '')) === '') {
            $errors[] = 'Task name is required.';
        }
        if ($actionKey === 'sales_doc.email.prepare_send' && (trim((string) ($payload['subject'] ?? '')) === '' || trim((string) ($payload['body'] ?? '')) === '')) {
            $errors[] = 'Email subject and body are required.';
        }
        if ($actionKey === 'sales_doc.status.update' && !isset($payload['status'])) {
            $errors[] = 'Target status is required.';
        }
        if ($actionKey === 'sales_doc.attachment.summary' && trim((string) ($payload['summary'] ?? '')) === '') {
            $errors[] = 'Summary content is required.';
        }
        if ($actionKey === 'estimate.convert.project' && $docType !== 'estimate') {
            $errors[] = 'Only estimates can be converted to projects.';
        }
        if ($actionKey === 'estimate.convert.invoice' && $docType !== 'estimate') {
            $errors[] = 'Only estimates can be converted to invoices.';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'approval_required' => true,
        ];
    }

    public function create_preview_action_approval(string $docType, int $docId, array $plan, string $message): array
    {
        $table = db_prefix() . 'futureagent_approvals';
        if (!$this->db->table_exists($table)) {
            throw new RuntimeException('FutureAgent approvals table is not available.');
        }

        $now = date('Y-m-d H:i:s');
        $id = $this->uuid();
        $payload = [
            'source_module' => 'futurecrmagent',
            'source' => 'sales_doc_preview',
            'doc_type' => $this->normalize_doc_type($docType),
            'doc_id' => $docId,
            'message' => $message,
            'action' => [
                'action' => (string) ($plan['action_key'] ?? 'futurecrmagent.preview_action'),
                'action_key' => (string) ($plan['action_key'] ?? ''),
                'relation_type' => $this->normalize_doc_type($docType),
                'relation_id' => $docId,
                'reason' => (string) ($plan['description'] ?? $message),
                'payload' => is_array($plan['payload'] ?? null) ? $plan['payload'] : [],
                'approval_only' => true,
            ],
            'action_plan' => $plan,
            'requested_by_staffid' => get_staff_user_id(),
            'approval_only' => true,
        ];

        $row = [
            'id' => $id,
            'company_id' => '00000000-0000-0000-0000-000000000000',
            'issue_id' => null,
            'ticketid' => null,
            'perfex_task_id' => null,
            'type' => 'futurecrmagent.preview_action',
            'status' => 'pending',
            'requested_by_agent_id' => null,
            'requested_by_staffid' => get_staff_user_id(),
            'payload_json' => json_encode($payload),
            'created_at' => $now,
            'updated_at' => $now,
        ];

        foreach (array_keys($row) as $field) {
            if (!$this->db->field_exists($field, $table)) {
                unset($row[$field]);
            }
        }

        $this->db->insert($table, $row);

        return [
            'id' => $id,
            'status' => 'pending',
            'type' => 'futurecrmagent.preview_action',
        ];
    }

    private function normalize_doc_type(string $docType): string
    {
        $docType = strtolower(trim($docType));
        return in_array($docType, ['estimate', 'proposal', 'invoice'], true) ? $docType : '';
    }

    private function preview_status_label(string $docType, int $status): string
    {
        if ($docType === 'proposal' && function_exists('format_proposal_status')) {
            return trim(strip_tags(format_proposal_status($status, '', false)));
        }
        if ($docType === 'estimate' && function_exists('format_estimate_status')) {
            return trim(strip_tags(format_estimate_status($status, '', false)));
        }
        if ($docType === 'invoice' && function_exists('format_invoice_status')) {
            return trim(strip_tags(format_invoice_status($status, '', false)));
        }

        return $status > 0 ? ('Status #' . $status) : '-';
    }

    private function preview_item_lines(array $items, array $document): array
    {
        $currency = $this->preview_currency($document);
        $lines = [];
        foreach (array_slice($items, 0, 8) as $item) {
            $description = trim((string) ($item['description'] ?? ''));
            if ($description === '') {
                $description = 'Dòng dịch vụ';
            }
            $qty = (float) ($item['qty'] ?? 0);
            $rate = (float) ($item['rate'] ?? 0);
            $lineTotal = $qty * $rate;
            $unit = trim((string) ($item['unit'] ?? ''));
            $qtyLabel = rtrim(rtrim(number_format($qty, 2, '.', ''), '0'), '.');
            $line = $description . ' x ' . ($qtyLabel !== '' ? $qtyLabel : '1');
            if ($unit !== '') {
                $line .= ' ' . $unit;
            }
            $line .= ' = ' . app_format_money($lineTotal, $currency);
            $lines[] = $line;
        }

        if (count($items) > 8) {
            $lines[] = 'Còn ' . (count($items) - 8) . ' dòng khác trong bảng items.';
        }

        return $lines;
    }

    private function preview_missing_summary(array $missing, array $risks): string
    {
        $missing = array_values(array_filter($missing));
        $risks = array_values(array_filter($risks));
        if (!$missing && !$risks) {
            return 'Không phát hiện thiếu trường bắt buộc hoặc cảnh báo nghiệp vụ rõ ràng theo dữ liệu hiện có.';
        }

        $text = 'Các điểm cần chú ý trước khi gửi khách:';
        foreach ($missing as $field) {
            $text .= "\n- Thiếu: " . (string) $field;
        }
        foreach ($risks as $risk) {
            $text .= "\n- Cảnh báo: " . (string) $risk;
        }

        return $text;
    }

    private function normalize_preview_list($values): array
    {
        if (!is_array($values)) {
            $values = [$values];
        }

        $normalized = [];
        foreach ($values as $value) {
            $label = $this->preview_scalar_label($value);
            if ($label !== '') {
                $normalized[] = $label;
            }
        }

        return array_values(array_unique($normalized));
    }

    private function preview_scalar_label($value): string
    {
        if (is_bool($value) || $value === null) {
            return '';
        }
        if (is_scalar($value)) {
            return trim((string) $value);
        }
        if (is_array($value)) {
            foreach (['label', 'title', 'source', 'name', 'type', 'table', 'field'] as $key) {
                if (isset($value[$key]) && is_scalar($value[$key])) {
                    return trim((string) $value[$key]);
                }
            }
            if (isset($value['doc_type'], $value['doc_id'])) {
                return trim((string) $value['doc_type']) . ' #' . trim((string) $value['doc_id']);
            }
        }

        return '';
    }

    private function summarize_tool_result(array $result): array
    {
        $summary = [];
        foreach ($result as $key => $value) {
            if (is_array($value)) {
                $summary[$key] = count($value);
            } elseif (is_scalar($value) || $value === null) {
                $summary[$key] = mb_substr((string) $value, 0, 160);
            }
        }

        return $summary;
    }

    private function normalize_sales_doc_header(string $docType, array $header): array
    {
        $allowed = [
            'clientid',
            'project_id',
            'currency',
            'sale_agent',
            'date',
            'expirydate',
            'duedate',
            'open_till',
            'reference_no',
            'subject',
            'discount_type',
            'discount_percent',
            'discount_total',
            'adminnote',
            'clientnote',
            'terms',
            'content',
        ];
        $out = [];
        foreach ($allowed as $key) {
            if (!array_key_exists($key, $header)) {
                continue;
            }
            $out[$key] = is_scalar($header[$key]) ? trim((string) $header[$key]) : '';
        }

        foreach (['clientid', 'project_id', 'currency', 'sale_agent'] as $intKey) {
            if (isset($out[$intKey]) && $out[$intKey] !== '') {
                $out[$intKey] = (int) $out[$intKey];
            }
        }

        if (isset($out['discount_type']) && !in_array($out['discount_type'], ['before_tax', 'after_tax', ''], true)) {
            unset($out['discount_type']);
        }

        foreach (['discount_percent', 'discount_total'] as $moneyKey) {
            if (isset($out[$moneyKey]) && $out[$moneyKey] !== '') {
                $out[$moneyKey] = max(0, (float) $out[$moneyKey]);
            }
        }

        if ($docType === 'proposal' && isset($out['clientid']) && empty($out['rel_type'])) {
            $out['rel_type'] = 'customer';
            $out['rel_id'] = (int) $out['clientid'];
        }

        return $out;
    }

    private function sales_doc_schema(string $docType): array
    {
        return [
            'doc_type' => $docType,
            'header' => [
                'clientid' => 'Perfex customer userid. For proposals this maps to rel_type=customer and rel_id.',
                'project_id' => 'Perfex project id when known.',
                'currency' => 'Perfex currency id.',
                'sale_agent' => 'Perfex staff id.',
                'date' => 'YYYY-MM-DD.',
                'expirydate' => 'Estimate expiry date.',
                'duedate' => 'Invoice due date.',
                'open_till' => 'Proposal open till date.',
                'subject' => 'Proposal subject.',
                'discount_type' => 'Optional Perfex document-level discount type: before_tax or after_tax.',
                'discount_percent' => 'Optional document-level percent discount, 0 to 100.',
                'discount_total' => 'Optional document-level fixed discount amount.',
                'adminnote' => 'Internal note.',
                'clientnote' => 'Customer-visible note.',
                'terms' => 'Terms and conditions.',
                'content' => 'Proposal content/body.',
            ],
            'items' => [[
                'description' => 'Short line item title.',
                'long_description' => 'Detailed line item description.',
                'qty' => 'Numeric quantity.',
                'unit' => 'Unit label.',
                'rate' => 'Unit price before totals.',
                'taxname' => ['Tax name formatted as Name|Rate from context only.'],
            ]],
        ];
    }

    private function get_sales_doc_summary(string $docType, int $id): array
    {
        $tables = [
            'estimate' => 'estimates',
            'proposal' => 'proposals',
            'invoice' => 'invoices',
        ];
        if (empty($tables[$docType]) || $id <= 0) {
            return [];
        }

        $row = $this->db->where('id', $id)->get(db_prefix() . $tables[$docType])->row_array();
        return $row ?: [];
    }

    private function sales_doc_is_draft(string $docType, int $id): bool
    {
        $row = $this->get_sales_doc_summary($docType, $id);
        if (!$row) {
            return false;
        }

        $status = (int) ($row['status'] ?? 0);
        if ($docType === 'estimate') {
            return $status === 1;
        }
        if ($docType === 'invoice') {
            return $status === 6;
        }
        if ($docType === 'proposal') {
            return $status === 6;
        }

        return false;
    }

    private function sales_doc_model_name(string $docType): string
    {
        return [
            'estimate' => 'estimates_model',
            'invoice' => 'invoices_model',
            'proposal' => 'proposals_model',
        ][$docType];
    }

    private function load_sales_doc_model(string $docType): void
    {
        $models = [
            'estimate' => 'estimates_model',
            'invoice' => 'invoices_model',
            'proposal' => 'proposals_model',
        ];
        if (!empty($models[$docType])) {
            $this->load->model($models[$docType]);
        }
    }

    private function sales_doc_edit_url(string $docType, int $id): string
    {
        $routes = [
            'estimate' => 'estimates/estimate/',
            'invoice' => 'invoices/invoice/',
            'proposal' => 'proposals/proposal/',
        ];

        return admin_url($routes[$docType] . $id);
    }

    private function sales_doc_draft_status(string $docType): int
    {
        return $docType === 'estimate' ? 1 : 6;
    }

    private function sales_doc_open_status(string $docType): int
    {
        if ($docType === 'estimate') {
            return 2;
        }
        if ($docType === 'proposal') {
            return 1;
        }

        return 1;
    }

    private function sales_doc_update_payload(string $docType, int $id, array $row, array $plan, string $targetStatus): array
    {
        $header = $plan['header'] ?? [];
        $items = $plan['items'] ?? [];
        $newItems = [];
        foreach ($items as $index => $item) {
            $newItems[] = [
                'order' => $index + 1,
                'description' => (string) ($item['description'] ?? ''),
                'long_description' => (string) ($item['long_description'] ?? ''),
                'qty' => (float) ($item['qty'] ?? 1),
                'unit' => (string) ($item['unit'] ?? ''),
                'rate' => (float) ($item['rate'] ?? 0),
                'taxname' => is_array($item['taxname'] ?? null) ? $item['taxname'] : [],
                'is_optional' => 0,
                'is_selected' => 1,
            ];
        }

        $totals = $this->calculate_sales_doc_totals($newItems, $header);
        $removedItems = array_map(static function ($item) {
            return (int) $item['id'];
        }, get_items_by_type($docType, $id));

        $base = [
            'currency' => (int) ($header['currency'] ?? $row['currency'] ?? 0),
            'subtotal' => $totals['subtotal'],
            'total' => $totals['total'],
            'total_tax' => $totals['total_tax'],
            'adjustment' => (float) ($row['adjustment'] ?? 0),
            'discount_percent' => (float) ($header['discount_percent'] ?? $row['discount_percent'] ?? 0),
            'discount_total' => (float) ($header['discount_total'] ?? $row['discount_total'] ?? 0),
            'discount_type' => (string) ($header['discount_type'] ?? $row['discount_type'] ?? ''),
            'status' => $targetStatus === 'open' ? $this->sales_doc_open_status($docType) : $this->sales_doc_draft_status($docType),
            'show_quantity_as' => (int) ($row['show_quantity_as'] ?? 1),
            'removed_items' => $removedItems,
            'newitems' => $newItems,
        ];

        if ($docType === 'estimate') {
            return array_merge($base, [
                'clientid' => (int) ($header['clientid'] ?? $row['clientid'] ?? 0),
                'project_id' => (int) ($header['project_id'] ?? $row['project_id'] ?? 0),
                'number' => (string) ($row['number'] ?? get_option('next_estimate_number')),
                'date' => $this->sales_doc_date($header['date'] ?? $row['date'] ?? date('Y-m-d')),
                'expirydate' => $this->sales_doc_date($header['expirydate'] ?? $row['expirydate'] ?? ''),
                'sale_agent' => (int) ($header['sale_agent'] ?? $row['sale_agent'] ?? get_staff_user_id()),
                'adminnote' => (string) ($header['adminnote'] ?? $row['adminnote'] ?? ''),
                'clientnote' => (string) ($header['clientnote'] ?? $row['clientnote'] ?? ''),
                'terms' => (string) ($header['terms'] ?? $row['terms'] ?? ''),
                'reference_no' => (string) ($header['reference_no'] ?? $row['reference_no'] ?? ''),
                'billing_street' => (string) ($row['billing_street'] ?? ''),
                'billing_city' => (string) ($row['billing_city'] ?? ''),
                'billing_state' => (string) ($row['billing_state'] ?? ''),
                'billing_zip' => (string) ($row['billing_zip'] ?? ''),
                'billing_country' => (int) ($row['billing_country'] ?? 0),
                'shipping_street' => (string) ($row['shipping_street'] ?? ''),
                'shipping_city' => (string) ($row['shipping_city'] ?? ''),
                'shipping_state' => (string) ($row['shipping_state'] ?? ''),
                'shipping_zip' => (string) ($row['shipping_zip'] ?? ''),
                'shipping_country' => (int) ($row['shipping_country'] ?? 0),
                'include_shipping' => (int) ($row['include_shipping'] ?? 0),
                'show_shipping_on_estimate' => (int) ($row['show_shipping_on_estimate'] ?? 1),
            ]);
        }

        if ($docType === 'invoice') {
            return array_merge($base, [
                'clientid' => (int) ($header['clientid'] ?? $row['clientid'] ?? 0),
                'project_id' => (int) ($header['project_id'] ?? $row['project_id'] ?? 0),
                'number' => (string) ($row['number'] ?? get_option('next_invoice_number')),
                'date' => $this->sales_doc_date($header['date'] ?? $row['date'] ?? date('Y-m-d')),
                'duedate' => $this->sales_doc_date($header['duedate'] ?? $row['duedate'] ?? ''),
                'sale_agent' => (int) ($header['sale_agent'] ?? $row['sale_agent'] ?? get_staff_user_id()),
                'adminnote' => (string) ($header['adminnote'] ?? $row['adminnote'] ?? ''),
                'clientnote' => (string) ($header['clientnote'] ?? $row['clientnote'] ?? ''),
                'terms' => (string) ($header['terms'] ?? $row['terms'] ?? ''),
                'billing_street' => (string) ($row['billing_street'] ?? ''),
                'billing_city' => (string) ($row['billing_city'] ?? ''),
                'billing_state' => (string) ($row['billing_state'] ?? ''),
                'billing_zip' => (string) ($row['billing_zip'] ?? ''),
                'billing_country' => (int) ($row['billing_country'] ?? 0),
                'shipping_street' => (string) ($row['shipping_street'] ?? ''),
                'shipping_city' => (string) ($row['shipping_city'] ?? ''),
                'shipping_state' => (string) ($row['shipping_state'] ?? ''),
                'shipping_zip' => (string) ($row['shipping_zip'] ?? ''),
                'shipping_country' => (int) ($row['shipping_country'] ?? 0),
                'include_shipping' => (int) ($row['include_shipping'] ?? 0),
                'show_shipping_on_invoice' => (int) ($row['show_shipping_on_invoice'] ?? 1),
                'recurring' => (int) ($row['recurring'] ?? 0),
                'cycles' => (int) ($row['cycles'] ?? 0),
                'allowed_payment_modes' => [],
                'cancel_overdue_reminders' => (int) ($row['cancel_overdue_reminders'] ?? 0),
            ]);
        }

        $clientId = (int) ($header['clientid'] ?? $row['rel_id'] ?? 0);
        $customer = $clientId > 0 ? $this->db->where('userid', $clientId)->get(db_prefix() . 'clients')->row_array() : [];

        return array_merge($base, [
            'subject' => (string) ($header['subject'] ?? $row['subject'] ?? _l('new_proposal')),
            'proposal_to' => (string) ($customer['company'] ?? $row['proposal_to'] ?? ''),
            'email' => (string) ($row['email'] ?? ''),
            'phone' => (string) ($row['phone'] ?? ''),
            'address' => (string) ($row['address'] ?? ''),
            'city' => (string) ($row['city'] ?? ''),
            'state' => (string) ($row['state'] ?? ''),
            'zip' => (string) ($row['zip'] ?? ''),
            'country' => (int) ($row['country'] ?? 0),
            'date' => $this->sales_doc_date($header['date'] ?? $row['date'] ?? date('Y-m-d')),
            'open_till' => $this->sales_doc_date($header['open_till'] ?? $row['open_till'] ?? ''),
            'assigned' => (int) ($header['sale_agent'] ?? $row['assigned'] ?? get_staff_user_id()),
            'allow_comments' => (int) ($row['allow_comments'] ?? 1),
            'rel_type' => $clientId > 0 ? 'customer' : (string) ($row['rel_type'] ?? ''),
            'rel_id' => $clientId > 0 ? $clientId : (int) ($row['rel_id'] ?? 0),
            'project_id' => (int) ($header['project_id'] ?? $row['project_id'] ?? 0),
            'content' => (string) ($header['content'] ?? $row['content'] ?? '{proposal_items}'),
        ]);
    }

    private function calculate_sales_doc_totals(array $items, array $header): array
    {
        $subtotal = 0.0;
        $totalTax = 0.0;
        foreach ($items as $item) {
            $line = ((float) $item['qty']) * ((float) $item['rate']);
            $subtotal += $line;
            foreach ($item['taxname'] as $taxName) {
                if (strpos($taxName, '|') === false) {
                    continue;
                }
                [, $rate] = explode('|', $taxName, 2);
                $totalTax += $line * ((float) $rate) / 100;
            }
        }

        $discountPercent = (float) ($header['discount_percent'] ?? 0);
        $discountTotal = (float) ($header['discount_total'] ?? 0);
        if ($discountPercent > 0) {
            $discountTotal = $subtotal * $discountPercent / 100;
        }
        $discountType = (string) ($header['discount_type'] ?? '');
        if ($discountTotal > 0 && $discountType === 'before_tax' && $subtotal > 0) {
            $totalTax -= $totalTax * ($discountTotal / $subtotal);
        }
        $total = max(0, $subtotal - $discountTotal + max(0, $totalTax));

        return [
            'subtotal' => $subtotal,
            'total_tax' => max(0, $totalTax),
            'total' => $total,
        ];
    }

    private function sales_doc_date($value): string
    {
        $value = trim((string) $value);
        if ($value === '' || $value === '0000-00-00') {
            return '';
        }

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : to_sql_date($value);
    }

    private function search_customers(string $query): array
    {
        $this->db->select('userid, company');
        $this->db->from(db_prefix() . 'clients');
        $this->db->limit(10);
        $terms = $this->search_terms($query);
        if ($terms) {
            $this->db->group_start();
            foreach ($terms as $term) {
                $this->db->or_like('company', $term);
            }
            $this->db->group_end();
        }

        return $this->db->get()->result_array();
    }

    private function search_projects(string $query): array
    {
        $this->db->select('id, name, clientid');
        $this->db->from(db_prefix() . 'projects');
        $this->db->limit(10);
        foreach ($this->search_terms($query) as $term) {
            $this->db->or_like('name', $term);
        }

        return $this->db->get()->result_array();
    }

    private function search_items(string $query): array
    {
        $this->db->select('id, description, long_description, rate, unit, tax, tax2');
        $this->db->from(db_prefix() . 'items');
        $this->db->limit(20);
        foreach ($this->search_terms($query) as $term) {
            $this->db->or_like('description', $term);
            $this->db->or_like('long_description', $term);
        }

        return $this->db->get()->result_array();
    }

    private function list_taxes(): array
    {
        $rows = $this->db->select('id, name, taxrate')->get(db_prefix() . 'taxes')->result_array();
        foreach ($rows as &$row) {
            $row['taxname'] = $row['name'] . '|' . $row['taxrate'];
        }

        return $rows;
    }

    private function list_currencies(): array
    {
        return $this->db->select('id, name, symbol, isdefault')->get(db_prefix() . 'currencies')->result_array();
    }

    private function list_staff(): array
    {
        return $this->db->select('staffid, firstname, lastname, email')->where('active', 1)->limit(25)->get(db_prefix() . 'staff')->result_array();
    }

    private function tax_name_exists(string $taxName): bool
    {
        if ($taxName === '') {
            return true;
        }

        if (strpos($taxName, '|') === false) {
            return false;
        }

        [$name, $rate] = explode('|', $taxName, 2);
        return (bool) $this->db
            ->where('name', trim($name))
            ->where('taxrate', trim($rate))
            ->count_all_results(db_prefix() . 'taxes');
    }

    private function row_exists(string $table, string $column, int $id): bool
    {
        if ($id <= 0) {
            return false;
        }

        return (bool) $this->db->where($column, $id)->count_all_results(db_prefix() . $table);
    }

    private function search_terms(string $query): array
    {
        preg_match_all('/[\p{L}\p{N}_-]{3,}/u', mb_strtolower($query), $matches);
        return array_slice(array_unique($matches[0] ?? []), 0, 6);
    }

    private function preview_client_id(string $docType, array $document): int
    {
        if ($docType === 'proposal') {
            return (string) ($document['rel_type'] ?? '') === 'customer' ? (int) ($document['rel_id'] ?? 0) : 0;
        }

        return (int) ($document['clientid'] ?? 0);
    }

    private function preview_totals(array $document): array
    {
        return [
            'subtotal' => (float) ($document['subtotal'] ?? 0),
            'discount_percent' => (float) ($document['discount_percent'] ?? 0),
            'discount_total' => (float) ($document['discount_total'] ?? 0),
            'discount_type' => (string) ($document['discount_type'] ?? ''),
            'adjustment' => (float) ($document['adjustment'] ?? 0),
            'total_tax' => (float) ($document['total_tax'] ?? 0),
            'total' => (float) ($document['total'] ?? 0),
        ];
    }

    private function preview_currency(array $document)
    {
        $currencyId = (int) ($document['currency'] ?? 0);
        if ($currencyId > 0 && function_exists('get_currency')) {
            $currency = get_currency($currencyId);
            if ($currency) {
                return $currency;
            }
        }

        return get_base_currency();
    }

    private function preview_related_rows(string $table, string $docType, int $docId): array
    {
        $fullTable = db_prefix() . $table;
        if (!$this->db->table_exists($fullTable)) {
            return [];
        }

        $this->db->from($fullTable);
        if ($this->db->field_exists('rel_type', $fullTable) && $this->db->field_exists('rel_id', $fullTable)) {
            $this->db->where('rel_type', $docType);
            $this->db->where('rel_id', $docId);
        } elseif ($this->db->field_exists($docType . '_id', $fullTable)) {
            $this->db->where($docType . '_id', $docId);
        } else {
            return [];
        }

        if ($this->db->field_exists('dateadded', $fullTable)) {
            $this->db->order_by('dateadded', 'DESC');
        } elseif ($this->db->field_exists('date', $fullTable)) {
            $this->db->order_by('date', 'DESC');
        }
        $this->db->limit(10);

        return $this->db->get()->result_array();
    }

    private function preview_activity_rows(string $docType, int $docId): array
    {
        $tables = [
            'estimate' => 'estimate_activity',
            'invoice' => 'invoice_activity',
            'proposal' => 'proposal_activity',
        ];
        if (empty($tables[$docType])) {
            return [];
        }

        $fullTable = db_prefix() . $tables[$docType];
        if (!$this->db->table_exists($fullTable)) {
            return [];
        }

        $foreignKey = $docType . 'id';
        if (!$this->db->field_exists($foreignKey, $fullTable)) {
            return [];
        }

        $this->db->where($foreignKey, $docId);
        if ($this->db->field_exists('date', $fullTable)) {
            $this->db->order_by('date', 'DESC');
        }
        $this->db->limit(10);

        return $this->db->get($fullTable)->result_array();
    }

    private function preview_permission_snapshot(string $docType): array
    {
        $module = $docType . 's';
        return [
            'view' => staff_can('view', $module) || staff_can('view_own', $module),
            'edit' => staff_can('edit', $module),
            'create' => staff_can('create', $module),
            'create_task' => staff_can('create', 'tasks'),
            'create_project' => staff_can('create', 'projects'),
            'create_invoice' => staff_can('create', 'invoices'),
        ];
    }

    private function preview_permission_error(string $docType, string $permission): string
    {
        if ($permission === 'create_task') {
            return staff_cant('create', 'tasks') ? 'You do not have permission to create tasks.' : '';
        }
        if ($permission === 'create_project') {
            return staff_cant('create', 'projects') ? 'You do not have permission to create projects.' : '';
        }
        if ($permission === 'create_invoice') {
            return staff_cant('create', 'invoices') ? 'You do not have permission to create invoices.' : '';
        }

        $module = $docType . 's';
        return staff_cant('edit', $module) ? 'You do not have permission to request this document write action.' : '';
    }

    private function sales_doc_preview_url(string $docType, int $id): string
    {
        $routes = [
            'estimate' => 'estimates/list_estimates/',
            'invoice' => 'invoices/list_invoices/',
            'proposal' => 'proposals/list_proposals/',
        ];

        return admin_url($routes[$docType] . $id);
    }

    private function uuid(): string
    {
        if (function_exists('futureagent_uuid')) {
            return futureagent_uuid();
        }

        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
