<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InspectionRunItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'inspection_run_id',
        'item_label',
        'status',
        'damage_report_id',
        'notes',
    ];

    public function inspectionRun(): BelongsTo
    {
        return $this->belongsTo(InspectionRun::class);
    }

    public function damageReport(): BelongsTo
    {
        return $this->belongsTo(DamageReport::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'bermasalah' => 'Bermasalah',
            'perhatian' => 'Perlu Perhatian',
            'na' => 'N/A',
            default => 'Kondisi Baik (OK)',
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'bermasalah' => 'bg-danger text-white',
            'perhatian' => 'bg-warning-subtle text-warning-emphasis border border-warning',
            'na' => 'bg-light text-muted border',
            default => 'bg-success-subtle text-success border border-success-subtle',
        };
    }
}
