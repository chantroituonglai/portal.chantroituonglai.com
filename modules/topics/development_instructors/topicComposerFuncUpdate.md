# Phương án bổ sung chức năng tạo tiêu đề hàng loạt cho Topic Composer

## 1. Tổng quan

Chức năng này cho phép người dùng tạo tiêu đề hàng loạt cho nhiều item bằng cách tự động hóa quy trình sử dụng AI. Quy trình này sẽ mô phỏng các thao tác thủ công: chọn item, sử dụng AI Edit cho trường Title, chọn prompt "Generate from Content" với độ dài 12 từ và không trả về HTML.

## 2. Thiết kế giao diện

### 2.1. Thêm nút "Batch Generate Titles" vào giao diện

```php
// Thêm vào file topic_detail_action_buttons_display_script_displayTopicComposerResult_1.php
<div class="batch-actions mt-3 mb-3">
    <button type="button" class="btn btn-info batch-generate-titles-btn">
        <i class="fa fa-magic"></i> <?php echo _l('batch_generate_titles'); ?>
    </button>
</div>
```

### 2.2. Thêm modal xác nhận và cài đặt

```php
// Thêm vào cuối file topic_detail_action_buttons_display_script_displayTopicComposerResult_1.php
<div class="modal fade" id="batch-titles-modal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><?php echo _l('batch_generate_titles'); ?></h4>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label><?php echo _l('word_limit'); ?></label>
                    <input type="number" class="form-control" id="batch-title-word-limit" value="12" min="5" max="20">
                    <small class="text-muted"><?php echo _l('recommended_between_5_20'); ?></small>
                </div>
                <div class="form-group">
                    <div class="checkbox">
                        <input type="checkbox" id="batch-title-return-html">
                        <label for="batch-title-return-html"><?php echo _l('return_html'); ?></label>
                    </div>
                </div>
                <div class="form-group">
                    <label><?php echo _l('items_to_process'); ?></label>
                    <div class="radio">
                        <input type="radio" id="process-all-items" name="items-to-process" value="all" checked>
                        <label for="process-all-items"><?php echo _l('all_items'); ?></label>
                    </div>
                    <div class="radio">
                        <input type="radio" id="process-selected-items" name="items-to-process" value="selected">
                        <label for="process-selected-items"><?php echo _l('selected_items'); ?></label>
                    </div>
                </div>
                <div class="alert alert-info">
                    <i class="fa fa-info-circle"></i> <?php echo _l('batch_titles_info'); ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
                <button type="button" class="btn btn-primary start-batch-generation"><?php echo _l('start_generation'); ?></button>
            </div>
        </div>
    </div>
</div>
```

## 3. Thêm JavaScript Handler

Tạo file mới `topic_detail_action_buttons_display_script_displayTopicComposerResult_batchTitleGenerator.php` với nội dung sau:

```php
<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<script>
// Batch Title Generator
(function() {
    // Khởi tạo biến toàn cục để theo dõi quá trình
    window.batchTitleGenerator = {
        items: [],
        currentIndex: 0,
        wordLimit: 12,
        returnHtml: false,
        results: [],
        isProcessing: false,
        selectedItems: []
    };

    // Xử lý khi click vào nút Batch Generate Titles
    $('.batch-generate-titles-btn').on('click', function() {
        $('#batch-titles-modal').modal('show');
    });

    // Xử lý khi click vào nút Start Generation
    $('.start-batch-generation').on('click', function() {
        // Lấy cài đặt từ modal
        const wordLimit = parseInt($('#batch-title-word-limit').val()) || 12;
        const returnHtml = $('#batch-title-return-html').is(':checked');
        const processType = $('input[name="items-to-process"]:checked').val();
        
        // Khởi tạo danh sách items cần xử lý
        let itemsToProcess = [];
        
        if (processType === 'all') {
            // Lấy tất cả items
            itemsToProcess = window.TopicComposer.items.map((item, index) => ({
                item: item,
                index: index
            }));
        } else {
            // Lấy các items đã chọn
            $('.item-checkbox:checked').each(function() {
                const index = $(this).closest('.list-group-item').data('index');
                itemsToProcess.push({
                    item: window.TopicComposer.items[index],
                    index: index
                });
            });
            
            // Kiểm tra nếu không có item nào được chọn
            if (itemsToProcess.length === 0) {
                alert_float('warning', '<?php echo _l("please_select_at_least_one_item"); ?>');
                return;
            }
        }
        
        // Cập nhật biến toàn cục
        window.batchTitleGenerator.items = itemsToProcess;
        window.batchTitleGenerator.currentIndex = 0;
        window.batchTitleGenerator.wordLimit = wordLimit;
        window.batchTitleGenerator.returnHtml = returnHtml;
        window.batchTitleGenerator.results = [];
        window.batchTitleGenerator.isProcessing = true;
        
        // Đóng modal
        $('#batch-titles-modal').modal('hide');
        
        // Hiển thị progress bar
        showBatchProgressBar(itemsToProcess.length);
        
        // Bắt đầu xử lý item đầu tiên
        processNextItem();
    });
    
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
        $('.batch-actions').after(progressHtml);
        
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
        
        // Mô phỏng việc click vào edit item
        window.TopicComposer.currentEditingIndex = index;
        loadItemEditor(index);
        
        // Đợi để đảm bảo editor đã load xong
        setTimeout(function() {
            // Lấy nội dung từ editor
            const content = item.Content || '';
            
            // Tạo prompt cho AI
            const prompt = `Generate a concise title based on the following content. The title should be around ${window.batchTitleGenerator.wordLimit} words, be engaging and accurately reflect the main topic. Content: ${content}`;
            
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
                
                // Cập nhật tiêu đề trong form
                $('.item-edit-form input[name="title"]').val(output);
                
                // Đánh dấu là đã thay đổi
                window.TopicComposer.handlers.markAsChanged();
                
                // Lưu item
                window.TopicComposer.handlers.saveItemChanges();
                
                // Tăng index và xử lý item tiếp theo
                window.batchTitleGenerator.currentIndex++;
                
                // Đợi một chút trước khi xử lý item tiếp theo để tránh quá tải
                setTimeout(processNextItem, 1000);
            }, configData);
        }, 1500);
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
            `);
        
        // Hiển thị thông báo
        alert_float('success', '<?php echo _l("batch_title_generation_completed"); ?>');
        
        // Làm mới danh sách items bằng cách render lại
        renderItems();
    }
})();
</script>
```

## 4. Thêm checkbox chọn item

Thêm checkbox vào mỗi item trong danh sách để hỗ trợ chức năng chọn item:

```php
// Sửa đổi phần render item trong file JavaScript
function renderItem(item, index) {
    // Code hiện tại...
    
    // Thêm checkbox vào đầu mỗi item
    const checkboxHtml = `
        <div class="item-checkbox-wrapper">
            <input type="checkbox" class="item-checkbox" id="item-checkbox-${index}">
            <label for="item-checkbox-${index}" class="sr-only">Select item</label>
        </div>
    `;
    
    // Thêm checkbox vào HTML của item
    const $item = $(itemHtml);
    $item.find('.item-header').prepend(checkboxHtml);
    
    // Code tiếp theo...
}
```

## 5. Thêm vào file ngôn ngữ

Thêm các chuỗi ngôn ngữ mới vào file `application/language/english/english_lang.php`:

```php
$lang['batch_generate_titles'] = 'Batch Generate Titles';
$lang['word_limit'] = 'Word Limit';
$lang['recommended_between_5_20'] = 'Recommended between 5-20 words';
$lang['return_html'] = 'Return HTML';
$lang['items_to_process'] = 'Items to Process';
$lang['all_items'] = 'All Items';
$lang['selected_items'] = 'Selected Items';
$lang['batch_titles_info'] = 'This will generate titles for multiple items using AI. The process may take some time depending on the number of items.';
$lang['start_generation'] = 'Start Generation';
$lang['generating_titles'] = 'Generating Titles...';
$lang['cancel'] = 'Cancel';
$lang['confirm_cancel_batch_generation'] = 'Are you sure you want to cancel the batch generation?';
$lang['batch_generation_cancelled'] = 'Batch generation cancelled';
$lang['batch_generation_completed'] = 'Batch Generation Completed';
$lang['processed_items'] = 'Processed Items';
$lang['dismiss'] = 'Dismiss';
$lang['batch_title_generation_completed'] = 'Batch title generation completed successfully';
$lang['please_select_at_least_one_item'] = 'Please select at least one item';
```

## 6. Thêm vào file init.php

Thêm file mới vào danh sách các file script được load:

```php
// Thêm vào file init.php hoặc file tương tự
$CI->app_scripts->add('topic-composer-batch-title-generator-js', base_url('views/includes/displayTopicComposerResult/topic_detail_action_buttons_display_script_displayTopicComposerResult_batchTitleGenerator.php'));
```

## 7. Kết luận

Chức năng tạo tiêu đề hàng loạt sẽ giúp người dùng tiết kiệm thời gian khi làm việc với nhiều item. Thay vì phải thực hiện thủ công từng bước cho từng item, người dùng có thể thiết lập một lần và hệ thống sẽ tự động xử lý tất cả các item đã chọn.

Phương án này có những ưu điểm sau:

1. **Sử dụng các hàm hiện có**: Tận dụng hàm `callAIEditAPI` từ file `topic_detail_action_buttons_display_script_displayTopicComposerResult_1.php` mà không cần tạo thêm hàm mới.

2. **Không cần thêm API backend mới**: Không yêu cầu thêm hàm xử lý mới trong backend, chỉ sử dụng các API hiện có.

3. **Tích hợp liền mạch**: Hoạt động liền mạch với cấu trúc hiện có của Topic Composer.

4. **Giao diện thân thiện**: Cung cấp giao diện trực quan với progress bar và thông báo.

5. **Xử lý hàng loạt hiệu quả**: Xử lý tuần tự các item để tránh quá tải hệ thống.

Chức năng này tuân thủ các nguyên tắc thiết kế và cấu trúc hiện có của Topic Composer, đồng thời tận dụng các API và hàm xử lý đã có sẵn để đảm bảo tính nhất quán và dễ bảo trì.
