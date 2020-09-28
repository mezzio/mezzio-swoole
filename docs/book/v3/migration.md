# Migration

This document covers changes between version 2 and version 3, and how you may
update your code to adapt to them.

### SwooleRequestHandlerRunner

The internals of `Mezzio\Swoole\SwooleRequestHandlerRunner` have changed entirely.
As a result, the constructor signature has also changed.
Before, it read:

```php
public function __construct(
    Psr\Http\Server\RequestHandlerInterface $handler,
    callable $serverRequestFactory,
    callable $serverRequestErrorResponseGenerator,
    Mezzio\Swoole\PidManager $pidManager,
    Swoole\Http\Server $httpServer,
    Mezzio\Swoole\StaticResourceHandlerInterface $staticResourceHandler = null,
    Mezzio\Swoole\Log\AccessLogInterface $logger = null,
    string $processName = self::DEFAULT_PROCESS_NAME,
    ?Mezzio\Swoole\HotCodeReload\Reloader $hotCodeReloader = null
)
```

It now reads:

```php
public function __construct(
    Swoole\Http\Server $httpServer,
    Psr\EventDispatcher\EventDispatcherInterface $dispatcher
)
```

As such, if you were providing your own factory for the class, or instantiating it manually, you will need to update your code.
See the [chapter on events](events.md) for more information on how the the [PSR-14 event dispatcher](https://www.php-fig.org/psr/psr-14/) is used internally, and what listeners are provided.
