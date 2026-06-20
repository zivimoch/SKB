<?php

$baseUrl = getenv('SKB_BASE_URL') ?: 'http://127.0.0.1:8000';
$keyId = getenv('SKB_KEY_ID');
$secret = getenv('SKB_SECRET');
$path = '/api/v1/integrations/echo';
$body = json_encode(['message' => 'test-koneksi'], JSON_UNESCAPED_SLASHES);
$timestamp = (string) time();
$nonce = bin2hex(random_bytes(16));
$requestId = sprintf(
    '%s-%s-4%s-%s%s-%s',
    bin2hex(random_bytes(4)),
    bin2hex(random_bytes(2)),
    bin2hex(random_bytes(2)),
    dechex(random_int(8, 11)),
    substr(bin2hex(random_bytes(2)), 1),
    bin2hex(random_bytes(6))
);
$canonical = implode("\n", ['POST', $path, '', $timestamp, $nonce, '', hash('sha256', $body)]);
$signature = base64_encode(hash_hmac('sha256', $canonical, $secret, true));

$curl = curl_init($baseUrl.$path);
curl_setopt_array($curl, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POSTFIELDS => $body,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Accept: application/json',
        'X-SKB-Key-Id: '.$keyId,
        'X-SKB-Timestamp: '.$timestamp,
        'X-SKB-Nonce: '.$nonce,
        'X-SKB-Signature: '.$signature,
        'X-Request-Id: '.$requestId,
        'X-SKB-Actor-Id: user-fiktif-001',
        'X-SKB-Actor-Name: Pengguna Sandbox',
        'X-SKB-Actor-Role: Case Manager',
        'X-SKB-Actor-Institution: Instansi Sandbox',
    ],
]);

$response = curl_exec($curl);
$status = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
curl_close($curl);

echo "HTTP {$status}\n{$response}\n";
