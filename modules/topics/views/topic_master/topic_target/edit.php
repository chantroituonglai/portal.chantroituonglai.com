<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>

<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-8">
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="tw-flex tw-justify-between tw-mb-4">
                            <h4 class="tw-font-semibold tw-text-lg tw-text-neutral-700">
                                <?php echo _l('edit_topic_target'); ?>
                            </h4>
                            <a href="<?php echo admin_url('topics/topic_master/targets'); ?>" class="btn btn-primary">
                                <i class="fa fa-list"></i> <?php echo _l('back_to_list'); ?>
                            </a>
                        </div>

                        <?php echo form_open(admin_url('topics/topic_master/target_edit/'.$target->id), ['id' => 'target-form']); ?>
                        <div class="row">
                            <div class="col-md-12">
                                <?php echo render_input('title', 'topic_target_name', $target->title); ?>
                                <?php echo render_input('target_id', 'topic_target_id', $target->target_id, 'number', [
                                    'readonly' => true
                                ]); ?>
                                <?php echo render_input('target_type', 'topic_target_type', $target->target_type, 'text', [
                                    'placeholder' => 'e.g. CONTENT, SOCIAL, etc.',
                                    'data-toggle' => 'tooltip',
                                    'title' => _l('topic_target_type_help')
                                ]); ?>
                            </div>
                        </div>
                        <div class="tw-flex tw-justify-end tw-space-x-3">
                            <a href="<?php echo admin_url('topics/topic_master/targets'); ?>" class="btn btn-default">
                                <?php echo _l('cancel'); ?>
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <?php echo _l('submit'); ?>
                            </button>
                        </div>
                        <?php echo form_close(); ?>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="panel_s">
                    <div class="panel-body">
                        
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php init_tail(); ?>

<script>
$(function() {
    appValidateForm($('#target-form'), {
        title: 'required',
        target_type: 'required'
    });

    // Convert target_type to uppercase on blur
    $('input[name="target_type"]').on('blur', function() {
        $(this).val($(this).val().toUpperCase());
    });
});
</script>
