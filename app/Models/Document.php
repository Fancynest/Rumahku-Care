<?php

namespace App\Models;

use App\Models\Traits\HasAttachments;
use App\Services\ScheduleStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Document extends Model
{
    use HasAttachments;
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'subject_type',
        'subject_id',
        'type',
        'title',
        'reference_number',
        'issued_at',
        'expires_at',
        'cost',
        'notes',
        'archived_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'cost' => 'integer',
            'issued_at' => 'date',
            'expires_at' => 'date',
            'archived_at' => 'datetime',
        ];
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('archived_at');
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeArchived(Builder $query): Builder
    {
        return $query->whereNotNull('archived_at');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function getStatusAttribute(): ?string
    {
        return app(ScheduleStatus::class)->determineDocument($this);
    }

    public function getStatusLabelAttribute(): ?string
    {
        return ScheduleStatus::documentLabel($this->status);
    }

    public function getStatusChipClassAttribute(): string
    {
        return ScheduleStatus::documentChipClass($this->status);
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'pajak_tahunan' => 'Pajak Tahunan (PKB)',
            'stnk' => 'STNK / Plat 5 Tahunan',
            'asuransi' => 'Polis Asuransi',
            'garansi' => 'Kartu Garansi',
            'nota_pembelian' => 'Nota Pembelian',
            'manual' => 'Buku Manual',
            default => 'Dokumen / Lainnya',
        };
    }

    public function getFormattedCostAttribute(): ?string
    {
        if ($this->cost === null) {
            return null;
        }

        return 'Rp '.number_format($this->cost, 0, ',', '.');
    }

    public function getDueLabelAttribute(): string
    {
        if ($this->expires_at === null) {
            return 'Tidak ada batas waktu';
        }

        $today = Carbon::today();
        $diff = (int) $today->diffInDays($this->expires_at->startOfDay(), false);

        if ($diff < 0) {
            return 'Kedaluwarsa '.abs($diff).' hari lalu';
        }

        if ($diff === 0) {
            return 'Jatuh tempo hari ini';
        }

        if ($diff <= 30) {
            return 'Sisa '.$diff.' hari';
        }

        $months = (int) round($diff / 30);

        return 'Sisa '.max(1, $months).' bulan';
    }

    public function getSubjectNameAttribute(): string
    {
        if ($this->subject instanceof Vehicle) {
            return $this->subject->name.' ('.$this->subject->plate.')';
        }

        if ($this->subject instanceof HomeAsset) {
            return $this->subject->name.' • '.$this->subject->room;
        }

        return 'Aset';
    }

    public function getIsArchivedAttribute(): bool
    {
        return $this->archived_at !== null;
    }
}
