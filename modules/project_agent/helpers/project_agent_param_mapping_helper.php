<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Project Agent – Parameter Mapping Helper (Mini Agent)
 * Build a reasonable default param mapping for an action based on its schema
 * and known context/related entities.
 *
 * Mapping format per field:
 * [field] => ['source' => 'context.project_id'|'context.user_id'|'context.session_id'|'related.*'|'static', 'default' => mixed]
 */

/**
 * Heuristic mapping without external AI call.
 * @param array $actionRow row from project_agent_actions (array)
 * @return array mapping
 */
function project_agent_build_param_mapping(array $actionRow)
{
    $mapping = [];
    $schema = isset($actionRow['params_schema']) ? $actionRow['params_schema'] : [];
    if (is_string($schema)) { $tmp = json_decode($schema, true); if (json_last_error()===JSON_ERROR_NONE) { $schema = $tmp; } }
    $related = [];
    if (isset($actionRow['related_tables'])) {
        $rel = is_string($actionRow['related_tables']) ? json_decode($actionRow['related_tables'], true) : $actionRow['related_tables'];
        if (json_last_error()===JSON_ERROR_NONE || is_array($rel)) { $related = (array)$rel; }
    }
    $relatedLower = array_map('strtolower', $related);

    $props = isset($schema['properties']) && is_array($schema['properties']) ? $schema['properties'] : [];
    $required = isset($schema['required']) && is_array($schema['required']) ? $schema['required'] : [];

    foreach ($props as $name => $ps) {
        $lname = strtolower($name);
        // Direct context ids
        if ($lname === 'project_id') { $mapping[$name] = ['source'=>'context.project_id']; continue; }
        if ($lname === 'user_id' || $lname === 'staff_id' || $lname === 'assignee_id') { $mapping[$name] = ['source'=>'context.user_id']; continue; }
        if ($lname === 'session_id') { $mapping[$name] = ['source'=>'context.session_id']; continue; }

        // Common related entities
        if (strpos($lname, 'customer_id') !== false || strpos($lname, 'client_id') !== false) {
            $mapping[$name] = ['source'=>'related.customer']; continue;
        }
        if (strpos($lname, 'task_id') !== false) { $mapping[$name] = ['source'=>'related.task']; continue; }
        if (strpos($lname, 'lead_id') !== false) { $mapping[$name] = ['source'=>'related.lead']; continue; }
        if (strpos($lname, 'invoice_id') !== false) { $mapping[$name] = ['source'=>'related.invoice']; continue; }
        if (strpos($lname, 'estimate_id') !== false) { $mapping[$name] = ['source'=>'related.estimate']; continue; }
        if (strpos($lname, 'expense_id') !== false) { $mapping[$name] = ['source'=>'related.expense']; continue; }

        // If action declares related tables, and field ends with '_id', use first related table as a guess
        if (substr($lname, -3) === '_id' && !empty($relatedLower)) {
            $tbl = $relatedLower[0];
            // Map table name heuristic to a related.* token
            $tok = 'related.' . rtrim($tbl, 's');
            $mapping[$name] = ['source' => $tok];
            continue;
        }

        // Leave unmapped for optional params; for required and boolean, provide safer defaults
        $isReq = in_array($name, $required, true);
        $type = isset($ps['type']) ? $ps['type'] : 'string';
        if ($isReq) {
            if ($type === 'boolean') { $mapping[$name] = ['source'=>'static','default'=>false]; }
            elseif ($type === 'integer' || $type === 'number') { $mapping[$name] = ['source'=>'static','default'=>0]; }
            else { $mapping[$name] = ['source'=>'static','default'=>'']; }
        }
    }
    return $mapping;
}

/** Merge new mapping into existing one, preserving explicit user choices */
function project_agent_merge_param_mapping(array $existing = null, array $suggested)
{
    if (!is_array($existing) || empty($existing)) return $suggested;
    foreach ($suggested as $k => $v) {
        if (!isset($existing[$k]) || !is_array($existing[$k])) { $existing[$k] = $v; continue; }
        // Preserve existing non-empty source/default
        if (empty($existing[$k]['source']) && !empty($v['source'])) { $existing[$k]['source'] = $v['source']; }
        if ((!isset($existing[$k]['default']) || $existing[$k]['default']==='') && isset($v['default'])) { $existing[$k]['default'] = $v['default']; }
    }
    return $existing;
}

/**
 * AI-driven mapping suggestion using current provider.
 * Returns array mapping or empty array on failure.
 */
function project_agent_build_param_mapping_ai(array $actionRow)
{
    try {
        $schema = isset($actionRow['params_schema']) ? $actionRow['params_schema'] : [];
        if (is_string($schema)) { $tmp = json_decode($schema, true); if (json_last_error()===JSON_ERROR_NONE) { $schema=$tmp; } }
        if (!is_array($schema) || empty($schema)) return [];
        $allowedSources = [
            'context.project_id','context.user_id','context.session_id','static',
            'related.project','related.customer','related.task','related.invoice','related.estimate','related.expense'
        ];
        $prompt = [];
        $prompt[] = 'You are a parameter mapping assistant.';
        $prompt[] = 'Given an action params JSON Schema, propose a mapping for each field to a source token or default value.';
        $prompt[] = 'Allowed sources: ' . implode(', ', $allowedSources) . '.';
        $prompt[] = 'Return STRICT JSON only as {"mapping": { "field": {"source":"...", "default":<value_optional>} }} with no markdown.';
        $prompt[] = '';
        $prompt[] = 'Action ID: ' . ($actionRow['action_id'] ?? '');
        $prompt[] = 'Schema JSON:';
        $prompt[] = json_encode($schema, JSON_UNESCAPED_UNICODE);
        $text = implode("\n", $prompt);
        // Call provider
        $providerId = get_option('project_agent_ai_provider') ?: 'geminiai';
        try { $provider = \app\services\ai\AiProviderRegistry::getProvider($providerId); }
        catch (\Throwable $e) { $provider = \app\services\ai\AiProviderRegistry::getProvider('openai'); }
        $raw = $provider->chat($text);
        if (!is_string($raw)) return [];
        $raw = trim(preg_replace('/^```json\s*/i','',$raw));
        $raw = trim(preg_replace('/```\s*$/','',$raw));
        $dec = json_decode($raw, true);
        if (json_last_error()!==JSON_ERROR_NONE || !is_array($dec)) return [];
        if (isset($dec['mapping']) && is_array($dec['mapping'])) return $dec['mapping'];
        // Some models may return mapping root directly
        return $dec;
    } catch (\Throwable $e) { return []; }
}

/**
 * Ask AI to build/refresh a JSON Schema for action parameters.
 * Returns array schema or empty array on failure.
 */
function project_agent_build_params_schema_ai(array $actionRow, array $relatedTables = [])
{
    try {
        $name = $actionRow['name'] ?? ($actionRow['action_id'] ?? '');
        $desc = $actionRow['description'] ?? '';
        $allowedSources = [
            'context.project_id','context.user_id','context.session_id','static',
            'related.project','related.customer','related.task','related.invoice','related.estimate','related.expense'
        ];
        $prompt = [];
        $prompt[] = 'You are a parameter schema designer.';
        $prompt[] = 'Design a strict JSON Schema for the action parameters.';
        $prompt[] = 'Consider the action name/description and these related entities: ' . implode(', ', $relatedTables) . '.';
        $prompt[] = 'The action is executed within a project context, with commonly available IDs: ' . implode(', ', $allowedSources) . '.';
        $prompt[] = 'Return STRICT JSON only, no markdown, with keys: {"type":"object","properties":{...},"required":[...] }';
        $prompt[] = '';
        $prompt[] = 'Action ID: ' . ($actionRow['action_id'] ?? '');
        $prompt[] = 'Action Name: ' . $name;
        $prompt[] = 'Action Description: ' . $desc;
        $text = implode("\n", $prompt);
        // Call provider
        $providerId = get_option('project_agent_ai_provider') ?: 'geminiai';
        try { $provider = \app\services\ai\AiProviderRegistry::getProvider($providerId); }
        catch (\Throwable $e) { $provider = \app\services\ai\AiProviderRegistry::getProvider('openai'); }
        $raw = $provider->chat($text);
        if (!is_string($raw)) return [];
        $raw = trim(preg_replace('/^```json\s*/i','',$raw));
        $raw = trim(preg_replace('/```\s*$/','',$raw));
        $dec = json_decode($raw, true);
        if (json_last_error()!==JSON_ERROR_NONE || !is_array($dec)) return [];
        if (!isset($dec['type']) || !isset($dec['properties'])) return [];
        // basic normalization
        if (!isset($dec['required']) || !is_array($dec['required'])) { $dec['required'] = []; }
        return $dec;
    } catch (\Throwable $e) { return []; }
}
