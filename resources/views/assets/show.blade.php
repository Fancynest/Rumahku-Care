@extends('layouts.app')

@section('title', 'Detail Aset: AC Ruang Tamu - Rumahku Care')

@section('content')
<!-- Header Back Button & Actions -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
  <div class="d-flex align-items-center gap-2">
    <a href="{{ route('home-assets.index') }}" class="btn btn-glass btn-sm p-1.5 rounded-3" title="Kembali">
      <i class="bi bi-arrow-left"></i>
    </a>
    <div>
      <h4 class="fw-bold mb-0 text-dark" style="letter-spacing: -0.02em;">Detail Aset Rumah</h4>
      <p class="text-muted mb-0 small">Buku riwayat perawatan & checklist kondisi aset.</p>
    </div>
  </div>

  <div class="d-flex align-items-center gap-2">
    <button class="btn btn-glass btn-sm"><i class="bi bi-exclamation-triangle me-1"></i> Catat Kerusakan</button>
    <button class="btn btn-dark btn-sm"><i class="bi bi-check2 me-1"></i> Catat Servis Selesai</button>
  </div>
</div>

<!-- Hero Card Glass -->
<div class="glass-panel p-4 mb-4">
  <div class="row g-4 align-items-center">
    
    <div class="col-md-3">
      <div class="rounded-3 overflow-hidden position-relative border" style="height: 160px; border-color: rgba(226, 232, 240, 0.7) !important;">
        <img src="https://images.unsplash.com/photo-1625961332771-3f40b0e2bdcf?auto=format&fit=crop&w=600&q=80" class="w-100 h-100 object-fit-cover" alt="AC Daikin">
        <span class="position-absolute bottom-0 start-0 m-2 glass-pill" style="font-size: 0.7rem;">AST-001</span>
      </div>
    </div>

    <div class="col-md-6">
      <div class="d-flex align-items-center gap-2 mb-2">
        <span class="glass-pill"><i class="bi bi-geo-alt"></i> Ruang Tamu</span>
        <span class="chip chip-warning"><span class="chip-dot"></span> Waktunya Cuci</span>
      </div>
      <h4 class="fw-bold text-dark mb-1">AC Daikin 1 PK Flash Inverter</h4>
      <p class="text-muted small mb-3">Model: FTKQ25SVM4 • Freon R32 • Daya 680W</p>
      
      <div class="row g-2 text-muted small" style="font-size: 0.8rem;">
        <div class="col-sm-6">
          <div>Dipasang: 15 Desember 2024</div>
          <div>Umur Pakai: 1 Th 11 Bln</div>
        </div>
        <div class="col-sm-6">
          <div>Garansi: <span class="text-dark fw-medium">Aktif s.d Des 2027</span></div>
          <div>Teknisi Langganan: Pak Budi (AC)</div>
        </div>
      </div>
    </div>

    <div class="col-md-3 border-start-md ps-md-4">
      <div class="p-3 rounded-3 bg-white bg-opacity-60 border text-center mb-2" style="border-color: rgba(226, 232, 240, 0.7);">
        <small class="text-muted d-block">Total Biaya Perawatan</small>
        <h4 class="fw-bold text-dark mb-0">Rp 375.000</h4>
        <small class="text-muted" style="font-size: 0.72rem;">3x Servis Tercatat</small>
      </div>
      <button class="btn btn-glass btn-sm w-100 mb-1.5"><i class="bi bi-paperclip me-1"></i> Nota & Garansi</button>
      <button class="btn btn-glass btn-sm w-100"><i class="bi bi-qr-code me-1"></i> Cetak QR Aset</button>
    </div>

  </div>
</div>

<!-- Grid: Checklist Inspeksi + Riwayat Servis -->
<div class="row g-4">
  
  <!-- Left: Checklist Inspeksi -->
  <div class="col-lg-6">
    <div class="glass-panel p-4 h-100">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <h6 class="fw-bold text-dark mb-0">Checklist Pemeriksaan Mandiri</h6>
          <small class="text-muted">Panduan pengecekan fisik kondisi AC</small>
        </div>
        <span class="glass-pill">Template AC</span>
      </div>

      <div class="space-y-2">
        <div class="p-2.5 rounded-3 bg-white bg-opacity-60 border mb-2" style="border-color: rgba(226, 232, 240, 0.7);">
          <div class="d-flex justify-content-between align-items-center">
            <div class="form-check mb-0">
              <input class="form-check-input" type="checkbox" id="chk1" checked>
              <label class="form-check-label small fw-medium text-dark ms-1" for="chk1">
                Kebersihan Filter Udara
              </label>
            </div>
            <span class="chip chip-warning">Perlu Dicuci</span>
          </div>
        </div>

        <div class="p-2.5 rounded-3 bg-white bg-opacity-60 border mb-2" style="border-color: rgba(226, 232, 240, 0.7);">
          <div class="d-flex justify-content-between align-items-center">
            <div class="form-check mb-0">
              <input class="form-check-input" type="checkbox" id="chk2" checked>
              <label class="form-check-label small fw-medium text-dark ms-1" for="chk2">
                Suhu Hembusan Dingin
              </label>
            </div>
            <span class="chip chip-success">Normal (18°C)</span>
          </div>
        </div>

        <div class="p-2.5 rounded-3 bg-white bg-opacity-60 border mb-2" style="border-color: rgba(226, 232, 240, 0.7);">
          <div class="d-flex justify-content-between align-items-center">
            <div class="form-check mb-0">
              <input class="form-check-input" type="checkbox" id="chk3" checked>
              <label class="form-check-label small fw-medium text-dark ms-1" for="chk3">
                Cek Kebocoran Air Indoor
              </label>
            </div>
            <span class="chip chip-success">Kering</span>
          </div>
        </div>

        <div class="p-2.5 rounded-3 bg-white bg-opacity-60 border mb-2" style="border-color: rgba(226, 232, 240, 0.7);">
          <div class="d-flex justify-content-between align-items-center">
            <div class="form-check mb-0">
              <input class="form-check-input" type="checkbox" id="chk4">
              <label class="form-check-label small fw-medium text-dark ms-1" for="chk4">
                Kelancaran Pipa Pembuangan
              </label>
            </div>
            <span class="chip chip-neutral">Belum Cek</span>
          </div>
        </div>

        <div class="p-2.5 rounded-3 bg-white bg-opacity-60 border mb-3" style="border-color: rgba(226, 232, 240, 0.7);">
          <div class="d-flex justify-content-between align-items-center">
            <div class="form-check mb-0">
              <input class="form-check-input" type="checkbox" id="chk5">
              <label class="form-check-label small fw-medium text-dark ms-1" for="chk5">
                Kondisi Kipas & Kisi Outdoor
              </label>
            </div>
            <span class="chip chip-neutral">Belum Cek</span>
          </div>
        </div>

        <button class="btn btn-dark btn-sm w-100 py-2">
          Simpan Hasil Pemeriksaan
        </button>
      </div>

    </div>
  </div>

  <!-- Right: Timeline Servis -->
  <div class="col-lg-6">
    <div class="glass-panel p-4 h-100">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <h6 class="fw-bold text-dark mb-0">Buku Riwayat Servis</h6>
          <small class="text-muted">Catatan perawatan terdahulu yang Anda simpan</small>
        </div>
        <button class="btn btn-glass btn-sm">+ Catat</button>
      </div>

      <div class="border-start border-slate-200 ps-3 ms-2 space-y-4">
        
        <!-- Riwayat 1 -->
        <div class="position-relative pb-4">
          <div class="position-absolute top-0 start-0 translate-middle rounded-circle bg-dark" style="width: 8px; height: 8px; margin-left: -13px;"></div>
          <div class="d-flex justify-content-between align-items-center">
            <span class="fw-semibold text-dark small">Cuci Rutin & Cek Freon</span>
            <span class="chip chip-success py-0 px-1.5" style="font-size: 0.68rem;">Selesai</span>
          </div>
          <div class="text-muted" style="font-size: 0.75rem;">9 Agustus 2026 • Oleh: Pak Budi (Teknisi AC)</div>
          <p class="text-muted small mt-1 mb-1">Cuci evaporator indoor dan blower outdoor. Tekanan freon 78 psi normal.</p>
          <div class="d-flex justify-content-between align-items-center pt-1" style="font-size: 0.75rem;">
            <span class="fw-semibold text-dark">Biaya: Rp 75.000</span>
            <a href="#" class="text-muted text-decoration-none"><i class="bi bi-paperclip"></i> Lihat Nota</a>
          </div>
        </div>

        <!-- Riwayat 2 -->
        <div class="position-relative pb-4">
          <div class="position-absolute top-0 start-0 translate-middle rounded-circle bg-dark" style="width: 8px; height: 8px; margin-left: -13px;"></div>
          <div class="d-flex justify-content-between align-items-center">
            <span class="fw-semibold text-dark small">Cuci AC & Pembersihan Selang Drain</span>
            <span class="chip chip-success py-0 px-1.5" style="font-size: 0.68rem;">Selesai</span>
          </div>
          <div class="text-muted" style="font-size: 0.75rem;">12 Mei 2026 • Oleh: Pak Budi (Teknisi AC)</div>
          <p class="text-muted small mt-1 mb-1">Selang pembuangan tersumbat lumut. Di-flush sampai lancar.</p>
          <div class="d-flex justify-content-between align-items-center pt-1" style="font-size: 0.75rem;">
            <span class="fw-semibold text-dark">Biaya: Rp 100.000</span>
            <a href="#" class="text-muted text-decoration-none"><i class="bi bi-paperclip"></i> Lihat Nota</a>
          </div>
        </div>

        <!-- Riwayat 3 (Pemasangan) -->
        <div class="position-relative">
          <div class="position-absolute top-0 start-0 translate-middle rounded-circle bg-slate-400" style="width: 8px; height: 8px; margin-left: -13px;"></div>
          <div class="d-flex justify-content-between align-items-center">
            <span class="fw-semibold text-dark small">Pemasangan Unit Baru</span>
            <span class="chip chip-neutral py-0 px-1.5" style="font-size: 0.68rem;">Pemasangan</span>
          </div>
          <div class="text-muted" style="font-size: 0.75rem;">15 Desember 2024 • Pembelian Unit</div>
          <div class="d-flex justify-content-between align-items-center pt-1" style="font-size: 0.75rem;">
            <span class="fw-semibold text-dark">Biaya Jasa: Rp 200.000</span>
            <a href="#" class="text-muted text-decoration-none"><i class="bi bi-file-earmark-pdf"></i> Kartu Garansi</a>
          </div>
        </div>

      </div>

    </div>
  </div>

</div>
@endsection
