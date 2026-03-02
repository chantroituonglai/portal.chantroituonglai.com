<?php
defined('BASEPATH') or exit('No direct script access allowed');

// Load debug helper
if (!function_exists('pa_log_error')) {
    require_once(__DIR__ . '/project_agent_debug_helper.php');
}

function pa_progress_dir() {
    $base = FCPATH . 'temp' . DIRECTORY_SEPARATOR . 'project_agent_progress';
    // pa_log_error('pa_progress_dir - Base path: ' . $base);
    if (!is_dir($base)) { 
        // pa_log_error('pa_progress_dir - Directory does not exist, creating...');
        $result = @mkdir($base, 0777, true);
        // pa_log_error('pa_progress_dir - Directory creation result: ' . ($result ? 'SUCCESS' : 'FAILED'));
        if ($result) {
            // pa_log_error('pa_progress_dir - Directory created successfully');
        }
    } else {
        // pa_log_error('pa_progress_dir - Directory already exists');
    }
    return $base;
}

function pa_progress_path($token) {
    $safe = preg_replace('/[^a-zA-Z0-9_\-]/', '_', (string)$token);
    return pa_progress_dir() . DIRECTORY_SEPARATOR . $safe . '.log';
}

function project_agent_progress_init($token) {
    $path = pa_progress_path($token);
    // pa_log_error('project_agent_progress_init - Token: ' . $token . ', Path: ' . $path);
    $result = @file_put_contents($path, json_encode(['ts'=>time(),'event'=>'start']) . "\n");
    // pa_log_error('project_agent_progress_init - File write result: ' . ($result !== false ? 'SUCCESS' : 'FAILED'));
    if ($result !== false) {
        // pa_log_error('project_agent_progress_init - File created successfully, size: ' . filesize($path) . ' bytes');
    }
}

function project_agent_progress_add($token, $event, $data = []) {
    if (!$token) { 
        // pa_log_error('project_agent_progress_add - No token provided, skipping');
        return; 
    }
    $path = pa_progress_path($token);
    $row = ['ts' => time(), 'token' => (string)$token, 'event' => (string)$event, 'data' => $data];
    // pa_log_error('project_agent_progress_add - Token: ' . $token . ', Event: ' . $event . ', Path: ' . $path);
    $result = @file_put_contents($path, json_encode($row) . "\n", FILE_APPEND);
    // pa_log_error('project_agent_progress_add - File append result: ' . ($result !== false ? 'SUCCESS' : 'FAILED'));
    if ($result !== false && is_file($path)) {
        // pa_log_error('project_agent_progress_add - File size after append: ' . filesize($path) . ' bytes');
    }
    // Push realtime via Pusher if enabled
    pa_progress_push($token, $row);
}

function project_agent_progress_complete($token, $summary = []) {
    project_agent_progress_add($token, 'complete', ['summary' => $summary]);
}

function project_agent_progress_read($token) {
    $path = pa_progress_path($token);
    // pa_log_error('project_agent_progress_read - Token: ' . $token . ', Path: ' . $path);
    if (!is_file($path)) { 
        // pa_log_error('project_agent_progress_read - File does not exist');
        return ['events'=>[], 'done'=>false]; 
    }
    // pa_log_error('project_agent_progress_read - File exists, size: ' . filesize($path) . ' bytes');
    $lines = @file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    // pa_log_error('project_agent_progress_read - Lines read: ' . count($lines));
    $events = [];
    $done = false;
    foreach ($lines as $ln) {
        $obj = json_decode($ln, true);
        if (is_array($obj)) {
            $events[] = $obj;
            if (isset($obj['event']) && $obj['event'] === 'complete') { $done = true; }
        }
    }
    // pa_log_error('project_agent_progress_read - Events parsed: ' . count($events) . ', Done: ' . ($done ? 'true' : 'false'));
    return ['events' => $events, 'done' => $done];
}

function pa_pusher_enabled() {
    try {
        return (int) get_option('pusher_realtime_notifications') === 1
            && get_option('pusher_app_key') && get_option('pusher_app_secret') && get_option('pusher_app_id');
    } catch (\Throwable $e) { return false; }
}

function pa_progress_push($token, array $payload) {
    if (!pa_pusher_enabled()) { 
        log_message('error', '[PA][pusher] Pusher not enabled - skipping event: ' . $payload['event']);
        return; 
    }
    try {
        $CI = &get_instance();
        $CI->load->library('app_pusher');
        $channel = 'project-agent-' . preg_replace('/[^a-zA-Z0-9_\-]/','_', (string)$token);
        
        // Log detailed payload information
        log_message('error', '[PA][pusher] ===== PUSHER PAYLOAD DEBUG =====');
        log_message('error', '[PA][pusher] Token: ' . $token);
        log_message('error', '[PA][pusher] Channel: ' . $channel);
        log_message('error', '[PA][pusher] Event name: ' . $payload['event']);
        log_message('error', '[PA][pusher] Full payload: ' . json_encode($payload, JSON_PRETTY_PRINT));
        log_message('error', '[PA][pusher] Payload keys: ' . implode(', ', array_keys($payload)));
        log_message('error', '[PA][pusher] ======================================');
        
        // If payload is too large (e.g. ai_final), only send a small signal and let client fetch full result via AJAX
        $eventName = $payload['event'];
        $jsonPayload = json_encode($payload);
        $isTooLarge = strlen($jsonPayload) > 3500 || $eventName === 'ai_final';
        log_message('error', '[PA][pusher] Payload size: ' . strlen($jsonPayload) . ' bytes; TooLarge: ' . ($isTooLarge ? 'YES' : 'NO'));
        if ($isTooLarge && $eventName === 'ai_final') {
            $signal = [
                'ts' => isset($payload['ts']) ? $payload['ts'] : time(),
                'token' => $token,
                'event' => 'ai_final_ready',
                'data' => ['token' => $token]
            ];
            log_message('error', '[PA][pusher] Broadcasting ai_final_ready signal to channel: ' . $channel);
            $CI->app_pusher->trigger($channel, 'ai_final_ready', $signal);
        } else {
            log_message('error', '[PA][pusher] Broadcasting event: ' . $eventName . ' to channel: ' . $channel);
            $CI->app_pusher->trigger($channel, $eventName, $payload);
        }
        log_message('error', '[PA][pusher] Event broadcasted successfully: ' . $payload['event']);
        // Broadcast to per-user channel if available
        try {
            $uid = null;
            if (isset($payload['data']['user_id'])) { $uid = (int)$payload['data']['user_id']; }
            elseif (isset($payload['data']['by'])) { $uid = (int)$payload['data']['by']; }
            if ($uid) {
                $uch = 'project-agent-user-' . $uid;
                log_message('error', '[PA][pusher] Broadcasting event to user channel: ' . $uch);
                if ($isTooLarge && $eventName === 'ai_final') {
                    $signal['event'] = 'ai_final_ready';
                    $CI->app_pusher->trigger($uch, 'ai_final_ready', $signal);
                } else {
                    $CI->app_pusher->trigger($uch, $eventName, $payload);
                }
            }
        } catch (\Throwable $e) { log_message('debug','[PA][pusher] user-channel push failed: '.$e->getMessage()); }
        
        // Test global channel communication
        // log_message('error', '[PA][pusher] ===== GLOBAL TEST CHANNEL =====');
        // log_message('error', '[PA][pusher] Sending test event to global_test channel');
        // $CI->app_pusher->trigger('global_test', 'global_event', [
        //     'timestamp' => time(),
        //     'message' => 'Test from pa_progress_push',
        //     // 'original_event' => $payload['event'],
        //     // 'original_token' => $token
        // ]);
        // log_message('error', '[PA][pusher] Global test event sent successfully');
        // log_message('error', '[PA][pusher] ================================');
        
    } catch (\Throwable $e) {
        log_message('error', '[PA][pusher] Error broadcasting event ' . $payload['event'] . ': ' . $e->getMessage());
        log_message('error', '[PA][pusher] Error stack trace: ' . $e->getTraceAsString());
    }
}

// ---- Simple async job helpers ----
function pa_job_path($token) {
    $safe = preg_replace('/[^a-zA-Z0-9_\-]/', '_', (string)$token);
    return pa_progress_dir() . DIRECTORY_SEPARATOR . $safe . '.job.json';
}

function project_agent_job_save($token, array $data) {
    $path = pa_job_path($token);
    // pa_log_error('project_agent_job_save - Token: ' . $token . ', Path: ' . $path);
    $result = @file_put_contents($path, json_encode($data));
    // pa_log_error('project_agent_job_save - File write result: ' . ($result !== false ? 'SUCCESS' : 'FAILED'));
    if ($result !== false) {
        // pa_log_error('project_agent_job_save - File created successfully, size: ' . filesize($path) . ' bytes');
    }
}

function project_agent_job_load($token) {
    $p = pa_job_path($token);
    // pa_log_error('project_agent_job_load - Token: ' . $token . ', Path: ' . $p);
    if (!is_file($p)) {
        // pa_log_error('project_agent_job_load - Job file does not exist');
        return null;
    }
    // pa_log_error('project_agent_job_load - Job file exists, size: ' . filesize($p) . ' bytes');
    $raw = @file_get_contents($p);
    $dec = json_decode($raw, true);
    // pa_log_error('project_agent_job_load - JSON decode result: ' . (is_array($dec) ? 'SUCCESS' : 'FAILED'));
    return is_array($dec) ? $dec : null;
}

function pa_lock_path($token) {
    $safe = preg_replace('/[^a-zA-Z0-9_\-]/', '_', (string)$token);
    return pa_progress_dir() . DIRECTORY_SEPARATOR . $safe . '.lock';
}

function pa_lock_acquire($token) {
    $p = pa_lock_path($token);
    if (is_file($p)) {
        // stale lock older than 10 minutes -> release
        $age = time() - @filemtime($p);
        if ($age > 600) { @unlink($p); }
        else { return false; }
    }
    return @file_put_contents($p, (string)time()) !== false;
}

function pa_lock_release($token) {
    @unlink(pa_lock_path($token));
}

function pa_job_result_path($token) {
    $safe = preg_replace('/[^a-zA-Z0-9_\-]/', '_', (string)$token);
    return pa_progress_dir() . DIRECTORY_SEPARATOR . $safe . '.result.json';
}

function project_agent_job_result_save($token, array $data) {
    $p = pa_job_result_path($token);
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    return @file_put_contents($p, $json) !== false;
}

function project_agent_job_result_load($token) {
    $p = pa_job_result_path($token);
    if (!is_file($p)) {
        return null;
    }
    $raw = @file_get_contents($p);
    $dec = json_decode($raw, true);
    return is_array($dec) ? $dec : null;
}
