@extends('layouts.app')

@section('title', 'Beranda - Rumahku Care')

@section('content')
<!-- (a) 1 Kalimat Sapaan Kontekstual -->
<div class="mb-4">
  <h4 class="fw-bold mb-1 text-dark" style="letter-spacing: -0.02em;">{{ $greetingMessage }}</h4>
  @if ($hasNoData)
    <p class="text-muted mb-0 small">Mulai kelola inventaris aset dan jadwalkan perawatan berkala Anda di satu tempat.</p>
  @else
    <p class="text-muted mb-0 small">Pantau status jadwal perawatan dan kondisi fisik seluruh aset Anda secara terpadu.</p>
  @endif
</div>

@if ($hasNoData)
  <!-- Empty State Onboarding -->
  <div class="glass-panel p-5 text-center my-4">
    <div class="mx-auto mb-3 d-inline-flex align-items-center justify-content-center rounded-circle bg-light border" style="width: 64px; height: 64px;">
      <i class="bi bi-house-add text-dark fs-3"></i>
    </div>
    <h5 class="fw-bold text-dark mb-2">Mulai Kelola Rumah dan Kendaraan Anda</h5>
    <p class="text-muted small mx-auto mb-4" style="max-width: 500px;">
      Anda belum memiliki kendaraan, aset rumah, maupun jadwal perawatan yang terdaftar. Tambahkan aset pertama Anda untuk mulai memantau servis berkala dan pengingat jatuh tempo secara otomatis.
    </p>
    <div class="d-flex flex-wrap justify-content-center gap-3">
      <a href="{{ route('vehicles.index') }}" class="btn btn-dark px-4 py-2 d-inline-flex align-items-center gap-2" style="min-height: 44px; font-weight: 500;">
        <i class="bi bi-car-front"></i>
        <span>Tambah Kendaraan</span>
      </a>
      <a href="{{ route('home-assets.index') }}" class="btn btn-glass px-4 py-2 d-inline-flex align-items-center gap-2" style="min-height: 44px; font-weight: 500;">
        <i class="bi bi-grid"></i>
        <span>Tambah Aset Rumah</span>
      </a>
    </div>
  </div>
@else
  <!-- (c) 2 Angka Ringkasan -->
  <div class="row g-3 mb-4">
    <div class="col-sm-6">
      <div class="glass-panel p-4 h-100">
        <div class="text-muted small fw-medium mb-1">Jadwal Perlu Tindakan</div>
        <div class="d-flex align-items-baseline gap-2">
          <h2 class="fw-bold text-dark mb-0">{{ $urgentCount }}</h2>
          <span class="text-muted small">agenda berstatus terlambat atau mendesak</span>
        </div>
      </div>
    </div>

    <div class="col-sm-6">
      <div class="glass-panel p-4 h-100">
        <div class="text-muted small fw-medium mb-1">Aset Kondisi Prima</div>
        <div class="d-flex align-items-baseline gap-2">
          <h2 class="fw-bold text-dark mb-0">{{ $primeAssetsCount }}</h2>
          <span class="text-muted small">unit ({{ $primeVehiclesCount }} kendaraan, {{ $normalHomeAssetsCount }} aset rumah)</span>
        </div>
      </div>
    </div>
  </div>

  <!-- (b) Maksimal 3 Kartu Jadwal Paling Mendesak -->
  <div class="mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h6 class="fw-bold text-dark mb-0">Perlu Perhatian Segera</h6>
      <a href="{{ route('schedules.index') }}" class="text-dark small text-decoration-none fw-medium">
        Lihat Semua Jadwal <i class="bi bi-arrow-right"></i>
      </a>
    </div>

    @if ($topUrgentCards->isEmpty())
      <div class="glass-panel p-4 text-center my-2">
        <i class="bi bi-shield-check text-success fs-2 mb-2 d-block"></i>
        <div class="fw-bold text-dark mb-1">Semua Aset Terawat Baik</div>
        <p class="text-muted small mb-3">Tidak ada jadwal perawatan yang mendesak atau terlambat saat ini.</p>
        <div class="d-flex justify-content-center gap-2">
          <a href="{{ route('vehicles.index') }}" class="btn btn-glass btn-sm px-3" style="min-height: 40px;">
            <i class="bi bi-car-front me-1"></i> Buka Garasi
          </a>
          <a href="{{ route('home-assets.index') }}" class="btn btn-glass btn-sm px-3" style="min-height: 40px;">
            <i class="bi bi-grid me-1"></i> Aset Rumah
          </a>
        </div>
      </div>
    @else
      <div class="row g-3">
        @foreach ($topUrgentCards as $sch)
          @php
            $isVehicle = $sch->subject instanceof \App\Models\Vehicle;
            $subjectOdo = $isVehicle ? (int) $sch->subject->odometer : 0;
          @endphp
          <div class="col-md-4">
            <div class="glass-panel p-3.5 h-100 d-flex flex-column justify-content-between">
              <div>
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <div class="d-flex align-items-center gap-1">
                    <span class="chip {{ $sch->status_chip_class }} py-0.5 px-2">
                      <span class="chip-dot"></span> {{ ucfirst($sch->status) }}
                    </span>
                    @if ($sch->priority && $sch->priority !== 'normal')
                      <span class="badge {{ $sch->priority === 'darurat' ? 'bg-danger text-white' : ($sch->priority === 'mendesak' ? 'bg-warning-subtle text-warning-emphasis border border-warning' : 'bg-info-subtle text-info-emphasis border border-info') }} py-0.5 px-1.5 text-capitalize" style="font-size: 0.65rem;">
                        {{ ucfirst($sch->priority) }}
                      </span>
                    @endif
                  </div>
                  <span class="badge bg-light text-dark border px-2 py-0.5" style="font-size: 0.68rem;">
                    {{ $isVehicle ? 'Kendaraan' : 'Aset Rumah' }}
                  </span>
                </div>

                <div class="fw-bold text-secondary small mb-0.5">{{ $sch->subject_name }}</div>
                <h6 class="fw-bold mb-1 text-dark">{{ $sch->title }}</h6>

                <!-- Sisa KM / Hari -->
                <div class="text-muted small mb-3" style="font-size: 0.8rem;">
                  @if ($sch->trigger_type === 'kilometer')
                    @php
                      $diff = (int) $sch->next_due_km - $subjectOdo;
                    @endphp
                    @if ($diff <= 0)
                      <span class="text-danger fw-bold"><i class="bi bi-speedometer2 me-1"></i>Terlewat {{ number_format(abs($diff)) }} KM</span>
                    @else
                      <span class="text-secondary"><i class="bi bi-speedometer2 me-1"></i>Sisa {{ number_format($diff) }} KM</span>
                    @endif
                    <span class="text-muted d-block" style="font-size: 0.72rem;">Target: {{ number_format($sch->next_due_km) }} KM</span>
                  @elseif ($sch->next_due_date)
                    @php
                      $daysDiff = \Carbon\Carbon::today()->diffInDays(\Carbon\Carbon::parse($sch->next_due_date), false);
                    @endphp
                    @if ($daysDiff < 0)
                      <span class="text-danger fw-bold"><i class="bi bi-clock-history me-1"></i>Terlambat {{ abs($daysDiff) }} hari</span>
                    @else
                      <span class="text-secondary"><i class="bi bi-clock me-1"></i>Sisa {{ $daysDiff }} hari lagi</span>
                    @endif
                    <span class="text-muted d-block" style="font-size: 0.72rem;">Jatuh tempo: {{ \Carbon\Carbon::parse($sch->next_due_date)->format('d M Y') }}</span>
                  @else
                    <span class="text-secondary"><i class="bi bi-calendar-event me-1"></i>Jadwal Rutin</span>
                  @endif
                </div>
              </div>

              <!-- Tombol Cepat: Tandai Selesai -->
              <div class="pt-2.5 border-top border-slate-200 d-flex justify-content-between align-items-center">
                <span class="badge bg-white text-dark border px-2 py-1 font-monospace" style="font-size: 0.7rem;">
                  {{ $sch->trigger_label }}
                </span>
                <button class="btn btn-dark btn-sm px-3 d-inline-flex align-items-center gap-1.5 btn-dashboard-complete" 
                        style="min-height: 40px; font-weight: 500;"
                        data-bs-toggle="modal" 
                        data-bs-target="#modalTandaiSelesai"
                        data-id="{{ $sch->id }}"
                        data-title="{{ $sch->title }}"
                        data-trigger="{{ $sch->trigger_type }}"
                        data-subject="{{ $sch->subject_name }}"
                        data-odo="{{ $subjectOdo }}"
                        data-workshop="{{ $isVehicle ? ($sch->subject->workshop ?? '') : '' }}">
                  <i class="bi bi-check2"></i>
                  <span>Tandai Selesai</span>
                </button>
              </div>
            </div>
          </div>
        @endforeach
      </div>
    @endif
  </div>
@endif
@endsection

@section('modals')
<!-- ================= MODAL: TANDAI SELESAI (DASHBOARD) ================= -->
<div class="modal fade" id="modalTandaiSelesai" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content glass-panel border-0 shadow-lg">
      <form action="" method="POST" id="formDashboardComplete">
        @csrf
        <div class="modal-header border-bottom border-slate-200 py-3">
          <h6 class="modal-title fw-bold text-dark" id="modalDashboardTitle">Tandai Selesai</h6>
          <button type="button" class="btn-close small" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body py-3">
          <div class="p-2.5 rounded-3 bg-light border mb-3">
            <small class="text-muted d-block" style="font-size: 0.72rem;">Subjek Perawatan:</small>
            <div class="fw-bold text-dark" id="modalDashboardSubject">-</div>
          </div>

          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label text-muted small mb-1">Tanggal Selesai <span class="text-danger">*</span></label>
              <input type="date" name="done_at" class="form-control" value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}" required>
            </div>
            <div class="col-6" id="wrapperDashboardOdo">
              <label class="form-label text-muted small mb-1">Odometer Saat Ini <span class="text-danger">*</span></label>
              <input type="number" name="odometer" id="inputDashboardOdo" class="form-control" min="0">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label text-muted small mb-1">Bengkel / Teknisi Pelaksana</label>
            <input type="text" name="workshop_name" id="inputDashboardWorkshop" class="form-control" placeholder="Opsional (mis. Auto2000, Pak Budi AC)">
          </div>

          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label text-muted small mb-1">Biaya Jasa (Rp)</label>
              <input type="number" name="labor_cost" class="form-control" value="0" min="0" step="1000">
            </div>
            <div class="col-6">
              <label class="form-label text-muted small mb-1">Biaya Parts (Rp)</label>
              <input type="number" name="parts_cost" class="form-control" value="0" min="0" step="1000">
            </div>
          </div>

          <div class="mb-2">
            <label class="form-label text-muted small mb-1">Catatan Pelaksanaan</label>
            <textarea name="notes" class="form-control" rows="2" placeholder="Catatan kondisi setelah diservis..."></textarea>
          </div>
        </div>

        <div class="modal-footer border-top border-slate-200 py-2.5">
          <button type="button" class="btn btn-glass btn-sm" data-bs-dismiss="modal" style="min-height: 44px; padding: 0.4rem 1.2rem;">Batal</button>
          <button type="submit" class="btn btn-dark btn-sm px-4" style="min-height: 44px;">Simpan & Perbarui Siklus</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  document.querySelectorAll('.btn-dashboard-complete').forEach(function(btn) {
    btn.addEventListener('click', function() {
      const id = this.getAttribute('data-id');
      const title = this.getAttribute('data-title');
      const trigger = this.getAttribute('data-trigger');
      const subject = this.getAttribute('data-subject');
      const odo = parseInt(this.getAttribute('data-odo') || 0, 10);
      const workshop = this.getAttribute('data-workshop');

      const form = document.getElementById('formDashboardComplete');
      form.action = '/schedules/' + id + '/complete';

      document.getElementById('modalDashboardTitle').textContent = 'Tandai Selesai: ' + title;
      document.getElementById('modalDashboardSubject').textContent = subject;
      document.getElementById('inputDashboardWorkshop').value = workshop;

      const odoWrapper = document.getElementById('wrapperDashboardOdo');
      const odoInput = document.getElementById('inputDashboardOdo');
      if (trigger === 'kilometer') {
        odoWrapper.classList.remove('d-none');
        odoInput.required = true;
        odoInput.min = 0;
        odoInput.value = odo;
      } else {
        odoWrapper.classList.add('d-none');
        odoInput.required = false;
      }
    });
  });
</script>
@endpush
