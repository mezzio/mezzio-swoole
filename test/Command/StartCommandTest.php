<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Swoole\Command;

use Mezzio\Application;
use Mezzio\MiddlewareFactory;
use Mezzio\Swoole\Command\StartCommand;
use Mezzio\Swoole\PidManager;
use MezzioTest\Swoole\AttributeAssertionTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Swoole\Http\Server as SwooleHttpServer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function array_key_exists;
use function get_include_path;
use function getmypid;
use function realpath;
use function set_include_path;
use function sprintf;

use const PATH_SEPARATOR;

class StartCommandTest extends TestCase
{
    use AttributeAssertionTrait;
    use ReflectMethodTrait;

    /**
     * @var ContainerInterface|MockObject
     * @psalm-var MockObject&ContainerInterface
     */
    private $container;

    /**
     * @var InputInterface|MockObject
     * @psalm-var MockObject&InputInterface
     */
    private $input;

    /** @var string */
    private $originalIncludePath;

    /**
     * @var OutputInterface|MockObject
     * @psalm-var MockObject&OutputInterface
     */
    private $output;

    /**
     * @var PidManager|MockObject
     * @psalm-var MockObject&PidManager
     */
    private $pidManager;

    protected function setUp(): void
    {
        $this->container  = $this->createMock(ContainerInterface::class);
        $this->input      = $this->createMock(InputInterface::class);
        $this->output     = $this->createMock(OutputInterface::class);
        $this->pidManager = $this->createMock(PidManager::class);

        $this->originalIncludePath = get_include_path();
        set_include_path(sprintf(
            '%s/TestAsset%s%s',
            realpath(__DIR__),
            PATH_SEPARATOR,
            $this->originalIncludePath
        ));
    }

    protected function tearDown(): void
    {
        set_include_path($this->originalIncludePath);
    }

    public function testConstructorAcceptsContainer(): StartCommand
    {
        $command = new StartCommand($this->container);
        $this->assertAttributeSame($this->container, 'container', $command);
        return $command;
    }

    /**
     * @depends testConstructorAcceptsContainer
     */
    public function testStartCommandIsASymfonyConsoleCommand(StartCommand $command): void
    {
        $this->assertInstanceOf(Command::class, $command);
    }

    /**
     * @depends testConstructorAcceptsContainer
     */
    public function testCommandDefinesNumWorkersOption(StartCommand $command): InputOption
    {
        $this->assertTrue($command->getDefinition()->hasOption('num-workers'));
        return $command->getDefinition()->getOption('num-workers');
    }

    /**
     * @depends testCommandDefinesNumWorkersOption
     */
    public function testNumWorkersOptionIsRequired(InputOption $option): void
    {
        $this->assertTrue($option->isValueRequired());
    }

    /**
     * @depends testCommandDefinesNumWorkersOption
     */
    public function testNumWorkersOptionDefinesShortOption(InputOption $option): void
    {
        $this->assertSame('w', $option->getShortcut());
    }

    /**
     * @depends testConstructorAcceptsContainer
     */
    public function testCommandDefinesNumTaskWorkersOption(StartCommand $command): InputOption
    {
        $this->assertTrue($command->getDefinition()->hasOption('num-task-workers'));
        return $command->getDefinition()->getOption('num-task-workers');
    }

    /**
     * @depends testCommandDefinesNumTaskWorkersOption
     */
    public function testNumTaskWorkersOptionIsRequired(InputOption $option): void
    {
        $this->assertTrue($option->isValueRequired());
    }

    /**
     * @depends testCommandDefinesNumTaskWorkersOption
     */
    public function testNumTaskWorkersOptionDefinesShortOption(InputOption $option): void
    {
        $this->assertSame('t', $option->getShortcut());
    }

    /**
     * @depends testConstructorAcceptsContainer
     */
    public function testCommandDefinesDaemonizeOption(StartCommand $command): InputOption
    {
        $this->assertTrue($command->getDefinition()->hasOption('daemonize'));
        return $command->getDefinition()->getOption('daemonize');
    }

    /**
     * @depends testCommandDefinesDaemonizeOption
     */
    public function testDaemonizeOptionHasNoValue(InputOption $option): void
    {
        $this->assertFalse($option->acceptValue());
    }

    /**
     * @depends testCommandDefinesDaemonizeOption
     */
    public function testDaemonizeOptionDefinesShortOption(InputOption $option): void
    {
        $this->assertSame('d', $option->getShortcut());
    }

    public function testExecuteReturnsErrorIfServerIsRunningInBaseMode(): void
    {
        $this->pidManager->method('read')->willReturn([getmypid(), null]);
        $this->container->method('get')->with(PidManager::class)->willReturn($this->pidManager);

        $command = new StartCommand($this->container);

        $this->output
            ->expects($this->once())
            ->method('writeln')
            ->with($this->stringContains('Server is already running'));

        $execute = $this->reflectMethod($command, 'execute');
        $this->assertSame(1, $execute->invoke($command, $this->input, $this->output));
    }

    public function testExecuteReturnsErrorIfServerIsRunningInProcessMode(): void
    {
        $this->pidManager->method('read')->willReturn([1000000, getmypid()]);
        $this->container->method('get')->with(PidManager::class)->willReturn($this->pidManager);

        $command = new StartCommand($this->container);

        $this->output
            ->expects($this->once())
            ->method('writeln')
            ->with($this->stringContains('Server is already running'));

        $execute = $this->reflectMethod($command, 'execute');
        $this->assertSame(1, $execute->invoke($command, $this->input, $this->output));
    }

    public function noRunningProcesses(): iterable
    {
        yield 'empty'        => [[]];
        yield 'null-all'     => [[null, null]];
        yield 'base-mode'    => [[1000000, null]];
        yield 'process-mode' => [[1000000, 1000001]];
    }

    /**
     * @dataProvider noRunningProcesses
     */
    public function testExecuteRunsApplicationIfServerIsNotCurrentlyRunning(array $pids): void
    {
        $httpServer        = $this->createMock(TestAsset\HttpServer::class);
        $middlewareFactory = $this->createMock(MiddlewareFactory::class);
        $application       = $this->createMock(Application::class);

        $this->input
            ->method('getOption')
            ->will($this->returnValueMap([
                ['daemonize', true],
                ['num-workers', 6],
                ['num-task-workers', 4],
            ]));

        $this->pidManager->method('read')->willReturn($pids);

        $httpServer
            ->expects($this->once())
            ->method('set')
            ->with($this->callback(static function (array $options) {
                return array_key_exists('daemonize', $options)
                    && array_key_exists('worker_num', $options)
                    && array_key_exists('task_worker_num', $options)
                    && true === $options['daemonize']
                    && 6 === $options['worker_num']
                    && 4 === $options['task_worker_num'];
            }));

        $application->expects($this->once())->method('run');

        $this->output
            ->expects($this->never())
            ->method('writeln')
            ->with($this->stringContains('Server is already running'));

        $this->container
            ->method('get')
            ->will($this->returnValueMap([
                [PidManager::class, $this->pidManager],
                [SwooleHttpServer::class, $httpServer],
                [MiddlewareFactory::class, $middlewareFactory],
                [Application::class, $application],
            ]));

        $command = new StartCommand($this->container);

        $execute = $this->reflectMethod($command, 'execute');

        $this->assertSame(0, $execute->invoke($command, $this->input, $this->output));
    }

    /**
     * @dataProvider noRunningProcesses
     */
    public function testExecuteRunsApplicationWithoutSettingOptionsIfNoneProvided(array $pids): void
    {
        $this->input
            ->method('getOption')
            ->will($this->returnValueMap([
                ['daemonize', false],
                ['num-workers', null],
                ['num-task-workers', null],
            ]));

        [$command, $httpServer, $application] = $this->prepareSuccessfulStartCommand($pids);

        $httpServer->expects($this->never())->method('set');
        $application->expects($this->once())->method('run');

        $this->output
            ->expects($this->never())
            ->method('writeln')
            ->with($this->stringContains('Server is already running'));

        $execute = $this->reflectMethod($command, 'execute');
        $this->assertSame(0, $execute->invoke($command, $this->input, $this->output));
    }

    public function testExecutionDoesNotFailEvenIfProgrammaticConfigFilesDoNotExist(): void
    {
        set_include_path($this->originalIncludePath);

        [$command] = $this->prepareSuccessfulStartCommand([]);

        $execute = $this->reflectMethod($command, 'execute');
        $this->assertSame(0, $execute->invoke($command, $this->input, $this->output));
    }

    private function prepareSuccessfulStartCommand(array $pids): array
    {
        $httpServer        = $this->createMock(TestAsset\HttpServer::class);
        $middlewareFactory = $this->createMock(MiddlewareFactory::class);
        $application       = $this->createMock(Application::class);

        $this->pidManager->method('read')->willReturn($pids);
        $this->container
            ->method('get')
            ->will($this->returnValueMap([
                [PidManager::class, $this->pidManager],
                [SwooleHttpServer::class, $httpServer],
                [MiddlewareFactory::class, $middlewareFactory],
                [Application::class, $application],
            ]));

        $command = new StartCommand($this->container);

        return [$command, $httpServer, $application];
    }
}
