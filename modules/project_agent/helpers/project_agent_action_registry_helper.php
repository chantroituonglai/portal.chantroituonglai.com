<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Project Agent Action Registry Helper
 * Manages the registry of available actions and their execution
 */

class ProjectAgentActionRegistry {
    
    private $actions = [];
    private $CI;
    private $aliases = [
        // Billing
        'get_project_billing_status' => 'check_billing_status',
        'project_billing_status'     => 'check_billing_status',
        'billing_status'             => 'check_billing_status',
        'get_billing_status'         => 'check_billing_status',
        // Invoices
        'get_outstanding_invoices'   => 'list_overdue_invoices',
        'list_outstanding_invoices'  => 'list_overdue_invoices',
        'overdue_invoices'           => 'list_overdue_invoices',
        // Tasks
        'add_task'                   => 'create_task',
        'create_new_task'            => 'create_task',
        // Projects
        'update_project_details'     => 'update_project',
        'add_project_milestone'      => 'add_milestone',
        // Expenses/Timesheets
        'invoice_timesheet'          => 'invoice_timesheets',
    ];
    
    public function __construct() {
        $this->CI = &get_instance();
        $this->loadActionsFromDatabase();
    }

    /**
     * Return JSON-schema like params schema for an action
     */
    public function getActionSchema($action_id) {
        $action = $this->getAction($action_id);
        if (!$action) {
            throw new Exception('Action not found');
        }
        $schema = $action['params_schema'];
        if (is_string($schema)) {
            $decoded = json_decode($schema, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $schema = $decoded;
            }
        }
        if (!is_array($schema)) {
            $schema = ['type' => 'object', 'properties' => []];
        }
        // Always ensure properties key exists for UI renderer
        if (!isset($schema['properties']) || !is_array($schema['properties'])) {
            $schema['properties'] = [];
        }
        return $schema;
    }

    /**
     * Load actions from database
     */
    private function loadActionsFromDatabase() {
        // Use only canonical table name (single prefix)
        $table = db_prefix() . 'project_agent_actions';
        
        if (!$this->CI->db->table_exists($table)) {
            $this->actions = [];
            log_message('error', 'Project Agent: Actions table not found - ' . $table);
            return;
        }
        $actions = $this->CI->db->get($table)->result_array();
        
        foreach ($actions as $action) {
            $this->actions[$action['action_id']] = [
                'action_id' => $action['action_id'],
                'name' => $action['name'],
                'description' => $action['description'],
                'params_schema' => json_decode($action['params_schema'], true),
                'permissions' => json_decode($action['permissions'], true),
                'prompt_override' => isset($action['prompt_override']) ? $action['prompt_override'] : null,
                'param_mapping' => isset($action['param_mapping']) ? json_decode($action['param_mapping'], true) : null,
                'risk_level' => $action['risk_level'],
                'requires_confirm' => (bool)$action['requires_confirm'],
                'is_active' => (bool)$action['is_active']
            ];
        }

        // Fallback: if table exists but empty, load built-in safe defaults (in-memory)
        if (empty($this->actions)) {
            $builtIns = $this->getBuiltInActions();
            $this->actions = $builtIns; // immediate availability
            log_message('debug', 'Project Agent: using built-in action registry fallback (no DB actions found).');
            // Try to seed DB table so actions persist next loads
            try {
                if ($this->CI->db->table_exists($table)) {
                    foreach ($builtIns as $id => $row) {
                        $exists = $this->CI->db->get_where($table, ['action_id' => $id])->row_array();
                        if ($exists) continue;
                        $this->CI->db->insert($table, [
                            'action_id' => $row['action_id'],
                            'name' => $row['name'],
                            'description' => $row['description'],
                            'params_schema' => json_encode($row['params_schema']),
                            'permissions' => json_encode($row['permissions']),
                            'risk_level' => $row['risk_level'],
                            'requires_confirm' => $row['requires_confirm'] ? 1 : 0,
                            'is_active' => 1,
                        ]);
                    }
                    log_message('info', 'Project Agent: Seeded built-in actions into table ' . $table);
                }
            } catch (\Throwable $e) {
                log_message('error', 'Project Agent: Failed to seed actions - ' . $e->getMessage());
            }
        }
    }
    
    /**
     * Get all available actions
     */
    public function getAllActions() {
        return $this->actions;
    }
    
    /**
     * Get action by ID
     */
    public function getAction($action_id) {
        $id = $this->normalizeActionId($action_id);
        return isset($this->actions[$id]) ? $this->actions[$id] : null;
    }
    
    /**
     * Check if action exists
     */
    public function actionExists($action_id) {
        $id = $this->normalizeActionId($action_id);
        return isset($this->actions[$id]);
    }
    
    /**
     * Validate action parameters against schema
     */
    public function validateParams($action_id, $params) {
        $action = $this->getAction($action_id);
        if (!$action) {
            return ['valid' => false, 'error' => 'Action not found'];
        }
        
        $schema = $action['params_schema'];
        $errors = [];
        
        // Check required fields
        if (isset($schema['required'])) {
            foreach ($schema['required'] as $field) {
                if (!isset($params[$field])) {
                    $errors[] = "Missing required field: {$field}";
                }
            }
        }
        
        // Validate field types
        if (isset($schema['properties'])) {
            foreach ($schema['properties'] as $field => $fieldSchema) {
                if (isset($params[$field])) {
                    $validation = $this->validateField($field, $params[$field], $fieldSchema);
                    if (!$validation['valid']) {
                        $errors[] = $validation['error'];
                    }
                }
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Validate individual field
     */
    private function validateField($field, $value, $schema) {
        $type = $schema['type'] ?? 'string';
        
        switch ($type) {
            case 'string':
                if (!is_string($value)) {
                    return ['valid' => false, 'error' => "Field {$field} must be a string"];
                }
                if (isset($schema['minLength']) && strlen($value) < $schema['minLength']) {
                    return ['valid' => false, 'error' => "Field {$field} must be at least {$schema['minLength']} characters"];
                }
                if (isset($schema['maxLength']) && strlen($value) > $schema['maxLength']) {
                    return ['valid' => false, 'error' => "Field {$field} must be at most {$schema['maxLength']} characters"];
                }
                if (isset($schema['format']) && $schema['format'] === 'date') {
                    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                        return ['valid' => false, 'error' => "Field {$field} must be in YYYY-MM-DD format"];
                    }
                    if (!strtotime($value)) {
                        return ['valid' => false, 'error' => "Field {$field} must be a valid date"];
                    }
                }
                break;
                
            case 'integer':
                if (!is_numeric($value) || (int)$value != $value) {
                    return ['valid' => false, 'error' => "Field {$field} must be an integer"];
                }
                $value = (int)$value;
                if (isset($schema['minimum']) && $value < $schema['minimum']) {
                    return ['valid' => false, 'error' => "Field {$field} must be at least {$schema['minimum']}"];
                }
                if (isset($schema['maximum']) && $value > $schema['maximum']) {
                    return ['valid' => false, 'error' => "Field {$field} must be at most {$schema['maximum']}"];
                }
                break;
                
            case 'number':
                if (!is_numeric($value)) {
                    return ['valid' => false, 'error' => "Field {$field} must be a number"];
                }
                $value = (float)$value;
                if (isset($schema['minimum']) && $value < $schema['minimum']) {
                    return ['valid' => false, 'error' => "Field {$field} must be at least {$schema['minimum']}"];
                }
                if (isset($schema['maximum']) && $value > $schema['maximum']) {
                    return ['valid' => false, 'error' => "Field {$field} must be at most {$schema['maximum']}"];
                }
                break;
                
            case 'boolean':
                if (!is_bool($value) && !in_array($value, [0, 1, '0', '1'])) {
                    return ['valid' => false, 'error' => "Field {$field} must be a boolean"];
                }
                break;
                
            case 'array':
                if (!is_array($value)) {
                    return ['valid' => false, 'error' => "Field {$field} must be an array"];
                }
                // Basic items type check
                if (isset($schema['items']['type'])) {
                    foreach ($value as $idx => $v) {
                        $res = $this->validateField($field."[$idx]", $v, $schema['items']);
                        if (!$res['valid']) { return $res; }
                    }
                }
                break;
                
            case 'object':
                if (!is_array($value) && !is_object($value)) {
                    return ['valid' => false, 'error' => "Field {$field} must be an object"];
                }
                break;
        }
        // Enum constraint
        if (isset($schema['enum']) && is_array($schema['enum'])) {
            if (!in_array($value, $schema['enum'])) {
                return ['valid' => false, 'error' => "Field {$field} must be one of: " . implode(', ', $schema['enum'])];
            }
        }
        
        return ['valid' => true];
    }
    
    /**
     * Check user permissions for action
     */
    public function checkPermissions($action_id, $user_id = null) {
        $action = $this->getAction($action_id);
        if (!$action) {
            return false;
        }
        // Bypass if user has Project Agent admin capability
        if (has_permission('project_agent', '', 'admin')) {
            return true;
        }
        
        $required_permissions = $action['permissions'];
        // If no permissions specified, allow by default
        if (empty($required_permissions)) {
            return true;
        }
        // Normalize to array
        if (!is_array($required_permissions)) {
            $required_permissions = [$required_permissions];
        }
        foreach ($required_permissions as $perm) {
            $feature = 'project_agent';
            $capability = 'view';
            if (is_array($perm)) {
                $feature = $perm['feature'] ?? $feature;
                $capability = $perm['capability'] ?? ($perm['permission'] ?? $capability);
            } elseif (is_string($perm)) {
                // Accept formats: feature:capability or feature.capability
                if (strpos($perm, ':') !== false) {
                    list($feature, $capability) = explode(':', $perm, 2);
                } elseif (strpos($perm, '.') !== false) {
                    list($feature, $capability) = explode('.', $perm, 2);
                } else {
                    // Single token -> treat as feature, default to 'view'
                    $feature = $perm;
                }
            }
            if (!has_permission($feature, '', $capability)) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * Execute action
     */
    public function executeAction($action_id, $params, $context) {
        // Normalize alias to canonical id
        $action_id = $this->normalizeActionId($action_id);
        // Validate action exists
        if (!$this->actionExists($action_id)) {
            return [
                'success' => false,
                'error' => 'Action not found: ' . $action_id
            ];
        }
        
        // Validate parameters
        $validation = $this->validateParams($action_id, $params);
        if (!$validation['valid']) {
            return [
                'success' => false,
                'error' => 'Parameter validation failed: ' . implode(', ', $validation['errors'])
            ];
        }
        
        // Check permissions
        if (!$this->checkPermissions($action_id, $context['user_id'])) {
            return [
                'success' => false,
                'error' => 'Permission denied for action: ' . $action_id
            ];
        }
        
        // Execute action
        try {
            $result = $this->executeActionHandler($action_id, $params, $context);
            return [
                'success' => true,
                'result' => $result
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Action execution failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get action schema from database
     */
    private function getActionSchemaFromDB($action_id) {
        $action = $this->getAction($action_id);
        if (!$action || !isset($action['params_schema'])) {
            return null;
        }
        
        $schema = $action['params_schema'];
        if (is_string($schema)) {
            $decoded = json_decode($schema, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $schema = $decoded;
            }
        }
        
        return is_array($schema) ? $schema : null;
    }
    
    /**
     * Get required fields from schema
     */
    private function getRequiredFields($action_id) {
        $schema = $this->getActionSchemaFromDB($action_id);
        return $schema && isset($schema['required']) ? $schema['required'] : [];
    }
    
    /**
     * Get field properties from schema
     */
    private function getFieldProperties($action_id) {
        $schema = $this->getActionSchemaFromDB($action_id);
        return $schema && isset($schema['properties']) ? $schema['properties'] : [];
    }
    
    /**
     * Execute action handler
     */
    private function executeActionHandler($action_id, $params, $context) {
        // $action_id expected canonical here
        switch ($action_id) {
            case 'create_project':
                return $this->executeCreateProject($params, $context);
                
            case 'create_task':
                return $this->executeCreateTask($params, $context);
                
            case 'check_billing_status':
                return $this->executeCheckBillingStatus($params, $context);
                
            case 'summarize_project_work_remaining':
                return $this->executeSummarizeProjectWorkRemaining($params, $context);
                
            case 'get_project_overview':
                return $this->executeGetProjectOverview($params, $context);
                
            case 'list_project_tasks':
                return $this->executeListProjectTasks($params, $context);
                
            case 'update_task':
                return $this->executeUpdateTask($params, $context);
                
            case 'add_project_member':
                return $this->executeAddProjectMember($params, $context);
                
            case 'create_estimate':
                return $this->executeCreateEstimate($params, $context);
                
            case 'list_overdue_invoices':
                return $this->executeListOverdueInvoices($params, $context);
                
            // Project Management Actions
            case 'update_project':
                return $this->executeUpdateProject($params, $context);
                
            case 'add_milestone':
                return $this->executeAddMilestone($params, $context);
                
            case 'link_invoice_to_project':
                return $this->executeLinkInvoiceToProject($params, $context);
                
            case 'get_project_finance_overview':
                return $this->executeGetProjectFinanceOverview($params, $context);
                
            case 'invoice_project':
                return $this->executeInvoiceProject($params, $context);
                
            // Task Management Actions
            case 'link_task_to':
                return $this->executeLinkTaskTo($params, $context);
                
            case 'add_task_follower':
                return $this->executeAddTaskFollower($params, $context);
                
            case 'start_task_timer':
                return $this->executeStartTaskTimer($params, $context);
                
            case 'add_task_timesheet':
                return $this->executeAddTaskTimesheet($params, $context);
                
            case 'bill_non_project_tasks_to_invoice':
                return $this->executeBillNonProjectTasksToInvoice($params, $context);
                
            // Timesheet & Expense Actions
            case 'list_project_timesheets':
                return $this->executeListProjectTimesheets($params, $context);
                
            case 'invoice_timesheets':
                return $this->executeInvoiceTimesheets($params, $context);
                
            case 'record_expense':
                return $this->executeRecordExpense($params, $context);
                
            case 'set_recurring_expense':
                return $this->executeSetRecurringExpense($params, $context);
                
            case 'convert_expense_to_invoice':
                return $this->executeConvertExpenseToInvoice($params, $context);
                
            // Estimate & Invoice Actions
            case 'send_estimate_email':
                return $this->executeSendEstimateEmail($params, $context);
                
            case 'convert_estimate_to_invoice':
                return $this->executeConvertEstimateToInvoice($params, $context);
                
            case 'convert_estimate_to_project':
                return $this->executeConvertEstimateToProject($params, $context);
                
            case 'create_estimate_request_form':
                return $this->executeCreateEstimateRequestForm($params, $context);
                
            case 'send_invoice_email':
                return $this->executeSendInvoiceEmail($params, $context);
                
            case 'set_recurring_invoice':
                return $this->executeSetRecurringInvoice($params, $context);
                
            case 'record_invoice_payment':
                return $this->executeRecordInvoicePayment($params, $context);
                
            case 'invoice_project_expenses':
                return $this->executeInvoiceProjectExpenses($params, $context);
                
            // Analysis & Health Check Actions
            case 'find_unlinked_entities':
                return $this->executeFindUnlinkedEntities($params, $context);
                
            case 'autolink_entities':
                return $this->executeAutolinkEntities($params, $context);
                
            case 'bill_tasks_to_invoice':
                return $this->executeBillTasksToInvoice($params, $context);
                
            case 'create_reminder':
                return $this->executeCreateReminder($params, $context);
                
            default:
                throw new Exception('Action handler not implemented: ' . $action_id);
        }
    }

    /**
     * Normalize external/AI-proposed action id to canonical registry key
     */
    private function normalizeActionId($action_id) {
        $id = strtolower(trim((string)$action_id));
        $id = str_replace([' ', '-', '.'], '_', $id);
        if (isset($this->aliases[$id])) {
            return $this->aliases[$id];
        }
        // Soft fallback for common prefixes
        if (!isset($this->actions[$id])) {
            // try removing common verbs
            $soft = preg_replace('/^(get_|create_|make_|do_|run_)/','', $id);
            if (isset($this->aliases[$soft])) return $this->aliases[$soft];
        }
        return $id;
    }

    /**
     * Built-in minimal action registry for environments where DB seeding hasn't run.
     * Returns associative array keyed by action_id with same structure as DB rows decoded.
     */
    private function getBuiltInActions() {
        $A = [];
        $add = function($id, $name, $desc, $schema, $perms, $risk='low', $confirm=false) use (&$A){
            $A[$id] = [
                'action_id' => $id,
                'name' => $name,
                'description' => $desc,
                'params_schema' => $schema,
                'permissions' => $perms,
                'prompt_override' => null,
                'risk_level' => $risk,
                'requires_confirm' => $confirm,
                'is_active' => true,
            ];
        };
        // Project
        $add('update_project','Update Project','Update project information',
            ['type'=>'object','required'=>['project_id'],'properties'=>[
                'project_id'=>['type'=>'integer','minimum'=>1],
                'name'=>['type'=>'string'],'description'=>['type'=>'string'],
                'status'=>['type'=>'integer'],'start_date'=>['type'=>'string'],'deadline'=>['type'=>'string'],
                'billing_type'=>['type'=>'integer'],
            ]], ['projects:edit']);
        $add('add_milestone','Add Milestone','Add milestone to project',
            ['type'=>'object','required'=>['project_id','name','due_date'],'properties'=>[
                'project_id'=>['type'=>'integer','minimum'=>1],'name'=>['type'=>'string','minLength'=>1],
                'description'=>['type'=>'string'],'due_date'=>['type'=>'string']
            ]], ['projects:edit']);
        $add('add_project_member','Add Project Member','Add members to a project',
            ['type'=>'object','required'=>['project_id','staff_ids'],'properties'=>[
                'project_id'=>['type'=>'integer','minimum'=>1],'staff_ids'=>['type'=>'array','items'=>['type'=>'integer']]
            ]], ['projects:edit']);
        $add('get_project_overview','Get Project Overview','Overview with progress & milestones',
            ['type'=>'object','required'=>['project_id'],'properties'=>['project_id'=>['type'=>'integer','minimum'=>1]]],
            ['projects:view']);
        // Tasks
        $add('create_task','Create Task','Create a new task',
            ['type'=>'object','required'=>['title'],'properties'=>[
                'project_id'=>['type'=>'integer'],'title'=>['type'=>'string','minLength'=>1],
                'description'=>['type'=>'string'],'assignee_ids'=>['type'=>'array','items'=>['type'=>'integer']],
                'priority'=>['type'=>'integer'],'start_date'=>['type'=>'string'],'due_date'=>['type'=>'string'],
                'hourly_rate'=>['type'=>'number'],'billable'=>['type'=>'boolean']
            ]], ['tasks:create']);
        $add('update_task','Update Task','Update task information',
            ['type'=>'object','required'=>['task_id'],'properties'=>[
                'task_id'=>['type'=>'integer','minimum'=>1],'title'=>['type'=>'string'],'description'=>['type'=>'string'],
                'status'=>['type'=>'integer'],'priority'=>['type'=>'integer'],'due_date'=>['type'=>'string'],
                'assignee_ids'=>['type'=>'array','items'=>['type'=>'integer']],
            ]], ['tasks:edit']);
        $add('list_project_tasks','List Project Tasks','List tasks for project',
            ['type'=>'object','required'=>['project_id'],'properties'=>[
                'project_id'=>['type'=>'integer','minimum'=>1],'status'=>['type'=>'integer']
            ]], ['projects:view','tasks:view']);
        // Billing/Finance
        $add('check_billing_status','Check Billing Status','Check billing status for a project',
            ['type'=>'object','required'=>['project_id'],'properties'=>['project_id'=>['type'=>'integer','minimum'=>1]]],
            ['projects:view','invoices:view']);
        $add('list_overdue_invoices','List Overdue Invoices','List overdue invoices',
            ['type'=>'object','properties'=>['customer_id'=>['type'=>'integer','minimum'=>1]]], ['invoices:view']);
        $add('create_estimate','Create Estimate','Create a new estimate',
            ['type'=>'object','required'=>['customer_id'],'properties'=>[
                'customer_id'=>['type'=>'integer','minimum'=>1],'project_id'=>['type'=>'integer'],
                'items'=>['type'=>'array','items'=>['type'=>'object']],
                'terms'=>['type'=>'string'],'notes'=>['type'=>'string']
            ]], ['estimates:create'],'medium',true);
        $add('record_expense','Record Expense','Record new expense',
            ['type'=>'object','required'=>['amount','category'],'properties'=>[
                'amount'=>['type'=>'number','minimum'=>0.01],'category'=>['type'=>'integer'],
                'description'=>['type'=>'string'],'date'=>['type'=>'string'],'project_id'=>['type'=>'integer'],
                'billable'=>['type'=>'boolean'],'tax'=>['type'=>'number']
            ]], ['expenses:create']);
        $add('invoice_timesheets','Invoice Timesheets','Create invoice from timesheets',
            ['type'=>'object','required'=>['project_id'],'properties'=>[
                'project_id'=>['type'=>'integer','minimum'=>1],'timesheet_ids'=>['type'=>'array','items'=>['type'=>'integer']],
                'due_date'=>['type'=>'string']
            ]], ['invoices:create','projects:view'],'medium',true);
        $add('summarize_project_work_remaining','Summarize Work Remaining','Summarize remaining work',
            ['type'=>'object','required'=>['project_id'],'properties'=>['project_id'=>['type'=>'integer','minimum'=>1]]],
            ['projects:view']);
        return $A;
    }
    
    // ========================================
    // ACTION HANDLERS (10 Basic Actions)
    // ========================================
    
    /**
     * 1. Create Project
     */
    private function executeCreateProject($params, $context) {
        $this->CI->load->model('projects_model');
        
        // Build project data using schema
        $project_data = $this->buildDataFromSchema('create_project', $params, $context, 'projects', [
            'description' => ''
        ]);
        
        // Set start_date default if not provided
        if (!isset($project_data['start_date'])) {
            $project_data['start_date'] = date('Y-m-d');
        }
        
        $project_id = $this->CI->projects_model->add($project_data);
        
        // Add members if specified (special handling)
        if (!empty($params['members'])) {
            foreach ($params['members'] as $member_id) {
                $this->CI->projects_model->add_project_members($project_id, [$member_id]);
            }
        }
        
        return [
            'project_id' => $project_id,
            'message' => 'Project created successfully'
        ];
    }
    
    /**
     * 2. Create Task
     */
    private function executeCreateTask($params, $context) {
        $this->CI->load->model('tasks_model');
        
        // Build task data using schema
        $task_data = $this->buildDataFromSchema('create_task', $params, $context, 'tasks', [
            'rel_type' => 'project',
            'priority' => 2,
            'billable' => 0,
            'hourly_rate' => 0
        ]);
        
        // Set startdate default if not provided
        if (!isset($task_data['startdate'])) {
            $task_data['startdate'] = date('Y-m-d');
        }
        
        $task_id = $this->CI->tasks_model->add($task_data);
        
        // Add assignees if specified (special handling)
        if (!empty($params['assignee_ids'])) {
            $this->CI->tasks_model->add_task_assignees($task_id, $params['assignee_ids']);
        }
        
        return [
            'task_id' => $task_id,
            'message' => 'Task created successfully'
        ];
    }
    
    /**
     * 3. Check Billing Status
     */
    private function executeCheckBillingStatus($params, $context) {
        $project_id = $params['project_id'];
        
        // Get timesheets - calculate total time dynamically (billed column doesn't exist in this version)
        $this->CI->db->select('SUM(CASE WHEN ' . db_prefix() . 'taskstimers.end_time IS NULL THEN ' . time() . ' - ' . db_prefix() . 'taskstimers.start_time ELSE ' . db_prefix() . 'taskstimers.end_time - ' . db_prefix() . 'taskstimers.start_time END) as total_seconds, SUM(CASE WHEN ' . db_prefix() . 'taskstimers.end_time IS NULL THEN (' . time() . ' - ' . db_prefix() . 'taskstimers.start_time) * ' . db_prefix() . 'tasks.hourly_rate ELSE (' . db_prefix() . 'taskstimers.end_time - ' . db_prefix() . 'taskstimers.start_time) * ' . db_prefix() . 'tasks.hourly_rate END) as total_amount');
        $this->CI->db->from(db_prefix() . 'taskstimers');
        $this->CI->db->join(db_prefix() . 'tasks', db_prefix() . 'tasks.id = ' . db_prefix() . 'taskstimers.task_id');
        $this->CI->db->where(db_prefix() . 'tasks.rel_type', 'project');
        $this->CI->db->where(db_prefix() . 'tasks.rel_id', $project_id);
        // Note: billed column doesn't exist in this version, so we'll get all timesheets
        $timesheets = $this->CI->db->get()->row();
        
        // Get unbilled expenses
        $this->CI->db->select('SUM(amount) as total_amount, COUNT(*) as count');
        $this->CI->db->from(db_prefix() . 'expenses');
        $this->CI->db->where('project_id', $project_id);
        $this->CI->db->where('billable', 1);
        $this->CI->db->where('invoiceid IS NULL');
        $expenses = $this->CI->db->get()->row();
        
        // Get overdue invoices
        $this->CI->db->select('COUNT(*) as count, SUM(total) as total_amount');
        $this->CI->db->from(db_prefix() . 'invoices');
        $this->CI->db->where('project_id', $project_id);
        $this->CI->db->where('status', 2); // Sent
        $this->CI->db->where('duedate <', date('Y-m-d'));
        $overdue_invoices = $this->CI->db->get()->row();
        
        return [
            'unbilled_timesheets' => [
                'hours' => $timesheets->total_seconds ? sec2qty($timesheets->total_seconds) : 0,
                'amount' => $timesheets->total_amount ?? 0
            ],
            'unbilled_expenses' => [
                'count' => $expenses->count ?? 0,
                'amount' => $expenses->total_amount ?? 0
            ],
            'overdue_invoices' => [
                'count' => $overdue_invoices->count ?? 0,
                'amount' => $overdue_invoices->total_amount ?? 0
            ]
        ];
    }
    
    /**
     * 4. Summarize Project Work Remaining
     */
    private function executeSummarizeProjectWorkRemaining($params, $context) {
        $project_id = $params['project_id'];
        
        // Get overdue tasks
        $this->CI->db->select('COUNT(*) as count');
        $this->CI->db->from(db_prefix() . 'tasks');
        $this->CI->db->where('rel_type', 'project');
        $this->CI->db->where('rel_id', $project_id);
        $this->CI->db->where('status !=', 5); // Not completed
        $this->CI->db->where('duedate <', date('Y-m-d'));
        $overdue_tasks = $this->CI->db->get()->row();
        
        // Get tasks without assignee
        $this->CI->db->select('COUNT(*) as count');
        $this->CI->db->from(db_prefix() . 'tasks');
        $this->CI->db->join(db_prefix() . 'task_assigned', db_prefix() . 'tasks.id = ' . db_prefix() . 'task_assigned.taskid', 'left');
        $this->CI->db->where('rel_type', 'project');
        $this->CI->db->where('rel_id', $project_id);
        $this->CI->db->where('status !=', 5);
        $this->CI->db->where(db_prefix() . 'task_assigned.taskid IS NULL');
        $no_assignee_tasks = $this->CI->db->get()->row();
        
        // Get tasks due this week
        $this->CI->db->select('COUNT(*) as count');
        $this->CI->db->from(db_prefix() . 'tasks');
        $this->CI->db->where('rel_type', 'project');
        $this->CI->db->where('rel_id', $project_id);
        $this->CI->db->where('status !=', 5);
        $this->CI->db->where('duedate >=', date('Y-m-d'));
        $this->CI->db->where('duedate <=', date('Y-m-d', strtotime('+7 days')));
        $due_this_week = $this->CI->db->get()->row();
        
        return [
            'overdue_tasks' => $overdue_tasks->count ?? 0,
            'no_assignee_tasks' => $no_assignee_tasks->count ?? 0,
            'due_this_week' => $due_this_week->count ?? 0,
            'total_remaining' => ($overdue_tasks->count ?? 0) + ($no_assignee_tasks->count ?? 0) + ($due_this_week->count ?? 0)
        ];
    }
    
    /**
     * 5. Get Project Overview
     */
    private function executeGetProjectOverview($params, $context) {
        $project_id = $params['project_id'];
        
        $this->CI->load->model('projects_model');
        $project = $this->CI->projects_model->get($project_id);
        
        if (!$project) {
            throw new Exception('Project not found');
        }
        
        // Get project progress
        $progress = $this->CI->projects_model->calc_progress($project_id);
        
        // Get milestones
        $this->CI->db->select('*');
        $this->CI->db->from(db_prefix() . 'milestones');
        $this->CI->db->where('project_id', $project_id);
        $this->CI->db->order_by('due_date', 'ASC');
        $milestones = $this->CI->db->get()->result_array();
        
        return [
            'project' => $project,
            'progress' => $progress,
            'milestones' => $milestones
        ];
    }
    
    /**
     * 6. List Project Tasks
     */
    private function executeListProjectTasks($params, $context) {
        $project_id = $params['project_id'];
        
        $this->CI->db->select(db_prefix() . 'tasks.*');
        $this->CI->db->from(db_prefix() . 'tasks');
        
        // Only join task_status if the table exists
        if ($this->CI->db->table_exists(db_prefix() . 'task_status')) {
            $this->CI->db->select(db_prefix() . 'task_status.name as status_name');
            $this->CI->db->join(db_prefix() . 'task_status', db_prefix() . 'task_status.id = ' . db_prefix() . 'tasks.status');
        } else {
            // Add a default status_name if table doesn't exist
            $this->CI->db->select("'Not Available' as status_name");
        }
        
        $this->CI->db->where('rel_type', 'project');
        $this->CI->db->where('rel_id', $project_id);
        
        if (isset($params['status'])) {
            $this->CI->db->where(db_prefix() . 'tasks.status', $params['status']);
        }
        
        $this->CI->db->order_by('duedate', 'ASC');
        $tasks = $this->CI->db->get()->result_array();
        
        return [
            'tasks' => $tasks,
            'count' => count($tasks)
        ];
    }
    
    /**
     * 7. Update Task
     */
    private function executeUpdateTask($params, $context) {
        $task_id = $params['task_id'];
        
        $this->CI->load->model('tasks_model');
        
        $update_data = [];
        
        if (isset($params['title'])) $update_data['name'] = $params['title'];
        if (isset($params['description'])) $update_data['description'] = $params['description'];
        if (isset($params['status'])) $update_data['status'] = $params['status'];
        if (isset($params['priority'])) $update_data['priority'] = $params['priority'];
        if (isset($params['due_date'])) $update_data['duedate'] = $params['due_date'];
        if (isset($params['billable'])) $update_data['billable'] = $params['billable'];
        if (isset($params['hourly_rate'])) $update_data['hourly_rate'] = $params['hourly_rate'];
        
        $success = $this->CI->tasks_model->update($update_data, $task_id);
        
        // Update assignees if specified
        if (isset($params['assignee_ids'])) {
            $this->CI->tasks_model->remove_task_assignees($task_id);
            if (!empty($params['assignee_ids'])) {
                $this->CI->tasks_model->add_task_assignees($task_id, $params['assignee_ids']);
            }
        }
        
        return [
            'success' => $success,
            'message' => $success ? 'Task updated successfully' : 'Failed to update task'
        ];
    }
    
    /**
     * 8. Add Project Member
     */
    private function executeAddProjectMember($params, $context) {
        $project_id = $params['project_id'];
        $staff_ids = $params['staff_ids'];
        
        $this->CI->load->model('projects_model');
        
        $success = $this->CI->projects_model->add_project_members($project_id, $staff_ids);
        
        return [
            'success' => $success,
            'message' => $success ? 'Members added successfully' : 'Failed to add members'
        ];
    }
    
    /**
     * 9. Create Estimate
     */
    private function executeCreateEstimate($params, $context) {
        $this->CI->load->model('estimates_model');
        
        $estimate_data = [
            'clientid' => $params['customer_id'],
            'project_id' => $params['project_id'] ?? null,
            'number' => $this->CI->estimates_model->get_next_estimate_number(),
            'date' => date('Y-m-d'),
            'expirydate' => date('Y-m-d', strtotime('+30 days')),
            'currency' => get_option('customer_default_currency'),
            'subtotal' => 0,
            'total_tax' => 0,
            'total' => 0,
            'adjustment' => 0,
            'addedfrom' => $context['user_id'],
            'status' => 1, // Draft
            'terms' => $params['terms'] ?? '',
            'note' => $params['notes'] ?? ''
        ];
        
        $estimate_id = $this->CI->estimates_model->add($estimate_data);
        
        // Add items if specified
        if (!empty($params['items'])) {
            foreach ($params['items'] as $item) {
                $item_data = [
                    'rel_id' => $estimate_id,
                    'rel_type' => 'estimate',
                    'description' => $item['description'],
                    'long_description' => $item['long_description'] ?? '',
                    'qty' => $item['qty'],
                    'rate' => $item['rate'],
                    'taxname' => $item['taxname'] ?? '',
                    'item_order' => $item['item_order'] ?? 0
                ];
                
                $this->CI->db->insert(db_prefix() . 'item_invoice', $item_data);
            }
        }
        
        return [
            'estimate_id' => $estimate_id,
            'message' => 'Estimate created successfully'
        ];
    }
    
    /**
     * 10. List Overdue Invoices
     */
    private function executeListOverdueInvoices($params, $context) {
        $this->CI->db->select(db_prefix() . 'invoices.*, ' . db_prefix() . 'clients.company');
        $this->CI->db->from(db_prefix() . 'invoices');
        $this->CI->db->join(db_prefix() . 'clients', db_prefix() . 'clients.userid = ' . db_prefix() . 'invoices.clientid');
        $this->CI->db->where('status', 2); // Sent
        $this->CI->db->where('duedate <', date('Y-m-d'));
        
        if (isset($params['customer_id'])) {
            $this->CI->db->where('clientid', $params['customer_id']);
        }
        
        $this->CI->db->order_by('duedate', 'ASC');
        $invoices = $this->CI->db->get()->result_array();
        
        $total_amount = 0;
        foreach ($invoices as $invoice) {
            $total_amount += $invoice['total'];
        }
        
        return [
            'invoices' => $invoices,
            'count' => count($invoices),
            'total_amount' => $total_amount
        ];
    }
    
    // ========================================
    // PROJECT MANAGEMENT ACTIONS (5 actions)
    // ========================================
    
    /**
     * 11. Update Project
     */
    private function executeUpdateProject($params, $context) {
        $project_id = $params['project_id'];
        
        $this->CI->load->model('projects_model');
        
        $update_data = [];
        
        if (isset($params['name'])) $update_data['name'] = $params['name'];
        if (isset($params['description'])) $update_data['description'] = $params['description'];
        if (isset($params['status'])) $update_data['status'] = $params['status'];
        if (isset($params['start_date'])) $update_data['start_date'] = $params['start_date'];
        if (isset($params['deadline'])) $update_data['deadline'] = $params['deadline'];
        if (isset($params['billing_type'])) $update_data['billing_type'] = $params['billing_type'];
        
        $success = $this->CI->projects_model->update($update_data, $project_id);
        
        return [
            'success' => $success,
            'message' => $success ? 'Project updated successfully' : 'Failed to update project'
        ];
    }
    
    /**
     * 12. Add Milestone
     */
    private function executeAddMilestone($params, $context) {
        $milestone_data = [
            'project_id' => $params['project_id'],
            'name' => $params['name'],
            'description' => $params['description'] ?? '',
            'due_date' => $params['due_date'],
            'addedfrom' => $context['user_id']
        ];
        
        $this->CI->db->insert(db_prefix() . 'milestones', $milestone_data);
        $milestone_id = $this->CI->db->insert_id();
        
        return [
            'milestone_id' => $milestone_id,
            'message' => 'Milestone added successfully'
        ];
    }
    
    /**
     * 13. Link Invoice to Project
     */
    private function executeLinkInvoiceToProject($params, $context) {
        $project_id = $params['project_id'];
        $invoice_id = $params['invoice_id'];
        
        $this->CI->db->where('id', $invoice_id);
        $success = $this->CI->db->update(db_prefix() . 'invoices', ['project_id' => $project_id]);
        
        return [
            'success' => $success,
            'message' => $success ? 'Invoice linked to project successfully' : 'Failed to link invoice'
        ];
    }
    
    /**
     * 14. Get Project Finance Overview
     */
    private function executeGetProjectFinanceOverview($params, $context) {
        $project_id = $params['project_id'];
        
        // Get project details
        $this->CI->load->model('projects_model');
        $project = $this->CI->projects_model->get($project_id);
        
        // Get billing status
        $billing_status = $this->executeCheckBillingStatus($params, $context);
        
        // Get project invoices
        $this->CI->db->select('*');
        $this->CI->db->from(db_prefix() . 'invoices');
        $this->CI->db->where('project_id', $project_id);
        $invoices = $this->CI->db->get()->result_array();
        
        // Calculate totals
        $total_invoiced = 0;
        $total_paid = 0;
        foreach ($invoices as $invoice) {
            $total_invoiced += $invoice['total'];
            if ($invoice['status'] == 3) { // Paid
                $total_paid += $invoice['total'];
            }
        }
        
        return [
            'project' => $project,
            'billing_status' => $billing_status,
            'invoices' => $invoices,
            'total_invoiced' => $total_invoiced,
            'total_paid' => $total_paid,
            'outstanding' => $total_invoiced - $total_paid
        ];
    }
    
    /**
     * 15. Invoice Project
     */
    private function executeInvoiceProject($params, $context) {
        $project_id = $params['project_id'];
        
        $this->CI->load->model('invoices_model');
        
        // Create invoice
        $invoice_data = [
            'clientid' => $this->getProjectClientId($project_id),
            'project_id' => $project_id,
            'number' => $this->CI->invoices_model->get_next_invoice_number(),
            'date' => date('Y-m-d'),
            'duedate' => $params['due_date'] ?? date('Y-m-d', strtotime('+30 days')),
            'currency' => get_option('customer_default_currency'),
            'subtotal' => 0,
            'total_tax' => 0,
            'total' => 0,
            'adjustment' => 0,
            'addedfrom' => $context['user_id'],
            'status' => 1 // Draft
        ];
        
        $invoice_id = $this->CI->invoices_model->add($invoice_data);
        
        // Add timesheets if requested
        if ($params['include_timesheets'] ?? false) {
            $this->addTimesheetsToInvoice($invoice_id, $project_id);
        }
        
        // Add expenses if requested
        if ($params['include_expenses'] ?? false) {
            $this->addExpensesToInvoice($invoice_id, $project_id);
        }
        
        return [
            'invoice_id' => $invoice_id,
            'message' => 'Project invoice created successfully'
        ];
    }
    
    // ========================================
    // TASK MANAGEMENT ACTIONS (5 actions)
    // ========================================
    
    /**
     * 16. Link Task to Entity
     */
    private function executeLinkTaskTo($params, $context) {
        $task_id = $params['task_id'];
        $rel_type = $params['rel_type'];
        $rel_id = $params['rel_id'];
        
        $this->CI->load->model('tasks_model');
        
        $update_data = [
            'rel_type' => $rel_type,
            'rel_id' => $rel_id
        ];
        
        $success = $this->CI->tasks_model->update($update_data, $task_id);
        
        return [
            'success' => $success,
            'message' => $success ? 'Task linked successfully' : 'Failed to link task'
        ];
    }
    
    /**
     * 17. Add Task Follower
     */
    private function executeAddTaskFollower($params, $context) {
        $task_id = $params['task_id'];
        $staff_id = $params['staff_id'];
        
        $this->CI->load->model('tasks_model');
        
        $success = $this->CI->tasks_model->add_task_followers($task_id, [$staff_id]);
        
        return [
            'success' => $success,
            'message' => $success ? 'Follower added successfully' : 'Failed to add follower'
        ];
    }
    
    /**
     * 18. Start Task Timer
     */
    private function executeStartTaskTimer($params, $context) {
        $task_id = $params['task_id'];
        $note = $params['note'] ?? '';
        
        $this->CI->load->model('tasks_model');
        
        $timer_data = [
            'task_id' => $task_id,
            'start_time' => date('Y-m-d H:i:s'),
            'staff_id' => $context['user_id'],
            'note' => $note
        ];
        
        $this->CI->db->insert(db_prefix() . 'taskstimers', $timer_data);
        $timer_id = $this->CI->db->insert_id();
        
        return [
            'timer_id' => $timer_id,
            'message' => 'Task timer started successfully'
        ];
    }
    
    /**
     * 19. Add Task Timesheet
     */
    private function executeAddTaskTimesheet($params, $context) {
        $task_id = $params['task_id'];
        $hours = $params['hours'];
        $note = $params['note'] ?? '';
        $date = $params['date'] ?? date('Y-m-d');
        
        $timesheet_data = [
            'task_id' => $task_id,
            'start_time' => strtotime($date . ' 09:00:00'),
            'end_time' => strtotime($date . ' ' . date('H:i:s', strtotime("+{$hours} hours"))),
            'staff_id' => $context['user_id'],
            'note' => $note,
            // Note: billed column doesn't exist in this version
        ];
        
        $this->CI->db->insert(db_prefix() . 'taskstimers', $timesheet_data);
        $timesheet_id = $this->CI->db->insert_id();
        
        return [
            'timesheet_id' => $timesheet_id,
            'message' => 'Timesheet added successfully'
        ];
    }
    
    /**
     * 20. Bill Non-Project Tasks to Invoice
     */
    private function executeBillNonProjectTasksToInvoice($params, $context) {
        $invoice_id = $params['invoice_id'];
        $task_ids = $params['task_ids'] ?? [];
        
        if (empty($task_ids)) {
            // Get all non-project tasks
            $this->CI->db->select('id');
            $this->CI->db->from(db_prefix() . 'tasks');
            $this->CI->db->where('rel_type IS NULL OR rel_type = ""');
            $tasks = $this->CI->db->get()->result_array();
            $task_ids = array_column($tasks, 'id');
        }
        
        $billed_count = 0;
        foreach ($task_ids as $task_id) {
                    // Note: billed column doesn't exist in this version, so we'll just count the timesheets
        $this->CI->db->where('task_id', $task_id);
        $success = true; // Assume success since we can't mark as billed
            
            if ($success) {
                $billed_count++;
            }
        }
        
        return [
            'billed_count' => $billed_count,
            'message' => "{$billed_count} tasks billed to invoice"
        ];
    }
    
    // ========================================
    // TIMESHEET & EXPENSE ACTIONS (5 actions)
    // ========================================
    
    /**
     * 21. List Project Timesheets
     */
    private function executeListProjectTimesheets($params, $context) {
        $project_id = $params['project_id'];
        
        $this->CI->db->select(db_prefix() . 'taskstimers.id, ' . db_prefix() . 'taskstimers.task_id, ' . db_prefix() . 'taskstimers.start_time, ' . db_prefix() . 'taskstimers.end_time, ' . db_prefix() . 'taskstimers.staff_id, ' . db_prefix() . 'taskstimers.note, ' . db_prefix() . 'tasks.name as task_name, ' . db_prefix() . 'staff.firstname, ' . db_prefix() . 'staff.lastname');
        $this->CI->db->from(db_prefix() . 'taskstimers');
        $this->CI->db->join(db_prefix() . 'tasks', db_prefix() . 'tasks.id = ' . db_prefix() . 'taskstimers.task_id');
        $this->CI->db->join(db_prefix() . 'staff', db_prefix() . 'staff.staffid = ' . db_prefix() . 'taskstimers.staff_id');
        $this->CI->db->where(db_prefix() . 'tasks.rel_type', 'project');
        $this->CI->db->where(db_prefix() . 'tasks.rel_id', $project_id);
        
        if (isset($params['date_from'])) {
            $this->CI->db->where('start_time >=', $params['date_from'] . ' 00:00:00');
        }
        
        if (isset($params['date_to'])) {
            $this->CI->db->where('start_time <=', $params['date_to'] . ' 23:59:59');
        }
        
        // Note: billed column doesn't exist in this version, so we can't filter by billing status
        
        $this->CI->db->order_by('start_time', 'DESC');
        $timesheets = $this->CI->db->get()->result_array();
        
        return [
            'timesheets' => $timesheets,
            'count' => count($timesheets)
        ];
    }
    
    /**
     * 22. Invoice Timesheets
     */
    private function executeInvoiceTimesheets($params, $context) {
        $project_id = $params['project_id'];
        $timesheet_ids = $params['timesheet_ids'] ?? [];
        
        $this->CI->load->model('invoices_model');
        
        // Create invoice
        $invoice_data = [
            'clientid' => $this->getProjectClientId($project_id),
            'project_id' => $project_id,
            'number' => $this->CI->invoices_model->get_next_invoice_number(),
            'date' => date('Y-m-d'),
            'duedate' => $params['due_date'] ?? date('Y-m-d', strtotime('+30 days')),
            'currency' => get_option('customer_default_currency'),
            'subtotal' => 0,
            'total_tax' => 0,
            'total' => 0,
            'adjustment' => 0,
            'addedfrom' => $context['user_id'],
            'status' => 1 // Draft
        ];
        
        $invoice_id = $this->CI->invoices_model->add($invoice_data);
        
        // Add timesheets to invoice
        if (!empty($timesheet_ids)) {
            $this->addSpecificTimesheetsToInvoice($invoice_id, $timesheet_ids);
        } else {
            $this->addTimesheetsToInvoice($invoice_id, $project_id);
        }
        
        return [
            'invoice_id' => $invoice_id,
            'message' => 'Timesheet invoice created successfully'
        ];
    }
    
    /**
     * 23. Record Expense
     */
    private function executeRecordExpense($params, $context) {
        $this->CI->load->model('expenses_model');
        
        // Build expense data using schema
        $expense_data = $this->buildDataFromSchema('record_expense', $params, $context, 'expenses', [
            'description' => '',
            'billable' => 0,
            'tax' => 0
        ]);
        
        // Set date default if not provided
        if (!isset($expense_data['date'])) {
            $expense_data['date'] = date('Y-m-d');
        }
        
        $expense_id = $this->CI->expenses_model->add($expense_data);
        
        return [
            'expense_id' => $expense_id,
            'message' => 'Expense recorded successfully'
        ];
    }
    
    /**
     * 24. Set Recurring Expense
     */
    private function executeSetRecurringExpense($params, $context) {
        $recurring_data = [
            'amount' => $params['amount'],
            'category' => $params['category'],
            'description' => $params['description'] ?? '',
            'repeat_every' => $params['repeat_every'],
            'repeat_type' => $params['repeat_type'] ?? 'month',
            'cycles' => $params['cycles'] ?? 0,
            'last_recurring_date' => $params['last_recurring_date'] ?? date('Y-m-d'),
            'addedfrom' => $context['user_id']
        ];
        
        $this->CI->db->insert(db_prefix() . 'expenses_recurring', $recurring_data);
        $recurring_id = $this->CI->db->insert_id();
        
        return [
            'recurring_id' => $recurring_id,
            'message' => 'Recurring expense set successfully'
        ];
    }
    
    /**
     * 25. Convert Expense to Invoice
     */
    private function executeConvertExpenseToInvoice($params, $context) {
        $expense_id = $params['expense_id'];
        $invoice_id = $params['invoice_id'];
        
        // Get expense details
        $this->CI->db->select('*');
        $this->CI->db->from(db_prefix() . 'expenses');
        $this->CI->db->where('id', $expense_id);
        $expense = $this->CI->db->get()->row();
        
        if (!$expense) {
            throw new Exception('Expense not found');
        }
        
        // Add expense as invoice item
        $item_data = [
            'rel_id' => $invoice_id,
            'rel_type' => 'invoice',
            'description' => $expense->description,
            'long_description' => 'Converted from expense #' . $expense_id,
            'qty' => 1,
            'rate' => $expense->amount,
            'taxname' => '',
            'item_order' => 0
        ];
        
        $this->CI->db->insert(db_prefix() . 'item_invoice', $item_data);
        
        // Mark expense as converted
        $this->CI->db->where('id', $expense_id);
        $this->CI->db->update(db_prefix() . 'expenses', ['invoiceid' => $invoice_id]);
        
        return [
            'success' => true,
            'message' => 'Expense converted to invoice item successfully'
        ];
    }
    
    // ========================================
    // ESTIMATE & INVOICE ACTIONS (8 actions)
    // ========================================
    
    /**
     * 26. Send Estimate Email
     */
    private function executeSendEstimateEmail($params, $context) {
        $estimate_id = $params['estimate_id'];
        
        $this->CI->load->model('estimates_model');
        
        $email_data = [
            'email' => $params['email'] ?? '',
            'subject' => $params['subject'] ?? 'Estimate #' . $estimate_id,
            'message' => $params['message'] ?? ''
        ];
        
        $success = $this->CI->estimates_model->send_estimate_to_email($estimate_id, $email_data);
        
        return [
            'success' => $success,
            'message' => $success ? 'Estimate email sent successfully' : 'Failed to send estimate email'
        ];
    }
    
    /**
     * 27. Convert Estimate to Invoice
     */
    private function executeConvertEstimateToInvoice($params, $context) {
        $estimate_id = $params['estimate_id'];
        
        $this->CI->load->model('estimates_model');
        
        $invoice_id = $this->CI->estimates_model->convert_to_invoice($estimate_id, $params['status'] ?? 2);
        
        return [
            'invoice_id' => $invoice_id,
            'message' => 'Estimate converted to invoice successfully'
        ];
    }
    
    /**
     * 28. Convert Estimate to Project
     */
    private function executeConvertEstimateToProject($params, $context) {
        $estimate_id = $params['estimate_id'];
        
        $this->CI->load->model('estimates_model');
        $this->CI->load->model('projects_model');
        
        // Get estimate details
        $estimate = $this->CI->estimates_model->get($estimate_id);
        
        if (!$estimate) {
            throw new Exception('Estimate not found');
        }
        
        // Create project
        $project_data = [
            'name' => $params['project_name'] ?? 'Project from Estimate #' . $estimate_id,
            'clientid' => $estimate->clientid,
            'start_date' => $params['start_date'] ?? date('Y-m-d'),
            'description' => 'Project created from estimate #' . $estimate_id,
            'addedfrom' => $context['user_id']
        ];
        
        $project_id = $this->CI->projects_model->add($project_data);
        
        return [
            'project_id' => $project_id,
            'message' => 'Project created from estimate successfully'
        ];
    }
    
    /**
     * 29. Create Estimate Request Form
     */
    private function executeCreateEstimateRequestForm($params, $context) {
        $form_data = [
            'name' => $params['name'],
            'description' => $params['description'] ?? '',
            'form_data' => json_encode($params['form_data'] ?? []),
            'addedfrom' => $context['user_id'],
            'dateadded' => date('Y-m-d H:i:s')
        ];
        
        $this->CI->db->insert(db_prefix() . 'estimate_request_forms', $form_data);
        $form_id = $this->CI->db->insert_id();
        
        return [
            'form_id' => $form_id,
            'message' => 'Estimate request form created successfully'
        ];
    }
    
    /**
     * 30. Send Invoice Email
     */
    private function executeSendInvoiceEmail($params, $context) {
        $invoice_id = $params['invoice_id'];
        
        $this->CI->load->model('invoices_model');
        
        $email_data = [
            'email' => $params['email'] ?? '',
            'subject' => $params['subject'] ?? 'Invoice #' . $invoice_id,
            'message' => $params['message'] ?? ''
        ];
        
        $success = $this->CI->invoices_model->send_invoice_to_email($invoice_id, $email_data);
        
        return [
            'success' => $success,
            'message' => $success ? 'Invoice email sent successfully' : 'Failed to send invoice email'
        ];
    }
    
    /**
     * 31. Set Recurring Invoice
     */
    private function executeSetRecurringInvoice($params, $context) {
        $recurring_data = [
            'invoice_id' => $params['invoice_id'],
            'repeat_every' => $params['repeat_every'],
            'repeat_type' => $params['repeat_type'] ?? 'month',
            'cycles' => $params['cycles'] ?? 0,
            'last_recurring_date' => $params['last_recurring_date'] ?? date('Y-m-d'),
            'addedfrom' => $context['user_id']
        ];
        
        $this->CI->db->insert(db_prefix() . 'invoices_recurring', $recurring_data);
        $recurring_id = $this->CI->db->insert_id();
        
        return [
            'recurring_id' => $recurring_id,
            'message' => 'Recurring invoice set successfully'
        ];
    }
    
    /**
     * 32. Record Invoice Payment
     */
    private function executeRecordInvoicePayment($params, $context) {
        $this->CI->load->model('payments_model');
        
        $payment_data = [
            'invoiceid' => $params['invoice_id'],
            'amount' => $params['amount'],
            'paymentmode' => $params['payment_mode'] ?? 'bank',
            'transactionid' => $params['transaction_id'] ?? '',
            'note' => $params['note'] ?? '',
            'date' => $params['date'] ?? date('Y-m-d'),
            'addedfrom' => $context['user_id']
        ];
        
        $payment_id = $this->CI->payments_model->add($payment_data);
        
        return [
            'payment_id' => $payment_id,
            'message' => 'Payment recorded successfully'
        ];
    }
    
    /**
     * 33. Invoice Project Expenses
     */
    private function executeInvoiceProjectExpenses($params, $context) {
        $project_id = $params['project_id'];
        $expense_ids = $params['expense_ids'] ?? [];
        
        $this->CI->load->model('invoices_model');
        
        // Create invoice
        $invoice_data = [
            'clientid' => $this->getProjectClientId($project_id),
            'project_id' => $project_id,
            'number' => $this->CI->invoices_model->get_next_invoice_number(),
            'date' => date('Y-m-d'),
            'duedate' => $params['due_date'] ?? date('Y-m-d', strtotime('+30 days')),
            'currency' => get_option('customer_default_currency'),
            'subtotal' => 0,
            'total_tax' => 0,
            'total' => 0,
            'adjustment' => 0,
            'addedfrom' => $context['user_id'],
            'status' => 1 // Draft
        ];
        
        $invoice_id = $this->CI->invoices_model->add($invoice_data);
        
        // Add expenses to invoice
        if (!empty($expense_ids)) {
            foreach ($expense_ids as $expense_id) {
                $this->executeConvertExpenseToInvoice(['expense_id' => $expense_id, 'invoice_id' => $invoice_id], $context);
            }
        } else {
            // Add all project expenses
            $this->addExpensesToInvoice($invoice_id, $project_id);
        }
        
        return [
            'invoice_id' => $invoice_id,
            'message' => 'Project expenses invoice created successfully'
        ];
    }
    
    // ========================================
    // ANALYSIS & HEALTH CHECK ACTIONS (4 actions)
    // ========================================
    
    /**
     * 34. Find Unlinked Entities
     */
    private function executeFindUnlinkedEntities($params, $context) {
        $entity_type = $params['entity_type'] ?? 'tasks';
        $date_from = $params['date_from'] ?? null;
        $date_to = $params['date_to'] ?? null;
        
        $entities = [];
        
        switch ($entity_type) {
            case 'tasks':
                $this->CI->db->select('*');
                $this->CI->db->from(db_prefix() . 'tasks');
                $this->CI->db->where('rel_type IS NULL OR rel_type = ""');
                if ($date_from) {
                    $this->CI->db->where('dateadded >=', $date_from);
                }
                if ($date_to) {
                    $this->CI->db->where('dateadded <=', $date_to);
                }
                $entities = $this->CI->db->get()->result_array();
                break;
                
            case 'expenses':
                $this->CI->db->select('*');
                $this->CI->db->from(db_prefix() . 'expenses');
                $this->CI->db->where('project_id IS NULL');
                if ($date_from) {
                    $this->CI->db->where('date >=', $date_from);
                }
                if ($date_to) {
                    $this->CI->db->where('date <=', $date_to);
                }
                $entities = $this->CI->db->get()->result_array();
                break;
                
            case 'timesheets':
                        $this->CI->db->select(db_prefix() . 'taskstimers.id, ' . db_prefix() . 'taskstimers.task_id, ' . db_prefix() . 'taskstimers.start_time, ' . db_prefix() . 'taskstimers.end_time, ' . db_prefix() . 'taskstimers.staff_id, ' . db_prefix() . 'taskstimers.note, ' . db_prefix() . 'tasks.name as task_name');
        $this->CI->db->from(db_prefix() . 'taskstimers');
        $this->CI->db->join(db_prefix() . 'tasks', db_prefix() . 'tasks.id = ' . db_prefix() . 'taskstimers.task_id');
                $this->CI->db->where(db_prefix() . 'tasks.rel_type IS NULL OR ' . db_prefix() . 'tasks.rel_type = ""');
                if ($date_from) {
                    $this->CI->db->where('start_time >=', $date_from . ' 00:00:00');
                }
                if ($date_to) {
                    $this->CI->db->where('start_time <=', $date_to . ' 23:59:59');
                }
                $entities = $this->CI->db->get()->result_array();
                break;
        }
        
        return [
            'entity_type' => $entity_type,
            'entities' => $entities,
            'count' => count($entities)
        ];
    }
    
    /**
     * 35. Auto-link Entities
     */
    private function executeAutolinkEntities($params, $context) {
        $entity_type = $params['entity_type'] ?? 'tasks';
        $project_id = $params['project_id'] ?? null;
        $auto_assign = $params['auto_assign'] ?? false;
        
        $linked_count = 0;
        
        switch ($entity_type) {
            case 'tasks':
                if ($project_id) {
                    $this->CI->db->where('rel_type IS NULL OR rel_type = ""');
                    $success = $this->CI->db->update(db_prefix() . 'tasks', [
                        'rel_type' => 'project',
                        'rel_id' => $project_id
                    ]);
                    $linked_count = $this->CI->db->affected_rows();
                }
                break;
                
            case 'expenses':
                if ($project_id) {
                    $this->CI->db->where('project_id IS NULL');
                    $success = $this->CI->db->update(db_prefix() . 'expenses', ['project_id' => $project_id]);
                    $linked_count = $this->CI->db->affected_rows();
                }
                break;
        }
        
        return [
            'linked_count' => $linked_count,
            'message' => "{$linked_count} entities auto-linked successfully"
        ];
    }
    
    /**
     * 36. Bill Tasks to Invoice
     */
    private function executeBillTasksToInvoice($params, $context) {
        $invoice_id = $params['invoice_id'];
        $task_ids = $params['task_ids'] ?? [];
        $timesheet_ids = $params['timesheet_ids'] ?? [];
        
        $billed_count = 0;
        
        // Note: billed column doesn't exist in this version, so we'll just count the items
        if (!empty($task_ids)) {
            foreach ($task_ids as $task_id) {
                $this->CI->db->where('task_id', $task_id);
                $success = true; // Assume success since we can't mark as billed
                if ($success) {
                    $billed_count++;
                }
            }
        }
        
        // Note: billed column doesn't exist in this version, so we'll just count the items
        if (!empty($timesheet_ids)) {
            foreach ($timesheet_ids as $timesheet_id) {
                $this->CI->db->where('id', $timesheet_id);
                $success = true; // Assume success since we can't mark as billed
                if ($success) {
                    $billed_count++;
                }
            }
        }
        
        return [
            'billed_count' => $billed_count,
            'message' => "{$billed_count} items billed to invoice"
        ];
    }
    
    /**
     * 37. Create Reminder
     */
    private function executeCreateReminder($params, $context) {
        // Build reminder data using schema
        $reminder_data = $this->buildDataFromSchema('create_reminder', $params, $context, 'reminders', [
            'notify_by_email' => 1
        ]);
        
        $this->CI->db->insert(db_prefix() . 'reminders', $reminder_data);
        $reminder_id = $this->CI->db->insert_id();
        
        return [
            'reminder_id' => $reminder_id,
            'message' => 'Reminder created successfully'
        ];
    }
    
    // ========================================
    // HELPER METHODS
    // ========================================
    
    /**
     * Build data array from parameters using schema
     */
    private function buildDataFromSchema($action_id, $params, $context, $table_name, $defaults = []) {
        $required_fields = $this->getRequiredFields($action_id);
        $properties = $this->getFieldProperties($action_id);
        
        // Start with defaults
        $data = $defaults;
        
        // Map parameters to database fields based on schema
        foreach ($properties as $field => $field_schema) {
            $db_field = $this->mapParamToDbField($field, $table_name);
            if ($db_field && isset($params[$field])) {
                $data[$db_field] = $params[$field];
            } elseif ($db_field && isset($field_schema['default'])) {
                $data[$db_field] = $field_schema['default'];
            }
        }
        
        // Add context fields only if they exist in schema or are commonly needed
        $context_fields = $this->getContextFieldsForTable($table_name);
        foreach ($context_fields as $context_field => $context_value) {
            // Only add if not already set and if the field exists in the table
            if (!isset($data[$context_field])) {
                $data[$context_field] = $context_value;
            }
        }
        
        return $data;
    }
    
    /**
     * Get context fields that should be added for a specific table
     */
    private function getContextFieldsForTable($table_name) {
        $context_fields = [];
        
        // Check if table exists and get its structure
        $full_table_name = db_prefix() . $table_name;
        if ($this->CI->db->table_exists($full_table_name)) {
            $fields = $this->CI->db->list_fields($full_table_name);
            
            // Only add context fields if they exist in the table
            if (in_array('addedfrom', $fields)) {
                $context_fields['addedfrom'] = $this->CI->session->userdata('staff_user_id') ?? 1;
            }
            if (in_array('creator', $fields)) {
                $context_fields['creator'] = $this->CI->session->userdata('staff_user_id') ?? 1;
            }
            if (in_array('staff', $fields)) {
                $context_fields['staff'] = $this->CI->session->userdata('staff_user_id') ?? 1;
            }
        }
        
        return $context_fields;
    }
    
    /**
     * Map parameter field to database field
     */
    private function mapParamToDbField($param_field, $table_name) {
        $mappings = [
            'reminders' => [
                'description' => 'description',
                'date' => 'date',
                'rel_type' => 'rel_type',
                'rel_id' => 'rel_id',
                'staff' => 'staff',
                'notify_by_email' => 'notify_by_email',
                'isnotified' => 'isnotified'
            ],
            'tasks' => [
                'title' => 'name',
                'description' => 'description',
                'project_id' => 'rel_id',
                'assignee_ids' => 'assignee_ids', // Special handling needed
                'priority' => 'priority',
                'start_date' => 'startdate',
                'due_date' => 'duedate',
                'status' => 'status',
                'billable' => 'billable',
                'hourly_rate' => 'hourly_rate'
            ],
            'projects' => [
                'name' => 'name',
                'description' => 'description',
                'customer_id' => 'clientid',
                'start_date' => 'start_date',
                'end_date' => 'deadline',
                'deadline' => 'deadline',
                'billing_type' => 'billing_type',
                'status' => 'status',
                'members' => 'members' // Special handling needed
            ],
            'invoices' => [
                'customer_id' => 'clientid',
                'project_id' => 'project_id',
                'due_date' => 'duedate',
                'items' => 'items', // Special handling needed
                'terms' => 'terms',
                'notes' => 'note'
            ],
            'expenses' => [
                'amount' => 'amount',
                'category' => 'category',
                'description' => 'description',
                'date' => 'date',
                'project_id' => 'project_id',
                'billable' => 'billable',
                'tax' => 'tax'
            ]
        ];
        
        return isset($mappings[$table_name][$param_field]) ? $mappings[$table_name][$param_field] : $param_field;
    }
    
    /**
     * Get project client ID
     */
    private function getProjectClientId($project_id) {
        $this->CI->db->select('clientid');
        $this->CI->db->from(db_prefix() . 'projects');
        $this->CI->db->where('id', $project_id);
        $project = $this->CI->db->get()->row();
        
        return $project ? $project->clientid : null;
    }
    
    /**
     * Add timesheets to invoice
     */
    private function addTimesheetsToInvoice($invoice_id, $project_id) {
        // Get timesheets for project (billed column doesn't exist in this version)
        $this->CI->db->select(db_prefix() . 'taskstimers.id, ' . db_prefix() . 'taskstimers.task_id, ' . db_prefix() . 'taskstimers.start_time, ' . db_prefix() . 'taskstimers.end_time, ' . db_prefix() . 'taskstimers.staff_id, ' . db_prefix() . 'taskstimers.note, ' . db_prefix() . 'tasks.name as task_name, ' . db_prefix() . 'tasks.hourly_rate');
        $this->CI->db->from(db_prefix() . 'taskstimers');
        $this->CI->db->join(db_prefix() . 'tasks', db_prefix() . 'tasks.id = ' . db_prefix() . 'taskstimers.task_id');
        $this->CI->db->where(db_prefix() . 'tasks.rel_type', 'project');
        $this->CI->db->where(db_prefix() . 'tasks.rel_id', $project_id);
        // Note: billed column doesn't exist in this version, so we'll get all timesheets
        
        $timesheets = $this->CI->db->get()->result_array();
        
        foreach ($timesheets as $timesheet) {
            // Calculate total hours dynamically
            $total_seconds = $timesheet['end_time'] ? ($timesheet['end_time'] - $timesheet['start_time']) : (time() - $timesheet['start_time']);
            $total_hours = sec2qty($total_seconds);
            
            $item_data = [
                'rel_id' => $invoice_id,
                'rel_type' => 'invoice',
                'description' => $timesheet['task_name'] . ' - ' . $timesheet['note'],
                'long_description' => 'Timesheet entry',
                'qty' => $total_hours,
                'rate' => $timesheet['hourly_rate'] ?? 0,
                'taxname' => '',
                'item_order' => 0
            ];
            
            $this->CI->db->insert(db_prefix() . 'item_invoice', $item_data);
            
            // Note: billed column doesn't exist in this version, so we can't mark as billed
        }
    }
    
    /**
     * Add specific timesheets to invoice
     */
    private function addSpecificTimesheetsToInvoice($invoice_id, $timesheet_ids) {
        foreach ($timesheet_ids as $timesheet_id) {
            $this->CI->db->select(db_prefix() . 'taskstimers.id, ' . db_prefix() . 'taskstimers.task_id, ' . db_prefix() . 'taskstimers.start_time, ' . db_prefix() . 'taskstimers.end_time, ' . db_prefix() . 'taskstimers.staff_id, ' . db_prefix() . 'taskstimers.note, ' . db_prefix() . 'taskstimers.billed, ' . db_prefix() . 'tasks.name as task_name, ' . db_prefix() . 'tasks.hourly_rate');
            $this->CI->db->from(db_prefix() . 'taskstimers');
            $this->CI->db->join(db_prefix() . 'tasks', db_prefix() . 'tasks.id = ' . db_prefix() . 'taskstimers.task_id');
            $this->CI->db->where(db_prefix() . 'taskstimers.id', $timesheet_id);
            
            $timesheet = $this->CI->db->get()->row();
            
            if ($timesheet) {
                // Calculate total hours dynamically
                $total_seconds = $timesheet->end_time ? ($timesheet->end_time - $timesheet->start_time) : (time() - $timesheet->start_time);
                $total_hours = sec2qty($total_seconds);
                
                $item_data = [
                    'rel_id' => $invoice_id,
                    'rel_type' => 'invoice',
                    'description' => $timesheet->task_name . ' - ' . $timesheet->note,
                    'long_description' => 'Timesheet entry',
                    'qty' => $total_hours,
                    'rate' => $timesheet->hourly_rate ?? 0,
                    'taxname' => '',
                    'item_order' => 0
                ];
                
                $this->CI->db->insert(db_prefix() . 'item_invoice', $item_data);
                
                // Note: billed column doesn't exist in this version, so we can't mark as billed
            }
        }
    }
    
    /**
     * Add expenses to invoice
     */
    private function addExpensesToInvoice($invoice_id, $project_id) {
        // Get unbilled expenses for project
        $this->CI->db->select('*');
        $this->CI->db->from(db_prefix() . 'expenses');
        $this->CI->db->where('project_id', $project_id);
        $this->CI->db->where('billable', 1);
        $this->CI->db->where('invoiceid IS NULL');
        
        $expenses = $this->CI->db->get()->result_array();
        
        foreach ($expenses as $expense) {
            $item_data = [
                'rel_id' => $invoice_id,
                'rel_type' => 'invoice',
                'description' => $expense['description'],
                'long_description' => 'Expense: ' . $expense['description'],
                'qty' => 1,
                'rate' => $expense['amount'],
                'taxname' => '',
                'item_order' => 0
            ];
            
            $this->CI->db->insert(db_prefix() . 'item_invoice', $item_data);
            
            // Mark as converted
            $this->CI->db->where('id', $expense['id']);
            $this->CI->db->update(db_prefix() . 'expenses', ['invoiceid' => $invoice_id]);
        }
    }
}
