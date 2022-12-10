<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace MezzioTest\Swoole;

use Mezzio\Swoole\PidManagerFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class PidManagerFactoryTest extends TestCase
{
    /** @psalm-var MockObject&ContainerInterface */
    private ContainerInterface|MockObject $container;

    private PidManagerFactory $pidManagerFactory;

    protected function setUp(): void
    {
        $this->container         = $this->createMock(ContainerInterface::class);
        $this->pidManagerFactory = new PidManagerFactory();
    }

    public function testFactoryReturnsAPidManager(): void
    {
        $factory = $this->pidManagerFactory;
        $this->assertIsObject($factory($this->container));
    }
}
