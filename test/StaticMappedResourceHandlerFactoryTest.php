<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Swoole;

use Mezzio\Swoole\StaticMappedResourceHandler;
use Mezzio\Swoole\StaticMappedResourceHandlerFactory;
use Mezzio\Swoole\StaticResourceHandler\FileLocationRepositoryInterface;
use Mezzio\Swoole\StaticResourceHandler\CacheControlMiddleware;
use Mezzio\Swoole\StaticResourceHandler\ContentTypeFilterMiddleware;
use Mezzio\Swoole\StaticResourceHandler\MethodNotAllowedMiddleware;
use Mezzio\Swoole\StaticResourceHandler\OptionsMiddleware;
use Mezzio\Swoole\StaticResourceHandler\HeadMiddleware;
use Mezzio\Swoole\StaticResourceHandler\ClearStatCacheMiddleware;
use Mezzio\Swoole\StaticResourceHandler\GzipMiddleware;
use Mezzio\Swoole\StaticResourceHandler\LastModifiedMiddleware;
use Mezzio\Swoole\StaticResourceHandler\ETagMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use ReflectionProperty;

use function sprintf;

class StaticMappedResourceHandlerFactoryTest extends TestCase
{
    protected function setUp() : void
    {
        $this->container = $this->prophesize(ContainerInterface::class);
        $this->mockFileLocRepo = $this->prophesize(FileLocationRepositoryInterface::class);
    }

    public function assertHasMiddlewareOfType(string $type, array $middlewareList)
    {
        $middleware = $this->getMiddlewareByType($type, $middlewareList);
        $this->assertInstanceOf($type, $middleware);
    }

    public function getMiddlewareByType(string $type, array $middlewareList)
    {
        foreach ($middlewareList as $middleware) {
            if ($middleware instanceof $type) {
                return $middleware;
            }
        }
        $this->fail(sprintf(
            'Could not find middleware of type %s',
            $type
        ));
    }

    public function testFactoryConfiguresHandlerBasedOnConfiguration()
    {
        $config = [
            'mezzio-swoole' => [
                'swoole-http-server' => [
                    'static-files' => [
                        'document-root' => __DIR__ . '/TestAsset',
                        'type-map' => [
                            'png' => 'image/png',
                            'txt' => 'text/plain',
                        ],
                        'clearstatcache-interval' => 3600,
                        'etag-type' => 'strong',
                        'directives' => [
                            '/\.txt$/' => [
                                'cache-control' => [
                                    'no-cache',
                                    'must-revalidate',
                                ],
                            ],
                            '/\.png$/' => [
                                'cache-control' => [
                                    'public',
                                    'no-transform',
                                ],
                                'last-modified' => true,
                                'etag' => true,
                            ],
                        ],
                        'gzip' => [
                            'level' => 1
                        ]
                    ],
                ],
            ],
        ];

        $fileLocRepo = $this->mockFileLocRepo->reveal();
        $this->container->get('config')->willReturn($config);
        $this->container->get(FileLocationRepositoryInterface::class)->willReturn($fileLocRepo);

        $factory = new StaticMappedResourceHandlerFactory();

        $handler = $factory($this->container->reveal());

        $this->assertAttributeSame(
            $fileLocRepo,
            'fileLocationRepo',
            $handler
        );

        $r = new ReflectionProperty($handler, 'middleware');
        $r->setAccessible(true);
        $middleware = $r->getValue($handler);

        $this->assertHasMiddlewareOfType(ContentTypeFilterMiddleware::class, $middleware);
        $this->assertHasMiddlewareOfType(MethodNotAllowedMiddleware::class, $middleware);
        $this->assertHasMiddlewareOfType(OptionsMiddleware::class, $middleware);
        $this->assertHasMiddlewareOfType(HeadMiddleware::class, $middleware);
        $this->assertHasMiddlewareOfType(ClearStatCacheMiddleware::class, $middleware);
        $this->assertHasMiddlewareOfType(GzipMiddleware::class, $middleware);

        $contentTypeFilter = $this->getMiddlewareByType(
            ContentTypeFilterMiddleware::class,
            $middleware
        );
        $this->assertAttributeSame(
            $config['mezzio-swoole']['swoole-http-server']['static-files']['type-map'],
            'typeMap',
            $contentTypeFilter
        );

        $clearStatsCache = $this->getMiddlewareByType(
            ClearStatCacheMiddleware::class,
            $middleware
        );
        $this->assertAttributeSame(
            $config['mezzio-swoole']['swoole-http-server']['static-files']['clearstatcache-interval'],
            'interval',
            $clearStatsCache
        );

        $this->assertHasMiddlewareOfType(CacheControlMiddleware::class, $middleware);
        $cacheControl = $this->getMiddlewareByType(CacheControlMiddleware::class, $middleware);
        $this->assertAttributeEquals(
            [
                '/\.txt$/' => [
                    'no-cache',
                    'must-revalidate',
                ],
                '/\.png$/' => [
                    'public',
                    'no-transform',
                ],
            ],
            'cacheControlDirectives',
            $cacheControl
        );

        $this->assertHasMiddlewareOfType(LastModifiedMiddleware::class, $middleware);
        $lastModified = $this->getMiddlewareByType(LastModifiedMiddleware::class, $middleware);
        $this->assertAttributeEquals(
            ['/\.png$/'],
            'lastModifiedDirectives',
            $lastModified
        );

        $this->assertHasMiddlewareOfType(ETagMiddleware::class, $middleware);
        $eTag = $this->getMiddlewareByType(ETagMiddleware::class, $middleware);
        $this->assertAttributeEquals(
            ['/\.png$/'],
            'etagDirectives',
            $eTag
        );
        $this->assertAttributeEquals(
            ETagMiddleware::ETAG_VALIDATION_STRONG,
            'etagValidationType',
            $eTag
        );
    }
}
