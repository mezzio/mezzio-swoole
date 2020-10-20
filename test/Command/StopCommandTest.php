<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Swoole\Command;

use Mezzio\Swoole\Command\StopCommand;
use Mezzio\Swoole\PidManager;
use MezzioTest\Swoole\AttributeAssertionTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function getmypid;

class StopCommandTest extends TestCase
{
    use AttributeAssertionTrait;
    use ReflectMethodTrait;

    /** @var InputInterface|MockObject */
    private $input;

    /** @var OutputInterface|MockObject */
    private $output;

    /** @var PidManager|MockObject */
    private $pidManager;

    protected function setUp(): void
    {
        $this->input      = $this->createMock(InputInterface::class);
        $this->output     = $this->createMock(OutputInterface::class);
        $this->pidManager = $this->createMock(PidManager::class);
    }

    public function testConstructorAcceptsPidManager(): StopCommand
    {
        $command = new StopCommand($this->pidManager);
        $this->assertAttributeSame($this->pidManager, 'pidManager', $command);
        return $command;
    }

    /**
     * @depends testConstructorAcceptsPidManager
     */
    public function testConstructorSetsDefaultName(StopCommand $command)
    {
        $this->assertSame('stop', $command->getName());
    }

    /**
     * @depends testConstructorAcceptsPidManager
     */
    public function testStopCommandIsASymfonyConsoleCommand(StopCommand $command)
    {
        $this->assertInstanceOf(Command::class, $command);
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
    public function testExecuteReturnsSuccessWhenServerIsNotCurrentlyRunning(array $pids)
    {
        $this->pidManager->method('read')->willReturn($pids);

        $command = new StopCommand($this->pidManager);

        $this->output
            ->expects($this->once())
            ->method('writeln')
            ->with($this->stringContains('Server is not running'));

        $execute = $this->reflectMethod($command, 'execute');

        $this->assertSame(0, $execute->invoke(
            $command,
            $this->input,
            $this->output
        ));
    }

    public function runningProcesses(): iterable
    {
        yield 'base-mode'    => [[getmypid(), null]];
        yield 'process-mode' => [[1000000, getmypid()]];
    }

    /**
     * @dataProvider runningProcesses
     */
    public function testExecuteReturnsErrorIfUnableToStopServer(array $pids)
    {
        $this->pidManager->method('read')->willReturn($pids);
        $this->pidManager->expects($this->never())->method('delete');

        $masterPid   = $pids[0];
        $spy         = (object) ['called' => false];
        $killProcess = static function (int $pid, ?int $signal = null) use ($masterPid, $spy) {
            TestCase::assertSame($masterPid, $pid);
            $spy->called = true;
            return $signal === 0;
        };

        $command                = new StopCommand($this->pidManager);
        $command->killProcess   = $killProcess;
        $command->waitThreshold = 1;

        $this->output
            ->expects($this->exactly(2))
            ->method('writeln')
            ->withConsecutive(
                [$this->stringContains('Stopping server')],
                [$this->stringContains('Error stopping server')]
            );

        $execute = $this->reflectMethod($command, 'execute');

        $this->assertSame(1, $execute->invoke(
            $command,
            $this->input,
            $this->output
        ));

        $this->assertTrue($spy->called);
    }

    /**
     * @dataProvider runningProcesses
     */
    public function testExecuteReturnsSuccessIfAbleToStopServer(array $pids)
    {
        $this->pidManager->method('read')->willReturn($pids);
        $this->pidManager->expects($this->atLeastOnce())->method('delete');

        $masterPid   = $pids[0];
        $spy         = (object) ['called' => false];
        $killProcess = static function (int $pid) use ($masterPid, $spy) {
            TestCase::assertSame($masterPid, $pid);
            $spy->called = true;
            return true;
        };

        $command              = new StopCommand($this->pidManager);
        $command->killProcess = $killProcess;

        $this->output
            ->expects($this->exactly(2))
            ->method('writeln')
            ->withConsecutive(
                [$this->stringContains('Stopping server')],
                [$this->stringContains('Server stopped')]
            );

        $execute = $this->reflectMethod($command, 'execute');

        $this->assertSame(0, $execute->invoke(
            $command,
            $this->input,
            $this->output
        ));

        $this->assertTrue($spy->called);
    }
}
