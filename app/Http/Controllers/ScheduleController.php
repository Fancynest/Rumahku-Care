<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\NormalizesNumbers;
use App\Models\HomeAsset;
use App\Models\MaintenanceSchedule;
use App\Models\Vehicle;
use App\Services\ActivityLogger;
use App\Services\ActivityTimeline;
use App\Services\MaintenanceRecorder;
use App\Services\ScheduleCalendar;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    use NormalizesNumbers;

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

        $allSchedules = $query->get()->reject(function ($s) {
            return $s->subject instanceof Vehicle && $s->subject->archived_at !== null;
        })->values();

        $priorityRank = [
            'darurat' => 0,
            'mendesak' => 1,
            'perhatian' => 2,
            'normal' => 3,
        ];

        $grouped = [
            'terlambat' => $allSchedules->filter(fn ($s) => $s->status === 'terlambat')
                ->sortBy(fn ($s) => $priorityRank[$s->priority] ?? 99)->values(),
            'mendesak' => $allSchedules->filter(fn ($s) => $s->status === 'mendesak')
                ->sortBy(fn ($s) => $priorityRank[$s->priority] ?? 99)->values(),
            'perhatian' => $allSchedules->filter(fn ($s) => $s->status === 'perhatian')
                ->sortBy(fn ($s) => $priorityRank[$s->priority] ?? 99)->values(),
            'normal' => $allSchedules->filter(fn ($s) => $s->status === 'normal')
                ->sortBy(fn ($s) => $priorityRank[$s->priority] ?? 99)->values(),
        ];

        $vehicles = $request->user()->vehicles()->active()->get();
        $homeAssets = $request->user()->homeAssets()->get();
        $templates = config('maintenance.templates', []);

        return view('schedules.index', compact('grouped', 'filter', 'vehicles', 'homeAssets', 'templates'));
    }

    public function calendar(Request $request, ScheduleCalendar $calendarService): View
    {
        $month = $request->query('month');
        $showCompleted = filter_var($request->query('show_completed', false), FILTER_VALIDATE_BOOLEAN);

        $calendarData = $calendarService->build($request->user(), $month, $showCompleted);

        return view('schedules.calendar', $calendarData);
    }

    public function history(Request $request, ActivityTimeline $timelineService): View
    {
        $filters = [
            'type' => $request->query('type', 'all'),
            'subject' => $request->query('subject', 'all'),
            'from' => $request->query('from'),
            'to' => $request->query('to'),
            'page' => (int) $request->query('page', 1),
        ];

        $timelineData = $timelineService->build($request->user(), $filters);

        return view('schedules.history', $timelineData);
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
            $subject = $request->user()->vehicles()->active()->findOrFail($validated['subject_id']);
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

        app(ActivityLogger::class)->log(
            $request->user(),
            'schedule.created',
            $subject,
            'Jadwal "'.$schedule->title.'" ditambahkan untuk '.(method_exists($subject, 'raw_name') ? $subject->raw_name : $subject->name).'.',
            ['title' => $schedule->title, 'trigger_type' => $schedule->trigger_type, 'priority' => $schedule->priority],
        );

        return redirect()->back()
            ->with('success', 'Jadwal perawatan "'.$schedule->title.'" berhasil ditambahkan.');
    }

    public function update(Request $request, MaintenanceSchedule $schedule): RedirectResponse
    {
        abort_if($schedule->user_id !== $request->user()->id, 404);

        $subject = $schedule->subject;
        $isVehicle = $subject instanceof Vehicle;

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'trigger_type' => ['required', 'string', 'in:tanggal,interval_hari,kilometer'],
            'interval_days' => ['nullable', 'required_if:trigger_type,interval_hari', 'integer', 'min:1'],
            'interval_km' => ['nullable', 'required_if:trigger_type,kilometer', 'integer', 'min:100'],
            'next_due_date' => ['nullable', 'date'],
            'next_due_km' => ['nullable', 'integer', 'min:0'],
            'priority' => ['required', 'string', 'in:normal,perhatian,mendesak,darurat'],
            'is_active' => ['required', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        if (! $isVehicle && $validated['trigger_type'] === 'kilometer') {
            throw ValidationException::withMessages([
                'trigger_type' => 'Pemicu kilometer hanya dapat digunakan untuk kendaraan.',
            ]);
        }

        $nextDueDate = $validated['next_due_date'] ?? $schedule->next_due_date;
        $nextDueKm = $validated['next_due_km'] ?? $schedule->next_due_km;

        if ($validated['trigger_type'] === 'kilometer') {
            if (empty($nextDueKm)) {
                $nextDueKm = (int) ($subject?->odometer ?? 0) + (int) $validated['interval_km'];
            }
            $nextDueDate = null;
        } elseif ($validated['trigger_type'] === 'interval_hari') {
            if (empty($nextDueDate)) {
                $nextDueDate = Carbon::today()->addDays((int) $validated['interval_days'])->toDateString();
            }
            $nextDueKm = null;
        } else {
            $nextDueKm = null;
        }

        $trackFields = ['title', 'trigger_type', 'interval_days', 'interval_km', 'next_due_date', 'next_due_km', 'priority', 'is_active', 'notes'];
        $oldData = $schedule->only($trackFields);

        $schedule->update([
            'title' => $validated['title'],
            'trigger_type' => $validated['trigger_type'],
            'interval_days' => $validated['trigger_type'] === 'interval_hari' ? $validated['interval_days'] : null,
            'interval_km' => $validated['trigger_type'] === 'kilometer' ? $validated['interval_km'] : null,
            'next_due_date' => $nextDueDate,
            'next_due_km' => $nextDueKm,
            'priority' => $validated['priority'],
            'is_active' => (bool) $validated['is_active'],
            'notes' => $validated['notes'] ?? null,
        ]);

        app(ActivityLogger::class)->logUpdated(
            $request->user(),
            'schedule.updated',
            $subject,
            'Jadwal "'.$schedule->title.'" diperbarui.',
            $oldData,
            $schedule->fresh()->only($trackFields),
        );

        return redirect()->back()
            ->with('success', 'Jadwal "'.$schedule->title.'" berhasil diperbarui.');
    }

    public function complete(Request $request, MaintenanceSchedule $schedule, MaintenanceRecorder $recorder): RedirectResponse
    {
        abort_if($schedule->user_id !== $request->user()->id, 404);

        $subject = $schedule->subject;
        $isVehicle = $subject instanceof Vehicle;

        $this->normalizeRequestNumbers($request, integerFields: ['odometer', 'labor_cost', 'parts_cost']);

        $rules = [
            'done_at' => ['required', 'date', 'before_or_equal:today'],
            'labor_cost' => ['nullable', 'integer', 'min:0'],
            'parts_cost' => ['nullable', 'integer', 'min:0'],
            'parts' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'workshop_name' => ['nullable', 'string', 'max:150'],
        ];

        if ($schedule->trigger_type === 'kilometer' || ($isVehicle && $request->filled('odometer'))) {
            $rules['odometer'] = [
                $schedule->trigger_type === 'kilometer' ? 'required' : 'nullable',
                'integer',
                'min:0',
                $recorder->validateOdometerTimeline($isVehicle ? $subject : null, $request->input('done_at')),
            ];
        } else {
            $rules['odometer'] = ['nullable', 'integer', 'min:0'];
        }

        $validated = $request->validate($rules, [
            'done_at.before_or_equal' => 'Tanggal servis tidak boleh di masa depan.',
        ]);

        $recorder->record(
            user: $request->user(),
            subject: $subject,
            data: $validated,
            schedule: $schedule
        );

        app(ActivityLogger::class)->log(
            $request->user(),
            'schedule.completed',
            $subject,
            'Jadwal "'.$schedule->title.'" ditandai selesai. Tanggal: '.$validated['done_at'].'.',
            array_filter([
                'schedule_title' => $schedule->title,
                'done_at' => $validated['done_at'],
                'odometer' => $validated['odometer'] ?? null,
                'workshop_name' => $validated['workshop_name'] ?? null,
                'labor_cost' => $validated['labor_cost'] ?? null,
                'parts_cost' => $validated['parts_cost'] ?? null,
            ]),
        );

        return redirect()->back()
            ->with('success', 'Jadwal "'.$schedule->title.'" berhasil ditandai selesai.');
    }

    public function destroy(Request $request, MaintenanceSchedule $schedule): RedirectResponse
    {
        abort_if($schedule->user_id !== $request->user()->id, 404);

        $title = $schedule->title;
        $subject = $schedule->subject;
        $schedule->delete();

        app(ActivityLogger::class)->log(
            $request->user(),
            'schedule.deleted',
            $subject,
            'Jadwal "'.$title.'" dihapus.',
            ['schedule_title' => $title],
        );

        return redirect()->back()
            ->with('success', 'Jadwal "'.$title.'" berhasil dihapus.');
    }
}
