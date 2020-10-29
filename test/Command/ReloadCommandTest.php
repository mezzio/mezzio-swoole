<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Swoole\Command;

use Mezzio\Swoole\Command\ReloadCommand;
use MezzioTest\Swoole\AttributeAssertionTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use const SWOOLE_BASE;
use const SWOOLE_PROCESS;

class ReloadCommandTest extends TestCase
{
    use AttributeAssertionTrait;
    use ReflectMethodTrait;

    /**
     * @var InputInterface|MockObject
     * @psalm-var MockObject&InputInterface
     */
    private $input;

    /**
     * @var OutputInterface|MockObject
     * @psalm-var MockObject&OutputInterface
     */
    private $output;

    protected function setUp(): void
    {
        $this->input  = $this->createMock(InputInterface::class);
        $this->output = $this->createMock(OutputInterface::class);
    }

    /**
     * @return Application|MockObject
     * @psalm-return MockObject&Application
     */
    public function mockApplication()
    {
        $helperSet   = $this->createMock(HelperSet::class);
        $application = $this->createMock(Application::class);
        $application->method('getHelperSet')->willReturn($helperSet);
        return $application;
    }

    public function testConstructorAcceptsServerMode(): ReloadCommand
    {
        $command = new ReloadCommand(SWOOLE_PROCESS);
        $this->assertAttributeSame(SWOOLE_PROCESS, 'serverMode', $command);
        return $command;
    }

    /**
     * @depends testConstructorAcceptsServerMode
     */
    public function testReloadCommandIsASymfonyConsoleCommand(ReloadCommand $command): void
    {
        $this->assertInstanceOf(Command::class, $command);
    }

    /**
     * @depends testConstructorAcceptsServerMode
     */
    public function testCommandDefinesNumWorkersOption(ReloadCommand $command): InputOption
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

    public function testExecuteEndsWithErrorWhenServerModeIsNotProcessMode(): void
    {
        $command = new ReloadCommand(SWOOLE_BASE);

        $this->output
            ->expects($this->once())
            ->method('writeln')
            ->with($this->stringContains('not configured to run in SWOOLE_PROCESS mode'));

        $execute = $this->reflectMethod($command, 'execute');
        $this->assertSame(1, $execute->invoke($command, $this->input, $this->output));
    }

    public function testExecuteEndsWithErrorWhenStopCommandFails(): void
    {
        $command = new ReloadCommand(SWOOLE_PROCESS);

        $stopCommand = $this->createMock(Command::class);
        $stopCommand
            ->method('run')
            ->with(
                $this->callback(static function (ArrayInput $arg) {
                    return 'stop' === (string) $arg;
                }),
                $this->output
            )
            ->willReturn(1);

        $application = $this->mockApplication();
        $application->method('find')->with('stop')->willReturn($stopCommand);

        $command->setApplication($application);

        $this->output
            ->expects($this->exactly(2))
            ->method('writeln')
            ->withConsecutive(
                [$this->stringContains('Reloading server')],
                [$this->stringContains('Cannot reload server: unable to stop')]
            );

        $execute = $this->reflectMethod($command, 'execute');
        $this->assertSame(1, $execute->invoke($command, $this->input, $this->output));
    }

    public function testExecuteEndsWithErrorWhenStartCommandFails(): void
    {
        $command = new ReloadCommand(SWOOLE_PROCESS);

        $this->input->method('getOption')->with('num-workers')->willReturn(5);

        $stopCommand = $this->createMock(Command::class);
        $stopCommand
            ->method('run')
            ->with(
                $this->callback(static function (ArrayInput $arg) {
                    return 'stop' === (string) $arg;
                }),
                $this->output
            )
            ->willReturn(0);

        $startCommand = $this->createMock(Command::class);
        $startCommand
            ->method('run')
            ->with(
                $this->callback(static function (ArrayInput $arg) {
                    return 'start --daemonize=1 --num-workers=5' === (string) $arg;
                }),
                $this->output
            )
            ->willReturn(1);

        $application = $this->mockApplication();
        $application
            ->expects($this->exactly(2))
            ->method('find')
            ->withConsecutive(
                ['stop'],
                ['start']
            )
            ->willReturnOnConsecutiveCalls(
                $stopCommand,
                $startCommand
            );

        $command->setApplication($application);

        $this->output
            ->expects($this->exactly(4))
            ->method('writeln')
            ->withConsecutive(
                [$this->stringContains('Reloading server')],
                [$this->stringContains('[DONE]')],
                [$this->stringContains('Starting server')],
                [$this->stringContains('Cannot reload server: unable to start')]
            );

        $this->output
            ->expects($this->exactly(6))
            ->method('write')
            ->withConsecutive(
                [$this->stringContains('Waiting for 5 seconds')],
                [$this->stringContains('<info>.</info>')],
                [$this->stringContains('<info>.</info>')],
                [$this->stringContains('<info>.</info>')],
                [$this->stringContains('<info>.</info>')],
                [$this->stringContains('<info>.</info>')]
            );

        $execute = $this->reflectMethod($command, 'execute');
        $this->assertSame(1, $execute->invoke($command, $this->input, $this->output));
    }

    public function testExecuteEndsWithSuccessWhenBothStopAndStartCommandsSucceed(): void
    {
        $command = new ReloadCommand(SWOOLE_PROCESS);

        $this->input->method('getOption')->with('num-workers')->willReturn(5);

        $stopCommand = $this->createMock(Command::class);
        $stopCommand
            ->method('run')
            ->with(
                $this->callback(static function (ArrayInput $arg) {
                    return 'stop' === (string) $arg;
                }),
                $this->output
            )
            ->willReturn(0);

        $startCommand = $this->createMock(Command::class);
        $startCommand
            ->method('run')
            ->with(
                $this->callback(static function (ArrayInput $arg) {
                    return 'start --daemonize=1 --num-workers=5' === (string) $arg;
                }),
                $this->output
            )
            ->willReturn(0);

        $application = $this->mockApplication();
        $application
            ->expects($this->exactly(2))
            ->method('find')
            ->withConsecutive(
                ['stop'],
                ['start']
            )
            ->willReturnOnConsecutiveCalls(
                $stopCommand,
                $startCommand
            );

        $this->output
            ->expects($this->exactly(3))
            ->method('writeln')
            ->withConsecutive(
                [$this->stringContains('Reloading server')],
                [$this->stringContains('[DONE]')],
                [$this->stringContains('Starting server')]
            );

        $this->output
            ->expects($this->exactly(6))
            ->method('write')
            ->withConsecutive(
                [$this->stringContains('Waiting for 5 seconds')],
                [$this->stringContains('<info>.</info>')],
                [$this->stringContains('<info>.</info>')],
                [$this->stringContains('<info>.</info>')],
                [$this->stringContains('<info>.</info>')],
                [$this->stringContains('<info>.</info>')]
            );

        $command->setApplication($application);

        $execute = $this->reflectMethod($command, 'execute');
        $this->assertSame(0, $execute->invoke($command, $this->input, $this->output));
    }
}
