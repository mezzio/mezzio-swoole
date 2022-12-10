<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace Mezzio\Swoole\Event;

use Mezzio\Swoole\Log\AccessLogInterface;
use Mezzio\Swoole\StaticResourceHandler\StaticResourceResponse;
use Mezzio\Swoole\StaticResourceHandlerInterface;

class StaticResourceRequestListener
{
    public function __construct(
        private StaticResourceHandlerInterface $staticResourceHandler,
        private AccessLogInterface $logger
    ) {
    }

    public function __invoke(RequestEvent $event): void
    {
        $request  = $event->getRequest();
        $response = $event->getResponse();

        $staticResourceResponse = $this->staticResourceHandler->processStaticResource($request, $response);

        if (! $staticResourceResponse instanceof StaticResourceResponse) {
            return;
        }

        $this->logger->logAccessForStaticResource($request, $staticResourceResponse);
        $event->responseSent();
    }
}
