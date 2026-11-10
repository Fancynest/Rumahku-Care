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
            <input type="text" class="form-control" name="name" value="{{ $homeAsset->name }}" required >
          </div>

          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label text-muted small mb-1">Kategori <span class="text-danger">*</span></label>
              <select class="form-select" name="category" required >
                @foreach (['bangunan', 'listrik', 'air', 'pendingin', 'dapur', 'kamar_mandi', 'keamanan', 'eksterior', 'peralatan'] as $cat)
                  <option value="{{ $cat }}" {{ $homeAsset->category === $cat ? 'selected' : '' }}>
                    {{ ucfirst(str_replace('_', ' ', $cat)) }}
                  </option>
                @endforeach
              </select>
            </div>
            <div class="col-6">
              <label class="form-label text-muted small mb-1">Lokasi / Ruangan <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="room" value="{{ $homeAsset->room }}" required >
            </div>
          </div>

          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label text-muted small mb-1">Merek</label>
              <input type="text" class="form-control" name="brand" value="{{ $homeAsset->brand }}" >
            </div>
            <div class="col-6">
              <label class="form-label text-muted small mb-1">Model / Tipe</label>
              <input type="text" class="form-control" name="model" value="{{ $homeAsset->model }}" >
            </div>
          </div>

          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label text-muted small mb-1">Tanggal Pasang / Beli</label>
              <input type="date" class="form-control" name="purchased_at" value="{{ $homeAsset->purchased_at?->format('Y-m-d') }}" >
            </div>
            <div class="col-6">
              <label class="form-label text-muted small mb-1">Kondisi Fisik <span class="text-danger">*</span></label>
              <select class="form-select" name="condition" required >
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
          <button type="button" class="btn btn-glass btn-sm" data-bs-dismiss="modal" >Batal</button>
          <button type="submit" class="btn btn-dark btn-sm px-4" >Simpan Perubahan</button>
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
              <select class="form-select" id="selectAssetInspectionTemplate" name="template_key" >
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
              <input type="date" name="inspected_at" class="form-control" value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}" required >
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label text-muted small mb-1">Judul Sesi Inspeksi <span class="text-danger">*</span></label>
            <input type="text" name="title" id="inputAssetInspectionTitle" class="form-control" value="Inspeksi Keselamatan Rumah" required >
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
          <button type="button" class="btn btn-glass btn-sm" data-bs-dismiss="modal" >Batal</button>
          <button type="submit" class="btn btn-dark btn-sm px-4" >Simpan Hasil Inspeksi</button>
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
            <input type="text" name="title" class="form-control" placeholder="Contoh: AC tidak dingin / pipa bocor" required >
          </div>

          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label text-muted small mb-1">Tingkat Keparahan <span class="text-danger">*</span></label>
              <select name="severity" class="form-select" required >
                <option value="ringan">Ringan (Masih berfungsi)</option>
                <option value="sedang" selected>Sedang (Terganggu)</option>
                <option value="berat">Berat (Mati total / Bahaya)</option>
              </select>
            </div>
            <div class="col-6">
              <label class="form-label text-muted small mb-1">Tanggal Diketahui <span class="text-danger">*</span></label>
              <input type="date" name="reported_at" class="form-control" value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}" required >
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label text-muted small mb-1">Deskripsi Kerusakan</label>
            <textarea name="description" class="form-control" rows="2" placeholder="Jelaskan detail bunyi aneh, letak kebocoran, atau pemicunya..."></textarea>
          </div>

          <div class="mb-2">
            <label class="form-label text-muted small mb-1">Foto Kerusakan (Opsional, Maks 5 MB)</label>
            <input type="file" name="photo" class="form-control" accept="image/*,application/pdf" >
          </div>
        </div>

        <div class="modal-footer border-top border-slate-200 py-2.5">
          <button type="button" class="btn btn-glass btn-sm" data-bs-dismiss="modal" >Batal</button>
          <button type="submit" class="btn btn-dark btn-sm px-4" >Catat Kerusakan</button>
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
              <select name="status" id="inputStatusDamage" class="form-select" required >
                <option value="dilaporkan">Dilaporkan</option>
                <option value="dalam_perbaikan">Dalam Perbaikan</option>
                <option value="selesai">Selesai Diperbaiki</option>
              </select>
            </div>
            <div class="col-6">
              <label class="form-label text-muted small mb-1">Tanggal Selesai</label>
              <input type="date" name="resolved_at" id="inputStatusResolvedAt" class="form-control" value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}" >
            </div>
          </div>

          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label text-muted small mb-1">Teknisi / Vendor</label>
              <input type="text" name="technician_name" id="inputStatusTech" class="form-control" placeholder="Nama teknisi" >
            </div>
            <div class="col-6">
              <label class="form-label text-muted small mb-1">Biaya Perbaikan (Rp)</label>
              <input type="number" name="repair_cost" id="inputStatusCost" class="form-control" value="0" min="0" step="5000" >
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label text-muted small mb-1">Catatan Hasil Pengerjaan</label>
            <textarea name="notes" id="inputStatusNotes" class="form-control" rows="2" placeholder="Komponen yang diganti, garansi teknisi, dll..."></textarea>
          </div>

          <div class="mb-2">
            <label class="form-label text-muted small mb-1">Upload Nota / Bukti Perbaikan (Opsional)</label>
            <input type="file" name="receipt" class="form-control" accept="image/*,application/pdf" >
          </div>
        </div>

        <div class="modal-footer border-top border-slate-200 py-2.5">
          <button type="button" class="btn btn-glass btn-sm" data-bs-dismiss="modal" >Batal</button>
          <button type="submit" class="btn btn-dark btn-sm px-4" >Perbarui Status</button>
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
              <select name="type" class="form-select" required >
                @foreach ($documentTypes as $typeKey => $typeLabel)
                  <option value="{{ $typeKey }}">{{ $typeLabel }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-6">
              <label class="form-label text-muted small mb-1">Nomor Referensi / Seri</label>
              <input type="text" name="reference_number" class="form-control" placeholder="Contoh: GAR-123456" >
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label text-muted small mb-1">Judul Dokumen <span class="text-danger">*</span></label>
            <input type="text" name="title" class="form-control" placeholder="Contoh: Garansi Resmi Kompresor 3 Tahun" required >
          </div>

          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label text-muted small mb-1">Tanggal Terbit</label>
              <input type="date" name="issued_at" class="form-control" value="{{ date('Y-m-d') }}" >
            </div>
            <div class="col-6">
              <label class="form-label text-muted small mb-1">Tanggal Jatuh Tempo / Habis</label>
              <input type="date" name="expires_at" class="form-control" >
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label text-muted small mb-1">Biaya / Nilai Pertanggungan (Rp)</label>
            <input type="number" name="cost" class="form-control" value="0" min="0" step="1000" >
          </div>

          <div class="mb-3">
            <label class="form-label text-muted small mb-1">Catatan Tambahan</label>
            <textarea name="notes" class="form-control" rows="2" placeholder="Ketentuan klaim, kontak vendor..."></textarea>
          </div>

          <div class="mb-2">
            <label class="form-label text-muted small mb-1">Berkas Dokumen (Foto / PDF, Maks 5 MB)</label>
            <input type="file" name="file" class="form-control" accept="image/*,application/pdf" >
          </div>
        </div>

        <div class="modal-footer border-top border-slate-200 py-2.5">
          <button type="button" class="btn btn-glass btn-sm" data-bs-dismiss="modal" >Batal</button>
          <button type="submit" class="btn btn-dark btn-sm px-4" >Simpan Dokumen</button>
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
            <small class="text-muted d-block" >Dokumen yang Diperpanjang:</small>
            <div class="fw-bold text-dark" id="labelPerpanjangTitle">-</div>
            <small class="text-muted" >Dokumen lama akan otomatis diarsipkan dan riwayat tetap tersimpan.</small>
          </div>

          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label text-muted small mb-1">Tanggal Terbit Baru</label>
              <input type="date" name="issued_at" class="form-control" value="{{ date('Y-m-d') }}" >
            </div>
            <div class="col-6">
              <label class="form-label text-muted small mb-1">Jatuh Tempo Baru <span class="text-danger">*</span></label>
              <input type="date" name="expires_at" class="form-control" required >
            </div>
          </div>

          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label text-muted small mb-1">Nomor Referensi Baru</label>
              <input type="text" name="reference_number" id="inputPerpanjangRef" class="form-control" >
            </div>
            <div class="col-6">
              <label class="form-label text-muted small mb-1">Biaya Perpanjangan (Rp)</label>
              <input type="number" name="cost" id="inputPerpanjangCost" class="form-control" value="0" min="0" step="1000" >
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label text-muted small mb-1">Catatan Tambahan</label>
            <textarea name="notes" class="form-control" rows="2" placeholder="Catatan perpanjangan periode ini..."></textarea>
          </div>

          <div class="mb-2">
            <label class="form-label text-muted small mb-1">Unggah Berkas Baru (Opsional, PDF/Foto)</label>
            <input type="file" name="file" class="form-control" accept="image/*,application/pdf" >
          </div>
        </div>

        <div class="modal-footer border-top border-slate-200 py-2.5">
          <button type="button" class="btn btn-glass btn-sm" data-bs-dismiss="modal" >Batal</button>
          <button type="submit" class="btn btn-dark btn-sm px-4" >Perpanjang & Arsipkan Lama</button>
        </div>
      </form>
    </div>
  </div>
</div>
