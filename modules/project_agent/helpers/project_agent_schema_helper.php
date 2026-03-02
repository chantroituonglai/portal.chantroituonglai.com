<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Project Agent Schema Helper (Mini Agent)
 * - Introspect DB tables (columns/types/keys)
 * - Ask AI to recommend safe/selective mappings (order columns, default selects, avoid columns, filters)
 * - Persist mappings in options as project_agent_schema_map_{table}
 */

function project_agent_schema_list_tables($candidates = []) {
    $CI = &get_instance();
    $CI->load->database();
    $prefix = db_prefix();
    $tables = [];
    // Normalize candidates to array of unprefixed names
    $wanted = [];
    foreach ((array)$candidates as $t) {
        $t = trim($t);
        if ($t === '') continue;
        // Allow with/without prefix
        if (strpos($t, $prefix) === 0) { $t = substr($t, strlen($prefix)); }
        $wanted[] = $t;
    }
    // If none provided, no-op
    if (empty($wanted)) { return []; }
    // Verify existence
    foreach ($wanted as $name) {
        $full = $prefix . $name;
        if ($CI->db->table_exists($full)) { $tables[] = $name; }
    }
    return array_values(array_unique($tables));
}

function project_agent_schema_introspect($table) {
    $CI = &get_instance();
    $CI->load->database();
    $full = db_prefix() . $table;
    $out = ['table' => $table, 'columns' => [], 'primary_keys' => [], 'indexes' => []];
    try {
        // Columns and types
        $fields = $CI->db->field_data($full);
        foreach ($fields as $f) {
            $out['columns'][] = [
                'name' => $f->name,
                'type' => $f->type,
                'max_length' => $f->max_length,
                'primary_key' => !empty($f->primary_key),
            ];
            if (!empty($f->primary_key)) { $out['primary_keys'][] = $f->name; }
        }
    } catch (\Throwable $e) {}
    try {
        // Indexes (best-effort)
        $q = $CI->db->query('SHOW KEYS FROM `'.$full.'`');
        foreach ($q->result_array() as $row) {
            $out['indexes'][] = [
                'key_name' => $row['Key_name'] ?? '',
                'column_name' => $row['Column_name'] ?? '',
                'non_unique' => isset($row['Non_unique']) ? (int)$row['Non_unique'] : 1,
                'index_type' => $row['Index_type'] ?? '',
            ];
        }
    } catch (\Throwable $e) {}
    return $out;
}

function project_agent_schema_build_prompt(array $schemas) {
    $lines = [];
    $lines[] = 'You are a MySQL schema assistant. For each table, recommend:';
    $lines[] = '- order_candidates: array of column names in order of preference for recent/chronological sorting';
    $lines[] = '- default_select: a minimal list of columns for lightweight SELECTs (avoid large text)';
    $lines[] = '- avoid_columns: columns to exclude from default selects (e.g., long text/blob)';
    $lines[] = '- common_filters: common WHERE filters key->description';
    $lines[] = '- notes: short tips if needed';
    $lines[] = 'Return STRICT JSON: { "tables": { "table_name": { ... } } } with no markdown or explanations.';
    $lines[] = '';
    foreach ($schemas as $s) {
        $lines[] = 'TABLE: ' . $s['table'];
        $lines[] = 'COLUMNS:';
        foreach ($s['columns'] as $c) {
            $lines[] = ' - ' . $c['name'] . ' : ' . $c['type'] . (isset($c['max_length']) && $c['max_length'] ? '(' . $c['max_length'] . ')' : '') . (!empty($c['primary_key']) ? ' [PK]' : '');
        }
        if (!empty($s['indexes'])) {
            $lines[] = 'INDEXES:';
            foreach ($s['indexes'] as $idx) {
                $lines[] = ' - ' . ($idx['key_name'] ?? '') . ' on ' . ($idx['column_name'] ?? '') . ' type ' . ($idx['index_type'] ?? '') . ' unique=' . (isset($idx['non_unique']) ? (int)!$idx['non_unique'] : 0);
            }
        }
        $lines[] = '';
    }
    return implode("\n", $lines);
}

function project_agent_schema_ai_learn(array $tables) {
    $CI = &get_instance();
    $CI->load->database();
    $result = ['success' => true, 'tables' => [], 'errors' => []];
    // Build schemas
    $schemas = [];
    foreach ($tables as $t) { $schemas[] = project_agent_schema_introspect($t); }
    $prompt = project_agent_schema_build_prompt($schemas);
    // Include the built prompt for admin/debug visibility
    $result['prompt'] = $prompt;
    // Call provider directly (bypass HTML flow)
    try {
        $providerId = get_option('project_agent_ai_provider') ?: 'geminiai';
        try { $provider = \app\services\ai\AiProviderRegistry::getProvider($providerId); }
        catch (\Throwable $e) { $provider = \app\services\ai\AiProviderRegistry::getProvider('openai'); }
        $raw = $provider->chat($prompt);
        // Parse JSON
        $parsed = project_agent_schema_parse_json($raw);
        if (!is_array($parsed) || empty($parsed['tables'])) {
            $result['success'] = false;
            $result['errors'][] = 'AI did not return valid mapping JSON';
            return $result;
        }
        // Save per table
        foreach ($parsed['tables'] as $tbl => $map) {
            $key = 'project_agent_schema_map_' . strtolower($tbl);
            update_option($key, json_encode($map));
            $result['tables'][$tbl] = $map;
        }
    } catch (\Throwable $e) {
        $result['success'] = false;
        $result['errors'][] = $e->getMessage();
    }
    return $result;
}

function project_agent_schema_parse_json($response) {
    if (!is_string($response)) return null;
    $resp = trim($response);
    $resp = preg_replace('/^```json\s*/i','',$resp);
    $resp = preg_replace('/```\s*$/','',$resp);
    $parsed = json_decode($resp, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($parsed)) return $parsed;
    if (preg_match('/\{[\s\S]*\}/', $resp, $m)) {
        $parsed = json_decode($m[0], true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($parsed)) return $parsed;
    }
    return null;
}

function project_agent_schema_get_mapping($table) {
    $key = 'project_agent_schema_map_' . strtolower($table);
    $json = get_option($key);
    if (!$json) return null;
    $map = json_decode($json, true);
    return (json_last_error() === JSON_ERROR_NONE) ? $map : null;
}
