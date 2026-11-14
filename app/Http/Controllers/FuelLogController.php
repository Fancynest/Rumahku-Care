<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\NormalizesNumbers;
use App\Models\FuelLog;
use App\Models\Vehicle;
use App\Services\ActivityLogger;
use App\Services\AttachmentStorer;
use App\Services\OdometerTimeline;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FuelLogController extends Controller
{
    use NormalizesNumbers;

    /**
     * Normalisasi input liter (koma desimal -> titik), total biaya (titik ribuan -> angka polos), dan odometer.
     */
    protected function normalizeInput(Request $request): void
    {
        $this->normalizeRequestNumbers(
            $request,
            integerFields: ['total_cost', 'odometer'],
            decimalFields: ['liters'],
        );

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

            app(ActivityLogger::class)->log(
                $request->user(),
                'fuel_log.created',
                $vehicle,
                'Pengisian BBM '.$vehicle->name.' '.number_format($validated['liters'], 1).' L (Rp '.number_format($validated['total_cost']).') pada '.$validated['filled_at'].'.',
                array_filter([
                    'filled_at' => $validated['filled_at'],
                    'odometer' => $validated['odometer'],
                    'liters' => $validated['liters'],
                    'total_cost' => $validated['total_cost'],
                    'fuel_grade' => $validated['fuel_grade'] ?? null,
                    'station' => $validated['station'] ?? null,
                ]),
            );
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

            app(ActivityLogger::class)->log(
                $request->user(),
                'fuel_log.updated',
                $vehicle,
                'Catatan BBM '.$vehicle->name.' ('.$validated['filled_at'].') diperbarui.',
                array_filter([
                    'filled_at' => $validated['filled_at'],
                    'odometer' => $validated['odometer'],
                    'liters' => $validated['liters'],
                    'total_cost' => $validated['total_cost'],
                ]),
            );
        });

        return redirect()->to(route('vehicles.index', ['v' => $vehicle->id]).'#bbm')
            ->with('success', 'Catatan pengisian BBM berhasil diperbarui.');
    }

    public function destroy(Request $request, FuelLog $fuelLog): RedirectResponse
    {
        abort_if($fuelLog->user_id !== $request->user()->id, 404);

        $vehicleId = $fuelLog->vehicle_id;
        $vehicle = $fuelLog->vehicle;
        $filledAt = $fuelLog->filled_at;
        $fuelLog->delete();

        app(ActivityLogger::class)->log(
            $request->user(),
            'fuel_log.deleted',
            $vehicle,
            'Catatan BBM '.($vehicle?->name ?? 'kendaraan').' ('.($filledAt?->format('d/m/Y') ?? '-').') dihapus.',
            ['vehicle_name' => $vehicle?->name, 'filled_at' => $filledAt?->toDateString()],
        );

        return redirect()->to(route('vehicles.index', ['v' => $vehicleId]).'#bbm')
            ->with('success', 'Catatan pengisian BBM berhasil dihapus.');
    }
}
