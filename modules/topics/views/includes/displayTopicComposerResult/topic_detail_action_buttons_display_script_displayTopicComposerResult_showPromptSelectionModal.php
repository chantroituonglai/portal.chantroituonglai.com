<?php defined('BASEPATH') or exit('No direct script access allowed');   ?>
<script>


function showPromptSelectionModal(content, field, onComplete, target) {
        console.log('showPromptSelectionModal', field, target);
         // Get content from TinyMCE editor
        let editorContent = '';
        if (field === 'content') {
            const $form = $('.item-edit-form');
            var _editorContent = tinymce.get($form.find('.content-editor')[0].id).getContent()
            if (_editorContent) {
                // editorContent = _editorContent.replace(/<br\s*\/?>/gi, '\n')
                //                           .replace(/<\/p>/gi, '\n')
                //                           .replace(/<[^>]+>/g, '')
                //                           .trim();
                editorContent = _editorContent;
            }
        } else {
            editorContent = content;
        }
        
        // Remove existing modal and event handlers
        const $existingModal = $('#prompt-selection-modal');
        if ($existingModal.length) {
            $existingModal.off('click', '.select-prompt-btn');
            $existingModal.remove();
        }

        // Determine field type and set default options
        let isRichTextEditor = false;
        let returnHtmlDefault = false; // Default to false for all field types
        
        // Check if target is provided and determine field type
        if (target) {
            const $target = $(target);
            const $closestField = $target.closest('.form-group').find('input, textarea, .tox-tinymce');
            console.log('target field detection:', $closestField);
            
            if ($closestField.length) {
                // Check if it's a rich text editor (TinyMCE)
                if ($closestField.hasClass('tox-tinymce') || 
                    $closestField.closest('.tox-tinymce').length || 
                    $closestField.hasClass('mce-content-body') ||
                    $closestField.closest('.mce-content-body').length) {
                    isRichTextEditor = true;
                    returnHtmlDefault = true;
                } else if ($closestField.is('input[type="text"]') || $closestField.is('textarea')) {
                    // Plain text inputs and textareas should have returnHtmlDefault = false
                    isRichTextEditor = false;
                    returnHtmlDefault = false;
                }
            }
        } else {
            // If no target, determine based on field name
            if (field === 'content') {
                isRichTextEditor = true;
                returnHtmlDefault = true;
            } else if (field === 'summary' || field === 'title' || field === 'name' || field === 'description') {
                isRichTextEditor = false;
                returnHtmlDefault = false;
            }
        }
        
        console.log('Field type detection:', { field, isRichTextEditor, returnHtmlDefault });

        const prompts = [
            {
                id: 'generate_from_content',
                title: '<?php echo _l("generate_from_content"); ?>',
                description: '<?php echo _l("generate_from_content_desc"); ?>',
                prompt: 'Generate new content based on the following text, maintaining key points while improving and expanding the content:'
            },
            {
                id: 'seo',
                title: '<?php echo _l("write_seo"); ?>',
                description: '<?php echo _l("write_seo_desc"); ?>',
                prompt: 'Tối ưu nội dung cho SEO, sử dụng từ khóa và cấu trúc phù hợp:'
            },
            {
                id: 'detailed',
                title: '<?php echo _l("write_detailed"); ?>',
                description: '<?php echo _l("write_detailed_desc"); ?>',
                prompt: 'Viết chi tiết và mở rộng nội dung sau, thêm các ví dụ và giải thích cụ thể:'
            },
            {
                id: 'concise',
                title: '<?php echo _l("write_concise"); ?>',
                description: '<?php echo _l("write_concise_desc"); ?>',
                prompt: 'Tóm tắt và viết ngắn gọn nội dung sau, giữ lại các ý chính quan trọng:'
            },
            {
                id: 'engaging',
                title: '<?php echo _l("write_engaging"); ?>',
                description: '<?php echo _l("write_engaging_desc"); ?>',
                prompt: 'Viết lại nội dung sau một cách hấp dẫn và cuốn hút hơn, sử dụng ngôn ngữ sinh động:'
            },
            {
                id: 'academic',
                title: '<?php echo _l("write_academic"); ?>',
                description: '<?php echo _l("write_academic_desc"); ?>',
                prompt: 'Viết lại theo phong cách học thuật, sử dụng ngôn ngữ chuyên ngành và trích dẫn:'
            },
            {
                id: 'simple',
                title: '<?php echo _l("write_simple"); ?>',
                description: '<?php echo _l("write_simple_desc"); ?>',
                prompt: 'Viết lại nội dung sau bằng ngôn ngữ đơn giản, dễ hiểu cho mọi người:'
            },
            {
                id: 'storytelling',
                title: '<?php echo _l("write_storytelling"); ?>',
                description: '<?php echo _l("write_storytelling_desc"); ?>',
                prompt: 'Viết lại dưới dạng câu chuyện, tạo tính kết nối và cảm xúc với người đọc:'
            },
            {
                id: 'professional',
                title: '<?php echo _l("write_professional"); ?>',
                description: '<?php echo _l("write_professional_desc"); ?>',
                prompt: 'Viết lại theo phong cách chuyên nghiệp, phù hợp với môi trường doanh nghiệp:'
            },
            {
                id: 'persuasive',
                title: '<?php echo _l("write_persuasive"); ?>',
                description: '<?php echo _l("write_persuasive_desc"); ?>',
                prompt: 'Viết lại theo hướng thuyết phục, tạo sự tin tưởng và kêu gọi hành động:'
            },
            {
                id: 'friendly',
                title: '<?php echo _l("write_friendly"); ?>',
                description: '<?php echo _l("write_friendly_desc"); ?>',
                prompt: 'Viết lại với giọng điệu thân thiện, gần gũi như trò chuyện với bạn bè:'
            },
           
            {
                id: 'creative',
                title: '<?php echo _l("write_creative"); ?>',
                description: '<?php echo _l("write_creative_desc"); ?>',
                prompt: 'Viết lại một cách sáng tạo, độc đáo và gây ấn tượng:'
            },
            {
                id: 'technical',
                title: '<?php echo _l("write_technical"); ?>',
                description: '<?php echo _l("write_technical_desc"); ?>',
                prompt: 'Viết theo hướng kỹ thuật, chi tiết về quy trình và thông số:'
            },
            {
                id: 'emotional',
                title: '<?php echo _l("write_emotional"); ?>',
                description: '<?php echo _l("write_emotional_desc"); ?>',
                prompt: 'Viết lại với nhiều cảm xúc, tạo sự đồng cảm và kết nối:'
            },
            {
                id: 'journalistic',
                title: '<?php echo _l("write_journalistic"); ?>',
                description: '<?php echo _l("write_journalistic_desc"); ?>',
                prompt: 'Viết theo phong cách báo chí, khách quan và cung cấp thông tin:'
            },
            {
                id: 'custom',
                title: '<?php echo _l("write_custom"); ?>',
                description: '<?php echo _l("write_custom_desc"); ?>',
                prompt: ''
            },
            {
                id: 'neutralize',
                title: '<?php echo _l("neutralize_content"); ?>',
                description: '<?php echo _l("neutralize_content_desc"); ?>',
                prompt: `Viết lại nội dung sau một cách trung lập và khách quan, giữ nguyên các thông tin chính và giá trị nội dung, nhưng:
                - Loại bỏ các đề cập trực tiếp đến thương hiệu cụ thể
                - Thay thế các so sánh trực tiếp bằng mô tả tính năng/đặc điểm
                - Tập trung vào lợi ích và giá trị cho người dùng
                - Sử dụng ngôn ngữ trung lập, không thiên vị
                - Giữ nguyên cấu trúc và luồng thông tin của bài viết
                - Đảm bảo tính chính xác của thông tin kỹ thuật và đặc điểm sản phẩm
                - Thay các câu quảng cáo bằng thông tin hữu ích
                Trả về kết quả là HTML với các đoạn được bọc trong thẻ p.`
            }
        ];

        // Get saved preferences from localStorage
        const savedPrefs = JSON.parse(localStorage.getItem('promptPreferences') || '{}');
        const promptUsage = savedPrefs.promptUsage || {};
        const lastWordLimits = savedPrefs.wordLimits || {};

        // Sort prompts by usage count
        const sortedPrompts = [...prompts].sort((a, b) => {
            const aCount = promptUsage[a.id] || 0;
            const bCount = promptUsage[b.id] || 0;
            return bCount - aCount;
        });

        const modalHtml = `
            <div class="modal fade" id="prompt-selection-modal" data-backdrop="false">
                <div class="modal-dialog modal-lg" style="z-index: 1060;">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                            <h4 class="modal-title"><?php echo _l('select_writing_style'); ?></h4>
                        </div>
                        <div class="modal-body">
                            <!-- Default Commands -->
                            <div class="default-commands mb-20">
                                <h5><?php echo _l('default_commands'); ?></h5>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="checkbox">
                                            <input type="checkbox" id="return-html" ${returnHtmlDefault ? 'checked' : ''}>
                                            <label for="return-html">
                                                <?php echo _l('always_return_html'); ?>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="checkbox">
                                            <input type="checkbox" id="no-markdown" checked>
                                            <label for="no-markdown">
                                                <?php echo _l('no_markdown_return'); ?>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="checkbox">
                                            <input type="checkbox" id="no-json" checked>
                                            <label for="no-json">
                                                <?php echo _l('no_json_return'); ?>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Word Limit Control -->
                            <div class="word-limit-control mb-20">
                                <label>
                                    <?php echo _l('max_word_limit'); ?>
                                    <span class="word-limit-display">${lastWordLimits[field] || 500}</span> <?php echo _l('words'); ?>
                                </label>
                                <div class="slider-container">
                                    <input type="range" class="word-limit-slider" 
                                        min="10" max="2000" step="1" 
                                        value="${lastWordLimits[field] || 500}">
                                    <div class="slider-input">
                                        <input type="number" class="form-control word-limit-input" 
                                            min="10" max="2000" 
                                            value="${lastWordLimits[field] || 500}">
                                    </div>
                                </div>
                            </div>


                            <div class="image-selection-section mt-3">
                                <div class="panel panel-default">
                                    <div class="panel-heading" style="cursor: pointer;" onclick="$(this).next().collapse('toggle')">
                                        <h4 class="panel-title">
                                            <i class="fa fa-image"></i> <?php echo _l('include_uploaded_images'); ?>
                                            <i class="fa fa-chevron-down pull-right"></i>
                                        </h4>
                                    </div>
                                    <div class="panel-collapse collapse">
                                        <div class="panel-body" id="uploaded-images-list">
                                            <div class="text-muted mb-2">
                                                <small><?php echo _l('select_images_to_include'); ?></small>
                                            </div>
                                            <div class="uploaded-images-container">
                                                <i class="fa fa-spinner fa-spin"></i> <?php echo _l('loading_images'); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="url-override-section mt-3">
                                <div class="panel panel-default">
                                    <div class="panel-heading" style="cursor: pointer;" onclick="$(this).next().collapse('toggle')">
                                        <h4 class="panel-title">
                                            <i class="fa fa-link"></i> <?php echo _l('url_override'); ?>
                                            <i class="fa fa-chevron-down pull-right"></i>
                                        </h4>
                                    </div>
                                    <div class="panel-collapse collapse">
                                        <div class="panel-body">
                                            <div class="text-muted mb-2">
                                                <small><?php echo _l('url_override_desc'); ?></small>
                                            </div>
                                            <div class="url-override-container">
                                                <i class="fa fa-spinner fa-spin"></i> <?php echo _l('scanning_urls'); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Controller Preview Section -->
                            <div class="controller-preview-section mt-3">
                                <div class="panel panel-default">
                                    <div class="panel-heading" style="cursor: pointer;" onclick="$(this).next().collapse('toggle')">
                                        <h4 class="panel-title">
                                            <i class="fa fa-cog"></i> <?php echo _l('controller_preview'); ?>
                                            <i class="fa fa-chevron-down pull-right"></i>
                                        </h4>
                                    </div>
                                    <div class="panel-collapse collapse" id="controller-preview-collapse">
                                        <div class="panel-body">
                                            <div id="controller-preview-content">
                                                <div class="text-muted mb-2">
                                                    <small><?php echo _l('select_controller_to_preview'); ?></small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Prompts Grid -->
                            <div class="row prompt-grid">
                                ${sortedPrompts.map(prompt => `
                                    <div class="col-md-6 mb-3">
                                        <div class="prompt-card card h-100" 
                                             data-id="${prompt.id}"
                                             data-usage="${promptUsage[prompt.id] || 0}">
                                            <div class="card-body">
                                                <h5 class="card-title">
                                                    ${prompt.title}
                                                    <span class="badge bg-info float-end">
                                                        ${promptUsage[prompt.id] || 0}
                                                    </span>
                                                </h5>
                                                <p class="card-text small">${prompt.description}</p>
                                                ${prompt.id === 'custom' ? `
                                                    <textarea class="form-control custom-prompt mb-2" 
                                                        rows="2" placeholder="<?php echo _l('enter_custom_prompt'); ?>"></textarea>
                                                ` : ''}
                                                <button class="btn btn-primary btn-sm select-prompt-btn">
                                                    <?php echo _l('use_this'); ?>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                `).join('')}
                            </div>

                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal"><?php echo _l('close'); ?></button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-backdrop fade in" 
                 id="prompt-selection-backdrop" 
                 style="z-index: 1050;"
                 data-related-modal="prompt-selection-modal"></div>
        `;

        // Add modal to body
        $('body').append(modalHtml);
        
        const $modal = $('#prompt-selection-modal');
        const $backdrop = $('.modal-backdrop').last();

        // Add custom styles
        const styleHtml = `
            <style>
                #prompt-selection-modal {
                    background: rgba(0, 0, 0, 0.5);
                }
                #prompt-selection-modal .modal-dialog {
                    margin-top: 100px;
                }
                #prompt-selection-modal.fade.in {
                    opacity: 1;
                }
                .modal-backdrop.fade.in {
                    opacity: 0.5;
                }
            </style>
        `;
        $('head').append(styleHtml);

        // Setup word limit controls
        const $slider = $modal.find('.word-limit-slider');
        const $input = $modal.find('.word-limit-input');
        const $display = $modal.find('.word-limit-display');

        // Sync slider and input
        $slider.on('input', function() {
            const value = $(this).val();
            $input.val(value);
            $display.text(value);
        });

        $input.on('change', function() {
            let value = parseInt($(this).val());
            // Validate input range

            $(this).val(value);
            $slider.val(value);
            $display.text(value);
        });

        // Handle prompt card click (for word limit)
        $modal.on('click', '.prompt-card', function(e) {
            if (!$(e.target).hasClass('select-prompt-btn')) {
                const promptId = $(this).data('id');
                const savedLimit = lastWordLimits[`${field}_${promptId}`] || lastWordLimits[field] || 500;
                $slider.val(savedLimit);
                $input.val(savedLimit);
                $display.text(savedLimit);
            }
        });

        // Handle prompt selection
        $modal.on('click', '.select-prompt-btn', function() {
            const $card = $(this).closest('.prompt-card');
            const promptId = $card.data('id');
            const wordLimit = parseInt($slider.val());

            // Update usage count
            promptUsage[promptId] = (promptUsage[promptId] || 0) + 1;

            // Save word limit for this field and prompt
            lastWordLimits[field] = wordLimit;
            lastWordLimits[`${field}_${promptId}`] = wordLimit;

            // Save to localStorage
            localStorage.setItem('promptPreferences', JSON.stringify({
                promptUsage,
                wordLimits: lastWordLimits
            }));

            console.log('1- select-prompt-btn', promptId, prompts);
            var promptText = prompts.find(p => p.id === promptId).prompt;
            promptText += `. Giới hạn độ dài kết quả trong khoảng ${wordLimit} từ Tiếng Việt.`;

            // Add selected default commands
            const defaultCommands = [];
            if ($('#return-html').is(':checked')) {
                defaultCommands.push('Luôn luôn trả về kết quả là HTML');
            } else {
                defaultCommands.push('Trả về nội dung dạng text thuần không có bất kỳ thẻ HTML, markdown hay định dạng nào khác. Không bao gồm thẻ <p>, <br> hay bất kỳ thẻ HTML nào trong kết quả');
            }
            if ($('#no-markdown').is(':checked')) {
                defaultCommands.push('không trả về markdown');
            } else {
                defaultCommands.push('trả về markdown');
            }
            if ($('#no-json').is(':checked')) {
                defaultCommands.push('không trả về json, chỉ trả về đúng kết quả');
            }

            if (defaultCommands.length > 0) {
                promptText += '. ' + defaultCommands.join(', ');
            }
            if (promptId === 'generate_from_content') {
                const $form = $('.item-edit-form');
                if ($form.length > 0) {
                    const $editor = $form.find('.content-editor');
                    if ($editor.length > 0 && tinymce.get($editor[0].id)) {
                        var _editorContent = tinymce.get($editor[0].id).getContent();
                        console.log('1- _editorContent', _editorContent);
                        if (_editorContent) {
                            editorContent = _editorContent.replace(/<br\s*\/?>/gi, '\n')
                                                      .replace(/<\/p>/gi, '\n')
                                                      .replace(/<[^>]+>/g, '')
                                                      .trim();
                        }
                    }
                }
            }

            try {
                // Get URL overrides
                const urlOverrides = [];
                $('.url-override-input').each(function () {
                    const $input = $(this);
                    const originalUrl = $input.data('original');
                    const overrideUrl = $input.val().trim();

                    // Only include if there's an override value and input is disabled (means it's been shortened)
                    if (overrideUrl && $input.prop('disabled')) {
                        urlOverrides.push({
                            original: originalUrl,
                            override: overrideUrl
                        });
                    }
                });
            
                // Apply URL overrides to content
                let modifiedContent = getModifiedContent(editorContent, urlOverrides);
                if (urlOverrides.length > 0) {
                    const warnings = checkUnhandledWarnings();
                    const confirmMessage = formatUrlOverridesMessage(urlOverrides) + warnings;

                    if (confirm(confirmMessage)) {
                        editorContent = modifiedContent;
                    }else {
                        return
                    }
                }
            } catch (e) {
                console.error('Error getting URL overrides:', e);
            }

            promptText += '. Content: ' + editorContent;

            // Bổ sung thông tin controller vào prompt nếu có
            if (window.TopicComposer && window.TopicComposer.selectedController) {
                const controller = window.TopicComposer.selectedController;
                
                // Thêm thông tin controller vào prompt
                promptText += '\n\n--- Controller Information ---';
                
                if (controller.site) {
                    promptText += `\nSite: ${controller.site}`;
                }
                
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
                
                console.log('Added controller information to prompt:', controller.site);
            }

             // Get selected images
            const selectedImages = [];
                $('.uploaded-image-checkbox:checked').each(function() {
                    selectedImages.push($(this).data('url'));
                });

            // Modify prompt if images selected
            if (selectedImages.length > 0) {
                promptText += '\n\nPlease include these images in appropriate places in the content:\n';
                selectedImages.forEach(url => {
                    promptText += `- ${url}\n`;
                });
            }

            console.log('2- promptText', promptText);
            $modal.modal('hide');

            // Prepare UI elements
            const $fieldWrapper = field === 'content' ? 
                $('.tox-tinymce').closest('.mce-container') : 
                $(`[name="${field}"]`).parent();
            
            const $field = field === 'content' ? 
                tinymce.get($('.item-edit-form').find('.content-editor')[0].id) : 
                $(`[name="${field}"]`);

            // Disable field and show loading
            if (field === 'content' && $field) {
                $field.getBody().contentEditable = false;
                $($field.getContainer()).addClass('mce-disabled');
                $($field.getContainer()).addClass('mce-loading');
            } else {
                $field.prop('disabled', true);
            }
            
            $fieldWrapper.addClass('ai-processing');

            console.log('2- callAIEditAPI');
            // Call shared API function with unified handling
            callAIEditAPI(editorContent, field, promptText, function(output, configData) {
                let processedOutput = output;
                console.log('3- processedOutput > return-html', configData);
                const { returnHtml, noMarkdown, noJson } = configData;
                // Nếu không check return HTML
                if (!returnHtml) {
                    // Strip all HTML tags
                    processedOutput = output.replace(/<[^>]*>/g, '')
                        // Replace HTML entities
                        .replace(/&nbsp;/g, ' ')
                        .replace(/&amp;/g, '&')
                        .replace(/&lt;/g, '<')
                        .replace(/&gt;/g, '>')
                        .replace(/&quot;/g, '"')
                        // Replace multiple spaces/newlines
                        .replace(/\s+/g, ' ')
                        .trim();
                        
                    // Add proper line breaks for readability
                    processedOutput = processedOutput
                        .split(/[.!?](?=\s|$)/)
                        .filter(sentence => sentence.trim())
                        .join('.\n\n')
                        .trim();
                        
                } else {
                    // Existing HTML processing logic
                    let isHtml = /<[a-z][\s\S]*>/i.test(output);
                    if (!isHtml) {
                        // Process both JSON and Markdown simultaneously
                        let jsonContent = '';
                        let markdownContent = '';

                        // Try parse JSON
                        try {
                            const jsonData = JSON.parse(output);
                            if (typeof jsonData === 'object') {
                                // Process each property
                                jsonContent = Object.values(jsonData)
                                    .map(value => {
                                        if (typeof value === 'string') {
                                            // Check if value contains HTML
                                            if (/<[^>]*>/g.test(value)) {
                                                return value; // Keep HTML as is
                                            }
                                            // Check if value is markdown
                                            if (/[*#_`]/.test(value)) {
                                                return convertMarkdownToHtml(value);
                                            }
                                            return `<p>${value}</p>`; // Wrap plain text in p tags
                                        }
                                        return `<p>${JSON.stringify(value)}</p>`;
                                    })
                                    .filter(text => text && text.trim())
                                    .join('\n');
                            }
                        } catch (e) {
                            // Not valid JSON, try markdown
                            if (/[*#_`]/.test(output)) {
                                markdownContent = convertMarkdownToHtml(output);
                            }
                        }

                        // Choose the best processed content
                        if (jsonContent) {
                            processedOutput = jsonContent;
                        } else if (markdownContent) {
                            processedOutput = markdownContent;
                        } else {
                            // If neither JSON nor Markdown, wrap in p tags
                            processedOutput = `<p>${output}</p>`;
                        }
                    }
                }

                // Helper function to convert markdown to HTML
                function convertMarkdownToHtml(markdown) {
                    try {
                        if (typeof marked !== 'undefined') {
                            return marked(markdown);
                        }
                        // Basic markdown conversion
                        return markdown
                            // Headers
                            .replace(/^### (.*$)/gim, '<h3>$1</h3>')
                            .replace(/^## (.*$)/gim, '<h2>$1</h2>')
                            .replace(/^# (.*$)/gim, '<h1>$1</h1>')
                            // Bold
                            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                            // Italic
                            .replace(/\*(.*?)\*/g, '<em>$1</em>')
                            // Lists
                            .replace(/^\s*\n\*/gm, '<ul>\n*')
                            .replace(/^(\*.+)\s*\n([^\*])/gm, '$1\n</ul>\n$2')
                            .replace(/^\*(.+)/gm, '<li>$1</li>')
                            // Line breaks
                            .replace(/\n/g, '<br>');
                    } catch (e) {
                        console.warn('Error converting markdown:', e);
                        return `<p>${markdown}</p>`;
                    }
                }

                // Update field with processed output
                if (field === 'content' && $field) {
                    if ($('#return-html').is(':checked')) {
                        $field.setContent(processedOutput);
                    } else {
                        // For plain text, wrap in pre tag to preserve formatting
                        $field.setContent(`<pre style="white-space: pre-wrap;">${processedOutput}</pre>`);
                    }
                } else {
                    $field.val(processedOutput);
                }
                
                // Notify completion
                if (onComplete) {
                    onComplete(processedOutput);
                }
            }, {
                returnHtml: $('#return-html').is(':checked'),
                noMarkdown: $('#no-markdown').is(':checked'),
                noJson: $('#no-json').is(':checked')
            }).always(function() {
                // Cleanup
                if (field === 'content' && $field) {
                    $field.getBody().contentEditable = true;
                    $($field.getContainer()).removeClass('mce-disabled');
                    $($field.getContainer()).removeClass('mce-loading');
                } else {
                    $field.prop('disabled', false);
                }
                
                $fieldWrapper.removeClass('ai-processing');
            });
        });

        // Show modal
        $modal.modal({
            backdrop: false,
            keyboard: false
        });

        // Adjust backdrop when showing
        $modal.on('shown.bs.modal', function() {
            $backdrop.addClass('in');
            $modal.addClass('in');
        });

        // Clean up on modal close
        $modal.on('hidden.bs.modal', function() {
            // Xóa backdrop cụ thể của prompt modal
            $('#prompt-selection-backdrop').remove();
            
            // Xóa modal và các event handlers
            $(this)
                .off('hidden.bs.modal')
                .off('click', '.select-prompt-btn')
                .remove();
            
            // Khôi phục trạng thái backdrop cho modal cha (nếu có)
            const $parentModal = $('#topic-composer-modal');
            if ($parentModal.length > 0 && $parentModal.css('display') === 'block') {
                $('body').addClass('modal-open');
                $parentModal.css('display', 'block');
            }
            
            // Remove all ai-processing classes when closing the prompt modal
            $('.ai-processing').removeClass('ai-processing');
            
            // Re-enable any disabled editors
            $('textarea.content-editor').each(function() {
                const editorId = $(this).attr('id');
                if (tinymce.get(editorId)) {
                    const editor = tinymce.get(editorId);
                    editor.getBody().contentEditable = true;
                    $(editor.getContainer()).removeClass('mce-disabled');
                    $(editor.getContainer()).removeClass('mce-loading');
                }
            });
            
            // Re-enable any disabled inputs/textareas
            $('input[type="text"], textarea').prop('disabled', false);
            
            // Remove any loading indicators
            $('.ai-edit-btn').prop('disabled', false).html('<i class="fa fa-magic"></i> <?php echo _l('ai_edit'); ?>');
            
            // Additional cleanup for any visual indicators
            $('.mce-container.mce-disabled, .mce-container.mce-loading').removeClass('mce-disabled mce-loading');
        });

        // Thêm timeout để đảm bảo xóa backdrop
        setTimeout(() => {
            // $('.modal-backdrop').not('#prompt-selection-backdrop').remove();
            $('#prompt-selection-backdrop').remove();
        }, 300);

        // Thêm logic xử lý khi click outside
        $('#prompt-selection-backdrop').on('click', function() {
            $modal.modal('hide');
        });

        // Add styles for usage badge
        const badgeStyles = `
            <style>
                .prompt-card {
                    cursor: pointer;
                    transition: all 0.2s ease;
                }
                .prompt-card:hover {
                    border-color: #80bdff;
                    box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
                }
                .badge {
                    font-size: 12px;
                    padding: 3px 6px;
                    background-color: #17a2b8;
                    color: white;
                    border-radius: 10px;
                }
            </style>
        `;
        $('head').append(badgeStyles);

        // Load chậm các chức năng/code
        loadUploadedImages();
        loadUrlOverrideTable(editorContent);


        // Add event handlers for controller preview
        $(document).on('shown.bs.modal', '#prompt-selection-modal', function() {
            updateControllerPreview();
            
            // Show controller preview if controller is selected
            if (window.TopicComposer && window.TopicComposer.selectedController) {
                setTimeout(function() {
                    $('#controller-preview-collapse').collapse('show');
                }, 500);
            }
        });
        
        // Update preview when prompt is selected
        $(document).on('click', '.select-prompt-btn', function() {
            updateControllerPreview();
        });
    }

    // Function to load uploaded images
    function loadUploadedImages() {
        const $container = $('.uploaded-images-container');
        
        // Get all images with external data
        const images = [];
        $('.image-item img[data-original-url]').each(function() {
            const $img = $(this);
            images.push({
                originalUrl: $img.data('original-url'),
                currentUrl: $img.attr('src'),
                filename: $img.closest('.image-item').find('.caption').text().trim()
            });
        });

        if (images.length === 0) {
            $container.html(`
                <div class="text-muted">
                    <?php echo _l('no_uploaded_images'); ?>
                </div>
            `);
            return;
        }

        // Generate checkboxes for images
        let html = '<div class="row">';
        images.forEach((img, index) => {
            html += `
                <div class="col-md-6 mb-2">
                    <div class="checkbox-image-item">
                        <label class="d-flex align-items-center">
                            <input type="checkbox" 
                                   class="uploaded-image-checkbox mr-2"
                                   data-url="${img.currentUrl}">
                            <div class="image-preview-small mr-2">
                                <img src="${img.currentUrl}" 
                                     class="img-responsive"
                                     style="max-height: 40px;">
                            </div>
                            <small class="text-muted">${img.filename}</small>
                        </label>
                    </div>
                </div>
            `;
        });
        html += '</div>';

        $container.html(html);
    }

    function scanUrlsFromContent(content) {
        const urlRegex = /((https?:\/\/)?([\w-]+\.)+[\w-]{2,}(\/[^\s<>"]*)?)/g;
        const matches = content.match(urlRegex) || [];
        return [...new Set(matches)];
    }

    // Add new functions before loadUrlOverrideTable
    function checkUrlExternalData(originalUrl, topicMasterId) {
        return new Promise((resolve) => {
            if (!originalUrl || !topicMasterId) {
                resolve({ exists: false });
                return;
            }

            const rel_id = window.TopicComposer.handlers.md5(originalUrl);
                
                $.ajax({
                url: admin_url + 'topics/check_image_external_data',
                type: 'POST',
                data: {
                    topic_master_id: topicMasterId,
                    rel_id: rel_id,
                    rel_type: 'link',
                },
                success: function(response) {
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
                error: function() {
                    resolve({ exists: false });
                }
            });
        });
    }

    function shortenUrl(originalUrl, overrideUrl) {
        return new Promise((resolve, reject) => {
            const data = {
                target_url: overrideUrl,
                name: `${window.TopicComposer.handlers.md5(originalUrl)} | ${topicMasterId} ${topicMasterTitle}`,
                description: `Link rút gọn cho ${topicMasterId} ${topicMasterTitle}`,
                track_me: true,
                nofollow: true,
                topicid: topicCurrentId,
                topic_title: topicMasterTitle,
                topic_master_id: topicMasterId
            };

            $.ajax({
                url: 'https://automate.chantroituonglai.com/webhook/a2f51b4a-af5e-42ba-9c0f-88368a056a39',
                type: 'POST',
                data: JSON.stringify(data),
                contentType: 'application/json',
                success: function(response) {
                    if (response.success && response.data) {
                        // Save to external data
                        const externalData = {
                            topic_master_id: topicMasterId,
                            rel_type: 'link',
                            rel_id: window.TopicComposer.handlers.md5(originalUrl),
                            rel_data: response.data.url, // Shortened URL
                            rel_data_raw: JSON.stringify(response) // Full response
                        };

                        $.ajax({
                            url: admin_url + 'topics/save_external_data',
                            type: 'POST',
                            data: externalData,
                            success: function(saveResponse) {
                                try {
                                    if (typeof saveResponse === 'string') {
                                        saveResponse = JSON.parse(saveResponse);
                                    }
                                    if (saveResponse.success) {
                                        resolve({
                                            success: true,
                                            short_url: response.data.url,
                                            data: response.data
                                        });
                                    } else {
                                        throw new Error(saveResponse.message || '<?php echo _l('failed_to_save_external_data'); ?>');
                                    }
                                } catch (e) {
                                    reject(e);
                                }
                            },
                            error: function(xhr, status, error) {
                                reject(new Error('<?php echo _l('failed_to_save_external_data'); ?>'));
                            }
                        });
                    } else {
                        reject(new Error(response.message || '<?php echo _l('shortening_failed'); ?>'));
                    }
                },
                error: function(xhr, status, error) {
                    reject(error);
                }
            });
        });
    }

    // Add helper function to normalize URL for comparison
    function normalizeUrl(url) {
        return url.toLowerCase()
                  .normalize('NFD')
                  .replace(/[\u0300-\u036f]/g, '') // Remove diacritics
                  .replace(/[^\w\-\.\/:]/g, '');    // Remove special chars except common URL chars
    }

    // Modify loadUrlOverrideTable function
    async function loadUrlOverrideTable(content) {
        console.log('3- loadUrlOverrideTable', content);
        const $container = $('.url-override-container');
        const urls = scanUrlsFromContent(content);
        console.log('4- urls', urls);

        if (urls.length === 0) {
            $container.html(`
                <div class="text-muted">
                    <?php echo _l('no_urls_found'); ?>
                </div>
            `);
            return;
        }

        // Show loading state
        $container.html(`
            <div class="text-center">
                <i class="fa fa-spinner fa-spin"></i> <?php echo _l('checking_urls'); ?>
            </div>
        `);

        // Check external data for all URLs first
        const urlData = await Promise.all(urls.map(async url => {
            const result = await checkUrlExternalData(url, topicMasterId);
            return {
                url,
                externalData: result
            };
        }));

        let html = `
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th><?php echo _l('original_url'); ?></th>
                            <th><?php echo _l('override_url'); ?></th>
                            <th width="100"><?php echo _l('actions'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
        `;

        urlData.forEach(({url, externalData}, index) => {
            console.log('5- urlData', url);
            console.log('5- externalData', externalData);
            html += `
                <tr data-url-index="${index}">
                    <td data-label="<?php echo _l('original_url'); ?>">
                        <div class="url-cell">
                            <span class="url-text">${url}</span>
                            <button type="button" class="btn btn-xs btn-default copy-url-btn" 
                                    data-url="${url}" title="<?php echo _l('copy_url'); ?>">
                                <i class="fa fa-copy"></i>
                            </button>
                        </div>
                    </td>
                    <td data-label="<?php echo _l('override_url'); ?>">
                        <div class="url-override-input-group">
                            <input type="text" class="form-control url-override-input" 
                                   data-original="${url}" 
                                   placeholder="<?php echo _l('enter_override_url'); ?>"
                                   ${externalData.exists ? 'disabled value="' + externalData.data + '"' : ''}>
                            <button type="button" class="btn btn-info btn-sm shorten-url-btn" 
                                    style="display:${externalData.exists ? 'none' : 'none'}"
                                    ${externalData.exists ? 'disabled' : ''}>
                                <i class="fa fa-link"></i> <?php echo _l('shorten_url'); ?>
                            </button>
                        </div>
                    </td>
                    <td data-label="<?php echo _l('status'); ?>" class="url-status" data-url="${url}">
                        ${externalData.exists ? `
                            <span class="label label-success">
                                <i class="fa fa-check"></i> <?php echo _l('shortened'); ?>
                            </span>
                        ` : ''}
                    </td>
                </tr>
            `;
        });

        html += `
                    </tbody>
                </table>
            </div>
        `;

        $container.html(html);

        // Add event handlers
        $container.find('.copy-url-btn').click(function() {
            const url = $(this).data('url');
            const $temp = $("<input>");
            $("body").append($temp);
            $temp.val(url).select();
            try {
                document.execCommand("copy");
                alert_float('success', '<?php echo _l('url_copied_to_clipboard'); ?>');
            } catch (e) {
                alert_float('danger', '<?php echo _l('failed_to_copy_url'); ?>');
            }
            $temp.remove();
        });

        // Add shorten handlers only for URLs without external data
        urlData.forEach(({url, externalData}, index) => {
            console.log('6- urlData', $container);
            console.log(url);
            console.log('6- externalData', externalData);
            if (!externalData.exists) {
                // Find row using data attribute instead of text content
                const $row = $container.find(`tr[data-url-index="${index}"]`);
                console.log('7- $row', $row);
                const $status = $row.find('.url-status');
                const $shortenBtn = $row.find('.shorten-url-btn');
                const $overrideInput = $row.find('.url-override-input');

                $overrideInput.on('input', function() {
                    const hasValue = $(this).val().trim().length > 0;
                    $shortenBtn.toggle(hasValue);
                });

                $shortenBtn.click(function() {
                    const $btn = $(this);
                    const overrideUrl = $overrideInput.val().trim();
                    
                    $btn.prop('disabled', true)
                        .html('<i class="fa fa-spinner fa-spin"></i>');

                    shortenUrl(url, overrideUrl)
                        .then(response => {
                            if (response.success) {
                                $status.html(`
                                    <span class="label label-success">
                                        <i class="fa fa-check"></i> <?php echo _l('shortened'); ?>
                                    </span>
                                `);
                                $overrideInput.val(response.short_url).prop('disabled', true);
                                $btn.hide();
                            } else {
                                throw new Error(response.message || '<?php echo _l('shortening_failed'); ?>');
                            }
                        })
                        .catch(error => {
                            alert_float('danger', error.message);
                            $btn.prop('disabled', false)
                                .html('<i class="fa fa-link"></i> <?php echo _l('shorten_url'); ?>');
                        });
                });
            }
        });
    }

    // Update getModifiedContent to handle HTML content
    function getModifiedContent(content, overrides) {
        if (!overrides || !overrides.length) return content;

        let modifiedContent = content;
        overrides.forEach(({original, override}) => {
            if (original && override) {
                // Escape special characters in URL for regex
                const escapedOriginal = original.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                
                // Create regex that matches URL in various contexts
                const urlRegex = new RegExp(
                    `(href=["']?)${escapedOriginal}|` + // For href attributes
                    `(src=["']?)${escapedOriginal}|` +  // For src attributes
                    `(?<=[\\s>])${escapedOriginal}(?=[\\s<])`, // For plain text URLs
                    'g'
                );

                modifiedContent = modifiedContent.replace(urlRegex, (match, p1, p2) => {
                    // If matched in attribute, preserve the attribute part
                    if (p1) return p1 + override;
                    if (p2) return p2 + override;
                    return override;
                });
            }
        });

        return modifiedContent;
    }

    // Add function to format URL overrides message
    function formatUrlOverridesMessage(urlOverrides) {
        if (!urlOverrides.length) return '';

        let message = `Xác nhận ghi đè ${urlOverrides.length} liên kết:\n\n`;
        
        urlOverrides.forEach((override, index) => {
            message += `${index + 1}. ${override.original} -> ${override.override}\n`;
        });

        return message;
    }

    // Add function to check for unhandled items
    function checkUnhandledWarnings() {
        const warnings = [];
        
        // Check for unoverridden URLs
        const $unoverriddenUrls = $('.url-override-input:not(:disabled)');
        if ($unoverriddenUrls.length > 0) {
            warnings.push(`${$unoverriddenUrls.length} liên kết chưa được ghi đè`);
        }

        // Check for unselected images
        const $unselectedImages = $('.uploaded-image-checkbox:not(:checked)');
        if ($unselectedImages.length > 0) {
            warnings.push(`${$unselectedImages.length} hình ảnh chưa được chọn`);
        }

        return warnings.length ? '\nLưu ý: ' + warnings.join(', ') : '';
    }

    // Function to update controller preview
    function updateControllerPreview() {
        const $previewContent = $('#controller-preview-content');
        
        if (!window.TopicComposer || !window.TopicComposer.selectedController) {
            $previewContent.html(`
                <div class="alert alert-info">
                    <i class="fa fa-info-circle"></i> <?php echo _l('no_controller_selected'); ?>
                </div>
            `);
            return;
        }
        
        const controller = window.TopicComposer.selectedController;
        let previewHtml = `
            <div class="controller-preview">
                <div class="panel panel-info">
                    <div class="panel-heading">
                        <h4 class="panel-title">${escapeHtml(controller.site || '')}</h4>
                    </div>
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-12">
                                <p><strong>Platform:</strong> <span class="platform-badge platform-${(controller.platform || 'website').toLowerCase()}">${escapeHtml(controller.platform || 'Website')}</span></p>
                                ${controller.slogan ? `<p><strong>Slogan:</strong> ${escapeHtml(controller.slogan)}</p>` : ''}
                            </div>
                        </div>
        `;
        
        // Add action_1 (Writing Requirements) if available
        if (controller.action_1) {
            previewHtml += `
                <div class="writing-requirements mtop15">
                    <h5><strong><i class="fa fa-pencil"></i> Writing Requirements</strong></h5>
                    <div class="well well-sm">
                        ${controller.action_1}
                    </div>
                </div>
            `;
        } else if (controller.writing_style) {
            previewHtml += `
                <div class="writing-style-preview mtop15">
                    <h5><strong><i class="fa fa-pencil"></i> Writing Style</strong></h5>
                    <div class="well well-sm">
                        ${controller.writing_style}
                    </div>
                </div>
            `;
        }
        
        // Add action_2 (Additional Instructions) if available
        if (controller.action_2) {
            previewHtml += `
                <div class="additional-instructions mtop15">
                    <h5><strong><i class="fa fa-info-circle"></i> Additional Instructions</strong></h5>
                    <div class="well well-sm">
                        ${controller.action_2}
                    </div>
                </div>
            `;
        }
        
        previewHtml += `
                    </div>
                </div>
            </div>
        `;
        
        $previewContent.html(previewHtml);
    }

    // Helper function to escape HTML
    function escapeHtml(text) {
        if (!text) return '';
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.toString().replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    // Add event handlers for controller preview
    $(document).on('shown.bs.modal', '#prompt-selection-modal', function() {
        updateControllerPreview();
        
        // Show controller preview if controller is selected
        if (window.TopicComposer && window.TopicComposer.selectedController) {
            setTimeout(function() {
                $('#controller-preview-collapse').collapse('show');
            }, 500);
        }
    });
    
    // Update preview when prompt is selected
    $(document).on('click', '.select-prompt-btn', function() {
        updateControllerPreview();
    });

</script>