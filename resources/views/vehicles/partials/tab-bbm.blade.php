<div class="tab-pane fade" id="bbm" role="tabpanel" aria-labelledby="tab-v-bbm-btn">
  <div class="glass-panel p-4 mb-4">
    <!-- Header Tab -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
      <div>
        <h6 class="fw-bold text-dark mb-0">Catatan Pengisian & Efisiensi BBM</h6>
        <span class="text-muted small">Pantau konsumsi bahan bakar (metode tangki penuh), rata-rata efisiensi, dan biaya per kilometer.</span>
      </div>
      <button class="btn btn-dark btn-sm px-3 d-inline-flex align-items-center gap-1.5" data-bs-toggle="modal" data-bs-target="#modalCatatBBM" style="min-height: 40px; font-weight: 500;">
        <i class="bi bi-fuel-pump"></i>
        <span>Catat BBM</span>
      </button>
    </div>

    <!-- 4 Kartu Metrik Ringkas -->
    <div class="row g-3 mb-4">
      <div class="col-sm-6 col-lg-3">
        <div class="p-3 rounded-3 bg-white border border-slate-200 h-100">
          <small class="text-muted d-block" style="font-size: 0.75rem;">Konsumsi Terakhir</small>
          <div class="fw-bold fs-4 text-dark mt-0.5">
            {{ $fuelMetrics['latest_km_l'] !== null ? number_format($fuelMetrics['latest_km_l'], 2, ',', '.') . ' km/L' : '-' }}
          </div>
          <small class="text-muted" style="font-size: 0.72rem;">Metode tangki penuh</small>
        </div>
      </div>

      <div class="col-sm-6 col-lg-3">
        <div class="p-3 rounded-3 bg-white border border-slate-200 h-100">
          <small class="text-muted d-block" style="font-size: 0.75rem;">Rata-rata (5 Terakhir)</small>
          <div class="fw-bold fs-4 text-dark mt-0.5">
            {{ $fuelMetrics['average_5_km_l'] !== null ? number_format($fuelMetrics['average_5_km_l'], 2, ',', '.') . ' km/L' : '-' }}
          </div>
          <small class="text-muted" style="font-size: 0.72rem;">Efisiensi 5 interval terakhir</small>
        </div>
      </div>

      <div class="col-sm-6 col-lg-3">
        <div class="p-3 rounded-3 bg-white border border-slate-200 h-100">
          <small class="text-muted d-block" style="font-size: 0.75rem;">Biaya per KM</small>
          <div class="fw-bold fs-4 text-dark mt-0.5">
            {{ $fuelMetrics['cost_per_km'] !== null ? 'Rp ' . number_format($fuelMetrics['cost_per_km'], 0, ',', '.') . '/km' : '-' }}
          </div>
          <small class="text-muted" style="font-size: 0.72rem;">Rata-rata biaya perjalanan</small>
        </div>
      </div>

      <div class="col-sm-6 col-lg-3">
        <div class="p-3 rounded-3 bg-white border border-slate-200 h-100">
          <small class="text-muted d-block" style="font-size: 0.75rem;">Pengeluaran Bulan Ini</small>
          <div class="fw-bold fs-4 text-dark mt-0.5">
            Rp {{ number_format($fuelMetrics['total_cost_this_month'], 0, ',', '.') }}
          </div>
          <small class="text-muted" style="font-size: 0.72rem;">
            {{ number_format($fuelMetrics['total_liters_this_month'], 2, ',', '.') }} Liter terisi
          </small>
        </div>
      </div>
    </div>

    <!-- Riwayat 10 Pengisian Terakhir -->
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h6 class="fw-bold text-dark mb-0">10 Catatan Pengisian Terakhir</h6>
      @if ($fuelMetrics['logs']->count() > 10)
        <span class="text-muted small">Menampilkan 10 dari total {{ $fuelMetrics['logs']->count() }} catatan</span>
      @endif
    </div>

    @if ($fuelMetrics['recent_logs']->isEmpty())
      <div class="p-4 text-center text-muted bg-white-50 rounded-3 border border-dashed border-slate-200">
        <i class="bi bi-fuel-pump d-block fs-3 mb-1 text-secondary"></i>
        <div class="fw-medium text-dark small">Belum ada catatan pengisian BBM</div>
        <p class="text-muted mb-3" style="font-size: 0.78rem;">Catat pengisian bensin untuk memantau konsumsi bahan bakar (km/L) dan biaya operasional.</p>
        <button class="btn btn-dark btn-sm px-3" data-bs-toggle="modal" data-bs-target="#modalCatatBBM" style="min-height: 44px;">
          <i class="bi bi-plus-lg me-1"></i> Catat Pengisian Pertama
        </button>
      </div>
    @else
      <div class="table-responsive rounded-3 border border-slate-200 bg-white">
        <table class="table table-hover align-middle mb-0" style="font-size: 0.84rem;">
          <thead class="table-light text-muted" style="font-size: 0.75rem; letter-spacing: 0.04em;">
            <tr>
              <th class="ps-3 py-2.5">TANGGAL</th>
              <th class="py-2.5">ODOMETER</th>
              <th class="py-2.5">VOLUME</th>
              <th class="py-2.5">TOTAL BIAYA</th>
              <th class="py-2.5">HARGA/LITER</th>
              <th class="py-2.5">KONSUMSI</th>
              <th class="py-2.5">JENIS & SPBU</th>
              <th class="py-2.5">NOTA</th>
              <th class="text-end pe-3 py-2.5">AKSI</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($fuelMetrics['recent_logs'] as $log)
              <tr>
                <td class="ps-3 fw-medium text-dark">
                  {{ $log->filled_at ? $log->filled_at->format('d M Y') : '-' }}
                </td>
                <td class="font-monospace text-dark">
                  {{ number_format($log->odometer) }} KM
                </td>
                <td>
                  <span class="fw-semibold text-dark">{{ number_format($log->liters, 2, ',', '.') }} L</span>
                  @if ($log->is_full_tank)
                    <span class="badge bg-success-subtle text-success-emphasis border border-success ms-1" style="font-size: 0.65rem;">Penuh</span>
                  @else
                    <span class="badge bg-secondary-subtle text-secondary border ms-1" style="font-size: 0.65rem;">Parsial</span>
                  @endif
                </td>
                <td class="fw-semibold text-dark">
                  {{ $log->formatted_total_cost }}
                </td>
                <td class="text-muted small">
                  {{ $log->formatted_price_per_liter }}
                </td>
                <td>
                  @if ($log->consumption_km_l !== null)
                    <span class="badge bg-primary-subtle text-primary-emphasis border border-primary fw-bold" style="font-size: 0.75rem;">
                      {{ number_format($log->consumption_km_l, 2, ',', '.') }} km/L
                    </span>
                  @else
                    <span class="text-muted" title="{{ $log->is_full_tank ? 'Pengisian penuh pertama / titik awal perhitungan' : 'Pengisian parsial tidak dihitung sendiri' }}">-</span>
                  @endif
                </td>
                <td>
                  <div class="text-dark fw-medium">{{ $log->fuel_grade ?: '-' }}</div>
                  @if ($log->station)
                    <div class="text-muted font-monospace" style="font-size: 0.72rem;">{{ $log->station }}</div>
                  @endif
                </td>
                <td>
                  @if ($log->attachments->isNotEmpty())
                    <a href="{{ route('attachments.show', $log->attachments->first()->id) }}" target="_blank" class="btn btn-glass btn-sm p-1 px-2" style="font-size: 0.75rem;" title="Lihat Nota BBM">
                      <i class="bi bi-paperclip text-primary"></i> Nota
                    </a>
                  @else
                    <span class="text-muted small">-</span>
                  @endif
                </td>
                <td class="text-end pe-3">
                  <div class="d-inline-flex align-items-center gap-1.5">
                    <button type="button" 
                            class="btn btn-glass btn-sm p-1.5 btn-edit-fuel-log" 
                            data-bs-toggle="modal" 
                            data-bs-target="#modalEditBBM" 
                            data-id="{{ $log->id }}"
                            data-filled-at="{{ $log->filled_at ? $log->filled_at->format('Y-m-d') : '' }}"
                            data-odometer="{{ $log->odometer }}"
                            data-liters="{{ $log->liters }}"
                            data-total-cost="{{ $log->total_cost }}"
                            data-fuel-grade="{{ $log->fuel_grade ?? '' }}"
                            data-is-full-tank="{{ $log->is_full_tank ? '1' : '0' }}"
                            data-station="{{ $log->station ?? '' }}"
                            data-notes="{{ $log->notes ?? '' }}"
                            title="Edit Catatan BBM"
                            style="min-height: 34px; min-width: 34px;">
                      <i class="bi bi-pencil"></i>
                    </button>
                    <form action="{{ route('fuel-logs.destroy', $log->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus catatan BBM ini?');" class="m-0">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-glass btn-sm p-1.5 text-danger" title="Hapus Catatan" style="min-height: 34px; min-width: 34px;">
                        <i class="bi bi-trash"></i>
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>
</div>
