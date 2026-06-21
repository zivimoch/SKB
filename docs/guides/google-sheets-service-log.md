# Sinkronisasi Log Layanan ke Google Sheets

SKB menulis data dari database ke tab `Log Layanan (Auto)` pada spreadsheet yang dikonfigurasi. Sinkronisasi hanya mengelola rentang `A2:K`; header pada baris pertama dan tab lain tidak diubah.

## Kolom

| Kolom | Sumber SKB |
|---|---|
| no_klien | `hub_cases.client_number` |
| Inisial Korban | Nama korban terenkripsi pada `case_people`, diubah menjadi inisial |
| Agenda | `intervention_activities.title_encrypted` |
| Layanan | `content.services` pada laporan intervensi, berasal dari `m_keyword`/`t_keyword` Moka |
| Jumlah Layanan | Jumlah layanan pada laporan |
| Tanggal pelaksanaan | `intervention_activities.scheduled_date` |
| Tanggal laporan | Waktu pembaruan laporan dari aplikasi sumber |
| Petugas | Nama, jabatan, dan instansi petugas |
| Tanggal diterima | `intervention_reports.confirmed_at` |
| Pemohon | Pembuat agenda, dikosongkan jika pembuat dan petugas sama |
| Keterangan | Status laporan atau alasan penolakan |

## Kredensial Google

1. Buat project di Google Cloud.
2. Aktifkan Google Sheets API.
3. Buat service account dan unduh kredensial JSON.
4. Simpan JSON di server sebagai:

   `/var/www/html/dev-skb/storage/app/private/google-service-account.json`

5. Salin `client_email` dari JSON, lalu bagikan spreadsheet kepada email tersebut sebagai **Editor**.
6. Batasi izin file JSON agar hanya dapat dibaca user web server.

Jangan commit file kredensial ke Git. Folder kredensial JSON sudah dikecualikan melalui `.gitignore`.

## Konfigurasi

Tambahkan ke `.env`:

```dotenv
GOOGLE_SHEETS_ENABLED=true
GOOGLE_SHEETS_SPREADSHEET_ID=1ucUT7na_9AcUg1u7RnIjAX5iZd68q4vEv2B4_40qzKI
GOOGLE_SHEETS_SERVICE_LOG_SHEET="Log Layanan (Auto)"
GOOGLE_SHEETS_CREDENTIALS_PATH=/var/www/html/dev-skb/storage/app/private/google-service-account.json
```

Kemudian:

```bash
php artisan optimize:clear
php artisan skb:sync-service-log
```

## Otomatisasi

Command dijadwalkan setiap lima menit. Pastikan scheduler Laravel dijalankan oleh cron:

```cron
* * * * * cd /var/www/html/dev-skb && php artisan schedule:run >> /dev/null 2>&1
```

Kegagalan Google Sheets tidak menggagalkan API sinkronisasi Moka–SKB karena proses ini berjalan terpisah.
