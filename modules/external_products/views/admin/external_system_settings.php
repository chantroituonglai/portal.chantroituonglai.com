<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin"><?php echo _l('external_systems_configuration'); ?></h4>
                        <hr class="hr-panel-heading" />

                        <ul class="nav nav-tabs" role="tablist">
                            <li role="presentation" class="<?php echo $active_tab === 'haravan' ? 'active' : ''; ?>">
                                <a href="#tab-haravan" aria-controls="tab-haravan" role="tab" data-toggle="tab"><?php echo _l('haravan_api_settings'); ?></a>
                            </li>
                            <li role="presentation" class="<?php echo $active_tab === 'woocommerce' ? 'active' : ''; ?>">
                                <a href="#tab-woocommerce" aria-controls="tab-woocommerce" role="tab" data-toggle="tab">WooCommerce</a>
                            </li>
                            <li role="presentation" class="<?php echo $active_tab === 'shopify' ? 'active' : ''; ?>">
                                <a href="#tab-shopify" aria-controls="tab-shopify" role="tab" data-toggle="tab">Shopify</a>
                            </li>
                            <li role="presentation" class="<?php echo $active_tab === 'magento' ? 'active' : ''; ?>">
                                <a href="#tab-magento" aria-controls="tab-magento" role="tab" data-toggle="tab">Magento</a>
                            </li>
                        </ul>

                        <div class="tab-content mtop20">
                            <div role="tabpanel" class="tab-pane <?php echo $active_tab === 'haravan' ? 'active' : ''; ?>" id="tab-haravan">
                                <?php echo form_open(admin_url('external_products/external_system_settings?tab=haravan'), ['id' => 'haravan-settings-form']); ?>
                                <input type="hidden" name="system" value="haravan">
                                <div class="row">
                                    <div class="col-md-7">
                                        <div class="form-group">
                                            <div class="checkbox checkbox-primary">
                                                <input type="checkbox" id="haravan_api_enabled" name="settings[haravan_api_enabled]" value="1" <?php echo (int) $haravan['enabled'] === 1 ? 'checked' : ''; ?>>
                                                <label for="haravan_api_enabled"><?php echo _l('haravan_api_enabled'); ?></label>
                                            </div>
                                            <p class="text-muted no-mbot"><?php echo _l('haravan_api_help'); ?></p>
                                        </div>
                                        <div class="form-group">
                                            <label for="haravan_api_token" class="control-label"><?php echo _l('haravan_api_token'); ?> <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="haravan_api_token" name="settings[haravan_api_token]" value="<?php echo html_escape($haravan['token']); ?>" placeholder="DAC0F729FCC5E9633362DC94C3A6ECD800FE1071CC0FE361B6D86CFC6E3A9E87" autocomplete="off">
                                            <p class="text-muted no-mbot mtop5"><?php echo _l('haravan_token_help'); ?></p>
                                        </div>
                                        <div class="form-group">
                                            <label for="haravan_api_base_url" class="control-label"><?php echo _l('haravan_api_base_url'); ?></label>
                                            <input type="url" class="form-control" id="haravan_api_base_url" name="settings[haravan_api_base_url]" value="<?php echo html_escape($haravan['base_url'] ?: 'https://apis.haravan.com/com'); ?>" placeholder="https://apis.haravan.com/com">
                                            <p class="text-muted no-mbot mtop5"><?php echo _l('haravan_base_url_help'); ?></p>
                                        </div>
                                        <div class="btn-bottom-toolbar text-right">
                                            <button type="submit" class="btn btn-primary"><i class="fa-regular fa-floppy-disk"></i> <?php echo _l('save_settings'); ?></button>
                                        </div>
                                    </div>
                                    <div class="col-md-5">
                                        <div class="panel_s">
                                            <div class="panel-body">
                                                <h5 class="bold mtop0"><?php echo _l('test_haravan_connection'); ?></h5>
                                                <p class="text-muted small"><?php echo _l('test_haravan_connection'); ?></p>
                                                <div class="form-group">
                                                    <label for="haravan-test-sku" class="control-label"><?php echo _l('sku'); ?></label>
                                                    <input type="text" id="haravan-test-sku" class="form-control" placeholder="006439">
                                                </div>
                                                <button type="button" class="btn btn-info" onclick="testHaravanConnection();"><i class="fa fa-link"></i> <?php echo _l('test_connection'); ?></button>
                                                <div id="haravan-test-result" class="alert mtop15 hide" role="alert"></div>
                                            </div>
                                        </div>
                                        <div class="panel_s">
                                            <div class="panel-body">
                                                <h5 class="bold mtop0"><?php echo _l('sync_product_by_sku'); ?></h5>
                                                <p class="text-muted small"><?php echo _l('enter_sku_to_sync'); ?></p>
                                                <div class="form-group">
                                                    <label for="haravan-sync-sku" class="control-label"><?php echo _l('sku'); ?></label>
                                                    <input type="text" id="haravan-sync-sku" class="form-control" placeholder="<?php echo _l('enter_sku'); ?>">
                                                </div>
                                                <button type="button" class="btn btn-success" onclick="syncHaravanProduct();"><i class="fa fa-refresh"></i> <?php echo _l('sync_now'); ?></button>
                                                <div id="haravan-sync-result" class="alert mtop15 hide" role="alert"></div>
                                            </div>
                                        </div>
                                        <?php if (!empty($available_skus)) { ?>
                                            <div class="panel_s">
                                                <div class="panel-body">
                                                    <h5 class="bold mtop0"><?php echo _l('sync_from_external_products'); ?></h5>
                                                    <p class="text-muted small"><?php echo _l('select_external_product_sku'); ?></p>
                                                    <select id="haravan-external-sku" class="selectpicker" data-width="100%" data-live-search="true" title="<?php echo _l('select_external_product_sku'); ?>">
                                                        <?php foreach ($available_skus as $sku) { ?>
                                                            <option value="<?php echo html_escape($sku); ?>"><?php echo html_escape($sku); ?></option>
                                                        <?php } ?>
                                                    </select>
                                                    <button type="button" class="btn btn-primary mtop10" onclick="syncHaravanExternalSku();"><i class="fa fa-download"></i> <?php echo _l('sync_now'); ?></button>
                                                    <div id="haravan-external-sync-result" class="alert mtop15 hide" role="alert"></div>
                                                </div>
                                            </div>
                                        <?php } else { ?>
                                            <div class="panel_s">
                                                <div class="panel-body">
                                                    <h5 class="bold mtop0"><?php echo _l('sync_from_external_products'); ?></h5>
                                                    <p class="text-muted no-mbot"><?php echo _l('no_available_external_skus'); ?></p>
                                                </div>
                                            </div>
                                        <?php } ?>
                                    </div>
                                </div>
                                <?php echo form_close(); ?>
                            </div>

                            <div role="tabpanel" class="tab-pane <?php echo $active_tab === 'woocommerce' ? 'active' : ''; ?>" id="tab-woocommerce">
                                <?php echo form_open(admin_url('external_products/external_system_settings?tab=woocommerce')); ?>
                                <input type="hidden" name="system" value="woocommerce">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="woocommerce_api_url" class="control-label"><?php echo _l('woocommerce_api_url'); ?></label>
                                            <input type="url" id="woocommerce_api_url" name="settings[woocommerce_api_url]" class="form-control" value="<?php echo html_escape($woocommerce['api_url']); ?>" placeholder="https://example.com/wp-json/wc/v3/">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="woocommerce_api_key" class="control-label"><?php echo _l('woocommerce_api_key'); ?></label>
                                            <input type="text" id="woocommerce_api_key" name="settings[woocommerce_api_key]" class="form-control" value="<?php echo html_escape($woocommerce['api_key']); ?>" autocomplete="off">
                                        </div>
                                    </div>
                                </div>
                                <div class="btn-bottom-toolbar text-right">
                                    <button type="submit" class="btn btn-primary"><i class="fa-regular fa-floppy-disk"></i> <?php echo _l('save_settings'); ?></button>
                                </div>
                                <?php echo form_close(); ?>
                            </div>

                            <div role="tabpanel" class="tab-pane <?php echo $active_tab === 'shopify' ? 'active' : ''; ?>" id="tab-shopify">
                                <?php echo form_open(admin_url('external_products/external_system_settings?tab=shopify')); ?>
                                <input type="hidden" name="system" value="shopify">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="shopify_api_url" class="control-label"><?php echo _l('shopify_api_url'); ?></label>
                                            <input type="url" id="shopify_api_url" name="settings[shopify_api_url]" class="form-control" value="<?php echo html_escape($shopify['api_url']); ?>" placeholder="https://your-store.myshopify.com/admin/api/">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="shopify_access_token" class="control-label"><?php echo _l('shopify_access_token'); ?></label>
                                            <input type="text" id="shopify_access_token" name="settings[shopify_access_token]" class="form-control" value="<?php echo html_escape($shopify['access_token']); ?>" autocomplete="off">
                                        </div>
                                    </div>
                                </div>
                                <div class="btn-bottom-toolbar text-right">
                                    <button type="submit" class="btn btn-primary"><i class="fa-regular fa-floppy-disk"></i> <?php echo _l('save_settings'); ?></button>
                                </div>
                                <?php echo form_close(); ?>
                            </div>

                            <div role="tabpanel" class="tab-pane <?php echo $active_tab === 'magento' ? 'active' : ''; ?>" id="tab-magento">
                                <?php echo form_open(admin_url('external_products/external_system_settings?tab=magento')); ?>
                                <input type="hidden" name="system" value="magento">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="magento_api_url" class="control-label"><?php echo _l('magento_api_url'); ?></label>
                                            <input type="url" id="magento_api_url" name="settings[magento_api_url]" class="form-control" value="<?php echo html_escape($magento['api_url']); ?>" placeholder="https://your-magento.com/rest/V1/">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="magento_access_token" class="control-label"><?php echo _l('magento_access_token'); ?></label>
                                            <input type="text" id="magento_access_token" name="settings[magento_access_token]" class="form-control" value="<?php echo html_escape($magento['access_token']); ?>" autocomplete="off">
                                        </div>
                                    </div>
                                </div>
                                <div class="btn-bottom-toolbar text-right">
                                    <button type="submit" class="btn btn-primary"><i class="fa-regular fa-floppy-disk"></i> <?php echo _l('save_settings'); ?></button>
                                </div>
                                <?php echo form_close(); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
<script>
(function ($) {
    'use strict';

    init_selectpicker();

    window.testHaravanConnection = function testHaravanConnection() {
        var sku = $('#haravan-test-sku').val();

        $.ajax({
            url: '<?php echo admin_url('external_products/test_haravan_connection'); ?>',
            type: 'POST',
            data: { test_sku: sku },
            dataType: 'json',
            beforeSend: function () {
                renderHaravanFeedback('#haravan-test-result', 'info', '<?php echo _l('processing'); ?>');
            },
            success: function (response) {
                var type = response.success ? 'success' : 'danger';
                var message = response.message ? response.message : '<?php echo _l('error_processing_request'); ?>';
                renderHaravanFeedback('#haravan-test-result', type, message);
            },
            error: function () {
                renderHaravanFeedback('#haravan-test-result', 'danger', '<?php echo _l('error_processing_request'); ?>');
            }
        });
    };

    window.syncHaravanProduct = function syncHaravanProduct() {
        var sku = $('#haravan-sync-sku').val();

        if (!sku) {
            renderHaravanFeedback('#haravan-sync-result', 'warning', '<?php echo _l('enter_sku_to_sync'); ?>');
            return;
        }

        $.ajax({
            url: '<?php echo admin_url('external_products/sync_haravan_product'); ?>',
            type: 'POST',
            data: { sku: sku },
            dataType: 'json',
            beforeSend: function () {
                renderHaravanFeedback('#haravan-sync-result', 'info', '<?php echo _l('processing'); ?>');
            },
            success: function (response) {
                var type = response.success ? 'success' : 'danger';
                var message = response.message ? response.message : '<?php echo _l('error_processing_request'); ?>';
                renderHaravanFeedback('#haravan-sync-result', type, message);
            },
            error: function () {
                renderHaravanFeedback('#haravan-sync-result', 'danger', '<?php echo _l('error_processing_request'); ?>');
            }
        });
    };

    window.syncHaravanExternalSku = function syncHaravanExternalSku() {
        var $select = $('#haravan-external-sku');
        var sku = $select.val();

        if (!sku) {
            renderHaravanFeedback('#haravan-external-sync-result', 'warning', '<?php echo _l('select_external_product_sku'); ?>');
            return;
        }

        $.ajax({
            url: '<?php echo admin_url('external_products/sync_haravan_product'); ?>',
            type: 'POST',
            data: { sku: sku },
            dataType: 'json',
            beforeSend: function () {
                renderHaravanFeedback('#haravan-external-sync-result', 'info', '<?php echo _l('processing'); ?>');
            },
            success: function (response) {
                var type = response.success ? 'success' : 'danger';
                var message = response.message ? response.message : '<?php echo _l('error_processing_request'); ?>';
                renderHaravanFeedback('#haravan-external-sync-result', type, message);
                if (response.success) {
                    $select.find('option[value="' + sku.replace(/(["\\])/g, '\\$1') + '"]').remove();
                    $select.selectpicker('refresh');
                }
            },
            error: function () {
                renderHaravanFeedback('#haravan-external-sync-result', 'danger', '<?php echo _l('error_processing_request'); ?>');
            }
        });
    };

    function renderHaravanFeedback(selector, type, message) {
        $(selector)
            .removeClass('hide alert-info alert-success alert-danger alert-warning')
            .addClass('alert-' + type)
            .html(message)
            .removeClass('hide');
    }
})(jQuery);
</script>
