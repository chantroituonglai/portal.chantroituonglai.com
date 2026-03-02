function loadActionStates(actionTypeCode) {
    if (!actionTypeCode) {
        $('#action_state_code').html('<option value="">' + app.lang.none + '</option>');
        $('#action_state_code').selectpicker('refresh');
        return;
    }

    $.ajax({
        url: admin_url + 'action_states/get_by_type/' + actionTypeCode,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            var options = '<option value="">' + app.lang.none + '</option>';
            response.forEach(function(state) {
                options += '<option value="' + state.action_state_code + '">' + 
                          state.name + '</option>';
            });
            $('#action_state_code').html(options);
            $('#action_state_code').selectpicker('refresh');
        }
    });
}

$(document).ready(function() {
    $('#action_type_code').on('change', function() {
        loadActionStates($(this).val());
    });
});

$(function() {
    // Khởi tạo biến lưu trữ trạng thái filter hiện tại
    window.currentFilterState = 'total'; // Đặt là biến global để có thể truy cập từ mọi nơi

    // Đánh dấu total là active mặc định trước khi khởi tạo DataTable
    $('.topic-state-card.topics-total').addClass('active');

    // Khởi tạo DataTable với cấu hình đầy đủ
    var table = $('.table-topics').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": {
            "url": admin_url + 'topics/table',
            "type": "POST",
            "data": function(d) {
                // Luôn gửi filter mặc định trong lần load đầu tiên
                d.filters = [window.currentFilterState];
                d.search_mode = 'or';
                return d;
            }
        },
        "order": [[0, 'desc']],
        "columnDefs": [
            { "orderable": false, "targets": [5] }
        ],
        "initComplete": function(settings, json) {
            // Callback sau khi DataTable đã được khởi tạo hoàn toàn
            console.log('DataTable initialized with filter:', window.currentFilterState);
        }
    });

    // Xử lý click vào các topic state cards
    $('.topic-state-card').on('click', function(e) {
        e.preventDefault();
        var filter = $(this).data('filter');
        
        // Cập nhật trạng thái filter
        window.currentFilterState = filter;
        
        // Cập nhật giao diện
        $('.topic-state-card').removeClass('active');
        $(this).addClass('active');
        
        // Reload table với filter mới
        table.ajax.reload(null, false); // false để giữ nguyên trang hiện tại
    });

    // Handle expand/collapse
    $('.table-topics').on('click', '.toggle-subtable', function(e) {
        e.preventDefault();
        var tr = $(this).closest('tr');
        var row = table.row(tr);
        var topicid = $(this).data('topicid');
        var icon = $(this).find('i');

        if (row.child.isShown()) {
            row.child.hide();
            tr.removeClass('shown');
            icon.removeClass('fa-minus-square').addClass('fa-plus-square');
        } else {
            // Loading indicator
            row.child('<div class="text-center"><i class="fa fa-spinner fa-spin"></i> Loading...</div>').show();
            tr.addClass('shown');
            icon.removeClass('fa-plus-square').addClass('fa-minus-square');

            // Load history via AJAX
            loadTopicHistory(topicid, tr);
        }
    });

    // Initialize color picker
    if($('.color-picker-wrapper').length > 0) {
        $('.color-picker-wrapper input').colorpicker({
            format: 'hex'
        }).on('changeColor', function(e) {
            $(this).siblings('.input-group-addon').find('i').css('background-color', e.color.toString());
        });
    }
    
    // Initialize datatables on dashboard
    if($('.table-topics-dashboard').length > 0) {
        initDataTable('.table-topics-dashboard', admin_url + 'topics/table_dashboard', undefined, undefined);
    }

    initActionTypeSorting();
    initActionStateSorting();

    // Thêm sự kiện click cho khối "Fail Topics"
    $('.topic-state-card.fail-topics').on('click', function() {
        var filter = $(this).data('filter'); // 'fail'
        applyFilter(filter);
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
            $('.toggle-topic-id').html('<i class="fa fa-eye"></i> ' + app.lang.show_topic_id);
            
            // Show this topic ID
            idText.show();
            $(this).html('<i class="fa fa-eye-slash"></i> ' + app.lang.hide_topic_id);
        } else {
            idText.hide();
            $(this).html('<i class="fa fa-eye"></i> ' + app.lang.show_topic_id);
        }
    });
});

function loadTopicHistory(topicid, $row) {
    $.get(admin_url + 'topics/get_topic_history_ajax', {
        topicid: topicid
    }, function(response) {
        var history = JSON.parse(response).data;
        var $historyRow = $('<tr class="expanded-row"><td colspan="6"><div class="history-container tw-p-4"></div></td></tr>');
        var $historyContainer = $historyRow.find('.history-container');
        
        if (history.length === 0) {
            $historyContainer.append('<div class="tw-text-center tw-text-neutral-500">No history found</div>');
        } else {
            history.forEach(function(item) {
                $historyContainer.append(`
                    <div class="history-item tw-mb-4 tw-border-b tw-border-neutral-200 tw-pb-4 last:tw-border-0 last:tw-pb-0">
                        <div class="tw-flex tw-items-center tw-justify-between">
                            <div class="tw-flex tw-items-center tw-space-x-4">
                                <span class="label label-info">${item.action_type_name}</span>
                                <span class="label label-default">${item.action_state_name}</span>
                                <span class="tw-text-neutral-600">${moment(item.dateupdated).format('DD/MM/YYYY HH:mm')}</span>
                            </div>
                            <div>
                                <a href="#" class="show-history-log-popup" 
                                   data-toggle="modal" 
                                   data-target="#logModal"
                                   data-topicid="${item.topicid}"
                                   data-id="${item.id}">
                                    <i class="fa fa-file-text-o"></i> View Log
                                </a>
                            </div>
                        </div>
                    </div>
                `);
            });
        }
        
        $row.after($historyRow);
    });
}

$(document).on('click', '.show-history-log-popup', function(e) {
    e.preventDefault();
    var topicid = $(this).data('topicid');
    var id = $(this).data('id');
    
    $('#logModal').modal('show');
    $('#logModal').find('.loading').show();
    $('#logModal').find('.log-content').hide();
    
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
                $('#logModal').find('.log-content').html(logHtml);
            } else {
                $('#logModal').find('.log-content').html('<div class="alert alert-danger">Error loading log data</div>');
            }
        } catch (e) {
            $('#logModal').find('.log-content').html('<div class="alert alert-danger">Error parsing response</div>');
        }
        $('#logModal').find('.loading').hide();
        $('#logModal').find('.log-content').show();
    });
}); 

// Add drag & drop sorting for action types
function initActionTypeSorting() {
    var actionTypeList = $('.action-type-list');
    if (actionTypeList.length) {
        Sortable.create(actionTypeList[0], {
            handle: '.drag-handle',
            animation: 150,
            onEnd: function(evt) {
                var positions = {};
                actionTypeList.find('.action-type-item').each(function(index) {
                    positions[$(this).data('id')] = index;
                });
                
                // Save new positions
                $.post(admin_url + 'topics/action_types/reorder', {
                    positions: positions
                });
            }
        });
    }
}

// Add drag & drop sorting for action states
function initActionStateSorting() {
    var actionStateList = $('.action-state-list');
    if (actionStateList.length) {
        Sortable.create(actionStateList[0], {
            handle: '.drag-handle', 
            animation: 150,
            onEnd: function(evt) {
                var positions = {};
                actionStateList.find('.action-state-item').each(function(index) {
                    positions[$(this).data('id')] = index;
                });
                
                // Save new positions
                $.post(admin_url + 'topics/action_states/reorder', {
                    positions: positions
                });
            }
        });
    }
}

// Add click handlers for quick filter buttons
$(document).on('click', '.quick-filter', function(e) {
    e.preventDefault();
    var filter = $(this).data('filter');
    
    // Update active state of filter buttons
    $('.quick-filter').removeClass('active');
    $(this).addClass('active');
    
    // Reload DataTable with filter
    var table = $('.dt-table').DataTable();
    table.ajax.url(admin_url + 'topics/table?quick_filter=' + filter).load();
});

// Clear filter
$(document).on('click', '.clear-filter', function(e) {
    e.preventDefault();
    
    // Remove active state from all filter buttons
    $('.quick-filter').removeClass('active');
    
    // Reload DataTable without filter
    var table = $('.dt-table').DataTable();
    table.ajax.url(admin_url + 'topics/table').load();
}); 

// Hàm áp dụng bộ lọc và tải lại DataTable
function applyFilter(filter) {
    if (typeof window.currentFilterState !== 'undefined') {
        window.currentFilterState = filter;
        var table = $('.table-topics').DataTable();
        if (table) {
            table.ajax.reload();
        }
    }
}

// Khởi tạo DataTable với hỗ trợ bộ lọc
$('#topics-table').DataTable({
    // ... existing configurations ...
    ajax: {
        url: admin_url + 'topics/get_table_data',
        type: 'POST',
        data: function(d) {
            d.filters = {
                state: currentFilterState // Áp dụng trạng thái bộ lọc hiện tại
            };
        }
    },
    // ... existing configurations ...
}); 

// Function to copy text to clipboard
function copyToClipboard(text) {
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
    alert_float('success', app.lang.copied_to_clipboard);
} 
