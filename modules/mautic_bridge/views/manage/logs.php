<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php $this->load->view('mautic_bridge/manage/_header'); ?>
<div class="mautic-bridge-toolbar">
    <div class="form-group">
        <select id="mautic-bridge-filter-level" class="selectpicker" data-width="160px">
            <option value=""><?php echo _l('mautic_bridge_all_levels'); ?></option>
            <option value="info">info</option>
            <option value="warning">warning</option>
            <option value="error">error</option>
        </select>
    </div>
</div>
<?php
render_datatable([
    'ID',
    _l('mautic_bridge_level'),
    _l('mautic_bridge_message'),
    _l('mautic_bridge_context'),
    _l('created_at'),
], 'mautic-bridge-logs');
?>
<?php $this->load->view('mautic_bridge/manage/_footer'); ?>
