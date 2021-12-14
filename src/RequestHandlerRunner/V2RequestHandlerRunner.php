<?php

declare(strict_types=1);

namespace Mezzio\Swoole\RequestHandlerRunner;

use Laminas\HttpHandlerRunner\RequestHandlerRunnerInterface;
use Mezzio\Swoole\Exception\InvalidArgumentException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Swoole\Http\Server as SwooleHttpServer;

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
