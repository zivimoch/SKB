# Onboarding Instansi

1. Instansi mengirim formulir permohonan integrasi dan menunjuk PIC teknis, keamanan, serta perlindungan data.
2. Para pihak menyepakati tujuan pemrosesan, data yang dipertukarkan, dasar hukum, retensi, SLA, dan prosedur insiden.
3. Tim SKB membuat institution dan integration client sandbox.
4. Instansi menerima `key_id`, secret sandbox, scope, endpoint, dan data fiktif.
5. Instansi menguji `/health`, `/integrations/me`, `/integrations/echo`, lalu payload kasus.
6. Tim SKB melakukan contract test, security review, dan verifikasi audit actor.
7. Setelah disetujui, kredensial production diterbitkan melalui kanal rahasia.
8. Aktivitas production dimonitor dan kredensial dirotasi berkala.

Secret tidak boleh dikirim melalui email, chat, issue GitHub, atau source control.

## Membuat client sandbox

Administrator SKB menjalankan:

```bash
php artisan skb:integration-client instansi-a-sandbox-v1 instansi-a \
  --name="Aplikasi Manajemen Kasus Instansi A" \
  --institution-code=instansi-a \
  --institution-name="Instansi A" \
  --environment=sandbox \
  --scopes=connection:test,cases:read,cases:write,assessments:write
```

Secret ditampilkan satu kali. Simpan melalui secret manager dan kirim ke PIC menggunakan kanal rahasia.
