<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Controllers Class
 * Handles all controller-related operations for topics module
 */
class Controllers extends AdminController
{
    /**
     * Class constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Topic_controller_model');
        $this->load->helper('topic_platform');
        // $lang = $this->session->userdata('admin_language');
        // $this->load->language('draft_writer_lang', $lang);
        // $this->load->language('controllers_lang', $lang);
    }

    /**
     * Display list of all controllers
     */
    public function index()
    {
        if (!has_permission('topics', '', 'view')) {
            access_denied('topics');
        }

        $data['title'] = _l('topic_controllers');
        $data['controllers'] = $this->Topic_controller_model->get();
        $this->load->view('controllers/index', $data);
    }

    /**
     * Create new controller
     */
    public function create()
    {
        if (!has_permission('topics', '', 'create')) {
            access_denied('topics');
        }

        if ($this->input->post()) {
            $data = $this->input->post();
            
            // Handle login config
            if (isset($data['login_config']) && is_array($data['login_config'])) {
                $login_config = [];
                foreach ($data['login_config'] as $key => $value) {
                    $login_config[$key] = $this->input->post('login_config[' . $key . ']', false);
                }
                $data['login_config'] = json_encode($login_config);
                unset($data['login_config']);
            } else if (isset($data['login_fields']) && is_array($data['login_fields'])) {
                // For backward compatibility
                $login_config = [];
                foreach ($data['login_fields'] as $key => $value) {
                    $login_config[$key] = $this->input->post('login_fields[' . $key . ']', false);
                }
                $data['login_config'] = json_encode($login_config);
                unset($data['login_fields']);
            }
            
            // Handle writing style
            if (isset($data['writing_style_options']) && is_array($data['writing_style_options'])) {
                $writing_style = [
                    'style' => $data['writing_style_options']['style'] ?? '',
                    'tone' => $data['writing_style_options']['tone'] ?? '',
                    'language' => $data['writing_style_options']['language'] ?? 'vietnamese',
                    'criteria' => $data['writing_style_options']['criteria'] ?? []
                ];
                $data['writing_style'] = json_encode($writing_style);
                unset($data['writing_style_options']);
            }
            
            // Handle custom fields
            $custom_fields = [];
            if (isset($data['custom_fields'])) {
                $custom_fields = $data['custom_fields'];
                unset($data['custom_fields']);
            }
            
            $id = $this->Topic_controller_model->add($data);
            
            if ($id) {
                // Ensure milestones in linked project for all action types
                if (!empty($data['project_id'])) {
                    topics_ensure_project_milestones_for_controller($id);
                }
                // Save custom fields
                if (!empty($custom_fields)) {
                    handle_custom_fields_post($id, $custom_fields, 'topic_controller');
                }
                
                // Test connection if platform and login config are provided
                if (!empty($data['platform']) && !empty($data['login_config'])) {
                    test_platform_connection($id);
                }
                
                set_alert('success', _l('added_successfully', _l('controller')));
                redirect(admin_url('topics/controllers'));
            }
        }

        // Get active projects from Perfex CRM
        $this->load->model('projects_model');
        $projects = $this->db->select('id, name')
            ->where('status !=', 5) // Not canceled
            ->get(db_prefix() . 'projects')
            ->result_array();
        
        // Get custom fields for topic controllers
        $custom_fields = get_custom_fields('topic_controller');

        $data['title'] = _l('new_controller');
        $data['platforms'] = $this->Topic_controller_model->get_platforms();
        $data['writing_styles'] = get_writing_styles();
        $data['writing_tones'] = get_writing_tones();
        $data['writing_criteria'] = get_writing_criteria();
        $data['active_projects'] = $projects;
        $data['custom_fields'] = $custom_fields;
        $this->load->view('controllers/create', $data);
    }

    /**
     * View controller details
     * @param int $id Controller ID
     */
    public function view($id)
    {
        if (!has_permission('topics', '', 'view')) {
            access_denied('topics');
        }

        $data['controller'] = $this->Topic_controller_model->get($id);
        if (!$data['controller']) {
            show_404();
        }

        // Load related topics count first
        $data['topics_count'] = $this->Topic_controller_model->get_topics_count($id);
        
        $data['title'] = $data['controller']->site;
        $this->load->view('controllers/detail', $data);
    }

    /**
     * Get controllers table data
     */
    public function table()
    {
        if (!has_permission('topics', '', 'view')) {
            ajax_access_denied();
        }

        $this->app->get_table_data(module_views_path('topics', 'includes/controllers_table'));
    }

    /**
     * Edit controller
     * @param int $id Controller ID
     */
    public function edit($id)
    {
        if (!has_permission('topics', '', 'edit')) {
            access_denied('topics');
        }

        $data['controller'] = $this->Topic_controller_model->get($id);
        if (!$data['controller']) {
            show_404();
        }

        if ($this->input->post()) {
            $update_data = $this->input->post();
            
            // Debug: Log the incoming form data
            log_message('error','Controller Edit Form Data [ID: ' . $id . '] - ' . json_encode($update_data));
            
            // Handle selected categories
            $selected_categories = $this->input->post('selected_categories');
            if (isset($selected_categories)) {
                // Ensure selected_categories is always treated as an array
                if (!is_array($selected_categories)) {
                    if (is_string($selected_categories) && !empty($selected_categories)) {
                        // If it's a single string value, convert to array with one element
                        $selected_categories = [$selected_categories];
                    } else {
                        // Empty or invalid value, set to empty array
                        $selected_categories = [];
                    }
                }
                
                // Convert all values to strings to ensure proper handling
                $selected_categories = array_map('strval', $selected_categories);
                
                // Debug log
                log_message('error','Processing selected categories: ' . json_encode($selected_categories));
                
                // Update controller's selected categories in the database
                $success = $this->Topic_controller_model->update_selected_categories($id, $selected_categories);
                
                if (!$success) {
                    log_message('error','Failed to save selected categories for controller [ID: ' . $id . ']');
                }
            } else {
                // If no categories selected, pass empty array
                $this->Topic_controller_model->update_selected_categories($id, []);
            }
            
            // Handle login config
            if (isset($update_data['login_config']) && is_array($update_data['login_config'])) {
                $login_config = [];
                foreach ($update_data['login_config'] as $key => $value) {
                    $login_config[$key] = $this->input->post('login_config[' . $key . ']', false);
                }
                // Debug: Log the processed login config
                log_message('error','Controller Login Config [ID: ' . $id . '] - ' . json_encode($login_config));
                $update_data['login_config'] = json_encode($login_config);
            } else if (isset($update_data['login_fields']) && is_array($update_data['login_fields'])) {
                // For backward compatibility
                $login_config = [];
                foreach ($update_data['login_fields'] as $key => $value) {
                    $login_config[$key] = $this->input->post('login_fields[' . $key . ']', false);
                }
                $update_data['login_config'] = json_encode($login_config);
                unset($update_data['login_fields']);
            }
            
            // Handle writing style
            if (isset($update_data['writing_style_options']) && is_array($update_data['writing_style_options'])) {
                $writing_style = [
                    'style' => $update_data['writing_style_options']['style'] ?? '',
                    'tone' => $update_data['writing_style_options']['tone'] ?? '',
                    'language' => $update_data['writing_style_options']['language'] ?? 'vietnamese',
                    'criteria' => $update_data['writing_style_options']['criteria'] ?? []
                ];
                $update_data['writing_style'] = json_encode($writing_style);
                unset($update_data['writing_style_options']);
            }
            
            // Handle custom fields
            $custom_fields = [];
            if (isset($update_data['custom_fields'])) {
                $custom_fields = $update_data['custom_fields'];
                unset($update_data['custom_fields']);
            }
            
            // Debug: Log final update data
            log_message('error','Controller Update Data [ID: ' . $id . '] - ' . json_encode($update_data));
            
            // Remove selected_categories from update_data as it's handled separately
            if (isset($update_data['selected_categories'])) {
                unset($update_data['selected_categories']);
            }
            
            // Ensure blog_id is properly handled (stored in categories_state by the model)
            if (isset($update_data['blog_id'])) {
                $update_data['blog_id'] = strval($update_data['blog_id']);
            }
            
            if ($this->Topic_controller_model->update($id, $update_data)) {
                // Ensure milestones if project assigned/changed
                if (isset($update_data['project_id']) && !empty($update_data['project_id'])) {
                    topics_ensure_project_milestones_for_controller($id);
                }
                // Save custom fields
                if (!empty($custom_fields)) {
                    handle_custom_fields_post($id, $custom_fields, 'topic_controller');
                }
                
                // Process selected categories if any
                if (isset($selected_categories) && is_array($selected_categories)) {
                    // Add debug logging
                    log_message('error','Processing selected categories for controller [ID: ' . $id . '] - ' . json_encode($selected_categories));
                    
                    // Convert all values to strings
                    $selected_categories = array_map('strval', $selected_categories);
                    
                    // Filter out any empty values
                    $selected_categories = array_filter($selected_categories, function($value) {
                        return !empty($value);
                    });
                    
                    // Update controller's selected categories in the database
                    $success = $this->Topic_controller_model->update_selected_categories($id, $selected_categories);
                    
                    if (!$success) {
                        log_message('error','Failed to update selected categories for controller [ID: ' . $id . ']');
                    }
                }
                
                // Test connection if platform and login config are provided
                if (!empty($update_data['platform']) && isset($update_data['login_config'])) {
                    test_platform_connection($id);
                }
                
                set_alert('success', _l('updated_successfully', _l('controller')));
                redirect(admin_url('topics/controllers'));
            } else {
                // Debug: Set alert for failure
                set_alert('danger', 'Failed to update controller. Check logs for details.');
                redirect(admin_url('topics/controllers/edit/' . $id));
            }
        }

        // Get active projects from Perfex CRM
        $this->load->model('projects_model');
        $projects = $this->db->select('id, name')
            ->where('status !=', 5) // Not canceled
            ->get(db_prefix() . 'projects')
            ->result_array();
            
        // Get custom fields for topic controllers
        $custom_fields = get_custom_fields('topic_controller');
        $data['custom_fields'] = $custom_fields;
        
        // Get saved categories if available
        if (!empty($data['controller']->categories_state)) {
            $data['categories'] = json_decode($data['controller']->categories_state, true);
        }

        // Get login config and writing style
        $data['login_config'] = $this->Topic_controller_model->get_login_config($id);
        $data['writing_style'] = $this->Topic_controller_model->get_writing_style($id);
        $data['title'] = _l('edit_controller');

        $data['active_projects'] = $projects;
        $data['platforms'] = $this->Topic_controller_model->get_platforms();
        $data['writing_styles'] = get_writing_styles();
        $data['writing_tones'] = get_writing_tones();
        $data['writing_criteria'] = get_writing_criteria();
        $this->load->view('controllers/edit', $data);
    }

    /**
     * Clone controller
     * @param int $id Controller ID to clone
     */
    public function clone($id)
    {
        if (!has_permission('topics', '', 'create')) {
            access_denied('topics');
        }

        // Get original controller
        $original_controller = $this->Topic_controller_model->get($id);
        if (!$original_controller) {
            set_alert('danger', _l('controller_not_found'));
            redirect(admin_url('topics/controllers'));
        }

        // Create new controller data
        $clone_data = (array)$original_controller;
        
        // Remove fields that shouldn't be copied
        unset($clone_data['id']);
        
        // Add " (Clone)" to site field (instead of name)
        $clone_data['site'] = $clone_data['site'] . ' (' . _l('clone') . ')';
        
        // Reset timestamps and sync data
        $clone_data['datecreated'] = date('Y-m-d H:i:s');
        $clone_data['dateupdated'] = null;
        $clone_data['categories_last_sync'] = null;
        $clone_data['tags_last_sync'] = null;
        $clone_data['tags_sync_session_id'] = null;
        
        // Add the clone
        $new_id = $this->Topic_controller_model->add($clone_data);
        
        if ($new_id) {
            // Clone actors
            $this->load->model('Topic_controller_actor_model');
            $actors = $this->Topic_controller_actor_model->get(null, $id);
            
            if (!empty($actors)) {
                foreach ($actors as $actor) {
                    $actor_data = [
                        'controller_id' => $new_id,
                        'name' => $actor['name'],
                        'description' => $actor['description'],
                        'priority' => $actor['priority'],
                        'active' => $actor['active']
                    ];
                    
                    $this->Topic_controller_actor_model->add($actor_data);
                }
                log_message('error','Controller Actors Cloned [Controller ID From: ' . $id . ', To: ' . $new_id . ']');
            }
            
            log_message('error','Controller Cloned [ID From: ' . $id . ', To: ' . $new_id . ']');
            set_alert('success', _l('controller_cloned_successfully'));
        } else {
            set_alert('danger', _l('controller_clone_failed'));
        }
        
        redirect(admin_url('topics/controllers'));
    }

    /**
     * Delete controller
     * @param int $id Controller ID
     */
    public function delete($id)
    {
        if (!has_permission('topics', '', 'delete')) {
            access_denied('topics');
        }

        if ($this->Topic_controller_model->delete($id)) {
            set_alert('success', _l('deleted_successfully', _l('controller')));
        }

        redirect(admin_url('topics/controllers'));
    }

    /**
     * Get available topics for controller
     * @param int $controller_id Controller ID
     */
    public function get_available_topics($controller_id)
    {
        if (!has_permission('topics', '', 'view')) {
            ajax_access_denied();
        }

        $this->app->get_table_data(module_views_path('topics', 'includes/available_topics_table'), [
            'controller_id' => $controller_id,
            'table_type' => 'available'
        ]);
    }

    /**
     * Remove topics from controller
     * @param int $controller_id Controller ID
     */
    public function remove_topics($controller_id)
    {
        if (!has_permission('topics', '', 'edit')) {
            ajax_access_denied();
        }

        $topic_ids = $this->input->post('topic_ids');
        
        if (empty($topic_ids)) {
            echo json_encode([
                'success' => false,
                'message' => _l('no_topics_selected')
            ]);
            die();
        }

        $success = $this->Topic_controller_model->remove_topics($controller_id, $topic_ids);
        
        echo json_encode([
            'success' => $success,
            'message' => $success ? _l('topics_removed_successfully') : _l('topics_remove_failed')
        ]);
    }

    /**
     * Add topics to controller
     * @param int $controller_id Controller ID
     */
    public function add_topics($controller_id)
    {
        if (!has_permission('topics', '', 'edit')) {
            ajax_access_denied();
        }

        $topic_ids = $this->input->post('topic_ids');
        
        if (empty($topic_ids)) {
            echo json_encode([
                'success' => false,
                'message' => _l('no_topics_selected')
            ]);
            die();
        }

        $success = $this->Topic_controller_model->add_topics($controller_id, $topic_ids);
        
        echo json_encode([
            'success' => $success,
            'message' => $success ? _l('topics_added_successfully') : _l('topics_add_failed')
        ]);
    }

    /**
     * Get related topics for controller
     * @param int $controller_id Controller ID
     */
    public function get_related_topics($controller_id)
    {
        if (!has_permission('topics', '', 'view')) {
            ajax_access_denied();
        }

        $this->app->get_table_data(module_views_path('topics', 'includes/available_topics_table'), [
            'controller_id' => $controller_id,
            'table_type' => 'related'
        ]);
    }

    /**
     * Test platform connection
     * @param int $id Controller ID (optional, null for AJAX calls)
     */
    public function test_connection($id = null)
    {
        if (!has_permission('topics', '', 'edit') && !has_permission('topics', '', 'create')) {
            ajax_access_denied();
        }
        
        // Handle POST request for temporary test connection (create form)
        if ($this->input->server('REQUEST_METHOD') === 'POST' && !$id) {
            $platform = $this->input->post('platform');
            $login_fields = $this->input->post();
            
            // Remove platform from login fields
            unset($login_fields['platform']);
            
            if (!$platform) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Platform not specified'
                ]);
                return;
            }
            
            // Get platform connector
            $connector = $this->_get_platform_connector($platform);
            
            if (!$connector) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Platform connector not found: ' . $platform
                ]);
                return;
            }
            
            // Test connection
            $result = $connector->testConnection($login_fields);
            
            echo json_encode($result);
            return;
        }
        
        // If id is not provided directly, get it from POST
        if (!$id) {
            $id = $this->input->post('id');
        }
        
        // Handle GET request for existing controller
        if (!$id) {
            echo json_encode([
                'success' => false,
                'message' => 'Controller ID not specified'
            ]);
            return;
        }
        
        // Get controller data from database
        $controller = $this->Topic_controller_model->get($id);
        
        if (!$controller) {
            echo json_encode(['success' => false, 'message' => _l('controller_not_found')]);
            return;
        }
        
        $result = test_platform_connection($id);
        $success = isset($result['success']) ? $result['success'] : false;
        $message = isset($result['message']) ? $result['message'] : _l('connection_test_failed');

        // If connection was successful, also fetch categories
        if ($success) {
            // Fetch categories for this controller
            $categories = get_platform_categories($id);
            
            $result['categories'] = $categories['categories'] ?? [];
            
            // Save categories to controller state
            if (!empty($result['categories'])) {
                $this->db->where('id', $id);
                $this->db->update(db_prefix() . 'topic_controllers', [
                    'categories_state' => json_encode($result['categories']),
                    'categories_last_sync' => date('Y-m-d H:i:s')
                ]);
            }
            
            // Check if site info is available and update slogan and logo_url
            if (isset($result['site_info'])) {
                $update_data = [];
                
                // Update slogan from site description if available
                if (!empty($result['site_info']['description'])) {
                    $update_data['slogan'] = $result['site_info']['description'];
                }
                
                // Update logo URL if available
                if (!empty($result['site_info']['logo'])) {
                    $update_data['logo_url'] = $result['site_info']['logo'];
                }
                
                // Update controller if we have data to update
                if (!empty($update_data)) {
                    $this->db->where('id', $id);
                    $this->db->update(db_prefix() . 'topic_controllers', $update_data);
                    
                    // Add info to the result for user feedback
                    $result['updated_fields'] = array_keys($update_data);
                    $result['field_values'] = $update_data;
                    
                    // Add specific message about what was updated
                    $updated_fields_text = '';
                    
                    if (isset($update_data['slogan']) && isset($update_data['logo_url'])) {
                        $updated_fields_text = _l('slogan') . ' ' . _l('and') . ' ' . _l('logo_url');
                    } elseif (isset($update_data['slogan'])) {
                        $updated_fields_text = _l('slogan');
                    } elseif (isset($update_data['logo_url'])) {
                        $updated_fields_text = _l('logo_url');
                    }
                    
                    $result['message'] .= '. ' . _l('website_info_updated');
                    $result['update_message'] = sprintf('%s: %s', _l('website_info_updated'), $updated_fields_text);
                }
            }
        }
        
        echo json_encode($result);
    }
    
    /**
     * Get platform fields
     */
    public function get_platform_fields()
    {
        if (!has_permission('topics', '', 'view')) {
            ajax_access_denied();
        }
        
        $platform = $this->input->post('platform');
        
        if (!$platform) {
            echo json_encode([
                'success' => false,
                'message' => 'Platform not specified'
            ]);
            die();
        }
        
        // Get platform info
        $platform_info = get_platform_info($platform);
        
        if (!$platform_info) {
            echo json_encode([
                'success' => false,
                'message' => 'Platform not found'
            ]);
            die();
        }
        
        // Get login fields
        $login_fields = get_platform_login_fields($platform);
        
        echo json_encode([
            'success' => true,
            'login_fields' => $login_fields,
            'platform_info' => $platform_info
        ]);
    }
    
    /**
     * Get platform categories
     * @param int $id Controller ID
     */
    public function get_platform_categories($id)
    {
        if (!has_permission('topics', '', 'view')) {
            ajax_access_denied();
        }
        
        $result = get_platform_categories($id);
        
        echo json_encode($result);
    }
    
    /**
     * Get writing styles
     */
    public function get_writing_styles()
    {
        if (!has_permission('topics', '', 'view')) {
            ajax_access_denied();
        }
        
        echo json_encode([
            'success' => true,
            'data' => get_writing_styles()
        ]);
    }
    
    /**
     * Get writing tones
     */
    public function get_writing_tones()
    {
        if (!has_permission('topics', '', 'view')) {
            ajax_access_denied();
        }
        
        echo json_encode([
            'success' => true,
            'data' => get_writing_tones()
        ]);
    }
    
    /**
     * Get writing criteria
     */
    public function get_writing_criteria()
    {
        if (!has_permission('topics', '', 'view')) {
            ajax_access_denied();
        }

        echo json_encode([
            'success' => true,
            'criteria' => get_writing_criteria()
        ]);
    }

    /**
     * Get categories for a controller
     * @param int $id Controller ID
     */
    public function get_categories($id)
    {
        if (!has_permission('topics', '', 'view')) {
            ajax_access_denied();
        }
        
        // Check if controller exists
        $controller = $this->Topic_controller_model->get($id);
        if (!$controller) {
            echo json_encode([
                'success' => false,
                'message' => 'Controller not found'
            ]);
            return;
        }
        
        // Get categories from database
        $categories = $this->Topic_controller_model->get_categories($id);
        
        echo json_encode([
            'success' => true,
            'categories' => $categories,
            'last_sync' => $controller->categories_last_sync ? _dt($controller->categories_last_sync) : _l('never_synced')
        ]);
    }

    /**
     * Quick save login credentials without updating the entire controller
     * @param int $id Controller ID
     */
    public function quick_save_login($id)
    {
        if (!has_permission('topics', '', 'edit')) {
            ajax_access_denied();
        }
        
        // Validate controller ID
        $controller = $this->Topic_controller_model->get($id);
        if (!$controller) {
            echo json_encode([
                'success' => false,
                'message' => 'Controller not found'
            ]);
            return;
        }
        
        // Process login configuration
        $login_fields = $this->input->post('login_config');
        $platform = $this->input->post('platform');
        
        if (!is_array($login_fields) || empty($login_fields)) {
            echo json_encode([
                'success' => false,
                'message' => 'No login configuration provided'
            ]);
            return;
        }
        
        // Log the action for debugging
        log_message('error','Quick Save Login [ID: ' . $id . '] - Login fields: ' . json_encode($login_fields));
        
        // Update the login configuration
        $login_config_json = json_encode($login_fields);
        $success = $this->Topic_controller_model->set_login_config($id, $login_fields);
        
        if ($success) {
            // Test connection with the new credentials
            $result = test_platform_connection($id);
            $connection_success = $result['success'] ?? false;
            
            echo json_encode([
                'success' => true,
                'message' => 'Login credentials saved successfully',
                'connection_success' => $connection_success,
                'connection_message' => $result['message'] ?? ''
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to save login credentials'
            ]);
        }
    }

    /**
     * Sync categories
     * @param int $id Controller ID
     */
    public function sync_categories($id)
    {
        if (!has_permission('topics', '', 'edit')) {
            ajax_access_denied();
        }
        
        // Check if controller exists
        $controller = $this->Topic_controller_model->get($id);
        if (!$controller) {
            echo json_encode([
                'success' => false,
                'message' => 'Controller not found'
            ]);
            return;
        }
        
        // Get platform connector
        $platform = $controller->platform;
        $connector = get_platform_connector($platform);
        
        if (!$connector) {
            echo json_encode([
                'success' => false,
                'message' => 'Platform connector not found: ' . $platform
            ]);
            return;
        }
        
        // Get login config
        $login_config = $this->Topic_controller_model->get_login_config($id);
        
        if (empty($login_config)) {
            echo json_encode([
                'success' => false,
                'message' => 'Login configuration not found'
            ]);
            return;
        }
        
        // Get categories from platform
        $result = $connector->getCategories($login_config);
        
        if (!$result['success']) {
            echo json_encode([
                'success' => false,
                'message' => $result['message'] ?? 'Failed to get categories'
            ]);
            return;
        }
        
        // Save categories to database
        $categories = $result['categories'] ?? [];
        $saved_count = 0;
        
        if (!empty($categories)) {
            foreach ($categories as $category) {
                $saved = $this->Topic_controller_model->save_category($id, $category);
                if ($saved) {
                    $saved_count++;
                }
            }
        }
        
        // Update last sync time
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'topic_controllers', [
            'categories_last_sync' => date('Y-m-d H:i:s')
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => sprintf(_l('categories_synced_count'), $saved_count),
            'count' => $saved_count
        ]);
    }

    /**
     * Save categories state (expanded/collapsed) for a controller
     * 
     * @param int $id Controller ID
     */
    public function save_categories_state($id)
    {
        if (!has_permission('topics', '', 'edit')) {
            ajax_access_denied();
        }
        
        // Check if controller exists
        $controller = $this->Topic_controller_model->get($id);
        if (!$controller) {
            echo json_encode([
                'success' => false,
                'message' => 'Controller not found'
            ]);
            return;
        }
        
        // Get expanded categories
        $expanded_categories = $this->input->post('expanded_categories');
        
        // Save state
        $success = $this->Topic_controller_model->save_controller_state($id, [
            'expanded_categories' => $expanded_categories
        ]);
        
        echo json_encode([
            'success' => $success,
            'message' => $success ? 'State saved successfully' : 'Failed to save state'
        ]);
    }

    /**
     * Get blogs for a controller, optionally filtered by category
     * 
     * @param int $id Controller ID
     * @return void
     */
    public function get_blogs($id)
    {
        if (!has_permission('topics', '', 'view')) {
            ajax_access_denied();
        }
        
        // Check if controller exists
        $controller = $this->Topic_controller_model->get($id);
        if (!$controller) {
            echo json_encode([
                'success' => false,
                'message' => 'Controller not found'
            ]);
            return;
        }
        
        // Get category ID from request if provided
        $category_id = $this->input->get('category_id');
        // Kiểm tra xem có yêu cầu lấy trực tiếp từ API không
        $direct_fetch = $this->input->get('direct_fetch');
        
        // Hỗ trợ phân trang cho data từ database
        $page = (int)$this->input->get('page') ?: 1;
        $limit = (int)$this->input->get('limit') ?: 10;
        $offset = ($page - 1) * $limit;
        
        // Nếu không yêu cầu lấy trực tiếp từ API, lấy từ database với phân trang
        if (empty($direct_fetch)) {
            // Get blogs from database with pagination
            $blogs = $this->Topic_controller_model->get_blogs($id, $category_id, $limit, $offset);
            $total_count = $this->Topic_controller_model->count_blogs($id, $category_id);
            
            // Log the database lookup
            log_message('debug', 'Controllers - Getting blogs from database. Count: ' . count($blogs) . 
                        ' for controller #' . $id . ' and category #' . $category_id);
            
            echo json_encode([
                'success' => true,
                'blogs' => $blogs,
                'source' => 'database',
                'pagination' => [
                    'total' => $total_count,
                    'page' => $page,
                    'limit' => $limit,
                    'pages' => ceil($total_count / $limit)
                ]
            ]);
            return;
        }
        
        // Lấy trực tiếp từ API (tương tự sync_blogs nhưng không lưu vào database)
        log_message('debug', 'Controllers - Getting blogs directly from platform for controller #' . $id . 
                    ' and category #' . $category_id);
        
        // Get platform connector
        $connector = get_platform_connector($controller->platform);
        
        if (!$connector) {
            echo json_encode([
                'success' => false,
                'message' => 'Platform connector not found'
            ]);
            return;
        }
        
        // Get login config
        $login_config = json_decode($controller->login_config, true);
        
        if (!$login_config) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid login configuration'
            ]);
            return;
        }
        
        // Add site URL to config
        $login_config['site_url'] = $controller->site;
        log_message('error', 'Controllers - before get blogs');
        // Get blogs from platform
        $result = $connector->getBlogs($login_config, ['category_id' => $category_id]);
        log_message('error', 'Controllers - after get blogs');
        
        if (!$result['success']) {
            echo json_encode([
                'success' => false,
                'message' => $result['message'] ?? 'Failed to get blogs from platform'
            ]);
            return;
        }
        
        // Log số lượng blog đã lấy được
        log_message('error', 'Controllers - Retrieved ' . count($result['blogs']) . ' blogs directly from platform');
        
        // Chỉ trả về số lượng blog khi lấy từ API, không trả về nội dung đầy đủ
        // Điều này sẽ giúp tối ưu hóa băng thông và yêu cầu người dùng sync để lưu vào database
        echo json_encode([
            'success' => true,
            'count' => count($result['blogs']),
            'source' => 'platform_api',
            'category_id' => $category_id,
            'controller_id' => $id,
            'need_sync' => true, // Thêm cờ để thông báo cần sync
            'message' => _l('api_blogs_found_sync_required')
        ]);
    }
    
    /**
     * Sync blogs for a controller by category
     * 
     * @param int $id Controller ID
     * @return void
     */
    public function sync_blogs($id)
    {
        if (!has_permission('topics', '', 'edit')) {
            ajax_access_denied();
        }
        
        // Check if controller exists
        $controller = $this->Topic_controller_model->get($id);
        if (!$controller) {
            echo json_encode([
                'success' => false,
                'message' => 'Controller not found'
            ]);
            return;
        }
        
        // Get category ID from request
        $category_id = $this->input->post('category_id');
        
        if (!$category_id) {
            echo json_encode([
                'success' => false,
                'message' => _l('controller_select_category_first')
            ]);
            return;
        }
        
        // Get platform connector
        $connector = get_platform_connector($controller->platform);
        
        if (!$connector) {
            echo json_encode([
                'success' => false,
                'message' => 'Platform connector not found'
            ]);
            return;
        }
        
        // Get login config
        $login_config = json_decode($controller->login_config, true);
        
        if (!$login_config) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid login configuration'
            ]);
            return;
        }
        
        // Add site URL to config
        $login_config['site_url'] = $controller->site;
        
        // Get blogs from platform
        $result = $connector->getBlogs($login_config, ['category_id' => $category_id]);
        
        if (!$result['success']) {
            echo json_encode([
                'success' => false,
                'message' => $result['message'] ?? 'Failed to get blogs from platform'
            ]);
            return;
        }
        
        // Save blogs to database
        $saved_count = 0;
        $total_bytes_saved = 0;
        
        // Log bắt đầu quá trình lưu blogs
        log_message('info', 'Controllers - Starting to save ' . count($result['blogs']) . ' blogs to database');
        
        foreach ($result['blogs'] as $blog) {
            // Ước tính kích thước dữ liệu blog
            $estimated_size = strlen(json_encode($blog));
            
            // Log thông tin cơ bản của blog
            log_message('debug', 'Controllers - Syncing blog #' . $blog['blog_id'] . ' - "' . $blog['title'] . 
                        '" (Size: ' . $estimated_size . ' bytes, Excerpt length: ' . strlen($blog['excerpt'] ?? '') . ' bytes)');
            
            $blog_id = $this->Topic_controller_model->save_blog($id, $blog);
            if ($blog_id) {
                $saved_count++;
                $total_bytes_saved += $estimated_size;
                
                // Lưu mối quan hệ giữa blog và danh mục
                $result_rel = $this->Topic_controller_model->save_blog_relationship($id, $blog['blog_id'], 'category', $category_id);
                if (!$result_rel) {
                    log_message('error', 'Controllers - Failed to save blog-category relationship for blog #' . $blog['blog_id'] . ' and category #' . $category_id);
                }
                
                // Xử lý danh mục bổ sung
                if (isset($blog['categories']) && is_array($blog['categories'])) {
                    foreach ($blog['categories'] as $cat) {
                        // WordPress API returns categories as array of IDs
                        $cat_id = is_array($cat) && isset($cat['category_id']) ? $cat['category_id'] : $cat;
                        if (!empty($cat_id) && $cat_id != $category_id) {
                            $this->Topic_controller_model->save_blog_relationship($id, $blog['blog_id'], 'category', $cat_id);
                        }
                    }
                }
                
                // Xử lý tags nếu có
                if (isset($blog['tags']) && is_array($blog['tags'])) {
                    foreach ($blog['tags'] as $tag) {
                        $tag_id = is_array($tag) && isset($tag['tag_id']) ? $tag['tag_id'] : $tag;
                        if (!empty($tag_id)) {
                            $this->Topic_controller_model->save_blog_relationship($id, $blog['blog_id'], 'tag', $tag_id);
                        }
                    }
                }
                
                // Log lưu blog thành công
                log_message('info', 'Controllers - Blog #' . $blog['blog_id'] . ' saved successfully');
            } else {
                // Log lỗi khi lưu blog
                log_message('error', 'Controllers - Failed to save blog #' . $blog['blog_id'] . ' - ' . $blog['title']);
            }
        }
        
        // Log tổng kết quá trình đồng bộ
        log_message('info', 'Controllers - Syncing completed. ' . $saved_count . '/' . count($result['blogs']) . 
                    ' blogs saved successfully. Total data size: ' . $total_bytes_saved . ' bytes');
        
        // Cập nhật thời gian đồng bộ cho danh mục
        $this->Topic_controller_model->update_category_sync_time($id, $category_id);
        
        echo json_encode([
            'success' => true,
            'message' => sprintf(_l('blogs_synced_count'), $saved_count),
            'count' => $saved_count
        ]);
    }

    /**
     * Save a single blog from API to database
     * @param int $id Controller ID
     */
    public function save_single_blog($id)
    {
        if (!has_permission('topics', '', 'edit')) {
            ajax_access_denied();
        }
        
        // Validate input
        $category_id = $this->input->post('category_id');
        $blog_id = $this->input->post('blog_id');
        $blog_data_json = $this->input->post('blog_data');
        
        if (!$category_id || !$blog_id || !$blog_data_json) {
            echo json_encode([
                'success' => false,
                'message' => 'Missing required parameters'
            ]);
            return;
        }
        
        // Check if controller exists
        $controller = $this->Topic_controller_model->get($id);
        if (!$controller) {
            echo json_encode([
                'success' => false,
                'message' => 'Controller not found'
            ]);
            return;
        }
        
        // Parse blog data
        $blog = json_decode($blog_data_json, true);
        if (!$blog) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid blog data'
            ]);
            return;
        }
        
        // Log blog data
        log_message('info', 'Controllers - Saving single blog #' . $blog_id . ' to database');
        
        // Save blog to database using the same logic as in sync_blogs
        $blog_id_db = $this->Topic_controller_model->save_blog($id, $blog);
        if (!$blog_id_db) {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to save blog to database'
            ]);
            return;
        }
        
        // Save relationship between blog and category
        $result_rel = $this->Topic_controller_model->save_blog_relationship($id, $blog['blog_id'], 'category', $category_id);
        if (!$result_rel) {
            log_message('error', 'Controllers - Failed to save blog-category relationship for blog #' . $blog['blog_id'] . ' and category #' . $category_id);
        }
        
        // Handle additional categories
        if (isset($blog['categories']) && is_array($blog['categories'])) {
            foreach ($blog['categories'] as $cat) {
                $cat_id = is_array($cat) && isset($cat['category_id']) ? $cat['category_id'] : $cat;
                if (!empty($cat_id) && $cat_id != $category_id) {
                    $this->Topic_controller_model->save_blog_relationship($id, $blog['blog_id'], 'category', $cat_id);
                }
            }
        }
        
        // Handle tags
        if (isset($blog['tags']) && is_array($blog['tags'])) {
            foreach ($blog['tags'] as $tag) {
                $tag_id = is_array($tag) && isset($tag['tag_id']) ? $tag['tag_id'] : $tag;
                if (!empty($tag_id)) {
                    $this->Topic_controller_model->save_blog_relationship($id, $blog['blog_id'], 'tag', $tag_id);
                }
            }
        }
        
        // Update category sync time
        $this->Topic_controller_model->update_category_sync_time($id, $category_id);
        
        echo json_encode([
            'success' => true,
            'message' => _l('blog_saved_successfully')
        ]);
    }

    /**
     * Get tags for a controller from database
     * @param int $id Controller ID
     * @return void
     */
    public function get_tags($id)
    {
        if (!has_permission('topics', '', 'view')) {
            ajax_access_denied();
        }

        $controller = $this->Topic_controller_model->get($id);
        if (!$controller) {
            echo json_encode([
                'success' => false,
                'message' => _l('controller_not_found')
            ]);
            return;
        }

        // Get tags from database
        $tags = $this->Topic_controller_model->get_tags($id);

        echo json_encode([
            'success' => true,
            'data' => $tags,
            'last_sync' => $controller->tags_last_sync ? _dt($controller->tags_last_sync) : _l('never_synced')
        ]);
    }

    /**
     * Manage tags sync session
     * @param int $controller_id Controller ID
     * @param int $page Current page
     * @return string Session ID
     */
    private function _manage_tags_sync_session($controller_id, $page = 1)
    {
        $this->load->model('Topic_sync_log_model');
        
        // Ghi log cho debug
        log_message('debug', 'Managing tags sync session for controller ID: ' . $controller_id . ', Page: ' . $page);
        
        // Lấy thông tin controller
        $controller = $this->Topic_controller_model->get($controller_id);
        
        // Nếu là trang đầu tiên, tạo phiên mới trừ khi có session_id trong controller
        if ($page == 1) {
            // Kiểm tra xem controller có session_id đang hoạt động không
            if ($controller && !empty($controller->tags_sync_session_id)) {
                // Kiểm tra xem phiên có tồn tại trong database không
                $existing_session = $this->Topic_sync_log_model->get_session($controller->tags_sync_session_id);
                
                if ($existing_session && $existing_session->status == 'in_progress') {
                    log_message('debug', 'Using existing active session ID: ' . $controller->tags_sync_session_id);
                    return $controller->tags_sync_session_id;
                }
            }
            
            // Tạo phiên mới
            $session_id = $this->Topic_sync_log_model->create_session($controller_id, 'tags_sync', [
                'controller_id' => $controller_id,
                'status' => 'in_progress',
                'start_time' => date('Y-m-d H:i:s'),
                'total_pages' => 1, // Sẽ cập nhật sau
                'current_page' => 1,
                'total_items' => 0,
                'items_processed' => 0,
                'success_count' => 0,
                'error_count' => 0,
                'last_update' => date('Y-m-d H:i:s')
            ]);
            
            log_message('debug', 'Created new sync session with ID: ' . $session_id);
            
            // Lưu session ID vào controller để dễ dàng truy cập sau này
            $this->db->where('id', $controller_id)
                    ->update(db_prefix() . 'topic_controllers', [
                        'tags_sync_session_id' => $session_id
                    ]);
            
            return $session_id;
        } else {
            // Tìm phiên đồng bộ hiện tại
            
            // Đầu tiên kiểm tra session_id từ controller
            if ($controller && !empty($controller->tags_sync_session_id)) {
                $session = $this->Topic_sync_log_model->get_session($controller->tags_sync_session_id);
                if ($session && $session->status == 'in_progress') {
                    log_message('debug', 'Using controller\'s session ID: ' . $controller->tags_sync_session_id . ' for page: ' . $page);
                    return $controller->tags_sync_session_id;
                }
            }
            
            // Nếu không tìm thấy phiên từ controller, tìm phiên đang hoạt động
            $current_session = $this->Topic_sync_log_model->get_active_session($controller_id, 'tags_sync');
            
            if ($current_session) {
                log_message('debug', 'Found active session ID: ' . $current_session->session_id . ' for page: ' . $page);
                
                // Cập nhật controller với phiên đang hoạt động
                $this->db->where('id', $controller_id)
                        ->update(db_prefix() . 'topic_controllers', [
                            'tags_sync_session_id' => $current_session->session_id
                        ]);
                
                return $current_session->session_id;
            } else {
                log_message('debug', 'No active session found for page ' . $page . ', creating new one');
                // Nếu không tìm thấy, tạo phiên mới
                return $this->_manage_tags_sync_session($controller_id, 1);
            }
        }
    }

    /**
     * Update tags sync log
     * @param string $session_id Session ID
     * @param array $data Data to update
     * @return bool Success or failure
     */
    private function _update_tags_sync_log($session_id, $data)
    {
        $this->load->model('Topic_sync_log_model');
        
        // Chuẩn bị dữ liệu cập nhật
        $summary_updates = [];
        $log_entry = null;
        
        foreach ($data as $key => $value) {
            if ($key == 'message' || $key == 'type' || $key == 'details') {
                // Nếu là thông tin log, thêm vào log_entry
                if ($key == 'message') {
                    if (!$log_entry) $log_entry = [];
                    $log_entry['message'] = $value;
                } else if ($key == 'type') {
                    if (!$log_entry) $log_entry = [];
                    $log_entry['type'] = $value;
                } else if ($key == 'details') {
                    if (!$log_entry) $log_entry = [];
                    $log_entry['details'] = $value;
                }
            } else {
                // Nếu là thông tin tóm tắt, thêm vào summary_updates
                $summary_updates[$key] = $value;
            }
        }
        
        // Cập nhật session
        return $this->Topic_sync_log_model->update_session($session_id, $summary_updates, $log_entry);
    }

    /**
     * Complete tags sync session
     * @param string $session_id Session ID
     * @return bool Success or failure
     */
    private function _complete_tags_sync_session($session_id)
    {
        $this->load->model('Topic_sync_log_model');
        
        // Lấy thông tin session để biết controller_id
        $session = $this->Topic_sync_log_model->get_session($session_id);
        if ($session) {
            // Đặt tags_sync_session_id thành NULL trong bảng topic_controllers khi đồng bộ hoàn thành
            $controller_id = $session->controller_id;
            $this->db->where('id', $controller_id)
                    ->update(db_prefix() . 'topic_controllers', [
                        'tags_sync_session_id' => NULL
                    ]);
        }
        
        // Hoàn thành session với trạng thái "completed"
        return $this->Topic_sync_log_model->complete_session($session_id, 'completed');
    }

    /**
     * Get tags sync logs
     * @param int $controller_id Controller ID
     * @return void
     */
    public function get_tags_sync_logs($controller_id)
    {
        if (!has_permission('topics', '', 'view')) {
            ajax_access_denied();
        }

        $this->load->model('Topic_sync_log_model');
        
        // Lấy 10 phiên đồng bộ gần nhất
        $logs = $this->Topic_sync_log_model->get_recent_sessions($controller_id, 'tags_sync', 10);
        
        $formatted_logs = [];
        foreach ($logs as $log) {
            $formatted_logs[] = $this->Topic_sync_log_model->format_session_for_response($log);
        }
        
        echo json_encode([
            'success' => true,
            'data' => $formatted_logs
        ]);
    }

    /**
     * Get tags sync log details
     * @param string $session_id Session ID
     * @return void
     */
    public function get_tags_sync_log_details($session_id)
    {
        if (!has_permission('topics', '', 'view')) {
            ajax_access_denied();
        }

        // Ghi log cho debug
        log_message('debug', 'Getting sync log details for session ID: ' . $session_id);

        $this->load->model('Topic_sync_log_model');
        
        // Lấy chi tiết phiên đồng bộ
        $log = $this->Topic_sync_log_model->get_session($session_id);
        
        if (!$log) {
            log_message('error', 'Session ID not found: ' . $session_id);
            echo json_encode([
                'success' => false,
                'message' => 'Không tìm thấy phiên đồng bộ'
            ]);
            return;
        }
        
        $summary_data = json_decode($log->summary_data, true);
        $log_data = json_decode($log->log_data, true);
        
        // Luôn đếm số lượng thực tế từ database
        $actual_count = $this->Topic_sync_log_model->get_processed_tags_count($session_id);
        
        // Đặt giá trị vào processed_tags và items_processed
        $summary_data['processed_tags'] = $actual_count;
        $summary_data['items_processed'] = $actual_count;
        
        // Cập nhật vào database cho lần sau
        $this->Topic_sync_log_model->update_processed_count_from_db($session_id);
        
        // Bổ sung thông tin cho trạng thái pending
        $can_continue = false;
        if ($summary_data['status'] === 'pending') {
            $can_continue = true;
        }
        
        // Ghi log dữ liệu trả về
        log_message('debug', 'Sync log details found. Status: ' . ($summary_data['status'] ?? 'unknown') . 
                             ', Logs count: ' . count($log_data['logs'] ?? []) . 
                             ', Can continue: ' . ($can_continue ? 'yes' : 'no'));
       
        echo json_encode([
            'success' => true,
            'summary' => $summary_data,
            'logs' => $log_data['logs'] ?? [],
            'controller_id' => $summary_data['controller_id'] ?? null,
            'can_continue' => $can_continue
        ]);
    }

    /**
     * Resume tags sync with platform API
     * @param int $id Controller ID
     */
    public function resume_tags_sync($id)
    {
        if (!has_permission('topics', '', 'edit')) {
            ajax_access_denied();
        }

        // Lấy session_id từ POST request
        $session_id = $this->input->post('session_id');
        $page = $this->input->post('page') ? (int)$this->input->post('page') : 1;

        // Ghi log bắt đầu tiếp tục đồng bộ
        log_message('debug', 'Resuming tags sync for controller ID: ' . $id . ', Session: ' . $session_id . ', Page: ' . $page);

        $this->load->model('Topic_sync_log_model');
        
        // Kiểm tra xem phiên đồng bộ có tồn tại không
        $session = $this->Topic_sync_log_model->get_session($session_id);
        if (!$session) {
            log_message('error', 'Resume failed: Session ID not found: ' . $session_id);
            echo json_encode([
                'success' => false,
                'message' => 'Không tìm thấy phiên đồng bộ'
            ]);
            return;
        }
        
        // Lấy thông tin controller
        $controller = $this->Topic_controller_model->get($id);
        if (!$controller) {
            log_message('error', 'Resume failed: Controller not found: ' . $id);
            echo json_encode([
                'success' => false,
                'message' => _l('controller_not_found')
            ]);
            return;
        }

        // Cập nhật trạng thái phiên đồng bộ
        $summary_data = json_decode($session->summary_data, true);
        
        // Xử lý trạng thái "pending" hoặc "cancelled" - nếu phiên đang ở trạng thái này, lấy trang tiếp theo
        if (isset($summary_data['status']) && ($summary_data['status'] === 'pending' || $summary_data['status'] === 'cancelled')) {
            // Lấy trang tiếp theo từ trang hiện tại
            $current_page = $summary_data['current_page'] ?? 1;
            $page = $current_page + 1;
            
            // Nếu đã bị hủy, có thể cần phục hồi từ trang hiện tại thay vì trang tiếp theo
            if ($summary_data['status'] === 'cancelled') {
                $page = $current_page; // Tiếp tục từ trang hiện tại nơi phiên bị hủy
                log_message('debug', 'Resuming from cancelled status. Current page: ' . $current_page);
            } else {
                log_message('debug', 'Resuming from pending status. Current page: ' . $current_page . ', Next page: ' . $page);
            }
        }
        
        // Đảm bảo các khóa cần thiết đều tồn tại
        $required_keys = [
            'controller_id' => $id,
            'controller_name' => $controller->site ?? 'Unknown',
            'platform' => $controller->platform ?? 'Unknown',
            'status' => 'in_progress',
            'current_page' => $page,
            'total_pages' => $summary_data['total_pages'] ?? 0,
            'total_tags' => $summary_data['total_tags'] ?? 0,
            'processed_tags' => $summary_data['processed_tags'] ?? 0
        ];
        
        foreach ($required_keys as $key => $default_value) {
            if (!isset($summary_data[$key])) {
                $summary_data[$key] = $default_value;
            }
        }
        
        $summary_data['status'] = 'in_progress';
        $summary_data['current_page'] = $page;
        $summary_data['last_update'] = date('Y-m-d H:i:s');
        $summary_data['resume_time'] = date('Y-m-d H:i:s');
        
        // Nếu phiên này đã kết thúc trước đó, xóa thời gian kết thúc
        if (isset($summary_data['end_time'])) {
            $summary_data['end_time'] = null;
        }
        
        // Thêm log về việc tiếp tục phiên
        $log_entry = [
            'message' => 'Tiếp tục đồng bộ từ trang ' . $page,
            'type' => 'info',
            'details' => [
                'page' => $page,
                'resumed_at' => date('Y-m-d H:i:s'),
                'from_status' => $session->status
            ]
        ];
        
        // Nếu là phiên đã bị hủy, thêm thông tin đặc biệt
        if ($session->status === 'cancelled') {
            $log_entry = [
                'message' => 'Khôi phục phiên đã bị hủy và tiếp tục đồng bộ từ trang ' . $page,
                'type' => 'warning',
                'details' => [
                    'page' => $page,
                    'resumed_at' => date('Y-m-d H:i:s'),
                    'from_status' => 'cancelled',
                    'cancelled_time' => $summary_data['end_time'] ?? 'Unknown'
                ]
            ];
            
            // Cập nhật trực tiếp trạng thái trong database
            $this->db->where('session_id', $session_id)
                    ->update(db_prefix() . 'topic_sync_logs', [
                        'status' => 'in_progress',
                        'end_time' => null
                    ]);
            
            log_message('info', 'Resumed a cancelled sync session: ' . $session_id);
        }
        
        // Cập nhật session trong database
        $updated = $this->Topic_sync_log_model->update_session($session_id, $summary_data, $log_entry);
        if (!$updated) {
            log_message('error', 'Resume failed: Could not update session: ' . $session_id);
            echo json_encode([
                'success' => false,
                'message' => 'Không thể cập nhật phiên đồng bộ'
            ]);
            return;
        }
        
        // Đảm bảo logs không bị mất khi tiếp tục phiên đã bị hủy
        if ($session->status === 'cancelled') {
            // Lấy logs hiện tại từ session
            $logs = [];
            if (!empty($session->log_data)) {
                $log_data = json_decode($session->log_data, true);
                if (isset($log_data['logs']) && is_array($log_data['logs'])) {
                    $logs = $log_data['logs'];
                }
            }
            
            // Thêm log phục hồi phiên vào đầu logs
            $recovery_log = [
                'timestamp' => date('Y-m-d H:i:s'),
                'message' => 'Phiên đồng bộ đã bị hủy được khôi phục và tiếp tục từ trang ' . $page,
                'type' => 'warning',
                'details' => [
                    'recovered_at' => date('Y-m-d H:i:s'),
                    'from_page' => $page
                ]
            ];
            
            // Thêm vào đầu mảng
            array_unshift($logs, $recovery_log);
            
            // Cập nhật logs trong database
            $log_data = ['logs' => $logs];
            $this->db->where('session_id', $session_id)
                    ->update(db_prefix() . 'topic_sync_logs', [
                        'log_data' => json_encode($log_data)
                    ]);
            
            log_message('info', 'Restored logs for resumed cancelled session: ' . $session_id . ', Log count: ' . count($logs));
        }
        
        // Cập nhật trực tiếp trạng thái trong database (để đảm bảo)
        $this->db->where('session_id', $session_id)
                ->update(db_prefix() . 'topic_sync_logs', [
                    'status' => 'in_progress'
                ]);
        
        // Cập nhật session ID trong bảng controller
        $this->db->where('id', $id)
                ->update(db_prefix() . 'topic_controllers', [
                    'tags_sync_session_id' => $session_id
                ]);
        
        // Tạo URL chuyển hướng
        $redirect_url = admin_url('topics/controllers/sync_tags/' . $id . '?page=' . $page);
        
        // Trả về thông tin thành công và URL chuyển hướng
        $response = [
            'success' => true,
            'message' => 'Đã tiếp tục phiên đồng bộ thành công',
            'session_id' => $session_id,
            'redirect_url' => $redirect_url,
            'page' => $page
        ];
        
        // Thực hiện gọi sync_tags trực tiếp nếu là AJAX request
        if ($this->input->is_ajax_request()) {
            log_message('debug', 'This is an AJAX request, returning response for client-side redirect');
            echo json_encode($response);
        } else {
            // Nếu không phải AJAX request, chuyển hướng trực tiếp
            log_message('debug', 'This is not an AJAX request, redirecting directly to sync_tags');
            redirect($redirect_url);
        }
    }

    /**
     * Sync tags with platform API
     * @param int $id Controller ID
     */
    public function sync_tags($id)
    {
        if (!has_permission('topics', '', 'edit')) {
            ajax_access_denied();
        }

        // Lấy tham số từ URL hoặc POST
        $page = $this->input->get('page') ? (int)$this->input->get('page') : 1;
        $per_page = 20; // Giảm số lượng tags mỗi request để tránh timeout
        $session_id = null;

        // Lấy thông tin controller
        $controller = $this->Topic_controller_model->get($id);
        if (!$controller) {
            set_alert('danger', _l('controller_not_found'));
            redirect(admin_url('topics/controllers'));
        }

        // Kiểm tra xem có phiên đồng bộ đang chạy không
        $this->load->model('Topic_sync_log_model');
        
        // Nếu controller có session_id, sử dụng nó
        if (!empty($controller->tags_sync_session_id)) {
            $session_id = $controller->tags_sync_session_id;
            $session = $this->Topic_sync_log_model->get_session($session_id);
            
            // Nếu phiên không tồn tại hoặc đã hoàn thành/hủy, tạo phiên mới
            if (!$session || in_array($session->status, ['completed', 'failed', 'cancelled'])) {
                $session_id = null;
            }
        }
        
        // Tạo phiên đồng bộ mới nếu cần
        if (!$session_id) {
            log_message('debug', 'Creating new sync session for controller ID: ' . $id);
            // Tính toán tổng số tags ước tính dựa trên số lượng tags mỗi trang
            $estimated_total_tags = $per_page * 20; // Ước tính sơ bộ ban đầu, sẽ được cập nhật sau
            
            $summary_data = [
                'controller_id' => $id,
                'controller_name' => $controller->site ?? 'Unknown',
                'platform' => $controller->platform ?? 'Unknown',
                'start_time' => date('Y-m-d H:i:s'),
                'status' => 'in_progress',
                'current_page' => $page,
                'per_page' => $per_page,
                'total_pages' => 20, // Giá trị ước lượng ban đầu
                'total_tags' => $estimated_total_tags,
                'processed_tags' => 0,
                'last_update' => date('Y-m-d H:i:s')
            ];
            
            $session_id = $this->Topic_sync_log_model->create_session($id, 'tags_sync', $summary_data);
            
            // Cập nhật session ID trong bảng controller
            $this->db->where('id', $id)
                    ->update(db_prefix() . 'topic_controllers', [
                        'tags_sync_session_id' => $session_id
                    ]);
        } else {
            // Lấy thông tin phiên hiện tại
            $session = $this->Topic_sync_log_model->get_session($session_id);
            $summary_data = json_decode($session->summary_data, true);
            
            // Đảm bảo các khóa cần thiết đều tồn tại
            $required_keys = [
                'controller_id' => $id,
                'controller_name' => $controller->site ?? 'Unknown',
                'platform' => $controller->platform ?? 'Unknown',
                'status' => 'in_progress',
                'current_page' => $page,
                'total_pages' => 0,
                'total_tags' => 0,
                'processed_tags' => 0
            ];
            
            foreach ($required_keys as $key => $default_value) {
                if (!isset($summary_data[$key])) {
                    $summary_data[$key] = $default_value;
                }
            }
            
            $summary_data['status'] = 'in_progress';
            $summary_data['current_page'] = $page;
            $summary_data['last_update'] = date('Y-m-d H:i:s');
            $summary_data['resume_time'] = date('Y-m-d H:i:s');
            
            // Cập nhật phiên trong database
            $this->Topic_sync_log_model->update_session($session_id, $summary_data);
        }
        
        log_message('debug', 'Starting tags sync for controller ID: ' . $id . ', Session: ' . $session_id . ', Page: ' . $page);

        try {
            // Lấy connector dựa trên platform
            $connector = $this->_get_platform_connector($controller->platform);
            if (!$connector) {
                throw new Exception("Không tìm thấy connector cho platform: " . $controller->platform);
            }

            // Lấy thông tin đăng nhập
            $config = $this->Topic_controller_model->get_login_config($id);
            if (!$config) {
                throw new Exception("Không thể lấy thông tin đăng nhập cho controller ID: " . $id);
            }

            // Lấy blog_id từ controller (now stored in categories_state)
            $blog_id = 'default';
            if (!empty($controller->categories_state)) {
                $categories_state = json_decode($controller->categories_state, true);
                if (isset($categories_state['blog_id'])) {
                    $blog_id = $categories_state['blog_id'];
                }
            }

            // Tạo options cho request
            $options = [
                'page' => $page,
                'per_page' => $per_page
            ];

            // Lấy tags từ platform
            $tags_response = $connector->get_tags($config, $blog_id, $options);

            // Kiểm tra response
            if (!isset($tags_response['success']) || !$tags_response['success']) {
                $error_message = isset($tags_response['message']) ? $tags_response['message'] : 'Không thể lấy tags từ platform';
                throw new Exception($error_message);
            }

            // Lấy dữ liệu tags
            $tags = $tags_response['data']['tags'] ?? [];
            $total_tags = $tags_response['data']['total'] ?? 0;
            $total_pages = $tags_response['data']['total_pages'] ?? 0;

            // Cập nhật thông tin tổng số tags và trang
            $summary_data['total_tags'] = $total_tags;
            $summary_data['total_pages'] = $total_pages;
            
            // Kiểm tra và khởi tạo processed_tags nếu chưa tồn tại
            if (!isset($summary_data['processed_tags'])) {
                $summary_data['processed_tags'] = 0;
            }
            $summary_data['processed_tags'] += count($tags);
            
            $this->Topic_sync_log_model->update_session($session_id, $summary_data);

            // Log thông tin
            $log_entry = [
                'message' => 'Đã lấy ' . count($tags) . ' tags từ trang ' . $page . '/' . $total_pages,
                'type' => 'info',
                'details' => [
                    'page' => $page,
                    'total_pages' => $total_pages,
                    'tags_count' => count($tags),
                    'total_tags' => $total_tags
                ]
            ];
            $this->Topic_sync_log_model->update_session($session_id, $summary_data, $log_entry);

            // Lưu tags vào database
            if (!empty($tags)) {
                log_message('debug', 'Saving ' . count($tags) . ' tags for controller ID: ' . $id);
                
                // Chuẩn bị dữ liệu tags để lưu
                $tags_to_save = [];
                foreach ($tags as $tag) {
                    $tags_to_save[] = [
                        'tag_id' => $tag['id'],
                        'name' => $tag['name'],
                        'slug' => $tag['slug'] ?? sanitize_title($tag['name']),
                        'count' => $tag['count'] ?? 0,
                        'url' => $tag['url'] ?? ''
                    ];
                }
                
                // Lưu tags vào database
                $this->load->model('Topic_controller_model');
                $save_result = $this->Topic_controller_model->save_tags($id, $tags_to_save, $session_id);
                
                if ($save_result) {
                    // Lấy số lượng tags đã thêm/cập nhật
                    $inserted_count = $save_result['inserted'] ?? 0;
                    $updated_count = $save_result['updated'] ?? 0;
                    $total_processed = $inserted_count + $updated_count;
                    
                    // Cập nhật số lượng tags thành công (sử dụng giá trị tuyệt đối thay vì cộng dồn)
                    $success_count = $this->Topic_sync_log_model->get_processed_tags_count($session_id);
                    $summary_data['success_count'] = $success_count;
                    
                    // Tăng đếm số lượng tags đã xử lý
                    $this->Topic_sync_log_model->increment_processed_count($session_id, $total_processed);
                    
                    $log_entry = [
                        'message' => 'Đã lưu ' . $total_processed . ' tags vào database (Thêm mới: ' . $inserted_count . ', Cập nhật: ' . $updated_count . ')',
                        'type' => 'success',
                        'details' => [
                            'inserted_count' => $inserted_count,
                            'updated_count' => $updated_count
                        ]
                    ];
                } else {
                    // Cập nhật số lượng tags lỗi
                    $summary_data['error_count'] = isset($summary_data['error_count']) ? $summary_data['error_count'] + count($tags) : count($tags);
                    
                    $log_entry = [
                        'message' => 'Lỗi khi lưu tags vào database',
                        'type' => 'error',
                        'details' => [
                            'error' => 'Database error'
                        ]
                    ];
                }
                $this->Topic_sync_log_model->update_session($session_id, $summary_data, $log_entry);
            }

            // Kiểm tra xem đã đồng bộ hết các trang chưa
            if ($page >= $total_pages) {
                // Đã hoàn thành đồng bộ
                $this->Topic_sync_log_model->complete_session($session_id, 'completed');
                
                // Đặt flag cho biết đây là đồng bộ đầy đủ để xóa các tags cũ
                $this->session->set_userdata('is_full_sync_tags_' . $id, true);
                log_message('debug', 'Set full sync flag for controller ID: ' . $id);
                
                // Cập nhật thời gian đồng bộ tags trong bảng controller
                $this->db->where('id', $id)
                        ->update(db_prefix() . 'topic_controllers', [
                            'tags_last_sync' => date('Y-m-d H:i:s')
                        ]);
                
                // Trả về kết quả thành công
                if ($this->input->is_ajax_request()) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Đồng bộ tags hoàn tất',
                        'session_id' => $session_id,
                        'is_completed' => true,
                        'current_page' => $page,
                        'total_pages' => $total_pages
                    ]);
                } else {
                    // Even for non-AJAX requests, return JSON to support the AJAX-based approach
                    echo json_encode([
                        'success' => true,
                        'message' => 'Đồng bộ tags hoàn tất',
                        'session_id' => $session_id,
                        'is_completed' => true,
                        'current_page' => $page,
                        'total_pages' => $total_pages
                    ]);
                }
            } else {
                // Chưa hoàn thành, cần đồng bộ trang tiếp theo
                if ($this->input->is_ajax_request()) {
                    // Kiểm tra xem có tham số "auto_continue" không
                    $auto_continue = $this->input->get('auto_continue');
                    
                    // Nếu có tham số "pending" trong query string, đánh dấu trạng thái là đang chờ xử lý
                    $pending = $this->input->get('pending');
                    
                    if ($pending) {
                        // Cập nhật trạng thái phiên thành "pending"
                        $this->Topic_sync_log_model->update_session($session_id, [
                            'status' => 'pending',
                            'last_update' => date('Y-m-d H:i:s'),
                            'last_update_timestamp' => time()
                        ], [
                            'message' => 'Phiên đồng bộ chuyển sang trạng thái chờ xử lý',
                            'type' => 'warning'
                        ]);
                        
                        echo json_encode([
                            'success' => true,
                            'message' => 'Đang chờ xử lý dữ liệu',
                            'session_id' => $session_id,
                            'is_completed' => false,
                            'current_page' => $page,
                            'next_page' => $page + 1,
                            'total_pages' => $total_pages,
                            'status' => 'pending',
                            'data' => [
                                'tags' => [],  // Không trả về tags trong trạng thái pending
                                'next_page' => $page + 1,
                                'is_completed' => false,
                                'total_pages' => $total_pages,
                                'status' => 'pending'
                            ]
                        ]);
                    } else {
                        echo json_encode([
                            'success' => true,
                            'message' => 'Đang đồng bộ tags...',
                            'session_id' => $session_id,
                            'is_completed' => false,
                            'current_page' => $page,
                            'next_page' => $page + 1,
                            'total_pages' => $total_pages,
                            'data' => [
                                'tags' => $tags,
                                'next_page' => $page + 1,
                                'is_completed' => false,
                                'total_pages' => $total_pages,
                                'total_processed_tags' => $summary_data['processed_tags']
                            ]
                        ]);
                    }
                } else {
                    // Chuyển hướng đến trang tiếp theo
                    redirect(admin_url('topics/controllers/sync_tags/' . $id . '?page=' . ($page + 1)));
                }
            }
        } catch (Exception $e) {
            log_message('error', 'Error syncing tags: ' . $e->getMessage());
            
            // Ghi log lỗi
            $log_entry = [
                'message' => 'Lỗi khi đồng bộ tags: ' . $e->getMessage(),
                'type' => 'error',
                'details' => [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]
            ];
            $this->Topic_sync_log_model->update_session($session_id, $summary_data, $log_entry);
            
            // Đánh dấu phiên đồng bộ thất bại
            $this->Topic_sync_log_model->complete_session($session_id, 'failed');
            
            // Trả về lỗi
            if ($this->input->is_ajax_request()) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Lỗi khi đồng bộ tags: ' . $e->getMessage(),
                    'session_id' => $session_id
                ]);
            } else {
                set_alert('danger', 'Lỗi khi đồng bộ tags: ' . $e->getMessage());
                redirect(admin_url('topics/controllers/view/' . $id . '?group=tags'));
            }
        }
    }

    /**
     * Cancel tags sync session
     * @param string $session_id Session ID
     * @return void
     */
    public function cancel_tags_sync($session_id)
    {
        if (!has_permission('topics', '', 'edit')) {
            ajax_access_denied();
        }

        $this->load->model('Topic_sync_log_model');
        
        // Kiểm tra xem phiên đồng bộ có tồn tại không
        $session = $this->Topic_sync_log_model->get_session($session_id);
        
        if (!$session) {
            echo json_encode([
                'success' => false,
                'message' => 'Không tìm thấy phiên đồng bộ'
            ]);
            return;
        }
        
        // Đánh dấu phiên đồng bộ đã hủy
        $result = $this->Topic_sync_log_model->update_session($session_id, [
            'status' => 'cancelled',
            'end_time' => date('Y-m-d H:i:s')
        ], [
            'message' => 'Người dùng đã hủy quá trình đồng bộ',
            'type' => 'warning'
        ]);
        
        if ($result) {
            // Cập nhật phiên đồng bộ thành 'completed_with_cancel'
            $this->Topic_sync_log_model->complete_session($session_id, 'cancelled');
            
            // Đặt tags_sync_session_id thành NULL trong bảng topic_controllers
            $controller_id = $session->controller_id;
            $this->db->where('id', $controller_id)
                    ->update(db_prefix() . 'topic_controllers', [
                        'tags_sync_session_id' => NULL
                    ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Phiên đồng bộ đã được hủy'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Không thể hủy phiên đồng bộ'
            ]);
        }
    }

    /**
     * Update tags sync status
     * @param string $session_id Session ID
     * @return void
     */
    public function update_tags_sync_status($session_id)
    {
        if (!has_permission('topics', '', 'edit')) {
            ajax_access_denied();
        }

        $this->load->model('Topic_sync_log_model');
        
        // Lấy trạng thái từ request
        $status = $this->input->post('status');
        
        // Các trạng thái hợp lệ
        $valid_statuses = ['in_progress', 'completed', 'pending', 'cancelled', 'failed'];
        
        if (!in_array($status, $valid_statuses)) {
            echo json_encode([
                'success' => false,
                'message' => 'Trạng thái không hợp lệ'
            ]);
            return;
        }
        
        // Lấy thông tin phiên hiện tại
        $session = $this->Topic_sync_log_model->get_session($session_id);
        
        if (!$session) {
            echo json_encode([
                'success' => false,
                'message' => 'Không tìm thấy phiên đồng bộ'
            ]);
            return;
        }
        
        // Cập nhật trạng thái
        $summary_data = json_decode($session->summary_data, true);
        $summary_data['status'] = $status;
        $summary_data['last_update'] = date('Y-m-d H:i:s');
        $summary_data['last_update_timestamp'] = time();  // Thêm timestamp để dễ dàng tính toán thời gian
        
        // Nếu trạng thái là completed, cancelled, hoặc failed, cập nhật thời gian kết thúc
        if (in_array($status, ['completed', 'cancelled', 'failed'])) {
            $summary_data['end_time'] = date('Y-m-d H:i:s');
            
            // Đối với completed, cancelled, hoặc failed, cập nhật bảng controller
            $controller_id = $session->controller_id;
            $this->db->where('id', $controller_id)
                    ->update(db_prefix() . 'topic_controllers', [
                        'tags_sync_session_id' => NULL
                    ]);
            
            // Chỉ đối với completed, cập nhật thời gian đồng bộ tags và đặt flag
            if ($status == 'completed') {
                $this->db->where('id', $controller_id)
                        ->update(db_prefix() . 'topic_controllers', [
                            'tags_last_sync' => date('Y-m-d H:i:s')
                        ]);
                
                // Đặt flag cho biết đây là đồng bộ đầy đủ để xóa các tags cũ
                $this->session->set_userdata('is_full_sync_tags_' . $controller_id, true);
            }
        } 
        // Đối với trạng thái pending, không thay đổi tags_sync_session_id
        else if ($status == 'pending') {
            // Log thông tin pending để debug
            log_message('debug', 'Session ' . $session_id . ' set to pending status. Will continue from page ' . 
                ($summary_data['current_page'] + 1) . ' of ' . $summary_data['total_pages']);
        }
        
        // Lấy current_page và total_pages từ request nếu có
        $current_page = $this->input->post('current_page');
        $total_pages = $this->input->post('total_pages');
        
        if ($current_page) {
            $summary_data['current_page'] = $current_page;
        }
        
        if ($total_pages) {
            $summary_data['total_pages'] = $total_pages;
        }
        
        // Cập nhật phiên
        $result = $this->Topic_sync_log_model->update_session($session_id, $summary_data, [
            'message' => 'Cập nhật trạng thái thành: ' . $status,
            'type' => ($status == 'completed' ? 'success' : 
                      ($status == 'failed' ? 'error' : 
                      ($status == 'pending' ? 'warning' : 'info')))
        ]);
        
        if ($result) {
            // Nếu trạng thái là completed, cancelled hoặc failed, gọi hàm complete_session
            if (in_array($status, ['completed', 'cancelled', 'failed'])) {
                $this->Topic_sync_log_model->complete_session($session_id, $status);
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Đã cập nhật trạng thái thành công',
                'status' => $status
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Không thể cập nhật trạng thái'
            ]);
        }
    }

    /**
     * Get platform connector based on platform name
     * @param string $platform Platform name
     * @return object|null Platform connector object or null if not found
     */
    private function _get_platform_connector($platform)
    {
        log_message('debug', 'Getting platform connector for: ' . $platform);
        
        // Kiểm tra xem platform có hợp lệ không
        if (empty($platform)) {
            log_message('error', 'Empty platform name provided');
            return null;
        }
        
        // Chuẩn hóa tên platform (chuyển về chữ thường)
        $platform = strtolower($platform);
        
        // Tìm file connector
        $connector_file = FCPATH . 'modules/topics/includes/platform_connectors/' . $platform . '_connector.php';
        
        // Kiểm tra xem file connector có tồn tại không
        if (!file_exists($connector_file)) {
            log_message('error', 'Connector file not found: ' . $connector_file);
            return null;
        }
        
        // Tạo các tên class có thể có từ tên platform
        $class_names = [
            ucfirst($platform) . 'Connector',  // Ví dụ: WordpressConnector
            ucwords($platform) . 'Connector',  // Ví dụ: WordPressConnector
            ucfirst($platform) . '_Connector'  // Ví dụ: Wordpress_Connector
        ];
        
        // Kiểm tra từng tên class
        $found_class = null;
        
        // Load file connector
        require_once($connector_file);
        
        foreach ($class_names as $class_name) {
            if (class_exists($class_name, false)) {
                $found_class = $class_name;
                break;
            }
        }
        
        if (!$found_class) {
            log_message('error', 'Connector class not found for platform: ' . $platform . '. Tried: ' . implode(', ', $class_names));
            return null;
        }
        
        // Tạo instance của connector
        try {
            $connector = new $found_class();
            log_message('debug', 'Created connector instance: ' . $found_class);
            return $connector;
        } catch (Exception $e) {
            log_message('error', 'Error creating connector instance: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Validate session status
     * Checks the current status of a sync session and returns appropriate information
     * @param string $session_id Session ID
     * @return array Information about the session status
     */
    public function validate_session_status($session_id)
    {
        if (!has_permission('topics', '', 'view')) {
            ajax_access_denied();
        }

        $this->load->model('Topic_sync_log_model');
        
        // Kiểm tra xem phiên đồng bộ có tồn tại không
        $session = $this->Topic_sync_log_model->get_session($session_id);
        
        if (!$session) {
            echo json_encode([
                'success' => false,
                'message' => 'Không tìm thấy phiên đồng bộ',
                'status' => 'not_found'
            ]);
            return;
        }
        
        // Lấy dữ liệu tóm tắt
        $summary_data = json_decode($session->summary_data, true);
        $status = $summary_data['status'] ?? $session->status;
        
        // Chuẩn bị dữ liệu trả về
        $response = [
            'success' => true,
            'session_id' => $session_id,
            'status' => $status,
            'controller_id' => $session->controller_id,
            'current_page' => $summary_data['current_page'] ?? 1,
            'total_pages' => $summary_data['total_pages'] ?? 1,
            'total_processed' => $summary_data['processed_tags'] ?? 0,
            'last_update' => $summary_data['last_update'] ?? $session->dateupdated
        ];
        
        // Xử lý theo từng trạng thái
        if ($status == 'pending') {
            $response['message'] = 'Phiên đồng bộ đang chờ tiếp tục từ trang ' . 
                $response['current_page'] . '/' . $response['total_pages'];
            $response['next_page'] = ($response['current_page'] + 1);
            $response['can_continue'] = true;
        } else if ($status == 'in_progress') {
            $response['message'] = 'Phiên đồng bộ đang diễn ra';
            $response['can_continue'] = false;
        } else if ($status == 'completed') {
            $response['message'] = 'Phiên đồng bộ đã hoàn thành';
            $response['can_continue'] = false;
        } else if ($status == 'cancelled') {
            $response['message'] = 'Phiên đồng bộ đã bị hủy';
            $response['can_continue'] = false;
        } else if ($status == 'failed') {
            $response['message'] = 'Phiên đồng bộ đã thất bại';
            $response['can_continue'] = false;
        } else {
            $response['message'] = 'Trạng thái phiên đồng bộ: ' . $status;
            $response['can_continue'] = false;
        }
        
        echo json_encode($response);
    }

    /**
     * Get sync session details
     * @param int $controller_id Controller ID
     * @return void
     */
    public function get_sync_session_details($controller_id)
    {
        if (!has_permission('topics', '', 'view')) {
            ajax_access_denied();
        }

        $this->load->model('Topic_sync_log_model');
        
        // Lấy session_id từ request
        $session_id = $this->input->get('session_id');
        
        if (!$session_id) {
            echo json_encode([
                'success' => false,
                'message' => 'Session ID không được cung cấp'
            ]);
            return;
        }
        
        // Lấy thông tin phiên
        $session = $this->Topic_sync_log_model->get_session($session_id);
        
        if (!$session) {
            echo json_encode([
                'success' => false,
                'message' => 'Không tìm thấy phiên đồng bộ'
            ]);
            return;
        }
        
        // Kiểm tra xem session có phải của controller này không
        if ($session->controller_id != $controller_id) {
            echo json_encode([
                'success' => false,
                'message' => 'Phiên đồng bộ không thuộc về controller này'
            ]);
            return;
        }
        
        // Lấy thông tin chi tiết
        $summary_data = json_decode($session->summary_data, true);
        
        // Lấy logs từ log_data trong session
        $log_data = json_decode($session->log_data, true);
        $logs = isset($log_data['logs']) ? $log_data['logs'] : [];
        
        echo json_encode([
            'success' => true,
            'summary' => $summary_data,
            'logs' => $logs
        ]);
    }

    /**
     * Get tags data for DataTable server-side processing
     * @param int $id Controller ID
     * @return void
     */
    public function get_tags_table($id)
    {
        if (!has_permission('topics', '', 'view')) {
            ajax_access_denied();
        }

        $controller = $this->Topic_controller_model->get($id);
        if (!$controller) {
            echo json_encode([
                'draw' => intval($this->input->get('draw')),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => []
            ]);
            return;
        }

        $this->load->model('Topic_controller_model');
        
        // Get search value
        $search = $this->input->get('search');
        $search_value = $search['value'] ?? '';
        
        // Get order
        $order = $this->input->get('order');
        $order_column = $order[0]['column'] ?? 1; // Default order by name column
        $order_dir = $order[0]['dir'] ?? 'asc'; // Default ascending
        
        // Get limit
        $start = $this->input->get('start') ?? 0;
        $length = $this->input->get('length') ?? 25;
        
        // Map columns for ordering
        $columns = [
            0 => 'tag_id',
            1 => 'name',
            2 => 'slug',
            3 => 'count'
        ];
        
        $order_by = $columns[$order_column] ?? 'name';
        
        // Get total records count
        $total_records = $this->Topic_controller_model->get_tags_count($id);
        
        // Get filtered records
        $filtered_tags = $this->Topic_controller_model->get_tags_for_table(
            $id, 
            $search_value, 
            $order_by, 
            $order_dir, 
            $start, 
            $length
        );
        
        // Count filtered records
        $filtered_count = $this->Topic_controller_model->get_filtered_tags_count($id, $search_value);
        
        // Format data for DataTable
        $data = [];
        foreach ($filtered_tags as $tag) {
            // Sử dụng url đã được lưu trong bảng
            $url = $tag['url'] ?? '';
            
            // Nếu url rỗng, xây dựng từ thông tin controller và slug
            if (empty($url)) {
                // Lấy site từ controller làm domain
                $site = $controller->site ?? '';
                if (!empty($site)) {
                    // Đảm bảo site có https:// ở đầu
                    if (strpos($site, 'http') !== 0) {
                        $site = 'https://' . $site;
                    }
                    $url = rtrim($site, '/') . '/tag/' . $tag['slug'];
                }
            }
            
            $data[] = [
                'tag_id' => $tag['tag_id'],
                'name' => $tag['name'],
                'slug' => $tag['slug'],
                'count' => $tag['count'],
                'url' => $url
            ];
        }
        
        echo json_encode([
            'draw' => intval($this->input->get('draw')),
            'recordsTotal' => $total_records,
            'recordsFiltered' => $filtered_count,
            'data' => $data
        ]);
    }

    /**
     * Get tags sync state (last sync time) for a controller
     * @param int $id Controller ID
     * @return void
     */
    public function get_tags_sync_state($id)
    {
        if (!has_permission('topics', '', 'view')) {
            ajax_access_denied();
        }

        $controller = $this->Topic_controller_model->get($id);
        if (!$controller) {
            echo json_encode([
                'success' => false,
                'message' => _l('controller_not_found')
            ]);
            return;
        }

        echo json_encode([
            'success' => true,
            'last_sync' => $controller->tags_last_sync ? _dt($controller->tags_last_sync) : _l('never_synced')
        ]);
    }

    /**
     * Get action buttons for controller
     * @param int $controller_id Controller ID
     */
    public function get_action_buttons($controller_id)
    {
        if (!has_permission('topics', '', 'view')) {
            ajax_access_denied();
        }

        $this->load->model('Topic_controller_action_button_model');
        $this->load->model('Topic_action_button_model');
        
        if ($this->input->is_ajax_request()) {
            $action_buttons = $this->Topic_controller_action_button_model->get_action_buttons_by_controller($controller_id);
            
            $data = [];
            foreach ($action_buttons as $button) {
                $row = [];
                
                // Action Button Name
                $row[] = '<a href="#" onclick="edit_action_button_assignment(' . $button['id'] . '); return false;" class="tw-text-neutral-500 hover:tw-text-neutral-700 focus:tw-text-neutral-700">' . html_escape($button['name']) . '</a>';
                
                // Button Type
                $row[] = '<span class="label label-' . html_escape($button['button_type']) . '">' . 
                         ucfirst(html_escape($button['button_type'])) . '</span>';
                
                // Workflow ID
                $row[] = '<span class="text-nowrap">' . html_escape($button['workflow_id']) . '</span>';
                
                // Action Command
                $row[] = '<span class="text-nowrap">' . 
                         ($button['action_command'] ? html_escape($button['action_command']) : '-') . '</span>';
                
                // Target Action Type
                $row[] = '<span class="text-nowrap">' . 
                         ($button['target_action_type'] ? html_escape($button['target_action_type']) : '-') . '</span>';
                
                // Target Action State
                $row[] = '<span class="text-nowrap">' . 
                         ($button['target_action_state'] ? html_escape($button['target_action_state']) : '-') . '</span>';
                
                // Status toggle switch
                $row[] = '<div class="onoffswitch">
                            <input type="checkbox" data-switch-url="' . admin_url('topics/controllers/change_button_assignment_status') . '" 
                                   name="onoffswitch" class="onoffswitch-checkbox" 
                                   id="assignment_status_' . $button['id'] . '" 
                                   data-id="' . $button['id'] . '" ' . 
                                   ($button['status'] == 1 ? 'checked' : '') . '>
                            <label class="onoffswitch-label" for="assignment_status_' . $button['id'] . '"></label>
                          </div>';
                
                // Order
                $row[] = '<span class="text-nowrap order-handle">' . html_escape($button['order']) . '</span>';
                
                // Options column
                $options = '';
                if (has_permission('topics', '', 'edit')) {
                    $options .= '<a href="#" onclick="edit_action_button_assignment(' . $button['id'] . '); return false;" 
                                  class="tw-text-neutral-500 hover:tw-text-neutral-700 focus:tw-text-neutral-700 btn btn-xs btn-default">
                                  <i class="fa fa-pencil-square-o"></i>
                               </a>';
                }
                if (has_permission('topics', '', 'delete')) {
                    $options .= ' <a href="' . admin_url('topics/controllers/delete_action_button_assignment/' . $button['id']) . '" 
                                  class="tw-text-neutral-500 hover:tw-text-neutral-700 focus:tw-text-neutral-700 btn btn-xs btn-danger _delete">
                                  <i class="fa fa-remove"></i>
                               </a>';
                }
                $row[] = '<div class="tw-flex tw-items-center tw-space-x-3">' . $options . '</div>';
                
                $data[] = $row;
            }

            echo json_encode(['data' => $data]);
            return;
        }

        $data = [
            'controller_id' => $controller_id,
            'controller' => $this->Topic_controller_model->get($controller_id),
            'action_buttons' => $this->Topic_controller_action_button_model->get_action_buttons_by_controller($controller_id),
            'available_buttons' => $this->Topic_action_button_model->get(), // All action buttons including controller_only ones
            'title' => _l('controller_action_buttons')
        ];
        
        $this->load->view('controllers/action_buttons', $data);
    }

    /**
     * Add action button to controller
     * @param int $controller_id Controller ID
     */
    public function add_action_button_to_controller($controller_id)
    {
        if (!has_permission('topics', '', 'edit')) {
            ajax_access_denied();
        }
        
        $this->load->model('Topic_controller_action_button_model');
        
        if ($this->input->post()) {
            $action_button_id = $this->input->post('action_button_id');
            
            if (!$action_button_id) {
                echo json_encode([
                    'success' => false,
                    'message' => _l('action_button_id_required')
                ]);
                return;
            }
            
            $data = [
                'controller_id' => $controller_id,
                'action_button_id' => $action_button_id,
                'status' => 1,
                'order' => $this->input->post('order') ?? 0
            ];
            
            $id = $this->Topic_controller_action_button_model->add($data);
            
            if ($id) {
                echo json_encode([
                    'success' => true,
                    'message' => _l('action_button_added_to_controller')
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => _l('action_button_already_assigned_or_error')
                ]);
            }
            
            return;
        }
        
        // Load view cho form thêm action button
        $this->load->model('Topic_action_button_model');
        
        // Lấy các action button chưa được gán cho controller này
        $this->db->select('ab.*');
        $this->db->from(db_prefix() . 'topic_action_buttons ab');
        $this->db->where('ab.status', 1);
        $this->db->where('ab.controller_only', 1);
        $this->db->where_not_in('ab.id', 'SELECT action_button_id FROM ' . db_prefix() . 'topic_controller_action_buttons WHERE controller_id = ' . $controller_id, false);
        $available_buttons = $this->db->get()->result_array();
        
        $data = [
            'controller_id' => $controller_id,
            'controller' => $this->Topic_controller_model->get($controller_id),
            'available_buttons' => $available_buttons,
            'title' => _l('add_action_button_to_controller')
        ];
        
        $this->load->view('controllers/action_button_assignment_modal', $data);
    }

    /**
     * Edit action button assignment
     * @param int $id Assignment ID
     */
    public function edit_action_button_assignment($id)
    {
        if (!has_permission('topics', '', 'edit')) {
            ajax_access_denied();
        }
        
        $this->load->model('Topic_controller_action_button_model');
        
        if ($this->input->post()) {
            $data = [
                'status' => $this->input->post('status'),
                'order' => $this->input->post('order')
            ];
            
            if ($this->Topic_controller_action_button_model->update($data, $id)) {
                echo json_encode([
                    'success' => true,
                    'message' => _l('updated_successfully')
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => _l('update_failed')
                ]);
            }
            
            return;
        }
        
        $assignment = $this->Topic_controller_action_button_model->get_by_id($id);
        if (!$assignment) {
            show_404();
        }
        
        // Lấy thông tin đầy đủ về assignment
        $this->db->select('cab.*, ab.name as button_name, ab.button_type, ab.workflow_id, ab.action_command');
        $this->db->from(db_prefix() . 'topic_controller_action_buttons cab');
        $this->db->join(db_prefix() . 'topic_action_buttons ab', 'cab.action_button_id = ab.id');
        $this->db->where('cab.id', $id);
        $assignment = $this->db->get()->row_array();
        
        if (!$assignment) {
            show_404();
        }
        
        $data = [
            'assignment' => $assignment,
            'controller' => $this->Topic_controller_model->get($assignment['controller_id']),
            'title' => _l('edit_action_button_assignment')
        ];
        
        $this->load->view('controllers/action_button_assignment_edit_modal', $data);
    }

    /**
     * Delete action button assignment
     * @param int $id Assignment ID
     */
    public function delete_action_button_assignment($id)
    {
        if (!has_permission('topics', '', 'delete')) {
            ajax_access_denied();
        }
        
        $this->load->model('Topic_controller_action_button_model');
        
        if ($this->Topic_controller_action_button_model->delete($id)) {
            set_alert('success', _l('deleted_successfully'));
        } else {
            set_alert('warning', _l('problem_deleting'));
        }
        
        redirect(admin_url('topics/controllers'));
    }

    /**
     * Change status of action button assignment
     */
    public function change_button_assignment_status()
    {
        if (!has_permission('topics', '', 'edit')) {
            ajax_access_denied();
        }
        
        $this->load->model('Topic_controller_action_button_model');
        
        $id = $this->input->post('id');
        $status = $this->input->post('status');
        
        // Validate status
        $status = intval($status);
        if ($status !== 0 && $status !== 1) {
            echo json_encode([
                'success' => false,
                'message' => _l('invalid_status')
            ]);
            return;
        }
        
        if ($this->Topic_controller_action_button_model->change_status($id, $status)) {
            echo json_encode([
                'success' => true,
                'message' => _l('status_changed_successfully')
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => _l('failed_to_change_status')
            ]);
        }
    }

    /**
     * Update order of action button assignments
     */
    public function update_button_assignment_order()
    {
        if (!has_permission('topics', '', 'edit')) {
            ajax_access_denied();
        }
        
        $this->load->model('Topic_controller_action_button_model');
        
        $orders = $this->input->post('orders');
        
        if ($this->Topic_controller_action_button_model->update_order($orders)) {
            echo json_encode([
                'success' => true,
                'message' => _l('order_updated_successfully')
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => _l('failed_to_update_order')
            ]);
        }
    }

    /**
     * Get all actors for a specific controller
     */
    public function get_actors()
    {
        if (!has_permission('topics', '', 'view')) {
            ajax_access_denied();
        }

        $controller_id = $this->input->get('controller_id');

        if (!$controller_id) {
            echo json_encode([
                'success' => false,
                'message' => _l('missing_controller_id')
            ]);
            return;
        }

        $this->load->model('Topic_controller_actor_model');
        $actors = $this->Topic_controller_actor_model->get_actors_by_controller($controller_id);

        // Generate HTML for actors list
        $html = '';
        if (!empty($actors)) {
            foreach ($actors as $actor) {
                $html .= '<div class="actor-container panel panel-default" data-id="' . $actor['id'] . '">';
                $html .= '<div class="panel-heading">';
                $html .= '<div class="actor-header">';
                $html .= '<span class="actor-name"><i class="fa fa-user"></i> ' . html_escape($actor['name']) . '</span>';
                $html .= '<div class="actor-controls pull-right">';
                $html .= '<div class="onoffswitch">';
                $html .= '<input type="checkbox" data-id="' . $actor['id'] . '" class="onoffswitch-checkbox actor-status" id="actor_status_' . $actor['id'] . '" ' . ($actor['active'] ? 'checked' : '') . '>';
                $html .= '<label class="onoffswitch-label" for="actor_status_' . $actor['id'] . '"></label>';
                $html .= '</div>';
                $html .= '<button type="button" class="btn btn-xs btn-default edit-actor" data-id="' . $actor['id'] . '">';
                $html .= '<i class="fa fa-pencil-square"></i>';
                $html .= '</button>';
                $html .= '<button type="button" class="btn btn-xs btn-danger delete-actor" data-id="' . $actor['id'] . '">';
                $html .= '<i class="fa fa-trash"></i>';
                $html .= '</button>';
                $html .= '<button type="button" class="btn btn-xs btn-default move-actor">';
                $html .= '<i class="fa fa-arrows-v"></i>';
                $html .= '</button>';
                $html .= '</div>';
                $html .= '</div>';
                $html .= '</div>';
                $html .= '<div class="panel-body">';
                $html .= '<div class="actor-description">';
                // Don't escape HTML content from TinyMCE
                $html .= $actor['description'];
                $html .= '</div>';
                $html .= '<div class="actor-priority">';
                $html .= '<span class="label label-default">' . _l('priority') . ': ' . $actor['priority'] . '</span>';
                $html .= '</div>';
                $html .= '</div>';
                $html .= '</div>';
            }
        }

        echo json_encode([
            'success' => true,
            'html' => $html,
            'count' => count($actors)
        ]);
    }

    /**
     * Get a specific actor
     */
    public function get_actor()
    {
        if (!has_permission('topics', '', 'view')) {
            ajax_access_denied();
        }

        $id = $this->input->get('id');

        if (!$id) {
            echo json_encode([
                'success' => false,
                'message' => _l('missing_actor_id')
            ]);
            return;
        }

        $this->load->model('Topic_controller_actor_model');
        $actor = $this->Topic_controller_actor_model->get($id);

        if (!$actor) {
            echo json_encode([
                'success' => false,
                'message' => _l('actor_not_found')
            ]);
            return;
        }

        echo json_encode([
            'success' => true,
            'data' => $actor
        ]);
    }

    /**
     * Save (add or update) an actor
     */
    public function save_actor()
    {
        if (!has_permission('topics', '', 'edit')) {
            ajax_access_denied();
        }

        $data = $this->input->post();

        if (empty($data['name'])) {
            echo json_encode([
                'success' => false,
                'message' => _l('name_required')
            ]);
            return;
        }

        $this->load->model('Topic_controller_actor_model');

        // Set active value if checkbox was not checked
        if (!isset($data['active'])) {
            $data['active'] = 0;
        }

        // Format data for database
        $save_data = [
            'controller_id' => $data['controller_id'],
            'name' => $data['name'],
            'description' => $data['description'],
            'priority' => $data['priority'],
            'active' => $data['active']
        ];

        // Add or update
        if (empty($data['id'])) {
            // Add new actor
            $id = $this->Topic_controller_actor_model->add($save_data);
            $message = _l('actor_added_successfully');
        } else {
            // Update existing actor
            $id = $data['id'];
            $success = $this->Topic_controller_actor_model->update($id, $save_data);
            $message = _l('actor_updated_successfully');
        }

        if ($id) {
            echo json_encode([
                'success' => true,
                'message' => $message,
                'id' => $id
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => empty($data['id']) ? _l('error_adding_actor') : _l('error_updating_actor')
            ]);
        }
    }

    /**
     * Delete an actor
     */
    public function delete_actor()
    {
        if (!has_permission('topics', '', 'delete')) {
            ajax_access_denied();
        }

        $id = $this->input->post('id');

        if (!$id) {
            echo json_encode([
                'success' => false,
                'message' => _l('missing_actor_id')
            ]);
            return;
        }

        $this->load->model('Topic_controller_actor_model');
        $success = $this->Topic_controller_actor_model->delete($id);

        if ($success) {
            echo json_encode([
                'success' => true,
                'message' => _l('actor_deleted_successfully')
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => _l('error_deleting_actor')
            ]);
        }
    }

    /**
     * Update actor status (active/inactive)
     */
    public function update_actor_status()
    {
        if (!has_permission('topics', '', 'edit')) {
            ajax_access_denied();
        }

        $id = $this->input->post('id');
        $active = (int)$this->input->post('active');

        if (!$id) {
            echo json_encode([
                'success' => false,
                'message' => _l('missing_actor_id')
            ]);
            return;
        }

        $this->load->model('Topic_controller_actor_model');
        $success = $this->Topic_controller_actor_model->change_status($id, $active);

        if ($success) {
            echo json_encode([
                'success' => true,
                'message' => _l('status_changed_successfully')
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => _l('error_changing_status')
            ]);
        }
    }

    /**
     * Update actor priorities
     */
    public function update_actor_priorities()
    {
        if (!has_permission('topics', '', 'edit')) {
            ajax_access_denied();
        }

        $actors = json_decode($this->input->post('actors'), true);

        if (empty($actors)) {
            echo json_encode([
                'success' => false,
                'message' => _l('missing_required_fields')
            ]);
            return;
        }
        
        // Get the controller_id for the first actor
        $this->load->model('Topic_controller_actor_model');
        $first_actor = $this->Topic_controller_actor_model->get($actors[0]);
        
        if (!$first_actor) {
            echo json_encode([
                'success' => false,
                'message' => _l('actor_not_found')
            ]);
            return;
        }
        
        $controller_id = $first_actor->controller_id;
        $success = $this->Topic_controller_actor_model->update_priorities($controller_id, $actors);

        if ($success) {
            echo json_encode([
                'success' => true,
                'message' => _l('priorities_updated_successfully')
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => _l('error_updating_priorities')
            ]);
        }
    }
}
        