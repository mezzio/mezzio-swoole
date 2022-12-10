<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace Mezzio\Swoole\Event;

use Psr\EventDispatcher\StoppableEventInterface;
use Swoole\Http\Request as SwooleHttpRequest;
use Swoole\Http\Response as SwooleHttpResponse;

class RequestEvent implements StoppableEventInterface
{
    private bool $responseSent = false;

    public function __construct(private SwooleHttpRequest $request, private SwooleHttpResponse $response)
    {
    }

    public function isPropagationStopped(): bool
    {
        return $this->responseSent;
    }

    public function getRequest(): SwooleHttpRequest
    {
        return $this->request;
    }

    public function getResponse(): SwooleHttpResponse
    {
        return $this->response;
    }

    public function responseSent(): void
    {
        $this->responseSent = true;
    }
}
