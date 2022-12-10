<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace Mezzio\Swoole\StaticResourceHandler;

use Psr\Container\ContainerInterface;

use function array_merge;
use function count;
use function getcwd;
use function is_array;
use function strlen;

class FileLocationRepositoryFactory
{
    /**
     * Create a file location repository, initializing with the static files setting configured by mezzio-swoole
     */
    public function __invoke(ContainerInterface $container): FileLocationRepository
    {
        $config = $container->get('config')['mezzio-swoole']['swoole-http-server']['static-files'] ?? [];

        // Build list of document roots mapped to the default document root directory
        $configDocRoots = $config['document-root'] ?? getcwd() . '/public';
        $isArray        = is_array($configDocRoots);

        $mappedDocRoots = ($isArray && ($configDocRoots !== []))
            || (! $isArray && strlen((string) $configDocRoots) > 0)
            ? ['/' => $configDocRoots]
            : [];

        // Add any configured mapped document roots
        $configMappedDocRoots = $config['mapped-document-roots'] ?? [];

        if (count($configMappedDocRoots) > 0) {
            $mappedDocRoots = array_merge($mappedDocRoots, $configMappedDocRoots);
        }

        return new FileLocationRepository($mappedDocRoots);
    }
}
