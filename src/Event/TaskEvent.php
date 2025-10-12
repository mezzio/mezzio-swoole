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
    private mixed $returnValue;

    private bool $taskProcessed = false;

    public function __construct(
        SwooleHttpServer $server,
        int $taskId,
        private readonly int $workerId,
        mixed $data
    ) {
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

    public function getReturnValue(): mixed
    {
        return $this->returnValue;
    }

    public function getWorkerId(): int
    {
        return $this->workerId;
    }
}
