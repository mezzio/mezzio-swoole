<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Swoole\Log;

use Mezzio\Swoole\Log\StdoutLogger;
use Mezzio\Swoole\Log\SwooleLoggerFactory;
use PHPUnit\Framework\TestCase;

class SwooleLoggerFactoryTest extends TestCase
{
    use LoggerFactoryHelperTrait;

    public function testReturnsConfiguredNamedLogger()
    {
        $logger = (new SwooleLoggerFactory())($this->createContainerMockWithNamedLogger());
        $this->assertSame($this->logger, $logger);
    }

    public function provideConfigsWithNoNamedLogger() : iterable
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
     */
    public function testReturnsPsrLoggerWhenNoNamedLoggerIsFound(?array $config)
    {
        $logger = (new SwooleLoggerFactory())($this->createContainerMockWithConfigAndPsrLogger($config));
        $this->assertSame($this->logger, $logger);
    }

    /**
     * @dataProvider provideConfigsWithNoNamedLogger
     */
    public function testReturnsStdoutLoggerWhenOtherLoggersAreNotFound(?array $config)
    {
        $logger = (new SwooleLoggerFactory())($this->createContainerMockWithConfigAndNotPsrLogger($config));
        $this->assertInstanceOf(StdoutLogger::class, $logger);
    }
}
