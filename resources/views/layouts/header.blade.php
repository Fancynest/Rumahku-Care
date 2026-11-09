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
      <button class="btn btn-glass rounded-3 position-relative p-2" type="button" data-bs-toggle="dropdown" aria-label="Notifikasi" style="min-height: 40px; min-width: 40px; display: inline-flex; align-items: center; justify-content: center;">
        <i class="bi bi-bell text-secondary" style="font-size: 0.95rem;"></i>
        @if (isset($urgentSchedules) && $urgentSchedules->isNotEmpty())
          <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle"></span>
        @endif
      </button>
      <div class="dropdown-menu dropdown-menu-end glass-panel border-0 p-3 shadow-sm" style="width: 330px;">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <span class="fw-bold small text-dark">Pemberitahuan Perawatan</span>
          @if (isset($urgentSchedules) && $urgentSchedules->isNotEmpty())
            <span class="chip chip-urgent py-0 px-1.5">{{ $urgentSchedules->count() }} Perlu Tindakan</span>
          @else
            <span class="chip chip-success py-0 px-1.5">Semua Prima</span>
          @endif
        </div>
        <div class="list-group list-group-flush" style="font-size: 0.8rem;">
          @if (isset($urgentSchedules) && $urgentSchedules->isNotEmpty())
            @foreach ($urgentSchedules as $sched)
              @php
                $targetUrl = $sched->url ?? ($sched->subject_type === App\Models\Vehicle::class
                    ? route('vehicles.index', ['v' => $sched->subject_id])
                    : route('home-assets.show', $sched->subject_id));
              @endphp
              <a href="{{ $targetUrl }}" class="list-group-item list-group-item-action px-0 py-2 bg-transparent border-bottom text-decoration-none">
                <div class="d-flex justify-content-between align-items-baseline mb-0.5">
                  <div class="fw-semibold text-dark text-truncate" style="max-width: 170px;">{{ $sched->title }}</div>
                  <div class="d-flex align-items-center gap-1">
                    @if ($sched->type && $sched->type !== 'jadwal')
                      <span class="badge bg-secondary-subtle text-secondary py-0 px-1 text-capitalize" style="font-size: 0.6rem;">
                        {{ $sched->type }}
                      </span>
                    @endif
                    @if ($sched->priority && $sched->priority !== 'normal')
                      <span class="badge {{ $sched->priority === 'darurat' ? 'bg-danger text-white' : 'bg-warning-subtle text-warning-emphasis' }} py-0 px-1 text-capitalize" style="font-size: 0.6rem;">
                        {{ $sched->priority }}
                      </span>
                    @endif
                    <span class="chip {{ $sched->status_chip_class }} py-0 px-1" style="font-size: 0.65rem;">
                      {{ $sched->status_label }}
                    </span>
                  </div>
                </div>
                <div class="text-muted" style="font-size: 0.72rem;">
                  {{ $sched->subject_name }} • {{ $sched->due_label ?? ($sched->next_due_date ? $sched->next_due_date->format('d/m/Y') : 'Segera') }}
                </div>
              </a>
            @endforeach
            <div class="text-center pt-2">
              <a href="{{ route('schedules.index') }}" class="text-dark small text-decoration-none fw-medium" style="font-size: 0.78rem;">
                Lihat Semua Jadwal <i class="bi bi-arrow-right ms-0.5"></i>
              </a>
            </div>
          @else
            <div class="list-group-item px-0 py-3 bg-transparent text-muted small text-center">
              Tidak ada jadwal perawatan mendesak saat ini.
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>
</header>
