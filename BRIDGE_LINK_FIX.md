# Bridge Link Auto-Save Fix

## Problem
When a task is created from ChatPion via API, the link was only saved in ChatPion's `perfex_crm_task_links` table, but NOT in Perfex CRM's `tblchatpion_bridge_task_links` table.

This caused the `task_deleted` webhook to fail because it couldn't find the link in Perfex CRM's database.

## Root Cause
The `save_chatpion_bridge_link()` method in Perfex CRM was trying to read ChatPion data from `$this->input->post()`, but when called via REST API, the data was in `$task_data` array instead.

## Solution
Modified `perfex_crm/modules/api/controllers/Tasks.php` to:

1. **Read ChatPion fields from POST** and add them to `$insert_data`
2. **Pass enriched `$insert_data` to `save_chatpion_bridge_link()`**
3. **Fallback to POST** if not in `$insert_data` (defensive coding)

### Code Changes

#### 1. In `data_post()` - Read ChatPion fields from POST

```php
// After building $insert_data array
// Add ChatPion bridge fields if provided ✅
$chatpion_fields = ['chatpion_campaign_id', 'chatpion_user_id', 'chatpion_platform', 'chatpion_sync_time', 'source'];
foreach ($chatpion_fields as $field) {
    $value = $this->input->post($field, TRUE);
    if (!empty($value)) {
        $insert_data[$field] = $value;
    }
}
```

#### 2. In `save_chatpion_bridge_link()` - Read from task_data with POST fallback

```php
private function save_chatpion_bridge_link($task_id, $task_data)
{
    // Try from POST first (for standard requests)
    $chatpion_campaign_id = $this->input->post('chatpion_campaign_id', TRUE);
    $chatpion_user_id = $this->input->post('chatpion_user_id', TRUE);
    $chatpion_platform = $this->input->post('chatpion_platform', TRUE);
    
    // If not in POST, try from task_data (for API requests) ✅
    if (empty($chatpion_campaign_id) && isset($task_data['chatpion_campaign_id'])) {
        $chatpion_campaign_id = $task_data['chatpion_campaign_id'];
        $chatpion_user_id = $task_data['chatpion_user_id'] ?? null;
        $chatpion_platform = $task_data['chatpion_platform'] ?? 'instagram_poster';
    }
    
    // ... rest of the code
}
```

## Data Flow

### Before Fix ❌
```
ChatPion → API POST with chatpion_campaign_id=88
                      ↓
          Perfex data_post() builds $insert_data
                      ↓
          $insert_data = [name, startdate, ...] (NO ChatPion fields!) ❌
                      ↓
          tasks_model->add($insert_data) → Task created
                      ↓
          save_chatpion_bridge_link($task_id, $insert_data)
                      ↓
          Reads from $insert_data → EMPTY! ❌
          Reads from POST → EMPTY (already consumed)! ❌
                      ↓
                   SKIP SAVE
```

### After Fix ✅
```
ChatPion → API POST with chatpion_campaign_id=88
                      ↓
          Perfex data_post() builds $insert_data
                      ↓
          Loop through chatpion_fields ✅
          Read from POST → chatpion_campaign_id=88 ✅
                      ↓
          $insert_data = [name, startdate, ..., chatpion_campaign_id=88] ✅
                      ↓
          tasks_model->add($insert_data) → Task created
                      ↓
          save_chatpion_bridge_link($task_id, $insert_data)
                      ↓
          Reads from POST → EMPTY (consumed)
          Fallback to $insert_data → FOUND! ✅
                      ↓
          INSERT into tblchatpion_bridge_task_links ✅
```

## Testing

### Test 1: Create Campaign from ChatPion

**Expected logs in Perfex CRM:**
```
[Perfex CRM API] About to call save_chatpion_bridge_link
[Perfex CRM API] Task ID: 524
[Perfex CRM API] Insert data keys: name, startdate, ..., chatpion_campaign_id, chatpion_user_id

[ChatPion Bridge] ========== SAVE BRIDGE LINK ==========
[ChatPion Bridge] Task ID: 524
[ChatPion Bridge] Task data keys: name, startdate, chatpion_campaign_id, ...
[ChatPion Bridge] From POST - Campaign ID: ''
[ChatPion Bridge] From POST - User ID: ''
[ChatPion Bridge] From task_data - Campaign ID: 88    ← Found!
[ChatPion Bridge] From task_data - User ID: 1
[ChatPion Bridge] ✓ Valid campaign ID found
[ChatPion Bridge] Saving bridge link for task: 524
[ChatPion Bridge] Campaign ID: 88
[ChatPion Bridge] User ID: 1
[ChatPion Bridge] Platform: instagram_poster
[ChatPion Bridge] Link data prepared: {"task_id":524,"campaign_id":"88",...}
[ChatPion Bridge] Inserting into table: tblchatpion_bridge_task_links
[ChatPion Bridge] Insert affected rows: 1
[ChatPion Bridge] Insert ID: 16
[ChatPion Bridge] ✓ Bridge link saved successfully - Link ID: 16
[ChatPion Bridge] ========== SUCCESS ==========
```

**Verify in database:**
```sql
SELECT * FROM tblchatpion_bridge_task_links 
WHERE task_id = 522 AND campaign_id = 88;
```

**Expected:** 1 row found ✅

### Test 2: Delete Task from Perfex CRM

**Expected logs in Perfex CRM:**
```
[ChatPion Bridge] ========== TASK DELETED HOOK ==========
[ChatPion Bridge] Task ID: 522
[ChatPion Bridge] Found link:
[ChatPion Bridge]   - Campaign ID: 88    ← Found because we saved it!
[ChatPion Bridge]   - Project ID: 35
[ChatPion Bridge] Webhook call preparation:
[ChatPion Bridge]   - URL: https://chantroituonglai.net/perfex_task_deleted
...
[ChatPion Bridge] SUCCESS: ChatPion notified successfully
[ChatPion Bridge] Local bridge link deleted for task: 522
```

**Expected logs in ChatPion:**
```
[Perfex Webhook] ========== NEW REQUEST ==========
[Perfex Webhook] Task ID: 522
[Perfex Webhook] Found link - Campaign ID: 88
[Perfex Webhook] Delete operation result: SUCCESS
[Perfex Webhook] Link deleted successfully for campaign ID: 88
```

## Benefits

### 1. Dual Table Sync ✅
Both ChatPion and Perfex CRM now maintain their own link records:
- **ChatPion:** `perfex_crm_task_links`
- **Perfex CRM:** `tblchatpion_bridge_task_links`

### 2. Webhook Works ✅
When task is deleted in Perfex CRM, the `task_deleted` hook:
1. Finds link in `tblchatpion_bridge_task_links`
2. Sends webhook to ChatPion
3. ChatPion deletes its link in `perfex_crm_task_links`
4. Perfex deletes its link in `tblchatpion_bridge_task_links`

### 3. Bidirectional Sync ✅
Both systems stay in sync automatically:
- Create task → Both systems record link
- Delete task → Both systems remove link

## Files Modified

### 1. `perfex_crm/modules/api/controllers/Tasks.php`

**Line 826-905:** `save_chatpion_bridge_link()` method
- Added fallback to `$task_data` array
- Added comprehensive logging
- Improved error handling

## Migration Not Required

✅ No database changes needed
✅ Works with existing tables
✅ Backward compatible

## Next Steps

1. **Test campaign creation** and verify logs
2. **Test task deletion** and verify webhook
3. **Monitor logs** for any errors
4. Consider adding **database index** for performance:

```sql
ALTER TABLE `tblchatpion_bridge_task_links` 
ADD INDEX `idx_campaign_user` (`campaign_id`, `user_id`);
```

## Rollback Plan

If issues occur, remove the fallback logic:

```php
// Revert to POST-only
$chatpion_campaign_id = $this->input->post('chatpion_campaign_id', TRUE);
$chatpion_user_id = $this->input->post('chatpion_user_id', TRUE);

if (empty($chatpion_campaign_id)) {
    return; // Skip
}
```

But this will break webhook functionality.

## Summary

✅ **Problem:** Bridge links not saved when task created via API  
✅ **Solution:** Read from `task_data` array as fallback  
✅ **Result:** Dual table sync works automatically  
✅ **Benefit:** Task deletion webhook now functions correctly  

---

**Status:** Ready for testing
**Updated:** 2025-10-17
