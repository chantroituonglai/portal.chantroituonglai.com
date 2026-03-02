<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<!-- Action Buttons Section -->
<div class="row">
    <div class="col-md-12">
        <!-- Active Buttons -->
        <div class="btn-group">
            <div id="dynamic-action-buttons">
                <?php 
                // Sắp xếp action buttons theo trường order
                usort($action_buttons, function($a, $b) {
                    $a_order = isset($a['order']) ? intval($a['order']) : 0;
                    $b_order = isset($b['order']) ? intval($b['order']) : 0;
                    return $a_order - $b_order;
                });
                
                foreach ($action_buttons as $button) { 
                    // Check if button should be ignored based on current type and state
                    $ignore_types = !empty($button['ignore_types']) ? json_decode($button['ignore_types'], true) : [];
                    $ignore_states = !empty($button['ignore_states']) ? json_decode($button['ignore_states'], true) : [];
                    
                    // Ensure arrays are valid
                    $ignore_types = is_array($ignore_types) ? $ignore_types : [];
                    $ignore_states = is_array($ignore_states) ? $ignore_states : [];
                    
                    // Debug info
                    log_message('debug', 'Button: ' . $button['name']);
                    log_message('debug', 'Ignore Types: ' . json_encode($ignore_types));
                    log_message('debug', 'Ignore States: ' . json_encode($ignore_states));
                ?>
                    <button type="button" 
                            class="btn btn-<?php echo html_escape($button['button_type']); ?> topic-action-button"
                            data-workflow-id="<?php echo html_escape($button['workflow_id']); ?>"
                            data-target-type="<?php echo html_escape($button['target_action_type']); ?>"
                            data-target-state="<?php echo html_escape($button['target_action_state']); ?>"
                            data-topic-id="<?php echo html_escape($topic->id); ?>"
                            data-button-id="<?php echo html_escape($button['id']); ?>"
                            data-ignore-types='<?php echo html_escape(json_encode($ignore_types)); ?>'
                            data-ignore-states='<?php echo html_escape(json_encode($ignore_states)); ?>'
                            data-order="<?php echo html_escape($button['order'] ?? 0); ?>"
                            data-action-command="<?php echo html_escape($button['action_command'] ?? ''); ?>">
                        <i class="fa fa-play-circle"></i> <?php echo html_escape($button['name']); ?>
                    </button>
                <?php } ?>
            </div>
        </div>
        
        <!-- Deactivated Buttons Section -->
        <div class="mtop10" id="deactivated-buttons-section" style="display:none;">
            <hr>
            <p class="text-muted"><i class="fa fa-info-circle"></i> <?php echo _l('deactivated_buttons'); ?>:</p>
            <div class="btn-group">
                <div id="deactivated-action-buttons">
                    <!-- Will be populated by JavaScript -->
                </div>
            </div>
        </div>
    </div>
</div>



<!-- Execution Results Section -->
<div class="row mtop15">
    <div class="col-md-12">
        <div class="panel_s">
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-12">
                        <h4 class="pull-left">
                            <?php echo _l('execution_results'); ?>
                            <i class="fa fa-question-circle" data-toggle="tooltip" 
                               title="<?php echo _l('execution_results_help'); ?>"></i>
                        </h4>
                        <div class="pull-right">
                            <button type="button" class="btn btn-default btn-sm" id="clear-results">
                                <i class="fa fa-trash"></i> <?php echo _l('clear'); ?>
                            </button>
                        </div>
                    </div>
                </div>
                <hr class="mtop5 mbot10" />
                <div id="execution-results" class="mtop10">
                    <!-- Results will be populated here -->
                    <div class="execution-results-placeholder text-muted">
                        <?php echo _l('no_execution_results'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Action History Modal -->
<div class="modal fade" id="action-history-modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><?php echo _l('select_processed_values'); ?></h4>
            </div>
            <div class="modal-body">
                <div id="processed-values">
                    <div class="processed-values-list">
                        <!-- Checkboxes will be populated dynamically -->
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
// Wrap trong init function của Perfex
document.addEventListener("DOMContentLoaded", function(event) {
    init_js_topic_detail_buttons(); 

    // Add click handler for expandable content
    $(document).on('click', '.expandable-content', function() {
        $(this).toggleClass('expanded');
    });
});

function init_js_topic_detail_buttons() {
    console.log('init_js_topic_detail_buttons');
    var historyData = null;
    var isExecuting = false;

    // Remove any existing handlers first to prevent duplicates
    $(document).off('click', '.topic-action-button');
    
    function initTopicActionButtons() {
        var isExecuting = false;
        var historyData = null;

        // Load history data khi khởi tạo
        var topicId = '<?php echo $topic->topicid; ?>';
        $.ajax({
            url: admin_url + 'topics/get_topic_history_ajax',
            type: 'POST',
            data: { topicid: topicId },
            success: function(response) {
                try {
                    var data = JSON.parse(response);
                    if (data.success) {
                        historyData = data.data;
                        updateButtonsFromHistory(historyData);
                    } else {
                        // Nếu không có history hoặc lỗi, ẩn tất cả các nút
                        $('#dynamic-action-buttons button').hide();
                        alert_float('danger', data.message || '<?php echo _l('error_loading_history'); ?>');
                    }
                } catch (e) {
                    console.error(e);
                    // Nếu có lỗi parsing, ẩn tất cả các nút
                    $('#dynamic-action-buttons button').hide();
                    alert_float('danger', '<?php echo _l('error_processing_response'); ?>');
                }
            },
            error: function() {
                // Nếu có lỗi AJAX, ẩn tất cả các nút
                $('#dynamic-action-buttons button').hide();
                alert_float('danger', '<?php echo _l('error_loading_history'); ?>');
            }
        });

        // Handle topic action button clicks
        $(document).on('click', '.topic-action-button', function(e) {
            e.preventDefault();
            if (isExecuting) return;
            
            isExecuting = true;
            var btn = $(this);
            
            // Add disabled and loading state
            btn.addClass('disabled executing')
               .prop('disabled', true)
               .css('cursor', 'not-allowed');
            
            var originalText = btn.html();
            btn.html('<i class="fa fa-spinner fa-spin"></i> ' + btn.text());
            
            var workflowData = {
                workflow_id: btn.data('workflow-id'),
                topic_id: btn.data('topic-id'),
                target_type: btn.data('target-type'),
                target_state: btn.data('target-state'),
                button_id: btn.data('button-id'),
                action_command: btn.data('action-command'),
                selected_values: []
            };
            
            // Reset button state function
            function resetButtonState() {
                btn.removeClass('disabled executing')
                   .prop('disabled', false)
                   .css('cursor', 'pointer')
                   .html(originalText);
                isExecuting = false;
            }

            if (!historyData) {
                executeWorkflow(workflowData);
                return;
            }

            // Find matching history entry
            var matchingHistory = historyData.find(function(history) {
                return history.action_type_code === workflowData.target_type && 
                       history.action_state_code === workflowData.target_state;
            });

            if (matchingHistory) {
                // Load processed values for the matching history
                $.ajax({
                    url: admin_url + 'topics/get_log_data',
                    type: 'POST',
                    data: {
                        topicid: workflowData.topic_id,
                        id: matchingHistory.id
                    },
                    success: function(response) {
                        try {
                            var data = JSON.parse(response);
                            if (data.success) {
                                populateProcessedValues(data.data, btn, workflowData);
                                $('#action-history-modal').modal('show');
                            }
                        } catch (e) {
                            console.error(e);
                            alert_float('danger', '<?php echo _l('error_loading_processed_values'); ?>');
                        }
                    },
                    error: function() {
                        alert_float('danger', '<?php echo _l('error_loading_processed_values'); ?>');
                    },
                    complete: resetButtonState
                });
            } else {
                executeWorkflow(workflowData);
            }
        });
    }

    // Initialize buttons
    initTopicActionButtons();

    // Make initTopicActionButtons available globally
    window.initTopicActionButtons = initTopicActionButtons;

    /**
     * Execute Workflow. Remomber to pass all data in workflowData
     *  process_topic_action(
            $workflow_data['topic_id'], 
            [
                'action_type' => $workflow_data['target_type'],
                'workflow_id' => $workflow_data['workflow_id'],
                'target_type' => $workflow_data['target_type'],
                'target_state' => $workflow_data['target_state'],
                'selected_page_id' => $workflow_data['selected_page_id'] ?? null,
                'selected_page_internal_id' => $workflow_data['selected_page_internal_id'] ?? null,
                'post_type' => $workflow_data['post_type'] ?? null,
                'from_selection' => $workflow_data['from_selection'] ?? false,
                // Add any other fields that are part of the workflow data
            ]
        );
     * @param workflowData
     */
    function executeWorkflow(workflowData) {
        // Show loading in results area
        showExecutionLoading();
        
        // Lưu lại button đang thực thi
        var executingButton = $('.topic-action-button[data-button-id="' + workflowData.button_id + '"]');
        
        // Prepare data for request
        var requestData = {...workflowData}; // Clone workflowData
        
        if (workflowData.from_selection) {
            $('.execution-result-item').find('.submit-social-selection').hide();
            $('.execution-result-item').find('.fanpage-select').prop('disabled', true);
            $('.execution-result-item').find('.post-type-select').prop('disabled', true);
            
            // Add audit fields if present
            if (workflowData.audit_type) {
                requestData.audit_type = workflowData.audit_type;
        }
            if (workflowData.audit_step) {
                requestData.audit_step = workflowData.audit_step;
            }
        }

        return $.ajax({
            url: admin_url + 'topics/execute_workflow',
            type: 'POST',
            data: requestData,
            dataType: 'json',
            success: function(response) {
                console.log('executeWorkflow response', response);
                try {
                    if (response.success) {
                        alert_float('success', response.message);
                        displayExecutionResult(response, workflowData);
                        if (response.data && response.data.reload) {
                            location.reload();
                        }
                    } else {
                        if (response.prompt_action === 'configure_button') {
                            showConfigurationModal(response.current_data, response.missing_fields);
                        } else {
                            alert_float('danger', response.message);
                            displayExecutionError(response);
                        }
                    }
                } catch (e) {
                    console.log('Parse error:', e);
                    var errorMessage = {
                        success: false,
                        message: '<?php echo _l('error_processing_response'); ?>',
                        error_details: e.message,
                        stack: e.stack
                    };
                    alert_float('danger', errorMessage.message);
                    displayExecutionError(errorMessage);
                }
            },
            error: function(xhr, status, error) {
                console.log('AJAX error:', {xhr, status, error});
                
                var errorMessage = {
                    success: false,
                    message: '<?php echo _l('error_executing_workflow'); ?>',
                    data: {
                        status_code: xhr.status,
                        status_text: xhr.statusText,
                        response_text: xhr.responseText,
                        error_type: status,
                        error_details: error
                    }
                };

                try {
                    // Thử parse response text nếu là JSON
                    var jsonResponse = JSON.parse(xhr.responseText);
                    if (jsonResponse.message) {
                        errorMessage.message = jsonResponse.message;
                    }
                    if (jsonResponse.data) {
                        errorMessage.data = {...errorMessage.data, ...jsonResponse.data};
                    }
                } catch (e) {
                    // Nếu không phải JSON, sử dụng responseText gốc
                    errorMessage.data.raw_response = xhr.responseText;
                }

                alert_float('danger', errorMessage.message);
                displayExecutionError(errorMessage);
            },
            complete: function(response) {
                if (executingButton.length) {
                    executingButton.removeClass('disabled executing')
                        .prop('disabled', false)
                        .css('cursor', 'pointer')
                        .find('.fa-spinner').remove();
                }
                // Check if we need to clear dynamic-action-buttons
                if (response && response.responseJSON && response.responseJSON.data && response.responseJSON.data.clear_button) {
                    $('.topic-action-button').removeClass('disabled executing')
                        .prop('disabled', false)
                        .css('cursor', 'pointer')
                        .find('.fa-spinner').remove();
                }
                isExecuting = false;
            }
        });
    }

    // Handle Load History button click
    $('#load-history-buttons').on('click', function() {
        console.log('load-history-buttons');
        var topicId = '<?php echo $topic->topicid; ?>';
        console.log('load-history-buttons topicId', topicId);
        // Load history data
        $.ajax({
            url: admin_url + 'topics/get_topic_history_ajax',
            type: 'POST',
            data: { topicid: topicId },
            success: function(response) {
                try {
                    var data = JSON.parse(response);
                    if (data.success) {
                        historyData = data.data;
                        updateButtonsFromHistory(historyData);
                    } else {
                        alert_float('danger', data.message || '<?php echo _l('error_loading_history'); ?>');
                    }
                } catch (e) {
                    console.error(e);
                    alert_float('danger', '<?php echo _l('error_processing_response'); ?>');
                }
            },
            error: function() {
                alert_float('danger', '<?php echo _l('error_loading_history'); ?>');
            }
        });
    });

    // Handle Execute button click in modal
    $('.topic-action-button').off('click').on('click', function() {
        console.log('execute-action', isExecuting);
        if (isExecuting) return;
        
        isExecuting = true;
        var selectedValues = [];
        $('.processed-value-checkbox:checked').each(function() {
            selectedValues.push($(this).val());
        });
        
        var clickedButton = $(this); // Lấy nút được bấm
        var workflowData = {
            workflow_id: clickedButton.data('workflow-id'),
            topic_id: clickedButton.data('topic-id'),
            target_type: clickedButton.data('target-type'),
            target_state: clickedButton.data('target-state'),
            selected_values: selectedValues,
            button_id: clickedButton.data('button-id'),
            action_command: clickedButton.data('action-command'),
            from_history: true
        };
        executeWorkflow(workflowData);
        
        $('#action-history-modal').modal('hide');
    });

    function showExecutionLoading() {
        var loadingHtml = `
            <div class="execution-result-item loading">
                <div class="execution-loading">
                    <i class="fa fa-spinner fa-spin"></i> <?php echo _l('executing_workflow'); ?>
                </div>
            </div>
        `;
        $('#execution-results .execution-results-placeholder').hide();
        $('#execution-results').prepend(loadingHtml);
    }

    /**
     * Display Execution Error
     * @param errorData
     */
    function displayExecutionError(errorData) {
        if (errorData.data && errorData.data.needs_controller) {
            // Store current workflow data for retry
            var currentWorkflowData = window.lastWorkflowData;
            
            // Show controller selection modal
            var modalHtml = `
                <div class="modal fade" id="controller-selection-modal">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                                <h4 class="modal-title"><?php echo _l('select_controller'); ?></h4>
                            </div>
                            <div class="modal-body">
                                <div class="form-group">
                                    <label for="controller_id"><?php echo _l('select_controller'); ?></label>
                                    <select class="form-control" id="controller_id" name="controller_id">
                                        <option value=""><?php echo _l('select_controller'); ?></option>
                                    </select>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
                                <button type="button" class="btn btn-info" id="add-to-controller"><?php echo _l('add_to_controller'); ?></button>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            // Remove existing modal if any
            $('#controller-selection-modal').remove();
            
            // Add modal to body
            $('body').append(modalHtml);

            // Load available controllers
            $.get(admin_url + 'topics/get_available_controllers', {
                topic_id: errorData.data.topic_id
            }, function(response) {
                if (response.success) {
                    var select = $('#controller_id');
                    response.data.controllers.forEach(function(controller) {
                        select.append(new Option(controller.site, controller.id));
                    });
                }
            }, 'json');

            // Handle add to controller
            $('#add-to-controller').click(function() {
                var controller_id = $('#controller_id').val();
                if (!controller_id) {
                    alert_float('warning', '<?php echo _l('please_select_controller'); ?>');
                    return;
                }

                $.post(admin_url + 'topics/add_topic_to_controller', {
                    topic_id: errorData.data.topic_id,
                    controller_id: controller_id
                }, function(response) {
                    if (response.success) {
                        alert_float('success', response.message);
                        $('#controller-selection-modal').modal('hide');
                        // Retry the workflow execution
                        if (currentWorkflowData) {
                            executeWorkflow(currentWorkflowData);
                        }
                    } else {
                        alert_float('danger', response.message);
                    }
                }, 'json');
            });

            // Show modal
            $('#controller-selection-modal').modal('show');
        } else {
            // Display normal error
        var errorDetails = '';
        
        if (errorData.data) {
            if (errorData.data.file) {
                errorDetails += `<div class="error-file">File: ${errorData.data.file}</div>`;
            }
            if (errorData.data.line) {
                errorDetails += `<div class="error-line">Line: ${errorData.data.line}</div>`;
            }
            if (errorData.data.trace) {
                errorDetails += `
                    <div class="error-trace">
                        <strong>Stack Trace:</strong>
                        <pre class="execution-data">${errorData.data.trace}</pre>
                    </div>
                `;
            }
        }

        var errorHtml = `
            <div class="execution-result-item error">
                <div class="execution-timestamp">
                    ${moment().format('DD/MM/YYYY HH:mm:ss')}
                </div>
                <div class="execution-message">
                    <strong>Error:</strong> ${errorData.message}
                </div>
                ${errorDetails}
                ${errorData.data && !errorData.data.file ? `
                    <div class="execution-details">
                        <strong>Details:</strong>
                        <pre class="execution-data">${JSON.stringify(errorData.data, null, 2)}</pre>
                    </div>
                ` : ''}
            </div>
        `;

        $('#execution-results .execution-results-placeholder').hide();
        $('#execution-results').prepend(errorHtml);
        }
    }

    /**
     * Display Execution Result
     * @param data
     * @param workflowData
     */
    function displayExecutionResult(data, workflowData) {
        console.log('displayExecutionResult', {
            data: data,
            workflowData: workflowData,
            target_type_state: workflowData.target_type + '_' + workflowData.target_state
        });
        
        // Remove loading indicator
        $('.execution-result-item.loading').remove();
        console.log('displayExecutionResult', workflowData.target_type + '_' + workflowData.target_state);

        // Xử lý riêng cho từng target_type và target_state
        switch(workflowData.target_type + '_' + workflowData.target_state) {
            case 'ExecutionTag_ExecAudit_ExecutionTag_ExecAudit_SocialAuditCompleted':
                console.log('Handling Social Media Post');
                displaySocialMediaResult(data, workflowData);
                break;
            
            case 'ExecutionTag_ExecWriting_ExecutionTag_ExecWriting_Complete':
                console.log('Handling WordPress Post');
                displayWordPressResult(data, workflowData);
                break;

            case 'ExecutionTag_ExecWriting_ExecutionTag_ExecWriting_PostCreated':
                console.log('Handling WordPress Post Creation Options');
                displayWordPressResult(data, workflowData);
                break;
            case 'ImageGenerateToggle_GenImgCompleted':
                console.log('Handling Image Generation');
                displayImageGenerationResult(data, workflowData);
                break;
            case 'init_success':
                if (workflowData.action_command === 'TopicComposer') {
                    console.log('Handling Topic Composer | audit_step', data);
                    if (data.data.audit_step == 1 || data.data.audit_step == 2) {
                        displayTopicComposerResult(data, workflowData);
                        
                    } 
                }  // Kiểm tra action_command trước tiên
                else {
                    console.log('Handling Default Result');
                    displayDefaultResult(data, workflowData);
                }
                break;
            case 'BuildPostStructure_BuildPostStructure_B_Begin':
                 if (workflowData.action_command === 'WRITE_DRAFT') {
                    console.log('Handling Draft Writing based on action_command');
                    displayDraftWritingResult(data, workflowData);
                }  else {
                    console.log('Handling Default Result');
                    displayDefaultResult(data, workflowData);
                }
                break;
            default:
                console.log('Handling Default Result');
                displayDefaultResult(data, workflowData);
        }
    }

   

    function prependExecutionResult(html) {
        $('.execution-results-placeholder').remove();
        $('#execution-results').prepend(html);
        
        // Limit the number of results shown
        var maxResults = 5;
        $('.execution-result-item').slice(maxResults).remove();
    }

    // Clear results button handler
    $('#clear-results').on('click', function() {
        $('#execution-results').html(`
            <div class="execution-results-placeholder text-muted">
                <?php echo _l('no_execution_results'); ?>
            </div>
        `);

        // Nhả tất cả các nút topic-action-button
        $('.topic-action-button').removeClass('disabled executing')
            .prop('disabled', false)
            .css('cursor', 'pointer')
            .find('.fa-spinner').remove();
    });

    function showConfigurationModal(currentData, missingFields) {
        var modalHtml = `
            <div class="modal fade" id="configure-button-modal">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                            <h4 class="modal-title"><?php echo _l('configure_action_button'); ?></h4>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-info">
                                <?php echo _l('please_complete_missing_information'); ?>
                            </div>
                            <form id="button-config-form">
                                ${missingFields.map(field => `
                                    <div class="form-group">
                                        <label>${field.replace('_', ' ').toUpperCase()}</label>
                                        <input type="text" 
                                               name="${field}" 
                                               class="form-control" 
                                               value="${currentData[field] || ''}"
                                               required>
                                    </div>
                                `).join('')}
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default" data-dismiss="modal">
                                <?php echo _l('close'); ?>
                            </button>
                            <button type="button" class="btn btn-primary" onclick="saveAndExecute()">
                                <?php echo _l('save_and_execute'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Thêm modal vào DOM và hiển thị
        $('body').append(modalHtml);
        $('#configure-button-modal').modal('show');
    }

    function saveAndExecute() {
        var formData = $('#button-config-form').serializeArray();
        var updatedData = {...workflowData};
        
        formData.forEach(function(item) {
            updatedData[item.name] = item.value;
        });

        $('#configure-button-modal').modal('hide');
        executeWorkflow(updatedData);
    }

    function populateProcessedValues(logData, button, workflowData) {
        var container = $('.processed-values-list');
        container.empty();
        
        try {
            var processedData = JSON.parse(logData);
            Object.keys(processedData).forEach(function(key) {
                container.append(`
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" class="processed-value-checkbox" value="${key}">
                            ${key}: ${JSON.stringify(processedData[key])}
                        </label>
                    </div>
                `);
            });
            
            // Store active button and workflow data
            $('.topic-action-button').removeClass('active');
            button.addClass('active');
            button.data('workflow-data', workflowData);
            
        } catch (e) {
            console.error('Error parsing log data:', e);
            container.append('<p class="text-danger"><?php echo _l('error_parsing_log_data'); ?></p>');
        }
    }

    /**
     * Bind events for post selection form
     * @param {object} workflowData Current workflow data
     * @param {boolean} hasTopItems Whether topic has top items
     * @param {boolean} hasMoBaiKetLuan Whether topic has mo bai or ket luan
     */
    function bindPostSelectionEvents(workflowData, hasTopItems, hasMoBaiKetLuan) {
        $('.submit-post-selection').off('click').on('click', function() {
            var btn = $(this);
            var selectedOption = $('.post-type-select:checked').val();
            
            if (!selectedOption) {
                alert_float('warning', '<?php echo _l('please_select_option'); ?>');
                return;
            }

            // Determine audit type and step based on selection and hasTopItems
            var auditType = '';
            var auditStep = 1;
            
            switch(selectedOption) {
                case 'audit_post':
                    auditType = 'PostAudit';
                    auditStep = hasTopItems ? 1 : 2;
                    break;
                    
                case 'write_content':
                    auditType = 'PostAudit';
                    auditStep = hasTopItems ? 2 : 1;
                    break;
                    
                case 'write_social':
                    auditType = 'SocialAudit';
                    auditStep = hasMoBaiKetLuan ? 2 : 1;
                    break;
            }

            // Check if controller is required (step 2) but not assigned
            console.log('auditStep', auditStep);
            console.log('.table-controller-info', $('.table-controller-info').length);
            if (auditStep === 1 && !$('.table-controller-info').length) {
                alert_float('warning', '<?php echo _l('topic_needs_controller'); ?>');
                return;
            }

            // Get controller data from the table
            var controllerData = {};
            $('.table-controller-info tr').each(function() {
                var key = $(this).find('td:first strong').text().toLowerCase().replace(/[^a-z0-9]/g, '_');
                var value = $(this).find('td:last').text().trim();
                if ($(this).find('.expandable-content').length) {
                    value = $(this).find('.expandable-content').html().trim();
                }
                if (key && value !== '-') {
                    controllerData[key] = value;
                }
            });

            // Get WordPress post ID if available from current data
            var wordpressPostId = null;
            if (window.lastWordPressData && window.lastWordPressData.data && window.lastWordPressData.data.options && window.lastWordPressData.data.options.id) {
                wordpressPostId = window.lastWordPressData.data.options.id;
            }
            
            // Store workflow data for potential retry
            window.lastWorkflowData = {
                workflow_id: btn.data('workflow-id'),
                topic_id: btn.data('topic-id'),
                target_type: btn.data('target-type'),
                target_state: btn.data('target-state'),
                button_id: btn.data('button-id'),
                action_command: btn.data('action-command'),
                selected_option: selectedOption,
                audit_type: auditType,
                audit_step: auditStep,
                from_selection: true,
                wordpress_post_id: wordpressPostId, // Add WordPress post ID
                // Add controller data
                controller_site: controllerData.site,
                controller_platform: controllerData.platform,
                controller_blog_id: controllerData.blog_id,
                controller_logo_url: controllerData.logo_url,
                controller_project_id: controllerData.project_id,
                controller_page_mapping: controllerData.page_mapping,
                controller_slogan: controllerData.slogan,
                controller_writing_style: controllerData.writing_style,
                controller_action_1: controllerData.action_1,
                controller_action_2: controllerData.action_2
            };
            
            // Disable form while processing
            btn.prop('disabled', true);
            $('.post-type-select').prop('disabled', true);
            
            executeWorkflow(window.lastWorkflowData);
        });

        // Enable submit button when an option is selected
        $('.post-type-select').on('change', function() {
            $('.submit-post-selection').prop('disabled', false);
        });

        // Initially disable submit button
        $('.submit-post-selection').prop('disabled', true);
    }

    <?php 
    ob_start();
    $this->load->view('includes/topic_detail_action_buttons_display_script');
    $topic_detail_action_buttons_display_script = ob_get_clean();
    // Loại bỏ thẻ <script> và </script>
    $topic_detail_action_buttons_display_script = str_replace(['<script>', '</script>'], '', $topic_detail_action_buttons_display_script);
    echo $topic_detail_action_buttons_display_script;
    ?>
}

<?php 

ob_start();
$this->load->view('includes/topic_detail_action_buttons_ext_script');
$topic_detail_action_buttons_ext_script = ob_get_clean();
// Loại bỏ thẻ <script> và </script>
$topic_detail_action_buttons_ext_script = str_replace(['<script>', '</script>'], '', $topic_detail_action_buttons_ext_script);
echo $topic_detail_action_buttons_ext_script;

?>

function openImagePreview(url, title) {
    var modal = `
        <div class="modal fade" id="imagePreviewModal">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                        <h4 class="modal-title">${title}</h4>
                    </div>
                    <div class="modal-body">
                        <img src="${url}" class="img-responsive" alt="${title}" />
                    </div>
                </div>
            </div>
        </div>
    `;
    
    $('#imagePreviewModal').remove();
    $('body').append(modal);
    $('#imagePreviewModal').modal('show');
}

function copyImageUrl(url) {
    navigator.clipboard.writeText(url).then(() => {
        alert_float('success', '<?php echo _l('url_copied_to_clipboard'); ?>');
    }).catch(() => {
        alert_float('danger', '<?php echo _l('failed_to_copy_url'); ?>');
    });
}
</script>


<script>
// Define text constants for JS
const active_text = '<?php echo _l('active'); ?>';
const inactive_text = '<?php echo _l('inactive'); ?>';
const select_text = '<?php echo _l('select'); ?>';
const meta_description_text = '<?php echo _l('meta_description'); ?>';
const meta_keywords_text = '<?php echo _l('meta_keywords'); ?>';
const social_post_text = '<?php echo _l('social_post'); ?>';
const meta_footer_text = '<?php echo _l('meta_footer'); ?>';
const view_full_post_text = '<?php echo _l('view_full_post'); ?>';
const category_text = '<?php echo _l('category'); ?>';
</script>
<script src="<?php echo module_dir_url('topics', 'assets/js/topics/topic_actions.js?ver='.time()); ?>"></script>

<style>
/* Topic Composer Styles */
#topic-composer-form .panel {
    margin-bottom: 25px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.12);
}

#topic-composer-form .panel-heading {
    padding: 15px;
}

#topic-composer-form .panel-title {
    margin: 0;
    line-height: 34px;
}

#topic-composer-form .form-group {
    margin-bottom: 20px;
}

#topic-composer-form label {
    font-weight: 600;
    color: #333;
}

#topic-composer-form .thumbnail {
    margin-bottom: 15px;
}

#topic-composer-form .thumbnail img {
    max-height: 150px;
    object-fit: cover;
}

#topic-composer-form .caption {
    padding: 10px;
    text-align: center;
}

#topic-composer-form .selectpicker {
    width: 100%;
}

.bootstrap-select .selected .text {
    color: #333;
}
</style> 

<style>
.modal-fullscreen {
    width: 95%;
    margin: 30px auto;
}
.modal-fullscreen .modal-content {
    min-height: calc(100vh - 60px);
}
.content-section {
    margin-bottom: 20px;
    border: 1px solid #ddd;
    border-radius: 4px;
}
.section-header {
    padding: 10px;
    background: #f8f9fa;
    border-bottom: 1px solid #ddd;
    display: flex;
    align-items: center;
}
.drag-handle {
    cursor: move;
    padding: 0 10px;
    color: #666;
}
.section-title {
    flex: 1;
    margin: 0 10px;
}
.editor-container {
    padding: 15px;
    min-height: 200px;
}
.section-actions {
    padding: 0 10px;
}
</style>

