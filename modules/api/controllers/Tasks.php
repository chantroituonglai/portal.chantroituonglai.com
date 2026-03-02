<?php

defined('BASEPATH') OR exit('No direct script access allowed');

// This can be removed if you use __autoload() in config.php OR use Modular Extensions

/** @noinspection PhpIncludeInspection */
require __DIR__.'/REST_Controller.php';

/**
 * This is an example of a few basic user interaction methods you could use
 * all done with a hardcoded array
 *
 * @package         CodeIgniter
 * @subpackage      Rest Server
 * @category        Controller
 * @author          Phil Sturgeon, Chris Kacerguis
 * @license         MIT
 * @link            https://github.com/chriskacerguis/codeigniter-restserver
 */
class Tasks extends REST_Controller {
    /**
     * Indicates whether Chatpion Bridge module is active.
     *
     * @var bool
     */
    protected $chatpion_bridge_active = false;

    function __construct()
    {
        // Construct the parent class
        parent::__construct();

        $this->load->library('app_modules');
        if ($this->app_modules->is_active('chatpion_bridge')) {
            $this->load->model('chatpion_bridge/Chatpion_bridge_task_model', 'chatpionBridgeTaskModel');
            $this->load->model('chatpion_bridge/Chatpion_bridge_model', 'chatpionBridgeModel');
            $this->chatpion_bridge_active = true;
        }
    }

    /**
     * @api {get} api/tasks/:id Request Task information
     * @apiName GetTask
     * @apiGroup Tasks
     *
     * @apiHeader {String} Authorization Basic Access Authentication token.
     *
     * @apiParam {Number} id Task unique ID.
     *
     * @apiSuccess {Object} Tasks information.
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *         "id": "10",
     *         "name": "This is a task",
     *         "description": "",
     *         "priority": "2",
     *         "dateadded": "2019-02-25 12:26:37",
     *         "startdate": "2019-01-02 00:00:00",
     *         "duedate": "2019-01-04 00:00:00",
     *         "datefinished": null,
     *         "addedfrom": "9",
     *         "is_added_from_contact": "0",
     *         "status": "4",
     *         "recurring_type": null,
     *         "repeat_every": "0",
     *         "recurring": "0",
     *         "is_recurring_from": null,
     *         ...
     *     }
     *
     * @apiError {Boolean} status Request status.
     * @apiError {String} message No data were found.
     *
     * @apiErrorExample Error-Response:
     *     HTTP/1.1 404 Not Found
     *     {
     *       "status": false,
     *       "message": "No data were found"
     *     }
     */
    public function data_get($id = '')
    {
        // If the id parameter doesn't exist return all the
        $data = $this->Api_model->get_table('tasks', $id);

        // Check if the data store contains
        if ($data)
        {
            $data = $this->Api_model->get_api_custom_data($data, "tasks", $id);
            $data = $this->append_chatpion_campaign_data($data, $id);

            // Set the response and exit
            $this->response($data, REST_Controller::HTTP_OK); // OK (200) being the HTTP response code
        }
        else
        {
            // Set the response and exit
            $this->response([
                'status' => FALSE,
                'message' => 'No data were found'
            ], REST_Controller::HTTP_NOT_FOUND); // NOT_FOUND (404) being the HTTP response code
        }
    }

    /**
     * @api {get} api/tasks/search/:keysearch Search Tasks Information
     * @apiName GetTaskSearch
     * @apiGroup Tasks
     *
     * @apiHeader {String} Authorization Basic Access Authentication token.
     *
     * @apiParam {String} keysearch Search Keywords.
     *
     * @apiSuccess {Object} Tasks information.
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *         "id": "10",
     *         "name": "This is a task",
     *         "description": "",
     *         "priority": "2",
     *         "dateadded": "2019-02-25 12:26:37",
     *         "startdate": "2019-01-02 00:00:00",
     *         "duedate": "2019-01-04 00:00:00",
     *         "datefinished": null,
     *         "addedfrom": "9",
     *         "is_added_from_contact": "0",
     *         "status": "4",
     *         "recurring_type": null,
     *         "repeat_every": "0",
     *         "recurring": "0",
     *         "is_recurring_from": null,
     *         ...
     *     }
     *
     * @apiError {Boolean} status Request status.
     * @apiError {String} message No data were found.
     *
     * @apiErrorExample Error-Response:
     *     HTTP/1.1 404 Not Found
     *     {
     *       "status": false,
     *       "message": "No data were found"
     *     }
     */
    public function data_search_get($key = '')
    {
        // If the id parameter doesn't exist return all the
        $data = $this->Api_model->search('tasks', $key);

        // Check if the data store contains
        if ($data)
        {
			usort($data, function($a, $b) {
				return $a['id'] - $b['id'];
			});
            $data = $this->Api_model->get_api_custom_data($data,"tasks");
            $data = $this->append_chatpion_campaign_data($data);

            // Set the response and exit
            $this->response($data, REST_Controller::HTTP_OK); // OK (200) being the HTTP response code
        } else {
            // Set the response and exit
            $this->response([
                'status' => FALSE,
                'message' => 'No data were found'
            ], REST_Controller::HTTP_NOT_FOUND); // NOT_FOUND (404) being the HTTP response code
        }
    }

    /**
     * @api {post} api/tasks Add New Task
     * @apiName PostTask
     * @apiGroup Tasks
     *
     * @apiHeader {String} Authorization Basic Access Authentication token.
     *
     * @apiParam {String} name              Mandatory Task Name.
     * @apiParam {Date} startdate           Mandatory Task Start Date.
     * @apiParam {String} [is_public]       Optional Task public.
     * @apiParam {String} [billable]        Optional Task billable.
     * @apiParam {String} [hourly_rate]     Optional Task hourly rate.
     * @apiParam {String} [milestone]       Optional Task milestone.
     * @apiParam {Date} [duedate]           Optional Task deadline.
     * @apiParam {String} [priority]        Optional Task priority.
     * @apiParam {String} [repeat_every]    Optional Task repeat every.
     * @apiParam {Number} [repeat_every_custom]     Optional Task repeat every custom.
     * @apiParam {String} [repeat_type_custom]      Optional Task repeat type custom.
     * @apiParam {Number} [cycles]                  Optional cycles.
     * @apiParam {string="lead","customer","invoice", "project", "quotation", "contract", "annex", "ticket", "expense", "proposal"} rel_type Mandatory Task Related.
     * @apiParam {Number} rel_id            Optional Related ID.
     * @apiParam {String} [tags]            Optional Task tags.
     * @apiParam {String} [description]     Optional Task description.
     *
     *
     * @apiParamExample {Multipart Form} Request-Example:
     *     array (size=15)
     *     'is_public' => string 'on' (length=2)
     *     'billable' => string 'on' (length=2)
     *     'name' => string 'Task 12' (length=7)
     *     'hourly_rate' => string '0' (length=1)
     *     'milestone' => string '' (length=0)
     *     'startdate' => string '17/07/2019' (length=10)
     *     'duedate' => string '31/07/2019 11:07' (length=16)
     *     'priority' => string '2' (length=1)
     *     'repeat_every' => string '' (length=0)
     *     'repeat_every_custom' => string '1' (length=1)
     *     'repeat_type_custom' => string 'day' (length=3)
     *     'rel_type' => string 'customer' (length=8)
     *     'rel_id' => string '9' (length=1)
     *     'tags' => string '' (length=0)
     *     'description' => string '<span>Task Description</span>' (length=29)
     *
     *
     * @apiSuccess {String} status Request status.
     * @apiSuccess {String} message Task add successful.
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *       "status": true,
     *       "message": "Task add successful."
     *     }
     *
     * @apiError {String} status Request status.
     * @apiError {String} message Task add fail.
     *
     * @apiErrorExample Error-Response:
     *     HTTP/1.1 404 Not Found
     *     {
     *       "status": false,
     *       "message": "Task add fail."
     *     }
     * 
     */
    public function data_post()
    {
        \modules\api\core\Apiinit::the_da_vinci_code('api');
        
        log_message('error', '[Perfex CRM API] ========== DATA_POST START ==========');
        log_message('error', '[Perfex CRM API] Raw $_POST at entry: ' . json_encode($_POST));
        log_message('error', '[Perfex CRM API] input->post() at entry: ' . json_encode($this->input->post()));
        
        // form validation
        $this->form_validation->set_rules('name', 'Task Name', 'trim|required|max_length[600]', array('is_unique' => 'This %s already exists please enter another Task Name'));
        $this->form_validation->set_rules('startdate', 'Task Start Date', 'trim|required', array('is_unique' => 'This %s already exists please enter another Task Start Date'));
        $this->form_validation->set_rules('is_public', 'Publicly available task', 'trim', array('is_unique' => 'Public state can be 1. Skip it completely to set it at non-public'));
        
        log_message('error', '[Perfex CRM API] About to run form validation...');
        
        if ($this->form_validation->run() == FALSE)
        {
            // form validation error
            log_message('error', '[Perfex CRM API] Form validation FAILED');
            log_message('error', '[Perfex CRM API] Validation errors: ' . json_encode($this->form_validation->error_array()));
            
            $message = array(
                'status' => FALSE,
                'error' => $this->form_validation->error_array(),
                'message' => validation_errors() 
            );
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        }
        else
        {
            log_message('error', '[Perfex CRM API] Form validation PASSED');
            // Log all POST data first
            log_message('error', '[Perfex CRM API] ========== DATA_POST DEBUG ==========');
            log_message('error', '[Perfex CRM API] Raw $_POST: ' . json_encode($_POST));
            log_message('error', '[Perfex CRM API] input->post() all: ' . json_encode($this->input->post()));
            
            $insert_data = [
                'name' => $this->input->post('name', TRUE),
                'startdate' => $this->input->post('startdate', TRUE),
                'is_public' => $this->input->post('is_public', TRUE),
                'billable' => $this->Api_model->value($this->input->post('billable', TRUE)),
                'hourly_rate' => $this->Api_model->value($this->input->post('hourly_rate', TRUE)),
                'milestone' => $this->Api_model->value($this->input->post('milestone', TRUE)),
                'duedate' => $this->Api_model->value($this->input->post('duedate', TRUE)),
                'priority' => $this->Api_model->value($this->input->post('priority', TRUE)),
                'repeat_every' => $this->Api_model->value($this->input->post('repeat_every', TRUE)),
                'repeat_every_custom' => $this->Api_model->value($this->input->post('repeat_every_custom', TRUE)),
                'repeat_type_custom' => $this->Api_model->value($this->input->post('repeat_type_custom', TRUE)),
                'cycles' => $this->Api_model->value($this->input->post('cycles', TRUE)),
                'rel_type' => $this->Api_model->value($this->input->post('rel_type', TRUE)),
                'rel_id' => $this->Api_model->value($this->input->post('rel_id', TRUE)),
                'tags' => $this->Api_model->value($this->input->post('tags', TRUE)),
                'description' => $this->Api_model->value($this->input->post('description', TRUE)),
                'status' => $this->Api_model->value($this->input->post('status', TRUE))
            ];
               
            if (!empty($this->input->post('custom_fields', TRUE))) {
                $insert_data['custom_fields'] = $this->Api_model->value($this->input->post('custom_fields', TRUE));
            }
            
            // Collect ChatPion bridge fields separately (not for task insert)
            log_message('error', '[Perfex CRM API] Checking for ChatPion fields...');
            $chatpion_data = [];
            $chatpion_fields = ['chatpion_campaign_id', 'chatpion_user_id', 'chatpion_platform', 'chatpion_sync_time', 'source'];
            foreach ($chatpion_fields as $field) {
                $value = $this->input->post($field, TRUE);
                log_message('error', '[Perfex CRM API] Field ' . $field . ' = ' . var_export($value, true));
                if (!empty($value)) {
                    $chatpion_data[$field] = $value;
                    log_message('error', '[Perfex CRM API] Saved ' . $field . ' to chatpion_data');
                }
            }
            
            log_message('error', '[Perfex CRM API] ChatPion data collected: ' . json_encode($chatpion_data));
            
            // insert data
            $this->load->model('tasks_model');
            
            log_message('error', '[Perfex CRM API] Received task creation request');
            log_message('error', '[Perfex CRM API] Insert data: ' . json_encode($insert_data));
            
            $output = $this->tasks_model->add($insert_data);
            
            log_message('error', '[Perfex CRM API] tasks_model->add() returned: ' . var_export($output, true));
            
            if ($output > 0 && !empty($output)) {
                // success
                $this->handle_task_attachments_array($output);
                
                // Save ChatPion bridge link if chatpion_campaign_id is provided
                log_message('error', '[Perfex CRM API] About to call save_chatpion_bridge_link');
                log_message('error', '[Perfex CRM API] Task ID: ' . $output);
                log_message('error', '[Perfex CRM API] ChatPion data keys: ' . implode(', ', array_keys($chatpion_data)));
                
                $this->save_chatpion_bridge_link($output, $chatpion_data, $insert_data);
                
                $message = array(
                    'status' => TRUE,
                    'message' => 'Task add successful.',
                    'id' => $output,
                    'taskid' => $output  // For backward compatibility
                );
                
                log_message('error', '[Perfex CRM API] Task created successfully - Task ID: ' . $output);
                log_message('error', '[Perfex CRM API] Response: ' . json_encode($message));
                
                $this->response($message, REST_Controller::HTTP_OK);
            } else {
                // error
                $message = array(
                    'status' => FALSE,
                    'message' => 'Task add failed.'
                );
                $this->response($message, REST_Controller::HTTP_NOT_FOUND);
            }
        }
    }

    /**
     * @api {delete} api/delete/tasks/:id Delete a Task
     * @apiName DeleteTask
     * @apiGroup Tasks
     *
     * @apiHeader {String} Authorization Basic Access Authentication token.
     *
     * @apiParam {Number} id Task unique ID.
     *
     * @apiSuccess {String} status Request status.
     * @apiSuccess {String} message Task Delete Successful.
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *       "status": true,
     *       "message": "Task Delete Successful."
     *     }
     *
     * @apiError {String} status Request status.
     * @apiError {String} message Task Delete Fail.
     *
     * @apiErrorExample Error-Response:
     *     HTTP/1.1 404 Not Found
     *     {
     *       "status": false,
     *       "message": "Task Delete Fail."
     *     }
     */
    public function data_delete($id = '')
    {
        $id = $this->security->xss_clean($id);
        if (empty($id) && !is_numeric($id)) {
            $message = array(
                'status' => FALSE,
                'message' => 'Invalid Task ID'
            );
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        } else {
            // delete data
            $this->load->model('tasks_model');
            $output = $this->tasks_model->delete_task($id);
            if ($output === TRUE) {
                // success
                $message = array(
                    'status' => TRUE,
                    'message' => 'Task Delete Successful.'
                );
                $this->response($message, REST_Controller::HTTP_OK);
            } else {
                // error
                $message = array(
                    'status' => FALSE,
                    'message' => 'Task Delete Fail.'
                );
                $this->response($message, REST_Controller::HTTP_NOT_FOUND);
            }
        }
    }

    /**
     * @api {put} api/tasks/:id Update a task
     * @apiName PutTask
     * @apiGroup Tasks
     *
     * @apiHeader {String} Authorization Basic Access Authentication token.
     *
     * @apiParam {String} name              Mandatory Task Name.
     * @apiParam {Date} startdate           Mandatory Task Start Date.
     * @apiParam {String} [is_public]       Optional Task public.
     * @apiParam {String} [billable]        Optional Task billable.
     * @apiParam {String} [hourly_rate]     Optional Task hourly rate.
     * @apiParam {String} [milestone]       Optional Task milestone.
     * @apiParam {Date} [duedate]           Optional Task deadline.
     * @apiParam {String} [priority]        Optional Task priority.
     * @apiParam {String} [repeat_every]    Optional Task repeat every.
     * @apiParam {Number} [repeat_every_custom]     Optional Task repeat every custom.
     * @apiParam {String} [repeat_type_custom]      Optional Task repeat type custom.
     * @apiParam {Number} [cycles]                  Optional cycles.
     * @apiParam {string="lead","customer","invoice", "project", "quotation", "contract", "annex", "ticket", "expense", "proposal"} rel_type Mandatory Task Related.
     * @apiParam {Number} rel_id            Optional Related ID.
     * @apiParam {String} [tags]            Optional Task tags.
     * @apiParam {String} [description]     Optional Task description.
     *
     *
     * @apiParamExample {json} Request-Example:
     *  {
     *      "billable": "1", 
     *      "is_public": "1",
     *      "name": "Task 1",
     *      "hourly_rate": "0.00",
     *      "milestone": "0",
     *      "startdate": "27/08/2019",
     *      "duedate": null,
     *      "priority": "0",
     *      "repeat_every": "",
     *      "repeat_every_custom": "1",
     *      "repeat_type_custom": "day",
     *      "cycles": "0",
     *      "rel_type": "lead",
     *      "rel_id": "11",
     *      "tags": "",
     *      "description": ""
     *   }
     *
     * @apiSuccess {String} status Request status.
     * @apiSuccess {String} message Task Update Successful.
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *     {
     *       "status": true,
     *       "message": "Task Update Successful."
     *     }
     *
     * @apiError {String} status Request status.
     * @apiError {String} message Task Update Fail.
     *
     * @apiErrorExample Error-Response:
     *     HTTP/1.1 404 Not Found
     *     {
     *       "status": false,
     *       "message": "Task Update Fail."
     *     }
     */
    public function data_put($id = '')
    {
        $_POST = json_decode($this->security->xss_clean(file_get_contents("php://input")), true);
        if (empty($_POST) || !isset($_POST)) {
            $this->load->library('parse_input_stream');
            $_POST = $this->parse_input_stream->parse_parameters();
            $_FILES = $this->parse_input_stream->parse_files();
            if (empty($_POST) || !isset($_POST)) {
                $message = array('status' => FALSE, 'message' => 'Data Not Acceptable OR Not Provided');
                $this->response($message, REST_Controller::HTTP_NOT_ACCEPTABLE);
            }
        }
        $this->form_validation->set_data($_POST);
        if (empty($id) && !is_numeric($id)) {
            $message = array('status' => FALSE, 'message' => 'Invalid Lead ID');
            $this->response($message, REST_Controller::HTTP_NOT_FOUND);
        } else {
            $update_data = $this->input->post();
            $update_file = isset($update_data['file']) ? $update_data['file'] : null;
            unset($update_data['file']);

            // update data
            $this->load->model('tasks_model');
            $output = $this->tasks_model->update($update_data, $id);
            if (!empty($update_file) && count($update_file)) {
                if ($output <= 0 || empty($output)) {
                    $output = $id;
                }
            }

            if ($output > 0 && !empty($output)) {
                // success
                $attachments = $this->tasks_model->get_task_attachments($output);
                foreach ($attachments as $attachment) {
                    $this->tasks_model->remove_task_attachment($attachment['id']);
                }
                $this->handle_task_attachments_array($output);
                $message = array(
                    'status' => TRUE,
                    'message' => 'Task Update Successful.'
                );
                $this->response($message, REST_Controller::HTTP_OK);
            } else {
                // error
                $message = array(
                    'status' => FALSE,
                    'message' => 'Task Update Fail.'
                );
                $this->response($message, REST_Controller::HTTP_NOT_FOUND);
            }
        }
    }

    public function chatpion_link_put($id = '')
    {
        if (!is_numeric($id)) {
            $this->response(['status' => false, 'message' => 'Invalid Task ID'], REST_Controller::HTTP_BAD_REQUEST);
        }

        if (! $this->chatpion_bridge_active) {
            $this->response(['status' => false, 'message' => 'Chatpion Bridge module is not active'], REST_Controller::HTTP_NOT_IMPLEMENTED);
        }

        $payload = $this->get_request_payload();
        $campaignId = isset($payload['campaign_id']) ? trim($payload['campaign_id']) : '';
        if ($campaignId === '') {
            $this->response(['status' => false, 'message' => 'campaign_id is required'], REST_Controller::HTTP_BAD_REQUEST);
        }

        $save = ['campaign_id' => $campaignId];
        if (array_key_exists('account_id', $payload)) {
            $save['account_id'] = $payload['account_id'];
        }
        foreach (['last_status', 'post_url', 'media_type', 'last_synced_at', 'created_by'] as $optional) {
            if (array_key_exists($optional, $payload)) {
                $save[$optional] = $payload[$optional];
            }
        }
        if (array_key_exists('workspace', $payload)) {
            $save['workspace'] = $payload['workspace'];
        }

        $success = $this->chatpionBridgeTaskModel->upsert_task_link((int) $id, $save);

        if (! $success) {
            $this->response(['status' => false, 'message' => 'Unable to store Chatpion link'], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }

        $link = $this->chatpionBridgeTaskModel->get_task_link((int) $id);
        $this->response([
            'status' => true,
            'message' => 'Chatpion campaign linked successfully.',
            'data' => $link ? $this->chatpionBridgeModel->format_task_link($link) : null,
        ], REST_Controller::HTTP_OK);
    }

    public function chatpion_link_delete($id = '')
    {
        if (!is_numeric($id)) {
            $this->response(['status' => false, 'message' => 'Invalid Task ID'], REST_Controller::HTTP_BAD_REQUEST);
        }

        if (! $this->chatpion_bridge_active) {
            $this->response(['status' => false, 'message' => 'Chatpion Bridge module is not active'], REST_Controller::HTTP_NOT_IMPLEMENTED);
        }

        $deleted = $this->chatpionBridgeTaskModel->delete_task_link((int) $id);
        $this->response([
            'status' => true,
            'message' => $deleted ? 'Chatpion campaign unlinked.' : 'No Chatpion campaign linked with this task.',
        ], REST_Controller::HTTP_OK);
    }

    public function workspace_put($id = '')
    {
        if (!is_numeric($id)) {
            $this->response(['status' => false, 'message' => 'Invalid Task ID'], REST_Controller::HTTP_BAD_REQUEST);
        }

        if (! $this->chatpion_bridge_active) {
            $this->response(['status' => false, 'message' => 'Chatpion Bridge module is not active'], REST_Controller::HTTP_NOT_IMPLEMENTED);
        }

        $payload = $this->get_request_payload();
        $workspace = array_key_exists('workspace', $payload) ? $payload['workspace'] : null;

        if ($workspace === null) {
            $candidate = $payload;
            unset($candidate['workspace']);
            $workspace = $candidate;
        }

        if ($workspace === null || $workspace === []) {
            $this->response(['status' => false, 'message' => 'Workspace payload is required'], REST_Controller::HTTP_BAD_REQUEST);
        }

        $success = $this->chatpionBridgeTaskModel->update_workspace_only((int) $id, $workspace);
        if (! $success) {
            $this->response(['status' => false, 'message' => 'Unable to update workspace'], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }

        $link = $this->chatpionBridgeTaskModel->get_task_link((int) $id);
        $this->response([
            'status' => true,
            'message' => 'Workspace updated successfully.',
            'data' => $link ? $this->chatpionBridgeModel->format_task_link($link) : null,
        ], REST_Controller::HTTP_OK);
    }

    function handle_task_attachments_array($task_id, $index_name = 'file') {
        $path = get_upload_path_by_type('task') . $task_id . '/';
        $CI = & get_instance();
        if (isset($_FILES[$index_name]['name']) && ($_FILES[$index_name]['name'] != '' || is_array($_FILES[$index_name]['name']) && count($_FILES[$index_name]['name']) > 0)) {
            if (!is_array($_FILES[$index_name]['name'])) {
                $_FILES[$index_name]['name'] = [$_FILES[$index_name]['name']];
                $_FILES[$index_name]['type'] = [$_FILES[$index_name]['type']];
                $_FILES[$index_name]['tmp_name'] = [$_FILES[$index_name]['tmp_name']];
                $_FILES[$index_name]['error'] = [$_FILES[$index_name]['error']];
                $_FILES[$index_name]['size'] = [$_FILES[$index_name]['size']];
            }
            _file_attachments_index_fix($index_name);
            for ($i = 0; $i < count($_FILES[$index_name]['name']); $i++) {
                // Get the temp file path
                $tmpFilePath = $_FILES[$index_name]['tmp_name'][$i];
                // Make sure we have a filepath
                if (!empty($tmpFilePath) && $tmpFilePath != '') {
                    if (_perfex_upload_error($_FILES[$index_name]['error'][$i]) || !_upload_extension_allowed($_FILES[$index_name]['name'][$i])) {
                        continue;
                    }
                    _maybe_create_upload_path($path);
                    $filename = unique_filename($path, $_FILES[$index_name]['name'][$i]);
                    $newFilePath = $path . $filename;
                    // Upload the file into the temp dir
                    if (copy($tmpFilePath, $newFilePath)) {
                        unlink($tmpFilePath);
                        $CI = & get_instance();
                        $CI->load->model('tasks_model');
                        $data = [];
                        $data[] = ['file_name' => $filename, 'filetype' => $_FILES[$index_name]['type'][$i], ];
                        $CI->tasks_model->add_attachment_to_database($task_id, $data, false);
                    }
                }
            }
        }
        return true;
    }

    private function append_chatpion_campaign_data($data, $id = '')
    {
        if (! $this->chatpion_bridge_active || empty($data)) {
            return $data;
        }

        if ($id !== '' && is_numeric($id)) {
            $this->apply_chatpion_campaign_to_task($data);

            return $data;
        }

        if (is_array($data)) {
            foreach ($data as $key => $task) {
                $this->apply_chatpion_campaign_to_task($data[$key]);
            }
        }

        return $data;
    }

    private function apply_chatpion_campaign_to_task(&$task)
    {
        if (! $this->chatpion_bridge_active) {
            return;
        }

        $taskId = null;
        if (is_array($task)) {
            $taskId = $task['id'] ?? null;
        } elseif (is_object($task)) {
            $taskId = $task->id ?? null;
        }

        if (! $taskId) {
            return;
        }

        $link = $this->chatpionBridgeTaskModel->get_task_link((int) $taskId);
        $formatted = $link ? $this->chatpionBridgeModel->format_task_link($link) : null;

        if (is_array($task)) {
            $task['chatpion_campaign'] = $formatted;
        } else {
            $task->chatpion_campaign = $formatted ? (object) $formatted : null;
        }
    }

    private function get_request_payload(): array
    {
        $raw = $this->input->raw_input_stream;
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        $payload = $this->input->post();
        if (! empty($payload)) {
            return $payload;
        }

        $this->load->library('parse_input_stream');
        $payload = $this->parse_input_stream->parse_parameters();

        return is_array($payload) ? $payload : [];
    }

    /**
     * @api {get} api/tasks/check_chatpion_campaign Check if ChatPion campaign exists
     * @apiName CheckChatpionCampaign
     * @apiGroup Tasks
     *
     * @apiHeader {String} Authorization Basic Access Authentication token.
     *
     * @apiParam {String} campaign_id Campaign ID from ChatPion.
     * @apiParam {String} [media_type=all] Media type (facebook, instagram, or all).
     *
     * @apiSuccess {String} status Success status.
     * @apiSuccess {Object} data Response data.
     * @apiSuccess {Boolean} data.exists Whether task exists.
     * @apiSuccess {Number} data.task_id Task ID in Perfex CRM.
     * @apiSuccess {Number} data.project_id Project ID.
     * @apiSuccess {String} data.task_name Task name.
     * @apiSuccess {Object} data.link_data Link data from chatpion_bridge_task_links.
     *
     * @apiSuccessExample Success-Response (Task Exists):
     *     HTTP/1.1 200 OK
     *     {
     *       "status": "success",
     *       "data": {
     *         "exists": true,
     *         "task_id": 123,
     *         "project_id": 45,
     *         "task_name": "Campaign Name",
     *         "link_data": {...}
     *       }
     *     }
     *
     * @apiSuccessExample Success-Response (Task Not Found):
     *     HTTP/1.1 200 OK
     *     {
     *       "status": "success",
     *       "data": {
     *         "exists": false
     *       }
     *     }
     */
    public function check_chatpion_campaign_get()
    {
        // Check if chatpion_bridge module is active
        if (!$this->chatpion_bridge_active) {
            $this->response([
                'status' => 'error',
                'message' => 'ChatPion Bridge module is not active'
            ], REST_Controller::HTTP_BAD_REQUEST);
        }

        // Get parameters
        $campaign_id = $this->get('campaign_id');
        $media_type = $this->get('media_type') ?: 'all';

        if (empty($campaign_id)) {
            $this->response([
                'status' => 'error',
                'message' => 'Campaign ID is required'
            ], REST_Controller::HTTP_BAD_REQUEST);
        }

        // Validate media_type
        $media_type = strtolower(trim($media_type));
        if (!in_array($media_type, ['facebook', 'instagram', 'all'], true)) {
            $media_type = 'all';
        }

        try {
            // Query chatpion_bridge_task_links table
            $this->db->select('*');
            $this->db->from(db_prefix() . 'chatpion_bridge_task_links');
            $this->db->where('campaign_id', $campaign_id);

            if ($media_type !== 'all') {
                $this->db->where('media_type', $media_type);
            }

            $link = $this->db->get()->row_array();

            if ($link) {
                // Get task details
                $this->load->model('tasks_model');
                $task = $this->tasks_model->get($link['task_id']);

                if ($task) {
                    $this->response([
                        'status' => 'success',
                        'data' => [
                            'exists' => true,
                            'task_id' => (int) $link['task_id'],
                            'project_id' => isset($task->rel_id) ? (int) $task->rel_id : null,
                            'task_name' => $task->name ?? '',
                            'link_data' => $link
                        ]
                    ], REST_Controller::HTTP_OK);
                } else {
                    $this->response([
                        'status' => 'error',
                        'message' => 'Task not found'
                    ], REST_Controller::HTTP_NOT_FOUND);
                }
            } else {
                $this->response([
                    'status' => 'success',
                    'data' => [
                        'exists' => false
                    ]
                ], REST_Controller::HTTP_OK);
            }
        } catch (Exception $e) {
            $this->response([
                'status' => 'error',
                'message' => 'Error: ' . $e->getMessage()
            ], REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    /**
     * Save ChatPion bridge link when task is created from ChatPion
     * 
     * @param int $task_id Created task ID
     * @param array $chatpion_data ChatPion-specific fields
     * @param array $task_data Task data for getting project ID
     */
    private function save_chatpion_bridge_link($task_id, $chatpion_data, $task_data)
    {
        log_message('error', '[ChatPion Bridge] ========== SAVE BRIDGE LINK ==========');
        log_message('error', '[ChatPion Bridge] Task ID: ' . $task_id);
        log_message('error', '[ChatPion Bridge] ChatPion data keys: ' . implode(', ', array_keys($chatpion_data)));
        log_message('error', '[ChatPion Bridge] ChatPion data: ' . json_encode($chatpion_data));
        
        // Get ChatPion fields from dedicated array
        $chatpion_campaign_id = $chatpion_data['chatpion_campaign_id'] ?? null;
        $chatpion_user_id = $chatpion_data['chatpion_user_id'] ?? null;
        $chatpion_platform = $chatpion_data['chatpion_platform'] ?? 'instagram_poster';
        
        log_message('error', '[ChatPion Bridge] Campaign ID: ' . var_export($chatpion_campaign_id, true));
        log_message('error', '[ChatPion Bridge] User ID: ' . var_export($chatpion_user_id, true));
        log_message('error', '[ChatPion Bridge] Platform: ' . var_export($chatpion_platform, true));
        
        if (empty($chatpion_campaign_id)) {
            log_message('error', '[ChatPion Bridge] No campaign ID found - skipping bridge link creation');
            log_message('error', '[ChatPion Bridge] ========== SKIP ==========');
            return;
        }
        
        log_message('error', '[ChatPion Bridge] ✓ Valid campaign ID found');
        log_message('error', '[ChatPion Bridge] Saving bridge link for task: ' . $task_id);
        log_message('error', '[ChatPion Bridge] Campaign ID: ' . $chatpion_campaign_id);
        log_message('error', '[ChatPion Bridge] User ID: ' . $chatpion_user_id);
        log_message('error', '[ChatPion Bridge] Platform: ' . $chatpion_platform);
        
        // Prepare link data matching actual table structure
        $link_data = [
            'task_id' => $task_id,
            'campaign_id' => $chatpion_campaign_id,
            'account_id' => $chatpion_user_id, // ChatPion user ID as account ID
            'media_type' => $chatpion_platform ?: 'instagram_poster',
            'last_status' => 0, // Initial status
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'created_by' => get_staff_user_id() ?: $chatpion_user_id // Current staff or ChatPion user
        ];
        
        log_message('error', '[ChatPion Bridge] Link data prepared: ' . json_encode($link_data));
        
        // Insert into chatpion_bridge_task_links table
        try {
            $table_name = db_prefix() . 'chatpion_bridge_task_links';
            log_message('error', '[ChatPion Bridge] Inserting into table: ' . $table_name);
            
            $this->db->insert($table_name, $link_data);
            
            $affected_rows = $this->db->affected_rows();
            $insert_id = $this->db->insert_id();
            
            log_message('error', '[ChatPion Bridge] Insert affected rows: ' . $affected_rows);
            log_message('error', '[ChatPion Bridge] Insert ID: ' . $insert_id);
            
            if ($affected_rows > 0) {
                log_message('error', '[ChatPion Bridge] ✓ Bridge link saved successfully - Link ID: ' . $insert_id);
                log_message('error', '[ChatPion Bridge] ========== SUCCESS ==========');
            } else {
                log_message('error', '[ChatPion Bridge] ✗ Failed to save bridge link - no rows affected');
                log_message('error', '[ChatPion Bridge] Last query: ' . $this->db->last_query());
                log_message('error', '[ChatPion Bridge] ========== FAILED ==========');
            }
        } catch (Exception $e) {
            log_message('error', '[ChatPion Bridge] ✗ Exception saving bridge link: ' . $e->getMessage());
            log_message('error', '[ChatPion Bridge] Stack trace: ' . $e->getTraceAsString());
            log_message('error', '[ChatPion Bridge] ========== ERROR ==========');
        }
    }
}
