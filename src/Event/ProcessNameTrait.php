<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\Swoole\Event;

use const PHP_OS;

trait ProcessNameTrait
{
    /**
     * Function/callable to use to set the Swoole process name.
     *
     * @internal
     * @var callable
     */
    public static $setProcessName = 'swoole_set_process_name';

    /**
     * Set the process name, only if the current OS supports the operation
     */
    private function setProcessName(string $name): void
    {
        if (PHP_OS === 'Darwin') {
            return;
        }
        (self::$setProcessName)($name);
    }
}
