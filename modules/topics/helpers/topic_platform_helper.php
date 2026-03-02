<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Topic Platform Helper
 * 
 * Helper for connecting and interacting with various website platforms
 */

/**
 * Get platform connector instance
 * 
 * @param string $platform Platform name
 * @return PlatformConnectorInterface|null Platform connector instance or null if not found
 */
function get_platform_connector($platform)
{
    $platform = strtolower($platform);
    
    // Load the connector class based on platform
    // Check if we're in a module context with 'topics' module
    if (function_exists('module_dir_path')) {
        $connector_file = module_dir_path('topics') . 'includes/platform_connectors/' . $platform . '_connector.php';
    } else {
        // Fallback to direct path if module_dir_path is not available
        $connector_file = FCPATH . 'modules/topics/includes/platform_connectors/' . $platform . '_connector.php';
    }
    $connector_class = ucfirst($platform) . 'Connector';
    
    // Check if connector file exists
    if (!file_exists($connector_file)) {
        log_message('error', 'Platform connector not found: ' . $platform . ' (Path: ' . $connector_file . ')');
        return null;
    }
    
    // Load the connector file
    require_once($connector_file);
    
    // Check if connector class exists
    if (!class_exists($connector_class)) {
        log_message('error', 'Platform connector class not found: ' . $connector_class);
        return null;
    }
    
    // Create and return connector instance
    return new $connector_class();
}

/**
 * Get platform information
 * 
 * @param string $platform Platform name
 * @return array Platform information
 */
function get_platform_info($platform = null)
{
    $CI = &get_instance();
    
    // Get all platforms
    $platforms_json = get_option('topic_controller_platforms');
    $platforms = json_decode($platforms_json, true);
    
    if (!is_array($platforms)) {
        $platforms = [];
    }
    
    // Return specific platform if requested
    if ($platform !== null) {
        $platform = strtolower($platform);
        return isset($platforms[$platform]) ? $platforms[$platform] : null;
    }
    
    return $platforms;
}

/**
 * Get login fields for a platform
 * 
 * @param string $platform Platform name
 * @return array Login fields
 */
function get_platform_login_fields($platform)
{
    $platform_info = get_platform_info($platform);
    
    if (!$platform_info || !isset($platform_info['login_fields'])) {
        return [];
    }
    
    return $platform_info['login_fields'];
}

/**
 * Test connection to a platform
 * 
 * @param int $controller_id Controller ID
 * @return array Connection test result
 */
function test_platform_connection($controller_id)
{
    $CI = &get_instance();
    $CI->load->model('Topic_controller_model');
    
    // Get controller data
    $controller = $CI->Topic_controller_model->get($controller_id);
    
    if (!$controller) {
        return [
            'success' => false,
            'message' => 'Controller not found'
        ];
    }
    
    // Get platform and login config
    $platform = $controller->platform;
    $login_config = json_decode($controller->login_config, true);
    
    if (!$platform || !$login_config) {
        return [
            'success' => false,
            'message' => 'Missing platform or login configuration'
        ];
    }
    
    // Get platform connector
    $connector = get_platform_connector($platform);
    
    if (!$connector) {
        return [
            'success' => false,
            'message' => 'Platform connector not found: ' . $platform
        ];
    }
    
    // Test connection
    $result = $connector->testConnection($login_config);
    
    // Update login status
    $update_data = [
        'last_login' => date('Y-m-d H:i:s'),
        'login_status' => $result['success'] ? 1 : 2
    ];
    
    $CI->Topic_controller_model->update($controller_id, $update_data);
    
    // Make sure site_info is passed to the result if available
    if ($result['success'] && !isset($result['site_info'])) {
        // Try to get site info directly if not included in the result
        $site_info = $connector->getSiteInfo($login_config);
        if ($site_info) {
            $result['site_info'] = $site_info;
        }
    }
    
    return $result;
}

/**
 * Get categories from a platform
 * 
 * @param int $controller_id Controller ID
 * @param bool $force_refresh Force refresh from platform
 * @return array Categories
 */
function get_platform_categories($controller_id, $force_refresh = false)
{
    $CI = &get_instance();
    $CI->load->model('Topic_controller_model');
    
    // Get controller data
    $controller = $CI->Topic_controller_model->get($controller_id);
    
    if (!$controller) {
        return [
            'success' => false,
            'categories' => [],
            'message' => 'Controller not found'
        ];
    }
    
    // Check if we have categories in database and not forcing refresh
    if (!$force_refresh) {
        $categories = $CI->Topic_controller_model->get_categories($controller_id);
        
        if (!empty($categories)) {
            // Format categories for tree view
            $formatted_categories = [];
            foreach ($categories as $category) {
                $formatted_categories[] = [
                    'category_id' => $category['category_id'],
                    'parent_id' => $category['parent_id'],
                    'name' => $category['name'],
                    'slug' => $category['slug'],
                    'description' => $category['description'],
                    'count' => $category['count'],
                    'url' => $category['url'],
                    'image_url' => $category['image_url'],
                    'last_sync' => $category['last_sync']
                ];
            }
            
            return [
                'success' => true,
                'categories' => $formatted_categories,
                'message' => 'Categories loaded from database',
                'from_cache' => true
            ];
        }
    }
    
    // Get platform and login config
    $platform = $controller->platform;
    $login_config = json_decode($controller->login_config, true);
    
    if (!$platform || !$login_config) {
        return [
            'success' => false,
            'categories' => [],
            'message' => 'Missing platform or login configuration'
        ];
    }
    
    // Get platform connector
    $connector = get_platform_connector($platform);
    
    if (!$connector) {
        return [
            'success' => false,
            'categories' => [],
            'message' => 'Platform connector not found: ' . $platform
        ];
    }
    
    // Get categories from platform
    $result = $connector->getCategories($login_config);
    
    // If successful, save categories to database
    if ($result['success'] && !empty($result['categories'])) {
        foreach ($result['categories'] as $category) {
            $CI->Topic_controller_model->save_category($controller_id, $category);
        }
    }
    
    return $result;
}

/**
 * Publish a post to a platform
 * 
 * @param int $controller_id Controller ID
 * @param array $post Post data
 * @return array Publish result
 */
function publish_platform_post($controller_id, $post)
{
    $CI = &get_instance();
    $CI->load->model('Topic_controller_model');
    
    // Get controller data
    $controller = $CI->Topic_controller_model->get($controller_id);
    
    if (!$controller) {
        return [
            'success' => false,
            'post_id' => 0,
            'post_url' => '',
            'message' => 'Controller not found'
        ];
    }
    
    // Get platform and login config
    $platform = $controller->platform;
    $login_config = json_decode($controller->login_config, true);
    
    if (!$platform || !$login_config) {
        return [
            'success' => false,
            'post_id' => 0,
            'post_url' => '',
            'message' => 'Missing platform or login configuration'
        ];
    }
    
    // Get platform connector
    $connector = get_platform_connector($platform);
    
    if (!$connector) {
        return [
            'success' => false,
            'post_id' => 0,
            'post_url' => '',
            'message' => 'Platform connector not found: ' . $platform
        ];
    }
    
    // Publish post
    return $connector->publishPost($login_config, $post);
}

/**
 * Get writing styles
 * 
 * @return array Writing styles
 */
function get_writing_styles()
{
    $styles_json = get_option('topic_controller_writing_styles');
    $styles = json_decode($styles_json, true);
    
    if (!is_array($styles)) {
        $styles = [];
    }
    
    return $styles;
}

/**
 * Get writing tones
 * 
 * @return array Writing tones
 */
function get_writing_tones()
{
    $tones_json = get_option('topic_controller_writing_tones');
    $tones = json_decode($tones_json, true);
    
    if (!is_array($tones)) {
        $tones = [];
    }
    
    return $tones;
}

/**
 * Get writing criteria
 * 
 * @return array Writing criteria
 */
function get_writing_criteria()
{
    return [
        'seo_friendly' => 'SEO thân thiện',
        'keyword_rich' => 'Giàu từ khóa',
        'engaging' => 'Hấp dẫn người đọc',
        'concise' => 'Ngắn gọn, súc tích',
        'detailed' => 'Chi tiết, đầy đủ',
        'research_based' => 'Dựa trên nghiên cứu',
        'storytelling' => 'Kể chuyện',
        'actionable' => 'Thực tế, có thể áp dụng',
        'data_driven' => 'Dựa trên dữ liệu',
        'conversational' => 'Trò chuyện'
    ];
}

/**
 * Get tags from platform
 * 
 * @param array $login_config Login config array
 * @param int $blog_id Blog ID
 * @param int $per_page Số lượng tags trên mỗi trang
 * @return array|bool Tags array or false on error
 */
function get_platform_tags($login_config, $blog_id, $per_page = 100)
{
    // Get platform from login config
    $platform = isset($login_config['platform']) ? $login_config['platform'] : '';
    
    if (empty($platform)) {
        log_message('error', 'Platform not specified in login config for get_platform_tags');
        return false;
    }
    
    // Log the attempt
    log_message('info', 'Attempting to get tags from platform: ' . $platform . ' for blog ID: ' . $blog_id . ' with per_page: ' . $per_page);
    
    // Get connector for platform
    $connector = get_platform_connector($platform);
    if (!$connector) {
        log_message('error', 'Platform connector not found for: ' . $platform);
        return false;
    }
    
    // Log connector class
    log_message('info', 'Using connector class: ' . get_class($connector) . ' for platform: ' . $platform);
    
    // Check if the connector has the get_tags method
    if (!method_exists($connector, 'get_tags')) {
        log_message('error', 'The ' . get_class($connector) . ' connector does not have a get_tags method');
        return false;
    }
    
    // Try to get tags directly without checking login
    try {
        // Log the config (excluding sensitive data)
        $safe_config = $login_config;
        if (isset($safe_config['password'])) $safe_config['password'] = '***';
        if (isset($safe_config['application_password'])) $safe_config['application_password'] = '***';
        log_message('info', 'Calling get_tags with config: ' . json_encode($safe_config) . ' and blog ID: ' . $blog_id);
        
        // Thêm tham số per_page vào config
        $config = $login_config;
        $config['per_page'] = $per_page;
        
        $tags = $connector->get_tags($config, $blog_id);
        
        if ($tags === false) {
            log_message('error', 'Failed to get tags from platform: ' . $platform);
            return false;
        }
        
        // Log success and the first few tags for debugging
        $tag_count = is_array($tags) ? count($tags) : 0;
        log_message('info', 'Successfully retrieved ' . $tag_count . ' tags from platform: ' . $platform);
        
        if ($tag_count > 0) {
            // Log the first 3 tags as a sample
            $sample_tags = array_slice($tags, 0, 3);
            log_message('info', 'Sample tags: ' . json_encode($sample_tags));
            
            // Remove parent_id from tags if it exists
            foreach ($tags as &$tag) {
                if (isset($tag['parent_id'])) {
                    unset($tag['parent_id']);
                }
            }
        } else {
            log_message('error', 'No tags were returned from the platform, but the call was successful');
        }
        
        return $tags;
    } catch (Exception $e) {
        log_message('error', 'Exception when getting tags from platform: ' . $platform . ' - ' . $e->getMessage());
        return false;
    }
} 