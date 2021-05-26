<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace MezzioTest\Swoole\Log;

use Mezzio\Swoole\Log\AccessLogFactory;
use Mezzio\Swoole\Log\AccessLogFormatter;
use Mezzio\Swoole\Log\AccessLogFormatterInterface;
use Mezzio\Swoole\Log\Psr3AccessLogDecorator;
use Mezzio\Swoole\Log\StdoutLogger;
use MezzioTest\Swoole\AttributeAssertionTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionProperty;
use Webmozart\Assert\Assert;
use Zend\Expressive\Swoole\Log\AccessLogFormatterInterface as LegacyAccessLogFormatterInterface;

class AccessLogFactoryTest extends TestCase
{
    use AttributeAssertionTrait;
    use LoggerFactoryHelperTrait;

    /**
     * @var LoggerInterface|MockObject
     * @psalm-var LoggerInterface&MockObject
     */
    private $logger;

    /**
     * @var AccessLogFormatterInterface|MockObject
     * @psalm-var AccessLogFormatterInterface&MockObject
     */
    private $formatter;

    protected function setUp(): void
    {
        $this->logger    = $this->createMock(LoggerInterface::class);
        $this->formatter = $this->createMock(AccessLogFormatterInterface::class);
    }

    public function testCreatesDecoratorWithStdoutLoggerAndAccessLogFormatterWhenNoConfigLoggerOrFormatterPresent(): void
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

    public function testUsesConfiguredStandardLoggerServiceWhenPresent(): void
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

    public function testUsesConfiguredCustomLoggerServiceWhenPresent(): void
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

    public function testUsesConfiguredFormatterServiceWhenPresent(): void
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

    public function testUsesConfigurationToSeedGeneratedLoggerAndFormatter(): void
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
        Assert::isInstanceOf($formatter, AccessLogFormatterInterface::class);

        $this->assertAttributeSame(AccessLogFormatter::FORMAT_COMBINED, 'format', $formatter);
    }
}
