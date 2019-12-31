<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Swoole;

use Mezzio\Swoole\PidManager;
use Mezzio\Swoole\PidManagerFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class PidManagerFactoryTest extends TestCase
{
    protected function setUp() : void
    {
        $this->container = $this->prophesize(ContainerInterface::class);
        $this->pidManagerFactory = new PidManagerFactory();
    }

    public function testFactoryReturnsAPidManager()
    {
        $factory = $this->pidManagerFactory;
        $pidManager = $factory($this->container->reveal());
        $this->assertInstanceOf(PidManager::class, $pidManager);
    }
}
