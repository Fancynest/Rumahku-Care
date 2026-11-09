<?php

namespace App\Models;

use App\Models\Traits\HasAttachments;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class HomeAsset extends Model
{
    use HasAttachments;
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'category',
        'room',
        'brand',
        'model',
        'purchased_at',
        'last_inspected_at',
        'condition',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'purchased_at' => 'date',
            'last_inspected_at' => 'date',
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
     * @return MorphMany<MaintenanceRecord, $this>
     */
    public function maintenanceRecords(): MorphMany
    {
        return $this->morphMany(MaintenanceRecord::class, 'subject')->orderByDesc('done_at')->orderByDesc('id');
    }

    /**
     * @return MorphMany<MaintenanceSchedule, $this>
     */
    public function schedules(): MorphMany
    {
        return $this->morphMany(MaintenanceSchedule::class, 'subject')->where('is_active', true);
    }

    public function getConditionLabelAttribute(): string
    {
        return match ($this->condition) {
            'perlu_perhatian' => 'Perlu Perhatian',
            'rusak' => 'Rusak',
            default => 'Normal',
        };
    }

    public function getConditionChipClassAttribute(): string
    {
        return match ($this->condition) {
            'perlu_perhatian' => 'chip-warning',
            'rusak' => 'chip-urgent',
            default => 'chip-success',
        };
    }

    public function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
            'kamar_mandi' => 'Kamar Mandi',
            default => ucfirst(str_replace('_', ' ', $this->category)),
        };
    }
}
