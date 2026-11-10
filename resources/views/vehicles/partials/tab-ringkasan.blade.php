<div class="tab-pane fade show active" id="ringkasan" role="tabpanel" aria-labelledby="tab-v-ringkasan-btn">
  @if ($selectedVehicle->isArchived())
    <div class="alert alert-warning border-0 shadow-sm d-flex align-items-center justify-content-between mb-3" role="alert">
      <div class="d-flex align-items-center gap-2">
        <i class="bi bi-archive-fill fs-5"></i>
        <div>
          <span class="fw-bold">Kendaraan ini berada dalam arsip</span>
          <span class="small text-muted d-block">
            Alasan arsip: <span class="fw-semibold text-dark">{{ ucfirst(str_replace('_', ' ', $selectedVehicle->archive_reason ?? 'Lainnya')) }}</span> • Diarsipkan pada {{ $selectedVehicle->archived_at?->format('d M Y') }}.
          </span>
        </div>
      </div>
    </div>
  @endif

  <!-- Visual Kendaraan Besar di Tengah -->
  <div class="glass-panel p-4 mb-4 text-center position-relative">
    <div class="d-flex justify-content-between align-items-start mb-2 flex-wrap gap-2">
      <div class="text-start">
        <span class="badge bg-light text-dark border px-2 py-1 mb-1 text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.05em;">
          {{ strtoupper(str_replace('_', ' ', $selectedVehicle->body_type ?? 'KENDARAAN')) }}
        </span>
        <h3 class="fw-bold text-dark mb-0" style="letter-spacing: -0.02em;">{{ $selectedVehicle->name }}</h3>
      </div>

      <div class="d-flex align-items-center gap-2">
        @if ($selectedVehicle->vehicleModel?->asset_glb)
          <!-- Color Picker hanya tampil jika menggunakan model GLB 3D nyata -->
          <div class="d-flex align-items-center gap-1.5 p-1 bg-white rounded-pill border shadow-xs" title="Sesuaikan warna cat 3D">
            <button class="color-swatch-btn active" style="background-color: #0f172a;" onclick="setGLBColor([0.06, 0.09, 0.16, 1])"></button>
            <button class="color-swatch-btn" style="background-color: #ffffff;" onclick="setGLBColor([0.95, 0.95, 0.95, 1])"></button>
            <button class="color-swatch-btn" style="background-color: #dc2626;" onclick="setGLBColor([0.85, 0.15, 0.15, 1])"></button>
            <button class="color-swatch-btn" style="background-color: #2563eb;" onclick="setGLBColor([0.15, 0.38, 0.92, 1])"></button>
          </div>
        @endif

        <button type="button" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#modalEditKendaraan" style="min-height: 38px;">
          <i class="bi bi-pencil"></i>
          <span class="d-none d-sm-inline">Edit</span>
        </button>

        @if (! $selectedVehicle->isArchived())
          <button type="button" class="btn btn-outline-danger btn-sm d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#modalArsipKendaraan" style="min-height: 38px;">
            <i class="bi bi-archive"></i>
            <span class="d-none d-sm-inline">Arsipkan</span>
          </button>
        @else
          <form action="{{ route('vehicles.restore', $selectedVehicle->id) }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-outline-success btn-sm d-flex align-items-center gap-1" style="min-height: 38px;">
              <i class="bi bi-arrow-counterclockwise"></i>
              <span>Pulihkan</span>
            </button>
          </form>

          <button type="button" class="btn btn-danger btn-sm d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#modalHapusPermanenKendaraan" style="min-height: 38px;">
            <i class="bi bi-trash3"></i>
            <span>Hapus Permanen</span>
          </button>
        @endif
      </div>
    </div>

    <!-- Stage Visual Showcase -->
    <div class="my-3 d-flex justify-content-center align-items-center" style="min-height: 260px;">
      <x-vehicle-visual :vehicle="$selectedVehicle" />
    </div>

    <!-- 3 Info Kunci: Plat, Odometer, Status -->
    <div class="row g-3 text-start pt-3 border-top border-slate-200 mt-2">
      <!-- Info 1: Nomor Plat & Warna -->
      <div class="col-md-4 col-sm-6">
        <div class="p-2.5 rounded-3 bg-white-50 border border-slate-200 h-100">
          <small class="text-muted d-block" style="font-size: 0.75rem;">Nomor Registrasi (Plat)</small>
          <div class="fw-bold fs-5 text-dark font-monospace mt-0.5">{{ $selectedVehicle->plate }}</div>
          <div class="d-flex align-items-center gap-2 mt-1">
            <small class="text-muted" style="font-size: 0.72rem;">Tahun: {{ $selectedVehicle->year ?? '-' }}</small>
            <span class="text-muted" style="font-size: 0.72rem;">•</span>
            <span class="badge bg-white text-dark border py-0.5 px-1.5 text-capitalize" style="font-size: 0.72rem; font-weight: 500;">
              <i class="bi bi-palette me-1 text-secondary"></i>Warna: {{ $selectedVehicle->color_key }}
            </span>
          </div>
        </div>
      </div>

      <!-- Info 2: Odometer -->
      <div class="col-md-4 col-sm-6">
        <div class="p-2.5 rounded-3 bg-white-50 border border-slate-200 h-100">
          <div class="d-flex justify-content-between align-items-center">
            <small class="text-muted" style="font-size: 0.75rem;">Odometer Saat Ini</small>
            <button type="button" class="btn btn-link text-dark p-0 small text-decoration-none fw-medium" data-bs-toggle="modal" data-bs-target="#modalUpdateKM">Update</button>
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

    <!-- Tombol Aksi Utama -->
    <div class="d-flex flex-wrap justify-content-center align-items-center gap-2.5 mt-4">
      <button class="btn btn-dark d-inline-flex align-items-center justify-content-center gap-2 px-4 shadow-sm" 
              style="min-height: 44px; font-weight: 600;" 
              data-bs-toggle="modal" 
              data-bs-target="#modalCatatServis">
        <i class="bi bi-wrench"></i>
        <span>Catat Servis</span>
      </button>

      <button class="btn btn-glass d-inline-flex align-items-center justify-content-center gap-2 px-3.5" 
              data-bs-toggle="modal" 
              data-bs-target="#modalDetailSpesifikasi" 
              style="min-height: 44px; font-weight: 500;">
        <i class="bi bi-info-circle text-muted"></i>
        <span>Detail Spesifikasi</span>
      </button>

      <button class="btn btn-glass d-inline-flex align-items-center justify-content-center gap-2 px-3.5" 
              data-bs-toggle="modal" 
              data-bs-target="#modalUpdateKM" 
              style="min-height: 44px; font-weight: 500;">
        <i class="bi bi-speedometer2 text-muted"></i>
        <span>Update KM</span>
      </button>
    </div>
  </div>

  <!-- Riwayat Servis Terakhir dengan Dukungan Nota / Bukti Bayar -->
  <div class="glass-panel p-4 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <h6 class="fw-bold text-dark mb-0">Riwayat Servis Terakhir</h6>
        <span class="text-muted small">Catatan perawatan berkala dan bukti nota bengkel untuk {{ $selectedVehicle->name }}.</span>
      </div>
      <button class="btn btn-dark btn-sm d-flex align-items-center gap-1.5" data-bs-toggle="modal" data-bs-target="#modalCatatServis" style="min-height: 40px; padding: 0.4rem 1rem;">
        <i class="bi bi-wrench"></i>
        <span>Catat Servis</span>
      </button>
    </div>

    @if ($recentRecords->isEmpty())
      <div class="p-4 text-center text-muted bg-white-50 rounded-3 border border-dashed border-slate-200">
        <i class="bi bi-wrench-adjustable d-block fs-3 mb-1 text-secondary"></i>
        <div class="fw-medium text-dark small">Belum ada riwayat servis tercatat</div>
        <p class="text-muted mb-0" style="font-size: 0.78rem;">Klik "Catat Servis" di atas untuk menambahkan catatan perawatan pertama Anda.</p>
      </div>
    @else
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
          <thead class="text-muted" style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em;">
            <tr>
              <th class="ps-3 py-2.5">Tanggal & KM</th>
              <th class="py-2.5">Jenis Servis</th>
              <th class="py-2.5">Bengkel</th>
              <th class="py-2.5">Total Biaya</th>
              <th class="py-2.5">Catatan</th>
              <th class="pe-3 py-2.5 text-end">Nota & Bukti</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($recentRecords as $record)
              <tr>
                <td class="ps-3 py-3">
                  <div class="fw-bold text-dark">{{ $record->done_at?->format('d/m/Y') ?? '-' }}</div>
                  <div class="text-muted font-monospace" style="font-size: 0.75rem;">
                    {{ $record->odometer ? number_format($record->odometer) . ' KM' : '-' }}
                  </div>
                </td>
                <td class="py-3">
                  <span class="badge bg-light text-dark border px-2 py-1">
                    {{ $record->service_type }}
                  </span>
                  @if ($record->schedule)
                    <div class="text-success small mt-0.5" style="font-size: 0.72rem;">
                      <i class="bi bi-check-circle-fill me-0.5"></i>{{ $record->schedule->title }}
                    </div>
                  @endif
                </td>
                <td class="py-3 text-secondary">
                  <i class="bi bi-geo-alt me-1 text-muted"></i>{{ $record->workshop_name ?? '-' }}
                </td>
                <td class="py-3">
                  <div class="fw-bold text-dark">Rp {{ number_format($record->total_cost, 0, ',', '.') }}</div>
                  @if ($record->labor_cost > 0 || $record->parts_cost > 0)
                    <small class="text-muted d-block" style="font-size: 0.7rem;">
                      Jasa: Rp {{ number_format($record->labor_cost, 0, ',', '.') }} | Part: Rp {{ number_format($record->parts_cost, 0, ',', '.') }}
                    </small>
                  @endif
                </td>
                <td class="py-3">
                  <div class="text-dark">{{ $record->notes ?? '-' }}</div>
                  @if ($record->parts)
                    <small class="text-muted d-block mt-0.5" style="font-size: 0.72rem;">
                      <i class="bi bi-tools me-1"></i>{{ $record->parts }}
                    </small>
                  @endif
                </td>
                <td class="pe-3 py-3 text-end">
                  <div class="d-inline-flex align-items-center gap-1.5">
                    @foreach ($record->attachments as $att)
                      <a href="{{ route('attachments.show', $att->id) }}" target="_blank" class="badge bg-light text-dark border text-decoration-none py-1 px-2" title="{{ $att->original_name }}">
                        <i class="bi {{ str_contains($att->mime, 'pdf') ? 'bi-file-earmark-pdf text-danger' : 'bi-image' }} me-1"></i>Nota
                      </a>
                    @endforeach
                    <button type="button" class="btn btn-glass btn-sm py-1 px-2 btn-upload-record-nota" 
                            style="font-size: 0.75rem; min-height: 32px;"
                            data-bs-toggle="modal" 
                            data-bs-target="#modalUploadNotaServis"
                            data-id="{{ $record->id }}"
                            data-title="{{ $record->service_type ?: 'Servis' }} ({{ $record->done_at?->format('d/m/Y') }})">
                      <i class="bi bi-paperclip"></i>
                      <span>+ Nota</span>
                    </button>
                  </div>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>

  <!-- Galeri Foto & Dokumen Kendaraan -->
  <div class="glass-panel p-4 mb-4">
    <x-attachments :model="$selectedVehicle" />
  </div>
</div>
