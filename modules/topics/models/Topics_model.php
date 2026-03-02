<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Topics_model extends App_Model
{
    // Thêm constant cho target type backup
    private const BACKUP_TARGET_TYPE = 'TOPLIST_SINGLE_ITEM_RAW_BACKUP';
    private const BACKUP_TARGET_ID = 4;

    public function __construct()
    {
        parent::__construct();
        $this->table = db_prefix() . 'topics';
    }

    public function get_all_topics()
    {
        $this->db->select([
            $this->table.'.id',
            $this->table.'.topicid',
            $this->table.'.topictitle',
            $this->table.'.log',
            $this->table.'.action_type_code',
            $this->table.'.action_state_code',
            $this->table.'.target_id',
            $this->table.'.datecreated',
            $this->table.'.dateupdated',
            db_prefix().'topic_action_types.name as action_type_name',
            db_prefix().'topic_action_states.name as action_state_name'
        ]);
        
        $this->db->from($this->table);
        $this->db->join(
            db_prefix().'topic_action_types', 
            db_prefix().'topic_action_types.action_type_code = '.$this->table.'.action_type_code', 
            'left'
        );
        $this->db->join(
            db_prefix().'topic_action_states', 
            db_prefix().'topic_action_states.action_state_code = '.$this->table.'.action_state_code', 
            'left'
        );
        
        return $this->db->get()->result();
    }

    public function get_topic($id)
    {
        $this->db->select([
            $this->table.'.id', 
            $this->table.'.topicid',
            $this->table.'.topictitle',
            $this->table.'.log',
            $this->table.'.action_type_code',
            $this->table.'.action_state_code',
            $this->table.'.target_id',
            $this->table.'.datecreated',
            $this->table.'.dateupdated',
            db_prefix().'topic_action_types.name as action_type_name',
            db_prefix().'topic_action_states.name as action_state_name'
        ]);
        
        $this->db->from($this->table);
        $this->db->join(
            db_prefix().'topic_action_types', 
            db_prefix().'topic_action_types.action_type_code = '.$this->table.'.action_type_code', 
            'left'
        );
        $this->db->join(
            db_prefix().'topic_action_states', 
            db_prefix().'topic_action_states.action_state_code = '.$this->table.'.action_state_code', 
            'left'
        );
        $this->db->where($this->table.'.id', $id);
        return $this->db->get()->row();
    }

    public function get_topic_by_topicid($topicid)
    {
        $this->db->select([
            $this->table.'.id', 
            $this->table.'.topicid',
            $this->table.'.topictitle',
            $this->table.'.log',
            $this->table.'.action_type_code',
            $this->table.'.action_state_code',
            $this->table.'.target_id',
            $this->table.'.datecreated',
            $this->table.'.dateupdated',
            db_prefix().'topic_action_types.name as action_type_name',
            db_prefix().'topic_action_states.name as action_state_name'
        ]);
        
        $this->db->from($this->table);
        $this->db->join(
            db_prefix().'topic_action_types', 
            db_prefix().'topic_action_types.action_type_code = '.$this->table.'.action_type_code', 
            'left'
        );
        $this->db->join(
            db_prefix().'topic_action_states', 
            db_prefix().'topic_action_states.action_state_code = '.$this->table.'.action_state_code', 
            'left'
        );
        $this->db->where($this->table.'.topicid', $topicid);
        return $this->db->get()->row();
    }

    public function add_topic($data)
    {
        if (!isset($data['target_id'])) {
            $data['target_id'] = 0;
        }
        $data['datecreated'] = date('Y-m-d H:i:s');
        $data['dateupdated'] = date('Y-m-d H:i:s');
        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }

    public function update_topic($id, $data)
    {
        $data['dateupdated'] = date('Y-m-d H:i:s');
        $this->db->where('id', $id);
        return $this->db->update($this->table, $data);
    }

    public function delete_topic($id)
    {
        return $this->db->delete($this->table, ['id' => $id]);
    }

    public function get_all_topic_ids()
{
    $this->db->select('topicid');
    $query = $this->db->get($this->table);
    return array_column($query->result_array(), 'topicid');
}

    public function get_topic_history($topicid, $action_type = null)
    {
        $this->db->select([
            $this->table . '.id',
            $this->table . '.topicid', 
            $this->table . '.topictitle',
            $this->table . '.log',
            $this->table . '.action_type_code',
            $this->table . '.action_state_code',
            $this->table . '.dateupdated',
            $this->table . '.automation_id',
            'at.name as action_type_name',
            'ast.name as action_state_name',
            'ast.color as state_color',
            'ast.valid_data as valid_data',
            'tal.workflow_id as workflow_id',  
            'tal.automation_id as execution_id',
            'tal.status as automation_status'
        ]);
        
        $this->db->from($this->table);
        
        // Join với bảng action_types
        $this->db->join(
            db_prefix() . 'topic_action_types at', 
            'at.action_type_code = ' . $this->table . '.action_type_code', 
            'left'
        );
        
        // Join với bảng action_states 
        $this->db->join(
            db_prefix() . 'topic_action_states ast', 
            'ast.action_state_code = ' . $this->table . '.action_state_code AND ' .
            'ast.action_type_code = ' . $this->table . '.action_type_code', 
            'left'
        );
        
        // Join trực tiếp với automation_logs theo automation_id
        $this->db->join(
            db_prefix() . 'topic_automation_logs tal',
            'tal.id = ' . $this->table . '.automation_id',
            'left'
        );
        
        $this->db->where($this->table . '.topicid', $topicid);
        
        if ($action_type) {
            $this->db->where($this->table . '.action_type_code', $action_type);
        }
        
        $this->db->order_by($this->table . '.dateupdated', 'DESC');
        $result = $this->db->get()->result();
        // log_message('error', print_r($result, true));
        return $result;
    }

    public function get_topics_grouped() {
        $this->db->select([
            $this->table.'.topicid',
            'MAX('.$this->table.'.topictitle) as topictitle',
            'GROUP_CONCAT('.$this->table.'.log) as logs',
            'GROUP_CONCAT('.db_prefix().'topic_action_types.name) as action_types',
            'GROUP_CONCAT('.db_prefix().'topic_action_states.name) as action_states',
            'GROUP_CONCAT('.$this->table.'.dateupdated) as dates',
            'GROUP_CONCAT('.$this->table.'.id) as ids'
        ]);
        
        $this->db->from($this->table);
        $this->db->join(
            db_prefix().'topic_action_types', 
            db_prefix().'topic_action_types.action_type_code = '.$this->table.'.action_type_code', 
            'left'
        );
        $this->db->join(
            db_prefix().'topic_action_states', 
            db_prefix().'topic_action_states.action_state_code = '.$this->table.'.action_state_code', 
            'left'
        );
        
        $this->db->group_by($this->table.'.topicid');
        $this->db->order_by('MAX('.$this->table.'.dateupdated)', 'DESC');
        
        return $this->db->get()->result_array();
    }

    public function get_log_data($id, $topicid)
    {
        $this->db->select([
            $this->table . '.id',
            $this->table . '.topicid',
            $this->table . '.topictitle',
            $this->table . '.log',
            $this->table . '.dateupdated',
            $this->table . '.automation_id'
        ]);
        
        $this->db->from($this->table);
        
        // Chỉ join và select automation_logs nếu các trường tồn tại
        if ($this->db->field_exists('automation_id', db_prefix() . 'topics')) {
            $automation_logs_table = db_prefix() . 'topic_automation_logs';
            
            if ($this->db->table_exists($automation_logs_table)) {
                $this->db->join(
                    $automation_logs_table . ' tal',
                    'tal.topic_id = ' . $this->table . '.topicid AND tal.automation_id = ' . $this->table . '.automation_id',
                    'left'
                );
                
                if ($this->db->field_exists('execution_id', $automation_logs_table)) {
                    $this->db->select('tal.execution_id');
                }
                if ($this->db->field_exists('workflow_id', $automation_logs_table)) {
                    $this->db->select('tal.workflow_id');
                }
            }
        }
        
        $this->db->where($this->table . '.id', $id);
        $this->db->where($this->table . '.topicid', $topicid);
        return $this->db->get()->row_array();
    }

    public function get_active_topics()
    {
        $this->db->select([
            $this->table . '.id',
            $this->table . '.topicid',
            $this->table . '.topictitle',
            $this->table . '.action_type_code',
            $this->table . '.action_state_code',
            $this->table . '.dateupdated',
            db_prefix() . 'topic_action_types.name as action_type_name',
            db_prefix() . 'topic_action_states.name as action_state_name',
            db_prefix() . 'topic_action_states.color as state_color'
        ]);
        
        $this->db->from($this->table);
        $this->db->join(
            db_prefix() . 'topic_action_types',
            db_prefix() . 'topic_action_types.action_type_code = ' . $this->table . '.action_type_code',
            'left'
        );
        $this->db->join(
            db_prefix() . 'topic_action_states',
            db_prefix() . 'topic_action_states.action_state_code = ' . $this->table . '.action_state_code',
            'left'
        );
        
        $this->db->where([
            $this->table . '.status' => 1,
            $this->table . '.master_record' => 1
        ]);
        $this->db->where('target_id !=', self::BACKUP_TARGET_ID);
        
        // Get the query before executing
        $query = $this->db->get_compiled_select();
        
        // Log the query
        log_activity('Topics Query: ' . $query);
        
        // Execute and return results
        return $this->db->get()->result_array();
    }

    public function search_topics($q, $limit = 20)
    {
        // Get the latest record for each topicid
        $subquery = $this->db->select('t1.*')
            ->from($this->table . ' t1')
            ->join(
                '(SELECT topicid, MAX(dateupdated) as max_date 
                  FROM ' . $this->table . ' 
                  GROUP BY topicid) t2',
                't1.topicid = t2.topicid AND t1.dateupdated = t2.max_date'
            );

        if ($q) {
            $search_mode = $this->input->post('search_mode') ?? 'or';
            $this->db->group_start();
            
            if ($search_mode === 'or') {
                $this->db->like('t1.topicid', $q);
                $this->db->or_like('t1.topictitle', $q); 
            } else {
                $this->db->like('t1.topicid', $q);
                $this->db->like('t1.topictitle', $q);
            }
            
            $this->db->group_end();
        }

        $subquery->limit($limit);
        $subquery->order_by('t1.dateupdated', 'DESC');
        
        $query = $subquery->get();
        
        return $query->result_array();
    }

    public function get_topic_steps($topicid) {
        // Lấy ordered action types
        $this->load->model('Action_type_model');
        $action_types = $this->Action_type_model->get_ordered_action_types();
        
        // Lấy latest states cho mỗi action type
        $steps = [];
        foreach($action_types as $type) {
            $latest_state = $this->db->select([
                    'topics.action_state_code',
                    'topics.dateupdated',
                    'tas.name as state_name',
                    'tas.color as state_color',
                    'tas.valid_data as valid_data'
                ])
                ->from(db_prefix() . 'topics topics')
                ->join(db_prefix() . 'topic_action_states tas', 
                    'tas.action_state_code = topics.action_state_code AND 
                     tas.action_type_code = topics.action_type_code', 'left')
                ->where('topics.topicid', $topicid)
                ->where('topics.action_type_code', $type['action_type_code'])
                ->order_by('topics.dateupdated', 'DESC')
                ->limit(1)
                ->get()
                ->row_array();

            $steps[] = [
                'id' => $type['id'],
                'name' => $type['name'],
                'action_type_code' => $type['action_type_code'],
                'parent_id' => $type['parent_id'],
                'position' => $type['position'],
                'state_name' => $latest_state['state_name'] ?? null,
                'state_color' => $latest_state['state_color'] ?? null,
                'dateupdated' => $latest_state['dateupdated'] ?? null,
                'valid_data' => $latest_state['valid_data'] ?? null
            ];
        }
        
        return $steps;
    }

    /**
     * Count total topics
     */
    public function count_total_topics()
    {
        $this->db->select('COUNT(DISTINCT topicid) as total');
        $this->db->from($this->table);
        $result = $this->db->get()->row();
        return $result->total;
    }

    /**
     * Count topics by state
     */
    public function count_topics_by_state($state_code)
    {
        $this->db->where('status', 1);
        $this->db->where('action_state_code', $state_code);
        return $this->db->count_all_results($this->table);
    }

    /**
     * Get topics by state
     */
    public function get_topics_by_state($state_code)
    {
        $this->db->select([
            $this->table.'.id',
            $this->table.'.topicid', 
            $this->table.'.topictitle',
            $this->table.'.log',
            $this->table.'.action_type_code',
            $this->table.'.action_state_code',
            $this->table.'.target_id',
            $this->table.'.datecreated',
            $this->table.'.dateupdated',
            db_prefix().'topic_action_types.name as action_type_name',
            db_prefix().'topic_action_states.name as action_state_name',
            db_prefix().'topic_action_states.color as state_color'
        ]);

        $this->db->from($this->table);
        
        $this->db->join(
            db_prefix().'topic_action_types',
            db_prefix().'topic_action_types.action_type_code = '.$this->table.'.action_type_code',
            'left'
        );
        
        $this->db->join(
            db_prefix().'topic_action_states',
            db_prefix().'topic_action_states.action_state_code = '.$this->table.'.action_state_code',
            'left'
        );

        $this->db->where($this->table.'.action_state_code', $state_code);
        
        return $this->db->get()->result_array();
    }

    public function get_filtered_topics($filters, $start, $length, $search, $order, $columns)
    {
        $this->db->select([
            't.topicid',
            't.id',
            't.topictitle',
            'tat.name as action_type_name',
            'ast.name as action_state_name',
            'ast.color as state_color',
            't.dateupdated'
        ]);
        $this->db->from(db_prefix() . 'topics t');
        $this->db->join(db_prefix() . 'topic_action_types tat', 't.action_type_code = tat.action_type_code', 'left');
        $this->db->join(db_prefix() . 'topic_action_states ast', 't.action_state_code = ast.action_state_code', 'left');

        // Áp dụng bộ lọc nếu có
        if (!empty($filters['state'])) {
            $this->db->where('ast.action_state_code', $filters['state']);
        }

        if (!empty($filters['action_type'])) {
            $this->db->where('t.action_type_code', $filters['action_type']);
        }

        if (!empty($filters['is_active'])) {
            $this->db->where('t.status', $filters['is_active']);
        }

        // Xử lý tìm kiếm
        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('t.topicid', $search);
            $this->db->or_like('t.topictitle', $search);
            $this->db->group_end();
        }

        // Xử lý sắp xếp
        if (!empty($order)) {
            foreach ($order as $o) {
                $columnIdx = intval($o['column']);
                $dir = $o['dir'] === 'asc' ? 'ASC' : 'DESC';
                $columnName = $columns[$columnIdx]['data'];
                $this->db->order_by($columnName, $dir);
            }
        } else {
            $this->db->order_by('t.id', 'DESC');
        }

        // Tổng số bản ghi
        $recordsTotal = $this->db->count_all_results('', false);

        // Lấy dữ liệu với phân trang
        if ($length != -1) {
            $this->db->limit($length, $start);
        }

        $query = $this->db->get();
        $data = $query->result_array();

        // Tổng số bản ghi sau khi lọc
        $recordsFiltered = $recordsTotal;

        return [
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data
        ];
    }

    public function count_active_topics() {
        $this->db->from("(SELECT t.* 
            FROM " . $this->table . " t
            INNER JOIN (
                SELECT topicid, MAX(dateupdated) as max_date
                FROM " . $this->table . "
                GROUP BY topicid
            ) latest ON t.topicid = latest.topicid 
            AND t.dateupdated = latest.max_date) as topics");
        return $this->db->count_all_results();
    }

    public function bulk_action($action, $ids) {
        if (empty($ids)) {
            return false;
        }
        
        $status = ($action === 'activate') ? 1 : 0;
        
        $this->db->where_in('id', $ids);
        $this->db->update($this->table, [
            'status' => $status,
            'dateupdated' => date('Y-m-d H:i:s')
        ]);
        
        return $this->db->affected_rows() > 0;
    }

    public function get_topic_history_with_target($topicid)
    {
        $this->db->select([
            $this->table . '.*',
            'tt.target_type',
            db_prefix() . 'topic_action_types.name as action_type_name',
            db_prefix() . 'topic_action_states.name as action_state_name',
            db_prefix() . 'topic_action_states.color as state_color'
        ]);
        
        $this->db->from($this->table);
        $this->db->join(db_prefix() . 'topic_target tt', 'tt.id = ' . $this->table . '.target_id', 'left');
        $this->db->join(db_prefix() . 'topic_action_types', 
            db_prefix() . 'topic_action_types.action_type_code = ' . $this->table . '.action_type_code', 'left');
        $this->db->join(db_prefix() . 'topic_action_states',
            db_prefix() . 'topic_action_states.action_state_code = ' . $this->table . '.action_state_code', 'left');
        
        $this->db->where($this->table . '.topicid', $topicid);
        $this->db->order_by($this->table . '.dateupdated', 'DESC');
        
        return $this->db->get()->result();
    }

    public function add_topic_with_target($data)
    {
        // Start transaction
        $this->db->trans_start();
        
        // Add target first if exists
        $target_id = null;
        if (isset($data['target_type']) && isset($data['target_data'])) {
            $target = [
                'target_type' => $data['target_type'],
                'target_id' => $data['target_id'] ?? 0,
                'data' => $data['data']
            ];
            $this->load->model('Topic_target_model');
            $target_id = $this->Topic_target_model->add($target);
        }
        
        // Add topic
        $topic_data = [
            'topicid' => $data['topicid'],
            'topictitle' => $data['topictitle'],
            'action_type_code' => $data['action_type_code'],
            'action_state_code' => $data['action_state_code'],
            'target_id' => $target_id,
            'log' => $data['log']
        ];
        
        $this->db->insert($this->table, $topic_data);
        $topic_id = $this->db->insert_id();
        
        $this->db->trans_complete();
        
        return $this->db->trans_status() ? $topic_id : false;
    }

    public function get_latest_backup($topic_id)
    {
        $this->db->select([
            $this->table . '.*',
            'tt.target_type'
        ]);
        
        $this->db->from($this->table);
        $this->db->join(
            db_prefix() . 'topic_target tt',
            'tt.id = ' . $this->table . '.target_id',
            'left'
        );
        
        $this->db->where([
            $this->table . '.topicid' => $topic_id,
            'tt.target_type' => self::BACKUP_TARGET_TYPE
        ]);
        
        $this->db->order_by($this->table . '.dateupdated', 'DESC');
        $this->db->limit(1);
        
        return $this->db->get()->row();
    }

    public function get_topic_by_id($id)
    {
        $this->db->select('t.*, tt.target_type, t.data as data');
        $this->db->from(db_prefix() . 'topics t');
        $this->db->join(db_prefix() . 'topic_target tt', 'tt.id = t.target_id', 'left');
        $this->db->where('t.id', $id);
        return $this->db->get()->row();
    }

    public function get_action_types()
    {
        $this->db->select('*');
        $this->db->from(db_prefix() . 'topic_action_types');
        $this->db->order_by('position', 'asc');
        
        return $this->db->get()->result_array();
    }

    public function get_action_states()
    {
        $this->db->select('tas.*, tat.name as action_type_name');
        $this->db->from(db_prefix() . 'topic_action_states tas');
        $this->db->join(
            db_prefix() . 'topic_action_types tat', 
            'tat.action_type_code = tas.action_type_code', 
            'left'
        );
        $this->db->order_by('tas.position', 'asc');
        
        return $this->db->get()->result_array();
    }
}