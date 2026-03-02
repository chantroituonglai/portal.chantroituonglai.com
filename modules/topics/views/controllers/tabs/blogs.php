<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<style>
.blogs-container {
    margin-top: 15px;
}

.blog-card {
    border: 1px solid #e9ecef;
    border-radius: 8px;
    margin-bottom: 20px;
    transition: all 0.3s ease;
    background-color: #fff;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    overflow: hidden;
}

.blog-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.blog-header {
    display: flex;
    align-items: center;
    padding: 16px;
    border-bottom: 1px solid #e9ecef;
    background-color: #f8f9fa;
}

.blog-title {
    font-size: 16px;
    font-weight: 600;
    margin: 0;
    flex: 1;
    color: #2d3748;
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.blog-date {
    color: #64748b;
    font-size: 12px;
    margin-left: 10px;
    white-space: nowrap;
}

.blog-content {
    padding: 16px;
}

.blog-excerpt {
    color: #4b5563;
    font-size: 14px;
    margin-bottom: 15px;
    line-height: 1.6;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
    height: 65px;
}

.blog-meta {
    display: flex;
    align-items: center;
    margin-top: 15px;
    color: #64748b;
    font-size: 12px;
    flex-wrap: wrap;
}

.meta-item {
    display: flex;
    align-items: center;
    margin-right: 15px;
    margin-bottom: 5px;
}

.meta-item i {
    margin-right: 5px;
    color: #4a5568;
}

.blog-image {
    width: 100%;
    height: 180px;
    object-fit: cover;
    border-radius: 4px;
    margin-bottom: 15px;
    border: 1px solid #e9ecef;
}

.blog-image-placeholder {
    width: 100%;
    height: 180px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #f8f9fa;
    color: #adb5bd;
    border-radius: 4px;
    margin-bottom: 15px;
    border: 1px dashed #ced4da;
}

.blog-actions {
    text-align: right;
    padding: 12px 16px;
    border-top: 1px solid #e9ecef;
    background-color: #f8f9fa;
}

.blog-tag {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 12px;
    background-color: #e9ecef;
    color: #4b5563;
    font-size: 11px;
    margin-right: 5px;
    margin-bottom: 5px;
    font-weight: 500;
}

.blog-category {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 12px;
    background-color: #e0f2fe;
    color: #0284c7;
    font-size: 11px;
    margin-right: 5px;
    margin-bottom: 5px;
    font-weight: 500;
    border: 1px solid #bae6fd;
}

.sync-info {
    font-size: 12px;
    color: #64748b;
    display: flex;
    align-items: center;
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px dashed #e9ecef;
}

.sync-info i {
    margin-right: 5px;
    color: #4a5568;
}

.sync-date {
    font-style: italic;
}

.filter-card {
    margin-bottom: 20px;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 16px;
    background-color: #fff;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.category-select {
    width: 100%;
    margin-bottom: 15px;
}

.filter-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 15px;
}

.filter-actions .selected-info {
    font-size: 14px;
    color: #475569;
}

.category-badge {
    display: inline-block;
    padding: 8px 12px;
    border-radius: 20px;
    margin-right: 10px;
    margin-bottom: 10px;
    color: #fff;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s ease;
    border: 1px solid transparent;
}

.category-badge i {
    margin-right: 5px;
}

.category-badge .sync-time {
    font-size: 11px;
    margin-left: 5px;
    opacity: 0.9;
    font-style: italic;
}

.category-badge.synced {
    background-color: #0ea5e9;
}

.category-badge.not-synced {
    background-color: #64748b;
}

.category-badge.selected-category {
    background-color: #0284c7;
    border: 1px solid #fff;
    box-shadow: 0 0 0 2px #0284c7, 0 2px 5px rgba(0,0,0,0.2);
    transform: translateY(-3px);
    position: relative;
}

.category-badge.selected-category::after {
    content: "✓";
    margin-left: 5px;
    font-weight: bold;
}

.selected-category-info {
    margin-top: 10px;
    margin-bottom: 15px;
    padding: 10px 15px;
    background-color: #f0f9ff;
    border-left: 3px solid #0ea5e9;
    border-radius: 4px;
    display: none;
    font-size: 14px;
}

.selected-category-info.active {
    display: block;
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-5px); }
    to { opacity: 1; transform: translateY(0); }
}

.category-badge:hover {
    transform: translateY(-3px);
    box-shadow: 0 3px 8px rgba(0,0,0,0.1);
    background-color: #0891cf;
}

.category-badge.not-synced:hover {
    background-color: #475569;
}

.success-pulse {
    display: inline-block;
    margin-left: 5px;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background-color: #10b981;
    animation: pulse 1.5s infinite;
}

.error-pulse {
    display: inline-block;
    margin-left: 5px;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background-color: #ef4444;
    animation: pulse 1.5s infinite;
}

@keyframes pulse {
    0% {
        box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
    }
    70% {
        box-shadow: 0 0 0 10px rgba(16, 185, 129, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(16, 185, 129, 0);
    }
}

.syncing-indicator {
    display: inline-flex;
    align-items: center;
    animation: fadeIn 0.3s ease;
}

.btn-primary {
    background-color: #0ea5e9;
    border-color: #0284c7;
}

.btn-primary:hover {
    background-color: #0284c7;
    border-color: #0284c7;
}

.btn-info {
    background-color: #38bdf8;
    border-color: #0ea5e9;
}

.btn-info:hover {
    background-color: #0ea5e9;
    border-color: #0ea5e9;
}

#blogs_loading {
    padding: 30px 0;
}

.alert-info {
    background-color: #f0f9ff;
    border-color: #bae6fd;
    color: #0369a1;
}

.spinner {
  margin: 0 auto;
  width: 70px;
  text-align: center;
}

.spinner > div {
  width: 16px;
  height: 16px;
  background-color: #0ea5e9;
  border-radius: 100%;
  display: inline-block;
  -webkit-animation: sk-bouncedelay 1.4s infinite ease-in-out both;
  animation: sk-bouncedelay 1.4s infinite ease-in-out both;
}

.spinner .bounce1 {
  -webkit-animation-delay: -0.32s;
  animation-delay: -0.32s;
}

.spinner .bounce2 {
  -webkit-animation-delay: -0.16s;
  animation-delay: -0.16s;
}

@-webkit-keyframes sk-bouncedelay {
  0%, 80%, 100% { -webkit-transform: scale(0) }
  40% { -webkit-transform: scale(1.0) }
}

@keyframes sk-bouncedelay {
  0%, 80%, 100% { 
    -webkit-transform: scale(0);
    transform: scale(0);
  } 40% { 
    -webkit-transform: scale(1.0);
    transform: scale(1.0);
  }
}

.data-source-toggle {
    padding: 5px;
    background-color: #f8f9fa;
    border-radius: 4px;
    display: inline-block;
    margin-right: 10px;
}

/* Styling cho loading indicator */
.loading-wrapper {
    position: relative;
}

#loading_timeout_warning {
    transition: all 0.3s ease;
}

/* Hiệu ứng cho progress bar */
.progress {
    height: 6px;
    background-color: #f0f9ff;
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 15px;
}

.progress-bar {
    background-color: #0ea5e9;
    transition: width 0.5s ease;
}

.progress-bar-striped {
    background-image: linear-gradient(45deg, 
        rgba(255, 255, 255, 0.15) 25%, 
        transparent 25%, 
        transparent 50%, 
        rgba(255, 255, 255, 0.15) 50%, 
        rgba(255, 255, 255, 0.15) 75%, 
        transparent 75%, 
        transparent);
    background-size: 1rem 1rem;
}

/* Thêm CSS cho cấu trúc cây phân cấp */
.category-tree {
    padding: 0;
    margin: 0;
    font-family: Arial, sans-serif;
}

.category-tree ul {
    list-style: none;
    padding-left: 20px;
    margin: 0;
}

.category-tree li {
    margin: 3px 0;
    position: relative;
}

.category-tree .expander {
    display: inline-block;
    width: 16px;
    height: 16px;
    text-align: center;
    line-height: 16px;
    cursor: pointer;
    margin-right: 3px;
    font-weight: bold;
    color: #555;
}

.category-tree .category-node {
    display: flex;
    align-items: center;
    margin: 2px 0;
}

.category-tree .category-icon {
    margin-right: 5px;
    color: #555;
}

.category-tree .category-checkbox {
    margin-right: 5px;
}

.category-tree .category-label {
    cursor: pointer;
    font-size: 13px;
    color: #333;
}

.category-tree .category-label.disabled {
    color: #999;
    font-style: italic;
}

.category-tree .child-categories {
    display: none;
}

.category-tree .child-categories.expanded {
    display: block;
}

.category-tree-controls {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
}

.category-tree-controls .btn {
    font-size: 12px;
    padding: 2px 5px;
    background: #f5f5f5;
    border: 1px solid #ddd;
    color: #333;
}

.categories-container {
    max-height: 400px;
    overflow-y: auto;
    border: 1px solid #ddd;
    padding: 5px;
    background-color: #fff;
}

.category-filter-search {
    position: relative;
    margin-bottom: 10px;
}

.category-filter-search input {
    padding: 5px 5px 5px 25px;
    border: 1px solid #ddd;
    width: 100%;
}

.category-filter-search i {
    position: absolute;
    left: 7px;
    top: 8px;
    color: #777;
}
</style>

<div class="blogs-container">
    <div class="row mbot15">
        <div class="col-md-6">
            <h4><?php echo _l('controller_website_blogs'); ?></h4>
        </div>
        <div class="col-md-6 text-right">
            <button type="button" class="btn btn-info" id="sync_blogs">
                <i class="fa fa-refresh"></i> <?php echo _l('controller_sync_data'); ?>
            </button>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-12">
            <div class="view-options text-right" style="display:none;">
                <div class="btn-group">
                    <button type="button" class="btn btn-default active" id="view_mode_cards">
                        <i class="fa fa-th"></i> <?php echo _l('card_view'); ?>
                    </button>
                    <button type="button" class="btn btn-default" id="view_mode_table">
                        <i class="fa fa-table"></i> <?php echo _l('table_view'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-12">
            <div class="panel_s">
                <div class="panel-body">
                    <!-- Filter section -->
                    <div class="filter-card">
                        <h5><?php echo _l('controller_filter_by_category'); ?></h5>
                        <p class="text-muted"><?php echo _l('controller_select_category_to_sync'); ?></p>
                        
                        <!-- Categories section -->
                        <div id="categories_container" class="mbot15">
                            <div id="categories_loading" class="blogs-categories_loading text-center">
                                <i class="fa fa-spinner fa-spin"></i> <?php echo _l('loading_categories'); ?>
                            </div>
                            
                            <div id="categories_content" class="mtop10" style="display:none;">
                                <!-- Filter and tree controls -->
                                <div class="category-filter-search">
                                    <i class="fa fa-search"></i>
                                    <input type="text" class="form-control" id="category_search" placeholder="<?php echo _l('controller_category_search'); ?>">
                                </div>
                                
                                <div class="category-tree-controls">
                                    <div>
                                        <button type="button" class="btn btn-xs btn-default" id="expand_all_categories">
                                            <i class="fa fa-plus-square"></i> <?php echo _l('controller_category_expand_all'); ?>
                                        </button>
                                        <button type="button" class="btn btn-xs btn-default" id="collapse_all_categories">
                                            <i class="fa fa-minus-square"></i> <?php echo _l('controller_category_collapse_all'); ?>
                                        </button>
                                    </div>
                                    <div>
                                        <span class="text-muted small" id="categories_count"></span>
                                    </div>
                                </div>
                                
                                <div class="categories-container">
                                    <div class="category-tree" id="category_tree"></div>
                                </div>
                            </div>
                            
                            <div id="no_categories" class="alert alert-info mtop10" style="display:none;">
                                <?php echo _l('no_categories_found'); ?>
                            </div>
                            
                            <!-- Selected category info -->
                            <div id="selected_category_info" class="selected-category-info">
                                <i class="fa fa-check-circle"></i> <?php echo _l('controller_selected_category'); ?>: <strong id="selected_category_name"></strong>
                            </div>
                        </div>
                        
                        <div class="filter-actions">
                            <div class="selected-info" id="selected_info">
                                <span id="no_selection"><?php echo _l('controller_no_category_selected'); ?></span>
                            </div>
                            <div>
                                <!-- Thay thế dropdown bằng toggle switch -->
                                <div class="data-source-toggle mtop5 mbot10">
                                    <label class="checkbox-inline">
                                        <input type="checkbox" id="direct_api_toggle" value="1">
                                        <span class="text-info"><i class="fa fa-cloud"></i> <?php echo _l('direct_from_api'); ?></span>
                                    </label>
                                    <span class="text-muted small"><?php echo _l('may_take_longer'); ?></span>
                                </div>
                                
                                <!-- Nút Apply Filter -->
                                <button type="button" class="btn btn-primary" id="apply_filter">
                                    <i class="fa fa-filter"></i> <?php echo _l('controller_apply_filter'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Loading indicator cải tiến -->
                    <div id="blogs_loading" class="text-center mtop20 mbot20">
                        <div class="spinner">
                            <div class="bounce1"></div>
                            <div class="bounce2"></div>
                            <div class="bounce3"></div>
                        </div>
                        <p class="mtop10" id="loading_text"><?php echo _l('loading_blogs'); ?></p>
                        <div class="progress mtop10" style="height:6px;">
                            <div id="loading_progress" class="progress-bar progress-bar-striped active" role="progressbar" 
                                aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width:0%">
                            </div>
                        </div>
                        <div id="loading_timeout_warning" class="alert alert-warning mtop10" style="display:none;">
                            <i class="fa fa-exclamation-triangle"></i> <?php echo _l('loading_taking_long'); ?> <span id="loading_elapsed">0</span>s
                        </div>
                    </div>
                    
                    <!-- Blogs list container -->
                    <div id="blogs_list" class="row mtop10" style="display:none;">
                        <!-- Blogs will be loaded dynamically here in card view -->
                    </div>
                    
                    <!-- Table view for blogs -->
                    <div id="blogs_table_container" class="mtop10" style="display:none;">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered" id="blogs_table">
                                <thead>
                                    <tr>
                                        <th><?php echo _l('table_blog_title'); ?></th>
                                        <th><?php echo _l('table_blog_date'); ?></th>
                                        <th><?php echo _l('table_blog_categories'); ?></th>
                                        <th><?php echo _l('table_blog_tags'); ?></th>
                                        <th><?php echo _l('table_blog_actions'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Blogs will be loaded dynamically here in table form -->
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination for table view -->
                        <div id="blogs_pagination" class="pagination-container">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="dataTables_info" id="pagination_info"></div>
                                </div>
                                <div class="col-md-6">
                                    <div class="dataTables_paginate paging_simple_numbers text-right">
                                        <ul class="pagination"></ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- API results notification -->
                    <div id="api_blogs_notification" class="alert alert-info text-center mtop20 mbot20" style="display:none;">
                        <i class="fa fa-info-circle fa-3x"></i>
                        <h4 id="api_blogs_count" class="mtop10"></h4>
                        <p><?php echo _l('sync_to_view_details'); ?></p>
                        <button type="button" class="btn btn-info mtop10" id="sync_api_blogs">
                            <i class="fa fa-refresh"></i> <?php echo _l('sync_blogs_now'); ?>
                        </button>
                    </div>
                    
                    <!-- Empty state -->
                    <div id="no_blogs" class="text-center mtop20 mbot20" style="display:none;">
                        <i class="fa fa-exclamation-circle fa-3x text-warning"></i>
                        <p class="mtop10 no-blogs-message"><?php echo _l('no_blogs_found'); ?></p>
                        <div class="alert alert-info mtop10">
                            <strong><i class="fa fa-info-circle"></i> <?php echo _l('controller_how_to_sync'); ?></strong>
                            <ol class="mtop10 text-left">
                                <li><?php echo _l('controller_select_category_first'); ?></li>
                                <li><?php echo _l('controller_click_apply_filter'); ?></li>
                                <li><?php echo _l('controller_then_sync_blogs'); ?></li>
                            </ol>
                            <p class="text-center mtop10">
                                <?php echo _l('controller_select_category_to_sync'); ?> 
                                <a href="#" id="go_to_categories_tab" class="text-info"><i class="fa fa-arrow-right"></i> <?php echo _l('go_to_categories'); ?></a>
                            </p>
                        </div>
                        <button type="button" class="btn btn-info" id="sync_blogs_empty">
                            <i class="fa fa-refresh"></i> <?php echo _l('sync_blogs_now'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Category selection help -->
<div id="category_selection_help" class="alert alert-info text-center mtop15" style="display:none;">
    <i class="fa fa-lightbulb-o"></i> <?php echo _l('controller_select_category_help'); ?>
</div>

<!-- Thêm trong phần HTML, thêm modal cho nội dung blog -->
<div id="blog_detail_modal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="blog_detail_title">Blog Detail</h4>
            </div>
            <div class="modal-body">
                <div class="text-center" id="blog_detail_loading">
                    <i class="fa fa-spinner fa-spin fa-3x"></i>
                    <p class="mtop10"><?php echo _l('loading_blog_content'); ?></p>
                </div>
                <div id="blog_detail_content" style="display:none;">
                    <div id="blog_detail_featured_image" class="text-center mbot15"></div>
                    <div id="blog_detail_excerpt" class="well"></div>
                    <div class="alert alert-info">
                        <i class="fa fa-info-circle"></i> <?php echo _l('optimized_content_notice'); ?>
                    </div>
                    <div class="text-center">
                        <a href="#" target="_blank" class="btn btn-primary" id="blog_detail_view_full">
                            <i class="fa fa-external-link"></i> <?php echo _l('view_full_article'); ?>
                        </a>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
            </div>
        </div>
    </div>
</div>

<script>
/**
 * Format string with placeholders
 * Similar to sprintf in PHP
 * @param {string} format - Format string with {n} placeholders
 * @param {...any} args - Arguments to replace placeholders
 * @return {string} - Formatted string
 */
function sprintf(format) {
    var args = Array.prototype.slice.call(arguments, 1);
    return format.replace(/{(\d+)}/g, function(match, number) { 
        return typeof args[number] != 'undefined' ? args[number] : match;
    });
}

// Init variables
var controllerId = <?php echo $controller->id; ?>;
var selectedCategoryId = null;
var loadingTimer = null;
var loadingStartTime = null;
var loadingElapsedSeconds = 0;
var loadingTimeout = 60; // Timeout sau 60 giây

function waitForJQuery(callback) {
    if (typeof jQuery !== 'undefined') {
        callback();
    } else {
        setTimeout(function() {
            waitForJQuery(callback);
        }, 100);
    }
}

// Sử dụng hàm waitForJQuery
waitForJQuery(function() {
    $(document).ready(function() {
        $('#blogs_loading').hide();
        // Prevent scroll to top when clicking tabs
        $('.nav-tabs a[role="tab"]').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Show tab without scrolling
            $(this).tab('show');
            
            // Prevent URL hash change
            history.pushState("", document.title, window.location.pathname + window.location.search);
            
            return false;
        });
        
        // Handle sync blogs button
        $('#sync_blogs, #sync_blogs_empty').on('click', function() {
            syncBlogs(controllerId, selectedCategoryId);
        });
        
        // Show selection help if no category selected initially
        setTimeout(function() {
            if (!selectedCategoryId && $('#categories_badges .category-badge').length > 0) {
                $('#category_selection_help').fadeIn('slow');
            }
        }, 1000);
        
        // Handle apply filter button
        $('#apply_filter').on('click', function() {
            // Lấy checkbox được chọn
            var $selectedCheckbox = $('.category-checkbox:checked');
            if ($selectedCheckbox.length > 0) {
                $('#category_selection_help').hide();
                
                // Lấy categoryId từ checkbox đã chọn
                selectedCategoryId = $selectedCheckbox.data('category-id');
                
                // Lấy giá trị của checkbox direct_api_toggle
                var directFetch = $('#direct_api_toggle').prop('checked');
                loadBlogs(controllerId, selectedCategoryId, directFetch);
            } else {
                alert_float('warning', '<?php echo _l('controller_select_category_first'); ?>');
                // Show selection help with animation
                $('#category_selection_help').hide().fadeIn('slow');
            }
        });
        
        // Handle checkbox API toggle
        $('#direct_api_toggle').on('change', function() {
            if ($(this).prop('checked')) {
                // Hiển thị thông báo khi chọn lấy từ API
                alert_float('info', '<?php echo _l('direct_api_selected_notice'); ?>', 4000);
            }
        });
        
        // Handle category badge clicks
        $(document).on('click', '.category-badge', function(e) {
            e.stopPropagation(); // Prevent click from affecting parent elements
            
            // Remove selected class from all badges
            $('.category-badge').removeClass('selected-category');
            
            // Add selected class to clicked badge
            $(this).addClass('selected-category');
            
            // Set selected category ID
            selectedCategoryId = $(this).data('category-id');
            
            // Get selected category name and count
            var categoryName = $(this).clone().children('.category-count-badge, .sync-time').remove().end().text().trim();
            
            // Update selected category info
            $('#selected_category_name').text(categoryName);
            $('#selected_category_info').addClass('active');
            
            // Update selection info in filter actions
            $('#no_selection').hide();
            $('#selected_info').html('<i class="fa fa-check-circle text-success"></i> <strong>' + categoryName + '</strong> <?php echo _l('controller_selected'); ?>');
            
            // Enable apply button with visual cue
            $('#apply_filter').addClass('btn-success').removeClass('btn-primary')
                .html('<i class="fa fa-check"></i> <?php echo _l('controller_apply_filter'); ?>');
            
            // Hide help when a category is selected
            $('#category_selection_help').fadeOut();
        });
        
        // Load categories on page load
        loadBlogCategories(controllerId);

        // Handle view mode switching (cards/table)
        $('#view_mode_cards, #view_mode_table').on('click', function() {
            // Skip if already active
            if ($(this).hasClass('active')) return;
            
            // Toggle active class
            $('.view-options .btn').removeClass('active');
            $(this).addClass('active');
            
            // Get current blogs data
            var blogs = $('#blogs_list').data('blogs');
            var dataSource = $('#blogs_list').data('source');
            var pagination = $('#blogs_list').data('pagination');
            
            if (!blogs || !blogs.length) return;
            
            // Switch view based on button clicked
            if ($(this).attr('id') === 'view_mode_cards') {
                $('#blogs_table_container').hide();
                renderBlogsList(blogs, dataSource);
                $('#blogs_list').show();
            } else {
                $('#blogs_list').hide();
                renderBlogsTable(blogs, dataSource, pagination);
                $('#blogs_table_container').show();
            }
        });
        
        // Handle sync API blogs button
        $(document).on('click', '#sync_api_blogs', function() {
            var data = $('#api_blogs_notification').data();
            if (data && data.controller_id && data.category_id) {
                syncBlogs(data.controller_id, data.category_id);
            }
        });

        // Handle blog detail button click
        $(document).on('click', '.view-blog-detail', function() {
            var blogId = $(this).data('blog-id');
            
            // Find blog in current list
            var blogs = $('#blogs_list').data('blogs');
            var blog = null;
            
            if (blogs) {
                blog = blogs.find(function(b) {
                    return b.blog_id == blogId;
                });
            }
            
            if (blog) {
                showBlogDetail(blog);
            } else {
                alert_float('warning', '<?php echo _l('blog_not_found'); ?>');
            }
        });

        // Handle click on save blog button
        $(document).on('click', '.save-blog', function() {
            var blogId = $(this).data('blog-id');
            saveBlogToDatabase(controllerId, selectedCategoryId, blogId);
        });

        // Thêm xử lý sự kiện cho nút chuyển tab
        $('#go_to_categories_tab').on('click', function(e) {
            e.preventDefault();
            // Chuyển sang tab Categories
            $('.nav-tabs a[href="#categories"]').tab('show');
        });
    });
});

/**
 * Load categories from server
 * @param {number} controllerId - ID of the controller
 */
function loadBlogCategories(controllerId) {
    // Show loading
    $('#categories_content').hide();
    $('#no_categories').hide();
    $('.blogs-categories_loading').show();
    
    // AJAX request to get categories
    $.ajax({
        url: admin_url + 'topics/controllers/get_categories/' + controllerId,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            // Hide loading
            $('.blogs-categories_loading').hide();
            
            if (response.success && response.categories && response.categories.length > 0) {
                renderCategoriesBadges(response.categories);
                $('#categories_content').show();
            } else {
                // Show empty state
                $('#no_categories').show();
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading categories:', error);
            // Hide loading, show empty state
            $('.blogs-categories_loading').hide();
            $('#no_categories').show();
        }
    });
}

/**
 * Check if category has been synced before
 * @param {object} category - Category object
 * @return {boolean} Whether the category has been synced
 */
function hasBeenSynced(category) {
    return category && category.last_sync;
}

/**
 * Render categories as badges
 * @param {Array} categories - List of categories
 */
function renderCategoriesBadges(categories) {
    // Hiển thị container
    $('#categories_badges').hide();
    $('#category_tree').empty();
    $('#categories_content').show();
    
    // Update count
    $('#categories_count').text(categories.length + ' <?php echo _l('controller_category_count'); ?>');
    
    // Convert flat list to tree structure
    var categoryMap = {};
    var rootCategories = [];
    
    // First pass: create map
    categories.forEach(function(category) {
        category.children = [];
        categoryMap[category.category_id] = category;
    });
    
    // Second pass: build tree
    categories.forEach(function(category) {
        if (category.parent_id && categoryMap[category.parent_id]) {
            categoryMap[category.parent_id].children.push(category);
        } else {
            rootCategories.push(category);
        }
    });
    
    // Sort root categories by name
    rootCategories.sort(function(a, b) {
        return a.name.localeCompare(b.name);
    });
    
    // Render tree
    renderCategoryTree(rootCategories, $('#category_tree'));
    
    // Initialize expand/collapse icons
    initializeTreeBehavior();
}

/**
 * Render category tree recursively
 */
function renderCategoryTree(categories, parentElement) {
    var ul = $('<ul class="list-unstyled">');
    
    categories.forEach(function(category) {
        var li = $('<li>');
        var hasChildren = category.children && category.children.length > 0;
        
        // Create node
        var nodeDiv = $('<div class="category-node">');
        
        // Add expander if has children
        if (hasChildren) {
            nodeDiv.append('<span class="expander">+</span>');
        } else {
            nodeDiv.append('<span class="expander" style="visibility:hidden">+</span>');
        }
        
        // Add folder icon
        var folderIcon = hasChildren ? 'fa-folder' : 'fa-folder-o';
        nodeDiv.append('<i class="fa ' + folderIcon + ' category-icon"></i>');
        
        // Add checkbox
        var hasSynced = hasBeenSynced(category);
        var disabledClass = !hasSynced ? ' disabled' : '';
        var disabledAttr = !hasSynced ? ' disabled' : '';
        
        nodeDiv.append('<input type="checkbox" class="category-checkbox" data-category-id="' + category.category_id + '"' + disabledAttr + '>');
        
        // Prepare counts for both API and database
        var dbCount = category.db_count !== undefined ? category.db_count : 0;
        var apiCount = category.api_count !== undefined ? category.api_count : (category.count || 0);
        
        // Format counts with appropriate styling
        var countStyle = '';
        if (dbCount < apiCount) {
            // Database has fewer blogs than API - can sync more
            countStyle = 'style="font-size:12px;color:#777;"';
        } else if (dbCount > apiCount) {
            // Database has more blogs than API - unusual situation
            countStyle = 'style="font-size:12px;color:#d9534f;"';
        } else {
            // Counts are equal - fully synced
            countStyle = 'style="font-size:12px;color:#5cb85c;"';
        }
        
        // Format: Tên cate (DB: count / API: count)
        var countInfo = ' <span ' + countStyle + '>(DB: ' + dbCount + ' / API: ' + apiCount + ')</span>';
        
        // Add sync info
        var syncInfo = '';
        if (hasSynced) {
            syncInfo = ' <span style="font-size:11px;color:#777;font-style:italic;">' + formatDate(category.last_sync) + '</span>';
        } else {
            syncInfo = ' <span style="font-size:11px;color:#999;">[<?php echo _l('never_synced'); ?>]</span>';
        }
        
        // Add category label with DB/API counts and sync info
        nodeDiv.append('<span class="category-label' + disabledClass + '" data-category-id="' + category.category_id + '">' + 
            category.name + countInfo + syncInfo + '</span>');
            
        li.append(nodeDiv);
        
        // Add child categories if any
        if (hasChildren) {
            // Sort children by name
            category.children.sort(function(a, b) {
                return a.name.localeCompare(b.name);
            });
            
            var childDiv = $('<div class="child-categories">');
            renderCategoryTree(category.children, childDiv);
            li.append(childDiv);
        }
        
        ul.append(li);
    });
    
    parentElement.append(ul);
}

/**
 * Initialize tree behavior (expand/collapse)
 */
function initializeTreeBehavior() {
    // Handle expander clicks
    $('.category-tree .expander').on('click', function(e) {
        e.stopPropagation();
        var $this = $(this);
        var $childCategories = $this.closest('li').find('> .child-categories');
        var $folderIcon = $this.siblings('.category-icon');
        
        if ($childCategories.hasClass('expanded')) {
            // Collapse
            $childCategories.removeClass('expanded');
            $this.text('+');
            $folderIcon.removeClass('fa-folder-open').addClass('fa-folder');
        } else {
            // Expand
            $childCategories.addClass('expanded');
            $this.text('-');
            $folderIcon.removeClass('fa-folder').addClass('fa-folder-open');
        }
    });
    
    // Handle category label clicks
    $(document).on('click', '.category-label', function(e) {
        e.stopPropagation();
        
        if ($(this).hasClass('disabled')) return;
        
        var categoryId = $(this).data('category-id');
        var $checkbox = $(this).siblings('.category-checkbox');
        
        // Toggle checkbox
        $checkbox.prop('checked', !$checkbox.prop('checked'));
        
        // Update selection
        if ($checkbox.prop('checked')) {
            // Set as selected category
            selectedCategoryId = categoryId;
            
            // Uncheck other checkboxes (for single selection mode)
            $('.category-checkbox').not($checkbox).prop('checked', false);
            
            // Update selected category info
            // Lấy chỉ phần tên danh mục, không bao gồm thông tin count
            var categoryLabelText = $(this).text();
            var categoryNameOnly = categoryLabelText.split('(')[0].trim();
            $('#selected_category_name').text(categoryNameOnly);
            $('#selected_category_info').addClass('active');
            
            // Update selection info in filter actions
            $('#no_selection').hide();
            $('#selected_info').html('<i class="fa fa-check-circle text-success"></i> <strong>' + categoryNameOnly + '</strong> <?php echo _l('controller_selected'); ?>');
            
            // Enable apply button with visual cue
            $('#apply_filter').addClass('btn-success').removeClass('btn-primary')
                .html('<i class="fa fa-check"></i> <?php echo _l('controller_apply_filter'); ?>');
            
            // Hide help when a category is selected
            $('#category_selection_help').fadeOut();
        } else {
            // Clear selection
            selectedCategoryId = null;
            $('#selected_category_info').removeClass('active');
            $('#no_selection').show();
            $('#selected_info').html('<span id="no_selection"><?php echo _l('controller_no_category_selected'); ?></span>');
            $('#apply_filter').removeClass('btn-success').addClass('btn-primary')
                .html('<i class="fa fa-filter"></i> <?php echo _l('controller_apply_filter'); ?>');
        }
    });
    
    // Handle checkbox clicks
    $(document).on('click', '.category-checkbox', function(e) {
        e.stopPropagation();
        
        if ($(this).prop('disabled')) return;
        
        var categoryId = $(this).data('category-id');
        
        // Uncheck other checkboxes (for single selection mode)
        $('.category-checkbox').not(this).prop('checked', false);
        
        if ($(this).prop('checked')) {
            selectedCategoryId = categoryId;
            
            // Update selected category info
            // Lấy chỉ phần tên danh mục, không bao gồm thông tin count
            var categoryLabelText = $(this).siblings('.category-label').text();
            var categoryNameOnly = categoryLabelText.split('(')[0].trim();
            $('#selected_category_name').text(categoryNameOnly);
            $('#selected_category_info').addClass('active');
            
            // Update selection info in filter actions
            $('#no_selection').hide();
            $('#selected_info').html('<i class="fa fa-check-circle text-success"></i> <strong>' + categoryNameOnly + '</strong> <?php echo _l('controller_selected'); ?>');
            
            // Enable apply button
            $('#apply_filter').addClass('btn-success').removeClass('btn-primary')
                .html('<i class="fa fa-check"></i> <?php echo _l('controller_apply_filter'); ?>');
            
            // Hide help when a category is selected
            $('#category_selection_help').fadeOut();
        } else {
            // Clear selection
            selectedCategoryId = null;
            $('#selected_category_info').removeClass('active');
            $('#no_selection').show();
            $('#selected_info').html('<span id="no_selection"><?php echo _l('controller_no_category_selected'); ?></span>');
            $('#apply_filter').removeClass('btn-success').addClass('btn-primary')
                .html('<i class="fa fa-filter"></i> <?php echo _l('controller_apply_filter'); ?>');
        }
    });
    
    // Handle expand all button
    $('#expand_all_categories').on('click', function() {
        $('.category-tree .child-categories').addClass('expanded');
        $('.category-tree .expander').each(function() {
            if ($(this).css('visibility') !== 'hidden') {
                $(this).text('-');
                $(this).siblings('.category-icon').removeClass('fa-folder').addClass('fa-folder-open');
            }
        });
    });
    
    // Handle collapse all button
    $('#collapse_all_categories').on('click', function() {
        $('.category-tree .child-categories').removeClass('expanded');
        $('.category-tree .expander').each(function() {
            if ($(this).css('visibility') !== 'hidden') {
                $(this).text('+');
                $(this).siblings('.category-icon').removeClass('fa-folder-open').addClass('fa-folder');
            }
        });
    });
    
    // Handle search
    $('#category_search').on('keyup', function() {
        var searchTerm = $(this).val().toLowerCase();
        
        if (searchTerm === '') {
            // Show all categories
            $('.category-tree li').show();
            return;
        }
        
        // Initially hide all
        $('.category-tree li').hide();
        
        // Find and show matching categories and their parents
        $('.category-label').each(function() {
            var $label = $(this);
            var categoryName = $label.text().toLowerCase();
            
            if (categoryName.includes(searchTerm)) {
                // Show this category
                $label.closest('li').show();
                
                // Show its parents
                var $parent = $label.closest('li').parents('li');
                $parent.show();
                
                // Expand parent containers
                $parent.find('> .child-categories').addClass('expanded');
                $parent.find('> .category-node > .expander').text('-');
                $parent.find('> .category-node > .category-icon').removeClass('fa-folder').addClass('fa-folder-open');
            }
        });
    });
    
    // Show current category selection if any
    if (selectedCategoryId) {
        var $checkbox = $('.category-checkbox[data-category-id="' + selectedCategoryId + '"]');
        if ($checkbox.length) {
            $checkbox.prop('checked', true);
            
            // Expand its parents
            $checkbox.parents('.child-categories').addClass('expanded');
            $checkbox.parents('li').find('> .category-node > .expander').text('-');
            $checkbox.parents('li').find('> .category-node > .category-icon').removeClass('fa-folder').addClass('fa-folder-open');
        }
    }
}

/**
 * Format date to readable format
 * @param {string} dateString - Date string
 * @return {string} Formatted date
 */
function formatDate(dateString) {
    var date = new Date(dateString);
    var day = date.getDate().toString().padStart(2, '0');
    var month = (date.getMonth() + 1).toString().padStart(2, '0');
    var year = date.getFullYear();
    var hours = date.getHours().toString().padStart(2, '0');
    var minutes = date.getMinutes().toString().padStart(2, '0');
    
    return '(' + day + '/' + month + '/' + year + ' ' + hours + ':' + minutes + ')';
}

/**
 * Check server status for the selected category
 * @param {number} controllerId - ID of the controller
 * @param {string} categoryId - ID of the category
 */
function checkServerStatus(controllerId, categoryId) {
    $.ajax({
        url: admin_url + 'topics/controllers/get_categories/' + controllerId,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success && response.categories) {
                var category = response.categories.find(function(cat) {
                    return cat.category_id == categoryId;
                });
                
                if (category) {
                    if (!hasBeenSynced(category)) {
                        // Category has never been synced
                        $('#no_blogs .no-blogs-message').html('<?php echo _l('category_never_synced'); ?> <strong>' + category.name + '</strong>');
                    } else {
                        // Category has been synced but no blogs
                        $('#no_blogs .no-blogs-message').html('<?php echo _l('category_synced_no_blogs'); ?> <strong>' + category.name + '</strong>. <?php echo _l('last_sync'); ?>: ' + formatDate(category.last_sync));
                    }
                }
            }
        }
    });
}

/**
 * Load blogs from server
 * @param {number} controllerId - ID of the controller
 * @param {string} categoryId - ID of the category
 * @param {boolean} directFetch - Whether to fetch blogs directly from API
 * @param {number} page - Page number for pagination
 * @param {number} limit - Limit per page
 */
function loadBlogs(controllerId, categoryId, directFetch = false, page = 1, limit = 10) {
    // Reset loading state
    resetLoadingState();
    
    // Hide all content containers
    $('#blogs_list').hide();
    $('#blogs_table_container').hide();
    $('#no_blogs').hide();
    $('#api_blogs_notification').hide();
    $('#blogs_loading').show();
    
    // Nếu đang lấy từ API, hiển thị thông báo phù hợp
    if (directFetch) {
        $('#loading_text').html('<?php echo _l('loading_blogs_from_api'); ?> <small class="text-muted"><?php echo _l('may_take_longer'); ?></small>');
        // Bắt đầu tracking thời gian loading
        startLoadingTimer();
    } else {
        $('#loading_text').text('<?php echo _l('loading_blogs_from_db'); ?>');
    }
    
    // Simulate progress bar
    simulateProgress(directFetch);
    
    // Debug info
    console.log('Loading blogs for controller: ' + controllerId + ', category: ' + categoryId + ', direct fetch: ' + directFetch);
    
    // AJAX request to get blogs
    $.ajax({
        url: admin_url + 'topics/controllers/get_blogs/' + controllerId,
        type: 'GET',
        data: {
            category_id: categoryId,
            direct_fetch: directFetch ? 1 : 0,
            page: page,
            limit: limit
        },
        dataType: 'json',
        timeout: directFetch ? 120000 : 30000, // Tăng timeout khi lấy từ API (120s) so với DB (30s)
        success: function(response) {
            // Dừng và reset timer
            stopLoadingTimer();
            
            // Hide loading
            $('#blogs_loading').hide();
            
            // Debug info
            console.log('Blog response received:', response);
            
            // Thêm thông tin nguồn dữ liệu
            var dataSource = response.source || 'database';
            console.log('Data source: ' + dataSource);
            
            // Ensure response is properly parsed
            if (typeof response === 'string') {
                try {
                    response = JSON.parse(response);
                } catch (e) {
                    console.error('Error parsing response:', e);
                }
            }
            
            if (response.success) {
                // Nếu response là từ API và có cờ need_sync
                if (dataSource === 'platform_api' && response.need_sync) {
                    // Hiển thị thông báo số lượng blog tìm thấy và nút sync
                    var count = response.count || 0;
                    $('#api_blogs_count').text(sprintf('<?php echo _l('blogs_count_found_in_api'); ?>', count));
                    
                    // Lưu các thông tin cần thiết để sync
                    $('#api_blogs_notification').data({
                        'controller_id': response.controller_id,
                        'category_id': response.category_id,
                        'count': count
                    });
                    
                    // Hiển thị thông báo
                    $('#api_blogs_notification').show();
                }
                // Nếu response là từ database và có blogs
                else if (dataSource === 'database' && response.blogs && response.blogs.length > 0) {
                    // Lưu danh sách blogs và thông tin phân trang để dùng sau này
                    $('#blogs_list').data('blogs', response.blogs);
                    $('#blogs_list').data('source', dataSource);
                    
                    // Lưu thông tin phân trang để sử dụng khi chuyển đổi chế độ xem
                    if (response.pagination) {
                        $('#blogs_list').data('pagination', response.pagination);
                    }
                    
                    // Hiển thị tùy chọn chuyển đổi kiểu xem (card/table)
                    $('.view-options').show();
                    
                    // Hiển thị view tương ứng (card hoặc table)
                    if ($('#view_mode_cards').hasClass('active')) {
                        renderBlogsList(response.blogs, dataSource);
                        $('#blogs_list').show();
                    } else {
                        renderBlogsTable(response.blogs, dataSource, response.pagination);
                        $('#blogs_table_container').show();
                    }
                }
                // Không có blogs nào
                else if ((!response.blogs || response.blogs.length === 0) && !response.need_sync) {
                    // Check server for more information about the category
                    console.log('No blogs found for this category, checking server status');
                    
                    if (!directFetch) {
                        // Nếu đang lấy từ database, hiển thị tùy chọn để lấy trực tiếp từ API
                        $('#no_blogs .no-blogs-message').html('<?php echo _l('no_blogs_found_in_database'); ?> <button id="try_direct_fetch" class="btn btn-sm btn-info"><i class="fa fa-refresh"></i> <?php echo _l('try_direct_fetch'); ?></button>');
                        
                        // Xử lý sự kiện nhấp vào nút thử lấy trực tiếp
                        $('#try_direct_fetch').off('click').on('click', function() {
                            // Automatically check the direct API toggle
                            $('#direct_api_toggle').prop('checked', true);
                            loadBlogs(controllerId, categoryId, true);
                        });
                    } else {
                        // Nếu đã thử lấy trực tiếp từ API nhưng vẫn không có kết quả
                        $('#no_blogs .no-blogs-message').html('<?php echo _l('no_blogs_found_api'); ?> <strong>' + categoryId + '</strong>. <?php echo _l('please_check_wp_site'); ?>');
                    }
                    
                    checkServerStatus(controllerId, categoryId);
                    $('#no_blogs').show();
                }
            } else {
                // Hiển thị thông báo lỗi
                if (response.message) {
                    console.log('Error message:', response.message);
                    $('#no_blogs .no-blogs-message').text(response.message);
                } else {
                    $('#no_blogs .no-blogs-message').text('<?php echo _l('no_blogs_found'); ?>');
                }
                $('#no_blogs').show();
            }
        },
        error: function(xhr, status, error) {
            // Xử lý lỗi...như trước đây, không cần thay đổi
            stopLoadingTimer();
            
            console.error('Error loading blogs:', error);
            console.log('XHR status:', status);
            console.log('XHR response:', xhr.responseText);
            
            $('#blogs_loading').hide();
            
            var errorMsg = '<?php echo _l('error_loading_blogs'); ?>: ' + error;
            
            if (status === 'timeout') {
                errorMsg = '<?php echo _l('request_timeout'); ?>';
                if (directFetch) {
                    errorMsg += ' <?php echo _l('api_timeout_suggestion'); ?>';
                }
            } else {
                try {
                    var response = JSON.parse(xhr.responseText);
                    if (response && response.message) {
                        errorMsg += ' - ' + response.message;
                    }
                } catch (e) {
                    if (xhr.status === 404) {
                        errorMsg = '<?php echo _l('api_endpoint_not_found'); ?>';
                    } else if (xhr.status === 403) {
                        errorMsg = '<?php echo _l('permission_denied'); ?>';
                    } else if (xhr.status === 500) {
                        errorMsg = '<?php echo _l('server_error'); ?>';
                    }
                }
            }
            
            $('#no_blogs .no-blogs-message').html(errorMsg);
            if (directFetch) {
                $('#no_blogs .no-blogs-message').append(' <button id="try_from_db" class="btn btn-sm btn-default mtop5"><i class="fa fa-database"></i> <?php echo _l('try_from_db'); ?></button>');
                
                $('#try_from_db').off('click').on('click', function() {
                    $('#direct_api_toggle').prop('checked', false);
                    loadBlogs(controllerId, categoryId, false);
                });
            }
            $('#no_blogs').show();
        }
    });
}

/**
 * Reset loading state
 */
function resetLoadingState() {
    // Reset loading timer
    stopLoadingTimer();
    
    // Reset progress bar
    $('#loading_progress').css('width', '0%').attr('aria-valuenow', 0);
    
    // Hide timeout warning
    $('#loading_timeout_warning').hide();
    $('#loading_elapsed').text('0');
    loadingElapsedSeconds = 0;
}

/**
 * Start loading timer for timeout detection
 */
function startLoadingTimer() {
    loadingStartTime = new Date();
    loadingElapsedSeconds = 0;
    
    // Update loading elapsed time every second
    loadingTimer = setInterval(function() {
        loadingElapsedSeconds = Math.floor((new Date() - loadingStartTime) / 1000);
        $('#loading_elapsed').text(loadingElapsedSeconds);
        
        // Show timeout warning after 15 seconds
        if (loadingElapsedSeconds >= 15 && loadingElapsedSeconds < loadingTimeout) {
            $('#loading_timeout_warning').fadeIn();
        }
        
        // Update loading text after 30 seconds
        if (loadingElapsedSeconds == 30) {
            $('#loading_text').html('<?php echo _l('still_loading'); ?> <i class="fa fa-hourglass-half"></i>');
        }
        
        // Reached timeout but AJAX is still going
        if (loadingElapsedSeconds >= loadingTimeout) {
            $('#loading_text').html('<?php echo _l('loading_taking_too_long'); ?> <i class="fa fa-exclamation-triangle text-warning"></i>');
        }
    }, 1000);
}

/**
 * Stop loading timer
 */
function stopLoadingTimer() {
    if (loadingTimer) {
        clearInterval(loadingTimer);
        loadingTimer = null;
    }
}

/**
 * Simulate progress bar
 */
function simulateProgress(isFromApi) {
    var increment = isFromApi ? 2 : 10; // API loads slower, so we increment slower
    var interval = isFromApi ? 800 : 300; // More frequent updates for DB, less frequent for API
    var maxProgress = isFromApi ? 90 : 95; // Max progress before completion
    
    var progress = 0;
    var progressInterval = setInterval(function() {
        if (progress < maxProgress) {
            progress += increment;
            if (progress > maxProgress) progress = maxProgress;
            
            // Update progress bar
            $('#loading_progress').css('width', progress + '%').attr('aria-valuenow', progress);
            
            // Slow down as we get closer to maxProgress
            if (progress > 60) {
                increment = isFromApi ? 1 : 5;
                interval = isFromApi ? 1500 : 600;
                clearInterval(progressInterval);
                progressInterval = setInterval(arguments.callee, interval);
            }
        } else {
            clearInterval(progressInterval);
        }
    }, interval);
    
    // Store interval in data for cleanup
    $('#blogs_loading').data('progressInterval', progressInterval);
}

/**
 * Render blogs list
 * @param {Array} blogs - List of blogs
 * @param {string} dataSource - Source of the blog data ('database' or 'platform_api')
 */
function renderBlogsList(blogs, dataSource) {
    var html = '';
    
    // Debug logs to check data
    console.log('Rendering blogs list with ' + blogs.length + ' blogs from source: ' + dataSource);
    if (blogs.length > 0) {
        console.log('Sample blog data:', blogs[0]);
    }
    
    // Thêm header tổng số bài viết
    html += '<div class="col-md-12 mbot15">';
    html += '<div class="alert alert-info">';
    html += '<i class="fa fa-info-circle"></i> <?php echo _l('total_blogs_found'); ?>: <strong>' + blogs.length + '</strong>';
    
    // Hiển thị nguồn dữ liệu
    if (dataSource === 'platform_api') {
        html += ' <span class="label label-primary"><?php echo _l('source_api'); ?></span>';
        html += ' <small><?php echo _l('api_data_not_saved'); ?></small>';
    } else {
        html += ' <span class="label label-default"><?php echo _l('source_database'); ?></span>';
    }
    
    html += '</div>';
    html += '</div>';
    
    // Loop through blogs
    blogs.forEach(function(blog) {
        html += '<div class="col-md-4">';
        html += '<div class="blog-card">';
        
        // Blog header
        html += '<div class="blog-header">';
        html += '<h5 class="blog-title">' + blog.title + '</h5>';
        
        if (blog.date_published) {
            var date = new Date(blog.date_published);
            html += '<span class="blog-date">' + formatDate(blog.date_published) + '</span>';
        }
        
        html += '</div>';
        
        // Blog content
        html += '<div class="blog-content">';
        
        if (blog.featured_image) {
            html += '<img src="' + blog.featured_image + '" class="blog-image" alt="' + blog.title + '">';
        } else {
            html += '<div class="blog-image-placeholder"><i class="fa fa-image fa-3x"></i></div>';
        }
        
        if (blog.excerpt && blog.excerpt.trim() !== '') {
            html += '<div class="blog-excerpt">' + blog.excerpt + '</div>';
        } else {
            html += '<div class="blog-excerpt text-muted"><em><?php echo _l("no_excerpt_available"); ?></em></div>';
        }
        
        // Categories and tags
        html += '<div>';
        
        if (blog.categories && blog.categories.length > 0) {
            blog.categories.forEach(function(category) {
                // Kiểm tra nếu category là object (có name) hoặc chỉ là ID
                var catName = category.name || 'Category #' + category;
                html += '<span class="blog-category">' + catName + '</span>';
            });
        }
        
        if (blog.tags && blog.tags.length > 0) {
            blog.tags.forEach(function(tag) {
                // Tương tự như categories
                var tagName = tag.name || 'Tag #' + tag;
                html += '<span class="blog-tag">' + tagName + '</span>';
            });
        }
        
        html += '</div>';
        
        // Blog meta
        html += '<div class="blog-meta">';
        
        if (blog.author) {
            html += '<div class="meta-item"><i class="fa fa-user"></i> ' + blog.author + '</div>';
        }
        
        if (blog.comment_count !== undefined) {
            html += '<div class="meta-item"><i class="fa fa-comment"></i> ' + blog.comment_count + '</div>';
        }
        
        if (blog.view_count !== undefined) {
            html += '<div class="meta-item"><i class="fa fa-eye"></i> ' + blog.view_count + '</div>';
        }
        
        html += '</div>'; // End blog meta
        
        // Sync info
        if (blog.last_sync) {
            html += '<div class="sync-info mtop10"><i class="fa fa-refresh"></i> <?php echo _l('controller_last_sync'); ?>: <span class="sync-date">' + formatDate(blog.last_sync) + '</span></div>';
        }
        
        html += '</div>'; // End blog content
        
        // Blog actions
        html += '<div class="blog-actions">';
        html += '<button type="button" class="btn btn-sm btn-default view-blog-detail" data-blog-id="' + blog.blog_id + '"><i class="fa fa-search"></i> <?php echo _l('view_details'); ?></button> ';
        html += '<a href="' + blog.url + '" target="_blank" class="btn btn-sm btn-primary"><i class="fa fa-external-link"></i> <?php echo _l('controller_view_on_site'); ?></a>';
        
        // Thêm nút lưu nếu dữ liệu từ API
        if (dataSource === 'platform_api') {
            html += ' <button type="button" class="btn btn-sm btn-success save-blog" data-blog-id="' + blog.blog_id + '"><i class="fa fa-save"></i> <?php echo _l('save_to_db'); ?></button>';
        }
        
        html += '</div>';
        
        html += '</div>'; // End blog card
        html += '</div>'; // End col
    });
    
    // Add to container
    $('#blogs_list').html(html);
    
    // Thêm CSS inline
    $("<style>")
        .prop("type", "text/css")
        .html(`
            .blog-image-placeholder {
                width: 100%;
                height: 180px;
                display: flex;
                align-items: center;
                justify-content: center;
                background-color: #f8f9fa;
                color: #adb5bd;
                border-radius: 4px;
                margin-bottom: 10px;
            }
        `)
        .appendTo("head");
}

/**
 * Render blogs in table format
 * @param {Array} blogs - List of blogs
 * @param {string} dataSource - Source of the blog data ('database' or 'platform_api')
 * @param {Object} pagination - Pagination information
 */
function renderBlogsTable(blogs, dataSource, pagination) {
    // Clear the table body
    var $tableBody = $('#blogs_table tbody').empty();
    
    // Add header with source and total info
    var $headerRow = $('<tr class="info">').appendTo($tableBody);
    var colspan = 5;
    var sourceLabel = dataSource === 'platform_api' ? 
        '<span class="label label-primary"><?php echo _l('source_api'); ?></span>' : 
        '<span class="label label-default"><?php echo _l('source_database'); ?></span>';
    
    $headerRow.append(
        $('<td colspan="' + colspan + '">').html(
            '<i class="fa fa-info-circle"></i> <?php echo _l('total_blogs_found'); ?>: <strong>' + blogs.length + '</strong> ' + 
            sourceLabel
        )
    );
    
    // Loop through blogs to add rows
    blogs.forEach(function(blog) {
        var $row = $('<tr>').appendTo($tableBody);
        
        // Title column
        $row.append(
            $('<td>').html('<strong>' + blog.title + '</strong>')
        );
        
        // Date column
        var dateStr = blog.date_published ? formatDate(blog.date_published) : '-';
        $row.append(
            $('<td>').text(dateStr)
        );
        
        // Categories column
        var categoriesHtml = '';
        if (blog.categories && blog.categories.length > 0) {
            blog.categories.forEach(function(category) {
                var catName = category.name || 'Category #' + category;
                categoriesHtml += '<span class="blog-category">' + catName + '</span> ';
            });
        } else {
            categoriesHtml = '-';
        }
        $row.append(
            $('<td>').html(categoriesHtml)
        );
        
        // Tags column
        var tagsHtml = '';
        if (blog.tags && blog.tags.length > 0) {
            blog.tags.forEach(function(tag) {
                var tagName = tag.name || 'Tag #' + tag;
                tagsHtml += '<span class="blog-tag">' + tagName + '</span> ';
            });
        } else {
            tagsHtml = '-';
        }
        $row.append(
            $('<td>').html(tagsHtml)
        );
        
        // Actions column
        var actionsHtml = '';
        actionsHtml += '<button type="button" class="btn btn-xs btn-default view-blog-detail" data-blog-id="' + blog.blog_id + '"><i class="fa fa-search"></i></button> ';
        actionsHtml += '<a href="' + blog.url + '" target="_blank" class="btn btn-xs btn-primary"><i class="fa fa-external-link"></i></a>';
        
        if (dataSource === 'platform_api') {
            actionsHtml += ' <button type="button" class="btn btn-xs btn-success save-blog" data-blog-id="' + blog.blog_id + '"><i class="fa fa-save"></i></button>';
        }
        
        $row.append(
            $('<td>').html(actionsHtml)
        );
    });
    
    // Render pagination
    renderPagination(pagination);
}

/**
 * Render pagination for the table view
 * @param {Object} pagination - Pagination information
 */
function renderPagination(pagination) {
    if (!pagination) return;
    
    var $paginationContainer = $('#blogs_pagination');
    var $paginationInfo = $('#pagination_info');
    var $pagination = $paginationContainer.find('.pagination').empty();
    
    // Show pagination info text
    var from = ((pagination.page - 1) * pagination.limit) + 1;
    var to = Math.min(pagination.page * pagination.limit, pagination.total);
    $paginationInfo.text(sprintf('<?php echo _l('pagination_showing'); ?>', from, to, pagination.total));
    
    // First page button
    var $firstBtn = $('<li class="paginate_button first' + (pagination.page === 1 ? ' disabled' : '') + '">').appendTo($pagination);
    $firstBtn.append($('<a href="#" data-page="1"><?php echo _l('pagination_first'); ?></a>'));
    
    // Previous page button
    var $prevBtn = $('<li class="paginate_button previous' + (pagination.page === 1 ? ' disabled' : '') + '">').appendTo($pagination);
    $prevBtn.append($('<a href="#" data-page="' + (pagination.page - 1) + '"><?php echo _l('pagination_previous'); ?></a>'));
    
    // Page buttons
    var startPage = Math.max(1, pagination.page - 2);
    var endPage = Math.min(pagination.pages, pagination.page + 2);
    
    for (var i = startPage; i <= endPage; i++) {
        var $pageBtn = $('<li class="paginate_button' + (pagination.page === i ? ' active' : '') + '">').appendTo($pagination);
        $pageBtn.append($('<a href="#" data-page="' + i + '">' + i + '</a>'));
    }
    
    // Next page button
    var $nextBtn = $('<li class="paginate_button next' + (pagination.page === pagination.pages ? ' disabled' : '') + '">').appendTo($pagination);
    $nextBtn.append($('<a href="#" data-page="' + (pagination.page + 1) + '"><?php echo _l('pagination_next'); ?></a>'));
    
    // Last page button
    var $lastBtn = $('<li class="paginate_button last' + (pagination.page === pagination.pages ? ' disabled' : '') + '">').appendTo($pagination);
    $lastBtn.append($('<a href="#" data-page="' + pagination.pages + '"><?php echo _l('pagination_last'); ?></a>'));
    
    // Add page number input
    var $pageInputContainer = $('<li class="paginate_input">').appendTo($pagination);
    $pageInputContainer.append(
        $('<span style="margin: 0 5px;"><?php echo _l('pagination_page'); ?></span>')
    );
    
    var $pageInput = $('<input type="number" class="form-control input-sm" style="width: 60px; display: inline-block;" min="1" max="' + pagination.pages + '" value="' + pagination.page + '">');
    $pageInputContainer.append($pageInput);
    
    $pageInputContainer.append(
        $('<span style="margin: 0 5px;"><?php echo _l('pagination_of'); ?> ' + pagination.pages + '</span>')
    );
    
    // Bind pagination events
    $pagination.find('a[data-page]').on('click', function(e) {
        e.preventDefault();
        
        if ($(this).parent().hasClass('disabled')) return;
        
        var page = parseInt($(this).data('page'));
        var categoryId = selectedCategoryId;
        var directFetch = $('#direct_api_toggle').prop('checked');
        
        loadBlogs(controllerId, categoryId, directFetch, page, pagination.limit);
    });
    
    // Handle page input change
    $pageInput.on('change', function() {
        var page = parseInt($(this).val());
        if (isNaN(page) || page < 1) page = 1;
        if (page > pagination.pages) page = pagination.pages;
        
        var categoryId = selectedCategoryId;
        var directFetch = $('#direct_api_toggle').prop('checked');
        
        loadBlogs(controllerId, categoryId, directFetch, page, pagination.limit);
    });
}

/**
 * Sync blogs with platform
 * @param {number} controllerId - ID of the controller
 * @param {string} categoryId - ID of the category
 */
function syncBlogs(controllerId, categoryId) {
    if (!categoryId) {
        alert_float('warning', '<?php echo _l('controller_select_category_first'); ?>');
        // Show selection help with animation
        $('#category_selection_help').hide().fadeIn('slow');
        return;
    }
    
    // Reset loading state
    resetLoadingState();
    
    // Display syncing UI
    var $selectedLabel = $('.category-checkbox[data-category-id="' + categoryId + '"]').siblings('.category-label');
    var selectedCategoryName = $selectedLabel.text();
    var syncBtn = $('#sync_blogs');
    var originalBtnHtml = syncBtn.html();
    
    syncBtn.html('<i class="fa fa-refresh fa-spin"></i> <?php echo _l('syncing'); ?> ' + selectedCategoryName)
           .prop('disabled', true)
           .addClass('disabled');
    
    // Add a syncing indicator to the selected category
    $selectedLabel.append(' <i class="fa fa-refresh fa-spin syncing-indicator"></i>');
    
    // Hiển thị thông báo thực hiện tối ưu
    alert_float('info', '<?php echo _l('syncing_blogs_optimized'); ?>', 8000);
    
    // Bắt đầu tracking thời gian loading
    startLoadingTimer();
    simulateProgress(true); // Sync hoạt động giống như API fetch
    
    // AJAX request to sync blogs
    $.ajax({
        url: admin_url + 'topics/controllers/sync_blogs/' + controllerId,
        type: 'POST',
        data: {
            category_id: categoryId
        },
        dataType: 'json',
        timeout: 180000, // 3 phút cho sync, lâu hơn vì có thể cần tải nhiều blogs
        success: function(response) {
            // Dừng và reset timer
            stopLoadingTimer();
            
            // Remove syncing indicator
            $('.syncing-indicator').remove();
            
            if (response.success) {
                // Show success message with count
                var successMsg = '';
                if (response.count > 0) {
                    successMsg = '<?php echo _l('blogs_synced_successfully'); ?>: ' + response.count + ' blogs';
                    if (response.message) {
                        successMsg += '. ' + response.message;
                    }
                } else {
                    successMsg = response.message || '<?php echo _l('no_new_blogs_found'); ?>';
                }
                
                alert_float('success', successMsg);
                
                // Update the category label to show it's synced (remove disabled class)
                $selectedLabel.removeClass('disabled');
                
                // Enable the checkbox if it was disabled
                var $checkbox = $('.category-checkbox[data-category-id="' + categoryId + '"]');
                $checkbox.prop('disabled', false);
                
                // Reload blogs with the updated data
                $('#direct_api_toggle').prop('checked', false); // Tắt chế độ Direct from API
                loadBlogs(controllerId, categoryId, false); // Load từ database
                
                // Log sync complete for debugging
                console.log('Sync completed successfully. Blogs saved: ' + response.count);
            } else {
                // Show error message
                var errorMsg = response.message || '<?php echo _l('error_syncing_blogs'); ?>';
                alert_float('danger', errorMsg);
                
                // Log error for debugging
                console.error('Sync failed:', errorMsg);
            }
            
            // Reset sync button
            syncBtn.html(originalBtnHtml)
                   .prop('disabled', false)
                   .removeClass('disabled');
        },
        error: function(xhr, status, error) {
            // Dừng và reset timer
            stopLoadingTimer();
            
            // Remove syncing indicator
            $('.syncing-indicator').remove();
            
            // Specific handling for timeout
            if (status === 'timeout') {
                alert_float('danger', '<?php echo _l('sync_timeout'); ?>');
                console.error('Sync timeout after ' + loadingElapsedSeconds + ' seconds');
            } else {
                // Get detailed error message if available
                var errorMsg = '<?php echo _l('error_syncing_blogs'); ?>: ' + error;
                try {
                    var responseObj = JSON.parse(xhr.responseText);
                    if (responseObj && responseObj.message) {
                        errorMsg = responseObj.message;
                    }
                } catch (e) {
                    console.error('Error parsing error response:', e);
                }
                
                // Show error message
                alert_float('danger', errorMsg);
                
                // Log error for debugging
                console.error('XHR error during sync:', status, error, xhr.responseText);
            }
            
            // Reset sync button
            syncBtn.html(originalBtnHtml)
                   .prop('disabled', false)
                   .removeClass('disabled');
        }
    });
}

/**
 * Show blog detail in modal
 * @param {object} blog - Blog object
 */
function showBlogDetail(blog) {
    // Reset modal content
    $('#blog_detail_title').text(blog.title);
    $('#blog_detail_excerpt').html(blog.excerpt || '<?php echo _l("no_excerpt_available"); ?>');
    $('#blog_detail_featured_image').empty();
    $('#blog_detail_view_full').attr('href', blog.url);
    
    // Show featured image if available
    if (blog.featured_image) {
        $('#blog_detail_featured_image').html('<img src="' + blog.featured_image + '" class="img-responsive" style="max-height:300px;margin:0 auto;">');
    }
    
    // Hide loading, show content
    $('#blog_detail_loading').hide();
    $('#blog_detail_content').show();
    
    // Show modal
    $('#blog_detail_modal').modal('show');
    
    // Log view for debugging
    console.log('Viewing blog detail:', blog);
}

/**
 * Save a single blog from API to database
 * @param {number} controllerId - Controller ID
 * @param {string} categoryId - Category ID
 * @param {string} blogId - Blog ID to save
 */
function saveBlogToDatabase(controllerId, categoryId, blogId) {
    // Find blog in current list
    var blogs = $('#blogs_list').data('blogs');
    var blog = null;
    
    if (blogs) {
        blog = blogs.find(function(b) {
            return b.blog_id == blogId;
        });
    }
    
    if (!blog) {
        alert_float('warning', '<?php echo _l('blog_not_found'); ?>');
        return;
    }
    
    // Disable button and show loading state
    var $button = $('.save-blog[data-blog-id="' + blogId + '"]');
    var originalHtml = $button.html();
    $button.html('<i class="fa fa-spinner fa-spin"></i> <?php echo _l('saving'); ?>').prop('disabled', true);
    
    // AJAX request to save blog
    $.ajax({
        url: admin_url + 'topics/controllers/save_single_blog/' + controllerId,
        type: 'POST',
        data: {
            category_id: categoryId,
            blog_id: blogId,
            blog_data: JSON.stringify(blog)
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Show success message
                alert_float('success', response.message || '<?php echo _l('blog_saved_successfully'); ?>');
                
                // Update button to show saved state
                $button.html('<i class="fa fa-check"></i> <?php echo _l('saved'); ?>')
                    .removeClass('btn-success')
                    .addClass('btn-default')
                    .prop('disabled', true);
            } else {
                // Show error message
                alert_float('danger', response.message || '<?php echo _l('error_saving_blog'); ?>');
                
                // Reset button
                $button.html(originalHtml).prop('disabled', false);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error saving blog:', error);
            
            // Show error message
            var errorMsg = '<?php echo _l('error_saving_blog'); ?>: ' + error;
            try {
                var response = JSON.parse(xhr.responseText);
                if (response && response.message) {
                    errorMsg = response.message;
                }
            } catch (e) {
                // Not JSON
            }
            
            alert_float('danger', errorMsg);
            
            // Reset button
            $button.html(originalHtml).prop('disabled', false);
        }
    });
}
</script> 