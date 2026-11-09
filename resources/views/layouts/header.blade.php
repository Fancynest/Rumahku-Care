<header class="top-header">
  <div class="d-flex align-items-center gap-3">
    <button class="btn btn-glass d-lg-none p-1.5" id="sidebarToggle" aria-label="Toggle Navigation">
      <i class="bi bi-list fs-5"></i>
    </button>
  </div>

  <div class="d-flex align-items-center gap-2">
    @yield('header-action')

    <!-- Notification Bell -->
    <div class="dropdown">
      <button class="btn btn-glass rounded-3 position-relative p-2" type="button" data-bs-toggle="dropdown" aria-label="Notifikasi">
        <i class="bi bi-bell text-secondary" style="font-size: 0.95rem;"></i>
        @if (isset($urgentSchedules) && $urgentSchedules->isNotEmpty())
          <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle"></span>
        @endif
      </button>
      <div class="dropdown-menu dropdown-menu-end glass-panel border-0 p-3 shadow-sm" style="width: 320px;">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <span class="fw-bold small text-dark">Pemberitahuan</span>
          @if (isset($urgentSchedules) && $urgentSchedules->isNotEmpty())
            <span class="chip chip-urgent py-0 px-1.5">{{ $urgentSchedules->count() }} Perlu Perhatian</span>
          @else
            <span class="chip chip-success py-0 px-1.5">Semua Prima</span>
          @endif
        </div>
        <div class="list-group list-group-flush" style="font-size: 0.8rem;">
          @if (isset($urgentSchedules) && $urgentSchedules->isNotEmpty())
            @foreach ($urgentSchedules as $sched)
              @php
                $targetUrl = $sched->subject_type === App\Models\Vehicle::class
                    ? route('vehicles.index', ['v' => $sched->subject_id])
                    : route('home-assets.show', $sched->subject_id);
              @endphp
              <a href="{{ $targetUrl }}" class="list-group-item list-group-item-action px-0 py-2 bg-transparent border-bottom text-decoration-none">
                <div class="d-flex justify-content-between align-items-baseline">
                  <div class="fw-semibold text-dark">{{ $sched->title }}</div>
                  <span class="chip {{ $sched->status_chip_class }} py-0 px-1" style="font-size: 0.65rem;">
                    {{ $sched->status_label }}
                  </span>
                </div>
                <div class="text-muted" style="font-size: 0.72rem;">
                  {{ $sched->subject?->name ?? 'Aset' }} • 
                  @if ($sched->trigger_type === 'kilometer')
                    Target: {{ number_format($sched->next_due_km) }} KM
                  @else
                    {{ $sched->next_due_date ? $sched->next_due_date->format('d M Y') : 'Segera' }}
                  @endif
                </div>
              </a>
            @endforeach
          @else
            <div class="list-group-item px-0 py-2 bg-transparent text-muted small">
              Tidak ada jadwal perawatan mendesak saat ini.
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>
</header>
