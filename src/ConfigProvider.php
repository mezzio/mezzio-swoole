<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace Mezzio\Swoole;

use Laminas\HttpHandlerRunner\RequestHandlerRunner;
use Mezzio\Swoole\Command\ReloadCommand;
use Mezzio\Swoole\Command\ReloadCommandFactory;
use Mezzio\Swoole\Command\StartCommand;
use Mezzio\Swoole\Command\StartCommandFactory;
use Mezzio\Swoole\Command\StatusCommand;
use Mezzio\Swoole\Command\StatusCommandFactory;
use Mezzio\Swoole\Command\StopCommand;
use Mezzio\Swoole\Command\StopCommandFactory;
use Mezzio\Swoole\Event\EventDispatcherFactory;
use Mezzio\Swoole\Event\EventDispatcherInterface;
use Mezzio\Swoole\Event\HotCodeReloaderWorkerStartListener;
use Mezzio\Swoole\Event\HotCodeReloaderWorkerStartListenerFactory;
use Mezzio\Swoole\Event\RequestEvent;
use Mezzio\Swoole\Event\RequestHandlerRequestListener;
use Mezzio\Swoole\Event\RequestHandlerRequestListenerFactory;
use Mezzio\Swoole\Event\ServerShutdownEvent;
use Mezzio\Swoole\Event\ServerShutdownListener;
use Mezzio\Swoole\Event\ServerShutdownListenerFactory;
use Mezzio\Swoole\Event\ServerStartEvent;
use Mezzio\Swoole\Event\ServerStartListener;
use Mezzio\Swoole\Event\ServerStartListenerFactory;
use Mezzio\Swoole\Event\StaticResourceRequestListener;
use Mezzio\Swoole\Event\StaticResourceRequestListenerFactory;
use Mezzio\Swoole\Event\SwooleListenerProvider;
use Mezzio\Swoole\Event\SwooleListenerProviderFactory;
use Mezzio\Swoole\Event\WorkerStartEvent;
use Mezzio\Swoole\Event\WorkerStartListener;
use Mezzio\Swoole\Event\WorkerStartListenerFactory;
use Mezzio\Swoole\Exception\ExtensionNotLoadedException;
use Mezzio\Swoole\HotCodeReload\FileWatcher\InotifyFileWatcher;
use Mezzio\Swoole\HotCodeReload\FileWatcherInterface;
use Mezzio\Swoole\Log\AccessLogFactory;
use Mezzio\Swoole\Log\AccessLogInterface;
use Mezzio\Swoole\Log\SwooleLoggerFactory;
use Mezzio\Swoole\StaticResourceHandler\FileLocationRepository;
use Mezzio\Swoole\StaticResourceHandler\FileLocationRepositoryFactory;
use Mezzio\Swoole\StaticResourceHandler\FileLocationRepositoryInterface;
use Mezzio\Swoole\Task\TaskEventDispatchListener;
use Mezzio\Swoole\Task\TaskEventDispatchListenerFactory;
use Mezzio\Swoole\Task\TaskInvokerListener;
use Mezzio\Swoole\Task\TaskInvokerListenerFactory;
use Psr\Http\Message\ServerRequestInterface;
use Swoole\Http\Server as SwooleHttpServer;

use function extension_loaded;
use function getcwd;

use const PHP_SAPI;

/**
 * Provide component configuration to the application.
 *
 * NOTE: this provider should be aggregated AFTER the laminas/laminas-httphandlerrunner
 * config provider (class Laminas\HttpHandlerRunner\ConfigProvider) to ensure
 * that its SwooleRequestHandlerRunner is used as the application request handler runner.
 */
class ConfigProvider
{
    public function __invoke(): array
    {
        if (! extension_loaded('swoole') && ! extension_loaded('openswoole')) {
            throw new ExtensionNotLoadedException(
                'One of either the Swoole (https://github.com/swoole/swoole-src) or'
                . ' Open Swoole (https://www.swoole.co.uk) extensions must be loaded'
                . ' to use mezzio/mezzio-swoole'
            );
        }

        $config = PHP_SAPI === 'cli'
            ? ['dependencies' => $this->getDependencies()]
            : [];

        $config['mezzio-swoole'] = $this->getDefaultConfig();
        $config['laminas-cli']   = $this->getConsoleConfig();

        return $config;
    }

    public function getDefaultConfig(): array
    {
        return [
            'application_root'   => getcwd(),
            'hot-code-reload'    => [
                // Interval, in ms, that the InotifyFileWatcher should use to
                // check for changes.
                'interval' => 500,
                // Paths to watch for changes. These may be files or
                // directories.
                'paths' => [],
            ],
            'swoole-http-server' => [
                // A prefix for the process name of the master process and workers.
                // By default the master process will be named `mezzio-master`,
                // each http worker `mezzio-worker-n` and each task worker
                // `mezzio-task-worker-n` where n is the id of the worker
                'process-name' => SwooleRequestHandlerRunner::DEFAULT_PROCESS_NAME,
                'options'      => [
                    // We set a default for this. Without one, Swoole\Http\Server
                    // defaults to the value of `ulimit -n`. Unfortunately, in
                    // virtualized or containerized environments, this often
                    // reports higher than the host container allows. 1024 is a
                    // sane default; users should check their host system, however,
                    // and set a production value to match.
                    'max_conn' => 1024,
                ],
                'listeners'    => [
                    ServerStartEvent::class => [
                        ServerStartListener::class,
                    ],
                    // To enable hot code reloading, add the following to your
                    // own configuration:
                    // <code>
                    //     Event\WorkerStartEvent::class => [
                    //         \Mezzio\Swoole\Event\HotCodeReloaderWorkerStartListener::class,
                    //     ]),
                    // </code>
                    WorkerStartEvent::class => [
                        WorkerStartListener::class,
                    ],
                    // To disable the StaticResourceRequestListener, set the
                    // value of this event to:
                    // <code>
                    //     new \Laminas\Stdlib\ArrayUtils\MergeReplaceKey([
                    //         Event\RequestHandlerRequestListener::class,
                    //     ]),
                    // </code>
                    RequestEvent::class        => [
                        StaticResourceRequestListener::class,
                        RequestHandlerRequestListener::class,
                    ],
                    ServerShutdownEvent::class => [
                        ServerShutdownListener::class,
                    ],
                ],
            ],
        ];
    }

    public function getDependencies(): array
    {
        // phpcs:disable Generic.Files.LineLength.TooLong
        return [
            'factories'  => [
                ReloadCommand::class                      => ReloadCommandFactory::class,
                StartCommand::class                       => StartCommandFactory::class,
                StatusCommand::class                      => StatusCommandFactory::class,
                StopCommand::class                        => StopCommandFactory::class,
                EventDispatcherInterface::class           => EventDispatcherFactory::class,
                HotCodeReloaderWorkerStartListener::class => HotCodeReloaderWorkerStartListenerFactory::class,
                RequestHandlerRequestListener::class      => RequestHandlerRequestListenerFactory::class,
                ServerShutdownListener::class             => ServerShutdownListenerFactory::class,
                ServerStartListener::class                => ServerStartListenerFactory::class,
                StaticResourceRequestListener::class      => StaticResourceRequestListenerFactory::class,
                SwooleListenerProvider::class             => SwooleListenerProviderFactory::class,
                WorkerStartListener::class                => WorkerStartListenerFactory::class,
                AccessLogInterface::class                 => AccessLogFactory::class,
                SwooleLoggerFactory::SWOOLE_LOGGER        => SwooleLoggerFactory::class,
                PidManager::class                         => PidManagerFactory::class,
                SwooleRequestHandlerRunner::class         => SwooleRequestHandlerRunnerFactory::class,
                ServerRequestInterface::class             => ServerRequestSwooleFactory::class,
                StaticResourceHandler::class              => StaticResourceHandlerFactory::class,
                StaticMappedResourceHandler::class        => StaticMappedResourceHandlerFactory::class,
                SwooleHttpServer::class                   => HttpServerFactory::class,
                TaskEventDispatchListener::class          => TaskEventDispatchListenerFactory::class,
                TaskInvokerListener::class                => TaskInvokerListenerFactory::class,
                FileLocationRepository::class             => FileLocationRepositoryFactory::class,
            ],
            'invokables' => [
                InotifyFileWatcher::class => InotifyFileWatcher::class,
            ],
            'aliases'    => [
                RequestHandlerRunner::class            => SwooleRequestHandlerRunner::class,
                StaticResourceHandlerInterface::class  => StaticResourceHandler::class,
                FileWatcherInterface::class            => InotifyFileWatcher::class,
                FileLocationRepositoryInterface::class => FileLocationRepository::class,
            ],
            'delegators' => [
                'Mezzio\WhoopsPageHandler' => [
                    WhoopsPrettyPageHandlerDelegator::class,
                ],
            ],
        ];
        // phpcs:enable
    }

    public function getConsoleConfig(): array
    {
        return [
            'commands' => [
                'mezzio:swoole:reload' => ReloadCommand::class,
                'mezzio:swoole:start'  => StartCommand::class,
                'mezzio:swoole:status' => StatusCommand::class,
                'mezzio:swoole:stop'   => StopCommand::class,
            ],
        ];
    }
}
