<?php

namespace App\Http\Controllers;

use App\Models\DamageReport;
use App\Models\HomeAsset;
use App\Models\Vehicle;
use App\Services\AttachmentStorer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DamageReportController extends Controller
{
    /**
     * @var array<string, class-string>
     */
    protected array $reportableMap = [
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
            'reportable_type' => ['required', 'string'],
            'reportable_id' => ['required', 'integer'],
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'severity' => ['required', 'string', 'in:ringan,sedang,berat'],
            'reported_at' => ['required', 'date', 'before_or_equal:today'],
            'photo' => AttachmentStorer::validationRules(false),
        ], [
            'title.required' => 'Judul kerusakan wajib diisi.',
            'severity.in' => 'Tingkat keparahan tidak valid.',
            'reported_at.before_or_equal' => 'Tanggal pelaporan tidak boleh di masa depan.',
            'photo.max' => 'Ukuran foto melebihi batas maksimal.',
            'photo.mimes' => 'Format foto harus berupa JPG, PNG, WEBP, atau PDF.',
        ]);

        $parentClass = $this->reportableMap[$validated['reportable_type']] ?? null;
        if (! $parentClass || ! class_exists($parentClass)) {
            abort(404, 'Tipe objek tidak valid.');
        }

        $parent = $parentClass::find($validated['reportable_id']);
        if (! $parent || (int) $parent->user_id !== (int) $request->user()->id) {
            abort(404, 'Objek tidak ditemukan atau bukan milik Anda.');
        }

        DB::transaction(function () use ($request, $parent, $validated, $storer): void {
            /** @var DamageReport $report */
            $report = $parent->damageReports()->create([
                'user_id' => $request->user()->id,
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'severity' => $validated['severity'],
                'status' => 'dilaporkan',
                'reported_at' => $validated['reported_at'],
            ]);

            if ($request->hasFile('photo')) {
                $storer->store($report, $request->file('photo'), $request->user(), 'photo');
            }

            if ($parent instanceof HomeAsset) {
                $parent->recalculateCondition();
            }
        });

        return redirect()->back()
            ->with('success', 'Laporan kerusakan "'.$validated['title'].'" berhasil dicatat.');
    }

    public function updateStatus(Request $request, DamageReport $damageReport, AttachmentStorer $storer): RedirectResponse
    {
        abort_if((int) $damageReport->user_id !== (int) $request->user()->id, 404);

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:dilaporkan,dalam_perbaikan,selesai'],
            'resolved_at' => ['nullable', 'required_if:status,selesai', 'date', 'before_or_equal:today'],
            'technician_name' => ['nullable', 'string', 'max:100'],
            'repair_cost' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'receipt' => AttachmentStorer::validationRules(false),
        ], [
            'status.in' => 'Status perbaikan tidak valid.',
            'resolved_at.required_if' => 'Tanggal selesai wajib diisi bila status telah selesai.',
            'resolved_at.before_or_equal' => 'Tanggal selesai tidak boleh di masa depan.',
            'receipt.max' => 'Ukuran nota/bukti melebihi batas maksimal.',
            'receipt.mimes' => 'Format berkas harus berupa JPG, PNG, WEBP, atau PDF.',
        ]);

        DB::transaction(function () use ($request, $damageReport, $validated, $storer): void {
            $dataToUpdate = [
                'status' => $validated['status'],
                'resolved_at' => $validated['status'] === 'selesai' ? ($validated['resolved_at'] ?? now()->toDateString()) : null,
                'technician_name' => $validated['technician_name'] ?? $damageReport->technician_name,
                'repair_cost' => isset($validated['repair_cost']) ? (int) $validated['repair_cost'] : $damageReport->repair_cost,
                'notes' => $validated['notes'] ?? $damageReport->notes,
            ];

            $damageReport->update($dataToUpdate);

            if ($request->hasFile('receipt')) {
                $storer->store($damageReport, $request->file('receipt'), $request->user(), 'receipt');
            }

            $parent = $damageReport->reportable;
            if ($parent instanceof HomeAsset) {
                $parent->recalculateCondition();
            }
        });

        return redirect()->back()
            ->with('success', 'Status laporan kerusakan "'.$damageReport->title.'" berhasil diperbarui.');
    }

    public function destroy(Request $request, DamageReport $damageReport): RedirectResponse
    {
        abort_if((int) $damageReport->user_id !== (int) $request->user()->id, 404);

        $parent = $damageReport->reportable;

        $damageReport->delete();

        if ($parent instanceof HomeAsset) {
            $parent->recalculateCondition();
        }

        return redirect()->back()
            ->with('success', 'Laporan kerusakan "'.$damageReport->title.'" berhasil dihapus.');
    }
}
