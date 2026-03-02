<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<script>
/**
 * Display Draft Writing Result
 */
function displayDraftWritingResult(data, workflowData) {
    var timestamp = moment().format('DD/MM/YYYY HH:mm:ss');
    var countdownInterval;
    var pollInterval;
    var maxPollingTime = 300; // 5 minutes
    var pollingStartTime;
    var $resultContainer;

    // Global state management for draft writing
    window.DraftWriter = {
        content: null,
        originalContent: null,
        hasChanges: false,
        editor: null,
        metadata: {
            title: '',
            description: '',
            keywords: '',
            topic_id: workflowData.topic_id,
            workflow_id: workflowData.workflow_id,
            lastSaved: null
        },
        analysis: {
            wordCount: 0,
            readingTime: 0,
            headings: [],
            keywords: {},
            density: {}
        },
        storage: {
            // Lưu draft vào localStorage
            saveDraft: function(autoSave = false) {
                const draftKey = `draft_${window.DraftWriter.metadata.topic_id}`;
                const draftData = {
                    content: window.DraftWriter.editor ? window.DraftWriter.editor.getContent() : '',
                    title: $('#draft-title').val(),
                    description: $('#draft-description').val(),
                    lastSaved: new Date().toISOString(),
                    topicId: window.DraftWriter.metadata.topic_id,
                    autoSave: autoSave
                };
                
                localStorage.setItem(draftKey, JSON.stringify(draftData));
                
                if (!autoSave) {
                    alert_float('success', '<?php echo _l('draft_saved_to_local'); ?>');
                }
                
                // Update last saved time
                window.DraftWriter.metadata.lastSaved = draftData.lastSaved;
                $('#last-saved-time').text(moment(draftData.lastSaved).fromNow());
                
                return draftData;
            },
            
            // Load draft từ localStorage
            loadDraft: function() {
                const draftKey = `draft_${window.DraftWriter.metadata.topic_id}`;
                const savedDraft = localStorage.getItem(draftKey);
                
                if (savedDraft) {
                    try {
                        return JSON.parse(savedDraft);
                    } catch (e) {
                        console.error('Error parsing saved draft:', e);
                        return null;
                    }
                }
                
                return null;
            },
            
            // Xóa draft khỏi localStorage
            clearDraft: function() {
                const draftKey = `draft_${window.DraftWriter.metadata.topic_id}`;
                localStorage.removeItem(draftKey);
            }
        }
    };
    
    console.log('displayDraftWritingResult', data, workflowData);
    
    // Step 1: Show loading và bắt đầu polling
    if (data.data && data.data.audit_step === 1) {
        console.log("Displaying Draft Writing Step 1");
        
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
            <div class="execution-result-item" id="draft-writing-result">
                <div class="execution-timestamp text-muted">
                    <i class="fa fa-clock-o"></i> ${timestamp}
                </div>
                <div class="execution-status">
                    <i class="fa fa-spinner fa-spin text-info"></i> 
                    <strong>${data.message || '<?php echo _l('processing_draft'); ?>'}</strong>
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

        // Store workflow data globally for future use
        window.currentWorkflowData = workflowData;

        // Check if we have workflow info to start polling
        if (data.data.workflow_id && data.data.execution_id) {
            pollWorkflowStatus(
                data.data.workflow_id,
                data.data.execution_id,
                workflowData,
                function(draftData) {
                    // Callback khi polling hoàn tất thành công
                    displayDraftResult(draftData, workflowData);
                }
            );
        } else {
            // Show error if no workflow info
            $('#draft-writing-result .execution-status')
                .html('<i class="fa fa-times text-danger"></i> <?php echo _l('workflow_info_missing'); ?>');
        }
        return;
    }

    // Step 2: Xử lý khi người dùng lưu bản nháp
    if (data.data && data.data.audit_step === 2) {
        console.log("Displaying Draft Writing Step 2");
        
        var resultHtml = `
            <div class="execution-result-item" id="draft-writing-result-step2">
                <div class="execution-timestamp text-muted">
                    <i class="fa fa-clock-o"></i> ${timestamp}
                </div>
                <div class="execution-status">
                    <i class="fa fa-check text-success"></i> 
                    <strong>${data.message || '<?php echo _l('draft_saved_successfully'); ?>'}</strong>
                </div>
            </div>
        `;
        prependExecutionResult(resultHtml);
        
        // Clear local storage draft after successful save to server
        window.DraftWriter.storage.clearDraft();
        
        // Nếu cần reload trang
        if (data.data.reload) {
            setTimeout(function() {
                location.reload();
            }, 2000);
        }
        
        return;
    }

    // Internal functions section ------------------------------------------------------------  

    /**
     * Display draft result after polling completes
     */
    function displayDraftResult(draftData, workflowData) {
        console.log('displayDraftResult', draftData, workflowData);
        
        // Cập nhật trạng thái hiển thị
        $('#draft-writing-result .execution-status')
            .html('<i class="fa fa-check text-success"></i> <strong><?php echo _l('draft_content_ready'); ?></strong>');
        
        // Xóa thanh progress
        $('#draft-writing-result .progress').remove();
        
        // Parse data từ response
        let formattedContent = '';
        let draftTitle = '';
        let draftDescription = '';
        let items = [];
        
        try {
            // Xử lý dữ liệu từ response
            if (draftData.data && draftData.data.response && draftData.data.response.data) {
                // Nếu có data array từ response
                items = draftData.data.response.data;
            } else if (Array.isArray(draftData)) {
                // Nếu draftData là array
                items = draftData;
            } else if (draftData.items) {
                // Nếu có items property
                items = draftData.items;
            }
            
            // Format data từ items
            if (items && items.length > 0) {
                // Lấy thông tin cơ bản từ item đầu tiên
                draftTitle = items[0].Title || items[0].Topic || '';
                draftDescription = items[0].Summary || '';
                
                // Format content từ tất cả các items
                formattedContent = formatItemsToContent(items);
            }
        } catch (e) {
            console.error('Error parsing draft data:', e);
            formattedContent = '<p>Error parsing content: ' + e.message + '</p>';
        }
        
        // Lưu dữ liệu vào global state
        window.DraftWriter.originalContent = formattedContent;
        window.DraftWriter.metadata.title = draftTitle;
        window.DraftWriter.metadata.description = draftDescription;
        
        // Thêm nút "Edit Draft" vào kết quả
        const editButton = `
            <div class="mtop15">
                <button class="btn btn-info open-draft-editor-btn">
                    <i class="fa fa-pencil-square-o"></i> <?php echo _l('edit_draft'); ?>
                </button>
            </div>
        `;
        
        // Thêm nút vào execution result
        $('#draft-writing-result .execution-details').append(editButton);
        
        // Bind sự kiện click cho nút edit
        $('.open-draft-editor-btn').click(function() {
            openDraftEditor(formattedContent, draftTitle, draftDescription, items);
        });
        
        // Hiển thị trước một phần nội dung (preview)
        const previewContent = `
            <div class="mtop15 draft-preview">
                <h5><?php echo _l('content_preview'); ?></h5>
                <div class="draft-preview-content">
                    ${formattedContent.substring(0, 300)}...
                </div>
            </div>
        `;
        
        $('#draft-writing-result .execution-details').append(previewContent);
    }
    
    /**
     * Format array of items into HTML content
     */
    function formatItemsToContent(items) {
        let formattedContent = '';
        
        // Add main title from first item
        if (items[0] && items[0].Title) {
            formattedContent += `<h1>${items[0].Title}</h1>`;
        }
        
        // Add summary/introduction if exists
        if (items[0] && items[0].Summary) {
            formattedContent += `<p class="introduction">${items[0].Summary}</p>`;
        }
        
        // Process all items
        items.forEach((item, index) => {
            if (item.Item_Title && item.Item_Content) {
                // Add heading for each item
                formattedContent += `<h2>${item.Item_Title}</h2>`;
                
                // Add content for each item
                formattedContent += `<div class="item-content">${item.Item_Content}</div>`;
                
                // Add images if available
                if (item.item_Pictures) {
                    let images = [];
                    try {
                        if (typeof item.item_Pictures === 'string') {
                            images = JSON.parse(item.item_Pictures);
                        } else {
                            images = item.item_Pictures;
                        }
                        
                        if (Array.isArray(images) && images.length > 0) {
                            formattedContent += '<div class="item-images">';
                            images.forEach(img => {
                                const imgSrc = img['item_Pictures-src'] || img;
                                if (imgSrc) {
                                    formattedContent += `<img src="${imgSrc}" alt="${item.Item_Title}" class="img-responsive">`;
                                }
                            });
                            formattedContent += '</div>';
                        }
                    } catch (e) {
                        console.error('Error parsing images:', e);
                    }
                }
            }
        });
        
        // Add footer if exists
        if (items[0] && items[0].Topic_footer) {
            formattedContent += `<div class="topic-footer">${items[0].Topic_footer}</div>`;
        }
        
        return formattedContent;
    }
    
    /**
     * Open Draft Editor Modal
     */
    function openDraftEditor(content, title, description, items) {
        // Check if modal already exists and remove it
        if ($('#draft-writer-modal').length > 0) {
            $('#draft-writer-modal').remove();
        }
        
        // Check if we have a saved draft
        const savedDraft = window.DraftWriter.storage.loadDraft();
        
        if (savedDraft) {
            content = savedDraft.content || content;
            title = savedDraft.title || title;
            description = savedDraft.description || description;
            
            // Show notification that we're loading a saved draft
            alert_float('info', '<?php echo _l('loading_saved_draft'); ?>');
        }
        
        // Create modal HTML
        const modalHtml = `
            <div class="modal fade" id="draft-writer-modal" tabindex="-1" role="dialog" data-backdrop="static">
                <div class="modal-dialog modal-fullscreen" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                            <h4 class="modal-title"><?php echo _l('draft_writer'); ?></h4>
                            <div class="draft-save-status">
                                <span id="last-saved-time" class="text-muted">${savedDraft ? '<?php echo _l('last_saved'); ?>: ' + moment(savedDraft.lastSaved).fromNow() : ''}</span>
                            </div>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <!-- Editor Column - 2/3 width -->
                                <div class="col-md-8 editor-column">
                                    <div class="editor-container">
                                        <!-- Title Section -->
                                        <div class="form-group title-section">
                                            <label for="draft-title"><?php echo _l('title'); ?></label>
                                            <div class="input-group">
                                                <input type="text" id="draft-title" class="form-control" value="${escapeHtml(title)}">
                                                <span class="input-group-btn">
                                                    <button class="btn btn-info ai-edit-title-btn" type="button" title="<?php echo _l('ai_edit'); ?>">
                                                        <i class="fa fa-magic"></i>
                                                    </button>
                                                    <button class="btn btn-default quick-save-title-btn" type="button" title="<?php echo _l('quick_save'); ?>">
                                                        <i class="fa fa-save"></i>
                                                    </button>
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <!-- Description Section -->
                                        <div class="form-group description-section">
                                            <label for="draft-description"><?php echo _l('description'); ?></label>
                                            <div class="input-group">
                                                <textarea id="draft-description" class="form-control" rows="3">${escapeHtml(description)}</textarea>
                                                <span class="input-group-btn">
                                                    <button class="btn btn-info ai-edit-description-btn" type="button" title="<?php echo _l('ai_edit'); ?>">
                                                        <i class="fa fa-magic"></i>
                                                    </button>
                                                    <button class="btn btn-default quick-save-description-btn" type="button" title="<?php echo _l('quick_save'); ?>">
                                                        <i class="fa fa-save"></i>
                                                    </button>
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <!-- Content Editor -->
                                        <div class="form-group content-section">
                                            <label for="draft-content"><?php echo _l('content'); ?></label>
                                            <div class="content-editor-toolbar">
                                                <button class="btn btn-info btn-sm ai-edit-content-btn" type="button">
                                                    <i class="fa fa-magic"></i> <?php echo _l('ai_edit'); ?>
                                                </button>
                                                <button class="btn btn-primary btn-sm ai-search-btn" type="button">
                                                    <i class="fa fa-search"></i> <?php echo _l('ai_search'); ?>
                                                </button>
                                                <button class="btn btn-default btn-sm toggle-analysis-btn" type="button">
                                                    <i class="fa fa-bar-chart"></i> <?php echo _l('toggle_analysis'); ?>
                                                </button>
                                            </div>
                                            <textarea id="draft-content" class="form-control">${content}</textarea>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Analysis Column - 1/3 width -->
                                <div class="col-md-4 analysis-column">
                                    <div class="analysis-container">
                                        <h4><?php echo _l('content_analysis'); ?></h4>
                                        
                                        <!-- Stats Panel -->
                                        <div class="panel panel-default stats-panel">
                                            <div class="panel-heading">
                                                <h4 class="panel-title"><?php echo _l('content_stats'); ?></h4>
                                            </div>
                                            <div class="panel-body">
                                                <div class="row stats-row">
                                                    <div class="col-xs-4 stat-item">
                                                        <div class="stat-value" id="word-count">0</div>
                                                        <div class="stat-label"><?php echo _l('words'); ?></div>
                                                    </div>
                                                    <div class="col-xs-4 stat-item">
                                                        <div class="stat-value" id="paragraph-count">0</div>
                                                        <div class="stat-label"><?php echo _l('paragraphs'); ?></div>
                                                    </div>
                                                    <div class="col-xs-4 stat-item">
                                                        <div class="stat-value" id="reading-time">0</div>
                                                        <div class="stat-label"><?php echo _l('min_read'); ?></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Outline Panel -->
                                        <div class="panel panel-default outline-panel">
                                            <div class="panel-heading">
                                                <h4 class="panel-title"><?php echo _l('content_outline'); ?></h4>
                                            </div>
                                            <div class="panel-body">
                                                <div id="content-outline">
                                                    <div class="loading-outline">
                                                        <i class="fa fa-spinner fa-spin"></i> <?php echo _l('generating_outline'); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Keyword Analysis Panel -->
                                        <div class="panel panel-default keyword-panel">
                                            <div class="panel-heading">
                                                <h4 class="panel-title"><?php echo _l('keyword_analysis'); ?></h4>
                                            </div>
                                            <div class="panel-body">
                                                <div id="keyword-cloud" style="height: 200px;"></div>
                                                <div id="keyword-table" class="mtop10">
                                                    <table class="table table-striped table-keywords">
                                                        <thead>
                                                            <tr>
                                                                <th><?php echo _l('keyword'); ?></th>
                                                                <th><?php echo _l('count'); ?></th>
                                                                <th><?php echo _l('density'); ?></th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <!-- Will be populated by JavaScript -->
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- SEO Suggestions Panel -->
                                        <div class="panel panel-default seo-panel">
                                            <div class="panel-heading">
                                                <h4 class="panel-title"><?php echo _l('seo_suggestions'); ?></h4>
                                            </div>
                                            <div class="panel-body">
                                                <ul class="seo-checklist" id="seo-suggestions">
                                                    <!-- Will be populated by JavaScript -->
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <div class="draft-actions">
                                <button type="button" class="btn btn-default auto-save-toggle">
                                    <i class="fa fa-clock-o"></i> <?php echo _l('auto_save'); ?>: <span class="auto-save-status on"><?php echo _l('on'); ?></span>
                                </button>
                                <button type="button" class="btn btn-default cancel-draft-btn" data-dismiss="modal">
                                    <i class="fa fa-times"></i> <?php echo _l('cancel'); ?>
                                </button>
                                <button type="button" class="btn btn-info save-draft-btn">
                                    <i class="fa fa-save"></i> <?php echo _l('save_draft'); ?>
                                </button>
                                <button type="button" class="btn btn-success publish-draft-btn">
                                    <i class="fa fa-globe"></i> <?php echo _l('publish'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Append modal to body
        $('body').append(modalHtml);
        
        // Initialize TinyMCE
        initDraftEditor();
        
        // Show modal
        $('#draft-writer-modal').modal('show');
        
        // Bind events
        setupDraftEditorEvents();
        
        // Start auto-save timer
        startAutoSave();
    }
    
    /**
     * Initialize TinyMCE editor
     */
    function initDraftEditor() {
        // Initialize TinyMCE
        tinymce.init({
            selector: '#draft-content',
            height: 500,
            plugins: [
                'advlist autolink lists link image charmap print preview anchor',
                'searchreplace visualblocks code fullscreen',
                'insertdatetime media table paste code help wordcount'
            ],
            toolbar: 'undo redo | formatselect | bold italic backcolor | \
                     alignleft aligncenter alignright alignjustify | \
                     bullist numlist outdent indent | removeformat | help',
            setup: function(editor) {
                // Store editor reference
                window.DraftWriter.editor = editor;
                
                // On init
                editor.on('init', function() {
                    // Initial analysis
                    analyzeContent();
                });
                
                // On content change
                editor.on('change', function() {
                    // Mark as changed
                    window.DraftWriter.hasChanges = true;
                    
                    // Update analysis
                    analyzeContent();
                });
                
                // On selection change - for contextual actions
                editor.on('SelectionChange', function() {
                    handleSelectionChange(editor);
                });
            }
        });
    }
    
    /**
     * Setup events for Draft Editor
     */
    function setupDraftEditorEvents() {
        // AI Edit Title button
        $('.ai-edit-title-btn').click(function() {
            const title = $('#draft-title').val();
            showPromptSelectionModal(title, 'title', function(prompt) {
                // Show loading
                $(this).html('<i class="fa fa-spinner fa-spin"></i>');
                
                // Call AI api
                callAIEditAPI(title, 'title', prompt, function(newTitle) {
                    // Update title
                    $('#draft-title').val(newTitle);
                    
                    // Mark as changed
                    window.DraftWriter.hasChanges = true;
                    
                    // Reset button
                    $('.ai-edit-title-btn').html('<i class="fa fa-magic"></i>');
                });
            });
        });
        
        // AI Edit Description button
        $('.ai-edit-description-btn').click(function() {
            const description = $('#draft-description').val();
            showPromptSelectionModal(description, 'description', function(prompt) {
                // Show loading
                $(this).html('<i class="fa fa-spinner fa-spin"></i>');
                
                // Call AI api
                callAIEditAPI(description, 'description', prompt, function(newDescription) {
                    // Update description
                    $('#draft-description').val(newDescription);
                    
                    // Mark as changed
                    window.DraftWriter.hasChanges = true;
                    
                    // Reset button
                    $('.ai-edit-description-btn').html('<i class="fa fa-magic"></i>');
                });
            });
        });
        
        // AI Edit Content button
        $('.ai-edit-content-btn').click(function() {
            const content = window.DraftWriter.editor.getContent();
            showPromptSelectionModal(content, 'content', function(prompt) {
                // Show loading
                $('.ai-edit-content-btn').html('<i class="fa fa-spinner fa-spin"></i>');
                
                // Call AI api
                callAIEditAPI(content, 'content', prompt, function(newContent) {
                    // Update content
                    window.DraftWriter.editor.setContent(newContent);
                    
                    // Mark as changed
                    window.DraftWriter.hasChanges = true;
                    
                    // Reset button
                    $('.ai-edit-content-btn').html('<i class="fa fa-magic"></i> <?php echo _l('ai_edit'); ?>');
                    
                    // Update analysis
                    analyzeContent();
                });
            });
        });
        
        // AI Search button
        $('.ai-search-btn').click(function() {
            const content = window.DraftWriter.editor.getContent();
            showAISearchModal(content, 'content', function(newContent) {
                // Update content
                window.DraftWriter.editor.setContent(newContent);
                
                // Mark as changed
                window.DraftWriter.hasChanges = true;
                
                // Update analysis
                analyzeContent();
            });
        });
        
        // Toggle Analysis column on mobile
        $('.toggle-analysis-btn').click(function() {
            $('.analysis-column').toggleClass('visible-xs visible-sm');
        });
        
        // Quick Save Title
        $('.quick-save-title-btn').click(function() {
            // Show loading
            $(this).html('<i class="fa fa-spinner fa-spin"></i>');
            
            // Save to localStorage
            window.DraftWriter.storage.saveDraft();
            
            // Reset button after short delay
            setTimeout(() => {
                $('.quick-save-title-btn').html('<i class="fa fa-save"></i>');
            }, 1000);
        });
        
        // Quick Save Description
        $('.quick-save-description-btn').click(function() {
            // Show loading
            $(this).html('<i class="fa fa-spinner fa-spin"></i>');
            
            // Save to localStorage
            window.DraftWriter.storage.saveDraft();
            
            // Reset button after short delay
            setTimeout(() => {
                $('.quick-save-description-btn').html('<i class="fa fa-save"></i>');
            }, 1000);
        });
        
        // Auto Save toggle
        $('.auto-save-toggle').click(function() {
            const $status = $(this).find('.auto-save-status');
            if ($status.hasClass('on')) {
                $status.removeClass('on').addClass('off').text('<?php echo _l('off'); ?>');
                stopAutoSave();
            } else {
                $status.removeClass('off').addClass('on').text('<?php echo _l('on'); ?>');
                startAutoSave();
            }
        });
        
        // Save Draft button
        $('.save-draft-btn').click(function() {
            // Save to server
            saveDraftToServer();
        });
        
        // Publish button
        $('.publish-draft-btn').click(function() {
            // Confirm publish
            if (confirm('<?php echo _l('confirm_publish_draft'); ?>')) {
                // Set publish flag
                const draftData = prepareSubmissionData();
                draftData.publish = true;
                
                // Submit
                submitDraft(draftData);
            }
        });
        
        // Cancel button - confirm if there are changes
        $('.cancel-draft-btn').click(function(e) {
            if (window.DraftWriter.hasChanges) {
                if (!confirm('<?php echo _l('discard_changes_confirmation'); ?>')) {
                    e.preventDefault();
                    return false;
                }
            }
        });
        
        // Bind click events on outline items to scroll to that heading
        $('#content-outline').on('click', '.outline-item', function() {
            const headingId = $(this).data('id');
            if (headingId) {
                const editor = window.DraftWriter.editor;
                const dom = editor.dom;
                const heading = dom.select('#' + headingId)[0];
                if (heading) {
                    editor.selection.scrollIntoView(heading);
                }
            }
        });
    }
    
    /**
     * Handle selection change in editor
     */
    function handleSelectionChange(editor) {
        const selection = editor.selection.getContent();
        
        // If there is selected text and it's not too short
        if (selection && selection.length > 10) {
            // Check if selection toolbar already exists
            if ($('#selection-toolbar').length === 0) {
                // Create toolbar
                const toolbarHtml = `
                    <div id="selection-toolbar" class="selection-toolbar">
                        <button class="btn btn-xs btn-primary selection-rewrite-btn">
                            <i class="fa fa-pencil"></i> <?php echo _l('rewrite'); ?>
                        </button>
                        <button class="btn btn-xs btn-info selection-improve-btn">
                            <i class="fa fa-arrow-up"></i> <?php echo _l('improve'); ?>
                        </button>
                        <button class="btn btn-xs btn-default selection-factcheck-btn">
                            <i class="fa fa-check-circle"></i> <?php echo _l('fact_check'); ?>
                        </button>
                        <button class="btn btn-xs btn-success selection-expand-btn">
                            <i class="fa fa-expand"></i> <?php echo _l('expand'); ?>
                        </button>
                    </div>
                `;
                
                // Append to editor container
                $('.content-section').append(toolbarHtml);
                
                // Position toolbar near selection
                positionSelectionToolbar();
                
                // Bind events
                bindSelectionToolbarEvents(editor);
            } else {
                // Update toolbar position
                positionSelectionToolbar();
            }
        } else {
            // Remove toolbar if no selection
            $('#selection-toolbar').remove();
        }
    }
    
    /**
     * Position selection toolbar near cursor
     */
    function positionSelectionToolbar() {
        const toolbar = $('#selection-toolbar');
        if (!toolbar.length) return;
        
        // Get editor info
        const editor = window.DraftWriter.editor;
        const selection = editor.selection;
        const range = selection.getRng();
        const rect = range.getBoundingClientRect();
        
        // Position toolbar above selection
        const editorRect = editor.getContainer().getBoundingClientRect();
        const toolbarHeight = toolbar.outerHeight();
        
        toolbar.css({
            position: 'absolute',
            left: rect.left - editorRect.left + (rect.width / 2) - (toolbar.outerWidth() / 2),
            top: rect.top - editorRect.top - toolbarHeight - 10
        });
    }
    
    /**
     * Bind events for selection toolbar
     */
    function bindSelectionToolbarEvents(editor) {
        // Rewrite button
        $('.selection-rewrite-btn').click(function() {
            const selection = editor.selection.getContent();
            showPromptSelectionModal(selection, 'selection', function(prompt) {
                callAIEditAPI(selection, 'selection', prompt, function(newContent) {
                    editor.selection.setContent(newContent);
                    window.DraftWriter.hasChanges = true;
                    $('#selection-toolbar').remove();
                });
            });
        });
        
        // Improve button
        $('.selection-improve-btn').click(function() {
            const selection = editor.selection.getContent();
            callAIEditAPI(selection, 'selection', 'Improve this content', function(newContent) {
                editor.selection.setContent(newContent);
                window.DraftWriter.hasChanges = true;
                $('#selection-toolbar').remove();
            });
        });
        
        // Fact check button
        $('.selection-factcheck-btn').click(function() {
            const selection = editor.selection.getContent();
            showAISearchModal(selection, 'selection', function(searchResults) {
                // Display results in modal
                showFactCheckResults(searchResults, selection, function(updatedContent) {
                    if (updatedContent) {
                        editor.selection.setContent(updatedContent);
                        window.DraftWriter.hasChanges = true;
                    }
                    $('#selection-toolbar').remove();
                });
            });
        });
        
        // Expand button
        $('.selection-expand-btn').click(function() {
            const selection = editor.selection.getContent();
            callAIEditAPI(selection, 'selection', 'Expand this content with more details', function(newContent) {
                editor.selection.setContent(newContent);
                window.DraftWriter.hasChanges = true;
                $('#selection-toolbar').remove();
            });
        });
    }
    
    /**
     * Show fact check results in modal
     */
    function showFactCheckResults(results, originalText, callback) {
        // Create modal for fact check results
        const modalHtml = `
            <div class="modal fade" id="fact-check-modal" tabindex="-1" role="dialog">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                            <h4 class="modal-title"><?php echo _l('fact_check_results'); ?></h4>
                        </div>
                        <div class="modal-body">
                            <div class="original-text">
                                <h5><?php echo _l('original_text'); ?></h5>
                                <div class="well">${originalText}</div>
                            </div>
                            <div class="fact-check-results">
                                <h5><?php echo _l('fact_check_findings'); ?></h5>
                                <div class="results-container">
                                    ${formatFactCheckResults(results)}
                                </div>
                            </div>
                            <div class="suggested-correction mtop15">
                                <h5><?php echo _l('suggested_correction'); ?></h5>
                                <textarea class="form-control" id="correction-text" rows="5">${results.suggestedCorrection || originalText}</textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('cancel'); ?></button>
                            <button type="button" class="btn btn-primary apply-correction-btn"><?php echo _l('apply_correction'); ?></button>
                        </div>
                                            </div>
                </div>
            </div>
        `;
        
        // Add modal to body
        $('body').append(modalHtml);
        const $modal = $('#fact-check-modal');
        $modal.modal('show');
        
        // Apply correction button
        $('.apply-correction-btn').click(function() {
            const correctionText = $('#correction-text').val();
            $modal.modal('hide');
            callback(correctionText);
        });
    }
    
    /**
     * Format fact check results
     */
    function formatFactCheckResults(results) {
        if (!results || !results.findings) {
            return '<div class="alert alert-info"><?php echo _l('no_fact_check_findings'); ?></div>';
        }
        
        let html = '<ul class="fact-check-findings">';
        
        results.findings.forEach(finding => {
            const statusClass = finding.accurate ? 'text-success' : 'text-danger';
            const statusIcon = finding.accurate ? 'fa-check-circle' : 'fa-times-circle';
            
            html += `
                <li class="finding-item">
                    <div class="finding-status ${statusClass}">
                        <i class="fa ${statusIcon}"></i>
                    </div>
                    <div class="finding-content">
                        <div class="finding-text">${finding.text}</div>
                        <div class="finding-explanation">${finding.explanation}</div>
                        ${finding.sources ? `
                            <div class="finding-sources">
                                <small><?php echo _l('sources'); ?>:</small>
                                <ul class="sources-list">
                                    ${finding.sources.map(source => `
                                        <li><a href="${source.url}" target="_blank">${source.title}</a></li>
                                    `).join('')}
                                </ul>
                            </div>
                        ` : ''}
                    </div>
                </li>
            `;
        });
        
        html += '</ul>';
        return html;
    }
    
    /**
     * Initialize content analysis
     */
    function initContentAnalysis() {
        // Update analysis when content changes
        window.DraftWriter.editor.on('keyup', debounce(updateContentAnalysis, 1000));
        window.DraftWriter.editor.on('change', debounce(updateContentAnalysis, 1000));
        
        // Initial analysis
        updateContentAnalysis();
    }
    
    /**
     * Update content analysis
     */
    function updateContentAnalysis() {
        const editor = window.DraftWriter.editor;
        if (!editor) return;
        
        const content = editor.getContent();
        const textContent = stripHtml(content);
        const title = $('#draft-title').val();
        const description = $('#draft-description').val();
        
        // Update word count
        const wordCount = countWords(textContent);
        window.DraftWriter.analysis.wordCount = wordCount;
        $('#word-count').text(wordCount);
        
        // Update reading time (average 200 words per minute)
        const readingTime = Math.ceil(wordCount / 200);
        window.DraftWriter.analysis.readingTime = readingTime;
        $('#reading-time').text(readingTime > 0 ? readingTime : '<1');
        
        // Update headings outline
        updateHeadingsOutline(content);
        
        // Update keyword analysis
        updateKeywordAnalysis(textContent, title, description);
        
        // Update SEO analysis
        updateSeoAnalysis(content, title, description);
    }
    
    /**
     * Update headings outline
     */
    function updateHeadingsOutline(content) {
        const $outlineContainer = $('#content-outline');
        const headingRegex = /<h([1-6])(?:[^>]*)>(.*?)<\/h\1>/gi;
        const headings = [];
        let match;
        
        // Clear current outline
        $outlineContainer.empty();
        
        // Extract headings
        while ((match = headingRegex.exec(content)) !== null) {
            const level = parseInt(match[1]);
            const text = stripHtml(match[2]);
            const id = 'heading-' + (headings.length + 1);
            
            headings.push({
                level: level,
                text: text,
                id: id
            });
        }
        
        // Store headings in analysis
        window.DraftWriter.analysis.headings = headings;
        
        // If no headings, show placeholder
        if (headings.length === 0) {
            $outlineContainer.html('<div class="alert alert-info"><?php echo _l('no_headings_found'); ?></div>');
            return;
        }
        
        // Build outline
        let outlineHtml = '<ul class="outline-list">';
        
        headings.forEach((heading, index) => {
            // Assign ID to heading in editor
            injectHeadingId(heading, index);
            
            // Add to outline
            outlineHtml += `
                <li class="outline-item outline-level-${heading.level}" data-id="${heading.id}">
                    <span class="outline-indicator">${'•'.repeat(heading.level)}</span>
                    <span class="outline-text">${heading.text}</span>
                </li>
            `;
        });
        
        outlineHtml += '</ul>';
        $outlineContainer.html(outlineHtml);
    }
    
    /**
     * Inject ID to heading in editor
     */
    function injectHeadingId(heading, index) {
        const editor = window.DraftWriter.editor;
        const headings = editor.dom.select('h1, h2, h3, h4, h5, h6');
        
        if (headings[index]) {
            editor.dom.setAttrib(headings[index], 'id', heading.id);
        }
    }
    
    /**
     * Update keyword analysis
     */
    function updateKeywordAnalysis(textContent, title, description) {
        // Extract keywords
        const keywords = extractKeywords(textContent);
        const $keywordsContainer = $('#keyword-cloud');
        
        // Store keywords
        window.DraftWriter.analysis.keywords = keywords;
        
        // Clear current keywords
        $keywordsContainer.empty();
        
        // Build keyword cloud
        if ($.fn.jQCloud) {
            const cloudData = Object.keys(keywords).map(keyword => {
                return {
                    text: keyword,
                    weight: keywords[keyword],
                    link: '#'
                };
            }).filter(item => item.weight > 1)
            .sort((a, b) => b.weight - a.weight)
            .slice(0, 30);
            
            // Destroy existing cloud
            if ($keywordsContainer.data('jqcloud')) {
                $keywordsContainer.jQCloud('destroy');
            }
            
            // Create new cloud
            if (cloudData.length > 0) {
                $keywordsContainer.jQCloud(cloudData, {
                    width: $keywordsContainer.width(),
                    height: 200,
                    autoResize: true
                });
            } else {
                $keywordsContainer.html('<div class="alert alert-info"><?php echo _l('not_enough_content_for_analysis'); ?></div>');
            }
        } else {
            // Fallback if jQCloud not available
            let keywordHtml = '<div class="keyword-list">';
            
            Object.keys(keywords).sort((a, b) => keywords[b] - keywords[a])
                .slice(0, 15)
                .forEach(keyword => {
                    keywordHtml += `
                        <span class="keyword-tag" style="font-size: ${10 + keywords[keyword]}px">
                            ${keyword} (${keywords[keyword]})
                        </span>
                    `;
                });
            
            keywordHtml += '</div>';
            $keywordsContainer.html(keywordHtml);
        }
        
        // Update keyword density
        updateKeywordDensity(keywords, textContent);
    }
    
    /**
     * Update keyword density analysis
     */
    function updateKeywordDensity(keywords, textContent) {
        const $densityContainer = $('#keyword-density');
        const totalWords = countWords(textContent);
        
        // Clear current density
        $densityContainer.empty();
        
        // If not enough content
        if (totalWords < 100) {
            $densityContainer.html('<div class="alert alert-info"><?php echo _l('not_enough_content_for_density'); ?></div>');
            return;
        }
        
        // Sort keywords by count
        const sortedKeywords = Object.keys(keywords)
            .filter(k => k.length > 3) // Ignore short words
            .sort((a, b) => keywords[b] - keywords[a])
            .slice(0, 10);
        
        // Calculate density
        const density = {};
        sortedKeywords.forEach(keyword => {
            density[keyword] = (keywords[keyword] / totalWords * 100).toFixed(2);
        });
        
        // Store density in analysis
        window.DraftWriter.analysis.density = density;
        
        // Build density table
        let densityHtml = `
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th><?php echo _l('keyword'); ?></th>
                        <th><?php echo _l('count'); ?></th>
                        <th><?php echo _l('density'); ?></th>
                    </tr>
                </thead>
                <tbody>
        `;
        
        sortedKeywords.forEach(keyword => {
            const densityValue = parseFloat(density[keyword]);
            let statusClass = '';
            
            // Ideal density is between 0.5% and 2.5%
            if (densityValue < 0.5) {
                statusClass = 'text-muted';
            } else if (densityValue > 2.5) {
                statusClass = 'text-danger';
            } else {
                statusClass = 'text-success';
            }
            
            densityHtml += `
                <tr>
                    <td>${keyword}</td>
                    <td>${keywords[keyword]}</td>
                    <td class="${statusClass}">${density[keyword]}%</td>
                </tr>
            `;
        });
        
        densityHtml += `
                </tbody>
            </table>
        `;
        
        $densityContainer.html(densityHtml);
    }
    
    /**
     * Update SEO analysis
     */
    function updateSeoAnalysis(content, title, description) {
        const $seoContainer = $('#seo-analysis');
        const textContent = stripHtml(content);
        const wordCount = countWords(textContent);
        
        // Clear current analysis
        $seoContainer.empty();
        
        const seoChecks = [
            // Title checks
            {
                id: 'title-length',
                status: title.length > 0 && title.length <= 60,
                message: title.length > 60 
                    ? '<?php echo _l('seo_title_too_long'); ?>'
                    : (title.length === 0 ? '<?php echo _l('seo_title_missing'); ?>' : '<?php echo _l('seo_title_good'); ?>')
            },
            
            // Meta description checks
            {
                id: 'meta-description',
                status: description.length > 0 && description.length <= 160,
                message: description.length > 160 
                    ? '<?php echo _l('seo_description_too_long'); ?>'
                    : (description.length === 0 ? '<?php echo _l('seo_description_missing'); ?>' : '<?php echo _l('seo_description_good'); ?>')
            },
            
            // Content length
            {
                id: 'content-length',
                status: wordCount >= 300,
                message: wordCount < 300 
                    ? '<?php echo _l('seo_content_too_short'); ?>'
                    : '<?php echo _l('seo_content_good_length'); ?>'
            },
            
            // Headings check
            {
                id: 'headings',
                status: window.DraftWriter.analysis.headings.length > 0,
                message: window.DraftWriter.analysis.headings.length === 0
                    ? '<?php echo _l('seo_no_headings'); ?>'
                    : '<?php echo _l('seo_has_headings'); ?>'
            },
            
            // H1 check
            {
                id: 'h1-check',
                status: window.DraftWriter.analysis.headings.some(h => h.level === 1),
                message: window.DraftWriter.analysis.headings.some(h => h.level === 1)
                    ? '<?php echo _l('seo_has_h1'); ?>'
                    : '<?php echo _l('seo_no_h1'); ?>'
            },
            
            // Image alt text check
            {
                id: 'image-alt',
                status: checkImagesAltText(content),
                message: checkImagesAltText(content)
                    ? '<?php echo _l('seo_images_have_alt'); ?>'
                    : '<?php echo _l('seo_images_missing_alt'); ?>'
            }
        ];
        
        // Build SEO analysis
        let seoHtml = '<ul class="seo-checklist">';
        
        seoChecks.forEach(check => {
            const statusClass = check.status ? 'text-success' : 'text-danger';
            const statusIcon = check.status ? 'fa-check-circle' : 'fa-times-circle';
            
            seoHtml += `
                <li class="seo-check-item" id="${check.id}">
                    <div class="check-status ${statusClass}">
                        <i class="fa ${statusIcon}"></i>
                    </div>
                    <div class="check-message">
                        ${check.message}
                    </div>
                </li>
            `;
        });
        
        seoHtml += '</ul>';
        $seoContainer.html(seoHtml);
    }
    
    /**
     * Check if images have alt text
     */
    function checkImagesAltText(content) {
        const imgRegex = /<img [^>]*src="[^"]*"[^>]*>/gi;
        const altRegex = /alt="[^"]*"/i;
        
        const images = content.match(imgRegex) || [];
        
        // If no images, return true (no issue)
        if (images.length === 0) return true;
        
        // Check if all images have alt text
        return images.every(img => altRegex.test(img));
    }
    
    /**
     * Extract keywords from text
     */
    function extractKeywords(text) {
        // Common words to ignore (stopwords)
        const stopwords = [
            'a', 'about', 'above', 'after', 'again', 'against', 'all', 'am', 'an', 'and', 'any',
            'are', 'as', 'at', 'be', 'because', 'been', 'before', 'being', 'below', 'between',
            'both', 'but', 'by', 'can', 'did', 'do', 'does', 'doing', 'down', 'during', 'each',
            'few', 'for', 'from', 'further', 'had', 'has', 'have', 'having', 'he', 'her', 'here',
            'hers', 'herself', 'him', 'himself', 'his', 'how', 'i', 'if', 'in', 'into', 'is', 'it',
            'its', 'itself', 'just', 'me', 'more', 'most', 'my', 'myself', 'no', 'nor', 'not', 'now',
            'of', 'off', 'on', 'once', 'only', 'or', 'other', 'our', 'ours', 'ourselves', 'out',
            'over', 'own', 'same', 'she', 'should', 'so', 'some', 'such', 'than', 'that', 'the',
            'their', 'theirs', 'them', 'themselves', 'then', 'there', 'these', 'they', 'this',
            'those', 'through', 'to', 'too', 'under', 'until', 'up', 'very', 'was', 'we', 'were',
            'what', 'when', 'where', 'which', 'while', 'who', 'whom', 'why', 'will', 'with', 'you',
            'your', 'yours', 'yourself', 'yourselves'
        ];
        
        // Clean text and split into words
        const words = text.toLowerCase()
            .replace(/[^\w\s]/g, ' ')
            .split(/\s+/)
            .filter(word => word.length > 2 && !stopwords.includes(word));
        
        // Count keywords
        const keywords = {};
        words.forEach(word => {
            keywords[word] = (keywords[word] || 0) + 1;
        });
        
        return keywords;
    }
    
    /**
     * Strip HTML from text
     */
    function stripHtml(html) {
        const tmp = document.createElement('div');
        tmp.innerHTML = html;
        return tmp.textContent || tmp.innerText || '';
    }
    
    /**
     * Count words in a text
     */
    function countWords(text) {
        return text.split(/\s+/).filter(word => word.length > 0).length;
    }
    
    /**
     * Debounce function to limit frequent calls
     */
    function debounce(func, wait) {
        let timeout;
        return function() {
            const context = this;
            const args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                func.apply(context, args);
            }, wait);
        };
    }
    
    /**
     * Format content from items
     */
    function formatContentFromItems(items) {
        if (!items || !items.length) return '';
        
        let formattedContent = '';
        
        // Add title
        if (items[0].Title) {
            formattedContent += `<h1>${items[0].Title}</h1>`;
        }
        
        // Add summary if available
        if (items[0].Summary) {
            formattedContent += `<p class="summary">${items[0].Summary}</p>`;
        }
        
        // Process items
        items.forEach((item, index) => {
            // Add item title as heading
            if (item.Item_Title) {
                formattedContent += `<h2>${item.Item_Title}</h2>`;
            }
            
            // Add item content
            if (item.Item_Content) {
                formattedContent += item.Item_Content;
            }
            
            // Add images if available
            if (item.item_Pictures && item.item_Pictures.length) {
                formattedContent += '<div class="item-images">';
                
                try {
                    // Parse item_Pictures if it's a string
                    const images = typeof item.item_Pictures === 'string' 
                        ? JSON.parse(item.item_Pictures) 
                        : item.item_Pictures;
                    
                    images.forEach(img => {
                        const imgSrc = img.src || img;
                        formattedContent += `
                            <figure class="item-image">
                                <img src="${imgSrc}" alt="${item.Item_Title || 'Image'}" />
                                <figcaption>${item.Item_Title || 'Image'}</figcaption>
                            </figure>
                        `;
                    });
                } catch (e) {
                    console.error('Error parsing images:', e);
                }
                
                formattedContent += '</div>';
            }
            
            // Add separator if not last item
            if (index < items.length - 1) {
                formattedContent += '<hr>';
            }
        });
        
        // Add footer if available
        if (items[0].Topic_footer) {
            formattedContent += `
                <div class="topic-footer">
                    <h3><?php echo _l('conclusion'); ?></h3>
                    ${items[0].Topic_footer}
                </div>
            `;
        }
        
        return formattedContent;
    }
    
    /**
     * Poll workflow status
     */
    function pollWorkflowStatus(workflowId, executionId, workflowData, onSuccess) {
        var pollInterval = 10000; // 10 seconds
        var maxAttempts = 30; // 5 minutes maximum
        var attempts = 0;
        var timeRemaining = maxAttempts * (pollInterval/1000); // Total time in seconds
        var countdownInterval;

        // Add poll info container after progress bar
        var pollInfoHtml = `
            <div class="poll-info mtop5">
                <div class="poll-count text-muted">
                    <span class="attempts-counter">Polling: 0/${maxAttempts}</span>
                    <span class="countdown-timer pull-right">Time remaining: 5:00</span>
                </div>
            </div>
        `;
        $('#draft-writing-result .execution-details').after(pollInfoHtml);

        function updateCountdown() {
            timeRemaining--;
            if (timeRemaining >= 0) {
                var minutes = Math.floor(timeRemaining / 60);
                var seconds = timeRemaining % 60;
                var timeString = String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
                $('#draft-writing-result .countdown-timer').text('Time remaining: ' + timeString);
            }
        }

        function poll() {
            attempts++;
            
            // Update attempts counter
            $('#draft-writing-result .attempts-counter').text(`Polling: ${attempts}/${maxAttempts}`);

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
                        console.log("Poll response:", response);
                        
                        if (!response.success) {
                            if (countdownInterval) clearInterval(countdownInterval);
                            $('#draft-writing-result .poll-info').remove();
                            $('#draft-writing-result .execution-status')
                                .html('<i class="fa fa-times text-danger"></i> <?php echo _l('workflow_check_failed'); ?>');
                            return;
                        }
                        
                        if (response.finished === true) {
                            if (countdownInterval) clearInterval(countdownInterval);
                            $('#draft-writing-result .poll-info').remove();
                            
                            if (response.data && response.data.success) {
                                onSuccess(response.data);
                            } else {
                                $('#draft-writing-result .execution-status')
                                    .html('<i class="fa fa-warning text-warning"></i> ' + 
                                        (response.message || '<?php echo _l('error_processing_draft'); ?>'));
                            }
                            return;
                        }
                        
                        // Update progress status
                        $('.status-text').text(response.status || 'processing');
                        
                        // Continue polling if not finished
                        if (attempts < maxAttempts) {
                            setTimeout(poll, pollInterval);
                        } else {
                            if (countdownInterval) clearInterval(countdownInterval);
                            $('#draft-writing-result .poll-info').remove();
                            $('#draft-writing-result .execution-status')
                                .html('<i class="fa fa-warning text-warning"></i> <?php echo _l('polling_timeout'); ?>');
                        }
                    } catch (e) {
                        console.error('Polling error:', e);
                    }
                },
                error: function() {
                    if (countdownInterval) clearInterval(countdownInterval);
                    $('#draft-writing-result .poll-info').remove();
                    $('#draft-writing-result .execution-status')
                        .html('<i class="fa fa-times text-danger"></i> <?php echo _l('polling_error'); ?>');
                }
            });
        }

        // Start countdown timer
        countdownInterval = setInterval(updateCountdown, 1000);

        // Start polling
        poll();
    }
    
    /**
     * Prepare submission data for step 2
     */
    function prepareSubmissionData() {
        // Lấy dữ liệu từ editor
        const content = window.DraftWriter.editor ? window.DraftWriter.editor.getContent() : '';
        const title = $('#draft-title').val();
        const description = $('#draft-description').val();
        
        return {
            title: title,
            description: description,
            content: content,
            original_content: window.DraftWriter.originalContent,
            has_changes: window.DraftWriter.hasChanges,
            analysis: window.DraftWriter.analysis
        };
    }
    
    /**
     * Save draft to server
     */
    function saveDraftToServer() {
        const $btn = $('.save-draft-btn');
        
        // Lấy dữ liệu workflow từ step 1 đã lưu
        const baseWorkflowData = window.currentWorkflowData || {};

        // Thêm dữ liệu changes vào workflowData gốc
        const extendedData = {
            ...baseWorkflowData,
            audit_step: 2,
            changes_data: prepareSubmissionData()
        };

        // Disable button và hiển thị loading
        $btn.prop('disabled', true)
           .html('<i class="fa fa-spinner fa-spin"></i> <?php echo _l('saving_draft'); ?>');

        // Gọi hàm executeWorkflow có sẵn
        executeWorkflow(extendedData, function(response) {
            // Re-enable button
            $btn.prop('disabled', false)
               .html('<?php echo _l('save_draft'); ?>');
               
            if (response.success) {
                // Clear local storage draft after successful save
                window.DraftWriter.storage.clearDraft();
                
                // Show success message
                alert_float('success', response.message || '<?php echo _l('draft_saved_successfully'); ?>');
            } else {
                // Show error message
                alert_float('danger', response.message || '<?php echo _l('error_saving_draft'); ?>');
            }
        });
    }
    
    /**
     * Submit draft for publishing
     */
    function submitDraft(draftData) {
        const $btn = $('.publish-draft-btn');
        
        // Lấy dữ liệu workflow từ step 1 đã lưu
        const baseWorkflowData = window.currentWorkflowData || {};

        // Thêm dữ liệu changes vào workflowData gốc
        const extendedData = {
            ...baseWorkflowData,
            audit_step: 2,
            publish: true,
            changes_data: draftData
        };

        // Disable button và hiển thị loading
        $btn.prop('disabled', true)
           .html('<i class="fa fa-spinner fa-spin"></i> <?php echo _l('publishing'); ?>');

        // Gọi hàm executeWorkflow có sẵn
        executeWorkflow(extendedData, function(response) {
            // Re-enable button
            $btn.prop('disabled', false)
               .html('<?php echo _l('publish'); ?>');
               
            if (response.success) {
                // Clear local storage draft after successful save
                window.DraftWriter.storage.clearDraft();
                
                // Show success message
                alert_float('success', response.message || '<?php echo _l('draft_published_successfully'); ?>');
                
                // Close modal
                $('#draft-writer-modal').modal('hide');
                
                // Reload page if needed
                if (response.data && response.data.reload) {
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                }
            } else {
                // Show error message
                alert_float('danger', response.message || '<?php echo _l('error_publishing_draft'); ?>');
            }
        });
    }
}
</script>

<!-- Modal markup for Draft Writer -->
<div class="modal fade" id="draft-writer-modal" tabindex="-1" role="dialog" aria-labelledby="draft-writer-modal-label">
    <div class="modal-dialog modal-fullscreen" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close cancel-draft-btn" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="draft-writer-modal-label"><?php echo _l('draft_writer'); ?></h4>
                <div class="auto-save-status">
                    <span class="auto-save-toggle">
                        <span class="toggle-label"><?php echo _l('auto_save'); ?>:</span>
                        <span class="toggle-status off"><?php echo _l('off'); ?></span>
                    </span>
                    <span class="last-saved">
                        <span class="last-saved-label"><?php echo _l('last_saved'); ?>:</span>
                        <span id="last-saved-time"><?php echo _l('never'); ?></span>
                    </span>
                </div>
            </div>
            <div class="modal-body">
                <div class="row">
                    <!-- Editor Column (2/3) -->
                    <div class="col-md-8 editor-column">
                        <!-- Title Section -->
                        <div class="title-section">
                            <div class="form-group">
                                <label for="draft-title"><?php echo _l('title'); ?></label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="draft-title" placeholder="<?php echo _l('enter_title'); ?>">
                                    <span class="input-group-btn">
                                        <button class="btn btn-default title-ai-edit-btn" type="button">
                                            <i class="fa fa-magic"></i> <?php echo _l('ai_edit'); ?>
                                        </button>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Description Section -->
                        <div class="description-section">
                            <div class="form-group">
                                <label for="draft-description"><?php echo _l('meta_description'); ?></label>
                                <div class="input-group">
                                    <textarea class="form-control" id="draft-description" rows="2" placeholder="<?php echo _l('enter_description'); ?>"></textarea>
                                    <span class="input-group-btn">
                                        <button class="btn btn-default description-ai-edit-btn" type="button">
                                            <i class="fa fa-magic"></i> <?php echo _l('ai_edit'); ?>
                                        </button>
                                    </span>
                                </div>
                                <small class="text-muted description-counter">0/160 <?php echo _l('characters'); ?></small>
                            </div>
                        </div>
                        
                        <!-- Content Section -->
                        <div class="content-section">
                            <div class="form-group">
                                <label for="draft-content"><?php echo _l('content'); ?></label>
                                <div class="content-toolbar">
                                    <button class="btn btn-sm btn-primary content-ai-edit-btn">
                                        <i class="fa fa-magic"></i> <?php echo _l('ai_edit'); ?>
                                    </button>
                                    <button class="btn btn-sm btn-info content-ai-search-btn">
                                        <i class="fa fa-search"></i> <?php echo _l('ai_search'); ?>
                                    </button>
                                </div>
                                <textarea id="draft-content" class="form-control"></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Analysis Column (1/3) -->
                    <div class="col-md-4 analysis-column">
                        <div class="analysis-panel">
                            <ul class="nav nav-tabs" role="tablist">
                                <li role="presentation" class="active">
                                    <a href="#outline-tab" aria-controls="outline-tab" role="tab" data-toggle="tab">
                                        <i class="fa fa-list"></i> <?php echo _l('outline'); ?>
                                    </a>
                                </li>
                                <li role="presentation">
                                    <a href="#analysis-tab" aria-controls="analysis-tab" role="tab" data-toggle="tab">
                                        <i class="fa fa-bar-chart"></i> <?php echo _l('analysis'); ?>
                                    </a>
                                </li>
                                <li role="presentation">
                                    <a href="#seo-tab" aria-controls="seo-tab" role="tab" data-toggle="tab">
                                        <i class="fa fa-search"></i> <?php echo _l('seo'); ?>
                                    </a>
                                </li>
                            </ul>
                            
                            <div class="tab-content">
                                <!-- Outline Tab -->
                                <div role="tabpanel" class="tab-pane active" id="outline-tab">
                                    <div class="panel-section">
                                        <h4 class="section-title"><?php echo _l('content_outline'); ?></h4>
                                        <div id="content-outline" class="section-content">
                                            <div class="alert alert-info"><?php echo _l('outline_will_appear_here'); ?></div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Analysis Tab -->
                                <div role="tabpanel" class="tab-pane" id="analysis-tab">
                                    <div class="panel-section">
                                        <h4 class="section-title"><?php echo _l('content_stats'); ?></h4>
                                        <div class="section-content">
                                            <div class="stats-item">
                                                <span class="stats-label"><?php echo _l('word_count'); ?>:</span>
                                                <span class="stats-value" id="word-count">0</span>
                                            </div>
                                            <div class="stats-item">
                                                <span class="stats-label"><?php echo _l('reading_time'); ?>:</span>
                                                <span class="stats-value" id="reading-time">< 1</span> <?php echo _l('minutes'); ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="panel-section">
                                        <h4 class="section-title"><?php echo _l('keyword_cloud'); ?></h4>
                                        <div id="keyword-cloud" class="section-content" style="height: 200px;">
                                            <div class="alert alert-info"><?php echo _l('add_content_for_keywords'); ?></div>
                                        </div>
                                    </div>
                                    
                                    <div class="panel-section">
                                        <h4 class="section-title"><?php echo _l('keyword_density'); ?></h4>
                                        <div id="keyword-density" class="section-content">
                                            <div class="alert alert-info"><?php echo _l('add_content_for_density'); ?></div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- SEO Tab -->
                                <div role="tabpanel" class="tab-pane" id="seo-tab">
                                    <div class="panel-section">
                                        <h4 class="section-title"><?php echo _l('seo_analysis'); ?></h4>
                                        <div id="seo-analysis" class="section-content">
                                            <div class="alert alert-info"><?php echo _l('add_content_for_seo'); ?></div>
                                        </div>                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <div class="row">
                    <div class="col-md-6 text-left">
                        <button type="button" class="btn btn-default save-local-btn">
                            <i class="fa fa-save"></i> <?php echo _l('save_locally'); ?>
                        </button>
                    </div>
                    <div class="col-md-6">
                        <button type="button" class="btn btn-default cancel-draft-btn" data-dismiss="modal">
                            <i class="fa fa-times"></i> <?php echo _l('cancel'); ?>
                        </button>
                        <button type="button" class="btn btn-info save-draft-btn">
                            <i class="fa fa-save"></i> <?php echo _l('save_draft'); ?>
                        </button>
                        <button type="button" class="btn btn-success publish-draft-btn">
                            <i class="fa fa-check"></i> <?php echo _l('publish'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- CSS for Draft Writer -->
<style>
/* Modal Styles */
.modal-fullscreen {
    width: 95%;
    height: 95vh;
    margin: 10px auto;
}

.modal-fullscreen .modal-content {
    height: 100%;
    display: flex;
    flex-direction: column;
}

.modal-fullscreen .modal-body {
    flex: 1;
    overflow-y: auto;
    padding-bottom: 30px;
}

/* Header Styles */
#draft-writer-modal .modal-header {
    display: flex;
    align-items: center;
    padding: 10px 15px;
    border-bottom: 2px solid #f0f0f0;
}

#draft-writer-modal .modal-title {
    margin-right: auto;
    font-weight: 600;
}

.auto-save-status {
    display: flex;
    align-items: center;
    margin-left: 15px;
    font-size: 13px;
}

.auto-save-toggle {
    display: flex;
    align-items: center;
    margin-right: 15px;
}

.toggle-label {
    margin-right: 5px;
}

.toggle-status {
    padding: 2px 8px;
    border-radius: 3px;
    font-weight: 600;
}

.toggle-status.on {
    background-color: #ebf7ed;
    color: #10B981;
}

.toggle-status.off {
    background-color: #f8f9fa;
    color: #6c757d;
}

.last-saved {
    color: #6c757d;
}

/* Editor Column Styles */
.editor-column {
    border-right: 1px solid #e9ecef;
    padding-right: 20px;
}

.title-section,
.description-section {
    margin-bottom: 20px;
}

.description-counter {
    text-align: right;
    margin-top: 5px;
}

.content-section {
    position: relative;
}

.content-toolbar {
    margin-bottom: 10px;
    display: flex;
    gap: 5px;
}

/* Analysis Column Styles */
.analysis-column {
    padding-left: 20px;
}

.analysis-panel {
    background: #fafafa;
    border-radius: 4px;
    border: 1px solid #e9ecef;
    height: 100%;
    display: flex;
    flex-direction: column;
}

.analysis-panel .nav-tabs {
    background: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
}

.analysis-panel .tab-content {
    flex: 1;
    overflow-y: auto;
    padding: 15px;
}

.panel-section {
    margin-bottom: 25px;
}

.section-title {
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 10px;
    color: #555;
    border-bottom: 1px solid #e9ecef;
    padding-bottom: 8px;
}

.section-content {
    padding: 0 5px;
}

/* Outline Styles */
.outline-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.outline-item {
    padding: 5px 0;
    cursor: pointer;
    transition: background-color 0.3s;
}

.outline-item:hover {
    background-color: #f8f9fa;
}

.outline-indicator {
    color: #6c757d;
    margin-right: 5px;
}

.outline-text {
    font-weight: 500;
}

.outline-level-1 { padding-left: 0px; font-weight: bold; }
.outline-level-2 { padding-left: 10px; }
.outline-level-3 { padding-left: 20px; }
.outline-level-4 { padding-left: 30px; }
.outline-level-5 { padding-left: 40px; }
.outline-level-6 { padding-left: 50px; }

/* Stats Styles */
.stats-item {
    margin-bottom: 10px;
    display: flex;
    justify-content: space-between;
    padding: 5px 10px;
    background: #fff;
    border-radius: 4px;
    border: 1px solid #e9ecef;
}

.stats-value {
    font-weight: 600;
}

/* Keyword Styles */
#keyword-cloud {
    background: #fff;
    border: 1px solid #e9ecef;
    border-radius: 4px;
    padding: 10px;
}

.keyword-item {
    display: flex;
    justify-content: space-between;
    padding: 5px 10px;
    margin-bottom: 5px;
    background: #fff;
    border-radius: 4px;
    border: 1px solid #e9ecef;
}

.keyword-text {
    font-weight: 500;
}

.keyword-count {
    color: #6c757d;
}

/* SEO Styles */
.seo-item {
    margin-bottom: 10px;
    padding: 10px;
    border-radius: 4px;
}

.seo-item.good {
    background-color: #ebf7ed;
    border: 1px solid #d1e7dd;
}

.seo-item.warning {
    background-color: #fff8e6;
    border: 1px solid #ffe5bc;
}

.seo-item.error {
    background-color: #ffefef;
    border: 1px solid #f5c2c7;
}

.seo-indicator {
    margin-right: 8px;
}

.seo-indicator.good { color: #10B981; }
.seo-indicator.warning { color: #F59E0B; }
.seo-indicator.error { color: #EF4444; }

/* Selection Toolbar Styles */
.selection-toolbar {
    position: absolute;
    background: #fff;
    border: 1px solid #d0d0d0;
    border-radius: 4px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    padding: 5px;
    z-index: 100;
    display: flex;
    gap: 5px;
}

/* Fact Check Styles */
.fact-check-findings {
    list-style: none;
    padding: 0;
}

.finding-item {
    display: flex;
    margin-bottom: 10px;
    padding: 10px;
    background: #f8f9fa;
    border-radius: 4px;
}

.finding-status {
    margin-right: 10px;
    font-size: 18px;
}

.finding-content {
    flex: 1;
}

.finding-text {
    font-weight: 500;
    margin-bottom: 5px;
}

.finding-explanation {
    color: #555;
    margin-bottom: 5px;
}

.finding-sources {
    font-size: 12px;
}

.sources-list {
    margin-top: 5px;
    padding-left: 20px;
}

/* Button style override for AI buttons */
.btn-ai {
    background-color: #6366F1;
    color: white;
    border: none;
}

.btn-ai:hover {
    background-color: #4F46E5;
    color: white;
}

/* Animation for auto-save */
@keyframes saving-pulse {
    0% { opacity: 1; }
    50% { opacity: 0.5; }
    100% { opacity: 1; }
}

.saving-indicator {
    animation: saving-pulse 1.5s infinite;
}
</style>

<script>
// Add libraries if needed
if (!window.jqCloudLoaded) {
    $('head').append('<link href="https://cdnjs.cloudflare.com/ajax/libs/jqcloud/1.0.4/jqcloud.min.css" rel="stylesheet">');
    $.getScript('https://cdnjs.cloudflare.com/ajax/libs/jqcloud/1.0.4/jqcloud.min.js', function() {
        window.jqCloudLoaded = true;
        console.log('jQCloud loaded');
    });
}

if (!window.chartJsLoaded) {
    $.getScript('https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.8.0/chart.min.js', function() {
        window.chartJsLoaded = true;
        console.log('Chart.js loaded');
    });
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

/**
 * Extract keywords from text
 */
function extractKeywords(text) {
    // Remove common words and count occurrences
    const commonWords = ['a', 'an', 'the', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'with', 'by', 'about', 'as', 'of', 'from'];
    const words = text.toLowerCase()
        .replace(/[^\w\s]/g, '')
        .split(/\s+/)
        .filter(word => word.length > 3 && !commonWords.includes(word));
    
    const keywords = {};
    words.forEach(word => {
        keywords[word] = (keywords[word] || 0) + 1;
    });
    
    return keywords;
}

/**
 * Debounce function to limit rapid function calls
 */
function debounce(func, wait) {
    let timeout;
    return function() {
        const context = this;
        const args = arguments;
        clearTimeout(timeout);
        timeout = setTimeout(() => {
            func.apply(context, args);
        }, wait);
    };
}

// Additional utility functions will be added as needed
</script>