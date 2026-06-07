<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Mautic_bridge_webhook extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('app_object_cache');
        $this->load->model('mautic_bridge/Mautic_bridge_model', 'mauticBridgeModel');
        $this->load->helper('mautic_bridge/mautic_bridge');
    }

    public function contact()
    {
        if ((int) get_option('mautic_bridge_enabled') !== 1) {
            mautic_bridge_json_response(['status' => false, 'message' => 'bridge disabled'], 503);
            return;
        }

        $rawPayload = (string) $this->input->raw_input_stream;
        if (!$this->verify_contact_request($rawPayload)) {
            $this->mauticBridgeModel->log('warning', 'webhook_secret_invalid', ['ip' => $this->input->ip_address()]);
            mautic_bridge_json_response(['status' => false, 'message' => 'unauthorized'], 401);
            return;
        }

        $payload = json_decode($rawPayload, true);
        if (!is_array($payload)) {
            $payload = $this->input->post(null, true);
        }
        if (!is_array($payload) || empty($payload)) {
            mautic_bridge_json_response(['status' => false, 'message' => 'invalid payload'], 400);
            return;
        }

        $result = $this->mauticBridgeModel->handle_mautic_webhook_events($payload);
        mautic_bridge_json_response(['status' => true, 'data' => $result], 202);
    }

    public function process()
    {
        $secret = $this->extract_secret();
        if (!$this->mauticBridgeModel->verify_webhook_secret($secret)) {
            mautic_bridge_json_response(['status' => false, 'message' => 'unauthorized'], 401);
            return;
        }

        $limit = (int) $this->input->get('limit');
        $reconcile = (int) $this->input->get('reconcile') === 1;
        $result = ['queue' => $this->mauticBridgeModel->process_queue($limit ?: 25)];
        if ($reconcile) {
            $result['reconcile'] = $this->mauticBridgeModel->reconcile_contacts(50);
        }
        mautic_bridge_json_response(['status' => true, 'data' => $result], 200);
    }

    private function extract_secret(): ?string
    {
        $headers = function_exists('apache_request_headers') ? apache_request_headers() : [];
        foreach ($headers as $key => $value) {
            $lower = strtolower((string) $key);
            if (in_array($lower, ['x-mautic-bridge-secret', 'x-webhook-secret'], true)) {
                return trim((string) $value);
            }
        }
        $querySecret = $this->input->get('secret', true);
        if ($querySecret) {
            return trim((string) $querySecret);
        }

        return null;
    }

    private function verify_contact_request(string $rawPayload): bool
    {
        $secret = $this->extract_secret();
        if ($this->mauticBridgeModel->verify_webhook_secret($secret)) {
            return true;
        }

        $signature = $this->extract_header('Webhook-Signature');
        $webhookSecret = (string) get_option('mautic_bridge_webhook_secret');
        if ($signature === null || $webhookSecret === '' || $rawPayload === '') {
            return false;
        }

        $expected = base64_encode(hash_hmac('sha256', $rawPayload, $webhookSecret, true));

        return hash_equals($expected, trim($signature));
    }

    private function extract_header(string $headerName): ?string
    {
        $headers = function_exists('apache_request_headers') ? apache_request_headers() : [];
        foreach ($headers as $key => $value) {
            if (strtolower((string) $key) === strtolower($headerName)) {
                return trim((string) $value);
            }
        }

        return null;
    }
}
