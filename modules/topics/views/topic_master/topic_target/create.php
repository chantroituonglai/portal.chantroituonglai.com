<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>

<script>
    var confirmEditTargetMessage = '<?php echo _l('confirm_edit_target'); ?>';
</script>

<script>
    var langConfirmActionPrompt = '<?php echo _l('confirm_action'); ?>';
    var langContinue = '<?php echo _l('continue'); ?>';
    var langCancel = '<?php echo _l('cancel'); ?>';
</script>

<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-8">
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="tw-flex tw-justify-between tw-mb-4">
                            <h4 class="tw-font-semibold tw-text-lg tw-text-neutral-700">
                                <?php echo _l('new_topic_target'); ?>
                            </h4>
                            <a href="<?php echo admin_url('topics/topic_master/targets'); ?>" class="btn btn-primary">
                                <i class="fa fa-list"></i> <?php echo _l('back_to_list'); ?>
                            </a>
                        </div>
                        
                        <?php echo form_open(admin_url('topics/topic_master/target_create'), ['id' => 'target-form']); ?>
                        <div class="row">
                            <div class="col-md-12">
                                <?php echo render_input('title', 'topic_target_name'); ?>
                                <?php 
                                $existing_types = [];
                                foreach($targets as $t) {
                                    $existing_types[] = $t->target_type;
                                }
                                ?>
                                <?php echo render_input('target_type', 'topic_target_type', '', 'text', [
                                    'placeholder' => _l('topic_target_type_help'),
                                    'data-existing-types' => htmlspecialchars(json_encode($existing_types), ENT_QUOTES, 'UTF-8'),
                                    'data-toggle' => 'tooltip',
                                    'title' => _l('topic_target_type_help'),
                                    'autocomplete' => 'off'
                                ]); ?>
                                <?php echo render_select('status', [
                                    ['id' => 1, 'name' => _l('active')],
                                    ['id' => 0, 'name' => _l('inactive')]
                                ], ['id', 'name'], 'topic_target_status', 1); ?>
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
                        <h4 class="tw-font-semibold tw-text-lg tw-text-neutral-700 tw-mb-4">
                            <?php echo _l('existing_targets'); ?>
                        </h4>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th><?php echo _l('topic_target_name'); ?></th>
                                        <th><?php echo _l('topic_target_type'); ?></th>
                                        <th><?php echo _l('topic_target_status'); ?></th>
                                        <th class="text-right"><?php echo _l('options'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($targets as $target) { ?>
                                    <tr>
                                        <td><?php echo html_escape($target->title); ?></td>
                                        <td><span class="badge badge-info"><?php echo html_escape($target->target_type); ?></span></td>
                                        <td>
                                            <?php
                                            $status_class = $target->status == 1 ? 'success' : 'danger';
                                            $status_text = $target->status == 1 ? _l('active') : _l('inactive');
                                            ?>
                                            <span class="badge badge-<?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                        </td>
                                        <td class="text-right">
                                            <a href="<?php echo admin_url('topics/topic_master/target_edit/'.$target->id); ?>" class="btn btn-default btn-icon">
                                                <i class="fa fa-pen-to-square"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
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
        target_type: {
            required: true,
            remote: {
                url: admin_url + "topics/topic_master/check_target_type",
                type: "post",
                data: {
                    target_type: function() {
                        return $('input[name="target_type"]').val();
                    }
                },
                beforeSend: function() {
                    var currentValue = $('input[name="target_type"]').val();
                    var existingTypes = $('input[name="target_type"]').data('existing-types');
                    
                    if (existingTypes.includes(currentValue)) {
                        // Replace browser confirm with custom dialog
                        var dialog = $("#confirm_edit_target");
                        if (dialog.length === 0) {
                            $("body").append('<div id="confirm_edit_target" class="modal fade" role="dialog">'
                                + '<div class="modal-dialog">'
                                + '<div class="modal-content">'
                                + '<div class="modal-header">'
                                + '<button type="button" class="close" data-dismiss="modal">&times;</button>'
                                + '<h4 class="modal-title">' + langConfirmActionPrompt + '</h4>'
                                + '</div>'
                                + '<div class="modal-body">'
                                + '<p>' + confirmEditTargetMessage + '</p>'
                                + '</div>'
                                + '<div class="modal-footer">'
                                + '<button type="button" class="btn btn-default" data-dismiss="modal">' + langCancel + '</button>'
                                + '<button type="button" class="btn btn-info" id="confirm_edit_target_submit">' + langContinue + '</button>'
                                + '</div>'
                                + '</div>'
                                + '</div>'
                                + '</div>');
                        }

                        $("#confirm_edit_target").modal('show');
                        
                        $("#confirm_edit_target_submit").off().on('click', function() {
                            $("#confirm_edit_target").modal('hide');
                            // Get the target ID from the existing targets data
                            var targetId = null;
                            var existingTypes = $('input[name="target_type"]').data('existing-types');
                            var currentValue = $('input[name="target_type"]').val().toUpperCase();
                            
                            <?php foreach($targets as $t): ?>
                            if ('<?php echo $t->target_type; ?>' === currentValue) {
                                targetId = <?php echo $t->id; ?>;
                            }
                            <?php endforeach; ?>
                            
                            if (targetId) {
                                window.location.href = admin_url + 'topics/topic_master/target_edit/' + targetId;
                            }
                        });

                        $('#confirm_edit_target').off('hidden.bs.modal').on('hidden.bs.modal', function () {
                            // $('input[name="target_type"]').val('');
                        });

                        return false;
                    }
                    return true;
                },
                dataFilter: function(response) {
                    return response === "true";
                }
            }
        }
    });
});
</script> 