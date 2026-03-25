<?php
defined('BASEPATH') or exit('No direct script access allowed');

function ocg_bridge_ensure_schema()
{
    $CI = &get_instance();

    $tbl = db_prefix() . 'openclaw_bridge_events';
    if (!$CI->db->table_exists($tbl)) {
        $CI->db->query('CREATE TABLE `' . $tbl . '` (
'
          . '  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
'
          . '  `event_uid` VARCHAR(80) NOT NULL,
'
          . '  `event_name` VARCHAR(120) NOT NULL,
'
          . '  `module_name` VARCHAR(80) NOT NULL,
'
          . '  `action_name` VARCHAR(40) NOT NULL,
'
          . '  `entity_id` VARCHAR(64) NULL,
'
          . '  `payload_json` LONGTEXT NULL,
'
          . '  `status` VARCHAR(20) NOT NULL DEFAULT "pending",
'
          . '  `attempts` INT NOT NULL DEFAULT 0,
'
          . '  `http_code` INT NULL,
'
          . '  `response_excerpt` TEXT NULL,
'
          . '  `last_error` TEXT NULL,
'
          . '  `next_retry_at` DATETIME NULL,
'
          . '  `created_at` DATETIME NOT NULL,
'
          . '  `updated_at` DATETIME NOT NULL,
'
          . '  PRIMARY KEY (`id`),
'
          . '  UNIQUE KEY `uniq_ocg_bridge_uid` (`event_uid`),
'
          . '  KEY `idx_ocg_bridge_status_retry` (`status`, `next_retry_at`),
'
          . '  KEY `idx_ocg_bridge_created` (`created_at`)
'
          . ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;');
    }

    $tblPipeline = db_prefix() . 'openclaw_agent_pipeline_logs';
    if (!$CI->db->table_exists($tblPipeline)) {
        $CI->db->query('CREATE TABLE `' . $tblPipeline . '` (
'
          . '  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
'
          . '  `request_id` VARCHAR(120) NULL,
'
          . '  `event_uid` VARCHAR(120) NULL,
'
          . '  `direction` VARCHAR(20) NOT NULL DEFAULT "system",
'
          . '  `channel` VARCHAR(60) NOT NULL DEFAULT "bridge",
'
          . '  `agent_id` VARCHAR(80) NULL,
'
          . '  `entity_type` VARCHAR(80) NULL,
'
          . '  `entity_id` VARCHAR(80) NULL,
'
          . '  `event_name` VARCHAR(160) NULL,
'
          . '  `status` VARCHAR(30) NOT NULL DEFAULT "info",
'
          . '  `http_code` INT NULL,
'
          . '  `summary` TEXT NULL,
'
          . '  `payload_json` LONGTEXT NULL,
'
          . '  `meta_json` LONGTEXT NULL,
'
          . '  `created_at` DATETIME NOT NULL,
'
          . '  PRIMARY KEY (`id`),
'
          . '  KEY `idx_ocg_pipe_created` (`created_at`),
'
          . '  KEY `idx_ocg_pipe_dir_status` (`direction`,`status`),
'
          . '  KEY `idx_ocg_pipe_agent` (`agent_id`),
'
          . '  KEY `idx_ocg_pipe_req` (`request_id`)
'
          . ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;');
    }
}

function ocg_pipeline_log($row)
{
    $CI = &get_instance();
    $tbl = db_prefix() . 'openclaw_agent_pipeline_logs';
    if (!$CI->db->table_exists($tbl)) {
        return 0;
    }

    $payload = [
        'request_id' => isset($row['request_id']) ? (string) $row['request_id'] : null,
        'event_uid' => isset($row['event_uid']) ? (string) $row['event_uid'] : null,
        'direction' => isset($row['direction']) ? (string) $row['direction'] : 'system',
        'channel' => isset($row['channel']) ? (string) $row['channel'] : 'bridge',
        'agent_id' => isset($row['agent_id']) ? (string) $row['agent_id'] : null,
        'entity_type' => isset($row['entity_type']) ? (string) $row['entity_type'] : null,
        'entity_id' => isset($row['entity_id']) ? (string) $row['entity_id'] : null,
        'event_name' => isset($row['event_name']) ? (string) $row['event_name'] : null,
        'status' => isset($row['status']) ? (string) $row['status'] : 'info',
        'http_code' => isset($row['http_code']) ? (int) $row['http_code'] : null,
        'summary' => isset($row['summary']) ? (string) $row['summary'] : '',
        'payload_json' => json_encode(isset($row['payload']) ? $row['payload'] : []),
        'meta_json' => json_encode(isset($row['meta']) ? $row['meta'] : []),
        'created_at' => date('Y-m-d H:i:s'),
    ];

    $CI->db->insert($tbl, $payload);
    return (int) $CI->db->insert_id();
}

function ocg_bridge_enabled()
{
    return ((int) get_option('openclaw_bridge_enabled')) === 1;
}

function ocg_bridge_endpoint()
{
    return trim((string) get_option('openclaw_bridge_endpoint'));
}

function ocg_bridge_scope_for_event($eventName, $moduleName = '')
{
    $event = strtolower(trim((string) $eventName));
    $module = strtolower(trim((string) $moduleName));

    if (strpos($event, 'sales.') === 0) {
        return 'sales';
    }
    if (strpos($event, 'project.') === 0) {
        return 'projects';
    }
    if (strpos($event, 'task.') === 0) {
        return 'tasks';
    }
    if (strpos($event, 'ticket.') === 0) {
        return 'tickets';
    }
    if (strpos($event, 'customer.') === 0 || strpos($event, 'contact.') === 0) {
        return 'clients';
    }
    if (strpos($event, 'lead.') === 0) {
        return 'leads';
    }
    if (strpos($event, 'staff.') === 0) {
        return 'staff';
    }
    if (strpos($event, 'calendar.') === 0) {
        return 'calendar';
    }
    if (strpos($event, 'portal.cron.') === 0) {
        return 'cron';
    }

    if (in_array($module, ['estimates', 'invoices', 'proposals', 'invoicepaymentrecords'], true)) {
        return 'sales';
    }
    if ($module === 'projects') {
        return 'projects';
    }
    if ($module === 'tasks') {
        return 'tasks';
    }
    if ($module === 'tickets') {
        return 'tickets';
    }
    if ($module === 'clients' || $module === 'contacts') {
        return 'clients';
    }
    if ($module === 'leads') {
        return 'leads';
    }
    if ($module === 'staff') {
        return 'staff';
    }
    if ($module === 'events') {
        return 'calendar';
    }
    if ($module === 'cron') {
        return 'cron';
    }

    return 'misc';
}

function ocg_bridge_scope_enabled($scope)
{
    $scope = trim((string) $scope);
    if ($scope === '' || $scope === 'misc') {
        return true;
    }
    $raw = get_option('openclaw_bridge_scope_' . $scope);
    if ($raw === '') {
        return true;
    }
    return ((int) $raw) === 1;
}

function ocg_bridge_parse_patterns($raw)
{
    $raw = (string) $raw;
    if ($raw === '') {
        return [];
    }

    $items = preg_split('/[\r\n,]+/', $raw);
    if (!is_array($items)) {
        return [];
    }

    $out = [];
    foreach ($items as $item) {
        $pattern = trim((string) $item);
        if ($pattern === '' || strpos($pattern, '#') === 0) {
            continue;
        }
        $out[] = $pattern;
    }
    return $out;
}

function ocg_bridge_pattern_match($pattern, $value)
{
    $pattern = trim((string) $pattern);
    $value = trim((string) $value);
    if ($pattern === '' || $value === '') {
        return false;
    }

    $regex = preg_quote($pattern, '/');
    $regex = str_replace('\*', '.*', $regex);
    return preg_match('/^' . $regex . '$/i', $value) === 1;
}

function ocg_bridge_any_pattern_match($patterns, $value)
{
    if (!is_array($patterns)) {
        return false;
    }
    foreach ($patterns as $pattern) {
        if (ocg_bridge_pattern_match($pattern, $value)) {
            return true;
        }
    }
    return false;
}

function ocg_bridge_event_allowed($eventName, $moduleName, $actionName)
{
    $scope = ocg_bridge_scope_for_event($eventName, $moduleName);
    if (!ocg_bridge_scope_enabled($scope)) {
        return [false, 'scope_disabled:' . $scope];
    }

    $eventName = trim((string) $eventName);
    $moduleName = trim((string) $moduleName);
    $actionName = trim((string) $actionName);
    $candidates = array_filter([
        $eventName,
        $moduleName,
        $moduleName !== '' ? ($moduleName . '.' . $actionName) : '',
        $scope,
        $scope !== '' ? ($scope . '.' . $actionName) : '',
    ]);

    $allowPatterns = ocg_bridge_parse_patterns((string) get_option('openclaw_bridge_event_allow_patterns'));
    if (!empty($allowPatterns)) {
        $allowHit = false;
        foreach ($candidates as $candidate) {
            if (ocg_bridge_any_pattern_match($allowPatterns, $candidate)) {
                $allowHit = true;
                break;
            }
        }
        if (!$allowHit) {
            return [false, 'allowlist_miss'];
        }
    }

    $denyPatterns = ocg_bridge_parse_patterns((string) get_option('openclaw_bridge_event_deny_patterns'));
    if (!empty($denyPatterns)) {
        foreach ($candidates as $candidate) {
            if (ocg_bridge_any_pattern_match($denyPatterns, $candidate)) {
                return [false, 'denylist_match:' . $candidate];
            }
        }
    }

    return [true, 'ok'];
}

function ocg_bridge_payload_policy()
{
    $policy = strtolower(trim((string) get_option('openclaw_bridge_payload_policy')));
    if (!in_array($policy, ['full', 'summary', 'off'], true)) {
        $policy = 'full';
    }
    return $policy;
}

function ocg_bridge_payload_summary($payload)
{
    if (!is_array($payload)) {
        return $payload;
    }

    $preferred = [
        'id', 'userid', 'ticketid', 'name', 'subject', 'title', 'status', 'priority', 'date', 'dateadded',
        'datecreated', 'duedate', 'clientid', 'project_id', 'task_id', 'lead_id', 'staffid', 'total', 'subtotal',
        'currency', 'currency_name', 'hash',
    ];

    $out = [];
    foreach ($preferred as $key) {
        if (array_key_exists($key, $payload)) {
            $out[$key] = $payload[$key];
        }
    }

    if (empty($out)) {
        $picked = 0;
        foreach ($payload as $k => $v) {
            if ($picked >= 20) {
                break;
            }
            if (is_scalar($v) || $v === null) {
                $out[$k] = $v;
                $picked++;
            }
        }
    }

    $out['_summary_only'] = true;
    return $out;
}

function ocg_bridge_payload_fields_map()
{
    $raw = trim((string) get_option('openclaw_bridge_payload_fields_json'));
    if ($raw === '') {
        return [];
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        return [];
    }
    return $decoded;
}

function ocg_bridge_pick_payload_fields($payload, $eventName, $moduleName, $actionName)
{
    if (!is_array($payload)) {
        return $payload;
    }

    $map = ocg_bridge_payload_fields_map();
    if (empty($map)) {
        return $payload;
    }

    $candidates = array_filter([
        trim((string) $eventName),
        trim((string) $moduleName),
        trim((string) $moduleName) !== '' ? trim((string) $moduleName) . '.' . trim((string) $actionName) : '',
        ocg_bridge_scope_for_event($eventName, $moduleName),
    ]);

    $selectedFields = null;
    foreach ($candidates as $candidate) {
        foreach ($map as $pattern => $fields) {
            if (!is_array($fields)) {
                continue;
            }
            if (ocg_bridge_pattern_match((string) $pattern, (string) $candidate)) {
                $selectedFields = $fields;
                break 2;
            }
        }
    }

    if ($selectedFields === null && isset($map['default']) && is_array($map['default'])) {
        $selectedFields = $map['default'];
    }

    if (!is_array($selectedFields) || empty($selectedFields)) {
        return $payload;
    }

    $picked = [];
    foreach ($selectedFields as $field) {
        $field = trim((string) $field);
        if ($field === '') {
            continue;
        }
        if (array_key_exists($field, $payload)) {
            $picked[$field] = $payload[$field];
        }
    }
    $picked['_filtered_fields'] = array_values($selectedFields);
    return $picked;
}

function ocg_bridge_prepare_payload($payload, $eventName, $moduleName, $actionName)
{
    $includePayload = ((int) get_option('openclaw_bridge_include_payload')) === 1;
    if (!$includePayload) {
        return ['_payload' => 'disabled_by_option'];
    }

    $policy = ocg_bridge_payload_policy();
    if ($policy === 'off') {
        return ['_payload' => 'disabled_by_policy'];
    }

    $safePayload = $payload;
    if ($policy === 'summary') {
        $safePayload = ocg_bridge_payload_summary($safePayload);
    }

    return ocg_bridge_pick_payload_fields($safePayload, $eventName, $moduleName, $actionName);
}

function ocg_bridge_emit_from_table($eventName, $table, $idField, $entityId, $actionName, $extra = [])
{
    $CI = &get_instance();
    $row = [];
    if ($entityId !== null && $entityId !== '') {
        $row = $CI->db->where($idField, $entityId)->get(db_prefix() . $table)->row_array();
    }

    return ocg_bridge_emit_event($eventName, $table, $entityId, is_array($row) ? $row : [], $actionName, $extra);
}

function ocg_bridge_emit_event($eventName, $moduleName, $entityId, $payload, $actionName, $extra = [])
{
    if (!ocg_bridge_enabled()) {
        return ['ok' => false, 'status' => 'disabled'];
    }

    $gate = ocg_bridge_event_allowed($eventName, $moduleName, $actionName);
    if (!$gate[0]) {
        ocg_pipeline_log([
            'request_id' => null,
            'event_uid' => null,
            'direction' => 'outbound',
            'channel' => 'portal_to_openclaw',
            'agent_id' => trim((string) get_option('openclaw_bridge_agent')) ?: 'crm-agent',
            'entity_type' => $moduleName,
            'entity_id' => $entityId !== null ? (string) $entityId : null,
            'event_name' => $eventName,
            'status' => 'skipped',
            'summary' => 'Bridge event skipped by config: ' . (string) $gate[1],
            'payload' => ['event' => $eventName, 'module' => $moduleName, 'action' => $actionName],
            'meta' => ['reason' => (string) $gate[1]],
        ]);
        return ['ok' => false, 'status' => 'filtered', 'reason' => (string) $gate[1]];
    }

    $endpoint = ocg_bridge_endpoint();
    if ($endpoint === '') {
        return ['ok' => false, 'status' => 'missing_endpoint'];
    }

    $CI = &get_instance();
    $now = date('Y-m-d H:i:s');

    $safePayload = ocg_bridge_prepare_payload($payload, $eventName, $moduleName, $actionName);

    $envelope = [
        'spec_version' => '1.0',
        'event_name' => (string) $eventName,
        'module' => (string) $moduleName,
        'action' => (string) $actionName,
        'entity_id' => $entityId !== null ? (string) $entityId : null,
        'timestamp' => gmdate('c'),
        'source' => [
            'system' => 'portal',
            'module' => OPENCLAW_GATEWAY_MODULE_NAME,
            'host' => isset($_SERVER['HTTP_HOST']) ? (string) $_SERVER['HTTP_HOST'] : '',
        ],
        'routing' => [
            'target_agent' => trim((string) get_option('openclaw_bridge_agent')) ?: 'crm-agent',
            'notify_agent' => trim((string) get_option('openclaw_bridge_notify_agent')) ?: 'main',
        ],
        'payload' => $safePayload,
        'meta' => is_array($extra) ? $extra : [],
    ];

    $uidSeed = json_encode([
        $eventName,
        $moduleName,
        $actionName,
        $entityId,
        isset($payload['datecreated']) ? $payload['datecreated'] : null,
        isset($payload['date']) ? $payload['date'] : null,
        $now,
    ]);
    $eventUid = 'ocg_evt_' . substr(hash('sha256', $uidSeed), 0, 32);

    $tbl = db_prefix() . 'openclaw_bridge_events';
    if (!$CI->db->table_exists($tbl)) {
        return ['ok' => false, 'status' => 'missing_table'];
    }

    $insert = [
        'event_uid' => $eventUid,
        'event_name' => (string) $eventName,
        'module_name' => (string) $moduleName,
        'action_name' => (string) $actionName,
        'entity_id' => $entityId !== null ? (string) $entityId : null,
        'payload_json' => json_encode($envelope),
        'status' => 'pending',
        'attempts' => 0,
        'created_at' => $now,
        'updated_at' => $now,
    ];

    // Ignore duplicate uid safely.
    @$CI->db->insert($tbl, $insert);

    $row = $CI->db->where('event_uid', $eventUid)->get($tbl)->row_array();
    if (!$row) {
        return ['ok' => false, 'status' => 'queue_insert_failed'];
    }

    ocg_pipeline_log([
        'request_id' => $eventUid,
        'event_uid' => $eventUid,
        'direction' => 'outbound',
        'channel' => 'portal_to_openclaw',
        'agent_id' => isset($envelope['routing']['target_agent']) ? $envelope['routing']['target_agent'] : 'crm-agent',
        'entity_type' => $moduleName,
        'entity_id' => $entityId !== null ? (string) $entityId : null,
        'event_name' => $eventName,
        'status' => 'queued',
        'summary' => 'Portal event queued for OpenClaw delivery',
        'payload' => $envelope,
    ]);

    return ocg_bridge_send_row($row);
}

function ocg_bridge_send_row($row)
{
    $CI = &get_instance();
    $tbl = db_prefix() . 'openclaw_bridge_events';
    $endpoint = ocg_bridge_endpoint();
    $timeoutMs = (int) get_option('openclaw_bridge_timeout_ms');
    if ($timeoutMs <= 0) {
        $timeoutMs = 12000;
    }

    $payload = isset($row['payload_json']) ? (string) $row['payload_json'] : '{}';
    $payload = ocg_bridge_build_request_body($row, $payload);
    $headers = [
        'Content-Type: application/json',
        'Accept: application/json',
        'X-Bridge-Event-Id: ' . (string) $row['event_uid'],
    ];

    $token = trim((string) get_option('openclaw_bridge_auth_token'));
    if ($token !== '') {
        $headers[] = 'Authorization: Bearer ' . $token;
        $headers[] = 'x-openclaw-hook-secret: ' . $token;
    }

    $secret = trim((string) get_option('openclaw_bridge_hmac_secret'));
    if ($secret !== '') {
        $headers[] = 'X-Bridge-Signature: sha256=' . hash_hmac('sha256', $payload, $secret);
    }

    $res = ocg_bridge_http_post($endpoint, $payload, $headers, $timeoutMs);
    $attempts = isset($row['attempts']) ? ((int) $row['attempts'] + 1) : 1;

    if ($res['ok']) {
        $CI->db->where('id', (int) $row['id'])->update($tbl, [
            'status' => 'sent',
            'attempts' => $attempts,
            'http_code' => (int) $res['http_code'],
            'response_excerpt' => mb_substr((string) $res['body'], 0, 2000),
            'last_error' => null,
            'next_retry_at' => null,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        ocg_pipeline_log([
            'request_id' => isset($row['event_uid']) ? (string) $row['event_uid'] : null,
            'event_uid' => isset($row['event_uid']) ? (string) $row['event_uid'] : null,
            'direction' => 'outbound',
            'channel' => 'portal_to_openclaw',
            'status' => 'delivered',
            'http_code' => (int) $res['http_code'],
            'event_name' => isset($row['event_name']) ? (string) $row['event_name'] : null,
            'entity_type' => isset($row['module_name']) ? (string) $row['module_name'] : null,
            'entity_id' => isset($row['entity_id']) ? (string) $row['entity_id'] : null,
            'summary' => 'Delivered to OpenClaw hook',
            'meta' => ['attempts' => $attempts],
        ]);

        return ['ok' => true, 'status' => 'sent', 'http_code' => $res['http_code']];
    }

    $delay = (int) get_option('openclaw_bridge_retry_delay_sec');
    if ($delay <= 0) {
        $delay = 60;
    }

    $CI->db->where('id', (int) $row['id'])->update($tbl, [
        'status' => 'failed',
        'attempts' => $attempts,
        'http_code' => (int) $res['http_code'],
        'response_excerpt' => mb_substr((string) $res['body'], 0, 2000),
        'last_error' => mb_substr((string) $res['error'], 0, 2000),
        'next_retry_at' => date('Y-m-d H:i:s', time() + $delay),
        'updated_at' => date('Y-m-d H:i:s'),
    ]);

    ocg_pipeline_log([
        'request_id' => isset($row['event_uid']) ? (string) $row['event_uid'] : null,
        'event_uid' => isset($row['event_uid']) ? (string) $row['event_uid'] : null,
        'direction' => 'outbound',
        'channel' => 'portal_to_openclaw',
        'status' => 'failed',
        'http_code' => (int) $res['http_code'],
        'event_name' => isset($row['event_name']) ? (string) $row['event_name'] : null,
        'entity_type' => isset($row['module_name']) ? (string) $row['module_name'] : null,
        'entity_id' => isset($row['entity_id']) ? (string) $row['entity_id'] : null,
        'summary' => 'Delivery to OpenClaw failed',
        'meta' => ['attempts' => $attempts, 'error' => $res['error']],
    ]);

    return ['ok' => false, 'status' => 'failed', 'http_code' => $res['http_code'], 'error' => $res['error']];
}

function ocg_bridge_retry_failed()
{
    if (!ocg_bridge_enabled()) {
        return;
    }

    $CI = &get_instance();
    $tbl = db_prefix() . 'openclaw_bridge_events';
    if (!$CI->db->table_exists($tbl)) {
        return;
    }

    $maxAttempts = (int) get_option('openclaw_bridge_retry_max');
    if ($maxAttempts <= 0) {
        $maxAttempts = 3;
    }

    $now = date('Y-m-d H:i:s');
    $rows = $CI->db
        ->where('status', 'failed')
        ->where('attempts <', $maxAttempts)
        ->group_start()
            ->where('next_retry_at IS NULL', null, false)
            ->or_where('next_retry_at <=', $now)
        ->group_end()
        ->order_by('id', 'ASC')
        ->limit(30)
        ->get($tbl)
        ->result_array();

    foreach ($rows as $row) {
        ocg_bridge_send_row($row);
    }
}

function ocg_bridge_build_request_body($row, $payloadJson)
{
    $groupId = trim((string) get_option('openclaw_bridge_group_id'));
    if ($groupId === '') {
        return $payloadJson;
    }

    $event = json_decode((string) $payloadJson, true);
    if (!is_array($event)) {
        $event = [];
    }

    $eventName = isset($event['event_name']) ? (string) $event['event_name'] : 'portal.event';
    $module = isset($event['module']) ? (string) $event['module'] : 'portal';
    $entityId = isset($event['entity_id']) ? (string) $event['entity_id'] : '';
    $agent = isset($event['routing']['target_agent']) ? (string) $event['routing']['target_agent'] : 'crm-agent';

    $draftText = '[Portal][' . $eventName . '] module=' . $module;
    if ($entityId !== '') {
        $draftText .= ' entity=' . $entityId;
    }
    $draftText .= ' agent=' . $agent;

    $compat = [
        'request_id' => 'bridge_' . (string) $row['event_uid'],
        'group_id' => $groupId,
        'draft_text' => $draftText,
        'draft_meta' => $event,
        'relevance_score' => 0.98,
        'source' => 'portal_openclaw_gateway',
    ];

    return json_encode($compat);
}

function ocg_bridge_http_post($url, $payloadJson, $headers, $timeoutMs)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payloadJson);
    curl_setopt($ch, CURLOPT_TIMEOUT_MS, $timeoutMs);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, min($timeoutMs, 5000));
    curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);

    $body = curl_exec($ch);
    $errno = curl_errno($ch);
    $error = $errno ? curl_error($ch) : '';
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($errno) {
        return ['ok' => false, 'http_code' => $code > 0 ? $code : 599, 'body' => (string) $body, 'error' => 'curl_' . $errno . ': ' . $error];
    }

    if ($code >= 200 && $code < 300) {
        return ['ok' => true, 'http_code' => $code, 'body' => (string) $body, 'error' => ''];
    }

    return ['ok' => false, 'http_code' => $code, 'body' => (string) $body, 'error' => 'http_' . $code];
}
