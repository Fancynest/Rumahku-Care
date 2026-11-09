# Panduan & Daftar Kebutuhan Aset Visual Kendaraan

Folder ini menyimpan aset visual model 3D (`.glb`) dan render transparan studio (`.webp` / `.png`) untuk setiap tipe kendaraan di **Rumahku Care**.

## Standar Format & Ukuran Aset
* **Model 3D (`.glb`):** Format Binary glTF (`.glb`), ukuran maksimal **< 2 MB** per model. Tekstur bodi di-assign material dengan nama `Body` atau `Paint` agar penyesuaian warna `baseColorFactor` berfungsi lewat JS model-viewer.
* **Gambar Studio Cutout (`.webp`):** Latar belakang **100% transparan (alpha cutout)**, ukuran file **< 200 KB**, dimensi lebar maksimal **1200px** (rasio horizontal 16:9 atau 4:3).
* **Aset Generik (`generic/*.svg`):** Vektor siluet transparan untuk fallback otomatis per `body_type` jika model belum memiliki gambar/GLB khusus (tidak akan pernah blank/error).

---

## Tabel Kebutuhan File per Model Kendaraan

| Brand | Model | Jenis Bodi | File Gambar Diharapkan | File 3D GLB Diharapkan | Status Saat Ini |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **Honda** | Beat Street | `motor_matic` | `honda-beat.webp` | `honda-beat.glb` | **Tersedia (WebP 140 KB, transparan)** |
| **Honda** | Vario 125 | `motor_matic` | `honda-vario-125.webp` | `honda-vario-125.glb` | Fallback Generik (`generic/motor_matic.svg`) |
| **Honda** | Vario 160 | `motor_matic` | `honda-motorcycle.png` | `honda-vario-160.glb` | **Tersedia (PNG 139 KB, transparan)** |
| **Honda** | Scoopy | `motor_matic` | `honda-scoopy.webp` | `honda-scoopy.glb` | Fallback Generik (`generic/motor_matic.svg`) |
| **Honda** | CBR150R | `motor_sport` | `honda-cbr150r.webp` | `honda-cbr150r.glb` | Fallback Generik (`generic/default.svg`) |
| **Honda** | Brio Satya | `hatchback` | `honda-brio.webp` | `honda-brio.glb` | Fallback Generik (`generic/default.svg`) |
| **Yamaha** | NMAX 155 | `motor_matic` | `yamaha-nmax.webp` | `yamaha-nmax.glb` | Fallback Generik (`generic/motor_matic.svg`) |
| **Yamaha** | Aerox 155 | `motor_matic` | `yamaha-aerox.webp` | `yamaha-aerox.glb` | Fallback Generik (`generic/motor_matic.svg`) |
| **Toyota** | Avanza 1.5 G | `mpv` | `toyota-avanza.webp` | `toyota-avanza.glb` | **Tersedia (PNG 77 KB, transparan)** |
| **Toyota** | Innova Zenix | `mpv` | `toyota-innova-zenix.webp` | `toyota-innova-zenix.glb` | Fallback Generik (`generic/mpv.svg`) |
| **Toyota** | Land Cruiser Prado | `suv` | `toyota-prado.webp` | `toyota-prado.glb` | **Tersedia (WebP 33 KB, transparan)** |
| **Toyota** | Hilux D-Cab | `pickup` | `toyota-hilux.webp` | `toyota-hilux.glb` | Fallback Generik (`generic/default.svg`) |
| **Toyota** | Hiace Commuter | `van` | `toyota-hiace.webp` | `toyota-hiace.glb` | Fallback Generik (`generic/default.svg`) |
| **Mitsubishi** | Xpander | `mpv` | `mitsubishi-xpander.webp` | `mitsubishi-xpander.glb` | Fallback Generik (`generic/mpv.svg`) |

---

## File yang Dapat Diberikan Tambahan oleh Pengguna:
1. `toyota-avanza.webp` atau foto Avanza resolusi tinggi transparan (jika ingin mengganti yang saat ini).
2. File GLB 3D (seperti `toyota-prado.glb`, `honda-beat.glb`) berukuran di bawah 2 MB untuk aktivasi rotasi 360° interaktif via `<model-viewer>`.
