<?php

namespace App\Models;

use App\Models\Traits\HasAttachments;
use App\Services\ScheduleStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Vehicle extends Model
{
    use HasAttachments;
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'vehicle_model_id',
        'custom_model_name',
        'custom_body_type',
        'plate',
        'year',
        'color_key',
        'odometer',
        'fuel_type',
        'workshop',
        'archived_at',
        'archive_reason',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'year' => 'integer',
            'odometer' => 'integer',
            'archived_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (Vehicle $vehicle): void {
            $vehicle->allSchedules()->each(fn ($s) => $s->delete());
            $vehicle->maintenanceRecords()->each(fn ($m) => $m->delete());
            $vehicle->fuelLogs()->each(fn ($f) => $f->delete());
            $vehicle->damageReports()->each(fn ($d) => $d->delete());
            $vehicle->inspectionRuns()->each(fn ($i) => $i->delete());
            $vehicle->documents()->each(fn ($doc) => $doc->delete());
        });
    }

    public function scopeActive($query)
    {
        return $query->whereNull('archived_at');
    }

    public function scopeArchived($query)
    {
        return $query->whereNotNull('archived_at');
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    /**
     * @return MorphMany<MaintenanceSchedule, $this>
     */
    public function allSchedules(): MorphMany
    {
        return $this->morphMany(MaintenanceSchedule::class, 'subject');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<VehicleModel, $this>
     */
    public function vehicleModel(): BelongsTo
    {
        return $this->belongsTo(VehicleModel::class);
    }

    /**
     * @return MorphMany<MaintenanceRecord, $this>
     */
    public function maintenanceRecords(): MorphMany
    {
        return $this->morphMany(MaintenanceRecord::class, 'subject')->orderByDesc('done_at')->orderByDesc('id');
    }

    /**
     * @return MorphOne<MaintenanceRecord, $this>
     */
    public function latestMaintenanceRecord(): MorphOne
    {
        return $this->morphOne(MaintenanceRecord::class, 'subject')->latestOfMany('done_at');
    }

    /**
     * @return HasMany<FuelLog, $this>
     */
    public function fuelLogs(): HasMany
    {
        return $this->hasMany(FuelLog::class)->orderByDesc('filled_at')->orderByDesc('id');
    }

    /**
     * @return MorphMany<MaintenanceSchedule, $this>
     */
    public function schedules(): MorphMany
    {
        return $this->morphMany(MaintenanceSchedule::class, 'subject')->where('is_active', true);
    }

    /**
     * @return MorphMany<Document, $this>
     */
    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'subject')->latest();
    }

    /**
     * @return MorphMany<DamageReport, $this>
     */
    public function damageReports(): MorphMany
    {
        return $this->morphMany(DamageReport::class, 'reportable')->orderByDesc('reported_at')->orderByDesc('id');
    }

    /**
     * @return MorphMany<InspectionRun, $this>
     */
    public function inspectionRuns(): MorphMany
    {
        return $this->morphMany(InspectionRun::class, 'inspectable')->orderByDesc('inspected_at')->orderByDesc('id');
    }

    public function getNameAttribute(): string
    {
        $base = $this->vehicleModel
            ? $this->vehicleModel->display_name
            : ($this->custom_model_name ?? 'Kendaraan');

        return $this->archived_at ? "{$base} (arsip)" : $base;
    }

    public function getRawNameAttribute(): string
    {
        return $this->vehicleModel
            ? $this->vehicleModel->display_name
            : ($this->custom_model_name ?? 'Kendaraan');
    }

    public function getBodyTypeAttribute(): string
    {
        return $this->vehicleModel?->body_type ?? $this->custom_body_type ?? 'motor_matic';
    }

    public function getStatusLabelAttribute(): string
    {
        $activeSchedules = $this->schedules;
        if ($activeSchedules->isEmpty()) {
            return 'Belum ada jadwal';
        }

        $statuses = $activeSchedules->map(fn ($s) => $s->status);
        $worst = ScheduleStatus::worst($statuses);

        return match ($worst) {
            ScheduleStatus::TERLAMBAT, ScheduleStatus::MENDESAK => 'Waktunya Servis',
            ScheduleStatus::PERHATIAN => 'Perlu Perhatian',
            default => 'Kondisi Prima',
        };
    }

    public function getStatusChipClassAttribute(): string
    {
        return match ($this->status_label) {
            'Waktunya Servis' => 'chip-urgent',
            'Perlu Perhatian' => 'chip-warning',
            'Belum ada jadwal' => 'chip-neutral',
            default => 'chip-success',
        };
    }
}
