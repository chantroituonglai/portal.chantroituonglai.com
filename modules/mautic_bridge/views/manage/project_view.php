<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php $this->load->view('mautic_bridge/manage/_header'); ?>
<?php
$snapshot = json_decode((string) ($map['snapshot_json'] ?? ''), true);
$snapshot = is_array($snapshot) ? $snapshot : [];
?>
<div class="tw-flex tw-items-center tw-justify-between tw-gap-2">
    <div>
        <h4 class="no-margin"><?php echo html_escape($map['initiative_name']); ?></h4>
        <p class="text-muted mtop5 mbot0">
            <a href="<?php echo admin_url('projects/view/' . (int) $map['perfex_project_id']); ?>"><?php echo html_escape($map['perfex_project_name']); ?></a>
            &middot; <?php echo html_escape($map['customer_name'] ?? '-'); ?>
        </p>
    </div>
    <div class="btn-group">
        <button type="button" class="btn btn-default" id="mautic-bridge-refresh-project" data-id="<?php echo (int) $map['id']; ?>">
            <i class="fa fa-refresh"></i> <?php echo _l('mautic_bridge_refresh_snapshot'); ?>
        </button>
        <button type="button" class="btn btn-primary" id="mautic-bridge-generate-tasks" data-id="<?php echo (int) $map['id']; ?>">
            <i class="fa fa-check-circle"></i> <?php echo _l('mautic_bridge_generate_rollout_tasks'); ?>
        </button>
    </div>
</div>
<hr class="hr-panel-heading" />
<div class="row">
    <div class="col-md-3 col-sm-6">
        <div class="mautic-bridge-stat">
            <div class="text-muted"><?php echo _l('mautic_bridge_campaign_name'); ?></div>
            <div><?php echo html_escape($map['mautic_campaign_name'] ?? '-'); ?></div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="mautic-bridge-stat">
            <div class="text-muted"><?php echo _l('mautic_bridge_segment'); ?></div>
            <div><?php echo html_escape($map['mautic_segment_name'] ?? '-'); ?></div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="mautic-bridge-stat">
            <div class="text-muted"><?php echo _l('mautic_bridge_project_hq_objects'); ?></div>
            <div class="stat-value"><?php echo count($objects); ?></div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="mautic-bridge-stat">
            <div class="text-muted"><?php echo _l('mautic_bridge_last_synced_at'); ?></div>
            <div><?php echo html_escape($map['last_synced_at'] ?? '-'); ?></div>
        </div>
    </div>
</div>

<div class="row mtop20">
    <div class="col-md-8">
        <h4><?php echo _l('mautic_bridge_related_objects'); ?></h4>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th><?php echo _l('type'); ?></th>
                        <th><?php echo _l('name'); ?></th>
                        <th><?php echo _l('mautic_bridge_status'); ?></th>
                        <th><?php echo _l('mautic_bridge_last_synced_at'); ?></th>
                        <th><?php echo _l('options'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($objects as $object) { ?>
                        <tr>
                            <td><?php echo html_escape($object['object_type']); ?></td>
                            <td><?php echo html_escape($object['object_name']); ?></td>
                            <td><?php echo html_escape($object['status'] ?? '-'); ?></td>
                            <td><?php echo html_escape($object['last_seen_at'] ?? '-'); ?></td>
                            <td>
                                <?php if (!empty($object['object_url'])) { ?>
                                    <a href="<?php echo html_escape($object['object_url']); ?>" target="_blank" rel="noopener" class="btn btn-default btn-icon">
                                        <i class="fa fa-external-link"></i>
                                    </a>
                                <?php } ?>
                            </td>
                        </tr>
                    <?php } ?>
                    <?php if (empty($objects)) { ?>
                        <tr><td colspan="5" class="text-center text-muted"><?php echo _l('no_records_found'); ?></td></tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="col-md-4">
        <h4><?php echo _l('mautic_bridge_campaign_health'); ?></h4>
        <table class="table table-striped">
            <tbody>
                <tr><td><?php echo _l('mautic_bridge_last_synced_at'); ?></td><td class="text-right"><?php echo html_escape($snapshot['refreshed_at'] ?? '-'); ?></td></tr>
                <?php foreach (($snapshot['catalog_counts'] ?? []) as $type => $count) { ?>
                    <tr><td><?php echo html_escape($type); ?></td><td class="text-right"><?php echo (int) $count; ?></td></tr>
                <?php } ?>
            </tbody>
        </table>
        <h4><?php echo _l('mautic_bridge_create_issue_task'); ?></h4>
        <div class="form-group">
            <input type="text" id="mautic-bridge-issue-key" class="form-control" placeholder="issue_key">
        </div>
        <div class="form-group">
            <input type="text" id="mautic-bridge-issue-title" class="form-control" placeholder="<?php echo _l('task_single'); ?>">
        </div>
        <div class="form-group">
            <textarea id="mautic-bridge-issue-description" class="form-control" rows="3" placeholder="<?php echo _l('task_add_description'); ?>"></textarea>
        </div>
        <button type="button" class="btn btn-default" id="mautic-bridge-create-issue" data-id="<?php echo (int) $map['id']; ?>">
            <i class="fa fa-exclamation-triangle"></i> <?php echo _l('mautic_bridge_create_issue_task'); ?>
        </button>
    </div>
</div>

<h4 class="mtop20"><?php echo _l('mautic_bridge_sync_logs'); ?></h4>
<div class="table-responsive">
    <table class="table table-striped">
        <thead>
            <tr>
                <th><?php echo _l('mautic_bridge_level'); ?></th>
                <th><?php echo _l('mautic_bridge_event_type'); ?></th>
                <th><?php echo _l('mautic_bridge_message'); ?></th>
                <th><?php echo _l('mautic_bridge_context'); ?></th>
                <th><?php echo _l('created_at'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($logs as $log) { ?>
                <tr>
                    <td><?php echo html_escape($log['level']); ?></td>
                    <td><?php echo html_escape($log['event_type']); ?></td>
                    <td><?php echo html_escape($log['message']); ?></td>
                    <td><code><?php echo html_escape(character_limiter((string) $log['context_json'], 160)); ?></code></td>
                    <td><?php echo html_escape($log['created_at']); ?></td>
                </tr>
            <?php } ?>
            <?php if (empty($logs)) { ?>
                <tr><td colspan="5" class="text-center text-muted"><?php echo _l('no_records_found'); ?></td></tr>
            <?php } ?>
        </tbody>
    </table>
</div>
<script>
    $(function() {
        function postProjectAction(url, data) {
            $.post(url, data).done(function(response) {
                var ok = response && response.success === true;
                alert_float(ok ? 'success' : 'danger', response && response.message ? response.message : 'Done.');
                if (ok) {
                    window.setTimeout(function() { window.location.reload(); }, 700);
                }
            }).fail(function(xhr) {
                alert_float('danger', xhr.responseText || xhr.statusText);
            });
        }
        $('#mautic-bridge-refresh-project').on('click', function() {
            postProjectAction(admin_url + 'mautic_bridge_manage/project_refresh', {id: $(this).data('id')});
        });
        $('#mautic-bridge-generate-tasks').on('click', function() {
            if (!confirm('<?php echo _l('mautic_bridge_confirm_generate_tasks'); ?>')) {
                return;
            }
            postProjectAction(admin_url + 'mautic_bridge_manage/project_generate_tasks', {id: $(this).data('id')});
        });
        $('#mautic-bridge-create-issue').on('click', function() {
            postProjectAction(admin_url + 'mautic_bridge_manage/project_create_issue', {
                id: $(this).data('id'),
                issue_key: $('#mautic-bridge-issue-key').val(),
                title: $('#mautic-bridge-issue-title').val(),
                description: $('#mautic-bridge-issue-description').val()
            });
        });
    });
</script>
<?php $this->load->view('mautic_bridge/manage/_footer'); ?>
