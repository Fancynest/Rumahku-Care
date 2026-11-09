@extends('layouts.app')

@section('title', 'Jadwal & Pengingat - Rumahku Care')

@section('header-action')
<div class="d-flex align-items-center gap-2">
  <button class="btn btn-glass btn-sm d-flex align-items-center gap-1.5" data-bs-toggle="modal" data-bs-target="#modalTemplateJadwal" style="min-height: 40px; padding: 0.4rem 1rem;">
    <i class="bi bi-collection"></i>
    <span>Mulai dari Template</span>
  </button>
  <button class="btn btn-dark btn-sm d-flex align-items-center gap-1.5" data-bs-toggle="modal" data-bs-target="#modalBuatJadwal" style="min-height: 40px; padding: 0.4rem 1rem;">
    <i class="bi bi-plus-lg"></i>
    <span>+ Buat Jadwal</span>
  </button>
</div>
@endsection

@section('content')
<!-- Header & Filter -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
  <div>
    <h5 class="fw-bold text-dark mb-0" style="letter-spacing: -0.02em;">Jadwal Perawatan & Pengingat</h5>
    <span class="text-muted small">Kelola siklus pemeliharaan berkala untuk kendaraan dan peralatan rumah Anda.</span>
  </div>

  <!-- Filter Pills -->
  <div class="d-flex align-items-center gap-1 bg-white p-1 rounded-pill border shadow-xs">
    <a href="{{ route('schedules.index', ['filter' => 'all']) }}" 
       class="btn btn-sm rounded-pill px-3 py-1 text-decoration-none {{ $filter === 'all' ? 'btn-dark' : 'text-muted' }}" 
       style="font-size: 0.8rem; font-weight: 500;">
      Semua
    </a>
    <a href="{{ route('schedules.index', ['filter' => 'home_asset']) }}" 
       class="btn btn-sm rounded-pill px-3 py-1 text-decoration-none {{ $filter === 'home_asset' ? 'btn-dark' : 'text-muted' }}" 
       style="font-size: 0.8rem; font-weight: 500;">
      <i class="bi bi-house-door me-1"></i>Rumah
    </a>
    <a href="{{ route('schedules.index', ['filter' => 'vehicle']) }}" 
       class="btn btn-sm rounded-pill px-3 py-1 text-decoration-none {{ $filter === 'vehicle' ? 'btn-dark' : 'text-muted' }}" 
       style="font-size: 0.8rem; font-weight: 500;">
      <i class="bi bi-car-front me-1"></i>Kendaraan
    </a>
  </div>
</div>

@php
  $totalSchedules = $grouped['terlambat']->count() + $grouped['mendesak']->count() + $grouped['perhatian']->count() + $grouped['normal']->count();
  $groupMeta = [
    'terlambat' => [
      'label' => 'Terlambat (Segera Lakukan Perawatan)',
      'chip' => 'chip-danger',
      'icon' => 'bi-exclamation-octagon-fill text-danger',
    ],
    'mendesak' => [
      'label' => 'Mendesak (Jatuh Tempo Dalam Waktu Dekat)',
      'chip' => 'chip-warning',
      'icon' => 'bi-exclamation-triangle-fill text-warning',
    ],
    'perhatian' => [
      'label' => 'Perlu Perhatian (Siapkan Jadwal Servis)',
      'chip' => 'chip-warning',
      'icon' => 'bi-info-circle-fill text-primary',
    ],
    'normal' => [
      'label' => 'Kondisi Normal / Terjadwal Aman',
      'chip' => 'chip-success',
      'icon' => 'bi-check-circle-fill text-success',
    ],
  ];
@endphp

@if ($totalSchedules === 0)
  <div class="glass-panel p-5 text-center my-4">
    <i class="bi bi-calendar2-check text-muted" style="font-size: 3rem;"></i>
    <h5 class="fw-bold mt-3 mb-1 text-dark">Belum Ada Jadwal Perawatan</h5>
    <p class="text-muted small mb-4">Tambahkan jadwal berkala atau gunakan template siap pakai untuk menjaga kondisi aset Anda tetap prima.</p>
    <div class="d-flex justify-content-center gap-2">
      <button class="btn btn-glass px-4 py-2" data-bs-toggle="modal" data-bs-target="#modalTemplateJadwal" style="min-height: 44px;">
        <i class="bi bi-collection me-1.5"></i> Gunakan Template
      </button>
      <button class="btn btn-dark px-4 py-2" data-bs-toggle="modal" data-bs-target="#modalBuatJadwal" style="min-height: 44px;">
        <i class="bi bi-plus-lg me-1.5"></i> Buat Jadwal Baru
      </button>
    </div>
  </div>
@else
  @foreach (['terlambat', 'mendesak', 'perhatian', 'normal'] as $statusKey)
    @php
      $items = $grouped[$statusKey];
      $meta = $groupMeta[$statusKey];
    @endphp

    @if ($items->count() > 0)
      <div class="mb-4">
        <!-- Group Header -->
        <div class="d-flex align-items-center gap-2 mb-2.5">
          <i class="bi {{ $meta['icon'] }} fs-5"></i>
          <h6 class="fw-bold text-dark mb-0">{{ $meta['label'] }}</h6>
          <span class="badge bg-white border text-secondary rounded-pill px-2 py-0.5" style="font-size: 0.72rem;">{{ $items->count() }}</span>
        </div>

        <!-- Schedule Items Card -->
        <div class="glass-panel p-0 overflow-hidden mb-3">
          <div class="list-group list-group-flush">
            @foreach ($items as $sch)
              @php
                $isVehicle = $sch->subject instanceof \App\Models\Vehicle;
                $subjectOdo = $isVehicle ? (int) $sch->subject->odometer : 0;
              @endphp
              <div class="list-group-item p-3 border-slate-200 bg-transparent">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                  <!-- Col 1: Subjek & Judul -->
                  <div class="d-flex align-items-center gap-3" style="min-width: 260px;">
                    <div class="rounded-3 bg-white p-2.5 border shadow-2xs text-center" style="width: 44px; height: 44px; display: flex; align-items: center; justify-content: center;">
                      <i class="bi {{ $isVehicle ? 'bi-car-front' : 'bi-house-gear' }} fs-5 text-dark"></i>
                    </div>
                    <div>
                      <div class="d-flex align-items-center gap-2 mb-0.5">
                        <span class="badge bg-light text-dark border px-2 py-0.5" style="font-size: 0.68rem;">
                          {{ $isVehicle ? 'Kendaraan' : 'Aset Rumah' }}
                        </span>
                        <span class="fw-semibold text-secondary small">{{ $sch->subject_name }}</span>
                      </div>
                      <div class="fw-bold text-dark fs-6">{{ $sch->title }}</div>
                      @if ($sch->notes)
                        <div class="text-muted small mt-0.5" style="font-size: 0.74rem;">{{ $sch->notes }}</div>
                      @endif
                    </div>
                  </div>

                  <!-- Col 2: Pemicu & Status Waktu/KM -->
                  <div class="text-start text-md-center">
                    <span class="badge bg-white text-dark border px-2.5 py-1 mb-1 font-monospace" style="font-size: 0.72rem;">
                      {{ $sch->trigger_label }}
                    </span>
                    <div class="small text-muted" style="font-size: 0.78rem;">
                      @if ($sch->trigger_type === 'kilometer')
                        Target: <strong class="text-dark">{{ number_format($sch->next_due_km) }} KM</strong>
                        @php
                          $diff = (int)$sch->next_due_km - $subjectOdo;
                        @endphp
                        @if ($diff <= 0)
                          <span class="text-danger fw-bold">(Terlewat {{ number_format(abs($diff)) }} KM)</span>
                        @else
                          <span class="text-secondary">(Sisa {{ number_format($diff) }} KM)</span>
                        @endif
                      @elseif ($sch->next_due_date)
                        Jatuh tempo: <strong class="text-dark">{{ \Carbon\Carbon::parse($sch->next_due_date)->format('d M Y') }}</strong>
                        @php
                          $daysDiff = \Carbon\Carbon::today()->diffInDays(\Carbon\Carbon::parse($sch->next_due_date), false);
                        @endphp
                        @if ($daysDiff < 0)
                          <span class="text-danger fw-bold">({{ abs($daysDiff) }} hari lalu)</span>
                        @else
                          <span class="text-secondary">({{ $daysDiff }} hari lagi)</span>
                        @endif
                      @endif
                    </div>
                  </div>

                  <!-- Col 3: Status Chip & Tombol Aksi -->
                  <div class="d-flex align-items-center gap-2 ms-auto ms-md-0">
                    <span class="chip {{ $sch->status_chip_class }} py-1 px-2.5">
                      <span class="chip-dot"></span> {{ ucfirst($sch->status) }}
                    </span>

                    <button class="btn btn-dark btn-sm d-inline-flex align-items-center gap-1.5 px-3 btn-complete-modal" 
                            style="min-height: 40px; font-weight: 500;"
                            data-bs-toggle="modal" 
                            data-bs-target="#modalTandaiSelesai"
                            data-id="{{ $sch->id }}"
                            data-title="{{ $sch->title }}"
                            data-trigger="{{ $sch->trigger_type }}"
                            data-subject="{{ $sch->subject_name }}"
                            data-odo="{{ $subjectOdo }}"
                            data-workshop="{{ $isVehicle ? ($sch->subject->workshop ?? '') : '' }}">
                      <i class="bi bi-check2"></i>
                      <span>Tandai Selesai</span>
                    </button>

                    <!-- Dropdown Hapus -->
                    <div class="dropdown">
                      <button class="btn btn-glass btn-sm p-2 rounded-2" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="min-height: 40px; min-width: 40px;">
                        <i class="bi bi-three-dots-vertical"></i>
                      </button>
                      <ul class="dropdown-menu dropdown-menu-end glass-panel border-0 p-1 shadow-sm">
                        <li>
                          <form action="{{ route('schedules.destroy', $sch->id) }}" method="POST" onsubmit="return confirm('Hapus jadwal perawatan ini?');" class="m-0">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="dropdown-item py-1.5 rounded-2 small text-danger d-flex align-items-center gap-2">
                              <i class="bi bi-trash"></i>
                              <span>Hapus Jadwal</span>
                            </button>
                          </form>
                        </li>
                      </ul>
                    </div>
                  </div>
                </div>
              </div>
            @endforeach
          </div>
        </div>
      </div>
    @endif
  @endforeach
@endif
@endsection

@section('modals')
<!-- ================= MODAL: BUAT JADWAL BARU ================= -->
<div class="modal fade" id="modalBuatJadwal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content glass-panel border-0 shadow-lg">
      <form action="{{ route('schedules.store') }}" method="POST" id="formBuatJadwal">
        @csrf
        <input type="hidden" name="subject_type" id="inputSubjectType" value="{{ $vehicles->first() ? 'App\Models\Vehicle' : ($homeAssets->first() ? 'App\Models\HomeAsset' : '') }}">
        <input type="hidden" name="subject_id" id="inputSubjectId" value="{{ $vehicles->first()?->id ?? $homeAssets->first()?->id }}">

        <div class="modal-header border-bottom border-slate-200 py-3">
          <h6 class="modal-title fw-bold text-dark">Buat Jadwal Perawatan</h6>
          <button type="button" class="btn-close small" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body py-3">
          <!-- Pilih Subjek -->
          <div class="mb-3">
            <label class="form-label text-muted small mb-1">Pilih Subjek Aset <span class="text-danger">*</span></label>
            <select class="form-select" id="selectSubjectPicker" required>
              <optgroup label="Kendaraan">
                @foreach ($vehicles as $v)
                  <option value="App\Models\Vehicle:{{ $v->id }}" data-type="vehicle" data-odo="{{ $v->odometer }}">
                    {{ $v->name }} ({{ $v->plate }})
                  </option>
                @endforeach
              </optgroup>
              <optgroup label="Aset Rumah">
                @foreach ($homeAssets as $ha)
                  <option value="App\Models\HomeAsset:{{ $ha->id }}" data-type="home_asset">
                    {{ $ha->name }} ({{ $ha->room ?? 'Rumah' }})
                  </option>
                @endforeach
              </optgroup>
            </select>
          </div>

          <!-- Judul Jadwal -->
          <div class="mb-3">
            <label class="form-label text-muted small mb-1">Nama / Judul Agenda <span class="text-danger">*</span></label>
            <input type="text" name="title" id="inputScheduleTitle" class="form-control" placeholder="Contoh: Ganti Oli Mesin, Cuci Filter AC" required>
          </div>

          <!-- Tipe Pemicu -->
          <div class="mb-3">
            <label class="form-label text-muted small mb-1">Tipe Pemicu Jadwal <span class="text-danger">*</span></label>
            <select name="trigger_type" id="selectTriggerType" class="form-select" required>
              <option value="interval_hari">Interval Rutin (Hari / Bulan)</option>
              <option value="kilometer" id="optionTriggerKM">Jarak Tempuh (Kilometer / KM)</option>
              <option value="tanggal">Tanggal Pasti (Satu Kali)</option>
            </select>
            <small class="text-muted" id="triggerKmHint" style="font-size: 0.72rem;">* Pemicu kilometer hanya tersedia untuk kendaraan bermotor.</small>
          </div>

          <!-- Input Dinamis Sesuai Pemicu -->
          <!-- Pemicu Interval Hari -->
          <div id="wrapperIntervalDays" class="mb-3">
            <label class="form-label text-muted small mb-1">Ulangi Setiap Berapa Hari? <span class="text-danger">*</span></label>
            <div class="input-group">
              <input type="number" name="interval_days" id="inputIntervalDays" class="form-control" placeholder="90" min="1" value="90">
              <span class="input-group-text bg-transparent text-muted">Hari</span>
            </div>
            <small class="text-muted" style="font-size: 0.72rem;">Misal: 30 hari (1 bulan), 90 hari (3 bulan), 180 hari (6 bulan).</small>
          </div>

          <!-- Pemicu Kilometer -->
          <div id="wrapperIntervalKM" class="mb-3 d-none">
            <label class="form-label text-muted small mb-1">Ulangi Setiap Berapa KM? <span class="text-danger">*</span></label>
            <div class="input-group">
              <input type="number" name="interval_km" id="inputIntervalKM" class="form-control" placeholder="5000" min="100" step="100">
              <span class="input-group-text bg-transparent text-muted">KM</span>
            </div>
            <small class="text-muted" style="font-size: 0.72rem;">Jatuh tempo berikutnya akan dihitung otomatis dari odometer kendaraan saat ini.</small>
          </div>

          <!-- Tanggal Jatuh Tempo Pertama -->
          <div id="wrapperFirstDate" class="mb-3">
            <label class="form-label text-muted small mb-1">Tanggal Mulai / Jatuh Tempo Pertama</label>
            <input type="date" name="first_due_date" id="inputFirstDueDate" class="form-control" value="{{ date('Y-m-d') }}">
          </div>

          <!-- Prioritas -->
          <div class="mb-3">
            <label class="form-label text-muted small mb-1">Tingkat Prioritas <span class="text-danger">*</span></label>
            <select name="priority" id="selectPriority" class="form-select" required>
              <option value="normal" selected>Normal</option>
              <option value="perhatian">Perlu Perhatian</option>
              <option value="mendesak">Mendesak</option>
              <option value="darurat">Darurat</option>
            </select>
          </div>

          <!-- Catatan -->
          <div class="mb-2">
            <label class="form-label text-muted small mb-1">Catatan Tambahan (Opsional)</label>
            <textarea name="notes" id="inputScheduleNotes" class="form-control" rows="2" placeholder="Instruksi perawatan, rekomendasi teknisi, atau spesifikasi suku cadang..."></textarea>
          </div>
        </div>

        <div class="modal-footer border-top border-slate-200 py-2.5">
          <button type="button" class="btn btn-glass btn-sm" data-bs-dismiss="modal" style="min-height: 44px; padding: 0.4rem 1.2rem;">Batal</button>
          <button type="submit" class="btn btn-dark btn-sm px-4" style="min-height: 44px;">Simpan Jadwal</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ================= MODAL: MULAI DARI TEMPLATE ================= -->
<div class="modal fade" id="modalTemplateJadwal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content glass-panel border-0 shadow-lg">
      <div class="modal-header border-bottom border-slate-200 py-3">
        <div>
          <h6 class="modal-title fw-bold text-dark mb-0">Pilih Template Perawatan</h6>
          <small class="text-muted" style="font-size: 0.75rem;">Semua template di bawah merupakan <strong class="text-dark">contoh awal, sesuaikan</strong> dengan kebutuhan dan spesifikasi aset Anda.</small>
        </div>
        <button type="button" class="btn-close small" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body py-3">
        <div class="row g-3">
          @foreach ($templates as $t)
            <div class="col-md-6">
              <div class="p-3 rounded-3 bg-white border border-slate-200 h-100 d-flex flex-column justify-content-between">
                <div>
                  <div class="d-flex justify-content-between align-items-center mb-1">
                    <span class="badge bg-light text-dark border px-2 py-0.5" style="font-size: 0.68rem;">
                      {{ $t['target_type'] === 'vehicle' ? 'Kendaraan' : 'Aset Rumah' }}
                    </span>
                    <span class="text-muted font-monospace" style="font-size: 0.72rem;">
                      {{ $t['trigger_type'] === 'kilometer' ? 'Tiap ' . number_format($t['interval_km']) . ' KM' : 'Tiap ' . $t['interval_days'] . ' Hari' }}
                    </span>
                  </div>
                  <h6 class="fw-bold text-dark mb-1">{{ $t['title'] }}</h6>
                  <p class="text-muted small mb-3" style="font-size: 0.75rem;">{{ $t['notes'] }}</p>
                </div>
                <div>
                  <button type="button" 
                          class="btn btn-dark btn-sm w-100 btn-apply-template" 
                          style="min-height: 40px;"
                          data-title="{{ $t['title'] }}"
                          data-target-type="{{ $t['target_type'] }}"
                          data-trigger-type="{{ $t['trigger_type'] }}"
                          data-interval-days="{{ $t['interval_days'] ?? '' }}"
                          data-interval-km="{{ $t['interval_km'] ?? '' }}"
                          data-priority="{{ $t['priority'] }}"
                          data-notes="{{ $t['notes'] }}">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Gunakan Template Ini
                  </button>
                </div>
              </div>
            </div>
          @endforeach
        </div>
      </div>
      <div class="modal-footer border-top border-slate-200 py-2.5">
        <button type="button" class="btn btn-glass btn-sm" data-bs-dismiss="modal" style="min-height: 44px; padding: 0.4rem 1.2rem;">Tutup</button>
      </div>
    </div>
  </div>
</div>

<!-- ================= MODAL: TANDAI SELESAI ================= -->
<div class="modal fade" id="modalTandaiSelesai" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content glass-panel border-0 shadow-lg">
      <form action="" method="POST" id="formTandaiSelesai">
        @csrf
        <div class="modal-header border-bottom border-slate-200 py-3">
          <h6 class="modal-title fw-bold text-dark" id="modalTandaiTitle">Tandai Selesai</h6>
          <button type="button" class="btn-close small" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body py-3">
          <div class="p-2.5 rounded-3 bg-light border mb-3">
            <small class="text-muted d-block" style="font-size: 0.72rem;">Subjek Perawatan:</small>
            <div class="fw-bold text-dark" id="modalTandaiSubject">-</div>
          </div>

          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label text-muted small mb-1">Tanggal Selesai <span class="text-danger">*</span></label>
              <input type="date" name="done_at" class="form-control" value="{{ date('Y-m-d') }}" required>
            </div>
            <div class="col-6" id="wrapperCompleteOdometer">
              <label class="form-label text-muted small mb-1">Odometer Saat Ini <span class="text-danger">*</span></label>
              <input type="number" name="odometer" id="inputCompleteOdometer" class="form-control" min="0">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label text-muted small mb-1">Bengkel / Teknisi Pelaksana</label>
            <input type="text" name="workshop_name" id="inputCompleteWorkshop" class="form-control" placeholder="Opsional (mis. Auto2000, Pak Budi AC)">
          </div>

          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label text-muted small mb-1">Biaya Jasa (Rp)</label>
              <input type="number" name="labor_cost" class="form-control" value="0" min="0" step="1000">
            </div>
            <div class="col-6">
              <label class="form-label text-muted small mb-1">Biaya Parts (Rp)</label>
              <input type="number" name="parts_cost" class="form-control" value="0" min="0" step="1000">
            </div>
          </div>

          <div class="mb-2">
            <label class="form-label text-muted small mb-1">Catatan Pelaksanaan</label>
            <textarea name="notes" class="form-control" rows="2" placeholder="Catatan kondisi setelah diservis..."></textarea>
          </div>
        </div>

        <div class="modal-footer border-top border-slate-200 py-2.5">
          <button type="button" class="btn btn-glass btn-sm" data-bs-dismiss="modal" style="min-height: 44px; padding: 0.4rem 1.2rem;">Batal</button>
          <button type="submit" class="btn btn-dark btn-sm px-4" style="min-height: 44px;">Simpan & Perbarui Siklus</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  // Subject picker logic
  const picker = document.getElementById('selectSubjectPicker');
  const hiddenType = document.getElementById('inputSubjectType');
  const hiddenId = document.getElementById('inputSubjectId');
  const triggerSelect = document.getElementById('selectTriggerType');
  const optKM = document.getElementById('optionTriggerKM');
  const wrapperDays = document.getElementById('wrapperIntervalDays');
  const wrapperKM = document.getElementById('wrapperIntervalKM');

  function syncSubjectPicker() {
    if (!picker) return;
    const selected = picker.options[picker.selectedIndex];
    if (!selected) return;

    const [type, id] = selected.value.split(':');
    hiddenType.value = type;
    hiddenId.value = id;

    const isVehicle = selected.getAttribute('data-type') === 'vehicle';
    if (!isVehicle) {
      optKM.disabled = true;
      if (triggerSelect.value === 'kilometer') {
        triggerSelect.value = 'interval_hari';
      }
    } else {
      optKM.disabled = false;
    }
    syncTriggerFields();
  }

  function syncTriggerFields() {
    const val = triggerSelect.value;
    if (val === 'kilometer') {
      wrapperKM.classList.remove('d-none');
      wrapperDays.classList.add('d-none');
      document.getElementById('inputIntervalKM').required = true;
      document.getElementById('inputIntervalDays').required = false;
    } else if (val === 'interval_hari') {
      wrapperKM.classList.add('d-none');
      wrapperDays.classList.remove('d-none');
      document.getElementById('inputIntervalKM').required = false;
      document.getElementById('inputIntervalDays').required = true;
    } else {
      wrapperKM.classList.add('d-none');
      wrapperDays.classList.add('d-none');
      document.getElementById('inputIntervalKM').required = false;
      document.getElementById('inputIntervalDays').required = false;
    }
  }

  if (picker) {
    picker.addEventListener('change', syncSubjectPicker);
    triggerSelect.addEventListener('change', syncTriggerFields);
    syncSubjectPicker();
  }

  // Apply template into create schedule modal
  document.querySelectorAll('.btn-apply-template').forEach(function(btn) {
    btn.addEventListener('click', function() {
      const title = this.getAttribute('data-title');
      const targetType = this.getAttribute('data-target-type');
      const triggerType = this.getAttribute('data-trigger-type');
      const intervalDays = this.getAttribute('data-interval-days');
      const intervalKm = this.getAttribute('data-interval-km');
      const priority = this.getAttribute('data-priority');
      const notes = this.getAttribute('data-notes');

      // Close template modal & open create modal
      const templateModalEl = document.getElementById('modalTemplateJadwal');
      const templateModal = bootstrap.Modal.getInstance(templateModalEl) || new bootstrap.Modal(templateModalEl);
      templateModal.hide();

      const createModalEl = document.getElementById('modalBuatJadwal');
      const createModal = new bootstrap.Modal(createModalEl);

      // Pre-fill fields
      document.getElementById('inputScheduleTitle').value = title;
      document.getElementById('selectPriority').value = priority;
      document.getElementById('inputScheduleNotes').value = notes;

      // Select first matching subject of targetType
      for (let i = 0; i < picker.options.length; i++) {
        if (picker.options[i].getAttribute('data-type') === targetType) {
          picker.selectedIndex = i;
          break;
        }
      }
      syncSubjectPicker();

      triggerSelect.value = triggerType;
      syncTriggerFields();

      if (intervalDays) document.getElementById('inputIntervalDays').value = intervalDays;
      if (intervalKm) document.getElementById('inputIntervalKM').value = intervalKm;

      createModal.show();
    });
  });

  // Complete schedule modal trigger
  document.querySelectorAll('.btn-complete-modal').forEach(function(btn) {
    btn.addEventListener('click', function() {
      const id = this.getAttribute('data-id');
      const title = this.getAttribute('data-title');
      const trigger = this.getAttribute('data-trigger');
      const subject = this.getAttribute('data-subject');
      const odo = parseInt(this.getAttribute('data-odo') || 0, 10);
      const workshop = this.getAttribute('data-workshop');

      const form = document.getElementById('formTandaiSelesai');
      form.action = '/schedules/' + id + '/complete';

      document.getElementById('modalTandaiTitle').textContent = 'Tandai Selesai: ' + title;
      document.getElementById('modalTandaiSubject').textContent = subject;
      document.getElementById('inputCompleteWorkshop').value = workshop;

      const odoWrapper = document.getElementById('wrapperCompleteOdometer');
      const odoInput = document.getElementById('inputCompleteOdometer');
      if (trigger === 'kilometer') {
        odoWrapper.classList.remove('d-none');
        odoInput.required = true;
        odoInput.min = odo;
        odoInput.value = odo;
      } else {
        odoWrapper.classList.add('d-none');
        odoInput.required = false;
      }
    });
  });
</script>
@endpush
