<?php

namespace App\Services;

use App\Models\MaintenanceSchedule;
use App\Models\Vehicle;
use Carbon\Carbon;
use InvalidArgumentException;

class ScheduleStatus
{
    public const TERLAMBAT = 'terlambat';

    public const MENDESAK = 'mendesak';

    public const PERHATIAN = 'perhatian';

    public const NORMAL = 'normal';

    public function determine(MaintenanceSchedule $schedule): string
    {
        if ($schedule->trigger_type === 'kilometer') {
            if ($schedule->subject_type !== Vehicle::class && ! ($schedule->subject instanceof Vehicle)) {
                throw new InvalidArgumentException('Jadwal berbasis kilometer hanya valid untuk subjek Kendaraan (Vehicle).');
            }

            if ($schedule->next_due_km === null) {
                return self::NORMAL;
            }

            $currentOdometer = $schedule->subject?->odometer ?? 0;
            $diffKm = $schedule->next_due_km - $currentOdometer;

            $thresholdTerlambat = (int) config('maintenance.thresholds.km.terlambat', 0);
            $thresholdMendesak = (int) config('maintenance.thresholds.km.mendesak', 200);
            $thresholdPerhatian = (int) config('maintenance.thresholds.km.perhatian', 500);

            if ($diffKm <= $thresholdTerlambat) {
                return self::TERLAMBAT;
            }

            if ($diffKm <= $thresholdMendesak) {
                return self::MENDESAK;
            }

            if ($diffKm <= $thresholdPerhatian) {
                return self::PERHATIAN;
            }

            return self::NORMAL;
        }

        // Trigger type: tanggal or interval_hari
        if ($schedule->next_due_date === null) {
            return self::NORMAL;
        }

        $dueDate = Carbon::parse($schedule->next_due_date)->startOfDay();
        $today = Carbon::today();
        $diffDays = (int) $today->diffInDays($dueDate, false);

        $thresholdTerlambat = (int) config('maintenance.thresholds.date.terlambat', 0);
        $thresholdMendesak = (int) config('maintenance.thresholds.date.mendesak', 7);
        $thresholdPerhatian = (int) config('maintenance.thresholds.date.perhatian', 30);

        if ($diffDays <= $thresholdTerlambat) {
            return self::TERLAMBAT;
        }

        if ($diffDays <= $thresholdMendesak) {
            return self::MENDESAK;
        }

        if ($diffDays <= $thresholdPerhatian) {
            return self::PERHATIAN;
        }

        return self::NORMAL;
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
}
