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
use Mezzio\Swoole\StaticResourceHandler\{
    FileLocationRepository, FileLocationRepositoryFactory, FileLocationRepositoryInterface
};
use Psr\Http\Message\ServerRequestInterface;
use Swoole\Http\Server as SwooleHttpServer;

use function extension_loaded;

use const PHP_SAPI;

class ConfigProvider
{
    public function __invoke() : array
    {
        $config = PHP_SAPI === 'cli' && extension_loaded('swoole')
            ? ['dependencies' => $this->getDependencies()]
            : [];

        $config['mezzio-swoole'] = $this->getDefaultConfig();

        return $config;
    }

    public function getDefaultConfig() : array
    {
        return [
            'swoole-http-server' => [
                // A prefix for the process name of the master process and workers.
                // By default the master process will be named `mezzio-master`,
                // each http worker `mezzio-worker-n` and each task worker
                // `mezzio-task-worker-n` where n is the id of the worker
                'process-name' => 'mezzio',
                'options' => [
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
            ],
        ];
    }

    public function getDependencies() : array
    {
        return [
            'factories'  => [
                Command\ReloadCommand::class           => Command\ReloadCommandFactory::class,
                Command\StartCommand::class            => Command\StartCommandFactory::class,
                Command\StatusCommand::class           => Command\StatusCommandFactory::class,
                Command\StopCommand::class             => Command\StopCommandFactory::class,
                Log\AccessLogInterface::class          => Log\AccessLogFactory::class,
                Log\SwooleLoggerFactory::SWOOLE_LOGGER => Log\SwooleLoggerFactory::class,
                PidManager::class                      => PidManagerFactory::class,
                SwooleRequestHandlerRunner::class      => SwooleRequestHandlerRunnerFactory::class,
                ServerRequestInterface::class          => ServerRequestSwooleFactory::class,
                StaticResourceHandler::class           => StaticResourceHandlerFactory::class,
                SwooleHttpServer::class                => HttpServerFactory::class,
                Reloader::class                        => ReloaderFactory::class,
                FileLocationRepository::class          => FileLocationRepositoryFactory::class,
            ],
            'invokables' => [
                InotifyFileWatcher::class => InotifyFileWatcher::class,
            ],
            'aliases' => [
                RequestHandlerRunner::class            => SwooleRequestHandlerRunner::class,
                StaticResourceHandlerInterface::class  => StaticResourceHandler::class,
                FileWatcherInterface::class            => InotifyFileWatcher::class,
                FileLocationRepositoryInterface::class => FileLocationRepository::class,

                // Legacy Zend Framework aliases
                \Zend\Expressive\Swoole\Command\ReloadCommand::class => Command\ReloadCommand::class,
                \Zend\Expressive\Swoole\Command\StartCommand::class => Command\StartCommand::class,
                \Zend\Expressive\Swoole\Command\StatusCommand::class => Command\StatusCommand::class,
                \Zend\Expressive\Swoole\Command\StopCommand::class => Command\StopCommand::class,
                \Zend\Expressive\Swoole\Log\AccessLogInterface::class => Log\AccessLogInterface::class,
                \Zend\Expressive\Swoole\PidManager::class => PidManager::class,
                \Zend\Expressive\Swoole\SwooleRequestHandlerRunner::class => SwooleRequestHandlerRunner::class,
                \Zend\Expressive\Swoole\StaticResourceHandler::class => StaticResourceHandler::class,
                \Zend\Expressive\Swoole\HotCodeReload\Reloader::class => Reloader::class,
                \Zend\HttpHandlerRunner\RequestHandlerRunner::class => RequestHandlerRunner::class,
                \Zend\Expressive\Swoole\StaticResourceHandlerInterface::class => StaticResourceHandlerInterface::class,
                \Zend\Expressive\Swoole\HotCodeReload\FileWatcherInterface::class => FileWatcherInterface::class,
            ],
            'delegators' => [
                'Mezzio\WhoopsPageHandler' => [
                    WhoopsPrettyPageHandlerDelegator::class,
                ],
            ],
        ];
    }
}
