# SKB Integration Platform

SKB adalah hub API-first untuk pertukaran data penanganan kasus lintas aplikasi dan instansi. Project ini menyediakan Partner API, antarmuka operasional, audit actor, dokumentasi interaktif, serta integrasi awal dengan MokaV2.

Runtime minimum: PHP 8.2 dan Laravel 12.

## Developer Portal

- Portal: `/developers`
- API reference interaktif: `/developers/api`
- OpenAPI: `/developers/openapi.yaml`
- Health: `/api/v1/health`
- Test credentials: `/api/v1/integrations/me`
- Echo signed request: `/api/v1/integrations/echo`

## Prinsip identitas

- Integration client mengidentifikasi aplikasi.
- External actor mengidentifikasi manusia pada aplikasi sumber.
- Local user hanya untuk orang yang login langsung ke SKB.
- Password aplikasi sumber tidak boleh disalin untuk production; gunakan OIDC/SSO.
- Otorisasi membutuhkan scope aplikasi dan hak actor.

## Quick start

```bash
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate
php artisan serve --host=127.0.0.1 --port=8000
```

Buka `http://127.0.0.1:8000/developers`.

Membuat integration client sandbox:

```bash
php artisan skb:integration-client instansi-a-sandbox-v1 instansi-a \
  --institution-code=instansi-a \
  --institution-name="Instansi A"
```

Uji contoh:

```bash
export SKB_BASE_URL=http://127.0.0.1:8000
export SKB_KEY_ID=moka-v1
export SKB_SECRET='replace-with-issued-secret'
php examples/php/signed_request.php
node examples/node/signed-request.mjs
python3 examples/python/signed_request.py
```

## Dokumentasi

- [Arsitektur](docs/architecture.md)
- [Onboarding](docs/guides/onboarding.md)
- [Authentication](docs/guides/authentication.md)
- [Error handling](docs/guides/error-handling.md)
- [OpenAPI](docs/openapi.yaml)
- [Security](SECURITY.md)
- [Tata kelola data](docs/governance/data-sharing.md)
- [API lifecycle](docs/governance/api-lifecycle.md)
- [Incident response](docs/governance/incident-response.md)
- [Kamus data](docs/data-dictionary/)

## Keamanan repository

Jangan commit `.env`, secret, private key, database dump, log, backup, atau data kasus nyata. Repository GitHub sebaiknya private sampai proses open-source dan sanitasi keamanan disetujui pemilik sistem.
