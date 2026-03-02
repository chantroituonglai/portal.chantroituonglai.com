# Hướng Dẫn Triển Khai Nhanh: TopicController - Predefined Action Buttons

## Tổng Quan Thực Hiện

### ✅ Đã Có Sẵn Trong Codebase
- **Model**: `Topic_action_button_model.php` **đã tồn tại**! Với đầy đủ các phương thức CRUD và logic xử lý.
- **Controller**: Cần thêm các phương thức quản lý action buttons vào `Controllers.php` (không phải Topics.php).
- **View**: `views/includes/topic_detail_action_buttons.php` đã có sẵn logic hiển thị nút (template đã hoàn chỉnh).
- **Publishing**: Phương thức `publish_to_platform` trong `Ultimate_editor.php` đã có sẵn để xử lý việc đăng bài.

### 🛠 Cần Triển Khai
1. **Bổ sung các phương thức xử lý Action Buttons vào Controllers.php**
2. **Tích hợp Action Buttons với Topic Controller Detail View**
3. **Tích hợp với Ultimate Editor / Draft Writer**
4. **Kết nối Action Buttons với cơ chế xuất bản**
5. **Bổ sung một số tính năng nâng cao và tối ưu UX/UI**

## Quy Trình Triển Khai Chi Tiết

### Bước 1: Thêm Các Phương Thức Vào `Controllers.php`

```php
// Thêm vào Controllers.php

/**
 * Hiển thị danh sách các Action Buttons
 */
public function action_buttons()
{
    if (!has_permission('topics', '', 'view')) {
        access_denied('topics');
    }

    if ($this->input->is_ajax_request()) {
        $this->load->model('Topic_action_button_model');
        $action_buttons = $this->Topic_action_button_model->get();

        $data = [];
        foreach ($action_buttons as $button) {
            $row = [];
            
            // Name
            $row[] = '<a href="#" onclick="edit_action_button(' . $button['id'] . '); return false;" class="tw-text-neutral-500 hover:tw-text-neutral-700 focus:tw-text-neutral-700">' . html_escape($button['name']) . '</a>';
            
            // Button Type
            $row[] = '<span class="label label-' . html_escape($button['button_type']) . '">' . 
                     ucfirst(html_escape($button['button_type'])) . '</span>';
            
            // Workflow ID
            $row[] = '<span class="text-nowrap">' . html_escape($button['workflow_id']) . '</span>';
            
            // Trigger Type
            $row[] = '<span class="label label-info">' . 
                     ucfirst(html_escape($button['trigger_type'])) . '</span>';
            
            // Target Action Type
            $row[] = '<span class="text-nowrap">' . 
                     ($button['target_action_type'] ? html_escape($button['target_action_type']) : '-') . '</span>';
            
            // Target Action State
            $row[] = '<span class="text-nowrap">' . 
                     ($button['target_action_state'] ? html_escape($button['target_action_state']) : '-') . '</span>';
            
            // Status toggle switch
            $row[] = '<div class="onoffswitch">
                        <input type="checkbox" data-switch-url="' . admin_url('topics/controllers/change_button_status') . '" 
                               name="onoffswitch" class="onoffswitch-checkbox" 
                               id="status_' . $button['id'] . '" 
                               data-id="' . $button['id'] . '" ' . 
                               ($button['status'] == 1 ? 'checked' : '') . '>
                        <label class="onoffswitch-label" for="status_' . $button['id'] . '"></label>
                      </div>';
            
            // Options column
            $options = '';
            if (has_permission('topics', '', 'edit')) {
                $options .= '<a href="#" onclick="edit_action_button(' . $button['id'] . '); return false;" 
                              class="tw-text-neutral-500 hover:tw-text-neutral-700 focus:tw-text-neutral-700">
                              <i class="fa-regular fa-pen-to-square fa-lg"></i>
                           </a>';
            }
            if (has_permission('topics', '', 'delete')) {
                $options .= ' <a href="' . admin_url('topics/controllers/delete_action_button/' . $button['id']) . '" 
                              class="tw-text-neutral-500 hover:tw-text-neutral-700 focus:tw-text-neutral-700 _delete">
                              <i class="fa-regular fa-trash-can fa-lg"></i>
                           </a>';
            }
            $row[] = '<div class="tw-flex tw-items-center tw-space-x-3">' . $options . '</div>';
            
            $data[] = $row;
        }

        echo json_encode(['data' => $data]);
        return;
    }

    // Load view data
    $this->load->model('Topic_action_button_model');
    $data['action_buttons'] = $this->Topic_action_button_model->get();
    $this->load->model(['Action_type_model', 'Action_state_model']);
    $data['action_types'] = $this->Action_type_model->get_all_action_types();
    $data['action_states'] = $this->Action_state_model->get_all_action_states();
    $data['title'] = _l('topic_action_buttons');
    
    $this->load->view('controllers/action_buttons/manage', $data);
}

/**
 * Thêm/Sửa Action Button
 * @param string $id Button ID (nếu sửa)
 */
public function action_button($id = '')
{
    if (!has_permission('topics', '', 'view')) {
        access_denied('topics');
    }

    $this->load->model('Topic_action_button_model');

    if ($this->input->post()) {
        $post_data = $this->input->post();
        
        // Convert status từ checkbox
        $post_data['status'] = isset($post_data['status']) ? 1 : 0;

        // Handle ignore arrays
        $post_data['ignore_types'] = !empty($post_data['ignore_types']) ? 
            json_encode($post_data['ignore_types']) : null;
        $post_data['ignore_states'] = !empty($post_data['ignore_states']) ? 
            json_encode($post_data['ignore_states']) : null;

        // Kiểm tra xem có ID trong post data không
        $button_id = $post_data['id'] ?? '';
        unset($post_data['id']); // Xóa id khỏi post_data để tránh conflict

        if ($button_id) {
            // Update
            if (!has_permission('topics', '', 'edit')) {
                access_denied('topics');
            }
            $success = $this->Topic_action_button_model->update($post_data, $button_id);
            $message = _l('updated_successfully', _l('action_button'));
        } else {
            // Create
            if (!has_permission('topics', '', 'create')) {
                access_denied('topics');
            }
            $success = $this->Topic_action_button_model->add($post_data);
            $message = _l('added_successfully', _l('action_button'));
        }

        if ($success) {
            echo json_encode([
                'success' => true,
                'message' => $message
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => _l('error_adding_updating_action_button')
            ]);
        }
        return;
    }

    // Load required data for the form
    $this->load->model(['Action_type_model', 'Action_state_model']);
    $data['action_types'] = $this->Action_type_model->get_all_action_types();
    $data['action_states'] = $this->Action_state_model->get_all_action_states();
    
    if ($id) {
        $data['action_button'] = $this->Topic_action_button_model->get($id);
        if (!$data['action_button']) {
            show_404();
        }
        $data['title'] = _l('edit_action_button');
    } else {
        $data['title'] = _l('new_action_button');
    }

    $this->load->view('controllers/action_buttons/modal', $data);
}

/**
 * Đổi trạng thái của button
 * @param int $id Button ID
 * @param int $status Status (0/1)
 */
public function change_button_status()
{
    if (!has_permission('topics', '', 'edit')) {
        ajax_access_denied();
    }

    $id = $this->input->post('id');
    $status = $this->input->post('status');

    $this->load->model('Topic_action_button_model');
    $success = $this->Topic_action_button_model->change_status($id, $status);

    echo json_encode([
        'success' => $success,
    ]);
}

/**
 * Xóa Action Button
 * @param int $id Button ID
 */
public function delete_action_button($id)
{
    if (!has_permission('topics', '', 'delete')) {
        ajax_access_denied();
    }

    $this->load->model('Topic_action_button_model');
    $success = $this->Topic_action_button_model->delete($id);

    echo json_encode([
        'success' => $success,
    ]);
}

/**
 * Lấy Action States theo Type
 */
public function get_action_states()
{
    if (!$this->input->is_ajax_request()) {
        show_404();
    }

    $type_code = $this->input->post('type_code');
    $this->load->model('Action_state_model');
    $states = $this->Action_state_model->get_states_by_type($type_code);

    echo json_encode($states);
}
```

### Bước 2: Tích Hợp Action Buttons với Topic Controller Detail View

```php
// Cập nhật Controllers.php - Phương thức view($id)
public function view($id)
{
    // ... code hiện tại ...
    
    // Thêm dòng sau để lấy action buttons phù hợp
    $this->load->model('Topic_action_button_model');
    
    // Lấy các topics được quản lý bởi controller này
    $topic_ids = $this->Topic_controller_model->get_topic_ids_by_controller($id);
    $data['action_buttons'] = [];
    
    // Nếu có topic, lấy action buttons phù hợp với topic đầu tiên
    if (!empty($topic_ids)) {
        $data['action_buttons'] = $this->Topic_action_button_model->get_buttons_for_topic($topic_ids[0]);
    }
    
    // ... code hiện tại ...
    $this->load->view('controllers/detail', $data);
}
```

```php
// Cập nhật views/controllers/detail.php - Thêm vào phần thích hợp
<?php $this->load->view('includes/topic_detail_action_buttons', ['topic' => $controller, 'action_buttons' => $action_buttons]); ?>
```

### Bước 3: Tích Hợp với Ultimate Editor / Draft Writer

```php
// Thêm vào Ultimate_editor.php
/**
 * Get Action Buttons For Topic Controller
 * 
 * @param int $controller_id
 * @return json
 */
public function get_controller_action_buttons($controller_id = null)
{
    if (!$controller_id && $this->input->get('controller_id')) {
        $controller_id = $this->input->get('controller_id');
    }
    
    if (!$controller_id) {
        echo json_encode(['success' => false, 'message' => 'Controller ID is required']);
        return;
    }
    
    $this->load->model(['Topic_controller_model', 'Topic_action_button_model']);
    
    // Lấy topic_id từ controller_id
    $topic_ids = $this->Topic_controller_model->get_topic_ids_by_controller($controller_id);
    if (empty($topic_ids)) {
        echo json_encode(['success' => false, 'message' => 'No topics found for this controller']);
        return;
    }
    
    // Lấy action buttons phù hợp với topic đầu tiên (topic master)
    $buttons = $this->Topic_action_button_model->get_buttons_for_topic($topic_ids[0]);
    
    // Lọc ra các nút liên quan đến xuất bản
    $publish_buttons = array_filter($buttons, function($button) {
        return isset($button['action_command']) && 
               in_array($button['action_command'], ['PUBLISH_POST', 'PREVIEW_POST', 'CHECK_STATUS']);
    });
    
    echo json_encode([
        'success' => true,
        'buttons' => $publish_buttons,
        'all_buttons' => $buttons
    ]);
}

/**
 * Get Topics For Controller (for Action Button dropdown)
 * 
 * @param int $controller_id
 * @return json
 */
public function get_controller_topics($controller_id = null)
{
    if (!$controller_id && $this->input->get('controller_id')) {
        $controller_id = $this->input->get('controller_id');
    }
    
    if (!$controller_id) {
        echo json_encode(['success' => false, 'message' => 'Controller ID is required']);
        return;
    }
    
    $this->load->model('Topic_controller_model');
    $topics = $this->Topic_controller_model->get_topics_by_controller($controller_id);
    
    echo json_encode([
        'success' => true,
        'topics' => $topics
    ]);
}
```

```javascript
// Thêm vào assets/js/ultimate_editor_integration.js (tạo file mới)

/**
 * Ultimate Editor - Action Buttons Integration
 * This file handles the integration between Action Buttons and Ultimate Editor
 */

var UltimateEditorActionButtons = {};

/**
 * Initialize Action Buttons integration with Ultimate Editor
 */
UltimateEditorActionButtons.init = function() {
    'use strict';
    
    console.log('Initializing Action Buttons integration with Ultimate Editor...');
    
    // Dữ liệu lưu trữ
    this.data = {
        controllerId: null,
        topicId: null,
        buttons: []
    };
    
    // Khởi tạo container cho action buttons trong publish tab
    this.createButtonsContainer();
    
    // Đăng ký các sự kiện
    this.bindEvents();
    
    return this;
};

/**
 * Tạo container cho action buttons trong publish tab
 */
UltimateEditorActionButtons.createButtonsContainer = function() {
    // Kiểm tra xem tab publish đã có chưa
    if (!$('#tab_publish').length) return;
    
    // Kiểm tra xem container đã có chưa
    if ($('#action-buttons-container').length) return;
    
    // Tạo container
    const containerHtml = `
        <div class="panel_s panel-action-buttons" id="action-buttons-panel">
            <div class="panel-heading">
                <h4 class="panel-title">
                    ${app.lang.action_buttons || 'Action Buttons'}
                </h4>
            </div>
            <div class="panel-body">
                <div id="action-buttons-container">
                    <div class="text-center text-muted action-buttons-placeholder">
                        <i class="fa fa-info-circle"></i> ${app.lang.select_controller_to_view_buttons || 'Select a controller to view available action buttons'}
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Thêm vào tab publish sau các panel khác
    $('#tab_publish .row').append(`<div class="col-md-12">${containerHtml}</div>`);
    
    return this;
};

/**
 * Đăng ký các sự kiện
 */
UltimateEditorActionButtons.bindEvents = function() {
    // Controller change event trong publish tab
    $('#topic-controller-select').on('change', function() {
        const controllerId = $(this).val();
        if (controllerId) {
            UltimateEditorActionButtons.loadButtonsForController(controllerId);
        } else {
            UltimateEditorActionButtons.clearButtons();
        }
    });
    
    // Khi tab publish được hiển thị, refresh buttons
    $('a[href="#tab_publish"]').on('shown.bs.tab', function() {
        const controllerId = $('#topic-controller-select').val();
        if (controllerId) {
            UltimateEditorActionButtons.loadButtonsForController(controllerId);
        }
    });
    
    // Xử lý click action button trong container
    $(document).on('click', '#action-buttons-container .action-button', function(e) {
        e.preventDefault();
        const $button = $(this);
        const actionCommand = $button.data('action-command');
        
        // Xử lý các loại command khác nhau
        switch(actionCommand) {
            case 'PUBLISH_POST':
                UltimateEditorActionButtons.handlePublishAction($button);
                break;
            case 'PREVIEW_POST':
                UltimateEditorActionButtons.handlePreviewAction($button);
                break;
            case 'CHECK_STATUS':
                UltimateEditorActionButtons.handleCheckStatusAction($button);
                break;
            default:
                // Xử lý generic action buttons
                UltimateEditorActionButtons.handleGenericAction($button);
                break;
        }
    });
    
    return this;
};

/**
 * Tải action buttons cho controller
 * @param {string} controllerId - ID của controller
 */
UltimateEditorActionButtons.loadButtonsForController = function(controllerId) {
    if (!controllerId) return;
    
    // Lưu controller ID
    this.data.controllerId = controllerId;
    
    // Hiển thị loading
    $('#action-buttons-container').html(`
        <div class="text-center">
            <i class="fa fa-spinner fa-spin"></i> ${app.lang.loading || 'Loading...'}
        </div>
    `);
    
    // Gọi API để lấy buttons
    $.ajax({
        url: admin_url + 'topics/ultimate_editor/get_controller_action_buttons',
        type: 'GET',
        data: { controller_id: controllerId },
        dataType: 'json',
        success: function(response) {
            if (response.success && response.buttons.length > 0) {
                UltimateEditorActionButtons.renderButtons(response.buttons);
                UltimateEditorActionButtons.data.buttons = response.all_buttons || response.buttons;
            } else {
                UltimateEditorActionButtons.renderNoButtons();
            }
        },
        error: function() {
            UltimateEditorActionButtons.renderError();
        }
    });
    
    return this;
};

/**
 * Hiển thị action buttons
 * @param {Array} buttons - Danh sách các buttons
 */
UltimateEditorActionButtons.renderButtons = function(buttons) {
    const $container = $('#action-buttons-container');
    
    // Xóa nội dung hiện tại
    $container.empty();
    
    // Thêm tiêu đề
    $container.append(`<p class="text-muted">${app.lang.available_actions || 'Available Actions'}:</p>`);
    
    // Tạo button container
    const $btnContainer = $('<div class="action-buttons-group"></div>');
    
    // Thêm từng button
    buttons.forEach(function(button) {
        const $btn = $(`
            <button type="button" 
                class="btn btn-${button.button_type || 'default'} action-button m-r-5 m-b-5"
                data-workflow-id="${button.workflow_id || ''}"
                data-target-type="${button.target_action_type || ''}"
                data-target-state="${button.target_action_state || ''}"
                data-button-id="${button.id || ''}"
                data-action-command="${button.action_command || ''}">
                <i class="fa fa-${UltimateEditorActionButtons.getIconForCommand(button.action_command)}"></i> ${button.name}
            </button>
        `);
        
        $btnContainer.append($btn);
    });
    
    // Thêm vào container
    $container.append($btnContainer);
    
    return this;
};

/**
 * Hiển thị thông báo không có buttons
 */
UltimateEditorActionButtons.renderNoButtons = function() {
    $('#action-buttons-container').html(`
        <div class="text-center text-muted">
            <i class="fa fa-info-circle"></i> ${app.lang.no_action_buttons || 'No action buttons available for this controller'}
        </div>
    `);
    
    return this;
};

/**
 * Hiển thị lỗi khi tải buttons
 */
UltimateEditorActionButtons.renderError = function() {
    $('#action-buttons-container').html(`
        <div class="text-center text-danger">
            <i class="fa fa-exclamation-circle"></i> ${app.lang.error_loading_buttons || 'Error loading action buttons'}
        </div>
    `);
    
    return this;
};

/**
 * Xóa buttons
 */
UltimateEditorActionButtons.clearButtons = function() {
    this.data.buttons = [];
    this.data.controllerId = null;
    
    $('#action-buttons-container').html(`
        <div class="text-center text-muted action-buttons-placeholder">
            <i class="fa fa-info-circle"></i> ${app.lang.select_controller_to_view_buttons || 'Select a controller to view available action buttons'}
        </div>
    `);
    
    return this;
};

/**
 * Lấy icon phù hợp với loại command
 * @param {string} command - Action command
 * @returns {string} - Tên class icon
 */
UltimateEditorActionButtons.getIconForCommand = function(command) {
    switch(command) {
        case 'PUBLISH_POST':
            return 'upload';
        case 'PREVIEW_POST':
            return 'eye';
        case 'CHECK_STATUS':
            return 'refresh';
        default:
            return 'play-circle';
    }
};

/**
 * Xử lý action button xuất bản
 * @param {jQuery} $button - Element button
 */
UltimateEditorActionButtons.handlePublishAction = function($button) {
    // Kiểm tra xem đã save draft chưa
    if (typeof UltimateEditor !== 'undefined' && typeof UltimateEditor.isDraftSaved !== 'function') {
        alert_float('warning', app.lang.save_draft_first || 'Please save your draft first');
        return;
    }
    
    // Lấy dữ liệu từ UltimateEditorPublish
    const publishData = UltimateEditorPublish.getPublishData();
    
    // Lấy dữ liệu từ button
    const workflowId = $button.data('workflow-id');
    const buttonId = $button.data('button-id');
    
    // Nếu không có controller_id, hiển thị thông báo
    if (!publishData.controller_id) {
        alert_float('warning', app.lang.select_controller_first || 'Please select a controller first');
        return;
    }
    
    // Xác nhận trước khi xuất bản
    Swal.fire({
        title: app.lang.confirm_publish || 'Publish Content',
        text: app.lang.confirm_publish_question || 'Are you sure you want to publish this content?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: app.lang.yes_publish || 'Yes, publish it!',
        cancelButtonText: app.lang.cancel || 'No, cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            // Thay đổi trạng thái nút
            const originalText = $button.html();
            $button.addClass('disabled').html('<i class="fa fa-spinner fa-spin"></i> ' + (app.lang.publishing || 'Publishing...'));
            
            // Trước khi xuất bản, lưu draft hiện tại
            if (typeof UltimateEditor !== 'undefined' && typeof UltimateEditor.saveDraft === 'function') {
                UltimateEditor.saveDraft(false, function() {
                    // Sau khi lưu draft, tiếp tục xuất bản
                    executePublishWithCurrentData();
                });
            } else {
                // Nếu không có UltimateEditor, xuất bản luôn
                executePublishWithCurrentData();
            }
            
            function executePublishWithCurrentData() {
                // Lấy dữ liệu xuất bản đã cập nhật
                const currentPublishData = UltimateEditorPublish.getPublishData();
                
                // Thêm thông tin từ action button
                currentPublishData.workflow_id = workflowId;
                currentPublishData.button_id = buttonId;
                
                // Gọi API để xuất bản
                $.ajax({
                    url: admin_url + 'topics/ultimate_editor/execute_publish_action',
                    type: 'POST',
                    data: currentPublishData,
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Hiển thị thông báo thành công
                            alert_float('success', response.message || app.lang.content_published_successfully);
                            
                            // Cập nhật tab publish
                            if (response.data && response.data.permalink) {
                                $('#publish-status-message').html(`
                                    <div class="alert alert-success">
                                        <i class="fa fa-check-circle"></i> ${response.message || app.lang.content_published_successfully}
                                    </div>
                                    <div class="mtop10">
                                        <a href="${response.data.permalink}" target="_blank" class="btn btn-info">
                                            <i class="fa fa-external-link"></i> ${app.lang.view_published_content || 'View Published Content'}
                                        </a>
                                    </div>
                                `);
                                
                                // Cập nhật thông tin xuất bản trong draft
                                if (typeof UltimateEditor !== 'undefined' && typeof UltimateEditor.updateDraftPublishStatus === 'function') {
                                    UltimateEditor.updateDraftPublishStatus(true, response.data.permalink);
                                }
                            }
                        } else {
                            // Hiển thị lỗi
                            alert_float('danger', response.message || app.lang.failed_to_publish_content);
                            
                            // Cập nhật tab publish
                            $('#publish-status-message').html(`
                                <div class="alert alert-danger">
                                    <i class="fa fa-times-circle"></i> ${response.message || app.lang.failed_to_publish_content}
                                </div>
                            `);
                        }
                    },
                    error: function(xhr, status, error) {
                        // Hiển thị lỗi
                        alert_float('danger', app.lang.server_error + ': ' + error);
                        
                        // Cập nhật tab publish
                        $('#publish-status-message').html(`
                            <div class="alert alert-danger">
                                <i class="fa fa-times-circle"></i> ${app.lang.server_error || 'Server Error'}: ${error}
                            </div>
                        `);
                    },
                    complete: function() {
                        // Khôi phục trạng thái nút
                        $button.removeClass('disabled').html(originalText);
                    }
                });
            }
        }
    });
};

/**
 * Xử lý action button xem trước
 * @param {jQuery} $button - Element button
 */
UltimateEditorActionButtons.handlePreviewAction = function($button) {
    // Triển khai tương tự previewDraft trong UltimateEditor
    if (typeof UltimateEditor !== 'undefined' && typeof UltimateEditor.previewDraft === 'function') {
        UltimateEditor.previewDraft();
    } else {
        alert_float('warning', app.lang.preview_not_available || 'Preview is not available');
    }
};

/**
 * Xử lý action button kiểm tra trạng thái
 * @param {jQuery} $button - Element button
 */
UltimateEditorActionButtons.handleCheckStatusAction = function($button) {
    // Lấy thông tin từ draft và controller
    const controllerId = $('#topic-controller-select').val();
    const draftId = $('#current-draft-id').val();
    
    if (!controllerId) {
        alert_float('warning', app.lang.select_controller_first || 'Please select a controller first');
        return;
    }
    
    if (!draftId) {
        alert_float('warning', app.lang.save_draft_first || 'Please save your draft first');
        return;
    }
    
    // Thay đổi trạng thái nút
    const originalText = $button.html();
    $button.addClass('disabled').html('<i class="fa fa-spinner fa-spin"></i> ' + (app.lang.checking || 'Checking...'));
    
    // Gọi API để kiểm tra trạng thái
    $.ajax({
        url: admin_url + 'topics/ultimate_editor/check_post_existence',
        type: 'POST',
        data: {
            controller_id: controllerId,
            title: $('#draft-title').val()
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                if (response.exists) {
                    // Hiển thị thông báo đã tồn tại
                    let message = app.lang.post_exists || 'A post with similar title already exists';
                    
                    if (response.similarity > 0) {
                        message += ` (${Math.round(response.similarity * 100)}% similar)`;
                    }
                    
                    if (response.permalink) {
                        $('#publish-status-message').html(`
                            <div class="alert alert-warning">
                                <i class="fa fa-exclamation-triangle"></i> ${message}
                            </div>
                            <div class="mtop10">
                                <a href="${response.permalink}" target="_blank" class="btn btn-info">
                                    <i class="fa fa-external-link"></i> ${app.lang.view_post || 'View Post'}
                                </a>
                            </div>
                        `);
                    } else {
                        $('#publish-status-message').html(`
                            <div class="alert alert-warning">
                                <i class="fa fa-exclamation-triangle"></i> ${message}
                            </div>
                        `);
                    }
                } else {
                    // Hiển thị thông báo chưa tồn tại
                    $('#publish-status-message').html(`
                        <div class="alert alert-success">
                            <i class="fa fa-check-circle"></i> ${app.lang.post_title_available || 'Post title is available'}
                        </div>
                    `);
                }
            } else {
                // Hiển thị lỗi
                alert_float('danger', response.message || app.lang.error_checking_post);
                
                $('#publish-status-message').html(`
                    <div class="alert alert-danger">
                        <i class="fa fa-times-circle"></i> ${response.message || app.lang.error_checking_post}
                    </div>
                `);
            }
        },
        error: function(xhr, status, error) {
            // Hiển thị lỗi
            alert_float('danger', app.lang.server_error + ': ' + error);
            
            $('#publish-status-message').html(`
                <div class="alert alert-danger">
                    <i class="fa fa-times-circle"></i> ${app.lang.server_error || 'Server Error'}: ${error}
                </div>
            `);
        },
        complete: function() {
            // Khôi phục trạng thái nút
            $button.removeClass('disabled').html(originalText);
        }
    });
};

/**
 * Xử lý generic action button
 * @param {jQuery} $button - Element button
 */
UltimateEditorActionButtons.handleGenericAction = function($button) {
    // Lấy dữ liệu từ button
    const workflowId = $button.data('workflow-id');
    const targetType = $button.data('target-type');
    const targetState = $button.data('target-state');
    const buttonId = $button.data('button-id');
    
    // Lấy dữ liệu từ editor
    const topicId = $('#topic_id').val();
    const draftId = $('#current-draft-id').val();
    
    if (!topicId) {
        alert_float('warning', app.lang.missing_topic_id || 'Missing topic ID');
        return;
    }
    
    // Xác nhận trước khi thực thi
    Swal.fire({
        title: app.lang.confirm_action || 'Confirm Action',
        text: app.lang.confirm_execute_workflow || 'Are you sure you want to execute this workflow?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: app.lang.yes_execute || 'Yes, execute it!',
        cancelButtonText: app.lang.cancel || 'No, cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            // Thay đổi trạng thái nút
            const originalText = $button.html();
            $button.addClass('disabled').html('<i class="fa fa-spinner fa-spin"></i> ' + (app.lang.executing || 'Executing...'));
            
            // Trước khi thực thi, lưu draft hiện tại
            if (typeof UltimateEditor !== 'undefined' && typeof UltimateEditor.saveDraft === 'function') {
                UltimateEditor.saveDraft(false, function() {
                    // Sau khi lưu draft, tiếp tục thực thi
                    executeWorkflow();
                });
            } else {
                // Nếu không có UltimateEditor, thực thi luôn
                executeWorkflow();
            }
            
            function executeWorkflow() {
                // Gọi API để thực thi workflow
                $.ajax({
                    url: admin_url + 'topics/process_topic_action',
                    type: 'POST',
                    data: {
                        topic_id: topicId,
                        workflow_id: workflowId,
                        target_type: targetType,
                        target_state: targetState,
                        button_id: buttonId,
                        draft_id: draftId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Hiển thị thông báo thành công
                            alert_float('success', response.message || app.lang.workflow_executed_successfully);
                            
                            // Kiểm tra xem có cần reload không
                            if (response.reload) {
                                setTimeout(function() {
                                    location.reload();
                                }, 2000);
                            }
                        } else {
                            // Hiển thị lỗi
                            alert_float('danger', response.message || app.lang.failed_to_execute_workflow);
                        }
                    },
                    error: function(xhr, status, error) {
                        // Hiển thị lỗi
                        alert_float('danger', app.lang.server_error + ': ' + error);
                    },
                    complete: function() {
                        // Khôi phục trạng thái nút
                        $button.removeClass('disabled').html(originalText);
                    }
                });
            }
        }
    });
};

// Khởi tạo khi document ready
$(document).ready(function() {
    // Kiểm tra xem có tab publish không (chỉ khởi tạo nếu đang ở trong UltimateEditor)
    if ($('#tab_publish').length && typeof UltimateEditorPublish !== 'undefined') {
        UltimateEditorActionButtons.init();
    }
});
```

```html
<!-- Thêm vào views/ultimate_editor/index.php hoặc file tương ứng -->
<!-- Thêm vào phần scripts -->
<script src="<?php echo base_url('modules/topics/assets/js/ultimate_editor_integration.js'); ?>"></script>
```

### Bước 4: Kết Nối Action Buttons với Cơ Chế Xuất Bản

```php
// Thêm vào Ultimate_editor.php hoặc cập nhật phương thức hiện có
/**
 * Execute Publishing Action từ Action Button
 * 
 * @return json
 */
public function execute_publish_action()
{
    // Kiểm tra quyền truy cập
    if (!has_permission('topics', '', 'edit')) {
        echo json_encode(['success' => false, 'message' => _l('access_denied')]);
        return;
    }
    
    // Nhận thông tin từ request
    $workflow_id = $this->input->post('workflow_id');
    $topic_id = $this->input->post('topic_id');
    $controller_id = $this->input->post('controller_id');
    $draft_id = $this->input->post('draft_id');
    
    if (!$topic_id || !$controller_id) {
        echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
        return;
    }
    
    // Lấy thông tin topic
    $this->load->model('Topics_model');
    $topic = $this->Topics_model->get($topic_id);
    
    if (!$topic) {
        echo json_encode(['success' => false, 'message' => 'Topic not found']);
        return;
    }
    
    // Lấy nội dung draft (ưu tiên draft_id, nếu không lấy draft mới nhất)
    $this->load->model('Topic_draft_model');
    $draft = null;
    
    if ($draft_id) {
        $draft = $this->Topic_draft_model->get($draft_id);
    } else {
        $draft = $this->Topic_draft_model->get_latest_draft($topic_id);
    }
    
    if (!$draft) {
        echo json_encode(['success' => false, 'message' => 'No drafts found for this topic']);
        return;
    }
    
    // Lấy thông tin controller
    $this->load->model('Topic_controller_model');
    $controller = $this->Topic_controller_model->get($controller_id);
    
    if (!$controller) {
        echo json_encode(['success' => false, 'message' => 'Controller not found']);
        return;
    }
    
    // Lấy danh mục và tags từ draft
    $categories = [];
    $tags = [];
    
    if (!empty($draft->categories)) {
        $categories = is_array($draft->categories) ? $draft->categories : json_decode($draft->categories, true);
    }
    
    if (!empty($draft->tags)) {
        $tags = is_array($draft->tags) ? $draft->tags : json_decode($draft->tags, true);
    }
    
    // Chuẩn bị dữ liệu xuất bản
    $post_data = [
        'title' => $draft->title ?? $topic->title,
        'content' => $draft->content,
        'excerpt' => $draft->excerpt ?? '',
        'categories' => $categories,
        'tags' => $tags,
        'status' => 'publish', // Có thể cấu hình trong action button
        'schedule_time' => null
    ];
    
    // Load helper để gọi hàm publish_platform_post
    $this->load->helper('topic_platform_helper');
    
    // Xuất bản lên nền tảng
    $result = publish_platform_post($controller_id, $post_data);
    
    if ($result['success']) {
        // Cập nhật thông tin topic
        $this->Topics_model->update($topic_id, [
            'last_published' => date('Y-m-d H:i:s'),
            'published_url' => isset($result['permalink']) ? $result['permalink'] : '',
            'published_status' => 1
        ]);
        
        // Cập nhật trạng thái draft
        if ($draft) {
            $this->Topic_draft_model->update($draft->id, [
                'publish_status' => 1,
                'publish_date' => date('Y-m-d H:i:s'),
                'published_url' => isset($result['permalink']) ? $result['permalink'] : ''
            ]);
        }
        
        // Ghi log thành công
        $this->log_activity($topic_id, 'topic_published', json_encode([
            'draft_id' => $draft->id,
            'controller_id' => $controller_id,
            'platform' => $controller->platform,
            'result' => $result
        ]));
        
        echo json_encode([
            'success' => true, 
            'message' => _l('content_published_successfully'),
            'data' => [
                'permalink' => isset($result['permalink']) ? $result['permalink'] : '',
                'post_id' => isset($result['post_id']) ? $result['post_id'] : '',
                'platform' => $controller->platform
            ]
        ]);
    } else {
        // Ghi log thất bại
        $this->log_activity($topic_id, 'topic_publish_failed', json_encode([
            'draft_id' => $draft->id,
            'controller_id' => $controller_id,
            'platform' => $controller->platform,
            'error' => $result['message'] ?? 'Unknown error'
        ]));
        
        echo json_encode(['success' => false, 'message' => $result['message'] ?? _l('failed_to_publish_content')]);
    }
}

/**
 * Log hoạt động xuất bản
 * 
 * @param int $topic_id
 * @param string $action
 * @param string $description
 */
private function log_activity($topic_id, $action, $description = '')
{
    $this->db->insert(db_prefix() . 'topic_activity_log', [
        'topic_id' => $topic_id,
        'action' => $action,
        'description' => $description,
        'user_id' => get_staff_user_id(),
        'date' => date('Y-m-d H:i:s')
    ]);
}
```

### Bước 5: Bổ Sung Tính Năng Nâng Cao (Tùy Chọn)

1. **Thêm Helpers cho Action Buttons** (Nếu cần)

```php
// helpers/topic_action_button_helper.php
function render_action_buttons($buttons, $topic_id) {
    $CI = &get_instance();
    
    $html = '<div class="action-buttons-container">';
    foreach ($buttons as $button) {
        $html .= '<button type="button" 
            class="btn btn-' . $button['button_type'] . ' topic-action-button"
            data-workflow-id="' . $button['workflow_id'] . '"
            data-target-type="' . $button['target_action_type'] . '"
            data-target-state="' . $button['target_action_state'] . '"
            data-topic-id="' . $topic_id . '"
            data-button-id="' . $button['id'] . '"
            data-action-command="' . ($button['action_command'] ?? '') . '">
            <i class="fa fa-${UltimateEditorActionButtons.getIconForCommand(button.action_command)}"></i> ${button.name}
        </button>';
    }
    $html .= '</div>';
    
    return $html;
}
```

2. **Tối ưu UX cho Action Buttons Management**

```javascript
// Thêm vào assets/js/controllers.js
function deleteActionButton(id) {
    Swal.fire({
        title: 'Bạn có chắc chắn?',
        text: "Hành động này không thể hoàn tác!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Xóa',
        cancelButtonText: 'Hủy'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: admin_url + 'topics/controllers/delete_action_button/' + id,
                type: 'POST',
                success: function(response) {
                    if (response.success) {
                        Swal.fire(
                            'Đã xóa!',
                            'Nút hành động đã được xóa.',
                            'success'
                        );
                        $('.table-action-buttons').DataTable().ajax.reload();
                    } else {
                        Swal.fire(
                            'Lỗi!',
                            response.message,
                            'error'
                        );
                    }
                }
            });
        }
    });
}
```

## Lưu Ý Quan Trọng

1. **Tận Dụng Code Sẵn Có**: Toàn bộ model `Topic_action_button_model` đã được triển khai đầy đủ, không cần code lại từ đầu.

2. **Endpoint Cần Triển Khai**:
   ```
   /topics/controllers/action_buttons                  - Quản lý danh sách nút
   /topics/controllers/action_button/[id]              - Thêm/Sửa nút
   /topics/controllers/change_button_status            - Đổi trạng thái nút
   /topics/controllers/delete_action_button            - Xóa nút
   /topics/controllers/get_action_states               - Lấy states theo type
   /ultimate_editor/execute_publish_action             - Xử lý action xuất bản
   /ultimate_editor/get_controller_action_buttons      - Lấy action buttons cho controller
   ```

3. **Topic Status vs Action Buttons**: Action Buttons chỉ hiển thị khi `target_action_type` và `target_action_state` phù hợp với `action_type_code` và `action_state_code` của topic.

4. **Tính Năng Đặc Biệt**:
   - `ignore_types` và `ignore_states` là các mảng JSON để loại trừ hiển thị nút trong một số trạng thái nhất định.
   - `action_command` cho phép định nghĩa hành động cụ thể, ví dụ:
     - `PUBLISH_POST`: Xuất bản nội dung lên nền tảng
     - `PREVIEW_POST`: Xem trước nội dung
     - `CHECK_STATUS`: Kiểm tra trạng thái bài viết trên nền tảng

5. **Liên Kết với Cơ Chế Xuất Bản**:
   - Namespace `UltimateEditorPublish` đã được triển khai sẵn trong `ultimate_editor_publish.js` để xử lý việc xuất bản.
   - Cần sử dụng phương thức `UltimateEditorPublish.getPublishData()` để lấy dữ liệu xuất bản hiện tại.
   - Phương thức `UltimateEditor.saveDraft()` nên được gọi trước khi xuất bản để đảm bảo dữ liệu được lưu.
   - Namespace `UltimateEditorActionButtons` được tạo mới để tích hợp giữa action buttons và Ultimate Editor.

6. **Cấu Trúc JavaScript**:
   - Các file JavaScript chính của Ultimate Editor:
     - `ultimate_editor.js`: File chính khởi tạo và điều phối các chức năng
     - `ultimate_editor_exec.js`: Xử lý các thao tác thực thi như lưu, tạo draft
     - `ultimate_editor_fn.js`: Các utility functions
     - `ultimate_editor_presents.js`: Các hàm xử lý giao diện
     - `ultimate_editor_publish.js`: Xử lý xuất bản (quan trọng nhất cho integration)
     - `ultimate_editor_lang.js`: Chuỗi ngôn ngữ
   - File mới cần tạo: `ultimate_editor_integration.js` - Xử lý tích hợp Action Buttons với Ultimate Editor

7. **Các Tables Liên Quan**:
   ```
   tbltopic_action_buttons  - Cấu hình nút hành động
   tbltopic_action_types    - Loại hành động
   tbltopic_action_states   - Trạng thái hành động
   tbltopic_automation_logs - Log quá trình thực thi
   tbltopic_activity_log    - Log hoạt động xuất bản
   tbltopic_drafts          - Lưu trữ các bản nháp
   ```

## Kiểm Thử Nhanh

1. **Tạo Action Button**:
   - Vào **Topics > Controllers > Action Buttons** để tạo mới một nút.
   - Đặt tên là "Publish to WordPress" với button_type="primary".
   - Đặt action_command="PUBLISH_POST" và target_action_type, target_action_state phù hợp.

2. **Kiểm Tra Integration với Ultimate Editor**:
   - Mở Ultimate Editor và chọn một controller trong tab Publish.
   - Kiểm tra xem action buttons có hiển thị trong panel "Action Buttons" không.
   - Nhấn vào nút "Publish to WordPress" và kiểm tra xem quy trình xuất bản có hoạt động không.

3. **Kiểm Tra trong Trang Controller Detail**:
   - Mở trang chi tiết của một controller (Topics > Controllers > [tên controller]).
   - Kiểm tra xem action buttons có hiển thị không.
   - Nhấn vào nút để kiểm tra kết quả trong phần "Execution Results".

⚡ **Pro Tip**: Tạo một workflow đầy đủ bằng cách kết hợp nhiều nút hành động:
1. Nút "Write Draft" (action_command="WRITE_DRAFT") kết nối với Draft Writer
2. Nút "Preview Draft" (action_command="PREVIEW_POST") để xem trước nội dung 
3. Nút "Check Existence" (action_command="CHECK_STATUS") để kiểm tra xem bài đã tồn tại chưa
4. Nút "Publish to Platform" (action_command="PUBLISH_POST") để xuất bản
5. Nút "Share on Social" (action_command="SHARE_SOCIAL") để chia sẻ sau khi xuất bản