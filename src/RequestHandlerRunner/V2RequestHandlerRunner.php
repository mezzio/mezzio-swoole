<?php

declare(strict_types=1);

namespace Mezzio\Swoole\RequestHandlerRunner;

use Laminas\HttpHandlerRunner\RequestHandlerRunnerInterface;
use Mezzio\Swoole\Exception\InvalidArgumentException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Swoole\Http\Server as SwooleHttpServer;

/**
 * Starts a Swoole web server that handles incoming requests.
 *
 * Registers callbacks on each server event that marshal a typed event with the
 * arguments provided to the callback, and then dispatches the event using a
 * PSR-14 dispatcher.
 *
 * This version will be renamed to Mezzio\Swoole\SwooleRequestHandlerRunner in
 * version 4.
 *
 * @deprecated since 3.6.0; will remove in 4.0.0.
 */
final class V2RequestHandlerRunner implements
    RequestHandlerConstantsInterface,
    RequestHandlerRunnerInterface
{
    use RequestHandlerRunnerTrait;

    public function __construct(
        SwooleHttpServer $httpServer,
        EventDispatcherInterface $dispatcher
    ) {
        // The HTTP server should not yet be running
        if ($httpServer->getMasterPid() > 0 || $httpServer->getManagerPid() > 0) {
            throw new InvalidArgumentException('The Swoole server has already been started');
        }
        $this->httpServer = $httpServer;
        $this->dispatcher = $dispatcher;
    }
}
