<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\Swoole;

use Psr\Container\ContainerInterface;

use function getcwd;

/**
 * Create and return a StaticResourceHandler
 *
 * Uses the following configuration in order to configure and serve static
 * resources from the filesystem:
 *
 * <code>
 * 'mezzio-swoole' => [
 *     'swoole-http-server' => [
 *         'static-files' => [
 *             'document-root' => '/path/to/static/files/to/serve', // usu getcwd() . /public/
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
class StaticResourceHandlerFactory
{
    public function __invoke(ContainerInterface $container) : StaticResourceHandler
    {
        $config = $container->get('config')['mezzio-swoole']['swoole-http-server']['static-files'] ?? [];
        return new StaticResourceHandler(
            $container->get(StaticResourceHandler\FileLocationRepositoryInterface::class),
            $this->configureMiddleware($config)
        );
    }

    /**
     * Prepare the list of middleware based on configuration provided.
     *
     * Examines the configuration provided and uses it to create the list of
     * middleware to return. By default, the following are always present:
     *
     * - MethodNotAllowedMiddleware
     * - OptionsMiddleware
     * - HeadMiddleware
     *
     * If the clearstatcache-interval setting is present and non-false, it is
     * used to seed a ClearStatCacheMiddleware instance.
     *
     * If any cache-control directives are discovered, they are used to seed a
     * CacheControlMiddleware instance.
     *
     * If any last-modified directives are discovered, they are used to seed a
     * LastModifiedMiddleware instance.
     *
     * If any etag directives are discovered, they are used to seed a
     * ETagMiddleware instance.
     *
     * This method is marked protected to allow users to extend this factory
     * in order to provide their own middleware and/or configuration schema.
     *
     * @return StaticResourceHandler\MiddlewareInterface[]
     */
    protected function configureMiddleware(array $config) : array
    {
        $middleware = [
            new StaticResourceHandler\ContentTypeFilterMiddleware(
                $config['type-map'] ?? StaticResourceHandler\ContentTypeFilterMiddleware::TYPE_MAP_DEFAULT
            ),
            new StaticResourceHandler\MethodNotAllowedMiddleware(),
            new StaticResourceHandler\OptionsMiddleware(),
            new StaticResourceHandler\HeadMiddleware(),
        ];

        $compressionLevel = $config['gzip']['level'] ?? 0;
        if ($compressionLevel > 0) {
            $middleware[] = new StaticResourceHandler\GzipMiddleware($compressionLevel);
        }

        $clearStatCacheInterval = $config['clearstatcache-interval'] ?? false;
        if ($clearStatCacheInterval) {
            $middleware[] = new StaticResourceHandler\ClearStatCacheMiddleware((int) $clearStatCacheInterval);
        }

        $directiveList = $config['directives'] ?? [];
        $cacheControlDirectives = [];
        $lastModifiedDirectives = [];
        $etagDirectives = [];

        foreach ($directiveList as $regex => $directives) {
            if (isset($directives['cache-control'])) {
                $cacheControlDirectives[$regex] = $directives['cache-control'];
            }
            if (isset($directives['last-modified'])) {
                $lastModifiedDirectives[] = $regex;
            }
            if (isset($directives['etag'])) {
                $etagDirectives[] = $regex;
            }
        }

        if ($cacheControlDirectives !== []) {
            $middleware[] = new StaticResourceHandler\CacheControlMiddleware($cacheControlDirectives);
        }

        if ($lastModifiedDirectives !== []) {
            $middleware[] = new StaticResourceHandler\LastModifiedMiddleware($lastModifiedDirectives);
        }

        if ($etagDirectives !== []) {
            $middleware[] = new StaticResourceHandler\ETagMiddleware(
                $etagDirectives,
                $config['etag-type'] ?? StaticResourceHandler\ETagMiddleware::ETAG_VALIDATION_WEAK
            );
        }

        return $middleware;
    }
}
