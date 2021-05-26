<?php

declare(strict_types=1);

namespace MezzioTest\Swoole\Event;

use InvalidArgumentException;
use Mezzio\Swoole\Event\SwooleListenerProviderFactory;
use Mezzio\Swoole\Exception\InvalidListenerException;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use stdClass;

use function iterator_to_array;

class SwooleListenerProviderFactoryTest extends TestCase
{
    /**
     * @psalm-return iterable<array-key, list<mixed>>
     */
    public function invalidListenerTypes(): iterable
    {
        yield 'null'                => [null];
        yield 'true'                => [true];
        yield 'false'               => [false];
        yield 'int'                 => [1];
        yield 'float'               => [1.1];
        yield 'array'               => [['values']];
        yield 'non-callable object' => [(object) []];
    }

    /**
     * @dataProvider invalidListenerTypes
     * @param mixed $invalidListener
     */
    public function testFactoryRaisesErrorForNonCallableNonStringListeners($invalidListener): void
    {
        $config    = [
            'mezzio-swoole' => [
                'swoole-http-server' => [
                    'listeners' => [
                        stdClass::class => [
                            $invalidListener,
                        ],
                    ],
                ],
            ],
        ];
        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->once())
            ->method('has')
            ->with('config')
            ->willReturn(true);
        $container
            ->expects($this->once())
            ->method('get')
            ->with('config')
            ->willReturn($config);

        $factory = new SwooleListenerProviderFactory();

        $this->expectException(InvalidArgumentException::class);
        $factory($container);
    }

    public function testFactoryRaisesErrorForNonexistentListenerService(): void
    {
        $config    = [
            'mezzio-swoole' => [
                'swoole-http-server' => [
                    'listeners' => [
                        stdClass::class => [
                            'ClassDoesNotExist',
                        ],
                    ],
                ],
            ],
        ];
        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->exactly(2))
            ->method('has')
            ->withConsecutive(['config'], ['ClassDoesNotExist'])
            ->willReturnOnConsecutiveCalls(true, false);
        $container
            ->expects($this->once())
            ->method('get')
            ->with('config')
            ->willReturn($config);

        $factory = new SwooleListenerProviderFactory();

        $this->expectException(InvalidListenerException::class);
        $this->expectExceptionMessage('Missing listener service');
        $factory($container);
    }

    public function testFactoryRaisesErrorForNonCallableListenerService(): void
    {
        $config    = [
            'mezzio-swoole' => [
                'swoole-http-server' => [
                    'listeners' => [
                        stdClass::class => [
                            'UncallableListener',
                        ],
                    ],
                ],
            ],
        ];
        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->exactly(2))
            ->method('has')
            ->withConsecutive(['config'], ['UncallableListener'])
            ->willReturnOnConsecutiveCalls(true, true);
        $container
            ->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(['config'], ['UncallableListener'])
            ->willReturnOnConsecutiveCalls($config, new stdClass());

        $factory = new SwooleListenerProviderFactory();

        $this->expectException(InvalidListenerException::class);
        $this->expectExceptionMessage('expected callable or string');
        $factory($container);
    }

    public function testFactoryProducesProviderWithConfiguredListeners(): void
    {
        $listener = function (stdClass $event): void {
        };

        $config    = [
            'mezzio-swoole' => [
                'swoole-http-server' => [
                    'listeners' => [
                        stdClass::class => [
                            'ValidListener',
                        ],
                    ],
                ],
            ],
        ];
        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->exactly(2))
            ->method('has')
            ->withConsecutive(['config'], ['ValidListener'])
            ->willReturnOnConsecutiveCalls(true, true);
        $container
            ->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(['config'], ['ValidListener'])
            ->willReturnOnConsecutiveCalls($config, $listener);

        $factory = new SwooleListenerProviderFactory();

        $provider = $factory($container);

        $listeners = iterator_to_array($provider->getListenersForEvent(new stdClass()));
        $this->assertSame([$listener], $listeners);
    }
}
