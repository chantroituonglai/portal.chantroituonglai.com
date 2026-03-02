<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Project Agent AI Integration Helper
 * Integrates Project Agent with Core AI Services and GeminiAI
 */

// Load debug helper
if (!function_exists('pa_log_error')) {
    require_once(__DIR__ . '/project_agent_debug_helper.php');
}

class ProjectAgentAiIntegration {
    
    private $CI;
    private $aiProvider;
    
    public function __construct() {
        $this->CI = &get_instance();
        $this->initializeAiProvider();
    }
    
    /**
     * Try fallback AI provider when current one fails
     */
    private function tryFallbackProvider() {
        pa_log_error('Attempting fallback AI provider...');
        
        // If current provider is GeminiAI, try OpenAI
        if ($this->aiProvider && get_class($this->aiProvider) === 'Perfexcrm\Geminiai\GeminiProvider') {
            pa_log_error('Current provider is GeminiAI, trying OpenAI fallback...');
            try {
                $this->aiProvider = \app\services\ai\AiProviderRegistry::getProvider('openai');
                if ($this->aiProvider) {
                    pa_log_error('Fallback to OpenAI successful: ' . get_class($this->aiProvider));
                    return;
                }
            } catch (\Throwable $e) {
                pa_log_error('OpenAI fallback failed: ' . $e->getMessage());
            }
        }
        
        // If current provider is OpenAI, try GeminiAI
        if ($this->aiProvider && get_class($this->aiProvider) === 'app\services\ai\OpenAiProvider') {
            pa_log_error('Current provider is OpenAI, trying GeminiAI fallback...');
            try {
                if ($this->isGeminiaiModuleInstalled()) {
                    $this->aiProvider = \app\services\ai\AiProviderRegistry::getProvider('geminiai');
                    if ($this->aiProvider) {
                        pa_log_error('Fallback to GeminiAI successful: ' . get_class($this->aiProvider));
                        return;
                    }
                }
            } catch (\Throwable $e) {
                pa_log_error('GeminiAI fallback failed: ' . $e->getMessage());
            }
        }
        
        pa_log_error('No fallback provider available');
    }

    /**
     * Initialize AI Provider from registry
     */
    private function initializeAiProvider() {
        pa_log_error('Initializing AI Provider...');
        try {
            // First priority: Try to get GeminiAI provider (FHC's module)
            if ($this->isGeminiaiModuleInstalled()) {
                pa_log_error('GeminiAI module is installed, trying to get provider...');
                $this->aiProvider = \app\services\ai\AiProviderRegistry::getProvider('geminiai');
                pa_log_error('GeminiAI provider obtained: ' . get_class($this->aiProvider));
                log_message('error', 'Project Agent: Using GeminiAI provider (FHC module)');
                return;
            } else {
                pa_log_error('GeminiAI module is NOT installed');
            }


        } catch (\Throwable $e) {
            log_message('error', 'Project Agent: GeminiAI provider not available - ' . $e->getMessage());
        }
        
        try {
            // Second priority: Try to get OpenAI provider (Perfex default)
            pa_log_error('Trying OpenAI provider...');
            $this->aiProvider = \app\services\ai\AiProviderRegistry::getProvider('openai');
            pa_log_error('OpenAI provider obtained: ' . get_class($this->aiProvider));
            log_message('error', 'Project Agent: Using OpenAI provider (Perfex default)');
            return;
        } catch (\Throwable $e) {
            pa_log_error('OpenAI provider failed: ' . $e->getMessage());
            log_message('error', 'Project Agent: OpenAI provider not available - ' . $e->getMessage());
        }
        
        try {
            // Third priority: Try any available provider
            pa_log_error('Trying fallback providers...');
            $providers = \app\services\ai\AiProviderRegistry::getAllProviders();
            pa_log_error('Available providers: ' . json_encode(array_keys($providers)));
            if (!empty($providers)) {
                $firstProvider = array_values($providers)[0];
                if (is_array($firstProvider) && isset($firstProvider['provider'])) {
                    $this->aiProvider = $firstProvider['provider'];
                    $name = isset($firstProvider['name']) ? $firstProvider['name'] : (isset($firstProvider['id']) ? $firstProvider['id'] : 'unknown');
                    pa_log_error('Fallback provider obtained: ' . get_class($this->aiProvider) . ' (' . $name . ')');
                    log_message('error', 'Project Agent: Using fallback provider - ' . $name);
                    return;
                }
            }
        } catch (\Throwable $e) {
            pa_log_error('getAllProviders failed: ' . $e->getMessage());
            log_message('error', 'Project Agent: getAllProviders failed - ' . $e->getMessage());
        }
        
        // If still no provider, log warning and set to null
        $this->aiProvider = null;
        pa_log_error('No AI providers available - setting to null');
        log_message('warning', 'Project Agent: No AI providers registered. Please install and configure an AI module (OpenAI or GeminiAI).');
    }

    /**
     * Conditional debug logger based on module option
     */
    private function pa_debug($message) {
        try {
            $enabled = (int) $this->safe_get_option('project_agent_debug_enabled');
            if ($enabled) { log_message('error', is_string($message) ? $message : json_encode($message)); }
        } catch (\Throwable $e) {
            // ignore
        }
    }
    
    /**
     * Safe get_option with proper table prefix handling and memory optimization
     */
    private function safe_get_option($name) {
        try {
            $CI = &get_instance();
            $CI->db->select('value');
            $CI->db->where(db_prefix() . 'options.name', $name);
            $CI->db->limit(1); // Prevent large result sets
            $row = $CI->db->get(db_prefix() . 'options')->row();
            
            // Force garbage collection after query
            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }
            
            return $row ? $row->value : null;
        } catch (\Throwable $e) {
            $this->pa_debug('[PA][safe_get_option] Error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Check if GeminiAI module is installed and active
     */
    public function isGeminiaiModuleInstalled() {
        $CI = &get_instance();
        
        // Check if module directory exists
        if (!is_dir(module_dir_path('geminiai'))) {
            return false;
        }
        
        // Check if module is registered in database
        $CI->db->select('active');
        $CI->db->from(db_prefix() . 'modules');
        $CI->db->where('module_name', 'geminiai');
        $module = $CI->db->get()->row();
        
        if (!$module || !$module->active) {
            return false;
        }
        
        // Check if GeminiAI provider is registered
        try {
            \app\services\ai\AiProviderRegistry::getProvider('geminiai');
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Get GeminiAI installation recommendation
     */
    public function getGeminiaiRecommendation() {
        if ($this->isGeminiaiModuleInstalled()) {
            return [
                'installed' => true,
                'message' => 'GeminiAI module is installed and active',
                'provider' => 'geminiai'
            ];
        }
        
        return [
            'installed' => false,
            'message' => 'For best AI quality, install GeminiAI module by FHC',
            'recommendation' => [
                'title' => 'Install GeminiAI Module',
                'description' => 'GeminiAI module provides superior AI capabilities for Project Agent',
                'benefits' => [
                    'Better response quality',
                    'Faster processing',
                    'More accurate project analysis',
                    'Enhanced context understanding'
                ],
                'installation_url' => admin_url('modules'),
                'module_name' => 'geminiai'
            ]
        ];
    }
    
    /**
     * Get available AI providers
     */
    public function getAvailableProviders() {
        try {
            return \app\services\ai\AiProviderRegistry::getAllProviders();
        } catch (\Throwable $e) {
            return [];
        }
    }
    
    /**
     * Check if AI is available
     */
    public function isAiAvailable() {
        // More thorough check - ensure provider is not just initialized but actually functional
        if ($this->aiProvider === null) {
            return false;
        }
        
        // Additional check: try to get provider name to ensure it's properly loaded
        try {
            $name = $this->aiProvider->getName();
            return !empty($name);
        } catch (\Throwable $e) {
            log_message('error', 'Project Agent: AI provider check failed - ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Generate AI response using 2-phase flow:
     * 1) Extract a STRICT-JSON action plan
     * 2) Execute actions, then ask model to compose conversational summary
     */
    public function generateResponse($prompt, $context = []) {
        pa_log_error('generateResponse called with prompt length: ' . strlen($prompt) . ', context keys: ' . (is_array($context) ? implode(', ', array_keys($context)) : 'NOT_ARRAY'));
        
        if (!$this->aiProvider) {
            pa_log_error('No AI provider available');
            // Return a friendly, non-fatal response so the UI doesn't break
            return [
                'success' => false,
                'error' => 'No AI provider available'
            ];
        }
        
        pa_log_error('AI provider available, proceeding with context building');
        try {
            $token = isset($context['client_token']) ? (string)$context['client_token'] : null;
            pa_log_error('Client token: ' . $token);
            
            // Ensure available actions list present in context (DB-defined, active)
            if (empty($context['available_actions']) || !is_array($context['available_actions'])) {
                pa_log_error('Injecting available_actions into context');
                $this->CI->load->model('project_agent_model');
                $rows = (array) $this->CI->project_agent_model->get_actions(true); // active only
                $avail = [];
                foreach ($rows as $r) {
                    if (isset($r['action_id'])) {
                        $avail[$r['action_id']] = [
                            'name' => isset($r['name']) ? $r['name'] : $r['action_id'],
                            'description' => isset($r['description']) ? $r['description'] : '',
                            'params_schema' => isset($r['params_schema']) ? $r['params_schema'] : [],
                            'param_mapping' => isset($r['param_mapping']) ? $r['param_mapping'] : [],
                            'prompt_override' => isset($r['prompt_override']) ? $r['prompt_override'] : '',
                        ];
                    }
                }
                $context['available_actions'] = $avail;
                pa_log_error('Injected available_actions into context: ' . implode(', ', array_keys($avail)));
            } else {
                pa_log_error('available_actions already present in context: ' . implode(', ', array_keys($context['available_actions'])));
            }
            
            // Enrich context using builder (session/project/user awareness)
            try {
                pa_log_error('Starting context building for token: ' . $token);
                pa_log_error('Memory before context building: ' . round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB');
                
                $builder = new ProjectAgentContextBuilder();
                pa_log_error('Context builder initialized');
                $sessionId = isset($context['session_id']) ? (int)$context['session_id'] : null;
                $projectId = null;
                if (isset($context['project']) && is_object($context['project']) && isset($context['project']->id)) { $projectId = (int)$context['project']->id; }
                elseif (isset($context['project_id'])) { $projectId = (int)$context['project_id']; }
                $userId = isset($context['user_id']) ? (int)$context['user_id'] : (function_exists('get_staff_user_id') ? get_staff_user_id() : null);
                pa_log_error('Context params - sessionId: ' . $sessionId . ', projectId: ' . $projectId . ', userId: ' . $userId . ', token: ' . $token);
                
                if ($token) { 
                    project_agent_progress_init($token); 
                    pa_log_error('Progress initialized for token: ' . $token);
                }
                
                // Check memory limit before building context
                $memory_limit = ini_get('memory_limit');
                $current_memory = memory_get_usage(true);
                pa_log_error('Memory limit: ' . $memory_limit . ', Current: ' . round($current_memory / 1024 / 1024, 2) . ' MB');
                
                pa_log_error('About to call buildContext with params: sessionId=' . $sessionId . ', projectId=' . $projectId . ', userId=' . $userId . ', token=' . $token);
                $full = $builder->buildContext($sessionId, $projectId, $userId, $token);
                pa_log_error('buildContext returned successfully for token: ' . $token);
                pa_log_error('Context keys returned: ' . (is_array($full) ? implode(', ', array_keys($full)) : 'NOT_ARRAY'));
                pa_log_error('Memory after context building: ' . round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB');
                $context = array_merge($full, $context);
                pa_log_error('Context merged successfully, final keys: ' . implode(', ', array_keys($context)));
            } catch (\Throwable $e) { 
                pa_log_error('Context building failed: ' . $e->getMessage());
                pa_log_error('Memory error details: ' . $e->getTraceAsString());
            }
            // Phase 1: Strict JSON extraction of actions (with retry/compact fallback)
            $maxList = (int) $this->safe_get_option('project_agent_max_actions_in_prompt');
            if ($maxList <= 0) { $maxList = 0; }
            if ($token) { project_agent_progress_add($token, 'ai_phase', ['step' => 'extract_start','user_id'=>isset($context['user_id'])?$context['user_id']:null]); }
            $extractionPrompt = $this->buildActionExtractionPrompt($prompt, $context, false, $maxList);
            pa_log_error('Action extraction prompt length: ' . strlen($extractionPrompt) . ' full="' . $extractionPrompt . '"');
            pa_log_error('AI Provider: ' . ($this->aiProvider ? get_class($this->aiProvider) : 'NULL'));
            try {
                $rawExtraction = $this->aiProvider->chat($extractionPrompt);
                pa_log_error('AI Provider chat() returned: ' . gettype($rawExtraction) . ' - ' . var_export($rawExtraction, true));
                
                // Check if current provider has quota exceeded and try fallback
                if (empty($rawExtraction) && method_exists($this->aiProvider, 'isQuotaExceeded') && $this->aiProvider->isQuotaExceeded()) {
                    pa_log_error('Current AI provider quota exceeded, trying fallback...');
                    $this->tryFallbackProvider();
                    if ($this->aiProvider) {
                        $rawExtraction = $this->aiProvider->chat($extractionPrompt);
                        pa_log_error('Fallback AI Provider chat() returned: ' . gettype($rawExtraction) . ' - ' . var_export($rawExtraction, true));
                    }
                }
            } catch (\Throwable $e) {
                pa_log_error('AI Provider chat() failed: ' . $e->getMessage());
                $rawExtraction = null;
            }
            $rawLen = (is_string($rawExtraction)?strlen($rawExtraction):0);
            pa_log_error('Action extraction raw length: ' . $rawLen . ', content: ' . substr($rawExtraction, 0, 200));
            $plan = ($rawLen>0) ? $this->parseJsonResponse($rawExtraction) : null;
            if (!$plan) {
                if ($token) { project_agent_progress_add($token, 'ai_phase', ['step' => 'extract_retry','user_id'=>isset($context['user_id'])?$context['user_id']:null]); }
                $compactPrompt = $this->buildActionExtractionPrompt($prompt, $context, true, 12);
                pa_log_error('Action extraction compact prompt length: ' . strlen($compactPrompt));
                
                try {
                    $rawExtraction2 = $this->aiProvider->chat($compactPrompt);
                    $rawLen2 = (is_string($rawExtraction2)?strlen($rawExtraction2):0);
                    pa_log_error('Action extraction compact raw length: ' . $rawLen2);
                    
                    // Check if fallback needed for compact prompt too
                    if ($rawLen2 == 0 && method_exists($this->aiProvider, 'isQuotaExceeded') && $this->aiProvider->isQuotaExceeded()) {
                        pa_log_error('Compact prompt also failed due to quota, trying fallback...');
                        $this->tryFallbackProvider();
                        if ($this->aiProvider) {
                            $rawExtraction2 = $this->aiProvider->chat($compactPrompt);
                            $rawLen2 = (is_string($rawExtraction2)?strlen($rawExtraction2):0);
                            pa_log_error('Fallback compact prompt raw length: ' . $rawLen2);
                        }
                    }
                    
                    if ($rawLen2>0) { $plan = $this->parseJsonResponse($rawExtraction2); }
                } catch (\Throwable $e) {
                    pa_log_error('Compact prompt failed: ' . $e->getMessage());
                }
            }
            if ($token) { project_agent_progress_add($token, 'ai_phase', ['step' => 'extract_done', 'has_plan' => (bool)$plan,'user_id'=>isset($context['user_id'])?$context['user_id']:null]); }
            if (!$plan) {
                // Fallback: no plan parsed
                $plan = ['intent' => 'general_query', 'actions' => [], 'context_needed' => []];
            }
            // Execute actions if any
            $executedActions = [];
            if (!empty($plan['actions']) && is_array($plan['actions'])) {
                foreach ($plan['actions'] as $act) {
                    $aid = isset($act['action_id']) ? $act['action_id'] : '';
                    $params = isset($act['params']) ? $act['params'] : [];
                    $desc = isset($act['description']) ? $act['description'] : '';
                    
                    // Validate action exists in available actions
                    if (!isset($context['available_actions'][$aid])) {
                        pa_log_error('Action not found in available actions: ' . $aid);
                        $res = [
                            'success' => false,
                            'error' => 'Action not available: ' . $aid
                        ];
                    } else {
                        if ($token) { project_agent_progress_add($token, 'action_start', ['action_id'=>$aid,'description'=>$desc,'user_id'=>isset($context['user_id'])?$context['user_id']:null]); }
                        $res = $this->executeAction($aid, $params, $context);
                        if (isset($res['success']) && !$res['success']) {
                            $err = isset($res['error']) ? (string)$res['error'] : 'unknown_error';
                            log_message('error', '[PA][action] '.$aid.' failed: '.$err);
                        }
                    }
                    
                    $executedActions[] = [
                        'action_id' => $aid,
                        'params' => $params,
                        'result' => $res,
                        'description' => $desc,
                    ];
                    if ($token) {
                        $payload = ['action_id'=>$aid,'ok'=> (bool)($res['success'] ?? false),'user_id'=>isset($context['user_id'])?$context['user_id']:null];
                        if (!(bool)($res['success'] ?? false) && isset($res['error'])) { $payload['error'] = (string)$res['error']; }
                        project_agent_progress_add($token, 'action_done', $payload);
                    }
                }
            }
            // Phase 2: Conversational finalization based on results
            if ($token) { project_agent_progress_add($token, 'ai_phase', ['step' => 'finalize_start','user_id'=>isset($context['user_id'])?$context['user_id']:null]); }
            $finalText = $this->generateConversationalResponse($prompt, $executedActions, $context);
            if ($token) { project_agent_progress_add($token, 'ai_phase', ['step' => 'finalize_done','user_id'=>isset($context['user_id'])?$context['user_id']:null]); }
            return [
                'success' => true,
                'response' => $finalText,
                'provider' => $this->aiProvider->getName(),
                'executed_actions' => $executedActions,
                'suggested_actions' => isset($plan['actions']) ? $plan['actions'] : [],
            ];
        } catch (Exception $e) {
            pa_log_error('Project Agent AI Error: ' . $e->getMessage());
            
            // Check if user is admin - show full technical error
            $isAdmin = has_permission('project_agent', '', 'admin');
            
            if ($isAdmin) {
                // Admin gets full technical error details
                $technicalError = $this->buildTechnicalErrorMessage($e, $context);
            return [
                    'success' => true, 
                    'response' => $technicalError, 
                    'provider' => $this->aiProvider ? $this->aiProvider->getName() : 'n/a', 
                    'executed_actions' => [], 
                    'suggested_actions' => [], 
                    'explainer' => true,
                    'is_html' => true
                ];
            } else {
                // Regular users get friendly error message
                $friendly = $this->buildFriendlyErrorMessage($e->getMessage(), $context);
                if ($friendly) {
                    return [ 'success' => true, 'response' => $friendly, 'provider' => $this->aiProvider ? $this->aiProvider->getName() : 'n/a', 'executed_actions' => [], 'suggested_actions' => [], 'explainer' => true ];
                }
                return [ 'success' => false, 'error' => 'AI generation failed: ' . $e->getMessage() ];
            }
        }
    }

    /**
     * Build technical error message for admin users
     */
    private function buildTechnicalErrorMessage($exception, $context) {
        $html = '<div class="technical-error-report">';
        $html .= '<h5><i class="fa fa-exclamation-triangle text-danger"></i> Technical Error Report</h5>';
        
        // Basic error info
        $html .= '<div class="error-section mb-3">';
        $html .= '<h6>Error Details:</h6>';
        $html .= '<ul class="list-unstyled">';
        $html .= '<li><strong>Type:</strong> ' . htmlspecialchars(get_class($exception)) . '</li>';
        $html .= '<li><strong>Message:</strong> ' . htmlspecialchars($exception->getMessage()) . '</li>';
        $html .= '<li><strong>File:</strong> ' . htmlspecialchars($exception->getFile()) . '</li>';
        $html .= '<li><strong>Line:</strong> ' . $exception->getLine() . '</li>';
        $html .= '<li><strong>Time:</strong> ' . date('Y-m-d H:i:s') . '</li>';
        $html .= '</ul>';
        $html .= '</div>';
        
        // Stack trace
        $html .= '<div class="error-section mb-3">';
        $html .= '<h6>Stack Trace:</h6>';
        $html .= '<pre class="bg-light p-2 small" style="max-height: 300px; overflow-y: auto;">';
        $html .= htmlspecialchars($exception->getTraceAsString());
        $html .= '</pre>';
        $html .= '</div>';
        
        // Context info
        $html .= '<div class="error-section mb-3">';
        $html .= '<h6>Context Information:</h6>';
        $html .= '<ul class="list-unstyled">';
        $html .= '<li><strong>AI Provider:</strong> ' . ($this->aiProvider ? $this->aiProvider->getName() : 'None') . '</li>';
        $html .= '<li><strong>Session ID:</strong> ' . (isset($context['session_id']) ? $context['session_id'] : 'N/A') . '</li>';
        $html .= '<li><strong>Project ID:</strong> ' . (isset($context['project_id']) ? $context['project_id'] : 'N/A') . '</li>';
        $html .= '<li><strong>User ID:</strong> ' . (isset($context['user_id']) ? $context['user_id'] : 'N/A') . '</li>';
        $html .= '</ul>';
        $html .= '</div>';
        
        // Debugging steps
        $html .= '<div class="error-section mb-3">';
        $html .= '<h6>Debugging Steps:</h6>';
        $html .= '<ol>';
        $html .= '<li>Check application logs in <code>application/logs/</code></li>';
        $html .= '<li>Verify AI provider configuration and API keys</li>';
        $html .= '<li>Check database connectivity and table structure</li>';
        $html .= '<li>Enable debug logging in Project Agent Settings</li>';
        $html .= '<li>Test AI provider connection manually</li>';
        $html .= '<li>Check server error logs for additional details</li>';
        $html .= '</ol>';
        $html .= '</div>';
        
        // Quick actions
        $html .= '<div class="error-section">';
        $html .= '<h6>Quick Actions:</h6>';
        $html .= '<div class="btn-group btn-group-sm" role="group">';
        $html .= '<button class="btn btn-outline-primary" onclick="copyErrorToClipboard()"><i class="fa fa-copy"></i> Copy Error</button>';
        $html .= '<button class="btn btn-outline-secondary" onclick="refreshPage()"><i class="fa fa-refresh"></i> Refresh</button>';
        $html .= '<button class="btn btn-outline-info" onclick="openHealthCheck()"><i class="fa fa-heartbeat"></i> Health Check</button>';
        $html .= '</div>';
        $html .= '</div>';
        
        $html .= '</div>';
        
        // Add JavaScript for quick actions
        $html .= '<script>';
        $html .= 'function copyErrorToClipboard() {';
        $html .= '  var errorText = "' . addslashes($exception->getMessage()) . '";';
        $html .= '  navigator.clipboard.writeText(errorText).then(function() {';
        $html .= '    alert("Error copied to clipboard");';
        $html .= '  });';
        $html .= '}';
        $html .= 'function refreshPage() { location.reload(); }';
        $html .= 'function openHealthCheck() { window.open("' . admin_url('project_agent/health') . '", "_blank"); }';
        $html .= '</script>';
        
        return $html;
    }

    /**
     * Generate a user-friendly error message.
     * If Error Explainer is enabled, craft a helpful message and include admin tips when user has admin permission.
     */
    private function buildFriendlyErrorMessage($rawError, $context) {
        try {
            $enabled = (int) $this->safe_get_option('project_agent_error_explainer_enabled');
            if (!$enabled) { return null; }
            $isAdmin = has_permission('project_agent', '', 'admin');

            // Build a compact, role-aware prompt for the mini agent
            $lines = [];
            $lines[] = 'You are an Error Explainer Mini Agent.';
            $lines[] = 'Explain the following application error in friendly, plain language.';
            $lines[] = 'Then provide 2-4 actionable steps.';
            $lines[] = 'Return your response as HTML content (no Markdown, no code fences). Use <p>, <ul>, <li>.';
            if ($isAdmin) {
                $lines[] = 'User role: admin. Include an Admin Tips section with concrete remediation steps.';
            } else {
                $lines[] = 'User role: standard. Avoid admin-only steps; suggest what the user can try.';
            }
            $lines[] = '';
            $lines[] = 'RAW ERROR:';
            $lines[] = (string) $rawError;
            $lines[] = '';
            $lines[] = 'Return your response as a JSON object with this structure:';
            $lines[] = '{';
            $lines[] = '  "explanation": "Short explanation (1-2 lines)",';
            $lines[] = '  "user_steps": ["Step 1", "Step 2", "Step 3"],';
            if ($isAdmin) { 
                $lines[] = '  "admin_tips": ["Admin tip 1", "Admin tip 2"]';
            }
            $lines[] = '}';
            $prompt = implode("\n", $lines);

            // Use standalone mini-agent (Gemini HTTP) to avoid provider recursion
            $response = $this->miniAgentExplainError($prompt);
            if (is_string($response) && trim($response) !== '') { 
                // Try to parse as JSON first
                $jsonResponse = json_decode($response, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($jsonResponse)) {
                    // Convert JSON to HTML format
                    $html = '<p>' . htmlspecialchars($jsonResponse['explanation'] ?? 'Error explanation') . '</p>';
                    
                    if (!empty($jsonResponse['user_steps']) && is_array($jsonResponse['user_steps'])) {
                        $html .= '<p>What you can try:</p><ul>';
                        foreach ($jsonResponse['user_steps'] as $step) {
                            $html .= '<li>' . htmlspecialchars($step) . '</li>';
                        }
                        $html .= '</ul>';
                    }
                    
                    if ($isAdmin && !empty($jsonResponse['admin_tips']) && is_array($jsonResponse['admin_tips'])) {
                        $html .= '<p>Admin tips:</p><ul>';
                        foreach ($jsonResponse['admin_tips'] as $tip) {
                            $html .= '<li>' . htmlspecialchars($tip) . '</li>';
                        }
                        $html .= '</ul>';
                    }
                    
                    return $html;
                } else {
                    // Fallback to raw response if not JSON
                    return trim($response);
                }
            }

            // Fallback manual message if provider unavailable
            $msg = [];
            $msg[] = 'Oops! Something went wrong while generating the AI answer.';
            $msg[] = '';
            $msg[] = 'What happened:';
            $msg[] = '- ' . (string)$rawError;
            $msg[] = '';
            if ($isAdmin) {
                $msg[] = 'Admin tips:';
                $msg[] = '- Check application logs for details (application/logs).';
                $msg[] = '- Ensure DB schema and module migrations are up-to-date (Project Agent → Health).';
                $msg[] = '- If prompts are too large, reduce available actions or enable compact prompts.';
                $msg[] = '- Enable Debug Logging in Project Agent → Settings to inspect prompts/responses.';
            } else {
                $msg[] = 'What you can try:';
                $msg[] = '- Rephrase the question in a simpler way.';
                $msg[] = '- Provide any missing details (e.g., project or task).';
            }
            return implode("\n", $msg);
        } catch (\Throwable $e) { return null; }
    }

    /**
     * Standalone Gemini mini-agent call using module option API key.
     * Avoids using current provider to prevent recursion on provider errors.
     */
    private function miniAgentExplainError($prompt) {
        $apiKey = (string) $this->safe_get_option('project_agent_error_explainer_api_key');
        if (!$apiKey) { 
            $this->pa_debug('[PA][mini-agent] No API key configured');
            return null; 
        }
        
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . rawurlencode($apiKey);
        $payload = [
            'contents' => [
                [ 'parts' => [ [ 'text' => $prompt ] ] ]
            ],
            'generationConfig' => [
                'temperature' => 0.3,
                'maxOutputTokens' => 512,
                'responseMimeType' => 'application/json'
            ]
        ];
        $body = json_encode($payload);
        
        $this->pa_debug('[PA][mini-agent] Request URL: ' . $url);
        $this->pa_debug('[PA][mini-agent] Request payload: ' . substr($body, 0, 500) . '...');
        
        try {
            // Prefer cURL when available
            if (function_exists('curl_init')) {
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 8);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For development
                $resp = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curlError = curl_error($ch);
                curl_close($ch);
                
                $this->pa_debug('[PA][mini-agent] HTTP Code: ' . $httpCode);
                if ($curlError) {
                    $this->pa_debug('[PA][mini-agent] cURL Error: ' . $curlError);
                    return null;
                }
            } else {
                // Fallback to stream context
                $ctx = stream_context_create([
                    'http' => [
                        'method' => 'POST',
                        'header' => "Content-Type: application/json\r\n",
                        'content' => $body,
                        'timeout' => 8
                    ]
                ]);
                $resp = @file_get_contents($url, false, $ctx);
                $httpCode = 200; // best-effort
            }
            
            if (!is_string($resp) || trim($resp) === '') { 
                $this->pa_debug('[PA][mini-agent] Empty response');
                return null; 
            }
            
            $this->pa_debug('[PA][mini-agent] Raw response: ' . substr($resp, 0, 1000) . '...');
            
            $decoded = json_decode($resp, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->pa_debug('[PA][mini-agent] JSON decode error: ' . json_last_error_msg());
                return null;
            }
            
            if (!is_array($decoded)) {
                $this->pa_debug('[PA][mini-agent] Response is not array');
                return null;
            }
            
            // Check for API errors
            if (isset($decoded['error'])) {
                $this->pa_debug('[PA][mini-agent] API Error: ' . json_encode($decoded['error']));
                return null;
            }
            
            // Gemini response parsing
            if (isset($decoded['candidates'][0]['content']['parts'][0]['text'])) {
                $result = (string) $decoded['candidates'][0]['content']['parts'][0]['text'];
                $this->pa_debug('[PA][mini-agent] Success: ' . substr($result, 0, 200) . '...');
                
                // Since we requested JSON response, try to validate it
                $jsonTest = json_decode($result, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $this->pa_debug('[PA][mini-agent] Valid JSON response received');
                } else {
                    $this->pa_debug('[PA][mini-agent] Response is not valid JSON: ' . json_last_error_msg());
                }
                
                return $result;
            }
            
            if (isset($decoded['candidates'][0]['content']['parts']) && is_array($decoded['candidates'][0]['content']['parts'])) {
                $txt = '';
                foreach ($decoded['candidates'][0]['content']['parts'] as $p) {
                    if (isset($p['text'])) { $txt .= $p['text'] . "\n"; }
                }
                $txt = trim($txt);
                if ($txt !== '') { 
                    $this->pa_debug('[PA][mini-agent] Success (multi-part): ' . substr($txt, 0, 200) . '...');
                    
                    // Since we requested JSON response, try to validate it
                    $jsonTest = json_decode($txt, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $this->pa_debug('[PA][mini-agent] Valid JSON response received (multi-part)');
                    } else {
                        $this->pa_debug('[PA][mini-agent] Multi-part response is not valid JSON: ' . json_last_error_msg());
                    }
                    
                    return $txt; 
                }
            }
            
            $this->pa_debug('[PA][mini-agent] No valid content found in response');
            return null;
            
        } catch (\Throwable $e) {
            $this->pa_debug('[PA][mini-agent] Exception: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Parse AI response and execute suggested actions
     */
    // === Phase 1 helpers ===
    private function buildActionExtractionPrompt($prompt, $context, $compact = false, $limit = 0) {
        $lines = [];
        $lines[] = 'You are an action extraction AI.';
        $lines[] = 'You MUST respond in STRICT JSON format ONLY. No markdown, no commentary.';
        $lines[] = 'CRITICAL: You can ONLY use actions from the "Available Actions" list below.';
        $lines[] = 'DO NOT create or suggest actions that are not in the available list.';
        $lines[] = '';
        $lines[] = 'Response format:';
        $lines[] = '{';
        $lines[] = '  "intent": "user_intent_description",';
        $lines[] = '  "actions": [';
        $lines[] = '    {';
        $lines[] = '      "action_id": "action_name",';
        $lines[] = '      "params": { /* key:value */ },';
        $lines[] = '      "description": "What this action does"';
        $lines[] = '    }';
        $lines[] = '  ],';
        $lines[] = '  "context_needed": ["list of missing context"]';
        $lines[] = '}';
        $lines[] = '';
        // Project context (compact)
        if (!empty($context['project'])) {
            $p = $context['project'];
            $lines[] = 'Current Project:';
            $lines[] = '- id: ' . (isset($p->id)?$p->id:'');
            $lines[] = '- name: ' . (isset($p->name)?$p->name:'');
            $lines[] = '- progress: ' . (isset($p->progress)?$p->progress:'');
            $lines[] = '';
        }
        // Temporal context for relative date interpretation
        $now = isset($context['current_time']) && is_array($context['current_time']) ? $context['current_time'] : [];
        if (!empty($now)) {
            $lines[] = 'Current Time Context:';
            $lines[] = '- now: ' . (isset($now['datetime']) ? $now['datetime'] : date('Y-m-d H:i:s'));
            $lines[] = '- today: ' . (isset($now['date']) ? $now['date'] : date('Y-m-d'));
            $lines[] = '- timezone: ' . (isset($now['timezone']) ? $now['timezone'] : date_default_timezone_get());
            if (isset($now['day_of_week'])) { $lines[] = '- day_of_week: ' . $now['day_of_week']; }
            $lines[] = '';
            // Guidance for relative phrases (EN + VI)
            $lines[] = 'IMPORTANT: Convert any relative time phrases (e.g., "in 3 days", "next week", "tomorrow", "3 ngày tới", "1 tuần nữa") relative to the Current Time Context.';
            $lines[] = '- For parameters with format=date use YYYY-MM-DD (local timezone).';
            $lines[] = '- For parameters with format=datetime use YYYY-MM-DD HH:MM:SS (local timezone).';
            $lines[] = '- Example: "in 3 days" -> today+3 days; "next week" -> next week Monday 09:00 if a start date is implied.';
            $lines[] = '';
        }
        // Available actions with schema (full or compact)
        if (!empty($context['available_actions'])) {
            $lines[] = $compact ? 'Available Actions (compact):' : 'Available Actions (with EXACT parameter requirements):';
            $count = 0;
            foreach ($context['available_actions'] as $aid => $a) {
                if ($limit && $count >= $limit) break;
                $count++;
                $lines[] = '';
                $lines[] = 'Action: ' . $aid;
                if (!$compact) {
                $lines[] = 'Description: ' . (isset($a['description']) ? $a['description'] : '');
                }
                $schema = isset($a['params_schema']) ? $a['params_schema'] : [];
                if (is_string($schema)) {
                    $dec = json_decode($schema, true);
                    if (json_last_error() === JSON_ERROR_NONE) { $schema = $dec; }
                }
                if (is_array($schema)) {
                    $req = isset($schema['required']) && is_array($schema['required']) ? $schema['required'] : [];
                    if (!empty($req)) { $lines[] = 'Required parameters: ' . implode(', ', $req); }
                    if (!$compact && !empty($schema['properties']) && is_array($schema['properties'])) {
                        $lines[] = 'Parameter details:';
                        foreach ($schema['properties'] as $param => $ps) {
                            $type = isset($ps['type']) ? $ps['type'] : 'string';
                            $isReq = in_array($param, $req) ? ' (REQUIRED)' : ' (optional)';
                            $line = '  - ' . $param . ': ' . $type . $isReq;
                            if (isset($ps['format'])) { $line .= ' | format: ' . $ps['format']; }
                            if (isset($ps['minimum'])) { $line .= ' | minimum: ' . $ps['minimum']; }
                            if (isset($ps['enum']) && is_array($ps['enum'])) { $line .= ' | enum: [' . implode(', ', $ps['enum']) . ']'; }
                            $lines[] = $line;
                        }
                    }
                }
                // Include param mapping hints
                $mapping = isset($a['param_mapping']) ? $a['param_mapping'] : [];
                if (is_string($mapping)) { $tmp = json_decode($mapping, true); if (json_last_error()===JSON_ERROR_NONE) { $mapping = $tmp; } }
                if (!$compact && is_array($mapping) && !empty($mapping)) {
                    $lines[] = 'Default mapping hints:';
                    foreach ($mapping as $field => $mp) {
                        $src = isset($mp['source']) ? $mp['source'] : '';
                        $def = array_key_exists('default', $mp) ? $mp['default'] : null;
                        $hint = '  - ' . $field . ' <= ' . ($src ?: '');
                        if ($def !== null && $def !== '') { $hint .= ' | default: ' . (is_scalar($def)?$def:json_encode($def)); }
                        $lines[] = $hint;
                    }
                }
                if (!$compact && !empty($a['prompt_override'])) {
                    $lines[] = 'Hint: ' . preg_replace('/\s+/', ' ', trim($a['prompt_override']));
                }
            }
            $lines[] = '';
        }
        $lines[] = 'User Request: ' . $prompt;
        $lines[] = 'CRITICAL: Provide ALL required parameters with correct types and formats. Return ONLY valid JSON.';
        $lines[] = 'CRITICAL: You MUST only use action_id values that exist in the Available Actions list above.';
        
        $promptText = implode("\n", $lines);
        pa_log_error('Action extraction prompt length: ' . strlen($promptText));
        pa_log_error('Available actions count: ' . (isset($context['available_actions']) ? count($context['available_actions']) : 0));
        
        return $promptText;
    }

    private function parseJsonResponse($response) {
        if (!is_string($response)) { return null; }
        $resp = trim($response);
        $resp = preg_replace('/^```json\s*/i','',$resp);
        $resp = preg_replace('/```\s*$/','',$resp);
        $parsed = json_decode($resp, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($parsed)) {
            return $parsed;
        }
        if (preg_match('/\{[\s\S]*\}/', $resp, $m)) {
            $parsed = json_decode($m[0], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($parsed)) {
                return $parsed;
            }
        }
        $err = json_last_error_msg();
        log_message('error','Project Agent: Failed to parse JSON response - '.$err);
        // Extra debug details (guarded by setting)
        $this->pa_debug('[PA][extract][parse_error] last_error_msg='.$err);
        $this->pa_debug('[PA][extract][raw] first_2000="' . substr($response, 0, 2000) . '"');
        return null;
    }

    // === Phase 2 helper ===
    private function generateConversationalResponse($originalPrompt, $executedActions, $context) {
        $lines = [];
        $lines[] = 'You are a helpful Project Management Assistant.';
        $lines[] = 'Based on the executed actions and results, provide a conversational response to the user.';
        $lines[] = 'IMPORTANT: Return HTML only (no Markdown, no code fences).';
        $lines[] = 'Use <p>, <ul>, <li>, <strong>, <em>, and <br> when needed.';
        $lines[] = '';
        $lines[] = 'Original User Request: ' . $originalPrompt;
        $lines[] = '';
        $lines[] = 'Executed Actions and Results:';
        foreach ($executedActions as $a) {
            $lines[] = '- Action: ' . ($a['action_id'] ?? '');
            if (!empty($a['description'])) { $lines[] = '  Description: ' . $a['description']; }
            $lines[] = '  Parameters: ' . json_encode($a['params']);
            $lines[] = '  Result: ' . json_encode($a['result']);
            $lines[] = '';
        }
        if (!empty($context['project'])) {
            $p = $context['project'];
            $lines[] = 'Project Context:';
            $lines[] = '- Name: ' . (isset($p->name)?$p->name:'');
            $lines[] = '- Progress: ' . (isset($p->progress)?$p->progress:'');
            $lines[] = '- Status: ' . (isset($p->status)?$p->status:'');
            $lines[] = '';
        }
        $lines[] = 'Provide a helpful, conversational HTML response that:';
        $lines[] = '1) Acknowledges what was done';
        $lines[] = '2) Summarizes the results';
        $lines[] = '3) Offers next steps or additional help';
        $lines[] = '4) Uses a friendly, professional tone';
        $lines[] = 'Avoid technical details or JSON.';
        $convPrompt = implode("\n", $lines);
        return $this->aiProvider->chat($convPrompt);
    }
    
    /**
     * Extract action parameters from AI response
     */
    private function extractActionParameters($response, $actionId) {
        $params = [];
        
        // Look for parameters section after action_id
        $paramPatterns = [
            '/action_id:\s*["\']?' . preg_quote($actionId, '/') . '["\']?.*?parameters?:\s*\{([^}]+)\}/is',
            '/action_id:\s*["\']?' . preg_quote($actionId, '/') . '["\']?.*?parameters?:\s*\[([^\]]+)\]/is'
        ];
        
        foreach ($paramPatterns as $pattern) {
            if (preg_match($pattern, $response, $matches)) {
                $paramText = $matches[1];
                
                // Extract key-value pairs
                $kvPatterns = [
                    '/`?(\w+)`?\s*:\s*["\']?([^"\',\s]+)["\']?/',
                    '/`?(\w+)`?\s*=\s*["\']?([^"\',\s]+)["\']?/',
                    '/`?(\w+)`?\s*→\s*["\']?([^"\',\s]+)["\']?/'
                ];
                
                foreach ($kvPatterns as $kvPattern) {
                    if (preg_match_all($kvPattern, $paramText, $kvMatches)) {
                        for ($i = 0; $i < count($kvMatches[1]); $i++) {
                            $key = trim($kvMatches[1][$i]);
                            $value = trim($kvMatches[2][$i]);
                            
                            // Convert common values
                            if ($value === 'true') $value = true;
                            elseif ($value === 'false') $value = false;
                            elseif (is_numeric($value)) $value = (float)$value;
                            
                            $params[$key] = $value;
                        }
                    }
                }
            }
        }
        
        return $params;
    }
    
    /**
     * Execute action through Action Registry
     */
    private function executeAction($actionId, $params, $context) {
        try {
            // Initialize Action Registry
            $registry = new ProjectAgentActionRegistry();
            
            // Preprocess params: auto-fill context values, normalize dates/types
            $params = $this->preprocessActionParams($actionId, $params, $context);
            // Lightweight prefetch context based on action's related tables
            try {
                if (isset($context['project_id']) && $context['project_id']) {
                    $prefCtxHelper = new ProjectAgentContextHelper();
                    $lightCtx = $prefCtxHelper->prefetchActionContext($actionId, (int)$context['project_id'], 10);
                    if (is_array($lightCtx)) {
                        // Merge non-destructively into $context under 'prefetch'
                        $context['prefetch'] = isset($context['prefetch']) && is_array($context['prefetch'])
                            ? array_merge($context['prefetch'], $lightCtx)
                            : $lightCtx;
                    }
                }
            } catch (\Throwable $e) {
                $this->pa_debug('[PA][prefetch] skipped: ' . $e->getMessage());
            }
            
            // Execute action
            $result = $registry->executeAction($actionId, $params, $context);
            
            return $result;
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Normalize/auto-fill parameters based on schema and context
     */
    private function preprocessActionParams($actionId, $params, $context) {
        try {
            $registry = new ProjectAgentActionRegistry();
            $action = $registry->getAction($actionId);
            if (!$action) { return $params; }
            $schema = isset($action['params_schema']) ? $action['params_schema'] : [];
            if (is_string($schema)) { $dec = json_decode($schema, true); if (json_last_error()===JSON_ERROR_NONE) { $schema=$dec; } }
            $mapping = isset($action['param_mapping']) ? $action['param_mapping'] : [];
            if (is_string($mapping)) { $dm = json_decode($mapping, true); if (json_last_error()===JSON_ERROR_NONE) { $mapping=$dm; } }

            // Auto-fill common context
            if (isset($context['project_id']) && !isset($params['project_id'])) { $params['project_id'] = $context['project_id']; }
            if (isset($context['user_id']) && !isset($params['user_id'])) { $params['user_id'] = $context['user_id']; }

            if (isset($schema['properties']) && is_array($schema['properties'])) {
                foreach ($schema['properties'] as $field => $fs) {
                    // If missing, try mapping sources first
                    if (!isset($params[$field]) && isset($mapping[$field])) {
                        $m = $mapping[$field];
                        $src = isset($m['source']) ? (string)$m['source'] : '';
                        if ($src === 'context.project_id' && isset($context['project_id'])) { $params[$field] = $context['project_id']; }
                        elseif ($src === 'context.user_id' && isset($context['user_id'])) { $params[$field] = $context['user_id']; }
                        elseif ($src === 'context.session_id' && isset($context['session_id'])) { $params[$field] = $context['session_id']; }
                        elseif ($src === 'static' && array_key_exists('default', $m)) { $params[$field] = $m['default']; }
                        // related.* can be implemented later
                    }
                    if (!isset($params[$field])) { continue; }
                    // Normalize dates to YYYY-MM-DD
                    if (isset($fs['format']) && $fs['format']==='date') {
                        $date = (string)$params[$field];
                        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $date)) {
                            // DD/MM/YYYY -> YYYY-MM-DD
                            $params[$field] = date('Y-m-d', strtotime(str_replace('/', '-', $date)));
                        } elseif (preg_match('/^\d{4}\/\d{2}\/\d{2}$/', $date)) {
                            $params[$field] = date('Y-m-d', strtotime($date));
                        }
                    }
                    // Coerce integer/number types
                    if (isset($fs['type']) && $fs['type']==='integer') { $params[$field] = (int)$params[$field]; }
                    if (isset($fs['type']) && $fs['type']==='number') { $params[$field] = (float)$params[$field]; }
                    if (isset($fs['type']) && $fs['type']==='boolean') {
                        $v = $params[$field];
                        $params[$field] = ($v===true||$v==='1'||$v===1||$v==='true') ? true : false;
                    }
                }
            }
        } catch (\Throwable $e) { /* ignore */ }
        return $params;
    }
    
    /**
     * Build enhanced prompt with Project Agent context
     */
    private function buildEnhancedPrompt($prompt, $context) {
        $systemPrompt = $this->getSystemPrompt();
        
        $enhancedPrompt = $systemPrompt . "\n\n";
        
        // Add project context if available
        if (!empty($context['project'])) {
            $enhancedPrompt .= "Project Context:\n";
            $enhancedPrompt .= "- Name: " . $context['project']->name . "\n";
            $enhancedPrompt .= "- Status: " . $context['project']->status . "\n";
            $enhancedPrompt .= "- Progress: " . $context['project']->progress . "%\n";
            if (!empty($context['project']->description)) {
                $enhancedPrompt .= "- Description: " . $context['project']->description . "\n";
            }
            $enhancedPrompt .= "\n";
        }
        
        // Add available actions context
        if (!empty($context['available_actions'])) {
            $enhancedPrompt .= "Available Actions:\n";
            foreach ($context['available_actions'] as $action_id => $action) {
                $enhancedPrompt .= "- {$action_id}: {$action['description']}\n";
                if (!empty($action['prompt_override'])) {
                    $enhancedPrompt .= "  Hint: " . preg_replace('/\s+/', ' ', trim($action['prompt_override'])) . "\n";
                }
            }
            $enhancedPrompt .= "\n";
        }
        
        // Add memory context
        if (!empty($context['memory_entries'])) {
            $enhancedPrompt .= "Recent Memory:\n";
            foreach (array_slice($context['memory_entries'], 0, 5) as $entry) {
                $kind = is_array($entry) && isset($entry['kind']) ? $entry['kind'] : (is_object($entry) && isset($entry->kind) ? $entry->kind : 'note');
                $text = '';
                if (is_array($entry)) {
                    if (isset($entry['text'])) { $text = (string)$entry['text']; }
                    elseif (isset($entry['content_json'])) { $text = substr(json_encode($entry['content_json']), 0, 100); }
                } else if (is_object($entry)) {
                    if (isset($entry->text)) { $text = (string)$entry->text; }
                    elseif (isset($entry->content_json)) { $text = substr(json_encode($entry->content_json), 0, 100); }
                }
                $enhancedPrompt .= "- {$kind}: " . substr($text, 0, 100) . "...\n";
            }
            $enhancedPrompt .= "\n";
        }
        
        $enhancedPrompt .= "User Request: " . $prompt . "\n\n";
        $enhancedPrompt .= "Please analyze the request and provide a structured response that includes:\n";
        $enhancedPrompt .= "1. Intent analysis\n";
        $enhancedPrompt .= "2. Recommended actions (with action_id and parameters)\n";
        $enhancedPrompt .= "3. Risk assessment\n";
        $enhancedPrompt .= "4. Additional context needed\n";
        
        return $enhancedPrompt;
    }
    
    /**
     * Get system prompt for Project Agent
     */
    private function getSystemPrompt() {
        $basePrompt = $this->safe_get_option('project_agent_system_prompt');
        
        if (empty($basePrompt)) {
            $basePrompt = "You are a Project Management AI Assistant for Perfex CRM. ";
            $basePrompt .= "Your role is to help users manage projects, tasks, estimates, and invoices through natural language interaction. ";
            $basePrompt .= "You can analyze user requests and suggest appropriate actions from the available action registry. ";
            $basePrompt .= "Always prioritize safety and confirm before executing financial actions. ";
            $basePrompt .= "Provide clear, actionable responses and explain your reasoning.\n\n";
            
            $basePrompt .= "**IMPORTANT: When suggesting actions, use this exact format:**\n";
            $basePrompt .= "```\n";
            $basePrompt .= "action_id: [action_name]\n";
            $basePrompt .= "parameters: {\n";
            $basePrompt .= "  param1: value1,\n";
            $basePrompt .= "  param2: value2\n";
            $basePrompt .= "}\n";
            $basePrompt .= "```\n\n";
            
            $basePrompt .= "**Available Actions:**\n";
            $basePrompt .= "- create_task: Create new task\n";
            $basePrompt .= "- summarize_project_work_remaining: Get project summary\n";
            $basePrompt .= "- check_billing_status: Check billing status\n";
            $basePrompt .= "- create_estimate: Create estimate\n";
            $basePrompt .= "- create_invoice: Create invoice\n";
            $basePrompt .= "- update_project: Update project details\n";
            $basePrompt .= "- add_project_member: Add project member\n";
            $basePrompt .= "- add_milestone: Add project milestone\n";
            $basePrompt .= "- record_expense: Record expense\n";
            $basePrompt .= "- invoice_timesheets: Invoice timesheets\n";
            $basePrompt .= "- list_overdue_invoices: List overdue invoices\n";
            $basePrompt .= "- find_unlinked_entities: Find unlinked entities\n";
            $basePrompt .= "- create_reminder: Create reminder\n";
            $basePrompt .= "- project_health_check: Check project health\n\n";
            
            $basePrompt .= "**Instructions:**\n";
            $basePrompt .= "1. Analyze the user request\n";
            $basePrompt .= "2. Identify the appropriate action(s) to execute\n";
            $basePrompt .= "3. Use the exact format above to specify actions\n";
            $basePrompt .= "4. The system will automatically execute these actions\n";
            $basePrompt .= "5. Provide a summary of what was done\n";
        }
        
        return $basePrompt;
    }
    
    /**
     * Parse AI response into structured format
     */
    public function parseAiResponse($response) {
        // Try to parse JSON response
        if (preg_match('/```json\s*(.*?)\s*```/s', $response, $matches)) {
            $jsonStr = $matches[1];
            $parsed = json_decode($jsonStr, true);
            if ($parsed) {
                return $parsed;
            }
        }
        
        // Try to extract JSON from response
        if (preg_match('/\{.*\}/s', $response, $matches)) {
            $parsed = json_decode($matches[0], true);
            if ($parsed) {
                return $parsed;
            }
        }
        
        // Fallback: return structured response
        return [
            'intent' => 'general_query',
            'actions' => [],
            'message' => $response,
            'confidence' => 0.5
        ];
    }
    
    /**
     * Generate action plan from AI response
     */
    public function generateActionPlan($aiResponse, $context) {
        $plan = [
            'plan_id' => 'plan_' . time(),
            'steps' => [],
            'requires_confirm' => false,
            'risk_level' => 'low'
        ];
        
        if (isset($aiResponse['actions']) && is_array($aiResponse['actions'])) {
            foreach ($aiResponse['actions'] as $index => $action) {
                $step = [
                    'id' => 's' . ($index + 1),
                    'action' => $action['action_id'] ?? '',
                    'params' => $action['params'] ?? [],
                    'description' => $action['description'] ?? '',
                    'risk' => $action['risk'] ?? 'low'
                ];
                
                $plan['steps'][] = $step;
                
                // Check if any step requires confirmation
                if (($action['risk'] ?? 'low') === 'high' || ($action['requires_confirm'] ?? false)) {
                    $plan['requires_confirm'] = true;
                }
                
                // Update overall risk level
                if (($action['risk'] ?? 'low') === 'high') {
                    $plan['risk_level'] = 'high';
                } elseif (($action['risk'] ?? 'low') === 'medium' && $plan['risk_level'] !== 'high') {
                    $plan['risk_level'] = 'medium';
                }
            }
        }
        
        return $plan;
    }
    
    /**
     * Validate action plan against available actions
     */
    public function validateActionPlan($plan, $availableActions) {
        $validation = [
            'valid' => true,
            'errors' => [],
            'warnings' => []
        ];
        
        foreach ($plan['steps'] as $step) {
            $action_id = $step['action'];
            
            // Check if action exists
            if (!isset($availableActions[$action_id])) {
                $validation['valid'] = false;
                $validation['errors'][] = "Action not found: {$action_id}";
                continue;
            }
            
            // Check if action is active
            if (!($availableActions[$action_id]['is_active'] ?? true)) {
                $validation['warnings'][] = "Action is inactive: {$action_id}";
            }
            
            // Validate parameters against schema
            $schema = $availableActions[$action_id]['params_schema'] ?? [];
            $paramValidation = $this->validateParameters($step['params'], $schema);
            
            if (!$paramValidation['valid']) {
                $validation['valid'] = false;
                $validation['errors'] = array_merge($validation['errors'], $paramValidation['errors']);
            }
        }
        
        return $validation;
    }
    
    /**
     * Validate parameters against JSON schema
     */
    private function validateParameters($params, $schema) {
        $validation = [
            'valid' => true,
            'errors' => []
        ];
        
        // Check required fields
        if (isset($schema['required'])) {
            foreach ($schema['required'] as $field) {
                if (!isset($params[$field])) {
                    $validation['valid'] = false;
                    $validation['errors'][] = "Missing required field: {$field}";
                }
            }
        }
        
        // Validate field types
        if (isset($schema['properties'])) {
            foreach ($schema['properties'] as $field => $fieldSchema) {
                if (isset($params[$field])) {
                    $fieldValidation = $this->validateField($field, $params[$field], $fieldSchema);
                    if (!$fieldValidation['valid']) {
                        $validation['valid'] = false;
                        $validation['errors'][] = $fieldValidation['error'];
                    }
                }
            }
        }
        
        return $validation;
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
                break;
                
            case 'integer':
                if (!is_numeric($value) || (int)$value != $value) {
                    return ['valid' => false, 'error' => "Field {$field} must be an integer"];
                }
                break;
                
            case 'number':
                if (!is_numeric($value)) {
                    return ['valid' => false, 'error' => "Field {$field} must be a number"];
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
                break;
        }
        
        return ['valid' => true];
    }
}
