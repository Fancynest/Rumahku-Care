<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Services\AttentionFeed;
use App\Services\ExpenseReport;
use App\Services\ScheduleStatus;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(
        Request $request,
        ScheduleStatus $scheduleStatus,
        AttentionFeed $feed,
        ExpenseReport $expenseReport
    ): View {
        $user = $request->user();

        $allSchedules = $scheduleStatus->forUser($user);

        // Max 3 most urgent items (jadwal, dokumen, kerusakan) for the cards
        $topUrgentCards = $feed->urgent($user)->take(3);

        // 1. Jumlah item berstatus terlambat / mendesak
        $urgentCount = $feed->urgentCount($user);

        // 2. Pengeluaran bulan ini (menggantikan metrik Aset Kondisi Prima)
        $expenseData = $expenseReport->getMonthlyReport($user);
        $currentMonthExpense = $expenseData['total_this_month'];
        $currentMonthExpenseFormatted = $expenseData['formatted_total_this_month'];

        // Jumlah aset untuk empty-state check dan kompatibilitas
        $vehicleSchedules = $allSchedules->where('subject_type', Vehicle::class)->groupBy('subject_id');
        $vehicles = $user->vehicles()->get()->each(function ($v) use ($vehicleSchedules) {
            $v->setRelation('schedules', $vehicleSchedules->get($v->id, collect()));
        });
        $primeVehiclesCount = $vehicles->filter(fn ($v) => $v->status_label === 'Kondisi Prima')->count();

        $homeAssets = $user->homeAssets()->get();
        $normalHomeAssetsCount = $homeAssets->filter(fn ($ha) => $ha->condition === 'normal')->count();

        $primeAssetsCount = $primeVehiclesCount + $normalHomeAssetsCount;

        $hasNoData = $vehicles->isEmpty() && $homeAssets->isEmpty() && $feed->get($user)->isEmpty();

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
            'currentMonthExpense',
            'currentMonthExpenseFormatted',
            'primeAssetsCount',
            'primeVehiclesCount',
            'normalHomeAssetsCount',
            'hasNoData'
        ));
    }
}
