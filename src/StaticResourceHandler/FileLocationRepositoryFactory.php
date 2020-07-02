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
        // Build list of document roots mapped to the default document root directory
        $configDocRoots = $container->get('config')
            ['mezzio-swoole']['swoole-http-server']['static-files']['document-root']
            ?? getcwd() . '/public';
        $isArray = \is_array($configDocRoots);

        $mappedDocRoots = ($isArray && (count($configDocRoots) > 0))
            || ((! $isArray) && strlen(strval($configDocRoots)) > 0)
            ? ['/' => $configDocRoots]
            : [];

        // Add any configured mapped document roots
        $configMappedDocRoots = $container->get('config')
            ['mezzio-swoole']['swoole-http-server']['static-files']['mapped-document-roots']
            ?? [];

        if (count($configMappedDocRoots) > 0) {
            $mappedDocRoots = array_merge($mappedDocRoots, $configMappedDocRoots);
        }

        return new FileLocationRepository($mappedDocRoots);
    }
}
