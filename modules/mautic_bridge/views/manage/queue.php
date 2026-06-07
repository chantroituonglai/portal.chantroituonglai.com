<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php $this->load->view('mautic_bridge/manage/_header'); ?>
<div class="mautic-bridge-toolbar">
    <div class="form-group">
        <select id="mautic-bridge-filter-status" class="selectpicker" data-width="160px">
            <option value=""><?php echo _l('mautic_bridge_all_statuses'); ?></option>
            <option value="pending">pending</option>
            <option value="retry">retry</option>
            <option value="failed">failed</option>
            <option value="done">done</option>
        </select>
    </div>
    <div class="form-group">
        <select id="mautic-bridge-filter-direction" class="selectpicker" data-width="160px">
            <option value=""><?php echo _l('mautic_bridge_all_directions'); ?></option>
            <option value="inbound">inbound</option>
            <option value="outbound">outbound</option>
        </select>
    </div>
    <input type="number" id="mautic-bridge-process-limit" class="form-control" value="25" min="1" max="100" style="width:100px;">
    <button type="button" class="btn btn-primary" id="mautic-bridge-process-due">
        <i class="fa fa-play"></i> <?php echo _l('mautic_bridge_process_due'); ?>
    </button>
    <button type="button" class="btn btn-default" id="mautic-bridge-process-selected">
        <i class="fa fa-check-square-o"></i> <?php echo _l('mautic_bridge_process_selected'); ?>
    </button>
</div>
<?php
render_datatable([
    '<span class="hide">-</span>',
    'ID',
    _l('mautic_bridge_direction'),
    _l('mautic_bridge_event_type'),
    _l('mautic_bridge_status'),
    _l('email'),
    _l('mautic_bridge_rel_type'),
    _l('mautic_bridge_rel_id'),
    _l('mautic_bridge_mautic_contact_id'),
    _l('mautic_bridge_attempts'),
    _l('mautic_bridge_next_attempt'),
    _l('mautic_bridge_last_error'),
    _l('created_at'),
    _l('updated_at'),
    _l('options'),
], 'mautic-bridge-queue');
?>
<?php $this->load->view('mautic_bridge/manage/_footer'); ?>
