<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
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

    public const DEFAULT_NUM_WORKERS = 4;

    public const HELP = <<<'EOH'
Start the web server. If --daemonize is provided, starts the server as a
background process and returns handling to the shell; otherwise, the
server runs in the current process.

Use --num-workers to control how many worker processes to start. If you
do not provide the option, 4 workers will be started.
EOH;

    private const PROGRAMMATIC_CONFIG_FILES = [
        'config/pipeline.php',
        'config/routes.php',
    ];

    /** @var string Cannot be defined explicitly due to parent class */
    public static $defaultName = 'mezzio:swoole:start';

    /** @var ContainerInterface */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
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
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->pidManager = $this->container->get(PidManager::class);
        if ($this->isRunning()) {
            $output->writeln('<error>Server is already running!</error>');
            return 1;
        }

        $serverOptions = [];
        $daemonize     = $input->getOption('daemonize');
        $numWorkers    = $input->getOption('num-workers');
        if ($daemonize) {
            $serverOptions['daemonize'] = $daemonize;
        }
        if (null !== $numWorkers) {
            $serverOptions['worker_num'] = $numWorkers;
        }

        if ([] !== $serverOptions) {
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
