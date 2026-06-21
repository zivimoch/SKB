<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class ServiceLogSheetBuilder
{
    public function rows(): array
    {
        $victims = DB::table('case_people')
            ->where('role', 'victim')
            ->get()
            ->keyBy('case_id');

        return DB::table('intervention_reports as report')
            ->join('intervention_activities as activity', 'activity.id', '=', 'report.activity_id')
            ->join('intervention_cycles as cycle', 'cycle.id', '=', 'activity.cycle_id')
            ->join('hub_cases as case', 'case.id', '=', 'cycle.case_id')
            ->whereNull('case.deleted_at')
            ->orderBy('activity.scheduled_date')
            ->orderBy('activity.scheduled_time')
            ->orderBy('report.id')
            ->select([
                'case.id as case_id',
                'case.client_number',
                'activity.title_encrypted',
                'activity.scheduled_date',
                'report.status',
                'report.officer_source_id',
                'report.content_encrypted',
                'report.confirmed_at',
                'report.completed_at',
                'report.updated_at',
            ])
            ->get()
            ->map(function ($row) use ($victims): array {
                $content = $this->decrypt($row->content_encrypted) ?: [];
                $officer = $content['officer'] ?? [];
                $requester = $content['requester'] ?? [];
                $services = collect($content['services'] ?? [])
                    ->pluck('keyword')
                    ->filter()
                    ->values();
                $victim = $victims->get($row->case_id);
                $identity = $victim ? ($this->decrypt($victim->identity_encrypted) ?: []) : [];
                $requesterIsOfficer = ! empty($requester['source_id'])
                    && (string) $requester['source_id'] === (string) $row->officer_source_id;

                return [
                    $row->client_number,
                    $this->initials($identity['name'] ?? null),
                    $this->decrypt($row->title_encrypted),
                    $services->implode(', '),
                    $services->count(),
                    $this->date($row->scheduled_date),
                    $this->dateTime($content['reported_at'] ?? $row->completed_at ?? $row->updated_at),
                    $this->person($officer),
                    $this->dateTime($row->confirmed_at),
                    $requesterIsOfficer ? '' : $this->person($requester),
                    $this->description($row->status, $content),
                ];
            })
            ->all();
    }

    private function decrypt(?string $value): mixed
    {
        if ($value === null) {
            return null;
        }

        return json_decode(Crypt::decryptString($value), true, flags: JSON_THROW_ON_ERROR);
    }

    private function initials(?string $name): string
    {
        if (! $name) {
            return '';
        }

        return collect(preg_split('/\s+/u', trim($name)))
            ->filter()
            ->map(fn (string $word): string => mb_strtoupper(mb_substr($word, 0, 1)))
            ->implode('');
    }

    private function person(array $person): string
    {
        $name = trim((string) ($person['name'] ?? ''));
        $details = array_values(array_filter([
            trim((string) ($person['position'] ?? '')),
            trim((string) ($person['institution'] ?? '')),
        ]));

        return $name.($details ? ' ('.implode(' - ', $details).')' : '');
    }

    private function date(mixed $value): string
    {
        return $value ? Carbon::parse($value)->timezone(config('app.timezone'))->format('d/m/Y') : '';
    }

    private function dateTime(mixed $value): string
    {
        return $value ? Carbon::parse($value)->timezone(config('app.timezone'))->format('d/m/Y H:i') : '';
    }

    private function description(string $status, array $content): string
    {
        return match ($status) {
            'pending' => 'Menunggu konfirmasi',
            'accepted' => 'Diterima',
            'rejected' => 'Ditolak'.(! empty($content['rejection_reason']) ? ': '.$content['rejection_reason'] : ''),
            'done' => 'Laporan selesai',
            default => $status,
        };
    }
}
