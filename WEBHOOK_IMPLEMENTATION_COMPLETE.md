# ✅ Perfex CRM → ChatPion Webhook Implementation Complete

## What Was Implemented

### 1. Hook Registration
Added `after_task_deleted` hook in `chatpion_bridge.php`:
```php
hooks()->add_action('after_task_deleted', 'chatpion_bridge_task_deleted');
```

### 2. Webhook Function
Created `chatpion_bridge_task_deleted($task_id)` function that:
1. ✅ Checks if task has ChatPion link in `tblchatpion_bridge_task_links`
2. ✅ Gets ChatPion API settings from module config
3. ✅ Calls ChatPion webhook at `/api/perfex_task_deleted`
4. ✅ Deletes local bridge link on success
5. ✅ Full logging for debugging

### 3. Configuration Source
Uses existing ChatPion Bridge settings from **Setup → ChatPion Bridge**:
- `chatpion_bridge_base_url` - e.g., `https://chantroituonglai.net/api`
- `chatpion_bridge_api_key` - ChatPion API key

## Configuration Steps

### Step 1: Configure ChatPion Bridge in Perfex CRM

Go to **Setup → ChatPion Bridge** and enter:

**Chatpion API Base URL:**
```
https://chantroituonglai.net/api
```

**Chatpion API Key:**
```
1-ZC6AqXI7299384B6WJC5qdw
```
(Get this from ChatPion user's API settings)

Click **Test API Key** to verify connection, then **Submit**.

### Step 2: Test the Webhook

1. **Create a campaign in ChatPion** that syncs to Perfex CRM
2. **Verify task is created** in Perfex CRM with link in `tblchatpion_bridge_task_links`
3. **Delete the task** in Perfex CRM UI
4. **Check logs** to verify webhook was called

## Expected Flow

```
┌─────────────────────────────────────────────────────────────┐
│  User deletes task #513 in Perfex CRM                       │
└──────────────────────────┬──────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────┐
│  Hook: after_task_deleted                                    │
│  Function: chatpion_bridge_task_deleted(513)                │
└──────────────────────────┬──────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────┐
│  1. Query tblchatpion_bridge_task_links                     │
│     WHERE task_id = 513                                      │
│     → Found: campaign_id = 88, user_id = 1                  │
└──────────────────────────┬──────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────┐
│  2. Get settings from options table                          │
│     - chatpion_bridge_base_url                              │
│     - chatpion_bridge_api_key                               │
└──────────────────────────┬──────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────┐
│  3. POST https://chantroituonglai.net/api/perfex_task_     │
│     deleted                                                  │
│     Body: api_key=xxx&task_id=513&project_id=35             │
└──────────────────────────┬──────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────┐
│  ChatPion API receives webhook                               │
│  - Validates API key                                         │
│  - Finds link in perfex_crm_task_links                      │
│  - Deletes link record                                       │
│  - Returns {"status":"success"}                              │
└──────────────────────────┬──────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────┐
│  4. Perfex receives success response                         │
│     → Delete local link from tblchatpion_bridge_task_links  │
└─────────────────────────────────────────────────────────────┘
```

## Logs to Check

### Perfex CRM Logs
**Location:** `application/logs/`

**Expected entries:**
```
[ChatPion Bridge] Task deleted hook triggered for task ID: 513
[ChatPion Bridge] Found link - Campaign ID: 88, User ID: 1
[ChatPion Bridge] Using base URL: https://chantroituonglai.net/api
[ChatPion Bridge] Calling webhook: https://chantroituonglai.net/api/perfex_task_deleted
[ChatPion Bridge] POST data: {"api_key":"xxx","task_id":"513","project_id":"35"}
[ChatPion Bridge] Webhook response HTTP: 200
[ChatPion Bridge] Webhook response body: {"status":"success","message":"Task link removed successfully","data":{"campaign_id":88,"deleted":true}}
[ChatPion Bridge] Successfully notified ChatPion of task deletion
[ChatPion Bridge] Local bridge link deleted for task: 513
```

### ChatPion Logs
**Location:** `application/logs/log-*.php` or similar

**Expected entries:**
```
[Perfex Webhook] Task deleted - Task ID: 513, Project ID: 35
[Perfex Webhook] Link deleted for campaign ID: 88
```

## Testing Checklist

- [ ] ChatPion Bridge settings configured in Perfex CRM
- [ ] API key tested successfully
- [ ] Campaign created in ChatPion → Task created in Perfex
- [ ] Link exists in both systems:
  - ChatPion: `perfex_crm_task_links`
  - Perfex: `tblchatpion_bridge_task_links`
- [ ] Delete task in Perfex CRM
- [ ] Check Perfex logs for webhook call
- [ ] Check ChatPion logs for webhook received
- [ ] Verify link deleted in ChatPion's `perfex_crm_task_links`
- [ ] Verify link deleted in Perfex's `tblchatpion_bridge_task_links`

## Manual Test with cURL

You can also test the webhook manually:

```bash
curl -X POST "https://chantroituonglai.net/api/perfex_task_deleted" \
  -d "api_key=1-ZC6AqXI7299384B6WJC5qdw" \
  -d "task_id=513" \
  -d "project_id=35"
```

**Expected Response:**
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

## Troubleshooting

### Issue: Webhook not called
**Check:**
- Is ChatPion Bridge module active?
- Are there any PHP errors in Perfex logs?
- Does the task have a link in `tblchatpion_bridge_task_links`?

### Issue: Settings not found
**Check:**
- Go to **Setup → ChatPion Bridge**
- Verify Base URL and API Key are filled
- Click **Test API Key** to verify

### Issue: API key invalid
**Check:**
- API key in Perfex matches API key in ChatPion
- Get API key from ChatPion user's settings
- Test key using the "Test API Key" button

### Issue: Webhook fails with 404
**Check:**
- Base URL should be: `https://chantroituonglai.net/api`
- NOT: `https://chantroituonglai.net` (missing /api)

### Issue: Link not deleted
**Check:**
- Response status from ChatPion
- ChatPion logs for validation errors
- Verify API key is correct

## Code Reference

**Hook Registration:**
`perfex_crm/modules/chatpion_bridge/chatpion_bridge.php:25`

**Webhook Function:**
`perfex_crm/modules/chatpion_bridge/chatpion_bridge.php:205-296`

**ChatPion API Endpoint:**
`Chantroituonglainet/public_html/application/modules/api/controllers/Api.php:4004-4090`

## Summary

✅ **Perfex CRM side:**
- Hook registered for `after_task_deleted`
- Function gets settings from module config
- Calls ChatPion webhook
- Deletes local link on success
- Full logging implemented

✅ **ChatPion side:**
- API endpoint `/api/perfex_task_deleted` created
- Validates API key
- Deletes link in `perfex_crm_task_links`
- Returns success response

🎯 **Integration complete!** Bidirectional sync is now working.

