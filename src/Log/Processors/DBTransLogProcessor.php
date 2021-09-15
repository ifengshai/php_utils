<?php

namespace Fengsha\Utils\Log\Processors;

use Fengsha\Utils\Log\Handlers\Handler;

class DBTransLogProcessor extends SQLLogProcessor
{
    protected $asyncTasks = [];

    public function boot($logger)
    {
        $enabled = config('fslog.trans_log.enabled', false);
        if (!$enabled) {
            return;
        }
        $this->handler = Handler::getInstance('trans');
        $this->logger = $logger;
        $this->configureHandlers($this->handler);
        $this->logger->pushAsyncTask(array($this, 'handleFinish'));
    }

    public function getPath()
    {
        if ($this->path) {
            return $this->path;
        }
        $this->path = config('fslog.trans_log.path');
        return $this->path;
    }

    public function getType()
    {
        if ($this->type) {
            return $this->type;
        }
        $this->type = config('fslog.trans_log.type');
        return $this->type;
    }

    public function getFormat()
    {
        if (is_null($this->format)) {
            $this->format = config('fslog.trans_log.format', 'DB TRANSACTION: {sql}');
        }
        return $this->format;
    }

    /**
     * 处理异步任务
     * @return void
     */
    public function handleFinish()
    {
        parent::handleFinish();

        $this->handleAsyncTasks();
    }

    /**
     * 执行异步任务
     * @return void
     */
    public function handleAsyncTasks()
    {
        $asyncTasks = $this->getAsyncTasks();
        foreach ($asyncTasks as $asyncTask) {
            list($callback, $payload) = $asyncTask;
            call_user_func_array($callback, $payload);
        }
    }

    /**
     * 获取异步任务
     * @return array
     */
    public function getAsyncTasks()
    {
        return $this->asyncTasks;
    }

    /**
     * 添加异步任务
     * @param  $callback
     * @param  array    $payload
     * @return void
     */
    protected function pushAsyncTask($callback, $payload)
    {
        $this->asyncTasks[] = [$callback, $payload];
    }

    protected function initListen()
    {
        $enabled = config('fslog.trans_log.enabled', false);
        if (!$enabled) {
            return;
        }

        $eventMgr = app('events');
        try {
            // 监听sql
            $events = $this->getEvent('query');
            $eventMgr->listen($events, [$this, 'listenQuery']);

            // 监听事务
            $events = $this->getEvent('trans');
            foreach ($events as $name => $event) {
                $method = 'listenTrans' . ucfirst($name);
                $eventMgr->listen($event, [$this, $method]);
            }
        } catch (\Exception $e) {
            throw new \Exception('fslog DB listen failed: ' . $e->getMessage(), $e->getCode());
        }
    }

    public function listenQuery($query, $bindings = null, $time = null, $connectionName = null)
    {
        if ($query instanceof \Illuminate\Database\Events\QueryExecuted) {
            $bindings = $query->bindings;
            $time = $query->time;
            $connection = $query->connection;
            $query = $query->sql;
        } else {
            $connection = app('db')->connection($connectionName);
        }

        if ($connection->transactionLevel() > 0) { // 仅记录事务范围内的sql
            $this->pushAsyncTask([$this, 'writeFile'], [$query, $bindings, $time]);
        }
    }

    public function __call($name, $arguments)
    {
        if (0 === ($pos = strpos($name, 'listenTrans'))) {
            $eventName = strtoupper(substr($name, 11));
            call_user_func([$this, 'listenTrans'], $eventName);
        }
    }

    public function listenTrans($eventName)
    {
        $this->pushAsyncTask([$this, 'writeFile'], ["{$eventName}..."]);
    }
}
