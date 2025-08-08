<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace Mezzio\Swoole\Event;

abstract class AbstractTaskEvent extends AbstractServerAwareEvent
{
    protected mixed $data;

    protected int $taskId;

    public function getTaskId(): int
    {
        return $this->taskId;
    }

    public function getData(): mixed
    {
        return $this->data;
    }
}
