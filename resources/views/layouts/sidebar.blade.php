<aside class="sidebar" id="sidebar">
  <div class="sidebar-brand justify-content-between">
    <div class="d-flex align-items-center gap-2.5 overflow-hidden">
      <div class="brand-symbol">
        <i class="bi bi-shield-check"></i>
      </div>
      <div class="overflow-hidden">
        <h6 class="brand-text-title text-truncate">Rumahku Care</h6>
        <span class="brand-text-sub text-truncate">Personal Asset Manager</span>
      </div>
    </div>
    <button type="button" class="btn btn-sm btn-touch text-secondary d-lg-none p-1 ms-auto" id="sidebarClose" aria-label="Tutup Menu">
      <i class="bi bi-x-lg fs-5"></i>
    </button>
  </div>

  <nav class="sidebar-menu pt-3">
    <a href="{{ route('dashboard') }}" class="nav-item-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
      <i class="bi bi-house"></i>
      <span>Beranda</span>
    </a>

    <a href="{{ route('home-assets.index') }}" class="nav-item-link {{ request()->routeIs('home-assets.*') ? 'active' : '' }}">
      <i class="bi bi-grid"></i>
      <span>Rumah</span>
    </a>

    <a href="{{ route('vehicles.index') }}" class="nav-item-link {{ request()->routeIs('vehicles.*') ? 'active' : '' }}">
      <i class="bi bi-car-front"></i>
      <span>Kendaraan</span>
    </a>

    <a href="{{ route('schedules.index') }}" class="nav-item-link {{ request()->routeIs('schedules.*') ? 'active' : '' }}">
      <i class="bi bi-calendar-check"></i>
      <span>Jadwal</span>
    </a>

    <a href="{{ route('finance.index') }}" class="nav-item-link {{ request()->routeIs('finance.*') ? 'active' : '' }}">
      <i class="bi bi-wallet2"></i>
      <span>Keuangan</span>
    </a>
  </nav>

  <div class="p-3 border-top border-slate-200 d-flex flex-column gap-2 mt-auto">
    <div class="px-1">
      <a href="{{ route('technicians.index') }}" class="text-decoration-none text-muted small d-inline-flex align-items-center gap-1.5 py-1 text-xs">
        <i class="bi bi-person-lines-fill"></i>
        <span>Kontak Teknisi</span>
      </a>
    </div>
    @auth
      <div class="d-flex align-items-center gap-2 pt-1">
        <a href="{{ route('profile.edit') }}" class="d-flex align-items-center gap-2 text-decoration-none text-dark flex-grow-1 overflow-hidden" title="Pengaturan Profil">
          <div class="avatar-md bg-slate-200 text-slate-700">
            {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
          </div>
          <div class="overflow-hidden me-auto">
            <div class="fw-semibold text-truncate text-dark small">{{ Auth::user()->name }}</div>
            <div class="text-muted text-truncate text-2xs">{{ Auth::user()->email }}</div>
          </div>
        </a>
        <form action="{{ route('logout') }}" method="POST" class="m-0">
          @csrf
          <button type="submit" class="btn btn-glass btn-touch btn-sm p-1.5 text-danger" title="Keluar" aria-label="Keluar">
            <i class="bi bi-box-arrow-right"></i>
          </button>
        </form>
      </div>
    @else
      <a href="{{ route('login') }}" class="btn btn-dark btn-sm w-100">
        <i class="bi bi-box-arrow-in-right me-1.5"></i> Masuk
      </a>
    @endauth
  </div>
</aside>
