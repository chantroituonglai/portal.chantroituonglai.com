<?php

defined('BASEPATH') or exit('No direct script access allowed');

if (!function_exists('mautic_bridge_cron')) {
    function mautic_bridge_cron($limit = 25, $reconcile = true): array
    {
        $CI = &get_instance();
        $CI->load->library('mautic_bridge/Mautic_bridge_cron', null, 'mauticBridgeCron');

        return $CI->mauticBridgeCron->run((int) $limit, (bool) $reconcile);
    }
}
