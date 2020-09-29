<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\Swoole\Event;

abstract class AbstractTaskEvent extends AbstractServerAwareEvent
{
    /** @var mixed */
    protected $data;

    protected int $taskId;

    public function getTaskId(): int
    {
        return $this->taskId;
    }

    /** @return mixed */
    public function getData()
    {
        return $this->data;
    }
}
