<?php

namespace Fengsha\Utils\Log\Providers;

use Illuminate\Support\ServiceProvider;
use Fengsha\Utils\Log\Logger;

class LoggerServiceProvider extends ServiceProvider
{
    protected $defer = false;

    public function register()
    {
        $configPath = __DIR__ . '/../../config/fslog.php';
        $this->mergeConfigFrom($configPath, 'fslog');
        $this->app->singleton('fslog', function ($app) {
            return new Logger;
        });
    }

    public function boot()
    {
        $configPath = __DIR__ . '/../../config/fslog.php';
        $this->publishes([$configPath => config_path('fslog.php')]);
        $this->app->make('fslog');
    }
}
