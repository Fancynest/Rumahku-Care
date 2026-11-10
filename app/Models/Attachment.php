<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class Attachment extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'attachable_type',
        'attachable_id',
        'path',
        'original_name',
        'mime',
        'size',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'size' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (Attachment $attachment): void {
            $path = $attachment->path;
            if ($path) {
                if (DB::transactionLevel() > 0) {
                    DB::afterCommit(function () use ($path): void {
                        if (Storage::disk('local')->exists($path)) {
                            Storage::disk('local')->delete($path);
                        }
                    });
                } else {
                    if (Storage::disk('local')->exists($path)) {
                        Storage::disk('local')->delete($path);
                    }
                }
            }
        });
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
    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getIsImageAttribute(): bool
    {
        return str_starts_with($this->mime ?? '', 'image/');
    }

    public function getIsPdfAttribute(): bool
    {
        return ($this->mime ?? '') === 'application/pdf';
    }

    public function getFormattedSizeAttribute(): string
    {
        $bytes = (int) $this->size;
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1).' MB';
        }

        return number_format(max(1, $bytes / 1024)).' KB';
    }

    public function getUrlAttribute(): string
    {
        return route('attachments.show', $this->id);
    }
}
