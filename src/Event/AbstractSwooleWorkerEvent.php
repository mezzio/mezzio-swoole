<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\Swoole\Event;

use Swoole\Http\Server as SwooleHttpServer;

abstract class AbstractSwooleWorkerEvent
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
