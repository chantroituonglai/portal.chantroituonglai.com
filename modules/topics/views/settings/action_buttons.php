<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="row">
    <div class="col-md-12">
        <?php if (has_permission('settings', '', 'edit')) { ?>
            <div class="row">
                <div class="col-md-12">
                    <div class="table-responsive">
                        <table class="table table-bordered">
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
                            <tbody id="action-buttons-table">
                                <?php foreach ($action_buttons as $button) { ?>
                                <tr data-button-id="<?php echo $button['id']; ?>">
                                    <td>
                                        <input type="text" class="form-control" name="name" 
                                               value="<?php echo html_escape($button['name']); ?>" />
                                    </td>
                                    <td>
                                        <select class="form-control" name="button_type">
                                            <option value="primary" <?php echo $button['button_type'] == 'primary' ? 'selected' : ''; ?>>Primary</option>
                                            <option value="info" <?php echo $button['button_type'] == 'info' ? 'selected' : ''; ?>>Info</option>
                                            <option value="warning" <?php echo $button['button_type'] == 'warning' ? 'selected' : ''; ?>>Warning</option>
                                            <option value="danger" <?php echo $button['button_type'] == 'danger' ? 'selected' : ''; ?>>Danger</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control" name="workflow_id" 
                                               value="<?php echo html_escape($button['workflow_id']); ?>" />
                                    </td>
                                    <td>
                                        <select class="form-control selectpicker" name="target_action_type" data-live-search="true">
                                            <option value=""><?php echo _l('none'); ?></option>
                                            <?php foreach ($action_types as $type) { ?>
                                            <option value="<?php echo $type['action_type_code']; ?>" 
                                                    <?php echo $button['target_action_type'] == $type['action_type_code'] ? 'selected' : ''; ?>>
                                                <?php echo $type['name']; ?>
                                            </option>
                                            <?php } ?>
                                        </select>
                                    </td>
                                    <td>
                                        <select class="form-control selectpicker" name="target_action_state" data-live-search="true">
                                            <option value=""><?php echo _l('none'); ?></option>
                                            <?php foreach ($action_states as $state) { ?>
                                            <option value="<?php echo $state['action_state_code']; ?>"
                                                    <?php echo $button['target_action_state'] == $state['action_state_code'] ? 'selected' : ''; ?>>
                                                <?php echo $state['name']; ?>
                                            </option>
                                            <?php } ?>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control" name="description" 
                                               value="<?php echo html_escape($button['description']); ?>" />
                                    </td>
                                    <td>
                                        <div class="onoffswitch">
                                            <input type="checkbox" name="status" class="onoffswitch-checkbox" 
                                                   id="status_<?php echo $button['id']; ?>"
                                                   <?php echo $button['status'] ? 'checked' : ''; ?>>
                                            <label class="onoffswitch-label" for="status_<?php echo $button['id']; ?>"></label>
                                        </div>
                                    </td>
                                    <td>
                                        <input type="number" class="form-control" name="order" 
                                               value="<?php echo $button['order']; ?>" />
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-danger btn-xs delete-button">
                                            <i class="fa fa-remove"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                    <button type="button" class="btn btn-info" id="add-button">
                        <i class="fa fa-plus"></i> <?php echo _l('add_button'); ?>
                    </button>
                    <button type="button" class="btn btn-primary" id="save-buttons">
                        <i class="fa fa-check"></i> <?php echo _l('save'); ?>
                    </button>
                </div>
            </div>
        <?php } ?>
    </div>
</div>

<script>
$(function() {
    // Add new button row
    $('#add-button').on('click', function() {
        var template = $('#action-buttons-table tr:first').clone();
        template.find('input').val('');
        template.find('select').val('');
        template.find('.selectpicker').selectpicker('refresh');
        $('#action-buttons-table').append(template);
    });

    // Delete button
    $(document).on('click', '.delete-button', function() {
        if (confirm(app.lang.confirm_action_prompt)) {
            $(this).closest('tr').remove();
        }
    });

    // Save all buttons
    $('#save-buttons').on('click', function() {
        var data = [];
        $('#action-buttons-table tr').each(function() {
            var row = $(this);
            data.push({
                id: row.data('button-id'),
                name: row.find('[name="name"]').val(),
                button_type: row.find('[name="button_type"]').val(),
                workflow_id: row.find('[name="workflow_id"]').val(),
                target_action_type: row.find('[name="target_action_type"]').val(),
                target_action_state: row.find('[name="target_action_state"]').val(),
                description: row.find('[name="description"]').val(),
                status: row.find('[name="status"]').is(':checked') ? 1 : 0,
                order: row.find('[name="order"]').val()
            });
        });

        $.post(admin_url + 'topics/save_action_buttons', {
            buttons: data
        }).done(function(response) {
            response = JSON.parse(response);
            if (response.success) {
                alert_float('success', response.message);
                // Reload page after short delay
                setTimeout(function() {
                    window.location.reload();
                }, 1000);
            } else {
                alert_float('danger', response.message);
            }
        });
    });
});
</script> 