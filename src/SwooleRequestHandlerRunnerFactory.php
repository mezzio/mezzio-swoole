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
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;

class SwooleRequestHandlerRunnerFactory
{
    public function __invoke(ContainerInterface $container) : SwooleRequestHandlerRunner
    {
        $staticResourceHandler = $container->has(StaticResourceHandlerInterface::class)
            ? $container->get(StaticResourceHandlerInterface::class)
            : ($container->has(\Zend\Expressive\Swoole\StaticResourceHandlerInterface::class)
                ? $container->get(\Zend\Expressive\Swoole\StaticResourceHandlerInterface::class)
                : null);
        $logger = $container->has(Log\AccessLogInterface::class)
            ? $container->get(Log\AccessLogInterface::class)
            : ($container->has(\Zend\Expressive\Swoole\Log\AccessLogInterface::class)
                ? $container->get(\Zend\Expressive\Swoole\Log\AccessLogInterface::class)
                : null);

        return new SwooleRequestHandlerRunner(
            $container->get(ApplicationPipeline::class),
            $container->get(ServerRequestInterface::class),
            $container->get(ServerRequestErrorResponseGenerator::class),
            $container->get(PidManager::class),
            $container->get(ServerFactory::class),
            $staticResourceHandler,
            $logger
        );
    }
}
