# Contributing

1. Buat branch dari `main`.
2. Jangan gunakan data kasus nyata pada fixture atau test.
3. Perbarui `docs/openapi.yaml` untuk perubahan kontrak.
4. Tambahkan test untuk endpoint atau aturan keamanan baru.
5. Jalankan `php artisan test` dan formatter sebelum pull request.
6. Jelaskan dampak migrasi, kompatibilitas API, dan risiko keamanan.

Commit tidak boleh memuat `.env`, credential, log, backup, private key, atau PII.
