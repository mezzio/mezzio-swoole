<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace MezzioTest\Swoole\Command;

use Mezzio\Swoole\Command\ReloadCommand;
use Mezzio\Swoole\Command\StartCommand;
use Mezzio\Swoole\Command\StopCommand;
use MezzioTest\Swoole\AttributeAssertionTrait;
use MezzioTest\Swoole\ConsecutiveConstraint;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use const SWOOLE_BASE;
use const SWOOLE_PROCESS;

final class ReloadCommandTest extends TestCase
{
    use AttributeAssertionTrait;
    use ReflectMethodTrait;

    private InputInterface&MockObject $input;

    private OutputInterface&MockObject $output;

    private StartCommand&MockObject $startCommand;

    private StopCommand&MockObject $stopCommand;

    protected function setUp(): void
    {
        $this->input        = $this->createMock(InputInterface::class);
        $this->output       = $this->createMock(OutputInterface::class);
        $this->startCommand = $this->createMock(StartCommand::class);
        $this->stopCommand  = $this->createMock(StopCommand::class);
    }

    public function testConstructorAcceptsServerMode(): ReloadCommand
    {
        $command = new ReloadCommand(SWOOLE_PROCESS, $this->stopCommand, $this->startCommand);
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

    /**
     * @depends testConstructorAcceptsServerMode
     */
    public function testCommandDefinesNumTaskWorkersOption(ReloadCommand $command): InputOption
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

    public function testExecuteEndsWithErrorWhenServerModeIsNotProcessMode(): void
    {
        $command = new ReloadCommand(SWOOLE_BASE, $this->stopCommand, $this->startCommand);

        $this->output
            ->expects($this->once())
            ->method('writeln')
            ->with($this->stringContains('not configured to run in SWOOLE_PROCESS mode'));

        $execute = $this->reflectMethod($command, 'execute');
        $this->assertSame(Command::FAILURE, $execute->invoke($command, $this->input, $this->output));
    }

    public function testExecuteEndsWithErrorWhenStopCommandFails(): void
    {
        $command = new ReloadCommand(SWOOLE_PROCESS, $this->stopCommand, $this->startCommand);

        $this->stopCommand
            ->method('run')
            ->with(
                $this->callback(static fn(ArrayInput $arg): bool => "'" . StopCommand::NAME . "'" === (string) $arg),
                $this->output
            )
            ->willReturn(Command::FAILURE);

        $this->output
            ->expects($this->exactly(2))
            ->method('writeln')
            ->with(new ConsecutiveConstraint([
                $this->stringContains('Reloading server'),
                $this->stringContains('Cannot reload server: unable to stop'),
            ]));

        $execute = $this->reflectMethod($command, 'execute');
        $this->assertSame(Command::FAILURE, $execute->invoke($command, $this->input, $this->output));
    }

    public function testExecuteEndsWithErrorWhenStartCommandFails(): void
    {
        $command = new ReloadCommand(SWOOLE_PROCESS, $this->stopCommand, $this->startCommand);

        $this->input
            ->expects($this->exactly(2))
            ->method('getOption')
            ->willReturnMap([
                ['num-workers', 5],
                ['num-task-workers', null],
            ]);

        $this->stopCommand
            ->method('run')
            ->with(
                $this->callback(static fn(ArrayInput $arg): bool => "'" . StopCommand::NAME . "'" === (string) $arg),
                $this->output
            )
            ->willReturn(Command::SUCCESS);

        $this->startCommand
            ->method('run')
            ->with(
                $this->callback(
                    static fn(ArrayInput $arg): bool
                        => "'" . StartCommand::NAME . "' --daemonize=1 --num-workers=5" === (string) $arg
                ),
                $this->output
            )
            ->willReturn(Command::FAILURE);

        $this->output
            ->expects($this->exactly(4))
            ->method('writeln')
            ->with(new ConsecutiveConstraint([
                $this->stringContains('Reloading server'),
                $this->stringContains('[DONE]'),
                $this->stringContains('Starting server'),
                $this->stringContains('Cannot reload server: unable to start'),
            ]));

        $this->output
            ->expects($this->exactly(6))
            ->method('write')
            ->with(new ConsecutiveConstraint([
                $this->stringContains('Waiting for 5 seconds'),
                $this->stringContains('<info>.</info>'),
                $this->stringContains('<info>.</info>'),
                $this->stringContains('<info>.</info>'),
                $this->stringContains('<info>.</info>'),
                $this->stringContains('<info>.</info>'),
            ]));

        $execute = $this->reflectMethod($command, 'execute');
        $this->assertSame(Command::FAILURE, $execute->invoke($command, $this->input, $this->output));
    }

    public function testExecuteEndsWithSuccessWhenBothStopAndStartCommandsSucceed(): void
    {
        $command = new ReloadCommand(SWOOLE_PROCESS, $this->stopCommand, $this->startCommand);

        $this->input
            ->expects($this->exactly(2))
            ->method('getOption')
            ->willReturnMap([
                ['num-workers', 5],
                ['num-task-workers', 2],
            ]);

        $this->stopCommand
            ->method('run')
            ->with(
                $this->callback(static fn(ArrayInput $arg): bool => "'" . StopCommand::NAME . "'" === (string) $arg),
                $this->output
            )
            ->willReturn(Command::SUCCESS);

        $this->startCommand
            ->method('run')
            ->with(
                $this->callback(
                    static fn(ArrayInput $arg): bool
                        => "'" . StartCommand::NAME . "' --daemonize=1 --num-workers=5 --num-task-workers=2"
                            === (string) $arg
                ),
                $this->output
            )
            ->willReturn(Command::SUCCESS);

        $this->output
            ->expects($this->exactly(3))
            ->method('writeln')
            ->with(new ConsecutiveConstraint([
                $this->stringContains('Reloading server'),
                $this->stringContains('[DONE]'),
                $this->stringContains('Starting server'),
            ]));

        $this->output
            ->expects($this->exactly(6))
            ->method('write')
            ->with(new ConsecutiveConstraint([
                $this->stringContains('Waiting for 5 seconds'),
                $this->stringContains('<info>.</info>'),
                $this->stringContains('<info>.</info>'),
                $this->stringContains('<info>.</info>'),
                $this->stringContains('<info>.</info>'),
                $this->stringContains('<info>.</info>'),
            ]));

        $execute = $this->reflectMethod($command, 'execute');
        $this->assertSame(Command::SUCCESS, $execute->invoke($command, $this->input, $this->output));
    }
}
