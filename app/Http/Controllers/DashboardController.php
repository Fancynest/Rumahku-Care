<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $vehicles = Vehicle::with('vehicleModel')->get();
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
