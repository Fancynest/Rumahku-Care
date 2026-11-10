@extends('layouts.app')

@section('title', 'Buku Kontak Teknisi & Bengkel - Rumahku Care')

@section('content')
<!-- Header Actions -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
  <div>
    <h4 class="fw-bold mb-1 text-dark">Buku Kontak Teknisi & Bengkel</h4>
    <p class="text-muted mb-0 small">Simpan kontak montir, bengkel, dan tukang langganan Anda sendiri agar tidak repot mencari saat butuh.</p>
  </div>
  <div class="d-flex gap-2">
    <button class="btn btn-dark btn-sm d-flex align-items-center gap-1.5" data-bs-toggle="modal" data-bs-target="#modalTambahTeknisi">
      <i class="bi bi-person-plus"></i>
      <span>Tambah Kontak Baru</span>
    </button>
  </div>
</div>

<!-- Filter Bar -->
<div class="glass-panel p-3 mb-4">
  <div class="row g-2 align-items-center mb-3">
    <div class="col-md-6">
      <div class="input-group">
        <span class="input-group-text bg-transparent border-end-0 text-muted ps-3"><i class="bi bi-search"></i></span>
        <input type="text" class="form-control border-start-0 ps-1" placeholder="Cari nama teknisi, bengkel, atau bidang...">
      </div>
    </div>
    <div class="col-md-4">
      <select class="form-select">
        <option value="">Semua Bidang Keahlian</option>
        <option>Teknisi AC & Pendingin</option>
        <option>Bengkel Mobil & Montir</option>
        <option>Bengkel Motor & Servis</option>
        <option>Listrik & Pompa Air</option>
        <option>Tukang Bangunan & Ledeng</option>
        <option>Elektronik & Kulkas</option>
      </select>
    </div>
    <div class="col-md-2 text-md-end">
      <button class="btn btn-glass btn-sm w-100 py-2"><i class="bi bi-arrow-repeat"></i> Reset</button>
    </div>
  </div>

  <div class="d-flex gap-2.5 overflow-x-auto pb-1">
    <button class="btn btn-dark btn-sm px-3.5 py-1.5 rounded-pill text-nowrap text-xs">Semua Kontak (4)</button>
    <button class="btn btn-glass btn-sm px-3.5 py-1.5 rounded-pill text-nowrap text-xs">Teknisi AC (1)</button>
    <button class="btn btn-glass btn-sm px-3.5 py-1.5 rounded-pill text-nowrap text-xs">Bengkel Kendaraan (2)</button>
    <button class="btn btn-glass btn-sm px-3.5 py-1.5 rounded-pill text-nowrap text-xs">Listrik & Pompa (1)</button>
  </div>
</div>

<!-- ================= CONTACT CARDS GRID ================= -->
<div class="row g-4">
  
  <!-- Kontak 1: Pak Budi (AC) -->
  <div class="col-md-6 col-xl-4">
    <div class="glass-panel p-4 h-100 d-flex flex-column justify-content-between">
      <div>
        <div class="d-flex justify-content-between align-items-start mb-3">
          <div class="d-flex align-items-center gap-2.5">
            <div class="avatar-lg bg-dark text-white">
              PB
            </div>
            <div>
              <h6 class="fw-bold text-dark mb-0">Pak Budi (Teknisi AC)</h6>
              <span class="glass-pill py-0 px-2 mt-1 text-2xs">Teknisi AC & Freon</span>
            </div>
          </div>
        </div>

        <div class="small text-muted mb-3">
          <div class="mb-1"><i class="bi bi-geo-alt me-1.5 text-dark"></i> Area Cipete & Jakarta Selatan</div>
          <div class="mb-1"><i class="bi bi-telephone me-1.5 text-dark"></i> +62 812-3456-7890</div>
          <div><i class="bi bi-star-fill me-1.5 text-warning"></i> Rating Langganan: <strong>5.0</strong> (Sangat Rapi)</div>
        </div>

        <div class="p-2.5 rounded-3 bg-light border mb-3 small text-xs">
          <div class="fw-semibold text-dark mb-1">Catatan Pribadi:</div>
          <div class="text-muted">Ongkos cuci AC 75rb/unit, bawa tangga sendiri, bisa datang Sabtu/Minggu pagi.</div>
        </div>
      </div>

      <div class="d-flex gap-2 pt-2 border-top border-slate-200">
        <a href="https://wa.me/6281234567890?text=Halo%20Pak%20Budi,%20mau%20jadwalkan%20servis%20AC" target="_blank" class="btn btn-dark btn-sm flex-fill d-flex align-items-center justify-content-center gap-1.5">
          <i class="bi bi-whatsapp text-success"></i>
          <span>Hubungi WA</span>
        </a>
        <a href="tel:081234567890" class="btn btn-glass btn-sm px-3" title="Telepon">
          <i class="bi bi-telephone"></i>
        </a>
      </div>
    </div>
  </div>

  <!-- Kontak 2: Bengkel Mobil Berkah -->
  <div class="col-md-6 col-xl-4">
    <div class="glass-panel p-4 h-100 d-flex flex-column justify-content-between">
      <div>
        <div class="d-flex justify-content-between align-items-start mb-3">
          <div class="d-flex align-items-center gap-2.5">
            <div class="avatar-lg bg-dark text-white">
              BB
            </div>
            <div>
              <h6 class="fw-bold text-dark mb-0">Bengkel Berkah Motor</h6>
              <span class="glass-pill py-0 px-2 mt-1 text-2xs">Servis Mobil & Ganti Oli</span>
            </div>
          </div>
        </div>

        <div class="small text-muted mb-3">
          <div class="mb-1"><i class="bi bi-geo-alt me-1.5 text-dark"></i> Jl. Fatmawati No. 42 (Dekat SPBU)</div>
          <div class="mb-1"><i class="bi bi-telephone me-1.5 text-dark"></i> +62 898-7654-3210</div>
          <div><i class="bi bi-car-front me-1.5 text-dark"></i> Rujukan Servis: <strong>Kendaraan Pribadi</strong></div>
        </div>

        <div class="p-2.5 rounded-3 bg-light border mb-3 small text-xs">
          <div class="fw-semibold text-dark mb-1">Catatan Pribadi:</div>
          <div class="text-muted">Montirnya jujur, suku cadang asli Toyota ada bukti nota dan garansi 1 bulan.</div>
        </div>
      </div>

      <div class="d-flex gap-2 pt-2 border-top border-slate-200">
        <a href="https://wa.me/6289876543210?text=Halo%20Bengkel%20Berkah,%20mau%20tanya%20jadwal%20servis" target="_blank" class="btn btn-dark btn-sm flex-fill d-flex align-items-center justify-content-center gap-1.5">
          <i class="bi bi-whatsapp text-success"></i>
          <span>Hubungi WA</span>
        </a>
        <a href="tel:089876543210" class="btn btn-glass btn-sm px-3" title="Telepon">
          <i class="bi bi-telephone"></i>
        </a>
      </div>
    </div>
  </div>

  <!-- Kontak 3: AHASS Cipete (Motor) -->
  <div class="col-md-6 col-xl-4">
    <div class="glass-panel p-4 h-100 d-flex flex-column justify-content-between">
      <div>
        <div class="d-flex justify-content-between align-items-start mb-3">
          <div class="d-flex align-items-center gap-2.5">
            <div class="avatar-lg bg-dark text-white">
              AH
            </div>
            <div>
              <h6 class="fw-bold text-dark mb-0">AHASS Cipete 0123</h6>
              <span class="glass-pill py-0 px-2 mt-1 text-2xs">Bengkel Resmi Honda</span>
            </div>
          </div>
        </div>

        <div class="small text-muted mb-3">
          <div class="mb-1"><i class="bi bi-geo-alt me-1.5 text-dark"></i> Jl. RS Fatmawati Kav. 18</div>
          <div class="mb-1"><i class="bi bi-telephone me-1.5 text-dark"></i> +62 821-9988-7766</div>
          <div><i class="bi bi-bicycle me-1.5 text-dark"></i> Langganan untuk: <strong>Honda Vario 160</strong></div>
        </div>

        <div class="p-2.5 rounded-3 bg-light border mb-3 small text-xs">
          <div class="fw-semibold text-dark mb-1">Catatan Pribadi:</div>
          <div class="text-muted">Bisa booking antrian lewat WhatsApp H-1 agar tidak perlu tunggu lama di lokasi.</div>
        </div>
      </div>

      <div class="d-flex gap-2 pt-2 border-top border-slate-200">
        <a href="https://wa.me/6282199887766?text=Halo%20AHASS,%20mau%20booking%20servis%20Vario" target="_blank" class="btn btn-dark btn-sm flex-fill d-flex align-items-center justify-content-center gap-1.5">
          <i class="bi bi-whatsapp text-success"></i>
          <span>Booking WA</span>
        </a>
        <a href="tel:082199887766" class="btn btn-glass btn-sm px-3" title="Telepon">
          <i class="bi bi-telephone"></i>
        </a>
      </div>
    </div>
  </div>

  <!-- Kontak 4: Mas Joko (Listrik & Pompa) -->
  <div class="col-md-6 col-xl-4">
    <div class="glass-panel p-4 h-100 d-flex flex-column justify-content-between">
      <div>
        <div class="d-flex justify-content-between align-items-start mb-3">
          <div class="d-flex align-items-center gap-2.5">
            <div class="avatar-lg bg-dark text-white">
              MJ
            </div>
            <div>
              <h6 class="fw-bold text-dark mb-0">Mas Joko (Tukang Listrik & Pompa)</h6>
              <span class="glass-pill py-0 px-2 mt-1 text-2xs">Instalasi & Pompa Air</span>
            </div>
          </div>
        </div>

        <div class="small text-muted mb-3">
          <div class="mb-1"><i class="bi bi-geo-alt me-1.5 text-dark"></i> Tinggal dekat komplek (Blok B)</div>
          <div class="mb-1"><i class="bi bi-telephone me-1.5 text-dark"></i> +62 811-2233-4455</div>
          <div><i class="bi bi-lightning-charge me-1.5 text-dark"></i> Ahli: <strong>Panel MCB & Pompa Wasser</strong></div>
        </div>

        <div class="p-2.5 rounded-3 bg-light border mb-3 small text-xs">
          <div class="fw-semibold text-dark mb-1">Catatan Pribadi:</div>
          <div class="text-muted">Bisa dipanggil mendadak jika pompa macet atau MCB jeglek terus.</div>
        </div>
      </div>

      <div class="d-flex gap-2 pt-2 border-top border-slate-200">
        <a href="https://wa.me/6281122334455?text=Halo%20Mas%20Joko,%20bisa%20minta%20tolong%20cek%20rumah?" target="_blank" class="btn btn-dark btn-sm flex-fill d-flex align-items-center justify-content-center gap-1.5">
          <i class="bi bi-whatsapp text-success"></i>
          <span>Hubungi WA</span>
        </a>
        <a href="tel:081122334455" class="btn btn-glass btn-sm px-3" title="Telepon">
          <i class="bi bi-telephone"></i>
        </a>
      </div>
    </div>
  </div>

</div>
@endsection

@section('modals')
<!-- Modal Tambah Teknisi Baru -->
<div class="modal fade" id="modalTambahTeknisi" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content glass-panel border-0 shadow-lg p-3">
      <div class="modal-header border-bottom border-slate-200 pb-2">
        <h6 class="modal-title fw-bold text-dark">Daftarkan Kontak Teknisi / Bengkel</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-3">
        <form>
          <div class="mb-3">
            <label class="form-label small fw-semibold text-muted">Nama Orang / Nama Bengkel <span class="text-danger">*</span></label>
            <input type="text" class="form-control" placeholder="Contoh: Pak Budi (AC) / Bengkel Jaya" required>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold text-muted">Bidang Keahlian / Kategori <span class="text-danger">*</span></label>
            <select class="form-select" required>
              <option value="">Pilih Bidang...</option>
              <option>Teknisi AC & Pendingin</option>
              <option>Bengkel Mobil & Montir</option>
              <option>Bengkel Motor & CVT</option>
              <option>Listrik & Panel MCB</option>
              <option>Pompa Air & Saluran Pipa</option>
              <option>Tukang Bangunan & Atap</option>
              <option>Kulkas & Elektronik Dapur</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold text-muted">Nomor WhatsApp / Telepon <span class="text-danger">*</span></label>
            <input type="text" class="form-control font-monospace" placeholder="Contoh: 08123456789" required>
            <small class="text-muted text-2xs">Akan dibuatkan tombol direct chat WhatsApp otomatis.</small>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold text-muted">Alamat / Lokasi Bengkel</label>
            <input type="text" class="form-control" placeholder="Contoh: Jl. Fatmawati / Dekat rumah">
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold text-muted">Catatan Pribadi</label>
            <textarea class="form-control" rows="2" placeholder="Tarif kisaran, kebiasaan kerja, atau rekomendasi khusus..."></textarea>
          </div>
          <button type="button" class="btn btn-dark w-100 py-2 fw-medium rounded-3" data-bs-dismiss="modal">Simpan Kontak ke Buku Telepon</button>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection
