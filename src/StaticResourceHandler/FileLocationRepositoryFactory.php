<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\Swoole\StaticResourceHandler;

use Psr\Container\ContainerInterface;
use InvalidArgumentException;
use function getcwd;

class FileLocationRepositoryFactory
{
    /**
     * Create a file location repository, initializing with the static files setting configured by mezzio-swoole
     */
    public function __invoke(ContainerInterface $container) : FileLocationRepository
    {
        $docRoots = $container->get('config')['mezzio-swoole']['swoole-http-server']['static-files']['document-root']
            ?? [getcwd() . '/public'];
        if (! is_array($docRoots)) {
            // Accomodate if the user defines document-root as a string or array
            $docRoots = [$docRoots];
        }
        return new FileLocationRepository($docRoots);
    }
}
