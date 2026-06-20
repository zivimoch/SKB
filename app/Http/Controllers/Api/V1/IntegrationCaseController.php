<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\CaseBundleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class IntegrationCaseController extends Controller
{
    public function __construct(private readonly CaseBundleService $cases) {}

    public function upsert(Request $request, string $externalCaseId): JsonResponse
    {
        $client = $request->attributes->get('integration_client');
        $actor = $request->attributes->get('integration_actor');
        $idempotencyKey = (string) $request->header('Idempotency-Key');
        $requestHash = hash('sha256', $request->getContent());

        $receipt = DB::table('integration_receipts')
            ->where('client_key_id', $client['key_id'])
            ->where('idempotency_key', $idempotencyKey)
            ->first();

        if ($receipt) {
            if (! hash_equals($receipt->request_hash, $requestHash)) {
                return response()->json([
                    'message' => 'Idempotency-Key pernah digunakan untuk payload yang berbeda.',
                ], 409);
            }

            return response()->json(json_decode($receipt->response_body, true), $receipt->response_status)
                ->header('X-SKB-Idempotent-Replay', 'true');
        }

        try {
            $payload = $request->validate($this->rules());
        } catch (ValidationException $exception) {
            throw $exception;
        }

        $result = DB::transaction(function () use ($payload, $externalCaseId, $client): array {
            return $this->cases->upsert($client['source_system'], $externalCaseId, $payload);
        }, 3);

        $body = [
            'data' => [
                'id' => $result['id'],
                'source_system' => $client['source_system'],
                'source_id' => $externalCaseId,
                'created' => $result['created'],
                'last_synced_at' => $result['last_synced_at'],
                'profile_synced_at' => $result['profile_synced_at'],
            ],
        ];

        DB::table('integration_receipts')->insert([
            'id' => (string) Str::uuid(),
            'client_key_id' => $client['key_id'],
            'idempotency_key' => $idempotencyKey,
            'request_hash' => $requestHash,
            'response_status' => $result['created'] ? 201 : 200,
            'response_body' => json_encode($body, JSON_THROW_ON_ERROR),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->audit($request, 'case.bundle.upserted', $externalCaseId, $requestHash, [
            'created' => $result['created'],
            'actor' => $actor,
        ]);

        return response()->json($body, $result['created'] ? 201 : 200);
    }

    public function show(Request $request, string $externalCaseId): JsonResponse
    {
        $client = $request->attributes->get('integration_client');
        $actor = $request->attributes->get('integration_actor');
        $case = $this->cases->find($client['source_system'], $externalCaseId);

        if (! $case) {
            return response()->json(['message' => 'Data kasus tidak ditemukan.'], 404);
        }

        $this->audit($request, 'case.bundle.read', $externalCaseId, null, ['actor' => $actor]);

        return response()->json(['data' => $case]);
    }

    private function rules(): array
    {
        return [
            'schema_version' => ['required', 'string', 'in:1.0'],
            'sync_scope' => ['nullable', 'string', 'in:full,case_profile'],
            'source_version' => ['nullable', 'string', 'max:100'],
            'source_updated_at' => ['nullable', 'date'],
            'case' => ['required', 'array'],
            'case.registration_number' => ['nullable', 'string', 'max:255'],
            'case.client_number' => ['nullable', 'string', 'max:255'],
            'case.status' => ['nullable', 'string', 'max:50'],
            'case.reported_at' => ['nullable', 'date'],
            'case.occurred_at' => ['nullable', 'date'],
            'case.occurred_at_estimated' => ['nullable', 'boolean'],
            'case.summary' => ['nullable', 'string'],
            'case.location' => ['nullable', 'array'],
            'case.classifications' => ['nullable', 'array'],
            'case.active_intervention_cycle' => ['nullable', 'integer', 'min:1'],
            'people' => ['nullable', 'array'],
            'people.*.source_id' => ['nullable', 'string', 'max:191'],
            'people.*.role' => ['required', 'in:reporter,victim,respondent'],
            'people.*.identity' => ['required', 'array'],
            'officers' => ['nullable', 'array'],
            'officers.*.source_id' => ['required', 'uuid'],
            'officers.*.name' => ['required', 'string', 'max:255'],
            'officers.*.role' => ['nullable', 'string', 'max:100'],
            'officers.*.institution' => ['nullable', 'string', 'max:255'],
            'event_histories' => ['nullable', 'array'],
            'event_histories.*.source_id' => ['nullable', 'string', 'max:191'],
            'event_histories.*.event_date' => ['nullable', 'date'],
            'event_histories.*.event_time' => ['nullable', 'date_format:H:i:s'],
            'event_histories.*.description' => ['required', 'string'],
            'assessments' => ['nullable', 'array'],
            'assessments.*.source_id' => ['nullable', 'string', 'max:191'],
            'assessments.*.assessed_at' => ['nullable', 'date'],
            'assessments.*.content' => ['required', 'array'],
            'interventions' => ['nullable', 'array'],
            'interventions.*.cycle_number' => ['required', 'integer', 'min:1'],
            'interventions.*.status' => ['nullable', 'string', 'max:30'],
            'interventions.*.activities' => ['nullable', 'array'],
            'interventions.*.activities.*.source_id' => ['required', 'string', 'max:191'],
            'interventions.*.activities.*.title' => ['required', 'string'],
            'interventions.*.activities.*.scheduled_date' => ['nullable', 'date'],
            'interventions.*.activities.*.scheduled_time' => ['nullable', 'date_format:H:i:s'],
            'interventions.*.activities.*.reports' => ['nullable', 'array'],
            'interventions.*.activities.*.reports.*.source_id' => ['required', 'string', 'max:191'],
            'interventions.*.activities.*.reports.*.officer_source_id' => ['nullable', 'string', 'max:191'],
            'interventions.*.activities.*.reports.*.status' => ['required', 'in:pending,accepted,rejected,done'],
            'interventions.*.activities.*.reports.*.confirmed_at' => ['nullable', 'date'],
            'interventions.*.activities.*.reports.*.completed_at' => ['nullable', 'date'],
            'interventions.*.activities.*.reports.*.content' => ['nullable', 'array'],
            'interventions.*.monitoring_evaluation' => ['nullable', 'array'],
            'interventions.*.monitoring_evaluation.source_id' => ['nullable', 'string', 'max:191'],
            'interventions.*.monitoring_evaluation.decision' => ['nullable', 'string', 'max:50'],
            'interventions.*.monitoring_evaluation.content' => ['required_with:interventions.*.monitoring_evaluation', 'array'],
            'terminations' => ['nullable', 'array'],
            'terminations.*.source_id' => ['nullable', 'string', 'max:191'],
            'terminations.*.type' => ['required', 'in:selesai,ditutup'],
            'terminations.*.status' => ['nullable', 'string', 'max:30'],
            'terminations.*.reason' => ['nullable', 'string'],
        ];
    }

    private function audit(
        Request $request,
        string $action,
        string $resourceId,
        ?string $requestHash,
        array $metadata = []
    ): void {
        $client = $request->attributes->get('integration_client');

        DB::table('security_audit_logs')->insert([
            'id' => (string) Str::uuid(),
            'actor_type' => 'integration_client',
            'actor_id' => $client['key_id'],
            'action' => $action,
            'resource_type' => 'case',
            'resource_id' => $resourceId,
            'request_id' => $request->header('X-Request-Id') ?: $request->header('Idempotency-Key'),
            'ip_hash' => hash_hmac('sha256', (string) $request->ip(), config('app.key')),
            'request_hash' => $requestHash,
            'metadata' => json_encode($metadata, JSON_THROW_ON_ERROR),
            'occurred_at' => now(),
        ]);
    }
}
