<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<script> 
 /**
     * Display Social Media Result
     * @param data
     * @param workflowData
     */
    function displaySocialMediaResult(data, workflowData) {
        var timestamp = moment().format('DD/MM/YYYY HH:mm:ss');
        
        // Check if the response contains pages
        if (data.data && data.data.pages) {
            var pages = data.data.pages;
            var resultHtml = `
                <div class="execution-result-item">
                    <div class="execution-timestamp text-muted">
                        <i class="fa fa-clock-o"></i> ${timestamp}
                    </div>
                    <div class="execution-status">
                        <i class="fa fa-check-circle text-primary"></i> 
                        <strong><?php echo _l('select_fanpage_and_post_type'); ?></strong>
                    </div>
                    <div class="execution-details mtop10">
                        <div class="form-group">
                            <label><?php echo _l('select_fanpage'); ?></label>
                            <select class="form-control fanpage-select" ${data.data.step === 2 ? 'disabled' : ''}>
                                ${pages.map(page => 
                                    `<option value="${page.page_id}" data-id="${page.id}">${page.page_name}</option>`
                                ).join('')}
                            </select>
                        </div>
                        <div class="form-group">
                            <label><?php echo _l('post_type'); ?></label>
                            <select class="form-control post-type-select" ${data.data.step === 2 ? 'disabled' : ''}>
                                <option value="image"><?php echo _l('post_type_image'); ?></option>
                                <option value="link"><?php echo _l('post_type_link'); ?></option>
                                <option value="carousel"><?php echo _l('post_type_carousel'); ?></option>
                                <option value="slider"><?php echo _l('post_type_slider'); ?></option>
                            </select>
                        </div>
                        ${data.data.step === 1 ? `
                            <button type="button" class="btn btn-info submit-social-selection" 
                                    data-workflow-id="${workflowData.workflow_id}"
                                    data-topic-id="${workflowData.topic_id}"
                                    data-target-type="${workflowData.target_type}"
                                    data-target-state="${workflowData.target_state}"
                                    data-button-id="${workflowData.button_id}">
                                <i class="fa fa-check"></i> <?php echo _l('submit'); ?>
                            </button>
                        ` : `
                            <div class="alert alert-success">
                                <?php echo _l('workflow_executed_successfully'); ?>
                            </div>
                        `}
                    </div>
                </div>
            `;
            prependExecutionResult(resultHtml);

            // Bind event for the submit button if step is 1
            if (data.data.step === 1) {
                $('.submit-social-selection').off('click').on('click', function() {
                    var btn = $(this);
                    var selectedPage = $('.fanpage-select option:selected');
                    
                    var newWorkflowData = {
                        workflow_id: btn.data('workflow-id'),
                        topic_id: btn.data('topic-id'),
                        target_type: btn.data('target-type'),
                        target_state: btn.data('target-state'),
                        button_id: btn.data('button-id'),
                        selected_page_id: selectedPage.val(),
                        selected_page_internal_id: selectedPage.data('id'),
                        post_type: $('.post-type-select').val(),
                        from_selection: true
                    };
                    
                    executeWorkflow(newWorkflowData);
                });
            }
        } else {
            displayDefaultResult(data, workflowData);
        }
    }
</script>