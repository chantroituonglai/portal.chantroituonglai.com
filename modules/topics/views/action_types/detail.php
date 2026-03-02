<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <h4 class="tw-mt-0 tw-font-semibold tw-text-lg tw-text-neutral-700">
                    <?php echo _l('action_type_detail'); ?>
                </h4>
                <div class="panel_s">
                    <div class="panel-body">
                        <table class="table table-bordered">
                            <tbody>
                                <tr>
                                    <td width="30%"><strong><?php echo _l('action_type_id'); ?>:</strong></td>
                                    <td><?php echo html_escape($action_type->id); ?></td>
                                </tr>
                                <tr>
                                    <td><strong><?php echo _l('action_type_name'); ?>:</strong></td>
                                    <td><?php echo html_escape($action_type->name); ?></td>
                                </tr>
                                <tr>
                                    <td><strong><?php echo _l('action_type_code'); ?>:</strong></td>
                                    <td><?php echo html_escape($action_type->action_type_code); ?></td>
                                </tr>
                                <tr>
                                    <td><strong><?php echo _l('ngày_tạo'); ?>:</strong></td>
                                    <td><?php echo _dt($action_type->datecreated); ?></td>
                                </tr>
                                <tr>
                                    <td><strong><?php echo _l('ngày_cập_nhật'); ?>:</strong></td>
                                    <td><?php echo _dt($action_type->dateupdated); ?></td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="row">
                            <div class="col-md-12">
                                <a href="<?php echo admin_url('topics/action_types/edit/' . $action_type->id); ?>" class="btn btn-warning">
                                    <i class="fa fa-pencil-square-o"></i> <?php echo _l('edit_action_type'); ?>
                                </a>
                                <a href="<?php echo admin_url('topics/action_types'); ?>" class="btn btn-default">
                                    <?php echo _l('back'); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?> 