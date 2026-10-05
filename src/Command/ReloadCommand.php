<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace Mezzio\Swoole\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function sleep;

use const SWOOLE_PROCESS;

#[AsCommand(
    self::NAME,
    'Reload the web server.',
    help: self::HELP,
)]
class ReloadCommand extends Command
{
    /**
     * @var string
     */
    public const NAME = 'mezzio:swoole:reload';

    /**
     * @var string
     */
    public const HELP = <<<'EOH'
Reload the web server. Sends a SIGUSR1 signal to master process and reload
all worker processes.

This command is only relevant when the server was started using the
--daemonize option, and the mezzio-swoole.swoole-http-server.mode
configuration value is set to SWOOLE_PROCESS.
EOH;

    public function __construct(
        private readonly int $serverMode,
        private readonly StopCommand $stopCommand,
        private readonly StartCommand $startCommand,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'num-workers',
            'w',
            InputOption::VALUE_REQUIRED,
            'Number of worker processes to use after reloading.'
        );
        $this->addOption(
            'num-task-workers',
            't',
            InputOption::VALUE_REQUIRED,
            'Number of task worker processes to use.'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($this->serverMode !== SWOOLE_PROCESS) {
            $output->writeln(
                '<error>Server is not configured to run in SWOOLE_PROCESS mode; cannot reload</error>'
            );
            return Command::FAILURE;
        }

        $output->writeln('<info>Reloading server ...</info>');
        $result = $this->stopCommand->run($this->createStopInput(), $output);

        if (0 !== $result) {
            $output->writeln('<error>Cannot reload server: unable to stop current server</error>');
            return $result;
        }

        $output->write('<info>Waiting for 5 seconds to ensure server is stopped...</info>');
        for ($i = 0; $i < 5; ++$i) {
            $output->write('<info>.</info>');
            sleep(1);
        }

        $output->writeln('<info>[DONE]</info>');
        $output->writeln('<info>Starting server</info>');
        $result = $this->startCommand->run($this->createStartInput($input), $output);

        if (0 !== $result) {
            $output->writeln('<error>Cannot reload server: unable to start server</error>');
            return $result;
        }

        return Command::SUCCESS;
    }

    private function createStopInput(): ArrayInput
    {
        return new ArrayInput([
            'command' => StopCommand::NAME,
        ]);
    }

    private function createStartInput(InputInterface $input): ArrayInput
    {
        $inputArguments = [
            'command'       => StartCommand::NAME,
            '--daemonize'   => true,
            '--num-workers' => $input->getOption('num-workers') ?? StartCommand::DEFAULT_NUM_WORKERS,
        ];

        $numTaskWorkers = $input->getOption('num-task-workers');
        if (null !== $numTaskWorkers) {
            $inputArguments['--num-task-workers'] = (int) $numTaskWorkers;
        }

        return new ArrayInput($inputArguments);
    }
}
