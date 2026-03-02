<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Topic_controller_model extends App_Model
{
    protected $table;

    public function __construct()
    {
        parent::__construct();
        $this->table = db_prefix() . 'topic_controllers';
    }

    public function get($id = '')
    {
        $this->db->select('*');
        
        if (is_numeric($id)) {
            $this->db->where('id', $id);
            return $this->db->get($this->table)->row();
        }
        
        return $this->db->get($this->table)->result_array();
    }

    public function add($data)
    {
        // Handle blog_id field specially since it's been moved to categories_state
        if (isset($data['blog_id'])) {
            // Create categories_state with blog_id
            $categories_state = ['blog_id' => $data['blog_id']];
            
            if (isset($data['categories_state']) && !empty($data['categories_state'])) {
                $existing_state = json_decode($data['categories_state'], true) ?: [];
                $categories_state = array_merge($existing_state, $categories_state);
            }
            
            $data['categories_state'] = json_encode($categories_state);
            
            // Remove blog_id from the data array since the column no longer exists
            unset($data['blog_id']);
        }
        
        $data = $this->handle_empty_fields($data);
        $this->db->insert($this->table, $data);
        $insert_id = $this->db->insert_id();
        
        if ($insert_id) {
            log_activity('New Controller Added [ID: ' . $insert_id . ']');
        }
        
        return $insert_id;
    }

    public function update($id, $data)
    {
        // Handle blog_id field specially since it's been moved to categories_state
        if (isset($data['blog_id'])) {
            // Save blog_id in categories_state for backward compatibility
            $controller = $this->get($id);
            $categories_state = [];
            
            if ($controller && !empty($controller->categories_state)) {
                $categories_state = json_decode($controller->categories_state, true) ?: [];
            }
            
            $categories_state['blog_id'] = $data['blog_id'];
            $data['categories_state'] = json_encode($categories_state);
            
            // Remove blog_id from the data array since the column no longer exists
            unset($data['blog_id']);
        }
        
        // Debug log before handling empty fields
        log_activity('Controller Update Model Before Processing [ID: ' . $id . '] - ' . json_encode($data));
        
        $data = $this->handle_empty_fields($data);
        
        // Debug log after handling empty fields
        log_activity('Controller Update Model After Processing [ID: ' . $id . '] - ' . json_encode($data));
        
        $this->db->where('id', $id);
        $this->db->update($this->table, $data);
        
        $affected_rows = $this->db->affected_rows();
        $error = $this->db->error();
        
        // Log error if any
        if (!empty($error['message'])) {
            log_activity('Controller Update DB Error [ID: ' . $id . '] - ' . $error['message']);
        }
        
        if ($affected_rows > 0) {
            log_activity('Controller Updated [ID: ' . $id . ']');
            return true;
        }
        
        return false;
    }

    public function delete($id)
    {
        $this->db->where('id', $id);
        $this->db->delete($this->table);
        
        if ($this->db->affected_rows() > 0) {
            log_activity('Controller Deleted [ID: ' . $id . ']');
            return true;
        }
        
        return false;
    }

    public function get_topic_masters($controller_id)
    {
        $this->db->where('controller_id', $controller_id);
        return $this->db->get(db_prefix() . 'topic_master')->result_array();
    }

    private function handle_empty_fields($data)
    {
        // Define all possible fields from the database
        $fields = [
            'status',
            'site',
            'platform',
            'logo_url',
            'slogan',
            'writing_style',
            'emails',
            'project_id',
            'seo_task_sheet_id',
            'raw_data',
            'action_1',
            'action_2',
            'page_mapping',
            'login_config',
            'last_login',
            'login_status',
            'selected_categories',
            'categories_state',
            'expanded_categories',
            'tags_state'
        ];

        // Set NULL for empty fields, but preserve login_config as it's crucial
        foreach ($fields as $field) {
            if (isset($data[$field]) && $data[$field] === '' && $field !== 'login_config') {
                $data[$field] = null;
            }
        }

        return $data;
    }

    public function change_status($id, $status)
    {
        $this->db->where('id', $id);
        $this->db->update($this->table, ['status' => $status]);
        
        if ($this->db->affected_rows() > 0) {
            log_activity('Controller Status Changed [ID: ' . $id . ', Status: ' . $status . ']');
            return true;
        }
        
        return false;
    }

    public function get_active_controllers()
    {
        $this->db->where('status', 1);
        return $this->db->get($this->table)->result_array();
    }

    /**
     * Get controller info for a specific topic
     * @param mixed $topic_id Topic ID from topics table (int) or topicid from topic_master (string)
     * @return object|null Controller object or null if not found
     */
    public function get_controller_by_topic($topic_id)
    {
        // If topic_id is numeric, search by topics.id
        if (is_numeric($topic_id)) {
            $this->db->select([
                'c.*',
                't.topicid',
                'tm.id as master_id'
            ]);
            $this->db->from(db_prefix() . 'topics t')
                ->join(db_prefix() . 'topic_master tm', 'tm.topicid = t.topicid')
                ->join(db_prefix() . 'topic_controller tc', 'tc.topic_id = tm.id')
                ->join($this->table . ' c', 'tc.controller_id = c.id')
                ->where([
                    't.id' => $topic_id,
                    'c.status' => 1
                ]);
        } 
        // If topic_id is string, search directly in topic_master
        else {
            $this->db->select('c.*')
                ->from($this->table . ' c')
                ->join(db_prefix() . 'topic_controller tc', 'tc.controller_id = c.id')
                ->join(db_prefix() . 'topic_master tm', 'tm.id = tc.topic_id')
                ->where([
                    'tm.topicid' => $topic_id,
                    'c.status' => 1
                ]);
        }

        return $this->db->get()->row();
    }

    /**
     * Check if topic belongs to any controller
     * @param int $topic_id Topic ID from topics table
     * @return bool True if topic has controller, false otherwise
     */
    public function topic_has_controller($topic_id)
    {
        // First get topic master id
        $this->db->select('tm.id as master_id');
        $this->db->from(db_prefix() . 'topics t');
        $this->db->join(db_prefix() . 'topic_master tm', 'tm.topicid = t.topicid');
        $this->db->where('t.id', $topic_id);
        $topic = $this->db->get()->row();

        if (!$topic) {
            return false;
        }

        // Then check in topic_controller table
        $this->db->where('topic_id', $topic->master_id);
        return $this->db->count_all_results(db_prefix() . 'topic_controller') > 0;
    }

    /**
     * Add topics to controller
     * @param int $controller_id Controller ID
     * @param array $topic_ids Topic IDs from topics table
     * @return bool Success status
     */
    public function add_topics($controller_id, $topic_ids)
    {
        // Start transaction
        $this->db->trans_start();
        
        foreach ($topic_ids as $topic_id) {
            // Get topic master id
            $this->db->select('tm.id as master_id');
            $this->db->from(db_prefix() . 'topics t');
            $this->db->join(db_prefix() . 'topic_master tm', 'tm.topicid = t.topicid');
            $this->db->where('t.id', $topic_id);
            $topic = $this->db->get()->row();

            if ($topic) {
                // Check if association already exists
                $exists = $this->db->where([
                    'controller_id' => $controller_id,
                    'topic_id' => $topic->master_id
                ])->count_all_results(db_prefix() . 'topic_controller') > 0;
                
                if (!$exists) {
                    $this->db->insert(db_prefix() . 'topic_controller', [
                        'controller_id' => $controller_id,
                        'topic_id' => $topic->master_id,
                        'datecreated' => date('Y-m-d H:i:s'),
                        'staff_id' => get_staff_user_id()
                    ]);
                }
            }
        }
        
        $this->db->trans_complete();
        
        return $this->db->trans_status() !== FALSE;
    }

    public function get_topic_ids_by_controller($controller_id)
    {
        $this->db->select('topic_id');
        $this->db->where('controller_id', $controller_id);
        $result = $this->db->get(db_prefix() . 'topic_controller')->result_array();
        return array_column($result, 'topic_id');
    }

    public function remove_topics($controller_id, $topic_ids)
    {
        $this->db->where('controller_id', $controller_id);
        $this->db->where_in('topic_id', $topic_ids);
        $this->db->delete(db_prefix() . 'topic_controller');
        
        if ($this->db->affected_rows() > 0) {
            log_activity('Topics Removed from Controller [Controller ID: ' . $controller_id . ', Topic IDs: ' . implode(',', $topic_ids) . ']');
            return true;
        }
        return false;
    }

    public function get_topics_count($controller_id)
    {
        $this->db->where('controller_id', $controller_id);
        return $this->db->count_all_results(db_prefix() . 'topic_controller');
    }

    public function get_related_topics($controller_id)
    {
        $this->db->select([
            'tm.id',
            'tm.topicid',
            'tm.topictitle', 
            'tm.status',
            'tc.datecreated as assigned_date'
        ]);
        $this->db->from(db_prefix() . 'topic_master tm');
        $this->db->join(db_prefix() . 'topic_controller tc', 'tc.topic_id = tm.id');
        $this->db->where('tc.controller_id', $controller_id);
        
        // Handle DataTables ordering
        $order_column = $this->input->post('order')[0]['column'] ?? 0;
        $order_dir = $this->input->post('order')[0]['dir'] ?? 'asc';
        $columns = ['tm.id', 'tm.topicid', 'tm.topictitle', 'tm.status', 'tc.datecreated'];
        
        if (isset($columns[$order_column])) {
            $this->db->order_by($columns[$order_column], $order_dir);
        }
        
        // Handle DataTables search
        $search = $this->input->post('search')['value'] ?? '';
        if ($search) {
            $this->db->group_start();
            $this->db->like('tm.topicid', $search);
            $this->db->or_like('tm.topictitle', $search);
            $this->db->group_end();
        }
        
        // Get total records count
        $total_records = $this->db->count_all_results('', false);
        
        // Handle pagination
        $start = $this->input->post('start') ?? 0;
        $length = $this->input->post('length') ?? 10;
        if ($length > 0) {
            $this->db->limit($length, $start);
        }
        
        $result = $this->db->get()->result_array();
        
        return [
            'data' => $result,
            'recordsTotal' => $total_records,
            'recordsFiltered' => $total_records,
            'draw' => intval($this->input->post('draw'))
        ];
    }

    public function search_controllers($search = '')
    {
        if ($search) {
            $this->db->group_start();
            $this->db->like('site', $search);
            $this->db->or_like('platform', $search);
            $this->db->or_like('blog_id', $search);
            $this->db->group_end();
        }

        $this->db->order_by('site', 'asc');
        return $this->db->get($this->table)->result_array();
    }

    /**
     * Get available platforms
     * 
     * @return array List of platforms
     */
    public function get_platforms()
    {
        $platforms_json = get_option('topic_controller_platforms');
        $platforms = json_decode($platforms_json, true);
        
        if (!is_array($platforms)) {
            return [];
        }
        
        return $platforms;
    }
    
    /**
     * Get platform information
     * 
     * @param string $platform Platform name
     * @return array Platform information
     */
    public function get_platform_info($platform)
    {
        $platforms = $this->get_platforms();
        
        if (!isset($platforms[$platform])) {
            return null;
        }
        
        return $platforms[$platform];
    }
    
    /**
     * Set login configuration for a controller
     * 
     * @param int $id Controller ID
     * @param array $login_config Login configuration
     * @return bool Success status
     */
    public function set_login_config($id, $login_config)
    {
        // Validate login config
        if (!is_array($login_config)) {
            return false;
        }
        
        // Convert to JSON
        $login_config_json = json_encode($login_config);
        
        // Update controller
        $this->db->where('id', $id);
        $this->db->update($this->table, [
            'login_config' => $login_config_json,
            'last_login' => null,
            'login_status' => 0
        ]);
        
        if ($this->db->affected_rows() > 0) {
            log_activity('Controller Login Config Updated [ID: ' . $id . ']');
            return true;
        }
        
        return false;
    }
    
    /**
     * Get login configuration for a controller
     * 
     * @param int $id Controller ID
     * @return array Login configuration
     */
    public function get_login_config($id)
    {
        $this->db->select('login_config');
        $this->db->where('id', $id);
        $result = $this->db->get($this->table)->row();
        
        if (!$result || !$result->login_config) {
            return [];
        }
        
        $login_config = json_decode($result->login_config, true);
        
        if (!is_array($login_config)) {
            return [];
        }
        
        return $login_config;
    }
    
    /**
     * Update login status for a controller
     * 
     * @param int $id Controller ID
     * @param int $status Login status (0=Not logged in, 1=Logged in, 2=Error)
     * @return bool Success status
     */
    public function update_login_status($id, $status)
    {
        $this->db->where('id', $id);
        $this->db->update($this->table, [
            'login_status' => $status,
            'last_login' => date('Y-m-d H:i:s')
        ]);
        
        if ($this->db->affected_rows() > 0) {
            log_activity('Controller Login Status Updated [ID: ' . $id . ', Status: ' . $status . ']');
            return true;
        }
        
        return false;
    }
    
    /**
     * Set writing style for a controller
     * 
     * @param int $id Controller ID
     * @param array $writing_style Writing style configuration
     * @return bool Success status
     */
    public function set_writing_style($id, $writing_style)
    {
        // Validate writing style
        if (!is_array($writing_style)) {
            return false;
        }
        
        // Ensure required fields
        if (!isset($writing_style['style'])) {
            $writing_style['style'] = '';
        }
        
        if (!isset($writing_style['tone'])) {
            $writing_style['tone'] = '';
        }
        
        if (!isset($writing_style['language'])) {
            $writing_style['language'] = 'vietnamese';
        }
        
        if (!isset($writing_style['criteria']) || !is_array($writing_style['criteria'])) {
            $writing_style['criteria'] = [];
        }
        
        // Convert to JSON
        $writing_style_json = json_encode($writing_style);
        
        // Update controller
        $this->db->where('id', $id);
        $this->db->update($this->table, [
            'writing_style' => $writing_style_json
        ]);
        
        if ($this->db->affected_rows() > 0) {
            log_activity('Controller Writing Style Updated [ID: ' . $id . ']');
            return true;
        }
        
        return false;
    }
    
    /**
     * Get writing style for a controller
     * 
     * @param int $id Controller ID
     * @return array Writing style configuration
     */
    public function get_writing_style($id)
    {
        $this->db->select('writing_style');
        $this->db->where('id', $id);
        $result = $this->db->get($this->table)->row();
        
        if (!$result || !$result->writing_style) {
            return [
                'style' => '',
                'tone' => '',
                'language' => 'vietnamese',
                'criteria' => []
            ];
        }
        
        $writing_style = json_decode($result->writing_style, true);
        
        if (!is_array($writing_style)) {
            return [
                'style' => $result->writing_style, // For backward compatibility
                'tone' => '',
                'language' => 'vietnamese',
                'criteria' => []
            ];
        }
        
        // Ensure all fields exist
        if (!isset($writing_style['style'])) {
            $writing_style['style'] = '';
        }
        
        if (!isset($writing_style['tone'])) {
            $writing_style['tone'] = '';
        }
        
        if (!isset($writing_style['language'])) {
            $writing_style['language'] = 'vietnamese';
        }
        
        if (!isset($writing_style['criteria']) || !is_array($writing_style['criteria'])) {
            $writing_style['criteria'] = [];
        }
        
        return $writing_style;
    }
    
    /**
     * Get controllers by platform
     * 
     * @param string $platform Platform name
     * @return array List of controllers
     */
    public function get_by_platform($platform)
    {
        $this->db->where('platform', $platform);
        $this->db->where('status', 1);
        return $this->db->get($this->table)->result_array();
    }
    
    /**
     * Get controllers with active login
     * 
     * @return array List of controllers
     */
    public function get_with_active_login()
    {
        $this->db->where('login_status', 1);
        $this->db->where('status', 1);
        return $this->db->get($this->table)->result_array();
    }
    
    /**
     * Get categories for a controller
     * @param int $controller_id Controller ID
     * @return array List of categories
     */
    public function get_categories($controller_id)
    {
        // Lấy danh sách categories
        $this->db->where('controller_id', $controller_id);
        $this->db->order_by('name', 'asc');
        $categories = $this->db->get(db_prefix() . 'topic_controller_categories')->result_array();
        
        // Đếm số lượng bài viết trong database cho mỗi category
        foreach ($categories as &$category) {
            // API count đã được lưu trong trường 'count' khi đồng bộ từ WordPress
            // Giữ lại giá trị này, đổi tên thành 'api_count'
            $category['api_count'] = $category['count'] ?? 0;
            
            // Đếm số lượng bài viết trong database
            $this->db->select('COUNT(DISTINCT b.id) as db_count');
            $this->db->from(db_prefix() . 'topic_controller_blogs b');
            $this->db->join(
                db_prefix() . 'topic_controller_blog_relationships rel',
                'b.controller_id = rel.controller_id AND b.blog_id = rel.blog_id',
                'inner'
            );
            $this->db->where('b.controller_id', $controller_id);
            $this->db->where('rel.term_id', $category['category_id']);
            
            $result = $this->db->get()->row();
            $category['db_count'] = $result ? (int)$result->db_count : 0;
        }
        
        return $categories;
    }
    
    /**
     * Save category to database
     * 
     * @param int $controller_id Controller ID
     * @param array $category Category data
     * @return int|bool Category ID or false on failure
     */
    public function save_category($controller_id, $category)
    {
        // Validate input
        if (!is_numeric($controller_id) || !is_array($category)) {
            log_message('error', 'Invalid input for save_category: ' . json_encode(['controller_id' => $controller_id, 'category' => $category]));
            return false;
        }
        
        // Log incoming category data
        log_message('debug', 'Saving category for controller ' . $controller_id . ': ' . json_encode($category));
        
        // Check if category already exists
        $this->db->where('controller_id', $controller_id);
        $this->db->where('category_id', $category['category_id']);
        $existing = $this->db->get(db_prefix() . 'topic_controller_categories')->row();
        
        // Log if category exists
        if ($existing) {
            log_message('debug', 'Found existing category with ID: ' . $existing->id);
        }
        
        // Prepare data
        $data = [
            'controller_id' => $controller_id,
            'category_id' => $category['category_id'],
            'parent_id' => $category['parent_id'] ?? null,
            'name' => $category['name'],
            'slug' => $category['slug'] ?? null,
            'description' => $category['description'] ?? null,
            'count' => $category['count'] ?? 0,
            'url' => $category['url'] ?? null,
            'image_url' => $category['image_url'] ?? null,
            'raw_data' => isset($category['raw_data']) ? (is_array($category['raw_data']) ? json_encode($category['raw_data']) : $category['raw_data']) : null,
            'last_sync' => date('Y-m-d H:i:s')
        ];
        
        // Log prepared data
        log_message('debug', 'Prepared category data: ' . json_encode($data));
        
        if ($existing) {
            // Update existing category
            $this->db->where('id', $existing->id);
            $this->db->update(db_prefix() . 'topic_controller_categories', $data);
            
            if ($this->db->affected_rows() > 0) {
                log_message('info', 'Category Updated [ID: ' . $existing->id . ', Controller ID: ' . $controller_id . ', Category ID: ' . $category['category_id'] . ']');
            } else {
                log_message('error', 'Failed to update category [ID: ' . $existing->id . ']. Error: ' . $this->db->error()['message']);
            }
            
            return $existing->id;
        } else {
            // Insert new category
            $data['datecreated'] = date('Y-m-d H:i:s');
            $this->db->insert(db_prefix() . 'topic_controller_categories', $data);
            $insert_id = $this->db->insert_id();
            
            if ($insert_id) {
                log_message('info', 'Category Added [ID: ' . $insert_id . ', Controller ID: ' . $controller_id . ', Category ID: ' . $category['category_id'] . ']');
            } else {
                log_message('error', 'Failed to insert category. Error: ' . $this->db->error()['message']);
            }
            
            return $insert_id;
        }
    }
    
    /**
     * Save controller state
     * @param int $id Controller ID
     * @param array $state State data
     * @return bool Success
     */
    public function save_controller_state($id, $state)
    {
        // Validate input
        if (!is_numeric($id) || !is_array($state)) {
            log_activity('Invalid input for save_controller_state: ' . json_encode(['id' => $id, 'state' => $state]));
            return false;
        }
        
        // Get existing controller
        $controller = $this->get($id);
        if (!$controller) {
            log_activity('Controller not found for save_controller_state: ' . $id);
            return false;
        }
        
        // Prepare state data
        $update_data = [];
        
        // Handle expanded categories
        if (isset($state['expanded_categories'])) {
            if (is_array($state['expanded_categories'])) {
                $update_data['expanded_categories'] = json_encode($state['expanded_categories']);
            } else {
                $update_data['expanded_categories'] = $state['expanded_categories'];
            }
        }
        
        // Handle categories state
        if (isset($state['categories_state'])) {
            $update_data['categories_state'] = $state['categories_state'];
        }
        
        // Handle tags state
        if (isset($state['tags_state'])) {
            $update_data['tags_state'] = $state['tags_state'];
        }
        
        // If no valid state data provided, return false
        if (empty($update_data)) {
            log_activity('No valid state data provided for save_controller_state: ' . $id);
            return false;
        }
        
        // Update the controller
        $this->db->where('id', $id);
        $this->db->update($this->table, $update_data);
        
        if ($this->db->affected_rows() > 0) {
            log_activity('Controller State Updated [ID: ' . $id . ']');
            return true;
        }
        
        return false;
    }

    /**
     * Get blogs for a controller
     * @param int $controller_id Controller ID
     * @param int|null $category_id Category ID
     * @param int $limit Limit results
     * @param int $offset Offset for pagination
     * @return array Blogs
     */
    public function get_blogs($controller_id, $category_id = null, $limit = null, $offset = null)
    {
        // Get blogs from database
        if ($category_id) {
            $this->db->select('b.*, rel.term_id as category_id');
            $this->db->from(db_prefix() . 'topic_controller_blogs b');
            $this->db->join(
                db_prefix() . 'topic_controller_blog_relationships rel', 
                'b.controller_id = rel.controller_id AND b.blog_id = rel.blog_id',
                'inner'
            );
            $this->db->where('b.controller_id', $controller_id);
            $this->db->where('rel.type', 'category');
            $this->db->where('rel.term_id', $category_id);
            $this->db->order_by('b.date_published', 'desc');
            
            // Apply pagination if provided
            if ($limit !== null) {
                $this->db->limit($limit, $offset);
            }
            
            $blogs = $this->db->get()->result_array();
        } else {
            $this->db->where('controller_id', $controller_id);
            $this->db->order_by('date_published', 'desc');
            
            // Apply pagination if provided
            if ($limit !== null) {
                $this->db->limit($limit, $offset);
            }
            
            $blogs = $this->db->get(db_prefix() . 'topic_controller_blogs')->result_array();
        }
        
        // Enrich blogs with categories and tags
        foreach ($blogs as &$blog) {
            // Get categories for this blog
            $this->db->select('c.*');
            $this->db->from(db_prefix() . 'topic_controller_categories c');
            $this->db->join(
                db_prefix() . 'topic_controller_blog_relationships rel', 
                'c.controller_id = rel.controller_id AND c.category_id = rel.term_id',
                'inner'
            );
            $this->db->where('rel.controller_id', $controller_id);
            $this->db->where('rel.blog_id', $blog['blog_id']);
            $this->db->where('rel.type', 'category');
            
            $blog['categories'] = $this->db->get()->result_array();
            
            // Get tags for this blog
            $this->db->select('t.*');
            $this->db->from(db_prefix() . 'topic_controller_tags t');
            $this->db->join(
                db_prefix() . 'topic_controller_blog_relationships rel', 
                't.controller_id = rel.controller_id AND t.tag_id = rel.term_id',
                'inner'
            );
            $this->db->where('rel.controller_id', $controller_id);
            $this->db->where('rel.blog_id', $blog['blog_id']);
            $this->db->where('rel.type', 'tag');
            
            $blog['tags'] = $this->db->get()->result_array();
        }
        
        return $blogs;
    }
    
    /**
     * Count total blogs for a controller
     * @param int $controller_id Controller ID
     * @param int|null $category_id Category ID
     * @return int Total number of blogs
     */
    public function count_blogs($controller_id, $category_id = null)
    {
        // Count blogs from database
        if ($category_id) {
            $this->db->select('COUNT(DISTINCT b.blog_id) as count');
            $this->db->from(db_prefix() . 'topic_controller_blogs b');
            $this->db->join(
                db_prefix() . 'topic_controller_blog_relationships rel', 
                'b.controller_id = rel.controller_id AND b.blog_id = rel.blog_id',
                'inner'
            );
            $this->db->where('b.controller_id', $controller_id);
            $this->db->where('rel.type', 'category');
            $this->db->where('rel.term_id', $category_id);
            
            $result = $this->db->get()->row();
            return $result ? (int)$result->count : 0;
        } else {
            $this->db->where('controller_id', $controller_id);
            return $this->db->count_all_results(db_prefix() . 'topic_controller_blogs');
        }
    }
    
    /**
     * Save blog to database
     * @param int $controller_id Controller ID
     * @param array $blog Blog data
     * @return int|bool Blog ID or false on failure
     */
    public function save_blog($controller_id, $blog)
    {
        // Validate input
        if (!is_numeric($controller_id) || !is_array($blog)) {
            log_message('error', 'Invalid input for save_blog: ' . json_encode(['controller_id' => $controller_id, 'blog' => $blog]));
            return false;
        }
        
        // Log incoming blog data
        log_message('debug', 'Saving blog for controller ' . $controller_id . ': ' . json_encode($blog));
        
        // Check if blog already exists
        $this->db->where('controller_id', $controller_id);
        $this->db->where('blog_id', $blog['blog_id']);
        $existing = $this->db->get(db_prefix() . 'topic_controller_blogs')->row();
        
        // Log if blog exists
        if ($existing) {
            log_message('debug', 'Found existing blog with ID: ' . $existing->id);
        }
        
        // Prepare data
        $data = [
            'controller_id' => $controller_id,
            'blog_id' => $blog['blog_id'],
            'title' => $blog['title'],
            'slug' => $blog['slug'] ?? null,
            'excerpt' => $blog['excerpt'] ?? null,
            'status' => $blog['status'] ?? 'publish',
            'author' => $blog['author'] ?? null,
            'featured_image' => $blog['featured_image'] ?? null,
            'url' => $blog['url'] ?? null,
            'date_published' => $blog['date_published'] ?? null,
            'date_modified' => $blog['date_modified'] ?? null,
            'comment_count' => $blog['comment_count'] ?? 0,
            'view_count' => $blog['view_count'] ?? 0,
            'raw_data' => isset($blog['raw_data']) ? (is_array($blog['raw_data']) ? json_encode($blog['raw_data']) : $blog['raw_data']) : null,
            'last_sync' => date('Y-m-d H:i:s')
        ];
        
        // Log prepared data
        log_message('debug', 'Prepared blog data: ' . json_encode($data));
        
        if ($existing) {
            // Update existing blog
            $this->db->where('id', $existing->id);
            $this->db->update(db_prefix() . 'topic_controller_blogs', $data);
            
            if ($this->db->affected_rows() > 0) {
                log_message('info', 'Blog Updated [ID: ' . $existing->id . ', Controller ID: ' . $controller_id . ', Blog ID: ' . $blog['blog_id'] . ']');
            } else {
                log_message('error', 'Failed to update blog [ID: ' . $existing->id . ']. Error: ' . $this->db->error()['message']);
            }
            
            return $existing->id;
        } else {
            // Insert new blog
            $this->db->insert(db_prefix() . 'topic_controller_blogs', $data);
            $insert_id = $this->db->insert_id();
            
            if ($insert_id) {
                log_message('info', 'Blog Created [ID: ' . $insert_id . ', Controller ID: ' . $controller_id . ', Blog ID: ' . $blog['blog_id'] . ']');
            } else {
                log_message('error', 'Failed to create blog. Error: ' . $this->db->error()['message']);
            }
            
            return $insert_id;
        }
    }
    
    /**
     * Save blog relationship (category or tag)
     * @param int $controller_id Controller ID
     * @param string $blog_id Blog ID
     * @param string $type Relationship type (category or tag)
     * @param string $term_id Term ID (category_id or tag_id)
     * @return bool Success
     */
    public function save_blog_relationship($controller_id, $blog_id, $type, $term_id)
    {
        // Validate input
        if (!is_numeric($controller_id) || empty($blog_id) || empty($type) || empty($term_id)) {
            log_message('error', 'Invalid input for save_blog_relationship: ' . json_encode([
                'controller_id' => $controller_id,
                'blog_id' => $blog_id,
                'type' => $type,
                'term_id' => $term_id
            ]));
            return false;
        }
        
        // Check if relationship already exists
        $this->db->where('controller_id', $controller_id);
        $this->db->where('blog_id', $blog_id);
        $this->db->where('type', $type);
        $this->db->where('term_id', $term_id);
        $existing = $this->db->get(db_prefix() . 'topic_controller_blog_relationships')->row();
        
        if ($existing) {
            // Relationship already exists
            return true;
        }
        
        // Insert new relationship
        $data = [
            'controller_id' => $controller_id,
            'blog_id' => $blog_id,
            'type' => $type,
            'term_id' => $term_id,
            'datecreated' => date('Y-m-d H:i:s')
        ];
        
        $this->db->insert(db_prefix() . 'topic_controller_blog_relationships', $data);
        
        return $this->db->affected_rows() > 0;
    }
    
    /**
     * Update category sync time
     * @param int $controller_id Controller ID
     * @param string $category_id Category ID
     * @return bool Success
     */
    public function update_category_sync_time($controller_id, $category_id)
    {
        $this->db->where('controller_id', $controller_id);
        $this->db->where('category_id', $category_id);
        $this->db->update(db_prefix() . 'topic_controller_categories', [
            'last_sync' => date('Y-m-d H:i:s')
        ]);
        
        return $this->db->affected_rows() > 0;
    }

    /**
     * Get tags for a controller
     * @param int $controller_id Controller ID
     * @return array Tags
     */
    public function get_tags($controller_id)
    {
        // Get tags from database
        $this->db->select('*');
        $this->db->from(db_prefix() . 'topic_controller_tags');
        $this->db->where('controller_id', $controller_id);
        $this->db->order_by('name', 'asc');
        
        return $this->db->get()->result_array();
    }

    /**
     * Save tags for a controller
     * @param int $controller_id Controller ID
     * @param array $tags Tags data
     * @param string $session_id Session ID (optional)
     * @return array Array of inserted and updated counts
     */
    public function save_tags($controller_id, $tags, $session_id = null)
    {
        if (empty($tags)) {
            return ['inserted' => 0, 'updated' => 0];
        }
        
        $this->db->trans_start();
        
        $inserted_count = 0;
        $updated_count = 0;
        
        foreach ($tags as $tag) {
            // Standardize tag data
            $data = [
                'controller_id' => $controller_id,
                'tag_id' => $tag['tag_id'] ?? $tag['id'],
                'name' => $tag['name'],
                'slug' => $tag['slug'] ?? null,
                'description' => $tag['description'] ?? null,
                'count' => $tag['count'] ?? 0,
                'url' => $tag['url'] ?? null,
                'raw_data' => json_encode($tag),
                'last_sync' => date('Y-m-d H:i:s'),
                'dateupdated' => date('Y-m-d H:i:s')
            ];
            
            // Thêm session_id nếu được cung cấp
            if (!empty($session_id)) {
                $data['sync_session_id'] = $session_id;
            }
            
            // Check if tag exists
            $this->db->where('controller_id', $controller_id);
            $this->db->where('tag_id', $data['tag_id']);
            $existing_tag = $this->db->get(db_prefix() . 'topic_controller_tags')->row();
            
            if ($existing_tag) {
                // Update existing tag
                $this->db->where('id', $existing_tag->id);
                $this->db->update(db_prefix() . 'topic_controller_tags', $data);
                
                if ($this->db->affected_rows() > 0) {
                    $updated_count++;
                }
            } else {
                // Add datecreated for new tags
                $data['datecreated'] = date('Y-m-d H:i:s');
                
                // Insert new tag
                $this->db->insert(db_prefix() . 'topic_controller_tags', $data);
                
                if ($this->db->affected_rows() > 0) {
                    $inserted_count++;
                }
            }
        }
        
        // Cập nhật processed_count trong bảng sync_logs nếu có session_id
        if (!empty($session_id) && ($inserted_count > 0 || $updated_count > 0)) {
            $this->load->model('Topic_sync_log_model');
            $this->Topic_sync_log_model->increment_processed_count($session_id, $inserted_count + $updated_count);
        }
        
        log_message('info', 'Updated ' . $updated_count . ' tags and inserted ' . $inserted_count . ' new tags for controller #' . $controller_id);
        
        // Lấy các tag_id từ API hiện tại
        $platform_tag_ids = array_column($tags, 'tag_id');
        
        // Kiểm tra xem đây có phải là đồng bộ đầy đủ hay không
        // Nếu đồng bộ trang cuối cùng hoặc không phân trang, thì mới xóa các tags không còn tồn tại
        $full_sync = false;
        if (!empty($_SESSION['full_tags_sync_' . $controller_id])) {
            $full_sync = true;
            
            // Nếu không có tag_id column thì sử dụng id column
            if (empty($platform_tag_ids)) {
                $platform_tag_ids = array_column($tags, 'id');
            }
            
            if (!empty($platform_tag_ids)) {
                // Xóa các tags không còn tồn tại trên nền tảng
                $this->db->where('controller_id', $controller_id);
                $this->db->where_not_in('tag_id', $platform_tag_ids);
                $this->db->delete(db_prefix() . 'topic_controller_tags');
                
                log_message('info', 'Deleted ' . $this->db->affected_rows() . ' outdated tags for controller #' . $controller_id);
            }
            
            // Reset full sync flag
            unset($_SESSION['full_tags_sync_' . $controller_id]);
        }
        
        $this->db->trans_complete();
        
        return [
            'inserted' => $inserted_count,
            'updated' => $updated_count,
            'full_sync' => $full_sync
        ];
    }

    /**
     * Count total tags for a controller
     * @param int $controller_id
     * @return int
     */
    public function get_tags_count($controller_id)
    {
        return $this->db->where('controller_id', $controller_id)
            ->from(db_prefix() . 'topic_controller_tags')
            ->count_all_results();
    }
    
    /**
     * Get tags data for DataTable with search, order and pagination
     * @param int $controller_id
     * @param string $search
     * @param string $order_by
     * @param string $order_dir
     * @param int $start
     * @param int $length
     * @return array
     */
    public function get_tags_for_table($controller_id, $search = '', $order_by = 'name', $order_dir = 'asc', $start = 0, $length = 25)
    {
        $this->db->select('*')
            ->from(db_prefix() . 'topic_controller_tags')
            ->where('controller_id', $controller_id);
        
        // Apply search filter
        if (!empty($search)) {
            $this->db->group_start()
                ->like('name', $search)
                ->or_like('slug', $search)
                ->or_like('tag_id', $search)
                ->group_end();
        }
        
        // Apply order
        $this->db->order_by($order_by, $order_dir);
        
        // Apply limit for pagination
        if ($length > 0) {
            $this->db->limit($length, $start);
        }
        
        return $this->db->get()->result_array();
    }
    
    /**
     * Count filtered tags for DataTable pagination
     * @param int $controller_id
     * @param string $search
     * @return int
     */
    public function get_filtered_tags_count($controller_id, $search = '')
    {
        $this->db->where('controller_id', $controller_id);
        
        // Apply search filter
        if (!empty($search)) {
            $this->db->group_start()
                ->like('name', $search)
                ->or_like('slug', $search)
                ->or_like('tag_id', $search)
                ->group_end();
        }
        
        return $this->db->count_all_results(db_prefix() . 'topic_controller_tags');
    }

    /**
     * Get all controllers for dropdown
     * @return array Array of controller objects
     */
    public function get_all_controllers()
    {
        $this->db->select('id, site, platform');
        $this->db->where('status', 1);
        $this->db->order_by('site', 'ASC');
        return $this->db->get($this->table)->result_array();
    }

    /**
     * Add topic to controller relationship directly
     * @param int $controller_id Controller ID
     * @param string $topicid Topic ID (string format from topic_master)
     * @param int $staff_id Staff ID who created the relationship
     * @return bool Success status
     */
    public function add_topic_to_controller($controller_id, $topicid, $staff_id) 
    {
        // First get the topic_master id based on topicid
        $this->db->select('id');
        $this->db->where('topicid', $topicid);
        $topic_master = $this->db->get(db_prefix() . 'topic_master')->row();
        
        if (!$topic_master) {
            return false;
        }
        
        // Check if relationship already exists
        $this->db->where('controller_id', $controller_id);
        $this->db->where('topic_id', $topic_master->id);
        $exists = $this->db->count_all_results(db_prefix() . 'topic_controller') > 0;
        
        if ($exists) {
            return true; // Relationship already exists, consider it successful
        }
        
        // Insert the relationship
        $data = [
            'controller_id' => $controller_id,
            'topic_id' => $topic_master->id,
            'staff_id' => $staff_id,
            'datecreated' => date('Y-m-d H:i:s')
        ];
        
        $this->db->insert(db_prefix() . 'topic_controller', $data);
        return $this->db->affected_rows() > 0;
    }

    /**
     * Update selected categories for a controller
     * @param int $controller_id Controller ID
     * @param array $selected_categories Array of category IDs
     * @return bool Success status
     */
    public function update_selected_categories($controller_id, $selected_categories)
    {
        // Validate input
        if (!is_numeric($controller_id)) {
            log_activity('Invalid controller_id for update_selected_categories: ' . var_export($controller_id, true));
            return false;
        }
        
        // Ensure selected_categories is an array
        if (!is_array($selected_categories)) {
            log_activity('Converting non-array selected_categories to array for controller ID ' . $controller_id);
            
            // Try to convert to array if possible
            if (is_string($selected_categories) && !empty($selected_categories)) {
                // Check if it's a JSON string
                $temp = json_decode($selected_categories, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($temp)) {
                    $selected_categories = $temp;
                } else {
                    // Single value - convert to array
                    $selected_categories = [$selected_categories];
                }
            } else {
                // Default to empty array for other types
                $selected_categories = [];
            }
        }
        
        // Convert all values to strings to prevent array to string conversion errors
        $selected_categories = array_map('strval', $selected_categories);
        
        // Filter out any empty values
        $selected_categories = array_filter($selected_categories, function($value) {
            return !empty($value);
        });
        
        // Make sure we have unique values
        $selected_categories = array_unique($selected_categories);
        
        // Log the selected categories
        log_activity('Updating selected categories for controller [ID: ' . $controller_id . '] - ' . json_encode($selected_categories));
        
        try {
            // Start transaction
            $this->db->trans_start();
            
            // Get existing categories for this controller to verify selections are valid
            $existing_categories = $this->get_categories($controller_id);
            $existing_ids = [];
            
            foreach ($existing_categories as $category) {
                if (isset($category['category_id'])) {
                    $existing_ids[] = $category['category_id'];
                }
            }
            
            // Prepare categories state data (for backward compatibility)
            $categories_state = [];
            
            // Get existing categories_state to preserve any other data it might contain
            $controller = $this->get($controller_id);
            if ($controller && !empty($controller->categories_state)) {
                $categories_state = json_decode($controller->categories_state, true) ?: [];
            }
            
            // Update with new selected categories
            $categories_state['selected'] = array_values($selected_categories); // Use array_values to reindex array
            $categories_state['last_updated'] = date('Y-m-d H:i:s');
            
            // Save the categories in both the new selected_categories column and the old categories_state field
            $update_data = [
                'selected_categories' => json_encode(array_values($selected_categories)), // Store as JSON array
                'categories_state' => json_encode($categories_state), // Keep old format for backward compatibility
                'dateupdated' => date('Y-m-d H:i:s')
            ];
            
            $this->db->where('id', $controller_id);
            $this->db->update($this->table, $update_data);
            
            $this->db->trans_complete();
            
            if ($this->db->trans_status() !== FALSE) {
                log_activity('Successfully updated selected categories for controller [ID: ' . $controller_id . ']');
                return true;
            } else {
                log_activity('Database error when updating selected categories for controller [ID: ' . $controller_id . ']');
                return false;
            }
        } catch (Exception $e) {
            log_activity('Exception updating selected categories for controller [ID: ' . $controller_id . ']: ' . $e->getMessage());
            return false;
        }
    }
}
