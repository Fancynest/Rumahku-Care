<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeAssetController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\TechnicianController;
use App\Http\Controllers\VehicleController;
use Illuminate\Support\Facades\Route;

// Guest Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

// Authenticated Application Routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::prefix('home-assets')->name('home-assets.')->group(function () {
        Route::get('/', [HomeAssetController::class, 'index'])->name('index');
        Route::post('/', [HomeAssetController::class, 'store'])->name('store');
        Route::get('/{homeAsset}', [HomeAssetController::class, 'show'])->name('show');
        Route::put('/{homeAsset}', [HomeAssetController::class, 'update'])->name('update');
        Route::delete('/{homeAsset}', [HomeAssetController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('vehicles')->name('vehicles.')->group(function () {
        Route::get('/', [VehicleController::class, 'index'])->name('index');
        Route::post('/', [VehicleController::class, 'store'])->name('store');
        Route::patch('/{vehicle}/odometer', [VehicleController::class, 'updateOdometer'])->name('update-odometer');
        Route::post('/{vehicle}/service-logs', [VehicleController::class, 'storeServiceLog'])->name('service-logs.store');
    });

    Route::prefix('schedules')->name('schedules.')->group(function () {
        Route::get('/', [ScheduleController::class, 'index'])->name('index');
        Route::post('/', [ScheduleController::class, 'store'])->name('store');
        Route::put('/{schedule}', [ScheduleController::class, 'update'])->name('update');
        Route::post('/{schedule}/complete', [ScheduleController::class, 'complete'])->name('complete');
        Route::delete('/{schedule}', [ScheduleController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('technicians')->name('technicians.')->group(function () {
        Route::get('/', [TechnicianController::class, 'index'])->name('index');
    });
});
