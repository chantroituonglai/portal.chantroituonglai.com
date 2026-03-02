<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal fade" id="action_button_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title"><?php echo $title; ?></h4>
            </div>
            <div class="modal-body">
                <?php echo form_open(admin_url('topics/action_button' . (isset($action_button) ? '/' . $action_button['id'] : '')), ['id' => 'action-button-form']); ?>
                
                <?php if(isset($action_button)) { ?>
                    <input type="hidden" name="id" value="<?php echo $action_button['id']; ?>">
                <?php } ?>
                <?php if(isset($action_button['order'])) { ?>
                    <input type="hidden" name="order" value="<?php echo $action_button['order']; ?>">
                <?php } ?>
                <div class="form-group">
                    <label for="name"><?php echo _l('name'); ?></label>
                    <input type="text" id="name" name="name" class="form-control" required value="<?php echo isset($action_button["name"]) ? html_escape($action_button["name"]) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="workflow_id"><?php echo _l('workflow_id'); ?></label>
                    <input type="text" id="workflow_id" name="workflow_id" class="form-control" required value="<?php echo isset($action_button["workflow_id"]) ? html_escape($action_button["workflow_id"]) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="button_type"><?php echo _l('button_type'); ?></label>
                    <select id="button_type" name="button_type" class="form-control" required>
                        <option value="primary" <?php echo isset($action_button["button_type"]) && $action_button["button_type"] == "primary" ? "selected" : ""; ?>>Primary</option>
                        <option value="secondary" <?php echo isset($action_button["button_type"]) && $action_button["button_type"] == "secondary" ? "selected" : ""; ?>>Secondary</option>
                        <option value="success" <?php echo isset($action_button["button_type"]) && $action_button["button_type"] == "success" ? "selected" : ""; ?>>Success</option>
                        <option value="danger" <?php echo isset($action_button["button_type"]) && $action_button["button_type"] == "danger" ? "selected" : ""; ?>>Danger</option>
                        <option value="warning" <?php echo isset($action_button["button_type"]) && $action_button["button_type"] == "warning" ? "selected" : ""; ?>>Warning</option>
                        <option value="info" <?php echo isset($action_button["button_type"]) && $action_button["button_type"] == "info" ? "selected" : ""; ?>>Info</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="target_action_type"><?php echo _l('target_action_type'); ?></label>
                    <select name="target_action_type" class="selectpicker" data-width="100%" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>" required>
                        <?php foreach ($action_types as $type): ?>
                            <option value="<?php echo html_escape($type["action_type_code"]); ?>" <?php echo isset($action_button["target_action_type"]) && $action_button["target_action_type"] == $type["action_type_code"] ? "selected" : ""; ?>>
                                <?php echo html_escape($type["name"]); ?> - <?php echo html_escape($type["action_type_code"]); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="target_action_state"><?php echo _l('target_action_state'); ?></label>
                    <select name="target_action_state" class="selectpicker" data-width="100%" data-none-selected-text="<?php echo _l('dropdown_non_selected_tex'); ?>" required>
                        <?php foreach ($action_states as $state): ?>
                            <option value="<?php echo html_escape($state->action_state_code); ?>" <?php echo isset($action_button["target_action_state"]) && $action_button["target_action_state"] == $state->action_state_code ? "selected" : ""; ?>>
                                <?php echo html_escape($state->name); ?> - <?php echo html_escape($state->action_state_code); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="description"><?php echo _l('description'); ?></label>
                    <textarea id="description" name="description" class="form-control"><?php echo isset($action_button["description"]) ? html_escape($action_button["description"]) : ''; ?></textarea>
                </div>
                <div class="form-group">
                    <label for="trigger_type"><?php echo _l('trigger_type'); ?></label>
                    <select id="trigger_type" name="trigger_type" class="form-control" required>
                        <option value="webhook" <?php echo isset($action_button["trigger_type"]) && $action_button["trigger_type"] == "webhook" ? "selected" : ""; ?>>Webhook</option>
                        <option value="native" <?php echo isset($action_button["trigger_type"]) && $action_button["trigger_type"] == "native" ? "selected" : ""; ?>>Native</option>
                    </select>
                </div>
                <div class="form-group">
                    <div class="checkbox checkbox-primary">
                        <input type="checkbox" name="status" id="status" 
                            <?php echo (isset($action_button) && $action_button['status'] == 1) || !isset($action_button) ? 'checked' : ''; ?>>
                        <label for="status"><?php echo _l('active'); ?></label>
                    </div>
                </div>
                <div class="form-group">
                    <div class="checkbox checkbox-primary">
                        <input type="checkbox" name="controller_only" id="controller_only" 
                            <?php echo (isset($action_button) && !empty($action_button['controller_only'])) ? 'checked' : ''; ?>>
                        <label for="controller_only"><?php echo _l('controller_only'); ?></label>
                        <p class="text-muted small"><?php echo _l('controller_only_help_text'); ?></p>
                    </div>
                </div>
                <div class="form-group">
                    <label for="ignore_types"><?php echo _l('ignore_action_types'); ?></label>
                    <select id="ignore_types" name="ignore_types[]" class="selectpicker" multiple data-width="100%" data-live-search="true">
                        <?php foreach ($action_types as $type): ?>
                            <option value="<?php echo html_escape($type['action_type_code']); ?>"
                                <?php echo (isset($action_button) && !empty($action_button['ignore_types']) && 
                                    in_array($type['action_type_code'], 
                                        is_string($action_button['ignore_types']) ? 
                                        json_decode($action_button['ignore_types'], true) : 
                                        $action_button['ignore_types']
                                    )) ? 'selected' : ''; ?>>
                                <?php echo html_escape($type['name']); ?> (<?php echo html_escape($type['action_type_code']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="ignore_states"><?php echo _l('ignore_action_states'); ?></label>
                    <select id="ignore_states" name="ignore_states[]" class="selectpicker" multiple data-width="100%" data-live-search="true">
                        <?php
                    
                        foreach ($action_states as $state): ?>
                            <option value="<?php echo html_escape($state->action_state_code); ?>"
                                <?php echo (isset($action_button) && !empty($action_button->ignore_states) && 
                                    in_array($state['action_state_code'], json_decode($action_button->ignore_states, true))) ? 'selected' : ''; ?>>
                                <?php echo html_escape($state->name); ?> (<?php echo html_escape($state->action_state_code); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="action_command"><?php echo _l('action_command'); ?></label>
                    <input type="text" id="action_command" name="action_command" class="form-control" 
                           value="<?php echo isset($action_button["action_command"]) ? html_escape($action_button["action_command"]) : ''; ?>">
                    <small class="form-text text-muted"><?php echo _l('action_command_help_text'); ?></small>
                </div>
                <div class="tw-flex tw-justify-between">
                        <?php if(isset($action_button)) { ?>
                            <button type="button" class="btn btn-danger _delete" 
                                    onclick="deleteActionButton(<?php echo $action_button['id']; ?>)">
                                <i class="fa fa-trash-o"></i> <?php echo _l('delete'); ?>
                            </button>
                        <?php } else { ?>
                            <div></div>
                        <?php } ?>
                        <div>
                            <button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _l('close'); ?></button>
                            <button type="submit" class="btn btn-info"><?php echo _l('save'); ?></button>
                        </div>
                    </div>
                </div>
                <?php echo form_close(); ?>
            </div>
        </div>
    </div>
</div>
