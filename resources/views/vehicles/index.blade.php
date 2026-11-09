@extends('layouts.app')

@section('title', 'Kendaraan Saya & 3D Showcase - Rumahku Care')

@section('content')
<!-- Page Header -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
  <div>
    <h4 class="fw-bold mb-1 text-dark" style="letter-spacing: -0.02em;">Kendaraan Saya & 3D Showcase</h4>
    <p class="text-muted mb-0 small">Monitoring kondisi fisik kendaraan, simulasi visual warna, odometer servis, dan dokumen STNK.</p>
  </div>
  <div class="d-flex gap-2">
    <button class="btn btn-glass btn-sm" data-bs-toggle="modal" data-bs-target="#modalUpdateKM">
      <i class="bi bi-speedometer2 me-1"></i> Update Odometer
    </button>
    <button class="btn btn-dark btn-sm d-flex align-items-center gap-1.5" data-bs-toggle="modal" data-bs-target="#modalTambahKendaraan">
      <i class="bi bi-plus-lg"></i>
      <span>Tambah Kendaraan</span>
    </button>
  </div>
</div>

<!-- ================= 3-COLUMN STUDIO LAYOUT ================= -->
<div class="row g-4 align-items-stretch">
  
  <!-- ================= LEFT COLUMN: STATS & UPCOMING SCHEDULE ================= -->
  <div class="col-xl-3 col-lg-4">
    <div class="d-flex flex-column gap-3 h-100">
      
      <!-- 2x2 Stat Cards Grid -->
      <div class="row g-2">
        <!-- Stat 1: Total Vehicles -->
        <div class="col-6">
          <div class="glass-panel p-3 h-100">
            <small class="text-muted d-block mb-1" style="font-size: 0.72rem;">Total Kendaraan</small>
            <div class="d-flex align-items-baseline gap-2">
              <h3 class="fw-bold text-dark mb-0">3</h3>
              <span class="chip chip-success py-0 px-1" style="font-size: 0.65rem;">+1 Baru</span>
            </div>
            <!-- Mini Bar Visual like Reference -->
            <div class="d-flex align-items-end gap-1 mt-2" style="height: 22px;">
              <div class="w-100 rounded-1 bg-slate-200" style="height: 35%;"></div>
              <div class="w-100 rounded-1 bg-slate-200" style="height: 60%;"></div>
              <div class="w-100 rounded-1 bg-dark" style="height: 100%;"></div>
              <div class="w-100 rounded-1 bg-slate-200" style="height: 45%;"></div>
              <div class="w-100 rounded-1 bg-slate-200" style="height: 70%;"></div>
            </div>
          </div>
        </div>

        <!-- Stat 2: Active / Siap Pakai -->
        <div class="col-6">
          <div class="glass-panel p-3 h-100">
            <small class="text-muted d-block mb-1" style="font-size: 0.72rem;">Kondisi Prima</small>
            <div class="d-flex align-items-baseline gap-2">
              <h3 class="fw-bold text-dark mb-0">2</h3>
              <span class="chip chip-success py-0 px-1" style="font-size: 0.65rem;">66.7%</span>
            </div>
            <div class="d-flex align-items-end gap-1 mt-2" style="height: 22px;">
              <div class="w-100 rounded-1 bg-slate-200" style="height: 40%;"></div>
              <div class="w-100 rounded-1 bg-success" style="height: 85%;"></div>
              <div class="w-100 rounded-1 bg-slate-200" style="height: 50%;"></div>
              <div class="w-100 rounded-1 bg-slate-200" style="height: 30%;"></div>
            </div>
          </div>
        </div>

        <!-- Stat 3: Butuh Servis Segera -->
        <div class="col-6">
          <div class="glass-panel p-3 h-100">
            <small class="text-muted d-block mb-1" style="font-size: 0.72rem;">Waktunya Servis</small>
            <div class="d-flex align-items-baseline gap-2">
              <h3 class="fw-bold text-danger mb-0">1</h3>
              <span class="chip chip-urgent py-0 px-1" style="font-size: 0.65rem;">Avanza</span>
            </div>
            <div class="d-flex align-items-end gap-1 mt-2" style="height: 22px;">
              <div class="w-100 rounded-1 bg-slate-200" style="height: 20%;"></div>
              <div class="w-100 rounded-1 bg-danger" style="height: 90%;"></div>
              <div class="w-100 rounded-1 bg-slate-200" style="height: 30%;"></div>
            </div>
          </div>
        </div>

        <!-- Stat 4: Pajak & STNK -->
        <div class="col-6">
          <div class="glass-panel p-3 h-100">
            <small class="text-muted d-block mb-1" style="font-size: 0.72rem;">Pajak Mendekati</small>
            <div class="d-flex align-items-baseline gap-2">
              <h3 class="fw-bold text-warning mb-0">1</h3>
              <span class="chip chip-warning py-0 px-1" style="font-size: 0.65rem;">H-14</span>
            </div>
            <div class="d-flex align-items-end gap-1 mt-2" style="height: 22px;">
              <div class="w-100 rounded-1 bg-slate-200" style="height: 40%;"></div>
              <div class="w-100 rounded-1 bg-warning" style="height: 75%;"></div>
              <div class="w-100 rounded-1 bg-slate-200" style="height: 50%;"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Upcoming Schedule Timeline Card -->
      <div class="glass-panel p-3.5 flex-grow-1">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h6 class="fw-bold text-dark mb-0" style="font-size: 0.85rem;">Jadwal Servis Mendatang</h6>
          <span class="chip chip-neutral" style="font-size: 0.65rem;">Kalender</span>
        </div>

        <div class="schedule-item">
          <span class="schedule-dot bg-danger"></span>
          <div class="d-flex justify-content-between">
            <strong class="text-danger small">Minggu Ini (Sisa 180 KM)</strong>
          </div>
          <div class="fw-semibold text-dark small">Toyota Avanza</div>
          <small class="text-muted" style="font-size: 0.72rem;">Ganti Oli Mesin & Filter Oli</small>
        </div>

        <div class="schedule-item">
          <span class="schedule-dot bg-warning"></span>
          <div class="d-flex justify-content-between">
            <strong class="text-warning small">23 November 2026</strong>
          </div>
          <div class="fw-semibold text-dark small">Honda Vario 160</div>
          <small class="text-muted" style="font-size: 0.72rem;">Jatuh Tempo Pajak STNK Tahunan</small>
        </div>

        <div class="schedule-item mb-0">
          <span class="schedule-dot bg-dark"></span>
          <div class="d-flex justify-content-between">
            <strong class="text-dark small">Desember 2026</strong>
          </div>
          <div class="fw-semibold text-dark small">Honda Beat Street</div>
          <small class="text-muted" style="font-size: 0.72rem;">Pemeriksaan Kampas Rem & Busi</small>
        </div>
      </div>

    </div>
  </div>

  <!-- ================= CENTER COLUMN: TRUE 3D VEHICLE STUDIO ================= -->
  <div class="col-xl-6 col-lg-8">
    <div class="glass-panel p-4 h-100 d-flex flex-column justify-content-between position-relative">
      
      <!-- Studio Top Bar: Vehicle Selector Title, Color Palette & Angle Switcher -->
      <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2 pb-3 border-bottom border-slate-200">
        
        <!-- Active Vehicle Dropdown -->
        <div class="dropdown">
          <button class="btn btn-link text-dark p-0 fw-bold fs-5 text-decoration-none d-flex align-items-center gap-2" data-bs-toggle="dropdown" id="activeVehicleTitle">
            <span>HONDA BEAT STREET</span>
            <i class="bi bi-chevron-down fs-6 text-muted"></i>
          </button>
          <ul class="dropdown-menu glass-panel border-0 shadow-lg p-2" style="min-width: 250px;">
            <li>
              <a class="dropdown-item py-2 rounded-2 small d-flex justify-content-between align-items-center" href="javascript:void(0)" onclick="selectVehicle('beat')">
                <div>
                  <div class="fw-bold">Honda Beat Street</div>
                  <small class="text-muted font-monospace">B 2341 KLS • Motor</small>
                </div>
                <span class="chip chip-success">Aktif</span>
              </a>
            </li>
            <li>
              <a class="dropdown-item py-2 rounded-2 small d-flex justify-content-between align-items-center" href="javascript:void(0)" onclick="selectVehicle('prado')">
                <div>
                  <div class="fw-bold">Toyota Prado TX-L</div>
                  <small class="text-muted font-monospace">B 9999 KRA • SUV</small>
                </div>
                <span class="chip chip-success">Aktif</span>
              </a>
            </li>
            <li>
              <a class="dropdown-item py-2 rounded-2 small d-flex justify-content-between align-items-center" href="javascript:void(0)" onclick="selectVehicle('avanza')">
                <div>
                  <div class="fw-bold">Toyota Avanza 1.5 G</div>
                  <small class="text-muted font-monospace">B 1234 GAB • Mobil</small>
                </div>
                <span class="chip chip-urgent">Servis</span>
              </a>
            </li>
            <li>
              <a class="dropdown-item py-2 rounded-2 small d-flex justify-content-between align-items-center" href="javascript:void(0)" onclick="selectVehicle('vario')">
                <div>
                  <div class="fw-bold">Honda Vario 160 ABS</div>
                  <small class="text-muted font-monospace">B 5678 JKT • Motor</small>
                </div>
                <span class="chip chip-warning">Pajak</span>
              </a>
            </li>
          </ul>
        </div>

        <!-- Color Swatch Customizer -->
        <div class="d-flex align-items-center gap-2">
          <small class="text-muted small d-none d-sm-inline">Warna:</small>
          <div class="color-swatch-group">
            <button type="button" class="color-swatch-btn active" style="background-color: #0f172a;" title="Hitam Glossy" onclick="changeVehicleColor('black', this)"></button>
            <button type="button" class="color-swatch-btn" style="background-color: #f8fafc;" title="Putih Mutiara" onclick="changeVehicleColor('white', this)"></button>
            <button type="button" class="color-swatch-btn" style="background-color: #dc2626;" title="Merah Racing" onclick="changeVehicleColor('red', this)"></button>
            <button type="button" class="color-swatch-btn" style="background-color: #2563eb;" title="Biru Sporty" onclick="changeVehicleColor('blue', this)"></button>
            <button type="button" class="color-swatch-btn" style="background-color: #64748b;" title="Silver Metalik" onclick="changeVehicleColor('silver', this)"></button>
          </div>
        </div>

        <!-- Angle View Selector (Thumbnails exactly like reference image top right) -->
        <div class="d-flex align-items-center gap-1.5" id="angleThumbnailsContainer">
          <button class="angle-thumbnail-btn active" title="Tampak Depan Tiga Perempat" onclick="changeVehicleAngle('front', this)">
            <img src="{{ asset('images/vehicles/honda-beat.png') }}" id="thumbFront" alt="Front">
          </button>
          <button class="angle-thumbnail-btn" title="Tampak Samping" onclick="changeVehicleAngle('side', this)">
            <img src="{{ asset('images/vehicles/honda-beat.png') }}" id="thumbSide" style="transform: scaleX(-1);" alt="Side">
          </button>
          <button class="angle-thumbnail-btn" title="Tampak Belakang" onclick="changeVehicleAngle('rear', this)">
            <img src="{{ asset('images/vehicles/honda-beat.png') }}" id="thumbRear" style="filter: brightness(0.9);" alt="Rear">
          </button>
        </div>

      </div>

      <!-- Studio Stage with 100% Background-Free 3D Vehicle Render -->
      <div class="vehicle-studio-stage my-auto" id="studioStage">
        
        <!-- Live Plate & Odometer Overlay -->
        <div class="position-absolute top-0 start-0 m-3 z-1">
          <span class="glass-pill" id="stagePlate"><i class="bi bi-shield-check text-success"></i> B 2341 KLS</span>
          <span class="glass-pill ms-1" id="stageKm">8,450 KM</span>
          <span class="chip chip-success ms-1 py-0 px-2" id="stageStatus">Prima</span>
        </div>

        <!-- Interactive 3D Model Wrapper with Perspective Ground Shadow -->
        <div class="vehicle-3d-model-wrapper" id="vehicleModelWrapper">
          <img src="{{ asset('images/vehicles/honda-beat.png') }}" 
               class="vehicle-3d-img" 
               id="vehicleImage" 
               alt="Vehicle 3D Model">
          <div class="vehicle-ground-shadow" id="groundShadow"></div>
        </div>

        <small class="text-muted position-absolute bottom-0 end-0 m-2" style="font-size: 0.68rem; opacity: 0.7;">
          <i class="bi bi-arrows-move me-1"></i>Gerakkan mouse untuk rotasi 3D
        </small>
      </div>

      <!-- Studio Bottom Bar: Quick Action Pills (Exact like reference image) -->
      <div class="pt-3 border-top border-slate-200">
        <div class="d-flex flex-wrap justify-content-center gap-2">
          <button class="btn btn-glass btn-sm rounded-pill px-3 py-1.5" data-bs-toggle="modal" data-bs-target="#modalDetailSpesifikasi">
            <i class="bi bi-sliders me-1.5"></i> Detail Kendaraan
          </button>
          <button class="btn btn-glass btn-sm rounded-pill px-3 py-1.5" data-bs-toggle="modal" data-bs-target="#modalHealthReport">
            <i class="bi bi-heart-pulse me-1.5 text-danger"></i> Kondisi Komponen
          </button>
          <button class="btn btn-glass btn-sm rounded-pill px-3 py-1.5" data-bs-toggle="modal" data-bs-target="#modalDokumenSTNK">
            <i class="bi bi-file-earmark-check me-1.5 text-primary"></i> Dokumen & Pajak
          </button>
          <button class="btn btn-dark btn-sm rounded-pill px-3 py-1.5" data-bs-toggle="modal" data-bs-target="#modalUpdateKM">
            <i class="bi bi-wrench me-1.5"></i> Catat Servis
          </button>
        </div>
      </div>

    </div>
  </div>

  <!-- ================= RIGHT COLUMN: QUICK FLEET OVERVIEW ================= -->
  <div class="col-xl-3 col-lg-12">
    <div class="glass-panel p-3.5 h-100 d-flex flex-column justify-content-between">
      <div>
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div>
            <h6 class="fw-bold text-dark mb-0" style="font-size: 0.88rem;">Daftar Garasi Saya</h6>
            <small class="text-muted" style="font-size: 0.72rem;">Klik kendaraan untuk melihat model 3D</small>
          </div>
          <button class="btn btn-link text-muted p-0" data-bs-toggle="modal" data-bs-target="#modalTambahKendaraan"><i class="bi bi-plus-circle fs-5"></i></button>
        </div>

        <div class="d-flex flex-column gap-2" id="fleetOverviewList">
          
          <!-- Item 1: Honda Beat Street -->
          <div class="fleet-item-row active" onclick="selectVehicle('beat')" id="fleetItem-beat">
            <div class="d-flex align-items-center gap-2.5">
              <img src="{{ asset('images/vehicles/honda-beat.png') }}" class="fleet-thumb-img" alt="Beat">
              <div>
                <div class="fw-bold text-dark" style="font-size: 0.8rem;">HONDA BEAT</div>
                <small class="text-muted font-monospace" style="font-size: 0.7rem;">B 2341 KLS • 8,450 KM</small>
              </div>
            </div>
            <span class="chip chip-success py-0 px-1.5" style="font-size: 0.65rem;">Active</span>
          </div>

          <!-- Item 2: Toyota Prado (Direct match to reference image!) -->
          <div class="fleet-item-row" onclick="selectVehicle('prado')" id="fleetItem-prado">
            <div class="d-flex align-items-center gap-2.5">
              <img src="{{ asset('images/vehicles/toyota-prado.png') }}" class="fleet-thumb-img" alt="Prado">
              <div>
                <div class="fw-bold text-dark" style="font-size: 0.8rem;">TOYOTA PRADO</div>
                <small class="text-muted font-monospace" style="font-size: 0.7rem;">B 9999 KRA • 68,200 KM</small>
              </div>
            </div>
            <span class="chip chip-success py-0 px-1.5" style="font-size: 0.65rem;">Active</span>
          </div>

          <!-- Item 3: Toyota Avanza -->
          <div class="fleet-item-row" onclick="selectVehicle('avanza')" id="fleetItem-avanza">
            <div class="d-flex align-items-center gap-2.5">
              <img src="{{ asset('images/vehicles/toyota-avanza.png') }}" class="fleet-thumb-img" alt="Avanza">
              <div>
                <div class="fw-bold text-dark" style="font-size: 0.8rem;">TOYOTA AVANZA</div>
                <small class="text-muted font-monospace" style="font-size: 0.7rem;">B 1234 GAB • 44,820 KM</small>
              </div>
            </div>
            <span class="chip chip-urgent py-0 px-1.5" style="font-size: 0.65rem;">Service</span>
          </div>

          <!-- Item 4: Honda Vario 160 -->
          <div class="fleet-item-row" onclick="selectVehicle('vario')" id="fleetItem-vario">
            <div class="d-flex align-items-center gap-2.5">
              <img src="{{ asset('images/vehicles/honda-motorcycle.png') }}" class="fleet-thumb-img" alt="Vario">
              <div>
                <div class="fw-bold text-dark" style="font-size: 0.8rem;">HONDA VARIO 160</div>
                <small class="text-muted font-monospace" style="font-size: 0.7rem;">B 5678 JKT • 12,150 KM</small>
              </div>
            </div>
            <span class="chip chip-warning py-0 px-1.5" style="font-size: 0.65rem;">Pajak H-14</span>
          </div>

        </div>
      </div>

      <div class="pt-3 border-top border-slate-200 mt-3 text-center">
        <a href="{{ route('technicians.index') }}" class="btn btn-glass btn-sm w-100" style="font-size: 0.78rem;">
          <i class="bi bi-person-lines-fill me-1"></i> Kontak Bengkel Langganan
        </a>
      </div>

    </div>
  </div>

</div>
@endsection

@section('modals')
<!-- Modal Tambah Kendaraan Baru -->
<div class="modal fade" id="modalTambahKendaraan" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content glass-panel border-0 shadow-lg p-3">
      <div class="modal-header border-bottom border-slate-200 pb-2">
        <h6 class="modal-title fw-bold text-dark">Daftarkan Kendaraan Baru ke Garasi</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-3">
        <form>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label small fw-semibold text-muted">Model Kendaraan <span class="text-danger">*</span></label>
              <select class="form-select" required>
                <optgroup label="Sepeda Motor">
                  <option selected>Honda Beat Street / Deluxe</option>
                  <option>Honda Vario 125 / 160</option>
                  <option>Honda Scoopy</option>
                  <option>Yamaha NMAX 155</option>
                  <option>Yamaha Aerox 155</option>
                </optgroup>
                <optgroup label="Mobil">
                  <option>Toyota Prado TX-L</option>
                  <option>Toyota Avanza 1.3 / 1.5</option>
                  <option>Toyota Innova Zenix</option>
                  <option>Honda Brio Satya / RS</option>
                  <option>Mitsubishi Xpander</option>
                </optgroup>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold text-muted">Pilihan Warna Awal <span class="text-danger">*</span></label>
              <select class="form-select" required>
                <option>Hitam (Glossy / Matte Black)</option>
                <option>Putih (Pearl White)</option>
                <option>Merah (Racing Red)</option>
                <option>Biru (Sporty Blue)</option>
                <option>Silver Metalik</option>
              </select>
            </div>

            <div class="col-md-4">
              <label class="form-label small fw-semibold text-muted">Plat Nomor (No Polisi) <span class="text-danger">*</span></label>
              <input type="text" class="form-control font-monospace" placeholder="Contoh: B 2341 KLS" required>
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold text-muted">Tahun Pembuatan</label>
              <input type="number" class="form-control" placeholder="Contoh: 2023">
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold text-muted">Odometer Terkini (KM) <span class="text-danger">*</span></label>
              <input type="number" class="form-control fw-bold font-monospace" placeholder="8450" required>
            </div>

            <div class="col-md-6">
              <label class="form-label small fw-semibold text-muted">Jenis Bahan Bakar</label>
              <select class="form-select">
                <option>Bensin (Pertalite / Pertamax)</option>
                <option>Diesel / Solar</option>
                <option>Kendaraan Listrik (EV)</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold text-muted">Bengkel Langganan</label>
              <input type="text" class="form-control" placeholder="Contoh: AHASS Cipete / Berkah Motor">
            </div>
          </div>

          <div class="d-flex justify-content-end gap-2 mt-4 pt-2 border-top border-slate-200">
            <button type="button" class="btn btn-glass btn-sm" data-bs-dismiss="modal">Batal</button>
            <button type="button" class="btn btn-dark btn-sm px-4" data-bs-dismiss="modal">Simpan ke Garasi</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Modal Update Odometer -->
<div class="modal fade" id="modalUpdateKM" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content glass-panel border-0 shadow-lg p-3">
      <div class="modal-header border-bottom border-slate-200 pb-2">
        <h6 class="modal-title fw-bold text-dark">Update Kilometer Odometer</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-3">
        <form>
          <div class="mb-3">
            <label class="form-label small fw-semibold text-muted">Pilih Kendaraan</label>
            <select class="form-select">
              <option>Honda Beat Street (B 2341 KLS) - 8.450 KM</option>
              <option>Toyota Prado TX-L (B 9999 KRA) - 68.200 KM</option>
              <option>Toyota Avanza 1.5 G (B 1234 GAB) - 44.820 KM</option>
              <option>Honda Vario 160 (B 5678 JKT) - 12.150 KM</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold text-muted">Angka Speedometer Terkini (KM)</label>
            <input type="number" class="form-control form-control-lg fw-bold font-monospace" placeholder="8550">
            <small class="text-muted" style="font-size: 0.72rem;">Sistem akan langsung menghitung sisa jarak aman menuju servis ganti oli berikutnya.</small>
          </div>
          <button type="button" class="btn btn-dark w-100 py-2 fw-medium rounded-3" data-bs-dismiss="modal">Simpan Pembaruan Odo</button>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Modal Health Report / Komponen -->
<div class="modal fade" id="modalHealthReport" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content glass-panel border-0 shadow-lg p-3">
      <div class="modal-header border-bottom border-slate-200 pb-2">
        <h6 class="modal-title fw-bold text-dark"><i class="bi bi-heart-pulse text-danger me-2"></i>Laporan Kondisi Komponen</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-3 small">
        <div class="p-2.5 rounded-3 bg-white bg-opacity-60 border mb-2 d-flex justify-content-between align-items-center">
          <div>
            <div class="fw-semibold text-dark">Oli Mesin (MPX2)</div>
            <small class="text-muted">Diganti pada 6.000 KM</small>
          </div>
          <span class="chip chip-success">Bagus (Sisa 1.550 KM)</span>
        </div>
        <div class="p-2.5 rounded-3 bg-white bg-opacity-60 border mb-2 d-flex justify-content-between align-items-center">
          <div>
            <div class="fw-semibold text-dark">Roller & Vanbelt CVT</div>
            <small class="text-muted">Target cek pada 16.000 KM</small>
          </div>
          <span class="chip chip-success">Normal</span>
        </div>
        <div class="p-2.5 rounded-3 bg-white bg-opacity-60 border mb-2 d-flex justify-content-between align-items-center">
          <div>
            <div class="fw-semibold text-dark">Kampas Rem Depan / Belakang</div>
            <small class="text-muted">Ketebalan masih aman</small>
          </div>
          <span class="chip chip-neutral">75% Tebal</span>
        </div>
        <div class="p-2.5 rounded-3 bg-white bg-opacity-60 border d-flex justify-content-between align-items-center">
          <div>
            <div class="fw-semibold text-dark">Aki (Battery 12V 5Ah)</div>
            <small class="text-muted">Tegangan 12.6V</small>
          </div>
          <span class="chip chip-success">Optimal</span>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  // Pure Transparent Cutout Vehicle Database (No backgrounds!)
  const vehicleData = {
    beat: {
      title: 'HONDA BEAT STREET',
      plate: 'B 2341 KLS',
      km: '8,450 KM',
      status: 'Prima',
      statusChip: 'chip-success',
      image: "{{ asset('images/vehicles/honda-beat.png') }}",
      colorFilters: {
        black: 'brightness(0.85) contrast(1.15) grayscale(0.5)',
        white: 'brightness(1.15) contrast(1.0) saturate(0.8)',
        red: 'hue-rotate(-45deg) saturate(2) contrast(1.1)',
        blue: 'hue-rotate(180deg) saturate(1.8) contrast(1.1)',
        silver: 'grayscale(0.9) brightness(1.05) contrast(1.1)'
      }
    },
    prado: {
      title: 'TOYOTA PRADO TX-L',
      plate: 'B 9999 KRA',
      km: '68,200 KM',
      status: 'Prima',
      statusChip: 'chip-success',
      image: "{{ asset('images/vehicles/toyota-prado.png') }}",
      colorFilters: {
        black: 'brightness(0.65) contrast(1.25) grayscale(0.8)',
        white: 'none',
        red: 'hue-rotate(-40deg) saturate(2.4) brightness(0.95)',
        blue: 'hue-rotate(185deg) saturate(1.9)',
        silver: 'grayscale(0.95) brightness(1.1)'
      }
    },
    avanza: {
      title: 'TOYOTA AVANZA 1.5 G',
      plate: 'B 1234 GAB',
      km: '44,820 KM',
      status: 'Waktunya Ganti Oli',
      statusChip: 'chip-urgent',
      image: "{{ asset('images/vehicles/toyota-avanza.png') }}",
      colorFilters: {
        black: 'brightness(0.7) contrast(1.2) grayscale(0.8)',
        white: 'brightness(1.25)',
        red: 'hue-rotate(-35deg) saturate(2.1)',
        blue: 'hue-rotate(190deg) saturate(1.8)',
        silver: 'grayscale(1) brightness(1.1)'
      }
    },
    vario: {
      title: 'HONDA VARIO 160 ABS',
      plate: 'B 5678 JKT',
      km: '12,150 KM',
      status: 'Pajak H-14',
      statusChip: 'chip-warning',
      image: "{{ asset('images/vehicles/honda-motorcycle.png') }}",
      colorFilters: {
        black: 'brightness(0.8) grayscale(0.8)',
        white: 'brightness(1.2) saturate(0.6)',
        red: 'hue-rotate(-40deg) saturate(2)',
        blue: 'hue-rotate(180deg) saturate(1.8)',
        silver: 'grayscale(1) brightness(1.05)'
      }
    }
  };

  let currentVehicleKey = 'beat';
  let currentColorKey = 'black';

  function selectVehicle(key) {
    if (!vehicleData[key]) return;
    currentVehicleKey = key;
    const data = vehicleData[key];

    // Update title & stage details
    document.querySelector('#activeVehicleTitle span').innerText = data.title;
    document.getElementById('stagePlate').innerHTML = '<i class="bi bi-shield-check text-success"></i> ' + data.plate;
    document.getElementById('stageKm').innerText = data.km;
    
    const statusEl = document.getElementById('stageStatus');
    statusEl.innerText = data.status;
    statusEl.className = 'chip ms-1 py-0 px-2 ' + data.statusChip;

    // Update thumbnail images in top-right angle selector
    document.getElementById('thumbFront').src = data.image;
    document.getElementById('thumbSide').src = data.image;
    document.getElementById('thumbRear').src = data.image;

    // Smooth 3D stage transition
    const imgEl = document.getElementById('vehicleImage');
    const shadowEl = document.getElementById('groundShadow');
    imgEl.style.opacity = '0';
    imgEl.style.transform = 'scale(0.92) translateY(10px)';
    shadowEl.style.opacity = '0';

    setTimeout(() => {
      imgEl.src = data.image;
      applyColorFilter();
      imgEl.style.opacity = '1';
      imgEl.style.transform = 'scale(1) translateY(0px)';
      shadowEl.style.opacity = '1';
    }, 180);

    // Update fleet list highlight
    document.querySelectorAll('.fleet-item-row').forEach(el => el.classList.remove('active'));
    document.getElementById('fleetItem-' + key)?.classList.add('active');
  }

  function changeVehicleColor(colorKey, btnEl) {
    currentColorKey = colorKey;
    document.querySelectorAll('.color-swatch-btn').forEach(b => b.classList.remove('active'));
    btnEl.classList.add('active');
    applyColorFilter();
  }

  function applyColorFilter() {
    const data = vehicleData[currentVehicleKey];
    const filter = data.colorFilters[currentColorKey] || 'none';
    const imgEl = document.getElementById('vehicleImage');
    imgEl.style.filter = (filter === 'none' ? '' : filter) + ' drop-shadow(0 20px 22px rgba(15, 23, 42, 0.22))';
  }

  function changeVehicleAngle(angle, btnEl) {
    document.querySelectorAll('.angle-thumbnail-btn').forEach(b => b.classList.remove('active'));
    btnEl.classList.add('active');

    const wrapper = document.getElementById('vehicleModelWrapper');
    if (angle === 'side') {
      wrapper.style.transform = 'rotateY(22deg) scale(0.98)';
    } else if (angle === 'rear') {
      wrapper.style.transform = 'rotateY(40deg) scale(0.94)';
    } else {
      wrapper.style.transform = 'rotateY(0deg) scale(1)';
    }
  }

  // Interactive 3D Perspective Tilt on Mouse Movement (Gyroscope-like feel)
  const stage = document.getElementById('studioStage');
  const modelWrapper = document.getElementById('vehicleModelWrapper');

  if (stage && modelWrapper) {
    stage.addEventListener('mousemove', (e) => {
      const rect = stage.getBoundingClientRect();
      const x = e.clientX - rect.left - rect.width / 2;
      const y = e.clientY - rect.top - rect.height / 2;
      
      const rotateX = -(y / rect.height) * 14;
      const rotateY = (x / rect.width) * 20;

      modelWrapper.style.transform = `rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale(1.03)`;
    });

    stage.addEventListener('mouseleave', () => {
      modelWrapper.style.transform = 'rotateX(0deg) rotateY(0deg) scale(1)';
    });
  }

  // Initial load
  selectVehicle('beat');
</script>
@endpush
