<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Services\ScheduleStatus;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $allSchedules = ScheduleStatus::forUser($user);
        $urgentSchedules = ScheduleStatus::urgentForUser($user);

        // Max 3 most urgent schedules for the cards
        $topUrgentCards = $urgentSchedules->take(3);

        // 2 Summary numbers:
        // 1. Jumlah jadwal terlambat / mendesak
        $urgentCount = $allSchedules
            ->filter(fn ($s) => in_array($s->status, [ScheduleStatus::TERLAMBAT, ScheduleStatus::MENDESAK]))
            ->count();

        // 2. Jumlah aset dalam kondisi prima (kendaraan prima + aset rumah normal)
        $vehicleSchedules = $allSchedules->where('subject_type', Vehicle::class)->groupBy('subject_id');
        $vehicles = $user->vehicles()->get()->each(function ($v) use ($vehicleSchedules) {
            $v->setRelation('schedules', $vehicleSchedules->get($v->id, collect()));
        });
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
