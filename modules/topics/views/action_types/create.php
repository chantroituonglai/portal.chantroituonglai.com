<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <h4 class="tw-mt-0 tw-font-semibold tw-text-lg tw-text-neutral-700">
                    <?php echo _l('add_new_action_type'); ?>
                </h4>
                <?php echo form_open(admin_url('topics/action_types/create')); ?>
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="form-group">
                            <label for="name"><?php echo _l('action_type_name'); ?></label>
                            <input type="text" id="name" name="name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="action_type_code"><?php echo _l('action_type_code'); ?></label>
                            <input type="text" id="action_type_code" name="action_type_code" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="position"><?php echo _l('position'); ?></label>
                            <input type="number" id="position" name="position" class="form-control" value="0" min="0">
                        </div>
                        <button type="submit" class="btn btn-primary"><?php echo _l('add_new_action_type'); ?></button>
                        <a href="<?php echo admin_url('topics/action_types'); ?>" class="btn btn-default">
                            <?php echo _l('back'); ?>
                        </a>
                    </div>
                </div>
                <?php echo form_close(); ?>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?> 