# Kamus Data: Case

| Field | Tipe | Wajib | Keterangan |
|---|---|---:|---|
| `source_id` | string | Ya | ID stabil dari aplikasi sumber |
| `registration_number` | string/null | Tidak | Nomor registrasi kasus |
| `client_number` | string/null | Tidak | Nomor klien/korban |
| `status` | string/null | Tidak | Status penanganan |
| `reported_at` | date | Tidak | Tanggal pelaporan, `YYYY-MM-DD` |
| `occurred_at` | date | Tidak | Tanggal kejadian |
| `occurred_at_estimated` | boolean | Ya | Penanda tanggal perkiraan |
| `summary` | string/null | Tidak | Ringkasan kasus, data rahasia |
| `location` | object/null | Tidak | Alamat dan kode wilayah |
| `classifications` | object/null | Tidak | Kategori, jenis, bentuk kekerasan |
| `active_intervention_cycle` | integer | Ya | Siklus intervensi aktif |

Data identitas, ringkasan, dan alamat diklasifikasikan rahasia dan dienkripsi pada application layer.
