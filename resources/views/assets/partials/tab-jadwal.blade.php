<div class="tab-pane fade" id="jadwal" role="tabpanel" aria-labelledby="tab-jadwal-btn">
  <div class="glass-panel p-4 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <h6 class="fw-bold text-dark mb-0">Jadwal Perawatan Aset</h6>
        <span class="text-muted small">Agenda pemeliharaan berkala untuk {{ $homeAsset->name }}.</span>
      </div>
      <a href="{{ route('schedules.index', ['filter' => 'home_asset']) }}" class="btn btn-dark btn-sm px-3 d-inline-flex align-items-center gap-1.5" style="min-height: 40px; font-weight: 500;">
        <i class="bi bi-calendar-plus"></i>
        <span>Kelola di Jadwal</span>
      </a>
    </div>

    @if ($schedules->isEmpty())
      <div class="p-4 text-center text-muted bg-white-50 rounded-3 border border-dashed border-slate-200">
        <i class="bi bi-calendar2-x d-block fs-3 mb-1 text-secondary"></i>
        <div class="fw-medium text-dark small">Belum ada jadwal perawatan aktif untuk aset ini</div>
        <p class="text-muted mb-3" style="font-size: 0.78rem;">Buat pengingat berkala untuk servis rutin agar aset tetap prima.</p>
        <a href="{{ route('schedules.index', ['filter' => 'home_asset']) }}" class="btn btn-dark btn-sm px-3" style="min-height: 44px; display: inline-flex; align-items: center;">
          <i class="bi bi-plus-lg me-1"></i> Buat Jadwal Baru
        </a>
      </div>
    @else
      <div class="d-flex flex-column gap-2.5">
        @foreach ($schedules as $sch)
          <div class="p-3 rounded-3 bg-white border border-slate-200 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
              <div class="fw-bold text-dark">{{ $sch->title }}</div>
              <div class="text-muted small">
                <i class="bi bi-clock me-1"></i>{{ $sch->trigger_label }}
                @if ($sch->next_due_date)
                  • Jatuh tempo: {{ \Carbon\Carbon::parse($sch->next_due_date)->format('d/m/Y') }}
                @endif
              </div>
              @if ($sch->notes)
                <div class="text-muted small mt-1" style="font-size: 0.75rem;">{{ $sch->notes }}</div>
              @endif
            </div>
            <div class="d-flex align-items-center gap-2">
              <span class="chip {{ $sch->status_chip_class }} py-1 px-2">
                <span class="chip-dot"></span> {{ ucfirst($sch->status) }}
              </span>
              <form action="{{ route('schedules.complete', $sch->id) }}" method="POST" class="m-0">
                @csrf
                <input type="hidden" name="done_at" value="{{ date('Y-m-d') }}">
                <button type="submit" class="btn btn-glass btn-sm px-3 text-nowrap" style="min-height: 40px;" title="Tandai Selesai Hari Ini">
                  <i class="bi bi-check2 me-1"></i> Selesai
                </button>
              </form>
            </div>
          </div>
        @endforeach
      </div>
    @endif
  </div>
</div>
