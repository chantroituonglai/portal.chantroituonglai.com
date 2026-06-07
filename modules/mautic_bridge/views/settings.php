<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-8">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin"><?php echo _l('mautic_bridge_settings'); ?></h4>
                        <hr class="hr-panel-heading" />
                        <?php echo form_open(admin_url('mautic_bridge')); ?>
                        <div class="checkbox checkbox-primary">
                            <input type="checkbox" id="mautic_bridge_enabled" name="mautic_bridge_enabled" <?php echo !empty($settings['enabled']) ? 'checked' : ''; ?>>
                            <label for="mautic_bridge_enabled"><?php echo _l('mautic_bridge_enabled'); ?></label>
                        </div>
                        <div class="checkbox checkbox-primary">
                            <input type="checkbox" id="mautic_bridge_dry_run" name="mautic_bridge_dry_run" <?php echo !empty($settings['dry_run']) ? 'checked' : ''; ?>>
                            <label for="mautic_bridge_dry_run"><?php echo _l('mautic_bridge_dry_run'); ?></label>
                        </div>
                        <?php echo render_input('mautic_bridge_base_url', 'mautic_bridge_base_url', $settings['base_url'] ?? '', 'url'); ?>
                        <?php echo render_input('mautic_bridge_oauth_client_id', 'mautic_bridge_oauth_client_id', $settings['oauth_client_id'] ?? ''); ?>
                        <?php echo render_input('mautic_bridge_oauth_client_secret', 'mautic_bridge_oauth_client_secret', '', 'password'); ?>
                        <?php echo render_input('mautic_bridge_webhook_secret', 'mautic_bridge_webhook_secret', $settings['webhook_secret'] ?? '', 'password'); ?>
                        <div class="row">
                            <div class="col-md-4">
                                <?php echo render_select('mautic_bridge_default_lead_source', $lead_sources ?? [], ['id', 'name'], 'mautic_bridge_default_lead_source', $settings['default_lead_source'] ?? '', ['data-live-search' => true]); ?>
                            </div>
                            <div class="col-md-4">
                                <?php echo render_select('mautic_bridge_default_lead_status', $lead_statuses ?? [], ['id', 'name'], 'mautic_bridge_default_lead_status', $settings['default_lead_status'] ?? '', ['data-live-search' => true]); ?>
                            </div>
                            <div class="col-md-4">
                                <?php echo render_select('mautic_bridge_default_assigned_staff', $staff_members ?? [], ['staffid', ['firstname', 'lastname']], 'mautic_bridge_default_assigned_staff', $settings['default_assigned_staff'] ?? '', ['data-live-search' => true]); ?>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <?php echo render_input('mautic_bridge_timeout', 'mautic_bridge_timeout', $settings['timeout'] ?? 30, 'number'); ?>
                            </div>
                            <div class="col-md-6">
                                <?php echo render_input('mautic_bridge_retry_max', 'mautic_bridge_retry_max', $settings['retry_max'] ?? 3, 'number'); ?>
                            </div>
                        </div>
                        <div class="checkbox checkbox-danger">
                            <input type="checkbox" id="mautic_bridge_delete_remote" name="mautic_bridge_delete_remote" <?php echo !empty($settings['delete_remote']) ? 'checked' : ''; ?>>
                            <label for="mautic_bridge_delete_remote"><?php echo _l('mautic_bridge_delete_remote'); ?></label>
                        </div>
                        <button type="submit" class="btn btn-primary"><?php echo _l('mautic_bridge_save'); ?></button>
                        <button type="button" class="btn btn-default" id="mautic-bridge-test-connection" data-url="<?php echo admin_url('mautic_bridge/test_connection'); ?>">Test connection</button>
                        <a href="<?php echo admin_url('mautic_bridge_manage'); ?>" class="btn btn-default">
                            <i class="fa fa-list"></i> <?php echo _l('mautic_bridge_sync_manager'); ?>
                        </a>
                        <div id="mautic-bridge-preview" class="alert alert-info mtop15 hide"></div>
                        <?php echo form_close(); ?>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin">Webhook</h4>
                        <hr class="hr-panel-heading" />
                        <p><code><?php echo site_url('mautic_bridge/webhook/contact'); ?></code></p>
                        <p>Header: <code>X-Mautic-Bridge-Secret</code></p>
                        <p>Cron: <code><?php echo site_url('mautic_bridge/cron/process?reconcile=1&amp;secret=...'); ?></code></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script>
    $(function() {
        function runMauticBridgeAction($button, successMessage) {
            var originalText = $button.text();
            $button.prop('disabled', true).text('<?php echo _l('wait_text'); ?>');

            $.getJSON($button.data('url'))
                .done(function(response) {
                    var isSuccess = response.success === true || response.failed === 0;
                    var message = response.message || successMessage;
                    var alertType = isSuccess ? 'success' : 'danger';

                    if (typeof response.processed !== 'undefined') {
                        if (response.processed === 0) {
                            message = 'Queue is empty. No pending or retry jobs to process.';
                            alertType = 'info';
                        } else {
                            message += ' Processed: ' + response.processed + ', success: ' + response.success + ', failed: ' + response.failed + '.';
                        }
                    }

                    alert_float(alertType, message);
                })
                .fail(function(xhr) {
                    alert_float('danger', xhr.responseText || xhr.statusText);
                })
                .always(function() {
                    $button.prop('disabled', false).text(originalText);
                });
        }

        function renderMauticBridgePreview(preview) {
            var queue = preview.queue || {};
            var mautic = preview.mautic || {};
            var perfex = preview.perfex || {};
            var mappings = preview.mappings || {};
            var html = [
                '<strong>Dry-run preview</strong>',
                '<div>Queue ready now: ' + (queue.due_now || 0) + ' job(s). Inbound pending/retry: ' + (queue.inbound_pending_retry || 0) + '. Outbound pending/retry: ' + (queue.outbound_pending_retry || 0) + '.</div>',
                '<div>Mautic contacts total: ' + (mautic.total_contacts === null ? 'unknown' : mautic.total_contacts) + '.</div>',
                '<div>Perfex potential outbound: ' + (perfex.unmapped_leads_with_email || 0) + ' unmapped lead(s) with email, ' + (perfex.unmapped_contacts_with_email || 0) + ' unmapped contact(s) with email.</div>',
                '<div>Existing bridge mappings: ' + (mappings.total || 0) + ' total, ' + (mappings.lead || 0) + ' lead, ' + (mappings.contact || 0) + ' contact.</div>',
                '<div class="text-muted">Queue ready now is what Process queue will try immediately. Mautic total and Perfex potential counts need webhook/reconcile/backfill to become queue jobs.</div>'
            ];

            $('#mautic-bridge-preview').removeClass('hide').html(html.join(''));
        }

        $('#mautic-bridge-test-connection').on('click', function() {
            runMauticBridgeAction($(this), 'Mautic connection OK.');
        });

    });
</script>
