<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<script>

/**
 * Hàm giải mã chuỗi HTML entities thành mã HTML thường
 * @param {string} encodedText - Chuỗi bị mã hóa HTML entities
 * @returns {string} - Chuỗi đã được giải mã thành mã HTML thường
 */
function decodeHtmlEntities(encodedText) {
    if (!encodedText) return '';

    // Tạo một phần tử div tạm thời để giải mã HTML entities
    const div = document.createElement('div');
    div.innerHTML = encodedText;

    // Trả về nội dung text đã được giải mã
    return div.textContent || div.innerText || '';
}


/**
 * Helper function to clean HTML content
 */
function cleanHtmlContent(content) {
    if (!content) return '';
    
    // Remove any <pre> tags with xss attributes
    content = content.replace(/<pre[^>]*xss[^>]*>([\s\S]*?)<\/pre>/gi, '$1');
    
    // Remove other problematic tags that might be causing display issues
    content = content.replace(/<\/?xss[^>]*>/gi, '');
    
    // Decode HTML entities if needed
    if (content.indexOf('&lt;') !== -1 || content.indexOf('&gt;') !== -1) {
        content = decodeHtmlEntities(content);
    }
    
    return content;
}

/**
 * Display Draft Writing Result
 * Note: CSS for Draft Writer will be loaded dynamically when the modal is displayed
 */
function displayDraftWritingResult(data, workflowData) {
    console.log('displayDraftWritingResult called with data:', data);
    console.log('workflowData:', workflowData);
    
    // Check if we have a valid response
    if (!data || !data.success) {
        alert_float('danger', data.message || '<?php echo _l('error_loading_draft_writer'); ?>');
        return;
    }

    // Global state management
    window.DraftWriter = {
        topic_id: typeof topicCurrentId !== 'undefined' ? topicCurrentId : workflowData.topic_id,
        workflow_id: workflowData.workflow_id,
        execution_id: data.execution_id,
        hasChanges: false,
        editor: null
    };
    
    // Load modal templates
    <?php 
    ob_start();
    $this->load->view('includes/displayDraftWriter/topic_detail_action_buttons_display_script_displayDraftWriter_modal');
    $topic_detail_action_buttons_display_script_displayDraftWriter_modal = ob_get_clean();
    // Remove script tags
    $topic_detail_action_buttons_display_script_displayDraftWriter_modal = str_replace(['<script>', '</script>'], '', $topic_detail_action_buttons_display_script_displayDraftWriter_modal);
    echo $topic_detail_action_buttons_display_script_displayDraftWriter_modal;
    ?>  

    <?php 
    ob_start();
    $this->load->view('includes/displayDraftWriter/topic_detail_action_buttons_display_script_displayDraftWriter_storage');
    $topic_detail_action_buttons_display_script_displayDraftWriter_storage = ob_get_clean();
    // Remove script tags
    $topic_detail_action_buttons_display_script_displayDraftWriter_storage = str_replace(['<script>', '</script>'], '', $topic_detail_action_buttons_display_script_displayDraftWriter_storage);
    echo $topic_detail_action_buttons_display_script_displayDraftWriter_storage;
    ?>  

    <?php 
    ob_start();
    $this->load->view('includes/displayDraftWriter/topic_detail_action_buttons_display_script_displayDraftWriter_ai');
    $topic_detail_action_buttons_display_script_displayDraftWriter_ai = ob_get_clean();
    // Remove script tags
    $topic_detail_action_buttons_display_script_displayDraftWriter_ai = str_replace(['<script>', '</script>'], '', $topic_detail_action_buttons_display_script_displayDraftWriter_ai);
    echo $topic_detail_action_buttons_display_script_displayDraftWriter_ai;
    ?>  

    
    console.log('Global state initialized:', window.DraftWriter);

    // Initialize handlers
    initDraftWriterHandlers();
    console.log('Handlers initialized');

    
    console.log('Templates loaded, initializing Draft Writer');
    // Initialize Draft Writer
    initializeDraftWriter(data, workflowData);
}

/**
 * Initialize Draft Writer Handlers
 */
function initDraftWriterHandlers() {
    console.log('Initializing Draft Writer Handlers');
    window.DraftWriter.handlers = {
        markAsChanged: function() {
            window.DraftWriter.hasChanges = true;
        },
        
        saveChanges: function() {
            return window.DraftWriter.storage.saveDraft(
                window.DraftWriter.storage.getCurrentContent()
            );
        },
        
        publishDraft: function() {
            publishDraft();
        },
        
        improveContent: function(type, action, style, tone) {
            improveContent(type, action, style, tone);
        },
        
        searchContent: function(query) {
            return window.DraftWriter.ai.search(query, 'content', 5);
        },
        
        updateAnalysis: function() {
            updateContentAnalysis();
        }
    };
}

/**
 * Initialize Draft Writer
 */
function initializeDraftWriter(data, workflowData) {
    console.log('Initializing Draft Writer with data:', data);
    console.log('Workflow data:', workflowData);
    
    // The modal is already appended to the body by the displayDraftWriter_modal.php file
    // We need to update its attributes and show it

    window.DraftWriter.initDraftWriterModal(data, workflowData);
    
    // Ensure the topic_id is set correctly - use direct jQuery to set the value
    const topicIdToUse = typeof topicCurrentId !== 'undefined' ? topicCurrentId : workflowData.topic_id;
    $('#draft-writer-modal').find('#topic_id').val(topicIdToUse);
    console.log('Setting topic_id to:', topicIdToUse);
    console.log('Topic ID element:', $('#draft-writer-modal').find('#topic_id'));
    
    // Set data attributes for workflow and execution
    $('#draft-writer-modal').attr('data-workflow-id', workflowData.workflow_id);
    $('#draft-writer-modal').attr('data-execution-id', data.execution_id);
    
    // Log the modal state before showing it
    console.log('Modal before show:', {
        'topic_id': $('#draft-writer-modal').find('#topic_id').val(),
        'workflow_id': $('#draft-writer-modal').attr('data-workflow-id'),
        'execution_id': $('#draft-writer-modal').attr('data-execution-id')
    });
    
    // Show modal
    $('#draft-writer-modal').modal({
        backdrop: 'static',
        keyboard: false
    });

    console.log('Modal shown');

    // Đóng popover khi click ra ngoài
    $(document).on('click', function(e) {
        if ($(e.target).data('toggle') !== 'popover' 
            && $(e.target).parents('.popover.in').length === 0) { 
            $('[data-toggle="popover"]').popover('hide');
        }
    });

}

/**
 * Initialize Draft Writer Components
 */
function initializeDraftWriterComponents(data, workflowData) {
    console.log('Initializing Draft Writer Components');
    console.log('Topic ID:', workflowData.topic_id);
    
    // Ensure the topic_id is set in the global state
    window.DraftWriter.topic_id = workflowData.topic_id || '57219'; // Fallback to hardcoded value for testing
    
    // Instead of loading components via AJAX, use the data from the workflow response
    console.log('Using data from workflow response:', data);
    
    // Initialize TinyMCE editor
    initTinyMCE();
    
    // Initialize event handlers
    initEventHandlers();
    
    // Initialize auto-save
    initAutoSave();
    
    // Load initial content from the workflow response
    loadInitialContentFromResponse(data);
}

/**
 * Load initial content from workflow response
 */
function loadInitialContentFromResponse(data) {
    console.log('Loading initial content from response');
    
    // First try to load from local storage
    const savedDraft = window.DraftWriter.storage.loadDraft();
    if (savedDraft) {
        console.log('Found saved draft in local storage:', savedDraft);
        
        // Create and show the confirmation dialog
        showDraftConfirmationDialog(savedDraft, data);
        return;
    }
    
    // If no saved draft, load content from the workflow response
    loadContentFromWorkflowResponse(data);
}

/**
 * Show confirmation dialog for saved draft
 */
function showDraftConfirmationDialog(savedDraft, workflowData) {
    console.log('Showing draft confirmation dialog');
    // Clear old dialog
    removeDraftConfirmationDialog();
    // Create the dialog HTML
    const dialogHtml = `
        <div id="draft-confirmation-overlay" class="draft-confirmation-overlay">
            <div class="draft-confirmation-dialog">
                <div class="draft-confirmation-header">
                    <h4><?php echo _l('saved_draft_found'); ?></h4>
                </div>
                <div class="draft-confirmation-body">
                    <p><?php echo _l('saved_draft_found_message'); ?></p>
                    <div class="draft-info">
                        <div class="draft-info-item">
                            <strong><?php echo _l('title'); ?>:</strong> ${savedDraft.title || '<?php echo _l('no_title'); ?>'}
                        </div>
                        <div class="draft-info-item">
                            <strong><?php echo _l('last_saved'); ?>:</strong> ${formatLastSaved(savedDraft.last_saved)}
                        </div>
                    </div>
                </div>
                <div class="draft-confirmation-footer">
                    <button id="preview-draft-btn" class="btn btn-default">
                        <i class="fa fa-eye"></i> <?php echo _l('preview_old_saved'); ?>
                    </button>
                    <button id="reuse-draft-btn" class="btn btn-info">
                        <i class="fa fa-check"></i> <?php echo _l('reuse'); ?>
                    </button>
                    <button id="reload-content-btn" class="btn btn-warning">
                        <i class="fa fa-refresh"></i> <?php echo _l('reload'); ?>
                    </button>
                </div>
            </div>
        </div>
    `;
    
    // Add the dialog to the body
    $('body').append(dialogHtml);
    
    // Add styles for the dialog
    const dialogStyles = `
        <style>
            .draft-confirmation-overlay {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: rgba(0, 0, 0, 0.5);
                z-index: 10000;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            .draft-confirmation-dialog {
                background-color: #fff;
                border-radius: 5px;
                box-shadow: 0 3px 10px rgba(0, 0, 0, 0.3);
                width: 500px;
                max-width: 90%;
            }
            
            .draft-confirmation-header {
                padding: 15px;
                border-bottom: 1px solid #e5e5e5;
            }
            
            .draft-confirmation-header h4 {
                margin: 0;
                font-weight: 600;
            }
            
            .draft-confirmation-body {
                padding: 15px;
            }
            
            .draft-info {
                background-color: #f9f9f9;
                border: 1px solid #e5e5e5;
                border-radius: 3px;
                padding: 10px;
                margin-top: 10px;
            }
            
            .draft-info-item {
                margin-bottom: 5px;
            }
            
            .draft-confirmation-footer {
                padding: 15px;
                border-top: 1px solid #e5e5e5;
                text-align: right;
                display: flex;
                justify-content: flex-end;
                gap: 10px;
            }
            
            .draft-preview-modal .modal-dialog {
                width: 90%;
                max-width: 1200px;
            }
            
            .draft-preview-content {
                max-height: 500px;
                overflow-y: auto;
                border: 1px solid #e5e5e5;
                padding: 15px;
                margin-bottom: 15px;
                background-color: #fff;
            }
        </style>
    `;
    
    $('head').append(dialogStyles);
    
    initDraftWritingButtons(savedDraft, workflowData);
}

function initDraftWritingButtons(savedDraft, workflowData) {
    // Handle button clicks
    $('#preview-draft-btn').on('click', function() {
        showDraftPreview(savedDraft, workflowData);
    });
    
    $('#reuse-draft-btn').on('click', function() {
        loadSavedDraft(savedDraft);
        removeDraftConfirmationDialog();
    });
    
    $('#reload-content-btn').on('click', function() {
        loadContentFromWorkflowResponse(workflowData);
        removeDraftConfirmationDialog();
    });
    
}

/**
 * Show preview of saved draft
 */
function showDraftPreview(savedDraft, workflowData) {
    // Create the preview modal HTML
    const previewModalHtml = `
        <div class="modal fade draft-preview-modal" id="draft-preview-modal" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                        <h4 class="modal-title"><?php echo _l('preview_saved_draft'); ?></h4>
                    </div>
                    <div class="modal-body">
                        <div class="draft-preview-info">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><?php echo _l('title'); ?>:</label>
                                        <p class="form-control-static">${savedDraft.title || '<?php echo _l('no_title'); ?>'}</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><?php echo _l('last_saved'); ?>:</label>
                                        <p class="form-control-static">${formatLastSaved(savedDraft.last_saved)}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label><?php echo _l('description'); ?>:</label>
                                <p class="form-control-static">${savedDraft.description || '<?php echo _l('no_description'); ?>'}</p>
                            </div>
                        </div>
                        <div class="form-group">
                            <label><?php echo _l('content'); ?>:</label>
                            <div class="draft-preview-content">${savedDraft.content ? savedDraft.content : '<?php echo _l('no_content'); ?>'}</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">
                            <i class="fa fa-times"></i> <?php echo _l('close'); ?>
                        </button>
                        <button type="button" class="btn btn-info" id="use-this-draft-btn">
                            <i class="fa fa-check"></i> <?php echo _l('use_this_draft'); ?>
                        </button>
                        <button type="button" class="btn btn-warning" id="use-new-content-btn">
                            <i class="fa fa-refresh"></i> <?php echo _l('use_new_content'); ?>
                        </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    
    // Add the preview modal to the body
    $('body').append(previewModalHtml);
    
    // Show the preview modal
    $('#draft-preview-modal').modal('show');
    
    // Handle button clicks
    $('#use-this-draft-btn').on('click', function() {
        $('#draft-preview-modal').modal('hide');
        loadSavedDraft(savedDraft);
        removeDraftConfirmationDialog();
    });
    
    $('#use-new-content-btn').on('click', function() {
        $('#draft-preview-modal').modal('hide');
        loadContentFromWorkflowResponse(workflowData);
        removeDraftConfirmationDialog();
    });
    
    // Clean up when the modal is closed
    $('#draft-preview-modal').on('hidden.bs.modal', function() {
        $(this).remove();
    });
}

/**
 * Remove the draft confirmation dialog
 */
function removeDraftConfirmationDialog() {
    $('#draft-confirmation-overlay').remove();
}

/**
 * Format the last saved date
 */
function formatLastSaved(dateString) {
    if (!dateString) return '<?php echo _l('unknown'); ?>';
    
    try {
        const lastSaved = new Date(dateString);
        const now = new Date();
        const diff = Math.floor((now - lastSaved) / 1000); // Difference in seconds
        
        if (diff < 60) {
            return diff + ' <?php echo _l('seconds_ago'); ?>';
        } else if (diff < 3600) {
            return Math.floor(diff / 60) + ' <?php echo _l('minutes_ago'); ?>';
        } else if (diff < 86400) {
            return Math.floor(diff / 3600) + ' <?php echo _l('hours_ago'); ?>';
        } else {
            return lastSaved.toLocaleDateString() + ' ' + lastSaved.toLocaleTimeString();
        }
    } catch (e) {
        console.error('Error formatting date:', e);
        return '<?php echo _l('unknown'); ?>';
    }
}

/**
 * Load saved draft into the editor
 */
function loadSavedDraft(savedDraft) {
    $('#draft-title').val(savedDraft.title || '');
    $('#draft-description').val(savedDraft.description || '');
    
    if (window.DraftWriter.editor) {
        // Clean the content before setting it in the editor
        const cleanedContent = savedDraft.content ? savedDraft.content : '';
        window.DraftWriter.editor.setContent(cleanedContent);
    }
    
    $('#draft-tags').val(savedDraft.tags || '');
    $('#draft-category').val(savedDraft.category || '');
    
    if (savedDraft.featured_image) {
        $('#featured-image').attr('src', savedDraft.featured_image);
        $('#featured-image-preview').show();
        $('#featured-image-placeholder').hide();
    }
    
    alert_float('info', '<?php echo _l('draft_loaded_from_local_storage'); ?>');
    
    // Update content analysis
    updateContentAnalysis();
}

/**
 * Load content from workflow response
 */
function loadContentFromWorkflowResponse(data) {
    console.log('Loading content from workflow response',data);
    // If no saved draft, use data from the workflow response
    if (data && data.data && data.data.response && data.data.response.data && data.data.response.data.length > 0) {
        console.log('Using data from workflow response');
        const responseItems = data.data.response.data;
        console.log('Found ' + responseItems.length + ' items in response data');
        
        // Get the main topic and title from the first item
        const firstItem = responseItems[0];
        
        // Set title - decode HTML entities
        let mainTitle = '';
        if (firstItem.Title) {
            mainTitle = decodeHtmlEntities(firstItem.Title);
            $('#draft-title').val(mainTitle);
            console.log('Set title:', mainTitle);
        }
        
        // Set description/summary - decode HTML entities
        let mainSummary = '';
        if (firstItem.Summary) {
            mainSummary = decodeHtmlEntities(firstItem.Summary);
            $('#draft-description').val(mainSummary);
            console.log('Set description:', mainSummary);
        }
        
        // Build the complete HTML content from all items
        let content = '';
        
        // Add main topic heading if available
        if (firstItem.Topic) {
            const decodedTopic = decodeHtmlEntities(firstItem.Topic);
            content += `<h1>${decodedTopic}</h1>`;
        } else if (mainTitle) {
            content += `<h1>${mainTitle}</h1>`;
        }
        
        // Add main summary if available
        if (mainSummary) {
            content += `<p class="lead">${mainSummary}</p>`;
        }
        
        // Process all items in the array
        responseItems.forEach((item, index) => {
            // Add a separator between items if not the first item
            if (index > 0) {
                content += '<hr class="item-separator">';
            }
            
            // Add item title if available and different from main title
            if (item.Item_Title && decodeHtmlEntities(item.Item_Title) !== mainTitle) {
                const decodedItemTitle = decodeHtmlEntities(item.Item_Title);
                content += `<h2>${decodedItemTitle}</h2>`;
            }
            
            // Add item content if available
            if (item.Item_Content) {
                // Clean and properly format the content
                const cleanedContent = decodeHtmlEntities(item.Item_Content);
                // console.log('Cleaned content:', cleanedContent);
                // console.log('Item content:', item.Item_Content);
                content += `<div class="item-content">${cleanedContent}</div>`;
            }
            
            // Add item images if available
            if (item.item_Pictures_Full && item.item_Pictures_Full.length > 0) {
                content += '<div class="item-images">';
                
                // Try to parse the item_Pictures_Full if it's a string
                let pictures = item.item_Pictures_Full;
                if (typeof pictures === 'string') {
                    try {
                        pictures = JSON.parse(pictures);
                    } catch (e) {
                        console.error('Error parsing item_Pictures_Full:', e);
                    }
                }
                
                // Add each image
                if (Array.isArray(pictures)) {
                    pictures.forEach(pic => {
                        const imgSrc = typeof pic === 'string' ? pic : (pic.large_src || pic.src || '');
                        if (imgSrc) {
                            content += `<img src="${imgSrc}" alt="Image" class="img-responsive">`;
                        }
                    });
                } else if (typeof pictures === 'object' && pictures.large_src) {
                    content += `<img src="${pictures.large_src}" alt="Image" class="img-responsive">`;
                }
                
                content += '</div>';
            }
        });
        
        // Add footer if available from the first item
        if (firstItem.Topic_footer) {
            const decodedFooter = decodeHtmlEntities(firstItem.Topic_footer);
            content += `<hr><div class="footer">${decodedFooter}</div>`;
        }
        
        // Set content in editor
        if (window.DraftWriter.editor && content) {
            // Ensure the content is properly cleaned before setting it in the editor
            // console.log('Setting content in editor:', content);
            // const finalCleanedContent = cleanHtmlContent(content);
            window.DraftWriter.editor.setContent(content);
            // console.log('Set content in editor');
        }
        
        // Set tags if available from the first item
        if (firstItem.TopicKeywords) {
            const decodedKeywords = decodeHtmlEntities(firstItem.TopicKeywords);
            $('#draft-tags').val(decodedKeywords);
            console.log('Set tags:', decodedKeywords);
        }
        
        // Set featured image if available from the first item
        if (firstItem.item_Pictures_Full && firstItem.item_Pictures_Full.length > 0) {
            let featuredImage = '';
            
            // Try to parse the item_Pictures_Full if it's a string
            let pictures = firstItem.item_Pictures_Full;
            if (typeof pictures === 'string') {
                try {
                    pictures = JSON.parse(pictures);
                } catch (e) {
                    console.error('Error parsing item_Pictures_Full for featured image:', e);
                }
            }
            
            // Get the first image
            if (Array.isArray(pictures) && pictures.length > 0) {
                featuredImage = typeof pictures[0] === 'string' ? pictures[0] : (pictures[0].large_src || pictures[0].src || '');
            } else if (typeof pictures === 'object' && pictures.large_src) {
                featuredImage = pictures.large_src;
            }
            
            if (featuredImage) {
                $('#featured-image').attr('src', featuredImage);
                $('#featured-image-preview').show();
                $('#featured-image-placeholder').hide();
                console.log('Set featured image:', featuredImage);
            }
        }
        
        // console.log('Initial content loaded from workflow response', content);
    } else {
        console.log('No data available in workflow response');
        // If no data in response, initialize with empty content
        if (window.DraftWriter.editor) {
            window.DraftWriter.editor.setContent('');
        }
    }
    
    // Update content analysis after loading
    updateContentAnalysis();
}

/**
 * Initialize TinyMCE
 */
function initTinyMCE() {
    if (typeof tinymce !== 'undefined') {
        tinymce.init({
            selector: '#draft-content',
            height: 500,
            menubar: true,
            plugins: [
                'advlist autolink lists link image',
                'searchreplace code fullscreen',
                'insertdatetime table paste wordcount'
            ],
            extended_valid_elements: '*[*]', // Cho phép tất cả các thẻ và thuộc tính
            valid_elements: '*[*]',          // Tương tự như trên, nhưng cho các thẻ hợp lệ
            valid_children: '+body[style|script]', // Cho phép <style> và <script> trong <body>
            toolbar: false, // We're using our custom toolbar
            statusbar: true,
            setup: function(editor) {
                window.DraftWriter.editor = editor;
                
                editor.on('change', function() {
                    window.DraftWriter.hasChanges = true;
                    updateContentAnalysis();
                });
                
                editor.on('keyup', function() {
                    window.DraftWriter.hasChanges = true;
                });
            }
        });
    }
}

/**
 * Initialize event handlers
 */
function initEventHandlers() {
    console.log('Initializing event handlers');
    
    // Auto-save toggle
    $('.auto-save-toggle').on('click', function() {
        window.DraftWriter.storage.toggleAutoSave();
    });
    
    // Save draft button
    $('#save-draft-btn, #save-draft-btn-footer').on('click', function() {
        const saved = window.DraftWriter.storage.saveDraft(
            window.DraftWriter.storage.getCurrentContent()
        );
        
        if (saved) {
            alert_float('success', '<?php echo _l('draft_saved'); ?>');
        } else {
            alert_float('danger', '<?php echo _l('error_saving_draft'); ?>');
        }
    });
    
    // Publish draft button
    $('#publish-draft-btn, #publish-draft-btn-footer').on('click', function() {
        publishDraft();
    });
    
    // Content change handlers
    $('#draft-title, #draft-description, #draft-tags').on('input', function() {
        window.DraftWriter.hasChanges = true;
    });
    
    // Toolbar button handlers
    $('#format-bold').on('click', function() {
        if (window.DraftWriter.editor) {
            window.DraftWriter.editor.execCommand('Bold');
        }
    });
    
    $('#format-italic').on('click', function() {
        if (window.DraftWriter.editor) {
            window.DraftWriter.editor.execCommand('Italic');
        }
    });
    
    $('#format-underline').on('click', function() {
        if (window.DraftWriter.editor) {
            window.DraftWriter.editor.execCommand('Underline');
        }
    });
    
    $('#format-align-left').on('click', function() {
        if (window.DraftWriter.editor) {
            window.DraftWriter.editor.execCommand('JustifyLeft');
        }
    });
    
    $('#format-align-center').on('click', function() {
        if (window.DraftWriter.editor) {
            window.DraftWriter.editor.execCommand('JustifyCenter');
        }
    });
    
    $('#format-align-right').on('click', function() {
        if (window.DraftWriter.editor) {
            window.DraftWriter.editor.execCommand('JustifyRight');
        }
    });
    
    $('#format-bullet-list').on('click', function() {
        if (window.DraftWriter.editor) {
            window.DraftWriter.editor.execCommand('InsertUnorderedList');
        }
    });
    
    $('#format-number-list').on('click', function() {
        if (window.DraftWriter.editor) {
            window.DraftWriter.editor.execCommand('InsertOrderedList');
        }
    });
    
    // AI buttons
    $('#ai-edit-title-btn').on('click', function() {
        improveContent('title');
    });
    
    $('#ai-edit-description-btn').on('click', function() {
        improveContent('description');
    });
    
    $('#ai-edit-content-btn').on('click', function() {
        improveContent('content');
    });
    
    $('#ai-search-btn').on('click', function() {
        openAISearch();
    });
    
    $('#ai-improve-btn').on('click', function() {
        improveContent('content', 'improve');
    });
    
    console.log('Event handlers initialized');
}

/**
 * Initialize auto-save
 */
function initAutoSave() {
    window.DraftWriter.storage.startAutoSave();
}

/**
 * Publish draft
 */
function publishDraft() {
    if (!window.DraftWriter.editor) {
        alert_float('danger', '<?php echo _l('editor_not_initialized'); ?>');
        return;
    }
    
    const content = window.DraftWriter.editor.getContent();
    const title = $('#draft-title').val();
    const description = $('#draft-description').val();
    const tags = $('#draft-tags').val();
    const category = $('#draft-category').val();
    
    // Basic validation
    if (!title) {
        alert_float('warning', '<?php echo _l('title_required'); ?>');
        return;
    }
    
    if (!content) {
        alert_float('warning', '<?php echo _l('content_required'); ?>');
        return;
    }
    
    // Show confirmation dialog
    if (confirm('<?php echo _l('confirm_publish_draft'); ?>')) {
        $.ajax({
            url: admin_url + 'writing/publish_draft',
            type: 'POST',
            data: {
                topic_id: window.DraftWriter.topic_id,
                content: content,
                title: title,
                description: description,
                tags: tags,
                category: category
            },
            success: function(response) {
                try {
                    const result = JSON.parse(response);
                    
                    if (result.success) {
                        alert_float('success', result.message);
                        
                        // Clear draft from local storage
                        window.DraftWriter.storage.clearDraft();
                        
                        // Close modal
                        $('#draft-writer-modal').modal('hide');
                        
                        // Reload page if needed
                        if (result.data && result.data.reload) {
                            setTimeout(function() {
                                window.location.reload();
                            }, 1000);
                        }
                    } else {
                        alert_float('danger', result.message);
                    }
                } catch (e) {
                    console.error('Error parsing publish response:', e);
                    alert_float('danger', '<?php echo _l('error_publishing_draft'); ?>');
                }
            },
            error: function() {
                alert_float('danger', '<?php echo _l('error_publishing_draft'); ?>');
            }
        });
    }
}

/**
 * Improve content using AI
 */
function improveContent(type, action = 'improve', style = 'professional', tone = 'formal') {
    let content;
    
    switch (type) {
        case 'title':
            content = $('#draft-title').val();
            break;
        case 'description':
            content = $('#draft-description').val();
            break;
        case 'content':
            content = window.DraftWriter.editor ? 
                window.DraftWriter.editor.getSelection() || window.DraftWriter.editor.getContent() :
                '';
            break;
        default:
            alert_float('danger', '<?php echo _l('invalid_content_type'); ?>');
            return;
    }
    
    if (!content) {
        alert_float('warning', '<?php echo _l('no_content_to_improve'); ?>');
        return;
    }
    
    // Show loading state
    const $btn = $(`#ai-edit-${type}-btn`);
    const originalHtml = $btn.html();
    $btn.html('<i class="fa fa-spinner fa-spin"></i>');
    $btn.prop('disabled', true);
    
    // Call AI improve
    window.DraftWriter.ai.improve(content, type, style, tone)
        .done(function(response) {
            if (response.success) {
                // Update content based on type
                switch (type) {
                    case 'title':
                        $('#draft-title').val(response.content);
                        break;
                    case 'description':
                        $('#draft-description').val(response.content);
                        break;
                    case 'content':
                        if (window.DraftWriter.editor) {
                            if (window.DraftWriter.editor.selection.getContent()) {
                                window.DraftWriter.editor.selection.setContent(response.content);
                            } else {
                                window.DraftWriter.editor.setContent(response.content);
                            }
                        }
                        break;
                }
                
                // Show success message with stats
                const stats = response.stats;
                const message = `<?php echo _l('content_improved'); ?><br>
                    <?php echo _l('original_length'); ?>: ${stats.original_length}<br>
                    <?php echo _l('improved_length'); ?>: ${stats.improved_length}`;
                
                alert_float('success', message);
                
                // Mark as changed
                window.DraftWriter.hasChanges = true;
                
            } else {
                alert_float('danger', response.message || '<?php echo _l('error_improving_content'); ?>');
            }
        })
        .fail(function() {
            alert_float('danger', '<?php echo _l('error_improving_content'); ?>');
        })
        .always(function() {
            // Restore button state
            $btn.html(originalHtml);
            $btn.prop('disabled', false);
        });
}

/**
 * Open AI search
 */
function openAISearch() {
    $('#ai-search-modal').modal('show');
    
    // Focus search input
    setTimeout(function() {
        $('#ai-search-input').focus();
    }, 500);
    
    // Initialize search functionality if not already done
    if (!window.searchInitialized) {
        initializeSearch();
        window.searchInitialized = true;
    }
}

/**
 * Initialize search functionality
 */
function initializeSearch() {
    // Search button click
    $('#perform-ai-search-btn').on('click', function() {
        const query = $('#ai-search-input').val();
        if (!query) {
            alert_float('warning', '<?php echo _l('enter_search_query'); ?>');
            return;
        }
        
        performSearch(query);
    });
    
    // Enter key in search input
    $('#ai-search-input').on('keypress', function(e) {
        if (e.which === 13) {
            $('#perform-ai-search-btn').click();
        }
    });
    
    // Insert selected result
    $('#insert-search-result-btn').on('click', function() {
        const selected = $('#ai-search-results .search-result.selected');
        if (selected.length === 0) {
            alert_float('warning', '<?php echo _l('select_result_to_insert'); ?>');
            return;
        }
        
        const content = selected.data('content');
        if (window.DraftWriter.editor) {
            window.DraftWriter.editor.selection.setContent(content);
            window.DraftWriter.hasChanges = true;
        }
        
        $('#ai-search-modal').modal('hide');
    });
}

/**
 * Perform AI search
 */
function performSearch(query) {
    const $results = $('#ai-search-results');
    $results.html('<div class="text-center"><i class="fa fa-spinner fa-spin fa-2x"></i></div>');
    
    window.DraftWriter.ai.search(query, 'content', 5)
        .done(function(response) {
            if (response.success) {
                displaySearchResults(response.results);
            } else {
                $results.html(`<div class="alert alert-danger">${response.message || '<?php echo _l('search_failed'); ?>'}</div>`);
            }
        })
        .fail(function() {
            $results.html(`<div class="alert alert-danger"><?php echo _l('search_failed'); ?></div>`);
        });
}

/**
 * Display search results
 */
function displaySearchResults(results) {
    const $results = $('#ai-search-results');
    
    if (!results || results.length === 0) {
        $results.html(`<div class="alert alert-info"><?php echo _l('no_results_found'); ?></div>`);
        return;
    }
    
    let html = '<div class="search-results-container">';
    results.forEach((result, index) => {
        html += `
            <div class="search-result" data-content="${result.snippet}">
                <h4>${result.title}</h4>
                <p>${result.snippet}</p>
                <small class="text-muted">
                    <i class="fa fa-external-link"></i> ${result.url}
                    <span class="pull-right">
                        <i class="fa fa-star"></i> ${result.relevance_score}%
                    </span>
                </small>
            </div>
        `;
    });
    html += '</div>';
    
    $results.html(html);
    
    // Make results selectable
    $('.search-result').on('click', function() {
        $('.search-result').removeClass('selected');
        $(this).addClass('selected');
    });
}

/**
 * Update content analysis
 */
function updateContentAnalysis() {
    if (!window.DraftWriter.editor) return;
    
    // Update keyword analysis
    window.DraftWriter.analysis.updateKeywords();
    
    // Update SEO analysis
    window.DraftWriter.analysis.updateSEO();
}

/**
 * Update keyword analysis UI
 */
function updateKeywordAnalysisUI(analysis) {
    // Update stats section
    const $statsSection = $('.stats-section');
    $statsSection.html(`
        <div class="stats-row">
            <div class="stat-item">
                <div class="stat-value">${analysis.total_words}</div>
                <div class="stat-label"><?php echo _l('words'); ?></div>
            </div>
            <div class="stat-item">
                <div class="stat-value">${Math.ceil(analysis.total_words / 200)}</div>
                <div class="stat-label"><?php echo _l('min_read'); ?></div>
            </div>
            <div class="stat-item">
                <div class="score-indicator ${getScoreClass(analysis.average_score)}">
                    ${Math.round(analysis.average_score)}
                </div>
                <div class="stat-label"><?php echo _l('seo_score'); ?></div>
            </div>
        </div>
    `);

    // Update keyword analysis table
    const $densityContainer = $('#keyword-density');
    let densityHtml = `
        <table class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th><?php echo _l('keyword'); ?></th>
                    <th><?php echo _l('count'); ?></th>
                    <th><?php echo _l('density'); ?></th>
                    <th><?php echo _l('score'); ?></th>
                    <th><?php echo _l('distribution'); ?></th>
                    <th><?php echo _l('recommendations'); ?></th>
                </tr>
            </thead>
            <tbody>
    `;

    if (analysis.keywords && Object.keys(analysis.keywords).length > 0) {
        // Sort keywords by score
        const sortedKeywords = Object.values(analysis.keywords)
            .sort((a, b) => b.score - a.score);

        sortedKeywords.forEach(data => {
            // Calculate status class based on score
            let statusClass = '';
            if (data.score >= 80) statusClass = 'text-success';
            else if (data.score >= 60) statusClass = 'text-warning';
            else statusClass = 'text-danger';

            // Create distribution visualization
            const distributionHtml = createDistributionVisualization(data.positions.distribution);

            // Format recommendations
            const recommendationsHtml = formatRecommendations(data.recommendations);

            densityHtml += `
                <tr>
                    <td>
                        <div class="keyword-name">
                            <span class="keyword-text">${data.keyword}</span>
                            ${data.positions.title ? '<span class="badge badge-success" title="Found in title"><i class="fa fa-header"></i></span>' : ''}
                            ${data.positions.first_paragraph ? '<span class="badge badge-info" title="Found in first paragraph"><i class="fa fa-paragraph"></i></span>' : ''}
                            ${data.positions.headings > 0 ? `<span class="badge badge-primary" title="Found in ${data.positions.headings} headings">H${data.positions.headings}</span>` : ''}
                        </div>
                    </td>
                    <td>${data.count}</td>
                    <td>
                        <div class="density-indicator ${getDensityClass(data.density)}">
                            ${data.density}%
                        </div>
                    </td>
                    <td>
                        <div class="score-badge ${statusClass}">
                            ${data.score}/100
                        </div>
                    </td>
                    <td>
                        <div class="distribution-chart">
                            ${distributionHtml}
                        </div>
                    </td>
                    <td>
                        ${recommendationsHtml}
                    </td>
                </tr>
            `;
        });
    } else {
        densityHtml += `
            <tr>
                <td colspan="6" class="text-center">
                    <?php echo _l('no_keywords_found'); ?>
                </td>
            </tr>
        `;
    }

    densityHtml += `
            </tbody>
        </table>
        <div class="keyword-analysis-legend">
            <small class="text-muted">
                <i class="fa fa-info-circle"></i> <?php echo _l('keyword_analysis_info'); ?><br>
                <span class="density-good">■</span> <?php echo _l('optimal_density'); ?> (1-3%)<br>
                <span class="density-warning">■</span> <?php echo _l('low_density'); ?> (0.5-1%)<br>
                <span class="density-danger">■</span> <?php echo _l('very_low_density'); ?> (<0.5%)
            </small>
        </div>
    `;

    // Update container
    $densityContainer.html(densityHtml);

    // Initialize popovers
    $('[data-toggle="popover"]').popover({
        html: true,
        container: '#draft-writer-modal',
        placement: 'top',
        template: `
            <div class="popover keyword-analysis-popover" role="tooltip" style="z-index: 100000;">
                <div class="arrow"></div>
                <h3 class="popover-title"></h3>
                <div class="popover-content"></div>
            </div>
        `
    });
}

// Helper function to create distribution visualization
function createDistributionVisualization(distribution) {
    let html = '<div class="distribution-bars">';
    distribution.forEach((count, index) => {
        // Xác định màu sắc dựa trên mật độ
        let color;
        if (count === 0) {
            color = '#e9ecef'; // Màu xám nhạt cho phần không có từ khóa
        } else if (count >= 3) {
            color = '#10B981'; // Màu xanh lá cho mật độ tốt
        } else if (count >= 1) {
            color = '#F59E0B'; // Màu vàng cam cho mật độ trung bình
        } else {
            color = '#EF4444'; // Màu đỏ cho mật độ thấp
        }

        // Tính opacity dựa trên có/không có từ khóa
        const opacity = count > 0 ? 1 : 0.2;

        // Thêm thanh phân phối với màu sắc và tooltip
        html += `
            <div class="distribution-bar" 
                 style="background-color: ${color}; opacity: ${opacity}" 
                 title="Phần ${index + 1}: ${count} lần xuất hiện"
                 data-toggle="tooltip"
                 data-placement="top">
            </div>
        `;
    });
    html += '</div>';
    return html;
}

// Helper function to format recommendations
function formatRecommendations(recommendations) {
    if (!recommendations || recommendations.length === 0) {
        return '<span class="text-success"><i class="fa fa-check-circle"></i> <?php echo _l('no_recommendations'); ?></span>';
    }

    const recommendationsList = recommendations.map(rec => `<li>${translateRecommendation(rec)}</li>`).join('');
    
    return `
        <div class="recommendations-tooltip">
            <a href="#" data-toggle="popover" 
               data-trigger="hover" 
               title="<?php echo _l('recommendations'); ?>"
               data-content="<ul class='recommendations-list'>${recommendationsList}</ul>">
                <i class="fa fa-lightbulb-o"></i> ${recommendations.length} <?php echo _l('suggestions'); ?>
            </a>
        </div>
    `;
}

// Helper function to get density class
function getDensityClass(density) {
    if (density >= 1 && density <= 3) return 'density-good';
    if (density >= 0.5 && density < 1) return 'density-warning';
    return 'density-danger';
}

// Helper function to translate recommendations
function translateRecommendation(rec) {
    const translations = {
        'keyword_density_too_low': '<?php echo _l('keyword_density_too_low'); ?>',
        'keyword_density_too_high': '<?php echo _l('keyword_density_too_high'); ?>',
        'add_keyword_to_title': '<?php echo _l('add_keyword_to_title'); ?>',
        'add_keyword_to_first_paragraph': '<?php echo _l('add_keyword_to_first_paragraph'); ?>',
        'add_keyword_to_headings': '<?php echo _l('add_keyword_to_headings'); ?>',
        'add_keyword_to_beginning': '<?php echo _l('add_keyword_to_beginning'); ?>',
        'improve_keyword_distribution': '<?php echo _l('improve_keyword_distribution'); ?>'
    };
    return translations[rec] || rec;
}

/**
 * Update SEO analysis UI
 */
function updateSEOAnalysisUI(analysis) {
    console.log('Updating SEO analysis UI', analysis);
    // Update SEO score
    $('#seo-score .score-value').text(analysis.score || 0);
    
    // Set score color based on value
    let scoreColor = '#dc3545'; // Red for poor scores
    if (analysis.score >= 80) {
        scoreColor = '#28a745'; // Green for good scores
    } else if (analysis.score >= 50) {
        scoreColor = '#ffc107'; // Yellow for average scores
    }
    $('#seo-score .score-value').css('color', scoreColor);
    
    // Update suggestions
    let suggestionsHtml = '';
    
    if (analysis.suggestions && analysis.suggestions.length > 0) {
        analysis.suggestions.forEach(suggestion => {
            // Get icon based on suggestion type
            let icon = 'times-circle';
            if (suggestion.type === 'good') {
                icon = 'check-circle';
            } else if (suggestion.type === 'warning') {
                icon = 'exclamation-triangle';
            }
            
            // Get localized text for suggestion
            let suggestionText = suggestion.text;
            if (typeof _l === 'function' && suggestion.text.startsWith('seo_')) {
                suggestionText = _l(suggestion.text);
            }
            
            suggestionsHtml += `
                <div class="seo-item ${suggestion.type}">
                    <span class="seo-indicator">
                        <i class="fa fa-${icon}"></i>
                    </span>
                    <span class="seo-text">${suggestionText}</span>
                </div>
            `;
        });
    } else {
        suggestionsHtml = `
            <div class="seo-item warning">
                <span class="seo-indicator">
                    <i class="fa fa-info-circle"></i>
                </span>
                <span class="seo-text"><?php echo _l('no_seo_suggestions_available'); ?></span>
            </div>
        `;
    }
    
    $('#seo-suggestions').html(suggestionsHtml);
    
    // Update preview
    const title = $('#draft-title').val() || '<?php echo _l('title_preview'); ?>';
    const description = $('#draft-description').val() || '<?php echo _l('description_preview'); ?>';
    
    $('#preview-title').text(title);
    $('#preview-description').text(description);
    
    // Update detailed stats if available
    if (analysis.stats) {
        // Create or update stats table
        let statsHtml = `
            <div class="seo-stats-table">
                <table class="table table-bordered table-striped">
                    <tbody>
                        <tr>
                            <td><strong><?php echo _l('word_count'); ?></strong></td>
                            <td>${analysis.stats.word_count || 0}</td>
                        </tr>
                        <tr>
                            <td><strong><?php echo _l('title_length'); ?></strong></td>
                            <td>${analysis.stats.title_length || 0} <?php echo _l('characters'); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php echo _l('description_length'); ?></strong></td>
                            <td>${analysis.stats.description_length || 0} <?php echo _l('characters'); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php echo _l('keyword_density'); ?></strong></td>
                            <td>${analysis.stats.keyword_density || 0}%</td>
                        </tr>
                        <tr>
                            <td><strong><?php echo _l('image_count'); ?></strong></td>
                            <td>${analysis.stats.image_count || 0}</td>
                        </tr>
                        <tr>
                            <td><strong><?php echo _l('internal_links'); ?></strong></td>
                            <td>${analysis.stats.internal_links || 0}</td>
                        </tr>
                        <tr>
                            <td><strong><?php echo _l('heading_structure'); ?></strong></td>
                            <td>
                                H1: ${analysis.stats.has_h1 ? '<i class="fa fa-check text-success"></i>' : '<i class="fa fa-times text-danger"></i>'} 
                                H2: ${analysis.stats.has_h2 ? '<i class="fa fa-check text-success"></i>' : '<i class="fa fa-times text-danger"></i>'} 
                                H3: ${analysis.stats.has_h3 ? '<i class="fa fa-check text-success"></i>' : '<i class="fa fa-times text-danger"></i>'}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        `;
        
        // Add or update stats section
        if ($('#seo-detailed-stats').length) {
            $('#seo-detailed-stats').html(statsHtml);
        } else {
            $('#seo-suggestions').after(`
                <div class="seo-detailed-stats mt-15" id="seo-detailed-stats">
                    <h5><?php echo _l('detailed_stats'); ?></h5>
                    ${statsHtml}
                </div>
            `);
        }
    }
}

/**
 * Count words in text
 */
function countWords(text) {
    return text.split(/\s+/).filter(Boolean).length;
}

/**
 * Strip HTML tags from text
 */
function stripHtml(html) {
    const temp = document.createElement('div');
    temp.innerHTML = html;
    return temp.textContent || temp.innerText || '';
}

// Helper function to get score class
function getScoreClass(score) {
    if (score >= 80) return 'score-good';
    if (score >= 60) return 'score-warning';
    return 'score-danger';
}

<?php 
ob_start();
$this->load->view('includes/displayDraftWriter/topic_detail_action_buttons_display_script_displayDraftWriter_analysis');
$topic_detail_action_buttons_display_script_displayDraftWriter_analysis = ob_get_clean();
// Remove script tags
$topic_detail_action_buttons_display_script_displayDraftWriter_analysis = str_replace(['<script>', '</script>'], '', $topic_detail_action_buttons_display_script_displayDraftWriter_analysis);
echo $topic_detail_action_buttons_display_script_displayDraftWriter_analysis;
?>  

<?php 
ob_start();
$this->load->view('includes/displayDraftWriter/topic_detail_action_buttons_display_script_displayDraftWriter_search');
$topic_detail_action_buttons_display_script_displayDraftWriter_search = ob_get_clean();
// Remove script tags
$topic_detail_action_buttons_display_script_displayDraftWriter_search = str_replace(['<script>', '</script>'], '', $topic_detail_action_buttons_display_script_displayDraftWriter_search);
echo $topic_detail_action_buttons_display_script_displayDraftWriter_search;
?>  

</script> 