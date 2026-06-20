# Pemetaan domain MokaV2 ke SKB

Snapshot integrasi berpusat pada satu `klien.uuid`, karena satu kasus Moka dapat memiliki lebih dari satu korban/klien dengan kebutuhan layanan berbeda.

| Tahap SKB | Sumber MokaV2 | Isi utama |
|---|---|---|
| Identifikasi kasus | `kasus`, `klien`, `pelapor`, `terlapor`, tabel klasifikasi | registrasi, kejadian, lokasi, korban, pelapor, terlapor, kategori dan bentuk kekerasan |
| Asesmen awal | `riwayat_kejadian`, `asesmen` | kondisi fisik, psikologis, sosial-ekonomi, hukum, upaya, faktor pendukung, hambatan, kebutuhan |
| Perencanaan intervensi | `agenda`, `petugas` | siklus intervensi, agenda, jadwal, petugas |
| Pelaksanaan intervensi | `tindak_lanjut`, `t_keyword`, `m_keyword` | penerimaan/penolakan tugas, layanan, proses-hasil, RTL, durasi |
| Pemantauan dan evaluasi | `pemantauan` | kemajuan, evaluasi tujuan, rencana, keputusan |
| Terminasi | `terminasi` | selesai/ditutup, alasan, status persetujuan |

## Aturan proses yang dipertahankan

- intervensi dimulai dari siklus 1;
- status tindak lanjut: `pending`, `accepted`, `rejected`, atau `done`;
- penugasan yang ditolak tidak dihitung sebagai pekerjaan aktif;
- monev hanya dilakukan setelah semua tindak lanjut yang diterima selesai;
- keputusan monev membuka siklus berikutnya atau mengajukan terminasi;
- identitas sumber tidak menjadi primary key SKB: uniqueness memakai `source_system + source_id`;
- sinkronisasi adalah snapshot idempoten; pengiriman ulang payload yang sama aman.
