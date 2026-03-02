<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Writing Controller
 * 
 * Handles all Draft Writer functionality
 */
class Writing extends AdminController
{
    private $topic_id;
    private $topic;
    private $workflow_id;
    private $execution_id;

    /**
     * Class constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->model('topics_model');
        
        // Initialize get_keyword_analysis
        $this->topic_id = null;
        $this->topic = null;
        $this->workflow_id = null;
        $this->execution_id = null;
    }

    /**
     * Index method - displays the Draft Writer interface
     * 
     * @param int $topic_id Optional topic ID
     * @return void
     */
    public function index($topic_id = null)
    {
        if (!has_permission('topics', '', 'view')) {
            access_denied('topics');
        }

        if (!$topic_id && !$this->input->get('topic_id')) {
            show_404();
        }

        $this->topic_id = $topic_id ? $topic_id : $this->input->get('topic_id');
        $this->topic = $this->topics_model->get($this->topic_id);

        if (!$this->topic) {
            show_404();
        }

        // Get workflow info if available
        $this->workflow_id = $this->input->get('workflow_id');
        $this->execution_id = $this->input->get('execution_id');

        $data['title'] = _l('draft_writer') . ' - ' . $this->topic->name;
        $data['topic_id'] = $this->topic_id;
        $data['topic'] = $this->topic;
        $data['workflow_id'] = $this->workflow_id;
        $data['execution_id'] = $this->execution_id;

        // Load additional data for step 2
        $data['categories'] = $this->get_categories();
        $data['tags'] = $this->get_tags();
        $data['ai_templates'] = $this->get_ai_templates();

        $this->load->view('writing/index', $data);
    }

    /**
     * Get Draft Writer modal template
     * 
     * @return void
     */
    public function get_draft_writer_template()
    {
        // if (!has_permission('topics', '', 'view')) {
        //     ajax_access_denied();
        // }

        try {
            $data['topic_id'] = $this->input->get('topic_id');
            $data['workflow_id'] = $this->input->get('workflow_id');
            $data['execution_id'] = $this->input->get('execution_id');
            
            // Get initial content if available
            if ($data['topic_id']) {
                $topic = $this->topics_model->get($data['topic_id']);
                if ($topic && !empty($topic->data)) {
                    $data['initial_content'] = $this->prepare_initial_content($topic->data);
                }
            }
            
            // Load the modal template
            $this->load->view('includes/draftWriter/draft_writer_modal', $data);
        } catch (Exception $e) {
            log_activity('Error loading Draft Writer template: ' . $e->getMessage());
            echo '<div class="alert alert-danger">Error loading Draft Writer</div>';
        }
    }

    /**
     * Get Draft Writer toolbar template
     * 
     * @return void
     */
    public function get_draft_writer_toolbar()
    {
        // if (!has_permission('topics', '', 'view')) {
        //     ajax_access_denied();
        // }
        
        try {
            // Load the toolbar template
            $this->load->view('includes/draftWriter/draft_writer_toolbar');
        } catch (Exception $e) {
            log_activity('Error loading Draft Writer toolbar: ' . $e->getMessage());
            echo '<div class="alert alert-danger">Error loading toolbar</div>';
        }
    }

    /**
     * Get Draft Writer analysis panel template
     * 
     * @return void
     */
    public function get_draft_writer_analysis_panel()
    {
        // if (!has_permission('topics', '', 'view')) {
        //     ajax_access_denied();
        // }
        
        
        
        try {
            // Load the analysis panel template
            $this->load->view('includes/draftWriter/draft_writer_analysis_panel');
        } catch (Exception $e) {
            log_activity('Error loading Draft Writer analysis panel: ' . $e->getMessage());
            echo '<div class="alert alert-danger">Error loading analysis panel</div>';
        }
    }

    /**
     * Get Draft Writer editor panel template
     * 
     * @return void
     */
    public function get_draft_writer_editor_panel()
    {
        if (!has_permission('topics', '', 'view')) {
            ajax_access_denied();
        }
        
        try {
            // Load the editor panel template
            $this->load->view('includes/draftWriter/draft_writer_editor_panel');
        } catch (Exception $e) {
            log_activity('Error loading Draft Writer editor panel: ' . $e->getMessage());
            echo '<div class="alert alert-danger">Error loading editor panel</div>';
        }
    }

    /**
     * Publish draft - converts draft to published content
     * 
     * @return void
     */
    public function publish_draft()
    {
        // if (!has_permission('topics', '', 'edit')) {
        //     ajax_access_denied();
        // }

        try {
            $topic_id = $this->input->post('topic_id');
            $content = $this->input->post('content');
            $title = $this->input->post('title');
            $description = $this->input->post('description');
            $tags = $this->input->post('tags');
            $category = $this->input->post('category');

            if (!$topic_id) {
                throw new Exception(_l('missing_topic_id'));
            }

            // Basic validation
            if (empty($content)) {
                throw new Exception(_l('content_cannot_be_empty'));
            }

            if (empty($title)) {
                throw new Exception(_l('title_cannot_be_empty'));
            }

            // Prepare data for publishing
            $publish_data = [
                'topic_id' => $topic_id,
                'title' => $title,
                'description' => $description,
                'content' => $content,
                'tags' => $tags,
                'category' => $category,
                'published_at' => date('Y-m-d H:i:s'),
                'published_by' => get_staff_user_id()
            ];

            // Log the publishing attempt
            log_activity('Attempting to publish draft for Topic ID: ' . $topic_id);

            // For step 2, we'll prepare the response but not actually save to database yet
            $response = [
                'success' => true,
                'message' => _l('draft_published_successfully'),
                'data' => [
                    'topic_id' => $topic_id,
                    'publish_data' => $publish_data,
                    'reload' => true
                ]
            ];

            echo json_encode($response);

        } catch (Exception $e) {
            log_activity('Error publishing draft: ' . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * AI improve content - uses AI to improve selected content
     * 
     * @return void
     */
    public function ai_improve_content()
    {
        // if (!has_permission('topics', '', 'view')) {
        //     ajax_access_denied();
        // }

        try {
            $content = $this->input->post('content');
            $type = $this->input->post('type');
            $prompt = $this->input->post('prompt');
            $style = $this->input->post('style');
            $tone = $this->input->post('tone');

            if (empty($content)) {
                throw new Exception(_l('content_cannot_be_empty'));
            }

            // Log the improvement attempt
            log_activity('AI improve content request - Type: ' . $type . ', Style: ' . $style . ', Tone: ' . $tone);

            // For step 2, we'll return enhanced mock content based on style and tone
            $improved_content = $this->generate_improved_content($content, $type, $style, $tone);

            $response = [
                'success' => true,
                'content' => $improved_content,
                'stats' => [
                    'original_length' => strlen($content),
                    'improved_length' => strlen($improved_content),
                    'improvement_type' => $type,
                    'style_applied' => $style,
                    'tone_applied' => $tone
                ]
            ];

            echo json_encode($response);

        } catch (Exception $e) {
            log_activity('Error improving content: ' . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * AI fact check - uses AI to fact check selected content
     * 
     * @return void
     */
    public function ai_fact_check()
    {
        // if (!has_permission('topics', '', 'view')) {
        //     ajax_access_denied();
        // }

        try {
            $content = $this->input->post('content');
            $check_type = $this->input->post('check_type');

            if (empty($content)) {
                throw new Exception(_l('content_cannot_be_empty'));
            }

            // Log the fact check attempt
            log_activity('AI fact check request - Check Type: ' . $check_type);

            // For step 2, we'll return enhanced mock fact check results
            $results = $this->generate_fact_check_results($content, $check_type);

            $response = [
                'success' => true,
                'findings' => $results['findings'],
                'suggestedCorrection' => $results['correction'],
                'stats' => [
                    'total_claims' => count($results['findings']),
                    'accurate_claims' => count(array_filter($results['findings'], function($f) { return $f['accurate']; })),
                    'check_type' => $check_type
                ]
            ];

            echo json_encode($response);

        } catch (Exception $e) {
            log_activity('Error fact checking content: ' . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * AI search - uses AI to search for related information
     * 
     * @return void
     */
    public function ai_search()
    {
        // if (!has_permission('topics', '', 'view')) {
        //     ajax_access_denied();
        // }

        try {
            $query = $this->input->post('query');
            $search_type = $this->input->post('search_type');
            $limit = $this->input->post('limit') ?: 5;

            if (empty($query)) {
                throw new Exception(_l('search_query_cannot_be_empty'));
            }

            // Log the search attempt
            log_activity('AI search request - Query: ' . $query . ', Type: ' . $search_type);

            // For step 2, we'll return enhanced mock search results
            $results = $this->generate_search_results($query, $search_type, $limit);

            $response = [
                'success' => true,
                'results' => $results,
                'stats' => [
                    'total_results' => count($results),
                    'search_type' => $search_type,
                    'query' => $query
                ]
            ];

            echo json_encode($response);

        } catch (Exception $e) {
            log_activity('Error performing AI search: ' . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get keyword analysis - analyzes content for keyword density
     * 
     * @return void
     */
    public function get_keyword_analysis()
    {
        // if (!has_permission('topics', '', 'view')) {
        //     ajax_access_denied();
        // }

        try {
            $content = $this->input->post('content');
            $main_keywords = $this->input->post('main_keywords');

            if (empty($content)) {
                throw new Exception(_l('content_cannot_be_empty'));
            }

            // Perform keyword analysis
            $analysis = $this->analyze_keywords($content, $main_keywords);

            $response = [
                'success' => true,
                'analysis' => $analysis
            ];

            echo json_encode($response);

        } catch (Exception $e) {
            log_message('error', 'Error analyzing keywords: ' . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get SEO suggestions - provides SEO improvement suggestions
     * 
     * @return void
     */
    public function get_seo_suggestions()
    {
        try {
            $content = $this->input->post('content');
            $title = $this->input->post('title');
            $description = $this->input->post('description');
            $target_keyword = $this->input->post('target_keyword');

            // Perform SEO analysis
            $analysis = $this->analyze_seo($content, $title, $description, $target_keyword);

            $response = [
                'success' => true,
                'analysis' => $analysis
            ];

            echo json_encode($response);

        } catch (Exception $e) {
            log_activity('Error analyzing SEO: ' . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get available categories
     */
    private function get_categories()
    {
        // Mock categories for step 2
        return [
            ['id' => 1, 'name' => 'Technology'],
            ['id' => 2, 'name' => 'Business'],
            ['id' => 3, 'name' => 'Marketing']
        ];
    }

    /**
     * Get available tags
     */
    private function get_tags()
    {
        // Mock tags for step 2
        return [
            ['id' => 1, 'name' => 'AI'],
            ['id' => 2, 'name' => 'Writing'],
            ['id' => 3, 'name' => 'Content']
        ];
    }

    /**
     * Get AI templates
     */
    private function get_ai_templates()
    {
        // Mock AI templates for step 2
        return [
            [
                'id' => 1,
                'name' => 'Professional Blog Post',
                'description' => 'Creates a formal, informative blog post',
                'type' => 'blog'
            ],
            [
                'id' => 2,
                'name' => 'SEO-Optimized Article',
                'description' => 'Creates content optimized for search engines',
                'type' => 'seo'
            ]
        ];
    }

    /**
     * Prepare initial content from topic data
     */
    private function prepare_initial_content($data)
    {
        try {
            $content = json_decode($data, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid JSON data');
            }

            // Format content for editor
            return $this->format_content_for_editor($content);

        } catch (Exception $e) {
            log_activity('Error preparing initial content: ' . $e->getMessage());
            return '';
        }
    }

    /**
     * Format content for editor
     */
    private function format_content_for_editor($content)
    {
        // Basic formatting for step 2
        if (is_array($content)) {
            $html = '';
            foreach ($content as $item) {
                if (isset($item['title'])) {
                    $html .= '<h2>' . htmlspecialchars($item['title']) . '</h2>';
                }
                if (isset($item['content'])) {
                    $html .= '<p>' . htmlspecialchars($item['content']) . '</p>';
                }
            }
            return $html;
        }
        return htmlspecialchars($content);
    }

    /**
     * Generate improved content based on style and tone
     */
    private function generate_improved_content($content, $type, $style, $tone)
    {
        // Mock improvement for step 2
        $prefix = '';
        switch ($style) {
            case 'professional':
                $prefix = '[Professional Style] ';
                break;
            case 'casual':
                $prefix = '[Casual Style] ';
                break;
            default:
                $prefix = '[Standard Style] ';
        }

        switch ($tone) {
            case 'formal':
                $prefix .= '[Formal Tone] ';
                break;
            case 'friendly':
                $prefix .= '[Friendly Tone] ';
                break;
            default:
                $prefix .= '[Neutral Tone] ';
        }

        return $prefix . $content;
    }

    /**
     * Generate fact check results
     */
    private function generate_fact_check_results($content, $check_type)
    {
        // Mock fact check results for step 2
        return [
            'findings' => [
                [
                    'text' => 'Sample claim from content',
                    'accurate' => true,
                    'explanation' => 'This claim is verified by multiple sources',
                    'sources' => [
                        ['title' => 'Source 1', 'url' => 'https://example.com/1'],
                        ['title' => 'Source 2', 'url' => 'https://example.com/2']
                    ]
                ]
            ],
            'correction' => $content
        ];
    }

    /**
     * Generate search results
     */
    private function generate_search_results($query, $search_type, $limit)
    {
        // Mock search results for step 2
        $results = [];
        for ($i = 1; $i <= $limit; $i++) {
            $results[] = [
                'title' => "Search Result $i for: $query",
                'snippet' => "This is a sample search result snippet for $query with type: $search_type",
                'url' => "https://example.com/result$i",
                'relevance_score' => rand(70, 100)
            ];
        }
        return $results;
    }

    /**
     * Analyze keywords in content
     * 
     * @param string $content The content to analyze
     * @param string $main_keywords The main keywords separated by comma or semicolon
     * @return array Analysis results
     */
    private function analyze_keywords($content, $main_keywords) {
        log_message('error', 'Analyzing keywords: ' . $content . ' - ' . $main_keywords);
        // Chuẩn hóa nội dung
        $text = strip_tags($content);
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        
        // Tính tổng số từ
        $total_words = str_word_count($text);
        
        // Tách keywords
        $keywords = array_filter(
            array_map('trim', 
                preg_split('/[,;]/', $main_keywords)
            )
        );

        $results = [];
        $total_score = 0;
        $keyword_count = 0;
        
        foreach ($keywords as $keyword) {
            // Đếm số lần xuất hiện của keyword
            $count = preg_match_all('/\b' . preg_quote($keyword, '/') . '\b/ui', $text);
            
            // Tính keyword density
            $density = ($total_words > 0) ? ($count / $total_words) * 100 : 0;
            
            // Phân tích vị trí xuất hiện
            $positions = $this->analyze_keyword_positions($text, $keyword);
            
            // Đánh giá theo chuẩn Semrush
            $score = $this->calculate_keyword_score($density, $positions, $total_words);
            
            $results[$keyword] = [
                'keyword' => $keyword,
                'count' => $count,
                'density' => round($density, 2),
                'positions' => $positions,
                'score' => $score,
                'recommendations' => $this->get_keyword_recommendations($density, $positions)
            ];

            // Cập nhật tổng điểm và số lượng từ khóa cho average_score
            $total_score += $score;
            $keyword_count++;
        }

        // Tính average_score theo chuẩn Semrush
        $average_score = $keyword_count > 0 ? round($total_score / $keyword_count) : 0;

        return [
            'total_words' => $total_words,
            'keywords' => $results,
            'average_score' => $average_score // Thêm average_score vào kết quả
        ];
    }

    /**
     * Analyze keyword positions in content
     */
    private function analyze_keyword_positions($text, $keyword) {
        $positions = [
            'title' => false,
            'first_paragraph' => false, 
            'headings' => 0,
            'first_100_words' => false,
            'last_100_words' => false,
            'distribution' => []
        ];

        // Check title
        if (preg_match('/<h1[^>]*>.*?' . preg_quote($keyword, '/') . '.*?<\/h1>/ui', $text)) {
            $positions['title'] = true;
        }

        // Check first paragraph
        $first_p = '';
        if (preg_match('/<p[^>]*>(.*?)<\/p>/ui', $text, $matches)) {
            $first_p = $matches[1];
            if (preg_match('/\b' . preg_quote($keyword, '/') . '\b/ui', $first_p)) {
                $positions['first_paragraph'] = true;
            }
        }

        // Check headings
        $positions['headings'] = preg_match_all('/<h[1-6][^>]*>.*?' . preg_quote($keyword, '/') . '.*?<\/h[1-6]>/ui', $text);

        // Check first/last 100 words
        $words = explode(' ', strip_tags($text));
        $first_100 = implode(' ', array_slice($words, 0, 100));
        $last_100 = implode(' ', array_slice($words, -100));

        $positions['first_100_words'] = preg_match('/\b' . preg_quote($keyword, '/') . '\b/ui', $first_100);
        $positions['last_100_words'] = preg_match('/\b' . preg_quote($keyword, '/') . '\b/ui', $last_100);

        // Analyze distribution
        $text_length = strlen($text);
        $segment_size = floor($text_length / 4); // Chia thành 4 phần

        for ($i = 0; $i < 4; $i++) {
            $segment = substr($text, $i * $segment_size, $segment_size);
            $positions['distribution'][$i] = preg_match_all('/\b' . preg_quote($keyword, '/') . '\b/ui', $segment);
        }

        return $positions;
    }

    /**
     * Calculate keyword score based on Semrush standards
     */
    private function calculate_keyword_score($density, $positions, $total_words) {
        $score = 100;
        
        // Đánh giá mật độ từ khóa (Semrush recommends 1-3%)
        if ($density < 0.5) {
            $score -= 20;
        } elseif ($density > 3) {
            $score -= 15;
        }

        // Đánh giá vị trí xuất hiện
        if (!$positions['title']) {
            $score -= 10;
        }
        if (!$positions['first_paragraph']) {
            $score -= 10;
        }
        if ($positions['headings'] == 0) {
            $score -= 10;
        }
        if (!$positions['first_100_words']) {
            $score -= 5;
        }

        // Đánh giá phân bố
        $distribution = $positions['distribution'];
        $has_poor_distribution = false;
        foreach ($distribution as $segment_count) {
            if ($segment_count == 0) {
                $has_poor_distribution = true;
                break;
            }
        }
        if ($has_poor_distribution) {
            $score -= 10;
        }

        // Đánh giá độ dài nội dung
        if ($total_words < 300) {
            $score -= 15;
        }

        return max(0, min(100, $score));
    }

    /**
     * Get keyword optimization recommendations
     */
    private function get_keyword_recommendations($density, $positions) {
        $recommendations = [];

        if ($density < 0.5) {
            $recommendations[] = 'keyword_density_too_low';
        } elseif ($density > 3) {
            $recommendations[] = 'keyword_density_too_high';
        }

        if (!$positions['title']) {
            $recommendations[] = 'add_keyword_to_title';
        }
        if (!$positions['first_paragraph']) {
            $recommendations[] = 'add_keyword_to_first_paragraph';
        }
        if ($positions['headings'] == 0) {
            $recommendations[] = 'add_keyword_to_headings';
        }
        if (!$positions['first_100_words']) {
            $recommendations[] = 'add_keyword_to_beginning';
        }

        $has_poor_distribution = false;
        foreach ($positions['distribution'] as $segment_count) {
            if ($segment_count == 0) {
                $has_poor_distribution = true;
                break;
            }
        }
        if ($has_poor_distribution) {
            $recommendations[] = 'improve_keyword_distribution';
        }

        return $recommendations;
    }

    /**
     * Analyze SEO aspects of content
     * 
     * @param string $content The content to analyze
     * @param string $title The title to analyze
     * @param string $description The meta description to analyze
     * @param string $target_keyword The target keyword to check
     * @return array SEO analysis results
     */
    private function analyze_seo($content, $title, $description, $target_keyword = '')
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
        
        // Check description
        if (empty($description)) {
            $suggestions[] = [
                'type' => 'error',
                'text' => _l('seo_error_no_description')
            ];
            $score -= 20;
        } else {
            $description_length = mb_strlen($description, 'UTF-8');
            
            if ($description_length < 120) {
                $suggestions[] = [
                    'type' => 'warning',
                    'text' => _l('seo_warning_description_short')
                ];
                $score -= 10;
            } else if ($description_length > 160) {
                $suggestions[] = [
                    'type' => 'warning',
                    'text' => _l('seo_warning_description_long')
                ];
                $score -= 10;
            } else {
                $suggestions[] = [
                    'type' => 'good',
                    'text' => _l('seo_good_description_length')
                ];
            }
            
            // Check if description contains target keyword
            if (!empty($target_keyword) && stripos($description, $target_keyword) !== false) {
                $suggestions[] = [
                    'type' => 'good',
                    'text' => _l('seo_good_keyword_in_description')
                ];
            } else if (!empty($target_keyword)) {
                $suggestions[] = [
                    'type' => 'warning',
                    'text' => _l('seo_warning_keyword_not_in_description')
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
        
        if (!$has_h1 && !$has_h2) {
            $suggestions[] = [
                'type' => 'error',
                'text' => _l('seo_error_no_headings')
            ];
            $score -= 15;
        } else if (!$has_h1) {
            $suggestions[] = [
                'type' => 'warning',
                'text' => _l('seo_warning_no_h1')
            ];
            $score -= 10;
        } else if (!$has_h2) {
            $suggestions[] = [
                'type' => 'warning',
                'text' => _l('seo_warning_no_h2')
            ];
            $score -= 5;
        } else {
            $suggestions[] = [
                'type' => 'good',
                'text' => _l('seo_good_heading_structure')
            ];
        }
        
        // Check keyword density
        $keyword_density = 0;
        if (!empty($target_keyword) && $word_count > 0) {
            $keyword_count = substr_count(strtolower($text), strtolower($target_keyword));
            $keyword_density = ($keyword_count / $word_count) * 100;
            
            if ($keyword_density < 0.5) {
                $suggestions[] = [
                    'type' => 'warning',
                    'text' => _l('seo_warning_keyword_density_low')
                ];
                $score -= 5;
            } else if ($keyword_density > 3) {
                $suggestions[] = [
                    'type' => 'warning',
                    'text' => _l('seo_warning_keyword_density_high')
                ];
                $score -= 5;
            } else {
                $suggestions[] = [
                    'type' => 'good',
                    'text' => _l('seo_good_keyword_density')
                ];
            }
        }
        
        // Check for images
        $image_count = preg_match_all('/<img[^>]*>/i', $content, $matches);
        if ($image_count == 0) {
            $suggestions[] = [
                'type' => 'warning',
                'text' => _l('seo_warning_no_images')
            ];
            $score -= 5;
        } else {
            $alt_count = preg_match_all('/<img[^>]*alt=["\'](.*?)["\']/i', $content, $matches);
            if ($alt_count < $image_count) {
                $suggestions[] = [
                    'type' => 'warning',
                    'text' => _l('seo_warning_images_missing_alt')
                ];
                $score -= 5;
            } else {
                $suggestions[] = [
                    'type' => 'good',
                    'text' => _l('seo_good_images_with_alt')
                ];
            }
        }
        
        // Check for links
        $internal_links = preg_match_all('/<a[^>]*href=["\'](https?:\/\/[^"\']*|\/[^"\']*)["\'][^>]*>/i', $content, $matches);
        if ($internal_links == 0 && $word_count > 300) {
            $suggestions[] = [
                'type' => 'warning',
                'text' => _l('seo_warning_no_links')
            ];
            $score -= 5;
        }
        
        // Ensure score is between 0-100
        $score = max(0, min(100, $score));
        
        return [
            'score' => $score,
            'suggestions' => $suggestions,
            'stats' => [
                'word_count' => $word_count,
                'title_length' => isset($title_length) ? $title_length : 0,
                'description_length' => isset($description_length) ? $description_length : 0,
                'keyword_density' => round($keyword_density, 2),
                'image_count' => $image_count,
                'internal_links' => $internal_links,
                'has_h1' => $has_h1,
                'has_h2' => $has_h2,
                'has_h3' => $has_h3
            ]
        ];
    }
} 