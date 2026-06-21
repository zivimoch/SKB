<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SKB Developer Portal</title>
    <style>
        :root{--navy:#142d45;--blue:#116d8a;--mint:#65cfc7;--paper:#f4f8fa;--ink:#1d2d38;--muted:#647784}
        *{box-sizing:border-box}body{margin:0;background:var(--paper);color:var(--ink);font:16px/1.65 Inter,system-ui,sans-serif}a{color:var(--blue)}
        .hero{background:linear-gradient(130deg,var(--navy),var(--blue));color:#fff;padding:72px 24px}.wrap{max-width:1120px;margin:auto}.hero h1{font-size:46px;line-height:1.1;margin:12px 0}.hero p{max-width:720px;font-size:19px;opacity:.86}.badge{display:inline-flex;padding:6px 10px;border-radius:999px;background:#ffffff22;font-weight:800;font-size:13px}
        .actions{display:flex;gap:12px;flex-wrap:wrap;margin-top:28px}.btn{display:inline-block;padding:12px 17px;border-radius:11px;background:#fff;color:var(--navy);text-decoration:none;font-weight:800}.btn.alt{background:var(--mint)}
        main{padding:42px 24px 80px}.grid{display:grid;grid-template-columns:repeat(3,1fr);gap:18px}.card{background:#fff;border:1px solid #dce7eb;border-radius:18px;padding:22px;box-shadow:0 8px 28px #142d450b}.card h2{margin:0 0 8px;font-size:20px}.section{margin-top:42px}.steps{counter-reset:step;display:grid;gap:13px}.step{background:#fff;border-left:5px solid var(--mint);padding:17px 20px;border-radius:12px}.step:before{counter-increment:step;content:counter(step);display:inline-grid;place-items:center;width:28px;height:28px;border-radius:50%;background:var(--navy);color:#fff;font-weight:800;margin-right:10px}
        code{background:#eaf1f4;padding:2px 6px;border-radius:5px}footer{padding:26px;text-align:center;color:var(--muted)}
        @media(max-width:800px){.grid{grid-template-columns:1fr}.hero h1{font-size:35px}}
    </style>
</head>
<body>
<header class="hero"><div class="wrap">
    <span class="badge">Partner API • v1</span>
    <h1>SKB Developer Portal</h1>
    <p>Platform integrasi penanganan kasus lintas aplikasi dan instansi. Gunakan API SKB untuk bertukar data kasus secara aman, terukur, dan dapat diaudit.</p>
    <div class="actions">
        <a class="btn alt" href="{{ route('developers.api') }}">Coba API Interaktif</a>
        <a class="btn" href="{{ route('developers.openapi') }}">Unduh OpenAPI</a>
        <a class="btn" href="{{ url('/api/v1/health') }}">Cek Status API</a>
    </div>
</div></header>
<main class="wrap">
    <div class="grid">
        <article class="card"><h2>Kasus & Asesmen</h2><p>Kirim snapshot identifikasi, pihak terkait, klasifikasi, riwayat kejadian, dan asesmen dengan ID sumber yang stabil.</p></article>
        <article class="card"><h2>Intervensi</h2><p>Pertukarkan agenda, todo petugas, laporan pelaksanaan, monev, dan terminasi melalui kontrak API berversi.</p></article>
        <article class="card"><h2>Audit & Keamanan</h2><p>Setiap request diidentifikasi berdasarkan aplikasi sumber dan actor manusia, ditandatangani, dibatasi scope, dan dicatat.</p></article>
    </div>
    <section class="section">
        <h2>Mulai integrasi</h2>
        <div class="steps">
            <div class="step">Ajukan onboarding instansi dan tunjuk PIC teknis serta PIC perlindungan data.</div>
            <div class="step">Dapatkan <code>key_id</code>, secret sandbox, scope, dan data uji fiktif.</div>
            <div class="step">Uji <code>GET /api/v1/integrations/me</code> dan <code>POST /api/v1/integrations/echo</code>.</div>
            <div class="step">Validasi payload kasus menggunakan dokumentasi OpenAPI dan koleksi Postman.</div>
            <div class="step">Lulus security review sebelum kredensial production diterbitkan.</div>
        </div>
    </section>
    <section class="section grid">
        <article class="card"><h2>Dokumentasi</h2><p><a href="{{ route('developers.api') }}">API reference</a><br><a href="{{ route('developers.document', ['path' => 'guides/onboarding.md']) }}">Onboarding</a><br><a href="{{ route('developers.document', ['path' => 'guides/authentication.md']) }}">Authentication</a></p></article>
        <article class="card"><h2>Perangkat integrasi</h2><p>Contoh PHP, Node.js, Python, koleksi Postman, payload fiktif, serta canonical request untuk HMAC tersedia di repository.</p></article>
        <article class="card"><h2>Dukungan</h2><p>Gunakan <code>X-Request-Id</code> saat melaporkan kendala. Jangan kirim payload kasus atau secret melalui email/tiket dukungan.</p></article>
    </section>
</main>
<footer>SKB Integration Platform • Dokumentasi API v1</footer>
</body>
</html>
