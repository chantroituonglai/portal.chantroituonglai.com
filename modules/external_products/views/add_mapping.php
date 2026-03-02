<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <?php $this->load->view('admin/includes/aside'); ?>
    <div class="content-wrapper">
        <?php $this->load->view('admin/includes/header'); ?>
        <div class="content">
            <div class="row">
                <div class="col-md-12">
                    <div class="panel_s">
                        <div class="panel-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <h4 class="no-margin"><?php echo _l('add_external_product'); ?></h4>
                                    <hr class="hr-panel-heading" />
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-8 col-md-offset-2">
                                    <?php echo form_open(admin_url('external_products/add_mapping'), ['id' => 'add_mapping_form']); ?>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="sku" class="control-label"><?php echo _l('sku'); ?> <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="sku" name="sku" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="mapping_id" class="control-label"><?php echo _l('mapping_id'); ?> <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" id="mapping_id" name="mapping_id" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <label for="mapping_type" class="control-label"><?php echo _l('mapping_type'); ?> <span class="text-danger">*</span></label>
                                                <select class="form-control" id="mapping_type" name="mapping_type" required>
                                                    <option value=""><?php echo _l('select_option'); ?></option>
                                                    <option value="fast_barco">Fast Barco</option>
                                                    <option value="aeon_sku">AEON SKU</option>
                                                    <option value="emart">Emart</option>
                                                    <option value="emart_sku">Emart SKU</option>
                                                    <option value="woo">WooCommerce</option>
                                                    <option value="shopify">Shopify</option>
                                                    <option value="magento">Magento</option>
                                                    <option value="amazon">Amazon</option>
                                                    <option value="ebay">eBay</option>
                                                    <option value="other">Other</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-group">
                                                <button type="submit" class="btn btn-info"><?php echo _l('submit'); ?></button>
                                                <a href="<?php echo admin_url('external_products/mapping'); ?>" class="btn btn-default"><?php echo _l('cancel'); ?></a>
                                            </div>
                                        </div>
                                    </div>
                                    <?php echo form_close(); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php $this->load->view('admin/includes/footer'); ?>
    </div>
</div>
<?php init_tail(); ?>
<script>
$(function() {
    $('#add_mapping_form').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                var result = JSON.parse(response);
                if (result.success) {
                    alert_float('success', result.message);
                    window.location.href = '<?php echo admin_url('external_products/mapping'); ?>';
                } else {
                    alert_float('danger', result.message);
                }
            },
            error: function() {
                alert_float('danger', 'An error occurred while processing your request');
            }
        });
    });
});
</script>
</body>
</html>
