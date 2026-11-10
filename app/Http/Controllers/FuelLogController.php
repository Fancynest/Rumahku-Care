<?php

namespace App\Http\Controllers;

use App\Models\FuelLog;
use App\Models\Vehicle;
use App\Services\AttachmentStorer;
use App\Services\OdometerTimeline;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FuelLogController extends Controller
{
    /**
     * Normalisasi input liter (koma desimal -> titik), total biaya (titik ribuan -> angka polos), dan odometer.
     */
    protected function normalizeInput(Request $request): void
    {
        $liters = $request->input('liters');
        if (is_string($liters)) {
            $liters = trim($liters);
            // Hilangkan akhiran ,- jika ada
            $liters = preg_replace('/,\s*-\s*$/', '', $liters);
            // Ganti koma dengan titik untuk desimal
            $liters = str_replace(',', '.', $liters);
            // Buang unit dan karakter selain angka dan titik desimal
            $liters = preg_replace('/[^0-9.]/', '', $liters);
            $request->merge(['liters' => $liters]);
        }

        $cost = $request->input('total_cost');
        if (is_string($cost)) {
            $cost = trim($cost);
            // Hilangkan bagian desimal/akhiran sebelum membuang pemisah ribuan (mis. ",00", ",-", ",50")
            $cost = preg_replace('/,.*$/', '', $cost);
            $cost = preg_replace('/[^0-9]/', '', $cost);
            $request->merge(['total_cost' => $cost]);
        }

        $odometer = $request->input('odometer');
        if (is_string($odometer)) {
            $odometer = trim($odometer);
            // Hilangkan bagian desimal/akhiran sebelum membuang pemisah ribuan (mis. ",00", ",-", ",50")
            $odometer = preg_replace('/,.*$/', '', $odometer);
            $odometer = preg_replace('/[^0-9]/', '', $odometer);
            $request->merge(['odometer' => $odometer]);
        }

        $request->merge([
            'is_full_tank' => $request->boolean('is_full_tank'),
        ]);
    }

    public function store(
        Request $request,
        Vehicle $vehicle,
        OdometerTimeline $timeline,
        AttachmentStorer $storer
    ): RedirectResponse {
        abort_if($vehicle->user_id !== $request->user()->id, 404);

        $this->normalizeInput($request);

        $validated = $request->validate([
            'filled_at' => ['required', 'date', 'before_or_equal:today'],
            'odometer' => [
                'required',
                'integer',
                'min:0',
                $timeline->validate($vehicle, $request->input('filled_at')),
            ],
            'liters' => ['required', 'numeric', 'min:0.01', 'max:9999.99'],
            'total_cost' => ['required', 'integer', 'min:1'],
            'fuel_grade' => ['nullable', 'string', 'max:50'],
            'is_full_tank' => ['boolean'],
            'station' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'file' => AttachmentStorer::validationRules(false),
        ], [
            'filled_at.before_or_equal' => 'Tanggal pengisian tidak boleh di masa depan.',
            'liters.required' => 'Jumlah liter BBM wajib diisi.',
            'liters.numeric' => 'Format liter BBM tidak valid.',
            'total_cost.required' => 'Total biaya BBM wajib diisi.',
        ]);

        DB::transaction(function () use ($request, $vehicle, $validated, $timeline, $storer): void {
            /** @var FuelLog $fuelLog */
            $fuelLog = $vehicle->fuelLogs()->create([
                'user_id' => $request->user()->id,
                'filled_at' => $validated['filled_at'],
                'odometer' => (int) $validated['odometer'],
                'liters' => (float) $validated['liters'],
                'total_cost' => (int) $validated['total_cost'],
                'fuel_grade' => $validated['fuel_grade'] ?? null,
                'is_full_tank' => $request->boolean('is_full_tank'),
                'station' => $validated['station'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            // Naikkan odometer kendaraan jika pembacaan ini lebih tinggi (hanya naik)
            $timeline->updateVehicleOdometerIfHigher($vehicle, (int) $validated['odometer']);

            // Simpan lampiran nota opsional jika ada
            if ($request->hasFile('file')) {
                $storer->store($fuelLog, $request->file('file'), $request->user());
            }
        });

        return redirect()->to(route('vehicles.index', ['v' => $vehicle->id]).'#bbm')
            ->with('success', 'Catatan pengisian BBM berhasil disimpan.');
    }

    public function update(
        Request $request,
        FuelLog $fuelLog,
        OdometerTimeline $timeline
    ): RedirectResponse {
        abort_if($fuelLog->user_id !== $request->user()->id, 404);

        $vehicle = $fuelLog->vehicle;
        abort_if(! $vehicle || $vehicle->user_id !== $request->user()->id, 404);

        $this->normalizeInput($request);

        $validated = $request->validate([
            'filled_at' => ['required', 'date', 'before_or_equal:today'],
            'odometer' => [
                'required',
                'integer',
                'min:0',
                $timeline->validate($vehicle, $request->input('filled_at'), ignoreFuelLogId: $fuelLog->id),
            ],
            'liters' => ['required', 'numeric', 'min:0.01', 'max:9999.99'],
            'total_cost' => ['required', 'integer', 'min:1'],
            'fuel_grade' => ['nullable', 'string', 'max:50'],
            'is_full_tank' => ['boolean'],
            'station' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'filled_at.before_or_equal' => 'Tanggal pengisian tidak boleh di masa depan.',
            'liters.required' => 'Jumlah liter BBM wajib diisi.',
            'liters.numeric' => 'Format liter BBM tidak valid.',
            'total_cost.required' => 'Total biaya BBM wajib diisi.',
        ]);

        DB::transaction(function () use ($request, $fuelLog, $vehicle, $validated, $timeline): void {
            $fuelLog->update([
                'filled_at' => $validated['filled_at'],
                'odometer' => (int) $validated['odometer'],
                'liters' => (float) $validated['liters'],
                'total_cost' => (int) $validated['total_cost'],
                'fuel_grade' => $validated['fuel_grade'] ?? null,
                'is_full_tank' => $request->boolean('is_full_tank'),
                'station' => $validated['station'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            // Naikkan odometer kendaraan jika pembacaan ini lebih tinggi (hanya naik)
            $timeline->updateVehicleOdometerIfHigher($vehicle, (int) $validated['odometer']);
        });

        return redirect()->to(route('vehicles.index', ['v' => $vehicle->id]).'#bbm')
            ->with('success', 'Catatan pengisian BBM berhasil diperbarui.');
    }

    public function destroy(Request $request, FuelLog $fuelLog): RedirectResponse
    {
        abort_if($fuelLog->user_id !== $request->user()->id, 404);

        $vehicleId = $fuelLog->vehicle_id;
        $fuelLog->delete();

        return redirect()->to(route('vehicles.index', ['v' => $vehicleId]).'#bbm')
            ->with('success', 'Catatan pengisian BBM berhasil dihapus.');
    }
}
