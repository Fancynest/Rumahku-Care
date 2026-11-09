@extends('layouts.app')

@section('title', 'Dashboard - Rumahku Care')

@section('content')
<!-- (a) 1 Kalimat Sapaan -->
<div class="mb-4">
  <h4 class="fw-bold mb-1 text-dark" style="letter-spacing: -0.02em;">Selamat Datang, Gabriel</h4>
  <p class="text-muted mb-0 small">Berikut ringkasan kondisi aset rumah dan kendaraan pribadi yang memerlukan perhatian Anda hari ini.</p>
</div>

<!-- (c) 2 Angka Ringkasan -->
<div class="row g-3 mb-4">
  <div class="col-sm-6">
    <div class="glass-panel p-4 h-100">
      <div class="text-muted small fw-medium mb-1">Total Aset Dikelola</div>
      <div class="d-flex align-items-baseline gap-2">
        <h2 class="fw-bold text-dark mb-0">{{ 18 + $totalVehicles }}</h2>
        <span class="text-muted small">unit (18 rumah, {{ $totalVehicles }} kendaraan)</span>
      </div>
    </div>
  </div>

  <div class="col-sm-6">
    <div class="glass-panel p-4 h-100">
      <div class="text-muted small fw-medium mb-1">Jadwal Perawatan Bulan Ini</div>
      <div class="d-flex align-items-baseline gap-2">
        <h2 class="fw-bold text-dark mb-0">3</h2>
        <span class="text-muted small">agenda terjadwal</span>
      </div>
    </div>
  </div>
</div>

<!-- (b) Maksimal 3 Kartu Perlu Perhatian (diurutkan paling mendesak) -->
<div class="mb-3">
  <h6 class="fw-bold text-dark mb-3">Perlu Perhatian</h6>
  
  <div class="row g-3">
    <!-- Card 1 (Paling mendesak - Hari Ini) -->
    <div class="col-md-4">
      <div class="glass-panel p-3.5 h-100 d-flex flex-column justify-content-between">
        <div>
          <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="chip chip-urgent"><span class="chip-dot"></span> Hari Ini</span>
            <small class="text-muted">Aset Rumah</small>
          </div>
          <h6 class="fw-bold mb-1 text-dark">Cuci Filter & Cek AC</h6>
          <p class="text-muted small mb-3">AC Daikin Inverter (Ruang Tamu). Interval 3 bulan telah tercapai.</p>
        </div>
        <div class="pt-2 border-top border-slate-200 d-flex justify-content-between align-items-center">
          <small class="text-muted">Teknisi: Pak Joko</small>
          <a href="{{ route('home-assets.show', 1) }}" class="btn btn-dark btn-sm py-1.5 px-3" style="min-height: 44px; display: inline-flex; align-items: center;">
            Periksa
          </a>
        </div>
      </div>
    </div>

    <!-- Card 2 (Mendesak - Servis KM) -->
    <div class="col-md-4">
      <div class="glass-panel p-3.5 h-100 d-flex flex-column justify-content-between">
        <div>
          <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="chip chip-urgent"><span class="chip-dot"></span> Sisa 180 KM</span>
            <small class="text-muted font-monospace">B 1234 GAB</small>
          </div>
          <h6 class="fw-bold mb-1 text-dark">Ganti Oli Mesin Avanza</h6>
          <p class="text-muted small mb-3">Odometer saat ini 44.820 KM. Target batas servis berkala 45.000 KM.</p>
        </div>
        <div class="pt-2 border-top border-slate-200 d-flex justify-content-between align-items-center">
          <small class="text-muted">Bengkel Resmi</small>
          <a href="{{ route('vehicles.index', ['v' => $vehicles->firstWhere('plate', 'B 1234 GAB')?->id ?? '']) }}" class="btn btn-dark btn-sm py-1.5 px-3" style="min-height: 44px; display: inline-flex; align-items: center;">
            Buka Garasi
          </a>
        </div>
      </div>
    </div>

    <!-- Card 3 (Mendekati Jatuh Tempo) -->
    <div class="col-md-4">
      <div class="glass-panel p-3.5 h-100 d-flex flex-column justify-content-between">
        <div>
          <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="chip chip-warning"><span class="chip-dot"></span> H-14 Hari</span>
            <small class="text-muted font-monospace">B 2341 KLS</small>
          </div>
          <h6 class="fw-bold mb-1 text-dark">Pajak STNK Beat Street</h6>
          <p class="text-muted small mb-3">Jatuh tempo pembayaran PKB tahunan pada 23 November 2026.</p>
        </div>
        <div class="pt-2 border-top border-slate-200 d-flex justify-content-between align-items-center">
          <small class="text-muted">Samsat Online</small>
          <a href="{{ route('vehicles.index', ['v' => $vehicles->firstWhere('plate', 'B 2341 KLS')?->id ?? '']) }}" class="btn btn-glass btn-sm py-1.5 px-3" style="min-height: 44px; display: inline-flex; align-items: center;">
            Lihat STNK
          </a>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
