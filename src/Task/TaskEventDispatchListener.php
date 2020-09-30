<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\Swoole\Task;

use Mezzio\Swoole\Event\EventDispatcherInterface as SwooleEventDispatcherInterface;
use Mezzio\Swoole\Event\TaskEvent;
use Mezzio\Swoole\Exception\InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Throwable;
use Webmozart\Assert\Assert;

/**
 * Listener that dispatches TaskEvent `$data` arguments as events.
 *
 * This class purposely does not compose the dispatcher directly, but instead
 * composes the DI container. The reason is because all attempts to compose the
 * dispatcher led to failure to launch task workers. There's likely some
 * oddities around dangling references to be resolved somewhere.
 */
class TaskEventDispatchListener
{
    private ContainerInterface $container;

    private string $dispatcherServiceName;
    private ?string $loggerServiceName;

    public function __construct(
        $container,
        string $dispatcherServiceName = SwooleEventDispatcherInterface::class,
        ?string $loggerServiceName = null
    ) {
        if (! $container->has($dispatcherServiceName)) {
            throw new InvalidArgumentException(sprintf(
                'Cannot create %s; container does not contain "%s" service',
                __CLASS__,
                $dispatcherServiceName
            ));
        }

        $this->container             = $container;
        $this->dispatcherServiceName = $dispatcherServiceName;
        $this->loggerServiceName     = $loggerServiceName;
    }

    public function __invoke(TaskEvent $event): void
    {
        $data = $event->getData();
        if (! is_object($data)) {
            return;
        }

        try {
            $dispatcher = $this->container->get($this->dispatcherServiceName);
            Assert::isInstanceOf($dispatcher, EventDispatcherInterface::class);

            $event->setReturnValue($dispatcher->dispatch($data));
        } catch (Throwable $e) {
            $this->logDispatchError($e, $event);
        }
        $event->taskProcessingComplete();
    }

    private function logDispatchError(Throwable $e, TaskEvent $event): void
    {
        if (empty($this->loggerServiceName)) {
            return;
        }

        if (! $this->container->has($this->loggerServiceName)) {
            return;
        }

        $logger = $this->container->get($this->loggerServiceName);
        if (! $logger instanceof LoggerInterface) {
            return;
        }

        $logger->error('Error processing task {taskId}: {error}', [
            'taskId' => $event->getTaskId(),
            'error'  => sprintf(
                "[%s - %d] %s\n%s",
                get_class($e),
                $e->getCode(),
                $e->getMessage(),
                $e->getTraceAsString()
            ),
        ]);
    }
}
