<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<script>
// Draft Writer AI Functionality
window.DraftWriter.ai = {
    improve: function(content, type, style, tone, options = {}) {
        // Add controller data from global state if available
        if (window.DraftWriter && window.DraftWriter.selectedController) {
            options.controller = window.DraftWriter.selectedController;
        }
        
        return $.ajax({
            url: admin_url + 'writing/ai_improve_content',
            type: 'POST',
            data: {
                content: content,
                type: type,
                style: style,
                tone: tone,
                controller: options.controller // Include controller in request
            }
        });
    },
    factCheck: function(content, checkType, options = {}) {
        // Add controller data from global state if available
        if (window.DraftWriter && window.DraftWriter.selectedController) {
            options.controller = window.DraftWriter.selectedController;
        }
        
        return $.ajax({
            url: admin_url + 'writing/ai_fact_check',
            type: 'POST',
            data: {
                content: content,
                check_type: checkType,
                controller: options.controller // Include controller in request
            }
        });
    },
    search: function(query, searchType, limit, options = {}) {
        // Add controller data from global state if available
        if (window.DraftWriter && window.DraftWriter.selectedController) {
            options.controller = window.DraftWriter.selectedController;
        }
        
        return $.ajax({
            url: admin_url + 'writing/ai_search',
            type: 'POST',
            data: {
                query: query,
                search_type: searchType,
                limit: limit,
                controller: options.controller // Include controller in request
            }
        });
    },
    
    // Helper function to enhance prompts with controller information
    enhancePromptWithController: function(prompt) {
        if (!window.DraftWriter || !window.DraftWriter.selectedController) {
            return prompt;
        }
        
        const controller = window.DraftWriter.selectedController;
        let enhancedPrompt = prompt + '\n\n--- Controller Information ---\n';
        
        if (controller.site) {
            enhancedPrompt += `Site: ${controller.site}\n`;
        }
        
        if (controller.platform) {
            enhancedPrompt += `Platform: ${controller.platform}\n`;
        }
        
        if (controller.slogan) {
            enhancedPrompt += `Brand message: ${controller.slogan}\n`;
        }
        
        // Thêm action_1 (Writing Requirements) nếu có
        if (controller.action_1) {
            enhancedPrompt += '\n--- Writing Requirements ---\n' + controller.action_1 + '\n';
        } else if (controller.writing_style) {
            enhancedPrompt += '\n--- Writing Style ---\n' + controller.writing_style + '\n';
        }
        
        // Thêm action_2 (Additional Instructions) nếu có
        if (controller.action_2) {
            enhancedPrompt += '\n--- Additional Instructions ---\n' + controller.action_2 + '\n';
        }
        
        console.log('Enhanced prompt with controller:', controller.site);
        return enhancedPrompt;
    }
};
//    url: admin_url + "topics/execute_workflow", admin/topics/controllers
// type: "POST",

// Draft Writer Analysis Functionality
window.DraftWriter.analysis = {
    updateKeywords: function() {
        const content = tinymce.get('draft-content').getContent({format: 'text'});
        const mainKeywords = $('#draft-tags').val();
        
        return $.ajax({
            url: admin_url + 'topics/writing/get_keyword_analysis',
            type: 'POST',
            data: {
                content: content,
                main_keywords: mainKeywords
            }
        }).done(function(response) {
            try {   
                response = JSON.parse(response);
                if (response.success) {
                    updateKeywordAnalysisUI(response.analysis);
                }
            } catch (error) {
                console.error('Error updating keyword analysis:', error);
            }
        });
    },
    updateSEO: function() {
        const content = tinymce.get('draft-content').getContent();
        const title = $('#draft-title').val();
        const description = $('#draft-description').val();
        const targetKeyword = $('#main-keyword-input').val();
        
        // topics/controllers
        return $.ajax({
            url: admin_url + 'topics/writing/get_seo_suggestions',
            type: 'POST',
            data: {
                content: content,
                title: title,
                description: description,
                target_keyword: targetKeyword
            }
        }).done(function(response) {
            try {
                console.log('SEO response:', response);
                response = JSON.parse(response);
                if (response.success) {
                    updateSEOAnalysisUI(response.analysis);
                }
            } catch (error) {
                console.error('Error updating SEO analysis:', error);
            }
        });
    }
}; 

// Add a helper function to safely create notifications with string messages
window.DraftWriter.safeNotify = function(message, options = {}) {
    $.notify({
        message: String(message || '')
    }, Object.assign({
        type: 'info',
        delay: 3000
    }, options));
};
</script> 