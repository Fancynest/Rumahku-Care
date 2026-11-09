@extends('layouts.app')

@section('title', 'Detail: ' . $homeAsset->name . ' - Rumahku Care')

@section('content')
<!-- Header Back Button & Actions -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
  <div class="d-flex align-items-center gap-2">
    <a href="{{ route('home-assets.index') }}" class="btn btn-glass btn-sm p-2 rounded-3" title="Kembali ke Daftar Aset" style="min-height: 40px; min-width: 40px; display: inline-flex; align-items: center; justify-content: center;">
      <i class="bi bi-arrow-left"></i>
    </a>
    <div>
      <h4 class="fw-bold mb-0 text-dark" style="letter-spacing: -0.02em;">{{ $homeAsset->name }}</h4>
      <p class="text-muted mb-0 small">Kategori: {{ $homeAsset->category_label }} • Ruangan: {{ $homeAsset->room }}</p>
    </div>
  </div>

  <div class="d-flex align-items-center gap-2">
    <button class="btn btn-glass btn-sm px-3" data-bs-toggle="modal" data-bs-target="#modalEditAset" style="min-height: 40px;">
      <i class="bi bi-pencil me-1.5"></i> Edit Aset
    </button>
    <form action="{{ route('home-assets.destroy', $homeAsset->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus aset ini? Seluruh riwayat dan lampiran terkait akan ikut terhapus.');" class="m-0">
      @csrf
      @method('DELETE')
      <button type="submit" class="btn btn-glass btn-sm px-3 text-danger" style="min-height: 40px;">
        <i class="bi bi-trash me-1.5"></i> Hapus
      </button>
    </form>
  </div>
</div>

<!-- Nav Tabs Bootstrap (5 Tab: Ringkasan, Jadwal, Inspeksi, Kerusakan, Dokumen) -->
<ul class="nav nav-tabs border-bottom border-slate-200 mb-4" id="assetTabs" role="tablist">
  <li class="nav-item" role="presentation">
    <button class="nav-link active fw-medium text-dark d-flex align-items-center gap-1.5 px-3 py-2.5" id="tab-ringkasan-btn" data-bs-toggle="tab" data-bs-target="#ringkasan" type="button" role="tab" aria-controls="ringkasan" aria-selected="true" style="min-height: 44px;">
      <i class="bi bi-info-circle"></i>
      <span>Ringkasan</span>
    </button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link fw-medium text-secondary d-flex align-items-center gap-1.5 px-3 py-2.5" id="tab-jadwal-btn" data-bs-toggle="tab" data-bs-target="#jadwal" type="button" role="tab" aria-controls="jadwal" aria-selected="false" style="min-height: 44px;">
      <i class="bi bi-calendar-check"></i>
      <span>Jadwal</span>
      @if ($schedules->isNotEmpty())
        <span class="badge bg-light text-dark border ms-1" style="font-size: 0.65rem;">{{ $schedules->count() }}</span>
      @endif
    </button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link fw-medium text-secondary d-flex align-items-center gap-1.5 px-3 py-2.5" id="tab-inspeksi-btn" data-bs-toggle="tab" data-bs-target="#inspeksi" type="button" role="tab" aria-controls="inspeksi" aria-selected="false" style="min-height: 44px;">
      <i class="bi bi-clipboard-check"></i>
      <span>Inspeksi</span>
      @if ($homeAsset->inspectionRuns->isNotEmpty())
        <span class="badge bg-light text-dark border ms-1" style="font-size: 0.65rem;">{{ $homeAsset->inspectionRuns->count() }}</span>
      @endif
    </button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link fw-medium text-secondary d-flex align-items-center gap-1.5 px-3 py-2.5" id="tab-kerusakan-btn" data-bs-toggle="tab" data-bs-target="#kerusakan" type="button" role="tab" aria-controls="kerusakan" aria-selected="false" style="min-height: 44px;">
      <i class="bi bi-tools"></i>
      <span>Kerusakan</span>
      @php
        $openDamages = $homeAsset->damageReports->whereIn('status', ['dilaporkan', 'dalam_perbaikan']);
      @endphp
      @if ($openDamages->isNotEmpty())
        <span class="badge bg-danger text-white ms-1" style="font-size: 0.65rem;">{{ $openDamages->count() }}</span>
      @endif
    </button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link fw-medium text-secondary d-flex align-items-center gap-1.5 px-3 py-2.5" id="tab-dokumen-btn" data-bs-toggle="tab" data-bs-target="#dokumen" type="button" role="tab" aria-controls="dokumen" aria-selected="false" style="min-height: 44px;">
      <i class="bi bi-file-earmark-text"></i>
      <span>Dokumen</span>
      @php
        $activeDocs = $homeAsset->documents->whereNull('archived_at');
      @endphp
      @if ($activeDocs->isNotEmpty())
        <span class="badge bg-light text-dark border ms-1" style="font-size: 0.65rem;">{{ $activeDocs->count() }}</span>
      @endif
    </button>
  </li>
</ul>

<!-- Tab Contents -->
<div class="tab-content" id="assetTabContent">
  
  <!-- ================= TAB 1: RINGKASAN ================= -->
  <div class="tab-pane fade show active" id="ringkasan" role="tabpanel" aria-labelledby="tab-ringkasan-btn">
    <div class="glass-panel p-4 mb-4">
      <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom border-slate-200">
        <div class="d-flex align-items-center gap-2.5">
          @if ($homeAsset->primary_image_url)
            <a href="{{ $homeAsset->primary_image_url }}" target="_blank">
              <img src="{{ $homeAsset->primary_image_url }}" alt="{{ $homeAsset->name }}" class="rounded-2 object-fit-cover border" style="width: 52px; height: 52px;">
            </a>
          @endif
          <div>
            <span class="badge bg-light text-dark border px-2.5 py-1 text-uppercase me-2" style="font-size: 0.72rem; letter-spacing: 0.05em;">
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
          <table class="table table-sm table-borderless mb-0" style="font-size: 0.88rem;">
            <tbody>
              <tr>
                <td class="text-muted" style="width: 40%;">Merek</td>
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
          <table class="table table-sm table-borderless mb-0" style="font-size: 0.88rem;">
            <tbody>
              <tr>
                <td class="text-muted" style="width: 45%;">Tanggal Pasang / Beli</td>
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
          <h6 class="fw-bold text-dark mb-1" style="font-size: 0.85rem;">Catatan Perawatan</h6>
          <p class="text-muted small mb-0">{{ $homeAsset->notes }}</p>
        </div>
      @endif
    </div>

    <!-- Lampiran & Galeri Foto Aset -->
    <div class="glass-panel p-4 mb-4">
      <x-attachments :model="$homeAsset" />
    </div>
  </div>

  <!-- ================= TAB 2: JADWAL ================= -->
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

  <!-- ================= TAB 3: INSPEKSI ================= -->
  <div class="tab-pane fade" id="inspeksi" role="tabpanel" aria-labelledby="tab-inspeksi-btn">
    <div class="glass-panel p-4 mb-4">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <h6 class="fw-bold text-dark mb-0">Pemeriksaan Rutin & Keselamatan</h6>
          <span class="text-muted small">Catatan checklist berkala kondisi teknis aset.</span>
        </div>
        <button class="btn btn-dark btn-sm px-3 d-inline-flex align-items-center gap-1.5" data-bs-toggle="modal" data-bs-target="#modalMulaiInspeksi" style="min-height: 40px; font-weight: 500;">
          <i class="bi bi-clipboard-plus"></i>
          <span>Mulai Inspeksi</span>
        </button>
      </div>

      @if ($homeAsset->inspectionRuns->isEmpty())
        <div class="p-4 text-center text-muted bg-white-50 rounded-3 border border-dashed border-slate-200">
          <i class="bi bi-clipboard-x d-block fs-3 mb-1 text-secondary"></i>
          <div class="fw-medium text-dark small">Belum ada riwayat inspeksi tercatat</div>
          <p class="text-muted mb-3" style="font-size: 0.78rem;">Lakukan inspeksi keselamatan atau kelayakan rutin untuk mendeteksi potensi masalah lebih dini.</p>
          <button class="btn btn-dark btn-sm px-3" data-bs-toggle="modal" data-bs-target="#modalMulaiInspeksi" style="min-height: 44px;">
            <i class="bi bi-clipboard-plus me-1"></i> Mulai Inspeksi Pertama
          </button>
        </div>
      @else
        <div class="d-flex flex-column gap-3">
          @foreach ($homeAsset->inspectionRuns as $run)
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
                      <span class="badge {{ $item->status_badge_class }} px-2 py-0.5" style="font-size: 0.72rem;">
                        {{ $item->status_label }}
                      </span>
                      <span class="text-dark small fw-medium">{{ $item->item_label }}</span>
                      @if ($item->notes)
                        <span class="text-muted small">— {{ $item->notes }}</span>
                      @endif
                    </div>
                    @if (in_array($item->status, ['perhatian', 'bermasalah']))
                      @if ($item->damage_report_id)
                        <span class="badge bg-secondary-subtle text-secondary border px-2 py-1" style="font-size: 0.72rem;">
                          <i class="bi bi-check-circle me-1"></i>Sudah Dikonversi
                        </span>
                      @else
                        <form action="{{ route('inspections.convert-damage', $item->id) }}" method="POST" class="m-0" onsubmit="return confirm('Konversikan temuan ini menjadi laporan kerusakan aktif?');">
                          @csrf
                          <button type="submit" class="btn btn-glass btn-sm py-1 px-2.5 text-danger d-inline-flex align-items-center gap-1" style="min-height: 36px; font-size: 0.75rem;">
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

  <!-- ================= TAB 4: KERUSAKAN ================= -->
  <div class="tab-pane fade" id="kerusakan" role="tabpanel" aria-labelledby="tab-kerusakan-btn">
    <div class="glass-panel p-4 mb-4">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <h6 class="fw-bold text-dark mb-0">Riwayat Kerusakan & Perbaikan</h6>
          <span class="text-muted small">Pencatatan gangguan, kerusakan fisik, dan biaya teknisi pelaksana.</span>
        </div>
        <button class="btn btn-dark btn-sm px-3 d-inline-flex align-items-center gap-1.5" data-bs-toggle="modal" data-bs-target="#modalLaporKerusakan" style="min-height: 40px; font-weight: 500;">
          <i class="bi bi-tools"></i>
          <span>Laporkan Kerusakan</span>
        </button>
      </div>

      @if ($homeAsset->damageReports->isEmpty())
        <div class="p-4 text-center text-muted bg-white-50 rounded-3 border border-dashed border-slate-200">
          <i class="bi bi-shield-check d-block fs-3 mb-1 text-success"></i>
          <div class="fw-medium text-dark small">Tidak ada laporan kerusakan</div>
          <p class="text-muted mb-3" style="font-size: 0.78rem;">Aset ini tidak memiliki catatan kerusakan yang dilaporkan.</p>
          <button class="btn btn-glass btn-sm px-3" data-bs-toggle="modal" data-bs-target="#modalLaporKerusakan" style="min-height: 44px;">
            <i class="bi bi-plus-lg me-1"></i> Catat Kerusakan Baru
          </button>
        </div>
      @else
        <div class="d-flex flex-column gap-3">
          @foreach ($homeAsset->damageReports as $report)
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
                            data-bs-target="#modalStatusKerusakan"
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
                  <span class="text-muted d-block" style="font-size: 0.7rem;">Teknisi:</span>
                  <span class="fw-medium text-dark">{{ $report->technician_name ?: '-' }}</span>
                </div>
                <div>
                  <span class="text-muted d-block" style="font-size: 0.7rem;">Biaya Perbaikan:</span>
                  <span class="fw-medium text-dark">{{ $report->repair_cost ? 'Rp ' . number_format($report->repair_cost) : '-' }}</span>
                </div>
                @if ($report->notes)
                  <div class="w-100 mt-1 pt-1 border-top border-slate-200">
                    <span class="text-muted d-block" style="font-size: 0.7rem;">Catatan Perbaikan:</span>
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

  <!-- ================= TAB 5: DOKUMEN ================= -->
  <div class="tab-pane fade" id="dokumen" role="tabpanel" aria-labelledby="tab-dokumen-btn">
    <div class="glass-panel p-4 mb-4">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <h6 class="fw-bold text-dark mb-0">Garansi & Dokumen Aset</h6>
          <span class="text-muted small">Kartu garansi, faktur pembelian, manual servis, dan sertifikat resmi.</span>
        </div>
        <button class="btn btn-dark btn-sm px-3 d-inline-flex align-items-center gap-1.5" data-bs-toggle="modal" data-bs-target="#modalTambahDokumen" style="min-height: 40px; font-weight: 500;">
          <i class="bi bi-file-earmark-plus"></i>
          <span>Tambah Dokumen</span>
        </button>
      </div>

      <!-- Dokumen Aktif -->
      @if ($activeDocs->isEmpty())
        <div class="p-4 text-center text-muted bg-white-50 rounded-3 border border-dashed border-slate-200 mb-3">
          <i class="bi bi-file-earmark-x d-block fs-3 mb-1 text-secondary"></i>
          <div class="fw-medium text-dark small">Belum ada dokumen aktif tersimpan</div>
          <p class="text-muted mb-3" style="font-size: 0.78rem;">Simpan kartu garansi atau nota pembelian agar mudah diklaim saat dibutuhkan.</p>
          <button class="btn btn-dark btn-sm px-3" data-bs-toggle="modal" data-bs-target="#modalTambahDokumen" style="min-height: 44px;">
            <i class="bi bi-plus-lg me-1"></i> Tambah Dokumen Sekarang
          </button>
        </div>
      @else
        <div class="d-flex flex-column gap-2.5 mb-4">
          @foreach ($activeDocs as $doc)
            <div class="p-3 rounded-3 bg-white border border-slate-200 d-flex flex-wrap justify-content-between align-items-center gap-2">
              <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                  <span class="badge bg-light text-dark border px-2 py-0.5" style="font-size: 0.72rem;">
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
                  <div class="text-muted small mt-1" style="font-size: 0.75rem;">{{ $doc->notes }}</div>
                @endif
              </div>

              <div class="d-flex align-items-center gap-2">
                @if ($doc->attachments->isNotEmpty())
                  <a href="{{ route('attachments.show', $doc->attachments->first()->id) }}" target="_blank" class="btn btn-glass btn-sm px-2.5" style="min-height: 38px;" title="Lihat Berkas Lampiran">
                    <i class="bi {{ str_contains($doc->attachments->first()->mime, 'pdf') ? 'bi-file-earmark-pdf text-danger' : 'bi-image' }} me-1"></i> Berkas
                  </a>
                @endif
                <button class="btn btn-dark btn-sm px-3 btn-renew-document" 
                        style="min-height: 38px; font-size: 0.78rem;"
                        data-bs-toggle="modal" 
                        data-bs-target="#modalPerpanjangDokumen"
                        data-id="{{ $doc->id }}"
                        data-title="{{ $doc->title }}"
                        data-cost="{{ $doc->cost ?? 0 }}"
                        data-ref="{{ $doc->reference_number ?? '' }}">
                  <i class="bi bi-arrow-repeat me-1"></i> Perpanjang
                </button>
                <form action="{{ route('documents.destroy', $doc->id) }}" method="POST" onsubmit="return confirm('Hapus dokumen ini?');" class="m-0">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="btn btn-glass btn-sm text-danger p-2" title="Hapus Dokumen" style="min-height: 38px; min-width: 38px;">
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
        $archivedDocs = $homeAsset->documents->whereNotNull('archived_at');
      @endphp
      @if ($archivedDocs->isNotEmpty())
        <div class="pt-3 border-top border-slate-200">
          <button class="btn btn-link text-decoration-none text-secondary p-0 small fw-semibold d-flex align-items-center gap-1.5" type="button" data-bs-toggle="collapse" data-bs-target="#collapseArchivedDocs" aria-expanded="false">
            <i class="bi bi-archive"></i>
            <span>Lihat Riwayat Dokumen Terarsip ({{ $archivedDocs->count() }})</span>
            <i class="bi bi-chevron-down small"></i>
          </button>
          
          <div class="collapse mt-3" id="collapseArchivedDocs">
            <div class="d-flex flex-column gap-2">
              @foreach ($archivedDocs as $arc)
                <div class="p-2.5 rounded-2 bg-light border text-muted small d-flex justify-content-between align-items-center">
                  <div>
                    <span class="fw-medium text-dark">{{ $arc->title }}</span>
                    <span class="text-muted font-monospace ms-2">No: {{ $arc->reference_number ?: '-' }}</span>
                    <div class="text-secondary" style="font-size: 0.72rem;">
                      Diarsipkan: {{ $arc->archived_at->format('d/m/Y') }} • Biaya lama: Rp {{ number_format($arc->cost ?? 0) }}
                    </div>
                  </div>
                  @if ($arc->attachments->isNotEmpty())
                    <a href="{{ route('attachments.show', $arc->attachments->first()->id) }}" target="_blank" class="btn btn-glass btn-sm py-1 px-2" style="font-size: 0.72rem;">
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

</div>
@endsection

@section('modals')
<!-- ================= MODAL: EDIT ASET ================= -->
<div class="modal fade" id="modalEditAset" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content glass-panel border-0 shadow-lg">
      <form action="{{ route('home-assets.update', $homeAsset->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="modal-header border-bottom border-slate-200 py-3">
          <h6 class="modal-title fw-bold text-dark">Edit Aset — {{ $homeAsset->name }}</h6>
          <button type="button" class="btn-close small" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        
        <div class="modal-body py-3">
          <div class="mb-3">
            <label class="form-label text-muted small mb-1">Nama Aset <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="name" value="{{ $homeAsset->name }}" required style="min-height: 44px;">
          </div>

          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label text-muted small mb-1">Kategori <span class="text-danger">*</span></label>
              <select class="form-select" name="category" required style="min-height: 44px;">
                @foreach (['bangunan', 'listrik', 'air', 'pendingin', 'dapur', 'kamar_mandi', 'keamanan', 'eksterior', 'peralatan'] as $cat)
                  <option value="{{ $cat }}" {{ $homeAsset->category === $cat ? 'selected' : '' }}>
                    {{ ucfirst(str_replace('_', ' ', $cat)) }}
                  </option>
                @endforeach
              </select>
            </div>
            <div class="col-6">
              <label class="form-label text-muted small mb-1">Lokasi / Ruangan <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="room" value="{{ $homeAsset->room }}" required style="min-height: 44px;">
            </div>
          </div>

          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label text-muted small mb-1">Merek</label>
              <input type="text" class="form-control" name="brand" value="{{ $homeAsset->brand }}" style="min-height: 44px;">
            </div>
            <div class="col-6">
              <label class="form-label text-muted small mb-1">Model / Tipe</label>
              <input type="text" class="form-control" name="model" value="{{ $homeAsset->model }}" style="min-height: 44px;">
            </div>
          </div>

          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label text-muted small mb-1">Tanggal Pasang / Beli</label>
              <input type="date" class="form-control" name="purchased_at" value="{{ $homeAsset->purchased_at?->format('Y-m-d') }}" style="min-height: 44px;">
            </div>
            <div class="col-6">
              <label class="form-label text-muted small mb-1">Kondisi Fisik <span class="text-danger">*</span></label>
              <select class="form-select" name="condition" required style="min-height: 44px;">
                <option value="normal" {{ $homeAsset->condition === 'normal' ? 'selected' : '' }}>Normal</option>
                <option value="perlu_perhatian" {{ $homeAsset->condition === 'perlu_perhatian' ? 'selected' : '' }}>Perlu Perhatian</option>
                <option value="rusak" {{ $homeAsset->condition === 'rusak' ? 'selected' : '' }}>Rusak</option>
              </select>
            </div>
          </div>

          <div class="mb-2">
            <label class="form-label text-muted small mb-1">Catatan Tambahan</label>
            <textarea class="form-control" name="notes" rows="2">{{ $homeAsset->notes }}</textarea>
          </div>
        </div>

        <div class="modal-footer border-top border-slate-200 py-2.5">
          <button type="button" class="btn btn-glass btn-sm" data-bs-dismiss="modal" style="min-height: 44px; padding: 0.4rem 1.2rem;">Batal</button>
          <button type="submit" class="btn btn-dark btn-sm px-4" style="min-height: 44px;">Simpan Perubahan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ================= MODAL: MULAI INSPEKSI ================= -->
<div class="modal fade" id="modalMulaiInspeksi" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content glass-panel border-0 shadow-lg">
      <form action="{{ route('inspections.store') }}" method="POST">
        @csrf
        <input type="hidden" name="inspectable_type" value="{{ get_class($homeAsset) }}">
        <input type="hidden" name="inspectable_id" value="{{ $homeAsset->id }}">

        <div class="modal-header border-bottom border-slate-200 py-3">
          <h6 class="modal-title fw-bold text-dark">Mulai Inspeksi Berkala — {{ $homeAsset->name }}</h6>
          <button type="button" class="btn-close small" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body py-3">
          <div class="row g-2 mb-3">
            <div class="col-md-6">
              <label class="form-label text-muted small mb-1">Pilih Template Checklist</label>
              <select class="form-select" id="selectAssetInspectionTemplate" name="template_key" style="min-height: 44px;">
                @foreach ($inspectionTemplates as $key => $tmpl)
                  @if (($tmpl['target_type'] ?? '') === 'home_asset')
                    <option value="{{ $key }}" data-title="{{ $tmpl['title'] }}" data-items='@json($tmpl['items'])'>
                      {{ $tmpl['title'] }}
                    </option>
                  @endif
                @endforeach
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label text-muted small mb-1">Tanggal Inspeksi <span class="text-danger">*</span></label>
              <input type="date" name="inspected_at" class="form-control" value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}" required style="min-height: 44px;">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label text-muted small mb-1">Judul Sesi Inspeksi <span class="text-danger">*</span></label>
            <input type="text" name="title" id="inputAssetInspectionTitle" class="form-control" value="Inspeksi Keselamatan Rumah" required style="min-height: 44px;">
          </div>

          <!-- Dynamic Checklist Items Container -->
          <div class="mb-3">
            <label class="form-label text-muted small mb-1">Checklist Hasil Pemeriksaan</label>
            <div id="containerAssetInspectionItems" class="d-flex flex-column gap-2.5">
              <!-- Rendered via JS based on template -->
            </div>
          </div>

          <div class="mb-2">
            <label class="form-label text-muted small mb-1">Catatan Keseluruhan</label>
            <textarea name="notes" class="form-control" rows="2" placeholder="Catatan umum mengenai kondisi pemeriksaan..."></textarea>
          </div>
        </div>

        <div class="modal-footer border-top border-slate-200 py-2.5">
          <button type="button" class="btn btn-glass btn-sm" data-bs-dismiss="modal" style="min-height: 44px; padding: 0.4rem 1.2rem;">Batal</button>
          <button type="submit" class="btn btn-dark btn-sm px-4" style="min-height: 44px;">Simpan Hasil Inspeksi</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ================= MODAL: LAPOR KERUSAKAN ================= -->
<div class="modal fade" id="modalLaporKerusakan" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content glass-panel border-0 shadow-lg">
      <form action="{{ route('damage-reports.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="reportable_type" value="{{ get_class($homeAsset) }}">
        <input type="hidden" name="reportable_id" value="{{ $homeAsset->id }}">

        <div class="modal-header border-bottom border-slate-200 py-3">
          <h6 class="modal-title fw-bold text-dark">Laporkan Kerusakan — {{ $homeAsset->name }}</h6>
          <button type="button" class="btn-close small" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body py-3">
          <div class="mb-3">
            <label class="form-label text-muted small mb-1">Judul / Gejala Kerusakan <span class="text-danger">*</span></label>
            <input type="text" name="title" class="form-control" placeholder="Contoh: AC tidak dingin / pipa bocor" required style="min-height: 44px;">
          </div>

          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label text-muted small mb-1">Tingkat Keparahan <span class="text-danger">*</span></label>
              <select name="severity" class="form-select" required style="min-height: 44px;">
                <option value="ringan">Ringan (Masih berfungsi)</option>
                <option value="sedang" selected>Sedang (Terganggu)</option>
                <option value="berat">Berat (Mati total / Bahaya)</option>
              </select>
            </div>
            <div class="col-6">
              <label class="form-label text-muted small mb-1">Tanggal Diketahui <span class="text-danger">*</span></label>
              <input type="date" name="reported_at" class="form-control" value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}" required style="min-height: 44px;">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label text-muted small mb-1">Deskripsi Kerusakan</label>
            <textarea name="description" class="form-control" rows="2" placeholder="Jelaskan detail bunyi aneh, letak kebocoran, atau pemicunya..."></textarea>
          </div>

          <div class="mb-2">
            <label class="form-label text-muted small mb-1">Foto Kerusakan (Opsional, Maks 5 MB)</label>
            <input type="file" name="photo" class="form-control" accept="image/*,application/pdf" style="min-height: 44px;">
          </div>
        </div>

        <div class="modal-footer border-top border-slate-200 py-2.5">
          <button type="button" class="btn btn-glass btn-sm" data-bs-dismiss="modal" style="min-height: 44px; padding: 0.4rem 1.2rem;">Batal</button>
          <button type="submit" class="btn btn-dark btn-sm px-4" style="min-height: 44px;">Catat Kerusakan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ================= MODAL: UPDATE STATUS KERUSAKAN ================= -->
<div class="modal fade" id="modalStatusKerusakan" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content glass-panel border-0 shadow-lg">
      <form action="" method="POST" id="formStatusKerusakan" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="modal-header border-bottom border-slate-200 py-3">
          <h6 class="modal-title fw-bold text-dark" id="modalTitleStatusKerusakan">Perbarui Status Perbaikan</h6>
          <button type="button" class="btn-close small" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body py-3">
          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label text-muted small mb-1">Status Perbaikan <span class="text-danger">*</span></label>
              <select name="status" id="inputStatusDamage" class="form-select" required style="min-height: 44px;">
                <option value="dilaporkan">Dilaporkan</option>
                <option value="dalam_perbaikan">Dalam Perbaikan</option>
                <option value="selesai">Selesai Diperbaiki</option>
              </select>
            </div>
            <div class="col-6">
              <label class="form-label text-muted small mb-1">Tanggal Selesai</label>
              <input type="date" name="resolved_at" id="inputStatusResolvedAt" class="form-control" value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}" style="min-height: 44px;">
            </div>
          </div>

          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label text-muted small mb-1">Teknisi / Vendor</label>
              <input type="text" name="technician_name" id="inputStatusTech" class="form-control" placeholder="Nama teknisi" style="min-height: 44px;">
            </div>
            <div class="col-6">
              <label class="form-label text-muted small mb-1">Biaya Perbaikan (Rp)</label>
              <input type="number" name="repair_cost" id="inputStatusCost" class="form-control" value="0" min="0" step="5000" style="min-height: 44px;">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label text-muted small mb-1">Catatan Hasil Pengerjaan</label>
            <textarea name="notes" id="inputStatusNotes" class="form-control" rows="2" placeholder="Komponen yang diganti, garansi teknisi, dll..."></textarea>
          </div>

          <div class="mb-2">
            <label class="form-label text-muted small mb-1">Upload Nota / Bukti Perbaikan (Opsional)</label>
            <input type="file" name="receipt" class="form-control" accept="image/*,application/pdf" style="min-height: 44px;">
          </div>
        </div>

        <div class="modal-footer border-top border-slate-200 py-2.5">
          <button type="button" class="btn btn-glass btn-sm" data-bs-dismiss="modal" style="min-height: 44px; padding: 0.4rem 1.2rem;">Batal</button>
          <button type="submit" class="btn btn-dark btn-sm px-4" style="min-height: 44px;">Perbarui Status</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ================= MODAL: TAMBAH DOKUMEN ================= -->
<div class="modal fade" id="modalTambahDokumen" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content glass-panel border-0 shadow-lg">
      <form action="{{ route('documents.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="subject_type" value="{{ get_class($homeAsset) }}">
        <input type="hidden" name="subject_id" value="{{ $homeAsset->id }}">

        <div class="modal-header border-bottom border-slate-200 py-3">
          <h6 class="modal-title fw-bold text-dark">Tambah Dokumen — {{ $homeAsset->name }}</h6>
          <button type="button" class="btn-close small" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body py-3">
          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label text-muted small mb-1">Tipe Dokumen <span class="text-danger">*</span></label>
              <select name="type" class="form-select" required style="min-height: 44px;">
                @foreach ($documentTypes as $typeKey => $typeLabel)
                  <option value="{{ $typeKey }}">{{ $typeLabel }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-6">
              <label class="form-label text-muted small mb-1">Nomor Referensi / Seri</label>
              <input type="text" name="reference_number" class="form-control" placeholder="Contoh: GAR-123456" style="min-height: 44px;">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label text-muted small mb-1">Judul Dokumen <span class="text-danger">*</span></label>
            <input type="text" name="title" class="form-control" placeholder="Contoh: Garansi Resmi Kompresor 3 Tahun" required style="min-height: 44px;">
          </div>

          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label text-muted small mb-1">Tanggal Terbit</label>
              <input type="date" name="issued_at" class="form-control" value="{{ date('Y-m-d') }}" style="min-height: 44px;">
            </div>
            <div class="col-6">
              <label class="form-label text-muted small mb-1">Tanggal Jatuh Tempo / Habis</label>
              <input type="date" name="expires_at" class="form-control" style="min-height: 44px;">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label text-muted small mb-1">Biaya / Nilai Pertanggungan (Rp)</label>
            <input type="number" name="cost" class="form-control" value="0" min="0" step="1000" style="min-height: 44px;">
          </div>

          <div class="mb-3">
            <label class="form-label text-muted small mb-1">Catatan Tambahan</label>
            <textarea name="notes" class="form-control" rows="2" placeholder="Ketentuan klaim, kontak vendor..."></textarea>
          </div>

          <div class="mb-2">
            <label class="form-label text-muted small mb-1">Berkas Dokumen (Foto / PDF, Maks 5 MB)</label>
            <input type="file" name="file" class="form-control" accept="image/*,application/pdf" style="min-height: 44px;">
          </div>
        </div>

        <div class="modal-footer border-top border-slate-200 py-2.5">
          <button type="button" class="btn btn-glass btn-sm" data-bs-dismiss="modal" style="min-height: 44px; padding: 0.4rem 1.2rem;">Batal</button>
          <button type="submit" class="btn btn-dark btn-sm px-4" style="min-height: 44px;">Simpan Dokumen</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ================= MODAL: PERPANJANG DOKUMEN ================= -->
<div class="modal fade" id="modalPerpanjangDokumen" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content glass-panel border-0 shadow-lg">
      <form action="" method="POST" id="formPerpanjangDokumen" enctype="multipart/form-data">
        @csrf
        <div class="modal-header border-bottom border-slate-200 py-3">
          <h6 class="modal-title fw-bold text-dark" id="modalTitlePerpanjang">Perpanjang Dokumen</h6>
          <button type="button" class="btn-close small" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body py-3">
          <div class="p-2.5 rounded-3 bg-light border mb-3">
            <small class="text-muted d-block" style="font-size: 0.72rem;">Dokumen yang Diperpanjang:</small>
            <div class="fw-bold text-dark" id="labelPerpanjangTitle">-</div>
            <small class="text-muted" style="font-size: 0.72rem;">Dokumen lama akan otomatis diarsipkan dan riwayat tetap tersimpan.</small>
          </div>

          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label text-muted small mb-1">Tanggal Terbit Baru</label>
              <input type="date" name="issued_at" class="form-control" value="{{ date('Y-m-d') }}" style="min-height: 44px;">
            </div>
            <div class="col-6">
              <label class="form-label text-muted small mb-1">Jatuh Tempo Baru <span class="text-danger">*</span></label>
              <input type="date" name="expires_at" class="form-control" required style="min-height: 44px;">
            </div>
          </div>

          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label text-muted small mb-1">Nomor Referensi Baru</label>
              <input type="text" name="reference_number" id="inputPerpanjangRef" class="form-control" style="min-height: 44px;">
            </div>
            <div class="col-6">
              <label class="form-label text-muted small mb-1">Biaya Perpanjangan (Rp)</label>
              <input type="number" name="cost" id="inputPerpanjangCost" class="form-control" value="0" min="0" step="1000" style="min-height: 44px;">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label text-muted small mb-1">Catatan Tambahan</label>
            <textarea name="notes" class="form-control" rows="2" placeholder="Catatan perpanjangan periode ini..."></textarea>
          </div>

          <div class="mb-2">
            <label class="form-label text-muted small mb-1">Unggah Berkas Baru (Opsional, PDF/Foto)</label>
            <input type="file" name="file" class="form-control" accept="image/*,application/pdf" style="min-height: 44px;">
          </div>
        </div>

        <div class="modal-footer border-top border-slate-200 py-2.5">
          <button type="button" class="btn btn-glass btn-sm" data-bs-dismiss="modal" style="min-height: 44px; padding: 0.4rem 1.2rem;">Batal</button>
          <button type="submit" class="btn btn-dark btn-sm px-4" style="min-height: 44px;">Perpanjang & Arsipkan Lama</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  // 1. Sync Active Tab with URL Hash
  function initTabHashSync() {
    const hash = window.location.hash;
    if (hash) {
      const targetBtn = document.querySelector(`.nav-tabs button[data-bs-target="${hash}"]`);
      if (targetBtn) {
        bootstrap.Tab.getOrCreateInstance(targetBtn).show();
      }
    }

    document.querySelectorAll('.nav-tabs button[data-bs-toggle="tab"]').forEach(btn => {
      btn.addEventListener('shown.bs.tab', e => {
        const target = e.target.getAttribute('data-bs-target');
        if (history.replaceState) {
          history.replaceState(null, null, target);
        } else {
          window.location.hash = target;
        }
      });
    });
  }

  // 2. Dynamic Inspection Checklist Template Renderer
  function initInspectionTemplateRenderer() {
    const templateSelect = document.getElementById('selectAssetInspectionTemplate');
    const titleInput = document.getElementById('inputAssetInspectionTitle');
    const itemsContainer = document.getElementById('containerAssetInspectionItems');

    if (!templateSelect || !itemsContainer) return;

    function renderItems() {
      const selectedOption = templateSelect.options[templateSelect.selectedIndex];
      if (!selectedOption) return;

      const title = selectedOption.getAttribute('data-title') || '';
      titleInput.value = title;

      let items = [];
      try {
        items = JSON.parse(selectedOption.getAttribute('data-items') || '[]');
      } catch (e) {
        items = [];
      }

      itemsContainer.innerHTML = '';
      items.forEach((itemText, idx) => {
        const itemRow = document.createElement('div');
        itemRow.className = 'p-2.5 rounded-2 bg-light border d-flex flex-wrap align-items-center justify-content-between gap-2';
        itemRow.innerHTML = `
          <div class="fw-medium text-dark small" style="flex: 1 1 200px;">
            ${itemText}
            <input type="hidden" name="items[${idx}][label]" value="${itemText}">
          </div>
          <div class="d-flex align-items-center gap-1.5" style="flex: 0 0 auto;">
            <select name="items[${idx}][status]" class="form-select form-select-sm" style="min-height: 38px; width: 140px;">
              <option value="ok" selected>Kondisi OK</option>
              <option value="perhatian">Perhatian</option>
              <option value="bermasalah">Bermasalah</option>
              <option value="na">N/A</option>
            </select>
            <input type="text" name="items[${idx}][notes]" class="form-control form-control-sm" placeholder="Catatan (opsional)" style="min-height: 38px; width: 150px;">
          </div>
        `;
        itemsContainer.appendChild(itemRow);
      });
    }

    templateSelect.addEventListener('change', renderItems);
    renderItems();
  }

  // 3. Edit Damage Report Status Modal Wiring
  function initDamageStatusWiring() {
    document.querySelectorAll('.btn-edit-damage-status').forEach(btn => {
      btn.addEventListener('click', function() {
        const id = this.getAttribute('data-id');
        const title = this.getAttribute('data-title');
        const status = this.getAttribute('data-status');
        const tech = this.getAttribute('data-tech');
        const cost = this.getAttribute('data-cost');
        const notes = this.getAttribute('data-notes');

        const form = document.getElementById('formStatusKerusakan');
        form.action = '/damage-reports/' + id + '/status';

        document.getElementById('modalTitleStatusKerusakan').textContent = 'Perbarui Status: ' + title;
        document.getElementById('inputStatusDamage').value = status;
        document.getElementById('inputStatusTech').value = tech;
        document.getElementById('inputStatusCost').value = cost;
        document.getElementById('inputStatusNotes').value = notes;
      });
    });
  }

  // 4. Renew Document Modal Wiring
  function initRenewDocumentWiring() {
    document.querySelectorAll('.btn-renew-document').forEach(btn => {
      btn.addEventListener('click', function() {
        const id = this.getAttribute('data-id');
        const title = this.getAttribute('data-title');
        const cost = this.getAttribute('data-cost');
        const ref = this.getAttribute('data-ref');

        const form = document.getElementById('formPerpanjangDokumen');
        form.action = '/documents/' + id + '/renew';

        document.getElementById('labelPerpanjangTitle').textContent = title;
        document.getElementById('inputPerpanjangCost').value = cost;
        document.getElementById('inputPerpanjangRef').value = ref;
      });
    });
  }

  document.addEventListener('DOMContentLoaded', function() {
    initTabHashSync();
    initInspectionTemplateRenderer();
    initDamageStatusWiring();
    initRenewDocumentWiring();
  });
</script>
@endpush
