<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Swoole;

use Laminas\Diactoros\Response;
use Mezzio\Response\ServerRequestErrorResponseGenerator;
use Mezzio\Swoole\HotCodeReload\Reloader;
use Mezzio\Swoole\PidManager;
use Mezzio\Swoole\StaticResourceHandler\StaticResourceResponse;
use Mezzio\Swoole\StaticResourceHandlerInterface;
use Mezzio\Swoole\SwooleRequestHandlerRunner;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Swoole\Http\Request as SwooleHttpRequest;
use Swoole\Http\Response as SwooleHttpResponse;
use Swoole\Http\Server as SwooleHttpServer;

use function file_exists;
use function file_get_contents;
use function getcwd;
use function is_dir;
use function posix_getpid;
use function sprintf;

use const PHP_OS;

class SwooleRequestHandlerRunnerTest extends TestCase
{
    /**
     * @var RequestHandlerInterface|MockObject
     * @psalm-var MockObject&RequestHandlerInterface
     */
    private $requestHandler;

    /**
     * @var PidManager|MockObject
     * @psalm-var MockObject&PidManager
     */
    private $pidManager;

    /**
     * @var SwooleHttpServer|MockObject
     * @psalm-var MockObject&SwooleHttpServer
     */
    private $httpServer;

    /**
     * @var StaticResourceHandlerInterface|MockObject
     * @psalm-var MockObject&StaticResourceHandlerInterface
     */
    private $staticResourceHandler;

    /** @var callable */
    private $serverRequestFactory;

    /** @var callable */
    private $serverRequestError;

    /** @psalm-var null */
    private $logger;

    protected function setUp(): void
    {
        $this->requestHandler = $this->createMock(RequestHandlerInterface::class);

        $this->serverRequestFactory = function (): ServerRequestInterface {
            return $this->createMock(ServerRequestInterface::class);
        };

        $this->serverRequestError = function (): ServerRequestErrorResponseGenerator {
            return $this->createMock(ServerRequestErrorResponseGenerator::class);
        };

        $this->pidManager = $this->createMock(PidManager::class);

        $this->httpServer = $this->createMock(SwooleHttpServer::class);

        $this->staticResourceHandler = $this->createMock(StaticResourceHandlerInterface::class);

        $this->logger = null;
    }

    public function testConstructor(): void
    {
        $this->assertIsObject(new SwooleRequestHandlerRunner(
            $this->requestHandler,
            $this->serverRequestFactory,
            $this->serverRequestError,
            $this->pidManager,
            $this->httpServer,
            $this->staticResourceHandler,
            $this->logger
        ));
    }

    public function testRun(): void
    {
        $this->pidManager
            ->method('read')
            ->willReturn([]);

        $this->httpServer
            ->method('on')
            ->willReturn(null);

        $this->httpServer
            ->method('start')
            ->willReturn(null);

        $this->staticResourceHandler
            ->method('processStaticResource')
            ->with($this->any())
            ->willReturn(null);

        $requestHandler = new SwooleRequestHandlerRunner(
            $this->requestHandler,
            $this->serverRequestFactory,
            $this->serverRequestError,
            $this->pidManager,
            $this->httpServer,
            $this->staticResourceHandler,
            $this->logger
        );

        $this->httpServer
            ->expects($this->once())
            ->method('start');

        // Listeners are attached to each of:
        // - start
        // - workerstart
        // - workerstop
        // - workererror
        // - request
        // - shutdown
        $this->httpServer
            ->expects($this->exactly(6))
            ->method('on');

        $requestHandler->run();
    }

    public function testOnStart(): void
    {
        $runner = new SwooleRequestHandlerRunner(
            $this->requestHandler,
            $this->serverRequestFactory,
            $this->serverRequestError,
            $this->pidManager,
            $this->httpServer,
            $this->staticResourceHandler,
            $this->logger
        );

        $runner->onStart($swooleServer = $this->createMock(SwooleHttpServer::class));
        $this->expectOutputString(sprintf(
            "Swoole is running at :0, in %s\n",
            getcwd()
        ));
    }

    public function testOnRequestDelegatesToApplicationWhenNoStaticResourceHandlerPresent(): void
    {
        $content      = 'Content!';
        $psr7Response = new Response();
        $psr7Response->getBody()->write($content);

        $this->requestHandler
            ->method('handle')
            ->with($this->isInstanceOf(ServerRequestInterface::class))
            ->willReturn($psr7Response);

        $request         = $this->createMock(SwooleHttpRequest::class);
        $request->server = [
            'request_uri'    => '/',
            'remote_addr'    => '127.0.0.1',
            'request_method' => 'GET',
        ];
        $request->get    = [];

        $response = $this->createMock(SwooleHttpResponse::class);
        $response
            ->expects($this->once())
            ->method('status')
            ->with(200);
        $response
            ->expects($this->once())
            ->method('end')
            ->with($content);

        $runner = new SwooleRequestHandlerRunner(
            $this->requestHandler,
            $this->serverRequestFactory,
            $this->serverRequestError,
            $this->pidManager,
            $this->httpServer,
            null,
            $this->logger
        );

        $runner->onRequest($request, $response);

        $this->expectOutputRegex('/127\.0\.0\.1\s.*?\s"GET[^"]+" 200.*?\R$/');
    }

    public function testOnRequestDelegatesToApplicationWhenStaticResourceHandlerDoesNotMatchPath(): void
    {
        $content      = 'Content!';
        $psr7Response = new Response();
        $psr7Response->getBody()->write($content);

        $this->requestHandler
            ->method('handle')
            ->with($this->isInstanceOf(ServerRequestInterface::class))
            ->willReturn($psr7Response);

        $request         = $this->createMock(SwooleHttpRequest::class);
        $request->server = [
            'request_uri'    => '/',
            'remote_addr'    => '127.0.0.1',
            'request_method' => 'GET',
        ];
        $request->get    = [];

        $response = $this->createMock(SwooleHttpResponse::class);
        $response
            ->expects($this->once())
            ->method('status')
            ->with(200);
        $response
            ->expects($this->once())
            ->method('end')
            ->with($content);

        $this->staticResourceHandler
            ->method('processStaticResource')
            ->with($request, $response)
            ->willReturn(null);

        $runner = new SwooleRequestHandlerRunner(
            $this->requestHandler,
            $this->serverRequestFactory,
            $this->serverRequestError,
            $this->pidManager,
            $this->httpServer,
            $this->staticResourceHandler,
            $this->logger
        );

        $runner->onRequest($request, $response);

        $this->expectOutputRegex('/127\.0\.0\.1\s.*?\s"GET[^"]+" 200.*?\R$/');
    }

    public function testOnRequestDelegatesToStaticResourceHandlerOnMatch(): void
    {
        $this->requestHandler
            ->expects($this->never())
            ->method('handle');

        $request         = $this->createMock(SwooleHttpRequest::class);
        $request->server = [
            'request_uri'    => '/',
            'remote_addr'    => '127.0.0.1',
            'request_method' => 'GET',
        ];
        $request->get    = [];

        $response = $this->createMock(SwooleHttpResponse::class);

        $staticResponse = $this->createMock(StaticResourceResponse::class);
        $staticResponse->method('getStatus')->willReturn(200);
        $staticResponse->method('getContentLength')->willReturn(200);

        $this->staticResourceHandler
            ->method('processStaticResource')
            ->with($request, $response)
            ->willReturn($staticResponse);

        $runner = new SwooleRequestHandlerRunner(
            $this->requestHandler,
            $this->serverRequestFactory,
            $this->serverRequestError,
            $this->pidManager,
            $this->httpServer,
            $this->staticResourceHandler,
            $this->logger
        );

        $runner->onRequest($request, $response);

        $this->expectOutputRegex('/127\.0\.0\.1\s.*?\s"GET[^"]+" 200.*?\R$/');
    }

    public function testProcessNameIsUsedToCreateMasterProcessNameOnStart(): void
    {
        if (PHP_OS === 'Darwin' || ! is_dir('/proc')) {
            $this->markTestSkipped(
                'Testing process names is only performed on *nix systems (with the exception of MacOS)'
            );
        }

        $runner = new SwooleRequestHandlerRunner(
            $this->requestHandler,
            $this->serverRequestFactory,
            $this->serverRequestError,
            $this->pidManager,
            $this->httpServer,
            $this->staticResourceHandler,
            $this->logger,
            'test' // Process name
        );

        $pid                       = posix_getpid();
        $swooleServer              = $this->createMock(SwooleHttpServer::class);
        $swooleServer->master_pid  = 55555;
        $swooleServer->manager_pid = $pid;

        $runner->onStart($swooleServer);
        $this->expectOutputString(sprintf(
            "Swoole is running at :0, in %s\n",
            getcwd()
        ));

        $processFile = sprintf('/proc/%d/cmdline', $pid);
        $this->assertTrue(file_exists($processFile));

        $contents = file_get_contents($processFile);
        $this->assertStringContainsString('test-master', $contents);
    }

    public function testProcessNameIsUsedToCreateWorkerProcessNameOnWorkerStart(): void
    {
        if (PHP_OS === 'Darwin' || ! is_dir('/proc')) {
            $this->markTestSkipped(
                'Testing process names is only performed on *nix systems (with the exception of MacOS)'
            );
        }

        $runner = new SwooleRequestHandlerRunner(
            $this->requestHandler,
            $this->serverRequestFactory,
            $this->serverRequestError,
            $this->pidManager,
            $this->httpServer,
            $this->staticResourceHandler,
            $this->logger,
            'test' // Process name
        );

        $pid = posix_getpid();

        $swooleServer          = $this->createMock(SwooleHttpServer::class);
        $swooleServer->setting = [
            'worker_num' => $pid + 1,
        ];

        $runner->onWorkerStart($swooleServer, $pid);
        $this->expectOutputString(sprintf(
            "Worker started in %s with ID %d\n",
            getcwd(),
            $pid
        ));

        $processFile = sprintf('/proc/%d/cmdline', $pid);
        $this->assertTrue(file_exists($processFile));

        $contents = file_get_contents($processFile);
        $this->assertStringContainsString('test-worker', $contents);
    }

    public function testProcessNameIsUsedToCreateTaskWorkerProcessNameOnWorkerStart(): void
    {
        if (PHP_OS === 'Darwin' || ! is_dir('/proc')) {
            $this->markTestSkipped(
                'Testing process names is only performed on *nix systems (with the exception of MacOS)'
            );
        }

        $runner = new SwooleRequestHandlerRunner(
            $this->requestHandler,
            $this->serverRequestFactory,
            $this->serverRequestError,
            $this->pidManager,
            $this->httpServer,
            $this->staticResourceHandler,
            $this->logger,
            'test' // Process name
        );

        $pid = posix_getpid();

        $swooleServer          = $this->createMock(SwooleHttpServer::class);
        $swooleServer->setting = [
            'worker_num' => $pid - 2,
        ];

        $runner->onWorkerStart($swooleServer, $pid);
        $this->expectOutputString(sprintf(
            "Worker started in %s with ID %d\n",
            getcwd(),
            $pid
        ));

        $processFile = sprintf('/proc/%d/cmdline', $pid);
        $this->assertTrue(file_exists($processFile));

        $contents = file_get_contents($processFile);
        $this->assertStringContainsString('test-task-worker', $contents);
    }

    public function testHotCodeReloaderTriggeredOnWorkerStart(): void
    {
        $this->httpServer->setting = [
            'worker_num' => posix_getpid(),
        ];

        $hotCodeReloader = $this->createMock(Reloader::class);
        $hotCodeReloader
            ->expects(static::once())
            ->method('onWorkerStart')
            ->with($this->httpServer, 0);

        $runner = new SwooleRequestHandlerRunner(
            $this->requestHandler,
            $this->serverRequestFactory,
            $this->serverRequestError,
            $this->pidManager,
            $this->httpServer,
            $this->staticResourceHandler,
            $this->logger,
            SwooleRequestHandlerRunner::DEFAULT_PROCESS_NAME,
            $hotCodeReloader
        );
        $runner->onWorkerStart($this->httpServer, 0);
    }
}
