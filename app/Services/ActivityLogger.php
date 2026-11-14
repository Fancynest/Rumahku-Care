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
        'plate'             => 'Nomor Plat',
        'year'              => 'Tahun',
        'color_key'         => 'Warna',
        'fuel_type'         => 'Jenis BBM',
        'workshop'          => 'Bengkel Langganan',
        'odometer'          => 'Odometer (KM)',
        'custom_model_name' => 'Nama Model',
        'custom_body_type'  => 'Tipe Bodi',
        // HomeAsset
        'name'              => 'Nama Aset',
        'category'          => 'Kategori',
        'room'              => 'Lokasi/Ruangan',
        'brand'             => 'Merek',
        'model'             => 'Model',
        'condition'         => 'Kondisi',
        'notes'             => 'Catatan',
        'purchased_at'      => 'Tanggal Beli',
        'last_inspected_at' => 'Terakhir Inspeksi',
        // Schedule
        'title'             => 'Judul',
        'trigger_type'      => 'Pemicu',
        'interval_days'     => 'Interval (hari)',
        'interval_km'       => 'Interval (km)',
        'next_due_date'     => 'Jatuh Tempo',
        'next_due_km'       => 'Target KM',
        'priority'          => 'Prioritas',
        'is_active'         => 'Status Aktif',
        // DamageReport
        'status'            => 'Status',
        'technician_name'   => 'Teknisi',
        'repair_cost'       => 'Biaya Perbaikan',
        'resolved_at'       => 'Tanggal Selesai',
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

        return ActivityLog::create([
            'user_id'      => $user->id,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id'   => $subject?->getKey(),
            'event'        => $event,
            'description'  => $description,
            'properties'   => $properties ?: null,
        ]);
    }

    /**
     * Catat perubahan data (update) dengan diff old vs new.
     *
     * @param  array<string, mixed>  $old       Data sebelum update
     * @param  array<string, mixed>  $new       Data sesudah update
     * @param  array<string, string> $extraLabels  Override label tambahan
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
                    'old'   => $oldVal,
                    'new'   => $newVal,
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

        return class_basename($subject).' #'.$subject->getKey();
    }
}
