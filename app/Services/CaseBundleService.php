<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

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
            $this->syncOfficers($caseId, $sourceSystem, $payload['officers'] ?? []);
            $this->replaceInterventions($caseId, $payload['interventions'] ?? [], $sourceSystem);
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

    public function interventionFeed(string $sourceSystem, string $sourceId): ?array
    {
        $case = DB::table('hub_cases')
            ->where('source_system', $sourceSystem)
            ->where('source_id', $sourceId)
            ->whereNull('deleted_at')
            ->first();

        if (! $case) {
            return null;
        }

        $cycles = collect($this->readInterventions($case->id))
            ->map(function (array $cycle) use ($sourceSystem): array {
                $cycle['activities'] = collect($cycle['activities'])
                    ->reject(fn (array $activity): bool => $activity['origin_system'] === $sourceSystem)
                    ->values()
                    ->all();
                unset($cycle['monitoring_evaluation']);

                return $cycle;
            })
            ->filter(fn (array $cycle): bool => $cycle['activities'] !== [])
            ->values()
            ->all();

        return [
            'case_source_id' => $case->source_id,
            'excluded_origin_system' => $sourceSystem,
            'interventions' => $cycles,
        ];
    }

    public function createActivity(string $caseId, User $creator, array $data): string
    {
        return DB::transaction(function () use ($caseId, $creator, $data): string {
            $case = DB::table('hub_cases')->where('id', $caseId)->whereNull('deleted_at')->first();
            if (! $case) {
                throw new RuntimeException('Kasus SKB tidak ditemukan.');
            }

            $cycle = DB::table('intervention_cycles')
                ->where('case_id', $caseId)
                ->where('cycle_number', $data['intervention_cycle'])
                ->first();
            $cycleId = $cycle->id ?? (string) Str::uuid();
            DB::table('intervention_cycles')->updateOrInsert(
                ['case_id' => $caseId, 'cycle_number' => $data['intervention_cycle']],
                [
                    'id' => $cycleId,
                    'status' => 'active',
                    'created_at' => $cycle->created_at ?? now(),
                    'updated_at' => now(),
                ]
            );

            $officers = DB::table('integration_officers as officer')
                ->join('case_integration_officers as assignment', 'assignment.officer_id', '=', 'officer.id')
                ->where('assignment.case_id', $caseId)
                ->where('officer.source_system', $case->source_system)
                ->whereIn('officer.source_id', $data['officer_external_ids'])
                ->where('officer.active', true)
                ->select('officer.*')
                ->get();
            if ($officers->count() !== count(array_unique($data['officer_external_ids']))) {
                throw new RuntimeException('Satu atau lebih petugas tidak tersedia pada kasus ini.');
            }

            $activityId = (string) Str::uuid();
            DB::table('intervention_activities')->insert([
                'id' => $activityId,
                'cycle_id' => $cycleId,
                'source_id' => $activityId,
                'origin_system' => 'skb',
                'created_by' => $creator->id,
                'title_encrypted' => $this->encrypt($data['title']),
                'scheduled_date' => $data['scheduled_date'],
                'scheduled_time' => $data['scheduled_time'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($officers as $officer) {
                $reportId = (string) Str::uuid();
                DB::table('intervention_reports')->insert([
                    'id' => $reportId,
                    'activity_id' => $activityId,
                    'source_id' => $reportId,
                    'officer_source_id' => $officer->source_id,
                    'status' => 'pending',
                    'content_encrypted' => $this->encrypt([
                        'officer' => [
                            'name' => $officer->name,
                            'position' => $officer->role,
                            'institution' => $officer->institution,
                        ],
                        'requester' => [
                            'source_id' => $creator->external_id,
                            'name' => $creator->name,
                            'position' => $creator->role,
                        ],
                        'services' => [],
                    ]),
                    'confirmed_at' => null,
                    'completed_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('hub_cases')->where('id', $caseId)->update([
                'active_intervention_cycle' => max(
                    (int) $case->active_intervention_cycle,
                    (int) $data['intervention_cycle']
                ),
                'last_synced_at' => now(),
                'updated_at' => now(),
            ]);

            return $activityId;
        });
    }

    public function updateReport(string $caseId, string $reportSourceId, User $actor, array $data): void
    {
        DB::transaction(function () use ($caseId, $reportSourceId, $actor, $data): void {
            $report = DB::table('intervention_reports as report')
                ->join('intervention_activities as activity', 'activity.id', '=', 'report.activity_id')
                ->join('intervention_cycles as cycle', 'cycle.id', '=', 'activity.cycle_id')
                ->where('cycle.case_id', $caseId)
                ->where('report.source_id', $reportSourceId)
                ->select('report.*', 'activity.scheduled_date', 'activity.scheduled_time')
                ->first();
            if (! $report) {
                throw new RuntimeException('Todo SKB tidak ditemukan.');
            }
            if ((string) $report->officer_source_id !== (string) $actor->external_id) {
                throw new RuntimeException('Todo hanya dapat diproses oleh petugas pemiliknya.');
            }

            $content = $this->decrypt($report->content_encrypted) ?: [];
            $updates = ['updated_at' => now()];
            if ($data['action'] === 'accept') {
                $updates += ['status' => 'accepted', 'confirmed_at' => now()];
                unset($content['rejection_reason']);
            } elseif ($data['action'] === 'reject') {
                $updates += ['status' => 'rejected', 'confirmed_at' => now(), 'completed_at' => null];
                $content['rejection_reason'] = $data['rejection_reason'];
            } else {
                $completedAt = Carbon::parse($report->scheduled_date.' '.$data['completed_time']);
                $updates += [
                    'status' => 'done',
                    'confirmed_at' => $report->confirmed_at ?: now(),
                    'completed_at' => $completedAt,
                ];
                unset($content['rejection_reason']);
                $content['location'] = $data['location'];
                $content['process_and_result'] = $data['process_result'];
                $content['follow_up_plan'] = $data['follow_up_plan'];
            }
            $updates['content_encrypted'] = $this->encrypt($content);

            DB::table('intervention_reports')->where('id', $report->id)->update($updates);
            DB::table('hub_cases')->where('id', $caseId)->update([
                'last_synced_at' => now(),
                'updated_at' => now(),
            ]);
        });
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

    private function syncOfficers(string $caseId, string $sourceSystem, array $officers): void
    {
        $officerIds = [];
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
                    'email' => $officer['email'] ?? null,
                    'role' => $officer['role'] ?? null,
                    'institution' => $officer['institution'] ?? null,
                    'active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
            $officerIds[] = $existingId ?: DB::table('integration_officers')
                ->where('source_system', $sourceSystem)
                ->where('source_id', $officer['source_id'])
                ->value('id');

            if (! empty($officer['email'])) {
                $localUser = DB::table('users')
                    ->whereRaw('LOWER(TRIM(email)) = ?', [strtolower(trim($officer['email']))])
                    ->first();
                $externalIdUsed = DB::table('users')
                    ->where('external_id', $officer['source_id'])
                    ->when($localUser, fn ($query) => $query->where('id', '!=', $localUser->id))
                    ->exists();
                if ($localUser && ! $externalIdUsed) {
                    DB::table('users')->where('id', $localUser->id)->update([
                        'external_id' => $officer['source_id'],
                        'external_system' => $sourceSystem,
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        DB::table('case_integration_officers')->where('case_id', $caseId)->delete();
        foreach (array_filter(array_unique($officerIds)) as $officerId) {
            DB::table('case_integration_officers')->insert([
                'case_id' => $caseId,
                'officer_id' => $officerId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
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

    private function replaceInterventions(string $caseId, array $cycles, string $sourceSystem): void
    {
        foreach ($cycles as $cycle) {
            $existingCycle = DB::table('intervention_cycles')
                ->where('case_id', $caseId)
                ->where('cycle_number', $cycle['cycle_number'])
                ->first();
            $cycleId = $existingCycle->id ?? (string) Str::uuid();
            DB::table('intervention_cycles')->updateOrInsert(
                ['case_id' => $caseId, 'cycle_number' => $cycle['cycle_number']],
                [
                    'id' => $cycleId,
                    'status' => $cycle['status'] ?? 'active',
                    'created_at' => $existingCycle->created_at ?? now(),
                    'updated_at' => now(),
                ]
            );

            $incomingSourceIds = collect($cycle['activities'] ?? [])->pluck('source_id')->all();
            $staleActivities = DB::table('intervention_activities')
                ->where('cycle_id', $cycleId)
                ->where('origin_system', $sourceSystem);
            if ($incomingSourceIds !== []) {
                $staleActivities->whereNotIn('source_id', $incomingSourceIds);
            }
            $staleActivities->delete();

            foreach ($cycle['activities'] ?? [] as $activity) {
                $existingActivity = DB::table('intervention_activities')
                    ->where('cycle_id', $cycleId)
                    ->where('source_id', $activity['source_id'])
                    ->first();
                $activityId = $existingActivity->id ?? (string) Str::uuid();
                DB::table('intervention_activities')->updateOrInsert(
                    ['cycle_id' => $cycleId, 'source_id' => $activity['source_id']],
                    [
                        'id' => $activityId,
                        'origin_system' => $sourceSystem,
                        'title_encrypted' => $this->encrypt($activity['title']),
                        'scheduled_date' => $activity['scheduled_date'] ?? null,
                        'scheduled_time' => $activity['scheduled_time'] ?? null,
                        'created_at' => $existingActivity->created_at ?? now(),
                        'updated_at' => now(),
                    ]
                );

                DB::table('intervention_reports')->where('activity_id', $activityId)->delete();
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

            DB::table('monitoring_evaluations')->where('cycle_id', $cycleId)->delete();
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
                        $creator = $activity->created_by
                            ? DB::table('users')->where('id', $activity->created_by)->first()
                            : null;

                        return [
                            'source_id' => $activity->source_id,
                            'origin_system' => $activity->origin_system,
                            'title' => $this->decrypt($activity->title_encrypted),
                            'scheduled_date' => $activity->scheduled_date,
                            'scheduled_time' => $activity->scheduled_time,
                            'requester' => $creator ? [
                                'source_id' => $creator->external_id,
                                'name' => $creator->name,
                                'position' => $creator->role,
                            ] : null,
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
