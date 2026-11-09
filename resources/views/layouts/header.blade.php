<header class="top-header">
  <div class="d-flex align-items-center gap-3">
    <button class="btn btn-glass d-lg-none p-1.5" id="sidebarToggle">
      <i class="bi bi-list fs-5"></i>
    </button>
    <div class="input-group d-none d-md-flex" style="width: 280px;">
      <span class="input-group-text bg-transparent border-end-0 text-muted ps-3" style="border-color: rgba(203, 213, 225, 0.6);"><i class="bi bi-search" style="font-size: 0.8rem;"></i></span>
      <input type="text" class="form-control border-start-0 ps-1" placeholder="Cari aset atau kendaraan..." style="background: rgba(255, 255, 255, 0.5);">
    </div>
  </div>

  <div class="d-flex align-items-center gap-2">
    <!-- Quick Action Button Dropdown -->
    <div class="dropdown">
      <button class="btn btn-dark d-flex align-items-center gap-2 px-3 py-1.5 rounded-3" type="button" data-bs-toggle="dropdown" style="font-size: 0.85rem;">
        <i class="bi bi-plus-lg"></i>
        <span>Catat Mandiri</span>
      </button>
      <ul class="dropdown-menu dropdown-menu-end glass-panel border-0 p-2 shadow-sm" style="min-width: 210px;">
        <li><a class="dropdown-item py-1.5 rounded-2 small" href="#" data-bs-toggle="modal" data-bs-target="#modalAsetBaru"><i class="bi bi-house me-2 text-muted"></i>Aset Rumah Baru</a></li>
        <li><a class="dropdown-item py-1.5 rounded-2 small" href="#" data-bs-toggle="modal" data-bs-target="#modalServisBaru"><i class="bi bi-wrench me-2 text-muted"></i>Jadwal Perawatan Baru</a></li>
        <li><a class="dropdown-item py-1.5 rounded-2 small" href="#" data-bs-toggle="modal" data-bs-target="#modalUpdateKM"><i class="bi bi-speedometer2 me-2 text-muted"></i>Update Odometer (KM)</a></li>
        <li><a class="dropdown-item py-1.5 rounded-2 small" href="#" data-bs-toggle="modal" data-bs-target="#modalLogBBM"><i class="bi bi-fuel-pump me-2 text-muted"></i>Catat Pengisian BBM</a></li>
        <li><hr class="dropdown-divider my-1"></li>
        <li><a class="dropdown-item py-1.5 rounded-2 small text-danger" href="#"><i class="bi bi-exclamation-triangle me-2"></i>Catat Kerusakan</a></li>
      </ul>
    </div>

    <!-- Notification Bell -->
    <div class="dropdown">
      <button class="btn btn-glass rounded-3 position-relative p-2" type="button" data-bs-toggle="dropdown">
        <i class="bi bi-bell text-secondary" style="font-size: 0.95rem;"></i>
        <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle"></span>
      </button>
      <div class="dropdown-menu dropdown-menu-end glass-panel border-0 p-3 shadow-lg" style="width: 310px;">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <span class="fw-bold small text-dark">Pengingat Anda</span>
          <span class="chip chip-warning">3 perlu cek</span>
        </div>
        <div class="list-group list-group-flush" style="font-size: 0.8rem;">
          <a href="#" class="list-group-item list-group-item-action px-0 py-2 bg-transparent border-bottom">
            <div class="fw-semibold text-dark">AC Ruang Tamu</div>
            <div class="text-muted" style="font-size: 0.72rem;">Jadwal cuci filter rutin hari ini</div>
          </a>
          <a href="#" class="list-group-item list-group-item-action px-0 py-2 bg-transparent border-bottom">
            <div class="fw-semibold text-dark">Toyota Avanza</div>
            <div class="text-muted" style="font-size: 0.72rem;">Sisa 180 KM menuju ganti oli mesin</div>
          </a>
          <a href="#" class="list-group-item list-group-item-action px-0 py-2 bg-transparent">
            <div class="fw-semibold text-dark">Honda Vario 160</div>
            <div class="text-muted" style="font-size: 0.72rem;">Jatuh tempo pajak tahunan dalam 14 hari</div>
          </a>
        </div>
      </div>
    </div>
  </div>
</header>
