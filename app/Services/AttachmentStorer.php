<?php

namespace App\Services;

use App\Models\Attachment;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class AttachmentStorer
{
    /**
     * Dapatkan aturan validasi form untuk berkas lampiran berdasarkan config.
     *
     * @return array<int, string>
     */
    public static function validationRules(bool $required = false): array
    {
        $maxKb = (int) config('maintenance.attachments.max_kb', 5120);
        $extensions = implode(',', config('maintenance.attachments.allowed_extensions', ['jpg', 'jpeg', 'png', 'webp', 'pdf']));
        $mimes = implode(',', config('maintenance.attachments.allowed_mimes', ['image/jpeg', 'image/png', 'image/webp', 'application/pdf']));

        return [
            $required ? 'required' : 'nullable',
            'file',
            "max:{$maxKb}",
            "mimes:{$extensions}",
            "mimetypes:{$mimes}",
        ];
    }

    /**
     * Simpan lampiran secara aman pada disk privat dan rekam ke database.
     *
     * @throws ValidationException
     */
    public function store(Model $parent, UploadedFile $file, ?User $user = null, string $errorBagKey = 'file'): Attachment
    {
        $userId = $user?->id ?? auth()->id() ?? $parent->user_id;

        $maxPerObject = (int) config('maintenance.attachments.max_per_object', 5);
        if (method_exists($parent, 'attachments') && $parent->attachments()->count() >= $maxPerObject) {
            throw ValidationException::withMessages([
                $errorBagKey => "Jumlah lampiran untuk objek ini sudah mencapai batas maksimal ({$maxPerObject} berkas).",
            ]);
        }

        $maxKb = (int) config('maintenance.attachments.max_kb', 5120);
        if ($file->getSize() > $maxKb * 1024) {
            throw ValidationException::withMessages([
                $errorBagKey => "Ukuran berkas melebihi batas maksimal {$maxKb} KB.",
            ]);
        }

        $detectedMime = $file->getMimeType();
        $allowedMimes = config('maintenance.attachments.allowed_mimes', [
            'image/jpeg',
            'image/png',
            'image/webp',
            'application/pdf',
        ]);

        if (! in_array($detectedMime, $allowedMimes, true)) {
            throw ValidationException::withMessages([
                $errorBagKey => 'Format atau tipe berkas tidak diizinkan.',
            ]);
        }

        $path = $file->store("attachments/{$userId}", 'local');

        return $parent->attachments()->create([
            'user_id' => $userId,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $detectedMime,
            'size' => $file->getSize(),
        ]);
    }
}
