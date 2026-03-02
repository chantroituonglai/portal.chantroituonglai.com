/**
 * Ultimate Editor - Publish Functions
 * 
 * File này chứa tất cả các chức năng liên quan đến quy trình xuất bản nội dung
 * từ Ultimate Editor lên các nền tảng bên ngoài thông qua Topic Controller.
 * 
 * @package TopicController
 * @subpackage UltimateEditor
 */

// Namespace cho publish module
var UltimateEditorPublish = {};

/**
 * Khởi tạo module xuất bản
 */
UltimateEditorPublish.init = function() {
    'use strict';
    
    console.log('Initializing Publish module...');
    
    // Lưu trữ dữ liệu
    this.data = {
        selectedController: null,
        categories: [],
        tags: [],
        draft: null
    };
    
    // Khởi tạo UI components
    this.initUI();
    
    // Đăng ký các sự kiện
    this.bindEvents();
    
    // Đồng bộ tags từ container vào modal
    this.syncTagsFromContainer();

    // Vô hiệu hóa Publish Options, Categories Panel và Tags Panel khi khởi tạo
    this.disablePublishOptions();
    this.disableCategoriesPanel();
    this.disableTagsPanel();
    
    return this;
};

/**
 * Khởi tạo UI cho tab publish
 */
UltimateEditorPublish.initUI = function() {
    // Khởi tạo select2 cho tags
    if ($.fn.select2) {
        $('#tags-select').select2({
            tags: true,
            placeholder: app.lang.select_or_add_tags || 'Select or type to add tags',
            allowClear: true,
            multiple: true,
            tokenSeparators: [',', ' '],
            width: '100%'
        });
    }
    
    // Khởi tạo datepicker cho lịch xuất bản
    if ($.fn.datetimepicker) {
        $('#schedule-time').datetimepicker({
            format: 'Y-m-d H:i:s',
            step: 30,
            minDate: 0,
            defaultTime: '12:00'
        });
    }
    
    // Ẩn Publish Options cho đến khi controller được chọn
    this.disablePublishOptions();
    
    // Tải dữ liệu ban đầu
    this.loadControllers();
   
    return this;
};

/**
 * Đăng ký các sự kiện
 */
UltimateEditorPublish.bindEvents = function() {
    // Topic Controller change event
    $('#topic-controller-select').on('change', function() {
        const controllerId = $(this).val();
        if (controllerId) {
            UltimateEditorPublish.controllerSelected(controllerId);
        } else {
            UltimateEditorPublish.clearControllerData();
        }
    });
    
    // Post status change event
    $('#post-status').on('change', function() {
        const status = $(this).val();
        if (status === 'schedule') {
            $('.schedule-time-group').removeClass('hide');
        } else {
            $('.schedule-time-group').addClass('hide');
        }
    });
    
    // Featured image selection
    $('#select-feature-image').on('click', function(e) {
        e.preventDefault();
        UltimateEditorPublish.openMediaLibrary();
    });
    
    // Remove featured image
    $('#remove-feature-image').on('click', function(e) {
        e.preventDefault();
        UltimateEditorPublish.removeFeaturedImage();
    });
    
    // Khi tab publish được chọn
    $('a[href="#tab_publish"]').on('shown.bs.tab', function() {
        UltimateEditorPublish.refreshData();
    });
    
    // Khi tiêu đề thay đổi, cập nhật permalink slug
    $('#draft-title').on('input', function() {
        UltimateEditorPublish.updateSlugFromTitle($(this).val());
    }); 

    
    return this;
};

/**
 * Tải danh sách controllers
 */
UltimateEditorPublish.loadControllers = function() {
    // Hiển thị loading
    $('#topic-controller-select').html('<option value="">' + app.lang.loading + '...</option>');
    
    // Gọi API lấy danh sách controllers
    $.ajax({
        url: admin_url + 'topics/ultimate_editor/get_topic_controllers',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                UltimateEditorPublish.renderControllers(response.data);
            } else {
                alert_float('danger', response.message || app.lang.error_loading_controllers);
            }
        },
        error: function(xhr) {
            alert_float('danger', app.lang.error_loading_controllers);
            console.error('Error loading controllers:', xhr);
        }
    });
    
    return this;
};

/**
 * Hiển thị danh sách controllers
 */
UltimateEditorPublish.renderControllers = function(controllers) {
    const $select = $('#topic-controller-select');
    
    // Reset và thêm option mặc định
    $select.html('<option value="">' + app.lang.select_topic_controller + '</option>');
    
    // Thêm các controllers vào dropdown
    if (controllers && controllers.length) {
        controllers.forEach(function(controller) {
            const $option = $('<option></option>')
                .val(controller.id)
                .text(controller.name)
                .data('platform', controller.platform)
                .data('connected', controller.connected ? 'true' : 'false');
                
            $select.append($option);
        });
        
        // Kiểm tra xem có controller đã chọn trong draft hay không
        this.restoreSelectedController();
    } else {
        // Hiển thị thông báo nếu không có controller
        $select.html('<option value="">' + app.lang.no_controllers_available + '</option>');
    }
    
    return this;
};

/**
 * Xử lý khi controller được chọn
 */
UltimateEditorPublish.controllerSelected = function(controllerId) {
    // Lưu controller ID
    this.data.selectedController = controllerId;
    
    // Hiển thị thông tin controller
    const $selected = $('#topic-controller-select option:selected');
    $('#platform-name').text($selected.data('platform') || '');
    $('#controller-info').removeClass('hide');
    
    // Tải danh mục từ controller
    this.loadCategories(controllerId);
    
    
    // Cập nhật permalink prefix
    const platform = $selected.data('platform');
    this.updatePermalinkPrefix(platform);
    
    // Bật Publish Options và Categories Panel
    this.enablePublishOptions();
    this.enableCategoriesPanel();
    this.enableTagsPanel();
    
    // Cập nhật slug từ tiêu đề
    this.updateSlugFromTitle($('#draft-title').val());
    
    // Tải danh sách action buttons cho controller
    this.loadControllerActionButtons(controllerId);
    
    return this;
};

/**
 * Xử lý so sánh tags UI với tags từ platform
 * @param {Array} platformTags - Danh sách tags từ platform
 */
UltimateEditorPublish.processTagComparison = function(platformTags) {
    // Lấy tất cả tags từ container trong UI
    const $tagItems = $('#tags-container .tag-item');
    
    // Tạo map cho việc tìm kiếm nhanh
    const platformTagsMap = {};
    platformTags.forEach(tag => {
        if (tag && typeof tag === 'object' && tag.name && typeof tag.name === 'string') {
            platformTagsMap[tag.name.toLowerCase()] = tag;
        } else {
            console.warn('Skipping invalid platform tag object:', tag);
        }
    });
    
    // Duyệt qua từng tag trong UI và cập nhật hiển thị
    $tagItems.each(function() {
        const $tagItem = $(this);
        const tagText = $tagItem.data('value').toLowerCase();
        
        // Xóa các lớp indicator đã tồn tại
        $tagItem.removeClass('new-tag existing-tag');
        
        // Kiểm tra tag có tồn tại trong platform không
        if (platformTagsMap[tagText]) {
            // Tag đã tồn tại - thêm chỉ báo màu xanh
            $tagItem.addClass('existing-tag');
            $tagItem.find('.tag-indicator').remove(); // Xóa chỉ báo hiện tại
            $tagItem.append('<span class="tag-indicator" style="color: green;"><i class="fa fa-check"></i></span>');
        } else {
            // Tag mới - thêm chỉ báo màu cam
            $tagItem.addClass('new-tag');
            $tagItem.find('.tag-indicator').remove(); // Xóa chỉ báo hiện tại
            $tagItem.append('<span class="tag-indicator" style="color: orange;"><i class="fa fa-plus"></i></span>');
        }
    });
    
    console.log('Tags comparison completed');
    
    return this;
};

/**
 * Tải danh mục từ controller
 */
UltimateEditorPublish.loadCategories = function(controllerId) {
    // Hiển thị loading
    $('#categories-container').html('<div class="text-center"><i class="fa fa-spinner fa-spin"></i> ' + app.lang.loading_categories + '...</div>');
    
    // Gọi API lấy danh mục
    $.ajax({
        url: admin_url + 'topics/ultimate_editor/get_platform_categories/' + controllerId,
        type: 'GET',
        responseType: 'json',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                UltimateEditorPublish.renderCategories(response.categories);
            } else {
                $('#categories-container').html('<div class="alert alert-warning">' + (response.message || app.lang.error_loading_categories) + '</div>');
            }
        },
        error: function() {
            $('#categories-container').html('<div class="alert alert-danger">' + app.lang.error_loading_categories + '</div>');
        }
    });
    
    return this;
};

/**
 * Tải thông tin permalink prefix
 * @param {number} controllerId - ID của controller
 */
UltimateEditorPublish.loadPermalinkInfo = function(controllerId) {
    if (!controllerId) return this;
    
    // Lấy thông tin platform từ controller
    const platform = this.data.selectedController.platform;
    
    // Cập nhật permalink prefix
    this.updatePermalinkPrefix(platform);
    
    // Cập nhật slug từ tiêu đề
    this.updateSlugFromTitle($('#draft-title').val());
    
    return this;
};

/**
 * Hiển thị tags phổ biến
 * @param {Array} tags - Danh sách tags
 */
UltimateEditorPublish.renderPopularTags = function(tags) {
    console.log('Rendering popular tags:', tags);
    if (!tags || !tags.length) {
        $('#popular-tags-list').html('<div class="text-muted">' + app.lang.no_tags_found + '</div>');
        return this;
    }
    
    // Sort tags by popularity if count is available
    if (tags[0] && typeof tags[0] === 'object' && tags[0].hasOwnProperty('count')) {
        tags.sort(function (a, b) {
            return (b.count || 0) - (a.count || 0);
        });
    }
    
    // Take only the top 10 tags
    var displayTags = tags.slice(0, 10);
    
    // Make sure container is empty
    $('#popular-tags-list').empty();
    
    // Create tags wrapper
    var $tagsWrapper = $('<div class="popular-tags-wrapper"></div>');
    
    // Add each tag as a clickable item
    displayTags.forEach(function(tag) {
        // Get tag name, ensuring entities are decoded
        let tagName = tag.name || (typeof tag === 'string' ? tag : '');
        
        // Decode HTML entities first
        if (typeof decodeHtmlEntities === 'function') {
            tagName = decodeHtmlEntities(tagName);
        } else {
            // Simple decode for common entities if decodeHtmlEntities isn't available
            tagName = tagName.replace(/&quot;/g, '"')
                             .replace(/&lt;/g, '<')
                             .replace(/&gt;/g, '>')
                             .replace(/&amp;/g, '&'); // Ensure &amp; is last or handled carefully
        }

        // BEGIN MODIFICATION: Strip HTML tags to get plain text
        // Remove any HTML tags from the tag name to prevent format issues
        tagName = tagName.replace(/<[^>]*>/g, '').trim();
        // END MODIFICATION
        
        const tagCount = tag.count || 0;
        
        const $tag = $('<span class="popular-tag"></span>')
            .attr('data-tag', tagName)
            .text(tagName);
            
        if (tagCount) {
            // $tag.append('<span class="tag-count">' + tagCount + '</span>');
        }
        
        $tagsWrapper.append($tag);
    });
    
    $('#popular-tags-list').html($tagsWrapper);
    
    // Đăng ký sự kiện cho popular tags
    $('#popular-tags-list').off('click', '.popular-tag');
    $('#popular-tags-list').on('click', '.popular-tag', function() {
        const tag = $(this).data('tag');
        UltimateEditorPublish.addVisualTag(tag);
        
        // Add visual feedback
        $(this).addClass('tag-added');
        setTimeout(() => {
            $(this).removeClass('tag-added');
        }, 800);
    });
    
    console.log('Top 10 tags to display:', displayTags);
    
    return this;
};

/**
 * Tải các action buttons cho controller
 * @param {number} controllerId - ID của controller
 */
UltimateEditorPublish.loadControllerActionButtons = function(controllerId) {
    if (!controllerId) return;
    
    // Hiển thị loading
    $('#action-buttons-container').html('<div class="text-center mt-2"><i class="fa fa-spinner fa-spin"></i> ' + app.lang.loading + '...</div>');
    
    // Gọi API để lấy action buttons
    $.ajax({
        url: admin_url + 'topics/ultimate_editor/get_controller_action_buttons/' + controllerId,
        type: 'GET',
        responseType: 'json',
        dataType: 'json',
        success: (response) => {
            console.log(response);
            if (response.success) {
                this.renderActionButtons(response.buttons);
        } else {
                $('#action-buttons-container').html('<div class="alert alert-warning">' + (response.message || app.lang.no_action_buttons_found) + '</div>');
            }
        },
        error: (xhr) => {
            $('#action-buttons-container').html('<div class="alert alert-danger">' + app.lang.error_loading_action_buttons + '</div>');
            console.error('Error loading action buttons:', xhr);
        }
    });
};

/**
 * Hiển thị các action buttons
 * @param {Array} buttons - Danh sách action buttons
 */
UltimateEditorPublish.renderActionButtons = function(buttons) {
    const $container = $('#action-buttons-container');
    
    // Xóa nội dung hiện tại
    $container.empty();
    
    // Nếu không có buttons, hiển thị thông báo
    if (!buttons || !buttons.length) {
        $container.html('<div class="alert alert-info">' + (app.lang.no_action_buttons_available || 'No action buttons available for this controller') + '</div>');
        return;
    }
    
    // Tạo các nhóm button theo target_type
    const buttonGroups = {};
    
    // Nhóm các buttons theo target_type
    buttons.forEach(button => {
        if (!button.active) return; // Bỏ qua các button không hoạt động
        
        const groupKey = button.target_type || 'Other';
        if (!buttonGroups[groupKey]) {
            buttonGroups[groupKey] = [];
        }
        buttonGroups[groupKey].push(button);
    });
    
    // Hiển thị từng nhóm
    Object.keys(buttonGroups).forEach(groupKey => {
        console.log(groupKey);
        const groupButtons = buttonGroups[groupKey];
        
        // Tạo card cho nhóm
        const $card = $('<div class="card"></div>');
        const $cardHeader = $('<div class="card-header d-flex justify-content-between align-items-center"></div>');
        const $cardBody = $('<div class="card-body"></div>');
        
        // Đặt tiêu đề nhóm
        $cardHeader.html('<h5>' + formatActionTypeName(groupKey) + ' Actions</h5>');
        $card.append($cardHeader);
        
        // Tạo container cho các buttons
        const $buttonsRow = $('<div class="row"></div>');
        
        // Thêm từng button vào container
        groupButtons.forEach(button => {
            const $buttonCol = $('<div class="col-md-6 mb-2"></div>');
            
            // Tạo button HTML với đầy đủ data attributes
            const $button = $('<button></button>')
                .addClass('btn btn-block ultimate-editor-action-button')
                .addClass(button.class || 'btn-primary')
                .attr('id', 'action-button-' + button.id)
                .attr('type', 'button')
                .attr('data-button-id', button.id)
                .attr('data-target-type', button.target_type)
                .attr('data-target-state', button.target_state)
                .attr('data-action-command', button.action_command)
                .attr('data-workflow-id', button.workflow_id)
                .attr('data-controller-id', button.controller_id)
                .attr('data-toggle', 'tooltip')
                .attr('title', button.description || button.display_name);
            
            // Thêm icon nếu có
            if (button.icon) {
                $button.append('<i class="' + button.icon + ' mr-1"></i> ');
            }
            
            // Thêm text cho button
            $button.append(button.display_name || button.name);
            
            // Thêm button vào column
            $buttonCol.append($button);
            $buttonsRow.append($buttonCol);
        });
        
        // Thêm các buttons vào card body
        $cardBody.append($buttonsRow);
        $card.append($cardBody);
        
        // Thêm card vào container
        $container.append($card);
    });
    
    // Khởi tạo tooltips
    $('[data-toggle="tooltip"]').tooltip();
    
    // Đăng ký sự kiện cho các buttons
    this.bindActionButtonEvents('#action-buttons-container');
    
    // Hàm định dạng tên nhóm action
    function formatActionTypeName(typeName) {
        if (!typeName) return 'Other';
        
        // Xử lý chuỗi target_type thành tên hiển thị đẹp hơn
        // Ví dụ: 'WordPressPost' -> 'WordPress Post'
        return typeName.replace(/([A-Z])/g, ' $1').trim();
    }
};

/**
 * Bind events to action buttons
 * @param {string} containerSelector - Optional selector for container element
 */
UltimateEditorPublish.bindActionButtonEvents = function(containerSelector) {
    // Default container selector if not provided
    containerSelector = containerSelector || '#action-buttons-container';
    
    // Remove existing handlers to prevent duplicates
    $(document).off('click', containerSelector + ' .ultimate-editor-action-button');
    
    // Bind click event handler to all action buttons
    $(document).on('click', containerSelector + ' .ultimate-editor-action-button', function(e) {
        e.preventDefault();
        
        // If UltimateEditorPublish.Action is available, use its handler
        if (UltimateEditorPublish.Action && typeof UltimateEditorPublish.Action.handleButtonClick === 'function') {
            console.log('bindActionButtonEvents|UltimateEditorPublish.Action.handleButtonClick is available');
            UltimateEditorPublish.Action.handleButtonClick($(this));
        } else {
            console.error('UltimateEditorPublish.Action.handleButtonClick is not available');
            alert_float('warning', app.lang.action_handling_not_available || 'Action handling is not available');
        }
    });
};

/**
 * Thực thi action command
 * @param {number} actionId - ID của action
 * @param {string} actionCommand - Command cần thực thi
 * @param {jQuery} $button - Button element
 */
UltimateEditorPublish.executeActionCommand = function(actionId, actionCommand, $button) {
    if (!actionId || !actionCommand) return;
    
    // Check if we should use the new Action module
    if (UltimateEditorPublish.Action && typeof UltimateEditorPublish.Action.processGenericAction === 'function') {
        // Create action data structure for the new processor
        const actionData = {
            buttonId: actionId,
            actionCommand: actionCommand,
            controllerId: this.data.selectedController
        };
        
        // Get topic ID
        const topicId = $('#topic_id').val();
        
        // Process using the Action module
        UltimateEditorPublish.Action.processGenericAction(topicId, actionData, $button);
        return;
    }
};

/**
 * Tải và hiển thị các thẻ từ dữ liệu draft
 */
UltimateEditorPublish.loadDraftTagsToPopularTags = function() {
    console.log('Loading draft tags to popular tags section');
    
    // Lấy dữ liệu tags từ draft
    const draftTags = this.getDraftTags();
    if (!draftTags || draftTags.length === 0) {
        console.log('No draft tags found');
        return;
    }
    
    console.log('Found draft tags:', draftTags);
    
    // Hiển thị các thẻ từ draft trong phần "Popular Tags"
    const $popularTagsContainer = $('#popular-tags-list');
    
    // Thêm tiêu đề cho phần tags từ draft
    const $draftTagsTitle = $('<div class="draft-tags-title mt-3"></div>').text(app.lang.draft_tags || 'Draft Tags:');
    $popularTagsContainer.append($draftTagsTitle);
    
    // Tạo container cho các draft tags
    const $draftTagsContainer = $('<div class="draft-tags-container"></div>');
    
    // Thêm từng tag vào container
    draftTags.forEach(function(tag) {
        const $tagElement = $('<span class="label label-tag draft-tag"></span>')
            .text(tag)
            .on('click', function() {
                // Thêm tag vào visual container
                UltimateEditorPublish.addVisualTag(tag);
            });
        $draftTagsContainer.append($tagElement);
    });
    
    $popularTagsContainer.append($draftTagsContainer);
    
    // Thêm nút "Add all draft tags" nếu có nhiều hơn 1 tag
    if (draftTags.length > 1) {
        const $addAllBtn = $('<button class="btn btn-xs btn-default mt-2">')
            .html('<i class="fa fa-tags"></i> ' + (app.lang.add_all_draft_tags || 'Add all draft tags'))
            .on('click', function(e) {
                e.preventDefault(); // Ngăn chặn form submit nếu button nằm trong form
                
                console.log('Adding all draft tags to selection:', draftTags);
                
                // Lấy tags hiện tại từ select2
                let currentTags = $('#tags-select').val() || [];
                
                // Convert to array if it's not (just to be safe)
                if (!Array.isArray(currentTags)) {
                    currentTags = [currentTags];
                }
                
                // Lọc các tags mới (chưa có trong selection)
                const newTags = draftTags.filter(tag => !currentTags.includes(tag));
                
                if (newTags.length === 0) {
                    console.log('All draft tags already added to selection');
                    return;
                }
                
                // Thêm tất cả tags mới vào selection
                const updatedTags = [...currentTags, ...newTags];
                console.log('Updated tags:', updatedTags);
                
                const $tagSelect = $('#tags-select');
                if ($tagSelect.length) {
                    // First remove any placeholder/disabled options
                    $tagSelect.find('option[disabled]').remove();
                    
                    // Then add any missing options
                    newTags.forEach(tag => {
                        if ($tagSelect.find('option[value="' + tag + '"]').length === 0) {
                            const newOption = new Option(tag, tag, true, true);
                            $tagSelect.append(newOption);
                        }
                    });
                    
                    // Then set the value and trigger change
                    $tagSelect.val(updatedTags).trigger('change');
                    
                    console.log('Tags updated with values:', updatedTags);
                }
                
                // Hiển thị thông báo
                alert_float('success', newTags.length + ' tags added to selection');
            });
        
        $popularTagsContainer.append($addAllBtn);
    }
    
    return this;
};

/**
 * Lấy danh sách tags từ container
 * @returns {Array} Danh sách các tags
 */
UltimateEditorPublish.getDraftTags = function() {
    // Lấy tags trực tiếp từ container trong DOM
    const tags = [];
    
    // Duyệt qua tất cả các tag-item trong container
    $('#tags-container .tag-item').each(function() {
        // Lấy text từ span.tag-text
        const tagText = $(this).find('.tag-text').text().trim();
        
        // Hoặc lấy từ thuộc tính data-value nếu có
        const dataValue = $(this).data('value');
        
        // Ưu tiên data-value, nếu không có thì dùng text
        const tagValue = dataValue || tagText;
        
        if (tagValue && tagValue.length > 0) {
            tags.push(tagValue);
        }
    });
    
    console.log('Found', tags.length, 'tags in #tags-container:', tags);
    
    // Nếu không tìm thấy tags trong container, thử lấy từ dữ liệu draft (legacy fallback)
    if (tags.length === 0) {
        const fullDraftData = $('#full-draft-data').val() || sessionStorage.getItem('full_draft_data');
        if (fullDraftData) {
    try {
        const parsedDraft = JSON.parse(fullDraftData);
        if (parsedDraft.draft_tags) {
            // Nếu draft_tags là chuỗi, chuyển thành mảng
            if (typeof parsedDraft.draft_tags === 'string') {
                return parsedDraft.draft_tags.split(',').map(tag => tag.trim()).filter(tag => tag !== '');
            }
            // Nếu đã là mảng
            if (Array.isArray(parsedDraft.draft_tags)) {
                return parsedDraft.draft_tags.filter(tag => tag !== '');
            }
        }
    } catch (e) {
        console.error('Error parsing draft data for tags:', e);
            }
        }
    }
    
    return tags;
};

/**
 * Thêm tag mới vào giao diện và lựa chọn trong Select2
 * @param {string} tag - Giá trị tag cần thêm
 */
UltimateEditorPublish.addVisualTag = function(tag) {
    if (!tag) {
        console.warn('Attempted to add empty tag');
        return this;
    }

    // Normalize tag (trim)
    tag = tag.trim();

    if (tag.length === 0) {
        console.warn('Attempted to add empty tag after trimming');
        return this;
    }

    console.log('Attempting to add tag to selection:', tag);

    // Kiểm tra xem select2 element đã tồn tại chưa
    const $tagSelect = $('#tags-select');
    if (!$tagSelect.length) {
        console.warn('Tags select element (#tags-select) not found in DOM');
        return this;
    }

    // Kiểm tra xem Select2 đã được khởi tạo chưa
    if (!$tagSelect.data('select2')) {
        console.warn('Select2 is not initialized on #tags-select');
        // Optionally initialize it here if needed, or ensure it's initialized earlier
        // init_selectpicker(); // Or specific Select2 initialization
        // return this; // Or proceed cautiously
    }

    // Lấy danh sách các tags hiện đang được chọn
    let existingTags = $tagSelect.val() || [];

    // Đảm bảo existingTags luôn là một mảng
    if (!Array.isArray(existingTags)) {
        existingTags = [existingTags];
    }

    // Kiểm tra xem tag đã được chọn chưa
    if (existingTags.includes(tag)) {
        console.log('Tag already selected:', tag);
        return this; // Không cần làm gì thêm nếu tag đã được chọn
    }

    // Thêm tag mới vào danh sách các tags sẽ được chọn
    const updatedTags = [...existingTags, tag];

    try {
        // Kiểm tra xem tag này đã tồn tại dưới dạng <option> chưa
        if ($tagSelect.find('option[value="' + tag + '"]').length === 0) {
            // Nếu chưa tồn tại, tạo một <option> mới và thêm vào <select>
            // Tham số thứ 3 (defaultSelected) và thứ 4 (selected) nên là false ở đây.
            // Việc lựa chọn sẽ được xử lý bởi .val() và .trigger('change')
            const newOption = new Option(tag, tag, false, false);
            $tagSelect.append(newOption);
            console.log('New option created for tag:', tag);
        } else {
            console.log('Option for tag already exists:', tag);
        }

        // Cập nhật giá trị (các tags được chọn) của Select2
        // Điều này sẽ tự động chọn option mới (và giữ lại các option cũ đã chọn)
        $tagSelect.val(updatedTags);

        // Kích hoạt sự kiện 'change' để Select2 cập nhật giao diện người dùng
        $tagSelect.trigger('change.select2'); // Sử dụng namespace .select2 để chắc chắn

        console.log('Tag added and selected successfully:', tag);
        console.log('Current selected tags:', $tagSelect.val());

    } catch (e) {
        console.error('Error adding tag to Select2 selection:', e);
    }

    return this;
};

/**
 * Cập nhật prefix permalink
 */
UltimateEditorPublish.updatePermalinkPrefix = function(platform) {
    let prefix = '';
    
    // Thiết lập prefix dựa vào platform
    switch (platform) {
        case 'wordpress':
            prefix = 'https://your-site.com/';
            break;
        default:
            prefix = '/';
            break;
    }
    
    $('#permalink-prefix').text(prefix);
    
    return this;
};

/**
 * Lấy tags từ container và thêm vào select
 * Gọi hàm này khi mở modal publish để đồng bộ tags
 */
UltimateEditorPublish.syncTagsFromContainer = function() {
    // Lấy tags từ visual container với phương pháp trích xuất mạnh hơn
    const containerTags = [];
    $('#tags-container .tag-item').each(function() {
        // Lấy text từ span.tag-text
        const tagText = $(this).find('.tag-text').text().trim();
        
        // Hoặc lấy từ thuộc tính data-value/data-tag nếu có
        const dataValue = $(this).data('value') || $(this).data('tag');
        
        // Ưu tiên data-value/data-tag, nếu không có thì dùng text
        const tagValue = dataValue || tagText;
        
        if (tagValue && tagValue.length > 0) {
            containerTags.push(tagValue);
        }
    });
    
    console.log('Syncing tags from container to select:', containerTags);
    
    // Lấy select2 instance
    const $tagSelect = $('#tags-select');
    
    if (!$tagSelect.length) {
        console.warn('Tags select element not found in DOM');
        return this;
    }
    
    // Xóa các options hiện tại và khởi tạo lại
    $tagSelect.val(null).empty();
    // Đảm bảo không có placeholder disabled option
    $tagSelect.find('option[disabled]').remove();
    
    // Even if there are no tags, we need to continue to properly initialize the select
    
    // Thêm tags từ container vào select và đánh dấu là đã chọn
    containerTags.forEach(tag => {
        // Kiểm tra xem tag đã tồn tại trong select chưa
        const existingOption = $tagSelect.find('option[value="' + tag + '"]');
        
        if (existingOption.length === 0) {
            // Tạo mới option nếu chưa tồn tại
            const newOption = new Option(tag, tag, true, true);
            $tagSelect.append(newOption);
        } else {
            // Nếu đã tồn tại, đánh dấu là selected
            existingOption.prop('selected', true);
        }
    });
    
    // Kiểm tra và xử lý select2
    if ($tagSelect.data('select2')) {
        // Nếu select2 đã khởi tạo, chỉ trigger change để cập nhật UI
        $tagSelect.trigger('change');
    } else {
        // Nếu select2 chưa khởi tạo, khởi tạo mới
        if ($.fn.select2) {
            try {
                $tagSelect.select2({
                    placeholder: app.lang.select_or_add_tags || 'Select or type to add tags',
                    tags: true,
                    multiple: true,
                    tokenSeparators: [',', ' '],
                    width: '100%',
                    language: {
                        noResults: function() {
                            return app.lang.no_tags_found || 'No tags found';
                        }
                    }
                });
                console.log('Select2 initialized for tags-select');
                
                // Trigger change sau khi khởi tạo
                $tagSelect.trigger('change');
                
                // Thêm sự kiện để bắt các thay đổi từ select2
                $tagSelect.on('select2:select select2:unselect', function(e) {
                    console.log('Select2 selection changed:', $(this).val());
                });
            } catch (e) {
                console.error('Error initializing Select2 for tags:', e);
            }
        } else {
            console.warn('Select2 not available, using standard multi-select');
            $tagSelect.attr('multiple', 'multiple');
        }
    }
    
    return this;
};

/**
 * Xóa dữ liệu controller khi chưa chọn controller
 */
UltimateEditorPublish.clearControllerData = function() {
    // Xóa dữ liệu
    this.data.selectedController = null;
    this.data.categories = [];
    this.data.tags = [];
    
    // Ẩn thông tin controller
    $('#controller-info').addClass('hide');
    
    // Xóa danh mục
    $('#categories-tree').html('<div class="text-muted">' + app.lang.select_controller_to_view_categories + '</div>');
    
    // Xóa tags phổ biến
    $('#popular-tags-list').html('<div class="text-muted">' + app.lang.select_controller_to_view_tags + '</div>');
    
    // Xóa selection
    $('#tags-select').val(null).trigger('change');
    
    // Xóa action buttons
    $('#controller-action-buttons-container').html(
        '<div class="text-center text-muted">' +
        '<p>' + (app.lang.select_controller_to_view_actions || 'Select a controller to view available actions') + '</p>' +
        '</div>'
    );
    
    // Vô hiệu hóa Publish Options và Categories Panel
    this.disablePublishOptions();
    this.disableCategoriesPanel();
    this.disableTagsPanel();
    
    return this;
};

/**
 * Vô hiệu hóa Publish Options khi chưa chọn controller
 */
UltimateEditorPublish.disablePublishOptions = function() {
    // Thêm overlay và thông báo cho publish options
    const $publishOptions = $('[data-panel="publish-options"]');
    
    if (!$publishOptions.find('.publish-options-overlay').length) {
        $publishOptions.addClass('disabled-panel');
        $publishOptions.find('.panel-body').css('position', 'relative');
        
        const $overlay = $('<div class="publish-options-overlay"></div>').css({
            position: 'absolute',
            top: 0,
            left: 0,
            right: 0,
            bottom: 0,
            background: 'rgba(255, 255, 255, 0.8)',
            zIndex: 10,
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            textAlign: 'center',
            padding: '20px'
        });
        
        const $message = $('<div class="text-muted"></div>').html('<i class="fa fa-info-circle"></i> ' + app.lang.please_select_topic_controller);
        $overlay.append($message);
        
        $publishOptions.find('.panel-body').append($overlay);
    } else {
        $publishOptions.find('.publish-options-overlay').removeClass('hide');
        $publishOptions.addClass('disabled-panel');
    }
    
    // Vô hiệu hóa các trường nhập liệu
    $publishOptions.find('input, select, textarea').prop('disabled', true);
    
    return this;
};

/**
 * Bật Publish Options khi đã chọn controller
 */
UltimateEditorPublish.enablePublishOptions = function() {
    // Xóa overlay và thông báo
    const $publishOptions = $('[data-panel="publish-options"]');
    $publishOptions.removeClass('disabled-panel');
    $publishOptions.find('.publish-options-overlay').addClass('hide');
    
    // Bật các trường nhập liệu
    $publishOptions.find('input, select, textarea').prop('disabled', false);
    
    return this;
};

/**
 * Bật Categories Panel khi đã chọn controller
 */
UltimateEditorPublish.enableCategoriesPanel = function() {
    // Xóa overlay và thông báo
    const $categoriesPanel = $('[data-panel="categories-panel"]');
    $categoriesPanel.removeClass('disabled-panel');
    $categoriesPanel.find('.categories-panel-overlay').addClass('hide');
    
    // Bật các trường nhập liệu
    $categoriesPanel.find('input, select, textarea, button').prop('disabled', false);
    
    return this;
};

/**
 * Vô hiệu hóa Categories Panel khi chưa chọn controller
 */
UltimateEditorPublish.disableCategoriesPanel = function() {
    // Thêm overlay và thông báo cho categories panel
    const $categoriesPanel = $('[data-panel="categories-panel"]');
    
    if (!$categoriesPanel.find('.categories-panel-overlay').length) {
        $categoriesPanel.addClass('disabled-panel');
        $categoriesPanel.find('.panel-body').css('position', 'relative');
        
        const $overlay = $('<div class="categories-panel-overlay"></div>').css({
            position: 'absolute',
            top: 0,
            left: 0,
            right: 0,
            bottom: 0,
            background: 'rgba(255, 255, 255, 0.8)',
            zIndex: 10,
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            textAlign: 'center',
            padding: '20px'
        });
        
        const $message = $('<div class="text-muted"></div>').html('<i class="fa fa-info-circle"></i> ' + app.lang.please_select_topic_controller);
        $overlay.append($message);
        
        $categoriesPanel.find('.panel-body').append($overlay);
    } else {
        $categoriesPanel.find('.categories-panel-overlay').removeClass('hide');
        $categoriesPanel.addClass('disabled-panel');
    }
    
    // Vô hiệu hóa các trường nhập liệu
    $categoriesPanel.find('input, select, textarea, button').prop('disabled', true);
    
    return this;
};

/**
 * Vô hiệu hóa Tags Panel khi chưa chọn controller
 */
UltimateEditorPublish.disableTagsPanel = function() {
    // Thêm overlay và thông báo cho tags panel
    const $tagsPanel = $('[data-panel="tags-panel"]');
    
    if (!$tagsPanel.find('.tags-panel-overlay').length) {
        $tagsPanel.addClass('disabled-panel');
        $tagsPanel.find('.panel-body').css('position', 'relative');
        
        const $overlay = $('<div class="tags-panel-overlay"></div>').css({
            position: 'absolute',
            top: 0,
            left: 0,
            right: 0,
            bottom: 0,
            background: 'rgba(255, 255, 255, 0.8)',
            zIndex: 10,
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            textAlign: 'center',
            padding: '20px'
        });
        
        const $message = $('<div class="text-muted"></div>').html('<i class="fa fa-info-circle"></i> ' + app.lang.please_select_topic_controller);
        $overlay.append($message);
        
        $tagsPanel.find('.panel-body').append($overlay);
    } else {
        $tagsPanel.find('.tags-panel-overlay').removeClass('hide');
        $tagsPanel.addClass('disabled-panel');
    }
    
    // Vô hiệu hóa các trường nhập liệu
    $tagsPanel.find('input, select, textarea, button').prop('disabled', true);
    
    return this;
};

/**
 * Bật Tags Panel khi đã chọn controller
 */
UltimateEditorPublish.enableTagsPanel = function() {
    const $tagsPanel = $('[data-panel="tags-panel"]');
    $tagsPanel.removeClass('disabled-panel');
    
    if ($tagsPanel.find('.tags-panel-overlay').length) {
        $tagsPanel.find('.tags-panel-overlay').addClass('hide');
    }
    
    // Kích hoạt các trường nhập liệu
    $tagsPanel.find('input, select, textarea, button').prop('disabled', false);
    
    return this;
};

/**
 * Tạo slug từ text
 * @param {string} text - Văn bản cần chuyển thành slug
 * @returns {string} - Slug đã tạo
 */
UltimateEditorPublish.slugify = function(text) {
    if (!text) return '';
    
    // Chuyển sang chữ thường
    let slug = text.toLowerCase();
    
    // Xóa dấu
    slug = slug.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    
    // Thay thế các ký tự đặc biệt bằng dấu gạch ngang
    slug = slug.replace(/[^a-z0-9]+/g, '-');
    
    // Xóa dấu gạch ngang ở đầu và cuối
    slug = slug.replace(/^-+|-+$/g, '');
    
    return slug;
};

/**
 * Cập nhật slug từ tiêu đề
 * @param {string} title - Tiêu đề bản nháp
 */
UltimateEditorPublish.updateSlugFromTitle = function(title) {
    // Chỉ cập nhật khi đã chọn controller
    if (!this.data.selectedController) return;
    
    // Tạo slug từ tiêu đề
    const slug = this.slugify(title);
    
    // Cập nhật trường permalink-slug
    $('#permalink-slug').val(slug);
    
    return this;
};

/**
 * Kiểm tra sự tồn tại của bài viết
 * @param {number} controllerId - ID của controller
 * @param {string} title - Tiêu đề cần kiểm tra
 */
UltimateEditorPublish.checkPostExistence = function(controllerId, title) {
    console.log('Checking if post with title exists:', title);
    
    if (!controllerId || !title) {
        return;
    }
    // Hiển thị thông báo đang kiểm tra
    const $existenceCheck = $('#post-existence-check');
    if ($existenceCheck.length) {
        $existenceCheck.html('<i class="fa fa-spinner fa-spin"></i> ' + (app.lang.checking_existence || 'Checking...'));
        $existenceCheck.removeClass('hide');
    }
    
    // Gọi API kiểm tra sự tồn tại
    $.ajax({
        url: admin_url + 'topics/ultimate_editor/check_post_existence',
        type: 'POST',
        data: {
            controller_id: controllerId,
            title: title,
        },
        dataType: 'json',
        success: function(response) {
            if ($existenceCheck.length) {
                if (response.exists) {
                    $existenceCheck.html('<span class="text-warning"><i class="fa fa-exclamation-triangle"></i> ' + 
                        (app.lang.post_exists || 'Post with similar title exists') + '</span>');
                    
                    // Thêm link xem bài viết nếu có
                    if (response.post_url) {
                        $existenceCheck.append(' <a href="' + response.post_url + '" target="_blank" class="text-info">' + 
                            (app.lang.view_post || 'View') + '</a>');
                    }
                } else {
                    $existenceCheck.html('<span class="text-success"><i class="fa fa-check"></i> ' + 
                        (app.lang.post_title_available || 'Title available') + '</span>');
                    
                    // Tự động ẩn sau 5 giây
                    setTimeout(function() {
                        $existenceCheck.fadeOut();
                    }, 5000);
                }
            }
        },
        error: function() {
            if ($existenceCheck.length) {
                $existenceCheck.html('<span class="text-danger"><i class="fa fa-times"></i> ' + 
                    (app.lang.error_checking_existence || 'Error checking title existence') + '</span>');
            }
        }
    });
    
    return this;
};

/**
 * Lấy dữ liệu xuất bản từ form
 */
UltimateEditorPublish.getPublishData = function() {
    // Lấy dữ liệu draft
    const editorContent = UltimateEditor.getEditorContent();
    const draftContent = UltimateEditor.getDraftContent();
    
    // Lấy danh mục đã chọn
    const selectedCategories = [];
    $('#categories-tree input:checked').each(function() {
        selectedCategories.push($(this).val());
    });
    
    // Dữ liệu xuất bản
    return {
        controller_id: this.data.selectedController,
        title: draftContent.title || $('#draft-title').val(),
        content: editorContent.html || '',
        excerpt: $('#post-excerpt').val(),
        status: $('#post-status').val(),
        schedule_time: $('#schedule-time').val(),
        permalink_slug: $('#permalink-slug').val(),
        categories: selectedCategories,
        tags: $('#tags-select').val(),
        seo_title: $('#seo-title').val(),
        meta_description: $('#meta-description').val(),
        focus_keyword: $('#focus-keyword').val(),
        featured_image_id: $('#featured-image-id').val(),
        draft_id: $('#current-draft-id').val()
    };
};

/**
 * Mở thư viện media để chọn ảnh đại diện
 */
UltimateEditorPublish.openMediaLibrary = function() {
    // Kiểm tra controller đã chọn chưa
    if (!this.data.selectedController) {
        alert_float('warning', app.lang.please_select_topic_controller);
        return;
    }
    
    // Triển khai theo cách tương thích với hệ thống
    // Code thêm ở đây
};

/**
 * Xóa ảnh đại diện
 */
UltimateEditorPublish.removeFeaturedImage = function() {
    $('#feature-image').attr('src', app.site_url + 'modules/topics/assets/img/placeholder-image.jpg');
    $('#featured-image-id').val('');
    $('#remove-feature-image').addClass('hide');
    
    return this;
};

/**
 * Cập nhật dữ liệu khi tab được hiện
 */
UltimateEditorPublish.refreshData = function() {
    // Cập nhật tiêu đề xem trước
    const title = $('#draft-title').val();
    $('#preview-title').text(title);
    
    // Cập nhật nội dung xem trước
    const content = UltimateEditor.getEditorContent();
    $('#preview-content').html(content.html);
    
    // Cập nhật ngày và tác giả
    const currentDate = new Date().toLocaleDateString();
    $('#preview-date').text(currentDate);
    $('#preview-author').text(app.user_fullname);
    
    // Đồng bộ tags từ container vào modal
    this.syncTagsFromContainer();
    
    return this;
};

/**
 * Khôi phục controller đã chọn từ draft
 */
UltimateEditorPublish.restoreSelectedController = function() {
    const fullDraftData = $('#full-draft-data').val() || sessionStorage.getItem('full_draft_data');
    if (!fullDraftData) {
        return;
    }
    
    try {
        const parsedDraft = JSON.parse(fullDraftData);
        if (parsedDraft.controller_id) {
            $('#topic-controller-select').val(parsedDraft.controller_id).trigger('change');
        }
    } catch (e) {
        console.error('Error parsing draft data for controller:', e);
    }
    
    return this;
};


/**
 * Đồng bộ từ select2 sang visual tags
 */
UltimateEditorPublish.syncTagsToVisual = function() {
    // No longer needed - we only use tags-select
    return this;
};

/**
 * Đồng bộ từ visual tags sang select2
 */
UltimateEditorPublish.syncVisualToTags = function() {
    // No longer needed - we only use tags-select
    return this;
};

/**
 * Thêm tag vào container trực quan
 * @param {string} tag - Giá trị tag
 * @param {boolean} isNew - Có phải tag mới không (để thêm animation)
 */
UltimateEditorPublish.addTagToVisualContainer = function(tag, isNew = false) {
    // No longer needed - we only use tags-select
    return this;
};

/**
 * Thêm tag vào tags-select
 * @param {string} tag - Tag cần thêm
 */
UltimateEditorPublish.addTagToSelection = function(tag) {
    // Sử dụng phương thức mới
    return this.addVisualTag(tag);
};

/**
 * Hiển thị danh mục từ controller
 * @param {Array} categories - Danh sách danh mục
 */
UltimateEditorPublish.renderCategories = function(categories) {
    // Lưu lại danh mục cho sử dụng sau này
    this.data.categories = categories || [];
    
    // Hiển thị danh mục
    const $container = $('#categories-container');
    
    // Nếu không có danh mục
    if (!categories || !categories.length) {
        $container.html('<div class="alert alert-info">' + app.lang.no_categories_found + '</div>');
        return this;
    }

    // Tạo cây danh mục
    const $categoriesTree = $('<div id="categories-tree" class="categories-tree"></div>');
    
    // Xây dựng cấu trúc cây
    this.buildCategoryTree(categories, $categoriesTree, 0);
    
    // Thêm vào container
    $container.html('');
    $container.append($categoriesTree);
    
    // Đăng ký sự kiện cho cây danh mục
    this.initCategoryTreeEvents();
    
    // Khôi phục các danh mục đã chọn từ draft (nếu có)
    this.restoreSelectedCategories();
    
    return this;
};

/**
 * Xây dựng cấu trúc cây danh mục
 * @param {Array} categories - Danh sách danh mục
 * @param {jQuery} $parentElement - Phần tử cha để thêm vào
 * @param {number} level - Cấp độ hiện tại của cây
 */
UltimateEditorPublish.buildCategoryTree = function(categories, $parentElement, level) {
    // Lọc ra các danh mục cấp cao nhất (parent_id = 0 hoặc null)
    const topLevelCategories = categories.filter(function(cat) {
        return !cat.parent_id || cat.parent_id === 0 || cat.parent_id === '0';
    });
    
    // Nếu không có danh mục cấp cao nhất, hiển thị tất cả
    const categoriesToShow = topLevelCategories.length ? topLevelCategories : categories;
    
    // Tạo danh sách
    const $list = $('<ul class="category-list"></ul>');
    
    if (level > 0) {
        $list.addClass('nested');
    }
    
    // Thêm từng danh mục vào danh sách
    categoriesToShow.forEach(function(category) {
        // Tạo item cho danh mục
        const $item = $('<li class="category-item"></li>');
        
        // Tạo checkbox cho danh mục
        const $checkbox = $(
            '<div class="checkbox">' +
            '<label>' +
            '<input type="checkbox" name="category[]" value="' + category.id + '"> ' +
            '<span class="category-name">' + category.name + '</span>' +
            '</label>' +
            '</div>'
        );
        
        $item.append($checkbox);
        
        // Kiểm tra xem danh mục này có danh mục con không
        const childCategories = categories.filter(function(cat) {
            return cat.parent_id && (cat.parent_id === category.id || cat.parent_id === parseInt(category.id));
        });
        
        // Nếu có danh mục con, thêm icon mở rộng và đệ quy để xây dựng cây con
        if (childCategories.length) {
            const $expandIcon = $('<span class="category-expand-icon"><i class="fa fa-plus-square-o"></i></span>');
            $item.addClass('has-children');
            $item.prepend($expandIcon);
            
            // Đệ quy để xây dựng cây con
            const $childList = $('<ul class="category-list nested"></ul>').css('display', 'none');
            this.buildCategoryTree(childCategories, $childList, level + 1);
            $item.append($childList);
        }
        
        // Thêm item vào danh sách
        $list.append($item);
    }, this);
    
    // Thêm danh sách vào phần tử cha
    $parentElement.append($list);
    
    return this;
};

/**
 * Đăng ký sự kiện cho cây danh mục
 */
UltimateEditorPublish.initCategoryTreeEvents = function() {
    // Sự kiện khi click vào icon mở rộng
    $('#categories-tree').off('click', '.category-expand-icon');
    $('#categories-tree').on('click', '.category-expand-icon', function() {
        const $this = $(this);
        const $item = $this.closest('.category-item');
        const $childList = $item.children('ul.category-list');
        
        // Toggle hiển thị danh mục con
        $childList.slideToggle(200);
        
        // Toggle icon
        if ($this.find('i').hasClass('fa-plus-square-o')) {
            $this.find('i').removeClass('fa-plus-square-o').addClass('fa-minus-square-o');
                } else {
            $this.find('i').removeClass('fa-minus-square-o').addClass('fa-plus-square-o');
        }
    });
    
    // Sự kiện khi chọn/bỏ chọn danh mục cha (chọn tất cả các con)
    $('#categories-tree').off('change', 'input[type="checkbox"]');
    $('#categories-tree').on('change', 'input[type="checkbox"]', function() {
        const $this = $(this);
        const isChecked = $this.prop('checked');
        const $item = $this.closest('.category-item');
        
        // Chọn/bỏ chọn tất cả các danh mục con
        if ($item.hasClass('has-children')) {
            $item.find('ul.category-list input[type="checkbox"]').prop('checked', isChecked);
        }
        
        // Nếu một danh mục con được chọn, đảm bảo danh mục cha cũng được chọn
        if (isChecked) {
            const $parentItems = $item.parents('.category-item');
            $parentItems.find('> .checkbox > label > input[type="checkbox"]').prop('checked', true);
        }
    });
    
    return this;
};

/**
 * Khôi phục các danh mục đã chọn từ dữ liệu draft
 */
UltimateEditorPublish.restoreSelectedCategories = function() {
    // Kiểm tra dữ liệu draft trong sessionStorage hoặc hidden input
    const fullDraftData = $('#full-draft-data').val() || sessionStorage.getItem('full_draft_data');
    if (!fullDraftData) {
        return this;
    }
    
    try {
        // Parse dữ liệu draft
        const parsedDraft = JSON.parse(fullDraftData);
        
        // Nếu có thông tin danh mục đã chọn
        if (parsedDraft.categories && Array.isArray(parsedDraft.categories)) {
            // Chọn các danh mục trong cây
            parsedDraft.categories.forEach(categoryId => {
                $('#categories-tree input[value="' + categoryId + '"]').prop('checked', true);
                
                // Expand danh mục cha để hiển thị danh mục con đã chọn
                const $categoryItem = $('#categories-tree input[value="' + categoryId + '"]').closest('.category-item');
                const $parentItems = $categoryItem.parents('.category-item.has-children');
                
                $parentItems.each(function() {
                    const $item = $(this);
                    const $icon = $item.find('> .category-expand-icon i');
                    const $childList = $item.children('ul.category-list');
                    
                    // Show child list
                    $childList.show();
                    
                    // Update icon
                    $icon.removeClass('fa-plus-square-o').addClass('fa-minus-square-o');
                });
            });
        }
    } catch (e) {
        console.error('Error parsing draft data for categories:', e);
    }
    
    return this;
};

// Khởi tạo module khi document ready
$(document).ready(function() {
    'use strict';
    
    // Khởi tạo module publish nếu tab publish tồn tại
    if ($('#tab_publish').length || $('#publish-modal').length) {
        UltimateEditorPublish.init();
    }
});