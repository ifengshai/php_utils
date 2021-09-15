<?php

namespace Fengsha\Utils\Log\Providers;

use Illuminate\Support\ServiceProvider;
use Fengsha\Utils\Log\Logger;

class LumenLoggerServiceProvider extends ServiceProvider
{
    const CONFIG_KEY = 'fslog';

    protected $defer = false;

    public function register()
    {
        $this->app->configure(self::CONFIG_KEY);
        $configPath = __DIR__ . '/../../config/fslog.php';
        $this->mergeConfigFrom($configPath, self::CONFIG_KEY);
        $this->app->singleton('fslog', function ($app) {
            return new Logger;
        });
    }

    public function boot()
    {
        $this->app->make(self::CONFIG_KEY);
        if (isset($this->app->availableBindings['Psr\Log\LoggerInterface'])) {
            unset($this->app->availableBindings['Psr\Log\LoggerInterface']);
        }
        $this->app->singleton('Psr\Log\LoggerInterface', function ($app) {
            return $app->make(self::CONFIG_KEY);
        });
    }
}
