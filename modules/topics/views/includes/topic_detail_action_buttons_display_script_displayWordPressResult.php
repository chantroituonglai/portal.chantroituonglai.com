<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<script> 
/**
 * Display WordPress Result
 * @param data
 * @param workflowData
 */
function displayWordPressResult(data, workflowData) {
         // Store WordPress data globally
         window.lastWordPressData = data;
       
        
        console.log('displayWordPressResult', data, workflowData);
        var timestamp = moment().format('DD/MM/YYYY HH:mm:ss');

        // Check if this is step 1 with options
        if (data.data && data.data.step === 1 && data.data.show_selection) {
            // Parse topic data to check ACF fields
            console.log('WordPress data:', data.data.options);
            
            var topicData = data.data.options || {};
            var acfData = topicData.acf || {};
            var hasTopItems = acfData.top_item && acfData.top_item.length > 0;
            var hasMoBai = acfData.mo_bai && acfData.mo_bai.trim() !== '';
            var hasKetLuan = acfData.ket_luan && acfData.ket_luan.trim() !== '';
            var hasMoBaiKetLuan = hasMoBai || hasKetLuan;
            
            // Load controller info if available
            console.log('workflowData', workflowData);
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
                    // No controller found, show add button
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

                var resultHtml = `
                    <div class="execution-result-item">
                        <div class="execution-timestamp text-muted">
                            <i class="fa fa-clock-o"></i> ${timestamp}
                        </div>
                        <div class="execution-status">
                            <i class="fa fa-check-circle text-primary"></i> 
                            <strong><?php echo _l('select_post_type'); ?></strong>
                        </div>
                        <div class="execution-details mtop10">
                            <div class="row">
                                <div class="col-md-${controllerHtml ? '6' : '12'}">
                                    <div class="form-group">
                                        <label><?php echo _l('available_options'); ?></label>
                                        <div class="post-options">
                                            ${hasTopItems ? `
                                                <div class="option-item">
                                                    <label>
                                                        <input type="radio" name="post_option" value="audit_post" 
                                                               class="post-type-select">
                                                        <?php echo _l('audit_post'); ?>
                                                        <small class="text-muted"><?php echo _l('audit_post_desc'); ?></small>
                                                    </label>
                                                </div>
                                            ` : `
                                                <div class="option-item">
                                                    <label>
                                                        <input type="radio" name="post_option" value="write_content" 
                                                               class="post-type-select">
                                                        <?php echo _l('write_content'); ?>
                                                        <small class="text-muted"><?php echo _l('write_content_desc'); ?></small>
                                                    </label>
                                                </div>
                                            `}
                                            ${!hasMoBaiKetLuan ? `
                                                <div class="option-item">
                                                    <label>
                                                        <input type="radio" name="post_option" value="write_social" 
                                                               class="post-type-select">
                                                        <?php echo _l('write_social'); ?>
                                                        <small class="text-muted"><?php echo _l('write_social_desc'); ?></small>
                                                    </label>
                                                </div>
                                            ` : ''}
                                        </div>
                                    </div>
                                    
                                    <div class="buttons-row mtop10">
                                        <button type="button" class="btn btn-info submit-post-selection" 
                                                data-workflow-id="${workflowData.workflow_id}"
                                                data-topic-id="${workflowData.topic_id}"
                                                data-target-type="${workflowData.target_type}"
                                                data-target-state="${workflowData.target_state}"
                                                data-button-id="${workflowData.button_id}">
                                            <i class="fa fa-check"></i> <?php echo _l('submit'); ?>
                                        </button>

                                        ${data.data.options && data.data.options.id ? `
                                            <a href="${data.data.options.guid.rendered}" 
                                               target="_blank" 
                                               class="btn btn-info">
                                                <i class="fa fa-external-link"></i> 
                                                <?php echo _l('preview_post'); ?>
                                                <small class="text-muted preview-post-id">
                                                    (ID: ${data.data.options.id})
                                                </small>
                                            </a>
                                        ` : ''}
                                    </div>
                                </div>
                                ${controllerHtml}
                            </div>
                        </div>
                    </div>
                `;
                
                prependExecutionResult(resultHtml);

                // Bind event for submit button
                bindPostSelectionEvents(workflowData, hasTopItems, hasMoBaiKetLuan);
            });
            
            return;
        }

        // Check if this is step 2 with meta data
        if (data.success && data.data && Array.isArray(data.data) && data.data[0].response) {
            try {
                const metaData = JSON.parse(data.data[0].response);
                const wpData = window.lastWordPressData?.data?.response?.data || {};
                
                var resultHtml = `
                    <div class="execution-result-item success">
                        <div class="execution-timestamp text-muted">
                            <i class="fa fa-clock-o"></i> ${timestamp}
                        </div>
                        <div class="execution-status">
                            <i class="fa fa-check-circle text-success"></i> 
                            <strong><?php echo _l('meta_data_generated'); ?></strong>
                        </div>
                        <div class="execution-details mtop10">
                            ${displayWordPressPreview(metaData, wpData)}
                        </div>
                    </div>
                `;
                prependExecutionResult(resultHtml);
                return;
            } catch (e) {
                console.error('Error parsing meta data:', e);
                displayDefaultResult(data, workflowData);
            }
        }

        // Original WordPress result handling code
        var resultHtml = `
            <div class="execution-result-item success">
                <div class="execution-timestamp text-muted">
                    <i class="fa fa-clock-o"></i> ${timestamp}
                </div>
                <div class="execution-status">
                    <i class="fa fa-info text-info"></i> 
                    <strong><?php echo _l('wordpress_post_created'); ?></strong>
                </div>
                <div class="execution-message mtop5">
                    ${data.message}
                </div>
                ${data.data && data.data.url ? `
                    <div class="execution-details mtop10">
                        <div class="well well-sm">
                            <div class="execution-url">
                                <strong>WordPress URL:</strong>
                                <a href="${data.data.url}" target="_blank">${data.data.url}</a>
                            </div>
                        </div>
                    </div>
                ` : ''}
            </div>
        `;
        prependExecutionResult(resultHtml);

        // Mở URL trong tab mới nếu có yêu cầu
        if (data.data && data.data.open_url && data.data.url) {
            window.open(data.data.url, '_blank');
        }
    }
</script> 