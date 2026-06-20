<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class IntegrationCaseApiTest extends TestCase
{
    use RefreshDatabase;

    private string $secret = 'test-secret-that-is-longer-than-thirty-two-characters';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'cache.default' => 'array',
            'integrations.clients.test-key' => [
                'name' => 'Test',
                'source_system' => 'mokav2',
                'institution_code' => 'test-instansi',
                'institution_name' => 'Instansi Test',
                'environment' => 'sandbox',
                'scopes' => ['connection:test', 'cases:read', 'cases:write'],
                'secret' => $this->secret,
                'previous_secret' => null,
                'active' => true,
            ],
        ]);
        Cache::flush();
    }

    public function test_signed_bundle_is_stored_encrypted_and_is_idempotent(): void
    {
        $payload = $this->payload();
        $idempotencyKey = 'test-idempotency-key-0001';

        $response = $this->signedPut('client-uuid-1', $payload, $idempotencyKey);
        $response->assertCreated()->assertJsonPath('data.source_id', 'client-uuid-1');

        $case = DB::table('hub_cases')->first();
        $this->assertNotNull($case);
        $this->assertStringNotContainsString('Rahasia', $case->summary_encrypted);
        $this->assertSame('"Ringkasan Rahasia"', Crypt::decryptString($case->summary_encrypted));
        $this->assertDatabaseCount('hub_cases', 1);
        $this->assertDatabaseCount('case_people', 1);
        $this->assertDatabaseCount('intervention_cycles', 1);

        $profilePayload = $payload;
        $profilePayload['sync_scope'] = 'case_profile';
        $profilePayload['case']['summary'] = 'Ringkasan Profil Diperbarui';
        unset($profilePayload['interventions'], $profilePayload['terminations'], $profilePayload['officers']);
        $profile = $this->signedPut('client-uuid-1', $profilePayload, 'test-idempotency-key-profile-01');
        $profile->assertOk()->assertJsonPath('data.profile_synced_at', fn ($value) => is_string($value));
        $this->assertDatabaseCount('intervention_cycles', 1);
        $this->assertNotNull(DB::table('hub_cases')->value('profile_synced_at'));

        $replay = $this->signedPut('client-uuid-1', $payload, $idempotencyKey);
        $replay->assertCreated()->assertHeader('X-SKB-Idempotent-Replay', 'true');
        $this->assertDatabaseCount('hub_cases', 1);
    }

    public function test_post_sync_endpoint_supports_waf_that_blocks_put(): void
    {
        $response = $this->signedPost(
            'client-post-1',
            $this->payload(),
            'test-idempotency-post-0001'
        );

        $response->assertCreated()->assertJsonPath('data.source_id', 'client-post-1');
        $this->assertDatabaseHas('hub_cases', [
            'source_system' => 'mokav2',
            'source_id' => 'client-post-1',
        ]);
    }

    public function test_replayed_nonce_and_invalid_signature_are_rejected(): void
    {
        $payload = $this->payload();
        $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $timestamp = (string) time();
        $nonce = (string) Str::uuid();
        $idempotencyKey = 'test-idempotency-key-0002';
        $headers = $this->headers('PUT', '/api/v1/integrations/cases/client-uuid-2', $body, $idempotencyKey, $timestamp, $nonce);

        $this->call('PUT', '/api/v1/integrations/cases/client-uuid-2', [], [], [], $this->server($headers), $body)
            ->assertCreated();
        $this->call('PUT', '/api/v1/integrations/cases/client-uuid-2', [], [], [], $this->server($headers), $body)
            ->assertUnauthorized()
            ->assertJsonPath('detail', 'Request terdeteksi sebagai replay.');

        $headers['X-SKB-Nonce'] = (string) Str::uuid();
        $headers['X-SKB-Signature'] = 'invalid';
        $this->call('PUT', '/api/v1/integrations/cases/client-uuid-3', [], [], [], $this->server($headers), $body)
            ->assertUnauthorized();
    }

    private function signedPut(string $externalId, array $payload, string $idempotencyKey)
    {
        $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $path = '/api/v1/integrations/cases/'.$externalId;
        $headers = $this->headers(
            'PUT',
            $path,
            $body,
            $idempotencyKey,
            (string) time(),
            (string) Str::uuid()
        );

        return $this->call('PUT', $path, [], [], [], $this->server($headers), $body);
    }

    private function signedPost(string $externalId, array $payload, string $idempotencyKey)
    {
        $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $path = '/api/v1/integrations/cases/'.$externalId.'/sync';
        $headers = $this->headers(
            'POST',
            $path,
            $body,
            $idempotencyKey,
            (string) time(),
            (string) Str::uuid()
        );

        return $this->call('POST', $path, [], [], [], $this->server($headers), $body);
    }

    private function headers(
        string $method,
        string $path,
        string $body,
        string $idempotencyKey,
        string $timestamp,
        string $nonce
    ): array {
        $canonical = implode("\n", [
            $method,
            $path,
            '',
            $timestamp,
            $nonce,
            $idempotencyKey,
            hash('sha256', $body),
        ]);

        return [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'X-SKB-Key-Id' => 'test-key',
            'X-SKB-Timestamp' => $timestamp,
            'X-SKB-Nonce' => $nonce,
            'X-SKB-Signature' => base64_encode(hash_hmac('sha256', $canonical, $this->secret, true)),
            'Idempotency-Key' => $idempotencyKey,
        ];
    }

    private function server(array $headers): array
    {
        $server = [];
        foreach ($headers as $name => $value) {
            $key = strtoupper(str_replace('-', '_', $name));
            $server[in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'], true) ? $key : 'HTTP_'.$key] = $value;
        }

        return $server;
    }

    private function payload(): array
    {
        return [
            'schema_version' => '1.0',
            'source_version' => '202606200001',
            'source_updated_at' => '2026-06-20T10:00:00+07:00',
            'case' => [
                'registration_number' => 'REG-1',
                'client_number' => 'CLIENT-1',
                'status' => 'aktif',
                'reported_at' => '2026-06-20',
                'occurred_at' => '2026-06-19',
                'occurred_at_estimated' => false,
                'summary' => 'Ringkasan Rahasia',
                'location' => ['province_code' => '31'],
                'classifications' => ['case_categories' => ['A']],
                'active_intervention_cycle' => 1,
            ],
            'people' => [[
                'source_id' => 'victim-1',
                'role' => 'victim',
                'identity' => ['name' => 'Nama Sangat Rahasia', 'nik' => '1234567890123456'],
            ]],
            'event_histories' => [],
            'assessments' => [],
            'interventions' => [[
                'cycle_number' => 1,
                'status' => 'active',
                'activities' => [],
            ]],
            'terminations' => [],
        ];
    }
}
