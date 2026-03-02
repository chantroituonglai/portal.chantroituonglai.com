<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<script> 
    <?php   
    ob_start();
    $this->load->view('includes/topic_detail_action_buttons_display_script_displaySocialMediaResult');
    $topic_detail_action_buttons_display_script_displaySocialMediaResult = ob_get_clean();
    // Loại bỏ thẻ <script> và </script>
    $topic_detail_action_buttons_display_script_displaySocialMediaResult = str_replace(['<script>', '</script>'], '', $topic_detail_action_buttons_display_script_displaySocialMediaResult);
    echo $topic_detail_action_buttons_display_script_displaySocialMediaResult;
    
    ?>

    <?php 
    ob_start();
    $this->load->view('includes/topic_detail_action_buttons_display_script_displayWordPressResult');
    $topic_detail_action_buttons_display_script_displayWordPressResult = ob_get_clean();
    // Loại bỏ thẻ <script> và </script>
    $topic_detail_action_buttons_display_script_displayWordPressResult = str_replace(['<script>', '</script>'], '', $topic_detail_action_buttons_display_script_displayWordPressResult);
    echo $topic_detail_action_buttons_display_script_displayWordPressResult;
    ?>

    <?php 
    ob_start();
    $this->load->view('includes/topic_detail_action_buttons_display_script_displayImageGenerationResult');
    $topic_detail_action_buttons_display_script_displayImageGenerationResult = ob_get_clean();
    // Loại bỏ thẻ <script> và </script>
    $topic_detail_action_buttons_display_script_displayImageGenerationResult = str_replace(['<script>', '</script>'], '', $topic_detail_action_buttons_display_script_displayImageGenerationResult);
    echo $topic_detail_action_buttons_display_script_displayImageGenerationResult;
    ?>

    <?php 
    ob_start();
    $this->load->view('includes/topic_detail_action_buttons_display_script_displayTopicComposerResult');
    $topic_detail_action_buttons_display_script_displayTopicComposerResult = ob_get_clean();
    // Loại bỏ thẻ <script> và </script>
    $topic_detail_action_buttons_display_script_displayTopicComposerResult = str_replace(['<script>', '</script>'], '', $topic_detail_action_buttons_display_script_displayTopicComposerResult);
    echo $topic_detail_action_buttons_display_script_displayTopicComposerResult;
    ?>  

    <?php 
    ob_start();
    $this->load->view('includes/topic_detail_action_buttons_display_script_displayDraftWritingResult');
    $topic_detail_action_buttons_display_script_displayDraftWritingResult = ob_get_clean();
    // Loại bỏ thẻ <script> và </script>
    $topic_detail_action_buttons_display_script_displayDraftWritingResult = str_replace(['<script>', '</script>'], '', $topic_detail_action_buttons_display_script_displayDraftWritingResult);
    echo $topic_detail_action_buttons_display_script_displayDraftWritingResult;
    ?>  

    function displayDefaultResult(data, workflowData) {
        var timestamp = moment().format('DD/MM/YYYY HH:mm:ss');
        var resultHtml = `
            <div class="execution-result-item ${data.success ? 'success' : 'error'}">
                <div class="execution-timestamp text-muted">
                    <i class="fa fa-clock-o"></i> ${timestamp}
                </div>
                <div class="execution-status">
                    <i class="fa fa-${data.success ? 'check-circle text-success' : 'times-circle text-danger'}"></i> 
                    <strong>${data.success ? '<?php echo _l('execution_successful'); ?>' : '<?php echo _l('execution_failed'); ?>'}</strong>
                </div>
                <div class="execution-message mtop5">
                    ${data.message}
                </div>
                ${data.response ? `
                    <div class="execution-details mtop10">
                        <div class="well well-sm">
                            <pre class="execution-data">${JSON.stringify(data.response || {}, null, 2)}</pre>
                        </div>
                    </div>
                ` : ''}
            </div>
        `;
        prependExecutionResult(resultHtml);
    }

 </script> 