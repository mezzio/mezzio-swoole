<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

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
