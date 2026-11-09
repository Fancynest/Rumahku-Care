@extends('layouts.app')

@section('title', 'Aset Rumah Saya - Rumahku Care')

@section('content')
<!-- Header Actions -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
  <div>
    <h4 class="fw-bold mb-1 text-dark" style="letter-spacing: -0.02em;">Aset & Ruangan Rumah</h4>
    <p class="text-muted mb-0 small">Daftar inventaris perlengkapan dan instalasi rumah yang Anda kelola.</p>
  </div>
  <div class="d-flex gap-2">
    <button class="btn btn-glass btn-sm" data-bs-toggle="modal" data-bs-target="#modalKelolaRuangan">
      <i class="bi bi-door-open me-1"></i> Ruangan Saya
    </button>
    <button class="btn btn-dark btn-sm d-flex align-items-center gap-1.5" data-bs-toggle="modal" data-bs-target="#modalTambahAset">
      <i class="bi bi-plus-lg"></i>
      <span>Tambah Aset</span>
    </button>
  </div>
</div>

<!-- Minimalist Filter Bar -->
<div class="glass-panel p-3 mb-4">
  <div class="row g-2 align-items-center mb-3">
    <div class="col-md-5">
      <div class="input-group">
        <span class="input-group-text bg-transparent border-end-0 text-muted ps-3"><i class="bi bi-search"></i></span>
        <input type="text" class="form-control border-start-0 ps-1" placeholder="Cari nama aset atau merek..." style="background: rgba(255, 255, 255, 0.4);">
      </div>
    </div>
    <div class="col-md-3">
      <select class="form-select" style="background: rgba(255, 255, 255, 0.4);">
        <option value="">Semua Kategori</option>
        <option>Pendingin (AC, Kipas)</option>
        <option>Air (Pompa, Toren, Pipa)</option>
        <option>Listrik (MCB, Panel)</option>
        <option>Dapur & Elektronik</option>
        <option>Keamanan</option>
      </select>
    </div>
    <div class="col-md-3">
      <select class="form-select" style="background: rgba(255, 255, 255, 0.4);">
        <option value="">Semua Kondisi</option>
        <option>Kondisi Baik</option>
        <option>Perlu Cek</option>
        <option>Rusak</option>
      </select>
    </div>
    <div class="col-md-1 text-md-end">
      <button class="btn btn-glass btn-sm w-100 py-2"><i class="bi bi-arrow-repeat"></i></button>
    </div>
  </div>

  <!-- Room Filter Pills -->
  <div class="d-flex gap-2 overflow-x-auto pb-1">
    <button class="btn btn-dark btn-sm px-3 rounded-pill text-nowrap" style="font-size: 0.8rem;">Semua Ruangan (18)</button>
    <button class="btn btn-glass btn-sm px-3 rounded-pill text-nowrap" style="font-size: 0.8rem;">Ruang Tamu (4)</button>
    <button class="btn btn-glass btn-sm px-3 rounded-pill text-nowrap" style="font-size: 0.8rem;">Dapur (5)</button>
    <button class="btn btn-glass btn-sm px-3 rounded-pill text-nowrap" style="font-size: 0.8rem;">Kamar Utama (3)</button>
    <button class="btn btn-glass btn-sm px-3 rounded-pill text-nowrap" style="font-size: 0.8rem;">Dak Atas / Toren (2)</button>
    <button class="btn btn-glass btn-sm px-3 rounded-pill text-nowrap" style="font-size: 0.8rem;">Halaman (4)</button>
  </div>
</div>

<!-- ================= ASSET CARDS GRID ================= -->
<div class="row g-4">
  
  <!-- Aset 1: AC Ruang Tamu -->
  <div class="col-md-6 col-xl-4">
    <div class="asset-item-card h-100 d-flex flex-column justify-content-between">
      <div>
        <div class="position-relative" style="height: 150px; background: #e2e8f0; overflow: hidden;">
          <img src="https://images.unsplash.com/photo-1625961332771-3f40b0e2bdcf?auto=format&fit=crop&w=600&q=80" class="w-100 h-100 object-fit-cover" alt="AC Daikin">
          <div class="position-absolute top-0 start-0 m-2.5">
            <span class="glass-pill"><i class="bi bi-geo-alt"></i> Ruang Tamu</span>
          </div>
          <div class="position-absolute top-0 end-0 m-2.5">
            <span class="chip chip-warning"><span class="chip-dot"></span> Waktunya Cuci</span>
          </div>
        </div>

        <div class="p-3">
          <div class="d-flex justify-content-between align-items-baseline mb-1">
            <h6 class="fw-bold mb-0 text-dark">AC Split 1 PK Inverter</h6>
            <small class="text-muted" style="font-size: 0.72rem;">Pendingin</small>
          </div>
          <p class="text-muted small mb-3">Daikin Flash Inverter (FTKQ25)</p>

          <div class="p-2.5 rounded-3 bg-white bg-opacity-60 border small" style="border-color: rgba(226, 232, 240, 0.7); font-size: 0.78rem;">
            <div class="d-flex justify-content-between mb-1">
              <span class="text-muted">Pemeriksaan Terakhir:</span>
              <span class="text-danger fw-semibold">3 bln lalu (Hari Ini)</span>
            </div>
            <div class="d-flex justify-content-between mb-1">
              <span class="text-muted">Status Garansi:</span>
              <span class="text-success fw-medium">Aktif s.d Des 2027</span>
            </div>
            <div class="d-flex justify-content-between">
              <span class="text-muted">Riwayat Biaya:</span>
              <span class="text-dark fw-medium">Rp 375.000</span>
            </div>
          </div>
        </div>
      </div>

      <div class="p-3 pt-0 border-top border-slate-200 d-flex justify-content-between align-items-center mt-2">
        <a href="{{ route('home-assets.show', 1) }}" class="btn btn-glass btn-sm px-3 small">
          Inspeksi & Detail
        </a>
        <div class="dropdown">
          <button class="btn btn-link text-muted p-0" data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i></button>
          <ul class="dropdown-menu dropdown-menu-end glass-panel border-0 small shadow-sm">
            <li><a class="dropdown-item py-1.5" href="#">Edit Aset</a></li>
            <li><a class="dropdown-item py-1.5 text-danger" href="#">Hapus</a></li>
          </ul>
        </div>
      </div>
    </div>
  </div>

  <!-- Aset 2: Toren Air Utama -->
  <div class="col-md-6 col-xl-4">
    <div class="asset-item-card h-100 d-flex flex-column justify-content-between">
      <div>
        <div class="position-relative" style="height: 150px; background: #e2e8f0; overflow: hidden;">
          <img src="https://images.unsplash.com/photo-1541888946425-d0fbb18086f6?auto=format&fit=crop&w=600&q=80" class="w-100 h-100 object-fit-cover" alt="Toren Air">
          <div class="position-absolute top-0 start-0 m-2.5">
            <span class="glass-pill"><i class="bi bi-geo-alt"></i> Dak Atas</span>
          </div>
          <div class="position-absolute top-0 end-0 m-2.5">
            <span class="chip chip-success"><span class="chip-dot"></span> Bersih</span>
          </div>
        </div>

        <div class="p-3">
          <div class="d-flex justify-content-between align-items-baseline mb-1">
            <h6 class="fw-bold mb-0 text-dark">Toren Air 1000 Liter</h6>
            <small class="text-muted" style="font-size: 0.72rem;">Instalasi Air</small>
          </div>
          <p class="text-muted small mb-3">Penguin Total Drain TB-110</p>

          <div class="p-2.5 rounded-3 bg-white bg-opacity-60 border small" style="border-color: rgba(226, 232, 240, 0.7); font-size: 0.78rem;">
            <div class="d-flex justify-content-between mb-1">
              <span class="text-muted">Kuras Terakhir:</span>
              <span class="text-dark">25 Mei 2026</span>
            </div>
            <div class="d-flex justify-content-between mb-1">
              <span class="text-muted">Jadwal Kuras:</span>
              <span class="text-dark fw-medium">25 Nov 2026</span>
            </div>
            <div class="d-flex justify-content-between">
              <span class="text-muted">Riwayat Biaya:</span>
              <span class="text-dark fw-medium">Rp 150.000</span>
            </div>
          </div>
        </div>
      </div>

      <div class="p-3 pt-0 border-top border-slate-200 d-flex justify-content-between align-items-center mt-2">
        <a href="{{ route('home-assets.show', 2) }}" class="btn btn-glass btn-sm px-3 small">
          Inspeksi & Detail
        </a>
        <div class="dropdown">
          <button class="btn btn-link text-muted p-0" data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i></button>
          <ul class="dropdown-menu dropdown-menu-end glass-panel border-0 small shadow-sm">
            <li><a class="dropdown-item py-1.5" href="#">Edit Aset</a></li>
            <li><a class="dropdown-item py-1.5 text-danger" href="#">Hapus</a></li>
          </ul>
        </div>
      </div>
    </div>
  </div>

  <!-- Aset 3: Pompa Air Booster -->
  <div class="col-md-6 col-xl-4">
    <div class="asset-item-card h-100 d-flex flex-column justify-content-between">
      <div>
        <div class="position-relative" style="height: 150px; background: #e2e8f0; overflow: hidden;">
          <img src="https://images.unsplash.com/photo-1581092160607-ee22621dd758?auto=format&fit=crop&w=600&q=80" class="w-100 h-100 object-fit-cover" alt="Pompa Air">
          <div class="position-absolute top-0 start-0 m-2.5">
            <span class="glass-pill"><i class="bi bi-geo-alt"></i> Dak Atas</span>
          </div>
          <div class="position-absolute top-0 end-0 m-2.5">
            <span class="chip chip-success"><span class="chip-dot"></span> Normal</span>
          </div>
        </div>

        <div class="p-3">
          <div class="d-flex justify-content-between align-items-baseline mb-1">
            <h6 class="fw-bold mb-0 text-dark">Pompa Pendorong Air</h6>
            <small class="text-muted" style="font-size: 0.72rem;">Instalasi Air</small>
          </div>
          <p class="text-muted small mb-3">Wasser PB-218 EA Otomatis</p>

          <div class="p-2.5 rounded-3 bg-white bg-opacity-60 border small" style="border-color: rgba(226, 232, 240, 0.7); font-size: 0.78rem;">
            <div class="d-flex justify-content-between mb-1">
              <span class="text-muted">Pemeriksaan Terakhir:</span>
              <span class="text-dark">12 Sep 2026</span>
            </div>
            <div class="d-flex justify-content-between mb-1">
              <span class="text-muted">Status Garansi:</span>
              <span class="text-muted">Habis</span>
            </div>
            <div class="d-flex justify-content-between">
              <span class="text-muted">Riwayat Biaya:</span>
              <span class="text-dark fw-medium">Rp 0</span>
            </div>
          </div>
        </div>
      </div>

      <div class="p-3 pt-0 border-top border-slate-200 d-flex justify-content-between align-items-center mt-2">
        <a href="{{ route('home-assets.show', 3) }}" class="btn btn-glass btn-sm px-3 small">
          Inspeksi & Detail
        </a>
        <div class="dropdown">
          <button class="btn btn-link text-muted p-0" data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i></button>
          <ul class="dropdown-menu dropdown-menu-end glass-panel border-0 small shadow-sm">
            <li><a class="dropdown-item py-1.5" href="#">Edit Aset</a></li>
            <li><a class="dropdown-item py-1.5 text-danger" href="#">Hapus</a></li>
          </ul>
        </div>
      </div>
    </div>
  </div>

</div>
@endsection

@section('modals')
<!-- Modal Tambah Aset Baru -->
<div class="modal fade" id="modalTambahAset" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content glass-panel border-0 shadow-lg p-3">
      <div class="modal-header border-bottom border-slate-200 pb-2">
        <h6 class="modal-title fw-bold text-dark">Daftarkan Aset Rumah Baru</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-3">
        <form>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label small fw-semibold text-muted">Nama Aset / Perlengkapan <span class="text-danger">*</span></label>
              <input type="text" class="form-control" placeholder="Contoh: AC Kamar Utama" required>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold text-muted">Kategori Aset <span class="text-danger">*</span></label>
              <select class="form-select" required>
                <option value="">Pilih Kategori...</option>
                <option>Pendingin (AC, Kipas)</option>
                <option>Air (Pompa, Toren, Pipa)</option>
                <option>Listrik (MCB, Panel, Lampu)</option>
                <option>Dapur & Peralatan</option>
                <option>Struktur Bangunan</option>
                <option>Keamanan</option>
              </select>
            </div>

            <div class="col-md-6">
              <label class="form-label small fw-semibold text-muted">Letak Ruangan <span class="text-danger">*</span></label>
              <select class="form-select" required>
                <option value="">Pilih Ruangan...</option>
                <option>Ruang Tamu</option>
                <option>Dapur</option>
                <option>Kamar Tidur Utama</option>
                <option>Dak Atas / Toren</option>
                <option>Garasi</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold text-muted">Merek & Model</label>
              <input type="text" class="form-control" placeholder="Contoh: Daikin FTKQ25">
            </div>

            <div class="col-md-4">
              <label class="form-label small fw-semibold text-muted">Tanggal Pemasangan</label>
              <input type="date" class="form-control">
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold text-muted">Garansi Sampai</label>
              <input type="date" class="form-control">
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold text-muted">Interval Servis (Bulan)</label>
              <input type="number" class="form-control" placeholder="Contoh: 3">
            </div>

            <div class="col-12">
              <label class="form-label small fw-semibold text-muted">Catatan Tambahan</label>
              <textarea class="form-control" rows="2" placeholder="Catatan spesifikasi khusus atau penempatan garansi..."></textarea>
            </div>
          </div>

          <div class="d-flex justify-content-end gap-2 mt-4 pt-2 border-top border-slate-200">
            <button type="button" class="btn btn-glass btn-sm" data-bs-dismiss="modal">Batal</button>
            <button type="button" class="btn btn-dark btn-sm px-4" data-bs-dismiss="modal">Simpan Aset</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Modal Kelola Ruangan -->
<div class="modal fade" id="modalKelolaRuangan" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content glass-panel border-0 shadow-lg p-3">
      <div class="modal-header border-bottom border-slate-200 pb-2">
        <h6 class="modal-title fw-bold text-dark">Daftar Ruangan Rumah Anda</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-3">
        <div class="input-group mb-3">
          <input type="text" class="form-control" placeholder="Nama ruangan baru...">
          <button class="btn btn-dark btn-sm"><i class="bi bi-plus-lg"></i> Tambah</button>
        </div>

        <div class="list-group list-group-flush small">
          <div class="list-group-item d-flex justify-content-between align-items-center px-0 bg-transparent">
            <div><i class="bi bi-geo-alt text-muted me-2"></i>Ruang Tamu</div>
            <span class="glass-pill py-0.5 px-2">4 Aset</span>
          </div>
          <div class="list-group-item d-flex justify-content-between align-items-center px-0 bg-transparent">
            <div><i class="bi bi-geo-alt text-muted me-2"></i>Dapur</div>
            <span class="glass-pill py-0.5 px-2">5 Aset</span>
          </div>
          <div class="list-group-item d-flex justify-content-between align-items-center px-0 bg-transparent">
            <div><i class="bi bi-geo-alt text-muted me-2"></i>Dak Atas & Toren</div>
            <span class="glass-pill py-0.5 px-2">2 Aset</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
