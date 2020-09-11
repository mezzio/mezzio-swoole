<?php

namespace Mezzio\Swoole\Event;

use Swoole\Http\Server as SwooleHttpServer;

abstract class SwooleWorkerEvent
{
    /**
     * Swoole HTTP Server Instance
     *
     * @var SwooleHttpServer
     */
    protected $httpServer;

    /** @var int */
    protected $workerId;

    public function __construct(SwooleHttpServer $httpServer, int $workerId)
    {
        $this->httpServer = $httpServer;
        $this->workerId   = $workerId;
    }

    public function getHttpServer(): SwooleHttpServer
    {
        return $this->httpServer;
    }

    public function getWorkerId(): int
    {
        return $this->workerId;
    }
}
