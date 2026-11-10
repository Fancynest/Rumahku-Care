<?php

namespace App\Providers;

use App\Services\AttentionFeed;
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
        $this->app->scoped(AttentionFeed::class);

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
                    $seconds = $headers['Retry-After'] ?? 60;

                    return back(fallback: route('login'))
                        ->withInput($request->only('email'))
                        ->withErrors([
                            'email' => "Terlalu banyak percobaan. Coba lagi dalam {$seconds} detik.",
                        ]);
                });
        });

        RateLimiter::for('register', function (Request $request) {
            return Limit::perMinute(5)
                ->by($request->ip())
                ->response(function (Request $request, array $headers) {
                    $seconds = $headers['Retry-After'] ?? 60;

                    return back(fallback: route('register'))
                        ->withInput($request->except('password', 'password_confirmation'))
                        ->withErrors([
                            'email' => "Terlalu banyak percobaan. Coba lagi dalam {$seconds} detik.",
                        ]);
                });
        });

        RateLimiter::for('forgot-password', function (Request $request) {
            return Limit::perMinute(5)
                ->by($request->ip())
                ->response(function (Request $request, array $headers) {
                    $seconds = $headers['Retry-After'] ?? 60;

                    return back(fallback: route('password.request'))
                        ->withInput($request->only('email'))
                        ->withErrors([
                            'email' => "Terlalu banyak percobaan. Coba lagi dalam {$seconds} detik.",
                        ]);
                });
        });

        View::composer('layouts.header', function ($view): void {
            $urgentSchedules = collect();

            if (Auth::check()) {
                $urgentSchedules = app(AttentionFeed::class)->urgent(Auth::user())->take(5);
            }

            $view->with('urgentSchedules', $urgentSchedules);
        });
    }
}
