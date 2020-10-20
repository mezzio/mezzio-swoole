<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
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
    // phpcs:disable WebimpressCodingStandard.Functions.Param.MissingSpecification
    // phpcs:disable WebimpressCodingStandard.Functions.ReturnType.ReturnValue

    public function emergency($message, array $context = [])
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    public function alert($message, array $context = [])
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    public function critical($message, array $context = [])
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    public function error($message, array $context = [])
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    public function warning($message, array $context = [])
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    public function notice($message, array $context = [])
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    public function info($message, array $context = [])
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    public function debug($message, array $context = [])
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    public function log($level, $message, array $context = [])
    {
        foreach ($context as $key => $value) {
            $value   = is_string($value) || is_array($value) ? $value : (string) $value;
            $search  = sprintf('{%s}', $key);
            $message = str_replace($search, $value, $message);
        }
        printf('%s%s', $message, PHP_EOL);
    }
}
