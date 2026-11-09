<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Budget extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'category',
        'monthly_amount',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'monthly_amount' => 'integer',
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
     * Hitung status anggaran berdasarkan pengeluaran aktual:
     * - Aman: < 80%
     * - Hati-hati: 80% s/d 99%
     * - Melebihi: >= 100%
     *
     * @return array{
     *     spent: int,
     *     budget: int,
     *     percentage: float,
     *     status: 'aman'|'hati_hati'|'melebihi',
     *     label: string,
     *     chip_class: string
     * }
     */
    public function calculateProgress(int $spent): array
    {
        $budget = (int) $this->monthly_amount;
        $percentage = $budget > 0 ? round(($spent / $budget) * 100, 1) : 0;

        if ($percentage < 80) {
            $status = 'aman';
            $label = 'Aman';
            $chipClass = 'chip-success';
        } elseif ($percentage < 100) {
            $status = 'hati_hati';
            $label = 'Hati-hati';
            $chipClass = 'chip-warning';
        } else {
            $status = 'melebihi';
            $label = 'Melebihi';
            $chipClass = 'chip-urgent';
        }

        return [
            'spent' => $spent,
            'budget' => $budget,
            'percentage' => $percentage,
            'status' => $status,
            'label' => $label,
            'chip_class' => $chipClass,
        ];
    }
}
