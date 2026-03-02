<?php
defined('BASEPATH') or exit('No direct script access allowed');

// Use module_dir_path if available, otherwise fall back to direct path
if (function_exists('module_dir_path')) {
    require_once(module_dir_path('topics') . 'includes/platform_connectors/platform_connector_interface.php');
} else {
    require_once(FCPATH . 'modules/topics/includes/platform_connectors/platform_connector_interface.php');
}

/**
 * WordPress Connector
 * 
 * Connector for WordPress platform
 */
class WordPressConnector implements PlatformConnectorInterface
{
    /**
     * Test connection to WordPress
     * 
     * @param array $config Login configuration
     * @return array ['success' => bool, 'message' => string]
     */
    public function testConnection(array $config)
    {
        // Validate config
        $validation = $this->validateConfig($config);
        if (!$validation['success']) {
            return $validation;
        }
        
        // Prepare request URL
        $url = rtrim($config['url'], '/') . '/wp-json/wp/v2/categories';
        log_message('error', 'WordPress Connector - Test Connection URL: ' . $url);
        
        // Set up authentication - prioritize application password if available
        if (isset($config['application_password']) && !empty($config['application_password'])) {
            $auth = base64_encode($config['username'] . ':' . $config['application_password']);
            log_message('error', 'WordPress Connector - Using application password authentication');
        } else {
            $auth = base64_encode($config['username'] . ':' . $config['password']);
            log_message('error', 'WordPress Connector - Using regular password authentication');
        }
        
        // Set up cURL request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Basic ' . $auth,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        // Execute request
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        // Log raw response for debugging
        // log_message('error', 'WordPress Connector - Test Connection Raw Response: ' . substr($response, 0, 1000));
        log_message('error', 'WordPress Connector - Test Connection HTTP Code: ' . $httpCode);
        
        // Check for errors
        if ($error) {
            log_message('error', 'WordPress Connector - Test Connection Error: ' . $error);
            return [
                'success' => false,
                'message' => 'Connection error: ' . $error
            ];
        }
        
        // Check HTTP status code
        if ($httpCode >= 200 && $httpCode < 300) {
            // Fetch site information
            $siteInfo = $this->getSiteInfo($config);
            
            // Log site info for debugging
            log_message('error', 'WordPress Connector - Site Info: ' . json_encode($siteInfo));
            
            return [
                'success' => true,
                'message' => 'Connection successful',
                'site_info' => $siteInfo
            ];
        } else {
            $errorMessage = 'HTTP Error ' . $httpCode;
            
            // Try to parse error message from response
            if ($response) {
                $responseData = json_decode($response, true);
                if (isset($responseData['message'])) {
                    $errorMessage .= ': ' . $responseData['message'];
                }
                // Log error response data
                log_message('error', 'WordPress Connector - Error Response Data: ' . json_encode($responseData));
            }
            
            return [
                'success' => false,
                'message' => $errorMessage
            ];
        }
    }
    
    /**
     * Get basic information about the WordPress site
     * 
     * @param array $config Login configuration
     * @return array Site information
     */
    public function getSiteInfo(array $config)
    {
        $siteInfo = [
            'title' => '',
            'description' => '',
            'url' => rtrim($config['url'], '/'),
            'version' => '',
            'categories_count' => 0,
            'posts_count' => 0,
            'pages_count' => 0,
            'theme' => '',
            'logo' => ''
        ];
        
        // Set up authentication
        if (isset($config['application_password']) && !empty($config['application_password'])) {
            $auth = base64_encode($config['username'] . ':' . $config['application_password']);
            log_message('error', 'WordPress Connector - getSiteInfo using application password');
        } else {
            $auth = base64_encode($config['username'] . ':' . $config['password']);
            log_message('error', 'WordPress Connector - getSiteInfo using regular password');
        }
        
        // Get site data from the WP REST API
        $endpoints = [
            'site' => '/wp-json',
            'categories' => '/wp-json/wp/v2/categories?per_page=1',
            'posts' => '/wp-json/wp/v2/posts?per_page=1',
            'pages' => '/wp-json/wp/v2/pages?per_page=1'
        ];
        
        foreach ($endpoints as $key => $endpoint) {
            $url = rtrim($config['url'], '/') . $endpoint;
            log_message('error', 'WordPress Connector - getSiteInfo requesting endpoint: ' . $url);
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Basic ' . $auth,
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            // Log raw response for debugging
            // log_message('error', 'WordPress Connector - getSiteInfo ' . $key . ' Raw Response: ' . substr($response, 0, 1000));
            log_message('error', 'WordPress Connector - getSiteInfo ' . $key . ' HTTP Code: ' . $httpCode);
            
            if (!curl_error($ch) && $httpCode >= 200 && $httpCode < 300) {
                $data = json_decode($response, true);
                
                if ($key === 'site' && is_array($data)) {
                    $siteInfo['title'] = $data['name'] ?? '';
                    $siteInfo['description'] = $data['description'] ?? '';
                    $siteInfo['url'] = $data['url'] ?? $siteInfo['url'];
                    $siteInfo['logo'] = $data['site_logo'] ?? '';
                    // log_message('error', 'WordPress Connector - Site data parsed: ' . json_encode($data));
                } elseif (is_array($data)) {
                    // Get counts from headers
                    $totalItems = curl_getinfo($ch, CURLINFO_HEADER_OUT);
                    $matches = [];
                    if (preg_match('/X-WP-Total: (\d+)/', $totalItems, $matches)) {
                        $count = (int)$matches[1];
                    } else {
                        $count = 0;
                    }
                    
                    if ($key === 'categories') {
                        $siteInfo['categories_count'] = $count;
                    } elseif ($key === 'posts') {
                        $siteInfo['posts_count'] = $count;
                    } elseif ($key === 'pages') {
                        $siteInfo['pages_count'] = $count;
                    }
                    log_message('error', 'WordPress Connector - ' . $key . ' count: ' . $count);
                }
            } else {
                log_message('error', 'WordPress Connector - getSiteInfo ' . $key . ' error: ' . curl_error($ch));
            }
            
            curl_close($ch);
        }
        
        return $siteInfo;
    }
    
    /**
     * Get categories from WordPress
     * 
     * @param array $config Login configuration
     * @return array ['success' => bool, 'categories' => array, 'message' => string]
     */
    public function getCategories(array $config)
    {
        // Log start of operation
        log_message('error', 'WordPress Connector - Getting categories - Starting operation');
        log_message('error', 'WordPress Connector - Config: ' . json_encode($config));
        
        // Validate config
        $validation = $this->validateConfig($config);
        if (!$validation['success']) {
            log_message('error', 'WordPress Connector - Config validation failed: ' . $validation['message']);
            return [
                'success' => false,
                'categories' => [],
                'message' => $validation['message']
            ];
        }
        
        // Prepare request URL
        $url = rtrim($config['url'], '/') . '/wp-json/wp/v2/categories?per_page=100';
        log_message('error', 'WordPress Connector - Request URL: ' . $url);
        
        // Set up authentication - prioritize application password if available
        if (isset($config['application_password']) && !empty($config['application_password'])) {
            $auth = base64_encode($config['username'] . ':' . $config['application_password']);
            log_message('error', 'WordPress Connector - Using application password authentication');
        } else {
            $auth = base64_encode($config['username'] . ':' . $config['password']);
            log_message('error', 'WordPress Connector - Using regular password authentication');
        }
        
        log_message('error', 'WordPress Connector - Auth setup completed');
        
        // Set up cURL request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Basic ' . $auth,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        // Execute request
        log_message('error', 'WordPress Connector - Executing cURL request');
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        // Log raw response and curl info
        //  log_message('error', 'WordPress Connector - Raw Response: ' . substr($response, 0, 1000));
        log_message('error', 'WordPress Connector - HTTP Code: ' . $httpCode);
        // log_message('error', 'WordPress Connector - cURL Info: ' . json_encode(curl_getinfo($ch)));
        
        curl_close($ch);
        
        // Check for errors
        if ($error) {
            log_message('error', 'WordPress Connector - cURL error: ' . $error);
            return [
                'success' => false,
                'categories' => [],
                'message' => 'Connection error: ' . $error
            ];
        }
        
        // Check HTTP status code
        if ($httpCode >= 200 && $httpCode < 300) {
            $categories = json_decode($response, true);
            
            if (!is_array($categories)) {
                log_message('error', 'WordPress Connector - Invalid response format: ' . substr($response, 0, 500));
                return [
                    'success' => false,
                    'categories' => [],
                    'message' => 'Invalid response format'
                ];
            }
            
            // Log parsed categories
            log_message('error', 'WordPress Connector - Parsed Categories: ' . json_encode($categories));
            
            // Format categories
            $formattedCategories = [];
            foreach ($categories as $category) {
                if (isset($category['id']) && isset($category['name'])) {
                    $formattedCategory = [
                        'category_id' => $category['id'],
                        'name' => $category['name'],
                        'parent_id' => $category['parent'] ?? 0,
                        'count' => $category['count'] ?? 0,
                        'slug' => $category['slug'] ?? '',
                        'description' => $category['description'] ?? '',
                        'url' => $category['link'] ?? '',
                        'raw_data' => json_encode($category)
                    ];
                    $formattedCategories[] = $formattedCategory;
                    
                    // Log each formatted category
                    log_message('error', 'WordPress Connector - Formatted Category: ' . json_encode($formattedCategory));
                }
            }
            
            log_message('info', 'WordPress Connector - Successfully retrieved ' . count($formattedCategories) . ' categories');
            
            return [
                'success' => true,
                'categories' => $formattedCategories,
                'message' => 'Categories retrieved successfully'
            ];
        } else {
            $errorMessage = 'HTTP Error ' . $httpCode;
            
            // Try to parse error message from response
            if ($response) {
                $responseData = json_decode($response, true);
                if (isset($responseData['message'])) {
                    $errorMessage .= ': ' . $responseData['message'];
                }
            }
            
            log_message('error', 'WordPress Connector - Failed to get categories: ' . $errorMessage . ' - Response: ' . substr($response, 0, 500));
            
            return [
                'success' => false,
                'categories' => [],
                'message' => $errorMessage
            ];
        }
    }
    
    /**
     * Publish a post to WordPress
     * 
     * @param array $config Login configuration
     * @param array $post Post data
     * @return array ['success' => bool, 'post_id' => int|string, 'post_url' => string, 'message' => string]
     */
    public function publishPost(array $config, array $post)
    {
        // Validate config
        $validation = $this->validateConfig($config);
        if (!$validation['success']) {
            return [
                'success' => false,
                'post_id' => 0,
                'post_url' => '',
                'message' => $validation['message']
            ];
        }
        
        // Validate post data
        if (!isset($post['title']) || !isset($post['content'])) {
            return [
                'success' => false,
                'post_id' => 0,
                'post_url' => '',
                'message' => 'Missing required post data (title or content)'
            ];
        }
        
        // Prepare request URL
        $url = rtrim($config['url'], '/') . '/wp-json/wp/v2/posts';
        
        // Set up authentication
        $auth = base64_encode($config['username'] . ':' . $config['password']);
        
        // Prepare post data
        $postData = [
            'title' => $post['title'],
            'content' => $post['content'],
            'status' => $post['status'] ?? 'draft'
        ];
        
        // Add categories if provided
        if (isset($post['categories']) && is_array($post['categories'])) {
            $postData['categories'] = $post['categories'];
        }
        
        // Add featured image if provided
        if (isset($post['featured_image'])) {
            $postData['featured_media'] = $post['featured_image'];
        }
        
        // Add excerpt if provided
        if (isset($post['excerpt'])) {
            $postData['excerpt'] = $post['excerpt'];
        }
        
        // Set up cURL request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Basic ' . $auth,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        
        // Execute request
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        // Check for errors
        if ($error) {
            return [
                'success' => false,
                'post_id' => 0,
                'post_url' => '',
                'message' => 'Connection error: ' . $error
            ];
        }
        
        // Check HTTP status code
        if ($httpCode >= 200 && $httpCode < 300) {
            $responseData = json_decode($response, true);
            
            if (!is_array($responseData) || !isset($responseData['id'])) {
                return [
                    'success' => false,
                    'post_id' => 0,
                    'post_url' => '',
                    'message' => 'Invalid response format'
                ];
            }
            
            return [
                'success' => true,
                'post_id' => $responseData['id'],
                'post_url' => $responseData['link'] ?? '',
                'message' => 'Post published successfully'
            ];
        } else {
            $errorMessage = 'HTTP Error ' . $httpCode;
            
            // Try to parse error message from response
            if ($response) {
                $responseData = json_decode($response, true);
                if (isset($responseData['message'])) {
                    $errorMessage .= ': ' . $responseData['message'];
                }
            }
            
            return [
                'success' => false,
                'post_id' => 0,
                'post_url' => '',
                'message' => $errorMessage
            ];
        }
    }
    
    /**
     * Get required login fields for WordPress
     * 
     * @return array List of required fields
     */
    public function getLoginFields()
    {
        // Prioritize application_password by putting it first in the list
        return ['url', 'username', 'application_password', 'password'];
    }
    
    /**
     * Validate WordPress login configuration
     * 
     * @param array $config Login configuration
     * @return array ['success' => bool, 'message' => string]
     */
    public function validateConfig(array $config)
    {
        $requiredFields = ['url', 'username'];
        
        foreach ($requiredFields as $field) {
            if (!isset($config[$field]) || empty($config[$field])) {
                return [
                    'success' => false,
                    'message' => 'Missing required field: ' . $field
                ];
            }
        }
        
        // Check that at least one authentication method is provided
        if ((!isset($config['application_password']) || empty($config['application_password'])) &&
            (!isset($config['password']) || empty($config['password']))) {
            return [
                'success' => false,
                'message' => 'Missing authentication: either password or application_password is required'
            ];
        }
        
        // Validate URL format
        if (!filter_var($config['url'], FILTER_VALIDATE_URL)) {
            return [
                'success' => false,
                'message' => 'Invalid URL format'
            ];
        }
        
        return [
            'success' => true,
            'message' => 'Configuration is valid'
        ];
    }

    /**
     * Get blogs/posts from WordPress by category
     * 
     * @param array $config Login configuration
     * @param array $args Additional arguments including category_id
     * @return array ['success' => bool, 'blogs' => array, 'message' => string]
     */
    public function getBlogs(array $config, array $args = [])
    {
        // Log start of operation
        log_message('error', 'WordPress Connector - Getting blogs - Starting operation');
        log_message('error', 'WordPress Connector - Config: ' . json_encode($config));
        log_message('error', 'WordPress Connector - Args: ' . json_encode($args));
        
        // Validate config
        $validation = $this->validateConfig($config);
        if (!$validation['success']) {
            log_message('error', 'WordPress Connector - Config validation failed: ' . $validation['message']);
            return [
                'success' => false,
                'blogs' => [],
                'message' => $validation['message']
            ];
        }
        
        // Check for category_id in args
        $category_id = isset($args['category_id']) ? $args['category_id'] : null;
        if (!$category_id) {
            log_message('error', 'WordPress Connector - No category ID provided');
            return [
                'success' => false,
                'blogs' => [],
                'message' => 'Category ID is required'
            ];
        }
        
        // Prepare request URL - include published posts by default
        $url = rtrim($config['url'], '/') . '/wp-json/wp/v2/posts?categories=' . $category_id . '&per_page=100&status=publish';
        log_message('error', 'WordPress Connector - Request URL: ' . $url);
        
        // Try to discover if we need to use slug instead of ID for the category
        // Some WordPress installations require slug instead of ID
        $is_numeric_id = is_numeric($category_id);
        log_message('error', 'WordPress Connector - Category ID is numeric: ' . ($is_numeric_id ? 'true' : 'false'));
        
        // Set up authentication - prioritize application password if available
        if (isset($config['application_password']) && !empty($config['application_password'])) {
            $auth = base64_encode($config['username'] . ':' . $config['application_password']);
            log_message('error', 'WordPress Connector - Using application password authentication');
        } else {
            $auth = base64_encode($config['username'] . ':' . $config['password']);
            log_message('error', 'WordPress Connector - Using regular password authentication');
        }
        
        log_message('error', 'WordPress Connector - Auth setup completed');
        
        // Set up cURL request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Basic ' . $auth,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        // Execute request
        log_message('error', 'WordPress Connector - Executing cURL request for blogs');
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        // Log raw response and curl info
        // log_message('error', 'WordPress Connector - Raw Response: ' . substr($response, 0, 1000));
        log_message('error', 'WordPress Connector - HTTP Code: ' . $httpCode);
        // log_message('error', 'WordPress Connector - cURL Info: ' . json_encode(curl_getinfo($ch)));
        
        curl_close($ch);
        
        // Check for errors
        if ($error) {
            log_message('error', 'WordPress Connector - cURL error: ' . $error);
            return [
                'success' => false,
                'blogs' => [],
                'message' => 'Connection error: ' . $error
            ];
        }
        
        // Check HTTP status code
        if ($httpCode >= 200 && $httpCode < 300) {
            $posts = json_decode($response, true);
            
            if (!is_array($posts)) {
                log_message('error', 'WordPress Connector - Invalid response format: ' . substr($response, 0, 500));
                return [
                    'success' => false,
                    'blogs' => [],
                    'message' => 'Invalid response format'
                ];
            }
            
            // Log parsed posts
            log_message('error', 'WordPress Connector - Parsed Posts Count: ' . count($posts));
            log_message('error', 'WordPress Connector - Parsed Posts: ' . json_encode(array_slice($posts, 0, 2))); // Log only first 2 posts to avoid huge logs
            
            // If no posts found, try alternative approach with category slug if we were using numeric ID
            if (empty($posts) && is_numeric($category_id)) {
                log_message('error', 'WordPress Connector - No posts found with numeric category ID, trying to fetch category info first');
                
                // Get category info to find the slug
                $category_info = $this->getCategoryInfo($config, $category_id);
                if ($category_info && isset($category_info['slug'])) {
                    $slug = $category_info['slug'];
                    log_message('error', 'WordPress Connector - Got category slug: ' . $slug);
                    
                    // Try with slug instead
                    $alt_url = rtrim($config['url'], '/') . '/wp-json/wp/v2/posts?category_name=' . $slug . '&per_page=100&status=publish';
                    log_message('error', 'WordPress Connector - Trying alternative URL: ' . $alt_url);
                    
                    // Setup and execute new request with slug
                    $ch2 = curl_init();
                    curl_setopt($ch2, CURLOPT_URL, $alt_url);
                    curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch2, CURLOPT_HTTPHEADER, [
                        'Authorization: Basic ' . $auth,
                        'Content-Type: application/json'
                    ]);
                    curl_setopt($ch2, CURLOPT_TIMEOUT, 30);
                    
                    $alt_response = curl_exec($ch2);
                    $alt_httpCode = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
                    curl_close($ch2);
                    
                    log_message('error', 'WordPress Connector - Alternative HTTP Code: ' . $alt_httpCode);
                    
                    if ($alt_httpCode >= 200 && $alt_httpCode < 300) {
                        $alt_posts = json_decode($alt_response, true);
                        if (is_array($alt_posts) && !empty($alt_posts)) {
                            log_message('error', 'WordPress Connector - Found ' . count($alt_posts) . ' posts using category slug');
                            $posts = $alt_posts;
                        }
                    }
                }
            }
            
            // Format blogs
            $formattedBlogs = [];
            foreach ($posts as $post) {
                // Print log with basic info for debugging
                log_message('error', 'WordPress Connector - Processing post: ' . $post['id'] . ' - ' . $post['title']['rendered']);
                
                if (isset($post['id']) && isset($post['title']['rendered'])) {
                    // Extract featured image if available
                    $featured_image = null;
                    if (isset($post['featured_media']) && $post['featured_media'] > 0) {
                        $featured_image = $this->getPostFeaturedImage($config, $post['featured_media']);
                    }
                    
                    // Cắt ngắn excerpt thành tối đa 200 ký tự
                    $excerpt = '';
                    if (isset($post['excerpt']['rendered'])) {
                        $excerpt = strip_tags($post['excerpt']['rendered']);
                        $excerpt = trim($excerpt);
                        if (strlen($excerpt) > 200) {
                            $excerpt = substr($excerpt, 0, 197) . '...';
                        }
                    }
                    
                    // Log kích thước nội dung gốc để dễ dàng đánh giá
                    $original_content_size = isset($post['content']['rendered']) ? strlen($post['content']['rendered']) : 0;
                    log_message('error', 'WordPress Connector - Post ' . $post['id'] . ' original content size: ' . $original_content_size . ' bytes');
                    
                    // Chi tiết tác giả, nếu có thể lấy được
                    $author_name = '';
                    if (isset($post['_embedded']['author'][0]['name'])) {
                        $author_name = $post['_embedded']['author'][0]['name'];
                    } elseif (isset($post['author'])) {
                        $author_name = $post['author'];
                    }
                    
                    // Chuẩn bị dữ liệu blog chỉ với thông tin cần thiết
                    $formattedBlog = [
                        'blog_id' => $post['id'],
                        'title' => $post['title']['rendered'],
                        'content' => '', // Không lưu nội dung HTML để tiết kiệm dung lượng
                        'excerpt' => $excerpt,
                        'date_published' => $post['date'] ?? null,
                        'date_modified' => $post['modified'] ?? null,
                        'author' => $author_name,
                        'slug' => $post['slug'] ?? '',
                        'url' => $post['link'] ?? '',
                        'featured_image' => $featured_image,
                        'status' => $post['status'] ?? 'publish',
                        'comment_count' => $post['comment_count'] ?? 0,
                        'raw_data' => json_encode([
                            'id' => $post['id'],
                            'url' => $post['link'] ?? '',
                            'date' => $post['date'] ?? null,
                            'modified' => $post['modified'] ?? null,
                            'slug' => $post['slug'] ?? '',
                            'status' => $post['status'] ?? 'publish'
                        ]),
                        'categories' => isset($post['categories']) ? $post['categories'] : [$category_id]
                    ];
                    
                    $formattedBlogs[] = $formattedBlog;
                    
                    // Thêm log để biết quá trình xử lý đã hoàn tất
                    log_message('error', 'WordPress Connector - Blog formatted: ' . $post['id'] . ' - Size reduced from ' . 
                        $original_content_size . ' bytes to ' . strlen(json_encode($formattedBlog)) . ' bytes');
                }
            }
            
            log_message('error', 'WordPress Connector - Successfully retrieved ' . count($formattedBlogs) . ' blogs from category ' . $category_id);
            
            return [
                'success' => true,
                'blogs' => $formattedBlogs,
                'message' => count($formattedBlogs) . ' blogs found'
            ];
        } else {
            // Try to parse error message from response
            $error_data = json_decode($response, true);
            $error_message = isset($error_data['message']) ? $error_data['message'] : 'HTTP Error ' . $httpCode;
            
            log_message('error', 'WordPress Connector - API error: ' . $error_message);
            log_message('error', 'WordPress Connector - Response: ' . $response);
            
            return [
                'success' => false,
                'blogs' => [],
                'message' => $error_message
            ];
        }
    }
    
    /**
     * Get featured image URL for a post
     * @param array $config WordPress configuration
     * @param int $media_id Featured media ID
     * @return string|null Featured image URL or null if not found
     */
    private function getPostFeaturedImage($config, $media_id) 
    {
        // Skip if no media ID
        if (empty($media_id)) {
            return null;
        }
        
        // Set up authentication
        if (isset($config['application_password']) && !empty($config['application_password'])) {
            $auth = base64_encode($config['username'] . ':' . $config['application_password']);
        } else {
            $auth = base64_encode($config['username'] . ':' . $config['password']);
        }
        
        // Prepare request URL
        $url = rtrim($config['url'], '/') . '/wp-json/wp/v2/media/' . $media_id;
        log_message('error', 'WordPress Connector - Getting featured image: ' . $url);
        
        // Set up cURL request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Basic ' . $auth,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        // Execute request
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // Process response
        if ($httpCode >= 200 && $httpCode < 300) {
            $media = json_decode($response, true);
            if (isset($media['source_url'])) {
                return $media['source_url'];
            } elseif (isset($media['guid']['rendered'])) {
                return $media['guid']['rendered'];
            }
        }
        
        return null;
    }
    
    /**
     * Get category info from WordPress
     * @param array $config WordPress configuration
     * @param int $category_id Category ID
     * @return array|null Category info or null if not found
     */
    private function getCategoryInfo($config, $category_id)
    {
        // Set up authentication
        if (isset($config['application_password']) && !empty($config['application_password'])) {
            $auth = base64_encode($config['username'] . ':' . $config['application_password']);
        } else {
            $auth = base64_encode($config['username'] . ':' . $config['password']);
        }
        
        // Prepare request URL
        $url = rtrim($config['url'], '/') . '/wp-json/wp/v2/categories/' . $category_id;
        log_message('error', 'WordPress Connector - Getting category info: ' . $url);
        
        // Set up cURL request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Basic ' . $auth,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        // Execute request
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // Process response
        if ($httpCode >= 200 && $httpCode < 300) {
            return json_decode($response, true);
        }
        
        return null;
    }

    /**
     * Get blogs from platform
     * @param array $config Login configuration
     * @return array Blogs data or false on failure
     */
    public function get_blogs($config)
    {
        // WordPress thường chỉ có một blog, trả về thông tin site
        $siteInfo = $this->getSiteInfo($config);
        
        if (!$siteInfo) {
            return false;
        }
        
        // Định dạng dữ liệu trả về theo chuẩn
        return [
            'success' => true,
            'data' => [
                [
                    'id' => 1, // WordPress mặc định blog ID là 1
                    'name' => $siteInfo['title'],
                    'url' => $siteInfo['url'],
                    'description' => $siteInfo['description']
                ]
            ],
            'total' => 1,
            'message' => 'Successfully retrieved blogs'
        ];
    }

    /**
     * Get categories from platform
     * @param array $config Login configuration
     * @param int|string $blog_id Blog ID
     * @return array Categories data or false on failure
     */
    public function get_categories($config, $blog_id)
    {
        $result = $this->getCategories($config);
        
        if (!$result['success']) {
            return false;
        }
        
        return [
            'success' => true,
            'data' => $result['categories'],
            'total' => count($result['categories']),
            'message' => 'Successfully retrieved categories'
        ];
    }

    /**
     * Get tags from platform
     * @param array $config Login configuration
     * @param int|string $blog_id Blog ID
     * @param array $options Options for the request (e.g. page, per_page)
     * @return array Tags data with standard format or false on failure
     */
    public function get_tags($config, $blog_id, $options = [])
    {
        // Tạo mảng params từ các tham số đầu vào để tương thích với code hiện tại
        $params = [
            'config' => $config,
            'blog_id' => $blog_id
        ];
        
        // Thêm các tùy chọn vào params
        if (isset($options['page'])) {
            $params['page'] = $options['page'];
        }
        
        if (isset($options['per_page'])) {
            $params['per_page'] = $options['per_page'];
        }
        
        // Validate config
        $validation = $this->validateConfig($config);
        if (!$validation['success']) {
            log_message('error', 'WordPress Connector - get_tags: Config validation failed: ' . $validation['message']);
            return [
                'success' => false,
                'message' => $validation['message']
            ];
        }
        
        // Lấy các tham số tùy chọn
        $page = isset($params['page']) ? (int)$params['page'] : 1;
        $per_page = isset($params['per_page']) ? (int)$params['per_page'] : 20;
        
        // Prepare request URL
        $url = rtrim($config['url'], '/') . '/wp-json/wp/v2/tags?per_page=' . $per_page . '&page=' . $page;
        log_message('debug', 'WordPress Connector - get_tags: Request URL: ' . $url);
        
        // Set up authentication - prioritize application password if available
        if (isset($config['application_password']) && !empty($config['application_password'])) {
            $auth = base64_encode($config['username'] . ':' . $config['application_password']);
        } else {
            $auth = base64_encode($config['username'] . ':' . $config['password']);
        }
        
        // Set up cURL request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Basic ' . $auth,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HEADER, true);
        
        // Execute request
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $body = substr($response, $headerSize);
        $headers = substr($response, 0, $headerSize);
        
        // Log response for debugging
        log_message('debug', 'WordPress Connector - get_tags: HTTP Code: ' . $httpCode);
        
        // Check for errors
        if (curl_error($ch)) {
            log_message('error', 'WordPress Connector - get_tags: cURL Error: ' . curl_error($ch));
            curl_close($ch);
            return [
                'success' => false,
                'message' => 'Connection error: ' . curl_error($ch)
            ];
        }
        
        curl_close($ch);
        
        // Check HTTP status code
        if ($httpCode < 200 || $httpCode >= 300) {
            $errorMessage = 'HTTP Error ' . $httpCode;
            
            // Try to parse error message from response
            if ($body) {
                $responseData = json_decode($body, true);
                if (isset($responseData['message'])) {
                    $errorMessage .= ': ' . $responseData['message'];
                }
            }
            
            log_message('error', 'WordPress Connector - get_tags: ' . $errorMessage);
            return [
                'success' => false,
                'message' => $errorMessage
            ];
        }
        
        // Parse response
        $tags = json_decode($body, true);
        
        if (!is_array($tags)) {
            log_message('error', 'WordPress Connector - get_tags: Invalid response format');
            return [
                'success' => false,
                'message' => 'Invalid response format'
            ];
        }
        
        // Get total pages and total items from headers
        $totalPages = 1;
        $totalItems = count($tags);
        
        if (preg_match('/X-WP-TotalPages: (\d+)/i', $headers, $matches)) {
            $totalPages = (int)$matches[1];
        }
        
        if (preg_match('/X-WP-Total: (\d+)/i', $headers, $matches)) {
            $totalItems = (int)$matches[1];
        }
        
        // Format tags data
        $formattedTags = [];
        foreach ($tags as $tag) {
            $formattedTags[] = [
                'id' => $tag['id'],
                'name' => $tag['name'],
                'slug' => $tag['slug'],
                'count' => $tag['count'],
                'url' => $tag['link'] ?? ''
            ];
        }
        
        // Return formatted response
        return [
            'success' => true,
            'data' => [
                'tags' => $formattedTags,
                'total' => $totalItems,
                'total_pages' => $totalPages,
                'current_page' => $page
            ],
            'message' => 'Successfully retrieved tags'
        ];
    }

    /**
     * Get posts from platform
     * @param array $config Login configuration
     * @param int|string $blog_id Blog ID
     * @param array $options Options for the request (e.g. page, per_page, category, tag)
     * @return array Posts data or false on failure
     */
    public function get_posts($config, $blog_id, $options = [])
    {
        // Validate config
        $validation = $this->validateConfig($config);
        if (!$validation['success']) {
            return false;
        }
        
        // Prepare request URL
        $url = rtrim($config['url'], '/') . '/wp-json/wp/v2/posts';
        
        // Add query parameters
        $queryParams = [];
        
        // Pagination
        $page = isset($options['page']) ? (int)$options['page'] : 1;
        $per_page = isset($options['per_page']) ? (int)$options['per_page'] : 10;
        $queryParams['page'] = $page;
        $queryParams['per_page'] = $per_page;
        
        // Filtering
        if (isset($options['category']) && !empty($options['category'])) {
            $queryParams['categories'] = $options['category'];
        }
        
        if (isset($options['tag']) && !empty($options['tag'])) {
            $queryParams['tags'] = $options['tag'];
        }
        
        if (isset($options['search']) && !empty($options['search'])) {
            $queryParams['search'] = $options['search'];
        }
        
        // Add query parameters to URL
        if (!empty($queryParams)) {
            $url .= '?' . http_build_query($queryParams);
        }
        
        // Set up authentication
        if (isset($config['application_password']) && !empty($config['application_password'])) {
            $auth = base64_encode($config['username'] . ':' . $config['application_password']);
        } else {
            $auth = base64_encode($config['username'] . ':' . $config['password']);
        }
        
        // Set up cURL request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Basic ' . $auth,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HEADER, true);
        
        // Execute request
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $body = substr($response, $headerSize);
        $headers = substr($response, 0, $headerSize);
        
        // Check for errors
        if (curl_error($ch)) {
            curl_close($ch);
            return false;
        }
        
        curl_close($ch);
        
        // Check HTTP status code
        if ($httpCode < 200 || $httpCode >= 300) {
            return false;
        }
        
        // Parse response
        $posts = json_decode($body, true);
        
        if (!is_array($posts)) {
            return false;
        }
        
        // Get total pages and total items from headers
        $totalPages = 1;
        $totalItems = count($posts);
        
        if (preg_match('/X-WP-TotalPages: (\d+)/i', $headers, $matches)) {
            $totalPages = (int)$matches[1];
        }
        
        if (preg_match('/X-WP-Total: (\d+)/i', $headers, $matches)) {
            $totalItems = (int)$matches[1];
        }
        
        // Format posts data
        $formattedPosts = [];
        foreach ($posts as $post) {
            $formattedPosts[] = [
                'id' => $post['id'],
                'title' => $post['title']['rendered'],
                'content' => $post['content']['rendered'],
                'excerpt' => $post['excerpt']['rendered'],
                'date' => $post['date'],
                'modified' => $post['modified'],
                'slug' => $post['slug'],
                'status' => $post['status'],
                'url' => $post['link'],
                'featured_image' => $this->getPostFeaturedImage($config, $post['featured_media'])
            ];
        }
        
        // Return formatted response
        return [
            'success' => true,
            'data' => $formattedPosts,
            'total' => $totalItems,
            'total_pages' => $totalPages,
            'current_page' => $page,
            'message' => 'Successfully retrieved posts'
        ];
    }

    /**
     * Create post on platform
     * @param array $config Login configuration
     * @param int|string $blog_id Blog ID
     * @param array $post_data Post data
     * @return array|bool Created post data or false on failure
     */
    public function create_post($config, $blog_id, $post_data)
    {
        // Validate config
        $validation = $this->validateConfig($config);
        if (!$validation['success']) {
            return false;
        }
        
        // Prepare request URL
        $url = rtrim($config['url'], '/') . '/wp-json/wp/v2/posts';
        
        // Prepare post data
        $data = [
            'title' => $post_data['title'],
            'content' => $post_data['content'],
            'status' => $post_data['status'] ?? 'draft'
        ];
        
        // Add excerpt if provided
        if (isset($post_data['excerpt']) && !empty($post_data['excerpt'])) {
            $data['excerpt'] = $post_data['excerpt'];
        }
        
        // Add categories if provided
        if (isset($post_data['categories']) && !empty($post_data['categories'])) {
            $data['categories'] = $post_data['categories'];
        }
        
        // Add tags if provided
        if (isset($post_data['tags']) && !empty($post_data['tags'])) {
            $data['tags'] = $post_data['tags'];
        }
        
        // Set up authentication
        if (isset($config['application_password']) && !empty($config['application_password'])) {
            $auth = base64_encode($config['username'] . ':' . $config['application_password']);
        } else {
            $auth = base64_encode($config['username'] . ':' . $config['password']);
        }
        
        // Set up cURL request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Basic ' . $auth,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        // Execute request
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        // Check for errors
        if (curl_error($ch)) {
            curl_close($ch);
            return false;
        }
        
        curl_close($ch);
        
        // Check HTTP status code
        if ($httpCode < 200 || $httpCode >= 300) {
            return false;
        }
        
        // Parse response
        $post = json_decode($response, true);
        
        if (!is_array($post)) {
            return false;
        }
        
        // Return created post data
        return [
            'success' => true,
            'data' => [
                'id' => $post['id'],
                'title' => $post['title']['rendered'],
                'content' => $post['content']['rendered'],
                'excerpt' => $post['excerpt']['rendered'],
                'date' => $post['date'],
                'modified' => $post['modified'],
                'slug' => $post['slug'],
                'status' => $post['status'],
                'url' => $post['link']
            ],
            'message' => 'Post created successfully'
        ];
    }

    /**
     * Update post on platform
     * @param array $config Login configuration
     * @param int|string $blog_id Blog ID
     * @param int|string $post_id Post ID
     * @param array $post_data Post data
     * @return array|bool Updated post data or false on failure
     */
    public function update_post($config, $blog_id, $post_id, $post_data)
    {
        // Validate config
        $validation = $this->validateConfig($config);
        if (!$validation['success']) {
            return false;
        }
        
        // Prepare request URL
        $url = rtrim($config['url'], '/') . '/wp-json/wp/v2/posts/' . $post_id;
        
        // Prepare post data
        $data = [];
        
        // Add title if provided
        if (isset($post_data['title']) && !empty($post_data['title'])) {
            $data['title'] = $post_data['title'];
        }
        
        // Add content if provided
        if (isset($post_data['content']) && !empty($post_data['content'])) {
            $data['content'] = $post_data['content'];
        }
        
        // Add status if provided
        if (isset($post_data['status']) && !empty($post_data['status'])) {
            $data['status'] = $post_data['status'];
        }
        
        // Add excerpt if provided
        if (isset($post_data['excerpt']) && !empty($post_data['excerpt'])) {
            $data['excerpt'] = $post_data['excerpt'];
        }
        
        // Add categories if provided
        if (isset($post_data['categories']) && !empty($post_data['categories'])) {
            $data['categories'] = $post_data['categories'];
        }
        
        // Add tags if provided
        if (isset($post_data['tags']) && !empty($post_data['tags'])) {
            $data['tags'] = $post_data['tags'];
        }
        
        // Set up authentication
        if (isset($config['application_password']) && !empty($config['application_password'])) {
            $auth = base64_encode($config['username'] . ':' . $config['application_password']);
        } else {
            $auth = base64_encode($config['username'] . ':' . $config['password']);
        }
        
        // Set up cURL request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Basic ' . $auth,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        // Execute request
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        // Check for errors
        if (curl_error($ch)) {
            curl_close($ch);
            return false;
        }
        
        curl_close($ch);
        
        // Check HTTP status code
        if ($httpCode < 200 || $httpCode >= 300) {
            return false;
        }
        
        // Parse response
        $post = json_decode($response, true);
        
        if (!is_array($post)) {
            return false;
        }
        
        // Return updated post data
        return [
            'success' => true,
            'data' => [
                'id' => $post['id'],
                'title' => $post['title']['rendered'],
                'content' => $post['content']['rendered'],
                'excerpt' => $post['excerpt']['rendered'],
                'date' => $post['date'],
                'modified' => $post['modified'],
                'slug' => $post['slug'],
                'status' => $post['status'],
                'url' => $post['link']
            ],
            'message' => 'Post updated successfully'
        ];
    }

    /**
     * Upload media to platform
     * @param array $config Login configuration
     * @param int|string $blog_id Blog ID
     * @param string $file_path Local file path
     * @param array $media_data Media data (title, caption, etc.)
     * @return array|bool Uploaded media data or false on failure
     */
    public function upload_media($config, $blog_id, $file_path, $media_data = [])
    {
        // Validate config
        $validation = $this->validateConfig($config);
        if (!$validation['success']) {
            return false;
        }
        
        // Check if file exists
        if (!file_exists($file_path)) {
            return false;
        }
        
        // Prepare request URL
        $url = rtrim($config['url'], '/') . '/wp-json/wp/v2/media';
        
        // Get file info
        $file_name = basename($file_path);
        $file_type = mime_content_type($file_path);
        $file_content = file_get_contents($file_path);
        
        // Set up authentication
        if (isset($config['application_password']) && !empty($config['application_password'])) {
            $auth = base64_encode($config['username'] . ':' . $config['application_password']);
        } else {
            $auth = base64_encode($config['username'] . ':' . $config['password']);
        }
        
        // Set up cURL request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $file_content);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Basic ' . $auth,
            'Content-Type: ' . $file_type,
            'Content-Disposition: attachment; filename=' . $file_name
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        
        // Execute request
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        // Check for errors
        if (curl_error($ch)) {
            curl_close($ch);
            return false;
        }
        
        curl_close($ch);
        
        // Check HTTP status code
        if ($httpCode < 200 || $httpCode >= 300) {
            return false;
        }
        
        // Parse response
        $media = json_decode($response, true);
        
        if (!is_array($media)) {
            return false;
        }
        
        // Update media with additional data if provided
        if (!empty($media_data)) {
            $update_url = rtrim($config['url'], '/') . '/wp-json/wp/v2/media/' . $media['id'];
            
            // Prepare media data
            $data = [];
            
            // Add title if provided
            if (isset($media_data['title']) && !empty($media_data['title'])) {
                $data['title'] = $media_data['title'];
            }
            
            // Add caption if provided
            if (isset($media_data['caption']) && !empty($media_data['caption'])) {
                $data['caption'] = $media_data['caption'];
            }
            
            // Add description if provided
            if (isset($media_data['description']) && !empty($media_data['description'])) {
                $data['description'] = $media_data['description'];
            }
            
            // Add alt text if provided
            if (isset($media_data['alt_text']) && !empty($media_data['alt_text'])) {
                $data['alt_text'] = $media_data['alt_text'];
            }
            
            // Set up cURL request for update
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $update_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Basic ' . $auth,
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            
            // Execute request
            $update_response = curl_exec($ch);
            $update_httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            // Check for errors
            if (!curl_error($ch) && $update_httpCode >= 200 && $update_httpCode < 300) {
                $media = json_decode($update_response, true);
            }
            
            curl_close($ch);
        }
        
        // Return uploaded media data
        return [
            'success' => true,
            'data' => [
                'id' => $media['id'],
                'title' => $media['title']['rendered'],
                'url' => $media['source_url'],
                'date' => $media['date'],
                'type' => $media['media_type'],
                'mime_type' => $media['mime_type']
            ],
            'message' => 'Media uploaded successfully'
        ];
    }
    
    /**
     * Test connection to WordPress
     * @param array $config Login configuration
     * @return bool True if connected successfully, false otherwise
     */
    public function test_connection($config)
    {
        $result = $this->testConnection($config);
        return $result['success'];
    }
    
    /**
     * Check if post with similar title exists
     * @param array $config Login configuration
     * @param string $title Post title to check
     * @param int|string $blog_id Blog ID
     * @return array Result with standard format
     */
    public function check_post_exists($config, $title, $blog_id)
    {
        if (empty($config) || empty($title)) {
            return [
                'exists' => false,
                'permalink' => '',
                'similarity' => 0,
                'http_code' => 0,
                'error' => 'Invalid parameters'
            ];
        }
        
        try {
            // Build the API endpoint URL for searching posts
            $api_url = $this->get_api_url($config['url']) . 'posts';
            
            // Add query parameters
            $params = [
                'search' => $title,
                'per_page' => 5, // Limit to 5 results
                'status' => 'any', // Check all post statuses
                '_fields' => 'id,title,link,status' // Only get needed fields
            ];
            
            $api_url .= '?' . http_build_query($params);
            
            // Setup the request
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $api_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Accept: application/json'
            ]);
            
            // Add authentication if provided
            if (!empty($config['username']) && !empty($config['password'])) {
                curl_setopt($ch, CURLOPT_USERPWD, $config['username'] . ':' . $config['password']);
            } elseif (!empty($config['api_key'])) {
                // If using API key authentication
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'Accept: application/json',
                    'Authorization: Bearer ' . $config['api_key']
                ]);
            }
            
            // Execute the request
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            // Process the response
            if ($http_code !== 200 || empty($response)) {
                return [
                    'exists' => false,
                    'permalink' => '',
                    'similarity' => 0,
                    'http_code' => $http_code,
                    'error' => 'API request failed with code ' . $http_code
                ];
            }
            
            $posts = json_decode($response, true);
            
            // If no posts found
            if (empty($posts)) {
                return [
                    'exists' => false,
                    'permalink' => '',
                    'similarity' => 0,
                    'http_code' => $http_code
                ];
            }
            
            // Check each post for title similarity
            $title_lower = strtolower(trim($title));
            $best_match = null;
            $best_similarity = 0;
            
            foreach ($posts as $post) {
                $post_title = isset($post['title']['rendered']) ? 
                    strtolower(trim(strip_tags($post['title']['rendered']))) : 
                    '';
                
                // Calculate similarity
                $similarity = 0;
                if (!empty($post_title) && !empty($title_lower)) {
                    // Use levenshtein distance for similarity
                    $lev = levenshtein($title_lower, $post_title);
                    $max_len = max(strlen($title_lower), strlen($post_title));
                    
                    // Convert to similarity score (0-1)
                    $similarity = 1 - ($lev / $max_len);
                }
                
                // If this is the most similar post so far
                if ($similarity > $best_similarity) {
                    $best_similarity = $similarity;
                    $best_match = $post;
                }
                
                // Exact match found
                if ($post_title === $title_lower) {
                    return [
                        'exists' => true,
                        'permalink' => $post['link'],
                        'similarity' => 1.0,
                        'http_code' => $http_code,
                        'post_id' => $post['id'],
                        'post_title' => $post_title,
                        'post_status' => $post['status']
                    ];
                }
            }
            
            // If we found a good match (over 90% similarity)
            if ($best_similarity >= 0.9 && $best_match) {
                return [
                    'exists' => true,
                    'permalink' => $best_match['link'],
                    'similarity' => $best_similarity,
                    'http_code' => $http_code,
                    'post_id' => $best_match['id'],
                    'post_title' => isset($best_match['title']['rendered']) ? 
                        strip_tags($best_match['title']['rendered']) : '',
                    'post_status' => $best_match['status']
                ];
            }
            
            // No good match found
            return [
                'exists' => false,
                'permalink' => '',
                'similarity' => $best_similarity,
                'http_code' => $http_code
            ];
            
        } catch (Exception $e) {
            return [
                'exists' => false,
                'permalink' => '',
                'similarity' => 0,
                'http_code' => 0,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get the base API URL for WordPress REST API
     * 
     * @param string $site_url The site URL
     * @return string Formatted API URL with trailing slash
     */
    private function get_api_url($site_url) {
        // Remove trailing slash if present
        $site_url = rtrim($site_url, '/');
        
        // Check if URL ends with /wp-json/wp/v2/ already
        if (preg_match('|/wp-json/wp/v2/$|', $site_url)) {
            return $site_url;
        }
        
        // Check if URL ends with /wp-json/
        if (preg_match('|/wp-json/$|', $site_url)) {
            return $site_url . 'wp/v2/';
        }
        
        // Add the WordPress REST API path
        return $site_url . '/wp-json/wp/v2/';
    }
} 