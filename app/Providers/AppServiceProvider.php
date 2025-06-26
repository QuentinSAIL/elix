<?php

namespace App\Providers;

use App\Services\GoCardlessDataService;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(GoCardlessDataService::class, function ($app) {
            return new GoCardlessDataService;
        });
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
    }
}
