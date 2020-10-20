<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Swoole\Command;

use Mezzio\Swoole\Command\StatusCommand;
use Mezzio\Swoole\PidManager;
use MezzioTest\Swoole\AttributeAssertionTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function getmypid;

class StatusCommandTest extends TestCase
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

    public function testConstructorAcceptsPidManager(): StatusCommand
    {
        $command = new StatusCommand($this->pidManager);
        $this->assertAttributeSame($this->pidManager, 'pidManager', $command);
        return $command;
    }

    /**
     * @depends testConstructorAcceptsPidManager
     */
    public function testConstructorSetsDefaultName(StatusCommand $command)
    {
        $this->assertSame('status', $command->getName());
    }

    /**
     * @depends testConstructorAcceptsPidManager
     */
    public function testStatusCommandIsASymfonyConsoleCommand(StatusCommand $command)
    {
        $this->assertInstanceOf(Command::class, $command);
    }

    public function runningProcesses(): iterable
    {
        yield 'base-mode'    => [[getmypid(), null]];
        yield 'process-mode' => [[1000000, getmypid()]];
    }

    /**
     * @dataProvider runningProcesses
     */
    public function testExecuteIndicatesRunningServerWhenServerDetected(array $pids)
    {
        $this->pidManager->method('read')->willReturn($pids);

        $command = new StatusCommand($this->pidManager);

        $this->output
            ->expects($this->once())
            ->method('writeln')
            ->with($this->stringContains('Server is running'));

        $execute = $this->reflectMethod($command, 'execute');

        $this->assertSame(0, $execute->invoke(
            $command,
            $this->input,
            $this->output
        ));
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
    public function testExecuteIndicatesNoRunningServerWhenServerNotDetected(array $pids)
    {
        $this->pidManager->method('read')->willReturn($pids);

        $command = new StatusCommand($this->pidManager);

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
}
