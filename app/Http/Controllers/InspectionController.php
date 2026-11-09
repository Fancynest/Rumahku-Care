<?php

namespace App\Http\Controllers;

use App\Models\HomeAsset;
use App\Models\InspectionRun;
use App\Models\InspectionRunItem;
use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InspectionController extends Controller
{
    /**
     * @var array<string, class-string>
     */
    protected array $inspectableMap = [
        'vehicle' => Vehicle::class,
        'vehicles' => Vehicle::class,
        'home_asset' => HomeAsset::class,
        'home_assets' => HomeAsset::class,
        Vehicle::class => Vehicle::class,
        HomeAsset::class => HomeAsset::class,
    ];

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'inspectable_type' => ['required', 'string'],
            'inspectable_id' => ['required', 'integer'],
            'template_key' => ['nullable', 'string', 'max:50'],
            'title' => ['required', 'string', 'max:150'],
            'inspected_at' => ['required', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.label' => ['required', 'string', 'max:150'],
            'items.*.status' => ['required', 'string', 'in:ok,perhatian,bahaya'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
        ], [
            'title.required' => 'Judul inspeksi wajib diisi.',
            'inspected_at.before_or_equal' => 'Tanggal inspeksi tidak boleh di masa depan.',
            'items.required' => 'Daftar item pemeriksaan wajib diisi.',
            'items.*.status.in' => 'Status item pemeriksaan tidak valid.',
        ]);

        $parentClass = $this->inspectableMap[$validated['inspectable_type']] ?? null;
        if (! $parentClass || ! class_exists($parentClass)) {
            abort(404, 'Tipe objek tidak valid.');
        }

        $parent = $parentClass::find($validated['inspectable_id']);
        if (! $parent || (int) $parent->user_id !== (int) $request->user()->id) {
            abort(404, 'Objek tidak ditemukan atau bukan milik Anda.');
        }

        DB::transaction(function () use ($request, $parent, $validated): void {
            /** @var InspectionRun $run */
            $run = $parent->inspectionRuns()->create([
                'user_id' => $request->user()->id,
                'template_key' => $validated['template_key'] ?? null,
                'title' => $validated['title'],
                'inspected_at' => $validated['inspected_at'],
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($validated['items'] as $itemData) {
                $run->items()->create([
                    'item_label' => $itemData['label'],
                    'status' => $itemData['status'],
                    'notes' => $itemData['notes'] ?? null,
                ]);
            }

            if ($parent instanceof HomeAsset) {
                $parent->update(['last_inspected_at' => $validated['inspected_at']]);
            }
        });

        return redirect()->back()
            ->with('success', 'Inspeksi "'.$validated['title'].'" berhasil dicatat.');
    }

    public function convertIssueToDamageReport(Request $request, InspectionRunItem $item): RedirectResponse
    {
        $run = $item->inspectionRun;
        if (! $run || (int) $run->user_id !== (int) $request->user()->id) {
            abort(404, 'Item inspeksi tidak ditemukan atau bukan milik Anda.');
        }

        $parent = $run->inspectable;
        if (! $parent) {
            abort(404, 'Objek induk inspeksi tidak ditemukan.');
        }

        $severity = $item->status === 'bahaya' ? 'berat' : 'sedang';

        DB::transaction(function () use ($request, $parent, $run, $item, $severity): void {
            $notesPart = $item->notes ? "\nCatatan temuan: {$item->notes}" : '';

            $parent->damageReports()->create([
                'user_id' => $request->user()->id,
                'title' => 'Temuan: '.$item->item_label,
                'description' => "Ditemukan pada inspeksi '{$run->title}' tanggal ".$run->inspected_at->format('d/m/Y').".{$notesPart}",
                'severity' => $severity,
                'status' => 'dilaporkan',
                'reported_at' => $run->inspected_at->toDateString(),
            ]);

            if ($parent instanceof HomeAsset) {
                $parent->recalculateCondition();
            }
        });

        return redirect()->back()
            ->with('success', 'Temuan "'.$item->item_label.'" berhasil dikonversi menjadi laporan kerusakan.');
    }
}
