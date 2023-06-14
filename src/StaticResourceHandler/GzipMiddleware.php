<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace Mezzio\Swoole\StaticResourceHandler;

use Mezzio\Swoole\Exception;
use Mezzio\Swoole\Exception\InvalidArgumentException;
use Swoole\Http\Request;
use Swoole\Http\Response;

use function explode;
use function fclose;
use function feof;
use function fgets;
use function fopen;
use function function_exists;
use function sprintf;
use function stream_filter_append;
use function trim;

use const STREAM_FILTER_READ;
use const ZLIB_ENCODING_DEFLATE;
use const ZLIB_ENCODING_GZIP;

class GzipMiddleware implements MiddlewareInterface
{
    /**
     * @psalm-var array<int, string>
     * @var array<int, string>
     */
    public const COMPRESSION_CONTENT_ENCODING_MAP = [
        ZLIB_ENCODING_DEFLATE => 'deflate',
        ZLIB_ENCODING_GZIP    => 'gzip',
    ];

    private int $compressionLevel;

    /**
     * @param int $compressionLevel Compression level to use. Values less than
     *     1 indicate no compression should occur.
     * @throws Exception\InvalidArgumentException For $compressionLevel values
     *     greater than 9.
     */
    public function __construct(int $compressionLevel = 0)
    {
        if ($compressionLevel > 9) {
            throw new InvalidArgumentException(sprintf(
                '%s only allows compression levels up to 9; received %d',
                self::class,
                $compressionLevel
            ));
        }

        $this->compressionLevel = $compressionLevel;
    }

    /**
     * {@inheritDoc}
     */
    public function __invoke(Request $request, string $filename, callable $next): StaticResourceResponse
    {
        $response = $next($request, $filename);

        if (! $this->shouldCompress($request)) {
            return $response;
        }

        $compressionEncoding = $this->getCompressionEncoding($request);
        if (null === $compressionEncoding) {
            return $response;
        }

        $response->setResponseContentCallback(
            function (Response $swooleResponse, string $filename) use ($compressionEncoding, $response): void {
                $swooleResponse->header(
                    'Content-Encoding',
                    GzipMiddleware::COMPRESSION_CONTENT_ENCODING_MAP[$compressionEncoding],
                    true
                );
                $swooleResponse->header('Connection', 'close', true);

                $handle = fopen($filename, 'rb');
                $params = [
                    'level'  => $this->compressionLevel,
                    'window' => $compressionEncoding,
                    'memory' => 9,
                ];
                stream_filter_append($handle, 'zlib.deflate', STREAM_FILTER_READ, $params);

                $countBytes = function_exists('mb_strlen') ? 'mb_strlen' : 'strlen';
                $length     = 0;
                while (! feof($handle)) {
                    $line    = fgets($handle, 4096);
                    $length += $countBytes($line);
                    $swooleResponse->write($line);
                }

                fclose($handle);
                $response->setContentLength($length);
                $swooleResponse->header('Content-Length', (string) $length, true);
                $swooleResponse->end();
            }
        );
        return $response;
    }

    /**
     * Is gzip available for current request
     */
    private function shouldCompress(Request $request): bool
    {
        return $this->compressionLevel > 0
            && isset($request->header['accept-encoding']);
    }

    /**
     * Get gzcompress compression encoding.
     */
    private function getCompressionEncoding(Request $request): ?int
    {
        foreach (explode(',', $request->header['accept-encoding']) as $acceptEncoding) {
            $acceptEncoding = trim($acceptEncoding);
            if ('gzip' === $acceptEncoding) {
                return ZLIB_ENCODING_GZIP;
            }

            if ('deflate' === $acceptEncoding) {
                return ZLIB_ENCODING_DEFLATE;
            }
        }

        return null;
    }
}
