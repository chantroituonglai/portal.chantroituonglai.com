<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Project_management_agent extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('url');
        $this->load->helper('project_management_agent');
        $this->load->model('projects_model');
        $this->load->model('project_management_agent_model');
    }

    public function index()
    {
        if (!has_permission('projects', '', 'view')) {
            access_denied('project_management_agent');
        }

        $data = [];
        $data['title'] = _l('pma_title_project_composer');
        $data['project_id'] = (int) $this->input->get('project_id');
        // Basic projects list for dropdown (exclude status=4 Completed if exists)
        try {
            $data['projects'] = $this->projects_model->get('', []);
        } catch (\Throwable $e) {
            $data['projects'] = [];
        }
        $this->load->view('project_management_agent/project_composer', $data);
    }

    /**
     * API: Create composition for a project
     */
    public function compose_project()
    {
        if (!has_permission('projects', '', 'create')) { ajax_access_denied(); }
        $project_id = (int) $this->input->post('project_id');
        if ($project_id <= 0) { echo json_encode(['success'=>false,'message'=>_l('pma_error_invalid_project_id')]); return; }
        try {
            $cid = $this->project_management_agent_model->create_composition($project_id);
            echo json_encode(['success'=>true,'composition_id'=>$cid]);
        } catch (\Throwable $e) {
            echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
        }
    }

    /**
     * API: Run AI breakdown for composition
     */
    public function ai_breakdown()
    {
        if (!has_permission('projects', '', 'create')) { ajax_access_denied(); }
        $composition_id = (int) $this->input->post('composition_id');
        if ($composition_id <= 0) { echo json_encode(['success'=>false,'message'=>_l('pma_error_invalid_composition_id')]); return; }
        try {
            $result = $this->project_management_agent_model->run_ai_breakdown($composition_id);
            echo json_encode(['success'=>true,'breakdown'=>$result]);
        } catch (\Throwable $e) {
            echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
        }
    }

    public function get_breakdown_status($composition_id)
    {
        if (!has_permission('projects', '', 'view')) { 
            echo json_encode(['success' => false, 'message' => 'Access denied']); 
            return; 
        }
        
        try {
            $composition = $this->project_management_agent_model->get_composition($composition_id);
            if (!$composition) {
                echo json_encode(['success' => false, 'message' => 'Composition not found']);
                return;
            }
            
            $response = [
                'success' => true,
                'status' => $composition['status'],
                'message' => $composition['status_message'] ?? ''
            ];
            
            if ($composition['status'] === 'ready') {
                $response['breakdown'] = json_decode($composition['ai_breakdown'], true);
            }
            
            echo json_encode($response);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }



    /**
     * API: Execute project clone using stored composition
     */
    public function clone_project()
    {
        if (!has_permission('projects', '', 'create')) { ajax_access_denied(); }
        $composition_id = (int) $this->input->post('composition_id');
        $clone_config = $this->input->post('clone_config');
        if ($composition_id <= 0 || empty($clone_config)) { echo json_encode(['success'=>false,'message'=>_l('pma_error_invalid_parameters')]); return; }
        if (is_string($clone_config)) {
            $dec = json_decode($clone_config, true);
            if (json_last_error() === JSON_ERROR_NONE) { $clone_config = $dec; }
        }
        if (!is_array($clone_config)) { $clone_config = []; }
        try {
            $newId = $this->project_management_agent_model->execute_clone($composition_id, $clone_config);
            echo json_encode(['success'=>true,'new_project_id'=>$newId]);
        } catch (\Throwable $e) {
            echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
        }
    }

    /**
     * API: Save user-provided input data to composition
     */
    public function save_input_data()
    {
        if (!has_permission('projects', '', 'create')) { ajax_access_denied(); }
        $composition_id = (int) $this->input->post('composition_id');
        $input_data = (string) $this->input->post('input_data');
        if ($composition_id <= 0 || $input_data === '') {
            echo json_encode(['success'=>false,'message'=>'Invalid parameters']);
            return;
        }
        $decoded = json_decode($input_data, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            echo json_encode(['success'=>false,'message'=>'Invalid input data format']);
            return;
        }
        try {
            $ok = $this->project_management_agent_model->save_input_data($composition_id, $decoded);
            echo json_encode(['success'=>(bool)$ok]);
        } catch (\Throwable $e) {
            echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
        }
    }
}
