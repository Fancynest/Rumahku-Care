@extends('layouts.app')

@section('title', 'Detail: ' . $homeAsset->name . ' - Rumahku Care')

@section('content')
<!-- Header Back Button & Actions -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
  <div class="d-flex align-items-center gap-2">
    <a href="{{ route('home-assets.index') }}" class="btn btn-glass btn-sm p-2 rounded-3" title="Kembali ke Daftar Aset" style="min-height: 40px; min-width: 40px; display: inline-flex; align-items: center; justify-content: center;">
      <i class="bi bi-arrow-left"></i>
    </a>
    <div>
      <h4 class="fw-bold mb-0 text-dark" style="letter-spacing: -0.02em;">{{ $homeAsset->name }}</h4>
      <p class="text-muted mb-0 small">Kategori: {{ $homeAsset->category_label }} • Ruangan: {{ $homeAsset->room }}</p>
    </div>
  </div>

  <div class="d-flex align-items-center gap-2">
    <button class="btn btn-glass btn-sm px-3" data-bs-toggle="modal" data-bs-target="#modalEditAset" style="min-height: 40px;">
      <i class="bi bi-pencil me-1.5"></i> Edit Aset
    </button>
    <form action="{{ route('home-assets.destroy', $homeAsset->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus aset ini? Seluruh riwayat dan lampiran terkait akan ikut terhapus.');" class="m-0">
      @csrf
      @method('DELETE')
      <button type="submit" class="btn btn-glass btn-sm px-3 text-danger" style="min-height: 40px;">
        <i class="bi bi-trash me-1.5"></i> Hapus
      </button>
    </form>
  </div>
</div>

<!-- Nav Tabs Bootstrap (5 Tab: Ringkasan, Jadwal, Inspeksi, Kerusakan, Dokumen) -->
<ul class="nav nav-tabs border-bottom border-slate-200 mb-4" id="assetTabs" role="tablist">
  <li class="nav-item" role="presentation">
    <button class="nav-link active fw-medium text-dark d-flex align-items-center gap-1.5 px-3 py-2.5" id="tab-ringkasan-btn" data-bs-toggle="tab" data-bs-target="#ringkasan" type="button" role="tab" aria-controls="ringkasan" aria-selected="true" style="min-height: 44px;">
      <i class="bi bi-info-circle"></i>
      <span>Ringkasan</span>
    </button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link fw-medium text-secondary d-flex align-items-center gap-1.5 px-3 py-2.5" id="tab-jadwal-btn" data-bs-toggle="tab" data-bs-target="#jadwal" type="button" role="tab" aria-controls="jadwal" aria-selected="false" style="min-height: 44px;">
      <i class="bi bi-calendar-check"></i>
      <span>Jadwal</span>
      @if ($schedules->isNotEmpty())
        <span class="badge bg-light text-dark border ms-1" style="font-size: 0.65rem;">{{ $schedules->count() }}</span>
      @endif
    </button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link fw-medium text-secondary d-flex align-items-center gap-1.5 px-3 py-2.5" id="tab-inspeksi-btn" data-bs-toggle="tab" data-bs-target="#inspeksi" type="button" role="tab" aria-controls="inspeksi" aria-selected="false" style="min-height: 44px;">
      <i class="bi bi-clipboard-check"></i>
      <span>Inspeksi</span>
      @if ($homeAsset->inspectionRuns->isNotEmpty())
        <span class="badge bg-light text-dark border ms-1" style="font-size: 0.65rem;">{{ $homeAsset->inspectionRuns->count() }}</span>
      @endif
    </button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link fw-medium text-secondary d-flex align-items-center gap-1.5 px-3 py-2.5" id="tab-kerusakan-btn" data-bs-toggle="tab" data-bs-target="#kerusakan" type="button" role="tab" aria-controls="kerusakan" aria-selected="false" style="min-height: 44px;">
      <i class="bi bi-tools"></i>
      <span>Kerusakan</span>
      @php
        $openDamages = $homeAsset->damageReports->whereIn('status', ['dilaporkan', 'dalam_perbaikan']);
      @endphp
      @if ($openDamages->isNotEmpty())
        <span class="badge bg-danger text-white ms-1" style="font-size: 0.65rem;">{{ $openDamages->count() }}</span>
      @endif
    </button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link fw-medium text-secondary d-flex align-items-center gap-1.5 px-3 py-2.5" id="tab-dokumen-btn" data-bs-toggle="tab" data-bs-target="#dokumen" type="button" role="tab" aria-controls="dokumen" aria-selected="false" style="min-height: 44px;">
      <i class="bi bi-file-earmark-text"></i>
      <span>Dokumen</span>
      @php
        $activeDocs = $homeAsset->documents->whereNull('archived_at');
      @endphp
      @if ($activeDocs->isNotEmpty())
        <span class="badge bg-light text-dark border ms-1" style="font-size: 0.65rem;">{{ $activeDocs->count() }}</span>
      @endif
    </button>
  </li>
</ul>

<!-- Tab Contents -->
<div class="tab-content" id="assetTabContent">
  @include('assets.partials.tab-ringkasan')
  @include('assets.partials.tab-jadwal')
  @include('assets.partials.tab-inspeksi')
  @include('assets.partials.tab-kerusakan')
  @include('assets.partials.tab-dokumen')
</div>
@endsection

@section('modals')
  @include('assets.partials.modals')
@endsection

@push('scripts')
<script>
  // 1. Sync Active Tab with URL Hash
  function initTabHashSync() {
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

  // 2. Dynamic Inspection Checklist Template Renderer
  function initInspectionTemplateRenderer() {
    const templateSelect = document.getElementById('selectAssetInspectionTemplate');
    const titleInput = document.getElementById('inputAssetInspectionTitle');
    const itemsContainer = document.getElementById('containerAssetInspectionItems');

    if (!templateSelect || !itemsContainer) return;

    function renderItems() {
      const selectedOption = templateSelect.options[templateSelect.selectedIndex];
      if (!selectedOption) return;

      const title = selectedOption.getAttribute('data-title') || '';
      titleInput.value = title;

      let items = [];
      try {
        items = JSON.parse(selectedOption.getAttribute('data-items') || '[]');
      } catch (e) {
        items = [];
      }

      itemsContainer.innerHTML = '';
      items.forEach((itemText, idx) => {
        const itemRow = document.createElement('div');
        itemRow.className = 'p-2.5 rounded-2 bg-light border d-flex flex-wrap align-items-center justify-content-between gap-2';
        itemRow.innerHTML = `
          <div class="fw-medium text-dark small" style="flex: 1 1 200px;">
            ${itemText}
            <input type="hidden" name="items[${idx}][label]" value="${itemText}">
          </div>
          <div class="d-flex align-items-center gap-1.5" style="flex: 0 0 auto;">
            <select name="items[${idx}][status]" class="form-select form-select-sm" style="min-height: 38px; width: 140px;">
              <option value="ok" selected>Kondisi OK</option>
              <option value="perhatian">Perhatian</option>
              <option value="bermasalah">Bermasalah</option>
              <option value="na">N/A</option>
            </select>
            <input type="text" name="items[${idx}][notes]" class="form-control form-control-sm" placeholder="Catatan (opsional)" style="min-height: 38px; width: 150px;">
          </div>
        `;
        itemsContainer.appendChild(itemRow);
      });
    }

    templateSelect.addEventListener('change', renderItems);
    renderItems();
  }

  // 3. Edit Damage Report Status Modal Wiring
  function initDamageStatusWiring() {
    document.querySelectorAll('.btn-edit-damage-status').forEach(btn => {
      btn.addEventListener('click', function() {
        const id = this.getAttribute('data-id');
        const title = this.getAttribute('data-title');
        const status = this.getAttribute('data-status');
        const tech = this.getAttribute('data-tech');
        const cost = this.getAttribute('data-cost');
        const notes = this.getAttribute('data-notes');

        const form = document.getElementById('formStatusKerusakan');
        form.action = '/damage-reports/' + id + '/status';

        document.getElementById('modalTitleStatusKerusakan').textContent = 'Perbarui Status: ' + title;
        document.getElementById('inputStatusDamage').value = status;
        document.getElementById('inputStatusTech').value = tech;
        document.getElementById('inputStatusCost').value = cost;
        document.getElementById('inputStatusNotes').value = notes;
      });
    });
  }

  // 4. Renew Document Modal Wiring
  function initRenewDocumentWiring() {
    document.querySelectorAll('.btn-renew-document').forEach(btn => {
      btn.addEventListener('click', function() {
        const id = this.getAttribute('data-id');
        const title = this.getAttribute('data-title');
        const cost = this.getAttribute('data-cost');
        const ref = this.getAttribute('data-ref');

        const form = document.getElementById('formPerpanjangDokumen');
        form.action = '/documents/' + id + '/renew';

        document.getElementById('labelPerpanjangTitle').textContent = title;
        document.getElementById('inputPerpanjangCost').value = cost;
        document.getElementById('inputPerpanjangRef').value = ref;
      });
    });
  }

  document.addEventListener('DOMContentLoaded', function() {
    initTabHashSync();
    initInspectionTemplateRenderer();
    initDamageStatusWiring();
    initRenewDocumentWiring();
  });
</script>
@endpush
