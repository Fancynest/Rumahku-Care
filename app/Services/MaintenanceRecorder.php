<?php

namespace App\Services;

use App\Models\MaintenanceRecord;
use App\Models\MaintenanceSchedule;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\Carbon;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class MaintenanceRecorder
{
    /**
     * Validasi timeline odometer terhadap riwayat servis sebelum dan sesudah tanggal target.
     */
    public function validateOdometerTimeline(?Vehicle $vehicle, mixed $doneAt): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($vehicle, $doneAt): void {
            if (! $vehicle || $value === null) {
                return;
            }

            if (! $doneAt || ! strtotime((string) $doneAt)) {
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
        };
    }

    /**
     * Catat riwayat perawatan, perbarui status/siklus jadwal (jika ada), dan naikkan odometer kendaraan bila sesuai.
     *
     * @param  array<string, mixed>  $data
     */
    public function record(
        User|int $user,
        Model $subject,
        array $data,
        ?MaintenanceSchedule $schedule = null
    ): MaintenanceRecord {
        return DB::transaction(function () use ($user, $subject, $data, $schedule): MaintenanceRecord {
            $userId = $user instanceof User ? $user->id : (int) $user;
            $isVehicle = $subject instanceof Vehicle;

            $odometer = isset($data['odometer']) && $data['odometer'] !== null && $data['odometer'] !== ''
                ? (int) $data['odometer']
                : null;

            $serviceType = $data['service_type'] ?? ($schedule ? $schedule->title : 'Servis Berkala');
            $workshopName = $data['workshop_name'] ?? ($isVehicle ? $subject->workshop : null);
            $laborCost = (int) ($data['labor_cost'] ?? 0);
            $partsCost = (int) ($data['parts_cost'] ?? 0);

            // 1. Buat MaintenanceRecord
            $record = MaintenanceRecord::create([
                'user_id' => $userId,
                'subject_type' => $subject->getMorphClass(),
                'subject_id' => $subject->getKey(),
                'schedule_id' => $schedule?->id,
                'done_at' => $data['done_at'],
                'odometer' => $odometer,
                'service_type' => $serviceType,
                'workshop_name' => $workshopName,
                'labor_cost' => $laborCost,
                'parts_cost' => $partsCost,
                'parts' => $data['parts'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            // 2. Perbarui Siklus Jadwal
            if ($schedule) {
                if ($schedule->trigger_type === 'kilometer') {
                    if ($odometer !== null) {
                        if ($schedule->last_done_km === null || $odometer >= $schedule->last_done_km) {
                            $schedule->last_done_km = $odometer;
                            $schedule->next_due_km = $odometer + ($schedule->interval_km ?: 5000);
                            $schedule->last_done_at = $data['done_at'];
                        }
                    }
                } elseif ($schedule->trigger_type === 'interval_hari') {
                    if ($schedule->last_done_at === null || $data['done_at'] >= $schedule->last_done_at->toDateString()) {
                        $days = $schedule->interval_days ?: 30;
                        $schedule->next_due_date = Carbon::parse($data['done_at'])->addDays($days)->toDateString();
                        $schedule->last_done_at = $data['done_at'];
                    }
                } elseif ($schedule->trigger_type === 'tanggal') {
                    $schedule->is_active = false;
                    $schedule->last_done_at = $data['done_at'];
                }

                $schedule->save();
            }

            // 3. Naikkan Odometer Kendaraan jika lebih tinggi
            if ($isVehicle && $odometer !== null && $odometer > $subject->odometer) {
                $subject->update(['odometer' => $odometer]);
            }

            return $record;
        });
    }
}
