<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal fade" id="action_button_modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="myModalLabel">
                    <?php echo _l('edit_action_button_assignment'); ?>
                </h4>
            </div>
            <div class="modal-body">
                <form id="edit_action_button_assignment_form">
                    <input type="hidden" name="id" value="<?php echo $assignment['id']; ?>">
                    
                    <div class="form-group">
                        <label><?php echo _l('action_button'); ?></label>
                        <p class="form-control-static">
                            <span class="label label-<?php echo html_escape($assignment['button_type']); ?>">
                                <?php echo html_escape($assignment['button_name']); ?>
                            </span>
                        </p>
                    </div>
                    
                    <div class="form-group">
                        <label><?php echo _l('workflow_id'); ?></label>
                        <p class="form-control-static">
                            <?php echo html_escape($assignment['workflow_id']); ?>
                        </p>
                    </div>
                    
                    <?php if (!empty($assignment['action_command'])) { ?>
                    <div class="form-group">
                        <label><?php echo _l('action_command'); ?></label>
                        <p class="form-control-static">
                            <?php echo html_escape($assignment['action_command']); ?>
                        </p>
                    </div>
                    <?php } ?>
                    
                    <div class="form-group">
                        <label for="status"><?php echo _l('status'); ?></label>
                        <div class="onoffswitch">
                            <input type="checkbox" name="status" class="onoffswitch-checkbox" id="status" value="1" <?php echo $assignment['status'] == 1 ? 'checked' : ''; ?>>
                            <label class="onoffswitch-label" for="status"></label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="order"><?php echo _l('order'); ?></label>
                        <input type="number" id="order" name="order" class="form-control" value="<?php echo $assignment['order']; ?>" min="0">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
                <button type="button" class="btn btn-info" onclick="update_action_button_assignment()"><?php echo _l('save'); ?></button>
            </div>
        </div>
    </div>
</div>

<script>
    // Update action button assignment
    function update_action_button_assignment() {
        var formData = $('#edit_action_button_assignment_form').serialize();
        
        // Handle checkbox for status
        if (!$('#status').is(':checked')) {
            formData += '&status=0';
        }
        
        var assignmentId = <?php echo $assignment['id']; ?>;
        var url = admin_url + 'topics/controllers/edit_action_button_assignment/' + assignmentId;
        
        $.post(url, formData, function(response) {
            try {
                var result = JSON.parse(response);
                if (result.success) {
                    alert_float('success', result.message);
                    $('#action_button_modal').modal('hide');
                    // Refresh the table to show the updated assignment
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