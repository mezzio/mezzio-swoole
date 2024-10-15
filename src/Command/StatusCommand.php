<?php // phpcs:disable SlevomatCodingStandard.Classes.UnusedPrivateElements.WriteOnlyProperty

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace Mezzio\Swoole\Command;

use Mezzio\Swoole\PidManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('mezzio:swoole:status')]
class StatusCommand extends Command
{
    use IsRunningTrait;

    /**
     * @var string
     */
    public const HELP = <<<'EOH'
Find out if the server is running.

This command is only relevant when the server was started using the
--daemonize option.
EOH;

    /**
     * @deprecated Use StatusCommand::getDefaultName() instead. Will be removed in 5.0.0
     *
     * @var null|string
     */
    public static $defaultName = 'mezzio:swoole:status';

    public function __construct(private PidManager $pidManager)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Get the status of the web server.');
        $this->setHelp(self::HELP);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $message = $this->isRunning()
            ? '<info>Server is running</info>'
            : '<info>Server is not running</info>';

        $output->writeln($message);

        return 0;
    }
}
