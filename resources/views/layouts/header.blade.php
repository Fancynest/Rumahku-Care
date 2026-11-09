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
        @if (isset($urgentVehicles) && $urgentVehicles->isNotEmpty())
          <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle"></span>
        @endif
      </button>
      <div class="dropdown-menu dropdown-menu-end glass-panel border-0 p-3 shadow-sm" style="width: 290px;">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <span class="fw-bold small text-dark">Pemberitahuan</span>
          @if (isset($urgentVehicles) && $urgentVehicles->isNotEmpty())
            <span class="chip chip-urgent py-0 px-1.5">{{ $urgentVehicles->count() }} Perlu Servis</span>
          @else
            <span class="chip chip-success py-0 px-1.5">Semua Prima</span>
          @endif
        </div>
        <div class="list-group list-group-flush" style="font-size: 0.8rem;">
          <div class="list-group-item px-0 py-2 bg-transparent border-bottom">
            <div class="fw-semibold text-dark">AC Ruang Tamu</div>
            <div class="text-muted" style="font-size: 0.72rem;">Jadwal cuci filter rutin bulan ini</div>
          </div>
          @if (isset($urgentVehicles) && $urgentVehicles->isNotEmpty())
            @foreach ($urgentVehicles as $uv)
              <a href="{{ route('vehicles.index', ['v' => $uv->id]) }}" class="list-group-item list-group-item-action px-0 py-2 bg-transparent border-bottom text-decoration-none">
                <div class="fw-semibold text-danger">{{ $uv->name }} ({{ $uv->plate }})</div>
                <div class="text-muted" style="font-size: 0.72rem;">
                  Odometer {{ number_format($uv->odometer) }} KM — Waktunya servis berkala
                </div>
              </a>
            @endforeach
          @else
            <div class="list-group-item px-0 py-2 bg-transparent text-muted small">
              Tidak ada kendaraan yang perlu servis saat ini.
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>
</header>
