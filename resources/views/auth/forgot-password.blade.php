@extends('layouts.app')

@section('title', 'Lupa Kata Sandi - Rumahku Care')

@section('content')
<div class="row justify-content-center w-100">
  <div class="col-lg-4 col-md-6 col-sm-8 col-11">
    <div class="glass-panel p-4 p-md-5 shadow-sm">
      <div class="text-center mb-4">
        <div class="brand-symbol avatar-lg mx-auto mb-2">
          <i class="bi bi-key"></i>
        </div>
        <h4 class="fw-bold text-dark mb-1">Lupa Kata Sandi</h4>
        <p class="text-muted small">Masukkan email akun Anda untuk menerima tautan pengaturan ulang kata sandi.</p>
      </div>

      @if (session('status'))
        <div class="alert alert-info border-0 small mb-4" role="alert">
          <i class="bi bi-info-circle me-1"></i> {{ session('status') }}
        </div>
      @endif

      <form action="{{ route('password.email') }}" method="POST">
        @csrf
        <div class="mb-4">
          <label class="form-label text-muted small mb-1">Alamat Email</label>
          <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" required autofocus>
          @error('email')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>

        <button type="submit" class="btn btn-dark w-100 fw-semibold mb-3">
          Kirim Tautan Pemulihan
        </button>

        <div class="text-center">
          <a href="{{ route('login') }}" class="small fw-semibold text-muted text-decoration-none">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Halaman Masuk
          </a>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
