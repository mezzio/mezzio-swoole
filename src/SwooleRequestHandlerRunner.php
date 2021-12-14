<?php

declare(strict_types=1);

namespace Mezzio\Swoole;

use Laminas\HttpHandlerRunner\RequestHandlerRunner;
use Mezzio\Swoole\Exception\InvalidArgumentException;
use Mezzio\Swoole\RequestHandlerRunner\RequestHandlerConstantsInterface;
use Mezzio\Swoole\RequestHandlerRunner\RequestHandlerRunnerTrait;
use Psr\EventDispatcher\EventDispatcherInterface;
use Swoole\Http\Server as SwooleHttpServer;

/**
 * Starts a Swoole web server that handles incoming requests.
 *
 * Registers callbacks on each server event that marshal a typed event with the
 * arguments provided to the callback, and then dispatches the event using a
 * PSR-14 dispatcher.
 *
 * This version will be marked final in version 4.0, and updated to implement
 * the laminas-httphandlerrunner RequstHandlerRunnerInterface at that time.
 * As such EXTENSION IS DEPRECATED.
 */
class SwooleRequestHandlerRunner extends RequestHandlerRunner implements RequestHandlerConstantsInterface
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
