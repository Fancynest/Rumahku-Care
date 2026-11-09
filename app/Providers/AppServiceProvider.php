<?php

namespace App\Providers;

use App\Models\Vehicle;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('layouts.header', function ($view): void {
            $urgentVehicles = collect();

            if (Schema::hasTable('vehicles') && Schema::hasTable('service_logs')) {
                $urgentVehicles = Vehicle::with(['vehicleModel', 'latestServiceLog'])
                    ->get()
                    ->filter(fn ($v) => $v->status_label === 'Waktunya Servis');
            }

            $view->with('urgentVehicles', $urgentVehicles);
        });
    }
}
