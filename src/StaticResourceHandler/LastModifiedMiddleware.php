<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace Mezzio\Swoole\StaticResourceHandler;

use DateTimeImmutable;
use DateTimeZone;
use IntlDateFormatter;
use Swoole\Http\Request;

use function filemtime;
use function preg_match;

class LastModifiedMiddleware implements MiddlewareInterface
{
    use ValidateRegexTrait;

    /** @var string[] */
    private array $lastModifiedDirectives = [];

    /**
     * @param string[] $lastModifiedDirectives Array of regexex indicating
     *     paths/file types that should emit a Last-Modified header.
     */
    public function __construct(array $lastModifiedDirectives = [])
    {
        $this->validateRegexList($lastModifiedDirectives, 'Last-Modified');
        $this->lastModifiedDirectives = $lastModifiedDirectives;
    }

    public function __invoke(Request $request, string $filename, callable $next): StaticResourceResponse
    {
        $response = $next($request, $filename);

        if (! $this->getLastModifiedFlagForPath($request->server['request_uri'])) {
            return $response;
        }

        $lastModified = filemtime($filename) ?: 0;
        $lastModified = new DateTimeImmutable('@' . $lastModified, new DateTimeZone('GMT'));

        $formattedLastModified = IntlDateFormatter::formatObject(
            $lastModified,
            'EEEE dd-MMM-yy HH:mm:ss z'
        );

        $response->addHeader('Last-Modified', $formattedLastModified);

        if ($this->isUnmodified($request, $formattedLastModified)) {
            $response->setStatus(304);
            $response->disableContent();
        }

        return $response;
    }

    private function getLastModifiedFlagForPath(string $path): bool
    {
        foreach ($this->lastModifiedDirectives as $regexp) {
            if (preg_match($regexp, $path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return bool Returns true if the If-Modified-Since request header matches
     *     the $lastModifiedTime value; in such cases, no content is returned.
     */
    private function isUnmodified(Request $request, string $lastModified): bool
    {
        $ifModifiedSince = $request->header['if-modified-since'] ?? '';
        if ('' === $ifModifiedSince) {
            return false;
        }
        return new DateTimeImmutable($ifModifiedSince) >= new DateTimeImmutable($lastModified);
    }
}
