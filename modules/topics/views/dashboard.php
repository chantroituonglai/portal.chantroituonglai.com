<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>

<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <!-- Quick Stats -->
                <?php //$this->load->view('includes/topic_states'); ?>

                <div class="panel_s">
                    <div class="panel-body">
                        <div class="tw-flex tw-justify-between tw-items-center">
                            <h4 class="no-margin"><?php echo _l('topics_dashboard'); ?></h4>
                            <div class="tw-flex tw-gap-3">
                                <button class="btn btn-default" id="refreshTable">
                                    <i class="fa fa-refresh"></i> <?php echo _l('refresh'); ?>
                                </button>
                                <button class="btn btn-default" id="clearFilters" style="display:none">
                                    <i class="fa fa-times"></i> <?php echo _l('clear_filters'); ?>
                                </button>
                                <button class="btn btn-primary" id="exportData">
                                    <i class="fa fa-download"></i> <?php echo _l('export'); ?>
                                </button>
                            </div>
                        </div>
                        <hr class="hr-panel-heading" />

                        <!-- Active Filters Section -->
                        <div class="active-filters mb-3" style="display:none">
                            <h5><?php echo _l('active_filters'); ?></h5>
                            <div class="active-filters-list tw-flex tw-flex-wrap tw-gap-2">
                                <!-- Active filters will be added here dynamically -->
                            </div>
                        </div>
                        
                        <?php
                        $table_data = [
                            '<input type="checkbox" id="select_all" class="check-all">',
                            _l('topic_id'),
                            _l('topic_title'),
                            _l('action_type'),
                            _l('action_state'),
                            _l('last_update'),
                            _l('options')
                        ];

                        render_datatable($table_data, 'dashboard-topics');
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bulk Action Modal -->
<div class="modal fade" id="bulk_action" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><?php echo _l('confirm_action'); ?></h4>
            </div>
            <div class="modal-body">
                <p><?php echo _l('bulk_action_confirmation'); ?></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
                <button type="button" class="btn btn-primary" id="confirm_bulk_action"><?php echo _l('confirm'); ?></button>
            </div>
        </div>
    </div>
</div>

<?php init_tail(); ?>

<script>
// Add this function before $(function() {...})
function updateBulkActionsState() {
    var checkedBoxes = $('.row-checkbox:checked').length;
    var bulkActionsBtn = $('.bulk-actions .dropdown-toggle');
    var selectedCount = $('.selected-count');

    if (checkedBoxes > 0) {
        bulkActionsBtn.prop('disabled', false);
        selectedCount.text('(' + checkedBoxes + ' ' + '<?php echo _l("selected_items"); ?>' + ')');
    } else {
        bulkActionsBtn.prop('disabled', true);
        selectedCount.text('');
    }
}

$(function() {
    // Store active filters
    var activeFilters = new Set();
    
    // Khởi tạo biến searchMode trước
    let searchMode = 'or';
    
    // Khởi tạo DataTable
    var topicsTable = $('.table-dashboard-topics').DataTable({
        "processing": true,
        "serverSide": true,
        "order": [[5, 'desc']],
        "pageLength": 25,
        "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "<?php echo _l('all'); ?>"]],
        "ajax": {
            "url": admin_url + 'topics/dashboard_table',
            "type": "POST",
            "data": function(d) {
                d.filters = Array.from(activeFilters);
                d.search_mode = searchMode;
                return d;
            },
            "beforeSend": function() {
                $('.table-dashboard-topics').addClass('dt-table-loading');
                $('.dataTables_processing').addClass('dt-loader');
            },
            "complete": function() {
                $('.table-dashboard-topics').removeClass('dt-table-loading');
                $('.dataTables_processing').removeClass('dt-loader');
                $('.dataTables_wrapper').removeClass('table-loading');
            }
        },
        "initComplete": function() {
            $('#clearFilters').toggle(activeFilters.size > 0);
        },
        scrollX: false,
        autoWidth: true,
        responsive: true,
        columnDefs: [
            {
                targets: [0], // checkbox column
                width: '30px',
                orderable: false
            },
            {
                targets: [1], // ID column
                width: '80px'
            },
            {
                targets: [2], // Title column
                width: '200px'
            }
        ]
    });

    // Thêm toggle buttons sau khi DataTable được khởi tạo
    $('.dataTables_filter').append(`
        <div class="search-mode-toggle tw-ml-3">
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-sm btn-default active" data-mode="or">OR</button>
                <button type="button" class="btn btn-sm btn-default" data-mode="and">AND</button>
            </div>
        </div>
    `);

    // Xử lý toggle search mode
    $('.search-mode-toggle .btn').on('click', function() {
        $('.search-mode-toggle .btn').removeClass('active');
        $(this).addClass('active');
        searchMode = $(this).data('mode');
        topicsTable.ajax.reload();
    });

    // Handle filter clicks from topic states
    $('.topic-state-card').on('click', '.small-box-footer', function(e) {
        e.preventDefault();
        var filter = $(this).data('filter');
        
        if (!activeFilters.has(filter)) {
            activeFilters.add(filter);
            addFilterTag(filter);
            updateFiltersVisibility();
            topicsTable.ajax.reload();
        }
    });

    // Clear all filters
    $('#clearFilters').on('click', function() {
        activeFilters.clear();
        $('.active-filters-list').empty();
        updateFiltersVisibility();
        topicsTable.ajax.reload();
    });

    // Export functionality
    $('#exportData').on('click', function() {
        var params = $.param({
            filters: Array.from(activeFilters)
        });
        window.location.href = admin_url + 'topics/export?' + params;
    });

    // Function to add filter tag
    function addFilterTag(filter) {
        var filterName = getFilterName(filter);
        var tag = $('<span>', {
            class: 'filter-tag tw-flex tw-items-center',
            'data-filter': filter,
            html: filterName + ' <i class="fa fa-times tw-ml-2"></i>'
        });

        $('.active-filters-list').append(tag);
    }

    // Function to update filters visibility
    function updateFiltersVisibility() {
        $('.active-filters').toggle(activeFilters.size > 0);
        $('#clearFilters').toggle(activeFilters.size > 0);
    }

    // Function to get filter friendly name
    function getFilterName(filter) {
        if (filter === 'total') return '<?php echo _l('all_topics'); ?>';
        
        var names = {
            'writing': '<?php echo _l('writing_topics'); ?>',
            'social_audit': '<?php echo _l('social_audit_topics'); ?>',
            'scheduled_social': '<?php echo _l('scheduled_social_topics'); ?>',
            'post_audit_gallery': '<?php echo _l('post_audit_gallery_topics'); ?>'
        };
        return names[filter] || filter;
    }

    // Handle removing individual filters
    $(document).on('click', '.filter-tag', function() {
        var filter = $(this).data('filter');
        activeFilters.delete(filter);
        $(this).remove();
        updateFiltersVisibility();
        topicsTable.ajax.reload();
    });

    // Handle select all
    $('#select_all').on('change', function() {
        $('.row-checkbox').prop('checked', $(this).prop('checked'));
        updateBulkActionsState();
    });

    // Handle individual checkbox changes
    $(document).on('change', '.row-checkbox', function() {
        updateBulkActionsState();
    });

    // Xử lý bulk actions
    $('.bulk-action').on('click', function(e) {
        e.preventDefault();
        var action = $(this).data('action');
        var ids = [];
        
        $('.row-checkbox:checked').each(function() {
            ids.push($(this).data('id'));
        });

        if (ids.length === 0) {
            alert_float('warning', '<?php echo _l('no_items_selected'); ?>');
            return;
        }

        // Thêm confirmation dialog
        var confirmMessage = '<?php echo _l('confirm_action'); ?>';
        if (!confirm(confirmMessage)) {
            return;
        }

        // Thực hiện bulk action
        $.post(admin_url + 'topics/bulk_action', {
            action: action,
            ids: ids,
            csrf_token_name: csrfData.csrf_token_name // Thêm CSRF token
        }).done(function(response) {
            if (response.success) {
                alert_float('success', response.message);
                // Reload table để cập nhật dữ liệu
                topicsTable.ajax.reload();
                // Reset bulk actions UI
                $('.bulk-actions .dropdown-toggle').prop('disabled', true);
                $('.selected-count').text('');
                $('#select_all').prop('checked', false);
            } else {
                alert_float('danger', response.message);
            }
        });
    });

    // Add these event handlers
    $(document).on('change', '.row-checkbox', function() {
        updateBulkActionsState();
    });

    $(document).on('change', '#select_all', function() {
        $('.row-checkbox').prop('checked', $(this).prop('checked'));
        updateBulkActionsState();
    });

    var bulkActionModal = $('#bulk_action');
    var selectedAction;

    // Xử lý khi mở modal bulk action
    bulkActionModal.on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        selectedAction = button.data('action');
    });

    // Xử lý khi click confirm trong modal
    $('#confirm_bulk_action').on('click', function() {
        var ids = [];
        $('.row-checkbox:checked').each(function() {
            ids.push($(this).val());
        });

        if (ids.length === 0) {
            alert_float('warning', '<?php echo _l('no_items_selected'); ?>');
            return;
        }

        var data = {};
        data.ids = ids;
        data.action = selectedAction;
        
        $.post(admin_url + 'topics/bulk_action', data).done(function(response) {
            response = JSON.parse(response);
            
            if (response.success) {
                alert_float('success', response.message);
                // Đóng modal
                bulkActionModal.modal('hide');
                
                // Đợi 1 giây để người dùng thấy thông báo success
                setTimeout(function() {
                    // Refresh trang
                    window.location.reload();
                }, 1000);
            } else {
                alert_float('danger', response.message);
                // Đóng modal
                bulkActionModal.modal('hide');
            }
        });
    });

    // Xử lý checkbox select all
    $('input[name="select_all"]').on('change', function() {
        $('.row-checkbox').prop('checked', $(this).prop('checked'));
        handleBulkActionsButtonState();
    });

    // Xử lý single checkbox
    $(document).on('change', '.row-checkbox', function() {
        handleBulkActionsButtonState();
    });

    // Hàm xử lý trạng thái nút bulk actions
    function handleBulkActionsButtonState() {
        var checkedCount = $('.row-checkbox:checked').length;
        var $bulkActionsBtn = $('.bulk-actions .dropdown-toggle');
        
        if (checkedCount > 0) {
            $bulkActionsBtn.prop('disabled', false);
            $('.selected-count').text(' (' + checkedCount + ' ' + '<?php echo _l('selected_items'); ?>' + ')');
        } else {
            $bulkActionsBtn.prop('disabled', true);
            $('.selected-count').text('');
        }
    }

    // Thêm hàm copyToClipboard vào global scope
    window.copyToClipboard = function(text) {
        // Tạo một element input tạm thời
        var tempInput = document.createElement('input');
        tempInput.value = text;
        document.body.appendChild(tempInput);
        
        // Select và copy
        tempInput.select();
        document.execCommand('copy');
        
        // Xóa element tạm
        document.body.removeChild(tempInput);
        
        // Hiển thị thông báo
        alert_float('success', '<?php echo _l('copied_to_clipboard'); ?>');
    }

    // Add click event for refresh button
    $('#refreshTable').on('click', function() {
        topicsTable.ajax.reload();
    });
});
</script>

<style>
.active-filters {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 4px;
    margin-bottom: 20px;
}

.active-filters h5 {
    margin-top: 0;
    margin-bottom: 10px;
    color: #333;
}

.filter-tag {
    display: inline-block;
    padding: 5px 10px;
    background: #e3f2fd;
    color: #1565c0;
    border-radius: 15px;
    margin: 0 5px 5px 0;
    font-size: 12px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.filter-tag:hover {
    background: #bbdefb;
}

.filter-tag i {
    margin-left: 5px;
    font-size: 10px;
}

/* Update topic state cards to show clickable cursor */
.topic-state-card .small-box-footer {
    cursor: pointer;
}

.dt-loader {
    position: absolute;
    background: rgba(255, 255, 255, 0.9);
    z-index: 999;
    border-radius: 5px;
    box-shadow: 0 0px 5px 0px rgba(0, 0, 0, 0.1);
    transform: translateY(-50%);
    padding: 12px 25px;
    left: 50%;
    top: 50%;
    transform: translate(-50%, -50%);
}

.dt-loader:before {
    content: "<?php echo _l('loading'); ?>";
    margin-right: 5px;
}

.dt-loader:after {
    content: "...";
}

.dataTables_processing {
    display: none !important;
}

.dt-loader {
    display: none;
}

.dataTables_processing.dt-loader {
    display: block !important;
}

.dt-table-loading {
    opacity: 0.6;
    position: relative;
}

.dt-table-loading:after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.7);
    z-index: 1;
}

.search-mode-toggle {
    display: inline-block;
    vertical-align: middle;
}

.search-mode-toggle .btn-group {
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    border-radius: 4px;
}

.search-mode-toggle .btn {
    font-size: 11px;
    font-weight: 600;
    padding: 4px 8px;
    min-width: 40px;
}

.search-mode-toggle .btn.active {
    background-color: #2196F3;
    color: white;
    border-color: #1976D2;
}

.bulk-actions {
    margin-right: 10px;
}

.bulk-actions .dropdown-menu {
    min-width: 200px;
}

.bulk-actions .dropdown-menu > li > a {
    padding: 8px 15px;
}

.check-all, .row-checkbox {
    width: 17px;
    height: 17px;
}

.selected-count {
    margin-left: 5px;
    color: #666;
}

.table-responsive {
    width: 100%;
    margin-bottom: 15px;
    overflow-y: hidden;
    -ms-overflow-style: -ms-autohiding-scrollbar;
}

.dataTable {
    width: 100% !important;
    margin: 0 !important;
}

.dataTables_wrapper {
    width: 100%;
    overflow-x: auto;
}

/* Đảm bảo các cột có chiều rộng phù hợp */
.dataTable th,
.dataTable td {
    white-space: nowrap;
}

/* Cột checkbox */
.dataTable th:first-child,
.dataTable td:first-child {
    width: 30px !important;
    min-width: 30px !important;
}

/* Cột ID */
.dataTable th:nth-child(2),
.dataTable td:nth-child(2) {
    width: 80px !important;
    min-width: 80px !important;
}

/* Cột Title */
.dataTable th:nth-child(3),
.dataTable td:nth-child(3) {
    min-width: 200px !important;
    max-width: 300px !important;
}
</style>
