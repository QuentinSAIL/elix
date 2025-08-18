<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;

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
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        Blade::directive('euro', function ($expression) {
            return "<?php echo number_format($expression, 2, ',', ' ') . ' €'; ?>";
        });

        Blade::directive('limit', function ($expression) {
            $parts = explode(',', trim($expression, '() '));
            $string = $parts[0];
            $limit = $parts[1] ?? 100;

            return "<?php echo e(Str::limit({$string}, {$limit})); ?>";
        });

        // Rate limiters
        RateLimiter::for('global', function (Request $request) {
            return Limit::perMinute(200)->by(optional($request->user())->id ?: $request->ip());
        });

        RateLimiter::for('money', function (Request $request) {
            return Limit::perMinute(60)->by(optional($request->user())->id ?: $request->ip());
        });
    }
}
