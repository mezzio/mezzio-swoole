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

    /**
     * @var integer
     */
    protected $workerId;

    /**
     * OnWorkerStartEvent constructor.
     * @param SwooleHttpServer $httpServer
     * @param int $workerId
     */
    public function __construct(SwooleHttpServer $httpServer, int $workerId)
    {
        $this->httpServer = $httpServer;
        $this->workerId = $workerId;
    }

    /**
     * @return SwooleHttpServer
     */
    public function getHttpServer(): SwooleHttpServer
    {
        return $this->httpServer;
    }

    /**
     * @return int
     */
    public function getWorkerId(): int
    {
        return $this->workerId;
    }
}