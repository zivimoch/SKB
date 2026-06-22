<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OperationalUsersSeeder extends Seeder
{
    /**
     * Akun development yang bersumber dari database/data/users.csv MokaV2.
     *
     * Hash password dipertahankan agar kredensial development sama dengan
     * akun contoh di MokaV2. Ganti password sebelum penggunaan production.
     */
    public function run(): void
    {
        $now = now();
        $passwordHash = '$2y$10$o5g3uSPxMY7/QfRErCP8fufH3NLSJivnfhvGgbf7HrIByL6FvsGwS';

        $users = [
            [
                'external_id' => '6827f662-4f7f-4686-93b1-edc0b1104e52',
                'external_system' => 'mokav2',
                'name' => 'Alex Ferguson',
                'email' => 'mk@moka.ol',
                'role' => 'Manajer Kasus',
            ],
            [
                'external_id' => '060877b0-cb76-445c-8d56-c8283261b118',
                'external_system' => 'mokav2',
                'name' => 'John Constantine',
                'email' => 'paralegal@moka.ol',
                'role' => 'Paralegal',
            ],
        ];

        foreach ($users as $user) {
            DB::table('users')->updateOrInsert(
                ['email' => $user['email']],
                array_merge($user, [
                    'email_verified_at' => $now,
                    'password' => $passwordHash,
                    'active' => true,
                    'updated_at' => $now,
                    'created_at' => $now,
                ])
            );
        }
    }
}
