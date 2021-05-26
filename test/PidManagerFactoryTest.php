<?php

declare(strict_types=1);

namespace MezzioTest\Swoole;

use Mezzio\Swoole\PidManagerFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class PidManagerFactoryTest extends TestCase
{
    /**
     * @var ContainerInterface|MockObject
     * @psalm-var MockObject&ContainerInterface
     */
    private $container;

    /** @var PidManagerFactory */
    private $pidManagerFactory;

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
