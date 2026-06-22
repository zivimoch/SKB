<?php

namespace Tests\Feature;

use Database\Seeders\OperationalUsersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperationalUsersSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_moka_operational_users_idempotently(): void
    {
        $this->seed(OperationalUsersSeeder::class);
        $this->seed(OperationalUsersSeeder::class);

        $this->assertDatabaseCount('users', 2);
        $this->assertDatabaseHas('users', [
            'external_id' => '6827f662-4f7f-4686-93b1-edc0b1104e52',
            'email' => 'mk@moka.ol',
            'role' => 'Manajer Kasus',
            'active' => true,
        ]);
        $this->assertDatabaseHas('users', [
            'external_id' => '060877b0-cb76-445c-8d56-c8283261b118',
            'email' => 'paralegal@moka.ol',
            'role' => 'Paralegal',
            'active' => true,
        ]);
    }
}
