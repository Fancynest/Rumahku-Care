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
                $priorityMap = [
                    'terlambat' => 0,
                    'mendesak' => 1,
                    'perhatian' => 2,
                ];

                $urgentSchedules = Auth::user()->schedules()
                    ->with('subject')
                    ->where('is_active', true)
                    ->get()
                    ->filter(fn ($s) => isset($priorityMap[$s->status]))
                    ->sortBy(fn ($s) => $priorityMap[$s->status])
                    ->values()
                    ->take(5);
            }

            $view->with('urgentSchedules', $urgentSchedules);
        });
    }
}
