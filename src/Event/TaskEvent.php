<?php

declare(strict_types=1);

namespace Mezzio\Swoole\Event;

use Psr\EventDispatcher\StoppableEventInterface;
use Swoole\Http\Server as SwooleHttpServer;

class TaskEvent extends AbstractTaskEvent implements StoppableEventInterface
{
    /** @var mixed */
    private $returnValue;
    private bool $taskProcessed = false;
    private int $workerId;

    /** @param mixed $data */
    public function __construct(SwooleHttpServer $server, int $taskId, int $workerId, $data)
    {
        $this->server   = $server;
        $this->taskId   = $taskId;
        $this->workerId = $workerId;
        $this->data     = $data;
    }

    public function isPropagationStopped(): bool
    {
        return $this->taskProcessed;
    }

    public function taskProcessingComplete(): void
    {
        $this->taskProcessed = true;
    }

    /** @param mixed $returnValue */
    public function setReturnValue($returnValue): void
    {
        $this->returnValue = $returnValue;
    }

    /** @return mixed */
    public function getReturnValue()
    {
        return $this->returnValue;
    }

    public function getWorkerId(): int
    {
        return $this->workerId;
    }
}
