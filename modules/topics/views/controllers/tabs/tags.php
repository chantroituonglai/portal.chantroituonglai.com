<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<style>
/* Giao diện chung */
.tags-container {
    margin-top: 15px;
}

/* Cấu trúc cây phân cấp */
.tags-list {
    padding: 0;
    margin: 0;
    font-family: Arial, sans-serif;
    list-style: none;
}

.tags-list ul {
    list-style: none;
    padding-left: 20px;
    margin: 0;
}

.tag-item {
    margin: 5px 0;
    position: relative;
}

.tag-header {
    display: flex;
    align-items: center;
    padding: 5px;
    border-radius: 3px;
    transition: background-color 0.2s;
}

.tag-header:hover {
    background-color: #f5f5f5;
}

.tag-toggle {
    display: inline-block;
    width: 16px;
    height: 16px;
    text-align: center;
    line-height: 16px;
    cursor: pointer;
    margin-right: 5px;
    color: #555;
}

.tag-toggle-placeholder {
    display: inline-block;
    width: 16px;
    margin-right: 5px;
}

.tag-name {
    font-weight: 500;
    margin-right: 5px;
}

.tag-count {
    color: #777;
    font-size: 12px;
    margin-right: 10px;
}

.tag-link {
    margin-left: auto;
    color: #777;
}

.tag-children {
    display: none;
}

.tag-item.expanded > .tag-children {
    display: block;
}

.tag-item.expanded > .tag-header > .tag-toggle > .fa {
    transform: rotate(90deg);
}
</style>

<div class="tags-container">
    <div class="row mbot15">
        <div class="col-md-6">
            <h4><?php echo _l('controller_tag_list'); ?></h4>
            <small><?php echo _l('last_sync'); ?>: <span id="tags_last_sync">-</span></small>
        </div>
        <div class="col-md-6 text-right">
            <button type="button" class="btn btn-primary" id="get_tags_db">
                <i class="fa fa-database"></i> <?php echo _l('get_from_database', 'Get from Database'); ?>
            </button>
            <button type="button" class="btn btn-info" id="get_tags_api">
                <i class="fa fa-refresh"></i> <?php echo _l('get_from_api', 'Get from API'); ?>
            </button>
            <button type="button" class="btn btn-default" id="show_sync_sessions">
                <i class="fa fa-history"></i> Các phiên đồng bộ
                <span class="badge active-sessions-badge" style="display:none;">0</span>
            </button>
        </div>
    </div>
    
    <!-- Phiên đồng bộ gần đây -->
    <div id="sync_sessions_container" class="row" style="display:none;">
        <div class="col-md-12">
            <div class="panel_s">
                <div class="panel-heading">
                    <h4>Phiên đồng bộ gần đây</h4>
                </div>
                <div class="panel-body">
                    <div id="sync_sessions_loading" class="text-center mtop10 mbot10">
                        <div class="spinner">
                            <div class="bounce1"></div>
                            <div class="bounce2"></div>
                            <div class="bounce3"></div>
                        </div>
                        <p>Đang tải danh sách phiên đồng bộ...</p>
                    </div>
                    <div id="sync_sessions_list" class="mtop10" style="display:none;">
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>ID Phiên</th>
                                    <th>Trạng thái</th>
                                    <th>Tiến độ</th>
                                    <th>Bắt đầu</th>
                                    <th>Kết thúc</th>
                                    <th>Chức năng</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Nội dung sẽ được điền bởi JavaScript -->
                            </tbody>
                        </table>
                    </div>
                    <div id="sync_sessions_empty" class="alert alert-info mtop10" style="display:none;">
                        Không có phiên đồng bộ nào gần đây
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-12">
            <div class="panel_s">
                <div class="panel-body">
                    <!-- Loading indicator -->
                    <div id="tags_loading" class="text-center mtop20 mbot20">
                        <div class="spinner">
                            <div class="bounce1"></div>
                            <div class="bounce2"></div>
                            <div class="bounce3"></div>
                        </div>
                        <p class="mtop10"><?php echo _l('loading_tags'); ?></p>
                        <div class="progress mtop10">
                            <div id="tags_loading_progress" class="progress-bar progress-bar-striped active" role="progressbar" 
                                aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width:0%">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tags list container -->
                    <div id="tags_list" class="mtop20" style="display: none;">
                        <!-- DataTable sẽ được thêm vào đây -->
                    </div>
                    
                    <!-- Empty state -->
                    <div id="tags_empty" class="alert alert-info mtop20" style="display: none;">
                        <?php echo _l('controller_no_data'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Global variables
var selectedTagId = null;
var currentSyncSessionId = null;
var syncPollingInterval = null;
var lastUpdateTimestamp = 0;
var minTagsPerSync = 20; // Số lượng tags tối thiểu mỗi lần đồng bộ
var maxIdleTimeSeconds = 60; // Thời gian tối đa chờ đợi (giây) trước khi tự động tiếp tục
var lastProcessedCount = 0; // Biến toàn cục để theo dõi số lượng tags đã xử lý trong lần kiểm tra trước

/**
 * Function to add a log message to the sync details
 * @param {string} message - The message to display
 * @param {string} type - The type of message (success, error, warning, info)
 */
function addSyncLogMessage(message, type) {
    var timestamp = new Date().toLocaleTimeString();
    var cssClass = type === 'error' ? 'text-danger' : 
                   type === 'success' ? 'text-success' : 
                   type === 'warning' ? 'text-warning' : 'text-muted';
    
    var logHtml = '<div class="' + cssClass + '">' +
                  '<small>[' + timestamp + ']</small> ' + message +
                  '</div>';
    
    // Thêm vào #tags_sync_details nếu có
    if ($('#tags_sync_details').length) {
        $('#tags_sync_details .sync-logs').append(logHtml);
        
        // Scroll xuống cuối để thấy log mới nhất
        var logsWrapper = $('#tags_sync_details .sync-logs-wrapper');
        if (logsWrapper.length) {
            logsWrapper.scrollTop(logsWrapper[0].scrollHeight);
        }
    } 
    
    // Thêm vào .sync-logs nếu có - đảm bảo logs được thêm vào đúng vị trí
    if ($('.sync-logs').length) {
        $('.sync-logs').append(logHtml);
        
        // Scroll xuống cuối để thấy log mới nhất
        var logsWrapper = $('.sync-logs-wrapper');
        if (logsWrapper.length) {
            logsWrapper.scrollTop(logsWrapper[0].scrollHeight);
        }
    }
    
    // Log vào console để theo dõi 
    console.log('[' + (type || 'info') + '] ' + message);
}

/**
 * Function to update the sync session status
 * @param {number} controllerId - The controller ID
 * @param {number} sessionId - The sync session ID
 * @param {object} sessionData - The data to update (status, error_message, etc.)
 * @param {function} callback - Optional callback function to execute after successful update
 */
function updateSyncSession(controllerId, sessionId, sessionData, callback) {
    if (!sessionId) {
        console.error('No session ID provided for update');
        return;
    }
    
    console.log('Updating sync session:', sessionId, 'with data:', sessionData);
    
    $.ajax({
        url: admin_url + 'topics/controllers/update_tags_sync_status/' + sessionId,
        type: 'POST',
        data: sessionData,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                console.log('Sync session updated successfully');
                
                // Cập nhật localStorage nếu cần
                if (sessionData.status) {
                    localStorage.setItem('tags_sync_status_' + controllerId, sessionData.status);
                }
                
                // Gọi callback nếu được cung cấp
                if (typeof callback === 'function') {
                    callback(response);
                }
            } else {
                console.error('Failed to update sync session:', response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX error when updating sync session:', error);
        }
    });
}

/**
 * Load tags from database
 * @param {number} controllerId - ID of controller
 */
function loadTags(controllerId) {
    if (!controllerId) {
        return;
    }

    console.log("Loading tags for controller ID: " + controllerId);
    
    // Show loading
    $('#tags_list').hide();
    $('#tags_empty').hide();
    $('#tags_loading').show();
    
    // Fetch tags sync state (chỉ lấy thời gian đồng bộ cuối cùng)
    $('#tags_datatable_wrapper').addClass('table-loading');
    $.ajax({
        url: admin_url + 'topics/controllers/get_tags_sync_state/' + controllerId,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            // Update sync time if available
            if (response.success && response.last_sync) {
                $('#tags_last_sync').text(response.last_sync);
            }
            
            // Thiết lập bảng DataTable
            setupTagsTable(controllerId);
            
            // Ẩn loading và hiện bảng
            $('#tags_loading').hide();
            $('#tags_list').show();
            $('#tags_datatable_wrapper').removeClass('table-loading');
        },
        error: function() {
            // Vẫn thiết lập bảng ngay cả khi có lỗi
            setupTagsTable(controllerId);
            
            // Ẩn loading và hiện bảng
            $('#tags_loading').hide();
            $('#tags_list').show();
            $('#tags_datatable_wrapper').removeClass('table-loading');
        }
    });
}

// Hàm thiết lập bảng DataTable
function setupTagsTable(controllerId) {
    // Nếu đã khởi tạo DataTable rồi, hủy nó trước khi khởi tạo lại
    if ($.fn.DataTable.isDataTable('#tags_datatable')) {
        $('#tags_datatable').DataTable().destroy();
    }
    
    // Xây dựng HTML cho bảng
    var tableHtml = '<table class="table table-tags dt-table" id="tags_datatable">';
    tableHtml += '<thead>';
    tableHtml += '<tr>';
    tableHtml += '<th><?php echo _l('id'); ?></th>';
    tableHtml += '<th><?php echo _l('name'); ?></th>';
    tableHtml += '<th><?php echo _l('slug'); ?></th>';
    tableHtml += '<th><?php echo _l('post_count'); ?></th>';
    tableHtml += '<th><?php echo _l('options'); ?></th>';
    tableHtml += '</tr>';
    tableHtml += '</thead>';
    tableHtml += '<tbody>';
    
    // Không cần thêm dữ liệu vào tbody, DataTable sẽ tự làm việc đó
    tableHtml += '</tbody>';
    tableHtml += '</table>';
    
    // Thay thế nội dung
    $('#tags_list').html(tableHtml);
    
    // Khởi tạo DataTable
    var dataTable = $('#tags_datatable').DataTable({
        language: appLang,
        beforeSend: function () {
                $('#tags_datatable_wrapper').addClass('table-loading');
        },
        complete: function () {
            $('#tags_datatable_wrapper').removeClass('table-loading');
        },
        processing: true,
        responsive: true,
        autoWidth: false,
        pageLength: 25,
        serverSide: true, // Sử dụng dữ liệu từ server
        ajax: {
            url: admin_url + 'topics/controllers/get_tags_table/' + controllerId,
            type: 'GET'
        },
        dom: "<'row'<'col-md-7'lB><'col-md-5'f>>rt<'row'<'col-md-4'i><'col-md-8'p>>",
        buttons: [
            {
                extend: 'collection',
                text: '<?php echo _l('export'); ?>',
                className: 'btn btn-default',
                buttons: [
                    { extend: 'excel', text: 'Excel' },
                    { extend: 'csv', text: 'CSV' },
                    { extend: 'pdf', text: 'PDF' }
                ]
            }
        ],
        columns: [
            { data: 'tag_id' },
            { 
                data: 'name',
                render: function(data, type, row) {
                    return '<a href="' + row.url + '" target="_blank">' + data + '</a>';
                }
            },
            { data: 'slug' },
            { data: 'count' },
            {
                data: null,
                orderable: false,
                render: function(data, type, row) {
                    var options = '<div class="dropdown action-relative">';
                    options += '<button class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
                    options += '<i class="fa fa-cog"></i>';
                    options += '</button>';
                    
                    options += '<ul class="dropdown-menu dropdown-menu-right">';
                    options += '<li><a href="' + row.url + '" target="_blank"><?php echo _l('view_in_platform'); ?></a></li>';
                    // Thêm các tùy chọn khác nếu cần
                    options += '</ul>';
                    
                    options += '</div>';
                    return options;
                }
            }
        ],
        columnDefs: [
            {
                targets: [0],
                width: '80px'
            }
        ],
        order: [[1, 'asc']] // Sắp xếp theo tên
    });
    
    return dataTable;
}

/**
 * Sync tags with platform API
 * @param {number} controllerId - ID of the controller
 */
function syncTags(controllerId) {
    if (!controllerId) {
        return;
    }
    
    console.log("Starting syncTags for controller ID: " + controllerId);
    
    // Show loading
    $('#tags_list').hide();
    $('#tags_empty').hide();
    $('#tags_loading').show();
    $('#tags_loading_progress').css('width', '0%');
    
    // Disable sync button
    $('#get_tags_api').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> <?php echo _l('get_from_api', 'Get from API'); ?>');
    
    // Initialize sync status
    var syncStatus = {
        totalTags: 0,
        currentPage: 1,
        totalPages: 1,
        isComplete: false
    };
    
    // Update progress UI
    function updateProgress() {
        var progressPercent = (syncStatus.currentPage / syncStatus.totalPages) * 100;
        $('#tags_loading_progress').css('width', progressPercent + '%');
        
        // Main status text
        var statusText = '<?php echo _l('syncing_tags_progress', 'Syncing tags'); ?>';
        statusText += ' - ' + syncStatus.currentPage + '/' + syncStatus.totalPages;
        if (syncStatus.totalTags > 0) {
            statusText += ' (' + syncStatus.totalTags + ' <?php echo _l('tags_found', 'tags found'); ?>)';
        }
        $('#tags_loading p').text(statusText);
        
        // Add detailed progress information
        if (!$('#tags_sync_details').length) {
            var detailsHtml = '<div id="tags_sync_details" class="mtop10 small text-left" style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; border-radius: 4px; background-color: #f8f8f9;"></div>';
            $('#tags_loading').append(detailsHtml);
        }
    }
    
    /**
     * Hiển thị UI loading khi đồng bộ tags
     */
    function showTagSyncLoadingUI() {
        // Ẩn các phần không cần thiết
        $('#tags_list').hide();
        $('#tags_empty').hide();
        
        // Hiển thị loading
        $('#tags_loading').show();
        
        // Đảm bảo container chi tiết đồng bộ được tạo
        if (!$('#tags_sync_details').length) {
            var detailsHtml = '<div id="tags_sync_details" class="mtop10 small text-left" style="max-height: 500px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; border-radius: 4px; background-color: #f8f8f9;"></div>';
            $('#tags_loading').append(detailsHtml);
        }
    }
    
    /**
     * Hiển thị danh sách tags và ẩn loading UI
     */
    function showTagsList() {
        // Ẩn loading và thông báo
        $('#tags_loading').hide();
        $('#tags_resume_message').remove();
        
        // Xóa phiên hiện tại
        currentSyncSessionId = null;
        
        // Dừng polling nếu đang chạy
        stopSyncLogPolling();
        
        // Hiển thị danh sách tags nếu có, ngược lại hiển thị thông báo rỗng
        if ($('#tags_datatable').length && $('#tags_datatable').find('tbody tr').length > 0) {
            $('#tags_list').show();
        } else {
            $('#tags_empty').show();
        }
        
        // Reset nút đồng bộ
        $('#get_tags_api').prop('disabled', false).html('<i class="fa fa-refresh"></i> <?php echo _l('get_from_api', 'Get from API'); ?>');
    }
    
    // Function to perform the sync for a specific page
    function performSync(controllerId, url, page, sessionId, totalPages) {
        // Hiển thị thông báo đang đồng bộ
        showTagSyncLoadingUI();
        
        // Gửi yêu cầu AJAX để lấy tags từ API
        $.ajax({
            url: url,
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                console.log('Sync response:', response);
                
                // Kiểm tra lỗi từ API
                if (!response.success) {
                    var errorMessage = response.message || 'Lỗi không xác định khi đồng bộ tags';
                    addSyncLogMessage('Lỗi: ' + errorMessage, 'error');
                    
                    // Cập nhật thanh tiến trình hiển thị lỗi
                    $('#tags_loading_progress').css('width', '100%').addClass('progress-bar-danger');
                    $('#tags_loading p').html('<i class="fa fa-times-circle"></i> Đồng bộ lỗi');
                    
                    // Sau 3 giây, ẩn UI loading và hiển thị thông báo lỗi
                    setTimeout(function() {
                        $('#tags_loading').hide();
                        alert_float('danger', errorMessage);
                        
                        // Cập nhật trạng thái session là lỗi
                        updateSyncSession(controllerId, sessionId, {
                            status: 'error',
                            error_message: errorMessage
                        });
                        
                        return;
                    }, 3000);
                    
                    // Cập nhật trạng thái session là lỗi
                    updateSyncSession(controllerId, sessionId, {
                        status: 'error',
                        error_message: errorMessage
                    });
                    
                    return;
                }
                
                // Xử lý phản hồi thành công
                var tags = response.data.tags || [];
                var nextPage = response.data.next_page;
                var isCompleted = response.data.is_completed;
                var totalPages = response.data.total_pages || totalPages;
                
                // Đảm bảo nextPage có giá trị khi cần thiết
                if (isCompleted === false && !nextPage) {
                    nextPage = parseInt(page) + 1;
                    console.log('nextPage không được xác định, tính toán từ page hiện tại:', nextPage);
                }
                
                // Log thông tin đồng bộ
                if (tags.length > 0) {
                    addSyncLogMessage('Đã đồng bộ ' + tags.length + ' tags từ trang ' + page, 'success');
                    
                    // Log từng tag đã đồng bộ
                    tags.forEach(function(tag) {
                        addSyncLogMessage('Tag: ' + tag.name + ' (ID: ' + tag.id + ')', 'info');
                    });
                    } else {
                    addSyncLogMessage('Không có tags nào được đồng bộ từ trang ' + page, 'warning');
                }
                
                // Cập nhật thông tin session
                var sessionData = {
                    current_page: page,
                    total_pages: totalPages,
                    processed_tags: tags.length,
                    total_processed_tags: (parseInt(response.data.total_processed_tags) || 0)
                };
                
                // Xác định trạng thái dựa trên isCompleted và nextPage
                if (isCompleted === true || isCompleted === 'true') {
                    sessionData.status = 'completed';
                    addSyncLogMessage('Đồng bộ hoàn tất!', 'success');
                } else if (isCompleted === false || isCompleted === 'false') {
                    if (nextPage) {
                        // Nếu server báo chưa hoàn thành và có trang tiếp theo, tiếp tục đồng bộ
                        sessionData.status = 'in_progress';
                        var nextUrl = url.replace(/[?&]page=\d+/, '') + (url.indexOf('?') > -1 ? '&' : '?') + 'page=' + nextPage + '&session_id=' + sessionId;
                        
                        addSyncLogMessage('Tiếp tục đồng bộ trang ' + nextPage + ' / ' + totalPages, 'info');
                        
                        // Cập nhật session trước khi chuyển sang trang tiếp theo
                        updateSyncSession(controllerId, sessionId, sessionData, function() {
                            // Gọi đệ quy để đồng bộ trang tiếp theo
                            performSync(controllerId, nextUrl, nextPage, sessionId, totalPages);
                        });
                        
                        return;
                    } else if (response.data.status === 'pending') {
                        // Nếu server báo trạng thái là "pending", cập nhật session và hiển thị trạng thái đang chờ
                        sessionData.status = 'pending';
                        addSyncLogMessage('Server đang xử lý dữ liệu. Đồng bộ sẽ tiếp tục sau khi xử lý hoàn tất.', 'warning');
                    } else {
                        // Trường hợp lạ: isCompleted = false nhưng không có nextPage và không phải pending
                        sessionData.status = 'failed';
                        addSyncLogMessage('Không thể xác định trang tiếp theo. Đồng bộ bị gián đoạn.', 'error');
                    }
                } else {
                    // Trường hợp isCompleted không được xác định rõ ràng
                    if (nextPage) {
                        // Vẫn còn trang tiếp theo, tiếp tục đồng bộ
                        sessionData.status = 'in_progress';
                    } else {
                        // Không có trang tiếp theo, coi như đã hoàn thành
                        sessionData.status = 'completed';
                        addSyncLogMessage('Đồng bộ hoàn tất (theo ngầm định)!', 'success');
                    }
                }
                
                // Cập nhật session và tải lại danh sách sessions
                updateSyncSession(controllerId, sessionId, sessionData, function() {
                    loadSyncSessions(controllerId);
                    
                    // Nếu đã hoàn thành, hiển thị danh sách tags
                    if (sessionData.status === 'completed') {
                        // Thực hiện các thao tác của showTagsList trực tiếp
                        $('#tags_loading').hide();
                        $('#tags_resume_message').remove();
                        currentSyncSessionId = null;
                        if (syncPollingInterval) {
                            clearInterval(syncPollingInterval);
                            syncPollingInterval = null;
                        }
                        if ($('#tags_datatable').length && $('#tags_datatable').find('tbody tr').length > 0) {
                            $('#tags_list').show();
                        } else {
                            $('#tags_empty').show();
                        }
                        $('#get_tags_api').prop('disabled', false).html('<i class="fa fa-refresh"></i> <?php echo _l('get_from_api', 'Get from API'); ?>');
                        
                        // Tải lại danh sách tags sau khi đồng bộ hoàn tất
                        tagsTable.ajax.reload(function() {
                            // Hiển thị thông báo thành công
                            alert_float('success', 'Đồng bộ tags thành công!');
                        });
                    } else {
                        // Nếu là trạng thái pending hoặc error, vẫn giữ giao diện đồng bộ
                        // nhưng khởi động quá trình kiểm tra định kỳ
                        if (sessionData.status === 'pending') {
                            startSyncLogPolling(controllerId, sessionId);
                        }
                    }
                });
            },
            error: function(xhr, status, error) {
                // Xử lý lỗi kết nối
                console.error('Sync error:', error);
                var errorMessage = 'Lỗi kết nối: ' + (error || status);
                addSyncLogMessage(errorMessage, 'error');
                
                // Cập nhật trạng thái session là lỗi
                updateSyncSession(controllerId, sessionId, {
                    status: 'error',
                    error_message: errorMessage
                }, function() {
                    loadSyncSessions(controllerId);
                });
            }
        });
    }
    
    // Start the sync process
    updateProgress();
    addSyncLogMessage('<?php echo _l('tags_sync_in_progress', 'Tags synchronization in progress...'); ?>');
    
    // Gửi yêu cầu đồng bộ ban đầu để có được session_id từ server
    $.ajax({
        url: admin_url + 'topics/controllers/sync_tags/' + controllerId,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                var sessionId = response.session_id;
                if (sessionId) {
                    console.log('Sync started with session ID:', sessionId);
                    currentSyncSessionId = sessionId;
                    // Lưu vào localStorage
                    localStorage.setItem('tags_sync_session_' + controllerId, sessionId);
                    // Bắt đầu đồng bộ với session_id
                    performSync(controllerId, admin_url + 'topics/controllers/sync_tags/' + controllerId + '?session_id=' + sessionId, 1, sessionId, syncStatus.totalPages);
                } else {
                    console.error('No session ID returned from server');
                    alert_float('danger', 'Không thể bắt đầu đồng bộ: Không có ID phiên được trả về từ máy chủ');
                    
                    // Thực hiện các thao tác của showTagsList trực tiếp
                    $('#tags_loading').hide();
                    $('#tags_resume_message').remove();
                    currentSyncSessionId = null;
                    if (syncPollingInterval) {
                        clearInterval(syncPollingInterval);
                        syncPollingInterval = null;
                    }
                    if ($('#tags_datatable').length && $('#tags_datatable').find('tbody tr').length > 0) {
                        $('#tags_list').show();
                    } else {
                        $('#tags_empty').show();
                    }
                    $('#get_tags_api').prop('disabled', false).html('<i class="fa fa-refresh"></i> <?php echo _l('get_from_api', 'Get from API'); ?>');
                }
            } else {
                console.error('Failed to start sync:', response.message);
                alert_float('danger', 'Không thể bắt đầu đồng bộ: ' + (response.message || 'Lỗi không xác định'));
                
                // Thực hiện các thao tác của showTagsList trực tiếp
                $('#tags_loading').hide();
                $('#tags_resume_message').remove();
                currentSyncSessionId = null;
                if (syncPollingInterval) {
                    clearInterval(syncPollingInterval);
                    syncPollingInterval = null;
                }
                if ($('#tags_datatable').length && $('#tags_datatable').find('tbody tr').length > 0) {
                    $('#tags_list').show();
                } else {
                    $('#tags_empty').show();
                }
                $('#get_tags_api').prop('disabled', false).html('<i class="fa fa-refresh"></i> <?php echo _l('get_from_api', 'Get from API'); ?>');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error starting sync:', error);
            alert_float('danger', 'Lỗi kết nối khi bắt đầu đồng bộ');
            
            // Thực hiện các thao tác của showTagsList trực tiếp
            $('#tags_loading').hide();
            $('#tags_resume_message').remove();
            currentSyncSessionId = null;
            if (syncPollingInterval) {
                clearInterval(syncPollingInterval);
                syncPollingInterval = null;
            }
            if ($('#tags_datatable').length && $('#tags_datatable').find('tbody tr').length > 0) {
                $('#tags_list').show();
            } else {
                $('#tags_empty').show();
            }
            $('#get_tags_api').prop('disabled', false).html('<i class="fa fa-refresh"></i> <?php echo _l('get_from_api', 'Get from API'); ?>');
        }
    });
}

// Biến toàn cục để lưu trữ ID phiên đồng bộ hiện tại
// var currentSyncSessionId = null; // Đã khai báo ở đầu file
// var syncPollingInterval = null; // Đã khai báo ở đầu file

// Kiểm tra LocalStorage để tìm session đồng bộ gần đây nhất
function checkExistingSyncSession(controllerId) {
    var sessionId = localStorage.getItem('tags_sync_session_' + controllerId);
    var sessionStatus = localStorage.getItem('tags_sync_status_' + controllerId);
    
    if (sessionId && sessionStatus !== 'completed' && sessionStatus !== 'error') {
        console.log('Found previous sync session: ' + sessionId + ' with status: ' + sessionStatus);
        
        // Cập nhật số phiên đang chạy vào badge và hiển thị
        updateActiveSyncSessionsBadge(controllerId);
        
        // KHÔNG tự động bắt đầu theo dõi phiên nữa, để người dùng chủ động click vào nút theo dõi
        // startSyncLogPolling(controllerId, sessionId);
        return true;
    }
    
    return false;
}

// Hàm mới để cập nhật badge thể hiện số phiên đang chạy
function updateActiveSyncSessionsBadge(controllerId) {
    $.ajax({
        url: admin_url + 'topics/controllers/get_tags_sync_logs/' + controllerId,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success && response.data && response.data.length > 0) {
                // Đếm số phiên đang chạy và cập nhật badge
                var activeSessionsCount = 0;
                for (var i = 0; i < response.data.length; i++) {
                    if (response.data[i].status === 'in_progress') {
                        activeSessionsCount++;
                    }
                }
                
                if (activeSessionsCount > 0) {
                    $('.active-sessions-badge').text(activeSessionsCount).show();
                } else {
                    $('.active-sessions-badge').hide();
                }
            } else {
                $('.active-sessions-badge').hide();
            }
        }
    });
}

// Biến toàn cục để theo dõi thời gian của lần cập nhật cuối
var lastUpdateTimestamp = 0;
var minTagsPerSync = 20; // Số lượng tags tối thiểu mỗi lần đồng bộ
var maxIdleTimeSeconds = 60; // Thời gian tối đa chờ đợi (giây) trước khi tự động tiếp tục

// Hàm để bắt đầu polling nhật ký đồng bộ
function startSyncLogPolling(controllerId, sessionId) {
    // Lưu ID phiên đồng bộ hiện tại
    currentSyncSessionId = sessionId;
    
    // Lưu vào localStorage để sau khi refresh vẫn giữ được
    localStorage.setItem('tags_sync_session_' + controllerId, sessionId);
    
    // Log the session ID for debugging
    console.log('Starting sync log polling for session ID: ' + sessionId);
    
    // Ẩn container danh sách phiên
    $('#sync_sessions_container').hide();
    
    // Hiển thị UI loading nếu chưa hiển thị
    $('#tags_list').hide();
    $('#tags_empty').hide();
    $('#tags_loading').show();
    $('#tags_loading_progress').css('width', '0%');
    
    // Dừng polling hiện tại nếu có
    stopSyncLogPolling();
    
    // Đảm bảo rằng container chi tiết đồng bộ đã được tạo
    if (!$('#tags_sync_details').length) {
        var detailsHtml = '<div id="tags_sync_details" class="mtop10 small text-left" style="max-height: 500px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; border-radius: 4px; background-color: #f8f8f9;"></div>';
        $('#tags_loading').append(detailsHtml);
    }
    
    // Khởi tạo giá trị mặc định cho nextPage và totalPages
    var nextPage = 1; // Mặc định bắt đầu từ trang 1
    var totalPages = 0; // Ban đầu chưa biết tổng số trang
    
    // Thêm header cho nhật ký đồng bộ
    $('#tags_sync_details').html(
        '<div class="sync-header" style="position: sticky; top: 0; background-color: #f8f8f9; padding-bottom: 10px; border-bottom: 1px solid #ddd; z-index: 100;">' +
        '<h5><i class="fa fa-sync"></i> Thông tin phiên đồng bộ</h5>' +
        '<div class="sync-session-id">ID phiên: ' + sessionId + '</div>' +
        '<div class="sync-page">Trang: ' + nextPage + (totalPages ? '/' + totalPages : '') + '</div>' +
        '<div class="sync-controls mtop10"><button class="btn btn-xs btn-danger cancel-sync-btn"><i class="fa fa-times"></i> Hủy đồng bộ</button></div>' +
        '</div>' +
        '<div class="sync-summary mtop10" style="position: sticky; top: 120px; background-color: #f8f8f9; padding: 10px 0; border-bottom: 1px solid #ddd; z-index: 99;"></div>' +
        '<div class="sync-logs-wrapper" style="height: 200px; overflow-y: auto; margin-top: 15px;">' +
        '<div class="sync-logs"></div>' +
        '</div>'
    );
    
    // Xử lý sự kiện hủy đồng bộ
    $('.cancel-sync-btn').on('click', function() {
        if (confirm('Bạn có chắc chắn muốn hủy quá trình đồng bộ này?')) {
            cancelSyncSession(controllerId, sessionId);
        }
    });
    
    // Thực hiện polling đầu tiên ngay lập tức
    pollSyncLog(controllerId);
    
    // Bắt đầu polling định kỳ
    syncPollingInterval = setInterval(function() {
        pollSyncLog(controllerId);
    }, 2000); // Polling mỗi 2 giây
}

// Hàm để hủy phiên đồng bộ
function cancelSyncSession(controllerId, sessionId) {
    $.ajax({
        url: admin_url + 'topics/controllers/cancel_tags_sync/' + sessionId,
        type: 'POST',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                stopSyncLogPolling();
                alert_float('info', 'Đã hủy quá trình đồng bộ');
                
                // Cập nhật trạng thái trong localStorage
                localStorage.setItem('tags_sync_status_' + controllerId, 'cancelled');
                
                // Làm mới danh sách phiên đồng bộ
                refreshSyncSessionsList(controllerId);
                
                // Không tự động tải lại danh sách tags để tránh tạo phiên đồng bộ mới
                // setTimeout(function() {
                //     loadTags(controllerId);
                // }, 1000);
            } else {
                alert_float('warning', 'Không thể hủy quá trình đồng bộ: ' + response.message);
            }
        },
        error: function() {
            alert_float('danger', 'Lỗi khi gửi yêu cầu hủy đồng bộ');
        }
    });
}

// Hàm thực hiện polling
function pollSyncLog(controllerId) {
    if (!currentSyncSessionId) {
        console.error('No sync session ID available for polling');
        return;
    }
    
    console.log('Polling sync log for session: ' + currentSyncSessionId);
    
    // Lấy chi tiết phiên đồng bộ hiện tại
    $.ajax({
        url: admin_url + 'topics/controllers/get_tags_sync_log_details/' + currentSyncSessionId,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            console.log('Received polling response:', response);
            
            if (response.success) {
                // Cập nhật trạng thái trong localStorage
                localStorage.setItem('tags_sync_status_' + controllerId, response.summary.status);
                
                // Cập nhật giao diện với dữ liệu mới nhất
                updateSyncLogUI(response.summary, response.logs);
                console.log('response.summary.status', response.summary.status);
                
                // Kiểm tra các trạng thái khác nhau
                if (response.summary.status === 'pending') {
                    // Trạng thái 'pending' có nghĩa là server đã xử lý xong một đợt và đang chờ client tiếp tục
                    console.log('Server is in pending state, continuing to next page');
                    
                    // Lấy thông tin trang tiếp theo
                    var nextPage = response.summary.current_page + 1;
                    if (nextPage <= response.summary.total_pages) {
                        // Nếu còn trang tiếp theo, thực hiện đồng bộ
                        addSyncLogMessage('Phát hiện trạng thái pending. Tiếp tục đồng bộ sang trang ' + nextPage, 'info');
                        
                        // Tạo URL để tiếp tục đồng bộ
                        var nextUrl = admin_url + 'topics/controllers/sync_tags/' + controllerId + 
                                     '?page=' + nextPage + '&session_id=' + currentSyncSessionId + '&_=' + new Date().getTime();
                        
                        // Gọi API để tiếp tục đồng bộ
                        processSyncRequest(nextUrl, controllerId);
                    } else {
                        // Nếu không còn trang tiếp theo, có thể đánh dấu là đã hoàn thành
                        addSyncLogMessage('Đã đồng bộ tất cả các trang.', 'success');
                        $('#tags_loading_progress').css('width', '100%');
                        
                        // Cập nhật trạng thái thành 'completed'
                        $.ajax({
                            url: admin_url + 'topics/controllers/update_tags_sync_status/' + currentSyncSessionId,
                            type: 'POST',
                            data: {
                                status: 'completed'
                            },
                            dataType: 'json',
                            success: function(updateResponse) {
                                if (updateResponse.success) {
                                    alert_float('success', 'Đồng bộ tags hoàn tất!');
                                    stopSyncLogPolling();
                                    
                                    // Tải lại danh sách tags sau khi đồng bộ hoàn tất
                                    setTimeout(function() {
                                        loadTags(controllerId);
                                    }, 3000);
                                }
                            }
                        });
                    }
                } else if (response.summary.status !== 'in_progress') {
                    // Nếu đồng bộ đã hoàn thành (không phải pending hoặc in_progress), dừng polling
                    console.log('Sync completed with status: ' + response.summary.status);
                    stopSyncLogPolling();
                    
                    // Hiển thị thông báo hoàn thành
                    if (response.summary.status === 'completed') {
                        alert_float('success', 'Đồng bộ tags hoàn tất: ' + 
                            response.summary.success_count + ' tags thành công, ' + 
                            response.summary.error_count + ' lỗi');
                    } else if (response.summary.status === 'cancelled') {
                        alert_float('info', 'Đồng bộ tags đã bị hủy');
                    } else {
                        alert_float('warning', 'Đồng bộ tags thất bại');
                    }
                    
                    // Tải lại danh sách tags sau khi đồng bộ hoàn tất
                    setTimeout(function() {
                        loadTags(controllerId);
                    }, 3000);
                } else {
                    console.log('Sync is still in progress');
                    
                    // Kiểm tra thời gian từ lần cập nhật cuối
                    var currentTime = Math.floor(Date.now() / 1000); // Thời gian hiện tại (giây)
                    var lastUpdateTime = response.summary.last_update_timestamp || 0;
                    
                    if (typeof lastUpdateTime === 'string' && response.summary.last_update) {
                        // Nếu là chuỗi thời gian, chuyển đổi thành timestamp
                        lastUpdateTime = new Date(response.summary.last_update).getTime() / 1000;
                    }
                    
                    var timeSinceLastUpdate = currentTime - lastUpdateTime;
                    
                    // Sử dụng processed_tags thay vì items_processed vì items_processed luôn là 0
                    var currentProcessedTags = response.summary.processed_tags || 0;
                    var tagsProcessedSinceLastCheck = currentProcessedTags - (lastProcessedCount || 0);
                    lastProcessedCount = currentProcessedTags; // Cập nhật lại số lượng đã xử lý
                    
                    console.log('Time since last update:', timeSinceLastUpdate, 'seconds');
                    console.log('Current processed tags:', currentProcessedTags);
                    console.log('Tags processed since last check:', tagsProcessedSinceLastCheck);
                    console.log('Current page:', response.summary.current_page, 'Total pages:', response.summary.total_pages);
                    
                    // Kiểm tra nếu:
                    // 1. Đã qua khoảng thời gian chờ tối đa
                    // 2. Số lượng tags đã xử lý ít nhất là min_tags_per_sync
                    // 3. Chưa phải trang cuối cùng
                    if (timeSinceLastUpdate > maxIdleTimeSeconds && 
                        currentProcessedTags >= minTagsPerSync && 
                        response.summary.current_page < response.summary.total_pages) {
                        
                        // Tự động tiếp tục sang trang tiếp theo
                        var nextPage = response.summary.current_page + 1;
                        
                        addSyncLogMessage('Tự động tiếp tục sang trang ' + nextPage + ' sau ' + 
                                         timeSinceLastUpdate + ' giây không hoạt động', 'info');
                        
                        console.log('Auto-continuing to next page:', nextPage);
                        
                        // Tạo URL để tiếp tục đồng bộ
                        var nextUrl = admin_url + 'topics/controllers/sync_tags/' + controllerId + 
                                     '?page=' + nextPage + '&auto_continue=1&_=' + new Date().getTime();
                        
                        // Gọi API để tiếp tục đồng bộ
                        processSyncRequest(nextUrl, controllerId);
                    }
                }
            } else {
                console.error('Error response in polling:', response);
                // Nếu không tìm thấy phiên đồng bộ, có thể đã bị xóa
                if (response.message && response.message.indexOf('không tìm thấy') !== -1) {
                    stopSyncLogPolling();
                    localStorage.removeItem('tags_sync_session_' + controllerId);
                    localStorage.removeItem('tags_sync_status_' + controllerId);
                    alert_float('warning', 'Phiên đồng bộ không còn tồn tại');
                }
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error in polling:', status, error);
            console.log('Response Text:', xhr.responseText);
            
            // Nếu gặp lỗi trong quá trình polling, thử lại vài lần trước khi dừng
            var errorCount = parseInt(localStorage.getItem('tags_sync_error_count_' + controllerId) || '0');
            errorCount++;
            
            if (errorCount > 5) {
                // Nếu lỗi quá nhiều, dừng polling
                stopSyncLogPolling();
                alert_float('danger', 'Đã dừng theo dõi tiến trình đồng bộ do lỗi kết nối');
                localStorage.removeItem('tags_sync_error_count_' + controllerId);
            } else {
                localStorage.setItem('tags_sync_error_count_' + controllerId, errorCount);
            }
        }
    });
}

// Biến toàn cục để theo dõi số lượng tags đã xử lý trong lần kiểm tra trước
var lastProcessedCount = 0;

// Hàm để dừng polling
function stopSyncLogPolling() {
    if (syncPollingInterval) {
        clearInterval(syncPollingInterval);
        syncPollingInterval = null;
    }
}

// Hàm để cập nhật giao diện với dữ liệu nhật ký đồng bộ mới
function updateSyncLogUI(summary, logs) {
    // Tính toán % tiến trình
    var progressPercent = 0;
    
    if (summary.current_page && summary.total_pages && summary.total_pages > 0) {
        progressPercent = Math.min(Math.round((summary.current_page / summary.total_pages) * 100), 100);
    } else if (summary.status === 'completed' || summary.status === 'cancelled' || summary.status === 'error') {
        progressPercent = 100;
    }
    
    $('#tags_loading_progress').css('width', progressPercent + '%');
    
    // Cập nhật thông tin trạng thái
    var statusText = '';
    var statusIcon = '';
    
    if (summary.status === 'in_progress') {
        statusText = '<?php echo _l('tags_sync_in_progress', 'Đang đồng bộ tags'); ?>';
        if (summary.current_page && summary.total_pages) {
            statusText += ' - Trang ' + summary.current_page + '/' + summary.total_pages;
        }
        statusIcon = '<i class="fa fa-spinner fa-spin"></i> ';
    } else if (summary.status === 'pending') {
        statusText = 'Đang chờ tiếp tục đồng bộ - Trang ' + summary.current_page + '/' + summary.total_pages;
        statusIcon = '<i class="fa fa-clock-o text-warning"></i> ';
    } else if (summary.status === 'completed') {
        statusText = '<?php echo _l('tags_sync_complete', 'Đồng bộ tags hoàn tất'); ?>';
        statusIcon = '<i class="fa fa-check-circle text-success"></i> ';
    } else if (summary.status === 'cancelled') {
        statusText = 'Đồng bộ đã bị hủy';
        statusIcon = '<i class="fa fa-ban text-warning"></i> ';
    } else {
        statusText = 'Đồng bộ thất bại';
        statusIcon = '<i class="fa fa-times-circle text-danger"></i> ';
    }
    
    $('#tags_loading p').html(statusIcon + statusText);
    
    // Cập nhật thông tin tổng quan
    var summaryHtml = '<div class="row">';
    
    // Sử dụng các giá trị từ server
    var totalTagsCount = summary.total_tags || 0;
    var processedTagsCount = summary.processed_tags || 0;
    var successCount = summary.success_count || 0;
    var errorCount = summary.error_count || 0;
    
    // Đảm bảo successCount không lớn hơn totalTagsCount
    if (successCount > totalTagsCount && totalTagsCount > 0) {
        successCount = totalTagsCount;
    }
    
    // Tính phần trăm chỉ dựa trên dữ liệu từ server, không tính toán lại
    var processedPercent = 0;
    if (totalTagsCount > 0) {
        processedPercent = Math.min(Math.round((processedTagsCount / totalTagsCount) * 100), 100);
    }
    
    // Tạo các thẻ thông tin
    var infoItems = [
        { label: 'Tổng số tags', value: totalTagsCount, icon: 'fa-tags' },
        { label: 'Đã xử lý', value: processedTagsCount, icon: 'fa-check-square-o', 
          percent: processedPercent },
        { label: 'Thành công', value: successCount, icon: 'fa-check', color: 'text-success' },
        { label: 'Lỗi', value: errorCount, icon: 'fa-exclamation-triangle', 
          color: errorCount > 0 ? 'text-danger' : 'text-muted' }
    ];
    
    infoItems.forEach(function(item) {
        summaryHtml += '<div class="col-md-3 col-sm-6">';
        summaryHtml += '<div class="sync-stat" style="padding: 5px; margin-bottom: 10px;">';
        summaryHtml += '<div><i class="fa ' + item.icon + ' ' + (item.color || '') + '"></i> <strong>' + item.label + ':</strong></div>';
        summaryHtml += '<div class="stat-value" style="font-size: 18px; font-weight: bold;">' + item.value;
        
        if (item.percent !== undefined) {
            summaryHtml += ' <small>(' + item.percent + '%)</small>';
        }
        
        summaryHtml += '</div>';
        summaryHtml += '</div>';
        summaryHtml += '</div>';
    });
    
    summaryHtml += '</div>';
    
    // Thêm thông tin trang và mốc thời gian
    summaryHtml += '<div class="row">';
    summaryHtml += '<div class="col-md-12">';
    summaryHtml += '<div class="sync-details" style="margin-top: 5px; padding: 8px; background-color: #f9f9f9; border-radius: 4px;">';
    summaryHtml += '<div><strong>Trang hiện tại:</strong> ' + summary.current_page + '/' + summary.total_pages + 
                   ' <small>(~' + (summary.per_page || 20) + ' tags/trang)</small></div>';
    summaryHtml += '</div>';
    summaryHtml += '</div>';
    summaryHtml += '</div>';
    
    // Cập nhật thời gian
    summaryHtml += '<div class="sync-times" style="margin-top: 5px; font-size: 12px; color: #666;">';
    summaryHtml += '<div><strong>Bắt đầu:</strong> ' + (summary.start_time || 'N/A') + '</div>';
    
    if (summary.status !== 'in_progress') {
        summaryHtml += '<div><strong>Kết thúc:</strong> ' + (summary.end_time || 'N/A') + '</div>';
    }
    
    summaryHtml += '<div><strong>Cập nhật lần cuối:</strong> ' + (summary.last_update || 'N/A') + '</div>';
    summaryHtml += '</div>';
    
    // Cập nhật phần tổng quan
    $('.sync-summary').html(summaryHtml);
    
    // Cập nhật nhật ký chi tiết
    var logsHtml = '<h5 style="position: sticky; top: 0; background-color: #f8f8f9; padding: 5px 0; margin-top: 0; border-bottom: 1px solid #ddd; z-index: 90;">Nhật ký đồng bộ:</h5>';
    logsHtml += '<div class="sync-logs-container" style="max-height: 200px; overflow-y: auto; padding: 5px; border: 1px solid #eee; background-color: #fff;">';
    
    if (logs && logs.length > 0) {
        // Hiển thị nhật ký theo thứ tự thời gian (cũ nhất lên đầu)
        for (var i = 0; i < logs.length; i++) {
            var log = logs[i];
            var logClass = 'text-muted';
            var logIcon = 'fa-info-circle';
            
            if (log.type === 'error') {
                logClass = 'text-danger';
                logIcon = 'fa-times-circle';
            } else if (log.type === 'success') {
                logClass = 'text-success';
                logIcon = 'fa-check-circle';
            } else if (log.type === 'warning') {
                logClass = 'text-warning';
                logIcon = 'fa-exclamation-triangle';
            }
            
            logsHtml += '<div class="' + logClass + '" style="margin-bottom: 5px; padding-bottom: 5px; border-bottom: 1px dotted #eee;">';
            logsHtml += '<i class="fa ' + logIcon + '"></i> ';
            logsHtml += '<small>[' + log.timestamp + ']</small> ' + log.message;
            
            // Nếu có chi tiết và là lỗi, hiển thị thêm thông tin
            if (log.details && log.type === 'error') {
                logsHtml += '<a href="#" class="toggle-details" style="margin-left: 10px; font-size: 11px;">Chi tiết</a>';
                logsHtml += '<div class="log-details" style="display: none; margin-top: 5px; padding: 5px; background: #f9f9f9; border-left: 3px solid #ddd; font-size: 11px;">';
                
                if (typeof log.details === 'object') {
                    for (var key in log.details) {
                        if (log.details.hasOwnProperty(key)) {
                            var detailValue = log.details[key];
                            // Chuyển đổi đối tượng thành chuỗi nếu cần
                            if (typeof detailValue === 'object') {
                                detailValue = JSON.stringify(detailValue);
                            }
                            logsHtml += '<div><strong>' + key + ':</strong> ' + detailValue + '</div>';
                        }
                    }
                } else {
                    logsHtml += log.details;
                }
                
                logsHtml += '</div>';
            }
            
            logsHtml += '</div>';
        }
    } else {
        logsHtml += '<div class="text-center">Chưa có nhật ký nào</div>';
    }
    
    logsHtml += '</div>';
    
    // Cập nhật phần nhật ký
    $('.sync-logs').html(logsHtml);
    
    // Tự động cuộn xuống để hiển thị logs mới nhất
    var logsWrapper = $('.sync-logs-wrapper');
    if (logsWrapper.length) {
        logsWrapper.scrollTop(logsWrapper[0].scrollHeight);
    }
    
    // Xử lý sự kiện hiển thị/ẩn chi tiết
    $('.toggle-details').on('click', function(e) {
        e.preventDefault();
        $(this).next('.log-details').toggle();
        var text = $(this).next('.log-details').is(':visible') ? 'Ẩn chi tiết' : 'Chi tiết';
        $(this).text(text);
    });
}

function waitForJQuery(callback) {
    if (typeof jQuery !== 'undefined') {
        callback();
    } else {
        setTimeout(function() {
            waitForJQuery(callback);
        }, 100);
    }
}

/**
 * Load sync sessions for a controller
 * @param {number} controllerId - ID of the controller
 */
function loadSyncSessions(controllerId) {
    $('#sync_sessions_list').hide();
    $('#sync_sessions_empty').hide();
    $('#sync_sessions_loading').show();
    
    $.ajax({
        url: admin_url + 'topics/controllers/get_tags_sync_logs/' + controllerId,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            $('#sync_sessions_loading').hide();
            
            if (response.success && response.data && response.data.length > 0) {
                renderSyncSessionsList(controllerId, response.data);
                $('#sync_sessions_list').show();
                
                // Hiển thị container nếu có phiên đang chạy
                var hasInProgressSession = false;
                var activeSessionsCount = 0;
                
                for (var i = 0; i < response.data.length; i++) {
                    if (response.data[i].status === 'in_progress') {
                        hasInProgressSession = true;
                        activeSessionsCount++;
                    }
                }
                
                // Cập nhật badge hiển thị số phiên đang chạy
                if (activeSessionsCount > 0) {
                    $('.active-sessions-badge').text(activeSessionsCount).show();
                    
                    // Tự động hiển thị danh sách phiên nếu có phiên đang chạy
                    $('#sync_sessions_container').show();
                } else {
                    $('.active-sessions-badge').hide();
                }
            } else {
                $('#sync_sessions_empty').show();
                $('.active-sessions-badge').hide();
            }
        },
        error: function() {
            $('#sync_sessions_loading').hide();
            $('#sync_sessions_empty').show();
            alert_float('danger', 'Lỗi khi tải danh sách phiên đồng bộ');
        }
    });
}

/**
 * Render sync sessions list
 * @param {number} controllerId - ID of the controller
 * @param {Array} sessions - List of sync sessions
 */
function renderSyncSessionsList(controllerId, sessions) {
    var html = '';
    
    sessions.forEach(function(session) {
        var statusClass = '';
        var statusText = '';
        
        if (session.status === 'in_progress') {
            statusClass = 'label-info';
            statusText = 'Đang chạy';
        } else if (session.status === 'pending') {
            statusClass = 'label-warning';
            statusText = 'Đang chờ';
        } else if (session.status === 'completed') {
            statusClass = 'label-success';
            statusText = 'Hoàn thành';
        } else if (session.status === 'cancelled') {
            statusClass = 'label-warning';
            statusText = 'Đã hủy';
        } else {
            statusClass = 'label-danger';
            statusText = 'Lỗi';
        }
        
        // Tính toán tiến trình
        var progressPercent = 0;
        if (session.total_pages > 0) {
            progressPercent = Math.min(Math.round((session.current_page / session.total_pages) * 100), 100);
        } else if (session.status !== 'in_progress' && session.status !== 'pending') {
            progressPercent = 100;
        }
        
        // Tạo các nút chức năng phù hợp với trạng thái phiên
        var actionButtons = '';
        
        // Nút xem chi tiết (cho tất cả các phiên)
        actionButtons += '<button class="btn btn-xs btn-info btn-view-session" data-session-id="' + session.session_id + '">' +
                         '<i class="fa fa-search"></i> Chi tiết</button> ';
        
        // Nút hủy (chỉ cho phiên đang chạy hoặc đang chờ)
        if (session.status === 'in_progress' || session.status === 'pending') {
            actionButtons += '<button class="btn btn-xs btn-danger btn-cancel-session" data-session-id="' + session.session_id + '">' +
                             '<i class="fa fa-times"></i> Hủy</button> ';
        }
        
        // Nút tiếp tục theo dõi (cho phiên đang chạy)
        if (session.status === 'in_progress') {
            actionButtons += '<button class="btn btn-xs btn-primary btn-monitor-session" data-session-id="' + session.session_id + '">' +
                             '<i class="fa fa-eye"></i> Theo dõi</button>';
        }
        
        // Nút tiếp tục đồng bộ (cho phiên đang chờ)
        if (session.status === 'pending') {
            actionButtons += '<button class="btn btn-xs btn-success btn-resume-session" data-session-id="' + session.session_id + '" ' +
                             'data-current-page="' + (session.current_page || 1) + '" data-total-pages="' + (session.total_pages || 1) + '">' +
                             '<i class="fa fa-play"></i> Tiếp tục</button>';
        }
        
        // Nút resume (cho phiên đã hủy hoặc bị lỗi)
        if (session.status === 'cancelled' || session.status === 'failed') {
            actionButtons += '<button class="btn btn-xs btn-success btn-resume-session" data-session-id="' + session.session_id + '" ' +
                             'data-current-page="' + (session.current_page || 1) + '" data-total-pages="' + (session.total_pages || 1) + '">' +
                             '<i class="fa fa-play"></i> Tiếp tục</button>';
        }
        
        html += '<tr>';
        html += '<td><code>' + session.session_id + '</code></td>';
        html += '<td><span class="label ' + statusClass + '">' + statusText + '</span></td>';
        html += '<td>';
        html += '<div class="progress" style="margin-bottom:0;">';
        html += '<div class="progress-bar progress-bar-striped' + ((session.status === 'in_progress' || session.status === 'pending') ? ' active' : '') + '" ' +
                'role="progressbar" aria-valuenow="' + progressPercent + '" aria-valuemin="0" aria-valuemax="100" ' +
                'style="width:' + progressPercent + '%">' + progressPercent + '%</div>';
        html += '</div>';
        
        // Thêm thông tin số items đã xử lý
        html += '<small>' + (session.processed_tags || session.items_processed || 0) + '/' + 
                (session.total_tags || 0) + ' tags</small>';
        html += '</td>';
        html += '<td>' + (session.start_time || '-') + '</td>';
        html += '<td>' + (session.end_time || '-') + '</td>';
        html += '<td>' + actionButtons + '</td>';
        html += '</tr>';
    });
    
    $('#sync_sessions_list tbody').html(html);
    
    // Thêm các event handler cho các nút
    $('.btn-view-session').on('click', function() {
        var sessionId = $(this).data('session-id');
        viewSessionDetails(controllerId, sessionId);
    });
    
    $('.btn-cancel-session').on('click', function() {
        var sessionId = $(this).data('session-id');
        if (confirm('Bạn có chắc chắn muốn hủy phiên đồng bộ này?')) {
            cancelSyncSession(controllerId, sessionId);
        }
    });
    
    $('.btn-monitor-session').on('click', function() {
        var sessionId = $(this).data('session-id');
        $('#sync_sessions_container').hide(); // Ẩn danh sách phiên
        startSyncLogPolling(controllerId, sessionId); // Bắt đầu theo dõi phiên
    });
    
    // Thêm event handler cho nút resume
    $('.btn-resume-session').on('click', function() {
        var sessionId = $(this).data('session-id');
        var currentPage = $(this).data('current-page');
        var totalPages = $(this).data('total-pages');
        
        if (confirm('Bạn có chắc chắn muốn tiếp tục phiên đồng bộ này từ trang ' + currentPage + '?')) {
            resumeSyncSession(controllerId, sessionId, currentPage, totalPages);
        }
    });
}

/**
 * Tiếp tục phiên đồng bộ
 * @param {string} controllerId - ID của controller
 * @param {string} sessionId - ID của phiên đồng bộ
 * @param {number} currentPage - Trang hiện tại
 * @param {number} totalPages - Tổng số trang
 */
function resumeSyncSession(controllerId, sessionId, currentPage, totalPages) {
    // Hiển thị loading UI
    $('#sync_sessions_container').hide();
    $('#tags_list').hide();
    $('#tags_empty').hide();
    $('#tags_loading').show();
    $('#tags_loading_progress').css('width', '0%');
    
    // Sử dụng trang hiện tại làm trang tiếp theo (sẽ xác định chính xác sau khi kiểm tra phiên)
    var nextPage = currentPage;
    
    // Lưu thông tin phiên đồng bộ vào localStorage để có thể theo dõi sau khi refresh
    localStorage.setItem('tags_sync_session_' + controllerId, sessionId);
    localStorage.setItem('tags_sync_status_' + controllerId, 'resuming');
    
    // Thêm thông báo đang resume
    var resumeHtml = '<div id="tags_resume_message" class="alert alert-info mtop10">' +
                     '<i class="fa fa-refresh fa-spin"></i> Đang tiếp tục phiên đồng bộ từ trang ' + nextPage + (totalPages ? '/' + totalPages : '') + '...' +
                     '</div>';
    $('#tags_loading').after(resumeHtml);
    
    // Đảm bảo chi tiết đồng bộ được hiển thị
    if (!$('#tags_sync_details').length) {
        var detailsHtml = '<div id="tags_sync_details" class="mtop10 small text-left" style="max-height: 500px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; border-radius: 4px; background-color: #f8f8f9;"></div>';
        $('#tags_loading').append(detailsHtml);
    }
    
    // Thêm thông tin về phiên đồng bộ
    $('#tags_sync_details').html(
        '<div class="sync-header" style="position: sticky; top: 0; background-color: #f8f8f9; padding-bottom: 10px; border-bottom: 1px solid #ddd; z-index: 100;">' +
        '<h5><i class="fa fa-sync"></i> Thông tin phiên đồng bộ</h5>' +
        '<div class="sync-session-id">ID phiên: ' + sessionId + '</div>' +
        '<div class="sync-page">Trang: ' + nextPage + (totalPages ? '/' + totalPages : '') + '</div>' +
        '<div class="sync-controls mtop10"><button class="btn btn-xs btn-danger cancel-sync-btn"><i class="fa fa-times"></i> Hủy đồng bộ</button></div>' +
        '</div>' +
        '<div class="sync-summary mtop10" style="position: sticky; top: 120px; background-color: #f8f8f9; padding: 10px 0; border-bottom: 1px solid #ddd; z-index: 99;"></div>' +
        '<div class="sync-logs-wrapper" style="height: 200px; overflow-y: auto; margin-top: 15px;">' +
        '<div class="sync-logs"></div>' +
        '</div>'
    );
    
    // Thêm thông báo bắt đầu resume
    addSyncLogMessage('Đang chuẩn bị tiếp tục phiên đồng bộ...', 'info');
    
    // Kiểm tra trạng thái phiên hiện tại
    $.ajax({
        url: admin_url + 'topics/controllers/get_sync_session_details/' + controllerId,
        type: 'GET',
        data: {
            session_id: sessionId
        },
        dataType: 'json',
        success: function(checkResponse) {
            if (!checkResponse || !checkResponse.success) {
                var errorMessage = checkResponse && checkResponse.message ? checkResponse.message : 'Không thể lấy thông tin phiên đồng bộ';
                addSyncLogMessage('Lỗi kiểm tra phiên: ' + errorMessage, 'error');
                alert_float('danger', errorMessage);
                
                // Thực hiện các thao tác của showTagsList trực tiếp
                $('#tags_loading').hide();
                $('#tags_resume_message').remove();
                currentSyncSessionId = null;
                if (syncPollingInterval) {
                    clearInterval(syncPollingInterval);
                    syncPollingInterval = null;
                }
                if ($('#tags_datatable').length && $('#tags_datatable').find('tbody tr').length > 0) {
                    $('#tags_list').show();
                } else {
                    $('#tags_empty').show();
                }
                $('#get_tags_api').prop('disabled', false).html('<i class="fa fa-refresh"></i> <?php echo _l('get_from_api', 'Get from API'); ?>');
                return;
            }
            
            // Lấy thông tin phiên
            var sessionDetails = checkResponse.summary || {};
            
            // Xác định trang để tiếp tục
            var pageToResume = currentPage || sessionDetails.current_page || 1;
            var totalPagesToSync = totalPages || sessionDetails.total_pages || 1;
            
            // Nếu phiên đã hoàn thành, không tiếp tục
            if (sessionDetails.status === 'completed') {
                addSyncLogMessage('Phiên đồng bộ này đã hoàn thành. Không cần tiếp tục.', 'warning');
                alert_float('warning', 'Phiên đồng bộ này đã hoàn thành.');
                
                // Thực hiện các thao tác của showTagsList trực tiếp
                $('#tags_loading').hide();
                $('#tags_resume_message').remove();
                currentSyncSessionId = null;
                if (syncPollingInterval) {
                    clearInterval(syncPollingInterval);
                    syncPollingInterval = null;
                }
                if ($('#tags_datatable').length && $('#tags_datatable').find('tbody tr').length > 0) {
                    $('#tags_list').show();
                } else {
                    $('#tags_empty').show();
                }
                $('#get_tags_api').prop('disabled', false).html('<i class="fa fa-refresh"></i> <?php echo _l('get_from_api', 'Get from API'); ?>');
                return;
            }
            // Phiên đã bị hủy, nhưng chúng ta cho phép tiếp tục
            else if (sessionDetails.status === 'cancelled') {
                var cancelWarning = $('<div class="alert alert-warning mt-2" style="display:none;">Phiên đồng bộ này đã bị hủy trước đó. Đang khôi phục lại...</div>');
                $('#tags_sync_logs').prepend(cancelWarning);
                cancelWarning.fadeIn('fast').effect('pulsate', { times: 3 }, 2000);
                
                addSyncLogMessage('Phiên đồng bộ này đã bị hủy trước đó. Đang khôi phục lại...', 'warning');
                // Tiếp tục với phiên bị hủy
            }
            
            // Cập nhật trạng thái phiên thành đang chạy nếu hiện tại là pending hoặc cancelled
            if (sessionDetails.status === 'pending' || sessionDetails.status === 'cancelled') {
                addSyncLogMessage('Phiên đang ở trạng thái "' + sessionDetails.status + '", chuyển sang "in_progress"', 'info');
                updateSyncSession(controllerId, sessionId, {
                    status: 'in_progress'
                }, function() {
                    // Tiếp tục sau khi cập nhật trạng thái
                    continueWithResume(controllerId, sessionId, pageToResume, totalPagesToSync);
                });
            } else {
                // Tiếp tục ngay nếu không phải pending
                continueWithResume(controllerId, sessionId, pageToResume, totalPagesToSync);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error checking session status:', error);
            addSyncLogMessage('Lỗi kết nối khi kiểm tra trạng thái phiên: ' + error, 'error');
            alert_float('danger', 'Không thể kiểm tra trạng thái phiên đồng bộ');
            
            // Thay thế việc gọi showTagsList() với xử lý lỗi trực tiếp
            $('#tags_loading').hide();
            $('#tags_resume_message').remove();
            currentSyncSessionId = null;
            
            // Dừng polling nếu đang chạy
            if (syncPollingInterval) {
                clearInterval(syncPollingInterval);
                syncPollingInterval = null;
            }
            
            // Hiển thị danh sách tags nếu có, ngược lại hiển thị thông báo rỗng
            if ($('#tags_datatable').length && $('#tags_datatable').find('tbody tr').length > 0) {
                $('#tags_list').show();
            } else {
                $('#tags_empty').show();
            }
            
            // Reset nút đồng bộ
            $('#get_tags_api').prop('disabled', false).html('<i class="fa fa-refresh"></i> <?php echo _l('get_from_api', 'Get from API'); ?>');
        }
    });
    
    // Hàm tiếp tục quá trình resume sau khi kiểm tra trạng thái
    function continueWithResume(controllerId, sessionId, nextPage, totalPages) {
        // Bắt đầu polling để theo dõi tiến trình
        startSyncLogPolling(controllerId, sessionId);
    
    // Gọi API để resume phiên đồng bộ
    $.ajax({
        url: admin_url + 'topics/controllers/resume_tags_sync/' + controllerId,
        type: 'POST',
        data: {
            session_id: sessionId,
            page: nextPage
        },
        dataType: 'json',
        success: function(response) {
                console.log('Resume response:', response);
            
            if (response.success) {
                    addSyncLogMessage('Đã tiếp tục phiên đồng bộ thành công', 'success');
                
                // Lưu session ID mới (nếu có) vào localStorage
                if (response.session_id) {
                    localStorage.setItem('tags_sync_session_' + controllerId, response.session_id);
                    currentSyncSessionId = response.session_id;
                } else {
                    currentSyncSessionId = sessionId;
                }
                
                // Thông báo resume thành công
                alert_float('success', 'Đã tiếp tục phiên đồng bộ thành công. Đang đồng bộ từ trang ' + nextPage);
                
                // Chuyển hướng nếu có URL chuyển hướng trong response
                if (response.redirect_url) {
                        addSyncLogMessage('Đang gửi yêu cầu đồng bộ đến: ' + response.redirect_url, 'info');
                    
                    // Thêm một tham số ngẫu nhiên để tránh cache
                    var redirectUrl = response.redirect_url + '&_=' + new Date().getTime();
                    console.log('Sync URL: ' + redirectUrl);
                    
                        // Tạo URL đồng bộ với sessionId
                        var syncUrl = redirectUrl;
                        if (syncUrl.indexOf('session_id=') === -1) {
                            syncUrl += '&session_id=' + currentSyncSessionId;
                        }
                        
                        // Sử dụng performSync để đồng bộ với URL đã được xây dựng
                        performSync(controllerId, syncUrl, nextPage, currentSyncSessionId, totalPages);
                            } else {
                        // Nếu không có URL chuyển hướng, tạo URL đồng bộ mặc định
                        var defaultSyncUrl = admin_url + 'topics/controllers/sync_tags/' + controllerId + 
                                           '?page=' + nextPage + '&session_id=' + currentSyncSessionId + 
                                           '&_=' + new Date().getTime();
                        
                        addSyncLogMessage('Không có URL chuyển hướng, sử dụng URL mặc định: ' + defaultSyncUrl, 'info');
                        
                        // Thực hiện đồng bộ với URL mặc định
                        performSync(controllerId, defaultSyncUrl, nextPage, currentSyncSessionId, totalPages);
                }
            } else {
                    // Xử lý lỗi resume
                    addSyncLogMessage('Lỗi khi tiếp tục phiên đồng bộ: ' + (response.message || 'Không xác định'), 'error');
                    alert_float('danger', 'Không thể tiếp tục phiên đồng bộ: ' + (response.message || 'Lỗi không xác định'));
                    
                    // Thực hiện các thao tác của showTagsList trực tiếp
                    $('#tags_loading').hide();
                    $('#tags_resume_message').remove();
                    currentSyncSessionId = null;
                    
                    // Dừng polling nếu đang chạy
                    if (syncPollingInterval) {
                        clearInterval(syncPollingInterval);
                        syncPollingInterval = null;
                    }
                    
                    // Hiển thị danh sách tags nếu có, ngược lại hiển thị thông báo rỗng
                    if ($('#tags_datatable').length && $('#tags_datatable').find('tbody tr').length > 0) {
                        $('#tags_list').show();
                    } else {
                        $('#tags_empty').show();
                    }
                    
                    // Reset nút đồng bộ
                    $('#get_tags_api').prop('disabled', false).html('<i class="fa fa-refresh"></i> <?php echo _l('get_from_api', 'Get from API'); ?>');
            }
        },
        error: function(xhr, status, error) {
            console.error('Resume AJAX Error:', status, error);
            
            // Xử lý lỗi resume
            addSyncLogMessage('Lỗi AJAX khi tiếp tục phiên đồng bộ: ' + error, 'error');
            alert_float('danger', 'Lỗi khi tiếp tục phiên đồng bộ');
            
            // Thực hiện các thao tác của showTagsList trực tiếp
            $('#tags_loading').hide();
            $('#tags_resume_message').remove();
            currentSyncSessionId = null;
            
            // Dừng polling nếu đang chạy
            if (syncPollingInterval) {
                clearInterval(syncPollingInterval);
                syncPollingInterval = null;
            }
            
            // Hiển thị danh sách tags nếu có, ngược lại hiển thị thông báo rỗng
            if ($('#tags_datatable').length && $('#tags_datatable').find('tbody tr').length > 0) {
                $('#tags_list').show();
            } else {
                $('#tags_empty').show();
            }
            
            // Reset nút đồng bộ
            $('#get_tags_api').prop('disabled', false).html('<i class="fa fa-refresh"></i> <?php echo _l('get_from_api', 'Get from API'); ?>');
        }
        });
    }
}

/**
 * Xem chi tiết phiên đồng bộ
 * @param {number} controllerId - ID của controller
 * @param {string} sessionId - ID của phiên đồng bộ
 */
function viewSessionDetails(controllerId, sessionId) {
    $.ajax({
        url: admin_url + 'topics/controllers/get_sync_session_details/' + controllerId,
        data: {
            session_id: sessionId
        },
        dataType: 'json',
        success: function(response) {
            // Tạo modal nếu chưa tồn tại
            var modalId = 'sync_session_details_modal';
            var $modal = $('#' + modalId);
            
            if (!$modal.length) {
                $modal = $('<div class="modal fade" id="' + modalId + '" tabindex="-1" role="dialog" aria-labelledby="' + modalId + 'Label" aria-hidden="true">' +
                    '<div class="modal-dialog modal-lg">' +
                    '<div class="modal-content">' +
                    '<div class="modal-header">' +
                    '<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>' +
                    '<h4 class="modal-title" id="' + modalId + 'Label">Chi tiết phiên đồng bộ</h4>' +
                    '</div>' +
                    '<div class="modal-body"></div>' +
                    '<div class="modal-footer">' +
                    '<button type="button" class="btn btn-default" data-dismiss="modal">Đóng</button>' +
                    '<button type="button" class="btn btn-info resume-session" data-controller-id="" data-session-id="">Tiếp tục phiên</button>' +
                    '</div>' +
                    '</div>' +
                    '</div>' +
                    '</div>');
                $('body').append($modal);
            }
            
            var sessionDetails = response.session;
            var statusClass = {
                'pending': 'info',
                'in_progress': 'primary',
                'completed': 'success',
                'failed': 'danger',
                'cancelled': 'warning'
            };
            
            // Format thông tin phiên
            var sessionInfo = '<div class="row">' +
                '<div class="col-md-6">' +
                '<strong>ID phiên:</strong> ' + sessionDetails.session_id + '<br>' +
                '<strong>Trạng thái:</strong> <span class="label label-' + (statusClass[sessionDetails.status] || 'default') + '">' + 
                  (sessionDetails.status === 'cancelled' ? 'Đã hủy (có thể tiếp tục)' : sessionDetails.status) + '</span><br>' +
                '<strong>Thời gian bắt đầu:</strong> ' + (sessionDetails.start_time || 'N/A') + '<br>' +
                '<strong>Thời gian cập nhật cuối:</strong> ' + (sessionDetails.last_update || 'N/A') + '<br>' +
                '<strong>Thời gian kết thúc:</strong> ' + (sessionDetails.end_time || 'N/A') + '<br>' +
                '</div>' +
                '<div class="col-md-6">' +
                '<strong>Tổng số tags:</strong> ' + (sessionDetails.total_tags || 0) + '<br>' +
                '<strong>Số tags đã xử lý:</strong> ' + (sessionDetails.processed_tags || 0) + '<br>' +
                '<strong>Trang hiện tại:</strong> ' + (sessionDetails.current_page || 1) + '/' + (sessionDetails.total_pages || 1) + '<br>' +
                '<strong>Thành công:</strong> ' + (sessionDetails.success_count || 0) + '<br>' +
                '<strong>Lỗi:</strong> ' + (sessionDetails.error_count || 0) + '<br>' +
                '</div>' +
                '</div>';
                
            // Thêm thông báo nếu phiên đã bị hủy
            if (sessionDetails.status === 'cancelled') {
                sessionInfo += '<div class="alert alert-warning mt-3">' +
                    '<i class="fa fa-info-circle"></i> Phiên này đã bị hủy trước đó, nhưng bạn có thể tiếp tục bằng cách nhấn nút "Tiếp tục phiên".' +
                    '</div>';
            }
            
            // Hiển thị nhật ký nếu có
            if (response.logs && response.logs.length > 0) {
                sessionInfo += '<div class="panel panel-default mt-3">' +
                    '<div class="panel-heading">Nhật ký đồng bộ</div>' +
                    '<div class="panel-body" style="max-height: 300px; overflow-y: auto;">';
                
                // Hiển thị logs theo thứ tự từ mới đến cũ
                for (var i = response.logs.length - 1; i >= 0; i--) {
                    var log = response.logs[i];
                    var logTypeClass = {
                        'info': 'text-info',
                        'success': 'text-success',
                        'warning': 'text-warning',
                        'error': 'text-danger'
                    };
                    sessionInfo += '<div class="' + (logTypeClass[log.type] || '') + '">';
                    sessionInfo += '<small>[' + log.timestamp + ']</small> ' + log.message;
                    sessionInfo += '</div>';
                }
                
                sessionInfo += '</div></div>';
            }
            
            // Hiển thị thông tin phiên đồng bộ
            $modal.find('.modal-body').html(sessionInfo);
            
            // Cập nhật trạng thái của nút "Tiếp tục phiên"
            var $resumeButton = $modal.find('.resume-session');
            $resumeButton.data('controller-id', controllerId).data('session-id', sessionId);
            
            // Hiển thị/ẩn nút tiếp tục phiên dựa vào trạng thái
            if (sessionDetails.status === 'completed') {
                $resumeButton.hide(); // Ẩn nút nếu phiên đã hoàn thành
            } else {
                $resumeButton.show(); // Hiển thị nút nếu có thể tiếp tục
                
                // Thêm sự kiện click cho nút tiếp tục phiên
                $resumeButton.off('click').on('click', function() {
                    var cId = $(this).data('controller-id');
                    var sId = $(this).data('session-id');
                    
                    // Đóng modal
                    $modal.modal('hide');
                    
                    // Tiếp tục phiên đồng bộ
                    resumeSyncSession(cId, sId, sessionDetails.current_page, sessionDetails.total_pages);
                });
            }
            
            // Hiển thị modal
            $modal.modal('show');
            
            // Làm mới danh sách phiên khi đóng modal
            $modal.off('hidden.bs.modal').on('hidden.bs.modal', function() {
                refreshSyncSessionsList(controllerId);
            });
        },
        error: function() {
            alert_float('danger', 'Lỗi khi tải chi tiết phiên đồng bộ');
        }
    });
}

/**
 * Làm mới danh sách phiên đồng bộ
 * @param {number} controllerId - ID của controller
 */
function refreshSyncSessionsList(controllerId) {
    // Tải lại danh sách phiên đồng bộ
    loadSyncSessions(controllerId);
}

// Sử dụng hàm waitForJQuery
waitForJQuery(function() {
    // Initialize when tab is shown
    $(document).ready(function() {
        // Log admin_url for debugging
        console.log('Current admin_url: ' + admin_url);
        
        // Kiểm tra xem có phiên đồng bộ cũ chưa hoàn thành không
        checkExistingSyncSession(controllerId);
        
        // Load tags when tab is shown
        $('a[href="#tags"]').on('shown.bs.tab', function (e) {
            loadTags(controllerId);
            loadSyncSessions(controllerId);
        });
        
        // Get tags from database button
        $('#get_tags_db').on('click', function() {
            loadTags(controllerId);
        });
        
        // Get tags from API button
        $('#get_tags_api').on('click', function() {
            syncTags(controllerId);
        });
        
        // Show sync sessions button
        $('#show_sync_sessions').on('click', function() {
            $('#sync_sessions_container').toggle();
            if ($('#sync_sessions_container').is(':visible')) {
                loadSyncSessions(controllerId);
            }
        });
        
        // Load sync sessions ngay khi trang được tải
        loadSyncSessions(controllerId);
        
        // Debug info
        console.log('Controller ID:', controllerId);
        console.log('Sync tags endpoint:', admin_url + 'topics/controllers/sync_tags/' + controllerId);
        console.log('Get tags endpoint:', admin_url + 'topics/controllers/get_tags/' + controllerId);
        console.log('Get logs endpoint:', admin_url + 'topics/controllers/get_tags_sync_logs/' + controllerId);
    });
})

// Hàm để xử lý yêu cầu đồng bộ và tự động tiếp tục qua các trang
function processSyncRequest(url, controllerId) {
            $.ajax({
        url: url,
                type: 'GET',
                dataType: 'json',
        success: function(syncResponse) {
            console.log('Sync response:', syncResponse);
            addSyncLogMessage('Nhận được phản hồi từ sync_tags: ' + JSON.stringify(syncResponse), 'success');
            
            // Cập nhật UI dựa trên kết quả đồng bộ
            if (syncResponse.success) {
                // Cập nhật thanh tiến trình
                var progress = Math.round((syncResponse.current_page / syncResponse.total_pages) * 100);
                $('#tags_loading_progress').css('width', progress + '%');
                
                // Cập nhật thông tin trang
                $('.sync-page').text('Trang: ' + syncResponse.current_page + '/' + syncResponse.total_pages);
                
                // Đảm bảo biến next_page luôn tồn tại
                var nextPage = syncResponse.next_page;
                
                // Nếu next_page không tồn tại nhưng vẫn chưa hoàn thành, tính toán next_page dựa trên current_page
                if (!nextPage && !syncResponse.is_completed) {
                    nextPage = syncResponse.current_page + 1;
                    console.log('nextPage không tồn tại, tính toán từ current_page:', nextPage);
                }
                
                // Nếu chưa hoàn thành và có trang tiếp theo, tiếp tục đồng bộ
                if (!syncResponse.is_completed && nextPage && nextPage <= syncResponse.total_pages) {
                    addSyncLogMessage('Tiếp tục đồng bộ trang ' + nextPage + '/' + syncResponse.total_pages, 'success');
                    
                    // Đợi 1 giây trước khi tiếp tục trang tiếp theo
                    setTimeout(function() {
                        var nextUrl = admin_url + 'topics/controllers/sync_tags/' + controllerId + '?page=' + nextPage + '&_=' + new Date().getTime();
                        processSyncRequest(nextUrl, controllerId);
                    }, 3000);
                } else if (syncResponse.is_completed) {
                    // Đồng bộ hoàn tất
                    addSyncLogMessage('Đồng bộ hoàn tất!', 'success');
                    $('#tags_loading_progress').css('width', '100%');
                    
                    setTimeout(function() {
                        $('#tags_loading').hide();
                        alert_float('success', 'Đồng bộ tags hoàn tất!');
                        
                        // Xóa thông tin phiên đồng bộ
                        localStorage.removeItem('tags_sync_session_' + controllerId);
                        localStorage.removeItem('tags_sync_status_' + controllerId);
                        
                        // Tải lại danh sách tags
                        loadTags(controllerId);
                    }, 3000);
                    } else {
                    // Trường hợp không có trang tiếp theo và không phải đã hoàn thành
                    addSyncLogMessage('Không thể xác định trang tiếp theo', 'warning');
                    $('#tags_loading').hide();
                    alert_float('warning', 'Quá trình đồng bộ có thể không hoàn chỉnh');
                    loadTags(controllerId);
                }
            } else {
                // Xử lý lỗi
                addSyncLogMessage('Lỗi khi đồng bộ: ' + (syncResponse.message || 'Không xác định'), 'error');
                $('#tags_loading').hide();
                alert_float('danger', 'Đã xảy ra lỗi trong quá trình đồng bộ: ' + (syncResponse.message || 'Lỗi không xác định'));
                    }
                },
                error: function(xhr, status, error) {
            console.error('Sync AJAX Error:', status, error);
            addSyncLogMessage('Lỗi AJAX khi đồng bộ: ' + error, 'error');
            $('#tags_loading').hide();
            alert_float('danger', 'Lỗi kết nối khi đồng bộ tags');
                }
            });
}

</script> 