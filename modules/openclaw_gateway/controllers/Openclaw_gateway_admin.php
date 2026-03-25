<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Openclaw_gateway_admin extends AdminController
{
    private $scopeKeys = ['sales', 'projects', 'tasks', 'tickets', 'clients', 'leads', 'staff', 'calendar', 'cron'];

    public function __construct()
    {
        parent::__construct();
        $this->load->model('openclaw_gateway/Openclaw_gateway_model', 'gateway_model');
    }

    public function index()
    {
        if (!is_admin()) {
            access_denied('openclaw_gateway');
        }

        $this->render_dashboard_page('overview');
    }

    public function bridge_settings()
    {
        if (!is_admin()) {
            access_denied('openclaw_gateway');
        }

        if ($this->input->method(true) === 'POST' && (string) $this->input->post('ocg_settings_submit') === '1') {
            $this->save_bridge_settings();
            set_alert('success', 'OpenClaw bridge settings updated');
            redirect(admin_url('openclaw_gateway/openclaw_gateway_admin/bridge_settings'));
        }

        $this->render_dashboard_page('bridge_settings');
    }

    public function scope_settings()
    {
        if (!is_admin()) {
            access_denied('openclaw_gateway');
        }

        if ($this->input->method(true) === 'POST' && (string) $this->input->post('ocg_settings_submit') === '1') {
            $this->save_bridge_settings();
            set_alert('success', 'OpenClaw scope settings updated');
            redirect(admin_url('openclaw_gateway/openclaw_gateway_admin/scope_settings'));
        }

        $this->render_dashboard_page('scope_settings');
    }

    public function pipeline_logs()
    {
        if (!is_admin()) {
            access_denied('openclaw_gateway');
        }

        $this->render_dashboard_page('pipeline_logs');
    }

    public function bridge_queue()
    {
        if (!is_admin()) {
            access_denied('openclaw_gateway');
        }

        $this->render_dashboard_page('bridge_queue');
    }

    public function gateway_logs()
    {
        if (!is_admin()) {
            access_denied('openclaw_gateway');
        }

        $this->render_dashboard_page('gateway_logs');
    }

    public function pipeline_table()
    {
        if (!is_admin()) {
            access_denied('openclaw_gateway');
        }
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $table = db_prefix() . 'openclaw_agent_pipeline_logs';
        if (!$this->db->table_exists($table)) {
            echo json_encode(['draw' => (int) $this->input->post('draw'), 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => []]);
            exit();
        }

        $aColumns = ['id', 'created_at', 'direction', 'status', 'agent_id', 'event_name', 'entity_type', 'entity_id', 'request_id', 'summary'];
        $result = data_tables_init($aColumns, 'id', $table, [], $this->build_pipeline_where(), ['id']);

        foreach ($result['rResult'] as $aRow) {
            $entity = trim(((string) ($aRow['entity_type'] ?? '')) . '#' . ((string) ($aRow['entity_id'] ?? '')), '#');
            $summary = (string) ($aRow['summary'] ?? '');
            if (mb_strlen($summary) > 180) {
                $summary = mb_substr($summary, 0, 180) . '...';
            }

            $row = [];
            $row[] = (int) $aRow['id'];
            $row[] = e($aRow['created_at']);
            $row[] = e($aRow['direction']);
            $row[] = e($aRow['status']);
            $row[] = e($aRow['agent_id']);
            $row[] = e($aRow['event_name']);
            $row[] = e($entity);
            $row[] = e($aRow['request_id']);
            $row[] = e($summary);
            $row[] = '<a href="#" class="btn btn-default btn-icon ocg-open-detail" data-type="pipeline" data-id="' . (int) $aRow['id'] . '"><i class="fa fa-eye"></i></a>';
            $result['output']['aaData'][] = $row;
        }

        echo json_encode($result['output']);
        exit();
    }

    public function bridge_table()
    {
        if (!is_admin()) {
            access_denied('openclaw_gateway');
        }
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $table = db_prefix() . 'openclaw_bridge_events';
        if (!$this->db->table_exists($table)) {
            echo json_encode(['draw' => (int) $this->input->post('draw'), 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => []]);
            exit();
        }

        $aColumns = ['id', 'created_at', 'event_name', 'module_name', 'action_name', 'status', 'attempts', 'http_code', 'last_error'];
        $result = data_tables_init($aColumns, 'id', $table, [], $this->build_bridge_where(), ['id']);

        foreach ($result['rResult'] as $aRow) {
            $lastError = (string) ($aRow['last_error'] ?? '');
            if (mb_strlen($lastError) > 120) {
                $lastError = mb_substr($lastError, 0, 120) . '...';
            }

            $row = [];
            $row[] = (int) $aRow['id'];
            $row[] = e($aRow['created_at']);
            $row[] = e($aRow['event_name']);
            $row[] = e($aRow['module_name']);
            $row[] = e($aRow['action_name']);
            $row[] = e($aRow['status']);
            $row[] = (int) ($aRow['attempts'] ?? 0);
            $row[] = (int) ($aRow['http_code'] ?? 0);
            $row[] = e($lastError);
            $row[] = '<a href="#" class="btn btn-default btn-icon ocg-open-detail" data-type="bridge" data-id="' . (int) $aRow['id'] . '"><i class="fa fa-eye"></i></a>';
            $result['output']['aaData'][] = $row;
        }

        echo json_encode($result['output']);
        exit();
    }

    public function gateway_table()
    {
        if (!is_admin()) {
            access_denied('openclaw_gateway');
        }
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $table = db_prefix() . 'openclaw_gateway_logs';
        if (!$this->db->table_exists($table)) {
            echo json_encode(['draw' => (int) $this->input->post('draw'), 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => []]);
            exit();
        }

        $aColumns = ['id', 'created_at', 'http_method', 'path', 'status', 'http_code', 'action_id', 'request_id', 'error_message'];
        $result = data_tables_init($aColumns, 'id', $table, [], $this->build_gateway_where(), ['id']);

        foreach ($result['rResult'] as $aRow) {
            $error = (string) ($aRow['error_message'] ?? '');
            if (mb_strlen($error) > 120) {
                $error = mb_substr($error, 0, 120) . '...';
            }

            $row = [];
            $row[] = (int) $aRow['id'];
            $row[] = e($aRow['created_at']);
            $row[] = e($aRow['http_method']);
            $row[] = e($aRow['path']);
            $row[] = e($aRow['status']);
            $row[] = (int) ($aRow['http_code'] ?? 0);
            $row[] = e($aRow['action_id']);
            $row[] = e($aRow['request_id']);
            $row[] = e($error);
            $row[] = '<a href="#" class="btn btn-default btn-icon ocg-open-detail" data-type="gateway" data-id="' . (int) $aRow['id'] . '"><i class="fa fa-eye"></i></a>';
            $result['output']['aaData'][] = $row;
        }

        echo json_encode($result['output']);
        exit();
    }

    public function log_detail($type = '', $id = 0)
    {
        if (!is_admin()) {
            access_denied('openclaw_gateway');
        }
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $type = strtolower(trim((string) $type));
        $id = (int) $id;
        if ($id <= 0 || !in_array($type, ['pipeline', 'bridge', 'gateway'], true)) {
            return $this->json_error('invalid_request', 400);
        }

        $row = $this->fetch_log_row($type, $id);
        if (!$row) {
            return $this->json_error('not_found', 404);
        }

        echo json_encode([
            'ok' => true,
            'type' => $type,
            'id' => $id,
            'row' => $row,
            'decoded' => [
                'payload' => $this->decode_json_maybe($row['payload_json'] ?? null),
                'meta' => $this->decode_json_maybe($row['meta_json'] ?? null),
                'params_masked' => $this->decode_json_maybe($row['params_masked'] ?? null),
            ],
        ]);
        exit();
    }

    private function render_dashboard_page($pageMode)
    {
        $titles = [
            'overview' => 'Agent Pipeline / Overview',
            'bridge_settings' => 'Bridge Settings',
            'scope_settings' => 'Scopes',
            'pipeline_logs' => 'Pipeline Logs',
            'bridge_queue' => 'Bridge Delivery Queue',
            'gateway_logs' => 'Gateway API Logs',
        ];

        $activeLogTabMap = [
            'overview' => 'pipeline',
            'bridge_settings' => 'bridge',
            'scope_settings' => 'bridge',
            'pipeline_logs' => 'pipeline',
            'bridge_queue' => 'bridge',
            'gateway_logs' => 'gateway',
        ];

        $data = [];
        $data['title'] = $titles[$pageMode] ?? 'Agent Pipeline / Agent Logs';
        $data['page_mode'] = $pageMode;
        $data['active_log_tab'] = $activeLogTabMap[$pageMode] ?? 'pipeline';
        $data['stats_24h'] = $this->gateway_model->pipeline_counts_24h();
        $data['overview_stats'] = $this->gateway_model->overview_stats(24);
        $data['recent_alerts'] = $this->gateway_model->recent_alerts(6);
        $data['bridge_settings'] = $this->read_bridge_settings();
        $data['scope_keys'] = $this->scopeKeys;
        $data['filters'] = [
            'direction' => trim((string) $this->input->get('direction')),
            'status' => trim((string) $this->input->get('status')),
            'agent_id' => trim((string) $this->input->get('agent_id')),
            'request_id' => trim((string) $this->input->get('request_id')),
            'event_name' => trim((string) $this->input->get('event_name')),
            'from' => trim((string) $this->input->get('from')),
            'to' => trim((string) $this->input->get('to')),
        ];

        $this->load->view('admin/pipeline', $data);
    }

    private function read_bridge_settings()
    {
        $settings = [
            'openclaw_bridge_enabled',
            'openclaw_bridge_include_payload',
            'openclaw_bridge_endpoint',
            'openclaw_bridge_agent',
            'openclaw_bridge_notify_agent',
            'openclaw_bridge_timeout_ms',
            'openclaw_bridge_retry_max',
            'openclaw_bridge_retry_delay_sec',
            'openclaw_bridge_group_id',
            'openclaw_bridge_auth_token',
            'openclaw_bridge_hmac_secret',
            'openclaw_bridge_payload_policy',
            'openclaw_bridge_event_allow_patterns',
            'openclaw_bridge_event_deny_patterns',
            'openclaw_bridge_payload_fields_json',
        ];

        foreach ($this->scopeKeys as $scopeKey) {
            $settings[] = 'openclaw_bridge_scope_' . $scopeKey;
        }

        $result = [];
        foreach ($settings as $key) {
            $result[$key] = get_option($key);
        }

        return $result;
    }

    private function save_bridge_settings()
    {
        $section = strtolower(trim((string) $this->input->post('ocg_settings_section')));
        $saveBridgeSection = $section === '' || $section === 'bridge';
        $saveScopeSection = $section === '' || $section === 'scopes';

        if ($saveBridgeSection) {
            $textKeys = [
                'openclaw_bridge_endpoint',
                'openclaw_bridge_auth_token',
                'openclaw_bridge_hmac_secret',
                'openclaw_bridge_agent',
                'openclaw_bridge_notify_agent',
                'openclaw_bridge_group_id',
            ];
            foreach ($textKeys as $key) {
                update_option($key, trim((string) $this->input->post($key)));
            }

            $policy = strtolower(trim((string) $this->input->post('openclaw_bridge_payload_policy')));
            if (!in_array($policy, ['full', 'summary', 'off'], true)) {
                $policy = 'full';
            }
            update_option('openclaw_bridge_payload_policy', $policy);
            update_option('openclaw_bridge_timeout_ms', max(1, (int) $this->input->post('openclaw_bridge_timeout_ms')) ?: 12000);
            update_option('openclaw_bridge_retry_max', max(1, (int) $this->input->post('openclaw_bridge_retry_max')) ?: 3);
            update_option('openclaw_bridge_retry_delay_sec', max(1, (int) $this->input->post('openclaw_bridge_retry_delay_sec')) ?: 60);
            update_option('openclaw_bridge_enabled', $this->input->post('openclaw_bridge_enabled') ? 1 : 0);
            update_option('openclaw_bridge_include_payload', $this->input->post('openclaw_bridge_include_payload') ? 1 : 0);
        }

        if ($saveScopeSection) {
            $textKeys = [
                'openclaw_bridge_event_allow_patterns',
                'openclaw_bridge_event_deny_patterns',
                'openclaw_bridge_payload_fields_json',
            ];
            foreach ($textKeys as $key) {
                update_option($key, trim((string) $this->input->post($key)));
            }

            foreach ($this->scopeKeys as $scopeKey) {
                update_option('openclaw_bridge_scope_' . $scopeKey, $this->input->post('openclaw_bridge_scope_' . $scopeKey) ? 1 : 0);
            }
        }
    }

    private function build_pipeline_where()
    {
        $where = [];
        foreach (['direction', 'status'] as $key) {
            $value = $this->post_filter($key);
            if ($value !== '') {
                $where[] = 'AND ' . $key . ' = "' . $this->db->escape_str($value) . '"';
            }
        }
        foreach (['agent_id', 'request_id', 'event_name'] as $key) {
            $value = $this->post_filter($key);
            if ($value !== '') {
                $where[] = 'AND ' . $key . ' LIKE "%' . $this->db->escape_like_str($value) . '%"';
            }
        }
        return $this->append_date_filters($where);
    }

    private function build_bridge_where()
    {
        $where = [];
        $status = $this->post_filter('status');
        if ($status !== '') {
            $where[] = 'AND status LIKE "%' . $this->db->escape_like_str($status) . '%"';
        }
        foreach (['event_name', 'agent_id'] as $key) {
            $value = $this->post_filter($key);
            if ($value !== '') {
                $column = $key === 'agent_id' ? 'action_name' : $key;
                $where[] = 'AND ' . $column . ' LIKE "%' . $this->db->escape_like_str($value) . '%"';
            }
        }
        return $this->append_date_filters($where);
    }

    private function build_gateway_where()
    {
        $where = [];
        $status = $this->post_filter('status');
        if ($status !== '') {
            $where[] = 'AND status LIKE "%' . $this->db->escape_like_str($status) . '%"';
        }
        foreach (['request_id', 'event_name', 'agent_id'] as $key) {
            $value = $this->post_filter($key);
            if ($value !== '') {
                $column = $key === 'event_name' ? 'action_id' : ($key === 'agent_id' ? 'path' : $key);
                $where[] = 'AND ' . $column . ' LIKE "%' . $this->db->escape_like_str($value) . '%"';
            }
        }
        return $this->append_date_filters($where);
    }

    private function append_date_filters($where)
    {
        $from = $this->post_filter('from');
        $to = $this->post_filter('to');
        if ($from !== '') {
            $where[] = 'AND created_at >= "' . $this->db->escape_str($from) . ' 00:00:00"';
        }
        if ($to !== '') {
            $where[] = 'AND created_at <= "' . $this->db->escape_str($to) . ' 23:59:59"';
        }

        return $where;
    }

    private function post_filter($key)
    {
        return trim((string) $this->input->post($key));
    }

    private function fetch_log_row($type, $id)
    {
        $tables = [
            'pipeline' => db_prefix() . 'openclaw_agent_pipeline_logs',
            'bridge' => db_prefix() . 'openclaw_bridge_events',
            'gateway' => db_prefix() . 'openclaw_gateway_logs',
        ];
        $table = $tables[$type] ?? '';
        if ($table === '' || !$this->db->table_exists($table)) {
            return null;
        }

        return $this->db->where('id', $id)->get($table)->row_array();
    }

    private function decode_json_maybe($value)
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        $decoded = json_decode($value, true);
        return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
    }

    private function json_error($error, $status = 400)
    {
        $this->output->set_status_header($status);
        echo json_encode(['ok' => false, 'error' => $error]);
        exit();
    }
}
