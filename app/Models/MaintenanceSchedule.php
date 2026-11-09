<?php

namespace App\Models;

use App\Services\ScheduleStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MaintenanceSchedule extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::saved(fn () => ScheduleStatus::clearCache());
        static::deleted(fn () => ScheduleStatus::clearCache());
    }

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'subject_type',
        'subject_id',
        'title',
        'trigger_type',
        'interval_days',
        'interval_km',
        'next_due_date',
        'next_due_km',
        'last_done_at',
        'last_done_km',
        'priority',
        'is_active',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'next_due_date' => 'date',
            'last_done_at' => 'date',
            'interval_days' => 'integer',
            'interval_km' => 'integer',
            'next_due_km' => 'integer',
            'last_done_km' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return HasMany<MaintenanceRecord, $this>
     */
    public function records(): HasMany
    {
        return $this->hasMany(MaintenanceRecord::class, 'schedule_id')->orderByDesc('done_at')->orderByDesc('id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getStatusAttribute(): string
    {
        return (new ScheduleStatus)->determine($this);
    }

    public function getStatusLabelAttribute(): string
    {
        return ScheduleStatus::label($this->status);
    }

    public function getStatusChipClassAttribute(): string
    {
        return ScheduleStatus::chipClass($this->status);
    }
}
