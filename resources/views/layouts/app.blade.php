<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SKB Manajemen Kasus')</title>
    <style>
        :root{--navy:#17324d;--blue:#176b87;--cyan:#64ccc5;--paper:#f4f7f9;--ink:#1d2b36;--muted:#657681;--danger:#b33a3a}
        *{box-sizing:border-box}body{margin:0;background:var(--paper);color:var(--ink);font:15px/1.5 Inter,system-ui,sans-serif}
        a{color:inherit;text-decoration:none}.topbar{background:linear-gradient(120deg,var(--navy),var(--blue));color:#fff;padding:16px 28px;display:flex;align-items:center;justify-content:space-between;box-shadow:0 8px 24px #17324d22}
        .brand{font-size:21px;font-weight:800;letter-spacing:.2px}.topbar small{opacity:.75}.container{max-width:1180px;margin:0 auto;padding:28px 20px 70px}
        .card{background:#fff;border:1px solid #dfe7eb;border-radius:18px;box-shadow:0 10px 30px #17324d0d}.btn{border:0;border-radius:10px;padding:10px 15px;font-weight:700;cursor:pointer}.btn-primary{background:var(--blue);color:#fff}.btn-light{background:#fff;color:var(--navy)}
        .notice{padding:12px 16px;background:#e4f8f3;border:1px solid #98dfcf;border-radius:12px;margin-bottom:18px}.error{color:var(--danger);font-size:13px}
        input,select,textarea{width:100%;border:1px solid #cdd9df;border-radius:10px;padding:10px 12px;font:inherit;background:#fff}textarea{min-height:92px;resize:vertical}label{display:grid;gap:6px;font-weight:700}
        .grid{display:grid;gap:16px}.muted{color:var(--muted)}.pill{display:inline-flex;padding:5px 9px;border-radius:999px;background:#e9f4f6;color:#17617a;font-size:12px;font-weight:800}
        @media(max-width:720px){.topbar{padding:14px 16px}.container{padding:18px 12px 60px}}
    </style>
    @stack('styles')
</head>
<body>
<header class="topbar">
    <a class="brand" href="{{ route('cases.index') }}">SKB <small>Manajemen Kasus</small></a>
    @auth
        <div style="display:flex;gap:12px;align-items:center">
            <span>{{ auth()->user()->name }}</span>
            <form method="post" action="{{ route('logout') }}">@csrf<button class="btn btn-light">Keluar</button></form>
        </div>
    @endauth
</header>
<main class="container">
    @if(session('status'))<div class="notice">{{ session('status') }}</div>@endif
    @yield('content')
</main>
@stack('scripts')
</body>
</html>
