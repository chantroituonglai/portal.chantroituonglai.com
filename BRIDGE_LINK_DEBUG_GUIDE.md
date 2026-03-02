# Bridge Link Debug Guide

## Issue
`chatpion_bridge_task_links` table is not being populated when task is created from ChatPion.

## Debug Steps

### Step 1: Create Test Campaign

1. Go to ChatPion
2. Create new Instagram campaign
3. Link to Perfex CRM project
4. Save campaign

### Step 2: Check Perfex CRM Logs

**Location:** `perfex_crm/application/logs/log-YYYY-MM-DD.php`

**Look for:**
```
[ChatPion Bridge] ========== SAVE BRIDGE LINK ==========
[ChatPion Bridge] Task ID: XXX
[ChatPion Bridge] POST chatpion_campaign_id: 'YYY'
[ChatPion Bridge] POST chatpion_user_id: 'ZZZ'
[ChatPion Bridge] All POST data: {...}
```

### Scenario A: POST data is empty

**Log shows:**
```
[ChatPion Bridge] POST chatpion_campaign_id: ''
[ChatPion Bridge] POST chatpion_user_id: ''
[ChatPion Bridge] No campaign ID - skipping
```

**Problem:** Data not being sent via POST

**Check:**
1. ChatPion's `Perfex_crm_api->create_task()` method
2. Verify `$payload` includes `chatpion_campaign_id`
3. Check `http_build_query($payload)` output

**Fix:** Ensure ChatPion sends data via POST body:
```php
// In Perfex_crm_api.php
$payload = [
    'name' => $task_data['name'],
    // ...
    'chatpion_campaign_id' => $task_data['chatpion_campaign_id'],
    'chatpion_user_id' => $task_data['chatpion_user_id'],
];

curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
```

### Scenario B: POST data exists but not saved

**Log shows:**
```
[ChatPion Bridge] POST chatpion_campaign_id: '88'
[ChatPion Bridge] POST chatpion_user_id: '1'
[ChatPion Bridge] Saving bridge link for task: 521
[ChatPion Bridge] Link data: {...}
[ChatPion Bridge] Failed to save bridge link - no rows affected
```

**Problem:** Database insert failed

**Check:**
```sql
-- Check if table exists
SHOW TABLES LIKE 'tblchatpion_bridge_task_links';

-- Check table structure
DESCRIBE tblchatpion_bridge_task_links;
```

**Expected structure:**
```sql
CREATE TABLE `tblchatpion_bridge_task_links` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `task_id` INT(11) NOT NULL,
  `campaign_id` INT(11) NOT NULL,
  `project_id` INT(11) NULL,
  `user_id` INT(11) NULL,
  `media_type` VARCHAR(50) NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `task_id` (`task_id`)
);
```

### Scenario C: Success

**Log shows:**
```
[ChatPion Bridge] ========== SAVE BRIDGE LINK ==========
[ChatPion Bridge] Task ID: 521
[ChatPion Bridge] POST chatpion_campaign_id: '88'
[ChatPion Bridge] POST chatpion_user_id: '1'
[ChatPion Bridge] Saving bridge link for task: 521
[ChatPion Bridge] Campaign ID: 88
[ChatPion Bridge] User ID: 1
[ChatPion Bridge] Link data: {"task_id":521,"campaign_id":"88","project_id":"35","user_id":"1","media_type":"instagram_poster"}
[ChatPion Bridge] Bridge link saved successfully - Link ID: 15
```

**Verify:**
```sql
SELECT * FROM tblchatpion_bridge_task_links WHERE task_id = 521;
```

**Expected:** 1 row found ✅

## Common Issues

### Issue 1: API authentication

If POST data is sent but not received:
- Check if `authtoken` is correct
- Verify API is enabled in Perfex CRM

### Issue 2: Form validation

Perfex API validates required fields. If `chatpion_campaign_id` is not in allowed fields, it may be stripped.

**Check:** `perfex_crm/modules/api/controllers/Tasks.php` - form validation rules

### Issue 3: Database permissions

If insert fails silently:
```sql
-- Check permissions
SHOW GRANTS FOR CURRENT_USER;

-- Try manual insert
INSERT INTO tblchatpion_bridge_task_links 
(task_id, campaign_id, project_id, user_id, media_type, created_at, updated_at)
VALUES 
(999, 88, 35, 1, 'test', NOW(), NOW());
```

## Quick Test Script

Run this in ChatPion to verify data is being sent:

```php
// In Instagram_poster.php, before calling create_task:
log_message('error', '[PREFLIGHT CHECK] Task data being sent:');
log_message('error', '[PREFLIGHT CHECK] chatpion_campaign_id: ' . ($task_data['chatpion_campaign_id'] ?? 'MISSING'));
log_message('error', '[PREFLIGHT CHECK] chatpion_user_id: ' . ($task_data['chatpion_user_id'] ?? 'MISSING'));
```

## Expected Complete Flow

```
ChatPion Instagram_poster.php
  ↓ create_perfex_task_for_campaign(88, ...)
  ↓ Prepare task_data with chatpion_campaign_id = 88
  ↓
ChatPion Perfex_crm_api.php
  ↓ create_task(35, task_data)
  ↓ Build payload with chatpion_campaign_id
  ↓ POST to /api/tasks
  ↓
Perfex CRM API Tasks.php
  ↓ data_post() receives POST data
  ↓ tasks_model->add() creates task (ID: 521)
  ↓ save_chatpion_bridge_link(521, task_data)
  ↓ Read chatpion_campaign_id from POST
  ↓ INSERT INTO tblchatpion_bridge_task_links
  ✅ Link saved!
```

## Logs to Watch

**Terminal 1 (ChatPion):**
```bash
tail -f application/logs/log-*.php | grep -E "\[Perfex|PREFLIGHT"
```

**Terminal 2 (Perfex):**
```bash
tail -f application/logs/log-*.php | grep "ChatPion Bridge"
```

## Next Steps After Identifying Issue

1. If POST data missing → Fix `Perfex_crm_api.php` payload
2. If table missing → Run migration SQL
3. If insert fails → Check permissions
4. If success → Test task deletion webhook

---

**Current Status:** Waiting for test campaign creation to see logs

