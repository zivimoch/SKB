<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ServiceKeywordsSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $rows = array_map('str_getcsv', explode("\n", trim($this->csv())));
        $headers = array_shift($rows);

        foreach ($rows as $row) {
            $item = array_combine($headers, $row);
            $sourceId = (string) $item['id'];
            $existing = DB::table('m_keywords')
                ->where('source_system', 'seed:mokav2')
                ->where('source_id', $sourceId)
                ->first();

            $payload = [
                'jabatan' => $item['jabatan'],
                'keyword' => trim($item['keyword']),
                'jenis_agenda' => $item['jenis_agenda'] ?: 'Layanan',
                'active' => true,
                'updated_at' => $now,
                'deleted_at' => null,
            ];

            if ($existing) {
                DB::table('m_keywords')->where('id', $existing->id)->update($payload);

                continue;
            }

            DB::table('m_keywords')->insert($payload + [
                'id' => (string) Str::uuid(),
                'source_system' => 'seed:mokav2',
                'source_id' => $sourceId,
                'created_at' => $now,
            ]);
        }
    }

    private function csv(): string
    {
        return <<<'CSV'
id,jabatan,keyword,jenis_agenda
1,Konselor,Pengukuran Awal,Layanan
2,Konselor,Administrasi Tes Psikologi,Layanan
3,Konselor,Psikososial / Psikoedukasi,Layanan
4,Konselor,Pendampingan Psikologi,Layanan
5,Konselor,Konseling,Layanan
6,Konselor,Pemeriksaan Psikologi,Layanan
7,Konselor,Hasil Pemeriksaan Psikologi,Layanan
8,Konselor,BAP / Saksi Ahli,Layanan
9,Konselor,Proyeksi Restitusi,Layanan
10,Konselor,Verifikasi HPP / BAP,Layanan
11,Psikolog,Pengukuran Awal,Layanan
12,Psikolog,Administrasi Tes Psikologi,Layanan
13,Psikolog,Psikososial / Psikoedukasi,Layanan
14,Psikolog,Pendampingan Psikologi,Layanan
15,Psikolog,Konseling,Layanan
16,Psikolog,Pemeriksaan Psikologi,Layanan
17,Psikolog,Hasil Pemeriksaan Psikologi,Layanan
18,Psikolog,BAP / Saksi Ahli,Layanan
19,Psikolog,Proyeksi Restitusi,Manajemen Layanan
20,Psikolog,Verifikasi HPP / BAP,Layanan
21,Advokat,Konsultasi Hukum,Layanan
22,Advokat,Pendampingan di Kepolisian,Layanan
23,Advokat,Mediasi,Layanan
24,Paralegal,Konsultasi Hukum,Layanan
25,Paralegal,Pendampingan di Kepolisian,Layanan
26,Paralegal,Pendampingan di PN,Layanan
27,Paralegal,Pendampingan di PA,Layanan
28,Paralegal,Penjangkauan Paralegal POS,Layanan
29,Paralegal,Penjangkauan Paralegal URC,Layanan
30,Paralegal,Pendampingan Lainnya,Manajemen Layanan
31,Unit Reaksi Cepat,Konsultasi Hukum,Layanan
32,Unit Reaksi Cepat,Pendampingan di Kepolisian,Layanan
33,Unit Reaksi Cepat,Pendampingan di PN,Layanan
34,Unit Reaksi Cepat,Pendampingan di PA,Layanan
35,Unit Reaksi Cepat,Penjangkauan Paralegal URC,Layanan
36,Unit Reaksi Cepat,Pendampingan Lainnya,Layanan
37,Pendamping Kasus,Pendampingan Lainnya,Layanan
38,Pendamping Kasus,Pendampingan Puskesmas Rawat Inap,Layanan
39,Pendamping Kasus,Pendampingan Puskesmas Rawat Jalan,Layanan
40,Pendamping Kasus,Pendampingan Rumah Sakit Rawat Inap,Layanan
41,Pendamping Kasus,Pendampingan Rumah Sakit Rawat Jalan,Layanan
42,Pendamping Kasus,Pendampingan Visum,Layanan
43,Pendamping Kasus,Pendampingan BAP,Layanan
44,Pendamping Kasus,Pendampingan LP,Layanan
45,Pendamping Kasus,Rujukan,Layanan
46,Pendamping Kasus,Home Visit,Layanan
47,Pendamping Kasus,School Visit,Layanan
48,Manajer Kasus,Koordinasi Internal,Manajemen Layanan
49,Manajer Kasus,Koordinasi Eksternal,Manajemen Layanan
50,Manajer Kasus,Rujukan,Manajemen Layanan
51,Manajer Kasus,Pengelolaan Dokumen Klien,Manajemen Layanan
52,Pendamping Kasus,Penjangkauan,Layanan
53,Advokat,Supervisi,Manajemen Layanan
54,Manajer Kasus,Review Kasus,Manajemen Layanan
55,Manajer Kasus,Asesmen Lanjutan,Manajemen Layanan
56,Manajer Kasus,Case Conference,Manajemen Layanan
57,Unit Reaksi Cepat,Pendampingan Puskesmas Rawat Inap,Layanan
58,Unit Reaksi Cepat,Pendampingan Puskesmas Rawat Jalan,Layanan
59,Unit Reaksi Cepat,Pendampingan Rumah Sakit Rawat Inap,Layanan
60,Unit Reaksi Cepat,Pendampingan Rumah Sakit Rawat Jalan,Layanan
61,Unit Reaksi Cepat,Pendampingan Rumah Sakit Visum,Layanan
62,Paralegal,Pendampingan Puskesmas Rawat Inap,Manajemen Layanan
63,Paralegal,Pendampingan Puskesmas Rawat Jalan,Manajemen Layanan
64,Paralegal,Pendampingan Rumah Sakit Rawat Inap,Manajemen Layanan
65,Paralegal,Pendampingan Rumah Sakit Rawat Jalan,Manajemen Layanan
66,Paralegal,Pendampingan Rumah Sakit Visum,Manajemen Layanan
67,Konselor,Pendampingan Rumah Sakit Visum,Layanan
68,Konselor,Pendampingan Puskesmas Rawat Inap,Layanan
69,Konselor,Pendampingan Puskesmas Rawat Jalan,Layanan
70,Konselor,Pendampingan Rumah Sakit Rawat Inap,Layanan
71,Konselor,Pendampingan Rumah Sakit Rawat Jalan,Layanan
72,Pendamping Kasus,Pemulangan,Layanan
73,Manajer Kasus,Pemulangan,Layanan
74,Pendamping Kasus,Rujukan ke Rumah Aman,Layanan
75,Pendamping Kasus,Rujukan ke UPTD PPA,Layanan
76,Pendamping Kasus,Rujukan ke KemenPPA,Layanan
77,Pendamping Kasus,Rujukan ke Panti Dinas Sosial,Layanan
78,Pendamping Kasus,Rujukan ke Lembaga Lain,Layanan
79,Paralegal,Co-Mediator,Layanan
80,Manajer Kasus,School Visit,Layanan
81,Pendamping Kasus,Penerimaan Akses Layanan RPS,Layanan
82,Pendamping Kasus,Penghentian Akses Layanan RPS,Layanan
83,Pendamping Kasus,Pendampingan di Kantor Pusat,Layanan
84,Pendamping Kasus,Asesmen Lanjutan,Manajemen Layanan
85,Psikolog,Supervisi,Manajemen Layanan
86,Advokat,Pendampingan di PA,Layanan
87,Advokat,Pendampingan di PN,Layanan
88,Tenaga Ahli,School Visit,Layanan
89,Tenaga Ahli,Case Conference,Manajemen Layanan
90,Tenaga Ahli,Asesmen Lanjutan,Manajemen Layanan
91,Tenaga Ahli,Riview Kasus,Manajemen Layanan
92,Tenaga Ahli,Pengelolaan Dokumen Klien,Manajemen Layanan
93,Tenaga Ahli,Rujukan,Layanan
94,Tenaga Ahli,Koordinasi Eksternal,Manajemen Layanan
95,Tenaga Ahli,Koordinasi Internal,Manajemen Layanan
97,Tenaga Ahli,Home Visit,Layanan
98,Tenaga Ahli,School Visit,Layanan
99,Tenaga Ahli,Rujukan ke Panti Dinas Sosial,Layanan
100,Tenaga Ahli,Penyiapan Pemulangan & Reintegrasi Sosial,Layanan
101,Tenaga Ahli,Pendampingan Lainnya,Layanan
102,Tenaga Ahli,Pendampingan Puskesmas Rawat Inap,Layanan
103,Tenaga Ahli,Penjangkauan,Layanan
104,Tenaga Ahli,Pendampingan Puskesmas Rawat Jalan,Layanan
105,Tenaga Ahli,Pendampingan Rumah Sakit Rawat Jalan,Layanan
106,Tenaga Ahli,Pendampingan Rumah Sakit Visum,Layanan
107,Tenaga Ahli,Pendampingan di Kantor Pusat,Layanan
108,Tenaga Ahli,Pendampingan Rumah Sakit Rawat Inap,Layanan
109,Tenaga Ahli,Pendampingan BAP,Layanan
110,Manajer Kasus,Home Visit,Layanan
111,Tenaga Ahli,Pendampingan Psikososial,Layanan
112,Pendamping Kasus,Pendampingan Psikososial,Layanan
113,Tenaga Ahli,Penyiapan Pemulangan & Reintegrasi Sosial,Layanan
114,Pendamping Kasus,Pendampingan Fasilitas Kesehatan,Layanan
115,Advokat,Koordinasi Eksternal,Manajemen Layanan
116,Advokat,Koordinasi Internal,Manajemen Layanan
117,Psikolog,Koordinasi Internal,Manajemen Layanan
640,Pendamping Kasus,Pendampingan Psikososial,Layanan
641,Pendamping Kasus,Rujukan,Layanan
642,Pendamping Kasus,Penyiapan Reintegrasi Sosial,Layanan
643,Manajer Kasus,Family Group Conference,Manajemen Layanan
644,Tenaga Ahli,Supervisi,Manajemen Layanan
CSV;
    }
}
