<div class="tab-pane fade show active" id="ringkasan" role="tabpanel" aria-labelledby="tab-ringkasan-btn">
  <div class="glass-panel p-4 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom border-slate-200">
      <div class="d-flex align-items-center gap-2.5">
        @if ($homeAsset->primary_image_url)
          <a href="{{ $homeAsset->primary_image_url }}" target="_blank">
            <img src="{{ $homeAsset->primary_image_url }}" alt="{{ $homeAsset->name }}" class="rounded-2 object-fit-cover border preview-thumb">
          </a>
        @endif
        <div>
          <span class="badge bg-light text-dark border px-2.5 py-1 text-uppercase me-2 text-2xs tracking-wider">
            {{ $homeAsset->category_label }}
          </span>
          <span class="text-muted small"><i class="bi bi-geo-alt me-1"></i>{{ $homeAsset->room }}</span>
        </div>
      </div>
      <span class="chip {{ $homeAsset->condition_chip_class }} py-1 px-2.5">
        <span class="chip-dot"></span> Kondisi: {{ $homeAsset->condition_label }}
      </span>
    </div>

    <div class="row g-3">
      <div class="col-md-6">
        <table class="table table-sm table-borderless mb-0 text-sm">
          <tbody>
            <tr>
              <td class="text-muted w-max-160">Merek</td>
              <td class="fw-semibold text-dark">{{ $homeAsset->brand ?: '-' }}</td>
            </tr>
            <tr>
              <td class="text-muted">Model / Tipe</td>
              <td class="text-dark">{{ $homeAsset->model ?: '-' }}</td>
            </tr>
            <tr>
              <td class="text-muted">Ruangan / Area</td>
              <td class="text-dark">{{ $homeAsset->room }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="col-md-6">
        <table class="table table-sm table-borderless mb-0 text-sm">
          <tbody>
            <tr>
              <td class="text-muted w-max-180">Tanggal Pasang / Beli</td>
              <td class="text-dark">{{ $homeAsset->purchased_at ? $homeAsset->purchased_at->format('d F Y') : '-' }}</td>
            </tr>
            <tr>
              <td class="text-muted">Terakhir Diperiksa</td>
              <td class="text-dark">{{ $homeAsset->last_inspected_at ? $homeAsset->last_inspected_at->format('d F Y') : '-' }}</td>
            </tr>
            <tr>
              <td class="text-muted">Status Fisik</td>
              <td class="fw-semibold text-dark">{{ $homeAsset->condition_label }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    @if ($homeAsset->notes)
      <div class="mt-4 pt-3 border-top border-slate-200">
        <h6 class="fw-bold text-dark mb-1 text-sm">Catatan Perawatan</h6>
        <p class="text-muted small mb-0">{{ $homeAsset->notes }}</p>
      </div>
    @endif
  </div>

  <!-- Lampiran & Galeri Foto Aset -->
  <div class="glass-panel p-4 mb-4">
    <x-attachments :model="$homeAsset" />
  </div>
</div>
