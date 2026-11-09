<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class VehicleController extends Controller
{
    public function index(): View
    {
        return view('vehicles.index');
    }
}
