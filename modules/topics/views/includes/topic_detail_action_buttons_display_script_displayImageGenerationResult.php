<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<script> 
/**
 * Display Image Generation Result
 * @param data
 * @param workflowData
 */
window.displayImageGenerationResult = function(data, workflowData) {
    console.log("Starting displayImageGenerationResult with:", { data, workflowData });
        var timestamp = moment().format('DD/MM/YYYY HH:mm:ss');

    // Check if topic needs controller
    if (data.data && data.data.needs_controller) {
        var resultHtml = `
            <div class="execution-result-item">
                <div class="execution-timestamp text-muted">
                    <i class="fa fa-clock-o"></i> ${timestamp}
                </div>
                <div class="execution-status">
                    <i class="fa fa-exclamation-circle text-warning"></i> 
                    <strong><?php echo _l('topic_needs_controller'); ?></strong>
                </div>
                <div class="execution-details mtop10">
                    <div class="panel panel-warning">
                        <div class="panel-heading">
                            <h3 class="panel-title">
                                <?php echo _l('controller_info'); ?>
                                <button type="button" 
                                        onclick="showControllerSearchModal(${workflowData.topic_id})" 
                                        class="btn btn-info btn-xs pull-right">
                                    <i class="fa fa-plus"></i> <?php echo _l('add_controller'); ?>
                                </button>
                            </h3>
                        </div>
                        <div class="panel-body">
                            <div class="alert alert-warning">
                                <i class="fa fa-exclamation-triangle"></i>
                                <?php echo _l('no_controller_assigned'); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        prependExecutionResult(resultHtml);
        return;
    }

        // Step 1: Show loading and start polling
    if (data.data && data.data.audit_step === 1) {
        console.log("Displaying Step 1 loading state");
        
        // Try to get workflow info from response_text if data.response is null
        if (!data.data.response && data.data.response_text) {
            try {
                const responseData = JSON.parse(data.data.response_text);
                if (responseData.data && responseData.data.workflow_id && responseData.data.execution_id) {
                    data.data.workflow_id = responseData.data.workflow_id;
                    data.data.execution_id = responseData.data.execution_id;
                    data.data.needs_polling = true;
                }
            } catch (e) {
                console.error('Error parsing response_text:', e);
            }
        }

            var loadingHtml = `
                <div class="execution-result-item" id="image-generation-result">
                    <div class="execution-timestamp text-muted">
                        <i class="fa fa-clock-o"></i> ${timestamp}
                    </div>
                    <div class="execution-status">
                        <i class="fa fa-spinner fa-spin text-info"></i> 
                        <strong>${data.message || '<?php echo _l('generating_images'); ?>'}</strong>
                    </div>
                    <div class="execution-details mtop10">
                        <div class="progress">
                            <div class="progress-bar progress-bar-striped active" 
                                 role="progressbar" 
                                 style="width: 100%">
                                <span class="status-text">${data.data.status || 'running'}</span>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            prependExecutionResult(loadingHtml);

        // Check if we have workflow info to start polling
        if (data.data.workflow_id && data.data.execution_id) {
                pollWorkflowStatus(
                    data.data.workflow_id, 
                    data.data.execution_id,
                workflowData,
                function(imageData) {
                    // Callback when polling completes successfully
                    displayImageSelectionStep(imageData, workflowData);
                }
            );
        } else {
            // Show error if no workflow info available
            $('#image-generation-result .execution-status')
                .html('<i class="fa fa-times text-danger"></i> <?php echo _l('workflow_info_missing'); ?>');
            }
            return;
        }
        
    // Step 2: Display final result (after image selection)
    if (data.success && data.data && data.data.audit_step === 2) {
            var resultHtml = `
                <div class="execution-result-item">
                    <div class="execution-timestamp text-muted">
                        <i class="fa fa-clock-o"></i> ${timestamp}
                    </div>
                    <div class="execution-status">
                    <i class="fa fa-check-circle text-success"></i> 
                    <strong><?php echo _l('image_selection_completed'); ?></strong>
                </div>
                <div class="execution-details mtop10">
                    <div class="selected-image">
                        <img src="${data.data.selectedImage.imageUrl}" 
                             alt="${data.data.selectedImage.title}"
                             class="img-responsive" />
                        <div class="image-info mtop10">
                            <div class="image-title">${data.data.selectedImage.title}</div>
                            <div class="image-source">
                                <small>Source: ${data.data.selectedImage.origin}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        prependExecutionResult(resultHtml);
    }
}

window.displayImageSelectionStep = function(imageData, workflowData) {
    console.log('displayImageSelectionStep', imageData, workflowData);
    var timestamp = moment().format('DD/MM/YYYY HH:mm:ss');
    
    // Load controller info first
    $.get(admin_url + 'topics/get_topic_controller', {
        topic_id: workflowData.topic_id,
    }, function(response) {
        response = JSON.parse(response);
        var controllerHtml = '';
        
        if (response.success && response.data.controller) {
            var controller = response.data.controller;
            controllerHtml = `
                <div class="col-md-6">
                    <div class="panel panel-info">
                        <div class="panel-heading">
                            <h3 class="panel-title">
                                <?php echo _l('controller_info'); ?>
                                <div class="pull-right">
                                    <button type="button" 
                                            onclick="showControllerSearchModal(${workflowData.topic_id})" 
                                            class="btn btn-xs btn-warning mright5" 
                                            title="<?php echo _l('change_controller'); ?>">
                                        <i class="fa fa-exchange"></i>
                                    </button>
                                    <a href="${admin_url}topics/controllers/view/${controller.id}" 
                                       target="_blank" 
                                       class="btn btn-xs btn-default mright5" 
                                       title="<?php echo _l('view_controller'); ?>">
                                        <i class="fa fa-eye"></i>
                                    </a>
                                    <a href="#" 
                                       onclick="toggleControllerDetails(this); return false;" 
                                       class="btn btn-xs btn-default" 
                                       title="<?php echo _l('toggle_details'); ?>">
                                        <i class="fa fa-chevron-down"></i>
                                    </a>
                                </div>
                            </h3>
                        </div>
                        <div class="panel-body controller-details" style="display: none;">
                            <div class="table-responsive">
                                <table class="table table-striped table-controller-info">
                                    <tbody>
                                        <tr>
                                            <td><strong><?php echo _l('site'); ?></strong></td>
                                            <td>${controller.site || '-'}</td>
                                        </tr>
                                        <tr>
                                            <td><strong><?php echo _l('platform'); ?></strong></td>
                                            <td>${controller.platform || '-'}</td>
                                        </tr>
                                        <tr>
                                            <td><strong><?php echo _l('blog_id'); ?></strong></td>
                                            <td>${controller.blog_id || '-'}</td>
                                        </tr>
                                        <tr>
                                            <td><strong><?php echo _l('logo_url'); ?></strong></td>
                                            <td>${controller.logo_url ? `<a href="${controller.logo_url}" target="_blank">${controller.logo_url}</a>` : '-'}</td>
                                        </tr>
                                        <tr>
                                            <td><strong><?php echo _l('status'); ?></strong></td>
                                            <td>
                                                <span class="label label-${controller.status == 1 ? 'success' : 'danger'}">
                                                    ${controller.status == 1 ? '<?php echo _l('active'); ?>' : '<?php echo _l('inactive'); ?>'}
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong><?php echo _l('project_id'); ?></strong></td>
                                            <td>${controller.project_id || '-'}</td>
                                        </tr>
                                        <tr>
                                            <td><strong><?php echo _l('seo_task_sheet_id'); ?></strong></td>
                                            <td>${controller.seo_task_sheet_id || '-'}</td>
                                        </tr>
                                        <tr>
                                            <td><strong><?php echo _l('emails'); ?></strong></td>
                                            <td>${controller.emails || '-'}</td>
                                        </tr>
                                      
                                        <tr>
                                            <td><strong><?php echo _l('page_mapping'); ?></strong></td>
                                            <td>${controller.page_mapping || '-'}</td>
                                        </tr>
                                        <tr>
                                            <td><strong><?php echo _l('datecreated'); ?></strong></td>
                                            <td>${moment(controller.datecreated).format('DD/MM/YYYY HH:mm:ss')}</td>
                                        </tr>
                                        <tr>
                                            <td><strong><?php echo _l('dateupdated'); ?></strong></td>
                                            <td>${moment(controller.dateupdated).format('DD/MM/YYYY HH:mm:ss')}</td>
                                        </tr>
                                        <tr>
                                            <td><strong><?php echo _l('slogan'); ?></strong></td>
                                            <td>${controller.slogan || '-'}</td>
                                        </tr>
                                        <tr>
                                            <td><strong><?php echo _l('writing_style'); ?></strong></td>
                                            <td>
                                                <div class="expandable-content">
                                                    ${controller.writing_style || '-'}
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong><?php echo _l('action_1'); ?></strong></td>
                                            <td>
                                                <div class="expandable-content">
                                                    ${controller.action_1 || '-'}
                                                </div>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong><?php echo _l('action_2'); ?></strong></td>
                                            <td>
                                                <div class="expandable-content">
                                                    ${controller.action_2 || '-'}
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        } else {
            controllerHtml = `
                <div class="col-md-6">
                    <div class="panel panel-warning">
                        <div class="panel-heading">
                            <h3 class="panel-title">
                                <?php echo _l('controller_info'); ?>
                                <button type="button" 
                                        onclick="showControllerSearchModal(${workflowData.topic_id})" 
                                        class="btn btn-info btn-xs pull-right">
                                    <i class="fa fa-plus"></i> <?php echo _l('add_controller'); ?>
                                </button>
                            </h3>
                        </div>
                        <div class="panel-body">
                            <div class="alert alert-warning">
                                <i class="fa fa-exclamation-triangle"></i>
                                <?php echo _l('no_controller_assigned'); ?>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        // Store workflowData in a global variable for later use
        window.currentWorkflowData = workflowData;
        
        var selectionHtml = `
            <div class="execution-result-item">
                <div class="execution-timestamp text-muted">
                    <i class="fa fa-clock-o"></i> ${timestamp}
                </div>
                <div class="execution-status">
                    <i class="fa fa-check-circle text-primary"></i> 
                    <strong><?php echo _l('please_select_image'); ?></strong>
                    </div>
                    <div class="execution-details mtop10">
                    <div class="row">
                        <div class="col-md-${controllerHtml ? '6' : '12'}">
                        <div class="image-grid">
                                ${imageData.map((image, index) => `
                                <div class="image-item">
                                    <div class="image-preview">
                                        <img src="${image.thumbnailUrl}" 
                                             alt="${image.title}"
                                             data-full-url="${image.imageUrl}"
                                             onclick="openImagePreview('${image.imageUrl}', '${image.title}')" />
                                    </div>
                                    <div class="image-info">
                                        <div class="image-title">${image.title}</div>
                                        <div class="image-source">
                                            <small>Source: ${image.origin}</small>
                                        </div>
                                        <div class="image-actions">
                                                <button class="btn btn-success btn-block" 
                                                        onclick="selectImage('${image.imageUrl}')">
                                                    <i class="fa fa-check"></i> Select This Image
                                            </button>
                                            </div>
                                        </div>
                                    </div>
                                `).join('')}
                                </div>
                        </div>
                        ${controllerHtml}
                        </div>
                    </div>
                </div>
            `;
            
        // Replace loading state with selection interface
        $('#image-generation-result').replaceWith(selectionHtml);
    });
}

// Make selectImage function global by attaching to window object
window.selectImage = function(selectedImageUrl) {
    // Get workflowData from global variable
    const workflowData = window.currentWorkflowData;
    if (!workflowData) {
        console.error('No workflow data available');
        alert_float('danger', '<?php echo _l('workflow_data_missing'); ?>');
        return;
    }

    // Prepare new workflow data with selected image
    var newWorkflowData = {
        workflow_id: workflowData.workflow_id,
        topic_id: workflowData.topic_id,
        target_type: workflowData.target_type,
        target_state: workflowData.target_state,
        button_id: workflowData.button_id,
        selected_option: selectedImageUrl,
        audit_step: 2,
        from_selection: true
    };
    
    // Execute workflow with new data
    executeWorkflow(newWorkflowData);
};

function pollWorkflowStatus(workflowId, executionId, workflowData, onSuccess) {
        console.log("Polling workflow status for workflowId:", workflowId, "and executionId:", executionId);
        var pollInterval = 10000; // 10 seconds
    var maxAttempts = 60; // 10 minutes maximum
        var attempts = 0;
    var totalTime = maxAttempts * (pollInterval/1000); // Total time in seconds
    var timeRemaining = totalTime;
    var countdownInterval;

    // Add poll info container after progress bar
    var pollInfoHtml = `
        <div class="poll-info mtop5">
            <div class="poll-count text-muted">
                <span class="attempts-counter">Polling: 0/${maxAttempts}</span>
                <span class="countdown-timer pull-right">Time remaining: 10:00</span>
            </div>
        </div>
    `;
    $('#image-generation-result .execution-details').after(pollInfoHtml);

    function updateCountdown() {
        timeRemaining--;
        if (timeRemaining >= 0) {
            var minutes = Math.floor(timeRemaining / 60);
            var seconds = timeRemaining % 60;
            var timeString = String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
            $('#image-generation-result .countdown-timer').text('Time remaining: ' + timeString);
        }
    }

        function poll() {
            attempts++;
        console.log("Polling attempt: " + attempts, $('#image-generation-result .attempts-counter'));
        
     
        if ($('#image-generation-result .attempts-counter').length === 0){
                var pollInfoHtml = `
                <div class="poll-info mtop5">
                    <div class="poll-count text-muted">
                        <span class="attempts-counter">Polling: 0/${maxAttempts}</span>
                        <span class="countdown-timer pull-right">Time remaining: 10:00</span>
                    </div>
                </div>
            `;
            $('#image-generation-result .execution-details').after(pollInfoHtml);
            }

           // Update attempts counter
           $('#image-generation-result .attempts-counter').text(`Polling: ${attempts}/${maxAttempts}`);


            $.ajax({
                url: admin_url + 'topics/check_workflow_status',
                type: 'POST',
                data: {
                    workflow_id: workflowId,
                    execution_id: executionId
                },
                success: function(response) {
                    try {
                        response = JSON.parse(response);
                    console.log("Poll response:", response, response.success, response.finished);
                    
                    if (!response.success) {
                        // Clear countdown interval
                        if (countdownInterval) {
                            clearInterval(countdownInterval);
                        }
                        
                        // Remove poll info
                        $('#image-generation-result .poll-info').remove();
                        
                        // Show error message
                        $('#image-generation-result .execution-status')
                            .html('<i class="fa fa-times text-danger"></i> <?php echo _l('workflow_check_failed'); ?>');
                        return;
                    }
                    
                        if (response.finished === true) {
                        if (countdownInterval) {
                            clearInterval(countdownInterval);
                        }
                        $('#image-generation-result .poll-info').remove();
                        
                        // Extract image data and call success callback
                        console.log("Response data:", response.data);
                        if (response.data && response.data.contextData && 
                            response.data.contextData['node:Loop Over Items'] &&
                            response.data.contextData['node:Loop Over Items'].processedItems) {
                            
                            var imageData = response.data.contextData['node:Loop Over Items']
                                .processedItems.map(item => item.json);
                            
                            if (Array.isArray(imageData) && imageData.length > 0) {
                                onSuccess(imageData);
                                return;
                            }
                        }
                        
                        // Show error if no valid data
                        $('#image-generation-result .execution-status')
                            .html('<i class="fa fa-warning text-warning"></i> <?php echo _l('no_images_found'); ?>');
                    }
                    
                    // Update progress status
                    $('.status-text').text(response.status || 'running');
                        
                        // Continue polling if not finished and not exceeded max attempts
                        if (attempts < maxAttempts) {
                            setTimeout(poll, pollInterval);
                        } else {
                        // Clear countdown interval
                        if (countdownInterval) {
                            clearInterval(countdownInterval);
                        }
                        
                        // Remove poll info on timeout
                        $('#image-generation-result .poll-info').remove();
                        
                            // Show timeout message
                            $('#image-generation-result .execution-status')
                                .html('<i class="fa fa-warning text-warning"></i> <?php echo _l('generation_timeout'); ?>');
                        }
                    } catch (e) {
                        console.error('Polling error:', e);
                    }
                },
                error: function() {
                // Clear countdown interval
                if (countdownInterval) {
                    clearInterval(countdownInterval);
                }
                
                // Remove poll info on error
                $('#image-generation-result .poll-info').remove();
                
                    // Show error in current result item
                    $('#image-generation-result .execution-status')
                        .html('<i class="fa fa-times text-danger"></i> <?php echo _l('polling_error'); ?>');
                }
            });
        }

    // Start countdown timer
    countdownInterval = setInterval(updateCountdown, 1000);

        // Start polling
        poll();
    }
</script> 
