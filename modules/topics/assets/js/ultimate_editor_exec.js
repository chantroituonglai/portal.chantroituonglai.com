/**
 * ULTIMATE EDITOR SEO FUNCTIONS
 * File này chứa các hàm liên quan đến phân tích và hiển thị SEO
 * 
 * CÁC NHÓM HÀM:
 * - EXECUTION FUNCTIONS: Hàm thực thi các tác vụ chính, gọi API, xử lý workflow
 * - UI/PRESENTATION FUNCTIONS: Hàm hiển thị, render UI và xử lý hiệu ứng
 * - FUNCTIONAL FUNCTIONS: Hàm xử lý logic, tính toán và xử lý dữ liệu
 * - UTILITY FUNCTIONS: Hàm tiện ích, hỗ trợ các tác vụ phổ biến
 */

// Add at the beginning of the file, near other initialization code

// Check if there's a last created draft ID in sessionStorage on page load
$(document).ready(function () {
    const lastCreatedDraftId = sessionStorage.getItem('last_created_draft_id');
    if (lastCreatedDraftId) {
        console.log('Found last created draft ID in sessionStorage:', lastCreatedDraftId);

        // Clear the stored ID to prevent reloading on future page loads
        sessionStorage.removeItem('last_created_draft_id');

        // Wait for editor to initialize before loading the draft
        const checkEditorInterval = setInterval(function () {
            if (editor && editor.initialized) {
                clearInterval(checkEditorInterval);
                console.log('Editor initialized, loading draft:', lastCreatedDraftId);
                loadDraft(lastCreatedDraftId);
            }
        }, 100);

        // Safety timeout to prevent infinite checking
        setTimeout(function () {
            clearInterval(checkEditorInterval);
        }, 10000);
    }
});

/**
 * UTILITY FUNCTION
 * Kiểm tra và khôi phục editor nếu cần
 * @returns {boolean} True nếu editor khả dụng hoặc đã được khôi phục thành công
 */
function checkAndRecoverEditor() {
    console.log('Checking editor availability...');

    // Kiểm tra xem editor có tồn tại và khả dụng không
    if (typeof editor !== 'undefined' && editor && typeof editor.getContent === 'function') {
        console.log('Editor is available via global editor variable');
        return true;
    }

    // Kiểm tra TinyMCE
    if (typeof tinymce !== 'undefined' && tinymce.activeEditor && tinymce.activeEditor.initialized) {
        console.log('Editor is available via TinyMCE activeEditor');
        window.editor = tinymce.activeEditor; // Đảm bảo tham chiếu editor toàn cục được cập nhật
        return true;
    }

    console.warn('Editor not available, attempting recovery...');

    // Kiểm tra xem phần tử editor có tồn tại không
    if (!$('#editor-content').length) {
        console.error('Editor element #editor-content not found in DOM');
        return false;
    }

    // Thử khôi phục editor
    try {
        // Xóa các instance cũ nếu có
        if (typeof tinymce !== 'undefined' && tinymce.get('editor-content')) {
            tinymce.get('editor-content').remove();
        }

        // Lưu nội dung hiện tại của textarea/div để khôi phục sau này
        const existingContent = $('#editor-content').html() || $('#editor-content').val();

        console.log('Starting editor recovery process...');

        // Thông báo cho người dùng
        alert_float('warning', 'Editor is being recovered. Please wait a moment and try again.');

        // Khôi phục nội dung
        $('#editor-content').html(existingContent);

        // Trả về false vì quá trình khôi phục cần thời gian
        return false;
    } catch (e) {
        console.error('Failed to recover editor:', e);
        return false;
    }
}

/**
 * UTILITY FUNCTION
 * Lấy nội dung từ editor một cách an toàn
 * @returns {string} Nội dung của editor
 */
function safeGetEditorContent() {
    console.log('Safely getting editor content');

    // Thử lấy từ biến editor toàn cục
    if (typeof editor !== 'undefined' && editor && typeof editor.getContent === 'function') {
        try {
            console.log('Getting content from global editor variable');
            return editor.getContent();
        } catch (e) {
            console.warn('Error getting content from global editor:', e);
        }
    }

    // Thử lấy từ TinyMCE
    if (typeof tinymce !== 'undefined' && tinymce.activeEditor && tinymce.activeEditor.initialized) {
        try {
            console.log('Getting content from active TinyMCE editor');
            return tinymce.activeEditor.getContent();
        } catch (e) {
            console.warn('Error getting content from tinymce.activeEditor:', e);
        }
    }

    // Thử lấy trực tiếp từ phần tử HTML
    if ($('#editor-content').length) {
        console.log('Getting content directly from HTML element');
        return $('#editor-content').html() || $('#editor-content').val() || '';
    }

    console.warn('No available method to get editor content');
    return '';
}

/**
 * UTILITY FUNCTION
 * Khôi phục tham chiếu editor sau khi có lỗi
 * @param {object} previousEditor - Tham chiếu editor trước khi thực hiện workflow
 */
function restoreEditorReference(previousEditor) {
    if (previousEditor && typeof previousEditor.getContent === 'function') {
        console.log('Restoring previous editor reference');
        window.editor = previousEditor;

        // Kiểm tra xem editor có còn hoạt động không
        try {
            previousEditor.getContent();
            console.log('Previous editor reference restored successfully');
        } catch (e) {
            console.warn('Previous editor reference is no longer valid:', e);
            window.editor = null;
        }
    }
}

/**
 * UTILITY FUNCTION
 * Đặt nội dung cho editor một cách an toàn
 * @param {string} content - Nội dung cần đặt vào editor
 * @returns {boolean} - Trả về true nếu thành công, false nếu thất bại
 */
function safeSetEditorContent(content) {
    console.log('Safely setting editor content');

    // Backup nội dung vào element HTML để đảm bảo không mất
    $('#editor-content').html(content);

    // Thử đặt nội dung bằng TinyMCE
    if (typeof tinymce !== 'undefined' && tinymce.get('editor-content')) {
        try {
            console.log('Setting content using TinyMCE');
            tinymce.get('editor-content').setContent(content);
            return true;
        } catch (e) {
            console.warn('Error setting content using TinyMCE:', e);
        }
    }

    // Thử đặt nội dung bằng biến editor toàn cục
    if (typeof editor !== 'undefined' && editor && typeof editor.setContent === 'function') {
        try {
            console.log('Setting content using global editor variable');
            editor.setContent(content);
            return true;
        } catch (e) {
            console.warn('Error setting content using global editor variable:', e);
        }
    }

    // Nếu không thể đặt nội dung bằng các phương pháp trên, trả về false
    return false;
}

/**
 * THỰC THI (EXECUTION FUNCTION)
 * Tải nội dung từ workflow dựa trên topic ID
 */
function loadContentFromWorkflow(topicId) {
    if (isLoadingContent) {
        console.log('Already loading content, skipping duplicate request');
        return;
    }

    // Lưu tham chiếu đến editor hiện tại trước khi thực hiện workflow
    const currentEditor = window.editor;
    console.log('Saving current editor reference:', currentEditor);

    isLoadingContent = true;
    const workflowData = {
        workflow_id: WORKFLOW_ID,
        topic_id: topicId,
        target_type: TARGET_TYPE,
        target_state: TARGET_STATE,
        button_id: BUTTON_ID,
        action_command: ACTION_COMMAND,
        from_history: true
    };

    console.log('Executing workflow with data:', workflowData);

    // Show loading indicator
    showLoadingIndicator('Fetching content...');

    // First try to get existing draft content
    $.ajax({
        url: admin_url + 'topics/ultimate_editor/get_draft_content',
        type: 'GET',
        data: {
            workflow_id: WORKFLOW_ID,
            topic_id: topicId
        },
        success: function (response) {
            console.log('Get draft content response:', response);

            if (response.success && response.data) {
                // If we have existing draft content, use it
                applyContentToEditor(response.data);
                hideLoadingIndicator();
                isLoadingContent = false;

                // Đảm bảo editor vẫn hoạt động sau khi áp dụng nội dung
                if (!checkAndRecoverEditor()) {
                    console.log('Restoring editor reference after applying content');
                    restoreEditorReference(currentEditor);
                }
            } else {
                // If no existing draft, execute workflow to get content
                executeWorkflow();
            }
        },
        error: function () {
            // On error, fallback to executing workflow
            executeWorkflow();
        }
    });

    // Execute workflow to get content
    function executeWorkflow() {
        showLoadingIndicator('Executing workflow...');
        var requestData = {
            topic_id: topicId,
            workflow_id: WORKFLOW_ID,
            target_type: TARGET_TYPE,
            target_state: TARGET_STATE
        };

        $.ajax({
            url: admin_url + 'topics/ultimate_editor/execute_workflow',
            type: 'POST',
            data: requestData,
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    var data = response.data;
                    // If the response indicates further polling is needed
                    if (data.needs_polling) {
                        pollWorkflowResults(data.workflow_id, data.execution_id, topicId);
                    } else {
                        // Update the editor with the returned content
                        applyContentToEditor(data.response);
                        hideLoadingIndicator();
                        isLoadingContent = false;

                        // Đảm bảo editor vẫn hoạt động sau khi áp dụng nội dung
                        if (!checkAndRecoverEditor()) {
                            console.log('Restoring editor reference after workflow execution');
                            restoreEditorReference(currentEditor);
                        }
                    }
                } else {
                    alert(response.message);
                    hideLoadingIndicator();
                    isLoadingContent = false;

                    // Đảm bảo editor vẫn hoạt động sau khi có lỗi
                    restoreEditorReference(currentEditor);
                }
            },
            error: function (xhr, status, error) {
                alert('Workflow execution failed: ' + error);
                hideLoadingIndicator();
                isLoadingContent = false;

                // Đảm bảo editor vẫn hoạt động sau khi có lỗi
                restoreEditorReference(currentEditor);
            }
        });
    }
}

/**
 * THỰC THI (EXECUTION FUNCTION)
 * Kiểm tra kết quả workflow theo định kỳ
 */
function pollWorkflowResults(workflowId, executionId, topicId) {
    pollTimeoutCount++;

    if (pollTimeoutCount > MAX_POLL_ATTEMPTS) {
        hideLoadingIndicator();
        isLoadingContent = false;
        pollTimeoutCount = 0;
        alert_float('danger', 'Timeout waiting for workflow results');
        return;
    }

    updateLoadingIndicator('Waiting for results... (' + pollTimeoutCount + '/' + MAX_POLL_ATTEMPTS + ')');

    $.ajax({
        url: admin_url + 'topics/ultimate_editor/check_workflow_status',
        type: 'POST',
        data: {
            workflow_id: workflowId,
            execution_id: executionId,
            topic_id: topicId
        },
        dataType: 'json',
        success: function (response) {
            console.log('Poll workflow status response:', response);

            if (response.success) {
                // Check if the workflow is complete
                if (response.data.status === 'completed') {
                    // Check if we have data
                    if (response.data.workflow_response &&
                        response.data.workflow_response.data &&
                        response.data.workflow_response.data.response) {

                        // Process Draft Writer workflow response
                        if (response.data.workflow_response.data.response.data) {
                            // This is a Draft Writer response
                            const workflowResponse = response.data.workflow_response;
                            updateLoadingIndicator('Applying content to editor...');
                            setTimeout(function () {
                                applyDraftWriterContentToEditor(workflowResponse, topicId);
                                hideLoadingIndicator();
                                isLoadingContent = false;
                                pollTimeoutCount = 0;
                            }, 500);
                        } else {
                            // Regular workflow response
                            const workflowResponse = response.data.workflow_response;
                            updateLoadingIndicator('Applying content to editor...');
                            setTimeout(function () {
                                applyContentToEditor(workflowResponse);
                                hideLoadingIndicator();
                                isLoadingContent = false;
                                pollTimeoutCount = 0;
                            }, 500);
                        }
                    } else {
                        hideLoadingIndicator();
                        isLoadingContent = false;
                        pollTimeoutCount = 0;
                        alert_float('danger', 'No data in workflow response');
                    }
                } else if (response.data.status === 'failed') {
                    hideLoadingIndicator();
                    isLoadingContent = false;
                    pollTimeoutCount = 0;
                    alert_float('danger', 'Workflow execution failed: ' + (response.data.error_message || 'Unknown error'));
                } else {
                    // Still running, poll again
                    setTimeout(function () {
                        pollWorkflowResults(workflowId, executionId, topicId);
                    }, POLL_INTERVAL);
                }
            } else {
                hideLoadingIndicator();
                isLoadingContent = false;
                pollTimeoutCount = 0;
                alert_float('danger', response.message || 'Error checking workflow status');
            }
        },
        error: function (xhr, status, error) {
            console.error('Poll workflow status error:', error);
            hideLoadingIndicator();
            isLoadingContent = false;
            pollTimeoutCount = 0;
            alert_float('danger', 'Error checking workflow status: ' + error);
        }
    });
}

/**
 * THỰC THI (EXECUTION FUNCTION)
 * Áp dụng nội dung từ Draft Writer vào trình soạn thảo
 */
function applyDraftWriterContentToEditor(workflowResponse, topicId) {
    console.log('Applying Draft Writer content to editor:', workflowResponse);

    // Lưu tham chiếu đến editor hiện tại
    const currentEditor = window.editor;

    if (!workflowResponse || !workflowResponse.data || !workflowResponse.data.response || !workflowResponse.data.response.data) {
        console.error('Invalid workflow response:', workflowResponse);
        alert_float('danger', 'Invalid workflow response structure');
        return;
    }

    // Extract data from Draft Writer response
    const responseData = workflowResponse.data.response.data[0];

    // Extract title, summary, and item content
    const title = responseData.Title || '';
    const summary = responseData.Summary || '';

    // Generate HTML content from item details
    let htmlContent = '';

    // Add item content
    if (responseData.Item_Title && responseData.Item_Content) {
        htmlContent += '<h2>' + responseData.Item_Title + '</h2>';
        htmlContent += responseData.Item_Content;
    }

    // Add footer if available
    if (responseData.Topic_footer) {
        htmlContent += '<div class="topic-footer">' + responseData.Topic_footer + '</div>';
    }

    // Clean HTML content to prevent issues
    htmlContent = cleanHtmlContent(htmlContent);

    // Set title
    $('#draft-title').val(title);

    // Set description/summary
    $('#draft-description').val(summary);

    // Update the editor with content using safe method
    const setContentResult = safeSetEditorContent(htmlContent);

    if (!setContentResult) {
        // Fallback if editor not initialized
        $('#editor-content').html(htmlContent);
        alert_float('warning', 'Editor not initialized, content saved but display may be limited');

        // Try to recover editor
        if (!checkAndRecoverEditor()) {
            restoreEditorReference(currentEditor);
        }
    } else {
        // Trigger change event for analysis updates if editor is available
        if (typeof editor !== 'undefined' && editor && typeof editor.fire === 'function') {
            try {
                editor.fire('change');
            } catch (e) {
                console.warn('Error firing change event:', e);
            }
        }
    }

    // Update the keywords field if available
    if (responseData.TopicKeywords) {
        $('#keywords').val(responseData.TopicKeywords);
    }

    // Set dirty flag to trigger save
    isDirty = true;

    // Update content analysis
    analyzeContent(htmlContent, title, summary, responseData.TopicKeywords);

    // Cập nhật số từ
    updateWordCount();

    // Render tags
    renderTags();

    // Show success message
    alert_float('success', 'Content loaded successfully from Draft Writer');

    // Save draft automatically
    saveDraft(true);
}


/**
 * THỰC THI (EXECUTION FUNCTION)
 * Lưu bản nháp vào cơ sở dữ liệu
 * @param {boolean} isAutosave - Có phải là autosave không
 * @param {Function} callback - Hàm callback được gọi sau khi lưu thành công
 */
function saveDraft(isAutosave = false, callback = null) {
    console.log('Attempting to save draft, autosave:', isAutosave);

    // Kiểm tra trạng thái editor một cách kỹ lưỡng
    if (!checkAndRecoverEditor()) {
        console.warn('Editor not available for saving');

        // For autosave, just fail silently
        if (!isAutosave) {
            alert_float('warning', 'Editor is not available. Please refresh the page.');
        }

        // Gọi callback với kết quả false nếu được cung cấp
        if (typeof callback === 'function') {
            callback(false);
        }

        return;
    }

    // Lấy nội dung một cách an toàn từ editor
    const content = safeGetEditorContent();
    const topicId = $('#topicid').val();
    const draftId = $('#current-draft-id').val();
    const title = $('#draft-title').val();
    const description = $('#draft-description').val();
    const tags = $('#draft-tags').val();
    const metaDescription = $('#seo-meta-description').val() || description; // Sử dụng description nếu không có meta description
    const keywords = $('#keywords').val();
    const featureImage = $('#feature-image-url').val();

    if (!title) {
        if (!isAutosave) {
            alert_float('warning', _l('please_enter_draft_title'));
        }
        return;
    }

    // Kiểm tra draft_description không được để trống
    if (!description) {
        if (!isAutosave) {
            alert_float('warning', _l('please_enter_draft_description'));
        }
        return;
    }

    // For autosave, show a small indicator
    if (isAutosave) {
        $('#saving-indicator').removeClass('d-none');
    } else {
        showLoadingIndicator('Saving draft...');
    }

    // Gom tất cả metadata vào một đối tượng JSON
    const metadata = {
        meta_description: metaDescription,
        keywords: keywords,
        draft_description: description,
        draft_tags: tags,
        feature_image: featureImage
    };

    console.log('Saving draft with metadata:', metadata);

    $.ajax({
        url: admin_url + 'topics/ultimate_editor/save_draft',
        type: 'POST',
        data: {
            topic_id: topicId,
            draft_id: draftId,
            draft_title: title,
            draft_content: content,
            draft_description: description,
            draft_tags: tags,
            meta_description: metaDescription,
            keywords: keywords,
            feature_image: featureImage,
            draft_metadata: JSON.stringify(metadata), // Gửi metadata dưới dạng chuỗi JSON
            is_autosave: isAutosave
        },
        success: function (response) {
            try {
                // Parse response if it's a string
                if (typeof response === 'string') {
                    response = JSON.parse(response);
                }

                if (response.success) {
                    isDirty = false;
                    lastSaveTime = new Date();
                    updateLastSavedTime();

                    // Update draft ID if it's a new draft
                    if (response.data && response.data.id && (!draftId || draftId !== response.data.id)) {
                        $('#current-draft-id').val(response.data.id);

                        // Cũng lưu draft ID vào sessionStorage để đảm bảo nó được khôi phục sau khi refresh
                        sessionStorage.setItem('last_created_draft_id', response.data.id);
                    }

                    // Lưu trữ full draft data vào hidden field và sessionStorage
                    if (response.data) {
                        try {
                            $('#full-draft-data').val(JSON.stringify(response.data));
                            sessionStorage.setItem('full_draft_data', JSON.stringify(response.data));
                            console.log('Updated full draft data in hidden field and sessionStorage');
                        } catch (e) {
                            console.error('Error storing full draft data:', e);
                        }
                    }

                    // Update metadata fields if available
                    if (response.data) {
                        if (response.data.meta_description) {
                            $('#seo-meta-description').val(response.data.meta_description);
                        }
                        if (response.data.keywords) {
                            $('#keywords').val(response.data.keywords);
                        }
                        if (response.data.draft_description) {
                            $('#draft-description').val(response.data.draft_description);
                        }
                        if (response.data.draft_tags) {
                            $('#draft-tags').val(response.data.draft_tags);
                            renderTags(); // Re-render tags if they've changed
                        }
                    }

                    if (!isAutosave) {
                        hideLoadingIndicator();
                        alert_float('success', response.message || 'Draft saved successfully');
                    } else {
                        // Hide the autosave indicator after a short delay
                        setTimeout(function () {
                            $('#saving-indicator').addClass('d-none');
                        }, 1000);
                    }

                    // Gọi callback function nếu được cung cấp
                    if (typeof callback === 'function') {
                        callback(response);
                    }
                } else {
                    if (!isAutosave) {
                        hideLoadingIndicator();
                    } else {
                        $('#saving-indicator').addClass('d-none');
                    }
                    console.error('Save error:', response.message);
                    alert_float('danger', response.message || 'Failed to save draft');

                    // Gọi callback function với kết quả lỗi
                    if (typeof callback === 'function') {
                        callback(false);
                    }
                }
            } catch (e) {
                console.error('Error processing response:', e);
                if (!isAutosave) {
                    hideLoadingIndicator();
                } else {
                    $('#saving-indicator').addClass('d-none');
                }
                alert_float('danger', 'Error processing response');

                // Gọi callback function với kết quả lỗi
                if (typeof callback === 'function') {
                    callback(false);
                }
            }
        },
        error: function (xhr, status, error) {
            if (!isAutosave) {
                hideLoadingIndicator();
            } else {
                $('#saving-indicator').addClass('d-none');
            }
            console.error('Ajax error:', xhr.responseText);
            alert_float('danger', 'Error saving draft: ' + error);

            // Gọi callback function với kết quả lỗi
            if (typeof callback === 'function') {
                callback(false);
            }
        }
    });
}

/**
 * THỰC THI (EXECUTION FUNCTION)
 * Tạo bản nháp mới
 */
function createNewDraft() {
    console.log('Creating new draft');
    const title = $('#new-draft-title').val().trim();
    const description = $('#new-draft-description').val().trim();

    if (!title) {
        alert_float('warning', _l('please_enter_draft_title'));
        return;
    }

    showLoadingIndicator('Creating new draft...');
    console.log('Draft data:', { title: title, description: description, topicId: topicId });

    $.ajax({
        url: admin_url + 'topics/ultimate_editor/create_draft',
        type: 'POST',
        data: {
            topic_id: topicId,
            draft_title: title,
            draft_description: description,
            set_as_active: true
        },
        success: function (response) {
            hideLoadingIndicator();
            console.log('Create draft API response:', response);

            if (typeof response === 'string') {
                try {
                    response = JSON.parse(response);
                    console.log('Parsed response:', response);
                } catch (e) {
                    console.error('Failed to parse create draft response:', e);
                    alert_float('danger', _l('error_creating_draft'));
                    return;
                }
            }

            if (response.success) {
                $('#new-draft-modal').modal('hide');
                console.log('New draft created successfully with ID:', response.draft_id);
                alert_float('success', _l('draft_created_successfully'));

                // Store information about the current draft before reloading
                if (response.draft_id) {
                    console.log('Storing draft ID in sessionStorage:', response.draft_id);
                    sessionStorage.setItem('last_created_draft_id', response.draft_id);
                }

                // Reload the page after a short delay
                console.log('Page will reload in 1 second');
                setTimeout(function () {
                    console.log('Reloading page...');
                    window.location.reload();
                }, 1000);
            } else {
                console.error('Failed to create draft:', response.message || 'Unknown error');
                alert_float('danger', response.message || _l('error_creating_draft'));
            }
        },
        error: function (xhr, status, error) {
            hideLoadingIndicator();
            console.error('AJAX error when creating draft:', { xhr: xhr, status: status, error: error });
            alert_float('danger', 'Error creating new draft: ' + error);
        }
    });
}

/**
 * THỰC THI (EXECUTION FUNCTION)
 * Tải bản nháp từ cơ sở dữ liệu
 */
function loadDraft(draftId) {
    console.log('Loading draft with ID:', draftId);
    showLoadingIndicator(_l('loading_draft'));

    $.ajax({
        url: admin_url + 'topics/ultimate_editor/get_draft/' + draftId,
        type: 'GET',
        responseType: 'json',
        dataType: 'json',
        success: function (response) {
            hideLoadingIndicator();
            console.log('Load draft API response:', response);

            if (response.success && response.draft) {

                // Exit preview mode if active
                if (window.isPreviewMode || (typeof isPreviewMode !== 'undefined' && isPreviewMode)) {
                    console.log('Exiting preview mode after loading draft');
                    // Clear the backup state
                    localStorage.removeItem('preview_backup_state');

                    cancelPreview();
                }

                const draft = response.draft;

                // Process content if it's a JSON string
                // Check if draft content is a string JSON and parse it if needed
                if (typeof draft.draft_content === 'string' && draft.draft_content.startsWith('{')) {
                    try {
                        // Try to parse it as JSON to see if it's valid JSON
                        JSON.parse(draft.draft_content);
                        // If parsing succeeds but it's really supposed to be content, we need to decode it
                        // Content should be HTML, not a JSON object
                        if (draft.draft_content.includes('"content":[')) {
                            try {
                                const parsedContent = JSON.parse(draft.draft_content);
                                if (parsedContent.content && Array.isArray(parsedContent.content)) {
                                    // Content is in a JSON structure, extract actual HTML content
                                    let htmlContent = '';
                                    parsedContent.content.forEach(item => {
                                        if (item.type === 'text' && item.text) {
                                            htmlContent += item.text;
                                        }
                                    });
                                    if (htmlContent) {
                                        draft.draft_content = htmlContent;
                                        console.log('Converted JSON content to HTML');
                                    }
                                }
                            } catch (e) {
                                console.warn('Content appears to be JSON but not in expected format, using as-is');
                            }
                        }
                    } catch (e) {
                        // Not valid JSON, it's likely actual HTML content
                        console.log('Draft content is plain text/HTML, using as-is');
                    }
                }

                // Store this draft in sessionStorage to persist across page refreshes
                sessionStorage.setItem('full_draft_data', JSON.stringify(draft));

                // Also update the hidden field for initial page load persistence
                $('#full-draft-data').val(JSON.stringify(draft));

                // Set current draft ID
                $('#current-draft-id').val(draft.id);

                // Update the draft as the active draft on the server
                $.ajax({
                    url: admin_url + 'topics/ultimate_editor/set_active_draft',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        topic_id: $('#topicid').val(),
                        draft_id: draft.id
                    },
                    success: function (setActiveResponse) {
                        console.log('Set active draft response:', setActiveResponse, 'draft.id:', draft.id, 'topic_id:', $('#topicid').val());
                        if (!setActiveResponse.success) {
                            console.warn('Failed to set active draft on server:', setActiveResponse.message);
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error('Error setting active draft on server:', error);
                    }
                });

                // Update UI elements
                console.log('Updating UI elements for draft:', draft);
                $('#current-draft-name').text(draft.draft_title || '');
                $('#draft-title').val(draft.draft_title || '');
                $('#draft-version').text('v' + (draft.version || '1'));
                $('#draft-status').text(draft.status || 'draft')
                    .removeClass('badge-warning badge-success')
                    .addClass(draft.status === 'final' ? 'badge-success' : 'badge-warning');

                // Clear draft tags container and repopulate
                $('#draft-tags').val(draft.draft_tags || '');
                renderTags();

                // Try to set editor content with retry mechanism
                let editorInitAttempts = 0;
                const maxAttempts = 5;

                function trySetEditorContent() {
                    editorInitAttempts++;
                    console.log(`Attempt ${editorInitAttempts} to set editor content`);

                    const result = safeSetEditorContent(draft.draft_content || '');

                    if (!result && editorInitAttempts < maxAttempts) {
                        // Editor not ready, wait and try again
                        console.warn(`Editor not initialized on attempt ${editorInitAttempts}, retrying in 500ms...`);
                        setTimeout(trySetEditorContent, 500);
                    } else if (!result) {
                        // Max attempts reached
                        console.error('Failed to initialize editor after multiple attempts');
                        $('#editor-content').html(draft.draft_content || '');
                        alert_float('warning', 'Editor initialization issue. Content loaded but editor features may be limited.');
                    } else {
                        console.log('Editor content set successfully');
                    }
                }

                // Start the first attempt
                trySetEditorContent();

                // Parse metadata
                let metadata = {};
                if (draft.draft_metadata) {
                    if (typeof draft.draft_metadata === 'string') {
                        try {
                            metadata = JSON.parse(draft.draft_metadata);
                        } catch (e) {
                            console.warn('Failed to parse draft metadata, using as is:', e);
                            metadata = {};
                        }
                    } else {
                        metadata = draft.draft_metadata;
                    }
                }

                // Update metadata fields - first check from draft_metadata object
                // Then fall back to direct properties if needed (for backward compatibility)

                // Handle meta description
                if (metadata.meta_description !== undefined) {
                    $('#meta-description').val(metadata.meta_description);
                    console.log('Meta description updated from metadata:', metadata.meta_description);
                } else if (response.draft.meta_description) {
                    $('#meta-description').val(response.draft.meta_description);
                    console.log('Meta description updated from draft:', response.draft.meta_description);
                }

                // Handle keywords
                if (metadata.keywords !== undefined) {
                    $('#keywords').val(metadata.keywords);
                    console.log('Keywords updated from metadata:', metadata.keywords);
                } else if (response.draft.keywords) {
                    $('#keywords').val(response.draft.keywords);
                    console.log('Keywords updated from draft:', response.draft.keywords);
                }

                // Handle draft description
                if (metadata.draft_description !== undefined) {
                    $('#draft-description').val(metadata.draft_description);
                    console.log('Draft description updated from metadata:', metadata.draft_description);
                } else if (response.draft.draft_description) {
                    $('#draft-description').val(response.draft.draft_description);
                    console.log('Draft description updated from draft:', response.draft.draft_description);
                }

                // Handle feature image
                if (draft.feature_image) {
                    $('#feature-image').attr('src', draft.feature_image);
                    $('#feature-image-url').val(draft.feature_image);
                    $('.feature-image-preview').removeClass('hide');
                } else {
                    $('.feature-image-preview').addClass('hide');
                    $('#feature-image-url').val('');
                }

                // Set SEO target keyword - try to get from metadata, or fallback to the first tag
                let targetKeyword = '';
                console.log('Metadata:', metadata);
                if (metadata.keywords !== undefined) {
                    targetKeyword = metadata.keywords;
                    console.log('Target keyword set from metadata:', targetKeyword);
                } else if (draft.target_keyword) {
                    targetKeyword = draft.target_keyword;
                    console.log('Target keyword set from draft:', targetKeyword);
                }

                if (metadata.draft_tags) {
                    // If no target keyword is specified, use the first tag
                    const tags = metadata.draft_tags.split(',').map(tag => tag.trim()).filter(Boolean);
                    if (tags.length > 0) {
                        $('#draft-tags').val(metadata.draft_tags);
                        renderTags();
                        console.log('Target keyword set from first tag:', targetKeyword);
                    }
                }

                if (targetKeyword) {
                    $('#seo-target-keyword').val(targetKeyword);
                    // Trigger SEO analysis after a short delay
                    setTimeout(function () {
                        analyzeSEO();
                    }, 500);
                }

                // Update character counts and word stats
                updateWordCount();

                // Reset dirty flag
                isDirty = false;


                console.log('Draft loaded successfully:', draft);
                alert_float('success', _l('draft_loaded_successfully'));
            } else {
                const errorMsg = response.message || _l('error_loading_draft');
                console.error('Error loading draft:', errorMsg);
                alert_float('danger', errorMsg);
            }
        },
        error: function (xhr, status, error) {
            hideLoadingIndicator();
            console.error('Error loading draft:', error, xhr.responseText);
            alert_float('danger', _l('error_loading_draft') + ': ' + error);
        }
    });
}

/**
 * THỰC THI (EXECUTION FUNCTION)
 * Xóa bản nháp
 */
function deleteDraft(draftId) {
    if (!confirm('Are you sure you want to delete this draft? This action cannot be undone.')) {
        return;
    }

    showLoadingIndicator('Deleting draft...');

    $.ajax({
        url: admin_url + 'topics/ultimate_editor/delete_draft/' + draftId,
        type: 'POST',
        success: function (response) {
            hideLoadingIndicator();

            if (response.success) {
                alert_float('success', response.message || 'Draft deleted successfully');

                // If the deleted draft is the current one, create a new draft
                if ($('#current-draft-id').val() == draftId) {
                    $('#current-draft-id').val('');
                    $('#current-draft-name').text('No draft selected');
                    $('#draft-version').text('');
                    $('#draft-status').text('');
                }

                // Refresh drafts list
                loadDraftsList();
            } else {
                alert_float('danger', response.message || 'Failed to delete draft');
            }
        },
        error: function (xhr, status, error) {
            hideLoadingIndicator();
            alert_float('danger', 'Error deleting draft: ' + error);
        }
    });
}

/**
 * THỰC THI (EXECUTION FUNCTION)
 * Nhân bản bản nháp
 */
function duplicateDraft(draftId) {
    showLoadingIndicator('Duplicating draft...');

    $.ajax({
        url: admin_url + 'topics/ultimate_editor/duplicate_draft',
        type: 'POST',
        data: {
            draft_id: draftId
        },
        success: function (response) {
            hideLoadingIndicator();

            if (response.success) {
                alert_float('success', response.message || 'Draft duplicated successfully');

                // Refresh drafts list
                loadDraftsList();
            } else {
                alert_float('danger', response.message || 'Failed to duplicate draft');
            }
        },
        error: function (xhr, status, error) {
            hideLoadingIndicator();
            alert_float('danger', 'Error duplicating draft: ' + error);
        }
    });
}

/**
 * THỰC THI (EXECUTION FUNCTION)
 * Chuyển bản nháp thành bản chính thức
 */
function convertToFinal() {
    const draftId = $('#current-draft-id').val();

    if (!draftId) {
        alert_float('warning', 'No draft selected');
        return;
    }

    if (!confirm('Are you sure you want to finalize this draft? This will mark it as ready for publication.')) {
        return;
    }

    showLoadingIndicator('Finalizing draft...');

    $.ajax({
        url: admin_url + 'topics/ultimate_editor/convert_to_final',
        type: 'POST',
        data: {
            draft_id: draftId
        },
        success: function (response) {
            hideLoadingIndicator();

            if (response.success) {
                alert_float('success', response.message || 'Draft finalized successfully');

                // Update status UI
                $('#draft-status').removeClass('badge-warning').addClass('badge-success').text('final');

                // Refresh drafts list
                loadDraftsList();
            } else {
                alert_float('danger', response.message || 'Failed to finalize draft');
            }
        },
        error: function (xhr, status, error) {
            hideLoadingIndicator();
            alert_float('danger', 'Error finalizing draft: ' + error);
        }
    });
}

/**
 * THỰC THI (EXECUTION FUNCTION)
 * Tải danh sách bản nháp
 */
function loadDraftsList() {
    const topicId = $('#topicid').val();

    console.log('Loading drafts for topic ID:', topicId);

    $.ajax({
        url: admin_url + 'topics/ultimate_editor/get_drafts',
        type: 'GET',
        data: {
            topic_id: topicId
        },
        success: function (response) {
            try {
                // Parse response if it's a string
                if (typeof response === 'string') {
                    response = JSON.parse(response);
                }

                console.log('Drafts loaded successfully:', response);

                if (response.success && response.drafts) {
                    // Clear existing drafts
                    $('#drafts-list').empty();

                    if (response.drafts.length === 0) {
                        $('#drafts-list').html(
                            '<div class="no-drafts-message">' +
                            '<i class="fa fa-file-text-o fa-2x mb-2"></i><br>' +
                            'Không có bản nháp nào' +
                            '</div>'
                        );
                        return;
                    }

                    console.log('Found', response.drafts.length, 'drafts');

                    // Sort drafts by creation date (newest first by default)
                    const sortOrder = $('#draft-sort-order').val() || 'newest';
                    response.drafts.sort(function (a, b) {
                        const dateA = new Date(a.updated_at || a.created_at);
                        const dateB = new Date(b.updated_at || b.created_at);
                        return sortOrder === 'newest' ? dateB - dateA : dateA - dateB;
                    });

                    // Add drafts to the list
                    $.each(response.drafts, function (i, draft) {
                        const isActive = (draft.id == $('#current-draft-id').val() || draft.is_active);
                        const updatedDate = new Date(draft.updated_at || draft.created_at);
                        const formattedDate = updatedDate.toLocaleDateString() + ' ' + updatedDate.toLocaleTimeString();

                        console.log('Rendering draft:', draft.id, draft.draft_title);

                        const draftHtml = `
                                <div class="draft-item ${isActive ? 'active' : ''}" data-draft-id="${draft.id}">
                                    <div class="d-flex justify-content-between">
                                        <div class="draft-title">${draft.draft_title}</div>
                                        <div class="draft-badges">
                                            <span class="badge badge-info">v${draft.version}</span>
                                            <span class="badge badge-${draft.status === 'final' ? 'success' : 'warning'}">${draft.status}</span>
                                        </div>
                                    </div>
                                    <div class="draft-metadata mt-2">
                                        <span><i class="fa fa-clock-o"></i> ${formattedDate}</span>
                                        <span><i class="fa fa-user"></i> ${draft.last_edited_by_name || draft.created_by_name || 'Unknown'}</span>
                                        </div>
                                    <div class="draft-action-buttons mt-2">
                                        <button class="btn btn-sm btn-success use-draft-btn" data-draft-id="${draft.id}">
                                            <i class="fa fa-check"></i> Use
                                            </button>
                                        <button class="btn btn-sm btn-info preview-draft-btn" data-draft-id="${draft.id}">
                                            <i class="fa fa-eye"></i> Preview
                                            </button>
                                        </div>
                                </div>
                            `;

                        $('#drafts-list').append(draftHtml);
                    });

                    // Bind events to the draft actions
                    bindDraftActions();

                    console.log('Draft list rendering complete');
                } else {
                    console.error('Error in response:', response.message || 'Unknown error');
                    $('#drafts-list').html(
                        '<div class="alert alert-warning text-center">' +
                        (response.message || 'Không thể tải danh sách bản nháp') +
                        '</div>'
                    );
                }
            } catch (e) {
                console.error('Error processing response:', e, response);
                $('#drafts-list').html(
                    '<div class="alert alert-danger text-center">' +
                    'Lỗi khi xử lý dữ liệu bản nháp: ' + e.message +
                    '</div>'
                );
            }
        },
        error: function (xhr, status, error) {
            console.error('Error loading drafts:', error, xhr.responseText);
            $('#drafts-list').html(
                '<div class="alert alert-danger text-center">' +
                'Lỗi khi tải danh sách bản nháp: ' + error +
                '</div>'
            );
        }
    });
}


/**
 * THỰC THI (EXECUTION FUNCTION)
 * Xem trước bản nháp
 */
function previewDraft(draftId) {
    showLoadingIndicator(_l('loading_drafts'));

    $.ajax({
        url: admin_url + 'topics/ultimate_editor/get_draft',
        type: 'GET',
        data: {
            draft_id: draftId
        },
        success: function (response) {
            hideLoadingIndicator();

            // Handle string response
            if (typeof response === 'string') {
                try {
                    response = JSON.parse(response);
                } catch (e) {
                    console.error('Failed to parse draft preview response:', e);
                    alert_float('danger', _l('error_loading_draft_preview'));
                    return;
                }
            }

            if (response.success && response.draft) {
                // Store current state for reverting
                const currentState = {
                    content: safeGetEditorContent(),
                    title: $('#draft-title').val(),
                    description: $('#draft-description').val(),
                    tags: $('#draft-tags').val(),
                    keywords: $('#keywords').val(),
                    featureImage: $('#feature-image-url').val()
                };

                // Store the state for possible restoration
                localStorage.setItem('preview_backup_state', JSON.stringify(currentState));

                // Apply preview
                const draft = response.draft;

                // Use retry mechanism for setting editor content
                let editorInitAttempts = 0;
                const maxAttempts = 5;

                function trySetPreviewContent() {
                    editorInitAttempts++;
                    console.log(`Preview: Attempt ${editorInitAttempts} to set editor content`);

                    const result = safeSetEditorContent(draft.draft_content || '');

                    if (!result && editorInitAttempts < maxAttempts) {
                        // Editor not ready, wait and try again
                        console.warn(`Preview: Editor not initialized on attempt ${editorInitAttempts}, retrying in 500ms...`);
                        setTimeout(trySetPreviewContent, 500);
                    } else if (!result) {
                        // Max attempts reached
                        console.error('Preview: Failed to initialize editor after multiple attempts');
                        $('#editor-content').html(draft.draft_content || '');
                        alert_float('warning', 'Editor initialization issue. Preview content loaded but editor features may be limited.');
                    } else {
                        console.log('Preview: Editor content set successfully');
                    }
                }

                // Start the first attempt
                trySetPreviewContent();

                $('#draft-title').val(draft.draft_title || '');

                // Update feature image if available
                if (draft.feature_image) {
                    $('#feature-image-url').val(draft.feature_image);
                    $('#feature-image').attr('src', draft.feature_image);
                    $('.feature-image-preview').removeClass('hide');
                } else {
                    $('#feature-image-url').val('');
                    $('#feature-image').attr('src', '');
                    $('.feature-image-preview').addClass('hide');
                }

                // Update metadata if available
                let metadata = null;
                if (draft.draft_metadata) {
                    if (typeof draft.draft_metadata === 'string') {
                        try {
                            metadata = JSON.parse(draft.draft_metadata);
                        } catch (e) {
                            console.warn('Failed to parse draft metadata, using as is:', e);
                            metadata = {};
                        }
                    } else {
                        metadata = draft.draft_metadata;
                    }

                    $('#draft-description').val(metadata.draft_description || '');
                    $('#draft-tags').val(metadata.draft_tags || '');
                    $('#keywords').val(metadata.keywords || '');
                } else {
                    // Clear fields if no metadata
                    $('#draft-description').val('');
                    $('#draft-tags').val('');
                    $('#keywords').val('');
                }

                // Update UI
                renderTags();
                updateWordCount();

                // Show preview banner
                showPreviewBanner(draft.draft_title, draftId);

                // Set preview flag
                isPreviewMode = true;
                previewDraftId = draftId;

                console.log('Preview loaded successfully for draft:', draftId);
            } else {
                const errorMsg = response.message || _l('error_loading_draft_preview');
                console.error('Error loading draft preview:', errorMsg);
                alert_float('danger', errorMsg);
            }
        },
        error: function (xhr, status, error) {
            hideLoadingIndicator();
            console.error('Error previewing draft:', error, xhr.responseText);
            alert_float('danger', _l('error_loading_draft_preview') + ': ' + error);
        }
    });
}

/**
 * THỰC THI (EXECUTION FUNCTION)
 * Phân tích SEO và hiển thị kết quả
 */
function analyzeSEO() {
    // Kiểm tra trạng thái editor trước
    if (!checkAndRecoverEditor()) {
        alert_float('warning', 'Editor is not available. Please refresh the page.');
        return;
    }

    const draftId = $('#current-draft-id').val();
    const content = safeGetEditorContent();
    const title = $('#draft-title').val();
    const description = $('#draft-description').val();
    const topicTags = $('#draft-tags').val();

    // Use tags for SEO analysis
    let targetKeyword = $('#seo-target-keyword').val();

    // If no target keyword specified, try to use the first tag
    if (!targetKeyword && topicTags) {
        const tags = topicTags.split(',').map(tag => tag.trim()).filter(Boolean);
        if (tags.length > 0) {
            targetKeyword = tags[0];
            $('#seo-target-keyword').val(targetKeyword);
        }
    }

    if (!draftId) {
        alert_float('warning', 'No draft selected');
        return;
    }

    if (!targetKeyword) {
        alert_float('warning', 'Please enter a target keyword or add tags');
        return;
    }

    // Reset SEO checklist to loading state
    if (typeof resetSEOChecklist === 'function') {
        resetSEOChecklist();
    } else {
        // Fallback if resetSEOChecklist function is not available
        $('#seo-analysis-loading').removeClass('hide');
    }

    // Perform local analysis first
    const localAnalysis = performLocalSEOAnalysis(content, title, description, targetKeyword, topicTags);
    displaySEOAnalysis(localAnalysis);

    // Then call the server for more detailed analysis
    $.ajax({
        url: admin_url + 'topics/ultimate_editor/analyze_seo',
        type: 'POST',
        data: {
            draft_id: draftId,
            content: content,
            title: title,
            description: description,
            target_keyword: targetKeyword,
            tags: topicTags
        },
        success: function (response) {
            if (response.success && response.analysis) {
                displaySEOAnalysis(response.analysis);
            } else {
                // Keep the local analysis displayed
                alert_float('info', response.message || 'Using local SEO analysis');
            }
        },
        error: function (xhr, status, error) {
            // Đảm bảo phần tử loading được ẩn trong trường hợp lỗi
            $('#seo-analysis-loading').addClass('hide');
            alert_float('warning', 'Server analysis failed, using local results: ' + error);
        }
    });
}


/**
 * THỰC THI (EXECUTION FUNCTION)
 * Import nội dung từ Draft Writer
 */
function importFromDraftWriter() {
    const topicId = $('#topicid').val();

    if (!topicId) {
        alert_float('warning', 'Topic ID not found. Cannot import content.');
        return;
    }

    // Intentamos recuperar datos desde localStorage primero
    const localStorageKey = 'draft_' + topicId;
    const savedDraft = localStorage.getItem(localStorageKey);

    // Si tenemos datos en localStorage
    if (savedDraft) {
        try {
            const draftData = JSON.parse(savedDraft);

            // Mostrar confirmación antes de importar
            if (confirm('Found saved draft in local storage. Do you want to import it?')) {
                // Enviar datos al servidor para crear un nuevo draft
                $.ajax({
                    url: admin_url + 'topics/ultimate_editor/recover_from_local_storage',
                    type: 'POST',
                    data: {
                        topic_id: topicId,
                        local_storage_data: savedDraft
                    },
                    dataType: 'json',
                    success: function (response) {
                        if (response.success) {
                            alert_float('success', response.message);
                            // Redirect if URL provided
                            if (response.redirect_url) {
                                window.location.href = response.redirect_url;
                            }
                        } else {
                            alert_float('danger', response.message);
                        }
                    },
                    error: function (xhr, status, error) {
                        alert_float('danger', 'Error importing draft: ' + error);
                    }
                });
            }
        } catch (e) {
            console.error('Error parsing saved draft:', e);
            alert_float('warning', 'Could not parse saved draft data.');
        }
    } else {
        // Si no hay datos en localStorage, preguntar si quiere cargar desde flujo de trabajo
        if (confirm('No saved draft found in local storage. Do you want to load from workflow?')) {
            loadContentFromWorkflow(topicId);
        }
    }
}

/**
 * THỰC THI (EXECUTION FUNCTION)
 * Import từ dữ liệu workflow
 */
function importFromWorkflowData(workflowData) {
    const topicId = $('#topicid').val();

    if (!topicId) {
        alert_float('warning', 'Topic ID not found. Cannot import content.');
        return;
    }

    // Mostrar indicador de carga
    showLoadingIndicator('Importing content from Draft Writer...');

    // Enviar datos al servidor
    $.ajax({
        url: admin_url + 'topics/ultimate_editor/import_from_draft_writer',
        type: 'POST',
        data: {
            topic_id: topicId,
            workflow_data: workflowData
        },
        dataType: 'json',
        success: function (response) {
            hideLoadingIndicator();

            if (response.success) {
                if (response.need_localstorage_data) {
                    // El servidor necesita datos del localStorage
                    importFromDraftWriter();
                } else {
                    alert_float('success', response.message);
                    // Redirect if URL provided
                    if (response.redirect_url) {
                        window.location.href = response.redirect_url;
                    }
                }
            } else {
                alert_float('danger', response.message);
            }
        },
        error: function (xhr, status, error) {
            hideLoadingIndicator();
            alert_float('danger', 'Error importing content: ' + error);
        }
    });
}


/**
 * THỰC THI (EXECUTION FUNCTION)
 * Tạo bản nháp từ workflow
 */
function createDraftFromWorkflow() {
    const topicId = $('#topicid').val();

    if (!topicId) {
        alert_float('warning', _l('topic_id_required'));
        return;
    }

    showLoadingIndicator(_l('loading_content'));

    // Store workflow parameters from config
    const workflowData = {
        workflow_id: WORKFLOW_ID,
        topic_id: topicId,
        target_type: TARGET_TYPE,
        target_state: TARGET_STATE,
        button_id: BUTTON_ID,
        action_command: ACTION_COMMAND
    };

    // Execute workflow and get content
    $.ajax({
        url: admin_url + 'topics/ultimate_editor/execute_workflow',
        type: 'POST',
        data: workflowData,
        success: function (response) {
            try {
                // Parse response if it's a string
                if (typeof response === 'string') {
                    response = JSON.parse(response);
                }

                if (!response.success) {
                    hideLoadingIndicator();
                    alert_float('danger', response.message || _l('workflow_failed'));
                    return;
                }

                console.log('Workflow executed successfully, response:', response);

                // Check if polling is needed
                if (response.data && response.data.needs_polling && response.data.execution_id) {
                    // Need to poll for results
                    updateLoadingIndicator(_l('processing_content'));
                    pollForWorkflowAndCreateDraft(
                        response.data.workflow_id,
                        response.data.execution_id,
                        topicId
                    );
                } else if (response.data && response.data.response) {
                    // We already have the content
                    hideLoadingIndicator();

                    // Xử lý các cấu trúc dữ liệu phản hồi khác nhau
                    let workflowData = response.data.response;

                    // Kiểm tra định dạng phản hồi từ DraftWriter
                    if (workflowData.data && workflowData.data.response && workflowData.data.response.data) {
                        console.log('Detected nested DraftWriter response, extracting data');
                        workflowData = workflowData.data.response.data;
                    }

                    // Kiểm tra xem response có phải là string JSON không
                    if (typeof workflowData === 'string' && workflowData.startsWith('{')) {
                        try {
                            workflowData = JSON.parse(workflowData);
                            console.log('Parsed JSON string response');
                        } catch (e) {
                            console.warn('Failed to parse response as JSON, using as is:', e);
                        }
                    }

                    console.log('Creating draft from direct workflow response data:', workflowData);
                    createDraftFromWorkflowContent(workflowData, topicId);
                } else {
                    hideLoadingIndicator();
                    alert_float('warning', _l('no_content_returned_from_workflow'));
                }
            } catch (error) {
                hideLoadingIndicator();
                console.error('Error processing workflow response:', error);
                alert_float('danger', _l('error_processing_workflow_response'));
            }
        },
        error: function (xhr, status, error) {
            hideLoadingIndicator();
            console.error('Error executing workflow:', error);
            alert_float('danger', _l('workflow_failed'));
        }
    });
}

/**
 * THỰC THI (EXECUTION FUNCTION)
 * Kiểm tra workflow và tạo bản nháp
 */
function pollForWorkflowAndCreateDraft(workflowId, executionId, topicId) {
    let attempts = 0;
    const maxAttempts = MAX_POLL_ATTEMPTS;
    const pollInterval = POLL_INTERVAL;

    function checkStatus() {
        if (attempts >= maxAttempts) {
            hideLoadingIndicator();
            alert_float('warning', _l('workflow_timeout'));
            return;
        }

        attempts++;
        updateLoadingIndicator(_l('processing_content') + ' (' + attempts + '/' + maxAttempts + ')');

        $.ajax({
            url: admin_url + 'topics/ultimate_editor/check_workflow_status',
            type: 'GET',
            data: {
                workflow_id: workflowId,
                execution_id: executionId
            },
            success: function (response) {
                try {
                    // Parse response if it's a string
                    if (typeof response === 'string') {
                        response = JSON.parse(response);
                    }

                    if (!response.success) {
                        hideLoadingIndicator();
                        alert_float('danger', response.message || _l('error_checking_workflow_status'));
                        return;
                    }

                    const status = response.data.status;

                    if (status === 'success' || status === 'completed') { // Thêm "completed" cho tương thích
                        // Workflow completed successfully, get content and create draft
                        $.ajax({
                            url: admin_url + 'topics/ultimate_editor/get_draft_content',
                            type: 'GET',
                            data: {
                                workflow_id: workflowId,
                                execution_id: executionId,
                                topic_id: topicId
                            },
                            success: function (contentResponse) {
                                try {
                                    // Parse response if it's a string
                                    if (typeof contentResponse === 'string') {
                                        contentResponse = JSON.parse(contentResponse);
                                    }

                                    hideLoadingIndicator();

                                    if (contentResponse.success) {
                                        console.log('Got workflow content response:', contentResponse);

                                        // Xử lý các cấu trúc dữ liệu khác nhau
                                        let workflowResponseData;

                                        // Trường hợp 1: Dữ liệu trong execution_data
                                        if (contentResponse.data && contentResponse.data.execution_data) {
                                            workflowResponseData = contentResponse.data.execution_data;
                                        }
                                        // Trường hợp 2: Dữ liệu trong workflow_response
                                        else if (contentResponse.data && contentResponse.data.workflow_response) {
                                            workflowResponseData = contentResponse.data.workflow_response;
                                        }
                                        // Trường hợp 3: Dữ liệu trực tiếp trong data
                                        else if (contentResponse.data) {
                                            workflowResponseData = contentResponse.data;
                                        }
                                        // Trường hợp mặc định: Sử dụng toàn bộ response
                                        else {
                                            workflowResponseData = contentResponse;
                                        }

                                        // Kiểm tra cấu trúc đặc biệt từ DraftWriter
                                        if (workflowResponseData.data && workflowResponseData.data.response &&
                                            workflowResponseData.data.response.data) {
                                            console.log('Detected Draft Writer response structure');
                                            workflowResponseData = workflowResponseData.data.response.data;
                                        }

                                        // Create new draft instead of updating existing one
                                        createDraftFromWorkflowContent(workflowResponseData, topicId);
                                    } else {
                                        alert_float('danger', contentResponse.message || _l('error_getting_draft_content'));
                                    }
                                } catch (error) {
                                    hideLoadingIndicator();
                                    console.error('Error processing content response:', error);
                                    alert_float('danger', _l('error_processing_content_response'));
                                }
                            },
                            error: function (xhr, status, error) {
                                hideLoadingIndicator();
                                console.error('Error getting draft content:', error);
                                alert_float('danger', _l('error_getting_draft_content'));
                            }
                        });
                    } else if (status === 'running' || status === 'waiting') {
                        // Workflow still running, check again after interval
                        setTimeout(checkStatus, pollInterval);
                    } else {
                        // Workflow failed or was cancelled
                        hideLoadingIndicator();
                        alert_float('danger', _l('workflow_failed_with_status') + ': ' + status);
                    }
                } catch (error) {
                    hideLoadingIndicator();
                    console.error('Error processing status response:', error);
                    alert_float('danger', _l('error_processing_status_response'));
                }
            },
            error: function (xhr, status, error) {
                hideLoadingIndicator();
                console.error('Error checking workflow status:', error);
                alert_float('danger', _l('error_checking_workflow_status'));
            }
        });
    }

    // Start polling
    checkStatus();
}

/**
 * THỰC THI (EXECUTION FUNCTION)
 * Tạo bản nháp từ nội dung workflow
 */
function createDraftFromWorkflowContent(workflowData, topicId) {
    console.log('Creating draft from workflow content:', workflowData);

    let draftTitle = '';
    let draftContent = '';
    let metaDescription = '';
    let keywords = '';
    let htmlContent = '';

    // Xử lý dữ liệu dựa trên cấu trúc, tương tự như applyContentToEditor
    if (Array.isArray(workflowData) && workflowData.length > 0) {
        console.log('Processing array data with ' + workflowData.length + ' items');

        // Lấy item đầu tiên cho tiêu đề và mô tả
        const firstItem = workflowData[0];

        // Xử lý tiêu đề
        if (firstItem.Title) {
            draftTitle = typeof decodeHtmlEntities === 'function' ?
                decodeHtmlEntities(firstItem.Title) : firstItem.Title;
        }

        // Xử lý mô tả/tóm tắt
        if (firstItem.Summary) {
            metaDescription = typeof decodeHtmlEntities === 'function' ?
                decodeHtmlEntities(firstItem.Summary) : firstItem.Summary;
        }

        // Xử lý từ khóa
        if (firstItem.TopicKeywords) {
            keywords = typeof decodeHtmlEntities === 'function' ?
                decodeHtmlEntities(firstItem.TopicKeywords) : firstItem.TopicKeywords;
        }

        // Tạo nội dung HTML từ các item
        workflowData.forEach((item, index) => {
            // Thêm separator giữa các item
            if (index > 0) {
                htmlContent += '<hr class="item-separator">';
            }

            // Thêm tiêu đề item nếu có và khác với tiêu đề chính
            if (item.Item_Title && item.Item_Title !== draftTitle) {
                const itemTitle = typeof decodeHtmlEntities === 'function' ?
                    decodeHtmlEntities(item.Item_Title) : item.Item_Title;
                htmlContent += '<h2>' + itemTitle + '</h2>';
            }

            // Thêm nội dung item
            if (item.Item_Content) {
                const itemContent = typeof decodeHtmlEntities === 'function' ?
                    decodeHtmlEntities(item.Item_Content) : item.Item_Content;
                htmlContent += '<div class="item-content">' + itemContent + '</div>';
            }

            // Xử lý hình ảnh nếu có
            if (item.item_Pictures_Full && item.item_Pictures_Full.length > 0) {
                htmlContent += '<div class="item-images">';

                let pictures = item.item_Pictures_Full;
                if (typeof pictures === 'string') {
                    try {
                        pictures = JSON.parse(pictures);
                    } catch (e) {
                        console.error('Error parsing pictures:', e);
                    }
                }

                if (Array.isArray(pictures)) {
                    pictures.forEach(pic => {
                        const imgSrc = typeof pic === 'string' ? pic : (pic.large_src || pic.src || '');
                        if (imgSrc) {
                            htmlContent += '<img src="' + imgSrc + '" alt="Image" class="img-responsive">';
                        }
                    });
                } else if (typeof pictures === 'object' && pictures.large_src) {
                    htmlContent += '<img src="' + pictures.large_src + '" alt="Image" class="img-responsive">';
                }

                htmlContent += '</div>';
            }
        });

        // Thêm footer nếu có
        if (firstItem.Topic_footer) {
            const footerContent = typeof decodeHtmlEntities === 'function' ?
                decodeHtmlEntities(firstItem.Topic_footer) : firstItem.Topic_footer;
            htmlContent += '<hr><div class="topic-footer">' + footerContent + '</div>';
        }

        // Sử dụng HTML đã tạo làm nội dung bản nháp
        draftContent = htmlContent;

    } else if (typeof workflowData === 'object') {
        // Xử lý khi workflowData là object
        draftTitle = workflowData.Title || workflowData.title || workflowData.draft_title || '';
        draftContent = workflowData.Content || workflowData.content || workflowData.draft_content || workflowData.Item_Content || '';
        metaDescription = workflowData.Description || workflowData.description || workflowData.meta_description || workflowData.Summary || workflowData.summary || '';
        keywords = workflowData.Keywords || workflowData.keywords || workflowData.TopicKeywords || workflowData.topic_keywords || '';

        // Nếu content có cấu trúc đặc biệt, xử lý nội dung
        if (!draftContent && workflowData.data && workflowData.data.response) {
            const responseData = workflowData.data.response;
            if (Array.isArray(responseData) && responseData.length > 0) {
                // Gọi đệ quy để xử lý dữ liệu mảng
                return createDraftFromWorkflowContent(responseData, topicId);
            } else if (typeof responseData === 'object') {
                // Cập nhật các trường từ response
                draftTitle = responseData.Title || responseData.title || draftTitle;
                draftContent = responseData.Content || responseData.content || responseData.Item_Content || draftContent;
                metaDescription = responseData.Description || responseData.Summary || metaDescription;
                keywords = responseData.Keywords || responseData.TopicKeywords || keywords;
            }
        }
    } else if (typeof workflowData === 'string') {
        // Nếu là string, thử phân tích JSON
        try {
            const parsedData = JSON.parse(workflowData);
            return createDraftFromWorkflowContent(parsedData, topicId);
        } catch (e) {
            // Nếu không phải JSON, coi như nội dung thuần túy
            draftContent = workflowData;
            draftTitle = 'New Draft from Workflow - ' + new Date().toLocaleString();
        }
    }

    // Kiểm tra và đảm bảo tiêu đề hợp lệ
    if (!draftTitle) {
        draftTitle = 'New Draft from Workflow - ' + new Date().toLocaleString();
    }

    // Đảm bảo nội dung không rỗng
    if (!draftContent && htmlContent) {
        draftContent = htmlContent;
    }

    // Log để kiểm tra
    console.log('Draft data extracted:', {
        title: draftTitle,
        contentLength: draftContent ? draftContent.length : 0,
        metaDescription: metaDescription,
        keywords: keywords
    });

    // Hiển thị chỉ báo đang tải
    showLoadingIndicator(_l('creating_new_draft'));

    // Tạo bản nháp mới qua API
    $.ajax({
        url: admin_url + 'topics/ultimate_editor/create_draft',
        type: 'POST',
        data: {
            topic_id: topicId,
            draft_title: draftTitle,
            draft_content: draftContent,
            draft_description: metaDescription, // Thêm description vào dữ liệu gửi đi
            draft_metadata: JSON.stringify({
                meta_description: metaDescription,
                keywords: keywords,
                draft_description: metaDescription,
                workflow_data: JSON.stringify(workflowData) // Lưu dữ liệu gốc để tham khảo
            }),
            set_as_active: true
        },
        success: function (response) {
            hideLoadingIndicator();
            console.log('Create draft API response:', response);

            if (typeof response === 'string') {
                try {
                    response = JSON.parse(response);
                    console.log('Parsed response:', response);
                } catch (e) {
                    console.error('Failed to parse create draft response:', e);
                    alert_float('danger', _l('error_creating_draft'));
                    return;
                }
            }

            if (response.success) {
                $('#new-draft-modal').modal('hide');
                console.log('New draft created successfully with ID:', response.draft_id);
                alert_float('success', _l('draft_created_successfully'));

                // Lưu thông tin draft ID vào sessionStorage để tự động load sau khi refresh
                if (response.draft_id) {
                    console.log('Storing draft ID in sessionStorage:', response.draft_id);
                    sessionStorage.setItem('last_created_draft_id', response.draft_id);
                }

                // Reload trang sau một khoảng thời gian ngắn
                console.log('Page will reload in 1 second');
                setTimeout(function () {
                    console.log('Reloading page...');
                    window.location.reload();
                }, 1000);
            } else {
                console.error('Failed to create draft:', response.message || 'Unknown error');
                alert_float('danger', response.message || _l('error_creating_draft'));
            }
        },
        error: function (xhr, status, error) {
            hideLoadingIndicator();
            console.error('AJAX error when creating draft:', { xhr: xhr, status: status, error: error });
            alert_float('danger', 'Error creating new draft: ' + error);
        }
    });
}


/**
 * THỰC THI (EXECUTION FUNCTION)
 * Xuất bản bản nháp
 */
function publishDraft() {
    console.log('publishDraft() called - Opening publish modal');
    // Show the publish modal instead of publishing directly
    $('#publish-modal').modal({
        backdrop: 'static',
        keyboard: false,
        show: true
    });
    console.log('Modal should be open now');
    return false;
}

/**
 * THỰC THI (EXECUTION FUNCTION)
 * Tải danh sách topic controllers
 */
function loadTopicControllers() {
    console.log('Loading topic controllers...');

    // Kiểm tra DOM đã sẵn sàng chưa
    if (!$('#topic-controller-select').length) {
        console.warn('Topic controller select element not found in DOM yet, waiting...');
        // Thử lại sau 500ms nếu phần tử chưa tồn tại
        setTimeout(loadTopicControllers, 500);
        return;
    }

    // Đảm bảo admin_url được xác định
    var apiUrl = admin_url + 'topics/ultimate_editor/get_topic_controllers';

    console.log('Fetching controllers from:', apiUrl);

    // Hiển thị loading spinner bên cạnh dropdown
    var $select = $('#topic-controller-select');
    var $selectContainer = $select.closest('.form-group');

    // Add loading spinner if not already present
    if ($selectContainer.find('.controller-loading-indicator').length === 0) {
        $selectContainer.append('<div class="controller-loading-indicator" style="position:absolute; right:30px; top:35px;"><i class="fa fa-spinner fa-spin" style="color:#03a9f4;"></i> <span style="color:#03a9f4; font-size:12px;">Loading controllers...</span></div>');
    } else {
        $selectContainer.find('.controller-loading-indicator').removeClass('hide');
    }

    // Add loading class to select
    $select.addClass('loading');

    // Hiển thị placeholder loading trong dropdown
    $select.find('option:not(:first)').remove();
    $select.append('<option value="" disabled>Loading controllers...</option>');

    // Disable select while loading
    $select.prop('disabled', true);

    $.ajax({
        url: apiUrl,
        type: 'GET',
        dataType: 'json',
        cache: false, // Tránh cache
        success: function (response) {
            console.log('Controllers response:', response);
            // Xóa option "Loading controllers..."
            $select.find('option:not(:first)').remove();

            // Enable select
            $select.prop('disabled', false);
            $select.removeClass('loading');

            // Hide loading indicator
            $selectContainer.find('.controller-loading-indicator').addClass('hide');

            if (response && response.success && response.data && response.data.length > 0) {
                // Populate the dropdown
                $.each(response.data, function (index, controller) {
                    // Ensure controller object has required properties
                    if (!controller || !controller.id) {
                        console.warn('Invalid controller data:', controller);
                        return;
                    }

                    // Generate display name with fallbacks
                    var displayName = '';

                    // First choice: Use provided name
                    if (controller.name) {
                        displayName = controller.name;
                    }
                    // Second choice: Use site name
                    else if (controller.site) {
                        displayName = controller.site;
                    }
                    // Last resort: Use platform and ID
                    else {
                        displayName = (controller.platform || 'unknown') + ' #' + controller.id;
                    }

                    // Add platform as suffix if not already in the name
                    if (controller.platform && !displayName.toLowerCase().includes(controller.platform.toLowerCase())) {
                        displayName += ' (' + controller.platform + ')';
                    }

                    var $option = $('<option>', {
                        value: controller.id,
                        text: displayName,
                        'data-platform': controller.platform,
                        'data-connected': controller.connected ? 'true' : 'false',
                        'data-site': controller.site || ''
                    });

                    // If controller is not connected, disable the option
                    if (!controller.connected) {
                        $option.attr('disabled', 'disabled')
                            .attr('title', 'Controller is not connected');
                    }

                    $select.append($option);
                });

                // Initialize select picker if available
                if ($.fn.selectpicker) {
                    $select.selectpicker('refresh');
                }

                // Check if any options were added
                setTimeout(function () {
                    var optionsCount = $select.find('option').length;
                    console.log('Options count after population:', optionsCount);
                    if (optionsCount <= 1) {
                        console.warn('No options were added to the dropdown');
                        alert_float('warning', 'No controllers were loaded. Please check console for details.');
                    }
                }, 100);

                // Show success message
                alert_float('success', 'Controllers loaded successfully');
            } else {
                console.error('Failed to load controllers or no controllers available:', response);
                $select.append('<option value="" disabled>No controllers available</option>');
                alert_float('warning', 'No topic controllers available');
            }
        },
        error: function (xhr, status, error) {
            // Re-enable select
            $select.prop('disabled', false);
            $select.removeClass('loading');

            // Hide loading indicator
            $selectContainer.find('.controller-loading-indicator').addClass('hide');

            $select.find('option:not(:first)').remove();
            $select.append('<option value="" disabled>Error loading controllers</option>');

            console.error('Error loading controllers:', error);
            console.error('Status:', status);
            console.error('Response:', xhr.responseText);

            try {
                var response = JSON.parse(xhr.responseText);
                console.error('Parsed error response:', response);
            } catch (e) {
                console.error('Could not parse error response');
            }

            alert_float('danger', 'Network error while loading controllers: ' + error);
        }
    });
}

/**
 * THỰC THI (EXECUTION FUNCTION)
 * Tải danh sách categories từ controller
 */
function loadCategories(controllerId) {
    console.log('Loading categories for controller ID:', controllerId);

    if (!controllerId) {
        // No controller selected - show message
        $('#categories-tree').html('<div class="alert alert-info text-center">' +
            '<i class="fa fa-info-circle"></i><br>' +
            'Please select a topic controller to enable categories</div>');
        return;
    }

    // Show loading indicator in categories tree
    $('#categories-tree').html('<div class="categories-loading-indicator text-center p-3"><i class="fa fa-spinner fa-spin fa-2x"></i><p class="mt-2" style="color:#03a9f4;">Loading categories...</p></div>');

    // Hide selected category info if visible
    $('#selected_category_info').removeClass('active');

    // AJAX request to get categories
    $.ajax({
        url: admin_url + 'topics/ultimate_editor/get_platform_categories/' + controllerId,
        type: 'GET',
        dataType: 'json',
        cache: false, // Prevent caching
        success: function (response) {
            // Remove loading indicator
            $('.categories-loading-indicator').remove();

            try {
                // Ensure response is in the correct format
                var data = typeof response === 'string' ? JSON.parse(response) : response;

                if (data.success && data.data) {
                    console.log('Categories loaded successfully:', data.data.length);

                    // Store categories data for later use
                    window.DraftWriter = window.DraftWriter || {};
                    window.DraftWriter.publish = window.DraftWriter.publish || {};
                    window.DraftWriter.publish.categories = data.data;

                    // Render the category tree
                    renderCategoryTree(data.data);
                } else {
                    console.error('Failed to load categories:', data.message || 'Unknown error');
                    $('#categories-tree').html('<div class="alert alert-warning">' +
                        (data.message || 'Failed to load categories') + '</div>');

                    // Show notification
                    alert_float('warning', data.message || 'Failed to load categories');
                }
            } catch (e) {
                console.error('Error parsing categories response:', e);
                $('#categories-tree').html('<div class="alert alert-danger">Error processing categories data</div>');

                // Show notification
                alert_float('danger', 'Error processing categories data');
            }
        },
        error: function (xhr, status, error) {
            // Remove loading indicator
            $('.categories-loading-indicator').remove();

            console.error('Network error while loading categories:', error);
            console.error('Status:', status);
            console.error('Response:', xhr.responseText);

            $('#categories-tree').html('<div class="alert alert-danger">Network error while loading categories</div>');

            // Show notification
            alert_float('danger', 'Network error while loading categories: ' + error);
        }
    });
}

/**
 * THỰC THI (EXECUTION FUNCTION)
 * Tải danh sách tags từ controller
 */
function loadTags(controllerId) {
    // Set a flag to prevent duplicate calls
    if (window.loadingTags) {
        console.log('Already loading tags, skipping duplicate request');
        return;
    }

    window.loadingTags = true;

    if (!controllerId) {
        // No controller selected - show message
        $('#popular-tags-list').html('<div class="alert alert-info text-center">' +
            '<i class="fa fa-info-circle"></i><br>' +
            'Please select a topic controller to enable tags</div>');
        window.loadingTags = false;
        return;
    }

    // Show loading indicators
    var $tagsContainer = $('#tags-select').closest('.form-group');

    if ($tagsContainer.find('.tags-loading-indicator').length === 0) {
        $tagsContainer.append('<div class="tags-loading-indicator" style="margin-top:10px;"><i class="fa fa-spinner fa-spin"></i> <span style="color:#03a9f4; font-size:12px;">Loading tags...</span></div>');
    } else {
        $tagsContainer.find('.tags-loading-indicator').removeClass('hide');
    }

    $('#popular-tags-list').html('<div class="text-center p-2"><i class="fa fa-spinner fa-spin"></i> <span style="color:#03a9f4; font-size:12px;">Loading popular tags...</span></div>');

    downloadPopularTags(controllerId, function (response) {
        // Hide loading indicators
        $tagsContainer.find('.tags-loading-indicator').addClass('hide');

        // Clear the container again before adding new content
        $('#popular-tags-list').empty();

        try {
            // Ensure response is in the correct format
            var data = typeof response === 'string' ? JSON.parse(response) : response;
            console.log('Tags loaded successfully:', data);
            if (data.success && data.data) {
                // Store tags data for later use
                window.DraftWriter = window.DraftWriter || {};
                window.DraftWriter.publish = window.DraftWriter.publish || {};
                window.DraftWriter.publish.tags = data.data;


                // Use popular_tags from response if available, otherwise generate them
                if (data.popular_tags && data.popular_tags.length > 0) {
                    // Decode HTML entities in tag names
                    var popularTags = data.popular_tags.map(function (tag) {
                        if (tag && typeof tag === 'object' && tag.name) {
                            tag.name = decodeHtmlEntities(tag.name);
                        }
                        return tag;
                    });

                    console.log('Using server-provided popular tags:', popularTags);

                    // Render popular tags
                    UltimateEditorPublish.renderPopularTags(popularTags);
                }
                else if (data.data.tags && data.data.tags.length > 0) {
                    // Fallback: Generate popular tags from all tags
                    console.log('Generating popular tags from all tags');
                    var allTags = [...data.data.tags];

                    // Decode HTML entities in tag names
                    allTags.forEach(function (tag) {
                        if (tag && typeof tag === 'object' && tag.name) {
                            tag.name = decodeHtmlEntities(tag.name);
                        }
                    });

                    // Sort by count in descending order
                    allTags.sort(function (a, b) {
                        return (b.count || 0) - (a.count || 0);
                    });

                    // Get top 10 tags
                    var popularTags = allTags.slice(0, 10);

                    // Render popular tags
                    UltimateEditorPublish.renderPopularTags(popularTags);
                }
                else {
                    // Load draft tags if no tags available
                    UltimateEditorPublish.loadDraftTagsToPopularTags();
                }
            } else {
                console.error('Failed to load tags:', data.message || 'Unknown error');
                $('#tags-select').html('<option disabled>Failed to load tags</option>');
                $('#popular-tags-list').html('<div class="alert alert-warning">' +
                    (data.message || 'Failed to load tags') + '</div>');

                // Load draft tags even if controller tags failed
                UltimateEditorPublish.loadDraftTagsToPopularTags();

                // Show notification
                alert_float('warning', data.message || 'Failed to load tags');
            }
        } catch (e) {
            console.error('Error parsing tags response:', e);
            $('#tags-select').html('<option disabled>Error processing tags data</option>');
            $('#popular-tags-list').html('<div class="alert alert-danger">Error processing tags data</div>');

            // Load draft tags even if there was an error
            UltimateEditorPublish.loadDraftTagsToPopularTags();

            // Show notification
            alert_float('danger', 'Error processing tags data');
        }

        // Reset loading flag
        window.loadingTags = false;
    }, function (error) {
        // Hide loading indicators
        $tagsContainer.find('.tags-loading-indicator').addClass('hide');

        console.error('Network error while loading tags:', error);
        console.error('Status:', status);
        console.error('Response:', xhr.responseText);

        // Clear container before adding error message
        $('#popular-tags-list').empty();

        $('#tags-select').html('<option disabled>Error loading tags</option>');
        $('#popular-tags-list').html('<div class="alert alert-danger">Network error while loading tags</div>');

        // Load draft tags even if there was a network error
        UltimateEditorPublish.loadDraftTagsToPopularTags();

        // Show notification
        alert_float('danger', 'Network error while loading tags: ' + error);

        // Reset loading flag
        window.loadingTags = false;
    });
}

/**
 * THỰC THI (EXECUTION FUNCTION)
 * Kiểm tra xem ảnh đã tồn tại trong external data chưa
 * 
 * @param {string} imageUrl - URL của ảnh cần kiểm tra
 * @param {number} topicId - ID của topic
 * @returns {Promise} Promise chứa kết quả kiểm tra
 */
function checkImageExternalData(imageUrl, topicId) {
    return new Promise((resolve) => {
        if (!imageUrl || !topicId) {
            resolve({ exists: false });
            return;
        }

        const rel_id = md5(imageUrl);

        $.ajax({
            url: admin_url + 'topics/check_image_external_data',
            type: 'POST',
            data: {
                topic_id: topicId,
                rel_id: rel_id,
                rel_type: 'image',
            },
            success: function (response) {
                try {
                    if (typeof response === 'string') {
                        response = JSON.parse(response);
                    }
                    resolve({
                        exists: response.exists,
                        data: response.rel_data
                    });
                } catch (e) {
                    console.error('Error parsing response:', e);
                    resolve({ exists: false });
                }
            },
            error: function () {
                resolve({ exists: false });
            }
        });
    });
}

/**
 * THỰC THI (EXECUTION FUNCTION)
 * Lưu ảnh vào server (sử dụng workflow của TopicComposerProcessor)
 * 
 * @param {string} imageUrl - URL của ảnh cần lưu
 * @param {number} topicId - ID của topic
 * @returns {Promise} Promise chứa kết quả lưu ảnh
 */
function saveImageToServer(imageUrl, topicId) {
    return new Promise((resolve, reject) => {
        // Chuẩn bị dữ liệu cho workflow
        const workflowData = {
            workflow_id: window.WORKFLOW_ID || null,
            audit_step: 6,
            changes_data: {
                image_url: imageUrl
            }
        };

        // Gọi API để thực hiện workflow
        $.ajax({
            url: admin_url + 'topics/process_data/' + topicId + '/TopicComposer',
            type: 'POST',
            data: workflowData,
            success: function (response) {
                try {
                    if (typeof response === 'string') {
                        response = JSON.parse(response);
                    }

                    if (response.success) {
                        resolve({
                            success: true,
                            url: response.data.wordpress_url || imageUrl,
                            data: response.data
                        });
                    } else {
                        reject(new Error(response.message || 'Failed to download image'));
                    }
                } catch (error) {
                    reject(error);
                }
            },
            error: function (xhr, status, error) {
                reject(new Error(error));
            }
        });
    });
}

/**
 * UI/PRESENTATION FUNCTION
 * Bind events to draft actions
 */
function bindDraftActions() {
    // Use draft button
    $('.use-draft-btn').off('click').on('click', function () {
        const draftId = $(this).data('draft-id');
        console.log('Loading draft with ID:', draftId);

        // Confirm if there are unsaved changes
        if (isDirty) {
            if (!confirm(_l('unsaved_changes_will_be_lost'))) {
                return;
            }
        }

        // Load the draft
        loadDraft(draftId);

        // After loading, ensure the draft data is also in the hidden field for persistence
        // This is a redundant safety measure
        setTimeout(function () {
            const sessionDraftData = sessionStorage.getItem('full_draft_data');
            if (sessionDraftData) {
                $('#full-draft-data').val(sessionDraftData);
                console.log('Updated hidden field with session draft data for persistence');
            }
        }, 1000);
    });

    // Preview draft button
    $('.preview-draft-btn').off('click').on('click', function () {
        const draftId = $(this).data('draft-id');
        console.log('Previewing draft with ID:', draftId);

        // Show loading
        showLoadingIndicator(_l('loading_draft'));

        // Get draft data
        $.ajax({
            url: admin_url + 'topics/ultimate_editor/get_draft/' + draftId,
            type: 'GET',
            dataType: 'json',
            success: function (response) {
                hideLoadingIndicator();

                if (response.success && response.draft) {
                    enterPreviewMode(response.draft);
                } else {
                    alert_float('danger', response.message || _l('error_loading_draft'));
                }
            },
            error: function () {
                hideLoadingIndicator();
                alert_float('danger', _l('error_loading_draft'));
            }
        });
    });
}