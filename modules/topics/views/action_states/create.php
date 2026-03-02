<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <h4 class="tw-mt-0 tw-font-semibold tw-text-lg tw-text-neutral-700">
                    <?php echo _l('add_new_action_state'); ?>
                </h4>
                <?php echo form_open(admin_url('topics/action_states/create')); ?>
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-12">
                                <?php 
                                echo render_input('name', 'action_state_name', '', 'text', ['required' => true]);
                                echo render_input('action_state_code', 'action_state_code', '', 'text', ['required' => true]);
                                echo render_select('action_type_code', $action_types, ['action_type_code', 'name'], 'action_type', '', ['required' => true]); 
                                echo render_color_picker('color', 'action_state_color', '#000000', ['required' => true]);
                                ?>
                                <div class="form-group">
                                    <label for="position"><?php echo _l('position'); ?></label>
                                    <input type="number" id="position" name="position" class="form-control" value="0" min="0">
                                </div>
                            </div>
                        </div>
                        <div class="btn-bottom-toolbar text-right">
                            <button type="submit" class="btn-primary">
                                <i class="fa fa-check tw-mr-1"></i>
                                <?php echo _l('submit'); ?>
                            </button>
                            <a href="<?php echo admin_url('topics/action_states'); ?>" class="btn-default">
                                <i class="fa fa-circle-left tw-mr-1"></i>
                                <?php echo _l('back'); ?>
                            </a>
                        </div>
                    </div>
                </div>
                <?php echo form_close(); ?>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?> 