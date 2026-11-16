<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\HomeAsset;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Model;

/**
 * ActivityLogger — central service untuk mencatat semua aktivitas user.
 *
 * Usage:
 *   $logger->log($user, 'vehicle.created', $vehicle, 'Kendaraan Honda Beat didaftarkan');
 *   $logger->logUpdated($user, $vehicle, $old, $new, $labelMap);
 */
class ActivityLogger
{
    /**
     * Label field yang ditampilkan saat ada perubahan data.
     */
    private const FIELD_LABELS = [
        // Vehicle
        'plate' => 'Nomor Plat',
        'year' => 'Tahun',
        'color_key' => 'Warna',
        'fuel_type' => 'Jenis BBM',
        'workshop' => 'Bengkel Langganan',
        'odometer' => 'Odometer (KM)',
        'custom_model_name' => 'Nama Model',
        'custom_body_type' => 'Tipe Bodi',
        // HomeAsset
        'name' => 'Nama Aset',
        'category' => 'Kategori',
        'room' => 'Lokasi/Ruangan',
        'brand' => 'Merek',
        'model' => 'Model',
        'condition' => 'Kondisi',
        'notes' => 'Catatan',
        'purchased_at' => 'Tanggal Beli',
        'last_inspected_at' => 'Terakhir Inspeksi',
        // Schedule
        'title' => 'Judul',
        'trigger_type' => 'Pemicu',
        'interval_days' => 'Interval (hari)',
        'interval_km' => 'Interval (km)',
        'next_due_date' => 'Jatuh Tempo',
        'next_due_km' => 'Target KM',
        'priority' => 'Prioritas',
        'is_active' => 'Status Aktif',
        // DamageReport
<<<<<<< HEAD
        'status'            => 'Status',
        'technician_name'   => 'Teknisi',
        'repair_cost'       => 'Biaya Perbaikan',
        'resolved_at'       => 'Tanggal Selesai',
        // User / Profile
        'email'             => 'Alamat Email',
=======
        'status' => 'Status',
        'technician_name' => 'Teknisi',
        'repair_cost' => 'Biaya Perbaikan',
        'resolved_at' => 'Tanggal Selesai',
>>>>>>> upstream/main
    ];

    // ─── Core ────────────────────────────────────────────────────────────────

    /**
     * Catat satu aktivitas sederhana.
     *
     * @param  array<string, mixed>  $properties
     */
    public function log(
        User $user,
        string $event,
        ?Model $subject,
        string $description,
        array $properties = [],
    ): ActivityLog {
        // Simpan nama subjek agar tetap terbaca meski record dihapus nanti
        if ($subject !== null && empty($properties['subject_name'])) {
            $properties['subject_name'] = $this->resolveSubjectName($subject);
        }

        // Otomatis tambahkan data IP & perangkat jika ada request aktif
        if (request()) {
            if (!isset($properties['ip_address'])) {
                $properties['ip_address'] = request()->ip() ?? '127.0.0.1';
            }
            if (!isset($properties['device'])) {
                $properties['device'] = self::parseUserAgent(request()->userAgent());
            }
        }

        return ActivityLog::create([
            'user_id' => $user->id,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject?->getKey(),
            'event' => $event,
            'description' => $description,
            'properties' => $properties ?: null,
        ]);
    }

    /**
     * Catat perubahan data (update) dengan diff old vs new.
     *
     * @param  array<string, mixed>  $old  Data sebelum update
     * @param  array<string, mixed>  $new  Data sesudah update
     * @param  array<string, string>  $extraLabels  Override label tambahan
     */
    public function logUpdated(
        User $user,
        string $event,
        Model $subject,
        string $description,
        array $old,
        array $new,
        array $extraLabels = [],
    ): ?ActivityLog {
        $labels = array_merge(self::FIELD_LABELS, $extraLabels);
        $changed = [];

        foreach ($new as $key => $newVal) {
            $oldVal = $old[$key] ?? null;
            // Normalize untuk perbandingan
            $oldNorm = is_bool($oldVal) ? (int) $oldVal : $oldVal;
            $newNorm = is_bool($newVal) ? (int) $newVal : $newVal;

            if ((string) $oldNorm !== (string) $newNorm) {
                $changed[$key] = [
                    'label' => $labels[$key] ?? ucfirst(str_replace('_', ' ', $key)),
                    'old' => $oldVal,
                    'new' => $newVal,
                ];
            }
        }

        // Tidak ada yang berubah — skip logging
        if (empty($changed)) {
            return null;
        }

        return $this->log($user, $event, $subject, $description, [
            'changed' => $changed,
        ]);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function resolveSubjectName(Model $subject): string
    {
        if ($subject instanceof Vehicle) {
            return $subject->raw_name.' ('.$subject->plate.')';
        }

        if ($subject instanceof HomeAsset) {
            return $subject->name;
        }

        if ($subject instanceof User) {
            return $subject->name.' (Akun)';
        }

        return class_basename($subject).' #'.$subject->getKey();
    }

    /**
     * Parse User-Agent string menjadi format ringkas dan mudah dibaca (mis: Chrome (Windows 10/11)).
     */
    public static function parseUserAgent(?string $userAgent): string
    {
        if (!$userAgent) {
            return 'Perangkat tidak dikenal';
        }

        // Deteksi Sistem Operasi
        $os = 'OS Lainnya';
        if (preg_match('/windows nt 10/i', $userAgent)) {
            $os = 'Windows 10/11';
        } elseif (preg_match('/windows/i', $userAgent)) {
            $os = 'Windows';
        } elseif (preg_match('/iphone/i', $userAgent)) {
            $os = 'iPhone (iOS)';
        } elseif (preg_match('/ipad/i', $userAgent)) {
            $os = 'iPad (iPadOS)';
        } elseif (preg_match('/android/i', $userAgent)) {
            $os = 'Android';
        } elseif (preg_match('/macintosh|mac os x/i', $userAgent)) {
            $os = 'macOS';
        } elseif (preg_match('/linux/i', $userAgent)) {
            $os = 'Linux';
        }

        // Deteksi Browser
        $browser = 'Browser';
        if (preg_match('/edg/i', $userAgent)) {
            $browser = 'Edge';
        } elseif (preg_match('/chrome|crios/i', $userAgent) && !preg_match('/opr|opera/i', $userAgent)) {
            $browser = 'Chrome';
        } elseif (preg_match('/firefox|fxios/i', $userAgent)) {
            $browser = 'Firefox';
        } elseif (preg_match('/safari/i', $userAgent) && !preg_match('/chrome|crios/i', $userAgent)) {
            $browser = 'Safari';
        } elseif (preg_match('/opr|opera/i', $userAgent)) {
            $browser = 'Opera';
        }

        return "{$browser} ({$os})";
    }
}
