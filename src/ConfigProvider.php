<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\Swoole;

use Laminas\HttpHandlerRunner\RequestHandlerRunner;
use Mezzio\Swoole\HotCodeReload\FileWatcher\InotifyFileWatcher;
use Mezzio\Swoole\HotCodeReload\FileWatcherInterface;
use Mezzio\Swoole\HotCodeReload\Reloader;
use Mezzio\Swoole\HotCodeReload\ReloaderFactory;
use Mezzio\Swoole\StaticResourceHandler\FileLocationRepository;
use Mezzio\Swoole\StaticResourceHandler\FileLocationRepositoryFactory;
use Mezzio\Swoole\StaticResourceHandler\FileLocationRepositoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Swoole\Http\Server as SwooleHttpServer;

use function extension_loaded;
use function getcwd;

use const PHP_SAPI;

class ConfigProvider
{
    public function __invoke(): array
    {
        $config = PHP_SAPI === 'cli' && extension_loaded('swoole')
            ? ['dependencies' => $this->getDependencies()]
            : [];

        $config['mezzio-swoole'] = $this->getDefaultConfig();

        return $config;
    }

    public function getDefaultConfig(): array
    {
        return [
            'application_root'   => getcwd(),
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
                'static-files' => [
                    'enable' => true,
                ],
                'listeners'    => [
                    Event\ServerStartEvent::class => [
                        Event\ServerStartListener::class,
                    ],
                    // To enable hot code reloading, add the following to your
                    // own configuration:
                    // <code>
                    //     Event\WorkerStartEvent::class => [
                    //         \Mezzio\Swoole\Event\HotCodeReloaderWorkerStartListener::class,
                    //     ]),
                    // </code>
                    Event\WorkerStartEvent::class => [
                        Event\WorkerStartListener::class,
                    ],
                    // To disable the StaticResourceRequestListener, set the
                    // value of this event to:
                    // <code>
                    //     new \Laminas\Stdlib\ArrayUtils\MergeReplaceKey([
                    //         Event\RequestHandlerRequestListener::class,
                    //     ]),
                    // </code>
                    Event\RequestEvent::class        => [
                        Event\StaticResourceRequestListener::class,
                        Event\RequestHandlerRequestListener::class,
                    ],
                    Event\ServerShutdownEvent::class => [
                        Event\ServerShutdownListener::class,
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
                Command\ReloadCommand::class                    => Command\ReloadCommandFactory::class,
                Command\StartCommand::class                     => Command\StartCommandFactory::class,
                Command\StatusCommand::class                    => Command\StatusCommandFactory::class,
                Command\StopCommand::class                      => Command\StopCommandFactory::class,
                Event\EventDispatcherInterface::class           => Event\EventDispatcherFactory::class,
                Event\HotCodeReloaderWorkerStartListener::class => Event\HotCodeReloaderWorkerStartListenerFactory::class,
                Event\RequestHandlerRequestListener::class      => Event\RequestHandlerRequestListenerFactory::class,
                Event\ServerShutdownListener::class             => Event\ServerShutdownListenerFactory::class,
                Event\ServerStartListener::class                => Event\ServerStartListenerFactory::class,
                Event\StaticResourceRequestListener::class      => Event\StaticResourceRequestListenerFactory::class,
                Event\SwooleListenerProvider::class             => Event\SwooleListenerProviderFactory::class,
                Event\WorkerStartListener::class                => Event\WorkerStartListenerFactory::class,
                Log\AccessLogInterface::class                   => Log\AccessLogFactory::class,
                Log\SwooleLoggerFactory::SWOOLE_LOGGER          => Log\SwooleLoggerFactory::class,
                PidManager::class                               => PidManagerFactory::class,
                SwooleRequestHandlerRunner::class               => SwooleRequestHandlerRunnerFactory::class,
                ServerRequestInterface::class                   => ServerRequestSwooleFactory::class,
                StaticResourceHandler::class                    => StaticResourceHandlerFactory::class,
                StaticMappedResourceHandler::class              => StaticMappedResourceHandlerFactory::class,
                SwooleHttpServer::class                         => HttpServerFactory::class,
                Reloader::class                                 => ReloaderFactory::class,
                FileLocationRepository::class                   => FileLocationRepositoryFactory::class,
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
}
