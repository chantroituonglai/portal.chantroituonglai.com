<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <h4 class="tw-mt-0 tw-font-semibold tw-text-lg tw-text-neutral-700">
                    <?php echo _l('action_state_detail'); ?>
                </h4>
                <div class="panel_s">
                    <div class="panel-body">
                        <table class="table table-bordered">
                            <tbody>
                                <tr>
                                    <td width="30%"><strong><?php echo _l('action_state_id'); ?>:</strong></td>
                                    <td><?php echo html_escape($action_state->id); ?></td>
                                </tr>
                                <tr>
                                    <td><strong><?php echo _l('action_state_name'); ?>:</strong></td>
                                    <td><?php echo html_escape($action_state->name); ?></td>
                                </tr>
                                <tr>
                                    <td><strong><?php echo _l('created_date'); ?>:</strong></td>
                                    <td><?php echo _dt($action_state->datecreated); ?></td>
                                </tr>
                                <tr>
                                    <td><strong><?php echo _l('updated_date'); ?>:</strong></td>
                                    <td><?php echo _dt($action_state->dateupdated); ?></td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="tw-flex tw-items-center tw-space-x-3">
                                    <a href="<?php echo admin_url('topics/action_states/edit/' . $action_state->id); ?>" 
                                       class="tw-text-neutral-500 hover:tw-text-neutral-700 focus:tw-text-neutral-700">
                                        <i class="fa fa-pen-to-square fa-lg"></i>
                                        <?php echo _l('edit_action_state'); ?>
                                    </a>
                                    <a href="<?php echo admin_url('topics/action_states'); ?>" 
                                       class="tw-text-neutral-500 hover:tw-text-neutral-700 focus:tw-text-neutral-700">
                                        <i class="fa fa-circle-left fa-lg"></i>
                                        <?php echo _l('back_to_list'); ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?> 