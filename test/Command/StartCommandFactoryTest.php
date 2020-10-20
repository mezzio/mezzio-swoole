<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Swoole\Command;

use Mezzio\Swoole\Command\StartCommand;
use Mezzio\Swoole\Command\StartCommandFactory;
use MezzioTest\Swoole\AttributeAssertionTrait;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class StartCommandFactoryTest extends TestCase
{
    use AttributeAssertionTrait;

    public function testFactoryProducesCommand(): void
    {
        $container = $this->createMock(ContainerInterface::class);

        $factory = new StartCommandFactory();

        $command = $factory($container);

        $this->assertInstanceOf(StartCommand::class, $command);
        $this->assertAttributeSame($container, 'container', $command);
    }
}
