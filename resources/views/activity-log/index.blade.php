@extends('layouts.app')

@section('title', 'Log Aktivitas - Rumahku Care')

@section('content')

{{-- Header --}}
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
  <div>
    <h4 class="fw-bold text-dark mb-1">Log Aktivitas</h4>
    <span class="text-muted small">Semua perubahan dan kejadian tercatat secara detail di sini.</span>
  </div>
  <div class="text-muted small">
    Total: <strong class="text-dark">{{ number_format($total) }}</strong> entri
  </div>
</div>

{{-- Filter --}}
<div class="glass-panel p-3 mb-4">
  <form method="GET" action="{{ route('activity-log.index') }}" class="row g-2 align-items-end">

    {{-- Kategori Event --}}
    <div class="col-12 col-sm-6 col-md-3">
      <label class="form-label text-muted small fw-semibold mb-1" for="filterEvent">Kategori</label>
      <select name="event" id="filterEvent" class="form-select form-select-sm">
        <option value="all"         {{ $filters['event'] === 'all'          ? 'selected' : '' }}>Semua Kategori</option>
        <option value="vehicle"     {{ $filters['event'] === 'vehicle'      ? 'selected' : '' }}>🚗 Kendaraan</option>
        <option value="home_asset"  {{ $filters['event'] === 'home_asset'   ? 'selected' : '' }}>🏠 Aset Rumah</option>
        <option value="schedule"    {{ $filters['event'] === 'schedule'     ? 'selected' : '' }}>📅 Jadwal</option>
        <option value="damage_report" {{ $filters['event'] === 'damage_report' ? 'selected' : '' }}>⚠️ Kerusakan</option>
        <option value="inspection"  {{ $filters['event'] === 'inspection'   ? 'selected' : '' }}>📋 Inspeksi</option>
        <option value="fuel_log"    {{ $filters['event'] === 'fuel_log'     ? 'selected' : '' }}>⛽ BBM</option>
        <option value="document"    {{ $filters['event'] === 'document'     ? 'selected' : '' }}>📄 Dokumen</option>
      </select>
    </div>

    {{-- Subjek --}}
    <div class="col-12 col-sm-6 col-md-3">
      <label class="form-label text-muted small fw-semibold mb-1" for="filterSubject">Subjek</label>
      <select name="subject" id="filterSubject" class="form-select form-select-sm">
        <option value="all" {{ $filters['subject'] === 'all' ? 'selected' : '' }}>Semua Subjek</option>
        @if ($vehicles->isNotEmpty())
          <optgroup label="Kendaraan">
            @foreach ($vehicles as $v)
              <option value="vehicle:{{ $v->id }}" {{ $filters['subject'] === 'vehicle:'.$v->id ? 'selected' : '' }}>
                {{ $v->raw_name }} ({{ $v->plate }})
              </option>
            @endforeach
          </optgroup>
        @endif
        @if ($homeAssets->isNotEmpty())
          <optgroup label="Aset Rumah">
            @foreach ($homeAssets as $ha)
              <option value="home_asset:{{ $ha->id }}" {{ $filters['subject'] === 'home_asset:'.$ha->id ? 'selected' : '' }}>
                {{ $ha->name }}
              </option>
            @endforeach
          </optgroup>
        @endif
      </select>
    </div>

    {{-- Dari Tanggal --}}
    <div class="col-6 col-sm-3 col-md-2">
      <label class="form-label text-muted small fw-semibold mb-1" for="filterFrom">Dari</label>
      <input type="date" name="from" id="filterFrom" class="form-control form-control-sm" value="{{ $filters['from'] }}">
    </div>

    {{-- Sampai Tanggal --}}
    <div class="col-6 col-sm-3 col-md-2">
      <label class="form-label text-muted small fw-semibold mb-1" for="filterTo">Sampai</label>
      <input type="date" name="to" id="filterTo" class="form-control form-control-sm" value="{{ $filters['to'] }}">
    </div>

    {{-- Tombol --}}
    <div class="col-12 col-md-2 d-flex gap-2">
      <button type="submit" class="btn btn-dark btn-sm flex-fill d-flex align-items-center justify-content-center gap-1.5">
        <i class="bi bi-funnel"></i>
        <span>Saring</span>
      </button>
      @if ($filters['event'] !== 'all' || $filters['subject'] !== 'all' || $filters['from'] || $filters['to'])
        <a href="{{ route('activity-log.index') }}" class="btn btn-glass btn-touch btn-sm d-flex align-items-center justify-content-center p-2 text-secondary" title="Reset Filter">
          <i class="bi bi-x-lg"></i>
        </a>
      @endif
    </div>
  </form>
</div>

{{-- Daftar Log --}}
@php
  $hasFilter = $filters['event'] !== 'all' || $filters['subject'] !== 'all' || !empty($filters['from']) || !empty($filters['to']);
@endphp
@if ($grouped->isEmpty())
  <div class="glass-panel p-5 text-center text-muted">
    <i class="bi bi-clock-history d-block fs-1 mb-3 text-secondary opacity-50"></i>
    <h6 class="fw-bold text-dark">{{ $hasFilter ? 'Tidak Ada Log yang Sesuai' : 'Belum Ada Log Aktivitas' }}</h6>
    <p class="text-muted small mb-0">
      {{ $hasFilter ? 'Tidak ditemukan catatan aktivitas yang sesuai dengan kriteria penyaringan di atas.' : 'Setiap kali ada perubahan data, akan muncul di sini secara otomatis.' }}
    </p>
    @if ($hasFilter)
      <div class="mt-3">
        <a href="{{ route('activity-log.index') }}" class="btn btn-dark btn-sm px-3">
          <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Saringan
        </a>
      </div>
    @endif
  </div>
@else
  <div class="d-flex flex-column gap-4 mb-4">
    @foreach ($grouped as $dateLabel => $logs)
      <div>
        {{-- Label tanggal --}}
        <div class="d-flex align-items-center gap-2 mb-2.5">
          <span class="badge bg-slate-200 text-slate-800 fw-semibold px-2.5 py-1.5 rounded-pill text-xs">
            <i class="bi bi-calendar-event me-1"></i>{{ $dateLabel }}
          </span>
          <div class="flex-grow-1 border-bottom border-slate-200"></div>
          <span class="text-muted text-2xs">{{ $logs->count() }} entri</span>
        </div>

        {{-- Kartu log --}}
        <div class="d-flex flex-column gap-2">
          @foreach ($logs as $log)
            <div class="p-3 rounded-3 bg-white border border-slate-200 shadow-2xs">
              <div class="d-flex flex-wrap align-items-start gap-3">

                {{-- Icon --}}
                <div class="avatar-lg rounded-3 bg-light text-dark flex-shrink-0 d-flex align-items-center justify-content-center">
                  <i class="bi {{ $log->icon }} fs-5"></i>
                </div>

                {{-- Konten utama --}}
                <div class="flex-grow-1 overflow-hidden">
                  <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                    <span class="chip {{ $log->chip_class }} py-0 px-2 text-2xs">{{ $log->event_label }}</span>
                    <span class="badge bg-light text-secondary border py-0 px-2 text-2xs">
                      {{ $log->subject_name }}
                    </span>
                    <span class="text-muted text-2xs ms-auto">
                      {{ $log->created_at->locale('id')->isoFormat('HH:mm') }}
                    </span>
                  </div>

                  <p class="fw-semibold text-dark small mb-0">{{ $log->description }}</p>

                  {{-- Detail diff perubahan --}}
                  @if ($log->hasFieldChanges())
                    <div class="mt-2 pt-2 border-top border-slate-100">
                      <div class="d-flex flex-wrap gap-2">
                        @foreach ($log->properties['changed'] as $field => $change)
                          <div class="bg-slate-50 border border-slate-200 rounded-2 px-2 py-1 text-2xs">
                            <span class="text-muted fw-medium">{{ $change['label'] }}:</span>
                            <span class="text-danger text-decoration-line-through ms-1">
                              {{ $change['old'] ?? '(kosong)' }}
                            </span>
                            <i class="bi bi-arrow-right text-muted mx-1"></i>
                            <span class="text-success fw-semibold">
                              {{ $change['new'] ?? '(kosong)' }}
                            </span>
                          </div>
                        @endforeach
                      </div>
                    </div>
                  @elseif (!empty($log->properties) && !isset($log->properties['subject_name']))
                    {{-- Properties non-diff (servis, bbm, dll) --}}
                    <div class="mt-2 pt-2 border-top border-slate-100">
                      <div class="d-flex flex-wrap gap-2">
                        @foreach ($log->properties as $key => $val)
                          @if ($val !== null && $val !== '' && $key !== 'subject_name')
                            <span class="badge bg-light text-secondary border text-2xs fw-normal">
                              {{ ucfirst(str_replace('_', ' ', $key)) }}: <strong>{{ $val }}</strong>
                            </span>
                          @endif
                        @endforeach
                      </div>
                    </div>
                  @endif
                </div>

              </div>
            </div>
          @endforeach
        </div>
      </div>
    @endforeach
  </div>

  {{-- Paginasi --}}
  @if ($paginator->hasPages())
    <div class="d-flex justify-content-center mt-4">
      {{ $paginator->links() }}
    </div>
  @endif
@endif

@endsection
