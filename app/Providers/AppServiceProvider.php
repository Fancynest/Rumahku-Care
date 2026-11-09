<?php

namespace App\Providers;

use App\Services\ScheduleStatus;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
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
        $this->app->scoped(ScheduleStatus::class);

        $this->app['events']->listen(RequestHandled::class, function (): void {
            $this->app->forgetScopedInstances();
        });
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

            if (Auth::check()) {
                $urgentSchedules = app(ScheduleStatus::class)->urgentForUser(Auth::user())->take(5);
            }

            $view->with('urgentSchedules', $urgentSchedules);
        });
    }
}
