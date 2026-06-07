<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php $this->load->view('mautic_bridge/manage/_header'); ?>
<?php
$preview = $summary['preview'] ?? [];
$queue = $preview['queue'] ?? [];
$mappings = $preview['mappings'] ?? [];
$perfex = $preview['perfex'] ?? [];
$mautic = $preview['mautic'] ?? [];
?>
<div class="row">
    <div class="col-md-3 col-sm-6">
        <div class="mautic-bridge-stat">
            <div class="text-muted"><?php echo _l('mautic_bridge_queue_due_now'); ?></div>
            <div class="stat-value"><?php echo (int) ($queue['due_now'] ?? 0); ?></div>
            <div class="text-muted"><?php echo (int) ($queue['pending_retry_total'] ?? 0); ?> pending/retry</div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="mautic-bridge-stat">
            <div class="text-muted"><?php echo _l('mautic_bridge_mapped_records'); ?></div>
            <div class="stat-value"><?php echo (int) ($mappings['total'] ?? 0); ?></div>
            <div class="text-muted"><?php echo (int) ($mappings['lead'] ?? 0); ?> leads / <?php echo (int) ($mappings['contact'] ?? 0); ?> contacts</div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="mautic-bridge-stat">
            <div class="text-muted"><?php echo _l('mautic_bridge_mautic_contacts'); ?></div>
            <div class="stat-value"><?php echo $mautic['total_contacts'] === null ? '-' : (int) $mautic['total_contacts']; ?></div>
            <div class="<?php echo !empty($mautic['ok']) ? 'text-success' : 'text-danger'; ?>">
                <?php echo !empty($mautic['ok']) ? 'API OK' : html_escape($mautic['message'] ?? 'API error'); ?>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="mautic-bridge-stat">
            <div class="text-muted"><?php echo _l('mautic_bridge_campaign_tags'); ?></div>
            <div class="stat-value"><?php echo (int) ($summary['campaign_mappings'] ?? 0); ?></div>
            <div class="text-muted"><?php echo html_escape($summary['last_mapping_sync'] ?? '-'); ?></div>
        </div>
    </div>
</div>

<div class="row mtop20">
    <div class="col-md-6">
        <div class="panel_s">
            <div class="panel-body">
                <h4 class="no-margin"><?php echo _l('mautic_bridge_sync_scope'); ?></h4>
                <hr class="hr-panel-heading" />
                <table class="table table-striped no-mtop">
                    <tbody>
                        <tr>
                            <td><?php echo _l('mautic_bridge_inbound_pending'); ?></td>
                            <td class="text-right"><?php echo (int) ($queue['inbound_pending_retry'] ?? 0); ?></td>
                        </tr>
                        <tr>
                            <td><?php echo _l('mautic_bridge_outbound_pending'); ?></td>
                            <td class="text-right"><?php echo (int) ($queue['outbound_pending_retry'] ?? 0); ?></td>
                        </tr>
                        <tr>
                            <td><?php echo _l('mautic_bridge_unmapped_leads'); ?></td>
                            <td class="text-right"><?php echo (int) ($perfex['unmapped_leads_with_email'] ?? 0); ?></td>
                        </tr>
                        <tr>
                            <td><?php echo _l('mautic_bridge_unmapped_contacts'); ?></td>
                            <td class="text-right"><?php echo (int) ($perfex['unmapped_contacts_with_email'] ?? 0); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="panel_s">
            <div class="panel-body">
                <h4 class="no-margin"><?php echo _l('mautic_bridge_health'); ?></h4>
                <hr class="hr-panel-heading" />
                <table class="table table-striped no-mtop">
                    <tbody>
                        <tr>
                            <td><?php echo _l('mautic_bridge_base_url'); ?></td>
                            <td class="text-right"><code><?php echo html_escape($settings['base_url'] ?? ''); ?></code></td>
                        </tr>
                        <tr>
                            <td><?php echo _l('mautic_bridge_dry_run'); ?></td>
                            <td class="text-right"><?php echo !empty($settings['dry_run']) ? _l('yes') : _l('no'); ?></td>
                        </tr>
                        <tr>
                            <td><?php echo _l('mautic_bridge_queue_failed'); ?></td>
                            <td class="text-right"><?php echo (int) ($queue['failed'] ?? 0); ?></td>
                        </tr>
                        <tr>
                            <td><?php echo _l('mautic_bridge_latest_log'); ?></td>
                            <td class="text-right"><?php echo html_escape($summary['latest_log']['message'] ?? '-'); ?></td>
                        </tr>
                    </tbody>
                </table>
                <a href="<?php echo admin_url('mautic_bridge_manage/queue'); ?>" class="btn btn-primary">
                    <i class="fa fa-list"></i> <?php echo _l('mautic_bridge_queue'); ?>
                </a>
                <a href="<?php echo admin_url('mautic_bridge_manage/backfill'); ?>" class="btn btn-default">
                    <i class="fa fa-database"></i> <?php echo _l('mautic_bridge_backfill'); ?>
                </a>
            </div>
        </div>
    </div>
</div>
<?php $this->load->view('mautic_bridge/manage/_footer'); ?>
