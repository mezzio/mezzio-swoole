<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace Mezzio\Swoole\Command;

use Mezzio\Application;
use Mezzio\MiddlewareFactory;
use Mezzio\Swoole\PidManager;
use Psr\Container\ContainerInterface;
use Swoole\Http\Server as SwooleHttpServer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function file_exists;

class StartCommand extends Command
{
    use IsRunningTrait;

    public PidManager $pidManager;

    /**
     * @var int
     */
    public const DEFAULT_NUM_WORKERS = 4;

    /**
     * @var string
     */
    public const HELP = <<<'EOH'
Start the web server. If --daemonize is provided, starts the server as a
background process and returns handling to the shell; otherwise, the
server runs in the current process.

Use --num-workers to control how many worker processes to start. If you
do not provide the option, 4 workers will be started.
EOH;

    /**
     * @var string[]
     */
    private const PROGRAMMATIC_CONFIG_FILES = [
        'config/pipeline.php',
        'config/routes.php',
    ];

    /** @var null|string Cannot be defined explicitly due to parent class */
    public static $defaultName = 'mezzio:swoole:start';

    public function __construct(private ContainerInterface $container)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Start the web server.');
        $this->setHelp(self::HELP);
        $this->addOption(
            'daemonize',
            'd',
            InputOption::VALUE_NONE,
            'Daemonize the web server (run as a background process).'
        );
        $this->addOption(
            'num-workers',
            'w',
            InputOption::VALUE_REQUIRED,
            'Number of worker processes to use.'
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
        /** @var PidManager $this->pidManager */
        $this->pidManager = $this->container->get(PidManager::class);

        if ($this->isRunning()) {
            $output->writeln('<error>Server is already running!</error>');
            return 1;
        }

        $serverOptions = [];
        $daemonize     = (bool) $input->getOption('daemonize');

        $numWorkers = $input->getOption('num-workers');

        $numTaskWorkers = $input->getOption('num-task-workers');

        if ($daemonize) {
            $serverOptions['daemonize'] = $daemonize;
        }

        if (null !== $numWorkers) {
            $serverOptions['worker_num'] = (int) $numWorkers;
        }

        if (null !== $numTaskWorkers) {
            $serverOptions['task_worker_num'] = (int) $numTaskWorkers;
        }

        if ([] !== $serverOptions) {
            /** @var SwooleHttpServer $server */
            $server = $this->container->get(SwooleHttpServer::class);
            $server->set($serverOptions);
        }

        /** @var Application $app */
        $app = $this->container->get(Application::class);

        /** @var MiddlewareFactory $factory */
        $factory = $this->container->get(MiddlewareFactory::class);

        // Execute programmatic/declarative middleware pipeline and routing
        // configuration statements, if they exist
        foreach (self::PROGRAMMATIC_CONFIG_FILES as $configFile) {
            if (file_exists($configFile)) {
                (require $configFile)($app, $factory, $this->container);
            }
        }

        // Run the application
        $app->run();

        return 0;
    }
}
