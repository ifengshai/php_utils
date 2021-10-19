<?php

namespace Fengsha\Utils\RedisDistributedLock\Providers;

use Illuminate\Support\ServiceProvider;
use Fengsha\Utils\RedisDistributedLock\RedisDistributedLock;
class RedisDistributedLockServiceProvider extends ServiceProvider
{
    protected $defer = false;

    public function register()
    {
        $configPath = __DIR__ . '/../../config/fslock.php';
        $this->mergeConfigFrom($configPath, 'fslock');
        $this->app->singleton('fsredisdistributedlock', function ($app) {
            return new RedisDistributedLock();
        });
    }

    public function boot()
    {
        $configPath = __DIR__ . '/../../config/fslock.php';
        if(!function_exists('config_path')){
            $path = base_path().'/config/fslock.php';
            $this->publishes([$configPath => $path]);
        }else{
            $this->publishes([$configPath => config_path('fslock.php')]);
        }
//        $this->publishes([$configPath => config_path('fslock.php')]);
        $this->app->make('fsredisdistributedlock');
    }
}