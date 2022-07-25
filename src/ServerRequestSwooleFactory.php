<?php // phpcs:disable WebimpressCodingStandard.NamingConventions.ValidVariableName.NotCamelCaps

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace Mezzio\Swoole;

use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\ServerRequestFilter\FilterServerRequestInterface;
use Laminas\Diactoros\ServerRequestFilter\FilterUsingXForwardedHeaders;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Swoole\Http\Request as SwooleHttpRequest;

use function array_change_key_case;
use function array_key_exists;
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
    public function __invoke(ContainerInterface $container): callable
    {
        $requestFilter = $container->has(FilterServerRequestInterface::class)
            ? $container->get(FilterServerRequestInterface::class)
            : FilterUsingXForwardedHeaders::trustReservedSubnets();

        $stripXForwardedHeaders = function (array $headers): array {
            /** @psalm-var list<string> */
            static $disallowedHeaders = [
                'X-FORWARDED-FOR',
                'X-FORWARDED-HOST',
                'X-FORWARDED-PORT',
                'X-FORWARDED-PROTO',
            ];

            $headers = array_change_key_case($headers, CASE_UPPER);
            foreach ($disallowedHeaders as $name) {
                if (array_key_exists($name, $headers)) {
                    unset($headers[$name]);
                }
            }

            return $headers;
        };

        // phpcs:disable Generic.Files.LineLength.TooLong
        return static function (SwooleHttpRequest $request) use ($requestFilter, $stripXForwardedHeaders): ServerRequestInterface {
            // Aggregate values from Swoole request object
            $get     = $request->get ?? [];
            $post    = $request->post ?? [];
            $cookie  = $request->cookie ?? [];
            $files   = $request->files ?? [];
            $server  = $request->server ?? [];
            $headers = $request->header ?? [];

            // Normalize SAPI params
            $server = array_change_key_case($server, CASE_UPPER);

            $request = new ServerRequest(
                $server,
                normalizeUploadedFiles($files),
                marshalUriFromSapi($server, $stripXForwardedHeaders($headers)),
                marshalMethodFromSapi($server),
                new SwooleStream($request),
                $headers,
                $cookie,
                $get,
                $post,
                marshalProtocolVersionFromSapi($server)
            );

            return $requestFilter instanceof FilterServerRequestInterface
                ? $requestFilter($request)
                : $request;
        };
        // phpcs:enable Generic.Files.LineLength.TooLong
    }
}
