<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
	<div class="content">
		<div class="row">
			<div class="col-md-8 col-md-offset-2">
				<div class="panel_s">
					<div class="panel-body">
						<h4 class="no-margin"><?php echo _l('edit_order_mapping'); ?></h4>
						<hr class="hr-panel-heading" />
						<?php
							$mappingData = [];
							if (!empty($mapping->data)) {
								$decoded = json_decode($mapping->data, true);
								if (is_array($decoded)) {
									$mappingData = $decoded;
								}
							}
						?>
						<?php echo form_open(admin_url('external_products/edit_order_mapping/' . $mapping->id), ['id' => 'edit_order_mapping_form']); ?>
							<div class="form-group">
								<label for="external_order_id" class="control-label"><?php echo _l('external_order_id'); ?> <span class="text-danger">*</span></label>
								<input type="text" class="form-control" id="external_order_id" name="external_order_id" value="<?php echo html_escape($mappingData['external_order_id'] ?? $mapping->uniquekey); ?>" required>
							</div>

							<div class="form-group">
								<label for="internal_order_id" class="control-label"><?php echo _l('internal_order_id'); ?> <span class="text-danger">*</span></label>
								<input type="text" class="form-control" id="internal_order_id" name="internal_order_id" value="<?php echo html_escape($mappingData['internal_order_id'] ?? $mapping->target_id); ?>" required>
							</div>

							<div class="form-group">
								<label for="customer_name" class="control-label"><?php echo _l('customer_name'); ?></label>
								<input type="text" class="form-control" id="customer_name" name="customer_name" value="<?php echo html_escape($mappingData['customer_name'] ?? ''); ?>">
							</div>

							<div class="form-group">
								<label for="external_system" class="control-label"><?php echo _l('external_system'); ?></label>
								<input type="text" class="form-control" id="external_system" name="external_system" value="<?php echo html_escape($mappingData['external_system'] ?? ''); ?>">
							</div>

							<div class="form-group">
								<label for="order_date" class="control-label"><?php echo _l('order_date'); ?></label>
								<input type="date" class="form-control" id="order_date" name="order_date" value="<?php echo html_escape($mappingData['order_date'] ?? ''); ?>">
							</div>

							<div class="form-group">
								<button type="submit" class="btn btn-info"><?php echo _l('update'); ?></button>
								<a href="<?php echo admin_url('external_products/order_mapping'); ?>" class="btn btn-default"><?php echo _l('cancel'); ?></a>
							</div>
						<?php echo form_close(); ?>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<?php init_tail(); ?>
<script>
	$(function() {
		$('#edit_order_mapping_form').on('submit', function(e) {
			e.preventDefault();

			$.post($(this).attr('action'), $(this).serialize())
				.done(function(response) {
					try {
						var result = JSON.parse(response);
						if (result.success) {
							alert_float('success', result.message);
							window.location.href = '<?php echo admin_url('external_products/order_mapping'); ?>';
						} else {
							alert_float('danger', result.message);
						}
					} catch (e) {
						alert_float('danger', '<?php echo _l('problem_processing_request'); ?>');
					}
				})
				.fail(function() {
					alert_float('danger', '<?php echo _l('problem_processing_request'); ?>');
				});
		});
	});
</script>
