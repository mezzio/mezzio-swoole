<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Swoole;

use Mezzio\Swoole\StaticResourceHandler;
use Mezzio\Swoole\StaticResourceHandlerFactory;
use Mezzio\Swoole\StaticResourceHandler\FileLocationRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use ReflectionProperty;

use function sprintf;

class StaticResourceHandlerFactoryTest extends TestCase
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

        $factory = new StaticResourceHandlerFactory();

        $handler = $factory($this->container->reveal());

        $this->assertAttributeSame(
            $fileLocRepo,
            'fileLocationRepo',
            $handler
        );

        $r = new ReflectionProperty($handler, 'middleware');
        $r->setAccessible(true);
        $middleware = $r->getValue($handler);

        $this->assertHasMiddlewareOfType(StaticResourceHandler\ContentTypeFilterMiddleware::class, $middleware);
        $this->assertHasMiddlewareOfType(StaticResourceHandler\MethodNotAllowedMiddleware::class, $middleware);
        $this->assertHasMiddlewareOfType(StaticResourceHandler\OptionsMiddleware::class, $middleware);
        $this->assertHasMiddlewareOfType(StaticResourceHandler\HeadMiddleware::class, $middleware);
        $this->assertHasMiddlewareOfType(StaticResourceHandler\ClearStatCacheMiddleware::class, $middleware);
        $this->assertHasMiddlewareOfType(StaticResourceHandler\GzipMiddleware::class, $middleware);

        $contentTypeFilter = $this->getMiddlewareByType(
            StaticResourceHandler\ContentTypeFilterMiddleware::class,
            $middleware
        );
        $this->assertAttributeSame(
            $config['mezzio-swoole']['swoole-http-server']['static-files']['type-map'],
            'typeMap',
            $contentTypeFilter
        );

        $clearStatsCache = $this->getMiddlewareByType(
            StaticResourceHandler\ClearStatCacheMiddleware::class,
            $middleware
        );
        $this->assertAttributeSame(
            $config['mezzio-swoole']['swoole-http-server']['static-files']['clearstatcache-interval'],
            'interval',
            $clearStatsCache
        );

        $this->assertHasMiddlewareOfType(StaticResourceHandler\CacheControlMiddleware::class, $middleware);
        $cacheControl = $this->getMiddlewareByType(StaticResourceHandler\CacheControlMiddleware::class, $middleware);
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

        $this->assertHasMiddlewareOfType(StaticResourceHandler\LastModifiedMiddleware::class, $middleware);
        $lastModified = $this->getMiddlewareByType(StaticResourceHandler\LastModifiedMiddleware::class, $middleware);
        $this->assertAttributeEquals(
            ['/\.png$/'],
            'lastModifiedDirectives',
            $lastModified
        );

        $this->assertHasMiddlewareOfType(StaticResourceHandler\ETagMiddleware::class, $middleware);
        $eTag = $this->getMiddlewareByType(StaticResourceHandler\ETagMiddleware::class, $middleware);
        $this->assertAttributeEquals(
            ['/\.png$/'],
            'etagDirectives',
            $eTag
        );
        $this->assertAttributeEquals(
            StaticResourceHandler\ETagMiddleware::ETAG_VALIDATION_STRONG,
            'etagValidationType',
            $eTag
        );
    }
}
