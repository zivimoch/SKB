<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProblemDetails
{
    public static function response(
        Request $request,
        int $status,
        string $title,
        string $detail,
        string $type = 'about:blank',
        array $extensions = []
    ): JsonResponse {
        return response()->json(array_merge([
            'type' => $type,
            'title' => $title,
            'status' => $status,
            'detail' => $detail,
            'instance' => '/'.$request->path(),
            'request_id' => $request->header('X-Request-Id'),
        ], $extensions), $status, ['Content-Type' => 'application/problem+json']);
    }
}
