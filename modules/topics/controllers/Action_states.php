<?php defined('BASEPATH') or exit('No direct script access allowed');

class Action_states extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Action_state_model');
    }

    public function index()
    {
        if (!has_permission('topics', '', 'view')) {
            access_denied('topics');
        }

        $data['title'] = _l('action_states');
        $data['action_states'] = $this->Action_state_model->get_all_action_states();
        $this->load->view('action_states/index', $data);
    }

    public function create()
    {
        if (!has_permission('topics', '', 'create')) {
            access_denied('topics');
        }

        $this->load->model('Action_type_model');
        
        if ($this->input->post()) {
            $data = $this->input->post();
            $id = $this->Action_state_model->add_action_state($data);
            
            if ($id) {
                set_alert('success', _l('action_state_added'));
                redirect(admin_url('topics/action_states'));
            }
        }

        $data['title'] = _l('add_new_action_state');
        $data['action_types'] = $this->Action_type_model->get_all_action_types();
        $this->load->view('action_states/create', $data);
    }

    public function edit($id)
    {
        if (!has_permission('topics', '', 'edit')) {
            access_denied('topics');
        }

        $this->load->model('Action_type_model');
        
        if ($this->input->post()) {
            $data = $this->input->post();
            
            $this->load->library('form_validation');
            
            $this->form_validation->set_rules([
                [
                    'field' => 'name',
                    'label' => _l('action_state_name'),
                    'rules' => 'required|trim'
                ]
            ]);
            
            if ($this->form_validation->run()) {
                // Log before update
                log_activity('Attempting to update action state: ' . $id . ' with data: ' . json_encode($data));
                
                // Chỉ lấy các trường có dữ liệu
                $update_data = [];
                
                if (!empty($data['name'])) {
                    $update_data['name'] = $data['name'];
                }
                
                if (!empty($data['action_type_code'])) {
                    $update_data['action_type_code'] = $data['action_type_code'];
                }
                
                if (!empty($data['color'])) {
                    $update_data['color'] = $data['color'];
                }
                
                if (isset($data['position']) && $data['position'] !== '') {
                    $update_data['position'] = $data['position'];
                }
                
                // Lấy action_state_code hiện tại từ database
                $current_state = $this->Action_state_model->get_action_state($id);
                $update_data['action_state_code'] = $current_state->action_state_code;
                
                // Luôn thêm dateupdated
                $update_data['dateupdated'] = date('Y-m-d H:i:s');
                
                $success = $this->Action_state_model->update_action_state($id, $update_data);
                
                // Log after update
                log_activity('Update action state result: ' . ($success ? 'Success' : 'Failed'));
                
                if ($success) {
                    set_alert('success', _l('action_state_updated'));
                    redirect(admin_url('topics/action_states'));
                } else {
                    set_alert('danger', _l('action_state_update_failed'));
                }
            }
        }

        $data['action_state'] = $this->Action_state_model->get_action_state($id);
        if (!$data['action_state']) {
            show_404();
        }
        
        $data['title'] = _l('edit_action_state');
        $data['action_types'] = $this->Action_type_model->get_all_action_types();
        $this->load->view('action_states/edit', $data);
    }

    public function delete($id)
    {
        if (!has_permission('topics', '', 'delete')) {
            access_denied('topics');
        }

        // Check if action state is in use
        if ($this->Action_state_model->is_action_state_in_use($id)) {
            set_alert('warning', _l('action_state_in_use'));
            redirect(admin_url('topics/action_states'));
        }

        if ($this->Action_state_model->delete_action_state($id)) {
            set_alert('success', _l('deleted_successfully'));
        }

        redirect(admin_url('topics/action_states'));
    }

    public function get_by_type($action_type_id)
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }
        
        $states = $this->Action_state_model->get_states_by_type($action_type_id);
        echo json_encode($states);
        die();
    }

    public function table()
        {
            if (!has_permission('topics', '', 'view')) {
                ajax_access_denied();
            }

            $this->app->get_table_data(module_views_path('topics', 'includes/action_states_table'));
  
        }

    public function reorder()
    {
        if (!has_permission('topics', '', 'edit')) {
            ajax_access_denied();
        }

        $positions = $this->input->post('positions');
        if ($this->Action_state_model->reorder_positions($positions)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false]);
        }
    }

    public function get_type_states($type_id)
    {
        if (!has_permission('topics', '', 'view')) {
            ajax_access_denied();
        }

        // Set content type header
        header('Content-Type: application/json');

        $states = $this->Action_state_model->get_states_by_type_id($type_id);
        
        // Return data in DataTables expected format
        echo json_encode([
            'draw' => $this->input->get('draw'), // Value from DataTables
            'recordsTotal' => count($states),
            'recordsFiltered' => count($states),
            'data' => $states
        ]);
        die();
    }

    public function toggle_valid_data($id)
    {
        if (!has_permission('topics', '', 'edit')) {
            ajax_access_denied();
        }

        $success = $this->Action_state_model->toggle_valid_data($id);

        $response = [
            'success' => $success,
            'message' => $success ? _l('updated_successfully') : _l('update_failed')
        ];

        echo json_encode($response);
    }
} 