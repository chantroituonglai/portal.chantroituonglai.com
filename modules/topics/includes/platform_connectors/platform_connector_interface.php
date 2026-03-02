<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Platform Connector Interface
 * Interface for all platform connectors
 */
interface PlatformConnectorInterface
{
    /**
     * Get blogs from platform
     * @param array $config Login configuration
     * @return array Blogs data or false on failure
     */
    public function get_blogs($config);

    /**
     * Get categories from platform
     * @param array $config Login configuration
     * @param int|string $blog_id Blog ID
     * @return array Categories data or false on failure
     */
    public function get_categories($config, $blog_id);

    /**
     * Get tags from platform
     * @param array $config Login configuration
     * @param int|string $blog_id Blog ID
     * @param array $options Options for the request (e.g. page, per_page)
     * @return array Tags data with standard format:
     *  [
     *      'data' => [...],           // Mảng các tag
     *      'total_pages' => int,      // Tổng số trang
     *      'total_items' => int,      // Tổng số tag
     *      'http_code' => int         // Mã HTTP của API request
     *  ]
     *  hoặc false nếu thất bại
     */
    public function get_tags($config, $blog_id, $options = []);

    /**
     * Get posts from platform
     * @param array $config Login configuration
     * @param int|string $blog_id Blog ID
     * @param array $options Options for the request (e.g. page, per_page, category, tag)
     * @return array Posts data or false on failure
     */
    public function get_posts($config, $blog_id, $options = []);

    /**
     * Create post on platform
     * @param array $config Login configuration
     * @param int|string $blog_id Blog ID
     * @param array $post_data Post data
     * @return array|bool Created post data or false on failure
     */
    public function create_post($config, $blog_id, $post_data);

    /**
     * Update post on platform
     * @param array $config Login configuration
     * @param int|string $blog_id Blog ID
     * @param int|string $post_id Post ID
     * @param array $post_data Post data
     * @return array|bool Updated post data or false on failure
     */
    public function update_post($config, $blog_id, $post_id, $post_data);

    /**
     * Upload media to platform
     * @param array $config Login configuration
     * @param int|string $blog_id Blog ID
     * @param string $file_path Local file path
     * @param array $media_data Media data (title, caption, etc.)
     * @return array|bool Uploaded media data or false on failure
     */
    public function upload_media($config, $blog_id, $file_path, $media_data = []);
    
    /**
     * Get connection status
     * @param array $config Login configuration
     * @return bool True if connected successfully, false otherwise
     */
    public function test_connection($config);
    
    /**
     * Check if post with similar title exists
     * @param array $config Login configuration
     * @param string $title Post title to check
     * @param int|string $blog_id Blog ID
     * @return array Result with standard format:
     *  [
     *      'exists' => bool,          // true if post exists, false otherwise
     *      'permalink' => string,     // URL to the existing post if found
     *      'similarity' => float,     // Similarity score (0-1) if applicable
     *      'http_code' => int         // HTTP code of the API request
     *  ]
     */
    public function check_post_exists($config, $title, $blog_id);
} 