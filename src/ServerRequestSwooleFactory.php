<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\Swoole;

use Laminas\Diactoros\ServerRequest;
use Psr\Container\ContainerInterface;
use Swoole\Http\Request as SwooleHttpRequest;

use function Laminas\Diactoros\marshalMethodFromSapi;
use function Laminas\Diactoros\marshalProtocolVersionFromSapi;
use function Laminas\Diactoros\marshalUriFromSapi;
use function Laminas\Diactoros\normalizeUploadedFiles;

use const CASE_UPPER;

/**
 * Return a factory for generating a server request from Swoole.
 */
class ServerRequestSwooleFactory
{
    public function __invoke(ContainerInterface $container) : callable
    {
        return function (SwooleHttpRequest $request) {
            // Aggregate values from Swoole request object
            $get     = $request->get ?? [];
            $post    = $request->post ?? [];
            $cookie  = $request->cookie ?? [];
            $files   = $request->files ?? [];
            $server  = $request->server ?? [];
            $headers = $request->header ?? [];

            // Normalize SAPI params
            $server = array_change_key_case($server, CASE_UPPER);

            return new ServerRequest(
                $server,
                normalizeUploadedFiles($files),
                marshalUriFromSapi($server, $headers),
                marshalMethodFromSapi($server),
                new SwooleStream($request),
                $headers,
                $cookie,
                $get,
                $post,
                marshalProtocolVersionFromSapi($server)
            );
        };
    }
}
