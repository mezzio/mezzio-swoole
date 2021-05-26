<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace Mezzio\Swoole\Event;

use Mezzio\Response\ServerRequestErrorResponseGenerator;
use Mezzio\Swoole\Log\AccessLogInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Webmozart\Assert\Assert;

final class RequestHandlerRequestListenerFactory
{
    public function __invoke(ContainerInterface $container): RequestHandlerRequestListener
    {
        $pipeline = $container->get('Mezzio\ApplicationPipeline');
        Assert::isInstanceOf($pipeline, RequestHandlerInterface::class);

        $requestFactory = $container->get(ServerRequestInterface::class);
        Assert::isCallable($requestFactory);

        $responseGenerator = $container->get(ServerRequestErrorResponseGenerator::class);
        Assert::isCallable($responseGenerator);

        $logger = $container->get(AccessLogInterface::class);
        Assert::isInstanceOf($logger, AccessLogInterface::class);

        return new RequestHandlerRequestListener(
            $pipeline,
            $requestFactory,
            $responseGenerator,
            $logger
        );
    }
}
