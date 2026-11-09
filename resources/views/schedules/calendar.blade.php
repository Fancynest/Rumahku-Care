@extends('layouts.app')

@section('title', 'Kalender Perawatan - Rumahku Care')

@section('content')
@include('schedules.partials.nav-tabs')

<!-- Kotak Pemberitahuan Jadwal Berbasis KM -->
@if ($km_schedules_count > 0)
  <div class="alert alert-info border-0 shadow-2xs d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4" style="background: rgba(238, 242, 255, 0.85); border-left: 4px solid #4f46e5 !important;">
    <div class="d-flex align-items-center gap-2">
      <i class="bi bi-speedometer2 fs-5 text-primary"></i>
      <div>
        <strong class="text-dark">{{ $km_schedules_count }} jadwal perawatan berbasis KM</strong>
        <span class="text-secondary small d-block">Jadwal ini dipicu oleh jarak tempuh odometer dan hanya dipantau di tab Mendatang.</span>
      </div>
    </div>
    <a href="{{ route('schedules.index') }}" class="btn btn-dark btn-sm px-3 py-1.5" style="font-size: 0.8rem;">
      Buka Tab Mendatang <i class="bi bi-arrow-right ms-1"></i>
    </a>
  </div>
@endif

<!-- Header Kalender: Pemilih Bulan & Toggle Selesai -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
  <div>
    <h5 class="fw-bold text-dark mb-0" style="letter-spacing: -0.02em;">Kalender Agenda dan Jatuh Tempo</h5>
    <span class="text-muted small">Peta visual jadwal servis berkala dan dokumen yang jatuh tempo per tanggal.</span>
  </div>

  <div class="d-flex flex-wrap align-items-center gap-3">
    <!-- Toggle Tampilkan Servis Selesai -->
    <div class="form-check form-switch mb-0">
      <input class="form-check-input" type="checkbox" id="toggleShowCompleted" 
             onchange="window.location.href = '{{ route('schedules.calendar', ['month' => $month]) }}&show_completed=' + (this.checked ? '1' : '0')" 
             {{ $show_completed ? 'checked' : '' }}>
      <label class="form-check-label text-dark small fw-medium" for="toggleShowCompleted">
        Riwayat Selesai
      </label>
    </div>

    <!-- Navigasi Bulan -->
    <div class="d-flex align-items-center gap-2 bg-white p-1.5 rounded-3 border shadow-2xs">
      <a href="{{ route('schedules.calendar', ['month' => $prev_month, 'show_completed' => $show_completed ? '1' : '0']) }}" 
         class="btn btn-glass btn-sm p-1.5 px-2 text-dark" 
         title="Bulan Sebelumnya" 
         style="min-height: 36px; min-width: 36px;">
        <i class="bi bi-chevron-left"></i>
      </a>
      <span class="fw-bold text-dark px-2 text-capitalize text-nowrap" style="font-size: 0.88rem; min-width: 140px; text-align: center;">
        {{ $month_label }}
      </span>
      <a href="{{ route('schedules.calendar', ['month' => $next_month, 'show_completed' => $show_completed ? '1' : '0']) }}" 
         class="btn btn-glass btn-sm p-1.5 px-2 text-dark" 
         title="Bulan Berikutnya" 
         style="min-height: 36px; min-width: 36px;">
        <i class="bi bi-chevron-right"></i>
      </a>
      @if ($month !== date('Y-m'))
        <a href="{{ route('schedules.calendar', ['month' => date('Y-m'), 'show_completed' => $show_completed ? '1' : '0']) }}" 
           class="btn btn-dark btn-sm px-2.5 ms-1" 
           style="min-height: 36px; font-size: 0.78rem;">
          Hari Ini
        </a>
      @endif
    </div>
  </div>
</div>

<!-- ================= 1. DESKTOP GRID KALENDER (>= 768px) ================= -->
<div class="glass-panel p-3 d-none d-md-block overflow-hidden mb-4">
  <div class="table-responsive">
    <table class="table table-bordered mb-0" style="table-layout: fixed; border-color: rgba(226, 232, 240, 0.8);">
      <thead>
        <tr class="text-center text-muted text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.05em; background: rgba(248, 250, 252, 0.7);">
          <th style="width: 14.28%;" class="py-2.5">Senin</th>
          <th style="width: 14.28%;" class="py-2.5">Selasa</th>
          <th style="width: 14.28%;" class="py-2.5">Rabu</th>
          <th style="width: 14.28%;" class="py-2.5">Kamis</th>
          <th style="width: 14.28%;" class="py-2.5">Jumat</th>
          <th style="width: 14.28%;" class="py-2.5 text-secondary">Sabtu</th>
          <th style="width: 14.28%;" class="py-2.5 text-danger">Minggu</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($weeks as $week)
          <tr>
            @foreach ($week as $day)
              @php
                $itemCount = $day['items']->count();
                $displayItems = $day['items']->take(3);
                $remainingCount = $itemCount - 3;
              @endphp
              <td class="p-2 align-top position-relative {{ $day['is_current_month'] ? 'bg-white' : 'bg-light text-muted opacity-60' }}" 
                  style="height: 120px; transition: background 0.15s ease;">
                <!-- Header Hari (Nomor Tanggal) -->
                <div class="d-flex justify-content-between align-items-center mb-1.5">
                  <span class="small fw-bold {{ $day['is_today'] ? 'badge bg-dark text-white rounded-circle d-inline-flex align-items-center justify-content-center' : ($day['is_current_month'] ? 'text-dark' : 'text-muted') }}" 
                        style="{{ $day['is_today'] ? 'width: 24px; height: 24px;' : '' }}">
                    {{ $day['day_number'] }}
                  </span>
                  @if ($itemCount > 0)
                    <span class="badge bg-light text-secondary border px-1.5 py-0.5" style="font-size: 0.65rem;">
                      {{ $itemCount }}
                    </span>
                  @endif
                </div>

                <!-- Daftar Chip Agenda (Maksimal 3) -->
                <div class="d-flex flex-column gap-1 overflow-hidden">
                  @foreach ($displayItems as $item)
                    <a href="{{ $item->url }}" 
                       class="text-decoration-none chip {{ $item->chip_class }} py-0.5 px-1.5 rounded-2 d-flex align-items-center justify-content-between text-truncate" 
                       style="font-size: 0.68rem; line-height: 1.2;" 
                       title="{{ $item->type_label }}: {{ $item->title }} ({{ $item->subject_name }})">
                      <span class="text-truncate fw-medium">
                        {{ $item->title }}
                      </span>
                    </a>
                  @endforeach

                  <!-- Tombol +N lagi yang membuka modal detail hari -->
                  @if ($remainingCount > 0)
                    <button type="button" 
                            class="btn btn-link text-primary p-0 text-start small text-decoration-none fw-semibold btn-show-day-items" 
                            data-bs-toggle="modal" 
                            data-bs-target="#modalDayItems" 
                            data-date="{{ $day['date']->translatedFormat('l, d F Y') }}" 
                            data-items="{{ json_encode($day['items']->toArray()) }}" 
                            style="font-size: 0.7rem;">
                      +{{ $remainingCount }} lagi...
                    </button>
                  @endif
                </div>
              </td>
            @endforeach
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>

<!-- ================= 2. MOBILE AGENDA LIST (< 768px) ================= -->
<div class="d-md-none mb-4">
  @if (empty($agendaList))
    <div class="glass-panel p-4 text-center text-muted">
      <i class="bi bi-calendar2-check d-block fs-3 mb-2 text-success"></i>
      <div class="fw-bold text-dark">Tidak Ada Agenda pada Bulan Ini</div>
      <p class="text-muted small mb-0">Tidak ada jadwal servis atau masa berlaku dokumen yang jatuh tempo pada {{ $month_label }}.</p>
    </div>
  @else
    <div class="d-flex flex-column gap-3">
      @foreach ($agendaList as $agenda)
        <div class="glass-panel p-3">
          <div class="d-flex align-items-center gap-2 mb-2 pb-2 border-bottom border-slate-100">
            <span class="badge bg-dark text-white px-2 py-1" style="font-size: 0.72rem;">
              {{ $agenda['date']->translatedFormat('d M') }}
            </span>
            <strong class="text-dark small">{{ $agenda['date']->translatedFormat('l, d F Y') }}</strong>
          </div>

          <div class="d-flex flex-column gap-2">
            @foreach ($agenda['items'] as $item)
              <div class="p-2.5 rounded-3 bg-white border border-slate-200 d-flex justify-content-between align-items-center gap-2">
                <div>
                  <div class="d-flex align-items-center gap-1.5 mb-1">
                    <span class="chip {{ $item->chip_class }} py-0 px-1.5" style="font-size: 0.65rem;">
                      {{ $item->status_label }}
                    </span>
                    <span class="badge bg-light text-dark border py-0 px-1.5" style="font-size: 0.65rem;">
                      {{ $item->type_label }}
                    </span>
                  </div>
                  <div class="fw-bold text-dark small">{{ $item->title }}</div>
                  <div class="text-muted" style="font-size: 0.72rem;">{{ $item->subject_name }}</div>
                </div>
                <a href="{{ $item->url }}" class="btn btn-glass btn-sm px-2.5" style="font-size: 0.75rem; min-height: 36px;">
                  Buka
                </a>
              </div>
            @endforeach
          </div>
        </div>
      @endforeach
    </div>
  @endif
</div>

<!-- ================= MODAL DETAIL AGENDA HARI (+N Lagi) ================= -->
<div class="modal fade" id="modalDayItems" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content glass-panel border-0 shadow-lg">
      <div class="modal-header border-bottom border-slate-200 py-3">
        <div>
          <h6 class="modal-title fw-bold text-dark mb-0" id="modalDayTitle">Agenda Tanggal</h6>
          <span class="text-muted small" id="modalDaySubtitle">Daftar agenda perawatan dan dokumen</span>
        </div>
        <button type="button" class="btn-close small" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body py-3">
        <div class="d-flex flex-column gap-2" id="modalDayItemsList">
          <!-- Diisi otomatis oleh JavaScript -->
        </div>
      </div>

      <div class="modal-footer border-top border-slate-200 py-2.5">
        <button type="button" class="btn btn-glass btn-sm px-3" data-bs-dismiss="modal" style="min-height: 38px;">Tutup</button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  document.querySelectorAll('.btn-show-day-items').forEach(btn => {
    btn.addEventListener('click', function() {
      const dateStr = this.getAttribute('data-date');
      const itemsRaw = this.getAttribute('data-items');
      const items = JSON.parse(itemsRaw || '[]');

      document.getElementById('modalDayTitle').textContent = 'Agenda: ' + dateStr;
      document.getElementById('modalDaySubtitle').textContent = items.length + ' item terdaftar pada tanggal ini';

      const container = document.getElementById('modalDayItemsList');
      container.innerHTML = '';

      items.forEach(item => {
        const itemEl = document.createElement('div');
        itemEl.className = 'p-3 rounded-3 bg-white border border-slate-200 d-flex justify-content-between align-items-center gap-2';
        itemEl.innerHTML = `
          <div>
            <div class="d-flex align-items-center gap-1.5 mb-1">
              <span class="chip ${item.chip_class} py-0 px-1.5" style="font-size: 0.65rem;">
                ${item.status_label}
              </span>
              <span class="badge bg-light text-dark border py-0 px-1.5" style="font-size: 0.65rem;">
                ${item.type_label}
              </span>
            </div>
            <div class="fw-bold text-dark small">${item.title}</div>
            <div class="text-muted" style="font-size: 0.72rem;">${item.subject_name} • ${item.detail || ''}</div>
          </div>
          <a href="${item.url}" class="btn btn-dark btn-sm px-3" style="min-height: 36px; font-size: 0.75rem;">
            Buka
          </a>
        `;
        container.appendChild(itemEl);
      });
    });
  });
</script>
@endpush
