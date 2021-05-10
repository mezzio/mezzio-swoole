<?php

declare(strict_types=1);

namespace Mezzio\Swoole\Event;

use Mezzio\Swoole\Log\AccessLogInterface;
use Mezzio\Swoole\StaticResourceHandlerInterface;

class StaticResourceRequestListener
{
    private AccessLogInterface $logger;

    private StaticResourceHandlerInterface $staticResourceHandler;

    public function __construct(
        StaticResourceHandlerInterface $staticResourceHandler,
        AccessLogInterface $logger
    ) {
        $this->staticResourceHandler = $staticResourceHandler;
        $this->logger                = $logger;
    }

    public function __invoke(RequestEvent $event): void
    {
        $request  = $event->getRequest();
        $response = $event->getResponse();

        $staticResourceResponse = $this->staticResourceHandler->processStaticResource($request, $response);

        if (! $staticResourceResponse) {
            return;
        }

        $this->logger->logAccessForStaticResource($request, $staticResourceResponse);
        $event->responseSent();
    }
}
