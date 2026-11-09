<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Models\VehicleModel;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class VehicleController extends Controller
{
    public function index(Request $request): View
    {
        $vehicles = $request->user()->vehicles()
            ->with(['vehicleModel', 'schedules' => fn ($q) => $q->where('is_active', true)])
            ->latest()
            ->get();

        $selectedId = $request->query('v');
        $selectedVehicle = $vehicles->firstWhere('id', $selectedId) ?? $vehicles->first();

        $recentRecords = collect();
        $activeSchedules = collect();

        if ($selectedVehicle) {
            $recentRecords = $selectedVehicle->maintenanceRecords()
                ->latest('done_at')
                ->latest('id')
                ->take(10)
                ->get();

            $activeSchedules = $selectedVehicle->schedules()
                ->where('is_active', true)
                ->get();
        }

        $modelsGrouped = VehicleModel::orderBy('brand')
            ->orderBy('model')
            ->get()
            ->groupBy('brand');

        return view('vehicles.index', compact(
            'vehicles',
            'selectedVehicle',
            'recentRecords',
            'activeSchedules',
            'modelsGrouped'
        ));
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
            'odometer' => ['required', 'integer', 'min:'.$vehicle->odometer],
        ], [
            'odometer.min' => 'Odometer baru tidak boleh lebih kecil dari odometer kendaraan saat ini ('.number_format($vehicle->odometer).' KM).',
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

        if (! $request->has('done_at') && $request->has('serviced_at')) {
            $request->merge(['done_at' => $request->input('serviced_at')]);
        }

        $validated = $request->validate([
            'done_at' => ['required', 'date', 'before_or_equal:today'],
            'odometer' => [
                'required',
                'integer',
                'min:0',
                function (string $attribute, mixed $value, \Closure $fail) use ($vehicle, $request): void {
                    $doneAt = $request->input('done_at') ?? $request->input('serviced_at');
                    if (! $doneAt || ! strtotime($doneAt)) {
                        return;
                    }

                    $prevRecord = $vehicle->maintenanceRecords()
                        ->whereNotNull('odometer')
                        ->whereDate('done_at', '<=', $doneAt)
                        ->orderByDesc('done_at')
                        ->orderByDesc('id')
                        ->first();

                    if ($prevRecord && (int) $value < (int) $prevRecord->odometer) {
                        $fail('Odometer tidak boleh lebih kecil dari riwayat servis sebelumnya ('.number_format($prevRecord->odometer).' KM pada '.$prevRecord->done_at->format('d/m/Y').').');

                        return;
                    }

                    $nextRecord = $vehicle->maintenanceRecords()
                        ->whereNotNull('odometer')
                        ->whereDate('done_at', '>', $doneAt)
                        ->orderBy('done_at')
                        ->orderBy('id')
                        ->first();

                    if ($nextRecord && (int) $value > (int) $nextRecord->odometer) {
                        $fail('Odometer tidak boleh lebih besar dari riwayat servis berikutnya ('.number_format($nextRecord->odometer).' KM pada '.$nextRecord->done_at->format('d/m/Y').').');
                    }
                },
            ],
            'service_type' => ['nullable', 'string', 'max:100'],
            'workshop_name' => ['nullable', 'string', 'max:150'],
            'labor_cost' => ['nullable', 'integer', 'min:0'],
            'parts_cost' => ['nullable', 'integer', 'min:0'],
            'parts' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'schedule_id' => [
                'nullable',
                function (string $attribute, mixed $value, \Closure $fail) use ($vehicle, $request): void {
                    if (empty($value)) {
                        return;
                    }
                    $exists = $vehicle->schedules()
                        ->where('id', $value)
                        ->where('user_id', $request->user()->id)
                        ->exists();
                    if (! $exists) {
                        $fail('Jadwal perawatan yang dipilih tidak valid.');
                    }
                },
            ],
        ], [
            'done_at.before_or_equal' => 'Tanggal servis tidak boleh di masa depan.',
        ]);

        DB::transaction(function () use ($request, $vehicle, $validated): void {
            $laborCost = (int) ($validated['labor_cost'] ?? 0);
            $partsCost = (int) ($validated['parts_cost'] ?? 0);
            $odometer = (int) $validated['odometer'];

            $schedule = null;
            if (! empty($validated['schedule_id'])) {
                $schedule = $vehicle->schedules()->find($validated['schedule_id']);
            }

            $vehicle->maintenanceRecords()->create([
                'user_id' => $request->user()->id,
                'schedule_id' => $schedule?->id,
                'done_at' => $validated['done_at'],
                'odometer' => $odometer,
                'service_type' => $validated['service_type'] ?? 'Servis Berkala',
                'workshop_name' => $validated['workshop_name'] ?? $vehicle->workshop,
                'labor_cost' => $laborCost,
                'parts_cost' => $partsCost,
                'parts' => $validated['parts'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            if ($schedule) {
                if ($schedule->trigger_type === 'kilometer') {
                    if ($schedule->last_done_km === null || $odometer >= $schedule->last_done_km) {
                        $schedule->last_done_km = $odometer;
                        $schedule->next_due_km = $odometer + ($schedule->interval_km ?: 5000);
                        $schedule->last_done_at = $validated['done_at'];
                    }
                } elseif ($schedule->trigger_type === 'interval_hari') {
                    if ($schedule->last_done_at === null || $validated['done_at'] >= $schedule->last_done_at->toDateString()) {
                        $days = $schedule->interval_days ?: 30;
                        $schedule->next_due_date = Carbon::parse($validated['done_at'])->addDays($days)->toDateString();
                        $schedule->last_done_at = $validated['done_at'];
                    }
                } elseif ($schedule->trigger_type === 'tanggal') {
                    $schedule->is_active = false;
                    $schedule->last_done_at = $validated['done_at'];
                }
                $schedule->save();
            }

            if ($odometer > $vehicle->odometer) {
                $vehicle->update(['odometer' => $odometer]);
            }
        });

        return redirect()->back()
            ->with('success', 'Catatan servis untuk '.$vehicle->name.' berhasil disimpan.');
    }
}
