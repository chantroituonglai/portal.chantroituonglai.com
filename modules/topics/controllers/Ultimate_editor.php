<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Ultimate Editor Controller
 * Quản lý các chức năng nâng cao cho việc soạn thảo và xuất bản nội dung
 */
class Ultimate_editor extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Topics_model');
        $this->load->model('Topic_editor_draft_model');
        $this->load->model('Topic_sync_log_model');
        // $lang = $this->session->userdata('admin_language');
        // $this->load->language('ultimate_editor_lang', $lang);  
    }

    /**
     * Trang chính của Ultimate Editor
     * @param int $topic_id ID của topic cần chỉnh sửa
     * @return void
     */
    public function index($topic_id = null)
    {
        if (!$topic_id) {
            show_404();
            return;
        }

        $topic = $this->db->get_where('tbltopics', ['id' => $topic_id])->row();
        if (!$topic) {
            show_404();
            return;
        }

        // Check for active draft
        $active_draft = null;
        if (isset($topic->active_draft_id) && $topic->active_draft_id) {
            $active_draft = $this->db->get_where('tbltopic_editor_drafts', ['id' => $topic->active_draft_id])->row();
            if ($active_draft) {
                // Fetch all drafts for this topic
                $drafts = $this->db->get_where('tbltopic_editor_drafts', ['topic_id' => $topic_id])->result();
                
                $data = [
                    'title' => $active_draft->draft_title,
                    'topic_id' => $topic_id,
                    'topic' => $topic,
                    'active_draft' => $active_draft,
                    'drafts' => $drafts
                ];
                
                // Xác định đường dẫn view theo chuẩn Perfex
                $viewPath = 'topics/ultimate_editor/index';
                log_message('error', 'Attempting to load view with path: ' . $viewPath);
                
                try {
                    // Thử load view với module context
                    $CI = &get_instance();
                    
                    // Thêm module views path vào view paths
                    $moduleViewPath = module_dir_path('topics') . 'views/';
                    log_message('error', 'Adding module view path: ' . $moduleViewPath);
                    $CI->load->add_package_path(module_dir_path('topics'));
                    
                    // Load view
                    $this->load->view($viewPath, $data);
                    log_message('error', 'View loaded successfully');
                    
                    // Reset view path
                    $CI->load->remove_package_path(module_dir_path('topics'));
                } catch (Exception $e) {
                    log_message('error', 'Error loading view: ' . $e->getMessage());
                    log_message('error', 'Error trace: ' . $e->getTraceAsString());
                }
                
                log_message('error', '====== END DEBUG VIEW LOADING ======');
                return;
            }
        }

        // Create new draft if no active draft exists
        $log_data = json_decode($topic->log, true);
        $content = '';
        
        if ($log_data && isset($log_data['changes_data']) && isset($log_data['changes_data']['updated_items'])) {
            foreach ($log_data['changes_data']['updated_items'] as $item) {
                if (isset($item['changes']['Item_Content']['to'])) {
                    $content = $item['changes']['Item_Content']['to'];
                    break; // Get content from first item
                }
            }
        }

        // Ensure we have content
        if (empty($content)) {
            $content = json_encode(['content' => [
                ['type' => 'text', 'text' => '']
            ]]);
        }

        // Create new draft
        $draft_data = [
            'topic_id' => $topic_id,
            'draft_title' => $topic->topictitle,
            'draft_content' => $content,
            'status' => 'draft',
            'version' => 1,
            'created_by' => 1, // Replace with actual user ID
            'last_edited_by' => 1, // Replace with actual user ID
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $this->db->insert('tbltopic_editor_drafts', $draft_data);
        $draft_id = $this->db->insert_id();

        // Update topic's active draft
        $this->db->where('id', $topic_id);
        $this->db->update('tbltopics', ['active_draft_id' => $draft_id]);

        // Fetch all drafts for this topic (will only have the new one at this point)
        $drafts = $this->db->get_where('tbltopic_editor_drafts', ['topic_id' => $topic_id])->result();

        $data = [
            'title' => $topic->topictitle,
            'topic_id' => $topic_id,
            'topic' => $topic,
            'active_draft' => $this->db->get_where('tbltopic_editor_drafts', ['id' => $draft_id])->row(),
            'drafts' => $drafts
        ];

        // Xác định đường dẫn view theo chuẩn Perfex
        $viewPath = 'topics/ultimate_editor/index';
        log_message('error', 'Attempting to load view with path: ' . $viewPath);
        
        try {
            // Thử load view với module context
            $CI = &get_instance();
            
            // Thêm module views path vào view paths
            $moduleViewPath = module_dir_path('topics') . 'views/';
            log_message('error', 'Adding module view path: ' . $moduleViewPath);
            $CI->load->add_package_path(module_dir_path('topics'));
            
            // Load view
            $this->load->view($viewPath, $data);
            log_message('error', 'View loaded successfully');
            
            // Reset view path
            $CI->load->remove_package_path(module_dir_path('topics'));
        } catch (Exception $e) {
            log_message('error', 'Error loading view: ' . $e->getMessage());
            log_message('error', 'Error trace: ' . $e->getTraceAsString());
        }
        
        log_message('error', '====== END DEBUG VIEW LOADING ======');
    }

    /**
     * Guardar borrador
     * @return void
     */
    public function save_draft()
    {
        if (!has_permission('topics', '', 'edit')) {
            ajax_access_denied();
        }

        try {
            $draft_id = $this->input->post('draft_id');
            $topic_id = $this->input->post('topic_id');
            $draft_title = $this->input->post('draft_title');
            $draft_content = $this->input->post('draft_content');
            $draft_description = $this->input->post('draft_description');
            $draft_tags = $this->input->post('draft_tags');
            $meta_description = $this->input->post('meta_description');
            $keywords = $this->input->post('keywords');
            $is_autosave = $this->input->post('is_autosave') ? true : false;
            
            if (empty($draft_id)) {
                throw new Exception(_l('draft_id_required'));
            }
            
            if (empty($topic_id)) {
                throw new Exception(_l('topic_id_required'));
            }
            
            if (empty($draft_title)) {
                throw new Exception(_l('title_required'));
            }
            
            // Lấy draft hiện tại để giữ các metadata hiện có
            $current_draft = $this->Topic_editor_draft_model->get($draft_id);
            $current_metadata = [];
            
            if ($current_draft && $current_draft->draft_metadata) {
                $current_metadata = json_decode($current_draft->draft_metadata, true);
                if (!is_array($current_metadata)) {
                    $current_metadata = [];
                }
            }
            
            // Cập nhật metadata với các giá trị mới
            $draft_metadata = $current_metadata;
            $draft_metadata['meta_description'] = $meta_description ?: ($current_metadata['meta_description'] ?? '');
            $draft_metadata['keywords'] = $keywords ?: ($current_metadata['keywords'] ?? '');
            $draft_metadata['draft_description'] = $draft_description ?: ($current_metadata['draft_description'] ?? '');
            $draft_metadata['draft_tags'] = $draft_tags ?: ($current_metadata['draft_tags'] ?? '');
            
            // Đảm bảo không có giá trị null
            foreach ($draft_metadata as $key => $value) {
                if ($value === null) {
                    $draft_metadata[$key] = '';
                }
            }
            
            // Cập nhật dữ liệu bản nháp
            $draft_data = [
                'draft_title' => $draft_title,
                'draft_content' => $draft_content,
                'draft_metadata' => json_encode($draft_metadata),
                'updated_at' => date('Y-m-d H:i:s'),
                'last_edited_by' => get_staff_user_id()
            ];
            
            $success = $this->Topic_editor_draft_model->update($draft_id, $draft_data);
            
            if (!$success) {
                throw new Exception(_l('error_updating_draft'));
            }
            
            // Lấy dữ liệu bản nháp đã cập nhật
            $draft = $this->Topic_editor_draft_model->get($draft_id);
            
            // Thêm metadata vào kết quả trả về để Frontend có thể cập nhật UI
            if ($draft) {
                $draft_metadata = json_decode($draft->draft_metadata, true);
                $draft->keywords = $draft_metadata['keywords'] ?? '';
                $draft->meta_description = $draft_metadata['meta_description'] ?? '';
                $draft->draft_description = $draft_metadata['draft_description'] ?? '';
                $draft->draft_tags = $draft_metadata['draft_tags'] ?? '';
            }
            
            $response = [
                'success' => true,
                'message' => $is_autosave ? _l('draft_autosaved') : _l('draft_saved_successfully'),
                'data' => $draft
            ];
        
        echo json_encode($response);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Tạo draft mới
     * @return void
     */
    public function create_draft()
    {
        if (!has_permission('topics', '', 'create')) {
            ajax_access_denied();
        }

        $data = $this->input->post();
        
        // Kiểm tra dữ liệu bắt buộc
        if (empty($data['topic_id']) || empty($data['draft_title'])) {
            $response = [
                'success' => false,
                'message' => _l('required_fields_missing')
            ];
            echo json_encode($response);
            return;
        }
        
        // Tạo draft mới
        $draft_data = [
            'topic_id' => $data['topic_id'],
            'draft_title' => $data['draft_title'],
            'draft_content' => $data['draft_content'] ?? '',
            'status' => 'draft',
            'version' => 1
        ];
        
        if (isset($data['draft_sections'])) {
            $draft_data['draft_sections'] = $data['draft_sections'];
        }
        
        if (isset($data['draft_metadata'])) {
            $draft_data['draft_metadata'] = $data['draft_metadata'];
        }
        
        $draft_id = $this->Topic_editor_draft_model->add($draft_data);
        
        if ($draft_id) {
            // Nếu cần đặt làm active draft
            if (isset($data['set_as_active']) && $data['set_as_active'] == 'true') {
                $this->Topic_editor_draft_model->set_active_draft($data['topic_id'], $draft_id);
            }
            
            $response = [
                'success' => true,
                'message' => _l('draft_created_successfully'),
                'draft_id' => $draft_id
            ];
        } else {
            $response = [
                'success' => false,
                'message' => _l('draft_creation_failed')
            ];
        }
        
        echo json_encode($response);
    }

    /**
     * Xóa draft
     * @param int $draft_id ID của draft cần xóa
     * @return void
     */
    public function delete_draft($draft_id)
    {
        if (!has_permission('topics', '', 'delete')) {
            ajax_access_denied();
        }

        $result = $this->Topic_editor_draft_model->delete($draft_id);
        
        if ($result) {
            $response = [
                'success' => true,
                'message' => _l('draft_deleted_successfully')
            ];
        } else {
            $response = [
                'success' => false,
                'message' => _l('draft_deletion_failed')
            ];
        }
        
        echo json_encode($response);
    }

    /**
     * Lấy thông tin chi tiết của draft
     * @param int $draft_id ID của draft
     * @return void
     */
    public function get_draft($draft_id = null)
    {
        if (!has_permission('topics', '', 'view')) {
            ajax_access_denied();
        }

        // If draft_id is not provided in URL path, check for it in query parameters
        if ($draft_id === null) {
            $draft_id = $this->input->get('draft_id');
            if (empty($draft_id)) {
                $response = [
                    'success' => false,
                    'message' => _l('draft_id_required')
                ];
                echo json_encode($response);
                return;
            }
        }

        $draft = $this->Topic_editor_draft_model->get($draft_id);
        
        if ($draft) {
            // Chuyển đổi các trường JSON thành đối tượng
            if (isset($draft->draft_sections) && !empty($draft->draft_sections)) {
                $draft->draft_sections = json_decode($draft->draft_sections);
            }
            
            if (isset($draft->draft_metadata) && !empty($draft->draft_metadata)) {
                $draft->draft_metadata = json_decode($draft->draft_metadata);
            }
            
            $response = [
                'success' => true,
                'draft' => $draft
            ];
        } else {
            $response = [
                'success' => false,
                'message' => _l('draft_not_found')
            ];
        }
        
        echo json_encode($response);
    }

    /**
     * Đặt draft làm active
     * @return void
     */
    public function set_active_draft()
    {
        if (!has_permission('topics', '', 'edit')) {
            ajax_access_denied();
        }

        $topic_id = $this->input->post('topic_id');
        $draft_id = $this->input->post('draft_id');
        $full_draft = $this->input->post('full_draft');
        
        if (empty($topic_id) || empty($draft_id)) {
            $response = [
                'success' => false,
                'message' => _l('required_fields_missing')
            ];
            echo json_encode($response);
            return;
        }
        
        // Cập nhật active draft trong model
        $result = $this->Topic_editor_draft_model->set_active_draft($topic_id, $draft_id);
        
        // Nếu có dữ liệu đầy đủ của draft, cập nhật thêm thông tin
        if ($result && !empty($full_draft)) {
            try {
                // Giải mã dữ liệu từ JSON string
                $draft_data = json_decode($full_draft, true);
                
                // Kiểm tra và đảm bảo dữ liệu hợp lệ
                if ($draft_data && isset($draft_data['id']) && $draft_data['id'] == $draft_id) {
                    // Chuẩn bị dữ liệu cập nhật
                    $update_data = [];
                    
                    // Kiểm tra và cập nhật metadata nếu có
                    if (isset($draft_data['draft_metadata'])) {
                        // Đảm bảo metadata là chuỗi JSON
                        if (is_array($draft_data['draft_metadata']) || is_object($draft_data['draft_metadata'])) {
                            $update_data['draft_metadata'] = json_encode($draft_data['draft_metadata']);
                        } else {
                            $update_data['draft_metadata'] = $draft_data['draft_metadata'];
                        }
                    }
                    
                    // Các trường khác nếu cần
                    if (!empty($update_data)) {
                        // Thêm thông tin người cập nhật và thời gian
                        $update_data['last_edited_by'] = get_staff_user_id();
                        $update_data['updated_at'] = date('Y-m-d H:i:s');
                        
                        // Cập nhật dữ liệu draft
                        $this->db->where('id', $draft_id);
                        $this->db->update(db_prefix() . 'topic_editor_drafts', $update_data);
                        
                        log_activity('Updated draft metadata [Draft ID: ' . $draft_id . ']');
                    }
                }
            } catch (Exception $e) {
                // Log lỗi nhưng không trả về lỗi cho client
                log_message('error', 'Error updating draft metadata: ' . $e->getMessage());
            }
        }
        
        if ($result) {
            $response = [
                'success' => true,
                'message' => _l('active_draft_updated_successfully')
            ];
        } else {
            $response = [
                'success' => false,
                'message' => _l('active_draft_update_failed')
            ];
        }
        
        echo json_encode($response);
    }

    /**
     * Tạo bản sao của draft
     * @return void
     */
    public function duplicate_draft()
    {
        if (!has_permission('topics', '', 'create')) {
            ajax_access_denied();
        }

        $draft_id = $this->input->post('draft_id');
        
        if (empty($draft_id)) {
            $response = [
                'success' => false,
                'message' => _l('required_fields_missing')
            ];
            echo json_encode($response);
            return;
        }
        
        $new_draft_id = $this->Topic_editor_draft_model->duplicate_draft($draft_id);
        
        if ($new_draft_id) {
            $response = [
                'success' => true,
                'message' => _l('draft_duplicated_successfully'),
                'draft_id' => $new_draft_id
            ];
        } else {
            $response = [
                'success' => false,
                'message' => _l('draft_duplication_failed')
            ];
        }
        
        echo json_encode($response);
    }

    /**
     * Lấy danh sách draft theo topic_id
     * @return void
     */
    public function get_drafts()
    {
        if (!has_permission('topics', '', 'view')) {
            ajax_access_denied();
        }

        $topic_id = $this->input->get('topic_id');
        
        if (empty($topic_id)) {
            $response = [
                'success' => false,
                'message' => _l('required_fields_missing')
            ];
            echo json_encode($response);
            return;
        }
        
        // Lấy thông tin topic để biết draft nào đang active
        $topic = $this->Topics_model->get_topic($topic_id);
        $active_draft_id = $topic ? $topic->active_draft_id : null;
        
        // Lấy danh sách draft
        $drafts = $this->Topic_editor_draft_model->get($topic_id, true);
        
        // Thêm thông tin người tạo và chỉnh sửa
        foreach ($drafts as &$draft) {
            $draft['created_by_name'] = $draft['created_by'] ? get_staff_full_name($draft['created_by']) : '';
            $draft['last_edited_by_name'] = $draft['last_edited_by'] ? get_staff_full_name($draft['last_edited_by']) : '';
            $draft['is_active'] = ($active_draft_id == $draft['id']);
        }
        
        $response = [
            'success' => true,
            'drafts' => $drafts,
            'active_draft_id' => $active_draft_id
        ];
        
        echo json_encode($response);
    }

    /**
     * Lưu cài đặt editor cho topic
     * @return void
     */
    public function save_editor_settings()
    {
        if (!has_permission('topics', '', 'edit')) {
            ajax_access_denied();
        }

        $topic_id = $this->input->post('topic_id');
        $settings = $this->input->post('settings');
        
        if (empty($topic_id) || empty($settings)) {
            $response = [
                'success' => false,
                'message' => _l('required_fields_missing')
            ];
            echo json_encode($response);
            return;
        }
        
        // Chuyển đổi settings thành JSON nếu cần
        if (is_array($settings)) {
            $settings = json_encode($settings);
        }
        
        // Cập nhật settings
        $this->db->where('id', $topic_id);
        $this->db->update(db_prefix() . 'topics', ['editor_settings' => $settings]);
        
        if ($this->db->affected_rows() > 0) {
            $response = [
                'success' => true,
                'message' => _l('editor_settings_saved_successfully')
            ];
        } else {
            $response = [
                'success' => false,
                'message' => _l('editor_settings_save_failed')
            ];
        }
        
        echo json_encode($response);
    }

    /**
     * Chuyển đổi draft thành bài viết cuối cùng
     * @return void
     */
    public function convert_to_final()
    {
        if (!has_permission('topics', '', 'edit')) {
            ajax_access_denied();
        }

        $draft_id = $this->input->post('draft_id');
        
        if (empty($draft_id)) {
            $response = [
                'success' => false,
                'message' => _l('required_fields_missing')
            ];
            echo json_encode($response);
            return;
        }
        
        $result = $this->Topic_editor_draft_model->convert_to_final($draft_id);
        
        if ($result) {
            $response = [
                'success' => true,
                'message' => _l('draft_converted_to_final_successfully')
            ];
        } else {
            $response = [
                'success' => false,
                'message' => _l('draft_conversion_failed')
            ];
        }
        
        echo json_encode($response);
    }

    /**
     * Análisis SEO mejorado
     * @return void
     */
    public function analyze_seo()
    {
        if (!has_permission('topics', '', 'view')) {
            ajax_access_denied();
        }

        try {
        $draft_id = $this->input->post('draft_id');
            $content = $this->input->post('content');
            $title = $this->input->post('title');
            $description = $this->input->post('description');
        $target_keyword = $this->input->post('target_keyword');
            $tags = $this->input->post('tags');
            
            // Si tenemos draft_id pero no content, obtenemos el contenido del draft
            if ($draft_id && (!$content || !$title)) {
                $draft = $this->Topic_editor_draft_model->get($draft_id);
                if (!$draft) {
                    throw new Exception(_l('draft_not_found'));
                }
                
                $content = $content ?: $draft->draft_content;
                $title = $title ?: $draft->draft_title;
                $description = $description ?: $draft->draft_description;
            }
            
            if (empty($content)) {
                throw new Exception(_l('content_cannot_be_empty'));
            }
            
            // Implementación local de análisis SEO
            $analysis = $this->perform_local_seo_analysis($content, $title, $description, $target_keyword);
            
            $response = [
                'success' => true,
                'analysis' => $analysis,
                'source' => 'local'
            ];
            
            echo json_encode($response);
            
        } catch (Exception $e) {
            log_message('error', 'Error in analyze_seo: ' . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Implementación local del análisis SEO
     * @param string $content Contenido HTML
     * @param string $title Título del contenido
     * @param string $description Descripción meta
     * @param string $target_keyword Palabra clave objetivo
     * @return array Resultados del análisis
     */
    private function perform_local_seo_analysis($content, $title, $description, $target_keyword = '')
    {
        // Strip HTML tags for text analysis
        $text = strip_tags($content);
        
        // Count words
        $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $word_count = count($words);
        
        // Initialize score and suggestions
        $score = 100;
        $suggestions = [];
        
        // Check title
        if (empty($title)) {
            $suggestions[] = [
                'type' => 'error',
                'text' => _l('seo_error_no_title')
            ];
            $score -= 20;
        } else {
            $title_length = mb_strlen($title, 'UTF-8');
            
            if ($title_length < 30) {
                $suggestions[] = [
                    'type' => 'warning',
                    'text' => _l('seo_warning_title_short')
                ];
                $score -= 10;
            } else if ($title_length > 60) {
                $suggestions[] = [
                    'type' => 'warning',
                    'text' => _l('seo_warning_title_long')
                ];
                $score -= 10;
            } else {
                $suggestions[] = [
                    'type' => 'good',
                    'text' => _l('seo_good_title_length')
                ];
            }
            
            // Check if title contains target keyword
            if (!empty($target_keyword) && stripos($title, $target_keyword) !== false) {
                $suggestions[] = [
                    'type' => 'good',
                    'text' => _l('seo_good_keyword_in_title')
                ];
            } else if (!empty($target_keyword)) {
                $suggestions[] = [
                    'type' => 'warning',
                    'text' => _l('seo_warning_keyword_not_in_title')
                ];
                $score -= 5;
            }
        }
        
        // Check content length
        if ($word_count < 300) {
            $suggestions[] = [
                'type' => 'error',
                'text' => _l('seo_error_content_short')
            ];
            $score -= 20;
        } else if ($word_count < 600) {
            $suggestions[] = [
                'type' => 'warning',
                'text' => _l('seo_warning_content_medium')
            ];
            $score -= 10;
        } else {
            $suggestions[] = [
                'type' => 'good',
                'text' => _l('seo_good_content_length')
            ];
        }
        
        // Check headings
        $has_h1 = preg_match('/<h1[^>]*>.*?<\/h1>/i', $content);
        $has_h2 = preg_match('/<h2[^>]*>.*?<\/h2>/i', $content);
        $has_h3 = preg_match('/<h3[^>]*>.*?<\/h3>/i', $content);
        $headings_count = preg_match_all('/<h[1-6][^>]*>.*?<\/h[1-6]>/i', $content, $matches);
        
        // Check images
        $images_count = preg_match_all('/<img[^>]*>/i', $content, $matches);
        
        // Check links
        $links_count = preg_match_all('/<a[^>]*href=["\'][^"\']*["\'][^>]*>.*?<\/a>/i', $content, $matches);
        
        // Ensure score is between 0-100
        $score = max(0, min(100, $score));
        
        return [
            'score' => $score,
            'suggestions' => $suggestions,
            'stats' => [
                'wordCount' => $word_count,
                'headingsCount' => $headings_count,
                'imagesCount' => $images_count,
                'linksCount' => $links_count
            ]
        ];
    }

    /**
     * Análisis de palabras clave
     * @return void
     */
    public function analyze_keywords()
    {
        if (!has_permission('topics', '', 'view')) {
            ajax_access_denied();
        }

        try {
            $draft_id = $this->input->post('draft_id');
            $content = $this->input->post('content');
            $main_keywords = $this->input->post('main_keywords');
            
            // Si tenemos draft_id pero no content, obtenemos el contenido del draft
            if ($draft_id && !$content) {
        $draft = $this->Topic_editor_draft_model->get($draft_id);
        if (!$draft) {
                    throw new Exception(_l('draft_not_found'));
                }
                
                $content = $draft->draft_content;
            }
            
            if (empty($content)) {
                throw new Exception(_l('content_cannot_be_empty'));
            }
            
            // Cargar el controlador Writing para usar sus funciones
            if (!class_exists('Writing')) {
                $this->load->library('../controllers/writing');
            }
            
            // Usar la función analyze_keywords del controlador Writing
            $writing = new Writing();
            $analysis = $writing->analyze_keywords($content, $main_keywords);
        
        $response = [
            'success' => true,
                'analysis' => $analysis
        ];
        
        echo json_encode($response);
            
        } catch (Exception $e) {
            log_message('error', 'Error in analyze_keywords: ' . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Importa los datos del Draft Writer al Ultimate Editor
     * @return void
     */
    public function import_from_draft_writer()
    {
        if (!has_permission('topics', '', 'edit')) {
            ajax_access_denied();
        }

        try {
            $topic_id = $this->input->post('topic_id');
            $workflow_data = $this->input->post('workflow_data');
            $draft_content = $this->input->post('draft_content');
            
            if (empty($topic_id)) {
                throw new Exception(_l('required_fields_missing'));
            }
            
            // Si tenemos workflow_data, extraer contenido
            $content = '';
            $title = '';
            $description = '';
            
            if ($workflow_data && !empty($workflow_data['data']['response']['data'])) {
                $data = $workflow_data['data']['response']['data'][0];
                
                // Extract data fields
                $title = $data['Title'] ?? '';
                $summary = $data['Summary'] ?? '';
                
                // Build content from items
                $content = '<h1>' . $title . '</h1>';
                $content .= '<p>' . $summary . '</p>';
                
                // Add items
                if (isset($data['Item_Position']) && isset($data['Item_Title']) && isset($data['Item_Content'])) {
                    $content .= '<h2>' . $data['Item_Title'] . '</h2>';
                    $content .= $data['Item_Content'];
                }
                
                // Add topic footer if exists
                if (isset($data['Topic_footer']) && !empty($data['Topic_footer'])) {
                    $content .= '<div class="topic-footer">' . $data['Topic_footer'] . '</div>';
                }
            }
            // Si tenemos draft_content, usarlo directamente
            else if ($draft_content) {
                if (is_string($draft_content)) {
                    // Intentar parsear JSON
                    $parsed = json_decode($draft_content, true);
                    if ($parsed && json_last_error() === JSON_ERROR_NONE) {
                        $content = $parsed['content'] ?? '';
                        $title = $parsed['title'] ?? '';
                        $description = $parsed['description'] ?? '';
                    } else {
                        // Usar el contenido tal cual
                        $content = $draft_content;
                    }
                } else if (is_array($draft_content)) {
                    $content = $draft_content['content'] ?? '';
                    $title = $draft_content['title'] ?? '';
                    $description = $draft_content['description'] ?? '';
                }
            }
            
            // Si no tenemos content, verificar en localStorage
            if (empty($content) && empty($title)) {
                // No podemos acceder directamente a localStorage desde el backend
                // Enviamos una señal al frontend para que recupere los datos
                $response = [
                    'success' => true,
                    'need_localstorage_data' => true,
                    'message' => _l('load_from_local_storage')
                ];
                
                echo json_encode($response);
                return;
            }
            
            // Crear un nuevo draft con el contenido importado
            $draft_data = [
                'topic_id' => $topic_id,
                'draft_title' => $title,
                'draft_content' => $content,
                'meta_description' => $description,
                'created_by' => get_staff_user_id(),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $draft_id = $this->Topic_editor_draft_model->add($draft_data);
            
            if (!$draft_id) {
                throw new Exception(_l('error_creating_draft'));
            }
            
            // Establecer como draft activo
            $this->Topic_editor_draft_model->set_active_draft($topic_id, $draft_id);
            
            $response = [
                'success' => true,
                'draft_id' => $draft_id,
                'message' => _l('draft_imported_successfully'),
                'redirect_url' => admin_url('topics/ultimate_editor/index/' . $topic_id)
            ];
            
            echo json_encode($response);
            
        } catch (Exception $e) {
            log_message('error', 'Error importing draft: ' . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Recupera un draft desde localStorage
     * @return void
     */
    public function recover_from_local_storage()
    {
        if (!has_permission('topics', '', 'edit')) {
            ajax_access_denied();
        }

        try {
            $topic_id = $this->input->post('topic_id');
            $local_storage_data = $this->input->post('local_storage_data');
            
            if (empty($topic_id) || empty($local_storage_data)) {
                throw new Exception(_l('required_fields_missing'));
            }
            
            // Decodificar datos
            $data = json_decode($local_storage_data, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception(_l('invalid_local_storage_data'));
            }
            
            // Extraer datos relevantes
            $title = $data['title'] ?? '';
            $content = $data['content'] ?? '';
            $description = $data['description'] ?? '';
            
            // Crear un nuevo draft con el contenido recuperado
            $draft_data = [
                'topic_id' => $topic_id,
                'draft_title' => $title,
                'draft_content' => $content,
                'meta_description' => $description,
                'created_by' => get_staff_user_id(),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $draft_id = $this->Topic_editor_draft_model->add($draft_data);
            
            if (!$draft_id) {
                throw new Exception(_l('error_creating_draft'));
            }
            
            // Establecer como draft activo
            $this->Topic_editor_draft_model->set_active_draft($topic_id, $draft_id);
            
            $response = [
                'success' => true,
                'draft_id' => $draft_id,
                'message' => _l('draft_recovered_successfully'),
                'redirect_url' => admin_url('topics/ultimate_editor/index/' . $topic_id)
            ];
            
            echo json_encode($response);
            
        } catch (Exception $e) {
            log_message('error', 'Error recovering draft: ' . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Xuất bản nội dung từ draft
     * @return void
     */
    public function publish_draft()
    {
        if (!has_permission('topics', '', 'edit')) {
            ajax_access_denied();
        }

        $draft_id = $this->input->post('draft_id');
        $platform = $this->input->post('platform');
        $controller_id = $this->input->post('controller_id');
        
        if (empty($draft_id) || empty($platform) || empty($controller_id)) {
            $response = [
                'success' => false,
                'message' => _l('required_fields_missing')
            ];
            echo json_encode($response);
            return;
        }
        
        // Lấy thông tin draft
        $draft = $this->Topic_editor_draft_model->get($draft_id);
        if (!$draft) {
            $response = [
                'success' => false,
                'message' => _l('draft_not_found')
            ];
            echo json_encode($response);
            return;
        }
        
        // Tạo session đồng bộ
        $session_id = $this->Topic_sync_log_model->create_session(
            $controller_id,
            'publish_draft',
            [
                'draft_id' => $draft_id,
                'platform' => $platform,
                'total_items' => 1,
                'items_processed' => 0,
                'topic_id' => $draft->topic_id
            ]
        );
        
        // Tạo response ban đầu
        $response = [
            'success' => true,
            'message' => _l('publish_process_started'),
            'session_id' => $session_id
        ];
        
        echo json_encode($response);
        
        // Bắt đầu tiến trình xuất bản (sẽ được xử lý bởi AJAX tiếp theo)
    }

    /**
     * Kiểm tra tiến trình xuất bản
     * @return void
     */
    public function check_publish_status()
    {
        if (!has_permission('topics', '', 'view')) {
            ajax_access_denied();
        }

        $session_id = $this->input->get('session_id');
        
        if (empty($session_id)) {
            $response = [
                'success' => false,
                'message' => _l('required_fields_missing')
            ];
            echo json_encode($response);
            return;
        }
        
        // Lấy thông tin session
        $session = $this->Topic_sync_log_model->get_session($session_id);
        
        if (!$session) {
            $response = [
                'success' => false,
                'message' => _l('session_not_found')
            ];
            echo json_encode($response);
            return;
        }
        
        // Định dạng dữ liệu trả về
        $response = $this->Topic_sync_log_model->format_session_for_response($session);
        
        echo json_encode($response);
    }

    /**
     * Tiếp tục quá trình xuất bản
     * @return void
     */
    public function continue_publish()
    {
        if (!has_permission('topics', '', 'edit')) {
            ajax_access_denied();
        }

        $session_id = $this->input->post('session_id');
        
        if (empty($session_id)) {
            $response = [
                'success' => false,
                'message' => _l('required_fields_missing')
            ];
            echo json_encode($response);
            return;
        }
        
        // Lấy thông tin session
        $session = $this->Topic_sync_log_model->get_session($session_id);
        
        if (!$session) {
            $response = [
                'success' => false,
                'message' => _l('session_not_found')
            ];
            echo json_encode($response);
            return;
        }
        
        // Xử lý dữ liệu summary
        $summary_data = json_decode($session->summary_data, true);
        
        // Lấy thông tin draft
        $draft_id = $summary_data['draft_id'];
        $draft = $this->Topic_editor_draft_model->get($draft_id);
        
        if (!$draft) {
            // Cập nhật session với lỗi
            $this->Topic_sync_log_model->complete_session(
                $session_id,
                'failed',
                ['error_message' => 'Draft not found'],
                'Draft not found'
            );
            
            $response = [
                'success' => false,
                'message' => _l('draft_not_found')
            ];
            echo json_encode($response);
            return;
        }
        
        // Thực hiện xuất bản (sẽ được triển khai đầy đủ theo connector)
        // Đây là mẫu đơn giản
        $publish_result = $this->perform_publish($draft, $summary_data);
        
        if ($publish_result['success']) {
            // Cập nhật session thành công
            $this->Topic_sync_log_model->complete_session(
                $session_id,
                'completed',
                [
                    'items_processed' => 1,
                    'success_count' => 1,
                    'published_url' => $publish_result['url'] ?? null
                ],
                'Publish completed successfully'
            );
            
            // Cập nhật trạng thái draft
            $this->Topic_editor_draft_model->update($draft_id, [
                'status' => 'published'
            ]);
            
            $response = [
                'success' => true,
                'message' => _l('publish_completed_successfully'),
                'url' => $publish_result['url'] ?? null
            ];
        } else {
            // Cập nhật session thất bại
            $this->Topic_sync_log_model->complete_session(
                $session_id,
                'failed',
                [
                    'error_message' => $publish_result['message']
                ],
                'Publish failed: ' . $publish_result['message']
            );
            
            $response = [
                'success' => false,
                'message' => $publish_result['message']
            ];
        }
        
        echo json_encode($response);
    }

    /**
     * Thực hiện xuất bản draft (mẫu đơn giản)
     * @param object $draft Thông tin draft
     * @param array $summary_data Dữ liệu từ session
     * @return array Kết quả xuất bản
     */
    private function perform_publish($draft, $summary_data)
    {
        // Mẫu đơn giản để quy trình hoạt động
        // Trong thực tế, sẽ sử dụng connector để xuất bản lên platform
        
        // Cập nhật topic với nội dung từ draft
        $this->db->where('id', $draft->topic_id);
        $this->db->update(db_prefix() . 'topics', [
            'content' => $draft->draft_content,
            'name' => $draft->draft_title,
            'last_updated' => date('Y-m-d H:i:s')
        ]);
        
        if ($this->db->affected_rows() > 0) {
            return [
                'success' => true,
                'message' => 'Content updated in local database',
                'url' => admin_url('topics/detail/' . $draft->topic_id)
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to update content in local database'
            ];
        }
    }

    /**
     * Thực thi một workflow và lấy kết quả
     * Hỗ trợ tương thích với API đã có để lấy dữ liệu từ N8N
     * @return void
     */
    public function execute_workflow()
    {
        if (!has_permission('topics', '', 'view')) {
            ajax_access_denied();
            return;
        }

        $topic_id = $this->input->post('topic_id');
        // Prepare workflow data (similar to DraftWritingProcessor)
        $workflow_data = $this->prepare_workflow_data($topic_id);
        
        // Call the n8n service
        $n8n_response = $this->call_n8n_service($workflow_data);
        
        if (isset($n8n_response['success']) && $n8n_response['success']) {
            // Check if data is returned as an array (as in json_response_step1.md)
            if (isset($n8n_response['data']) && is_array($n8n_response['data']) && isset($n8n_response['data'][0])) {
                $response = [
                    'success' => true,
                    'message' => _l('workflow_executed_successfully'),
                    'data' => [
                        'response'     => $n8n_response['data'],
                        'workflow_id'  => $workflow_data['workflow_data']['workflow_id'],
                        'execution_id' => null,
                        'needs_polling'=> false
                    ]
                ];
            } elseif (isset($n8n_response['data']['response']['success']) && $n8n_response['data']['response']['success'] === true) {
                $response = [
                    'success' => true,
                    'message' => _l('workflow_executed_successfully'),
                    'data' => [
                        'response'     => $n8n_response['data']['response'],
                        'workflow_id'  => $n8n_response['data']['workflow_id'] ?? $workflow_data['workflow_data']['workflow_id'],
                        'execution_id' => $n8n_response['data']['execution_id'] ?? null,
                        'needs_polling'=> true
                    ]
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => $n8n_response['message'] ?? _l('workflow_execution_failed')
                ];
            }
        } else {
            $response = [
                'success' => false,
                'message' => $n8n_response['message'] ?? _l('workflow_execution_failed')
            ];
        }
        
        echo json_encode($response);
    }
    
    /**
     * Call n8n service to execute workflow
     * @param array $data Data to send to n8n
     * @return array Response from n8n
     */
    private function call_n8n_service($data)
    {
        try {
            // Get n8n service URL from config
            $n8n_host = get_option('topics_n8n_host');
            $n8n_webhook_url = get_option('topics_n8n_webhook_url');
            $n8n_api_key = get_option('topics_n8n_api_key');
            
            log_message('error', 'N8N Config - Host: ' . $n8n_host . ', Webhook URL: ' . $n8n_webhook_url);
            
            if (empty($n8n_host) && empty($n8n_webhook_url)) {
                throw new Exception('N8N service URL not configured. Please check topics_n8n_host or topics_n8n_webhook_url settings.');
            }

            // Prepare webhook URL
            $webhook_url = '';
            if (!empty($n8n_webhook_url)) {
                $webhook_url = rtrim($n8n_webhook_url, '/') . '/' . $data['workflow_data']['workflow_id'];
            } else {
                $webhook_url = rtrim($n8n_host, '/') . '/webhook/' . $data['workflow_data']['workflow_id'];
            }
            
            // Log the URL being called
            log_message('error', 'Calling N8N webhook URL: ' . $webhook_url);
            
            // Initialize curl
            $ch = curl_init($webhook_url);
            
            // Set curl options
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            
            $headers = [
                'Content-Type: application/json',
                'Accept: application/json'
            ];
            
            // Add API key if available
            if (!empty($n8n_api_key)) {
                $headers[] = 'X-N8N-API-KEY: ' . $n8n_api_key;
            }
            
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30); // 30 seconds timeout
            
            // Execute curl request
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            // Log raw response
            log_message('error', 'N8N raw response: ' . $response);
            log_message('error', 'N8N HTTP code: ' . $http_code);
            
            // Check for curl errors
            if (curl_errno($ch)) {
                $curl_error = curl_error($ch);
                curl_close($ch);
                throw new Exception('Curl error: ' . $curl_error);
            }
            
            // Close curl
            curl_close($ch);
            
            // Check HTTP response code
            if ($http_code !== 200) {
                throw new Exception("N8N service returned HTTP code {$http_code}. Response: " . substr($response, 0, 1000));
            }
            
            // Decode response
            $decoded_response = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid JSON response from n8n service. Error: ' . json_last_error_msg() . '. Response: ' . substr($response, 0, 1000));
            }
            
            return $decoded_response;
            
        } catch (Exception $e) {
            // Log the error with full context
            log_message('error', sprintf(
                "N8N service call failed:\nError: %s\nTrace: %s\nData sent: %s",
                $e->getMessage(),
                $e->getTraceAsString(),
                json_encode($data)
            ));
            
            // Return error response
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'error_details' => [
                    'code' => $e->getCode(),
                    'file' => basename($e->getFile()),
                    'line' => $e->getLine()
                ]
            ];
        }
    }
    
    /**
     * Lấy nội dung draft từ kết quả workflow
     * @return void
     */
    public function get_draft_content()
    {
        // Kiểm tra quyền
        if (!has_permission('topics', '', 'view')) {
            ajax_access_denied();
        }

        try {
            // Lấy dữ liệu từ request
            $execution_id = $this->input->get('execution_id');
            $workflow_id = $this->input->get('workflow_id');
            $topic_id = $this->input->get('topic_id');
            
            // Log request parameters for debugging
            log_message('error', "get_draft_content called with params: " . 
                        json_encode(['execution_id' => $execution_id, 'workflow_id' => $workflow_id, 'topic_id' => $topic_id]));
            
            // Kiểm tra dữ liệu bắt buộc cho workflow_id và topic_id
            if (empty($workflow_id) || empty($topic_id)) {
                throw new Exception(_l('required_fields_missing'));
            }
            
            // Nếu không có execution_id, trả về active draft hiện tại
            if (empty($execution_id)) {
                log_message('error', "No execution_id provided, returning active draft for topic {$topic_id}");
                
                // Lấy active draft
                $active_draft = $this->Topic_editor_draft_model->get_active_draft($topic_id);
                
                if ($active_draft) {
                    log_message('error', "Found active draft with ID {$active_draft->id} for topic {$topic_id}");
                    
                    // Parse metadata if exists
                    $metadata = [];
                    if (!empty($active_draft->draft_metadata)) {
                        $decoded_metadata = json_decode($active_draft->draft_metadata, true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $metadata = $decoded_metadata;
                        }
                    }
                    
                    $response = [
                        'success' => true,
                        'message' => _l('loaded_current_draft'),
                        'data' => [
                            'draft_id' => $active_draft->id,
                            'draft_content' => $active_draft->draft_content,
                            'draft_title' => $active_draft->draft_title,
                            'version' => $active_draft->version,
                            'status' => $active_draft->status,
                            'meta_description' => $metadata['meta_description'] ?? '',
                            'keywords' => $metadata['keywords'] ?? ''
                        ]
                    ];
                    
                    echo json_encode($response);
                    return;
                } else {
                    log_message('error', "No active draft found for topic {$topic_id}");
                    // Nếu không có active draft, trả về lỗi để client có thể thực thi workflow
                    echo json_encode([
                        'success' => false,
                        'message' => _l('no_active_draft_found'),
                        'needs_workflow' => true
                    ]);
                    return;
                }
            }
            
            // Tiếp tục xử lý với execution_id nếu có
            log_message('error', "Checking execution status for workflow {$workflow_id}, execution {$execution_id}");
            
            // Kiểm tra trạng thái execution
            $execution_status = check_n8n_execution_status($workflow_id, $execution_id);
            log_message('error', "Execution status result: " . json_encode($execution_status));
            
            if ($execution_status['success']) {
                $status = $execution_status['data']['status'] ?? null;
                
                if ($status === 'success') {
                    log_message('error', "Workflow execution completed successfully");
                    
                    // Lấy dữ liệu từ kết quả execution
                    $data = $execution_status['data']['data'] ?? [];
                    
                    // Log received data
                    log_message('error', "Received data from workflow: " . json_encode(array_keys($data)));
                    
                    // Kiểm tra nếu dữ liệu rỗng hoặc không hợp lệ
                    if (empty($data)) {
                        log_message('error', "Empty data received from workflow");
                        throw new Exception(_l('empty_data_received_from_workflow'));
                    }
                    
                    // Lấy active draft và cập nhật nội dung
                    $active_draft = $this->Topic_editor_draft_model->get_active_draft($topic_id);
                    
                    if ($active_draft) {
                        log_message('error', "Updating existing draft with ID {$active_draft->id}");
                        
                        // Prepare draft content - handle different response formats
                        $content = '';
                        $title = '';
                        
                        // Check for content in different possible locations
                        if (isset($data['content'])) {
                            $content = $data['content'];
                        } elseif (isset($data['section_content'])) {
                            $content = $data['section_content'];
                        } elseif (isset($data['draft_content'])) {
                            $content = $data['draft_content'];
                        } elseif (isset($data['html_content'])) {
                            $content = $data['html_content'];
                        }
                        
                        // Check for title in different possible locations
                        if (isset($data['title'])) {
                            $title = $data['title'];
                        } elseif (isset($data['section_title'])) {
                            $title = $data['section_title'];
                        } elseif (isset($data['draft_title'])) {
                            $title = $data['draft_title'];
                        } elseif (isset($data['post_title'])) {
                            $title = $data['post_title'];
                        }
                        
                        // Cập nhật nội dung draft
                        $draft_data = [
                            'draft_content' => $content ?: $active_draft->draft_content,
                            'draft_title' => $title ?: $active_draft->draft_title,
                            'last_edited_by' => get_staff_user_id()
                        ];
                        
                        // Update metadata if available
                        $draft_metadata = [];
                        if (!empty($active_draft->draft_metadata)) {
                            $decoded_metadata = json_decode($active_draft->draft_metadata, true);
                            if (json_last_error() === JSON_ERROR_NONE) {
                                $draft_metadata = $decoded_metadata;
                            }
                        }
                        
                        if (isset($data['meta_description'])) {
                            $draft_metadata['meta_description'] = $data['meta_description'];
                        }
                        
                        if (isset($data['keywords'])) {
                            $draft_metadata['keywords'] = $data['keywords'];
                        }
                        
                        if (!empty($draft_metadata)) {
                            $draft_data['draft_metadata'] = json_encode($draft_metadata);
                        }
                        
                        // Update draft in database
                        $this->Topic_editor_draft_model->update($active_draft->id, $draft_data);
                        
                        // Trả về thông tin draft đã cập nhật
                        $response = [
                            'success' => true,
                            'message' => _l('draft_updated_with_workflow_content'),
                            'data' => [
                                'draft_id' => $active_draft->id,
                                'draft_content' => $draft_data['draft_content'],
                                'draft_title' => $draft_data['draft_title'],
                                'meta_description' => $draft_metadata['meta_description'] ?? '',
                                'keywords' => $draft_metadata['keywords'] ?? '',
                                'version' => $active_draft->version,
                                'status' => $active_draft->status,
                                'execution_data' => $data
                            ]
                        ];
                    } else {
                        log_message('error', "Creating new draft for topic {$topic_id}");
                        
                        // Determine content and title from various possible response formats
                        $content = '';
                        $title = '';
                        
                        // Check for content in different possible locations
                        if (isset($data['content'])) {
                            $content = $data['content'];
                        } elseif (isset($data['section_content'])) {
                            $content = $data['section_content'];
                        } elseif (isset($data['draft_content'])) {
                            $content = $data['draft_content'];
                        } elseif (isset($data['html_content'])) {
                            $content = $data['html_content'];
                        }
                        
                        // Check for title in different possible locations
                        if (isset($data['title'])) {
                            $title = $data['title'];
                        } elseif (isset($data['section_title'])) {
                            $title = $data['section_title'];
                        } elseif (isset($data['draft_title'])) {
                            $title = $data['draft_title'];
                        } elseif (isset($data['post_title'])) {
                            $title = $data['post_title'];
                        }
                        
                        // Get topic title as fallback
                        if (empty($title)) {
                            $this->db->select('topictitle');
                            $this->db->where('id', $topic_id);
                            $topic = $this->db->get(db_prefix() . 'topics')->row();
                            $title = $topic ? $topic->topictitle : 'Draft from workflow';
                        }
                        
                        // Tạo draft mới
                        $draft_data = [
                            'topic_id' => $topic_id,
                            'draft_title' => $title ?: 'Draft from workflow',
                            'draft_content' => $content ?: '',
                            'status' => 'draft',
                            'version' => 1,
                            'created_by' => get_staff_user_id(),
                            'last_edited_by' => get_staff_user_id()
                        ];
                        
                        // Metadata
                        $draft_metadata = [];
                        if (isset($data['meta_description'])) {
                            $draft_metadata['meta_description'] = $data['meta_description'];
                        }
                        
                        if (isset($data['keywords'])) {
                            $draft_metadata['keywords'] = $data['keywords'];
                        }
                        
                        if (!empty($draft_metadata)) {
                            $draft_data['draft_metadata'] = json_encode($draft_metadata);
                        }
                        
                        $draft_id = $this->Topic_editor_draft_model->add($draft_data);
                        $this->Topic_editor_draft_model->set_active_draft($topic_id, $draft_id);
                        
                        $response = [
                            'success' => true,
                            'message' => _l('new_draft_created_from_workflow'),
                            'data' => [
                                'draft_id' => $draft_id,
                                'draft_content' => $draft_data['draft_content'],
                                'draft_title' => $draft_data['draft_title'],
                                'meta_description' => $draft_metadata['meta_description'] ?? '',
                                'keywords' => $draft_metadata['keywords'] ?? '',
                                'version' => 1,
                                'status' => 'draft',
                                'execution_data' => $data
                            ]
                        ];
                    }
                } else if ($status === 'running') {
                    log_message('error', "Workflow execution still running");
                    $response = [
                        'success' => true,
                        'status' => 'running',
                        'message' => _l('workflow_still_running'),
                        'data' => [
                            'progress' => $execution_status['data']['progress'] ?? 0
                        ]
                    ];
                } else {
                    log_message('error', "Workflow execution failed with status: {$status}");
                    throw new Exception(_l('workflow_failed_or_cancelled'));
                }
            } else {
                log_message('error', "Error checking execution status: " . ($execution_status['message'] ?? 'Unknown error'));
                throw new Exception($execution_status['message'] ?? _l('error_checking_execution_status'));
            }
            
            echo json_encode($response);
            
        } catch (Exception $e) {
            log_message('error', "Error in get_draft_content: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Kiểm tra trạng thái workflow
     * @return void
     */
    public function check_workflow_status()
    {
        // Get parameters
        $workflow_id = $this->input->get('workflow_id');
        $execution_id = $this->input->get('execution_id');
        $topic_id = $this->input->get('topic_id');
        
        // Validate required parameters
        if (!$workflow_id || !$execution_id) {
            echo json_encode([
                'success' => false,
                'message' => _l('missing_required_parameters'),
                'data' => [
                    'error_details' => 'Missing workflow_id or execution_id'
                ]
            ]);
            return;
        }
        
        
        // Check if we have a specific processor for this action
        if (class_exists('UltimateEditorImageGenerateToggleProcessor')) {
            $processor = new UltimateEditorImageGenerateToggleProcessor();
            // Pass topic_id if available
            $result = $processor->checkWorkflowStatus($workflow_id, $execution_id, $topic_id);
        } else {
            // Use a generic workflow status check
            $result = [
                'success' => true,
                'message' => _l('checking_workflow_status'),
                'data' => [
                    'workflow_id' => $workflow_id,
                    'execution_id' => $execution_id
                ],
                'finished' => false
            ];
            
            // Call generic endpoint to check status
            $url = 'https://automate.chantroituonglai.com/webhook/ACTION_BUTTONS_GET_WORKFLOWS';
            
            // Create data array - only add topic_id if available
            $data = [
                'workflow_id' => $workflow_id,
                'execution_id' => $execution_id
            ];
            
            if ($topic_id) {
                $data['topic_id'] = $topic_id;
            }
            
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Accept: application/json'
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($http_code == 200) {
                $data = json_decode($response, true);
                $result['finished'] = isset($data['finished']) ? $data['finished'] : false;
                $result['data'] = $data;
            }
        }
        
        echo json_encode($result);
    }
    /**
     * Get Topic Controllers
     * 
     * @return json
     */
    public function get_topic_controllers()
    {
        $this->load->model('Topic_controller_model');
       // $this->load->helper('topic_platform_helper');
        
        $controllers = $this->Topic_controller_model->get();
        $result = [];
        
        foreach ($controllers as $controller) {
            // Convert object to array if needed
            if (is_object($controller)) {
                $controller = (array)$controller;
            }
            
            // Check if controller data has the required fields
            if (!isset($controller['id']) || !isset($controller['platform'])) {
                continue;
            }
            
            // Use site name as controller name
            $name = !empty($controller['site']) ? $controller['site'] : '';
            
            // If site is empty, use platform name and ID
            if (empty($name)) {
                $name = ucfirst($controller['platform']) . ' #' . $controller['id'];
            }
            
            try {
                $connection_status = function_exists('test_platform_connection') 
                    ? test_platform_connection($controller['id']) 
                    : ['success' => false, 'message' => 'Connection test function not available'];
                    
                $connected = isset($connection_status['success']) ? $connection_status['success'] : false;
            } catch (Exception $e) {
                $connected = false;
                log_activity('Error checking connection for controller ID ' . $controller['id'] . ': ' . $e->getMessage());
            }
            
            $result[] = [
                'id' => $controller['id'],
                'name' => $name,
                'platform' => $controller['platform'],
                'site' => $controller['site'] ?? '',
                'blog_id' => $controller['blog_id'] ?? '',
                'connected' => $connected
            ];
        }
        
        echo json_encode(['success' => true, 'data' => $result]);
    }

    /**
     * Get Categories From Platform
     * 
     * @param int $controller_id
     * @return json
     */
    public function get_platform_categories($controller_id)
    {
       // $this->load->helper('topic_platform_helper');
        $categories = get_platform_categories($controller_id);
        
        if (isset($categories['categories'])) {
            echo json_encode(['success' => true, 'data' => $categories['categories']]);
        } else {
            echo json_encode(['success' => false, 'data' => [], 'message' => 'Failed to load categories']);
        }
    }

       /**
     * Get Tags From Platform
     * 
     * @param int $controller_id
     * @param bool $popular_only - Whether to return only popular tags
     * @return json
     */
    public function get_platform_tags($controller_id, $popular_only = false)
    {
        // $this->load->helper('topic_platform_helper'); // Assuming helper is loaded elsewhere or autoloaded
        $this->load->model('Topic_controller_model');
        
        $controller = $this->Topic_controller_model->get($controller_id);
        if (!$controller) {
            echo json_encode(['success' => false, 'data' => [], 'message' => _l('controller_not_found')]);
            return;
        }
        
        // Parse login configuration from JSON
        $login_config = [];
        if (!empty($controller->login_config)) {
            // Ensure json_decode handles potential errors gracefully
            $decoded_config = json_decode($controller->login_config, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $login_config = $decoded_config;
            } else {
                // Log error or handle invalid JSON if necessary
                log_activity('Invalid JSON in login_config for controller ID ' . $controller_id);
            }
        }
        
        // Add platform to login_config for helper function
        $login_config['platform'] = $controller->platform;
        
        // Call the helper function with proper parameters
        // The helper function `get_platform_tags` is expected to return an array,
        // potentially including keys like 'success', 'data', 'message'.
        $tags_result = get_platform_tags($login_config, $controller->blog_id);
        
        // Check if the helper function returned a valid result and indicated success
        if ($tags_result !== false && isset($tags_result['success']) && $tags_result['success'] === true) {

            // Extract the actual data payload from the helper's response
            $tag_data = $tags_result['data'] ?? [];
            $message = $tags_result['message'] ?? _l('tags_loaded_successfully'); // Use helper message or default

            // BEGIN MODIFICATION: Clean tag names before sending to frontend
            if (isset($tag_data['tags']) && is_array($tag_data['tags'])) {
                $cleaned_tags = [];
                foreach ($tag_data['tags'] as $tag) {
                    if (isset($tag['name']) && is_string($tag['name'])) {
                        // 1. Decode HTML entities (like &amp;, &quot;)
                        $cleaned_name = htmlspecialchars_decode($tag['name'], ENT_QUOTES | ENT_HTML5);
                        // 2. Strip any remaining HTML/XML tags
                        $cleaned_name = strip_tags($cleaned_name);
                        // 3. Trim whitespace
                        $tag['name'] = trim($cleaned_name);

                        // Only add tags with non-empty names after cleaning
                        if (!empty($tag['name'])) {
                             // Ensure count is an integer, default to 0 if not set or invalid
                            $tag['count'] = isset($tag['count']) ? (int)$tag['count'] : 0;
                            $cleaned_tags[] = $tag;
                        }
                    } else if (is_array($tag) && !isset($tag['name'])) {
                        // If it's an array but missing 'name', keep it but ensure count is set
                        $tag['count'] = isset($tag['count']) ? (int)$tag['count'] : 0;
                        $cleaned_tags[] = $tag; // Keep tag structure even if name is missing
                    }
                    // Ignore tags that are not arrays or don't have a name key if strict filtering is desired
                }
                // Replace original tags with cleaned tags
                $tag_data['tags'] = $cleaned_tags;
            }
            // END MODIFICATION

            // Prepare the base response structure using the cleaned $tag_data
            $response = [
                'success' => true,
                'data' => $tag_data, // This now contains the cleaned 'tags', 'total', etc.
                'message' => $message
            ];

            // If popular_only is requested, calculate and add popular tags
            // The parameter comes from the URL, so it might be a string 'true'
            if ($popular_only === 'true' || $popular_only === true) {
                $popular_tags = [];

                // Make sure we have tags and the 'tags' key exists within the cleaned data
                if (isset($tag_data['tags']) && is_array($tag_data['tags']) && !empty($tag_data['tags'])) {
                    // Use the already cleaned tags
                    $all_tags = $tag_data['tags'];

                    // Sort tags by count in descending order
                    // The 'count' key is guaranteed to be an integer due to cleaning above
                    usort($all_tags, function($a, $b) {
                        // Use null coalescing just in case, though count should be set
                        return ($b['count'] ?? 0) - ($a['count'] ?? 0);
                    });

                    // Get the top 20 tags (or fewer if less than 20 exist)
                    $popular_tags = array_slice($all_tags, 0, 20);

                    // Add popular tags to the response
                    $response['popular_tags'] = $popular_tags;
                } else {
                     // If 'tags' key is missing, not an array, or empty after cleaning, provide empty popular tags
                    $response['popular_tags'] = [];
                }
            }
            // Output the final JSON response
            echo json_encode($response);

        } else {
            // Handle failure case: helper returned false or success was false
            $error_message = isset($tags_result['message']) ? $tags_result['message'] : _l('failed_to_load_tags');
            echo json_encode(['success' => false, 'data' => [], 'message' => $error_message]);
        }
    }
    
    /**
     * Publish To Platform
     * 
     * @return json
     */
    public function publish_to_platform()
    {
       // $this->load->helper('topic_platform_helper');
        
        $controller_id = $this->input->post('controller_id');
        $topic_id = $this->input->post('topic_id');
        $title = $this->input->post('title');
        $content = $this->input->post('content');
        $excerpt = $this->input->post('excerpt');
        $categories = $this->input->post('categories');
        $tags = $this->input->post('tags');
        $status = $this->input->post('status');
        $schedule_time = $this->input->post('schedule_time');
        
        if (!$controller_id || !$topic_id || !$title || !$content) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            return;
        }
        
        // Ensure categories and tags are in the correct format
        if (!is_array($categories) && !empty($categories)) {
            $categories = explode(',', $categories);
        } else if (!is_array($categories)) {
            $categories = [];
        }
        
        if (!is_array($tags) && !empty($tags)) {
            $tags = explode(',', $tags);
        } else if (!is_array($tags)) {
            $tags = [];
        }
        
        $post_data = [
            'title' => $title,
            'content' => $content,
            'excerpt' => $excerpt,
            'categories' => $categories,
            'tags' => $tags,
            'status' => $status,
            'schedule_time' => $schedule_time
        ];
        
        $result = publish_platform_post($controller_id, $post_data);
        
        if ($result['success']) {
            // Update topic with published info
            $this->load->model('Topics_model');
            $this->Topics_model->update($topic_id, [
                'last_published' => date('Y-m-d H:i:s'),
                'published_url' => isset($result['permalink']) ? $result['permalink'] : '',
                'published_status' => 1
            ]);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Content published successfully',
                'data' => [
                    'permalink' => isset($result['permalink']) ? $result['permalink'] : '',
                    'post_id' => isset($result['post_id']) ? $result['post_id'] : ''
                ]
            ]);
            return; // Add return statement to prevent executing the else block
        } else {
            echo json_encode(['success' => false, 'message' => $result['message'] ?? 'Failed to publish content']);
        }
    }

    private function prepare_workflow_data($topic_id) {
        // Get workflow configuration from POST data
        $workflow_id = $this->input->post('workflow_id');
        $target_type = $this->input->post('target_type');
        $target_state = $this->input->post('target_state');
        $action_command = $this->input->post('action_command') ?? 'WRITE_DRAFT';
        
        // Retrieve topic details if available
        $topic = $this->db->get_where(db_prefix().'topics', ['id' => $topic_id])->row();
        
        $action_data = [
            'workflow_id'   => $workflow_id,
            'target_type'   => $target_type,
            'target_state'  => $target_state,
            'audit_step'    => 1,
            'action_command'=> $action_command
        ];
        
        return [
            'workflow_data' => $action_data,
            'topic'         => is_object($topic) ? (array)$topic : ['id' => $topic_id],
            'execution_data'=> [
                'timestamp' => date('Y-m-d H:i:s'),
                'user_id'   => get_staff_user_id(),
                'source'    => $target_type,
                'audit_step'=> 1
            ]
        ];
    }

    /**
     * Check if post with similar title exists on the platform
     * 
     * @return json
     */
    public function check_post_existence()
    {
        header('Content-Type: application/json');
        
        $controller_id = $this->input->post('controller_id');
        $title = $this->input->post('title');
        
        if (!$controller_id || !$title) {
            echo json_encode([
                'success' => false, 
                'message' => 'Thiếu thông tin controller_id hoặc title'
            ]);
            return;
        }
        
        // Load topic controller model and topic platform helper
        $this->load->model('Topic_controller_model');
       // $this->load->helper('topic_platform_helper');
        
        try {
            // Get controller details
            $controller = $this->Topic_controller_model->get($controller_id);
            if (!$controller) {
                throw new Exception('Không tìm thấy controller');
            }
            
            // Get connector based on platform
            $connector = get_platform_connector($controller->platform);
            if (!$connector) {
                throw new Exception('Không hỗ trợ nền tảng ' . $controller->platform);
            }
            
            // Parse login configuration from JSON
            $login_config = [];
            if (!empty($controller->login_config)) {
                $login_config = json_decode($controller->login_config, true);
            }
            
            // Configuration for API connection
            $config = [
                'url' => $controller->site, // Use 'site' field instead of 'site_url'
                'username' => isset($login_config['username']) ? $login_config['username'] : '',
                'password' => isset($login_config['password']) ? $login_config['password'] : '',
                'api_key' => isset($login_config['api_key']) ? $login_config['api_key'] : ''
            ];
            
            // Check if post exists
            $result = $connector->check_post_exists($config, $title, $controller->blog_id);
            
            echo json_encode([
                'success' => true,
                'exists' => $result['exists'],
                'permalink' => $result['exists'] ? $result['permalink'] : '',
                'similarity' => $result['similarity'] ?? 0
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get action buttons for Ultimate Editor by controller ID
     * This is a replacement for topics/controllers/get_action_buttons
     * to provide Ultimate Editor specific buttons
     * 
     * @param int $controller_id Controller ID
     * @return json Action buttons data
     */
    public function get_controller_action_buttons($controller_id = null)
    {
        // Check if user has permission
        if (!has_permission('topics', '', 'view')) {
            ajax_access_denied();
        }

        // If controller_id is not provided directly, try to get from request
        if (!$controller_id) {
            $controller_id = $this->input->get('controller_id');
        }

        // Validate controller_id
        if (!$controller_id) {
            echo json_encode([
                'success' => false,
                'message' => _l('controller_id_required'),
                'buttons' => []
            ]);
            return;
        }

        // Load required models
        $this->load->model('Topic_controller_model');
        $this->load->model('Topic_controller_action_button_model');
        
        // Get controller information
        $controller = $this->Topic_controller_model->get($controller_id);
        if (!$controller) {
            echo json_encode([
                'success' => false,
                'message' => _l('controller_not_found'),
                'buttons' => []
            ]);
            return;
        }

        // Get action buttons for this controller (including controller_only buttons)
        $buttons = $this->Topic_controller_action_button_model->get_action_buttons_by_controller($controller_id);
        
        // Format buttons for Ultimate Editor
        $formatted_buttons = [];
        foreach ($buttons as $button) {
            // Format each button
            $formatted_button = [
                'id' => $button['id'],
                'name' => $button['name'],
                'display_name' => $button['display_name'] ?: $button['name'],
                'class' => $button['button_class'] ?: 'btn btn-sm btn-primary',
                'target_type' => $button['target_action_type'],
                'target_state' => $button['target_action_state'],
                'action_command' => $button['action_command'],
                'workflow_id' => $button['workflow_id'],
                'controller_id' => $controller_id,
                'controller_platform' => $controller->platform,
                'controller_name' => $controller->site,
                'active' => $button['status'],
                'trigger_type' => $button['trigger_type'] ?: 'workflow',
                'icon' => $button['icon'] ?: 'fa fa-cog',
                'order' => $button['button_order'] ?: 999,
                'description' => $button['description']
            ];
            
            $formatted_buttons[] = $formatted_button;
        }
        
        // Sort buttons by order
        usort($formatted_buttons, function($a, $b) {
            return $a['order'] - $b['order'];
        });
        
        // Return formatted buttons
        echo json_encode([
            'success' => true,
            'controller' => [
                'id' => $controller->id,
                'name' => $controller->site,
                'platform' => $controller->platform
            ],
            'buttons' => $formatted_buttons
        ]);
    }

    /**
     * Process action from Ultimate Editor
     * This function handles requests from ultimate_editor_actionButtons.js
     * 
     * @return json Response data
     */
    public function process_action()
    {
        // Check if user has permission
        if (!has_permission('topics', '', 'view')) {
            ajax_access_denied();
        }

        // Get input parameters
        $topic_id = $this->input->post('topic_id'); // Optional - can be null
        $target_type = $this->input->post('target_type');
        $target_state = $this->input->post('target_state');
        $action_command = $this->input->post('action_command');
        $workflow_id = $this->input->post('workflow_id');
        $button_id = $this->input->post('button_id');
        $controller_id = $this->input->post('controller_id');
        
        // Get content data (added)
        $content = $this->input->post('content');
        $thumbnail = $this->input->post('thumbnail'); // New thumbnail object
        $publish_options = $this->input->post('publish_options');
        $categories = $this->input->post('categories');
        $tags = $this->input->post('tags');
        $additional_data = $this->input->post('additional_data');

        // Log detailed information on content, categories, tags
        $detailed_log = [
            'topic_id' => $topic_id,
            'target_type' => $target_type,
            'target_state' => $target_state,
            'action_command' => $action_command,
            'controller_id' => $controller_id
        ];
        
        // Log content details
        if (!empty($content)) {
            $detailed_log['content'] = [
                'title' => isset($content['title']) ? $content['title'] : null,
                'excerpt_length' => isset($content['excerpt']) ? strlen($content['excerpt']) : 0,
                'content_length' => isset($content['content']) ? strlen($content['content']) : 0,
            ];
        }
        
        // Log thumbnail details
        if (!empty($thumbnail)) {
            $detailed_log['thumbnail'] = [
                'has_url' => !empty($thumbnail['url']),
                'has_id' => !empty($thumbnail['id']),
                'url_length' => isset($thumbnail['url']) ? strlen($thumbnail['url']) : 0
            ];
        } else {
            $detailed_log['thumbnail'] = [
                'status' => 'not_provided',
            ];
        }
        
        // Log publish options
        if (!empty($publish_options)) {
            $detailed_log['publish_options'] = $publish_options;
        }
        
        // Log categories details
        if (!empty($categories)) {
            $detailed_log['categories'] = [
                'count' => count($categories),
                'first_few' => array_slice($categories, 0, 3) // Log first 3 categories
            ];
        }
        
        // Log tags details
        if (!empty($tags)) {
            $detailed_log['tags'] = [
                'count' => count($tags),
                'first_few' => array_slice($tags, 0, 5) // Log first 5 tags
            ];
        }
        
        log_message('error', 'Ultimate Editor action request (detailed): ' . json_encode($detailed_log));
        
        // Log basic request info
        $log_data = [
            'topic_id' => $topic_id,
            'target_type' => $target_type,
            'target_state' => $target_state,
            'action_command' => $action_command,
            'workflow_id' => $workflow_id,
            'controller_id' => $controller_id,
            'has_content' => !empty($content),
            'has_thumbnail' => !empty($thumbnail),
            'has_publish_options' => !empty($publish_options),
            'has_categories' => !empty($categories),
            'has_tags' => !empty($tags),
            'has_additional_data' => !empty($additional_data)
        ];
        log_message('error', 'Ultimate Editor action request: ' . json_encode($log_data));

        // Validate required parameters - topic_id is optional
        if (!$target_type || !$target_state) {
            echo json_encode([
                'success' => false,
                'message' => _l('missing_required_parameters'),
                'data' => [
                    'error_details' => 'Missing one or more required parameters: target_type, target_state'
                ]
            ]);
            return;
        }
        
        // If controller_id is required but not provided
        if ($this->_action_requires_controller($target_type, $target_state) && !$controller_id) {
            echo json_encode([
                'success' => false,
                'message' => _l('controller_required'),
                'data' => [
                    'needs_controller' => true,
                    'error_details' => 'This action requires a controller to be selected'
                ]
            ]);
            return;
        }

        // If topic_id is required for this action type but not provided
        if ($this->_action_requires_topic($target_type, $target_state) && !$topic_id) {
            echo json_encode([
                'success' => false,
                'message' => _l('topic_required'),
                'data' => [
                    'needs_topic' => true,
                    'error_details' => 'This action requires a topic to be selected'
                ]
            ]);
            return;
        }

        // Process the action using factory pattern
        try {
            $result = $this->_process_action_by_type(
                $topic_id,
                $target_type,
                $target_state,
                $action_command,
                $workflow_id,
                $button_id,
                $controller_id,
                $content,
                $publish_options,
                $categories,
                $tags,
                $additional_data,
                $thumbnail // Pass the thumbnail as a separate parameter
            );
            
            echo json_encode($result);
            
        } catch (Exception $e) {
            // Handle exceptions
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => [
                    'error_details' => $e->getTraceAsString()
                ]
            ]);
        }
    }

    /**
     * Check if action requires a controller
     * 
     * @param string $target_type Action target type
     * @param string $target_state Action target state
     * @return boolean Whether controller is required
     */
    private function _action_requires_controller($target_type, $target_state)
    {
        // Add logic to determine if controller is required based on action type and state
        $requires_controller = [
            'WordPressPost',
            'SocialMediaPost',
            'DraftWriting'
        ];
        
        return in_array($target_type, $requires_controller);
    }
    
    /**
     * Check if action requires a topic
     * 
     * @param string $target_type Action target type
     * @param string $target_state Action target state
     * @return boolean Whether topic is required
     */
    private function _action_requires_topic($target_type, $target_state)
    {
        // Define actions that specifically require a topic
        $requires_topic = [
            'TopicComposer',
            'ImageGenerateToggle'
        ];
        
        // Some actions require a topic based on their state
        $state_specific_requirements = [
            'DraftWriting' => ['BuildPostStructure_A_Init']
        ];
        
        // Check if the action type requires a topic
        if (in_array($target_type, $requires_topic)) {
            return true;
        }
        
        // Check state-specific requirements
        if (isset($state_specific_requirements[$target_type]) && 
            in_array($target_state, $state_specific_requirements[$target_type])) {
            return true;
        }
        
        return false;
    }

    /**
     * Process action by type using Factory pattern
     * Similar to PHP TopicActionProcessorFactory::create method
     * 
     * @param int|null $topic_id Topic ID (optional)
     * @param string $target_type Action target type
     * @param string $target_state Action target state
     * @param string $action_command Action command
     * @param string $workflow_id Workflow ID
     * @param string $button_id Button ID
     * @param string $controller_id Controller ID
     * @param array $content Content data (title, content, excerpt, etc.)
     * @param array $publish_options Publishing options (status, visibility, schedule, etc.)
     * @param array $categories Selected categories
     * @param array $tags Selected tags
     * @param array $additional_data Additional data for processing
     * @return array Response data
     */
    private function _process_action_by_type($topic_id = null, $target_type, $target_state, $action_command, $workflow_id, $button_id, $controller_id, $content = null, $publish_options = null, $categories = null, $tags = null, $additional_data = null, $thumbnail = null)
    {
        // Log action processing with appropriate context
        if ($topic_id) {
            log_activity('Processing Ultimate Editor action - Type: ' . $target_type . ', State: ' . $target_state . ', Command: ' . $action_command, $topic_id);
        } else {
            log_activity('Processing Ultimate Editor action without topic - Type: ' . $target_type . ', State: ' . $target_state . ', Command: ' . $action_command);
        }
        
        // Initialize action data array
        $action_data = [
            'target_type' => $target_type,
            'target_state' => $target_state,
            'action_command' => $action_command,
            'workflow_id' => $workflow_id,
            'button_id' => $button_id,
            'controller_id' => $controller_id
        ];
        
        // Add topic_id if available
        if ($topic_id) {
            $action_data['topic_id'] = $topic_id;
        }
        
        // Add content data if available
        if ($content) {
            $action_data['content'] = $content;
        }
        
        // Add thumbnail data if available
        if ($thumbnail) {
            $action_data['thumbnail'] = $thumbnail;
        }
        
        // Add publish options if available
        if ($publish_options) {
            $action_data['publish_options'] = $publish_options;
        }
        
        // Add categories if available
        if ($categories) {
            $action_data['categories'] = $categories;
        }
        
        // Add tags if available
        if ($tags) {
            $action_data['tags'] = $tags;
        }
        
        // Add additional data if available
        if ($additional_data) {
            $action_data['additional_data'] = $additional_data;
        }
      
        // Use the factory to get the appropriate processor
        $processor = UltimateEditorActionProcessorFactory::create($target_type, $target_state, $action_command);
        
        // Process the action
        $result = process_ultimate_editor_action($topic_id, $action_data);
        
        // Update button status if needed
        if (isset($result['update_button_status']) && $button_id) {
            $this->load->model('topics_model');
            $this->topics_model->update_action_button_status($button_id, $result['update_button_status']);
        }
        
        return $result;
    }
    
    /**
     * Handle opening content from Draft Writer into Ultimate Editor
     * Receives data from Draft Writer and either previews content or opens it in edit mode
     * 
     * @return void
     */
    public function open()
    {
        // Get parameters from POST
        $topic_id = $this->input->post('topic_id');
        $controller_id = $this->input->post('controller_id');
        $title = $this->input->post('title');
        $description = $this->input->post('description');
        $content = $this->input->post('content');
        $tags = $this->input->post('tags');
        $category = $this->input->post('category');
        $mode = $this->input->post('mode') ?? 'edit'; // Default to edit mode
        
        // Check if topic exists
        $topic = $this->db->get_where('tbltopics', ['id' => $topic_id])->row();
        if (!$topic) {
            show_404();
            return;
        }
        
        // Format content properly if it's not already in the correct format
        if (!empty($content) && !is_object(json_decode($content))) {
            // Convert text content to Ultimate Editor JSON format
            $formatted_content = json_encode([
                'content' => [
                    ['type' => 'text', 'text' => $content]
                ]
            ]);
        } else {
            $formatted_content = $content;
        }
        
        // Find or create draft
        $active_draft = null;
        if (isset($topic->active_draft_id) && $topic->active_draft_id) {
            $active_draft = $this->db->get_where('tbltopic_editor_drafts', ['id' => $topic->active_draft_id])->row();
        }
        
        if (!$active_draft) {
            // Create new draft
            $draft_data = [
                'topic_id' => $topic_id,
                'draft_title' => $title ?: $topic->topictitle,
                'draft_content' => $formatted_content,
                'status' => 'draft',
                'version' => 1,
                'created_by' => get_staff_user_id(),
                'last_edited_by' => get_staff_user_id(),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            // Add metadata including description and tags
            if (!empty($description) || !empty($tags) || !empty($category)) {
                $metadata = [
                    'draft_description' => $description,
                    'draft_tags' => $tags,
                    'draft_category' => $category,
                    'controller_id' => $controller_id
                ];
                $draft_data['draft_metadata'] = json_encode($metadata);
            }
            
            $this->db->insert('tbltopic_editor_drafts', $draft_data);
            $draft_id = $this->db->insert_id();
            
            // Update topic's active draft
            $this->db->where('id', $topic_id);
            $this->db->update('tbltopics', ['active_draft_id' => $draft_id]);
            
            // Get the draft we just created
            $active_draft = $this->db->get_where('tbltopic_editor_drafts', ['id' => $draft_id])->row();
        } else if (!empty($title) || !empty($content) || !empty($description) || !empty($tags)) {
            // Update existing draft with new content from Draft Writer
            $draft_data = [];
            
            if (!empty($title)) {
                $draft_data['draft_title'] = $title;
            }
            
            if (!empty($content)) {
                $draft_data['draft_content'] = $formatted_content;
            }
            
            // Get current metadata
            $current_metadata = [];
            if ($active_draft->draft_metadata) {
                $current_metadata = json_decode($active_draft->draft_metadata, true);
                if (!is_array($current_metadata)) {
                    $current_metadata = [];
                }
            }
            
            // Update metadata
            $metadata_updated = false;
            if (!empty($description)) {
                $current_metadata['draft_description'] = $description;
                $metadata_updated = true;
            }
            
            if (!empty($tags)) {
                $current_metadata['draft_tags'] = $tags;
                $metadata_updated = true;
            }
            
            if (!empty($category)) {
                $current_metadata['draft_category'] = $category;
                $metadata_updated = true;
            }
            
            if (!empty($controller_id)) {
                $current_metadata['controller_id'] = $controller_id;
                $metadata_updated = true;
            }
            
            if ($metadata_updated) {
                $draft_data['draft_metadata'] = json_encode($current_metadata);
            }
            
            if (!empty($draft_data)) {
                $draft_data['updated_at'] = date('Y-m-d H:i:s');
                $draft_data['last_edited_by'] = get_staff_user_id();
                
                $this->db->where('id', $active_draft->id);
                $this->db->update('tbltopic_editor_drafts', $draft_data);
            }
        }
        
        // Redirect to editor
        if ($mode === 'preview') {
            // Add preview flag to URL
            redirect(admin_url('topics/ultimate_editor/index/' . $topic_id . '?preview=1'));
        } else {
            redirect(admin_url('topics/ultimate_editor/index/' . $topic_id));
        }
    }
} 