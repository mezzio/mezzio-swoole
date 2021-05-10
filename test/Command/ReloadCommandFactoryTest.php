<?php

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

    public function testFactoryUsesDefaultsToCreateCommandWhenNoConfigPresent(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->with('config')->willReturn(false);

        $factory = new ReloadCommandFactory();

        $command = $factory($container);

        $this->assertInstanceOf(ReloadCommand::class, $command);
    }

    /**
     * @psalm-return iterable<
     *     array-key,
     *     array{
     *         0: array<string, array<string, array<string, mixed>>>,
     *         1: int
     *     }
     * >
     */
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
     * @psalm-param array<string, array<string, array<string, mixed>>> $config
     */
    public function testFactoryUsesConfigToCreateCommandWhenPresent(array $config, int $mode): void
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
