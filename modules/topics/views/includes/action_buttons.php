<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="table-responsive">
    <table class="table table-action-buttons" id="action-buttons-table">
        <thead>
            <tr>
                <th><?php echo _l('name'); ?></th>
                <th><?php echo _l('button_type'); ?></th>
                <th><?php echo _l('workflow_id'); ?></th>
                <th><?php echo _l('target_action_type'); ?></th>
                <th><?php echo _l('target_action_state'); ?></th>
                <th><?php echo _l('description'); ?></th>
                <th><?php echo _l('status'); ?></th>
                <th><?php echo _l('order'); ?></th>
                <th><?php echo _l('options'); ?></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($action_buttons as $button) { ?>
            <tr>
                <td>
                    <a href="#" 
                    onclick="edit_action_button(<?php echo $button['id']; ?>); return false;"     
                    <?php echo html_escape($button['name']); ?></td>
                <td>
                    <span class="label label-<?php echo html_escape($button['button_type']); ?>">
                        <?php echo ucfirst(html_escape($button['button_type'])); ?>
                    </span>
                </td>
                <td><?php echo html_escape($button['workflow_id']); ?></td>
                <td><?php echo html_escape($button['target_action_type']); ?></td>
                <td><?php echo html_escape($button['target_action_state']); ?></td>
                <td><?php echo html_escape($button['description']); ?></td>
                <td>
                    <div class="onoffswitch">
                        <input type="checkbox" 
                               data-switch-url="<?php echo admin_url('topics/change_button_status'); ?>" 
                               name="onoffswitch" 
                               class="onoffswitch-checkbox" 
                               id="status_<?php echo $button['id']; ?>" 
                               data-id="<?php echo $button['id']; ?>" 
                               <?php echo $button['status'] == 1 ? 'checked' : ''; ?>>
                        <label class="onoffswitch-label" for="status_<?php echo $button['id']; ?>"></label>
                    </div>
                </td>
                <td><?php echo html_escape($button['order']); ?></td>
                <td>
                    <div class="tw-flex tw-items-center tw-space-x-3">
                        <?php if (has_permission('topics', '', 'edit')) { ?>
                            <a href="#" 
                               onclick="edit_action_button(<?php echo $button['id']; ?>); return false;" 
                               class="tw-text-neutral-500 hover:tw-text-neutral-700 focus:tw-text-neutral-700">
                                <i class="fa-regular fa-pen-to-square fa-lg"></i>
                            </a>
                        <?php } ?>
                        <?php if (has_permission('topics', '', 'delete')) { ?>
                            <a href="<?php echo admin_url('topics/delete_action_button/'.$button['id']); ?>" 
                               class="tw-text-neutral-500 hover:tw-text-neutral-700 focus:tw-text-neutral-700 _delete">
                                <i class="fa-regular fa-trash-can fa-lg"></i>
                            </a>
                        <?php } ?>
                    </div>
                </td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
</div>

<script>
function edit_action_button(id) {
    var url = admin_url + 'topics/action_button/' + id;
    $.get(url, function(response) {
        var data = JSON.parse(response);
        if (data.success) {
            var modal = $('#action_button_modal');
            modal.find('.add-title').addClass('hide');
            modal.find('.edit-title').removeClass('hide');
            modal.find('[name="id"]').val(id);
            modal.find('[name="name"]').val(data.action_button.name);
            modal.find('[name="button_type"]').val(data.action_button.button_type).change();
            modal.find('[name="workflow_id"]').val(data.action_button.workflow_id);
            modal.find('[name="target_action_type"]').val(data.action_button.target_action_type).change();
            modal.find('[name="target_action_state"]').val(data.action_button.target_action_state).change();
            modal.find('[name="description"]').val(data.action_button.description);
            modal.find('[name="order"]').val(data.action_button.order);
            modal.find('[name="status"]').prop('checked', data.action_button.status == 1);
            modal.modal('show');
        }
    });
}
</script> 