<?php

namespace Fengsha\Utils\Log\Processors;

use Fengsha\Utils\Log\Handlers\Handler;
use Fengsha\Utils\Log\Extend\RequestProcessor;
use Fengsha\Utils\Log\Extend\CustomProcessor;

class SQLLogProcessor extends Processor
{
    const DEFAULT_EXT_FORMAT = 'time:{time} sql:[{sql}]';

    private function __construct()
    {
        $this->traceId = $this->getTraceId();
        $this->rpcId = $this->getRpcId();
        $this->userId = $this->getUserId();
        $this->host = $this->getHost();
        $this->uri = $this->getUri();
        $this->defaultFormat = $this->getDefaultFormat();
        $this->format = $this->getFormat();
        $this->initListen();
    }

    public static function getInstance()
    {
        if (!static::$instance instanceof static) {
            static::$instance = new static;
        }
        return static::$instance;
    }

    public function boot($logger)
    {
        $enabled = config('fslog.sql_log.enabled', false);
        if (!$enabled) {
            return;
        }
        $this->handler = Handler::getInstance('sql');
        $this->logger = $logger;
        $this->configureHandlers($this->handler);
    }

    public function getPath()
    {
        if ($this->path) {
            return $this->path;
        }
        $this->path = config('fslog.sql_log.path');
        return $this->path;
    }

    public function getType()
    {
        if ($this->type) {
            return $this->type;
        }
        $this->type = config('fslog.sql_log.type');
        return $this->type;
    }

    public function getFormat()
    {
        if (is_null($this->format)) {
            $this->format = config('fslog.sql_log.format', self::DEFAULT_EXT_FORMAT);
        }
        return $this->format;
    }

    public function writeLog($query, $bindings = null, $time = null, $connectionName = null)
    {
        if ($this->getType() === 'fluentd') {
            $this->writeFluentd($query, $bindings, $time, $connectionName);
        } else {
            $this->writeFile($query, $bindings, $time, $connectionName);
        }
    }

    public function writeFluentd($query, $bindings = null, $time = null, $connectionName = null)
    {
        $this->handler->pushProcessor(new RequestProcessor(true));

        $customFields = [
            'sql' => $query,
            'time' => $time
        ];
        $this->handler->pushProcessor(new CustomProcessor($customFields));
        $this->handler->info('');
    }

    public function writeFile($query, $bindings = null, $time = null, $connectionName = null)
    {
        if (is_array($bindings)) {
            $query = str_replace('%', '%%', $query);
            $query = str_replace('?', "'%s'", $query);
            $query = vsprintf($query, $bindings);
        }

        $message = $this->getDefaultFormat() . $this->getFormat();
        $replace = [
            '{traceId}' => $this->getTraceId(),
            '{from}' => $this->getFrom(),
            '{host}' => $this->getHost(),
            '{uri}' => $this->getUri(),
            '{clientIp}' => $this->getClientIp(),
            '{rpcId}' => $this->getRpcId(),
            '{userId}' => $this->getUserId(),
            '{sql}' => $query,
            '{time}' => $time
        ];
        $result = strtr($message, $replace);
        $this->handler->info($result);
    }

    protected function initListen()
    {
        $enabled = config('fslog.sql_log.enabled', false);
        if (!$enabled) {
            return;
        }
        $request = $this->getRequestLogProcessor();
        $queryCollector = $this;
        $request->listen(function ($query, $bindings = null, $time = null, $connectionName = null) use ($queryCollector) {
            $queryCollector->writeLog($query, $bindings, $time, $connectionName);
        });
    }

    protected function getRequestLogProcessor()
    {
        return RequestLogProcessor::getInstance();
    }
}
