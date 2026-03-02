<?php defined('BASEPATH') or exit('No direct script access allowed');   ?>
<script>
/**
 * Display Topic Composer Result
 */
function displayTopicComposerResult(data, workflowData) {
    var timestamp = moment().format('DD/MM/YYYY HH:mm:ss');
    var countdownInterval;
    var pollInterval;
    var maxPollingTime = 300; // 5 minutes
    var pollingStartTime;
    var $resultContainer;

    // Global state management
    window.TopicComposer = {
        items: [],
        originalItems: [],
        currentEditingIndex: -1,
        hasChanges: false,
        editors: new Map(),
        submissionData: {
            items: data.items || [],
            deletedItems: [],
            deletedOrders: new Set() // Thêm Set để track các order đã bị xóa
        }
    };
    initTopicComposerHandlers();
    // Lưu workflowData gốc vào biến global
    window.TopicComposer.handlers.showPromptSelectionModal = showPromptSelectionModal;
    window.currentWorkflowData = workflowData;

    <?php 
    ob_start();
    $this->load->view('includes/displayTopicComposerResult/topic_detail_action_buttons_display_script_displayTopicComposerResult_showPromptSelectionModal');
    $topic_detail_action_buttons_display_script_displayTopicComposerResult_showPromptSelectionModal = ob_get_clean();
    // Loại bỏ thẻ <script> và </script>
    $topic_detail_action_buttons_display_script_displayTopicComposerResult_showPromptSelectionModal = str_replace(['<script>', '</script>'], '', $topic_detail_action_buttons_display_script_displayTopicComposerResult_showPromptSelectionModal);
    echo $topic_detail_action_buttons_display_script_displayTopicComposerResult_showPromptSelectionModal;
    ?>  

    <?php 
    ob_start();
    $this->load->view('includes/displayTopicComposerResult/topic_detail_action_buttons_display_script_displayTopicComposerResult_showSearchResultsModal');
    $topic_detail_action_buttons_display_script_displayTopicComposerResult_showSearchResultsModal = ob_get_clean();
    // Loại bỏ thẻ <script> và </script>
    $topic_detail_action_buttons_display_script_displayTopicComposerResult_showSearchResultsModal = str_replace(['<script>', '</script>'], '', $topic_detail_action_buttons_display_script_displayTopicComposerResult_showSearchResultsModal);
    echo $topic_detail_action_buttons_display_script_displayTopicComposerResult_showSearchResultsModal;
    ?>  

    // Helper functions - định nghĩa trước khi sử dụng
    function showError(message) {
        if ($resultContainer) {
            clearPolling();
            $resultContainer.find('.execution-status')
                .html(`<i class="fa fa-times text-danger"></i> ${message}`);
        }
    }

    function clearPolling() {
        if (countdownInterval) clearInterval(countdownInterval);
        if (pollInterval) clearInterval(pollInterval);
        if ($resultContainer) {
            $resultContainer.find('.poll-info').remove();
        }
    }

    function startPolling(workflowId, executionId, workflowData) {
        var remainingTime = maxPollingTime;
        
        function updateCountdown() {
            remainingTime--;
            if (remainingTime <= 0) {
                clearPolling();
                showError('<?php echo _l('polling_timeout'); ?>');
                return;
            }
            
            var minutes = Math.floor(remainingTime / 60);
            var seconds = remainingTime % 60;
            $('.countdown').text(
                (minutes < 10 ? '0' : '') + minutes + ':' + 
                (seconds < 10 ? '0' : '') + seconds
            );
        }

        function poll() {
            console.log('poll');
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
                        if (response.success) {
                            if (response.data.status === 'completed') {
                                clearPolling();
                                showComposerModal(response.data);
                            }
                        } else {
                            showError(response.message);
                        }
                    } catch (e) {
                        console.error('Error parsing poll response:', e);
                        showError('<?php echo _l('error_processing_response'); ?>');
                    }
                },
                error: function() {
                    showError('<?php echo _l('polling_error'); ?>');
                }
            });
        }

        // Start countdown timer
        countdownInterval = setInterval(updateCountdown, 1000);
        // Start polling
        pollInterval = setInterval(poll, 5000);
        poll(); // Poll immediately
    }

    // Thêm hàm scroll helper vào đầu file
    function scrollToComposerElement($element, offset = 0) {
        if (window.innerWidth <= 767) { // Mobile only
            const modalBody = $('#topic-composer-modal .modal-body');
            const elementTop = $element.offset().top - modalBody.offset().top + modalBody.scrollTop();
            modalBody.animate({
                scrollTop: elementTop - offset
            }, 500);
        }
    }

    function showComposerModal(composerData) {
        // Parse response data if needed
        console.log('showComposerModal', composerData);
        window.TopicComposer.items = Array.isArray(composerData) ? composerData : [composerData];
        // Thay thế bằng deep copy
        window.TopicComposer.originalItems = JSON.parse(JSON.stringify(
            Array.isArray(composerData) ? composerData : [composerData]
        ));

        // Khởi tạo cache ảnh đã download
        if (!window.downloadedImages) {
            window.downloadedImages = new Set();
            
            // Tìm tất cả ảnh đã tải trong danh sách items hiện tại
            window.TopicComposer.items.forEach(item => {
                try {
                    if (item.item_Pictures) {
                        const pictures = JSON.parse(item.item_Pictures);
                        pictures.forEach(pic => {
                            if (pic['item_Pictures-src'] && isImageDownloaded(pic['item_Pictures-src'])) {
                                window.downloadedImages.add(pic['item_Pictures-src']);
                            }

                            if (pic['item_Pictures-src']) {
                                isImageDownloaded(pic['item_Pictures-src']).then(result => {
                                    if (result.exists) {
                                        window.downloadedImages.add(pic['item_Pictures-src']);
                                        if (!window.downloadedImagesMap) {
                                            window.downloadedImagesMap = new Map();
                                        }
                                        window.downloadedImagesMap.set(pic['item_Pictures-src'], result.downloadedUrl);
                                    }
                                });
                            }
                        });
                    }
                } catch (e) {
                    console.error('Error parsing item pictures:', e);
                }
            });
        }

        // Chỉ lấy topic và summary từ item đầu tiên
        const firstItem = window.TopicComposer.items[0] || {};
        const topic = firstItem.Topic || '';
        const summary = firstItem.Summary || '';
        

        var modalHtml = `
            <div class="modal fade" id="topic-composer-modal" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
                <div class="modal-dialog modal-fullscreen">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h4 class="modal-title">
                                <?php echo _l('topic_composer'); ?> 
                                <small class="text-muted">(${window.TopicComposer.items.length} <?php echo _l('items'); ?>)</small>
                            </h4>
                        </div>
                        <div class="modal-body">
                            <!-- Topic Config Section -->
                            <div class="topic-config-section">
                                <h4>
                                    <i class="fa fa-cog"></i> <?php echo _l('topic_config'); ?>
                                </h4>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="topic-name"><?php echo _l('topic'); ?></label>
                                            <div class="input-group">
                                                <input type="text" 
                                                       class="form-control" 
                                                       id="topic-name" 
                                                       name="topic-name"
                                                       value="${topic}"
                                                       onchange="window.TopicComposer.handlers.markAsChanged()">
                                                <span class="input-group-btn">
                                                    <button type="button" 
                                                            class="btn btn-info quick-save-btn"
                                                            onclick="window.TopicComposer.handlers.quickSaveConfig('topic')"
                                                            data-loading-text="<i class='fa fa-spinner fa-spin'></i>">
                                                        <i class="fa fa-save"></i>
                                                    </button>
                                                </span>
                                            </div>
                                            <div class="invalid-feedback">
                                                <?php echo _l('topic_required'); ?>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Summary field - Full width -->
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label for="topic-summary"><?php echo _l('summary'); ?></label>
                                            <div class="input-group">
                                                <div class="summary-wrapper w-100">
                                                    <div id="topic-summary" 
                                                         class="form-control summary-editor" 
                                                         contenteditable="true"
                                                         onblur="window.TopicComposer.handlers.markAsChanged()">${summary}</div>
                                                    <div class="summary-actions">
                                                        <button type="button" 
                                                                class="btn btn-info quick-save-btn summary-save-btn"
                                                                onclick="window.TopicComposer.handlers.quickSaveConfig('summary')"
                                                                data-loading-text="<i class='fa fa-spinner fa-spin'></i>">
                                                            <i class="fa fa-save"></i>
                                                        </button>
                                                        <button type="button"
                                                                class="btn btn-default ai-edit-btn">
                                                            <i class="fa fa-magic"></i>
                                                        </button>
                                                    </div>
                                                    <div class="save-feedback"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Controller Selection Section -->
                            <div class="controller-selection-section panel panel-default">
                                <div class="panel-heading">
                                    <h4 class="panel-title">
                                        <a data-toggle="collapse" href="#controller-selection-collapse" aria-expanded="false" class="collapsed">
                                            <i class="fa fa-cog"></i> <?php echo _l('controller_selection'); ?>
                                            <span class="pull-right">
                                                <i class="fa fa-chevron-down"></i>
                                            </span>
                                        </a>
                                    </h4>
                                </div>
                                <div id="controller-selection-collapse" class="panel-collapse collapse">
                                    <div class="panel-body">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label for="topic-composer-controller-select"><?php echo _l('select_controller'); ?></label>
                                                    <div class="input-group">
                                                        <select class="form-control" id="topic-composer-controller-select">
                                                            <option value=""><?php echo _l('select_controller'); ?></option>
                                                        </select> 
                                                        <span class="input-group-btn">
                                                            <button type="button" class="btn btn-info" onclick="window.TopicComposer.handlers.loadTopicComposerControllers()">
                                                                <i class="fa fa-refresh"></i>
                                                            </button>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div id="topic-composer-controller-info" class="mtop10" style="display:none;">
                                                    <!-- Controller info will be displayed here -->
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Items Section -->
                            <div class="row">
                                <!-- Left Panel: Items List -->
                                <div class="col-md-4">
                                    <div class="panel panel-default">
                                        <div class="panel-heading">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <h3 class="panel-title"><?php echo _l('items_list'); ?></h3>
                                                <div class="btn-group quick-action-buttons">
                                                    <button type="button" class="btn btn-info btn-sm batch-generate-titles-btn" title="<?php echo _l('batch_generate_titles'); ?>">
                                                        <i class="fa fa-magic"></i> <?php echo _l('batch_generate_titles'); ?>
                                                    </button>
                                                    <button type="button" class="btn btn-success btn-sm bulk-edit-content-btn" title="<?php echo _l('bulk_edit_content'); ?>">
                                                        <i class="fa fa-list-alt"></i> <?php echo _l('bulk_edit_content'); ?>
                                                    </button>
                                                    <button type="button" class="btn btn-primary btn-sm auto-reposition-btn" title="<?php echo _l('auto_reposition'); ?>">
                                                        <i class="fa fa-sort-numeric-asc"></i> <?php echo _l('auto_reposition'); ?>
                                                    </button>
                                                    <button type="button" class="btn btn-warning btn-sm quick-select-empty-btn" title="<?php echo _l('select_empty_items'); ?>">
                                                        <i class="fa fa-filter"></i> <?php echo _l('select_empty'); ?>
                                                    </button>
                                                    <button type="button" class="btn btn-info btn-sm bulk-download-images-btn" title="<?php echo _l('bulk_download_images'); ?>">
                                                        <i class="fa fa-download"></i> <?php echo _l('bulk_download_images'); ?>
                                                    </button>
                                                    <button type="button" class="btn btn-danger btn-sm delete-selected-btn" style="display:none">
                                                        <i class="fa fa-trash"></i> <?php echo _l('delete_selected'); ?> 
                                                        <span class="selected-count"></span>
                                                    </button>
                                                    <button type="button" class="btn btn-primary btn-sm add-item-btn">
                                                        <i class="fa fa-plus"></i> <?php echo _l('add_item'); ?>
                                                    </button>
                                                </div>
                                                <div class="batch-actions-status">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="panel-body items-list">
                                            <div class="list-group sortable-items">
                                                ${window.TopicComposer.items.map((item, index) => `
                                                    <div class="list-group-item" data-index="${index}">
                                                        <div class="item-header d-flex justify-content-between align-items-center">
                                                            <div class="d-flex align-items-center">
                                                                <div class="checkbox checkbox-primary" style="margin: 0 10px 0 0">
                                                                    <input type="checkbox" class="item-checkbox" id="item-${index}" data-index="${index}">
                                                                    <label for="item-${index}"></label>
                                                                </div>
                                                                <span class="drag-handle"><i class="fa fa-bars"></i></span>
                                                                <span class="item-position">${item.Item_Position}</span>
                                                                <div class="item-title">
                                                                    ${htmlEntityDecode(item.Item_Title)}
                                                                </div>
                                                            </div>
                                                            <div class="item-actions">
                                                                <button type="button" class="btn btn-xs btn-default edit-item-btn" data-index="${index}">
                                                                    <i class="fa fa-pencil"></i>
                                                                </button>
                                                                <button type="button" class="btn btn-xs btn-danger delete-item-btn" data-index="${index}">
                                                                    <i class="fa fa-trash"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                `).join('')}
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Right Panel: Editor -->
                                <div class="col-md-8">
                                    <div class="panel panel-info">
                                        <div class="panel-heading">
                                            <h3 class="panel-title"><?php echo _l('editor'); ?></h3>
                                        </div>
                                        <div class="panel-body">
                                            <div id="item-editor">
                                                <div class="text-center text-muted">
                                                    <i class="fa fa-arrow-left"></i> 
                                                    <?php echo _l('select_item_to_edit'); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default close-btn">
                                <i class="fa fa-times"></i> <?php echo _l('close'); ?>
                            </button>
                            <button type="button" class="btn btn-info save-all-btn">
                                <i class="fa fa-check"></i> <?php echo _l('save_all'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Remove existing modal if any
        $('#topic-composer-modal').remove();
        
        // Append and show modal
        $('body').append(modalHtml);
        
        // Setup event handlers before showing modal
        setupModalEventHandlers();
        window.TopicComposer.handlers.generateBulkTitlesModal();
        window.TopicComposer.handlers.generateBulkContentModal();
        window.TopicComposer.handlers.initAIHandlers();
        window.TopicComposer.handlers.initControllerHandlers();
        
        // Show modal
        $('#topic-composer-modal').modal('show');

        // Bind scroll behavior cho edit và save
        $(document).on('click', '.edit-item-btn', function() {
            const $editor = $('#item-editor');
            if ($editor.length) {
                setTimeout(() => {
                    scrollToComposerElement($editor, 60);
                }, 100);
            }
        });

        // Show modal
        $('#topic-composer-modal').modal('show');
        
        // Call refreshItemsList immediately to update image counts when modal first opens
         // Do this:
        refreshItemsList().catch(err => console.error('Error refreshing items list:', err));

        // Bind scroll behavior cho edit và save
        $(document).on('click', '.edit-item-btn', function() {
            const $editor = $('#item-editor');
            if ($editor.length) {
                setTimeout(() => {
                    scrollToComposerElement($editor, 60);
                }, 100);
            }
        });

        // Add mobile styles
        const mobileStyleHtml = `
            <style data-id="composer-mobile-styles">
                @keyframes saveHighlight {
                    0% { background-color: #d4edda; }
                    100% { background-color: transparent; }
                }

                .save-highlight {
                    animation: saveHighlight 2s ease-out;
                }

                @media (max-width: 767px) {
                    #topic-composer-modal .modal-body {
                        scroll-behavior: smooth;
                    }
                    
                    .list-group-item {
                        padding: 12px 15px;
                    }
                    
                    .list-group-item:active {
                        background-color: #f8f9fa;
                    }
                    
                    .editor-actions {
                        position: sticky;
                        top: 0;
                        background: #fff;
                        padding: 10px 0;
                        margin: -15px -15px 15px;
                        border-bottom: 1px solid #dee2e6;
                        z-index: 100;
                    }
                }
            </style>
        `;

        // Add styles once
        if (!$('head').find('style[data-id="composer-mobile-styles"]').length) {
            $('head').append(mobileStyleHtml);
        }

        // Add config validation to existing save handler
        const originalSaveHandler = window.TopicComposer.handlers.saveChanges;
        window.TopicComposer.handlers.saveChanges = function() {
            // Validate config fields
            const title = $('#topic-title').val();
            const topic = $('#topic-name').val();
            
            let isValid = true;
            
            if (!title) {
                $('#topic-title').addClass('is-invalid');
                isValid = false;
            } else {
                $('#topic-title').removeClass('is-invalid');
            }
            
            if (!topic) {
                $('#topic-name').addClass('is-invalid');
                isValid = false;
            } else {
                $('#topic-name').removeClass('is-invalid');
            }

            if (!isValid) {
                alert_float('warning', '<?php echo _l('please_fill_required_fields'); ?>');
                return false;
            }

            // Update composerData with config values
            composerData.Title = title;
            composerData.Topic = topic;

            // Call original save handler
            return originalSaveHandler.apply(this, arguments);
        };
    }

    function setupModalEventHandlers() {
        const $modal = $('#topic-composer-modal');

        // Edit item button click
        $modal.on('click', '.edit-item-btn', function() {
            const index = $(this).data('index');
            window.TopicComposer.currentEditingIndex = index;
            loadItemEditor(index);
        });

        // Save all button click
        $modal.on('click', '.save-all-btn', function() {
            saveAllChanges();
        });

        // Initialize sortable if library exists
        if (typeof Sortable !== 'undefined') {
            new Sortable($modal.find('.sortable-items')[0], {
                handle: '.drag-handle',
                animation: 150,
                onEnd: function() {
                    updateItemsOrder();
                }
            });
        } else {
            console.warn('Sortable library not loaded');
            // Hide drag handles if Sortable is not available
            $modal.find('.drag-handle').hide();
        }

        // Clean up events when modal closes
        $modal.on('hidden.bs.modal', function() {
            $modal.off('click', '.edit-item-btn');
            $modal.off('click', '.save-all-btn');
            cleanupEditors();
        });

        // Thêm biến cờ để tránh hiển thị confirm hai lần
        let isClosing = false;

        // Thêm xử lý nút đóng
        $modal.on('click', '.close-btn', function() {
            if (hasUnsavedChanges() && !isClosing) {
                isClosing = true;
                if (confirm('<?php echo _l('confirm_close'); ?>')) {
                    window.TopicComposer.hasChanges = false;
                    $modal.modal('hide');
                } else {
                    isClosing = false;
                }
            } else {
                $modal.modal('hide');
            }
        });

        // Thêm xử lý khi nhấn ESC hoặc click outside
        $modal.on('hide.bs.modal', function(e) {
            if (hasUnsavedChanges() && !isClosing) {
                isClosing = true;
                if (!confirm('<?php echo _l('confirm_close'); ?>')) {
                    e.preventDefault();
                    isClosing = false;
                } else {
                    window.TopicComposer.hasChanges = false;
                }
            } else if (isClosing) {
                // Reset biến cờ nếu đóng modal đã được xác nhận từ nút close
                isClosing = false;
            }
        });

        // Handle checkbox changes
        $modal.on('change', '.item-checkbox', function() {
            const selectedCount = $modal.find('.item-checkbox:checked').length;
            const $deleteSelectedBtn = $modal.find('.delete-selected-btn');
            
            if (selectedCount > 0) {
                $deleteSelectedBtn.show()
                    .find('.selected-count')
                    .text(`(${selectedCount} ${selectedCount === 1 ? '<?php echo _l("item"); ?>' : '<?php echo _l("items"); ?>'})`);
            } else {
                $deleteSelectedBtn.hide();
            }
        });

        // Delete selected items
        $modal.on('click', '.delete-selected-btn', function() {
            const selectedIndexes = [];
            $modal.find('.item-checkbox:checked').each(function() {
                selectedIndexes.push($(this).data('index'));
            });

            if (selectedIndexes.length > 0) {
                if (confirm('<?php echo _l('confirm_delete_selected_items'); ?>')) {
                    // Sort indexes in descending order to avoid index shifting issues
                    selectedIndexes.sort((a, b) => b - a);
                    
                    // Store current positions before deletion
                    const currentPositions = window.TopicComposer.items.map(item => ({
                        Item_Position: item.Item_Position,
                        row_number: item.row_number
                    }));
                    
                    // Remove items
                    selectedIndexes.forEach(index => {
                        window.TopicComposer.items.splice(index, 1);
                    });

                    // Reassign positions while maintaining relative order
                    window.TopicComposer.items.forEach((item, newIndex) => {
                        // Find original position in currentPositions
                        const originalPosition = currentPositions.find(pos => pos.row_number === item.row_number);
                        if (originalPosition) {
                            // Keep relative position pattern but update number
                            item.Item_Position = originalPosition.Item_Position.replace(
                                /\d+/,
                                newIndex + 1
                            );
                        } else {
                            item.Item_Position = `Top ${newIndex + 1}`;
                        }
                    });

                    // Clear editor if current item was deleted
                    if (selectedIndexes.includes(window.TopicComposer.currentEditingIndex)) {
                        $('#item-editor').html(`
                            <div class="text-center text-muted">
                                <i class="fa fa-arrow-left"></i> 
                                <?php echo _l('select_item_to_edit'); ?>
                            </div>
                        `);
                        window.TopicComposer.currentEditingIndex = -1;
                    }

                    window.TopicComposer.hasChanges = true;
                    refreshItemsList();
                    alert_float('success', '<?php echo _l('items_deleted'); ?>');
                }
            }
        });

        // Delete single item
        $modal.on('click', '.delete-item-btn', function() {
            const index = $(this).data('index');
            if (confirm('<?php echo _l('confirm_delete_item'); ?>')) {
                // Store current positions before deletion
                const currentPositions = window.TopicComposer.items.map(item => ({
                    Item_Position: item.Item_Position,
                    row_number: item.row_number
                }));
                
                // Remove item
                window.TopicComposer.items.splice(index, 1);
                
                // Reassign positions while maintaining relative order
                window.TopicComposer.items.forEach((item, newIndex) => {
                    // Find original position in currentPositions
                    const originalPosition = currentPositions.find(pos => pos.row_number === item.row_number);
                    if (originalPosition) {
                        // Keep relative position pattern but update number
                        item.Item_Position = originalPosition.Item_Position.replace(
                            /\d+/,
                            newIndex + 1
                        );
                    } else {
                        item.Item_Position = `Top ${newIndex + 1}`;
                    }
                });

                // Clear editor if deleted item was being edited
                if (window.TopicComposer.currentEditingIndex === index) {
                    $('#item-editor').html(`
                        <div class="text-center text-muted">
                            <i class="fa fa-arrow-left"></i> 
                            <?php echo _l('select_item_to_edit'); ?>
                        </div>
                    `);
                    window.TopicComposer.currentEditingIndex = -1;
                }

                window.TopicComposer.hasChanges = true;
                refreshItemsList();
                alert_float('success', '<?php echo _l('item_deleted'); ?>');
            }
        });

        // Add handlers for new buttons
        setupItemActions();

        // Add handler for Quick Select Empty Items button
        $modal.on('click', '.quick-select-empty-btn', function() {
            // Find all empty items
            const emptyItems = window.TopicComposer.items.filter((item, index) => {
                return (!item.Item_Position || item.Item_Position.trim() === '') &&
                       (!item.Item_Title || item.Item_Title.trim() === '') &&
                       (!item.Item_Content || item.Item_Content.trim() === '');
            });

            // Select checkboxes for empty items
            $('.item-checkbox').prop('checked', false); // Uncheck all first
            emptyItems.forEach(item => {
                const index = window.TopicComposer.items.indexOf(item);
                $(`.item-checkbox[data-index="${index}"]`).prop('checked', true);
            });

            // Update delete button visibility and count
            const selectedCount = emptyItems.length;
            const $deleteSelectedBtn = $modal.find('.delete-selected-btn');
            
            if (selectedCount > 0) {
                $deleteSelectedBtn.show()
                    .find('.selected-count')
                    .text(`(${selectedCount} ${selectedCount === 1 ? '<?php echo _l("item"); ?>' : '<?php echo _l("items"); ?>'})`);
                
                // Show notification
                alert_float('info', `<?php echo _l('found_empty_items'); ?>: ${selectedCount}`);
            } else {
                $deleteSelectedBtn.hide();
                alert_float('warning', '<?php echo _l('no_empty_items_found'); ?>');
            }
        });

        // Handle Bulk Download Images button click
        $modal.on('click', '.bulk-download-images-btn', async function() {
            // Disable button while processing
            const $btn = $(this);
            $btn.prop('disabled', true)
                .html('<i class="fa fa-spinner fa-spin"></i> <?php echo _l("processing"); ?>');

            try {
                // Sử dụng hàm mới để lấy tất cả ảnh từ tất cả các items
                const allImages = [];
                window.TopicComposer.items.forEach(item => {
                    const itemImages = extractImagesFromContent(item);
                    console.log("extractImagesFromContent 752", itemImages);
                    if (itemImages.length > 0) {
                        allImages.push(...itemImages);
                    }
                });
                
                console.log("allImages 758", allImages);
                if (allImages.length === 0) {
                    $btn.prop('disabled', false)
                        .html('<i class="fa fa-download"></i> <?php echo _l("bulk_download_images"); ?>');
                    alert_float('info', '<?php echo _l("no_images_found"); ?>');
                    return;
                }

                // Lọc ảnh chưa download - dùng phiên bản bất đồng bộ để kiểm tra API
                const imageDownloadStatuses = await Promise.all(
                    allImages.map(async url => ({
                        url,
                        isDownloaded: await isImageDownloaded(url)
                    }))
                );
                console.log("imageDownloadStatuses 773", imageDownloadStatuses);
                const uniqueImageSrcs = [...new Set(
                    imageDownloadStatuses
                        .filter(item => item.isDownloaded.exists === false)
                        .map(item => item.url)
                )];
                console.log("uniqueImageSrcs 776", uniqueImageSrcs);
                if (uniqueImageSrcs.length === 0) {
                    $btn.prop('disabled', true)
                        .html('<i class="fa fa-download"></i> <?php echo _l("bulk_download_images_downloaded"); ?>');
                    alert_float('info', '<?php echo _l("all_images_already_downloaded"); ?>');
                    return;
                }

                alert_float('info', `<?php echo _l("found_images_to_download"); ?>: ${uniqueImageSrcs.length}`);

                // Create progress UI
                const progressHtml = `
                    <div class="bulk-download-progress">
                        <h4><?php echo _l("downloading_images"); ?> (0/${uniqueImageSrcs.length})</h4>
                        <div class="progress">
                            <div class="progress-bar progress-bar-info" style="width: 0%"></div>
                        </div>
                        <div class="progress-stats">
                            <span class="text-success downloaded-count">0</span> <?php echo _l("images_downloaded"); ?> / 
                            <span class="text-danger failed-count">0</span> <?php echo _l("images_failed"); ?>
                        </div>
                        <div class="progress-actions mt-2">
                            <button type="button" class="btn btn-danger btn-sm cancel-download-btn">
                                <i class="fa fa-times"></i> <?php echo _l("cancel_download"); ?>
                            </button>
                        </div>
                    </div>
                `;
                
                $('#topic-composer-modal .modal-body .batch-actions-status').append(progressHtml);

                // Download images sequentially
                let downloadedCount = 0;
                let failedCount = 0;
                let currentIndex = 0;
                let isCancelled = false;

                // Add cancel event handler
                $('.cancel-download-btn').on('click', function() {
                    isCancelled = true;
                    $(this).prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> <?php echo _l("cancelling"); ?>');
                    alert_float('warning', '<?php echo _l("download_cancelling"); ?>');
                });

                // Create a recursive async function for downloading
                async function downloadNext() {
                    if (isCancelled) {
                        // Download cancelled
                        $btn.prop('disabled', false)
                            .html('<i class="fa fa-download"></i> <?php echo _l("bulk_download_images"); ?>');
                        
                        $('.bulk-download-progress h4').text(`<?php echo _l("download_cancelled"); ?> (${currentIndex}/${uniqueImageSrcs.length})`);
                        $('.cancel-download-btn').remove();
                        
                        setTimeout(() => {
                            alert_float('warning', '<?php echo _l("download_cancelled_message"); ?>');
                            // Don't remove progress UI to show final stats
                            refreshItemsList();
                        }, 1000);
                        return;
                    }

                    if (currentIndex >= uniqueImageSrcs.length) {
                        // All done
                        $btn.prop('disabled', false)
                            .html('<i class="fa fa-download"></i> <?php echo _l("bulk_download_images"); ?>');
                        
                        // Remove cancel button when complete
                        $('.cancel-download-btn').remove();
                        
                        setTimeout(() => {
                            alert_float('success', '<?php echo _l("images_downloading_completed"); ?>');
                            // Refresh items list to update undownloaded images badges
                            refreshItemsList();
                        }, 2000);
                        return;
                    }

                    const imgSrc = uniqueImageSrcs[currentIndex];
                    currentIndex++;

                    // Update progress
                    const progress = Math.round((currentIndex / uniqueImageSrcs.length) * 100);
                    $('.bulk-download-progress .progress-bar').css('width', progress + '%');
                    $('.bulk-download-progress h4').text(`<?php echo _l("downloading_images"); ?> (${currentIndex}/${uniqueImageSrcs.length})`);

                    try {
                        // Use the existing downloadImageToServer but with a promise wrapper
                        const success = await new Promise(resolve => {
                            window.TopicComposer.handlers.downloadImageToServer(imgSrc, null, function(success) {
                                resolve(success);
                            });
                        });

                        if (success) {
                            downloadedCount++;
                            $('.bulk-download-progress .downloaded-count').text(downloadedCount);
                            // Thêm URL vào cache để không tải lại
                            if (!window.downloadedImages) {
                                window.downloadedImages = new Set();
                            }
                            window.downloadedImages.add(imgSrc);
                        } else {
                            failedCount++;
                            $('.bulk-download-progress .failed-count').text(failedCount);
                        }
                        
                        // Small delay to avoid overwhelming the server
                        await new Promise(resolve => setTimeout(resolve, 100));
                        
                        // Process next image
                        await downloadNext();
                    } catch (error) {
                        console.error('Error downloading image:', error);
                        failedCount++;
                        $('.bulk-download-progress .failed-count').text(failedCount);
                        
                        // Continue with next image despite errors
                        await downloadNext();
                    }
                }

                // Start downloading
                await downloadNext();
            } catch (error) {
                console.error('Error in bulk download process:', error);
                $btn.prop('disabled', false)
                    .html('<i class="fa fa-download"></i> <?php echo _l("bulk_download_images"); ?>');
                alert_float('danger', '<?php echo _l("error_processing_downloads"); ?>');
            }
        });
    }

    /**
     * Setup item actions
     */
    function setupItemActions() {
        const $modal = $('#topic-composer-modal');

        // Add new item
        $modal.on('click', '.add-item-btn', function() {
            const newIndex = window.TopicComposer.items.length;
            const newItem = {
                Item_Position: `Top ${newIndex + 1}`,
                Item_Title: `<?php echo _l('new_item'); ?> ${newIndex + 1}`,
                Item_Content: '',
                row_number: Date.now(), // Unique identifier
                'web-scraper-order': generateOrderId(),
                'web-scraper-start-url': window.TopicComposer.items[0]['web-scraper-start-url'],
                'Topic-href': window.TopicComposer.items[0]['Topic-href'],
                'item_Pictures': '',
                'item_Pictures_Full': '',
                'Topic_footer': '',
                'TopicKeywords': ''
            };

            window.TopicComposer.items.push(newItem);
            refreshItemsList();
            loadItemEditor(newIndex);
        });

        // Delete item
        $modal.on('click', '.delete-item-btn', function() {
            const index = $(this).data('index');
            if (confirm('<?php echo _l('confirm_delete_item'); ?>')) {
                window.TopicComposer.items.splice(index, 1);
                // Update positions
                window.TopicComposer.items.forEach((item, i) => {
                    item.Item_Position = `Top ${i + 1}`;
                });
                refreshItemsList();
                // Clear editor if deleted item was being edited
                if (window.TopicComposer.currentEditingIndex === index) {
                    $('#item-editor').html(`
                        <div class="text-center text-muted">
                            <i class="fa fa-arrow-left"></i> 
                            <?php echo _l('select_item_to_edit'); ?>
                        </div>
                    `);
                    window.TopicComposer.currentEditingIndex = -1;
                }
            }
        });
    }

    /**
     * Refresh items list
     */
    async function refreshItemsList() {
        console.log('refreshItemsList');
        const $itemsList = $('.sortable-items');
        
        // Create an array of promises for each item's HTML
        const itemHTMLPromises = window.TopicComposer.items.map(async (item, index) => {
            // Check if item has changed
            const originalItem = window.TopicComposer.originalItems.find(
                orig => orig['web-scraper-order'] === item['web-scraper-order']
            );
            const changes = getItemChanges(item, originalItem);
            
            // Get all images from item
            const allImages = extractImagesFromContent(item);
            
            // Count downloaded and undownloaded images
            let downloadedImagesCount = 0;
            let undownloadedImagesCount = 0;
            
            if (allImages.length > 0) {
                // Use Promise.all to check all images concurrently
                const imageResults = await Promise.all(allImages.map(imgUrl => isImageDownloaded(imgUrl)));
                
                imageResults.forEach(result => {
                    if (result.exists) {
                        downloadedImagesCount++;
                    } else {
                        undownloadedImagesCount++;
                    }
                });
            }
            
            const totalImagesCount = allImages.length;
            
            return `
                <div class="list-group-item ${changes ? 'has-changes' : ''}" 
                     data-index="${index}"
                     ${changes ? `data-changes="${changes.join(', ')}"` : ''}>
                    <div class="item-header d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <div class="checkbox checkbox-primary" style="margin: 0 10px 0 0">
                                <input type="checkbox" class="item-checkbox" id="item-${index}" data-index="${index}">
                                <label for="item-${index}"></label>
                            </div>
                            <span class="drag-handle"><i class="fa fa-bars"></i></span>
                            <span class="item-position">${item.Item_Position}</span>
                            <div class="item-title">
                                ${htmlEntityDecode(item.Item_Title)}
                                ${changes ? `
                                    <span class="changes-indicator" title="<?php echo _l('modified_fields'); ?>: ${changes.join(', ')}">
                                        <i class="fa fa-check-circle text-success"></i>
                                    </span>
                                ` : ''}
                                ${totalImagesCount > 0 ? `
                                    <span class="images-status-badge" 
                                          title="<?php echo _l('images_status'); ?>: ${downloadedImagesCount}/${totalImagesCount} <?php echo _l('downloaded'); ?>">
                                        <i class="fa fa-image ${downloadedImagesCount === totalImagesCount ? 'text-success' : undownloadedImagesCount > 0 ? 'text-warning' : 'text-muted'}"></i>
                                        <span class="count">${downloadedImagesCount}/${totalImagesCount}</span>
                                    </span>
                                ` : ''}
                            </div>
                        </div>
                        <div class="item-actions">
                            <button type="button" class="btn btn-xs btn-default edit-item-btn" data-index="${index}">
                                <i class="fa fa-pencil"></i>
                            </button>
                            <button type="button" class="btn btn-xs btn-danger delete-item-btn" data-index="${index}">
                                <i class="fa fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    ${changes ? `
                        <div class="changes-tooltip">
                            <div class="changes-details">
                                <strong><?php echo _l('modified_fields'); ?>:</strong>
                                ${changes.map(field => `
                                    <span class="change-field">${field}</span>
                                `).join(', ')}
                            </div>
                        </div>
                    ` : ''}
                </div>
            `;
        });
        
        // Wait for all item HTML to be generated
        const itemsHTML = await Promise.all(itemHTMLPromises);
        $itemsList.html(itemsHTML.join(''));

        // Thêm styles cho đánh dấu và tooltip
        const styleHtml = `
            <style>
                .has-changes {
                    border-left: 4px solid #28a745;
                }

                .changes-indicator {
                    margin-left: 5px;
                }
                
                .images-status-badge {
                    display: inline-flex;
                    align-items: center;
                    margin-left: 5px;
                    background-color: #f8f9fa;
                    border-radius: 3px;
                    padding: 2px 5px;
                    font-size: 11px;
                    border: 1px solid #dee2e6;
                }
                
                .images-status-badge .count {
                    margin-left: 3px;
                    font-weight: bold;
                }
                
                .images-status-badge .fa-image.text-success {
                    color: #28a745;
                }
                
                .images-status-badge .fa-image.text-warning {
                    color: #ffc107;
                }
                
                .images-status-badge .fa-image.text-muted {
                    color: #6c757d;
                }

                .undownloaded-images-badge {
                    display: inline-flex;
                    align-items: center;
                    margin-left: 5px;
                    background-color: #fff3cd;
                    border-radius: 3px;
                    padding: 2px 5px;
                    font-size: 11px;
                }
                
                .undownloaded-images-badge .count {
                    margin-left: 3px;
                    font-weight: bold;
                }

                .changes-tooltip {
                    position: absolute;
                    background-color: #f9f9f9;
                    border: 1px solid #dee2e6;
                    padding: 5px;
                    border-radius: 4px;
                    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                    z-index: 1000;
                    opacity: 0;
                    transition: opacity 0.3s ease-in-out;
                    pointer-events: none;
                }

                .list-group-item:hover .changes-tooltip {
                    opacity: 1;
                }

                .changes-details {
                    font-size: 12px;
                }

                .change-field {
                    background-color: #e9ecef;
                    border-radius: 4px;
                    padding: 2px 5px;
                    margin-right: 5px;
                }
            </style>
        `;

        // Add styles once
        if (!$('head').find('style[data-id="item-changes-styles"]').length) {
            $('head').append($(styleHtml).attr('data-id', 'item-changes-styles'));
        }

        // Reinitialize sortable
        if (typeof Sortable !== 'undefined') {
            new Sortable($itemsList[0], {
                handle: '.drag-handle',
                animation: 150,
                onEnd: function() {
                    updateItemsOrder();
                }
            });
        }
    }

    /**
     * Load item editor
     * @param {number} index - Index of the item to edit
     */
    function loadItemEditor(index) {
        // Check if there are unsaved changes on current item
        if (window.TopicComposer.currentEditingIndex !== -1 && hasUnsavedChanges()) {
            if (!confirm('<?php echo _l('confirm_switch_item'); ?>')) {
                // Keep current item selected in the list
                $('.edit-item-btn').removeClass('active');
                $(`.edit-item-btn[data-index="${window.TopicComposer.currentEditingIndex}"]`).addClass('active');
                return;
            }
        }

        const item = window.TopicComposer.items[index];
        const $editor = $('#item-editor');

        // Store initial values for change detection
        window.TopicComposer.initialValues = {
            position: item.Item_Position,
            title: item.Item_Title,
            content: item.Item_Content
        };

        const editorHtml = `
            <form class="item-edit-form" data-index="${index}">
                <!-- Add sticky header with action buttons -->
                <div class="editor-actions-header">
                    <div class="btn-group">
                        <button type="button" class="btn btn-default reset-changes-btn">
                            <i class="fa fa-undo"></i> <?php echo _l('reset_changes'); ?>
                        </button>
                        <button type="button" class="btn btn-info save-item-btn">
                            <i class="fa fa-save"></i> <?php echo _l('save_item'); ?>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label><?php echo _l('position'); ?></label>
                    <div class="input-group">
                        <input type="text" class="form-control" name="position" 
                               value="${item.Item_Position}"
                               onchange="window.TopicComposer.handlers.markAsChanged()">
                        <span class="input-group-btn">
                            <button type="button" 
                                    class="btn btn-info quick-position-btn"
                                    onclick="window.TopicComposer.handlers.quickSetPosition(${index})"
                                    title="<?php echo _l('quick_set_position'); ?>">
                                <i class="fa fa-sort-numeric-asc"></i>
                            </button>
                        </span>
                    </div>
                </div>
                <div class="form-group">
                    <label>
                        <?php echo _l('title'); ?>
                        <div class="btn-group pull-right">
                            <button type="button" class="btn btn-xs btn-info ai-edit-btn" data-field="title">
                                <i class="fa fa-magic"></i> <?php echo _l('ai_edit'); ?>
                            </button>
                            <!-- <button type="button" class="btn btn-xs btn-primary ai-search-btn" data-field="title">
                                <i class="fa fa-search"></i> <?php echo _l('ai_search'); ?>
                            </button> -->
                        </div>
                    </label>
                    <input type="text" class="form-control" name="title" 
                           value="${htmlEntityDecode(item.Item_Title)}" 
                           onchange="window.TopicComposer.handlers.markAsChanged()">
                </div>
                <div class="form-group">
                    <label>
                        <?php echo _l('content'); ?>
                        <div class="btn-group pull-right">
                            <button type="button" class="btn btn-xs btn-info ai-edit-btn" data-field="content">
                                <i class="fa fa-magic"></i> <?php echo _l('ai_edit'); ?>
                            </button>
                            <!-- <button type="button" class="btn btn-xs btn-primary ai-search-btn" data-field="content">
                                <i class="fa fa-search"></i> <?php echo _l('ai_search'); ?>
                            </button> -->
                        </div>
                    </label>
                    <textarea class="form-control content-editor" name="content">${htmlEntityDecode(item.Item_Content)}</textarea>
                </div>
                <div class="form-group">
                    <label><?php echo _l('images'); ?></label>
                    <div class="row">
                        ${generateImageGallery(item)}
                    </div>
                </div>
                <div class="form-group">
                    <label><?php echo _l('keywords'); ?></label>
                    <input type="text" class="form-control" name="keywords" 
                           value="${item.TopicKeywords || ''}" readonly>
                </div>
            </form>
        `;

        $editor.html(editorHtml);

        // Add styles for sticky elements
        const styleHtml = `
            <style>
                /* Sticky header styles */
                .editor-actions-header {
                    position: sticky;
                    top: 0;
                    background: #fff;
                    padding: 10px;
                    border-bottom: 1px solid #ddd;
                    margin: -15px -15px 15px -15px;
                    z-index: 100;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                }

                /* Sticky footer styles */
                #topic-composer-modal .modal-footer {
                    position: sticky;
                    bottom: 0;
                    background: #fff;
                    padding: 15px;
                    border-top: 1px solid #ddd;
                    margin: 0;
                    z-index: 100;
                    box-shadow: 0 -2px 4px rgba(0,0,0,0.1);
                }

                /* Adjust modal body padding */
                #topic-composer-modal .modal-body {
                    padding-bottom: 70px; /* Make room for sticky footer */
                }

                /* Add transition effects */
                .editor-actions-header,
                #topic-composer-modal .modal-footer {
                    transition: box-shadow 0.3s ease;
                }

                .editor-actions-header.scrolled,
                #topic-composer-modal .modal-footer.scrolled {
                    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
                }

                /* Add visual feedback for buttons */
                .editor-actions-header .btn,
                #topic-composer-modal .modal-footer .btn {
                    transition: all 0.2s ease;
                }

                .editor-actions-header .btn:hover,
                #topic-composer-modal .modal-footer .btn:hover {
                    transform: translateY(-1px);
                }

                /* Add separator between buttons */
                .editor-actions-header .btn-group {
                    border-radius: 4px;
                    overflow: hidden;
                }

                .editor-actions-header .btn {
                    border-right: 1px solid rgba(0,0,0,0.1);
                }

                .editor-actions-header .btn:last-child {
                    border-right: none;
                }
            </style>
        `;

        // Add styles once
        if (!$('head').find('style[data-id="editor-sticky-styles"]').length) {
            $('head').append($(styleHtml).attr('data-id', 'editor-sticky-styles'));
        }

        // Add scroll event handler for shadow effects
        const $header = $('.editor-actions-header');
        const $footer = $('#topic-composer-modal .modal-footer');
        
        $('#topic-composer-modal .modal-body').scroll(function() {
            const scrollTop = $(this).scrollTop();
            
            if (scrollTop > 10) {
                $header.addClass('scrolled');
            } else {
                $header.removeClass('scrolled');
            }
             
            const scrollBottom = $(this).scrollTop() + $(this).height();
            const contentHeight = $(this)[0].scrollHeight;
            
            if (contentHeight - scrollBottom > 10) {
                $footer.addClass('scrolled');
            } else {
                $footer.removeClass('scrolled');
            }
        });

        // Setup AI action handlers
        setupAIHandlers($editor, index);

        // Bind event handlers
        $editor.find('.reset-changes-btn').on('click', function() {
            resetChanges(index);
        });

        $editor.find('.save-item-btn').on('click', function() {
            saveItemChanges(index);
            refreshItemsList();
        });

        // Initialize TinyMCE
        tinymce.init({
            selector: '.content-editor',
            height: 400,
            // Use TinyMCE CDN
            content_css: 'https://cdn.tiny.cloud/1/no-api-key/tinymce/6.7.2/skins/content/default/content.min.css',
            skin_url: 'https://cdn.tiny.cloud/1/no-api-key/tinymce/6.7.2/skins/ui/oxide',
            // Basic plugins from CDN
            plugins: [
                'advlist',
                'autolink',
                'lists',
                'link',
                'image',
                'charmap',
                'preview',
                'anchor',
                'searchreplace',
                'visualblocks',
                'code',
                'fullscreen',
                'insertdatetime',
                'media',
                'table',
                'help',
                'wordcount'
            ],
            // Simplified toolbar
            toolbar: [
                'undo redo | formatselect | bold italic underline strikethrough',
                'alignleft aligncenter alignright alignjustify | bullist numlist outdent indent',
                'link image media | removeformat help'
            ].join(' | '),
            // Additional settings
            menubar: false,
            statusbar: true,
            browser_spellcheck: true,
            contextmenu: false,
            paste_as_text: true,
            setup: function(editor) {
                editor.on('change', function() {
                    try {
                    window.TopicComposer.handlers.markAsChanged();
                    } catch (error) {
                        console.error('Error marking as changed:', error);
                    }
                });
                
                // Add custom CSS
                editor.on('init', function() {
                    editor.getDoc().body.style.fontSize = '14px';
                    editor.getDoc().body.style.fontFamily = '-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Oxygen-Sans,Ubuntu,Cantarell,"Helvetica Neue",sans-serif';
                });
            }
        });

        // Store editor instance
        window.TopicComposer.editors.set(index, tinymce.get($editor.find('.content-editor')[0].id));

        // Update current editing index
        window.TopicComposer.currentEditingIndex = index;
        
        // Highlight active item in list
        $('.edit-item-btn').removeClass('active');
        $(`.edit-item-btn[data-index="${index}"]`).addClass('active');

        // Thêm xử lý scroll sau khi init editor
        const originalLoadItemEditor = window.TopicComposer.handlers.loadItemEditor;
        
        // Trong hàm saveItemChanges, thêm callback sau khi save
        const originalSaveItemChanges = window.TopicComposer.handlers.saveItemChanges;
       
    }

    function setupAIHandlers($editor, index) {
        // Handle AI Edit
        $editor.on('click', '.ai-edit-btn', function() {
            const field = $(this).data('field');
            const $field = $editor.find(`[name="${field}"]`);
            const content = field === 'content' ? 
                tinymce.get($field[0].id).getContent() : 
                $field.val();

            const $btn = $(this);

            // Show prompt selection first
            showPromptSelectionModal(content, field, function(selectedPrompt) {
                // Add loading overlay
                const $fieldWrapper = field === 'content' ? 
                    $field.closest('.mce-container') : 
                    $field.parent();
                
                $fieldWrapper.addClass('ai-processing');
                
                // Disable field
                if (field === 'content') {
                    const editor = tinymce.get($field[0].id);
                    if (editor) {
                        editor.getBody().contentEditable = false;
                        $(editor.getContainer()).addClass('mce-disabled');
                        $(editor.getContainer()).addClass('mce-loading');
                    }
                } else {
                    $field.prop('disabled', true);
                }

                $btn.prop('disabled', false)
                .html('<i class="fa fa-magic"></i> <?php echo _l('ai_edit'); ?>');

                 console.log('1- callAIEditAPI');
               
            }, $(this));
        });

        // Handle AI Search
        $editor.on('click', '.ai-search-btn', function() {
            const field = $(this).data('field');
            const $field = $editor.find(`[name="${field}"]`);
            const content = field === 'content' ? 
                tinymce.get($field[0].id).getContent() : 
                $field.val();

            showAISearchModal(content, field, function(searchResult) {
                if (field === 'content') {
                    const editor = tinymce.get($field[0].id);
                    if (editor) {
                        editor.setContent(searchResult);
                    }
                } else {
                    $field.val(searchResult);
                }
                window.TopicComposer.handlers.markAsChanged();
            });
        });
    }

    /**
     * Check if there are unsaved changes
     * @returns {boolean} True if there are unsaved changes, false otherwise
     */
    function hasUnsavedChanges() {
        if (window.TopicComposer.currentEditingIndex === -1) return window.TopicComposer.hasChanges;
        
        const $form = $('.item-edit-form');
        if (!$form.length) return window.TopicComposer.hasChanges;

        const currentValues = {
            position: $form.find('[name="position"]').val(),
            title: $form.find('[name="title"]').val(),
            content: tinymce.get($form.find('.content-editor')[0].id).getContent()
        };

        const initialValues = window.TopicComposer.initialValues;
        if (currentValues !== 'undefined' && initialValues !== 'undefined') {   
        return currentValues.position !== initialValues.position ||
               currentValues.title !== initialValues.title ||
               currentValues.content !== initialValues.content ||
               window.TopicComposer.hasChanges;
        }
        return false;
    }

    function resetChanges(index) {
        if (confirm('<?php echo _l('confirm_reset_changes'); ?>')) {
            loadItemEditor(index);
        }
    }

    function saveItemChanges(index) {
        const $form = $('.item-edit-form');
        const editor = tinymce.get($form.find('.content-editor')[0].id);
        
        // Update item data
        window.TopicComposer.items[index] = {
            ...window.TopicComposer.items[index],
            Item_Position: $form.find('[name="position"]').val(),
            Item_Title: $form.find('[name="title"]').val(),
            Item_Content: editor.getContent()
        };

        // Update initial values after save
        window.TopicComposer.initialValues = {
            position: window.TopicComposer.items[index].Item_Position,
            title: window.TopicComposer.items[index].Item_Title,
            content: window.TopicComposer.items[index].Item_Content
        };

        window.TopicComposer.hasChanges = false;
        $('.form-actions').removeClass('has-changes');
        
        // Show success message
        alert_float('success', '<?php echo _l('item_saved'); ?>');
    }

    function updateItemsOrder() {
        $('.sortable-items .list-group-item').each(function(index) {
            const itemIndex = $(this).data('index');
            const newPosition = `Top ${index + 1}`;
            
            // Kiểm tra nếu vị trí thay đổi
            if (window.TopicComposer.items[itemIndex].Item_Position !== newPosition) {
                window.TopicComposer.items[itemIndex].Item_Position = newPosition;
                window.TopicComposer.hasChanges = true; // Đánh dấu có thay đổi
            }
            
            $(this).find('.item-position').text(newPosition);
            
            // Update position in editor if this item is being edited
            if (window.TopicComposer.currentEditingIndex === itemIndex) {
                $('[name="position"]').val(newPosition);
            }
        });

        // Hiển thị visual feedback khi có thay đổi
        if (window.TopicComposer.hasChanges) {
            $('.form-actions').addClass('has-changes');
            // Hiển thị nút Save All nếu đang ẩn
            $('.save-all-btn').show();
        }
    }

    function saveAllChanges() {
        const $modal = $('#topic-composer-modal');
        const $saveBtn = $modal.find('.save-all-btn');
        
        $saveBtn.prop('disabled', true)
               .html('<i class="fa fa-spinner fa-spin"></i> <?php echo _l('saving'); ?>');

        // Collect all changes
        const updatedData = window.TopicComposer.items.map((item, index) => {
            const $form = $(`.item-edit-form[data-index="${index}"]`);
            if ($form.length) {
                const editor = window.TopicComposer.editors.get(index);
                return {
                    ...item,
                    Item_Position: $form.find('[name="position"]').val(),
                    Item_Title: $form.find('[name="title"]').val(),
                    Item_Content: editor ? editor.getContent() : item.Item_Content,
                    'web-scraper-order': item['web-scraper-order'] // Giữ nguyên order
                };
            }
            return item;
        });

        // Prepare data for submission with correct structure
        const submissionData = prepareSubmissionData();
     

        // Generate changes summary
        const changesSummary = generateChangesSummary(window.TopicComposer.originalItems, updatedData);

        // Create execution result HTML
        const timestamp = moment().format('DD/MM/YYYY HH:mm:ss');
        const resultHtml = `
            <div class="execution-result-item">
                <div class="execution-timestamp text-muted">
                    <i class="fa fa-clock-o"></i> ${timestamp}
                </div>
                <div class="execution-status">
                    <i class="fa fa-check text-success"></i> 
                    <strong><?php echo _l('changes_ready_to_apply'); ?></strong>
                </div>
                <div class="execution-details mtop10">
                    <div class="panel panel-info">
                        <div class="panel-heading">
                            <h3 class="panel-title"><?php echo _l('changes_summary'); ?></h3>
                        </div>
                        <div class="panel-body">
                            <pre class="changes-json"><code>${JSON.stringify(changesSummary, null, 2)}</code></pre>
                            <div class="action-buttons mtop15">
                                <button type="button" class="btn btn-default preview-changes-btn">
                                    <i class="fa fa-eye"></i> <?php echo _l('preview_changes'); ?>
                                </button>
                                <button type="button" class="btn btn-info apply-changes-btn">
                                    <i class="fa fa-check"></i> <?php echo _l('apply_changes'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Hide modal and show execution result
        $modal.modal('hide');
        $('.modal-backdrop').remove();
        prependExecutionResult(resultHtml);

        // Setup action buttons with full data
        setupResultActions(submissionData);
    }

    /**
     * Generate order ID
     * @returns {String} Order ID
     */
    function generateOrderId() {
        return `temp-${Date.now()}-${Math.floor(Math.random() * 1000)}`;
    }

    /**
     * Generate changes summary
     * @param {Array} originalItems - Original items
     * @param {Array} updatedItems - Updated items
     * @returns {Object} Changes summary
     */
    function generateChangesSummary(originalItems, updatedItems) {
        console.log('generateChangesSummary', originalItems, updatedItems);
        const changes = {
            items_count: updatedItems.length,
            modified_items: [],
            deleted_items: [], // Mảng chứa items bị xóa
            added_items: [], // Mảng chứa items mới thêm
            changes_overview: {
                position_changes: 0,
                title_changes: 0,
                content_changes: 0,
                deleted_count: 0, // Số lượng items bị xóa
                added_count: 0 // Số lượng items mới
            }
        };

        // Tạo map để tra cứu nhanh bằng web-scraper-order
        const originalMap = new Map(
            originalItems.map(item => [item['web-scraper-order'], item])
        );
        const updatedMap = new Map(
            updatedItems.map(item => [item['web-scraper-order'], item])
        );

        // Kiểm tra items bị xóa (có trong original nhưng không có trong updated)
        originalItems.forEach(original => {
            const order = original['web-scraper-order'];
            if (!updatedMap.has(order)) {
                changes.deleted_items.push({
                    order: order,
                    item_position: original.Item_Position,
                    item_title: original.Item_Title,
                    original_item: original
                });
                changes.changes_overview.deleted_count++;
            }
        });

        // Kiểm tra items mới và thay đổi
        updatedItems.forEach((item, index) => {
            const order = item['web-scraper-order'];
            const original = originalMap.get(order);

            // Item mới (không có trong original)
            if (!original) {
                changes.added_items.push({
                    order: order,
                    item_position: item.Item_Position,
                    item_title: item.Item_Title,
                    new_item: item
                });
                changes.changes_overview.added_count++;
                return;
            }

            // Kiểm tra thay đổi nếu item tồn tại trong cả 2 bản
            const itemChanges = {
                item_position: item.Item_Position,
                changes: {},
                original_item: original
            };

            // Check position changes
            if (item.Item_Position !== original.Item_Position) {
                itemChanges.changes.position = {
                    from: original.Item_Position,
                    to: item.Item_Position
                };
                changes.changes_overview.position_changes++;
            }

            // Check title changes
            if (item.Item_Title !== original.Item_Title) {
                itemChanges.changes.title = {
                    from: original.Item_Title,
                    to: item.Item_Title
                };
                changes.changes_overview.title_changes++;
            }

            // Check content changes
            if (item.Item_Content !== original.Item_Content) {
                itemChanges.changes.content = {
                    summary: '<?php echo _l('content_modified'); ?>',
                    original_content: original.Item_Content,
                    new_content: item.Item_Content
                };
                changes.changes_overview.content_changes++;
            }

            if (Object.keys(itemChanges.changes).length > 0) {
                itemChanges.web_scraper_order = order;
                changes.modified_items.push(itemChanges);
            }
        });

        return changes;
    }

    /**
     * @returns {Promise<Object>} Promise resolving to {exists: boolean, downloadedUrl: string}
     */
    async function isImageDownloaded(imgUrl) {
        // Kiểm tra cache trước khi gọi API
        if (window.downloadedImages && window.downloadedImages.has(imgUrl)) {
            // Get cached downloaded URL if available
            const cachedUrl = window.downloadedImagesMap ? window.downloadedImagesMap.get(imgUrl) : imgUrl;
            console.log("isImageDownloaded 2887", imgUrl, cachedUrl);
            return { exists: true, downloadedUrl: cachedUrl || imgUrl };
        }
        
        try {
            // Generate MD5 hash of URL for rel_id if available
            const rel_id = typeof window.TopicComposer !== 'undefined' && 
                          typeof window.TopicComposer.handlers !== 'undefined' && 
                          typeof window.TopicComposer.handlers.md5 === 'function' ? 
                          window.TopicComposer.handlers.md5(imgUrl) : imgUrl;
            
            // Sử dụng check_image_external_data API để kiểm tra
            const externalData = await checkImageExternalData(imgUrl);
            
            // Nếu ảnh tồn tại, thêm vào cache
            if (externalData.exists && externalData.rel_data) {
                // Get the downloaded URL from rel_data
                const downloadedUrl = externalData.rel_data;
                
                // Add to cache for future synchronous checks
                if (!window.downloadedImages) {
                    window.downloadedImages = new Set();
                }
                if (!window.downloadedImagesMap) {
                    window.downloadedImagesMap = new Map();
                }
                
                window.downloadedImages.add(imgUrl);
                window.downloadedImagesMap.set(imgUrl, downloadedUrl);
                
                return { exists: true, downloadedUrl: downloadedUrl };
            }
            
            return { exists: false, downloadedUrl: null };
        } catch (e) {
            console.log('Error checking if image is downloaded:', e);
            return { exists: false, downloadedUrl: null };
        }
    }

    /**
     * Đếm số lượng hình ảnh chưa được download trong một item
     * (Phiên bản đồng bộ - chỉ kiểm tra cache local)
     * @param {Object|String} content - HTML content hoặc item object
     * @param {Object} item - Item object (optional nếu content đã là item object)
     * @returns {Promise<Number>} Số lượng hình ảnh chưa download (dựa trên cache)
     */
    async function countUndownloadedImages(content, item) {
        // Nếu không có tham số item, chỉ dùng content (cho khả năng tương thích ngược)
        if (!item && typeof content === 'object') {
            item = content;
            content = item.Item_Content;
        }
        
        // Trường hợp không có dữ liệu
        if (!item && !content) return 0;
        
        // Lấy danh sách ảnh từ item hoặc content
        const imageUrls = extractImagesFromContent(item || content);
        
        // Nếu không có ảnh
        if (!imageUrls.length) return 0;
        
        // Đếm số lượng ảnh chưa download (dùng Promise.all để kiểm tra tất cả các ảnh một lúc)
        const imageResults = await Promise.all(
            imageUrls.map(imgUrl => isImageDownloaded(imgUrl))
        );
        
        // Lọc ra các ảnh chưa download
        const undownloadedCount = imageResults.filter(result => !result.exists).length;
        
        return undownloadedCount;
    }

    /**
     * Đếm số lượng hình ảnh chưa được download trong một item
     * (Phiên bản bất đồng bộ - kiểm tra đầy đủ qua API)
     * @param {Object|String} content - HTML content hoặc item object
     * @param {Object} item - Item object (optional nếu content đã là item object)
     * @returns {Promise<Number>} Promise resolving to number of undownloaded images
     */
    async function countUndownloadedImagesAsync(content, item) {
        // Nếu không có tham số item, chỉ dùng content (cho khả năng tương thích ngược)
        if (!item && typeof content === 'object') {
            item = content;
            content = item.Item_Content;
        }
        
        // Trường hợp không có dữ liệu
        if (!item && !content) return 0;
        
        // Lấy danh sách ảnh từ item hoặc content
        const imageUrls = extractImagesFromContent(item || content);
        
        // Nếu không có ảnh
        if (!imageUrls.length) return 0;
        
        // Kiểm tra tất cả ảnh bằng Promise.all
        const downloadCheckResults = await Promise.all(
            imageUrls.map(async imgUrl => {
                const isDownloaded = await isImageDownloaded(imgUrl);
                return { imgUrl, isDownloaded };
            })
        );
        
        // Lọc ra các ảnh chưa download
        const undownloadedImages = downloadCheckResults.filter(result => !result.isDownloaded);
        
        return undownloadedImages.length;
    }

    /**
     * Trích xuất tất cả các URLs hình ảnh từ một item hoặc HTML content
     * @param {Object|String} itemOrContent - Item object hoặc HTML content
     * @returns {Array} Mảng chứa các URL hình ảnh
     */
    function extractImagesFromContent(itemOrContent) {
        // Khởi tạo mảng lưu trữ URLs của ảnh
        const imageUrls = [];
        
        // Trường hợp không có dữ liệu
        if (!itemOrContent) return imageUrls;
        console.log("extractImagesFromContent 1902", itemOrContent);
        try {
            // Trường hợp input là item object
            if (typeof itemOrContent === 'object') {
                // Ưu tiên sử dụng item_Pictures nếu có
                if (itemOrContent.item_Pictures) {
                    try {
                        // Trường hợp item_Pictures là chuỗi JSON
                        if (typeof itemOrContent.item_Pictures === 'string') {
                            const parsed = JSON.parse(itemOrContent.item_Pictures);
                            if (Array.isArray(parsed)) {
                                const urls = parsed.map(img => img['item_Pictures-src']).filter(url => url);
                                return urls;
                            }
                        }
                        // Trường hợp item_Pictures đã là object
                        else if (Array.isArray(itemOrContent.item_Pictures)) {
                            const urls = itemOrContent.item_Pictures
                                .map(img => img['item_Pictures-src'])
                                .filter(url => url);
                            return urls;
                        }
                    } catch (e) {
                        console.error('Error parsing item_Pictures:', e);
                    }
                }
                
                // Nếu không có item_Pictures hoặc parsing thất bại, thử parse từ Item_Content
                if (itemOrContent.Item_Content) {
                    return extractImagesFromContent(itemOrContent.Item_Content);
                }
                
                return imageUrls;
            }
        } catch (e) {
            console.error('Error extracting images:', e);
        }
        
        return imageUrls;
    }

    // Khởi tạo cache để lưu trữ ảnh đã download
    if (typeof window.downloadedImages === 'undefined') {
        window.downloadedImages = new Set();
    }

    /**
     * Setup result actions
     * @param {Object} submissionData - Data to be submitted
         */
    function setupResultActions(submissionData) {
        console.log('setupResultActions', submissionData);
        // Preview changes
        $('.preview-changes-btn').click(function() {
            // Lấy full_items từ submissionData để preview
            showPreviewModal(submissionData);
        });

        // Apply changes - proceed to step 2
        $('.apply-changes-btn').click(function() {
            const $btn = $(this);
            const clickedButton = $(this);
            
            // Lấy dữ liệu workflow từ step 1 đã lưu
            const baseWorkflowData = window.currentWorkflowData || {  };
            baseWorkflowData.audit_step = 2;
            // Thêm dữ liệu changes vào workflowData gốc
            const extendedData = {
                ...baseWorkflowData,
                changes_data: submissionData
            };

            // Disable button và hiển thị loading
            $btn.prop('disabled', true)
               .html('<i class="fa fa-spinner fa-spin"></i> <?php echo _l('applying_changes'); ?>');

            // Gọi hàm executeWorkflow có sẵn
            executeWorkflow(extendedData).then(function(response) {
                if (response.success && response.workflow_id && response.execution_id) {
                    // Hiển thị loading state
                    var timestamp = moment().format('DD/MM/YYYY HH:mm:ss');
                    var loadingHtml = `
                        <div class="execution-result-item" id="topic-composer-result">
                            <div class="execution-timestamp text-muted">
                                <i class="fa fa-clock-o"></i> ${timestamp}
                            </div>
                            <div class="execution-status">
                                <i class="fa fa-spinner fa-spin text-info"></i> 
                                <strong><?php echo _l('applying_changes'); ?></strong>
                            </div>
                            <div class="execution-details mtop10">
                                <div class="progress">
                                    <div class="progress-bar progress-bar-striped active" 
                                         role="progressbar" 
                                         style="width: 100%">
                                        <span class="status-text">processing</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    prependExecutionResult(loadingHtml);

                    // Bắt đầu polling
                    pollWorkflowStatus(
                        response.workflow_id,
                        response.execution_id,
                        extendedData,
                        function(successData) {
                            // Callback khi polling hoàn tất thành công
                            if (successData.reload) {
                                window.location.reload();
                            }
                        }
                    );
                } else {
                    alert_float('danger', response.message || '<?php echo _l('error_applying_changes'); ?>');
                    $btn.prop('disabled', false)
                       .html('<i class="fa fa-check"></i> <?php echo _l('apply_changes'); ?>');
                }
            }).catch(function(error) {
                console.error('Error executing workflow:', error);
                alert_float('danger', '<?php echo _l('error_applying_changes'); ?>');
                $btn.prop('disabled', false)
                   .html('<i class="fa fa-check"></i> <?php echo _l('apply_changes'); ?>');
            });
        });
    }
 
    function showPreviewModal(submissionData) {
        // Thêm các fallback cho dữ liệu không tồn tại
        const safeSubmissionData = submissionData || {};
        const changesSummary = safeSubmissionData.changesSummary || {};
        
        // Xử lý modified items an toàn
        const modifiedItems = Array.isArray(changesSummary.modified_items) 
            ? changesSummary.modified_items.map(item => ({
                original_item: item.original_item || {},
                new_item: item.new_item || {},
                web_scraper_order: item.web_scraper_order || '',
                changes: Array.isArray(item.changes) ? item.changes : []
            })) 
            : [];

        // Xử lý added items an toàn
        const addedItems = Array.isArray(changesSummary.added_items) 
            ? changesSummary.added_items.map(item => ({
                Item_Title: item.Item_Title || item.title || 'Untitled',
                Item_Position: item.Item_Position || item.position || '',
                web_scraper_order: item.web_scraper_order || item.order || ''
            })) 
            : [];

        // Xử lý deleted items an toàn
        const deletedItems = Array.isArray(changesSummary.deleted_items) 
            ? changesSummary.deleted_items.map(item => ({
                Item_Title: item.Item_Title || item.title || 'Untitled',
                Item_Position: item.Item_Position || item.position || '',
                order: item.order || ''
            })) 
            : [];

        // Hàm helper lấy title an toàn
        const getItemTitle = (item) => {
            return item.Item_Title 
                || item.item_title 
                || item.title 
                || 'Untitled';
        };

        // Tạo nội dung modal
        const modalHtml = `
            <div class="modal fade" id="preview-changes-modal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title"><?php _l('preview_changes'); ?></h5>
                            <button type="button" class="close" data-dismiss="modal">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="changes-summary">
                                <h5><?php echo _l('changes_summary'); ?></h5>
                                <div class="summary-stats">
                                    <div class="stat-item">
                                        <div class="stat-label"><?php echo _l('total_items'); ?></div>
                                        <div class="stat-value">${changesSummary.items_count || 0}</div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-label"><?php echo _l('modified_items'); ?></div>
                                        <div class="stat-value">${modifiedItems.length}</div>
                                    </div>
                                </div>

                                ${addedItems.length > 0 ? `
                                    <section class="mb-4">
                                        <h6 class="text-success">
                                            <i class="fa fa-plus-circle"></i> 
                                            <?php echo _l('added_items'); ?> (${addedItems.length})
                                        </h6>
                                        <div class="list-group">
                                            ${addedItems.map(item => `
                                                <div class="list-group-item">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <span>${getItemTitle(item)}</span>
                                                        <span class="badge bg-primary">#${item.Item_Position}</span>
                                                    </div>
                                                    ${item.web_scraper_order ? `<small class="text-muted">Order: ${item.web_scraper_order}</small>` : ''}
                                                </div>
                                            `).join('')}
                                        </div>
                                    </section>
                                ` : ''}

                                ${modifiedItems.length > 0 ? `
                                    <section class="mb-4">
                                        <h6 class="text-warning">
                                            <i class="fa fa-edit"></i> 
                                            <?php echo _l('modified_items'); ?> (${modifiedItems.length})
                                        </h6>
                                        ${modifiedItems.map(item => `
                                            <div class="card mb-3">
                                                <div class="card-body">
                                                    <h6 class="card-title d-flex justify-content-between">
                                                        <span>${getItemTitle(item.original_item)} → ${getItemTitle(item.new_item)}</span>
                                                        ${item.web_scraper_order ? `<span class="badge bg-dark">Order: ${item.web_scraper_order}</span>` : ''}
                                                    </h6>
                                                    <div class="changes-list">
                                                        ${item.changes.map(change => `
                                                            <div class="change-item">
                                                                <span class="change-field">${change.field}:</span>
                                                                <span class="change-from">${change.from || 'N/A'}</span>
                                                                <i class="fa fa-arrow-right mx-2 text-muted"></i>
                                                                <span class="change-to">${change.to || 'N/A'}</span>
                                                            </div>
                                                        `).join('')}
                                                    </div>
                                                </div>
                                            </div>
                                        `).join('')}
                                    </section>
                                ` : ''}

                                ${deletedItems.length > 0 ? `
                                    <section>
                                        <h6 class="text-danger">
                                            <i class="fa fa-trash"></i> 
                                            <?php echo _l('deleted_items'); ?> (${deletedItems.length})
                                        </h6>
                                        <div class="list-group">
                                            ${deletedItems.map(item => `
                                                <div class="list-group-item bg-light">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <del>${getItemTitle(item)}</del>
                                                        <span class="badge bg-secondary">#${item.Item_Position}</span>
                                                    </div>
                                                    ${item.order ? `<small class="text-muted">Order: ${item.order}</small>` : ''}
                                                </div>
                                            `).join('')}
                                        </div>
                                    </section>
                                ` : ''}
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal"><?php echo _l('close'); ?></button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Xóa modal cũ nếu tồn tại
        $('#preview-changes-modal').remove();
        
        // Thêm modal mới và hiển thị
        $('body').append(modalHtml);
        $('#preview-changes-modal').modal('show');
         $(document.getElementById('preview-changes-modal')).find('.close-preview-changes-modal').on('click', function() {
        {
            $(document.getElementById('preview-changes-modal')).modal('hide');
        }
    });

    } 

    // Hàm tạo nội dung preview
    function generatePreviewContent(data) {
        data = data.changesSummary || {};
        console.log('generatePreviewContent data:', data);
        
        let content = '';
        
        // Thêm kiểm tra tồn tại các trường
        const addedCount = data.changes_overview?.added_count || 0;
        const deletedCount = data.changes_overview?.deleted_count || 0;
        
        // Added Items
        if (addedCount > 0) {
            content += `
                <section class="mb-4">
                    <h6 class="d-flex align-items-center gap-2 mb-3 text-success">
                        <i class="fas fa-plus-circle"></i>
                        <?php echo _l('added_items'); ?> (${addedCount})
                    </h6>
                    <div class="list-group">
                        ${data.added_items.map(item => `
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between">
                                    <strong>${item.item_title}</strong>
                                    <span class="badge bg-primary">#${item.item_position}</span>
                                </div>
                                ${item.web_scraper_order ? `<small class="text-muted">Order: ${item.web_scraper_order}</small>` : ''}
                            </div>
                        `).join('')}
                    </div>
                </section>
            `;
        }

        // Modified Items
        if (data.modified_items.length > 0) {
            content += `
                <section class="mb-4">
                    <h6 class="d-flex align-items-center gap-2 mb-3 text-warning">
                        <i class="fas fa-pencil-alt"></i>
                        <?php echo _l('modified_items'); ?> (${data.modified_items.length})
                    </h6>
                    ${data.modified_items.map(item => `
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between mb-2">
                                    <h6 class="card-title">${item.original_item.Item_Title}</h6>
                                    <span class="badge bg-dark">Order: ${item.web_scraper_order}</span>
                                </div>
                                ${renderChanges(item.changes)}
                            </div>
                        </div>
                    `).join('')}
                </section>
            `;
        }

        // Deleted Items
        if (deletedCount > 0) {
            content += `
                <section>
                    <h6 class="d-flex align-items-center gap-2 mb-3 text-danger">
                        <i class="fas fa-trash-alt"></i>
                        <?php echo _l('deleted_items'); ?> (${deletedCount})
                    </h6>
                    <div class="list-group">
                        ${data.deleted_items.map(item => `
                            <div class="list-group-item bg-light">
                                <div class="d-flex justify-content-between">
                                    <del>${item.item_title}</del>
                                    <span class="badge bg-secondary">#${item.item_position}</span>
                                </div>
                                ${item.order ? `<small class="text-muted">Order: ${item.order}</small>` : ''}
                            </div>
                        `).join('')}
                    </div>
                </section>
            `;
        }

        return content || `<div class="alert alert-info"><?php echo _l('no_changes_to_preview'); ?></div>`;
    }

    // Hàm render thống kê
    function renderSummaryStats(summary) {
        return `
            <dl class="row mb-0">
                <dt class="col-6"><?php echo _l('total_items'); ?></dt>
                <dd class="col-6 text-end"><?php echo $summary->items_count; ?></dd>
                
                <dt class="col-6"><?php echo _l('position_changes'); ?></dt>
                <dd class="col-6 text-end"><?php echo $summary->changes_overview->position_changes; ?></dd>
                
                <dt class="col-6"><?php echo _l('title_changes'); ?></dt>
                <dd class="col-6 text-end"><?php echo $summary->changes_overview->title_changes; ?></dd>
                
                <dt class="col-6"><?php echo _l('content_changes'); ?></dt>
                <dd class="col-6 text-end"><?php echo $summary->changes_overview->content_changes; ?></dd>
                
                <dt class="col-6"><?php echo _l('added_items'); ?></dt>
                <dd class="col-6 text-end"><?php echo $summary->changes_overview->added_count; ?></dd>
                
                <dt class="col-6"><?php echo _l('deleted_items'); ?></dt>
                <dd class="col-6 text-end"><?php echo $summary->changes_overview->deleted_count; ?></dd>
            </dl>
        `;
    }

    // Hàm render chi tiết thay đổi
    function renderChanges(changes) {
        return Object.entries(changes).map(([field, data]) => {
            const fieldLabel = {
                position: <?php echo _l('position'); ?>,
                title: <?php echo _l('title'); ?>,
                content: <?php echo _l('content'); ?>
            }[field] || field;

            return `
                <div class="change-item mb-3">
                    <div class="d-flex align-items-center gap-2 text-muted mb-2">
                        <i class="fas fa-arrow-circle-right"></i>
                        <strong>${fieldLabel}</strong>
                    </div>
                    <div class="change-diff ps-4">
                        ${renderDiff(field, data)}
                    </div>
                </div>
            `;
        }).join('');
    }

    // Hàm render diff
    function renderDiff(field, data) {
        console.log('renderDiff', field, data); 
        const from = data.from || data.original_content || '';
        const to = data.to || data.new_content || '';

        if (field === 'content') {
            return `
                <div class="row g-2">
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-header small"><?php echo _l('original'); ?></div>
                            <div class="card-body small"><?php echo $from; ?></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-header small"><?php echo _l('new_version'); ?></div>
                            <div class="card-body small"><?php echo $to; ?></div>
                        </div>
                    </div>
                </div>
            `;
        }
        
        return `
            <div class="d-flex align-items-center gap-2">
                <span class="text-danger">${truncateContent(from)}</span>
                <i class="fas fa-arrow-right text-muted"></i>
                <span class="text-success">${truncateContent(to)}</span>
            </div>
        `;
    }

    // Hàm hỗ trợ
    function truncateContent(text, maxLength = 150) {
        if (typeof text !== 'string') {
            if (typeof text === 'number') return text.toString();
            if (text === null || text === undefined) return '';
            return JSON.stringify(text);
        }
        return text.length > maxLength ? text.substring(0, maxLength) + '...' : text;
    }

    function prepareSubmissionData() {
        const originalMap = new Map(
            window.TopicComposer.originalItems.map(item => [item['web-scraper-order'], item])
        );
        
        const submissionData = {
            added_items: [],
            updated_items: [],
            deleted_items: [],
            all_items: window.TopicComposer.items.map(item => ({
                ...item,
                'web-scraper-order': item['web-scraper-order'] || generateOrderId()
            }))
        };

        // Process added and updated items
        submissionData.all_items.forEach(item => {
            const order = item['web-scraper-order'];
            if (!originalMap.has(order)) {
                submissionData.added_items.push({
                    order: order,
                    data: item
                });
            } else {
                const original = originalMap.get(order);
                if (isItemModified(original, item)) {
                    submissionData.updated_items.push({
                        order: order,
                        original: original,
                        changes: diffObjects(original, item)
                    });
                }
            }
        });

        // Process deleted items
        window.TopicComposer.originalItems.forEach(original => {
            const order = original['web-scraper-order'];
            if (!submissionData.all_items.find(item => item['web-scraper-order'] === order)) {
                submissionData.deleted_items.push({
                    order: order,
                    data: original
                });
            }
        });

        // Generate changes summary from the same source
        submissionData.changesSummary = generateChangesSummary(
            window.TopicComposer.originalItems,
            submissionData.all_items
        );

        return submissionData;
    }

    // Helper function to check item modifications
    function isItemModified(original, updated) {
        const fields = ['Item_Position', 'Item_Title', 'Item_Content', 'item_Pictures'];
        return fields.some(field => JSON.stringify(original[field]) !== JSON.stringify(updated[field]));
    }

    // Thêm hàm diffObjects vào phần helper functions
    function diffObjects(original, updated) {
        const changes = {};
        const fields = [
            'Item_Position',
            'Item_Title', 
            'Item_Content',
            'item_Pictures',
            'web-scraper-order'
        ];

        fields.forEach(field => {
            if (JSON.stringify(original[field]) !== JSON.stringify(updated[field])) {
                changes[field] = {
                    from: original[field],
                    to: updated[field]
                };
            }
        });

        return changes;
    }

    // Thêm hàm pollWorkflowStatus để xử lý polling
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
        $('#topic-composer-result .execution-details').after(pollInfoHtml);

        function updateCountdown() {
            timeRemaining--;
            if (timeRemaining >= 0) {
                var minutes = Math.floor(timeRemaining / 60);
                var seconds = timeRemaining % 60;
                var timeString = String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
                $('#topic-composer-result .countdown-timer').text('Time remaining: ' + timeString);
            }
        }

        function poll() {
            attempts++;
            console.log("Polling attempt: " + attempts);
            
            // Update attempts counter
            $('#topic-composer-result .attempts-counter').text(`Polling: ${attempts}/${maxAttempts}`);

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
                            $('#topic-composer-result .poll-info').remove();
                            $('#topic-composer-result .execution-status')
                                .html('<i class="fa fa-times text-danger"></i> <?php echo _l('workflow_check_failed'); ?>');
                            return;
                        }
                        
                        if (response.finished === true) {
                            if (countdownInterval) clearInterval(countdownInterval);
                            $('#topic-composer-result .poll-info').remove();
                            
                            if (response.data && response.data.success) {
                                onSuccess(response.data);
                            } else {
                                $('#topic-composer-result .execution-status')
                                    .html('<i class="fa fa-warning text-warning"></i> ' + 
                                        (response.message || '<?php echo _l('error_applying_changes'); ?>'));
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
                            $('#topic-composer-result .poll-info').remove();
                            $('#topic-composer-result .execution-status')
                                .html('<i class="fa fa-warning text-warning"></i> <?php echo _l('polling_timeout'); ?>');
                        }
                    } catch (e) {
                        console.error('Polling error:', e);
                    }
                },
                error: function() {
                    if (countdownInterval) clearInterval(countdownInterval);
                    $('#topic-composer-result .poll-info').remove();
                    $('#topic-composer-result .execution-status')
                        .html('<i class="fa fa-times text-danger"></i> <?php echo _l('polling_error'); ?>');
                }
            });
        }

        // Start countdown timer
        countdownInterval = setInterval(updateCountdown, 1000);

        // Start polling
        poll();
    }

    // Thêm hàm kiểm tra item đã được chỉnh sửa
    function getItemChanges(item, originalItem) {
        if (!originalItem) return null;
        
        const changes = [];
        if (item.Item_Title !== originalItem.Item_Title) changes.push('Title');
        if (item.Item_Content !== originalItem.Item_Content) changes.push('Content');
        if (item.Item_Position !== originalItem.Item_Position) changes.push('Position');
        
        return changes.length > 0 ? changes : null;
    }

    function cleanupEditors() {
        window.TopicComposer.editors.forEach(editor => {
            if (editor && typeof editor.destroy === 'function') {
                editor.destroy();
            }
        });
        window.TopicComposer.editors.clear();
        window.TopicComposer.currentEditingIndex = -1;
        window.TopicComposer.hasChanges = false;
    }

    // Main execution flow
    if (data.data && data.data.audit_step === 1) {
        console.log("Starting Topic Composer with:", { data, workflowData });
        
        // Parse response data
        var responseData = null;
        try {
            if (data.data.response && data.data.response.data) {
                responseData = data.data.response.data;
                console.log('Response data available:', responseData);
            } else if (data.data.response_text) {
                const parsedResponse = JSON.parse(data.data.response_text);
                if (parsedResponse.response && parsedResponse.response.data) {
                    responseData = parsedResponse.response.data;
                    console.log('Response data parsed:', responseData);
                }
            }
        } catch (e) {
            console.error('Error parsing response:', e);
        }

        // If we have response data, show modal immediately
        if (responseData) {
            console.log('Showing composer modal immediately with data');
            showComposerModal(responseData);
            return;
        }

        // Otherwise, show loading and start polling
        var resultHtml = `
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
                        <div class="progress-bar progress-bar-striped active" role="progressbar" style="width: 100%">
                            <span class="status-text">${data.data.status || 'running'}</span>
                        </div>
                    </div>
                    <div class="poll-info mtop10">
                        <?php echo _l('time_remaining'); ?>: <span class="countdown">05:00</span>
                    </div>
                </div>
            </div>
        `;
        prependExecutionResult(resultHtml);
        
        $resultContainer = $('#topic-composer-result');
        pollingStartTime = Date.now();

        // Start polling if we have workflow info
        if (data.data.workflow_id && data.data.execution_id) {
            startPolling(data.data.workflow_id, data.data.execution_id, workflowData);
        } else {
            showError('<?php echo _l('workflow_info_missing'); ?>');
        }
    }

    let actionButtons = '';
    if (data.actions && Array.isArray(data.actions)) {
        data.actions.forEach(action => {
            let buttonHtml = '';
            
            // Check action type and state
            if (action.type === 'edit' && action.state === 'ai') {
                // AI edit buttons group
                buttonHtml = `
                    <div class="btn-group">
                        <button type="button" 
                                class="btn btn-info btn-sm" 
                                onclick="handleTopicAction('${action.type}', ${data.topic_id}, '${action.command}')"
                                title="<?php echo _l('ai_edit'); ?>">
                            <i class="fa fa-magic"></i> <?php echo _l('ai_edit'); ?>
                        </button>
                        <button type="button" 
                                class="btn btn-danger btn-sm" 
                                onclick="handleTopicAction('${action.type}', ${data.topic_id}, 'generate_from_content')"
                                title="<?php echo _l('generate_from_content'); ?>">
                            <i class="fa fa-file-text"></i> <?php echo _l('generate_from_content'); ?>
                        </button>
                    </div>`;
            } else {
                // Original button generation for other action types
                buttonHtml = `
                    <button type="button" 
                            class="btn btn-${action.state === 'success' ? 'success' : 'info'} btn-sm"
                            onclick="handleTopicAction('${action.type}', ${data.topic_id}, '${action.command}')"
                            title="${action.title || ''}">
                        <i class="fa fa-${action.icon || 'check'}"></i> ${action.label || action.type}
                    </button>`;
            }
            
            actionButtons += buttonHtml;
        });
    }
    
}

/**
 * Initialize components
 */
function initializeComponents() {
    // Store items globally
    window.composerItems = composerState.items;

    // Initialize Sortable for items list
    new Sortable(document.querySelector('.sortable-items'), {
        handle: '.drag-handle',
        animation: 150,
        onEnd: function(evt) {
            updateItemsOrder();
        }
    });

    
    // Initialize first item editor if exists
    if (window.composerItems.length > 0) {
        loadItemEditor(0);
    }
}

/**
 * Save changes from composer
 */
function saveTopicComposerChanges(form) {
    var formData = new FormData(form);
    
    // Get TinyMCE content
    formData.set('section_content', tinymce.get('section_content').getContent());
    
    $.ajax({
        url: admin_url + 'topics/save_composed_topic',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            try {
                response = JSON.parse(response);
                if (response.success) {
                    alert_float('success', response.message);
                    // Reload page after save
                    setTimeout(() => location.reload(), 2000);
                } else {
                    alert_float('danger', response.message);
                }
            } catch (e) {
                console.error('Error parsing response:', e);
                alert_float('danger', 'Error saving changes');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX error:', error);
            alert_float('danger', 'Error saving changes');
        }
    });
}

/**
 * Parse and display images with external data check
 */
function parseAndDisplayImages(data) {
    try {
        let images = [];
        if (data.item_Pictures) {
            const parsed = JSON.parse(data.item_Pictures);
            images = parsed.map(img => img['item_Pictures-src']);
        }

        // Return early if no images
        if (!images.length) {
            return '<div class="alert alert-info"><?php echo _l("no_image_data"); ?></div>';
        }

        // Generate initial HTML
        let html = '<div class="row">';
        images.forEach(img => {
            const imageId = 'img-' + Math.random().toString(36).substr(2, 9);
            html += `
            <div class="col-md-6">
                    <div class="image-item">
                        <div class="image-preview">
                            <img src="${img}" 
                                 class="img-responsive" 
                                 onclick="openImagePreview('${img}')"
                                 alt="">
            </div>
                        
                        <div class="image-info">
                            <div class="image-actions" id="${imageId}">
                                <div class="loading-actions">
                                    <i class="fa fa-spinner fa-spin"></i>
                                </div>
                            </div>
                            <div class="caption text-center">
                                ${img.split('/').pop()}
                            </div>
                        </div>
                    </div>
                </div>
            `;

            // Check external data after render
            setTimeout(() => {
                checkAndUpdateImageActions(img, imageId, data);
            }, 0);
        });
        html += '</div>';

        return html;

    } catch (e) {
        console.error('Error parsing images:', e);
        return '';
    }
}

/**
 * Helper function to check and update image actions
 */
function checkAndUpdateImageActions(imageUrl, containerId, data) {
    console.log('checkAndUpdateImageActions', imageUrl, containerId, data);
    const $container = $(`#${containerId}`);
    if (!$container.length) return;

    console.log("checkAndUpdateImageActions 2887");
    checkImageExternalData(imageUrl).then(externalData => {
        if (externalData.exists) {
            // Update image source if WordPress URL exists
            const $img = $container.closest('.image-item').find('img');
            $img.attr('src', externalData.wordpress_url)
               .attr('data-original-url', imageUrl);
            
            // Update onclick handler
            $img.attr('onclick', `openImagePreview('${externalData.wordpress_url}')`);
            
            // Update caption
            $container.siblings('.caption').html('(WordPress) ' + imageUrl.split('/').pop());
            
            // Update action buttons with copy button
            $container.html(`
                <div class="btn-group btn-group-xs">
                    <button type="button" 
                            class="btn btn-info find-similar-btn" 
                            data-url="${imageUrl}" 
                            disabled
                            title="<?php echo _l('find_similar_images'); ?>">
                        <i class="fa fa-search"></i>
                    </button>
                    <button type="button" 
                            class="btn btn-success download-image-btn"
                            data-url="${imageUrl}" 
                            disabled
                            title="<?php echo _l('download_to_server'); ?>">
                        <i class="fa fa-download"></i>
                    </button>
                    <button type="button"
                            class="btn btn-default copy-url-btn"
                            data-url="${externalData.wordpress_url}"
                            title="<?php echo _l('copy_image_url'); ?>">
                        <i class="fa fa-copy"></i>
                    </button>
                </div>
                <i class="fa fa-check text-success ml-2" 
                   title="<?php echo _l('image_already_downloaded'); ?>"></i>
            `);

            // Add copy handler
            $container.find('.copy-url-btn').click(function() {
                const $btn = $(this);
                const url = $btn.data('url');
                
                // Create temporary input
                const $temp = $("<input>");
                $("body").append($temp);
                $temp.val(url).select();
                
                try {
                    // Copy to clipboard
                    document.execCommand("copy");
                    // Show success message
                    alert_float('success', '<?php echo _l('url_copied_to_clipboard'); ?>');
                } catch (e) {
                    console.error('Copy failed:', e);
                    alert_float('danger', '<?php echo _l('failed_to_copy_url'); ?>');
                } finally {
                    $temp.remove();
                }
            });
        } else {
            // Show action buttons for non-downloaded images
            $container.html(`
                <div class="btn-group btn-group-xs">
                    <button type="button" 
                            class="btn btn-info find-similar-btn" 
                            data-url="${imageUrl}"
                            title="<?php echo _l('find_similar_images'); ?>">
                        <i class="fa fa-search"></i>
                    </button>
                    <button type="button" 
                            class="btn btn-success download-image-btn"
                            data-url="${imageUrl}"
                            title="<?php echo _l('download_to_server'); ?>">
                        <i class="fa fa-download"></i>
                    </button>
                </div>
            `);

            // Add click handlers
            $container.find('.find-similar-btn').click(function() {
                if (!$(this).prop('disabled')) {
                    findSimilarImages(imageUrl);
                }
            });

            $container.find('.download-image-btn').click(function() {
                if (!$(this).prop('disabled')) {
                    const $btn = $(this);
                    const imageUrl = $btn.data('url');
                    window.TopicComposer.handlers.downloadImageToServer(imageUrl, $btn);
                }
            });
        }
    });
}

// Placeholder functions for actions
function findSimilarImages(imageUrl) {
    console.log('Find similar images for:', imageUrl);
    // Show notification that this feature is coming soon
    alert_float('info', '<?php echo _l("similar_images_feature_coming_soon"); ?> ' + imageUrl);
    
    // Alternatively, open Google Images search in a new tab
    if (confirm('<?php echo _l("open_google_image_search"); ?>')) {
        const googleSearchUrl = 'https://www.google.com/searchbyimage?image_url=' + encodeURIComponent(imageUrl);
        window.open(googleSearchUrl, '_blank');
    }
}

/**
 * Parse and display keywords
 */
function parseAndDisplayKeywords(keywords) {
    if (!keywords) return '';
    return keywords.split(',')
        .map(kw => kw.trim())
        .filter(kw => kw)
        .map(kw => `<span class="badge bg-info">${htmlEntityDecode(kw)}</span>`)
        .join(' ');
}

/**
 * Proceed to step 2
 */
function proceedToStep2() {
    $('#topic-composer-modal').modal('hide');

    // Continue with step 2 processing...
}

// Helper functions
function generateContentSections(data) {
    let sections = [];
    if (data.Item_Position && data.Item_Title && data.Item_Content) {
        sections.push({
            position: data.Item_Position,
            title: data.Item_Title,
            content: data.Item_Content
        });
    }
    
    return sections.map((section, index) => `
        <div class="content-section" data-position="${section.position}">
            <div class="section-header">
                <span class="drag-handle"><i class="fa fa-bars"></i></span>
                <input type="text" class="form-control section-title" 
                       value="${htmlEntityDecode(section.title)}"
                       placeholder="<?php echo _l('section_title'); ?>">
                <div class="section-actions">
                    <button type="button" class="btn btn-xs btn-danger" onclick="removeSection(this)">
                        <i class="fa fa-trash"></i>
                    </button>
                </div>
            </div>
            <div class="section-body">
                <div class="editor-container">${htmlEntityDecode(section.content)}</div>
            </div>
        </div>
    `).join('');
}

function generateImageGallery(data) {
    return parseAndDisplayImages(data);
}

function generateKeywordOptions(keywords) {
    if (!keywords) return '';
    return keywords.split(',')
        .map(kw => kw.trim())
        .filter(kw => kw)
        .map(kw => `<option value="${kw}">${htmlEntityDecode(kw)}</option>`)
        .join('');
}

function previewContent() {
    // Implementation of previewContent function
}

function cancelEditing() {
    // Implementation of cancelEditing function
}

function saveContent() {
    // Implementation of saveContent function
}

function updateSectionPositions() {
    // Implementation of updateSectionPositions function
}

function addNewSection() {
    // Implementation of addNewSection function
}

function uploadNewImage() {
    // Implementation of uploadNewImage function
}

// Helper function to decode HTML entities
function decodeHtmlContent(content) {
    const textarea = document.createElement('textarea');
    textarea.innerHTML = content;
    return textarea.value;
}

// Cập nhật hàm xử lý xóa item
function handleDeleteItem(index) {
    const item = window.TopicComposer.items[index];
    const order = item['web-scraper-order'];
    
    if (order) {
        // Thêm order vào Set các order đã xóa
        window.TopicComposer.submissionData.deletedOrders.add(order);
        
        // Lưu thông tin item bị xóa
        window.TopicComposer.submissionData.deletedItems.push({
            'web-scraper-order': order,
            item: item
        });
    }
    
    // Xóa item khỏi danh sách
    window.TopicComposer.items.splice(index, 1);
    refreshItemsList();
}


<?php 
    ob_start();
    $this->load->view('includes/displayTopicComposerResult/topic_detail_action_buttons_display_script_displayTopicComposerResult_1');
    $topic_detail_action_buttons_display_script_displayTopicComposerResult_1 = ob_get_clean();
    // Loại bỏ thẻ <script> và </script>
    $topic_detail_action_buttons_display_script_displayTopicComposerResult_1 = str_replace(['<script>', '</script>'], '', $topic_detail_action_buttons_display_script_displayTopicComposerResult_1);
    echo $topic_detail_action_buttons_display_script_displayTopicComposerResult_1;
    ?>  

<?php 
    ob_start();
    $this->load->view('includes/displayTopicComposerResult/topic_detail_action_buttons_display_script_scriptHandlers');
    $topic_detail_action_buttons_display_script_scriptHandlers = ob_get_clean();
    // Loại bỏ thẻ <script> và </script>
    $topic_detail_action_buttons_display_script_scriptHandlers = str_replace(['<script>', '</script>'], '', $topic_detail_action_buttons_display_script_scriptHandlers);
    echo $topic_detail_action_buttons_display_script_scriptHandlers;
    ?>  
<?php 
    ob_start();
    $this->load->view('includes/displayTopicComposerResult/topic_detail_action_buttons_display_script_displayTopicComposerResult_batchTitleGenerator');
    $topic_detail_action_buttons_display_script_displayTopicComposerResult_batchTitleGenerator = ob_get_clean();
    // Loại bỏ thẻ <script> và </script>
    $topic_detail_action_buttons_display_script_displayTopicComposerResult_batchTitleGenerator = str_replace(['<script>', '</script>'], '', $topic_detail_action_buttons_display_script_displayTopicComposerResult_batchTitleGenerator);
    echo $topic_detail_action_buttons_display_script_displayTopicComposerResult_batchTitleGenerator;
    ?>  

/**
 * Check image external data
 */
function checkImageExternalData(imageUrl) {
    
    if (!imageUrl) {
        return { exists: false };
    }

    try {
        // Generate MD5 hash
        const rel_id = window.TopicComposer.handlers.md5(imageUrl);
        console.log('checkImageExternalData', imageUrl, rel_id);
        // Return promise from ajax call
        return new Promise((resolve) => {
            $.ajax({
                url: admin_url + 'topics/check_image_external_data',
                type: 'POST',
                data: {
                    topic_master_id: topicMasterId,
                    rel_id: rel_id,
                    rel_type: 'image'
                },
                success: function(response) {
                    try {
                        if (typeof response === 'string') {
                            response = JSON.parse(response);
                        }
                        if (response.exists && response.rel_data) {
                            resolve({
                                exists: true,
                                rel_data: response.rel_data
                            });
            } else {
                            resolve({ exists: false });
                        }
                    } catch (e) {
                        console.error('Error parsing response:', e);
                        resolve({ exists: false });
                    }
                },
                error: function() {
                    resolve({ exists: false });
                }
            });
        });
    } catch (e) {
        console.error('Error in checkImageExternalData:', e);
        return { exists: false };
    }
}

/**
 * Check and update image actions
 */
async function checkAndUpdateImageActions(imageUrl, containerId, data) {
    const $container = $(`#${containerId}`);
    if (!$container.length) return;

    try {
        console.log("checkAndUpdateImageActions 2887");
        const externalData = await checkImageExternalData(imageUrl);
        console.log('checkAndUpdateImageActions', imageUrl, externalData);
        if (externalData.exists) {
            const newImageUrl = externalData.rel_data;
            // Update image source if WordPress URL exists
            const $img = $container.closest('.image-item').find('img');
            
            // Update image src and attributes
            $img.attr({
                'src': newImageUrl,
                'data-original-url': imageUrl,
                'onclick': `openImagePreview('${newImageUrl}')`
            });
            
            // Update caption
            $container.siblings('.caption').html(`
                <div class="text-left">
                    <small class="text-muted">(<?php echo _l('image_downloaded_from_server'); ?>)</small><br>
                    ${imageUrl.split('/').pop()}
                </div>
            `);
            
            // Update action buttons
            $container.html(`
                <div class="btn-group btn-group-xs">
                    <button type="button" 
                            class="btn btn-info find-similar-btn" 
                            data-url="${imageUrl}" 
                            disabled
                            title="<?php echo _l('find_similar_images'); ?>">
                        <i class="fa fa-search"></i>
                    </button>
                    <button type="button" 
                            class="btn btn-success download-image-btn"
                            data-url="${imageUrl}" 
                            disabled
                            title="<?php echo _l('download_to_server'); ?>">
                        <i class="fa fa-download"></i>
                    </button>
                    <button type="button"
                            class="btn btn-default copy-url-btn"
                            data-url="${newImageUrl}"
                            title="<?php echo _l('copy_image_url'); ?>">
                        <i class="fa fa-copy"></i>
                    </button>
                </div>
                <i class="fa fa-check text-success ml-2" 
                   title="<?php echo _l('image_already_downloaded'); ?>"></i>
            `);

            // Add copy handler
            $container.find('.copy-url-btn').click(function() {
                const $btn = $(this);
                const url = $btn.data('url');
              
                // Create temporary input
                const $temp = $("<input>");
                $("body").append($temp);
                $temp.val(url).select();
                
                try {
                    console.log('copy-url-btn clicked',$temp.val(url).select() );
                    // Copy to clipboard
                    document.execCommand("copy");
                    // Show success message
                    alert_float('success', '<?php echo _l('url_copied_to_clipboard'); ?>');
                } catch (e) {
                    console.error('Copy failed:', e);
                    alert_float('danger', '<?php echo _l('failed_to_copy_url'); ?>');
                } finally {
                    $temp.remove();
                }
            });
        } else {
            // Show action buttons for non-downloaded images
            $container.html(`
                <div class="btn-group btn-group-xs">
                    <button type="button" 
                            class="btn btn-info find-similar-btn" 
                            data-url="${imageUrl}"
                            title="<?php echo _l('find_similar_images'); ?>">
                        <i class="fa fa-search"></i>
                    </button>
                    <button type="button" 
                            class="btn btn-success download-image-btn"
                            data-url="${imageUrl}"
                            title="<?php echo _l('download_to_server'); ?>">
                        <i class="fa fa-download"></i>
                    </button>
                </div>
            `);

            // Add click handlers
            $container.find('.find-similar-btn').click(function() {
                if (!$(this).prop('disabled')) {
                    findSimilarImages(imageUrl);
                }
    });

            $container.find('.download-image-btn').click(function() {
                if (!$(this).prop('disabled')) {
                    const $btn = $(this);
                    const imageUrl = $btn.data('url');
                    window.TopicComposer.handlers.downloadImageToServer(imageUrl, $btn);
                }
            });
        }
    } catch (e) {
        console.error('Error in checkAndUpdateImageActions:', e);
        $container.html(`
            <div class="text-danger">
                <i class="fa fa-exclamation-circle"></i>
                <span class="ml-1"><?php echo _l('error_checking_image'); ?></span>
                        </div>
        `);
    }
}

/**
 * Parse and display images
 */
function parseAndDisplayImages(data) {
    try {
        let images = [];
        if (data.item_Pictures) {
            const parsed = JSON.parse(data.item_Pictures);
            images = parsed.map(img => img['item_Pictures-src']);
        }

        // Return early if no images
        if (!images.length) {
            return '<div class="alert alert-info"><?php echo _l("no_image_data"); ?></div>';
        }

        // Generate initial HTML
        let html = '<div class="row">';
        images.forEach(img => {
            const imageId = 'img-' + Math.random().toString(36).substr(2, 9);
            html += `
                <div class="col-md-6">
                                        <div class="image-item">
                        <div class="image-preview">
                            <img src="${img}" 
                                 class="img-responsive" 
                                 onclick="openImagePreview('${img}')"
                                 alt="">
                                            </div>
                        <div class="image-info">
                            <div class="image-actions" id="${imageId}">
                                <div class="loading-actions">
                                    <i class="fa fa-spinner fa-spin"></i>
                                        </div>
                                    </div>
                            <div class="caption text-center">
                                ${img.split('/').pop()}
                        </div>
                    </div>
                </div>
            </div>
        `;

            // Check external data after render
            setTimeout(() => {
                checkAndUpdateImageActions(img, imageId, data);
            }, 0);
        });
        html += '</div>';

        return html;

    } catch (e) {
        console.error('Error parsing images:', e);
        return '';
    }
}

/**
 * Update image badge for a specific item
 * @param {Number} itemIndex - Index of the item to update
 */
async function updateItemImageBadge(itemIndex) {
    const item = window.TopicComposer.items[itemIndex];
    if (!item) return;
    
    // Get all images from the item
    const allImages = extractImagesFromContent(item);
    if (allImages.length === 0) return;
    
    // Count downloaded and undownloaded images
    let downloadedCount = 0;
    let undownloadedCount = 0;
    
    // Use Promise.all to check all images concurrently
    const imageResults = await Promise.all(allImages.map(imgUrl => isImageDownloaded(imgUrl)));
    
    // Count downloaded and undownloaded images
    imageResults.forEach(result => {
        if (result.exists) {
            downloadedCount++;
        } else {
            undownloadedCount++;
        }
    });
    
    // Find and update the badge
    const $item = $(`.sortable-items .list-group-item[data-index="${itemIndex}"]`);
    if (!$item.length) return;
    
    const $badge = $item.find('.images-status-badge');
    
    // If badge exists, update it
    if ($badge.length) {
        // Update count
        $badge.find('.count').text(`${downloadedCount}/${allImages.length}`);
        
        // Update icon class
        const $icon = $badge.find('.fa-image');
        $icon.removeClass('text-success text-warning text-muted');
        
        if (downloadedCount === allImages.length) {
            $icon.addClass('text-success');
        } else if (undownloadedCount > 0) {
            $icon.addClass('text-warning');
        } else {
            $icon.addClass('text-muted');
        }
        
        // Update tooltip
        $badge.attr('title', `<?php echo _l('images_status'); ?>: ${downloadedCount}/${allImages.length} <?php echo _l('downloaded'); ?>`);
    } else {
        // Create new badge if it doesn't exist
        const badgeHTML = `
            <span class="images-status-badge" 
                    title="<?php echo _l('images_status'); ?>: ${downloadedCount}/${allImages.length} <?php echo _l('downloaded'); ?>">
                <i class="fa fa-image ${downloadedCount === allImages.length ? 'text-success' : undownloadedCount > 0 ? 'text-warning' : 'text-muted'}"></i>
                <span class="count">${downloadedCount}/${allImages.length}</span>
            </span>
        `;
        
        // Append to item title
        $item.find('.item-title').append(badgeHTML);
    }
}

/**
 * Update image badges for the item containing a specific image
 * @param {String} imageUrl - URL of the image that was just downloaded
 */
async function updateImageBadgesForItem(imageUrl) {
    // Skip if no items available
    if (!window.TopicComposer || !window.TopicComposer.items || !window.TopicComposer.items.length) {
        return;
    }
    
    // Find the index of the item containing this image
    let targetItemIndex = -1;
    
    window.TopicComposer.items.forEach((item, index) => {
        const itemImages = extractImagesFromContent(item);
        if (itemImages.includes(imageUrl)) {
            targetItemIndex = index;
        }
    });
    
    // Update badge if we found the item
    if (targetItemIndex !== -1) {
        await updateItemImageBadge(targetItemIndex);
    } else {
        // If we can't find the exact item, refresh all items
        await refreshItemsList().catch(err => console.error('Error refreshing items list:', err));
    }
}
</script>
