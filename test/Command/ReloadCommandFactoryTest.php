<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Swoole\Command;

use Mezzio\Swoole\Command\ReloadCommand;
use Mezzio\Swoole\Command\ReloadCommandFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

use const SWOOLE_BASE;
use const SWOOLE_PROCESS;

class ReloadCommandFactoryTest extends TestCase
{
    public function testFactoryUsesDefaultsToCreateCommandWhenNoConfigPresent()
    {
        $container = $this->prophesize(ContainerInterface::class);
        $container->has('config')->willReturn(false);
        $container->get('config')->shouldNotBeCalled();

        $factory = new ReloadCommandFactory();

        $command = $factory($container->reveal());

        $this->assertInstanceOf(ReloadCommand::class, $command);
    }

    public function configProvider() : iterable
    {
        yield 'empty' => [
            [],
            SWOOLE_BASE
        ];

        yield 'populated' => [
            ['mezzio-swoole' => [
                'swoole-http-server' => [
                    'mode' => SWOOLE_PROCESS,
                ],
            ]],
            SWOOLE_PROCESS
        ];
    }

    /**
     * @dataProvider configProvider
     */
    public function testFactoryUsesConfigToCreateCommandWhenPresent(array $config, int $mode)
    {
        $container = $this->prophesize(ContainerInterface::class);
        $container->has('config')->willReturn(true);
        $container->get('config')->willReturn($config);

        $factory = new ReloadCommandFactory();

        $command = $factory($container->reveal());

        $this->assertInstanceOf(ReloadCommand::class, $command);
        $this->assertAttributeSame($mode, 'serverMode', $command);
    }
}
