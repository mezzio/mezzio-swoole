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
use MezzioTest\Swoole\AttributeAssertionTrait;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Zend\Expressive\Swoole\Log\AccessLogFormatterInterface as LegacyAccessLogFormatterInterface;

class AccessLogFactoryTest extends TestCase
{
    use AttributeAssertionTrait;
    use LoggerFactoryHelperTrait;

    public function testCreatesDecoratorWithStdoutLoggerAndAccessLogFormatterWhenNoConfigLoggerOrFormatterPresent()
    {
        $factory = new AccessLogFactory();
        $logger  = $factory($this->createContainerMockWithConfigAndNotPsrLogger([
            'has' => [
                AccessLogFormatterInterface::class       => false,
                LegacyAccessLogFormatterInterface::class => false,
            ],
        ]));

        $this->assertInstanceOf(Psr3AccessLogDecorator::class, $logger);
        $this->assertAttributeInstanceOf(StdoutLogger::class, 'logger', $logger);
        $this->assertAttributeInstanceOf(AccessLogFormatter::class, 'formatter', $logger);
    }

    public function testUsesConfiguredStandardLoggerServiceWhenPresent()
    {
        $factory = new AccessLogFactory();
        $logger  = $factory($this->createContainerMockWithConfigAndPsrLogger([
            'has' => [
                AccessLogFormatterInterface::class       => false,
                LegacyAccessLogFormatterInterface::class => false,
            ],
        ]));

        $this->assertInstanceOf(Psr3AccessLogDecorator::class, $logger);
        $this->assertAttributeSame($this->logger, 'logger', $logger);
        $this->assertAttributeInstanceOf(AccessLogFormatter::class, 'formatter', $logger);
    }

    public function testUsesConfiguredCustomLoggerServiceWhenPresent()
    {
        $factory = new AccessLogFactory();
        $logger  = $factory($this->createContainerMockWithNamedLogger([
            'has' => [
                AccessLogFormatterInterface::class       => false,
                LegacyAccessLogFormatterInterface::class => false,
            ],
        ]));

        $this->assertInstanceOf(Psr3AccessLogDecorator::class, $logger);
        $this->assertAttributeSame($this->logger, 'logger', $logger);
        $this->assertAttributeInstanceOf(AccessLogFormatter::class, 'formatter', $logger);
    }

    public function testUsesConfiguredFormatterServiceWhenPresent()
    {
        $factory = new AccessLogFactory();
        $logger  = $factory($this->createContainerMockWithConfigAndNotPsrLogger([
            'has' => [AccessLogFormatterInterface::class => true],
            'get' => [AccessLogFormatterInterface::class => $this->formatter],
        ]));

        $this->assertInstanceOf(Psr3AccessLogDecorator::class, $logger);
        $this->assertAttributeInstanceOf(StdoutLogger::class, 'logger', $logger);
        $this->assertAttributeSame($this->formatter, 'formatter', $logger);
    }

    public function testUsesConfigurationToSeedGeneratedLoggerAndFormatter()
    {
        $factory = new AccessLogFactory();
        $logger  = $factory($this->createContainerMockWithConfigAndNotPsrLogger(
            [
                'has' => [
                    AccessLogFormatterInterface::class       => false,
                    LegacyAccessLogFormatterInterface::class => false,
                ],
            ],
            [
                'mezzio-swoole' => [
                    'swoole-http-server' => [
                        'logger' => [
                            'format'               => AccessLogFormatter::FORMAT_COMBINED,
                            'use-hostname-lookups' => true,
                        ],
                    ],
                ],
            ],
        ));

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
