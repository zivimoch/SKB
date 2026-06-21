<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifySpreadsheetExportToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('integrations.spreadsheet_export_token');
        $provided = (string) $request->bearerToken();

        if (strlen($expected) < 32 || $provided === '' || ! hash_equals($expected, $provided)) {
            return response()->json(['message' => 'Token export spreadsheet tidak valid.'], 401);
        }

        return $next($request);
    }
}
