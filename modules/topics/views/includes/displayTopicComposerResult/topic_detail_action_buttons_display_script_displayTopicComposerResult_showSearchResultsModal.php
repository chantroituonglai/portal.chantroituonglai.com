<?php defined('BASEPATH') or exit('No direct script access allowed');   ?>
<script>

function showAISearchModal(content, field, callback) {
        console.log('showAISearchModal', content, field);
        const sampleQuestions = [
            {
                id: 'verify',
                title: '<?php echo _l("verify_content"); ?>',
                description: '<?php echo _l("verify_content_desc"); ?>',
                question: 'Xác nhận lại nội dung này có chính xác không?'
            },
            {
                id: 'fact_check',
                title: '<?php echo _l("fact_check"); ?>',
                description: '<?php echo _l("fact_check_desc"); ?>',
                question: 'Những thông tin trong nội dung này có đúng không?'
            },
            {
                id: 'find_source',
                title: '<?php echo _l("find_source"); ?>',
                description: '<?php echo _l("find_source_desc"); ?>',
                question: 'Có tài liệu hoặc nguồn nào đề cập đến nội dung này không?'
            },
            {
                id: 'similar',
                title: '<?php echo _l("find_similar"); ?>',
                description: '<?php echo _l("find_similar_desc"); ?>',
                question: 'Tìm các nội dung tương tự về chủ đề này'
            },
            {
                id: 'references',
                title: '<?php echo _l("find_references"); ?>',
                description: '<?php echo _l("find_references_desc"); ?>',
                question: 'Tìm các tài liệu tham khảo liên quan đến chủ đề này'
            },
            {
                id: 'expert',
                title: '<?php echo _l("expert_opinion"); ?>',
                description: '<?php echo _l("expert_opinion_desc"); ?>',
                question: 'Ý kiến chuyên gia về vấn đề này là gì?'
            },
            {
                id: 'custom',
                title: '<?php echo _l("custom_question"); ?>',
                description: '<?php echo _l("custom_question_desc"); ?>',
                question: ''
            }
        ];

        const modalHtml = `
            <div class="modal fade" id="ai-search-modal">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                          <!--  <h4 class="modal-title"><?php echo _l('ai_search'); ?></h4> -->
                        </div>
                        <div class="modal-body">
                            <!-- Sample Questions -->
                            <div class="sample-questions mb-20">
                                <label><?php echo _l('sample_questions'); ?></label>
                                <div class="row">
                                    ${sampleQuestions.map(q => `
                                        <div class="col-md-6">
                                            <div class="question-card" data-id="${q.id}">
                                                <h5>${q.title}</h5>
                                                <p class="text-muted">${q.description}</p>
                                                ${q.id === 'custom' ? `
                                                    <textarea class="form-control custom-question" 
                                                        placeholder="<?php echo _l('enter_custom_question'); ?>"
                                                    ></textarea>
                                                ` : `
                                                    <button type="button" class="btn btn-block btn-info use-question-btn" 
                                                            data-question="${q.question}">
                                                        <i class="fa fa-search"></i> <?php echo _l('use_this_question'); ?>
                                                    </button>
                                                `}
                                            </div>
                                        </div>
                                    `).join('')}
                                </div>
                            </div>

                            <!-- Search Form -->
                            <div class="search-form">
                                <div class="form-group">
                                    <label><?php echo _l('your_question'); ?></label>
                                    <textarea class="form-control" id="ai-search-question" rows="3"
                                        placeholder="<?php echo _l('enter_search_question'); ?>"></textarea>
                                </div>
                                <button type="button" class="btn btn-info search-btn">
                                    <i class="fa fa-search"></i> <?php echo _l('search'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Remove existing modal if any
        $('#ai-search-modal').remove();
        
        // Add and show new modal
        $('body').append(modalHtml);
        const $modal = $('#ai-search-modal');
        $modal.modal('show');

        // Handle sample question selection
        $modal.on('click', '.use-question-btn', function() {
            const question = $(this).data('question');
            $('#ai-search-question').val(question);
        });

        // Handle search
        $modal.on('click', '.search-btn', function() {
            const question = $('#ai-search-question').val().trim();
            if (!question) {
                alert_float('warning', '<?php echo _l("please_enter_question"); ?>');
                return;
            }

            const $btn = $(this);
            $btn.prop('disabled', true)
                .html('<i class="fa fa-spinner fa-spin"></i> <?php echo _l("searching"); ?>');

            // Call AI Search webhook
            $.ajax({
                url: 'https://automate.chantroituonglai.com/webhook/AISEARCH',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({
                    content: content,
                    field: field,
                    question: question
                }),
                success: function(response) {
                    if (response && response.success) {
                        $modal.modal('hide');
                        showSearchResultsModal(response.data, field, callback);
                    } else {
                        alert_float('warning', response.message || '<?php echo _l("ai_search_error"); ?>');
                    }
                },
                error: function() {
                    alert_float('danger', '<?php echo _l("ai_service_error"); ?>');
                },
                complete: function() {
                    $btn.prop('disabled', false)
                        .html('<i class="fa fa-search"></i> <?php echo _l("search"); ?>');
                }
            });
        });
    }

    
     function showSearchResultsModal(results, field, $targetField) {
        const modalHtml = `
            <div class="modal fade" id="ai-search-results-modal">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                            <h4 class="modal-title"><?php echo _l('search_results'); ?></h4>
                        </div>
                        <div class="modal-body">
                            <div class="search-results-list">
                                ${results.map((result, index) => `
                                    <div class="search-result-item">
                                        <h5>${result.title}</h5>
                                        <div class="result-content">${result.content}</div>
                                        <button type="button" class="btn btn-xs btn-success use-result-btn" 
                                                data-index="${index}">
                                            <i class="fa fa-check"></i> <?php echo _l('use_this'); ?>
                                        </button>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Remove existing modal if any
        $('#ai-search-results-modal').remove();
        
        // Add and show new modal
        $('body').append(modalHtml);
        const $modal = $('#ai-search-results-modal');
        $modal.modal('show');

        // Handle result selection
        $modal.on('click', '.use-result-btn', function() {
            const index = $(this).data('index');
            const selectedResult = results[index];
            
            if (field === 'content') {
                tinymce.get($targetField[0].id).setContent(selectedResult.content);
            } else {
                $targetField.val(selectedResult.title);
            }
            
            window.TopicComposer.handlers.markAsChanged();
            $modal.modal('hide');
            alert_float('success', '<?php echo _l('content_updated'); ?>');
        });
    }
</script>