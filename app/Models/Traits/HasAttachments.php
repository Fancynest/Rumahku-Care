<?php

namespace App\Models\Traits;

use App\Models\Attachment;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasAttachments
{
    protected static function bootHasAttachments(): void
    {
        static::deleting(function ($model): void {
            // Menggunakan each()->delete() agar hook deleting pada Attachment dipanggil dan file fisik terhapus
            $model->attachments()->each(function (Attachment $attachment): void {
                $attachment->delete();
            });
        });
    }

    /**
     * @return MorphMany<Attachment, $this>
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable')->latest();
    }

    public function primaryImage(): ?Attachment
    {
        return $this->attachments->first(fn (Attachment $att) => $att->is_image);
    }

    public function getPrimaryImageUrlAttribute(): ?string
    {
        return $this->primaryImage()?->url;
    }
}
