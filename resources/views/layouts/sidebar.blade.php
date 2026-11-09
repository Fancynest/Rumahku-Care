<aside class="sidebar" id="sidebar">
  <div class="sidebar-brand">
    <div class="brand-symbol">
      <i class="bi bi-shield-check"></i>
    </div>
    <div>
      <h6 class="brand-text-title">Rumahku Care</h6>
      <span class="brand-text-sub">Personal Asset Manager</span>
    </div>
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

    <a href="{{ route('technicians.index') }}" class="nav-item-link {{ request()->routeIs('technicians.*') ? 'active' : '' }}">
      <i class="bi bi-wrench-adjustable"></i>
      <span>Servis & Kontak</span>
    </a>
  </nav>

  <div class="p-3 border-top border-slate-200 d-flex align-items-center gap-2 mt-auto" style="border-color: rgba(226, 232, 240, 0.6) !important;">
    <div class="rounded-circle bg-slate-200 text-slate-700 d-flex align-items-center justify-content-center fw-bold" style="width: 34px; height: 34px; font-size: 0.8rem;">
      GA
    </div>
    <div class="overflow-hidden">
      <div class="fw-semibold text-truncate text-dark" style="font-size: 0.85rem;">Gabriel</div>
      <div class="text-muted" style="font-size: 0.72rem;">Akun Personal</div>
    </div>
  </div>
</aside>
