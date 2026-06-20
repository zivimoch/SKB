# Baseline keamanan integrasi SKB

SKB menerima data rahasia melalui HTTPS dan request signing. TLS wajib, tetapi tidak menggantikan autentikasi pada level aplikasi.

## Canonical request

Pengirim menghitung HMAC-SHA256 dan mengirim hasil Base64 pada `X-SKB-Signature`.

```text
HTTP_METHOD
/api/v1/path
raw_query_string
unix_timestamp
nonce
idempotency_key
sha256_hex(raw_body)
```

Header wajib:

- `X-SKB-Key-Id`
- `X-SKB-Timestamp`
- `X-SKB-Nonce`
- `X-SKB-Signature`
- `Idempotency-Key` untuk request mutasi
- `X-Request-Id` untuk korelasi audit

Timestamp hanya diterima dalam jendela lima menit. Nonce hanya dapat dipakai sekali selama sepuluh menit. Secret lama dapat dipasang sementara melalui `MOKA_INTEGRATION_PREVIOUS_SECRET` saat rotasi tanpa downtime.

## Kontrol yang sudah diimplementasikan

- allow-list klien integrasi dan secret terpisah per aplikasi;
- signature HMAC dengan perbandingan konstan;
- timestamp dan nonce anti-replay;
- idempotency receipt untuk mencegah duplikasi;
- rate limiting per aplikasi sumber;
- validasi payload dan transaksi database;
- UUID internal terpisah dari ID aplikasi sumber;
- enkripsi application-layer untuk PII, asesmen, ringkasan, laporan intervensi, monev, dan terminasi;
- audit log tanpa menyimpan payload rahasia atau alamat IP mentah;
- isolasi data berdasarkan `source_system`.

## Kontrol produksi yang wajib di infrastruktur

- TLS 1.3 dan mTLS antara gateway aplikasi;
- private network/VPN pemerintah dan IP allow-list;
- WAF/API gateway dengan batas ukuran body;
- secret manager/HSM, rotasi berkala, dan pemisahan tugas;
- PostgreSQL/MySQL dengan encryption at rest, backup terenkripsi, PITR, dan akun least privilege;
- Redis terautentikasi untuk nonce/rate limit pada deployment multi-node;
- SIEM, alert atas signature gagal/replay/rate-limit, serta retensi audit append-only;
- pemindaian dependency/SAST/DAST, penetration test independen, dan threat modeling;
- kebijakan klasifikasi, retensi, pemusnahan, dan prosedur respons insiden.

Kode aplikasi ini adalah baseline teknis, bukan klaim sertifikasi keamanan. Deployment untuk data negara harus melewati review keamanan, uji penetrasi, dan persetujuan tata kelola instansi.
