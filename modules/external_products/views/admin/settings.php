<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
            <div class="row">
                <div class="col-md-12">
                    <div class="panel_s">
                        <div class="panel-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <h4 class="no-margin"><?php echo _l('external_products'); ?> - <?php echo _l('settings'); ?></h4>
                                    <hr class="hr-panel-heading" />
                                </div>
                            </div>
                            
                            <?php echo form_open(admin_url('external_products/settings'), ['id' => 'external_products_settings_form']); ?>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="external_products_enabled" class="control-label">
                                            <?php echo _l('enable_external_products_module'); ?>
                                        </label>
                                        <div class="checkbox">
                                            <input type="checkbox" id="external_products_enabled" name="external_products_enabled" 
                                                   value="1" <?php echo get_option('external_products_enabled') == 1 ? 'checked' : ''; ?>>
                                            <label for="external_products_enabled"></label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="external_products_auto_sync" class="control-label">
                                            <?php echo _l('enable_auto_sync'); ?>
                                        </label>
                                        <div class="checkbox">
                                            <input type="checkbox" id="external_products_auto_sync" name="external_products_auto_sync" 
                                                   value="1" <?php echo get_option('external_products_auto_sync') == 1 ? 'checked' : ''; ?>>
                                            <label for="external_products_auto_sync"></label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="external_products_sync_interval" class="control-label">
                                            <?php echo _l('sync_interval_minutes'); ?>
                                        </label>
                                        <input type="number" class="form-control" id="external_products_sync_interval" 
                                               name="external_products_sync_interval" 
                                               value="<?php echo get_option('external_products_sync_interval', 60); ?>" 
                                               min="1" max="1440">
                                        <small class="help-block"><?php echo _l('sync_interval_help_text'); ?></small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="external_products_default_mapping_status" class="control-label">
                                            <?php echo _l('default_mapping_status'); ?>
                                        </label>
                                        <select class="form-control" id="external_products_default_mapping_status" 
                                                name="external_products_default_mapping_status">
                                            <option value="pending" <?php echo get_option('external_products_default_mapping_status') == 'pending' ? 'selected' : ''; ?>>
                                                <?php echo _l('pending'); ?>
                                            </option>
                                            <option value="active" <?php echo get_option('external_products_default_mapping_status') == 'active' ? 'selected' : ''; ?>>
                                                <?php echo _l('active'); ?>
                                            </option>
                                            <option value="inactive" <?php echo get_option('external_products_default_mapping_status') == 'inactive' ? 'selected' : ''; ?>>
                                                <?php echo _l('inactive'); ?>
                                            </option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-12">
                                    <h5><?php echo _l('external_systems_configuration'); ?></h5>
                                    <hr />
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="woocommerce_api_url" class="control-label">
                                            <?php echo _l('woocommerce_api_url'); ?>
                                        </label>
                                        <input type="url" class="form-control" id="woocommerce_api_url" 
                                               name="woocommerce_api_url" 
                                               value="<?php echo get_option('woocommerce_api_url'); ?>"
                                               placeholder="https://yourstore.com/wp-json/wc/v3/">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="woocommerce_api_key" class="control-label">
                                            <?php echo _l('woocommerce_api_key'); ?>
                                        </label>
                                        <input type="text" class="form-control" id="woocommerce_api_key" 
                                               name="woocommerce_api_key" 
                                               value="<?php echo get_option('woocommerce_api_key'); ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="shopify_api_url" class="control-label">
                                            <?php echo _l('shopify_api_url'); ?>
                                        </label>
                                        <input type="url" class="form-control" id="shopify_api_url" 
                                               name="shopify_api_url" 
                                               value="<?php echo get_option('shopify_api_url'); ?>"
                                               placeholder="https://yourstore.myshopify.com/admin/api/2023-01/">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="shopify_access_token" class="control-label">
                                            <?php echo _l('shopify_access_token'); ?>
                                        </label>
                                        <input type="text" class="form-control" id="shopify_access_token" 
                                               name="shopify_access_token" 
                                               value="<?php echo get_option('shopify_access_token'); ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-12">
                                    <h5><?php echo _l('mapping_statistics'); ?></h5>
                                    <hr />
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="panel_s">
                                        <div class="panel-body text-center">
                                            <h3 class="text-info"><?php echo get_external_products_count(); ?></h3>
                                            <p class="text-muted"><?php echo _l('total_external_products'); ?></p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="panel_s">
                                        <div class="panel-body text-center">
                                            <h3 class="text-success"><?php echo get_mapped_products_count(); ?></h3>
                                            <p class="text-muted"><?php echo _l('mapped_products'); ?></p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="panel_s">
                                        <div class="panel-body text-center">
                                            <h3 class="text-warning"><?php echo get_unmapped_products_count(); ?></h3>
                                            <p class="text-muted"><?php echo _l('unmapped_products'); ?></p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="panel_s">
                                        <div class="panel-body text-center">
                                            <h3 class="text-primary">
                                                <?php 
                                                $total = get_external_products_count();
                                                $mapped = get_mapped_products_count();
                                                echo $total > 0 ? round(($mapped / $total) * 100, 1) : 0;
                                                ?>%
                                            </h3>
                                            <p class="text-muted"><?php echo _l('mapping_percentage'); ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-12">
                                    <h5><?php echo _l('haravan_api_settings'); ?></h5>
                                    <hr />
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="haravan_api_enabled" class="control-label">
                                            <?php echo _l('haravan_api_enabled'); ?>
                                        </label>
                                        <div class="checkbox">
                                            <input type="checkbox" id="haravan_api_enabled" name="haravan_api_enabled"
                                                   value="1" <?php echo get_option('haravan_api_enabled') == 1 ? 'checked' : ''; ?>>
                                            <label for="haravan_api_enabled"></label>
                                        </div>
                                        <small class="help-block"><?php echo _l('haravan_api_help'); ?></small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="haravan_api_base_url" class="control-label">
                                            <?php echo _l('haravan_api_base_url'); ?>
                                        </label>
                                        <input type="url" class="form-control" id="haravan_api_base_url"
                                               name="haravan_api_base_url"
                                               value="<?php echo get_option('haravan_api_base_url', 'https://apis.haravan.com/com'); ?>"
                                               placeholder="https://apis.haravan.com/com">
                                        <small class="help-block"><?php echo _l('haravan_base_url_help'); ?></small>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="haravan_api_token" class="control-label">
                                            <?php echo _l('haravan_api_token'); ?> <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="haravan_api_token"
                                               name="haravan_api_token"
                                               value="<?php echo get_option('haravan_api_token'); ?>"
                                               placeholder="DAC0F729FCC5E9633362DC94C3A6ECD800FE1071CC0FE361B6D86CFC6E3A9E87">
                                        <small class="help-block"><?php echo _l('haravan_token_help'); ?></small>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <h6><?php echo _l('test_haravan_connection'); ?></h6>
                                    <div class="form-group">
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="test_sku" placeholder="006439" value="006439">
                                            <div class="input-group-btn">
                                                <button type="button" class="btn btn-info" onclick="testHaravanConnection()">
                                                    <i class="fa fa-refresh"></i> <?php echo _l('test_connection'); ?>
                                                </button>
                                            </div>
                                        </div>
                                        <small class="help-block"><?php echo _l('enter_sku'); ?></small>
                                    </div>
                                    <div id="testResult" class="alert" style="display: none;"></div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <h6><?php echo _l('sync_haravan_product'); ?></h6>
                                    <div class="form-group">
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="sync_sku" placeholder="006439">
                                            <div class="input-group-btn">
                                                <button type="button" class="btn btn-success" onclick="syncHaravanProduct()">
                                                    <i class="fa fa-download"></i> <?php echo _l('sync_now'); ?>
                                                </button>
                                            </div>
                                        </div>
                                        <small class="help-block"><?php echo _l('enter_sku_to_sync'); ?></small>
                                    </div>
                                    <div id="syncResult" class="alert" style="display: none;"></div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <button type="submit" class="btn btn-info">
                                            <i class="fa fa-save"></i> <?php echo _l('save_settings'); ?>
                                        </button>
                                        <a href="<?php echo admin_url('external_products'); ?>" class="btn btn-default">
                                            <i class="fa fa-arrow-left"></i> <?php echo _l('back_to_external_products'); ?>
                                        </a>
                                        <a href="<?php echo admin_url('external_products/haravan_products'); ?>" class="btn btn-success pull-right">
                                            <i class="fa fa-list"></i> <?php echo _l('haravan_products'); ?>
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <?php echo form_close(); ?>
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
    $('#external_products_settings_form').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            beforeSend: function() {
                $('button[type="submit"]').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Saving...');
            },
            success: function(response) {
                alert_float('success', 'Settings saved successfully');
            },
            error: function() {
                alert_float('danger', 'An error occurred while saving settings');
            },
            complete: function() {
                $('button[type="submit"]').prop('disabled', false).html('<i class="fa fa-save"></i> <?php echo _l('save_settings'); ?>');
            }
        });
    });
});

function testHaravanConnection() {
    var sku = $('#test_sku').val() || '006439';

    $.ajax({
        url: '<?php echo admin_url('external_products/test_haravan_connection'); ?>',
        type: 'POST',
        data: { test_sku: sku },
        dataType: 'json',
        beforeSend: function() {
            showTestResult('info', 'Testing connection...');
        },
        success: function(response) {
            if (response.success) {
                showTestResult('success', response.message);
            } else {
                showTestResult('danger', response.message);
            }
        },
        error: function() {
            showTestResult('danger', 'Connection test failed');
        }
    });
}

function syncHaravanProduct() {
    var sku = $('#sync_sku').val();

    if (!sku) {
        showSyncResult('danger', 'Please enter SKU to sync');
        return;
    }

    $.ajax({
        url: '<?php echo admin_url('external_products/sync_haravan_product'); ?>',
        type: 'POST',
        data: { sku: sku },
        dataType: 'json',
        beforeSend: function() {
            showSyncResult('info', 'Syncing product...');
        },
        success: function(response) {
            if (response.success) {
                showSyncResult('success', response.message);
                $('#sync_sku').val('');
            } else {
                showSyncResult('danger', response.message);
            }
        },
        error: function() {
            showSyncResult('danger', 'Sync failed');
        }
    });
}

function showTestResult(type, message) {
    $('#testResult').removeClass('alert-success alert-danger alert-info').addClass('alert-' + type).html(message).show();
}

function showSyncResult(type, message) {
    $('#syncResult').removeClass('alert-success alert-danger alert-info').addClass('alert-' + type).html(message).show();
}
</script>
