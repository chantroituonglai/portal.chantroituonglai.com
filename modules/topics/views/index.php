<?php init_head(); ?>
<style>
.child-row.hidden {
    display: none;
}
.child-row {
    background-color: #f9f9f9;
}
.toggle-subtable {
    cursor: pointer;
    color: #03a9f4;
}

/* Add these styles for better table column handling */
.table-topics th,
.table-topics td {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.table-topics .topic-title {
    max-width: 200px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.tw-w-\[100px\], .tw-max-w-\[100px\]{
    max-width: 100px;
}

.tw-max-w-\[300px\]{
    max-width: 300px
}

.tw-max-w-\[200px\] {
    max-width: 200px;
}

/* Add these styles to your existing <style> block */
.log-content-pre {
    background-color: #f8f9fa;
    border: 1px solid #eee;
    border-radius: 4px;
    padding: 15px;
    margin: 0;
    white-space: pre-wrap;
    word-wrap: break-word;
    max-height: 500px;
    overflow-y: auto;
    font-family: monospace;
    font-size: 13px;
    line-height: 1.5;
}

.log-content-pre code {
    background: none;
    padding: 0;
    border: none;
    font-family: inherit;
    white-space: inherit;
}

/* Update modal styles */
#logModal .modal-dialog {
    width: 750px;
    max-width: 90%;
}

#logModal .modal-content {
    border-radius: 6px;
    border: none;
    box-shadow: 0 3px 7px rgba(0,0,0,.2);
}

#logModal .modal-header {
    background: #f8f9fa;
    padding: 15px 20px;
    border-bottom: 1px solid #e5e5e5;
    border-radius: 6px 6px 0 0;
}

#logModal .modal-header .modal-title {
    font-size: 16px;
    font-weight: 600;
    color: #333;
}

#logModal .modal-body {
    padding: 20px;
    max-height: 80vh;
    overflow-y: auto;
}

#logModal .loading {
    padding: 30px 0;
}

#logModal .loading i {
    color: #03a9f4;
}

/* Log content styles */
.log-content-pre {
    background-color: #f8f9fa;
    border: 1px solid #e5e5e5;
    border-radius: 4px;
    padding: 15px;
    margin: 0;
    white-space: pre-wrap;
    word-wrap: break-word;
    max-height: 500px;
    overflow-y: auto;
    font-family: Consolas, Monaco, 'Andale Mono', monospace;
    font-size: 13px;
    line-height: 1.5;
    color: #333;
}

.log-content-pre::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

.log-content-pre::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

.log-content-pre::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 4px;
}

.log-content-pre::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

.log-content-pre code {
    background: none;
    padding: 0;
    border: none;
    font-family: inherit;
    white-space: inherit;
}

/* Close button style */
#logModal .close {
    opacity: .5;
    transition: opacity .2s;
}

#logModal .close:hover {
    opacity: .8;
}

/* Add these styles to your existing <style> block */
.history-container {
    background: #fff;
    border-radius: 4px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.history-item {
    transition: all 0.2s;
}

.history-item:hover {
    background-color: #f8f9fa;
}

.show-history-log-popup {
    color: #03a9f4;
    text-decoration: none;
    padding: 4px 8px;
    border-radius: 4px;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
}

.show-history-log-popup:hover {
    background: rgba(3, 169, 244, 0.1);
    text-decoration: none;
}

.show-history-log-popup i {
    margin-right: 6px;
}

/* Label styles */
.label {
    display: inline-block;
    padding: 4px 12px;
    font-size: 11px;
    line-height: 1.4;
    border-radius: 12px;
    text-transform: uppercase;
    letter-spacing: 0.3px;
    font-weight: 600;
}

/* Info label style (for action types) */
.label-info {
    background-color: #e3f2fd;
    color: #0277bd;
}

/* Action Type Labels */
.label-action-type {
    background: #e3f2fd;
    color: #0277bd;
    border: 1px solid #90caf9;
}

/* Action State Labels */
.label-action-state {
    background: #f5f5f5;
    color: #455a64;
    border: 1px solid #e0e0e0;
}

/* Specific action type colors */
.label-type-create {
    background: #e8f5e9;
    color: #2e7d32;
    border: 1px solid #a5d6a7;
}

.label-type-update {
    background: #fff3e0;
    color: #ef6c00;
    border: 1px solid #ffcc80;
}

.label-type-delete {
    background: #fbe9e7;
    color: #d84315;
    border: 1px solid #ffab91;
}

/* Specific state colors */
.label-state-pending {
    background: #fff8e1;
    color: #ff8f00;
    border: 1px solid #ffe082;
}

.label-state-processing {
    background: #e3f2fd;
    color: #1565c0;
    border: 1px solid #90caf9;
}

.label-state-completed {
    background: #e8f5e9;
    color: #2e7d32;
    border: 1px solid #a5d6a7;
}

.label-state-failed {
    background: #fbe9e7;
    color: #d84315;
    border: 1px solid #ffab91;
}

/* Add these styles to your existing <style> block */
.bulk-actions-selection a.disabled {
    pointer-events: none;
    opacity: 0.6;
    cursor: not-allowed;
}

/* Update styles for disabled state */
.bulk-actions-selection a.disabled {
    pointer-events: none;
    opacity: 0.6;
    cursor: not-allowed;
    color: #999 !important;
    background-color: #f5f5f5 !important;
}

.bulk-actions-selection a[data-disabled="true"] {
    pointer-events: none;
    opacity: 0.6;
    cursor: not-allowed;
}

/* Prevent hover effects on disabled items */
.bulk-actions-selection a.disabled:hover,
.bulk-actions-selection a[data-disabled="true"]:hover {
    background-color: #f5f5f5 !important;
    cursor: not-allowed;
}

.history-actions a {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 8px;
    border-radius: 4px;
    transition: all 0.2s;
}

.history-actions a:hover {
    background: rgba(0,0,0,0.05);
    text-decoration: none;
}

.history-actions a i {
    font-size: 14px;
}

.history-actions .text-info:hover {
    color: #0056b3;
}

/* Responsive Table Styles */
@media (max-width: 991px) {
    .table-topics {
        display: block;
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    .table-topics th,
    .table-topics td {
        white-space: nowrap;
        min-width: 100px;
    }

    /* Pin only checkbox column */
    .table-topics th:first-child,
    .table-topics td:first-child {
        position: sticky;
        left: 0;
        background: #fff;
        z-index: 1;
        min-width: 50px;
    }

    .topic-id-container {
        min-width: 200px;
    }

    .tw-max-w-\[300px\] {
        min-width: 200px;
    }

    /* Add shadow to indicate scrollable content */
    .dataTables_wrapper::after {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        bottom: 0;
        width: 5px;
        background: linear-gradient(to right, rgba(0,0,0,0), rgba(0,0,0,0.1));
        pointer-events: none;
    }

    .topic-state-card{
        min-height: max-content !important;
        padding-bottom: 40px;
    }
}


/* Responsive Button Group Styles */
@media (max-width: 991px) {
    .panel-body .tw-flex.tw-justify-between {
        flex-direction: column;
        gap: 15px;
    }
    
    .panel-body .tw-flex.tw-justify-between .tw-flex.tw-gap-3 {
        flex-wrap: wrap;
        gap: 8px !important;
        justify-content: flex-start;
    }

    /* Make buttons full width on mobile */
    .panel-body .tw-flex.tw-gap-3 .btn {
        flex: 1 1 calc(50% - 8px);
        min-width: calc(50% - 8px);
        padding: 6px 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0;
    }

    /* Quick Actions dropdown full width */
    .panel-body .tw-flex.tw-gap-3 .dropdown {
        flex: 1 1 100%;
    }

    .panel-body .tw-flex.tw-gap-3 .dropdown-toggle {
        width: 100%;
        text-align: left;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    /* Adjust icon spacing */
    .panel-body .tw-flex.tw-gap-3 .btn i {
        margin-right: 8px;
    }

    /* Make text wrap if needed */
    .panel-body .tw-flex.tw-gap-3 .btn {
        white-space: normal;
        text-align: left;
    }
}
</style>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <!-- Topic States Section -->
            <div class="col-md-12">
                <?php $this->load->view('includes/topic_states'); ?>
            </div>

            <!-- Active Filters Section -->
            <div class="col-md-12 tw-mb-4" id="active-filters" style="display:none;">
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="tw-flex tw-items-center tw-space-x-2">
                            <span class="tw-font-medium"><?php echo _l('active_filters'); ?>:</span>
                            <div class="filter-tags tw-flex tw-flex-wrap tw-gap-2"></div>
                            <button id="clearFilters" class="btn btn-default btn-sm">
                                <i class="fa fa-times"></i> <?php echo _l('clear_filters'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Topics List Section -->
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="tw-flex tw-justify-between tw-items-center tw-mb-4">
                            <h4 class="tw-font-semibold tw-text-lg"><?php echo _l('topics'); ?></h4>
                            <div class="tw-flex tw-gap-3">
                                <!-- Add Refresh button -->
                                <button class="btn btn-default" id="refreshTable">
                                    <i class="fa fa-refresh"></i> <?php echo _l('refresh'); ?>
                                </button>
                                <!-- Add Quick Actions dropdown -->
                                <div class="dropdown bulk-actions">
                                    <button class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <?php echo _l('quick_actions'); ?>
                                        <span class="selected-count"></span>
                                        <span class="caret"></span>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li class="bulk-actions-selection">
                                            <a href="#" data-target="#controller_modal">
                                                <i class="fa fa-plus-circle"></i> <?php echo _l('add_controller'); ?>
                                            </a>
                                        </li>
                                        <li class="divider"></li>
                                        <li class="bulk-actions-selection">
                                            <a href="#" data-toggle="modal" data-target="#bulk_action" data-action="activate">
                                                <i class="fa fa-check"></i> <?php echo _l('quick_activate'); ?>
                                            </a>
                                        </li>
                                        <li class="bulk-actions-selection">
                                            <a href="#" data-toggle="modal" data-target="#bulk_action" data-action="deactivate">
                                                <i class="fa fa-times"></i> <?php echo _l('quick_deactivate'); ?>
                                            </a>
                                        </li>
                                        <li class="divider"></li>
                                        <li>
                                            <a href="#" data-action="activate_all">
                                                <i class="fa fa-check-double"></i> <?php echo _l('activate_all'); ?>
                                            </a>
                                        </li>
                                        <li>
                                            <a href="#" data-action="deactivate_all">
                                                <i class="fa fa-times-circle"></i> <?php echo _l('deactivate_all'); ?>
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                                
                                <!-- Add Clear Filters button -->
                                <button class="btn btn-default" id="clearFilters" style="display:none">
                                    <i class="fa fa-times"></i> <?php echo _l('clear_filters'); ?>
                                </button>

                                <!-- Existing buttons -->
                                <a href="<?php echo admin_url('topics/topic_master/targets'); ?>" class="btn btn-info">
                                    <i class="fa fa-bullseye tw-mr-1"></i>
                                    <?php echo _l('topic_targets'); ?>
                                </a>
                                <a href="<?php echo admin_url('topics/action_types'); ?>" class="btn btn-info">
                                    <i class="fa fa-gear tw-mr-1"></i>
                                    <?php echo _l('action_types'); ?>
                                </a>
                                <?php if (has_permission('topics', '', 'create')) { ?>
                                <a href="<?php echo admin_url('topics/create'); ?>" class="btn btn-primary">
                                    <i class="fa fa-plus tw-mr-1"></i>
                                    <?php echo _l('new_topic'); ?>
                                </a>
                                <?php } ?>
                            </div>
                        </div>
                        <?php render_datatable([
                            '<input type="checkbox" id="select_all" class="tw-mr-2"/>',  // New checkbox column
                            _l('topic_id'),
                            _l('topic_title'),
                            _l('status'),
                            _l('datecreated'),
                            _l('options')
                        ], 'topics'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Log Modal -->
<div class="modal fade" id="logModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><?php echo _l('log_details'); ?></h4>
            </div>
            <div class="modal-body">
                <div class="loading text-center">
                    <i class="fa fa-spinner fa-spin fa-3x"></i>
                </div>
                <div class="log-content" style="display:none;"></div>
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
                <h4 class="modal-title"><?php echo _l('bulk_action_confirmation'); ?></h4>
            </div>
            <div class="modal-body">
                <p><?php echo _l('bulk_action_confirmation_msg'); ?></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
                <button type="button" class="btn btn-primary" id="confirmBulkAction"><?php echo _l('confirm'); ?></button>
            </div>
        </div>
    </div>
</div>

<!-- Controller Modal -->
<div class="modal fade" id="controller_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><?php echo _l('select_controller'); ?></h4>
            </div>
            <div class="modal-body">
                <select id="controller_select" class="form-control">
                    <?php 
                    $CI = &get_instance();
                    $CI->load->model('topics/Topic_controller_model');
                    $controllers = $CI->Topic_controller_model->get();
                    foreach($controllers as $controller){ ?>
                    <option value="<?php echo $controller['id']; ?>"><?php echo $controller['site']; ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
                <button type="button" class="btn btn-info" onclick="assignController()"><?php echo _l('assign'); ?></button>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script>
$(function() {
    var activeFilters = new Set();
    let searchMode = 'or';

    // Initialize DataTable with filter support
    var topicsTable = $('.table-topics').DataTable({
        pageLength: 50,
        responsive: false,
        autoWidth: false,
        processing: true,
        serverSide: true,
        order: [[4, 'desc']],
        ajax: {
            url: admin_url + 'topics/table',
            type: 'POST',
            data: function(d) {
                d.filters = Array.from(activeFilters);
                d.search_mode = searchMode;
                return d;
            },
            "beforeSend": function() {
                $('.table-topics').addClass('dt-table-loading');
                $('.dataTables_processing').addClass('dt-loader');
            },
            "complete": function() {
                $('.table-topics').removeClass('dt-table-loading');
                $('.dataTables_processing').removeClass('dt-loader');
                $('.dataTables_wrapper').removeClass('table-loading');
            }
        },
        "initComplete": function() {
            $('#clearFilters').toggle(activeFilters.size > 0);
        },
        columnDefs: [
            { 
                targets: 0,
                orderable: false,
                checkboxes: true,
                width: '30px',
                render: function (data, type, row) {
                    return '<input type="checkbox" class="row-checkbox" value="' + row[0] + '">';
                }
            },
            { "width": "50px", "targets": 1 },  // Topic ID
            { "width": "200px", "targets": 2 },  // Topic Title
            { "width": "100px", "targets": -1 }, // Options column
            { 
                "targets": [1,2], 
                "className": "text-nowrap",
                "overflow": "hidden",
                "text-overflow": "ellipsis"
            }
        ]
    });

    // Handle log popup
    $('#logModal').on('show.bs.modal', function (e) {
        var button = $(e.relatedTarget);
        var topicid = button.data('topicid');
        var id = button.data('id');
        var modal = $(this);
        
        modal.find('.loading').show();
        modal.find('.log-content').hide();
        
        // Load log data via AJAX
        $.post(admin_url + 'topics/get_log_data', {
            topicid: topicid,
            id: id
        }, function(response) {
            try {
                var data = JSON.parse(response);
                if (data.success) {
                    var logHtml = `
                        <div class="log-item tw-bg-white tw-rounded-lg tw-shadow-sm tw-p-4">
                            <div class="log-header tw-border-b tw-border-neutral-200 tw-pb-3 tw-mb-3">
                                <div class="tw-grid tw-grid-cols-2 tw-gap-3">
                                    <div class="tw-flex tw-items-center">
                                        <span class="tw-font-medium tw-w-24">Topic ID:</span>
                                        <span>${data.data.topicid}</span>
                                    </div>
                                    <div class="tw-flex tw-items-center">
                                        <span class="tw-font-medium tw-w-24">Date:</span>
                                        <span>${moment(data.data.dateupdated).format('DD/MM/YYYY HH:mm')}</span>
                                    </div>
                                </div>
                                <div class="tw-mt-2">
                                    <div class="tw-flex tw-items-center">
                                        <span class="tw-font-medium tw-w-24">Title:</span>
                                        <span class="tw-flex-1">${data.data.topictitle}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="log-body">
                                <div class="tw-font-medium tw-mb-2">Log Content:</div>
                                <pre class="log-content-pre"><code>${formatLogContent(data.data.log)}</code></pre>
                            </div>
                        </div>
                    `;
                    modal.find('.log-content').html(logHtml);
                } else {
                    modal.find('.log-content').html('<div class="alert alert-danger">Error loading log data</div>');
                }
            } catch (e) {
                modal.find('.log-content').html('<div class="alert alert-danger">Error parsing response</div>');
            }
            modal.find('.loading').hide();
            modal.find('.log-content').show();
        });
    });

    // Handle show/hide topic ID in table
    $(document).on('click', '.toggle-topic-id', function(e) {
        e.preventDefault();
        e.stopPropagation();
        var container = $(this).closest('.tw-flex-1');
        var idText = container.find('.topic-id-text');
        
        if (idText.is(':hidden')) {
            // Hide all other topic IDs first
            $('.topic-id-text').hide();
            $('.toggle-topic-id').html('<i class="fa fa-eye"></i> <?php echo _l('show_topic_id'); ?>');
            
            // Show this topic ID
            idText.show();
            $(this).html('<i class="fa fa-eye-slash"></i> <?php echo _l('hide_topic_id'); ?>');
        } else {
            idText.hide();
            $(this).html('<i class="fa fa-eye"></i> <?php echo _l('show_topic_id'); ?>');
        }
    });

    // Function to copy text to clipboard
    window.copyToClipboard = function(text) {
        // Create temporary input
        var tempInput = document.createElement('input');
        tempInput.value = text;
        document.body.appendChild(tempInput);
        
        // Select and copy
        tempInput.select();
        document.execCommand('copy');
        
        // Remove temporary input
        document.body.removeChild(tempInput);
        
        // Show success message
        alert_float('success', '<?php echo _l('copied_to_clipboard'); ?>');
    }

    // Add search mode toggle
    $('.dataTables_filter').append(`
        <div class="search-mode-toggle tw-ml-3">
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-sm btn-default active" data-mode="or">OR</button>
                <button type="button" class="btn btn-sm btn-default" data-mode="and">AND</button>
            </div>
        </div>
    `);

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

    // Filter tag functions
    function addFilterTag(filter) {
        var tag = $(`
            <div class="filter-tag" data-filter="${filter}">
                <span class="label label-info">
                    ${filter}
                    <i class="fa fa-times remove-filter"></i>
                </span>
            </div>
        `);
        $('.filter-tags').append(tag);
    }

    function updateFiltersVisibility() {
        $('#active-filters').toggle(activeFilters.size > 0);
    }

    // Remove filter when clicking on tag
    $(document).on('click', '.remove-filter', function() {
        var filter = $(this).closest('.filter-tag').data('filter');
        activeFilters.delete(filter);
        $(this).closest('.filter-tag').remove();
        updateFiltersVisibility();
        topicsTable.ajax.reload();
    });

    // Clear all filters
    $('#clearFilters').on('click', function() {
        activeFilters.clear();
        $('.filter-tags').empty();
        updateFiltersVisibility();
        topicsTable.ajax.reload();
    });

    // Toggle search mode
    $('.search-mode-toggle .btn').on('click', function() {
        $('.search-mode-toggle .btn').removeClass('active');
        $(this).addClass('active');
        searchMode = $(this).data('mode');
        topicsTable.ajax.reload();
    });

    // Store active filters
    var activeFilters = new Set();

    // Initialize search mode
    // let searchMode = 'or';

    // Update bulk actions state
    function updateBulkActionsState() {
        var checkedBoxes = $('.row-checkbox:checked').length;
        
        // Disable/enable bulk actions dropdown button
        $('.bulk-actions .dropdown-toggle').prop('disabled', checkedBoxes === 0);
        
        if (checkedBoxes === 0) {
            // Thêm class disabled và data attribute
            $('.bulk-actions-selection a')
                .addClass('disabled')
                .attr('data-disabled', 'true');
        } else {
            // Xóa class disabled và data attribute
            $('.bulk-actions-selection a')
                .removeClass('disabled')
                .removeAttr('data-disabled');
        }
        
        // Update selected count
        $('.selected-count').text(checkedBoxes > 0 ? ' (' + checkedBoxes + ')' : '');
    }

    // Add click handler to prevent clicking disabled items
    $(document).on('click', '.bulk-actions-selection a.disabled', function(e) {
        e.preventDefault();
        return false;
    });

    // Thêm biến toàn cục để lưu action
    window.currentBulkAction = null;

    // Cập nhật hàm xử lý cho Active All và Deactive All
    function handleBulkActionAll(action) {
        // Set giá trị vào biến toàn cục
        window.currentBulkAction = action;
        
        // Hiển thị modal xác nhận
        $('#bulk_action').modal('show');
    }

    // Handle bulk action confirmation
    $('#confirmBulkAction').on('click', function() {
        var action = window.currentBulkAction;
        console.log('Action on confirm:', action); // Debug log
        
        if (!action) {
            alert_float('danger', 'No action specified');
            return;
        }

        var data = {
            action: action,
            ids: $('.row-checkbox:checked').map(function() {
                return $(this).val();
            }).get()
        };

        console.log('Request data:', data); // Debug log

        // Perform bulk action
        $.post(admin_url + 'topics/bulk_action', data).done(function(response) {
            console.log('Action:', action); // Debug log
            console.log('Response:', response); // Debug log
            var response = JSON.parse(response);
            if (response.success) {
                alert_float('success', response.message);
                
                // Kiểm tra chính xác action có kết thúc bằng _all
                if (action === 'activate_all' || action === 'deactivate_all') {
                    console.log('Refreshing page...'); // Debug log
                    window.location.reload();
                } else {
                    console.log('Refreshing table only...'); // Debug log
                    // Đối với action item: chỉ refresh bảng và reset trạng thái
                    topicsTable.ajax.reload(null, false);
                    
                    // Reset các trạng thái UI
                    $('.bulk-actions .dropdown-toggle').prop('disabled', true);
                    $('.selected-count').text('');
                    $('#select_all').prop('checked', false);
                    $('.row-checkbox').prop('checked', false);
                }
            } else {
                alert_float('danger', response.message);
            }
            $('#bulk_action').modal('hide');
        }).fail(function(xhr, status, error) {
            console.error('Bulk action error:', error); // Debug log
            alert_float('danger', 'Error performing bulk action');
            $('#bulk_action').modal('hide');
        });
    });

    // Add checkbox change handlers
    $(document).on('change', '.row-checkbox', updateBulkActionsState);
    $(document).on('change', '#select_all', function() {
        $('.row-checkbox').prop('checked', $(this).prop('checked'));
        updateBulkActionsState();
    });

    // Thêm handler khi modal đóng để reset biến toàn cục
    $('#bulk_action').on('hidden.bs.modal', function () {
        window.currentBulkAction = null;
    });

    // Sửa lại handler cho bulk action links
    $(document).on('click', '.bulk-actions-selection a', function(e) {
        e.preventDefault();
        
        // Lấy action từ data attribute
        var action = $(this).data('action');
        console.log('Clicked action:', action); // Debug log
        
        // Kiểm tra số lượng item được chọn
        var checkedBoxes = $('.row-checkbox:checked').length;
        if (checkedBoxes === 0) {
            alert_float('warning', 'Please select at least one item');
            return false;
        }

        // Xử lý riêng cho Add Controller
        if ($(this).data('target') === '#controller_modal'){
            $('#controller_modal').modal('show');
            return;
        }
        
        // Set action vào biến toàn cục chocác action khác
        window.currentBulkAction = action;
        console.log('Set currentBulkAction:', window.currentBulkAction); // Debug log
        
        // Show bulk action modal cho các action khác
        $('#bulk_action').modal('show');
    });

    // Thêm handler khi modal đóng để reset data
    $('#bulk_action').on('hidden.bs.modal', function () {
        $(this).removeData('action');
    });

    // Add click handler for activate_all and deactivate_all links
    $(document).on('click', '.bulk-actions a[data-action$="_all"]', function(e) {
        e.preventDefault();
        
        // Get the action from data attribute
        var action = $(this).data('action');
        
        // Set the current bulk action
        window.currentBulkAction = action;
        
        // Show the confirmation modal
        $('#bulk_action').modal('show');
    });

    // Add click event for refresh button
    $('#refreshTable').on('click', function() {
        topicsTable.ajax.reload();
    });
});

// Handle expand/collapse
$('body').on('click', '.toggle-subtable', function(e) {
    e.preventDefault();
    var $icon = $(this).find('i');
    var $row = $(this).closest('tr');
    var topicid = $(this).data('topicid');
    
    if ($icon.hasClass('fa-plus-square')) {
        $icon.removeClass('fa-plus-square').addClass('fa-minus-square');
        loadTopicHistory(topicid, $row);
    } else {
        $icon.removeClass('fa-minus-square').addClass('fa-plus-square');
        $row.nextUntil('tr:not(.expanded-row)').remove();
    }
});

// Initialize popovers for log content
$('body').on('mouseenter', '.show-log-popup', function() {
    $(this).popover({
        placement: 'left',
        html: true,
        container: 'body',
        trigger: 'manual',
        template: '<div class="popover" role="tooltip"><div class="arrow"></div><div class="popover-content tw-max-w-[500px] tw-break-words"></div></div>'
    }).popover('show');
});

$('body').on('mouseleave', '.show-log-popup', function() {
    $(this).popover('hide');
});

// Handle topic history loading
function loadTopicHistory(topicid, $row) {
    $.ajax({
        url: admin_url + 'topics/get_topic_history_ajax',
        type: 'POST',
        data: {
            topicid: topicid
        },
        success: function(response) {
            try {
                var data = JSON.parse(response);
                if (!data.success) {
                    console.error('Error loading history:', data.message);
                    return;
                }
                
                var history = data.data;
                var $historyRow = $('<tr class="expanded-row"><td colspan="6"><div class="history-container tw-p-4"></div></td></tr>');
                var $historyContainer = $historyRow.find('.history-container');
                
                if (history.length === 0) {
                    $historyContainer.append('<div class="tw-text-center tw-text-neutral-500"><?php echo _l('no_history_found'); ?></div>');
                } else {
                    history.forEach(function(item) {
                        // Set default color if state_color is undefined
                        const stateColor = item.state_color || '#000000';
                        const textColor = getContrastColor(stateColor);
                        
                        $historyContainer.append(`
                            <div class="history-item tw-p-3 tw-border-b tw-border-neutral-200 last:tw-border-0">
                                <div class="tw-flex tw-items-center tw-justify-between">
                                    <div class="tw-flex tw-items-center tw-space-x-4">
                                        <span class="label label-info">${item.action_type_name || ''}</span>
                                        <span class="label" style="background-color: ${stateColor}; 
                                              color: ${textColor};
                                              font-weight: 600;">
                                            ${item.action_state_name || ''}
                                        </span>
                                        <span class="tw-text-neutral-600">
                                            ${moment(item.dateupdated).format('DD/MM/YYYY HH:mm')}
                                        </span>
                                    </div>
                                    <div>
                                        <a href="#" class="show-history-log-popup" 
                                           data-toggle="modal" 
                                           data-target="#logModal"
                                           data-topicid="${item.topicid}"
                                           data-id="${item.id}">
                                            <i class="fa fa-file-text-o"></i> <?php echo _l('view_log'); ?>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        `);
                    });
                }
                
                $row.after($historyRow);
            } catch (e) {
                console.error('Error parsing response:', e);
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.error('AJAX error:', textStatus, errorThrown);
        }
    });
}

// Add this function before the $(function() { ... }) block
function formatLogContent(logContent) {
    try {
        // Check if the content is JSON
        const parsed = JSON.parse(logContent);
        return JSON.stringify(parsed, null, 2);
    } catch (e) {
        // If not JSON, return as is
        return logContent;
    }
}

// Helper function to adjust color brightness
function adjustBrightness(color, percent) {
    // Return default color if color is undefined/null/empty
    if (!color) return '#000000';
    
    // Remove # if present
    color = color.replace('#', '');
    
    // If color is not valid hex, return default
    if (!/^[0-9A-F]{6}$/i.test(color)) return '#000000';
    
    try {
        var R = parseInt(color.substring(0,2),16);
        var G = parseInt(color.substring(2,2),16);
        var B = parseInt(color.substring(4,2),16);

        R = parseInt(R * (100 + percent) / 100);
        G = parseInt(G * (100 + percent) / 100);
        B = parseInt(B * (100 + percent) / 100);

        R = (R<255)?R:255;  
        G = (G<255)?G:255;  
        B = (B<255)?B:255;  

        var RR = ((R.toString(16).length==1)?"0"+R.toString(16):R.toString(16));
        var GG = ((G.toString(16).length==1)?"0"+G.toString(16):G.toString(16));
        var BB = ((B.toString(16).length==1)?"0"+B.toString(16):B.toString(16));

        return "#"+RR+GG+BB;
    } catch (e) {
        console.error('Error adjusting brightness:', e);
        return '#000000';
    }
}

// Helper function to get contrast color (black or white) based on background
function getContrastColor(hexcolor) {
    // Remove # if present
    hexcolor = hexcolor.replace("#", "");
    
    // Convert to RGB
    var r = parseInt(hexcolor.substr(0,2),16);
    var g = parseInt(hexcolor.substr(2,2),16);
    var b = parseInt(hexcolor.substr(4,2),16);
    
    // Calculate luminance
    var yiq = ((r*299)+(g*587)+(b*114))/1000;
    
    // Tăng ngưỡng để có độ tương phản tốt hơn
    return (yiq >= 140) ? '#000000' : '#ffffff';
}

function get_n8n_url(execution_id, workflow_id) {
    const n8n_host = '<?php echo get_option("topics_n8n_host"); ?>';
    const n8n_webhook = '<?php echo get_option("topics_n8n_webhook_url"); ?>';
    
    if (n8n_host) {
        const base_url = rtrim(n8n_host, '/');
        if (workflow_id) {
            return base_url + '/workflow/' + workflow_id + '/executions/' + execution_id;
        }
        return base_url + '/execution/' + execution_id;
    }
    
    // Fallback to old webhook URL
    if (n8n_webhook && execution_id) {
        return n8n_webhook.replace('/webhook/', '/execution/') + '/' + execution_id;
    }
    
    return '#';
}

function rtrim(str, char) {
    if (!str) return str;
    return str.replace(new RegExp(char + '+$'), '');
}

function assignController() {
    var controllerId = $('#controller_select').val();
    var selectedTopics = $('.row-checkbox:checked').map(function() {
        return $(this).val();
    }).get();
    
    if (selectedTopics.length === 0) {
        alert_float('warning', '<?php echo _l("please_select_topics"); ?>');
        return;
    }
    
    //�óng modal chọn controller
    $('#controller_modal').modal('hide');
    
    // Hiển thị dialog xác nhận
    var message = '<?php echo _l("confirm_action_prompt"); ?>';
    message = message.replace('{0}', selectedTopics.length);
    
    // Sử dụng confirm dialog của Perfex
    confirm_dialog({
        message: message,
        title: '<?php echo _l("confirm"); ?>',
        yes_callback: function() {
            $.post(admin_url + 'topics/assign_controller', {
                controller_id: controllerId,
                topic_ids: selectedTopics
            }).done(function(response) {
                response = JSON.parse(response);
                if (response.success) {
                    alert_float('success', response.message);
                    // Refresh table
                    topicsTable.ajax.reload();
                } else {
                    alert_float('danger', response.message);
                }
            });
        },
        no_callback: function() {
            // Mở lại modal chọn controller nếu user click No
            $('#controller_modal').modal('show');
        }
    });
}
</script>

