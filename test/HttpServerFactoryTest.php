<?php

declare(strict_types=1);

namespace MezzioTest\Swoole;

use Mezzio\Swoole\Exception\InvalidArgumentException;
use Mezzio\Swoole\HttpServerFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Swoole\Event as SwooleEvent;
use Swoole\Http\Server as SwooleServer;
use Swoole\Process;
use Swoole\Runtime as SwooleRuntime;
use Throwable;
use Webmozart\Assert\Assert;

use function array_merge;
use function defined;
use function extension_loaded;
use function go;
use function json_decode;
use function json_encode;
use function method_exists;
use function usleep;

use const SWOOLE_BASE;
use const SWOOLE_PROCESS;
use const SWOOLE_SOCK_TCP;
use const SWOOLE_SOCK_TCP6;
use const SWOOLE_SOCK_UDP;
use const SWOOLE_SOCK_UDP6;
use const SWOOLE_SSL;
use const SWOOLE_UNIX_DGRAM;
use const SWOOLE_UNIX_STREAM;

class HttpServerFactoryTest extends TestCase
{
    /**
     * @var ContainerInterface|MockObject
     * @psalm-var MockObject&ContainerInterface
     */
    private $container;

    protected function setUp(): void
    {
        parent::setUp();
        $this->container = $this->createMock(ContainerInterface::class);
    }

    public function testFactoryCanCreateServerWithDefaultConfiguration(): void
    {
        /**
         * Initialise servers inside a process or subsequent tests will fail
         *
         * @see https://github.com/swoole/swoole-src/issues/1754
         */
        $process = new Process(function (Process $worker) {
            $this->container->method('get')->with('config')->willReturn([]);
            $factory      = new HttpServerFactory();
            $swooleServer = $factory($this->container);
            $this->assertSame(HttpServerFactory::DEFAULT_HOST, $swooleServer->host);
            $this->assertSame(HttpServerFactory::DEFAULT_PORT, $swooleServer->port);
            $this->assertSame(SWOOLE_BASE, $swooleServer->mode);
            $this->assertSame(SWOOLE_SOCK_TCP, $swooleServer->type);
            $worker->write('Process Complete');
            $worker->exit(0);
        });
        $process->start();
        $this->assertSame('Process Complete', $process->read());
        Process::wait(true);
    }

    public function testFactorySetsPortAndHostAsConfigured(): void
    {
        $process = new Process(function (Process $worker) {
            $this->container->method('get')->with('config')->willReturn([
                'mezzio-swoole' => [
                    'swoole-http-server' => [
                        'host'     => '0.0.0.0',
                        'port'     => 8081,
                        'mode'     => SWOOLE_BASE,
                        'protocol' => SWOOLE_SOCK_TCP6,
                    ],
                ],
            ]);
            $factory      = new HttpServerFactory();
            $swooleServer = $factory($this->container);
            $worker->write(json_encode([
                'host' => $swooleServer->host,
                'port' => $swooleServer->port,
                'mode' => $swooleServer->mode,
                'type' => $swooleServer->type,
            ]));
            $worker->exit(0);
        });
        $process->start();
        $data = $process->read();
        Process::wait(true);

        $result = json_decode($data, true);
        $this->assertSame([
            'host' => '0.0.0.0',
            'port' => 8081,
            'mode' => SWOOLE_BASE,
            'type' => SWOOLE_SOCK_TCP6,
        ], $result);
    }

    /**
     * @psalm-return array<array-key, int[]>
     */
    public function getInvalidPortNumbers(): array
    {
        return [
            [-1],
            [0],
            [65536],
            [999999],
        ];
    }

    /**
     * @dataProvider getInvalidPortNumbers
     */
    public function testExceptionThrownForOutOfRangePortNumber(int $port): void
    {
        $this->container->method('get')->with('config')->willReturn([
            'mezzio-swoole' => [
                'swoole-http-server' => [
                    'port' => $port,
                ],
            ],
        ]);
        $factory = new HttpServerFactory();
        try {
            $factory($this->container);
            $this->fail('An exception was not thrown');
        } catch (InvalidArgumentException $e) {
            $this->assertSame('Invalid port', $e->getMessage());
        }
    }

    /**
     * @psalm-return array<array-key, list<int|string>>
     */
    public function invalidServerModes(): array
    {
        return [
            [0],
            [(string) SWOOLE_BASE],
            [(string) SWOOLE_PROCESS],
            [10],
        ];
    }

    /**
     * @dataProvider invalidServerModes
     * @param int|string $mode
     */
    public function testExceptionThrownForInvalidServerMode($mode): void
    {
        $this->container->method('get')->with('config')->willReturn([
            'mezzio-swoole' => [
                'swoole-http-server' => [
                    'mode' => $mode,
                ],
            ],
        ]);
        $factory = new HttpServerFactory();
        try {
            $factory($this->container);
            $this->fail('An exception was not thrown');
        } catch (InvalidArgumentException $e) {
            $this->assertSame('Invalid server mode', $e->getMessage());
        }
    }

    /**
     * @psalm-return array<array-key, list<int|string>>
     */
    public function invalidSocketTypes(): array
    {
        return [
            [0],
            [(string) SWOOLE_SOCK_TCP],
            [(string) SWOOLE_SOCK_TCP6],
            [10],
        ];
    }

    /**
     * @dataProvider invalidSocketTypes
     * @param int|string $type
     */
    public function testExceptionThrownForInvalidSocketType($type): void
    {
        $this->container->method('get')->with('config')->willReturn([
            'mezzio-swoole' => [
                'swoole-http-server' => [
                    'protocol' => $type,
                ],
            ],
        ]);
        $factory = new HttpServerFactory();
        try {
            $factory($this->container);
            $this->fail('An exception was not thrown');
        } catch (InvalidArgumentException $e) {
            $this->assertSame('Invalid server protocol', $e->getMessage());
        }
    }

    public function testServerOptionsAreCorrectlySetFromConfig(): void
    {
        $serverOptions = [
            'pid_file' => '/tmp/swoole.pid',
        ];
        $this->container->method('get')->with('config')->willReturn([
            'mezzio-swoole' => [
                'swoole-http-server' => [
                    'options' => $serverOptions,
                ],
            ],
        ]);
        $process = new Process(function (Process $worker) {
            $factory      = new HttpServerFactory();
            $swooleServer = $factory($this->container);
            $worker->write(json_encode($swooleServer->setting));
            $worker->exit();
        });
        $process->start();
        $setOptions = json_decode($process->read(), true);
        Process::wait(true);
        $this->assertSame($serverOptions, $setOptions);
    }

    /**
     * @psalm-return array<array-key, array{
     *     0: int,
     *     1: array<empty, empty>|array<string, non-empty-string>,
     * }>
     */
    public function validSocketTypes(): array
    {
        $validTypes = [
            [SWOOLE_SOCK_TCP, []],
            [SWOOLE_SOCK_TCP6, []],
            [SWOOLE_SOCK_UDP, []],
            [SWOOLE_SOCK_UDP6, []],
            [SWOOLE_UNIX_DGRAM, []],
            [SWOOLE_UNIX_STREAM, []],
        ];

        if (defined('SWOOLE_SSL')) {
            $extraOptions = [
                'ssl_cert_file' => __DIR__ . '/TestAsset/ssl/server.crt',
                'ssl_key_file'  => __DIR__ . '/TestAsset/ssl/server.key',
            ];
            $validTypes[] = [SWOOLE_SOCK_TCP | SWOOLE_SSL, $extraOptions];
            $validTypes[] = [SWOOLE_SOCK_TCP6 | SWOOLE_SSL, $extraOptions];
        }

        return $validTypes;
    }

    /**
     * @dataProvider validSocketTypes
     * @param int $socketType
     * @psalm-param array<string, string> $additionalOptions
     */
    public function testServerCanBeStartedForKnownSocketTypeCombinations($socketType, array $additionalOptions): void
    {
        $this->container->method('get')->with('config')->willReturn([
            'mezzio-swoole' => [
                'swoole-http-server' => [
                    'host'     => '127.0.0.1',
                    'port'     => 8080,
                    'protocol' => $socketType,
                    'mode'     => SWOOLE_PROCESS,
                    'options'  => array_merge([
                        'worker_num' => 1,
                    ], $additionalOptions),
                ],
            ],
        ]);
        $process = new Process(function (Process $worker) {
            try {
                $factory      = new HttpServerFactory();
                $swooleServer = $factory($this->container);
                $swooleServer->on('Start', static function (SwooleServer $server) use ($worker) {
                    // Give the server a chance to start up and avoid zombies
                    usleep(10000);
                    $worker->write('Server Started');
                    $server->stop();
                    $server->shutdown();
                });
                $swooleServer->on('Request', static function ($req, $rep) {
                    // noop
                });
                $swooleServer->on('Packet', static function (SwooleServer $server, $data, $clientInfo) {
                    // noop
                });
                $swooleServer->start();
            } catch (Throwable $exception) {
                $worker->write('Exception Thrown: ' . $exception->getMessage());
            }
            $worker->exit();
        });
        $process->start();
        $output = $process->read();
        Process::wait(true);
        $this->assertSame('Server Started', $output);
    }

    public function testFactoryCanEnableCoroutines(): void
    {
        if (! method_exists(SwooleRuntime::class, 'enableCoroutine')) {
            $this->markTestSkipped('The installed version of Swoole does not support coroutines.');
        }

        $this->container->method('get')->with('config')->willReturn([
            'mezzio-swoole' => [
                'enable_coroutine' => true,
            ],
        ]);

        $factory = new HttpServerFactory();
        $factory($this->container);

        // Xdebug is not ready yet in swoole.
        if (extension_loaded('xdebug')) {
            $this->expectWarning();

            go(static function () {
            });
        } else {
            $i = 0;
            go(static function () use (&$i) {
                Assert::integer($i);
                usleep(1000);
                $i += 1;
                SwooleEvent::exit();
            });
            go(function () use (&$i) {
                Assert::integer($i);
                $i += 1;
                $this->assertEquals(1, $i);
            });
        }
    }
}
