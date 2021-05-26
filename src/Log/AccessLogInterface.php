<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace Mezzio\Swoole\Log;

use Mezzio\Swoole\StaticResourceHandler\StaticResourceResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Swoole\Http\Request;

interface AccessLogInterface extends LoggerInterface
{
    public function logAccessForStaticResource(Request $request, StaticResourceResponse $response): void;

    public function logAccessForPsr7Resource(Request $request, ResponseInterface $response): void;
}
