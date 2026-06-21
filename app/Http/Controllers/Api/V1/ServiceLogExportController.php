<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\ServiceLogExportService;
use Illuminate\Http\JsonResponse;

class ServiceLogExportController extends Controller
{
    public function __invoke(ServiceLogExportService $export): JsonResponse
    {
        $rows = $export->rows();

        return response()->json([
            'data' => $rows,
            'meta' => [
                'count' => count($rows),
                'generated_at' => now()->toIso8601String(),
            ],
        ]);
    }
}
