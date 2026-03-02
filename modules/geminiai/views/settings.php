<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="com-md-12">
	<?php $CI = &get_instance(); ?>

	<ul class="nav nav-tabs" role="tablist">
		<li role="presentation" class="active"><a href="#geminiai-classification" aria-controls="geminiai-classification" role="tab" data-toggle="tab">Ticket Classification</a></li>
		<li role="presentation"><a href="#geminiai-settings" aria-controls="geminiai-settings" role="tab" data-toggle="tab">Settings</a></li>
		<li role="presentation"><a href="#geminiai-history" aria-controls="geminiai-history" role="tab" data-toggle="tab">History: Email Read & Ticket Classification</a></li>
	</ul>

	<div class="tab-content mtop20">
		<div role="tabpanel" class="tab-pane active" id="geminiai-classification">
			<h4 class="mbot10">Ticket Classification</h4>
			<div class="checkbox checkbox-primary">
				<input type="checkbox" id="geminiai_ticket_classify_enabled" name="settings[geminiai_ticket_classify_enabled]" value="1" <?= get_option('geminiai_ticket_classify_enabled') == '1' ? 'checked' : '' ?>>
				<label for="geminiai_ticket_classify_enabled">Enable classification on piped tickets</label>
			</div>

			<?= render_textarea('settings[geminiai_ticket_prompt]', 'Classification prompt template', get_option('geminiai_ticket_prompt'), ['rows' => 6]); ?>
			<p class="text-muted">
				Available placeholders: <code>{$subject}</code>, <code>{$body}</code>. The model should return <strong>strict JSON</strong>.
			</p>
			<div class="panel_s">
				<div class="panel-body">
					<p class="bold mbot10">Expected JSON example:</p>
					<pre class="no-margin"><code>{
  "category": "Technical Issue",
  "priority": "High",
  "score": 0.82
}</code></pre>
					<?php
					// Compute current allowed lists from mapping settings
					$catMap = [
						'Technical Issue' => 'geminiai_map_dept_technical_issue',
						'Billing' => 'geminiai_map_dept_billing',
						'Sales' => 'geminiai_map_dept_sales',
						'Account' => 'geminiai_map_dept_account',
						'Feedback' => 'geminiai_map_dept_feedback',
						'Other' => 'geminiai_map_dept_other',
					];
					$priMap = [
						'Low' => 'geminiai_map_pri_low',
						'Medium' => 'geminiai_map_pri_medium',
						'High' => 'geminiai_map_pri_high',
						'Urgent' => 'geminiai_map_pri_urgent',
					];
					$allowedCats = [];
					foreach ($catMap as $label => $opt) {
						$val = (int) get_option($opt);
						if ($val > 0) { $allowedCats[] = $label; }
					}
					if (empty($allowedCats)) { $allowedCats = array_keys($catMap); }
					$allowedPris = [];
					foreach ($priMap as $label => $opt) {
						$val = (int) get_option($opt);
						if ($val > 0) { $allowedPris[] = $label; }
					}
					if (empty($allowedPris)) { $allowedPris = array_keys($priMap); }
					$allowedCatsStr = implode(', ', $allowedCats);
					$allowedPrisStr = implode(', ', $allowedPris);
					?>
					<p class="text-muted mtop15">Note: The following policy is always appended (not editable) to enforce strict JSON and allowed choices:</p>
					<pre class="no-margin"><code>You must choose category only from: <?= e($allowedCatsStr); ?> and priority only from: <?= e($allowedPrisStr); ?>.
Return ONLY a compact JSON object with exactly these keys: category, priority, score.
Do not include any explanations, markdown, or code fences. The output MUST be valid JSON.</code></pre>
				</div>
			</div>

			<h4 class="mtop20 mbot10">Department & Priority Mapping</h4>
			<?php
				// Departments
				$departments = $CI->db->get(db_prefix() . 'departments')->result_array();
				echo render_select('settings[geminiai_map_dept_technical_issue]', $departments, ['departmentid','name'], 'Technical Issue -> Department', get_option('geminiai_map_dept_technical_issue'));
				echo render_select('settings[geminiai_map_dept_billing]', $departments, ['departmentid','name'], 'Billing -> Department', get_option('geminiai_map_dept_billing'));
				echo render_select('settings[geminiai_map_dept_sales]', $departments, ['departmentid','name'], 'Sales -> Department', get_option('geminiai_map_dept_sales'));
				echo render_select('settings[geminiai_map_dept_account]', $departments, ['departmentid','name'], 'Account -> Department', get_option('geminiai_map_dept_account'));
				echo render_select('settings[geminiai_map_dept_feedback]', $departments, ['departmentid','name'], 'Feedback -> Department', get_option('geminiai_map_dept_feedback'));
				echo render_select('settings[geminiai_map_dept_other]', $departments, ['departmentid','name'], 'Other -> Department', get_option('geminiai_map_dept_other'));

				// Priorities
				$priorities = $CI->db->get(db_prefix() . 'tickets_priorities')->result_array();
				echo render_select('settings[geminiai_map_pri_low]', $priorities, ['priorityid','name'], 'Low -> Priority', get_option('geminiai_map_pri_low'));
				echo render_select('settings[geminiai_map_pri_medium]', $priorities, ['priorityid','name'], 'Medium -> Priority', get_option('geminiai_map_pri_medium'));
				echo render_select('settings[geminiai_map_pri_high]', $priorities, ['priorityid','name'], 'High -> Priority', get_option('geminiai_map_pri_high'));
				echo render_select('settings[geminiai_map_pri_urgent]', $priorities, ['priorityid','name'], 'Urgent -> Priority', get_option('geminiai_map_pri_urgent'));
			?>
		</div>

		<div role="tabpanel" class="tab-pane" id="geminiai-settings">
			<h4 class="mbot10">Settings</h4>
			<?= render_input('settings[geminiai_api_key]', _l('geminiai_api_key'), get_option('geminiai_api_key'), 'password'); ?>
			<?= render_textarea('settings[geminiai_api_keys]', 'Multiple API Keys (one per line)', get_option('geminiai_api_keys'), ['rows' => 4, 'placeholder' => 'key-1...\nkey-2...\nkey-3...']); ?>
			<p class="text-muted">If multiple keys are provided, the module rotates automatically when a key hits quota (HTTP 429). The single key field above remains for backward compatibility.</p>
			<?php $activeIdx = (int) get_option('geminiai_active_key_index'); ?>
			<p><strong>Active key index:</strong> <?= (int) $activeIdx; ?></p>
			<?php
			$models = Perfexcrm\Geminiai\GeminiProvider::getModels();
			echo render_select('settings[geminiai_model]', $models, ['id', 'name'], 'geminiai_model', get_option('geminiai_model'));
			?>
			<?= render_input('settings[geminiai_max_token]', _l('geminiai_max_token'), get_option('geminiai_max_token'), 'number'); ?>
		</div>

		<div role="tabpanel" class="tab-pane" id="geminiai-history">
			<h4 class="mtop20 mbot10">History: Email Read & Ticket Classification</h4>
			<div class="table-responsive">
				<table class="table table-striped">
					<thead>
						<tr>
							<th>Time</th>
							<th>From</th>
							<th>Subject</th>
							<th>Classification</th>
							<th>Score</th>
							<th>Ticket</th>
						</tr>
					</thead>
					<tbody>
						<?php
						$CI = &get_instance();
						$CI->load->model('geminiai/Geminiai_model');
						$logs = $CI->Geminiai_model->get_history_latest(50);
						foreach ($logs as $log) { ?>
							<tr>
								<td><?= e(_dt($log->created_at)); ?></td>
								<td><?= e($log->email_from); ?></td>
								<td title="<?= e($log->subject); ?>"><?= e(mb_strimwidth($log->subject, 0, 60, '…')); ?></td>
								<td><?= e($log->classification); ?></td>
								<td><?= e($log->score); ?></td>
								<td>
									<?php if ($log->ticket_id) { ?>
										<a href="<?= admin_url('tickets/ticket/'.$log->ticket_id) ?>" target="_blank">#<?= e($log->ticket_id); ?></a>
									<?php } else { echo '-'; } ?>
								</td>
							</tr>
						<?php } ?>
					</tbody>
				</table>
			</div>
			<p class="text-muted">Only the last 50 entries are shown.</p>
		</div>
	</div>

	<script>
		$(function(){
			var hash = window.location.hash;
			if(hash && $('a[href="'+hash+'"]').length){
				$('a[href="'+hash+'"]').tab('show');
			}
			$('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
				if(e.target && e.target.hash){
					history.replaceState(null, null, e.target.hash);
				}
			});
		});
	</script>
</div>
