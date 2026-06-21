# Google Apps Script — Log Layanan SKB

Apps Script membaca endpoint JSON read-only SKB dan menulis datanya ke tab `Log Layanan (Auto)`.

## Konfigurasi SKB

Tambahkan token acak minimal 32 karakter ke `.env`:

```dotenv
SPREADSHEET_EXPORT_TOKEN=GANTI_DENGAN_SECRET_ACAK_YANG_PANJANG
```

Kemudian jalankan:

```bash
php artisan optimize:clear
php artisan optimize
```

Endpoint:

```text
GET https://mokapppa.jakarta.go.id/dev-skb/api/v1/exports/service-logs
Authorization: Bearer SPREADSHEET_EXPORT_TOKEN
```

## Code.gs

Buka **Extensions → Apps Script**, lalu tempelkan kode berikut:

```javascript
const SKB_API_URL =
  'https://mokapppa.jakarta.go.id/dev-skb/api/v1/exports/service-logs';
const TARGET_SHEET = 'Log Layanan (Auto)';

function onOpen() {
  SpreadsheetApp.getUi()
    .createMenu('SKB')
    .addItem('Atur Token API', 'simpanTokenSKB')
    .addSeparator()
    .addItem('Perbarui Log Layanan', 'refreshLogLayanan')
    .addToUi();
}

function simpanTokenSKB() {
  const ui = SpreadsheetApp.getUi();
  const result = ui.prompt(
    'Token API SKB',
    'Masukkan SPREADSHEET_EXPORT_TOKEN dari server SKB.',
    ui.ButtonSet.OK_CANCEL
  );

  if (result.getSelectedButton() === ui.Button.OK) {
    PropertiesService.getScriptProperties()
      .setProperty('SKB_API_TOKEN', result.getResponseText().trim());
    ui.alert('Token berhasil disimpan.');
  }
}

function refreshLogLayanan() {
  const token = PropertiesService.getScriptProperties()
    .getProperty('SKB_API_TOKEN');

  if (!token) {
    throw new Error(
      'Token belum disimpan. Jalankan fungsi simpanTokenSKB terlebih dahulu.'
    );
  }

  const response = UrlFetchApp.fetch(SKB_API_URL, {
    method: 'get',
    headers: {
      Accept: 'application/json',
      Authorization: 'Bearer ' + token
    },
    muteHttpExceptions: true
  });

  if (response.getResponseCode() !== 200) {
    throw new Error(
      'API SKB gagal (' + response.getResponseCode() + '): ' +
      response.getContentText()
    );
  }

  const payload = JSON.parse(response.getContentText());
  const records = payload.data || [];
  const rows = records.map(function (item) {
    return [
      item.no_klien || '',
      item.inisial_korban || '',
      item.agenda || '',
      (item.layanan || []).join(', '),
      item.jumlah_layanan || 0,
      parseDate_(item.tanggal_pelaksanaan),
      parseDate_(item.tanggal_laporan),
      item.petugas || '',
      parseDate_(item.tanggal_diterima),
      item.pemohon || '',
      item.keterangan || ''
    ];
  });

  const sheet = SpreadsheetApp.getActive()
    .getSheetByName(TARGET_SHEET);

  if (!sheet) {
    throw new Error('Sheet "' + TARGET_SHEET + '" tidak ditemukan.');
  }

  const existingRows = Math.max(sheet.getLastRow() - 1, 0);
  if (existingRows > 0) {
    sheet.getRange(2, 1, existingRows, 11).clearContent();
  }

  if (rows.length > 0) {
    sheet.getRange(2, 1, rows.length, 11).setValues(rows);
    sheet.getRange(2, 6, rows.length, 1).setNumberFormat('dd/MM/yyyy');
    sheet.getRange(2, 7, rows.length, 1).setNumberFormat('dd/MM/yyyy HH:mm');
    sheet.getRange(2, 9, rows.length, 1).setNumberFormat('dd/MM/yyyy HH:mm');
  }

  SpreadsheetApp.getActive().toast(
    rows.length + ' baris berhasil diperbarui.',
    'SKB',
    5
  );
}

function parseDate_(value) {
  return value ? new Date(value) : '';
}
```

## Aktivasi

1. Simpan project Apps Script.
2. Dari editor, pilih fungsi `simpanTokenSKB`, kemudian klik **Run**.
3. Berikan izin ketika Google meminta otorisasi.
4. Masukkan token yang sama dengan `.env` SKB.
5. Muat ulang spreadsheet.
6. Menu **SKB → Perbarui Log Layanan** akan muncul.

Menu kustom adalah bentuk tombol yang paling stabil. Jika menginginkan tombol visual, buat Drawing di spreadsheet lalu gunakan **Assign script** dengan nama:

```text
refreshLogLayanan
```

Fungsi `onOpen()` berjalan setiap spreadsheet dibuka atau dimuat ulang untuk memasang menu. Data tidak otomatis ditarik pada refresh agar spreadsheet tidak lambat dan API tidak terpanggil tanpa sengaja.
