<?php

namespace App\Services;

use App\Models\MaintenanceSchedule;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class ScheduleStatus
{
    public const TERLAMBAT = 'terlambat';

    public const MENDESAK = 'mendesak';

    public const PERHATIAN = 'perhatian';

    public const NORMAL = 'normal';

    public function determine(MaintenanceSchedule $schedule): string
    {
        $status = self::NORMAL;

        if ($schedule->trigger_type === 'kilometer') {
            if ($schedule->subject_type !== Vehicle::class && ! ($schedule->subject instanceof Vehicle)) {
                throw new InvalidArgumentException('Jadwal berbasis kilometer hanya valid untuk subjek Kendaraan (Vehicle).');
            }

            if ($schedule->next_due_km !== null) {
                $currentOdometer = $schedule->subject?->odometer ?? 0;
                $diffKm = $schedule->next_due_km - $currentOdometer;

                $thresholdTerlambat = (int) config('maintenance.thresholds.km.terlambat', 0);
                $thresholdMendesak = (int) config('maintenance.thresholds.km.mendesak', 200);
                $thresholdPerhatian = (int) config('maintenance.thresholds.km.perhatian', 500);

                if ($diffKm <= $thresholdTerlambat) {
                    $status = self::TERLAMBAT;
                } elseif ($diffKm <= $thresholdMendesak) {
                    $status = self::MENDESAK;
                } elseif ($diffKm <= $thresholdPerhatian) {
                    $status = self::PERHATIAN;
                }
            }
        } elseif ($schedule->next_due_date !== null) {
            $dueDate = Carbon::parse($schedule->next_due_date)->startOfDay();
            $today = Carbon::today();
            $diffDays = (int) $today->diffInDays($dueDate, false);

            $thresholdTerlambat = (int) config('maintenance.thresholds.date.terlambat', 0);
            $thresholdMendesak = (int) config('maintenance.thresholds.date.mendesak', 7);
            $thresholdPerhatian = (int) config('maintenance.thresholds.date.perhatian', 30);

            if ($diffDays <= $thresholdTerlambat) {
                $status = self::TERLAMBAT;
            } elseif ($diffDays <= $thresholdMendesak) {
                $status = self::MENDESAK;
            } elseif ($diffDays <= $thresholdPerhatian) {
                $status = self::PERHATIAN;
            }
        }

        // Prioritas "darurat" menaikkan tampilan ke kelompok Mendesak minimal
        if ($schedule->priority === 'darurat' && in_array($status, [self::NORMAL, self::PERHATIAN])) {
            return self::MENDESAK;
        }

        return $status;
    }

    public static function label(string $status): string
    {
        return match ($status) {
            self::TERLAMBAT => 'Terlambat',
            self::MENDESAK => 'Mendesak',
            self::PERHATIAN => 'Perhatian',
            default => 'Normal',
        };
    }

    public static function chipClass(string $status): string
    {
        return match ($status) {
            self::TERLAMBAT => 'chip-urgent',
            self::MENDESAK => 'chip-urgent',
            self::PERHATIAN => 'chip-warning',
            default => 'chip-success',
        };
    }

    /**
     * @param  iterable<string>  $statuses
     */
    public static function worst(iterable $statuses): ?string
    {
        $order = [
            self::TERLAMBAT => 4,
            self::MENDESAK => 3,
            self::PERHATIAN => 2,
            self::NORMAL => 1,
        ];

        $worst = null;
        $maxWeight = 0;

        foreach ($statuses as $status) {
            $weight = $order[$status] ?? 0;
            if ($weight > $maxWeight) {
                $maxWeight = $weight;
                $worst = $status;
            }
        }

        return $worst;
    }

    /**
     * @var array<int, Collection<int, MaintenanceSchedule>>
     */
    private static array $cachedActiveSchedules = [];

    /**
     * @var array<int, Collection<int, MaintenanceSchedule>>
     */
    private static array $cachedUrgentSchedules = [];

    /**
     * Ambil seluruh jadwal aktif milik user (dengan subjek terkait), dimemoize per siklus request.
     *
     * @return Collection<int, MaintenanceSchedule>
     */
    public static function forUser(User $user): Collection
    {
        if (! isset(self::$cachedActiveSchedules[$user->id])) {
            self::$cachedActiveSchedules[$user->id] = $user->schedules()
                ->with('subject')
                ->where('is_active', true)
                ->get();
        }

        return self::$cachedActiveSchedules[$user->id];
    }

    /**
     * Ambil jadwal mendesak/terlambat/perhatian milik user terurut prioritas, dimemoize per request.
     *
     * @return Collection<int, MaintenanceSchedule>
     */
    public static function urgentForUser(User $user): Collection
    {
        if (! isset(self::$cachedUrgentSchedules[$user->id])) {
            self::$cachedUrgentSchedules[$user->id] = self::sortUrgent(self::forUser($user));
        }

        return self::$cachedUrgentSchedules[$user->id];
    }

    /**
     * Urutkan jadwal berdasarkan status (terlambat > mendesak > perhatian) lalu tie-breaker prioritas (darurat > mendesak > perhatian > normal).
     *
     * @param  Collection<int, MaintenanceSchedule>  $schedules
     * @return Collection<int, MaintenanceSchedule>
     */
    public static function sortUrgent(Collection $schedules): Collection
    {
        $statusRank = [
            self::TERLAMBAT => 0,
            self::MENDESAK => 1,
            self::PERHATIAN => 2,
        ];

        $priorityRank = [
            'darurat' => 0,
            'mendesak' => 1,
            'perhatian' => 2,
            'normal' => 3,
        ];

        return $schedules
            ->filter(fn ($s) => in_array($s->status, [self::TERLAMBAT, self::MENDESAK, self::PERHATIAN]))
            ->sort(function ($a, $b) use ($statusRank, $priorityRank) {
                $statusCmp = ($statusRank[$a->status] ?? 99) <=> ($statusRank[$b->status] ?? 99);
                if ($statusCmp !== 0) {
                    return $statusCmp;
                }

                return ($priorityRank[$a->priority] ?? 99) <=> ($priorityRank[$b->priority] ?? 99);
            })
            ->values();
    }

    /**
     * Bersihkan cache memoize (berguna saat mutasi data atau antar-test).
     */
    public static function clearCache(): void
    {
        self::$cachedActiveSchedules = [];
        self::$cachedUrgentSchedules = [];
    }
}
