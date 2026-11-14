<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\NormalizesNumbers;
use App\Models\Vehicle;
use App\Models\VehicleModel;
use App\Services\ActivityLogger;
use App\Services\FuelCalculator;
use App\Services\MaintenanceRecorder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class VehicleController extends Controller
{
    use NormalizesNumbers;

    public function index(Request $request, FuelCalculator $fuelCalculator): View
    {
        $showArchived = $request->boolean('archived');
        $vehiclesQuery = $showArchived
            ? $request->user()->vehicles()->archived()
            : $request->user()->vehicles()->active();

        $vehicles = $vehiclesQuery
            ->with(['vehicleModel', 'schedules' => fn ($q) => $q->where('is_active', true)])
            ->latest()
            ->get();

        $archivedCount = $request->user()->vehicles()->archived()->count();
        $activeCount = $request->user()->vehicles()->active()->count();

        $selectedId = $request->query('v');
        $selectedVehicle = $vehicles->firstWhere('id', $selectedId) ?? $vehicles->first();

        $recentRecords = collect();
        $activeSchedules = collect();
        $fuelMetrics = [
            'logs' => collect(),
            'recent_logs' => collect(),
            'latest_km_l' => null,
            'average_5_km_l' => null,
            'cost_per_km' => null,
            'total_cost_this_month' => 0,
            'total_liters_this_month' => 0,
        ];

        if ($selectedVehicle) {
            $selectedVehicle->load([
                'attachments',
                'documents.attachments',
                'damageReports.attachments',
                'inspectionRuns.items',
            ]);

            $recentRecords = $selectedVehicle->maintenanceRecords()
                ->with('attachments')
                ->latest('done_at')
                ->latest('id')
                ->take(10)
                ->get();

            $activeSchedules = $selectedVehicle->schedules()
                ->where('is_active', true)
                ->get();

            $fuelMetrics = $fuelCalculator->calculate($selectedVehicle);
        }

        $modelsGrouped = VehicleModel::orderBy('brand')
            ->orderBy('model')
            ->get()
            ->groupBy('brand');

        $inspectionTemplates = config('maintenance.inspection_templates', []);
        $allTypes = config('maintenance.document_types', []);
        $allowedKeys = config('maintenance.allowed_document_types.vehicle', array_keys($allTypes));
        $documentTypes = array_intersect_key($allTypes, array_flip($allowedKeys));

        return view('vehicles.index', compact(
            'vehicles',
            'selectedVehicle',
            'recentRecords',
            'activeSchedules',
            'fuelMetrics',
            'modelsGrouped',
            'inspectionTemplates',
            'documentTypes',
            'showArchived',
            'archivedCount',
            'activeCount'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->normalizeRequestNumbers($request, ['odometer']);
        $this->normalizePlate($request);

        $validated = $request->validate([
            'plate' => [
                'required',
                'string',
                'max:20',
                Rule::unique('vehicles', 'plate')->where('user_id', $request->user()->id),
            ],
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

        app(ActivityLogger::class)->log(
            $request->user(),
            'vehicle.created',
            $vehicle,
            'Kendaraan '.$vehicle->name.' ('.$vehicle->plate.') didaftarkan ke garasi.',
            ['odometer' => $vehicle->odometer, 'fuel_type' => $vehicle->fuel_type],
        );

        return redirect()->route('vehicles.index', ['v' => $vehicle->id])
            ->with('success', 'Kendaraan '.$vehicle->name.' berhasil didaftarkan ke garasi.');
    }

    public function update(Request $request, Vehicle $vehicle): RedirectResponse
    {
        abort_if($vehicle->user_id !== $request->user()->id, 404);

        $this->normalizePlate($request);

        $validated = $request->validate([
            'plate' => [
                'required',
                'string',
                'max:20',
                Rule::unique('vehicles', 'plate')
                    ->where('user_id', $request->user()->id)
                    ->ignore($vehicle->id),
            ],
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
            'color_key' => ['required', 'string', 'max:30'],
            'fuel_type' => ['required', 'string', 'max:50'],
            'workshop' => ['nullable', 'string', 'max:150'],
        ]);

        $modelId = $validated['vehicle_model_id'] === 'other' ? null : (int) $validated['vehicle_model_id'];

        $newData = [
            'vehicle_model_id' => $modelId,
            'custom_model_name' => $modelId ? null : $validated['custom_model_name'],
            'custom_body_type' => $modelId ? null : $validated['custom_body_type'],
            'plate' => strtoupper($validated['plate']),
            'year' => $validated['year'] ?? null,
            'color_key' => $validated['color_key'],
            'fuel_type' => $validated['fuel_type'],
            'workshop' => $validated['workshop'] ?? null,
        ];

        $trackFields = ['plate', 'year', 'color_key', 'fuel_type', 'workshop', 'custom_model_name', 'custom_body_type'];
        $oldData = $vehicle->only($trackFields);

        $vehicle->update($newData);

        app(ActivityLogger::class)->logUpdated(
            $request->user(),
            'vehicle.updated',
            $vehicle,
            'Data kendaraan '.$vehicle->raw_name.' diperbarui.',
            $oldData,
            $vehicle->fresh()->only($trackFields),
        );

        return redirect()->route('vehicles.index', [
            'v' => $vehicle->id,
            'archived' => $vehicle->isArchived() ? 1 : null,
        ])->with('success', 'Data kendaraan '.$vehicle->raw_name.' berhasil diperbarui.');
    }

    public function archive(Request $request, Vehicle $vehicle): RedirectResponse
    {
        abort_if($vehicle->user_id !== $request->user()->id, 404);

        $validated = $request->validate([
            'archive_reason' => ['required', 'string', 'in:dijual,rusak_berat,lainnya'],
        ]);

        $vehicle->update([
            'archived_at' => now(),
            'archive_reason' => $validated['archive_reason'],
        ]);

        app(ActivityLogger::class)->log(
            $request->user(),
            'vehicle.archived',
            $vehicle,
            'Kendaraan '.$vehicle->raw_name.' diarsipkan. Alasan: '.$validated['archive_reason'].'.',
            ['archive_reason' => $validated['archive_reason']],
        );

        return redirect()->route('vehicles.index')
            ->with('success', 'Kendaraan '.$vehicle->raw_name.' berhasil diarsipkan.');
    }

    public function restore(Request $request, Vehicle $vehicle): RedirectResponse
    {
        abort_if($vehicle->user_id !== $request->user()->id, 404);

        $vehicle->update([
            'archived_at' => null,
            'archive_reason' => null,
        ]);

        app(ActivityLogger::class)->log(
            $request->user(),
            'vehicle.restored',
            $vehicle,
            'Kendaraan '.$vehicle->raw_name.' dipulihkan ke garasi aktif.',
        );

        return redirect()->route('vehicles.index', ['v' => $vehicle->id])
            ->with('success', 'Kendaraan '.$vehicle->raw_name.' berhasil dipulihkan ke garasi aktif.');
    }

    public function destroy(Request $request, Vehicle $vehicle): RedirectResponse
    {
        abort_if($vehicle->user_id !== $request->user()->id, 404);

        if (! $vehicle->isArchived()) {
            return redirect()->back()
                ->withErrors(['confirm_plate' => 'Kendaraan harus diarsipkan terlebih dahulu sebelum dapat dihapus permanen.']);
        }

        $request->validate([
            'confirm_plate' => ['required', 'string'],
        ]);

        $expected = strtoupper(preg_replace('/\s+/', '', $vehicle->plate));
        $actual = strtoupper(preg_replace('/\s+/', '', (string) $request->input('confirm_plate')));

        if ($expected !== $actual) {
            return redirect()->back()
                ->withErrors(['confirm_plate' => 'Nomor plat yang dimasukkan tidak cocok dengan plat kendaraan.']);
        }

        $name = $vehicle->raw_name;
        $plate = $vehicle->plate;
        $vehicle->delete();

        app(ActivityLogger::class)->log(
            $request->user(),
            'vehicle.deleted',
            null,
            'Kendaraan '.$name.' ('.$plate.') dihapus permanen.',
            ['subject_name' => $name.' ('.$plate.')'],
        );

        return redirect()->route('vehicles.index', ['archived' => 1])
            ->with('success', 'Kendaraan '.$name.' berhasil dihapus permanen beserta seluruh riwayatnya.');
    }

    public function updateOdometer(Request $request, Vehicle $vehicle): RedirectResponse
    {
        abort_if($vehicle->user_id !== $request->user()->id, 404);

        $this->normalizeRequestNumbers($request, integerFields: ['odometer']);

        $validated = $request->validate([
            'odometer' => ['required', 'integer', 'min:'.$vehicle->odometer],
        ], [
            'odometer.min' => 'Odometer baru tidak boleh lebih kecil dari odometer kendaraan saat ini ('.number_format($vehicle->odometer).' KM).',
        ]);

        $oldOdometer = $vehicle->odometer;

        $vehicle->update([
            'odometer' => $validated['odometer'],
        ]);

        app(ActivityLogger::class)->log(
            $request->user(),
            'vehicle.odometer_updated',
            $vehicle,
            'Odometer '.$vehicle->name.' diperbarui menjadi '.number_format($validated['odometer']).' KM.',
            ['old' => $oldOdometer, 'new' => $validated['odometer']],
        );

        return redirect()->back()
            ->with('success', 'Odometer '.$vehicle->name.' berhasil diperbarui menjadi '.number_format($vehicle->odometer).' KM.');
    }

    public function storeServiceLog(Request $request, Vehicle $vehicle, MaintenanceRecorder $recorder): RedirectResponse
    {
        abort_if($vehicle->user_id !== $request->user()->id, 404);

        if (! $request->has('done_at') && $request->has('serviced_at')) {
            $request->merge(['done_at' => $request->input('serviced_at')]);
        }

        $this->normalizeRequestNumbers($request, integerFields: ['odometer', 'labor_cost', 'parts_cost']);

        $validated = $request->validate([
            'done_at' => ['required', 'date', 'before_or_equal:today'],
            'odometer' => [
                'required',
                'integer',
                'min:0',
                $recorder->validateOdometerTimeline($vehicle, $request->input('done_at')),
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

        $schedule = ! empty($validated['schedule_id'])
            ? $vehicle->schedules()->find($validated['schedule_id'])
            : null;

        $recorder->record(
            user: $request->user(),
            subject: $vehicle,
            data: $validated,
            schedule: $schedule
        );

        app(ActivityLogger::class)->log(
            $request->user(),
            'vehicle.service_logged',
            $vehicle,
            'Catatan servis untuk '.$vehicle->name.' disimpan'.($schedule ? ' (Jadwal: '.$schedule->title.')' : '').'. Tanggal: '.$validated['done_at'].'.',
            array_filter([
                'done_at'       => $validated['done_at'],
                'odometer'      => $validated['odometer'],
                'service_type'  => $validated['service_type'] ?? null,
                'workshop_name' => $validated['workshop_name'] ?? null,
                'labor_cost'    => $validated['labor_cost'] ?? null,
                'parts_cost'    => $validated['parts_cost'] ?? null,
            ]),
        );

        return redirect()->back()
            ->with('success', 'Catatan servis untuk '.$vehicle->name.' berhasil disimpan.');
    }

    protected function normalizePlate(Request $request): void
    {
        if ($request->has('plate')) {
            $plate = trim((string) preg_replace('/\s+/', ' ', (string) $request->input('plate')));
            $request->merge([
                'plate' => strtoupper($plate),
            ]);
        }
    }
}
