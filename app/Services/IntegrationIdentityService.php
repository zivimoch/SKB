<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class IntegrationIdentityService
{
    public function registerClient(string $keyId, array $client): array
    {
        if (! empty($client['id'])) {
            DB::table('integration_clients')->where('id', $client['id'])->update([
                'last_seen_at' => now(),
                'updated_at' => now(),
            ]);

            return array_merge($client, ['key_id' => $keyId]);
        }

        $institutionId = null;
        if (! empty($client['institution_code'])) {
            $institutionId = DB::table('institutions')->where('code', $client['institution_code'])->value('id')
                ?: (string) Str::uuid();
            DB::table('institutions')->updateOrInsert(
                ['code' => $client['institution_code']],
                [
                    'id' => $institutionId,
                    'name' => $client['institution_name'] ?? $client['institution_code'],
                    'active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        $clientId = DB::table('integration_clients')->where('key_id', $keyId)->value('id')
            ?: (string) Str::uuid();
        DB::table('integration_clients')->updateOrInsert(
            ['key_id' => $keyId],
            [
                'id' => $clientId,
                'institution_id' => $institutionId,
                'name' => $client['name'],
                'source_system' => $client['source_system'],
                'scopes' => json_encode($client['scopes'] ?? [], JSON_THROW_ON_ERROR),
                'environment' => $client['environment'] ?? 'sandbox',
                'active' => true,
                'last_seen_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        return array_merge($client, ['id' => $clientId, 'key_id' => $keyId]);
    }

    public function resolveActor(Request $request, array $client): ?array
    {
        $externalId = trim((string) $request->header('X-SKB-Actor-Id'));
        if ($externalId === '') {
            return null;
        }

        $actorId = DB::table('external_actors')
            ->where('integration_client_id', $client['id'])
            ->where('external_id', $externalId)
            ->value('id') ?: (string) Str::uuid();

        DB::table('external_actors')->updateOrInsert(
            ['integration_client_id' => $client['id'], 'external_id' => $externalId],
            [
                'id' => $actorId,
                'name' => $request->header('X-SKB-Actor-Name'),
                'role' => $request->header('X-SKB-Actor-Role'),
                'institution_name' => $request->header('X-SKB-Actor-Institution'),
                'active' => true,
                'first_seen_at' => DB::table('external_actors')->where('id', $actorId)->value('first_seen_at') ?: now(),
                'last_seen_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        return [
            'id' => $actorId,
            'external_id' => $externalId,
            'name' => $request->header('X-SKB-Actor-Name'),
            'role' => $request->header('X-SKB-Actor-Role'),
            'institution' => $request->header('X-SKB-Actor-Institution'),
        ];
    }
}
