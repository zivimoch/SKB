# Changelog

## [Unreleased]

### Changed

- Menurunkan framework ke Laravel 12 dan runtime minimum ke PHP 8.2 untuk
  kompatibilitas deployment CentOS yang menjalankan PHP 8.2.
- Menyesuaikan PHPUnit ke versi 11 dan menghapus `laravel/pao` yang membutuhkan
  PHP 8.3.
- Menambahkan kompatibilitas indeks `utf8mb4` untuk MySQL/MariaDB lama dengan
  batas indeks InnoDB 767 byte.
- Menyimpan dokumen JSON sebagai `LONGTEXT` agar migration dapat berjalan pada
  MySQL/MariaDB lama yang belum memiliki tipe kolom native `JSON`.
- Membuat timestamp yang diisi aplikasi bersifat nullable pada level skema untuk
  kompatibilitas MySQL lama yang menolak `TIMESTAMP NOT NULL` tanpa default.

### Added

- Developer Portal dan API reference interaktif.
- Seeder idempotent untuk akun development Manajer Kasus dan Paralegal yang
  bersumber dari data pengguna contoh MokaV2.
- Endpoint health, integration identity, dan echo.
- Registri institution, integration client, external actor, scope, dan identity provider.
- Dokumentasi onboarding, authentication, governance, lifecycle, dan kamus data.
- Contoh PHP, Node.js, Python, serta koleksi Postman.

## [1.0.0] - 2026-06-20

- API v1 untuk snapshot kasus.
- HMAC signing, timestamp, nonce, idempotency, rate limiting, audit log, dan enkripsi data sensitif.
- Integrasi awal MokaV2.
