<?php

namespace App\Providers;

use Illuminate\Support\Facades\Auth;
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
            $urgentSchedules = collect();

            if (Auth::check() && Schema::hasTable('maintenance_schedules')) {
                $urgentSchedules = Auth::user()->schedules()
                    ->with('subject')
                    ->where('is_active', true)
                    ->get()
                    ->filter(fn ($s) => in_array($s->status, ['terlambat', 'mendesak']))
                    ->sortBy(fn ($s) => $s->status === 'terlambat' ? 0 : 1)
                    ->take(5);
            }

            $view->with('urgentSchedules', $urgentSchedules);
        });
    }
}
