@extends('layouts.app')

@section('title', 'Dashboard - Rumahku Care')

@section('content')
<!-- Page Header -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
  <div>
    <h4 class="fw-bold mb-1 text-dark" style="letter-spacing: -0.02em;">Dashboard Perawatan</h4>
    <p class="text-muted mb-0 small">Ringkasan kondisi aset rumah & kendaraan pribadi Anda per <strong>9 November 2026</strong>.</p>
  </div>
  <div class="d-flex gap-2">
    <button class="btn btn-glass btn-sm"><i class="bi bi-download me-1"></i> Ekspor Laporan</button>
    <button class="btn btn-glass btn-sm"><i class="bi bi-sliders me-1"></i> Preferensi</button>
  </div>
</div>

<!-- ================= SMART REMINDER GLASS ROW ================= -->
<div class="glass-panel p-4 mb-4">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <div class="d-flex align-items-center gap-2">
      <span class="chip chip-warning">
        <span class="chip-dot"></span> Smart Reminder
      </span>
      <span class="text-muted small">Peringatan otomatis berdasarkan interval waktu & jarak tempuh (KM)</span>
    </div>
    <a href="#" class="text-decoration-none small text-dark fw-medium">Lihat Kalender <i class="bi bi-arrow-right"></i></a>
  </div>

  <div class="row g-3">
    <!-- Reminder 1: AC Rumah -->
    <div class="col-lg-4 col-md-6">
      <div class="reminder-card h-100 d-flex flex-column justify-content-between">
        <div>
          <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="chip chip-urgent"><span class="chip-dot"></span> Hari Ini</span>
            <small class="text-muted">Ruang Tamu</small>
          </div>
          <h6 class="fw-bold mb-1 text-dark">Cuci Filter & Cek AC</h6>
          <p class="text-muted small mb-3">Terakhir dibersihkan 3 bulan lalu (9 Agustus 2026).</p>
        </div>
        <div class="d-flex justify-content-between align-items-center pt-2 border-top border-slate-200">
          <small class="text-muted">Interval: 3 Bulan</small>
          <button class="btn btn-dark btn-sm py-1 px-2.5 small" data-bs-toggle="modal" data-bs-target="#modalSelesaiServis">
            Tandai Selesai
          </button>
        </div>
      </div>
    </div>

    <!-- Reminder 2: Mobil Avanza -->
    <div class="col-lg-4 col-md-6">
      <div class="reminder-card h-100 d-flex flex-column justify-content-between">
        <div>
          <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="chip chip-warning"><span class="chip-dot"></span> Sisa 180 KM</span>
            <small class="text-muted font-monospace">B 1234 GAB</small>
          </div>
          <h6 class="fw-bold mb-1 text-dark">Ganti Oli Mesin Avanza</h6>
          <p class="text-muted small mb-3">Target: 45.000 KM • Odometer saat ini: 44.820 KM</p>
        </div>
        <div class="d-flex justify-content-between align-items-center pt-2 border-top border-slate-200">
          <small class="text-muted">Interval: 5.000 KM</small>
          <button class="btn btn-glass btn-sm py-1 px-2.5 small" data-bs-toggle="modal" data-bs-target="#modalUpdateKM">
            Update Odo
          </button>
        </div>
      </div>
    </div>

    <!-- Reminder 3: Pajak Motor -->
    <div class="col-lg-4 col-md-12">
      <div class="reminder-card h-100 d-flex flex-column justify-content-between">
        <div>
          <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="chip chip-neutral"><span class="chip-dot"></span> H-14 Hari</span>
            <small class="text-muted font-monospace">B 5678 JKT</small>
          </div>
          <h6 class="fw-bold mb-1 text-dark">Pajak Tahunan STNK Vario</h6>
          <p class="text-muted small mb-3">Jatuh tempo: 23 November 2026. Estimasi Rp 310.000</p>
        </div>
        <div class="d-flex justify-content-between align-items-center pt-2 border-top border-slate-200">
          <small class="text-muted">Masa berlaku STNK</small>
          <a href="{{ route('vehicles.index') }}" class="btn btn-glass btn-sm py-1 px-2.5 small text-decoration-none">
            Lihat Detail
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ================= MINIMAL STATS ROW ================= -->
<div class="row g-3 mb-4">
  <div class="col-sm-6 col-xl-3">
    <div class="glass-panel stat-box">
      <div class="stat-label mb-1">Aset Rumah Dikelola</div>
      <div class="stat-value">18 <span class="fs-6 text-muted fw-normal">unit</span></div>
      <div class="mt-2 d-flex align-items-center gap-1.5 small text-muted">
        <span class="chip chip-success py-0 px-1.5"><span class="chip-dot"></span> 17 baik</span>
        <span class="chip chip-urgent py-0 px-1.5"><span class="chip-dot"></span> 1 rusak</span>
      </div>
    </div>
  </div>

  <div class="col-sm-6 col-xl-3">
    <div class="glass-panel stat-box">
      <div class="stat-label mb-1">Kendaraan Garasi</div>
      <div class="stat-value">2 <span class="fs-6 text-muted fw-normal">unit</span></div>
      <div class="mt-2 small text-muted">
        <span>1 Mobil, 1 Sepeda Motor</span>
      </div>
    </div>
  </div>

  <div class="col-sm-6 col-xl-3">
    <div class="glass-panel stat-box">
      <div class="stat-label mb-1">Servis Terdekat</div>
      <div class="stat-value">3 <span class="fs-6 text-muted fw-normal">agenda</span></div>
      <div class="mt-2 small text-muted">
        <span>Bulan November 2026</span>
      </div>
    </div>
  </div>

  <div class="col-sm-6 col-xl-3">
    <div class="glass-panel stat-box">
      <div class="stat-label mb-1">Pengeluaran Bulan Ini</div>
      <div class="stat-value">Rp 850<span class="fs-6 text-muted fw-normal">rb</span></div>
      <div class="mt-2 small text-muted">
        <span>Anggaran mandiri: Rp 2.000.000</span>
      </div>
    </div>
  </div>
</div>

<!-- ================= MAIN SPLIT SECTION ================= -->
<div class="row g-4 mb-4">
  
  <!-- Left: Minimalist Expense Chart -->
  <div class="col-lg-8">
    <div class="glass-panel p-4 h-100">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <h6 class="fw-bold text-dark mb-0">Biaya Perawatan Pribadi (6 Bulan Terakhir)</h6>
          <small class="text-muted">Rekapitulasi riwayat biaya rumah vs kendaraan</small>
        </div>
        <div class="glass-pill">Tahun 2026</div>
      </div>
      <div style="height: 260px; position: relative;">
        <canvas id="expenseChart"></canvas>
      </div>
    </div>
  </div>

  <!-- Right: Minimal Odometer Monitor -->
  <div class="col-lg-4">
    <div class="glass-panel p-4 h-100">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold text-dark mb-0">Posisi Odometer Kendaraan</h6>
        <a href="{{ route('vehicles.index') }}" class="small text-muted text-decoration-none">Kelola</a>
      </div>

      <!-- Item Avanza -->
      <div class="p-3 rounded-3 bg-white bg-opacity-60 border mb-3" style="border-color: rgba(226, 232, 240, 0.7);">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <div>
            <div class="fw-semibold text-dark small">Toyota Avanza 1.5 G</div>
            <small class="text-muted font-monospace">B 1234 GAB</small>
          </div>
          <span class="odometer-minimal">44,820 KM</span>
        </div>
        <div class="d-flex justify-content-between text-muted small mb-1" style="font-size: 0.75rem;">
          <span>Target Oli (45.000 KM)</span>
          <span class="text-danger fw-semibold">Sisa 180 KM</span>
        </div>
        <div class="progress" style="height: 5px; background: rgba(0,0,0,0.06);">
          <div class="progress-bar bg-danger" style="width: 96%"></div>
        </div>
        <div class="text-end mt-2">
          <button class="btn btn-link btn-sm p-0 text-decoration-none text-dark small fw-medium" data-bs-toggle="modal" data-bs-target="#modalUpdateKM">+ Perbarui KM</button>
        </div>
      </div>

      <!-- Item Vario -->
      <div class="p-3 rounded-3 bg-white bg-opacity-60 border" style="border-color: rgba(226, 232, 240, 0.7);">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <div>
            <div class="fw-semibold text-dark small">Honda Vario 160 ABS</div>
            <small class="text-muted font-monospace">B 5678 JKT</small>
          </div>
          <span class="odometer-minimal">12,150 KM</span>
        </div>
        <div class="d-flex justify-content-between text-muted small mb-1" style="font-size: 0.75rem;">
          <span>Target CVT (16.000 KM)</span>
          <span class="text-success fw-semibold">Sisa 3.850 KM</span>
        </div>
        <div class="progress" style="height: 5px; background: rgba(0,0,0,0.06);">
          <div class="progress-bar bg-dark" style="width: 52%"></div>
        </div>
        <div class="text-end mt-2">
          <button class="btn btn-link btn-sm p-0 text-decoration-none text-dark small fw-medium" data-bs-toggle="modal" data-bs-target="#modalUpdateKM">+ Perbarui KM</button>
        </div>
      </div>

    </div>
  </div>

</div>

<!-- ================= SECONDARY SPLIT: AGENDA & CATATAN PRIBADI ================= -->
<div class="row g-4">
  
  <!-- Tabel Tugas Terjadwal -->
  <div class="col-lg-8">
    <div class="glass-panel p-4">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <h6 class="fw-bold text-dark mb-0">Agenda Servis & Perawatan Mendatang</h6>
          <small class="text-muted">Aset yang mendekati interval pengecekan berkala</small>
        </div>
        <a href="#" class="btn btn-glass btn-sm">Semua Riwayat</a>
      </div>

      <div class="table-responsive">
        <table class="minimal-table">
          <thead>
            <tr>
              <th>Aset</th>
              <th>Pekerjaan</th>
              <th>Jadwal / Pemicu</th>
              <th>Status</th>
              <th class="text-end">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>
                <div class="fw-semibold text-dark">AC Ruang Tamu</div>
                <small class="text-muted">Daikin 1 PK Inverter</small>
              </td>
              <td>Cuci filter & cek outdoor</td>
              <td>
                <div class="text-danger fw-medium">09 Nov 2026 (Hari Ini)</div>
                <small class="text-muted">Interval 3 Bulan</small>
              </td>
              <td><span class="chip chip-urgent"><span class="chip-dot"></span> Waktunya Servis</span></td>
              <td class="text-end">
                <button class="btn btn-dark btn-sm py-1 px-2.5 small" data-bs-toggle="modal" data-bs-target="#modalSelesaiServis">Selesai</button>
              </td>
            </tr>

            <tr>
              <td>
                <div class="fw-semibold text-dark">Toyota Avanza</div>
                <small class="text-muted">B 1234 GAB</small>
              </td>
              <td>Ganti oli mesin & filter oli</td>
              <td>
                <div class="text-warning fw-medium">Sisa 180 KM</div>
                <small class="text-muted">Target 45.000 KM</small>
              </td>
              <td><span class="chip chip-warning"><span class="chip-dot"></span> Mendekati Target</span></td>
              <td class="text-end">
                <button class="btn btn-glass btn-sm py-1 px-2.5 small" data-bs-toggle="modal" data-bs-target="#modalUpdateKM">Catat Servis</button>
              </td>
            </tr>

            <tr>
              <td>
                <div class="fw-semibold text-dark">Toren Air Utama</div>
                <small class="text-muted">Penguin 1000L - Dak Atas</small>
              </td>
              <td>Kuras endapan & lumut</td>
              <td>
                <div class="text-dark">25 Nov 2026</div>
                <small class="text-muted">Interval 6 Bulan</small>
              </td>
              <td><span class="chip chip-neutral"><span class="chip-dot"></span> Terjadwal</span></td>
              <td class="text-end">
                <button class="btn btn-glass btn-sm py-1 px-2.5 small"><i class="bi bi-pencil"></i></button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

    </div>
  </div>

  <!-- Right: Catatan Kerusakan Aktif & Kontak Teknisi Langganan Saya -->
  <div class="col-lg-4">
    
    <!-- Catatan Kerusakan -->
    <div class="glass-panel p-4 mb-4">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold text-dark mb-0">Catatan Kerusakan Aktif</h6>
        <span class="chip chip-urgent">1 Perlu Ditangani</span>
      </div>
      <div class="p-3 rounded-3 bg-white bg-opacity-70 border" style="border-color: rgba(239, 68, 68, 0.2);">
        <div class="d-flex justify-content-between align-items-start">
          <span class="fw-bold text-dark small">Keran Wastafel Dapur Bocor</span>
          <small class="text-muted">2 hari lalu</small>
        </div>
        <p class="text-muted small mt-1 mb-2">Air merembes di bawah kabinet dapur saat dinyalakan. Perlu ganti seal karet.</p>
        <div class="d-flex justify-content-between align-items-center pt-2 border-top border-slate-200">
          <small class="text-muted">Estimasi beli suku cadang: Rp 150rb</small>
          <button class="btn btn-glass btn-sm py-0.5 px-2 small">Selesai Diperbaiki</button>
        </div>
      </div>
    </div>

    <!-- Buku Kontak Teknisi / Bengkel Langganan Saya -->
    <div class="glass-panel p-4">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h6 class="fw-bold text-dark mb-0">Buku Kontak Teknisi Saya</h6>
        <button class="btn btn-link btn-sm text-dark p-0 text-decoration-none small fw-medium">+ Tambah</button>
      </div>
      <p class="text-muted" style="font-size: 0.75rem;">Simpan nomor tukang atau bengkel langganan Anda sendiri untuk memudahkan dihubungi saat dibutuhkan.</p>

      <div class="d-flex align-items-center justify-content-between py-2 border-bottom" style="border-color: rgba(226, 232, 240, 0.5);">
        <div>
          <div class="fw-semibold small text-dark">Pak Budi (Teknisi AC)</div>
          <small class="text-muted" style="font-size: 0.72rem;">Langganan cuci AC rumah</small>
        </div>
        <a href="https://wa.me/628123456789" target="_blank" class="btn btn-glass btn-sm py-1 px-2.5 small">
          <i class="bi bi-whatsapp text-success me-1"></i> Chat
        </a>
      </div>

      <div class="d-flex align-items-center justify-content-between py-2 border-bottom" style="border-color: rgba(226, 232, 240, 0.5);">
        <div>
          <div class="fw-semibold small text-dark">Bengkel Mobil Berkah</div>
          <small class="text-muted" style="font-size: 0.72rem;">Langganan servis & ganti oli Avanza</small>
        </div>
        <a href="https://wa.me/628987654321" target="_blank" class="btn btn-glass btn-sm py-1 px-2.5 small">
          <i class="bi bi-whatsapp text-success me-1"></i> Chat
        </a>
      </div>

      <div class="d-flex align-items-center justify-content-between py-2">
        <div>
          <div class="fw-semibold small text-dark">Mas Joko (Tukang Listrik & Pompa)</div>
          <small class="text-muted" style="font-size: 0.72rem;">Instalasi MCB & servis pompa air</small>
        </div>
        <a href="https://wa.me/628112233445" target="_blank" class="btn btn-glass btn-sm py-1 px-2.5 small">
          <i class="bi bi-whatsapp text-success me-1"></i> Chat
        </a>
      </div>

    </div>

  </div>

</div>
@endsection

@section('modals')
<!-- Modal Update KM Kendaraan -->
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
            <label class="form-label small fw-semibold text-muted">Pilih Kendaraan Anda</label>
            <select class="form-select">
              <option value="1">Toyota Avanza (B 1234 GAB) - Terakhir: 44.820 KM</option>
              <option value="2">Honda Vario 160 (B 5678 JKT) - Terakhir: 12.150 KM</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold text-muted">Posisi Odometer Hari Ini (KM)</label>
            <input type="number" class="form-control form-control-lg fw-bold font-monospace" placeholder="Contoh: 45050">
            <small class="text-muted" style="font-size: 0.72rem;">Sistem akan otomatis menghitung jarak sisa menuju servis oli berikutnya.</small>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold text-muted">Tanggal Pengecekan</label>
            <input type="date" class="form-control" value="2026-11-09">
          </div>
          <button type="button" class="btn btn-dark w-100 py-2 fw-medium rounded-3" data-bs-dismiss="modal">Simpan & Hitung Ulang</button>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Modal Catat Servis Selesai -->
<div class="modal fade" id="modalSelesaiServis" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content glass-panel border-0 shadow-lg p-3">
      <div class="modal-header border-bottom border-slate-200 pb-2">
        <h6 class="modal-title fw-bold text-dark">Catat Penyelesaian Servis</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-3">
        <form>
          <div class="p-2.5 rounded-2 bg-slate-100 text-dark small mb-3">
            Servis: <strong>Cuci Filter & Cek AC (AC Ruang Tamu)</strong>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold text-muted">Tanggal Dikerjakan</label>
            <input type="date" class="form-control" value="2026-11-09">
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold text-muted">Total Biaya Dikeluarkan (Rp)</label>
            <input type="number" class="form-control" placeholder="Contoh: 75000">
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold text-muted">Dikerjakan Oleh</label>
            <input type="text" class="form-control" placeholder="Contoh: Dikerjakan Sendiri / Pak Budi AC">
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold text-muted">Catatan Hasil Pemeriksaan</label>
            <textarea class="form-control" rows="2" placeholder="Catatan kondisi aset setelah diservis..."></textarea>
          </div>
          <button type="button" class="btn btn-dark w-100 py-2 fw-medium rounded-3" data-bs-dismiss="modal">Simpan ke Buku Riwayat</button>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  const ctx = document.getElementById('expenseChart').getContext('2d');
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: ['Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov'],
      datasets: [
        {
          label: 'Rumah',
          data: [350000, 200000, 750000, 150000, 300000, 450000],
          backgroundColor: '#0f172a',
          borderRadius: 4
        },
        {
          label: 'Kendaraan',
          data: [600000, 450000, 300000, 850000, 400000, 400000],
          backgroundColor: '#94a3b8',
          borderRadius: 4
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'top',
          align: 'end',
          labels: {
            boxWidth: 10,
            font: { family: "'Plus Jakarta Sans', sans-serif", size: 11 }
          }
        },
        tooltip: {
          callbacks: {
            label: function(context) {
              return context.dataset.label + ': Rp ' + context.parsed.y.toLocaleString('id-ID');
            }
          }
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            callback: function(value) {
              return 'Rp ' + (value / 1000) + 'k';
            },
            font: { family: "'Plus Jakarta Sans', sans-serif", size: 10 }
          },
          grid: { color: 'rgba(226, 232, 240, 0.5)' }
        },
        x: {
          grid: { display: false },
          ticks: { font: { family: "'Plus Jakarta Sans', sans-serif", size: 10 } }
        }
      }
    }
  });
</script>
@endpush
