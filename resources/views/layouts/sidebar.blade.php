<aside class="sidebar" id="sidebar">
  <div class="sidebar-brand">
    <div class="brand-symbol">
      <i class="bi bi-layers-half"></i>
    </div>
    <div>
      <h6 class="brand-text-title">Rumahku Care</h6>
      <span class="brand-text-sub">Personal Asset Manager</span>
    </div>
  </div>

  <div class="sidebar-menu">
    <div class="menu-section">Utama</div>
    <a href="{{ route('dashboard') }}" class="nav-item-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
      <i class="bi bi-grid"></i>
      <span>Dashboard</span>
    </a>

    <div class="menu-section">Rumah Saya</div>
    <a href="{{ route('home-assets.index') }}" class="nav-item-link {{ request()->routeIs('home-assets.*') ? 'active' : '' }}">
      <i class="bi bi-house"></i>
      <span>Aset & Ruangan</span>
    </a>
    <a href="#" class="nav-item-link">
      <i class="bi bi-check2-circle"></i>
      <span>Checklist Inspeksi</span>
    </a>
    <a href="#" class="nav-item-link">
      <i class="bi bi-exclamation-circle"></i>
      <span>Catatan Kerusakan</span>
    </a>
    <a href="#" class="nav-item-link">
      <i class="bi bi-calendar-check"></i>
      <span>Agenda Kebersihan</span>
    </a>

    <div class="menu-section">Kendaraan Saya</div>
    <a href="{{ route('vehicles.index') }}" class="nav-item-link {{ request()->routeIs('vehicles.*') ? 'active' : '' }}">
      <i class="bi bi-car-front"></i>
      <span>Data Kendaraan & 3D</span>
    </a>
    <a href="#" class="nav-item-link">
      <i class="bi bi-speedometer2"></i>
      <span>Servis & Odometer</span>
    </a>
    <a href="#" class="nav-item-link">
      <i class="bi bi-fuel-pump"></i>
      <span>Log Bahan Bakar (BBM)</span>
    </a>
    <a href="#" class="nav-item-link">
      <i class="bi bi-file-earmark-ruled"></i>
      <span>Pajak & STNK</span>
    </a>

    <div class="menu-section">Kontak & Rekap</div>
    <a href="{{ route('technicians.index') }}" class="nav-item-link {{ request()->routeIs('technicians.*') ? 'active' : '' }}">
      <i class="bi bi-person-lines-fill"></i>
      <span>Teknisi & Bengkel</span>
    </a>
    <a href="#" class="nav-item-link">
      <i class="bi bi-calendar3"></i>
      <span>Kalender Terpadu</span>
    </a>
    <a href="#" class="nav-item-link">
      <i class="bi bi-clock-history"></i>
      <span>Riwayat Servis</span>
    </a>
    <a href="#" class="nav-item-link">
      <i class="bi bi-wallet2"></i>
      <span>Biaya & Anggaran</span>
    </a>
  </div>

  <div class="p-3 border-top border-slate-200 d-flex align-items-center gap-2 mt-auto" style="border-color: rgba(226, 232, 240, 0.6) !important;">
    <div class="rounded-circle bg-slate-200 text-slate-700 d-flex align-items-center justify-content-center fw-bold" style="width: 32px; height: 32px; font-size: 0.75rem;">
      GA
    </div>
    <div class="overflow-hidden">
      <div class="fw-semibold text-truncate text-dark" style="font-size: 0.82rem;">Gabriel</div>
      <div class="text-muted" style="font-size: 0.7rem;">Akun Pribadi</div>
    </div>
    <a href="#" class="ms-auto text-secondary text-decoration-none" title="Keluar"><i class="bi bi-box-arrow-right" style="font-size: 0.9rem;"></i></a>
  </div>
</aside>
