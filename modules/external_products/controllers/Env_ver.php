<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Env_ver extends AdminController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        show_404();
    }

    public function activate()
    {
        if (!has_permission('external_products', '', 'create')) {
            access_denied('external_products');
        }

        $res = [
            'status' => true,
            'message' => 'External Mapping Management module activated successfully',
            'original_url' => admin_url('external_products')
        ];

        echo json_encode($res);
    }
    
    public function upgrade_database()
    {
        if (!has_permission('external_products', '', 'edit')) {
            access_denied('external_products');
        }

        $res = [
            'status' => true,
            'message' => 'External Mapping Management is up to date',
            'original_url' => admin_url('external_products')
        ];

        echo json_encode($res);
    }

    public function deactivate()
    {
        if (!has_permission('external_products', '', 'delete')) {
            access_denied('external_products');
        }

        // Disable module options
        update_option('external_products_enabled', 0);
        
        $res = [
            'status' => true,
            'message' => 'External Mapping Management module deactivated successfully',
            'original_url' => admin_url('modules')
        ];

        echo json_encode($res);
    }

    public function get_module_info()
    {
        $info = [
            'name' => 'External Mapping Management',
            'version' => '2.0.0',
            'author' => 'Future Horizon Ltd Company',
            'author_uri' => 'https://www.chantroituonglai.com',
            'description' => 'Manage external products, orders and their mapping with internal systems',
            'requires_upgrade' => false
        ];

        echo json_encode($info);
    }
}
