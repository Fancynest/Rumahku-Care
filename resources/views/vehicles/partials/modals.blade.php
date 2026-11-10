@if ($selectedVehicle)
  <!-- ================= MODAL: UPDATE ODOMETER ================= -->
  <div class="modal fade" id="modalUpdateKM" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
      <div class="modal-content glass-panel border-0 shadow-lg">
        <form action="{{ route('vehicles.update-odometer', $selectedVehicle->id) }}" method="POST">
          @csrf
          @method('PATCH')
          <div class="modal-header border-bottom border-slate-200 py-3">
            <h6 class="modal-title fw-bold text-dark">Update Odometer</h6>
            <button type="button" class="btn-close small" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body py-3">
            <div class="mb-2">
              <label class="form-label text-muted small mb-1">{{ $selectedVehicle->name }} ({{ $selectedVehicle->plate }})</label>
              <div class="input-group">
                <input type="number" 
                       name="odometer" 
                       class="form-control fw-bold fs-5" 
                       value="{{ $selectedVehicle->odometer }}" 
                       min="{{ $selectedVehicle->odometer }}" 
                       required>
                <span class="input-group-text bg-transparent text-muted">KM</span>
              </div>
              <small class="text-muted" style="font-size: 0.72rem;">Masukkan angka total KM terbaru di dashboard fisik.</small>
            </div>
          </div>
          <div class="modal-footer border-top border-slate-200 py-2.5">
            <button type="button" class="btn btn-glass btn-sm" data-bs-dismiss="modal" style="min-height: 40px;">Batal</button>
            <button type="submit" class="btn btn-dark btn-sm px-3" style="min-height: 40px;">Simpan Perubahan</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- ================= MODAL: CATAT SERVIS ================= -->
  <div class="modal fade" id="modalCatatServis" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content glass-panel border-0 shadow-lg">
        <form action="{{ route('vehicles.service-logs.store', $selectedVehicle->id) }}" method="POST">
          @csrf
          <div class="modal-header border-bottom border-slate-200 py-3">
            <h6 class="modal-title fw-bold text-dark">Catat Servis — {{ $selectedVehicle->name }}</h6>
            <button type="button" class="btn-close small" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body py-3">
            <!-- Baris 1: Tanggal & Odometer -->
            <div class="row g-3 mb-3">
              <div class="col-md-6">
                <label class="form-label text-muted small mb-1">Tanggal Servis <span class="text-danger">*</span></label>
                <input type="date" name="done_at" class="form-control" value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}" required>
              </div>
              <div class="col-md-6">
                <label class="form-label text-muted small mb-1">Odometer Saat Servis (KM) <span class="text-danger">*</span></label>
                <input type="number" 
                       name="odometer" 
                       class="form-control font-monospace fw-semibold" 
                       value="{{ $selectedVehicle->odometer }}" 
                       min="0" 
                       required>
                <small class="text-muted" style="font-size: 0.72rem;">Sesuai angka odometer pada tanggal pelaksanaan servis.</small>
              </div>
            </div>

            <!-- Baris 2: Jenis Servis & Nama Bengkel -->
            <div class="row g-3 mb-3">
              <div class="col-md-6">
                <label class="form-label text-muted small mb-1">Jenis Servis <span class="text-danger">*</span></label>
                <select name="service_type" class="form-select" required>
                  <option value="Ganti Oli">Ganti Oli</option>
                  <option value="Servis Berkala" selected>Servis Berkala</option>
                  <option value="Perbaikan">Perbaikan</option>
                  <option value="Ganti Ban">Ganti Ban</option>
                  <option value="Lainnya">Lainnya</option>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label text-muted small mb-1">Nama Bengkel Pelaksana</label>
                <input type="text" name="workshop_name" class="form-control" value="{{ $selectedVehicle->workshop }}" placeholder="Contoh: Auto2000, AHASS">
              </div>
            </div>

            <!-- Baris 3: Tandai Jadwal Selesai -->
            <div class="mb-3">
              <label class="form-label text-muted small mb-1">Tandai Jadwal Selesai (Opsional)</label>
              <select name="schedule_id" class="form-select">
                <option value="">-- Tidak Ada / Servis Mandiri Tanpa Jadwal --</option>
                @foreach ($activeSchedules as $sch)
                  <option value="{{ $sch->id }}">
                    {{ $sch->title }} ({{ $sch->trigger_label }}) — Status: {{ ucfirst($sch->status) }}
                  </option>
                @endforeach
              </select>
              <small class="text-muted" style="font-size: 0.72rem;">Jika dipilih, siklus jadwal terkait akan otomatis diperbarui.</small>
            </div>

            <!-- Baris 4: Biaya Jasa & Suku Cadang + Total -->
            <div class="p-3 rounded-3 bg-light border mb-3">
              <div class="row g-2 align-items-center">
                <div class="col-md-4">
                  <label class="form-label text-muted small mb-1">Biaya Jasa (Rp)</label>
                  <input type="number" id="servisLaborCost" name="labor_cost" class="form-control" value="0" min="0" step="1000">
                </div>
                <div class="col-md-4">
                  <label class="form-label text-muted small mb-1">Biaya Suku Cadang (Rp)</label>
                  <input type="number" id="servisPartsCost" name="parts_cost" class="form-control" value="0" min="0" step="1000">
                </div>
                <div class="col-md-4 text-md-end pt-2 pt-md-0">
                  <small class="text-muted d-block" style="font-size: 0.72rem;">Total Biaya Perawatan:</small>
                  <div class="fw-bold fs-5 text-dark" id="servisTotalCost">Rp 0</div>
                </div>
              </div>
            </div>

            <!-- Baris 5: Suku Cadang Diganti & Catatan -->
            <div class="mb-3">
              <label class="form-label text-muted small mb-1">Suku Cadang yang Diganti</label>
              <textarea name="parts" class="form-control" rows="2" placeholder="Contoh: Oli Mesin SPX2 0.8L, Busi CPR9EA, Filter Oli"></textarea>
            </div>
            <div class="mb-2">
              <label class="form-label text-muted small mb-1">Catatan Tambahan</label>
              <textarea name="notes" class="form-control" rows="2" placeholder="Catatan kondisi kendaraan, rekomendasi servis berikutnya, atau garansi bengkel..."></textarea>
            </div>
          </div>
          <div class="modal-footer border-top border-slate-200 py-2.5">
            <button type="button" class="btn btn-glass btn-sm" data-bs-dismiss="modal" style="min-height: 44px; padding: 0.4rem 1.2rem;">Batal</button>
            <button type="submit" class="btn btn-dark btn-sm px-4" style="min-height: 44px;">Simpan Catatan Servis</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- ================= MODAL: DETAIL SPESIFIKASI ================= -->
  <div class="modal fade" id="modalDetailSpesifikasi" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content glass-panel border-0 shadow-lg">
        <div class="modal-header border-bottom border-slate-200 py-3">
          <h6 class="modal-title fw-bold text-dark">Spesifikasi Kendaraan</h6>
          <button type="button" class="btn-close small" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body py-3">
          <table class="table table-sm table-borderless mb-0" style="font-size: 0.85rem;">
            <tbody>
              <tr>
                <td class="text-muted" style="width: 40%;">Merek & Model</td>
                <td class="fw-semibold text-dark">{{ $selectedVehicle->name }}</td>
              </tr>
              <tr>
                <td class="text-muted">Nomor Polisi</td>
                <td class="fw-bold font-monospace text-dark">{{ $selectedVehicle->plate }}</td>
              </tr>
              <tr>
                <td class="text-muted">Tipe Bodi</td>
                <td class="text-uppercase text-dark">{{ str_replace('_', ' ', $selectedVehicle->body_type ?? '-') }}</td>
              </tr>
              <tr>
                <td class="text-muted">Tahun Pembuatan</td>
                <td class="text-dark">{{ $selectedVehicle->year ?? '-' }}</td>
              </tr>
              <tr>
                <td class="text-muted">Bahan Bakar</td>
                <td class="text-dark">{{ $selectedVehicle->fuel_type }}</td>
              </tr>
              <tr>
                <td class="text-muted">Warna Terdaftar</td>
                <td class="text-capitalize text-dark">{{ $selectedVehicle->color_key }}</td>
              </tr>
              <tr>
                <td class="text-muted">Bengkel Rujukan</td>
                <td class="text-dark">{{ $selectedVehicle->workshop ?? 'Belum ditentukan' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <div class="modal-footer border-top border-slate-200 py-2.5">
          <button type="button" class="btn btn-dark btn-sm px-3" data-bs-dismiss="modal" style="min-height: 40px;">Tutup</button>
        </div>
      </div>
    </div>
  </div>

  <!-- ================= MODAL: UPLOAD NOTA SERVIS ================= -->
  <div class="modal fade" id="modalUploadNotaServis" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content glass-panel border-0 shadow-lg">
        <form action="{{ route('attachments.store') }}" method="POST" enctype="multipart/form-data">
          @csrf
          <input type="hidden" name="attachable_type" value="App\Models\MaintenanceRecord">
          <input type="hidden" name="attachable_id" id="inputNotaRecordId" value="">

          <div class="modal-header border-bottom border-slate-200 py-3">
            <h6 class="modal-title fw-bold text-dark" id="modalTitleUploadNota">Lampirkan Nota Servis</h6>
            <button type="button" class="btn-close small" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>

          <div class="modal-body py-3">
            <div class="p-2.5 rounded-3 bg-light border mb-3">
              <small class="text-muted d-block" style="font-size: 0.72rem;">Catatan Servis Terkait:</small>
              <div class="fw-bold text-dark" id="labelNotaRecordTitle">-</div>
            </div>

            <div class="mb-2">
              <label class="form-label text-muted small mb-1">Pilih Berkas Nota (Foto atau PDF, Maks 5 MB) <span class="text-danger">*</span></label>
              <input type="file" name="file" class="form-control" accept="image/*,application/pdf" required style="min-height: 44px;">
              <small class="text-muted" style="font-size: 0.72rem;">Berkas disimpan secara privat dan hanya dapat diakses oleh Anda.</small>
            </div>
          </div>

          <div class="modal-footer border-top border-slate-200 py-2.5">
            <button type="button" class="btn btn-glass btn-sm" data-bs-dismiss="modal" style="min-height: 44px; padding: 0.4rem 1.2rem;">Batal</button>
            <button type="submit" class="btn btn-dark btn-sm px-4" style="min-height: 44px;">Upload Nota</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- ================= MODAL: MULAI INSPEKSI KENDARAAN ================= -->
  <div class="modal fade" id="modalMulaiInspeksiKendaraan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content glass-panel border-0 shadow-lg">
        <form action="{{ route('inspections.store') }}" method="POST">
          @csrf
          <input type="hidden" name="inspectable_type" value="{{ get_class($selectedVehicle) }}">
          <input type="hidden" name="inspectable_id" value="{{ $selectedVehicle->id }}">
          <input type="hidden" name="template_key" value="kendaraan_pra_jalan">

          <div class="modal-header border-bottom border-slate-200 py-3">
            <h6 class="modal-title fw-bold text-dark">Inspeksi Pra-Perjalanan — {{ $selectedVehicle->name }}</h6>
            <button type="button" class="btn-close small" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>

          <div class="modal-body py-3">
            <div class="row g-2 mb-3">
              <div class="col-md-6">
                <label class="form-label text-muted small mb-1">Judul Pemeriksaan <span class="text-danger">*</span></label>
                <input type="text" name="title" class="form-control" value="Inspeksi Pra-Perjalanan Kendaraan" required style="min-height: 44px;">
              </div>
              <div class="col-md-6">
                <label class="form-label text-muted small mb-1">Tanggal Inspeksi <span class="text-danger">*</span></label>
                <input type="date" name="inspected_at" class="form-control" value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}" required style="min-height: 44px;">
              </div>
            </div>

            <!-- Vehicle Pra-Jalan Checklist -->
            <div class="mb-3">
              <label class="form-label text-muted small mb-1">Checklist 6 Titik Komponen Vital</label>
              <div class="d-flex flex-column gap-2">
                @php
                  $defaultVehicleItems = $inspectionTemplates['kendaraan_pra_jalan']['items'] ?? [
                      'Tekanan & Ketebalan Alur Ban',
                      'Ketinggian & Kondisi Oli Mesin',
                      'Fungsi Rem & Level Minyak Rem',
                      'Lampu Utama, Lampu Rem, & Sein',
                      'Air Radiator, Cadangan Wiper, & Karet Wiper',
                      'Indikator Aki & Kelistrikan Starter',
                  ];
                @endphp
                @foreach ($defaultVehicleItems as $idx => $itemText)
                  <div class="p-2.5 rounded-2 bg-light border d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div class="fw-medium text-dark small" style="flex: 1 1 200px;">
                      {{ $itemText }}
                      <input type="hidden" name="items[{{ $idx }}][label]" value="{{ $itemText }}">
                    </div>
                    <div class="d-flex align-items-center gap-1.5" style="flex: 0 0 auto;">
                      <select name="items[{{ $idx }}][status]" class="form-select form-select-sm" style="min-height: 38px; width: 140px;">
                        <option value="ok" selected>Kondisi OK</option>
                        <option value="perhatian">Perhatian</option>
                        <option value="bermasalah">Bermasalah</option>
                        <option value="na">N/A</option>
                      </select>
                      <input type="text" name="items[{{ $idx }}][notes]" class="form-control form-control-sm" placeholder="Catatan (opsional)" style="min-height: 38px; width: 150px;">
                    </div>
                  </div>
                @endforeach
              </div>
            </div>

            <div class="mb-2">
              <label class="form-label text-muted small mb-1">Catatan Tambahan</label>
              <textarea name="notes" class="form-control" rows="2" placeholder="Catatan rute perjalanan atau rekomendasi servis..."></textarea>
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

  <!-- ================= MODAL: LAPOR KERUSAKAN KENDARAAN ================= -->
  <div class="modal fade" id="modalLaporKerusakanKendaraan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content glass-panel border-0 shadow-lg">
        <form action="{{ route('damage-reports.store') }}" method="POST" enctype="multipart/form-data">
          @csrf
          <input type="hidden" name="reportable_type" value="{{ get_class($selectedVehicle) }}">
          <input type="hidden" name="reportable_id" value="{{ $selectedVehicle->id }}">

          <div class="modal-header border-bottom border-slate-200 py-3">
            <h6 class="modal-title fw-bold text-dark">Laporkan Kerusakan — {{ $selectedVehicle->name }}</h6>
            <button type="button" class="btn-close small" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>

          <div class="modal-body py-3">
            <div class="mb-3">
              <label class="form-label text-muted small mb-1">Judul Kendala / Kerusakan <span class="text-danger">*</span></label>
              <input type="text" name="title" class="form-control" placeholder="Contoh: Bunyi decit saat rem mendadak" required style="min-height: 44px;">
            </div>

            <div class="row g-2 mb-3">
              <div class="col-6">
                <label class="form-label text-muted small mb-1">Tingkat Keparahan <span class="text-danger">*</span></label>
                <select name="severity" class="form-select" required style="min-height: 44px;">
                  <option value="ringan">Ringan (Baret / Lecet)</option>
                  <option value="sedang" selected>Sedang (Bunyi / Getar)</option>
                  <option value="berat">Berat (Mogok / Rem Blong)</option>
                </select>
              </div>
              <div class="col-6">
                <label class="form-label text-muted small mb-1">Tanggal Diketahui <span class="text-danger">*</span></label>
                <input type="date" name="reported_at" class="form-control" value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}" required style="min-height: 44px;">
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label text-muted small mb-1">Deskripsi Gangguan</label>
              <textarea name="description" class="form-control" rows="2" placeholder="Jelaskan kronologi, indikator speedometer, atau gejala saat melaju..."></textarea>
            </div>

            <div class="mb-2">
              <label class="form-label text-muted small mb-1">Foto Bukti / Video Kerusakan (Maks 5 MB)</label>
              <input type="file" name="photo" class="form-control" accept="image/*,application/pdf" style="min-height: 44px;">
            </div>
          </div>

          <div class="modal-footer border-top border-slate-200 py-2.5">
            <button type="button" class="btn btn-glass btn-sm" data-bs-dismiss="modal" style="min-height: 44px; padding: 0.4rem 1.2rem;">Batal</button>
            <button type="submit" class="btn btn-dark btn-sm px-4" style="min-height: 44px;">Catat Laporan</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- ================= MODAL: UPDATE STATUS KERUSAKAN KENDARAAN ================= -->
  <div class="modal fade" id="modalStatusKerusakanKendaraan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content glass-panel border-0 shadow-lg">
        <form action="" method="POST" id="formStatusKerusakanKendaraan" enctype="multipart/form-data">
          @csrf
          @method('PUT')

          <div class="modal-header border-bottom border-slate-200 py-3">
            <h6 class="modal-title fw-bold text-dark" id="modalTitleStatusKerusakanKendaraan">Update Status Perbaikan</h6>
            <button type="button" class="btn-close small" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>

          <div class="modal-body py-3">
            <div class="row g-2 mb-3">
              <div class="col-6">
                <label class="form-label text-muted small mb-1">Status Perbaikan <span class="text-danger">*</span></label>
                <select name="status" id="inputStatusDamageKendaraan" class="form-select" required style="min-height: 44px;">
                  <option value="dilaporkan">Dilaporkan</option>
                  <option value="dalam_perbaikan">Dalam Perbaikan</option>
                  <option value="selesai">Selesai Diperbaiki</option>
                </select>
              </div>
              <div class="col-6">
                <label class="form-label text-muted small mb-1">Tanggal Selesai</label>
                <input type="date" name="resolved_at" id="inputStatusResolvedAtKendaraan" class="form-control" value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}" style="min-height: 44px;">
              </div>
            </div>

            <div class="row g-2 mb-3">
              <div class="col-6">
                <label class="form-label text-muted small mb-1">Bengkel / Teknisi</label>
                <input type="text" name="technician_name" id="inputStatusTechKendaraan" class="form-control" placeholder="Nama bengkel / mekanik" style="min-height: 44px;">
              </div>
              <div class="col-6">
                <label class="form-label text-muted small mb-1">Biaya Perbaikan (Rp)</label>
                <input type="number" name="repair_cost" id="inputStatusCostKendaraan" class="form-control" value="0" min="0" step="5000" style="min-height: 44px;">
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label text-muted small mb-1">Catatan Hasil Pengerjaan</label>
              <textarea name="notes" id="inputStatusNotesKendaraan" class="form-control" rows="2" placeholder="Suku cadang yang diganti, garansi bengkel..."></textarea>
            </div>

            <div class="mb-2">
              <label class="form-label text-muted small mb-1">Nota / Bukti Servis Bengkel (Opsional)</label>
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

  <!-- ================= MODAL: TAMBAH DOKUMEN KENDARAAN ================= -->
  <div class="modal fade" id="modalTambahDokumenKendaraan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content glass-panel border-0 shadow-lg">
        <form action="{{ route('documents.store') }}" method="POST" enctype="multipart/form-data">
          @csrf
          <input type="hidden" name="subject_type" value="{{ get_class($selectedVehicle) }}">
          <input type="hidden" name="subject_id" value="{{ $selectedVehicle->id }}">

          <div class="modal-header border-bottom border-slate-200 py-3">
            <h6 class="modal-title fw-bold text-dark">Tambah Dokumen — {{ $selectedVehicle->name }}</h6>
            <button type="button" class="btn-close small" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>

          <div class="modal-body py-3">
            <div class="row g-2 mb-3">
              <div class="col-6">
                <label class="form-label text-muted small mb-1">Tipe Dokumen <span class="text-danger">*</span></label>
                <select name="type" class="form-select" required style="min-height: 44px;">
                  @foreach ($documentTypes as $typeKey => $typeLabel)
                    <option value="{{ $typeKey }}" {{ $typeKey === 'stnk_pajak' ? 'selected' : '' }}>{{ $typeLabel }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-6">
                <label class="form-label text-muted small mb-1">Nomor Registrasi / Polis</label>
                <input type="text" name="reference_number" class="form-control" placeholder="Contoh: No. STNK / No. Polis" style="min-height: 44px;">
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label text-muted small mb-1">Judul Dokumen <span class="text-danger">*</span></label>
              <input type="text" name="title" class="form-control" placeholder="Contoh: Pajak STNK Tahunan / Asuransi TLO" required style="min-height: 44px;">
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
              <label class="form-label text-muted small mb-1">Biaya / Pokok PKB (Rp)</label>
              <input type="number" name="cost" class="form-control" value="0" min="0" step="1000" style="min-height: 44px;">
            </div>

            <div class="mb-3">
              <label class="form-label text-muted small mb-1">Catatan Tambahan</label>
              <textarea name="notes" class="form-control" rows="2" placeholder="Samsat tujuan, no kontak agen asuransi..."></textarea>
            </div>

            <div class="mb-2">
              <label class="form-label text-muted small mb-1">Foto STNK / Berkas Polis (Maks 5 MB)</label>
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

  <!-- ================= MODAL: PERPANJANG DOKUMEN KENDARAAN ================= -->
  <div class="modal fade" id="modalPerpanjangDokumenKendaraan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content glass-panel border-0 shadow-lg">
        <form action="" method="POST" id="formPerpanjangDokumenKendaraan" enctype="multipart/form-data">
          @csrf
          <div class="modal-header border-bottom border-slate-200 py-3">
            <h6 class="modal-title fw-bold text-dark" id="modalTitlePerpanjangKendaraan">Perpanjang Dokumen</h6>
            <button type="button" class="btn-close small" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>

          <div class="modal-body py-3">
            <div class="p-2.5 rounded-3 bg-light border mb-3">
              <small class="text-muted d-block" style="font-size: 0.72rem;">Dokumen yang Diperpanjang:</small>
              <div class="fw-bold text-dark" id="labelPerpanjangTitleKendaraan">-</div>
              <small class="text-muted" style="font-size: 0.72rem;">Dokumen lama otomatis diarsipkan ke riwayat kepemilikan.</small>
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
                <label class="form-label text-muted small mb-1">Nomor Polis / Bukti Baru</label>
                <input type="text" name="reference_number" id="inputPerpanjangRefKendaraan" class="form-control" style="min-height: 44px;">
              </div>
              <div class="col-6">
                <label class="form-label text-muted small mb-1">Biaya Bayar (Rp)</label>
                <input type="number" name="cost" id="inputPerpanjangCostKendaraan" class="form-control" value="0" min="0" step="1000" style="min-height: 44px;">
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label text-muted small mb-1">Catatan Tambahan</label>
              <textarea name="notes" class="form-control" rows="2" placeholder="Catatan pembayaran pajak / perpanjangan asuransi..."></textarea>
            </div>

            <div class="mb-2">
              <label class="form-label text-muted small mb-1">Unggah Lembar Pajak Baru (PDF / Foto)</label>
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

  <!-- ================= MODAL: CATAT BBM ================= -->
  <div class="modal fade" id="modalCatatBBM" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content glass-panel border-0 shadow-lg">
        <form action="{{ route('vehicles.fuel-logs.store', $selectedVehicle->id) }}" method="POST" enctype="multipart/form-data">
          @csrf
          <div class="modal-header border-bottom border-slate-200 py-3">
            <h6 class="modal-title fw-bold text-dark"><i class="bi bi-fuel-pump me-1.5"></i> Catat Pengisian BBM — {{ $selectedVehicle->name }}</h6>
            <button type="button" class="btn-close small" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>

          <div class="modal-body py-3">
            <div class="row g-2 mb-3">
              <div class="col-6">
                <label class="form-label text-muted small mb-1">Tanggal Pengisian <span class="text-danger">*</span></label>
                <input type="date" name="filled_at" class="form-control" value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}" required style="min-height: 44px;">
              </div>
              <div class="col-6">
                <label class="form-label text-muted small mb-1">Odometer (KM) <span class="text-danger">*</span></label>
                <input type="number" name="odometer" class="form-control" value="{{ $selectedVehicle->odometer }}" min="0" required style="min-height: 44px;">
              </div>
            </div>

            <div class="row g-2 mb-3">
              <div class="col-6">
                <label class="form-label text-muted small mb-1">Volume Bensin (Liter) <span class="text-danger">*</span></label>
                <input type="text" name="liters" class="form-control" placeholder="mis. 32,5" required style="min-height: 44px;">
                <small class="text-muted" style="font-size: 0.72rem;">Bisa koma desimal ("32,5")</small>
              </div>
              <div class="col-6">
                <label class="form-label text-muted small mb-1">Total Biaya (Rp) <span class="text-danger">*</span></label>
                <input type="text" name="total_cost" class="form-control" placeholder="mis. 350.000" required style="min-height: 44px;">
                <small class="text-muted" style="font-size: 0.72rem;">Bisa titik ribuan</small>
              </div>
            </div>

            <div class="row g-2 mb-3">
              <div class="col-6">
                <label class="form-label text-muted small mb-1">Jenis Bahan Bakar</label>
                <input type="text" name="fuel_grade" list="fuelGradesList" class="form-control" placeholder="Pertalite / Dexlite..." style="min-height: 44px;">
                <datalist id="fuelGradesList">
                  <option value="Pertalite (RON 90)">
                  <option value="Pertamax (RON 92)">
                  <option value="Pertamax Turbo (RON 98)">
                  <option value="Solar">
                  <option value="Dexlite">
                  <option value="Pertamina Dex">
                  <option value="Shell Super">
                  <option value="Shell V-Power">
                  <option value="BP 92">
                  <option value="BP Ultimate">
                </datalist>
              </div>
              <div class="col-6">
                <label class="form-label text-muted small mb-1">Nama / Lokasi SPBU</label>
                <input type="text" name="station" class="form-control" placeholder="mis. SPBU Dago" style="min-height: 44px;">
              </div>
            </div>

            <div class="mb-3">
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="is_full_tank" id="inputIsFullTank" value="1" checked>
                <label class="form-check-label text-dark fw-medium small" for="inputIsFullTank">
                  Pengisian Tangki Penuh (Full Tank)
                </label>
              </div>
              <small class="text-muted d-block ps-1" style="font-size: 0.72rem;">Centang untuk menghitung konsumsi efisiensi km/L secara akurat.</small>
            </div>

            <div class="mb-3">
              <label class="form-label text-muted small mb-1">Catatan Tambahan (Opsional)</label>
              <textarea name="notes" class="form-control" rows="2" placeholder="Catatan pengisian BBM..."></textarea>
            </div>

            <div class="mb-2">
              <label class="form-label text-muted small mb-1">Lampiran Struk / Nota (Opsional)</label>
              <input type="file" name="file" class="form-control" accept=".jpg,.jpeg,.png,.webp,.pdf,image/*,application/pdf" style="min-height: 44px;">
              <small class="text-muted" style="font-size: 0.72rem;">Format JPG, PNG, atau PDF (maks 5 MB).</small>
            </div>
          </div>

          <div class="modal-footer border-top border-slate-200 py-2.5">
            <button type="button" class="btn btn-glass btn-sm" data-bs-dismiss="modal" style="min-height: 44px; padding: 0.4rem 1.2rem;">Batal</button>
            <button type="submit" class="btn btn-dark btn-sm px-4" style="min-height: 44px;">Simpan Catatan</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- ================= MODAL: EDIT BBM ================= -->
  <div class="modal fade" id="modalEditBBM" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content glass-panel border-0 shadow-lg">
        <form id="formEditBBM" method="POST">
          @csrf
          @method('PUT')
          <div class="modal-header border-bottom border-slate-200 py-3">
            <h6 class="modal-title fw-bold text-dark"><i class="bi bi-pencil-square me-1.5"></i> Edit Catatan BBM</h6>
            <button type="button" class="btn-close small" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>

          <div class="modal-body py-3">
            <div class="row g-2 mb-3">
              <div class="col-6">
                <label class="form-label text-muted small mb-1">Tanggal Pengisian <span class="text-danger">*</span></label>
                <input type="date" name="filled_at" id="inputEditFilledAt" class="form-control" max="{{ date('Y-m-d') }}" required style="min-height: 44px;">
              </div>
              <div class="col-6">
                <label class="form-label text-muted small mb-1">Odometer (KM) <span class="text-danger">*</span></label>
                <input type="number" name="odometer" id="inputEditOdometer" class="form-control" min="0" required style="min-height: 44px;">
              </div>
            </div>

            <div class="row g-2 mb-3">
              <div class="col-6">
                <label class="form-label text-muted small mb-1">Volume Bensin (Liter) <span class="text-danger">*</span></label>
                <input type="text" name="liters" id="inputEditLiters" class="form-control" required style="min-height: 44px;">
              </div>
              <div class="col-6">
                <label class="form-label text-muted small mb-1">Total Biaya (Rp) <span class="text-danger">*</span></label>
                <input type="text" name="total_cost" id="inputEditTotalCost" class="form-control" required style="min-height: 44px;">
              </div>
            </div>

            <div class="row g-2 mb-3">
              <div class="col-6">
                <label class="form-label text-muted small mb-1">Jenis Bahan Bakar</label>
                <input type="text" name="fuel_grade" id="inputEditFuelGrade" list="fuelGradesList" class="form-control" style="min-height: 44px;">
              </div>
              <div class="col-6">
                <label class="form-label text-muted small mb-1">Nama / Lokasi SPBU</label>
                <input type="text" name="station" id="inputEditStation" class="form-control" style="min-height: 44px;">
              </div>
            </div>

            <div class="mb-3">
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="is_full_tank" id="inputEditIsFullTank" value="1">
                <label class="form-check-label text-dark fw-medium small" for="inputEditIsFullTank">
                  Pengisian Tangki Penuh (Full Tank)
                </label>
              </div>
            </div>

            <div class="mb-2">
              <label class="form-label text-muted small mb-1">Catatan Tambahan (Opsional)</label>
              <textarea name="notes" id="inputEditNotes" class="form-control" rows="2"></textarea>
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
@endif

<!-- ================= MODAL: TAMBAH KENDARAAN (Cascading Select) ================= -->
<div class="modal fade" id="modalTambahKendaraan" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content glass-panel border-0 shadow-lg">
      <form action="{{ route('vehicles.store') }}" method="POST">
        @csrf
        <div class="modal-header border-bottom border-slate-200 py-3">
          <h6 class="modal-title fw-bold text-dark">Tambah Kendaraan Baru</h6>
          <button type="button" class="btn-close small" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        
        <div class="modal-body py-3">
          <!-- Pilih Merek -->
          <div class="mb-3">
            <label class="form-label text-muted small mb-1">Merek Kendaraan <span class="text-danger">*</span></label>
            <select class="form-select" id="selectBrand" required style="min-height: 44px;">
              <option value="" disabled selected>Pilih Merek...</option>
              @foreach ($modelsGrouped as $brand => $brandModels)
                <option value="{{ $brand }}">{{ $brand }}</option>
              @endforeach
              <option value="other">Merek Lainnya...</option>
            </select>
          </div>

          <!-- Pilih Model (Cascading) -->
          <div class="mb-3" id="wrapperModel">
            <label class="form-label text-muted small mb-1">Model Kendaraan <span class="text-danger">*</span></label>
            <select class="form-select" id="selectModel" name="vehicle_model_id" required style="min-height: 44px;">
              <option value="" disabled selected>Pilih merek terlebih dahulu...</option>
            </select>
          </div>

          <!-- Form Tambahan Khusus "Lainnya" -->
          <div id="wrapperOtherModel" class="p-3 rounded-3 bg-light border mb-3 d-none">
            <div class="mb-2">
              <label class="form-label text-muted small mb-1">Nama Model Lengkap <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="custom_model_name" placeholder="Misal: Vespa Primavera 150" style="min-height: 44px;">
            </div>
            <div>
              <label class="form-label text-muted small mb-1">Jenis Bodi <span class="text-danger">*</span></label>
              <select class="form-select" name="custom_body_type" style="min-height: 44px;">
                <option value="motor_matic">Motor Matic (Skuter)</option>
                <option value="motor_sport">Motor Sport / Manual</option>
                <option value="hatchback">Hatchback (City Car)</option>
                <option value="sedan">Sedan</option>
                <option value="mpv">MPV (Mobil Keluarga)</option>
                <option value="suv">SUV</option>
                <option value="pickup">Pickup</option>
                <option value="van">Van</option>
                <option value="bus">Bus</option>
                <option value="truk">Truk</option>
              </select>
            </div>
          </div>

          <!-- Plat & Tahun -->
          <div class="row g-2 mb-3">
            <div class="col-7">
              <label class="form-label text-muted small mb-1">Nomor Plat Polisi <span class="text-danger">*</span></label>
              <input type="text" class="form-control font-monospace text-uppercase" name="plate" placeholder="B 1234 ABC" required style="min-height: 44px;">
            </div>
            <div class="col-5">
              <label class="form-label text-muted small mb-1">Tahun Perakitan</label>
              <input type="number" class="form-control" name="year" placeholder="{{ date('Y') }}" min="1970" max="{{ date('Y') + 1 }}" style="min-height: 44px;">
            </div>
          </div>

          <!-- Odometer & Bahan Bakar -->
          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label text-muted small mb-1">Odometer Awal (KM) <span class="text-danger">*</span></label>
              <input type="number" class="form-control" name="odometer" placeholder="0" min="0" required style="min-height: 44px;">
            </div>
            <div class="col-6">
              <label class="form-label text-muted small mb-1">Bahan Bakar <span class="text-danger">*</span></label>
              <select class="form-select" name="fuel_type" required style="min-height: 44px;">
                <option value="Bensin" selected>Bensin</option>
                <option value="Diesel">Diesel</option>
                <option value="Listrik (EV)">Listrik (EV)</option>
                <option value="Hybrid">Hybrid</option>
              </select>
            </div>
          </div>

          <!-- Warna & Bengkel -->
          <div class="row g-2 mb-2">
            <div class="col-6">
              <label class="form-label text-muted small mb-1">Warna Bodi <span class="text-danger">*</span></label>
              <select class="form-select" name="color_key" required style="min-height: 44px;">
                <option value="hitam" selected>Hitam</option>
                <option value="putih">Putih</option>
                <option value="silver">Silver / Abu-abu</option>
                <option value="merah">Merah</option>
                <option value="biru">Biru</option>
                <option value="lainnya">Lainnya</option>
              </select>
            </div>
            <div class="col-6">
              <label class="form-label text-muted small mb-1">Bengkel Langganan</label>
              <input type="text" class="form-control" name="workshop" placeholder="Opsional (misal: AHASS)" style="min-height: 44px;">
            </div>
          </div>
        </div>

        <div class="modal-footer border-top border-slate-200 py-2.5">
          <button type="button" class="btn btn-glass btn-sm" data-bs-dismiss="modal" style="min-height: 44px; padding: 0.4rem 1.2rem;">Batal</button>
          <button type="submit" class="btn btn-dark btn-sm px-4" style="min-height: 44px;">Simpan ke Garasi</button>
        </div>
      </form>
    </div>
  </div>
</div>

@if ($selectedVehicle)
<!-- Modal Edit Kendaraan -->
<div class="modal fade" id="modalEditKendaraan" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content glass-panel border-0 shadow-lg">
      <form action="{{ route('vehicles.update', $selectedVehicle->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="modal-header border-bottom border-slate-200 py-3">
          <h6 class="modal-title fw-bold text-dark">Edit Informasi Kendaraan</h6>
          <button type="button" class="btn-close small" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        
        <div class="modal-body py-3">
          <!-- Pilih Merek -->
          <div class="mb-3">
            <label class="form-label text-muted small mb-1">Merek Kendaraan <span class="text-danger">*</span></label>
            <select class="form-select" id="selectBrandEdit" required style="min-height: 44px;">
              <option value="" disabled>Pilih Merek...</option>
              @foreach ($modelsGrouped as $brand => $brandModels)
                <option value="{{ $brand }}" {{ $selectedVehicle->vehicleModel?->brand === $brand ? 'selected' : '' }}>{{ $brand }}</option>
              @endforeach
              <option value="other" {{ (! $selectedVehicle->vehicle_model_id && $selectedVehicle->custom_model_name) ? 'selected' : '' }}>Merek Lainnya...</option>
            </select>
          </div>

          <!-- Pilih Model (Cascading) -->
          <div class="mb-3" id="wrapperModelEdit">
            <label class="form-label text-muted small mb-1">Model Kendaraan <span class="text-danger">*</span></label>
            <select class="form-select" id="selectModelEdit" name="vehicle_model_id" required style="min-height: 44px;">
              @if ($selectedVehicle->vehicleModel)
                @foreach ($modelsGrouped[$selectedVehicle->vehicleModel->brand] ?? [] as $m)
                  <option value="{{ $m->id }}" {{ $selectedVehicle->vehicle_model_id === $m->id ? 'selected' : '' }}>
                    {{ $m->model }} ({{ strtoupper(str_replace('_', ' ', $m->body_type)) }})
                  </option>
                @endforeach
                <option value="other">+ Model Lainnya...</option>
              @else
                <option value="other" selected>Model Kustom / Lainnya</option>
              @endif
            </select>
          </div>

          <!-- Form Tambahan Khusus "Lainnya" -->
          <div id="wrapperOtherModelEdit" class="p-3 rounded-3 bg-light border mb-3 {{ $selectedVehicle->vehicle_model_id ? 'd-none' : '' }}">
            <div class="mb-2">
              <label class="form-label text-muted small mb-1">Nama Model Lengkap <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="custom_model_name" value="{{ $selectedVehicle->custom_model_name }}" placeholder="Misal: Vespa Primavera 150" style="min-height: 44px;">
            </div>
            <div>
              <label class="form-label text-muted small mb-1">Jenis Bodi <span class="text-danger">*</span></label>
              <select class="form-select" name="custom_body_type" style="min-height: 44px;">
                @foreach (['motor_matic' => 'Motor Matic (Skuter)', 'motor_sport' => 'Motor Sport / Manual', 'hatchback' => 'Hatchback (City Car)', 'sedan' => 'Sedan', 'mpv' => 'MPV (Mobil Keluarga)', 'suv' => 'SUV', 'pickup' => 'Pickup', 'van' => 'Van', 'bus' => 'Bus', 'truk' => 'Truk'] as $bKey => $bLabel)
                  <option value="{{ $bKey }}" {{ $selectedVehicle->custom_body_type === $bKey ? 'selected' : '' }}>{{ $bLabel }}</option>
                @endforeach
              </select>
            </div>
          </div>

          <!-- Plat & Tahun -->
          <div class="row g-2 mb-3">
            <div class="col-7">
              <label class="form-label text-muted small mb-1">Nomor Plat Polisi <span class="text-danger">*</span></label>
              <input type="text" class="form-control font-monospace text-uppercase" name="plate" value="{{ $selectedVehicle->plate }}" required style="min-height: 44px;">
            </div>
            <div class="col-5">
              <label class="form-label text-muted small mb-1">Tahun Perakitan</label>
              <input type="number" class="form-control" name="year" value="{{ $selectedVehicle->year }}" placeholder="{{ date('Y') }}" min="1970" max="{{ date('Y') + 1 }}" style="min-height: 44px;">
            </div>
          </div>

          <!-- Bahan Bakar & Warna -->
          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label text-muted small mb-1">Bahan Bakar <span class="text-danger">*</span></label>
              <select class="form-select" name="fuel_type" required style="min-height: 44px;">
                @foreach (['Bensin', 'Diesel', 'Listrik (EV)', 'Hybrid'] as $ft)
                  <option value="{{ $ft }}" {{ $selectedVehicle->fuel_type === $ft ? 'selected' : '' }}>{{ $ft }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-6">
              <label class="form-label text-muted small mb-1">Warna Bodi <span class="text-danger">*</span></label>
              <select class="form-select text-capitalize" name="color_key" required style="min-height: 44px;">
                @foreach (['hitam', 'putih', 'silver', 'merah', 'biru', 'lainnya'] as $ck)
                  <option value="{{ $ck }}" {{ strtolower($selectedVehicle->color_key) === $ck ? 'selected' : '' }}>{{ ucfirst($ck) }}</option>
                @endforeach
              </select>
            </div>
          </div>

          <!-- Bengkel Langganan -->
          <div class="mb-2">
            <label class="form-label text-muted small mb-1">Bengkel Langganan</label>
            <input type="text" class="form-control" name="workshop" value="{{ $selectedVehicle->workshop }}" placeholder="Opsional (misal: AHASS)" style="min-height: 44px;">
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

<!-- Modal Arsip Kendaraan -->
<div class="modal fade" id="modalArsipKendaraan" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content glass-panel border-0 shadow-lg">
      <form action="{{ route('vehicles.archive', $selectedVehicle->id) }}" method="POST">
        @csrf
        <div class="modal-header border-bottom border-slate-200 py-3">
          <h6 class="modal-title fw-bold text-dark">Arsipkan Kendaraan</h6>
          <button type="button" class="btn-close small" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        
        <div class="modal-body py-3">
          <p class="text-muted small mb-3">
            Kendaraan yang diarsipkan tidak akan muncul di pemilih garasi aktif, kalender, dan pengingat beranda. Seluruh riwayat servis, pengeluaran BBM, dan laporan keuangan <strong>tetap tersimpan</strong> dan dapat dipulihkan sewaktu-waktu.
          </p>

          <div class="mb-3">
            <label class="form-label text-muted small mb-1">Alasan Pengarsipan <span class="text-danger">*</span></label>
            <select class="form-select" name="archive_reason" required style="min-height: 44px;">
              <option value="dijual" selected>Kendaraan Telah Dijual</option>
              <option value="rusak_berat">Rusak Berat / Tidak Digunakan</option>
              <option value="lainnya">Alasan Lainnya</option>
            </select>
          </div>
        </div>

        <div class="modal-footer border-top border-slate-200 py-2.5">
          <button type="button" class="btn btn-glass btn-sm" data-bs-dismiss="modal" style="min-height: 44px; padding: 0.4rem 1.2rem;">Batal</button>
          <button type="submit" class="btn btn-danger btn-sm px-4" style="min-height: 44px;">Arsipkan Sekarang</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Hapus Permanen Kendaraan -->
<div class="modal fade" id="modalHapusPermanenKendaraan" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content glass-panel border-0 shadow-lg">
      <form action="{{ route('vehicles.destroy', $selectedVehicle->id) }}" method="POST">
        @csrf
        @method('DELETE')
        <div class="modal-header border-bottom border-slate-200 py-3">
          <h6 class="modal-title fw-bold text-danger">Hapus Permanen Kendaraan</h6>
          <button type="button" class="btn-close small" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        
        <div class="modal-body py-3">
          <div class="alert alert-danger border-0 small mb-3" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-1"></i>
            <strong>Peringatan!</strong> Tindakan ini tidak dapat dibatalkan. Seluruh riwayat servis, jadwal, log BBM, laporan kerusakan, berkas dokumen, dan semua lampiran foto fisik akan <strong>dihapus permanen</strong>.
          </div>

          <p class="text-muted small mb-2">
            Untuk mengonfirmasi, ketik nomor plat kendaraan ini: <strong class="text-dark font-monospace">{{ $selectedVehicle->plate }}</strong>
          </p>

          <div class="mb-2">
            <input type="text" name="confirm_plate" class="form-control font-monospace text-uppercase" placeholder="Ketik {{ $selectedVehicle->plate }}" required style="min-height: 44px;">
          </div>
        </div>

        <div class="modal-footer border-top border-slate-200 py-2.5">
          <button type="button" class="btn btn-glass btn-sm" data-bs-dismiss="modal" style="min-height: 44px; padding: 0.4rem 1.2rem;">Batal</button>
          <button type="submit" class="btn btn-danger btn-sm px-4" style="min-height: 44px;">Hapus Permanen</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endif
