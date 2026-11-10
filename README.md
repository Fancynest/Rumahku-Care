# Rumahku Care

Rumahku Care adalah aplikasi *personal asset manager* berbasis web yang dirancang untuk membantu pengelolaan, pemeliharaan, dan pencatatan berkala seluruh aset hunian pribadi serta kendaraan keluarga dalam satu tempat terpadu.

---

## Fitur Utama

- **Aset Rumah:** Inventaris aset hunian berdasarkan kategori dan ruangan, pemantauan kondisi fisik (normal, perlu perhatian, rusak), serta riwayat perawatannya.
- **Kendaraan:** Manajemen garasi digital, pencatatan odometer berkala, visualisasi model kendaraan (3D GLB & vektor 2D), pemilihan katalog terstandar, serta mekanisme pengarsipan.
- **Jadwal & Pengingat:** Sistem penjadwalan fleksibel berbasis tanggal spesifik, interval hari, atau jarak tempuh (kilometer) dengan indikator status otomatis (normal, perhatian, mendesak, terlambat).
- **Inspeksi Berkala:** Formulir pemeriksaan rutin terstruktur (keselamatan hunian, instalasi air/listrik, dan kelaikan kendaraan) dengan fitur konversi temuan bermasalah langsung menjadi laporan kerusakan.
- **Laporan Kerusakan:** Pelaporan insiden kerusakan fisik aset beserta tingkat keparahan, riwayat tindak lanjut, koordinasi teknisi, dan realisasi biaya perbaikan.
- **Dokumen & Garansi:** Arsip dokumen legalitas dan kepemilikan (STNK, pajak tahunan, polis asuransi, sertifikat, buku manual, nota pembelian) dengan peringatan jatuh tempo dan alur perpanjangan.
- **Bahan Bakar (BBM):** Pencatatan log pengisian BBM, kalkulasi efisiensi konsumsi (km/liter) menggunakan metode *full-to-full*, serta estimasi pengeluaran per kilometer.
- **Keuangan & Anggaran:** Rekapitulasi pengeluaran berkala lintas 4 pilar (servis, BBM, perbaikan, dokumen), penetapan pagu anggaran bulanan dengan pelacak progres, visualisasi tren 6 bulan, dan fasilitas ekspor laporan ke format CSV.
- **Kalender Perawatan:** Tampilan kalender bulanan terpadu untuk memantau agenda perawatan dan jatuh tempo dokumen mendatang, dilengkapi daftar agenda harian responsif.
- **Linimasa Riwayat:** Rekam jejak kronologis terpadu dari seluruh aktivitas pemeliharaan, pengisian BBM, perbaikan kerusakan, inspeksi, dan dokumen.

---

## Tech Stack

- **Backend:** PHP 8.4, Laravel 12
- **Frontend:** Blade Templates, Bootstrap 5.3, Bootstrap Icons
- **Visualisasi 3D:** Google Model-Viewer Web Component (GLTF/GLB)
- **Database:** SQLite (default lokal) / MySQL (kompatibel penuh, *database-agnostic*)
- **Pengujian:** PHPUnit

---

## Tangkapan Layar

> *Placeholder: Cuplikan antarmuka Rumahku Care.*

```text
+-----------------------------------------------------------------------+
|  Rumahku Care                                         [ Gabriel v ]   |
+-----------------------------------------------------------------------+
|  Beranda   | Halo Gabriel, seluruh aset dalam kondisi prima.         |
|  Rumah     |                                                         |
|  Kendaraan | [ Pengeluaran Bulan Ini: Rp 850.000 ] [ 4 Aset Prima ]  |
|  Jadwal    |                                                         |
|  Keuangan  | Jadwal Paling Mendesak:                                 |
|            | - Servis Oli Berkala Honda Beat (Sisa 150 KM)           |
|            | - Cuci AC Ruang Tamu (Jatuh tempo 5 hari lagi)          |
+-----------------------------------------------------------------------+
```

---

## Instalasi Lokal

### Prasyarat
- PHP >= 8.4 dengan ekstensi `pdo_sqlite`, `mbstring`, `fileinfo`, `openssl`
- Composer >= 2.x

### Langkah Pemasangan

1. **Clone repositori:**
   ```bash
   git clone https://github.com/Fancynest/Rumahku-Care.git
   cd Rumahku-Care
   ```

2. **Pasang dependensi backend:**
   ```bash
   composer install
   ```

3. **Siapkan berkas lingkungan:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Jalankan migrasi dan seeder awal:**
   ```bash
   php artisan migrate --seed
   ```

5. **Jalankan server pengembangan lokal:**
   ```bash
   php artisan serve
   ```
   Akses aplikasi melalui peramban web di `http://127.0.0.1:8000`.

---

## Akun Demo

Setelah menjalankan seeder bawaan (`DatabaseSeeder`), akun default berikut dapat langsung digunakan untuk masuk:

- **Email:** `gabriel@rumahkucare.id`
- **Kata Sandi:** `password`

Untuk mengisi dataset simulasi pengujian yang lebih lengkap (aset rumah, kendaraan terarsip, aneka kerusakan, log BBM, dan anggaran):
```bash
php artisan db:seed --class=DemoDataSeeder
```

---

## Menjalankan Pengujian

Jalankan seluruh rangkaian pengujian fitur dan unit menggunakan runner bawaan:

```bash
php artisan test
```

---

## Struktur Folder Singkat

```text
Rumahku-Care/
├── app/
│   ├── Http/Controllers/       # Controller web & otentikasi
│   ├── Models/                 # Eloquent model (Vehicle, HomeAsset, dsb.)
│   └── Services/               # Domain logic (jadwal, kalkulator BBM, keuangan)
├── config/                     # Konfigurasi aplikasi & pemeliharaan (maintenance.php)
├── database/
│   ├── migrations/             # Definisi skema tabel database
│   └── seeders/                # Data awal & contoh demonstrasi
├── public/
│   ├── css/style.css           # Lembar gaya tata letak aplikasi
│   └── images/vehicles/        # Aset gambar & vektor visual kendaraan
├── resources/
│   └── views/                  # Template tampilan Blade
├── routes/
│   └── web.php                 # Rute antarmuka pengguna
└── tests/                      # Pengujian otomatis Feature & Unit
```

---

## Rencana Pengembangan Mendatang

Beberapa fitur yang direncanakan untuk tahap iterasi selanjutnya:
- Integrasi notifikasi langsung melalui jalur pesan instan (WhatsApp / Telegram) atau surel.
- Mode tampilan gelap (*dark mode*) otomatis.
- Manajemen hak akses multi-pengguna / peran anggota keluarga dalam satu hunian.
- Dukungan pembacaan dokumen otomatis via OCR untuk nota servis dan struk BBM.

---

## Lisensi

Proyek ini dirilis di bawah lisensi terbuka [MIT](LICENSE).
