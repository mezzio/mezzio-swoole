<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Swoole\Log;

use Mezzio\Swoole\Log\AccessLogFactory;
use Mezzio\Swoole\Log\AccessLogFormatter;
use Mezzio\Swoole\Log\AccessLogFormatterInterface;
use Mezzio\Swoole\Log\Psr3AccessLogDecorator;
use Mezzio\Swoole\Log\StdoutLogger;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use ReflectionProperty;

class AccessLogFactoryTest extends TestCase
{
    public function setUp()
    {
        $this->container = $this->prophesize(ContainerInterface::class);
        $this->logger = $this->prophesize(LoggerInterface::class)->reveal();
        $this->formatter = $this->prophesize(AccessLogFormatterInterface::class)->reveal();
    }

    public function testCreatesDecoratorWithStdoutLoggerAndAccessLogFormatterWhenNoConfigLoggerOrFormatterPresent()
    {
        $factory = new AccessLogFactory();

        $this->container->has('config')->willReturn(false);
        $this->container->get('config')->shouldNotBeCalled();
        $this->container->has(LoggerInterface::class)->willReturn(false);
        $this->container->get(LoggerInterface::class)->shouldNotBeCalled();
        $this->container->has(AccessLogFormatterInterface::class)->willReturn(false);
        $this->container->has(\Zend\Expressive\Swoole\Log\AccessLogFormatterInterface::class)->willReturn(false);
        $this->container->get(AccessLogFormatterInterface::class)->shouldNotBeCalled();
        $this->container->get(\Zend\Expressive\Swoole\Log\AccessLogFormatterInterface::class)->shouldNotBeCalled();

        $logger = $factory($this->container->reveal());

        $this->assertInstanceOf(Psr3AccessLogDecorator::class, $logger);
        $this->assertAttributeInstanceOf(StdoutLogger::class, 'logger', $logger);
        $this->assertAttributeInstanceOf(AccessLogFormatter::class, 'formatter', $logger);
    }

    public function testUsesConfiguredStandardLoggerServiceWhenPresent()
    {
        $factory = new AccessLogFactory();

        $this->container->has('config')->willReturn(false);
        $this->container->get('config')->shouldNotBeCalled();
        $this->container->has(LoggerInterface::class)->willReturn(true);
        $this->container->get(LoggerInterface::class)->willReturn($this->logger);
        $this->container->has(AccessLogFormatterInterface::class)->willReturn(false);
        $this->container->has(\Zend\Expressive\Swoole\Log\AccessLogFormatterInterface::class)->willReturn(false);
        $this->container->get(AccessLogFormatterInterface::class)->shouldNotBeCalled();
        $this->container->get(\Zend\Expressive\Swoole\Log\AccessLogFormatterInterface::class)->shouldNotBeCalled();

        $logger = $factory($this->container->reveal());

        $this->assertInstanceOf(Psr3AccessLogDecorator::class, $logger);
        $this->assertAttributeSame($this->logger, 'logger', $logger);
        $this->assertAttributeInstanceOf(AccessLogFormatter::class, 'formatter', $logger);
    }

    public function testUsesConfiguredCustomLoggerServiceWhenPresent()
    {
        $factory = new AccessLogFactory();

        $this->container->has('config')->willReturn(true);
        $this->container->get('config')->willReturn([
            'mezzio-swoole' => [
                'swoole-http-server' => [
                    'logger' => [
                        'logger-name' => 'my_logger',
                    ],
                ],
            ],
        ]);
        $this->container->get('my_logger')->willReturn($this->logger);
        $this->container->has(AccessLogFormatterInterface::class)->willReturn(false);
        $this->container->has(\Zend\Expressive\Swoole\Log\AccessLogFormatterInterface::class)->willReturn(false);
        $this->container->get(AccessLogFormatterInterface::class)->shouldNotBeCalled();
        $this->container->get(\Zend\Expressive\Swoole\Log\AccessLogFormatterInterface::class)->shouldNotBeCalled();

        $logger = $factory($this->container->reveal());

        $this->assertInstanceOf(Psr3AccessLogDecorator::class, $logger);
        $this->assertAttributeSame($this->logger, 'logger', $logger);
        $this->assertAttributeInstanceOf(AccessLogFormatter::class, 'formatter', $logger);
    }

    public function testUsesConfiguredFormatterServiceWhenPresent()
    {
        $factory = new AccessLogFactory();

        $this->container->has('config')->willReturn(false);
        $this->container->get('config')->shouldNotBeCalled();
        $this->container->has(LoggerInterface::class)->willReturn(false);
        $this->container->get(LoggerInterface::class)->shouldNotBeCalled();
        $this->container->has(AccessLogFormatterInterface::class)->willReturn(true);
        $this->container->get(AccessLogFormatterInterface::class)->willReturn($this->formatter);

        $logger = $factory($this->container->reveal());

        $this->assertInstanceOf(Psr3AccessLogDecorator::class, $logger);
        $this->assertAttributeInstanceOf(StdoutLogger::class, 'logger', $logger);
        $this->assertAttributeSame($this->formatter, 'formatter', $logger);
    }

    public function testUsesConfigurationToSeedGeneratedLoggerAndFormatter()
    {
        $factory = new AccessLogFactory();

        $this->container->has('config')->willReturn(true);
        $this->container->get('config')->willReturn([
            'mezzio-swoole' => [
                'swoole-http-server' => [
                    'logger' => [
                        'format' => AccessLogFormatter::FORMAT_COMBINED,
                        'use-hostname-lookups' => true,
                    ],
                ],
            ],
        ]);
        $this->container->has(LoggerInterface::class)->willReturn(false);
        $this->container->get(LoggerInterface::class)->shouldNotBeCalled();
        $this->container->has(AccessLogFormatterInterface::class)->willReturn(false);
        $this->container->has(\Zend\Expressive\Swoole\Log\AccessLogFormatterInterface::class)->willReturn(false);
        $this->container->get(AccessLogFormatterInterface::class)->shouldNotBeCalled();
        $this->container->get(\Zend\Expressive\Swoole\Log\AccessLogFormatterInterface::class)->shouldNotBeCalled();

        $logger = $factory($this->container->reveal());

        $this->assertInstanceOf(Psr3AccessLogDecorator::class, $logger);
        $this->assertAttributeInstanceOf(StdoutLogger::class, 'logger', $logger);
        $this->assertAttributeInstanceOf(AccessLogFormatter::class, 'formatter', $logger);
        $this->assertAttributeSame(true, 'useHostnameLookups', $logger);

        $r = new ReflectionProperty($logger, 'formatter');
        $r->setAccessible(true);
        $formatter = $r->getValue($logger);

        $this->assertAttributeSame(AccessLogFormatter::FORMAT_COMBINED, 'format', $formatter);
    }
}
