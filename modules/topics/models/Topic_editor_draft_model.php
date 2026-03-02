<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Topic Editor Draft Model
 * Quản lý các bản nháp của bài viết trong Ultimate Editor
 */
class Topic_editor_draft_model extends App_Model
{
    protected $table;

    public function __construct()
    {
        parent::__construct();
        $this->table = db_prefix() . 'topic_editor_drafts';
    }

    /**
     * Lấy draft theo ID hoặc theo topic_id
     * @param int $id ID của draft hoặc topic_id
     * @param bool $is_topic_id Xác định $id có phải là topic_id hay không
     * @param array $where Điều kiện bổ sung
     * @return object|array Draft object hoặc danh sách draft
     */
    public function get($id = '', $is_topic_id = false, $where = [])
    {
        $this->db->select('*');
        
        if (is_numeric($id)) {
            if ($is_topic_id) {
                $this->db->where('topic_id', $id);
                if (!empty($where)) {
                    $this->db->where($where);
                }
                return $this->db->get($this->table)->result_array();
            } else {
                $this->db->where('id', $id);
                if (!empty($where)) {
                    $this->db->where($where);
                }
                return $this->db->get($this->table)->row();
            }
        }
        
        if (!empty($where)) {
            $this->db->where($where);
        }
        return $this->db->get($this->table)->result_array();
    }

    /**
     * Thêm draft mới
     * @param array $data Dữ liệu draft
     * @return int ID của draft mới
     */
    public function add($data)
    {
        // Đảm bảo các trường bắt buộc không null
        if (empty($data['draft_title'])) {
            $data['draft_title'] = 'Untitled Draft';
        }
        
        if (empty($data['draft_content'])) {
            $data['draft_content'] = '';
        }
        
        // Thêm thời gian tạo và cập nhật
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        // Xử lý các trường JSON
        if (isset($data['draft_sections']) && is_array($data['draft_sections'])) {
            $data['draft_sections'] = json_encode($data['draft_sections']);
        }
        
        if (isset($data['draft_metadata']) && is_array($data['draft_metadata'])) {
            $data['draft_metadata'] = json_encode($data['draft_metadata']);
        }
        
        // Thiết lập người tạo nếu chưa có
        if (!isset($data['created_by']) && is_logged_in()) {
            $data['created_by'] = get_staff_user_id();
        }
        
        // Thiết lập người chỉnh sửa cuối cùng
        if (is_logged_in()) {
            $data['last_edited_by'] = get_staff_user_id();
        }
        
        $this->db->insert($this->table, $data);
        $insert_id = $this->db->insert_id();
        
        if ($insert_id) {
            log_activity('New Topic Editor Draft Added [ID: ' . $insert_id . ']');
            
            // Cập nhật active_draft_id cho topic nếu đây là draft đầu tiên
            $this->update_active_draft_for_topic($data['topic_id'], $insert_id);
        }
        
        return $insert_id;
    }

    /**
     * Cập nhật draft
     * @param int $id ID của draft
     * @param array $data Dữ liệu cập nhật
     * @return bool Kết quả cập nhật
     */
    public function update($id, $data)
    {
        // Cập nhật thời gian
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        // Xử lý các trường JSON
        if (isset($data['draft_sections']) && is_array($data['draft_sections'])) {
            $data['draft_sections'] = json_encode($data['draft_sections']);
        }
        
        if (isset($data['draft_metadata']) && is_array($data['draft_metadata'])) {
            $data['draft_metadata'] = json_encode($data['draft_metadata']);
        }
        
        // Cập nhật người chỉnh sửa cuối cùng
        if (is_logged_in()) {
            $data['last_edited_by'] = get_staff_user_id();
        }
        
        // Tăng version nếu được yêu cầu
        if (isset($data['increase_version']) && $data['increase_version']) {
            $this->db->where('id', $id);
            $current_version = $this->db->get($this->table)->row()->version;
            $data['version'] = $current_version + 1;
            unset($data['increase_version']);
        }
        
        $this->db->where('id', $id);
        $this->db->update($this->table, $data);
        
        if ($this->db->affected_rows() > 0) {
            log_activity('Topic Editor Draft Updated [ID: ' . $id . ']');
            return true;
        }
        
        return false;
    }

    /**
     * Xóa draft
     * @param int $id ID của draft
     * @return bool Kết quả xóa
     */
    public function delete($id)
    {
        // Kiểm tra và lấy thông tin draft trước khi xóa
        $draft = $this->get($id);
        if (!$draft) {
            return false;
        }
        
        $this->db->where('id', $id);
        $this->db->delete($this->table);
        
        if ($this->db->affected_rows() > 0) {
            log_activity('Topic Editor Draft Deleted [ID: ' . $id . ']');
            
            // Kiểm tra nếu đây là active draft, cập nhật lại active draft cho topic
            $this->check_and_update_active_draft($draft->topic_id, $id);
            
            return true;
        }
        
        return false;
    }

    /**
     * Lấy draft mới nhất theo topic_id
     * @param int $topic_id ID của topic
     * @return object|null Draft mới nhất
     */
    public function get_latest_draft($topic_id)
    {
        $this->db->where('topic_id', $topic_id);
        $this->db->order_by('updated_at', 'DESC');
        return $this->db->get($this->table)->row();
    }

    /**
     * Lấy draft đang hoạt động của topic
     * @param int $topic_id ID của topic
     * @return object|null Draft đang hoạt động
     */
    public function get_active_draft($topic_id)
    {
        // Lấy active_draft_id từ bảng topics
        $this->db->select('active_draft_id');
        $this->db->where('id', $topic_id);
        $topic = $this->db->get(db_prefix() . 'topics')->row();
        
        if ($topic && $topic->active_draft_id) {
            // Lấy draft theo active_draft_id
            return $this->get($topic->active_draft_id);
        }
        
        // Nếu không có active draft, lấy draft mới nhất
        return $this->get_latest_draft($topic_id);
    }

    /**
     * Cập nhật draft đang hoạt động cho topic
     * @param int $topic_id ID của topic
     * @param int $draft_id ID của draft muốn đặt làm active
     * @return bool Kết quả cập nhật
     */
    public function set_active_draft($topic_id, $draft_id)
    {
        // Kiểm tra sự tồn tại của draft
        $draft = $this->get($draft_id);
        if (!$draft || $draft->topic_id != $topic_id) {
            return false;
        }
        
        // Cập nhật active_draft_id trong bảng topics
        $this->db->where('id', $topic_id);
        $this->db->update(db_prefix() . 'topics', ['active_draft_id' => $draft_id]);
        
        if ($this->db->affected_rows() > 0) {
            log_activity('Active Draft Updated for Topic [Topic ID: ' . $topic_id . ', Draft ID: ' . $draft_id . ']');
            return true;
        }
        
        return false;
    }

    /**
     * Kiểm tra và cập nhật active draft khi draft hiện tại bị xóa
     * @param int $topic_id ID của topic
     * @param int $deleted_draft_id ID của draft bị xóa
     */
    private function check_and_update_active_draft($topic_id, $deleted_draft_id)
    {
        // Lấy active_draft_id hiện tại
        $this->db->select('active_draft_id');
        $this->db->where('id', $topic_id);
        $topic = $this->db->get(db_prefix() . 'topics')->row();
        
        // Nếu draft bị xóa là active draft, cập nhật lại
        if ($topic && $topic->active_draft_id == $deleted_draft_id) {
            // Lấy draft mới nhất còn lại
            $latest_draft = $this->get_latest_draft($topic_id);
            
            if ($latest_draft) {
                $this->set_active_draft($topic_id, $latest_draft->id);
            } else {
                // Nếu không còn draft nào, đặt active_draft_id về null
                $this->db->where('id', $topic_id);
                $this->db->update(db_prefix() . 'topics', ['active_draft_id' => null]);
            }
        }
    }

    /**
     * Cập nhật active draft cho topic nếu chưa có
     * @param int $topic_id ID của topic
     * @param int $draft_id ID của draft mới tạo
     */
    private function update_active_draft_for_topic($topic_id, $draft_id)
    {
        // Kiểm tra xem topic đã có active draft chưa
        $this->db->select('active_draft_id');
        $this->db->where('id', $topic_id);
        $topic = $this->db->get(db_prefix() . 'topics')->row();
        
        // Nếu chưa có active draft, đặt draft mới làm active
        if ($topic && is_null($topic->active_draft_id)) {
            $this->set_active_draft($topic_id, $draft_id);
        }
    }

    /**
     * Tạo bản sao của draft hiện tại
     * @param int $draft_id ID của draft cần sao chép
     * @return int|bool ID của draft mới hoặc false nếu thất bại
     */
    public function duplicate_draft($draft_id)
    {
        // Lấy thông tin draft gốc
        $draft = $this->get($draft_id);
        if (!$draft) {
            return false;
        }
        
        // Tạo dữ liệu cho draft mới
        $new_draft_data = [
            'topic_id' => $draft->topic_id,
            'draft_title' => $draft->draft_title . ' (Copy)',
            'draft_content' => $draft->draft_content,
            'draft_sections' => $draft->draft_sections,
            'draft_metadata' => $draft->draft_metadata,
            'status' => 'draft',
            'version' => 1  // Bắt đầu với version 1
        ];
        
        // Thêm draft mới
        return $this->add($new_draft_data);
    }

    /**
     * Lấy lịch sử phiên bản của draft
     * @param int $topic_id ID của topic
     * @return array Danh sách các phiên bản draft
     */
    public function get_draft_history($topic_id)
    {
        $this->db->where('topic_id', $topic_id);
        $this->db->order_by('updated_at', 'DESC');
        return $this->db->get($this->table)->result_array();
    }

    /**
     * Đếm số lượng draft theo topic_id
     * @param int $topic_id ID của topic
     * @return int Số lượng draft
     */
    public function count_drafts($topic_id)
    {
        $this->db->where('topic_id', $topic_id);
        return $this->db->count_all_results($this->table);
    }

    /**
     * Lưu nhanh draft, tạo mới hoặc cập nhật nếu đã tồn tại
     * @param array $data Dữ liệu draft
     * @return int|bool ID của draft hoặc false nếu thất bại
     */
    public function quick_save($data)
    {
        // Kiểm tra xem có draft_id không
        if (isset($data['draft_id']) && !empty($data['draft_id'])) {
            // Cập nhật draft hiện có
            $draft_id = $data['draft_id'];
            unset($data['draft_id']);
            
            $success = $this->update($draft_id, $data);
            return $success ? $draft_id : false;
        } else {
            // Tạo draft mới
            unset($data['draft_id']);
            return $this->add($data);
        }
    }

    /**
     * Chuyển đổi draft thành bài viết cuối cùng
     * @param int $draft_id ID của draft
     * @return bool Kết quả chuyển đổi
     */
    public function convert_to_final($draft_id)
    {
        // Lấy thông tin draft
        $draft = $this->get($draft_id);
        if (!$draft) {
            return false;
        }
        
        // Cập nhật trạng thái draft thành final
        $update_data = [
            'status' => 'final',
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        if (is_logged_in()) {
            $update_data['last_edited_by'] = get_staff_user_id();
        }
        
        $this->db->where('id', $draft_id);
        $this->db->update($this->table, $update_data);
        
        if ($this->db->affected_rows() > 0) {
            log_activity('Draft Converted to Final [ID: ' . $draft_id . ']');
            
            // Đặt draft này làm active
            $this->set_active_draft($draft->topic_id, $draft_id);
            
            return true;
        }
        
        return false;
    }
} 