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
        'condition_source',
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

    protected static function booted(): void
    {
        static::deleting(function (HomeAsset $asset): void {
            $asset->allSchedules()->each(fn ($s) => $s->delete());
            $asset->maintenanceRecords()->each(fn ($m) => $m->delete());
            $asset->damageReports()->each(fn ($d) => $d->delete());
            $asset->inspectionRuns()->each(fn ($i) => $i->delete());
            $asset->documents()->each(fn ($doc) => $doc->delete());
        });
    }

    /**
     * @return MorphMany<MaintenanceSchedule, $this>
     */
    public function allSchedules(): MorphMany
    {
        return $this->morphMany(MaintenanceSchedule::class, 'subject');
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

    /**
     * Sinkronkan kondisi fisik aset berdasarkan riwayat kerusakan terbuka.
     * Tidak pernah menurunkan kondisi manual pengguna.
     * Saat laporan dibuat: condition = max(condition saat ini, turunan severity).
     * Saat semua laporan selesai/dihapus: set normal HANYA jika source = damage.
     */
    public function recalculateCondition(): void
    {
        $openReports = $this->damageReports()
            ->whereIn('status', ['dilaporkan', 'dalam_perbaikan'])
            ->get();

        if ($openReports->isEmpty()) {
            if ($this->condition_source === 'damage') {
                $this->update([
                    'condition' => 'normal',
                    'condition_source' => 'manual',
                ]);
            }

            return;
        }

        $derivedCondition = $openReports->contains(fn (DamageReport $r) => $r->severity === 'berat')
            ? 'rusak'
            : 'perlu_perhatian';

        $ranks = [
            'normal' => 0,
            'perlu_perhatian' => 1,
            'rusak' => 2,
        ];

        $currentRank = $ranks[$this->condition] ?? 0;
        $derivedRank = $ranks[$derivedCondition] ?? 0;

        if ($derivedRank > $currentRank) {
            $this->update([
                'condition' => $derivedCondition,
                'condition_source' => 'damage',
            ]);
        } elseif ($this->condition_source === 'damage') {
            $this->update([
                'condition' => $derivedCondition,
            ]);
        }
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
