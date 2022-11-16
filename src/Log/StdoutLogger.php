<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 */

declare(strict_types=1);

namespace Mezzio\Swoole\Log;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

use function is_array;
use function is_string;
use function printf;
use function sprintf;
use function str_replace;

use const PHP_EOL;

/**
 * Default logger for logging server start and requests.
 *
 * PSR-3 logger implementation that logs to STDOUT, using a newline after each
 * message. Priority is ignored.
 *
 * @internal
 */
class StdoutLogger implements LoggerInterface
{
    /**
     * @param string $message
     */
    public function emergency($message, array $context = []): void
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    /**
     * @param string $message
     */
    public function alert($message, array $context = []): void
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    /**
     * @param string $message
     */
    public function critical($message, array $context = []): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    /**
     * @param string $message
     */
    public function error($message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    /**
     * @param string $message
     */
    public function warning($message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    /**
     * @param string $message
     */
    public function notice($message, array $context = []): void
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    /**
     * @param string $message
     */
    public function info($message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    /**
     * @param string $message
     */
    public function debug($message, array $context = []): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    /**
     * @param mixed $level Generally a string from a LogLevel constant.
     * @param string $message
     */
    public function log($level, $message, array $context = []): void
    {
        foreach ($context as $key => $value) {
            $value   = is_string($value) || is_array($value) ? $value : (string) $value;
            $search  = sprintf('{%s}', $key);
            $message = str_replace($search, $value, $message);
        }

        printf('%s%s', $message, PHP_EOL);
    }
}
