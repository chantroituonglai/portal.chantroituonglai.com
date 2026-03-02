<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Project Agent Planner Helper
 * Handles action planning and execution
 */

class ProjectAgentPlannerHelper {
    
    private $CI;
    private $actionRegistry;
    private $memoryHelper;
    private $contextHelper;
    
    public function __construct() {
        $this->CI = &get_instance();
        $this->CI->load->helper('project_agent_action_registry_helper');
        $this->CI->load->helper('project_agent_memory_helper');
        $this->CI->load->helper('project_agent_context_helper');
        
        $this->actionRegistry = new ProjectAgentActionRegistry();
        $this->memoryHelper = new ProjectAgentMemoryHelper();
        $this->contextHelper = new ProjectAgentContextHelper();
    }
    
    /**
     * Create action plan from user request
     */
    public function createPlan($user_request, $session_id, $project_id = null, $context = []) {
        $plan = [
            'plan_id' => 'plan_' . time() . '_' . rand(1000, 9999),
            'user_request' => $user_request,
            'session_id' => $session_id,
            'project_id' => $project_id,
            'steps' => [],
            'requires_confirm' => false,
            'risk_level' => 'low',
            'estimated_duration' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'status' => 'draft'
        ];
        
        // Analyze user request and create steps
        $plan['steps'] = $this->analyzeRequest($user_request, $context);
        
        // Calculate plan metrics
        $plan['requires_confirm'] = $this->checkConfirmationRequired($plan['steps']);
        $plan['risk_level'] = $this->calculateRiskLevel($plan['steps']);
        $plan['estimated_duration'] = $this->estimateDuration($plan['steps']);
        
        return $plan;
    }
    
    /**
     * Analyze user request and create action steps
     */
    private function analyzeRequest($request, $context) {
        $steps = [];
        $request_lower = strtolower($request);
        
        // Check for common patterns
        if (strpos($request_lower, 'create task') !== false || strpos($request_lower, 'add task') !== false) {
            $steps[] = $this->createTaskStep($request, $context);
        }
        
        if (strpos($request_lower, 'check billing') !== false || strpos($request_lower, 'billing status') !== false) {
            $steps[] = $this->checkBillingStep($request, $context);
        }
        
        if (strpos($request_lower, 'create project') !== false || strpos($request_lower, 'new project') !== false) {
            $steps[] = $this->createProjectStep($request, $context);
        }
        
        if (strpos($request_lower, 'update task') !== false || strpos($request_lower, 'modify task') !== false) {
            $steps[] = $this->updateTaskStep($request, $context);
        }
        
        if (strpos($request_lower, 'add member') !== false || strpos($request_lower, 'assign member') !== false) {
            $steps[] = $this->addMemberStep($request, $context);
        }
        
        if (strpos($request_lower, 'create estimate') !== false || strpos($request_lower, 'new estimate') !== false) {
            $steps[] = $this->createEstimateStep($request, $context);
        }
        
        if (strpos($request_lower, 'summary') !== false || strpos($request_lower, 'overview') !== false) {
            $steps[] = $this->summaryStep($request, $context);
        }
        
        // If no specific action found, try to infer from context
        if (empty($steps)) {
            $steps[] = $this->inferActionStep($request, $context);
        }
        
        return $steps;
    }
    
    /**
     * Create task step
     */
    private function createTaskStep($request, $context) {
        return [
            'id' => 's1',
            'action' => 'create_task',
            'params' => $this->extractTaskParams($request),
            'description' => 'Create new task based on user request',
            'risk' => 'low',
            'estimated_time' => 30
        ];
    }
    
    /**
     * Check billing step
     */
    private function checkBillingStep($request, $context) {
        return [
            'id' => 's1',
            'action' => 'check_billing_status',
            'params' => [
                'project_id' => $context['project_id'] ?? null
            ],
            'description' => 'Check billing status for project',
            'risk' => 'low',
            'estimated_time' => 15
        ];
    }
    
    /**
     * Create project step
     */
    private function createProjectStep($request, $context) {
        return [
            'id' => 's1',
            'action' => 'create_project',
            'params' => $this->extractProjectParams($request),
            'description' => 'Create new project based on user request',
            'risk' => 'medium',
            'estimated_time' => 60
        ];
    }
    
    /**
     * Update task step
     */
    private function updateTaskStep($request, $context) {
        return [
            'id' => 's1',
            'action' => 'update_task',
            'params' => $this->extractUpdateParams($request),
            'description' => 'Update existing task',
            'risk' => 'low',
            'estimated_time' => 20
        ];
    }
    
    /**
     * Add member step
     */
    private function addMemberStep($request, $context) {
        return [
            'id' => 's1',
            'action' => 'add_project_member',
            'params' => $this->extractMemberParams($request),
            'description' => 'Add member to project',
            'risk' => 'low',
            'estimated_time' => 15
        ];
    }
    
    /**
     * Create estimate step
     */
    private function createEstimateStep($request, $context) {
        return [
            'id' => 's1',
            'action' => 'create_estimate',
            'params' => $this->extractEstimateParams($request),
            'description' => 'Create new estimate',
            'risk' => 'medium',
            'estimated_time' => 45
        ];
    }
    
    /**
     * Summary step
     */
    private function summaryStep($request, $context) {
        return [
            'id' => 's1',
            'action' => 'summarize_project_work_remaining',
            'params' => [
                'project_id' => $context['project_id'] ?? null
            ],
            'description' => 'Generate project summary',
            'risk' => 'low',
            'estimated_time' => 20
        ];
    }
    
    /**
     * Infer action step
     */
    private function inferActionStep($request, $context) {
        return [
            'id' => 's1',
            'action' => 'get_project_overview',
            'params' => [
                'project_id' => $context['project_id'] ?? null
            ],
            'description' => 'Get project overview (inferred action)',
            'risk' => 'low',
            'estimated_time' => 15
        ];
    }
    
    /**
     * Extract task parameters from request
     */
    private function extractTaskParams($request) {
        $params = [
            'title' => '',
            'description' => '',
            'project_id' => null,
            'priority' => 2,
            'due_date' => null
        ];
        
        // Extract title (basic pattern matching)
        if (preg_match('/create task[:\s]+(.+?)(?:\s|$)/i', $request, $matches)) {
            $params['title'] = trim($matches[1]);
        }
        
        // Extract due date
        if (preg_match('/(\d{1,2}\/\d{1,2}\/\d{4}|\d{4}-\d{2}-\d{2})/i', $request, $matches)) {
            $params['due_date'] = $matches[1];
        }
        
        // Extract priority
        if (strpos(strtolower($request), 'high priority') !== false) {
            $params['priority'] = 1;
        } elseif (strpos(strtolower($request), 'low priority') !== false) {
            $params['priority'] = 3;
        }
        
        return $params;
    }
    
    /**
     * Extract project parameters from request
     */
    private function extractProjectParams($request) {
        $params = [
            'name' => '',
            'customer_id' => null,
            'description' => '',
            'start_date' => date('Y-m-d'),
            'end_date' => null
        ];
        
        // Extract project name
        if (preg_match('/create project[:\s]+(.+?)(?:\s|$)/i', $request, $matches)) {
            $params['name'] = trim($matches[1]);
        }
        
        return $params;
    }
    
    /**
     * Extract update parameters from request
     */
    private function extractUpdateParams($request) {
        $params = [
            'task_id' => null,
            'title' => '',
            'description' => '',
            'status' => null
        ];
        
        // Extract task ID if mentioned
        if (preg_match('/task\s+(\d+)/i', $request, $matches)) {
            $params['task_id'] = $matches[1];
        }
        
        return $params;
    }
    
    /**
     * Extract member parameters from request
     */
    private function extractMemberParams($request) {
        $params = [
            'project_id' => null,
            'staff_ids' => []
        ];
        
        return $params;
    }
    
    /**
     * Extract estimate parameters from request
     */
    private function extractEstimateParams($request) {
        $params = [
            'customer_id' => null,
            'project_id' => null,
            'items' => []
        ];
        
        return $params;
    }
    
    /**
     * Check if plan requires confirmation
     */
    private function checkConfirmationRequired($steps) {
        foreach ($steps as $step) {
            $action = $this->actionRegistry->getAction($step['action']);
            if ($action && $action['requires_confirm']) {
                return true;
            }
            
            if (($step['risk'] ?? 'low') === 'high') {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Calculate overall risk level
     */
    private function calculateRiskLevel($steps) {
        $max_risk = 'low';
        
        foreach ($steps as $step) {
            $step_risk = $step['risk'] ?? 'low';
            
            if ($step_risk === 'high') {
                $max_risk = 'high';
                break;
            } elseif ($step_risk === 'medium' && $max_risk === 'low') {
                $max_risk = 'medium';
            }
        }
        
        return $max_risk;
    }
    
    /**
     * Estimate plan duration
     */
    private function estimateDuration($steps) {
        $total_time = 0;
        
        foreach ($steps as $step) {
            $total_time += $step['estimated_time'] ?? 15;
        }
        
        return $total_time;
    }
    
    /**
     * Execute plan
     */
    public function executePlan($plan, $user_id) {
        $results = [
            'plan_id' => $plan['plan_id'],
            'status' => 'running',
            'steps' => [],
            'started_at' => date('Y-m-d H:i:s'),
            'completed_at' => null,
            'success_count' => 0,
            'error_count' => 0
        ];
        
        foreach ($plan['steps'] as $step) {
            $step_result = $this->executeStep($step, $user_id);
            $results['steps'][] = $step_result;
            
            if ($step_result['success']) {
                $results['success_count']++;
            } else {
                $results['error_count']++;
            }
        }
        
        $results['status'] = ($results['error_count'] === 0) ? 'completed' : 'completed_with_errors';
        $results['completed_at'] = date('Y-m-d H:i:s');
        
        return $results;
    }
    
    /**
     * Execute single step
     */
    private function executeStep($step, $user_id) {
        $result = [
            'step_id' => $step['id'],
            'action' => $step['action'],
            'params' => $step['params'],
            'started_at' => date('Y-m-d H:i:s'),
            'completed_at' => null,
            'success' => false,
            'result' => null,
            'error' => null
        ];
        
        try {
            $context = [
                'user_id' => $user_id
            ];
            
            $execution_result = $this->actionRegistry->executeAction($step['action'], $step['params'], $context);
            
            $result['success'] = $execution_result['success'];
            $result['result'] = $execution_result['result'] ?? null;
            $result['error'] = $execution_result['error'] ?? null;
            
        } catch (Exception $e) {
            $result['success'] = false;
            $result['error'] = $e->getMessage();
        }
        
        $result['completed_at'] = date('Y-m-d H:i:s');
        
        return $result;
    }
    
    /**
     * Validate plan
     */
    public function validatePlan($plan) {
        $validation = [
            'valid' => true,
            'errors' => [],
            'warnings' => []
        ];
        
        if (empty($plan['steps'])) {
            $validation['valid'] = false;
            $validation['errors'][] = 'Plan has no steps';
        }
        
        foreach ($plan['steps'] as $step) {
            // Check if action exists
            if (!$this->actionRegistry->actionExists($step['action'])) {
                $validation['valid'] = false;
                $validation['errors'][] = "Action not found: {$step['action']}";
                continue;
            }
            
            // Validate parameters
            $param_validation = $this->actionRegistry->validateParams($step['action'], $step['params']);
            if (!$param_validation['valid']) {
                $validation['valid'] = false;
                $validation['errors'] = array_merge($validation['errors'], $param_validation['errors']);
            }
        }
        
        return $validation;
    }
    
    /**
     * Dry run plan
     */
    public function dryRunPlan($plan) {
        $dry_run = [
            'plan_id' => $plan['plan_id'],
            'simulation' => true,
            'steps' => [],
            'summary' => []
        ];
        
        foreach ($plan['steps'] as $step) {
            $step_simulation = $this->simulateStep($step);
            $dry_run['steps'][] = $step_simulation;
        }
        
        $dry_run['summary'] = $this->generateDryRunSummary($dry_run['steps']);
        
        return $dry_run;
    }
    
    /**
     * Simulate step execution
     */
    private function simulateStep($step) {
        $simulation = [
            'step_id' => $step['id'],
            'action' => $step['action'],
            'params' => $step['params'],
            'simulated_result' => null,
            'would_succeed' => true,
            'estimated_impact' => 'low'
        ];
        
        // Simulate based on action type
        switch ($step['action']) {
            case 'create_task':
                $simulation['simulated_result'] = [
                    'task_id' => 'simulated_' . rand(1000, 9999),
                    'message' => 'Task would be created successfully'
                ];
                break;
                
            case 'check_billing_status':
                $simulation['simulated_result'] = [
                    'unbilled_timesheets' => ['hours' => 0, 'amount' => 0],
                    'unbilled_expenses' => ['count' => 0, 'amount' => 0],
                    'overdue_invoices' => ['count' => 0, 'amount' => 0]
                ];
                break;
                
            case 'create_project':
                $simulation['simulated_result'] = [
                    'project_id' => 'simulated_' . rand(1000, 9999),
                    'message' => 'Project would be created successfully'
                ];
                $simulation['estimated_impact'] = 'medium';
                break;
                
            default:
                $simulation['simulated_result'] = [
                    'message' => 'Action would be executed successfully'
                ];
        }
        
        return $simulation;
    }
    
    /**
     * Generate dry run summary
     */
    private function generateDryRunSummary($steps) {
        $summary = [
            'total_steps' => count($steps),
            'estimated_duration' => 0,
            'risk_level' => 'low',
            'impact_level' => 'low',
            'recommendations' => []
        ];
        
        foreach ($steps as $step) {
            $summary['estimated_duration'] += $step['estimated_time'] ?? 15;
            
            if (($step['risk'] ?? 'low') === 'high') {
                $summary['risk_level'] = 'high';
            } elseif (($step['risk'] ?? 'low') === 'medium' && $summary['risk_level'] === 'low') {
                $summary['risk_level'] = 'medium';
            }
            
            if ($step['estimated_impact'] === 'high') {
                $summary['impact_level'] = 'high';
            } elseif ($step['estimated_impact'] === 'medium' && $summary['impact_level'] === 'low') {
                $summary['impact_level'] = 'medium';
            }
        }
        
        // Generate recommendations
        if ($summary['risk_level'] === 'high') {
            $summary['recommendations'][] = 'Review high-risk actions before execution';
        }
        
        if ($summary['impact_level'] === 'high') {
            $summary['recommendations'][] = 'Consider breaking down high-impact actions';
        }
        
        if ($summary['total_steps'] > 5) {
            $summary['recommendations'][] = 'Consider executing plan in smaller batches';
        }
        
        return $summary;
    }
}
