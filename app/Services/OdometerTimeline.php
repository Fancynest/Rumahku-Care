<?php

namespace App\Services;

use App\Models\FuelLog;
use App\Models\MaintenanceRecord;
use App\Models\Vehicle;
use Carbon\Carbon;
use Closure;

class OdometerTimeline
{
    /**
     * Validasi timeline odometer terhadap riwayat servis dan BBM sebelum dan sesudah tanggal target.
     */
    public function validate(
        ?Vehicle $vehicle,
        mixed $targetDate,
        ?int $ignoreFuelLogId = null,
        ?int $ignoreMaintenanceRecordId = null
    ): Closure {
        return function (string $attribute, mixed $value, Closure $fail) use (
            $vehicle,
            $targetDate,
            $ignoreFuelLogId,
            $ignoreMaintenanceRecordId
        ): void {
            if (! $vehicle || $value === null || $value === '') {
                return;
            }

            if (! $targetDate || ! strtotime((string) $targetDate)) {
                return;
            }

            $dateStr = Carbon::parse($targetDate)->toDateString();
            $val = (int) $value;

            // 1. Cari catatan sebelumnya (<= targetDate)
            $prev = $this->findPrevious($vehicle, $dateStr, $ignoreFuelLogId, $ignoreMaintenanceRecordId);
            if ($prev && $val < $prev['odometer']) {
                $fail("Odometer tidak boleh lebih kecil dari {$prev['label']} sebelumnya (".number_format($prev['odometer'])." KM pada {$prev['date']->format('d/m/Y')}).");

                return;
            }

            // 2. Cari catatan berikutnya (> targetDate)
            $next = $this->findNext($vehicle, $dateStr, $ignoreFuelLogId, $ignoreMaintenanceRecordId);
            if ($next && $val > $next['odometer']) {
                $fail("Odometer tidak boleh lebih besar dari {$next['label']} berikutnya (".number_format($next['odometer'])." KM pada {$next['date']->format('d/m/Y')}).");
            }
        };
    }

    /**
     * Cari catatan dengan odometer terakhir pada atau sebelum tanggal target.
     *
     * @return array{odometer: int, date: Carbon, label: string}|null
     */
    public function findPrevious(
        Vehicle $vehicle,
        string $date,
        ?int $ignoreFuelLogId = null,
        ?int $ignoreMaintenanceRecordId = null
    ): ?array {
        $mQuery = $vehicle->maintenanceRecords()
            ->whereNotNull('odometer')
            ->whereDate('done_at', '<=', $date);
        if ($ignoreMaintenanceRecordId) {
            $mQuery->where('id', '!=', $ignoreMaintenanceRecordId);
        }
        /** @var MaintenanceRecord|null $mPrev */
        $mPrev = $mQuery->orderByDesc('done_at')->orderByDesc('id')->first();

        $fQuery = $vehicle->fuelLogs()
            ->whereNotNull('odometer')
            ->whereDate('filled_at', '<=', $date);
        if ($ignoreFuelLogId) {
            $fQuery->where('id', '!=', $ignoreFuelLogId);
        }
        /** @var FuelLog|null $fPrev */
        $fPrev = $fQuery->orderByDesc('filled_at')->orderByDesc('id')->first();

        if (! $mPrev && ! $fPrev) {
            return null;
        }

        if ($mPrev && ! $fPrev) {
            return [
                'odometer' => (int) $mPrev->odometer,
                'date' => Carbon::parse($mPrev->done_at),
                'label' => 'riwayat servis',
            ];
        }

        if ($fPrev && ! $mPrev) {
            return [
                'odometer' => (int) $fPrev->odometer,
                'date' => Carbon::parse($fPrev->filled_at),
                'label' => 'catatan BBM',
            ];
        }

        $mDate = Carbon::parse($mPrev->done_at)->startOfDay();
        $fDate = Carbon::parse($fPrev->filled_at)->startOfDay();

        if ($mDate->gt($fDate)) {
            return [
                'odometer' => (int) $mPrev->odometer,
                'date' => $mDate,
                'label' => 'riwayat servis',
            ];
        }

        if ($fDate->gt($mDate)) {
            return [
                'odometer' => (int) $fPrev->odometer,
                'date' => $fDate,
                'label' => 'catatan BBM',
            ];
        }

        // Tanggal sama: pilih pembacaan odometer tertinggi
        if ((int) $mPrev->odometer >= (int) $fPrev->odometer) {
            return [
                'odometer' => (int) $mPrev->odometer,
                'date' => $mDate,
                'label' => 'riwayat servis',
            ];
        }

        return [
            'odometer' => (int) $fPrev->odometer,
            'date' => $fDate,
            'label' => 'catatan BBM',
        ];
    }

    /**
     * Cari catatan dengan odometer pertama setelah tanggal target.
     *
     * @return array{odometer: int, date: Carbon, label: string}|null
     */
    public function findNext(
        Vehicle $vehicle,
        string $date,
        ?int $ignoreFuelLogId = null,
        ?int $ignoreMaintenanceRecordId = null
    ): ?array {
        $mQuery = $vehicle->maintenanceRecords()
            ->whereNotNull('odometer')
            ->whereDate('done_at', '>', $date);
        if ($ignoreMaintenanceRecordId) {
            $mQuery->where('id', '!=', $ignoreMaintenanceRecordId);
        }
        /** @var MaintenanceRecord|null $mNext */
        $mNext = $mQuery->orderBy('done_at')->orderBy('id')->first();

        $fQuery = $vehicle->fuelLogs()
            ->whereNotNull('odometer')
            ->whereDate('filled_at', '>', $date);
        if ($ignoreFuelLogId) {
            $fQuery->where('id', '!=', $ignoreFuelLogId);
        }
        /** @var FuelLog|null $fNext */
        $fNext = $fQuery->orderBy('filled_at')->orderBy('id')->first();

        if (! $mNext && ! $fNext) {
            return null;
        }

        if ($mNext && ! $fNext) {
            return [
                'odometer' => (int) $mNext->odometer,
                'date' => Carbon::parse($mNext->done_at),
                'label' => 'riwayat servis',
            ];
        }

        if ($fNext && ! $mNext) {
            return [
                'odometer' => (int) $fNext->odometer,
                'date' => Carbon::parse($fNext->filled_at),
                'label' => 'catatan BBM',
            ];
        }

        $mDate = Carbon::parse($mNext->done_at)->startOfDay();
        $fDate = Carbon::parse($fNext->filled_at)->startOfDay();

        if ($mDate->lt($fDate)) {
            return [
                'odometer' => (int) $mNext->odometer,
                'date' => $mDate,
                'label' => 'riwayat servis',
            ];
        }

        if ($fDate->lt($mDate)) {
            return [
                'odometer' => (int) $fNext->odometer,
                'date' => $fDate,
                'label' => 'catatan BBM',
            ];
        }

        // Tanggal sama: pilih pembacaan odometer terendah
        if ((int) $mPrevOdo = (int) $mNext->odometer <= (int) $fNext->odometer) {
            return [
                'odometer' => (int) $mNext->odometer,
                'date' => $mDate,
                'label' => 'riwayat servis',
            ];
        }

        return [
            'odometer' => (int) $fNext->odometer,
            'date' => $fDate,
            'label' => 'catatan BBM',
        ];
    }

    /**
     * Naikkan odometer kendaraan jika pembacaan baru lebih tinggi dari odometer sekarang.
     * Tidak pernah menurunkan odometer kendaraan saat backfill.
     */
    public function updateVehicleOdometerIfHigher(Vehicle $vehicle, int $odometer): void
    {
        if ($odometer > (int) $vehicle->odometer) {
            $vehicle->update(['odometer' => $odometer]);
        }
    }
}
