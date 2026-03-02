<?php

// Add PDF Customizer module's settings link
hooks()->add_action('admin_init', function () {
    get_instance()->app_menu->add_setup_menu_item('custom_pdf', [
        'slug'     => 'custom_pdf_settinfs',
        'name'     => _l('custom_pdf'),
        'icon'     => '',
        'href'     => admin_url('custom_pdf/settings'),
        'position' => 35,
    ]);
    // \modules\custom_pdf\core\Apiinit::ease_of_mind(CUSTOM_PDF_MODULE);
});
