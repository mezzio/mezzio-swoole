<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace Mezzio\Swoole;

use Mezzio\Swoole\StaticResourceHandler\CacheControlMiddleware;
use Mezzio\Swoole\StaticResourceHandler\ClearStatCacheMiddleware;
use Mezzio\Swoole\StaticResourceHandler\ContentTypeFilterMiddleware;
use Mezzio\Swoole\StaticResourceHandler\ETagMiddleware;
use Mezzio\Swoole\StaticResourceHandler\GzipMiddleware;
use Mezzio\Swoole\StaticResourceHandler\HeadMiddleware;
use Mezzio\Swoole\StaticResourceHandler\LastModifiedMiddleware;
use Mezzio\Swoole\StaticResourceHandler\MethodNotAllowedMiddleware;
use Mezzio\Swoole\StaticResourceHandler\OptionsMiddleware;
use Psr\Container\ContainerInterface;

use function is_string;

abstract class AbstractStaticResourceHandlerFactory
{
    /** @return StaticResourceHandlerInterface */
    abstract public function __invoke(ContainerInterface $container);

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
    protected function configureMiddleware(array $config): array
    {
        $middleware = [
            new ContentTypeFilterMiddleware(
                $config['type-map'] ?? ContentTypeFilterMiddleware::TYPE_MAP_DEFAULT
            ),
            new MethodNotAllowedMiddleware(),
            new OptionsMiddleware(),
            new HeadMiddleware(),
        ];

        $compressionLevel = $config['gzip']['level'] ?? 0;
        if ($compressionLevel > 0) {
            $middleware[] = new GzipMiddleware($compressionLevel);
        }

        $clearStatCacheInterval = $config['clearstatcache-interval'] ?? false;
        if ($clearStatCacheInterval) {
            $middleware[] = new ClearStatCacheMiddleware((int) $clearStatCacheInterval);
        }

        $directiveList          = $config['directives'] ?? [];
        $cacheControlDirectives = [];
        $lastModifiedDirectives = [];
        $etagDirectives         = [];

        foreach ($directiveList as $regex => $directives) {
            if (isset($directives['cache-control'])) {
                $cacheControlDirectives[$regex] = $directives['cache-control'];
            }

            if (isset($directives['last-modified']) && is_string($regex)) {
                $lastModifiedDirectives[] = $regex;
            }

            if (isset($directives['etag']) && is_string($regex)) {
                $etagDirectives[] = $regex;
            }
        }

        if ($cacheControlDirectives !== []) {
            $middleware[] = new CacheControlMiddleware($cacheControlDirectives);
        }

        if ($lastModifiedDirectives !== []) {
            $middleware[] = new LastModifiedMiddleware($lastModifiedDirectives);
        }

        if ($etagDirectives !== []) {
            $middleware[] = new ETagMiddleware(
                $etagDirectives,
                $config['etag-type'] ?? ETagMiddleware::ETAG_VALIDATION_WEAK
            );
        }

        return $middleware;
    }
}
