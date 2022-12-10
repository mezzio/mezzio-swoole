<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace Mezzio\Swoole;

use Mezzio\Swoole\StaticResourceHandler\FileLocationRepositoryInterface;
use Psr\Container\ContainerInterface;

/**
 * Create and return a StaticMappedResourceHandler
 *
 * Uses the following configuration in order to configure and serve static
 * resources from the filesystem:
 *
 * <code>
 * 'mezzio-swoole' => [
 *     'swoole-http-server' => [
 *         'static-files' => [
 *             'document-root' => '/path/to/static/files/to/serve', // usu getcwd() . /public/
 *             'mapped-document-roots' => [
 *                 'foo' => '/var/lib/where-foo-files-are'
 *             ]
 *             'type-map' => [
 *                 // extension => mimetype pairs of types to cache.
 *                 // A default list exists if none is provided.
 *             ],
 *             'clearstatcache-interval' => 3600, // How often a worker should clear the
 *                                                // filesystem stat cache. If not provided,
 *                                                // it will never clear it. Value should be
 *                                                // an integer indicating number of seconds
 *                                                // between clear operations. 0 or negative
 *                                                // values will clear on every request.
 *             'etag-type' => 'weak|strong', // ETag algorithm type to use, if any
 *             'gzip' => [
 *                 'level' => 4, // Integer between 1 and 9 indicating compression level to use.
 *                               // Values less than 1 disable compression.
 *             ],
 *             'directives' => [
 *                 // Rules governing which server-side caching headers are emitted.
 *                 // Each key must be a valid regular expression, and should match
 *                 // typically only file extensions, but potentially full paths.
 *                 // When a static resource matches, all associated rules will apply.
 *                 'regex' => [
 *                     'cache-control' => [
 *                         // one or more valid Cache-Control directives:
 *                         // - must-revalidate
 *                         // - no-cache
 *                         // - no-store
 *                         // - no-transform
 *                         // - public
 *                         // - private
 *                         // - max-age=\d+
 *                     ],
 *                     'last-modified' => bool, // Emit a Last-Modified header?
 *                     'etag' => bool, // Emit an ETag header?
 *                 ],
 *             ],
 *         ],
 *     ],
 * ],
 * </code>
 */
class StaticMappedResourceHandlerFactory extends AbstractStaticResourceHandlerFactory
{
    public function __invoke(ContainerInterface $container): StaticMappedResourceHandler
    {
        /** @var array<array-key, mixed> $config */
        $config = $container->get('config')['mezzio-swoole']['swoole-http-server']['static-files'] ?? [];

        /** @var FileLocationRepositoryInterface $fileLocationRepository */
        $fileLocationRepository = $container->get(FileLocationRepositoryInterface::class);
        return new StaticMappedResourceHandler(
            $fileLocationRepository,
            $this->configureMiddleware($config)
        );
    }
}
