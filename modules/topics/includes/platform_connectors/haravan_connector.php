<?php
defined('BASEPATH') or exit('No direct script access allowed');

// Use module_dir_path if available, otherwise fall back to direct path
if (function_exists('module_dir_path')) {
    require_once(module_dir_path('topics') . 'includes/platform_connectors/platform_connector_interface.php');
} else {
    require_once(FCPATH . 'modules/topics/includes/platform_connectors/platform_connector_interface.php');
}

/**
 * Haravan Connector
 * 
 * Connector for Haravan e-commerce platform
 */
class HaravanConnector implements PlatformConnectorInterface
{
    /**
     * Test connection to Haravan
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
        $url = rtrim($config['shop_url'], '/') . '/admin/blogs.json';
        log_message('debug', 'Haravan Connector - Test Connection URL: ' . $url);
        
        // Set up cURL request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        // Set up authentication - prioritize Bearer Token if available
        $headers = ['Content-Type: application/json'];
        
        if (isset($config['bearer_token']) && !empty($config['bearer_token'])) {
            // Use Bearer Token authentication
            $headers[] = 'Authorization: Bearer ' . $config['bearer_token'];
            log_message('debug', 'Haravan Connector - Using Bearer Token authentication');
        } else {
            // Fall back to API key authentication
            $headers[] = 'X-Haravan-Access-Token: ' . $config['api_key'];
            log_message('debug', 'Haravan Connector - Using API Key authentication');
        }
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        // Execute request
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        // Log raw response and curl info
        log_message('debug', 'Haravan Connector - Test Connection Raw Response: ' . substr($response, 0, 1000));
        log_message('debug', 'Haravan Connector - Test Connection HTTP Code: ' . $httpCode);
        log_message('debug', 'Haravan Connector - Test Connection cURL Info: ' . json_encode(curl_getinfo($ch)));
        
        curl_close($ch);
        
        // Check for errors
        if ($error) {
            log_message('error', 'Haravan Connector - Test Connection Error: ' . $error);
            return [
                'success' => false,
                'message' => 'Connection error: ' . $error
            ];
        }
        
        // Check HTTP status code
        if ($httpCode >= 200 && $httpCode < 300) {
            // Get shop information
            $shopInfo = $this->getShopInfo($config, $response);
            
            // Log shop info for debugging
            log_message('debug', 'Haravan Connector - Shop Info: ' . json_encode($shopInfo));
            
            return [
                'success' => true,
                'message' => 'Connection successful',
                'site_info' => $shopInfo
            ];
        } else {
            $errorMessage = 'HTTP Error ' . $httpCode;
            
            // Try to parse error message from response
            if ($response) {
                $responseData = json_decode($response, true);
                if (isset($responseData['errors'])) {
                    $errorMessage .= ': ' . (is_array($responseData['errors']) ? implode(', ', $responseData['errors']) : $responseData['errors']);
                }
                // Log error response data
                log_message('error', 'Haravan Connector - Error Response Data: ' . json_encode($responseData));
            }
            
            return [
                'success' => false,
                'message' => $errorMessage
            ];
        }
    }
    
    /**
     * Get basic information about the Haravan shop
     * 
     * @param array $config Login configuration
     * @param string $blogsResponse Optional blogs API response
     * @return array Shop information
     */
    private function getShopInfo(array $config, $blogsResponse = null)
    {
        $shopInfo = [
            'name' => '',
            'url' => rtrim($config['shop_url'], '/'),
            'blogs_count' => 0,
            'blogs' => [],
            'domains' => [],
            'description' => ''
        ];
        
        // Parse blogs data if provided
        if ($blogsResponse) {
            $blogsData = json_decode($blogsResponse, true);
            if (isset($blogsData['blogs']) && is_array($blogsData['blogs'])) {
                $shopInfo['blogs_count'] = count($blogsData['blogs']);
                
                // Get basic info about each blog
                foreach ($blogsData['blogs'] as $blog) {
                    if (isset($blog['title']) && isset($blog['id'])) {
                        $shopInfo['blogs'][] = [
                            'id' => $blog['id'],
                            'title' => $blog['title'],
                            'articles_count' => $blog['articles_count'] ?? 0
                        ];
                    }
                }
            }
        }
        
        // Get shop information from the API
        $url = rtrim($config['shop_url'], '/') . '/admin/shop.json';
        
        // Set up cURL request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        // Set up authentication - prioritize Bearer Token if available
        $headers = ['Content-Type: application/json'];
        
        if (isset($config['bearer_token']) && !empty($config['bearer_token'])) {
            // Use Bearer Token authentication
            $headers[] = 'Authorization: Bearer ' . $config['bearer_token'];
        } else {
            // Fall back to API key authentication
            $headers[] = 'X-Haravan-Access-Token: ' . $config['api_key'];
        }
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        // Execute request
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        // Parse shop data if available
        if (!$error && $httpCode >= 200 && $httpCode < 300) {
            $shopData = json_decode($response, true);
            
            if (isset($shopData['shop']) && is_array($shopData['shop'])) {
                $shop = $shopData['shop'];
                $shopInfo['name'] = $shop['name'] ?? '';
                $shopInfo['description'] = $shop['description'] ?? '';
                
                // Get shop domains
                if (isset($shop['domain']) && !empty($shop['domain'])) {
                    $shopInfo['domains'][] = $shop['domain'];
                }
                
                if (isset($shop['my_shop']) && is_array($shop['my_shop'])) {
                    if (isset($shop['my_shop']['domain']) && !empty($shop['my_shop']['domain'])) {
                        $shopInfo['domains'][] = $shop['my_shop']['domain'];
                    }
                    
                    if (isset($shop['my_shop']['subdomain']) && !empty($shop['my_shop']['subdomain'])) {
                        $shopInfo['domains'][] = $shop['my_shop']['subdomain'] . '.myharavan.com';
                    }
                }
                
                // Ensure unique domains
                $shopInfo['domains'] = array_unique($shopInfo['domains']);
            }
        }
        
        return $shopInfo;
    }
    
    /**
     * Get blog categories from Haravan
     * 
     * @param array $config Login configuration
     * @return array ['success' => bool, 'categories' => array, 'message' => string]
     */
    public function getCategories(array $config)
    {
        // Log start of operation
        log_message('debug', 'Haravan Connector - Getting categories - Starting operation');
        
        // Validate config
        $validation = $this->validateConfig($config);
        if (!$validation['success']) {
            log_message('error', 'Haravan Connector - Config validation failed: ' . $validation['message']);
            return [
                'success' => false,
                'categories' => [],
                'message' => $validation['message']
            ];
        }
        
        // First, get all blogs
        log_message('debug', 'Haravan Connector - Getting blogs list');
        $blogs = $this->getBlogs($config);
        
        if (!$blogs['success']) {
            log_message('error', 'Haravan Connector - Failed to get blogs: ' . $blogs['message']);
            return [
                'success' => false,
                'categories' => [],
                'message' => $blogs['message']
            ];
        }
        
        // If no blogs found
        if (empty($blogs['blogs'])) {
            log_message('info', 'Haravan Connector - No blogs found');
            return [
                'success' => true,
                'categories' => [],
                'message' => 'No blogs found'
            ];
        }
        
        log_message('debug', 'Haravan Connector - Found ' . count($blogs['blogs']) . ' blogs');
        
        // Get categories for each blog
        $allCategories = [];
        
        foreach ($blogs['blogs'] as $blog) {
            log_message('debug', 'Haravan Connector - Processing blog: ' . $blog['id'] . ' - ' . ($blog['title'] ?? 'Untitled'));
            
            // Prepare request URL for blog articles
            $url = rtrim($config['shop_url'], '/') . '/admin/blogs/' . $blog['id'] . '/articles.json?limit=1';
            log_message('debug', 'Haravan Connector - Request URL: ' . $url);
            
            // Set up cURL request
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            // Set up authentication - prioritize Bearer Token if available
            $headers = ['Content-Type: application/json'];
            
            if (isset($config['bearer_token']) && !empty($config['bearer_token'])) {
                // Use Bearer Token authentication
                $headers[] = 'Authorization: Bearer ' . $config['bearer_token'];
                log_message('debug', 'Haravan Connector - Using Bearer Token authentication');
            } else {
                // Fall back to API key authentication
                $headers[] = 'X-Haravan-Access-Token: ' . $config['api_key'];
                log_message('debug', 'Haravan Connector - Using API Key authentication');
            }
            
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            
            // Execute request
            log_message('debug', 'Haravan Connector - Executing cURL request for blog ' . $blog['id']);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            // Log response code
            log_message('debug', 'Haravan Connector - HTTP Response Code for blog ' . $blog['id'] . ': ' . $httpCode);
            
            // Skip if error
            if ($error) {
                log_message('error', 'Haravan Connector - cURL error for blog ' . $blog['id'] . ': ' . $error);
                continue;
            }
            
            if ($httpCode < 200 || $httpCode >= 300) {
                log_message('error', 'Haravan Connector - HTTP error for blog ' . $blog['id'] . ': ' . $httpCode . ' - Response: ' . substr($response, 0, 500));
                continue;
            }
            
            // Add blog as a category
            $allCategories[] = [
                'id' => 'blog_' . $blog['id'],
                'name' => $blog['title'] . ' (Blog)',
                'parent' => 0,
                'count' => $blog['articles_count'] ?? 0
            ];
            
            log_message('debug', 'Haravan Connector - Successfully added blog ' . $blog['id'] . ' as category');
        }
        
        log_message('info', 'Haravan Connector - Successfully processed all blogs. Total categories: ' . count($allCategories));
        
        return [
            'success' => true,
            'categories' => $allCategories,
            'message' => 'Categories retrieved successfully'
        ];
    }
    
    /**
     * Get all blogs from Haravan
     * 
     * @param array $config Login configuration
     * @return array ['success' => bool, 'blogs' => array, 'message' => string]
     */
    private function getBlogs(array $config)
    {
        // Prepare request URL
        $url = rtrim($config['shop_url'], '/') . '/admin/blogs.json';
        
        // Set up cURL request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        // Set up authentication - prioritize Bearer Token if available
        $headers = ['Content-Type: application/json'];
        
        if (isset($config['bearer_token']) && !empty($config['bearer_token'])) {
            // Use Bearer Token authentication
            $headers[] = 'Authorization: Bearer ' . $config['bearer_token'];
        } else {
            // Fall back to API key authentication
            $headers[] = 'X-Haravan-Access-Token: ' . $config['api_key'];
        }
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        // Execute request
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        // Check for errors
        if ($error) {
            return [
                'success' => false,
                'blogs' => [],
                'message' => 'Connection error: ' . $error
            ];
        }
        
        // Check HTTP status code
        if ($httpCode >= 200 && $httpCode < 300) {
            $responseData = json_decode($response, true);
            
            if (!is_array($responseData) || !isset($responseData['blogs'])) {
                return [
                    'success' => false,
                    'blogs' => [],
                    'message' => 'Invalid response format'
                ];
            }
            
            return [
                'success' => true,
                'blogs' => $responseData['blogs'],
                'message' => 'Blogs retrieved successfully'
            ];
        } else {
            $errorMessage = 'HTTP Error ' . $httpCode;
            
            // Try to parse error message from response
            if ($response) {
                $responseData = json_decode($response, true);
                if (isset($responseData['errors'])) {
                    $errorMessage .= ': ' . (is_array($responseData['errors']) ? implode(', ', $responseData['errors']) : $responseData['errors']);
                }
            }
            
            return [
                'success' => false,
                'blogs' => [],
                'message' => $errorMessage
            ];
        }
    }
    
    /**
     * Publish a post to Haravan
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
        
        // Check if category_id exists and is valid
        if (!isset($post['category_id']) || empty($post['category_id'])) {
            return [
                'success' => false,
                'post_id' => 0,
                'post_url' => '',
                'message' => 'Missing category ID'
            ];
        }
        
        // Parse blog ID from category ID
        $category_parts = explode('_', $post['category_id']);
        if (!isset($category_parts[1]) || !is_numeric($category_parts[1])) {
            return [
                'success' => false,
                'post_id' => 0,
                'post_url' => '',
                'message' => 'Invalid category ID format'
            ];
        }
        
        $blog_id = $category_parts[1];
        
        // Prepare request URL
        $url = rtrim($config['shop_url'], '/') . '/admin/blogs/' . $blog_id . '/articles.json';
        
        // Prepare post data
        $article_data = [
            'article' => [
                'title' => $post['title'],
                'body_html' => $post['content'],
                'published' => true
            ]
        ];
        
        // Add tags if available
        if (isset($post['tags']) && !empty($post['tags'])) {
            $article_data['article']['tags'] = is_array($post['tags']) ? implode(', ', $post['tags']) : $post['tags'];
        }
        
        // Add featured image if available
        if (isset($post['featured_image']) && !empty($post['featured_image'])) {
            $article_data['article']['image'] = ['src' => $post['featured_image']];
        }
        
        // Add summary if available
        if (isset($post['excerpt']) && !empty($post['excerpt'])) {
            $article_data['article']['summary_html'] = $post['excerpt'];
        }
        
        // Convert post data to JSON
        $post_json = json_encode($article_data);
        
        // Set up cURL request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_json);
        
        // Set up authentication - prioritize Bearer Token if available
        $headers = ['Content-Type: application/json'];
        
        if (isset($config['bearer_token']) && !empty($config['bearer_token'])) {
            // Use Bearer Token authentication
            $headers[] = 'Authorization: Bearer ' . $config['bearer_token'];
        } else {
            // Fall back to API key authentication
            $headers[] = 'X-Haravan-Access-Token: ' . $config['api_key'];
        }
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
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
            
            if (!is_array($responseData) || !isset($responseData['article']) || !isset($responseData['article']['id'])) {
                return [
                    'success' => false,
                    'post_id' => 0,
                    'post_url' => '',
                    'message' => 'Invalid response format'
                ];
            }
            
            $article = $responseData['article'];
            
            return [
                'success' => true,
                'post_id' => $article['id'],
                'post_url' => isset($article['url']) ? $article['url'] : rtrim($config['shop_url'], '/') . '/blogs/' . $blog_id . '/articles/' . $article['id'],
                'message' => 'Article published successfully'
            ];
        } else {
            $errorMessage = 'HTTP Error ' . $httpCode;
            
            // Try to parse error message from response
            if ($response) {
                $responseData = json_decode($response, true);
                if (isset($responseData['errors'])) {
                    $errorMessage .= ': ' . (is_array($responseData['errors']) ? implode(', ', $responseData['errors']) : $responseData['errors']);
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
     * Get required login fields for Haravan
     * 
     * @return array List of required fields
     */
    public function getLoginFields()
    {
        // Prioritize bearer_token by putting it first in the list
        return ['shop_url', 'bearer_token', 'api_key', 'password'];
    }
    
    /**
     * Validate Haravan login configuration
     * 
     * @param array $config Login configuration
     * @return array ['success' => bool, 'message' => string]
     */
    public function validateConfig(array $config)
    {
        // Only shop_url is absolutely required
        if (!isset($config['shop_url']) || empty($config['shop_url'])) {
            return [
                'success' => false,
                'message' => 'Missing required field: shop_url'
            ];
        }
        
        // Check that at least one authentication method is provided
        if ((!isset($config['bearer_token']) || empty($config['bearer_token'])) &&
            (!isset($config['api_key']) || empty($config['api_key']))) {
            return [
                'success' => false,
                'message' => 'Missing authentication: either bearer_token or api_key is required'
            ];
        }
        
        // Validate URL format
        if (!filter_var($config['shop_url'], FILTER_VALIDATE_URL)) {
            return [
                'success' => false,
                'message' => 'Invalid shop URL format'
            ];
        }
        
        return [
            'success' => true,
            'message' => 'Configuration is valid'
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
        // Validate config
        $validation = $this->validateConfig($config);
        if (!$validation['success']) {
            log_message('error', 'Haravan Connector - get_tags: Config validation failed: ' . $validation['message']);
            return [
                'success' => false,
                'message' => $validation['message']
            ];
        }
        
        // Lấy các tham số tùy chọn
        $page = isset($options['page']) ? (int)$options['page'] : 1;
        $per_page = isset($options['per_page']) ? (int)$options['per_page'] : 20;
        
        // Prepare request URL
        $url = rtrim($config['shop_url'], '/') . '/admin/blogs/' . $blog_id . '/tags.json';
        $url .= '?limit=' . $per_page . '&page=' . $page;
        
        log_message('debug', 'Haravan Connector - get_tags: Request URL: ' . $url);
        
        // Set up cURL request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        // Set up authentication - prioritize Bearer Token if available
        $headers = ['Content-Type: application/json'];
        
        if (isset($config['bearer_token']) && !empty($config['bearer_token'])) {
            // Use Bearer Token authentication
            $headers[] = 'Authorization: Bearer ' . $config['bearer_token'];
        } else {
            // Fall back to API key authentication
            $headers[] = 'X-Haravan-Access-Token: ' . $config['api_key'];
        }
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        // Execute request
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        // Log response for debugging
        log_message('debug', 'Haravan Connector - get_tags: HTTP Code: ' . $httpCode);
        
        // Check for errors
        if (curl_error($ch)) {
            log_message('error', 'Haravan Connector - get_tags: cURL Error: ' . curl_error($ch));
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
            if ($response) {
                $responseData = json_decode($response, true);
                if (isset($responseData['errors'])) {
                    $errorMessage .= ': ' . json_encode($responseData['errors']);
                }
            }
            
            log_message('error', 'Haravan Connector - get_tags: ' . $errorMessage);
            return [
                'success' => false,
                'message' => $errorMessage
            ];
        }
        
        // Parse response
        $responseData = json_decode($response, true);
        
        if (!isset($responseData['tags']) || !is_array($responseData['tags'])) {
            log_message('error', 'Haravan Connector - get_tags: Invalid response format');
            return [
                'success' => false,
                'message' => 'Invalid response format'
            ];
        }
        
        $tags = $responseData['tags'];
        
        // Get pagination info from headers or response
        $totalItems = count($tags);
        $totalPages = 1;
        
        // Haravan API doesn't provide total count in headers, so we need to estimate
        if (count($tags) >= $per_page) {
            // If we got a full page, there might be more
            $totalPages = $page + 1;
        }
        
        // Format tags data
        $formattedTags = [];
        foreach ($tags as $tag) {
            $formattedTags[] = [
                'id' => $tag['id'],
                'name' => $tag['title'],
                'slug' => $tag['handle'],
                'count' => $tag['popularity'] ?? 0,
                'url' => $config['shop_url'] . '/blogs/' . $blog_id . '/tagged/' . $tag['handle']
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
     * Check if post with similar title exists
     * @param array $config Login configuration
     * @param string $title Post title to check
     * @param int|string $blog_id Blog ID
     * @return array Result with standard format
     */
    public function check_post_exists($config, $title, $blog_id)
    {
        // Validate config and parameters
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
            // Build the API endpoint URL for searching articles
            $api_url = $this->get_api_url($config['url']) . 'articles.json';
            
            // Add query parameters for search
            $params = [
                'title' => $title,
                'published_status' => 'any',
                'blog_id' => $blog_id,
                'limit' => 5 // Limit to 5 results
            ];
            
            $api_url .= '?' . http_build_query($params);
            
            // Setup the request
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $api_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            
            // Add authentication headers
            $headers = [
                'Content-Type: application/json',
                'Accept: application/json'
            ];
            
            if (!empty($config['api_key'])) {
                $headers[] = 'X-Haravan-Access-Token: ' . $config['api_key'];
            }
            
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            
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
            
            $data = json_decode($response, true);
            $articles = isset($data['articles']) ? $data['articles'] : [];
            
            // If no articles found
            if (empty($articles)) {
                return [
                    'exists' => false,
                    'permalink' => '',
                    'similarity' => 0,
                    'http_code' => $http_code
                ];
            }
            
            // Check each article for title similarity
            $title_lower = strtolower(trim($title));
            $best_match = null;
            $best_similarity = 0;
            
            foreach ($articles as $article) {
                $article_title = isset($article['title']) ? 
                    strtolower(trim($article['title'])) : '';
                
                // Calculate similarity
                $similarity = 0;
                if (!empty($article_title) && !empty($title_lower)) {
                    // Use levenshtein distance for similarity
                    $lev = levenshtein($title_lower, $article_title);
                    $max_len = max(strlen($title_lower), strlen($article_title));
                    
                    // Convert to similarity score (0-1)
                    $similarity = 1 - ($lev / $max_len);
                }
                
                // If this is the most similar article so far
                if ($similarity > $best_similarity) {
                    $best_similarity = $similarity;
                    $best_match = $article;
                }
                
                // Exact match found
                if ($article_title === $title_lower) {
                    $permalink = $config['url'] . 'blogs/' . $blog_id . '/' . $article['handle'];
                    
                    return [
                        'exists' => true,
                        'permalink' => $permalink,
                        'similarity' => 1.0,
                        'http_code' => $http_code,
                        'post_id' => $article['id'],
                        'post_title' => $article['title'],
                        'post_status' => $article['published_at'] ? 'published' : 'draft'
                    ];
                }
            }
            
            // If we found a good match (over 90% similarity)
            if ($best_similarity >= 0.9 && $best_match) {
                $permalink = $config['url'] . 'blogs/' . $blog_id . '/' . $best_match['handle'];
                
                return [
                    'exists' => true,
                    'permalink' => $permalink,
                    'similarity' => $best_similarity,
                    'http_code' => $http_code,
                    'post_id' => $best_match['id'],
                    'post_title' => $best_match['title'],
                    'post_status' => $best_match['published_at'] ? 'published' : 'draft'
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
} 