<?php

namespace App\Http\Middleware;

use App\Services\IntegrationIdentityService;
use App\Support\ProblemDetails;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class VerifyIntegrationSignature
{
    public function __construct(private readonly IntegrationIdentityService $identities) {}

    public function handle(Request $request, Closure $next): Response
    {
        $keyId = (string) $request->header('X-SKB-Key-Id');
        $timestamp = (string) $request->header('X-SKB-Timestamp');
        $nonce = (string) $request->header('X-SKB-Nonce');
        $signature = (string) $request->header('X-SKB-Signature');
        $idempotencyKey = (string) $request->header('Idempotency-Key');
        $client = config("integrations.clients.{$keyId}") ?: $this->databaseClient($keyId);

        if (! $client || ! ($client['active'] ?? false) || empty($client['secret'])) {
            return $this->deny($request, 'Kredensial integrasi tidak valid.');
        }

        if (! ctype_digit($timestamp) || abs(now()->timestamp - (int) $timestamp) > config('integrations.clock_skew_seconds')) {
            return $this->deny($request, 'Timestamp request tidak valid atau kedaluwarsa.');
        }

        if (! preg_match('/^[A-Za-z0-9._:-]{16,128}$/', $nonce)) {
            return $this->deny($request, 'Nonce request tidak valid.');
        }

        $changesCaseData = $request->isMethod('PUT')
            || ($request->isMethod('POST') && $request->is('api/v1/integrations/cases/*/sync'));

        if ($changesCaseData && ! preg_match('/^[A-Za-z0-9._:-]{16,128}$/', $idempotencyKey)) {
            return response()->json(['message' => 'Idempotency-Key wajib untuk request perubahan data.'], 422);
        }

        $canonical = implode("\n", [
            strtoupper($request->method()),
            '/'.$request->path(),
            $request->getQueryString() ?? '',
            $timestamp,
            $nonce,
            $idempotencyKey,
            hash('sha256', $request->getContent()),
        ]);

        $valid = $this->matches($canonical, $signature, $client['secret'])
            || (! empty($client['previous_secret']) && $this->matches($canonical, $signature, $client['previous_secret']));

        if (! $valid) {
            return $this->deny($request, 'Signature request tidak valid.');
        }

        $nonceKey = 'integration-nonce:'.hash('sha256', $keyId.':'.$nonce);
        if (! Cache::add($nonceKey, true, config('integrations.nonce_ttl_seconds'))) {
            return $this->deny($request, 'Request terdeteksi sebagai replay.');
        }

        $client = $this->identities->registerClient($keyId, $client);
        $actor = $this->identities->resolveActor($request, $client);
        $request->attributes->set('integration_client', $client);
        $request->attributes->set('integration_actor', $actor);

        return $next($request);
    }

    private function matches(string $canonical, string $provided, string $secret): bool
    {
        $expected = base64_encode(hash_hmac('sha256', $canonical, $secret, true));

        return $provided !== '' && hash_equals($expected, $provided);
    }

    private function databaseClient(string $keyId): ?array
    {
        $row = DB::table('integration_clients')->where('key_id', $keyId)->where('active', true)->first();
        if (! $row || ! $row->secret_encrypted) {
            return null;
        }

        return [
            'id' => $row->id,
            'name' => $row->name,
            'source_system' => $row->source_system,
            'environment' => $row->environment,
            'scopes' => json_decode($row->scopes, true) ?: [],
            'secret' => Crypt::decryptString($row->secret_encrypted),
            'previous_secret' => $row->previous_secret_encrypted
                ? Crypt::decryptString($row->previous_secret_encrypted)
                : null,
            'active' => (bool) $row->active,
        ];
    }

    private function deny(Request $request, string $message): JsonResponse
    {
        return ProblemDetails::response(
            $request,
            401,
            'Request tidak terautentikasi',
            $message,
            'https://docs.skb.go.id/problems/invalid-signature'
        );
    }
}
