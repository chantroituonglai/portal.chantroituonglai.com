<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Chatpion_bridge extends AdminController
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('chatpion_bridge/chatpion_bridge_model', 'chatpionBridgeModel');
        $this->load->model('chatpion_bridge/chatpion_bridge_task_model', 'chatpionBridgeTaskModel');
        $this->load->language('chatpion_bridge/chatpion_bridge');
    }

    public function index()
    {
        if (!is_admin()) {
            access_denied('chatpion_bridge');
        }

        if ($this->input->post()) {
            $data = $this->input->post(null, true);
            $settings = [
                'base_url'           => trim($data['chatpion_bridge_base_url'] ?? ''),
                'api_key'            => trim($data['chatpion_bridge_api_key'] ?? ''),
                'api_timeout'        => (int) ($data['chatpion_bridge_api_timeout'] ?? 30),
                'enable_logging'     => isset($data['chatpion_bridge_enable_logging']) ? 1 : 0,
                'workspace_template' => $data['chatpion_bridge_default_workspace_template'] ?? '',
            ];

            $this->chatpionBridgeModel->save_global_settings($settings);

            set_alert('success', _l('chatpion_bridge_settings_saved'));
            redirect(admin_url('chatpion_bridge'));
        }

        $data['settings'] = $this->chatpionBridgeModel->get_global_settings();
        $data['title']    = _l('chatpion_bridge_settings_heading');

        $this->load->view('chatpion_bridge/settings', $data);
    }

    public function test_connection()
    {
        if (!is_admin()) {
            access_denied('chatpion_bridge');
        }

        if (! $this->input->is_ajax_request()) {
            show_404();
        }

        if (!staff_can('view', 'tasks') && !staff_can('view_own', 'tasks')) {
            access_denied('tasks');
        }

        $baseUrl = $this->input->post('base_url', true);
        $apiKey  = $this->input->post('api_key', true);
        $timeout = (int) $this->input->post('timeout', true);

        if ($timeout <= 0) {
            $timeout = (int) get_option('chatpion_bridge_api_timeout') ?: 30;
        }

        $result = $this->chatpionBridgeModel->test_connection($baseUrl, $apiKey, $timeout);

        echo json_encode($result);
        die;
    }

    public function campaigns()
    {
        $this->assert_tasks_view_permission();

        $params = [
            'page'        => $this->input->get('page') ?: 1,
            'limit'       => $this->input->get('limit') ?: 20,
            'search'      => $this->input->get('search', true),
            'status'      => $this->input->get('status', true),
            'account_id'  => $this->input->get('account_id', true),
            'owner_user_id' => $this->input->get('owner_user_id', true),
            'media_type'  => $this->input->get('media_type', true) ?: 'all',
        ];

        if (isset($params['media_type'])) {
            $mediaType = strtolower(trim((string) $params['media_type']));
            if (!in_array($mediaType, ['facebook', 'instagram', 'all'], true)) {
                $mediaType = 'all';
            }
            $params['media_type'] = $mediaType;
        }

        $filteredParams = array_filter($params, function ($value) {
            return $value !== null && $value !== '';
        });

        $response = $this->chatpionBridgeModel->call_chatpion_api('instagram_campaigns', $filteredParams);

        echo json_encode($response['body'] ?? [
            'status'  => 'error',
            'message' => _l('chatpion_bridge_unexpected_error'),
        ]);
        die;
    }

    public function campaign($campaignId)
    {
        $this->assert_tasks_view_permission();

        $mediaType = $this->input->get('media_type', true) ?: 'all';
        $mediaType = strtolower(trim((string) $mediaType));
        if (!in_array($mediaType, ['facebook', 'instagram', 'all'], true)) {
            $mediaType = 'all';
        }
        $data = $this->chatpionBridgeModel->fetch_campaign_status($campaignId, $mediaType);
        if (! $data) {
            echo json_encode([
                'status'  => 'error',
                'message' => _l('chatpion_bridge_campaign_not_found'),
            ]);
            die;
        }

        echo json_encode([
            'status' => 'success',
            'data'   => $data,
        ]);
        die;
    }

    public function link_task()
    {
        $this->assert_tasks_edit_permission();

        $taskId     = (int) $this->input->post('task_id');
        $campaignId = trim($this->input->post('campaign_id', true));
        $accountId  = $this->input->post('account_id', true);
        $mediaType  = $this->input->post('media_type', true);
        if ($mediaType !== null) {
            $mediaType = strtolower(trim((string) $mediaType));
            if (!in_array($mediaType, ['facebook', 'instagram'], true)) {
                $mediaType = null;
            }
        }

        if ($taskId <= 0 || $campaignId === '') {
            echo json_encode(['success' => false, 'message' => _l('chatpion_bridge_invalid_parameters')]);
            die;
        }

        $payload = [
            'campaign_id' => $campaignId,
            'account_id'  => $accountId,
            'media_type'  => $mediaType ?: null,
            'created_by'  => get_staff_user_id(),
        ];

        $this->chatpionBridgeTaskModel->upsert_task_link($taskId, $payload);
        $link = $this->chatpionBridgeTaskModel->get_task_link($taskId);

        if ($link) {
            $campaignData = $this->chatpionBridgeModel->fetch_campaign_status(
                $link['campaign_id'],
                $link['media_type'] ?? 'all'
            );
            if ($campaignData) {
                $this->chatpionBridgeModel->sync_campaign_status($taskId, $link, $campaignData);
                $link = $this->chatpionBridgeTaskModel->get_task_link($taskId);
            }
        }

        echo json_encode([
            'success' => true,
            'data'    => $this->chatpionBridgeModel->format_task_link($link),
        ]);
        die;
    }

    public function unlink_task($taskId)
    {
        $this->assert_tasks_edit_permission();

        $taskId = (int) $taskId;
        if ($taskId <= 0) {
            echo json_encode(['success' => false, 'message' => _l('chatpion_bridge_invalid_parameters')]);
            die;
        }

        $this->chatpionBridgeTaskModel->delete_task_link($taskId);

        echo json_encode(['success' => true]);
        die;
    }

    public function refresh_task($taskId)
    {
        $this->assert_tasks_view_permission();

        $taskId = (int) $taskId;
        if ($taskId <= 0) {
            echo json_encode(['success' => false, 'message' => _l('chatpion_bridge_invalid_parameters')]);
            die;
        }

        $link = $this->chatpionBridgeTaskModel->get_task_link($taskId);
        if (! $link) {
            echo json_encode(['success' => false, 'message' => _l('chatpion_bridge_campaign_not_linked')]);
            die;
        }

        $campaignData = $this->chatpionBridgeModel->fetch_campaign_status(
            $link['campaign_id'],
            $link['media_type'] ?? 'all'
        );
        if (! $campaignData) {
            echo json_encode(['success' => false, 'message' => _l('chatpion_bridge_campaign_not_found')]);
            die;
        }

        $this->chatpionBridgeModel->sync_campaign_status($taskId, $link, $campaignData);
        $updated = $this->chatpionBridgeTaskModel->get_task_link($taskId);

        echo json_encode([
            'success' => true,
            'data'    => $this->chatpionBridgeModel->format_task_link($updated),
        ]);
        die;
    }

    public function workspace($taskId)
    {
        $this->assert_tasks_edit_permission();

        $taskId = (int) $taskId;
        if ($taskId <= 0) {
            echo json_encode(['success' => false, 'message' => _l('chatpion_bridge_invalid_parameters')]);
            die;
        }

        $payload = json_decode($this->input->raw_input_stream, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($payload)) {
            $payload = $this->input->post(null, false);
        }

        $workspace = $payload;
        if (is_array($workspace) && isset($workspace['workspace'])) {
            $workspace = $workspace['workspace'];
        }

        $result = $this->chatpionBridgeTaskModel->update_workspace_only($taskId, $workspace);
        $link   = $this->chatpionBridgeTaskModel->get_task_link($taskId);

        echo json_encode([
            'success' => (bool) $result,
            'data'    => $this->chatpionBridgeModel->format_task_link($link),
        ]);
        die;
    }

    protected function assert_tasks_view_permission(): void
    {
        if (staff_cant('view', 'tasks') && staff_cant('view_own', 'tasks')) {
            access_denied('tasks');
        }
    }

    protected function assert_tasks_edit_permission(): void
    {
        if (staff_cant('edit', 'tasks') && staff_cant('edit_own', 'tasks')) {
            access_denied('tasks');
        }
    }
}
