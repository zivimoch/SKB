<?php

namespace App\Http\Controllers;

use App\Services\CaseBundleService;
use App\Services\MokaApiClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CaseWorkspaceController extends Controller
{
    public function __construct(private readonly CaseBundleService $cases, private readonly MokaApiClient $moka) {}

    public function index(): View
    {
        $cases = DB::table('hub_cases')->whereNull('deleted_at')->orderByDesc('last_synced_at')->get();

        return view('cases.index', compact('cases'));
    }

    public function show(string $id): View
    {
        $row = DB::table('hub_cases')->where('id', $id)->whereNull('deleted_at')->first();
        abort_unless($row, 404);
        $case = $this->cases->find($row->source_system, $row->source_id);
        $officers = DB::table('integration_officers')->where('source_system', $row->source_system)
            ->where('active', true)->orderBy('name')->get();

        return view('cases.show', compact('case', 'officers'));
    }

    public function data(string $id): JsonResponse
    {
        $row = DB::table('hub_cases')->where('id', $id)->whereNull('deleted_at')->first();
        abort_unless($row, 404);

        return response()->json(['data' => $this->cases->find($row->source_system, $row->source_id)]);
    }

    public function storeAgenda(Request $request, string $id): RedirectResponse
    {
        $row = DB::table('hub_cases')->where('id', $id)->whereNull('deleted_at')->first();
        abort_unless($row, 404);
        abort_unless($row->source_system === 'mokav2', 422, 'Kasus ini tidak berasal dari MokaV2.');

        $data = $request->validate([
            'title' => ['required', 'string', 'max:2000'],
            'scheduled_date' => ['required', 'date'],
            'scheduled_time' => ['required', 'date_format:H:i'],
            'officer_external_ids' => ['required', 'array', 'min:1'],
            'officer_external_ids.*' => ['required', 'uuid'],
        ]);

        $result = $this->moka->createAgenda($row->source_id, [
            'command_id' => (string) Str::uuid(),
            'title' => $data['title'],
            'scheduled_date' => $data['scheduled_date'],
            'scheduled_time' => $data['scheduled_time'].':00',
            'intervention_cycle' => $row->active_intervention_cycle,
            'officer_external_ids' => array_values(array_unique($data['officer_external_ids'])),
            'created_by_external_id' => $request->user()->external_id,
            'created_by_email' => $request->user()->email,
        ]);
        $this->rememberMokaIdentity($request, data_get($result, 'data.creator_external_id'));
        $this->applyMokaBundle($row->source_id, data_get($result, 'data.case_bundle'));

        return redirect()->route('cases.show', $id)->with('status', 'Agenda berhasil dibuat di Moka dan disinkronkan kembali ke SKB.');
    }

    public function updateReport(Request $request, string $id, string $reportUuid): RedirectResponse
    {
        $row = DB::table('hub_cases')->where('id', $id)->whereNull('deleted_at')->first();
        abort_unless($row, 404);
        $data = $request->validate([
            'action' => ['required', 'in:accept,reject,complete'],
            'rejection_reason' => ['required_if:action,reject', 'nullable', 'string'],
            'location' => ['required_if:action,complete', 'nullable', 'string'],
            'completed_time' => ['required_if:action,complete', 'nullable', 'date_format:H:i'],
            'process_result' => ['required_if:action,complete', 'nullable', 'string'],
            'follow_up_plan' => ['required_if:action,complete', 'nullable', 'string'],
        ]);

        $result = $this->moka->updateReport($reportUuid, [
            'command_id' => (string) Str::uuid(),
            'actor_external_id' => $request->user()->external_id,
            'actor_email' => $request->user()->email,
            'action' => $data['action'],
            'rejection_reason' => $data['rejection_reason'] ?? null,
            'location' => $data['location'] ?? null,
            'completed_time' => isset($data['completed_time']) ? $data['completed_time'].':00' : null,
            'process_result' => $data['process_result'] ?? null,
            'follow_up_plan' => $data['follow_up_plan'] ?? null,
        ]);
        $this->rememberMokaIdentity($request, data_get($result, 'data.actor_external_id'));
        $this->applyMokaBundle($row->source_id, data_get($result, 'data.case_bundle'));

        return redirect()->route('cases.show', $id)->with('status', 'Todo berhasil diperbarui di Moka dan disinkronkan kembali.');
    }

    private function applyMokaBundle(string $sourceId, mixed $bundle): void
    {
        if (! is_array($bundle)) {
            throw new \RuntimeException('Moka tidak mengembalikan snapshot kasus terbaru.');
        }

        $this->cases->upsert('mokav2', $sourceId, $bundle);
    }

    private function rememberMokaIdentity(Request $request, mixed $externalId): void
    {
        if (! is_string($externalId) || ! Str::isUuid($externalId)) {
            return;
        }

        $user = $request->user();
        if ($user->external_id === $externalId) {
            return;
        }

        $alreadyUsed = DB::table('users')
            ->where('external_id', $externalId)
            ->where('id', '!=', $user->id)
            ->exists();

        if (! $alreadyUsed) {
            $user->forceFill([
                'external_id' => $externalId,
                'external_system' => 'mokav2',
            ])->save();
        }
    }
}
