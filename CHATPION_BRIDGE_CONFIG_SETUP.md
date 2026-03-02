# ChatPion Bridge Config Setup

## Purpose
Store ChatPion API credentials in Perfex CRM so that webhooks can be sent back to ChatPion when tasks are deleted.

## Database Schema

```sql
CREATE TABLE IF NOT EXISTS `tblchatpion_bridge_config` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `chatpion_user_id` INT(11) NOT NULL COMMENT 'ChatPion user ID',
  `api_key` VARCHAR(255) NOT NULL COMMENT 'ChatPion API key for this user',
  `webhook_url` VARCHAR(500) NOT NULL DEFAULT 'https://chantroituonglai.net',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `chatpion_user_id` (`chatpion_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## How It Works

### 1. When ChatPion Creates a Task

ChatPion passes additional parameter `chatpion_api_key` along with task data:

```php
// In ChatPion: Instagram_poster.php
$task_data = [
    'name' => $task_name,
    'description' => $task_description,
    // ... other fields ...
    'chatpion_campaign_id' => $campaign_id,
    'chatpion_user_id' => $user_id,
    'chatpion_api_key' => $this->get_user_api_key($user_id), // NEW!
    'source' => 'chatpion'
];
```

### 2. Perfex CRM API Receives and Stores Config

In `perfex_crm/modules/api/controllers/Tasks.php`:

```php
public function data_post()
{
    // ... existing validation ...
    
    // Store ChatPion config if provided
    $chatpion_user_id = $this->Api_model->value($this->input->post('chatpion_user_id', TRUE));
    $chatpion_api_key = $this->Api_model->value($this->input->post('chatpion_api_key', TRUE));
    
    if(!empty($chatpion_user_id) && !empty($chatpion_api_key)) {
        $this->store_chatpion_config($chatpion_user_id, $chatpion_api_key);
    }
    
    // ... continue with task creation ...
}

private function store_chatpion_config($user_id, $api_key)
{
    $config_data = [
        'chatpion_user_id' => $user_id,
        'api_key' => $api_key,
        'webhook_url' => 'https://chantroituonglai.net',
        'is_active' => 1,
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    // Check if exists
    $this->db->where('chatpion_user_id', $user_id);
    $existing = $this->db->get(db_prefix() . 'chatpion_bridge_config')->row();
    
    if($existing) {
        // Update existing
        $this->db->where('chatpion_user_id', $user_id);
        $this->db->update(db_prefix() . 'chatpion_bridge_config', $config_data);
        
        log_message('error', '[ChatPion Bridge] Updated config for user: ' . $user_id);
    } else {
        // Insert new
        $config_data['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert(db_prefix() . 'chatpion_bridge_config', $config_data);
        
        log_message('error', '[ChatPion Bridge] Created config for user: ' . $user_id);
    }
}
```

### 3. When Task is Deleted, Retrieve Config

In `perfex_crm/modules/chatpion_bridge/chatpion_bridge.php`:

```php
function chatpion_bridge_task_deleted($task_id)
{
    $CI =& get_instance();
    
    // Get ChatPion link to find user
    $CI->db->select('*');
    $CI->db->where('task_id', $task_id);
    $link = $CI->db->get(db_prefix() . 'chatpion_bridge_task_links')->row();
    
    if(!$link || empty($link->user_id)) {
        return; // No link or user ID
    }
    
    // Get ChatPion config
    $CI->db->select('*');
    $CI->db->where('chatpion_user_id', $link->user_id);
    $CI->db->where('is_active', 1);
    $config = $CI->db->get(db_prefix() . 'chatpion_bridge_config')->row();
    
    if(!$config) {
        log_message('error', '[ChatPion Bridge] No config found for user: ' . $link->user_id);
        return;
    }
    
    // Call webhook
    $webhook_url = rtrim($config->webhook_url, '/') . '/api/perfex_task_deleted';
    
    $post_data = [
        'api_key' => $config->api_key,
        'task_id' => $task_id,
        'project_id' => $link->project_id ?? null
    ];
    
    // ... send webhook ...
}
```

## Implementation Steps

### Step 1: Create Migration SQL

Save this as `perfex_crm/modules/chatpion_bridge/install.sql`:

```sql
-- ChatPion Bridge Config Table
CREATE TABLE IF NOT EXISTS `tblchatpion_bridge_config` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `chatpion_user_id` INT(11) NOT NULL COMMENT 'ChatPion user ID',
  `api_key` VARCHAR(255) NOT NULL COMMENT 'ChatPion API key',
  `webhook_url` VARCHAR(500) NOT NULL DEFAULT 'https://chantroituonglai.net',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `chatpion_user_id` (`chatpion_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Also store user_id in task links for easy lookup
ALTER TABLE `tblchatpion_bridge_task_links` 
ADD COLUMN `user_id` INT(11) NULL AFTER `task_id`;
```

### Step 2: Update ChatPion to Send API Key

Add method to get user's API key in ChatPion:

```php
// In Instagram_poster.php

private function get_user_api_key($user_id)
{
    // Get or generate API key for this user
    $api_key_data = $this->basic->get_data('api', [
        'where' => ['user_id' => $user_id, 'deleted' => '0']
    ]);
    
    if(empty($api_key_data)) {
        // No API key, generate one
        $api_key = $this->_generate_api_key();
        
        $insert_data = [
            'user_id' => $user_id,
            'api_key' => $api_key,
            'deleted' => '0',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $this->basic->insert_data('api', $insert_data);
        return $api_key;
    }
    
    return $api_key_data[0]['api_key'];
}

private function _generate_api_key()
{
    return md5(uniqid(rand(), true));
}
```

Then include it in task data:

```php
$task_data = [
    'name' => $task_name,
    'description' => $task_description,
    'startdate' => $start_date,
    'duedate' => $due_date,
    'priority' => 2,
    'status' => $task_status,
    'tags' => $tags,
    // ChatPion Bridge Integration
    'chatpion_campaign_id' => $campaign_id,
    'chatpion_user_id' => $user_id,
    'chatpion_platform' => 'instagram_poster',
    'chatpion_sync_time' => date('Y-m-d H:i:s'),
    'chatpion_api_key' => $this->get_user_api_key($user_id), // NEW!
    'source' => 'chatpion'
];
```

### Step 3: Update Perfex CRM API to Store Config

Add to `perfex_crm/modules/api/controllers/Tasks.php`:

```php
public function data_post()
{
    // ... existing code ...
    
    // Store ChatPion config if provided
    $chatpion_user_id = $this->Api_model->value($this->input->post('chatpion_user_id', TRUE));
    $chatpion_api_key = $this->Api_model->value($this->input->post('chatpion_api_key', TRUE));
    
    if(!empty($chatpion_user_id) && !empty($chatpion_api_key)) {
        $this->store_chatpion_config($chatpion_user_id, $chatpion_api_key);
    }
    
    // ... continue with task creation ...
}

private function store_chatpion_config($user_id, $api_key)
{
    $config_data = [
        'chatpion_user_id' => $user_id,
        'api_key' => $api_key,
        'webhook_url' => 'https://chantroituonglai.net',
        'is_active' => 1,
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    $this->db->where('chatpion_user_id', $user_id);
    $existing = $this->db->get(db_prefix() . 'chatpion_bridge_config')->row();
    
    if($existing) {
        $this->db->where('chatpion_user_id', $user_id);
        $this->db->update(db_prefix() . 'chatpion_bridge_config', $config_data);
    } else {
        $config_data['created_at'] = date('Y-m-d H:i:s');
        $this->db->insert(db_prefix() . 'chatpion_bridge_config', $config_data);
    }
    
    log_message('error', '[ChatPion Bridge] Stored config for user: ' . $user_id);
}
```

## Testing Flow

1. **Create Campaign in ChatPion**
   - API key is automatically included
   - Config stored in Perfex CRM

2. **Delete Task in Perfex CRM**
   - Webhook called with stored API key
   - Link removed from ChatPion

3. **Verify**
   - Check `tblchatpion_bridge_config` in Perfex
   - Check logs in both systems
   - Verify link is removed from ChatPion's `perfex_crm_task_links`

