<div class="tab-pane fade" id="kerusakan" role="tabpanel" aria-labelledby="tab-v-kerusakan-btn">
  <div class="glass-panel p-4 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <h6 class="fw-bold text-dark mb-0">Riwayat Kerusakan & Trouble Kendaraan</h6>
        <span class="text-muted small">Pencatatan kendala mesin, kelistrikan, bodi, dan perbaikan bengkel.</span>
      </div>
      <button class="btn btn-dark btn-sm px-3 d-inline-flex align-items-center gap-1.5" data-bs-toggle="modal" data-bs-target="#modalLaporKerusakanKendaraan" style="min-height: 40px; font-weight: 500;">
        <i class="bi bi-tools"></i>
        <span>Laporkan Kerusakan</span>
      </button>
    </div>

    @if ($selectedVehicle->damageReports->isEmpty())
      <div class="p-4 text-center text-muted bg-white-50 rounded-3 border border-dashed border-slate-200">
        <i class="bi bi-shield-check d-block fs-3 mb-1 text-success"></i>
        <div class="fw-medium text-dark small">Tidak ada laporan kerusakan aktif</div>
        <p class="text-muted mb-3" style="font-size: 0.78rem;">Kendaraan dalam kondisi fisik normal tanpa catatan kendala terbuka.</p>
        <button class="btn btn-glass btn-sm px-3" data-bs-toggle="modal" data-bs-target="#modalLaporKerusakanKendaraan" style="min-height: 44px;">
          <i class="bi bi-plus-lg me-1"></i> Catat Kerusakan Baru
        </button>
      </div>
    @else
      <div class="d-flex flex-column gap-3">
        @foreach ($selectedVehicle->damageReports as $report)
          <div class="p-3.5 rounded-3 bg-white border border-slate-200">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
              <div>
                <div class="d-flex align-items-center gap-1.5 mb-1">
                  <span class="badge {{ $report->severity_badge_class }} px-2 py-0.5" style="font-size: 0.72rem;">
                    {{ $report->severity_label }}
                  </span>
                  <span class="badge {{ $report->status_badge_class }} px-2 py-0.5" style="font-size: 0.72rem;">
                    {{ $report->status_label }}
                  </span>
                </div>
                <h6 class="fw-bold text-dark mb-0.5">{{ $report->title }}</h6>
                <small class="text-muted font-monospace" style="font-size: 0.75rem;">
                  Dilaporkan: {{ $report->reported_at?->format('d M Y') }}
                  @if ($report->resolved_at)
                    • Selesai: {{ $report->resolved_at->format('d M Y') }}
                  @endif
                </small>
              </div>

              <div class="d-flex align-items-center gap-2">
                @if ($report->status !== 'selesai')
                  <button class="btn btn-dark btn-sm px-3 btn-edit-damage-status" 
                          style="min-height: 38px; font-size: 0.78rem;"
                          data-bs-toggle="modal" 
                          data-bs-target="#modalStatusKerusakanKendaraan"
                          data-id="{{ $report->id }}"
                          data-title="{{ $report->title }}"
                          data-status="{{ $report->status }}"
                          data-tech="{{ $report->technician_name ?? '' }}"
                          data-cost="{{ $report->repair_cost ?? 0 }}"
                          data-notes="{{ $report->notes ?? '' }}">
                    <i class="bi bi-pencil-square me-1"></i> Update Status
                  </button>
                @endif
                <form action="{{ route('damage-reports.destroy', $report->id) }}" method="POST" onsubmit="return confirm('Hapus laporan kerusakan ini?');" class="m-0">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="btn btn-glass btn-sm text-danger p-2" title="Hapus Laporan" style="min-height: 38px; min-width: 38px;">
                    <i class="bi bi-trash"></i>
                  </button>
                </form>
              </div>
            </div>

            @if ($report->description)
              <p class="text-secondary small mb-2">{{ $report->description }}</p>
            @endif

            <div class="p-2.5 rounded-2 bg-light border small text-muted d-flex flex-wrap gap-3">
              <div>
                <span class="text-muted d-block" style="font-size: 0.7rem;">Bengkel / Teknisi:</span>
                <span class="fw-medium text-dark">{{ $report->technician_name ?: '-' }}</span>
              </div>
              <div>
                <span class="text-muted d-block" style="font-size: 0.7rem;">Biaya Perbaikan:</span>
                <span class="fw-medium text-dark">{{ $report->repair_cost ? 'Rp ' . number_format($report->repair_cost) : '-' }}</span>
              </div>
              @if ($report->notes)
                <div class="w-100 mt-1 pt-1 border-top border-slate-200">
                  <span class="text-muted d-block" style="font-size: 0.7rem;">Catatan:</span>
                  <span class="text-dark">{{ $report->notes }}</span>
                </div>
              @endif
            </div>

            <!-- Lampiran Foto / Nota Bukti Perbaikan -->
            @if ($report->attachments->isNotEmpty())
              <div class="mt-2.5 pt-2 border-top border-slate-100 d-flex flex-wrap gap-2">
                @foreach ($report->attachments as $att)
                  <a href="{{ route('attachments.show', $att->id) }}" target="_blank" class="btn btn-glass btn-sm py-1 px-2.5 d-inline-flex align-items-center gap-1.5" style="min-height: 32px; font-size: 0.75rem;">
                    <i class="bi {{ str_contains($att->mime, 'pdf') ? 'bi-file-earmark-pdf text-danger' : 'bi-image text-primary' }}"></i>
                    <span>{{ Str::limit($att->original_name, 20) }}</span>
                  </a>
                @endforeach
              </div>
            @endif
          </div>
        @endforeach
      </div>
    @endif
  </div>
</div>
