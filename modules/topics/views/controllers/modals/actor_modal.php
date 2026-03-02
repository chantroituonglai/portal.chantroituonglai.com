<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<!-- Actor Modal -->
<div class="modal fade" id="actorModal" tabindex="-1" role="dialog" aria-labelledby="actorModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="actorModalLabel"><?php echo _l('add_actor'); ?></h4>
            </div>
            <div class="modal-body">
                <div id="actor_response"></div>
                <form id="actor_form">
                    <input type="hidden" name="id" id="actor_id">
                    <input type="hidden" name="controller_id" id="actor_controller_id" value="<?php echo isset($controller) ? $controller->id : ''; ?>">
                    
                    <div class="form-group">
                        <label for="name" class="control-label"><?php echo _l('actor_name'); ?> <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="actor_name" name="name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description" class="control-label"><?php echo _l('actor_description'); ?></label>
                        <textarea class="form-control tinymce" id="actor_description" name="description" rows="5"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="priority" class="control-label"><?php echo _l('priority'); ?></label>
                        <input type="number" class="form-control" id="actor_priority" name="priority" min="0" step="1" value="0">
                    </div>
                    
                    <div class="form-group">
                        <div class="checkbox checkbox-primary">
                            <input type="checkbox" name="active" id="actor_active" value="1" checked>
                            <label for="actor_active"><?php echo _l('active'); ?></label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
                <button type="button" class="btn btn-primary" id="saveActorBtn"><?php echo _l('save'); ?></button>
            </div>
        </div>
    </div>
</div> 