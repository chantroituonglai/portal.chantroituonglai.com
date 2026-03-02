# Perfex CRM API Endpoint Testing

## Endpoint: Check ChatPion Campaign

### Details
- **URL**: `/api/tasks/check_chatpion_campaign`
- **Method**: `GET`
- **Authentication**: Basic Auth (API Key)
- **Module**: `api`
- **Controller**: `Tasks.php`
- **Method**: `check_chatpion_campaign_get()`

### Authentication

Sử dụng Basic Auth với API key từ Perfex CRM:
```
Authorization: Basic base64(api_key:api_secret)
```

Hoặc sử dụng header:
```
authtoken: YOUR_API_KEY
```

### Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `campaign_id` | string | Yes | ID của campaign trong ChatPion |
| `media_type` | string | No | Loại media: `facebook`, `instagram`, hoặc `all` (default: `all`) |

### Test Commands

#### Test 1: Check campaign exists
```bash
# Với API key
curl -X GET "https://portal.chantroituonglai.com/api/tasks/check_chatpion_campaign?campaign_id=88&media_type=instagram" \
  -H "authtoken: YOUR_API_KEY"

# Hoặc với Basic Auth
curl -X GET "https://portal.chantroituonglai.com/api/tasks/check_chatpion_campaign?campaign_id=88&media_type=instagram" \
  -u "YOUR_API_KEY:YOUR_API_SECRET"
```

#### Test 2: Check campaign không tồn tại
```bash
curl -X GET "https://portal.chantroituonglai.com/api/tasks/check_chatpion_campaign?campaign_id=999999&media_type=instagram" \
  -H "authtoken: YOUR_API_KEY"
```

#### Test 3: Missing campaign_id (Error case)
```bash
curl -X GET "https://portal.chantroituonglai.com/api/tasks/check_chatpion_campaign?media_type=instagram" \
  -H "authtoken: YOUR_API_KEY"
```

### Expected Responses

#### Response 1: Task exists
```json
{
    "status": "success",
    "data": {
        "exists": true,
        "task_id": 123,
        "project_id": 45,
        "task_name": "Campaign #88 - Instagram Post",
        "link_data": {
            "id": 1,
            "task_id": 123,
            "campaign_id": "88",
            "account_id": "account123",
            "workspace_json": null,
            "last_status": "completed",
            "post_url": "https://www.instagram.com/...",
            "media_type": "instagram",
            "last_synced_at": "2025-10-17 14:38:48",
            "created_at": "2025-10-17 14:38:48",
            "updated_at": "2025-10-17 14:38:48",
            "created_by": 1
        }
    }
}
```

#### Response 2: Task not found
```json
{
    "status": "success",
    "data": {
        "exists": false
    }
}
```

#### Response 3: Error - Missing campaign_id
```json
{
    "status": "error",
    "message": "Campaign ID is required"
}
```

#### Response 4: Error - Module not active
```json
{
    "status": "error",
    "message": "ChatPion Bridge module is not active"
}
```

### Integration Flow

```
ChatPion → Perfex CRM API Library → Perfex CRM API Endpoint
         → chatpion_bridge_task_links table → Task data
```

### Debug Steps

1. **Verify module is active**:
   ```sql
   SELECT * FROM tblmodules WHERE module_name = 'chatpion_bridge';
   ```

2. **Check table exists**:
   ```sql
   SHOW TABLES LIKE 'tblchatpion_bridge_task_links';
   ```

3. **Check campaign link exists**:
   ```sql
   SELECT * FROM tblchatpion_bridge_task_links WHERE campaign_id = '88';
   ```

4. **Check task exists**:
   ```sql
   SELECT * FROM tbltasks WHERE id = [task_id_from_above];
   ```

### Common Issues

#### Issue 1: Returns task_id = 0
**Cause**: Task không tồn tại trong database hoặc campaign_id không match
**Fix**: 
- Verify campaign_id chính xác
- Check bảng `tblchatpion_bridge_task_links`
- Ensure task đã được tạo từ ChatPion

#### Issue 2: Module not active
**Cause**: Module chatpion_bridge chưa được activate
**Fix**: 
- Activate module trong Perfex CRM admin
- Check `tblmodules` table

#### Issue 3: Authentication failed
**Cause**: API key không hợp lệ
**Fix**:
- Verify API key trong Perfex CRM Settings > API
- Check authtoken header hoặc Basic Auth

### Testing với ChatPion

Sau khi endpoint hoạt động, test từ ChatPion:

1. **Tạo campaign mới** trong Instagram Poster
2. **Verify task được tạo** trong Perfex CRM
3. **Check task link** trong `tblchatpion_bridge_task_links`
4. **Test sync lại** bằng cách xóa record trong `perfex_crm_task_links` (ChatPion)
5. **Bấm Sync** trong Instagram Poster list
6. **Verify thông báo** "Found existing task in Perfex CRM and mapped successfully"

### Success Criteria

✅ Endpoint trả về HTTP 200
✅ Response có đúng format JSON
✅ `task_id` là số nguyên (không phải 0)
✅ `project_id` match với project được config
✅ `link_data` chứa đầy đủ thông tin
✅ ChatPion có thể map lại task đã tồn tại
