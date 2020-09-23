<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Swoole\Command;

use Mezzio\Swoole\Command\StopCommand;
use Mezzio\Swoole\Command\StopCommandFactory;
use Mezzio\Swoole\PidManager;
use MezzioTest\Swoole\AttributeAssertionTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;

class StopCommandFactoryTest extends TestCase
{
    use AttributeAssertionTrait;
    use ProphecyTrait;

    public function testFactoryProducesCommand()
    {
        $pidManager = $this->prophesize(PidManager::class)->reveal();
        $container  = $this->prophesize(ContainerInterface::class);
        $container->get(PidManager::class)->willReturn($pidManager);

        $factory = new StopCommandFactory();

        $command = $factory($container->reveal());

        $this->assertInstanceOf(StopCommand::class, $command);
        $this->assertAttributeSame($pidManager, 'pidManager', $command);
    }
}
