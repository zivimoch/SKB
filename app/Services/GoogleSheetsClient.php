<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GoogleSheetsClient
{
    public function replaceServiceLogRows(array $rows): void
    {
        if (! config('google_sheets.enabled')) {
            throw new RuntimeException('Integrasi Google Sheets belum diaktifkan.');
        }

        $spreadsheetId = (string) config('google_sheets.spreadsheet_id');
        $sheet = (string) config('google_sheets.service_log_sheet');
        if ($spreadsheetId === '' || $sheet === '') {
            throw new RuntimeException('ID spreadsheet atau nama sheet belum dikonfigurasi.');
        }

        $token = $this->accessToken();
        $baseUrl = 'https://sheets.googleapis.com/v4/spreadsheets/'.$spreadsheetId.'/values/';
        $clearRange = $this->a1($sheet, 'A2:K');

        Http::withToken($token)
            ->acceptJson()
            ->post($baseUrl.rawurlencode($clearRange).':clear')
            ->throw();

        if ($rows === []) {
            return;
        }

        $endRow = count($rows) + 1;
        $writeRange = $this->a1($sheet, 'A2:K'.$endRow);

        Http::withToken($token)
            ->acceptJson()
            ->put($baseUrl.rawurlencode($writeRange).'?valueInputOption=RAW', [
                'range' => $writeRange,
                'majorDimension' => 'ROWS',
                'values' => $rows,
            ])
            ->throw();
    }

    private function accessToken(): string
    {
        $credentials = $this->credentials();
        $cacheKey = 'google-sheets-token:'.hash('sha256', $credentials['client_email']);

        return Cache::remember($cacheKey, now()->addMinutes(50), function () use ($credentials): string {
            $now = time();
            $header = $this->base64Url(json_encode(['alg' => 'RS256', 'typ' => 'JWT'], JSON_THROW_ON_ERROR));
            $claims = $this->base64Url(json_encode([
                'iss' => $credentials['client_email'],
                'scope' => 'https://www.googleapis.com/auth/spreadsheets',
                'aud' => $credentials['token_uri'] ?? 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 3600,
            ], JSON_THROW_ON_ERROR));
            $unsigned = $header.'.'.$claims;

            if (! openssl_sign($unsigned, $signature, $credentials['private_key'], OPENSSL_ALGO_SHA256)) {
                throw new RuntimeException('Gagal menandatangani kredensial Google service account.');
            }

            $assertion = $unsigned.'.'.$this->base64Url($signature);
            $response = Http::asForm()->acceptJson()->post(
                $credentials['token_uri'] ?? 'https://oauth2.googleapis.com/token',
                [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $assertion,
                ]
            )->throw()->json();

            if (empty($response['access_token'])) {
                throw new RuntimeException('Google tidak mengembalikan access token.');
            }

            return $response['access_token'];
        });
    }

    private function credentials(): array
    {
        $path = (string) config('google_sheets.credentials_path');
        if ($path === '' || ! is_file($path) || ! is_readable($path)) {
            throw new RuntimeException('File kredensial Google service account tidak ditemukan atau tidak dapat dibaca.');
        }

        $credentials = json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        foreach (['client_email', 'private_key'] as $key) {
            if (empty($credentials[$key])) {
                throw new RuntimeException('Kredensial Google tidak memiliki '.$key.'.');
            }
        }

        return $credentials;
    }

    private function a1(string $sheet, string $range): string
    {
        return "'".str_replace("'", "''", $sheet)."'!".$range;
    }

    private function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
