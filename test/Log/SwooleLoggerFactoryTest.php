<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace MezzioTest\Swoole\Log;

use Mezzio\Swoole\Log\StdoutLogger;
use Mezzio\Swoole\Log\SwooleLoggerFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class SwooleLoggerFactoryTest extends TestCase
{
    use LoggerFactoryHelperTrait;

    /**
     * @var LoggerInterface|MockObject
     * @psalm-var LoggerInterface&MockObject
     */
    private $logger;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    public function testReturnsConfiguredNamedLogger(): void
    {
        $logger = (new SwooleLoggerFactory())($this->createContainerMockWithNamedLogger());
        $this->assertSame($this->logger, $logger);
    }

    /**
     * @psalm-return iterable<array-key, list<null|array<string, mixed>>>
     */
    public static function provideConfigsWithNoNamedLogger(): iterable
    {
        yield 'no config' => [null];
        yield 'empty config' => [[]];
        yield 'empty mezzio-swoole' => [['mezzio-swoole' => []]];
        yield 'empty swoole-http-server' => [
            [
                'mezzio-swoole' => [
                    'swoole-http-server' => [],
                ],
            ],
        ];
        yield 'empty logger' => [
            [
                'mezzio-swoole' => [
                    'swoole-http-server' => [
                        'logger' => [],
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider provideConfigsWithNoNamedLogger
     * @psalm-param null|array<string, mixed> $config
     */
    public function testReturnsPsrLoggerWhenNoNamedLoggerIsFound(?array $config): void
    {
        $logger = (new SwooleLoggerFactory())($this->createContainerMockWithConfigAndPsrLogger([], $config));
        $this->assertSame($this->logger, $logger);
    }

    /**
     * @dataProvider provideConfigsWithNoNamedLogger
     * @psalm-param null|array<string, mixed> $config
     */
    public function testReturnsStdoutLoggerWhenOtherLoggersAreNotFound(?array $config): void
    {
        $logger = (new SwooleLoggerFactory())($this->createContainerMockWithConfigAndNotPsrLogger([], $config));
        $this->assertInstanceOf(StdoutLogger::class, $logger);
    }
}
