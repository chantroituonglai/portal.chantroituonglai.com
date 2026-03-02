<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Project Agent Module Installation
 */

$CI = &get_instance();

// Create database tables
$CI->load->dbforge();

// Table: project_agent_sessions
$fields = [
    'session_id' => [
        'type' => 'BIGINT',
        'constraint' => 20,
        'unsigned' => TRUE,
        'auto_increment' => TRUE,
    ],
    'project_id' => [
        'type' => 'BIGINT',
        'constraint' => 20,
        'unsigned' => TRUE,
        'null' => TRUE,
    ],
    'user_id' => [
        'type' => 'BIGINT',
        'constraint' => 20,
        'unsigned' => TRUE,
        'null' => FALSE,
    ],
    'title' => [
        'type' => 'VARCHAR',
        'constraint' => 255,
        'null' => TRUE,
    ],
    'created_at' => [
        'type' => 'DATETIME',
        'null' => FALSE,
    ],
    'updated_at' => [
        'type' => 'DATETIME',
        'null' => FALSE,
    ],
];

$CI->dbforge->add_field($fields);
$CI->dbforge->add_key('session_id', TRUE);
$CI->dbforge->add_key('project_id');
$CI->dbforge->add_key('user_id');
$CI->dbforge->create_table(db_prefix() . 'project_agent_sessions', TRUE);

// Table: project_agent_memory_entries
$fields = [
    'entry_id' => [
        'type' => 'BIGINT',
        'constraint' => 20,
        'unsigned' => TRUE,
        'auto_increment' => TRUE,
    ],
    'session_id' => [
        'type' => 'BIGINT',
        'constraint' => 20,
        'unsigned' => TRUE,
        'null' => FALSE,
    ],
    'scope' => [
        'type' => 'ENUM',
        'constraint' => ['session', 'project', 'user', 'global'],
        'null' => FALSE,
    ],
    'kind' => [
        'type' => 'ENUM',
        'constraint' => [
            'input', 'context_snapshot', 'fact', 'assumption',
            'analysis_summary', 'decision', 'plan', 'action_call',
            'action_result', 'observation', 'warning', 'next_step',
            'state_summary', 'system_note'
        ],
        'null' => FALSE,
    ],
    'content_json' => [
        'type' => 'TEXT',
        'null' => FALSE,
    ],
    'created_at' => [
        'type' => 'DATETIME',
        'null' => FALSE,
    ],
    'project_id' => [
        'type' => 'BIGINT',
        'constraint' => 20,
        'unsigned' => TRUE,
        'null' => TRUE,
    ],
    'customer_id' => [
        'type' => 'BIGINT',
        'constraint' => 20,
        'unsigned' => TRUE,
        'null' => TRUE,
    ],
    'entity_refs' => [
        'type' => 'TEXT',
        'null' => TRUE,
    ],
];

$CI->dbforge->add_field($fields);
$CI->dbforge->add_key('entry_id', TRUE);
$CI->dbforge->add_key('session_id');
$CI->dbforge->add_key(['session_id', 'created_at'], FALSE, 'idx_session_time_desc');
$CI->dbforge->create_table(db_prefix() . 'project_agent_memory_entries', TRUE);

// Table: project_agent_actions
$fields = [
    'action_id' => [
        'type' => 'VARCHAR',
        'constraint' => 100,
        'null' => FALSE,
    ],
    'name' => [
        'type' => 'VARCHAR',
        'constraint' => 255,
        'null' => FALSE,
    ],
    'description' => [
        'type' => 'TEXT',
        'null' => TRUE,
    ],
    'params_schema' => [
        'type' => 'TEXT',
        'null' => FALSE,
    ],
    'permissions' => [
        'type' => 'TEXT',
        'null' => FALSE,
    ],
    'prompt_override' => [
        'type' => 'TEXT',
        'null' => TRUE,
    ],
    'param_mapping' => [
        'type' => 'TEXT',
        'null' => TRUE,
    ],
    'risk_level' => [
        'type' => 'ENUM',
        'constraint' => ['low', 'medium', 'high'],
        'default' => 'low',
        'null' => FALSE,
    ],
    'requires_confirm' => [
        'type' => 'TINYINT',
        'constraint' => 1,
        'default' => 0,
        'null' => FALSE,
    ],
    'is_active' => [
        'type' => 'TINYINT',
        'constraint' => 1,
        'default' => 1,
        'null' => FALSE,
    ],
];

$CI->dbforge->add_field($fields);
$CI->dbforge->add_key('action_id', TRUE);
$CI->dbforge->create_table(db_prefix() . 'project_agent_actions', TRUE);

// Table: project_agent_action_logs
$fields = [
    'log_id' => [
        'type' => 'BIGINT',
        'constraint' => 20,
        'unsigned' => TRUE,
        'auto_increment' => TRUE,
    ],
    'session_id' => [
        'type' => 'BIGINT',
        'constraint' => 20,
        'unsigned' => TRUE,
        'null' => FALSE,
    ],
    'plan_id' => [
        'type' => 'VARCHAR',
        'constraint' => 100,
        'null' => FALSE,
    ],
    'run_id' => [
        'type' => 'VARCHAR',
        'constraint' => 100,
        'null' => FALSE,
    ],
    'action_id' => [
        'type' => 'VARCHAR',
        'constraint' => 100,
        'null' => FALSE,
    ],
    'params_json' => [
        'type' => 'LONGTEXT',
        'null' => FALSE,
    ],
    'result_json' => [
        'type' => 'LONGTEXT',
        'null' => TRUE,
    ],
    'status' => [
        'type' => 'ENUM',
        'constraint' => ['queued', 'running', 'success', 'failed'],
        'null' => FALSE,
    ],
    'error_message' => [
        'type' => 'TEXT',
        'null' => TRUE,
    ],
    'executed_at' => [
        'type' => 'DATETIME',
        'null' => FALSE,
    ],
    'executed_by' => [
        'type' => 'BIGINT',
        'constraint' => 20,
        'unsigned' => TRUE,
        'null' => FALSE,
    ],
    'client_token' => [
        'type' => 'VARCHAR',
        'constraint' => 255,
        'null' => TRUE,
    ],
];

$CI->dbforge->add_field($fields);
$CI->dbforge->add_key('log_id', TRUE);
$CI->dbforge->add_key('session_id');
$CI->dbforge->add_key('plan_id');
$CI->dbforge->add_key('action_id');
$CI->dbforge->create_table(db_prefix() . 'project_agent_action_logs', TRUE);

// Insert default actions
$default_actions = [
    // Project Management Actions
    [
        'action_id' => 'create_project',
        'name' => 'Create Project',
        'description' => 'Create a new project',
        'params_schema' => json_encode([
            'type' => 'object',
            'required' => ['customer_id', 'name', 'billing_type'],
            'properties' => [
                'customer_id' => ['type' => 'integer', 'minimum' => 1],
                'name' => ['type' => 'string', 'minLength' => 1],
                'billing_type' => ['type' => 'integer', 'enum' => [1, 2, 3]],
                'start_date' => ['type' => 'string', 'format' => 'date'],
                'end_date' => ['type' => 'string', 'format' => 'date'],
                'members' => ['type' => 'array', 'items' => ['type' => 'integer']],
                'settings' => ['type' => 'object']
            ]
        ]),
        'permissions' => json_encode(['projects:create']),
        'risk_level' => 'low',
        'requires_confirm' => FALSE,
        'is_active' => TRUE
    ],
    [
        'action_id' => 'create_task',
        'name' => 'Create Task',
        'description' => 'Create a new task',
        'params_schema' => json_encode([
            'type' => 'object',
            'required' => ['title'],
            'properties' => [
                'project_id' => ['type' => 'integer'],
                'title' => ['type' => 'string', 'minLength' => 1],
                'description' => ['type' => 'string'],
                'assignee_ids' => ['type' => 'array', 'items' => ['type' => 'integer']],
                'priority' => ['type' => 'integer'],
                'start_date' => ['type' => 'string', 'format' => 'date'],
                'due_date' => ['type' => 'string', 'format' => 'date'],
                'hourly_rate' => ['type' => 'number'],
                'billable' => ['type' => 'boolean'],
                'recurring' => ['type' => 'object']
            ]
        ]),
        'permissions' => json_encode(['tasks:create']),
        'risk_level' => 'low',
        'requires_confirm' => FALSE,
        'is_active' => TRUE
    ],
    [
        'action_id' => 'check_billing_status',
        'name' => 'Check Billing Status',
        'description' => 'Check billing status for a project',
        'params_schema' => json_encode([
            'type' => 'object',
            'required' => ['project_id'],
            'properties' => [
                'project_id' => ['type' => 'integer', 'minimum' => 1]
            ]
        ]),
        'permissions' => json_encode(['projects:view', 'invoices:view']),
        'risk_level' => 'low',
        'requires_confirm' => FALSE,
        'is_active' => TRUE
    ],
    [
        'action_id' => 'summarize_project_work_remaining',
        'name' => 'Summarize Project Work Remaining',
        'description' => 'Get summary of remaining work for a project',
        'params_schema' => json_encode([
            'type' => 'object',
            'required' => ['project_id'],
            'properties' => [
                'project_id' => ['type' => 'integer', 'minimum' => 1]
            ]
        ]),
        'permissions' => json_encode(['projects:view', 'tasks:view']),
        'risk_level' => 'low',
        'requires_confirm' => FALSE,
        'is_active' => TRUE
    ],
    [
        'action_id' => 'get_project_overview',
        'name' => 'Get Project Overview',
        'description' => 'Get project overview with progress and milestones',
        'params_schema' => json_encode([
            'type' => 'object',
            'required' => ['project_id'],
            'properties' => [
                'project_id' => ['type' => 'integer', 'minimum' => 1]
            ]
        ]),
        'permissions' => json_encode(['projects:view']),
        'risk_level' => 'low',
        'requires_confirm' => FALSE,
        'is_active' => TRUE
    ],
    [
        'action_id' => 'list_project_tasks',
        'name' => 'List Project Tasks',
        'description' => 'List all tasks for a project',
        'params_schema' => json_encode([
            'type' => 'object',
            'required' => ['project_id'],
            'properties' => [
                'project_id' => ['type' => 'integer', 'minimum' => 1],
                'status' => ['type' => 'integer']
            ]
        ]),
        'permissions' => json_encode(['projects:view', 'tasks:view']),
        'risk_level' => 'low',
        'requires_confirm' => FALSE,
        'is_active' => TRUE
    ],
    [
        'action_id' => 'update_task',
        'name' => 'Update Task',
        'description' => 'Update task information',
        'params_schema' => json_encode([
            'type' => 'object',
            'required' => ['task_id'],
            'properties' => [
                'task_id' => ['type' => 'integer', 'minimum' => 1],
                'title' => ['type' => 'string'],
                'description' => ['type' => 'string'],
                'status' => ['type' => 'integer'],
                'priority' => ['type' => 'integer'],
                'due_date' => ['type' => 'string', 'format' => 'date'],
                'assignee_ids' => ['type' => 'array', 'items' => ['type' => 'integer']],
                'billable' => ['type' => 'boolean'],
                'hourly_rate' => ['type' => 'number']
            ]
        ]),
        'permissions' => json_encode(['tasks:edit']),
        'risk_level' => 'low',
        'requires_confirm' => FALSE,
        'is_active' => TRUE
    ],
    [
        'action_id' => 'add_project_member',
        'name' => 'Add Project Member',
        'description' => 'Add members to a project',
        'params_schema' => json_encode([
            'type' => 'object',
            'required' => ['project_id', 'staff_ids'],
            'properties' => [
                'project_id' => ['type' => 'integer', 'minimum' => 1],
                'staff_ids' => ['type' => 'array', 'items' => ['type' => 'integer']]
            ]
        ]),
        'permissions' => json_encode(['projects:edit']),
        'risk_level' => 'low',
        'requires_confirm' => FALSE,
        'is_active' => TRUE
    ],
    [
        'action_id' => 'create_estimate',
        'name' => 'Create Estimate',
        'description' => 'Create a new estimate',
        'params_schema' => json_encode([
            'type' => 'object',
            'required' => ['customer_id'],
            'properties' => [
                'customer_id' => ['type' => 'integer', 'minimum' => 1],
                'project_id' => ['type' => 'integer'],
                'items' => ['type' => 'array', 'items' => ['type' => 'object']],
                'terms' => ['type' => 'string'],
                'notes' => ['type' => 'string']
            ]
        ]),
        'permissions' => json_encode(['estimates:create']),
        'risk_level' => 'medium',
        'requires_confirm' => TRUE,
        'is_active' => TRUE
    ],
    [
        'action_id' => 'list_overdue_invoices',
        'name' => 'List Overdue Invoices',
        'description' => 'List overdue invoices',
        'params_schema' => json_encode([
            'type' => 'object',
            'properties' => [
                'customer_id' => ['type' => 'integer', 'minimum' => 1]
            ]
        ]),
        'permissions' => json_encode(['invoices:view']),
        'risk_level' => 'low',
        'requires_confirm' => FALSE,
        'is_active' => TRUE
    ],
    [
        'action_id' => 'update_project',
        'name' => 'Update Project',
        'description' => 'Update project information',
        'params_schema' => json_encode([
            'type' => 'object',
            'required' => ['project_id'],
            'properties' => [
                'project_id' => ['type' => 'integer', 'minimum' => 1],
                'name' => ['type' => 'string'],
                'description' => ['type' => 'string'],
                'status' => ['type' => 'integer'],
                'start_date' => ['type' => 'string', 'format' => 'date'],
                'deadline' => ['type' => 'string', 'format' => 'date'],
                'billing_type' => ['type' => 'integer']
            ]
        ]),
        'permissions' => json_encode(['projects:edit']),
        'risk_level' => 'low',
        'requires_confirm' => FALSE,
        'is_active' => TRUE
    ],
    [
        'action_id' => 'add_milestone',
        'name' => 'Add Milestone',
        'description' => 'Add milestone to project',
        'params_schema' => json_encode([
            'type' => 'object',
            'required' => ['project_id', 'name', 'due_date'],
            'properties' => [
                'project_id' => ['type' => 'integer', 'minimum' => 1],
                'name' => ['type' => 'string', 'minLength' => 1],
                'description' => ['type' => 'string'],
                'due_date' => ['type' => 'string', 'format' => 'date']
            ]
        ]),
        'permissions' => json_encode(['projects:edit']),
        'risk_level' => 'low',
        'requires_confirm' => FALSE,
        'is_active' => TRUE
    ],
    [
        'action_id' => 'link_invoice_to_project',
        'name' => 'Link Invoice to Project',
        'description' => 'Link existing invoice to project',
        'params_schema' => json_encode([
            'type' => 'object',
            'required' => ['project_id', 'invoice_id'],
            'properties' => [
                'project_id' => ['type' => 'integer', 'minimum' => 1],
                'invoice_id' => ['type' => 'integer', 'minimum' => 1]
            ]
        ]),
        'permissions' => json_encode(['projects:edit', 'invoices:edit']),
        'risk_level' => 'low',
        'requires_confirm' => FALSE,
        'is_active' => TRUE
    ],
    [
        'action_id' => 'get_project_finance_overview',
        'name' => 'Get Project Finance Overview',
        'description' => 'Get comprehensive financial overview of project',
        'params_schema' => json_encode([
            'type' => 'object',
            'required' => ['project_id'],
            'properties' => [
                'project_id' => ['type' => 'integer', 'minimum' => 1]
            ]
        ]),
        'permissions' => json_encode(['projects:view', 'invoices:view']),
        'risk_level' => 'low',
        'requires_confirm' => FALSE,
        'is_active' => TRUE
    ],
    [
        'action_id' => 'invoice_project',
        'name' => 'Invoice Project',
        'description' => 'Create invoice for project',
        'params_schema' => json_encode([
            'type' => 'object',
            'required' => ['project_id'],
            'properties' => [
                'project_id' => ['type' => 'integer', 'minimum' => 1],
                'include_timesheets' => ['type' => 'boolean'],
                'include_expenses' => ['type' => 'boolean'],
                'due_date' => ['type' => 'string', 'format' => 'date']
            ]
        ]),
        'permissions' => json_encode(['invoices:create']),
        'risk_level' => 'medium',
        'requires_confirm' => TRUE,
        'is_active' => TRUE
    ],
    [
        'action_id' => 'link_task_to',
        'name' => 'Link Task to Entity',
        'description' => 'Link task to project, invoice, or estimate',
        'params_schema' => json_encode([
            'type' => 'object',
            'required' => ['task_id', 'rel_type', 'rel_id'],
            'properties' => [
                'task_id' => ['type' => 'integer', 'minimum' => 1],
                'rel_type' => ['type' => 'string', 'enum' => ['project', 'invoice', 'estimate']],
                'rel_id' => ['type' => 'integer', 'minimum' => 1]
            ]
        ]),
        'permissions' => json_encode(['tasks:edit']),
        'risk_level' => 'low',
        'requires_confirm' => FALSE,
        'is_active' => TRUE
    ],
    [
        'action_id' => 'add_task_follower',
        'name' => 'Add Task Follower',
        'description' => 'Add follower to task',
        'params_schema' => json_encode([
            'type' => 'object',
            'required' => ['task_id', 'staff_id'],
            'properties' => [
                'task_id' => ['type' => 'integer', 'minimum' => 1],
                'staff_id' => ['type' => 'integer', 'minimum' => 1]
            ]
        ]),
        'permissions' => json_encode(['tasks:edit']),
        'risk_level' => 'low',
        'requires_confirm' => FALSE,
        'is_active' => TRUE
    ],
    [
        'action_id' => 'start_task_timer',
        'name' => 'Start Task Timer',
        'description' => 'Start timer for task',
        'params_schema' => json_encode([
            'type' => 'object',
            'required' => ['task_id'],
            'properties' => [
                'task_id' => ['type' => 'integer', 'minimum' => 1],
                'note' => ['type' => 'string']
            ]
        ]),
        'permissions' => json_encode(['tasks:edit']),
        'risk_level' => 'low',
        'requires_confirm' => FALSE,
        'is_active' => TRUE
    ],
    [
        'action_id' => 'add_task_timesheet',
        'name' => 'Add Task Timesheet',
        'description' => 'Add timesheet entry for task',
        'params_schema' => json_encode([
            'type' => 'object',
            'required' => ['task_id', 'hours'],
            'properties' => [
                'task_id' => ['type' => 'integer', 'minimum' => 1],
                'hours' => ['type' => 'number', 'minimum' => 0.1],
                'note' => ['type' => 'string'],
                'date' => ['type' => 'string', 'format' => 'date']
            ]
        ]),
        'permissions' => json_encode(['tasks:edit']),
        'risk_level' => 'low',
        'requires_confirm' => FALSE,
        'is_active' => TRUE
    ],
    [
        'action_id' => 'bill_non_project_tasks_to_invoice',
        'name' => 'Bill Non-Project Tasks to Invoice',
        'description' => 'Bill tasks not linked to projects to invoice',
        'params_schema' => json_encode([
            'type' => 'object',
            'required' => ['invoice_id'],
            'properties' => [
                'invoice_id' => ['type' => 'integer', 'minimum' => 1],
                'task_ids' => ['type' => 'array', 'items' => ['type' => 'integer']]
            ]
        ]),
        'permissions' => json_encode(['invoices:edit', 'tasks:view']),
        'risk_level' => 'medium',
        'requires_confirm' => TRUE,
        'is_active' => TRUE
    ],
    [
        'action_id' => 'list_project_timesheets',
        'name' => 'List Project Timesheets',
        'description' => 'List all timesheets for project',
        'params_schema' => json_encode([
            'type' => 'object',
            'required' => ['project_id'],
            'properties' => [
                'project_id' => ['type' => 'integer', 'minimum' => 1],
                'date_from' => ['type' => 'string', 'format' => 'date'],
                'date_to' => ['type' => 'string', 'format' => 'date'],
                'billed' => ['type' => 'boolean']
            ]
        ]),
        'permissions' => json_encode(['projects:view', 'tasks:view']),
        'risk_level' => 'low',
        'requires_confirm' => FALSE,
        'is_active' => TRUE
    ],
    [
        'action_id' => 'invoice_timesheets',
        'name' => 'Invoice Timesheets',
        'description' => 'Create invoice from timesheets',
        'params_schema' => json_encode([
            'type' => 'object',
            'required' => ['project_id'],
            'properties' => [
                'project_id' => ['type' => 'integer', 'minimum' => 1],
                'timesheet_ids' => ['type' => 'array', 'items' => ['type' => 'integer']],
                'due_date' => ['type' => 'string', 'format' => 'date']
            ]
        ]),
        'permissions' => json_encode(['invoices:create']),
        'risk_level' => 'medium',
        'requires_confirm' => TRUE,
        'is_active' => TRUE
    ],
    [
        'action_id' => 'record_expense',
        'name' => 'Record Expense',
        'description' => 'Record new expense',
        'params_schema' => json_encode([
            'type' => 'object',
            'required' => ['amount', 'category'],
            'properties' => [
                'amount' => ['type' => 'number', 'minimum' => 0.01],
                'category' => ['type' => 'integer'],
                'description' => ['type' => 'string'],
                'date' => ['type' => 'string', 'format' => 'date'],
                'project_id' => ['type' => 'integer'],
                'billable' => ['type' => 'boolean'],
                'tax' => ['type' => 'number']
            ]
        ]),
        'permissions' => json_encode(['expenses:create']),
        'risk_level' => 'medium',
        'requires_confirm' => TRUE,
        'is_active' => TRUE
    ],
    [
        'action_id' => 'set_recurring_expense',
        'name' => 'Set Recurring Expense',
        'description' => 'Set up recurring expense',
        'params_schema' => json_encode([
            'type' => 'object',
            'required' => ['amount', 'category', 'repeat_every'],
            'properties' => [
                'amount' => ['type' => 'number', 'minimum' => 0.01],
                'category' => ['type' => 'integer'],
                'description' => ['type' => 'string'],
                'repeat_every' => ['type' => 'integer', 'minimum' => 1],
                'repeat_type' => ['type' => 'string', 'enum' => ['day', 'week', 'month', 'year']],
                'cycles' => ['type' => 'integer'],
                'last_recurring_date' => ['type' => 'string', 'format' => 'date']
            ]
        ]),
        'permissions' => json_encode(['expenses:create']),
        'risk_level' => 'medium',
        'requires_confirm' => TRUE,
        'is_active' => TRUE
    ],
    [
        'action_id' => 'convert_expense_to_invoice',
        'name' => 'Convert Expense to Invoice',
        'description' => 'Convert expense to invoice item',
        'params_schema' => json_encode([
            'type' => 'object',
            'required' => ['expense_id', 'invoice_id'],
            'properties' => [
                'expense_id' => ['type' => 'integer', 'minimum' => 1],
                'invoice_id' => ['type' => 'integer', 'minimum' => 1]
            ]
        ]),
        'permissions' => json_encode(['expenses:edit', 'invoices:edit']),
        'risk_level' => 'medium',
        'requires_confirm' => TRUE,
        'is_active' => TRUE
    ],
    [
        'action_id' => 'send_estimate_email',
        'name' => 'Send Estimate Email',
        'description' => 'Send estimate via email',
        'params_schema' => json_encode([
            'type' => 'object',
            'required' => ['estimate_id'],
            'properties' => [
                'estimate_id' => ['type' => 'integer', 'minimum' => 1],
                'email' => ['type' => 'string', 'format' => 'email'],
                'subject' => ['type' => 'string'],
                'message' => ['type' => 'string']
            ]
        ]),
        'permissions' => json_encode(['estimates:view']),
        'risk_level' => 'low',
        'requires_confirm' => FALSE,
        'is_active' => TRUE
    ],
    [
        'action_id' => 'convert_estimate_to_invoice',
        'name' => 'Convert Estimate to Invoice',
        'description' => 'Convert estimate to invoice',
        'params_schema' => json_encode([
            'type' => 'object',
            'required' => ['estimate_id'],
            'properties' => [
                'estimate_id' => ['type' => 'integer', 'minimum' => 1],
                'status' => ['type' => 'integer']
            ]
        ]),
        'permissions' => json_encode(['estimates:edit', 'invoices:create']),
        'risk_level' => 'medium',
        'requires_confirm' => TRUE,
        'is_active' => TRUE
    ],
    [
        'action_id' => 'convert_estimate_to_project',
        'name' => 'Convert Estimate to Project',
        'description' => 'Create project from estimate',
        'params_schema' => json_encode([
            'type' => 'object',
            'required' => ['estimate_id'],
            'properties' => [
                'estimate_id' => ['type' => 'integer', 'minimum' => 1],
                'project_name' => ['type' => 'string'],
                'start_date' => ['type' => 'string', 'format' => 'date']
            ]
        ]),
        'permissions' => json_encode(['estimates:view', 'projects:create']),
        'risk_level' => 'medium',
        'requires_confirm' => TRUE,
        'is_active' => TRUE
    ],
    [
        'action_id' => 'create_estimate_request_form',
        'name' => 'Create Estimate Request Form',
        'description' => 'Create estimate request form',
        'params_schema' => json_encode([
            'type' => 'object',
            'required' => ['name'],
            'properties' => [
                'name' => ['type' => 'string', 'minLength' => 1],
                'description' => ['type' => 'string'],
                'form_data' => ['type' => 'object']
            ]
        ]),
        'permissions' => json_encode(['estimates:create']),
        'risk_level' => 'low',
        'requires_confirm' => FALSE,
        'is_active' => TRUE
    ],
    [
        'action_id' => 'send_invoice_email',
        'name' => 'Send Invoice Email',
        'description' => 'Send invoice via email',
        'params_schema' => json_encode([
            'type' => 'object',
            'required' => ['invoice_id'],
            'properties' => [
                'invoice_id' => ['type' => 'integer', 'minimum' => 1],
                'email' => ['type' => 'string', 'format' => 'email'],
                'subject' => ['type' => 'string'],
                'message' => ['type' => 'string']
            ]
        ]),
        'permissions' => json_encode(['invoices:view']),
        'risk_level' => 'low',
        'requires_confirm' => FALSE,
        'is_active' => TRUE
    ],
    [
        'action_id' => 'set_recurring_invoice',
        'name' => 'Set Recurring Invoice',
        'description' => 'Set up recurring invoice',
        'params_schema' => json_encode([
            'type' => 'object',
            'required' => ['invoice_id', 'repeat_every'],
            'properties' => [
                'invoice_id' => ['type' => 'integer', 'minimum' => 1],
                'repeat_every' => ['type' => 'integer', 'minimum' => 1],
                'repeat_type' => ['type' => 'string', 'enum' => ['day', 'week', 'month', 'year']],
                'cycles' => ['type' => 'integer'],
                'last_recurring_date' => ['type' => 'string', 'format' => 'date']
            ]
        ]),
        'permissions' => json_encode(['invoices:edit']),
        'risk_level' => 'medium',
        'requires_confirm' => TRUE,
        'is_active' => TRUE
    ],
    [
        'action_id' => 'record_invoice_payment',
        'name' => 'Record Invoice Payment',
        'description' => 'Record payment for invoice',
        'params_schema' => json_encode([
            'type' => 'object',
            'required' => ['invoice_id', 'amount'],
            'properties' => [
                'invoice_id' => ['type' => 'integer', 'minimum' => 1],
                'amount' => ['type' => 'number', 'minimum' => 0.01],
                'payment_mode' => ['type' => 'string'],
                'transaction_id' => ['type' => 'string'],
                'note' => ['type' => 'string'],
                'date' => ['type' => 'string', 'format' => 'date']
            ]
        ]),
        'permissions' => json_encode(['payments:create']),
        'risk_level' => 'medium',
        'requires_confirm' => TRUE,
        'is_active' => TRUE
    ],
    [
        'action_id' => 'invoice_project_expenses',
        'name' => 'Invoice Project Expenses',
        'description' => 'Create invoice for project expenses',
        'params_schema' => json_encode([
            'type' => 'object',
            'required' => ['project_id'],
            'properties' => [
                'project_id' => ['type' => 'integer', 'minimum' => 1],
                'expense_ids' => ['type' => 'array', 'items' => ['type' => 'integer']],
                'due_date' => ['type' => 'string', 'format' => 'date']
            ]
        ]),
        'permissions' => json_encode(['invoices:create', 'expenses:view']),
        'risk_level' => 'medium',
        'requires_confirm' => TRUE,
        'is_active' => TRUE
    ],
    [
        'action_id' => 'find_unlinked_entities',
        'name' => 'Find Unlinked Entities',
        'description' => 'Find tasks, expenses, or timesheets not linked to projects',
        'params_schema' => json_encode([
            'type' => 'object',
            'properties' => [
                'entity_type' => ['type' => 'string', 'enum' => ['tasks', 'expenses', 'timesheets']],
                'date_from' => ['type' => 'string', 'format' => 'date'],
                'date_to' => ['type' => 'string', 'format' => 'date']
            ]
        ]),
        'permissions' => json_encode(['projects:view', 'tasks:view', 'expenses:view']),
        'risk_level' => 'low',
        'requires_confirm' => FALSE,
        'is_active' => TRUE
    ],
    [
        'action_id' => 'autolink_entities',
        'name' => 'Auto-link Entities',
        'description' => 'Automatically link unlinked entities to projects',
        'params_schema' => json_encode([
            'type' => 'object',
            'properties' => [
                'entity_type' => ['type' => 'string', 'enum' => ['tasks', 'expenses', 'timesheets']],
                'project_id' => ['type' => 'integer'],
                'auto_assign' => ['type' => 'boolean']
            ]
        ]),
        'permissions' => json_encode(['projects:edit', 'tasks:edit', 'expenses:edit']),
        'risk_level' => 'medium',
        'requires_confirm' => TRUE,
        'is_active' => TRUE
    ],
    [
        'action_id' => 'bill_tasks_to_invoice',
        'name' => 'Bill Tasks to Invoice',
        'description' => 'Bill tasks to existing invoice',
        'params_schema' => json_encode([
            'type' => 'object',
            'required' => ['invoice_id'],
            'properties' => [
                'invoice_id' => ['type' => 'integer', 'minimum' => 1],
                'task_ids' => ['type' => 'array', 'items' => ['type' => 'integer']],
                'timesheet_ids' => ['type' => 'array', 'items' => ['type' => 'integer']]
            ]
        ]),
        'permissions' => json_encode(['invoices:edit', 'tasks:view']),
        'risk_level' => 'medium',
        'requires_confirm' => TRUE,
        'is_active' => TRUE
    ],
    [
        'action_id' => 'create_reminder',
        'name' => 'Create Reminder',
        'description' => 'Create reminder for project or task',
        'params_schema' => json_encode([
            'type' => 'object',
            'required' => ['title', 'date'],
            'properties' => [
                'title' => ['type' => 'string', 'minLength' => 1],
                'description' => ['type' => 'string'],
                'date' => ['type' => 'string', 'format' => 'date'],
                'time' => ['type' => 'string'],
                'rel_type' => ['type' => 'string', 'enum' => ['project', 'task', 'invoice', 'estimate']],
                'rel_id' => ['type' => 'integer'],
                'notify_by_email' => ['type' => 'boolean'],
                'notify_by_sms' => ['type' => 'boolean']
            ]
        ]),
        'permissions' => json_encode(['reminders:create']),
        'risk_level' => 'low',
        'requires_confirm' => FALSE,
        'is_active' => TRUE
    ]
];

foreach ($default_actions as $action) {
    // Avoid duplicate PK errors if installer is re-run
    $exists = $CI->db->get_where(db_prefix() . 'project_agent_actions', ['action_id' => $action['action_id']])->row_array();
    if (!$exists) {
        $CI->db->insert(db_prefix() . 'project_agent_actions', $action);
    }
}

// Add foreign key constraints
$CI->db->query('ALTER TABLE `' . db_prefix() . 'project_agent_sessions` 
    ADD CONSTRAINT `fk_pa_sessions_project` 
    FOREIGN KEY (`project_id`) REFERENCES `' . db_prefix() . 'projects`(`id`) 
    ON DELETE SET NULL ON UPDATE CASCADE');

$CI->db->query('ALTER TABLE `' . db_prefix() . 'project_agent_sessions` 
    ADD CONSTRAINT `fk_pa_sessions_user` 
    FOREIGN KEY (`user_id`) REFERENCES `' . db_prefix() . 'staff`(`staffid`) 
    ON DELETE CASCADE ON UPDATE CASCADE');

$CI->db->query('ALTER TABLE `' . db_prefix() . 'project_agent_memory_entries` 
    ADD CONSTRAINT `fk_pa_entries_session` 
    FOREIGN KEY (`session_id`) REFERENCES `' . db_prefix() . 'project_agent_sessions`(`session_id`) 
    ON DELETE CASCADE ON UPDATE CASCADE');

$CI->db->query('ALTER TABLE `' . db_prefix() . 'project_agent_memory_entries` 
    ADD CONSTRAINT `fk_pa_entries_project` 
    FOREIGN KEY (`project_id`) REFERENCES `' . db_prefix() . 'projects`(`id`) 
    ON DELETE SET NULL ON UPDATE CASCADE');

$CI->db->query('ALTER TABLE `' . db_prefix() . 'project_agent_memory_entries` 
    ADD CONSTRAINT `fk_pa_entries_customer` 
    FOREIGN KEY (`customer_id`) REFERENCES `' . db_prefix() . 'clients`(`userid`) 
    ON DELETE SET NULL ON UPDATE CASCADE');

$CI->db->query('ALTER TABLE `' . db_prefix() . 'project_agent_action_logs` 
    ADD CONSTRAINT `fk_pa_logs_session` 
    FOREIGN KEY (`session_id`) REFERENCES `' . db_prefix() . 'project_agent_sessions`(`session_id`) 
    ON DELETE CASCADE ON UPDATE CASCADE');

$CI->db->query('ALTER TABLE `' . db_prefix() . 'project_agent_action_logs` 
    ADD CONSTRAINT `fk_pa_logs_action` 
    FOREIGN KEY (`action_id`) REFERENCES `' . db_prefix() . 'project_agent_actions`(`action_id`) 
    ON DELETE CASCADE ON UPDATE CASCADE');

$CI->db->query('ALTER TABLE `' . db_prefix() . 'project_agent_action_logs` 
    ADD CONSTRAINT `fk_pa_logs_executed_by` 
    FOREIGN KEY (`executed_by`) REFERENCES `' . db_prefix() . 'staff`(`staffid`) 
    ON DELETE CASCADE ON UPDATE CASCADE');

// Create indexes for better performance
$CI->db->query('CREATE INDEX `idx_pa_entries_scope_kind` ON `' . db_prefix() . 'project_agent_memory_entries` (`scope`, `kind`)');
$CI->db->query('CREATE INDEX `idx_pa_logs_status_executed` ON `' . db_prefix() . 'project_agent_action_logs` (`status`, `executed_at`)');
$CI->db->query('CREATE INDEX `idx_pa_logs_client_token` ON `' . db_prefix() . 'project_agent_action_logs` (`client_token`)');

// Add Chain-Of-Memory columns for fresh installs (idempotent)
try {
    $tblE = db_prefix() . 'project_agent_memory_entries';
    $fields = $CI->db->list_fields($tblE);
    if (!in_array('is_chain_selected', $fields)) {
        $CI->db->query('ALTER TABLE `'.$tblE.'` ADD COLUMN `is_chain_selected` TINYINT(1) NOT NULL DEFAULT 0 AFTER `entity_refs`');
    }
    if (!in_array('chain_priority', $fields)) {
        $CI->db->query('ALTER TABLE `'.$tblE.'` ADD COLUMN `chain_priority` INT NOT NULL DEFAULT 0 AFTER `is_chain_selected`');
    }
    if (!in_array('related_question_id', $fields)) {
        $CI->db->query('ALTER TABLE `'.$tblE.'` ADD COLUMN `related_question_id` VARCHAR(100) NULL AFTER `chain_priority`');
    }
} catch (\Throwable $e) { log_message('debug','PA install chain cols: '.$e->getMessage()); }

// Create chains table if missing (fresh install)
try {
    $tblC = db_prefix() . 'project_agent_memory_chains';
    if (!$CI->db->table_exists($tblC)) {
        $CI->dbforge->add_field([
            'id' => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => TRUE],
            'session_id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => TRUE, 'null' => FALSE],
            'question_id' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => FALSE],
            'memory_ids' => ['type' => 'TEXT', 'null' => FALSE],
            'created_at' => ['type' => 'DATETIME', 'null' => FALSE],
        ]);
        $CI->dbforge->add_key('id', TRUE);
        $CI->dbforge->add_key('session_id');
        $CI->dbforge->create_table($tblC, TRUE);
        try { $CI->db->query('ALTER TABLE `'.$tblC.'` ALTER `created_at` SET DEFAULT CURRENT_TIMESTAMP'); } catch (\Throwable $e2) {}
    }
} catch (\Throwable $e) { log_message('debug','PA install chain table: '.$e->getMessage()); }

// Create response actions table for fresh installs
try {
    $tblR = db_prefix() . 'project_agent_response_actions';
    if (!$CI->db->table_exists($tblR)) {
        $CI->dbforge->add_field([
            'id' => ['type'=>'INT','constraint'=>11,'auto_increment'=>TRUE],
            'response_id' => ['type'=>'VARCHAR','constraint'=>100,'null'=>FALSE],
            'session_id' => ['type'=>'INT','constraint'=>11,'null'=>FALSE],
            'action_id' => ['type'=>'VARCHAR','constraint'=>100,'null'=>FALSE],
            'action_name' => ['type'=>'VARCHAR','constraint'=>255,'null'=>FALSE],
            'parameters' => ['type'=>'TEXT','null'=>FALSE],
            'status' => ['type'=>"ENUM('pending','executing','completed','failed')",'null'=>FALSE,'default'=>'pending'],
            'execution_order' => ['type'=>'INT','null'=>FALSE,'default'=>0],
            'created_at' => ['type'=>'DATETIME','null'=>FALSE],
            'updated_at' => ['type'=>'DATETIME','null'=>FALSE],
        ]);
        $CI->dbforge->add_key('id', TRUE);
        $CI->dbforge->add_key(['response_id','session_id']);
        $CI->dbforge->create_table($tblR, TRUE);
    }
} catch (\Throwable $e) { log_message('debug','PA install response actions table: '.$e->getMessage()); }

// Log installation
log_message('info', 'Project Agent Module: Installation completed successfully');
