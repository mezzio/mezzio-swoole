<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\Swoole;

use Laminas\HttpHandlerRunner\RequestHandlerRunner;
use Psr\Http\Message\ServerRequestInterface;

class ConfigProvider
{
    public function __invoke() : array
    {
        return PHP_SAPI !== 'cli' || ! extension_loaded('swoole')
            ? []
            : ['dependencies' => $this->getDependencies()];
    }

    public function getDependencies() : array
    {
        return [
            // Legacy Zend Framework aliases
            'aliases' => [
                \Zend\Expressive\Swoole\Log\AccessLogInterface::class => Log\AccessLogInterface::class,
                \Zend\Expressive\Swoole\PidManager::class => PidManager::class,
                \Zend\HttpHandlerRunner\RequestHandlerRunner::class => RequestHandlerRunner::class,
                \Zend\Expressive\Swoole\ServerFactory::class => ServerFactory::class,
                \Zend\Expressive\Swoole\StaticResourceHandlerInterface::class => StaticResourceHandlerInterface::class,
            ],
            'factories'  => [
                Log\AccessLogInterface::class         => Log\AccessLogFactory::class,
                PidManager::class                     => PidManagerFactory::class,
                RequestHandlerRunner::class           => SwooleRequestHandlerRunnerFactory::class,
                ServerFactory::class                  => ServerFactoryFactory::class,
                ServerRequestInterface::class         => ServerRequestSwooleFactory::class,
                StaticResourceHandlerInterface::class => StaticResourceHandlerFactory::class,
            ],
        ];
    }
}
