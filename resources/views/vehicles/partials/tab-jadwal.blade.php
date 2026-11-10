<div class="tab-pane fade" id="jadwal" role="tabpanel" aria-labelledby="tab-v-jadwal-btn">
  <div class="glass-panel p-4 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <h6 class="fw-bold text-dark mb-0">Jadwal Perawatan Berkala</h6>
        <span class="text-muted small">Agenda pemeliharaan berkala untuk {{ $selectedVehicle->name }}.</span>
      </div>
      <a href="{{ route('schedules.index', ['filter' => 'vehicle']) }}" class="btn btn-dark btn-sm px-3 d-inline-flex align-items-center gap-1.5 fw-medium min-h-40">
        <i class="bi bi-calendar-plus"></i>
        <span>Kelola di Jadwal</span>
      </a>
    </div>

    @if ($activeSchedules->isEmpty())
      <div class="p-4 text-center text-muted bg-white-50 rounded-3 border border-dashed border-slate-200">
        <i class="bi bi-calendar2-x d-block fs-3 mb-1 text-secondary"></i>
        <div class="fw-medium text-dark small">Belum ada jadwal perawatan aktif</div>
        <p class="text-muted mb-3 text-xs">Buat pengingat ganti oli atau servis berkala berbasis kilometer dan waktu.</p>
        <a href="{{ route('schedules.index', ['filter' => 'vehicle']) }}" class="btn btn-dark btn-sm px-3 d-inline-flex align-items-center min-h-44">
          <i class="bi bi-plus-lg me-1"></i> Buat Jadwal Baru
        </a>
      </div>
    @else
      <div class="d-flex flex-column gap-2.5">
        @foreach ($activeSchedules as $sch)
          <div class="p-3 rounded-3 bg-white border border-slate-200 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
              <div class="fw-bold text-dark">{{ $sch->title }}</div>
              <div class="text-muted small">
                <i class="bi bi-clock me-1"></i>{{ $sch->trigger_label }}
                @if ($sch->trigger_type === 'kilometer')
                  • Target: {{ number_format($sch->next_due_km) }} KM (Sisa: {{ number_format(max(0, (int)$sch->next_due_km - (int)$selectedVehicle->odometer)) }} KM)
                @elseif ($sch->next_due_date)
                  • Jatuh tempo: {{ \Carbon\Carbon::parse($sch->next_due_date)->format('d/m/Y') }}
                @endif
              </div>
              @if ($sch->notes)
                <div class="text-muted small mt-1 text-xs">{{ $sch->notes }}</div>
              @endif
            </div>
            <div class="d-flex align-items-center gap-2">
              <span class="chip {{ $sch->status_chip_class }} py-1 px-2">
                <span class="chip-dot"></span> {{ ucfirst($sch->status) }}
              </span>
              <button class="btn btn-glass btn-sm px-3 min-h-40" data-bs-toggle="modal" data-bs-target="#modalCatatServis">
                <i class="bi bi-wrench me-1"></i> Catat Servis
              </button>
            </div>
          </div>
        @endforeach
      </div>
    @endif
  </div>
</div>
