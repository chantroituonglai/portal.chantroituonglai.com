<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Feature Image Selector Controller
 * 
 * Xử lý việc hiển thị file browser riêng cho feature image
 * không phụ thuộc vào TinyMCE để tránh lỗi
 */
class Feature_image_selector extends AdminController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Hiển thị file browser với elFinder
     * Điều này tránh phụ thuộc vào TinyMCE
     */
    public function index()
    {
        $data['connector']   = admin_url() . '/utilities/media_connector';
        $data['mediaLocale'] = get_media_locale();
        
        // Load CSS
        $this->app_css->add('app-css', base_url($this->app_css->core_file('assets/css', 'style.css')) . '?v=' . $this->app_css->core_version(), 'editor-media');
        
        // Sử dụng view tùy chỉnh của chúng ta
        $this->load->view('includes/feature_image_fileBrowser', $data);
    }
} 