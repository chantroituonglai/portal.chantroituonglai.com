<?php 
defined('BASEPATH') or exit('No direct script access allowed');

class Action_types extends Admin_controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Action_type_model');
    }

    public function index()
    {
        if (!has_permission('topics', '', 'view')) {
            access_denied('topics');
        }

        $data['title'] = _l('action_types');
        
        // Lấy tất cả action types kèm số lượng states
        $data['action_types'] = $this->Action_type_model->get_action_types_with_states();
        
        $this->load->view('action_types/index', $data);
    }

    public function create()
    {
        if (!has_permission('topics', '', 'create')) {
            access_denied('topics');
        }

        if ($this->input->post()) {
            $data = $this->input->post();
            $result = $this->Action_type_model->add_action_type($data);
            
            if ($result['success']) {
                set_alert('success', _l('action_type_added'));
            } else {
                set_alert('warning', $result['message']);
            }
            redirect(admin_url('topics/action_types'));
        }

        $data['title'] = _l('add_new_action_type');
        $this->load->view('action_types/create', $data);
    }

    public function edit($id)
    {
        if (!has_permission('topics', '', 'edit')) {
            access_denied('topics');
        }

        if ($this->input->post()) {
            $data = $this->input->post();
            
            // Validate không cho chọn chính nó làm parent
            if (!empty($data['parent_id']) && $data['parent_id'] == $id) {
                set_alert('warning', _l('action_type_cannot_be_own_parent'));
                redirect(admin_url('topics/action_types/edit/' . $id));
            }
            
            // Validate không cho tạo circular reference
            if (!empty($data['parent_id']) && $this->Action_type_model->is_child($data['parent_id'], $id)) {
                set_alert('warning', _l('action_type_circular_reference'));
                redirect(admin_url('topics/action_types/edit/' . $id));
            }

            // Lấy thông tin hiện tại để kiểm tra parent_id có thay đổi không
            $current_type = $this->Action_type_model->get_action_type($id);
            $parent_changed = ($current_type->parent_id != $data['parent_id']);

            // Nếu parent_id thay đổi, không lấy position từ form
            if ($parent_changed) {
                unset($data['position']); // Position sẽ được tính lại trong model
            }

            $success = $this->Action_type_model->update_action_type($id, $data);
            if ($success) {
                if ($parent_changed) {
                    set_alert('success', _l('parent_type_changed'));
                } else {
                    set_alert('success', _l('updated_successfully', _l('action_type')));
                }
            }
            redirect(admin_url('topics/action_types'));
        }

        $data['action_type'] = $this->Action_type_model->get_action_type($id);
        if (!$data['action_type']) {
            show_404();
        }
        
        $data['title'] = _l('edit_action_type');
        $this->load->view('action_types/edit', $data);
    }

    public function delete($id)
    {
        if (!has_permission('topics', '', 'delete')) {
            access_denied('topics');
        }

        if ($this->Action_type_model->delete_action_type($id)) {
            set_alert('success', _l('deleted', _l('action_type')));
        }
        redirect(admin_url('topics/action_types'));
    }

    public function table()
    {
        if (!has_permission('topics', '', 'view')) {
            ajax_access_denied();
        }
        
        $this->load->model('Action_state_model');
        $this->load->model('Action_type_model');
        
        $this->app->get_table_data(module_views_path('topics', 'includes/action_types_table'));
    }

    public function reorder()
    {
        if (!has_permission('topics', '', 'edit')) {
            ajax_access_denied();
        }

        $positions = $this->input->post('positions');
        if ($this->Action_type_model->reorder_positions($positions)) {
            echo json_encode([
                'success' => true,
                'message' => _l('positions_updated')
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => _l('error_updating_positions')
            ]);
        }
    }
} 