<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="row">
    <div class="col-md-12">
        <!-- Debug Panel Settings  -->
        <h4 class="bold"><?php echo _l('topics_debug_settings'); ?></h4>
        <hr class="hr-panel-separator" />
        <?php render_yes_no_option(
            'topics_debug_panel_enabled',
            'topics_enable_debug_panel',
            get_option('topics_debug_panel_enabled')
        ); ?>

        <!-- Online Tracking Settings -->
        <h4 class="bold"><?php echo _l('topics_online_tracking'); ?></h4>
        <hr class="hr-panel-separator" />

        <?php render_yes_no_option(
            'topics_online_tracking_enabled',
            'topics_online_tracking_enabled',
            get_option('topics_online_tracking_enabled')
        ); ?>
        <hr />

        <?php echo render_input(
            'settings[topics_online_timeout]',
            'topics_online_timeout',
            get_option('topics_online_timeout'),
            'number',
            ['min' => 0]
        ); ?>
        <hr />

        <!-- N8n Integration Settings -->
        <h4 class="bold mt-4"><?php echo _l('topics_n8n_integration'); ?></h4>
        <hr class="hr-panel-separator" />

        <?php echo render_input(
            'settings[topics_n8n_host]',
            'topics_n8n_host',
            get_option('topics_n8n_host'),
            'text',
            ['placeholder' => 'https://n8n.yourdomain.com']
        ); ?>
        <hr />

        <?php echo render_input(
            'settings[topics_n8n_api_key]',
            'topics_n8n_api_key',
            get_option('topics_n8n_api_key'),
            'password',
            ['placeholder' => 'n8n_api_xxxxx']
        ); ?>
        <hr />
        <?php echo render_input(
            'settings[topics_n8n_webhook_url]',
            'topics_n8n_webhook_url',
            get_option('topics_n8n_webhook_url'),
            'text',
            ['placeholder' => 'https://n8n.yourdomain.com/webhook/']
        ); ?>
    </div>
</div>