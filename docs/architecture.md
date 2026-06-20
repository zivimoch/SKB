# Arsitektur Integrasi SKB

SKB adalah hub pertukaran data. Aplikasi sumber tetap menjadi system of record untuk data yang mereka miliki.

```text
Pengguna instansi
  → Aplikasi sumber
    → API Gateway / mTLS
      → SKB Partner API
        → Database SKB terenkripsi
        → Audit log
```

Identitas selalu dipisahkan:

- `integration_client`: aplikasi yang mengirim request;
- `external_actor`: manusia yang melakukan tindakan pada aplikasi sumber;
- `users`: orang yang login langsung ke SKB;
- `institution`: organisasi pemilik integration client.

Primary key SKB tidak menggunakan ID database aplikasi sumber. Korelasi menggunakan pasangan `source_system + source_id`.

## Pola sinkronisasi

- `case_profile`: identifikasi, klasifikasi, pihak terkait, riwayat, dan asesmen;
- `full`: seluruh snapshot termasuk intervensi dan terminasi;
- setiap mutasi wajib memiliki idempotency key;
- aplikasi sumber mengirim `source_updated_at` dan `source_version`;
- konflik tidak diselesaikan berdasarkan nama atau waktu lokal pengguna.

## Production topology

Production wajib berada di private network/VPN, menggunakan TLS 1.3, mTLS, WAF/API gateway, Redis untuk nonce/rate-limit, database encryption at rest, backup terenkripsi, SIEM, dan secret manager/HSM.
