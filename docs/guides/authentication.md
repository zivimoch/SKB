# Authentication dan Request Signing

Setiap aplikasi mendapat `key_id` dan secret berbeda. Untuk setiap request:

```text
METHOD
/api/v1/path
raw_query_string
unix_timestamp
nonce
idempotency_key
sha256_hex(raw_body)
```

Gabungkan tujuh baris tersebut dengan newline, hitung HMAC-SHA256 menggunakan secret, lalu kirim hasil Base64 sebagai `X-SKB-Signature`.

Header wajib:

- `X-SKB-Key-Id`
- `X-SKB-Timestamp`
- `X-SKB-Nonce`
- `X-SKB-Signature`
- `X-Request-Id`
- `Idempotency-Key` untuk mutasi

Header actor:

- `X-SKB-Actor-Id`
- `X-SKB-Actor-Name`
- `X-SKB-Actor-Role`
- `X-SKB-Actor-Institution`

`Actor-Id` harus stabil pada aplikasi sumber. Nama dan role hanya metadata tampilan/audit.

## Roadmap authentication

HMAC adalah mekanisme tahap awal. Production lintas instansi diarahkan ke OAuth 2.0 Client Credentials + mTLS. Login manusia langsung ke SKB menggunakan OIDC/SSO; password aplikasi sumber tidak disalin ke SKB.
