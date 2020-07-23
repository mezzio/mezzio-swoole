<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\Swoole;

use Mezzio\ApplicationPipeline;
use Mezzio\Response\ServerRequestErrorResponseGenerator;
use Mezzio\Swoole\Event\SwooleWorkerDispatcher;
use Mezzio\Swoole\Event\WorkerListenerProviderInterface;
use Mezzio\Swoole\HotCodeReload\Reloader;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Swoole\Http\Server as SwooleHttpServer;

class SwooleRequestHandlerRunnerFactory
{
    public function __invoke(ContainerInterface $container): SwooleRequestHandlerRunner
    {
        $logger = $container->has(Log\AccessLogInterface::class)
            ? $container->get(Log\AccessLogInterface::class)
            : null;

        $mezzioSwooleConfig = $container->has('config')
            ? $container->get('config')['mezzio-swoole']
            : [];

        $swooleHttpServerConfig = $mezzioSwooleConfig['swoole-http-server'] ?? [];

        return new SwooleRequestHandlerRunner(
            $container->get(ApplicationPipeline::class),
            $container->get(ServerRequestInterface::class),
            $container->get(ServerRequestErrorResponseGenerator::class),
            $container->get(PidManager::class),
            $container->get(SwooleHttpServer::class),
            $this->retrieveStaticResourceHandler($container, $swooleHttpServerConfig),
            $logger,
            $swooleHttpServerConfig['process-name'] ?? SwooleRequestHandlerRunner::DEFAULT_PROCESS_NAME,
            $this->retrieveHotCodeReloader($container, $mezzioSwooleConfig),
            $container->get(WorkerListenerProviderInterface::class)
        );
    }

    private function retrieveStaticResourceHandler(
        ContainerInterface $container,
        array $config
    ): ?StaticResourceHandlerInterface {
        $config  = $config['static-files'] ?? [];
        $enabled = isset($config['enable']) && true === $config['enable'];

        return $enabled && $container->has(StaticResourceHandlerInterface::class)
            ? $container->get(StaticResourceHandlerInterface::class)
            : null;
    }

    private function retrieveHotCodeReloader(
        ContainerInterface $container,
        array $config
    ): ?Reloader {
        $config  = $config['hot-code-reload'] ?? [];
        $enabled = isset($config['enable']) && true === $config['enable'];

        return $enabled && $container->has(Reloader::class)
            ? $container->get(Reloader::class)
            : null;
    }
}
