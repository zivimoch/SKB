@extends('layouts.app')
@section('title', ($case['case']['client_number'] ?? 'Kasus').' - SKB')
@push('styles')
<style>
    .hero{padding:25px;background:linear-gradient(135deg,#fff,#eef8f8);display:flex;justify-content:space-between;gap:20px;align-items:start}.hero h1{margin:5px 0}.summary{grid-template-columns:repeat(2,1fr);margin:18px 0}.summary button{padding:20px;text-align:left;cursor:pointer}.summary h2{margin:7px 0 3px;font-size:18px}
    .workspace{overflow:hidden}.work-head{padding:20px;border-bottom:1px solid #e3eaed}.tabs{display:flex;gap:8px;flex-wrap:wrap;margin-top:14px}.tab{border:1px solid #cbdade;background:#fff;padding:8px 12px;border-radius:999px;cursor:pointer}.tab.active{background:var(--navy);color:#fff}
    .activities{padding:20px;display:grid;gap:12px}.activity{border:1px solid #dce5e9;border-radius:14px;padding:16px}.activity h3{margin:0 0 8px}.reports{display:grid;gap:8px;margin-top:12px}.report{background:#f5f8fa;border-radius:10px;padding:11px}.services{display:flex;gap:6px;flex-wrap:wrap;margin-top:9px}.service{display:inline-flex;padding:4px 8px;border-radius:8px;background:#dff2ef;color:#175d58;font-size:12px;font-weight:700}.fab{position:fixed;right:25px;bottom:25px;width:58px;height:58px;border-radius:50%;border:0;background:var(--blue);color:#fff;font-size:30px;box-shadow:0 12px 30px #176b8755;cursor:pointer}
    dialog{border:0;border-radius:18px;padding:0;max-width:680px;width:calc(100% - 24px);box-shadow:0 22px 70px #102a3b55}dialog::backdrop{background:#102a3b99}.modal-head{padding:18px 22px;background:var(--navy);color:#fff;display:flex;justify-content:space-between}.modal-body{padding:22px;max-height:75vh;overflow:auto}.close{border:0;background:transparent;color:#fff;font-size:22px;cursor:pointer}.details{display:grid;grid-template-columns:180px 1fr;gap:8px 18px}.details dt{font-weight:800}.details dd{margin:0;white-space:pre-wrap}.form-grid{grid-template-columns:1fr 1fr}
    @media(max-width:700px){.summary,.form-grid{grid-template-columns:1fr}.hero{display:block}.details{grid-template-columns:1fr}.details dd{margin-bottom:9px}}
</style>
@endpush
@section('content')
<section class="card hero">
    <div><span class="pill">{{ strtoupper($case['source_system']) }}</span><h1>{{ data_get($case, 'case.client_number') ?: 'Data Klien' }}</h1><div class="muted">{{ data_get($case, 'case.registration_number') ?: 'Belum ada nomor registrasi' }}</div></div>
    <div>
        <span class="pill">{{ data_get($case, 'case.status') ?: '-' }}</span>
        <p class="muted">
            Data kasus terakhir disinkronkan:
            {{ $case['profile_synced_at'] ? \Carbon\Carbon::parse($case['profile_synced_at'])->timezone(config('app.timezone'))->format('d-m-Y H:i:s') : 'Belum pernah disinkronkan manual' }}
        </p>
        <p class="muted">Aktivitas integrasi terakhir: {{ \Carbon\Carbon::parse($case['last_synced_at'])->timezone(config('app.timezone'))->format('d-m-Y H:i:s') }}</p>
    </div>
</section>
<section class="grid summary">
    <button class="card" type="button" onclick="showInfo('case')"><span class="pill">1. Identifikasi</span><h2>Data Kasus</h2><div class="muted">Kasus, pelapor, korban, terlapor, dan klasifikasi.</div></button>
    <button class="card" type="button" onclick="showInfo('assessment')"><span class="pill">2. Asesmen Awal</span><h2>Riwayat & Kondisi Awal</h2><div class="muted">Riwayat kejadian dan hasil asesmen.</div></button>
</section>
<section class="card workspace">
    <div class="work-head"><span class="pill">3–7. Intervensi, Monev & Terminasi</span><div class="tabs" id="tabs"></div></div>
    <div class="activities" id="activities"></div>
</section>
<button class="fab" onclick="document.getElementById('agendaDialog').showModal()" title="Tambah agenda">+</button>

<dialog id="infoDialog"><div class="modal-head"><strong id="infoTitle">Detail</strong><button class="close" onclick="infoDialog.close()">×</button></div><div class="modal-body" id="infoBody"></div></dialog>
<dialog id="agendaDialog"><div class="modal-head"><strong>Tambah Agenda / Todo</strong><button class="close" onclick="agendaDialog.close()">×</button></div>
    <form class="modal-body grid" method="post" action="{{ route('cases.agendas.store', $case['id']) }}">
        @csrf
        <div class="grid form-grid"><label>Tanggal<input type="date" name="scheduled_date" value="{{ now()->toDateString() }}" required></label><label>Waktu<input type="time" name="scheduled_time" value="09:00" required></label></div>
        <label>Judul kegiatan<textarea name="title" required></textarea></label>
        <label>Petugas<select name="officer_external_ids[]" multiple size="5" required>@foreach($officers as $officer)<option value="{{ $officer->source_id }}">{{ $officer->name }} — {{ $officer->role }}</option>@endforeach</select></label>
        <p class="muted">Agenda dibuat lebih dahulu di MokaV2, kemudian snapshot terbaru otomatis dikirim kembali ke SKB.</p>
        <button class="btn btn-primary">Simpan Agenda</button>
    </form>
</dialog>
@endsection
@push('scripts')
<script>
let caseData=@json($case);const actorExternal=@json(auth()->user()->external_id);const csrf=@json(csrf_token());const reportActionBase=@json(url('/cases/'.$case['id'].'/reports'));let active=caseData.case.active_intervention_cycle||1;
const esc=v=>String(v??'-').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
function rows(obj){return `<dl class="details">${Object.entries(obj).map(([k,v])=>`<dt>${esc(k)}</dt><dd>${esc(Array.isArray(v)?v.join(', '):v)}</dd>`).join('')}</dl>`}
function showInfo(type){let html='';if(type==='case'){const c=caseData.case;html=rows({'No. Registrasi':c.registration_number,'No. Klien':c.client_number,'Status':c.status,'Tanggal Pelaporan':c.reported_at,'Tanggal Kejadian':c.occurred_at,'Ringkasan':c.summary});html+='<h3>Pihak Terkait</h3>'+caseData.people.map(p=>rows({'Peran':p.role,'Nama':p.identity.name,'NIK':p.identity.nik,'Jenis Kelamin':p.identity.gender,'Telepon':p.identity.phone,'Pekerjaan':p.identity.occupation})).join('<hr>')}else{html='<h3>Riwayat Kejadian</h3>'+caseData.event_histories.map(e=>rows({'Tanggal':e.event_date,'Waktu':e.event_time,'Keterangan':e.description})).join('<hr>')+'<h3>Asesmen</h3>'+caseData.assessments.map(a=>rows(a.content)).join('<hr>')}infoTitle.textContent=type==='case'?'Identifikasi':'Asesmen Awal';infoBody.innerHTML=html||'<p>Belum ada data.</p>';infoDialog.showModal()}
function reportActions(r){if(r.officer_source_id!==actorExternal)return'';const action=`${reportActionBase}/${encodeURIComponent(r.source_id)}`;if(r.status==='pending')return `<div class="grid" style="grid-template-columns:auto 1fr;align-items:end;margin-top:10px"><form method="post" action="${action}"><input type="hidden" name="_token" value="${csrf}"><input type="hidden" name="action" value="accept"><button class="btn btn-primary">Terima</button></form><form method="post" action="${action}" style="display:flex;gap:8px"><input type="hidden" name="_token" value="${csrf}"><input type="hidden" name="action" value="reject"><input name="rejection_reason" placeholder="Alasan penolakan" required><button class="btn">Tolak</button></form></div>`;if(r.status==='accepted')return `<form class="grid" method="post" action="${action}" style="margin-top:12px"><input type="hidden" name="_token" value="${csrf}"><input type="hidden" name="action" value="complete"><div class="grid form-grid"><label>Lokasi<input name="location" required></label><label>Jam selesai<input type="time" name="completed_time" required></label></div><label>Proses & hasil<textarea name="process_result" required></textarea></label><label>Rencana tindak lanjut<textarea name="follow_up_plan" required></textarea></label><button class="btn btn-primary">Selesaikan Todo</button></form>`;return''}
function serviceTags(services){if(!services?.length)return'';return `<div class="services">${services.map(s=>`<span class="service">${esc(s.keyword||s)}</span>`).join('')}</div>`}
function render(){tabs.innerHTML='';caseData.interventions.forEach(c=>{const b=document.createElement('button');b.className='tab '+(c.cycle_number===active?'active':'');b.textContent='Intervensi ke-'+c.cycle_number;b.onclick=()=>{active=c.cycle_number;render()};tabs.appendChild(b)});const cycle=caseData.interventions.find(c=>c.cycle_number===active);if(!cycle){activities.innerHTML='<p>Belum ada intervensi.</p>';return}activities.innerHTML=(cycle.activities||[]).map(a=>`<article class="activity"><span class="pill">${esc(a.scheduled_date)} ${esc(a.scheduled_time)}</span><h3>${esc(a.title)}</h3><div class="reports">${(a.reports||[]).map(r=>`<div class="report"><b>${esc(r.content?.officer?.name||'Petugas')}</b> <span class="pill">${esc(r.status)}</span><div class="muted">${esc(r.content?.officer?.position||'')} ${r.content?.officer?.institution?'— '+esc(r.content.officer.institution):''}</div>${serviceTags(r.content?.services)}${r.content?.rejection_reason?`<p>${esc(r.content.rejection_reason)}</p>`:''}${r.content?.process_and_result?`<p>${esc(r.content.process_and_result)}</p>`:''}${reportActions(r)}</div>`).join('')||'<div class="muted">Belum ada laporan petugas.</div>'}</div></article>`).join('')||'<p>Belum ada agenda pada siklus ini.</p>';if(cycle.monitoring_evaluation)activities.innerHTML+=`<article class="activity"><h3>Pemantauan & Evaluasi</h3>${rows(cycle.monitoring_evaluation.content)}</article>`}
render();
</script>
@endpush
