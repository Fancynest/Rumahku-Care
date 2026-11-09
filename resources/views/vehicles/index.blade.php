@extends('layouts.app')

@section('title', ($selectedVehicle ? $selectedVehicle->name : 'Kendaraan') . ' - Garasi Rumahku Care')

@section('header-action')
<button class="btn btn-dark btn-sm d-flex align-items-center gap-1.5" data-bs-toggle="modal" data-bs-target="#modalTambahKendaraan" style="min-height: 40px; padding: 0.4rem 1rem;">
  <i class="bi bi-plus-lg"></i>
  <span>Tambah Kendaraan</span>
</button>
@endsection

@section('content')
@if (!$selectedVehicle)
  <div class="glass-panel p-5 text-center my-4">
    <i class="bi bi-car-front text-muted" style="font-size: 3rem;"></i>
    <h5 class="fw-bold mt-3 mb-1 text-dark">Garasi Anda Masih Kosong</h5>
    <p class="text-muted small mb-4">Daftarkan kendaraan pertama Anda untuk mulai memantau servis berkala dan dokumen STNK.</p>
    <button class="btn btn-dark px-4 py-2" data-bs-toggle="modal" data-bs-target="#modalTambahKendaraan" style="min-height: 44px;">
      <i class="bi bi-plus-lg me-1"></i> Tambah Kendaraan Sekarang
    </button>
  </div>
@else

<!-- 1. Header Sederhana: Pemilih Kendaraan (Garasi Strip di Atas) -->
<div class="mb-4">
  <div class="d-flex align-items-center justify-content-between mb-2">
    <div>
      <h5 class="fw-bold text-dark mb-0" style="letter-spacing: -0.02em;">Kendaraan Saya & Garasi</h5>
      <span class="text-muted small">Pilih kendaraan untuk melihat kondisi fisik, odometer, dan servis.</span>
    </div>
  </div>

  <div class="d-flex gap-2 overflow-x-auto pb-2" style="scrollbar-width: thin;">
    @foreach ($vehicles as $v)
      @php
        $isActive = $v->id === $selectedVehicle->id;
      @endphp
      <a href="{{ route('vehicles.index', ['v' => $v->id]) }}" 
         class="text-decoration-none d-flex align-items-center gap-2.5 px-3 py-2 rounded-3 border transition-all text-nowrap {{ $isActive ? 'bg-white border-dark shadow-sm' : 'bg-white-50 border-slate-200 text-secondary' }}"
         style="min-height: 44px; transition: all 0.2s ease;">
        <i class="bi {{ str_starts_with($v->body_type ?? '', 'motor') ? 'bi-bicycle' : 'bi-car-front' }} {{ $isActive ? 'text-dark' : 'text-muted' }}"></i>
        <div>
          <div class="fw-bold small {{ $isActive ? 'text-dark' : 'text-slate-700' }}">{{ $v->name }}</div>
          <div class="text-muted font-monospace" style="font-size: 0.72rem;">{{ $v->plate }}</div>
        </div>
        <span class="chip {{ $v->status_chip_class }} py-0 px-1.5 ms-1" style="font-size: 0.65rem;">
          {{ $v->status_label }}
        </span>
      </a>
    @endforeach
  </div>
</div>

<!-- 2. Visual Kendaraan Besar di Tengah -->
<div class="glass-panel p-4 mb-4 text-center position-relative">
  <div class="d-flex justify-content-between align-items-start mb-2">
    <div class="text-start">
      <span class="badge bg-light text-dark border px-2 py-1 mb-1 text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.05em;">
        {{ strtoupper(str_replace('_', ' ', $selectedVehicle->body_type ?? 'KENDARAAN')) }}
      </span>
      <h3 class="fw-bold text-dark mb-0" style="letter-spacing: -0.02em;">{{ $selectedVehicle->name }}</h3>
    </div>

    @if ($selectedVehicle->vehicleModel?->asset_glb)
      <!-- Color Picker hanya tampil jika menggunakan model GLB 3D nyata -->
      <div class="d-flex align-items-center gap-1.5 p-1 bg-white rounded-pill border shadow-xs" title="Sesuaikan warna cat 3D">
        <button class="color-swatch-btn active" style="background-color: #0f172a;" onclick="setGLBColor([0.06, 0.09, 0.16, 1])"></button>
        <button class="color-swatch-btn" style="background-color: #ffffff;" onclick="setGLBColor([0.95, 0.95, 0.95, 1])"></button>
        <button class="color-swatch-btn" style="background-color: #dc2626;" onclick="setGLBColor([0.85, 0.15, 0.15, 1])"></button>
        <button class="color-swatch-btn" style="background-color: #2563eb;" onclick="setGLBColor([0.15, 0.38, 0.92, 1])"></button>
      </div>
    @endif
  </div>

  <!-- Stage Visual Showcase -->
  <div class="my-3 d-flex justify-content-center align-items-center" style="min-height: 260px;">
    <x-vehicle-visual :vehicle="$selectedVehicle" />
  </div>

  <!-- 3. Tiga Info Kunci: Plat, Odometer, Status -->
  <div class="row g-3 text-start pt-3 border-top border-slate-200 mt-2">
    <!-- Info 1: Nomor Plat -->
    <div class="col-md-4 col-sm-6">
      <div class="p-2.5 rounded-3 bg-white-50 border border-slate-200 h-100">
        <small class="text-muted d-block" style="font-size: 0.75rem;">Nomor Registrasi (Plat)</small>
        <div class="fw-bold fs-5 text-dark font-monospace mt-0.5">{{ $selectedVehicle->plate }}</div>
        <small class="text-muted" style="font-size: 0.72rem;">Tahun Perakitan: {{ $selectedVehicle->year ?? '-' }}</small>
      </div>
    </div>

    <!-- Info 2: Odometer -->
    <div class="col-md-4 col-sm-6">
      <div class="p-2.5 rounded-3 bg-white-50 border border-slate-200 h-100">
        <div class="d-flex justify-content-between align-items-center">
          <small class="text-muted" style="font-size: 0.75rem;">Odometer Saat Ini</small>
          <a href="#" class="text-dark small text-decoration-none fw-medium" data-bs-toggle="modal" data-bs-target="#modalUpdateKM">Update</a>
        </div>
        <div class="fw-bold fs-5 text-dark mt-0.5">{{ number_format($selectedVehicle->odometer) }} <span class="fs-6 text-muted fw-normal">KM</span></div>
        <small class="text-muted" style="font-size: 0.72rem;">Bahan Bakar: {{ $selectedVehicle->fuel_type }}</small>
      </div>
    </div>

    <!-- Info 3: Status Kondisi -->
    <div class="col-md-4 col-sm-12">
      <div class="p-2.5 rounded-3 bg-white-50 border border-slate-200 h-100">
        <small class="text-muted d-block" style="font-size: 0.75rem;">Status Perawatan</small>
        <div class="d-flex align-items-center gap-1.5 mt-1">
          <span class="chip {{ $selectedVehicle->status_chip_class }} py-0.5 px-2">
            <span class="chip-dot"></span> {{ $selectedVehicle->status_label }}
          </span>
        </div>
        <small class="text-muted d-block mt-1" style="font-size: 0.72rem;">Bengkel: {{ $selectedVehicle->workshop ?? 'Bengkel Resmi' }}</small>
      </div>
    </div>
  </div>

  <!-- 4. Tombol Utama & Menu Lainnya -->
  <div class="d-flex flex-wrap justify-content-center align-items-center gap-2.5 mt-4">
    <!-- 1 Tombol Utama: Catat Servis -->
    <button class="btn btn-dark d-inline-flex align-items-center justify-content-center gap-2 px-4 shadow-sm" 
            style="min-height: 44px; font-weight: 600;" 
            data-bs-toggle="modal" 
            data-bs-target="#modalCatatServis">
      <i class="bi bi-wrench"></i>
      <span>Catat Servis</span>
    </button>

    <!-- Menu Dropdown: Lainnya -->
    <div class="dropdown">
      <button class="btn btn-glass d-inline-flex align-items-center justify-content-center gap-2 px-3.5" 
              type="button" 
              data-bs-toggle="dropdown" 
              aria-expanded="false" 
              style="min-height: 44px; font-weight: 500;">
        <span>Lainnya</span>
        <i class="bi bi-chevron-down small"></i>
      </button>
      <ul class="dropdown-menu dropdown-menu-end glass-panel border-0 p-2 shadow-sm" style="min-width: 210px;">
        <li>
          <button class="dropdown-item py-2 rounded-2 small d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#modalDetailSpesifikasi">
            <i class="bi bi-info-circle text-muted"></i>
            <span>Detail Spesifikasi</span>
          </button>
        </li>
        <li>
          <button class="dropdown-item py-2 rounded-2 small d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#modalDokumenSTNK">
            <i class="bi bi-file-earmark-text text-muted"></i>
            <span>Dokumen & Pajak</span>
          </button>
        </li>
        <li><hr class="dropdown-divider my-1"></li>
        <li>
          <button class="dropdown-item py-2 rounded-2 small d-flex align-items-center gap-2 text-primary" data-bs-toggle="modal" data-bs-target="#modalUpdateKM">
            <i class="bi bi-speedometer2"></i>
            <span>Update Odometer (KM)</span>
          </button>
        </li>
      </ul>
    </div>
  </div>
</div>
@endif
@endsection

@section('modals')
@if ($selectedVehicle)
  <!-- ================= MODAL: UPDATE ODOMETER (Dipakai Ulang) ================= -->
  <div class="modal fade" id="modalUpdateKM" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
      <div class="modal-content glass-panel border-0 shadow-lg">
        <form action="{{ route('vehicles.update-odometer', $selectedVehicle->id) }}" method="POST">
          @csrf
          @method('PATCH')
          <div class="modal-header border-bottom border-slate-200 py-3">
            <h6 class="modal-title fw-bold text-dark">Update Odometer</h6>
            <button type="button" class="btn-close small" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body py-3">
            <div class="mb-2">
              <label class="form-label text-muted small mb-1">{{ $selectedVehicle->name }} ({{ $selectedVehicle->plate }})</label>
              <div class="input-group">
                <input type="number" 
                       name="odometer" 
                       class="form-control fw-bold fs-5" 
                       value="{{ $selectedVehicle->odometer }}" 
                       min="{{ $selectedVehicle->odometer }}" 
                       required>
                <span class="input-group-text bg-transparent text-muted">KM</span>
              </div>
              <small class="text-muted" style="font-size: 0.72rem;">Masukkan angka total KM terbaru di dashboard fisik.</small>
            </div>
          </div>
          <div class="modal-footer border-top border-slate-200 py-2.5">
            <button type="button" class="btn btn-glass btn-sm" data-bs-dismiss="modal" style="min-height: 40px;">Batal</button>
            <button type="submit" class="btn btn-dark btn-sm px-3" style="min-height: 40px;">Simpan Perubahan</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- ================= MODAL: CATAT SERVIS ================= -->
  <div class="modal fade" id="modalCatatServis" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content glass-panel border-0 shadow-lg">
        <div class="modal-header border-bottom border-slate-200 py-3">
          <h6 class="modal-title fw-bold text-dark">Catat Servis — {{ $selectedVehicle->name }}</h6>
          <button type="button" class="btn-close small" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body py-3">
          <div class="mb-3">
            <label class="form-label text-muted small mb-1">Jenis Servis / Tindakan</label>
            <select class="form-select">
              <option selected>Ganti Oli Mesin & Filter</option>
              <option>Servis Berkala Rutin (Tune Up)</option>
              <option>Penggantian Kampas Rem</option>
              <option>Rotasi / Penggantian Ban</option>
              <option>Perbaikan Kelistrikan / Aki</option>
              <option>Servis Lainnya</option>
            </select>
          </div>
          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label text-muted small mb-1">Tanggal Servis</label>
              <input type="date" class="form-control" value="{{ date('Y-m-d') }}">
            </div>
            <div class="col-6">
              <label class="form-label text-muted small mb-1">KM Saat Servis</label>
              <input type="number" class="form-control" value="{{ $selectedVehicle->odometer }}">
            </div>
          </div>
          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label text-muted small mb-1">Biaya (Rp)</label>
              <input type="number" class="form-control" placeholder="Contoh: 350000">
            </div>
            <div class="col-6">
              <label class="form-label text-muted small mb-1">Bengkel / Pelaksana</label>
              <input type="text" class="form-control" value="{{ $selectedVehicle->workshop ?? 'Bengkel Resmi' }}">
            </div>
          </div>
          <div class="mb-2">
            <label class="form-label text-muted small mb-1">Catatan Tambahan (opsional)</label>
            <textarea class="form-control" rows="2" placeholder="Nomor nota, saran mekanik, dll."></textarea>
          </div>
        </div>
        <div class="modal-footer border-top border-slate-200 py-2.5">
          <button type="button" class="btn btn-glass btn-sm" data-bs-dismiss="modal" style="min-height: 40px;">Tutup</button>
          <button type="button" class="btn btn-dark btn-sm px-3" data-bs-dismiss="modal" style="min-height: 40px;">Simpan Catatan</button>
        </div>
      </div>
    </div>
  </div>

  <!-- ================= MODAL: DETAIL SPESIFIKASI ================= -->
  <div class="modal fade" id="modalDetailSpesifikasi" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content glass-panel border-0 shadow-lg">
        <div class="modal-header border-bottom border-slate-200 py-3">
          <h6 class="modal-title fw-bold text-dark">Spesifikasi Kendaraan</h6>
          <button type="button" class="btn-close small" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body py-3">
          <table class="table table-sm table-borderless mb-0" style="font-size: 0.85rem;">
            <tbody>
              <tr>
                <td class="text-muted" style="width: 40%;">Merek & Model</td>
                <td class="fw-semibold text-dark">{{ $selectedVehicle->name }}</td>
              </tr>
              <tr>
                <td class="text-muted">Nomor Polisi</td>
                <td class="fw-bold font-monospace text-dark">{{ $selectedVehicle->plate }}</td>
              </tr>
              <tr>
                <td class="text-muted">Tipe Bodi</td>
                <td class="text-uppercase text-dark">{{ str_replace('_', ' ', $selectedVehicle->body_type ?? '-') }}</td>
              </tr>
              <tr>
                <td class="text-muted">Tahun Pembuatan</td>
                <td class="text-dark">{{ $selectedVehicle->year ?? '-' }}</td>
              </tr>
              <tr>
                <td class="text-muted">Bahan Bakar</td>
                <td class="text-dark">{{ $selectedVehicle->fuel_type }}</td>
              </tr>
              <tr>
                <td class="text-muted">Warna Terdaftar</td>
                <td class="text-capitalize text-dark">{{ $selectedVehicle->color_key }}</td>
              </tr>
              <tr>
                <td class="text-muted">Bengkel Rujukan</td>
                <td class="text-dark">{{ $selectedVehicle->workshop ?? 'Belum ditentukan' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="modal-footer border-top border-slate-200 py-2.5">
          <button type="button" class="btn btn-dark btn-sm px-3" data-bs-dismiss="modal" style="min-height: 40px;">Tutup</button>
        </div>
      </div>
    </div>
  </div>

  <!-- ================= MODAL: DOKUMEN & PAJAK ================= -->
  <div class="modal fade" id="modalDokumenSTNK" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content glass-panel border-0 shadow-lg">
        <div class="modal-header border-bottom border-slate-200 py-3">
          <h6 class="modal-title fw-bold text-dark">Dokumen STNK & Pajak</h6>
          <button type="button" class="btn-close small" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body py-3">
          <div class="p-3 rounded-3 bg-light border mb-3">
            <div class="d-flex justify-content-between align-items-center mb-1">
              <span class="text-muted small">Pajak Kendaraan Bermotor (PKB)</span>
              <span class="chip chip-warning py-0 px-2" style="font-size: 0.7rem;">Perlu Bayar</span>
            </div>
            <div class="fw-bold text-dark">Jatuh Tempo: 23 November 2026</div>
            <small class="text-muted">Sisa waktu: 14 Hari lagi</small>
          </div>
          <div class="small text-muted mb-2">
            <strong>Tips:</strong> Pembayaran pajak dapat dilakukan secara daring melalui aplikasi Samsat Digital Nasional (SIGNAL) atau gerai Samsat terdekat sebelum tanggal jatuh tempo untuk menghindari denda administrasi.
          </div>
        </div>
        <div class="modal-footer border-top border-slate-200 py-2.5">
          <button type="button" class="btn btn-dark btn-sm px-3" data-bs-dismiss="modal" style="min-height: 40px;">Mengerti</button>
        </div>
      </div>
    </div>
  </div>
@endif

<!-- ================= MODAL: TAMBAH KENDARAAN (Cascading Select) ================= -->
<div class="modal fade" id="modalTambahKendaraan" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content glass-panel border-0 shadow-lg">
      <form action="{{ route('vehicles.store') }}" method="POST">
        @csrf
        <div class="modal-header border-bottom border-slate-200 py-3">
          <h6 class="modal-title fw-bold text-dark">Tambah Kendaraan Baru</h6>
          <button type="button" class="btn-close small" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        
        <div class="modal-body py-3">
          <!-- Pilih Merek -->
          <div class="mb-3">
            <label class="form-label text-muted small mb-1">Merek Kendaraan <span class="text-danger">*</span></label>
            <select class="form-select" id="selectBrand" required>
              <option value="" disabled selected>Pilih Merek...</option>
              @foreach ($modelsGrouped as $brand => $brandModels)
                <option value="{{ $brand }}">{{ $brand }}</option>
              @endforeach
              <option value="other">Merek Lainnya...</option>
            </select>
          </div>

          <!-- Pilih Model (Cascading) -->
          <div class="mb-3" id="wrapperModel">
            <label class="form-label text-muted small mb-1">Model Kendaraan <span class="text-danger">*</span></label>
            <select class="form-select" id="selectModel" name="vehicle_model_id" required>
              <option value="" disabled selected>Pilih merek terlebih dahulu...</option>
            </select>
          </div>

          <!-- Form Tambahan Khusus "Lainnya" -->
          <div id="wrapperOtherModel" class="p-3 rounded-3 bg-light border mb-3 d-none">
            <div class="mb-2">
              <label class="form-label text-muted small mb-1">Nama Model Lengkap <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="custom_model_name" placeholder="Misal: Vespa Primavera 150">
            </div>
            <div>
              <label class="form-label text-muted small mb-1">Jenis Bodi <span class="text-danger">*</span></label>
              <select class="form-select" name="custom_body_type">
                <option value="motor_matic">Motor Matic (Skuter)</option>
                <option value="motor_sport">Motor Sport / Manual</option>
                <option value="hatchback">Hatchback (City Car)</option>
                <option value="sedan">Sedan</option>
                <option value="mpv">MPV (Mobil Keluarga)</option>
                <option value="suv">SUV</option>
                <option value="pickup">Pickup</option>
                <option value="van">Van</option>
                <option value="bus">Bus</option>
                <option value="truk">Truk</option>
              </select>
            </div>
          </div>

          <!-- Plat & Tahun -->
          <div class="row g-2 mb-3">
            <div class="col-7">
              <label class="form-label text-muted small mb-1">Nomor Plat Polisi <span class="text-danger">*</span></label>
              <input type="text" class="form-control font-monospace text-uppercase" name="plate" placeholder="B 1234 ABC" required>
            </div>
            <div class="col-5">
              <label class="form-label text-muted small mb-1">Tahun Perakitan</label>
              <input type="number" class="form-control" name="year" placeholder="{{ date('Y') }}" min="1970" max="{{ date('Y') + 1 }}">
            </div>
          </div>

          <!-- Odometer & Bahan Bakar -->
          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label text-muted small mb-1">Odometer Awal (KM) <span class="text-danger">*</span></label>
              <input type="number" class="form-control" name="odometer" placeholder="0" min="0" required>
            </div>
            <div class="col-6">
              <label class="form-label text-muted small mb-1">Bahan Bakar <span class="text-danger">*</span></label>
              <select class="form-select" name="fuel_type" required>
                <option value="Bensin" selected>Bensin</option>
                <option value="Diesel">Diesel</option>
                <option value="Listrik (EV)">Listrik (EV)</option>
                <option value="Hybrid">Hybrid</option>
              </select>
            </div>
          </div>

          <!-- Warna & Bengkel -->
          <div class="row g-2 mb-2">
            <div class="col-6">
              <label class="form-label text-muted small mb-1">Warna Bodi <span class="text-danger">*</span></label>
              <select class="form-select" name="color_key" required>
                <option value="hitam" selected>Hitam</option>
                <option value="putih">Putih</option>
                <option value="silver">Silver / Abu-abu</option>
                <option value="merah">Merah</option>
                <option value="biru">Biru</option>
                <option value="lainnya">Lainnya</option>
              </select>
            </div>
            <div class="col-6">
              <label class="form-label text-muted small mb-1">Bengkel Langganan</label>
              <input type="text" class="form-control" name="workshop" placeholder="Opsional (misal: AHASS)">
            </div>
          </div>
        </div>

        <div class="modal-footer border-top border-slate-200 py-2.5">
          <button type="button" class="btn btn-glass btn-sm" data-bs-dismiss="modal" style="min-height: 40px;">Batal</button>
          <button type="submit" class="btn btn-dark btn-sm px-3" style="min-height: 40px;">Simpan ke Garasi</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  // Cascading Select for Models Catalog
  const catalog = @json($modelsGrouped);
  const brandSelect = document.getElementById('selectBrand');
  const modelSelect = document.getElementById('selectModel');
  const otherWrapper = document.getElementById('wrapperOtherModel');

  if (brandSelect && modelSelect) {
    brandSelect.addEventListener('change', function() {
      const brand = this.value;
      modelSelect.innerHTML = '';

      if (brand === 'other') {
        const opt = document.createElement('option');
        opt.value = 'other';
        opt.textContent = 'Model Lainnya (Kustom)';
        opt.selected = true;
        modelSelect.appendChild(opt);
        otherWrapper.classList.remove('d-none');
        return;
      }

      const defaultOpt = document.createElement('option');
      defaultOpt.value = '';
      defaultOpt.textContent = 'Pilih Model ' + brand + '...';
      defaultOpt.disabled = true;
      defaultOpt.selected = true;
      modelSelect.appendChild(defaultOpt);

      if (catalog[brand]) {
        catalog[brand].forEach(function(item) {
          const opt = document.createElement('option');
          opt.value = item.id;
          opt.textContent = item.model + ' (' + item.body_type.replace('_', ' ').toUpperCase() + ')';
          modelSelect.appendChild(opt);
        });
      }

      // Option for custom model under known brand
      const otherOpt = document.createElement('option');
      otherOpt.value = 'other';
      otherOpt.textContent = '+ Model Lainnya...';
      modelSelect.appendChild(otherOpt);

      otherWrapper.classList.add('d-none');
    });

    modelSelect.addEventListener('change', function() {
      if (this.value === 'other') {
        otherWrapper.classList.remove('d-none');
      } else {
        otherWrapper.classList.add('d-none');
      }
    });
  }

  // Real-time color modification for GLB model-viewer
  function setGLBColor(colorFactor) {
    window.selectedColorFactor = colorFactor;
    const viewer = document.querySelector('model-viewer');
    if (viewer && viewer.model) {
      const material = viewer.model.materials.find(m => m.name.toLowerCase().includes('body') || m.name.toLowerCase().includes('paint')) || viewer.model.materials[0];
      if (material) {
        material.pbrMetallicRoughness.setBaseColorFactor(colorFactor);
      }
    }
  }
</script>
@endpush
