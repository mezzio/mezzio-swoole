<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Swoole\HotCodeReload;

use Mezzio\Swoole\HotCodeReload\FileWatcherInterface;
use Mezzio\Swoole\HotCodeReload\Reloader;
use PHPUnit\Framework\Constraint\Constraint;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Swoole\Server as SwooleServer;

class ReloaderTest extends TestCase
{
    /** @var FileWatcherInterface|MockObject */
    private $fileWatcher;

    /** @var int */
    private $interval = 123;

    /** @var Reloader */
    private $subject;

    protected function setUp(): void
    {
        $this->fileWatcher = $this->createMock(FileWatcherInterface::class);
        $this->subject     = new Reloader($this->fileWatcher, new NullLogger(), $this->interval);

        parent::setUp();
    }

    /**
     * Creates a constraint that checks if every value is a unique scalar value.
     * Uniqueness is checked by adding values as an array key until a repeat occurs.
     */
    private static function isUniqueScalar(): Constraint
    {
        return new class () extends Constraint {
            private $values = [];

            /**
             * @param string|int $other
             */
            protected function matches($other): bool
            {
                if (isset($this->values[$other])) {
                    return false;
                }

                return $this->values[$other] = true;
            }

            public function toString(): string
            {
                return 'is only used once';
            }
        };
    }

    public function testOnWorkerStartOnlyRegistersTickFunctionOnFirstServer(): void
    {
        $server0 = $this->createMock(SwooleServer::class);
        $server0
            ->expects(static::once())
            ->method('tick')
            ->with(
                $this->interval,
                static::callback(static function (callable $callback) use ($server0) {
                    $callback($server0);

                    return true;
                })
            );

        $server1 = $this->createMock(SwooleServer::class);
        $server1
            ->expects(static::never())
            ->method('tick');

        $this->subject->onWorkerStart($server0, 0);
        $this->subject->onWorkerStart($server1, 1);
    }

    public function testIncludedFilesAreOnlyAddedToWatchOnce(): void
    {
        $this->fileWatcher
            ->expects(static::atLeastOnce())
            ->method('addFilePath')
            ->with(static::isUniqueScalar());

        $server = $this->createMock(SwooleServer::class);
        $server->expects(static::never())->method('reload');
        $this->subject->onTick($server);
        $this->subject->onTick($server);
    }

    public function testServerReloadedWhenFilesChange(): void
    {
        $this->fileWatcher
            ->expects(static::once())
            ->method('readChangedFilePaths')
            ->willReturn([
                '/foo.php',
                '/bar.php',
            ]);

        $server = $this->createMock(SwooleServer::class);
        $server
            ->expects(static::once())
            ->method('reload');

        $this->subject->onTick($server);
    }
}
