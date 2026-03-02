<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">

            <?php
            $submitUrl = admin_url('publishx/post');
            if (isset($post_data)) {
                $submitUrl = admin_url('publishx/post/' . $post_data->id);
            }
            ?>
            <?php echo form_open_multipart($submitUrl, ['id' => 'publishx-form']); ?>
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="tw-mt-0 tw-font-semibold tw-text-lg tw-text-neutral-700">
                            <?php echo $title; ?>
                        </h4>
                        <hr>
                        <div class="col-md-6">

                            <?php if (isset($post_data->featured_image) && $post_data->featured_image != '') {
                                ?>
                                <div class="row">
                                    <div class="col-md-9">
                                        <img src="<?php echo substr(module_dir_url('publishx/uploads/posts/' . $post_data->id . '/' . $post_data->featured_image), 0, -1); ?>"
                                             class="img img-responsive">
                                    </div>
                                    <div class="col-md-3 text-right">
                                        <a href="<?php echo admin_url('publishx/remove_post_featured_image/' . $post_data->id); ?>"
                                           class="_delete text-danger"><i class="fa fa-remove"></i></a>
                                    </div>
                                </div>
                                <div class="clearfix"></div>
                            <?php } else { ?>
                                <div class="form-group">
                                    <label for="featured_image"
                                           class="control-label"><?php echo _l('publishx_upload_feature_image'); ?></label>
                                    <input type="file" name="featured_image" class="form-control">
                                </div>
                            <?php } ?>

                            <hr class="hr-panel-separator"/>

                            <?php echo render_input('post_title', 'publishx_tbl_post_title', $post_data->post_title ?? '') ?>
                            <button onclick="generateAIContent($(this), 'short_content')"
                                    class="pull-right btn btn-outline-primary">
                                <i class="fas fa-robot"></i> AI
                            </button>
                            <br>
                            <?php echo render_textarea('short_content', 'publishx_tbl_post_short_content', $post_data->short_content ?? '', ['rows' => 3], [], '', 'tinymce'); ?>
                            <button onclick="generateAIContent($(this), 'full_content')"
                                    class="pull-right btn btn-outline-primary">
                                <i class="fas fa-robot"></i> AI
                            </button>
                            <br>
                            <?php echo render_textarea('full_content', 'publishx_post_full_content', $post_data->full_content ?? '', ['rows' => 3], [], '', 'tinymce'); ?>
                        </div>

                        <div class="col-md-6">

                            <div class="col-md-6">
                                <?php echo render_select('category_id', $post_categories, ['id', 'category_name'], 'publishx_post_category', $post_data->category_id ?? '', [], [], '', '', false); ?>
                            </div>

                            <div class="col-md-6">
                                <?php echo render_select('language_id', $post_languages, ['id', 'name'], 'publishx_post_language', $post_data->language_id ?? '', [], [], '', '', false); ?>
                            </div>

                            <div class="col-md-12">
                                <?php echo render_input('meta_title', 'publishx_meta_title', $post_data->meta_title ?? '') ?>
                            </div>

                            <div class="col-md-12">
                                <?php echo render_textarea('meta_description', 'publishx_meta_description', $post_data->meta_description ?? '') ?>
                            </div>

                            <div class="col-md-12">
                                <?php echo render_input('meta_keywords', 'publishx_meta_keywords', $post_data->meta_keywords ?? '') ?>
                            </div>

                            <div class="col-md-6">
                                <?php echo render_select('status', publishx_post_statuses(), ['value', 'name'], 'publishx_post_status', $post_data->status ?? '', [], [], '', '', false); ?>
                            </div>

                            <div class="col-md-6">
                                <?php echo render_datetime_input('created_at', 'publishx_tbl_publication_date', $post_data->created_at ?? date('Y-m-d H:i:s'), ['data-date-end-date' => date('Y-m-d')]); ?>
                            </div>

                            <div class="btn-bottom-toolbar text-right">
                                <button type="submit"
                                        class="btn btn-primary"><?php echo _l('publishx_publish'); ?></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php echo form_close(); ?>
        </div>
        <div class="btn-bottom-pusher"></div>
    </div>
</div>
<?php init_tail(); ?>
</body>
<script>
    function generateAIContent(element, type) {
        'use strict'; // Use strict mode

        element.prop('disabled', true);
        var selectedValue = $('#language_id').val();
        var selectedText = $('#language_id option:selected').text();
        
       
        let requestData = {
            post_title: $('#post_title').val(),
            post_short: $('#short_content').val(),
            post_full: $('#full_content').val(),
            post_language: selectedText,
            type: type,
        };

        $.ajax({
            url: '<?php echo admin_url('publishx/postAI')?>',
            type: 'POST',
            data: requestData,
            success: function (data) {

                data = JSON.parse(data);

                if (data.status === 'ok') {

                    if (type === 'short_content') {
                        var editor = tinymce.get('short_content');

                        if (editor) {
                            let currentContent = editor.getContent();
                            editor.setContent(currentContent + data.ai_generated);
                        }
                    }
                    if (type === 'full_content') {
                        var editor = tinymce.get('full_content');

                        if (editor) {
                            let currentContent = editor.getContent();
                            editor.setContent(currentContent + data.ai_generated);
                        }
                    }
                } else {
                    alert_float('danger', data.message);
                }
                element.prop('disabled', false);
            },
            error: function (error) {
                alert('Failed');
                console.error(error);
            }
        });
    }

</script>
</html>
