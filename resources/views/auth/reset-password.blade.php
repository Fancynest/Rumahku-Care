@extends('layouts.app')

@section('title', 'Atur Ulang Kata Sandi - Rumahku Care')

@section('content')
<div class="row justify-content-center w-100">
  <div class="col-lg-4 col-md-6 col-sm-8 col-11">
    <div class="glass-panel p-4 p-md-5 shadow-sm">
      <div class="text-center mb-4">
        <div class="brand-symbol avatar-lg mx-auto mb-2">
          <i class="bi bi-shield-lock"></i>
        </div>
        <h4 class="fw-bold text-dark mb-1">Atur Ulang Kata Sandi</h4>
        <p class="text-muted small">Buat kata sandi baru yang aman untuk akun Anda.</p>
      </div>

      <form action="{{ route('password.update') }}" method="POST">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div class="mb-3">
          <label class="form-label text-muted small mb-1">Alamat Email</label>
          <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $email) }}" required autofocus>
          @error('email')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>

        <div class="mb-3">
          <label class="form-label text-muted small mb-1">Kata Sandi Baru</label>
          <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" required>
          @error('password')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
          <div class="form-text small text-muted">Minimal 8 karakter.</div>
        </div>

        <div class="mb-4">
          <label class="form-label text-muted small mb-1">Konfirmasi Kata Sandi Baru</label>
          <input type="password" name="password_confirmation" class="form-control" required>
        </div>

        <button type="submit" class="btn btn-dark w-100 fw-semibold mb-3">
          Simpan Kata Sandi Baru
        </button>

        <div class="text-center">
          <a href="{{ route('login') }}" class="small fw-semibold text-muted text-decoration-none">
            <i class="bi bi-arrow-left me-1"></i> Batal dan Kembali ke Masuk
          </a>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
