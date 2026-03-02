# OpenClaw Gateway module

Unified gateway for OpenClaw to control Portal APIs.

## Endpoints

- `GET /api/openclaw/v1/capabilities`
- `POST /api/openclaw/v1/actions/invoke`
- `POST /api/openclaw/v1/actions/batch`
- `GET /api/openclaw/v1/health`
- `GET /api/openclaw/v1/stats`
- `GET /api/openclaw/v1/audit/{request_id}`

## Auth mode

Configured by option `openclaw_gateway_auth_mode`:

- `api_key`: use `X-API-KEY`
- `token`: use `Authorization: Bearer <flutex_token>`
- `dual`: try API key first, then staff token fallback

## Example

```bash
curl -X GET "https://portal.example.com/api/openclaw/v1/capabilities" \
  -H "X-API-KEY: <service-key>"

curl -X POST "https://portal.example.com/api/openclaw/v1/actions/invoke" \
  -H "Content-Type: application/json" \
  -H "X-API-KEY: <service-key>" \
  -d '{
    "action_id": "core.projects.get",
    "payload": {"id": 1},
    "dry_run": false
  }'
```

## Safety defaults

- `openclaw_gateway_read_only = 0`
- write verbs honor idempotency key if provided
- request/response are audit-logged with masked sensitive fields
