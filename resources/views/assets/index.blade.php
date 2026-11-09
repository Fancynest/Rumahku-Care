@extends('layouts.app')

@section('title', 'Aset Rumah Saya - Rumahku Care')

@section('header-action')
<button class="btn btn-dark btn-sm d-flex align-items-center gap-1.5" data-bs-toggle="modal" data-bs-target="#modalTambahAset" style="min-height: 40px; padding: 0.4rem 1rem;">
  <i class="bi bi-plus-lg"></i>
  <span>Tambah Aset</span>
</button>
@endsection

@section('content')
<!-- Header Section -->
<div class="mb-4">
  <h4 class="fw-bold mb-1 text-dark" style="letter-spacing: -0.02em;">Aset Rumah Saya</h4>
  <p class="text-muted mb-0 small">Daftar inventaris perlengkapan dan instalasi rumah yang Anda kelola.</p>
</div>

<!-- Ringkasan Kondisi di Atas Daftar -->
<div class="row g-3 mb-4">
  <div class="col-sm-4">
    <div class="glass-panel p-3.5 h-100">
      <div class="d-flex justify-content-between align-items-center mb-1">
        <small class="text-muted" style="font-size: 0.75rem;">Kondisi Normal</small>
        <span class="chip chip-success py-0 px-1.5"><span class="chip-dot"></span> Baik</span>
      </div>
      <h3 class="fw-bold text-dark mb-0">{{ $countNormal }} <span class="fs-6 text-muted fw-normal">unit</span></h3>
    </div>
  </div>

  <div class="col-sm-4">
    <div class="glass-panel p-3.5 h-100">
      <div class="d-flex justify-content-between align-items-center mb-1">
        <small class="text-muted" style="font-size: 0.75rem;">Perlu Perhatian</small>
        <span class="chip chip-warning py-0 px-1.5"><span class="chip-dot"></span> Cek Rutin</span>
      </div>
      <h3 class="fw-bold text-warning mb-0">{{ $countAttention }} <span class="fs-6 text-muted fw-normal">unit</span></h3>
    </div>
  </div>

  <div class="col-sm-4">
    <div class="glass-panel p-3.5 h-100">
      <div class="d-flex justify-content-between align-items-center mb-1">
        <small class="text-muted" style="font-size: 0.75rem;">Kondisi Rusak</small>
        <span class="chip chip-urgent py-0 px-1.5"><span class="chip-dot"></span> Perbaikan</span>
      </div>
      <h3 class="fw-bold text-danger mb-0">{{ $countBroken }} <span class="fs-6 text-muted fw-normal">unit</span></h3>
    </div>
  </div>
</div>

<!-- Daftar Aset Real DB -->
<div class="glass-panel p-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h6 class="fw-bold text-dark mb-0">Daftar Aset Terdaftar ({{ $assets->count() }})</h6>
  </div>

  @if ($assets->isEmpty())
    <div class="text-center py-5">
      <i class="bi bi-house-gear text-muted" style="font-size: 2.5rem;"></i>
      <h6 class="fw-bold mt-3 mb-1 text-dark">Belum Ada Aset Rumah</h6>
      <p class="text-muted small mb-3">Daftarkan aset rumah seperti AC, toren air, pompa air, atau kompor.</p>
      <button class="btn btn-dark btn-sm px-3" data-bs-toggle="modal" data-bs-target="#modalTambahAset" style="min-height: 44px;">
        <i class="bi bi-plus-lg me-1"></i> Tambah Aset Pertama
      </button>
    </div>
  @else
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
        <thead class="text-muted" style="border-bottom: 1px solid rgba(226, 232, 240, 0.8);">
          <tr>
            <th class="py-2.5">Nama Aset</th>
            <th class="py-2.5">Kategori & Ruangan</th>
            <th class="py-2.5">Merek & Model</th>
            <th class="py-2.5">Kondisi</th>
            <th class="py-2.5 text-end">Aksi</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($assets as $asset)
            <tr>
              <td class="py-3">
                <div class="d-flex align-items-center gap-2.5">
                  @if ($asset->primary_image_url)
                    <img src="{{ $asset->primary_image_url }}" alt="{{ $asset->name }}" class="rounded-2 object-fit-cover border flex-shrink-0" style="width: 40px; height: 40px;" loading="lazy">
                  @else
                    <div class="rounded-2 bg-light border d-flex align-items-center justify-content-center text-muted flex-shrink-0" style="width: 40px; height: 40px; font-size: 1.1rem;">
                      <i class="bi bi-house-door"></i>
                    </div>
                  @endif
                  <div>
                    <a href="{{ route('home-assets.show', $asset->id) }}" class="fw-bold text-dark text-decoration-none d-block">
                      {{ $asset->name }}
                    </a>
                  </div>
                </div>
              </td>
              <td>
                <span class="text-capitalize text-secondary">{{ $asset->category_label }}</span>
                <small class="text-muted d-block" style="font-size: 0.72rem;"><i class="bi bi-geo-alt me-0.5"></i>{{ $asset->room }}</small>
              </td>
              <td>
                <span class="text-dark">{{ $asset->brand ?? '-' }}</span>
                @if ($asset->model)
                  <small class="text-muted d-block" style="font-size: 0.72rem;">{{ $asset->model }}</small>
                @endif
              </td>
              <td>
                <span class="chip {{ $asset->condition_chip_class }} py-0.5 px-2">
                  <span class="chip-dot"></span> {{ $asset->condition_label }}
                </span>
              </td>
              <td class="text-end">
                <a href="{{ route('home-assets.show', $asset->id) }}" class="btn btn-glass btn-sm py-1.5 px-2.5" style="min-height: 38px;">
                  Detail
                </a>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @endif
</div>
@endsection

@section('modals')
<!-- Modal Tambah Aset -->
<div class="modal fade" id="modalTambahAset" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content glass-panel border-0 shadow-lg">
      <form action="{{ route('home-assets.store') }}" method="POST">
        @csrf
        <div class="modal-header border-bottom border-slate-200 py-3">
          <h6 class="modal-title fw-bold text-dark">Tambah Aset Rumah Baru</h6>
          <button type="button" class="btn-close small" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        
        <div class="modal-body py-3">
          <div class="mb-3">
            <label class="form-label text-muted small mb-1">Nama Aset <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="name" placeholder="Misal: AC Ruang Tamu, Pompa Air Sumur" required style="min-height: 44px;">
          </div>

          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label text-muted small mb-1">Kategori <span class="text-danger">*</span></label>
              <select class="form-select" name="category" required style="min-height: 44px;">
                <option value="pendingin" selected>Pendingin (AC/Kipas)</option>
                <option value="air">Air (Pompa/Toren/Pipa)</option>
                <option value="listrik">Listrik (MCB/Panel)</option>
                <option value="dapur">Dapur (Kompor/Kulkas)</option>
                <option value="kamar_mandi">Kamar Mandi</option>
                <option value="bangunan">Bangunan (Atap/Plafon)</option>
                <option value="keamanan">Keamanan (CCTV/Pagar)</option>
                <option value="eksterior">Eksterior (Talang/Genteng)</option>
                <option value="peralatan">Peralatan (Mesin Cuci)</option>
              </select>
            </div>
            <div class="col-6">
              <label class="form-label text-muted small mb-1">Lokasi / Ruangan <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="room" placeholder="Misal: Ruang Tamu, Dapur" required style="min-height: 44px;">
            </div>
          </div>

          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label text-muted small mb-1">Merek</label>
              <input type="text" class="form-control" name="brand" placeholder="Misal: Daikin, Shimizu" style="min-height: 44px;">
            </div>
            <div class="col-6">
              <label class="form-label text-muted small mb-1">Model / Tipe</label>
              <input type="text" class="form-control" name="model" placeholder="Misal: Inverter 1 PK" style="min-height: 44px;">
            </div>
          </div>

          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label text-muted small mb-1">Tanggal Pasang / Beli</label>
              <input type="date" class="form-control" name="purchased_at" style="min-height: 44px;">
            </div>
            <div class="col-6">
              <label class="form-label text-muted small mb-1">Kondisi Saat Ini <span class="text-danger">*</span></label>
              <select class="form-select" name="condition" required style="min-height: 44px;">
                <option value="normal" selected>Normal</option>
                <option value="perlu_perhatian">Perlu Perhatian</option>
                <option value="rusak">Rusak</option>
              </select>
            </div>
          </div>

          <div class="mb-2">
            <label class="form-label text-muted small mb-1">Catatan Tambahan (opsional)</label>
            <textarea class="form-control" name="notes" rows="2" placeholder="Catatan servis berkala, lokasi stop kontak, dll."></textarea>
          </div>
        </div>

        <div class="modal-footer border-top border-slate-200 py-2.5">
          <button type="button" class="btn btn-glass btn-sm" data-bs-dismiss="modal" style="min-height: 40px;">Batal</button>
          <button type="submit" class="btn btn-dark btn-sm px-3" style="min-height: 40px;">Simpan Aset</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
