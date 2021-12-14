# Triggering Async Tasks

Application resources requiring lengthy processing are not uncommon.
In order to prevent these processes from impacting user experience, particularly when the user does not need to wait for the process to complete, we often delegate these to a [message queue](https://en.wikipedia.org/wiki/Message_queue).

While message queues are powerful, they also require additional infrastructure for your application, and can be hard to justify when you have a small number of heavy processes, or a small number of users.

In order to facilitate async processing, Swoole servers provides task worker processes, allowing your application to trigger tasks without the need for an external message queue, and without impacting the server worker processes &mdash; allowing your application to continue responding to requests while the server processes your task.

## Configuring the Server Process for Tasks

In order to take advantage of this feature, you will first need to configure the server to start up task workers.
In your local configuration for the server, you'll need to add `task_worker_num`.
The number of workers you configure define the number of concurrent tasks that can be executed at once.
Tasks are queued in the order that they trigger, meaning that a `task_worker_num` of 1 will offer no concurrency and tasks will execute in the order they are queued.

```php
'mezzio-swoole' => [
    'enable_coroutine' => true, //optional to enable coroutines and useful for tasks coroutines
    'swoole-http-server' => [
        'host' => '127.0.0.1',
        'port' => 8080,
        'options' => [
            'worker_num'      => 4,          // The number of HTTP Server Workers
            'task_worker_num' => 4,          // The number of Task Workers
            'task_enable_coroutine' => true, // optional to turn on task coroutine support
        ],
    ],
];
```

> ### CLI options for worker_num and task_worker_num
>
> Each of the `worker_num` and `task_worker_num` options have corresponding options in the `mezzio:swoole:start` and `mezzio:swoole:reload` console commands:
>
> - `--num-workers|-w` can be used to specify the number of HTTP Server Workers
> - `--num-task-workers|-t` can be used to specify the number of Task Workers

## Task Events

The `Mezzio\Swoole\SwooleRequestHandlerRunner` service registers listeners on the `Swoole\Http\Server` "task" and "finish" events.  "task" is triggered when `$server->task()` is called, and "finish" is triggered when a task worker calls `$trigger->finish()`.
Each of the listeners that the `SwooleRequestHandlerRunner` service registers in turn dispatch an [event via its composed PSR-14 event dispatcher](events.md):

- `Mezzio\Swoole\Event\TaskEvent` is dispatched via the "task" listener.
- `Mezzio\Swoole\Event\TaskFinishEvent` is dispatched via the "finish" listener.

## Registering Task Listeners

Registering listeners is the same as for other events: you will specify one or more of the above event types, pointing to a list of listeners that are defined as services in your container:

```php
// in config/autoload/mezzio.global.php:

use Mezzio\Swoole\Event;

return [
    // ...
    'mezzio-swoole' => [
        // ...
        'swoole-http-server' => [
            // ...
            'listeners' => [
                Event\TaskEvent::class => [
                    Your\TaskLoggerListener::class,
                    Your\TaskEventListener::class,
                ],
            ],
        ],
    ],
];
```

> ### TaskFinishEvent listener not required
>
> The "finish" event primarily exists to allow you to know when a given task has completed processing.
> In most cases, you can have a single listener that logs completion of the given task ID, ignoring the return value.

## Shipped task listeners

This package ships two listeners that you can use to process tasks: `Mezzio\Swoole\Task\TaskEventDispatchListener` and `Mezzio\Task\TaskInvokerListener`.

### TaskEventDispatchListener

`Mezzio\Swoole\Task\TaskEventDispatchListener` composes a [PSR-14 event dispatcher](https://www.php-fig.org/psr/psr-14/) and a [PSR-3 logger](https://www.php-fig.org/psr/psr-3/) instance.
When invoked, it retrieves the data from the `TaskEvent` (via `TaskEvent::getData()`).
If that data is not an object, it does nothing.
Otherwise, it treats it as an event, passing it to the composed event dispatcher, and setting the `TaskEvent`'s return value to the event returned by the dispatcher.
Once complete, it marks task processing as complete on the event.

To register this listener, use the following configuration:

```php
// in config/autoload/mezzio.global.php:

use Mezzio\Swoole\Event;
use Mezzio\Swoole\Task\TaskEventDispatchListener;

return [
    // ...
    'mezzio-swoole' => [
        // ...
        'swoole-http-server' => [
            // ...
            'listeners' => [
                Event\TaskEvent::class => [
                    TaskEventDispatchListener::class,
                ],
            ],
        ],
    ],
];
```

### TaskInvokerListener

`Mezzio\Swoole\Task\TaskInvokerListener` works with a suite of other classes to allow processing task data.
It composes a [PSR-11 container](https://www.php-fig.org/psr/psr-11/) and a [PSR-3 logger](https://www.php-fig.org/psr/psr-3/) instance.
When invoked, it pulls the data from the `TaskEvent` (via `TaskEvent::getData()`); if the data does not implement `Mezzio\Swoole\Task\TaskInterface`, it does nothing and returns immediately.
Otherwise, it invokes the task instance, passing it the PSR-11 container as the sole argument.
When done, it marks task processing complete in the `TaskEvent`.

The `TaskInterface` has the following definition:

```php
namespace Mezzio\Swoole\Task;

use JsonSerializable;
use Psr\Container\ContainerInterface;

interface TaskInterface extends JsonSerializable
{
    /**
     * @return mixed
     */
    public function __invoke(ContainerInterface $container);
}
```

The idea is that you can initialize a task as follows:

```php
$server->task(new Task(
    function ($data) {
        // process the data
    },
    $data
));
```

And then the `TaskInvokerListener` will intercept it, identify a task instance, and invoke it in order to process it.

To make this work, the package ships two `TaskInterface` implementations.
The first is `Mezzio\Swoole\Task\Task`, which composes the code that will process the task data, and the task data itself (as the "payload"):

```php
namespace Mezzio\Swoole\Task;

use Psr\Container\ContainerInterface;

final class Task implements TaskInterface
{
    /** @var callable */
    private $handler;

    private array $payload;

    public function __construct(callable $handler, ...$payload)
    {
        $this->handler = $handler;
        $this->payload = $payload;
    }

    /**
     * Container argument ignored in this implementation.
     */
    public function __invoke(ContainerInterface $container)
    {
        return ($this->handler)(...$this->payload);
    }

    // serialization details intentionally left out of listing
}
```

This works exactly like the prior example: instantiate the `Task` with the handler and any data it should process.
However, this task implementation has one caveat: the `$handler` MUST be serializable, and NOT contain references to other objects or resources (such as a database connection).
The reason is because the `Task` instance is serialized and sent to another process completely, where it is then deserialized.

To solve this limitation, the second implementation, `Mezzio\Swoole\Task\ServiceBasedTask`, composes a _service name_ and the task data to process.
During invocation, it pulls the service from the supplied container instance, and then uses the service to process the task:

```php
namespace Mezzio\Swoole\Task;

use Psr\Container\ContainerInterface;

final class ServiceBasedTask implements TaskInterface
{
    private array $payload;

    private string $serviceName;

    public function __construct(string $serviceName, ...$payload)
    {
        $this->serviceName = $serviceName;
        $this->payload     = $payload;
    }

    public function __invoke(ContainerInterface $container) : void
    {
        $deferred = $container->get($this->serviceName);
        $listener = $deferred instanceof DeferredServiceListener
            ? $deferred->getListener()
            : $deferred;
        $listener(...$this->payload);
    }

    // serialization details intentionally left out of listing
}
```

What is the `DeferredServiceListener`? It's a decorator for an invokable handler, generally a PSR-14 event listener.
The idea behind the class is to allow dispatching events normally via a PSR-14 event dispatcher in your code, but having the listener queue a task that it then processes itself.

To better understand the idea, let's look at the `DeferredServiceListener`:

```php
namespace Mezzio\Swoole\Task;

use Swoole\Http\Server as SwooleHttpServer;

final class DeferredServiceListener
{
    private SwooleHttpServer $server;

    /** @var callable */
    private $listener;

    private string $serviceName;

    public function __construct(SwooleHttpServer $server, callable $listener, string $serviceName)
    {
        $this->server      = $server;
        $this->listener    = $listener;
        $this->serviceName = $serviceName;
    }

    public function __invoke(object $event) : void
    {
        $this->server->task(new ServiceBasedTask($this->serviceName, $event));
    }

    public function getListener(): callable
    {
        return $this->listener;
    }
}
```

In your configuration, you will use the `Mezzio\Swoole\Task\DeferredServiceListenerDelegator` to decorate your event listener using the above class:

```php
// In config/autoload/dependencies.global.php:

use Mezzio\Swoole\Task\DeferredServiceListenerDelegator;

return [
    'dependences' => [
        'factories' => [
            App\Listener\UserCreationListener::class => App\Listener\UserCreationListenerFactory::class,
        ],
        'delegators' => [
            App\Listener\UserCreationListener::class => [
                DeferredServiceListenerDelegator::class,
            ],
        ],
    ],
];
```

You would attach your listener as needed for your listener provider implementation, pulling the listener from the container:

```php
use App\Event\UserCreationEvent;
use App\Listener\UserCreationListener;

// Example where $listenerProvider is a Psr\EventDispatcher\ListenerProviderInterface
// implementation, and defines a `listen()` method, and $container is a PSR-11
// container implementation:
$listenerProvider = $factory();
$listenerProvider->listen(UserCreationEvent::class, $container->get(UserCreationListener::class));
```

Somewhere in your code, you might then dispatch the `UserCreationEvent`:

```php
use App\Event\UserCreationEvent;

$dispatcher->dispatch(new UserCreationEvent($someData));
```

At this point, since the listener is decorated in a `DeferredServiceListener` instance, it queues a `ServiceBasedTask`.
When the task worker goes to invoke the `ServiceBasedTask`, it pulls the service from the container... which ends up decorating it as a `DeferredServiceListener` again.
To prevent infinite recursion, where the listener keeps queueing tasks for itself, the `ServiceBasedTask` checks to see if we have a `DeferredServiceListener`, and, if so, retrieves the actual listener it decorates from it.

While this approach may seem convoluted, what it enables is the use of other services from your DI container when processing the task, including databases, caching, logging, and more. On top of that, it allows you to remove any references in your code to the Swoole HTTP server instance, isolating your code from the details of how the code actually executes behind a PSR-14 event dispatcher.

**This is the recommended way to queue and process tasks with mezzio-swoole.**

## Examples

### Manually Triggering Tasks in Handlers

> Manually triggering tasks is not recommended, as it couples your application to Swoole, preventing usage in non-async paradigms as well as alternate async contexts.
> It can also make testing your application more difficult.
>
> We recommend the approach described in the section ["Dispatching a ServiceBasedTask via a PSR-14 Event Dispatcher"](#dispatching-a-servicebasedtask-via-a-psr-14-event-dispatcher).

If you want to manually dispatch a task, you will need to:

- Compose the `Swoole\Http\Server` instance in your class.
- Call that instance's `task()` method with the data representing the task.

As an example, we will create a request handler that composes the HTTP server instance.

```php
namespace Example;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Swoole\Http\Server as HttpServer;

class TaskTriggeringHandler implements RequestHandlerInterface
{
    /** @var ResponseFactoryInterface */
    private $responseFactory;

    /** @var HttpServer */
    private $server;

    public function __construct(
        HttpServer $server,
        ResponseFactoryInterface $responseFactory
    ) {
        $this->server          = $server;
        $this->responseFactory = $responseFactory;
    }

    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        // Gather data from request
        $data = $request->getParsedBody();

        // task() returns a task identifier, if you want to use it; otherwise,
        // you can ignore the return value.
        $taskIdentifier = $this->server->task([
            'to'      => $data['email'],
            'subject' => $data['subject'],
            'message' => $data['message'],
        ]);

        // The task() method is asynchronous, so execution continues immediately.
        return $this->responseFactory()->createResponse();
    }
}
```

Your handler will require a factory:

```php
namespace Example;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Swoole\Http\Server as HttpServer;

class TaskTriggeringHandlerFactory
{
    public function __invoke(ContainerInterface $container): TaskTriggeringHandler
    {
        return new TaskTriggeringHandler(
            $container->get(HttpServer::class),
            $container->get(ResponseFactoryInterface::class)
        );
    }
}
```

And you will then need to notify the container configuration:

```php
// in config/autoload/global.php or similar:

use Example\TaskTriggeringHandler;
use Example\TaskTriggeringHandlerFactory;

return [
    'dependencies' => [
        'factories' => [
            TaskTriggeringHandler::class => TaskTriggeringHandlerFactory::class,
        ],
    ],
];
```

### Logging TaskEvent listener

The following listener will listen to a `TaskEvent`, and log the information using the syslog.

```php
namespace Example;

use Mezzio\Swoole\Event\TaskEvent;

use function date;
use function sprintf;
use function syslog;
use function var_dump;

use const LOG_INFO;

class LoggingListener
{
    public function __invoke(TaskEvent $event): void
    {
        syslog(LOG_INFO, sprintf(
            '[%s] [%d] %s',
            date('c'),
            $event->getTaskId(),
            var_dump($event->getData())
        ));
    }
}
```

You would configure the application to use the listener as follows:

```php
// in config/autoload/swoole.global.php or similar:

use Example\LoggingListener;
use Mezzio\Swoole\Event\TaskEvent;

return [
    'dependencies' => [
        'invokables' => [
            LoggingListener::class => LoggingListener::class,
        ],
    ],
    'mezzio-swoole' => [
        'swoole-http-server' => [
            'listeners' => [
                TaskEvent::class => [
                    LoggingListener::class,
                ],
            ],
        ],
    ],
];
```

To trigger the event, you will create a task using a `Swoole\Http\Server` instance:

```php
$server->task($data);
```

> See the ["Manually Triggering Tasks in Handlers"](#manually-triggering-tasks-in-handlers) example for details on injecting the Swoole HTTP server in a request handler.

### Logging TaskFinishEvent listener

The following listener will listen to a `TaskFinishEvent`, and log the return value using the syslog.
It looks almost identical to the previous example.

```php
namespace Example;

use Mezzio\Swoole\Event\TaskFinishEvent;

use function date;
use function sprintf;
use function syslog;
use function var_dump;

use const LOG_INFO;

class TaskCompletionLoggingListener
{
    public function __invoke(TaskFinishEvent $event): void
    {
        syslog(LOG_INFO, sprintf(
            '[%s] [%d] %s',
            date('c'),
            $event->getTaskId(),
            var_dump($event->getReturnValue())
        ));
    }
}
```

Similar to the previous example, you would configure the application to use the listener as follows:

```php
// in config/autoload/swoole.global.php or similar:

use Example\TaskCompletionLoggingListener;
use Mezzio\Swoole\Event\TaskFinishEvent;

return [
    'dependencies' => [
        'invokables' => [
            TaskCompletionLoggingListener::class => TaskCompletionLoggingListener::class,
        ],
    ],
    'mezzio-swoole' => [
        'swoole-http-server' => [
            'listeners' => [
                TaskFinishEvent::class => [
                    TaskCompletionLoggingListener::class,
                ],
            ],
        ],
    ],
];
```

Unlike the previous example, however, you do not need to trigger this event yourself; it gets triggered by the `SwooleRequestHandlerRunner` service.

### Queueing task data for the TaskEventDispatchListener

In this example, we will configure the `TaskEventDispatchListener` as a `TaskEvent` listener.
The `TaskEventDispatchListener` will in turn have a listener attached for a custom event, `SomeDeferrableTask`.
We will queue a `SomeDeferrableTask` instance via the Swoole HTTP server `task()` method to defer its execution to the custom listener we create.

First, we will create the custom task type:

```php
namespace Example;

class SomeDeferrableTask
{
}
```

Next, we will create a listener for this event type:

```php
namespace Example;

class SomeDeferrableTaskListener
{
    public function __invoke(SomeDeferrableTask $event): void
    {
        // process the task here
    }
}
```

Next, we will configure listeners for the `TaskEvent` and our custom `SomeDeferrableTask`:

```php
// in config/autoload/swoole.global.php or similar:

use Example\SomeDeferrableTask;
use Example\SomeDeferrableTaskListener;
use Mezzio\Swoole\Event\TaskEvent;
use Mezzio\Swoole\Event\TaskEventDispatchListener;

return [
    'dependencies' => [
        'invokables' => [
            SomeDeferrableTaskListener::class => SomeDeferrableTaskListener::class,
        ],
    ],
    'mezzio-swoole' => [
        'swoole-http-server' => [
            'listeners' => [
                SomeDeferrableTask::class => [
                    SomeDeferrableTaskListener::class,
                ],
                TaskEvent::class => [
                    TaskEventDispatchListener::class,
                ],
            ],
        ],
    ],
];
```

To trigger the event, you will create a task using a `Swoole\Http\Server` instance:

```php
$server->task(new SomeDeferrableTask());
```

> See the ["Manually Triggering Tasks in Handlers"](#manually-triggering-tasks-in-handlers) example for details on injecting the Swoole HTTP server in a request handler.

### Queueing a Task for the TaskInvokerListener

In this example, we register the `TaskInvokerListener` with the `TaskEvent`.
We then create a `Mezzio\Swoole\Task\Task` instance and use the Swoole HTTP server to queue the task.

First, we will configure the `TaskInvokerListener` for the `TaskEvent`:

```php
// in config/autoload/swoole.global.php or similar:

use Mezzio\Swoole\Event\TaskEvent;
use Mezzio\Swoole\Event\TaskInvokerListener;

return [
    'mezzio-swoole' => [
        'swoole-http-server' => [
            'listeners' => [
                TaskEvent::class => [
                    TaskInvokerListener::class,
                ],
            ],
        ],
    ],
];
```

Next, we will create a `Task` instance.
The constructor for `Mezzio\Swoole\Task\Task` expects a callable listener as the first argument, and then zero or more additional arguments representing the arguments to pass to the listener.

```php
use Mezzio\Swoole\Task\Task;

$task = new Task(
    static function(object $event): void {
        // Process the $event object
    },
    (object) [
        'message' => 'hello world',
    ]
);
```

Finally, we will enqueue the task using a `Swoole\Http\Server` instance:

```php
$server->task($task);
```

> See the ["Manually Triggering Tasks in Handlers"](#manually-triggering-tasks-in-handlers) example for details on injecting the Swoole HTTP server in a request handler.

### Queueing a ServiceBasedTask for the TaskInvokerListener

In this example, we'll create a listener class that can handle a specific event type.
We will create a factory for the listener, and register it in the DI container.
We will also register the `TaskInvokerListener` with the `TaskEvent`.
Finally, we will then create a `Mezzio\Swoole\Task\ServiceBasedTask` instance using the service name for our listener and an instance of the event type it expects,  and use the Swoole HTTP server to queue the task.

First, let's define an event type:

```php
namespace Example;

class SomeDeferrableEvent
{
    /** @var string */
    private $message;

    public function __construct(string $message)
    {
        $this->message = $message;
    }

    public function __toString(): string
    {
        return $this->message;
    }
}
```

Next, we will create a listener for this event type:

```php
namespace Example;

use Psr\Log\LoggerInterface;

class SomeDeferrableEventListener
{
    /** @var LoggerInterface */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function __invoke(SomeDeferrableEvent $event): void
    {
        $this->logger->info(sprintf('Message: %s', (string) $event));
    }
}
```

The listener will require a factory:

```php
namespace Example;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class SomeDeferrableEventListenerFactory
{
    public function __invoke(ContainerInterface $container): SomeDeferrableEventListener
    {
        return new SomeDeferrableEventListener(
            $container->get(LoggerInterface::class)
        );
    }
}
```

At this point, we turn to configuration.
We will add dependency configuration for our listener (omitting the configuration for the logger service; we will assume you have done so already, or can figure out how to do so).
We will also add configuration to bind the `TaskInvokerListener` to the `TaskEvent`.

```php
// in config/autoload/swoole.global.php or similar:

use Example\SomeDeferrableEventListener;
use Example\SomeDeferrableEventListenerFactory;
use Mezzio\Swoole\Event\TaskEvent;
use Mezzio\Swoole\Event\TaskInvokerListener;

return [
    'dependencies' => [
        'factories' => [
            SomeDeferrableEventListener::class => SomeDeferrableEventListenerFactory::class,
        ],
    ],
    'mezzio-swoole' => [
        'swoole-http-server' => [
            'listeners' => [
                TaskEvent::class => [
                    TaskInvokerListener::class,
                ],
            ],
        ],
    ],
];
```

Next, we will create a `ServiceBasedTask` instance.
The constructor for `Mezzio\Swoole\Task\Task` expects the name of a service that can be pulled from the application DI container as the first argument, and then zero or more additional arguments representing the arguments to pass to the listener.
The service is expected to be invokable (i.e., it MUST define the method `__invoke()`).

```php
use Example\SomeDeferrableEvent;
use Example\SomeDeferrableEventListener;
use Mezzio\Swoole\Task\ServiceBasedTask;

$task = new ServiceBasedTask(
    SomeDeferrableEventListener::class,
    new SomeDeferrableEvent('hello world')
);
```

Finally, we will enqueue the task using a `Swoole\Http\Server` instance:

```php
$server->task($task);
```

> See the ["Manually Triggering Tasks in Handlers"](#manually-triggering-tasks-in-handlers) example for details on injecting the Swoole HTTP server in a request handler.

### Dispatching a ServiceBasedTask via a PSR-14 Event Dispatcher

This final example builds on the previous.
We will use the same event and listener.
However, instead of queueing the task via the Swoole HTTP server, we will queue it via a PSR-14 event dispatcher.
To make that possible, we will add a delegator factory for our listener that will do the work of queueing the task for us.

This example will make the assumption that you are using the same PSR-14 event dispatcher with both the `SwooleRequestHandlerRunner` service and the rest of your application, and will re-purpose the `Mezzio\Swoole\Event\SwooleListenerProvider` to also handle listeners for our `Example\SomeDeferrableEvent`.

First, we will define a handler that triggers this event:

```php
namespace Example;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

class MessageHandler implements RequestHandlerInterface
{
    /** @var EventDispatcherInterface */
    private $dispatcher;

    /** @var ResponseFactoryInterface */
    private $responseFactory;

    public function __construct(
        EventDispatcherInterface $dispatcher,
        ResponseFactoryInterface $responseFactory
    ) {
        $this->dispatcher      = $dispatcher;
        $this->responseFactory = $responseFactory;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->dispatcher->dispatch(new SomeDeferrableEvent('hello world'));
        return $this->responseFactory->createResponse();
    }
}
```

This handler will need a factory.

```php
namespace Example;

use Psr\Container\ContaienrInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseFactoryInterface;

class MessageHandlerFactory
{
    public function __invoke(ContainerInterface $container): MessageHandler
    {
        return new MessageHandler(
            $container->get(EventDispatcherInterface::class),
            $container->get(ResponseFactoryInterface::class)
        );
    }
}
```

The next step is changing configuration.
We need to configure our container to tell it about our handler, as well as to add the `Mezzio\Swoole\Task\DeferredServiceListenerDelegator` as a delegator factory for our `Example\SomeDeferrableEventListener`.
We will also add configuration to map our listener to our custom event.

```php
// in config/autoload/swoole.global.php or similar:

use Example\MessageHandler;
use Example\MessageHandlerFactory;
use Example\SomeDeferrableEvent;
use Example\SomeDeferrableEventListener;
use Example\SomeDeferrableEventListenerFactory;
use Mezzio\Swoole\Event\TaskEvent;
use Mezzio\Swoole\Event\TaskInvokerListener;
use Mezzio\Swoole\Task\DeferredServiceListenerDelegator;

return [
    'dependencies' => [
        'factories' => [
            MessageHandler::class              => MessageHandlerFactory::class,
            SomeDeferrableEventListener::class => SomeDeferrableEventListenerFactory::class,
        ],
        'delegators' => [
            SomeDeferrableEventListener::class => [
                DeferredServiceListenerDelegator::class,
            ],
        ],
    ],
    'mezzio-swoole' => [
        'swoole-http-server' => [
            'listeners' => [
                SomeDeferrableEvent::class => [
                    SomeDeferrableEventListener::class,
                ],
                TaskEvent::class => [
                    TaskInvokerListener::class,
                ],
            ],
        ],
    ],
];
```

At this point, we are done.

When the handler dispatches the event, our listener is notified.
However, the listener is decoreated via the `DeferredServiceListenerDelegator`, which will itself enqueue a `ServiceBasedTask` in the Swoole HTTP server, using the listener's service name and the event passed to the listener.
The `TaskInvokerListener` then passes the container to the task, which pulls our listener and executes it with the event.
