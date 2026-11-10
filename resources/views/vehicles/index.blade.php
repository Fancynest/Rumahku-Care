@extends('layouts.app')

@section('title', ($selectedVehicle ? $selectedVehicle->name : 'Kendaraan') . ' - Garasi Rumahku Care')

@section('header-action')
<button class="btn btn-dark btn-sm d-flex align-items-center gap-1.5 px-3" data-bs-toggle="modal" data-bs-target="#modalTambahKendaraan">
  <i class="bi bi-plus-lg"></i>
  <span>Tambah Kendaraan</span>
</button>
@endsection

@section('content')
@if (!$selectedVehicle)
  <div class="glass-panel p-5 text-center my-4">
    <i class="bi bi-car-front text-muted fs-1"></i>
    <h5 class="fw-bold mt-3 mb-1 text-dark">Garasi Anda Masih Kosong</h5>
    <p class="text-muted small mb-4">Daftarkan kendaraan pertama Anda untuk mulai memantau servis berkala dan dokumen STNK.</p>
    <button class="btn btn-dark px-4 py-2" data-bs-toggle="modal" data-bs-target="#modalTambahKendaraan">
      <i class="bi bi-plus-lg me-1"></i> Tambah Kendaraan Sekarang
    </button>
  </div>
@else

<!-- 1. Header Sederhana: Pemilih Kendaraan (Garasi Strip di Atas) -->
<div class="mb-4">
  <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-2">
    <div>
      <h5 class="fw-bold text-dark mb-0">
        {{ $showArchived ? 'Kendaraan Terarsip' : 'Kendaraan Saya & Garasi' }}
      </h5>
      <span class="text-muted small">
        {{ $showArchived ? 'Daftar kendaraan yang telah diarsipkan (dijual/rusak berat).' : 'Pilih kendaraan untuk melihat kondisi fisik, odometer, dan servis.' }}
      </span>
    </div>
    <div>
      @if ($showArchived)
        <a href="{{ route('vehicles.index') }}" class="btn btn-outline-dark btn-sm d-inline-flex align-items-center gap-1.5">
          <i class="bi bi-arrow-left"></i>
          <span>Kembali ke Garasi Aktif ({{ $activeCount }})</span>
        </a>
      @elseif ($archivedCount > 0)
        <a href="{{ route('vehicles.index', ['archived' => 1]) }}" class="btn btn-glass btn-sm text-muted d-inline-flex align-items-center gap-1.5">
          <i class="bi bi-archive"></i>
          <span>Tampilkan arsip ({{ $archivedCount }})</span>
        </a>
      @endif
    </div>
  </div>

  <div class="d-flex gap-2.5 overflow-x-auto pb-2">
    @foreach ($vehicles as $v)
      @php
        $isActive = $v->id === $selectedVehicle->id;
      @endphp
      <a href="{{ route('vehicles.index', ['v' => $v->id, 'archived' => $showArchived ? 1 : null]) }}" 
         class="text-decoration-none d-flex align-items-center gap-3 px-3.5 py-2.5 rounded-3 border text-nowrap {{ $isActive ? 'bg-white border-dark shadow-sm' : 'bg-white-50 border-slate-200 text-secondary' }}">
        <i class="bi {{ str_starts_with($v->body_type ?? '', 'motor') ? 'bi-bicycle' : 'bi-car-front' }} {{ $isActive ? 'text-dark' : 'text-muted' }}"></i>
        <div>
          <div class="fw-bold small {{ $isActive ? 'text-dark' : 'text-slate-700' }}">{{ $v->name }}</div>
          <div class="text-muted font-monospace text-2xs">{{ $v->plate }}</div>
        </div>
        <span class="chip {{ $v->status_chip_class }} py-0.5 px-2 ms-1 text-2xs">
          {{ $v->status_label }}
        </span>
      </a>
    @endforeach
  </div>
</div>

<!-- Nav Tabs Bootstrap (5 Tab: Ringkasan, Jadwal, Inspeksi, Kerusakan, Dokumen, BBM) -->
<ul class="nav nav-tabs nav-tabs-scroll mb-4" id="vehicleTabs" role="tablist">
  <li class="nav-item" role="presentation">
    <button class="nav-link active fw-medium text-dark d-flex align-items-center gap-1.5 px-3 py-2.5" id="tab-v-ringkasan-btn" data-bs-toggle="tab" data-bs-target="#ringkasan" type="button" role="tab" aria-controls="ringkasan" aria-selected="true">
      <i class="bi bi-speedometer2"></i>
      <span>Ringkasan</span>
    </button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link fw-medium text-secondary d-flex align-items-center gap-1.5 px-3 py-2.5" id="tab-v-jadwal-btn" data-bs-toggle="tab" data-bs-target="#jadwal" type="button" role="tab" aria-controls="jadwal" aria-selected="false">
      <i class="bi bi-calendar-check"></i>
      <span>Jadwal</span>
      @if ($activeSchedules->isNotEmpty())
        <span class="badge bg-light text-dark border ms-1 text-2xs">{{ $activeSchedules->count() }}</span>
      @endif
    </button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link fw-medium text-secondary d-flex align-items-center gap-1.5 px-3 py-2.5" id="tab-v-inspeksi-btn" data-bs-toggle="tab" data-bs-target="#inspeksi" type="button" role="tab" aria-controls="inspeksi" aria-selected="false">
      <i class="bi bi-clipboard-check"></i>
      <span>Inspeksi</span>
      @if ($selectedVehicle->inspectionRuns->isNotEmpty())
        <span class="badge bg-light text-dark border ms-1 text-2xs">{{ $selectedVehicle->inspectionRuns->count() }}</span>
      @endif
    </button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link fw-medium text-secondary d-flex align-items-center gap-1.5 px-3 py-2.5" id="tab-v-kerusakan-btn" data-bs-toggle="tab" data-bs-target="#kerusakan" type="button" role="tab" aria-controls="kerusakan" aria-selected="false">
      <i class="bi bi-tools"></i>
      <span>Kerusakan</span>
      @php
        $openVehicleDamages = $selectedVehicle->damageReports->whereIn('status', ['dilaporkan', 'dalam_perbaikan']);
      @endphp
      @if ($openVehicleDamages->isNotEmpty())
        <span class="badge bg-danger text-white ms-1 text-2xs">{{ $openVehicleDamages->count() }}</span>
      @endif
    </button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link fw-medium text-secondary d-flex align-items-center gap-1.5 px-3 py-2.5" id="tab-v-dokumen-btn" data-bs-toggle="tab" data-bs-target="#dokumen" type="button" role="tab" aria-controls="dokumen" aria-selected="false">
      <i class="bi bi-file-earmark-text"></i>
      <span>Dokumen</span>
      @php
        $activeVehicleDocs = $selectedVehicle->documents->whereNull('archived_at');
      @endphp
      @if ($activeVehicleDocs->isNotEmpty())
        <span class="badge bg-light text-dark border ms-1 text-2xs">{{ $activeVehicleDocs->count() }}</span>
      @endif
    </button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link fw-medium text-secondary d-flex align-items-center gap-1.5 px-3 py-2.5" id="tab-v-bbm-btn" data-bs-toggle="tab" data-bs-target="#bbm" type="button" role="tab" aria-controls="bbm" aria-selected="false">
      <i class="bi bi-fuel-pump"></i>
      <span>BBM</span>
      @if ($fuelMetrics['logs']->isNotEmpty())
        <span class="badge bg-light text-dark border ms-1 text-2xs">{{ $fuelMetrics['logs']->count() }}</span>
      @endif
    </button>
  </li>
</ul>

<!-- Tab Contents -->
<div class="tab-content" id="vehicleTabContent">
  @include('vehicles.partials.tab-ringkasan')
  @include('vehicles.partials.tab-jadwal')
  @include('vehicles.partials.tab-inspeksi')
  @include('vehicles.partials.tab-kerusakan')
  @include('vehicles.partials.tab-dokumen')
  @include('vehicles.partials.tab-bbm')
</div>
@endif
@endsection

@section('modals')
  @include('vehicles.partials.modals')
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

  // Cascading Brand & Model for Edit Vehicle
  const brandSelectEdit = document.getElementById('selectBrandEdit');
  const modelSelectEdit = document.getElementById('selectModelEdit');
  const otherWrapperEdit = document.getElementById('wrapperOtherModelEdit');

  if (brandSelectEdit && modelSelectEdit && otherWrapperEdit) {
    brandSelectEdit.addEventListener('change', function() {
      const brand = this.value;
      modelSelectEdit.innerHTML = '';

      if (brand === 'other') {
        const opt = document.createElement('option');
        opt.value = 'other';
        opt.textContent = 'Model Kustom / Lainnya';
        opt.selected = true;
        modelSelectEdit.appendChild(opt);
        otherWrapperEdit.classList.remove('d-none');
        return;
      }

      const defaultOpt = document.createElement('option');
      defaultOpt.value = '';
      defaultOpt.textContent = 'Pilih Model ' + brand + '...';
      defaultOpt.disabled = true;
      defaultOpt.selected = true;
      modelSelectEdit.appendChild(defaultOpt);

      if (catalog[brand]) {
        catalog[brand].forEach(function(item) {
          const opt = document.createElement('option');
          opt.value = item.id;
          opt.textContent = item.model + ' (' + item.body_type.replace('_', ' ').toUpperCase() + ')';
          modelSelectEdit.appendChild(opt);
        });
      }

      const otherOpt = document.createElement('option');
      otherOpt.value = 'other';
      otherOpt.textContent = '+ Model Lainnya...';
      modelSelectEdit.appendChild(otherOpt);

      otherWrapperEdit.classList.add('d-none');
    });

    modelSelectEdit.addEventListener('change', function() {
      if (this.value === 'other') {
        otherWrapperEdit.classList.remove('d-none');
      } else {
        otherWrapperEdit.classList.add('d-none');
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

  // 8. Wire Edit Fuel Log Modal
  document.querySelectorAll('.btn-edit-fuel-log').forEach(btn => {
    btn.addEventListener('click', function() {
      const id = this.getAttribute('data-id');
      const filledAt = this.getAttribute('data-filled-at');
      const odometer = this.getAttribute('data-odometer');
      const liters = this.getAttribute('data-liters');
      const totalCost = this.getAttribute('data-total-cost');
      const fuelGrade = this.getAttribute('data-fuel-grade');
      const isFullTank = this.getAttribute('data-is-full-tank') === '1';
      const station = this.getAttribute('data-station');
      const notes = this.getAttribute('data-notes');

      const form = document.getElementById('formEditBBM');
      if (form) {
        form.action = '/fuel-logs/' + id;
        document.getElementById('inputEditFilledAt').value = filledAt;
        document.getElementById('inputEditOdometer').value = odometer;
        document.getElementById('inputEditLiters').value = liters;
        document.getElementById('inputEditTotalCost').value = totalCost;
        document.getElementById('inputEditFuelGrade').value = fuelGrade;
        document.getElementById('inputEditIsFullTank').checked = isFullTank;
        document.getElementById('inputEditStation').value = station;
        document.getElementById('inputEditNotes').value = notes;
      }
    });
  });

  document.addEventListener('DOMContentLoaded', function() {
    initVehicleTabHashSync();
  });
</script>
@endpush
