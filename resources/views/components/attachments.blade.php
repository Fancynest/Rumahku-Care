@props([
  'model',
  'title' => 'Berkas & Foto Lampiran',
  'subtitle' => 'Daftar nota, dokumen, atau dokumentasi visual terkait.'
])

@php
  $attachments = $model->attachments ?? collect();
  $count = $attachments->count();
  $max = (int) config('maintenance.attachments.max_per_object', 5);
  $canUpload = $count < $max;
  $modelClass = get_class($model);
  $modalId = 'modalUpload_' . substr(md5($modelClass . '_' . $model->getKey()), 0, 10);
@endphp

<div class="attachments-component">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <div class="d-flex align-items-center gap-2">
        <h6 class="fw-bold text-dark mb-0">{{ $title }}</h6>
        <span class="badge bg-light text-muted border px-2 py-0.5 text-2xs">
          {{ $count }}/{{ $max }} berkas
        </span>
      </div>
      @if ($subtitle)
        <span class="text-muted small text-xs">{{ $subtitle }}</span>
      @endif
    </div>

    @if ($canUpload)
      <button type="button" class="btn btn-dark btn-sm px-3 d-inline-flex align-items-center gap-1.5 min-h-40 fw-medium" 
              data-bs-toggle="modal" data-bs-target="#{{ $modalId }}">
        <i class="bi bi-paperclip"></i>
        <span>Tambah Lampiran</span>
      </button>
    @else
      <span class="text-muted small fst-italic text-xs">Maksimal 5 lampiran</span>
    @endif
  </div>

  @if ($attachments->isEmpty())
    <div class="p-4 text-center text-muted bg-white-50 rounded-3 border border-dashed border-slate-200">
      <i class="bi bi-paperclip d-block fs-3 mb-1 text-secondary"></i>
      <div class="fw-medium text-dark small">Belum ada lampiran berkas atau foto</div>
      <p class="text-muted mb-2 text-xs">Unggah foto fisik, nota servis, kartu garansi, atau buku manual (maks 5 MB).</p>
      @if ($canUpload)
        <button type="button" class="btn btn-glass btn-sm px-3 min-h-40" data-bs-toggle="modal" data-bs-target="#{{ $modalId }}">
          <i class="bi bi-plus-lg me-1"></i> Unggah Berkas Pertama
        </button>
      @endif
    </div>
  @else
    <div class="row g-2.5">
      @foreach ($attachments as $att)
        <div class="col-sm-6 col-md-4">
          <div class="p-2.5 rounded-3 bg-white border border-slate-200 h-100 d-flex flex-column justify-content-between position-relative shadow-2xs">
            <div class="d-flex align-items-start gap-2.5 mb-2">
              @if ($att->is_image)
                <a href="{{ $att->url }}" target="_blank" class="d-block flex-shrink-0 rounded-2 overflow-hidden border bg-light preview-thumb" title="Lihat Foto">
                  <img src="{{ $att->url }}" alt="{{ $att->original_name }}" class="w-100 h-100 object-fit-cover" loading="lazy">
                </a>
              @else
                <a href="{{ $att->url }}" target="_blank" class="d-flex align-items-center justify-content-center flex-shrink-0 rounded-2 border bg-light text-danger text-decoration-none preview-thumb" title="Buka Dokumen PDF">
                  <i class="bi bi-file-earmark-pdf fs-3"></i>
                </a>
              @endif

              <div class="overflow-hidden me-auto pe-1">
                <a href="{{ $att->url }}" target="_blank" class="fw-semibold text-dark text-truncate d-block small text-decoration-none mb-0.5" title="{{ $att->original_name }}">
                  {{ $att->original_name }}
                </a>
                <div class="text-muted text-2xs">
                  <span class="text-uppercase fw-medium">{{ $att->is_image ? 'Gambar' : 'Dokumen PDF' }}</span> • {{ $att->formatted_size }}
                </div>
                <div class="text-muted text-3xs">
                  {{ $att->created_at->format('d M Y, H:i') }}
                </div>
              </div>
            </div>

            <!-- Action: Buka & Hapus -->
            <div class="d-flex justify-content-between align-items-center pt-2 border-top border-slate-200">
              <a href="{{ $att->url }}" target="_blank" class="text-secondary small text-decoration-none d-inline-flex align-items-center gap-1 text-xs">
                <i class="bi bi-box-arrow-up-right"></i> Buka
              </a>

              <form action="{{ route('attachments.destroy', $att->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus lampiran ini?');" class="m-0">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-link text-danger p-0 text-decoration-none small d-inline-flex align-items-center gap-1 text-xs" title="Hapus Lampiran">
                  <i class="bi bi-trash"></i> Hapus
                </button>
              </form>
            </div>
          </div>
        </div>
      @endforeach
    </div>
  @endif

  <!-- Modal Upload -->
  @if ($canUpload)
    <div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-panel border-0 shadow-lg">
          <form action="{{ route('attachments.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="attachable_type" value="{{ $modelClass }}">
            <input type="hidden" name="attachable_id" value="{{ $model->getKey() }}">

            <div class="modal-header border-bottom border-slate-200 py-3">
              <h6 class="modal-title fw-bold text-dark"><i class="bi bi-paperclip me-1.5"></i> Unggah Lampiran Baru</h6>
              <button type="button" class="btn-close small" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body py-3">
              <div class="mb-3">
                <label class="form-label text-muted small mb-1">Pilih Berkas (Foto / PDF) <span class="text-danger">*</span></label>
                <input type="file" name="file" class="form-control" required accept=".jpg,.jpeg,.png,.webp,.pdf,image/jpeg,image/png,image/webp,application/pdf">
                <div class="form-text text-muted small mt-1.5">
                  Format yang didukung: JPG, PNG, WEBP, atau PDF. Maksimal 5 MB per berkas.
                </div>
              </div>

              <div class="p-2.5 rounded-3 bg-light border text-muted small text-xs">
                <i class="bi bi-shield-lock me-1"></i> Berkas disimpan secara privat dan hanya dapat diakses oleh akun Anda.
              </div>
            </div>

            <div class="modal-footer border-top border-slate-200 py-2.5">
              <button type="button" class="btn btn-glass btn-sm" data-bs-dismiss="modal">Batal</button>
              <button type="submit" class="btn btn-dark btn-sm px-4">Unggah Sekarang</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  @endif
</div>
