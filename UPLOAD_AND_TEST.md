# Upload Updated File and Test

## What Changed

Added comprehensive logging to `perfex_crm/modules/api/controllers/Tasks.php`:

### Line 244-246: Log at entry point
```php
log_message('error', '[Perfex CRM API] ========== DATA_POST START ==========');
log_message('error', '[Perfex CRM API] Raw $_POST at entry: ' . json_encode($_POST));
log_message('error', '[Perfex CRM API] input->post() at entry: ' . json_encode($this->input->post()));
```

### Line 253: Before validation
```php
log_message('error', '[Perfex CRM API] About to run form validation...');
```

### Line 258-259: If validation fails
```php
log_message('error', '[Perfex CRM API] Form validation FAILED');
log_message('error', '[Perfex CRM API] Validation errors: ' . json_encode($this->form_validation->error_array()));
```

### Line 270: If validation passes
```php
log_message('error', '[Perfex CRM API] Form validation PASSED');
```

## Upload Steps

1. **Upload file to server:**
   ```bash
   scp perfex_crm/modules/api/controllers/Tasks.php \
       user@server:/path/to/perfex/modules/api/controllers/
   ```

2. **Or use FTP/SFTP client** to upload:
   - Local: `perfex_crm/modules/api/controllers/Tasks.php`
   - Remote: `/home/u310178187/domains/portal.chantroituonglai.com/public_html/modules/api/controllers/Tasks.php`

## Test Steps

### 1. Create New Campaign in ChatPion
- Create DIFFERENT campaign (not ID 88)
- Link to Perfex CRM project
- Save

### 2. Check Perfex Logs

Look for this sequence:

```
[Perfex CRM API] ========== DATA_POST START ==========
[Perfex CRM API] Raw $_POST at entry: {...}
[Perfex CRM API] input->post() at entry: {...}
[Perfex CRM API] About to run form validation...
```

Then EITHER:

**Scenario A: Validation passes** ✅
```
[Perfex CRM API] Form validation PASSED
[Perfex CRM API] ========== DATA_POST DEBUG ==========
[Perfex CRM API] Raw $_POST: {...}
[Perfex CRM API] Checking for ChatPion fields...
[Perfex CRM API] Field chatpion_campaign_id = '89'
[Perfex CRM API] Added chatpion_campaign_id to insert_data
```

**Scenario B: Validation fails** ❌
```
[Perfex CRM API] Form validation FAILED
[Perfex CRM API] Validation errors: {...}
```

## Expected Results

### If you see POST data with chatpion_campaign_id:
```json
{
    "name": "...",
    "chatpion_campaign_id": "89",
    "chatpion_user_id": "1",
    ...
}
```
→ ✅ **GOOD!** Data is being sent and received.

### If you see POST data WITHOUT chatpion_campaign_id:
```json
{
    "name": "...",
    "startdate": "...",
    // NO chatpion_campaign_id
}
```
→ ❌ **PROBLEM!** Data is being sent but stripped before reaching handler.

### If validation FAILS:
→ Need to check what fields are missing or invalid.

## Quick Checklist

- [ ] File uploaded to server
- [ ] New campaign created in ChatPion
- [ ] Logs checked for `DATA_POST START`
- [ ] Logs show POST data contents
- [ ] Validation status checked (PASSED/FAILED)
- [ ] ChatPion fields presence verified

## Common Issues

### Issue 1: No logs at all
**Problem:** File not uploaded correctly or cached.
**Solution:** Clear OpCache, restart PHP-FPM, or add random comment to force reload.

### Issue 2: Validation fails
**Problem:** Required fields missing.
**Solution:** Check which fields fail validation.

### Issue 3: POST data empty
**Problem:** Content-Type or API authentication issue.
**Solution:** Check Apiinit::the_da_vinci_code() isn't stripping data.

---

**Status:** Ready to upload and test  
**File:** `perfex_crm/modules/api/controllers/Tasks.php`  
**Action:** Upload → Create campaign → Check logs

