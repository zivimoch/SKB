<?php

namespace App\Http\Controllers;

use App\Services\CaseBundleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CaseWorkspaceController extends Controller
{
    public function __construct(private readonly CaseBundleService $cases) {}

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
        $officers = DB::table('integration_officers as officer')
            ->join('case_integration_officers as assignment', 'assignment.officer_id', '=', 'officer.id')
            ->where('assignment.case_id', $row->id)
            ->where('officer.active', true)
            ->orderBy('officer.name')
            ->select('officer.*')
            ->get();

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
        $data = $request->validate([
            'title' => ['required', 'string', 'max:2000'],
            'scheduled_date' => ['required', 'date'],
            'scheduled_time' => ['required', 'date_format:H:i'],
            'officer_external_ids' => ['required', 'array', 'min:1'],
            'officer_external_ids.*' => ['required', 'uuid'],
        ]);

        $this->cases->createActivity($row->id, $request->user(), [
            'title' => $data['title'],
            'scheduled_date' => $data['scheduled_date'],
            'scheduled_time' => $data['scheduled_time'].':00',
            'intervention_cycle' => $row->active_intervention_cycle,
            'officer_external_ids' => array_values(array_unique($data['officer_external_ids'])),
        ]);

        return redirect()->route('cases.show', $id)->with('status', 'Intervensi berhasil dibuat di SKB. Aplikasi sumber dapat mengambil data ini melalui API.');
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

        $this->cases->updateReport($row->id, $reportUuid, $request->user(), [
            'action' => $data['action'],
            'rejection_reason' => $data['rejection_reason'] ?? null,
            'location' => $data['location'] ?? null,
            'completed_time' => isset($data['completed_time']) ? $data['completed_time'].':00' : null,
            'process_result' => $data['process_result'] ?? null,
            'follow_up_plan' => $data['follow_up_plan'] ?? null,
        ]);

        return redirect()->route('cases.show', $id)->with('status', 'Todo berhasil diperbarui di SKB.');
    }
}
