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
            'external_id' => '43a348be-9f12-42f5-b567-f095f25111cd',
            'email' => 'mk@moka.ol',
            'role' => 'Manajer Kasus',
            'active' => true,
        ]);
        $this->assertDatabaseHas('users', [
            'external_id' => '43a348be-9112-42f5-b567-f095fc5111cd',
            'email' => 'paralegal@moka.ol',
            'role' => 'Paralegal',
            'active' => true,
        ]);
    }
}
