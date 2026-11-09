<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        ];
    }

    /**
     * @return BelongsTo<VehicleModel, $this>
     */
    public function vehicleModel(): BelongsTo
    {
        return $this->belongsTo(VehicleModel::class);
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
        $rem = $this->odometer % 5000;
        if ($this->odometer > 0 && ($rem >= 4800 || $rem <= 200)) {
            return 'Waktunya Servis';
        }

        return 'Kondisi Prima';
    }

    public function getStatusChipClassAttribute(): string
    {
        return $this->status_label === 'Waktunya Servis' ? 'chip-urgent' : 'chip-success';
    }
}
