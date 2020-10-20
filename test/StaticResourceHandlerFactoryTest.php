<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Swoole;

use Mezzio\Swoole\StaticResourceHandler;
use Mezzio\Swoole\StaticResourceHandler\MiddlewareInterface;
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

    /**
     * @var ContainerInterface|MockObject
     * @psalm-var MockObject&ContainerInterface
     */
    private $container;

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
        $r->setAccessible(true);
        $middleware = $r->getValue($handler);
        Assert::isList($middleware);
        Assert::allIsInstanceOf($middleware, MiddlewareInterface::class);

        $this->assertHasMiddlewareOfType(StaticResourceHandler\ContentTypeFilterMiddleware::class, $middleware);
        $this->assertHasMiddlewareOfType(StaticResourceHandler\MethodNotAllowedMiddleware::class, $middleware);
        $this->assertHasMiddlewareOfType(StaticResourceHandler\OptionsMiddleware::class, $middleware);
        $this->assertHasMiddlewareOfType(StaticResourceHandler\HeadMiddleware::class, $middleware);
        $this->assertHasMiddlewareOfType(StaticResourceHandler\ClearStatCacheMiddleware::class, $middleware);

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
