<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<script>
function updateButtonsFromHistory(historyData) {
    // if (!historyData || historyData.length === 0) {
    //     return;
    // }

    let anyButtonVisible = false;
    let anyButtonDeactivated = false;
    var currentType = '<?php echo $topic->action_type_code; ?>';
    var currentState = '<?php echo $topic->action_state_code; ?>';

    // First create all buttons in both sections
    $('#dynamic-action-buttons button').each(function() {
        var button = $(this);
        var buttonId = button.data('button-id');
        
        // Create deactivated version if not exists
        if ($('#deactivated-action-buttons button[data-button-id="' + buttonId + '"]').length === 0) {
            var deactivatedButton = button.clone()
                .addClass('disabled')
                .prop('disabled', false)
                .css('opacity', '0.6')
                .click(function() {
                    // Toggle visibility between sections
                    button.show();
                    $(this).hide();
                    
                    if ($('#deactivated-action-buttons button:visible').length === 0) {
                        $('#deactivated-buttons-section').hide();
                    }
                });
            
            $('#deactivated-action-buttons').append(deactivatedButton);
        }
    });

    // Then handle visibility based on history
    $('#dynamic-action-buttons button').each(function() {
        var button = $(this);
        var buttonId = button.data('button-id');
        var targetType = button.data('target-type');
        var targetState = button.data('target-state');
        var ignoreTypes = button.data('ignore-types') || [];
        var ignoreStates = button.data('ignore-states') || [];
        // console.log('dynamic-action-buttons name', button.html());
        // console.log('dynamic-action-buttons ignoreTypes', ignoreTypes);
        // console.log('dynamic-action-buttons ignoreStates', ignoreStates);  
        // console.log('dynamic-action-buttons targetType', targetType);
        // console.log('dynamic-action-buttons targetState', targetState);
        var typeExistsInHistory = false;
        var stateExistsInHistory = false;

        // Kiểm tra nếu có ignoreTypes
        if (ignoreTypes && ignoreTypes.length > 0) {
            typeExistsInHistory = ignoreTypes.some(function(ignoreType) {
                return historyData.some(function(history) {
                    return history.action_type_code === ignoreType;
                });
            });
        }

        // Kiểm tra nếu có ignoreStates 
        if (ignoreStates && ignoreStates.length > 0) {
            stateExistsInHistory = ignoreStates.some(function(ignoreState) {
                return historyData.some(function(history) {
                    return history.action_state_code === ignoreState;
                });
            });
        }

        // Logic mới: Ẩn button khi thỏa mãn một trong hai điều kiện sau:
        // 1. Một trong hai mảng rỗng và mảng còn lại có giá trị thỏa mãn
        // 2. Cả hai mảng có giá trị và cả hai điều kiện đều thỏa mãn
        var shouldHideButton = false;

        if ((!ignoreTypes || ignoreTypes.length === 0) && ignoreStates && ignoreStates.length > 0) {
            // Chỉ có ignoreStates
            shouldHideButton = stateExistsInHistory;
        } else if ((!ignoreStates || ignoreStates.length === 0) && ignoreTypes && ignoreTypes.length > 0) {
            // Chỉ có ignoreTypes
            shouldHideButton = typeExistsInHistory;
        } else if (ignoreTypes && ignoreTypes.length > 0 && ignoreStates && ignoreStates.length > 0) {
            // Có cả hai và cả hai đều thỏa mãn
            shouldHideButton = typeExistsInHistory && stateExistsInHistory;
        }

        var deactivatedButton = $('#deactivated-action-buttons button[data-button-id="' + buttonId + '"]');

        // Show in deactivated section if conditions are met
        if (shouldHideButton) {
            button.hide();
            deactivatedButton.show();
            anyButtonDeactivated = true;
        } else {
            // Show in active section if matching history exists
            var matchingHistory = historyData.find(function(history) {
                return history.action_type_code === targetType && 
                       history.action_state_code === targetState;
            });

            if (matchingHistory) {
                button.show()
                      .attr('data-history-id', matchingHistory.id)
                      .attr('title', '<?php echo _l("history_available_from"); ?>: ' + 
                              moment(matchingHistory.dateupdated).format('DD/MM/YYYY HH:mm:ss'));
                deactivatedButton.hide();
                anyButtonVisible = true;
            } else {
                // Hide in both sections if no match
                button.hide();
                deactivatedButton.hide();
            }
        }
    });

    // Sắp xếp các nút vô hiệu hóa theo thứ tự order
    var deactivatedButtons = $('#deactivated-action-buttons button:visible').detach().get();
    deactivatedButtons.sort(function(a, b) {
        var aOrder = parseInt($(a).data('order') || 0);
        var bOrder = parseInt($(b).data('order') || 0);
        return aOrder - bOrder;
    });
    $('#deactivated-action-buttons').append(deactivatedButtons);

    $('#deactivated-buttons-section').toggle(anyButtonDeactivated);
}


// Add controller search modal function
function showControllerSearchModal(topicId) {
    var modalHtml = `
        <div class="modal fade" id="controller-search-modal">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                        <h4 class="modal-title"><?php echo _l('select_controller'); ?></h4>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="input-group">
                                    <input type="text" class="form-control" placeholder="<?php echo _l('search_controller'); ?>" id="controller-search">
                                    <span class="input-group-btn">
                                        <button class="btn btn-default" type="button" onclick="searchControllers()">
                                            <i class="fa fa-search"></i>
                                        </button>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="row mtop15">
                            <div class="col-md-12">
                                <div class="table-responsive">
                                    <table class="table table-striped table-controllers">
                                        <thead>
                                            <tr>
                                                <th><?php echo _l('site'); ?></th>
                                                <th><?php echo _l('platform'); ?></th>
                                                <th><?php echo _l('blog_id'); ?></th>
                                                <th><?php echo _l('status'); ?></th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody id="controllers-list"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;

    // Remove existing modal if any
    $('#controller-search-modal').remove();
    $('body').append(modalHtml);

    // Load initial controllers
    searchControllers();

    // Show modal
    $('#controller-search-modal').modal('show');

    // Handle search input
    $('#controller-search').on('keyup', function(e) {
        if (e.keyCode === 13) {
            searchControllers();
        }
    });

    function searchControllers() {
        var search = $('#controller-search').val();
        
        $.get(admin_url + 'topics/search_controllers', {
            search: search
        }, function(response) {
            var html = '';
            response.data.forEach(function(controller) {
                html += `
                    <tr>
                        <td>${controller.site}</td>
                        <td>${controller.platform || '-'}</td>
                        <td>${controller.blog_id || '-'}</td>
                        <td>
                            <span class="label label-${controller.status == 1 ? 'success' : 'danger'}">
                                ${controller.status == 1 ? '<?php echo _l('active'); ?>' : '<?php echo _l('inactive'); ?>'}
                            </span>
                        </td>
                        <td>
                            <button type="button" 
                                    class="btn btn-info btn-xs"
                                    onclick="assignController(${topicId}, ${controller.id})">
                                <?php echo _l('select'); ?>
                            </button>
                        </td>
                    </tr>
                `;
            });
            $('#controllers-list').html(html);
        }, 'json');
    }
}

function assignController(topicId, controllerId) {
    $.post(admin_url + 'topics/add_topic_to_controller', {
        topic_id: topicId,
        controller_id: controllerId
    }, function(response) {
        console.log('assignController response', response);
        if (response.success) {
            alert_float('success', response.message);
            $('#controller-search-modal').modal('hide');
            // Refresh page to update controller info
            location.reload();
        } else {
            alert_float('danger', response.message);
        }
    }, 'json');
}


function displayWordPressPreview(metaData, wpData) {
    // Parse social post để tách thành các phần
    const socialPost = metaData.Social_Post || '';
    const socialParts = socialPost.split('\n\n').filter(Boolean);
    
    // Extract URL from social post
    const urlMatch = socialPost.match(/https?:\/\/[^\s]+\.html/);
    const postUrl = urlMatch ? urlMatch[0] : (wpData.link || '');

    // Extract title from social post (first line)
    const postTitle = socialParts[0]?.split('!')[0] || '';

    return `
        <div class="post-preview-container">
            <div class="post-preview-header">
                <div class="post-thumbnail">
                    <img src="${wpData.featured_media_url || wpData.jetpack_featured_media_url || ''}" 
                         alt="${postTitle}"
                         onerror="this.src='<?php echo site_url('assets/images/placeholder.png'); ?>'">
                </div>
                <div class="post-title">
                    <h3>${postTitle}</h3>
                </div>
            </div>
            
            <div class="post-content">
                <div class="post-section">
                    <h4><?php echo _l('meta_description'); ?></h4>
                    <div class="content-text">
                        ${metaData.Meta_Description || ''}
                    </div>
                </div>
                
                <div class="post-section">
                    <h4><?php echo _l('meta_keywords'); ?></h4>
                    <div class="content-text tags">
                        ${(metaData.Meta_Keywords || '').split(',').map(tag => 
                            `<span class="tag">${tag.trim()}</span>`
                        ).join('')}
                    </div>
                </div>
                
                <div class="post-section">
                    <h4><?php echo _l('social_post'); ?></h4>
                    <div class="content-text social-post">
                        ${socialParts.map(part => {
                            // Xử lý emojis và hashtags
                            const formattedPart = part
                                .replace(/([#][^\s#]+)/g, '<span class="hashtag">$1</span>') // Highlight hashtags
                                .replace(/([\uD800-\uDBFF][\uDC00-\uDFFF])/g, '<span class="emoji">$1</span>'); // Highlight emojis
                            return `<p>${formattedPart}</p>`;
                        }).join('')}
                    </div>
                </div>
                
                <div class="post-section">
                    <h4><?php echo _l('meta_footer'); ?></h4>
                    <div class="content-text">
                        ${metaData.Meta_Footer || ''}
                    </div>
                </div>
            </div>
            
            <div class="post-actions">
                <a href="${postUrl}" target="_blank" class="btn btn-info">
                    <i class="fa fa-external-link"></i> 
                    <?php echo _l('view_full_post'); ?>
                </a>
                <div class="post-meta">
                    <span class="text-muted">
                        <i class="fa fa-folder-o"></i> 
                        <?php echo _l('category'); ?>: ${metaData.Category || '-'}
                    </span>
                </div>
            </div>
        </div>
    `;
}

// Add toggle function
function toggleControllerDetails(element) {
    var $icon = $(element).find('i');
    var $details = $(element).closest('.panel').find('.controller-details');
    
    if ($details.is(':visible')) {
        $details.slideUp();
        $icon.removeClass('fa-chevron-up').addClass('fa-chevron-down');
    } else {
        $details.slideDown();
        $icon.removeClass('fa-chevron-down').addClass('fa-chevron-up');
    }
}


function htmlEntityDecode(str) {
    // console.log('htmlEntityDecode', str);
    return str
        .replace(/&amp;/g, "&")
        .replace(/&lt;/g, "<")
        .replace(/&gt;/g, ">")
        .replace(/&quot;/g, '"')
        .replace(/&#39;/g, "'");
}

/**
 * Display result cho Topic Composer
 */
function displayTopicComposerResult(data, workflowData) {
    var timestamp = moment().format('DD/MM/YYYY HH:mm:ss');

    // Step 1: Show loading và bắt đầu polling
    if (data.data && data.data.audit_step === 1) {
        console.log("Displaying Topic Composer Step 1");
        
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
            <div class="execution-result-item" id="topic-composer-result">
                <div class="execution-timestamp text-muted">
                    <i class="fa fa-clock-o"></i> ${timestamp}
                </div>
                <div class="execution-status">
                    <i class="fa fa-spinner fa-spin text-info"></i> 
                    <strong>${data.message || '<?php echo _l('processing_topic'); ?>'}</strong>
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
                function(composerData) {
                    // Callback khi polling hoàn tất thành công
                    displayTopicComposerStep2(composerData, workflowData);
                }
            );
        } else {
            // Show error if no workflow info
            $('#topic-composer-result .execution-status')
                .html('<i class="fa fa-times text-danger"></i> <?php echo _l('workflow_info_missing'); ?>');
        }
        return;
    }

    // Step 2: Hiển thị kết quả sau khi polling
    if (data.success && data.data && data.data.audit_step === 2) {
        var resultHtml = `
            <div class="execution-result-item">
                <div class="execution-timestamp text-muted">
                    <i class="fa fa-clock-o"></i> ${timestamp}
                </div>
                <div class="execution-status">
                    <i class="fa fa-check-circle text-success"></i> 
                    <strong><?php echo _l('topic_composed_successfully'); ?></strong>
                </div>
                <div class="execution-details mtop10">
                    <div class="panel panel-info">
                        <div class="panel-heading">
                            <h3 class="panel-title"><?php echo _l('composition_result'); ?></h3>
                        </div>
                        <div class="panel-body">
                            <div class="composed-content">
                                ${formatComposedContent(data.data.composedContent)}
                            </div>
                            ${data.data.additionalInfo ? `
                                <div class="additional-info mtop10">
                                    <hr>
                                    <h4><?php echo _l('additional_info'); ?></h4>
                                    <pre class="additional-info-content">${JSON.stringify(data.data.additionalInfo, null, 2)}</pre>
                                </div>
                            ` : ''}
                        </div>
                    </div>
                </div>
            </div>
        `;
        prependExecutionResult(resultHtml);
    }
}

/**
 * Format composed content
 */
function formatComposedContent(content) {
    if (!content) return '';
    
    // Parse content if it's JSON string
    try {
        if (typeof content === 'string') {
            content = JSON.parse(content);
        }
    } catch (e) {
        console.error('Error parsing content:', e);
    }

    // Format based on content structure
    if (typeof content === 'object') {
        let html = '<div class="composed-sections">';
        
        // Title section
        if (content.title) {
            html += `
                <div class="composed-section">
                    <h4><?php echo _l('title'); ?></h4>
                    <div class="content-text">${content.title}</div>
                </div>
            `;
        }

        // Main content sections
        if (content.sections) {
            content.sections.forEach((section, index) => {
                html += `
                    <div class="composed-section">
                        <h4><?php echo _l('section'); ?> ${index + 1}</h4>
                        <div class="content-text">${section}</div>
                    </div>
                `;
            });
        }

        html += '</div>';
        return html;
    }

    // Fallback for string content
    return `<div class="content-text">${content}</div>`;
}
</script>