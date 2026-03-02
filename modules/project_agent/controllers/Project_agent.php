<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Project Agent Controller
 * Main controller for AI Room interface
 */

class Project_agent extends AdminController {
    
    public function __construct() {
        parent::__construct();
        $this->load->model('project_agent_model');
        $this->load->helper('project_agent_debug');
    }
    
    /**
     * Module Overview + General Settings
     */
    public function index() {
        if (!has_permission('project_agent', '', 'view')) {
            access_denied('project_agent');
        }
        // Handle settings POST (admin only)
        if ($this->input->post() && has_permission('project_agent', '', 'admin')) {
            $opt = function($key,$default=null){ return ($this->input->post($key)!==null) ? $this->input->post($key) : $default; };
            update_option('project_agent_ai_room_enabled', (int)$opt('project_agent_ai_room_enabled', 1));
            update_option('project_agent_ai_provider', (string)$opt('project_agent_ai_provider', 'geminiai'));
            update_option('project_agent_system_prompt', (string)$opt('project_agent_system_prompt'));
            update_option('project_agent_auto_confirm_threshold', (int)$opt('project_agent_auto_confirm_threshold', 1000));
            update_option('project_agent_memory_retention_days', (int)$opt('project_agent_memory_retention_days', 30));
            update_option('project_agent_max_concurrent_sessions', (int)$opt('project_agent_max_concurrent_sessions', 10));
            update_option('project_agent_default_risk_level', (string)$opt('project_agent_default_risk_level', 'low'));
            update_option('project_agent_debug_enabled', (int)$opt('project_agent_debug_enabled', 0));
            update_option('project_agent_error_explainer_enabled', (int)$opt('project_agent_error_explainer_enabled', 0));
            $apiKey = (string)$opt('project_agent_error_explainer_api_key', '');
            if ($apiKey !== null) { update_option('project_agent_error_explainer_api_key', $apiKey); }
            // Context data size guards
            update_option('project_agent_context_task_limit', (int)$opt('project_agent_context_task_limit', 200));
            update_option('project_agent_context_milestone_limit', (int)$opt('project_agent_context_milestone_limit', 100));
            update_option('project_agent_context_activity_limit', (int)$opt('project_agent_context_activity_limit', 50));
            set_alert('success', _l('settings_updated'));
            redirect(admin_url('project_agent'));
            return;
        }

        $data = [];
        $data['title'] = 'Project Agent';
        $ai = new ProjectAgentAiIntegration();
        $data['ai_status'] = $ai->getGeminiaiRecommendation();
        $data['ai_available'] = $ai->isAiAvailable();
        $data['version'] = (defined('PROJECT_AGENT_DB_VERSION') ? PROJECT_AGENT_DB_VERSION : null);
        
        // Debug info for admin
        if (has_permission('project_agent', '', 'admin')) {
            $data['debug_info'] = [
                'ai_available' => $data['ai_available'],
                'ai_status' => $data['ai_status'],
                'providers' => $ai->getAvailableProviders()
            ];
        }
        // Options
        $data['opts'] = [
            'project_agent_ai_room_enabled' => (int) $this->safe_get_option('project_agent_ai_room_enabled'),
            'project_agent_ai_provider' => $this->safe_get_option('project_agent_ai_provider'),
            'project_agent_system_prompt' => $this->safe_get_option('project_agent_system_prompt'),
            'project_agent_auto_confirm_threshold' => (int) $this->safe_get_option('project_agent_auto_confirm_threshold'),
            'project_agent_memory_retention_days' => (int) $this->safe_get_option('project_agent_memory_retention_days'),
            'project_agent_max_concurrent_sessions' => (int) $this->safe_get_option('project_agent_max_concurrent_sessions'),
            'project_agent_default_risk_level' => $this->safe_get_option('project_agent_default_risk_level'),
            'project_agent_debug_enabled' => (int) $this->safe_get_option('project_agent_debug_enabled'),
            'project_agent_error_explainer_enabled' => (int) $this->safe_get_option('project_agent_error_explainer_enabled'),
            'project_agent_error_explainer_api_key' => $this->safe_get_option('project_agent_error_explainer_api_key'),
            'project_agent_context_task_limit' => (int) $this->safe_get_option('project_agent_context_task_limit'),
            'project_agent_context_milestone_limit' => (int) $this->safe_get_option('project_agent_context_milestone_limit'),
            'project_agent_context_activity_limit' => (int) $this->safe_get_option('project_agent_context_activity_limit'),
            // DB trace current state (file flag)
            'project_agent_db_trace_enabled' => (int) file_exists(FCPATH . 'temp' . DIRECTORY_SEPARATOR . 'pa_db_trace.on'),
        ];
        // List DB tables (unprefixed) for schema learning UI
        try {
            $tables = $this->db->list_tables();
            $unp = [];
            $pref = (string) db_prefix();
            foreach ($tables as $t) {
                $name = $t;
                if ($pref !== '') {
                    // Strip prefix or prefix_ (common in some installs)
                    if (strpos($t, $pref . '_') === 0) {
                        $name = substr($t, strlen($pref) + 1);
                    } elseif (strpos($t, $pref) === 0) {
                        $name = substr($t, strlen($pref));
                    }
                }
                $unp[] = $name;
            }
            sort($unp, SORT_NATURAL | SORT_FLAG_CASE);
            $data['db_tables'] = array_values(array_unique($unp));
            $data['db_table_models'] = $this->pa_build_table_model_index();
            // Build heuristic table->actions index for Overview (only DB-defined actions)
            $data['db_table_actions'] = $this->pa_build_table_action_index($data['db_tables']);
            $data['db_action_tables'] = $this->pa_build_action_table_index($data['db_table_actions']);
            // Build action meta map id=>name for view badge labels
            $data['db_action_meta'] = [];
            try {
                $rows = (array) $this->project_agent_model->get_actions(false);
                foreach ($rows as $r) {
                    $aid = is_array($r) ? ($r['action_id'] ?? null) : ($r->action_id ?? null);
                    if ($aid) { $data['db_action_meta'][$aid] = is_array($r) ? ($r['name'] ?? $aid) : ($r->name ?? $aid); }
                }
            } catch (\Throwable $e) {}
        } catch (\Throwable $e) { $data['db_tables'] = []; }
        // Available providers (best-effort)
        try { $data['providers'] = array_keys(\app\services\ai\AiProviderRegistry::getAllProviders()); } catch (\Throwable $e) { $data['providers'] = ['geminiai','openai']; }
        
        // Load actions for settings view
        $data['actions'] = $this->project_agent_model->get_actions(false);
        
        $this->load->view('project_agent/admin/settings', $data);
    }

    /**
     * Run Mini Agent schema learning for comma-separated tables
     */
    public function learn_schema() {
        if (!has_permission('project_agent', '', 'admin')) { ajax_access_denied(); }
        $tablesStr = (string)$this->input->post('tables');
        $list = array_filter(array_map('trim', explode(',', $tablesStr)));
        $tables = project_agent_schema_list_tables($list);
        if (empty($tables)) { return $this->json(['success'=>false,'error'=>'No valid tables'], 400); }
        $res = project_agent_schema_ai_learn($tables);
        
        // Additionally, build & save Parameter Mapping for each action id
        $this->load->helper('project_agent_param_mapping_helper');
        $mappings = [];
        $schemas = [];
        $forceOverwrite = (int)$this->input->post('force_schema') === 1;
        foreach ($ids as $aid) {
            if (!isset($byId[$aid])) continue;
            $row = $byId[$aid];
            try {
                $suggested = project_agent_build_param_mapping($row);
                // Merge with existing mapping if present
                $existing = [];
                if (isset($row['param_mapping']) && is_string($row['param_mapping']) && $row['param_mapping']!=='') {
                    $tmp = json_decode($row['param_mapping'], true);
                    if (json_last_error()===JSON_ERROR_NONE) { $existing = $tmp; }
                }
                $final = project_agent_merge_param_mapping($existing, $suggested);
                // If required fields remain unmapped, try AI refinement
                try {
                    $schema = isset($row['params_schema']) ? (is_string($row['params_schema']) ? json_decode($row['params_schema'], true) : $row['params_schema']) : [];
                    $req = (isset($schema['required']) && is_array($schema['required'])) ? $schema['required'] : [];
                    $missing = [];
                    foreach ($req as $f) {
                        $mp = isset($final[$f]) ? $final[$f] : null;
                        $src = is_array($mp) && isset($mp['source']) ? trim((string)$mp['source']) : '';
                        $hasDef = is_array($mp) && array_key_exists('default',$mp);
                        if ($src==='' && !$hasDef) { $missing[] = $f; }
                    }
                    if (!empty($missing)) {
                        $aiMap = project_agent_build_param_mapping_ai($row);
                        if (is_array($aiMap) && !empty($aiMap)) {
                            $final = project_agent_merge_param_mapping($final, $aiMap);
                        }
                    }
                } catch (\Throwable $e) { /* ignore */ }
                $this->project_agent_model->update_action($aid, ['param_mapping' => json_encode($final)]);
                $mappings[$aid] = $final;
                // Refresh params_schema via AI if empty/minimal
                try {
                    // Decide whether to overwrite existing schema
                    $currentSchema = isset($row['params_schema']) ? (is_string($row['params_schema']) ? json_decode($row['params_schema'], true) : $row['params_schema']) : [];
                    $hasSchema = is_array($currentSchema) && !empty($currentSchema) && !empty($currentSchema['properties']);
                    if ($forceOverwrite || !$hasSchema) {
                        $rel = isset($row['related_tables']) ? (is_string($row['related_tables']) ? json_decode($row['related_tables'], true) : $row['related_tables']) : [];
                        if (json_last_error()!==JSON_ERROR_NONE || !is_array($rel)) { $rel = []; }
                        $schemaAi = project_agent_build_params_schema_ai($row, $rel);
                        if (is_array($schemaAi) && !empty($schemaAi) && !empty($schemaAi['properties'])) {
                            $this->project_agent_model->update_action($aid, ['params_schema' => json_encode($schemaAi)]);
                            $schemas[$aid] = $schemaAi;
                        }
                    }
                } catch (\Throwable $e) { /* ignore */ }
            } catch (\Throwable $e) {
                $mappings[$aid] = ['error' => $e->getMessage()];
            }
        }

        $res['mappings'] = $mappings;
        if (!empty($schemas)) { $res['schemas'] = $schemas; }
        $res['actions'] = isset($res['actions']) ? $res['actions'] : [];
        return $this->json($res);
    }

    /** Retrieve saved mapping for a table */
    public function get_schema_mapping() {
        if (!has_permission('project_agent', '', 'admin')) { ajax_access_denied(); }
        $table = (string)$this->input->get_post('table');
        if (!$table) { return $this->json(['success'=>false,'error'=>'table required'], 400); }
        $map = project_agent_schema_get_mapping($table);
        return $this->json(['success'=> (bool)$map, 'mapping'=> $map]);
    }

    // Build index: table (unprefixed) => list of model files referencing it
    private function pa_build_table_model_index() {
        $map = [];
        $pref = (string) db_prefix();
        $dir = APPPATH . 'models';
        try {
            $rii = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS));
            foreach ($rii as $file) {
                if (!$file->isFile()) continue;
                $path = $file->getPathname();
                if (substr($path, -4) !== '.php') continue;
                $rel = str_replace(APPPATH, '', $path);
                $content = @file_get_contents($path, false, null, 0, 200000);
                if ($content === false) continue;
                if (preg_match_all("/db_prefix\(\)\s*\.\s*'([a-zA-Z0-9_]+)'/", $content, $m)) {
                    foreach ($m[1] as $tbl) { $map[$tbl][] = $rel; }
                }
                if ($pref !== '' && preg_match_all('/`' . preg_quote($pref, '/') . '([a-zA-Z0-9_]+)`/', $content, $m2)) {
                    foreach ($m2[1] as $tbl) { $map[$tbl][] = $rel; }
                }
            }
        } catch (\Throwable $e) {}
        foreach ($map as $k => $arr) { $map[$k] = array_values(array_unique($arr)); }
        return $map;
    }

    // Build heuristic mapping: table (unprefixed) => list of related actions
    private function pa_build_table_action_index($tables) {
        $map = [];
        // Use the same source as admin/actions index (DB-defined actions)
        $rows = [];
        try {
            $rows = (array) $this->project_agent_model->get_actions(false); // include inactive, consistent with admin/actions
        } catch (\Throwable $e) { $rows = []; }
        // Normalize into [id => meta]
        $actions = [];
        foreach ($rows as $r) {
            $aid = is_array($r) ? ($r['action_id'] ?? null) : (isset($r->action_id) ? $r->action_id : null);
            if (!$aid) { continue; }
            $name = is_array($r) ? ($r['name'] ?? $aid) : ($r->name ?? $aid);
            $perms = is_array($r) ? ($r['permissions'] ?? []) : ($r->permissions ?? []);
            $schema = is_array($r) ? ($r['params_schema'] ?? []) : ($r->params_schema ?? []);
            // Decode JSON fields if needed
            if (is_string($perms)) { $tmp = json_decode($perms, true); if (json_last_error()===JSON_ERROR_NONE) { $perms = $tmp; } }
            if (is_string($schema)) { $tmp = json_decode($schema, true); if (json_last_error()===JSON_ERROR_NONE) { $schema = $tmp; } }
            $actions[$aid] = [
                'action_id' => $aid,
                'name' => $name,
                'permissions' => $perms,
                'params_schema' => $schema,
            ];
        }

        // Keyword map per canonical feature
        $featureKeywords = [
            'projects'   => ['projects','project','milestone','member'],
            'tasks'      => ['tasks','task','subtask'],
            'invoices'   => ['invoices','invoice','payments','payment'],
            'estimates'  => ['estimates','estimate','proposal'],
            'expenses'   => ['expenses','expense'],
            'timesheets' => ['timesheets','timesheet','timer'],
            'reminders'  => ['reminders','reminder'],
            'customers'  => ['customers','customer','clients','client','contact','lead'],
        ];

        // Map table name -> feature guess
        $tableToFeature = function($tbl) use ($featureKeywords) {
            $t = strtolower($tbl);
            foreach ($featureKeywords as $feat => $words) {
                foreach ($words as $w) {
                    if (strpos($t, $w) !== false) return $feat;
                }
            }
            return null;
        };

        foreach ((array)$tables as $tbl) {
            $feat = $tableToFeature($tbl);
            $related = [];
            foreach ($actions as $aid => $a) {
                $score = 0;
                $id = strtolower($a['action_id'] ?? $aid);
                $name = strtolower($a['name'] ?? '');
                $perms = $a['permissions'] ?? [];
                if (!is_array($perms)) { $perms = [$perms]; }
                // Permission-based score
                foreach ($perms as $p) {
                    if (!is_string($p)) continue;
                    $feature = $p;
                    if (strpos($p, ':') !== false) { $feature = substr($p, 0, strpos($p, ':')); }
                    if (strpos($p, '.') !== false) { $feature = substr($p, 0, strpos($p, '.')); }
                    if ($feat && strtolower($feature) === $feat) { $score += 2; }
                    if ($feat && strpos(strtolower($feature), rtrim($feat,'s')) !== false) { $score += 1; }
                }
                // Name/id keyword score
                $kw = $feat ?: strtolower($tbl);
                if ($kw) {
                    if (strpos($id, rtrim($kw,'s')) !== false) $score++;
                    if (strpos($name, rtrim($kw,'s')) !== false) $score++;
                }
                // Params schema hints
                $schema = $a['params_schema'] ?? [];
                if (is_array($schema) && isset($schema['properties']) && is_array($schema['properties'])) {
                    $props = array_map('strtolower', array_keys($schema['properties']));
                    if ($feat === 'projects' && in_array('project_id', $props)) $score += 2;
                    if ($feat === 'tasks' && (in_array('task_id',$props) || in_array('project_id',$props))) $score++;
                    if ($feat === 'invoices' && in_array('invoice_id',$props)) $score += 2;
                    if ($feat === 'estimates' && in_array('estimate_id',$props)) $score += 2;
                    if ($feat === 'expenses' && in_array('expense_id',$props)) $score += 2;
                }
                if ($score > 0) { $related[$aid] = $a; }
            }
            // Keep up to 6 most relevant (by rough score via order above)
            $map[$tbl] = array_slice(array_values(array_map(function($x){return $x['action_id'];}, $related)), 0, 6);
        }
        return $map;
    }

    // Invert table->actions to action->tables and attach action name
    private function pa_build_action_table_index($tableToActions) {
        $out = [];
        if (!is_array($tableToActions)) return $out;
        // Load actions meta from the same DB source as admin/actions
        $names = [];
        try {
            $rows = (array) $this->project_agent_model->get_actions(false);
            foreach ($rows as $r) {
                $aid = is_array($r) ? ($r['action_id'] ?? null) : ($r->action_id ?? null);
                if ($aid) { $names[$aid] = is_array($r) ? ($r['name'] ?? $aid) : ($r->name ?? $aid); }
            }
        } catch (\Throwable $e) {}
        foreach ($tableToActions as $tbl => $acts) {
            foreach ((array)$acts as $aid) {
                if (!isset($out[$aid])) { $out[$aid] = ['action_id'=>$aid, 'name'=>$names[$aid] ?? $aid, 'tables'=>[]]; }
                $out[$aid]['tables'][] = $tbl;
            }
        }
        // sort tables & normalize
        foreach ($out as &$row) { sort($row['tables'], SORT_NATURAL | SORT_FLAG_CASE); }
        // sort actions by name
        usort($out, function($a,$b){ return strcasecmp($a['name'],$b['name']); });
        return $out;
    }

    /**
     * Dedicated Settings page (same view as index, admin-only)
     */
    public function settings() {
        if (!has_permission('project_agent', '', 'admin')) {
            access_denied('project_agent');
        }
        if ($this->input->post()) {
            $opt = function($key,$default=null){ return ($this->input->post($key)!==null) ? $this->input->post($key) : $default; };
            update_option('project_agent_ai_room_enabled', (int)$opt('project_agent_ai_room_enabled', 1));
            update_option('project_agent_ai_provider', (string)$opt('project_agent_ai_provider', 'geminiai'));
            update_option('project_agent_system_prompt', (string)$opt('project_agent_system_prompt'));
            update_option('project_agent_auto_confirm_threshold', (int)$opt('project_agent_auto_confirm_threshold', 1000));
            update_option('project_agent_memory_retention_days', (int)$opt('project_agent_memory_retention_days', 30));
            update_option('project_agent_max_concurrent_sessions', (int)$opt('project_agent_max_concurrent_sessions', 10));
            update_option('project_agent_default_risk_level', (string)$opt('project_agent_default_risk_level', 'low'));
            update_option('project_agent_debug_enabled', (int)$opt('project_agent_debug_enabled', 0));
            update_option('project_agent_error_explainer_enabled', (int)$opt('project_agent_error_explainer_enabled', 0));
            $apiKey = (string)$opt('project_agent_error_explainer_api_key', '');
            if ($apiKey !== null) { update_option('project_agent_error_explainer_api_key', $apiKey); }
            // Context size guards
            update_option('project_agent_context_task_limit', (int)$opt('project_agent_context_task_limit', 200));
            update_option('project_agent_context_milestone_limit', (int)$opt('project_agent_context_milestone_limit', 100));
            update_option('project_agent_context_activity_limit', (int)$opt('project_agent_context_activity_limit', 50));
            // DB trace toggle via settings page
            $traceEnabled = (int)$opt('project_agent_db_trace_enabled', 0) === 1;
            $flag = FCPATH . 'temp' . DIRECTORY_SEPARATOR . 'pa_db_trace.on';
            if ($traceEnabled) {
                if (!is_dir(FCPATH . 'temp')) { @mkdir(FCPATH . 'temp', 0777, true); }
                @file_put_contents($flag, '1');
            } else {
                @unlink($flag);
            }
            set_alert('success', _l('settings_updated'));
            redirect(admin_url('project_agent/settings'));
            return;
        }

        $data = [];
        $data['title'] = 'Project Agent Settings';
        $ai = new ProjectAgentAiIntegration();
        $data['ai_status'] = $ai->getGeminiaiRecommendation();
        $data['ai_available'] = $ai->isAiAvailable();
        $data['version'] = (defined('PROJECT_AGENT_DB_VERSION') ? PROJECT_AGENT_DB_VERSION : null);
        if (has_permission('project_agent', '', 'admin')) {
            $data['debug_info'] = [
                'ai_available' => $data['ai_available'],
                'ai_status' => $data['ai_status'],
                'providers' => $ai->getAvailableProviders()
            ];
        }
        $data['opts'] = [
            'project_agent_ai_room_enabled' => (int) $this->safe_get_option('project_agent_ai_room_enabled'),
            'project_agent_ai_provider' => $this->safe_get_option('project_agent_ai_provider'),
            'project_agent_system_prompt' => $this->safe_get_option('project_agent_system_prompt'),
            'project_agent_auto_confirm_threshold' => (int) $this->safe_get_option('project_agent_auto_confirm_threshold'),
            'project_agent_memory_retention_days' => (int) $this->safe_get_option('project_agent_memory_retention_days'),
            'project_agent_max_concurrent_sessions' => (int) $this->safe_get_option('project_agent_max_concurrent_sessions'),
            'project_agent_default_risk_level' => $this->safe_get_option('project_agent_default_risk_level'),
            'project_agent_debug_enabled' => (int) $this->safe_get_option('project_agent_debug_enabled'),
            'project_agent_error_explainer_enabled' => (int) $this->safe_get_option('project_agent_error_explainer_enabled'),
            'project_agent_error_explainer_api_key' => $this->safe_get_option('project_agent_error_explainer_api_key'),
            'project_agent_context_task_limit' => (int) $this->safe_get_option('project_agent_context_task_limit'),
            'project_agent_context_milestone_limit' => (int) $this->safe_get_option('project_agent_context_milestone_limit'),
            'project_agent_context_activity_limit' => (int) $this->safe_get_option('project_agent_context_activity_limit'),
            'project_agent_db_trace_enabled' => (int) file_exists(FCPATH . 'temp' . DIRECTORY_SEPARATOR . 'pa_db_trace.on'),
        ];
        // List DB tables (unprefixed) for schema learning UI
        try {
            $tables = $this->db->list_tables();
            $unp = [];
            $pref = (string) db_prefix();
            foreach ($tables as $t) {
                $name = $t;
                if ($pref !== '') {
                    if (strpos($t, $pref . '_') === 0) {
                        $name = substr($t, strlen($pref) + 1);
                    } elseif (strpos($t, $pref) === 0) {
                        $name = substr($t, strlen($pref));
                    }
                }
                $unp[] = $name;
            }
            sort($unp, SORT_NATURAL | SORT_FLAG_CASE);
            $data['db_tables'] = array_values(array_unique($unp));
            $data['db_table_models'] = $this->pa_build_table_model_index();
        } catch (\Throwable $e) { $data['db_tables'] = []; }
        try { $data['providers'] = array_keys(\app\services\ai\AiProviderRegistry::getAllProviders()); } catch (\Throwable $e) { $data['providers'] = ['geminiai','openai']; }
        
        // Load actions for settings view
        $data['actions'] = $this->project_agent_model->get_actions(false);
        
        $this->load->view('project_agent/admin/settings', $data);
    }

    /**
     * AI Room interface (moved from index)
     */
    public function ai() {
        if (!has_permission('project_agent', '', 'view')) {
            access_denied('project_agent');
        }
        $data['title'] = _l('ai_room_title');
        $data['projects'] = $this->get_projects_for_user();
        $data['actions'] = $this->get_available_actions();
        $aiIntegration = new ProjectAgentAiIntegration();
        $data['ai_status'] = $aiIntegration->getGeminiaiRecommendation();
        $data['ai_available'] = $aiIntegration->isAiAvailable();
        
        // Get or create session for general AI room (no specific project)
        $data['session'] = $this->get_or_create_session(null);
        pa_log_error('AI Room - Session created/retrieved: ' . ($data['session'] ? $data['session']->session_id : 'null'));
        
        // Render Perfex-standard admin wrapper view
        $this->load->view('project_agent/admin/ai_room', $data);
    }

    /**
     * Chat endpoint (AI request)
     */
    public function chat() {
        pa_log_error('===== CHAT ENDPOINT CALLED =====');
        pa_log_error('Request method: ' . $this->input->method());
        // pa_log_error('Request headers: ' . json_encode($this->input->request_headers()));
        
        if (!has_permission('project_agent', '', 'view')) {
            pa_log_error('Access denied - insufficient permissions');
            return $this->json(['success' => false, 'error' => 'denied'], 403);
        }
        
        $message    = (string) $this->input->post('message');
        $session_id = (int) $this->input->post('session_id');
        $project_id_raw = $this->input->post('project_id');
        $project_id = ($project_id_raw === '' || $project_id_raw === null) ? null : (int) $project_id_raw;
        $client_token = (string) $this->input->post('client_token');
        $async = (int) $this->input->post('async');
        
        pa_log_error('Chat parameters:');
        pa_log_error('- message: ' . substr($message, 0, 100) . (strlen($message) > 100 ? '...' : ''));
        pa_log_error('- session_id: ' . $session_id);
        pa_log_error('- project_id: ' . ($project_id ?: 'null'));
        pa_log_error('- client_token: ' . $client_token);
        pa_log_error('- async: ' . $async);
        
        // Server-side policy (ignore client flags)
        $final_only = true; // always enforce final-only in production
        $return_steps = has_permission('project_agent', '', 'debug_tools');
        
        if ($message === '') {
            pa_log_error('Empty message received');
            return $this->json(['success' => false, 'error' => 'Empty message']);
        }
        try {
            pa_log_error('Starting chat processing...');
            
            // Idempotency: short-term cache by client_token (10s)
            if ($client_token) {
                pa_log_error('Checking cache for client_token: ' . $client_token);
                $cache = $this->session->userdata('pa_chat_cache') ?: [];
                if (isset($cache[$client_token])) {
                    $entry = $cache[$client_token];
                    $age = time() - (int)$entry['ts'];
                    pa_log_error('Cache entry found, age: ' . $age . ' seconds');
                    if ($age <= 10) {
                        pa_log_error('Returning cached response');
                        return $this->json($entry['payload']);
                    } else {
                        pa_log_error('Cache entry expired, continuing with new request');
                    }
                } else {
                    pa_log_error('No cache entry found for token');
                }
            }
            
            // Treat presence of client_token as async queue request (respond immediately)
            $is_async = ((int)$this->input->post('async') === 1) || !empty($client_token);
            pa_log_error('Async mode: ' . ($is_async ? 'true' : 'false'));
            
            if ($is_async) {
                pa_log_error('Processing async request');
                if (!$client_token) { 
                    $client_token = 'ct-' . dechex(mt_rand()) . '-' . time();
                    pa_log_error('Generated new client_token: ' . $client_token);
                }
                // Ensure session
                if (!$session_id) {
                    pa_log_error('No session_id provided, creating/getting session for project_id: ' . ($project_id ?: 'null'));
                    $session = $this->get_or_create_session($project_id);
                    if ($session && isset($session->session_id)) { 
                        $session_id = (int)$session->session_id;
                        pa_log_error('Session created/retrieved, session_id: ' . $session_id);
                    } else {
                        pa_log_error('Failed to create/retrieve session for provided project_id. Retrying with NULL project...');
                        // Hard fallback: create a general session (no project) for the user
                        try {
                            $this->load->model('project_agent_model');
                            $forced_id = (int)$this->project_agent_model->create_session(null, get_staff_user_id());
                            if ($forced_id > 0) {
                                $session_id = $forced_id;
                                pa_log_error('Forced new general session created, session_id: ' . $session_id);
                            } else {
                                pa_log_error('Forced session creation returned invalid ID');
                            }
                        } catch (\Throwable $e) {
                            pa_log_error('Forced session creation failed: ' . $e->getMessage());
                        }
                    }
                } else {
                    pa_log_error('Using provided session_id: ' . $session_id);
                }
                
                // Persist user input to memory
                if ($session_id) {
                    pa_log_error('Adding user input to memory for session_id: ' . $session_id);
                    $mem = new ProjectAgentMemoryHelper();
                    $entryId = $mem->addEntry($session_id, 'input', ['text' => $message], 'session', $project_id);
                    pa_log_error('User input addEntry insert_id=' . (int)$entryId);
                } else {
                    pa_log_error('Cannot add to memory - no session_id');
                }
                
                // Save a lightweight job descriptor for runner
                $job = [
                    'message' => $message,
                    'session_id' => $session_id ?: null,
                    'project_id' => $project_id,
                    'user_id' => get_staff_user_id(),
                    'queued_at' => time(),
                ];
                pa_log_error('Saving job for token: ' . $client_token);
                pa_log_error('Job data: ' . json_encode($job));
                
                project_agent_job_save($client_token, $job);
                pa_log_error('Job saved successfully');
                
                project_agent_progress_init($client_token);
                pa_log_error('Progress initialized');
                
                project_agent_progress_add($client_token, 'queued', $job);
                pa_log_error('Queued event added to progress');
                
                // Trigger AI processing immediately after returning response
                pa_log_error('Triggering AI processing for token: ' . $client_token);
                
                // Send immediate ACK first
                $payload = ['success' => true, 'queued' => true, 'client_token' => $client_token, 'session_id' => $session_id];
                pa_log_error('Returning async response: ' . json_encode($payload));
                
                // Start AI processing in background (after response is sent)
                $this->start_background_ai_processing($client_token);
                
                return $this->json($payload);
            }
            
            pa_log_error('Processing synchronous request');
            
            // Ensure session (supports general sessions when no project selected)
            if (!$session_id) {
                pa_log_error('No session_id for sync request, creating/getting session');
                $session = $this->get_or_create_session($project_id);
                if ($session && isset($session->session_id)) {
                    $session_id = (int) $session->session_id;
                    pa_log_error('Session created/retrieved for sync, session_id: ' . $session_id);
                }
            }
            
            // Build minimal context; AI integration will enrich via ContextBuilder
            pa_log_error('Building context for AI request');
            $mem = new ProjectAgentMemoryHelper();
            $context = [];
            
            // Note: available_actions will be injected by AI integration helper
            
            $context['session_id'] = $session_id ?: null;
            if ($project_id !== null) { $context['project_id'] = (int)$project_id; }
            $context['user_id'] = get_staff_user_id();
            if ($client_token) { $context['client_token'] = $client_token; }
            
            // Add current time context for agent
            $context['current_time'] = [
                'datetime' => date('Y-m-d H:i:s'),
                'date' => date('Y-m-d'),
                'time' => date('H:i:s'),
                'timezone' => date_default_timezone_get(),
                'day_of_week' => date('l'),
                'timestamp' => time()
            ];
            
            pa_log_error('Context built: ' . json_encode(array_keys($context)));
            
                if ($session_id) {
                    pa_log_error('Adding input to memory for sync request');
                    $inId = $mem->addEntry($session_id, 'input', ['text' => $message], 'session', $project_id);
                    pa_log_error('Sync input addEntry insert_id=' . (int)$inId);
                }
            
            // Ask AI
            pa_log_error('Calling AI integration...');
            $ai = new ProjectAgentAiIntegration();
            $result = $ai->generateResponse($message, $context);
            pa_log_error('AI response received, success: ' . ($result['success'] ? 'true' : 'false'));
            if ($result['success']) {
                pa_log_error('AI request successful, processing response');
                
                // Add AI response to memory
                if ($session_id) {
                    pa_log_error('Adding AI response to memory');
                    $outId1 = $mem->addEntry($session_id, 'system_note', ['text' => $result['response']], 'session', $project_id);
                    pa_log_error('AI response addEntry (system_note) insert_id=' . (int)$outId1);
                    // Also store as ai_response for clarity in history
                    $outId2 = $mem->addEntry($session_id, 'ai_response', ['text' => $result['final'] ?? $result['response']], 'session', $project_id);
                    pa_log_error('AI response addEntry (ai_response) insert_id=' . (int)$outId2);
                }

                // Final-only: sanitize and prefer explicit final key if provider returns it
                $responseText = isset($result['response']) ? (string)$result['response'] : '';
                pa_log_error('Original response length: ' . strlen($responseText));
                
                if ($final_only) {
                    pa_log_error('Stripping analysis from response');
                    $responseText = $this->strip_analysis($responseText);
                    pa_log_error('Response length after stripping: ' . strlen($responseText));
                }
                if (isset($result['final'])) { 
                    $responseText = trim((string)$result['final']);
                    pa_log_error('Using final response, length: ' . strlen($responseText));
                }
                
                // Always return HTML (provider should return HTML; no markdown conversion here)
                $responseHtml = (string)$responseText;
                $isMini = !empty($result['explainer']);
                pa_log_error('Response type - isMini: ' . ($isMini ? 'true' : 'false'));

                // Persist executed actions to audit memory; do not append to UI unless debug
                if (!empty($result['executed_actions']) && $session_id) {
                    pa_log_error('Adding executed actions to memory: ' . count($result['executed_actions']));
                    $mem->addEntry($session_id, 'action_result_summary', $result['executed_actions'], 'session', $project_id);
                }

                // Build suggested actions list for UI
                $actionsForUi = [];
                if (!empty($result['suggested_actions']) && is_array($result['suggested_actions'])) {
                    $reg = new ProjectAgentActionRegistry();
                    $all = $reg->getAllActions();
                    foreach ($result['suggested_actions'] as $idx => $a) {
                        $aid = isset($a['action_id']) ? (string)$a['action_id'] : '';
                        $nm  = isset($all[$aid]['name']) ? $all[$aid]['name'] : $aid;
                        $params = isset($a['params']) ? $a['params'] : [];
                        $actionsForUi[] = [
                            'action_id' => $aid,
                            'action_name' => $nm,
                            'parameters' => $params,
                            'status' => 'pending',
                            'execution_order' => $idx + 1,
                        ];
                    }
                }

                $payload = [
                    'success' => true,
                    'session_id' => $session_id,
                    'run_id' => 'pa-' . date('Ymd-His'),
                    'response' => ['final' => $responseHtml, 'is_html' => true, 'mini' => $isMini],
                    'actions' => $actionsForUi
                ];
                
                pa_log_error('Final payload prepared: ' . json_encode(array_keys($payload)));
                pa_log_error('Response length in payload: ' . strlen($responseHtml));
                pa_log_error('Actions count: ' . count($actionsForUi));
                
                if ($client_token) {
                    pa_log_error('Caching response for client_token: ' . $client_token);
                    $cache = $this->session->userdata('pa_chat_cache') ?: [];
                    $cache[$client_token] = ['payload' => $payload, 'ts' => time()];
                    // Keep cache small
                    if (count($cache) > 50) { $cache = array_slice($cache, -30, null, true); }
                    $this->session->set_userdata('pa_chat_cache', $cache);
                    pa_log_error('Response cached successfully');
                }
                
                pa_log_error('Returning successful response');
                return $this->json($payload);
            } else {
                pa_log_error('AI request failed: ' . ($result['error'] ?? 'Unknown error'));
                
                // Check if user is admin for detailed error
                $isAdmin = has_permission('project_agent', '', 'admin');
                pa_log_error('User is admin: ' . ($isAdmin ? 'true' : 'false'));
                
                if ($isAdmin) {
                    pa_log_error('Returning detailed error for admin');
                    return $this->json([
                        'success' => false, 
                        'error' => $result['error'],
                        'technical_details' => [
                            'provider' => $result['provider'] ?? 'unknown',
                            'timestamp' => date('Y-m-d H:i:s'),
                            'debug_info' => $this->getDebugInfo()
                        ]
                    ]);
                } else {
                    pa_log_error('Returning simple error for non-admin');
                    return $this->json(['success'=>false, 'error'=>$result['error']]);
                }
            }
        } catch (Exception $e) {
            pa_log_error('Chat endpoint exception: ' . $e->getMessage());
            pa_log_error('Exception trace: ' . $e->getTraceAsString());
            return $this->json(['success'=>false, 'error'=>$e->getMessage()], 500);
        }
    }

    // Note: We expect providers to return HTML directly; no conversion to markdown or escaping here.

    /**
     * Persist suggested actions from a response
     */
    public function save_response_actions() {
        if (!has_permission('project_agent', '', 'view')) { ajax_access_denied(); }
        $response_id = (string)$this->input->post('response_id');
        $session_id  = (int)$this->input->post('session_id');
        $actionsJson = $this->input->post('actions');
        $actions = is_string($actionsJson) ? json_decode($actionsJson, true) : (array)$actionsJson;
        if (!$response_id || !$session_id || !is_array($actions)) {
            return $this->json(['success'=>false,'error'=>'invalid_params'], 400);
        }
        $tbl = db_prefix() . 'project_agent_response_actions';
        foreach ($actions as $i => $a) {
            $row = [
                'response_id' => $response_id,
                'session_id' => $session_id,
                'action_id' => isset($a['action_id']) ? (string)$a['action_id'] : '',
                'action_name' => isset($a['action_name']) ? (string)$a['action_name'] : (isset($a['action_id']) ? (string)$a['action_id'] : ''),
                'parameters' => json_encode(isset($a['parameters']) ? $a['parameters'] : []),
                'status' => isset($a['status']) ? (string)$a['status'] : 'pending',
                'execution_order' => isset($a['execution_order']) ? (int)$a['execution_order'] : ($i+1),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            $this->db->insert($tbl, $row);
        }
        return $this->json(['success'=>true]);
    }

    /**
     * Update action status for response actions
     */
    public function update_action_status() {
        if (!has_permission('project_agent', '', 'view')) { ajax_access_denied(); }
        $action_id = (string)$this->input->post('action_id');
        $status    = (string)$this->input->post('status');
        if (!$action_id || !$status) { return $this->json(['success'=>false,'error'=>'invalid_params'],400); }
        $tbl = db_prefix() . 'project_agent_response_actions';
        $this->db->where('action_id', $action_id);
        $ok = $this->db->update($tbl, ['status'=>$status, 'updated_at'=>date('Y-m-d H:i:s')]);
        return $this->json(['success'=>(bool)$ok]);
    }

    /**
     * Get response actions for a specific response_id
     */
    public function get_response_actions() {
        if (!has_permission('project_agent', '', 'view')) { ajax_access_denied(); }
        $response_id = (string)$this->input->post('response_id');
        if (!$response_id) { return $this->json(['success'=>false,'error'=>'invalid_params'],400); }
        $tbl = db_prefix() . 'project_agent_response_actions';
        $this->db->where('response_id', $response_id);
        $this->db->order_by('execution_order','ASC');
        $rows = $this->db->get($tbl)->result_array();
        return $this->json(['success'=>true,'actions'=>$rows]);
    }

    /**
     * Server-centric: set active session on server-side (source of truth)
     */
    public function set_active_session() {
        if (!has_permission('project_agent', '', 'view')) { ajax_access_denied(); }
        $session_id = (int)$this->input->post('session_id');
        if ($session_id <= 0) { return $this->json(['success'=>false,'error'=>'invalid_session']); }
        // Optionally validate exists
        try {
            $exists = $this->db->get_where($this->project_agent_model->t_sessions(), ['session_id'=>$session_id])->row();
            if (!$exists) { return $this->json(['success'=>false,'error'=>'session_not_found']); }
        } catch (\Throwable $e) { /* ignore strict validation */ }
        $this->session->set_userdata('pa_active_session_id', $session_id);
        return $this->json(['success'=>true,'session_id'=>$session_id]);
    }

    /**
     * Get server active session for current user
     */
    public function get_active_session() {
        if (!has_permission('project_agent', '', 'view')) { ajax_access_denied(); }
        $sid = (int)$this->session->userdata('pa_active_session_id');
        return $this->json(['success'=>true,'session_id'=>$sid ?: null]);
    }
    
    /**
     * AI Room for specific project
     */
    public function project($project_id = null) {
        if (!has_permission('project_agent', '', 'view')) {
            access_denied('project_agent');
        }
        
        if (!$project_id) {
            redirect(admin_url('project_agent'));
        }
        
        $this->load->model('projects_model');
        $project = $this->projects_model->get($project_id);
        
        if (!$project) {
            set_alert('warning', _l('error_project_not_found'));
            redirect(admin_url('project_agent'));
        }
        
        $data['title'] = _l('ai_room_title') . ' - ' . $project->name;
        $data['project'] = $project;
        $data['actions'] = $this->get_available_actions();
        // Build project context for view (tasks count, milestones, etc.)
        $data['project_context'] = $this->build_project_context($project_id);
        
        // Get or create session
        $data['session'] = $this->get_or_create_session($project_id);
        
        // Check AI provider status
        $aiIntegration = new ProjectAgentAiIntegration();
        $data['ai_status'] = $aiIntegration->getGeminiaiRecommendation();
        $data['ai_available'] = $aiIntegration->isAiAvailable();
        
        $this->load->view('ai_room', $data);
    }
    
    /**
     * Execute action via AJAX
     */
    public function execute_action() {
        if (!has_permission('project_agent', '', 'execute_safe')) {
            ajax_access_denied();
        }
        
        $action_id = $this->input->post('action_id');
        $params = $this->input->post('params');
        $session_id = $this->input->post('session_id');
        
        if (!$action_id || !$params) {
            echo json_encode([
                'success' => false,
                'error' => 'Missing required parameters'
            ]);
            return;
        }
        
        // Parse params if it's a string
        if (is_string($params)) {
            $params = json_decode($params, true);
        }
        
        $context = [
            'user_id' => get_staff_user_id(),
            'session_id' => $session_id
        ];
        
        // Initialize Action Registry
        $registry = new ProjectAgentActionRegistry();
        
        // Execute action
        $result = $registry->executeAction($action_id, $params, $context);
        
        // Log action execution
        if ($result['success']) {
            $this->log_action_execution($session_id, $action_id, $params, $result['result']);
        }
        
        echo json_encode($result);
    }
    
    /**
     * Get action schema for parameter editor
     */
    public function get_action_schema() {
        if (!has_permission('project_agent', '', 'view')) {
            ajax_access_denied();
        }
        
        $action_id = $this->input->get('action_id');
        
        if (!$action_id) {
            echo json_encode([
                'success' => false,
                'error' => 'Missing action ID'
            ]);
            return;
        }
        
        // Initialize Action Registry
        $registry = new ProjectAgentActionRegistry();
        
        try {
            $schema = $registry->getActionSchema($action_id);
            echo json_encode(['success' => true, 'schema' => $schema]);
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Admin: Manage actions (list, edit prompt, toggle active)
     */
    public function actions() {
        if (!has_permission('project_agent', '', 'admin')) {
            access_denied('project_agent');
        }
        $this->load->model('project_agent_model');
        $data['title'] = 'Agent Actions';
        $data['actions'] = $this->project_agent_model->get_actions(false); // include inactive
        // Provide DB tables (unprefixed) for Related Tables picker
        try {
            $tables = $this->db->list_tables();
            $unp = [];
            $pref = (string) db_prefix();
            foreach ($tables as $t) {
                $name = $t;
                if ($pref !== '') {
                    if (strpos($t, $pref . '_') === 0) { $name = substr($t, strlen($pref) + 1); }
                    elseif (strpos($t, $pref) === 0) { $name = substr($t, strlen($pref)); }
                }
                $unp[] = $name;
            }
            sort($unp, SORT_NATURAL | SORT_FLAG_CASE);
            $data['db_tables'] = array_values(array_unique($unp));
        } catch (\Throwable $e) { $data['db_tables'] = []; }
        // Prefer Perfex-style admin view location
        if (file_exists(module_dir_path(PROJECT_AGENT_MODULE_NAME, 'views/admin/actions/index.php'))) {
            $this->load->view('project_agent/admin/actions/index', $data);
        } else {
            $this->load->view('project_agent/actions_admin', $data);
        }
    }

    

    public function toggle_action() {
        if (!has_permission('project_agent', '', 'admin')) { ajax_access_denied(); }
        $action_id = $this->input->post('action_id');
        $is_active = (int) $this->input->post('is_active') ? 1 : 0;
        if (!$action_id) { return $this->json(['success'=>false,'error'=>'Missing action_id']); }
        $ok = $this->project_agent_model->update_action($action_id, ['is_active' => $is_active]);
        return $this->json(['success' => (bool)$ok]);
    }

    public function save_action_prompt() {
        if (!has_permission('project_agent', '', 'admin')) { ajax_access_denied(); }
        $action_id = $this->input->post('action_id');
        $prompt = (string) $this->input->post('prompt_override');
        if (!$action_id) { return $this->json(['success'=>false,'error'=>'Missing action_id']); }
        $ok = $this->project_agent_model->update_action($action_id, ['prompt_override' => $prompt]);
        return $this->json(['success' => (bool)$ok]);
    }

    /**
     * Save action configuration: prompt + parameter mapping
     */
    public function save_action_config() {
        if (!has_permission('project_agent', '', 'admin')) { ajax_access_denied(); }
        $action_id = (string)$this->input->post('action_id');
        $prompt = (string)$this->input->post('prompt_override');
        $mappingJson = $this->input->post('param_mapping');
        $entityType = (string)$this->input->post('entity_type');
        $related = $this->input->post('related_tables'); // array or csv
        if (!$action_id) { return $this->json(['success'=>false,'error'=>'Missing action_id']); }
        $mapping = null;
        if (is_string($mappingJson) && $mappingJson !== '') {
            $decoded = json_decode($mappingJson, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return $this->json(['success'=>false,'error'=>'Invalid mapping JSON']);
            }
            $mapping = json_encode($decoded);
        }
        $data = ['prompt_override' => $prompt];
        if ($mapping !== null) { $data['param_mapping'] = $mapping; }
        // Normalize related tables (store as JSON array of unprefixed names)
        if (is_array($related)) {
            $safe = [];
            foreach ($related as $t) { if (is_string($t) && $t !== '') { $safe[] = $t; } }
            $data['related_tables'] = json_encode(array_values(array_unique($safe)));
        } elseif (is_string($related) && $related !== '') {
            $parts = array_filter(array_map('trim', explode(',', $related)));
            $data['related_tables'] = json_encode(array_values(array_unique($parts)));
        }
        if ($entityType !== null) { $data['entity_type'] = $entityType; }
        $ok = $this->project_agent_model->update_action($action_id, $data);
        return $this->json(['success'=>(bool)$ok]);
    }

    /** Learn schema for union of related tables for given action_ids (or all active) */
    public function learn_related_tables() {
        if (!has_permission('project_agent', '', 'admin')) { ajax_access_denied(); }
        $ids = $this->input->post('action_ids');
        if (is_string($ids)) { $ids = json_decode($ids, true); }
        if (!is_array($ids)) { $ids = []; }

        // Fetch actions
        $all = (array) $this->project_agent_model->get_actions(false);
        $byId = [];
        foreach ($all as $r) {
            $aid = is_array($r) ? ($r['action_id'] ?? null) : ($r->action_id ?? null);
            if (!$aid) continue;
            $byId[$aid] = is_array($r) ? $r : (array) $r;
        }
        // If empty list -> default to all active
        if (empty($ids)) {
            foreach ($byId as $aid => $row) { if (!empty($row['is_active'])) { $ids[] = $aid; } }
        }

        // Collect related tables from declared field; fallback heuristic if missing
        $related = [];
        foreach ($ids as $aid) {
            if (!isset($byId[$aid])) continue;
            $row = $byId[$aid];
            $rt = isset($row['related_tables']) ? $row['related_tables'] : '';
            $list = [];
            if (is_string($rt) && $rt !== '') { $tmp = json_decode($rt, true); if (json_last_error()===JSON_ERROR_NONE) { $list = $tmp; } }
            if (is_array($list)) { foreach ($list as $t) { if (is_string($t) && $t!=='') $related[] = $t; } }
        }

        // Heuristic fallback: map by permissions/name/params
        if (empty($related)) {
            try {
                $tables = $this->db->list_tables();
                $unp = [];
                $pref = (string) db_prefix();
                foreach ($tables as $t) {
                    $name = $t;
                    if ($pref !== '') {
                        if (strpos($t, $pref . '_') === 0) { $name = substr($t, strlen($pref) + 1); }
                        elseif (strpos($t, $pref) === 0) { $name = substr($t, strlen($pref)); }
                    }
                    $unp[] = $name;
                }
                $tableToActions = $this->pa_build_table_action_index(array_values(array_unique($unp)));
                // Invert to action->tables
                foreach ($tableToActions as $tbl => $acts) {
                    foreach ($acts as $aid2) { if (in_array($aid2, $ids, true)) { $related[] = $tbl; break; } }
                }
            } catch (\Throwable $e) {}
        }

        $related = array_values(array_unique(array_filter($related)));
        if (empty($related)) {
            return $this->json(['success'=>false,'error'=>'No related tables resolved']);
        }
        // Validate & learn
        $tables = project_agent_schema_list_tables($related);
        if (empty($tables)) { return $this->json(['success'=>false,'error'=>'No valid tables'], 400); }
        $res = project_agent_schema_ai_learn($tables);
        
        // Update context_queries for each action based on schema learning results
        if ($res['success'] && !empty($res['tables'])) {
            foreach ($ids as $aid) {
                if (!isset($byId[$aid])) continue;
                $row = $byId[$aid];
                $rt = isset($row['related_tables']) ? $row['related_tables'] : '';
                $list = [];
                if (is_string($rt) && $rt !== '') { 
                    $tmp = json_decode($rt, true); 
                    if (json_last_error()===JSON_ERROR_NONE) { $list = $tmp; } 
                }
                
                // Build context_queries for this action's related tables
                $contextQueries = [];
                foreach ($list as $table) {
                    if (isset($res['tables'][$table])) {
                        $tableSchema = $res['tables'][$table];
                        $query = $this->build_context_query_from_schema($table, $tableSchema);
                        if ($query) {
                            $contextQueries[] = $query;
                        }
                    }
                }
                
                // Generate params_schema based on schema learning results
                $paramsSchema = $this->generate_params_schema_from_learning($row, $res['tables']);
                
                // Update the action with both context_queries and params_schema
                $updateData = [];
                if (!empty($contextQueries)) {
                    $updateData['context_queries'] = json_encode(['tables' => $contextQueries]);
                }
                if (!empty($paramsSchema)) {
                    $updateData['params_schema'] = json_encode($paramsSchema);
                }
                
                if (!empty($updateData)) {
                    $this->project_agent_model->update_action($aid, $updateData);
                }
            }
        }
        
        return $this->json($res);
    }
    
    /**
     * Build context query from schema learning response
     */
    private function build_context_query_from_schema($table, $tableSchema) {
        if (!is_array($tableSchema) || empty($tableSchema)) {
            return null;
        }
        
        $prefix = db_prefix();
        $fullTableName = $prefix . $table;
        
        // Check if table exists
        if (!$this->db->table_exists($fullTableName)) {
            return null;
        }
        
        // Get table structure
        $fields = $this->db->list_fields($fullTableName);
        if (empty($fields)) {
            return null;
        }
        
        // Build select columns based on schema recommendations
        $selectColumns = [];
        if (isset($tableSchema['default_select']) && is_array($tableSchema['default_select'])) {
            foreach ($tableSchema['default_select'] as $col) {
                if (in_array($col, $fields)) {
                    $selectColumns[] = "t1.`{$col}`";
                }
            }
        }
        
        // Fallback to basic columns if no default_select
        if (empty($selectColumns)) {
            $basicColumns = ['id'];
            foreach ($basicColumns as $col) {
                if (in_array($col, $fields)) {
                    $selectColumns[] = "t1.`{$col}`";
                }
            }
        }
        
        // Build order by based on schema recommendations
        $orderBy = '';
        if (isset($tableSchema['order_candidates']) && is_array($tableSchema['order_candidates'])) {
            foreach ($tableSchema['order_candidates'] as $col) {
                if (in_array($col, $fields)) {
                    $orderBy = "t1.`{$col}` DESC";
                    break;
                }
            }
        }
        
        // Fallback to id if no order_candidates
        if (empty($orderBy) && in_array('id', $fields)) {
            $orderBy = "t1.`id` DESC";
        }
        
        return [
            'table' => $table,
            'alias' => 't1',
            'from' => "{$fullTableName} AS t1",
            'select' => $selectColumns,
            'order_by' => $orderBy
        ];
    }
    
    /**
     * Generate params_schema using AI based on schema learning results
     */
    private function generate_params_schema_from_learning($actionRow, $schemaResults) {
        try {
            $actionId = $actionRow['action_id'] ?? '';
            $actionName = $actionRow['name'] ?? '';
            $actionDesc = $actionRow['description'] ?? '';
            
            // Build prompt for AI to generate params_schema
            $prompt = [];
            $prompt[] = 'You are a parameter schema designer for a CRM system.';
            $prompt[] = 'Design a strict JSON Schema for the action parameters based on the action details and related table schemas.';
            $prompt[] = 'Return STRICT JSON only, no markdown, with keys: {"type":"object","properties":{...},"required":[...]}';
            $prompt[] = '';
            $prompt[] = 'Action ID: ' . $actionId;
            $prompt[] = 'Action Name: ' . $actionName;
            $prompt[] = 'Action Description: ' . $actionDesc;
            $prompt[] = '';
            $prompt[] = 'Related Table Schemas:';
            $prompt[] = json_encode($schemaResults, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            $prompt[] = '';
            $prompt[] = 'Consider the table structures and common fields when designing the parameter schema.';
            $prompt[] = 'Include appropriate field types, validation rules, and required fields.';
            
            $text = implode("\n", $prompt);
            
            // Call AI provider
            $providerId = get_option('project_agent_ai_provider') ?: 'geminiai';
            try { 
                $provider = \app\services\ai\AiProviderRegistry::getProvider($providerId); 
            } catch (\Throwable $e) { 
                $provider = \app\services\ai\AiProviderRegistry::getProvider('openai'); 
            }
            
            $raw = $provider->chat($text);
            if (!is_string($raw)) return [];
            
            // Clean and parse response
            $raw = trim(preg_replace('/^```json\s*/i','',$raw));
            $raw = trim(preg_replace('/```\s*$/','',$raw));
            $dec = json_decode($raw, true);
            
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($dec)) {
                return [];
            }
            
            // Validate that it's a proper JSON Schema
            if (isset($dec['type']) && $dec['type'] === 'object' && isset($dec['properties'])) {
                return $dec;
            }
            
            return [];
            
        } catch (\Throwable $e) {
            return [];
        }
    }
    
    /**
     * Get memory entries for session
     */
    public function get_memory_entries() {
        if (!has_permission('project_agent', '', 'view')) {
            ajax_access_denied();
        }
        
        $session_id = $this->input->get('session_id');
        $project_id = $this->input->get('project_id');
        $limit = max(1, min(100, (int)($this->input->get('limit') ?: 50)));
        $offset = max(0, (int)($this->input->get('offset') ?: 0));
        
        if (!$session_id && $project_id) {
            // Find latest session for project
            $session = $this->project_agent_model->get_active_session($project_id, get_staff_user_id());
            if ($session && isset($session->session_id)) {
                $session_id = $session->session_id;
            } else {
                // Create a new session if none exists yet
                $new_session = $this->get_or_create_session((int)$project_id);
                if ($new_session && isset($new_session->session_id)) {
                    $session_id = $new_session->session_id;
                }
            }
        }
        
        $entries = [];
        if ($session_id) {
            $entries = $this->project_agent_model->get_memory_entries($session_id, $limit, $offset);
        }
        // Filter kinds for UI safety unless debug is enabled (permission-gated)
        $debugAllowed = has_permission('project_agent', '', 'debug_tools');
        $debug = $debugAllowed ? (int) $this->input->get('debug') : 0;
        if (!$debug && is_array($entries)) {
            $allowed = ['input','ai_response','action_call','action_result','note','system_note'];
            $entries = array_values(array_filter($entries, function($e) use ($allowed){
                $k = isset($e['kind']) ? $e['kind'] : '';
                return in_array($k, $allowed, true);
            }));
        }
        
        // Render simple HTML for timeline
        $html = '';
        foreach ($entries as $entry) {
            $kind = isset($entry['kind']) && $entry['kind'] ? $entry['kind'] : 'note';
            $badge = ($kind === 'input') ? 'primary' : (($kind === 'ai_response') ? 'secondary' : 'light');
            $content = '';
            if (!empty($entry['content_json'])) {
                $data = json_decode($entry['content_json'], true);
                if (isset($data['text'])) {
                    $content = htmlspecialchars(mb_substr($data['text'], 0, 120)) . '...';
                }
            }
            $time = isset($entry['created_at']) ? date('H:i', strtotime($entry['created_at'])) : '';
            $html .= '<div class="memory-item small mb-2 p-2 border-left border-primary">'
                  . '<div class="d-flex justify-content-between">'
                  . '<span class="badge badge-sm badge-' . $badge . '">' . ucfirst($kind) . '</span>'
                  . '<small class="text-muted">' . $time . '</small>'
                  . '</div>'
                  . '<div class="mt-1 text-truncate">' . $content . '</div>'
                  . '</div>';
        }
        
        return $this->json([
            'success' => true,
            'entries' => $entries,
            'html' => $html,
            'session_id' => $session_id
        ]);
    }
    
    /**
     * Remove obvious analysis/plan sections from model text
     */
    private function strip_analysis($text) {
        if (!is_string($text) || $text === '') { return $text; }
        // Prefer explicit <final> wrapper if present
        if (preg_match('/<final>([\s\S]*?)<\/final>/i', $text, $m)) {
            return trim($m[1]);
        }
        // Remove fenced code blocks
        $text = preg_replace('/```[\s\S]*?```/m', '', $text);
        $banHeads = [
            'Intent Analysis','Recommended Actions','Risk Assessment',
            'Additional Context','Plan','Reasoning','Rationale',
            'Steps','Tool Calls','Execution Plan'
        ];
        foreach ($banHeads as $h){
            $text = preg_replace('/\*{0,2}\s*'.preg_quote($h,'/').'\s*\:?\s*[\s\S]*?(?=\n{2,}|$)/i','',$text);
        }
        // Trim anything after common leakage phrase
        $text = preg_replace('/\bi will now[\s\S]*$/i','',$text);
        return trim($text);
    }
    
    /**
     * Add memory entry
     */
    public function add_memory_entry() {
        if (!has_permission('project_agent', '', 'view')) {
            ajax_access_denied();
        }
        
        $session_id = $this->input->post('session_id');
        $kind = $this->input->post('kind');
        $content = $this->input->post('content');
        $scope = $this->input->post('scope') ?: 'session';
        
        if (!$session_id || !$kind || !$content) {
            return $this->json([
                'success' => false,
                'error' => 'Missing required parameters'
            ]);
        }
        
        // Parse content if it's a string
        if (is_string($content)) {
            $content = json_decode($content, true);
        }
        
        $entry_data = [
            'session_id' => $session_id,
            'kind' => $kind,
            'content_json' => json_encode($content),
            'scope' => $scope,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $entry_id = $this->project_agent_model->add_memory_entry($entry_data);
        
        return $this->json([
            'success' => true,
            'entry_id' => $entry_id
        ]);
    }

    /**
     * Update Chain-Of-Memory selection for an entry
     */
    public function update_memory_chain() {
        if (!has_permission('project_agent', '', 'view')) { ajax_access_denied(); }
        $entry_id = (int)$this->input->post('memory_id');
        $is_selected = (int)$this->input->post('is_selected');
        $priority = $this->input->post('priority');
        $related_qid = $this->input->post('related_question_id');
        if (!$entry_id) { return $this->json(['success'=>false,'error'=>'Missing memory_id'], 400); }
        $ok = $this->project_agent_model->set_memory_chain_selected($entry_id, $is_selected, $priority, $related_qid);
        return $this->json(['success' => (bool)$ok]);
    }

    /**
     * Get selected memory chain for a session
     */
    public function get_memory_chain() {
        if (!has_permission('project_agent', '', 'view')) { ajax_access_denied(); }
        $session_id = (int)$this->input->get_post('session_id');
        if (!$session_id) { return $this->json(['success'=>false,'error'=>'session_id required'], 400); }
        $entries = $this->project_agent_model->get_chain_selected_entries($session_id);
        return $this->json(['success'=>true,'entries'=>$entries]);
    }

    /**
     * Send selected memory chain to AI with question
     */
    public function send_memory_chain() {
        if (!has_permission('project_agent', '', 'view')) { ajax_access_denied(); }
        $session_id = (int)$this->input->post('session_id');
        $question = (string)$this->input->post('question');
        $memory_ids = $this->input->post('memory_ids');
        if (is_string($memory_ids)) { $memory_ids = json_decode($memory_ids, true); }
        if (!is_array($memory_ids)) { $memory_ids = []; }
        if (!$question) { return $this->json(['success'=>false,'error'=>'question required'], 400); }

        // Load entries
        $tbl = db_prefix().'project_agent_memory_entries';
        if (!empty($memory_ids)) {
            $this->db->where_in('entry_id', array_map('intval', $memory_ids));
        } else if ($session_id) {
            $this->db->where('session_id', (int)$session_id);
            $this->db->where('is_chain_selected', 1);
        }
        $this->db->order_by('chain_priority', 'DESC');
        $this->db->order_by('created_at', 'DESC');
        $memories = $this->db->get($tbl)->result_array();

        // Build context
        $context = [];
        $context['memory_entries'] = [];
        foreach ($memories as $m) {
            $row = [
                'entry_id' => $m['entry_id'],
                'kind' => $m['kind'],
                'created_at' => $m['created_at'],
            ];
            $txt = '';
            try {
                $c = is_string($m['content_json']) ? json_decode($m['content_json'], true) : $m['content_json'];
                if (isset($c['text'])) { $txt = (string)$c['text']; }
                else { $txt = is_array($c) ? json_encode($c) : (string)$c; }
            } catch (\Throwable $e) {}
            $row['text'] = $txt;
            $context['memory_entries'][] = $row;
        }

        // Ask AI with enhanced prompt that includes memory entries
        $ai = new ProjectAgentAiIntegration();
        $result = $ai->generateResponse($question, $context);

        // Save chain record
        if ($session_id && !empty($memories)) {
            $this->project_agent_model->create_memory_chain_record($session_id, 'q_'.time(), array_column($memories, 'entry_id'));
        }

        return $this->json($result);
    }
    
    /**
     * Get project context
     */
    public function get_project_context() {
        if (!has_permission('project_agent', '', 'view')) {
            ajax_access_denied();
        }
        
        $project_id = $this->input->get('project_id');
        
        if (!$project_id) {
            return $this->json([
                'success' => false,
                'error' => 'Project ID required'
            ]);
        }
        
        $context = $this->build_project_context($project_id);
        
        return $this->json([
            'success' => true,
            'context' => $context
        ]);
    }
    
    /**
     * Get AI provider status
     */
    public function get_ai_status() {
        if (!has_permission('project_agent', '', 'view')) {
            ajax_access_denied();
        }
        
        $aiIntegration = new ProjectAgentAiIntegration();
        $status = $aiIntegration->getGeminiaiRecommendation();
        $status['ai_available'] = $aiIntegration->isAiAvailable();
        
        return $this->json([
            'success' => true,
            'status' => $status
        ]);
    }
    
    /**
     * Get available actions for current user
     */
    private function get_available_actions() {
        $registry = new ProjectAgentActionRegistry();
        $all_actions = $registry->getAllActions();
        $annotated = [];
        foreach ($all_actions as $action_id => $action) {
            $ok = $registry->checkPermissions($action_id, get_staff_user_id());
            $action['permission_ok'] = $ok;
            $annotated[$action_id] = $action;
        }
        return $annotated;
    }
    
    /**
     * Get projects accessible to current user
     */
    private function get_projects_for_user() {
        $this->load->model('projects_model');
        
        if (has_permission('projects', '', 'view')) {
            return $this->projects_model->get('', ['status' => 0]); // Active projects
        } else {
            // Get projects where user is member
            return $this->projects_model->get_user_projects(get_staff_user_id());
        }
    }
    
    /**
     * Get or create session for project
     */
    private function get_or_create_session($project_id) {
        $existing_session = $this->project_agent_model->get_active_session($project_id, get_staff_user_id());
        
        if ($existing_session) {
            return $existing_session;
        }
        
        // Create new session
        $session_data = [
            'project_id' => $project_id,
            'user_id' => get_staff_user_id(),
            'title' => 'AI Room Session - ' . date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $session_id = $this->project_agent_model->create_session_with_data($session_data);
        
        return $this->project_agent_model->get_session($session_id);
    }
    
    /**
     * Build project context
     */
    private function build_project_context($project_id) {
        $this->load->model('projects_model');
        $project = $this->projects_model->get($project_id);
        
        if (!$project) {
            return null;
        }
        
        // Get project progress
        $progress = $this->projects_model->calc_progress($project_id);
        
        // Get milestones
        $this->db->select('*');
        $this->db->from(db_prefix() . 'milestones');
        $this->db->where('project_id', $project_id);
        $this->db->order_by('due_date', 'ASC');
        $milestones = $this->db->get()->result_array();
        
        // Get recent tasks
        $this->db->select(db_prefix() . 'tasks.*');
        $this->db->from(db_prefix() . 'tasks');
        
        // Only join task_status if the table exists
        if ($this->db->table_exists(db_prefix() . 'task_status')) {
            $this->db->select(db_prefix() . 'task_status.name as status_name');
            $this->db->join(db_prefix() . 'task_status', db_prefix() . 'task_status.id = ' . db_prefix() . 'tasks.status');
        } else {
            // Add a default status_name if table doesn't exist
            $this->db->select("'Not Available' as status_name");
        }
        
        $this->db->where('rel_type', 'project');
        $this->db->where('rel_id', $project_id);
        $this->db->order_by('duedate', 'ASC');
        $this->db->limit(10);
        $recent_tasks = $this->db->get()->result_array();
        
        // Get total tasks count for the project
        $this->db->from(db_prefix() . 'tasks');
        $this->db->where('rel_type', 'project');
        $this->db->where('rel_id', $project_id);
        $tasks_count = (int) $this->db->count_all_results();
        
        return [
            'project' => $project,
            'progress' => $progress,
            'milestones' => $milestones,
            'recent_tasks' => $recent_tasks,
            'tasks_count' => $tasks_count
        ];
    }
    
    /**
     * Log action execution
     */
    private function log_action_execution($session_id, $action_id, $params, $result) {
        $log_data = [
            'session_id' => $session_id,
            'plan_id' => 'manual_' . time(),
            'run_id' => 'run_' . time(),
            'action_id' => $action_id,
            'params_json' => json_encode($params),
            'result_json' => json_encode($result),
            'status' => 'success',
            'executed_at' => date('Y-m-d H:i:s'),
            'executed_by' => get_staff_user_id(),
            'client_token' => 'manual_' . time()
        ];
        
        $this->project_agent_model->add_action_log($log_data);
    }

    /**
     * Get action logs for session
     */
    public function get_action_logs() {
        if (!has_permission('project_agent', '', 'view')) {
            ajax_access_denied();
        }

        $session_id = $this->input->get('session_id');
        $limit = $this->input->get('limit') ?: 50;
        $status = $this->input->get('status');
        $action_id = $this->input->get('action_id');

        // If no session_id provided, load recent logs from all sessions
        if (!$session_id) {
            $logs = $this->project_agent_model->get_recent_action_logs($limit, $status, $action_id);
        } else {
            $logs = $this->project_agent_model->get_action_logs($session_id, $limit, $status, $action_id);
        }

        return $this->json([
            'success' => true,
            'logs' => $logs,
            'total' => count($logs),
            'filters' => [
                'status' => $status,
                'action_id' => $action_id
            ]
        ]);
    }

    /**
     * Get action log details
     */
    public function get_action_log_details() {
        if (!has_permission('project_agent', '', 'view')) {
            ajax_access_denied();
        }
        
        $log_id = $this->input->get('log_id');
        
        if (!$log_id) {
            return $this->json(['success' => false, 'error' => 'Log ID required']);
        }
        
        $log = $this->project_agent_model->get_action_log($log_id);
        
        if (!$log) {
            return $this->json(['success' => false, 'error' => 'Log not found']);
        }
        
        // Parse JSON data
        $log->params = json_decode($log->params_json, true);
        $log->result = json_decode($log->result_json, true);
        
        return $this->json([
            'success' => true,
            'log' => $log
        ]);
    }

    /**
     * Safe get_option with proper table prefix handling
     */
    private function safe_get_option($name) {
        try {
            $this->db->select('value');
            $this->db->where(db_prefix() . 'options.name', $name);
            $row = $this->db->get(db_prefix() . 'options')->row();
            return $row ? $row->value : null;
        } catch (\Throwable $e) {
            log_message('error', 'Project Agent safe_get_option error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get debug information for admin users
     */
    private function getDebugInfo() {
        $debug = [
            'php_version' => PHP_VERSION,
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'execution_time' => microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'],
            'database_status' => $this->db->conn_id ? 'connected' : 'disconnected',
            'ai_providers' => []
        ];
        
        try {
            $ai = new ProjectAgentAiIntegration();
            $debug['ai_providers'] = $ai->getAvailableProviders();
        } catch (Exception $e) {
            $debug['ai_providers'] = ['error' => $e->getMessage()];
        }
        
        return $debug;
    }

    /**
     * Load conversation history by session ID
     */
    public function load_session_history() {
        if (!has_permission('project_agent', '', 'view')) { ajax_access_denied(); }
        
        $session_id = (int)$this->input->get_post('session_id');
        if (!$session_id) { 
            return $this->json(['success'=>false,'error'=>'session_id required'], 400); 
        }
        
        try {
            $mem = new ProjectAgentMemoryHelper();
            $entries = $mem->getEntries($session_id, 50); // Get last 50 entries
            // Extra diagnostics: log last 5 kinds
            $kinds = [];
            foreach (array_slice($entries, 0, 5) as $e) { $kinds[] = is_array($e)?($e['kind']??''):($e->kind??''); }
            pa_log_error('PA Debug: First entries kinds: ' . json_encode($kinds));

            // Debug logging
            pa_log_error('PA Debug: load_session_history for session ' . $session_id);
            pa_log_error('PA Debug: Found ' . count($entries) . ' raw entries');

            $conversation = [];
            foreach ($entries as $entry) {
                // Support both array and object row types
                $row = is_array($entry) ? $entry : (array)$entry;
                $contentRaw = isset($row['content_json']) ? $row['content_json'] : (isset($row['content']) ? $row['content'] : null);
                if ($contentRaw === null) {
                    pa_log_error('PA Debug: Skipping entry - no content_json');
                    continue;
                }
                $content = json_decode($contentRaw, true);
                if (isset($content['text'])) {
                    $conversation[] = [
                        'id' => isset($row['entry_id']) ? $row['entry_id'] : (isset($row['id']) ? $row['id'] : null),
                        'kind' => isset($row['kind']) ? $row['kind'] : 'unknown',
                        'text' => $content['text'],
                        'created_at' => isset($row['created_at']) ? $row['created_at'] : date('Y-m-d H:i:s'),
                        'type' => isset($content['type']) ? $content['type'] : 'unknown'
                    ];
                } else {
                    pa_log_error('PA Debug: Entry has no text field: ' . json_encode($content));
                }
            }

            pa_log_error('PA Debug: Built conversation with ' . count($conversation) . ' items');

            // Server-centric: set active session so subsequent chat uses restored session
            try {
                $this->session->set_userdata('pa_active_session_id', (int)$session_id);
                pa_log_error('PA Debug: Server active session set to ' . (int)$session_id);
            } catch (\Throwable $e) {
                pa_log_error('PA Debug: Failed to set server active session - ' . $e->getMessage());
            }
            
            return $this->json([
                'success' => true,
                'session_id' => $session_id,
                'conversation' => $conversation,
                'total_entries' => count($conversation),
                'debug_info' => [
                    'raw_entries_count' => count($entries),
                    'processed_entries' => count($conversation),
                    'timestamp' => time()
                ]
            ]);
        } catch (Exception $e) {
            return $this->json(['success'=>false,'error'=>'Failed to load conversation: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Create new session
     */
    public function create_new_session() {
        if (!has_permission('project_agent', '', 'view')) { ajax_access_denied(); }
        
        $project_id = $this->input->get_post('project_id') ? (int)$this->input->get_post('project_id') : null;
        
        try {
            $this->load->model('project_agent_model');
            $session_data = [
                'project_id' => $project_id,
                'user_id' => get_staff_user_id(),
                'title' => 'AI Room Session - ' . date('Y-m-d H:i:s')
            ];
            $session_id = $this->project_agent_model->create_session_with_data($session_data);
            
            return $this->json([
                'success' => true,
                'session_id' => $session_id,
                'message' => 'New session created successfully'
            ]);
        } catch (Exception $e) {
            return $this->json(['success'=>false,'error'=>'Failed to create session: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Get list of user sessions
     */
    public function get_user_sessions() {
        if (!has_permission('project_agent', '', 'view')) { ajax_access_denied(); }
        
        $project_id = $this->input->get_post('project_id') ? (int)$this->input->get_post('project_id') : null;
        $limit = (int)$this->input->get_post('limit') ?: 20;
        
        try {
            $this->load->model('project_agent_model');
            $sessions = $this->project_agent_model->get_user_sessions(get_staff_user_id(), $project_id, $limit);
            
            // Enrich with last message summary
            foreach ($sessions as &$s) {
                $sid = isset($s['session_id']) ? (int)$s['session_id'] : 0;
                if (!$sid) { continue; }
                try {
                    $latest = $this->project_agent_model->get_memory_entries($sid, 1, 0);
                    if (!empty($latest)) {
                        $row = $latest[0];
                        $content = [];
                        if (isset($row['content_json'])) {
                            $content = json_decode($row['content_json'], true);
                        }
                        if (isset($content['text'])) {
                            $s['last_message'] = ['text' => $content['text'], 'kind' => $row['kind']];
                        }
                    }
                } catch (\Throwable $e) { /* ignore */ }
            }
            unset($s);
            
            return $this->json([
                'success' => true,
                'sessions' => $sessions,
                'total' => count($sessions)
            ]);
        } catch (Exception $e) {
            return $this->json(['success'=>false,'error'=>'Failed to load sessions: ' . $e->getMessage()], 500);
        }
    }

    /** Rename a session (owner or admin) */
    public function rename_session() {
        if (!has_permission('project_agent', '', 'view')) { ajax_access_denied(); }
        $session_id = (int)$this->input->post('session_id');
        $title = trim((string)$this->input->post('title'));
        if (!$session_id || $title === '') { return $this->json(['success'=>false,'error'=>'invalid_params'], 400); }
        try {
            $sess = $this->project_agent_model->get_session($session_id);
            if (!$sess) { return $this->json(['success'=>false,'error'=>'not_found'], 404); }
            $owner = (int)$sess->user_id === (int)get_staff_user_id();
            if (!($owner || has_permission('project_agent','', 'admin'))) {
                return $this->json(['success'=>false,'error'=>'denied'], 403);
            }
            $this->project_agent_model->update_session($session_id, ['title'=>$title]);
            return $this->json(['success'=>true]);
        } catch (\Throwable $e) {
            return $this->json(['success'=>false,'error'=>$e->getMessage()], 500);
        }
    }

    /** Delete a session (owner or admin) */
    public function delete_session() {
        if (!has_permission('project_agent', '', 'view')) { ajax_access_denied(); }
        $session_id = (int)$this->input->post('session_id');
        if (!$session_id) { return $this->json(['success'=>false,'error'=>'invalid_params'], 400); }
        try {
            $sess = $this->project_agent_model->get_session($session_id);
            if (!$sess) { return $this->json(['success'=>false,'error'=>'not_found'], 404); }
            $owner = (int)$sess->user_id === (int)get_staff_user_id();
            if (!($owner || has_permission('project_agent','', 'admin'))) {
                return $this->json(['success'=>false,'error'=>'denied'], 403);
            }
            $ok = $this->project_agent_model->delete_session($session_id);
            return $this->json(['success'=>(bool)$ok]);
        } catch (\Throwable $e) {
            return $this->json(['success'=>false,'error'=>$e->getMessage()], 500);
        }
    }
    
    /**
     * Show conversation history page
     */
    public function conversation_history() {
        if (!has_permission('project_agent', '', 'view')) { access_denied(); }

        $project_id = $this->input->get('project_id') ? (int)$this->input->get('project_id') : null;
        $limit = 50;

        try {
            $this->load->model('project_agent_model');
            $sessions = $this->project_agent_model->get_user_sessions(get_staff_user_id(), $project_id, $limit);

            // Get session summaries
            foreach ($sessions as &$session) {
                $session = $this->project_agent_model->get_session_with_summary($session['session_id']);
            }

            $data = [
                'sessions' => $sessions,
                'project_id' => $project_id,
                'title' => _l('conversation_history')
            ];

            $this->load->view('admin/conversation_history', $data);
        } catch (Exception $e) {
            show_error('Failed to load conversation history: ' . $e->getMessage());
        }
    }

    /**
     * Test conversation history storage
     */
    public function test_conversation_storage() {
        if (!has_permission('project_agent', '', 'view')) {
            access_denied();
        }

        // Create test session
        $session_id = $this->project_agent_model->create_session(null, get_staff_user_id());

        // Add test input
        $this->load->helper('project_agent_memory');
        $mem = new ProjectAgentMemoryHelper();
        $mem->addEntry($session_id, 'input', ['text' => 'Test input message'], 'session', null);
        $mem->addEntry($session_id, 'ai_response', ['text' => 'Test AI response'], 'session', null);

        // Check if saved
        $entries = $this->project_agent_model->get_memory_entries($session_id);

        $result = [
            'session_id' => $session_id,
            'entries_count' => count($entries),
            'entries' => $entries,
            'test_passed' => count($entries) >= 2
        ];

        echo json_encode($result, JSON_PRETTY_PRINT);
    }

    /**
     * Test load session history API
     */
    public function test_load_session_history() {
        if (!has_permission('project_agent', '', 'view')) {
            access_denied();
        }

        $session_id = $this->input->get('session_id') ?: 9;

        try {
            $mem = new ProjectAgentMemoryHelper();
            $entries = $mem->getEntries($session_id, 50);

            $conversation = [];
            foreach ($entries as $entry) {
                $row = is_array($entry) ? $entry : (array)$entry;
                $contentRaw = isset($row['content_json']) ? $row['content_json'] : (isset($row['content']) ? $row['content'] : null);
                if ($contentRaw === null) { continue; }
                $content = json_decode($contentRaw, true);
                if (isset($content['text'])) {
                    $conversation[] = [
                        'id' => isset($row['entry_id']) ? $row['entry_id'] : (isset($row['id']) ? $row['id'] : null),
                        'kind' => isset($row['kind']) ? $row['kind'] : 'unknown',
                        'text' => $content['text'],
                        'created_at' => isset($row['created_at']) ? $row['created_at'] : date('Y-m-d H:i:s'),
                        'type' => isset($content['type']) ? $content['type'] : 'unknown'
                    ];
                }
            }

            $result = [
                'success' => true,
                'session_id' => $session_id,
                'conversation' => $conversation,
                'total_entries' => count($conversation),
                'raw_entries_count' => count($entries),
                'debug_info' => [
                    'entries_found' => count($entries),
                    'conversation_built' => count($conversation)
                ]
            ];

            header('Content-Type: application/json');
            echo json_encode($result, JSON_PRETTY_PRINT);
        } catch (Exception $e) {
            $result = [
                'success' => false,
                'error' => 'Exception: ' . $e->getMessage(),
                'session_id' => $session_id
            ];
            header('Content-Type: application/json');
            echo json_encode($result, JSON_PRETTY_PRINT);
        }
    }

    /**
     * Direct API test for debugging
     */
    public function debug_session_history() {
        if (!has_permission('project_agent', '', 'view')) {
            access_denied();
        }

        $session_id = $this->input->get('session_id') ?: 11; // Default to test session

        $data = [
            'title' => 'Debug Session History',
            'session_id' => $session_id,
            'api_url' => admin_url('project_agent/test_load_session_history?session_id=' . $session_id),
            'test_url' => admin_url('project_agent/test_load_session_history?session_id=' . $session_id)
        ];

        $this->load->view('admin/debug_session_history', $data);
    }

    /**
     * Output JSON helper
     */
    private function json($payload, $code = 200) {
        return $this->output
            ->set_status_header($code)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($payload));
    }

    /**
     * Context progress (polling endpoint)
     */
    public function context_progress() {
        if (!has_permission('project_agent', '', 'view')) { ajax_access_denied(); }
        $token = (string)$this->input->get_post('client_token');
        if (!$token) { return $this->json(['success'=>false,'error'=>'client_token required'], 400); }
        $res = project_agent_progress_read($token);
        return $this->json(['success'=>true,'events'=>$res['events'],'done'=>$res['done']]);
    }

    /**
     * Send a Pusher test event to the client's project-agent channel
     */
    public function pusher_ping() {
        if (!has_permission('project_agent', '', 'view')) { ajax_access_denied(); }
        $token = (string)$this->input->post('client_token');
        if (!$token) { return $this->json(['success'=>false,'error'=>'client_token required'], 400); }
        project_agent_progress_add($token, 'pa_ping', ['at' => date('c'), 'by' => get_staff_user_id()]);
        return $this->json(['success'=>true]);
    }

    /**
     * Test context building manually
     */
    public function test_context() {
        if (!has_permission('project_agent', '', 'view')) { ajax_access_denied(); }
        $token = (string)$this->input->post('client_token');
        if (!$token) { $token = 'test-' . time(); }
        
        try {
            pa_log_error('Starting manual context test for token: ' . $token);
            project_agent_progress_init($token);
            
            $builder = new ProjectAgentContextBuilder();
            $sessionId = 1; // Test with session 1
            $projectId = 1; // Test with project 1  
            $userId = get_staff_user_id();
            
            pa_log_error('Building context with params - sessionId: ' . $sessionId . ', projectId: ' . $projectId . ', userId: ' . $userId);
            $context = $builder->buildContext($sessionId, $projectId, $userId, $token);
            
            return $this->json(['success'=>true, 'context_keys' => array_keys($context), 'token' => $token]);
        } catch (\Throwable $e) {
            pa_log_error('Context test error: ' . $e->getMessage());
            return $this->json(['success'=>false, 'error' => $e->getMessage()]);
        }
    }

    /** Toggle DB tracing to find heavy queries (admin-only) */
    public function db_trace_toggle() {
        if (!has_permission('project_agent', '', 'admin')) { ajax_access_denied(); }
        $mode = (string)$this->input->get_post('mode');
        $flag = FCPATH . 'temp' . DIRECTORY_SEPARATOR . 'pa_db_trace.on';
        if ($mode === 'on') {
            if (!is_dir(FCPATH . 'temp')) { @mkdir(FCPATH . 'temp', 0777, true); }
            @file_put_contents($flag, '1');
            return $this->json(['success'=>true,'enabled'=>true]);
        } elseif ($mode === 'off') {
            @unlink($flag);
            return $this->json(['success'=>true,'enabled'=>false]);
        }
        $enabled = file_exists($flag);
        return $this->json(['success'=>true,'enabled'=>$enabled]);
    }

    /**
     * Kick AI run if not started, and stream status via events in progress log
     */
    public function chat_run() {
        pa_log_error('chat_run endpoint called');
        $token = (string)$this->input->post('client_token');
        $sig   = (string)$this->input->post('sig');
        pa_log_error('Received token: ' . $token . ', sig: ' . substr($sig, 0, 10) . '...');
        
        $isInternal = $this->validate_worker_sig($token, $sig);
        pa_log_error('Signature validation: ' . ($isInternal ? 'VALID' : 'INVALID'));
        
        // Fully hide this endpoint from public access – only signed internal calls allowed
        if (!$isInternal) { 
            pa_log_error('Invalid signature, returning 404');
            show_404(); 
            return; 
        }
        if (!$token) { 
            pa_log_error('No token provided, returning 400');
            return $this->json(['success'=>false,'error'=>'client_token required'], 400); 
        }
        try {
            pa_log_error('About to run AI job for token: ' . $token);
            $ok = $this->run_ai_job($token);
            pa_log_error('AI job completed, status: ' . ($ok ? 'done' : 'running'));
            return $this->json(['success'=>true,'status'=> $ok ? 'done' : 'running']);
        } catch (\Throwable $e) {
            pa_log_error('AI job failed: ' . $e->getMessage());
            return $this->json(['success'=>false,'status'=>'error','error'=>$e->getMessage()], 500);
        }
    }

    /**
     * Process queued job when frontend polls
     */
    public function chat_process() {
        $token = (string)$this->input->post('client_token');
        if (!$token) {
            return $this->json(['success' => false, 'error' => 'client_token required'], 400);
        }
        
        pa_log_error('Processing job for token: ' . $token);
        
        try {
            $ok = $this->run_ai_job($token);
            return $this->json(['success' => true, 'status' => $ok ? 'done' : 'running']);
        } catch (\Throwable $e) {
            pa_log_error('Job processing failed: ' . $e->getMessage());
            return $this->json(['success' => false, 'status' => 'error', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Poll chat status and final response
     */
    public function chat_progress() {
        if (!has_permission('project_agent', '', 'view')) { ajax_access_denied(); }
        $token = (string)$this->input->get_post('client_token');
        if (!$token) { return $this->json(['success'=>false,'error'=>'client_token required'], 400); }
        $res = project_agent_progress_read($token);
        $status = 'queued';
        $final = null;
        foreach ($res['events'] as $ev) {
            if ($ev['event'] === 'ai_started') { $status = 'running'; }
            if ($ev['event'] === 'ai_error') { $status = 'error'; }
            if ($ev['event'] === 'ai_final') { $status = 'done'; $final = $ev['data']; }
        }
        return $this->json(['success'=>true,'status'=>$status,'result'=>$final]);
    }

    /**
     * Fetch final chat result by token (used when pusher payload is too large)
     */
    public function chat_result() {
        if (!has_permission('project_agent', '', 'view')) { ajax_access_denied(); }
        $token = (string)$this->input->get_post('client_token');
        if (!$token) { return $this->json(['success'=>false,'error'=>'client_token required'], 400); }
        if (!function_exists('project_agent_job_result_load')) { $this->load->helper('project_agent_progress_helper'); }
        $payload = project_agent_job_result_load($token);
        if ($payload) { return $this->json(['success'=>true,'result'=>$payload]); }
        // fallback: try last from progress log
        $res = project_agent_progress_read($token);
        $final = null; foreach ($res['events'] as $ev) { if ($ev['event'] === 'ai_final') { $final = $ev['data']; } }
        if ($final) { return $this->json(['success'=>true,'result'=>$final]); }
        return $this->json(['success'=>false,'error'=>'result_not_found'], 404);
    }

    /**
     * Internal worker to execute queued AI job and push progress via Pusher/log
     */
    private function run_ai_job($token) {
        pa_log_error('Starting AI job for token: ' . $token);
        
        // Check memory before starting
        $memory_before = memory_get_usage(true);
        $memory_limit = ini_get('memory_limit');
        pa_log_error('Memory before: ' . round($memory_before / 1024 / 1024, 2) . ' MB, Limit: ' . $memory_limit);
        
        // Set memory limit if needed
        if ($memory_before > (128 * 1024 * 1024)) { // 128MB
            pa_log_error('Memory usage too high, skipping job');
            project_agent_progress_add($token, 'ai_error', ['message' => 'Memory usage too high']);
            return false;
        }
        
        $job = project_agent_job_load($token);
        if (!$job) { 
            pa_log_error('Job not found for token: ' . $token);
            project_agent_progress_add($token, 'ai_error', ['message'=>'job_not_found']); 
            return false; 
        }
        if (!pa_lock_acquire($token)) { 
            pa_log_error('Could not acquire lock for token: ' . $token);
            return false; 
        }
        try {
            pa_log_error('AI job started for token: ' . $token);
            project_agent_progress_add($token, 'ai_started', ['user_id' => $job['user_id']]);
            
            // Use minimal context to avoid memory issues
            $context = [
                'session_id' => $job['session_id'],
                'project_id' => $job['project_id'],
                'user_id' => $job['user_id'],
                'client_token' => $token,
                'minimal_mode' => true, // Flag to use minimal context
            ];
            
            pa_log_error('Calling AI integration with minimal context');
            $ai = new ProjectAgentAiIntegration();
            $result = $ai->generateResponse($job['message'], $context);
            pa_log_error('generateResponse completed, success: ' . ($result['success'] ? 'true' : 'false'));
            
            $payload = [
                'success' => (bool)($result['success'] ?? false),
                'response' => $result['response'] ?? '',
                'final' => isset($result['final']) ? $result['final'] : ($result['response'] ?? ''),
                'provider' => $result['provider'] ?? 'n/a',
                'executed_actions' => $result['executed_actions'] ?? [],
                'suggested_actions' => $result['suggested_actions'] ?? [],
                'session_id' => $job['session_id'],
                'user_id' => $job['user_id'],
            ];
            // Persist final for AJAX retrieval and auditing
            try {
                if (!function_exists('project_agent_job_result_save')) { $this->load->helper('project_agent_progress_helper'); }
                project_agent_job_result_save($token, $payload);
            } catch (\Throwable $e) { log_message('error','PA save result file failed: '.$e->getMessage()); }
            try {
                if (!empty($job['session_id'])) {
                    $mem = new ProjectAgentMemoryHelper();
                    $sid = (int)$job['session_id'];
                    pa_log_error('Persisting AI final to memory for session_id=' . $sid);
                    $rid1 = $mem->addEntry($sid, 'system_note', ['text' => $payload['final']], 'session', $job['project_id']);
                    pa_log_error('Persisted system_note insert_id=' . (int)$rid1);
                    $rid2 = $mem->addEntry($sid, 'ai_response', ['text' => $payload['final']], 'session', $job['project_id']);
                    pa_log_error('Persisted ai_response insert_id=' . (int)$rid2);
                } else {
                    pa_log_error('Skip persisting AI final - session_id is empty in job');
                }
            } catch (\Throwable $e) { log_message('error','PA save final to memory failed: '.$e->getMessage()); }
            project_agent_progress_add($token, 'ai_final', $payload);
            return true;
        } catch (\Throwable $e) {
            pa_log_error('AI job failed: ' . $e->getMessage());
            project_agent_progress_add($token, 'ai_error', ['message'=>$e->getMessage(), 'user_id'=> isset($job['user_id'])?$job['user_id']:null]);
            return false;
        } finally {
            pa_lock_release($token);
            
            // Check memory after
            $memory_after = memory_get_usage(true);
            pa_log_error('Memory after: ' . round($memory_after / 1024 / 1024, 2) . ' MB, Used: ' . round(($memory_after - $memory_before) / 1024 / 1024, 2) . ' MB');
        }
    }

    private function worker_sig($token) {
        $key = (string) $this->config->item('encryption_key');
        if ($key === '') { $key = 'perfex'; }
        return hash_hmac('sha256', (string)$token, $key);
    }

    private function validate_worker_sig($token, $sig) {
        if (!$token || !$sig) { return false; }
        return hash_equals($this->worker_sig($token), (string)$sig);
    }

    private function spawn_async_worker($token) {
        pa_log_error('Starting spawn_async_worker for token: ' . $token);
        
        // Use Perfex CRM standard approach: do_action hook
        try {
            // Schedule the job using Perfex's hook system
            do_action('project_agent_async_job', $token);
            pa_log_error('Job scheduled via do_action hook');
            return;
        } catch (\Throwable $e) {
            pa_log_error('do_action hook failed: ' . $e->getMessage());
        }
        
        // Fallback: Use Perfex's cron job approach
        try {
            // Create a temporary file to trigger the job
            $job_file = FCPATH . 'temp' . DIRECTORY_SEPARATOR . 'pa_job_' . $token . '.json';
            $job_data = [
                'token' => $token,
                'created_at' => time(),
                'status' => 'pending'
            ];
            
            if (!is_dir(FCPATH . 'temp')) {
                @mkdir(FCPATH . 'temp', 0777, true);
            }
            
            @file_put_contents($job_file, json_encode($job_data));
            pa_log_error('Job file created: ' . $job_file);
            
            // Trigger immediate execution
            $this->execute_async_job($token);
            return;
        } catch (\Throwable $e) {
            pa_log_error('Cron job approach failed: ' . $e->getMessage());
        }
        
        // Last resort: Direct execution
        pa_log_error('Falling back to direct execution');
        $this->execute_async_job($token);
    }
    
    private function execute_async_job($token) {
        pa_log_error('Executing async job for token: ' . $token);
        try {
            $this->run_ai_job($token);
            pa_log_error('Async job completed successfully');
        } catch (\Throwable $e) {
            pa_log_error('Async job failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Start AI processing in background after response is sent
     */
    private function start_background_ai_processing($token) {
        pa_log_error('Starting background AI processing for token: ' . $token);
        
        // Use output buffering to ensure response is sent first
        if (ob_get_level()) {
            ob_end_flush();
        }
        
        // Start output buffering to capture any output
        ob_start();
        
        // Process AI job
        try {
            pa_log_error('Executing AI job in background for token: ' . $token);
            $this->run_ai_job($token);
            pa_log_error('Background AI job completed successfully for token: ' . $token);
        } catch (\Throwable $e) {
            pa_log_error('Background AI job failed for token: ' . $token . ' - Error: ' . $e->getMessage());
        }
        
        // Clean up output buffer
        if (ob_get_level()) {
            ob_end_clean();
        }
    }

    /**
     * Admin health check – verify all module tables exist and are accessible.
     * Creates minimal schemas if missing and returns status summary.
     */
    public function health() {
        if (!has_permission('project_agent', '', 'admin')) {
            access_denied('project_agent');
        }
        
        $data = [];
        $data['title'] = 'Project Agent Health Check';
        
        $CI = &get_instance();
        // Ensure minimal tables exist
        if (function_exists('project_agent_ensure_min_tables')) {
            try { 
                project_agent_ensure_min_tables($CI); 
            } catch (\Throwable $e) { 
                log_message('error','PA health ensure failed: '.$e->getMessage()); 
            }
        }
        
        $tables = [
            'actions' => ['name' => db_prefix().'project_agent_actions'],
            'sessions' => ['name' => db_prefix().'project_agent_sessions'],
            'memory' => ['name' => db_prefix().'project_agent_memory_entries'],
            'logs' => ['name' => db_prefix().'project_agent_action_logs'],
        ];
        
        $summary = [];
        foreach ($tables as $key => $meta) {
            $name = $meta['name'];
            $exists = $this->db->table_exists($name);
            $count = null;
            if ($exists) {
                try { 
                    $count = (int)$this->db->count_all($name); 
                } catch (\Throwable $e) { 
                    $count = null; 
                }
            }
            $summary[$key] = [ 
                'table' => $name, 
                'exists' => $exists, 
                'count' => $count 
            ];
        }
        
        $data['tables'] = $summary;
        $data['db_version'] = (int)$this->safe_get_option('project_agent_db_version');
        
        // Check AI providers
        try {
            $ai = new ProjectAgentAiIntegration();
            $data['ai_available'] = $ai->isAiAvailable();
            $data['ai_providers'] = $ai->getAvailableProviders();
        } catch (Exception $e) {
            $data['ai_available'] = false;
            $data['ai_error'] = $e->getMessage();
        }
        
        // Check module status
        $data['modules'] = [
            'project_agent' => $this->app_modules->is_active('project_agent'),
            'openai' => $this->app_modules->is_active('openai'),
            'geminiai' => $this->app_modules->is_active('geminiai')
        ];
        
        $this->load->view('project_agent/admin/health', $data);
    }
    
    /**
     * AJAX health check endpoint
     */
    public function health_ajax() {
        if (!has_permission('project_agent', '', 'admin')) { 
            ajax_access_denied(); 
        }
        
        $CI = &get_instance();
        // Ensure minimal tables exist
        if (function_exists('project_agent_ensure_min_tables')) {
            try { project_agent_ensure_min_tables($CI); } catch (\Throwable $e) { log_message('error','PA health ensure failed: '.$e->getMessage()); }
        }
        $tables = [
            'actions' => ['name' => db_prefix().'project_agent_actions'],
            'sessions' => ['name' => db_prefix().'project_agent_sessions'],
            'memory' => ['name' => db_prefix().'project_agent_memory_entries'],
            'logs' => ['name' => db_prefix().'project_agent_action_logs'],
        ];
        $summary = [];
        foreach ($tables as $key => $meta) {
            $name = $meta['name'];
            $exists = $this->db->table_exists($name);
            $count = null;
            if ($exists) {
                try { $count = (int)$this->db->count_all($name); } catch (\Throwable $e) { $count = null; }
            } else {
                // Try legacy double-prefix name just for visibility
                $legacy = db_prefix().'tbl'.$key; // not exact; will just report false for visibility
            }
            $summary[$key] = [ 'table' => $name, 'exists' => $exists, 'count' => $count ];
        }
        return $this->json(['success'=>true,'tables'=>$summary,'db_version'=>(int)$this->safe_get_option('project_agent_db_version')]);
    }

}
