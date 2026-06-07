<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Futurecrmagent_tool_registry
{
    private $CI;

    private $config = [
        'max_tool_turns' => 6,
        'blocked_actions' => [
            'payment',
            'refund',
            'credit',
            'delete',
            'password',
            'auth',
            'shell',
        ],
    ];

    public function __construct()
    {
        $this->CI = &get_instance();
    }

    public function get_catalog(string $docType = '', int $docId = 0): array
    {
        return [
            'config' => $this->config,
            'domains' => [
                'customers',
                'contacts',
                'leads',
                'estimates',
                'proposals',
                'invoices',
                'projects',
                'tasks',
                'tickets',
                'notes',
                'reminders',
                'contracts',
                'expenses',
                'staff',
                'taxes',
                'currencies',
            ],
            'tools' => [
                'sales_doc.get_summary' => [
                    'name' => 'sales_doc.get_summary',
                    'description' => 'Read current sales document header, items, and totals.',
                    'mode' => 'read',
                    'approval_required' => false,
                    'input_schema' => ['doc_type' => 'estimate|proposal|invoice', 'doc_id' => 'int'],
                    'result_shape' => ['document' => 'array', 'items' => 'array', 'totals' => 'array'],
                ],
                'sales_doc.explain_totals' => [
                    'name' => 'sales_doc.explain_totals',
                    'description' => 'Explain subtotal, discount, tax, adjustment, and total.',
                    'mode' => 'read',
                    'approval_required' => false,
                    'input_schema' => ['doc_type' => 'estimate|proposal|invoice', 'doc_id' => 'int'],
                    'result_shape' => ['totals' => 'array', 'explanation' => 'string'],
                ],
                'sales_doc.find_missing_fields' => [
                    'name' => 'sales_doc.find_missing_fields',
                    'description' => 'Find missing or weak fields before sending a sales document.',
                    'mode' => 'read',
                    'approval_required' => false,
                    'input_schema' => ['doc_type' => 'estimate|proposal|invoice', 'doc_id' => 'int'],
                    'result_shape' => ['missing_fields' => 'array', 'warnings' => 'array'],
                ],
                'sales_doc.list_related_records' => [
                    'name' => 'sales_doc.list_related_records',
                    'description' => 'Read related notes, tasks, reminders, and activity.',
                    'mode' => 'read',
                    'approval_required' => false,
                    'input_schema' => ['doc_type' => 'estimate|proposal|invoice', 'doc_id' => 'int'],
                    'result_shape' => ['notes' => 'array', 'tasks' => 'array', 'reminders' => 'array', 'activity' => 'array'],
                ],
                'crm.customer.get_history' => [
                    'name' => 'crm.customer.get_history',
                    'description' => 'Read customer sales document and task history for comparison.',
                    'mode' => 'read',
                    'approval_required' => false,
                    'input_schema' => ['customer_id' => 'int'],
                    'result_shape' => ['customer' => 'array', 'estimates' => 'array', 'proposals' => 'array', 'invoices' => 'array', 'tasks' => 'array'],
                ],
                'crm.reference.lookup' => [
                    'name' => 'crm.reference.lookup',
                    'description' => 'Read allowed taxes, currencies, staff, and lightweight CRM lookup context.',
                    'mode' => 'read',
                    'approval_required' => false,
                    'input_schema' => ['query' => 'string'],
                    'result_shape' => ['taxes' => 'array', 'currencies' => 'array', 'staff' => 'array', 'customers' => 'array'],
                ],
                'sales_doc.note.add' => [
                    'name' => 'sales_doc.note.add',
                    'description' => 'Prepare an internal note for approval. Does not write immediately.',
                    'mode' => 'write',
                    'approval_required' => true,
                    'required_permission' => 'edit',
                    'input_schema' => ['note' => 'string'],
                    'result_shape' => ['action_plan' => 'array'],
                ],
                'sales_doc.task.create' => [
                    'name' => 'sales_doc.task.create',
                    'description' => 'Prepare a related task for approval. Does not write immediately.',
                    'mode' => 'write',
                    'approval_required' => true,
                    'required_permission' => 'create_task',
                    'input_schema' => ['name' => 'string', 'description' => 'string', 'duedate' => 'YYYY-MM-DD'],
                    'result_shape' => ['action_plan' => 'array'],
                ],
                'sales_doc.email.prepare_send' => [
                    'name' => 'sales_doc.email.prepare_send',
                    'description' => 'Prepare customer email content for approval. Does not send immediately.',
                    'mode' => 'write',
                    'approval_required' => true,
                    'required_permission' => 'edit',
                    'input_schema' => ['subject' => 'string', 'body' => 'string'],
                    'result_shape' => ['action_plan' => 'array'],
                ],
                'sales_doc.status.update' => [
                    'name' => 'sales_doc.status.update',
                    'description' => 'Prepare status change for approval. Does not update immediately.',
                    'mode' => 'write',
                    'approval_required' => true,
                    'required_permission' => 'edit',
                    'input_schema' => ['status' => 'int|string', 'reason' => 'string'],
                    'result_shape' => ['action_plan' => 'array'],
                ],
                'sales_doc.attachment.summary' => [
                    'name' => 'sales_doc.attachment.summary',
                    'description' => 'Prepare generated summary attachment for approval.',
                    'mode' => 'write',
                    'approval_required' => true,
                    'required_permission' => 'edit',
                    'input_schema' => ['summary' => 'string', 'filename' => 'string'],
                    'result_shape' => ['action_plan' => 'array'],
                ],
                'estimate.convert.invoice' => [
                    'name' => 'estimate.convert.invoice',
                    'description' => 'Prepare estimate-to-invoice conversion for approval.',
                    'mode' => 'write',
                    'approval_required' => true,
                    'required_permission' => 'create_invoice',
                    'input_schema' => ['reason' => 'string'],
                    'result_shape' => ['action_plan' => 'array'],
                ],
                'estimate.convert.project' => [
                    'name' => 'estimate.convert.project',
                    'description' => 'Prepare estimate-to-project conversion for approval.',
                    'mode' => 'write',
                    'approval_required' => true,
                    'required_permission' => 'create_project',
                    'input_schema' => ['project_name' => 'string', 'reason' => 'string'],
                    'result_shape' => ['action_plan' => 'array'],
                ],
            ],
        ];
    }

    public function tools_for_llm(string $docType = '', int $docId = 0): array
    {
        $tools = [];
        foreach ($this->get_catalog($docType, $docId)['tools'] as $tool) {
            $tools[] = [
                'type' => 'function',
                'function' => [
                    'name' => str_replace('.', '__', $tool['name']),
                    'description' => $tool['description'],
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [],
                        'additionalProperties' => true,
                    ],
                ],
            ];
        }

        return $tools;
    }

    public function select_tools(string $message, string $mode = 'qa'): array
    {
        $text = mb_strtolower($message);
        if (strpos($text, '/tools') !== false || strpos($text, 'công cụ') !== false || strpos($text, 'tool') !== false) {
            return ['crm.reference.lookup'];
        }
        if ($mode === 'action' || strpos($text, '/action_note') !== false || strpos($text, 'tạo note') !== false || strpos($text, 'ghi chú') !== false) {
            return ['sales_doc.get_summary', 'sales_doc.note.add'];
        }
        if (strpos($text, 'thiếu') !== false || strpos($text, 'missing') !== false || strpos($text, '/missing_fields') !== false) {
            return ['sales_doc.get_summary', 'sales_doc.find_missing_fields'];
        }
        if (strpos($text, 'tổng') !== false || strpos($text, 'total') !== false || strpos($text, 'giá') !== false) {
            return ['sales_doc.get_summary', 'sales_doc.explain_totals'];
        }
        if (strpos($text, 'lịch sử') !== false || strpos($text, 'khách') !== false || strpos($text, 'customer') !== false) {
            return ['sales_doc.get_summary', 'crm.customer.get_history'];
        }
        if ($mode === 'summary' || strpos($text, '/summary') !== false || strpos($text, 'tóm tắt') !== false) {
            return ['sales_doc.get_summary', 'sales_doc.list_related_records'];
        }

        return ['sales_doc.get_summary'];
    }

    public function execute_tool(string $toolName, array $args, array $context): array
    {
        $started = microtime(true);
        $toolName = str_replace('__', '.', $toolName);
        $catalog = $this->get_catalog((string) ($context['doc_type'] ?? ''), (int) ($context['doc_id'] ?? 0))['tools'];
        if (empty($catalog[$toolName])) {
            throw new InvalidArgumentException('Tool is not in the approved FutureCRM Agent catalog.');
        }

        $tool = $catalog[$toolName];
        $docType = (string) ($context['doc_type'] ?? $args['doc_type'] ?? '');
        $docId = (int) ($context['doc_id'] ?? $args['doc_id'] ?? 0);

        if (($tool['mode'] ?? 'read') === 'write') {
            $result = [
                'action_plan' => $this->approval_plan_from_tool($toolName, $args, $context),
                'approval_required' => true,
                'executed' => false,
            ];
        } elseif ($toolName === 'sales_doc.get_summary') {
            $result = $this->salesDocSummary($docType, $docId);
        } elseif ($toolName === 'sales_doc.explain_totals') {
            $result = $this->salesDocTotals($docType, $docId);
        } elseif ($toolName === 'sales_doc.find_missing_fields') {
            $result = $this->salesDocMissingFields($docType, $docId);
        } elseif ($toolName === 'sales_doc.list_related_records') {
            $result = $this->salesDocRelatedRecords($docType, $docId);
        } elseif ($toolName === 'crm.customer.get_history') {
            $customerId = (int) ($args['customer_id'] ?? $context['customer_id'] ?? 0);
            $result = $this->customerHistory($customerId);
        } elseif ($toolName === 'crm.reference.lookup') {
            $result = $this->referenceLookup((string) ($args['query'] ?? $context['message'] ?? ''));
        } else {
            throw new InvalidArgumentException('Tool handler is not implemented.');
        }

        return [
            'tool' => $toolName,
            'mode' => (string) ($tool['mode'] ?? 'read'),
            'duration_ms' => (int) round((microtime(true) - $started) * 1000),
            'result' => $result,
        ];
    }

    public function approval_plan_from_tool(string $toolName, array $args, array $context): array
    {
        $toolName = str_replace('__', '.', $toolName);
        $payload = $args;
        if ($toolName === 'sales_doc.note.add') {
            $payload = ['note' => trim((string) ($args['note'] ?? $args['summary'] ?? $context['message'] ?? ''))];
        } elseif ($toolName === 'sales_doc.task.create') {
            $payload = [
                'name' => trim((string) ($args['name'] ?? 'Follow up sales document')),
                'description' => trim((string) ($args['description'] ?? $context['message'] ?? '')),
                'duedate' => trim((string) ($args['duedate'] ?? '')),
            ];
        } elseif ($toolName === 'sales_doc.email.prepare_send') {
            $payload = [
                'subject' => trim((string) ($args['subject'] ?? 'Sales document follow-up')),
                'body' => trim((string) ($args['body'] ?? $context['message'] ?? '')),
            ];
        } elseif ($toolName === 'sales_doc.status.update') {
            $payload = [
                'status' => $args['status'] ?? '',
                'reason' => trim((string) ($args['reason'] ?? $context['message'] ?? '')),
            ];
        } elseif ($toolName === 'sales_doc.attachment.summary') {
            $payload = [
                'summary' => trim((string) ($args['summary'] ?? $context['message'] ?? '')),
                'filename' => trim((string) ($args['filename'] ?? 'futurecrmagent-summary.txt')),
            ];
        } elseif ($toolName === 'estimate.convert.invoice') {
            $payload = ['reason' => trim((string) ($args['reason'] ?? $context['message'] ?? ''))];
        } elseif ($toolName === 'estimate.convert.project') {
            $payload = [
                'project_name' => trim((string) ($args['project_name'] ?? 'Project from estimate')),
                'reason' => trim((string) ($args['reason'] ?? $context['message'] ?? '')),
            ];
        }

        return [
            'action_key' => $toolName,
            'title' => $this->catalogLabel($toolName),
            'description' => trim((string) ($context['message'] ?? 'Approval-gated CRM action')),
            'payload' => $payload,
            'requires_approval' => true,
            'approval_required' => true,
        ];
    }

    private function salesDocSummary(string $docType, int $docId): array
    {
        $document = $this->salesDocRow($docType, $docId);
        return [
            'document' => $document,
            'items' => get_items_by_type($docType, $docId),
            'totals' => $this->totalsFromDocument($document),
        ];
    }

    private function salesDocTotals(string $docType, int $docId): array
    {
        $summary = $this->salesDocSummary($docType, $docId);
        $totals = $summary['totals'];
        return [
            'totals' => $totals,
            'explanation' => 'Subtotal ' . $totals['subtotal'] . ', discount ' . $totals['discount_total'] . ', tax ' . $totals['total_tax'] . ', adjustment ' . $totals['adjustment'] . ', total ' . $totals['total'] . '.',
        ];
    }

    private function salesDocMissingFields(string $docType, int $docId): array
    {
        $document = $this->salesDocRow($docType, $docId);
        $items = get_items_by_type($docType, $docId);
        $missing = [];
        $warnings = [];

        if ($docType === 'proposal') {
            if (trim((string) ($document['subject'] ?? '')) === '') {
                $missing[] = 'Proposal subject';
            }
            if (trim((string) ($document['email'] ?? '')) === '') {
                $warnings[] = 'Proposal recipient email is empty.';
            }
        } else {
            if ((int) ($document['clientid'] ?? 0) <= 0) {
                $missing[] = 'Customer';
            }
        }
        if (!$items) {
            $missing[] = 'Line items';
        }
        if ((float) ($document['total'] ?? 0) <= 0) {
            $warnings[] = 'Document total is zero.';
        }
        if (trim((string) ($document['date'] ?? '')) === '') {
            $missing[] = 'Document date';
        }

        return [
            'missing_fields' => $missing,
            'warnings' => $warnings,
        ];
    }

    private function salesDocRelatedRecords(string $docType, int $docId): array
    {
        return [
            'notes' => $this->relatedRows('notes', $docType, $docId),
            'tasks' => $this->relatedRows('tasks', $docType, $docId),
            'reminders' => $this->relatedRows('reminders', $docType, $docId),
            'activity' => $this->activityRows($docType, $docId),
        ];
    }

    private function customerHistory(int $customerId): array
    {
        if ($customerId <= 0) {
            return ['customer' => null, 'estimates' => [], 'proposals' => [], 'invoices' => [], 'tasks' => []];
        }

        return [
            'customer' => $this->CI->db->where('userid', $customerId)->get(db_prefix() . 'clients')->row_array(),
            'estimates' => $this->limitedRows('estimates', ['clientid' => $customerId]),
            'proposals' => $this->limitedRows('proposals', ['rel_type' => 'customer', 'rel_id' => $customerId]),
            'invoices' => $this->limitedRows('invoices', ['clientid' => $customerId]),
            'tasks' => $this->limitedRows('tasks', ['rel_type' => 'customer', 'rel_id' => $customerId]),
        ];
    }

    private function referenceLookup(string $query): array
    {
        return [
            'taxes' => $this->CI->db->select('id, name, taxrate')->limit(50)->get(db_prefix() . 'taxes')->result_array(),
            'currencies' => $this->CI->db->select('id, name, symbol, isdefault')->get(db_prefix() . 'currencies')->result_array(),
            'staff' => $this->CI->db->select('staffid, firstname, lastname, email')->where('active', 1)->limit(25)->get(db_prefix() . 'staff')->result_array(),
            'customers' => $this->searchCustomers($query),
        ];
    }

    private function salesDocRow(string $docType, int $docId): array
    {
        $tables = [
            'estimate' => 'estimates',
            'proposal' => 'proposals',
            'invoice' => 'invoices',
        ];
        if (empty($tables[$docType]) || $docId <= 0) {
            return [];
        }

        $row = $this->CI->db->where('id', $docId)->get(db_prefix() . $tables[$docType])->row_array();
        return $row ?: [];
    }

    private function totalsFromDocument(array $document): array
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

    private function relatedRows(string $table, string $docType, int $docId): array
    {
        $fullTable = db_prefix() . $table;
        if (!$this->CI->db->table_exists($fullTable)) {
            return [];
        }
        if ($this->CI->db->field_exists('rel_type', $fullTable) && $this->CI->db->field_exists('rel_id', $fullTable)) {
            $this->CI->db->where('rel_type', $docType)->where('rel_id', $docId);
        } elseif ($this->CI->db->field_exists($docType . '_id', $fullTable)) {
            $this->CI->db->where($docType . '_id', $docId);
        } else {
            return [];
        }

        $this->CI->db->limit(10);
        return $this->CI->db->get($fullTable)->result_array();
    }

    private function activityRows(string $docType, int $docId): array
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
        if (!$this->CI->db->table_exists($fullTable)) {
            return [];
        }
        $foreignKey = $docType . 'id';
        if (!$this->CI->db->field_exists($foreignKey, $fullTable)) {
            return [];
        }

        return $this->CI->db->where($foreignKey, $docId)->limit(10)->get($fullTable)->result_array();
    }

    private function limitedRows(string $table, array $where): array
    {
        return $this->CI->db->where($where)->order_by('id', 'DESC')->limit(5)->get(db_prefix() . $table)->result_array();
    }

    private function searchCustomers(string $query): array
    {
        $this->CI->db->select('userid, company');
        $this->CI->db->from(db_prefix() . 'clients');
        $terms = [];
        preg_match_all('/[\p{L}\p{N}_-]{3,}/u', mb_strtolower($query), $matches);
        foreach (array_slice(array_unique($matches[0] ?? []), 0, 4) as $term) {
            $terms[] = $term;
        }
        if ($terms) {
            $this->CI->db->group_start();
            foreach ($terms as $term) {
                $this->CI->db->or_like('company', $term);
            }
            $this->CI->db->group_end();
        }
        return $this->CI->db->limit(10)->get()->result_array();
    }

    private function catalogLabel(string $toolName): string
    {
        $catalog = $this->get_catalog()['tools'];
        return (string) ($catalog[$toolName]['description'] ?? $toolName);
    }
}
