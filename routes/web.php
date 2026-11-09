<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeAssetController;
use App\Http\Controllers\TechnicianController;
use App\Http\Controllers\VehicleController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

Route::prefix('home-assets')->name('home-assets.')->group(function () {
    Route::get('/', [HomeAssetController::class, 'index'])->name('index');
    Route::get('/{id}', [HomeAssetController::class, 'show'])->name('show');
});

Route::prefix('vehicles')->name('vehicles.')->group(function () {
    Route::get('/', [VehicleController::class, 'index'])->name('index');
    Route::post('/', [VehicleController::class, 'store'])->name('store');
    Route::patch('/{vehicle}/odometer', [VehicleController::class, 'updateOdometer'])->name('update-odometer');
});

Route::prefix('technicians')->name('technicians.')->group(function () {
    Route::get('/', [TechnicianController::class, 'index'])->name('index');
});
