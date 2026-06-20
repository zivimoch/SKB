@extends('layouts.app')
@section('title', 'Daftar Kasus - SKB')
@push('styles')
<style>
    .head{display:flex;justify-content:space-between;align-items:end;margin-bottom:20px}.case-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:16px}
    .case{padding:20px;transition:.18s}.case:hover{transform:translateY(-3px);box-shadow:0 16px 36px #17324d18}.case h2{margin:9px 0 4px;font-size:18px}.meta{display:flex;justify-content:space-between;gap:10px;margin-top:17px;font-size:13px;color:var(--muted)}
</style>
@endpush
@section('content')
<div class="head"><div><h1 style="margin:0">Daftar Kasus</h1><p class="muted">Kasus yang terhubung dari MokaV2 dan aplikasi sumber lainnya.</p></div><span class="pill">{{ $cases->count() }} kasus</span></div>
<div class="case-grid">
@forelse($cases as $case)
    <a class="card case" href="{{ route('cases.show', $case->id) }}">
        <span class="pill">{{ strtoupper($case->source_system) }}</span>
        <h2>{{ $case->client_number ?: 'Klien tanpa nomor' }}</h2>
        <div class="muted">{{ $case->registration_number ?: 'Belum ada nomor registrasi' }}</div>
        <div class="meta"><span>Status: {{ $case->status ?: '-' }}</span><span>Intervensi {{ $case->active_intervention_cycle }}</span></div>
    </a>
@empty
    <div class="card" style="padding:28px">Belum ada kasus yang tersinkronisasi.</div>
@endforelse
</div>
@endsection
