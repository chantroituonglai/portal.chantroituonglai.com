<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<ul class="nav nav-tabs" role="tablist">
	<li role="presentation"  class="active">
		<a href="#set_support_print_tab" aria-controls="set_support_print_tab" role="tab" data-toggle="tab"><?php echo _l('sp_settings_tab1'); ?></a>
	</li>
</ul>
<div class="tab-content mtop30">
	<div role="tabpanel" class="tab-pane  active" id="set_support_print_tab">
		<div class="row">
			<div class="col-md-6">
			<?php echo render_input('settings['.SUPPORT_PRINT_MODULE_NAME.'_print_heading_text]','sp_settings_print_heading_text',get_option(SUPPORT_PRINT_MODULE_NAME.'_print_heading_text'),'text',array('maxlength'=>200)); ?>
			</div>
			<div class="col-md-6">
			<?php echo render_color_picker('settings['.SUPPORT_PRINT_MODULE_NAME.'_print_heading_color]',_l('sp_settings_print_heading_color'),get_option(SUPPORT_PRINT_MODULE_NAME.'_print_heading_color')); ?>
			</div>
		</div>
		<hr/>
		<div class="row">
			<div class="col-md-6">
				<label><?php echo _l('sp_settings_print_orientation_text');?></label><br/>
				<div class="radio radio-inline radio-primary">
					<input type="radio" id="orientation_p" name="settings[<?php echo SUPPORT_PRINT_MODULE_NAME;?>_print_orientation]" value="P" <?php if(get_option(SUPPORT_PRINT_MODULE_NAME.'_print_orientation') == 'P'){echo 'checked';} ?>>
					<label for="orientation_p"><?php echo _l('sp_settings_print_orientation_P_text'); ?></label>
				</div>
				<div class="radio radio-inline radio-primary">
					<input type="radio" id="orientation_l" name="settings[<?php echo SUPPORT_PRINT_MODULE_NAME;?>_print_orientation]" value="L" <?php if(get_option(SUPPORT_PRINT_MODULE_NAME.'_print_orientation') == 'L'){echo 'checked';} ?>>
					<label for="orientation_l"><?php echo _l('sp_settings_print_orientation_L_text'); ?></label>
				</div>
			</div>
			<div class="col-md-6">
				<?php render_yes_no_option(SUPPORT_PRINT_MODULE_NAME.'_email_send_pdf','sp_settings_email_send_pdf'); ?>
			</div>
		</div>
		<hr/>
		<div class="row">
			<div class="col-md-12">
				<?php echo render_textarea('settings['.SUPPORT_PRINT_MODULE_NAME.'_print_bank_details]','sp_settings_print_bank_details',get_option(SUPPORT_PRINT_MODULE_NAME.'_print_bank_details'),array('maxlength'=>2000)); ?>
				</div>
			</div>
		</div>
		<hr/>		
		<div class="row">
			<div class="col-md-12">
				<?php echo render_textarea('settings['.SUPPORT_PRINT_MODULE_NAME.'_print_footer]','sp_settings_print_footer',get_option(SUPPORT_PRINT_MODULE_NAME.'_print_footer'),array('maxlength'=>2000)); ?>
				</div>
			</div>
		</div>
		
	</div>
</div>