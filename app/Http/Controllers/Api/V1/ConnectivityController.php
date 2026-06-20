<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConnectivityController extends Controller
{
    public function health(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'service' => 'skb-api',
            'version' => 'v1',
            'time' => now()->toIso8601String(),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $client = $request->attributes->get('integration_client');
        $actor = $request->attributes->get('integration_actor');

        return response()->json([
            'data' => [
                'client' => [
                    'key_id' => $client['key_id'],
                    'name' => $client['name'],
                    'source_system' => $client['source_system'],
                    'environment' => $client['environment'] ?? 'sandbox',
                    'scopes' => $client['scopes'] ?? [],
                ],
                'actor' => $actor,
                'server_time' => now()->toIso8601String(),
            ],
        ]);
    }

    public function echo(Request $request): JsonResponse
    {
        return response()->json([
            'data' => [
                'received' => $request->json()->all(),
                'request_id' => $request->header('X-Request-Id'),
                'received_at' => now()->toIso8601String(),
                'message' => 'Koneksi dan signature valid.',
            ],
        ]);
    }
}
