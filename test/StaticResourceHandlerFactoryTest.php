<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace MezzioTest\Swoole;

use Mezzio\Swoole\StaticResourceHandler\CacheControlMiddleware;
use Mezzio\Swoole\StaticResourceHandler\ClearStatCacheMiddleware;
use Mezzio\Swoole\StaticResourceHandler\ContentTypeFilterMiddleware;
use Mezzio\Swoole\StaticResourceHandler\ETagMiddleware;
use Mezzio\Swoole\StaticResourceHandler\HeadMiddleware;
use Mezzio\Swoole\StaticResourceHandler\LastModifiedMiddleware;
use Mezzio\Swoole\StaticResourceHandler\MethodNotAllowedMiddleware;
use Mezzio\Swoole\StaticResourceHandler\MiddlewareInterface;
use Mezzio\Swoole\StaticResourceHandler\OptionsMiddleware;
use Mezzio\Swoole\StaticResourceHandlerFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use ReflectionProperty;
use Webmozart\Assert\Assert;

use function sprintf;

class StaticResourceHandlerFactoryTest extends TestCase
{
    use AttributeAssertionTrait;

    /** @psalm-var MockObject&ContainerInterface */
    private ContainerInterface|MockObject $container;

    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
    }

    /**
     * @psalm-param class-string $type
     * @psalm-param list<MiddlewareInterface> $middlewareList
     */
    public function assertHasMiddlewareOfType(string $type, array $middlewareList): void
    {
        $middleware = $this->getMiddlewareByType($type, $middlewareList);
        $this->assertInstanceOf($type, $middleware);
    }

    /**
     * @psalm-param class-string $type
     * @psalm-param list<MiddlewareInterface> $middlewareList
     */
    public function getMiddlewareByType(string $type, array $middlewareList): MiddlewareInterface
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

    public function testFactoryConfiguresHandlerBasedOnConfiguration(): void
    {
        $config = [
            'mezzio-swoole' => [
                'swoole-http-server' => [
                    'static-files' => [
                        'document-root'           => __DIR__ . '/TestAsset',
                        'type-map'                => [
                            'png' => 'image/png',
                            'txt' => 'text/plain',
                        ],
                        'clearstatcache-interval' => 3600,
                        'etag-type'               => 'strong',
                        'directives'              => [
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
                                'etag'          => true,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->container->method('get')->with('config')->willReturn($config);

        $factory = new StaticResourceHandlerFactory();

        $handler = $factory($this->container);

        $this->assertAttributeSame(
            $config['mezzio-swoole']['swoole-http-server']['static-files']['document-root'],
            'docRoot',
            $handler
        );

        $r = new ReflectionProperty($handler, 'middleware');

        $middleware = $r->getValue($handler);
        Assert::isList($middleware);
        Assert::allIsInstanceOf($middleware, MiddlewareInterface::class);

        $this->assertHasMiddlewareOfType(ContentTypeFilterMiddleware::class, $middleware);
        $this->assertHasMiddlewareOfType(MethodNotAllowedMiddleware::class, $middleware);
        $this->assertHasMiddlewareOfType(OptionsMiddleware::class, $middleware);
        $this->assertHasMiddlewareOfType(HeadMiddleware::class, $middleware);
        $this->assertHasMiddlewareOfType(ClearStatCacheMiddleware::class, $middleware);

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
