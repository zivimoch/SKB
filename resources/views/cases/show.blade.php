@extends('layouts.app')
@section('title', ($case['case']['client_number'] ?? 'Kasus').' - SKB')
@push('styles')
<style>
    :root{--skb-bg:#f6f8fb;--skb-card:#fff;--skb-line:#d8e0e8;--skb-text:#17212b;--skb-muted:#607080;--skb-primary:#0f766e;--skb-soft:#e7f5f3;--skb-done:#166534;--skb-danger:#b91c1c;--skb-shadow:0 14px 32px rgba(15,23,42,.08)}
    .case-shell{width:min(720px,100%);margin:0 auto;padding-bottom:92px}.case-header{margin-bottom:14px}.case-header h1{margin:0 0 6px;font-size:clamp(24px,5vw,32px)}.case-header p{margin:0;color:var(--skb-muted)}
    .summary{grid-template-columns:repeat(2,1fr);gap:12px;margin-bottom:12px}.summary .card{min-height:112px;border-radius:8px;box-shadow:var(--skb-shadow);padding:14px;text-align:left;cursor:pointer}.summary-small{display:block;margin-bottom:6px;color:var(--skb-primary);font-size:12px;font-weight:900}.summary h2{margin:0 0 8px;font-size:18px}.summary p{margin:0;color:var(--skb-muted)}
    .workspace{overflow:hidden;border-radius:8px;box-shadow:var(--skb-shadow)}.work-head{display:grid;gap:12px;padding:14px;border-bottom:1px solid var(--skb-line)}.tabs{display:flex;gap:8px;overflow-x:auto;padding-bottom:2px}.tab,.filter-btn{flex:0 0 auto;border:1px solid var(--skb-line);background:#fff;color:var(--skb-text);font-weight:800;cursor:pointer}.tab{min-height:38px;padding:8px 12px;border-radius:8px}.tab.active{background:var(--skb-primary);border-color:var(--skb-primary);color:#fff}.filter-btn{min-height:36px;padding:7px 8px;border-radius:8px;color:var(--skb-muted);font-size:12px;font-weight:900}.filter-btn.active{border-color:var(--skb-primary);background:var(--skb-soft);color:var(--skb-primary)}
    .progress-line{display:grid;gap:7px}.progress-meta{display:flex;justify-content:space-between;gap:10px;color:var(--skb-muted);font-size:14px}.bar{height:12px;overflow:hidden;border:1px solid var(--skb-line);border-radius:999px;background:#edf3f8}.fill{width:0%;height:100%;background:var(--skb-primary);transition:width .2s ease}.activities{padding:12px;display:grid;gap:10px}
    .todo-item{display:grid;grid-template-columns:28px minmax(0,1fr);gap:8px;width:100%;padding:9px 10px;border:1px solid var(--skb-line);border-radius:8px;background:#fff;color:var(--skb-text);text-align:left}.todo-item.done{border-color:#b7dec0;background:#f1f8f3}.todo-item.rejected{border-color:#fecaca;background:#fff7f7}.check{display:grid;place-items:center;width:28px;height:28px;margin-top:1px;border:2px solid var(--skb-line);border-radius:999px;color:transparent}.todo-item.done .check{background:var(--skb-done);border-color:var(--skb-done);color:#fff}.todo-title{display:block;margin-bottom:6px;font-size:16px}.todo-meta,.report-actions,.activity-meta{display:flex;gap:6px;flex-wrap:wrap;align-items:center;margin-bottom:7px}.report-list{display:grid;gap:8px;margin-top:8px}.report-item{display:grid;gap:6px;padding:9px;border:1px solid #e2e9ed;border-radius:8px;background:#f8fafb}.empty{padding:16px;border:1px dashed var(--skb-line);border-radius:8px;color:var(--skb-muted);text-align:center}.source-skb{background:#dff7ea;color:#17633a}.source-mokav2{background:#e8efff;color:#244c9b}.pill.done{background:#dcfce7;color:#166534}.pill.rejected{background:#fee2e2;color:#b91c1c}.pill.waiting{background:#fef3c7;color:#a16207}.pill.keyword{background:#eef2ff;color:#3730a3}
    .monev-card{margin-top:12px;border-left:5px solid var(--skb-primary);background:linear-gradient(135deg,#fff,#f8fffd)}.monev-body{padding:16px;display:grid;gap:12px}.monev-panel{display:grid;gap:10px;padding:14px;border:1px solid #c7ebe3;border-radius:12px;background:#f3fffc}.monev-panel.termination{border-color:#fecaca;background:#fff7f7}.monev-head{display:flex;justify-content:space-between;gap:10px;align-items:start;flex-wrap:wrap}.monev-head h3{margin:0;font-size:17px}.monev-grid{display:grid;gap:10px}.monev-row{padding:10px 12px;border-radius:10px;background:#fff;border:1px solid #e2ecef}.monev-row small{display:block;color:var(--skb-primary);font-weight:900;text-transform:uppercase;letter-spacing:.02em;margin-bottom:4px}.fab{position:fixed;right:25px;bottom:25px;width:58px;height:58px;border-radius:50%;border:0;background:var(--blue);color:#fff;font-size:30px;box-shadow:0 12px 30px #176b8755;cursor:pointer}
    dialog{border:0;border-radius:18px;padding:0;max-width:760px;width:calc(100% - 24px);box-shadow:0 22px 70px #102a3b55}dialog::backdrop{background:#102a3b99}.modal-head{padding:18px 22px;background:var(--navy);color:#fff;display:flex;justify-content:space-between}.modal-body{padding:22px;max-height:75vh;overflow:auto}.modal-section{margin-bottom:18px}.modal-section h3{margin:0 0 10px}.modal-actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:16px}.close{border:0;background:transparent;color:#fff;font-size:22px;cursor:pointer}.details{display:grid;grid-template-columns:180px 1fr;gap:8px 18px}.details dt{font-weight:800}.details dd{margin:0;white-space:pre-wrap}.form-grid{grid-template-columns:1fr 1fr}.btn-danger{background:#b33a3a;color:#fff}.btn-secondary{background:#e9f4f6;color:#17324d}.note{background:#fff8df;border:1px solid #f1d98b;border-radius:12px;padding:10px 12px}.service-select{min-height:126px}
    @media(max-width:700px){.summary,.form-grid{grid-template-columns:1fr}.details{grid-template-columns:1fr}.details dd{margin-bottom:9px}}
</style>
@endpush
@section('content')
<div class="case-shell">
    <header class="case-header">
        <h1>SKB Manajemen Kasus</h1>
        <p>{{ data_get($case, 'case.client_number') ?: 'Data Klien' }} - {{ data_get($case, 'case.registration_number') ?: 'Belum ada nomor registrasi' }}</p>
        <p>
            <span class="pill">{{ strtoupper($case['source_system']) }}</span>
            <span class="pill">{{ data_get($case, 'case.status') ?: '-' }}</span>
            <span class="muted">Sinkron profil: {{ $case['profile_synced_at'] ? \Carbon\Carbon::parse($case['profile_synced_at'])->timezone(config('app.timezone'))->format('d-m-Y H:i:s') : 'Belum pernah' }}</span>
        </p>
    </header>

    <section class="grid summary">
        <button class="card" type="button" onclick="showInfo('case')"><small class="summary-small">1. Identifikasi</small><h2>Data Kasus</h2><p>Data kasus, klasifikasi, pelapor, korban, dan terlapor.</p></button>
        <button class="card" type="button" onclick="showInfo('assessment')"><small class="summary-small">2. Asesmen Awal</small><h2>Riwayat & Kondisi Awal</h2><p>Riwayat kejadian dan asesmen kondisi awal klien.</p></button>
    </section>

    <section class="card workspace">
        <div class="work-head">
            <div><small class="summary-small">3. Perencanaan Intervensi, 4. Pelaksanaan Intervensi</small></div>
            <div class="tabs" id="tabs"></div>
            <div class="progress-line">
                <div class="progress-meta"><span id="progressText">Memuat todo...</span><strong id="progressPercent">0%</strong></div>
                <div class="bar"><div class="fill" id="progressFill"></div></div>
            </div>
            <div class="tabs" id="agendaFilters">
                <button class="filter-btn active" data-filter="all" type="button">Seluruh agenda</button>
                <button class="filter-btn" data-filter="mine" type="button">Agenda saya</button>
                <button class="filter-btn" data-filter="unfinished" type="button">Belum selesai</button>
            </div>
        </div>
        <div class="activities" id="activities"></div>
    </section>

    <section class="card workspace monev-card">
        <div class="monev-body">
            <small class="summary-small">5. Pemantauan dan Evaluasi, 6. Tindak Lanjut, 7. Terminasi Kasus</small>
            <div id="monevArea"></div>
        </div>
    </section>
</div>

<button class="fab" onclick="document.getElementById('agendaDialog').showModal()" title="Tambah agenda">+</button>

<dialog id="infoDialog"><div class="modal-head"><strong id="infoTitle">Detail</strong><button class="close" onclick="infoDialog.close()">×</button></div><div class="modal-body" id="infoBody"></div></dialog>
<dialog id="actionDialog"><div class="modal-head"><strong id="actionTitle">Tindak Lanjut</strong><button class="close" onclick="actionDialog.close()">×</button></div><div class="modal-body" id="actionBody"></div></dialog>
<dialog id="agendaDialog"><div class="modal-head"><strong>Tambah Agenda / Todo</strong><button class="close" onclick="agendaDialog.close()">×</button></div>
    <form class="modal-body grid" method="post" action="{{ route('cases.agendas.store', $case['id']) }}">
        @csrf
        <div class="grid form-grid"><label>Tanggal<input type="date" name="scheduled_date" value="{{ now()->toDateString() }}" required></label><label>Waktu<input type="time" name="scheduled_time" value="09:00" required></label></div>
        <label>Judul kegiatan<textarea name="title" required></textarea></label>
        <label>Petugas<select name="officer_external_ids[]" multiple size="5" required>@foreach($officers as $officer)<option value="{{ $officer->source_id }}">{{ $officer->name }} — {{ $officer->role }}</option>@endforeach</select></label>
        <p class="muted">Intervensi disimpan di hub SKB. Aplikasi sumber dapat mengambilnya melalui API sesuai kewenangan.</p>
        <button class="btn btn-primary">Simpan Intervensi</button>
    </form>
</dialog>
@endsection
@push('scripts')
<script>
let caseData=@json($case);const actorExternal=@json(auth()->user()->external_id);const csrf=@json(csrf_token());const reportActionBase=@json(url('/cases/'.$case['id'].'/reports'));let serviceKeywords=caseData.service_keywords||[];let active=caseData.case.active_intervention_cycle||1;let agendaFilter='all';
const esc=v=>String(v??'-').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
const sourceLabel=source=>source==='skb'?'SKB':source==='mokav2'?'Moka':String(source||'Tidak diketahui').toUpperCase();
const sourceClass=source=>source==='skb'?'source-skb':source==='mokav2'?'source-mokav2':'';
const statusLabel=status=>({pending:'Menunggu konfirmasi',accepted:'Diterima',rejected:'Ditolak',done:'Selesai'}[status]||status||'-');
const decisionLabel=decision=>({intervensi_selanjutnya:'Lanjut intervensi selanjutnya',ajukan_terminasi:'Ajukan terminasi kasus'}[decision]||decision||'-');
const reportAction=report=>`${reportActionBase}/${encodeURIComponent(report.source_id)}`;
const reportServices=report=>(report.content?.services||[]).map(item=>typeof item==='string'?{keyword:item}:item).filter(item=>item.keyword);
function rows(obj){return `<dl class="details">${Object.entries(obj).map(([k,v])=>`<dt>${esc(k)}</dt><dd>${esc(Array.isArray(v)?v.join(', '):v)}</dd>`).join('')}</dl>`}
function showInfo(type){let html='';if(type==='case'){const c=caseData.case;html=rows({'No. Registrasi':c.registration_number,'No. Klien':c.client_number,'Status':c.status,'Tanggal Pelaporan':c.reported_at,'Tanggal Kejadian':c.occurred_at,'Ringkasan':c.summary});html+='<h3>Pihak Terkait</h3>'+caseData.people.map(p=>rows({'Peran':p.role,'Nama':p.identity.name,'NIK':p.identity.nik,'Jenis Kelamin':p.identity.gender,'Telepon':p.identity.phone,'Pekerjaan':p.identity.occupation})).join('<hr>')}else{html='<h3>Riwayat Kejadian</h3>'+caseData.event_histories.map(e=>rows({'Tanggal':e.event_date,'Waktu':e.event_time,'Keterangan':e.description})).join('<hr>')+'<h3>Asesmen</h3>'+caseData.assessments.map(a=>rows(a.content)).join('<hr>')}infoTitle.textContent=type==='case'?'Identifikasi':'Asesmen Awal';infoBody.innerHTML=html||'<p>Belum ada data.</p>';infoDialog.showModal()}
async function readPayload(response){const text=await response.text();try{return JSON.parse(text)}catch(error){const clean=text.replace(/<[^>]+>/g,' ').replace(/\s+/g,' ').trim();return{message:clean?`Server membalas non-JSON (${response.status}): ${clean.slice(0,220)}`:`Server membalas non-JSON (${response.status}).`}}}
async function submitAjaxForm(form){const button=form.querySelector('button[type="submit"],button:not([type])');const original=button?button.textContent:null;if(button){button.disabled=true;button.textContent='Menyimpan...'}try{const target=form.getAttribute('action');const method=form.getAttribute('method')||'POST';const response=await fetch(target,{method,headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN':csrf},credentials:'same-origin',body:new FormData(form)});const payload=await readPayload(response);if(!response.ok)throw new Error(payload.message||'Proses gagal.');const previousActive=active;caseData=payload.data;serviceKeywords=caseData.service_keywords||serviceKeywords;active=caseData.interventions.some(c=>c.cycle_number===previousActive)?previousActive:(caseData.case.active_intervention_cycle||previousActive);render();actionDialog.close();agendaDialog.close();form.reset();return payload}catch(error){alert(error.message)}finally{if(button){button.disabled=false;button.textContent=original}}}
function bindAjaxForms(scope){scope.querySelectorAll('form').forEach(form=>form.addEventListener('submit',event=>{event.preventDefault();submitAjaxForm(form)}))}
function openAction(title, body){actionTitle.textContent=title;actionBody.innerHTML=body;bindAjaxForms(actionBody);actionDialog.showModal()}
function openAcceptModal(report){openAction('Terima Todo / Agenda',`
    <form method="post" action="${reportAction(report)}">
        <input type="hidden" name="_token" value="${csrf}">
        <input type="hidden" name="action" value="accept">
        <p class="note">Dengan menekan <b>Terima</b>, tugas ini tercatat sebagai diterima oleh Anda. Waktu penerimaan akan disimpan untuk audit layanan.</p>
        <div class="modal-actions"><button class="btn btn-primary">Terima Todo</button><button class="btn btn-secondary" type="button" onclick="actionDialog.close()">Batal</button></div>
    </form>`)}
function openRejectModal(report){openAction('Tolak Todo / Agenda',`
    <form method="post" action="${reportAction(report)}" class="grid">
        <input type="hidden" name="_token" value="${csrf}">
        <input type="hidden" name="action" value="reject">
        <p class="note">Penolakan akan tercatat di riwayat intervensi. Isi alasan dengan jelas agar pemohon memahami tindak lanjut berikutnya.</p>
        <label>Alasan penolakan<textarea name="rejection_reason" required>${esc(report.content?.rejection_reason||'')}</textarea></label>
        <div class="modal-actions"><button class="btn btn-danger">Simpan Penolakan</button><button class="btn btn-secondary" type="button" onclick="actionDialog.close()">Batal</button></div>
    </form>`)}
function serviceOptions(selected){return serviceKeywords.map(item=>{const isSelected=selected.includes(item.id)?'selected':'';const suffix=item.jabatan?` — ${item.jabatan}`:'';return `<option value="${esc(item.id)}" ${isSelected}>${esc(item.keyword)}${esc(suffix)}</option>`}).join('')}
function openCompleteModal(report){const selected=reportServices(report).map(item=>item.id).filter(Boolean);openAction('Formulir Tindak Lanjut Intervensi',`
    <form method="post" action="${reportAction(report)}" class="grid">
        <input type="hidden" name="_token" value="${csrf}">
        <input type="hidden" name="action" value="complete">
        <div class="grid form-grid"><label>Lokasi kegiatan<input name="location" value="${esc(report.content?.location||'')}" required></label><label>Jam selesai<input type="time" name="completed_time" required></label></div>
        <label>Detail layanan<select class="service-select" name="service_keyword_ids[]" multiple size="7" required>${serviceOptions(selected)}</select></label>
        ${serviceKeywords.length?'':'<p class="note">Master detail layanan belum tersedia. Sinkronkan data kasus dari Moka agar daftar m_keyword masuk ke SKB.</p>'}
        <label>Proses & hasil<textarea name="process_result" required>${esc(report.content?.process_and_result||'')}</textarea></label>
        <label>Rencana tindak lanjut<textarea name="follow_up_plan" required>${esc(report.content?.follow_up_plan||'')}</textarea></label>
        <div class="modal-actions"><button class="btn btn-primary">Simpan & Tandai Selesai</button><button class="btn btn-secondary" type="button" onclick="actionDialog.close()">Batal</button></div>
    </form>`)}
function openReportDetail(activity, report){const services=reportServices(report).map(item=>item.keyword).join(', ')||'-';const canProcess=report.officer_source_id===actorExternal;let actions='';if(canProcess&&report.status==='pending'){actions=`<button class="btn btn-primary" data-action-button="accept">Terima</button><button class="btn btn-danger" data-action-button="reject">Tolak</button>`}else if(canProcess&&report.status==='accepted'){actions=`<button class="btn btn-primary" data-action-button="complete">Isi Laporan Tindak Lanjut</button>`}openAction('Detail Kegiatan',`
    <div class="modal-section"><h3>Agenda</h3>${rows({'Judul Kegiatan':activity.title,'Tanggal / Waktu':`${activity.scheduled_date||'-'} ${activity.scheduled_time||''}`,'Sumber':sourceLabel(activity.origin_system)})}</div>
    <div class="modal-section"><h3>Tindak Lanjut</h3>${rows({'Petugas':`${report.content?.officer?.name||'-'} (${report.content?.officer?.position||'-'})`,'Status':statusLabel(report.status),'Alasan Ditolak':report.content?.rejection_reason||'-','Lokasi':report.content?.location||'-','Jam Selesai':report.completed_at||'-','Detail Layanan':services,'Rencana Tindak Lanjut':report.content?.follow_up_plan||'-'})}</div>
    ${actions?`<div class="modal-actions">${actions}</div>`:''}`);actionBody.querySelector('[data-action-button="accept"]')?.addEventListener('click',()=>openAcceptModal(report));actionBody.querySelector('[data-action-button="reject"]')?.addEventListener('click',()=>openRejectModal(report));actionBody.querySelector('[data-action-button="complete"]')?.addEventListener('click',()=>openCompleteModal(report))}
function reportButtons(r, cIndex, aIndex, rIndex){const canProcess=r.officer_source_id===actorExternal;let buttons=`<button class="btn btn-secondary" data-report-detail="${cIndex}:${aIndex}:${rIndex}" type="button">Detail</button>`;if(canProcess&&r.status==='pending')buttons+=`<button class="btn btn-primary" data-report-accept="${cIndex}:${aIndex}:${rIndex}" type="button">Terima</button><button class="btn btn-danger" data-report-reject="${cIndex}:${aIndex}:${rIndex}" type="button">Tolak</button>`;if(canProcess&&r.status==='accepted')buttons+=`<button class="btn btn-primary" data-report-complete="${cIndex}:${aIndex}:${rIndex}" type="button">Isi Laporan</button>`;return `<div class="report-actions">${buttons}</div>`}
function findReport(ref){const [c,a,r]=ref.split(':').map(Number);const activity=(caseData.interventions[c]?.activities||[])[a];return [activity,(activity?.reports||[])[r]]}
function attachReportButtons(){document.querySelectorAll('[data-report-detail]').forEach(button=>button.addEventListener('click',event=>{event.stopPropagation();const [activity,report]=findReport(button.dataset.reportDetail);openReportDetail(activity,report)}));document.querySelectorAll('[data-report-accept]').forEach(button=>button.addEventListener('click',event=>{event.stopPropagation();openAcceptModal(findReport(button.dataset.reportAccept)[1])}));document.querySelectorAll('[data-report-reject]').forEach(button=>button.addEventListener('click',event=>{event.stopPropagation();openRejectModal(findReport(button.dataset.reportReject)[1])}));document.querySelectorAll('[data-report-complete]').forEach(button=>button.addEventListener('click',event=>{event.stopPropagation();openCompleteModal(findReport(button.dataset.reportComplete)[1])}))}
function activityStatus(activity){const reports=activity.reports||[];if(reports.some(r=>r.status==='done'))return'done';if(reports.length&&reports.every(r=>r.status==='rejected'))return'rejected';if(reports.some(r=>r.status==='accepted'))return'accepted';return'pending'}
function activityMatchesFilter(activity){if(agendaFilter==='mine')return(activity.reports||[]).some(report=>report.officer_source_id===actorExternal);if(agendaFilter==='unfinished')return activityStatus(activity)!=='done'&&activityStatus(activity)!=='rejected';return true}
function renderFilters(){document.querySelectorAll('[data-filter]').forEach(button=>button.classList.toggle('active',button.dataset.filter===agendaFilter))}
function renderProgress(cycle){const reports=(cycle?.activities||[]).flatMap(activity=>activity.reports||[]);const counted=reports.filter(report=>report.status!=='rejected');const done=counted.filter(report=>report.status==='done').length;const total=counted.length;const rejected=reports.filter(report=>report.status==='rejected').length;const percent=total?Math.round((done/total)*100):0;progressText.textContent=`${done} dari ${total} laporan petugas selesai${rejected?`, ${rejected} ditolak`:''}`;progressPercent.textContent=`${percent}%`;progressFill.style.width=`${percent}%`}
function servicePills(report){return reportServices(report).map(item=>`<span class="pill keyword">${esc(item.keyword)}</span>`).join('')}
function monevRow(label,value){return`<div class="monev-row"><small>${esc(label)}</small><div>${esc(value||'-')}</div></div>`}
function renderMonevTerminasi(cycle){const item=cycle?.monitoring_evaluation;const terminations=caseData.terminations||[];let html='';if(item){html+=`<article class="monev-panel"><div class="monev-head"><h3>Pemantauan & Evaluasi - Intervensi ke-${esc(cycle.cycle_number)}</h3><span class="pill done">${esc(decisionLabel(item.decision))}</span></div><div class="monev-grid">${monevRow('Kemajuan yang dicapai / kondisi klien',item.content?.progress)}${monevRow('Tujuan yang belum tercapai',item.content?.goal_evaluation)}${monevRow('Rencana tindak lanjut',item.content?.plan)}${monevRow('Keputusan',decisionLabel(item.decision))}</div></article>`}if(item?.decision==='ajukan_terminasi'||terminations.length){html+=terminations.map(termination=>`<article class="monev-panel termination"><div class="monev-head"><h3>Terminasi Kasus</h3><span class="pill ${termination.status==='approved'?'done':'rejected'}">${esc(termination.status||'-')}</span></div><div class="monev-grid">${monevRow('Jenis terminasi',termination.type)}${monevRow('Status',termination.status)}${monevRow('Alasan / keterangan terminasi',termination.reason)}</div></article>`).join('') || '<p class="note">Intervensi ini mengajukan terminasi, tetapi detail terminasi belum tersedia.</p>'}monevArea.innerHTML=html||`<p class="note">Belum ada catatan pemantauan & evaluasi untuk Intervensi ke-${esc(cycle?.cycle_number||active)}.</p>`}
function render(){tabs.innerHTML='';caseData.interventions.forEach(c=>{const b=document.createElement('button');b.className='tab '+(c.cycle_number===active?'active':'');b.textContent='Intervensi ke-'+c.cycle_number;b.onclick=()=>{active=c.cycle_number;render()};tabs.appendChild(b)});renderFilters();const cIndex=caseData.interventions.findIndex(c=>c.cycle_number===active);const cycle=caseData.interventions[cIndex];renderProgress(cycle);renderMonevTerminasi(cycle);if(!cycle){activities.innerHTML='<div class="empty">Belum ada intervensi.</div>';return}const filtered=(cycle.activities||[]).filter(activityMatchesFilter);activities.innerHTML=filtered.map((a,aIndexOriginal)=>{const aIndex=(cycle.activities||[]).findIndex(item=>item.source_id===a.source_id);const status=activityStatus(a);return`<article class="todo-item ${status==='done'?'done':''} ${status==='rejected'?'rejected':''}"><span class="check">&#10003;</span><span><strong class="todo-title">${esc(a.title)}</strong><span class="todo-meta"><span class="pill">${esc(a.scheduled_date||'-')}</span><span class="pill">${esc(a.scheduled_time||'-')}</span><span class="pill ${sourceClass(a.origin_system)}">Sumber: ${esc(sourceLabel(a.origin_system))}</span><span class="pill ${status==='done'?'done':status==='rejected'?'rejected':'waiting'}">${esc(statusLabel(status))}</span></span><span class="report-list">${(a.reports||[]).map((r,rIndex)=>`<span class="report-item" role="button" tabindex="0" data-report-detail="${cIndex}:${aIndex}:${rIndex}"><span class="todo-meta"><span class="pill">${esc(r.content?.officer?.name||'Petugas')} (${esc(r.content?.officer?.position||'-')} - ${esc(r.content?.officer?.institution||'-')})</span><span class="pill ${r.status==='done'?'done':r.status==='rejected'?'rejected':'waiting'}">${esc(statusLabel(r.status))}</span>${r.content?.rejection_reason?`<span class="pill rejected">${esc(r.content.rejection_reason)}</span>`:''}${servicePills(r)}</span>${reportButtons(r,cIndex,aIndex,rIndex)}</span>`).join('')||'<span class="empty">Belum ada laporan petugas.</span>'}</span></span></article>`}).join('')||'<div class="empty">Belum ada todo pada intervensi ini.</div>';attachReportButtons()}
document.querySelectorAll('[data-filter]').forEach(button=>button.addEventListener('click',()=>{agendaFilter=button.dataset.filter;render()}));
render();
bindAjaxForms(agendaDialog);
</script>
@endpush
