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

<!-- Nav Tabs Bootstrap (5 Tab: Ringkasan, Jadwal, Inspeksi, Kerusakan, Dokumen) -->
<ul class="nav nav-tabs border-bottom border-slate-200 mb-4" id="vehicleTabs" role="tablist">
  <li class="nav-item" role="presentation">
    <button class="nav-link active fw-medium text-dark d-flex align-items-center gap-1.5 px-3 py-2.5" id="tab-v-ringkasan-btn" data-bs-toggle="tab" data-bs-target="#ringkasan" type="button" role="tab" aria-controls="ringkasan" aria-selected="true" style="min-height: 44px;">
      <i class="bi bi-speedometer2"></i>
      <span>Ringkasan</span>
    </button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link fw-medium text-secondary d-flex align-items-center gap-1.5 px-3 py-2.5" id="tab-v-jadwal-btn" data-bs-toggle="tab" data-bs-target="#jadwal" type="button" role="tab" aria-controls="jadwal" aria-selected="false" style="min-height: 44px;">
      <i class="bi bi-calendar-check"></i>
      <span>Jadwal</span>
      @if ($activeSchedules->isNotEmpty())
        <span class="badge bg-light text-dark border ms-1" style="font-size: 0.65rem;">{{ $activeSchedules->count() }}</span>
      @endif
    </button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link fw-medium text-secondary d-flex align-items-center gap-1.5 px-3 py-2.5" id="tab-v-inspeksi-btn" data-bs-toggle="tab" data-bs-target="#inspeksi" type="button" role="tab" aria-controls="inspeksi" aria-selected="false" style="min-height: 44px;">
      <i class="bi bi-clipboard-check"></i>
      <span>Inspeksi</span>
      @if ($selectedVehicle->inspectionRuns->isNotEmpty())
        <span class="badge bg-light text-dark border ms-1" style="font-size: 0.65rem;">{{ $selectedVehicle->inspectionRuns->count() }}</span>
      @endif
    </button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link fw-medium text-secondary d-flex align-items-center gap-1.5 px-3 py-2.5" id="tab-v-kerusakan-btn" data-bs-toggle="tab" data-bs-target="#kerusakan" type="button" role="tab" aria-controls="kerusakan" aria-selected="false" style="min-height: 44px;">
      <i class="bi bi-tools"></i>
      <span>Kerusakan</span>
      @php
        $openVehicleDamages = $selectedVehicle->damageReports->whereIn('status', ['dilaporkan', 'dalam_perbaikan']);
      @endphp
      @if ($openVehicleDamages->isNotEmpty())
        <span class="badge bg-danger text-white ms-1" style="font-size: 0.65rem;">{{ $openVehicleDamages->count() }}</span>
      @endif
    </button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link fw-medium text-secondary d-flex align-items-center gap-1.5 px-3 py-2.5" id="tab-v-dokumen-btn" data-bs-toggle="tab" data-bs-target="#dokumen" type="button" role="tab" aria-controls="dokumen" aria-selected="false" style="min-height: 44px;">
      <i class="bi bi-file-earmark-text"></i>
      <span>Dokumen</span>
      @php
        $activeVehicleDocs = $selectedVehicle->documents->whereNull('archived_at');
      @endphp
      @if ($activeVehicleDocs->isNotEmpty())
        <span class="badge bg-light text-dark border ms-1" style="font-size: 0.65rem;">{{ $activeVehicleDocs->count() }}</span>
      @endif
    </button>
  </li>
</ul>

<!-- Tab Contents -->
<div class="tab-content" id="vehicleTabContent">

  <!-- ================= TAB 1: RINGKASAN ================= -->
  <div class="tab-pane fade show active" id="ringkasan" role="tabpanel" aria-labelledby="tab-v-ringkasan-btn">
    <!-- Visual Kendaraan Besar di Tengah -->
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

  <!-- ================= TAB 2: JADWAL ================= -->
  <div class="tab-pane fade" id="jadwal" role="tabpanel" aria-labelledby="tab-v-jadwal-btn">
    <div class="glass-panel p-4 mb-4">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <h6 class="fw-bold text-dark mb-0">Jadwal Perawatan Berkala</h6>
          <span class="text-muted small">Agenda pemeliharaan berkala untuk {{ $selectedVehicle->name }}.</span>
        </div>
        <a href="{{ route('schedules.index', ['filter' => 'vehicle']) }}" class="btn btn-dark btn-sm px-3 d-inline-flex align-items-center gap-1.5" style="min-height: 40px; font-weight: 500;">
          <i class="bi bi-calendar-plus"></i>
          <span>Kelola di Jadwal</span>
        </a>
      </div>

      @if ($activeSchedules->isEmpty())
        <div class="p-4 text-center text-muted bg-white-50 rounded-3 border border-dashed border-slate-200">
          <i class="bi bi-calendar2-x d-block fs-3 mb-1 text-secondary"></i>
          <div class="fw-medium text-dark small">Belum ada jadwal perawatan aktif</div>
          <p class="text-muted mb-3" style="font-size: 0.78rem;">Buat pengingat ganti oli atau servis berkala berbasis kilometer dan waktu.</p>
          <a href="{{ route('schedules.index', ['filter' => 'vehicle']) }}" class="btn btn-dark btn-sm px-3" style="min-height: 44px; display: inline-flex; align-items: center;">
            <i class="bi bi-plus-lg me-1"></i> Buat Jadwal Baru
          </a>
        </div>
      @else
        <div class="d-flex flex-column gap-2.5">
          @foreach ($activeSchedules as $sch)
            <div class="p-3 rounded-3 bg-white border border-slate-200 d-flex flex-wrap justify-content-between align-items-center gap-2">
              <div>
                <div class="fw-bold text-dark">{{ $sch->title }}</div>
                <div class="text-muted small">
                  <i class="bi bi-clock me-1"></i>{{ $sch->trigger_label }}
                  @if ($sch->trigger_type === 'kilometer')
                    • Target: {{ number_format($sch->next_due_km) }} KM (Sisa: {{ number_format(max(0, (int)$sch->next_due_km - (int)$selectedVehicle->odometer)) }} KM)
                  @elseif ($sch->next_due_date)
                    • Jatuh tempo: {{ \Carbon\Carbon::parse($sch->next_due_date)->format('d/m/Y') }}
                  @endif
                </div>
                @if ($sch->notes)
                  <div class="text-muted small mt-1" style="font-size: 0.75rem;">{{ $sch->notes }}</div>
                @endif
              </div>
              <div class="d-flex align-items-center gap-2">
                <span class="chip {{ $sch->status_chip_class }} py-1 px-2">
                  <span class="chip-dot"></span> {{ ucfirst($sch->status) }}
                </span>
                <button class="btn btn-glass btn-sm px-3" data-bs-toggle="modal" data-bs-target="#modalCatatServis" style="min-height: 40px;">
                  <i class="bi bi-wrench me-1"></i> Catat Servis
                </button>
              </div>
            </div>
          @endforeach
        </div>
      @endif
    </div>
  </div>

  <!-- ================= TAB 3: INSPEKSI ================= -->
  <div class="tab-pane fade" id="inspeksi" role="tabpanel" aria-labelledby="tab-v-inspeksi-btn">
    <div class="glass-panel p-4 mb-4">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <h6 class="fw-bold text-dark mb-0">Inspeksi & Uji Kelayakan Kendaraan</h6>
          <span class="text-muted small">Checklist sebelum perjalanan jauh (ban, oli, rem, lampu, radiator, aki).</span>
        </div>
        <button class="btn btn-dark btn-sm px-3 d-inline-flex align-items-center gap-1.5" data-bs-toggle="modal" data-bs-target="#modalMulaiInspeksiKendaraan" style="min-height: 40px; font-weight: 500;">
          <i class="bi bi-clipboard-plus"></i>
          <span>Mulai Inspeksi</span>
        </button>
      </div>

      @if ($selectedVehicle->inspectionRuns->isEmpty())
        <div class="p-4 text-center text-muted bg-white-50 rounded-3 border border-dashed border-slate-200">
          <i class="bi bi-clipboard-x d-block fs-3 mb-1 text-secondary"></i>
          <div class="fw-medium text-dark small">Belum ada riwayat inspeksi kendaraan</div>
          <p class="text-muted mb-3" style="font-size: 0.78rem;">Lakukan inspeksi pra-perjalanan untuk memastikan keamanan seluruh komponen vital berkendara.</p>
          <button class="btn btn-dark btn-sm px-3" data-bs-toggle="modal" data-bs-target="#modalMulaiInspeksiKendaraan" style="min-height: 44px;">
            <i class="bi bi-clipboard-plus me-1"></i> Mulai Inspeksi Pertama
          </button>
        </div>
      @else
        <div class="d-flex flex-column gap-3">
          @foreach ($selectedVehicle->inspectionRuns as $run)
            <div class="p-3.5 rounded-3 bg-white border border-slate-200">
              <div class="d-flex flex-wrap justify-content-between align-items-center mb-2 pb-2 border-bottom border-slate-100 gap-2">
                <div>
                  <span class="badge {{ $run->status_badge_class }} py-0.5 px-2 me-2">
                    {{ $run->status_label }}
                  </span>
                  <strong class="text-dark">{{ $run->title }}</strong>
                </div>
                <div class="text-muted small font-monospace">
                  <i class="bi bi-calendar3 me-1"></i>{{ $run->inspected_at->format('d M Y') }}
                </div>
              </div>

              @if ($run->notes)
                <p class="text-muted small mb-2.5">{{ $run->notes }}</p>
              @endif

              <div class="list-group list-group-flush rounded-2 border">
                @foreach ($run->items as $item)
                  <div class="list-group-item d-flex flex-wrap justify-content-between align-items-center py-2 px-3 gap-2">
                    <div class="d-flex align-items-center gap-2">
                      <span class="badge {{ $item->status_badge_class }} px-2 py-0.5" style="font-size: 0.72rem;">
                        {{ $item->status_label }}
                      </span>
                      <span class="text-dark small fw-medium">{{ $item->item_label }}</span>
                      @if ($item->notes)
                        <span class="text-muted small">— {{ $item->notes }}</span>
                      @endif
                    </div>
                    @if (in_array($item->status, ['perhatian', 'bahaya']))
                      @if ($item->damage_report_id)
                        <span class="badge bg-secondary-subtle text-secondary border px-2 py-1" style="font-size: 0.72rem;">
                          <i class="bi bi-check-circle me-1"></i>Sudah Dikonversi
                        </span>
                      @else
                        <form action="{{ route('inspections.convert-damage', $item->id) }}" method="POST" class="m-0" onsubmit="return confirm('Konversikan temuan ini menjadi laporan kerusakan kendaraan?');">
                          @csrf
                          <button type="submit" class="btn btn-glass btn-sm py-1 px-2.5 text-danger d-inline-flex align-items-center gap-1" style="min-height: 36px; font-size: 0.75rem;">
                            <i class="bi bi-tools"></i>
                            <span>Jadikan Kerusakan</span>
                          </button>
                        </form>
                      @endif
                    @endif
                  </div>
                @endforeach
              </div>
            </div>
          @endforeach
        </div>
      @endif
    </div>
  </div>

  <!-- ================= TAB 4: KERUSAKAN ================= -->
  <div class="tab-pane fade" id="kerusakan" role="tabpanel" aria-labelledby="tab-v-kerusakan-btn">
    <div class="glass-panel p-4 mb-4">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <h6 class="fw-bold text-dark mb-0">Riwayat Kerusakan & Trouble Kendaraan</h6>
          <span class="text-muted small">Pencatatan kendala mesin, kelistrikan, bodi, dan perbaikan bengkel.</span>
        </div>
        <button class="btn btn-dark btn-sm px-3 d-inline-flex align-items-center gap-1.5" data-bs-toggle="modal" data-bs-target="#modalLaporKerusakanKendaraan" style="min-height: 40px; font-weight: 500;">
          <i class="bi bi-tools"></i>
          <span>Laporkan Kerusakan</span>
        </button>
      </div>

      @if ($selectedVehicle->damageReports->isEmpty())
        <div class="p-4 text-center text-muted bg-white-50 rounded-3 border border-dashed border-slate-200">
          <i class="bi bi-shield-check d-block fs-3 mb-1 text-success"></i>
          <div class="fw-medium text-dark small">Tidak ada laporan kerusakan aktif</div>
          <p class="text-muted mb-3" style="font-size: 0.78rem;">Kendaraan dalam kondisi fisik normal tanpa catatan kendala terbuka.</p>
          <button class="btn btn-glass btn-sm px-3" data-bs-toggle="modal" data-bs-target="#modalLaporKerusakanKendaraan" style="min-height: 44px;">
            <i class="bi bi-plus-lg me-1"></i> Catat Kerusakan Baru
          </button>
        </div>
      @else
        <div class="d-flex flex-column gap-3">
          @foreach ($selectedVehicle->damageReports as $report)
            <div class="p-3.5 rounded-3 bg-white border border-slate-200">
              <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                <div>
                  <div class="d-flex align-items-center gap-1.5 mb-1">
                    <span class="badge {{ $report->severity_badge_class }} px-2 py-0.5" style="font-size: 0.72rem;">
                      {{ $report->severity_label }}
                    </span>
                    <span class="badge {{ $report->status_badge_class }} px-2 py-0.5" style="font-size: 0.72rem;">
                      {{ $report->status_label }}
                    </span>
                  </div>
                  <h6 class="fw-bold text-dark mb-0.5">{{ $report->title }}</h6>
                  <small class="text-muted font-monospace" style="font-size: 0.75rem;">
                    Dilaporkan: {{ $report->reported_at?->format('d M Y') }}
                    @if ($report->resolved_at)
                      • Selesai: {{ $report->resolved_at->format('d M Y') }}
                    @endif
                  </small>
                </div>

                <div class="d-flex align-items-center gap-2">
                  @if ($report->status !== 'selesai')
                    <button class="btn btn-dark btn-sm px-3 btn-edit-damage-status" 
                            style="min-height: 38px; font-size: 0.78rem;"
                            data-bs-toggle="modal" 
                            data-bs-target="#modalStatusKerusakanKendaraan"
                            data-id="{{ $report->id }}"
                            data-title="{{ $report->title }}"
                            data-status="{{ $report->status }}"
                            data-tech="{{ $report->technician_name ?? '' }}"
                            data-cost="{{ $report->repair_cost ?? 0 }}"
                            data-notes="{{ $report->notes ?? '' }}">
                      <i class="bi bi-pencil-square me-1"></i> Update Status
                    </button>
                  @endif
                  <form action="{{ route('damage-reports.destroy', $report->id) }}" method="POST" onsubmit="return confirm('Hapus laporan kerusakan ini?');" class="m-0">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-glass btn-sm text-danger p-2" title="Hapus Laporan" style="min-height: 38px; min-width: 38px;">
                      <i class="bi bi-trash"></i>
                    </button>
                  </form>
                </div>
              </div>

              @if ($report->description)
                <p class="text-secondary small mb-2">{{ $report->description }}</p>
              @endif

              <div class="p-2.5 rounded-2 bg-light border small text-muted d-flex flex-wrap gap-3">
                <div>
                  <span class="text-muted d-block" style="font-size: 0.7rem;">Bengkel / Teknisi:</span>
                  <span class="fw-medium text-dark">{{ $report->technician_name ?: '-' }}</span>
                </div>
                <div>
                  <span class="text-muted d-block" style="font-size: 0.7rem;">Biaya Perbaikan:</span>
                  <span class="fw-medium text-dark">{{ $report->repair_cost ? 'Rp ' . number_format($report->repair_cost) : '-' }}</span>
                </div>
                @if ($report->notes)
                  <div class="w-100 mt-1 pt-1 border-top border-slate-200">
                    <span class="text-muted d-block" style="font-size: 0.7rem;">Catatan:</span>
                    <span class="text-dark">{{ $report->notes }}</span>
                  </div>
                @endif
              </div>

              <!-- Lampiran Foto / Nota Bukti Perbaikan -->
              @if ($report->attachments->isNotEmpty())
                <div class="mt-2.5 pt-2 border-top border-slate-100 d-flex flex-wrap gap-2">
                  @foreach ($report->attachments as $att)
                    <a href="{{ route('attachments.show', $att->id) }}" target="_blank" class="btn btn-glass btn-sm py-1 px-2.5 d-inline-flex align-items-center gap-1.5" style="min-height: 32px; font-size: 0.75rem;">
                      <i class="bi {{ str_contains($att->mime, 'pdf') ? 'bi-file-earmark-pdf text-danger' : 'bi-image text-primary' }}"></i>
                      <span>{{ Str::limit($att->original_name, 20) }}</span>
                    </a>
                  @endforeach
                </div>
              @endif
            </div>
          @endforeach
        </div>
      @endif
    </div>
  </div>

  <!-- ================= TAB 5: DOKUMEN ================= -->
  <div class="tab-pane fade" id="dokumen" role="tabpanel" aria-labelledby="tab-v-dokumen-btn">
    <div class="glass-panel p-4 mb-4">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <h6 class="fw-bold text-dark mb-0">Dokumen STNK, BPKB & Asuransi</h6>
          <span class="text-muted small">Kelola masa berlaku pajak tahunan, plat 5 tahunan, dan polis asuransi {{ $selectedVehicle->name }}.</span>
        </div>
        <button class="btn btn-dark btn-sm px-3 d-inline-flex align-items-center gap-1.5" data-bs-toggle="modal" data-bs-target="#modalTambahDokumenKendaraan" style="min-height: 40px; font-weight: 500;">
          <i class="bi bi-file-earmark-plus"></i>
          <span>Tambah Dokumen</span>
        </button>
      </div>

      <!-- Dokumen Aktif -->
      @if ($activeVehicleDocs->isEmpty())
        <div class="p-4 text-center text-muted bg-white-50 rounded-3 border border-dashed border-slate-200 mb-3">
          <i class="bi bi-file-earmark-x d-block fs-3 mb-1 text-secondary"></i>
          <div class="fw-medium text-dark small">Belum ada dokumen kendaraan tersimpan</div>
          <p class="text-muted mb-3" style="font-size: 0.78rem;">Simpan STNK atau polis asuransi untuk mendapatkan pengingat jatuh tempo otomatis.</p>
          <button class="btn btn-dark btn-sm px-3" data-bs-toggle="modal" data-bs-target="#modalTambahDokumenKendaraan" style="min-height: 44px;">
            <i class="bi bi-plus-lg me-1"></i> Tambah Dokumen Sekarang
          </button>
        </div>
      @else
        <div class="d-flex flex-column gap-2.5 mb-4">
          @foreach ($activeVehicleDocs as $doc)
            <div class="p-3 rounded-3 bg-white border border-slate-200 d-flex flex-wrap justify-content-between align-items-center gap-2">
              <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                  <span class="badge bg-light text-dark border px-2 py-0.5" style="font-size: 0.72rem;">
                    {{ $doc->type_label }}
                  </span>
                  @if ($doc->status)
                    <span class="chip {{ $doc->status_chip_class }} py-0.5 px-2">
                      <span class="chip-dot"></span> {{ $doc->status_label }}
                    </span>
                  @endif
                </div>
                <div class="fw-bold text-dark">{{ $doc->title }}</div>
                <div class="text-muted small">
                  @if ($doc->reference_number)
                    <span class="font-monospace me-2">No: {{ $doc->reference_number }}</span>
                  @endif
                  @if ($doc->expires_at)
                    <span>• Jatuh tempo: {{ $doc->expires_at->format('d/m/Y') }} ({{ $doc->due_label }})</span>
                  @endif
                </div>
                @if ($doc->notes)
                  <div class="text-muted small mt-1" style="font-size: 0.75rem;">{{ $doc->notes }}</div>
                @endif
              </div>

              <div class="d-flex align-items-center gap-2">
                @if ($doc->attachments->isNotEmpty())
                  <a href="{{ route('attachments.show', $doc->attachments->first()->id) }}" target="_blank" class="btn btn-glass btn-sm px-2.5" style="min-height: 38px;" title="Lihat Berkas Lampiran">
                    <i class="bi {{ str_contains($doc->attachments->first()->mime, 'pdf') ? 'bi-file-earmark-pdf text-danger' : 'bi-image' }} me-1"></i> Berkas
                  </a>
                @endif
                <button class="btn btn-dark btn-sm px-3 btn-renew-document" 
                        style="min-height: 38px; font-size: 0.78rem;"
                        data-bs-toggle="modal" 
                        data-bs-target="#modalPerpanjangDokumenKendaraan"
                        data-id="{{ $doc->id }}"
                        data-title="{{ $doc->title }}"
                        data-cost="{{ $doc->cost ?? 0 }}"
                        data-ref="{{ $doc->reference_number ?? '' }}">
                  <i class="bi bi-arrow-repeat me-1"></i> Perpanjang
                </button>
                <form action="{{ route('documents.destroy', $doc->id) }}" method="POST" onsubmit="return confirm('Hapus dokumen ini?');" class="m-0">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="btn btn-glass btn-sm text-danger p-2" title="Hapus Dokumen" style="min-height: 38px; min-width: 38px;">
                    <i class="bi bi-trash"></i>
                  </button>
                </form>
              </div>
            </div>
          @endforeach
        </div>
      @endif

      <!-- Collapsible Riwayat Dokumen Terarsip -->
      @php
        $archivedVehicleDocs = $selectedVehicle->documents->whereNotNull('archived_at');
      @endphp
      @if ($archivedVehicleDocs->isNotEmpty())
        <div class="pt-3 border-top border-slate-200">
          <button class="btn btn-link text-decoration-none text-secondary p-0 small fw-semibold d-flex align-items-center gap-1.5" type="button" data-bs-toggle="collapse" data-bs-target="#collapseArchivedVehicleDocs" aria-expanded="false">
            <i class="bi bi-archive"></i>
            <span>Lihat Riwayat Dokumen Terarsip ({{ $archivedVehicleDocs->count() }})</span>
            <i class="bi bi-chevron-down small"></i>
          </button>
          
          <div class="collapse mt-3" id="collapseArchivedVehicleDocs">
            <div class="d-flex flex-column gap-2">
              @foreach ($archivedVehicleDocs as $arc)
                <div class="p-2.5 rounded-2 bg-light border text-muted small d-flex justify-content-between align-items-center">
                  <div>
                    <span class="fw-medium text-dark">{{ $arc->title }}</span>
                    <span class="text-muted font-monospace ms-2">No: {{ $arc->reference_number ?: '-' }}</span>
                    <div class="text-secondary" style="font-size: 0.72rem;">
                      Diarsipkan: {{ $arc->archived_at->format('d/m/Y') }} • Biaya lama: Rp {{ number_format($arc->cost ?? 0) }}
                    </div>
                  </div>
                  @if ($arc->attachments->isNotEmpty())
                    <a href="{{ route('attachments.show', $arc->attachments->first()->id) }}" target="_blank" class="btn btn-glass btn-sm py-1 px-2" style="font-size: 0.72rem;">
                      <i class="bi bi-paperclip"></i> Berkas
                    </a>
                  @endif
                </div>
              @endforeach
            </div>
          </div>
        </div>
      @endif
    </div>
  </div>

</div>
@endif
@endsection

@section('modals')
@if ($selectedVehicle)
  <!-- ================= MODAL: UPDATE ODOMETER ================= -->
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
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content glass-panel border-0 shadow-lg">
        <form action="{{ route('vehicles.service-logs.store', $selectedVehicle->id) }}" method="POST">
          @csrf
          <div class="modal-header border-bottom border-slate-200 py-3">
            <h6 class="modal-title fw-bold text-dark">Catat Servis — {{ $selectedVehicle->name }}</h6>
            <button type="button" class="btn-close small" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body py-3">
            <!-- Baris 1: Tanggal & Odometer -->
            <div class="row g-3 mb-3">
              <div class="col-md-6">
                <label class="form-label text-muted small mb-1">Tanggal Servis <span class="text-danger">*</span></label>
                <input type="date" name="done_at" class="form-control" value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}" required>
              </div>
              <div class="col-md-6">
                <label class="form-label text-muted small mb-1">Odometer Saat Servis (KM) <span class="text-danger">*</span></label>
                <input type="number" 
                       name="odometer" 
                       class="form-control font-monospace fw-semibold" 
                       value="{{ $selectedVehicle->odometer }}" 
                       min="0" 
                       required>
                <small class="text-muted" style="font-size: 0.72rem;">Sesuai angka odometer pada tanggal pelaksanaan servis.</small>
              </div>
            </div>

            <!-- Baris 2: Jenis Servis & Nama Bengkel -->
            <div class="row g-3 mb-3">
              <div class="col-md-6">
                <label class="form-label text-muted small mb-1">Jenis Servis <span class="text-danger">*</span></label>
                <select name="service_type" class="form-select" required>
                  <option value="Ganti Oli">Ganti Oli</option>
                  <option value="Servis Berkala" selected>Servis Berkala</option>
                  <option value="Perbaikan">Perbaikan</option>
                  <option value="Ganti Ban">Ganti Ban</option>
                  <option value="Lainnya">Lainnya</option>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label text-muted small mb-1">Nama Bengkel Pelaksana</label>
                <input type="text" name="workshop_name" class="form-control" value="{{ $selectedVehicle->workshop }}" placeholder="Contoh: Auto2000, AHASS">
              </div>
            </div>

            <!-- Baris 3: Tandai Jadwal Selesai -->
            <div class="mb-3">
              <label class="form-label text-muted small mb-1">Tandai Jadwal Selesai (Opsional)</label>
              <select name="schedule_id" class="form-select">
                <option value="">-- Tidak Ada / Servis Mandiri Tanpa Jadwal --</option>
                @foreach ($activeSchedules as $sch)
                  <option value="{{ $sch->id }}">
                    {{ $sch->title }} ({{ $sch->trigger_label }}) — Status: {{ ucfirst($sch->status) }}
                  </option>
                @endforeach
              </select>
              <small class="text-muted" style="font-size: 0.72rem;">Jika dipilih, siklus jadwal terkait akan otomatis diperbarui.</small>
            </div>

            <!-- Baris 4: Biaya Jasa & Suku Cadang + Total -->
            <div class="p-3 rounded-3 bg-light border mb-3">
              <div class="row g-2 align-items-center">
                <div class="col-md-4">
                  <label class="form-label text-muted small mb-1">Biaya Jasa (Rp)</label>
                  <input type="number" id="servisLaborCost" name="labor_cost" class="form-control" value="0" min="0" step="1000">
                </div>
                <div class="col-md-4">
                  <label class="form-label text-muted small mb-1">Biaya Suku Cadang (Rp)</label>
                  <input type="number" id="servisPartsCost" name="parts_cost" class="form-control" value="0" min="0" step="1000">
                </div>
                <div class="col-md-4 text-md-end pt-2 pt-md-0">
                  <small class="text-muted d-block" style="font-size: 0.72rem;">Total Biaya Perawatan:</small>
                  <div class="fw-bold fs-5 text-dark" id="servisTotalCost">Rp 0</div>
                </div>
              </div>
            </div>

            <!-- Baris 5: Suku Cadang Diganti & Catatan -->
            <div class="mb-3">
              <label class="form-label text-muted small mb-1">Suku Cadang yang Diganti</label>
              <textarea name="parts" class="form-control" rows="2" placeholder="Contoh: Oli Mesin SPX2 0.8L, Busi CPR9EA, Filter Oli"></textarea>
            </div>
            <div class="mb-2">
              <label class="form-label text-muted small mb-1">Catatan Tambahan</label>
              <textarea name="notes" class="form-control" rows="2" placeholder="Catatan kondisi kendaraan, rekomendasi servis berikutnya, atau garansi bengkel..."></textarea>
            </div>
          </div>
          <div class="modal-footer border-top border-slate-200 py-2.5">
            <button type="button" class="btn btn-glass btn-sm" data-bs-dismiss="modal" style="min-height: 44px; padding: 0.4rem 1.2rem;">Batal</button>
            <button type="submit" class="btn btn-dark btn-sm px-4" style="min-height: 44px;">Simpan Catatan Servis</button>
          </div>
        </form>
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

  <!-- ================= MODAL: UPLOAD NOTA SERVIS ================= -->
  <div class="modal fade" id="modalUploadNotaServis" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content glass-panel border-0 shadow-lg">
        <form action="{{ route('attachments.store') }}" method="POST" enctype="multipart/form-data">
          @csrf
          <input type="hidden" name="attachable_type" value="App\Models\MaintenanceRecord">
          <input type="hidden" name="attachable_id" id="inputNotaRecordId" value="">

          <div class="modal-header border-bottom border-slate-200 py-3">
            <h6 class="modal-title fw-bold text-dark" id="modalTitleUploadNota">Lampirkan Nota Servis</h6>
            <button type="button" class="btn-close small" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>

          <div class="modal-body py-3">
            <div class="p-2.5 rounded-3 bg-light border mb-3">
              <small class="text-muted d-block" style="font-size: 0.72rem;">Catatan Servis Terkait:</small>
              <div class="fw-bold text-dark" id="labelNotaRecordTitle">-</div>
            </div>

            <div class="mb-2">
              <label class="form-label text-muted small mb-1">Pilih Berkas Nota (Foto atau PDF, Maks 5 MB) <span class="text-danger">*</span></label>
              <input type="file" name="file" class="form-control" accept="image/*,application/pdf" required style="min-height: 44px;">
              <small class="text-muted" style="font-size: 0.72rem;">Berkas disimpan secara privat dan hanya dapat diakses oleh Anda.</small>
            </div>
          </div>

          <div class="modal-footer border-top border-slate-200 py-2.5">
            <button type="button" class="btn btn-glass btn-sm" data-bs-dismiss="modal" style="min-height: 44px; padding: 0.4rem 1.2rem;">Batal</button>
            <button type="submit" class="btn btn-dark btn-sm px-4" style="min-height: 44px;">Upload Nota</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- ================= MODAL: MULAI INSPEKSI KENDARAAN ================= -->
  <div class="modal fade" id="modalMulaiInspeksiKendaraan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content glass-panel border-0 shadow-lg">
        <form action="{{ route('inspections.store') }}" method="POST">
          @csrf
          <input type="hidden" name="inspectable_type" value="{{ get_class($selectedVehicle) }}">
          <input type="hidden" name="inspectable_id" value="{{ $selectedVehicle->id }}">
          <input type="hidden" name="template_key" value="kendaraan_pra_jalan">

          <div class="modal-header border-bottom border-slate-200 py-3">
            <h6 class="modal-title fw-bold text-dark">Inspeksi Pra-Perjalanan — {{ $selectedVehicle->name }}</h6>
            <button type="button" class="btn-close small" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>

          <div class="modal-body py-3">
            <div class="row g-2 mb-3">
              <div class="col-md-6">
                <label class="form-label text-muted small mb-1">Judul Pemeriksaan <span class="text-danger">*</span></label>
                <input type="text" name="title" class="form-control" value="Inspeksi Pra-Perjalanan Kendaraan" required style="min-height: 44px;">
              </div>
              <div class="col-md-6">
                <label class="form-label text-muted small mb-1">Tanggal Inspeksi <span class="text-danger">*</span></label>
                <input type="date" name="inspected_at" class="form-control" value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}" required style="min-height: 44px;">
              </div>
            </div>

            <!-- Vehicle Pra-Jalan Checklist -->
            <div class="mb-3">
              <label class="form-label text-muted small mb-1">Checklist 6 Titik Komponen Vital</label>
              <div class="d-flex flex-column gap-2">
                @php
                  $defaultVehicleItems = $inspectionTemplates['kendaraan_pra_jalan']['items'] ?? [
                      'Tekanan & Ketebalan Alur Ban',
                      'Ketinggian & Kondisi Oli Mesin',
                      'Fungsi Rem & Level Minyak Rem',
                      'Lampu Utama, Lampu Rem, & Sein',
                      'Air Radiator, Cadangan Wiper, & Karet Wiper',
                      'Indikator Aki & Kelistrikan Starter',
                  ];
                @endphp
                @foreach ($defaultVehicleItems as $idx => $itemText)
                  <div class="p-2.5 rounded-2 bg-light border d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div class="fw-medium text-dark small" style="flex: 1 1 200px;">
                      {{ $itemText }}
                      <input type="hidden" name="items[{{ $idx }}][label]" value="{{ $itemText }}">
                    </div>
                    <div class="d-flex align-items-center gap-1.5" style="flex: 0 0 auto;">
                      <select name="items[{{ $idx }}][status]" class="form-select form-select-sm" style="min-height: 38px; width: 140px;">
                        <option value="ok" selected>Kondisi OK</option>
                        <option value="perhatian">Perhatian</option>
                        <option value="bahaya">Bahaya</option>
                      </select>
                      <input type="text" name="items[{{ $idx }}][notes]" class="form-control form-control-sm" placeholder="Catatan (opsional)" style="min-height: 38px; width: 150px;">
                    </div>
                  </div>
                @endforeach
              </div>
            </div>

            <div class="mb-2">
              <label class="form-label text-muted small mb-1">Catatan Tambahan</label>
              <textarea name="notes" class="form-control" rows="2" placeholder="Catatan rute perjalanan atau rekomendasi servis..."></textarea>
            </div>
          </div>

          <div class="modal-footer border-top border-slate-200 py-2.5">
            <button type="button" class="btn btn-glass btn-sm" data-bs-dismiss="modal" style="min-height: 44px; padding: 0.4rem 1.2rem;">Batal</button>
            <button type="submit" class="btn btn-dark btn-sm px-4" style="min-height: 44px;">Simpan Hasil Inspeksi</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- ================= MODAL: LAPOR KERUSAKAN KENDARAAN ================= -->
  <div class="modal fade" id="modalLaporKerusakanKendaraan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content glass-panel border-0 shadow-lg">
        <form action="{{ route('damage-reports.store') }}" method="POST" enctype="multipart/form-data">
          @csrf
          <input type="hidden" name="reportable_type" value="{{ get_class($selectedVehicle) }}">
          <input type="hidden" name="reportable_id" value="{{ $selectedVehicle->id }}">

          <div class="modal-header border-bottom border-slate-200 py-3">
            <h6 class="modal-title fw-bold text-dark">Laporkan Kerusakan — {{ $selectedVehicle->name }}</h6>
            <button type="button" class="btn-close small" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>

          <div class="modal-body py-3">
            <div class="mb-3">
              <label class="form-label text-muted small mb-1">Judul Kendala / Kerusakan <span class="text-danger">*</span></label>
              <input type="text" name="title" class="form-control" placeholder="Contoh: Bunyi decit saat rem mendadak" required style="min-height: 44px;">
            </div>

            <div class="row g-2 mb-3">
              <div class="col-6">
                <label class="form-label text-muted small mb-1">Tingkat Keparahan <span class="text-danger">*</span></label>
                <select name="severity" class="form-select" required style="min-height: 44px;">
                  <option value="ringan">Ringan (Baret / Lecet)</option>
                  <option value="sedang" selected>Sedang (Bunyi / Getar)</option>
                  <option value="berat">Berat (Mogok / Rem Blong)</option>
                </select>
              </div>
              <div class="col-6">
                <label class="form-label text-muted small mb-1">Tanggal Diketahui <span class="text-danger">*</span></label>
                <input type="date" name="reported_at" class="form-control" value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}" required style="min-height: 44px;">
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label text-muted small mb-1">Deskripsi Gangguan</label>
              <textarea name="description" class="form-control" rows="2" placeholder="Jelaskan kronologi, indikator speedometer, atau gejala saat melaju..."></textarea>
            </div>

            <div class="mb-2">
              <label class="form-label text-muted small mb-1">Foto Bukti / Video Kerusakan (Maks 5 MB)</label>
              <input type="file" name="photo" class="form-control" accept="image/*,application/pdf" style="min-height: 44px;">
            </div>
          </div>

          <div class="modal-footer border-top border-slate-200 py-2.5">
            <button type="button" class="btn btn-glass btn-sm" data-bs-dismiss="modal" style="min-height: 44px; padding: 0.4rem 1.2rem;">Batal</button>
            <button type="submit" class="btn btn-dark btn-sm px-4" style="min-height: 44px;">Catat Laporan</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- ================= MODAL: UPDATE STATUS KERUSAKAN KENDARAAN ================= -->
  <div class="modal fade" id="modalStatusKerusakanKendaraan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content glass-panel border-0 shadow-lg">
        <form action="" method="POST" id="formStatusKerusakanKendaraan" enctype="multipart/form-data">
          @csrf
          @method('PUT')

          <div class="modal-header border-bottom border-slate-200 py-3">
            <h6 class="modal-title fw-bold text-dark" id="modalTitleStatusKerusakanKendaraan">Update Status Perbaikan</h6>
            <button type="button" class="btn-close small" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>

          <div class="modal-body py-3">
            <div class="row g-2 mb-3">
              <div class="col-6">
                <label class="form-label text-muted small mb-1">Status Perbaikan <span class="text-danger">*</span></label>
                <select name="status" id="inputStatusDamageKendaraan" class="form-select" required style="min-height: 44px;">
                  <option value="dilaporkan">Dilaporkan</option>
                  <option value="dalam_perbaikan">Dalam Perbaikan</option>
                  <option value="selesai">Selesai Diperbaiki</option>
                </select>
              </div>
              <div class="col-6">
                <label class="form-label text-muted small mb-1">Tanggal Selesai</label>
                <input type="date" name="resolved_at" id="inputStatusResolvedAtKendaraan" class="form-control" value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}" style="min-height: 44px;">
              </div>
            </div>

            <div class="row g-2 mb-3">
              <div class="col-6">
                <label class="form-label text-muted small mb-1">Bengkel / Teknisi</label>
                <input type="text" name="technician_name" id="inputStatusTechKendaraan" class="form-control" placeholder="Nama bengkel / mekanik" style="min-height: 44px;">
              </div>
              <div class="col-6">
                <label class="form-label text-muted small mb-1">Biaya Perbaikan (Rp)</label>
                <input type="number" name="repair_cost" id="inputStatusCostKendaraan" class="form-control" value="0" min="0" step="5000" style="min-height: 44px;">
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label text-muted small mb-1">Catatan Hasil Pengerjaan</label>
              <textarea name="notes" id="inputStatusNotesKendaraan" class="form-control" rows="2" placeholder="Suku cadang yang diganti, garansi bengkel..."></textarea>
            </div>

            <div class="mb-2">
              <label class="form-label text-muted small mb-1">Nota / Bukti Servis Bengkel (Opsional)</label>
              <input type="file" name="receipt" class="form-control" accept="image/*,application/pdf" style="min-height: 44px;">
            </div>
          </div>

          <div class="modal-footer border-top border-slate-200 py-2.5">
            <button type="button" class="btn btn-glass btn-sm" data-bs-dismiss="modal" style="min-height: 44px; padding: 0.4rem 1.2rem;">Batal</button>
            <button type="submit" class="btn btn-dark btn-sm px-4" style="min-height: 44px;">Perbarui Status</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- ================= MODAL: TAMBAH DOKUMEN KENDARAAN ================= -->
  <div class="modal fade" id="modalTambahDokumenKendaraan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content glass-panel border-0 shadow-lg">
        <form action="{{ route('documents.store') }}" method="POST" enctype="multipart/form-data">
          @csrf
          <input type="hidden" name="subject_type" value="{{ get_class($selectedVehicle) }}">
          <input type="hidden" name="subject_id" value="{{ $selectedVehicle->id }}">

          <div class="modal-header border-bottom border-slate-200 py-3">
            <h6 class="modal-title fw-bold text-dark">Tambah Dokumen — {{ $selectedVehicle->name }}</h6>
            <button type="button" class="btn-close small" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>

          <div class="modal-body py-3">
            <div class="row g-2 mb-3">
              <div class="col-6">
                <label class="form-label text-muted small mb-1">Tipe Dokumen <span class="text-danger">*</span></label>
                <select name="type" class="form-select" required style="min-height: 44px;">
                  @foreach ($documentTypes as $typeKey => $typeLabel)
                    <option value="{{ $typeKey }}" {{ $typeKey === 'stnk_pajak' ? 'selected' : '' }}>{{ $typeLabel }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-6">
                <label class="form-label text-muted small mb-1">Nomor Registrasi / Polis</label>
                <input type="text" name="reference_number" class="form-control" placeholder="Contoh: No. STNK / No. Polis" style="min-height: 44px;">
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label text-muted small mb-1">Judul Dokumen <span class="text-danger">*</span></label>
              <input type="text" name="title" class="form-control" placeholder="Contoh: Pajak STNK Tahunan / Asuransi TLO" required style="min-height: 44px;">
            </div>

            <div class="row g-2 mb-3">
              <div class="col-6">
                <label class="form-label text-muted small mb-1">Tanggal Terbit</label>
                <input type="date" name="issued_at" class="form-control" value="{{ date('Y-m-d') }}" style="min-height: 44px;">
              </div>
              <div class="col-6">
                <label class="form-label text-muted small mb-1">Tanggal Jatuh Tempo / Habis</label>
                <input type="date" name="expires_at" class="form-control" style="min-height: 44px;">
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label text-muted small mb-1">Biaya / Pokok PKB (Rp)</label>
              <input type="number" name="cost" class="form-control" value="0" min="0" step="1000" style="min-height: 44px;">
            </div>

            <div class="mb-3">
              <label class="form-label text-muted small mb-1">Catatan Tambahan</label>
              <textarea name="notes" class="form-control" rows="2" placeholder="Samsat tujuan, no kontak agen asuransi..."></textarea>
            </div>

            <div class="mb-2">
              <label class="form-label text-muted small mb-1">Foto STNK / Berkas Polis (Maks 5 MB)</label>
              <input type="file" name="file" class="form-control" accept="image/*,application/pdf" style="min-height: 44px;">
            </div>
          </div>

          <div class="modal-footer border-top border-slate-200 py-2.5">
            <button type="button" class="btn btn-glass btn-sm" data-bs-dismiss="modal" style="min-height: 44px; padding: 0.4rem 1.2rem;">Batal</button>
            <button type="submit" class="btn btn-dark btn-sm px-4" style="min-height: 44px;">Simpan Dokumen</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- ================= MODAL: PERPANJANG DOKUMEN KENDARAAN ================= -->
  <div class="modal fade" id="modalPerpanjangDokumenKendaraan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content glass-panel border-0 shadow-lg">
        <form action="" method="POST" id="formPerpanjangDokumenKendaraan" enctype="multipart/form-data">
          @csrf
          <div class="modal-header border-bottom border-slate-200 py-3">
            <h6 class="modal-title fw-bold text-dark" id="modalTitlePerpanjangKendaraan">Perpanjang Dokumen</h6>
            <button type="button" class="btn-close small" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>

          <div class="modal-body py-3">
            <div class="p-2.5 rounded-3 bg-light border mb-3">
              <small class="text-muted d-block" style="font-size: 0.72rem;">Dokumen yang Diperpanjang:</small>
              <div class="fw-bold text-dark" id="labelPerpanjangTitleKendaraan">-</div>
              <small class="text-muted" style="font-size: 0.72rem;">Dokumen lama otomatis diarsipkan ke riwayat kepemilikan.</small>
            </div>

            <div class="row g-2 mb-3">
              <div class="col-6">
                <label class="form-label text-muted small mb-1">Tanggal Terbit Baru</label>
                <input type="date" name="issued_at" class="form-control" value="{{ date('Y-m-d') }}" style="min-height: 44px;">
              </div>
              <div class="col-6">
                <label class="form-label text-muted small mb-1">Jatuh Tempo Baru <span class="text-danger">*</span></label>
                <input type="date" name="expires_at" class="form-control" required style="min-height: 44px;">
              </div>
            </div>

            <div class="row g-2 mb-3">
              <div class="col-6">
                <label class="form-label text-muted small mb-1">Nomor Polis / Bukti Baru</label>
                <input type="text" name="reference_number" id="inputPerpanjangRefKendaraan" class="form-control" style="min-height: 44px;">
              </div>
              <div class="col-6">
                <label class="form-label text-muted small mb-1">Biaya Bayar (Rp)</label>
                <input type="number" name="cost" id="inputPerpanjangCostKendaraan" class="form-control" value="0" min="0" step="1000" style="min-height: 44px;">
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label text-muted small mb-1">Catatan Tambahan</label>
              <textarea name="notes" class="form-control" rows="2" placeholder="Catatan pembayaran pajak / perpanjangan asuransi..."></textarea>
            </div>

            <div class="mb-2">
              <label class="form-label text-muted small mb-1">Unggah Lembar Pajak Baru (PDF / Foto)</label>
              <input type="file" name="file" class="form-control" accept="image/*,application/pdf" style="min-height: 44px;">
            </div>
          </div>

          <div class="modal-footer border-top border-slate-200 py-2.5">
            <button type="button" class="btn btn-glass btn-sm" data-bs-dismiss="modal" style="min-height: 44px; padding: 0.4rem 1.2rem;">Batal</button>
            <button type="submit" class="btn btn-dark btn-sm px-4" style="min-height: 44px;">Perpanjang & Arsipkan Lama</button>
          </div>
        </form>
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
            <select class="form-select" id="selectBrand" required style="min-height: 44px;">
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
            <select class="form-select" id="selectModel" name="vehicle_model_id" required style="min-height: 44px;">
              <option value="" disabled selected>Pilih merek terlebih dahulu...</option>
            </select>
          </div>

          <!-- Form Tambahan Khusus "Lainnya" -->
          <div id="wrapperOtherModel" class="p-3 rounded-3 bg-light border mb-3 d-none">
            <div class="mb-2">
              <label class="form-label text-muted small mb-1">Nama Model Lengkap <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="custom_model_name" placeholder="Misal: Vespa Primavera 150" style="min-height: 44px;">
            </div>
            <div>
              <label class="form-label text-muted small mb-1">Jenis Bodi <span class="text-danger">*</span></label>
              <select class="form-select" name="custom_body_type" style="min-height: 44px;">
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
              <input type="text" class="form-control font-monospace text-uppercase" name="plate" placeholder="B 1234 ABC" required style="min-height: 44px;">
            </div>
            <div class="col-5">
              <label class="form-label text-muted small mb-1">Tahun Perakitan</label>
              <input type="number" class="form-control" name="year" placeholder="{{ date('Y') }}" min="1970" max="{{ date('Y') + 1 }}" style="min-height: 44px;">
            </div>
          </div>

          <!-- Odometer & Bahan Bakar -->
          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label text-muted small mb-1">Odometer Awal (KM) <span class="text-danger">*</span></label>
              <input type="number" class="form-control" name="odometer" placeholder="0" min="0" required style="min-height: 44px;">
            </div>
            <div class="col-6">
              <label class="form-label text-muted small mb-1">Bahan Bakar <span class="text-danger">*</span></label>
              <select class="form-select" name="fuel_type" required style="min-height: 44px;">
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
              <select class="form-select" name="color_key" required style="min-height: 44px;">
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
              <input type="text" class="form-control" name="workshop" placeholder="Opsional (misal: AHASS)" style="min-height: 44px;">
            </div>
          </div>
        </div>

        <div class="modal-footer border-top border-slate-200 py-2.5">
          <button type="button" class="btn btn-glass btn-sm" data-bs-dismiss="modal" style="min-height: 44px; padding: 0.4rem 1.2rem;">Batal</button>
          <button type="submit" class="btn btn-dark btn-sm px-4" style="min-height: 44px;">Simpan ke Garasi</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  // 1. Sync Active Tab with URL Hash
  function initVehicleTabHashSync() {
    const hash = window.location.hash;
    if (hash) {
      const targetBtn = document.querySelector(`.nav-tabs button[data-bs-target="${hash}"]`);
      if (targetBtn) {
        bootstrap.Tab.getOrCreateInstance(targetBtn).show();
      }
    }

    document.querySelectorAll('.nav-tabs button[data-bs-toggle="tab"]').forEach(btn => {
      btn.addEventListener('shown.bs.tab', e => {
        const target = e.target.getAttribute('data-bs-target');
        if (history.replaceState) {
          history.replaceState(null, null, target);
        } else {
          window.location.hash = target;
        }
      });
    });
  }

  // 2. Cascading Select for Models Catalog
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

  // 3. Real-time color modification for GLB model-viewer
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

  // 4. Real-time calculation of total service cost
  function updateServisTotal() {
    const labor = parseInt(document.getElementById('servisLaborCost')?.value || 0, 10) || 0;
    const parts = parseInt(document.getElementById('servisPartsCost')?.value || 0, 10) || 0;
    const total = labor + parts;
    const display = document.getElementById('servisTotalCost');
    if (display) {
      display.textContent = 'Rp ' + total.toLocaleString('id-ID');
    }
  }

  const laborInput = document.getElementById('servisLaborCost');
  const partsInput = document.getElementById('servisPartsCost');
  if (laborInput) {
    laborInput.addEventListener('input', updateServisTotal);
  }
  if (partsInput) {
    partsInput.addEventListener('input', updateServisTotal);
  }

  // 5. Wire Record Nota Upload Modal
  document.querySelectorAll('.btn-upload-record-nota').forEach(btn => {
    btn.addEventListener('click', function() {
      const id = this.getAttribute('data-id');
      const title = this.getAttribute('data-title');
      document.getElementById('inputNotaRecordId').value = id;
      document.getElementById('labelNotaRecordTitle').textContent = title;
    });
  });

  // 6. Wire Damage Status Modal for Vehicle
  document.querySelectorAll('.btn-edit-damage-status').forEach(btn => {
    btn.addEventListener('click', function() {
      const id = this.getAttribute('data-id');
      const title = this.getAttribute('data-title');
      const status = this.getAttribute('data-status');
      const tech = this.getAttribute('data-tech');
      const cost = this.getAttribute('data-cost');
      const notes = this.getAttribute('data-notes');

      const form = document.getElementById('formStatusKerusakanKendaraan');
      if (form) {
        form.action = '/damage-reports/' + id + '/status';
        document.getElementById('modalTitleStatusKerusakanKendaraan').textContent = 'Update Status: ' + title;
        document.getElementById('inputStatusDamageKendaraan').value = status;
        document.getElementById('inputStatusTechKendaraan').value = tech;
        document.getElementById('inputStatusCostKendaraan').value = cost;
        document.getElementById('inputStatusNotesKendaraan').value = notes;
      }
    });
  });

  // 7. Wire Renew Document Modal for Vehicle
  document.querySelectorAll('.btn-renew-document').forEach(btn => {
    btn.addEventListener('click', function() {
      const id = this.getAttribute('data-id');
      const title = this.getAttribute('data-title');
      const cost = this.getAttribute('data-cost');
      const ref = this.getAttribute('data-ref');

      const form = document.getElementById('formPerpanjangDokumenKendaraan');
      if (form) {
        form.action = '/documents/' + id + '/renew';
        document.getElementById('labelPerpanjangTitleKendaraan').textContent = title;
        document.getElementById('inputPerpanjangCostKendaraan').value = cost;
        document.getElementById('inputPerpanjangRefKendaraan').value = ref;
      }
    });
  });

  document.addEventListener('DOMContentLoaded', function() {
    initVehicleTabHashSync();
  });
</script>
@endpush
