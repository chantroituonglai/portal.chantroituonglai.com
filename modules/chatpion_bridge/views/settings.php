<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-8">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="tw-mt-0 tw-mb-4"><?php echo _l('chatpion_bridge_settings_heading'); ?></h4>
                        <?php echo form_open(admin_url('chatpion_bridge')); ?>
                        <?php echo render_input(
                            'chatpion_bridge_base_url',
                            'chatpion_bridge_base_url_label',
                            $settings['chatpion_bridge_base_url'] ?? '',
                            'text',
                            ['placeholder' => 'https://your-chatpion-domain.com/api']
                        ); ?>

                        <?php echo render_input(
                            'chatpion_bridge_api_key',
                            'chatpion_bridge_api_key_label',
                            $settings['chatpion_bridge_api_key'] ?? ''
                        ); ?>

                        <div class="form-group tw-flex tw-items-center tw-justify-between">
                            <div class="tw-flex-1 tw-pr-2">
                                <?php echo render_input(
                                    'chatpion_bridge_api_timeout',
                                    'chatpion_bridge_api_timeout_label',
                                    (string) ($settings['chatpion_bridge_api_timeout'] ?? 30),
                                    'number',
                                    ['min' => 5, 'max' => 120]
                                ); ?>
                            </div>
                            <div class="tw-shrink-0">
                                <button type="button"
                                        id="chatpion-bridge-test-btn"
                                        class="btn btn-default">
                                    <i class="fa-regular fa-plug tw-mr-1"></i>
                                    <?php echo _l('chatpion_bridge_test_button'); ?>
                                </button>
                            </div>
                        </div>

                        <div id="chatpion-bridge-test-result" class="alert hide"></div>

                        <?php echo render_textarea(
                            'chatpion_bridge_default_workspace_template',
                            'chatpion_bridge_default_workspace_template_label',
                            $settings['chatpion_bridge_default_workspace_template'] ?? '',
                            ['rows' => 6]
                        ); ?>

                        <div class="checkbox checkbox-primary mtop15">
                            <input type="checkbox"
                                   id="chatpion_bridge_enable_logging"
                                   name="chatpion_bridge_enable_logging"
                                   value="1"
                                   <?php echo !empty($settings['chatpion_bridge_enable_logging']) ? 'checked' : ''; ?>>
                            <label for="chatpion_bridge_enable_logging">
                                <?php echo _l('chatpion_bridge_enable_logging_label'); ?>
                            </label>
                        </div>

                        <div class="tw-mt-6">
                            <button type="submit" class="btn btn-primary"><?php echo _l('submit'); ?></button>
                        </div>
                        <?php echo form_close(); ?>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="panel_s">
                    <div class="panel-body">
                        <h5 class="tw-mt-0 tw-mb-3"><?php echo _l('chatpion_bridge_settings_help_title'); ?></h5>
                        <p class="tw-text-sm tw-text-neutral-600">
                            <?php echo _l('chatpion_bridge_settings_help_description'); ?>
                        </p>
                        <p class="tw-text-sm tw-text-neutral-600">
                            <?php echo _l('chatpion_bridge_settings_help_workspace'); ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var $ = window.jQuery || window.$;
        if (!$) { return; }

        $('#chatpion-bridge-test-btn').on('click', function () {
            const $btn = $(this);
            const $icon = $btn.find('i');
            const $alert = $('#chatpion-bridge-test-result');

            $alert.addClass('hide').removeClass('alert-success alert-danger').text('');

            const payload = {
                base_url: $('input[name="chatpion_bridge_base_url"]').val(),
                api_key: $('input[name="chatpion_bridge_api_key"]').val(),
                timeout: $('input[name="chatpion_bridge_api_timeout"]').val(),
                enable_logging: $('#chatpion_bridge_enable_logging').is(':checked') ? 1 : 0,
            };

            $btn.prop('disabled', true);
            $icon.addClass('fa-spin');

            $.ajax({
                url: admin_url + 'chatpion_bridge/test_connection',
                type: 'POST',
                dataType: 'json',
                data: payload,
            }).done(function (res) {
                res = res || {};
                if (res.success) {
                    $alert.removeClass('hide').addClass('alert alert-success')
                        .text(res.message || '<?php echo _l('chatpion_bridge_test_success'); ?>');
                } else {
                    $alert.removeClass('hide').addClass('alert alert-danger')
                        .text(res.message || '<?php echo _l('chatpion_bridge_test_failed'); ?>');
                }
            }).fail(function () {
                $alert.removeClass('hide').addClass('alert alert-danger')
                    .text('<?php echo _l('chatpion_bridge_test_failed'); ?>');
            }).always(function () {
                $btn.prop('disabled', false);
                $icon.removeClass('fa-spin');
            });
        });
    });
</script>
<?php init_tail(); ?>
