<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php $this->load->view('mautic_bridge/manage/_header'); ?>
<?php
$preview = $summary['preview'] ?? [];
$perfex = $preview['perfex'] ?? [];
$mautic = $preview['mautic'] ?? [];
?>
<?php if (empty($settings['dry_run'])) { ?>
    <div class="alert alert-warning">
        <?php echo _l('mautic_bridge_dry_run_required'); ?>
    </div>
<?php } ?>
<div class="row">
    <div class="col-md-5">
        <div class="panel_s">
            <div class="panel-body">
                <h4 class="no-margin"><?php echo _l('mautic_bridge_enqueue_backfill'); ?></h4>
                <hr class="hr-panel-heading" />
                <?php echo form_open('', ['id' => 'mautic-bridge-backfill-form']); ?>
                <?php
                echo render_select('direction', [
                    ['id' => 'inbound', 'name' => _l('mautic_bridge_backfill_inbound')],
                    ['id' => 'outbound_leads', 'name' => _l('mautic_bridge_backfill_outbound_leads')],
                    ['id' => 'outbound_contacts', 'name' => _l('mautic_bridge_backfill_outbound_contacts')],
                ], ['id', 'name'], 'mautic_bridge_direction', 'inbound');
                echo render_input('limit', 'mautic_bridge_batch_limit', 50, 'number', ['min' => 1, 'max' => 200]);
                echo render_input('page', 'mautic_bridge_mautic_page', 1, 'number', ['min' => 1]);
                ?>
                <div class="checkbox checkbox-primary" id="mautic-bridge-auto-pages-wrap">
                    <input type="checkbox" id="mautic_bridge_auto_pages" name="auto_pages" value="1" checked>
                    <label for="mautic_bridge_auto_pages"><?php echo _l('mautic_bridge_auto_pages'); ?></label>
                </div>
                <button type="submit" class="btn btn-primary mautic-bridge-dry-guard">
                    <i class="fa fa-plus"></i> <?php echo _l('mautic_bridge_enqueue'); ?>
                </button>
                <button type="button" class="btn btn-default hide" id="mautic-bridge-stop-backfill">
                    <i class="fa fa-stop"></i> <?php echo _l('mautic_bridge_stop'); ?>
                </button>
                <?php echo form_close(); ?>
                <div id="mautic-bridge-backfill-progress" class="alert alert-info mtop15 hide"></div>
            </div>
        </div>
    </div>
    <div class="col-md-7">
        <div class="panel_s">
            <div class="panel-body">
                <h4 class="no-margin"><?php echo _l('mautic_bridge_backfill_preview'); ?></h4>
                <hr class="hr-panel-heading" />
                <table class="table table-striped no-mtop">
                    <tbody>
                        <tr>
                            <td><?php echo _l('mautic_bridge_mautic_contacts'); ?></td>
                            <td class="text-right"><?php echo isset($mautic['total_contacts']) && $mautic['total_contacts'] !== null ? (int) $mautic['total_contacts'] : '-'; ?></td>
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
                <p class="text-muted">
                    <?php echo _l('mautic_bridge_backfill_note'); ?>
                </p>
            </div>
        </div>
    </div>
</div>
<?php $this->load->view('mautic_bridge/manage/_footer'); ?>
