# Provide a unique ID in your request

## The problem

You want a request-specific identifier via an HTTP request header for purposes of logging, tracking, etc.
If you add it via middleware, however, it is not present in your access logs.

Request identifiers are usually generated at the web-server level.
When you use dedicated web servers such as Apache or nginx, a load balancer, or a reverse proxy, these can be configured to create and inject a request ID before it reaches your application.
However, when using mezzio-swoole, the request handler runner we create **is** your web server.
It has listeners that take care of logging, which means that the request generated must _already_ have the identifier if you want to be able to log it.

This poses a problem: normally you will use middleware to propagate changes to the request.
How can you do it at the Swoole web server level?

## The solution

The answer is to provide a [delegator factory](https://docs.mezzio.dev/mezzio/v3/features/container/delegator-factories/) on the service that converts the Swoole HTTP request instance into the equivalent [PSR-7](https://www.php-fig.org/psr/psr-7/) HTTP request instance that is then passed to your application.

mezzio-swoole maps the `Psr\Http\Message\ServerRequestInterface` service to its `Mezzio\Swoole\ServerRequestSwooleFactory`.
That factory returns a _callable_ that accepts a `Swoole\HTTP\Request` instance and returns a `Psr\Http\Message\ServerRequestInterface` instance.
As such, your delegator factory will need to return a callable with the same signature.

In the following example, we use [ramsey/uuid](https://uuid.ramsey.dev/) to generate a unique request ID, and add it to an `X-Request-ID` header when returning the request.

```php
// In your App module's top-level source directory

declare(strict_types=1);

namespace App;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Ramsey\Uuid\Uuid;
use Swoole\Http\Request as SwooleHttpRequest;

class ServerRequestIdDecorator
{
    public function __invoke(ContainerInterface $container, string $serviceName, callable $factory): callable
    {
        return static fn (SwooleHttpRequest $swooleRequest): ServerRequestInterface => $factory($swooleRequest)
            ->withHeader('X-Request-ID', Uuid::uuid1());
    }
}
```

Then, in our App module's `ConfigProvider`, we would modify the dependency configuration to add the following:

```php
    public function getDependencies(): array
    {
        return [
            'delegators' => [
                \Psr\Http\Message\ServerRequestInterface::class => [
                    ServerRequestIdDecorator::class,
                ],
            ],
        ];
    }
```

This approach:

- Keeps the logic close to the web server.
- Utilizes facilities already built-in to Mezzio and mezzio-swoole.
- Allows other code to perform similar work in order to manipulate and modify the request.
