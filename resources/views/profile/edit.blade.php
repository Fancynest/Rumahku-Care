@extends('layouts.app')

@section('title', 'Profil & Pengaturan Akun - Rumahku Care')

@section('content')
<div class="container-fluid px-0">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="fw-bold text-dark mb-1">Pengaturan Profil & Akun</h4>
      <p class="text-muted small mb-0">Kelola informasi pribadi, keamanan kata sandi, dan status kepemilikan akun Anda.</p>
    </div>
  </div>

  <div class="row g-4">
    <!-- Kolom Kiri: Profil & Ganti Password -->
    <div class="col-lg-7 d-flex flex-column gap-4">
      <!-- Form 1: Data Profil -->
      <div class="glass-panel p-4 shadow-sm">
        <div class="d-flex align-items-center gap-2 mb-3">
          <div class="brand-symbol" style="width: 36px; height: 36px; font-size: 1rem;">
            <i class="bi bi-person"></i>
          </div>
          <div>
            <h6 class="fw-bold text-dark mb-0">Informasi Pribadi</h6>
            <span class="text-muted small" style="font-size: 0.78rem;">Perbarui nama dan alamat email terdaftar</span>
          </div>
        </div>

        <form action="{{ route('profile.update') }}" method="POST">
          @csrf
          @method('PATCH')

          <div class="mb-3">
            <label class="form-label text-muted small mb-1">Nama Lengkap</label>
            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}" required style="min-height: 44px;">
            @error('name')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="mb-3">
            <label class="form-label text-muted small mb-1">Alamat Email</label>
            <input type="email" name="email" id="profileEmailInput" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}" required style="min-height: 44px;">
            @error('email')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="mb-3">
            <label class="form-label text-muted small mb-1">
              Kata Sandi Saat Ini <span class="text-muted fw-normal">(Wajib diisi jika mengubah email)</span>
            </label>
            <input type="password" name="current_password" class="form-control @error('current_password') is-invalid @enderror" placeholder="Masukkan kata sandi saat ini jika email diubah" style="min-height: 44px;">
            @error('current_password')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <button type="submit" class="btn btn-dark fw-semibold" style="min-height: 44px;">
            <i class="bi bi-check2 me-1"></i> Simpan Perubahan Profil
          </button>
        </form>
      </div>

      <!-- Form 2: Ganti Password -->
      <div class="glass-panel p-4 shadow-sm">
        <div class="d-flex align-items-center gap-2 mb-3">
          <div class="brand-symbol" style="width: 36px; height: 36px; font-size: 1rem;">
            <i class="bi bi-shield-lock"></i>
          </div>
          <div>
            <h6 class="fw-bold text-dark mb-0">Keamanan Kata Sandi</h6>
            <span class="text-muted small" style="font-size: 0.78rem;">Perbarui kata sandi secara berkala untuk menjaga akun tetap aman</span>
          </div>
        </div>

        <form action="{{ route('profile.password') }}" method="POST">
          @csrf
          @method('PUT')

          <div class="mb-3">
            <label class="form-label text-muted small mb-1">Kata Sandi Saat Ini</label>
            <input type="password" name="current_password" class="form-control @error('current_password', 'updatePassword') is-invalid @enderror" required style="min-height: 44px;">
            @error('current_password', 'updatePassword')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="mb-3">
            <label class="form-label text-muted small mb-1">Kata Sandi Baru</label>
            <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" required style="min-height: 44px;">
            @error('password')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
            <div class="form-text small text-muted">Minimal 8 karakter.</div>
          </div>

          <div class="mb-3">
            <label class="form-label text-muted small mb-1">Konfirmasi Kata Sandi Baru</label>
            <input type="password" name="password_confirmation" class="form-control" required style="min-height: 44px;">
          </div>

          <div class="alert alert-light border small text-muted mb-3 py-2">
            <i class="bi bi-info-circle me-1"></i> Mengubah kata sandi akan otomatis mengeluarkan (logout) sesi aktif Anda pada peramban dan perangkat lain.
          </div>

          <button type="submit" class="btn btn-dark fw-semibold" style="min-height: 44px;">
            <i class="bi bi-key me-1"></i> Perbarui Kata Sandi
          </button>
        </form>
      </div>
    </div>

    <!-- Kolom Kanan: Ringkasan Akun & Hapus Akun -->
    <div class="col-lg-5 d-flex flex-column gap-4">
      <div class="glass-panel p-4 shadow-sm">
        <h6 class="fw-bold text-dark mb-3">Ringkasan Akun</h6>
        <div class="d-flex align-items-center gap-3 mb-3 pb-3 border-bottom">
          <div class="rounded-circle bg-dark text-white d-flex align-items-center justify-content-center fw-bold fs-4" style="width: 54px; height: 54px;">
            {{ strtoupper(substr($user->name, 0, 2)) }}
          </div>
          <div>
            <div class="fw-bold text-dark fs-6">{{ $user->name }}</div>
            <div class="text-muted small">{{ $user->email }}</div>
            <span class="badge bg-success-subtle text-success mt-1" style="font-size: 0.7rem;">Akun Aktif</span>
          </div>
        </div>

        <div class="d-flex flex-column gap-2 small text-muted">
          <div class="d-flex justify-content-between">
            <span>Bergabung sejak:</span>
            <span class="fw-medium text-dark">{{ $user->created_at ? $user->created_at->format('d M Y') : '-' }}</span>
          </div>
          <div class="d-flex justify-content-between">
            <span>Total Aset Rumah:</span>
            <span class="fw-medium text-dark">{{ $user->homeAssets()->count() }} aset</span>
          </div>
          <div class="d-flex justify-content-between">
            <span>Total Kendaraan:</span>
            <span class="fw-medium text-dark">{{ $user->vehicles()->count() }} kendaraan</span>
          </div>
        </div>
      </div>

      <!-- Zona Bahaya: Hapus Akun -->
      <div class="card border-danger-subtle bg-danger-subtle bg-opacity-25 p-4 shadow-sm rounded-4">
        <div class="d-flex align-items-center gap-2 mb-2 text-danger">
          <i class="bi bi-exclamation-triangle-fill fs-5"></i>
          <h6 class="fw-bold mb-0">Zona Berbahaya</h6>
        </div>
        <p class="text-muted small mb-3">
          Menghapus akun akan menghapus seluruh data Anda secara permanen: seluruh aset rumah, kendaraan di garasi, jadwal perawatan, catatan servis, riwayat BBM, dokumen garansi/pajak, serta seluruh file lampiran fisik di penyimpanan server.
        </p>

        <button type="button" class="btn btn-outline-danger fw-semibold w-100" data-bs-toggle="modal" data-bs-target="#modalHapusAkun" style="min-height: 44px;">
          <i class="bi bi-trash3 me-1"></i> Hapus Akun Saya Permanen
        </button>
      </div>
    </div>
  </div>
</div>
@endsection

@section('modals')
<!-- Modal Konfirmasi Hapus Akun -->
<div class="modal fade" id="modalHapusAkun" tabindex="-1" aria-labelledby="modalHapusAkunLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold text-danger" id="modalHapusAkunLabel">
          <i class="bi bi-exclamation-triangle-fill me-1.5"></i> Hapus Akun Selamanya?
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <form action="{{ route('profile.destroy') }}" method="POST">
        @csrf
        @method('DELETE')
        <div class="modal-body py-3">
          <div class="alert alert-danger border-0 small mb-3">
            <strong>Peringatan!</strong> Tindakan ini TIDAK DAPAT DIBATALKAN. Seluruh data aset, garasi, riwayat, dan berkas fisik Anda akan langsung dihapus dari sistem.
          </div>

          <div class="mb-3">
            <label class="form-label text-muted small mb-1">
              Masukkan Kata Sandi Anda untuk Mengonfirmasi
            </label>
            <input type="password" name="password" class="form-control @error('password', 'destroy') is-invalid @enderror" placeholder="Kata sandi akun Anda" required style="min-height: 44px;">
            @error('password', 'destroy')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal" style="min-height: 44px;">Batal</button>
          <button type="submit" class="btn btn-danger fw-semibold" style="min-height: 44px;">
            <i class="bi bi-trash3 me-1"></i> Ya, Hapus Akun Permanen
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
