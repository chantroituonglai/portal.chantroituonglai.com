<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Mautic_bridge_manage extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        if (!is_admin()) {
            access_denied('mautic_bridge');
        }

        $this->load->model('mautic_bridge/Mautic_bridge_model', 'mauticBridgeModel');
        $this->load->language('mautic_bridge/mautic_bridge');
    }

    public function index()
    {
        $data = $this->base_data('dashboard');
        $data['summary'] = $this->mauticBridgeModel->get_manage_summary();
        $this->load->view('mautic_bridge/manage/dashboard', $data);
    }

    public function queue()
    {
        if ($this->input->is_ajax_request()) {
            $this->json($this->mauticBridgeModel->get_queue_datatable());
        }

        $data = $this->base_data('queue');
        $this->load->view('mautic_bridge/manage/queue', $data);
    }

    public function mappings()
    {
        if ($this->input->is_ajax_request()) {
            $this->json($this->mauticBridgeModel->get_mappings_datatable());
        }

        $data = $this->base_data('mappings');
        $this->load->view('mautic_bridge/manage/mappings', $data);
    }

    public function campaigns()
    {
        if ($this->input->is_ajax_request()) {
            $this->json($this->mauticBridgeModel->get_campaigns_datatable());
        }

        $data = $this->base_data('campaigns');
        $this->load->view('mautic_bridge/manage/campaigns', $data);
    }

    public function logs()
    {
        if ($this->input->is_ajax_request()) {
            $this->json($this->mauticBridgeModel->get_logs_datatable());
        }

        $data = $this->base_data('logs');
        $this->load->view('mautic_bridge/manage/logs', $data);
    }

    public function backfill()
    {
        $data = $this->base_data('backfill');
        $data['summary'] = $this->mauticBridgeModel->get_manage_summary();
        $this->load->view('mautic_bridge/manage/backfill', $data);
    }

    public function google_import()
    {
        $data = $this->base_data('google_import');
        $data['defaults_ok'] = $this->mauticBridgeModel->google_import_defaults_ok();
        $this->load->view('mautic_bridge/manage/google_import', $data);
    }

    public function google_import_upload()
    {
        $this->json($this->mauticBridgeModel->create_google_import_job_from_upload('import_file'));
    }

    public function google_import_job($id)
    {
        $this->json($this->mauticBridgeModel->get_google_import_job_payload((int) $id));
    }

    public function google_import_process()
    {
        $jobId = (int) $this->input->post('job_id');
        $limit = (int) $this->input->post('limit');
        $this->json($this->mauticBridgeModel->process_google_import_job($jobId, $limit ?: 10));
    }

    public function health()
    {
        $this->json([
            'success' => true,
            'summary' => $this->mauticBridgeModel->get_manage_summary(),
        ]);
    }

    public function process_queue()
    {
        $ids = $this->input->post('ids');
        if (is_array($ids) && !empty($ids)) {
            $this->json($this->mauticBridgeModel->process_queue_ids($ids));
        }

        $limit = (int) $this->input->post('limit');
        $this->json($this->mauticBridgeModel->process_queue($limit ?: 25));
    }

    public function retry_queue()
    {
        $this->json($this->mauticBridgeModel->retry_queue($this->posted_ids()));
    }

    public function delete_queue()
    {
        $this->json($this->mauticBridgeModel->delete_queue($this->posted_ids()));
    }

    public function reset_mapping()
    {
        $this->json($this->mauticBridgeModel->reset_mapping($this->posted_ids()));
    }

    public function enqueue_resync()
    {
        $this->json($this->mauticBridgeModel->enqueue_resync($this->posted_ids()));
    }

    public function sync_campaigns()
    {
        $result = $this->mauticBridgeModel->sync_campaign_catalog();
        $result['success'] = !empty($result['ok']);
        $this->json($result);
    }

    public function enqueue_backfill()
    {
        $direction = (string) $this->input->post('direction', true);
        $limit = (int) $this->input->post('limit');
        $page = (int) $this->input->post('page');
        $this->json($this->mauticBridgeModel->enqueue_backfill($direction, $limit ?: 50, $page ?: 1));
    }

    public function projects($action = null, $id = null)
    {
        if ($action === 'create') {
            $this->project_create();
            return;
        }

        if (is_numeric($action)) {
            $this->project((int) $action);
            return;
        }

        $data = $this->base_data('projects');
        $data['summary'] = $this->mauticBridgeModel->get_project_hq_summary();
        $data['projects'] = $this->mauticBridgeModel->get_project_hq_list();
        $this->load->view('mautic_bridge/manage/projects', $data);
    }

    public function project_create()
    {
        if ($this->input->post()) {
            $result = $this->mauticBridgeModel->create_project_hq($this->input->post());
            if (!empty($result['success'])) {
                set_alert('success', $result['message']);
                redirect(admin_url('mautic_bridge_manage/projects/' . (int) $result['project_map_id']));
            }

            set_alert('danger', $result['message'] ?? 'Unable to save Project HQ.');
            redirect(admin_url('mautic_bridge_manage/projects/create'));
        }

        $data = $this->base_data('projects');
        $data['form_options'] = $this->mauticBridgeModel->get_project_hq_form_options();
        $this->load->view('mautic_bridge/manage/project_create', $data);
    }

    public function project($id)
    {
        $map = $this->mauticBridgeModel->get_project_hq_map((int) $id);
        if (!$map) {
            show_404();
        }

        $data = $this->base_data('projects');
        $data['map'] = $map;
        $data['objects'] = $this->mauticBridgeModel->get_project_hq_objects((int) $id);
        $data['logs'] = $this->mauticBridgeModel->get_project_hq_logs((int) $id);
        $data['templates'] = $this->mauticBridgeModel->get_project_hq_templates(true);
        $this->load->view('mautic_bridge/manage/project_view', $data);
    }

    public function project_templates()
    {
        $data = $this->base_data('project_templates');
        $data['templates'] = $this->mauticBridgeModel->get_project_hq_templates();
        $this->load->view('mautic_bridge/manage/project_templates', $data);
    }

    public function project_refresh()
    {
        $this->json($this->mauticBridgeModel->refresh_project_hq_snapshot((int) $this->input->post('id')));
    }

    public function project_generate_tasks()
    {
        $this->json($this->mauticBridgeModel->generate_project_hq_tasks(
            (int) $this->input->post('id'),
            (int) $this->input->post('template_id')
        ));
    }

    public function project_create_issue()
    {
        $this->json($this->mauticBridgeModel->create_project_hq_issue_task(
            (int) $this->input->post('id'),
            (string) $this->input->post('issue_key', true),
            (string) $this->input->post('title', true),
            (string) $this->input->post('description', true)
        ));
    }

    private function base_data(string $active): array
    {
        return [
            'title' => _l('mautic_bridge_sync_manager'),
            'active_tab' => $active,
            'settings' => $this->mauticBridgeModel->get_settings(),
        ];
    }

    private function posted_ids(): array
    {
        $ids = $this->input->post('ids');
        if (is_array($ids)) {
            return $ids;
        }

        $id = (int) $this->input->post('id');
        return $id > 0 ? [$id] : [];
    }

    private function json(array $payload): void
    {
        header('Content-Type: application/json');
        echo json_encode($payload);
        die;
    }
}
