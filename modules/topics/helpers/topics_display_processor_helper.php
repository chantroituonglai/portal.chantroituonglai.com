<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Interface cho các display processor
 */
interface TopicDisplayProcessorInterface {
    public function display($topic, $data);
}

/**
 * Processor cho WordPress Media
 */
class WordPressMediaDisplayProcessor implements TopicDisplayProcessorInterface {
    public function display($topic, $data) {
        ob_start();
        
        $image_url = null;
        
        // Cách 1: Thử lấy từ JSON đã fix
        if (!empty($data['upload_media']['guid']['raw'])) {
            $image_url = $data['upload_media']['guid']['raw'];
        }
        
        // Cách 2: Nếu không lấy được từ JSON, thử dùng get_guid_raw_from_upload_media_response
        if (!$image_url) {
            $image_url = get_guid_raw_from_upload_media_response($topic->data);
        }
        
        if ($image_url) {
            echo '<div class="text-center">';
            echo '<img src="' . $image_url . '" class="img-responsive" style="max-width: 100%; margin: 0 auto;">';
            echo '<div class="mt-2" style="margin-top: 15px; margin-left: auto; display: block; position: relative; width: fit-content;">';
            echo '<a href="' . $image_url . '" target="_blank" class="btn btn-primary">';
            echo '<i class="fa fa-external-link"></i> ' . _l('open_in_new_tab');
            echo '</a>';
            echo '</div>';
            echo '</div>';
        } else {
            echo '<div class="alert alert-warning">' . _l('no_image_data') . '</div>';
            if (ENVIRONMENT !== 'production') {
                echo '<div class="alert alert-info">Debug Info:';
                echo '<pre>Raw data: ' . htmlspecialchars($topic->data) . '</pre>';
                echo '<pre>Parsed data: ' . print_r($data, true) . '</pre>';
                echo '</div>';
            }
        }
        
        return ob_get_clean();
    }
}

/**
 * Processor cho Toplist Single Item Raw
 */
class ToplistSingleItemDisplayProcessor implements TopicDisplayProcessorInterface {
    public function display($topic, $data) {
        ob_start();
        
        echo '<div class="table-responsive">';
        echo '<table class="table table-bordered table-striped">';
        
        // Header
        echo '<thead><tr>';
        echo '<th style="width: 200px;">' . _l('field') . '</th>';
        echo '<th>' . _l('value') . '</th>';
        echo '</tr></thead>';
        
        // Body
        echo '<tbody>';
        
        // Hiển thị các trường chính
        $main_fields = [
            'Topic' => _l('topic'),
            'Title' => _l('title'),
            'Summary' => _l('summary'),
            'Item_Position' => _l('item_position'),
            'Item_Title' => _l('item_title'), 
            'Item_Content' => _l('item_content')
        ];
        
        foreach ($main_fields as $field => $label) {
            if (isset($data[$field])) {
                echo '<tr>';
                echo '<td><strong>' . $label . '</strong></td>';
                echo '<td>' . nl2br(html_escape($data[$field])) . '</td>';
                echo '</tr>';
            }
        }
        
        // Hiển thị Keywords
        if (!empty($data['TopicKeywords'])) {
            echo '<tr>';
            echo '<td><strong>' . _l('keywords') . '</strong></td>';
            echo '<td>';
            $keywords = explode(',', $data['TopicKeywords']);
            foreach ($keywords as $keyword) {
                echo '<span class="label label-info mr-1">' . trim($keyword) . '</span> ';
            }
            echo '</td>';
            echo '</tr>';
        }
        
        // Hiển thị Topic Footer
        if (!empty($data['Topic_footer'])) {
            echo '<tr>';
            echo '<td><strong>' . _l('topic_footer') . '</strong></td>';
            echo '<td>' . nl2br(html_escape($data['Topic_footer'])) . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table></div>';
        
        // Debug section
        if (ENVIRONMENT !== 'production') {
            echo '<div class="mt-4">';
            echo '<button class="btn btn-info" type="button" data-toggle="collapse" data-target="#debugData">';
            echo _l('show_debug_data');
            echo '</button>';
            echo '<div class="collapse mt-2" id="debugData">';
            echo '<div class="card card-body">';
            echo '<pre>' . print_r($data, true) . '</pre>';
            echo '</div></div></div>';
        }
        
        return ob_get_clean();
    }
}

/**
 * Default processor cho các loại không xác định
 */
class DefaultDisplayProcessor implements TopicDisplayProcessorInterface {
    public function display($topic, $data) {
        ob_start();
        
        if (!empty($data)) {
            echo '<div class="alert alert-info">' . _l('no_specific_handler') . ' ' . html_escape($topic->target_type) . '</div>';
            echo '<pre>' . print_r($data, true) . '</pre>';
        } else {
            echo '<div class="alert alert-warning">' . _l('failed_to_parse_data') . '</div>';
            echo '<pre>' . htmlspecialchars($topic->data) . '</pre>';
        }
        
        return ob_get_clean();
    }
}

/**
 * Processor cho Google Drive Image
 */
class GoogleDriveImageProcessor implements TopicDisplayProcessorInterface {
    public function display($topic, $data) {
        if (empty($data)) {
            return '<div class="alert alert-warning">No image data found</div>';
        }

        $thumbnail_url = $data['thumbnailLink'] ?? '';
        $view_url = $data['webViewLink'] ?? '';
        // Decode HTML entities trong tên file
        $file_name = html_entity_decode($data['name'] ?? 'Image', ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        // Get WordPress posts
        $wordpress_posts = get_wordpress_posts_from_history($topic->topicid);

        $html = '<div class="google-drive-image-viewer">
            <div class="image-container">
                <div class="image-title">' . htmlspecialchars($file_name) . '</div>
                <img src="' . htmlspecialchars($thumbnail_url) . '" 
                     alt="' . htmlspecialchars($file_name) . '"
                     class="img-responsive">
            </div>
            
            <div class="action-buttons">
                <a href="' . htmlspecialchars($view_url) . '" 
                   target="_blank" 
                   class="btn btn-info">
                    <i class="fa fa-external-link"></i> View in Drive
                </a>
            </div>
            
            <div class="wordpress-posts-section">
                <h4 class="section-title">Set as Featured Image</h4>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>';
        
        if (!empty($wordpress_posts)) {
            foreach ($wordpress_posts as $post) {
                // Decode HTML entities trong title
                $post_title = html_entity_decode($post['title'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                
                $html .= '<tr>
                    <td>
                        <a href="' . htmlspecialchars($post['link']) . '" target="_blank">
                            ' . htmlspecialchars($post_title) . '
                        </a>
                    </td>
                    <td>' . _dt($post['date']) . '</td>
                    <td>
                        <button type="button" 
                                class="btn btn-primary btn-sm set-featured-image"
                                data-post-id="' . $post['id'] . '"
                                data-image-url="' . htmlspecialchars($data['webContentLink']) . '"
                                onclick="setAsFeaturedImage(this)">
                            <i class="fa fa-image"></i> Set as Cover
                        </button>
                    </td>
                </tr>';
            }
        } else {
            $html .= '<tr><td colspan="3" class="text-center">No WordPress posts found</td></tr>';
        }

        $html .= '</tbody></table></div></div>';

        // Add CSS
        $html .= '<style>
            .google-drive-image-viewer {
                padding: 20px;
                background: #f8f9fa;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            .google-drive-image-viewer .image-container {
                text-align: center;
                margin-bottom: 20px;
                background: #fff;
                padding: 10px;
                border-radius: 4px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }
            .image-title {
                font-size: 16px;
                font-weight: 600;
                color: #333;
                margin-bottom: 10px;
                padding: 5px;
                background: #f8f9fa;
                border-radius: 4px;
            }
            .google-drive-image-viewer img {
                max-width: 100%;
                height: auto;
                margin: 0 auto;
            }
            .action-buttons {
                display: flex;
                gap: 10px;
                justify-content: center;
                margin-bottom: 20px;
            }
            .wordpress-posts-section {
                margin-top: 20px;
                background: #fff;
                padding: 15px;
                border-radius: 4px;
            }
            .section-title {
                margin-bottom: 15px;
                color: #333;
                font-size: 16px;
                font-weight: 600;
            }
        </style>';

        // Add JavaScript
        $html .= '<script>
        function setAsFeaturedImage(button) {
            var postId = $(button).data("post-id");
            var imageUrl = $(button).data("image-url");
            
            // Disable button and show loading state
            $(button).prop("disabled", true)
                    .html(\'<i class="fa fa-spinner fa-spin"></i> Setting...\');
            
            // Call workflow to set featured image
            $.ajax({
                url: admin_url + "topics/execute_workflow",
                type: "POST",
                data: {
                    workflow_id: "' . get_option('topics_set_featured_image_workflow_id') . '",
                    target_type: "SetFeaturedImage",
                    target_state: "Processing",
                    wordpress_post_id: postId,
                    image_url: imageUrl
                },
                success: function(response) {
                    if (response.success) {
                        alert_float("success", "Featured image set successfully");
                    } else {
                        alert_float("danger", response.message || "Failed to set featured image");
                    }
                },
                error: function() {
                    alert_float("danger", "Network error occurred");
                },
                complete: function() {
                    // Reset button state
                    $(button).prop("disabled", false)
                            .html(\'<i class="fa fa-image"></i> Set as Cover\');
                }
            });
        }
        </script>';

        return $html;
    }
}

/**
 * Factory class để tạo processor phù hợp
 */
class TopicDisplayProcessorFactory {
    public static function create($target_type, $action_type_code = null, $action_state_code = null) {
        // Activity log với description rõ ràng hơn
        log_activity('Creating display processor for Topic [Type: ' . $target_type . ', Action: ' . $action_type_code . ', State: ' . $action_state_code . ']');
        
        // 1. Xử lý theo action_type_code và action_state_code trước
        if ($action_type_code && $action_state_code) {
            // Case: ImageGeneration_MultiPurpose
            if ($action_type_code === 'ImageGeneration_MultiPurpose' && 
                $action_state_code === 'ImageGeneration_MultiPurpose_Complete') {
                log_activity('Selected UnsplashImageSliderProcessor based on action type and state');
                return new UnsplashImageSliderProcessor();
            }

            // Case: SearchImageToggle
            if ($action_type_code === 'SearchImageToggle' && 
                $action_state_code === 'ImgCompleted') {
                log_activity('Selected SearchImageToggleProcessor based on action type and state');
                return new SearchImageToggleProcessor();
            }

            // Case: ExecutionTag_ExecWriting
            if ($action_type_code === 'ExecutionTag_ExecWriting') {
                switch ($action_state_code) {
                    case 'ExecutionTag_ExecWriting_PostCreated':
                        log_activity('Selected WordPressPostProcessor for ExecutionTag_ExecWriting_PostCreated');
                        return new WordPressPostProcessor();
                    case 'ExecutionTag_ExecWriting_Partial':
                        log_activity('Selected ExecWritingPartialProcessor for ExecutionTag_ExecWriting_Partial');
                        return new ExecWritingPartialProcessor();
                }
            }

            // Thêm case mới cho ImageGenerateToggle
            if ($action_type_code === 'ImageGenerateToggle' && 
                $action_state_code === 'GenImgCompleted') {
                log_activity('Selected GoogleDriveImageProcessor for ImageGenerateToggle');
                return new GoogleDriveImageProcessor();
            }
        }

        // 2. Xử lý theo target_type nếu không match các case trên
        switch ($target_type) {
            case 'WORDPRESS_MEDIA':
                log_activity('Selected WordPressMediaDisplayProcessor based on target type');
                return new WordPressMediaDisplayProcessor();
                
            case 'TOPLIST_SINGLE_ITEM_RAW':
                log_activity('Selected ToplistSingleItemDisplayProcessor based on target type');
                return new ToplistSingleItemDisplayProcessor();
                
            case 'WORDPRESS_POST':
                log_activity('Selected WordPressPostProcessor based on target type');
                return new WordPressPostProcessor();
                
            default:
                log_activity('No specific processor found, using DefaultDisplayProcessor');
                return new DefaultDisplayProcessor();
        }
    }
}

/**
 * Processor cho Unsplash Image Slider
 */
class UnsplashImageSliderProcessor implements TopicDisplayProcessorInterface {
    public function display($topic, $data) {
        // Kiểm tra và lấy danh sách ảnh từ log.results
        if (empty($data['log']['results'])) {
            return '<div class="alert alert-warning">No images found</div>';
        }

        // Lấy 6 ảnh đầu tiên từ results
        $images = array_slice($data['log']['results'], 0, 6);
        // log_activity('UnsplashImageSliderProcessor: Displaying ' . print_r($images, true) . ' images');
        $html = '<div class="unsplash-slider">
            <div class="row">';
        
        // Hiển thị 3 ảnh mỗi dòng
        foreach (array_chunk($images, 3) as $row) {
            $html .= '<div class="row mb-4">';
            foreach ($row as $image) {
                $html .= '<div class="col-md-4">
                    <div class="image-card">
                        <a href="' . $image['links']['html'] . '" target="_blank" class="image-link">
                            <img src="' . $image['urls']['regular'] . '" 
                                 class="img-fluid" 
                                 alt="' . htmlspecialchars($image['alt_description'] ?? '') . '"
                                 loading="lazy">
                            <div class="image-overlay">
                                <div class="image-info">
                                    <p class="alt-description">' . htmlspecialchars($image['alt_description'] ?? '') . '</p>
                                    <div class="photographer">
                                        <img src="' . $image['user']['profile_image']['small'] . '" 
                                             class="rounded-circle" 
                                             width="30" 
                                             height="30">
                                        <span>' . htmlspecialchars($image['user']['name']) . '</span>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>';
            }
            $html .= '</div>';
        }

        $html .= '</div></div>';

        // Add CSS
        $html .= '<style>
            .unsplash-slider {
                margin: 20px 0;
            }
            .image-card {
                position: relative;
                border-radius: 8px;
                overflow: hidden;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                margin-bottom: 20px;
            }
            .image-link {
                display: block;
                text-decoration: none;
                color: white;
            }
            .image-card img {
                width: 100%;
                height: 250px;
                object-fit: cover;
                transition: transform 0.3s ease;
            }
            .image-card:hover img {
                transform: scale(1.05);
            }
            .image-overlay {
                position: absolute;
                bottom: 0;
                left: 0;
                right: 0;
                background: linear-gradient(transparent, rgba(0,0,0,0.8));
                padding: 15px;
                color: white;
            }
            .image-info {
                display: flex;
                flex-direction: column;
                gap: 10px;
            }
            .alt-description {
                margin: 0;
                font-size: 14px;
                opacity: 0.9;
                text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
            }
            .photographer {
                display: flex;
                align-items: center;
                gap: 10px;
            }
            .photographer img {
                width: 30px;
                height: 30px;
            }
            .photographer span {
                font-size: 14px;
                text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
            }
        </style>';

        return $html;
    }
}

/**
 * Processor cho Search Image Toggle
 */
class SearchImageToggleProcessor implements TopicDisplayProcessorInterface {
    public function display($topic, $data) {
        // Kiểm tra và lấy danh sách ảnh từ results
        if (empty($data['results'])) {
            return '<div class="alert alert-warning">No images found</div>';
        }

        // Lấy 6 ảnh đầu tiên từ results
        $images = array_slice($data['results'], 0, 6);
        log_activity('SearchImageToggleProcessor: Displaying ' . count($images) . ' images');
        
        $html = '<div class="unsplash-slider">
            <div class="row">';
        
        // Hiển thị 3 ảnh mỗi dòng
        foreach (array_chunk($images, 3) as $row) {
            $html .= '<div class="row mb-4">';
            foreach ($row as $image) {
                $html .= '<div class="col-md-4">
                    <div class="image-card">
                        <a href="' . $image['links']['html'] . '" target="_blank" class="image-link">
                            <img src="' . $image['urls']['regular'] . '" 
                                 class="img-fluid" 
                                 alt="' . htmlspecialchars($image['alt_description'] ?? '') . '"
                                 loading="lazy">
                            <div class="image-overlay">
                                <div class="image-info">
                                    <p class="alt-description">' . htmlspecialchars($image['alt_description'] ?? '') . '</p>
                                    <div class="photographer">
                                        <img src="' . $image['user']['profile_image']['small'] . '" 
                                             class="rounded-circle" 
                                             width="30" 
                                             height="30">
                                        <span>' . htmlspecialchars($image['user']['name']) . '</span>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>';
            }
            $html .= '</div>';
        }

        $html .= '</div></div>';

        // Add CSS
        $html .= '<style>
            .unsplash-slider {
                margin: 20px 0;
            }
            .image-card {
                position: relative;
                border-radius: 8px;
                overflow: hidden;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                margin-bottom: 20px;
            }
            .image-link {
                display: block;
                text-decoration: none;
                color: white;
            }
            .image-card img {
                width: 100%;
                height: 250px;
                object-fit: cover;
                transition: transform 0.3s ease;
            }
            .image-card:hover img {
                transform: scale(1.05);
            }
            .image-overlay {
                position: absolute;
                bottom: 0;
                left: 0;
                right: 0;
                background: linear-gradient(transparent, rgba(0,0,0,0.8));
                padding: 15px;
                color: white;
            }
            .image-info {
                display: flex;
                flex-direction: column;
                gap: 10px;
            }
            .alt-description {
                margin: 0;
                font-size: 14px;
                opacity: 0.9; 
                text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
            }
            .photographer {
                display: flex;
                align-items: center;
                gap: 10px;
            }
            .photographer img {
                width: 30px;
                height: 30px;
            }
            .photographer span {
                font-size: 14px;
                text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
            }
        </style>';

        return $html;
    }
}

/**
 * Processor cho WordPress Post
 */
class WordPressPostProcessor implements TopicDisplayProcessorInterface {
    public function display($topic, $data) {
        if (empty($data)) {
            return '<div class="alert alert-warning">No post data found</div>';
        }

        $post_id = $data['id'] ?? '';
        $post_link = $data['link'] ?? '';
        $post_title = $data['title']['rendered'] ?? '';
        
        // Trích xuất host từ post link
        $host = parse_url($post_link, PHP_URL_SCHEME) . '://' . parse_url($post_link, PHP_URL_HOST);
        $edit_link = $host . "/wp-admin/post.php?post={$post_id}&action=edit";

        $html = '<div class="wordpress-post-viewer">
            <div class="post-actions mb-3">
                <a href="' . $post_link . '" target="_blank" class="btn btn-primary mr-2">
                    <i class="fa fa-eye"></i> Xem bài viết
                </a>
                <a href="' . $edit_link . '" target="_blank" class="btn btn-info">
                    <i class="fa fa-edit"></i> Edit bài viết
                </a>
                <!-- <button class="btn btn-secondary" onclick="openPostPreview()">
                    <i class="fa fa-desktop"></i> Xem trước
                </button> -->
            </div>
            
            <!--div id="postPreviewModal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">' . htmlspecialchars($post_title) . '</h5>
                        <button type="button" class="close" onclick="closePostPreview()">×</button>
                    </div>
                    <div class="modal-body">
                        <iframe id="postPreviewFrame" src="' . $post_link . '"></iframe>
                    </div>
                </div>
            </div-->
        </div>';

        // Add JavaScript
        $html .= '<script>
            function openPostPreview() {
                $("#postPreviewModal").show();
                $("body").css("overflow", "hidden");
            }
            
            function closePostPreview() {
                $("#postPreviewModal").hide();
                $("body").css("overflow", "auto");
            }
        </script>';

        // Add CSS
        $html .= '<style>
            .wordpress-post-viewer {
                padding: 15px;
            }
            .post-actions {
                display: flex;
                gap: 10px;
            }
            #postPreviewModal {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                z-index: 9999;
                background: rgba(0, 0, 0, 0.85);
            }
            #postPreviewModal .modal-content {
                position: relative;
                width: 100%;
                height: 100%;
                background: #fff;
                display: flex;
                flex-direction: column;
            }
            #postPreviewModal .modal-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 10px 15px;
                background: #f8f9fa;
                border-bottom: 1px solid #dee2e6;
            }
            #postPreviewModal .modal-title {
                margin: 0;
                font-size: 1.1rem;
            }
            #postPreviewModal .close {
                border: none;
                background: none;
                font-size: 1.5rem;
                cursor: pointer;
                padding: 0;
                line-height: 1;
                opacity: 0.5;
            }
            #postPreviewModal .close:hover {
                opacity: 1;
            }
            #postPreviewModal .modal-body {
                flex: 1;
                padding: 0;
                overflow: hidden;
            }
            #postPreviewFrame {
                width: 100%;
                height: 100%;
                border: none;
                display: block;
            }
        </style>';

        return $html;
    }
}

/**
 * Processor cho ExecutionTag_ExecWriting với state ExecutionTag_ExecWriting_Partial
 */
class ExecWritingPartialProcessor implements TopicDisplayProcessorInterface {
    public function display($topic, $data) {
    
        // Kiểm tra và lấy nội dung từ cả hai cấu trúc có thể có
        $item_content = '';
        $google_content = '';

        // Kiểm tra cấu trúc cũ
        if (!empty($data['item_data']['Item_Content'])) {
            $item_content = $data['item_data']['Item_Content'];
        } 
        // Kiểm tra cấu trúc mới
        else if (!empty($data['item_data']['item_content'])) {
            $item_content = $data['item_data']['item_content'];
        }
    
        // Kiểm tra nội dung Google với nhiều cấu trúc có thể có
        $json_contents =  repair_json(html_entity_decode(($data['google_response']['candidates'][0]['content']['parts'][0]['text']),
         ENT_QUOTES, 'UTF-8'));
        if (!empty($json_contents)) {
            $decoded_text = json_decode($json_contents, true);

            // Cấu trúc 1: Trực tiếp có trường content
            if (isset($decoded_text['content'])) {
                $google_content = $decoded_text['content'];
            }
            // Cấu trúc 2: Có trường Data.Content_fixer
            else if (isset($decoded_text['Data']['Content_fixer'])) {
                $google_content = $decoded_text['Data']['Content_fixer'];
            }
        }

        // return ($data['google_response']['candidates'][0]['content']['parts'][0]['text']);
        if (empty($item_content) || empty($google_content)) {
            return '<div class="alert alert-warning">No content available</div>';
        }
       
        // Giải mã HTML entities
        $original_content = html_entity_decode($item_content, ENT_QUOTES, 'UTF-8');
        $decoded_google_content = html_entity_decode($google_content, ENT_QUOTES, 'UTF-8');

        // Lấy thông tin từ data với hỗ trợ cả hai cấu trúc
        $item_title = $data['item_data']['Item_Title'] ?? $data['item_data']['ten_top'] ?? '';
        $item_title_original = $data['item_data']['Title'] ?? $data['item_data']['ten_top'] ?? '';
        $item_position = $data['item_data']['Item_Position'] ?? $data['item_data']['vi_tri'] ?? '';
        $topic_keywords = $data['item_data']['TopicKeywords'] ?? $data['item_data']['google_keywords'] ?? '';

         // Convert keywords thành tags
         $keyword_tags = '';
         if (!empty($topic_keywords)) {
             $keywords = explode(',', $topic_keywords);
             foreach ($keywords as $keyword) {
                 $keyword = trim($keyword);
                 if (!empty($keyword)) {
                     $keyword_tags .= '<span class="keyword-tag">' . html_entity_decode($keyword) . '</span>';
                 }
             }
         }

        // Đảm bảo nội dung iframe có DOCTYPE
        $original_content_iframe =  htmlspecialchars($original_content);
        $google_content_iframe = htmlspecialchars($decoded_google_content);
        // Tạo phần header info
        $html = '<div class="topic-info-header">
        <div class="row">
            <div class="col-md-12">
                <div class="info-group">
                    <label>Title:</label>
                    <div class="info-value">' . html_entity_decode($item_title_original) . '</div>
                </div>
                <div class="info-group">
                    <label>Item Position:</label>
                    <div class="info-value">' . html_entity_decode($item_position) . '</div>
                </div>
            </div>
            <div class="col-md-12">
                <div class="info-group">
                    <label>Item Title:</label>
                    <div class="info-value">' . html_entity_decode($item_title) . '</div>
                </div>
                <div class="info-group">
                    <label>Topic Keywords:</label>
                    <div class="info-value keywords-container">
                            ' . $keyword_tags . '
                    </div>
                </div>
                </div>
            </div>
        </div>';

        $html .= '<div class="writing-content-wrapper">
            <div class="row mb-3">
                <div class="col-md-6">
                    <h4>Nội dung gốc</h4>
                    <iframe class="content-iframe" srcdoc="' . $original_content_iframe . '"></iframe>
                </div>
                <div class="col-md-6">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h4>Nội dung từ Google</h4>
                        <div class="action-buttons">
                            <button class="btn btn-primary btn-sm" onclick="toggleEditMode()">
                                <i class="fa fa-pencil"></i> Edit Content
                            </button>
                            <button class="btn btn-info btn-sm" onclick="sendToRewrite()">
                                <i class="fa fa-refresh"></i> Send To Rewrite
                            </button>
                        </div>
                    </div>
                    <div id="google-content-wrapper">
                        <iframe id="google-content-iframe" class="content-iframe" srcdoc="' . $google_content_iframe . '"></iframe>
                        <div id="google-content-editor" class="content-editor hide">
                            ' . htmlspecialchars($decoded_google_content) . '
                        </div>
                    </div>
                </div>
            </div>

            <div class="additional-fields-section panel-default">
                <div class="panel-heading">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="panel-title">Bổ sung ý kiến</h4>
                        <button class="btn btn-success btn-xs" onclick="addNewField()">
                            <i class="fa fa-plus"></i> Thêm ý kiến
                        </button>
                    </div>
                </div>
                <div class="panel-body">
                    <div id="additional-fields-container"></div>
                    <div class="text-right mtop15">
                        <button class="btn btn-info" onclick="saveAdditionalFields()">
                            <i class="fa fa-save"></i> Lưu ý kiến
                        </button>
                    </div>
                </div>
            </div>';

        // Add CSS
        $html .= '<style>
          .topic-info-header {
                background: #f8f9fa;
                padding: 15px;
                margin-bottom: 20px;
                border-radius: 5px;
                border: 1px solid #e9ecef;
            }
            .info-group {
                margin-bottom: 10px;
            }
            .info-group label {
                font-weight: 600;
                color: #495057;
                margin-bottom: 5px;
                display: block;
            }
            .info-value {
                background: #fff;
                padding: 8px 12px;
                border-radius: 4px;
                border: 1px solid #dee2e6;
                min-height: 38px;
            }
         
            .writing-content-wrapper {
                margin-top: 20px;
            }

            .writing-content-wrapper {
                padding: 15px;
            }
                 .keywords-container {
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
                padding: 8px;
            }
            .keyword-tag {
                background: #e9ecef;
                color: #495057;
                padding: 4px 12px;
                border-radius: 16px;
                font-size: 13px;
                display: inline-block;
                border: 1px solid #dee2e6;
            }
            .keyword-tag:hover {
                background: #dee2e6;
            }
            .content-iframe {
                width: 100%;
                height: 400px;
                background: #e5e7eb;
                border: 1px solid #ddd;
                border-radius: 5px;
                overflow: hidden;

            }
            .content-editor {
                width: 100%;
                height: 400px;
                border: 1px solid #ddd;
                border-radius: 5px;
                padding: 15px;
                background: #fff;
            }
            .additional-fields-section {
                margin-top: 25px;
                background: #fff;
                border: 1px solid #e4e5e7;
                border-radius: 4px;
            }
            .additional-fields-section .panel-heading {
                background: #f6f8fa;
                padding: 15px;
                border-bottom: 1px solid #e4e5e7;
            }
            .additional-fields-section .panel-title {
                color: #333;
                font-size: 14px;
                font-weight: 600;
                margin: 0;
            }
            .additional-fields-section .panel-body {
                padding: 15px;
            }
            .field-wrapper {
                position: relative;
                background: #f9f9f9;
                border: 1px solid #e4e5e7;
                border-radius: 4px;
                padding: 15px;
                margin-bottom: 15px;
            }
            .field-wrapper textarea {
                width: 100%;
                min-height: 80px;
                border: 1px solid #e4e5e7;
                border-radius: 4px;
                padding: 8px 12px;
                color: #4a4a4a;
                font-size: 13px;
                resize: vertical;
            }
            .field-wrapper textarea:focus {
                border-color: #84c529;
                box-shadow: 0 0 0 0.2rem rgba(132, 197, 41, 0.25);
                outline: 0;
            }
            .field-wrapper .remove-field {
                position: absolute;
                top: 10px;
                right: 10px;
                color: #fc2d42;
                cursor: pointer;
                opacity: 0.7;
                transition: opacity 0.2s;
            }
            .field-wrapper .remove-field:hover {
                opacity: 1;
            }
            .btn-xs {
                padding: 1px 5px;
                font-size: 12px;
                line-height: 1.5;
                border-radius: 3px;
            }
        </style>';

        // Add JavaScript
        $html .= '<script>
        $(function() {
            // Load saved fields from localStorage
            loadSavedFields();
            
            // Initialize TinyMCE
            initTinyMCE();
        });

        function initTinyMCE() {
            tinymce.init({
                selector: "#google-content-editor",
                inline: true,
                plugins: [
                    "advlist autolink lists link image charmap print preview anchor",
                    "searchreplace visualblocks code fullscreen",
                    "insertdatetime media table paste code help wordcount"
                ],
                toolbar: "undo redo | formatselect | bold italic backcolor | \
                    alignleft aligncenter alignright alignjustify | \
                    bullist numlist outdent indent | removeformat | help"
            });
        }

        function toggleEditMode() {
            const iframe = $("#google-content-iframe");
            const editor = $("#google-content-editor");
            
            if (iframe.is(":visible")) {
                iframe.addClass("hide");
                editor.removeClass("hide");
            } else {
                iframe.removeClass("hide");
                editor.addClass("hide");
            }
        }

        function sendToRewrite() {
            // Placeholder for future implementation
            alert_float("info", "Chức năng này sẽ được implement sau");
        }

        function addNewField() {
            const container = $("#additional-fields-container");
            const fieldId = Date.now();
            
            const fieldHtml = `
                <div class="field-wrapper" data-field-id="${fieldId}">
                    <textarea class="form-control" placeholder="Nhập ý kiến của bạn"></textarea>
                    <i class="fa fa-times remove-field" onclick="removeField(${fieldId})" title="Xóa ý kiến"></i>
                </div>
            `;
            
            container.append(fieldHtml);
        }

        function removeField(fieldId) {
            $(`.field-wrapper[data-field-id="${fieldId}"]`).remove();
            saveAdditionalFields();
        }

        function saveAdditionalFields() {
            const fields = [];
            $("#additional-fields-container textarea").each(function() {
                fields.push($(this).val());
            });
            
            localStorage.setItem("additionalFields_' . $topic->topicid . '", JSON.stringify(fields));
            alert_float("success", "Đã lưu ý kiến thành công");
        }

        function loadSavedFields() {
            const savedFields = localStorage.getItem("additionalFields_' . $topic->topicid . '");
            if (savedFields) {
                const fields = JSON.parse(savedFields);
                fields.forEach(field => {
                    const fieldId = Date.now();
                    const fieldHtml = `
                        <div class="field-wrapper" data-field-id="${fieldId}">
                            <textarea class="form-control">${field}</textarea>
                            <i class="fa fa-times remove-field" onclick="removeField(${fieldId})"></i>
                        </div>
                    `;
                    $("#additional-fields-container").append(fieldHtml);
                });
            }
        }
        </script>';

        return $html;
    }
}
