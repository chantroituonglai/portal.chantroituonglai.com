<div class="form-group">
    <label for="color" class="control-label"><?php echo _l('topic_state_color'); ?></label>
    <div class="color-picker-wrapper">
        <div class="input-group">
            <input type="text" class="form-control" name="color" 
                   value="<?php echo isset($state) ? $state->color : '#000000'; ?>" />
            <span class="input-group-addon"><i></i></span>
        </div>
    </div>
</div>

<?php init_color_picker(); ?> 