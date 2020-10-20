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
use MezzioTest\Swoole\AttributeAssertionTrait;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

use const SWOOLE_BASE;
use const SWOOLE_PROCESS;

class ReloadCommandFactoryTest extends TestCase
{
    use AttributeAssertionTrait;

    public function testFactoryUsesDefaultsToCreateCommandWhenNoConfigPresent()
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->with('config')->willReturn(false);

        $factory = new ReloadCommandFactory();

        $command = $factory($container);

        $this->assertInstanceOf(ReloadCommand::class, $command);
    }

    public function configProvider(): iterable
    {
        yield 'empty' => [
            [],
            SWOOLE_BASE,
        ];

        yield 'populated' => [
            [
                'mezzio-swoole' => [
                    'swoole-http-server' => [
                        'mode' => SWOOLE_PROCESS,
                    ],
                ],
            ],
            SWOOLE_PROCESS,
        ];
    }

    /**
     * @dataProvider configProvider
     */
    public function testFactoryUsesConfigToCreateCommandWhenPresent(array $config, int $mode)
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->with('config')->willReturn(true);
        $container->method('get')->with('config')->willReturn($config);

        $factory = new ReloadCommandFactory();

        $command = $factory($container);

        $this->assertInstanceOf(ReloadCommand::class, $command);
        $this->assertAttributeSame($mode, 'serverMode', $command);
    }
}
