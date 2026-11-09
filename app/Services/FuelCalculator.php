<?php

namespace App\Services;

use App\Models\FuelLog;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class FuelCalculator
{
    /**
     * Hitung metrik dan konsumsi BBM untuk kendaraan.
     *
     * @return array{
     *     logs: Collection<int, FuelLog>,
     *     recent_logs: Collection<int, FuelLog>,
     *     latest_km_l: float|null,
     *     average_5_km_l: float|null,
     *     cost_per_km: int|null,
     *     total_cost_this_month: int,
     *     total_liters_this_month: float
     * }
     */
    public function calculate(Vehicle $vehicle): array
    {
        /** @var Collection<int, FuelLog> $logs */
        $logs = $vehicle->fuelLogs()
            ->with('attachments')
            ->reorder()
            ->orderBy('filled_at')
            ->orderBy('id')
            ->get();

        $lastFullLog = null;
        $accumulatedLiters = 0.0;
        $accumulatedCost = 0;
        $intervals = [];

        foreach ($logs as $log) {
            if ($log->is_full_tank) {
                if ($lastFullLog === null) {
                    $lastFullLog = $log;
                    $accumulatedLiters = 0.0;
                    $accumulatedCost = 0;
                    $log->consumption_km_l = null;
                } else {
                    $distance = (int) $log->odometer - (int) $lastFullLog->odometer;
                    $liters = $accumulatedLiters + (float) $log->liters;
                    $cost = $accumulatedCost + (int) $log->total_cost;

                    if ($distance > 0 && $liters > 0) {
                        $kmL = round($distance / $liters, 2);
                        $log->consumption_km_l = $kmL;
                        $intervals[] = [
                            'distance' => $distance,
                            'liters' => $liters,
                            'cost' => $cost,
                            'km_l' => $kmL,
                        ];
                    } else {
                        $log->consumption_km_l = null;
                    }

                    $lastFullLog = $log;
                    $accumulatedLiters = 0.0;
                    $accumulatedCost = 0;
                }
            } else {
                $accumulatedLiters += (float) $log->liters;
                $accumulatedCost += (int) $log->total_cost;
                $log->consumption_km_l = null;
            }
        }

        // Rata-rata 5 interval terakhir
        $recentIntervals = array_slice($intervals, -5);
        $average5KmL = null;
        if (! empty($recentIntervals)) {
            $sumKmL = array_sum(array_column($recentIntervals, 'km_l'));
            $average5KmL = round($sumKmL / count($recentIntervals), 2);
        }

        $latestKmL = ! empty($intervals) ? end($intervals)['km_l'] : null;

        // Biaya per km dari interval tangki penuh
        $costPerKm = null;
        if (! empty($intervals)) {
            $totalDistance = array_sum(array_column($intervals, 'distance'));
            $totalCost = array_sum(array_column($intervals, 'cost'));
            if ($totalDistance > 0) {
                $costPerKm = (int) round($totalCost / $totalDistance);
            }
        }

        // Pengeluaran BBM bulan ini (Asia/Jakarta)
        $startOfMonth = Carbon::today()->startOfMonth();
        $endOfMonth = Carbon::today()->endOfMonth();

        $thisMonthLogs = $logs->filter(function (FuelLog $log) use ($startOfMonth, $endOfMonth) {
            $date = Carbon::parse($log->filled_at);

            return $date->betweenIncluded($startOfMonth, $endOfMonth);
        });

        $totalCostThisMonth = (int) $thisMonthLogs->sum('total_cost');
        $totalLitersThisMonth = (float) $thisMonthLogs->sum('liters');

        // Balikkan urutan logs untuk tampilan: terbaru di atas
        $descLogs = $logs->reverse()->values();

        return [
            'logs' => $descLogs,
            'recent_logs' => $descLogs->take(10),
            'latest_km_l' => $latestKmL,
            'average_5_km_l' => $average5KmL,
            'cost_per_km' => $costPerKm,
            'total_cost_this_month' => $totalCostThisMonth,
            'total_liters_this_month' => $totalLitersThisMonth,
        ];
    }
}
