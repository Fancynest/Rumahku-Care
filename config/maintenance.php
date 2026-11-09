<?php

return [
    'attachments' => [
        'max_kb' => 5120, // 5 MB
        'max_per_object' => 5,
        'allowed_mimes' => [
            'image/jpeg',
            'image/png',
            'image/webp',
            'application/pdf',
        ],
        'allowed_extensions' => ['jpg', 'jpeg', 'png', 'webp', 'pdf'],
    ],

    'thresholds' => [
        'date' => [
            'terlambat' => 0,     // <= 0 hari
            'mendesak' => 7,      // <= 7 hari
            'perhatian' => 30,    // <= 30 hari
        ],
        'km' => [
            'terlambat' => 0,     // <= 0 KM
            'mendesak' => 200,    // <= 200 KM
            'perhatian' => 500,   // <= 500 KM
        ],
    ],

    'templates' => [
        [
            'title' => 'Cuci & Cek Filter AC',
            'category' => 'pendingin',
            'target_type' => 'home_asset',
            'trigger_type' => 'interval_hari',
            'interval_days' => 90,
            'priority' => 'perhatian',
            'notes' => 'Pembersihan filter indoor & cek pendinginan outdoor (contoh awal, sesuaikan).',
        ],
        [
            'title' => 'Kuras Toren Air',
            'category' => 'air',
            'target_type' => 'home_asset',
            'trigger_type' => 'interval_hari',
            'interval_days' => 180,
            'priority' => 'normal',
            'notes' => 'Kuras endapan lumpur dan lumut penampungan air (contoh awal, sesuaikan).',
        ],
        [
            'title' => 'Pemeriksaan Pompa Air',
            'category' => 'air',
            'target_type' => 'home_asset',
            'trigger_type' => 'interval_hari',
            'interval_days' => 90,
            'priority' => 'normal',
            'notes' => 'Periksa kebocoran seal dan tekanan otomatis (contoh awal, sesuaikan).',
        ],
        [
            'title' => 'Bersihkan Talang Air',
            'category' => 'eksterior',
            'target_type' => 'home_asset',
            'trigger_type' => 'interval_hari',
            'interval_days' => 90,
            'priority' => 'normal',
            'notes' => 'Bersihkan daun dan sampah sebelum musim hujan (contoh awal, sesuaikan).',
        ],
        [
            'title' => 'Ganti Oli Mesin Motor',
            'category' => 'kendaraan',
            'target_type' => 'vehicle',
            'trigger_type' => 'kilometer',
            'interval_km' => 3000,
            'priority' => 'mendesak',
            'notes' => 'Ganti oli mesin dan oli gardan sesuai jarak tempuh (contoh awal, sesuaikan).',
        ],
        [
            'title' => 'Ganti Oli Mesin Mobil',
            'category' => 'kendaraan',
            'target_type' => 'vehicle',
            'trigger_type' => 'kilometer',
            'interval_km' => 5000,
            'priority' => 'mendesak',
            'notes' => 'Ganti oli mesin & filter oli berkala (contoh awal, sesuaikan).',
        ],
        [
            'title' => 'Cek Tekanan & Kondisi Ban',
            'category' => 'kendaraan',
            'target_type' => 'vehicle',
            'trigger_type' => 'interval_hari',
            'interval_days' => 30,
            'priority' => 'normal',
            'notes' => 'Periksa tekanan angin dan ketebalan alur ban (contoh awal, sesuaikan).',
        ],
        [
            'title' => 'Cek APAR / Detektor Asap',
            'category' => 'keamanan',
            'target_type' => 'home_asset',
            'trigger_type' => 'interval_hari',
            'interval_days' => 180,
            'priority' => 'perhatian',
            'notes' => 'Periksa manometer tekanan dan ganti baterai sensor (contoh awal, sesuaikan).',
        ],
    ],
];
