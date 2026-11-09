<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $statusRank = [
            'terlambat' => 0,
            'mendesak' => 1,
            'perhatian' => 2,
            'normal' => 3,
        ];

        $priorityRank = [
            'darurat' => 0,
            'mendesak' => 1,
            'perhatian' => 2,
            'normal' => 3,
        ];

        $allSchedules = $user->schedules()
            ->with('subject')
            ->where('is_active', true)
            ->get();

        // Urgent schedules sorted by urgency: terlambat -> mendesak -> perhatian, with priority tie-breaker (darurat > mendesak > perhatian > normal)
        $urgentSchedules = $allSchedules
            ->filter(fn ($s) => in_array($s->status, ['terlambat', 'mendesak', 'perhatian']))
            ->sort(function ($a, $b) use ($statusRank, $priorityRank) {
                $statusCmp = ($statusRank[$a->status] ?? 99) <=> ($statusRank[$b->status] ?? 99);
                if ($statusCmp !== 0) {
                    return $statusCmp;
                }

                return ($priorityRank[$a->priority] ?? 99) <=> ($priorityRank[$b->priority] ?? 99);
            })
            ->values();

        // Max 3 most urgent schedules for the cards
        $topUrgentCards = $urgentSchedules->take(3);

        // 2 Summary numbers:
        // 1. Jumlah jadwal terlambat / mendesak
        $urgentCount = $allSchedules
            ->filter(fn ($s) => in_array($s->status, ['terlambat', 'mendesak']))
            ->count();

        // 2. Jumlah aset dalam kondisi prima (kendaraan prima + aset rumah normal)
        $vehicles = $user->vehicles()->with('schedules')->get();
        $primeVehiclesCount = $vehicles->filter(fn ($v) => $v->status_label === 'Kondisi Prima')->count();

        $homeAssets = $user->homeAssets()->get();
        $normalHomeAssetsCount = $homeAssets->filter(fn ($ha) => $ha->condition === 'normal')->count();

        $primeAssetsCount = $primeVehiclesCount + $normalHomeAssetsCount;

        $hasNoData = $vehicles->isEmpty() && $homeAssets->isEmpty() && $allSchedules->isEmpty();

        // Contextual greeting
        $userName = explode(' ', $user->name)[0];
        if ($hasNoData) {
            $greetingMessage = "Halo {$userName}, selamat datang di Rumahku Care.";
        } elseif ($urgentCount > 0) {
            $greetingMessage = "Halo {$userName}, ada {$urgentCount} hal yang perlu perhatianmu minggu ini.";
        } else {
            $greetingMessage = "Halo {$userName}, seluruh aset rumah dan kendaraan Anda dalam kondisi prima.";
        }

        return view('dashboard', compact(
            'greetingMessage',
            'topUrgentCards',
            'urgentCount',
            'primeAssetsCount',
            'primeVehiclesCount',
            'normalHomeAssetsCount',
            'hasNoData'
        ));
    }
}
