<?php

namespace Fengsha\Utils\Log\Processors;

use Fengsha\Utils\Log\Handlers\Handler;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Arrayable;

abstract class Processor
{
    protected static $instance;

    protected $logger;

    protected $handler;

    protected $path;

    protected $type;

    protected $format;

    protected $defaultFormat;

    protected $traceId;

    protected $rpcId;

    protected $userId;

    protected $from;

    protected $host;

    protected $uri;

    protected $clientIp;

    protected $uid;

    protected $events;

    /**
     * 返回客户端结果时间
     * @var float
     */
    protected $finishTime;

    abstract public function boot($logger);

    abstract public function getPath();

    abstract public function getType();

    protected function configureHandlers(Handler $handler)
    {
        $path = $this->getPath();
        $type = $this->getType();
        $handler->setPath($path);
        $handler->setType($type);
        $handler->configureHandlers();
    }

    public function getHandler()
    {
        return $this->handler;
    }

    public function getDefaultFormat()
    {
        if (is_null($this->defaultFormat)) {
            $this->defaultFormat = config('fslog.default_format');
        }
        return $this->defaultFormat;
    }

    public function setFormat($format)
    {
        $this->format = $format;
    }

    protected function getHost()
    {
        if (is_null($this->host)) {
            $this->host = gethostname() ? gethostname() : 'cli';
        }
        return $this->host;
    }

    protected function getUri()
    {
        if (is_null($this->uri)) {
            if (isset($_SERVER['REQUEST_URI'])) {
                $this->uri = $_SERVER['REQUEST_URI'];
            } else {
                $args = isset($_SERVER['argv']) ? $_SERVER['argv'] : ['cli'];
                $this->uri = implode('|',$args);
            }
        }
        return $this->uri;
    }

    /**
     * get current signed user id
     *
     * @todo  去找业务了解，服务端 API 是否需要记录
     * @return integer
     */
    protected function getUid()
    {
        return 0;
    }

    public function setFinishTime($time = null)
    {
        if (defined('LARAVEL_END')) {
            $this->finishTime = LARAVEL_END;
            return;
        }
        if (is_float($time)) {
            $this->finishTime = $time;
        } else {
            $this->finishTime = microtime(true);
        }
    }

    protected function getFinishTime()
    {
        return is_null($this->finishTime) ? microtime(true) : $this->finishTime;
    }

    protected function getTotalTime()
    {
        return round(($this->getFinishTime() - LARAVEL_START) * 1000, 2);
    }

    protected function getAsyncTime()
    {
        return round((microtime(true) - $this->getFinishTime()) * 1000, 2);
    }

    protected function getTraceId()
    {
        if (is_null($this->traceId)) {
            $traceIdConf = config('ynlog.trace_id');
            $this->traceId = empty($_SERVER[$traceIdConf]) ? '0' : $_SERVER[$traceIdConf];
        }
        return $this->traceId;
    }

    protected function getRpcId()
    {
        if (is_null($this->rpcId)) {
            $this->rpcId = empty($_SERVER['HTTP_X_REQUEST_RPCID']) ? '0' : $_SERVER['HTTP_X_REQUEST_RPCID'];
        }
        return $this->rpcId;
    }

    protected function getUserId()
    {
        if (empty($this->userId)) {
            $this->userId = empty($_SERVER['HTTP_X_USERID']) ? '0' : $_SERVER['HTTP_X_USERID'];
        }
        return $this->userId;
    }

    protected function getFrom()
    {
        if (is_null($this->from)) {
            $this->from = empty($_SERVER['HTTP_FROM']) ? '0' : $_SERVER['HTTP_FROM'];
        }
        return $this->from;
    }

    protected function getClientIp()
    {
        if (is_null($this->clientIp)) {
            $this->clientIp = get_client_ip();
        }
        return $this->clientIp;
    }

    /**
     * 获取调用地点
     * @return array  调用地点数组 ['file' => $file, 'line' => $line]
     */
    protected function getCallPlace()
    {
        $stack = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 8);
        $file = 0;
        $line = 0;
        foreach ($stack as $trace) {
            $file = isset($trace['file']) ? $trace['file'] : $file;
            $line = isset($trace['line']) ? $trace['line'] : $line;
            if (isset($trace['class']) && strpos($trace['class'], 'Fengsha') === false) {
                break;
            }
        }

        return ['file' => $file, 'line' => $line];
    }

    /**
     * Format the parameters for the logger.
     *
     * @param  mixed  $message
     * @return mixed
     */
    protected function formatMessage($message)
    {
        if (is_array($message)) {
            $message = var_export($message, true);
        } elseif ($message instanceof Jsonable) {
            $message = $message->toJson();
        } elseif ($message instanceof Arrayable) {
            $message = var_export($message->toArray(), true);
        }
        $message = str_replace("\n", ' ', $message);
        return $message;
    }

    public function handleFinish()
    {
        $this->setFinishTime(microtime(true));
    }

    /**
     * 根据类型获取对应事件
     * @param $type
     * @return array|mixed
     */
    protected function getEvent($type)
    {
        $version = $this->getAppVersion();
        if (empty($this->events)) {
            $this->events = [];
            // db query事件
            $this->events['query'] = $version >= 5.2 ? \Illuminate\Database\Events\QueryExecuted::class : 'illuminate.query';
            // db transaction事件
            $this->events['trans'] = [
                'begin' => $version >= 5.2 ? \Illuminate\Database\Events\TransactionBeginning::class : 'connection.*.beganTransaction',
                'commit' => $version >= 5.2 ? \Illuminate\Database\Events\TransactionCommitted::class : 'connection.*.committed',
                'rollback' => $version >= 5.2 ? \Illuminate\Database\Events\TransactionRolledBack::class : 'connection.*.rollingBack',
            ];
        }

        if (!empty($type)) {
            return array_get($this->events, $type);
        }
        return array_flatten($this->events);
    }

    protected function getAppVersion()
    {
        $version = app()->version();
        if (strpos($version, 'Lumen') === 0) { // Lumen
            preg_match('/\((\d+(?:\.\d+){0,2})\)/', $version, $matches);
            $version = array_get($matches, 1, 0);
        }
        return floatval($version);
    }
}
