<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<!-- 
Giải thích chức năng các nút:

1. Nút "Tải lại" (Refresh Categories):
   - Tải lại danh mục từ cơ sở dữ liệu mà không đồng bộ với API
   - Giữ nguyên trạng thái mở rộng của các danh mục và danh mục đã chọn
   - Sử dụng khi muốn làm mới dữ liệu mà không cần đồng bộ lại từ API

2. Nút "Đồng bộ dữ liệu" (Sync Data):
   - Đồng bộ danh mục từ API của nền tảng về cơ sở dữ liệu
   - Quá trình này có thể mất thời gian tùy thuộc vào số lượng danh mục
   - Sử dụng khi muốn cập nhật danh mục mới nhất từ nền tảng
-->

<style>
/* Giao diện chung */
.categories-container {
    margin-top: 15px;
}

/* Cấu trúc cây phân cấp */
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

/* Loading spinner */
.spinner-border {
    width: 2rem;
    height: 2rem;
    border-width: 0.2em;
}

.spinner {
    margin: 0 auto;
    width: 70px;
    text-align: center;
}

.spinner > div {
    width: 18px;
    height: 18px;
    background-color: #0ea5e9;
    border-radius: 100%;
    display: inline-block;
    animation: sk-bouncedelay 1.4s infinite ease-in-out both;
}

.spinner .bounce1 {
    animation-delay: -0.32s;
}

.spinner .bounce2 {
    animation-delay: -0.16s;
}

@keyframes sk-bouncedelay {
    0%, 80%, 100% { 
        transform: scale(0);
    } 40% { 
        transform: scale(1.0);
    }
}

.progress {
    height: 6px;
    margin-bottom: 10px;
    overflow: hidden;
    background-color: #e9ecef;
    border-radius: 4px;
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

/* Khu vực hành động khi chọn category */
.selected-category-info {
    display: none;
    padding: 8px;
    margin-top: 10px;
    background-color: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 4px;
}

.selected-category-info.active {
    display: block;
}

.selected-category-actions {
    display: flex;
    gap: 10px;
    margin-top: 8px;
}

.quick-sync-progress {
    display: none;
    margin-top: 8px;
}
</style>

<div class="categories-container">
    <div class="row mbot15">
        <div class="col-md-6">
            <h4><?php echo _l('controller_category_hierarchy'); ?></h4>
            <small><?php echo _l('last_sync'); ?>: <span id="categories_last_sync">-</span></small>
        </div>
        <div class="col-md-6 text-right">
            <button type="button" class="btn btn-primary" id="refresh_categories" data-toggle="tooltip" title="<?php echo _l('refresh_categories_tooltip', 'Tải lại danh mục mà không đồng bộ với API'); ?>">
                <i class="fa fa-sync"></i> <?php echo _l('refresh_categories', 'Tải lại'); ?>
            </button>
            <button type="button" class="btn btn-info" id="sync_categories" data-toggle="tooltip" title="<?php echo _l('controller_sync_data_tooltip', 'Đồng bộ danh mục từ API'); ?>">
                <i class="fa fa-refresh"></i> <?php echo _l('controller_sync_data'); ?>
            </button>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-12">
            <div class="panel_s">
                <div class="panel-body">
                    <!-- Loading indicator -->
                    <div id="categories_loading" class="text-center mtop20 mbot20">
                        <div class="spinner">
                            <div class="bounce1"></div>
                            <div class="bounce2"></div>
                            <div class="bounce3"></div>
                        </div>
                        <p class="mtop10"><?php echo _l('loading_categories'); ?></p>
                        <div class="progress mtop10">
                            <div id="loading_progress" class="progress-bar progress-bar-striped active" role="progressbar" 
                                aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width:0%">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Categories tree container -->
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
                         <!-- Thêm khu vực hiển thị hành động khi chọn category -->
                    <div id="selected_category_info" class="selected-category-info">
                        <div class="selected-category-header">
                            <i class="fa fa-check-circle text-success"></i> 
                            <strong><?php echo _l('controller_selected_category'); ?>:</strong> 
                            <span id="selected_category_name"></span>
                        </div>
                        <div class="selected-category-actions">
                            <button type="button" class="btn btn-sm btn-info" id="quick_sync_blogs">
                                <i class="fa fa-refresh"></i> Quick Sync Blog
                            </button>
                            <button type="button" class="btn btn-sm btn-success" id="move_to_blogs">
                                <i class="fa fa-arrow-right"></i> Move to Blogs
                            </button>
                        </div>
                        <!-- Progress bar cho Quick Sync -->
                        <div class="quick-sync-progress">
                            <div class="progress mtop10">
                                <div id="quick_sync_progress" class="progress-bar progress-bar-striped active" 
                                    role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" 
                                    style="width:0%">
                                </div>
                            </div>
                            <p class="text-muted small" id="quick_sync_status">Đang đồng bộ...</p>
                        </div>
                    </div>
                        <div class="categories-container">
                            <div class="category-tree" id="category_tree"></div>
                        </div>
                        
                     
                    </div>
                   
                    <!-- Empty state -->
                    <div id="no_categories" class="text-center mtop20 mbot20" style="display:none;">
                        <i class="fa fa-exclamation-circle fa-3x text-warning"></i>
                        <p class="mtop10"><?php echo _l('no_categories_found'); ?></p>
                        <button type="button" class="btn btn-info" id="sync_categories_empty">
                            <i class="fa fa-refresh"></i> <?php echo _l('sync_categories_now'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Wait for document ready
// Init variables
var controllerId = <?php echo $controller->id; ?>;
var selectedCategoryId = null;

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
        console.log('Controller ID:', controllerId);
        
        // Kích hoạt tooltips
        $('[data-toggle="tooltip"]').tooltip();
        
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
        
        // Handle refresh categories button
        $('#refresh_categories').on('click', function() {
            // Hiển thị thông báo đang tải lại
            alert_float('info', '<?php echo _l('refreshing_categories', 'Đang tải lại danh mục...'); ?>');
            
            // Gọi hàm loadCategories để tải lại danh mục từ server mà không đồng bộ với API
            // Truyền tham số isRefresh = true để giữ nguyên trạng thái hiện tại
            loadCategories(controllerId, true);
        });
        
        // Handle sync categories button
        $('#sync_categories, #sync_categories_empty').on('click', function() {
            syncCategories(controllerId);
        });
        
        // Handle quick sync blogs button
        $('#quick_sync_blogs').on('click', function() {
            console.log('Quick sync button clicked, selectedCategoryId:', selectedCategoryId);
            
            if (!selectedCategoryId) {
                alert_float('warning', 'Vui lòng chọn một danh mục trước');
                return;
            }
            
            // Kiểm tra lại một lần nữa xem checkbox có được chọn không
            var $selectedCheckbox = $('.category-checkbox[data-category-id="' + selectedCategoryId + '"]');
            if ($selectedCheckbox.length === 0 || !$selectedCheckbox.prop('checked')) {
                console.warn('Checkbox not found or not checked for category ID:', selectedCategoryId);
                // Tìm checkbox đã chọn
                var $checkedCheckbox = $('.category-tree .category-checkbox:checked');
                if ($checkedCheckbox.length > 0) {
                    selectedCategoryId = $checkedCheckbox.data('category-id');
                    console.log('Found checked checkbox with category ID:', selectedCategoryId);
                } else {
                    alert_float('warning', 'Vui lòng chọn một danh mục trước');
                    return;
                }
            }
            
            quickSyncBlogs(controllerId, selectedCategoryId);
        });
        
        // Handle move to blogs button
        $('#move_to_blogs').on('click', function() {
            if (selectedCategoryId) {
                // Lưu categoryId vào localStorage để tab Blogs có thể sử dụng
                localStorage.setItem('selected_category_id', selectedCategoryId);
                
                // Chuyển sang tab Blogs
                $('.nav-tabs a[href="#blogs"]').tab('show');
            }
        });
        
        // Expand/collapse all
        $('#expand_all_categories').on('click', function() {
            $('.category-tree .expander').each(function() {
                var $childCategories = $(this).closest('li').find('> .child-categories');
                var $folderIcon = $(this).siblings('.category-icon');
                if (!$childCategories.hasClass('expanded')) {
                    $childCategories.addClass('expanded');
                    $(this).text('-');
                    $folderIcon.removeClass('fa-folder').addClass('fa-folder-open');
                }
            });
        });
        
        $('#collapse_all_categories').on('click', function() {
            $('.category-tree .expander').each(function() {
                var $childCategories = $(this).closest('li').find('> .child-categories');
                var $folderIcon = $(this).siblings('.category-icon');
                if ($childCategories.hasClass('expanded')) {
                    $childCategories.removeClass('expanded');
                    $(this).text('+');
                    $folderIcon.removeClass('fa-folder-open').addClass('fa-folder');
                }
            });
        });
        
        // Category search
        $('#category_search').on('input', function() {
            var searchText = $(this).val().toLowerCase();
            
            if (searchText.length === 0) {
                // Show all categories
                $('.category-tree li').show();
                return;
            }
            
            // Hide all categories first
            $('.category-tree li').hide();
            
            // Show categories matching the search and their parents
            $('.category-tree .category-label').each(function() {
                if ($(this).text().toLowerCase().indexOf(searchText) > -1) {
                    var $categoryItem = $(this).closest('li');
                    $categoryItem.show();
                    
                    // Show all parent categories
                    $categoryItem.parents('li').show();
                    
                    // Expand parent categories
                    $categoryItem.parents('li').each(function() {
                        var $expander = $(this).find('> .category-node > .expander');
                        var $childCategories = $(this).find('> .child-categories');
                        var $folderIcon = $expander.siblings('.category-icon');
                        
                        $childCategories.addClass('expanded');
                        $expander.text('-');
                        $folderIcon.removeClass('fa-folder').addClass('fa-folder-open');
                    });
                }
            });
        });
        
        // Load categories on page load
        loadCategories(controllerId, false);
    });
});

/**
 * Load categories from server
 * @param {number} controllerId - ID of the controller
 * @param {boolean} isRefresh - Có phải đang tải lại từ nút Refresh không
 */
function loadCategories(controllerId, isRefresh) {
    // Lưu trạng thái hiện tại của category tree trước khi tải lại
    var currentSelectedCategoryId = selectedCategoryId;
    var expandedCategoryIds = [];
    
    // Chỉ lưu trạng thái hiện tại nếu đang refresh
    if (isRefresh) {
        // Thu thập danh sách các category đang được mở rộng
        $('.category-tree .child-categories.expanded').each(function() {
            var $categoryItem = $(this).closest('li');
            var $categoryLabel = $categoryItem.find('> .category-node > .category-label');
            var categoryId = $categoryLabel.data('category-id');
            
            if (categoryId) {
                expandedCategoryIds.push(categoryId);
            }
        });
    }
    
    // Show loading
    $('#categories_loading').show();
    $('#categories_content').hide();
    $('#no_categories').hide();
    $('#selected_category_info').removeClass('active');
    
    // Start loading progress animation
    var loadingProgress = 0;
    var loadingTimer = setInterval(function() {
        loadingProgress += 5;
        if (loadingProgress > 90) {
            loadingProgress = 90;
            clearInterval(loadingTimer);
        }
        $('#loading_progress').css('width', loadingProgress + '%');
    }, 300);
    
    console.log('Loading categories for controller ID:', controllerId);
    // AJAX request to get categories
    $.ajax({
        url: admin_url + 'topics/controllers/get_categories/' + controllerId,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            // Hide loading
            $('#categories_loading').hide();
            clearInterval(loadingTimer);
            $('#loading_progress').css('width', '100%');
            
            console.log('Response:', response);
            if (response.success && response.categories && response.categories.length > 0) {
                // Count categories
                $('#categories_count').text(response.categories.length + ' <?php echo _l('controller_category_count'); ?>');
                
                // Update last sync time
                $('#categories_last_sync').text(response.last_sync);
                
                // Render categories tree
                renderCategoriesTree(response.categories);
                
                // Show the categories tree
                $('#categories_content').show();
                
                // Khôi phục trạng thái mở rộng của các category nếu đang refresh
                if (isRefresh && expandedCategoryIds.length > 0) {
                    console.log('Restoring expanded categories:', expandedCategoryIds);
                    expandedCategoryIds.forEach(function(categoryId) {
                        var $categoryItem = $('.category-tree .category-label[data-category-id="' + categoryId + '"]').closest('li');
                        var $expander = $categoryItem.find('> .category-node > .expander');
                        var $childCategories = $categoryItem.find('> .child-categories');
                        var $folderIcon = $expander.siblings('.category-icon');
                        
                        if ($expander.length && !$childCategories.hasClass('expanded')) {
                            $childCategories.addClass('expanded');
                            $expander.text('-');
                            $folderIcon.removeClass('fa-folder').addClass('fa-folder-open');
                        }
                    });
                    
                    // Khôi phục category đã chọn
                    if (currentSelectedCategoryId) {
                        console.log('Restoring selected category:', currentSelectedCategoryId);
                        var $checkbox = $('.category-checkbox[data-category-id="' + currentSelectedCategoryId + '"]');
                        if ($checkbox.length > 0) {
                            $checkbox.prop('checked', true).trigger('change');
                        }
                    }
                } else if (!isRefresh) {
                    // Nếu không phải đang refresh, kiểm tra nếu có categoryId được lưu từ trước trong localStorage
                    var savedCategoryId = localStorage.getItem('selected_category_id');
                    if (savedCategoryId) {
                        // Tìm và chọn checkbox tương ứng
                        $('.category-checkbox[data-category-id="' + savedCategoryId + '"]').prop('checked', true).trigger('change');
                        // Xóa giá trị đã lưu
                        localStorage.removeItem('selected_category_id');
                    }
                }
            } else {
                // Show empty state
                $('#no_categories').show();
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading categories:', error);
            // Hide loading, show empty state
            $('#categories_loading').hide();
            clearInterval(loadingTimer);
            $('#no_categories').show();
        }
    });
}

/**
 * Sync categories with platform
 * @param {number} controllerId - ID of the controller
 */
function syncCategories(controllerId) {
    console.log('Syncing categories for controller ID:', controllerId);
    // Show loading
    $('#categories_loading').show();
    $('#categories_content').hide();
    $('#no_categories').hide();
    $('#selected_category_info').removeClass('active');
    
    // Start loading progress animation
    var loadingProgress = 0;
    var loadingTimer = setInterval(function() {
        loadingProgress += 5;
        if (loadingProgress > 90) {
            loadingProgress = 90;
            clearInterval(loadingTimer);
        }
        $('#loading_progress').css('width', loadingProgress + '%');
    }, 300);
    
    // Add loading state to sync button
    var $syncBtn = $('#sync_categories');
    var originalHtml = $syncBtn.html();
    $syncBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> ' + lang('syncing', 'Đang đồng bộ...'));
    
    // AJAX request to sync categories
    $.ajax({
        url: admin_url + 'topics/controllers/sync_categories/' + controllerId,
        type: 'POST',
        dataType: 'json',
        success: function(response) {
            // Reset button
            $syncBtn.prop('disabled', false).html(originalHtml);
            
            // Hide loading
            $('#categories_loading').hide();
            clearInterval(loadingTimer);
            $('#loading_progress').css('width', '100%');
            
            if (response.success) {
                // Show success message
                alert_float('success', response.message || lang('categories_synced_successfully', 'Danh mục đã được đồng bộ thành công'));
                
                // Reload categories
                loadCategories(controllerId);
            } else {
                // Show error
                alert_float('danger', response.message || lang('error_syncing_categories', 'Lỗi khi đồng bộ danh mục'));
                $('#no_categories').show();
            }
        },
        error: function() {
            // Reset button
            $syncBtn.prop('disabled', false).html(originalHtml);
            
            // Hide loading, show empty state
            $('#categories_loading').hide();
            clearInterval(loadingTimer);
            $('#no_categories').show();
            
            // Show error
            alert_float('danger', lang('error_syncing_categories', 'Lỗi khi đồng bộ danh mục'));
        }
    });
}

/**
 * Quick sync blogs cho category được chọn
 * @param {number} controllerId - ID of the controller
 * @param {number} categoryId - ID of the category
 */
function quickSyncBlogs(controllerId, categoryId) {
    if (!categoryId) {
        alert_float('warning', 'Vui lòng chọn một danh mục trước');
        return;
    }
    
    console.log('Quick sync blogs for controller ID:', controllerId, 'category ID:', categoryId);
    
    // Hiển thị thanh tiến trình
    $('.quick-sync-progress').show();
    $('#quick_sync_status').text('Đang đồng bộ bài viết...');
    
    // Vô hiệu hóa nút Quick Sync
    var $quickSyncBtn = $('#quick_sync_blogs');
    var originalHtml = $quickSyncBtn.html();
    $quickSyncBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Đang đồng bộ...');
    
    // Animation cho thanh tiến trình
    var syncProgress = 0;
    var syncTimer = setInterval(function() {
        syncProgress += 5;
        if (syncProgress > 90) {
            syncProgress = 90;
            clearInterval(syncTimer);
        }
        $('#quick_sync_progress').css('width', syncProgress + '%');
    }, 200);
    
    // AJAX request để đồng bộ blogs
    $.ajax({
        url: admin_url + 'topics/controllers/sync_blogs/' + controllerId + '/' + categoryId,
        type: 'POST',
        dataType: 'json',
        data: {
            category_id: categoryId // Thêm category_id vào data để đảm bảo nó được gửi đi
        },
        success: function(response) {
            console.log('Sync response:', response);
            clearInterval(syncTimer);
            $('#quick_sync_progress').css('width', '100%');
            
            if (response.success) {
                $('#quick_sync_status').html('<i class="fa fa-check-circle text-success"></i> ' + 
                    (response.message || 'Đồng bộ thành công ' + (response.count || 0) + ' bài viết'));
                
                // Hiển thị thông báo thành công
                alert_float('success', 'Đồng bộ thành công ' + (response.count || 0) + ' bài viết');
                
                // Cập nhật lại thông tin category mà không làm mất trạng thái hiện tại
                // Thay vì tải lại toàn bộ danh mục, chỉ cập nhật thông tin số lượng bài viết
                if (response.updated_category) {
                    var updatedCategory = response.updated_category;
                    var $categoryLabel = $('.category-label[data-category-id="' + updatedCategory.category_id + '"]');
                    if ($categoryLabel.length > 0) {
                        // Cập nhật số lượng bài viết
                        var labelText = updatedCategory.name + ' <span class="text-muted">(' + (updatedCategory.count || 0) + ')</span>';
                        $categoryLabel.html(labelText);
                        
                        // Kích hoạt lại nút sau khi cập nhật thành công
                        $quickSyncBtn.prop('disabled', false).html(originalHtml);
                        
                        // Ẩn thanh tiến trình sau 3 giây
                        setTimeout(function() {
                            $('.quick-sync-progress').hide();
                        }, 3000);
                    } else {
                        // Nếu không tìm thấy category, tải lại toàn bộ danh mục
                        setTimeout(function() {
                            loadCategories(controllerId);
                        }, 1000);
                    }
                } else {
                    // Nếu không có thông tin category được cập nhật, tải lại toàn bộ danh mục
                    setTimeout(function() {
                        loadCategories(controllerId);
                    }, 1000);
                }
            } else {
                $('#quick_sync_status').html('<i class="fa fa-exclamation-circle text-danger"></i> ' + 
                    (response.message || 'Lỗi khi đồng bộ bài viết'));
                
                // Hiển thị thông báo lỗi
                alert_float('danger', response.message || 'Lỗi khi đồng bộ bài viết');
                
                // Kích hoạt lại nút
                $quickSyncBtn.prop('disabled', false).html(originalHtml);
                
                // Ẩn thanh tiến trình sau 3 giây
                setTimeout(function() {
                    $('.quick-sync-progress').hide();
                }, 3000);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error syncing blogs:', xhr.responseText);
            clearInterval(syncTimer);
            
            $('#quick_sync_progress').css('width', '100%');
            $('#quick_sync_status').html('<i class="fa fa-exclamation-circle text-danger"></i> Lỗi khi đồng bộ bài viết');
            
            // Hiển thị thông báo lỗi
            alert_float('danger', 'Lỗi kết nối khi đồng bộ bài viết: ' + error);
            
            // Kích hoạt lại nút
            $quickSyncBtn.prop('disabled', false).html(originalHtml);
            
            // Ẩn thanh tiến trình sau 3 giây
            setTimeout(function() {
                $('.quick-sync-progress').hide();
            }, 3000);
        }
    });
}

/**
 * Render categories tree recursively
 * @param {Array} categories - List of categories
 */
function renderCategoriesTree(categories) {
    // Xóa nội dung hiện tại
    $('#category_tree').empty();
    
    if (!categories || !Array.isArray(categories) || categories.length === 0) {
        console.error('Invalid or empty categories data:', categories);
        return;
    }
    
    // Convert flat list to tree structure
    var categoryMap = {};
    var rootCategories = [];
    
    // First pass: create map
    categories.forEach(function(category) {
        if (category) {
            category.children = [];
            categoryMap[category.category_id] = category;
        }
    });
    
    // Second pass: build tree
    categories.forEach(function(category) {
        if (!category) return;
        
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
    
    // Create root UL element
    var $rootUl = $('<ul class="list-unstyled"></ul>');
    
    // Build tree recursively
    buildCategoryTree(rootCategories, $rootUl);
    
    // Append to container
    $('#category_tree').append($rootUl);
    
    // Initialize expand/collapse icons and checkboxes
    initializeTreeHandlers();
}

/**
 * Build category tree recursively using jQuery
 * @param {Array} categories - List of categories
 * @param {jQuery} $parentElement - Parent element to append to
 */
function buildCategoryTree(categories, $parentElement) {
    if (!categories || !Array.isArray(categories) || categories.length === 0 || !$parentElement) {
        return;
    }
    
    categories.forEach(function(category) {
        if (!category) return;
        
        var hasChildren = category.children && Array.isArray(category.children) && category.children.length > 0;
        
        // Create list item
        var $li = $('<li></li>');
        
        // Create node div
        var $nodeDiv = $('<div class="category-node"></div>');
        
        // Add expander if has children
        if (hasChildren) {
            $nodeDiv.append('<span class="expander">+</span>');
        } else {
            $nodeDiv.append('<span class="expander" style="visibility:hidden">+</span>');
        }
        
        // Add folder icon
        var folderIcon = hasChildren ? 'fa-folder' : 'fa-folder-o';
        $nodeDiv.append('<i class="fa ' + folderIcon + ' category-icon"></i>');
        
        // Add checkbox
        $nodeDiv.append('<input type="checkbox" class="category-checkbox" data-category-id="' + category.category_id + '">');
        
        // Format last sync date
        var lastSyncText = '<?php echo _l('never_synced'); ?>';
        if (category.last_sync && category.last_sync !== '0000-00-00 00:00:00') {
            var lastSyncDate = new Date(category.last_sync.replace(' ', 'T'));
            if (!isNaN(lastSyncDate.getTime())) {
                lastSyncText = lastSyncDate.toLocaleDateString() + ' ' + lastSyncDate.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
            }
        }
        
        // Add category label with detailed count information
        var labelContent = category.name;
        
        // Add DB and API counts
        var dbCount = category.db_count !== undefined ? category.db_count : (category.count || 0);
        var apiCount = category.api_count !== undefined ? category.api_count : (category.count || 0);
        
        labelContent += ' <span class="text-muted">(DB: ' + dbCount + ' / API: ' + apiCount + ') (' + lastSyncText + ')</span>';
        
        $nodeDiv.append('<span class="category-label" data-category-id="' + category.category_id + '">' + labelContent + '</span>');
        
        // Append node to list item
        $li.append($nodeDiv);
        
        // Add child categories if any
        if (hasChildren) {
            // Sort children by name
            category.children.sort(function(a, b) {
                return a.name.localeCompare(b.name);
            });
            
            // Create child container
            var $childDiv = $('<div class="child-categories"></div>');
            var $childUl = $('<ul class="list-unstyled"></ul>');
            
            // Build children recursively
            buildCategoryTree(category.children, $childUl);
            
            // Append children
            $childDiv.append($childUl);
            $li.append($childDiv);
        }
        
        // Append list item to parent
        $parentElement.append($li);
    });
}

/**
 * Initialize tree behavior
 */
function initializeTreeHandlers() {
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
    
    // Handle checkbox clicks
    $('.category-tree .category-checkbox').on('change', function() {
        // Bỏ chọn tất cả các checkbox khác
        if ($(this).prop('checked')) {
            $('.category-tree .category-checkbox').not(this).prop('checked', false);
            
            // Lưu categoryId được chọn
            selectedCategoryId = $(this).data('category-id');
            
            // Lấy tên category từ label (chỉ lấy tên, không lấy thông tin số lượng và thời gian)
            var $label = $(this).siblings('.category-label');
            var fullText = $label.text();
            var categoryName = fullText.split('(')[0].trim(); // Lấy phần trước dấu ngoặc đầu tiên
            
            // Hiển thị khu vực hành động
            $('#selected_category_name').text(categoryName);
            $('#selected_category_info').addClass('active');
            $('.quick-sync-progress').hide();
        } else {
            // Nếu bỏ chọn, xóa categoryId và ẩn khu vực hành động
            selectedCategoryId = null;
            $('#selected_category_info').removeClass('active');
        }
    });
    
    // Handle category label clicks
    $('.category-tree .category-label').on('click', function() {
        // Tìm checkbox đi kèm và toggle trạng thái
        var $checkbox = $(this).siblings('.category-checkbox');
        $checkbox.prop('checked', !$checkbox.prop('checked')).trigger('change');
    });
    
    // Add keyboard support for accessibility
    $('.category-tree .expander').on('keypress', function(e) {
        if (e.which === 13 || e.which === 32) { // Enter or Space key
            e.preventDefault();
            $(this).click();
        }
    });
}

/**
 * Initialize categories with saved state
 * @param {number} controllerId - ID of the controller
 * @param {Array} expandedCategories - List of expanded category IDs
 */
function initCategoriesWithSavedState(controllerId, expandedCategories) {
    if (!expandedCategories || !Array.isArray(expandedCategories)) {
        console.error('Invalid expanded categories data:', expandedCategories);
        return;
    }
    
    // Expand categories that were previously expanded
    expandedCategories.forEach(function(categoryId) {
        var $categoryItem = $('.category-tree .category-label[data-category-id="' + categoryId + '"]').closest('li');
        var $expander = $categoryItem.find('> .category-node > .expander');
        var $childCategories = $categoryItem.find('> .child-categories');
        var $folderIcon = $expander.siblings('.category-icon');
        
        if ($expander.length && !$childCategories.hasClass('expanded')) {
            $childCategories.addClass('expanded');
            $expander.text('-');
            $folderIcon.removeClass('fa-folder').addClass('fa-folder-open');
        }
    });
}
</script> 