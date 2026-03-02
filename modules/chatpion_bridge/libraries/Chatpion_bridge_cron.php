<?php

class Chatpion_bridge_cron
{
    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->model('chatpion_bridge/Chatpion_bridge_model', 'chatpionBridgeModel');
        $this->CI->load->model('chatpion_bridge/Chatpion_bridge_task_model', 'chatpionBridgeTaskModel');
    }

    public function run(int $batchSize = 25): void
    {
        $links = $this->CI->chatpionBridgeModel->get_links_for_sync($batchSize);
        foreach ($links as $link) {
            $campaignId = $link['campaign_id'] ?? null;
            $taskId     = (int) ($link['task_id'] ?? 0);

            if (! $campaignId || $taskId <= 0) {
                continue;
            }

            $this->CI->chatpionBridgeModel->log_activity('info', 'sync_start', [
                'task_id'    => $taskId,
                'campaign_id'=> $campaignId,
            ]);

            $campaignData = $this->CI->chatpionBridgeModel->fetch_campaign_status(
                $campaignId,
                $link['media_type'] ?? 'all'
            );
            if (! $campaignData) {
                $this->CI->chatpionBridgeModel->log_activity('error', 'sync_failed', [
                    'task_id'    => $taskId,
                    'campaign_id'=> $campaignId,
                    'message'    => 'Unable to fetch campaign data from Chatpion.',
                ]);
                continue;
            }

            $updated = $this->CI->chatpionBridgeModel->sync_campaign_status($taskId, $link, $campaignData);

            $this->CI->chatpionBridgeModel->log_activity($updated ? 'info' : 'error', 'sync_completed', [
                'task_id'    => $taskId,
                'campaign_id'=> $campaignId,
                'status'     => $campaignData['status']['code'] ?? null,
                'post_url'   => $campaignData['post_url'] ?? null,
            ]);
        }
    }
}
