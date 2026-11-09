<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Vehicle extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'vehicle_model_id',
        'custom_model_name',
        'custom_body_type',
        'plate',
        'year',
        'color_key',
        'odometer',
        'service_interval_km',
        'fuel_type',
        'workshop',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'odometer' => 'integer',
            'service_interval_km' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<VehicleModel, $this>
     */
    public function vehicleModel(): BelongsTo
    {
        return $this->belongsTo(VehicleModel::class);
    }

    /**
     * @return HasMany<ServiceLog, $this>
     */
    public function serviceLogs(): HasMany
    {
        return $this->hasMany(ServiceLog::class)->orderByDesc('serviced_at')->orderByDesc('id');
    }

    /**
     * @return HasOne<ServiceLog, $this>
     */
    public function latestServiceLog(): HasOne
    {
        return $this->hasOne(ServiceLog::class)->latestOfMany();
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
        $latest = $this->latestServiceLog;
        if (! $latest) {
            return 'Belum ada catatan servis';
        }

        $interval = $this->service_interval_km ?: 5000;
        if (($this->odometer - $latest->odometer) >= ($interval - 200)) {
            return 'Waktunya Servis';
        }

        return 'Kondisi Prima';
    }

    public function getStatusChipClassAttribute(): string
    {
        return match ($this->status_label) {
            'Waktunya Servis' => 'chip-urgent',
            'Belum ada catatan servis' => 'chip-neutral',
            default => 'chip-success',
        };
    }
}
