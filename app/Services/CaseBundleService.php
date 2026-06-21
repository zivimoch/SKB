<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CaseBundleService
{
    public function upsert(string $sourceSystem, string $sourceId, array $payload): array
    {
        $now = now();
        $caseData = $payload['case'];
        $existing = DB::table('hub_cases')
            ->where('source_system', $sourceSystem)
            ->where('source_id', $sourceId)
            ->lockForUpdate()
            ->first();
        $caseId = $existing->id ?? (string) Str::uuid();
        $created = $existing === null;
        $scope = $payload['sync_scope'] ?? 'full';

        DB::table('hub_cases')->updateOrInsert(
            ['source_system' => $sourceSystem, 'source_id' => $sourceId],
            [
                'id' => $caseId,
                'source_version' => $payload['source_version'] ?? null,
                'registration_number' => $caseData['registration_number'] ?? null,
                'client_number' => $caseData['client_number'] ?? null,
                'status' => $caseData['status'] ?? null,
                'reported_at' => $caseData['reported_at'] ?? null,
                'occurred_at' => $caseData['occurred_at'] ?? null,
                'occurred_at_estimated' => $caseData['occurred_at_estimated'] ?? false,
                'summary_encrypted' => $this->encrypt($caseData['summary'] ?? null),
                'location_encrypted' => $this->encrypt($caseData['location'] ?? null),
                'classifications' => json_encode($caseData['classifications'] ?? [], JSON_THROW_ON_ERROR),
                'active_intervention_cycle' => $caseData['active_intervention_cycle'] ?? 1,
                'payload_hash' => hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR)),
                'source_updated_at' => $this->dateTime($payload['source_updated_at'] ?? null),
                'last_synced_at' => $now,
                'profile_synced_at' => $scope === 'case_profile'
                    ? $now
                    : ($existing->profile_synced_at ?? null),
                'created_at' => $existing->created_at ?? $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ]
        );

        $this->replacePeople($caseId, $payload['people'] ?? []);
        $this->replaceEventHistories($caseId, $payload['event_histories'] ?? []);
        $this->replaceAssessments($caseId, $payload['assessments'] ?? []);
        if ($scope === 'full') {
            $this->syncOfficers($sourceSystem, $payload['officers'] ?? []);
            $this->replaceInterventions($caseId, $payload['interventions'] ?? []);
            $this->replaceTerminations($caseId, $payload['terminations'] ?? []);
        }

        return [
            'id' => $caseId,
            'created' => $created,
            'last_synced_at' => $now->toIso8601String(),
            'profile_synced_at' => $scope === 'case_profile'
                ? $now->toIso8601String()
                : optional($existing)->profile_synced_at,
        ];
    }

    public function find(string $sourceSystem, string $sourceId): ?array
    {
        $case = DB::table('hub_cases')
            ->where('source_system', $sourceSystem)
            ->where('source_id', $sourceId)
            ->whereNull('deleted_at')
            ->first();

        if (! $case) {
            return null;
        }

        return [
            'id' => $case->id,
            'source_system' => $case->source_system,
            'source_id' => $case->source_id,
            'source_version' => $case->source_version,
            'source_updated_at' => $case->source_updated_at,
            'last_synced_at' => $case->last_synced_at,
            'profile_synced_at' => $case->profile_synced_at,
            'case' => [
                'registration_number' => $case->registration_number,
                'client_number' => $case->client_number,
                'status' => $case->status,
                'reported_at' => $case->reported_at,
                'occurred_at' => $case->occurred_at,
                'occurred_at_estimated' => (bool) $case->occurred_at_estimated,
                'summary' => $this->decrypt($case->summary_encrypted),
                'location' => $this->decrypt($case->location_encrypted),
                'classifications' => json_decode($case->classifications ?: '[]', true),
                'active_intervention_cycle' => $case->active_intervention_cycle,
            ],
            'people' => DB::table('case_people')->where('case_id', $case->id)->get()->map(fn ($row) => [
                'source_id' => $row->source_id,
                'role' => $row->role,
                'identity' => $this->decrypt($row->identity_encrypted),
            ])->all(),
            'event_histories' => DB::table('case_event_histories')->where('case_id', $case->id)->get()->map(fn ($row) => [
                'source_id' => $row->source_id,
                'event_date' => $row->event_date,
                'event_time' => $row->event_time,
                'description' => $this->decrypt($row->description_encrypted),
            ])->all(),
            'assessments' => DB::table('assessments')->where('case_id', $case->id)->get()->map(fn ($row) => [
                'source_id' => $row->source_id,
                'assessed_at' => $row->assessed_at,
                'content' => $this->decrypt($row->content_encrypted),
            ])->all(),
            'interventions' => $this->readInterventions($case->id),
            'terminations' => DB::table('terminations')->where('case_id', $case->id)->get()->map(fn ($row) => [
                'source_id' => $row->source_id,
                'type' => $row->type,
                'status' => $row->status,
                'reason' => $this->decrypt($row->reason_encrypted),
            ])->all(),
        ];
    }

    private function replacePeople(string $caseId, array $people): void
    {
        DB::table('case_people')->where('case_id', $caseId)->delete();
        foreach ($people as $person) {
            DB::table('case_people')->insert([
                'id' => (string) Str::uuid(),
                'case_id' => $caseId,
                'source_id' => $person['source_id'] ?? null,
                'role' => $person['role'],
                'identity_encrypted' => $this->encrypt($person['identity']),
                'identity_hash' => hash_hmac('sha256', json_encode($person['identity'], JSON_THROW_ON_ERROR), config('app.key')),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function syncOfficers(string $sourceSystem, array $officers): void
    {
        foreach ($officers as $officer) {
            $existingId = DB::table('integration_officers')
                ->where('source_system', $sourceSystem)
                ->where('source_id', $officer['source_id'])
                ->value('id');

            DB::table('integration_officers')->updateOrInsert(
                ['source_system' => $sourceSystem, 'source_id' => $officer['source_id']],
                [
                    'id' => $existingId ?: (string) Str::uuid(),
                    'name' => $officer['name'],
                    'role' => $officer['role'] ?? null,
                    'institution' => $officer['institution'] ?? null,
                    'active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    private function replaceEventHistories(string $caseId, array $items): void
    {
        DB::table('case_event_histories')->where('case_id', $caseId)->delete();
        foreach ($items as $item) {
            DB::table('case_event_histories')->insert([
                'id' => (string) Str::uuid(),
                'case_id' => $caseId,
                'source_id' => $item['source_id'] ?? null,
                'event_date' => $item['event_date'] ?? null,
                'event_time' => $item['event_time'] ?? null,
                'description_encrypted' => $this->encrypt($item['description']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function replaceAssessments(string $caseId, array $items): void
    {
        DB::table('assessments')->where('case_id', $caseId)->delete();
        foreach ($items as $item) {
            DB::table('assessments')->insert([
                'id' => (string) Str::uuid(),
                'case_id' => $caseId,
                'source_id' => $item['source_id'] ?? null,
                'content_encrypted' => $this->encrypt($item['content']),
                'assessed_at' => $this->dateTime($item['assessed_at'] ?? null),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function replaceInterventions(string $caseId, array $cycles): void
    {
        DB::table('intervention_cycles')->where('case_id', $caseId)->delete();
        foreach ($cycles as $cycle) {
            $cycleId = (string) Str::uuid();
            DB::table('intervention_cycles')->insert([
                'id' => $cycleId,
                'case_id' => $caseId,
                'cycle_number' => $cycle['cycle_number'],
                'status' => $cycle['status'] ?? 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($cycle['activities'] ?? [] as $activity) {
                $activityId = (string) Str::uuid();
                DB::table('intervention_activities')->insert([
                    'id' => $activityId,
                    'cycle_id' => $cycleId,
                    'source_id' => $activity['source_id'],
                    'title_encrypted' => $this->encrypt($activity['title']),
                    'scheduled_date' => $activity['scheduled_date'] ?? null,
                    'scheduled_time' => $activity['scheduled_time'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                foreach ($activity['reports'] ?? [] as $report) {
                    DB::table('intervention_reports')->insert([
                        'id' => (string) Str::uuid(),
                        'activity_id' => $activityId,
                        'source_id' => $report['source_id'],
                        'officer_source_id' => $report['officer_source_id'] ?? null,
                        'status' => $report['status'],
                        'content_encrypted' => $this->encrypt($report['content'] ?? []),
                        'confirmed_at' => $this->dateTime($report['confirmed_at'] ?? null),
                        'completed_at' => $this->dateTime($report['completed_at'] ?? null),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }

            if (! empty($cycle['monitoring_evaluation'])) {
                $monev = $cycle['monitoring_evaluation'];
                DB::table('monitoring_evaluations')->insert([
                    'id' => (string) Str::uuid(),
                    'cycle_id' => $cycleId,
                    'source_id' => $monev['source_id'] ?? null,
                    'content_encrypted' => $this->encrypt($monev['content']),
                    'decision' => $monev['decision'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    private function replaceTerminations(string $caseId, array $items): void
    {
        DB::table('terminations')->where('case_id', $caseId)->delete();
        foreach ($items as $item) {
            DB::table('terminations')->insert([
                'id' => (string) Str::uuid(),
                'case_id' => $caseId,
                'source_id' => $item['source_id'] ?? null,
                'type' => $item['type'],
                'status' => $item['status'] ?? null,
                'reason_encrypted' => $this->encrypt($item['reason'] ?? null),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function readInterventions(string $caseId): array
    {
        return DB::table('intervention_cycles')->where('case_id', $caseId)->orderBy('cycle_number')->get()
            ->map(function ($cycle): array {
                $activities = DB::table('intervention_activities')->where('cycle_id', $cycle->id)->get()
                    ->map(function ($activity): array {
                        return [
                            'source_id' => $activity->source_id,
                            'title' => $this->decrypt($activity->title_encrypted),
                            'scheduled_date' => $activity->scheduled_date,
                            'scheduled_time' => $activity->scheduled_time,
                            'reports' => DB::table('intervention_reports')->where('activity_id', $activity->id)->get()
                                ->map(fn ($report) => [
                                    'source_id' => $report->source_id,
                                    'officer_source_id' => $report->officer_source_id,
                                    'status' => $report->status,
                                    'confirmed_at' => $report->confirmed_at,
                                    'completed_at' => $report->completed_at,
                                    'content' => $this->decrypt($report->content_encrypted),
                                ])->all(),
                        ];
                    })->all();
                $monev = DB::table('monitoring_evaluations')->where('cycle_id', $cycle->id)->first();

                return [
                    'cycle_number' => $cycle->cycle_number,
                    'status' => $cycle->status,
                    'activities' => $activities,
                    'monitoring_evaluation' => $monev ? [
                        'source_id' => $monev->source_id,
                        'decision' => $monev->decision,
                        'content' => $this->decrypt($monev->content_encrypted),
                    ] : null,
                ];
            })->all();
    }

    private function encrypt(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return Crypt::encryptString(json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
    }

    private function decrypt(?string $value): mixed
    {
        if ($value === null) {
            return null;
        }

        return json_decode(Crypt::decryptString($value), true, flags: JSON_THROW_ON_ERROR);
    }

    private function dateTime(mixed $value): ?string
    {
        return $value ? Carbon::parse($value)->format('Y-m-d H:i:s') : null;
    }
}
