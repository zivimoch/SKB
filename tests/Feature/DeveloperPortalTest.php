<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tests\TestCase;

class DeveloperPortalTest extends TestCase
{
    use RefreshDatabase;

    private string $secret = 'developer-portal-test-secret-longer-than-32-characters';

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'cache.default' => 'array',
            'integrations.clients.portal-test' => [
                'name' => 'Portal Test',
                'source_system' => 'portal-test',
                'institution_code' => 'portal-instansi',
                'institution_name' => 'Portal Instansi',
                'environment' => 'sandbox',
                'scopes' => ['connection:test'],
                'secret' => $this->secret,
                'previous_secret' => null,
                'active' => true,
            ],
        ]);
        Cache::flush();
    }

    public function test_portal_openapi_and_health_are_public(): void
    {
        $this->get('/developers')->assertOk()->assertSee('SKB Developer Portal');
        $this->get('/developers/api')->assertOk();
        $this->get('/developers/openapi.yaml')->assertOk()->assertSee('openapi: 3.1.0');
        $this->get('/developers/docs/guides/onboarding.md')
            ->assertOk()
            ->assertSee('Onboarding');
        $this->getJson('/api/v1/health')->assertOk()->assertJsonPath('status', 'ok');
    }

    public function test_signed_me_registers_client_and_external_actor(): void
    {
        $path = '/api/v1/integrations/me';
        $timestamp = (string) time();
        $nonce = (string) Str::uuid();
        $canonical = implode("\n", ['GET', $path, '', $timestamp, $nonce, '', hash('sha256', '')]);
        $signature = base64_encode(hash_hmac('sha256', $canonical, $this->secret, true));

        $headers = [
            'X-SKB-Key-Id' => 'portal-test',
            'X-SKB-Timestamp' => $timestamp,
            'X-SKB-Nonce' => $nonce,
            'X-SKB-Signature' => $signature,
            'X-Request-Id' => (string) Str::uuid(),
            'X-SKB-Actor-Id' => 'actor-001',
            'X-SKB-Actor-Name' => 'Actor Fiktif',
            'X-SKB-Actor-Role' => 'Case Manager',
            'Accept' => 'application/json',
        ];
        $server = [];
        foreach ($headers as $name => $value) {
            $server['HTTP_'.strtoupper(str_replace('-', '_', $name))] = $value;
        }
        $response = $this->call('GET', $path, [], [], [], $server);

        $response->assertOk()
            ->assertJsonPath('data.client.source_system', 'portal-test')
            ->assertJsonPath('data.actor.external_id', 'actor-001');
        $this->assertDatabaseCount('integration_clients', 1);
        $this->assertDatabaseCount('external_actors', 1);
    }
}
