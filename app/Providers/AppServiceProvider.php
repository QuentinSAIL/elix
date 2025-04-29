<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
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
        Blade::directive('euro', function ($expression) {
            return "<?php echo number_format($expression, 2, ',', ' ') . ' €'; ?>";
        });

        Blade::directive('limit', function ($expression) {
            $parts = explode(',', trim($expression, "() "));
            $string = $parts[0];
            $limit  = $parts[1] ?? 100;
            return "<?php echo e(Str::limit({$string}, {$limit})); ?>";
        });
    }
}
