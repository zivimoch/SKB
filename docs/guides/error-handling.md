# Error Handling

Error API mengikuti struktur Problem Details RFC 9457:

```json
{
  "type": "https://docs.skb.go.id/problems/insufficient-scope",
  "title": "Scope tidak mencukupi",
  "status": 403,
  "detail": "Integration client membutuhkan scope cases:write.",
  "instance": "/api/v1/integrations/cases/abc",
  "request_id": "0192..."
}
```

Gunakan `request_id` saat meminta bantuan. Jangan menyertakan payload kasus atau secret.

Retry hanya untuk timeout, `429`, dan error `5xx`. Jangan retry otomatis untuk `400`, `401`, `403`, `409`, atau `422` sebelum akar masalah diperbaiki. Gunakan exponential backoff dengan jitter.
