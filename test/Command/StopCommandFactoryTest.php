<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace MezzioTest\Swoole\Command;

use Mezzio\Swoole\Command\StopCommandFactory;
use Mezzio\Swoole\PidManager;
use MezzioTest\Swoole\AttributeAssertionTrait;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class StopCommandFactoryTest extends TestCase
{
    use AttributeAssertionTrait;

    public function testFactoryProducesCommand(): void
    {
        $pidManager = $this->createMock(PidManager::class);
        $container  = $this->createMock(ContainerInterface::class);
        $container->method('get')->with(PidManager::class)->willReturn($pidManager);

        $factory = new StopCommandFactory();

        $command = $factory($container);

        $this->assertAttributeSame($pidManager, 'pidManager', $command);
    }
}
