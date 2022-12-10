<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
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

    /** @psalm-var MockObject&InputInterface */
    private InputInterface|MockObject $input;

    /**
     * @var OutputInterface|MockObject
     * @psalm-var MockObject&OutputInterface
     */
    private $output;

    /** @psalm-var MockObject&PidManager */
    private PidManager|MockObject $pidManager;

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
    public function testStatusCommandIsASymfonyConsoleCommand(StatusCommand $command): void
    {
        $this->assertInstanceOf(Command::class, $command);
    }

    /**
     * @psalm-return iterable<array-key, list<list<int|null>>>
     */
    public function runningProcesses(): iterable
    {
        yield 'base-mode'    => [[getmypid(), null]];
        yield 'process-mode' => [[1_000_000, getmypid()]];
    }

    /**
     * @dataProvider runningProcesses
     * @psalm-param list<null|int> $pids
     */
    public function testExecuteIndicatesRunningServerWhenServerDetected(array $pids): void
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

    /**
     * @psalm-return iterable<array-key, list<list<null|int>>>
     */
    public function noRunningProcesses(): iterable
    {
        yield 'empty'        => [[]];
        yield 'null-all'     => [[null, null]];
        yield 'base-mode'    => [[1_000_000, null]];
        yield 'process-mode' => [[1_000_000, 1_000_001]];
    }

    /**
     * @dataProvider noRunningProcesses
     * @psalm-param list<null|int> $pids
     */
    public function testExecuteIndicatesNoRunningServerWhenServerNotDetected(array $pids): void
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
