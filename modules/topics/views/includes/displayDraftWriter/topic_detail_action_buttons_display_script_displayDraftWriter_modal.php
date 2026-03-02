<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<script>
if (window.DraftWriter) {
    window.DraftWriter.debounceTimeout = null;
    window.DraftWriter.loadDraftWriterCSS =  function() {
        if (!document.getElementById('draft-writer-styles')) {
            const cssLink = document.createElement('link');
            cssLink.id = 'draft-writer-styles';
            cssLink.rel = 'stylesheet';
            cssLink.type = 'text/css';
            cssLink.href = '<?php echo base_url("modules/topics/assets/css/draft_writer.css?v=" . time()); ?>';
            cssLink.media = 'all';
            document.head.appendChild(cssLink);
            console.log('Draft Writer CSS loaded');
        }
    };

    window.DraftWriter.addFixedFooterStyles = function() {
        const style = document.createElement('style');
        style.id = 'draft-writer-fixed-footer-styles';
        style.textContent = `
            #draft-writer-modal .modal-content {
                display: flex;
                flex-direction: column;
                height: 100%;
            }
            
            #draft-writer-modal .modal-header {
                flex: 0 0 auto;
            }
            
            #draft-writer-modal .modal-body {
                flex: 1 1 auto;
                overflow-y: auto;
                padding-bottom: 20px;
            }
            
            #draft-writer-modal .modal-footer {
                flex: 0 0 auto;
                border-top: 1px solid #e5e5e5;
                background-color: #f5f5f5;
                padding: 15px;
                position: sticky;
                bottom: 0;
                z-index: 1020;
            }
            
            #draft-writer-modal .modal-dialog {
                height: calc(100% - 60px);
                margin: 30px auto;
            }
            
            @media (max-width: 767px) {
                #draft-writer-modal .modal-dialog {
                    height: calc(100% - 20px);
                    margin: 10px auto;
                }
            }
        `;
        document.head.appendChild(style);
        console.log('Draft Writer fixed footer styles loaded');
    }

    window.DraftWriter.adjustModalHeight = function() {
        const windowHeight = $(window).height();
        const modalDialog = $('#draft-writer-modal .modal-dialog');
        modalDialog.css('height', (windowHeight - 60) + 'px');
    }   
    
    window.DraftWriter.debounceUpdateStatistics = function() {
        clearTimeout(window.DraftWriter.debounceTimeout);
        window.DraftWriter.debounceTimeout = setTimeout(window.DraftWriter.updateStatistics, 1000); // Gọi hàm sau 1 giây (1000 ms)
    }

    window.DraftWriter.updateStatistics = function() {
        // Get the iframe
        const iframe = document.getElementById('wordpress-preview-iframe');
        if (!iframe) {
            console.log('Iframe not found, cannot update statistics');
            return;
        }
        
        try {
            // Get the content from the iframe's wp-content div
            const iframeDoc = iframe.contentWindow.document;
            const contentElement = iframeDoc.querySelector('.wp-content');
            
            if (!contentElement) {
                console.log('Content element not found in iframe');
                return;
            }
            
            // Get the text content
            const content = contentElement.innerHTML || '';
        
        // Update word count
        const words = content.trim().split(/\s+/).filter(function(word) {
            return word.length > 0;
        });
        const wordCount = words.length;
        $('#word-count').text(wordCount);
        
        // Update reading time (average reading speed: 200 words per minute)
        const readingTime = Math.max(1, Math.ceil(wordCount / 200));
        $('#reading-time').text(readingTime + ' min');
        
                console.log('Statistics updated from iframe: ' + wordCount + ' words, ' + readingTime + ' min reading time');
        } catch (error) {
            console.error('Error updating statistics from iframe:', error);
        }
    }
    
    window.DraftWriter.extractKeywordsFromContent = function(content) {
        const tags = $('#draft-tags').val().split(',').map(tag => tag.trim().toLowerCase()).filter(Boolean);
        const keywords = {};
        const totalWords = content.toLowerCase()
            .replace(/[^\w\s]/g, ' ')
            .split(/\s+/)
            .filter(word => word.length > 0).length;

        // Process tags first
        if (tags.length > 0) {
            tags.forEach(tag => {
                const regex = new RegExp('\\b' + tag + '\\b', 'gi');
                const matches = content.match(regex) || [];
                const count = matches.length;
                if (count > 0) {
                    keywords[tag] = {
                        word: tag,
                        count: count,
                        density: (count / totalWords * 100).toFixed(2),
                        isTag: true
                    };
                }
            });
        }

        // Process other words
        const stopWords = ['a', 'an', 'the', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'with', 'by', 'about', 'as', 'of', 'from'];
        const words = content.toLowerCase()
            .replace(/[^\w\s]/g, ' ')
            .split(/\s+/)
            .filter(word => 
                word.length > 3 && 
                !stopWords.includes(word) && 
                !tags.includes(word)
            );

        // Count word frequencies
        const wordCount = {};
        words.forEach(word => {
            wordCount[word] = (wordCount[word] || 0) + 1;
        });

        // Add words that appear more than twice
        Object.keys(wordCount).forEach(word => {
            if (wordCount[word] > 2 && !keywords[word]) {
                keywords[word] = {
                    word: word,
                    count: wordCount[word],
                    density: (wordCount[word] / totalWords * 100).toFixed(2),
                    isTag: false
                };
            }
        });

        return keywords;
    }

    window.DraftWriter.updateKeywordDensity = function(keywords, textContent) {
        const $densityContainer = $('#keyword-density');
    
        // Skip if content is empty
        if (!textContent.trim()) {
            $densityContainer.html('<div class="keyword-item"><span class="keyword-text">No keywords yet</span><span class="keyword-count">0</span></div>');
            return;
        }

        // Get tags as keywords
        const tags = $('#draft-tags').val().split(/[,;]/).map(tag => tag.trim()).filter(Boolean);
        
        // Sort keywords by count and tag status
        const sortedKeywords = Object.values(keywords)
            .sort((a, b) => {
                // First sort by score (descending)
                if (b.score !== a.score) return b.score - a.score;
                // Then by count (descending)
                return b.count - a.count;
            })
            .slice(0, 10); // Show top 10 keywords

        // Build density table
        let densityHtml = `
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th><?php echo _l('keyword'); ?></th>
                        <th><?php echo _l('count'); ?></th>
                        <th><?php echo _l('density'); ?></th>
                        <th><?php echo _l('score'); ?></th>
                        <th><?php echo _l('status'); ?></th>
                    </tr>
                </thead>
                <tbody>
        `;

        if (sortedKeywords.length > 0) {
            sortedKeywords.forEach(data => {
                const isTag = tags.includes(data.keyword);
                let statusClass = '';
                let statusIcon = '';
                
                // Determine status based on score
                if (data.score >= 80) {
                    statusClass = 'text-success';
                    statusIcon = 'fa-check-circle';
                } else if (data.score >= 60) {
                    statusClass = 'text-warning';
                    statusIcon = 'fa-exclamation-circle';
                } else {
                    statusClass = 'text-danger';
                    statusIcon = 'fa-times-circle';
                }

                densityHtml += `
                    <tr>
                        <td>
                            ${isTag ? '<i class="fa fa-tag text-info"></i> ' : ''}
                            ${data.keyword}
                        </td>
                        <td>${data.count}</td>
                        <td>${data.density}%</td>
                        <td>${data.score}</td>
                        <td class="${statusClass}">
                            <i class="fa ${statusIcon}"></i>
                            ${window.DraftWriter.getRecommendations(data.recommendations)}
                        </td>
                    </tr>
                `;
            });
        } else {
            densityHtml += `
                <tr>
                    <td colspan="5" class="text-center">
                        <?php echo _l('no_keywords_found'); ?>
                    </td>
                </tr>
            `;
        }

        densityHtml += `
                </tbody>
            </table>
            <small class="text-muted">
                <?php echo _l('keyword_analysis_info'); ?>
                <br><?php echo _l('optimal_density_range'); ?>
            </small>
        `;

        // Update container
        $densityContainer.html(densityHtml);
    }

    window.DraftWriter.getRecommendations = function(recommendations) {
        if (!recommendations || recommendations.length === 0) {
            return '';
        }

        return `
            <div class="keyword-recommendations">
                <a href="#" data-toggle="popover" data-trigger="hover" 
                title="<?php echo _l('recommendations'); ?>"
                data-content="${recommendations.map(rec => _l(rec)).join('<br>')}">
                    <i class="fa fa-info-circle"></i>
                </a>
            </div>
        `;
    }

    window.DraftWriter.initDraftWriterModal = function(data, workflowData) {     
        console.log('Initializing Draft Writer Modal');
        const modalHtml = `
            <div class="modal fade" id="draft-writer-modal" tabindex="-1" role="dialog" aria-labelledby="draftWriterModalLabel">
                <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                            <h4 class="modal-title" id="draftWriterModalLabel">
                                <i class="fa fa-pencil-square-o"></i> <?php echo _l('draft_writer'); ?>
                                <div class="ultimate-editor-badge">
                                    <i class="fa fa-bolt"></i> <?php echo _l('ultimate_editor_available'); ?>
                    </div>
                                        </h4>
                                    </div>
                        <div class="ultimate-editor-banner">
                            <i class="fa fa-info-circle"></i> <?php echo _l('use_ultimate_editor_message'); ?>
                            <button type="button" class="btn btn-sm btn-success" id="open-in-ultimate-editor-top">
                                <i class="fa fa-external-link"></i> <?php echo _l('open_in_ultimate_editor'); ?>
                                                    </button>
                                            </div>
                        <div class="modal-body">
                            <!-- Title and Description Hidden Fields -->
                            <input type="hidden" class="form-control" id="draft-title" name="draft-title">
                            <input type="hidden" class="form-control" id="draft-description" name="draft-description">
                            <input type="hidden" class="form-control" id="draft-tags" name="draft-tags">
                            <input type="hidden" class="form-control" id="draft-category" name="draft-category">
                            <input type="hidden" id="draft-content" name="draft-content">
                            <input type="hidden" id="topic_id" name="topic_id" value="">
                            
                            <!-- WordPress Preview Container -->
                            <div id="wordpress-preview-container" class="wordpress-preview-container">
                                <div class="preview-mode-notice alert alert-info">
                                    <i class="fa fa-spinner fa-spin"></i> <?php echo _l('loading_content', 'Loading content...'); ?>
                                        </div>
                                    </div>
                                
                            <!-- Statistics Section -->
                            <div class="row stats-row" style="margin-top: 15px;">
                                <div class="col-md-12">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="card-title"><?php echo _l('statistics'); ?></h5>
                                                </div>
                                        <div class="card-body stats-container">
                                                <div class="stat-item">
                                                <i class="fa fa-file-text-o"></i> <?php echo _l('word_count'); ?>: <span id="word-count">0</span>
                                                </div>
                                                <div class="stat-item">
                                                <i class="fa fa-clock-o"></i> <?php echo _l('reading_time'); ?>: <span id="reading-time">0 min</span>
                                                </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                            <div class="row full-width">
                                <div class="col-md-12 text-right">
                                <button type="button" class="btn btn-default" data-dismiss="modal">
                                    <i class="fa fa-times"></i> <?php echo _l('close'); ?>
                                </button>
                                    <div class="btn-group ultimate-editor-actions">
                                        <button type="button" class="btn btn-primary" id="preview-in-ultimate-editor">
                                            <i class="fa fa-eye"></i> <?php echo _l('preview_in_ultimate_editor'); ?>
                                </button>
                                        <button type="button" class="btn btn-success" id="open-in-ultimate-editor">
                                            <i class="fa fa-external-link"></i> <?php echo _l('open_in_ultimate_editor'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Append modal template to body
        $('body').append(modalHtml);

        // Load CSS styles
        window.DraftWriter.loadDraftWriterCSS();
        window.DraftWriter.addFixedFooterStyles();
        window.DraftWriter.addWordPressPreviewStyles();
       
        // Adjust modal height when shown
        $(document).on('shown.bs.modal', '#draft-writer-modal', function() {
            window.DraftWriter.adjustModalHeight();
            
            // Set topic_id on a hidden field for later use
            $('#topic_id').val(workflowData.topic_id);
            console.log('Topic ID set to:', workflowData.topic_id);
            
            // Load the content directly via workflow
            loadExistingDraft(workflowData);
        });

        // Add cleanup on modal hide/hidden
        $(document).on('hide.bs.modal', '#draft-writer-modal', function() {
            console.log('Modal is being hidden');
        });

        $(document).on('hidden.bs.modal', '#draft-writer-modal', function() {
            $('#draft-writer-modal').remove();
            console.log('Draft Writer modal removed');
        });

        // Add event handler to adjust modal height on window resize
        $(window).on('resize', function() {
            if ($('#draft-writer-modal').hasClass('in')) {
                window.DraftWriter.adjustModalHeight();
            }
        });

          // Initialize Draft Writer components after modal is shown
        $('#draft-writer-modal').on('shown.bs.modal', function() {
            console.log('Modal shown event triggered');
            
            // Set the topic_id again after the modal is shown
            $('#topic_id').val(workflowData.topic_id);
            
            // Initialize Ultimate Editor integration
            window.DraftWriter.initUltimateEditorIntegration();
        });

        $('#topic_id').val(workflowData.topic_id);
        console.log('Topic ID after modal setup:', $('#topic_id').val());
    }
    
}   

/**
 * Helper function to clean text content (decode entities but keep as text)
 */
function cleanTextContent(text) {
    if (!text) return '';
    
    // Create a temporary element to decode HTML entities
    const tempElement = document.createElement('div');
    
    // Replace problematic entities first
    text = text.replace(/&#38;/g, '&');
    tempElement.innerHTML = text;
    
    // Get as text
    const cleanText = tempElement.textContent || tempElement.innerText || text;
    
    // Escape HTML to prevent XSS
    return escapeHtml(cleanText);
}

/**
 * Helper function to escape HTML for safe insertion
 */
function escapeHtml(text) {
    if (!text) return '';
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.toString().replace(/[&<>"']/g, function(m) { return map[m]; });
}

$(document).ready(function() {
    // No need to initialize modal again - it's already done in window.DraftWriter.initDraftWriterModal
});

// Update the open content in Ultimate Editor function to not use controller-select
function openInUltimateEditor(content, mode) {
    const topicId = $('#topic_id').val();
    
    // Create form data
    const formData = {
        topic_id: topicId,
        title: content.title || '',
        description: content.description || '',
        content: content.content || '',
        tags: content.tags || '',
        category: content.category || '',
        mode: mode
    };
    
    // Create a temporary form to submit
    const $form = $('<form></form>')
        .attr('action', admin_url + 'topics/ultimate_editor/open')
        .attr('method', 'post')
        .attr('target', '_blank');
    
    // Add form fields
    $.each(formData, function(key, value) {
        $form.append($('<input></input>')
            .attr('type', 'hidden')
            .attr('name', key)
            .attr('value', value));
    });
    
    // Add form to body, submit it, and remove it
    $('body').append($form);
    $form.submit();
    $form.remove();
    
    // If edit mode, close the modal
    if (mode === 'edit') {
        $('#draft-writer-modal').modal('hide');
    }
}

// Function to load existing draft
function loadExistingDraft(workflowData) {
    console.log('Loading content from workflow for topic ID:', workflowData.topic_id);
    
    const topic_id = workflowData.topic_id;
    if (!topic_id) {
        console.error('No topic ID available');
        return;
    }
    
    // Always execute workflow to get content directly
    console.log('Executing workflow to get content...');
    
    // Prepare workflow data
    const requestData = {
        topic_id: topic_id,
        workflow_id: workflowData.workflow_id || (typeof WORKFLOW_ID !== 'undefined' ? WORKFLOW_ID : null),
        target_type: workflowData.target_type || (typeof TARGET_TYPE !== 'undefined' ? TARGET_TYPE : null),
        target_state: workflowData.target_state || (typeof TARGET_STATE !== 'undefined' ? TARGET_STATE : null)
    };
    
    console.log('Workflow request data:', requestData);
    
    // Execute workflow directly
    $.ajax({
        url: admin_url + 'topics/ultimate_editor/execute_workflow',
        type: 'POST',
        data: requestData,
        dataType: 'json',
        success: function(response) {
            console.log('Workflow execution response:', response);
            
            if (response.success) {
                // Check if polling is needed
                if (response.data && response.data.needs_polling && response.data.execution_id) {
                    console.log('Workflow needs polling, polling for results...');
                    pollWorkflowResults(
                        response.data.workflow_id, 
                        response.data.execution_id, 
                        topic_id
                    );
                } else if (response.data && response.data.response) {
                    // We already have the content, process it
                    processWorkflowResponse(response.data.response);
                } else {
                    console.error('Unexpected workflow response format:', response);
                }
            } else {
                console.error('Workflow execution failed:', response.message || 'Unknown error');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error executing workflow:', error);
        }
    });
    
    // Helper function to poll for workflow results
    function pollWorkflowResults(workflowId, executionId, topicId) {
        let attempts = 0;
        const maxAttempts = 20; // Maximum number of polling attempts
        const pollInterval = 2000; // Poll every 2 seconds
        
        function checkStatus() {
            if (attempts >= maxAttempts) {
                console.error('Timeout waiting for workflow results after', attempts, 'attempts');
                        $.notify({
                    message: 'Timeout waiting for workflow results'
                        }, {
                    type: 'danger',
                            delay: 3000
                        });
                return;
            }
            
            attempts++;
            console.log('Polling attempt', attempts, 'of', maxAttempts);
            
            $.ajax({
                url: admin_url + 'topics/ultimate_editor/check_workflow_status',
                type: 'POST',
                data: {
                    workflow_id: workflowId,
                    execution_id: executionId,
                    topic_id: topicId
                },
                dataType: 'json',
                success: function(response) {
                    console.log('Poll workflow status response:', response);
                    
                    if (response.success) {
                        // Check if the workflow is complete
                        if (response.data.status === 'completed') {
                            // Check if we have data
                            if (response.data.workflow_response) {
                                processWorkflowResponse(response.data.workflow_response);
                            } else {
                                console.error('No data in completed workflow response');
                        $.notify({
                                    message: 'No data in workflow response'
                        }, {
                                    type: 'danger',
                                    delay: 3000
                        });
                    }
                        } else if (response.data.status === 'failed') {
                            console.error('Workflow execution failed:', response.data.error_message || 'Unknown error');
                    $.notify({
                                message: 'Workflow execution failed: ' + (response.data.error_message || 'Unknown error')
                    }, {
                                type: 'danger',
                        delay: 3000
                    });
                        } else {
                            // Still running, poll again
                            setTimeout(checkStatus, pollInterval);
                }
                    } else {
                        console.error('Error checking workflow status:', response.message || 'Unknown error');
                $.notify({
                            message: response.message || 'Error checking workflow status'
                }, {
                    type: 'danger',
                    delay: 3000
                });
            }
        },
        error: function(xhr, status, error) {
                    console.error('Error checking workflow status:', error);
            $.notify({
                        message: 'Error checking workflow status: ' + error
            }, {
                type: 'danger',
                delay: 3000
            });
        }
    });
}

        // Start the first polling attempt
        checkStatus();
    }
    
    // Helper function to process workflow response and update UI
    function processWorkflowResponse(workflowResponse) {
        console.log('Processing workflow response:', workflowResponse);
        
        // Extract content based on response structure
        let content = workflowResponse;
        let title = '';
        let description = '';
        let htmlContent = '';
        let tags = '';
        
        // First, check if we got a JSON string that needs parsing
        if (typeof workflowResponse === 'string' && workflowResponse.trim().startsWith('{')) {
            try {
                content = JSON.parse(workflowResponse);
                console.log('Parsed JSON response:', content);
            } catch (e) {
                console.error('Failed to parse JSON response:', e);
            }
        }
        
        // Check different possible response structures
        if (workflowResponse.data && workflowResponse.data.response && workflowResponse.data.response.data) {
            // Draft Writer specific format
            console.log('Detected Draft Writer response format', workflowResponse.data.response.data);
            content = workflowResponse.data.response.data;
        } else if (workflowResponse.success && workflowResponse.data) {
            // Direct API response format
            console.log('Detected direct API response format');
            content = workflowResponse.data;
        }
        
        // Handle different content structures
        if (Array.isArray(content) && content.length > 0) {
            // Array structure (typically from Draft Writer or JSON data)
            console.log('Processing array data with', content.length, 'items');
            
            // Set the main title from the first item
            const firstItem = content[0];
            
            // Combine position and title if both exist
            if (firstItem.position && firstItem.title) {
                title = firstItem.position + ' - ' + firstItem.title;
            } else {
                title = firstItem.Title || firstItem.Item_Title || firstItem.title || '';
            }
            
            description = firstItem.Summary || firstItem.description || firstItem.Description || '';
            tags = firstItem.TopicKeywords || firstItem.Tags || firstItem.tags || '';
            
            // Build HTML content from all items in the array
            htmlContent = '';
            
            content.forEach((item, index) => {
                // Create a section for each item
                let itemTitle = '';
                if (item.Item_Position && item.Item_Title) {
                    itemTitle = item.Item_Position + ' - ' + item.Item_Title;
                } else {
                    itemTitle = item.Item_Title;
                }
                
                // Add the item title as a heading
                if (itemTitle) {
                    htmlContent += '<h2>' + itemTitle + '</h2>';
                }
                
                // Add item content
                if (item.Item_Content) {
                    const itemContent = item.Item_Content;
                    // Decode HTML entities and add to content
                    const decodedContent = itemContent.replace(/&#(\d+);/g, (match, dec) => String.fromCharCode(dec));
                    htmlContent += '<div class="item-content">' + decodedContent + '</div>';
                }
                
                // Add images if available
                if (item.images && item.images.length > 0) {
                    htmlContent += '<div class="item-images">';
                    item.images.forEach(imageUrl => {
                        htmlContent += '<img src="' + imageUrl + '" alt="Image" class="img-responsive">';
                    });
                    htmlContent += '</div>';
                }
                // Add a separator between items (except the last one)
                if (index < content.length - 1) {
                    htmlContent += '<hr class="item-separator">';
                }
            });
        } else if (typeof content === 'object') {
            // Object structure
            console.log('Processing object data');
            title = content.title || content.Title || content.Item_Title || '';
            description = content.description || content.Description || content.Summary || '';
            htmlContent = content.content || content.Content || content.draft_content || content.Item_Content || '';
            tags = content.tags || content.Tags || content.TopicKeywords || '';
        } else if (typeof content === 'string') {
            // String content (probably HTML)
            console.log('Processing string data');
            htmlContent = content;
            title = 'Draft Content'; // Default title
        }
        
        // console.log('Extracted content:', {
        //     title: title,
        //     description: description,
        //     contentLength: htmlContent.length,
        //     tags: tags
        // });
        
        // Set the hidden form fields for later use
        $('#draft-title').val(title);
        $('#draft-description').val(description);
        $('#draft-tags').val(tags);
        
        // Create WordPress-style layout in an iframe
        createWordPressStylePreview(title, description, htmlContent, tags);
        
        // Update statistics after loading content
        setTimeout(function() {
            window.DraftWriter.updateStatistics();
        }, 500);
        
        console.log('Content loaded successfully');
    }

    // Function to create WordPress-style preview in an iframe
    function createWordPressStylePreview(title, description, htmlContent, tags) {
        // Remove any existing iframe
        $('#wordpress-preview-iframe').remove();
        
        // Create iframe container if it doesn't exist
        if (!$('#wordpress-preview-container').length) {
            $('.content-section').html('<div id="wordpress-preview-container" class="wordpress-preview-container"></div>');
        }
        
        // Create the iframe
        const iframe = document.createElement('iframe');
        iframe.id = 'wordpress-preview-iframe';
        iframe.style.width = '100%';
        iframe.style.height = '600px';
        iframe.style.border = 'none';
        iframe.title = 'WordPress Preview';
        
        // Add iframe to container
        $('#wordpress-preview-container').append(iframe);
        
        // Clean up the title and description (decode HTML entities but keep them as text)
        const cleanTitle = cleanTextContent(title);
        const cleanDescription = cleanTextContent(description);
        
        // Prepare tags display
        let tagsHtml = '';
        if (tags) {
            const tagsList = cleanTextContent(tags).split(',').map(tag => tag.trim()).filter(Boolean);
            if (tagsList.length > 0) {
                tagsHtml = '<div class="wp-tags">' +
                    tagsList.map(tag => `<span class="tag">${escapeHtml(tag)}</span>`).join('') +
                    '</div>';
            }
        }
        
        // Create WordPress-style HTML content
        const wpHtml = `
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>${cleanTitle}</title>
                <style>
                    body {
                        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
                        color: #333;
                        line-height: 1.6;
                        margin: 0;
                        padding: 20px;
                        background: #f9f9f9;
                    }
                    .wp-container {
                        max-width: 100%;
                        margin: 0 auto;
                        background: #fff;
                        padding: 30px;
                        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                        border-radius: 4px;
                    }
                    .wp-title {
                        font-size: 2.2em;
                        font-weight: 600;
                        line-height: 1.2;
                        margin: 0 0 15px 0;
                        color: #23282d;
                    }
                    .wp-description {
                        font-size: 1.2em;
                        line-height: 1.6;
                        margin-bottom: 25px;
                        color: #555;
                        font-style: italic;
                        border-bottom: 1px solid #eee;
                        padding-bottom: 15px;
                    }
                    .wp-content {
                        margin-bottom: 30px;
                    }
                    .wp-content h2 {
                        font-size: 1.8em;
                        margin: 30px 0 15px;
                        padding-bottom: 10px;
                        border-bottom: 1px solid #f1f1f1;
                        color: #23282d;
                    }
                    .wp-content h3 {
                        font-size: 1.5em;
                        margin: 25px 0 10px;
                        color: #23282d;
                    }
                    .wp-content p {
                        margin: 0 0 20px;
                        font-size: 16px;
                        line-height: 1.8;
                    }
                    .wp-content img {
                        max-width: 100%;
                        height: auto;
                        display: block;
                        margin: 20px auto;
                        border-radius: 4px;
                    }
                    .wp-content ul, .wp-content ol {
                        margin: 0 0 20px 20px;
                        padding: 0;
                    }
                    .wp-content li {
                        margin-bottom: 10px;
                    }
                    .wp-content blockquote {
                        margin: 20px 0;
                        padding: 10px 20px;
                        border-left: 4px solid #0073aa;
                        background: #f9f9f9;
                        font-style: italic;
                    }
                    .wp-content pre {
                        background: #f5f5f5;
                        padding: 15px;
                        border-radius: 4px;
                        overflow-x: auto;
                        font-family: Consolas, Monaco, 'Andale Mono', monospace;
                        font-size: 14px;
                    }
                    .wp-tags {
                        margin-top: 30px;
                        padding-top: 20px;
                        border-top: 1px solid #eee;
                    }
                    .wp-tags .tag {
                        display: inline-block;
                        background: #f1f1f1;
                        padding: 5px 10px;
                        margin: 0 5px 5px 0;
                        border-radius: 3px;
                        font-size: 14px;
                        color: #23282d;
                        text-decoration: none;
                    }
                    .wp-meta {
                        margin-top: 20px;
                        font-size: 14px;
                        color: #777;
                    }
                    .preview-badge {
                        position: fixed;
                        top: 10px;
                        right: 10px;
                        background: rgba(0,115,170,0.8);
                        color: white;
                        padding: 5px 10px;
                        border-radius: 3px;
                        font-size: 12px;
                        z-index: 1000;
                    }
                    a {
                        color: #0073aa;
                        text-decoration: none;
                    }
                    a:hover {
                        color: #00a0d2;
                        text-decoration: underline;
                    }
                    .wp-content table {
                        border-collapse: collapse;
                        width: 100%;
                        margin-bottom: 20px;
                    }
                    .wp-content table, .wp-content th, .wp-content td {
                        border: 1px solid #ddd;
                    }
                    .wp-content th, .wp-content td {
                        padding: 12px;
                        text-align: left;
                    }
                    .wp-content th {
                        background-color: #f8f8f8;
                    }
                    .wp-content tr:nth-child(even) {
                        background-color: #f9f9f9;
                    }
                    /* Additional styles for items */
                    .item-content {
                        margin-bottom: 20px;
                    }
                    .item-images {
                        display: flex;
                        flex-wrap: wrap;
                        gap: 10px;
                        margin: 20px 0;
                    }
                    .item-images img {
                        max-width: 48%;
                        height: auto;
                        object-fit: cover;
                        border-radius: 4px;
                        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                    }
                    .item-specs {
                        margin: 20px 0;
                        background: #f9f9f9;
                        padding: 15px;
                        border-radius: 5px;
                    }
                    .specs-table {
                        width: 100%;
                        border-collapse: collapse;
                    }
                    .specs-table tr td {
                        padding: 8px 12px;
                        border-bottom: 1px solid #eee;
                    }
                    .specs-table tr:last-child td {
                        border-bottom: none;
                    }
                    .specs-table td:first-child {
                        width: 40%;
                        font-weight: 600;
                    }
                    .item-separator {
                        border: 0;
                        height: 1px;
                        background: #eee;
                        margin: 30px 0;
                    }
                </style>
            </head>
            <body>
                <div class="preview-badge">Preview Mode</div>
                <div class="wp-container">
                    <h1 class="wp-title">${cleanTitle}</h1>
                    <div class="wp-description">${cleanDescription}</div>
                    <div class="wp-content"></div>
                    ${tagsHtml}
                    <div class="wp-meta">
                        <span>Published: ${new Date().toLocaleDateString()}</span>
                    </div>
                </div>
            </body>
            </html>
        `;

        // Set the content of the iframe
        const iframeDoc = iframe.contentWindow.document;
        iframeDoc.open();
        iframeDoc.write(wpHtml);
        iframeDoc.close();

        // Add the content after iframe is loaded using a separate script
        iframe.onload = function() {
            const iframeWindow = iframe.contentWindow;
            const wpContent = iframeWindow.document.querySelector('.wp-content');
            if (wpContent) {
                // Set the HTML content directly to preserve all formatting
                wpContent.innerHTML = htmlContent;
                
                // Store the original content in hidden field for processing
                $('#draft-content').val(htmlContent);
                
                // Add message above iframe
                $('#wordpress-preview-container').prepend(
                    '<div class="preview-mode-notice alert alert-info" style="margin-bottom:15px;">' +
                    '<i class="fa fa-wordpress"></i> ' +
                    'Content is displayed in WordPress-style format. Use the Ultimate Editor for editing.' +
                    '</div>'
                );
            }

            // Hide the preview-mode-notice after content is loaded
            setTimeout(function() {
                $('#wordpress-preview-container .preview-mode-notice').fadeOut(400, function() { $(this).remove(); });
            }, 3000); // Give a longer delay to read the message
        };
    }
}

// Add event handlers for Ultimate Editor integration
window.DraftWriter.initUltimateEditorIntegration = function() {
    // Handler for Preview in Ultimate Editor button
    $('#preview-in-ultimate-editor, #open-in-ultimate-editor-top').on('click', function() {
        // Get the current content
        const content = {
            title: $('#draft-title').val(),
            description: $('#draft-description').val(),
            content: tinymce.get('draft-content') ? tinymce.get('draft-content').getContent() : $('#draft-content').val(),
            tags: $('#draft-tags').val(),
            category: $('#draft-category').val()
        };
        
        // Open in preview mode
        openInUltimateEditor(content, 'preview');
    });
    
    // Handler for Open in Ultimate Editor button
    $('#open-in-ultimate-editor').on('click', function() {
        // Get the current content
        const content = {
            title: $('#draft-title').val(),
            description: $('#draft-description').val(),
            content: tinymce.get('draft-content') ? tinymce.get('draft-content').getContent() : $('#draft-content').val(),
            tags: $('#draft-tags').val(),
            category: $('#draft-category').val()
        };
        
        // Open in edit mode
        openInUltimateEditor(content, 'edit');
    });
};

// Add styles for WordPress preview container
window.DraftWriter.addWordPressPreviewStyles = function() {
    const style = document.createElement('style');
    style.id = 'wordpress-preview-styles';
    style.textContent = `
        .wordpress-preview-container {
            margin-bottom: 20px;
        }
        
        #wordpress-preview-iframe {
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border-radius: 4px;
            transition: all 0.3s ease;
        }
        
        .stats-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
        }
        
        .stat-item {
            flex: 0 0 48%;
            margin-bottom: 5px;
            padding: 8px;
            background: #f9f9f9;
            border-radius: 3px;
            font-size: 14px;
        }
        
        .stats-row {
            margin-top: 20px;
        }
        
        .ultimate-editor-banner {
            background: #f1f8e9;
            border-left: 4px solid #8bc34a;
            padding: 10px 15px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .ultimate-editor-banner i {
            margin-right: 5px;
            color: #8bc34a;
        }
    `;
    document.head.appendChild(style);
};

</script> 