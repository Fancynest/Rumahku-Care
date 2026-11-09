# Panduan & Daftar Kebutuhan Aset Visual Kendaraan

Folder ini menyimpan aset visual model 3D (`.glb`), render transparan studio (`.webp` / `.png`), dan siluet vektor generik (`generic/*.svg`) untuk setiap tipe kendaraan di **Rumahku Care**.

## Standar Format & Ukuran Aset
* **Model 3D (`.glb`):** Format Binary glTF (`.glb`), ukuran maksimal **< 2 MB** per model. Tekstur bodi di-assign material dengan nama `Body` atau `Paint` agar penyesuaian warna `baseColorFactor` berfungsi lewat JS model-viewer.
* **Gambar Studio Cutout (`.webp` / `.png`):** Latar belakang **100% transparan (alpha cutout)**, ukuran file **< 200 KB**, dimensi lebar maksimal **1200px** (rasio horizontal 16:9 atau 4:3).
* **Aset Generik (`generic/*.svg`):** Vektor siluet transparan untuk fallback otomatis per `body_type` jika model belum memiliki gambar/GLB khusus (mencakup: `motor_matic`, `motor_sport`, `hatchback`, `sedan`, `mpv`, `suv`, `pickup`, `van`, `bus`, `truk`, dan `default`). Sistem tidak akan pernah blank atau error.

---

## Tabel Kebutuhan File per Model Kendaraan

| Brand | Model | Jenis Bodi | File Gambar Diharapkan | File 3D GLB Diharapkan | Status Saat Ini |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **Honda** | Beat Street | `motor_matic` | `honda-beat.webp` | `honda-beat.glb` | **Tersedia (WebP 140 KB, transparan)** |
| **Honda** | Vario 125 | `motor_matic` | `honda-vario-125.webp` | `honda-vario-125.glb` | Fallback Generik (`generic/motor_matic.svg`) |
| **Honda** | Vario 160 | `motor_matic` | `honda-motorcycle.png` | `honda-vario-160.glb` | **Tersedia (PNG 139 KB, transparan)** |
| **Honda** | Scoopy | `motor_matic` | `honda-scoopy.webp` | `honda-scoopy.glb` | Fallback Generik (`generic/motor_matic.svg`) |
| **Honda** | CBR150R | `motor_sport` | `honda-cbr150r.webp` | `honda-cbr150r.glb` | Fallback Generik (`generic/motor_sport.svg`) |
| **Honda** | Brio Satya | `hatchback` | `honda-brio.webp` | `honda-brio.glb` | Fallback Generik (`generic/hatchback.svg`) |
| **Yamaha** | NMAX 155 | `motor_matic` | `yamaha-nmax.webp` | `yamaha-nmax.glb` | Fallback Generik (`generic/motor_matic.svg`) |
| **Yamaha** | Aerox 155 | `motor_matic` | `yamaha-aerox.webp` | `yamaha-aerox.glb` | Fallback Generik (`generic/motor_matic.svg`) |
| **Yamaha** | YZF-R15 | `motor_sport` | `yamaha-r15.webp` | `yamaha-r15.glb` | Fallback Generik (`generic/motor_sport.svg`) |
| **Toyota** | Avanza 1.5 G | `mpv` | `toyota-avanza.webp` | `toyota-avanza.glb` | **Perlu diganti** (`toyota-avanza.png` format JPEG, belum transparan) |
| **Toyota** | Innova Zenix | `mpv` | `toyota-innova-zenix.webp` | `toyota-innova-zenix.glb` | Fallback Generik (`generic/mpv.svg`) |
| **Toyota** | Land Cruiser Prado | `suv` | `toyota-prado.webp` | `toyota-prado.glb` | **Tersedia (WebP 33 KB, transparan)** |
| **Toyota** | Hilux D-Cab | `pickup` | `toyota-hilux.webp` | `toyota-hilux.glb` | Fallback Generik (`generic/pickup.svg`) |
| **Toyota** | Hiace Commuter | `van` | `toyota-hiace.webp` | `toyota-hiace.glb` | Fallback Generik (`generic/van.svg`) |
| **Mitsubishi** | Xpander | `mpv` | `mitsubishi-xpander.webp` | `mitsubishi-xpander.glb` | Fallback Generik (`generic/mpv.svg`) |

---

## File Aset yang Perlu Disediakan Pengguna:
1. **`toyota-avanza.webp` (Prioritas):** Gambar cutout Toyota Avanza transparan (alpha channel) < 200 KB untuk menggantikan `toyota-avanza.png` yang berformat JPEG.
2. **File 3D GLB (`.glb` < 2 MB):** Untuk mengaktifkan panggung interaktif 3D 360° dan kustomisasi cat real-time (misal: `honda-beat.glb`, `toyota-prado.glb`).
3. **Render cutout model lainnya:** Gambar transparan untuk Vario 125, Scoopy, NMAX, Xpander, Zenix (opsional, jika ingin menggantikan fallback vektor generik).
