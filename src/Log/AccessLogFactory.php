<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\Swoole\Log;

use Psr\Container\ContainerInterface;
use Zend\Expressive\Swoole\Log\AccessLogFormatterInterface as LegacyAccessLogFormatterInterface;

/**
 * Create and return an access logger.
 *
 * Uses the Psr\Log\LoggerInterface to seed a Psr3AccessLogDecorator instance,
 * falling back to the shipped StdoutLogger if none is present. Additionally,
 * it will look for and use the following configuration, if found:
 *
 * <code>
 * 'mezzio-swoole' => [
 *     'swoole-http-server' => [
 *         'logger' => [
 *             'logger-name' => string, // the name of a service resolving a Psr\Log\LoggerInterface instance
 *             'format' => string, // one of the AccessLogFormatter::FORMAT_* constants
 *             'use-hostname-lookups' => bool, // Set to true to enable hostname lookups
 *         ],
 *     ],
 * ],
 * </code>
 */
class AccessLogFactory
{
    use LoggerResolvingTrait;

    public function __invoke(ContainerInterface $container): AccessLogInterface
    {
        $config = $container->has('config') ? $container->get('config') : [];
        $config = $config['mezzio-swoole']['swoole-http-server']['logger'] ?? [];

        return new Psr3AccessLogDecorator(
            $this->getLogger($container),
            $this->getFormatter($container, $config),
            $config['use-hostname-lookups'] ?? false
        );
    }

    private function getFormatter(ContainerInterface $container, array $config): AccessLogFormatterInterface
    {
        if ($container->has(AccessLogFormatterInterface::class)) {
            return $container->get(AccessLogFormatterInterface::class);
        }

        if ($container->has(LegacyAccessLogFormatterInterface::class)) {
            return $container->get(LegacyAccessLogFormatterInterface::class);
        }

        return new AccessLogFormatter(
            $config['format'] ?? AccessLogFormatter::FORMAT_COMMON
        );
    }
}
