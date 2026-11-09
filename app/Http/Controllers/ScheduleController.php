<?php

namespace App\Http\Controllers;

use App\Models\HomeAsset;
use App\Models\MaintenanceSchedule;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    public function index(Request $request): View
    {
        $filter = $request->query('filter', 'all'); // all, vehicle, home_asset

        $query = $request->user()->schedules()
            ->with('subject')
            ->where('is_active', true)
            ->latest();

        if ($filter === 'vehicle') {
            $query->where('subject_type', Vehicle::class);
        } elseif ($filter === 'home_asset') {
            $query->where('subject_type', HomeAsset::class);
        }

        $allSchedules = $query->get();

        $grouped = [
            'terlambat' => $allSchedules->filter(fn ($s) => $s->status === 'terlambat'),
            'mendesak' => $allSchedules->filter(fn ($s) => $s->status === 'mendesak'),
            'perhatian' => $allSchedules->filter(fn ($s) => $s->status === 'perhatian'),
            'normal' => $allSchedules->filter(fn ($s) => $s->status === 'normal'),
        ];

        $vehicles = $request->user()->vehicles()->get();
        $homeAssets = $request->user()->homeAssets()->get();
        $templates = config('maintenance.templates', []);

        return view('schedules.index', compact('grouped', 'filter', 'vehicles', 'homeAssets', 'templates'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'subject_type' => ['required', 'string', 'in:App\Models\Vehicle,App\Models\HomeAsset'],
            'subject_id' => ['required', 'integer'],
            'title' => ['required', 'string', 'max:150'],
            'trigger_type' => ['required', 'string', 'in:tanggal,interval_hari,kilometer'],
            'interval_days' => ['nullable', 'required_if:trigger_type,interval_hari', 'integer', 'min:1'],
            'interval_km' => ['nullable', 'required_if:trigger_type,kilometer', 'integer', 'min:100'],
            'first_due_date' => ['nullable', 'date'],
            'priority' => ['required', 'string', 'in:normal,perhatian,mendesak,darurat'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        // Validate subject ownership
        if ($validated['subject_type'] === Vehicle::class) {
            $subject = $request->user()->vehicles()->findOrFail($validated['subject_id']);
        } else {
            $subject = $request->user()->homeAssets()->findOrFail($validated['subject_id']);
            if ($validated['trigger_type'] === 'kilometer') {
                throw ValidationException::withMessages([
                    'trigger_type' => 'Pemicu kilometer hanya dapat digunakan untuk kendaraan.',
                ]);
            }
        }

        $nextDueDate = null;
        $nextDueKm = null;

        if ($validated['trigger_type'] === 'kilometer') {
            $nextDueKm = (int) $subject->odometer + (int) $validated['interval_km'];
        } elseif ($validated['trigger_type'] === 'interval_hari') {
            $startDate = ! empty($validated['first_due_date']) ? Carbon::parse($validated['first_due_date']) : Carbon::today()->addDays((int) $validated['interval_days']);
            $nextDueDate = $startDate->toDateString();
        } else {
            $nextDueDate = $validated['first_due_date'] ?? Carbon::today()->toDateString();
        }

        $schedule = $request->user()->schedules()->create([
            'subject_type' => $validated['subject_type'],
            'subject_id' => $validated['subject_id'],
            'title' => $validated['title'],
            'trigger_type' => $validated['trigger_type'],
            'interval_days' => $validated['interval_days'] ?? null,
            'interval_km' => $validated['interval_km'] ?? null,
            'next_due_date' => $nextDueDate,
            'next_due_km' => $nextDueKm,
            'priority' => $validated['priority'],
            'is_active' => true,
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->back()
            ->with('success', 'Jadwal perawatan "'.$schedule->title.'" berhasil ditambahkan.');
    }

    public function complete(Request $request, MaintenanceSchedule $schedule): RedirectResponse
    {
        abort_if($schedule->user_id !== $request->user()->id, 404);

        $rules = [
            'done_at' => ['required', 'date'],
            'labor_cost' => ['nullable', 'integer', 'min:0'],
            'parts_cost' => ['nullable', 'integer', 'min:0'],
            'parts' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'workshop_name' => ['nullable', 'string', 'max:150'],
        ];

        if ($schedule->trigger_type === 'kilometer') {
            $minOdometer = $schedule->subject instanceof Vehicle ? $schedule->subject->odometer : 0;
            $rules['odometer'] = ['required', 'integer', 'min:'.$minOdometer];
        } else {
            $rules['odometer'] = ['nullable', 'integer', 'min:0'];
        }

        $validated = $request->validate($rules);

        DB::transaction(function () use ($schedule, $validated): void {
            $subject = $schedule->subject;

            // 1. Create MaintenanceRecord
            $schedule->records()->create([
                'user_id' => $schedule->user_id,
                'subject_type' => $schedule->subject_type,
                'subject_id' => $schedule->subject_id,
                'done_at' => $validated['done_at'],
                'odometer' => $validated['odometer'] ?? null,
                'service_type' => $schedule->title,
                'workshop_name' => $validated['workshop_name'] ?? ($subject instanceof Vehicle ? $subject->workshop : null),
                'labor_cost' => $validated['labor_cost'] ?? 0,
                'parts_cost' => $validated['parts_cost'] ?? 0,
                'parts' => $validated['parts'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            // 2. Update Schedule last_done and next_due
            $schedule->last_done_at = $validated['done_at'];

            if ($schedule->trigger_type === 'kilometer') {
                $odometer = (int) $validated['odometer'];
                $schedule->last_done_km = $odometer;
                $schedule->next_due_km = $odometer + ($schedule->interval_km ?: 5000);

                if ($subject instanceof Vehicle && $odometer > $subject->odometer) {
                    $subject->update(['odometer' => $odometer]);
                }
            } elseif ($schedule->trigger_type === 'interval_hari') {
                $days = $schedule->interval_days ?: 30;
                $schedule->next_due_date = Carbon::parse($validated['done_at'])->addDays($days)->toDateString();
            } elseif ($schedule->trigger_type === 'tanggal') {
                $schedule->is_active = false;
            }

            $schedule->save();
        });

        return redirect()->back()
            ->with('success', 'Jadwal "'.$schedule->title.'" berhasil ditandai selesai.');
    }

    public function destroy(Request $request, MaintenanceSchedule $schedule): RedirectResponse
    {
        abort_if($schedule->user_id !== $request->user()->id, 404);

        $title = $schedule->title;
        $schedule->delete();

        return redirect()->back()
            ->with('success', 'Jadwal "'.$title.'" berhasil dihapus.');
    }
}
