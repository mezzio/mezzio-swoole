<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace MezzioTest\Swoole\Task;

use Mezzio\Swoole\Task\Task;
use MezzioTest\Swoole\TestAsset\CallableObject;
use MezzioTest\Swoole\TestAsset\ClassWithCallbacks;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class TaskTest extends TestCase
{
    public function testInvocationReturnsResultOfExecutingCallableWithPayloadArguments(): void
    {
        $expected = 'expected string';
        $first    = 'first';
        $second   = 2;
        $third    = true;
        $handler  = function (string $one, int $two, bool $three) use ($expected, $first, $second, $third): string {
            TestCase::assertSame($first, $one);
            TestCase::assertSame($second, $two);
            TestCase::assertSame($third, $three);
            return $expected;
        };

        $task = new Task($handler, $first, $second, $third);

        $this->assertSame($expected, $task($this->createMock(ContainerInterface::class)));
    }

    /**
     * @psalm-return iterable<array-key, array{0: callable, 1: string}>
     */
    public function provideHandlers(): iterable
    {
        yield 'object' => [
            new CallableObject(),
            CallableObject::class,
        ];

        yield 'function' => [
            'strtolower',
            'strtolower',
        ];

        $staticCallback = ClassWithCallbacks::class . '::staticCallback';
        yield 'static method, string notation' => [
            $staticCallback,
            $staticCallback,
        ];

        yield 'static method, array notation' => [
            [ClassWithCallbacks::class, 'staticCallback'],
            $staticCallback,
        ];

        yield 'instance method, array notation' => [
            [new ClassWithCallbacks(), 'instanceCallback'],
            ClassWithCallbacks::class . '::instanceCallback',
        ];
    }

    /**
     * @dataProvider provideHandlers
     */
    public function testSerializesTaskPerExpectations(callable $handler, string $expected): void
    {
        $task = new Task($handler, 'one', 'two', 'three');

        $this->assertSame(
            [
                'handler'   => $expected,
                'arguments' => ['one', 'two', 'three'],
            ],
            $task->jsonSerialize()
        );
    }
}
