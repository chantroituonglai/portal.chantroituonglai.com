<?php

defined('BASEPATH') or exit('No direct script access allowed');

if (!function_exists('chatpion_bridge_cron')) {
    /**
     * Cron entry point to sync Chatpion campaign statuses.
     *
     * @param int $batchSize
     * @return void
     */
    function chatpion_bridge_cron($batchSize = 25)
    {
        $CI =& get_instance();
        $CI->load->library('chatpion_bridge/Chatpion_bridge_cron', null, 'chatpionBridgeCron');
        $CI->chatpionBridgeCron->run($batchSize);
    }
}

