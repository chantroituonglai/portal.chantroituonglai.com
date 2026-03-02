<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="tw-mb-6">
                    <div class="tw-flex tw-items-center tw-justify-between">
                        <h4 class="tw-mt-0 tw-font-semibold tw-text-lg tw-text-neutral-700">
                            <?php echo _l('edit_topic'); ?>
                        </h4>
                    </div>
                </div>

                <?php echo form_open(admin_url('topics/edit/' . $topic->id)); ?>
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-6">
                                <?php echo render_input('topictitle', 'topic_title', $topic->topictitle); ?>
                            </div>
                            <div class="col-md-6">
                                <?php
                                echo render_select('action_type', $action_types, ['id', 'name'], 'action_type', $topic->action_type, [
                                    'data-width' => '100%',
                                    'data-none-selected-text' => _l('dropdown_non_selected_tex')
                                ]);
                                ?>
                            </div>
                            <div class="col-md-6">
                                <?php
                                echo render_select('action_state', $action_states, ['id', 'name'], 'action_state', $topic->action_state, [
                                    'data-width' => '100%',
                                    'data-none-selected-text' => _l('dropdown_non_selected_tex')
                                ]);
                                ?>
                            </div>
                            <div class="col-md-12">
                                <?php echo render_textarea('log', 'log', $topic->log, ['rows' => 4]); ?>
                            </div>
                        </div>

                        <hr class="hr-panel-separator" />

                        <div class="btn-bottom-toolbar text-right">
                            <button type="submit" class="btn-primary">
                                <i class="fa fa-check tw-mr-1"></i>
                                <?php echo _l('submit'); ?>
                            </button>
                            <a href="<?php echo admin_url('topics'); ?>" class="btn-default">
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

<script>
$(function(){
    // Load action states when action type changes
    $('#action_type').on('change', function(){
        loadActionStates($(this).val());
    });
});
</script>