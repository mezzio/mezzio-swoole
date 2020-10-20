<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Swoole\Log;

use Mezzio\Swoole\Log\AccessLogDataMap;
use Mezzio\Swoole\Log\AccessLogFormatterInterface;
use Mezzio\Swoole\Log\Psr3AccessLogDecorator;
use Mezzio\Swoole\StaticResourceHandler\StaticResourceResponse;
use MezzioTest\Swoole\AttributeAssertionTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface as Psr7Response;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use ReflectionClass;
use ReflectionProperty;
use Swoole\Http\Request;

class Psr3AccessLogDecoratorTest extends TestCase
{
    use AttributeAssertionTrait;

    /**
     * @var LoggerInterface|MockObject
     * @psalm-var MockObject&LoggerInterface
     */
    private $psr3Logger;

    /**
     * @var AccessLogFormatterInterface|MockObject
     * @psalm-var MockObject&AccessLogFormatterInterface
     */
    private $formatter;

    /**
     * @var Request|MockObject
     * @psalm-var MockObject&Request
     */
    private $request;

    /**
     * @var Psr7Response|MockObject
     * @psalm-var MockObject&Psr7Response
     */
    private $psr7Response;

    /**
     * @var StaticResourceResponse|MockObject
     * @psalm-var MockObject&StaticResourceResponse
     */
    private $staticResponse;

    protected function setUp(): void
    {
        $this->psr3Logger     = $this->createMock(LoggerInterface::class);
        $this->formatter      = $this->createMock(AccessLogFormatterInterface::class);
        $this->request        = $this->createMock(Request::class);
        $this->psr7Response   = $this->createMock(Psr7Response::class);
        $this->staticResponse = $this->createMock(StaticResourceResponse::class);
    }

    /** @return mixed */
    private function getPropertyForInstance(string $property, object $instance)
    {
        $r = new ReflectionProperty($instance, $property);
        $r->setAccessible(true);
        return $r->getValue($instance);
    }

    /**
     * @psalm-return iterable<array-key, list<string>>
     */
    public function psr3Methods(): iterable
    {
        $r = new ReflectionClass(LoggerInterface::class);
        foreach ($r->getMethods() as $method) {
            $name = $method->getName();
            yield $name => [$name];
        }
    }

    /**
     * @dataProvider psr3Methods
     */
    public function testProxiesToPsr3Methods(string $method): void
    {
        $logger = new Psr3AccessLogDecorator($this->psr3Logger, $this->formatter);
        switch ($method) {
            case 'log':
                $this->psr3Logger
                    ->expects($this->once())
                    ->method('log')
                    ->with(LogLevel::DEBUG, 'message', ['foo' => 'bar']);
                $this->assertNull($logger->log(LogLevel::DEBUG, 'message', ['foo' => 'bar']));
                break;
            default:
                $this->psr3Logger
                    ->expects($this->once())
                    ->method($method)
                    ->with('message', ['foo' => 'bar']);
                $this->assertNull($logger->$method('message', ['foo' => 'bar']));
                break;
        }
    }

    /**
     * @psalm-return array<array-key, array{0: int, 1: string}>
     */
    public function statusLogMethodValues(): array
    {
        return [
            '100' => [100, 'info'],
            '200' => [200, 'info'],
            '302' => [302, 'info'],
            '400' => [400, 'error'],
            '500' => [500, 'error'],
        ];
    }

    /**
     * @dataProvider statusLogMethodValues
     */
    public function testLogAccessForStaticResourceFormatsMessageAndPassesItToPsr3Logger(
        int $status,
        string $logMethod
    ): void {
        $expected = 'message';

        $this->staticResponse->method('getStatus')->willReturn($status);

        $this->formatter
            ->method('format')
            ->with($this->callback(function (AccessLogDataMap $mapper) {
                return $this->request === $this->getPropertyForInstance('request', $mapper)
                    && $this->staticResponse === $this->getPropertyForInstance('staticResource', $mapper)
                    && false === $this->getPropertyForInstance('useHostnameLookups', $mapper);
            }))
            ->willReturn($expected);

        $this->psr3Logger
            ->expects($this->once())
            ->method($logMethod)
            ->with($expected);

        $logger = new Psr3AccessLogDecorator($this->psr3Logger, $this->formatter);

        $this->assertNull($logger->logAccessForStaticResource($this->request, $this->staticResponse));
    }

    /**
     * @dataProvider statusLogMethodValues
     */
    public function testLogAccessForPsr7ResourceFormatsMessageAndPassesItToPsr3Logger(
        int $status,
        string $logMethod
    ): void {
        $expected = 'message';

        $this->psr7Response->method('getStatusCode')->willReturn($status);

        $this->formatter
             ->method('format')
             ->with($this->callback(function (AccessLogDataMap $mapper) {
                return $this->request === $this->getPropertyForInstance('request', $mapper)
                    && $this->psr7Response === $this->getPropertyForInstance('psrResponse', $mapper)
                    && false === $this->getPropertyForInstance('useHostnameLookups', $mapper);
             }))
            ->willReturn($expected);

        $this->psr3Logger
            ->expects($this->once())
            ->method($logMethod)
            ->with($expected);

        $logger = new Psr3AccessLogDecorator($this->psr3Logger, $this->formatter);

        $this->assertNull($logger->logAccessForPsr7Resource($this->request, $this->psr7Response));
    }
}
