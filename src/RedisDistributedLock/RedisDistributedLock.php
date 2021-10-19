<?php
namespace Fengsha\Utils\RedisDistributedLock;

class RedisDistributedLock
{
    const LOCK_SUCCESS = 'OK';
    const IF_NOT_EXIST = 'NX';
    const MILLISECONDS_EXPIRE_TIME = 'PX';

    const RELEASE_SUCCESS = 1;
    protected $redis;

    public function __construct()
    {
        $this->redis = new \Predis\Client(config('fslock'));
    }

    /**
     * 获取锁
     * @param String $key               锁
     * @param String $requestId         请求id
     * @param int $expireTime           过期时间
     * @return bool                     是否获取成功
     */
    public function getLock($key, $requestId, $expire)
    {
        $result = $this->redis->set($key, $requestId, self::MILLISECONDS_EXPIRE_TIME, $expire, self::IF_NOT_EXIST);
        return self::LOCK_SUCCESS === (string)$result;
    }

    /**
     * 阻塞式获取锁
     * @param String $key               锁
     * @param String $requestId         请求id
     * @param int $expireTime           过期时间
     * @param int $sleep                休眠时间(单位毫秒)
     * @return bool                     是否获取成功
     */
    public function lock($key, $requestId, $expire, $sleep = 3000)
    {
        while(true){
            try{
                $result = $this->redis->set($key, $requestId, self::MILLISECONDS_EXPIRE_TIME, $expire, self::IF_NOT_EXIST);
                if (self::LOCK_SUCCESS === (string)$result) {
                    return true;
                }else{
                    usleep($sleep * 1000);
                }
            }catch (\Exception $exception){
                return false;
            }
        }
    }

    /**
     * 尝试获取锁
     * @param String $key               锁
     * @param String $requestId         请求id
     * @param int $expireTime           过期时间(单位毫秒)
     * @param int $timeout              超时时间(单位毫秒)
     * @param int $sleep                休眠时间(单位毫秒)
     * @return bool                     是否获取成功
     */
    public function tryLock($key, $requestId, $expire, $timeout = 10000, $sleep = 3000) {
        $timeout = $timeout / 1000;
        $timeNow = microtime(true);
        while(true){
            try{
                $result = $this->redis->set($key, $requestId, self::MILLISECONDS_EXPIRE_TIME, $expire, self::IF_NOT_EXIST);
                if (self::LOCK_SUCCESS === (string)$result) {
                    return true;
                }else{
                    if ($timeout == 0){
                        return false;
                    }
                    usleep($sleep * 1000);
                    $timeEnd = microtime(true);
                    if($timeEnd - $timeNow >= $timeout){
                        return false;
                    }
                }
            }catch (\Exception $exception){
                return false;
            }
        }
    }

    /**释放锁
     * @return bool
     */
    public function releaseLock($key, $requestId) {
        $lua = "if redis.call('get', KEYS[1]) == ARGV[1] then return redis.call('del', KEYS[1]) else return 0 end";
        $result = $this->redis->eval($lua, 1, $key, $requestId);
        return self::RELEASE_SUCCESS === $result;
    }

    /**
     * 续期
     */
    public function refreshExpireTime($key, $requestId, $expire)
    {
        if($this->redis->get($key) == $requestId){
            $this->redis->set($key, $requestId,self::MILLISECONDS_EXPIRE_TIME, $expire);
        }
    }
}