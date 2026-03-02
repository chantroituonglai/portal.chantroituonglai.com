<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<link href="<?php echo module_dir_url('topics', 'assets/css/draft_writer.css?ver=' . time()); ?>" rel="stylesheet">
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <?php echo form_open(admin_url('topics/controllers/create'), ['id' => 'controller-form']); ?>
                        <h4 class="no-margin"><?php echo _l('new_controller'); ?></h4>
                        <hr class="hr-panel-separator" />
                        
                        <div class="row">
                            <div class="col-md-6">
                                <?php echo render_input('site', 'site', '', 'text', ['required' => true]); ?>
                            </div>
                            <div class="col-md-6">
                                <?php
                                $platform_options = [];
                                foreach ($platforms as $key => $platform) {
                                    $platform_options[] = [
                                        'id' => $key,
                                        'name' => $platform['name']
                                    ];
                                }
                                echo render_select('platform', $platform_options, ['id', 'name'], 'platform', '', ['id' => 'platform', 'required' => true]);
                                ?>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div id="login_fields_container" class="login-fields-container mtop15" style="display:none;">
                                    <!-- Login fields will be dynamically added here -->
                                </div>
                                <!-- Connection Status Container -->
                                <div id="connection_status_container" style="display:none;"></div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <?php echo render_input('blog_id', 'blog_id'); ?>
                            </div>
                            <div class="col-md-6">
                                <?php echo render_input('logo_url', 'logo_url', '', 'url'); ?>
                            </div>
                        </div>

                        <!-- Categories container - will be populated after connection test -->
                        <div class="row" id="categories_container" style="display:none;">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="control-label"><?php echo _l('categories'); ?></label>
                                    <div id="categories_list" class="mtop10">
                                        <!-- Categories will be populated dynamically after connection test -->
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <?php echo render_textarea('slogan', 'slogan'); ?>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <?php 
                                // Projects dropdown
                                $project_options = [['id' => '', 'name' => _l('none')]];
                                foreach ($active_projects as $project) {
                                    $project_options[] = [
                                        'id' => $project['id'],
                                        'name' => $project['name']
                                    ];
                                }
                                echo render_select('project_id', $project_options, ['id', 'name'], 'project_id');
                                ?>
                            </div>
                            <div class="col-md-6">
                                <?php echo render_select('status', 
                                    [
                                        ['id' => 1, 'name' => _l('active')],
                                        ['id' => 0, 'name' => _l('inactive')]
                                    ],
                                    ['id', 'name'],
                                    'status',
                                    1
                                ); ?>
                            </div>
                        </div>

                        <div class="row writing-style-section">
                            <div class="col-md-12">
                                <h4 class="bold"><?php echo _l('writing_style'); ?></h4>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <?php
                                $writing_style_options = [];
                                foreach ($writing_styles as $key => $name) {
                                    $writing_style_options[] = [
                                        'id' => $key,
                                        'name' => $name
                                    ];
                                }
                                echo render_select('writing_style_options[style]', $writing_style_options, ['id', 'name'], 'writing_style', '', ['id' => 'writing_style']);
                                ?>
                            </div>
                            <div class="col-md-6">
                                <?php
                                $writing_tone_options = [];
                                foreach ($writing_tones as $key => $name) {
                                    $writing_tone_options[] = [
                                        'id' => $key,
                                        'name' => $name
                                    ];
                                }
                                echo render_select('writing_style_options[tone]', $writing_tone_options, ['id', 'name'], 'writing_tone', '', ['id' => 'writing_tone']);
                                ?>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="control-label"><?php echo _l('writing_criteria'); ?></label>
                                    <div id="writing_criteria_container" class="mtop10">
                                        <!-- Writing criteria checkboxes will be dynamically added here -->
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <?php 
                                echo render_textarea('writing_style_options[custom_instructions]', 'custom_writing_instructions', '', ['rows' => 3, 'id' => 'custom_instructions']); 
                                ?>
                            </div>
                        </div>

                        <!-- Custom Fields Section -->
                        <div class="row">
                            <div class="col-md-12">
                                <h4 class="bold"><?php echo _l('custom_fields'); ?></h4>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <?php echo render_custom_fields('topic_controller'); ?>
                            </div>
                        </div>

                        <!-- Actors info section (only on create new page) -->
                        <div class="row" id="actors_info_section">
                            <div class="col-md-12">
                                <h4 class="bold"><?php echo _l('actors'); ?></h4>
                                <div class="alert alert-info">
                                    <i class="fa fa-info-circle"></i> <?php echo _l('actors_info'); ?>
                                    <br>
                                    <strong><?php echo _l('note'); ?>:</strong> <?php echo _l('actors_available_after_creation'); ?>
                                </div>
                            </div>
                        </div>

                        <div class="btn-bottom-toolbar text-right">
                            <button type="submit" class="btn btn-primary"><?php echo _l('submit'); ?></button>
                            <a href="<?php echo admin_url('topics/controllers'); ?>" class="btn btn-default">
                                <?php echo _l('back'); ?>
                            </a>
                        </div>
                        <?php echo form_close(); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php init_tail(); ?>
<script src="<?php echo base_url('modules/topics/assets/js/draft_writer/controllers.js'); ?>"></script>
<script>
$(function() {
    // Validate form
    appValidateForm($('#controller-form'), {
        site: 'required',
        platform: 'required'
    });
    
    // Initialize TinyMCE for custom instructions
    if (typeof(tinymce) !== 'undefined') {
        var editorConfig = {
            height: 150,
            menubar: false,
            plugins: [
                'advlist autolink lists link image charmap print preview anchor',
                'searchreplace visualblocks code fullscreen',
                'insertdatetime media table paste code help wordcount'
            ],
            toolbar: 'undo redo | formatselect | ' +
            'bold italic backcolor | alignleft aligncenter ' +
            'alignright alignjustify | bullist numlist outdent indent | ' +
            'removeformat | help',
            content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }'
        };

        init_editor('#action_1', editorConfig);
        init_editor('#action_2', editorConfig);
        init_editor('#custom_instructions', editorConfig);
    }
    
    // Add animation to panel
    $('.panel_s').addClass('animated fadeIn');
    
    // Trigger change event on platform dropdown if it has a value
    if ($('#platform').val()) {
        $('#platform').trigger('change');
    }
});
</script>
</body>
</html> 