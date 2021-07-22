<?php

namespace Jota\DeudoresContaduria\Providers;

use Jota\DeudoresContaduria\Classes\DeudoresContaduria;
use Illuminate\Support\ServiceProvider;

class DeudoresContaduriaProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->bind('DeudoresContaduria', function () {
            return new DeudoresContaduria();
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../../config/contaduria.php' => config_path('contaduria.php'),
        ]);
    }
}
