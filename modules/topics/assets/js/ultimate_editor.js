/**
 * Ultimate Editor JavaScript
 * 
 * File này chứa tất cả các hàm liên quan đến trình soạn thảo nâng cao với khả năng tạo bản nháp,
 * phân tích SEO, xuất bản và tích hợp Draft Writer.
 * 
 * Các file JavaScript liên quan:
 * @file ultimate_editor_fn.js - Chứa các hàm chức năng hỗ trợ
 * @file ultimate_editor_presents.js - Chứa các hàm UI/hiển thị và trang trí
 * @file ultimate_editor_exec.js - Chứa các hàm thực thi và xử lý workflow
 * 
 * Tính năng tích hợp TopicComposer:
 * - Tích hợp khả năng chọn ảnh từ Topic Composer làm Feature Image
 * - Kiểm tra và tải ảnh từ Topic Composer vào server thông qua workflow
 * - Quản lý ảnh đã tải và hiển thị trong modal chọn ảnh
 */

/********************************************************************************
    * PHÂN LOẠI HÀM TRONG FILE:
    * 
    * 1. HÀM KHỞI TẠO (INITIALIZATION FUNCTIONS)
    * - Các hàm khởi tạo cài đặt ban đầu, cấu hình và thiết lập môi trường
    * 
    * 2. HÀM CHỨC NĂNG (FUNCTIONAL FUNCTIONS)
    * - Xử lý logic nghiệp vụ, tính toán, chuyển đổi dữ liệu
    * - Các hàm trợ giúp (helper) có tính tái sử dụng cao
    * 
    * 3. HÀM THỰC THI (EXECUTION FUNCTIONS)
    * - Thực hiện các tác vụ chính như gọi API, xử lý workflow
    * - Thường có các hoạt động AJAX và thay đổi trạng thái hệ thống
    * 
    * 4. HÀM TRANG TRÍ (UI/PRESENTATION FUNCTIONS)
    * - Hiển thị, render UI, xử lý hiệu ứng
    * - Cập nhật giao diện người dùng và xử lý sự kiện UI
    ********************************************************************************/

// Constants
const AUTOSAVE_INTERVAL = 30000; // 30 seconds
const POLL_INTERVAL = 2000; // 2 seconds
const MAX_POLL_ATTEMPTS = 60; // 2 phút timeout (60 * 2 giây)
let lastSaveTime = new Date();
let isDirty = false;
let editor = null;
let isLoadingContent = false;
let pollTimeoutCount = 0;
let isPreviewMode = false;
let previewDraftId = null;

// Get workflow config from view
let WORKFLOW_ID;
let BUTTON_ID;
let TARGET_TYPE;
let TARGET_STATE;
let ACTION_COMMAND;

// INITIALIZATION FUNCTIONS Section 


/**
* KHỞI TẠO (INITIALIZATION FUNCTION)
* Khởi tạo trình soạn thảo và các thành phần liên quan
*/
function initializeEditor() {

    // Khi mới vào trang ultimate thì set body class hide-sidebar 
    $('body').addClass('hide-sidebar');
    
    // Vô hiệu hóa chức năng hover chuột vào mép trái để hiện menu
    // Phương pháp 1: Ghi đè sự kiện mousemove với namespace
    $(document).off('mousemove.sidebar');
    $(document).on('mousemove.sidebar', function(e) {
        // Không làm gì khi di chuột - để vô hiệu hóa chức năng tự động hiện menu
    });
    
    // Phương pháp 2: Ghi đè phương thức click của hide-menu để ngăn chặn việc toggle menu
    var originalClickHandler = $.fn.click;
    $.fn.click = function() {
        if (this.hasClass && this.hasClass('hide-menu')) {
            // Nếu đang ở trang Ultimate Editor, không cho phép click
            console.log('Hide menu click prevented in Ultimate Editor');
            return this;
        }
        // Gọi lại handler gốc cho tất cả các elements khác
        return originalClickHandler.apply(this, arguments);
    };
    
    // Phương pháp 3: Chặn sự kiện click toàn cầu và kiểm tra target
    $(document).on('click.ultimate', function(e) {
        if ($(e.target).hasClass('hide-menu') || $(e.target).closest('.hide-menu').length) {
            // Chặn event nếu là click trên hide-menu button
            e.stopImmediatePropagation();
            e.preventDefault();
            console.log('Menu toggle prevented');
            return false;
        }
    });
    
    console.log('Debug - DOM Elements:', {
        topicidElement: $('#topicid').length,
        draftContentElement: $('#editor-content').length,
        currentDraftIdElement: $('#current-draft-id').length,
        fullDraftDataElement: $('#full-draft-data').length
    });

    const topicId = $('#topicid').val();
    console.log('Debug - Topic ID value:', topicId);

    // Kiểm tra xem có dữ liệu draft đầy đủ không
    const fullDraftData = $('#full-draft-data').val();
    let draftDataFound = false;
    
    if (fullDraftData && fullDraftData.trim() !== '') {
        try {
            const parsedDraft = JSON.parse(fullDraftData);
            console.log('Found full draft data on page load from hidden field:', parsedDraft.id);
            
            // Lưu vào sessionStorage để đảm bảo nó được khôi phục sau khi editor khởi tạo
            sessionStorage.setItem('full_draft_data', fullDraftData);
            draftDataFound = true;
        } catch (e) {
            console.error('Error parsing full draft data from hidden field:', e);
        }
    } else {
        console.log('No full draft data found in hidden field, checking sessionStorage');
        
        // If not found in hidden field, try to get from sessionStorage
        const sessionDraftData = sessionStorage.getItem('full_draft_data');
        if (sessionDraftData) {
            try {
                const parsedDraft = JSON.parse(sessionDraftData);
                console.log('Found full draft data in sessionStorage:', parsedDraft.id);
                
                // Update the hidden field with the data from sessionStorage
                $('#full-draft-data').val(sessionDraftData);
                draftDataFound = true;
            } catch (e) {
                console.error('Error parsing full draft data from sessionStorage:', e);
            }
        } else {
            console.log('No full draft data found in sessionStorage either');
        }
    }

    if (!topicId) {
        console.error('Topic ID not found - Check if #topicid element exists and has a value');
        // Try to get topic ID from URL as fallback
        const urlParams = new URLSearchParams(window.location.search);
        const topicIdFromUrl = urlParams.get('topic_id');

        if (topicIdFromUrl) {
            console.log('Found topic ID from URL:', topicIdFromUrl);
            // Dynamically create the hidden input if it doesn't exist
            if ($('#topicid').length === 0) {
                $('body').append('<input type="hidden" id="topicid" value="' + topicIdFromUrl + '">');
            } else {
                $('#topicid').val(topicIdFromUrl);
            }
            // Continue with initialization using the URL parameter
            continueInitialization(topicIdFromUrl);
        } else {
            alert('Cannot initialize editor: Topic ID not found. Please try refreshing the page.');
            return;
        }
    } else {
        continueInitialization(topicId);
    }
}

/**
 * KHỞI TẠO (INITIALIZATION FUNCTION)
 * Tiếp tục quá trình khởi tạo sau khi có topic ID
 */
function continueInitialization(topicId) {
    console.log('Initializing Ultimate Editor for topic ID:', topicId);

    // Initialize TinyMCE first so it's available
    initializeTinyMCE();

    // Check if we have content from active draft
    const hasContent = $('#editor-content').html().trim() !== '';
    const activeDraftId = $('#current-draft-id').val();

    console.log('Draft status:', {
        hasContent: hasContent,
        activeDraftId: activeDraftId,
        contentLength: $('#editor-content').html().length
    });

    if (!hasContent || !activeDraftId) {
        console.log('No content or active draft found, loading from workflow...');
        // Load initial content from workflow if no content exists
        loadContentFromWorkflow(topicId);
    } else {
        console.log('Content already exists, skipping workflow load');
        // Still update word count for existing content
        setTimeout(updateWordCount, 1000);
    }

    // Setup event handlers
    setupEventHandlers();

    // Setup autosave
    setupAutosave();

    loadDraftsList();
}

/**
 * KHỞI TẠO (INITIALIZATION FUNCTION)
 * Khởi tạo trình soạn thảo TinyMCE
 */
function initializeTinyMCE() {
    tinymce.init({
        selector: '#editor-content',
        height: 800,
        plugins: [
            // 'advlist autolink lists link image charmap print preview anchor',
            // 'searchreplace visualblocks code fullscreen',
            // 'insertdatetime media table paste code help wordcount'
        ],
        toolbar: 'undo redo | formatselect | ' +
            'bold italic underline strikethrough | forecolor backcolor | ' +
            'alignleft aligncenter alignright alignjustify | ' +
            'bullist numlist | outdent indent | ' +
            'link image media table | code codesample | ' +
            'hr pagebreak | searchreplace | fullscreen preview | ' +
            'removeformat | help',
        setup: function (ed) {
            window.editor = ed;
            
            // Editor initialization event
            ed.on('init', function () {
                console.log('TinyMCE initialized');
                ed.initialized = true;
                
                // Set the content if active draft exists
                const fullDraftData = $('#full-draft-data').val();
                if (fullDraftData && fullDraftData.trim() !== '') {
                    try {
                        const parsedDraft = JSON.parse(fullDraftData);
                        console.log('Full draft data available in hidden field, applying to editor', parsedDraft);
                        
                        // Set the content in the editor
                        if (parsedDraft.draft_content) {
                            try {
                                // Parse the nested JSON content structure
                                const contentObj = JSON.parse(parsedDraft.draft_content);
                                // Extract HTML content from the structured content
                                let htmlContent = '';
                                if (contentObj.content && Array.isArray(contentObj.content)) {
                                    // Extract HTML from text fields in content array
                                    htmlContent = contentObj.content
                                        .filter(item => item.type === 'text')
                                        .map(item => item.text)
                                        .join('');
                                }
                                ed.setContent(htmlContent || parsedDraft.draft_content);
                            } catch (e) {
                                console.error('Error parsing nested content JSON:', e);
                                // Fallback to using the raw content
                                ed.setContent(parsedDraft.draft_content);
                            }
                        } else {
                            ed.setContent('');
                        }
                        
                        // Parse metadata
                        let metadata = {};
                        if (parsedDraft.draft_metadata) {
                            if (typeof parsedDraft.draft_metadata === 'string') {
                                try {
                                    metadata = JSON.parse(parsedDraft.draft_metadata);
                                } catch (e) {
                                    console.error('Error parsing draft metadata:', e);
                                    metadata = {};
                                }
                            } else {
                                metadata = parsedDraft.draft_metadata;
                            }
                        }
                        
                        // Populate fields from metadata or direct properties with HTML decoding
                        if (metadata.keywords !== undefined) {
                            $('#keywords').val(decodeHtmlEntities(metadata.keywords));
                        } else if (parsedDraft.keywords) {
                            $('#keywords').val(decodeHtmlEntities(parsedDraft.keywords));
                        }
                        
                        // Decode HTML entities in description
                        if (metadata.draft_description !== undefined) {
                            $('#draft-description').val(decodeHtmlEntities(metadata.draft_description));
                        } else if (parsedDraft.draft_description) {
                            $('#draft-description').val(decodeHtmlEntities(parsedDraft.draft_description));
                        }
                        
                        // Decode HTML entities in tags
                        if (metadata.draft_tags !== undefined) {
                            $('#draft-tags').val(decodeHtmlEntities(metadata.draft_tags));
                            renderTags();
                        } else if (parsedDraft.draft_tags) {
                            $('#draft-tags').val(decodeHtmlEntities(parsedDraft.draft_tags));
                            renderTags();
                        }
                        
                        // Populate SEO target keyword field from draft metadata or tags
                        let targetKeyword = '';
                        console.log('Draft metadata:', metadata);
                        if (metadata.target_keyword !== undefined) {
                            targetKeyword = metadata.target_keyword;
                        } else if (parsedDraft.target_keyword) {
                            targetKeyword = parsedDraft.target_keyword;
                        } else if (metadata.keywords !== undefined) {
                            targetKeyword = metadata.keywords;
                        } else if (parsedDraft.draft_tags) {
                            // If no target keyword is set, use the first tag
                            const tags = parsedDraft.draft_tags.split(',').map(tag => tag.trim()).filter(Boolean);
                            if (tags.length > 0) {
                                targetKeyword = tags[0];
                            }
                        }
                        
                        if (targetKeyword) {
                            $('#seo-target-keyword').val(targetKeyword);
                            // Trigger SEO analysis after a short delay to ensure editor is ready
                            setTimeout(function() {
                                analyzeSEO();
                            }, 500);
                        }
                        
                        console.log('Draft data restored from hidden field');
                        
                        // Reset dirty flag
                        isDirty = false;
                        
                        // Update word count
                        updateWordCount();

                        // Handle draft title if present (decode HTML entities)
                        if (parsedDraft.draft_title) {
                            const decodedTitle = decodeHtmlEntities(parsedDraft.draft_title);
                            $('#draft-title').val(decodedTitle);
                        }
                    } catch (e) {
                        console.error('Error applying full draft data from hidden field:', e);
                        
                        // If parsing from hidden field fails, try from sessionStorage
                        tryLoadFromSessionStorage();
                    }
                } else {
                    // If no data in hidden field, try from sessionStorage
                    tryLoadFromSessionStorage();
                }
                
                function tryLoadFromSessionStorage() {
                    console.log('Attempting to load draft data from sessionStorage');
                    const sessionDraftData = sessionStorage.getItem('full_draft_data');
                    if (sessionDraftData) {
                        try {
                            const parsedDraft = JSON.parse(sessionDraftData);
                            console.log('Full draft data available in sessionStorage, applying to editor');
                            
                            // Update the hidden field with the data from sessionStorage
                            $('#full-draft-data').val(sessionDraftData);
                            
                            // Set the content in the editor
                            if (parsedDraft.draft_content) {
                                try {
                                    // Parse the nested JSON content structure
                                    const contentObj = JSON.parse(parsedDraft.draft_content);
                                    // Extract HTML content from the structured content
                                    let htmlContent = '';
                                    if (contentObj.content && Array.isArray(contentObj.content)) {
                                        // Extract HTML from text fields in content array
                                        htmlContent = contentObj.content
                                            .filter(item => item.type === 'text')
                                            .map(item => item.text)
                                            .join('');
                                    }
                                    ed.setContent(htmlContent || parsedDraft.draft_content);
                                } catch (e) {
                                    console.error('Error parsing nested content JSON:', e);
                                    // Fallback to using the raw content
                                    ed.setContent(parsedDraft.draft_content);
                                }
                            } else {
                                ed.setContent('');
                            }
                            
                            // Also update other fields from sessionStorage data
                            if (parsedDraft.draft_title) {
                                $('#draft-title').val(parsedDraft.draft_title);
                            }
                            
                            if (parsedDraft.draft_description) {
                                $('#draft-description').val(parsedDraft.draft_description);
                            }
                            
                            if (parsedDraft.draft_tags) {
                                $('#draft-tags').val(parsedDraft.draft_tags);
                                renderTags();
                            }
                            
                            console.log('Draft data restored from sessionStorage');
                            
                            // Reset dirty flag
                            isDirty = false;
                            
                            // Update word count
                            updateWordCount();
                        } catch (e) {
                            console.error('Error applying full draft data from sessionStorage:', e);
                        }
                    } else {
                        console.log('No draft data found in sessionStorage');
                    }
                }
            });
        }
    });
}

/**
 * KHỞI TẠO (INITIALIZATION FUNCTION)
 * Thiết lập các event handler cho các thành phần giao diện
 */
function setupEventHandlers() {
    // Editor content change
    if (editor) {
        editor.on('change', function () {
            isDirty = true;
            updateWordCount();
        });
    }

    // Save draft button
    $('.save-draft-btn').off('click').on('click', function () {
        saveDraft();
    });

    // Load workflow button
    $('#load-workflow-btn').off('click').on('click', function () {
        const topicId = $('#topicid').val();
        loadContentFromWorkflow(topicId);
    });

    // New button: Create draft from workflow
    $('#create-from-workflow-btn').off('click').on('click', function () {
        createDraftFromWorkflow();
    });

    // Publish draft button
    $('.publish-draft-btn').off('click').on('click', function () {
        publishDraft();
    });

    // Import from Draft Writer button
    $('#btn-import-draft-writer').off('click').on('click', function () {
        importFromDraftWriter();
    });

    // SEO analysis button
    $('#analyze-seo-btn').off('click').on('click', function () {
        analyzeSEO();
    });

    // Add tag button
    $('#add-tag-btn').off('click').on('click', function () {
        addTag();
    });

    // Draft title change
    $('#draft-title').off('change keyup').on('change keyup', function () {
        isDirty = true;
        // Update UI elements that show draft title
        $('#current-draft-name').text($(this).val());
    });

    // AI tools buttons
    $('.ai-tool-btn').off('click').on('click', function (e) {
        e.preventDefault();
        // Show dropdown menu for AI tools
        var dropdown = $('<div class="ai-tool-dropdown">' +
            '<a href="#" id="ai-improve-btn"><i class="fa fa-arrow-up"></i> ' + _l('improve_content') + '</a>' +
            '<a href="#" id="ai-rewrite-btn"><i class="fa fa-refresh"></i> ' + _l('rewrite_content') + '</a>' +
            '<a href="#" id="ai-expand-btn"><i class="fa fa-expand"></i> ' + _l('expand_content') + '</a>' +
            '<a href="#" id="ai-summarize-btn"><i class="fa fa-compress"></i> ' + _l('summarize_content') + '</a>' +
            '<a href="#" id="ai-seo-btn"><i class="fa fa-line-chart"></i> ' + _l('optimize_seo') + '</a>' +
            '</div>');

        // Remove any existing dropdown
        $('.ai-tool-dropdown').remove();

        // Add new dropdown
        $(this).after(dropdown);

        // Handle dropdown item clicks
        // ... (existing dropdown click handlers)

        // Close when clicking outside
        $(document).one('click', function () {
            dropdown.remove();
        });

        return false;
    });

    // Initialize format buttons if not already initialized
    if (typeof initializeFormatButtons === 'function' && !formatButtonsInitialized) {
        initializeFormatButtons();
        formatButtonsInitialized = true;
    }

    // Feature image handlers in publish modal
    $('#select-feature-image').off('click').on('click', function (e) {
        e.preventDefault();
        // Use the global function for consistency
        window.selectFeatureImage();
    });
    
    // Remove feature image button in publish modal
    $('#remove-feature-image').off('click').on('click', function (e) {
        e.preventDefault();
        // Call the global function to ensure it's always available
        window.removeFeatureImage();
    });
    
    // Publish modal shown event - update feature image preview
    $('#publish-modal').on('shown.bs.modal', function() {
        console.log('Publish modal shown');

        // Ensure modal doesn't close when clicking outside or pressing Esc
        $(this).data('bs.modal').options.backdrop = 'static';
        $(this).data('bs.modal').options.keyboard = false;
        
        // Nếu chưa được khởi tạo, khởi tạo bây giờ
        if (!window.DraftWriter.publish.initialized) {
            console.log('Publish modal not yet initialized, initializing now...');
            initPublishTab();
            window.DraftWriter.publish.initialized = true;
        } else {
            console.log('Publish modal already initialized, refreshing data');
            refreshPublishModalData();
        }

        // Tải lại controllers nếu dropdown trống
        var $select = $('#topic-controller-select');
        console.log('Controller select exists:', $select.length > 0);
        console.log('Options count:', $select.find('option').length);

        if ($select.length > 0 && $select.find('option').length <= 1) {
            console.log('Loading controllers in modal...');
            loadTopicControllers();
        }
        
        // Sync tags from tags-container to the publish modal's tags-select
        if (typeof UltimateEditorPublish !== 'undefined' && typeof UltimateEditorPublish.syncTagsFromContainer === 'function') {
            console.log('Syncing tags from container to publish modal');
            UltimateEditorPublish.syncTagsFromContainer();
        }
    });

    // Bind handlers for tag removal
    $(document).on('click', '.tag-remove', function() {
        const tag = $(this).prev('.tag-text').text();
        removeTag(tag);
    });
    
    // Bind handlers for setting tag as SEO target keyword
    $(document).on('click', '.tag-text', function() {
        const tag = $(this).text();
        
        // Set this tag as the SEO target keyword
        $('#seo-target-keyword').val(tag);
        
        // Show notification
        alert_float('success', 'Tag "' + tag + '" set as SEO target keyword');
        
        // Switch to SEO analysis tab
        $('a[href="#tab_seo_analysis"]').tab('show');
        
        // Reset SEO checklist to loading state
        if (typeof resetSEOChecklist === 'function') {
            resetSEOChecklist();
        }
        
        // Trigger SEO analysis
        analyzeSEO();
    });
}

/**
 * KHỞI TẠO (INITIALIZATION FUNCTION)
 * Thiết lập chức năng tự động lưu
 */
function setupAutosave() {
    setInterval(function () {
        if (isDirty) {
            saveDraft(true);
        }
    }, AUTOSAVE_INTERVAL);
}


/**
 * KHỞI TẠO (INITIALIZATION FUNCTION)
 * Khởi tạo các nút định dạng
 */
function initializeFormatButtons() {
    // Cập nhật để sử dụng data-format
    $('.editor-toolbar-footer [data-format]').off('click').on('click', function () {
        console.log('Format button clicked:', $(this).data('format'));

        // Kiểm tra xem tinymce và editor đã sẵn sàng chưa
        if (typeof tinymce === 'undefined') {
            console.error('TinyMCE không được tải');
            alert_float('warning', 'Editor không sẵn sàng. Vui lòng làm mới trang.');
            return;
        }

        // Lấy editor instance
        const editorInstance = tinymce.get('editor-content');

        if (!editorInstance) {
            console.error('Không tìm thấy TinyMCE instance cho #editor-content');
            // Thử thay thế bằng active editor
            if (tinymce.activeEditor) {
                console.log('Sử dụng activeEditor thay thế');
                executeFormatCommand(tinymce.activeEditor, $(this).data('format'));
            } else {
                alert_float('warning', 'Editor chưa được khởi tạo. Vui lòng làm mới trang.');
            }
            return;
        }

        // Thực hiện lệnh định dạng
        executeFormatCommand(editorInstance, $(this).data('format'));
    });

    console.log('Format buttons initialized with data-format attribute');
}

/**
 * KHỞI TẠO (INITIALIZATION FUNCTION)
 * Khởi tạo tab xuất bản
 */
function initPublishTab() {
    console.log('Initializing publish modal...');

    // Đảm bảo DOM đã được tải đầy đủ trước khi khởi tạo
    if (!$('#topic-controller-select').length) {
        console.warn('Publish modal DOM not ready, delaying initialization...');
        setTimeout(initPublishTab, 500);
        return;
    }

    // Khởi tạo các thành phần UI
    loadTopicControllers();
    initEventHandlers(); // Bật lại event handlers
    initDatepicker();

    // Đăng ký sự kiện khi modal publish được hiển thị - chỉ nếu chưa có handler
    // This handler will be overridden by the more comprehensive ones in initPublishModal
    // and at the end of the document.ready function
    /* 
    $('#publish-modal').on('shown.bs.modal', function (e) {
        console.log('Publish modal shown, refreshing UI components');
        var $select = $('#topic-controller-select');
        if ($select.find('option').length <= 1) {
            loadTopicControllers();
        }
    });
    */

    console.log('Publish modal initialized successfully');
}

/**
 * KHỞI TẠO (INITIALIZATION FUNCTION)
 * Khởi tạo event handlers cho tab xuất bản
 */
function initEventHandlers() {
    console.log('Initializing publish tab event handlers');

    // Topic controller dropdown change event
    $('#topic-controller-select').on('change', function () {
        var controllerId = $(this).val();
        if (controllerId) {
            var option = $(this).find('option:selected');
            var platform = option.data('platform');
            var connected = option.data('connected');

            window.DraftWriter.publish.selectedController = controllerId;

            // Update UI
            $('#controller-info').removeClass('hide');
            $('#platform-name').text(platform);

            // Load categories and tags
            loadCategories(controllerId);
            loadTags(controllerId);

            // Update permalink prefix based on platform
            updatePermalinkPrefix(platform);

            // Update preview
            updatePostPreview();
        } else {
            $('#controller-info').addClass('hide');
            $('#categories-tree').html('');
            $('#tags-select').html('');
            $('#popular-tags-list').html('');
            $('#post-existence-check').addClass('hide');
        }
    });

    // Featured image selection
    $('#select-feature-image').on('click', function (e) {
        e.preventDefault();
        // Use the global function for consistency
        window.selectFeatureImage();
    });

    // Remove featured image
    $('#remove-feature-image').on('click', function (e) {
        e.preventDefault();
        // Use the global function for consistency
        window.removeFeatureImage();
    });

    // Popular tag click
    $(document).on('click', '.popular-tag', function () {
        var tag = $(this).text().trim();
        var tags = $('#tags-select').val() || [];

        if (!tags.includes(tag)) {
            tags.push(tag);
            $('#tags-select').val(tags).trigger('change');
        }
    });

    // Update permalink slug when draft title changes
    $('#draft-title').on('change keyup', function () {
        var title = $(this).val();
        if (title) {
            // Generate slug from title
            var slug = title.toLowerCase()
                .replace(/[^\w\s-]/g, '') // Remove special characters
                .replace(/\s+/g, '-')     // Replace spaces with hyphens
                .replace(/-+/g, '-');     // Replace multiple hyphens with single hyphen

            $('#permalink-slug').val(slug);

            // Check if post exists when controller is selected
            var controllerId = $('#topic-controller-select').val();
            if (controllerId) {
                UltimateEditorPublish.checkPostExistence(controllerId, title);
            }

            // Update preview
            updatePostPreview();
        }
    });

    // Update post status handling
    $('#post-status').on('change', function () {
        var status = $(this).val();
        if (status === 'schedule') {
            $('.schedule-time-group').removeClass('hide');
        } else {
            $('.schedule-time-group').addClass('hide');
        }
    });
}

/**
 * KHỞI TẠO (INITIALIZATION FUNCTION)
 * Khởi tạo datepicker cho lịch xuất bản
 */
function initDatepicker() {
    // Initialize datepicker for schedule time
    $('#schedule-time').datetimepicker({
        format: 'Y-m-d H:i:s',
        step: 30,
        minDate: 0,
        defaultTime: '12:00'
    });
}

/**
 * KHỞI TẠO (INITIALIZATION FUNCTION)
 * Khởi tạo modal xuất bản
 */
function initPublishModal() {
    console.log('Draft Writer Publish module initializing...');

    // Kiểm tra xem modal có tồn tại không
    if ($('#publish-modal').length) {
        console.log('Publish modal found, setting up event handlers');

        // Initialize publish tab when modal is shown
        $('#publish-modal').on('shown.bs.modal', function () {
            console.log('Publish modal shown, updating metadata and initializing tab');

            // Ensure modal doesn't close when clicking outside or pressing Esc
            $(this).data('bs.modal').options.backdrop = 'static';
            $(this).data('bs.modal').options.keyboard = false;

            // Update metadata before initializing
            var topicId = $('#topicid').val() || $('#topic_id').val();
            console.log('Topic ID from page:', topicId);

            if (!window.DraftWriter.metadata) {
                window.DraftWriter.metadata = {
                    topic_id: topicId
                };
            }

            // Khởi tạo tab chỉ một lần
            if (!window.DraftWriter.publish.initialized) {
                initPublishTab();
                window.DraftWriter.publish.initialized = true;
                console.log('Publish tab initialized');
            } else {
                console.log('Publish tab already initialized, refreshing data');
                // Always refresh data when modal is reopened
                refreshPublishModalData();
            }
        });

        console.log('Publish module setup complete');
    } else {
        console.warn('Publish modal not found in the page');
    }
}

/**
 * KHỞI TẠO (INITIALIZATION FUNCTION)
 * Refresh data in the publish modal
 */
function refreshPublishModalData() {
    console.log('Refreshing publish modal data');
    
    // Get the latest data from the editor
    var title = $('#draft-title').val() || '';
    var content = safeGetEditorContent();
    
    // Update the title in the modal if it exists
    if ($('#publish-title').length) {
        $('#publish-title').val(title);
    }
    
    // Update preview with latest content
    updatePostPreview();
    
    // If a controller is selected, refresh the dependent data
    var controllerId = $('#topic-controller-select').val();
    if (controllerId) {
        // Reload categories and tags with the latest data
        loadCategories(controllerId);
        loadTags(controllerId);
        
        // Check if post exists with current title
        if (title) {
            UltimateEditorPublish.checkPostExistence(controllerId, title);
        }
    }
}

/**
 * Check if TinyMCE is properly initialized
 * @returns {boolean} Returns true if TinyMCE is initialized successfully
 */
function checkTinyMCEInitialized() {
    console.log('Checking TinyMCE initialization...');
    
    if (typeof tinymce !== 'undefined' && tinymce.activeEditor && tinymce.activeEditor.initialized) {
        console.log('TinyMCE initialized successfully');
        return true;
    } else {
        console.warn('TinyMCE not initialized properly, trying to recover');
        
        // Check if the editor element exists
        if ($('#editor-content').length && (!tinymce || !tinymce.get('editor-content'))) {
            console.log('Reinitializing TinyMCE');
            // Call fallback initialization
            fallbackTinyMCEInit();
            return false;
        }
    }
    return false;
}

/**
 * Fallback initialization for TinyMCE if normal initialization fails
 */
function fallbackTinyMCEInit() {
    console.log('Running fallback TinyMCE initialization');
    
    if (typeof tinymce === 'undefined') {
        console.error('TinyMCE not available - cannot initialize');
        alert('Editor component not loaded properly. Please refresh the page.');
        return;
    }
    
    // Try to remove any existing editors first
    try {
        if (tinymce.get('editor-content')) {
            tinymce.get('editor-content').remove();
        }
    } catch(e) {
        console.warn('Error removing existing editor:', e);
    }
    
    // Initialize with basic settings
    tinymce.init({
        selector: '#editor-content',
        height: 800,
        plugins: [
            'advlist autolink lists link image charmap preview anchor',
            'searchreplace visualblocks code fullscreen',
            'insertdatetime media table paste code help wordcount'
        ],
        toolbar: 'undo redo | formatselect | bold italic underline | ' +
                 'alignleft aligncenter alignright | bullist numlist | link image',
        setup: function(ed) {
            window.editor = ed;
            
            // On init handler
            ed.on('init', function() {
                console.log('Fallback TinyMCE initialized');
                
                // Try to restore content from the HTML element
                const content = $('#editor-content').html();
                if (content) {
                    ed.setContent(content);
                }
            });
        }
    });
}

/**
 * Helper function for HTML entity decoding
 * Note: This is different from the one in ultimate_editor_fn.js as it uses a different approach
 * @param {string} encodedText - The encoded HTML text
 * @returns {string} Decoded text
 */
function decodeHtmlEntities(encodedText) {
    if (!encodedText) return '';
    const textArea = document.createElement('textarea');
    textArea.innerHTML = encodedText;
    return textArea.value;
}

/**
 * Helper function to clean HTML content from Draft Writer
 * @param {string} content - The HTML content to clean
 * @returns {string} Cleaned HTML content
 */
function cleanHtmlContent(content) {
    if (!content) return '';
    
    // Decode HTML entities
    content = decodeHtmlEntities(content);
    
    // Clean potentially problematic tags or attributes
    content = content
        .replace(/javascript:/gi, '')
        .replace(/on\w+="[^"]*"/gi, '')
        .replace(/on\w+='[^']*'/gi, '');
        
    return content;
}

/**
 * KHỞI TẠO (INITIALIZATION FUNCTION)
 * Theo dõi quá trình khởi tạo TinyMCE
 */
function monitorTinyMCEInitialization() {
    console.log('Starting TinyMCE initialization monitoring');
    
    // Monitor TinyMCE initialization
    let initCheckAttempts = 0;
    const maxInitCheckAttempts = 10;
    
    function checkInitialization() {
        initCheckAttempts++;
        
        if (checkTinyMCEInitialized()) {
            console.log('TinyMCE verified as initialized');
            return;
        }
        
        if (initCheckAttempts < maxInitCheckAttempts) {
            console.log(`TinyMCE not initialized on attempt ${initCheckAttempts}/${maxInitCheckAttempts}, checking again in 1 second...`);
            setTimeout(checkInitialization, 1000);
        } else {
            console.error('TinyMCE initialization check failed after maximum attempts');
            alert('Editor might not be fully functional. Please try refreshing the page if you experience issues.');
        }
    }
    
    // Start checking after a short delay
    setTimeout(checkInitialization, 1000);
}

// Khởi tạo khi tài liệu đã sẵn sàng
$(document).ready(function() {
    'use strict';
    
    console.log('Document ready, initializing Ultimate Editor...');
    
    // Khởi tạo cấu hình workflow
    WORKFLOW_ID = workflowConfig.workflow_id;
    BUTTON_ID = workflowConfig.button_id;
    TARGET_TYPE = workflowConfig.target_type;
    TARGET_STATE = workflowConfig.target_state;
    ACTION_COMMAND = workflowConfig.action_command;

    // Khởi tạo trình soạn thảo
    initializeEditor();
    
    // Khởi động theo dõi khởi tạo TinyMCE
    monitorTinyMCEInitialization();
    
    // Thiết lập DraftWriter components
    console.log('Initializing DraftWriter components...');

    // Đảm bảo DraftWriter object được khởi tạo
    window.DraftWriter = window.DraftWriter || {};
    window.DraftWriter.metadata = window.DraftWriter.metadata || {
        topic_id: $('#topicid').val() || $('#topic_id').val()
    };

    // Khởi tạo thành phần publish nếu chưa
    if (!window.DraftWriter.publish) {
        window.DraftWriter.publish = {
            selectedController: null,
            categories: [],
            tags: [],
            publishOptions: {
                status: 'draft',
                scheduleTime: null
            },
            initialized: false
        };
    }
    
    // Tải feature image từ external data nếu có
    if (typeof loadFeatureImageFromExternalData === 'function') {
        // Gọi hàm với một slight delay để đảm bảo các thành phần khác đã được khởi tạo
        setTimeout(function() {
            loadFeatureImageFromExternalData();
        }, 500);
    }

    // Topic controller select change event
    $(document).on('change', '#topic-controller-select', function() {
        var controllerId = $(this).val();
        
        if (controllerId) {
            // Get selected controller info
            var $option = $(this).find('option:selected');
            var platform = $option.data('platform');
            var connected = $option.data('connected') === 'true';
            
            // Store selected controller
            window.DraftWriter = window.DraftWriter || {};
            window.DraftWriter.publish = window.DraftWriter.publish || {};
            window.DraftWriter.publish.selectedController = {
                id: controllerId,
                platform: platform,
                connected: connected
            };
            
            // Update controller info display
            $('#platform-name').text(platform);
            $('#controller-info').removeClass('hide');
            
            // Load categories and tags
            loadCategories(controllerId);
            loadTags(controllerId);
            
            // Update permalink prefix based on platform
            updatePermalinkPrefix(platform);
            
            // Check post existence if title exists
            var title = $('#draft-title').val();
            if (title) {
                UltimateEditorPublish.checkPostExistence(controllerId, title);
            }
        } else {
            // Hide controller info
            $('#controller-info').addClass('hide');
            
            // Clear categories and tags
            $('#categories-tree').html('');
            if ($('#tags-select').data('select2')) {
                $('#tags-select').select2('destroy');
            }
            $('#tags-select').html('');
            $('#popular-tags-list').html('');
            
            // Clear stored data
            if (window.DraftWriter && window.DraftWriter.publish) {
                window.DraftWriter.publish.selectedController = null;
                window.DraftWriter.publish.categories = [];
                window.DraftWriter.publish.tags = [];
            }
        }
    });

    // Kiểm tra publish modal đã được tải chưa
    var publishModalExists = $('#publish-modal').length > 0;
    console.log('Publish modal exists:', publishModalExists);

    if (publishModalExists) {
        // Khởi tạo cho modal publish
        $('#publish-modal').on('shown.bs.modal', function() {
            console.log('Publish modal shown');

            // Nếu chưa được khởi tạo, khởi tạo bây giờ
            if (!window.DraftWriter.publish.initialized) {
                console.log('Publish modal not yet initialized, initializing now...');
                initPublishTab();
                window.DraftWriter.publish.initialized = true;
            } else {
                console.log('Publish modal already initialized, refreshing data');
                refreshPublishModalData();
            }

            // Tải lại controllers nếu dropdown trống
            var $select = $('#topic-controller-select');
            console.log('Controller select exists:', $select.length > 0);
            console.log('Options count:', $select.find('option').length);

            if ($select.length > 0 && $select.find('option').length <= 1) {
                console.log('Loading controllers in modal...');
                loadTopicControllers();
            }
        });
    }
});