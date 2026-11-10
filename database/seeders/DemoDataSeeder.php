<?php

namespace Database\Seeders;

use App\Models\Budget;
use App\Models\DamageReport;
use App\Models\Document;
use App\Models\FuelLog;
use App\Models\HomeAsset;
use App\Models\InspectionRun;
use App\Models\InspectionRunItem;
use App\Models\MaintenanceRecord;
use App\Models\MaintenanceSchedule;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleModel;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Akun Kosong (untuk pengujian Empty State di semua halaman)
        User::firstOrCreate(
            ['email' => 'empty@rumahkucare.id'],
            [
                'name' => 'Pengguna Baru Tanpa Aset',
                'password' => Hash::make('password'),
            ]
        );

        // 2. Akun Demo Penuh / Stress Test (teks panjang, banyak data, status beragam)
        $user = User::firstOrCreate(
            ['email' => 'demo@rumahkucare.id'],
            [
                'name' => 'Raden Mas Haryo Danangjoyo Kusumo Hadiningrat',
                'password' => Hash::make('password'),
            ]
        );

        // Aset Rumah Normal & Kasus Ekstrem
        $assetLong = HomeAsset::create([
            'user_id' => $user->id,
            'name' => 'Pendingin Ruangan Central Daikin Inverter Multi-Split VRV 10 PK Ruang Tamu Utama dan Koridor Barat',
            'room' => 'Ruang Tamu Utama dan Koridor Barat Laut Sayap Kiri',
            'category' => 'pendingin',
            'condition' => 'rusak',
            'condition_source' => 'damage',
            'brand' => 'Daikin Super Multi NX Series Inverter Technology',
            'model' => 'VRV-IV-S-RXYMQ10AB-SUPER-DELUXE-2024',
            'purchased_at' => Carbon::now()->subYears(3)->toDateString(),
            'notes' => 'Unit pendingin sentral utama rumah. Sering mengalami kendala kompresor mati saat cuaca terik di siang hari.',
        ]);

        $assetNormal = HomeAsset::create([
            'user_id' => $user->id,
            'name' => 'Pompa Air Jet Pump Pendorong',
            'room' => 'Taman Belakang & Ruang Pompa',
            'category' => 'air',
            'condition' => 'normal',
            'brand' => 'Grundfos',
            'model' => 'JP-Basic-4',
            'purchased_at' => Carbon::now()->subYear()->toDateString(),
        ]);

        // Kendaraan Aktif (Model Katalog)
        $model = VehicleModel::first();
        $vehicleActive = Vehicle::create([
            'user_id' => $user->id,
            'vehicle_model_id' => $model?->id,
            'plate' => 'B 1234 SUPERLONG',
            'year' => 2023,
            'odometer' => 45850,
            'color_key' => 'hitam',
            'fuel_type' => 'Pertamax Turbo (RON 98)',
            'workshop' => 'Bengkel Resmi Toyota Auto2000 Platinum Service Center BSD Tangerang',
        ]);

        // Kendaraan Aktif Kustom dengan Nama Sangat Panjang
        $vehicleCustom = Vehicle::create([
            'user_id' => $user->id,
            'vehicle_model_id' => null,
            'custom_model_name' => 'Harley Davidson Electra Glide Ultra Classic CVO Limited 117ci Twin-Cooled',
            'custom_body_type' => 'motor_sport',
            'plate' => 'DK 9999 HDV',
            'year' => 2021,
            'odometer' => 12500,
            'color_key' => 'merah',
            'fuel_type' => 'Pertamax Turbo',
            'workshop' => 'Bikers Brotherhood Custom Garage Sanur Bali',
        ]);

        // Kendaraan Terarsip
        Vehicle::create([
            'user_id' => $user->id,
            'vehicle_model_id' => $model?->id,
            'plate' => 'B 404 ARS',
            'year' => 2018,
            'odometer' => 120000,
            'color_key' => 'silver',
            'fuel_type' => 'Solar Dexlite',
            'archived_at' => Carbon::now()->subMonths(3),
            'archive_reason' => 'dijual',
        ]);

        // Jadwal Perawatan Beragam Status
        MaintenanceSchedule::create([
            'user_id' => $user->id,
            'subject_type' => Vehicle::class,
            'subject_id' => $vehicleActive->id,
            'title' => 'Ganti Oli Mesin Sintetik Penuh & Penggantian Filter Oli Original Auto2000',
            'trigger_type' => 'kilometer',
            'interval_km' => 10000,
            'last_done_km' => 35000,
            'next_due_km' => 45000, // Odo 45850 -> Terlambat 850 km!
            'priority' => 'darurat',
            'is_active' => true,
            'notes' => 'Wajib ganti sebelum akhir pekan untuk perjalanan dinas keluar kota.',
        ]);

        MaintenanceSchedule::create([
            'user_id' => $user->id,
            'subject_type' => HomeAsset::class,
            'subject_id' => $assetLong->id,
            'title' => 'Cuci AC & Pembersihan Evaporator Kimiawi Khusus Ruang Tamu Utama',
            'trigger_type' => 'interval_hari',
            'interval_days' => 90,
            'next_due_date' => Carbon::today()->addDays(2)->toDateString(), // Mendesak
            'priority' => 'mendesak',
            'is_active' => true,
        ]);

        MaintenanceSchedule::create([
            'user_id' => $user->id,
            'subject_type' => HomeAsset::class,
            'subject_id' => $assetNormal->id,
            'title' => 'Pengecekan Tekanan Tabung & Otomatis Pompa',
            'trigger_type' => 'interval_hari',
            'interval_days' => 180,
            'next_due_date' => Carbon::today()->addDays(14)->toDateString(), // Perhatian
            'priority' => 'perhatian',
            'is_active' => true,
        ]);

        // Dokumen (Kedaluwarsa, Segera Habis, Aman)
        Document::create([
            'user_id' => $user->id,
            'subject_type' => Vehicle::class,
            'subject_id' => $vehicleActive->id,
            'type' => 'pajak_tahunan',
            'title' => 'Pajak STNK Tahunan Samsat Jakarta Selatan',
            'reference_number' => 'PKB-DKI-2025-9988112233',
            'cost' => 4850000,
            'expires_at' => Carbon::today()->subDays(5)->toDateString(), // Kedaluwarsa
            'notes' => 'Pajak STNK tahunan samsat keliling.',
        ]);

        Document::create([
            'user_id' => $user->id,
            'subject_type' => Vehicle::class,
            'subject_id' => $vehicleActive->id,
            'type' => 'asuransi',
            'title' => 'Polis Asuransi Garda Oto Comprehensive Plus Banjir',
            'reference_number' => 'POLIS-ALL-RISK-GARDAMOTO-9922',
            'cost' => 6500000,
            'expires_at' => Carbon::today()->addDays(10)->toDateString(), // Segera habis
            'notes' => 'Asuransi komprehensif plus huru-hara dan banjir.',
        ]);

        Document::create([
            'user_id' => $user->id,
            'subject_type' => HomeAsset::class,
            'subject_id' => $assetLong->id,
            'type' => 'garansi',
            'title' => 'Kartu Garansi Resmi Kompresor Daikin Indonesia',
            'reference_number' => 'GARANSI-KOMPRESOR-DAIKIN-5THN',
            'expires_at' => Carbon::today()->addYears(2)->toDateString(), // Aman
        ]);

        // Laporan Kerusakan
        DamageReport::create([
            'user_id' => $user->id,
            'reportable_type' => HomeAsset::class,
            'reportable_id' => $assetLong->id,
            'title' => 'Kompresor Outdoor Mengeluarkan Suara Dengung Keras dan Udara Dingin Tidak Keluar',
            'description' => 'Terjadi sejak kemarin sore saat cuaca panas terik. Kipas outdoor menyala tapi udara yang ditiupkan ke dalam ruangan hanya berupa hembusan angin biasa tanpa rasa dingin sedikitpun.',
            'severity' => 'berat',
            'status' => 'dalam_perbaikan',
            'reported_at' => Carbon::today()->subDays(3)->toDateString(),
            'notes' => 'Teknisi Daikin sudah dipanggil dan menunggu spare part modul inverter.',
        ]);

        DamageReport::create([
            'user_id' => $user->id,
            'reportable_type' => Vehicle::class,
            'reportable_id' => $vehicleActive->id,
            'title' => 'Baret Dalam di Pintu Kiri Belakang Kena Trotoar Parkir',
            'description' => 'Goresan panjang sekitar 30 cm mengenai lapisan cat dasar.',
            'severity' => 'sedang',
            'status' => 'dilaporkan',
            'reported_at' => Carbon::today()->subDays(1)->toDateString(),
        ]);

        // Riwayat Servis (35+ data untuk menguji scrolling & paginasi)
        for ($i = 1; $i <= 35; $i++) {
            MaintenanceRecord::create([
                'user_id' => $user->id,
                'subject_type' => Vehicle::class,
                'subject_id' => $vehicleActive->id,
                'done_at' => Carbon::now()->subMonths(35 - $i)->toDateString(),
                'service_type' => 'Servis Rutin Berkala Ke-'.$i.' di Bengkel Resmi',
                'parts' => 'Oli Mesin TMO Full Synthetic 4L, Filter Oli, Filter Udara, Busi Iridium, Washer Kuras',
                'labor_cost' => 250000 + ($i * 10000),
                'parts_cost' => 650000 + ($i * 15000),
                'odometer' => 10000 + ($i * 1000),
                'workshop_name' => 'Auto2000 Platinum Service Center',
                'notes' => 'Pemeriksaan 40 titik standar garansi resmi.',
            ]);
        }

        // Catatan BBM (35+ data untuk menguji grafik & tabel BBM)
        $fuelOdo = 15000;
        for ($j = 1; $j <= 35; $j++) {
            $fuelOdo += rand(350, 480);
            FuelLog::create([
                'user_id' => $user->id,
                'vehicle_id' => $vehicleActive->id,
                'filled_at' => Carbon::now()->subDays((35 - $j) * 7)->toDateString(),
                'odometer' => $fuelOdo,
                'liters' => rand(38, 45) + (rand(1, 9) / 10),
                'total_cost' => rand(550000, 720000),
                'fuel_grade' => 'Pertamax Turbo',
                'is_full_tank' => true,
                'station' => 'SPBU Pertamina 31.12345 TB Simatupang Jakarta Selatan',
                'notes' => 'Isi penuh sebelum perjalanan keluar kota.',
            ]);
        }

        // Target Anggaran Keuangan Bulanan
        Budget::updateOrCreate(
            ['user_id' => $user->id, 'category' => 'total'],
            ['monthly_amount' => 10000000]
        );
        Budget::updateOrCreate(
            ['user_id' => $user->id, 'category' => 'servis'],
            ['monthly_amount' => 3500000]
        );
        Budget::updateOrCreate(
            ['user_id' => $user->id, 'category' => 'bbm'],
            ['monthly_amount' => 2500000]
        );
        Budget::updateOrCreate(
            ['user_id' => $user->id, 'category' => 'perbaikan'],
            ['monthly_amount' => 1500000]
        );

        // Inspeksi
        $run = InspectionRun::create([
            'user_id' => $user->id,
            'inspectable_type' => Vehicle::class,
            'inspectable_id' => $vehicleActive->id,
            'template_key' => 'mobil_standar',
            'title' => 'Inspeksi Lengkap Kendaraan Sebelum Mudik Lebaran',
            'inspected_at' => Carbon::now()->subDays(7)->toDateString(),
            'notes' => 'Inspeksi mandiri 15 titik pengecekan berkala.',
        ]);

        $items = [
            ['item_label' => 'Tekanan Ban & Kondisi Alur Tapak', 'status' => 'ok', 'notes' => '32 PSI rata di semua ban'],
            ['item_label' => 'Kondisi Minyak Rem & Selang', 'status' => 'perhatian', 'notes' => 'Minyak rem mulai agak keruh'],
            ['item_label' => 'Karet Wiper & Air Pembersih Kaca', 'status' => 'bahaya', 'notes' => 'Karet wiper depan robek'],
            ['item_label' => 'Lampu Depan, Jauh, dan Sein', 'status' => 'ok', 'notes' => 'Semua menyala terang'],
        ];

        foreach ($items as $itemData) {
            InspectionRunItem::create([
                'inspection_run_id' => $run->id,
                'item_label' => $itemData['item_label'],
                'status' => $itemData['status'],
                'notes' => $itemData['notes'],
            ]);
        }
    }
}
