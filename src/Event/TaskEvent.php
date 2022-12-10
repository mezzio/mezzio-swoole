<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace Mezzio\Swoole\Event;

use Psr\EventDispatcher\StoppableEventInterface;
use Swoole\Http\Server as SwooleHttpServer;

class TaskEvent extends AbstractTaskEvent implements StoppableEventInterface
{
    /** @var mixed */
    private $returnValue;

    private bool $taskProcessed = false;

    /** @param mixed $data */
    public function __construct(SwooleHttpServer $server, int $taskId, private int $workerId, $data)
    {
        $this->server = $server;
        $this->taskId = $taskId;
        $this->data   = $data;
    }

    public function isPropagationStopped(): bool
    {
        return $this->taskProcessed;
    }

    public function taskProcessingComplete(): void
    {
        $this->taskProcessed = true;
    }

    public function setReturnValue(mixed $returnValue): void
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
