<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class HomeAssetController extends Controller
{
    public function index(): View
    {
        return view('assets.index');
    }

    public function show(int $id): View
    {
        return view('assets.show', compact('id'));
    }
}
