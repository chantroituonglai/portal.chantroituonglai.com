<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Mautic_bridge_cron
{
    private $CI;

    public function __construct()
    {
        $this->CI = &get_instance();
        $this->CI->load->model('mautic_bridge/Mautic_bridge_model', 'mauticBridgeModel');
    }

    public function run(int $limit = 25, bool $reconcile = true): array
    {
        $result = [
            'queue' => $this->CI->mauticBridgeModel->process_queue($limit),
        ];
        if ($reconcile) {
            $result['reconcile'] = $this->CI->mauticBridgeModel->reconcile_contacts(50);
        }

        return $result;
    }
}
