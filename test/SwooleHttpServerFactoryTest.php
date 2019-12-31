<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Swoole;

use Mezzio\Swoole\SwooleHttpServerFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Swoole\Http\Server as SwooleHttpServer;
use Swoole\Process;

class SwooleHttpServerFactoryTest extends TestCase
{
    public function setUp()
    {
        $this->container = $this->prophesize(ContainerInterface::class);
        $this->swooleFactory = new SwooleHttpServerFactory();
    }

    public function testConstructor()
    {
        $this->assertInstanceOf(SwooleHttpServerFactory::class, $this->swooleFactory);
    }

    public function testInvokeWithoutConfig()
    {
        $process = new Process(function ($worker) {
            $server = ($this->swooleFactory)($this->container->reveal());
            $worker->write(sprintf('%s:%d', $server->host, $server->port));
            $worker->exit(0);
        }, true, 1);
        $process->start();
        $data = $process->read();
        Process::wait(true);

        $this->assertSame(
            sprintf('%s:%d', SwooleHttpServerFactory::DEFAULT_HOST, SwooleHttpServerFactory::DEFAULT_PORT),
            $data
        );
    }

    public function testInvokeWithConfig()
    {
        $config = [
            'mezzio-swoole' => [
                'swoole-http-server' => [
                    'host' => 'localhost',
                    'port' => 9501,
                ]
            ]
        ];
        $this->container
            ->get('config')
            ->willReturn($config);

        $process = new Process(function ($worker) {
            $server = ($this->swooleFactory)($this->container->reveal());
            $worker->write(sprintf('%s:%d', $server->host, $server->port));
            $worker->exit(0);
        }, true, 1);
        $process->start();
        $data = $process->read();
        Process::wait(true);

        $this->assertSame('localhost:9501', $data);
    }
}
