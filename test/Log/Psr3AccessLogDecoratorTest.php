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
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\ResponseInterface as Psr7Response;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use ReflectionClass;
use Swoole\Http\Request;

class Psr3AccessLogDecoratorTest extends TestCase
{
    use AttributeAssertionTrait;
    use ProphecyTrait;

    protected function setUp(): void
    {
        $this->psr3Logger     = $this->prophesize(LoggerInterface::class);
        $this->formatter      = $this->prophesize(AccessLogFormatterInterface::class);
        $this->request        = $this->prophesize(Request::class)->reveal();
        $this->psr7Response   = $this->prophesize(Psr7Response::class);
        $this->staticResponse = $this->prophesize(StaticResourceResponse::class);
    }

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
    public function testProxiesToPsr3Methods(string $method)
    {
        $logger = new Psr3AccessLogDecorator($this->psr3Logger->reveal(), $this->formatter->reveal());
        switch ($method) {
            case 'log':
                $this->psr3Logger
                    ->log(LogLevel::DEBUG, 'message', ['foo' => 'bar'])
                    ->shouldBeCalled();
                $this->assertNull($logger->log(LogLevel::DEBUG, 'message', ['foo' => 'bar']));
                break;
            default:
                $this->psr3Logger
                    ->$method('message', ['foo' => 'bar'])
                    ->shouldBeCalled();
                $this->assertNull($logger->$method('message', ['foo' => 'bar']));
                break;
        }
    }

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
    ) {
        $expected = 'message';
        $request  = $this->request;

        $response = $this->staticResponse;
        $response->getStatus()->willReturn($status);

        $this->formatter
            ->format(
                Argument::that(static function ($mapper) use ($request, $response) {
                    TestCase::assertInstanceOf(AccessLogDataMap::class, $mapper);
                    Psr3AccessLogDecoratorTest::assertAttributeSame($request, 'request', $mapper);
                    Psr3AccessLogDecoratorTest::assertAttributeSame($response->reveal(), 'staticResource', $mapper);
                    Psr3AccessLogDecoratorTest::assertAttributeSame(false, 'useHostnameLookups', $mapper);
                    return true;
                })
            )
            ->willReturn($expected);

        $this->psr3Logger->$logMethod($expected)->shouldBeCalled();

        $logger = new Psr3AccessLogDecorator($this->psr3Logger->reveal(), $this->formatter->reveal());

        $this->assertNull($logger->logAccessForStaticResource(
            $this->request,
            $response->reveal()
        ));
    }

    /**
     * @dataProvider statusLogMethodValues
     */
    public function testLogAccessForPsr7ResourceFormatsMessageAndPassesItToPsr3Logger(
        int $status,
        string $logMethod
    ) {
        $expected = 'message';
        $request  = $this->request;

        $response = $this->psr7Response;
        $response->getStatusCode()->willReturn($status);

        $this->formatter
            ->format(
                Argument::that(static function ($mapper) use ($request, $response) {
                    Psr3AccessLogDecoratorTest::assertInstanceOf(AccessLogDataMap::class, $mapper);
                    Psr3AccessLogDecoratorTest::assertAttributeSame($request, 'request', $mapper);
                    Psr3AccessLogDecoratorTest::assertAttributeSame($response->reveal(), 'psrResponse', $mapper);
                    Psr3AccessLogDecoratorTest::assertAttributeSame(false, 'useHostnameLookups', $mapper);
                    return true;
                })
            )
            ->willReturn($expected);

        $this->psr3Logger->$logMethod($expected)->shouldBeCalled();

        $logger = new Psr3AccessLogDecorator($this->psr3Logger->reveal(), $this->formatter->reveal());

        $this->assertNull($logger->logAccessForPsr7Resource(
            $this->request,
            $response->reveal()
        ));
    }
}
