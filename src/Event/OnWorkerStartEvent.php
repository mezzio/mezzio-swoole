<?php


namespace Mezzio\Swoole\Event;

use Swoole\Http\Server as SwooleHttpServer;

class OnWorkerStartEvent
{
    /**
     * Swoole HTTP Server Instance
     *
     * @var SwooleHttpServer
     */
    private $httpServer;

    /**
     * @var integer
     */
    private $workerId;

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