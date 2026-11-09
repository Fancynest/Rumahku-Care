<?php

namespace App\Models;

use App\Models\Traits\HasAttachments;
use App\Services\ScheduleStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        ];
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
        if ($this->vehicleModel) {
            return $this->vehicleModel->display_name;
        }

        return $this->custom_model_name ?? 'Kendaraan';
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
