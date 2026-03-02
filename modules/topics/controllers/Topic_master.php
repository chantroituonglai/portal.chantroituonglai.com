<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Topic_master extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Topic_master_model');
        $this->load->model('Topic_target_model');
    }

    public function index()
    {
        if (!has_permission('topics', '', 'view')) {
            access_denied('topics');
        }

        $data['title'] = _l('topic_master_list');
        $this->load->view('topic_master/index', $data);
    }

    public function table()
    {
        if (!has_permission('topics', '', 'view')) {
            ajax_access_denied();
        }

        $this->app->get_table_data(module_views_path('topics', 'includes/topic_master_table'));
    }

    public function view($id)
    {
        if (!has_permission('topics', '', 'view')) {
            access_denied('topics');
        }
        redirect(admin_url('topics/detail/' . $id));
    }

    public function create()
    {
        if (!has_permission('topics', '', 'create')) {
            access_denied('topics');
        }

        if ($this->input->post()) {
            $data = $this->input->post();
            
            if (empty($data['topicid'])) {
                $data['topicid'] = $this->input->post('topictitle');
            }
            
            $id = $this->Topic_master_model->add($data);
            
            if ($id) {
                set_alert('success', _l('added_successfully', _l('topic_master')));
                redirect(admin_url('topics/detail/' . $id));
            }
        }

        $data['title'] = _l('new_topic_master');
        $this->load->view('topic_master/create', $data);
    }

    public function edit($id)
    {
        if (!has_permission('topics', '', 'edit')) {
            access_denied('topics');
        }

        if ($this->input->post()) {
            $data = $this->input->post();
            $success = $this->Topic_master_model->update($id, $data);
            
            if ($success) {
                set_alert('success', _l('updated_successfully', _l('topic_master')));
            }
            
            redirect(admin_url('topics/detail/' . $id));
        }

        $data['topic'] = $this->Topic_master_model->get($id);
        if (!$data['topic']) {
            show_404();
        }

        $data['title'] = _l('edit_topic_master');
        $this->load->view('topic_master/edit', $data);
    }

    public function change_status($id)
    {
        if (!has_permission('topics', '', 'edit')) {
            ajax_access_denied();
        }

        $status = $this->input->post('status');
        $success = $this->Topic_master_model->change_status($id, $status);
        
        echo json_encode([
            'success' => $success
        ]);
    }

    public function targets()
    {
        if (!has_permission('topics', '', 'view')) {
            access_denied('topics');
        }

        $data['title'] = _l('topic_targets');
        $this->load->view('topic_master/topic_target/index', $data);
    }

    public function target_table()
    {
        if (!has_permission('topics', '', 'view')) {
            ajax_access_denied();
        }
        
        $this->app->get_table_data(module_views_path('topics', 'includes/topic_targets_table'));
    }

    private function _get_target_options($id) 
    {
        $options = '';
        
        if (has_permission('topics', '', 'edit')) {
            $options .= '<a href="' . admin_url('topics/topic_master/target_edit/' . $id) . '" 
                class="btn btn-default btn-icon"><i class="fa fa-pen-to-square"></i></a>';
        }
        
        if (has_permission('topics', '', 'delete')) {
            $options .= ' <a href="' . admin_url('topics/topic_master/target_delete/' . $id) . '" 
                class="btn btn-danger btn-icon _delete"><i class="fa fa-remove"></i></a>';
        }
        
        return $options;
    }

    public function target_create()
    {
        if (!has_permission('topics', '', 'create')) {
            access_denied('topics');
        }

        if ($this->input->post()) {
            $data = $this->input->post();
            
            // Validate form
            $this->load->library('form_validation');
            $this->form_validation->set_rules([
                [
                    'field' => 'title',
                    'label' => _l('topic_target_name'),
                    'rules' => 'required|trim'
                ],
                [
                    'field' => 'target_type',
                    'label' => _l('topic_target_type'),
                    'rules' => 'required|trim|callback_check_target_type'
                ]
            ]);

            if ($this->form_validation->run()) {
                // Format target_type to uppercase before saving
                $data['target_type'] = strtoupper($data['target_type']);
                
                $id = $this->Topic_target_model->add($data);
                
                if ($id) {
                    set_alert('success', _l('added_successfully', _l('topic_target')));
                    redirect(admin_url('topics/topic_master/targets'));
                }
            }
        }

        // Load danh sách targets từ model
        $this->load->model('Topic_target_model');
        $data['targets'] = $this->Topic_target_model->get_all(); // status = 1 for active targets
        
        $data['title'] = _l('new_topic_target');
        $this->load->view('topic_master/topic_target/create', $data);
    }

    public function check_target_type($str = null)
    {
        // For form validation callback
        if ($str !== null) {
            $target_type = strtoupper(trim($str));
            $exists = $this->Topic_target_model->get_by_type($target_type);
            return empty($exists);
        }
        
        // For AJAX request
        if ($this->input->is_ajax_request()) {
            $target_type = strtoupper(trim($this->input->post('target_type')));
            
            if (empty($target_type)) {
                echo json_encode(false);
                die();
            }

            $exists = $this->Topic_target_model->get_by_type($target_type);
            echo json_encode(empty($exists));
            die();
        }

        show_404();
    }

    public function target_edit($id)
    {
        if (!has_permission('topics', '', 'edit')) {
            access_denied('topics');
        }

        if ($this->input->post()) {
            $data = $this->input->post();
            
            // Validate form
            $this->load->library('form_validation');
            $this->form_validation->set_rules([
                [
                    'field' => 'title',
                    'label' => _l('topic_target_name'),
                    'rules' => 'required|trim'
                ],
                [
                    'field' => 'target_type',
                    'label' => _l('topic_target_type'),
                    'rules' => 'required|trim'
                ]
            ]);

            if ($this->form_validation->run()) {
                $success = $this->Topic_target_model->update($id, $data);
                
                if ($success) {
                    set_alert('success', _l('updated_successfully', _l('topic_target')));
                    redirect(admin_url('topics/topic_master/targets'));
                }
            }
        }

        // Get target by ID instead of target_type
        $data['target'] = $this->Topic_target_model->get($id);
        if (!$data['target']) {
            show_404();
        }

        // Get statistics
        $data['daily_count'] = $this->Topic_target_model->get_target_stats($id, 'daily');
        $data['weekly_count'] = $this->Topic_target_model->get_target_stats($id, 'weekly');
        $data['monthly_count'] = $this->Topic_target_model->get_target_stats($id, 'monthly');

        $data['title'] = _l('edit_topic_target');
        $this->load->view('topic_master/topic_target/edit', $data);
    }

    public function target_delete($id)
    {
        if (!has_permission('topics', '', 'delete')) {
            access_denied('topics');
        }

        $success = $this->Topic_master_model->delete_target($id);
        if ($success) {
            set_alert('success', _l('deleted_successfully', _l('topic_target')));
        }
        
        redirect(admin_url('topics/topic_master/targets'));
    }

    // Add this function to handle the options column
    private function _topic_target_options($row)
    {
        $options = '';
        
        if (has_permission('topics', '', 'view')) {
            $options .= '<a href="' . admin_url('topics/topic_master/target_view/' . $row['id']) . '" class="btn btn-default btn-icon"><i class="fa fa-eye"></i></a> ';
        }
        
        if (has_permission('topics', '', 'edit')) {
            $options .= '<a href="' . admin_url('topics/topic_master/target_edit/' . $row['id']) . '" class="btn btn-default btn-icon"><i class="fa fa-pencil-square-o"></i></a> ';
        }
        
        if (has_permission('topics', '', 'delete')) {
            $options .= '<a href="' . admin_url('topics/topic_master/target_delete/' . $row['id']) . '" class="btn btn-danger btn-icon _delete"><i class="fa fa-remove"></i></a>';
        }
        
        return $options;
    }
} 