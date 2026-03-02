<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<link href="<?php echo module_dir_url('topics', 'assets/css/progress_steps.css?ver=' . time()); ?>" rel="stylesheet">
<link href="<?php echo module_dir_url('topics', 'assets/css/detail.css?ver=' . time()); ?>" rel="stylesheet">
<!-- Thêm vào phần head hoặc trước closing body -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<input type="hidden" id="topicid" value="<?php echo $topic->topicid; ?>">
<script>
    var topicCurrentId = '<?php echo $topic->topicid; ?>';
    var _topicId = '<?php echo $topic->id; ?>';
    var topicMasterId = '<?php echo $this->Topic_master_model->get_by_topicid($topic->topicid)->id; ?>';
    var topicMasterTitle = '<?php echo $this->Topic_master_model->get_by_topicid($topic->topicid)->topictitle; ?>';
    var langTopicUpdatedNotification = '<?php echo _l('topic_updated_notification'); ?>';
    console.log('topicMasterId', topicMasterId);
    console.log('topicMasterTitle', topicMasterTitle);
</script>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <!-- Header Section -->
                        <div class="tw-flex tw-justify-between tw-items-center tw-mb-4">
                            <h4 class="tw-font-semibold tw-text-lg tw-text-neutral-700">
                                <i class="fa fa-magic"></i> <?php echo _l('topic_detail'); ?>
                            </h4>
                            <div class="tw-flex tw-items-center tw-space-x-2">
                                <div class="simple-bootstrap-select">
                                    <select data-width="100%" id="search_topics" class="selectpicker" 
                                            data-live-search="true" 
                                            data-title="<?php echo _l('search_topics'); ?>">
                                        <option value=""></option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <?php echo $additional_info; ?>

                        <!-- Tabs Navigation -->
                        <ul class="nav nav-tabs" role="tablist">
                            <li role="presentation" class="active">
                                <a href="#overview" aria-controls="overview" role="tab" data-toggle="tab">
                                    <i class="fa fa-th-list"></i> <?php echo _l('overview'); ?>
                                </a>
                            </li>
                            <li role="presentation">
                                <a href="#logs" aria-controls="logs" role="tab" data-toggle="tab" aria-expanded="true">
                                    <i class="fa fa-history"></i> Log
                                    <span id="new-log-count" class="badge badge-danger" style="display: none;">0</span>
                                </a>
                            </li>
                        </ul>

                        <!-- Tab Content -->
                        <div class="tab-content">
                            <!-- Overview Tab -->
                            <div role="tabpanel" class="tab-pane active" id="overview">
                                <div class="row">
                                    <div class="col-md-12">
                                        <!-- Topic Info Panel -->
                                        <div class="panel panel-default topic-info-panel">
                                            <div class="panel-body">
                                                <!-- Topic ID Row -->
                                                <div class="topic-info-row">
                                                    <div class="info-label"><?php echo _l('topic_id'); ?>:</div>
                                                    <div class="info-value">
                                                        <span id="topic-id-display" style="display: none;"><?php echo $topic->topicid; ?></span>
                                                        <a href="#" id="toggle-topic-id" class="btn btn-link">
                                                            <i class="fa fa-eye"></i> <?php echo _l('show_topic_id'); ?>
                                                        </a>
                    </div>
                </div>

                                                <!-- Topic Title Row -->
                                                <div class="topic-info-row">
                                                    <div class="info-label"><?php echo _l('topic_title'); ?>:</div>
                                                    <div class="info-value">
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <span class="topic-title"><?php echo html_escape($topic->topictitle); ?></span>
                                                            <?php if (has_permission('topics', '', 'edit')) { 
                                                                // Load status from topic_master
                                                                $master_status = $this->Topic_master_model->get_by_topicid($topic->topicid)->status;
                                                            ?>
                                                                <div class="status-toggle">
                                                                    <div class="onoffswitch">
                                                                        <input type="checkbox" 
                                                                               data-switch-url="<?php echo admin_url('topics/toggle_topic_status'); ?>" 
                                                                               data-id="<?php echo $topic->topicid; ?>" 
                                                                               class="onoffswitch-checkbox" 
                                                                               id="topic_status_switch" 
                                                                               <?php echo ($master_status == 1 ? 'checked' : ''); ?>>
                                                                        <label class="onoffswitch-label" for="topic_status_switch"></label>
                            </div>
                        </div>
                                                            <?php } ?>
                            </div>
                        </div>
                    </div>

                                                <!-- Action Buttons Row -->
                                                <div class="topic-info-row action-buttons-row">
                                                    <div class="info-label">
                                                        <?php echo _l('action_buttons'); ?>:
                                                        <div class="action-buttons-controls">
                                                            <button type="button" class="btn btn-default btn-sm" id="load-history-buttons">
                                                                <i class="fa fa-history"></i> <?php echo _l('load_from_history'); ?>
                                                            </button>
                                                            <a href="<?php echo admin_url('topics/action_buttons/create'); ?>" 
                                                               class="btn btn-info btn-sm">
                                                                <i class="fa fa-plus"></i> <?php echo _l('add_action_button'); ?>
                                                            </a>
                        </div>
                    </div>
                                                    <div class="info-value action-buttons-container">
                                                        <?php $this->load->view('includes/topic_detail_action_buttons', [
                                                            'topic' => $topic,
                                                            'action_buttons' => $action_buttons
                                                        ]); ?>
                        </div>
                    </div>
                </div>
            </div>

                                        <!-- Progress Steps Section -->
                                        <h4 class="tw-font-semibold tw-text-lg tw-text-neutral-700 tw-mb-4">
                                            <?php //echo _l('progress_overview'); ?>
                                        </h4>
                                        
                                        <!-- Progress Steps Container -->
                                        <div id="progress-steps-container">
                                            <?php $this->load->view('topics/includes/progress_steps', ['topic_steps' => $topic_steps]); ?>
                    </div>
                </div>
                                </div>
                    </div>

                            <!-- Logs Tab -->
                            <div role="tabpanel" class="tab-pane" id="logs">
                                <?php $this->load->view('topics/partials/logs_tab', [
                                    'topic' => $topic,
                                    'topic_history' => $topic_history
                                ]); ?>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="row mtop20">
                            <div class="col-md-12">
                                <div class="btn-group">
                                    <a href="<?php echo admin_url('topics'); ?>" class="btn btn-default">
                                        <i class="fa fa-circle-left"></i> <?php echo _l('back'); ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Log Modal -->
<div class="modal fade log-modal" id="logModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                    </button>
                <h4 class="modal-title"><?php echo _l('log_details'); ?></h4>
            </div>
            <div class="modal-body">
                <div class="loading" style="display: none;">
                    <div class="tw-flex tw-justify-center">
                        <div class="spinner-border" role="status">
                            <span class="sr-only"><?php echo _l('loading'); ?></span>
                        </div>
                </div>
                </div>
                <div class="log-content"></div>
            </div>
        </div>
    </div>
</div>

<!-- Processed Data Modal -->
<div class="modal fade" id="processedDataModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
            </button>
                <h4 class="modal-title"><?php echo _l('processed_data_details'); ?></h4>
            </div>
            <div class="modal-body">
                <div class="loading" style="display: none;">
                    <div class="tw-flex tw-justify-center">
                        <div class="spinner-border" role="status">
                            <span class="sr-only"><?php echo _l('loading'); ?></span>
                        </div>
                    </div>
        </div>
                <div class="processed-data-content"></div>
            </div>
        </div>
    </div>
</div>

<?php init_tail(); ?>
<script>
$(function() {
    // Handle refresh button click
 
    // Initialize select picker with correct options
    $('#search_topics').selectpicker({
        liveSearch: true,
        virtualScroll: false,
        size: 10,
        noneSelectedText: "<?php echo _l('search_topics'); ?>",
        liveSearchPlaceholder: "<?php echo _l('type_to_search'); ?>",
        title: "<?php echo _l('search_topics'); ?>",
        sanitize: false,
    });

    // Setup ajax search using proper event
    $('#search_topics').on('shown.bs.select', function() {
        var $searchbox = $('.bs-searchbox input');
        var searchTimeout;
        var lastSearchValue = '';
        
        $searchbox.on('keyup paste', function(e) {
            var $this = $(this);
            
            setTimeout(function() {
                var q = $this.val();
                
                if (q === lastSearchValue) {
                    return;
                }
                lastSearchValue = q;
                
                clearTimeout(searchTimeout);
                
                searchTimeout = setTimeout(function() {
                    $.ajax({
                        url: admin_url + 'topics/search',
                        type: 'GET',
                        data: {
                            q: q,
                            limit: 20
                        },
                        success: function(response) {
                            try {
                                var data = JSON.parse(response);
                                var groupedData = {};
                                
                                // Group data by topicid
                                data.forEach(function(item) {
                                    if (!groupedData[item.topicid]) {
                                        groupedData[item.topicid] = {
                                            id: item.id,
                                            topicid: item.topicid,
                                            topictitle: item.topictitle,
                                            states: []
                                        };
                                    }
                                    groupedData[item.topicid].states.push({
                                        action_type: item.action_type_code,
                                        action_state: item.action_state_code
                                    });
                                });
                                
                                // Build options HTML
                                var options = '<option value=""></option>';
                                Object.values(groupedData).forEach(function(group) {
                                    var statesInfo = group.states
                                        .map(function(state) {
                                            return `${state.action_type}:${state.action_state}`;
                                        })
                                        .join(', ');
                                        
                                    options += `<option value="${group.id}" 
                                        data-subtext="${group.topicid}">
                                        ${group.topictitle || group.topicid}
                                  
                                    </option>`;
                                });
                                
                                // Update select and maintain search state
                                $('#search_topics')
                                    .html(options)
                                    .selectpicker('refresh');
                                    
                                var searchBox = $('.bs-searchbox input');
                                searchBox.val(q);
                                searchBox.focus();
                                
                                var length = searchBox.val().length;
                                searchBox[0].setSelectionRange(length, length);
                            } catch (e) {
                                console.error('Error parsing response:', e);
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Search failed:', error);
                        }
                    });
                }, 300);
            }, 0);
        });
    });

    // Handle selection change
    $('#search_topics').on('changed.bs.select', function() {
        var selectedId = $(this).val();
        if (selectedId) {
            window.location.href = admin_url + 'topics/detail/' + selectedId;
        }
    });

    // Handle processed data popup
    $(document).on('click', '.show-processed-data', function(e) {
        e.preventDefault();
        var btn = $(this);
        var modal = $('#processedDataModal');
        var loading = modal.find('.loading');
        var content = modal.find('.processed-data-content');
        
        loading.show();
        content.html('');
        
        $.ajax({
            url: admin_url + 'topics/get_processed_data',
            type: 'POST',
            data: {
                topicid: btn.data('topicid'),
                id: btn.data('id')
            },
            success: function(response) {
                loading.hide();
                content.html(response);
            },
            error: function() {
                loading.hide();
                content.html('<div class="alert alert-danger"><?php echo _l('failed_to_load_processed_data'); ?></div>');
            }
        });
    });

    // Function to refresh progress steps
    function refreshProgressSteps() {
        var topicId = $('#topicid').val();
        $.ajax({
            url: admin_url + 'topics/get_progress_steps',
            type: 'POST',
            data: { topicid: topicId },
            success: function(response) {
                $('#progress-steps-container').html(response);
            },
            error: function() {
                alert_float('danger', '<?php echo _l('failed_to_refresh_progress_steps'); ?>');
            }
        });
    }

    // Make refreshProgressSteps available globally
    window.refreshProgressSteps = refreshProgressSteps;

    // Call refreshProgressSteps when needed
    // refreshProgressSteps();

    // Handle status toggle switch
    $('body').on('change', 'input[data-switch-url]', function() {
        var switch_url = $(this).data('switch-url');
        var topicid = $(this).data('id');
        var $switch = $(this);

        $.post(switch_url, {
            topicid: topicid
        }).done(function(response) {
            var data = JSON.parse(response);
            
            if (data.success) {
                alert_float('success', data.message);
                if (data.refresh) {
                    // Optional: Refresh only specific parts instead of whole page
                    refreshProgressSteps();
                }
            } else {
                // Revert switch if failed
                $switch.prop('checked', !$switch.prop('checked'));
                alert_float('danger', data.message);
            }
        }).fail(function(data) {
            // Revert switch if failed
            $switch.prop('checked', !$switch.prop('checked'));
            alert_float('danger', 'Error updating status');
        });
    });
});

function formatLogContent(content) {
    try {
        const { jsonrepair } = JSONRepair;
        // console.log(jsonrepair);
        // Thử parse như JSON
        if (content.trim().startsWith('{') || content.trim().startsWith('[')) {
            const obj = JSON.parse(jsonrepair(content));
            return JSON.stringify(obj, null, 2);
        }
        
        // Nếu không phải JSON, thử format code thông thường
        return js_beautify(content, {
            indent_size: 2,
            max_preserve_newlines: 2,
            wrap_line_length: 80
        });
    } catch (e) {
        // Nếu không format được, trả về nguyên bản
        // console.log('Error formatting log content:', e);
        return content;
    }
}
</script>

<link href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism.min.css" rel="stylesheet" />
<link href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/plugins/line-numbers/prism-line-numbers.min.css" rel="stylesheet" />

<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/prism.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/plugins/line-numbers/prism-line-numbers.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/plugins/autoloader/prism-autoloader.min.js"></script>

<script>
<?php if (get_option('pusher_realtime_notifications') == 1) { ?>
<?php $pusher_options = hooks()->apply_filters('pusher_options', [['disableStats' => true]]);
        if (!isset($pusher_options['cluster']) && get_option('pusher_cluster') != '') {
            $pusher_options['cluster'] = get_option('pusher_cluster');
        }
        ?>
var pusher_options = <?php echo json_encode($pusher_options); ?>;
var pusher = new Pusher("<?php echo get_option('pusher_app_key'); ?>", pusher_options);
// Sử dụng pusher từ app settings của Perfex
$(function() {
    // Handle connection state changes
    pusher.connection.bind('state_change', function(states) {
        console.log('Pusher connection state changed:', states);
        var icon = document.getElementById('connection-status-icon');
        switch (states.current) {
            case 'connected':
                console.log('Pusher connected successfully');
                icon.style.color = 'green';
                icon.title = 'Connected';
                break;
            case 'connecting':
                console.log('Pusher connecting...');
                icon.style.color = 'orange';
                icon.title = 'Connecting';
                break;
            case 'disconnected':
            case 'unavailable':
                console.error('Pusher disconnected or unavailable');
                icon.style.color = 'red';
                icon.title = 'Disconnected';
                break;
            default:
                console.log('Unknown pusher state:', states.current);
                icon.style.color = 'black';
                icon.title = 'Unknown';
        }
    });

    // Handle connection error
    pusher.connection.bind('error', function(err) {
        console.error('Pusher connection error:', err);
        var icon = document.getElementById('connection-status-icon');
        icon.style.color = 'red';
        icon.title = 'Connection Error';
        document.getElementById('last-update-time').textContent = 'Connection Error';
    });

    // Bind vào channel notifications của user hiện tại
    var channel = pusher.subscribe('notifications-channel-<?php echo get_staff_user_id(); ?>');
    
    // Bind event notification
    channel.bind('notification', function(data) {
        console.log('Received notification:', data);
        
        try {
            if (data && data.additional_data) {
                // Format base message
                var baseMessage = `${langTopicUpdatedNotification} ${data.description.split('[')[0].trim()} [Type: ${
                    data.additional_data.action_type_name || data.additional_data.action_type_code || ''
                }, State: ${
                    data.additional_data.action_state_name || data.additional_data.action_state_code || ''
                }]`;

                // Check if notification is for current topic
                if (data.additional_data.topicid === '<?php echo $topic->topicid; ?>') {
                    console.log('Matched topic notification for topicid:', '<?php echo $topic->topicid; ?>');
                    
                    // Refresh data nếu là topic hiện tại
                    refreshTopicData();
                    updateLastUpdateTime();
                    
                    console.log('Showing notification message:', baseMessage);
                    
                    // Show notification without link
                    AlertManager.addAlert("info", baseMessage, 5000);
                    
                    // Update connection status
                    var icon = document.getElementById('connection-status-icon');
                    icon.style.color = 'green';
                    icon.title = 'Connected - Last update: ' + moment(data.date).format('DD/MM/YYYY HH:mm:ss');
                } else {
                    console.log('Notification for different topic:', data.link);
                
                    // Add link to message for different topics with full admin URL
                    var fullLink = admin_url + data.link; // Use admin_url from Perfex
                    var messageWithLink = `${baseMessage} - <a href="${fullLink}" class="tw-text-primary hover:tw-text-primary-600">Click to view</a>`;
              
                    console.log('Showing notification message with link:', messageWithLink);
                    
                    // Show notification with link
                    AlertManager.addAlert("info", messageWithLink, 7000);
                }
            } else {
                console.log('Invalid notification data structure:', data);
            }
        } catch (e) {
            console.error('Error processing notification:', e);
            console.error('Raw notification data:', data);
        }
    });
});
<?php } else { ?>
// Pusher không khả dụng
$(function() {
    var icon = document.getElementById('connection-status-icon');
    icon.style.color = 'red';
    icon.title = 'Pusher Not Available';
    document.getElementById('last-update-time').textContent = 'Real-time updates not available';
});
<?php } ?>

// Function to update the last update time
function updateLastUpdateTime() {
    var now = new Date();
    var formattedTime = moment(now).format('DD/MM/YYYY HH:mm:ss');
    console.log('Updating last update time to:', formattedTime);
    document.getElementById('last-update-time').textContent = formattedTime;
}

// Function to refresh topic data
function refreshTopicData() {
    console.log('Refreshing topic data...');
    
    // Refresh history table
    $('#refresh-history').trigger('click');
    console.log('Triggered history refresh');
    
    // Refresh progress steps nếu có
    if (typeof refreshProgressSteps === 'function') {
        console.log('Refreshing progress steps...');
        refreshProgressSteps();
    } else {
        console.log('refreshProgressSteps function not available');
    }
}

function handleActionButton(button) {
    // Hiển thị modal để chọn workflow
    var modal = $('#select_workflow_modal');
    
    // Lưu thông tin button vào data của modal để sử dụng sau
    modal.data('button', button);
    
    // Load danh sách workflow dựa trên action type và state
    $.get(admin_url + 'topics/get_workflows', {
        action_type: button.target_action_type,
        action_state: button.target_action_state
    }, function(response) {
        if (response.success) {
            // Populate workflow dropdown
            var select = modal.find('#workflow_id');
            select.empty();
            response.workflows.forEach(function(workflow) {
                select.append($('<option>', {
                    value: workflow.id,
                    text: workflow.name
                }));
            });
            
            modal.modal('show');
        }
    });
}
</script>

<script>
document.getElementById('toggle-topic-id').addEventListener('click', function(e) {
    e.preventDefault();
    var topicIdDisplay = document.getElementById('topic-id-display');
    if (topicIdDisplay.style.display === 'none') {
        topicIdDisplay.style.display = 'inline';
        this.innerHTML = '<i class="fa fa-eye-slash"></i> <?php echo _l('hide_topic_id'); ?>';
    } else {
        topicIdDisplay.style.display = 'none';
        this.innerHTML = '<i class="fa fa-eye"></i> <?php echo _l('show_topic_id'); ?>';
    }
});
</script>
