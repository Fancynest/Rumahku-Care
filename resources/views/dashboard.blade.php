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
        <h2 class="fw-bold text-dark mb-0">{{ 1 + $urgentVehicles->count() }}</h2>
        <span class="text-muted small">agenda perlu perhatian</span>
      </div>
    </div>
  </div>
</div>

<!-- (b) Maksimal 3 Kartu Perlu Perhatian (diurutkan paling mendesak) -->
<div class="mb-3">
  <h6 class="fw-bold text-dark mb-3">Perlu Perhatian</h6>
  
  <div class="row g-3">
    <!-- Card 1 (Aset Rumah - Cuci Filter AC) -->
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

    <!-- Card 2 (Kendaraan Perlu Servis dari Database) -->
    <div class="col-md-4">
      <div class="glass-panel p-3.5 h-100 d-flex flex-column justify-content-between">
        @if ($urgentVehicle = $urgentVehicles->first())
          <div>
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span class="chip chip-urgent"><span class="chip-dot"></span> Waktunya Servis</span>
              <small class="text-muted font-monospace">{{ $urgentVehicle->plate }}</small>
            </div>
            <h6 class="fw-bold mb-1 text-dark">Servis Berkala {{ $urgentVehicle->name }}</h6>
            <p class="text-muted small mb-3">
              Odometer: {{ number_format($urgentVehicle->odometer) }} KM.
              @if ($urgentVehicle->latestServiceLog)
                Terakhir diservis di {{ number_format($urgentVehicle->latestServiceLog->odometer) }} KM.
              @else
                Belum ada catatan servis sebelumnya.
              @endif
            </p>
          </div>
          <div class="pt-2 border-top border-slate-200 d-flex justify-content-between align-items-center">
            <small class="text-muted">{{ $urgentVehicle->workshop ?? 'Bengkel Resmi' }}</small>
            <a href="{{ route('vehicles.index', ['v' => $urgentVehicle->id]) }}" class="btn btn-dark btn-sm py-1.5 px-3" style="min-height: 44px; display: inline-flex; align-items: center;">
              Buka Garasi
            </a>
          </div>
        @else
          <div>
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span class="chip chip-success"><span class="chip-dot"></span> Kondisi Prima</span>
              <small class="text-muted">Garasi</small>
            </div>
            <h6 class="fw-bold mb-1 text-dark">Kendaraan Siap Pakai</h6>
            <p class="text-muted small mb-3">Seluruh kendaraan di garasi belum mencapai batas interval servis berkala.</p>
          </div>
          <div class="pt-2 border-top border-slate-200 d-flex justify-content-between align-items-center">
            <small class="text-muted">{{ $totalVehicles }} Kendaraan</small>
            <a href="{{ route('vehicles.index') }}" class="btn btn-glass btn-sm py-1.5 px-3" style="min-height: 44px; display: inline-flex; align-items: center;">
              Lihat Garasi
            </a>
          </div>
        @endif
      </div>
    </div>

    <!-- Card 3 (STNK / Pajak Kendaraan dari Database) -->
    <div class="col-md-4">
      <div class="glass-panel p-3.5 h-100 d-flex flex-column justify-content-between">
        @php
          $sampleVehicle = $vehicles->first();
        @endphp
        <div>
          <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="chip chip-warning"><span class="chip-dot"></span> Pajak Tahunan</span>
            <small class="text-muted font-monospace">{{ $sampleVehicle?->plate ?? '-' }}</small>
          </div>
          <h6 class="fw-bold mb-1 text-dark">Pajak STNK {{ $sampleVehicle?->name ?? 'Kendaraan' }}</h6>
          <p class="text-muted small mb-3">Periksa masa berlaku STNK dan PKB tahunan di gerai Samsat atau SIGNAL.</p>
        </div>
        <div class="pt-2 border-top border-slate-200 d-flex justify-content-between align-items-center">
          <small class="text-muted">Dokumen Legal</small>
          <a href="{{ route('vehicles.index', $sampleVehicle ? ['v' => $sampleVehicle->id] : []) }}" class="btn btn-glass btn-sm py-1.5 px-3" style="min-height: 44px; display: inline-flex; align-items: center;">
            Lihat STNK
          </a>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
