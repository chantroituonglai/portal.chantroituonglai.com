<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Project Agent Memory Helper
 * Manages memory entries and context building
 */

class ProjectAgentMemoryHelper {
    
    private $CI;
    
    public function __construct() {
        $this->CI = &get_instance();
        $this->CI->load->model('project_agent_model');
    }
    
    /**
     * Add memory entry
     */
    public function addEntry($session_id, $kind, $content, $scope = 'session', $project_id = null, $customer_id = null) {
        // Validate and normalize kind
        $valid_kinds = ['input', 'ai_response', 'action_call', 'action_result', 'action_result_summary', 'note', 'system_note', 'output', 'context_snapshot', 'analysis_summary', 'decision', 'plan', 'observation', 'warning', 'next_step', 'state_summary'];

        if (empty($kind) || !in_array($kind, $valid_kinds)) {
            // Log the issue and set a default kind
            log_message('error', 'PA Memory: Invalid or empty kind "' . $kind . '", defaulting to "system_note"');
            $kind = 'system_note';
        }

        // Validate session_id
        if (empty($session_id) || !is_numeric($session_id)) {
            log_message('error', 'PA Memory: Invalid session_id: ' . $session_id);
            return false;
        }

        // Validate content
        if (empty($content)) {
            log_message('error', 'PA Memory: Empty content for session ' . $session_id);
            return false;
        }

        $entry_data = [
            'session_id' => $session_id,
            'kind' => $kind,
            'content_json' => json_encode($content),
            'scope' => $scope ?: 'session',
            'project_id' => $project_id,
            'customer_id' => $customer_id,
            'created_at' => date('Y-m-d H:i:s')
        ];

        $result = $this->CI->project_agent_model->add_memory_entry($entry_data);

        if (!$result) {
            log_message('error', 'PA Memory: Failed to save entry for session ' . $session_id . ', kind: ' . $kind);
        }

        return $result;
    }
    
    /**
     * Get memory entries for session
     */
    public function getEntries($session_id, $limit = 50, $offset = 0) {
        return $this->CI->project_agent_model->get_memory_entries($session_id, $limit, $offset);
    }
    
    /**
     * Get memory entries by kind
     */
    public function getEntriesByKind($session_id, $kind, $limit = 50) {
        return $this->CI->project_agent_model->get_memory_entries_by_kind($session_id, $kind, $limit);
    }
    
    /**
     * Get latest memory entry by kind
     */
    public function getLatestEntry($session_id, $kind) {
        return $this->CI->project_agent_model->get_latest_memory_entry($session_id, $kind);
    }
    
    /**
     * Build context from memory entries
     */
    public function buildContext($session_id, $project_id = null) {
        $context = [
            'recent_entries' => [],
            'project_context' => null,
            'user_context' => null,
            'global_context' => null
        ];
        
        // Get recent entries
        $recent_entries = $this->getEntries($session_id, 10);
        $context['recent_entries'] = $recent_entries;
        
        // Get project context if available
        if ($project_id) {
            $project_context = $this->getLatestEntry($session_id, 'context_snapshot');
            if ($project_context) {
                $context['project_context'] = json_decode($project_context->content_json, true);
            }
        }
        
        // Get user context
        $user_context = $this->getLatestEntry($session_id, 'user_context');
        if ($user_context) {
            $context['user_context'] = json_decode($user_context->content_json, true);
        }
        
        // Get global context
        $global_context = $this->getLatestEntry($session_id, 'global_context');
        if ($global_context) {
            $context['global_context'] = json_decode($global_context->content_json, true);
        }
        
        return $context;
    }
    
    /**
     * Add user input to memory
     */
    public function addUserInput($session_id, $input, $project_id = null) {
        return $this->addEntry($session_id, 'input', [
            'text' => $input,
            'timestamp' => time(),
            'type' => 'user_message'
        ], 'session', $project_id);
    }
    
    /**
     * Add AI response to memory
     */
    public function addAiResponse($session_id, $response, $project_id = null) {
        return $this->addEntry($session_id, 'ai_response', [
            'text' => $response,
            'timestamp' => time(),
            'type' => 'ai_message'
        ], 'session', $project_id);
    }
    
    /**
     * Add action execution to memory
     */
    public function addActionExecution($session_id, $action_id, $params, $result, $project_id = null) {
        return $this->addEntry($session_id, 'action_result', [
            'action_id' => $action_id,
            'params' => $params,
            'result' => $result,
            'timestamp' => time(),
            'status' => 'completed'
        ], 'session', $project_id);
    }
    
    /**
     * Add context snapshot
     */
    public function addContextSnapshot($session_id, $context_data, $project_id = null) {
        return $this->addEntry($session_id, 'context_snapshot', [
            'context' => $context_data,
            'timestamp' => time(),
            'type' => 'project_context'
        ], 'project', $project_id);
    }
    
    /**
     * Add analysis summary
     */
    public function addAnalysisSummary($session_id, $summary, $project_id = null) {
        return $this->addEntry($session_id, 'analysis_summary', [
            'summary' => $summary,
            'timestamp' => time(),
            'type' => 'ai_analysis'
        ], 'session', $project_id);
    }
    
    /**
     * Add decision to memory
     */
    public function addDecision($session_id, $decision, $reasoning, $project_id = null) {
        return $this->addEntry($session_id, 'decision', [
            'decision' => $decision,
            'reasoning' => $reasoning,
            'timestamp' => time(),
            'type' => 'user_decision'
        ], 'session', $project_id);
    }
    
    /**
     * Add plan to memory
     */
    public function addPlan($session_id, $plan, $project_id = null) {
        return $this->addEntry($session_id, 'plan', [
            'plan' => $plan,
            'timestamp' => time(),
            'type' => 'action_plan'
        ], 'session', $project_id);
    }
    
    /**
     * Add observation to memory
     */
    public function addObservation($session_id, $observation, $project_id = null) {
        return $this->addEntry($session_id, 'observation', [
            'observation' => $observation,
            'timestamp' => time(),
            'type' => 'system_observation'
        ], 'session', $project_id);
    }
    
    /**
     * Add warning to memory
     */
    public function addWarning($session_id, $warning, $project_id = null) {
        return $this->addEntry($session_id, 'warning', [
            'warning' => $warning,
            'timestamp' => time(),
            'type' => 'system_warning'
        ], 'session', $project_id);
    }
    
    /**
     * Add next step to memory
     */
    public function addNextStep($session_id, $next_step, $project_id = null) {
        return $this->addEntry($session_id, 'next_step', [
            'next_step' => $next_step,
            'timestamp' => time(),
            'type' => 'suggestion'
        ], 'session', $project_id);
    }
    
    /**
     * Add state summary to memory
     */
    public function addStateSummary($session_id, $state_summary, $project_id = null) {
        return $this->addEntry($session_id, 'state_summary', [
            'state_summary' => $state_summary,
            'timestamp' => time(),
            'type' => 'system_summary'
        ], 'session', $project_id);
    }
    
    /**
     * Add system note to memory
     */
    public function addSystemNote($session_id, $note, $project_id = null) {
        return $this->addEntry($session_id, 'system_note', [
            'note' => $note,
            'timestamp' => time(),
            'type' => 'system_note'
        ], 'session', $project_id);
    }
    
    /**
     * Clean old memory entries
     */
    public function cleanOldEntries($days = 30) {
        return $this->CI->project_agent_model->clean_old_memory_entries($days);
    }
    
    /**
     * Get memory statistics
     */
    public function getMemoryStats($session_id) {
        return $this->CI->project_agent_model->get_session_stats($session_id);
    }
}
