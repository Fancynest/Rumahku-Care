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
    <form action="{{ route('home-assets.destroy', $homeAsset->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus aset ini?');" class="m-0">
      @csrf
      @method('DELETE')
      <button type="submit" class="btn btn-glass btn-sm px-3 text-danger" style="min-height: 40px;">
        <i class="bi bi-trash me-1.5"></i> Hapus
      </button>
    </form>
  </div>
</div>

<!-- Main Detail Card -->
<div class="glass-panel p-4 mb-4">
  <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom border-slate-200">
    <div>
      <span class="badge bg-light text-dark border px-2.5 py-1 text-uppercase me-2" style="font-size: 0.72rem; letter-spacing: 0.05em;">
        {{ $homeAsset->category_label }}
      </span>
      <span class="text-muted small"><i class="bi bi-geo-alt me-1"></i>{{ $homeAsset->room }}</span>
    </div>
    <span class="chip {{ $homeAsset->condition_chip_class }} py-1 px-2.5">
      <span class="chip-dot"></span> Kondisi: {{ $homeAsset->condition_label }}
    </span>
  </div>

  <div class="row g-3">
    <div class="col-md-6">
      <table class="table table-sm table-borderless mb-0" style="font-size: 0.88rem;">
        <tbody>
          <tr>
            <td class="text-muted" style="width: 40%;">Merek</td>
            <td class="fw-semibold text-dark">{{ $homeAsset->brand ?: '-' }}</td>
          </tr>
          <tr>
            <td class="text-muted">Model / Tipe</td>
            <td class="text-dark">{{ $homeAsset->model ?: '-' }}</td>
          </tr>
          <tr>
            <td class="text-muted">Ruangan / Area</td>
            <td class="text-dark">{{ $homeAsset->room }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="col-md-6">
      <table class="table table-sm table-borderless mb-0" style="font-size: 0.88rem;">
        <tbody>
          <tr>
            <td class="text-muted" style="width: 45%;">Tanggal Pasang / Beli</td>
            <td class="text-dark">{{ $homeAsset->purchased_at ? $homeAsset->purchased_at->format('d F Y') : '-' }}</td>
          </tr>
          <tr>
            <td class="text-muted">Terakhir Diperiksa</td>
            <td class="text-dark">{{ $homeAsset->last_inspected_at ? $homeAsset->last_inspected_at->format('d F Y') : '-' }}</td>
          </tr>
          <tr>
            <td class="text-muted">Status Kondisi</td>
            <td class="fw-semibold text-dark">{{ $homeAsset->condition_label }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  @if ($homeAsset->notes)
    <div class="mt-4 pt-3 border-top border-slate-200">
      <h6 class="fw-bold text-dark mb-1" style="font-size: 0.85rem;">Catatan Perawatan</h6>
      <p class="text-muted small mb-0">{{ $homeAsset->notes }}</p>
    </div>
  @endif
</div>

<!-- Schedules Section for this Home Asset -->
<div class="glass-panel p-4 mb-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h6 class="fw-bold text-dark mb-0">Jadwal Perawatan Aset</h6>
      <span class="text-muted small">Daftar agenda pemeliharaan rutin untuk {{ $homeAsset->name }}.</span>
    </div>
    <a href="{{ route('schedules.index', ['filter' => 'home_asset']) }}" class="btn btn-glass btn-sm px-3" style="min-height: 40px;">
      <i class="bi bi-calendar-check me-1.5"></i> Kelola di Jadwal
    </a>
  </div>

  @if ($schedules->isEmpty())
    <div class="p-4 text-center text-muted bg-white-50 rounded-3 border border-dashed border-slate-200">
      <i class="bi bi-calendar2-x d-block fs-3 mb-1 text-secondary"></i>
      <div class="fw-medium text-dark small">Belum ada jadwal perawatan untuk aset ini</div>
      <p class="text-muted mb-0" style="font-size: 0.78rem;">Buat jadwal baru melalui menu Jadwal untuk mengatur pembersihan atau servis berkala.</p>
    </div>
  @else
    <div class="d-flex flex-column gap-2.5">
      @foreach ($schedules as $sch)
        <div class="p-3 rounded-3 bg-white border border-slate-200 d-flex flex-wrap justify-content-between align-items-center gap-2">
          <div>
            <div class="fw-bold text-dark">{{ $sch->title }}</div>
            <div class="text-muted small">
              <i class="bi bi-clock me-1"></i>{{ $sch->trigger_label }}
              @if ($sch->next_due_date)
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
            <form action="{{ route('schedules.complete', $sch->id) }}" method="POST" class="m-0">
              @csrf
              <input type="hidden" name="done_at" value="{{ date('Y-m-d') }}">
              <button type="submit" class="btn btn-glass btn-sm px-3 text-nowrap" style="min-height: 40px;" title="Tandai Selesai Hari Ini">
                <i class="bi bi-check2 me-1"></i> Selesai
              </button>
            </form>
          </div>
        </div>
      @endforeach
    </div>
  @endif
</div>
@endsection

@section('modals')
<!-- Modal Edit Aset -->
<div class="modal fade" id="modalEditAset" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content glass-panel border-0 shadow-lg">
      <form action="{{ route('home-assets.update', $homeAsset->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="modal-header border-bottom border-slate-200 py-3">
          <h6 class="modal-title fw-bold text-dark">Edit Aset — {{ $homeAsset->name }}</h6>
          <button type="button" class="btn-close small" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        
        <div class="modal-body py-3">
          <div class="mb-3">
            <label class="form-label text-muted small mb-1">Nama Aset <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="name" value="{{ $homeAsset->name }}" required style="min-height: 44px;">
          </div>

          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label text-muted small mb-1">Kategori <span class="text-danger">*</span></label>
              <select class="form-select" name="category" required style="min-height: 44px;">
                @foreach (['bangunan', 'listrik', 'air', 'pendingin', 'dapur', 'kamar_mandi', 'keamanan', 'eksterior', 'peralatan'] as $cat)
                  <option value="{{ $cat }}" {{ $homeAsset->category === $cat ? 'selected' : '' }}>
                    {{ ucfirst(str_replace('_', ' ', $cat)) }}
                  </option>
                @endforeach
              </select>
            </div>
            <div class="col-6">
              <label class="form-label text-muted small mb-1">Lokasi / Ruangan <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="room" value="{{ $homeAsset->room }}" required style="min-height: 44px;">
            </div>
          </div>

          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label text-muted small mb-1">Merek</label>
              <input type="text" class="form-control" name="brand" value="{{ $homeAsset->brand }}" style="min-height: 44px;">
            </div>
            <div class="col-6">
              <label class="form-label text-muted small mb-1">Model / Tipe</label>
              <input type="text" class="form-control" name="model" value="{{ $homeAsset->model }}" style="min-height: 44px;">
            </div>
          </div>

          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label text-muted small mb-1">Tanggal Pasang / Beli</label>
              <input type="date" class="form-control" name="purchased_at" value="{{ $homeAsset->purchased_at?->format('Y-m-d') }}" style="min-height: 44px;">
            </div>
            <div class="col-6">
              <label class="form-label text-muted small mb-1">Kondisi Saat Ini <span class="text-danger">*</span></label>
              <select class="form-select" name="condition" required style="min-height: 44px;">
                <option value="normal" {{ $homeAsset->condition === 'normal' ? 'selected' : '' }}>Normal</option>
                <option value="perlu_perhatian" {{ $homeAsset->condition === 'perlu_perhatian' ? 'selected' : '' }}>Perlu Perhatian</option>
                <option value="rusak" {{ $homeAsset->condition === 'rusak' ? 'selected' : '' }}>Rusak</option>
              </select>
            </div>
          </div>

          <div class="mb-2">
            <label class="form-label text-muted small mb-1">Catatan Tambahan</label>
            <textarea class="form-control" name="notes" rows="2">{{ $homeAsset->notes }}</textarea>
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
@endsection
