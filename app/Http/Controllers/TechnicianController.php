<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class TechnicianController extends Controller
{
    public function index(): View
    {
        return view('technicians.index');
    }
}
