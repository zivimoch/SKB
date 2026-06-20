# Kamus Data: Person

Role yang didukung: `reporter`, `victim`, dan `respondent`.

Identifier utama adalah `source_system + source_id`. Field identitas dapat berisi NIK, nama, tanggal lahir, gender, alamat, telepon, pendidikan, pekerjaan, kewarganegaraan, disabilitas, dan hubungan antar pihak.

Seluruh identitas disimpan terenkripsi. API consumer wajib menerapkan data minimization: jangan mengirim field yang tidak diperlukan untuk tujuan integrasi yang disepakati.
