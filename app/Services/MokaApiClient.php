<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class MokaApiClient
{
    public function createAgenda(string $clientUuid, array $payload): array
    {
        return $this->send('POST', '/api/v1/skb/cases/'.rawurlencode($clientUuid).'/agendas', $payload, 'skb-agenda');
    }

    public function updateReport(string $reportUuid, array $payload): array
    {
        return $this->send('POST', '/api/v1/skb/reports/'.rawurlencode($reportUuid), $payload, 'skb-report');
    }

    private function send(string $method, string $path, array $payload, string $prefix): array
    {
        $secret = (string) config('moka.secret');
        if (strlen($secret) < 32) {
            throw new RuntimeException('MOKA_API_SECRET belum dikonfigurasi.');
        }

        $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $timestamp = (string) time();
        $nonce = (string) Str::uuid();
        $idempotency = $prefix.':'.($payload['command_id'] ?? str_replace('-', '', (string) Str::uuid()));
        $canonical = implode("\n", [$method, $path, '', $timestamp, $nonce, $idempotency, hash('sha256', $body)]);
        $signature = base64_encode(hash_hmac('sha256', $canonical, $secret, true));

        $response = Http::withOptions(['verify' => config('moka.verify_tls')])
            ->timeout(config('moka.timeout'))
            ->acceptJson()
            ->withHeaders([
                'Content-Type' => 'application/json',
                'X-SKB-Key-Id' => config('moka.key_id'),
                'X-SKB-Timestamp' => $timestamp,
                'X-SKB-Nonce' => $nonce,
                'X-SKB-Signature' => $signature,
                'Idempotency-Key' => $idempotency,
                'X-Request-Id' => (string) Str::uuid(),
            ])
            ->send($method, config('moka.base_url').$path, ['body' => $body]);

        if (! $response->successful()) {
            throw new RuntimeException('Moka API gagal ('.$response->status().'): '.($response->json('message') ?: 'respons tidak dikenali'));
        }

        return $response->json();
    }
}
