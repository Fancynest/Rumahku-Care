<div class="tab-pane fade" id="dokumen" role="tabpanel" aria-labelledby="tab-v-dokumen-btn">
  <div class="glass-panel p-4 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <h6 class="fw-bold text-dark mb-0">Dokumen STNK, BPKB & Asuransi</h6>
        <span class="text-muted small">Kelola masa berlaku pajak tahunan, plat 5 tahunan, dan polis asuransi {{ $selectedVehicle->name }}.</span>
      </div>
      <button class="btn btn-dark btn-sm px-3 d-inline-flex align-items-center gap-1.5 fw-medium min-h-40" data-bs-toggle="modal" data-bs-target="#modalTambahDokumenKendaraan">
        <i class="bi bi-file-earmark-plus"></i>
        <span>Tambah Dokumen</span>
      </button>
    </div>

    <!-- Dokumen Aktif -->
    @if ($activeVehicleDocs->isEmpty())
      <div class="p-4 text-center text-muted bg-white-50 rounded-3 border border-dashed border-slate-200 mb-3">
        <i class="bi bi-file-earmark-x d-block fs-3 mb-1 text-secondary"></i>
        <div class="fw-medium text-dark small">Belum ada dokumen kendaraan tersimpan</div>
        <p class="text-muted mb-3 text-xs">Simpan STNK atau polis asuransi untuk mendapatkan pengingat jatuh tempo otomatis.</p>
        <button class="btn btn-dark btn-sm px-3 min-h-44" data-bs-toggle="modal" data-bs-target="#modalTambahDokumenKendaraan">
          <i class="bi bi-plus-lg me-1"></i> Tambah Dokumen Sekarang
        </button>
      </div>
    @else
      <div class="d-flex flex-column gap-2.5 mb-4">
        @foreach ($activeVehicleDocs as $doc)
          <div class="p-3 rounded-3 bg-white border border-slate-200 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
              <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge bg-light text-dark border px-2 py-0.5 text-2xs">
                  {{ $doc->type_label }}
                </span>
                @if ($doc->status)
                  <span class="chip {{ $doc->status_chip_class }} py-0.5 px-2">
                    <span class="chip-dot"></span> {{ $doc->status_label }}
                  </span>
                @endif
              </div>
              <div class="fw-bold text-dark">{{ $doc->title }}</div>
              <div class="text-muted small">
                @if ($doc->reference_number)
                  <span class="font-monospace me-2">No: {{ $doc->reference_number }}</span>
                @endif
                @if ($doc->expires_at)
                  <span>• Jatuh tempo: {{ $doc->expires_at->format('d/m/Y') }} ({{ $doc->due_label }})</span>
                @endif
              </div>
              @if ($doc->notes)
                <div class="text-muted small mt-1 text-xs">{{ $doc->notes }}</div>
              @endif
            </div>

            <div class="d-flex align-items-center gap-2">
              @if ($doc->attachments->isNotEmpty())
                <a href="{{ route('attachments.show', $doc->attachments->first()->id) }}" target="_blank" class="btn btn-glass btn-sm px-2.5 min-h-40" title="Lihat Berkas Lampiran">
                  <i class="bi {{ str_contains($doc->attachments->first()->mime, 'pdf') ? 'bi-file-earmark-pdf text-danger' : 'bi-image' }} me-1"></i> Berkas
                </a>
              @endif
              <button class="btn btn-dark btn-sm px-3 btn-renew-document min-h-40 text-xs" 
                      data-bs-toggle="modal" 
                      data-bs-target="#modalPerpanjangDokumenKendaraan"
                      data-id="{{ $doc->id }}"
                      data-title="{{ $doc->title }}"
                      data-cost="{{ $doc->cost ?? 0 }}"
                      data-ref="{{ $doc->reference_number ?? '' }}">
                <i class="bi bi-arrow-repeat me-1"></i> Perpanjang
              </button>
              <form action="{{ route('documents.destroy', $doc->id) }}" method="POST" onsubmit="return confirm('Hapus dokumen ini?');" class="m-0">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-glass btn-action text-danger" title="Hapus Dokumen">
                  <i class="bi bi-trash"></i>
                </button>
              </form>
            </div>
          </div>
        @endforeach
      </div>
    @endif

    <!-- Collapsible Riwayat Dokumen Terarsip -->
    @php
      $archivedVehicleDocs = $selectedVehicle->documents->whereNotNull('archived_at');
    @endphp
    @if ($archivedVehicleDocs->isNotEmpty())
      <div class="pt-3 border-top border-slate-200">
        <button class="btn btn-link text-decoration-none text-secondary p-0 small fw-semibold d-flex align-items-center gap-1.5" type="button" data-bs-toggle="collapse" data-bs-target="#collapseArchivedVehicleDocs" aria-expanded="false">
          <i class="bi bi-archive"></i>
          <span>Lihat Riwayat Dokumen Terarsip ({{ $archivedVehicleDocs->count() }})</span>
          <i class="bi bi-chevron-down small"></i>
        </button>
        
        <div class="collapse mt-3" id="collapseArchivedVehicleDocs">
          <div class="d-flex flex-column gap-2">
            @foreach ($archivedVehicleDocs as $arc)
              <div class="p-2.5 rounded-2 bg-light border text-muted small d-flex justify-content-between align-items-center">
                <div>
                  <span class="fw-medium text-dark">{{ $arc->title }}</span>
                  <span class="text-muted font-monospace ms-2">No: {{ $arc->reference_number ?: '-' }}</span>
                  <div class="text-secondary text-2xs">
                    Diarsipkan: {{ $arc->archived_at->format('d/m/Y') }} • Biaya lama: Rp {{ number_format($arc->cost ?? 0) }}
                  </div>
                </div>
                @if ($arc->attachments->isNotEmpty())
                  <a href="{{ route('attachments.show', $arc->attachments->first()->id) }}" target="_blank" class="btn btn-glass btn-sm py-1 px-2 text-2xs">
                    <i class="bi bi-paperclip"></i> Berkas
                  </a>
                @endif
              </div>
            @endforeach
          </div>
        </div>
      </div>
    @endif
  </div>
</div>
