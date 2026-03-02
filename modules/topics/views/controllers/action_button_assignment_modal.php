<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal fade" id="action_button_modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="myModalLabel">
                    <?php echo _l('add_action_button_to_controller'); ?>
                </h4>
            </div>
            <div class="modal-body">
                <?php if (empty($available_buttons)) { ?>
                    <div class="alert alert-warning">
                        <?php echo _l('no_available_action_buttons'); ?>
                    </div>
                <?php } else { ?>
                    <form id="add_action_button_form">
                        <div class="form-group">
                            <label for="action_button_id"><?php echo _l('select_action_button'); ?></label>
                            <select id="action_button_id" name="action_button_id" class="form-control selectpicker" data-live-search="true" required>
                                <option value=""><?php echo _l('select_action_button'); ?></option>
                                <?php foreach ($available_buttons as $button) { ?>
                                    <option value="<?php echo $button['id']; ?>">
                                        <?php echo html_escape($button['name']); ?> 
                                        (<?php echo html_escape($button['button_type']); ?>)
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="order"><?php echo _l('order'); ?></label>
                            <input type="number" id="order" name="order" class="form-control" value="0" min="0">
                        </div>
                    </form>
                <?php } ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
                <?php if (!empty($available_buttons)) { ?>
                    <button type="button" class="btn btn-info" onclick="save_action_button_assignment()"><?php echo _l('save'); ?></button>
                <?php } ?>
            </div>
        </div>
    </div>
</div>

<script>
    $(function() {
        // Initialize selectpicker
        $('.selectpicker').selectpicker();
    });
    
    // Save action button assignment
    function save_action_button_assignment() {
        var formData = $('#add_action_button_form').serialize();
        var controllerId = <?php echo $controller_id; ?>;
        var url = admin_url + 'topics/controllers/add_action_button_to_controller/' + controllerId;
        
        $.post(url, formData, function(response) {
            try {
                var result = JSON.parse(response);
                if (result.success) {
                    alert_float('success', result.message);
                    $('#action_button_modal').modal('hide');
                    // Refresh the table to show the new assignment
                    $('.table-controller-action-buttons').DataTable().ajax.reload();
                } else {
                    alert_float('danger', result.message);
                }
            } catch (e) {
                alert_float('danger', 'Error processing response');
                console.error(e);
            }
        });
    }
</script>