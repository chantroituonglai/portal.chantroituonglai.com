<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Deals extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('deals_model');
        $this->load->model('staff_model');
    }

    public function index($id = null)
    {
        close_setup_menu();
        if (!is_staff_member()) {
            access_denied('Deals');
        }
        $data['switch_kanban'] = true;

        $data['piplines'] = get_deals_result('tbl_deals_pipelines', null, 'array');
        $default_pipeline = get_option('default_pipeline');
        if (empty($default_pipeline)) {
            $default_pipeline = $data['piplines'][0]['pipeline_id'];
        }
        $data['default_pipeline'] = $default_pipeline;

        $data['sources'] = get_deals_result('tbl_deals_source', null, 'array');

        if ($this->session->userdata('deals_kanban_view') == 'true') {
            $data['switch_kanban'] = false;
            $data['bodyclass'] = 'kan-ban-body';
        }

        $data['staff'] = $this->staff_model->get('', ['active' => 1]);
        $data['customers'] = get_deals_result(db_prefix() . 'clients', null, 'array');
        if (is_gdpr() && get_option('gdpr_enable_consent_for_deals') == '1') {
            $this->load->model('gdpr_model');
            $data['consent_purposes'] = $this->gdpr_model->get_consent_purposes();
        }
        $data['dealid'] = $id;
        $data['isKanBan'] = $this->session->has_userdata('deals_kanban_view') &&
            $this->session->userdata('deals_kanban_view') == 'true';
        $data['title'] = _l('deals');
        $this->load->view('all_deals', $data);
    }

    public function switch_kanban($set = 0)
    {
        if ($set == 1) {
            $set = 'true';
        } else {
            $set = 'false';
        }
        $this->session->set_userdata([
            'deals_kanban_view' => $set,
        ]);
        redirect($_SERVER['HTTP_REFERER']);
    }

    public function kanban()
    {
        if (!is_staff_member()) {
            ajax_access_denied();
        }
        $pipeline_id = $this->input->get('pipeline_id');
        $data['base_currency'] = get_base_currency();
        // get stages by pipeline
        $data['stages'] = get_deals_order_by('tbl_deals_stages', ['pipeline_id' => $pipeline_id], 'stage_order', 'asc');
        $data['sources'] = get_deals_result('tbl_deals_source', null, 'array');
        echo $this->load->view('kan-ban', $data, true);
    }

    public function deals_kanban_load_more()
    {
        if (!is_staff_member()) {
            ajax_access_denied();
        }

        $status = $this->input->get('status');
        $page = $this->input->get('page');

        $this->db->where('stage_id', $status);
        $status = $this->db->get('tbl_deals_stages')->row_array();

        $leads = (new  \modules\deals\libraries\DealsKanban($status['stage_id']))
            ->search($this->input->get('search'))
            ->sortBy(
                $this->input->get('sort_by'),
                $this->input->get('sort')
            )
            ->page($page)->get();

        foreach ($leads as $lead) {
            $this->load->view('_kan_ban_card', [
                'deal' => $lead,
                'stage' => $status,
            ]);
        }
    }

    public function update_deal_satges()
    {
        if ($this->input->post() && $this->input->is_ajax_request()) {
            $this->deals_model->update_deal_satges($this->input->post());
        }
    }

    public function update_stage_order()
    {
        if ($this->input->post()) {
            $this->deals_model->update_stage_order($this->input->post());
        }
    }


    public function add_deals_attachment()
    {

        $id = $this->input->post('id');
        $lastFile = $this->input->post('last_file');
        if (!is_staff_member() || !$this->deals_model->staff_can_access_deals($id)) {
            ajax_access_denied();
        }
        $files = handle_deal_attachments_array($id, 'file');
        if ($files) {
            $i = 0;
            $len = count($files);
            foreach ($files as $file) {
                $success = $this->deals_model->add_attachment_to_database($id, 'deal', [$file]);
                $i++;
            }
        }
    }

    public function delete_attachment($id, $lead_id)
    {
        if (!is_staff_member() || !$this->deals_model->staff_can_access_deals($lead_id)) {
            ajax_access_denied();
        }
        $this->deals_model->delete_deals_attachment($id);
        //

        $msg = _l('deals_attachment_delete');
        $type = "success";
        set_alert($type, $msg);
        redirect('admin/deals/details/' . $lead_id . '/attachments');
    }

    public function dealsList()
    {
        if ($this->input->is_ajax_request()) {
            $this->load->model('datatabless');
            $deal_owner = $this->input->post('deal_owner');
            $company = $this->input->post('company');
            $pipeline = $this->input->post('pipeline');
            $source = $this->input->post('source');
            $custom_view = $this->input->post('custom_view');
            $select = 'tbl_deals.*,tbl_deals_stages.stage_name,tbl_deals_source.source_name,tbl_deals_pipelines.pipeline_name,(SELECT GROUP_CONCAT(name SEPARATOR ",") FROM ' . db_prefix() . 'taggables JOIN ' . db_prefix() . 'tags ON ' . db_prefix() . 'taggables.tag_id = ' . db_prefix() . 'tags.id WHERE rel_id = ' . db_prefix() . '_deals.id and rel_type="deal" ORDER by tag_order ASC) as tags';
            $this->datatabless->table = 'tbl_deals';
            $join_table = array('tbl_deals_stages', 'tbl_deals_source', 'tbl_deals_pipelines');
            $join_where = array('tbl_deals_stages.stage_id=tbl_deals.stage_id', 'tbl_deals_source.source_id=tbl_deals.source_id',
                'tbl_deals_pipelines.pipeline_id=tbl_deals.pipeline_id'
            );
            $custom_fields = get_custom_fields('deals', [
                'show_on_table' => 1,
            ]);

            $action_array = array('tbl_deals.id');
            $main_column = array('title', 'tbl_deals_stages.stage_name', 'tbl_deals_source.source_name', 'tbl_deals_pipelines.pipeline_name', 'deal_value');

            $i = 0;
            foreach ($custom_fields as $field) {
                $select_as = 'cvalue_' . $i;
                $join_table[] = db_prefix() . 'customfieldsvalues as ctable_' . $i;
                $join_where[] = 'tbl_deals.id = ctable_' . $i . '.relid AND ctable_' . $i . '.fieldto="' . $field['fieldto'] . '" AND ctable_' . $i . '.fieldid=' . $field['id'];
                $select .= ',ctable_' . $i . '.value as ' . $select_as;
                $main_column[] = 'ctable_' . $i . '.value';
                $i++;
            }

            $this->datatabless->select = $select;
            $this->datatabless->join_table = $join_table;
            $this->datatabless->join_where = $join_where;

            $result = array_merge($main_column, $action_array);
            $this->datatabless->column_order = $result;
            $this->datatabless->column_search = $result;
            $this->datatabless->order = array('tbl_deals.id' => 'desc');

            $where = array();
            if (!empty($deal_owner)) {
                $where['tbl_deals.default_deal_owner'] = $deal_owner;
            }
            if (!empty($pipeline)) {
                $where['tbl_deals.pipeline_id'] = $pipeline;
            }
            if (!empty($source)) {
                $where['tbl_deals.source_id'] = $source;
            }
            if (!empty($custom_view)) {
                if ($custom_view == 'created_today') {
                    $where['tbl_deals.created_at >='] = date('Y-m-d') . ' 00:00:00';
                } elseif ($custom_view == 'created_this_week') {
                    $where['tbl_deals.created_at >='] = date('Y-m-d', strtotime('monday this week')) . ' 00:00:00';
                } elseif ($custom_view == 'created_last_week') {
                    $where['tbl_deals.created_at >='] = date('Y-m-d', strtotime('monday last week')) . ' 00:00:00';
                    $where['tbl_deals.created_at <='] = date('Y-m-d', strtotime('sunday last week')) . ' 23:59:59';
                } elseif ($custom_view == 'created_this_month') {
                    $where['tbl_deals.created_at >='] = date('Y-m-01') . ' 00:00:00';
                } elseif ($custom_view == 'created_last_month') {
                    $where['tbl_deals.created_at >='] = date('Y-m-01', strtotime('last month')) . ' 00:00:00';
                    $where['tbl_deals.created_at <='] = date('Y-m-t', strtotime('last month')) . ' 23:59:59';
                } else if ($custom_view == 'customer') {
                    $where['tbl_deals.rel_type'] = 'customer';
                } else if ($custom_view == 'lead') {
                    $where['tbl_deals.rel_type'] = 'lead';
                } else if ($custom_view == 'contract') {
                    $where['tbl_deals.rel_type'] = 'contract';
                } else if ($custom_view == 'proposal') {
                    $where['tbl_deals.rel_type'] = 'proposal';
                } else {
                    $where['tbl_deals.status'] = $custom_view;
                }
            }
            $fetch_data = make_deals_datatables($where);

            $edited = has_permission('deals', '', 'edit');
            $deleted = has_permission('deals', '', 'delete');
            $data = array();
            foreach ($fetch_data as $_key => $v_deals) {
                $action = null;
                $sub_array = array();
                $sub_array[] = '<a  ' . ' class="text-info" href="' . base_url() . 'admin/deals/details/' . $v_deals->id . '">' . $v_deals->title . '</a>';
                $sub_array[] = deals_display_money($v_deals->deal_value, deals_default_currency());
                $sub_array[] = render_tags($v_deals->tags);
                $sub_array[] = $v_deals->stage_name;
                $sub_array[] = _d($v_deals->days_to_close);
                $sub_array[] = (!empty($v_deals->status) ? _l($v_deals->status) : '');
                $cvalue = 'cvalue_' . $_key;
                if (!empty($v_deals->$cvalue) && $v_deals->$cvalue) {
                    $sub_array[] = $v_deals->$cvalue;
                }
                $action .= btn_view_deals('admin/deals/details/' . $v_deals->id) . ' ';
                if (!empty($edited)) {
                    $action .= btn_edit_deals('admin/deals/new_deal/' . $v_deals->id) . ' ';
                }
                if (!empty($deleted)) {
                    $action .= btn_delete_deals('admin/deals/delete_deals/' . $v_deals->id) . ' ';
                }

                $sub_array[] = $action;
                $data[] = $sub_array;
            }

            deals_render_table($data, $where);
        } else {
            redirect('admin/dashboard');
        }
    }

    public function new_deal($id = NULL)
    {
        $data['title'] = _l('deals'); //Page title

        if (!empty($id)) {
            $edited = has_permission('deals', get_staff_user_id(), 'edit');
            if (!empty($edited)) {
                $data['deals'] = $this->db->where('id', $id)->get('tbl_deals')->row();
            }
            if (empty($data['deals'])) {
                $type = "error";
                $message = _l("no_record_found");
                set_alert($type, $message);
                redirect('admin/deals/new_deals');
            }

        }
        $data['sources'] = get_deals_result('tbl_deals_source', null, 'array');
        $data['customers'] = get_deals_result(db_prefix() . 'clients', null, 'array');
        $data['pipelines'] = get_deals_result('tbl_deals_pipelines', null, 'array');
        $data['staff'] = $this->staff_model->get('', ['active' => 1]);
        $this->load->view('new_deals', $data);
    }

    public function sources()
    {
        if (!is_admin() && get_option('staff_members_create_inline_deal_source') == '0') {
            access_denied('Deals Sources');
        }
        $data['sources'] = get_deals_result('tbl_deals_source', null, 'array');
        $data['title'] = _l('deals_sources');
        $this->load->view('sources', $data);
    }

    public function source_id($id = null)
    {
        if (!is_admin() && get_option('staff_members_create_inline_deal_source') == '0') {
            access_denied('Deals Sources');
        }
        if ($this->input->post()) {
            $data = $this->input->post();
            if (!$this->input->post('id')) {
                $inline = isset($data['inline']);
                if (isset($data['inline'])) {
                    unset($data['inline']);
                }
            } else {
                $id = $data['id'];
                unset($data['id']);
            }
            $pdata['source_name'] = $data['name'];

            $this->deals_model->_table_name = "tbl_deals_source"; // table name
            $this->deals_model->_primary_key = "source_id"; // $id
            $return_id = $this->deals_model->save_deals($pdata, $id);
            if (!$inline) {
                if ($return_id) {
                    set_alert('success', _l('added_successfully', _l('deal_source')));
                }
            } else {
                echo json_encode(['success' => $return_id ? true : false, 'id' => $return_id]);
            }
            if ($id) {
                set_alert('success', _l('updated_successfully', _l('deal_source')));
            }

        }
    }

    public function delete_source($id)
    {
        if (!is_admin() && get_option('staff_members_create_inline_deal_source') == '0') {
            access_denied('Deals Sources');
        }
        $this->deals_model->_table_name = "tbl_deals_source"; // table name
        $this->deals_model->_primary_key = "source_id"; // $id
        $this->deals_model->delete_deals($id);
        set_alert('success', _l('delete_successfully', _l('deal_source')));
        redirect('admin/deals/sources');
    }

    public function pipelines()
    {
        if (!is_admin() && get_option('staff_members_create_inline_deal_pipeline') == '0') {
            access_denied('Deals Pipelines');
        }
        $data['pipelines'] = get_deals_result('tbl_deals_pipelines', null, 'array');
        $data['title'] = _l('deals_pipelines');
        $this->load->view('pipelines', $data);
    }

    public function pipeline($id = null)
    {
        if (!is_admin() && get_option('staff_members_create_inline_deal_pipeline') == '0') {
            access_denied('Deals Pipeline');
        }
        if ($this->input->post()) {
            $data = $this->input->post();
            if (!$this->input->post('id')) {
                $inline = isset($data['inline']);
                if (isset($data['inline'])) {
                    unset($data['inline']);
                }
            } else {
                $id = $data['id'];
                unset($data['id']);
            }
            $pdata['pipeline_name'] = $data['name'];

            $this->deals_model->_table_name = "tbl_deals_pipelines"; // table name
            $this->deals_model->_primary_key = "pipeline_id"; // $id
            $return_id = $this->deals_model->save_deals($pdata, $id);
            if (!$inline) {
                if ($return_id) {
                    set_alert('success', _l('added_successfully', _l('deals_pipeline')));
                }
            } else {
                echo json_encode(['success' => $return_id ? true : false, 'id' => $return_id]);
            }
            if ($id) {
                set_alert('success', _l('updated_successfully', _l('deals_pipeline')));

            }
        }
    }


    public function delete_pipeline($id)
    {
        if (!is_admin() && get_option('staff_members_create_inline_deal_pipeline') == '0') {
            access_denied('Deals Pipeline');
        }
        $this->deals_model->_table_name = "tbl_deals_pipelines"; // table name
        $this->deals_model->_primary_key = "pipeline_id"; // $id
        $this->deals_model->delete_deals($id);
        set_alert('success', _l('delete_successfully', _l('deal_pipeline')));
        redirect('admin/deals/pipelines');
    }

    public function stages()
    {
        if (!is_admin() && get_option('staff_members_create_inline_deal_stage') == '0') {
            access_denied('Deals Stages');
        }
        $data['stages'] = join_data('tbl_deals_stages', '*', null, [
            'tbl_deals_pipelines' => 'tbl_deals_pipelines.pipeline_id = tbl_deals_stages.pipeline_id',
        ], 'array', ['tbl_deals_stages.stage_order' => 'ASC']);

        $data['pipelines'] = get_deals_result('tbl_deals_pipelines', null, 'array');
        $data['title'] = _l('deals_stages');
        $this->load->view('stages', $data);
    }


    public function stage($id = null)
    {
        if (!is_admin() && get_option('staff_members_create_inline_deal_stage') == '0') {
            access_denied('Deals stages');
        }
        if ($this->input->post()) {
            $data = $this->input->post();
            if (!$this->input->post('id')) {
                $inline = isset($data['inline']);
                if (isset($data['inline'])) {
                    unset($data['inline']);
                }
            } else {
                $id = $data['id'];
                unset($data['id']);
            }
            $pdata['stage_name'] = $data['name'];
            $pdata['pipeline_id'] = $data['pipeline_id'];
            $pdata['stage_order'] = $data['stage_order'];

            $this->deals_model->_table_name = "tbl_deals_stages"; // table name
            $this->deals_model->_primary_key = "stage_id"; // $id
            $return_id = $this->deals_model->save_deals($pdata, $id);
            if (!$inline) {
                if ($return_id) {
                    set_alert('success', _l('added_successfully', _l('deals_pipeline')));
                }
            } else {
                echo json_encode(['success' => $return_id ? true : false, 'id' => $return_id]);
            }
            if ($id) {
                set_alert('success', _l('updated_successfully', _l('deals_pipeline')));

            }
        }
    }

    public
    function settings()
    {
        $data['title'] = _l('deals_settings');
        $data['staff'] = $this->staff_model->get('', ['active' => 1]);
        $data['pipelines'] = get_deals_result('tbl_deals_pipelines', null, 'array');
        $data['sources'] = get_deals_result('tbl_deals_source', null, 'array');
        $data['stages'] = get_deals_result('tbl_deals_stages', null, 'array');
        $this->load->view('deals_settings', $data);
    }

    public function save_settings()
    {
        if ($this->input->post()) {
            $data = $this->input->post();
            $post_data['settings']['default_deal_owner'] = $data['default_deal_owner'] ?? null;
            $post_data['settings']['default_pipeline'] = $data['default_pipeline'] ?? null;
            $post_data['settings']['default_source'] = $data['default_deal_source'] ?? null;
            $post_data['settings']['default_stage'] = $data['stage_id'] ?? null;
            $post_data['settings']['deals_kanban_limit'] = $data['deals_kanban_limit'] ?? 50;
            $post_data['settings']['default_deals_kanban_sort_type'] = $data['default_deals_kanban_sort_type'] ?? 'dealorder';
            $post_data['settings']['default_deals_kanban_sort_by'] = $data['default_deals_kanban_sort_by'] ?? 'asc';
            $post_data['settings']['select_company_multiple_or_single'] = $data['select_company_multiple_or_single'] ?? 'multiple';

            // load settings_model
            $this->load->model('payment_modes_model');
            $this->load->model('settings_model');
            $success = $this->settings_model->update($post_data);
            if ($success > 0) {
                set_alert('success', _l('updated_successfully', _l('deals_settings')));
            }
            redirect('admin/deals/settings');
        }
    }

    public function getStateByID($id, $stage_id = null)
    {
        $stages = get_deals_order_by('tbl_deals_stages', array('pipeline_id' => $id), 'stage_order', true, null, 'array');
        if (!empty($stage_id)) {
            $selected = $stage_id;
        } else {
            $selected = get_option('default_stage');
        }
        $select = render_select('stage_id', $stages, ['stage_id', 'stage_name'], _l('stage'), $selected);
        echo json_encode($select);
    }

    public function edit_comment()
    {
        if ($this->input->post()) {
            $data = $this->input->post();
            $data['content'] = html_purify($this->input->post('content', false));
            if ($this->input->post('no_editor')) {
                $data['content'] = nl2br(clear_textarea_breaks($this->input->post('content')));
            }
            $success = $this->deals_model->edit_comment($data);
            $message = '';
            if ($success) {
                $message = _l('task_comment_updated');
            }
            echo json_encode([
                'success' => $success,
                'message' => $message,
            ]);
        }
    }

    public function add_deals_comment()
    {
        $data = $this->input->post();

        $data['content'] = html_purify($this->input->post('content', false));
        if ($this->input->post('no_editor')) {
            $data['content'] = nl2br($this->input->post('content'));
        }
        $comment_id = false;
        if (
            $data['content'] != ''
            || (isset($_FILES['file']['name']) && is_array($_FILES['file']['name']) && count($_FILES['file']['name']) > 0)
        ) {
            $comment_id = $this->deals_model->add_deal_comment($data);
            if ($comment_id) {
                $commentAttachments = handle_deal_attachments_array($data['deal_id'], 'file');
                if ($commentAttachments && is_array($commentAttachments)) {
                    foreach ($commentAttachments as $file) {
                        $file['deal_comment_id'] = $comment_id;
                        $this->deals_model->add_attachment_to_database($data['deal_id'], 'deal', [$file]);
                    }

                    if (count($commentAttachments) > 0) {
                        $this->db->query("UPDATE tbl_deals_comments  SET content = CONCAT(content, '[deal_attachment]')
                            WHERE id = " . $this->db->escape_str($comment_id));
                    }
                }
            }
        }
        echo json_encode([
            'success' => $comment_id ? true : false,
            // 'taskHtml' => $this->get_task_data($data['deal_id'], true),
        ]);
    }

    public function save_deals($id = NULL)
    {

        $created = has_permission('deals', '', 'create');
        $edited = has_permission('deals', '', 'edit');
        if (!empty($created) || !empty($edited) && !empty($id)) {
            $data = $this->deals_model->deals_array_from_post(array(
                'title',
                'deal_value',
                'source_id',
                'days_to_close',
                'pipeline_id',
                'stage_id',
                'default_deal_owner',
                'rel_type',
                'rel_id',
            ));


            $custom_fields = $this->input->post('custom_fields');

            $tags = $this->input->post('tags');
            if (empty($id)) {
                $data['status'] = 'open';
            }
            $data['client_id'] = json_encode($this->input->post('client_id', true));
            $data['user_id'] = json_encode($this->input->post('user_id', true));

            $where = array('title' => $data['title']);
            // duplicate value check in DB
            if (!empty($id)) { // if id exist in db update data
                $deal_id = array('id !=' => $id);
            } else { // if id is not exist then set id as null
                $deal_id = null;
            }
            // check whether this input data already exist or not
            $check_users = $this->deals_model->check_deals_update('tbl_deals', $where, $deal_id);
            if (!empty($check_users)) { // if input data already exist show error alert
                // massage for user
                $type = 'warning';
                $msg = _l('deals_already_exist');
            } else {
                $this->deals_model->_table_name = "tbl_deals"; // table name
                $this->deals_model->_primary_key = "id"; // $id
                $return_id = $this->deals_model->save_deals($data, $id);
                if (!empty($custom_fields)) {
                    handle_custom_fields_post($return_id, $custom_fields);
                }

                if (!empty($tags)) {
                    handle_tags_save($tags, $return_id, 'deal');
                }
                if (!empty($notifyUser)) {
                    foreach ($notifyUser as $v_user) {
                        if (!empty($v_user)) {
                            if ($v_user != $this->session->userdata('user_id')) {
                                add_notification(array(
                                    'to_user_id' => $v_user,
                                    'description' => 'deals',
                                    'icon' => 'clock-o',
                                    'link' => 'admin/deals/details/' . $return_id,
                                ));
                            }
                        }
                    }
                }
                if (!empty($notifyUser)) {
                    pusher_trigger_notification($notifyUser);
                }

                if (!empty($id)) {
                    $msg = _l('deals_information_update');
                    $activity = 'activity_deals_information_update';
                } else {
                    $msg = _l('deals_information_saved');
                    $activity = 'activity_deals_information_saved';
                }
                log_activity($activity . ' - ' . $data['title'] . ' [ID:' . $return_id . ']');

                $this->deals_model->log_deals_activity($return_id, 'not_deal_activity');
                // messages for user
                $type = "success";
            }
        }

        set_alert($type, $msg);
        redirect('admin/deals');
    }

    /**
     * { update customfield po }
     *
     * @param        $id     The identifier
     */
    public function update_customfield_po($id)
    {
        if ($this->input->post()) {
            $data = $this->input->post();
            $success = $this->purchase_model->update_customfield_po($id, $data);
            if ($success) {
                $message = _l('updated_successfully', _l('vendor_category'));
                set_alert('success', $message);
            }
            redirect(admin_url('purchase/purchase_order/' . $id));
        }
    }

    public function send_promotions_email($data)
    {
        $all_clients = get_deals_row('tbl_client', array('client_id' => $data['client_name']));
        $users_email = get_deals_row('tbl_users', array('user_id' => $data['user_id']));
        $deals = get_deals_row('tbl_deals', array('user_id' => $data['user_id']));
        $deals_email = config_item('deals_email');
        if (!empty($deals_email) && $deals_email == 1) {
            $email_template = email_templates(array('email_group' => 'deals_email'));
            $message = $email_template->template_body;
            $subject = $email_template->subject;
            $title = str_replace("{NAME}", $all_clients->name, $message);
            $designation = str_replace("{DEALS_TITLE}", $deals->title, $title);
            $message = str_replace("{SITE_NAME}", config_item('company_name'), $designation);
            $data['message'] = $message;
            $message = $this->load->view('email_template', $data, TRUE);
            $params['subject'] = $subject;
            $params['message'] = $message;
            $params['resourceed_file'] = '';
            $params['recipient'] = $users_email->email;
            $this->deals_model->send_email($params);
        }
        return true;
    }

    public function delete_deals($id = NULL)
    {

        $deleted = has_permission('deals', '', 'delete');

        if (!empty($deleted)) {
            $all_deals = $this->deals_model->check_by_deals(array('id' => $id), 'tbl_deals');
            if (empty($all_deals)) {
                $type = "error";
                $message = _l("no_record_found");
                set_alert($type, $message);
                redirect('admin/deals');
            }

            $all_comments = get_deals_result('tbl_deals_comments', array('deal_id' => $id));
            if (!empty($all_comments)) {
                foreach ($all_comments as $v_comments) {
                    $this->deals_model->remove_comment($v_comments->id);
                }
            }
            $all_attachments = get_deals_result(db_prefix() . 'files', array('rel_id' => $id, 'rel_type' => 'deal'));
            if (!empty($all_attachments)) {
                foreach ($all_attachments as $v_attachments) {
                    $this->deals_model->delete_deals_attachment($v_attachments->id);
                }
            }

            // check data is exist in tbl_deals_email
            $deal_email = get_deals_row('tbl_deals_email', array('deals_id' => $id));
            if (!empty($deal_email)) {
                $this->db->where('deals_id', $id);
                $this->db->delete('tbl_deals_email');
            }
            // check data is exist in tbl_deals_items
            $deal_items = get_deals_row('tbl_deals_items', array('deals_id' => $id));
            if (!empty($deal_items)) {
                $this->db->where('deals_id', $id);
                $this->db->delete('tbl_deals_items');
            }
            // check data is exist in tbl_deal_activity_log
            $deal_activity_log = get_deals_row('tbl_deal_activity_log', array('deal_id' => $id));


            if (!empty($deal_activity_log)) {
                $this->db->where('deal_id', $id);
                $this->db->delete('tbl_deal_activity_log');
            }

            // check data is exist in tbl_deals_mettings
            $deal_meeting = get_deals_row('tbl_deals_mettings', array('module_field_id' => $id, 'module' => 'deals'));

            if (!empty($deal_meeting)) {
                $this->db->where('module_field_id', $id);
                $this->db->delete('tbl_deals_mettings');

            }

            // check data is exist in tbl_deal_calls
            $deal_meeting = get_deals_row('tbl_deal_calls', array('module_field_id' => $id, 'module' => 'deals'));
            if (!empty($deal_meeting)) {
                $this->db->where('module_field_id', $id);
                $this->db->delete('tbl_deal_calls');
            }

            $this->db->where('fieldto', 'deals');
            $this->db->delete(db_prefix() . 'customfieldsvalues');


            $this->deals_model->_table_name = "tbl_deals";
            $this->deals_model->_primary_key = "id";
            $this->deals_model->delete_deals($id);;
            $type = "success";
            $message = _l('deals_information_delete');
            set_alert($type, $message);
            redirect('admin/deals');
        }
    }


    public function save_sorting_stages()
    {
        $ids = $this->input->post('page_id_array', TRUE);
        $arr = explode(',', $ids);
        for ($i = 1; $i <= count($arr); $i++) {
            $this->deals_model->_table_name = 'tbl_stage_name';
            $this->deals_model->_primary_key = 'stage_name_id';
            $cate_data['order'] = $i;
            $this->deals_model->save_deals($cate_data, $arr[$i - 1]);
        }
    }

    public function save_sorting_pipelines()
    {
        $ids = $this->input->post('page_id_array', TRUE);
        $arr = explode(',', $ids);
        for ($i = 1; $i <= count($arr); $i++) {
            $this->deals_model->_table_name = 'tbl_deals_pipelines';
            $this->deals_model->_primary_key = 'pipeline_id';
            $cate_data['order'] = $i;
            $this->deals_model->save_deals($cate_data, $arr[$i - 1]);
        }
    }

    public function saved_stages($id = null)
    {
        $this->deals_model->_table_name = 'tbl_deals_stages';
        $this->deals_model->_primary_key = 'stage_id';

        $cate_data['stage_name'] = $this->input->post('stage_name', TRUE);
        $cate_data['pipeline_id'] = $this->input->post('description', TRUE);

        // $cate_data['type'] = 'stages';
        $this->deals_model->save_deals($cate_data, $id);
        if (!empty($id)) {
            $msg = _l('successfully_stages_update');
            $activity = 'activity_successfully_updated_added';
        } else {
            $msg = _l('successfully_stages_added');
            $activity = 'activity_successfully_stages_added';
        }
        log_activity($activity . ' - ' . $cate_data['stage_name'] . ' [ID:' . $id . ']');
        // messages for user
        $type = "success";

        $message = $msg;
        set_alert($type, $message);
        redirect('admin/deals/stages');
    }

    public function delete_stages($id)
    {

        $this->deals_model->_table_name = 'tbl_deals_stages';
        $this->deals_model->_primary_key = 'stage_id	';
        $this->deals_model->delete_deals($id);

        $type = "success";
        $message = _l('stages_successfully_deleted');
        set_alert($type, $message);
        redirect('admin/deals/stages');
    }


    public function save_deals_notes($id)
    {

        $data = $this->deals_model->deals_array_from_post(array('notes'));

        //save data into table.
        $this->deals_model->_table_name = 'tbl_deals';
        $this->deals_model->_primary_key = 'id';
        $id = $this->deals_model->save_deals($data, $id);

        // save into activities
        if (!empty($id)) {
            $msg = _l('update_deals_notes');
            $activity = 'activity_update_deals_notes';
        } else {
            $msg = _l('deals_notes_added');
            $activity = 'activity_update_deals_notes';
        }
        log_activity($activity . ' - ' . $data['notes'] . ' [ID:' . $id . ']');

        $this->deals_model->log_deals_activity($id, 'not_deal_activity_notes', false, serialize(
            array(
                $data['notes'],
            )
        ));
        // messages for user
        $type = "success";
        set_alert($type, $msg);
        redirect('admin/deals/details/' . $id . '/' . 'notes');
    }

    public
    function saved_call($deals_id, $id = NULL)
    {
        $data = $this->deals_model->deals_array_from_post(array('date', 'call_summary', 'client_id', 'user_id', 'call_type', 'outcome', 'duration'));
        $data['module'] = 'deals';
        $data['module_field_id'] = $deals_id;
        $this->deals_model->_table_name = 'tbl_deal_calls';
        $this->deals_model->_primary_key = 'calls_id';
        $return_id = $this->deals_model->save_deals($data, $id);
        if (!empty($id)) {
            $id = $id;
            $activity = 'activity_update_deals_call';
            $msg = _l('update_deals_call');
        } else {
            $id = $return_id;
            $activity = 'not_deal_activity_call';
            $msg = _l('save_deals_call');
        }

        $this->deals_model->log_deals_activity($deals_id, $activity, false, serialize([
            $data['call_summary'],
        ]));

        log_activity($activity . ' - ' . $data['date'] . ' [ID:' . $id . ']');
        // messages for user
        $deals_info = $this->deals_model->check_by_deals(array('id' => $deals_id), 'tbl_deals');
        $notifiedUsers = array();
        if (!empty($deals_info->permission) && $deals_info->permission != 'all') {
            $permissionUsers = json_decode($deals_info->permission);
            foreach ($permissionUsers as $user => $v_permission) {
                array_push($notifiedUsers, $user);
            }
        } else {
            // $notifiedUsers = $this->deals_model->allowed_user_id('55');
        }
        if (!empty($notifiedUsers)) {
            foreach ($notifiedUsers as $users) {
                if ($users != $this->session->userdata('user_id')) {
                    add_notification(array(
                        'to_user_id' => $users,
                        'from_user_id' => true,
                        'description' => 'not_add_call',
                        'link' => 'admin/deals/details/' . $deals_info->id . '/call',
                        'value' => _l('lead') . ' ' . $deals_info->title,
                    ));
                }
            }
            pusher_trigger_notification($notifiedUsers);
        }
        // messages for user
        $type = "success";
        $message = $msg;
        set_alert($type, $message);
        redirect('admin/deals/details/' . $deals_id . '/' . 'call');
    }

    public
    function delete_items($id, $deals_id)
    {
        $all_items = $this->deals_model->check_by_deals(array('items_id' => $id), 'tbl_deals_items');
        if (empty($all_items)) {
            $data['type'] = 'error';
            $data['msg'] = _l('no_record_found');
        } else {

            $this->deals_model->_table_name = "tbl_deals_items";
            $this->deals_model->_primary_key = "items_id";
            $this->deals_model->delete_deals($id);
            $_data['id'] = $deals_id;
            $data['subview'] = $this->load->view('deals/deals_details/dealItems', $_data, true);
            $data['type'] = 'success';
            $data['msg'] = _l('deals_items_delete');
        }

        // set message
        set_alert($data['type'], $data['msg']);
        redirect('admin/deals/details/' . $deals_id . '/products');
    }

    public
    function delete_deals_email($deals_id, $id)
    {
        $email_info = $this->deals_model->check_by_deals(array('id' => $id), 'tbl_deals_email');
        if (empty($email_info)) {
            $data['type'] = 'error';
            $data['msg'] = _l('no_record_found');
        } else {
            $this->deals_model->_table_name = 'tbl_deals_email';
            $this->deals_model->_primary_key = 'id';
            $this->deals_model->delete_deals($id);
            $data['type'] = 'success';
            $data['msg'] = _l('deals_email_deleted');
        }
        set_alert($data['type'], $data['msg']);
        redirect('admin/deals/details/' . $deals_id . '/email');
    }

    public
    function delete_deals_call($id, $calls_id)
    {
        $calls_info = $this->deals_model->check_by_deals(array('calls_id' => $calls_id), 'tbl_deal_calls');
        if (empty($calls_info)) {
            $data['type'] = 'error';
            $data['msg'] = _l('no_record_found');
        } else {
            $this->deals_model->_table_name = 'tbl_deal_calls';
            $this->deals_model->_primary_key = 'calls_id';
            $success = $this->deals_model->delete_deals($calls_id);
            $data['type'] = 'success';
            $data['msg'] = _l('deals_call_deleted');
        }
        set_alert($data['type'], $data['msg']);
        redirect('admin/deals/details/' . $id . '/call');
    }

    public function delete_deals_mettings($id, $mettings_id)
    {
        $mettings_info = $this->deals_model->check_by_deals(array('mettings_id' => $mettings_id), 'tbl_deals_mettings');
        if (empty($mettings_info)) {
            $data['type'] = 'error';
            $data['msg'] = _l('no_record_found');
        } else {
            $this->deals_model->_table_name = 'tbl_deals_mettings';
            $this->deals_model->_primary_key = 'mettings_id';
            $this->deals_model->delete_deals($mettings_id);
            $data['type'] = 'success';
            $data['msg'] = _l('mettings_deleted');
        }
        set_alert($data['type'], $data['msg']);
        redirect('admin/deals/details/' . $id . '/mettings');
    }


    public function meeting_details($mettings_id = null)
    {
        $data['title'] = _l('meeting_details');
        $data['details'] = get_deals_row('tbl_deals_mettings', array('mettings_id' => $mettings_id));
        $data['subview'] = $this->load->view('deals/meeting_details', $data, FALSE);
        $this->load->view('deals/_layout_modal', $data);
    }

    public function saved_metting($id = NULL)
    {
        $this->deals_model->_table_name = 'tbl_deals_mettings';
        $this->deals_model->_primary_key = 'mettings_id';
        $deals_id = $this->input->post('deals_id', true);
        $data = $this->deals_model->deals_array_from_post(array('meeting_subject', 'user_id', 'location', 'description'));
        $data['module'] = 'deals';
        $data['module_field_id'] = $deals_id;
        $data['start_date'] = $this->input->post('start_date', true);
        $data['end_date'] = $this->input->post('end_date', true);
        $user_id = serialize($this->deals_model->deals_array_from_post(array('attendees')));
        if (!empty($user_id)) {
            $data['attendees'] = $user_id;
        } else {
            $data['attendees'] = '-';
        }

        $return_id = $this->deals_model->save_deals($data, $id);

        if (!empty($id)) {
            $id = $id;
            $activity = 'not_deal_activity_metting_updated';
            $msg = _l('update_deals_metting');
        } else {
            // $id = $return_id;
            $activity = 'not_deal_activity_metting';
            $msg = _l('save_deals_metting');
        }
        $this->deals_model->log_deals_activity($return_id, $activity, false, serialize(
            array(
                $data['meeting_subject'],
            )
        ));

        log_activity($activity . ' - ' . $data['meeting_subject'] . ' [ID:' . $id . ']');

        $deals_info = $this->deals_model->check_by_deals(array('id' => $data['deals_id']), 'tbl_deals');
        $notifiedUsers = array();
        if (!empty($deals_info->permission) && $deals_info->permission != 'all') {
            $permissionUsers = json_decode($deals_info->permission);
            foreach ($permissionUsers as $user => $v_permission) {
                array_push($notifiedUsers, $user);
            }
        }

        $type = "success";

        // messages for user
        set_alert($type, $msg);
        redirect('admin/deals/details/' . $deals_id . '/mettings');
    }


    public function save_task($id = null)
    {

        $created = has_permission('deals', '', 'create');
        $edited = has_permission('deals', '', 'edit');
        if (!empty($created) || !empty($edited) && !empty($id)) {
            $data = $this->tasks_model->deals_array_from_post(array(
                'module',
                'module_field_id',
                'task_name',
                'stage_id',
                'task_description',
                'task_start_date',
                'due_date',
                'module_field_id',
                'task_progress',
                'calculate_progress',
                'client_visible',
                'task_status',
                'hourly_rate',
                'tags',
                'billable'
            ));
            $estimate_hours = $this->input->post('task_hour', true);
            $check_flot = explode('.', $estimate_hours);
            if (!empty($check_flot[0])) {
                if (!empty($check_flot[1])) {
                    $data['task_hour'] = $check_flot[0] . ':' . $check_flot[1];
                } else {
                    $data['task_hour'] = $check_flot[0] . ':00';
                }
            } else {
                $data['task_hour'] = '0:00';
            }


            if ($data['task_status'] == 'completed') {
                $data['task_progress'] = 100;
            }
            if ($data['task_progress'] == 100) {
                $data['task_status'] = 'completed';
            }
            if (empty($id)) {
                $data['created_by'] = $this->session->userdata('user_id');
            }
            if (empty($data['billable'])) {
                $data['billable'] = 'No';
            }
            if (empty($data['hourly_rate'])) {
                $data['hourly_rate'] = '0';
            }
            $result = 0;

            $data['project_id'] = null;
            $data['milestones_id'] = null;
            $data['goal_tracking_id'] = null;
            $data['bug_id'] = null;
            $data['leads_id'] = null;
            $data['sub_task_id'] = null;
            $data['transactions_id'] = null;


            $permission = $this->input->post('permission', true);
            if (!empty($permission)) {
                if ($permission == 'everyone') {
                    $assigned = 'all';
                    $assigned_to['assigned_to'] = $this->tasks_model->allowed_user_id('54');
                } else {
                    $assigned_to = $this->tasks_model->deals_array_from_post(array('assigned_to'));
                    if (!empty($assigned_to['assigned_to'])) {
                        foreach ($assigned_to['assigned_to'] as $assign_user) {
                            $assigned[$assign_user] = $this->input->post('action_' . $assign_user, true);
                        }
                    }
                }
                if (!empty($assigned)) {
                    if ($assigned != 'all') {
                        $assigned = json_encode($assigned);
                    }
                } else {
                    $assigned = 'all';
                }
                $data['permission'] = $assigned;
            } else {
                set_alert('error', _l('assigned_to') . ' Field is required');
                if (empty($_SERVER['HTTP_REFERER'])) {
                    redirect('admin/tasks/all_task');
                } else {
                    redirect($_SERVER['HTTP_REFERER']);
                }
            }

            //save data into table.
            $this->tasks_model->_table_name = "tbl_task"; // table name
            $this->tasks_model->_primary_key = "task_id"; // $id
            $id = $this->tasks_model->save_deals($data, $id);

            $this->tasks_model->set_task_progress($id);

            $u_data['index_no'] = $id;
            $id = $this->tasks_model->save_deals($u_data, $id);
            $u_data['index_no'] = $id;
            $id = $this->tasks_model->save_deals($u_data, $id);
            save_custom_field(3, $id);

            if ($assigned == 'all') {
                $assigned_to['assigned_to'] = $this->tasks_model->allowed_user_id('54');
            }

            if (!empty($id)) {
                $msg = _l('update_task');
                $activity = 'activity_update_task';
                $id = $id;
                if (!empty($assigned_to['assigned_to'])) {
                    // send update
                    $this->notify_assigned_tasks($assigned_to['assigned_to'], $id, true);
                }
            } else {
                $msg = _l('save_task');
                $activity = 'activity_new_task';
                if (!empty($assigned_to['assigned_to'])) {
                    $this->notify_assigned_tasks($assigned_to['assigned_to'], $id);
                }
            }

            $url = 'admin/' . $data['module'] . '/tasks/' . $id;
            // save into activities

            log_activity($activity . ' - ' . $data['task_name'] . ' [ID:' . $id . ']');
            // messages for use

            if (!empty($data['project_id'])) {
                $this->tasks_model->set_progress($data['project_id']);
            }

            $type = "success";
            $message = $msg;
            set_alert($type, $message);
            redirect('admin/tasks/details/' . $id);
        } else {
            redirect($_SERVER['HTTP_REFERER']);
        }
    }

    public function new_category()
    {
        $data['title'] = _l('new') . ' ' . _l('categories');
        $data['type'] = 'deals';
        $data['subview'] = $this->load->view('deals/new_category', $data, FALSE);
        $this->load->view('admin/_layout_modal', $data);
    }

    public function update_category($id = null)
    {
        $this->deals_model->_table_name = 'tbl_stage_name';
        $this->deals_model->_primary_key = 'stage_name_id';

        $cate_data['stage_name'] = $this->input->post('stage_name', TRUE);
        $cate_data['description'] = $this->input->post('description', TRUE);
        $type = $this->input->post('type', TRUE);
        if (!empty($type)) {
            $cate_data['type'] = $type;
        } else {
            $cate_data['type'] = 'client';
        }
        // update root category
        $where = array('type' => $cate_data['type'], 'stage_name' => $cate_data['stage_name']);
        // duplicate value check in DB
        if (!empty($id)) { // if id exist in db update data
            $stage_name_id = array('stage_name_id !=' => $id);
        } else { // if id is not exist then set id as null
            $stage_name_id = null;
        }
        // check whether this input data already exist or not
        $check_category = $this->deals_model->check_deals_update('tbl_stage_name', $where, $stage_name_id);
        if (!empty($check_category)) { // if input data already exist show error alert
            // massage for user
            $type = 'error';
            $msg = "<strong style='color:#000'>" . $cate_data['stage_name'] . '</strong>  ' . _l('already_exist');
        } else { // save and update query
            $id = $this->deals_model->save_deals($cate_data, $id);

            $activity = array(
                'user' => $this->session->userdata('user_id'),
                'module' => 'settings',
                'module_field_id' => $id,
                'activity' => ('stage_name_added'),
                'value1' => $cate_data['stage_name']
            );
            $this->deals_model->_table_name = 'tbl_activities';
            $this->deals_model->_primary_key = 'activities_id';
            $this->deals_model->save_deals($activity);

            // messages for user
            $type = "success";
            $msg = _l('category_added');
        }
        if (!empty($id)) {
            $result = array(
                'id' => $id,
                'group' => $cate_data['stage_name'],
                'status' => $type,
                'message' => $msg,
            );
        } else {
            $result = array(
                'status' => $type,
                'message' => $msg,
            );
        }
        echo json_encode($result);
        exit();
    }

    public
    function details($id, $active = NULL, $edit = NULL)
    {
        $data['title'] = _l('deals_details'); //Page title
        $data['deals_details'] = $this->deals_model->dealInfo($id);
        $data['dropzone'] = true;
        //get all task information
        if (empty($active)) {
            $data['active'] = 'details';
        } else {
            $data['active'] = $active;
        }
        $data['all_tabs'] = deals_details_tabs($id);
        $data['activity_log'] = $this->deals_model->get_lead_activity_log($id);
        $data['module'] = 'deals';
        $data['id'] = $id;
        $data['staff'] = $this->staff_model->get('', ['active' => 1]);
        $data['global'] = $this->load->view('deals/deals_details/global', $data, TRUE);
        $data['subview'] = $this->load->view('deals_details/tab_view', $data, TRUE);
        $this->load->view('deals/_layout_main', $data);
    }

    public function add_deals_assignees($dealid)
    {
        $assignees = $this->input->post('assignee');

        $deal_info = get_deals_row('tbl_deals', array('id' => $dealid));
        if (!empty($deal_info)) {
            if (staff_can('edit', 'deals') ||
                ($deal_info->current_user_is_creator && staff_can('create', 'deals'))) {
                $this->deals_model->_table_name = 'tbl_deals';
                $this->deals_model->_primary_key = 'id';

                $data = array(
                    'user_id' => json_encode($assignees),
                );

                //

                $success = $this->deals_model->save_deals($data, $dealid);
                $message = '';
                if ($success) {
                    $message = _l('deal_assignee_added');
                }
                $this->deals_model->log_deals_activity($dealid, 'not_deals_assignee', false, serialize([
                    implode(', ', array_map(function ($assignee) {
                        return get_staff_full_name($assignee);
                    }, $assignees)),
                ]));
                set_alert('success', $message);
                redirect($_SERVER['HTTP_REFERER']);
            }
        }
    }

    public function remove_assignee($id, $deal_id)
    {
        $deal_info = get_deals_row('tbl_deals', array('id' => $deal_id));
        if (!empty($deal_info)) {
            if (staff_can('edit', 'deals') ||
                ($deal_info->current_user_is_creator && staff_can('create', 'deals'))) {
                $permission = json_decode($deal_info->user_id);
                if (!empty($permission)) {
                    // remove $id from array
                    if (($key = array_search($id, $permission)) !== false) {
                        unset($permission[$key]);
                    }
                    $this->deals_model->_table_name = 'tbl_deals';
                    $this->deals_model->_primary_key = 'id';
                    // reset array index
                    $permission = array_values($permission);
                    if (!empty($permission)) {
                        $data = array(
                            'user_id' => json_encode($permission),
                        );
                    } else {
                        $data = array(
                            'user_id' => null,
                        );
                    }
                    $success = $this->deals_model->save_deals($data, $deal_id);
                    $message = '';
                    if ($success) {
                        $message = _l('deal_assignee_removed');
                    }
                    $this->deals_model->log_deals_activity($deal_id, 'not_deals_assignee_removed', false, serialize([
                        get_staff_full_name($id),
                    ]));
                    echo json_encode([
                        'success' => $success,
                        'message' => $message,
                    ]);
                }
            }

        }
    }

    public function change_deal_owner($staffId, $dealId)
    {
        $deal_info = get_deals_row('tbl_deals', array('id' => $dealId));
        if (!empty($deal_info)) {
            if (staff_can('edit', 'deals') ||
                ($deal_info->current_user_is_creator && staff_can('create', 'deals'))) {
                $this->deals_model->_table_name = 'tbl_deals';
                $this->deals_model->_primary_key = 'id';

                $data = array(
                    'default_deal_owner' => $staffId,
                );

                $success = $this->deals_model->save_deals($data, $dealId);
                $message = '';
                if ($success) {
                    $message = _l('deal_owner_changed');
                }
                $this->deals_model->log_deals_activity($dealId, 'not_deal_owner_changed', false, serialize([
                    get_staff_full_name($staffId),
                ]));

                echo json_encode([
                    'success' => true,
                    'message' => $message,
                ]);
            }
        }
    }

    public
    function new_attachment($module, $id)
    {
        $data['title'] = lang('new_attachment');
        $data['dropzone'] = true;
        $data['module'] = $module;
        $data['module_field_id'] = $id;
        $data['subview'] = $this->load->view('deals/deals_details/new_attachment', $data, FALSE);
        $this->load->view('deals/_layout_modal', $data);
    }

    function validate_project_file()
    {
        return validate_post_file($this->input->post("file_name", true));
    }

    function upload_file()
    {
        upload_file_to_temp();
    }

    public function download_all_attachment($type, $id)
    {

        $attachment_info = get_deals_result('tbl_attachments', array('module' => $type, 'module_field_id' => $id));
        $FileName = $type . '_attachment';
        $this->load->library('zip');
        if (!empty($attachment_info) && !empty($FileName)) {
            foreach ($attachment_info as $v_attach) {
                $uploaded_files_info = $this->db->where('attachments_id', $v_attach->attachments_id)->get('tbl_attachments_files')->result();
                $filename = slug_it($FileName);
                foreach ($uploaded_files_info as $v_files) {
                    $down_data = ($v_files->files); // Read the file's contents
                    $this->zip->read_file($down_data);
                }
                $this->zip->download($filename . '.zip');
            }
        } else {
            $type = "error";
            $message = lang('operation_failed');
            // set_message($type, $message);
            if (empty($_SERVER['HTTP_REFERER'])) {
                redirect('admin/dashboard');
            } else {
                redirect($_SERVER['HTTP_REFERER']);
            }
        }
    }


    public function download_files($deal_id, $comment_id = null)
    {
        $taskWhere = 'external IS NULL';
        if ($comment_id) {
            $taskWhere .= ' AND deal_comment_id=' . $this->db->escape_str($comment_id);
        }

        $files = $this->deals_model->get_deal_attachments($deal_id, $taskWhere);

        if (count($files) == 0) {
            redirect($_SERVER['HTTP_REFERER']);
        }

        $path = get_upload_path_for_deal() . $deal_id;

        $this->load->library('zip');

        foreach ($files as $file) {
            $this->zip->read_file($path . '/' . $file['file_name']);
        }

        $this->zip->download('files.zip');
        $this->zip->clear_data();
    }


    /* Remove task comment / ajax */
    public function remove_comment($id)
    {
        echo json_encode([
            'success' => $this->deals_model->remove_comment($id),
        ]);
    }

    public function downloadd_files($uploaded_files_id, $comments = null)
    {
        $this->load->helper('download');
        if (!empty($comments)) {
            if ($uploaded_files_id) {
                $down_data = file_get_contents('uploads/' . $uploaded_files_id); // Read the file's contents
                if (!empty($down_data)) {
                    force_download($uploaded_files_id, $down_data);
                } else {
                    $type = "error";
                    $message = lang('operation_failed');
                    set_message($type, $message);
                    if (empty($_SERVER['HTTP_REFERER'])) {
                        redirect('admin/dashboard');
                    } else {
                        redirect($_SERVER['HTTP_REFERER']);
                    }
                }
            } else {
                if (empty($_SERVER['HTTP_REFERER'])) {
                    redirect('admin/dashboard');
                } else {
                    redirect($_SERVER['HTTP_REFERER']);
                }
            }
        } else {
            $uploaded_files_info = $this->deals_model->check_by(array('uploaded_files_id' => $uploaded_files_id), 'tbl_attachments_files');
            if ($uploaded_files_info->uploaded_path) {
                $data = file_get_contents($uploaded_files_info->uploaded_path); // Read the file's contents
                force_download($uploaded_files_info->file_name, $data);
            } else {
                if (empty($_SERVER['HTTP_REFERER'])) {
                    redirect('admin/dashboard');
                } else {
                    redirect($_SERVER['HTTP_REFERER']);
                }
            }
        }
    }

    public function changeStatus($id, $status)
    {
        $data['title'] = 'Change Status';
        $data['id'] = $id;
        $data['btn'] = $status;
        $data['status'] = $status;
        $data['deals_details'] = $this->deals_model->dealInfo($id);
        $data['subview'] = $this->load->view('deals/deals_details/_modal_change_status', $data, FALSE);
        $this->load->view('deals/_layout_modal', $data);

    }

    public function changedStatus($id, $status)
    {

        $pdata['status'] = $status;
        if ($status == 'won') {
            $pdata['convert_to_project'] = $this->input->post('convert_to_project', true);
            $create_invoice = $this->input->post('create_invoice', true);

            if ($create_invoice === 'on') {
                $this->createInvoice($id);
            }
        } else if ($status == 'lost') {
            $pdata['lost_reason'] = $this->input->post('lost_reason');
        }
        $this->deals_model->_table_name = 'tbl_deals';
        $this->deals_model->_primary_key = 'id';
        $this->deals_model->save_deals($pdata, $id);

        $this->deals_model->log_deals_activity($id, 'not_deals_status_change', false, serialize([
            $status,
        ]));

        $type = "success";
        $message = _l('deals_status_change');
        set_alert($type, $message);
        redirect($_SERVER['HTTP_REFERER']);
    }

    public function createInvoice($deal_id)
    {
        $deal_info = $this->deals_model->check_by_deals(array('id' => $deal_id), 'tbl_deals');

        $next_invoice_number = get_option('next_invoice_number');
        $format = get_option('invoice_number_format');
        $prefix = get_option('invoice_prefix');
        $__number = $next_invoice_number;

        $all_client = json_decode($deal_info->client_id, true);
        $sales_info = get_deals_result('tbl_deals_items', array('deals_id' => $deal_id));


        $new_items = array();
        $sub_total = 0;
        $item_tax_total = 0;
        if ($deal_info->deal_value > 0) {
            $sub_total += $deal_info->deal_value;
            $new_items[] = array(
                'description' => $deal_info->title,
                'long_description' => $deal_info->notes,
                'qty' => 1,
                'unit' => '',
                'rate' => $deal_info->deal_value,
                'taxname' => [],
                'order' => 1
            );
        }


        if (!empty($sales_info)) {
            foreach ($sales_info as $or => $item) {
                $sub_total += $item->unit_cost * $item->quantity;
                $item_tax_total += $item->item_tax_total;
                $new_items[] = array(
                    'description' => $item->item_name,
                    'long_description' => $item->item_desc,
                    'qty' => $item->quantity,
                    'unit' => $item->unit,
                    'rate' => $item->unit_cost,
                    'taxname' => (!empty($item->item_tax_name) ? json_decode($item->item_tax_name) : []),
                    'order' => $or + 1
                );
            }
        }


        if (get_option('invoice_due_after') != 0) {
            $duedate = (date('Y-m-d', strtotime('+' . get_option('invoice_due_after') . ' DAY', strtotime(date('Y-m-d')))));
        } else {
            $duedate = (date('Y-m-d'));
        }
        $this->load->model('payment_modes_model');
        $this->load->model('currencies_model');
        $payment_modes = $this->payment_modes_model->get('', [
            'expenses_only !=' => 1,
        ]);

        $allowed_payment_modes = [];
        foreach ($payment_modes as $payment_mode) {
            $allowed_payment_modes[] = $payment_mode['id'];
        }
        $currency = $this->currencies_model->get_base_currency()->id;


        if (!empty($all_client)) {
            foreach ($all_client as $client) {
                $clientInfo = $this->clients_model->get($client);
                $__number = $__number + 1;
                $_invoice_number = str_pad($__number, get_option('number_padding_prefixes'), '0', STR_PAD_LEFT);
                $new_invoice = array(
                    'clientid' => $client,
                    'billing_street' => $clientInfo->billing_street,
                    'billing_city' => $clientInfo->billing_city,
                    'billing_state' => $clientInfo->billing_state,
                    'billing_zip' => $clientInfo->billing_zip,
                    'shipping_street' => $clientInfo->shipping_street,
                    'shipping_city' => $clientInfo->shipping_city,
                    'shipping_state' => $clientInfo->shipping_state,
                    'shipping_zip' => $clientInfo->shipping_zip,
                    'number' => $_invoice_number,
                    'date' => date('Y-m-d'),
                    'duedate' => $duedate,
                    'allowed_payment_modes' => $allowed_payment_modes,
                    'currency' => (!empty($clientInfo->default_currency) ? $clientInfo->default_currency : $currency),
                    'sale_agent' => $deal_info->default_deal_owner,
                    'recurring' => 0,
                    'show_quantity_as' => 1,
                    'description' => $deal_info->notes ?: '',
                    'quantity' => 1,
                    'unit' => '',
                    'discount_type' => '',
                    'repeat_every_custom' => 1,
                    'repeat_type_custom' => 'day',
                    'rate' => '',
                    'newitems' => $new_items,
                    'subtotal' => $sub_total,
                    'discount_percent' => 0,
                    'discount_total' => 0.00,
                    'adjustment' => 0,
                    'total' => $sub_total + $item_tax_total,
                    'task_id' => '',
                    'project_id' => '',
                    'expense_id' => '',
                    'clientnote' => '',
                    'terms' => '',
                );

                $this->load->model('invoices_model');
                $invoice_id = $this->invoices_model->add($new_invoice);

                $this->deals_model->_table_name = 'tbl_deals';
                $this->deals_model->_primary_key = 'id';
                $this->deals_model->save_deals(array('invoice_id' => $invoice_id), $deal_id);
            }
        }
        return true;
    }

    public function createProjects($deal_id)
    {
        $deal_info = $this->deals_model->check_by_deals(array('id' => $deal_id), 'tbl_deals');

        $projects = '';
        if (empty(config_item('projects_number_format'))) {
            $projects .= config_item('projects_prefix');
        }
        $projects .= $this->items_model->generate_projects_number();

        $propability = 0;

        $all_stages = get_order_by('tbl_stage_name', array('type' => 'stages', 'description' => $deal_info->pipeline), 'order', true);
        // total stages
        if (!empty($all_stages)) {
            $total_stages = count($all_stages);
            foreach ($all_stages as $stage) {
                $res = round(100 / $total_stages);
                $propability += $res;
                if ($stage->stage_name_id == $deal_info->stage_id) {
                    break;
                }
            }
        }
        if ($deal_info->status === 'won') {
            $propability = 100;
        }
        if ($deal_info->status === 'lost') {
            $propability = 0;
        }

        $permission = array();
        $all_user = json_decode($deal_info->user_id, true);
        if (!empty($all_user)) {
            foreach ($all_user as $user) {
                $permission[$user] = array('view', 'edit', 'delete');
            }
        }
        $permission = json_encode($permission);
        $all_client = json_decode($deal_info->client_id, true);

        if (!empty($all_client)) {
            foreach ($all_client as $client) {
                $new_project = array(
                    'project_no' => $projects,
                    'project_name' => $deal_info->title,
                    'client_id' => $client,
                    'progress' => $propability,
                    'calculate_progress' => '',
                    'start_date' => date('Y-m-d'),
                    'end_date' => $deal_info->days_to_close,
                    'billing_type' => 'fixed_rate',
                    'project_cost' => $deal_info->deal_value,
                    'hourly_rate' => 0,
                    'project_status' => 'started',
                    'estimate_hours' => 0,
                    'description' => $deal_info->notes,
                    'permission' => $permission,
                );
                $this->deals_model->_table_name = "tbl_project"; //table name
                $this->deals_model->_primary_key = "project_id";
                $new_project_id = $this->deals_model->save_deals($new_project);

                $tasks = $this->input->post('tasks', true);
                if (!empty($tasks)) {
                    //get tasks info by id
                    foreach ($tasks as $task_id) {
                        $task_info = get_deals_row('tbl_task', array('task_id' => $task_id));
                        $task = array(
                            'task_name' => $task_info->task_name,
                            'project_id' => $new_project_id,
                            'milestones_id' => $task_info->milestones_id,
                            'permission' => $task_info->permission,
                            'task_description' => $task_info->task_description,
                            'task_start_date' => $task_info->task_start_date,
                            'due_date' => $task_info->due_date,
                            'task_created_date' => $task_info->task_created_date,
                            'task_status' => $task_info->task_status,
                            'task_progress' => $task_info->task_progress,
                            'task_hour' => $task_info->task_hour,
                            'tasks_notes' => $task_info->tasks_notes,
                            'timer_status' => $task_info->timer_status,
                            'client_visible' => $task_info->client_visible,
                            'timer_started_by' => $task_info->timer_started_by,
                            'start_time' => $task_info->start_time,
                            'logged_time' => $task_info->logged_time,
                            'created_by' => $task_info->created_by
                        );
                        $this->deals_model->_table_name = "tbl_task"; //table name
                        $this->deals_model->_primary_key = "task_id";
                        $this->deals_model->save_deals($task);
                    }
                }

                $projects_email = config_item('projects_email');
                if (!empty($projects_email) && $projects_email == 1) {
                    $this->send_project_notify_client($new_project_id);
                    if (!empty($all_user)) {
                        $this->send_project_notify_assign_user($new_project_id, $all_user);
                    }
                }
            }
        }
    }


    public
    function changeStage($deals_id, $stage_id)
    {
        $data['stage_id'] = $stage_id;
        $this->deals_model->_table_name = 'tbl_deals';
        $this->deals_model->_primary_key = 'id';
        $this->deals_model->save_deals($data, $deals_id);
        $type = "success";
        $message = _l('deals_updated');

        $this->deals_model->log_deals_activity($deals_id, 'not_deals_stage_change', false, serialize([
            $stage_id,
        ]));
        set_alert($type, $message);
        redirect($_SERVER['HTTP_REFERER']);
    }

    public
    function itemsSuggestions($id = null)
    {
        $term = $this->input->get('term', TRUE);
        $rows = $this->deals_model->getItemsInfo($term);
        if (!empty($rows)) {
            foreach ($rows as $row) {
                $row->qty = 1;
                $row->rate = $row->rate;
                $row->unit = $row->unit;
                $row->item_id = $row->id;
                $tax = 0;
                $result = (object)array_merge((array)$row, (array)$tax);
                $pr[] = array('item_id' => $row->id, 'label' => $row->description, 'row' => $result);
            }
            echo json_encode($pr);
            die();
        } else {
            echo json_encode(array(array('item_id' => 0, 'label' => _l('no_match_found'), 'value' => $term)));
            die();
        }
    }


    public
    function itemAddedManualy()
    {
        $items_info = (object)$this->input->post();

        $deals_id = $this->input->post('deals_id', true);
        $items_id = $this->input->post('items_id', true);

        if (!empty($items_info)) {
            $saved_items_id = 0;
            $items_info->saved_items_id = $saved_items_id;
            $items_info->code = '';
            $items_info->new_item_id = $saved_items_id;
            $tax_info = $items_info->tax_rates_id;
            $total_cost = $items_info->unit_cost * $items_info->quantity;
            if (!empty($tax_info)) {
                foreach ($tax_info as $v_tax) {
                    $all_tax = $this->db->where('id', $v_tax)->get(db_prefix() . 'taxes')->row();
                    $tax_name[] = $all_tax->name . '|' . $all_tax->taxrate;
                    $item_tax_total[] = ($total_cost / 100 * $all_tax->taxrate);
                }
            }

            $item_tax_total = (!empty($item_tax_total) ? array_sum($item_tax_total) : 0);

            $data['tax_rates_id'] = (!empty($items_info->tax_rates_id) ? json_encode($items_info->tax_rates_id) : '');
            $data['quantity'] = $items_info->quantity;
            $data['deals_id'] = $deals_id;
            $data['item_name'] = $items_info->item_name;
            $data['item_desc'] = $items_info->item_desc;
            $data['hsn_code'] = (!empty($items_info->hsn_code) ? $items_info->hsn_code : '');
            $data['unit_cost'] = $items_info->unit_cost;
            $data['unit'] = $items_info->unit;
            $data['item_tax_rate'] = '0.00';
            $data['item_tax_name'] = (!empty($tax_name) ? json_encode($tax_name) : '');
            $data['item_tax_total'] = (!empty($item_tax_total) ? $item_tax_total : '0.00');
            $data['total_cost'] = $total_cost;
            $data['item_id'] = $items_info->saved_items_id;

            $this->deals_model->_table_name = 'tbl_deals_items';
            $this->deals_model->_primary_key = 'items_id';
            $items_id = $this->deals_model->save_deals($data, $items_id);
            $msg = _l('deals_item_added');
            $activity = 'activity_deals_items_added';
            log_activity($activity . ' - ' . $data['item_name']);
            // messages for user
            $type = "success";

            $_data['id'] = $deals_id;
            $data['subview'] = $this->load->view('deals/deals_details/dealItems', $_data, true);
        } else {
            $type = "error";
            $msg = 'please Select an items';
        }
        set_alert($type, $msg);
        redirect('admin/deals/details/' . $deals_id . '/products');
    }

    public
    function add_insert_items($deals_id)
    {
        $edited = has_permission('deals', '', 'edit');
        if (!empty($edited)) {
            $v_items_id = $this->input->post('item_id', TRUE) ?? 3;
            if (!empty($v_items_id)) {
                $where = array('deals_id' => $deals_id, 'item_id' => $v_items_id);
                $items_info = $this->deals_model->check_by_deals(array('id' => $v_items_id), db_prefix() . 'items');

                // check whether this input data already exist or not
                $check_users = get_deals_row('tbl_deals_items', $where);
                if (!empty($check_users)) { // if input data already exist show error alert
                    // massage for user
                    $cdata['quantity'] = $check_users->quantity + 1;
                    $cdata['total_cost'] = $items_info->rate + $check_users->total_cost;

                    $this->deals_model->_table_name = 'tbl_deals_items';
                    $this->deals_model->_primary_key = 'items_id';
                    $items_id = $this->deals_model->save_deals($cdata, $check_users->items_id);
                } else {
                    $tax_name = array();
                    $total_tax = array();
                    $tax_id = array();


                    if (!empty($items_info->tax)) {
                        $tax_info = $this->db->where('id', $items_info->tax)->get(db_prefix() . 'taxes')->row();
                        $tax_name[] = $tax_info->name . '|' . $tax_info->taxrate;
                        $tax_id[] = $tax_info->id;
                        $total_tax[] = ($items_info->rate / 100 * $tax_info->taxrate);
                    } else {
                        $tax_info = '';
                    }
                    // tax2
                    if (!empty($items_info->tax2)) {
                        $tax_info = $this->db->where('id', $items_info->tax2)->get(db_prefix() . 'taxes')->row();
                        $tax_name[] = $tax_info->name . '|' . $tax_info->taxrate;
                        $tax_id[] = $tax_info->id;
                        $total_tax[] = ($items_info->rate / 100 * $tax_info->taxrate);
                    } else {
                        $tax_info = '';
                    }
                    $item_tax_total = (!empty($total_tax) ? array_sum($total_tax) : 0);


                    $data['quantity'] = 1;
                    $data['deals_id'] = $deals_id;
                    $data['tax_rates_id'] = (!empty($tax_id) ? json_encode($tax_id) : '');
                    $data['item_name'] = $items_info->description;
                    $data['item_desc'] = $items_info->long_description;
                    $data['unit_cost'] = $items_info->rate;
                    $data['unit'] = $items_info->unit;
                    $data['item_tax_rate'] = '0.00';
                    $data['item_tax_name'] = (!empty($tax_name) ? json_encode($tax_name) : '');
                    $data['item_tax_total'] = (!empty($item_tax_total) ? $item_tax_total : '0.00');
                    $data['total_cost'] = $items_info->rate;
                    $data['item_id'] = $items_info->id;

                    // get all client
                    $this->deals_model->_table_name = 'tbl_deals_items';
                    $this->deals_model->_primary_key = 'items_id';
                    $items_id = $this->deals_model->save_deals($data);
                }
                $action = ('activity_deals_items_added');
                $this->deals_model->log_deals_activity($deals_id, $action, false, serialize([
                    $items_info->description,
                ]));
                $type = "success";
                $msg = _l('deals_item_added');
                $_data['id'] = $deals_id;
                $data['subview'] = $this->load->view('deals/deals_details/dealItems', $_data, true);
            } else {
                $type = "error";
                $msg = 'please Select an items';
            }
            $message = $msg;
            $data['type'] = $type;
            $data['msg'] = $msg;
            echo json_encode($data);
            exit();
        } else {
            set_alert('error', _l('there_in_no_value'));
            if (empty($_SERVER['HTTP_REFERER'])) {
                redirect('admin/deals/details');
            } else {
                redirect($_SERVER['HTTP_REFERER']);
            }
        }
    }

    public
    function send_mail($id = null)
    {

        $data = $this->deals_model->deals_array_from_post(array('subject', 'message_body', 'deals_id', 'email_to', 'email_cc'));

        $user_id = get_staff_user_id();
        $user_info = $this->deals_model->check_by_deals(array('staffid' => $user_id), db_prefix() . 'staff');
        // get company name
        $name = $user_info->firstname . ' ' . $user_info->lastname;
        $params['subject'] = $data['subject'];
        $params['message'] = $data['message_body'];
        $params['recipient'] = $data['email_to'];
        $params['cc'] = $data['email_cc'];
        $params['fullname'] = $name;
        $params['recipient'] = $data['email_to'];
        $params['deal_id'] = $data['deals_id'];


        // save into inbox table procees
        $idata['email_to'] = $data['email_to'];
        $idata['email_cc'] = $data['email_cc'];

        $idata['email_from'] = $user_info->email;
        $idata['user_id'] = $user_id;
        $idata['deals_id'] = $data['deals_id'];
        $idata['subject'] = $data['subject'];
        $idata['message_body'] = $data['message_body'];
        $idata['message_time'] = date('Y-m-d H:i:s');
        // save into inbox
        $this->deals_model->_table_name = 'tbl_deals_email';
        $this->deals_model->_primary_key = 'id';
        $deal_id = $this->deals_model->save_deals($idata, $id);
        $attachments = handle_deal_attachments_array($deal_id);
        $params['attachments'] = $attachments;
        $params['template'] = $params;

        $send_email = $this->deals_model->send_email($params);


        // update deal $attachments using json_encode
        $this->deals_model->_table_name = 'tbl_deals_email';
        $this->deals_model->_primary_key = 'id';
        $this->deals_model->save_deals(array('attach_file' => json_encode($attachments)), $deal_id);

        $this->deals_model->log_deals_activity($data['deals_id'], 'not_deals_email_sent', false, serialize([
            $data['subject'],
        ]));
        $type = "success";
        $message = _l('msg_sent');
        set_alert($type, $message);

        if (!empty($id)) {
            $msg = _l('msg_sent');
            $activity = 'activity_msg_sent';
        } else {
            $msg = _l('msg_sent');
            $activity = 'activity_msg_sent';
        }
        log_activity($activity . ' - ' . $data['subject'] . ' [ID:' . $id . ']');
        // messages for user
        $type = "success";
        set_alert($type, $msg);
        redirect('admin/deals/details/' . $data['deals_id'] . '/email');
    }


    public
    function dealsManuallyItems()
    {
        $data['title'] = _l('added') . ' ' . _l('manually');
        $data['subview'] = $this->load->view('deals/deals_manually_items', $data, false);
        $this->load->view('admin/_layout_modal', $data);
    }

    public
    function email_details($deals_email_id = null)
    {
        $data['title'] = _l('email_details');
        $data['details'] = get_deals_row('tbl_deals_email', array('id' => $deals_email_id));
        $data['subview'] = $this->load->view('deals/email_details', $data, false);
        $this->load->view('deals/_layout_modal', $data);
    }

    public
    function call_details($deals_email_id = null)
    {
        $data['title'] = _l('call_details');
        $data['details'] = get_deals_row('tbl_deal_calls', array('calls_id' => $deals_email_id));
        $data['subview'] = $this->load->view('deals/call_details', $data, FALSE);
        // $this->load->view('admin/_layout_modal', $data);
        $this->load->view('deals/_layout_modal', $data);
    }

    public
    function manuallyItems($deals_id = null, $items_id = null)
    {
        $data['deals_id'] = $deals_id;
        if (!empty($items_id)) {
            $data['items_info'] = get_deals_row('tbl_deals_items', array('items_id' => $items_id));
        }
        $data['subview'] = $this->load->view('deals/deals_manually_items', $data, FALSE);
        $this->load->view('deals/_layout_modal', $data);
    }

    public
    function download_file($file)
    {
        $this->load->helper('download');
        if (file_exists(('uploads/' . $file))) {
            $down_data = file_get_contents('uploads/' . $file); // Read the file's contents
            force_download($file, $down_data);
        } else {
            $type = "error";
            $message = 'Operation Fieled !';
            set_alert($type, $message);
            if (empty($_SERVER['HTTP_REFERER'])) {
                redirect('admin/mailbox');
            } else {
                redirect($_SERVER['HTTP_REFERER']);
            }
        }
    }


    public
    function updateUsers($deals_id, $type)
    {
        // post data
        $data['deals'] = $this->deals_model->check_by_deals(array('id' => $deals_id), 'tbl_deals');
        $type_id = $this->input->post($type . '_id', true);
        if (!empty($type_id)) {
            $_data[$type . '_id'] = json_encode($type_id);
            $this->deals_model->_table_name = 'tbl_deals';
            $this->deals_model->_primary_key = 'id';
            $this->deals_model->save_deals($_data, $deals_id);

            if ($type == 'user') {

                foreach ($type_id as $v_user) {
                    if ($v_user != $this->session->userdata('user_id')) {
                        add_notification(array(
                            'to_user_id' => $v_user,
                            'from_user_id' => true,
                            'description' => 'not_deals_added_you',
                            'link' => 'admin/deals/details/' . $deals_id,
                            'value' => $data['deals']->title,
                        ));
                    }
                }
                pusher_trigger_notification($type_id);
            }
            $type = "success";
            $message = _l('deals_update_user');
            set_alert($type, $message);
            redirect('admin/deals/details/' . $deals_id);
        }
        $data['title'] = _l('update_' . $type);
        $data['type'] = $type;

        $data['subview'] = $this->load->view('deals/_modal_users', $data, FALSE);
        $this->load->view('deals/_layout_modal', $data);
    }


    public
    function save_deals_email_integration()
    {
        $input_data = $this->deals_model->deals_array_from_post(array(
            'encryption_deals', 'default_pipeline', 'default_stage', 'default_deal_owner', 'config_deals_host', 'config_deals_username', 'config_deals_mailbox', 'unread_deals_email', 'delete_mail_after_deals_import'
        ));

        $config_password = $this->input->post('config_deals_password', true);
        if (!empty($config_password)) {
            $input_data['config_deals_password'] = encrypt($config_password);
        }
        if ($input_data['encryption_deals'] == 'on') {
            $input_data['encryption_deals'] = null;
        }
        if (empty($input_data['unread_deals_email'])) {
            $input_data['unread_deals_email'] = 'on';
        }
        if (empty($input_data['delete_mail_after_deals_import'])) {
            $input_data['delete_mail_after_deals_import'] = null;
        }
        foreach ($input_data as $key => $value) {
            $data = array('value' => $value);
            $this->db->where('config_key', $key)->update('tbl_config', $data);
            $exists = $this->db->where('config_key', $key)->get('tbl_config');
            if ($exists->num_rows() == 0) {
                $this->db->insert('tbl_config', array("config_key" => $key, "value" => $value));
            }
        }
        $msg = _l('save_deals_email_integration');
        $activity = 'activity_save_deals_email_integration';

        log_activity($activity . ' - ' . $data['notes']);
        // messages for user
        $type = "success";
        set_alert($type, $msg);
        redirect('admin/deals/deals_setting');
    }

    public
    function sales_pipelines($id = NULL, $opt = null)
    {
        $data['title'] = _l('new_pipeline');
        if (!empty($id)) {
            $data['pipeline'] = $this->deals_model->check_by_deals(array('pipeline_id' => $id), 'tbl_deals_pipelines');
        }
        $this->load->view('sales_pipelines', $data);
    }

    public
    function saved_pipelines($id = null)
    {

        $cate_data['pipeline_name'] = $this->input->post('pipeline_name', TRUE);

        $this->deals_model->_table_name = 'tbl_deals_pipelines';
        $this->deals_model->_primary_key = 'pipeline_id';
        $id = $this->deals_model->save_deals($cate_data, $id);


        if (!empty($id)) {
            $msg = _l('successfully_pipelines_update');
            $activity = 'activity_successfully_pipelines_added_update';
        } else {
            $msg = _l('successfully_pipelines_added');
            $activity = 'activity_successfully_pipelines_added';
        }
        log_activity($activity . ' - ' . $cate_data['pipeline_name'] . ' [ID:' . $id . ']');
        $type = "success";

        set_alert($type, $msg);
        redirect('admin/deals/sales_pipelines');
    }

    public function delete_email_attachment($id, $file_name)
    {
        $this->db->where('id', $id);
        $deal_email_info = $this->db->get('tbl_deals_email')->row();
        if (!empty($deal_email_info)) {


            $deals_id = $deal_email_info->deals_id;
            $attachment = $deal_email_info->attach_file;

            // remove file from folder if exist
            $path = get_upload_path_for_deal() . $deals_id . '/' . $file_name;
            if (file_exists($path)) {
                unlink($path);
            }

            $attachment = json_decode($attachment);
            $new_attachment = array();
            foreach ($attachment as $v_attachment) {
                if ($v_attachment->file_name != $file_name) {
                    $new_attachment[] = $v_attachment;
                }
            }

            $this->db->where('id', $id);
            $this->db->update('tbl_deals_email', array('attach_file' => json_encode($new_attachment)));
            $type = "success";
            $message = _l('successfully_delete');
            set_alert($type, $message);
            redirect('admin/deals/details/' . $deals_id . '/email');
        } else {
            show_404();
        }


    }

    public function file_download($folder_indicator, $attachmentid = '', $file_name = '')
    {
        if (!empty($folder_indicator == 'deals_attachment' || $folder_indicator == 'deals_comments')) {
            if (!is_staff_logged_in()) {
                show_404();
            }
            // admin area

            if ($folder_indicator == 'deals_attachment') {
                $this->db->where('id', $attachmentid);
            } else {
                // Lead public form
                $this->db->where('attachment_key', $attachmentid);
            }

            $attachment = $this->db->get(db_prefix() . 'files')->row();

            if (!$attachment) {
                show_404();
            }

            $path = get_upload_path_for_deal() . $attachment->rel_id . '/' . $attachment->file_name;
            force_download($path, null);
        } else if ($folder_indicator == 'deals_email') {
            if (!is_staff_logged_in()) {
                show_404();
            }
            // client area
            $this->db->where('id', $attachmentid);
            $deal_email_info = $this->db->get('tbl_deals_email')->row();
            if (!empty($deal_email_info)) {
                $deals_id = $deal_email_info->deals_id;
                $attachment = $deal_email_info->attach_file;
                $attachment = json_decode($attachment);

                // if file_name is empty then download all attachments
                if (empty($file_name)) {
                    $path = get_upload_path_for_deal() . $deals_id . '/';
                    $zipname = 'deals_' . $deals_id . '_attachments.zip';
                    $zip = new ZipArchive;
                    $zip->open($zipname, ZipArchive::CREATE);
                    foreach ($attachment as $v_attachment) {
                        $zip->addFile($path . $v_attachment->file_name, $v_attachment->file_name);
                    }
                    $zip->close();
                    header('Content-Type: application/zip');
                    header('Content-disposition: attachment; filename=' . $zipname);
                    header('Content-Length: ' . filesize($zipname));
                    readfile($zipname);
                    unlink($zipname);
                } else {
                    // get attachments file according to file_name
                    foreach ($attachment as $v_attachment) {
                        if ($v_attachment->file_name == $file_name) {
                            $path = get_upload_path_for_deal() . $deals_id . '/' . $v_attachment->file_name;
                            force_download($path, null);
                        }
                    }
                }


            } else {
                show_404();
            }

        } else {
            show_404();
        }
    }

    public function add_activity()
    {
        $deal_id = $this->input->post('deal_id');

        if (!is_staff_member()) {
            ajax_access_denied();
        }
        if ($this->input->post()) {
            $message = $this->input->post('activity');
            $aId = $this->deals_model->log_deals_activity($deal_id, $message);

            if ($aId) {
                $this->db->where('id', $aId);
                $this->db->update('tbl_deal_activity_log', ['custom_activity' => 1]);
            }

        }
    }
}
