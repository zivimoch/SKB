<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} - SKB Developer Portal</title>
    <style>
        body{margin:0;background:#f4f8fa;color:#20313c;font:16px/1.7 Inter,system-ui,sans-serif}.top{background:#142d45;color:#fff;padding:18px 24px}.top a{color:#fff;text-decoration:none;font-weight:800}.doc{max-width:900px;margin:30px auto;background:#fff;border:1px solid #dce7eb;border-radius:16px;padding:28px 36px;box-shadow:0 8px 30px #142d450c}.doc h1,.doc h2,.doc h3{line-height:1.25;color:#142d45}.doc table{width:100%;border-collapse:collapse}.doc th,.doc td{border:1px solid #d7e1e5;padding:8px;text-align:left}.doc code{background:#edf3f5;padding:2px 5px;border-radius:4px}.doc pre{background:#142d45;color:#eff;padding:15px;border-radius:10px;overflow:auto}.doc pre code{background:transparent}
    </style>
</head>
<body>
<header class="top"><a href="{{ route('developers.index') }}">← SKB Developer Portal</a></header>
<article class="doc">{!! $content !!}</article>
</body>
</html>
