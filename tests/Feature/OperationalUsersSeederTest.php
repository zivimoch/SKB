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
            'external_id' => '35a1e5db-7ead-43ca-a326-2e2b4d5ffa327',
            'email' => 'mk@moka.ol',
            'role' => 'Manajer Kasus',
            'active' => true,
        ]);
        $this->assertDatabaseHas('users', [
            'external_id' => '35a1e5db-7ead-43ca-a326-2e2b4d5ffa329',
            'email' => 'paralegal@moka.ol',
            'role' => 'Paralegal',
            'active' => true,
        ]);
    }
}
