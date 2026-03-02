<?php $edited = has_permission('deals', '', 'edit'); ?>

<div class="panel-heading">
    <h3 class="panel-title" style="margin-top: -20px;"><?php
        if (!empty($deals_details->title)) {
            echo $deals_details->title;
        }
        ?>
        <span class="btn-xs pull-right">
            <?php
            if (!empty($edited)) { ?>
                <a href="<?= admin_url() ?>deals/new_deal/<?= $deals_details->id ?>"><?= _l('edit') . ' ' . _l('deals') ?></a>
            <?php }
            ?>
        </span>
    </h3>
</div>

<div class="panel-body row form-horizontal task_details">
    <div class="form-group col-sm-12">
        <div class="col-sm-6">
            <label class="control-label col-sm-5"><strong><?= _l('title') ?> :</strong>
            </label>
            <p class="form-control-static"><?php
                if (!empty($deals_details->title)) {
                    echo $deals_details->title;
                }
                ?></p>

        </div>
        <div class="col-sm-6">
            <div class="col-sm-5 text-right">
                <label class="control-label"><strong><?= _l('deal_owner') ?> :</strong></label>
            </div>
            <p class="form-control-static"><?php if (!empty($deals_details->default_deal_owner)) echo($deals_details->full_name); ?></p>
        </div>
    </div>
    <div class="form-group col-sm-12">
        <?php
        if ($deals_details->status == 'won') {
            $sClass = 'success';
        } elseif ($deals_details->status == 'lost') {
            $sClass = 'danger';
        } else {
            $sClass = 'warning';
        }
        ?>
        <div class="col-sm-6">
            <div class="col-sm-5 text-right">
                <label class="control-label"><strong><?= _l('status') ?> :</strong></label>
            </div>
            <p class="form-control-static">
                <span class="label label-<?= $sClass ?>"><?= (!empty($deals_details->status) ? _l($deals_details->status) : '-') ?></span>
            </p>
        </div>

        <div class="col-sm-6">
            <div class="col-sm-5 text-right">
                <label class="control-label"><strong><?= _l('source') ?> :</strong></label>
            </div>
            <p class="form-control-static"><?php if (!empty($deals_details->source_name)) echo $deals_details->source_name; ?></p>
        </div>
    </div>

    <div class="form-group col-sm-12">
        <div class="col-sm-6">
            <div class="col-sm-5 text-right">
                <label class="control-label"><strong><?= _l('deal_value') ?> :</strong></label>
            </div>
            <p class="form-control-static"><?php if (!empty($deals_details->deal_value)) echo $deals_details->deal_value; ?></p>
        </div>
        <div class="col-sm-6">
            <div class="col-sm-5 text-right">
                <label class="control-label"><strong><?= _l('create') . ' ' . _l('date') ?> :</strong></label>
            </div>
            <p class="form-control-static">
                <span><?= _d($deals_details->created_at) ?></span>
            </p>
        </div>
    </div>
    <div class="form-group col-sm-12">

        <div class="col-sm-6">
            <div class="col-sm-5 text-right">
                <label class="control-label"><strong><?= _l('close') . ' ' . _l('date') ?> :</strong></label>
            </div>
            <p class="form-control-static">
                <span><?= _d($deals_details->days_to_close) ?></span>
            </p>
        </div>
        <?php $tags = get_tags_in($deals_details->id, 'deal'); ?>
        <?php if (count($tags) > 0) { ?>
            <div class="col-sm-6">
                <div class="col-sm-5 text-right">
                    <label class="control-label"><strong><?= _l('tags') ?> :</strong></label>
                </div>
                <p class="tags-read-only-custom tw-text-sm tw-text-neutral-900">
                    <input type="text" class="tagsinput read-only" id="tags" name="tags"
                           value="<?php echo prep_tags_input($tags); ?>" data-role="tagsinput">
                </p>
            </div>
        <?php } ?>
    </div>
    <div class="form-group col-sm-12">
        <div class="col-sm-6">
            <div class="col-sm-5 text-right">
                <label class="control-label"><strong><?= _l('pipeline') ?> :</strong></label>
            </div>
            <p class="form-control-static"><?php if (!empty($deals_details->pipeline_name)) echo $deals_details->pipeline_name; ?></p>
        </div>
        <div class="col-sm-6">
            <div class="col-sm-5 text-right">
                <label class="control-label"><strong><?= _l('stage') ?> :</strong></label>
            </div>
            <p class="form-control-static"><?php if (!empty($deals_details->stage_name)) echo $deals_details->stage_name; ?></p>
        </div>
    </div>
    <div class="form-group col-sm-12">
        <div class="col-sm-6">
            <div class="col-sm-5 text-right">
                <label class="control-label"><strong><?= _l('assigne') ?> :</strong></label>
            </div>
            <div class="tw-items-center ltr:tw-space-x-2 tw-inline-flex">
                <div class="tw-flex -tw-space-x-1">
                    <?php
                    $_assignees = '';
                    foreach ($deals_details->assignees as $assignee) {
                        $_remove_assigne = '';
                        if (staff_can('edit', 'deals') ||
                            ($deals_details->current_user_is_creator && staff_can('create', 'deals'))) {
                            $_remove_assigne = ' <a href="#" class="remove-task-user text-danger" onclick="remove_deal_assignee(' . $assignee['staffid'] . ',' . $deals_details->id . '); return false;"><i class="fa fa-remove"></i></a>';
                        }
                        $_assignees .= '
               <div class="task-user"  data-toggle="tooltip" data-title="' . html_escape($assignee['full_name']) . '">
               <a href="' . admin_url('profile/' . $assignee['staffid']) . '" target="_blank">' . staff_profile_image($assignee['staffid'], [
                                'staff-profile-image-small',
                            ]) . '</a> ' . $_remove_assigne . '</span>
               </div>';
                    }
                    if ($_assignees == '') {
                        $_assignees = '<div class="text-danger display-block tw-text-sm tw-mt-2">' . _l('deal_no_assignees') . '</div>';
                    }
                    echo $_assignees;
                    ?>
                </div>
                <a href="#" data-target="#add-edit-members" data-toggle="modal"
                   class="tw-mt-1.5 rtl:tw-mr-3">
                    <svg class="tw-w-5 tw-h-5 tw-text-gray-400 hover:tw-text-gray-500" fill="none"
                         stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24"
                         stroke="currentColor">
                        <path
                                d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                </a>
            </div>
        </div>

    </div>
    <div class="form-group col-sm-12">
        <?php $custom_fields = get_custom_fields('deals');
        if (!empty($custom_fields)) {
            foreach ($custom_fields as $field) { ?>
                <?php $value = get_custom_field_value($deals_details->id, $field['id'], 'deals');
                if ($value == '') {
                    continue;
                } ?>
                <div class="col-sm-6">
                    <div class="col-sm-5 text-right">
                        <label class="control-label"><strong><?= $field['name'] ?> :</strong></label>
                    </div>
                    <p class="form-control-static">
                        <?php echo $value; ?>
                    </p>
                </div>
            <?php }
        }
        ?>
    </div>
</div>
<div class="modal fade" id="add-edit-members" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <?php echo form_open(admin_url('deals/add_deals_assignees/' . $deals_details->id)); ?>
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                            aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><?php echo _l('assigne'); ?></h4>
            </div>
            <div class="modal-body">
                <?php
                $user_id = $deals_details->user_id ?? [];
                if (!empty($user_id)) {
                    $user_id = json_decode($user_id, true);
                }
                $selected = [];
                foreach ($user_id as $id) {
                    $selected[] = $id;
                }
                echo render_select('assignee[]', $staff, ['staffid', ['firstname', 'lastname']], 'assigne', $selected, ['multiple' => true, 'data-actions-box' => true], [], '', '', false);
                ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
                <button type="submit" class="btn btn-primary" autocomplete="off"
                        data-loading-text="<?php echo _l('wait_text'); ?>"><?php echo _l('submit'); ?></button>
            </div>
        </div>
        <!-- /.modal-content -->
        <?php echo form_close(); ?>
    </div>
    <!-- /.modal-dialog -->
</div>
<script type="text/javascript">
    'use strict';

    // Remove task assignee
    function remove_deal_assignee(id, deal_id) {
        if (confirm_delete()) {
            requestGetJSON("deals/remove_assignee/" + id + "/" + deal_id).done(
                function (response) {
                    if (response.success === true || response.success === "true") {
                        alert_float("success", response.message);
                        location.reload();
                    }
                }
            );
            // reload the page
            location.reload();
        }
    }
</script>