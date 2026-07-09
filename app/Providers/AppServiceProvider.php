<?php

namespace App\Providers;

use App\Models\User;
use App\Observers\UserObserver;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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
        // Auth endpoints: prevent brute-force
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        RateLimiter::for('register', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        // General authenticated API: generous limit
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(300)->by($request->user()?->id ?: $request->ip());
        });

        // Override the password reset URL so the email link points to the SPA
        // (not the bare API endpoint). Configure FRONTEND_URL in .env.
        ResetPassword::createUrlUsing(function ($user, string $token) {
            $frontendUrl = rtrim(config('app.frontend_url', config('app.url')), '/');

            return $frontendUrl.'/reset-password?token='.$token.'&email='.urlencode($user->email);
        });

        // Create default categories for every new user
        User::observe(UserObserver::class);
    }
}
