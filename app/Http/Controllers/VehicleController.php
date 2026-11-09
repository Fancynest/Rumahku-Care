<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Models\VehicleModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VehicleController extends Controller
{
    public function index(Request $request): View
    {
        $vehicles = $request->user()->vehicles()
            ->with(['vehicleModel', 'schedules', 'maintenanceRecords'])
            ->latest()
            ->get();

        $selectedId = $request->query('v');
        $selectedVehicle = $vehicles->firstWhere('id', $selectedId) ?? $vehicles->first();

        $modelsGrouped = VehicleModel::orderBy('brand')
            ->orderBy('model')
            ->get()
            ->groupBy('brand');

        return view('vehicles.index', compact('vehicles', 'selectedVehicle', 'modelsGrouped'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'plate' => ['required', 'string', 'max:20'],
            'vehicle_model_id' => [
                'required',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value === 'other') {
                        return;
                    }

                    if (! is_numeric($value) || ! VehicleModel::where('id', $value)->exists()) {
                        $fail('Model kendaraan yang dipilih tidak valid.');
                    }
                },
            ],
            'custom_model_name' => ['required_if:vehicle_model_id,other', 'nullable', 'string', 'max:100'],
            'custom_body_type' => ['required_if:vehicle_model_id,other', 'nullable', 'string', 'in:motor_matic,motor_sport,hatchback,sedan,mpv,suv,pickup,van,bus,truk'],
            'year' => ['nullable', 'integer', 'min:1970', 'max:'.(date('Y') + 1)],
            'odometer' => ['required', 'integer', 'min:0'],
            'color_key' => ['required', 'string', 'max:30'],
            'fuel_type' => ['required', 'string', 'max:50'],
            'workshop' => ['nullable', 'string', 'max:150'],
        ]);

        $modelId = $validated['vehicle_model_id'] === 'other' ? null : (int) $validated['vehicle_model_id'];

        $vehicle = $request->user()->vehicles()->create([
            'vehicle_model_id' => $modelId,
            'custom_model_name' => $modelId ? null : $validated['custom_model_name'],
            'custom_body_type' => $modelId ? null : $validated['custom_body_type'],
            'plate' => strtoupper($validated['plate']),
            'year' => $validated['year'] ?? null,
            'odometer' => $validated['odometer'],
            'color_key' => $validated['color_key'],
            'fuel_type' => $validated['fuel_type'],
            'workshop' => $validated['workshop'] ?? null,
        ]);

        // Auto-create default oil change schedule for this vehicle
        $vehicle->schedules()->create([
            'user_id' => $request->user()->id,
            'title' => 'Ganti oli mesin',
            'trigger_type' => 'kilometer',
            'interval_km' => 5000,
            'next_due_km' => (int) $vehicle->odometer + 5000,
            'priority' => 'mendesak',
            'is_active' => true,
            'notes' => 'Servis oli mesin berkala sesuai jarak tempuh.',
        ]);

        return redirect()->route('vehicles.index', ['v' => $vehicle->id])
            ->with('success', 'Kendaraan '.$vehicle->name.' berhasil didaftarkan ke garasi.');
    }

    public function updateOdometer(Request $request, Vehicle $vehicle): RedirectResponse
    {
        abort_if($vehicle->user_id !== $request->user()->id, 404);

        $validated = $request->validate([
            'odometer' => ['required', 'integer', 'min:0'],
        ]);

        $vehicle->update([
            'odometer' => $validated['odometer'],
        ]);

        return redirect()->back()
            ->with('success', 'Odometer '.$vehicle->name.' berhasil diperbarui menjadi '.number_format($vehicle->odometer).' KM.');
    }

    public function storeServiceLog(Request $request, Vehicle $vehicle): RedirectResponse
    {
        abort_if($vehicle->user_id !== $request->user()->id, 404);

        $minOdometer = $vehicle->maintenanceRecords()->max('odometer') ?? $vehicle->odometer;

        $validated = $request->validate([
            'serviced_at' => ['required', 'date'],
            'odometer' => ['required', 'integer', 'min:'.$minOdometer],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $vehicle->maintenanceRecords()->create([
            'user_id' => $request->user()->id,
            'done_at' => $validated['serviced_at'],
            'odometer' => $validated['odometer'],
            'service_type' => 'Servis Berkala',
            'workshop_name' => $vehicle->workshop,
            'labor_cost' => 0,
            'parts_cost' => 0,
            'notes' => $validated['notes'],
        ]);

        if ($validated['odometer'] > $vehicle->odometer) {
            $vehicle->update(['odometer' => $validated['odometer']]);
        }

        return redirect()->back()
            ->with('success', 'Catatan servis untuk '.$vehicle->name.' berhasil disimpan.');
    }
}
