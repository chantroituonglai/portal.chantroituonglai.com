<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php $this->load->view('mautic_bridge/manage/_header'); ?>
<div class="mautic-bridge-toolbar">
    <button type="button" class="btn btn-primary" id="mautic-bridge-sync-campaigns">
        <i class="fa fa-refresh"></i> <?php echo _l('mautic_bridge_sync_campaigns'); ?>
    </button>
</div>
<?php
render_datatable([
    _l('mautic_bridge_campaign_id'),
    _l('mautic_bridge_campaign_alias'),
    _l('mautic_bridge_campaign_name'),
    _l('mautic_bridge_perfex_tag'),
    _l('mautic_bridge_synced_at'),
], 'mautic-bridge-campaigns');
?>
<?php $this->load->view('mautic_bridge/manage/_footer'); ?>
