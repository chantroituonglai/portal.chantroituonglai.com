<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Chatpion_bridge_model extends App_Model
{
    protected $logTable = 'chatpion_bridge_logs';
    protected $linkTable = 'chatpion_bridge_task_links';

    /**
     * Return global settings stored in options table
     */
    public function get_global_settings(): array
    {
        return [
            'chatpion_bridge_base_url'                  => (string) get_option('chatpion_bridge_base_url'),
            'chatpion_bridge_api_key'                   => (string) get_option('chatpion_bridge_api_key'),
            'chatpion_bridge_api_timeout'               => (int) get_option('chatpion_bridge_api_timeout') ?: 30,
            'chatpion_bridge_enable_logging'            => (int) get_option('chatpion_bridge_enable_logging'),
            'chatpion_bridge_default_workspace_template'=> (string) get_option('chatpion_bridge_default_workspace_template'),
        ];
    }

    public function call_chatpion_api(string $endpoint, array $params = [], string $method = 'GET', ?array $body = null, array $overrides = [])
    {
        $settings = $this->get_global_settings();
        $baseUrl  = $overrides['base_url'] ?? $settings['chatpion_bridge_base_url'];
        $apiKey   = $overrides['api_key'] ?? $settings['chatpion_bridge_api_key'];
        $timeout  = (int) ($overrides['timeout'] ?? $settings['chatpion_bridge_api_timeout'] ?? 30);
        $enableLogging = (int) ($overrides['enable_logging'] ?? $settings['chatpion_bridge_enable_logging']);

        $baseUrl = rtrim($baseUrl, '/');
        $method  = strtoupper($method);

        $params['api_key'] = $params['api_key'] ?? $apiKey;
        $url = $baseUrl . '/' . ltrim($endpoint, '/');
        if ($method === 'GET') {
            $query = http_build_query($params);
            $url .= (strpos($url, '?') === false ? '?' : '&') . $query;
        }

        $attempts = (int) ($overrides['attempts'] ?? 3);
        $delayMs  = (int) ($overrides['delay_ms'] ?? 500);

        $response = null;
        $bodyString = $body !== null ? json_encode($body, JSON_UNESCAPED_UNICODE) : null;

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            $ch = curl_init($url);
            $opts = [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT        => max(5, $timeout),
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            ];

            if ($method !== 'GET') {
                $opts[CURLOPT_CUSTOMREQUEST] = $method;
                $payload = $bodyString ?? json_encode($params, JSON_UNESCAPED_UNICODE);
                $opts[CURLOPT_POSTFIELDS] = $payload;
            }

            curl_setopt_array($ch, $opts);

            $raw     = curl_exec($ch);
            $http    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlErr = curl_error($ch);
            curl_close($ch);

            if ($curlErr !== '') {
                $this->log_activity('error', 'curl_error', [
                    'endpoint' => $endpoint,
                    'attempt'  => $attempt,
                    'error'    => $curlErr,
                ]);
                usleep($delayMs * 1000);
                $delayMs *= 2;
                continue;
            }

            $decoded = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $response = [
                    'http_code' => $http,
                    'body'      => $decoded,
                ];
                if ($enableLogging) {
                    $this->log_activity('info', 'chatpion_api_response', [
                        'endpoint' => $endpoint,
                        'http_code'=> $http,
                        'body'     => $decoded,
                    ]);
                }
                break;
            }

            $this->log_activity('error', 'invalid_json', [
                'endpoint' => $endpoint,
                'attempt'  => $attempt,
                'snippet'  => is_string($raw) ? substr(trim(strip_tags($raw)), 0, 150) : $raw,
            ]);
            usleep($delayMs * 1000);
            $delayMs *= 2;
        }

        if ($response === null) {
            return ['http_code' => null, 'body' => null];
        }

        return $response;
    }

    public function fetch_campaign_status($campaignId, string $mediaType = 'all')
    {
        $mediaType = strtolower(trim($mediaType));
        if (!in_array($mediaType, ['facebook', 'instagram', 'all'], true)) {
            $mediaType = 'all';
        }
        $response = $this->call_chatpion_api(
            "instagram_campaign/{$campaignId}",
            ['media_type' => $mediaType]
        );
        if (!isset($response['body']['status']) || $response['body']['status'] !== 'success') {
            return null;
        }

        return $response['body']['data'] ?? null;
    }

    public function sync_campaign_status(int $taskId, array $linkRow, array $campaignData): bool
    {
        $fieldsToUpdate = [
            'last_status'    => $campaignData['status']['code'] ?? null,
            'post_url'       => $campaignData['post_url'] ?? null,
            'media_type'     => $campaignData['media_type'] ?? ($campaignData['media']['type'] ?? null),
            'last_synced_at' => date('Y-m-d H:i:s'),
        ];

        if (!empty($campaignData['workspace'])) {
            $fieldsToUpdate['workspace_json'] = json_encode($campaignData['workspace'], JSON_UNESCAPED_UNICODE);
        }

        if (!empty($campaignData['account']['id'])) {
            $fieldsToUpdate['account_id'] = $campaignData['account']['id'];
        }

        $this->db->where('task_id', $taskId)
            ->update(db_prefix() . $this->linkTable, $fieldsToUpdate);

        return $this->db->affected_rows() >= 0;
    }

    public function get_links_for_sync($limit = 50)
    {
        $this->db->select('*')
            ->from(db_prefix() . $this->linkTable)
            ->order_by('COALESCE(last_synced_at, created_at)', 'ASC')
            ->limit($limit);

        return $this->db->get()->result_array();
    }

    public function log_activity(string $level, string $message, array $context = []): void
    {
        $data = [
            'level'   => $level,
            'message' => $message,
            'context' => !empty($context) ? json_encode($context, JSON_UNESCAPED_UNICODE) : null,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        if (!empty($context['task_id'])) {
            $data['task_id'] = $context['task_id'];
        }
        if (!empty($context['campaign_id'])) {
            $data['campaign_id'] = $context['campaign_id'];
        }

        $this->db->insert(db_prefix() . $this->logTable, $data);
    }

    public function format_task_link(?array $link): ?array
    {
        if (!is_array($link)) {
            return null;
        }

        $workspace = $link['workspace'] ?? null;
        if ($workspace === null && !empty($link['workspace_json'])) {
            $decoded = json_decode($link['workspace_json'], true);
            $workspace = json_last_error() === JSON_ERROR_NONE ? $decoded : $link['workspace_json'];
        }

        $statusKey = $this->map_status_key($link['last_status'] ?? null);

        return [
            'campaign_id'    => $link['campaign_id'] ?? null,
            'account_id'     => $link['account_id'] ?? null,
            'status'         => [
                'code'  => (string) ($link['last_status'] ?? ''),
                'key'   => $statusKey,
                'label' => $this->map_status_label($link['last_status'] ?? null),
            ],
            'post_url'       => $link['post_url'] ?? null,
            'media_type'     => $link['media_type'] ?? null,
            'last_synced_at' => $link['last_synced_at'] ?? null,
            'created_at'     => $link['created_at'] ?? null,
            'updated_at'     => $link['updated_at'] ?? null,
            'workspace'      => $workspace,
        ];
    }

    public function map_status_label($code): string
    {
        return ucfirst($this->map_status_key($code));
    }

    public function map_status_key($status): string
    {
        switch ((string) $status) {
            case '2':
                return 'completed';
            case '1':
                return 'processing';
            default:
                return 'pending';
        }
    }

    /**
     * Persist settings to options table
     */
    public function save_global_settings(array $settings): void
    {
        $baseUrl = rtrim($settings['base_url'] ?? '', '/');
        update_option('chatpion_bridge_base_url', $baseUrl);

        update_option('chatpion_bridge_api_key', $settings['api_key'] ?? '');

        $timeout = (int) ($settings['api_timeout'] ?? 30);
        if ($timeout <= 0) {
            $timeout = 30;
        }
        update_option('chatpion_bridge_api_timeout', $timeout);

        update_option('chatpion_bridge_enable_logging', !empty($settings['enable_logging']) ? 1 : 0);

        update_option(
            'chatpion_bridge_default_workspace_template',
            trim((string) ($settings['workspace_template'] ?? ''))
        );
    }

    public function test_connection($baseUrl, $apiKey, int $timeout = 30): array
    {
        $baseUrl = trim($baseUrl);
        $apiKey  = trim($apiKey);

        if ($baseUrl === '') {
            return [
                'success' => false,
                'message' => _l('chatpion_bridge_test_failed') . ' (missing base URL)',
            ];
        }

        if ($apiKey === '') {
            return [
                'success' => false,
                'message' => _l('chatpion_bridge_test_failed') . ' (missing API key)',
            ];
        }

        $endpoint = rtrim($baseUrl, '/') . '/instagram_campaigns';
        $query    = http_build_query([
            'api_key'    => $apiKey,
            'limit'      => 1,
            'media_type' => 'all',
        ]);
        $url = $endpoint . '?' . $query;

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => max(5, $timeout),
            CURLOPT_CONNECTTIMEOUT => 5,
        ]);

        $body     = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr !== '') {
            return [
                'success' => false,
                'message' => sprintf('%s (%s)', _l('chatpion_bridge_test_failed'), $curlErr),
            ];
        }

        if ($httpCode >= 400 || $body === false) {
            return [
                'success' => false,
                'message' => sprintf('%s (HTTP %s)', _l('chatpion_bridge_test_failed'), $httpCode),
            ];
        }

        $decoded = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $snippet = '';
            if (is_string($body)) {
                $snippet = trim(strip_tags($body));
                if (strlen($snippet) > 150) {
                    $snippet = substr($snippet, 0, 150) . '…';
                }
            }
            return [
                'success' => false,
                'message' => _l('chatpion_bridge_test_failed') . ' (invalid JSON response' . ($snippet ? ': ' . $snippet : '') . ')',
            ];
        }

        if (isset($decoded['status']) && $decoded['status'] === 'success') {
            return [
                'success' => true,
                'message' => _l('chatpion_bridge_test_success'),
                'data'    => $decoded,
            ];
        }

        $message = $decoded['message'] ?? 'Unknown response.';

        return [
            'success' => false,
            'message' => sprintf('%s (%s)', _l('chatpion_bridge_test_failed'), $message),
            'data'    => $decoded,
        ];
    }
}
