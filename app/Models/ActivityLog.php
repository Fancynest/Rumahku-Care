<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    public const UPDATED_AT = null; // immutable — only created_at

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'subject_type',
        'subject_id',
        'event',
        'description',
        'properties',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'properties' => 'array',
            'created_at' => 'datetime',
        ];
    }

    // ─── Relations ────────────────────────────────────────────────────────────

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    // ─── Accessors ────────────────────────────────────────────────────────────

    /**
     * Icon Bootstrap sesuai kategori event.
     */
    public function getIconAttribute(): string
    {
        return match (true) {
            str_starts_with($this->event, 'vehicle.') => 'bi-car-front',
            str_starts_with($this->event, 'home_asset.') => 'bi-grid',
            str_starts_with($this->event, 'schedule.') => 'bi-calendar-check',
            str_starts_with($this->event, 'damage_report.') => 'bi-exclamation-triangle',
            str_starts_with($this->event, 'inspection.') => 'bi-clipboard2-check',
            str_starts_with($this->event, 'fuel_log.') => 'bi-fuel-pump',
            str_starts_with($this->event, 'document.') => 'bi-file-earmark-text',
            default => 'bi-activity',
        };
    }

    /**
     * Warna chip sesuai jenis event (created/updated/deleted/dll).
     */
    public function getChipClassAttribute(): string
    {
        $suffix = last(explode('.', $this->event));

        return match ($suffix) {
            'created' => 'chip-success',
            'updated',
            'odometer_updated' => 'chip-info',
            'deleted' => 'chip-urgent',
            'archived' => 'chip-neutral',
            'restored' => 'chip-success',
            'completed',
            'service_logged' => 'chip-success',
            'status_updated' => 'chip-warning',
            default => 'chip-neutral',
        };
    }

    /**
     * Label chip yang ditampilkan ke user.
     */
    public function getEventLabelAttribute(): string
    {
        return match ($this->event) {
            'vehicle.created' => 'Kendaraan Baru',
            'vehicle.updated' => 'Data Diperbarui',
            'vehicle.odometer_updated' => 'Odometer Update',
            'vehicle.archived' => 'Diarsipkan',
            'vehicle.restored' => 'Dipulihkan',
            'vehicle.deleted' => 'Dihapus',
            'vehicle.service_logged' => 'Servis Dicatat',
            'home_asset.created' => 'Aset Baru',
            'home_asset.updated' => 'Data Diperbarui',
            'home_asset.deleted' => 'Dihapus',
            'schedule.created' => 'Jadwal Baru',
            'schedule.updated' => 'Jadwal Diperbarui',
            'schedule.completed' => 'Jadwal Selesai',
            'schedule.deleted' => 'Jadwal Dihapus',
            'damage_report.created' => 'Kerusakan Dilaporkan',
            'damage_report.status_updated' => 'Status Kerusakan',
            'damage_report.deleted' => 'Laporan Dihapus',
            'inspection.created' => 'Inspeksi Dicatat',
            'fuel_log.created' => 'BBM Dicatat',
            'fuel_log.updated' => 'BBM Diperbarui',
            'fuel_log.deleted' => 'BBM Dihapus',
            'document.created' => 'Dokumen Baru',
            'document.deleted' => 'Dokumen Dihapus',
            default => ucfirst(str_replace(['.', '_'], ' ', $this->event)),
        };
    }

    /**
     * Nama subjek yang terbaca. Fallback ke tipe jika model sudah dihapus.
     */
    public function getSubjectNameAttribute(): string
    {
        $subject = $this->subject;

        if ($subject instanceof Vehicle) {
            return $subject->raw_name.' ('.$subject->plate.')';
        }

        if ($subject instanceof HomeAsset) {
            return $subject->name;
        }

        // Subject sudah dihapus — tampilkan nama yang tersimpan di properties
        return $this->properties['subject_name'] ?? '(dihapus)';
    }

    /**
     * Apakah log ini memiliki diff perubahan.
     */
    public function hasFieldChanges(): bool
    {
        return ! empty($this->properties['changed']);
    }
}
