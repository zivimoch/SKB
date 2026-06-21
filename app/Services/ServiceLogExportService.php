<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class ServiceLogExportService
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
            ->leftJoin('users as requester', 'requester.id', '=', 'activity.created_by')
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
                'report.content_encrypted',
                'report.confirmed_at',
                'report.completed_at',
                'report.updated_at',
                'requester.name as requester_name',
                'requester.role as requester_role',
            ])
            ->get()
            ->map(function ($row) use ($victims): array {
                $content = $this->decrypt($row->content_encrypted) ?: [];
                $officer = $content['officer'] ?? [];
                $services = collect($content['services'] ?? [])
                    ->pluck('keyword')
                    ->filter()
                    ->values();
                $victim = $victims->get($row->case_id);
                $identity = $victim ? ($this->decrypt($victim->identity_encrypted) ?: []) : [];

                return [
                    'no_klien' => $row->client_number,
                    'inisial_korban' => $this->initials($identity['name'] ?? null),
                    'agenda' => $this->decrypt($row->title_encrypted),
                    'layanan' => $services->all(),
                    'jumlah_layanan' => $services->count(),
                    'tanggal_pelaksanaan' => $this->date($row->scheduled_date),
                    'tanggal_laporan' => $this->dateTime($row->completed_at ?: $row->updated_at),
                    'petugas' => $this->person($officer),
                    'tanggal_diterima' => $this->dateTime($row->confirmed_at),
                    'pemohon' => $this->person([
                        'name' => $row->requester_name,
                        'position' => $row->requester_role,
                    ]),
                    'keterangan' => $this->description($row->status, $content),
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

    private function date(mixed $value): ?string
    {
        return $value ? Carbon::parse($value)->timezone(config('app.timezone'))->toDateString() : null;
    }

    private function dateTime(mixed $value): ?string
    {
        return $value ? Carbon::parse($value)->timezone(config('app.timezone'))->toIso8601String() : null;
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
