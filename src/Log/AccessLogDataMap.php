<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace Mezzio\Swoole\Log;

use DateTimeImmutable;
use IntlDateFormatter;
use Mezzio\Swoole\StaticResourceHandler\StaticResourceResponse;
use Psr\Http\Message\ResponseInterface as PsrResponse;
use RuntimeException;
use Swoole\Http\Request as SwooleHttpRequest;

use function filter_var;
use function function_exists;
use function getcwd;
use function getenv;
use function gethostbyaddr;
use function gethostname;
use function http_build_query;
use function implode;
use function is_string;
use function microtime;
use function preg_match;
use function round;
use function sprintf;
use function str_starts_with;
use function strtolower;
use function substr;

use const FILTER_FLAG_IPV4;
use const FILTER_FLAG_IPV6;
use const FILTER_VALIDATE_IP;

class AccessLogDataMap
{
    /**
     * @var string
     */
    private const HOST_PORT_REGEX = '/^(?P<host>.*?)((?<!\]):(?P<port>\d+))?$/';

    /**
     * Timestamp when created, indicating end of request processing.
     */
    private float $endTime;

    private ?PsrResponse $psrResponse = null;

    private ?StaticResourceResponse $staticResource = null;

    public static function createWithPsrResponse(
        SwooleHttpRequest $request,
        PsrResponse $response,
        bool $useHostnameLookups = false
    ): self {
        $map              = new self($request, $useHostnameLookups);
        $map->psrResponse = $response;
        return $map;
    }

    public static function createWithStaticResource(
        SwooleHttpRequest $request,
        StaticResourceResponse $response,
        bool $useHostnameLookups = false
    ): self {
        $map                 = new self($request, $useHostnameLookups);
        $map->staticResource = $response;
        return $map;
    }

    /**
     * Client IP address of the request (%a)
     */
    public function getClientIp(): string
    {
        $headers = ['x-real-ip', 'client-ip', 'x-forwarded-for'];

        foreach ($headers as $header) {
            if (isset($this->request->header[$header])) {
                return $this->request->header[$header];
            }
        }

        return $this->getServerParamIp('REMOTE_ADDR');
    }

    /**
     * Local IP-address (%A)
     */
    public function getLocalIp(): string
    {
        return $this->getServerParamIp('REMOTE_ADDR');
    }

    /**
     * Filename (%f)
     *
     * @todo We likely need a way of injecting the gateway script, instead of
     *     assuming it's getcwd() . /public/index.php.
     * @todo We likely need a way of injecting the document root, instead of
     *     assuming it's getcwd() . /public.
     */
    public function getFilename(): string
    {
        if ($this->psrResponse !== null) {
            return getcwd() . '/public/index.php';
        }

        if ($this->staticResource !== null) {
            return getcwd() . '/public' . $this->getServerParam('PATH_INFO');
        }

        throw new RuntimeException(sprintf(
            'Initialized without a PSR-7 response or a %s instance',
            StaticResourceResponse::class
        ));
    }

    /**
     * Size of the message in bytes, excluding HTTP headers (%B, %b)
     */
    public function getBodySize(string $default): string
    {
        if ($this->psrResponse !== null) {
            return (string) $this->psrResponse->getBody()->getSize() ?: $default;
        }

        if ($this->staticResource !== null) {
            return (string) $this->staticResource->getContentLength() ?: $default;
        }

        throw new RuntimeException(sprintf(
            'Initialized without a PSR-7 response or a %s instance',
            StaticResourceResponse::class
        ));
    }

    /**
     * Remote hostname (%h)
     * Will log the IP address if hostnameLookups is false.
     */
    public function getRemoteHostname(): string
    {
        $ip = $this->getClientIp();

        return $ip !== '-' && $this->useHostnameLookups
            ? gethostbyaddr($ip)
            : $ip;
    }

    /**
     * The message protocol (%H)
     */
    public function getProtocol(): string
    {
        return $this->getServerParam('server_protocol');
    }

    /**
     * The request method (%m)
     */
    public function getMethod(): string
    {
        return $this->getServerParam('request_method');
    }

    /**
     * Returns a message header
     */
    public function getRequestHeader(string $name): string
    {
        return $this->request->header[strtolower($name)] ?? '-';
    }

    /**
     * Returns a message header
     */
    public function getResponseHeader(string $name): string
    {
        if ($this->psrResponse !== null) {
            return $this->psrResponse->getHeaderLine($name) ?: '-';
        }

        if ($this->staticResource !== null) {
            return $this->staticResource->getHeader($name) ?: '-';
        }

        throw new RuntimeException(sprintf(
            'Initialized without a PSR-7 response or a %s instance',
            StaticResourceResponse::class
        ));
    }

    /**
     * Returns a environment variable (%e)
     */
    public function getEnv(string $name): string
    {
        return getenv($name) ?: '-';
    }

    /**
     * Returns a cookie value (%{VARNAME}C)
     */
    public function getCookie(string $name): string
    {
        return $this->request->cookie[$name] ?? '-';
    }

    /**
     * The canonical port of the server serving the request. (%p)
     */
    public function getPort(string $format): string
    {
        switch ($format) {
            case 'canonical':
            case 'local':
                preg_match(self::HOST_PORT_REGEX, $this->request->header['host'] ?? '', $matches);
                $port   = $matches['port'] ?? null;
                $port   = $port ?: $this->getServerParam('server_port', '80');
                $scheme = $this->getServerParam('https', '');
                return $scheme && $port === '80' ? '443' : $port;
            default:
                return '-';
        }
    }

    /**
     * The query string (%q)
     * (prepended with a ? if a query string exists, otherwise an empty string).
     */
    public function getQuery(): string
    {
        $query = $this->request->get ?? [];
        return [] === $query ? '' : sprintf('?%s', http_build_query($query));
    }

    /**
     * Status. (%s)
     */
    public function getStatus(): string
    {
        if ($this->psrResponse !== null) {
            return (string) $this->psrResponse->getStatusCode();
        }

        if ($this->staticResource !== null) {
            return (string) $this->staticResource->getStatus();
        }

        throw new RuntimeException(sprintf(
            'Initialized without a PSR-7 response or a %s instance',
            StaticResourceResponse::class
        ));
    }

    /**
     * Remote user if the request was authenticated. (%u)
     */
    public function getRemoteUser(): string
    {
        return $this->getServerParam('REMOTE_USER');
    }

    /**
     * The URL path requested, not including any query string. (%U)
     */
    public function getPath(): string
    {
        return $this->getServerParam('PATH_INFO');
    }

    /**
     * The canonical ServerName of the server serving the request. (%v)
     */
    public function getHost(): string
    {
        return $this->getRequestHeader('host');
    }

    /**
     * The server name according to the UseCanonicalName setting. (%V)
     */
    public function getServerName(): string
    {
        return gethostname();
    }

    /**
     * First line of request. (%r)
     */
    public function getRequestLine(): string
    {
        return sprintf(
            '%s %s%s %s',
            $this->getMethod(),
            $this->getPath(),
            $this->getQuery(),
            $this->getProtocol()
        );
    }

    /**
     * Returns the response status line
     */
    public function getResponseLine(): string
    {
        $reasonPhrase = '';
        if ($this->psrResponse && $this->psrResponse->getReasonPhrase()) {
            $reasonPhrase .= sprintf(' %s', $this->psrResponse->getReasonPhrase());
        }

        return sprintf(
            '%s %d%s',
            $this->getProtocol(),
            $this->getStatus(),
            $reasonPhrase
        );
    }

    /**
     * Bytes transferred (received and sent), including request and headers (%S)
     */
    public function getTransferredSize(): string
    {
        return (string) ($this->getRequestMessageSize(0) + $this->getResponseMessageSize(0)) ?: '-';
    }

    /**
     * Get the request message size (including first line and headers)
     *
     * @param null|int $default
     */
    public function getRequestMessageSize($default = null): ?int
    {
        $strlen = function_exists('mb_strlen') ? 'mb_strlen' : 'strlen';

        $bodySize = $strlen($this->request->rawContent());

        if (null === $bodySize) {
            return $default;
        }

        $firstLine = $this->getRequestLine();

        $headers = [];

        foreach ($this->request->header as $header => $value) {
            if (is_string($value)) {
                $headers[] = sprintf('%s: %s', $header, $value);
                continue;
            }

            foreach ($value as $line) {
                $headers[] = sprintf('%s: %s', $header, $line);
            }
        }

        $headersSize = $strlen(implode("\r\n", $headers));

        return $strlen($firstLine) + 2 + $headersSize + 4 + $bodySize;
    }

    /**
     * Get the response message size (including first line and headers)
     *
     * @param null|int $default
     */
    public function getResponseMessageSize($default = null): ?int
    {
        if ($this->psrResponse !== null) {
            $bodySize = $this->psrResponse->getBody()->getSize();
        } elseif ($this->staticResource !== null) {
            $bodySize = $this->staticResource->getContentLength();
        } else {
            throw new RuntimeException(sprintf(
                'Initialized without a PSR-7 response or a %s instance',
                StaticResourceResponse::class
            ));
        }

        if (null === $bodySize) {
            return $default;
        }

        $strlen        = function_exists('mb_strlen') ? 'mb_strlen' : 'strlen';
        $firstLineSize = $strlen($this->getResponseLine());

        $headerSize = $this->psrResponse !== null
            ? $this->getPsrResponseHeaderSize()
            : $this->staticResource->getHeaderSize();

        return $firstLineSize + 2 + $headerSize + 4 + $bodySize;
    }

    /**
     * Returns the request time (%t, %{format}t)
     */
    public function getRequestTime(string $format): string
    {
        $begin = $this->getServerParam('request_time_float');
        $time  = $begin;

        if (str_starts_with($format, 'begin:')) {
            $format = substr($format, 6);
        } elseif (str_starts_with($format, 'end:')) {
            $time   = $this->endTime;
            $format = substr($format, 4);
        }

        switch ($format) {
            case 'sec':
                return sprintf('[%s]', round($time));
            case 'msec':
                return sprintf('[%s]', round($time * 1E3));
            case 'usec':
                return sprintf('[%s]', round($time * 1E6));
            default:
                // Cast to int first, as it may be a float
                $requestTime = new DateTimeImmutable('@' . (int) $time);
                return IntlDateFormatter::formatObject(
                    $requestTime,
                    '[' . StrftimeToICUFormatMap::mapStrftimeToICU($format, $requestTime) . ']'
                );
        }
    }

    /**
     * The time taken to serve the request. (%T, %{format}T)
     */
    public function getRequestDuration(string $format): string
    {
        $begin = $this->getServerParam('request_time_float');
        return match ($format) {
            'us' => (string) round(($this->endTime - $begin) * 1E6),
            'ms' => (string) round(($this->endTime - $begin) * 1E3),
            default => (string) round($this->endTime - $begin),
        };
    }

    private function __construct(
        private SwooleHttpRequest $request,
        private bool $useHostnameLookups
    ) {
        $this->endTime = microtime(true);
    }

    /**
     * Returns an server parameter value
     */
    private function getServerParam(string $key, string $default = '-'): string
    {
        $value = $this->request->server[strtolower($key)] ?? $default;
        return (string) $value;
    }

    /**
     * Returns an ip from the server params
     */
    private function getServerParamIp(string $key): string
    {
        $ip = $this->getServerParam($key);

        return false === filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6)
            ? '-'
            : $ip;
    }

    private function getPsrResponseHeaderSize(): int
    {
        if ($this->psrResponse === null) {
            return 0;
        }

        $headers = [];

        foreach ($this->psrResponse->getHeaders() as $header => $values) {
            foreach ($values as $value) {
                $headers[] = sprintf('%s: %s', $header, $value);
            }
        }

        $strlen = function_exists('mb_strlen') ? 'mb_strlen' : 'strlen';
        return $strlen(implode("\r\n", $headers));
    }
}
