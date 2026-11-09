@extends('layouts.app')

@section('title', 'Masuk - Rumahku Care')

@section('content')
<div class="row justify-content-center align-items-center" style="min-height: 75vh;">
  <div class="col-md-5 col-sm-8 col-11">
    <div class="glass-panel p-4 p-md-5 shadow-sm">
      <div class="text-center mb-4">
        <div class="brand-symbol mx-auto mb-2" style="width: 44px; height: 44px; font-size: 1.25rem;">
          <i class="bi bi-shield-check"></i>
        </div>
        <h4 class="fw-bold text-dark mb-1">Masuk ke Rumahku Care</h4>
        <p class="text-muted small">Kelola perawatan rumah dan garasi pribadi Anda</p>
      </div>

      <form action="{{ route('login') }}" method="POST">
        @csrf
        <div class="mb-3">
          <label class="form-label text-muted small mb-1">Alamat Email</label>
          <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" required autofocus style="min-height: 44px;">
          @error('email')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>

        <div class="mb-3">
          <label class="form-label text-muted small mb-1">Kata Sandi</label>
          <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" required style="min-height: 44px;">
          @error('password')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>

        <div class="d-flex justify-content-between align-items-center mb-4">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="remember" id="remember">
            <label class="form-check-label small text-muted" for="remember">Ingat Saya</label>
          </div>
        </div>

        <button type="submit" class="btn btn-dark w-100 fw-semibold mb-3" style="min-height: 44px;">
          Masuk Sekarang
        </button>

        <div class="text-center">
          <span class="text-muted small">Belum punya akun?</span>
          <a href="{{ route('register') }}" class="small fw-semibold text-dark text-decoration-none ms-1">Daftar Akun Baru</a>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
