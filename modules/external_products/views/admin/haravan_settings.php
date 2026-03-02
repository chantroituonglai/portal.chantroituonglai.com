<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <div class="clearfix mtop5 mbot20">
                            <h4 class="no-margin pull-left"><?php echo _l('haravan_api_settings'); ?></h4>
                            <div class="pull-right">
                                <a href="<?php echo admin_url('external_products/haravan_products'); ?>" class="btn btn-info">
                                    <i class="fa fa-list"></i> <?php echo _l('haravan_products'); ?>
                                </a>
                                <a href="<?php echo admin_url('external_products'); ?>" class="btn btn-default">
                                    <i class="fa fa-arrow-left"></i> <?php echo _l('back_to_external_products'); ?>
                                </a>
                            </div>
                        </div>
                        <hr class="hr-panel-heading" />

                        <?php echo form_open(admin_url('external_products/haravan_settings'), ['id' => 'haravan-settings-form']); ?>
                        <div class="row">
                            <div class="col-md-7">
                                <div class="row">
                                    <div class="col-md-12">
                                        <?php echo render_yes_no_option('haravan_api_enabled', 'haravan_api_enabled', 'haravan_api_help'); ?>
                                        <p class="text-muted mtop10"><?php echo _l('haravan_api_help'); ?></p>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <?php echo render_input('settings[haravan_api_token]', 'haravan_api_token', $haravan_token, 'text', [
                                            'placeholder'   => 'DAC0F729FCC5E9633362DC94C3A6ECD800FE1071CC0FE361B6D86CFC6E3A9E87',
                                            'autocomplete'   => 'off',
                                            'data-toggle'    => 'tooltip',
                                            'data-title'     => _l('haravan_token_help'),
                                        ]); ?>
                                        <p class="text-muted mtop5"><?php echo _l('haravan_token_help'); ?></p>
                                    </div>
                                    <div class="col-md-12">
                                        <?php echo render_input('settings[haravan_api_base_url]', 'haravan_api_base_url', $haravan_base_url ?: 'https://apis.haravan.com/com', 'url', [
                                            'placeholder' => 'https://apis.haravan.com/com',
                                            'data-toggle' => 'tooltip',
                                            'data-title'  => _l('haravan_base_url_help'),
                                        ]); ?>
                                        <p class="text-muted mtop5"><?php echo _l('haravan_base_url_help'); ?></p>
                                    </div>
                                </div>
                                <div class="btn-bottom-toolbar text-right mtop20">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fa-regular fa-floppy-disk"></i> <?php echo _l('save_settings'); ?>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="panel_s">
                                    <div class="panel-body">
                                        <h5 class="bold mtop0"><?php echo _l('test_haravan_connection'); ?></h5>
                                        <p class="text-muted small"><?php echo _l('test_haravan_connection'); ?></p>
                                        <?php echo render_input('haravan_test_sku', 'sku', '', 'text', [
                                            'id'          => 'haravan-test-sku',
                                            'placeholder' => '006439',
                                        ]); ?>
                                        <button type="button" class="btn btn-info" onclick="testHaravanConnection();">
                                            <i class="fa fa-link"></i> <?php echo _l('test_connection'); ?>
                                        </button>
                                        <div id="haravan-test-result" class="alert mtop15 hide" role="alert"></div>
                                    </div>
                                </div>
                                <div class="panel_s">
                                    <div class="panel-body">
                                        <h5 class="bold mtop0"><?php echo _l('sync_haravan_product'); ?></h5>
                                        <p class="text-muted small"><?php echo _l('enter_sku_to_sync'); ?></p>
                                        <?php echo render_input('haravan_sync_sku', 'sku', '', 'text', [
                                            'id'          => 'haravan-sync-sku',
                                            'placeholder' => _l('enter_sku'),
                                        ]); ?>
                                        <button type="button" class="btn btn-success" onclick="syncHaravanProduct();">
                                            <i class="fa fa-refresh"></i> <?php echo _l('sync_now'); ?>
                                        </button>
                                        <div id="haravan-sync-result" class="alert mtop15 hide" role="alert"></div>
                                    </div>
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
<?php init_tail(); ?>
<script>
(function($) {
    'use strict';

    appValidateForm($('#haravan-settings-form'), {
        'settings[haravan_api_token]': 'required',
        'settings[haravan_api_base_url]': 'required'
    });

    function renderFeedback($el, type, message) {
        $el
            .removeClass('hide alert-info alert-success alert-danger alert-warning')
            .addClass('alert-' + type)
            .html(message)
            .removeClass('hide');
    }

    window.testHaravanConnection = function testHaravanConnection() {
        var sku = $('#haravan-test-sku').val();

        $.ajax({
            url: admin_url + 'external_products/test_haravan_connection',
            type: 'POST',
            data: { test_sku: sku },
            dataType: 'json',
            beforeSend: function() {
                renderFeedback($('#haravan-test-result'), 'info', '<?php echo _l('processing'); ?>');
            },
            success: function(response) {
                var type = response.success ? 'success' : 'danger';
                var message = response.message ? response.message : '<?php echo _l('error_processing_request'); ?>';
                renderFeedback($('#haravan-test-result'), type, message);
            },
            error: function() {
                renderFeedback($('#haravan-test-result'), 'danger', '<?php echo _l('error_processing_request'); ?>');
            }
        });
    };

    window.syncHaravanProduct = function syncHaravanProduct() {
        var sku = $('#haravan-sync-sku').val();

        if (!sku) {
            renderFeedback($('#haravan-sync-result'), 'warning', '<?php echo _l('enter_sku_to_sync'); ?>');
            return;
        }

        $.ajax({
            url: admin_url + 'external_products/sync_haravan_product',
            type: 'POST',
            data: { sku: sku },
            dataType: 'json',
            beforeSend: function() {
                renderFeedback($('#haravan-sync-result'), 'info', '<?php echo _l('processing'); ?>');
            },
            success: function(response) {
                var type = response.success ? 'success' : 'danger';
                var message = response.message ? response.message : '<?php echo _l('error_processing_request'); ?>';
                renderFeedback($('#haravan-sync-result'), type, message);
            },
            error: function() {
                renderFeedback($('#haravan-sync-result'), 'danger', '<?php echo _l('error_processing_request'); ?>');
            }
        });
    };
})(jQuery);
</script>
</body>
</html>
