<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

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
        RateLimiter::for('login', function (Request $request) {
            $email = Str::lower((string) $request->input('email'));

            return Limit::perMinute(5)
                ->by($email.'|'.$request->ip())
                ->response(function (Request $request, array $headers) {
                    return response('Terlalu banyak percobaan masuk. Silakan coba lagi dalam 1 menit.', 429, $headers);
                });
        });

        View::composer('layouts.header', function ($view): void {
            $urgentSchedules = collect();

            if (Auth::check() && Schema::hasTable('maintenance_schedules')) {
                $statusRank = [
                    'terlambat' => 0,
                    'mendesak' => 1,
                    'perhatian' => 2,
                ];

                $priorityRank = [
                    'darurat' => 0,
                    'mendesak' => 1,
                    'perhatian' => 2,
                    'normal' => 3,
                ];

                $urgentSchedules = Auth::user()->schedules()
                    ->with('subject')
                    ->where('is_active', true)
                    ->get()
                    ->filter(fn ($s) => isset($statusRank[$s->status]))
                    ->sort(function ($a, $b) use ($statusRank, $priorityRank) {
                        $statusCmp = ($statusRank[$a->status] ?? 99) <=> ($statusRank[$b->status] ?? 99);
                        if ($statusCmp !== 0) {
                            return $statusCmp;
                        }

                        return ($priorityRank[$a->priority] ?? 99) <=> ($priorityRank[$b->priority] ?? 99);
                    })
                    ->values()
                    ->take(5);
            }

            $view->with('urgentSchedules', $urgentSchedules);
        });
    }
}
