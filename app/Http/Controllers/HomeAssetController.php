<?php

namespace App\Http\Controllers;

use App\Models\HomeAsset;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeAssetController extends Controller
{
    public function index(Request $request): View
    {
        $assets = $request->user()->homeAssets()->with('attachments')->latest()->get();

        $countNormal = $assets->where('condition', 'normal')->count();
        $countAttention = $assets->where('condition', 'perlu_perhatian')->count();
        $countBroken = $assets->where('condition', 'rusak')->count();

        return view('assets.index', compact(
            'assets',
            'countNormal',
            'countAttention',
            'countBroken'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'category' => ['required', 'string', 'in:bangunan,listrik,air,pendingin,dapur,kamar_mandi,keamanan,eksterior,peralatan'],
            'room' => ['required', 'string', 'max:100'],
            'brand' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'purchased_at' => ['nullable', 'date'],
            'last_inspected_at' => ['nullable', 'date'],
            'condition' => ['required', 'string', 'in:normal,perlu_perhatian,rusak'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $validated['condition_source'] = 'manual';
        $asset = $request->user()->homeAssets()->create($validated);

        app(ActivityLogger::class)->log(
            $request->user(),
            'home_asset.created',
            $asset,
            'Aset '.$asset->name.' ('.$asset->category_label.') didaftarkan.',
            ['category' => $asset->category, 'room' => $asset->room, 'condition' => $asset->condition],
        );

        return redirect()->route('home-assets.show', $asset->id)
            ->with('success', 'Aset rumah '.$asset->name.' berhasil didaftarkan.');
    }

    public function show(Request $request, HomeAsset $homeAsset): View
    {
        abort_if($homeAsset->user_id !== $request->user()->id, 404);

        $homeAsset->load([
            'attachments',
            'schedules',
            'documents.attachments',
            'damageReports.attachments',
            'inspectionRuns.items',
        ]);

        $schedules = $homeAsset->schedules()->where('is_active', true)->latest()->get();
        $inspectionTemplates = config('maintenance.inspection_templates', []);
        $allTypes = config('maintenance.document_types', []);
        $allowedKeys = config('maintenance.allowed_document_types.home_asset', array_keys($allTypes));
        $documentTypes = array_intersect_key($allTypes, array_flip($allowedKeys));

        return view('assets.show', compact('homeAsset', 'schedules', 'inspectionTemplates', 'documentTypes'));
    }

    public function update(Request $request, HomeAsset $homeAsset): RedirectResponse
    {
        abort_if($homeAsset->user_id !== $request->user()->id, 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'category' => ['required', 'string', 'in:bangunan,listrik,air,pendingin,dapur,kamar_mandi,keamanan,eksterior,peralatan'],
            'room' => ['required', 'string', 'max:100'],
            'brand' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'purchased_at' => ['nullable', 'date'],
            'last_inspected_at' => ['nullable', 'date'],
            'condition' => ['required', 'string', 'in:normal,perlu_perhatian,rusak'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $trackFields = ['name', 'category', 'room', 'brand', 'model', 'condition', 'notes', 'purchased_at', 'last_inspected_at'];
        $oldData = $homeAsset->only($trackFields);

        $validated['condition_source'] = 'manual';
        $homeAsset->update($validated);

        app(ActivityLogger::class)->logUpdated(
            $request->user(),
            'home_asset.updated',
            $homeAsset,
            'Data aset '.$homeAsset->name.' diperbarui.',
            $oldData,
            $homeAsset->fresh()->only($trackFields),
        );

        return redirect()->back()
            ->with('success', 'Data aset '.$homeAsset->name.' berhasil diperbarui.');
    }

    public function destroy(Request $request, HomeAsset $homeAsset): RedirectResponse
    {
        abort_if($homeAsset->user_id !== $request->user()->id, 404);

        $name = $homeAsset->name;
        $homeAsset->delete();

        app(ActivityLogger::class)->log(
            $request->user(),
            'home_asset.deleted',
            null,
            'Aset '.$name.' dihapus permanen.',
            ['subject_name' => $name],
        );

        return redirect()->route('home-assets.index')
            ->with('success', 'Aset '.$name.' berhasil dihapus.');
    }
}
