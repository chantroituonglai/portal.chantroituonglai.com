<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="topic-additional-info">
    <?php
    // Kiểm tra và hiển thị WordPress link từ data
    $topic_data = json_decode($topic->data, true);
    
    if (!empty($topic_data)) {
        // Kiểm tra các trạng thái hợp lệ từ action states
        $valid_states = [];
        if (isset($topic->action_state_code)) {
            $CI =& get_instance();
            $CI->load->model('Action_state_model');
            $state = $CI->Action_state_model->get_state_by_code($topic->action_state_code);
            if (!empty($state) && isset($state['valid_data']) && $state['valid_data'] == 1) {
                // Hiển thị WordPress link nếu có
                if (!empty($topic_data['link'])) {
                    ?>
                    <div class="wordpress-link-section">
                        <div class="row">
                            <div class="col-md-12">
                                <strong><?php echo _l('wordpress_post_link'); ?>:</strong>
                                <a href="<?php echo html_escape($topic_data['link']); ?>" 
                                   target="_blank" 
                                   class="ml-2">
                                    <i class="fa fa-external-link"></i> 
                                    <?php echo html_escape($topic_data['link']); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php
                }

                // Hiển thị thông tin bổ sung khác từ WordPress nếu có
                if (!empty($topic_data['id'])) {
                    ?>
                    <div class="wordpress-info-section">
                        <div class="row">
                            <div class="col-md-12">
                                <strong><?php echo _l('wordpress_post_id'); ?>:</strong>
                                <span class="ml-2"><?php echo html_escape($topic_data['id']); ?></span>
                            </div>
                        </div>
                    </div>
                    <?php
                }
            }
        }
    }
    ?>
</div>

<style>
.topic-additional-info {
    margin: 15px 0;
    padding: 10px;
    background: #f9f9f9;
    border: 1px solid #e3e3e3;
    border-radius: 3px;
}

.wordpress-link-section,
.wordpress-info-section {
    margin-bottom: 10px;
}

.wordpress-link-section a {
    word-break: break-all;
}

.wordpress-info-section:last-child {
    margin-bottom: 0;
}
</style> 