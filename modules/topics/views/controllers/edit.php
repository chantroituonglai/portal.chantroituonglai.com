<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<link href="<?php echo module_dir_url('topics', 'assets/css/draft_writer.css?ver=' . time()); ?>" rel="stylesheet">
<link href="<?php echo module_dir_url('topics', 'assets/css/controller.css?ver=' . time()); ?>" rel="stylesheet">
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-8 col-md-offset-2">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin"><?php echo $title; ?></h4>
                        <hr class="hr-panel-separator" />
                        
                        <?php echo form_open(admin_url('topics/controllers/edit/' . $controller->id), ['id' => 'controller-form']); ?>
                        <?php echo form_hidden('id', $controller->id); ?>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <?php echo render_input('site', 'site', $controller->site, 'text', ['required' => true]); ?>
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
                                echo render_select('platform', $platform_options, ['id', 'name'], 'platform', $controller->platform, ['id' => 'platform', 'required' => true]);
                                ?>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <?php if (isset($login_status)) { 
                                    $status_class = '';
                                    $status_icon = '';
                                    
                                    if ($login_status['status'] == 1) {
                                        $status_class = 'success';
                                        $status_icon = 'check-circle';
                                    } elseif ($login_status['status'] == 2) {
                                        $status_class = 'danger';
                                        $status_icon = 'times-circle';
                                    } else {
                                        $status_class = 'warning';
                                        $status_icon = 'exclamation-circle';
                                    }
                                ?>
                                    <div class="alert alert-<?php echo $status_class; ?>">
                                        <i class="fa fa-<?php echo $status_icon; ?>"></i> <strong><?php echo _l('connection_status'); ?>:</strong> <?php echo $login_status['text']; ?>
                                        <?php if (isset($login_status['last_login']) && !empty($login_status['last_login'])) { ?>
                                            <br><small><?php echo _l('last_login'); ?>: <?php echo $login_status['last_login']; ?></small>
                                        <?php } ?>
                                    </div>
                                <?php } ?>
                                
                                <div id="login_fields_container" class="login-fields-container mtop15">
                                    <!-- Login fields will be dynamically added here -->
                                    <?php if ($controller->platform && !empty($login_config)) { ?>
                                        <div class="edit-mode-indicator">
                                            <i class="fa fa-info-circle"></i> <?php echo _l('edit_mode'); ?> - <?php echo _l('configuration_loaded'); ?>
                                        </div>
                                        <div class="platform-info">
                                            <h4>
                                                <i class="fa <?php echo $platforms[$controller->platform]['icon']; ?>" style="color: <?php echo $platforms[$controller->platform]['color']; ?>"></i> 
                                                <?php echo $platforms[$controller->platform]['name']; ?> <?php echo _l('login_configuration'); ?>
                                            </h4>
                                            <?php if (isset($platforms[$controller->platform]['description'])): ?>
                                            <p><?php echo $platforms[$controller->platform]['description']; ?></p>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <?php foreach ($platforms[$controller->platform]['login_fields'] as $field) { 
                                            $field_value = isset($login_config[$field]) ? $login_config[$field] : '';
                                            $field_type = strpos($field, 'password') !== false ? 'password' : 'text';
                                            $field_label = _l($field) != $field ? _l($field) : ucwords(str_replace('_', ' ', $field));
                                            $has_value = !empty($field_value) ? 'true' : 'false';
                                        ?>
                                            <div class="form-group">
                                                <label for="login_field_<?php echo $field; ?>"><?php echo $field_label; ?></label>
                                                <div class="input-group">
                                                    <input type="<?php echo $field_type; ?>" 
                                                           id="login_field_<?php echo $field; ?>" 
                                                           name="login_config[<?php echo $field; ?>]" 
                                                           class="form-control login-field" 
                                                           value="<?php echo $field_value; ?>" 
                                                           placeholder="<?php echo $field_label; ?>"
                                                           data-original-value="<?php echo $field_value; ?>"
                                                           data-has-value="<?php echo $has_value; ?>">
                                                    <?php if ($field_type === 'password') { ?>
                                                        <span class="input-group-addon toggle-password" data-target="#login_field_<?php echo $field; ?>"><i class="fa fa-eye"></i></span>
                                                    <?php } ?>
                                                </div>
                                            </div>
                                        <?php } ?>
                                        
                                        <div id="connection_status_container" class="mtop10"></div>
                                        
                                        <button type="button" id="test_connection_edit_btn" class="btn btn-info" data-controller-id="<?php echo $controller->id; ?>">
                                            <i class="fa fa-plug"></i> <?php echo _l('test_connection'); ?>
                                        </button>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <?php echo render_input('logo_url', 'logo_url', $controller->logo_url, 'url'); ?>
                            </div>
                        </div>

                        <!-- Categories section -->
                        <div class="row" id="categories_container" <?php echo empty($categories) ? 'style="display:none;"' : ''; ?>>
                            <div class="col-md-12">
                                <div class="form-group animated fadeIn">
                                    <label class="control-label"><?php echo _l('categories'); ?></label>
                                    <div id="categories_list" class="mtop10">
                                        <?php if (!empty($categories)): ?>
                                            <?php 
                                            // Get previously selected categories
                                            $selected_category_ids = [];
                                            
                                            // First check if there's data in the new selected_categories column
                                            if (isset($controller->selected_categories) && !empty($controller->selected_categories)) {
                                                $selected_category_ids = json_decode($controller->selected_categories, true);
                                                if (!is_array($selected_category_ids)) {
                                                    $selected_category_ids = [];
                                                }
                                            }
                                            // Fallback to old format if needed
                                            else if (isset($controller->categories_state) && !empty($controller->categories_state)) {
                                                $categories_state = json_decode($controller->categories_state, true);
                                                if (isset($categories_state['selected']) && is_array($categories_state['selected'])) {
                                                    $selected_category_ids = $categories_state['selected'];
                                                }
                                            }
                                            
                                            foreach ($categories as $category): 
                                                // Make sure we have valid category_id and name
                                                $category_id = isset($category['category_id']) ? $category['category_id'] : (isset($category['id']) ? $category['id'] : 0);
                                                $category_name = isset($category['name']) ? $category['name'] : (isset($category['title']) ? $category['title'] : '(No name)');
                                                
                                                // Ensure category_id is not empty
                                                if (empty($category_id)) continue;
                                                
                                                $is_checked = in_array($category_id, $selected_category_ids) ? 'checked' : '';
                                            ?>
                                                <div class="checkbox checkbox-primary">
                                                    <input type="checkbox" id="category_<?php echo $category_id; ?>" 
                                                           name="selected_categories[]" 
                                                           value="<?php echo $category_id; ?>"
                                                           <?php echo $is_checked; ?>>
                                                    <label for="category_<?php echo $category_id; ?>"><?php echo $category_name; ?></label>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <?php echo render_textarea('slogan', 'slogan', $controller->slogan); ?>
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
                                echo render_select('project_id', $project_options, ['id', 'name'], 'project_id', $controller->project_id);
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
                                    $controller->status
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
                                $selected_style = isset($writing_style['style']) ? $writing_style['style'] : '';
                                $writing_style_options = [];
                                foreach ($writing_styles as $key => $name) {
                                    $writing_style_options[] = [
                                        'id' => $key,
                                        'name' => $name
                                    ];
                                }
                                echo render_select('writing_style_options[style]', $writing_style_options, ['id', 'name'], 'writing_style', $selected_style, ['id' => 'writing_style']);
                                ?>
                            </div>
                            <div class="col-md-6">
                                <?php
                                $selected_tone = isset($writing_style['tone']) ? $writing_style['tone'] : '';
                                $writing_tone_options = [];
                                foreach ($writing_tones as $key => $name) {
                                    $writing_tone_options[] = [
                                        'id' => $key,
                                        'name' => $name
                                    ];
                                }
                                echo render_select('writing_style_options[tone]', $writing_tone_options, ['id', 'name'], 'writing_tone', $selected_tone, ['id' => 'writing_tone']);
                                ?>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label class="control-label"><?php echo _l('writing_criteria'); ?></label>
                                    <div id="writing_criteria_container" class="mtop10">
                                        <?php 
                                        $selected_criteria = isset($writing_style['criteria']) ? $writing_style['criteria'] : [];
                                        foreach ($writing_criteria as $key => $criteria) { 
                                            $checked = in_array($key, $selected_criteria) ? 'checked' : '';
                                        ?>
                                            <div class="checkbox checkbox-primary">
                                                <input type="checkbox" id="criteria_<?php echo $key; ?>" name="writing_style_options[criteria][]" value="<?php echo $key; ?>" <?php echo $checked; ?>>
                                                <label for="criteria_<?php echo $key; ?>"><?php echo $criteria; ?></label>
                                            </div>
                                        <?php } ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <?php 
                                $custom_instructions = isset($writing_style['custom_instructions']) ? $writing_style['custom_instructions'] : '';
                                echo render_textarea('writing_style_options[custom_instructions]', 'custom_writing_instructions', $custom_instructions, ['rows' => 3, 'id' => 'custom_instructions']); 
                                ?>
                            </div>
                        </div>

                        <!-- Actors Section -->
                        <div class="row" id="actors_section">
                            <div class="col-md-12">
                                <h4 class="bold"><?php echo _l('actors'); ?></h4>
                                <hr class="hr-panel-separator" />
                                <p><?php echo _l('actors_info'); ?></p>
                                
                                <!-- Actor management controls -->
                                <div class="row">
                                    <div class="col-md-12">
                                        <a href="#" class="btn btn-primary" id="add_actor_btn">
                                            <i class="fa fa-plus"></i> <?php echo _l('add_actor'); ?>
                                        </a>
                                    </div>
                                </div>
                                
                                <div class="row mtop15">
                                    <div class="col-md-12">
                                        <div id="actors_list">
                                            <!-- List of actors will be loaded here via AJAX -->
                                            <div id="no-actors-message" <?php echo (empty($controller->id)) ? 'style="display: none;"' : ''; ?>>
                                                <?php echo _l('no_actors_yet'); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
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
                                <?php echo render_custom_fields('topic_controller', $controller->id ?? false); ?>
                            </div>
                        </div>

                        <div class="btn-bottom-toolbar text-right">
                            <button type="submit" class="btn btn-primary"><?php echo _l('submit'); ?></button>
                        </div>
                        <?php echo form_close(); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php $this->load->view('controllers/modals/actor_modal'); ?>

<?php init_tail(); ?>

<!-- Include the original controllers script -->
<script src="<?php echo base_url('modules/topics/assets/js/draft_writer/controllers.js?v='.microtime(true)); ?>"></script>

<!-- Include Sortable.js from CDN -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.14.0/Sortable.min.js"></script>

<!-- Add our actor management script -->
<script src="<?php echo module_dir_url('topics', 'assets/js/controllers/actors.js?v='.microtime(true)); ?>"></script>

<script>
    $(function() {
        // Initialize form validation
        appValidateForm($('#controller-form'), {
            site: 'required',
            platform: 'required'
        });

        // Initialize Select2 controls
        if ($.fn.select2) {
            $('#platform, #status, #writing_style, #writing_tone').select2();
        }
        
        // Initialize editors
        var editorConfig = {
            selector: '',
            height: 200,
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
            setup: function (editor) {
                editor.on('change', function () {
                    editor.save();
                });
            }
        };
        
        // Initialize editor for custom instructions
        if ($('#custom_instructions').length) {
            init_editor('#custom_instructions', editorConfig);
        }
        
        // Initialize toggle password functionality
        $('.toggle-password').on('click', function() {
            var input = $($(this).data('target'));
            if (input.attr('type') === 'password') {
                input.attr('type', 'text');
                $(this).html('<i class="fa fa-eye-slash"></i>');
            } else {
                input.attr('type', 'password');
                $(this).html('<i class="fa fa-eye"></i>');
            }
        });
        
        // Platform change handler
        $('#platform').on('change', function() {
            var platform = $(this).val();
            if (platform) {
                getPlatformFields(platform, storedLoginValues);
            } else {
                $('#login_fields_container').html('').slideUp('fast');
            }
        });
        
        // Test connection button for edit mode
        $('#test_connection_edit_btn').on('click', function() {
            var controllerId = $(this).data('controller-id');
            testConnection(controllerId);
        });
        
        // If we're in edit mode, preserve original values on page load
        preserveExistingLoginValues();
        
        // Add animation to panel
        $('.panel_s').addClass('animated fadeIn');
        
        // Initialize tooltips
        $('[data-toggle="tooltip"]').tooltip();

        // Add custom form submit handler
        $('#controller-form').on('submit', function() {
            // Ensure at least one category checkbox is selected if categories are available
            if ($('#categories_list .checkbox input[type="checkbox"]').length > 0 && 
                $('#categories_list .checkbox input[type="checkbox"]:checked').length === 0) {
                // Show warning about no categories selected
                console.log('Warning: No categories selected');
                // Continue with form submission
            }
            
            // Disable submit button to prevent double submission
            $(this).find('button[type="submit"]').prop('disabled', true);
            return true;
        });
    });
</script>
</body>
</html> 