<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Mautic_bridge extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('mautic_bridge/Mautic_bridge_model', 'mauticBridgeModel');
        $this->load->language('mautic_bridge/mautic_bridge');
    }

    public function index()
    {
        if (!is_admin()) {
            access_denied('mautic_bridge');
        }

        if ($this->input->post()) {
            $this->mauticBridgeModel->save_settings([
                'enabled' => $this->input->post('mautic_bridge_enabled') ? 1 : 0,
                'dry_run' => $this->input->post('mautic_bridge_dry_run') ? 1 : 0,
                'base_url' => $this->input->post('mautic_bridge_base_url', true),
                'oauth_client_id' => $this->input->post('mautic_bridge_oauth_client_id', true),
                'oauth_client_secret' => $this->input->post('mautic_bridge_oauth_client_secret', true),
                'webhook_secret' => $this->input->post('mautic_bridge_webhook_secret', true),
                'default_lead_source' => $this->input->post('mautic_bridge_default_lead_source', true),
                'default_lead_status' => $this->input->post('mautic_bridge_default_lead_status', true),
                'default_assigned_staff' => $this->input->post('mautic_bridge_default_assigned_staff', true),
                'timeout' => $this->input->post('mautic_bridge_timeout', true),
                'retry_max' => $this->input->post('mautic_bridge_retry_max', true),
                'delete_remote' => $this->input->post('mautic_bridge_delete_remote') ? 1 : 0,
            ]);
            set_alert('success', _l('mautic_bridge_settings_saved'));
            redirect(admin_url('mautic_bridge'));
        }

        $this->load->model('leads_model');
        $this->load->model('staff_model');

        $data['settings'] = $this->mauticBridgeModel->get_settings();
        $data['lead_sources'] = $this->leads_model->get_source();
        $data['lead_statuses'] = $this->leads_model->get_status();
        $data['staff_members'] = $this->staff_model->get('', ['active' => 1]);
        $data['title'] = _l('mautic_bridge_settings');
        $this->load->view('mautic_bridge/settings', $data);
    }

    public function test_connection()
    {
        if (!is_admin()) {
            access_denied('mautic_bridge');
        }

        $response = $this->mauticBridgeModel->call_mautic_api('contacts', 'GET', ['limit' => 1]);
        echo json_encode([
            'success' => (bool) $response['ok'],
            'message' => $response['message'],
            'http_code' => $response['http_code'] ?? null,
        ]);
        die;
    }

    public function process_queue()
    {
        if (!is_admin()) {
            access_denied('mautic_bridge');
        }
        $limit = (int) $this->input->get('limit');
        echo json_encode($this->mauticBridgeModel->process_queue($limit ?: 25));
        die;
    }

    public function dry_run_preview()
    {
        if (!is_admin()) {
            access_denied('mautic_bridge');
        }

        echo json_encode([
            'success' => true,
            'preview' => $this->mauticBridgeModel->dry_run_preview(),
        ]);
        die;
    }

    public function reconcile()
    {
        if (!is_admin()) {
            access_denied('mautic_bridge');
        }
        $limit = (int) $this->input->get('limit');
        echo json_encode($this->mauticBridgeModel->reconcile_contacts($limit ?: 50));
        die;
    }

    public function manage_index()
    {
        if (!is_admin()) {
            access_denied('mautic_bridge');
        }

        $data = $this->manage_base_data('dashboard');
        $data['summary'] = $this->mauticBridgeModel->get_manage_summary();
        $this->load->view('mautic_bridge/manage/dashboard', $data);
    }

    public function manage_queue()
    {
        if (!is_admin()) {
            access_denied('mautic_bridge');
        }

        if ($this->input->is_ajax_request()) {
            $this->json_response($this->mauticBridgeModel->get_queue_datatable());
            return;
        }

        $data = $this->manage_base_data('queue');
        $this->load->view('mautic_bridge/manage/queue', $data);
    }

    public function manage_mappings()
    {
        if (!is_admin()) {
            access_denied('mautic_bridge');
        }

        if ($this->input->is_ajax_request()) {
            $this->json_response($this->mauticBridgeModel->get_mappings_datatable());
            return;
        }

        $data = $this->manage_base_data('mappings');
        $this->load->view('mautic_bridge/manage/mappings', $data);
    }

    public function manage_campaigns()
    {
        if (!is_admin()) {
            access_denied('mautic_bridge');
        }

        if ($this->input->is_ajax_request()) {
            $this->json_response($this->mauticBridgeModel->get_campaigns_datatable());
            return;
        }

        $data = $this->manage_base_data('campaigns');
        $this->load->view('mautic_bridge/manage/campaigns', $data);
    }

    public function manage_logs()
    {
        if (!is_admin()) {
            access_denied('mautic_bridge');
        }

        if ($this->input->is_ajax_request()) {
            $this->json_response($this->mauticBridgeModel->get_logs_datatable());
            return;
        }

        $data = $this->manage_base_data('logs');
        $this->load->view('mautic_bridge/manage/logs', $data);
    }

    public function manage_backfill()
    {
        if (!is_admin()) {
            access_denied('mautic_bridge');
        }

        $data = $this->manage_base_data('backfill');
        $data['summary'] = $this->mauticBridgeModel->get_manage_summary();
        $this->load->view('mautic_bridge/manage/backfill', $data);
    }

    public function manage_google_import()
    {
        if (!is_admin()) {
            access_denied('mautic_bridge');
        }

        $data = $this->manage_base_data('google_import');
        $data['defaults_ok'] = $this->mauticBridgeModel->google_import_defaults_ok();
        $this->load->view('mautic_bridge/manage/google_import', $data);
    }

    public function manage_google_import_upload()
    {
        if (!is_admin()) {
            access_denied('mautic_bridge');
        }

        $this->json_response($this->mauticBridgeModel->create_google_import_job_from_upload('import_file'));
    }

    public function manage_google_import_job($id)
    {
        if (!is_admin()) {
            access_denied('mautic_bridge');
        }

        $this->json_response($this->mauticBridgeModel->get_google_import_job_payload((int) $id));
    }

    public function manage_google_import_process()
    {
        if (!is_admin()) {
            access_denied('mautic_bridge');
        }

        $jobId = (int) $this->input->post('job_id');
        $limit = (int) $this->input->post('limit');
        $this->json_response($this->mauticBridgeModel->process_google_import_job($jobId, $limit ?: 10));
    }

    public function manage_process_queue()
    {
        if (!is_admin()) {
            access_denied('mautic_bridge');
        }

        $ids = $this->input->post('ids');
        if (is_array($ids) && !empty($ids)) {
            $this->json_response($this->mauticBridgeModel->process_queue_ids($ids));
            return;
        }

        $limit = (int) $this->input->post('limit');
        $this->json_response($this->mauticBridgeModel->process_queue($limit ?: 25));
    }

    public function manage_retry_queue()
    {
        if (!is_admin()) {
            access_denied('mautic_bridge');
        }

        $this->json_response($this->mauticBridgeModel->retry_queue($this->posted_manage_ids()));
    }

    public function manage_delete_queue()
    {
        if (!is_admin()) {
            access_denied('mautic_bridge');
        }

        $this->json_response($this->mauticBridgeModel->delete_queue($this->posted_manage_ids()));
    }

    public function manage_reset_mapping()
    {
        if (!is_admin()) {
            access_denied('mautic_bridge');
        }

        $this->json_response($this->mauticBridgeModel->reset_mapping($this->posted_manage_ids()));
    }

    public function manage_enqueue_resync()
    {
        if (!is_admin()) {
            access_denied('mautic_bridge');
        }

        $this->json_response($this->mauticBridgeModel->enqueue_resync($this->posted_manage_ids()));
    }

    public function manage_sync_campaigns()
    {
        if (!is_admin()) {
            access_denied('mautic_bridge');
        }

        $result = $this->mauticBridgeModel->sync_campaign_catalog();
        $result['success'] = !empty($result['ok']);
        $this->json_response($result);
    }

    public function manage_enqueue_backfill()
    {
        if (!is_admin()) {
            access_denied('mautic_bridge');
        }

        $direction = (string) $this->input->post('direction', true);
        $limit = (int) $this->input->post('limit');
        $page = (int) $this->input->post('page');
        $this->json_response($this->mauticBridgeModel->enqueue_backfill($direction, $limit ?: 50, $page ?: 1));
    }

    public function manage_projects()
    {
        if (!is_admin()) {
            access_denied('mautic_bridge');
        }

        $data = $this->manage_base_data('projects');
        $data['summary'] = $this->mauticBridgeModel->get_project_hq_summary();
        $data['projects'] = $this->mauticBridgeModel->get_project_hq_list();
        $this->load->view('mautic_bridge/manage/projects', $data);
    }

    public function manage_project_create()
    {
        if (!is_admin()) {
            access_denied('mautic_bridge');
        }

        if ($this->input->post()) {
            $result = $this->mauticBridgeModel->create_project_hq($this->input->post());
            if (!empty($result['success'])) {
                set_alert('success', $result['message']);
                redirect(admin_url('mautic_bridge_manage/projects/' . (int) $result['project_map_id']));
            }

            set_alert('danger', $result['message'] ?? 'Unable to save Project HQ.');
            redirect(admin_url('mautic_bridge_manage/projects/create'));
        }

        $data = $this->manage_base_data('projects');
        $data['form_options'] = $this->mauticBridgeModel->get_project_hq_form_options();
        $this->load->view('mautic_bridge/manage/project_create', $data);
    }

    public function manage_project_view($id)
    {
        if (!is_admin()) {
            access_denied('mautic_bridge');
        }

        $map = $this->mauticBridgeModel->get_project_hq_map((int) $id);
        if (!$map) {
            show_404();
        }

        $data = $this->manage_base_data('projects');
        $data['map'] = $map;
        $data['objects'] = $this->mauticBridgeModel->get_project_hq_objects((int) $id);
        $data['logs'] = $this->mauticBridgeModel->get_project_hq_logs((int) $id);
        $data['templates'] = $this->mauticBridgeModel->get_project_hq_templates(true);
        $this->load->view('mautic_bridge/manage/project_view', $data);
    }

    public function manage_project_templates()
    {
        if (!is_admin()) {
            access_denied('mautic_bridge');
        }

        $data = $this->manage_base_data('project_templates');
        $data['templates'] = $this->mauticBridgeModel->get_project_hq_templates();
        $this->load->view('mautic_bridge/manage/project_templates', $data);
    }

    public function manage_project_refresh()
    {
        if (!is_admin()) {
            access_denied('mautic_bridge');
        }

        $this->json_response($this->mauticBridgeModel->refresh_project_hq_snapshot((int) $this->input->post('id')));
    }

    public function manage_project_generate_tasks()
    {
        if (!is_admin()) {
            access_denied('mautic_bridge');
        }

        $this->json_response($this->mauticBridgeModel->generate_project_hq_tasks(
            (int) $this->input->post('id'),
            (int) $this->input->post('template_id')
        ));
    }

    public function manage_project_create_issue()
    {
        if (!is_admin()) {
            access_denied('mautic_bridge');
        }

        $this->json_response($this->mauticBridgeModel->create_project_hq_issue_task(
            (int) $this->input->post('id'),
            (string) $this->input->post('issue_key', true),
            (string) $this->input->post('title', true),
            (string) $this->input->post('description', true)
        ));
    }

    private function manage_base_data(string $active): array
    {
        return [
            'title' => _l('mautic_bridge_sync_manager'),
            'active_tab' => $active,
            'settings' => $this->mauticBridgeModel->get_settings(),
        ];
    }

    private function posted_manage_ids(): array
    {
        $ids = $this->input->post('ids');
        if (is_array($ids)) {
            return $ids;
        }

        $id = (int) $this->input->post('id');
        return $id > 0 ? [$id] : [];
    }

    private function json_response(array $payload): void
    {
        header('Content-Type: application/json');
        echo json_encode($payload);
        die;
    }
}
