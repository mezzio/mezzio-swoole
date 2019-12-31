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
use Swoole\Http\Server as SwooleHttpServer;

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
                \Zend\HttpHandlerRunner\RequestHandlerRunner::class => RequestHandlerRunner::class,
            ],
            'factories'  => [
                ServerRequestInterface::class => ServerRequestSwooleFactory::class,
                RequestHandlerRunner::class   => RequestHandlerSwooleRunnerFactory::class,
                SwooleHttpServer::class       => SwooleHttpServerFactory::class
            ]
        ];
    }
}
