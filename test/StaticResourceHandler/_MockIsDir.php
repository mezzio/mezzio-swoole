<?php

/**
 * @see       https://github.com/mezzio/mezzio-swoole for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio-swoole/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio-swoole/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\Swoole\StaticResourceHandler;
/**
 * Mock is_dir so that it always returns true unless directory is BOGUS
 */
function is_dir($dir) {
    return $dir != 'BOGUS';
}