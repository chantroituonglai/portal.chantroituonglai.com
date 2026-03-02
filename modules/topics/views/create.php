<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <?php echo form_open(admin_url('topics/create'), ['id' => 'topic-form']); ?>
                        <h4 class="no-margin"><?php echo _l('new_topic'); ?></h4>
                        <hr class="hr-panel-heading" />
                        
                        <div class="row">
                            <div class="col-md-6">
                                <?php echo render_input('topictitle', 'topic_title', '', 'text', ['required' => true]); ?>
                            </div>
                            
                            <div class="col-md-6">
                                <?php
                                echo render_select('controller_id', 
                                    isset($controllers) ? $controllers : [], 
                                    ['id', 'site'], 
                                    'controller', 
                                    isset($topic) ? $topic->controller_id : '', 
                                    ['data-width' => '100%', 
                                     'data-none-selected-text' => _l('dropdown_non_selected_tex')]
                                );
                                ?>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <?php
                                echo render_select('action_type_code', 
                                    $action_types, 
                                    ['action_type_code', 'name'], 
                                    'action_type', 
                                    '', 
                                    ['data-width' => '100%', 
                                     'data-none-selected-text' => _l('dropdown_non_selected_tex'),
                                     'required' => true]
                                );
                                ?>
                            </div>
                            <div class="col-md-6">
                                <?php
                                echo render_select('action_state_code', 
                                    [], 
                                    ['action_state_code', 'name'], 
                                    'action_state', 
                                    '', 
                                    ['data-width' => '100%', 
                                     'data-none-selected-text' => _l('dropdown_non_selected_tex'),
                                     'required' => true]
                                );
                                ?>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-12">
                                <?php echo render_textarea('log', 'log', '', ['rows' => 4]); ?>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-12">
                                <div class="checkbox checkbox-primary">
                                    <input type="checkbox" name="status" id="status" checked>
                                    <label for="status"><?php echo _l('topic_active'); ?></label>
                                </div>
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
<?php init_tail(); ?>
<script>
$(function(){
    // Load action states when action type changes
    $('#action_type_code').on('change', function(){
        var actionTypeCode = $(this).val();
        if (!actionTypeCode) {
            $('#action_state_code').html('').selectpicker('refresh');
            return;
        }
        
        $.ajax({
            url: admin_url + 'topics/get_action_states',
            type: 'POST',
            data: {
                action_type_code: actionTypeCode
            },
            dataType: 'json',
            success: function(response) {
                var options = '<option value=""><?php echo _l('dropdown_non_selected_tex'); ?></option>';
                
                if (response.success && response.data) {
                    response.data.forEach(function(state) {
                        options += '<option value="' + state.action_state_code + '">' + state.name + '</option>';
                    });
                }
                
                $('#action_state_code').html(options).selectpicker('refresh');
            }
        });
    });

    // Form validation
    $('#topic-form').on('submit', function(e) {
        if (!$('#topictitle').val()) {
            alert_float('warning', '<?php echo _l('topic_title_required'); ?>');
            e.preventDefault();
            return false;
        }

        if (!$('#action_type_code').val()) {
            alert_float('warning', '<?php echo _l('action_type_required'); ?>');
            e.preventDefault();
            return false;
        }

        if (!$('#action_state_code').val()) {
            alert_float('warning', '<?php echo _l('action_state_required'); ?>');
            e.preventDefault();
            return false;
        }
    });
});
</script>