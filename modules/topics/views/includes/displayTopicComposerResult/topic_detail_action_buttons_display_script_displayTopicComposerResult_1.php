<?php defined('BASEPATH') or exit('No direct script access allowed');   ?>
<script>

// Add new shared function for AI Edit API calls
function callAIEditAPI(content, field, prompt, callback, configData = {}) {
    console.log('Calling AI Edit webhook with content:', content, field, prompt);
    
    // Add controller data from global state if available
    if (window.TopicComposer && window.TopicComposer.selectedController) {
        const controller = window.TopicComposer.selectedController;
        
        // Add controller info to configData
        configData.controller = controller;
        
        // Enhance prompt with controller information
        if (controller.writing_style) {
            prompt += `\n\nFollow this writing style: ${controller.writing_style}`;
        }
        
        if (controller.platform) {
            prompt += `\n\nOptimize for platform: ${controller.platform}`;
        }
        
        console.log('Using controller:', controller.site);
    }
    
    return $.ajax({
        url: 'https://automate.chantroituonglai.com/webhook/AIEDIT',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({
            content: content,
            field: field,
            prompt: prompt,
            controller: configData.controller // Include controller in API request
        }),
        success: function(response) {
            try {
                if (typeof response === 'string') {
                    response = JSON.parse(response);
                }

                if (response && response.success === "true" && response.output) {
                    callback(response.output, configData);
                    alert_float('success', '<?php echo _l('ai_edit_success'); ?>');
                } else {
                    alert_float('warning', response.message || '<?php echo _l('ai_edit_error'); ?>');
                }
            } catch (error) {
                console.error('Error parsing response:', error);
                alert_float('danger', '<?php echo _l('ai_service_error'); ?>');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
            alert_float('danger', '<?php echo _l('ai_service_error'); ?>');
        }
    });
}

</script>