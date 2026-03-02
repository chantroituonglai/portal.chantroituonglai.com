<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Project Agent Context Helper
 * Builds and manages project context for AI interactions
 */

// Load debug helper
if (!function_exists('pa_log_error')) {
    require_once(__DIR__ . '/project_agent_debug_helper.php');
}

class ProjectAgentContextHelper {
    
    private $CI;
    
    public function __construct() {
        $this->CI = &get_instance();
    }
    
    /**
     * Build comprehensive project context
     */
    public function buildProjectContext($project_id) {
        // Check memory usage before starting
        $memory_before = memory_get_usage(true);
        pa_log_error('Memory before project context: ' . round($memory_before / 1024 / 1024, 2) . ' MB');
        
        $context = [
            'project' => null,
            'tasks' => [],
            'milestones' => [],
            'team_members' => [],
            'billing_status' => null,
            'recent_activities' => [],
            'risks' => [],
            'opportunities' => []
        ];
        
        // Get project details
        try {
            $this->CI->load->model('projects_model');
            $project = $this->CI->projects_model->get($project_id);
            // log_message('error', '[PA][context] Project details: ' . json_encode($project));
            if ($project) {
                $context['project'] = $project;
                
                // Get project progress
                $context['project']->progress = $this->CI->projects_model->calc_progress($project_id);
                
                // Get project tasks (with memory protection)
                try {
                    pa_log_error('About to load tasks for project_id: ' . $project_id);
                    $context['tasks'] = $this->getProjectTasks($project_id);
                    pa_log_error('Tasks loaded successfully, count: ' . count($context['tasks']));
                } catch (\Throwable $e) {
                    pa_log_error('Error loading tasks: ' . $e->getMessage());
                    pa_log_error('Tasks error trace: ' . $e->getTraceAsString());
                    $context['tasks'] = [];
                }
                
                // Get project milestones
                try {
                    pa_log_error('About to load milestones for project_id: ' . $project_id);
                    $context['milestones'] = $this->getProjectMilestones($project_id);
                    pa_log_error('Milestones loaded successfully, count: ' . count($context['milestones']));
                } catch (\Throwable $e) {
                    pa_log_error('Error loading milestones: ' . $e->getMessage());
                    pa_log_error('Milestones error trace: ' . $e->getTraceAsString());
                    $context['milestones'] = [];
                }
                
                // Get team members
                try {
                    pa_log_error('About to load team for project_id: ' . $project_id);
                    $context['team_members'] = $this->getProjectTeam($project_id);
                    pa_log_error('Team loaded successfully, count: ' . count($context['team_members']));
                } catch (\Throwable $e) {
                    pa_log_error('Error loading team: ' . $e->getMessage());
                    pa_log_error('Team error trace: ' . $e->getTraceAsString());
                    $context['team_members'] = [];
                }
                
                // Get billing status
                try {
                    pa_log_error('About to load billing for project_id: ' . $project_id);
                    $context['billing_status'] = $this->getBillingStatus($project_id);
                    pa_log_error('Billing loaded successfully');
                } catch (\Throwable $e) {
                    pa_log_error('Error loading billing: ' . $e->getMessage());
                    pa_log_error('Billing error trace: ' . $e->getTraceAsString());
                    $context['billing_status'] = null;
                }
                
                // Get recent activities (with memory protection)
                try {
                    pa_log_error('About to load activities for project_id: ' . $project_id);
                    $context['recent_activities'] = $this->getRecentActivities($project_id);
                    pa_log_error('Activities loaded successfully, count: ' . count($context['recent_activities']));
                } catch (\Throwable $e) {
                    pa_log_error('Error loading activities: ' . $e->getMessage());
                    pa_log_error('Activities error trace: ' . $e->getTraceAsString());
                    $context['recent_activities'] = [];
                }
                
                // Skip risks and opportunities to save memory
                $context['risks'] = [];
                $context['opportunities'] = [];
            }
        } catch (\Throwable $e) {
            pa_log_error('Error building project context: ' . $e->getMessage());
            // Return minimal context
            $context = [
                'project' => null,
                'tasks' => [],
                'milestones' => [],
                'team_members' => [],
                'billing_status' => null,
                'recent_activities' => [],
                'risks' => [],
                'opportunities' => []
            ];
        }
        
        // Check memory usage after building
        $memory_after = memory_get_usage(true);
        pa_log_error('Memory after project context: ' . round($memory_after / 1024 / 1024, 2) . ' MB');
        pa_log_error('Memory used: ' . round(($memory_after - $memory_before) / 1024 / 1024, 2) . ' MB');
        
        pa_log_error('Project context built successfully - returning context with keys: ' . implode(', ', array_keys($context)));
        return $context;
    }

    /**
     * Prefetch lightweight context for a specific action
     * Uses declared related_tables (JSON) in project_agent_actions; falls back to common tables.
     */
    public function prefetchActionContext($action_id, $project_id, $limit = 10) {
        $ctx = [];
        try {
            $CI = $this->CI;
            $CI->load->model('project_agent_model');
            $row = $CI->project_agent_model->get_action($action_id);
            // 1) Prefer context_queries if present (generated by Mini Agent)
            if ($row && isset($row->context_queries) && is_string($row->context_queries) && $row->context_queries !== '') {
                $cq = json_decode($row->context_queries, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($cq) && !empty($cq['tables'])) {
                    $ctx['context_queries'] = [];
                    foreach ($cq['tables'] as $q) {
                        $table = isset($q['table']) ? $q['table'] : null;
                        $from  = isset($q['from']) ? $q['from'] : null;
                        $select= isset($q['select']) && is_array($q['select']) ? $q['select'] : [];
                        $order = isset($q['order_by']) ? $q['order_by'] : '';
                        if (!$table || !$from || empty($select)) continue;
                        // Only run if table exists
                        if (!$CI->db->table_exists(db_prefix() . $table)) continue;
                        // Build safe SQL
                        $sel = implode(', ', array_map(function($s){ return $s; }, $select));
                        $sql = 'SELECT ' . $sel . ' FROM ' . $from;
                        if ($order) { $sql .= ' ORDER BY ' . $order; }
                        $sql .= ' LIMIT ' . intval(max(1, (int)$limit));
                        try {
                            $res = $CI->db->query($sql)->result_array();
                            $ctx['context_queries'][$table] = $res;
                        } catch (\Throwable $e) {
                            pa_log_error('CQ query failed for ' . $table . ' : ' . $e->getMessage());
                        }
                    }
                }
            }

            // 2) Fallback to related_tables heuristic if no CQ results
            $related = [];
            if ($row && isset($row->related_tables) && is_string($row->related_tables) && $row->related_tables !== '') {
                $tmp = json_decode($row->related_tables, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($tmp)) { $related = $tmp; }
            }
            $related = array_map('strtolower', array_values(array_unique(array_filter($related))));

            // Common helpers
            if (in_array('tasks', $related, true)) {
                $ctx['tasks'] = $this->getProjectTasks($project_id);
            }
            if (in_array('milestones', $related, true)) {
                $ctx['milestones'] = $this->getProjectMilestones($project_id);
            }
            if (in_array('taskstimers', $related, true) || in_array('timesheets', $related, true)) {
                // Covered via billing status below
                $ctx['billing_status'] = $this->getBillingStatus($project_id);
            }
            if (in_array('invoices', $related, true)) {
                $ctx['invoices'] = $this->getLightInvoices($project_id, $limit);
            }
            if (in_array('estimates', $related, true)) {
                $ctx['estimates'] = $this->getLightEstimates($project_id, $limit);
            }
            if (in_array('expenses', $related, true)) {
                $ctx['expenses'] = $this->getLightExpenses($project_id, $limit);
            }
        } catch (\Throwable $e) {
            pa_log_error('prefetchActionContext error: ' . $e->getMessage());
        }
        return $ctx;
    }

    private function getLightInvoices($project_id, $limit = 10) {
        $this->CI->db->select('id,number,date,duedate,total,status');
        $this->CI->db->from(db_prefix().'invoices');
        $this->CI->db->where('project_id', $project_id);
        $this->CI->db->order_by('date','DESC');
        $this->CI->db->limit(max(1,(int)$limit));
        return $this->CI->db->get()->result_array();
    }

    private function getLightEstimates($project_id, $limit = 10) {
        $this->CI->db->select('id,number,date,expirydate,total,status');
        $this->CI->db->from(db_prefix().'estimates');
        $this->CI->db->where('project_id', $project_id);
        $this->CI->db->order_by('date','DESC');
        $this->CI->db->limit(max(1,(int)$limit));
        return $this->CI->db->get()->result_array();
    }

    private function getLightExpenses($project_id, $limit = 10) {
        $this->CI->db->select('id,category,amount,date,invoiceid');
        $this->CI->db->from(db_prefix().'expenses');
        $this->CI->db->where('project_id', $project_id);
        $this->CI->db->order_by('date','DESC');
        $this->CI->db->limit(max(1,(int)$limit));
        return $this->CI->db->get()->result_array();
    }
    
    /**
     * Get project tasks
     */
    private function getProjectTasks($project_id) {
        // Check memory before starting
        $memory_before = memory_get_usage(true);
        pa_log_error('getProjectTasks - Memory before: ' . round($memory_before / 1024 / 1024, 2) . ' MB');
        
        // Apply a sensible upper bound to avoid loading huge datasets into memory
        $limit = (int) get_option('project_agent_context_task_limit');
        if ($limit <= 0) { $limit = 20; } // Reduced from 200 to 20 to prevent memory issues
        
        pa_log_error('getProjectTasks - Using limit: ' . $limit);

        try {
            // Select only lightweight columns to keep memory small
            $this->CI->db->select(
                db_prefix() . 'tasks.id,' .
                db_prefix() . 'tasks.name,' .
                db_prefix() . 'tasks.duedate,' .
                db_prefix() . 'tasks.status,' .
                db_prefix() . 'tasks.billable'
            );
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
            $this->CI->db->order_by('duedate', 'ASC');
            $this->CI->db->limit($limit);

            pa_log_error('getProjectTasks - About to execute query');
            $result = $this->CI->db->get()->result_array();
            pa_log_error('getProjectTasks - Query executed successfully, result count: ' . count($result));
            
            // Check memory after
            $memory_after = memory_get_usage(true);
            pa_log_error('getProjectTasks - Memory after: ' . round($memory_after / 1024 / 1024, 2) . ' MB, Used: ' . round(($memory_after - $memory_before) / 1024 / 1024, 2) . ' MB');
            
            return $result;
        } catch (\Throwable $e) {
            pa_log_error('getProjectTasks - Error: ' . $e->getMessage());
            pa_log_error('getProjectTasks - Error trace: ' . $e->getTraceAsString());
            return [];
        }
    }
    
    /**
     * Get project milestones
     */
    private function getProjectMilestones($project_id) {
        $limit = (int) get_option('project_agent_context_milestone_limit');
        if ($limit <= 0) { $limit = 100; }
        // Only required columns (avoid large text columns)
        $this->CI->db->select('id,name,due_date');
        $this->CI->db->from(db_prefix() . 'milestones');
        $this->CI->db->where('project_id', $project_id);
        $this->CI->db->order_by('due_date', 'ASC');
        $this->CI->db->limit($limit);
        return $this->CI->db->get()->result_array();
    }
    
    /**
     * Get project team members
     */
    private function getProjectTeam($project_id) {
        $this->CI->db->select(db_prefix() . 'staff.firstname, ' . db_prefix() . 'staff.lastname, ' . db_prefix() . 'staff.email');
        $this->CI->db->from(db_prefix() . 'project_members');
        $this->CI->db->join(db_prefix() . 'staff', db_prefix() . 'staff.staffid = ' . db_prefix() . 'project_members.staff_id');
        $this->CI->db->where('project_id', $project_id);
        
        return $this->CI->db->get()->result_array();
    }
    
    /**
     * Get billing status
     */
    private function getBillingStatus($project_id) {
        $billing = [
            'unbilled_hours' => 0,
            'unbilled_amount' => 0,
            'unbilled_expenses' => 0,
            'overdue_invoices' => 0,
            'total_invoiced' => 0,
            'total_paid' => 0
        ];
        
        // Get unbilled timesheets - calculate total time dynamically
        $this->CI->db->select('SUM(CASE WHEN ' . db_prefix() . 'taskstimers.end_time IS NULL THEN ' . time() . ' - ' . db_prefix() . 'taskstimers.start_time ELSE ' . db_prefix() . 'taskstimers.end_time - ' . db_prefix() . 'taskstimers.start_time END) as total_seconds, SUM(CASE WHEN ' . db_prefix() . 'taskstimers.end_time IS NULL THEN (' . time() . ' - ' . db_prefix() . 'taskstimers.start_time) * ' . db_prefix() . 'tasks.hourly_rate ELSE (' . db_prefix() . 'taskstimers.end_time - ' . db_prefix() . 'taskstimers.start_time) * ' . db_prefix() . 'tasks.hourly_rate END) as total_amount');
        $this->CI->db->from(db_prefix() . 'taskstimers');
        $this->CI->db->join(db_prefix() . 'tasks', db_prefix() . 'tasks.id = ' . db_prefix() . 'taskstimers.task_id');
        $this->CI->db->where(db_prefix() . 'tasks.rel_type', 'project');
        $this->CI->db->where(db_prefix() . 'tasks.rel_id', $project_id);
        // Note: billed column doesn't exist in this version, so we'll get all timesheets
        $timesheets = $this->CI->db->get()->row();
        
        $billing['unbilled_hours'] = $timesheets->total_seconds ? sec2qty($timesheets->total_seconds) : 0;
        $billing['unbilled_amount'] = $timesheets->total_amount ?? 0;
        
        // Get unbilled expenses
        $this->CI->db->select('SUM(amount) as total_amount');
        $this->CI->db->from(db_prefix() . 'expenses');
        $this->CI->db->where('project_id', $project_id);
        $this->CI->db->where('billable', 1);
        $this->CI->db->where('invoiceid IS NULL');
        $expenses = $this->CI->db->get()->row();
        
        $billing['unbilled_expenses'] = $expenses->total_amount ?? 0;
        
        // Get invoice statistics
        $this->CI->db->select('COUNT(*) as count, SUM(total) as total_amount');
        $this->CI->db->from(db_prefix() . 'invoices');
        $this->CI->db->where('project_id', $project_id);
        $invoices = $this->CI->db->get()->row();
        
        $billing['total_invoiced'] = $invoices->total_amount ?? 0;
        
        return $billing;
    }
    
    /**
     * Get recent activities
     */
    private function getRecentActivities($project_id) {
        // Check memory before starting
        $memory_before = memory_get_usage(true);
        pa_log_error('getRecentActivities - Memory before: ' . round($memory_before / 1024 / 1024, 2) . ' MB');
        
        $activities = [];
        $limit = (int) get_option('project_agent_context_activity_limit');
        if ($limit <= 0) { $limit = 10; } // Reduced from 50 to 10 to prevent memory issues
        
        pa_log_error('getRecentActivities - Using limit: ' . $limit);
        
        try {
            // Get recent task updates
            $this->CI->db->select(db_prefix() . 'tasks.name, ' . db_prefix() . 'tasks.dateadded');
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
            $this->CI->db->order_by('dateadded', 'DESC');
            $this->CI->db->limit($limit);
            
            pa_log_error('getRecentActivities - About to execute query');
            $tasks = $this->CI->db->get()->result_array();
            pa_log_error('getRecentActivities - Query executed successfully, result count: ' . count($tasks));
            
            foreach ($tasks as $task) {
                $activities[] = [
                    'type' => 'task',
                    'description' => 'Task "' . $task['name'] . '" status: ' . $task['status_name'],
                    'date' => $task['dateadded']
                ];
            }
            
            // Check memory after
            $memory_after = memory_get_usage(true);
            pa_log_error('getRecentActivities - Memory after: ' . round($memory_after / 1024 / 1024, 2) . ' MB, Used: ' . round(($memory_after - $memory_before) / 1024 / 1024, 2) . ' MB');
            
            return $activities;
        } catch (\Throwable $e) {
            pa_log_error('getRecentActivities - Error: ' . $e->getMessage());
            pa_log_error('getRecentActivities - Error trace: ' . $e->getTraceAsString());
            return [];
        }
    }
    
    /**
     * Identify project risks
     */
    private function identifyRisks($project_id, $context) {
        $risks = [];
        
        // Check for overdue tasks
        $overdue_tasks = array_filter($context['tasks'], function($task) {
            return $task['duedate'] && strtotime($task['duedate']) < time() && $task['status'] != 5;
        });
        
        if (count($overdue_tasks) > 0) {
            $risks[] = [
                'type' => 'overdue_tasks',
                'severity' => 'high',
                'description' => count($overdue_tasks) . ' tasks are overdue',
                'count' => count($overdue_tasks)
            ];
        }
        
        // Check for tasks without assignees
        $unassigned_tasks = array_filter($context['tasks'], function($task) {
            return $task['status'] != 5 && empty($task['assigned']);
        });
        
        if (count($unassigned_tasks) > 0) {
            $risks[] = [
                'type' => 'unassigned_tasks',
                'severity' => 'medium',
                'description' => count($unassigned_tasks) . ' tasks have no assignees',
                'count' => count($unassigned_tasks)
            ];
        }
        
        // Check for low progress
        if ($context['project']->progress < 25 && $context['project']->deadline) {
            $days_remaining = (strtotime($context['project']->deadline) - time()) / (60 * 60 * 24);
            if ($days_remaining < 30) {
                $risks[] = [
                    'type' => 'low_progress',
                    'severity' => 'high',
                    'description' => 'Project progress is low (' . $context['project']->progress . '%) with ' . round($days_remaining) . ' days remaining',
                    'progress' => $context['project']->progress,
                    'days_remaining' => round($days_remaining)
                ];
            }
        }
        
        return $risks;
    }
    
    /**
     * Identify project opportunities
     */
    private function identifyOpportunities($project_id, $context) {
        $opportunities = [];
        
        // Check for unbilled work
        if ($context['billing_status']['unbilled_amount'] > 0) {
            $opportunities[] = [
                'type' => 'unbilled_work',
                'description' => 'Unbilled work available: $' . number_format($context['billing_status']['unbilled_amount'], 2),
                'amount' => $context['billing_status']['unbilled_amount']
            ];
        }
        
        // Check for completed tasks that can be billed
        $completed_tasks = array_filter($context['tasks'], function($task) {
            return $task['status'] == 5 && $task['billable'] == 1;
        });
        
        if (count($completed_tasks) > 0) {
            $opportunities[] = [
                'type' => 'billable_tasks',
                'description' => count($completed_tasks) . ' completed tasks ready for billing',
                'count' => count($completed_tasks)
            ];
        }
        
        // Check for upcoming milestones
        $upcoming_milestones = array_filter($context['milestones'], function($milestone) {
            $due_date = strtotime($milestone['due_date']);
            $now = time();
            return $due_date > $now && $due_date <= ($now + 7 * 24 * 60 * 60); // Next 7 days
        });
        
        if (count($upcoming_milestones) > 0) {
            $opportunities[] = [
                'type' => 'upcoming_milestones',
                'description' => count($upcoming_milestones) . ' milestones due this week',
                'count' => count($upcoming_milestones)
            ];
        }
        
        return $opportunities;
    }
    
    /**
     * Build user context
     */
    public function buildUserContext($user_id) {
        $context = [
            'user' => null,
            'permissions' => [],
            'recent_projects' => [],
            'preferences' => []
        ];
        
        // Get user details
        $this->CI->db->select('*');
        $this->CI->db->from(db_prefix() . 'staff');
        $this->CI->db->where('staffid', $user_id);
        $user = $this->CI->db->get()->row();
        
        if ($user) {
            $context['user'] = $user;
            
            // Get user permissions
            $context['permissions'] = $this->getUserPermissions($user_id);
            
            // Get recent projects
            $context['recent_projects'] = $this->getUserRecentProjects($user_id);
            
            // Get user preferences
            $context['preferences'] = $this->getUserPreferences($user_id);
        }
        
        return $context;
    }
    
    /**
     * Get user permissions
     */
    private function getUserPermissions($user_id) {
        $permissions = [];
        $tblLegacy = db_prefix() . 'staffpermissions';
        $tblNew    = db_prefix() . 'staff_permissions';

        if ($this->CI->db->table_exists($tblLegacy)) {
            // Legacy schema: tblstaffpermissions (permissionid, staffid)
            $this->CI->db->select('permissionid');
            $this->CI->db->from($tblLegacy);
            $this->CI->db->where('staffid', $user_id);
            $rows = $this->CI->db->get()->result_array();
            foreach ($rows as $r) { if (isset($r['permissionid'])) { $permissions[] = $r['permissionid']; } }
        } elseif ($this->CI->db->table_exists($tblNew)) {
            // Newer schema: tblstaff_permissions (feature, capability, staff_id)
            $this->CI->db->select('feature, capability');
            $this->CI->db->from($tblNew);
            $this->CI->db->where('staff_id', $user_id);
            $rows = $this->CI->db->get()->result_array();
            foreach ($rows as $r) {
                $feat = isset($r['feature']) ? $r['feature'] : '';
                $cap  = isset($r['capability']) ? $r['capability'] : '';
                if ($feat !== '' && $cap !== '') { $permissions[] = $feat . ':' . $cap; }
            }
        }
        return $permissions;
    }
    
    /**
     * Get user recent projects
     */
    private function getUserRecentProjects($user_id) {
        $projects = db_prefix() . 'projects';
        $this->CI->db->select($projects . '.*');
        $this->CI->db->from(db_prefix() . 'project_members');
        $this->CI->db->join($projects, $projects . '.id = ' . db_prefix() . 'project_members.project_id');
        $this->CI->db->where('staff_id', $user_id);
        // Resolve a safe order column dynamically to avoid unknown column errors across versions
        $orderCol = 'id';
        try {
            $fields = $this->CI->db->list_fields($projects);
            $cands = ['datecreated','date_added','dateadded','date_created','start_date','date_start'];
            foreach ($cands as $c) { if (in_array($c, $fields, true)) { $orderCol = $c; break; } }
        } catch (\Throwable $e) {}
        $this->CI->db->order_by($projects . '.' . $orderCol, 'DESC');
        $this->CI->db->limit(5);
        return $this->CI->db->get()->result_array();
    }
    
    /**
     * Get user preferences
     */
    private function getUserPreferences($user_id) {
        $preferences = [
            'language' => get_option('staff_default_language'),
            'timezone' => get_option('staff_default_timezone'),
            'date_format' => get_option('staff_default_date_format'),
            'time_format' => get_option('staff_default_time_format')
        ];
        
        return $preferences;
    }
    
    /**
     * Build global context
     */
    public function buildGlobalContext() {
        $context = [
            'system_info' => [],
            'company_info' => [],
            'current_time' => [
                'datetime' => date('Y-m-d H:i:s'),
                'date' => date('Y-m-d'),
                'time' => date('H:i:s'),
                'timezone' => date_default_timezone_get(),
                'day_of_week' => date('l'),
                'week_number' => date('W'),
                'month' => date('F'),
                'year' => date('Y'),
                'timestamp' => time(),
                'is_weekend' => in_array((int)date('N'), [6,7])
            ],
            'system_status' => []
        ];
        
        // Get system information
        $context['system_info'] = [
            'perfex_version' => get_option('current_version'),
            'php_version' => PHP_VERSION,
            'database_type' => $this->CI->db->platform(),
            'timezone' => date_default_timezone_get()
        ];
        
        // Get company information
        $context['company_info'] = [
            'name' => get_option('companyname'),
            'country' => get_option('customer_default_country'),
            'currency' => get_option('customer_default_currency'),
            'timezone' => get_option('default_timezone')
        ];
        
        // Get system status
        $context['system_status'] = [
            'total_projects' => $this->getTotalProjects(),
            'active_projects' => $this->getActiveProjects(),
            'total_customers' => $this->getTotalCustomers(),
            'total_staff' => $this->getTotalStaff()
        ];
        
        return $context;
    }
    
    /**
     * Get total projects
     */
    private function getTotalProjects() {
        return $this->CI->db->count_all(db_prefix() . 'projects');
    }
    
    /**
     * Get active projects
     */
    private function getActiveProjects() {
        $this->CI->db->where('status', 0); // Active status
        return $this->CI->db->count_all_results(db_prefix() . 'projects');
    }
    
    /**
     * Get total customers
     */
    private function getTotalCustomers() {
        return $this->CI->db->count_all(db_prefix() . 'clients');
    }
    
    /**
     * Get total staff
     */
    private function getTotalStaff() {
        return $this->CI->db->count_all(db_prefix() . 'staff');
    }
}

/**
 * ProjectAgentContextBuilder – builds multi-layer context for AI
 * Layers: session, project, user, temporal, organization, memory, system
 */
class ProjectAgentContextBuilder {
    private $CI;
    public function __construct() { $this->CI = &get_instance(); }

    public function buildContext($session_id = null, $project_id = null, $user_id = null, $progressToken = null) {
        $ctx = [];
        
        // Add current time context first
        $ctx['current_time'] = [
            'datetime' => date('Y-m-d H:i:s'),
            'date' => date('Y-m-d'),
            'time' => date('H:i:s'),
            'timezone' => date_default_timezone_get(),
            'day_of_week' => date('l'),
            'week_number' => date('W'),
            'month' => date('F'),
            'year' => date('Y'),
            'timestamp' => time(),
            'is_weekend' => in_array((int)date('N'), [6,7])
        ];
        
        // Log start
        pa_log_error('Starting buildContext - session_id: ' . $session_id . ', project_id: ' . $project_id . ', user_id: ' . $user_id);
        
        try {
            if ($progressToken) { 
                project_agent_progress_add($progressToken, 'context_begin', ['session_id'=>$session_id,'project_id'=>$project_id,'user_id'=>$user_id]); 
                pa_log_error('Progress token added for context_begin');
            }
            
            // Session
            pa_log_error('Building session context...');
            $ctx['session'] = $this->buildSessionContext($session_id);
            pa_log_error('Session context built: ' . ($ctx['session'] ? 'SUCCESS' : 'NULL'));
            
            if ($progressToken) { 
                project_agent_progress_add($progressToken, 'session_loaded', ['has_session'=> (bool)$ctx['session']]); 
                pa_log_error('Progress token added for session_loaded');
            }
            
            // Project (reuse existing helper)
            if ($project_id) {
                pa_log_error('Building project context for project_id: ' . $project_id);
                $helper = new ProjectAgentContextHelper();
                
                if ($progressToken) { 
                    project_agent_progress_add($progressToken, 'project_loading', ['project_id'=>$project_id]); 
                    pa_log_error('Progress token added for project_loading');
                }
                
                try {
                    
                    $c = $helper->buildProjectContext($project_id);
                    pa_log_error(' Project context built successfully');
                    
                    // lightweight counts for progress
                    if ($progressToken) {
                        $counts = [
                            'tasks' => isset($c['tasks']) && is_array($c['tasks']) ? count($c['tasks']) : 0,
                            'milestones' => isset($c['milestones']) && is_array($c['milestones']) ? count($c['milestones']) : 0,
                            'activities' => isset($c['recent_activities']) && is_array($c['recent_activities']) ? count($c['recent_activities']) : 0,
                        ];
                        project_agent_progress_add($progressToken, 'project_loaded', $counts);
                        pa_log_error(' Progress token added for project_loaded with counts: ' . json_encode($counts));
                    }
                    $ctx = array_merge($ctx, $c);
                    pa_log_error(' Project context merged into main context');
                } catch (\Throwable $e) {
                    pa_log_error(' Error building project context: ' . $e->getMessage());
                    pa_log_error(' Project context error trace: ' . $e->getTraceAsString());
                }
            }
            
            // User (reuse existing helper)
            if (!$user_id) { 
                $user_id = function_exists('get_staff_user_id') ? get_staff_user_id() : null; 
                pa_log_error(' Auto-detected user_id: ' . $user_id);
            }
            
            if ($user_id) {
                pa_log_error(' Building user context for user_id: ' . $user_id);
                $helper = isset($helper) ? $helper : new ProjectAgentContextHelper();
                
                if ($progressToken) { 
                    project_agent_progress_add($progressToken, 'user_loading', ['user_id'=>$user_id]); 
                    pa_log_error(' Progress token added for user_loading');
                }
                
                try {
                    $uc = $helper->buildUserContext($user_id);
                    pa_log_error(' User context built successfully');
                    
                    if ($progressToken) { 
                        project_agent_progress_add($progressToken, 'user_loaded', ['recent_projects'=> isset($uc['recent_projects'])?count($uc['recent_projects']):0]); 
                        pa_log_error(' Progress token added for user_loaded');
                    }
                    $ctx = array_merge($ctx, $uc);
                    pa_log_error(' User context merged into main context');
                } catch (\Throwable $e) {
                    pa_log_error(' Error building user context: ' . $e->getMessage());
                    pa_log_error(' User context error trace: ' . $e->getTraceAsString());
                }
            }
            
            // Temporal
            pa_log_error(' Building temporal context...');
            try {
                $ctx['temporal'] = $this->buildTemporalContext();
                pa_log_error(' Temporal context built successfully');
                
                if ($progressToken) { 
                    project_agent_progress_add($progressToken, 'temporal_loaded', []); 
                    pa_log_error(' Progress token added for temporal_loaded');
                }
            } catch (\Throwable $e) {
                pa_log_error(' Error building temporal context: ' . $e->getMessage());
                pa_log_error(' Temporal context error trace: ' . $e->getTraceAsString());
            }
            
            // Organization (compact)
            pa_log_error(' Building organization context...');
            try {
                $ctx['organization'] = $this->buildOrganizationContext();
                pa_log_error(' Organization context built successfully');
                
                if ($progressToken) { 
                    project_agent_progress_add($progressToken, 'organization_loaded', []); 
                    pa_log_error(' Progress token added for organization_loaded');
                }
            } catch (\Throwable $e) {
                pa_log_error(' Error building organization context: ' . $e->getMessage());
                pa_log_error(' Organization context error trace: ' . $e->getTraceAsString());
            }
            
            // Memory (compact)
            if ($session_id) { 
                pa_log_error(' Building memory context for session_id: ' . $session_id);
                try {
                    $ctx['memory'] = $this->buildMemoryContext($session_id); 
                    pa_log_error(' Memory context built successfully');
                    
                    if ($progressToken) { 
                        project_agent_progress_add($progressToken, 'memory_loaded', ['recent'=> isset($ctx['memory']['recent'])?count($ctx['memory']['recent']):0]); 
                        pa_log_error(' Progress token added for memory_loaded');
                    }
                } catch (\Throwable $e) {
                    pa_log_error(' Error building memory context: ' . $e->getMessage());
                    pa_log_error(' Memory context error trace: ' . $e->getTraceAsString());
                }
            }
            
            // System (compact)
            pa_log_error(' Building system context...');
            try {
                $ctx['system'] = $this->buildSystemContext();
                pa_log_error(' System context built successfully');
                
                if ($progressToken) { 
                    project_agent_progress_add($progressToken, 'system_loaded', []); 
                    pa_log_error(' Progress token added for system_loaded');
                }
            } catch (\Throwable $e) {
                pa_log_error(' Error building system context: ' . $e->getMessage());
                pa_log_error(' System context error trace: ' . $e->getTraceAsString());
            }
            
            if ($progressToken) {
                // final compact summary
                pa_log_error(' Building final summary...');
                try {
                    $summary = [
                        'project_name' => isset($ctx['project']->name) ? $ctx['project']->name : (isset($ctx['project']['name']) ? $ctx['project']['name'] : null),
                        'tasks' => isset($ctx['tasks']) && is_array($ctx['tasks']) ? count($ctx['tasks']) : 0,
                        'milestones' => isset($ctx['milestones']) && is_array($ctx['milestones']) ? count($ctx['milestones']) : 0,
                        'activities' => isset($ctx['recent_activities']) && is_array($ctx['recent_activities']) ? count($ctx['recent_activities']) : 0,
                    ];
                    project_agent_progress_complete($progressToken, $summary);
                    pa_log_error(' Progress completed with summary: ' . json_encode($summary));
                } catch (\Throwable $e) {
                    pa_log_error(' Error completing progress: ' . $e->getMessage());
                    pa_log_error(' Progress completion error trace: ' . $e->getTraceAsString());
                }
            }
            
            pa_log_error(' Context building completed successfully');
            return $ctx;
            
        } catch (\Throwable $e) {
            pa_log_error(' Fatal error in buildContext: ' . $e->getMessage());
            pa_log_error(' Fatal error trace: ' . $e->getTraceAsString());
            throw $e;
        }
    }

    private function buildSessionContext($session_id) {
        pa_log_error(' buildSessionContext called with session_id: ' . $session_id);
        
        if (!$session_id) {
            pa_log_error(' No session_id provided, returning null');
            return null;
        }
        
        $tbl = db_prefix() . 'project_agent_sessions';
        pa_log_error(' Checking table existence: ' . $tbl);
        
        if (!$this->CI->db->table_exists($tbl)) {
            pa_log_error(' Table does not exist: ' . $tbl);
            return null;
        }
        
        pa_log_error(' Querying session data for session_id: ' . (int)$session_id);
        $row = $this->CI->db->get_where($tbl, ['session_id' => (int)$session_id])->row_array();
        
        if (!$row) {
            pa_log_error(' No session data found for session_id: ' . (int)$session_id);
            return null;
        }
        
        pa_log_error(' Session data found: ' . json_encode($row));
        
        $result = [
            'session_id' => (int)$row['session_id'],
            'project_id' => isset($row['project_id']) ? (int)$row['project_id'] : null,
            'user_id'    => isset($row['user_id']) ? (int)$row['user_id'] : null,
            'created_at' => $row['created_at'] ?? null,
            'updated_at' => $row['updated_at'] ?? null,
        ];
        
        pa_log_error(' Session context built: ' . json_encode($result));
        return $result;
    }

    private function buildTemporalContext() {
        pa_log_error(' buildTemporalContext called');
        
        try {
            $now = new DateTime();
            pa_log_error(' DateTime object created successfully');
            
            $result = [
                'current_datetime' => $now->format('Y-m-d H:i:s'),
                'current_date' => $now->format('Y-m-d'),
                'current_time' => $now->format('H:i:s'),
                'timezone' => date_default_timezone_get(),
                'day_of_week' => $now->format('l'),
                'week_number' => $now->format('W'),
                'month' => $now->format('F'),
                'year' => $now->format('Y'),
                'is_weekend' => in_array((int)$now->format('N'), [6,7]),
                'timestamp' => $now->getTimestamp(),
                'hour' => (int)$now->format('H'),
                'minute' => (int)$now->format('i'),
                'second' => (int)$now->format('s'),
                'day_of_month' => (int)$now->format('j'),
                'day_of_year' => (int)$now->format('z'),
                'quarter' => ceil((int)$now->format('n') / 3),
                'is_business_hours' => $this->isBusinessHours($now),
                'season' => $this->getSeason($now)
            ];
            
            pa_log_error(' Temporal context built: ' . json_encode($result));
            return $result;
        } catch (\Throwable $e) {
            pa_log_error(' Error in buildTemporalContext: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Check if current time is within business hours (9 AM - 5 PM, Monday-Friday)
     */
    private function isBusinessHours($datetime) {
        $hour = (int)$datetime->format('H');
        $dayOfWeek = (int)$datetime->format('N'); // 1 = Monday, 7 = Sunday
        
        return ($dayOfWeek >= 1 && $dayOfWeek <= 5) && ($hour >= 9 && $hour < 17);
    }
    
    /**
     * Get season based on current date
     */
    private function getSeason($datetime) {
        $month = (int)$datetime->format('n');
        
        if ($month >= 3 && $month <= 5) {
            return 'spring';
        } elseif ($month >= 6 && $month <= 8) {
            return 'summer';
        } elseif ($month >= 9 && $month <= 11) {
            return 'autumn';
        } else {
            return 'winter';
        }
    }

    private function buildOrganizationContext() {
        pa_log_error(' buildOrganizationContext called');
        
        try {
            $result = [
                'company_name' => get_option('companyname'),
                'timezone' => get_option('default_timezone'),
                'date_format' => get_option('dateformat'),
                'time_format' => get_option('time_format'),
            ];
            
            pa_log_error(' Organization context built: ' . json_encode($result));
            return $result;
        } catch (\Throwable $e) {
            pa_log_error(' Error in buildOrganizationContext: ' . $e->getMessage());
            throw $e;
        }
    }

    private function buildMemoryContext($session_id) {
        log_message('debug', '[PA][ContextBuilder] buildMemoryContext called with session_id: ' . $session_id);
        
        $tbl = db_prefix() . 'project_agent_memory_entries';
        log_message('debug', '[PA][ContextBuilder] Checking memory table existence: ' . $tbl);
        
        if (!$this->CI->db->table_exists($tbl)) {
            log_message('debug', '[PA][ContextBuilder] Memory table does not exist: ' . $tbl);
            return [];
        }
        
        try {
            $this->CI->db->where('session_id', (int)$session_id);
            $this->CI->db->order_by('created_at', 'DESC');
            $this->CI->db->limit(5);
            
            log_message('debug', '[PA][ContextBuilder] Querying memory entries for session_id: ' . (int)$session_id);
            $rows = $this->CI->db->get($tbl)->result_array();
            
            log_message('debug', '[PA][ContextBuilder] Found ' . count($rows) . ' memory entries');
            
            $out = [];
            foreach ($rows as $r) {
                $text = '';
                try { 
                    $c = json_decode($r['content_json'], true); 
                    if (isset($c['text'])) { 
                        $text = (string)$c['text']; 
                    } 
                } catch (\Throwable $e) {
                    log_message('debug', '[PA][ContextBuilder] Error parsing memory content_json: ' . $e->getMessage());
                }
                $out[] = [
                    'kind' => $r['kind'],
                    'text' => $text,
                    'created_at' => $r['created_at']
                ];
            }
            
            $result = ['recent' => $out];
            log_message('debug', '[PA][ContextBuilder] Memory context built with ' . count($out) . ' entries');
            return $result;
        } catch (\Throwable $e) {
            pa_log_error(' Error in buildMemoryContext: ' . $e->getMessage());
            throw $e;
        }
    }

    private function buildSystemContext() {
        return [
            'perfex_version' => get_option('current_version'),
            'ai_provider' => get_option('project_agent_ai_provider')
        ];
    }
}
