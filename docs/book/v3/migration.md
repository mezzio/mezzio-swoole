# Migration

This document covers changes between version 2 and version 3, and how you may
update your code to adapt to them.

## SwooleRequestHandlerRunner

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

## Reloader

The class `Mezzio\Swoole\HotCodeReload\Reloader` and its associated factory have been removed.
Use the [HotCodeReloaderWorkerStartListener](hot-code-reload.md) instead.

Additionally, with version 3, you will need to specify which paths you want to scan for changes via configuration.
Please see the [hot code reloading section on Configuration](hot-code-reload.md#configuration) for details.

## Tasks

In version 2 of this package, if you wanted to use the Swoole HTTP Server task functionality, you needed to:

- Set the `task_worker_num` option for the server instance in configuration.
- Create and register a `task` event handler with the server instance.

With version 3 of this package, we now always register a `task` event handler (see the [Swoole HTTP Server Events](events.md) and [Triggering Async Tasks](async-tasks.md) chapters for details), which means you only need to configure the `task_worker_num` setting.
Alternately, you can pass the `--num-task-workers|-t` option with a numeric number of task workers to either of the `mezzio:swoole:start` or `mezzio:swoole:reload` console commands (see [Command line usage](#command-line-usage) section below for details).

## Command line usage

In releases prior to version 3, the package shipped with its own binary, `mezzio-swoole`, and defined the commands `start`, `stop`, `status`, and `reload`.

Starting with version 3, the package now leverages [laminas-cli](https://docs.laminas.dev/laminas-cli/), exposing the commands `mezzio:swoole:start`, `mezzio:swoole:stop`, `mezzio:swoole:status`, and `mezzio:swoole:reload`.
As such, if you started the server as follows:

```bash
$ ./vendor/bin/mezzio-swoole start
```

you will now start it using:

```bash
$ ./vendor/bin/laminas mezzio:swoole:start
```

Usage of other commands will change similarly.
