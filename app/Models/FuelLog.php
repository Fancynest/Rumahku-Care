<?php

namespace App\Models;

use App\Models\Traits\HasAttachments;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FuelLog extends Model
{
    use HasAttachments;
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'vehicle_id',
        'filled_at',
        'odometer',
        'liters',
        'total_cost',
        'fuel_grade',
        'is_full_tank',
        'station',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'vehicle_id' => 'integer',
            'filled_at' => 'date',
            'odometer' => 'integer',
            'liters' => 'decimal:2',
            'total_cost' => 'integer',
            'is_full_tank' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Vehicle, $this>
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getPricePerLiterAttribute(): int
    {
        $liters = (float) $this->liters;

        if ($liters <= 0) {
            return 0;
        }

        return (int) round($this->total_cost / $liters);
    }

    public function getFormattedTotalCostAttribute(): string
    {
        return 'Rp '.number_format($this->total_cost, 0, ',', '.');
    }

    public function getFormattedPricePerLiterAttribute(): string
    {
        return 'Rp '.number_format($this->price_per_liter, 0, ',', '.');
    }
}
