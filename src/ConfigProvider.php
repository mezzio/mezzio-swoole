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
                'options' => [
                    // We set a default for this. Without one, Swoole\Http\Server
                    // defaults to the value of `ulimit -n`. Unfortunately, in
                    // virtualized or containerized environments, this often
                    // reports higher than the host container allows. 1024 is a
                    // sane default; users should check their host system, however,
                    // and set a production value to match.
                    'max_conn' => 1024,
                ],
            ],
        ];
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
