<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\HomeAsset;
use App\Models\Vehicle;
use App\Services\ActivityLogger;
use App\Services\AttachmentStorer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DocumentController extends Controller
{
    /**
     * @var array<string, class-string>
     */
    protected array $subjectMap = [
        'vehicle' => Vehicle::class,
        'vehicles' => Vehicle::class,
        'home_asset' => HomeAsset::class,
        'home_assets' => HomeAsset::class,
        Vehicle::class => Vehicle::class,
        HomeAsset::class => HomeAsset::class,
    ];

    public function store(Request $request, AttachmentStorer $storer): RedirectResponse
    {
        $validated = $request->validate([
            'subject_type' => ['required', 'string'],
            'subject_id' => ['required', 'integer'],
            'type' => ['required', 'string', 'in:'.implode(',', array_keys(config('maintenance.document_types', [])))],
            'title' => ['required', 'string', 'max:150'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'issued_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:issued_at'],
            'cost' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'file' => AttachmentStorer::validationRules(false),
        ], [
            'expires_at.after_or_equal' => 'Tanggal kedaluwarsa tidak boleh lebih awal dari tanggal terbit.',
            'file.max' => 'Ukuran berkas lampiran melebihi batas maksimal.',
            'file.mimes' => 'Format berkas harus berupa JPG, PNG, WEBP, atau PDF.',
        ]);

        $subjectClass = $this->subjectMap[$validated['subject_type']] ?? null;
        if (! $subjectClass || ! class_exists($subjectClass)) {
            abort(404, 'Tipe subjek tidak valid.');
        }

        $subject = $subjectClass::find($validated['subject_id']);
        if (! $subject || (int) $subject->user_id !== (int) $request->user()->id) {
            abort(404, 'Subjek tidak ditemukan atau bukan milik Anda.');
        }

        $targetType = $subject instanceof Vehicle ? 'vehicle' : 'home_asset';
        $allowedForSubject = config("maintenance.allowed_document_types.{$targetType}", []);
        if (! in_array($validated['type'], $allowedForSubject, true)) {
            throw ValidationException::withMessages([
                'type' => 'Jenis dokumen tidak diizinkan untuk subjek ini.',
            ]);
        }

        DB::transaction(function () use ($request, $subject, $validated, $storer): void {
            $document = $subject->documents()->create([
                'user_id' => $request->user()->id,
                'type' => $validated['type'],
                'title' => $validated['title'],
                'reference_number' => $validated['reference_number'] ?? null,
                'issued_at' => $validated['issued_at'] ?? null,
                'expires_at' => $validated['expires_at'] ?? null,
                'cost' => $validated['cost'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            if ($request->hasFile('file')) {
                $storer->store($document, $request->file('file'), $request->user(), 'file');
            }

            app(ActivityLogger::class)->log(
                $request->user(),
                'document.created',
                $subject,
                'Dokumen "'.$validated['title'].'" ditambahkan.',
                array_filter([
                    'title' => $validated['title'],
                    'type' => $validated['type'],
                    'expires_at' => $validated['expires_at'] ?? null,
                    'cost' => $validated['cost'] ?? null,
                ]),
            );
        });

        return redirect()->back()
            ->with('success', 'Dokumen "'.$validated['title'].'" berhasil ditambahkan.');
    }

    public function update(Request $request, Document $document): RedirectResponse
    {
        abort_if((int) $document->user_id !== (int) $request->user()->id, 404);

        $validated = $request->validate([
            'type' => ['sometimes', 'string', 'in:'.implode(',', array_keys(config('maintenance.document_types', [])))],
            'title' => ['required', 'string', 'max:150'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'issued_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:issued_at'],
            'cost' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'expires_at.after_or_equal' => 'Tanggal kedaluwarsa tidak boleh lebih awal dari tanggal terbit.',
        ]);

        if (isset($validated['type'])) {
            $targetType = $document->subject instanceof Vehicle ? 'vehicle' : 'home_asset';
            $allowedForSubject = config("maintenance.allowed_document_types.{$targetType}", []);
            if (! in_array($validated['type'], $allowedForSubject, true)) {
                throw ValidationException::withMessages([
                    'type' => 'Jenis dokumen tidak diizinkan untuk subjek ini.',
                ]);
            }
        }

        $document->update($validated);

        return redirect()->back()
            ->with('success', 'Dokumen "'.$document->title.'" berhasil diperbarui.');
    }

    public function renew(Request $request, Document $document, AttachmentStorer $storer): RedirectResponse
    {
        abort_if((int) $document->user_id !== (int) $request->user()->id, 404);
        abort_if($document->archived_at !== null, 404, 'Dokumen yang telah diarsipkan tidak dapat diperpanjang.');

        $validated = $request->validate([
            'issued_at' => ['nullable', 'date'],
            'expires_at' => ['required', 'date', 'after_or_equal:issued_at'],
            'cost' => ['nullable', 'integer', 'min:0'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'file' => AttachmentStorer::validationRules(false),
        ], [
            'expires_at.required' => 'Tanggal kedaluwarsa baru wajib diisi untuk perpanjangan.',
            'expires_at.after_or_equal' => 'Tanggal kedaluwarsa baru tidak boleh lebih awal dari tanggal terbit.',
            'file.max' => 'Ukuran berkas lampiran melebihi batas maksimal.',
            'file.mimes' => 'Format berkas harus berupa JPG, PNG, WEBP, atau PDF.',
        ]);

        DB::transaction(function () use ($request, $document, $validated, $storer): void {
            // 1. Arsipkan dokumen lama
            $document->update(['archived_at' => now()]);

            // 2. Buat dokumen aktif yang baru
            $newDocument = Document::create([
                'user_id' => $document->user_id,
                'subject_type' => $document->subject_type,
                'subject_id' => $document->subject_id,
                'type' => $document->type,
                'title' => $document->title,
                'reference_number' => $validated['reference_number'] ?? $document->reference_number,
                'issued_at' => $validated['issued_at'] ?? now()->toDateString(),
                'expires_at' => $validated['expires_at'],
                'cost' => $validated['cost'] ?? 0,
                'notes' => $validated['notes'] ?? $document->notes,
                'archived_at' => null,
            ]);

            // 3. Simpan lampiran bila disertakan
            if ($request->hasFile('file')) {
                $storer->store($newDocument, $request->file('file'), $request->user(), 'file');
            }

            app(ActivityLogger::class)->log(
                $request->user(),
                'document.created',
                $document->subject,
                'Dokumen "'.$document->title.'" diperpanjang hingga '.($validated['expires_at'] ?? '-').'.',
                array_filter([
                    'title' => $document->title,
                    'expires_at' => $validated['expires_at'],
                    'cost' => $validated['cost'] ?? null,
                ]),
            );
        });

        return redirect()->back()
            ->with('success', 'Dokumen "'.$document->title.'" berhasil diperpanjang. Riwayat lama telah diarsipkan.');
    }

    public function destroy(Request $request, Document $document): RedirectResponse
    {
        abort_if((int) $document->user_id !== (int) $request->user()->id, 404);

        $title = $document->title;
        $subject = $document->subject;
        $document->delete();

        app(ActivityLogger::class)->log(
            $request->user(),
            'document.deleted',
            $subject,
            'Dokumen "'.$title.'" dihapus.',
            ['title' => $title],
        );

        return redirect()->back()
            ->with('success', 'Dokumen "'.$title.'" berhasil dihapus.');
    }
}
