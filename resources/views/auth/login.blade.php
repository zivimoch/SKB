@extends('layouts.app')
@section('title', 'Login SKB')
@section('content')
<div class="card" style="max-width:440px;margin:8vh auto;padding:30px">
    <h1 style="margin-top:0">Masuk ke SKB</h1>
    <p class="muted">Gunakan akun Moka yang telah diberi akses SKB.</p>
    <form class="grid" method="post" action="{{ route('login.store') }}">
        @csrf
        <label>Email<input type="email" name="email" value="{{ old('email') }}" required autofocus></label>
        @error('email')<div class="error">{{ $message }}</div>@enderror
        <label>Password<input type="password" name="password" required></label>
        <label style="display:flex;grid-template-columns:auto 1fr;align-items:center;font-weight:500"><input style="width:auto" type="checkbox" name="remember" value="1"> Ingat saya</label>
        <button class="btn btn-primary">Login</button>
    </form>
</div>
@endsection
