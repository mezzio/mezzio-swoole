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
use ReflectionProperty;

class AccessLogFactoryTest extends TestCase
{
    use LoggerFactoryHelperTrait;

    public function testCreatesDecoratorWithStdoutLoggerAndAccessLogFormatterWhenNoConfigLoggerOrFormatterPresent()
    {
        $factory = new AccessLogFactory();

        $this->container->has(AccessLogFormatterInterface::class)->willReturn(false);

        $this->container->has(\Zend\Expressive\Swoole\Log\AccessLogFormatterInterface::class)->willReturn(false);
        $this->container->get(AccessLogFormatterInterface::class)->shouldNotBeCalled();
        $this->container->get(\Zend\Expressive\Swoole\Log\AccessLogFormatterInterface::class)->shouldNotBeCalled();

        $logger = $factory($this->createContainerMockWithConfigAndNotPsrLogger());

        $this->assertInstanceOf(Psr3AccessLogDecorator::class, $logger);
        $this->assertAttributeInstanceOf(StdoutLogger::class, 'logger', $logger);
        $this->assertAttributeInstanceOf(AccessLogFormatter::class, 'formatter', $logger);
    }

    public function testUsesConfiguredStandardLoggerServiceWhenPresent()
    {
        $factory = new AccessLogFactory();

        $this->container->has(AccessLogFormatterInterface::class)->willReturn(false);

        $this->container->has(\Zend\Expressive\Swoole\Log\AccessLogFormatterInterface::class)->willReturn(false);
        $this->container->get(AccessLogFormatterInterface::class)->shouldNotBeCalled();
        $this->container->get(\Zend\Expressive\Swoole\Log\AccessLogFormatterInterface::class)->shouldNotBeCalled();

        $logger = $factory($this->createContainerMockWithConfigAndPsrLogger());

        $this->assertInstanceOf(Psr3AccessLogDecorator::class, $logger);
        $this->assertAttributeSame($this->logger, 'logger', $logger);
        $this->assertAttributeInstanceOf(AccessLogFormatter::class, 'formatter', $logger);
    }

    public function testUsesConfiguredCustomLoggerServiceWhenPresent()
    {
        $factory = new AccessLogFactory();

        $this->container->has(AccessLogFormatterInterface::class)->willReturn(false);

        $this->container->has(\Zend\Expressive\Swoole\Log\AccessLogFormatterInterface::class)->willReturn(false);
        $this->container->get(AccessLogFormatterInterface::class)->shouldNotBeCalled();
        $this->container->get(\Zend\Expressive\Swoole\Log\AccessLogFormatterInterface::class)->shouldNotBeCalled();

        $logger = $factory($this->createContainerMockWithNamedLogger());

        $this->assertInstanceOf(Psr3AccessLogDecorator::class, $logger);
        $this->assertAttributeSame($this->logger, 'logger', $logger);
        $this->assertAttributeInstanceOf(AccessLogFormatter::class, 'formatter', $logger);
    }

    public function testUsesConfiguredFormatterServiceWhenPresent()
    {
        $factory = new AccessLogFactory();

        $this->container->has(AccessLogFormatterInterface::class)->willReturn(true);
        $this->container->get(AccessLogFormatterInterface::class)->willReturn($this->formatter);

        $logger = $factory($this->createContainerMockWithConfigAndNotPsrLogger());

        $this->assertInstanceOf(Psr3AccessLogDecorator::class, $logger);
        $this->assertAttributeInstanceOf(StdoutLogger::class, 'logger', $logger);
        $this->assertAttributeSame($this->formatter, 'formatter', $logger);
    }

    public function testUsesConfigurationToSeedGeneratedLoggerAndFormatter()
    {
        $factory = new AccessLogFactory();

        $this->container->has(AccessLogFormatterInterface::class)->willReturn(false);

        $this->container->has(\Zend\Expressive\Swoole\Log\AccessLogFormatterInterface::class)->willReturn(false);
        $this->container->get(AccessLogFormatterInterface::class)->shouldNotBeCalled();
        $this->container->get(\Zend\Expressive\Swoole\Log\AccessLogFormatterInterface::class)->shouldNotBeCalled();

        $logger = $factory($this->createContainerMockWithConfigAndNotPsrLogger([
            'mezzio-swoole' => [
                'swoole-http-server' => [
                    'logger' => [
                        'format' => AccessLogFormatter::FORMAT_COMBINED,
                        'use-hostname-lookups' => true,
                    ],
                ],
            ],
        ]));

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
