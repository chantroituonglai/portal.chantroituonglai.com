# Perfex CRM → ChatPion Webhook: Task Deleted

## Overview
When a task is deleted in Perfex CRM, it should notify ChatPion to remove the corresponding link record in the `perfex_crm_task_links` table. This ensures bidirectional sync.

## ChatPion API Endpoint

**Endpoint:** `https://chantroituonglai.net/api/perfex_task_deleted`

**Method:** POST

**Authentication:** API Key (from ChatPion user's Perfex CRM config)

### Request Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `api_key` | string | Yes | ChatPion API key (get from Perfex config) |
| `task_id` | int | Yes | Perfex CRM task ID |
| `project_id` | int | No | Perfex CRM project ID (optional) |

### Response Format

#### Success Response (Link Found & Deleted)
```json
{
  "status": "success",
  "message": "Task link removed successfully",
  "data": {
    "campaign_id": 88,
    "deleted": true
  }
}
```

#### Success Response (No Link Found)
```json
{
  "status": "success",
  "message": "No link found for this task",
  "data": {
    "deleted": false
  }
}
```

#### Error Response
```json
{
  "status": "error",
  "message": "Error description"
}
```

## Implementation in Perfex CRM

### Option 1: Using ChatPion Bridge Module Hook

Add this to `perfex_crm/modules/chatpion_bridge/chatpion_bridge.php`:

```php
<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Chatpion_bridge
{
    public function __construct()
    {
        // Register hook for task deletion
        hooks()->add_action('after_task_deleted', 'chatpion_bridge_task_deleted');
    }
}

/**
 * Hook: Called after a task is deleted
 * Notifies ChatPion to remove the link
 */
function chatpion_bridge_task_deleted($task_id)
{
    $CI =& get_instance();
    
    log_message('error', '[ChatPion Bridge] Task deleted: ' . $task_id);
    
    // Get task details before it's fully deleted (if still available)
    $CI->db->select('rel_id, rel_type');
    $CI->db->where('id', $task_id);
    $task = $CI->db->get(db_prefix() . 'tasks')->row();
    
    $project_id = null;
    if($task && $task->rel_type === 'project') {
        $project_id = $task->rel_id;
    }
    
    // Get ChatPion link to find user
    $CI->db->select('*');
    $CI->db->where('task_id', $task_id);
    $link = $CI->db->get(db_prefix() . 'chatpion_bridge_task_links')->row();
    
    if(!$link) {
        log_message('error', '[ChatPion Bridge] No ChatPion link found for task: ' . $task_id);
        return;
    }
    
    // Get ChatPion config for this user (assume user_id stored somewhere)
    // You may need to adjust this based on your data structure
    $chatpion_user_id = $link->user_id ?? null;
    
    if(!$chatpion_user_id) {
        log_message('error', '[ChatPion Bridge] No user ID found in link');
        return;
    }
    
    // Get ChatPion API endpoint and key from options or config
    // This should be stored when user configures Perfex integration in ChatPion
    $chatpion_url = get_option('chatpion_webhook_url') ?: 'https://chantroituonglai.net';
    $chatpion_api_key = get_option('chatpion_api_key_user_' . $chatpion_user_id);
    
    if(!$chatpion_api_key) {
        log_message('error', '[ChatPion Bridge] No API key found for ChatPion user: ' . $chatpion_user_id);
        return;
    }
    
    // Call ChatPion webhook
    $webhook_url = rtrim($chatpion_url, '/') . '/api/perfex_task_deleted';
    
    $post_data = [
        'api_key' => $chatpion_api_key,
        'task_id' => $task_id,
        'project_id' => $project_id
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $webhook_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if($error) {
        log_message('error', '[ChatPion Bridge] cURL error: ' . $error);
        return;
    }
    
    log_message('error', '[ChatPion Bridge] Webhook response HTTP: ' . $http_code);
    log_message('error', '[ChatPion Bridge] Webhook response body: ' . $response);
    
    $result = json_decode($response, true);
    
    if($result && isset($result['status']) && $result['status'] === 'success') {
        log_message('error', '[ChatPion Bridge] Successfully notified ChatPion of task deletion');
        
        // Also delete the local bridge link
        $CI->db->where('task_id', $task_id);
        $CI->db->delete(db_prefix() . 'chatpion_bridge_task_links');
    } else {
        log_message('error', '[ChatPion Bridge] Failed to notify ChatPion: ' . ($result['message'] ?? 'Unknown error'));
    }
}
```

### Option 2: Direct Implementation in Tasks Controller

If you don't want to use a module, add this directly to `perfex_crm/application/controllers/admin/Tasks.php`:

Find the `delete_task()` method and add webhook call before/after deletion:

```php
public function delete_task($id)
{
    // ... existing permission checks ...
    
    // Get task details before deletion
    $this->load->model('tasks_model');
    $task = $this->tasks_model->get($id);
    
    if($task) {
        $project_id = ($task->rel_type === 'project') ? $task->rel_id : null;
        
        // Delete the task
        $response = $this->tasks_model->delete_task($id);
        
        if($response) {
            // ✅ Call ChatPion webhook AFTER successful deletion
            $this->notify_chatpion_task_deleted($id, $project_id);
            
            set_alert('success', _l('deleted', _l('task')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('task_lowercase')));
        }
    }
    
    redirect($_SERVER['HTTP_REFERER']);
}

/**
 * Notify ChatPion that a task has been deleted
 */
private function notify_chatpion_task_deleted($task_id, $project_id = null)
{
    // Check if ChatPion bridge link exists
    $this->db->select('*');
    $this->db->where('task_id', $task_id);
    $link = $this->db->get(db_prefix() . 'chatpion_bridge_task_links')->row();
    
    if(!$link) {
        return; // No ChatPion link, skip webhook
    }
    
    // Get ChatPion config (you may need to adjust based on your setup)
    $chatpion_url = 'https://chantroituonglai.net';
    $chatpion_api_key = get_option('chatpion_api_key_user_' . $link->user_id);
    
    if(!$chatpion_api_key) {
        log_message('error', '[ChatPion] No API key found for user: ' . $link->user_id);
        return;
    }
    
    $webhook_url = $chatpion_url . '/api/perfex_task_deleted';
    
    $post_data = [
        'api_key' => $chatpion_api_key,
        'task_id' => $task_id,
        'project_id' => $project_id
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $webhook_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    log_message('error', '[ChatPion] Webhook response: ' . $response);
    
    // Delete local bridge link
    $this->db->where('task_id', $task_id);
    $this->db->delete(db_prefix() . 'chatpion_bridge_task_links');
}
```

## Storage of ChatPion API Key in Perfex CRM

You need to store the ChatPion API key when the integration is set up. Add a setting in ChatPion Bridge module:

### Create Settings Table

```sql
CREATE TABLE IF NOT EXISTS `tblchatpion_bridge_config` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL COMMENT 'ChatPion user ID',
  `api_key` VARCHAR(255) NOT NULL COMMENT 'ChatPion API key',
  `webhook_url` VARCHAR(500) NOT NULL DEFAULT 'https://chantroituonglai.net',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Store Config When Creating Task from ChatPion

When ChatPion creates a task, also pass the API key to store:

```php
// In ChatPion Bridge module when receiving task creation
function chatpion_bridge_store_config($user_id, $api_key)
{
    $CI =& get_instance();
    
    $config = [
        'user_id' => $user_id,
        'api_key' => $api_key,
        'webhook_url' => 'https://chantroituonglai.net',
        'is_active' => 1,
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    // Check if exists
    $CI->db->where('user_id', $user_id);
    $existing = $CI->db->get(db_prefix() . 'chatpion_bridge_config')->row();
    
    if($existing) {
        $CI->db->where('user_id', $user_id);
        $CI->db->update(db_prefix() . 'chatpion_bridge_config', $config);
    } else {
        $config['created_at'] = date('Y-m-d H:i:s');
        $CI->db->insert(db_prefix() . 'chatpion_bridge_config', $config);
    }
}
```

## Testing

### Test Webhook Manually

```bash
curl -X POST "https://chantroituonglai.net/api/perfex_task_deleted" \
  -d "api_key=YOUR_CHATPION_API_KEY" \
  -d "task_id=513" \
  -d "project_id=35"
```

### Expected Response

```json
{
  "status": "success",
  "message": "Task link removed successfully",
  "data": {
    "campaign_id": 88,
    "deleted": true
  }
}
```

## Flow Diagram

```
┌─────────────────┐
│  Perfex CRM     │
│  User deletes   │
│  Task #513      │
└────────┬────────┘
         │
         │ 1. delete_task() called
         │
         ▼
┌─────────────────────────────┐
│  Tasks Controller           │
│  - Delete task              │
│  - Call webhook             │
└────────┬────────────────────┘
         │
         │ 2. POST /api/perfex_task_deleted
         │    task_id=513
         │    api_key=xxx
         │
         ▼
┌─────────────────────────────┐
│  ChatPion API               │
│  - Validate API key         │
│  - Find link by task_id     │
│  - Delete from              │
│    perfex_crm_task_links    │
└────────┬────────────────────┘
         │
         │ 3. Response
         │    {"status":"success"}
         │
         ▼
┌─────────────────────────────┐
│  Perfex CRM                 │
│  - Also delete local        │
│    chatpion_bridge_task_    │
│    links record             │
└─────────────────────────────┘
```

## Security Considerations

1. **API Key Validation**: ChatPion validates the API key before processing
2. **User Isolation**: Only deletes links belonging to the authenticated user
3. **HTTPS**: Always use HTTPS for webhook calls
4. **Timeout**: Set reasonable timeout (10s) to prevent hanging
5. **Error Handling**: Log all errors for debugging

## Logging

Both systems log webhook activity:

### ChatPion Logs
```
[Perfex Webhook] Task deleted - Task ID: 513, Project ID: 35
[Perfex Webhook] Link deleted for campaign ID: 88
```

### Perfex CRM Logs
```
[ChatPion Bridge] Task deleted: 513
[ChatPion Bridge] Webhook response HTTP: 200
[ChatPion Bridge] Successfully notified ChatPion of task deletion
```

## Troubleshooting

### Issue: Webhook not called
- Check if `after_task_deleted` hook is registered
- Verify ChatPion Bridge module is active
- Check Perfex CRM error logs

### Issue: API key not found
- Ensure API key is stored when task is created
- Check `tblchatpion_bridge_config` table
- Verify user_id mapping is correct

### Issue: Link not deleted
- Check ChatPion error logs for validation errors
- Verify task_id is correct
- Test webhook manually with cURL

