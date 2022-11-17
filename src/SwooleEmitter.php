<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace Mezzio\Swoole;

use Dflydev\FigCookies\SetCookies;
use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitterTrait;
use Psr\Http\Message\ResponseInterface;
use Swoole\Http\Response as SwooleHttpResponse;

use function extension_loaded;
use function implode;
use function substr;

use const PHP_SAPI;

class SwooleEmitter implements EmitterInterface
{
    use SapiEmitterTrait;

    /**
     * @see https://www.swoole.co.uk/docs/modules/swoole-http-server/methods-properties#swoole-http-response-write
     *
     * @var int
     */
    public const CHUNK_SIZE = 2_097_152;

    public function __construct(private SwooleHttpResponse $swooleResponse)
    {
    }

    /**
     * Emits a response for the Swoole environment.
     */
    public function emit(ResponseInterface $response): bool
    {
        if (! extension_loaded('swoole') && ! extension_loaded('openswoole')) {
            return false;
        }

        if (PHP_SAPI !== 'cli') {
            return false;
        }

        $this->emitStatusCode($response);
        $this->emitHeaders($response);
        $this->emitCookies($response);
        $this->emitBody($response);
        return true;
    }

    /**
     * Emit the status code
     */
    private function emitStatusCode(ResponseInterface $response): void
    {
        $this->swooleResponse->status($response->getStatusCode());
    }

    /**
     * Emit the headers
     */
    private function emitHeaders(ResponseInterface $response): void
    {
        foreach ($response->withoutHeader(SetCookies::SET_COOKIE_HEADER)->getHeaders() as $name => $values) {
            $name = $this->filterHeader($name);
            $this->swooleResponse->header($name, implode(', ', $values));
        }
    }

    /**
     * Emit the message body.
     */
    private function emitBody(ResponseInterface $response): void
    {
        $body = $response->getBody();

        if ($body->isSeekable()) {
            $body->rewind();
        }

        if (! $body->isReadable()) {
            $this->swooleResponse->end((string) $body);
            return;
        }

        if ($body->getSize() !== null && $body->getSize() <= static::CHUNK_SIZE) {
            $this->swooleResponse->end($body->getContents());
            return;
        }

        while (! $body->eof()) {
            $this->swooleResponse->write($body->read(static::CHUNK_SIZE));
        }

        $this->swooleResponse->end();
    }

    /**
     * Emit the cookies
     */
    private function emitCookies(ResponseInterface $response): void
    {
        foreach (SetCookies::fromResponse($response)->getAll() as $cookie) {
            $sameSite = $cookie->getSameSite() !== null ? substr($cookie->getSameSite()->asString(), 9) : '';

            $this->swooleResponse->cookie(
                $cookie->getName(),
                (string) $cookie->getValue(),
                $cookie->getExpires(),
                $cookie->getPath() ?: '/',
                $cookie->getDomain() ?: '',
                $cookie->getSecure(),
                $cookie->getHttpOnly(),
                $sameSite
            );
        }
    }
}
