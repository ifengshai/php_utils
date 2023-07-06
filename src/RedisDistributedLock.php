<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Redis;

class RedisDistributedLock
{
    const LOCK_SUCCESS = 'OK';
    const IF_NOT_EXIST = 'NX';
    const MILLISECONDS_EXPIRE_TIME = 'PX';
    const RELEASE_SUCCESS = 1;

    /**
     * 阻塞式获取锁
     *
     * @param String $key 锁
     * @param String $requestId 请求id
     * @param int $expire 过期时间(单位毫秒)
     * @param int $sleep 休眠时间(单位毫秒)
     * @return bool 是否获取成功
     */
    public function lock(string $key, string $requestId, int $expire, int $sleep = 3000): bool
    {
        while (true) {
            try {
                $result = Redis::set($key, $requestId, self::MILLISECONDS_EXPIRE_TIME, $expire, self::IF_NOT_EXIST);
                if (self::LOCK_SUCCESS === (string) $result) {
                    return true;
                } else {
                    usleep($sleep * 1000);
                }
            } catch (\Exception $exception) {
                return false;
            }
        }
    }

    /**
     * 尝试获取锁
     *
     * @param String $key 锁
     * @param String $requestId 请求id
     * @param int $expire 过期时间(单位毫秒)
     * @param int $timeout 超时时间(单位毫秒)
     * @param int $sleep 休眠时间(单位毫秒)
     * @return bool 是否获取成功
     */
    public function tryLock(string $key, string $requestId, int $expire, int $timeout = 5000, int $sleep = 3000): bool
    {
        $timeout = $timeout / 1000;
        $timeNow = microtime(true);
        while (true) {
            try {
                $result = Redis::set($key, $requestId, self::MILLISECONDS_EXPIRE_TIME, $expire, self::IF_NOT_EXIST);
                if (self::LOCK_SUCCESS === (string) $result) {
                    return true;
                } else {
                    if ($timeout == 0) {
                        return false;
                    }
                    usleep($sleep * 1000);
                    $timeEnd = microtime(true);
                    if ($timeEnd - $timeNow >= $timeout) {
                        return false;
                    }
                }
            } catch (\Exception $exception) {
                return false;
            }
        }
    }

    /**
     * 释放锁
     *
     * @param string $key 锁
     * @param string $requestId 请求id
     * @return bool 是否成功
     */
    public function releaseLock(string $key, string $requestId): bool
    {
        $lua = <<<LUA
if redis.call('get', KEYS[1]) == ARGV[1]
then
    return redis.call('del', KEYS[1])
else
    return 0
end
LUA;
        $result = Redis::eval($lua, 1, $key, $requestId);
        return self::RELEASE_SUCCESS === $result;
    }

    /**
     * 锁续期
     *
     * @param string $key 锁
     * @param string $requestId 请求id
     * @param int $expire 过期时间(单位毫秒)
     */
    public function refreshExpireTime(string $key, string $requestId, int $expire)
    {
        if (Redis::get($key) == $requestId) {
            Redis::set($key, $requestId, self::MILLISECONDS_EXPIRE_TIME, $expire);
        }
    }
}
