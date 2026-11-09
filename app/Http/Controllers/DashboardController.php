<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $vehicles = $user->vehicles()->with(['vehicleModel', 'latestServiceLog'])->get();
        $totalVehicles = $vehicles->count();
        $urgentVehicles = $vehicles->filter(fn ($v) => $v->status_label === 'Waktunya Servis');
        $primeVehiclesCount = $vehicles->filter(fn ($v) => $v->status_label === 'Kondisi Prima')->count();

        return view('dashboard', compact(
            'vehicles',
            'totalVehicles',
            'urgentVehicles',
            'primeVehiclesCount'
        ));
    }
}
