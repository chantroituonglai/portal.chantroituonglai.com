# POST Data Debug - Investigating Missing ChatPion Fields

## Problem
ChatPion sends `chatpion_campaign_id` via API, but Perfex CRM's `$insert_data` doesn't contain it.

## Added Debug Logging

### Location
`perfex_crm/modules/api/controllers/Tasks.php` - Line 260-298

### What We're Checking

```php
// Line 261-263: Log raw POST data
log_message('error', '[Perfex CRM API] ========== DATA_POST DEBUG ==========');
log_message('error', '[Perfex CRM API] Raw $_POST: ' . json_encode($_POST));
log_message('error', '[Perfex CRM API] input->post() all: ' . json_encode($this->input->post()));

// Line 289-298: Log each ChatPion field check
log_message('error', '[Perfex CRM API] Checking for ChatPion fields...');
foreach ($chatpion_fields as $field) {
    $value = $this->input->post($field, TRUE);
    log_message('error', '[Perfex CRM API] Field ' . $field . ' = ' . var_export($value, true));
    if (!empty($value)) {
        $insert_data[$field] = $value;
        log_message('error', '[Perfex CRM API] Added ' . $field . ' to insert_data');
    }
}
```

## Test Now

### Step 1: Create Campaign
Create a new campaign in ChatPion (different from ID 88)

### Step 2: Check Perfex Logs

Look for this section:
```
[Perfex CRM API] ========== DATA_POST DEBUG ==========
[Perfex CRM API] Raw $_POST: {...}
[Perfex CRM API] input->post() all: {...}
[Perfex CRM API] Checking for ChatPion fields...
[Perfex CRM API] Field chatpion_campaign_id = ???
[Perfex CRM API] Field chatpion_user_id = ???
```

## Expected Scenarios

### Scenario A: POST data contains ChatPion fields ✅

```
[Perfex CRM API] Raw $_POST: {
    "name": "...",
    "chatpion_campaign_id": "89",    ← Found!
    "chatpion_user_id": "1"
}
[Perfex CRM API] Field chatpion_campaign_id = '89'
[Perfex CRM API] Added chatpion_campaign_id to insert_data    ← Success!
```

**Result:** Should work! Link will be saved.

### Scenario B: POST data is empty ❌

```
[Perfex CRM API] Raw $_POST: []    ← Empty!
[Perfex CRM API] input->post() all: []
[Perfex CRM API] Field chatpion_campaign_id = ''    ← Not found!
```

**Problem:** Data is sent by ChatPion but not received by Perfex

**Possible causes:**
1. Content-Type mismatch (JSON vs form-urlencoded)
2. POST data consumed by form validation
3. Perfex REST API expecting different format

**Solution:** Check how ChatPion sends data

### Scenario C: POST has some fields but not ChatPion fields ❌

```
[Perfex CRM API] Raw $_POST: {
    "name": "...",
    "startdate": "...",
    "tags": "..."
    // No chatpion_campaign_id!
}
[Perfex CRM API] Field chatpion_campaign_id = ''    ← Not found!
```

**Problem:** ChatPion data stripped before reaching handler

**Possible causes:**
1. Form validation filters unknown fields
2. REST API whitelist blocks custom fields
3. Input class filtering

**Solution:** Need to check REST API input handling

## Next Steps Based on Results

### If POST data HAS ChatPion fields:
✅ Code is correct, just test again

### If POST data is EMPTY:
❌ Check ChatPion's `Perfex_crm_api.php`:
- Is it using correct Content-Type?
- Is it sending via POST?
- Check cURL options

### If POST has data but NOT ChatPion fields:
❌ Check Perfex REST API:
- Does it filter POST fields?
- Is there a whitelist?
- Check `\modules\api\core\Apiinit::the_da_vinci_code()`

## Quick Verification

### Check ChatPion is sending data:
```bash
# ChatPion logs
grep "Post data" /path/to/chatpion/logs/*.php | grep chatpion_campaign_id
```

Should see:
```
"chatpion_campaign_id":"89"
```

### Check Perfex is receiving data:
```bash
# Perfex logs
grep "Raw \$_POST" /path/to/perfex/logs/*.php | tail -1
```

Should see ChatPion fields in the JSON.

## Common Issues

### Issue 1: Content-Type mismatch

**ChatPion sends:** `application/x-www-form-urlencoded`
**Perfex expects:** Same

**Check:**
```php
// In Perfex_crm_api.php
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
// NOT: json_encode($payload)
```

### Issue 2: Form validation strips fields

Perfex's form validation might remove unknown fields.

**Check:**
```php
// In Tasks.php data_post()
$this->form_validation->set_rules('name', 'Task Name', 'trim|required');
// Only validates 'name', 'startdate', etc.
// Other fields might be preserved though
```

### Issue 3: REST API filtering

The `Apiinit::the_da_vinci_code()` might filter inputs.

**Check:** `perfex_crm/modules/api/core/Apiinit.php`

## Summary

We're adding comprehensive logging to see:
1. ✅ Is data sent by ChatPion?
2. ❓ Is data received by Perfex?
3. ❓ Are ChatPion fields in POST data?
4. ❓ Are they being added to insert_data?

Once we see the logs, we'll know exactly where the problem is! 🎯

---

**Action Required:** Create new campaign and paste the debug logs

