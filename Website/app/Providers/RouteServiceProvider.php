<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    // public const HOME = '/home';
    public const HOME = '/dashboard';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Guest (unauthenticated) tool rate limiters — keyed by IP, reset daily
        RateLimiter::for('guest-quiz', function (Request $request) {
            return Limit::perDay(3)->by('guest-quiz:' . $request->ip())
                ->response(fn () => response()->json([
                    'error'     => 'Daily limit reached for Quiz.',
                    'message'   => 'You have used all 3 free quiz generations today. Register for unlimited access.',
                    'register'  => url('/register'),
                ], 429));
        });

        RateLimiter::for('guest-presentation', function (Request $request) {
            return Limit::perDay(2)->by('guest-presentation:' . $request->ip())
                ->response(fn () => response()->json([
                    'error'     => 'Daily limit reached for Presentation.',
                    'message'   => 'You have used all 2 free presentations today. Register for unlimited access.',
                    'register'  => url('/register'),
                ], 429));
        });

        RateLimiter::for('guest-mindmap', function (Request $request) {
            return Limit::perDay(3)->by('guest-mindmap:' . $request->ip())
                ->response(fn () => response()->json([
                    'error'     => 'Daily limit reached for Mind Map.',
                    'message'   => 'You have used all 3 free mind maps today. Register for unlimited access.',
                    'register'  => url('/register'),
                ], 429));
        });

        RateLimiter::for('guest-animation', function (Request $request) {
            return Limit::perDay(2)->by('guest-animation:' . $request->ip())
                ->response(fn () => response()->json([
                    'error'     => 'Daily limit reached for Animation.',
                    'message'   => 'You have used all 2 free animations today. Register for unlimited access.',
                    'register'  => url('/register'),
                ], 429));
        });

        RateLimiter::for('guest-audio', function (Request $request) {
            return Limit::perDay(2)->by('guest-audio:' . $request->ip())
                ->response(fn () => response()->json([
                    'error'     => 'Daily limit reached for Audio.',
                    'message'   => 'You have used all 2 free audio generations today. Register for unlimited access.',
                    'register'  => url('/register'),
                ], 429));
        });

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
