<?php

use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DamageReportController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\FuelLogController;
use App\Http\Controllers\HomeAssetController;
use App\Http\Controllers\InspectionController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\TechnicianController;
use App\Http\Controllers\VehicleController;
use Illuminate\Support\Facades\Route;

// Guest Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:register');
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
        Route::put('/{vehicle}', [VehicleController::class, 'update'])->name('update');
        Route::post('/{vehicle}/archive', [VehicleController::class, 'archive'])->name('archive');
        Route::post('/{vehicle}/restore', [VehicleController::class, 'restore'])->name('restore');
        Route::delete('/{vehicle}', [VehicleController::class, 'destroy'])->name('destroy');
        Route::patch('/{vehicle}/odometer', [VehicleController::class, 'updateOdometer'])->name('update-odometer');
        Route::post('/{vehicle}/service-logs', [VehicleController::class, 'storeServiceLog'])->name('service-logs.store');
        Route::post('/{vehicle}/fuel-logs', [FuelLogController::class, 'store'])->name('fuel-logs.store');
    });

    Route::prefix('fuel-logs')->name('fuel-logs.')->group(function () {
        Route::put('/{fuelLog}', [FuelLogController::class, 'update'])->name('update');
        Route::delete('/{fuelLog}', [FuelLogController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('finance')->name('finance.')->group(function () {
        Route::get('/', [FinanceController::class, 'index'])->name('index');
        Route::get('/export', [FinanceController::class, 'export'])->name('export');
        Route::post('/budgets', [FinanceController::class, 'updateBudgets'])->name('budgets.update');
    });

    Route::prefix('schedules')->name('schedules.')->group(function () {
        Route::get('/', [ScheduleController::class, 'index'])->name('index');
        Route::get('/calendar', [ScheduleController::class, 'calendar'])->name('calendar');
        Route::get('/history', [ScheduleController::class, 'history'])->name('history');
        Route::post('/', [ScheduleController::class, 'store'])->name('store');
        Route::put('/{schedule}', [ScheduleController::class, 'update'])->name('update');
        Route::post('/{schedule}/complete', [ScheduleController::class, 'complete'])->name('complete');
        Route::delete('/{schedule}', [ScheduleController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('technicians')->name('technicians.')->group(function () {
        Route::get('/', [TechnicianController::class, 'index'])->name('index');
    });

    Route::prefix('attachments')->name('attachments.')->group(function () {
        Route::post('/', [AttachmentController::class, 'store'])->name('store');
        Route::get('/{attachment}', [AttachmentController::class, 'show'])->name('show');
        Route::delete('/{attachment}', [AttachmentController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('documents')->name('documents.')->group(function () {
        Route::post('/', [DocumentController::class, 'store'])->name('store');
        Route::put('/{document}', [DocumentController::class, 'update'])->name('update');
        Route::post('/{document}/renew', [DocumentController::class, 'renew'])->name('renew');
        Route::delete('/{document}', [DocumentController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('damage-reports')->name('damage-reports.')->group(function () {
        Route::post('/', [DamageReportController::class, 'store'])->name('store');
        Route::put('/{damageReport}/status', [DamageReportController::class, 'updateStatus'])->name('update-status');
        Route::delete('/{damageReport}', [DamageReportController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('inspections')->name('inspections.')->group(function () {
        Route::post('/', [InspectionController::class, 'store'])->name('store');
        Route::post('/items/{item}/convert-to-damage', [InspectionController::class, 'convertIssueToDamageReport'])->name('convert-damage');
    });
});
