<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CaseWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_case_workspace_requires_login_and_renders_for_authorized_user(): void
    {
        $this->get('/cases')->assertRedirect('/login');

        $user = User::factory()->create(['active' => true]);
        $this->actingAs($user)
            ->get('/cases')
            ->assertOk()
            ->assertSee('Daftar Kasus');
    }
}
