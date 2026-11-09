<?php

namespace App\Http\Controllers;

use App\Models\HomeAsset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeAssetController extends Controller
{
    public function index(Request $request): View
    {
        $assets = $request->user()->homeAssets()->latest()->get();

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

        $asset = $request->user()->homeAssets()->create($validated);

        return redirect()->route('home-assets.show', $asset->id)
            ->with('success', 'Aset rumah '.$asset->name.' berhasil didaftarkan.');
    }

    public function show(Request $request, HomeAsset $homeAsset): View
    {
        abort_if($homeAsset->user_id !== $request->user()->id, 404);

        $schedules = $homeAsset->schedules()->where('is_active', true)->latest()->get();

        return view('assets.show', compact('homeAsset', 'schedules'));
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

        $homeAsset->update($validated);

        return redirect()->back()
            ->with('success', 'Data aset '.$homeAsset->name.' berhasil diperbarui.');
    }

    public function destroy(Request $request, HomeAsset $homeAsset): RedirectResponse
    {
        abort_if($homeAsset->user_id !== $request->user()->id, 404);

        $name = $homeAsset->name;
        $homeAsset->delete();

        return redirect()->route('home-assets.index')
            ->with('success', 'Aset '.$name.' berhasil dihapus.');
    }
}
