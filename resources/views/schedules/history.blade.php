@extends('layouts.app')

@section('title', 'Riwayat Aktivitas Terpadu - Rumahku Care')

@section('content')
@include('schedules.partials.nav-tabs')

<!-- Header Riwayat -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
  <div>
    <h5 class="fw-bold text-dark mb-0" style="letter-spacing: -0.02em;">Riwayat Aktivitas Terpadu</h5>
    <span class="text-muted small">Catatan kronologis seluruh servis, pengisian BBM, kerusakan, inspeksi, dan dokumen.</span>
  </div>
  <div class="text-muted small">
    Total: <strong class="text-dark">{{ $total_events }}</strong> kejadian tercatat
  </div>
</div>

<!-- Filter Linimasa -->
<div class="glass-panel p-3 mb-4">
  <form method="GET" action="{{ route('schedules.history') }}" class="row g-2 align-items-end">
    <!-- Filter Jenis Kejadian -->
    <div class="col-12 col-sm-6 col-md-3">
      <label class="form-label text-muted small fw-semibold mb-1" for="filterType">Jenis Kejadian</label>
      <select name="type" id="filterType" class="form-select form-select-sm" style="min-height: 44px;">
        <option value="all" {{ $filters['type'] === 'all' ? 'selected' : '' }}>Semua Jenis</option>
        <option value="maintenance" {{ $filters['type'] === 'maintenance' ? 'selected' : '' }}>🔧 Perawatan Selesai</option>
        <option value="fuel" {{ $filters['type'] === 'fuel' ? 'selected' : '' }}>⛽ Pengisian BBM</option>
        <option value="damage" {{ $filters['type'] === 'damage' ? 'selected' : '' }}>⚠️ Laporan Kerusakan</option>
        <option value="inspection" {{ $filters['type'] === 'inspection' ? 'selected' : '' }}>📋 Hasil Inspeksi</option>
        <option value="document" {{ $filters['type'] === 'document' ? 'selected' : '' }}>📄 Pajak & Dokumen</option>
      </select>
    </div>

    <!-- Filter Subjek -->
    <div class="col-12 col-sm-6 col-md-3">
      <label class="form-label text-muted small fw-semibold mb-1" for="filterSubject">Subjek / Objek</label>
      <select name="subject" id="filterSubject" class="form-select form-select-sm" style="min-height: 44px;">
        <option value="all" {{ $filters['subject'] === 'all' ? 'selected' : '' }}>Semua Subjek</option>
        @if ($vehicles->isNotEmpty())
          <optgroup label="Kendaraan">
            @foreach ($vehicles as $v)
              <option value="vehicle:{{ $v->id }}" {{ $filters['subject'] === 'vehicle:'.$v->id ? 'selected' : '' }}>
                {{ $v->name }} ({{ $v->plate }})
              </option>
            @endforeach
          </optgroup>
        @endif
        @if ($home_assets->isNotEmpty())
          <optgroup label="Aset Rumah">
            @foreach ($home_assets as $ha)
              <option value="home_asset:{{ $ha->id }}" {{ $filters['subject'] === 'home_asset:'.$ha->id ? 'selected' : '' }}>
                {{ $ha->name }}
              </option>
            @endforeach
          </optgroup>
        @endif
      </select>
    </div>

    <!-- Filter Rentang Tanggal -->
    <div class="col-6 col-sm-3 col-md-2">
      <label class="form-label text-muted small fw-semibold mb-1" for="filterFrom">Dari Tanggal</label>
      <input type="date" name="from" id="filterFrom" class="form-control form-control-sm" value="{{ $filters['from'] }}" style="min-height: 44px;">
    </div>

    <div class="col-6 col-sm-3 col-md-2">
      <label class="form-label text-muted small fw-semibold mb-1" for="filterTo">Sampai Tanggal</label>
      <input type="date" name="to" id="filterTo" class="form-control form-control-sm" value="{{ $filters['to'] }}" style="min-height: 44px;">
    </div>

    <!-- Tombol Filter & Reset -->
    <div class="col-12 col-md-2 d-flex gap-2">
      <button type="submit" class="btn btn-dark btn-sm flex-fill d-flex align-items-center justify-content-center gap-1.5" style="min-height: 44px;">
        <i class="bi bi-funnel"></i>
        <span>Saring</span>
      </button>
      @if ($filters['type'] !== 'all' || $filters['subject'] !== 'all' || $filters['from'] || $filters['to'])
        <a href="{{ route('schedules.history') }}" class="btn btn-glass btn-sm d-flex align-items-center justify-content-center p-2 text-secondary" title="Reset Filter" style="min-height: 44px; min-width: 44px;">
          <i class="bi bi-x-lg"></i>
        </a>
      @endif
    </div>
  </form>
</div>

<!-- Daftar Aktivitas Berkelompok Per Tanggal -->
@if ($grouped_by_date->isEmpty())
  <div class="glass-panel p-5 text-center text-muted">
    <i class="bi bi-clock-history d-block fs-1 mb-3 text-secondary opacity-50"></i>
    <h6 class="fw-bold text-dark">Tidak Ada Riwayat Aktivitas</h6>
    <p class="text-muted small mb-0">Belum ada catatan aktivitas yang sesuai dengan kriteria penyaringan di atas.</p>
  </div>
@else
  <div class="d-flex flex-column gap-4 mb-4">
    @foreach ($grouped_by_date as $dateLabel => $items)
      <div>
        <!-- Penanda Tanggal -->
        <div class="d-flex align-items-center gap-2 mb-2.5">
          <span class="badge bg-slate-200 text-slate-800 fw-semibold px-2.5 py-1.5 rounded-pill" style="font-size: 0.78rem;">
            <i class="bi bi-calendar-event me-1"></i> {{ $dateLabel }}
          </span>
          <div class="flex-grow-1 border-bottom border-slate-200"></div>
          <span class="text-muted small" style="font-size: 0.72rem;">{{ $items->count() }} kejadian</span>
        </div>

        <!-- Daftar Kartu Kejadian -->
        <div class="d-flex flex-column gap-2">
          @foreach ($items as $item)
            <div class="p-3 rounded-3 bg-white border border-slate-200 shadow-2xs d-flex flex-wrap align-items-center justify-content-between gap-3 hover-lift">
              <div class="d-flex align-items-start gap-3 flex-grow-1" style="min-width: 240px;">
                <!-- Icon Lingkaran -->
                <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" 
                     style="width: 42px; height: 42px; background: rgba(241, 245, 249, 0.9); color: #1e293b;">
                  <i class="bi {{ $item->icon }} fs-5"></i>
                </div>

                <div class="overflow-hidden">
                  <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                    <span class="chip {{ $item->chip_class }} py-0 px-2" style="font-size: 0.7rem;">
                      {{ $item->type_label }}
                    </span>
                    <span class="badge bg-light text-secondary border py-0 px-2" style="font-size: 0.7rem;">
                      {{ $item->subject_name }}
                    </span>
                  </div>
                  <h6 class="fw-bold text-dark mb-1" style="font-size: 0.9rem;">{{ $item->title }}</h6>
                  <p class="text-muted small mb-0" style="font-size: 0.78rem;">{{ $item->summary }}</p>
                </div>
              </div>

              <!-- Tombol Aksi Buka Objek -->
              <div class="d-flex align-items-center">
                <a href="{{ $item->url }}" class="btn btn-glass btn-sm px-3.5 py-2 text-dark d-flex align-items-center gap-1.5" style="min-height: 44px; font-size: 0.8rem;">
                  <span>Detail</span>
                  <i class="bi bi-arrow-right"></i>
                </a>
              </div>
            </div>
          @endforeach
        </div>
      </div>
    @endforeach
  </div>

  <!-- Paginasi -->
  @if ($paginator->hasPages())
    <div class="d-flex justify-content-center mt-4">
      {{ $paginator->links() }}
    </div>
  @endif
@endif

@endsection
