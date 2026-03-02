/**
 * Ultimate Editor Action Buttons Module
 * 
 * This module handles action buttons functionality for Ultimate Editor,
 * following the same pattern as the PHP processor files:
 * - ImageGenerateToggleProcessor
 * - InitGooglesheetRawItemProcessor
 * - SocialMediaPostActionProcessor
 * - TopicComposerProcessor
 * - WordPressPostActionProcessor
 * - WordPressPostSelectionProcessor
 */



/**
 * Action namespace for UltimateEditorPublish
 * Handles all action types for Ultimate Editor buttons
 */
UltimateEditorPublish.Action = {
    // Current button that was clicked
    currentButton: null,
    
    // Current session/workflow data
    currentSession: null,
    
    // Polling timer for long-running operations
    pollingTimer: null,
    
    /**
     * Initialize action button handlers
     */
    init: function() {
        'use strict';
        // Initialize action buttons event binding for document-level elements
        UltimateEditorPublish.bindActionButtonEvents();
        
        // // Bind controller select change event
        // this.bindEvents_ControllerSelect();
        
        console.log('Ultimate Editor Action Buttons initialized');
    },
    
    /**
     * Consolidated function to bind events to controller select
     * This function handles all the change events for #topic-controller-select in one place
     */
    bindEvents_ControllerSelect: function() {
        console.log('Binding events to controller select');
        // Remove existing event handlers to prevent duplicates
        $(document).off('change', '#topic-controller-select');
        $('#topic-controller-select').off('change');
        
        // Add single delegated event handler
        $(document).on('change', '#topic-controller-select', function() {
            const $select = $(this);
            const controllerId = $select.val();
            
            console.log('Controller select changed to:', controllerId);
            
            if (controllerId) {
                // Get controller data
                const $option = $select.find('option:selected');
                const platform = $option.data('platform');
                const connected = $option.data('connected') === 'true';
                
                // Store selected controller in global namespace
                if (window.DraftWriter) {
                    window.DraftWriter.publish = window.DraftWriter.publish || {};
                    window.DraftWriter.publish.selectedController = {
                        id: controllerId,
                        platform: platform,
                        connected: connected
                    };
                }
                
                // Update UI
                $('#controller-info').removeClass('hide');
                $('#platform-name').text(platform || '');
                
                // Load dependent data
                if (typeof loadCategories === 'function') {
                    loadCategories(controllerId);
                }
                
                if (typeof loadTags === 'function') {
                    loadTags(controllerId);
                }
                
                // Update permalink prefix
                if (typeof updatePermalinkPrefix === 'function') {
                    updatePermalinkPrefix(platform);
                }
                
                // Check if post exists if we have a title
                const title = $('#draft-title').val();
                if (title && typeof UltimateEditorPublish.checkPostExistence === 'function') {
                    UltimateEditorPublish.checkPostExistence(controllerId, title);
                }
                
                // Update post preview
                if (typeof updatePostPreview === 'function') {
                    updatePostPreview();
                }
                
                // Load action buttons for this controller
                if (typeof UltimateEditorPublish.renderActionButtons === 'function') {
                    UltimateEditorPublish.renderActionButtons();
                }
                
                // Call UltimateEditorPublish.controllerSelected if it exists
                if (typeof UltimateEditorPublish.controllerSelected === 'function') {
                    UltimateEditorPublish.controllerSelected(controllerId);
                }
            } else {
                // Handle clearing UI when no controller is selected
                $('#controller-info').addClass('hide');
                $('#categories-tree').html('');
                
                // Clean up tags select2 if it exists
                if ($('#tags-select').data('select2')) {
                    $('#tags-select').select2('destroy');
                }
                
                $('#tags-select').html('');
                $('#popular-tags-list').html('');
                $('#post-existence-check').addClass('hide');
                
                // Clear stored data
                if (window.DraftWriter && window.DraftWriter.publish) {
                    window.DraftWriter.publish.selectedController = null;
                    window.DraftWriter.publish.categories = [];
                    window.DraftWriter.publish.tags = [];
                }
                
                // Call UltimateEditorPublish.clearControllerData if it exists
                if (typeof UltimateEditorPublish.clearControllerData === 'function') {
                    UltimateEditorPublish.clearControllerData();
                }
            }
            
            // Trigger a custom event for others to hook into
            $(document).trigger('controller.changed', [controllerId]);
        });
        
        return this;
    },
    
    /**
     * Handle action button click
     * @param {jQuery} $button The button that was clicked
     */
    handleButtonClick: function($button) {
        'use strict';
        
        // Store current button
        this.currentButton = $button;
        
        // Get action data from button attributes
        const actionData = {
            target_type: $button.data('target-type'),
            target_state: $button.data('target-state'),
            action_command: $button.data('action-command'),
            workflow_id: $button.data('workflow-id'),
            button_id: $button.data('button-id'),
            controller_id: $button.data('controller-id')
        };
        
        // If controller_id is not in the button data, try to get it from the select
        if (!actionData.controller_id) {
            actionData.controller_id = $('#topic-controller-select').val() || null;
        }
        
        // Set button loading state
        this.setButtonLoading($button, true);
        
        // Process action based on Factory pattern (similar to PHP TopicActionProcessorFactory)
        this.processActionByType(actionData, $button);
    },
    
    /**
     * Process action by type using Factory pattern
     * Mimics the PHP TopicActionProcessorFactory::create method
     * @param {object} actionData Action data
     * @param {jQuery} $button The button that was clicked
     */
    processActionByType: function(actionData, $button) {
        'use strict';
        
        // Log action information
        console.log(`Processing action - Type: ${actionData.target_type}, State: ${actionData.target_state}, Command: ${actionData.action_command}`);
        
        // Set button to loading state
        this.setButtonLoading($button, true);
        
        // Simply call the generic action handler - no specific cases
        this.processGenericAction(actionData, $button);
    },
    
    /**
     * Process ImageGenerateToggle action
     * @param {object} actionData Action data
     * @param {jQuery} $button The button that was clicked
     */
    processImageGenerateToggle: function(actionData, $button) {
        'use strict';
        // This will be implemented based on ImageGenerateToggleProcessor.php
        console.log('Processing ImageGenerateToggle action');
        
        // For now, default to generic implementation
        this.processGenericAction(actionData, $button);
    },
    
    /**
     * Process InitGooglesheetRawItem action
     * @param {object} actionData Action data
     * @param {jQuery} $button The button that was clicked
     */
    processInitGooglesheetRawItem: function(actionData, $button) {
        'use strict';
        // This will be implemented based on InitGooglesheetRawItemProcessor.php
        console.log('Processing InitGooglesheetRawItem action');
        
        // For now, default to generic implementation
        this.processGenericAction(actionData, $button);
    },
    
    /**
     * Process TopicComposer action
     * @param {object} actionData Action data
     * @param {jQuery} $button The button that was clicked
     */
    processTopicComposer: function(actionData, $button) {
        'use strict';
        // This will be implemented based on TopicComposerProcessor.php
        console.log('Processing TopicComposer action');
        
        // For now, default to generic implementation
        this.processGenericAction(actionData, $button);
    },
    
    /**
     * Process WordPress Post action
     * @param {object} actionData Action data
     * @param {jQuery} $button The button that was clicked
     */
    processWordPressPost: function(actionData, $button) {
        'use strict';
        // This will be implemented based on WordPressPostActionProcessor.php
        console.log('Processing WordPress Post action');
        
        // For now, default to generic implementation
        this.processGenericAction(actionData, $button);
    },
    
    /**
     * Process WordPress Post Selection action
     * @param {object} actionData Action data
     * @param {jQuery} $button The button that was clicked
     */
    processWordPressPostSelection: function(actionData, $button) {
        'use strict';
        // This will be implemented based on WordPressPostSelectionProcessor.php
        console.log('Processing WordPress Post Selection action');
        
        // For now, default to generic implementation
        this.processGenericAction(actionData, $button);
    },
    
    /**
     * Process Social Media Post action
     * @param {object} actionData Action data
     * @param {jQuery} $button The button that was clicked
     */
    processSocialMediaPost: function(actionData, $button) {
        'use strict';
        // This will be implemented based on SocialMediaPostActionProcessor.php
        console.log('Processing Social Media Post action');
        
        // For now, default to generic implementation
        this.processGenericAction(actionData, $button);
    },
    
    /**
     * Process Draft Writing action
     * @param {object} actionData Action data
     * @param {jQuery} $button The button that was clicked
     */
    processDraftWriting: function(actionData, $button) {
        'use strict';
        // This will be implemented based on DraftWritingProcessor.php
        console.log('Processing Draft Writing action');
        
        // For now, default to generic implementation
        this.processGenericAction(actionData, $button);
    },
    
    /**
     * Process generic action (fallback for unhandled types)
     * @param {object} actionData Action data
     * @param {jQuery} $button The button that was clicked
     */
    processGenericAction: function(actionData, $button) {
        'use strict';
        
        // Get topic ID if needed (optional for Ultimate Editor)
        const topicId = $('#current-topic_id').val();
        
        // Display action information in the publish status area
        $('#publish-status-message').html(`
            <div class="alert alert-info">
                <strong>Processing Action:</strong><br>
                <ul class="mt-2 mb-2">
                    <li><strong>Action Type:</strong> ${actionData.target_type}</li>
                    <li><strong>Action State:</strong> ${actionData.target_state}</li>
                    <li><strong>Action Command:</strong> ${actionData.action_command}</li>
                    <li><strong>Workflow ID:</strong> ${actionData.workflow_id || 'N/A'}</li>
                    <li><strong>Button ID:</strong> ${actionData.button_id || 'N/A'}</li>
                    <li><strong>Controller ID:</strong> ${actionData.controller_id || 'Not selected'}</li>
                </ul>
                <div class="mt-2">
                    <i class="fa fa-spinner fa-spin"></i> Processing request, please wait...
                </div>
            </div>
        `);
        
        // Prepare data for AJAX request - start with basic action data
        const data = {
            target_type: actionData.target_type,
            target_state: actionData.target_state,
            action_command: actionData.action_command,
            workflow_id: actionData.workflow_id,
            button_id: actionData.button_id,
            controller_id: actionData.controller_id
        };
        
        // Optionally add topic_id if available
        if (topicId) {
            data.topic_id = topicId;
        }
        
        // Debug TinyMCE editor access
        console.log('TinyMCE loaded:', typeof tinymce !== 'undefined');
        if (typeof tinymce !== 'undefined') {
            console.log('Active editor:', tinymce.activeEditor);
            // More defensive check for editors property
            console.log('Editors object:', tinymce.editors);
            if (tinymce.editors && typeof tinymce.editors === 'object') {
                console.log('Editor IDs:', Object.keys(tinymce.editors).join(', '));
            } else {
                console.log('No editors object available');
            }
        }
        
        // IMPROVED: Collect preview content information with better TinyMCE support
        let editorContent = '';
        
        // Method 1: Try accessing TinyMCE directly if available
        if (typeof tinymce !== 'undefined' && tinymce.activeEditor) {
            editorContent = tinymce.activeEditor.getContent();
            console.log('Retrieved content from tinymce.activeEditor', editorContent.substring(0, 100) + '...');
        } 
        // Method 2: Try using any available TinyMCE editor - with more defensive checks
        else if (typeof tinymce !== 'undefined' && tinymce.editors && typeof tinymce.editors === 'object') {
            try {
                const editorKeys = Object.keys(tinymce.editors);
                if (editorKeys.length > 0) {
                    const editorId = editorKeys[0];
                    editorContent = tinymce.editors[editorId].getContent();
                    console.log('Retrieved content from tinymce.editors['+editorId+']', editorContent.substring(0, 100) + '...');
                }
            } catch (e) {
                console.error('Error accessing TinyMCE editors:', e);
            }
        }
        // Method 3: Try previous method as fallback
        else if (typeof window.getEditorContent === 'function') {
            editorContent = window.getEditorContent();
            console.log('Retrieved content from window.getEditorContent()', editorContent.substring(0, 100) + '...');
        }
        // Method 4: Try getting from textarea as last resort
        else if ($('#draft-content').length) {
            editorContent = $('#draft-content').val();
            console.log('Retrieved content from #draft-content', editorContent.substring(0, 100) + '...');
        }
        // Method 5: If in publish modal, try getting from preview
        else if ($('.modal.show .modal-body').length) {
            editorContent = $('.modal.show .modal-body').html();
            console.log('Retrieved content from modal preview', editorContent.substring(0, 100) + '...');
        }
        // Method 6: Get content from modal directly for this specific case
        else if ($('dialog.modal-dialog-centered').length) {
            const modalContent = $('dialog.modal-dialog-centered').find('.modal-body').html();
            if (modalContent) {
                editorContent = modalContent;
                console.log('Retrieved content from dialog modal', editorContent.substring(0, 100) + '...');
            }
        }
        
        data.content = {
            title: $('#draft-title').val() || $('h1:first, h3:first').text() || '',
            content: editorContent,
            excerpt: '',
            meta_description: '',
            keywords: ''
        };

        // Create separate thumbnail object
        data.thumbnail = {
            url: $('#feature-image-url').val() || $('#featured-image-url').val() || '',
            id: $('#feature-image-id').val() || $('#featured-image-id').val() || ''
        };

        // Try to get thumbnail from various sources if not found
        if (!data.thumbnail.url) {
            // Try modal sources
            data.thumbnail.url = $('.modal.show img.featured-image').attr('src') || 
                                $('.publish-modal img.featured-image').attr('src') || 
                                $('img.featured-image').attr('src') || '';
            
            // Try first image in preview
            if (!data.thumbnail.url) {
                data.thumbnail.url = $('.modal.show img:first').attr('src') || 
                                    $('.publish-modal img:first').attr('src') || 
                                    $('dialog.modal-dialog-centered img:first').attr('src') || '';
            }
            
            // Try to get from topic external data (similar to loadFeatureImageFromExternalData)
            const topicId = $('#topic_id').val() || $('#topicid').val() || $('input[name="topic_id"]').val();
            if (topicId && !data.thumbnail.url) {
                console.log('Attempting to fetch featured image from external data for topic ID:', topicId);
                
                // Use a synchronous AJAX call to get the feature image
                // Note: This is not ideal for performance but ensures we get the image before proceeding
                $.ajax({
                    url: admin_url + 'topics/get_external_data',
                    type: 'POST',
                    async: false,
                    data: {
                        topic_id: topicId,
                        rel_type: 'feature_image',
                        rel_id: '1' // Feature image uses rel_id 1
                    },
                    success: function(response) {
                        try {
                            if (typeof response === 'string') {
                                response = JSON.parse(response);
                            }
                            
                            if (response.success && response.data && response.data.rel_data) {
                                data.thumbnail.url = response.data.rel_data;
                                console.log('Successfully fetched featured image from external data:', data.thumbnail.url);
                            }
                        } catch (e) {
                            console.error('Error parsing featured image response:', e);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX error loading featured image:', error);
                    }
                });
            }
        }
        
        // Update debugging
        console.log('Thumbnail data:', data.thumbnail);

        // Using the approach from applyContentToEditor function
        const mainSummary = $('#draft-description').val() || $('#draft-excerpt').val() || $('.modal.show #excerpt-preview').text() || $('.publish-modal #excerpt-preview').text() || $('.draft-excerpt').text() || $('.modal.show .modal-body p:first').text() || $('p:first').text() || '';
        const topicKeywords = $('#draft-tags').val() || $('#keywords').val() || $('.modal.show #keywords-preview').text() || $('.publish-modal #keywords-preview').text() || $('.keywords').text() || '';
        
        // Set excerpt, meta_description and keywords
        data.content.excerpt = mainSummary;
        data.content.meta_description = $('#meta-description').val() || mainSummary;
        data.content.keywords = topicKeywords;
        
        // Try to find excerpt from modal description directly
        if (!data.content.excerpt || data.content.excerpt === "No started timers found") {
            // Look in the publish modal for description
            const modalDescription = $('.modal.show .draft-description').text() || 
                                    $('.publish-modal .draft-description').text() || 
                                    $('dialog.modal-dialog-centered .draft-description').text();
            
            if (modalDescription && modalDescription.length > 0) {
                console.log('Found excerpt from modal description element:', modalDescription.substring(0, 100) + '...');
                data.content.excerpt = modalDescription;
            }
        }
        
        // Last resort for excerpt - take it from the DraftWriter object if available
        if ((!data.content.excerpt || data.content.excerpt === "No started timers found") && 
            window.DraftWriter && window.DraftWriter.topicData) {
            console.log('Trying to get excerpt from DraftWriter object');
            
            if (window.DraftWriter.topicData.excerpt) {
                data.content.excerpt = window.DraftWriter.topicData.excerpt;
                console.log('Found excerpt in DraftWriter.topicData:', data.content.excerpt.substring(0, 100) + '...');
            } else if (window.DraftWriter.topicData.description) {
                data.content.excerpt = window.DraftWriter.topicData.description;
                console.log('Found description in DraftWriter.topicData:', data.content.excerpt.substring(0, 100) + '...');
            }
        }
        
        // Debug information to see what values were found
        console.log('Content field details:', {
            title_length: data.content.title.length,
            content_length: data.content.content.length,
            excerpt_length: data.content.excerpt.length,
            meta_description_length: data.content.meta_description.length,
            keywords_length: data.content.keywords.length,
            has_thumbnail: !!data.thumbnail.url
        });
        
        // ADDED: Collect publish options
        data.publish_options = {
            status: $('#post-status').val() || 'draft',
            visibility: $('#post-visibility').val() || 'public',
            schedule_date: $('#schedule-date').val() || '',
            schedule_time: $('#schedule-time').val() || '',
        };
        
        // ADDED: Collect categories
        const categories = [];
        // Get selected categories from tree
        $('.category-node input:checked').each(function() {
            const $node = $(this).closest('.category-node');
            const categoryId = $(this).val();
            
            // Get category data from our stored categories if available
            let categoryData = { 
                id: categoryId,
                name: $node.data('name') || $node.find('.category-label').text().split('(')[0].trim(),
                slug: $node.data('slug') || '',
                parent: $node.data('parent') || 0,
                taxonomy: $node.data('taxonomy') || 'category'
            };
            
            // Try to get more complete data from the DraftWriter global object if available
            if (window.DraftWriter && 
                window.DraftWriter.publish && 
                window.DraftWriter.publish.categories) {
                
                // Find this category in the stored categories
                const storedCategory = window.DraftWriter.publish.categories.find(
                    cat => (cat.id == categoryId || cat.category_id == categoryId)
                );
                
                if (storedCategory) {
                    categoryData = {
                        id: categoryId,
                        name: storedCategory.name || categoryData.name,
                        slug: storedCategory.slug || storedCategory.name.toLowerCase().replace(/\s+/g, '-') || categoryData.slug,
                        parent: storedCategory.parent_id || storedCategory.parent || categoryData.parent,
                        taxonomy: storedCategory.taxonomy || categoryData.taxonomy,
                        description: storedCategory.description || '',
                        count: storedCategory.count || 0
                    };
                }
            }
            
            categories.push(categoryData);
        });
        data.categories = categories;
        
        // ADDED: Collect tags
        const tags = [];
        // Get tags from select2 if it exists
        if ($('#tags-select').length && $('#tags-select').data('select2')) {
            const selectedTags = $('#tags-select').select2('data');
            if (selectedTags && selectedTags.length) {
                selectedTags.forEach(function(tag) {
                    const tagData = {
                        id: tag.id,
                        name: tag.text,
                        slug: tag.slug || tag.text.toLowerCase().replace(/\s+/g, '-')
                    };
                    
                    // Try to find more data from stored tags
                    if (window.DraftWriter && 
                        window.DraftWriter.publish && 
                        window.DraftWriter.publish.tags &&
                        window.DraftWriter.publish.tags.tags) {
                        
                        const storedTag = window.DraftWriter.publish.tags.tags.find(
                            t => t.id == tag.id || t.term_id == tag.id
                        );
                        
                        if (storedTag) {
                            // Add additional data if available
                            tagData.taxonomy = storedTag.taxonomy || 'post_tag';
                            tagData.description = storedTag.description || '';
                            tagData.count = storedTag.count || 0;
                        }
                    }
                    
                    tags.push(tagData);
                });
            }
        }
        // Also check for tags listed in popular-tags-list that are selected
        $('.popular-tag.selected').each(function() {
            const tagId = $(this).data('id');
            const tagName = $(this).data('name') || $(this).text();
            
            // Check if this tag is already in the array
            const exists = tags.some(tag => tag.id === tagId);
            if (!exists) {
                const tagData = {
                    id: tagId,
                    name: tagName,
                    slug: $(this).data('slug') || tagName.toLowerCase().replace(/\s+/g, '-')
                };
                
                // Try to find more data from stored tags
                if (window.DraftWriter && 
                    window.DraftWriter.publish && 
                    window.DraftWriter.publish.tags &&
                    window.DraftWriter.publish.tags.tags) {
                    
                    const storedTag = window.DraftWriter.publish.tags.tags.find(
                        t => t.id == tagId || t.term_id == tagId
                    );
                    
                    if (storedTag) {
                        // Add additional data if available
                        tagData.taxonomy = storedTag.taxonomy || 'post_tag';
                        tagData.description = storedTag.description || '';
                        tagData.count = storedTag.count || 0;
                    }
                }
                
                tags.push(tagData);
            }
        });
        data.tags = tags;
        
        // ADDED: Collect additional data
        data.additional_data = {
            permalink: $('#permalink').val() || '',
            author_id: $('#post-author').val() || '',
            controller_platform: $('#topic-controller-select option:selected').data('platform') || '',
            controller_name: $('#topic-controller-select option:selected').text() || ''
        };
        
        // Log the complete data being sent
        console.log('Sending action data:', data);
        
        // Send AJAX request to process action
        $.ajax({
            url: admin_url + 'topics/ultimate_editor/process_action',
            type: 'POST',
            data: data,
            dataType: 'json',
            success: (response) => {
                // Handle success
                this.handleActionResponse(response, actionData);
                
                // Update publish status with response data
                if (response.success) {
                    let dataDisplay = '';
                    if (response.data) {
                        // Format data nicely if it exists
                        if (typeof response.data === 'object') {
                            dataDisplay = '<ul class="mt-2">';
                            for (const key in response.data) {
                                if (response.data.hasOwnProperty(key)) {
                                    const value = response.data[key];
                                    // Format value based on type
                                    let displayValue = value;
                                    if (typeof value === 'object' && value !== null) {
                                        displayValue = '<pre class="mt-1 mb-1">' + JSON.stringify(value, null, 2) + '</pre>';
                                    } else if (typeof value === 'boolean') {
                                        displayValue = value ? 'Yes' : 'No';
                                    }
                                    dataDisplay += `<li><strong>${key}:</strong> ${displayValue}</li>`;
                                }
                            }
                            dataDisplay += '</ul>';
                        } else {
                            dataDisplay = `<div class="mt-2">${JSON.stringify(response.data)}</div>`;
                        }
                    }
                    
                    $('#publish-status-message').html(`
                        <div class="alert alert-success">
                            <strong>Action Completed Successfully</strong>
                            <div class="mt-2 mb-2">${response.message}</div>
                            ${dataDisplay}
                        </div>
                    `);
                } else {
                    let errorDetails = '';
                    if (response.data && response.data.error_details) {
                        if (typeof response.data.error_details === 'object') {
                            errorDetails = `<pre class="mt-2 mb-0">${JSON.stringify(response.data.error_details, null, 2)}</pre>`;
                        } else {
                            errorDetails = `<div class="mt-2 text-danger">${response.data.error_details}</div>`;
                        }
                    }
                    
                    $('#publish-status-message').html(`
                        <div class="alert alert-danger">
                            <strong>Action Failed</strong>
                            <div class="mt-2 mb-2">${response.message}</div>
                            ${errorDetails}
                        </div>
                    `);
                }
            },
            error: (xhr, status, error) => {
                // Handle error
                alert_float('danger', app.lang.error_processing_action || 'Error processing action');
                console.error('Action processing error:', error);
                
                // Try to parse error response if possible
                let errorMessage = error;
                let errorDetails = '';
                
                try {
                    if (xhr.responseText) {
                        const errorResponse = JSON.parse(xhr.responseText);
                        if (errorResponse.message) {
                            errorMessage = errorResponse.message;
                        }
                        if (errorResponse.data && errorResponse.data.error_details) {
                            errorDetails = `<div class="mt-2">${errorResponse.data.error_details}</div>`;
                        }
                    }
                } catch (e) {
                    // Cannot parse JSON, use default error message
                    errorDetails = `<div class="mt-2">Status: ${status}, Error: ${error}</div>`;
                    if (xhr.responseText) {
                        errorDetails += `<div class="mt-2"><pre>${xhr.responseText.substring(0, 500)}</pre></div>`;
                    }
                }
                
                // Update publish status with error
                $('#publish-status-message').html(`
                    <div class="alert alert-danger">
                        <strong>Error Processing Action</strong>
                        <div class="mt-2 mb-2">${errorMessage}</div>
                        ${errorDetails}
                    </div>
                `);
                
                // Reset button state
                this.setButtonLoading($button, false);
            }
        });
    },
    
    /**
     * Handle action response from server
     * @param {object} response Response from server
     * @param {object} actionData Original action data
     */
    handleActionResponse: function(response, actionData) {
        'use strict';
        
        // Reset button state if not a long-running operation
        if (!response.data || !response.data.needs_polling) {
            this.setButtonLoading(this.currentButton, false);
        }
        
        if (response.success) {
            // Show success message
            alert_float('success', response.message);
            
            // Handle special responses
            if (response.data) {
                // Handle polling for long-running operations
                if (response.data.needs_polling && 
                    response.data.workflow_id && 
                    response.data.execution_id) {
                    this.startPolling(
                        response.data.workflow_id, 
                        response.data.execution_id,
                        actionData
                    );
                    return;
                }
                
                // Show selection form if needed
                if (response.data.show_selection) {
                    this.handleSelectionForm(response.data, actionData);
                    return;
                }
                
                // Handle redirect
                if (response.data.redirect) {
                    setTimeout(function() {
                        window.location.href = response.data.redirect;
                    }, 2000);
                }
                
                // Handle reload
                if (response.data.reload) {
                    setTimeout(function() {
                        window.location.reload();
                    }, 2000);
                }
            }
        } else {
            // Show error message
            alert_float('danger', response.message);
            
            // Show detailed error if available
            if (response.data && response.data.error_details) {
                console.error('Error details:', response.data.error_details);
            }
            
            // Check if we need to assign a controller
            if (response.data && response.data.needs_controller) {
                alert_float('warning', app.lang.topic_needs_controller || 'This topic needs a controller assigned before performing this action');
            }
        }
    },
    
    /**
     * Start polling for long-running operations
     * @param {string} workflowId Workflow ID
     * @param {string} executionId Execution ID
     * @param {object} actionData Original action data
     */
    startPolling: function(workflowId, executionId, actionData) {
        'use strict';
        
        console.log(`Starting polling for workflow ${workflowId}, execution ${executionId}`);
        
        // Store current session data
        this.currentSession = {
            workflow_id: workflowId,
            execution_id: executionId,
            action_data: actionData,
            poll_count: 0,
            max_polls: 30, // 5 minutes at 10-second intervals
            interval: 10000 // 10 seconds
        };
        
        // Clear any existing timer
        if (this.pollingTimer) {
            clearTimeout(this.pollingTimer);
        }
        
        // Start polling
        this.pollWorkflowStatus();
    },
    
    /**
     * Poll for workflow status
     */
    pollWorkflowStatus: function() {
        'use strict';
        
        if (!this.currentSession) return;
        
        const session = this.currentSession;
        session.poll_count++;
        
        console.log(`Polling workflow status (${session.poll_count}/${session.max_polls})`);
        
        // Update button text to show polling status
        this.updateButtonPollingStatus(session.poll_count);
        
        // Check if we've reached the maximum number of polls
        if (session.poll_count > session.max_polls) {
            console.log('Maximum poll count reached, stopping');
            this.stopPolling(true);
            return;
        }
        
        // Get topic ID if available (optional for Ultimate Editor)
        const topicId = $('#topic_id').val();
        
        // Prepare data for AJAX request
        const data = {
            workflow_id: session.workflow_id,
            execution_id: session.execution_id
        };
        
        // Optionally add topic_id if available
        if (topicId) {
            data.topic_id = topicId;
        }
        
        // Make AJAX request to check status
        $.ajax({
            url: admin_url + 'topics/ultimate_editor/check_workflow_status',
            type: 'GET',
            data: data,
            dataType: 'json',
            success: (response) => {
                console.log('Poll response:', response);
                
                if (response.success) {
                    // Check if the workflow is finished
                    if (response.finished) {
                        console.log('Workflow finished, stopping polling');
                        this.stopPolling();
                        
                        // Process the finished workflow result
                        this.processWorkflowResult(response, session.action_data);
                    } else {
                        // Schedule next poll
                        this.pollingTimer = setTimeout(() => {
                            this.pollWorkflowStatus();
                        }, session.interval);
                    }
                } else {
                    // Error occurred, stop polling
                    console.error('Error polling workflow status:', response.message);
                    this.stopPolling(true);
                    
                    // Show error message
                    alert_float('danger', response.message || app.lang.error_checking_workflow);
                }
            },
            error: (xhr, status, error) => {
                console.error('AJAX error polling workflow status:', error);
                
                // If it's just a network error, try again
                // Otherwise stop polling
                if (status === 'timeout' || status === 'error' || status === 'parsererror') {
                    console.log('Retrying after error...');
                    this.pollingTimer = setTimeout(() => {
                        this.pollWorkflowStatus();
                    }, session.interval);
                } else {
                    this.stopPolling(true);
                    alert_float('danger', app.lang.error_checking_workflow || 'Error checking workflow status');
                }
            }
        });
    },
    
    /**
     * Stop polling and reset button
     * @param {boolean} isError Whether the polling stopped due to an error
     */
    stopPolling: function(isError = false) {
        // 'use strict';
        
        // Clear timer
        if (this.pollingTimer) {
            clearTimeout(this.pollingTimer);
            this.pollingTimer = null;
        }
        
        // Reset button state
        this.setButtonLoading(this.currentButton, false);
        
        // Show message if stopped due to error
        if (isError) {
            alert_float('warning', app.lang.workflow_polling_timeout || 'The operation is taking longer than expected. Please check back later.');
        }
        
        // Clear session
        this.currentSession = null;
    },
    
    /**
     * Process workflow result after polling is finished
     * @param {object} response Response from workflow status check
     * @param {object} actionData Original action data
     */
    processWorkflowResult: function(response, actionData) {
        'use strict';
        
        // Handle different success scenarios based on action type
        if (actionData.target_type === 'ImageGenerateToggle') {
            this.processImageGenerateResult(response, actionData);
        } else {
            // Default success handling
            alert_float('success', response.message || app.lang.workflow_completed);
            
            // Check if we need to reload
            if (response.data && response.data.reload) {
                setTimeout(function() {
                    window.location.reload();
                }, 2000);
            }
        }
    },
    
    /**
     * Update button text during polling
     * @param {number} pollCount Current poll count
     */
    updateButtonPollingStatus: function(pollCount) {
        'use strict';
        
        if (!this.currentButton) return;
        
        // Update button text with spinner and poll count
        const loadingHtml = '<i class="fa fa-spinner fa-spin"></i> ' + 
                           (app.lang.checking_status || 'Checking status') + 
                           ` (${pollCount})`;
        
        this.currentButton.html(loadingHtml);
    },
    
    /**
     * Handle selection form for multi-step actions
     * @param {object} data Form data
     * @param {object} actionData Original action data
     */
    handleSelectionForm: function(data, actionData) {
        'use strict';
        
        // Reset button state
        this.setButtonLoading(this.currentButton, false);
        
        const targetType = actionData.target_type;
        
        // Route to appropriate selection handler based on action type
        if (targetType === 'ExecutionTag_ExecAudit' && 
            actionData.target_state === 'ExecutionTag_ExecAudit_SocialAuditCompleted') {
            // Call the specific handler for social media selection
            this.showSocialMediaSelectionForm(data, actionData);
        } 
        else if (targetType === 'ExecutionTag_ExecWriting' && 
                actionData.target_state === 'ExecutionTag_ExecWriting_Complete') {
            // Call the specific handler for WordPress post selection
            this.showWordPressPostSelectionForm(data, actionData);
        }
        else {
            // Generic selection handling
            console.warn('No specific handler for selection form of type:', targetType);
            alert_float('warning', app.lang.selection_not_supported || 'This selection type is not supported yet');
        }
    },
    
    /**
     * Show social media selection form
     * @param {object} data Form data
     * @param {object} actionData Original action data
     */
    showSocialMediaSelectionForm: function(data, actionData) {
        'use strict';
        console.log('Showing social media selection form');
        // To be implemented with specific form structure for social media posts
    },
    
    /**
     * Show WordPress post selection form
     * @param {object} data Form data
     * @param {object} actionData Original action data
     */
    showWordPressPostSelectionForm: function(data, actionData) {
        'use strict';
        console.log('Showing WordPress post selection form');
        // To be implemented with specific form structure for WordPress posts
    },
    
    /**
     * Process specific result for ImageGenerateToggle
     * @param {object} response Response data
     * @param {object} actionData Original action data
     */
    processImageGenerateResult: function(response, actionData) {
        'use strict';
        console.log('Processing image generation result');
        // To be implemented with specific handling for image generation results
    },
    
    /**
     * Set button loading state
     * @param {jQuery} $button Button element
     * @param {boolean} isLoading Whether button is in loading state
     */
    setButtonLoading: function($button, isLoading) {
        'use strict';
        
        if (!$button) return;
        
        const originalHtml = $button.data('original-html');
        const loadingHtml = '<i class="fa fa-spinner fa-spin"></i> ' + 
                           (app.lang.processing || 'Processing...');
        
        if (isLoading) {
            // Save original HTML if not already saved
            if (!originalHtml) {
                $button.data('original-html', $button.html());
            }
            
            // Set loading state
            $button.prop('disabled', true)
                   .html(loadingHtml)
                   .addClass('disabled');
        } else {
            // Restore original HTML
            $button.prop('disabled', false)
                   .html(originalHtml || $button.html())
                   .removeClass('disabled');
        }
    }
};

// Initialize on document ready
$(document).ready(function() {
    UltimateEditorPublish.Action.init();
});

