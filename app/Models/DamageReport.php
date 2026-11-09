<?php

namespace App\Models;

use App\Models\Traits\HasAttachments;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class DamageReport extends Model
{
    use HasAttachments;
    use HasFactory;

    protected $fillable = [
        'user_id',
        'reportable_type',
        'reportable_id',
        'title',
        'description',
        'severity',
        'status',
        'reported_at',
        'resolved_at',
        'technician_name',
        'repair_cost',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'reported_at' => 'date',
            'resolved_at' => 'date',
            'repair_cost' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reportable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Alias untuk konsistensi akses polymorphic sebagai subject.
     */
    public function subject(): MorphTo
    {
        return $this->morphTo('reportable');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', ['dilaporkan', 'dalam_perbaikan']);
    }

    public function scopeResolved(Builder $query): Builder
    {
        return $query->where('status', 'selesai');
    }

    public function getSeverityLabelAttribute(): string
    {
        return match ($this->severity) {
            'berat' => 'Berat',
            'sedang' => 'Sedang',
            default => 'Ringan',
        };
    }

    public function getSeverityBadgeClassAttribute(): string
    {
        return match ($this->severity) {
            'berat' => 'bg-danger text-white',
            'sedang' => 'bg-warning-subtle text-warning-emphasis border border-warning',
            default => 'bg-secondary-subtle text-secondary border',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'dilaporkan' => 'Dilaporkan',
            'dalam_perbaikan' => 'Dalam Perbaikan',
            'selesai' => 'Selesai',
            default => ucfirst((string) $this->status),
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'dilaporkan' => 'bg-danger-subtle text-danger border border-danger-subtle',
            'dalam_perbaikan' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
            'selesai' => 'bg-success-subtle text-success border border-success-subtle',
            default => 'bg-light text-dark border',
        };
    }

    public function getSubjectNameAttribute(): string
    {
        $rep = $this->reportable ?? $this->subject;
        if (! $rep) {
            return 'Aset';
        }

        if ($rep instanceof Vehicle) {
            $brand = $rep->vehicleModel ? ($rep->vehicleModel->brand.' '.$rep->vehicleModel->model) : 'Kendaraan';

            return "{$brand} ({$rep->plate})";
        }

        if ($rep instanceof HomeAsset) {
            return $rep->name.($rep->room ? " - {$rep->room}" : '');
        }

        return (string) ($rep->name ?? 'Aset');
    }
}
