<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\Swoole\Event;

use Mezzio\Swoole\Log\AccessLogInterface;
use Mezzio\Swoole\SwooleEmitter;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Swoole\Http\Request as SwooleHttpRequest;
use Swoole\Http\Response as SwooleHttpResponse;
use Throwable;

/**
 * "Run" a request handler using Swoole.
 *
 * The RequestHandlerRunner will marshal a request using the composed factory, and
 * then pass the request to the composed handler. Finally, it emits the response
 * returned by the handler using the Swoole emitter.
 *
 * If the factory for generating the request raises an exception or throwable,
 * then the runner will use the composed error response generator to generate a
 * response, based on the exception or throwable raised.
 */
class RequestHandlerRequestListener
{
    /**
     * Factory capable of generating a SwooleEmitter instance from a Swoole HTTP
     * response.
     *
     * @var null|callable
     */
    private $emitterFactory;

    private AccessLogInterface $logger;

    private RequestHandlerInterface $requestHandler;

    /**
     * A factory capable of generating an error response in the scenario that
     * the $serverRequestFactory raises an exception during generation of the
     * request instance.
     *
     * The factory will receive the Throwable or Exception that caused the error,
     * and must return a Psr\Http\Message\ResponseInterface instance.
     *
     * @var callable
     */
    private $serverRequestErrorResponseGenerator;

    /**
     * A factory capable of generating a Psr\Http\Message\ServerRequestInterface instance.
     * The factory will the Swoole HTTP request as an argument.
     *
     * @var callable
     */
    private $serverRequestFactory;

    public function __construct(
        RequestHandlerInterface $requestHandler,
        callable $serverRequestFactory,
        callable $serverRequestErrorResponseGenerator,
        AccessLogInterface $logger,
        ?callable $emitterFactory = null
    ) {
        $this->requestHandler = $requestHandler;
        $this->logger         = $logger;

        // Factories are cast as Closures to ensure return type safety.
        $this->serverRequestFactory
            = static function (SwooleHttpRequest $request) use ($serverRequestFactory): ServerRequestInterface {
                return $serverRequestFactory($request);
            };

        $this->serverRequestErrorResponseGenerator
            = static function (Throwable $exception) use ($serverRequestErrorResponseGenerator): ResponseInterface {
                return $serverRequestErrorResponseGenerator($exception);
            };

        if ($emitterFactory) {
            $this->emitterFactory
                = static function (SwooleHttpResponse $response) use ($emitterFactory): SwooleEmitter {
                    return $emitterFactory($response);
                };
        }
    }

    public function __invoke(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $emitter = $this->createEmitterFromResponse($event->getResponse());

        try {
            $psr7Request = ($this->serverRequestFactory)($request);
        } catch (Throwable $e) {
            // Error in generating the request
            $psr7Response = ($this->serverRequestErrorResponseGenerator)($e);
            $emitter->emit($psr7Response);
            $this->logger->logAccessForPsr7Resource($request, $psr7Response);
            $event->responseSent();
            return;
        }

        $psr7Response = $this->requestHandler->handle($psr7Request);
        $emitter->emit($psr7Response);
        $this->logger->logAccessForPsr7Resource($request, $psr7Response);
        $event->responseSent();
    }

    private function createEmitterFromResponse(SwooleHttpResponse $response): SwooleEmitter
    {
        if ($this->emitterFactory) {
            return ($this->emitterFactory)($response);
        }

        return new SwooleEmitter($response);
    }
}
