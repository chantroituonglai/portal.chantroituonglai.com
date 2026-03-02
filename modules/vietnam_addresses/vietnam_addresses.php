<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Vietnam Addresses
Description: Provide Vietnam provinces and districts selectors to replace manual address entry when country is Vietnam.
Version: 1.0.0
Requires at least: 3.2.*
*/

define('VIETNAM_ADDRESSES_MODULE_NAME', 'vietnam_addresses');

register_activation_hook(VIETNAM_ADDRESSES_MODULE_NAME, 'vietnam_addresses_activation_hook');

function vietnam_addresses_activation_hook(): void
{
    vietnam_addresses_ensure_datasets();
}

function vietnam_addresses_ensure_datasets(): void
{
    $CI = &get_instance();

    $dataDir = module_dir_path(VIETNAM_ADDRESSES_MODULE_NAME, 'assets/data/');
    if (!is_dir($dataDir)) {
        @mkdir($dataDir, 0755, true);
    }

    $files = [
        'cities.json'   => 'https://raw.githubusercontent.com/PhilDevs94/Vietnam-location-json/master/cities.json',
        'districts.json'=> 'https://raw.githubusercontent.com/PhilDevs94/Vietnam-location-json/master/districts.json',
        'wards.json'    => 'https://raw.githubusercontent.com/PhilDevs94/Vietnam-location-json/master/wards.json',
        'dvhcvn.json'   => 'https://raw.githubusercontent.com/navuitag/dvhcvn/master/dvhcvn.json',
    ];

    foreach ($files as $name => $url) {
        $target = $dataDir . $name;
        if (!file_exists($target) || filesize($target) < 1000) {
            $content = @file_get_contents($url);
            if ($content === false) {
                // Try cURL as fallback
                if (function_exists('curl_init')) {
                    $ch = curl_init($url);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
                    $content = curl_exec($ch);
                    curl_close($ch);
                }
            }
            if ($content !== false && $content !== null) {
                @file_put_contents($target, $content);
            }
        }
    }
}

// Also ensure datasets on admin init in case activation missed or files removed
hooks()->add_action('admin_init', 'vietnam_addresses_ensure_datasets');

// Add tiny inline markers to confirm embedding and expose data base URL
hooks()->add_action('app_admin_head', function () {
    $base = module_dir_url(VIETNAM_ADDRESSES_MODULE_NAME, 'assets/data/');
    echo '<script>window.VNA_DATA_BASE_URL = ' . json_encode($base) . '; console.log("[VN-Addr] admin head hook, data base:", window.VNA_DATA_BASE_URL);</script>';
});
hooks()->add_action('app_customers_head', function () {
    $base = module_dir_url(VIETNAM_ADDRESSES_MODULE_NAME, 'assets/data/');
    echo '<script>window.VNA_DATA_BASE_URL = ' . json_encode($base) . '; console.log("[VN-Addr] customers head hook, data base:", window.VNA_DATA_BASE_URL);</script>';
});

// Inject assets in Admin and Clients area (head + footer for reliability)
hooks()->add_action('app_admin_head', 'vietnam_addresses_load_assets');
hooks()->add_action('app_admin_footer', 'vietnam_addresses_load_assets');
hooks()->add_action('app_customers_head', 'vietnam_addresses_load_assets');
hooks()->add_action('app_customers_footer', 'vietnam_addresses_load_assets');

function vietnam_addresses_load_assets(): void
{
    $rev = '102';
    echo '<script>console.log("[VN-Addr] enqueue script");</script>';
    echo '<script src="' . module_dir_url(VIETNAM_ADDRESSES_MODULE_NAME, 'assets/js/vna_addresses.js') . '?v=' . $rev . '"></script>';
}