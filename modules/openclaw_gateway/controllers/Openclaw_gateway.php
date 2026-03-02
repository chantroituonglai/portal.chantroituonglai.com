<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Openclaw_gateway extends CI_Controller
{
    private $requestId;
    private $startedAt;
    private $currentPrincipal;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('openclaw_gateway/Openclaw_gateway_model', 'gateway_model');
        $this->load->helper('openclaw_gateway/openclaw_gateway');

        $this->requestId = ocg_request_id();
        $this->startedAt = microtime(true);
        $this->currentPrincipal = null;
    }

    public function capabilities()
    {
        $principal = $this->authenticate();
        if (!$principal['ok']) {
            return $this->respond_auth_error($principal);
        }

        $catalog = $this->build_action_catalog();
        $actions = [];
        foreach ($catalog as $actionId => $meta) {
            if ($this->is_action_allowed($principal['principal'], $actionId, $meta)) {
                $actions[$actionId] = $meta;
            }
        }

        return $this->respond('success', ['actions' => $actions, 'auth_mode' => (string) get_option('openclaw_gateway_auth_mode')]);
    }

    public function invoke()
    {
        $principal = $this->authenticate();
        if (!$principal['ok']) {
            return $this->respond_auth_error($principal);
        }

        $body = ocg_decode_json_body();
        $actionId = isset($body['action_id']) ? trim((string) $body['action_id']) : '';
        $payload = isset($body['payload']) && is_array($body['payload']) ? $body['payload'] : [];
        $dryRun = !empty($body['dry_run']);
        $idempotencyKey = isset($body['idempotency_key']) ? trim((string) $body['idempotency_key']) : '';

        if ($actionId === '') {
            return $this->respond_error('validation_error', 400, 'action_id is required');
        }

        $catalog = $this->build_action_catalog();
        if (!isset($catalog[$actionId])) {
            return $this->respond_error('validation_error', 404, 'action_id not found');
        }
        $meta = $catalog[$actionId];

        if (!$this->is_action_allowed($principal['principal'], $actionId, $meta)) {
            return $this->respond_error('scope_denied', 403, 'action scope denied', ['action_id' => $actionId]);
        }

        foreach ($meta['required_fields'] as $field) {
            if (!array_key_exists($field, $payload)) {
                return $this->respond_error('validation_error', 400, 'missing required field: ' . $field, ['action_id' => $actionId]);
            }
        }

        if ((int) get_option('openclaw_gateway_read_only') === 1 && ocg_is_write_verb($meta['verb'])) {
            return $this->respond_error('scope_denied', 403, 'gateway is in read-only mode', ['action_id' => $actionId]);
        }

        if ($dryRun) {
            return $this->respond('success', [
                'dry_run' => true,
                'action_id' => $actionId,
                'upstream' => $meta['upstream'],
                'method' => strtoupper($meta['verb']),
                'payload_preview' => ocg_mask_sensitive($payload),
            ]);
        }

        $principalHash = hash('sha256', json_encode($principal['principal']));
        if ($idempotencyKey !== '' && ocg_is_write_verb($meta['verb'])) {
            $existing = $this->gateway_model->idempotency_get($idempotencyKey, $principalHash, $actionId);
            if ($existing) {
                $cached = json_decode($existing['response_json'], true);
                if (is_array($cached)) {
                    $cached['meta']['idempotency_replay'] = true;
                    return $this->emit($cached, 200);
                }
            }
        }

        $exec = $this->execute_action($meta, $payload, $principal['principal']);
        $httpCode = (int) $exec['http_code'];

        $response = [
            'request_id' => $this->requestId,
            'status' => $exec['ok'] ? 'success' : 'error',
            'data' => $exec['ok'] ? $exec['data'] : null,
            'error' => $exec['ok'] ? null : [
                'code' => ocg_error_code($exec['status']),
                'message' => $exec['message'],
                'details' => $exec['details'],
            ],
            'meta' => [
                'latency_ms' => $this->latency_ms(),
                'upstream' => $meta['upstream'],
                'action_id' => $actionId,
                'risk_level' => $meta['risk_level'],
            ],
        ];

        if ($idempotencyKey !== '' && $exec['ok'] && ocg_is_write_verb($meta['verb'])) {
            $this->gateway_model->idempotency_store([
                'idempotency_key' => $idempotencyKey,
                'principal_hash' => $principalHash,
                'action_id' => $actionId,
                'request_hash' => hash('sha256', json_encode($payload)),
                'response_json' => json_encode($response),
                'created_at' => ocg_now(),
            ]);
        }

        return $this->emit($response, $httpCode);
    }

    public function batch()
    {
        $principal = $this->authenticate();
        if (!$principal['ok']) {
            return $this->respond_auth_error($principal);
        }

        $body = ocg_decode_json_body();
        $actions = isset($body['actions']) && is_array($body['actions']) ? $body['actions'] : [];
        if (empty($actions)) {
            return $this->respond_error('validation_error', 400, 'actions is required');
        }

        $results = [];
        foreach ($actions as $idx => $entry) {
            $actionId = isset($entry['action_id']) ? (string) $entry['action_id'] : '';
            $payload = isset($entry['payload']) && is_array($entry['payload']) ? $entry['payload'] : [];
            $catalog = $this->build_action_catalog();

            if ($actionId === '' || !isset($catalog[$actionId])) {
                $results[] = ['index' => $idx, 'status' => 'error', 'error' => 'action_id invalid'];
                continue;
            }

            $meta = $catalog[$actionId];
            if (!$this->is_action_allowed($principal['principal'], $actionId, $meta)) {
                $results[] = ['index' => $idx, 'status' => 'error', 'error' => 'scope denied'];
                continue;
            }

            $exec = $this->execute_action($meta, $payload, $principal['principal']);
            $results[] = [
                'index' => $idx,
                'action_id' => $actionId,
                'status' => $exec['ok'] ? 'success' : 'error',
                'http_code' => $exec['http_code'],
                'data' => $exec['ok'] ? $exec['data'] : null,
                'error' => $exec['ok'] ? null : $exec['message'],
            ];
        }

        return $this->respond('success', ['results' => $results]);
    }

    public function health()
    {
        $principal = $this->authenticate(false);
        $dbOk = $this->db->table_exists(db_prefix() . 'openclaw_gateway_logs');
        $apiModuleExists = is_dir(APPPATH . '../modules/api');
        $projectAgentExists = is_dir(APPPATH . '../modules/project_agent');

        return $this->respond('success', [
            'gateway' => 'ok',
            'auth' => $principal['ok'] ? 'ok' : 'anonymous',
            'db' => $dbOk ? 'ok' : 'missing',
            'modules' => [
                'api' => $apiModuleExists,
                'flutex_admin_api' => is_dir(APPPATH . '../modules/flutex_admin_api'),
                'project_agent' => $projectAgentExists,
            ],
            'auth_mode' => (string) get_option('openclaw_gateway_auth_mode'),
        ]);
    }

    public function stats()
    {
        $principal = $this->authenticate();
        if (!$principal['ok']) {
            return $this->respond_auth_error($principal);
        }

        $hours = (int) $this->input->get('hours');
        if ($hours <= 0) {
            $hours = 24;
        }

        $stats = $this->gateway_model->stats($hours);
        return $this->respond('success', ['hours' => $hours, 'stats' => $stats]);
    }

    public function audit($requestId = '')
    {
        $principal = $this->authenticate();
        if (!$principal['ok']) {
            return $this->respond_auth_error($principal);
        }

        $requestId = trim((string) $requestId);
        if ($requestId === '') {
            return $this->respond_error('validation_error', 400, 'request_id is required');
        }

        $rows = $this->gateway_model->get_audit($requestId);
        return $this->respond('success', ['entries' => $rows]);
    }

    private function authenticate($required = true)
    {
        $mode = (string) get_option('openclaw_gateway_auth_mode');
        if ($mode === '') {
            $mode = 'dual';
        }

        $headers = function_exists('apache_request_headers') ? apache_request_headers() : [];
        $apiKey = '';
        $authorization = '';

        foreach ($headers as $k => $v) {
            $lk = strtolower((string) $k);
            if ($lk === 'x-api-key') {
                $apiKey = trim((string) $v);
            }
            if ($lk === 'authorization') {
                $authorization = trim((string) $v);
            }
        }

        if ($apiKey === '' && $this->input->get('key')) {
            $apiKey = trim((string) $this->input->get('key'));
        }
        if ($apiKey === '' && $this->input->post('key')) {
            $apiKey = trim((string) $this->input->post('key'));
        }

        if (in_array($mode, ['api_key', 'dual'], true) && $apiKey !== '') {
            $principal = $this->auth_api_key($apiKey);
            if ($principal['ok']) {
                $this->currentPrincipal = $principal['principal'];
                return $principal;
            }
        }

        if (in_array($mode, ['token', 'dual'], true) && $authorization !== '') {
            $token = $authorization;
            if (stripos($authorization, 'Bearer ') === 0) {
                $token = trim(substr($authorization, 7));
            }
            $principal = $this->auth_flutex_token($token);
            if ($principal['ok']) {
                $this->currentPrincipal = $principal['principal'];
                return $principal;
            }
        }

        if (!$required) {
            $this->currentPrincipal = ['type' => 'anonymous', 'id' => null];
            return ['ok' => false, 'status' => 'unauthorized', 'message' => 'anonymous access'];
        }

        return ['ok' => false, 'status' => 'unauthorized', 'message' => 'invalid or missing credentials'];
    }

    private function auth_api_key($apiKey)
    {
        $tbl = db_prefix() . 'user_api';
        if (!$this->db->table_exists($tbl)) {
            return ['ok' => false, 'status' => 'unauthorized', 'message' => 'api key table missing'];
        }

        $row = $this->db
            ->where('token', $apiKey)
            ->where('permission_enable', 1)
            ->where('expiration_date > NOW()')
            ->get($tbl)
            ->row_array();

        if (!$row) {
            return ['ok' => false, 'status' => 'unauthorized', 'message' => 'api key invalid or expired'];
        }

        $permissions = $this->db
            ->where('api_id', (int) $row['id'])
            ->get(db_prefix() . 'user_api_permissions')
            ->result_array();

        return [
            'ok' => true,
            'principal' => [
                'type' => 'api_key',
                'id' => (string) $row['id'],
                'name' => (string) $row['name'],
                'user' => (string) $row['user'],
                'scopes' => $permissions,
            ],
        ];
    }

    private function auth_flutex_token($token)
    {
        if ($token === '') {
            return ['ok' => false, 'status' => 'unauthorized', 'message' => 'missing token'];
        }

        $tbl = db_prefix() . 'staff';
        if (!$this->db->table_exists($tbl)) {
            return ['ok' => false, 'status' => 'unauthorized', 'message' => 'staff table missing'];
        }

        $row = $this->db
            ->where('flutex_api_key', $token)
            ->where('active', 1)
            ->get($tbl)
            ->row_array();

        if (!$row) {
            return ['ok' => false, 'status' => 'unauthorized', 'message' => 'token invalid'];
        }

        return [
            'ok' => true,
            'principal' => [
                'type' => 'staff_token',
                'id' => (string) $row['staffid'],
                'name' => trim(((string) $row['firstname']) . ' ' . ((string) $row['lastname'])),
                'user' => (string) $row['email'],
                'scopes' => [['feature' => '*', 'capability' => '*']],
            ],
        ];
    }

    private function build_action_catalog()
    {
        static $catalog = null;
        if ($catalog !== null) {
            return $catalog;
        }

        $catalog = [];
        $permissions = function_exists('get_available_api_permissions') ? get_available_api_permissions() : [];

        foreach ($permissions as $module => $meta) {
            $caps = isset($meta['capabilities']) && is_array($meta['capabilities']) ? $meta['capabilities'] : [];
            foreach ($caps as $verb => $label) {
                $actionId = 'core.' . $module . '.' . strtolower((string) $verb);
                $catalog[$actionId] = [
                    'upstream' => 'modules/api',
                    'module' => $module,
                    'verb' => strtolower((string) $verb),
                    'required_fields' => [],
                    'scope' => ['feature' => $module, 'capability' => strtolower((string) $verb)],
                    'risk_level' => $this->risk_from_verb($verb),
                    'idempotency_mode' => ocg_is_write_verb($verb) ? 'required_for_safe_retry' : 'none',
                    'timeout_ms' => (int) get_option('openclaw_gateway_request_timeout_ms'),
                ];
            }
        }

        $agentActions = [
            'agent.project_agent.chat' => ['path' => 'admin/project_agent/chat', 'verb' => 'post', 'required' => ['message']],
            'agent.project_agent.execute_action' => ['path' => 'admin/project_agent/execute_action', 'verb' => 'post', 'required' => ['action_id', 'params']],
            'agent.project_agent.chat_progress' => ['path' => 'admin/project_agent/chat_progress', 'verb' => 'get', 'required' => ['client_token']],
            'agent.project_agent.chat_result' => ['path' => 'admin/project_agent/chat_result', 'verb' => 'get', 'required' => ['client_token']],
        ];

        foreach ($agentActions as $actionId => $cfg) {
            $catalog[$actionId] = [
                'upstream' => 'modules/project_agent',
                'module' => 'project_agent',
                'path' => $cfg['path'],
                'verb' => $cfg['verb'],
                'required_fields' => $cfg['required'],
                'scope' => ['feature' => 'project_agent', 'capability' => 'execute_safe'],
                'risk_level' => $this->risk_from_verb($cfg['verb']),
                'idempotency_mode' => ocg_is_write_verb($cfg['verb']) ? 'required_for_safe_retry' : 'none',
                'timeout_ms' => (int) get_option('openclaw_gateway_request_timeout_ms'),
            ];
        }

        return $catalog;
    }

    private function risk_from_verb($verb)
    {
        $verb = strtolower((string) $verb);
        if (in_array($verb, ['delete'], true)) {
            return 'high';
        }
        if (in_array($verb, ['post', 'put', 'patch'], true)) {
            return 'medium';
        }
        return 'low';
    }

    private function is_action_allowed($principal, $actionId, $meta)
    {
        if (!isset($principal['type'])) {
            return false;
        }

        if ($principal['type'] === 'staff_token') {
            return true;
        }

        $scopes = isset($principal['scopes']) && is_array($principal['scopes']) ? $principal['scopes'] : [];
        if (empty($scopes)) {
            return false;
        }

        $feature = isset($meta['scope']['feature']) ? (string) $meta['scope']['feature'] : '';
        $capability = isset($meta['scope']['capability']) ? (string) $meta['scope']['capability'] : '';

        foreach ($scopes as $scope) {
            $sf = isset($scope['feature']) ? strtolower((string) $scope['feature']) : '';
            $sc = isset($scope['capability']) ? strtolower((string) $scope['capability']) : '';
            if (($sf === '*' || $sf === strtolower($feature)) && ($sc === '*' || $sc === strtolower($capability))) {
                return true;
            }
        }

        return false;
    }

    private function execute_action($meta, $payload, $principal)
    {
        $verb = strtolower((string) $meta['verb']);
        $timeoutMs = isset($meta['timeout_ms']) ? (int) $meta['timeout_ms'] : 12000;
        if ($timeoutMs <= 0) {
            $timeoutMs = 12000;
        }

        $url = '';
        $headers = ['Accept: application/json', 'X-Request-Id: ' . $this->requestId];
        $encodeAs = 'json';

        if (strpos((string) $meta['upstream'], 'modules/api') === 0) {
            $headers[] = 'Content-Type: application/json';
            $module = (string) $meta['module'];
            $key = $this->extract_inbound_key();
            if ($key !== '') {
                $headers[] = 'X-API-KEY: ' . $key;
            }

            if ($verb === 'search_get' || $verb === 'search') {
                $query = isset($payload['query']) ? rawurlencode((string) $payload['query']) : '';
                $url = rtrim(site_url('api/' . $module . '/search/' . $query), '/');
                $verb = 'get';
            } elseif ($verb === 'get') {
                if (isset($payload['id']) && $payload['id'] !== '') {
                    $url = rtrim(site_url('api/' . $module . '/' . rawurlencode((string) $payload['id'])), '/');
                } else {
                    $url = rtrim(site_url('api/' . $module), '/');
                }
            } elseif ($verb === 'delete') {
                if (!isset($payload['id'])) {
                    return ['ok' => false, 'status' => 'validation_error', 'http_code' => 400, 'message' => 'id is required for delete', 'details' => null];
                }
                $url = rtrim(site_url('api/delete/' . $module . '/' . rawurlencode((string) $payload['id'])), '/');
            } else {
                if (isset($payload['id']) && $payload['id'] !== '') {
                    $url = rtrim(site_url('api/' . $module . '/' . rawurlencode((string) $payload['id'])), '/');
                    unset($payload['id']);
                } else {
                    $url = rtrim(site_url('api/' . $module), '/');
                }
            }
        } else {
            $encodeAs = 'form';
            $headers[] = 'Content-Type: application/x-www-form-urlencoded';
            $url = rtrim(site_url($meta['path']), '/');
            $auth = $this->extract_inbound_authorization();
            if ($auth !== '') {
                $headers[] = 'Authorization: ' . $auth;
            }
        }

        $retryMax = (int) get_option('openclaw_gateway_retry_max');
        if ($retryMax < 0) {
            $retryMax = 0;
        }

        $lastErr = null;
        $lastHttp = 500;
        for ($attempt = 0; $attempt <= $retryMax; $attempt++) {
            $res = $this->http_call($url, $verb, $payload, $headers, $timeoutMs, $encodeAs);
            $lastErr = $res;
            $lastHttp = (int) $res['http_code'];

            if ($res['ok']) {
                return [
                    'ok' => true,
                    'status' => 'success',
                    'http_code' => $lastHttp,
                    'data' => $res['body'],
                    'message' => 'ok',
                    'details' => ['attempt' => $attempt + 1, 'url' => $url],
                ];
            }

            if ($lastHttp < 500 && $res['status'] !== 'timeout') {
                break;
            }
            usleep(100000);
        }

        return [
            'ok' => false,
            'status' => $lastErr['status'],
            'http_code' => $lastHttp,
            'data' => null,
            'message' => $lastErr['message'],
            'details' => ['url' => $url, 'body' => $lastErr['body']],
        ];
    }

    private function http_call($url, $verb, $payload, $headers, $timeoutMs, $encodeAs = 'json')
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($verb));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, min($timeoutMs, 5000));
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, $timeoutMs);

        if (in_array(strtolower($verb), ['post', 'put', 'patch', 'delete'], true)) {
            if ($encodeAs === 'form') {
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            }
        }

        $raw = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno !== 0) {
            return [
                'ok' => false,
                'status' => 'timeout',
                'http_code' => 504,
                'message' => 'curl_error: ' . $error,
                'body' => null,
            ];
        }

        $decoded = json_decode((string) $raw, true);
        $body = is_array($decoded) ? $decoded : ['raw' => $raw];

        if ($httpCode >= 200 && $httpCode < 300) {
            return ['ok' => true, 'status' => 'success', 'http_code' => $httpCode, 'message' => 'ok', 'body' => $body];
        }

        return [
            'ok' => false,
            'status' => 'upstream_error',
            'http_code' => $httpCode > 0 ? $httpCode : 502,
            'message' => 'upstream returned non-2xx',
            'body' => $body,
        ];
    }

    private function extract_inbound_key()
    {
        $headers = function_exists('apache_request_headers') ? apache_request_headers() : [];
        foreach ($headers as $k => $v) {
            if (strtolower((string) $k) === 'x-api-key') {
                return trim((string) $v);
            }
        }
        if ($this->input->get('key')) {
            return trim((string) $this->input->get('key'));
        }
        if ($this->input->post('key')) {
            return trim((string) $this->input->post('key'));
        }
        return '';
    }

    private function extract_inbound_authorization()
    {
        $headers = function_exists('apache_request_headers') ? apache_request_headers() : [];
        foreach ($headers as $k => $v) {
            if (strtolower((string) $k) === 'authorization') {
                return trim((string) $v);
            }
        }
        return '';
    }

    private function respond($status, $data = null, $httpCode = 200, $error = null)
    {
        return $this->emit([
            'request_id' => $this->requestId,
            'status' => $status,
            'data' => $data,
            'error' => $error,
            'meta' => [
                'latency_ms' => $this->latency_ms(),
            ],
        ], $httpCode);
    }

    private function respond_error($status, $httpCode, $message, $details = null)
    {
        return $this->emit([
            'request_id' => $this->requestId,
            'status' => 'error',
            'data' => null,
            'error' => [
                'code' => ocg_error_code($status),
                'message' => $message,
                'details' => $details,
            ],
            'meta' => [
                'latency_ms' => $this->latency_ms(),
            ],
        ], $httpCode, $status);
    }

    private function respond_auth_error($principal)
    {
        return $this->respond_error($principal['status'], 401, $principal['message']);
    }

    private function emit($payload, $httpCode = 200, $statusForLog = null)
    {
        $status = $statusForLog;
        if ($status === null) {
            $status = $payload['status'] === 'success' ? 'success' : 'error';
        }

        $actionId = null;
        if (isset($payload['meta']['action_id'])) {
            $actionId = $payload['meta']['action_id'];
        }

        $principalType = 'unknown';
        $principalId = null;
        if (is_array($this->currentPrincipal)) {
            $principalType = isset($this->currentPrincipal['type']) ? (string) $this->currentPrincipal['type'] : 'unknown';
            $principalId = isset($this->currentPrincipal['id']) ? (string) $this->currentPrincipal['id'] : null;
        }

        $this->gateway_model->log_request([
            'request_id' => $this->requestId,
            'principal_type' => $principalType,
            'principal_id' => $principalId,
            'action_id' => $actionId,
            'http_method' => (string) $this->input->server('REQUEST_METHOD'),
            'path' => (string) $this->uri->uri_string(),
            'params_masked' => json_encode(ocg_mask_sensitive(ocg_decode_json_body())),
            'status' => $status,
            'http_code' => (int) $httpCode,
            'error_code' => isset($payload['error']['code']) ? (string) $payload['error']['code'] : null,
            'error_message' => isset($payload['error']['message']) ? (string) $payload['error']['message'] : null,
            'latency_ms' => $this->latency_ms(),
            'meta_json' => json_encode(isset($payload['meta']) ? $payload['meta'] : []),
            'created_at' => ocg_now(),
        ]);

        $this->output
            ->set_content_type('application/json')
            ->set_status_header($httpCode)
            ->set_output(json_encode($payload));
        return;
    }

    private function latency_ms()
    {
        return (int) round((microtime(true) - $this->startedAt) * 1000);
    }
}
