<div class="tab-pane fade" id="inspeksi" role="tabpanel" aria-labelledby="tab-v-inspeksi-btn">
  <div class="glass-panel p-4 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <h6 class="fw-bold text-dark mb-0">Inspeksi & Uji Kelayakan Kendaraan</h6>
        <span class="text-muted small">Checklist sebelum perjalanan jauh (ban, oli, rem, lampu, radiator, aki).</span>
      </div>
      <button class="btn btn-dark btn-sm px-3 d-inline-flex align-items-center gap-1.5 fw-medium min-h-40" data-bs-toggle="modal" data-bs-target="#modalMulaiInspeksiKendaraan">
        <i class="bi bi-clipboard-plus"></i>
        <span>Mulai Inspeksi</span>
      </button>
    </div>

    @if ($selectedVehicle->inspectionRuns->isEmpty())
      <div class="p-4 text-center text-muted bg-white-50 rounded-3 border border-dashed border-slate-200">
        <i class="bi bi-clipboard-x d-block fs-3 mb-1 text-secondary"></i>
        <div class="fw-medium text-dark small">Belum ada riwayat inspeksi kendaraan</div>
        <p class="text-muted mb-3 text-xs">Lakukan inspeksi pra-perjalanan untuk memastikan keamanan seluruh komponen vital berkendara.</p>
        <button class="btn btn-dark btn-sm px-3 min-h-44" data-bs-toggle="modal" data-bs-target="#modalMulaiInspeksiKendaraan">
          <i class="bi bi-clipboard-plus me-1"></i> Mulai Inspeksi Pertama
        </button>
      </div>
    @else
      <div class="d-flex flex-column gap-3">
        @foreach ($selectedVehicle->inspectionRuns as $run)
          <div class="p-3.5 rounded-3 bg-white border border-slate-200">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-2 pb-2 border-bottom border-slate-100 gap-2">
              <div>
                <span class="badge {{ $run->status_badge_class }} py-0.5 px-2 me-2">
                  {{ $run->status_label }}
                </span>
                <strong class="text-dark">{{ $run->title }}</strong>
              </div>
              <div class="text-muted small font-monospace">
                <i class="bi bi-calendar3 me-1"></i>{{ $run->inspected_at->format('d M Y') }}
              </div>
            </div>

            @if ($run->notes)
              <p class="text-muted small mb-2.5">{{ $run->notes }}</p>
            @endif

            <div class="list-group list-group-flush rounded-2 border">
              @foreach ($run->items as $item)
                <div class="list-group-item d-flex flex-wrap justify-content-between align-items-center py-2 px-3 gap-2">
                  <div class="d-flex align-items-center gap-2">
                    <span class="badge {{ $item->status_badge_class }} px-2 py-0.5 text-2xs">
                      {{ $item->status_label }}
                    </span>
                    <span class="text-dark small fw-medium">{{ $item->item_label }}</span>
                    @if ($item->notes)
                      <span class="text-muted small">— {{ $item->notes }}</span>
                    @endif
                  </div>
                  @if (in_array($item->status, ['perhatian', 'bermasalah']))
                    @if ($item->damage_report_id)
                      <span class="badge bg-secondary-subtle text-secondary border px-2 py-1 text-2xs">
                        <i class="bi bi-check-circle me-1"></i>Sudah Dikonversi
                      </span>
                    @else
                      <form action="{{ route('inspections.convert-damage', $item->id) }}" method="POST" class="m-0" onsubmit="return confirm('Konversikan temuan ini menjadi laporan kerusakan kendaraan?');">
                        @csrf
                        <button type="submit" class="btn btn-glass btn-sm py-1 px-2.5 text-danger d-inline-flex align-items-center gap-1 btn-action-sm text-xs">
                          <i class="bi bi-tools"></i>
                          <span>Jadikan Kerusakan</span>
                        </button>
                      </form>
                    @endif
                  @endif
                </div>
              @endforeach
            </div>
          </div>
        @endforeach
      </div>
    @endif
  </div>
</div>
