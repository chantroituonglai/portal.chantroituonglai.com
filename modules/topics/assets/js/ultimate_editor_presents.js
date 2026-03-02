/**
 * Ultimate Editor Presentation
 * 
 * File này chứa các hàm trình bày (PRESENTATION FUNCTIONS) cho Ultimate Editor, 
 * chủ yếu là hiển thị giao diện, xử lý các sự kiện UI và cập nhật giao diện người dùng
 */

/**
 * TRANG TRÍ (UI/PRESENTATION FUNCTION)
 * Áp dụng nội dung vào trình soạn thảo và cập nhật UI
 */
function applyContentToEditor(content) {
    console.log('Applying content to editor:', content);

    // Lưu tham chiếu đến editor trước khi thay đổi nội dung
    const currentEditor = window.editor;

    let htmlContent = '';
    let mainTitle = '';
    let mainSummary = '';
    let topicKeywords = '';

    // Xử lý khi content là mảng (từ json_response_step1.md)
    if (Array.isArray(content)) {
        console.log('Processing array content with ' + content.length + ' items');

        // Lấy item đầu tiên để extract title và summary
        const firstItem = content[0];

        // Xử lý title
        if (firstItem.Title) {
            mainTitle = decodeHtmlEntities(firstItem.Title);
            $('#draft-title').val(mainTitle);
            console.log('Set title:', mainTitle);
        }

        // Xử lý summary/description
        if (firstItem.Summary) {
            mainSummary = decodeHtmlEntities(firstItem.Summary);
            $('#draft-description').val(mainSummary);
            console.log('Set description:', mainSummary);
        }

        // Build HTML content

        // Xử lý từng item
        content.forEach((item, index) => {
            // Thêm separator giữa các item
            if (index > 0) {
                htmlContent += '<hr class="item-separator">';
            }

            // Thêm tiêu đề item nếu khác với tiêu đề chính
            if (item.Item_Title && decodeHtmlEntities(item.Item_Title) !== mainTitle) {
                const decodedItemTitle = decodeHtmlEntities(item.Item_Title);
                htmlContent += `<h2>${decodedItemTitle}</h2>`;
            }

            // Thêm nội dung item
            if (item.Item_Content) {
                const cleanedContent = decodeHtmlEntities(item.Item_Content);
                htmlContent += `<div class="item-content">${cleanedContent}</div>`;
            }

            // Thêm hình ảnh nếu có
            if (item.item_Pictures_Full && item.item_Pictures_Full.length > 0) {
                htmlContent += '<div class="item-images">';

                // Parse item_Pictures_Full nếu là string
                let pictures = item.item_Pictures_Full;
                if (typeof pictures === 'string') {
                    try {
                        pictures = JSON.parse(pictures);
                    } catch (e) {
                        console.error('Error parsing item_Pictures_Full:', e);
                    }
                }

                // Thêm mỗi hình ảnh
                if (Array.isArray(pictures)) {
                    pictures.forEach(pic => {
                        const imgSrc = typeof pic === 'string' ? pic : (pic.large_src || pic.src || '');
                        if (imgSrc) {
                            htmlContent += `<img src="${imgSrc}" alt="Image" class="img-responsive">`;
                        }
                    });
                } else if (typeof pictures === 'object' && pictures.large_src) {
                    htmlContent += `<img src="${pictures.large_src}" alt="Image" class="img-responsive">`;
                }

                htmlContent += '</div>';
            }
        });

        // Thêm footer nếu có
        if (firstItem.Topic_footer) {
            const decodedFooter = decodeHtmlEntities(firstItem.Topic_footer);
            htmlContent += `<hr><div class="footer">${decodedFooter}</div>`;
        }

        // Lưu keywords
        if (firstItem.TopicKeywords) {
            topicKeywords = decodeHtmlEntities(firstItem.TopicKeywords);
        }
    } else if (typeof content === 'object') {
        // Xử lý khi content là object
        htmlContent = content.content || content.draft_content || '';
        mainTitle = content.title || content.draft_title || '';
        mainSummary = content.description || content.meta_description || '';
        topicKeywords = content.keywords || content.tags || '';
    } else {
        // Xử lý khi content là string
        htmlContent = content;
    }

    try {
        // Cập nhật nội dung vào editor với cơ chế phòng lỗi
        let editorUpdated = false;

        // Thử cập nhật qua TinyMCE trước
        if (typeof tinymce !== 'undefined' && tinymce.get('editor-content')) {
            try {
                tinymce.get('editor-content').setContent(htmlContent);
                editorUpdated = true;
            } catch (e) {
                console.error('Error setting content using TinyMCE:', e);
            }
        }

        // Thử cập nhật qua biến editor toàn cục
        if (!editorUpdated && typeof editor !== 'undefined' && editor && typeof editor.setContent === 'function') {
            try {
                editor.setContent(htmlContent);
                editorUpdated = true;
            } catch (e) {
                console.error('Error setting content using global editor variable:', e);
            }
        }

        // Luôn cập nhật container HTML để đảm bảo nội dung được lưu
        $('#editor-content').html(htmlContent);

        // Kiểm tra và khôi phục editor nếu cần
        if (typeof checkAndRecoverEditor === 'function' && !checkAndRecoverEditor()) {
            if (typeof restoreEditorReference === 'function') {
                restoreEditorReference(currentEditor);
            }

            console.warn('Editor reference may have been lost during content update, attempted recovery');
        }

    } catch (error) {
        console.error('Error applying content to editor:', error);
        // Đảm bảo nội dung được lưu vào phần tử HTML
        $('#editor-content').html(htmlContent);

        // Thử khôi phục tham chiếu editor
        if (typeof restoreEditorReference === 'function') {
            restoreEditorReference(currentEditor);
        }
    }

    // Cập nhật tiêu đề
    if (mainTitle) {
        $('#draft-title').val(mainTitle);
    }

    // Cập nhật description
    if (mainSummary && $('#draft-description').length) {
        $('#draft-description').val(mainSummary);
    }

    // Cập nhật keywords/tags
    if (topicKeywords && $('#draft-tags').length) {
        $('#draft-tags').val(topicKeywords);
    }

    // Phân tích nội dung
    analyzeContent(htmlContent, mainTitle, mainSummary, topicKeywords);

    // Cập nhật số từ
    updateWordCount();

    // Render tags
    renderTags();

    alert_float('success', 'Content loaded successfully');
}

/**
 * TRANG TRÍ (UI/PRESENTATION FUNCTION)
 * Hiển thị kết quả phân tích SEO
 */
function displaySEOAnalysis(analysis) {
    // Hide loading indicator
    $('#seo-analysis-loading').addClass('hide');

    try {
        // If analysis is a string, try to parse it
        if (typeof analysis === 'string') {
            try {
                analysis = JSON.parse(analysis);
            } catch (e) {
                console.error('Error parsing SEO analysis JSON:', e);
                alert_float('danger', 'Invalid analysis data format');
                return;
            }
        }

        console.log('SEO Analysis data:', analysis);

        // Update score
        const score = analysis.score || 0;
        const scorePercent = Math.min(100, Math.max(0, score));
        $('.score-indicator .score-number').text(score);
        $('.score-progress .progress-bar').css('width', scorePercent + '%')
            .attr('aria-valuenow', score);

        // Update content stats
        $('#content-length').text(analysis.stats ? analysis.stats.wordCount : 0);
        $('#headings-count').text(analysis.stats ? analysis.stats.headingsCount : 0);
        $('#images-count').text(analysis.stats ? analysis.stats.imagesCount : 0);

        // Set progress bar color based on score
        const progressBar = $('.score-progress .progress-bar');
        if (score < 40) {
            progressBar.removeClass('bg-warning bg-success bg-info').addClass('bg-danger');
        } else if (score < 60) {
            progressBar.removeClass('bg-danger bg-success bg-info').addClass('bg-warning');
        } else if (score < 80) {
            progressBar.removeClass('bg-danger bg-warning bg-info').addClass('bg-success');
        } else {
            progressBar.removeClass('bg-danger bg-warning bg-success').addClass('bg-info');
        }

        // Make sure checks object exists
        if (!analysis.checks) {
            // Create checks from suggestions if available
            analysis.checks = extractChecksFromSuggestions(analysis.suggestions || []);
        }

        // Update checklist items
        updateSEOChecklistItem('title', analysis.checks.titleTag);
        updateSEOChecklistItem('description', analysis.checks.metaDescription);
        updateSEOChecklistItem('content-length', analysis.checks.contentLength);
        updateSEOChecklistItem('headings', analysis.checks.headingStructure);
        updateSEOChecklistItem('images', analysis.checks.imageOptimization);
        updateSEOChecklistItem('links', analysis.checks.internalLinks);
        updateSEOChecklistItem('keyword', analysis.checks.keywordUsage);

        // Update suggestions
        const suggestionsContainer = $('#seo-suggestions .suggestions-list');
        suggestionsContainer.empty();

        if (analysis.suggestions && analysis.suggestions.length > 0) {
            analysis.suggestions.forEach(function (suggestion) {
                const suggestionClass = suggestion.type === 'good' ? 'text-success' :
                    (suggestion.type === 'warning' ? 'text-warning' : 'text-danger');
                const suggestionIcon = suggestion.type === 'good' ? 'fa-check-circle' :
                    (suggestion.type === 'warning' ? 'fa-exclamation-triangle' : 'fa-times-circle');

                const suggestionHTML = `
                    <div class="suggestion-item mb-2">
                        <div class="d-flex">
                            <div class="suggestion-icon mr-2">
                                <i class="fa ${suggestionIcon} ${suggestionClass}"></i>
                            </div>
                            <div class="suggestion-text">
                                ${suggestion.text}
                            </div>
                        </div>
                    </div>
                `;

                suggestionsContainer.append(suggestionHTML);
            });
        } else {
            suggestionsContainer.append('<div class="text-muted">No suggestions available</div>');
        }

        console.log('SEO analysis displayed successfully');
    } catch (e) {
        console.error('Error displaying SEO analysis:', e);
        alert_float('danger', 'Error displaying SEO analysis: ' + e.message);
    }
}

/**
 * TRANG TRÍ (UI/PRESENTATION FUNCTION)
 * Extract checks from suggestions
 */
function extractChecksFromSuggestions(suggestions) {
    // Default values
    const checks = {
        titleTag: { status: 'unknown' },
        metaDescription: { status: 'unknown' },
        contentLength: { status: 'unknown' },
        headingStructure: { status: 'unknown' },
        imageOptimization: { status: 'unknown' },
        internalLinks: { status: 'unknown' },
        keywordUsage: { status: 'unknown' }
    };

    // Extract checks from suggestions
    suggestions.forEach(suggestion => {
        const text = suggestion.text.toLowerCase();
        const type = suggestion.type;

        // Title
        if (text.includes('title')) {
            checks.titleTag = { status: type, message: suggestion.text };
        }

        // Description
        if (text.includes('description')) {
            checks.metaDescription = { status: type, message: suggestion.text };
        }

        // Content length
        if (text.includes('content') && (text.includes('short') || text.includes('length'))) {
            checks.contentLength = { status: type, message: suggestion.text };
        }

        // Headings
        if (text.includes('heading') || text.includes('h1') || text.includes('h2')) {
            checks.headingStructure = { status: type, message: suggestion.text };
        }

        // Images
        if (text.includes('image') || text.includes('alt')) {
            checks.imageOptimization = { status: type, message: suggestion.text };
        }

        // Links
        if (text.includes('link') || text.includes('url') || text.includes('href')) {
            checks.internalLinks = { status: type, message: suggestion.text };
        }

        // Keyword usage
        if (text.includes('keyword') || text.includes('density')) {
            checks.keywordUsage = { status: type, message: suggestion.text };
        }
    });

    return checks;
}

/**
 * HIỂN THỊ (DISPLAY FUNCTION)
 * Cập nhật một mục trong danh sách kiểm tra SEO
 */
function updateSEOChecklistItem(id, check) {
    const item = $('#check-' + id);
    if (!item.length) return;

    const icon = item.find('i');
    const status = item.find('.status');

    // Remove spinner and any existing classes
    icon.removeClass('fa-spinner fa-spin fa-check-circle fa-exclamation-triangle fa-times-circle fa-question-circle');

    // If check is undefined or has unknown status
    if (!check || check.status === 'unknown') {
        icon.addClass('fa-question-circle').css('color', '#aaa');
        status.text('N/A').css('color', '#aaa');
        return;
    }

    // Set icon and status based on check result
    if (check.status === 'good') {
        icon.addClass('fa-check-circle').css('color', '#28a745');
        status.text('Good').css('color', '#28a745');
    } else if (check.status === 'warning') {
        icon.addClass('fa-exclamation-triangle').css('color', '#ffc107');
        status.text('Improve').css('color', '#ffc107');
    } else if (check.status === 'error') {
        icon.addClass('fa-times-circle').css('color', '#dc3545');
        status.text('Fix').css('color', '#dc3545');
    } else {
        // Default for any other status
        icon.addClass('fa-question-circle').css('color', '#aaa');
        status.text('N/A').css('color', '#aaa');
    }

    // Add tooltip if there's a message
    if (check.message) {
        item.attr('title', check.message);
        if (typeof $.fn.tooltip === 'function') {
            item.tooltip({
                container: 'body',
                placement: 'auto',
                html: false
            });
        }
    }
}

/**
 * TRANG TRÍ (UI/PRESENTATION FUNCTION)
 * Cập nhật danh sách kiểm tra SEO
 */
function updateSEOChecklist(analysis) {
    // Hàm trợ giúp để cập nhật từng mục kiểm tra
    function updateCheckItem(itemId, status, message) {
        const item = $('#' + itemId);
        const iconCell = item.find('td:first-child');
        const statusCell = item.find('.status');

        // Xóa biểu tượng spinner
        iconCell.html('');

        // Đặt biểu tượng và màu sắc phù hợp
        if (status === 'good') {
            iconCell.html('<i class="fa fa-check-circle text-success"></i>');
            statusCell.html('<span class="label label-success">Good</span>');
        } else if (status === 'warning') {
            iconCell.html('<i class="fa fa-exclamation-triangle text-warning"></i>');
            statusCell.html('<span class="label label-warning">Improve</span>');
        } else if (status === 'error') {
            iconCell.html('<i class="fa fa-times-circle text-danger"></i>');
            statusCell.html('<span class="label label-danger">Poor</span>');
        }

        // Đặt tooltip nếu có thông báo
        if (message && message.length > 0) {
            item.attr('title', message);
            if (typeof $.fn.tooltip === 'function') {
                item.tooltip({
                    container: 'body',
                    placement: 'left',
                    html: true
                });
            }
        }
    }

    // Khởi tạo trạng thái mặc định
    let titleStatus = 'error';
    let descriptionStatus = 'error';
    let contentStatus = 'error';
    let headingsStatus = 'error';
    let imagesStatus = 'error';
    let linksStatus = 'error';
    let keywordStatus = 'error';

    // Kiểm tra và đặt trạng thái dựa trên phân tích
    if (analysis.suggestions && analysis.suggestions.length > 0) {
        analysis.suggestions.forEach(suggestion => {
            const text = suggestion.text.toLowerCase();
            const type = suggestion.type;

            // Xác định mục kiểm tra dựa trên nội dung gợi ý
            if (text.includes('title')) {
                titleStatus = type;
            }

            if (text.includes('description')) {
                descriptionStatus = type;
            }

            if (text.includes('content length') || text.includes('word')) {
                contentStatus = type;
            }

            if (text.includes('heading') || text.includes('h1') || text.includes('h2')) {
                headingsStatus = type;
            }

            if (text.includes('image')) {
                imagesStatus = type;
            }

            if (text.includes('link')) {
                linksStatus = type;
            }

            if (text.includes('keyword') || text.includes('density')) {
                keywordStatus = type;
            }
        });
    }

    // Các chỉ số nếu có
    if (analysis.stats) {
        // Content length check
        if (analysis.stats.wordCount > 600) {
            contentStatus = 'good';
        } else if (analysis.stats.wordCount > 300) {
            contentStatus = 'warning';
        }

        // Headings check
        if (analysis.stats.headingsCount > 3) {
            headingsStatus = 'good';
        } else if (analysis.stats.headingsCount > 0) {
            headingsStatus = 'warning';
        }

        // Images check
        if (analysis.stats.imagesCount > 2) {
            imagesStatus = 'good';
        } else if (analysis.stats.imagesCount > 0) {
            imagesStatus = 'warning';
        }

        // Links check
        if (analysis.stats.linksCount > 2) {
            linksStatus = 'good';
        } else if (analysis.stats.linksCount > 0) {
            linksStatus = 'warning';
        }
    }

    // Cập nhật UI cho từng mục kiểm tra
    updateCheckItem('check-title', titleStatus, 'Title optimization');
    updateCheckItem('check-description', descriptionStatus, 'Description optimization');
    updateCheckItem('check-content-length', contentStatus, 'Content length check');
    updateCheckItem('check-headings', headingsStatus, 'Heading structure optimization');
    updateCheckItem('check-images', imagesStatus, 'Image optimization');
    updateCheckItem('check-links', linksStatus, 'Link structure');
    updateCheckItem('check-keyword', keywordStatus, 'Keyword usage and density');
}

/**
 * TRANG TRÍ (UI/PRESENTATION FUNCTION)
 * Gắn các action cho các nút trong danh sách bản nháp
 */
function bindDraftActions() {
    // Use draft button - loads the draft permanently
    $('.use-draft-btn').off('click').on('click', function (e) {
        e.preventDefault();
        e.stopPropagation();

        const draftId = $(this).data('draft-id');
        loadDraft(draftId);
    });

    // Preview draft button - loads temporarily
    $('.preview-draft-btn').off('click').on('click', function (e) {
        e.preventDefault();
        e.stopPropagation();

        const draftId = $(this).data('draft-id');
        previewDraft(draftId);
    });

    // Sort order change
    $('#draft-sort-order').off('change').on('change', function () {
        loadDraftsList();
    });

    // Refresh button
    $('#refresh-drafts-btn').off('click').on('click', function () {
        $('#drafts-list').html(
            '<div class="text-center p-3">' +
            '<i class="fa fa-spinner fa-spin fa-2x"></i>' +
            '<p class="mt-2">Đang tải...</p>' +
            '</div>'
        );
        loadDraftsList();
    });
}

/**
 * TRANG TRÍ (UI/PRESENTATION FUNCTION)
 * Hiển thị banner xem trước bản nháp
 */
function showPreviewBanner(draftTitle, draftId) {
    // Remove any existing preview banner
    $('.preview-banner').remove();

    // Use _l() function directly to get translated strings
    const previewMode = _l('preview_mode');
    const youArePreviewing = _l('you_are_previewing');
    const unsavedChanges = _l('unsaved_changes_will_be_lost');
    const useThisDraft = _l('use_this_draft');
    const cancelPreviewText = _l('cancel_preview');

    // Create the preview banner HTML
    const previewBanner = `
            <div class="preview-banner alert alert-info" style="margin-bottom: 15px; display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <strong>${previewMode}</strong>: ${youArePreviewing} <span class="badge badge-info">${draftTitle}</span>
                    <small class="text-muted">${unsavedChanges}</small>
                </div>
                <div>
                    <button type="button" class="btn btn-success btn-sm preview-use-draft-btn" data-draft-id="${draftId}">
                        <i class="fa fa-check"></i> ${useThisDraft}
                    </button>
                    <button type="button" class="btn btn-default btn-sm cancel-preview-btn" onclick="cancelPreview()">
                        <i class="fa fa-times"></i> ${cancelPreviewText}
                    </button>
                                    </div>
                                </div>
                            `;

    // Add the preview banner to the editor main
    $('.editor-main').prepend(previewBanner);

    // Add event handlers for the buttons - using a unique class name to avoid conflicts
    $('.preview-use-draft-btn').on('click', function () {

        const draftId = $(this).data('draft-id');

        // Confirm if there are unsaved changes
        if (isDirty) {
            if (!confirm(_l('unsaved_changes_will_be_lost'))) {
                return;
            }
        }

        console.log('Loading draft:', draftId);
        // Load the draft
        loadDraft(draftId);
        // const sessionDraftData = sessionStorage.getItem('full_draft_data');
        // if (sessionDraftData) {
        //     $('#full-draft-data').val(sessionDraftData);
        //     console.log('Updated hidden field with session draft data for persistence');

        //     // Additionally, refresh the drafts list to show the updated active state
        //     loadDraftsList();
        // }
    });

    // Set preview mode flags - make sure they're set at window level for global access
    window.isPreviewMode = true;
    window.previewDraftId = draftId;

    // Also set local variables if they exist
    if (typeof isPreviewMode !== 'undefined') {
        isPreviewMode = true;
    }
    if (typeof previewDraftId !== 'undefined') {
        previewDraftId = draftId;
    }

    console.log('Preview mode activated for draft:', draftId);
}

/**
 * TRANG TRÍ (UI/PRESENTATION FUNCTION)
 * Hủy xem trước bản nháp
 */
function cancelPreview() {
    console.log('Canceling preview mode...');

    // Check both global and local preview mode variables
    if (!(window.isPreviewMode || (typeof isPreviewMode !== 'undefined' && isPreviewMode))) {
        console.log('Not in preview mode, nothing to cancel');
        return;
    }

    // Try to restore from localStorage
    const backupState = localStorage.getItem('preview_backup_state');

    if (backupState) {
        try {
            const state = JSON.parse(backupState);

            // Restore editor content
            if (window.editor && state.content) {
                window.editor.setContent(state.content);
            } else if (typeof editor !== 'undefined' && editor && state.content) {
                editor.setContent(state.content);
            }

            // Restore form fields
            $('#draft-title').val(state.title || '');
            $('#draft-description').val(state.description || '');
            $('#draft-tags').val(state.tags || '');
            $('#keywords').val(state.keywords || '');

            // Restore feature image
            if (state.featureImage) {
                $('#feature-image-url').val(state.featureImage);
                $('#feature-image').attr('src', state.featureImage);
                $('.feature-image-preview').removeClass('hide');
            } else {
                $('#feature-image-url').val('');
                $('#feature-image').attr('src', '');
                $('.feature-image-preview').addClass('hide');
            }

            // Update UI
            renderTags();
            if (typeof updateWordCount === 'function') {
                updateWordCount();
            }

            console.log('Preview canceled, editor state restored');
        } catch (e) {
            console.error('Error restoring editor state:', e);
            alert_float('warning', _l('error_loading_draft'));
        }
    } else {
        console.log('No backup state found in localStorage');
    }

    // Remove the preview banner
    $('.preview-banner').remove();

    // Reset preview flags (both global and local if they exist)
    window.isPreviewMode = false;
    window.previewDraftId = null;

    if (typeof isPreviewMode !== 'undefined') {
        isPreviewMode = false;
    }
    if (typeof previewDraftId !== 'undefined') {
        previewDraftId = null;
    }

    // Clear the backup state
    localStorage.removeItem('preview_backup_state');

    // Show success message
    alert_float('info', _l('preview_mode_canceled'));
}

/**
 * TRANG TRÍ (UI/PRESENTATION FUNCTION)
 * Render các thẻ tag
 */
function renderTags() {
    const tagsInput = $('#draft-tags');
    const tagsContainer = $('#tags-container');

    if (!tagsInput.length || !tagsContainer.length) return;

    const tagsValue = tagsInput.val();
    const tags = tagsValue.split(',').map(tag => tag.trim()).filter(Boolean);

    // Clear the container
    tagsContainer.empty();

    // Add each tag as a clickable item
    tags.forEach(tag => {
        const tagItem = $(`
                <div class="tag-item" title="Click to use as SEO target keyword">
                    <span class="tag-text">${tag}</span>
                    <span class="tag-remove">&times;</span>
                </div>
            `);

        tagsContainer.append(tagItem);
    });
}

/**
 * TRANG TRÍ (UI/PRESENTATION FUNCTION)
 * Thêm thẻ tag
 */
function addTag() {
    const tagsInput = $('#draft-tags');
    const tagValue = tagsInput.val().trim();

    if (!tagValue) return;

    // Split by comma to handle multiple tags at once
    const newTags = tagValue.split(',').map(tag => tag.trim()).filter(Boolean);

    // Get current tags
    const currentTagsValue = tagsInput.data('tags') || '';
    let currentTags = currentTagsValue.split(',').map(tag => tag.trim()).filter(Boolean);

    // Add new tags
    newTags.forEach(tag => {
        if (!currentTags.includes(tag)) {
            currentTags.push(tag);
        }
    });

    // Update input value
    updateTagsInput(currentTags);

    // Clear the input field
    tagsInput.val('');

    // Mark as dirty for autosave
    isDirty = true;
}

/**
 * TRANG TRÍ (UI/PRESENTATION FUNCTION)
 * Xóa thẻ tag
 */
function removeTag(tagToRemove) {
    const tagsInput = $('#draft-tags');

    // Get current tags
    const currentTagsValue = tagsInput.data('tags') || tagsInput.val();
    let currentTags = currentTagsValue.split(',').map(tag => tag.trim()).filter(Boolean);

    // Remove the tag
    currentTags = currentTags.filter(tag => tag !== tagToRemove);

    // Update input value
    updateTagsInput(currentTags);

    // Mark as dirty for autosave
    isDirty = true;
}

/**
 * TRANG TRÍ (UI/PRESENTATION FUNCTION)
 * Cập nhật input thẻ tag
 */
function updateTagsInput(tags) {
    const tagsInput = $('#draft-tags');

    // Join tags with comma
    const tagsValue = tags.join(', ');

    // Store in data attribute and hidden input
    tagsInput.data('tags', tagsValue);
    tagsInput.val(tagsValue);

    // Render tags
    renderTags();
}

/**
 * TRANG TRÍ (UI/PRESENTATION FUNCTION)
 * Điền danh sách controllers vào dropdown
 */
function populateControllersDropdown(controllers) {
    // Đảm bảo đã tìm thấy select element
    var $select = $('#topic-controller-select');
    console.log('Populating controllers dropdown with:', controllers);
    console.log('Select element found:', $select.length > 0);

    // Nếu không tìm thấy select element, thử lại sau một khoảng thời gian
    if ($select.length === 0) {
        console.warn('Select element #topic-controller-select not found in DOM, retrying in 500ms...');
        setTimeout(function () {
            populateControllersDropdown(controllers);
        }, 500);
        return;
    }

    // Make sure loading indicators are removed
    var $selectContainer = $select.closest('.form-group');
    $selectContainer.find('.controller-loading-indicator').addClass('hide');
    $select.removeClass('loading');
    $select.prop('disabled', false);

    // Xóa tất cả các option hiện tại trừ option đầu tiên
    $select.find('option:not(:first)').remove();

    if (!controllers || controllers.length === 0) {
        $select.append('<option value="" disabled>No controllers available</option>');
        return;
    }

    // Thêm từng controller vào dropdown
    controllers.forEach(function (controller) {
        try {
            var option = $('<option>', {
                value: controller.id,
                text: controller.name + ' (' + controller.platform + ')',
                'data-platform': controller.platform,
                'data-connected': controller.connected
            });

            $select.append(option);
            console.log('Added option:', controller.name);
        } catch (e) {
            console.error('Error adding controller option:', e, controller);
        }
    });

    // Đảm bảo dropdown được cập nhật đúng cách
    $select.trigger('change');

    // Hiển thị số lượng option đã thêm
    console.log('Total options after population:', $select.find('option').length);
}

/**
 * TRANG TRÍ (UI/PRESENTATION FUNCTION)
 * Render cây categories
 * @param {Array} categories - Categories data from API
 */
function renderCategoryTree(categories) {
    var $container = $('#categories-tree');
    $container.empty();

    if (!categories || !Array.isArray(categories) || categories.length === 0) {
        console.error('Invalid or empty categories data');
        $container.html('<div class="alert alert-warning">' + (typeof app !== 'undefined' && app.lang ? app.lang.no_categories_found : 'No categories found') + '</div>');
        return;
    }

    // Update category count display theo chuẩn Perfex CRM
    var categoriesText = typeof app !== 'undefined' && app.lang ? app.lang.categories : 'categories';
    $('#categories_count').text(categories.length + ' ' + categoriesText);

    // Convert flat list to hierarchical tree structure
    var categoryMap = {};
    var rootCategories = [];

    // First pass: create map of all categories
    categories.forEach(function (category) {
        if (category) {
            // Ensure we have a consistent property name for the ID
            var categoryId = category.category_id || category.id;
            category.children = [];
            categoryMap[categoryId] = category;
        }
    });

    // Second pass: build parent-child relationships
    categories.forEach(function (category) {
        if (!category) return;

        var categoryId = category.category_id || category.id;
        var parentId = category.parent_id;

        if (parentId && categoryMap[parentId]) {
            // Add to parent's children
            categoryMap[parentId].children.push(categoryMap[categoryId]);
        } else {
            // Root category
            rootCategories.push(categoryMap[categoryId]);
        }
    });

    // Sort root categories alphabetically
    rootCategories.sort(function (a, b) {
        return (a.name || '').localeCompare(b.name || '');
    });

    // Create root UL element
    var $rootUl = $('<ul class="list-unstyled"></ul>');

    // Build tree recursively
    buildCategoryTree(rootCategories, $rootUl);

    // Append to container
    $container.append($rootUl);

    // Initialize event handlers
    initializeTreeHandlers();
}

/**
 * Build category tree recursively
 * @param {Array} categories - List of categories
 * @param {jQuery} $parentElement - Parent element to append to
 */
function buildCategoryTree(categories, $parentElement) {
    if (!categories || !Array.isArray(categories) || categories.length === 0 || !$parentElement) {
        return;
    }

    categories.forEach(function (category) {
        if (!category) return;

        // Determine if this category has children
        var hasChildren = category.children && Array.isArray(category.children) && category.children.length > 0;

        // Create the list item
        var $li = $('<li></li>');

        // Create the category node div (holds the elements for this category)
        var $nodeDiv = $('<div class="category-node"></div>');

        // Add expander button (+ or -) if category has children
        if (hasChildren) {
            $nodeDiv.append('<span class="expander">+</span>');
        } else {
            $nodeDiv.append('<span class="expander" style="visibility:hidden">+</span>');
        }

        // Add folder icon - different icon based on whether it has children
        var folderIcon = hasChildren ? 'fa-folder' : 'fa-folder-o';
        $nodeDiv.append('<i class="fa ' + folderIcon + ' category-icon"></i>');

        // Add checkbox for selection
        var categoryId = category.category_id || category.id;
        $nodeDiv.append('<input type="checkbox" class="category-checkbox" name="category[]" value="' + categoryId + '" data-category-id="' + categoryId + '">');

        // Format category name and any additional information
        var labelContent = category.name;

        // Add count information if available 
        if (category.count !== undefined) {
            labelContent += ' <span class="text-muted">(' + category.count + ')</span>';
        }

        // Create and append the label
        $nodeDiv.append('<span class="category-label" data-category-id="' + categoryId + '">' + labelContent + '</span>');

        // Append the node div to the list item
        $li.append($nodeDiv);

        // Process children if this category has any
        if (hasChildren) {
            // Sort children alphabetically
            category.children.sort(function (a, b) {
                return (a.name || '').localeCompare(b.name || '');
            });

            // Create child container elements
            var $childDiv = $('<div class="child-categories"></div>');
            var $childUl = $('<ul class="list-unstyled"></ul>');

            // Build children recursively
            buildCategoryTree(category.children, $childUl);

            // Append children
            $childDiv.append($childUl);
            $li.append($childDiv);
        }

        // Append the completed list item to parent
        $parentElement.append($li);
    });
}

/**
 * Initialize event handlers for the category tree
 */
function initializeTreeHandlers() {
    // Handle expander clicks (+ and - buttons)
    $('.category-tree .expander').on('click', function (e) {
        e.stopPropagation();
        var $this = $(this);
        var $childCategories = $this.closest('li').find('> .child-categories');
        var $folderIcon = $this.siblings('.category-icon');

        if ($childCategories.hasClass('expanded')) {
            // Collapse
            $childCategories.removeClass('expanded');
            $this.text('+');
            $folderIcon.removeClass('fa-folder-open').addClass('fa-folder');
        } else {
            // Expand
            $childCategories.addClass('expanded');
            $this.text('-');
            $folderIcon.removeClass('fa-folder').addClass('fa-folder-open');
        }
    });

    // Handle category label clicks (toggle checkbox)
    $('.category-tree .category-label').on('click', function () {
        var $checkbox = $(this).siblings('.category-checkbox');
        $checkbox.prop('checked', !$checkbox.prop('checked')).trigger('change');
    });

    // Handle checkbox changes
    $('.category-tree .category-checkbox').on('change', function () {
        // Ensure we have an array to store selected categories
        window.DraftWriter = window.DraftWriter || {};
        window.DraftWriter.publish = window.DraftWriter.publish || {};
        window.DraftWriter.publish.selectedCategoryIds = window.DraftWriter.publish.selectedCategoryIds || [];

        var categoryId = $(this).data('category-id');
        var categoryName = $(this).siblings('.category-label').text().split('(')[0].trim();

        if ($(this).prop('checked')) {
            // Add to selected categories
            if (!window.DraftWriter.publish.selectedCategoryIds.includes(categoryId)) {
                window.DraftWriter.publish.selectedCategoryIds.push(categoryId);
            }

            // Trigger a custom event to notify other parts of the system
            $(document).trigger('categorySelected', [categoryId, categoryName]);
        } else {
            // Remove from selected categories
            window.DraftWriter.publish.selectedCategoryIds = window.DraftWriter.publish.selectedCategoryIds.filter(id => id !== categoryId);

            // Trigger a custom event
            $(document).trigger('categoryUnselected', [categoryId]);
        }

        // Update selected categories display
        updateSelectedCategoriesDisplay();
    });

    // Implement expand all button
    $('#expand_all_categories').on('click', function () {
        $('.category-tree .expander').each(function () {
            var $this = $(this);
            var $childCategories = $this.closest('li').find('> .child-categories');
            var $folderIcon = $this.siblings('.category-icon');

            if (!$childCategories.hasClass('expanded') && $this.text() === '+') {
                $childCategories.addClass('expanded');
                $this.text('-');
                $folderIcon.removeClass('fa-folder').addClass('fa-folder-open');
            }
        });
    });

    // Implement collapse all button
    $('#collapse_all_categories').on('click', function () {
        $('.category-tree .expander').each(function () {
            var $this = $(this);
            var $childCategories = $this.closest('li').find('> .child-categories');
            var $folderIcon = $this.siblings('.category-icon');

            if ($childCategories.hasClass('expanded') && $this.text() === '-') {
                $childCategories.removeClass('expanded');
                $this.text('+');
                $folderIcon.removeClass('fa-folder-open').addClass('fa-folder');
            }
        });
    });

    // Implement search functionality
    $('#category_search').on('input', function () {
        var searchText = $(this).val().toLowerCase();

        if (searchText.length === 0) {
            // Show all categories when search is empty
            $('.category-tree li').show();
            return;
        }

        // Hide all categories first
        $('.category-tree li').hide();

        // Show categories matching search and their parents
        $('.category-tree .category-label').each(function () {
            if ($(this).text().toLowerCase().indexOf(searchText) > -1) {
                var $categoryItem = $(this).closest('li');

                // Show this category
                $categoryItem.show();

                // Show all parent categories
                $categoryItem.parents('li').show();

                // Make sure parent categories are expanded
                $categoryItem.parents('li').each(function () {
                    var $expander = $(this).find('> .category-node > .expander');
                    var $childCategories = $(this).find('> .child-categories');
                    var $folderIcon = $expander.siblings('.category-icon');

                    $childCategories.addClass('expanded');
                    $expander.text('-');
                    $folderIcon.removeClass('fa-folder').addClass('fa-folder-open');
                });
            }
        });
    });
}

/**
 * Update the display of selected categories
 */
function updateSelectedCategoriesDisplay() {
    var $categoryInfo = $('#selected_category_info');
    var selectedCategoryIds = window.DraftWriter && window.DraftWriter.publish ? window.DraftWriter.publish.selectedCategoryIds || [] : [];
    
    if (selectedCategoryIds.length === 0) {
        // Hide the info if no categories selected
        $categoryInfo.removeClass('active');
        return;
    }
    
    // Get names of selected categories
    var selectedCategories = [];
    selectedCategoryIds.forEach(function(categoryId) {
        var $checkbox = $('.category-tree .category-checkbox[data-category-id="' + categoryId + '"]');
        if ($checkbox.length) {
            var categoryName = $checkbox.siblings('.category-label').text().split('(')[0].trim();
            selectedCategories.push(categoryName);
        }
    });
    
    // Update the display
    if (selectedCategories.length > 0) {
        var categoryText = selectedCategories.join(', ');
        $('#selected_category_name').text(categoryText);
        $categoryInfo.addClass('active');
    } else {
        $categoryInfo.removeClass('active');
    }
}

/**
 * TRANG TRÍ (UI/PRESENTATION FUNCTION)
 * Khởi tạo select box cho tags
 */
function initTagsSelect(tags) {
    console.log('Initializing tags select with data:', tags);

    var $select = $('#tags-select');

    // Clear any existing options
    $select.empty();

    // If no tags provided, show a placeholder
    if (!tags || !Array.isArray(tags) || tags.length === 0) {
        $select.html('<option value="" disabled>No tags available</option>');
        return;
    }

    // Sort tags alphabetically
    tags.sort(function (a, b) {
        var aName = typeof a === 'object' ? a.name || '' : a;
        var bName = typeof b === 'object' ? b.name || '' : b;
        return aName.localeCompare(bName);
    });

    // Add each tag as an option
    tags.forEach(function (tag) {
        var tagValue, tagText;

        // Handle different data structures
        if (typeof tag === 'object') {
            tagValue = tag.id || tag.tag_id || tag.value || tag.name;
            tagText = tag.name || tag.text || tag.tag_name;
        } else {
            tagValue = tag;
            tagText = tag;
        }

        if (tagValue && tagText) {
            $select.append(new Option(tagText, tagValue));
        }
    });

    // Initialize Select2 if available
    if ($.fn.select2) {
        try {
            $select.select2({
                placeholder: 'Select or type to add tags',
                tags: true,
                multiple: true,
                tokenSeparators: [',', ' '],
                width: '100%'
            });

            console.log('Select2 initialized for tags');
        } catch (e) {
            console.error('Error initializing Select2 for tags:', e);
        }
    } else {
        console.warn('Select2 not available, using standard multi-select');
        $select.attr('multiple', 'multiple');
    }

    // Show success message
    alert_float('success', 'Tags loaded successfully');
}


/**
 * TRANG TRÍ (UI/PRESENTATION FUNCTION)
 * Cập nhật prefix permalink dựa trên platform
 */
function updatePermalinkPrefix(platform) {
    var prefix = '';

    if (platform === 'wordpress') {
        prefix = 'https://example.com/';
    } else if (platform === 'haravan') {
        prefix = 'https://example.com/blogs/news/';
    } else {
        prefix = 'https://example.com/';
    }

    $('#permalink-prefix').text(prefix);
}

/**
 * TRANG TRÍ (UI/PRESENTATION FUNCTION)
 * Cập nhật xem trước bài đăng
 */
function updatePostPreview() {
    var title = $('#draft-title').val() || 'Tiêu đề bài viết';
    var content = '';
    var featureImage = $('#feature-image-url').val();
    var description = $('#draft-description').val() || $('#draft-excerpt').val() || '';

    // Get content from editor
    if (tinymce && tinymce.activeEditor) {
        content = tinymce.activeEditor.getContent() || '<p>Nội dung bài viết sẽ hiển thị ở đây...</p>';
    } else {
        content = '<p>Nội dung bài viết sẽ hiển thị ở đây...</p>';
    }

    // Update feature image preview in publish tab
    if (featureImage) {
        // Update image in publish preview
        $('#post-preview-container #feature-image').attr('src', featureImage);
        $('#post-preview-container .feature-image-preview-container').removeClass('hide');
        $('#remove-feature-image').removeClass('hide');
    } else {
        // Use placeholder image
        $('#post-preview-container #feature-image').attr('src', module_dir_url + 'assets/img/placeholder-image.jpg');
        $('#remove-feature-image').addClass('hide');
    }

    // Get selected categories
    var categories = [];
    $('#categories-tree input:checked').each(function () {
        categories.push($(this).closest('.category-node').find('label').text());
    });

    // Update UI
    $('#preview-title').text(title);
    $('#preview-date').text(new Date().toLocaleDateString());
    $('#preview-author').text('Admin'); // Could be dynamic
    $('#preview-categories').text(categories.join(', ') || 'Chưa chọn danh mục');
    
    // Add description to preview
    if (description) {
        if (!$('#preview-description').length) {
            // If preview-description element doesn't exist, add it after the title
            $('#preview-content').before('<div id="preview-description" class="post-description"></div>');
        }
        $('#preview-description').html(description).show();
    } else {
        // Hide the description if empty
        if ($('#preview-description').length) {
            $('#preview-description').hide();
        }
    }

    // Prepare preview content
    var previewContent = $('<div>').html(content);
    // Remove any scripts for security
    previewContent.find('script').remove();

    // Display full content without truncation
    $('#preview-content').html(previewContent.html());
}

/**
 * @UI_PRESENTATION_FUNCTION: Cập nhật thông báo loading
 * Thay đổi nội dung thông báo trong overlay loading
 */
function updateLoadingIndicator(message) {
    $('.editor-loading .loading-message').text(message);
}

/**
 * @UI_PRESENTATION_FUNCTION: Ẩn chỉ báo loading
 * Xóa overlay loading khỏi giao diện
 */
function hideLoadingIndicator() {
    $('.editor-loading').remove();
}


/**
 * @UI_PRESENTATION_FUNCTION: Cập nhật thời gian lưu cuối cùng
 * Hiển thị thời gian lưu gần nhất trên giao diện
 */
function updateLastSavedTime() {
    $('#last-saved-time').text(lastSaveTime.toLocaleTimeString());
}

/**
 * @UI_PRESENTATION_FUNCTION: Cập nhật thông tin bản nháp
 * Cập nhật UI với thông tin mới nhất về bản nháp hiện tại
 */
function updateDraftInfo(data) {
    if (data.draft_id) {
        $('#current-draft-id').val(data.draft_id);
    }

    if (data.draft_title) {
        $('#current-draft-name').text(data.draft_title);
    } else if (data.title) {
        $('#current-draft-name').text(data.title);
    }

    // Update other draft info in the UI...
    if (data.version) {
        $('#draft-version').text('v' + data.version);
    }

    if (data.status) {
        $('#draft-status').text(data.status)
            .removeClass('badge-warning badge-success')
            .addClass(data.status === 'final' ? 'badge-success' : 'badge-warning');
    }
}

/**
 * @UI_PRESENTATION_FUNCTION: Cập nhật metadata
 * Cập nhật các trường metadata SEO trong giao diện người dùng
 */
function updateMetadata(data) {
    // Update SEO metadata if available
    if (data.meta_description) {
        $('#seo-meta-description').val(data.meta_description);
    }

    // Update keywords if available
    if (data.keywords) {
        $('#seo-target-keyword').val(data.keywords);
    }
}

/**
 * TRANG TRÍ (UI/PRESENTATION FUNCTION)
 * Hiển thị phân tích cơ bản
 */
function displayAnalysis(analysis) {
    // Hiển thị số từ và thời gian đọc
    if ($('#word-count-container').length) {
        $('#word-count').text(analysis.wordCount);
        $('#reading-time').text(analysis.readingTime + ' min');
    } else {
        // Thêm container nếu chưa có
        const analysisHtml = `
                    <div id="word-count-container" class="mt-3">
                        <div class="row">
                            <div class="col-md-6">
                                <span><i class="fa fa-file-text-o"></i> Word count: <strong id="word-count">${analysis.wordCount}</strong></span>
                            </div>
                            <div class="col-md-6">
                                <span><i class="fa fa-clock-o"></i> Reading time: <strong id="reading-time">${analysis.readingTime} min</strong></span>
                            </div>
                        </div>
                    </div>
                `;
        $('#editor-content').after(analysisHtml);
    }
}

/**
 * @UI_PRESENTATION_FUNCTION: Hiển thị chỉ báo loading 
 * Tạo và hiển thị overlay loading với thông báo
 */
function showLoadingIndicator(message = 'Loading...') {
    if (!$('#loading-overlay').length) {
        $('body').append('<div id="loading-overlay" class="overlay"><div class="spinner-container"><div class="spinner"></div><div class="message">' + (message || 'Loading...') + '</div></div></div>');
    } else {
        updateLoadingIndicator(message);
    }
}

/**
 * HIỂN THỊ (DISPLAY FUNCTION)
 * Cập nhật thông báo đang tải
 */
function updateLoadingIndicator(message) {
    $('#loading-overlay .message').text(message || 'Loading...');
}

/**
 * HIỂN THỊ (DISPLAY FUNCTION)
 * Ẩn trạng thái đang phân tích
 */
function hideLoadingIndicator() {
    $('#loading-overlay').remove();
}

/**
 * TRANG TRÍ (UI/PRESENTATION FUNCTION)
 * Hiển thị modal chọn ảnh từ Topic Composer
 */
function showTopicComposerImagesModal() {
    // Lấy topic ID
    const topicId = $('#topicid').val() || $('input[name="topic_id"]').val();

    // Lấy danh sách ảnh từ Topic Composer
    const images = getTopicComposerImages();

    // Nếu không có ảnh, hiển thị thông báo
    if (!images || images.length === 0) {
        alert_float('warning', 'No images found in Topic Composer');
        return;
    }

    // Tạo modal HTML
    const modalHtml = `
    <div class="modal fade" id="composer-images-modal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">Select Feature Image from Topic Composer</h4>
                </div>
                <div class="modal-body">
                    <div class="row composer-images-container">
                        ${images.map((image, index) => `
                            <div class="col-md-3 col-sm-4 col-xs-6 composer-image-item" data-url="${image.url}">
                                <div class="panel panel-default">
                                    <div class="panel-body p-2">
                                        <div class="image-wrap" style="height:150px;overflow:hidden;position:relative;">
                                            <img src="${image.url}" alt="${image.title}" class="img-responsive" style="max-height:150px;margin:0 auto;">
                                            <div class="image-loading-overlay" style="display:none;position:absolute;top:0;left:0;right:0;bottom:0;background:rgba(255,255,255,0.7);display:flex;align-items:center;justify-content:center;">
                                                <i class="fa fa-spinner fa-spin fa-2x"></i>
                                            </div>
                                        </div>
                                        <div class="image-actions mt-2 text-center">
                                            <button type="button" class="btn btn-primary btn-xs select-composer-image" data-url="${image.url}">
                                                Select
                                            </button>
                                            <div class="image-status-indicator text-center mt-1" style="min-height:20px;"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                    ${images.length === 0 ? '<div class="text-center">No images found in Topic Composer</div>' : ''}
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    `;

    // Thêm modal vào body nếu chưa tồn tại
    if ($('#composer-images-modal').length === 0) {
        $('body').append(modalHtml);
    } else {
        $('#composer-images-modal').html($(modalHtml).html());
    }

    // Hiển thị modal
    $('#composer-images-modal').modal('show');

    // Kiểm tra từng ảnh xem đã tồn tại trong external data chưa
    images.forEach(async (image) => {
        const $imageItem = $(`.composer-image-item[data-url="${image.url}"]`);
        const $statusIndicator = $imageItem.find('.image-status-indicator');

        // Hiển thị loading
        $imageItem.find('.image-loading-overlay').show();

        try {
            // Kiểm tra xem ảnh đã tồn tại trong external data chưa
            console.log("checkImageExternalData 1535");
            const checkResult = await checkImageExternalData(image.url, topicId);

            // Ẩn loading
            $imageItem.find('.image-loading-overlay').hide();

            // Cập nhật UI
            if (checkResult.exists) {
                $statusIndicator.html('<span class="label label-success"><i class="fa fa-check"></i> Already on server</span>');
            } else {
                $statusIndicator.html('<span class="label label-default">Not downloaded yet</span>');
            }
        } catch (error) {
            // Ẩn loading và hiển thị lỗi
            $imageItem.find('.image-loading-overlay').hide();
            $statusIndicator.html('<span class="label label-danger">Error checking status</span>');
            console.error('Error checking image status:', error);
        }
    });

    // Xử lý sự kiện khi chọn ảnh
    $(document).off('click', '.select-composer-image').on('click', '.select-composer-image', async function () {
        const imageUrl = $(this).data('url');
        const $imageItem = $(this).closest('.composer-image-item');

        // Hiển thị loading
        $imageItem.find('.image-loading-overlay').show();
        $(this).prop('disabled', true);

        try {
            // Kiểm tra xem ảnh đã tồn tại trong external data chưa
            console.log("checkImageExternalData 1566");
            const checkResult = await checkImageExternalData(imageUrl, topicId);

            if (checkResult.exists) {
                // Nếu ảnh đã tồn tại, sử dụng URL từ server
                $('#feature-image-url').val(checkResult.data);
                $('#feature-image').attr('src', checkResult.data);
                $('.feature-image-preview').removeClass('hide');
                $('#remove-feature-image').removeClass('hide');
                $('#composer-images-modal').modal('hide');

                // Thông báo thành công
                alert_float('success', 'Feature image selected successfully');
            } else {
                // Nếu chưa tồn tại, cần tải ảnh lên server
                // Sử dụng TopicComposerProcessor để tải ảnh
                saveImageToServer(imageUrl, topicId).then((result) => {
                    if (result.success) {
                        // Cập nhật UI
                        $('#feature-image-url').val(result.url);
                        $('#feature-image').attr('src', result.url);
                        $('.feature-image-preview').removeClass('hide');
                        $('#remove-feature-image').removeClass('hide');
                        $('#composer-images-modal').modal('hide');

                        // Thông báo thành công
                        alert_float('success', 'Image downloaded and selected successfully');
                    } else {
                        throw new Error(result.message || 'Failed to download image');
                    }
                }).catch((error) => {
                    alert_float('danger', error.message || 'Error downloading image');
                    $imageItem.find('.image-loading-overlay').hide();
                    $(this).prop('disabled', false);
                });
            }
        } catch (error) {
            // Hiển thị lỗi
            alert_float('danger', 'Error selecting image');
            console.error('Error selecting image:', error);
            $imageItem.find('.image-loading-overlay').hide();
            $(this).prop('disabled', false);
        }
    });
}

/**
 * TRANG TRÍ (UI/PRESENTATION FUNCTION)
 * Tải feature image từ external data
 */
function loadFeatureImageFromExternalData() {
    const topicId = $('#topicid').val() || $('input[name="topic_id"]').val();

    if (!topicId) {
        console.log('Topic ID not found - loadFeatureImageFromExternalData');
        return;
    }

    console.log('Loading feature image for topic ID:', topicId);

    // Hiển thị loading indicator trong preview
    $('.feature-image-preview').removeClass('hide');
    $('#feature-image').css('opacity', '0.5');
    $('<div id="feature-image-loading" style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);"><i class="fa fa-spinner fa-spin fa-2x"></i></div>')
        .appendTo('.feature-image-preview');

    // Gọi API để lấy dữ liệu feature_image
    $.ajax({
        url: admin_url + 'topics/get_external_data',
        type: 'POST',
        data: {
            topic_id: topicId,
            rel_type: 'feature_image',
            rel_id: '1' // Thêm rel_id là "1" cho feature image
        },
        success: function (response) {
            console.log('Feature image response received:', response);
            try {
                if (typeof response === 'string') {
                    response = JSON.parse(response);
                }

                if (response.success && response.data) {
                    // Cập nhật giao diện với ảnh đã lưu
                    const imageUrl = response.data.rel_data;
                    $('#feature-image-url').val(imageUrl);
                    $('#feature-image').attr('src', imageUrl);
                    $('.feature-image-preview').removeClass('hide');
                    $('#remove-feature-image').removeClass('hide');

                    console.log('Feature image loaded successfully:', imageUrl);
                } else {
                    // Không tìm thấy feature image - KHÔNG tự động hiển thị modal chọn ảnh
                    console.log('No feature_image found in external data');
                    // Ẩn preview nếu không có ảnh
                    $('.feature-image-preview').addClass('hide');
                }
            } catch (e) {
                console.error('Error parsing feature image response:', e);
                // Ẩn preview nếu có lỗi
                $('.feature-image-preview').addClass('hide');
            }
        },
        error: function (xhr, status, error) {
            console.error('AJAX error loading feature image:', error);
            // Ẩn preview nếu có lỗi
            $('.feature-image-preview').addClass('hide');
        },
        complete: function () {
            // Xóa loading indicator
            $('#feature-image-loading').remove();
            $('#feature-image').css('opacity', '1');
        }
    });
}

/**
 * Hàm hiển thị modal để chọn ảnh từ danh sách ảnh đã tải lên
 * Chỉ được gọi khi người dùng nhấn nút "Pick from Downloaded Images"
 * 
 * @param {string} topicId - ID của topic
 */
function tryLoadImageFromExternalData(topicId) {
    console.log('Loading external images for topic ID:', topicId);

    // Hiển thị loading indicator
    const loadingHtml = `
    <div id="image-loading-indicator" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; 
        background: rgba(255,255,255,0.7); z-index: 9999; display: flex; align-items: center; 
        justify-content: center; flex-direction: column;">
        <i class="fa fa-spinner fa-spin fa-3x"></i>
        <p class="mt-3">Loading images...</p>
    </div>`;

    if ($('#image-loading-indicator').length === 0) {
        $('body').append(loadingHtml);
    }

    // Gọi API để lấy tất cả external data với rel_type = 'image'
    $.ajax({
        url: admin_url + 'topics/get_external_data_by_type',
        type: 'POST',
        data: {
            topic_id: topicId,
            rel_type: 'image'
        },
        success: function (response) {
            // Xóa loading indicator
            $('#image-loading-indicator').remove();

            try {
                if (typeof response === 'string') {
                    response = JSON.parse(response);
                }

                console.log('External images response:', response);

                if (response.success && response.data && response.data.length > 0) {
                    // Hiển thị modal cho người dùng chọn ảnh
                    showImageSelectionModal(response.data);
                } else {
                    alert_float('warning', 'No images found in external data');
                    console.log('No suitable images found in external data');
                }
            } catch (e) {
                console.error('Error parsing external images response:', e);
                alert_float('danger', 'Error loading images from external data');
            }
        },
        error: function (xhr, status, error) {
            // Xóa loading indicator
            $('#image-loading-indicator').remove();

            console.error('AJAX error loading images from external data:', error, xhr.responseText);
            alert_float('danger', 'Error loading images from external data: ' + error);
        }
    });
}

/**
 * Hiển thị modal cho người dùng chọn ảnh từ danh sách
 * 
 * @param {Array} availableImages - Danh sách các ảnh
 */
function showImageSelectionModal(availableImages) {
    console.log('Showing image selection modal with', availableImages.length, 'images');

    // Thêm CSS để cải thiện giao diện modal
    if (!$('#feature-image-select-styles').length) {
        $('<style id="feature-image-select-styles">')
            .text(`
                .image-selection-panel {
                    transition: all 0.2s ease;
                    border: 2px solid #eee;
                    margin-bottom: 15px;
                }
                .image-selection-panel:hover {
                    border-color: #03a9f4;
                    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                }
                .image-selection-panel .panel-body {
                    padding: 10px;
                }
                .select-as-feature-btn {
                    margin-top: 10px;
                    width: 100%;
                }
                .available-images-container {
                    max-height: 500px;
                    overflow-y: auto;
                    margin: 10px 0;
                }
                .modal-title {
                    color: #03a9f4;
                }
            `)
            .appendTo('head');
    }

    // Tạo modal xác nhận
    var modalHtml = `
    <div class="modal fade" id="confirm-feature-image-modal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="fa fa-picture-o"></i> Select Feature Image</h4>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fa fa-info-circle"></i> Select an image to use as feature image for this content
                    </div>
                    <div class="row available-images-container">`;

    // Thêm từng ảnh vào modal
    $.each(availableImages, function (index, imageData) {
        modalHtml += `
        <div class="col-md-4 col-sm-6 mb-3">
            <div class="panel panel-default image-selection-panel" data-image-url="${imageData.rel_data}" data-image-id="${imageData.id}">
                <div class="panel-body text-center">
                    <div style="height: 150px; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                        <img src="${imageData.rel_data}" alt="Image ${index + 1}" style="max-width: 100%; max-height: 150px;">
                    </div>
                    <button class="btn btn-primary btn-sm select-as-feature-btn" data-image-url="${imageData.rel_data}" data-image-id="${imageData.id}">
                        <i class="fa fa-check"></i> Use as Feature Image
                    </button>
                </div>
            </div>
        </div>`;
    });

    modalHtml += `
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>`;

    // Thêm modal vào body nếu chưa tồn tại
    if ($('#confirm-feature-image-modal').length === 0) {
        $('body').append(modalHtml);
    } else {
        $('#confirm-feature-image-modal').html($(modalHtml).html());
    }

    // Hiển thị modal
    $('#confirm-feature-image-modal').modal('show');

    // Xử lý sự kiện khi click vào nút chọn ảnh
    $('.select-as-feature-btn').off('click').on('click', function () {
        const imageUrl = $(this).data('image-url');
        const imageId = $(this).data('image-id');
        const topicId = $('#topicid').val() || $('input[name="topic_id"]').val();

        console.log('Selected image as feature image:', { imageUrl, imageId, topicId });

        // Cập nhật giao diện với ảnh đã chọn
        $('#feature-image-url').val(imageUrl);
        $('#feature-image').attr('src', imageUrl);
        $('.feature-image-preview').removeClass('hide');
        $('#remove-feature-image').removeClass('hide');

        // Lưu lại ảnh này vào external data với rel_type = 'feature_image'
        saveImageExternalData(imageUrl);

        // Đóng modal
        $('#confirm-feature-image-modal').modal('hide');
    });
}

/**
 * TRANG TRÍ (UI/PRESENTATION FUNCTION)
 * Lưu feature image vào external data
 * 
 * @param {string} imageUrl - URL của feature image
 */
function saveImageExternalData(imageUrl) {
    const topicId = $('#topicid').val() || $('input[name="topic_id"]').val();

    if (!topicId || !imageUrl) {
        console.error('Missing required data for saving feature image:', { topicId, imageUrl });
        alert_float('warning', 'Could not save feature image: Missing data');
        return;
    }

    console.log('Saving feature image to external data:', { topicId, imageUrl });

    // Hiển thị loading indicator
    const $featureImage = $('#feature-image');
    $featureImage.css('opacity', '0.7');
    const $loading = $('<div id="save-image-loading" style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);"><i class="fa fa-spinner fa-spin fa-2x"></i></div>');
    $('.feature-image-preview').append($loading);

    // Gọi API để lưu dữ liệu - CHÚ Ý: Thêm rel_id là "1" cho feature_image
    $.ajax({
        url: admin_url + 'topics/save_external_data',
        type: 'POST',
        data: {
            topic_id: topicId,
            rel_type: 'feature_image',
            rel_id: '1', // Thêm rel_id là "1" cho feature image
            rel_data: imageUrl
        },
        success: function (response) {
            try {
                if (typeof response === 'string') {
                    response = JSON.parse(response);
                }

                if (response.success) {
                    console.log('Feature image saved to external data successfully:', response);
                    alert_float('success', 'Feature image saved successfully');
                } else {
                    console.error('Error saving feature image:', response.message);
                    alert_float('danger', 'Error saving feature image: ' + (response.message || 'Unknown error'));
                }
            } catch (e) {
                console.error('Error parsing response when saving feature image:', e);
                alert_float('danger', 'Error processing server response');
            }
        },
        error: function (xhr, status, error) {
            console.error('AJAX error saving feature image:', error, xhr.responseText);
            alert_float('danger', 'Error saving feature image: ' + error);
        },
        complete: function () {
            // Restore UI
            $featureImage.css('opacity', '1');
            $('#save-image-loading').remove();
        }
    });
}

/**
 * Trực tiếp hiển thị modal chọn ảnh từ external data
 * Hàm này hiển thị ảnh từ dữ liệu đã tải về khi người dùng nhấn nút "Pick from Downloaded Images"
 */
window.pickFromDownloadedImages = function () {
    const topicId = $('#topicid').val() || $('input[name="topic_id"]').val();

    if (!topicId) {
        alert_float('warning', 'Topic ID not found');
        return;
    }

    // Gọi hàm hiển thị ảnh từ external data - chỉ khi người dùng nhấn nút
    tryLoadImageFromExternalData(topicId);
};

/**
 * TRANG TRÍ (UI/PRESENTATION FUNCTION)
 * Xóa feature image
 */
function removeFeatureImage() {
    const topicId = $('#topicid').val() || $('input[name="topic_id"]').val();

    if (!topicId) {
        console.log('Topic ID not found - removeFeatureImage');
        return;
    }

    // Reset UI
    $('#feature-image-url').val('');
    $('#feature-image').attr('src', '');
    $('.feature-image-preview').addClass('hide');
    $('#remove-feature-image').addClass('hide');

    // Xóa dữ liệu trong external data
    $.ajax({
        url: admin_url + 'topics/delete_external_data',
        type: 'POST',
        data: {
            topic_id: topicId,
            rel_type: 'feature_image',
            rel_id: '1' // Thêm rel_id là "1" cho feature image
        },
        success: function (response) {
            try {
                if (typeof response === 'string') {
                    response = JSON.parse(response);
                }

                if (response.success) {
                    console.log('Feature image removed successfully');
                    alert_float('success', 'Feature image removed');
                } else {
                    console.error('Error removing feature image:', response.message);
                }
            } catch (e) {
                console.error('Error parsing response:', e);
            }
        },
        error: function (xhr, status, error) {
            console.error('AJAX error removing feature image:', error);
        }
    });
}

/**
 * Hàm để thêm nút chọn ảnh tải về bên cạnh nút chọn ảnh hiện tại
 * Được gọi sau khi trang đã tải xong
 */
function addFeatureImageButtons() {
    // Tìm container chứa nút chọn feature image
    const $container = $('.feature-image-buttons');

    // Nếu container không tồn tại, thử tìm nút chọn ảnh hiện tại
    if ($container.length === 0) {
        const $selectButton = $('button[onclick="selectFeatureImage()"], a[onclick="selectFeatureImage()"]');

        if ($selectButton.length) {
            // Tạo container mới để chứa các nút
            const $newContainer = $('<div class="feature-image-buttons btn-group"></div>');

            // Clone nút select hiện tại và thêm vào container
            const $clonedButton = $selectButton.clone();
            $newContainer.append($clonedButton);

            // Tạo nút mới để chọn từ ảnh đã tải về
            const $downloadedButton = $('<button type="button" class="btn btn-info" onclick="pickFromDownloadedImages()">' +
                '<i class="fa fa-download"></i> Pick from Downloaded Images</button>');
            $newContainer.append($downloadedButton);

            // Thay thế nút cũ bằng container mới
            $selectButton.replaceWith($newContainer);
        }
    } else {
        // Nếu container đã tồn tại, kiểm tra xem nút đã có chưa
        if ($container.find('[onclick="pickFromDownloadedImages()"]').length === 0) {
            // Thêm nút mới vào container
            const $downloadedButton = $('<button type="button" class="btn btn-info" onclick="pickFromDownloadedImages()">' +
                '<i class="fa fa-download"></i> Pick from Downloaded Images</button>');
            $container.append($downloadedButton);
        }
    }
}

/**
 * TRANG TRÍ (UI/PRESENTATION FUNCTION)
 * Chọn feature image - Hàm ghi đè trực tiếp
 * Lưu ý: Hàm này được gọi toàn cục, nên phải định nghĩa ở window
 */
window.selectFeatureImage = function () {
    // Kiểm tra xem Topic Composer có dữ liệu không
    const hasTopicComposerData = typeof window.TopicComposer !== 'undefined' &&
        window.TopicComposer.items &&
        window.TopicComposer.items.length > 0;

    if (!hasTopicComposerData) {
        // Nếu không có dữ liệu Topic Composer, hiển thị Media Library bình thường
        // Giả định rằng đây là cách xử lý mặc định trước đó
        if (typeof appMediaLibrary !== 'undefined') {
            appMediaLibrary.setMediaTypeUpload();
            appMediaLibrary.showMediaLibrary();

            // Thiết lập callback khi chọn ảnh
            appMediaLibrary.onMediaLibraryItemSelect = function (selectedItems) {
                if (selectedItems && selectedItems.length > 0) {
                    const selectedItem = selectedItems[0];

                    // Cập nhật UI với ảnh đã chọn
                    $('#feature-image-url').val(selectedItem.path);
                    $('#feature-image').attr('src', selectedItem.path);
                    $('.feature-image-preview').removeClass('hide');
                    $('#remove-feature-image').removeClass('hide');

                    // Lưu vào external data
                    saveImageExternalData(selectedItem.path);
                }
            };
        } else {
            console.warn('Media Library not available, using custom file selector');

            // Sử dụng Feature Image Selector thay thế
            const popupWidth = 1100;
            const popupHeight = 800;
            const left = (window.screen.width - popupWidth) / 2;
            const top = (window.screen.height - popupHeight) / 2;

            const selectorUrl = admin_url + 'topics/feature_image_selector';
            const popupWindow = window.open(
                selectorUrl,
                'featureImageSelector',
                `width=${popupWidth},height=${popupHeight},top=${top},left=${left},resizable=yes,scrollbars=yes`
            );

            // Xử lý khi cửa sổ popup đóng
            if (popupWindow) {
                // Lắng nghe thông điệp từ cửa sổ popup
                window.addEventListener('message', function (event) {
                    if (event.origin === window.location.origin &&
                        event.data &&
                        event.data.messageType === 'fileSelected') {

                        const file = event.data.file;
                        if (file && file.url) {
                            // Cập nhật UI với ảnh đã chọn
                            $('#feature-image-url').val(file.url);
                            $('#feature-image').attr('src', file.url);
                            $('.feature-image-preview').removeClass('hide');
                            $('#remove-feature-image').removeClass('hide');

                            // Lưu vào external data
                            saveImageExternalData(file.url);
                        }
                    }
                }, false);

                popupWindow.focus();
            } else {
                alert_float('warning', 'Could not open file selector. Please check your popup blocker settings.');
            }
        }
        return;
    }

    // Nếu có dữ liệu Topic Composer, hiển thị modal tùy chọn
    const modalHtml = `
    <div class="modal fade" id="select-image-source-modal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">Select Image Source</h4>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 text-center">
                            <div class="image-source-option" data-source="composer">
                                <i class="fa fa-newspaper-o fa-4x mb-2"></i>
                                <h4>From Topic Composer</h4>
                                <p class="text-muted">Select from images in Topic Composer</p>
                                <button class="btn btn-primary mt-2">Select</button>
                            </div>
                        </div>
                        <div class="col-md-6 text-center">
                            <div class="image-source-option" data-source="media">
                                <i class="fa fa-picture-o fa-4x mb-2"></i>
                                <h4>From Media Library</h4>
                                <p class="text-muted">Upload or select from media library</p>
                                <button class="btn btn-info mt-2">Select</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    `;

    // Thêm modal vào body nếu chưa tồn tại
    if ($('#select-image-source-modal').length === 0) {
        $('body').append(modalHtml);
    }

    // Hiển thị modal
    $('#select-image-source-modal').modal('show');

    // Thêm CSS cho modal nếu chưa có
    if (!$('#image-source-modal-styles').length) {
        $('<style>')
            .attr('id', 'image-source-modal-styles')
            .text(`
                .image-source-option {
                    border: 2px solid #eee;
                    border-radius: 5px;
                    padding: 20px;
                    margin-bottom: 15px;
                    cursor: pointer;
                    transition: all 0.2s ease;
                }
                .image-source-option:hover {
                    border-color: #03a9f4;
                    background-color: #f9f9f9;
                }
                .image-source-option i {
                    color: #666;
                    margin-bottom: 10px;
                }
                .image-source-option:hover i {
                    color: #03a9f4;
                }
            `)
            .appendTo('head');
    }

    // Xử lý sự kiện khi chọn nguồn ảnh
    $('.image-source-option').off('click').on('click', function () {
        const source = $(this).data('source');

        // Đóng modal
        $('#select-image-source-modal').modal('hide');

        if (source === 'composer') {
            // Hiển thị modal chọn ảnh từ Topic Composer
            showTopicComposerImagesModal();
        } else {
            // Hiển thị Media Library
            if (typeof appMediaLibrary !== 'undefined') {
                appMediaLibrary.setMediaTypeUpload();
                appMediaLibrary.showMediaLibrary();

                // Thiết lập callback khi chọn ảnh
                appMediaLibrary.onMediaLibraryItemSelect = function (selectedItems) {
                    if (selectedItems && selectedItems.length > 0) {
                        const selectedItem = selectedItems[0];

                        // Cập nhật UI với ảnh đã chọn
                        $('#feature-image-url').val(selectedItem.path);
                        $('#feature-image').attr('src', selectedItem.path);
                        $('.feature-image-preview').removeClass('hide');
                        $('#remove-feature-image').removeClass('hide');

                        // Lưu vào external data
                        saveImageExternalData(selectedItem.path);
                    }
                };
            } else {
                console.warn('Media Library not available, using custom file selector');

                // Sử dụng Feature Image Selector thay thế
                const popupWidth = 1100;
                const popupHeight = 800;
                const left = (window.screen.width - popupWidth) / 2;
                const top = (window.screen.height - popupHeight) / 2;

                const selectorUrl = admin_url + 'topics/feature_image_selector';
                const popupWindow = window.open(
                    selectorUrl,
                    'featureImageSelector',
                    `width=${popupWidth},height=${popupHeight},top=${top},left=${left},resizable=yes,scrollbars=yes`
                );

                // Xử lý khi cửa sổ popup đóng
                if (popupWindow) {
                    // Lắng nghe thông điệp từ cửa sổ popup
                    window.addEventListener('message', function (event) {
                        if (event.origin === window.location.origin &&
                            event.data &&
                            event.data.messageType === 'fileSelected') {

                            const file = event.data.file;
                            if (file && file.url) {
                                // Cập nhật UI với ảnh đã chọn
                                $('#feature-image-url').val(file.url);
                                $('#feature-image').attr('src', file.url);
                                $('.feature-image-preview').removeClass('hide');
                                $('#remove-feature-image').removeClass('hide');

                                // Lưu vào external data
                                saveImageExternalData(file.url);
                            }
                        }
                    }, false);

                    popupWindow.focus();
                } else {
                    alert_float('warning', 'Could not open file selector. Please check your popup blocker settings.');
                }
            }
        }
    });
};

// Đảm bảo window.removeFeatureImage cũng là global function
window.removeFeatureImage = removeFeatureImage;

// Thêm khởi tạo nút chọn ảnh khi DOM đã sẵn sàng
$(document).ready(function () {
    // Chờ một chút để đảm bảo các element đã được tạo
    setTimeout(addFeatureImageButtons, 500);
});

/**
 * TRANG TRÍ (UI/PRESENTATION FUNCTION)
 * Reset SEO checklist items to loading state
 */
function resetSEOChecklist() {
    // Get all checklist items
    const checklistItems = $('#seo-checklist-items tr');

    // Reset each item to loading state
    checklistItems.each(function () {
        const icon = $(this).find('i');
        const status = $(this).find('.status');

        // Reset classes and show spinner
        icon.removeClass('fa-check-circle fa-exclamation-triangle fa-times-circle fa-question-circle')
            .addClass('fa-spinner fa-spin')
            .css('color', '');

        // Clear status text
        status.text('').css('color', '');

        // Remove any tooltips
        if (typeof $.fn.tooltip === 'function') {
            $(this).tooltip('destroy');
        }
        $(this).removeAttr('title');
    });

    // Clear suggestions
    $('#seo-suggestions .suggestions-list').html('<div class="text-center"><i class="fa fa-spinner fa-spin"></i> Analyzing...</div>');
}
