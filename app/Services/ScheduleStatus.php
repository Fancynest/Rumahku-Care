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
}
