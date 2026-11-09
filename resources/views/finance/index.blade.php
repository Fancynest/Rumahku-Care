@extends('layouts.app')

@section('title', 'Keuangan & Laporan Pengeluaran - Rumahku Care')

@section('header-action')
<div class="d-flex align-items-center gap-2">
  <a href="{{ route('finance.export', request()->query()) }}" class="btn btn-glass btn-sm d-flex align-items-center gap-1.5" style="min-height: 40px; padding: 0.4rem 1rem;">
    <i class="bi bi-file-earmark-arrow-down"></i>
    <span>Ekspor CSV</span>
  </a>
</div>
@endsection

@section('content')
<!-- Header & Navigasi Bulan -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
  <div>
    <h4 class="fw-bold text-dark mb-1" style="letter-spacing: -0.02em;">Laporan Pengeluaran & Keuangan</h4>
    <span class="text-muted small">Rekapitulasi biaya servis, bensin, perbaikan, dan pajak kendaraan serta rumah.</span>
  </div>

  <!-- Pemilih Bulan (Prev / Next) -->
  <div class="d-flex align-items-center gap-2 bg-white p-1.5 rounded-3 border shadow-2xs">
    <a href="{{ route('finance.index', array_merge($filters, ['month' => $prev_month])) }}" 
       class="btn btn-glass btn-sm p-1.5 px-2 text-dark" 
       title="Bulan Sebelumnya" 
       style="min-height: 36px; min-width: 36px;">
      <i class="bi bi-chevron-left"></i>
    </a>
    <span class="fw-bold text-dark px-2 text-capitalize text-nowrap" style="font-size: 0.88rem; min-width: 130px; text-align: center;">
      {{ $month_label }}
    </span>
    <a href="{{ route('finance.index', array_merge($filters, ['month' => $next_month])) }}" 
       class="btn btn-glass btn-sm p-1.5 px-2 text-dark" 
       title="Bulan Berikutnya" 
       style="min-height: 36px; min-width: 36px;">
      <i class="bi bi-chevron-right"></i>
    </a>
    @if ($month !== date('Y-m'))
      <a href="{{ route('finance.index', array_merge($filters, ['month' => date('Y-m')])) }}" 
         class="btn btn-dark btn-sm px-2.5 ms-1" 
         style="min-height: 36px; font-size: 0.78rem;">
        Bulan Ini
      </a>
    @endif
  </div>
</div>

<!-- 2 Kartu Ringkasan Atas -->
<div class="row g-3 mb-4">
  <div class="col-md-6">
    <div class="glass-panel p-4 h-100">
      <small class="text-muted fw-medium d-block mb-1" style="font-size: 0.75rem;">Total Pengeluaran Bulan Ini</small>
      <div class="d-flex align-items-baseline gap-2 mb-1">
        <h2 class="fw-bold text-dark mb-0">{{ $formatted_total_this_month }}</h2>
      </div>
      <small class="text-muted" style="font-size: 0.75rem;">
        Mencakup servis, BBM, perbaikan, dan dokumen pada periode {{ $month_label }}.
      </small>
    </div>
  </div>

  <div class="col-md-6">
    <div class="glass-panel p-4 h-100">
      <small class="text-muted fw-medium d-block mb-1" style="font-size: 0.75rem;">Perbandingan vs Bulan Lalu</small>
      <div class="d-flex align-items-baseline gap-2 mb-1">
        @if ($diff_amount > 0)
          <h2 class="fw-bold text-danger mb-0">+Rp {{ number_format($diff_amount, 0, ',', '.') }}</h2>
          <span class="badge bg-danger-subtle text-danger border border-danger">
            <i class="bi bi-arrow-up-right me-0.5"></i> Naik {{ $diff_percentage !== null ? $diff_percentage . '%' : '' }}
          </span>
        @elseif ($diff_amount < 0)
          <h2 class="fw-bold text-success mb-0">-Rp {{ number_format(abs($diff_amount), 0, ',', '.') }}</h2>
          <span class="badge bg-success-subtle text-success border border-success">
            <i class="bi bi-arrow-down-right me-0.5"></i> Lebih Hemat {{ $diff_percentage !== null ? abs($diff_percentage) . '%' : '' }}
          </span>
        @else
          <h2 class="fw-bold text-secondary mb-0">Rp 0</h2>
          <span class="badge bg-secondary-subtle text-secondary border">Sama</span>
        @endif
      </div>
      <small class="text-muted" style="font-size: 0.75rem;">
        Total bulan lalu: Rp {{ number_format($total_prev_month, 0, ',', '.') }}
      </small>
    </div>
  </div>
</div>

<!-- Section: Anggaran Bulan Ini (Budgets) -->
<div class="glass-panel p-4 mb-4">
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
      <h6 class="fw-bold text-dark mb-0">Anggaran Pengeluaran Periode {{ $month_label }}</h6>
      <span class="text-muted small">Target batas pengeluaran bulanan dan pemantauan realisasi biaya aset Anda.</span>
    </div>
    <button type="button" class="btn btn-dark btn-sm px-3 d-inline-flex align-items-center gap-1.5" data-bs-toggle="modal" data-bs-target="#modalAturAnggaran" style="min-height: 38px;">
      <i class="bi bi-sliders"></i>
      <span>Atur Anggaran</span>
    </button>
  </div>

  @if (! $budgetSummary['has_any_budget'])
    <div class="p-3.5 text-center text-muted bg-white-50 rounded-3 border border-dashed border-slate-200">
      <i class="bi bi-piggy-bank d-block fs-4 mb-1 text-secondary"></i>
      <div class="fw-medium text-dark small">Belum ada anggaran yang diatur</div>
      <p class="text-muted mb-2.5" style="font-size: 0.78rem;">Atur batas anggaran bulanan untuk mengontrol pengeluaran servis, BBM, dan operasional aset.</p>
      <button type="button" class="btn btn-glass btn-sm px-3" data-bs-toggle="modal" data-bs-target="#modalAturAnggaran" style="min-height: 38px;">
        <i class="bi bi-plus-lg me-1"></i> Mulai Pasang Target Anggaran
      </button>
    </div>
  @else
    <div class="row g-3">
      @if ($budgetSummary['total'])
        @php $b = $budgetSummary['total']; @endphp
        <div class="col-12">
          <div class="p-3 rounded-3 bg-white border border-slate-200 shadow-2xs">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
              <div>
                <span class="fw-bold text-dark fs-6">Anggaran Total (Semua Kategori)</span>
                <span class="chip {{ $b['chip_class'] }} ms-2 py-0 px-2">
                  <span class="chip-dot"></span> {{ $b['label'] }} ({{ $b['percentage'] }}%)
                </span>
              </div>
              <div class="text-end">
                <span class="fw-bold text-dark">Rp {{ number_format($b['spent'], 0, ',', '.') }}</span>
                <span class="text-muted small">/ Rp {{ number_format($b['budget'], 0, ',', '.') }}</span>
              </div>
            </div>
            <div class="progress" style="height: 10px; background-color: rgba(226, 232, 240, 0.6); border-radius: 9999px;">
              <div class="progress-bar {{ $b['percentage'] >= 100 ? 'bg-danger' : ($b['percentage'] >= 80 ? 'bg-warning' : 'bg-success') }}" 
                   role="progressbar" 
                   style="width: {{ min(100, $b['percentage']) }}%; border-radius: 9999px;" 
                   aria-valuenow="{{ $b['percentage'] }}" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
          </div>
        </div>
      @endif

      @foreach ($budgetSummary['categories'] as $catKey => $b)
        @if ($b)
          <div class="col-md-6 col-lg-3">
            <div class="p-3 rounded-3 bg-white border border-slate-200 h-100 d-flex flex-column justify-content-between shadow-2xs">
              <div>
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <small class="fw-bold text-dark text-capitalize">{{ $categories[$catKey]['label'] ?? ucfirst($catKey) }}</small>
                  <span class="chip {{ $b['chip_class'] }} py-0 px-1.5" style="font-size: 0.65rem;">
                    {{ $b['label'] }}
                  </span>
                </div>
                <div class="d-flex justify-content-between align-items-baseline mb-2">
                  <span class="fw-bold text-dark small">Rp {{ number_format($b['spent'], 0, ',', '.') }}</span>
                  <span class="text-muted" style="font-size: 0.72rem;">/ {{ number_format($b['budget'], 0, ',', '.') }}</span>
                </div>
              </div>
              <div>
                <div class="progress" style="height: 6px; background-color: rgba(226, 232, 240, 0.6); border-radius: 9999px;">
                  <div class="progress-bar {{ $b['percentage'] >= 100 ? 'bg-danger' : ($b['percentage'] >= 80 ? 'bg-warning' : 'bg-success') }}" 
                       role="progressbar" 
                       style="width: {{ min(100, $b['percentage']) }}%; border-radius: 9999px;"></div>
                </div>
                <div class="text-end mt-1">
                  <small class="text-muted" style="font-size: 0.7rem;">{{ $b['percentage'] }}% terpakai</small>
                </div>
              </div>
            </div>
          </div>
        @endif
      @endforeach
    </div>
  @endif
</div>

<!-- Section: Rincian Kategori & Tren 6 Bulan -->
<div class="row g-3 mb-4">
  <!-- Kolom Kiri: Breakdown 4 Kategori (Bar CSS) -->
  <div class="col-lg-6">
    <div class="glass-panel p-4 h-100">
      <h6 class="fw-bold text-dark mb-3">Pecahan Biaya Berdasarkan Kategori</h6>

      <div class="d-flex flex-column gap-3">
        @foreach ($breakdown as $cat)
          <div>
            <div class="d-flex justify-content-between align-items-center mb-1 small">
              <span class="fw-medium text-dark d-flex align-items-center gap-1.5">
                <span style="width: 10px; height: 10px; border-radius: 50%; background-color: {{ $cat['color'] }}; display: inline-block;"></span>
                {{ $cat['label'] }}
              </span>
              <span class="text-muted">
                <strong class="text-dark">{{ $cat['formatted_amount'] }}</strong> ({{ $cat['percentage'] }}%)
              </span>
            </div>
            <div class="progress" style="height: 8px; background-color: rgba(226, 232, 240, 0.6); border-radius: 9999px;">
              <div class="progress-bar" role="progressbar" 
                   style="width: {{ $cat['percentage'] }}%; background-color: {{ $cat['color'] }}; border-radius: 9999px;" 
                   aria-valuenow="{{ $cat['percentage'] }}" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  </div>

  <!-- Kolom Kanan: Tren 6 Bulan (CSS Bar Murni) -->
  <div class="col-lg-6">
    <div class="glass-panel p-4 h-100 d-flex flex-column justify-content-between">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold text-dark mb-0">Tren Pengeluaran 6 Bulan</h6>
        <small class="text-muted" style="font-size: 0.72rem;">Klik bar untuk melihat bulan</small>
      </div>

      <!-- Chart Bar CSS Murni -->
      <div class="d-flex align-items-end justify-content-between gap-2 pt-3 pb-1" style="min-height: 140px;">
        @foreach ($six_month_trend as $bar)
          <a href="{{ route('finance.index', array_merge($filters, ['month' => $bar['month']])) }}" 
             class="flex-fill text-decoration-none d-flex flex-column align-items-center justify-content-end text-center group" 
             style="height: 130px;" 
             title="{{ $bar['label'] }}: {{ $bar['formatted_total'] }}">
            <div class="w-100 rounded-3 transition-all mb-2 position-relative" 
                 style="height: {{ $bar['height_percent'] }}%; max-height: 100px; min-height: 6px; background-color: {{ $bar['is_selected'] ? '#0f172a' : '#cbd5e1' }};">
            </div>
            <span class="small fw-semibold {{ $bar['is_selected'] ? 'text-dark' : 'text-muted' }}" style="font-size: 0.72rem;">
              {{ $bar['short_label'] }}
            </span>
          </a>
        @endforeach
      </div>

      <div class="pt-2 border-top border-slate-100 text-end">
        <small class="text-muted" style="font-size: 0.72rem;">Bar gelap menandakan bulan yang sedang aktif ditampilkan.</small>
      </div>
    </div>
  </div>
</div>

<!-- Daftar Pengeluaran & Filter -->
<div class="glass-panel p-4">
  <!-- Baris Filter -->
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
      <h6 class="fw-bold text-dark mb-0">Daftar Transaksi Pengeluaran</h6>
      <span class="text-muted small">Total {{ $raw_filtered_count }} pengeluaran tercatat pada periode ini.</span>
    </div>

    <!-- Filter Subjek & Kategori -->
    <div class="d-flex flex-wrap align-items-center gap-2">
      <!-- Filter Subjek -->
      <form action="{{ route('finance.index') }}" method="GET" class="m-0 d-flex align-items-center gap-2">
        <input type="hidden" name="month" value="{{ $month }}">
        <input type="hidden" name="category" value="{{ $filters['category'] }}">

        <select name="subject" class="form-select form-select-sm" onchange="this.form.submit()" style="min-height: 38px; min-width: 170px;">
          <option value="all" {{ $filters['subject'] === 'all' ? 'selected' : '' }}>Semua Aset & Garasi</option>
          <optgroup label="Kendaraan">
            @foreach ($vehicles as $v)
              <option value="vehicle:{{ $v->id }}" {{ $filters['subject'] === "vehicle:{$v->id}" ? 'selected' : '' }}>
                {{ $v->name }} ({{ $v->plate }})
              </option>
            @endforeach
          </optgroup>
          <optgroup label="Aset Rumah">
            @foreach ($homeAssets as $ha)
              <option value="home_asset:{{ $ha->id }}" {{ $filters['subject'] === "home_asset:{$ha->id}" ? 'selected' : '' }}>
                {{ $ha->name }} ({{ $ha->room }})
              </option>
            @endforeach
          </optgroup>
        </select>
      </form>

      <!-- Filter Kategori Pills -->
      <div class="d-flex align-items-center gap-1.5 bg-white p-1 rounded-pill border shadow-2xs">
        <a href="{{ route('finance.index', array_merge($filters, ['category' => 'all'])) }}" 
           class="btn btn-sm rounded-pill px-2.5 py-1 text-decoration-none {{ $filters['category'] === 'all' ? 'btn-dark' : 'text-muted' }}" 
           style="font-size: 0.78rem;">
          Semua
        </a>
        <a href="{{ route('finance.index', array_merge($filters, ['category' => 'servis'])) }}" 
           class="btn btn-sm rounded-pill px-2.5 py-1 text-decoration-none {{ $filters['category'] === 'servis' ? 'btn-dark' : 'text-muted' }}" 
           style="font-size: 0.78rem;">
          Servis
        </a>
        <a href="{{ route('finance.index', array_merge($filters, ['category' => 'bbm'])) }}" 
           class="btn btn-sm rounded-pill px-2.5 py-1 text-decoration-none {{ $filters['category'] === 'bbm' ? 'btn-dark' : 'text-muted' }}" 
           style="font-size: 0.78rem;">
          BBM
        </a>
        <a href="{{ route('finance.index', array_merge($filters, ['category' => 'perbaikan'])) }}" 
           class="btn btn-sm rounded-pill px-2.5 py-1 text-decoration-none {{ $filters['category'] === 'perbaikan' ? 'btn-dark' : 'text-muted' }}" 
           style="font-size: 0.78rem;">
          Perbaikan
        </a>
        <a href="{{ route('finance.index', array_merge($filters, ['category' => 'dokumen'])) }}" 
           class="btn btn-sm rounded-pill px-2.5 py-1 text-decoration-none {{ $filters['category'] === 'dokumen' ? 'btn-dark' : 'text-muted' }}" 
           style="font-size: 0.78rem;">
          Dokumen
        </a>
      </div>
    </div>
  </div>

  <!-- Tabel Pengeluaran -->
  @if ($items->isEmpty())
    <div class="p-5 text-center text-muted bg-white-50 rounded-3 border border-dashed border-slate-200 my-2">
      <i class="bi bi-wallet2 d-block fs-3 mb-1 text-secondary"></i>
      <div class="fw-medium text-dark small">Tidak ada transaksi pengeluaran</div>
      <p class="text-muted mb-0" style="font-size: 0.78rem;">
        Tidak ada data pengeluaran yang sesuai dengan filter pada periode {{ $month_label }}.
      </p>
    </div>
  @else
    <div class="table-responsive rounded-3 border border-slate-200 bg-white mb-3">
      <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
        <thead class="table-light text-muted" style="font-size: 0.75rem; letter-spacing: 0.04em;">
          <tr>
            <th class="ps-3 py-2.5">TANGGAL</th>
            <th class="py-2.5">KATEGORI</th>
            <th class="py-2.5">KETERANGAN</th>
            <th class="py-2.5">SUBJEK ASET</th>
            <th class="py-2.5">JUMLAH</th>
            <th class="text-end pe-3 py-2.5">TAUTAN</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($items as $item)
            <tr>
              <td class="ps-3 fw-medium text-dark">
                {{ $item->date->format('d M Y') }}
              </td>
              <td>
                <span class="badge {{ $item->category_badge }} px-2 py-0.5" style="font-size: 0.72rem;">
                  {{ $item->category_label }}
                </span>
              </td>
              <td class="fw-semibold text-dark">
                {{ $item->title }}
              </td>
              <td class="text-secondary small">
                {{ $item->subject_label }}
              </td>
              <td class="fw-bold text-dark">
                {{ $item->formatted_amount }}
              </td>
              <td class="text-end pe-3">
                <a href="{{ $item->url }}" class="btn btn-glass btn-sm p-1 px-2.5 text-decoration-none" style="font-size: 0.75rem;" title="Lihat Sumber Catatan">
                  <i class="bi bi-box-arrow-up-right me-1"></i> Buka
                </a>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    <!-- Paginasi 20 Item -->
    <div class="d-flex justify-content-between align-items-center mt-3">
      <span class="text-muted small">
        Menampilkan {{ $items->firstItem() ?? 0 }} - {{ $items->lastItem() ?? 0 }} dari {{ $items->total() }} catatan
      </span>
      <div>
        {{ $items->links() }}
      </div>
    </div>
  @endif
</div>

<!-- Modal Atur Anggaran -->
<div class="modal fade" id="modalAturAnggaran" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content glass-panel border-0 shadow-lg">
      <form action="{{ route('finance.budgets.update') }}" method="POST">
        @csrf
        <input type="hidden" name="month" value="{{ $month }}">

        <div class="modal-header border-bottom border-slate-200 py-3">
          <h6 class="modal-title fw-bold text-dark"><i class="bi bi-sliders me-1.5"></i> Atur Anggaran Bulanan</h6>
          <button type="button" class="btn-close small" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body py-3">
          <p class="text-muted small mb-3">
            Tentukan batas pengeluaran maksimal per bulan. Kosongkan nilai jika tidak ingin membatasi kategori tertentu.
          </p>

          <div class="mb-3">
            <label class="form-label text-dark fw-semibold small mb-1">Anggaran Total (Semua Kategori)</label>
            <div class="input-group">
              <span class="input-group-text bg-transparent text-muted small">Rp</span>
              <input type="text" name="budgets[total]" class="form-control" 
                     placeholder="mis. 5.000.000" 
                     value="{{ $budgetSummary['raw_budgets']->firstWhere('category', null)?->monthly_amount }}" 
                     style="min-height: 44px;">
            </div>
            <small class="text-muted" style="font-size: 0.72rem;">Batas gabungan servis, BBM, perbaikan, dan dokumen.</small>
          </div>

          <hr class="my-3 border-slate-200">

          <div class="mb-3">
            <label class="form-label text-muted small mb-1">Servis & Perawatan</label>
            <div class="input-group">
              <span class="input-group-text bg-transparent text-muted small">Rp</span>
              <input type="text" name="budgets[servis]" class="form-control" 
                     placeholder="mis. 1.000.000" 
                     value="{{ $budgetSummary['raw_budgets']->firstWhere('category', 'servis')?->monthly_amount }}" 
                     style="min-height: 44px;">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label text-muted small mb-1">Bahan Bakar (BBM)</label>
            <div class="input-group">
              <span class="input-group-text bg-transparent text-muted small">Rp</span>
              <input type="text" name="budgets[bbm]" class="form-control" 
                     placeholder="mis. 1.500.000" 
                     value="{{ $budgetSummary['raw_budgets']->firstWhere('category', 'bbm')?->monthly_amount }}" 
                     style="min-height: 44px;">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label text-muted small mb-1">Perbaikan Kerusakan</label>
            <div class="input-group">
              <span class="input-group-text bg-transparent text-muted small">Rp</span>
              <input type="text" name="budgets[perbaikan]" class="form-control" 
                     placeholder="mis. 1.000.000" 
                     value="{{ $budgetSummary['raw_budgets']->firstWhere('category', 'perbaikan')?->monthly_amount }}" 
                     style="min-height: 44px;">
            </div>
          </div>

          <div class="mb-2">
            <label class="form-label text-muted small mb-1">Dokumen & Pajak</label>
            <div class="input-group">
              <span class="input-group-text bg-transparent text-muted small">Rp</span>
              <input type="text" name="budgets[dokumen]" class="form-control" 
                     placeholder="mis. 500.000" 
                     value="{{ $budgetSummary['raw_budgets']->firstWhere('category', 'dokumen')?->monthly_amount }}" 
                     style="min-height: 44px;">
            </div>
          </div>
        </div>

        <div class="modal-footer border-top border-slate-200 py-2.5">
          <button type="button" class="btn btn-glass btn-sm" data-bs-dismiss="modal" style="min-height: 44px; padding: 0.4rem 1.2rem;">Batal</button>
          <button type="submit" class="btn btn-dark btn-sm px-4" style="min-height: 44px;">Simpan Anggaran</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
