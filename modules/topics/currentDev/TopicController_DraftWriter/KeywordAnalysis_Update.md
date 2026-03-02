# Keyword Analysis Feature Update Plan

## 1. Tổng Quan

### 1.1. Vấn Đề Hiện Tại
Chức năng phân tích từ khóa (Keyword Analysis) hiện đang bị phân tán và trùng lặp giữa hai vị trí:
1. **Trên trang chính** (`index.php`): Có một phần nhập từ khóa đơn giản và nút phân tích
2. **Trong tab Keyword Analysis** (`tab_keyword_analysis.php`): Có giao diện phân tích chi tiết hơn

Điều này gây ra:
- Trải nghiệm người dùng không nhất quán
- Xung đột giữa các phần tử có ID khác nhau (`#keywords` vs `#main-keywords`)
- Gây nhầm lẫn về nơi nên sử dụng chức năng

### 1.2. Mục Tiêu Cải Thiện
- Thống nhất giao diện phân tích từ khóa
- Tận dụng tối đa hệ thống tab
- Đảm bảo luồng dữ liệu nhất quán
- Cải thiện trải nghiệm người dùng và hiệu quả sử dụng

## 2. Các Thay Đổi Cần Thực Hiện

### 2.1. Cấu Trúc File
| File | Thay Đổi |
|------|----------|
| `views/topics/ultimate_editor/index.php` | Loại bỏ phần nhập và phân tích từ khóa trùng lặp |
| `views/topics/ultimate_editor/includes/tab_keyword_analysis.php` | Nâng cấp thành phiên bản chính thức duy nhất |
| `assets/js/ultimate_editor_exec.js` | Cập nhật `analyzeKeywords()` để xử lý cả hai vị trí |
| `assets/js/ultimate_editor_presents.js` | Cập nhật `displayKeywordAnalysis()` để hiển thị kết quả đúng vị trí |

### 2.2. Chi Tiết Thay Đổi

#### 2.2.1. Thay Đổi Trong `index.php`
```php
<!-- Xóa bỏ đoạn code sau -->
<div class="form-group mt-4">
    <label for="keywords"><?= _l('main_keywords') ?></label>
    <input type="text" id="keywords" class="form-control" placeholder="<?= _l('enter_keywords_comma_separated') ?>" value="<?= isset($active_draft) ? ($active_draft->keywords ?? '') : '' ?>">
</div>
<button id="btn-analyze-keywords" class="btn btn-block btn-default">
    <i class="fa fa-search"></i> <?= _l('analyze_keywords') ?>
</button>

<div id="keyword-analysis" class="mt-3 hidden">
    <h4><?= _l('analysis_results') ?></h4>
    <div id="keyword-analysis-container">
        <!-- Results will be loaded here -->
    </div>
</div>

<!-- Thay thế bằng nút liên kết tới tab -->
<button id="open-keyword-analysis-tab" class="btn btn-block btn-default mt-4">
    <i class="fa fa-key"></i> <?= _l('open_keyword_analysis') ?>
</button>
```

#### 2.2.2. Cập Nhật File `ultimate_editor_exec.js`
```javascript
/**
 * Hàm này phân tích mật độ từ khóa và các chỉ số SEO khác
 * 
 * @EXECUTION_FUNCTION: Thực hiện phân tích từ khóa thông qua API
 */
function analyzeKeywords() {
    const content = editor ? editor.getContent() : $('#editor-content').html();
    
    // Sử dụng cả hai ID có thể tồn tại
    let mainKeywords = $('#main-keywords').val() || $('#keywords').val();
    const draftId = $('#current-draft-id').val();

    if (!content) {
        alert_float('warning', app.lang.please_enter_content_before_analyzing);
        return;
    }

    if (!mainKeywords) {
        alert_float('warning', app.lang.please_enter_keywords);
        return;
    }

    // Hiển thị trạng thái đang phân tích
    $('#keyword-analysis-loading').removeClass('hide');
    $('#keyword-analysis-results').addClass('hide');
    
    // Đảm bảo tab được kích hoạt để hiển thị kết quả
    $('a[href="#tab_keyword_analysis"]').tab('show');

    // Gửi dữ liệu để phân tích
    $.ajax({
        url: admin_url + 'topics/ultimate_editor/analyze_keywords',
        type: 'POST',
        data: {
            draft_id: draftId,
            content: content,
            main_keywords: mainKeywords
        },
        dataType: 'json',
        success: function (response) {
            $('#keyword-analysis-loading').addClass('hide');

            if (response.success) {
                // Hiển thị kết quả
                displayKeywordAnalysis(response.analysis);
                $('#keyword-analysis-results').removeClass('hide');
            } else {
                alert_float('danger', response.message || app.lang.error_analyzing_keywords);
            }
        },
        error: function (xhr, status, error) {
            $('#keyword-analysis-loading').addClass('hide');
            alert_float('danger', app.lang.error_analyzing_keywords + ': ' + error);
        }
    });
}
```

#### 2.2.3. Thêm Event Handler trong `ultimate_editor.js`
```javascript
// Thêm vào hàm setupEventHandlers()
$('#open-keyword-analysis-tab').off('click').on('click', function() {
    $('a[href="#tab_keyword_analysis"]').tab('show');
});

// Thêm handler cho nút phân tích trong tab
$('.analyze-keywords-btn').off('click').on('click', function() {
    analyzeKeywords();
});
```

#### 2.2.4. Cập Nhật Hàm `displayKeywordAnalysis()` Trong `ultimate_editor_presents.js`
```javascript
function displayKeywordAnalysis(analysis) {
    // Cập nhật các thống kê tổng quan
    $('#total-word-count').text(analysis.total_words);
    $('#average-keyword-score').text(analysis.average_score);
    $('#analyzed-keywords-count').text(Object.keys(analysis.keywords).length);
    
    // Xóa nội dung cũ
    $('#keyword-details-body').empty();
    $('.recommendations-list').empty();
    
    // Thêm dữ liệu chi tiết từng từ khóa
    $.each(analysis.keywords, function(keyword, data) {
        // Xác định trạng thái
        let statusClass = 'label-default';
        if (data.score >= 80) statusClass = 'label-success';
        else if (data.score >= 50) statusClass = 'label-info';
        else if (data.score >= 30) statusClass = 'label-warning';
        else statusClass = 'label-danger';
        
        // Tạo hàng trong bảng
        $('#keyword-details-body').append(`
            <tr>
                <td>${keyword}</td>
                <td>${data.count}</td>
                <td>${data.density}%</td>
                <td>${data.score}/100</td>
                <td><span class="label ${statusClass}">${getStatusText(data.score)}</span></td>
            </tr>
        `);
        
        // Thêm khuyến nghị
        if (data.recommendations && data.recommendations.length) {
            data.recommendations.forEach(function(rec) {
                $('.recommendations-list').append(`
                    <div class="alert alert-info">
                        <strong>${keyword}:</strong> ${rec}
                    </div>
                `);
            });
        }
    });
    
    // Hiển thị biểu đồ nếu có
    if (typeof renderKeywordChart === 'function') {
        renderKeywordChart(analysis);
    }
    
    // Hiển thị cả trong container cũ nếu nó tồn tại (để tương thích ngược)
    if ($('#keyword-analysis').length && $('#keyword-analysis-container').length) {
        $('#keyword-analysis').removeClass('hidden');
        // Tạo bản tóm tắt đơn giản cho container cũ
        $('#keyword-analysis-container').html(`
            <div class="alert alert-info">
                <p><strong>${app.lang.total_words}:</strong> ${analysis.total_words}</p>
                <p><strong>${app.lang.average_score}:</strong> ${analysis.average_score}/100</p>
                <p><strong>${app.lang.analyzed_keywords}:</strong> ${Object.keys(analysis.keywords).length}</p>
                <p><a href="#" onclick="$('a[href=\\'#tab_keyword_analysis\\']').tab('show'); return false;">
                    <i class="fa fa-external-link"></i> ${app.lang.view_detailed_analysis}
                </a></p>
            </div>
        `);
    }
    
    // Helper function
    function getStatusText(score) {
        if (score >= 80) return app.lang.excellent;
        if (score >= 60) return app.lang.good;
        if (score >= 40) return app.lang.average;
        if (score >= 20) return app.lang.poor;
        return app.lang.needs_improvement;
    }
}
```

## 3. Kế Hoạch Triển Khai

### 3.1. Thứ Tự Thực Hiện
1. Đảm bảo tất cả chuỗi ngôn ngữ cần thiết đã được thêm
2. Cập nhật JavaScript (các file `ultimate_editor_exec.js` và `ultimate_editor_presents.js`)
3. Sửa đổi `tab_keyword_analysis.php` để đảm bảo tính nhất quán
4. Cuối cùng, chỉnh sửa `index.php` để xóa phần trùng lặp

### 3.2. Kiểm Thử
- **Kiểm tra nút liên kết tab**: Đảm bảo nút chuyển hướng đến tab keyword analysis hoạt động
- **Kiểm tra phân tích**: Đảm bảo chức năng phân tích trong tab hoạt động
- **Kiểm tra hiển thị**: Đảm bảo kết quả phân tích hiển thị đúng trong tab
- **Tương thích trình duyệt**: Kiểm tra hoạt động trên các trình duyệt chính

### 3.3. Lưu Ý
- Đảm bảo dữ liệu từ khóa không bị mất khi chuyển đổi giữa các tab 
- Hiển thị thông báo phù hợp nếu chưa nhập nội dung hoặc từ khóa
- Lưu giá trị từ khóa vào bản nháp để sử dụng lại sau này

## 4. Dự Kiến Lợi Ích

- **Trải nghiệm người dùng nhất quán**: Một vị trí duy nhất để phân tích từ khóa
- **Giao diện rõ ràng hơn**: Tách biệt rõ ràng giữa các chức năng
- **Bảo trì dễ dàng hơn**: Hạn chế trùng lặp code
- **Tương thích SEO tốt hơn**: Dễ dàng mở rộng thêm tính năng phân tích nâng cao

Khi triển khai thành công, tính năng phân tích từ khóa sẽ hoạt động hiệu quả hơn và mang lại trải nghiệm người dùng tốt hơn trong Ultimate Editor, hỗ trợ quy trình tạo nội dung chuyên nghiệp.
