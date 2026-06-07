<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php $this->load->view('mautic_bridge/manage/_header'); ?>
<div class="mautic-bridge-toolbar">
    <div class="form-group">
        <select id="mautic-bridge-filter-rel-type" class="selectpicker" data-width="180px">
            <option value=""><?php echo _l('mautic_bridge_all_rel_types'); ?></option>
            <option value="lead">lead</option>
            <option value="contact">contact</option>
        </select>
    </div>
</div>
<?php
render_datatable([
    'ID',
    _l('mautic_bridge_mautic_contact_id'),
    _l('mautic_bridge_rel_type'),
    _l('mautic_bridge_rel_id'),
    _l('email'),
    _l('mautic_bridge_mautic_modified_at'),
    _l('mautic_bridge_perfex_modified_at'),
    _l('mautic_bridge_last_synced_at'),
    _l('created_at'),
    _l('updated_at'),
    _l('options'),
], 'mautic-bridge-mappings');
?>
<?php $this->load->view('mautic_bridge/manage/_footer'); ?>
