<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class InspectionRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'inspectable_type',
        'inspectable_id',
        'template_key',
        'title',
        'inspected_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'inspected_at' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function inspectable(): MorphTo
    {
        return $this->morphTo();
    }

    public function items(): HasMany
    {
        return $this->hasMany(InspectionRunItem::class);
    }

    public function getHasIssuesAttribute(): bool
    {
        return $this->items->contains(fn (InspectionRunItem $i) => in_array($i->status, ['perhatian', 'bermasalah']));
    }

    public function getIssuesCountAttribute(): int
    {
        return $this->items->filter(fn (InspectionRunItem $i) => in_array($i->status, ['perhatian', 'bermasalah']))->count();
    }

    public function getHasDangerAttribute(): bool
    {
        return $this->items->contains(fn (InspectionRunItem $i) => $i->status === 'bermasalah');
    }

    public function getHasProblemAttribute(): bool
    {
        return $this->items->contains(fn (InspectionRunItem $i) => $i->status === 'bermasalah');
    }

    public function getStatusLabelAttribute(): string
    {
        if ($this->has_danger) {
            return 'Ditemukan Masalah';
        }

        if ($this->has_issues) {
            return $this->issues_count.' Perlu Perhatian';
        }

        return 'Kondisi Baik (Semua OK)';
    }

    public function getStatusBadgeClassAttribute(): string
    {
        if ($this->has_danger) {
            return 'bg-danger-subtle text-danger border border-danger-subtle';
        }

        if ($this->has_issues) {
            return 'bg-warning-subtle text-warning-emphasis border border-warning-subtle';
        }

        return 'bg-success-subtle text-success border border-success-subtle';
    }
}
