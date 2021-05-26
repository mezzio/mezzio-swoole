<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace Mezzio\Swoole;

use Psr\Container\ContainerInterface;

use function sys_get_temp_dir;

class PidManagerFactory
{
    public function __invoke(ContainerInterface $container): PidManager
    {
        $config = $container->get('config');
        return new PidManager(
            $config['mezzio-swoole']['swoole-http-server']['options']['pid_file']
                ?? sys_get_temp_dir() . '/laminas-swoole.pid'
        );
    }
}
