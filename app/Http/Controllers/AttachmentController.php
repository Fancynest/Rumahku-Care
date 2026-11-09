<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Models\DamageReport;
use App\Models\Document;
use App\Models\HomeAsset;
use App\Models\MaintenanceRecord;
use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController extends Controller
{
    /**
     * @var array<string, class-string>
     */
    protected array $morphMap = [
        'home_asset' => HomeAsset::class,
        'home_assets' => HomeAsset::class,
        'vehicle' => Vehicle::class,
        'vehicles' => Vehicle::class,
        'maintenance_record' => MaintenanceRecord::class,
        'maintenance_records' => MaintenanceRecord::class,
        'damage_report' => DamageReport::class,
        'damage_reports' => DamageReport::class,
        'document' => Document::class,
        'documents' => Document::class,
        HomeAsset::class => HomeAsset::class,
        Vehicle::class => Vehicle::class,
        MaintenanceRecord::class => MaintenanceRecord::class,
        DamageReport::class => DamageReport::class,
        Document::class => Document::class,
    ];

    public function store(Request $request): RedirectResponse
    {
        $maxKb = (int) config('maintenance.attachments.max_kb', 5120);

        $validated = $request->validate([
            'attachable_type' => ['required', 'string'],
            'attachable_id' => ['required', 'integer'],
            'file' => [
                'required',
                'file',
                'max:'.$maxKb,
                'mimes:jpg,jpeg,png,webp,pdf',
                'mimetypes:image/jpeg,image/png,image/webp,application/pdf',
            ],
        ], [
            'file.required' => 'Silakan pilih berkas lampiran yang akan diunggah.',
            'file.max' => 'Ukuran berkas melebihi batas maksimal 5 MB.',
            'file.mimes' => 'Format berkas harus berupa JPG, PNG, WEBP, atau PDF.',
            'file.mimetypes' => 'Format atau konten berkas tidak valid (hanya JPG, PNG, WEBP, atau PDF).',
        ]);

        $type = $validated['attachable_type'];
        $modelClass = $this->morphMap[$type] ?? null;

        if (! $modelClass) {
            abort(404, 'Tipe objek lampiran tidak valid.');
        }

        $parent = $modelClass::find($validated['attachable_id']);
        if (! $parent) {
            abort(404, 'Objek tujuan tidak ditemukan.');
        }

        // Cek kepemilikan objek induk oleh user yang sedang login
        if ((int) $parent->user_id !== (int) $request->user()->id) {
            abort(404, 'Anda tidak memiliki hak akses terhadap objek ini.');
        }

        // Batas maksimal 5 lampiran per objek
        $maxPerObject = (int) config('maintenance.attachments.max_per_object', 5);
        if ($parent->attachments()->count() >= $maxPerObject) {
            throw ValidationException::withMessages([
                'file' => "Jumlah lampiran untuk objek ini sudah mencapai batas maksimal ({$maxPerObject} berkas).",
            ]);
        }

        $file = $request->file('file');
        $detectedMime = $file->getMimeType();
        $allowedMimes = config('maintenance.attachments.allowed_mimes', [
            'image/jpeg',
            'image/png',
            'image/webp',
            'application/pdf',
        ]);

        if (! in_array($detectedMime, $allowedMimes, true)) {
            throw ValidationException::withMessages([
                'file' => 'Tipe konten berkas tidak diizinkan.',
            ]);
        }

        $userId = $request->user()->id;

        // Simpan ke disk 'local' di direktori khusus user dengan nama acak hashName
        $path = $file->store("attachments/{$userId}", 'local');

        $parent->attachments()->create([
            'user_id' => $userId,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $detectedMime,
            'size' => $file->getSize(),
        ]);

        return redirect()->back()->with('success', 'Lampiran berhasil diunggah.');
    }

    public function show(Request $request, Attachment $attachment): StreamedResponse
    {
        abort_if((int) $attachment->user_id !== (int) $request->user()->id, 404);
        abort_if(! Storage::disk('local')->exists($attachment->path), 404);

        $allowedMimes = config('maintenance.attachments.allowed_mimes', [
            'image/jpeg',
            'image/png',
            'image/webp',
            'application/pdf',
        ]);

        $contentType = in_array($attachment->mime, $allowedMimes, true)
            ? $attachment->mime
            : 'application/octet-stream';

        $disposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_INLINE,
            $attachment->original_name
        );

        return Storage::disk('local')->response(
            $attachment->path,
            $attachment->original_name,
            [
                'Content-Type' => $contentType,
                'Content-Disposition' => $disposition,
                'X-Content-Type-Options' => 'nosniff',
                'Content-Security-Policy' => "sandbox; default-src 'none'",
            ]
        );
    }

    public function destroy(Request $request, Attachment $attachment): RedirectResponse
    {
        abort_if((int) $attachment->user_id !== (int) $request->user()->id, 404);

        $attachment->delete();

        return redirect()->back()->with('success', 'Lampiran berhasil dihapus.');
    }
}
