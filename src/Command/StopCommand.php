<?php

declare(strict_types=1);

namespace Mezzio\Swoole\Command;

use Closure;
use Mezzio\Swoole\PidManager;
use Swoole\Process as SwooleProcess;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function time;
use function usleep;

class StopCommand extends Command
{
    use IsRunningTrait;

    public const HELP = <<<'EOH'
Stop the web server. Kills all worker processes and stops the web server.

This command is only relevant when the server was started using the
--daemonize option.
EOH;

    /** @var null|string Cannot be defined explicitly due to parent class */
    public static $defaultName = 'mezzio:swoole:stop';

    /**
     * @internal
     *
     * @var Closure Callable to execute when attempting to kill the server
     *     process. Generally speaking, this is SwooleProcess::kill; only
     *     change the value when testing.
     */
    public $killProcess;

    /**
     * How long to wait for the server process to end. Only change the value when testing.
     *
     * @internal
     */
    public int $waitThreshold = 60;

    private PidManager $pidManager;

    public function __construct(PidManager $pidManager)
    {
        $this->killProcess = Closure::fromCallable([SwooleProcess::class, 'kill']);
        $this->pidManager  = $pidManager;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Stop the web server.');
        $this->setHelp(self::HELP);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (! $this->isRunning()) {
            $output->writeln('<info>Server is not running</info>');
            return 0;
        }

        $output->writeln('<info>Stopping server ...</info>');

        if (! $this->stopServer()) {
            $output->writeln('<error>Error stopping server; check logs for details</error>');
            return 1;
        }

        $output->writeln('<info>Server stopped</info>');
        return 0;
    }

    private function stopServer(): bool
    {
        [$masterPid] = $this->pidManager->read();
        $startTime   = time();
        $result      = ($this->killProcess)((int) $masterPid);

        while (! $result) {
            if (! ($this->killProcess)((int) $masterPid, 0)) {
                continue;
            }
            if (time() - $startTime >= $this->waitThreshold) {
                $result = false;
                break;
            }
            usleep(10000);
        }

        if (! $result) {
            return false;
        }

        $this->pidManager->delete();

        return true;
    }
}
