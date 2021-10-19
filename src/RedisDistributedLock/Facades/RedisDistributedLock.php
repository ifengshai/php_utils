<?php

namespace Fengsha\Utils\RedisDistributedLock\Facades;

use Illuminate\Support\Facades\Facade;

class RedisDistributedLock extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'fsredisdistributedlock';
    }
}
