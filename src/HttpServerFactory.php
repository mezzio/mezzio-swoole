<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace Mezzio\Swoole;

use ArrayAccess;
use Mezzio\Swoole\Exception\InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Swoole\Http\Server as SwooleHttpServer;
use Swoole\Runtime as SwooleRuntime;
use Webmozart\Assert\Assert;

use function assert;
use function defined;
use function in_array;
use function is_array;
use function method_exists;

use const SWOOLE_BASE;
use const SWOOLE_PROCESS;
use const SWOOLE_SOCK_TCP;
use const SWOOLE_SOCK_TCP6;
use const SWOOLE_SOCK_UDP;
use const SWOOLE_SOCK_UDP6;
use const SWOOLE_SSL;
use const SWOOLE_UNIX_DGRAM;
use const SWOOLE_UNIX_STREAM;

class HttpServerFactory
{
    /**
     * @var string
     */
    public const DEFAULT_HOST = '127.0.0.1';

    /**
     * @var int
     */
    public const DEFAULT_PORT = 8080;

    /**
     * Swoole server supported modes
     *
     * @var int[]
     */
    private const MODES = [
        SWOOLE_BASE,
        SWOOLE_PROCESS,
    ];

    /**
     * Swoole server supported protocols
     *
     * @var int[]
     */
    private const PROTOCOLS = [
        SWOOLE_SOCK_TCP,
        SWOOLE_SOCK_TCP6,
        SWOOLE_SOCK_UDP,
        SWOOLE_SOCK_UDP6,
        SWOOLE_UNIX_DGRAM,
        SWOOLE_UNIX_STREAM,
    ];

    /**
     * @see https://www.swoole.co.uk/docs/modules/swoole-server-methods#swoole_server-__construct
     * @see https://www.swoole.co.uk/docs/modules/swoole-server/predefined-constants for $mode and $protocol constant
     *
     * @throws InvalidArgumentException For invalid $port values.
     * @throws InvalidArgumentException For invalid $mode values.
     * @throws InvalidArgumentException For invalid $protocol values.
     */
    public function __invoke(ContainerInterface $container): SwooleHttpServer
    {
        $config = $container->get('config');
        assert(is_array($config) || $config instanceof ArrayAccess);

        $swooleConfig = $config['mezzio-swoole'] ?? [];
        Assert::isMap($swooleConfig);

        $serverConfig = $swooleConfig['swoole-http-server'] ?? [];
        Assert::isMap($serverConfig);

        $host     = $serverConfig['host'] ?? static::DEFAULT_HOST;
        $port     = $serverConfig['port'] ?? static::DEFAULT_PORT;
        $mode     = $serverConfig['mode'] ?? SWOOLE_BASE;
        $protocol = $serverConfig['protocol'] ?? SWOOLE_SOCK_TCP;

        if ($port < 1 || $port > 65535) {
            throw new InvalidArgumentException('Invalid port');
        }

        if (! in_array($mode, static::MODES, true)) {
            throw new InvalidArgumentException('Invalid server mode');
        }

        $validProtocols = static::PROTOCOLS;
        if (defined('SWOOLE_SSL')) {
            $validProtocols[] = SWOOLE_SOCK_TCP | SWOOLE_SSL;
            $validProtocols[] = SWOOLE_SOCK_TCP6 | SWOOLE_SSL;
        }

        if (! in_array($protocol, $validProtocols, true)) {
            throw new InvalidArgumentException('Invalid server protocol');
        }

        $enableCoroutine = $swooleConfig['enable_coroutine'] ?? false;
        if ($enableCoroutine && method_exists(SwooleRuntime::class, 'enableCoroutine')) {
            SwooleRuntime::enableCoroutine();
        }

        $httpServer    = new SwooleHttpServer($host, $port, $mode, $protocol);
        $serverOptions = $serverConfig['options'] ?? [];

        Assert::isArray($serverOptions);

        $httpServer->set($serverOptions);

        return $httpServer;
    }
}
