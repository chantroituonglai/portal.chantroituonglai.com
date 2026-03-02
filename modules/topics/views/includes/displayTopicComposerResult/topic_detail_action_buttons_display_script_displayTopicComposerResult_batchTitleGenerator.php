<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<script>
/**
 * Helper function to convert language code to language name
 */
function getLanguageName(langCode) {
    const languages = {
        'en': 'English',
        'vi': 'Vietnamese',
        'zh': 'Chinese',
        'th': 'Thai',
        'ja': 'Japanese',
        'ko': 'Korean',
        'fr': 'French',
        'de': 'German',
        'es': 'Spanish',
        'ru': 'Russian'
    };
    
    return languages[langCode] || 'English';
}

// Batch Title Generator
 // Khởi tạo biến toàn cục để theo dõi quá trình
window.batchTitleGenerator = {
    items: [],
    currentIndex: 0,
    wordLimit: 12,
    returnHtml: false,
    results: [],
    isProcessing: false,
    selectedItems: [],
    language: null
};

// Hàm hiển thị progress bar
function showBatchProgressBar(totalItems) {
    // Xóa progress bar cũ nếu có
    $('#batch-progress-container').remove();
    
    // Tạo progress bar mới
    const progressHtml = `
        <div id="batch-progress-container" class="alert alert-info">
            <h4><i class="fa fa-spinner fa-spin"></i> <?php echo _l('generating_titles'); ?></h4>
            <div class="progress">
                <div class="progress-bar progress-bar-striped active" role="progressbar" style="width: 0%">
                    <span class="progress-text">0/${totalItems}</span>
                </div>
            </div>
            <div class="mt-2">
                <button type="button" class="btn btn-danger btn-sm cancel-batch-generation">
                    <?php echo _l('cancel'); ?>
                </button>
            </div>
        </div>
    `;
    
    // Thêm vào DOM
    $('.batch-actions-status').after(progressHtml);
    
    // Xử lý sự kiện cancel
    $('.cancel-batch-generation').on('click', function() {
        if (confirm('<?php echo _l("confirm_cancel_batch_generation"); ?>')) {
            window.batchTitleGenerator.isProcessing = false;
            $('#batch-progress-container').remove();
            alert_float('info', '<?php echo _l("batch_generation_cancelled"); ?>');
        }
    });
}

// Hàm cập nhật progress bar
function updateProgressBar(current, total) {
    
    const percent = Math.round((current / total) * 100);
    $('#batch-progress-container .progress-bar').css('width', percent + '%');
    $('#batch-progress-container .progress-text').text(`${current}/${total}`);
}

// Hàm xử lý item tiếp theo
function processNextItem() {
    // Kiểm tra nếu đã hủy quá trình
    if (!window.batchTitleGenerator.isProcessing) {
        return;
    }
    
    // Lấy thông tin item hiện tại
    const currentIndex = window.batchTitleGenerator.currentIndex;
    const items = window.batchTitleGenerator.items;
    
    // Kiểm tra nếu đã xử lý hết
    if (currentIndex >= items.length) {
        finishBatchGeneration();
        return;
    }
    
    // Cập nhật progress bar
    updateProgressBar(currentIndex + 1, items.length);
    
    // Lấy item cần xử lý
    const itemData = items[currentIndex];
    const item = itemData.item;
    const index = itemData.index;
    
    // Lấy nội dung trực tiếp từ item data
    const content = item.Item_Content || '';
    
    // Get the language
    const language = window.batchTitleGenerator.language || 'en';
    
    // Tạo prompt cho AI
    const prompt = `Generate a concise title in ${getLanguageName(language)} based on the following content. The title should be around ${window.batchTitleGenerator.wordLimit} words, be engaging and accurately reflect the main topic. Content: ${content}`;
    
    // Thêm các tùy chọn
    const defaultCommands = [];
    if (!window.batchTitleGenerator.returnHtml) {
        defaultCommands.push('Trả về nội dung dạng text thuần không có bất kỳ thẻ HTML, markdown hay định dạng nào khác');
    } else {
        defaultCommands.push('Luôn luôn trả về kết quả là HTML');
    }
    defaultCommands.push('không trả về markdown');
    defaultCommands.push('không trả về json, chỉ trả về đúng kết quả');
    
    const fullPrompt = prompt + '. ' + defaultCommands.join(', ');
    
    // Cấu hình cho callAIEditAPI
    const configData = {
        returnHtml: window.batchTitleGenerator.returnHtml,
        noMarkdown: true,
        noJson: true
    };
    
    // Gọi API để tạo tiêu đề sử dụng hàm callAIEditAPI có sẵn
    callAIEditAPI(content, 'title', fullPrompt, function(output) {
        // Lưu kết quả
        window.batchTitleGenerator.results.push({
            index: index,
            originalTitle: item.Topic || '',
            newTitle: output
        });
        
        // Cập nhật tiêu đề trong dữ liệu
        window.TopicComposer.items[index].Topic = output;
        window.TopicComposer.items[index].Item_Title = output;
        
        // Đánh dấu là đã thay đổi
        window.TopicComposer.hasChanges = true;
        
        // Tăng index và xử lý item tiếp theo
        window.batchTitleGenerator.currentIndex++;
        
        // Đợi một chút trước khi xử lý item tiếp theo để tránh quá tải
        setTimeout(processNextItem, 1000);
    }, configData);
}

// Hàm kết thúc quá trình tạo tiêu đề hàng loạt
function finishBatchGeneration() {
    // Cập nhật trạng thái
    window.batchTitleGenerator.isProcessing = false;
    
    // Thay đổi progress bar thành thông báo hoàn thành
    $('#batch-progress-container')
        .removeClass('alert-info')
        .addClass('alert-success')
        .html(`
            <h4><i class="fa fa-check-circle"></i> <?php echo _l('batch_generation_completed'); ?></h4>
            <p><?php echo _l('processed_items'); ?>: ${window.batchTitleGenerator.results.length}</p>
            <button type="button" class="btn btn-default btn-sm" onclick="$('#batch-progress-container').remove()">
                <?php echo _l('dismiss'); ?>
            </button>
            <button type="button" class="hidden btn btn-primary btn-sm save-all-changes-btn">
                <i class="fa fa-save"></i> <?php echo _l('save_all_changes'); ?>
            </button>
        `);
    
    // Xử lý sự kiện lưu tất cả thay đổi
    $('.save-all-changes-btn').on('click', function() {
        saveAllChanges();
    });
    
    // Hiển thị thông báo
    alert_float('success', '<?php echo _l("batch_title_generation_completed"); ?>');
    
    // Làm mới danh sách items bằng cách render lại
    window.TopicComposer.handlers.refreshItemsList();
}

// Hàm lưu tất cả thay đổi
function saveAllChanges() {
    return;
    // Hiển thị thông báo đang lưu
    alert_float('info', '<?php echo _l("saving_changes"); ?>');
    
    // Chuẩn bị dữ liệu để lưu
    const submissionData = {
        all_items: window.TopicComposer.items,
        updated_items: window.TopicComposer.items,
        added_items: [],
        deleted_items: []
    };
    
    // Chuẩn bị dữ liệu workflow
    const workflowData = {
        ...window.currentWorkflowData,
        audit_step: 2,
        changes_data: submissionData
    };
    
    // Thực hiện lưu
    executeWorkflow(workflowData).then(function(response) {
        if (response.success) {
            // Cập nhật dữ liệu gốc
            window.TopicComposer.originalItems = JSON.parse(JSON.stringify(window.TopicComposer.items));
            window.TopicComposer.hasChanges = false;
            
            // Hiển thị thông báo thành công
            alert_float('success', '<?php echo _l("changes_saved_successfully"); ?>');
            
            // Làm mới danh sách
            window.TopicComposer.handlers.refreshItemsList();
        } else {
            // Hiển thị thông báo lỗi
            alert_float('danger', response.message || '<?php echo _l("error_saving_changes"); ?>');
        }
    }).catch(function(error) {
        console.error('Error saving changes:', error);
        alert_float('danger', '<?php echo _l("error_saving_changes"); ?>');
    });
}


// Hàm tự động đánh lại số thứ tự
function autoReposition() {
    // Create and show modal for prefix selection
    showPositionPrefixModal();
}

// Hàm hiển thị modal để chọn prefix cho Position
function showPositionPrefixModal() {
    // Remove existing modal if any
    $('#position-prefix-modal').remove();
    $('#position-prefix-styles').remove();
    
    // Detect current prefix from items
    let currentPrefix = 'Top';
    let currentAddSpace = true;
    
    // Try to detect the current prefix pattern from the first few items
    if (window.TopicComposer && window.TopicComposer.items && window.TopicComposer.items.length > 0) {
        const sampleItems = window.TopicComposer.items.slice(0, 3);
        
        // Extract pattern from position values
        const positionValues = sampleItems.map(item => item.Item_Position || '');
        
        if (positionValues.length > 0) {
            // Extract prefix pattern from the first position value
            const firstPosition = positionValues[0];
            const match = firstPosition.match(/^(.*?)(\s*)(\d+)$/);
            
            if (match) {
                currentPrefix = match[1] || '';
                currentAddSpace = !!match[2];
            }
        }
    }
    
    // Common prefixes for user to choose from
    const commonPrefixes = [
        { value: 'Top', label: 'Top 1, Top 2, Top 3...' },
        { value: 'Chủ đề', label: 'Chủ đề 1, Chủ đề 2, Chủ đề 3...' },
        { value: '#', label: '#1, #2, #3...' },
        { value: 'Mục', label: 'Mục 1, Mục 2, Mục 3...' },
        { value: 'Phần', label: 'Phần 1, Phần 2, Phần 3...' },
        { value: 'Bước', label: 'Bước 1, Bước 2, Bước 3...' },
        { value: '', label: '1, 2, 3... (no prefix)' }
    ];
    
    // Create HTML for the modal
    const modalHtml = `
        <div class="modal fade" id="position-prefix-modal" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h4 class="modal-title"><?php echo _l('select_position_prefix'); ?></h4>
                    </div>
                    <div class="modal-body">
                        <form id="position-prefix-form">
                            <div class="form-group">
                                <label><?php echo _l('common_prefixes'); ?></label>
                                <div class="row">
                                    ${commonPrefixes.map(prefix => `
                                        <div class="col-md-6">
                                            <div class="radio">
                                                <input type="radio" 
                                                       id="prefix-${prefix.value}" 
                                                       name="position-prefix" 
                                                       value="${prefix.value}"
                                                       ${prefix.value === currentPrefix ? 'checked' : ''}>
                                                <label for="prefix-${prefix.value}">${prefix.label}</label>
                                            </div>
                                        </div>
                                    `).join('')}
                                </div>
                            </div>
                            <div class="form-group">
                                <label><?php echo _l('custom_prefix'); ?></label>
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <input type="radio" 
                                               id="prefix-custom" 
                                               name="position-prefix" 
                                               value="custom"
                                               ${!commonPrefixes.some(p => p.value === currentPrefix) ? 'checked' : ''}>
                                    </span>
                                    <input type="text" 
                                           class="form-control" 
                                           id="custom-prefix-input" 
                                           placeholder="<?php echo _l('enter_custom_prefix'); ?>"
                                           value="${!commonPrefixes.some(p => p.value === currentPrefix) ? currentPrefix : ''}"
                                           onclick="$('#prefix-custom').prop('checked', true)">
                                </div>
                            </div>
                            <div class="form-group">
                                <label><?php echo _l('preview'); ?></label>
                                <div class="well position-preview">
                                    <!-- Preview content will be dynamically updated -->
                                </div>
                            </div>
                            <div class="form-group">
                                <label><?php echo _l('advanced_options'); ?></label>
                                <div class="checkbox">
                                    <input type="checkbox" id="add-separator" ${currentAddSpace ? 'checked' : ''}>
                                    <label for="add-separator"><?php echo _l('add_space_after_prefix'); ?></label>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
                        <button type="button" class="btn btn-primary" id="apply-prefix-btn"><?php echo _l('apply'); ?></button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Append modal to body
    $('body').append(modalHtml);
    
    // Add CSS styles for the preview
    const previewStyles = `
        <style id="position-prefix-styles">
            .position-preview {
                font-size: 16px;
                line-height: 1.5;
                padding: 10px;
                background-color: #f9f9f9;
                border-radius: 4px;
                margin-bottom: 15px;
            }
            .prefix-part {
                color: #007bff;
                font-weight: bold;
            }
            .number-part {
                color: #28a745;
                font-weight: bold;
            }
            .custom-prefix-input {
                margin-top: 5px;
            }
            #position-prefix-modal .radio {
                margin-bottom: 10px;
            }
            #position-prefix-modal .form-group {
                margin-bottom: 20px;
            }
        </style>
    `;
    $('head').append(previewStyles);
    
    // Show modal
    $('#position-prefix-modal').modal('show');
    
    // Clean up on modal close
    $('#position-prefix-modal').on('hidden.bs.modal', function() {
        $('#position-prefix-styles').remove();
        $(this).remove();
    });
    
    // Add preview update functionality
    $('input[name="position-prefix"], #custom-prefix-input, #add-separator').on('change input', updatePrefixPreview);
    
    // Apply prefix when button is clicked
    $('#apply-prefix-btn').on('click', function() {
        applyAutoReposition();
        $('#position-prefix-modal').modal('hide');
    });
    
    // Initialize preview
    updatePrefixPreview();
    
    // Function to update preview based on selected options
    function updatePrefixPreview() {
        let prefix = $('input[name="position-prefix"]:checked').val();
        const addSpace = $('#add-separator').is(':checked');
        
        // If custom prefix is selected, get the value from input
        if (prefix === 'custom') {
            prefix = $('#custom-prefix-input').val();
        }
        
        // Create preview text
        let previewHtml = '';
        for (let i = 1; i <= 3; i++) {
            if (i > 1) previewHtml += ', ';
            
            previewHtml += `<span class="prefix-part">${prefix}</span>`;
            
            // Add space if checkbox is checked and prefix isn't empty
            if (addSpace && prefix !== '') {
                previewHtml += ' ';
            }
            
            previewHtml += `<span class="number-part">${i}</span>`;
        }
        
        previewHtml += '...';
        
        // Update preview element
        $('.position-preview').html(previewHtml);
    }
    
    // Function to apply the auto repositioning with selected prefix
    function applyAutoReposition() {
        // Get selected prefix
        let prefix = $('input[name="position-prefix"]:checked').val();
        if (prefix === 'custom') {
            prefix = $('#custom-prefix-input').val();
        }
        
        // Get space option
        const addSpace = $('#add-separator').is(':checked');
        
        // Display processing notification
    alert_float('info', '<?php echo _l("repositioning_items"); ?>');
    
        // Sort items based on current order in DOM
    $('.sortable-items .list-group-item').each(function(index) {
        const itemIndex = $(this).data('index');
        
            // Create new position string based on selected options
            let newPosition = '';
            
            if (prefix !== '') {
                newPosition += prefix;
                
                // Add space if checkbox is checked
                if (addSpace) {
                    newPosition += ' ';
                }
            }
            
            // Add the position number
            newPosition += (index + 1);
            
            // Update position in data
        window.TopicComposer.items[itemIndex].Item_Position = newPosition;
        
            // Update display
        $(this).find('.item-position').text(newPosition);
    });
    
        // Mark as changed
    window.TopicComposer.hasChanges = true;
    $('.form-actions').addClass('has-changes');
    
        // Show completion notification
    alert_float('success', '<?php echo _l("repositioning_completed"); ?>');
    }
}

/**
 * Hiển thị progress bar cho Bulk Content Edit
 */
function showBulkContentProgressBar(totalItems) {
    // Xóa progress bar cũ nếu có
    $('#bulk-content-progress-container').remove();
    
    // Tạo progress bar mới
    const progressHtml = `
        <div id="bulk-content-progress-container" class="alert alert-info">
            <h4><i class="fa fa-spinner fa-spin"></i> <?php echo _l('generating_content'); ?></h4>
            <div class="progress">
                <div class="progress-bar progress-bar-striped active" role="progressbar" style="width: 0%">
                    <span class="progress-text">0/${totalItems}</span>
                </div>
            </div>
            <div class="mt-2">
                <button type="button" class="btn btn-danger btn-sm cancel-bulk-content-generation">
                    <?php echo _l('cancel'); ?>
                </button>
            </div>
        </div>
    `;
    
    // Thêm vào DOM
    $('.batch-actions-status').after(progressHtml);
    
    // Xử lý sự kiện cancel
    $('.cancel-bulk-content-generation').on('click', function() {
        if (confirm('<?php echo _l("confirm_cancel_batch_generation"); ?>')) {
            window.bulkContentEditor.isProcessing = false;
            $('#bulk-content-progress-container').remove();
            alert_float('info', '<?php echo _l("batch_generation_cancelled"); ?>');
        }
    });
}

/**
 * Cập nhật progress bar cho Bulk Content Edit
 */
function updateBulkContentProgressBar(current, total) {
    const percent = Math.round((current / total) * 100);
    $('#bulk-content-progress-container .progress-bar').css('width', percent + '%');
    $('#bulk-content-progress-container .progress-text').text(`${current}/${total}`);
}

/**
 * Xử lý item tiếp theo cho Bulk Content Edit
 */
async function processBulkContentEditNextItem() {
    
    // Kiểm tra nếu đã hủy quá trình
    if (!window.bulkContentEditor.isProcessing) {
        return;
    }
    
    // Lấy thông tin item hiện tại
    const currentIndex = window.bulkContentEditor.currentIndex;
    const items = window.bulkContentEditor.items;
    console.log('processBulkContentEditNextItem', currentIndex, items.length);
    // Kiểm tra nếu đã xử lý hết
    if (currentIndex >= items.length) {
        finishBulkContentEdit();
        return;
    }
    
    // Cập nhật progress bar
    updateBulkContentProgressBar(currentIndex + 1, items.length);
    
    // Lấy item cần xử lý
    const itemData = items[currentIndex];
    const item = itemData.item;
    const index = itemData.index;
    
    // Lấy nội dung trực tiếp từ item data
    const content = item.Item_Content || '';
    const title = item.Topic || '';
    
    // Get the language
    const language = window.bulkContentEditor.language || 'en';
    
    // Nếu nội dung trống, bỏ qua và chuyển sang item tiếp theo
    if (!content.trim()) {
        window.bulkContentEditor.currentIndex++;
        setTimeout(processBulkContentEditNextItem, 100);
        return;
    }
    
    // Tạo prompt cho AI dựa trên style đã chọn
    let promptText = '';
    const contentStyle = window.bulkContentEditor.contentStyle;
    
    switch(contentStyle) {
        case 'detailed':
            promptText = `Expand and improve the following content with more details, examples, and explanations. Make it comprehensive and educational. Write in ${getLanguageName(language)}`;
            break;
        case 'concise':
            promptText = `Make the following content more concise and clear while preserving key information and meaning. Remove unnecessary details and repetition. Write in ${getLanguageName(language)}`;
            break;
        case 'creative':
            promptText = `Rewrite the following content in a more creative, engaging, and unique style. Add interesting perspectives and innovative expressions. Write in ${getLanguageName(language)}`;
            break;
        case 'professional':
            promptText = `Rewrite the following content in a professional, authoritative style suitable for a business context. Use formal language and structure. Write in ${getLanguageName(language)}`;
            break;
        case 'conversational':
            promptText = `Rewrite the following content in a conversational, friendly, and informal tone. Make it feel personal and relatable, as if speaking directly to the reader. Write in ${getLanguageName(language)}.`;
            break;
        case 'storytelling':
            promptText = `Transform the following content into a narrative story format with a beginning, middle, and end. Use storytelling techniques like character development, plot, and descriptive language. Write in ${getLanguageName(language)}`;
            break;
        case 'technical':
            promptText = `Rewrite the following content in a technical style with precise terminology, detailed explanations, and data-driven approach. Include industry-specific terminology where appropriate. Write in ${getLanguageName(language)}`;
            break;
        case 'academic':
            promptText = `Rewrite the following content in an academic style with formal language, structured arguments, and objective analysis. Use scholarly tone and include references where appropriate. Write in ${getLanguageName(language)}`;
            break;
        case 'persuasive':
            promptText = `Rewrite the following content in a persuasive style designed to convince the reader. Use compelling arguments, emotional appeals, and strong calls to action. Write in ${getLanguageName(language)}`;
            break;
        case 'instructional':
            promptText = `Transform the following content into a clear step-by-step guide or tutorial. Use numbered steps, explanations, and practical examples to help readers achieve a specific outcome. Write in ${getLanguageName(language)}`;
            break;
        default:
            promptText = `Improve the following content by enhancing clarity, flow, and readability. Write in ${getLanguageName(language)}`;
    }
    
    // Thêm tùy chọn tối ưu hóa nội dung
    const optimizationInstructions = [];
    const optimizationOptions = window.bulkContentEditor.optimizationOptions || {};
    
    if (optimizationOptions.seo) {
        optimizationInstructions.push('Tối ưu hóa nội dung cho SEO, đảm bảo mật độ từ khóa phù hợp, thêm các từ khóa liên quan, và cấu trúc nội dung theo tiêu chuẩn SEO.');
    }
    
    if (optimizationOptions.removeExternalLinks) {
        optimizationInstructions.push('Xóa tất cả các liên kết và nút điều hướng ra trang web bên ngoài. Thay thế các liên kết bằng cách chỉ giữ lại văn bản mô tả.');
    }
    
    if (optimizationOptions.optimizeParagraphLength) {
        optimizationInstructions.push('Tối ưu độ dài đoạn văn cho người đọc web, mỗi đoạn không quá 3-4 câu. Sử dụng câu ngắn, dễ hiểu và tách các ý thành các đoạn riêng biệt.');
    }
    
    if (optimizationOptions.addSubheadings) {
        optimizationInstructions.push('Thêm các tiêu đề phụ (h2, h3) hợp lý để phân chia nội dung thành các phần logic, giúp người đọc dễ dàng nắm bắt thông tin.');
    }
    
    if (optimizationOptions.addCallToAction) {
        optimizationInstructions.push('Thêm lời kêu gọi hành động (call-to-action) phù hợp ở cuối bài viết để khuyến khích người đọc tương tác.');
    }
    
    // Xử lý tùy chọn chèn hình ảnh
    if (optimizationOptions.insertImages) {
        // Tìm tất cả ảnh có trong item
        const itemImages = extractImagesFromContent(item);
        
        // Lọc ra các ảnh đã được download (sử dụng hàm async)
        const downloadedImages = [];
        for (const imgUrl of itemImages) {
            const result = await isImageDownloaded(imgUrl);
            if (result.exists) {
                // Use the downloaded URL (WordPress URL) instead of the original URL
                downloadedImages.push(result.downloadedUrl);
            }
        }
        
        console.log('processBulkContentEditNextItem > itemImages', itemImages);
        console.log('processBulkContentEditNextItem > downloadedImages (WordPress URLs)', downloadedImages);
        
        // Nếu có ảnh đã download, thêm vào prompt
        if (downloadedImages.length > 0) {
            const imageHTML = downloadedImages.map(url => `<img src="${url}" alt="image" class="img-responsive" />`).join('\n');
            optimizationInstructions.push(`Chèn ${downloadedImages.length} hình ảnh sau vào nội dung một cách hợp lý. Phân bố đều giữa các đoạn văn, đặt mỗi ảnh sau đoạn văn có nội dung liên quan, thêm caption cho mỗi ảnh. Sử dụng đúng HTML được cung cấp: ${imageHTML}`);
        }
    }
    
    // Thêm custom instructions nếu có
    const customInstructions = window.bulkContentEditor.customInstructions || '';
    if (customInstructions) {
        optimizationInstructions.push(customInstructions);
    }
    
    // Thêm thông tin controller nếu có
    const controller = window.bulkContentEditor.controller;
    if (controller) {
        // Thêm thông tin cơ bản về controller
        promptText += `\n\n--- Controller Information ---\nSite: ${controller.site || 'Unknown'}`;
        
        if (controller.platform) {
            promptText += `\nPlatform: ${controller.platform}`;
        }
        
        if (controller.slogan) {
            promptText += `\nSlogan: ${controller.slogan}`;
        }
        
        // Thêm action_1 (Writing Requirements) nếu có
        if (controller.action_1) {
            promptText += '\n\n--- Writing Requirements ---\n' + controller.action_1;
        } else if (controller.writing_style) {
            promptText += '\n\n--- Writing Style ---\n' + controller.writing_style;
        }
        
        // Thêm action_2 (Additional Instructions) nếu có
        if (controller.action_2) {
            promptText += '\n\n--- Additional Instructions ---\n' + controller.action_2;
        }
    }
    
    // Kết hợp với prompt chính
    if (optimizationInstructions.length > 0) {
        promptText += '. Additionally, please: ' + optimizationInstructions.join('. ');
    }
    
    // Thêm các tùy chọn tối ưu hóa vào prompt
    const defaultCommands = [
        'Return content with HTML formatting (paragraphs, lists, etc.)',
        'Do NOT include a complete HTML document structure (no html, head, or body tags)',
        'Do NOT include the title in the content',
        'Do NOT return markdown',
        'Do NOT return JSON, only return the formatted content'
    ];
    
    const fullPrompt = promptText + '. ' + defaultCommands.join(', ');
    
    // Cấu hình cho callAIEditAPI
    const configData = {
        returnHtml: true,
        noMarkdown: true,
        noJson: true
    };
    
    // Gọi API để cải thiện nội dung
    callAIEditAPI(content, 'content', fullPrompt, function(output) {
        console.log('processBulkContentEditNextItem > output', output);
        // Lưu kết quả
        window.bulkContentEditor.results.push({
            index: index,
            originalContent: content,
            newContent: output
        });
        
        // Cập nhật nội dung trong dữ liệu
        window.TopicComposer.items[index].Item_Content = output;
        
        // Đánh dấu là đã thay đổi
        window.TopicComposer.hasChanges = true;
        
        // Tăng index và xử lý item tiếp theo
        window.bulkContentEditor.currentIndex++;
        
        // Đợi một chút trước khi xử lý item tiếp theo để tránh quá tải
        setTimeout(processBulkContentEditNextItem, 1000);
    }, configData);
}

/**
 * Kết thúc quá trình Bulk Content Edit
 */
function finishBulkContentEdit() {
    // Cập nhật trạng thái
    window.bulkContentEditor.isProcessing = false;
    
    // Thay đổi progress bar thành thông báo hoàn thành
    $('#bulk-content-progress-container')
        .removeClass('alert-info')
        .addClass('alert-success')
        .html(`
            <h4><i class="fa fa-check-circle"></i> <?php echo _l('batch_generation_completed'); ?></h4>
            <p><?php echo _l('processed_items'); ?>: ${window.bulkContentEditor.results.length}</p>
            <button type="button" class="btn btn-default btn-sm" onclick="$('#bulk-content-progress-container').remove()">
                <?php echo _l('dismiss'); ?>
            </button>
            <button type="button" class="hidden btn btn-primary btn-sm save-all-changes-btn">
                <i class="fa fa-save"></i> <?php echo _l('save_all_changes'); ?>
            </button>
        `);
    
    // Xử lý sự kiện lưu tất cả thay đổi
    $('.save-all-changes-btn').on('click', function() {
        saveAllChanges();
    });
    
    // Hiển thị thông báo
    alert_float('success', '<?php echo _l("batch_content_generation_completed"); ?>');
    
    // Làm mới danh sách items
    window.TopicComposer.handlers.refreshItemsList();
}

/**
 * Helper function to extract images from content
 * Uses the globally defined extractImagesFromContent if available,
 * or implements it directly
 */
function extractImagesFromContent(itemOrContent) {
    // Check if we can use the global function
    if (typeof window.extractImagesFromContent === 'function') {
        return window.extractImagesFromContent(itemOrContent);
    }
    
    // Otherwise, implement directly
    const imageUrls = [];
    
    // Handle no data case
    if (!itemOrContent) return imageUrls;
    
    try {
        // Handle item object case
        if (typeof itemOrContent === 'object') {
            // Try to use item_Pictures if available
            if (itemOrContent.item_Pictures) {
                try {
                    // Handle item_Pictures as string JSON
                    if (typeof itemOrContent.item_Pictures === 'string') {
                        const parsed = JSON.parse(itemOrContent.item_Pictures);
                        if (Array.isArray(parsed)) {
                            const urls = parsed.map(img => img['item_Pictures-src']).filter(url => url);
                            return urls;
                        }
                    }
                    // Handle item_Pictures as object
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
            
            // If no item_Pictures or parsing failed, try Item_Content
            if (itemOrContent.Item_Content) {
                return extractImagesFromContent(itemOrContent.Item_Content);
            }
            
            return imageUrls;
        }
        
        // Handle string HTML content case
        if (typeof itemOrContent === 'string') {
            const content = itemOrContent;
            
            // Parse HTML content
            const parser = new DOMParser();
            const doc = parser.parseFromString(content, 'text/html');
            
            // Get all img tags
            const imgTags = doc.querySelectorAll('img');
            imgTags.forEach(img => {
                const src = img.getAttribute('src');
                if (src && !imageUrls.includes(src)) {
                    imageUrls.push(src);
                }
            });
            
            // Handle background images in style attributes
            const elementsWithBgImage = doc.querySelectorAll('*[style*="background-image"]');
            elementsWithBgImage.forEach(el => {
                const style = el.getAttribute('style');
                const urlMatch = /url\(['"]?(.*?)['"]?\)/i.exec(style);
                if (urlMatch && urlMatch[1] && !imageUrls.includes(urlMatch[1])) {
                    imageUrls.push(urlMatch[1]);
                }
            });
        }
    } catch (e) {
        console.error('Error extracting images:', e);
    }
    
    return imageUrls;
}

async function isImageDownloaded(imgUrl) {
    // Check cache first
    if (window.downloadedImages && window.downloadedImages.has(imgUrl)) {
        // Get cached downloaded URL if available
        const cachedUrl = window.downloadedImagesMap ? window.downloadedImagesMap.get(imgUrl) : imgUrl;
        return { exists: true, downloadedUrl: cachedUrl || imgUrl };
    }
    
    try {
        // Generate MD5 hash of URL for rel_id
        const rel_id = typeof window.TopicComposer !== 'undefined' && 
                      typeof window.TopicComposer.handlers !== 'undefined' && 
                      typeof window.TopicComposer.handlers.md5 === 'function' ? 
                      window.TopicComposer.handlers.md5(imgUrl) : imgUrl;
        
        // Use the same approach as checkImageExternalData
        const response = await new Promise((resolve, reject) => {
            $.ajax({
                url: admin_url + 'topics/check_image_external_data',
                type: 'POST',
                data: {
                    topic_master_id: typeof topicMasterId !== 'undefined' ? topicMasterId : '',
                    rel_id: rel_id,
                    rel_type: 'image'
                },
                success: function(response) {
                    resolve(response);
                },
                error: function(error) {
                    reject(error);
                }
            });
        });
        
        // Parse response if it's a string
        const data = typeof response === 'string' ? JSON.parse(response) : response;
        
        // Check if image exists in external data
        if (data.exists && data.rel_data) {
            // Get the downloaded URL from rel_data - this is the actual WordPress URL
            const downloadedUrl = data.rel_data;
            
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
    } catch (error) {
        console.error('Error checking if image is downloaded:', error);
        return { exists: false, downloadedUrl: null };
    }
}
</script>
