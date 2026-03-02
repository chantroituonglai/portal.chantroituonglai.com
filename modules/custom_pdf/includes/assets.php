<?php

hooks()->add_action('app_admin_head', 'custom_pdf_add_head_components');
function custom_pdf_add_head_components()
{
    custom_pdf_items_table_custom_style_render();
}

hooks()->add_action('app_admin_footer', function () {
    // Check if the 'custom_pdf' module is active
    if (get_instance()->app_modules->is_active('custom_pdf')) {
        // Generate the URL for the 'custom_pdf.js' script file
        $script_url = module_dir_url('custom_pdf', 'assets/js/custom_pdf.js');

        // Get the core version from the application's scripts
        $core_version = get_instance()->app_scripts->core_version();

        // Echo the script tag to include 'custom_pdf.js' with a version parameter
        echo '<script src="'.$script_url.'?v='.$core_version.'"></script>';
    }

    //\modules\custom_pdf\core\Apiinit::ease_of_mind(CUSTOM_PDF_MODULE);
});

hooks()->add_action('app_init', CUSTOM_PDF_MODULE.'_actLib');
function custom_pdf_actLib()
{
    //$CI = &get_instance();
    //$CI->load->library(CUSTOM_PDF_MODULE.'/Custom_pdf_aeiou');
    //$envato_res = $CI->custom_pdf_aeiou->validatePurchase(CUSTOM_PDF_MODULE);
    //if (!$envato_res) {
    //    set_alert('danger', 'One of your modules failed its verification and got deactivated. Please reactivate or contact support.');
    //}
}

hooks()->add_action('pre_activate_module', CUSTOM_PDF_MODULE.'_sidecheck');
function custom_pdf_sidecheck($module_name)
{
    /*
    if (CUSTOM_PDF_MODULE == $module_name['system_name']) {
        modules\custom_pdf\core\Apiinit::activate($module_name);
    }
    */
}

hooks()->add_action('pre_deactivate_module', CUSTOM_PDF_MODULE.'_deregister');
function custom_pdf_deregister($module_name)
{
    if (CUSTOM_PDF_MODULE == $module_name['system_name']) {
        delete_option(CUSTOM_PDF_MODULE.'_verification_id');
        delete_option(CUSTOM_PDF_MODULE.'_last_verification');
        delete_option(CUSTOM_PDF_MODULE.'_product_token');
        delete_option(CUSTOM_PDF_MODULE.'_heartbeat');
    }
}
//\modules\custom_pdf\core\Apiinit::ease_of_mind(CUSTOM_PDF_MODULE);
