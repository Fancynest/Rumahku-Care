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
<<<<<<< HEAD
            str_starts_with($this->event, 'auth.login')     => 'bi-box-arrow-in-right',
            str_starts_with($this->event, 'auth.logout')    => 'bi-box-arrow-right',
            str_starts_with($this->event, 'auth.failed')    => 'bi-shield-x',
            str_starts_with($this->event, 'auth.password')  => 'bi-key',
            str_starts_with($this->event, 'auth.profile')   => 'bi-person-gear',
            str_starts_with($this->event, 'auth.register')  => 'bi-person-plus',
            str_starts_with($this->event, 'auth.')          => 'bi-shield-lock',
            str_starts_with($this->event, 'vehicle.')       => 'bi-car-front',
            str_starts_with($this->event, 'home_asset.')    => 'bi-grid',
            str_starts_with($this->event, 'schedule.')      => 'bi-calendar-check',
=======
            str_starts_with($this->event, 'vehicle.') => 'bi-car-front',
            str_starts_with($this->event, 'home_asset.') => 'bi-grid',
            str_starts_with($this->event, 'schedule.') => 'bi-calendar-check',
>>>>>>> upstream/main
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
<<<<<<< HEAD
        return match ($this->event) {
            'auth.login'               => 'chip-success',
            'auth.logout'              => 'chip-neutral',
            'auth.failed'              => 'chip-urgent',
            'auth.register'            => 'chip-success',
            'auth.password_updated',
            'auth.password_reset'      => 'chip-urgent',
            'auth.profile_updated'     => 'chip-info',
            default => match (last(explode('.', $this->event))) {
                'created'         => 'chip-success',
                'updated',
                'odometer_updated' => 'chip-info',
                'deleted'         => 'chip-urgent',
                'archived'        => 'chip-neutral',
                'restored'        => 'chip-success',
                'completed',
                'service_logged'  => 'chip-success',
                'status_updated'  => 'chip-warning',
                default           => 'chip-neutral',
            },
=======
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
>>>>>>> upstream/main
        };
    }

    /**
     * Label chip yang ditampilkan ke user.
     */
    public function getEventLabelAttribute(): string
    {
        return match ($this->event) {
<<<<<<< HEAD
            'auth.login'               => 'Login Berhasil',
            'auth.logout'              => 'Logout',
            'auth.failed'              => 'Login Gagal',
            'auth.register'            => 'Registrasi Akun',
            'auth.password_updated'    => 'Ubah Password',
            'auth.password_reset'      => 'Reset Password',
            'auth.profile_updated'     => 'Ubah Profil',
            'vehicle.created'          => 'Kendaraan Baru',
            'vehicle.updated'          => 'Data Diperbarui',
=======
            'vehicle.created' => 'Kendaraan Baru',
            'vehicle.updated' => 'Data Diperbarui',
>>>>>>> upstream/main
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

        if ($subject instanceof User) {
            return $subject->name.' (Akun)';
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
