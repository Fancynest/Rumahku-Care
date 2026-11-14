<?php

namespace App\Http\Controllers;

use App\Models\HomeAsset;
use App\Models\InspectionRun;
use App\Models\InspectionRunItem;
use App\Models\Vehicle;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
            'items.*.status' => ['required', 'string', 'in:ok,perhatian,bermasalah,na'],
            'items.*.is_custom' => ['nullable', 'boolean'],
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

        $targetType = $parent instanceof Vehicle ? 'vehicle' : 'home_asset';
        $templateKey = $validated['template_key'] ?? null;
        $configuredTemplates = config('maintenance.inspection_templates', []);

        if ($templateKey !== null && $templateKey !== '' && $templateKey !== 'custom') {
            if (! array_key_exists($templateKey, $configuredTemplates)) {
                throw ValidationException::withMessages([
                    'template_key' => 'Template inspeksi tidak valid.',
                ]);
            }

            $template = $configuredTemplates[$templateKey];
            if (($template['target_type'] ?? '') !== $targetType) {
                throw ValidationException::withMessages([
                    'template_key' => 'Template inspeksi tidak cocok dengan jenis subjek.',
                ]);
            }

            $allowedLabels = $template['items'] ?? [];
            foreach ($validated['items'] as $index => $itemData) {
                $isCustom = ! empty($itemData['is_custom']);
                if (! $isCustom && ! in_array($itemData['label'], $allowedLabels, true)) {
                    throw ValidationException::withMessages([
                        "items.{$index}.label" => "Label item '{$itemData['label']}' tidak sesuai dengan template atau tidak ditandai sebagai custom.",
                    ]);
                }
            }
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

            $issueCount = collect($validated['items'])->where('status', '!=', 'ok')->where('status', '!=', 'na')->count();

            app(ActivityLogger::class)->log(
                $request->user(),
                'inspection.created',
                $parent,
                'Inspeksi "'.$validated['title'].'" dicatat. '.count($validated['items']).' item diperiksa'.($issueCount > 0 ? ', '.$issueCount.' ditemukan masalah' : '').'.',
                [
                    'title'       => $validated['title'],
                    'inspected_at' => $validated['inspected_at'],
                    'item_count'  => count($validated['items']),
                    'issue_count' => $issueCount,
                ],
            );
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

        if ($item->damage_report_id) {
            return redirect()->back()
                ->with('info', 'Temuan inspeksi ini sudah dikonversi menjadi laporan kerusakan.');
        }

        if (! in_array($item->status, ['perhatian', 'bermasalah'], true)) {
            return redirect()->back()
                ->with('error', 'Hanya temuan dengan status perhatian atau bermasalah yang dapat dikonversi.');
        }

        $parent = $run->inspectable;
        if (! $parent) {
            abort(404, 'Objek induk inspeksi tidak ditemukan.');
        }

        $severity = $item->status === 'bermasalah' ? 'berat' : 'sedang';

        DB::transaction(function () use ($request, $parent, $run, $item, $severity): void {
            $notesPart = $item->notes ? "\nCatatan temuan: {$item->notes}" : '';

            $report = $parent->damageReports()->create([
                'user_id' => $request->user()->id,
                'title' => 'Temuan: '.$item->item_label,
                'description' => "Ditemukan pada inspeksi '{$run->title}' tanggal ".$run->inspected_at->format('d/m/Y').".{$notesPart}",
                'severity' => $severity,
                'status' => 'dilaporkan',
                'reported_at' => $run->inspected_at->toDateString(),
            ]);

            $item->update(['damage_report_id' => $report->id]);

            if ($parent instanceof HomeAsset) {
                $parent->recalculateCondition();
            }
        });

        return redirect()->back()
            ->with('success', 'Temuan "'.$item->item_label.'" berhasil dikonversi menjadi laporan kerusakan.');
    }
}
